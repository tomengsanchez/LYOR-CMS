<?php
$googleCfg = $googleCfg ?? \App\GoogleSettings::get();
$gaId = (string) ($googleCfg->analytics_id ?? '');
$adsId = (string) ($googleCfg->ads_id ?? '');
$adsense = (string) ($googleCfg->adsense_client ?? '');
$loaderId = \App\GoogleSettings::gtagLoaderId($googleCfg);
?>
<?php if ($loaderId !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($loaderId) ?>"></script>
<script src="/public/assets/js/public/google-tags.js" id="cmsGoogleTags"
    data-ga-id="<?= htmlspecialchars($gaId) ?>"
    data-ads-id="<?= htmlspecialchars($adsId) ?>"></script>
<?php endif; ?>
<?php if ($adsense !== ''): ?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($adsense) ?>" crossorigin="anonymous"></script>
<?php endif; ?>
