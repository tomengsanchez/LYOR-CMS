<?php
namespace App\Controllers;

use App\AdminPath;

use Core\Controller;
use Core\Auth;
use App\UserSession;
use App\UserAccessDashboard;
use App\ApiToken;
use App\Flash;

class SessionsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $userId = (int) (Auth::id() ?? 0);
        $this->view('sessions/index', [
            'accessDashboard' => UserAccessDashboard::forUser($userId),
            'canRevokeAccess' => true,
        ]);
    }

    public function logoutOthers(): void
    {
        if (!Auth::check()) {
            $this->redirect(AdminPath::url('login'));
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(AdminPath::url('account/sessions'));
        }
        $this->validateCsrf();
        UserSession::revokeOthers();
        Flash::success('Other devices signed out.');
        $this->redirect(AdminPath::url('account/sessions'));
    }

    public function logoutSession(int $id): void
    {
        if (!Auth::check()) {
            $this->redirect(AdminPath::url('login'));
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(AdminPath::url('account/sessions'));
        }
        $this->validateCsrf();
        UserSession::revokeById($id);
        Flash::success('Session signed out.');
        $this->redirect(AdminPath::url('account/sessions'));
    }

    public function logoutToken(int $id): void
    {
        if (!Auth::check()) {
            $this->redirect(AdminPath::url('login'));
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(AdminPath::url('account/sessions'));
        }
        $this->validateCsrf();
        $userId = (int) (Auth::id() ?? 0);
        $ok = ApiToken::revokeByIdForUser((int) $id, $userId);
        if ($ok) {
            Flash::success('API token revoked.');
        } else {
            Flash::error('Token not found or already revoked.');
        }
        $this->redirect(AdminPath::url('account/sessions'));
    }
}
