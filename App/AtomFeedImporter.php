<?php
namespace App;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Core\Auth;

/**
 * Import Atom / Blogger export feeds into posts (and optionally pages).
 *
 * Feed `published` becomes `cms_posts.published_at`:
 * - past datetime → live backdated post
 * - future datetime → status published but hidden until due (`Post::liveSql`)
 *
 * Internal hrefs are rewritten with `SiteUrl` from config/app.php `base_url`.
 * Template feed: docs/samples/cms-atom-import/sample.atom
 */
final class AtomFeedImporter
{
    public const MAX_BYTES = 8388608;

    /**
     * @param array{
     *   update?: bool,
     *   include_pages?: bool,
     *   dry_run?: bool,
     *   limit?: int,
     *   author_id?: int,
     *   base_url?: string|null,
     *   create_missing_categories?: bool,
     *   spread_year?: int|string|null
     * } $opts
     * @return array{
     *   ok: bool,
     *   error?: string,
     *   created: int,
     *   updated: int,
     *   skipped: int,
     *   scheduled: int,
     *   backdated: int,
     *   pages_skipped: int,
     *   errors: list<string>,
     *   items: list<array<string, mixed>>
     * }
     */
    public static function importFile(string $path, array $opts = []): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return self::fail('Feed file is missing or unreadable.');
        }
        $size = filesize($path);
        if ($size === false || $size < 20) {
            return self::fail('Feed file is empty.');
        }
        if ($size > self::MAX_BYTES) {
            return self::fail('Feed file is larger than 8 MB.');
        }
        $xml = file_get_contents($path);
        if (!is_string($xml) || trim($xml) === '') {
            return self::fail('Could not read the feed file.');
        }

        return self::importXml($xml, $opts);
    }

    /**
     * @param array<string, mixed> $opts
     * @return array<string, mixed>
     */
    public static function importXml(string $xml, array $opts = []): array
    {
        $parsed = self::parse($xml);
        if (isset($parsed['error'])) {
            return self::fail((string) $parsed['error']);
        }
        /** @var list<array<string, mixed>> $entries */
        $entries = $parsed['entries'];
        $update = !empty($opts['update']);
        $includePages = !empty($opts['include_pages']);
        $dryRun = !empty($opts['dry_run']);
        $limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
        $authorId = (int) ($opts['author_id'] ?? 0);
        if ($authorId <= 0) {
            $authorId = (int) Auth::id();
        }
        $baseUrl = array_key_exists('base_url', $opts) ? $opts['base_url'] : null;
        if (is_string($baseUrl)) {
            $baseUrl = $baseUrl === '' ? '' : $baseUrl;
        } else {
            $baseUrl = null;
        }
        $createCats = !array_key_exists('create_missing_categories', $opts)
            || !empty($opts['create_missing_categories']);

        $spreadYear = self::normalizeSpreadYear($opts['spread_year'] ?? null);
        if ($spreadYear !== null) {
            $entries = self::applySpreadYear($entries, $spreadYear);
        }

        $pathMap = self::buildPathMap($entries);
        $now = UserTime::nowSql();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $scheduled = 0;
        $backdated = 0;
        $pagesSkipped = 0;
        $errors = [];
        $items = [];
        $imported = 0;

        foreach ($entries as $entry) {
            $kind = (string) $entry['kind'];
            if ($kind === 'page' && !$includePages) {
                $pagesSkipped++;
                $skipped++;
                $items[] = self::itemRow($entry, 'skipped', 'page (not imported)');
                continue;
            }
            if ($limit > 0 && $imported >= $limit) {
                $skipped++;
                $items[] = self::itemRow($entry, 'skipped', 'over limit');
                continue;
            }

            $slug = (string) $entry['slug'];
            $title = (string) $entry['title'];
            if ($title === '' || $slug === '') {
                $skipped++;
                $items[] = self::itemRow($entry, 'skipped', 'missing title or slug');
                continue;
            }

            $body = ImportHtml::cleanAndRewrite((string) $entry['html'], $pathMap, $baseUrl);
            $publishedAt = $entry['published_at'];
            $status = (string) $entry['status'];
            $excerpt = (string) $entry['excerpt'];
            $meta = (string) $entry['meta_description'];
            $labels = $entry['labels'];
            $catId = self::resolveCategoryId($labels, $createCats);
            $tags = self::tagCsv($labels, $catId);
            $isFuture = $status === 'published' && is_string($publishedAt) && $publishedAt > $now;
            $isPast = $status === 'published' && is_string($publishedAt) && $publishedAt <= $now;

            if ($kind === 'page') {
                $existing = Page::findBySlug($slug);
                if ($existing && !$update) {
                    $skipped++;
                    $items[] = self::itemRow($entry, 'skipped', 'page exists');
                    continue;
                }
                if (!$dryRun) {
                    $ok = self::persistPage($existing, [
                        'title' => $title,
                        'slug' => $slug,
                        'body' => $body,
                        'status' => $status === 'published' ? 'published' : 'draft',
                        'meta_description' => $meta !== '' ? $meta : $excerpt,
                        'author_id' => $authorId,
                    ]);
                    if (!$ok) {
                        $errors[] = 'Could not save page: ' . $title;
                        $items[] = self::itemRow($entry, 'error', 'save failed');
                        continue;
                    }
                }
                if ($existing) {
                    $updated++;
                    $items[] = self::itemRow($entry, 'updated', 'page');
                } else {
                    $created++;
                    $items[] = self::itemRow($entry, 'created', 'page');
                }
                $imported++;
                continue;
            }

            $existing = Post::findBySlug($slug);
            if ($existing && !$update) {
                $skipped++;
                $items[] = self::itemRow($entry, 'skipped', 'post exists');
                continue;
            }

            $payload = [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'body' => $body,
                'status' => $status,
                'category_id' => $catId,
                'tags' => $tags,
                'meta_title' => $title,
                'meta_description' => $meta !== '' ? $meta : $excerpt,
                'published_at' => $publishedAt ?? '',
                'author_id' => $authorId,
            ];
            if ($existing) {
                $payload['featured_image_id'] = $existing->featured_image_id ?? null;
                $payload['content_layout'] = $existing->content_layout ?? '';
                $payload['blocks_json'] = $existing->blocks_json ?? null;
                $payload['is_sticky'] = !empty($existing->is_sticky);
                $payload['llm_summary'] = (string) ($existing->llm_summary ?? '');
                $payload['citation_snippet'] = (string) ($existing->citation_snippet ?? '');
                $payload['faq_json'] = $existing->faq_json ?? null;
                $payload['robots_noindex'] = !empty($existing->robots_noindex);
            }

            if (!$dryRun) {
                $ok = self::persistPost($existing, $payload);
                if (!$ok) {
                    $errors[] = 'Could not save post: ' . $title;
                    $items[] = self::itemRow($entry, 'error', 'save failed');
                    continue;
                }
            }
            if ($existing) {
                $updated++;
                $action = 'updated';
            } else {
                $created++;
                $action = 'created';
            }
            if ($isFuture) {
                $scheduled++;
            } elseif ($isPast) {
                $backdated++;
            }
            $items[] = self::itemRow($entry, $action, $isFuture ? 'scheduled' : ($isPast ? 'backdated' : $status));
            $imported++;
        }

        return [
            'ok' => $errors === [],
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'scheduled' => $scheduled,
            'backdated' => $backdated,
            'pages_skipped' => $pagesSkipped,
            'errors' => $errors,
            'items' => $items,
        ];
    }

    /**
     * @return array{entries: list<array<string, mixed>>, error?: string}
     */
    public static function parse(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '' || !str_contains($xml, '<')) {
            return ['entries' => [], 'error' => 'Not a valid Atom feed.'];
        }
        $prev = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$feed instanceof \SimpleXMLElement) {
            return ['entries' => [], 'error' => 'Could not parse the Atom XML (external entities are blocked).'];
        }
        $feed->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $nodes = $feed->xpath('//atom:entry');
        if (!is_array($nodes) || $nodes === []) {
            $nodes = [];
            foreach ($feed->entry as $entry) {
                $nodes[] = $entry;
            }
        }
        $out = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \SimpleXMLElement) {
                continue;
            }
            $row = self::entryFromXml($node);
            if ($row !== null) {
                $out[] = $row;
            }
        }

        return ['entries' => $out];
    }

    /** @return int|null Year 2000–2100, or null to keep feed dates. */
    public static function normalizeSpreadYear(mixed $raw): ?int
    {
        if ($raw === null || $raw === false) {
            return null;
        }
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }
        }
        $year = (int) $raw;
        if ($year < 2000 || $year > 2100) {
            return null;
        }

        return $year;
    }

    /**
     * Evenly space LIVE post dates from 1 Jan through 31 Dec of $year (site timezone).
     * Oldest feed `published` lands in January. Pages and drafts are unchanged.
     *
     * @param list<array<string, mixed>> $entries
     * @return list<array<string, mixed>>
     */
    public static function applySpreadYear(array $entries, int $year): array
    {
        $year = self::normalizeSpreadYear($year);
        if ($year === null) {
            return $entries;
        }
        $idxs = [];
        foreach ($entries as $i => $entry) {
            if (($entry['kind'] ?? '') !== 'post' || ($entry['status'] ?? '') !== 'published') {
                continue;
            }
            $idxs[] = $i;
        }
        $n = count($idxs);
        if ($n === 0) {
            return $entries;
        }
        usort($idxs, static function (int $a, int $b) use ($entries): int {
            $pa = (string) ($entries[$a]['published_at'] ?? '');
            $pb = (string) ($entries[$b]['published_at'] ?? '');
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return $a <=> $b;
        });
        UserTime::apply();
        $tz = new \DateTimeZone(UserTime::timezoneId());
        $start = new \DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $year), $tz);
        $spanDays = (checkdate(2, 29, $year) ? 366 : 365) - 1;
        $hours = [8, 10, 12, 15, 18];
        foreach ($idxs as $k => $i) {
            $day = $n === 1 ? 0 : (int) round($spanDays * $k / ($n - 1));
            $hour = $hours[$k % count($hours)];
            $minute = ($k * 7) % 60;
            $dt = $start->modify('+' . $day . ' days')->setTime($hour, $minute, 0);
            $entries[$i]['published_at'] = $dt->format('Y-m-d H:i:s');
        }

        return self::refreshDestPaths($entries);
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return list<array<string, mixed>>
     */
    public static function refreshDestPaths(array $entries): array
    {
        foreach ($entries as $i => $entry) {
            $slug = (string) ($entry['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            if (($entry['kind'] ?? '') === 'page') {
                $entries[$i]['dest_path'] = self::pageDestPath($slug);
            } else {
                $entries[$i]['dest_path'] = Permalink::urlForPost((object) [
                    'slug' => $slug,
                    'published_at' => $entry['published_at'] ?? null,
                ]);
            }
        }

        return $entries;
    }

    public static function atomDateToSql(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        try {
            $dt = new \DateTimeImmutable($raw);
            $tz = new \DateTimeZone(UserTime::timezoneId());

            return $dt->setTimezone($tz)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array<string, string>
     */
    public static function buildPathMap(array $entries): array
    {
        $map = [];
        foreach ($entries as $entry) {
            $dest = (string) ($entry['dest_path'] ?? '');
            if ($dest === '') {
                continue;
            }
            $destKey = ImportHtml::lookupPath($dest);
            if ($destKey !== '' && $destKey !== '/') {
                $map[$destKey] = $dest;
            }
            foreach ($entry['source_paths'] ?? [] as $src) {
                $key = ImportHtml::lookupPath((string) $src);
                if ($key !== '' && $key !== '/') {
                    $map[$key] = $dest;
                }
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function entryFromXml(\SimpleXMLElement $entry): ?array
    {
        $b = $entry->children('http://schemas.google.com/blogger/2018');
        $type = strtoupper(trim((string) ($b->type ?? '')));
        if ($type === '') {
            $type = 'POST';
        }
        if (!in_array($type, ['POST', 'PAGE'], true)) {
            return null;
        }
        $trashed = strtolower(trim((string) ($b->trashed ?? '')));
        if ($trashed !== '' && !in_array($trashed, ['false', '0', 'no'], true)) {
            return null;
        }
        $bloggerStatus = strtoupper(trim((string) ($b->status ?? 'LIVE')));
        if (in_array($bloggerStatus, ['DELETED', 'TRASHED'], true)) {
            return null;
        }
        $title = trim(html_entity_decode((string) $entry->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($title === '') {
            return null;
        }
        $filename = trim((string) ($b->filename ?? ''));
        $slug = self::slugFromFilename($filename);
        if ($slug === '') {
            $slug = CmsSlug::from($title, $type === 'PAGE' ? 'page' : 'post');
        }
        $html = (string) $entry->content;
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $meta = trim(html_entity_decode((string) ($b->metaDescription ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $excerpt = $meta;
        if ($excerpt === '') {
            $excerpt = self::excerptFromHtml($html);
        }
        $publishedRaw = trim((string) $entry->published);
        $publishedAt = self::atomDateToSql($publishedRaw);
        $status = $bloggerStatus === 'DRAFT' ? 'draft' : 'published';
        if ($status === 'draft') {
            $publishedAt = null;
        } elseif ($publishedAt === null) {
            $publishedAt = UserTime::nowSql();
        }
        $labels = [];
        foreach ($entry->category as $cat) {
            $term = trim(html_entity_decode((string) $cat['term'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($term === '' || str_starts_with($term, 'http') || str_starts_with($term, 'tag:')) {
                continue;
            }
            if (!in_array($term, $labels, true)) {
                $labels[] = $term;
            }
        }
        $kind = $type === 'PAGE' ? 'page' : 'post';
        $destPath = $kind === 'page'
            ? self::pageDestPath($slug)
            : Permalink::urlForPost((object) [
                'slug' => $slug,
                'published_at' => $publishedAt,
            ]);
        $sourcePaths = [];
        if ($filename !== '') {
            $sourcePaths[] = $filename;
            $sourcePaths[] = preg_replace('/\.html?$/i', '', $filename) ?? $filename;
        }
        if ($kind === 'page') {
            $sourcePaths[] = '/p/' . $slug;
            $sourcePaths[] = '/p/' . $slug . '.html';
        } else {
            $sourcePaths[] = '/blog/' . $slug;
        }

        return [
            'kind' => $kind,
            'title' => $title,
            'slug' => $slug,
            'html' => $html,
            'excerpt' => $excerpt,
            'meta_description' => $meta,
            'published_at' => $publishedAt,
            'status' => $status,
            'labels' => $labels,
            'filename' => $filename,
            'dest_path' => $destPath,
            'source_paths' => array_values(array_unique($sourcePaths)),
        ];
    }

    public static function slugFromFilename(string $filename): string
    {
        $filename = trim(str_replace('\\', '/', $filename));
        if ($filename === '') {
            return '';
        }
        $base = basename($filename);
        $base = preg_replace('/\.html?$/i', '', $base) ?? $base;

        return CmsSlug::from($base, '');
    }

    private static function pageDestPath(string $slug): string
    {
        $page = Page::findBySlug($slug);
        if ($page) {
            return Permalink::urlForPage($page);
        }

        return Permalink::urlForPage((object) ['slug' => $slug]);
    }

    /**
     * @param list<string> $labels
     */
    private static function resolveCategoryId(array $labels, bool $createMissing): ?int
    {
        foreach ($labels as $label) {
            $cat = Category::findByName($label);
            if (!$cat) {
                $guess = CmsSlug::from($label, 'category');
                $cat = Category::findBySlug($guess);
            }
            if ($cat) {
                return (int) $cat->id;
            }
        }
        if ($createMissing && isset($labels[0]) && trim($labels[0]) !== '') {
            $id = Category::create(['name' => $labels[0]]);
            return $id > 0 ? $id : null;
        }
        $all = Category::all();
        if (isset($all[0])) {
            return (int) $all[0]->id;
        }

        return null;
    }

    /**
     * @param list<string> $labels
     */
    private static function tagCsv(array $labels, ?int $categoryId): string
    {
        $skip = '';
        if ($categoryId) {
            $cat = Category::find($categoryId);
            $skip = $cat ? strtolower((string) $cat->name) : '';
        }
        $tags = [];
        foreach ($labels as $label) {
            if ($skip !== '' && strtolower($label) === $skip) {
                continue;
            }
            $tags[] = $label;
        }

        return implode(', ', $tags);
    }

    private static function excerptFromHtml(string $html): string
    {
        $plain = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plain = preg_replace('/\s+/', ' ', $plain) ?? $plain;
        if (function_exists('mb_substr')) {
            return mb_substr($plain, 0, 240);
        }

        return substr($plain, 0, 240);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function persistPost(?object $existing, array $data): bool
    {
        if ($existing) {
            return Post::update((int) $existing->id, $data);
        }

        return Post::create($data) > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function persistPage(?object $existing, array $data): bool
    {
        if ($existing) {
            $data['featured_image_id'] = $existing->featured_image_id ?? null;
            $data['content_layout'] = $existing->content_layout ?? '';
            $data['blocks_json'] = $existing->blocks_json ?? null;
            $data['parent_id'] = $existing->parent_id ?? null;
            $data['llm_summary'] = (string) ($existing->llm_summary ?? '');
            $data['citation_snippet'] = (string) ($existing->citation_snippet ?? '');
            $data['faq_json'] = $existing->faq_json ?? null;
            $data['robots_noindex'] = !empty($existing->robots_noindex);

            return Page::update((int) $existing->id, $data);
        }

        return Page::create($data) > 0;
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private static function itemRow(array $entry, string $action, string $note): array
    {
        return [
            'kind' => $entry['kind'] ?? '',
            'title' => $entry['title'] ?? '',
            'slug' => $entry['slug'] ?? '',
            'published_at' => $entry['published_at'] ?? null,
            'action' => $action,
            'note' => $note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fail(string $message): array
    {
        return [
            'ok' => false,
            'error' => $message,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'scheduled' => 0,
            'backdated' => 0,
            'pages_skipped' => 0,
            'errors' => [$message],
            'items' => [],
        ];
    }
}
