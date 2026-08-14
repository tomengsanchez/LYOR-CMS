<?php
/** @var object $user */
/** @var array<int,object> $linkedProjects */
use App\PdfTable;
$esc = static fn (string $s): string => PdfTable::esc($s);
?>
<div class="pdf-section"><h3>User</h3>
<table class="pdf-kv"><tbody>
<tr><td>ID</td><td><?= (int) ($user->id ?? 0) ?></td></tr>
<tr><td>Username</td><td><?= $esc((string) ($user->username ?? '')) ?></td></tr>
<?php if (!empty(trim($user->display_name ?? ''))): ?>
<tr><td>Display name</td><td><?= $esc((string) $user->display_name) ?></td></tr>
<?php endif; ?>
<tr><td>Email</td><td><?= $esc((string) ($user->email ?? '-')) ?></td></tr>
<tr><td>Login access</td><td><?= $esc(\App\Models\User::loginAccessLabel($user)) ?></td></tr>
<tr><td>Role</td><td><?= $esc((string) ($user->role_name ?? '-')) ?></td></tr>
<tr><td>Linked Projects</td><td><?php
if (empty($linkedProjects)) {
    echo 'None';
} else {
    $names = [];
    foreach ($linkedProjects as $proj) {
        $names[] = $esc((string) ($proj->name ?? ''));
    }
    echo implode(', ', $names);
}
?></td></tr>
</tbody></table></div>
