/**
 * Visual layout builder — small rich-text toolbar (bold / italic / lists / link).
 * External file only. Public save re-allowlists tags in PHP.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            var RICH_TAGS = { P: 1, BR: 1, STRONG: 1, B: 1, EM: 1, I: 1, U: 1, UL: 1, OL: 1, LI: 1, A: 1 };
            var DROP_TAGS = { SCRIPT: 1, IFRAME: 1, OBJECT: 1, EMBED: 1, FORM: 1, STYLE: 1, NOSCRIPT: 1, LINK: 1, META: 1, TEXTAREA: 1, TEMPLATE: 1, SVG: 1 };

            function sanitizeRichClient(html) {
                var box = document.createElement('div');
                box.innerHTML = String(html || '');
                function walk(node) {
                    var kids = Array.prototype.slice.call(node.childNodes);
                    kids.forEach(function (child) {
                        if (child.nodeType === 8) {
                            node.removeChild(child);
                            return;
                        }
                        if (child.nodeType !== 1) {
                            return;
                        }
                        var tag = child.tagName;
                        if (DROP_TAGS[tag]) {
                            node.removeChild(child);
                            return;
                        }
                        if (tag === 'B') {
                            tag = 'STRONG';
                        } else if (tag === 'I') {
                            tag = 'EM';
                        }
                        walk(child);
                        if (!RICH_TAGS[child.tagName] && tag !== 'STRONG' && tag !== 'EM') {
                            while (child.firstChild) {
                                node.insertBefore(child.firstChild, child);
                            }
                            node.removeChild(child);
                            return;
                        }
                        if (child.tagName === 'A') {
                            var href = '';
                            try {
                                href = String(child.getAttribute('href') || '').trim();
                            } catch (e) {
                                href = '';
                            }
                            var attrs = Array.prototype.slice.call(child.attributes || []);
                            attrs.forEach(function (a) {
                                child.removeAttribute(a.name);
                            });
                            if (href && /^(https?:\/\/|\/|#)/i.test(href) && !/^\s*javascript:/i.test(href)) {
                                child.setAttribute('href', href.slice(0, 500));
                            } else {
                                while (child.firstChild) {
                                    node.insertBefore(child.firstChild, child);
                                }
                                node.removeChild(child);
                            }
                            return;
                        }
                        var leftover = Array.prototype.slice.call(child.attributes || []);
                        leftover.forEach(function (a) {
                            child.removeAttribute(a.name);
                        });
                    });
                }
                walk(box);
                return box.innerHTML;
            }

            function formatRichPreview(raw) {
                var s = String(raw || '');
                if (!s) {
                    return '';
                }
                if (/<[a-z]/i.test(s)) {
                    return sanitizeRichClient(s);
                }
                return esc(s).replace(/\n/g, '<br>');
            }

            function richEditorHtml(name, value, attrs) {
                attrs = attrs || '';
                return '<div class="cms-lb-rich">'
                    + '<div class="cms-lb-rich-bar" role="toolbar" aria-label="Text formatting">'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-rich-cmd="bold" title="Bold"><strong>B</strong></button>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-rich-cmd="italic" title="Italic"><em>I</em></button>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-rich-cmd="insertUnorderedList" title="Bulleted list">• List</button>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-rich-cmd="insertOrderedList" title="Numbered list">1. List</button>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm" data-rich-cmd="createLink" title="Link">Link</button>'
                    + '</div>'
                    + '<div class="cms-lb-rich-ed form-control form-control-sm" contenteditable="true" spellcheck="true" data-rich-for="'
                    + esc(name) + '" ' + attrs + '>' + formatRichPreview(value) + '</div>'
                    + '</div>';
            }

            function bindRichEditors(root, onChange) {
                if (!root || typeof onChange !== 'function') {
                    return;
                }
                root.querySelectorAll('.cms-lb-rich').forEach(function (wrap) {
                    var ed = wrap.querySelector('.cms-lb-rich-ed');
                    if (!ed) {
                        return;
                    }
                    wrap.querySelectorAll('[data-rich-cmd]').forEach(function (btn) {
                        btn.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                        });
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            ed.focus();
                            var cmd = btn.getAttribute('data-rich-cmd');
                            if (cmd === 'createLink') {
                                var href = window.prompt('Link URL', 'https://');
                                if (!href) {
                                    return;
                                }
                                href = String(href).trim();
                                if (!/^(https?:\/\/|\/|#)/i.test(href) || /^\s*javascript:/i.test(href)) {
                                    setStatus('Use https://, a path starting with /, or #', true);
                                    return;
                                }
                                document.execCommand('createLink', false, href);
                            } else {
                                document.execCommand(cmd, false, null);
                            }
                            onChange(ed, sanitizeRichClient(ed.innerHTML));
                        });
                    });
                    ed.addEventListener('input', function () {
                        onChange(ed, sanitizeRichClient(ed.innerHTML));
                    });
                });
            }

            ctx.sanitizeRichClient = sanitizeRichClient;
            ctx.formatRichPreview = formatRichPreview;
            ctx.richEditorHtml = richEditorHtml;
            ctx.bindRichEditors = bindRichEditors;
        }
    });
})();
