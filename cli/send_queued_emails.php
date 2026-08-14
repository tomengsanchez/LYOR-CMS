#!/usr/bin/env php
<?php
/**
 * Process email queue: send pending notification emails (background / cron).
 * Run after migration_021_email_queue. Web requests only enqueue; this script sends.
 *
 * Usage:
 *   php cli/send_queued_emails.php           Send up to 50 pending emails
 *   php cli/send_queued_emails.php --limit=100
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die('This script must be run from the command line.');
}

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;
use Core\Mailer;

$limit = 50;
if (isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--limit=') === 0) {
            $limit = max(1, min(500, (int) substr($arg, 8)));
            break;
        }
    }
}

$db = Database::getInstance();

$stmt = $db->prepare('SELECT id, to_email, subject, body, body_format FROM email_queue WHERE status = ? ORDER BY id ASC LIMIT ' . (int) $limit);
$stmt->execute(['pending']);
$rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

if (empty($rows)) {
    exit(0);
}

$updateSent = $db->prepare('UPDATE email_queue SET sent_at = NOW(), status = ?, error_message = NULL WHERE id = ?');
$updateFailed = $db->prepare('UPDATE email_queue SET status = ?, error_message = ? WHERE id = ?');

$sent = 0;
$failed = 0;

foreach ($rows as $row) {
    $isHtml = isset($row->body_format) && $row->body_format === 'html';
    $result = Mailer::send($row->to_email, $row->subject, $row->body ?? '', $isHtml);
    if ($result['success']) {
        $updateSent->execute(['sent', $row->id]);
        $sent++;
    } else {
        $err = isset($result['error']) ? substr($result['error'], 0, 500) : 'Unknown error';
        $updateFailed->execute(['failed', $err, $row->id]);
        $failed++;
    }
}

if ($sent > 0 || $failed > 0) {
    echo date('Y-m-d H:i:s') . " Queued emails: sent=$sent failed=$failed\n";
}
