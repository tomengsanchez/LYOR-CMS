<?php
/**
 * Smoke: Atom importer — backdates, future schedule (request-time visibility), BASE_URL links.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\AtomFeedImporter;
use App\ImportHtml;
use App\Models\Post;
use App\Permalink;
use App\SiteUrl;
use Core\Auth;
use Core\Database;

assert(SiteUrl::href('/blog/hello', '') === '/blog/hello', 'empty base stays root-relative');
assert(SiteUrl::href('/blog/hello', '/paper') === '/paper/blog/hello', 'path prefix base');
assert(SiteUrl::href('/blog/hello', 'http://cms.local') === 'http://cms.local/blog/hello', 'absolute origin base');
assert(SiteUrl::pathPrefix('http://cms.local/paper') === '/paper', 'origin path prefix');
assert(ImportHtml::isGoogleSearchUrl('https://www.google.com/search?q=Alpha'), 'google search detected');
assert(!ImportHtml::isGoogleSearchUrl('https://lalakiph.blogspot.com/p/about.html'), 'blogspot not google');

$rewritten = ImportHtml::cleanAndRewrite(
    '<p>See <a href="https://lalakiph.blogspot.com/2026/09/future-essay.html">next</a> and <a href="https://www.google.com/search?q=Alpha">Alpha</a>.</p>',
    ['/2026/09/future-essay' => '/blog/future-essay'],
    'http://imported.example'
);
assert(str_contains($rewritten, 'http://imported.example/blog/future-essay'), 'internal href uses base_url');
assert(!str_contains($rewritten, 'blogspot.com'), 'blogspot host removed');
assert(!str_contains($rewritten, 'google.com/search'), 'google search unwrapped');
assert(str_contains($rewritten, 'Alpha'), 'google link text kept');

$xxe = AtomFeedImporter::parse('<?xml version="1.0"?><!DOCTYPE feed [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><feed xmlns="http://www.w3.org/2005/Atom"><entry><title>&xxe;</title><content type="html">x</content><published>2020-01-01T00:00:00Z</published></entry></feed>');
$xxeTitle = (string) (($xxe['entries'][0]['title'] ?? '') . ($xxe['error'] ?? ''));
assert(!str_contains($xxeTitle, 'root:'), 'XXE file entity is not expanded');

$db = Database::getInstance();
$admin = $db->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_OBJ);
assert($admin, 'need a user');
Auth::login((int) $admin->id);

$suffix = bin2hex(random_bytes(4));
$pastSlug = 'imp-past-' . $suffix;
$futureSlug = 'imp-future-' . $suffix;
$catName = 'Imp Cat ' . $suffix;
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:blogger="http://schemas.google.com/blogger/2018">
  <title>Import smoke</title>
  <entry>
    <title>Past Essay {$suffix}</title>
    <content type="html"><![CDATA[<p style="color:red">See <a href="https://lalakiph.blogspot.com/2099/12/{$futureSlug}.html">the future one</a> and <a href="https://lalakiph.blogspot.com/p/about.html">About</a>. Also <a href="https://www.google.com/search?q=define+Alpha">Alpha</a>.</p>]]></content>
    <published>2020-01-15T08:00:00.000Z</published>
    <category term="{$catName}"/>
    <category term="Imp Tag {$suffix}"/>
    <blogger:type>POST</blogger:type>
    <blogger:status>LIVE</blogger:status>
    <blogger:filename>/2020/01/{$pastSlug}.html</blogger:filename>
    <blogger:metaDescription>A past post</blogger:metaDescription>
  </entry>
  <entry>
    <title>Future Essay {$suffix}</title>
    <content type="html"><![CDATA[<p>Scheduled body. Related: <a href="https://lalakiph.blogspot.com/2020/01/{$pastSlug}.html">past</a>.</p>]]></content>
    <published>2099-12-01T00:00:00.000Z</published>
    <category term="{$catName}"/>
    <blogger:type>POST</blogger:type>
    <blogger:status>LIVE</blogger:status>
    <blogger:filename>/2099/12/{$futureSlug}.html</blogger:filename>
    <blogger:metaDescription>A future post</blogger:metaDescription>
  </entry>
  <entry>
    <title>About</title>
    <content type="html"><![CDATA[<p>About page from feed</p>]]></content>
    <published>2020-01-01T00:00:00.000Z</published>
    <blogger:type>PAGE</blogger:type>
    <blogger:status>LIVE</blogger:status>
    <blogger:filename>/p/about.html</blogger:filename>
  </entry>
</feed>
XML;

$dry = AtomFeedImporter::importXml($xml, ['dry_run' => true, 'base_url' => 'http://imported.example']);
assert((int) $dry['created'] === 2, 'dry-run counts two posts');
assert(Post::findBySlug($pastSlug) === null, 'dry-run does not write');

$result = AtomFeedImporter::importXml($xml, ['base_url' => 'http://imported.example']);
assert(!empty($result['ok']), 'import ok: ' . implode('; ', $result['errors'] ?? []));
assert((int) $result['created'] === 2, 'two posts created');
assert((int) $result['scheduled'] === 1, 'one scheduled');
assert((int) $result['backdated'] === 1, 'one backdated');
assert((int) $result['pages_skipped'] === 1, 'page skipped by default');

$past = Post::findBySlug($pastSlug);
$future = Post::findBySlug($futureSlug);
assert($past, 'past post saved');
assert($future, 'future post saved');
$expectPast = AtomFeedImporter::atomDateToSql('2020-01-15T08:00:00.000Z');
$expectFuture = AtomFeedImporter::atomDateToSql('2099-12-01T00:00:00.000Z');
assert((string) $past->published_at === (string) $expectPast, 'backdated published_at');
assert((string) $future->published_at === (string) $expectFuture, 'scheduled published_at');
assert(Post::isLive($past), 'past is live');
assert(Post::isScheduled($future), 'future is scheduled');
assert(!Post::isLive($future), 'future is not live yet');
assert(Post::findBySlug($futureSlug, true) === null, 'scheduled hidden from public slug lookup');
assert(Post::findBySlug($pastSlug, true) !== null, 'backdated visible on public slug lookup');

$futurePath = Permalink::urlForPost($future);
$pastPath = Permalink::urlForPost($past);
$aboutPath = Permalink::urlForPage((object) ['slug' => 'about']);
$body = (string) $past->body;
assert(str_contains($body, SiteUrl::href($futurePath, 'http://imported.example')), 'past body links to future via base_url');
assert(str_contains($body, SiteUrl::href($aboutPath, 'http://imported.example')), 'about internal link rewritten');
assert(!str_contains($body, 'blogspot.com'), 'no blogspot leftovers in body');
assert(!str_contains($body, 'style='), 'inline styles stripped');
assert(!str_contains($body, 'google.com/search'), 'google wrapper removed');
assert(str_contains((string) $future->body, SiteUrl::href($pastPath, 'http://imported.example')), 'future body links back');

$skip = AtomFeedImporter::importXml($xml, ['base_url' => 'http://imported.example']);
assert((int) $skip['skipped'] >= 2, 'second import skips existing');
assert((int) $skip['created'] === 0, 'no duplicate create');

Post::softDelete((int) $past->id);
Post::softDelete((int) $future->id);

$index = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
assert(str_contains($index, "'/admin/posts/import'"), 'import route registered');

$samplePath = dirname(__DIR__, 2) . '/docs/samples/cms-atom-import/sample.atom';
assert(is_file($samplePath), 'sample.atom template exists');
$sampleXml = (string) file_get_contents($samplePath);
$sample = AtomFeedImporter::parse($sampleXml);
assert(empty($sample['error']), 'sample.atom parses');
$bySlug = [];
foreach ($sample['entries'] as $e) {
    $bySlug[(string) $e['slug']] = $e;
}
assert(($bySlug['sample-backdated-post']['status'] ?? '') === 'published', 'sample backdated is LIVE');
assert(($bySlug['sample-scheduled-post']['status'] ?? '') === 'published', 'sample scheduled is LIVE');
assert(($bySlug['sample-draft-post']['status'] ?? '') === 'draft', 'sample draft status');
assert(($bySlug['sample-import-notes']['kind'] ?? '') === 'page', 'sample page entry');
$sampleMap = AtomFeedImporter::buildPathMap($sample['entries']);
$sampleBody = ImportHtml::cleanAndRewrite((string) $bySlug['sample-backdated-post']['html'], $sampleMap, 'http://imported.example');
$schedHref = SiteUrl::href((string) $bySlug['sample-scheduled-post']['dest_path'], 'http://imported.example');
assert(str_contains($sampleBody, $schedHref), 'sample internal link rewritten');
assert(!str_contains($sampleBody, 'old-site.example'), 'sample old host removed');
assert(!str_contains($sampleBody, 'google.com/search'), 'sample google wrapper unwrapped');

$spreadParsed = AtomFeedImporter::parse(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:blogger="http://schemas.google.com/blogger/2018">
  <entry><title>S1</title><content type="html">a</content><published>2026-09-09T12:00:00Z</published><blogger:type>POST</blogger:type><blogger:status>LIVE</blogger:status><blogger:filename>/2026/09/spread-one.html</blogger:filename></entry>
  <entry><title>S2</title><content type="html">b</content><published>2026-09-09T13:00:00Z</published><blogger:type>POST</blogger:type><blogger:status>LIVE</blogger:status><blogger:filename>/2026/09/spread-two.html</blogger:filename></entry>
  <entry><title>S3</title><content type="html">c</content><published>2026-09-09T14:00:00Z</published><blogger:type>POST</blogger:type><blogger:status>LIVE</blogger:status><blogger:filename>/2026/09/spread-three.html</blogger:filename></entry>
  <entry><title>Draft</title><content type="html">d</content><published>2026-09-09T15:00:00Z</published><blogger:type>POST</blogger:type><blogger:status>DRAFT</blogger:status><blogger:filename>/2026/09/spread-draft.html</blogger:filename></entry>
</feed>
XML);
$spread = AtomFeedImporter::applySpreadYear($spreadParsed['entries'], 2026);
$spreadDates = [];
$draftAt = '';
foreach ($spread as $e) {
    if (($e['slug'] ?? '') === 'spread-draft') {
        $draftAt = (string) ($e['published_at'] ?? '');
        continue;
    }
    if (($e['kind'] ?? '') === 'post' && ($e['status'] ?? '') === 'published') {
        $spreadDates[] = (string) $e['published_at'];
    }
}
sort($spreadDates);
assert(count($spreadDates) === 3, 'spread keeps three live posts');
assert(str_starts_with($spreadDates[0], '2026-01-01'), 'spread starts 1 January');
assert(str_starts_with($spreadDates[2], '2026-12-31'), 'spread ends 31 December');
assert($spreadDates[0] < $spreadDates[1] && $spreadDates[1] < $spreadDates[2], 'spread is ordered');
assert($draftAt === '' || !str_starts_with($draftAt, '2026-01-01'), 'draft date not spread to January');
assert(AtomFeedImporter::normalizeSpreadYear('') === null, 'empty spread year ignored');
assert(AtomFeedImporter::normalizeSpreadYear('2026') === 2026, 'spread year parses');

echo "cms_atom_import_smoke_test: OK\n";
