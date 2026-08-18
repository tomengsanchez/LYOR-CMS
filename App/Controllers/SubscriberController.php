<?php
namespace App\Controllers;

use App\AdminPath;
use App\CsvExporter;
use App\Models\NewsletterSubscriber;
use Core\Auth;
use Core\Controller;

class SubscriberController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        if (!Auth::can('view_subscribers')) {
            $this->redirect(AdminPath::url());
            return;
        }
        $status = (string) ($_GET['status'] ?? '');
        $q = trim((string) ($_GET['q'] ?? ''));
        $this->view('subscribers/index', [
            'subscribers' => NewsletterSubscriber::allForAdmin($status !== '' ? $status : null, $q),
            'statuses' => NewsletterSubscriber::statuses(),
            'currentStatus' => $status,
            'searchQuery' => $q,
            'pendingCount' => NewsletterSubscriber::pendingCount(),
            'confirmedCount' => NewsletterSubscriber::confirmedCount(),
            'canManage' => Auth::can('manage_subscribers'),
            'canExport' => Auth::can('export_subscribers'),
        ]);
    }

    public function export(): void
    {
        $this->requireCapability('export_subscribers');
        $status = (string) ($_GET['status'] ?? '');
        $rows = NewsletterSubscriber::exportRows($status !== '' ? $status : null);
        CsvExporter::stream(
            'newsletter-subscribers',
            ['Email', 'Status', 'Consented', 'Confirmed', 'Unsubscribed', 'Created'],
            $rows,
            ['email', 'status', 'consent_at', 'confirmed_at', 'unsubscribed_at', 'created_at']
        );
    }

    public function confirm(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::can('manage_subscribers')) {
            $this->redirect(AdminPath::url());
            return;
        }
        NewsletterSubscriber::adminConfirm($id);
        $this->redirect(AdminPath::url('subscribers'));
    }

    public function unsubscribe(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::can('manage_subscribers')) {
            $this->redirect(AdminPath::url());
            return;
        }
        NewsletterSubscriber::adminUnsubscribe($id);
        $this->redirect(AdminPath::url('subscribers'));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        if (!Auth::can('manage_subscribers')) {
            $this->redirect(AdminPath::url());
            return;
        }
        NewsletterSubscriber::delete($id);
        $this->redirect(AdminPath::url('subscribers'));
    }

    protected function csrfRedirectUrl(): string
    {
        return AdminPath::url('subscribers');
    }
}
