<?php
namespace App\Controllers;

use App\Models\AppSettings;
use App\Models\NewsletterSubscriber;
use App\NewsletterSettings;
use Core\Csrf;
use Core\Controller;

class NewsletterController extends Controller
{
    public function form(): void
    {
        $settings = NewsletterSettings::get();
        $this->view('public/subscribe', [
            'branding' => AppSettings::getBrandingConfig(),
            'newsletterSettings' => $settings,
            'publicRobotsNoindex' => true,
            'publicMetaDescription' => 'Subscribe for email updates.',
        ]);
    }

    public function store(): void
    {
        $return = NewsletterSubscriber::safeReturnPath((string) ($_POST['return'] ?? '/subscribe'));
        if (!Csrf::validate()) {
            $_SESSION['newsletter_error'] = 'Could not verify the form. Try again.';
            $this->redirect($return === '/subscribe' ? '/subscribe' : $return);
            return;
        }
        $result = NewsletterSubscriber::subscribePublic($_POST);
        if (!empty($result['ok'])) {
            unset($_SESSION['newsletter_error']);
            $_SESSION['newsletter_message'] = NewsletterSubscriber::genericThanks(
                NewsletterSettings::get()->double_opt_in
            );
        } else {
            unset($_SESSION['newsletter_message']);
            $_SESSION['newsletter_error'] = (string) ($result['error'] ?? 'Could not subscribe.');
        }
        $this->redirect($return);
    }

    public function confirm(string $token): void
    {
        $state = NewsletterSubscriber::confirmByToken($token);
        $this->view('public/subscribe', [
            'branding' => AppSettings::getBrandingConfig(),
            'newsletterSettings' => NewsletterSettings::get(),
            'confirmState' => $state,
            'publicRobotsNoindex' => true,
            'hideSubscribeForm' => true,
            'publicMetaDescription' => 'Confirm your subscription.',
        ]);
    }

    public function unsubscribeForm(string $token): void
    {
        $row = NewsletterSubscriber::findByUnsubToken($token);
        $this->view('public/unsubscribe', [
            'branding' => AppSettings::getBrandingConfig(),
            'unsubToken' => $token,
            'unsubValid' => (bool) $row,
            'unsubDone' => false,
            'publicRobotsNoindex' => true,
            'publicMetaDescription' => 'Unsubscribe from email updates.',
        ]);
    }

    public function unsubscribe(string $token): void
    {
        if (!Csrf::validate()) {
            $this->view('public/unsubscribe', [
                'branding' => AppSettings::getBrandingConfig(),
                'unsubToken' => $token,
                'unsubValid' => (bool) NewsletterSubscriber::findByUnsubToken($token),
                'unsubDone' => false,
                'unsubError' => 'Could not verify the form. Try again.',
                'publicRobotsNoindex' => true,
            ]);
            return;
        }
        $ok = NewsletterSubscriber::unsubscribeByToken($token);
        $this->view('public/unsubscribe', [
            'branding' => AppSettings::getBrandingConfig(),
            'unsubToken' => $token,
            'unsubValid' => $ok,
            'unsubDone' => $ok,
            'publicRobotsNoindex' => true,
        ]);
    }
}
