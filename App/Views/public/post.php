<?php
use App\Models\Media;
use App\Models\Post;
use App\ContentBlocks;
use App\LayoutBuilder;
use App\Permalink;

$postTags = $postTags ?? [];
$comments = $comments ?? [];
$discussion = $discussion ?? \App\DiscussionSettings::get();
$commentMessage = $commentMessage ?? '';
$commentError = $commentError ?? '';
$postUrl = Permalink::urlForPost($post);
$bodyHtml = ContentBlocks::renderEntity($post, (string) ($post->body ?? ''));
$publicTitle = (($post->meta_title ?? '') !== '' ? $post->meta_title : $post->title) . ' — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = 'blog';
$postLayout = \App\PublicTheme::normalizeContentLayout($post->content_layout ?? null);
if ($postLayout !== null) {
    $publicLayout = $postLayout;
}
$hasVisualLayout = LayoutBuilder::hasLayout($post);
$editBarType = 'post';
$editBarId = (int) ($post->id ?? 0);
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
        <?php if (!empty($post->category_name) && !empty($post->category_slug)): ?>
        <a href="/blog/category/<?= htmlspecialchars($post->category_slug) ?>" class="public-badge public-badge-link"><?= htmlspecialchars($post->category_name) ?></a>
        <?php elseif (!empty($post->category_name)): ?>
        <span class="public-badge"><?= htmlspecialchars($post->category_name) ?></span>
        <?php endif; ?>
        <?php if (!empty($post->author_name)): ?>
        <span class="public-author"><?= htmlspecialchars($post->author_name) ?></span>
        <?php endif; ?>
        <?php if (\App\PublicTheme::showPostDates() && !empty($post->published_at)): ?><time class="public-date" datetime="<?= htmlspecialchars($post->published_at) ?>"><?= htmlspecialchars(\App\PublicTheme::formatPublicDate($post->published_at)) ?></time><?php endif; ?>
    </div>
    <?php if (!empty($postTags)): ?>
    <div class="public-post-tags mb-3">
        <?php foreach ($postTags as $tag): ?>
        <a href="/blog/tag/<?= htmlspecialchars($tag->slug) ?>" class="public-tag"><?= htmlspecialchars($tag->name) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php
    $bluf = trim((string) ($post->citation_snippet ?? ''));
    if ($bluf !== ''):
    ?>
    <p class="cms-ai-answer"><?= htmlspecialchars($bluf) ?></p>
    <?php endif; ?>
    <div class="cms-body"><?= $bodyHtml ?></div>
</article>
<?php require __DIR__ . '/partials/comments.php'; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
