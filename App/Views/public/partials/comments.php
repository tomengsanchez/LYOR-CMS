<?php
/** @var object $post */
/** @var array $comments */
/** @var object $discussion */
/** @var string $commentMessage */
/** @var string $commentError */

use App\Permalink;

if (empty($discussion->comments_enabled)) {
    return;
}
?>
<section class="public-comments" id="comments">
    <h2 class="public-comments-title">Comments</h2>

    <?php if (!empty($commentMessage)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($commentMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($commentError)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($commentError) ?></div>
    <?php endif; ?>

    <?php if (!empty($comments)): ?>
    <ol class="public-comment-list">
        <?php foreach ($comments as $comment): ?>
        <li class="public-comment" id="comment-<?= (int)$comment->id ?>">
            <div class="public-comment-meta">
                <strong><?= htmlspecialchars($comment->author_name ?? '') ?></strong>
                <?php if (!empty($comment->created_at)): ?>
                <time datetime="<?= htmlspecialchars($comment->created_at) ?>"><?= htmlspecialchars($comment->created_at) ?></time>
                <?php endif; ?>
            </div>
            <div class="public-comment-body"><?= nl2br(htmlspecialchars($comment->content ?? '')) ?></div>
            <button type="button" class="btn btn-link btn-sm p-0 public-comment-reply"
                data-reply-to="<?= (int)$comment->id ?>"
                data-reply-author="<?= htmlspecialchars($comment->author_name ?? '') ?>">Reply</button>
            <?php if (!empty($comment->replies)): ?>
            <ol class="public-comment-replies">
                <?php foreach ($comment->replies as $reply): ?>
                <li class="public-comment" id="comment-<?= (int)$reply->id ?>">
                    <div class="public-comment-meta">
                        <strong><?= htmlspecialchars($reply->author_name ?? '') ?></strong>
                        <?php if (!empty($reply->created_at)): ?>
                        <time datetime="<?= htmlspecialchars($reply->created_at) ?>"><?= htmlspecialchars($reply->created_at) ?></time>
                        <?php endif; ?>
                    </div>
                    <div class="public-comment-body"><?= nl2br(htmlspecialchars($reply->content ?? '')) ?></div>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <p class="text-muted">No comments yet. Be the first to reply.</p>
    <?php endif; ?>

    <div class="public-comment-form-wrap">
        <h3 class="h5">Leave a comment</h3>
        <p id="commentReplyHint" class="text-muted small mb-2 d-none"></p>
        <form method="post" action="/comment/post/<?= (int)$post->id ?>" class="public-comment-form">
            <?= \Core\Csrf::field() ?>
            <input type="hidden" name="parent_id" id="commentParentId" value="">
            <div class="visually-hidden" aria-hidden="true">
                <label for="commentWebsite">Website</label>
                <input type="text" name="website" id="commentWebsite" tabindex="-1" autocomplete="off">
            </div>
            <div class="row g-3">
                <?php if (!empty($discussion->require_name_email)): ?>
                <div class="col-md-6">
                    <label class="form-label" for="commentAuthorName">Name</label>
                    <input type="text" name="author_name" id="commentAuthorName" class="form-control" required maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="commentAuthorEmail">Email</label>
                    <input type="email" name="author_email" id="commentAuthorEmail" class="form-control" required maxlength="190">
                </div>
                <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label" for="commentAuthorName">Name (optional)</label>
                    <input type="text" name="author_name" id="commentAuthorName" class="form-control" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="commentAuthorEmail">Email (optional)</label>
                    <input type="email" name="author_email" id="commentAuthorEmail" class="form-control" maxlength="190">
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label" for="commentContent">Comment</label>
                    <textarea name="content" id="commentContent" class="form-control" rows="4" required maxlength="5000"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Post comment</button>
                    <button type="button" class="btn btn-outline-secondary" id="commentCancelReply">Cancel reply</button>
                </div>
            </div>
        </form>
    </div>
</section>
<script src="/public/assets/js/public/comments.js"></script>
