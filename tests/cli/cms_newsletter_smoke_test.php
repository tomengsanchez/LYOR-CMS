<?php
/**
 * Smoke: newsletter settings, widget type, subscribe (honeypot + confirm-off), admin actions.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\NewsletterSubscriber;
use App\Models\Widget;
use App\NewsletterSettings;
use Core\Database;

$db = Database::getInstance();
$db->query('SELECT 1 FROM cms_newsletter_subscribers LIMIT 1');

$prev = NewsletterSettings::get();
NewsletterSettings::save([
    'newsletter_enabled' => 1,
    'newsletter_double_opt_in' => 0,
    'newsletter_rate_limit' => 50,
]);
assert(NewsletterSettings::get()->enabled === true, 'newsletter enabled');
assert(NewsletterSettings::get()->double_opt_in === false, 'double opt-in off for smoke');

assert(isset(Widget::types()['newsletter']), 'newsletter widget type');
$cfg = Widget::sanitizeConfig('newsletter', [
    'intro' => 'Join',
    'placeholder' => 'you@example.com',
    'button' => 'Go',
    'consent_label' => 'Yes',
]);
assert($cfg['button'] === 'Go', 'newsletter sanitize');

$stamp = bin2hex(random_bytes(4));
$email = 'smoke.nl.' . $stamp . '@example.com';

$hp = NewsletterSubscriber::subscribePublic([
    'email' => $email,
    'consent' => '1',
    'website' => 'http://spam.test',
]);
assert(!empty($hp['ok']), 'honeypot treated as success');
assert(NewsletterSubscriber::findByEmail($email) === null, 'honeypot does not insert');

$bad = NewsletterSubscriber::subscribePublic([
    'email' => 'not-an-email',
    'consent' => '1',
]);
assert(empty($bad['ok']), 'invalid email rejected');

$ok = NewsletterSubscriber::subscribePublic([
    'email' => $email,
    'consent' => '1',
]);
assert(!empty($ok['ok']), 'subscribe ok');
$row = NewsletterSubscriber::findByEmail($email);
assert($row !== null, 'row stored');
assert(($row->status ?? '') === NewsletterSubscriber::STATUS_CONFIRMED, 'confirmed when opt-in off');

$id = (int) $row->id;
assert(NewsletterSubscriber::adminUnsubscribe($id), 'admin unsubscribe');
$row = NewsletterSubscriber::find($id);
assert(($row->status ?? '') === NewsletterSubscriber::STATUS_UNSUBSCRIBED, 'unsubscribed');
assert(NewsletterSubscriber::delete($id), 'delete');
assert(NewsletterSubscriber::findByEmail($email) === null, 'gone after delete');

assert(NewsletterSubscriber::safeReturnPath('https://evil.test') === '/subscribe', 'block absolute return');
assert(NewsletterSubscriber::safeReturnPath('//evil.test') === '/subscribe', 'block protocol-relative return');
assert(NewsletterSubscriber::safeReturnPath('/blog') === '/blog', 'allow relative return');

NewsletterSettings::save([
    'newsletter_enabled' => $prev->enabled ? 1 : 0,
    'newsletter_double_opt_in' => $prev->double_opt_in ? 1 : 0,
    'newsletter_rate_limit' => $prev->rate_limit_per_hour,
]);

echo "cms_newsletter_smoke_test: OK\n";
