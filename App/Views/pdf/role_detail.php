<?php
/** @var object $role */
use App\Capabilities;
use App\PdfTable;
$esc = static fn (string $s): string => PdfTable::esc($s);
$caps = $role->capabilities ?? [];
$labels = [];
if (is_array($caps)) {
    foreach ($caps as $c) {
        $labels[] = Capabilities::getLabel((string) $c);
    }
}
?>
<div class="pdf-section"><h3>Role</h3>
<table class="pdf-kv"><tbody>
<tr><td>Role</td><td><?= $esc((string) ($role->name ?? '')) ?></td></tr>
<tr><td>Capabilities</td><td><?= !empty($labels) ? $esc(implode('; ', $labels)) : 'None' ?></td></tr>
</tbody></table></div>
