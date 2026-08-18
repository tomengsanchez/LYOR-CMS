<?php
namespace App;

use App\SocialShare;
use Core\Mailer;

class NewsletterMail
{
    public static function sendConfirm(string $email, string $token): bool
    {
        $url = rtrim(SocialShare::baseUrl(), '/') . '/subscribe/confirm/' . rawurlencode($token);
        $name = (string) (\App\Models\AppSettings::getBrandingConfig()->app_name ?? 'Simple CMS');
        $body = "Confirm your subscription to {$name}.\n\n"
            . "Open this link to start receiving updates:\n{$url}\n\n"
            . "If you did not request this, you can ignore this email.";
        $result = Mailer::send($email, 'Confirm your subscription — ' . $name, $body, false);
        return !empty($result['success']);
    }
}
