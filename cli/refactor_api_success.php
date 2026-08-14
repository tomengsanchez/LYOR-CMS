<?php
/**
 * Replace $this->json( with $this->apiSuccess( in API controllers (success paths).
 * Run: php cli/refactor_api_success.php
 */
$root = dirname(__DIR__);
$skip = ['AuthController.php', 'MetaController.php'];
$files = glob($root . '/App/Controllers/Api/*.php') ?: [];

foreach ($files as $path) {
    if (in_array(basename($path), $skip, true)) {
        continue;
    }
    $c = str_replace("\r\n", "\n", file_get_contents($path));
    $orig = $c;
    $c = str_replace('$this->json(', '$this->apiSuccess(', $c);
    if ($c !== $orig) {
        file_put_contents($path, $c);
        echo 'Updated: ' . basename($path) . "\n";
    }
}

echo "Done. Fix nested success envelopes manually.\n";
