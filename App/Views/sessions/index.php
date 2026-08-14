<?php
/** @var array $accessDashboard */
$accessDashboard = $accessDashboard ?? [];
$canRevokeAccess = $canRevokeAccess ?? true;
ob_start();
?>
<div class="page-header mb-4">
    <h1>Access &amp; activity</h1>
    <p class="text-muted mb-0">
        See where you are signed in on the web or via mobile/API, review recent activity, and sign out sessions or revoke tokens you no longer trust.
    </p>
</div>

<?php
$canRevoke = !empty($canRevokeAccess);
$isSelf = true;
$revokeSessionUrlTpl = admin_url('account/sessions/logout/{id}');
$revokeTokenUrlTpl = admin_url('account/tokens/logout/{id}');
$showLogoutOthers = true;
$logoutOthersUrl = admin_url('account/sessions/logout-others');
require __DIR__ . '/../partials/user_access_dashboard.php';
?>
<?php
$content = ob_get_clean();
$pageTitle = 'Access & activity';
$currentPage = 'account-sessions';
require __DIR__ . '/../layout/main.php';
?>
