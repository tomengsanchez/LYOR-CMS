(function () {
    var cfg = window.helpChatConfig;
    if (!cfg || !cfg.enabled || !cfg.url) {
        return;
    }

    var root = document.getElementById('paperHelpChat');
    if (!root) {
        return;
    }

    var toggleBtn = document.getElementById('paperHelpChatToggle');
    var panel = document.getElementById('paperHelpChatPanel');
    var form = document.getElementById('paperHelpChatForm');
    var input = document.getElementById('paperHelpChatInput');
    var messages = document.getElementById('paperHelpChatMessages');
    var fullHelpLink = document.getElementById('paperHelpChatFullHelp');
    var screenLabel = document.getElementById('paperHelpChatScreen');
    var busy = false;

    function readMetaCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function currentCsrf() {
        return readMetaCsrf() || cfg.csrfToken || '';
    }

    function storeCsrf(token) {
        if (!token) {
            return;
        }
        cfg.csrfToken = token;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', token);
        }
    }

    function setOpen(open) {
        if (!panel || !toggleBtn) {
            return;
        }
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && input) {
            setTimeout(function () {
                input.focus();
            }, 50);
        }
    }

    function appendBubble(text, kind) {
        if (!messages) {
            return;
        }
        var el = document.createElement('div');
        el.className = 'paper-help-chat-bubble paper-help-chat-bubble--' + kind;
        el.textContent = text;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
    }

    function setBusy(isBusy) {
        busy = isBusy;
        if (form) {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = isBusy;
            }
        }
        if (input) {
            input.disabled = isBusy;
        }
    }

    function updateHelpLink(url) {
        if (fullHelpLink && url) {
            fullHelpLink.href = url;
        }
    }

    function friendlyError(message) {
        var msg = (message || '').toString();
        if (/csrf/i.test(msg)) {
            return 'Your session security check expired. Please send your question again.';
        }
        if (msg === '') {
            return 'Sorry, Ask Help could not answer right now. Try again or open Help from the menu.';
        }
        return msg;
    }

    function postChat(message, csrfToken) {
        return fetch(cfg.url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                page_key: cfg.pageKey || '',
                csrf_token: csrfToken
            })
        }).then(function (res) {
            return res.text().then(function (text) {
                var json = null;
                try {
                    json = text ? JSON.parse(text) : null;
                } catch (err) {
                    json = null;
                }
                return { ok: res.ok, status: res.status, json: json, raw: text };
            });
        });
    }

    if (screenLabel && cfg.pageLabel) {
        screenLabel.textContent = 'Screen: ' + cfg.pageLabel;
    }
    updateHelpLink(cfg.helpUrl || '/admin/help');
    storeCsrf(currentCsrf());

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var open = !(panel && panel.classList.contains('is-open'));
            setOpen(open);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy || !input) {
                return;
            }
            var message = (input.value || '').trim();
            if (!message) {
                return;
            }
            appendBubble(message, 'user');
            input.value = '';
            setBusy(true);
            appendBubble('Thinking…', 'assistant');
            var thinking = messages ? messages.lastChild : null;

            var token = currentCsrf();
            postChat(message, token)
                .then(function (result) {
                    var json = result.json || {};
                    if (json.data && json.data.csrf_token) {
                        storeCsrf(json.data.csrf_token);
                    }

                    // One automatic retry when the page token was stale.
                    if (
                        result.status === 403
                        && json.error
                        && /csrf|security check/i.test(String(json.error.message || ''))
                        && json.data
                        && json.data.csrf_token
                    ) {
                        return postChat(message, json.data.csrf_token).then(function (retry) {
                            if (retry.json && retry.json.data && retry.json.data.csrf_token) {
                                storeCsrf(retry.json.data.csrf_token);
                            }
                            return retry;
                        });
                    }
                    return result;
                })
                .then(function (result) {
                    if (thinking && thinking.parentNode) {
                        thinking.parentNode.removeChild(thinking);
                    }
                    var json = result.json || {};
                    if (!result.ok || !json || !json.success) {
                        var errMsg = friendlyError(
                            json && json.error ? json.error.message : ''
                        );
                        appendBubble(errMsg, 'error');
                        return;
                    }
                    var data = json.data || {};
                    if (data.help_url) {
                        updateHelpLink(data.help_url);
                    }
                    appendBubble(data.reply || 'No answer returned.', 'assistant');
                })
                .catch(function () {
                    if (thinking && thinking.parentNode) {
                        thinking.parentNode.removeChild(thinking);
                    }
                    appendBubble(
                        'Could not reach Ask Help. Check your connection, then try again or open Help from the menu.',
                        'error'
                    );
                })
                .finally(function () {
                    setBusy(false);
                    if (input) {
                        input.focus();
                    }
                });
        });
    }

    if (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (form) {
                    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            }
        });
    }

    appendBubble(
        'Hi! Ask a question about this screen. Answers come from the in-app help guide (not from your data records).',
        'assistant'
    );
})();
