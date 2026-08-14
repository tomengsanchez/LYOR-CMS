<?php
/** @var array<string, mixed>|null $publicJsonLd Schema.org JSON-LD */
if (empty($publicJsonLd) || !is_array($publicJsonLd)) {
    return;
}
$json = json_encode($publicJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    return;
}
?>
<script type="application/ld+json"><?= $json ?></script>
