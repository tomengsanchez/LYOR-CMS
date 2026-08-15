<?php
/** @var array<string, mixed>|null $publicShare Social meta from App\SocialShare */
if (empty($publicShare) || !is_array($publicShare)) {
    return;
}
$siteSeo = \App\Models\AppSettings::getSiteSeoConfig();
$shareTitle = trim((string) ($publicShare['title'] ?? ''));
$shareDescription = trim((string) ($publicShare['description'] ?? ''));
$shareUrl = trim((string) ($publicShare['url'] ?? ''));
$shareType = trim((string) ($publicShare['type'] ?? 'website'));
$shareSite = trim((string) ($publicShare['site_name'] ?? ''));
$shareImage = trim((string) ($publicShare['image'] ?? ''));
$shareImageType = trim((string) ($publicShare['image_type'] ?? ''));
$shareImageAlt = trim((string) ($publicShare['image_alt'] ?? $shareTitle));
$shareImageWidth = $publicShare['image_width'] ?? null;
$shareImageHeight = $publicShare['image_height'] ?? null;
$sharePublished = $publicShare['published_time'] ?? null;
$shareModified = $publicShare['modified_time'] ?? null;
$shareSection = trim((string) ($publicShare['section'] ?? ''));
$shareTags = is_array($publicShare['tags'] ?? null) ? $publicShare['tags'] : [];
$robotsNoindex = !empty($publicRobotsNoindex);
$jsonAlternateUrl = trim((string) ($publicJsonUrl ?? ''));
$llmSummary = trim((string) ($publicLlmSummary ?? ''));
$citationSnippet = trim((string) ($publicCitationSnippet ?? ''));
$ogLocale = str_replace('_', '-', (string) ($siteSeo->locale ?? 'en_US'));
$twitterHandle = trim((string) ($siteSeo->twitter_handle ?? ''));
$googleVerify = trim((string) ($siteSeo->google_site_verification ?? ''));
$siteKeywords = trim((string) ($siteSeo->site_keywords ?? ''));
$preferredCitation = trim((string) ($siteSeo->preferred_citation ?? ''));
if ($llmSummary === '' && trim((string) ($siteSeo->llm_site_summary ?? '')) !== '') {
    $llmSummary = trim((string) $siteSeo->llm_site_summary);
}
?>
<?php if ($robotsNoindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<?php endif; ?>
<?php if ($citationSnippet !== ''): ?>
<meta name="citation" content="<?= htmlspecialchars($citationSnippet) ?>">
<?php endif; ?>
<?php if ($preferredCitation !== ''): ?>
<meta name="citation_format" content="<?= htmlspecialchars($preferredCitation) ?>">
<?php endif; ?>
<?php if ($llmSummary !== ''): ?>
<meta name="abstract" content="<?= htmlspecialchars($llmSummary) ?>">
<?php endif; ?>
<?php if ($siteKeywords !== ''): ?>
<meta name="keywords" content="<?= htmlspecialchars($siteKeywords) ?>">
<?php endif; ?>
<?php if ($googleVerify !== ''): ?>
<meta name="google-site-verification" content="<?= htmlspecialchars($googleVerify) ?>">
<?php endif; ?>
<?php if ($jsonAlternateUrl !== ''): ?>
<link rel="alternate" type="application/json" href="<?= htmlspecialchars($jsonAlternateUrl) ?>" title="Machine-readable document">
<?php endif; ?>
<?php if ($shareDescription !== ''): ?>
<meta name="description" content="<?= htmlspecialchars($shareDescription) ?>">
<?php endif; ?>
<?php if ($shareUrl !== ''): ?>
<link rel="canonical" href="<?= htmlspecialchars($shareUrl) ?>">
<meta property="og:url" content="<?= htmlspecialchars($shareUrl) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= htmlspecialchars($shareType) ?>">
<meta property="og:title" content="<?= htmlspecialchars($shareTitle) ?>">
<?php if ($shareDescription !== ''): ?>
<meta property="og:description" content="<?= htmlspecialchars($shareDescription) ?>">
<?php endif; ?>
<?php if ($shareSite !== ''): ?>
<meta property="og:site_name" content="<?= htmlspecialchars($shareSite) ?>">
<?php endif; ?>
<meta property="og:locale" content="<?= htmlspecialchars($ogLocale) ?>">
<?php if ($shareImage !== ''): ?>
<meta property="og:image" content="<?= htmlspecialchars($shareImage) ?>">
<meta property="og:image:secure_url" content="<?= htmlspecialchars($shareImage) ?>">
<?php if ($shareImageType !== ''): ?>
<meta property="og:image:type" content="<?= htmlspecialchars($shareImageType) ?>">
<?php endif; ?>
<?php if ($shareImageWidth): ?>
<meta property="og:image:width" content="<?= (int) $shareImageWidth ?>">
<?php endif; ?>
<?php if ($shareImageHeight): ?>
<meta property="og:image:height" content="<?= (int) $shareImageHeight ?>">
<?php endif; ?>
<?php if ($shareImageAlt !== ''): ?>
<meta property="og:image:alt" content="<?= htmlspecialchars($shareImageAlt) ?>">
<?php endif; ?>
<?php endif; ?>
<?php if ($shareType === 'article' && !empty($sharePublished)): ?>
<meta property="article:published_time" content="<?= htmlspecialchars((string) $sharePublished) ?>">
<?php endif; ?>
<?php if ($shareType === 'article' && !empty($shareModified)): ?>
<meta property="article:modified_time" content="<?= htmlspecialchars((string) $shareModified) ?>">
<?php endif; ?>
<?php if ($shareSection !== ''): ?>
<meta property="article:section" content="<?= htmlspecialchars($shareSection) ?>">
<?php endif; ?>
<?php foreach ($shareTags as $shareTag): ?>
<?php $shareTag = trim((string) $shareTag); if ($shareTag === '') { continue; } ?>
<meta property="article:tag" content="<?= htmlspecialchars($shareTag) ?>">
<?php endforeach; ?>
<meta name="twitter:card" content="<?= $shareImage !== '' ? 'summary_large_image' : 'summary' ?>">
<?php if ($twitterHandle !== ''): ?>
<meta name="twitter:site" content="@<?= htmlspecialchars($twitterHandle) ?>">
<?php endif; ?>
<meta name="twitter:title" content="<?= htmlspecialchars($shareTitle) ?>">
<?php if ($shareDescription !== ''): ?>
<meta name="twitter:description" content="<?= htmlspecialchars($shareDescription) ?>">
<?php endif; ?>
<?php if ($shareImage !== ''): ?>
<meta name="twitter:image" content="<?= htmlspecialchars($shareImage) ?>">
<?php if ($shareImageAlt !== ''): ?>
<meta name="twitter:image:alt" content="<?= htmlspecialchars($shareImageAlt) ?>">
<?php endif; ?>
<?php endif; ?>
