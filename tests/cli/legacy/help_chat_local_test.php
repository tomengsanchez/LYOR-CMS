<?php
/**
 * Smoke: Ask Help local answer from help context (no LLM).
 * Run: php tests/cli/help_chat_local_test.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\HelpChatContext;
use App\HelpChatService;

$ctx = HelpChatContext::forPage('profile');
if (($ctx['content_key'] ?? '') === '' || strlen($ctx['text'] ?? '') < 50) {
    fwrite(STDERR, "FAIL: help context for profile too short\n");
    exit(1);
}

$result = HelpChatService::ask(1, 'How do I add a new profile?', 'profile');
if (empty($result['ok']) || trim((string) ($result['reply'] ?? '')) === '') {
    fwrite(STDERR, 'FAIL: ask returned ' . json_encode($result) . "\n");
    exit(1);
}

if (!in_array($result['mode'] ?? '', ['local', 'local_fallback', 'llm'], true)) {
    fwrite(STDERR, "FAIL: unexpected mode\n");
    exit(1);
}

echo "OK help_chat local (" . $result['mode'] . "), context chars=" . strlen($ctx['text']) . ", reply chars=" . strlen($result['reply']) . "\n";
