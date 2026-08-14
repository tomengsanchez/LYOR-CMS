(function () {
    'use strict';

    var form = document.querySelector('.public-comment-form');
    if (!form) {
        return;
    }

    var parentInput = form.querySelector('#commentParentId');
    var replyHint = form.querySelector('#commentReplyHint');
    var cancelReply = form.querySelector('#commentCancelReply');

    document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var parentId = btn.getAttribute('data-reply-to') || '';
            var author = btn.getAttribute('data-reply-author') || '';
            if (parentInput) {
                parentInput.value = parentId;
            }
            if (replyHint) {
                replyHint.textContent = author !== '' ? 'Replying to ' + author : 'Replying to comment';
                replyHint.classList.remove('d-none');
            }
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            var content = form.querySelector('#commentContent');
            if (content) {
                content.focus();
            }
        });
    });

    if (cancelReply) {
        cancelReply.addEventListener('click', function () {
            if (parentInput) {
                parentInput.value = '';
            }
            if (replyHint) {
                replyHint.textContent = '';
                replyHint.classList.add('d-none');
            }
        });
    }
})();
