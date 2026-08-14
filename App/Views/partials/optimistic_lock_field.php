<?php
/** @var object|null $record */
use App\OptimisticLock;

$value = OptimisticLock::formatForInput(OptimisticLock::fromRecord($record ?? null));
if ($value === '') {
    return;
}
?>
<input type="hidden" name="record_updated_at" value="<?= htmlspecialchars($value) ?>">
