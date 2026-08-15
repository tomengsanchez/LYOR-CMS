<?php
namespace App\Controllers;

use App\AdminPath;
use App\Flash;
use App\Models\Redirect;
use Core\Controller;

class RedirectController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('manage_settings');
        $this->view('redirects/index', [
            'redirects' => Redirect::all(),
            'formError' => $_SESSION['redirect_form_error'] ?? '',
        ]);
        unset($_SESSION['redirect_form_error']);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_settings');
        $id = Redirect::create([
            'from_path' => $_POST['from_path'] ?? '',
            'to_url' => $_POST['to_url'] ?? '',
            'status_code' => (int) ($_POST['status_code'] ?? 301),
            'is_active' => !empty($_POST['is_active']),
            'note' => $_POST['note'] ?? '',
        ]);
        if ($id <= 0) {
            $_SESSION['redirect_form_error'] = 'Could not save redirect. Check paths (unique, not /admin or /api) and target URL.';
            $this->redirect(AdminPath::url('redirects'));
            return;
        }
        Flash::success('Redirect created.');
        $this->redirect(AdminPath::url('redirects'));
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_settings');
        if (!Redirect::update($id, [
            'from_path' => $_POST['from_path'] ?? '',
            'to_url' => $_POST['to_url'] ?? '',
            'status_code' => (int) ($_POST['status_code'] ?? 301),
            'is_active' => !empty($_POST['is_active']),
            'note' => $_POST['note'] ?? '',
        ])) {
            Flash::error('Could not update redirect.');
        } else {
            Flash::success('Redirect updated.');
        }
        $this->redirect(AdminPath::url('redirects'));
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('manage_settings');
        if (Redirect::delete($id)) {
            Flash::success('Redirect deleted.');
        } else {
            Flash::error('Could not delete redirect.');
        }
        $this->redirect(AdminPath::url('redirects'));
    }
}
