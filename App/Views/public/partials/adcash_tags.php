<?php
$adcashCfg = $adcashCfg ?? \App\AdcashSettings::get();
$adcashZones = \App\AdcashSettings::activeZones($adcashCfg);
?>
<?php if ($adcashZones !== []): ?>
<script id="aclib" type="text/javascript" data-cfasync="false" src="https://acscdn.com/script/aclib.js"></script>
<script src="/public/assets/js/public/adcash-tags.js" id="cmsAdcashTags"
    <?php foreach ($adcashZones as $prop => $zoneId): ?>
    data-zone-<?= htmlspecialchars(str_replace('_', '-', $prop)) ?>="<?= htmlspecialchars($zoneId) ?>"
    <?php endforeach; ?>
></script>
<?php endif; ?>
