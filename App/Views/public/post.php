<?php
use App\Models\Media;
use App\Models\Post;
use App\ContentBlocks;
use App\LayoutBuilder;
use App\Permalink;
use App\PublicToc;

$postTags = $postTags ?? [];
$comments = $comments ?? [];
$discussion = $discussion ?? \App\DiscussionSettings::get();
$commentMessage = $commentMessage ?? '';
$commentError = $commentError ?? '';
$postUrl = Permalink::urlForPost($post);
$contentLocked = !empty($contentLocked);
if ($contentLocked) {
    $bodyHtml = '';
    $tocNav = '';
} else {
    $bodyHtml = ContentBlocks::renderEntity($post, (string) ($post->body ?? ''));
    $tocPack = PublicToc::enhance($bodyHtml);
    $bodyHtml = $tocPack['html'];
    $tocNav = PublicToc::renderNav($tocPack['items']);
}
$publicTitle = (($post->meta_title ?? '') !== '' ? $post->meta_title : $post->title) . ' — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = 'blog';
$postLayout = \App\PublicTheme::normalizeContentLayout($post->content_layout ?? null);
if ($postLayout !== null) {
    $publicLayout = $postLayout;
}
$hasVisualLayout = !$contentLocked && LayoutBuilder::hasLayout($post);
$editBarType = 'post';
$editBarId = (int) ($post->id ?? 0);
$authorUrl = Post::authorPublicUrl($post);
$neighbors = $neighborPosts ?? ['previous' => null, 'next' => null];
$updatedAt = trim((string) ($post->updated_at ?? ''));
$publishedAt = trim((string) ($post->published_at ?? ''));
$showUpdated = $updatedAt !== '' && $publishedAt !== '' && $updatedAt > $publishedAt
    && (strtotime($updatedAt) - strtotime($publishedAt)) > 3600;
$shareTitle = rawurlencode((string) $post->title);
$shareAbs = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . $postUrl;
ob_start();
?>
<?php require __DIR__ . '/partials/edit_bar.php'; ?>
<article class="public-card public-article">
    <a href="/blog" class="public-back">&larr; Back to blog</a>
    <?php if (!$hasVisualLayout && Post::hasFeaturedImage($post)): ?>
    <figure class="public-featured-figure mb-4">
        <?= Media::responsiveImg((int) $post->featured_image_id, [
            'alt' => (string) ($post->featured_alt_text ?? $post->title),
            'class' => 'public-featured-img',
            'sizes' => '(max-width: 768px) 100vw, min(1320px, 100vw)',
            'preferred_width' => 1200,
            'loading' => 'eager',
        ]) ?>
    </figure>
    <?php endif; ?>
    <?php if (!$hasVisualLayout): ?>
    <h1><?= htmlspecialchars($post->title) ?></h1>
    <?php endif; ?>
    <div class="public-post-meta mb-3">
        <?php if (!empty($post->is_sticky)): ?>
        <span class="public-badge">Pinned</span>
        <?php endif; ?>
        <?php if (!empty($post->category_name) && !empty($post->category_slug)): ?>
        <a href="/blog/category/<?= htmlspecialchars($post->category_slug) ?>" class="public-badge public-badge-link"><?= htmlspecialchars($post->category_name) ?></a>
        <?php elseif (!empty($post->category_name)): ?>
        <span class="public-badge"><?= htmlspecialchars($post->category_name) ?></span>
        <?php endif; ?>
        <?php if (!empty($post->author_name) && $authorUrl !== ''): ?>
        <a href="<?= htmlspecialchars($authorUrl) ?>" class="public-author"><?= htmlspecialchars($post->author_name) ?></a>
        <?php elseif (!empty($post->author_name)): ?>
        <span class="public-author"><?= htmlspecialchars($post->author_name) ?></span>
        <?php endif; ?>
        <?php if (\App\PublicTheme::showPostDates() && !empty($post->published_at)): ?><time class="public-date" datetime="<?= htmlspecialchars($post->published_at) ?>"><?= htmlspecialchars(\App\PublicTheme::formatPublicDate($post->published_at)) ?></time><?php endif; ?>
        <?php if ($showUpdated): ?>
        <time class="public-date public-date--updated" datetime="<?= htmlspecialchars($updatedAt) ?>">Updated <?= htmlspecialchars(\App\PublicTheme::formatPublicDate($updatedAt)) ?></time>
        <?php endif; ?>
        <?php if (!$contentLocked): $readMins = Post::readingMinutes($post); if ($readMins > 0): ?>
        <span class="public-read-time"><?= (int) $readMins ?> min read</span>
        <?php endif; endif; ?>
    </div>
    <?php if (!empty($postTags)): ?>
    <div class="public-post-tags mb-3">
        <?php foreach ($postTags as $tag): ?>
        <a href="/blog/tag/<?= htmlspecialchars($tag->slug) ?>" class="public-tag"><?= htmlspecialchars($tag->name) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($contentLocked): ?>
    <?php $unlockType = 'post'; $unlockEntity = $post; require __DIR__ . '/partials/password_gate.php'; ?>
    <?php else: ?>
    <?php
    $bluf = trim((string) ($post->citation_snippet ?? ''));
    if ($bluf !== ''):
    ?>
    <p class="cms-ai-answer"><?= htmlspecialchars($bluf) ?></p>
    <?php endif; ?>
    <?= $tocNav ?>
    <div class="cms-body"><?= $bodyHtml ?></div>
    <?php endif; ?>
    <nav class="public-share" aria-label="Share this post">
        <span class="public-share-label">Share</span>
        <button type="button" class="public-share-btn" data-copy-url="<?= htmlspecialchars($shareAbs !== '' ? $shareAbs : $postUrl) ?>" data-copied-label="Copied">Copy link</button>
        <a class="public-share-btn" href="mailto:?subject=<?= $shareTitle ?>&amp;body=<?= rawurlencode(($shareAbs !== '' ? $shareAbs : $postUrl)) ?>">Email</a>
        <button type="button" class="public-share-btn" hidden data-native-share data-share-title="<?= htmlspecialchars((string) $post->title) ?>" data-share-url="<?= htmlspecialchars($shareAbs !== '' ? $shareAbs : $postUrl) ?>">Share</button>
    </nav>
</article>
<?php
$relatedPosts = $relatedPosts ?? Post::related($post, 3);
if ($relatedPosts !== []):
?>
<nav class="public-related" aria-label="Related posts">
    <h2 class="public-related-title">Related posts</h2>
    <ul class="public-related-list">
        <?php foreach ($relatedPosts as $rel): ?>
        <li><a href="<?= htmlspecialchars(\App\Permalink::urlForPost($rel)) ?>"><?= htmlspecialchars($rel->title) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php endif; ?>
<?php if (!empty($neighbors['previous']) || !empty($neighbors['next'])): ?>
<nav class="public-post-nav" aria-label="Post navigation">
    <?php if (!empty($neighbors['previous'])): ?>
    <a class="public-post-nav-prev" href="<?= htmlspecialchars(Permalink::urlForPost($neighbors['previous'])) ?>">
        <span class="public-post-nav-label">Previous</span>
        <?= htmlspecialchars((string) $neighbors['previous']->title) ?>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
    <?php if (!empty($neighbors['next'])): ?>
    <a class="public-post-nav-next" href="<?= htmlspecialchars(Permalink::urlForPost($neighbors['next'])) ?>">
        <span class="public-post-nav-label">Next</span>
        <?= htmlspecialchars((string) $neighbors['next']->title) ?>
    </a>
    <?php endif; ?>
</nav>
<?php endif; ?>
<?php if (empty($contentLocked)): ?>
<?php require __DIR__ . '/partials/comments.php'; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
