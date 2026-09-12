<?php
/**
 * Import an Atom / Blogger export into posts (optional pages).
 *
 * Uses config/app.php `base_url` for rewritten internal links.
 * Feed dates become published_at: past = live backdated, future = hidden until due.
 *
 * Usage:
 *   php cli/import_atom_feed.php [path/to/feed.atom]
 *   php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom --dry-run --verbose
 *   php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom
 *   php cli/import_atom_feed.php path/to/feed.atom --update --include-pages
 *   php cli/import_atom_feed.php path/to/feed.atom --limit=5
 *   php cli/import_atom_feed.php xyz/feed.atom --spread-year=2026
 */
require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/cli_script_args.php';

use App\AtomFeedImporter;
use Core\Auth;
use Core\Database;

$argvList = $argv ?? [];
if (paper_cli_has_flag($argvList, 'help') || paper_cli_has_flag($argvList, 'h')) {
    echo "Import Atom/Blogger feed into CMS posts.\n\n";
    echo "  php cli/import_atom_feed.php [feed.atom] [--dry-run] [--update] [--include-pages] [--limit=N] [--author-id=N] [--spread-year=YYYY]\n\n";
    echo "Template: docs/samples/cms-atom-import/sample.atom\n";
    echo "Defaults: posts only (PAGE entries skipped), skip existing slugs, use BASE_URL from config/app.php.\n";
    echo "Scheduling: status stays published; public lists hide the post until published_at <= now.\n";
    echo "Spread year: evenly space LIVE post dates from 1 Jan through 31 Dec (oldest feed date first).\n";
    exit(0);
}

$path = null;
foreach (array_slice($argvList, 1) as $arg) {
    if (!is_string($arg) || str_starts_with($arg, '--')) {
        continue;
    }
    $path = $arg;
    break;
}
if ($path === null) {
    $root = dirname(__DIR__);
    $guesses = [
        $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . 'cms-atom-import' . DIRECTORY_SEPARATOR . 'sample.atom',
        $root . DIRECTORY_SEPARATOR . 'xyz' . DIRECTORY_SEPARATOR . 'feed.atom',
    ];
    foreach ($guesses as $guess) {
        if (is_file($guess)) {
            $path = $guess;
            break;
        }
    }
}
if ($path === null) {
    fwrite(STDERR, "Pass a feed path, e.g. php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom\n");
    exit(1);
}
if (!str_contains($path, '/') && !str_contains($path, '\\') && is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . $path)) {
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $path;
}

$db = Database::getInstance();
$authorId = (int) (paper_cli_arg_value($argvList, 'author-id', '0') ?? '0');
if ($authorId <= 0) {
    $admin = $db->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_OBJ);
    if (!$admin) {
        fwrite(STDERR, "No users found. Run php cli/migrate.php first.\n");
        exit(1);
    }
    $authorId = (int) $admin->id;
}
Auth::login($authorId);

$limitRaw = paper_cli_arg_value($argvList, 'limit', '0');
$spreadYear = null;
if (paper_cli_has_flag($argvList, 'spread-year')) {
    $spreadRaw = trim((string) (paper_cli_arg_value($argvList, 'spread-year', '') ?? ''));
    $spreadYear = $spreadRaw !== '' ? $spreadRaw : date('Y');
}
$result = AtomFeedImporter::importFile($path, [
    'update' => paper_cli_has_flag($argvList, 'update'),
    'include_pages' => paper_cli_has_flag($argvList, 'include-pages'),
    'dry_run' => paper_cli_has_flag($argvList, 'dry-run'),
    'limit' => (int) ($limitRaw ?? '0'),
    'author_id' => $authorId,
    'spread_year' => $spreadYear,
]);

if (!empty($result['error']) && (int) ($result['created'] ?? 0) === 0 && (int) ($result['updated'] ?? 0) === 0) {
    fwrite(STDERR, (string) $result['error'] . "\n");
    exit(1);
}

$dry = paper_cli_has_flag($argvList, 'dry-run') ? ' (dry-run)' : '';
echo "Atom import{$dry}: created={$result['created']} updated={$result['updated']} skipped={$result['skipped']}"
    . " scheduled={$result['scheduled']} backdated={$result['backdated']} pages_skipped={$result['pages_skipped']}\n";
echo "Internal links use base_url=" . (defined('BASE_URL') ? (BASE_URL !== '' ? BASE_URL : '(empty, site-root paths)') : '') . "\n";
if (!empty($result['errors'])) {
    foreach ($result['errors'] as $err) {
        fwrite(STDERR, "  error: {$err}\n");
    }
}
if (paper_cli_has_flag($argvList, 'verbose')) {
    foreach ($result['items'] as $item) {
        echo '  [' . ($item['action'] ?? '') . '] ' . ($item['kind'] ?? '') . ' ' . ($item['slug'] ?? '')
            . ' — ' . ($item['note'] ?? '') . "\n";
    }
}

exit(!empty($result['ok']) ? 0 : 1);
