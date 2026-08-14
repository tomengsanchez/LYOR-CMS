<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($post->title) ?></h2>
    <div>
        <?php if ($post->status === 'published'): ?><a href="/blog/<?= htmlspecialchars($post->slug) ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener">View public</a><?php endif; ?>
        <?php if (\Core\Auth::can('edit_posts')): ?><a href="<?= admin_url('posts/edit/' . (int)$post->id ) ?>" class="btn btn-primary">Edit</a><?php endif; ?>
        <a href="<?= admin_url('posts') ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
<div class="card"><div class="card-body">
<p><strong>Category:</strong> <?= htmlspecialchars($post->category_name ?? '—') ?> · <strong>Status:</strong> <?= htmlspecialchars($post->status) ?>
<?php if (!empty($post->robots_noindex)): ?> · <span class="badge bg-secondary">noindex</span><?php endif; ?></p>
<?php if (!empty($post->meta_title) || !empty($post->meta_description)): ?>
<p class="mb-1"><strong>SEO title:</strong> <?= htmlspecialchars($post->meta_title ?: '—') ?></p>
<p class="mb-0"><strong>Meta description:</strong> <?= htmlspecialchars($post->meta_description ?: '—') ?></p>
<?php endif; ?>
<?php if (!empty($post->llm_summary)): ?>
<p class="mt-2 mb-0"><strong>LLM summary:</strong> <?= htmlspecialchars($post->llm_summary) ?></p>
<?php endif; ?>
<?php if (\App\Models\Post::hasFeaturedImage($post)): ?>
<div class="mb-3">
    <strong>Featured image</strong>
    <div class="featured-image-preview mt-2">
        <img src="/serve/media/<?= (int)$post->featured_image_id ?>" alt="<?= htmlspecialchars($post->featured_alt_text ?? $post->title) ?>" class="public-featured-img">
        <?php if (!empty($post->featured_width) && !empty($post->featured_height)): ?>
        <p class="text-muted small mb-0 mt-1"><?= (int)$post->featured_width ?>×<?= (int)$post->featured_height ?> px · Public URL: <code>/share/media/<?= (int)$post->featured_image_id ?></code></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php if (!empty($post->excerpt)): ?><p class="text-muted"><?= htmlspecialchars($post->excerpt) ?></p><?php endif; ?>
<div class="cms-preview border rounded p-3 mt-3"><?= $post->body ?></div>
</div></div>
<?php $content = ob_get_clean(); $pageTitle = $post->title; $currentPage = 'posts'; require __DIR__ . '/../layout/main.php';
