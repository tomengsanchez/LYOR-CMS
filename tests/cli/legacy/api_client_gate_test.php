<?php
/**
 * Smoke test for API client id/secret hashing (no HTTP).
 * php tests/cli/api_client_gate_test.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ApiClients;
use App\ApiErrorCode;

$plain = ApiClients::generateSecret();
$hash = ApiClients::hashSecret($plain);
if (!ApiClients::verifySecret($plain, $hash)) {
    fwrite(STDERR, "password_verify failed for generated secret\n");
    exit(1);
}
if (ApiClients::verifySecret('wrong', $hash)) {
    fwrite(STDERR, "wrong secret should not verify\n");
    exit(1);
}
if (ApiClients::normalizeId('Paper-Mobile') !== 'paper-mobile') {
    fwrite(STDERR, "normalizeId should lowercase\n");
    exit(1);
}
if (ApiClients::normalizeId('bad id!') !== '') {
    fwrite(STDERR, "invalid id should normalize to empty\n");
    exit(1);
}
if (ApiErrorCode::UNAUTHORIZED_CLIENT !== 'UNAUTHORIZED_CLIENT') {
    fwrite(STDERR, "UNAUTHORIZED_CLIENT constant missing\n");
    exit(1);
}

echo "api_client_gate_test: OK\n";
