/**
 * Visual layout builder — compile design/settings to a live stylesheet.
 * Design ticks update #cmsBuilderLiveCss instead of rebuilding the canvas.
 */
(function () {
    window.CmsBuilder.bind(function (ctx) {
        with (ctx) {
            var LIVE_STYLE_FIELDS = {
                bg_color: 1,
                padding: 1,
                min_height: 1,
                margin: 1,
                text_align: 1,
                text_color: 1,
                font_size: 1,
                font_weight: 1,
                line_height: 1,
                border_width: 1,
                border_style: 1,
                border_color: 1,
                border_radius: 1,
                box_shadow: 1,
                width: 1,
                valign: 1,
                hide_mobile: 1,
                hide_desktop: 1,
                bg_media_id: 1,
                bg_image: 1,
                bg_overlay: 1,
                bg_overlay_opacity: 1
            };

            var BOX_SHADOWS = {
                sm: '0 1px 2px rgba(0,0,0,.08)',
                md: '0 4px 12px rgba(0,0,0,.12)',
                lg: '0 12px 28px rgba(0,0,0,.16)',
                none: 'none'
            };

            var COLOR_TOKENS = {
                accent: '--pub-accent',
                'accent-soft': '--pub-accent-soft',
                text: '--pub-text',
                muted: '--pub-muted',
                surface: '--pub-surface',
                bg: '--pub-bg',
                border: '--pub-border'
            };

            function isLiveStyleField(name) {
                return !!LIVE_STYLE_FIELDS[String(name || '')];
            }

            function elementCssClass(id) {
                var s = String(id || '');
                if (!/^[a-zA-Z0-9_-]{4,40}$/.test(s)) {
                    return '';
                }
                return 'cms-el-' + s;
            }

            function ensureNodeId(node) {
                if (!node || typeof node !== 'object') {
                    return '';
                }
                if (!elementCssClass(node.id)) {
                    node.id = uid();
                }
                return String(node.id);
            }

            function cssSafeColor(v) {
                v = String(v || '').trim();
                if (/^#[0-9a-fA-F]{3,8}$/.test(v)) {
                    return v;
                }
                var key = v.toLowerCase();
                if (COLOR_TOKENS[key]) {
                    return 'var(' + COLOR_TOKENS[key] + ')';
                }
                var m = v.match(/^var\(\s*(--pub-[a-z-]+)\s*\)$/i);
                if (m) {
                    var cssVar = m[1].toLowerCase();
                    for (var t in COLOR_TOKENS) {
                        if (COLOR_TOKENS[t] === cssVar) {
                            return 'var(' + cssVar + ')';
                        }
                    }
                }
                return '';
            }

            function cssSafeUrl(u) {
                u = String(u || '').trim();
                if (u === '' || u === '#' || u.indexOf('..') !== -1 || /[\\'"()<>\s]/.test(u)) {
                    return '';
                }
                if (!/^(https?:\/\/|\/)/i.test(u)) {
                    return '';
                }
                return u.slice(0, 500);
            }

            function hexToRgba(hex, alpha) {
                hex = String(hex || '').replace('#', '');
                if (hex.length === 3) {
                    hex = hex.charAt(0) + hex.charAt(0) + hex.charAt(1) + hex.charAt(1) + hex.charAt(2) + hex.charAt(2);
                }
                if (!/^[0-9a-fA-F]{6}/.test(hex)) {
                    return '';
                }
                var r = parseInt(hex.slice(0, 2), 16);
                var g = parseInt(hex.slice(2, 4), 16);
                var b = parseInt(hex.slice(4, 6), 16);
                var a = Math.max(0, Math.min(1, alpha));
                return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
            }

            function overlayCss(stored, opacity) {
                var color = cssSafeColor(stored);
                if (!color) {
                    return '';
                }
                var op = parseInt(opacity, 10);
                if (isNaN(op)) {
                    op = 40;
                }
                if (op < 1) {
                    return '';
                }
                if (op > 80) {
                    op = 80;
                }
                if (color.charAt(0) === '#') {
                    var rgba = hexToRgba(color, op / 100);
                    return rgba ? 'linear-gradient(' + rgba + ',' + rgba + ')' : '';
                }
                var mix = 'color-mix(in srgb,' + color + ' ' + op + '%,transparent)';
                return 'linear-gradient(' + mix + ',' + mix + ')';
            }

            function resolveBgUrl(settings) {
                settings = settings || {};
                var id = Number(settings.bg_media_id) || 0;
                if (id && typeof mediaById === 'function') {
                    var m = mediaById(id);
                    if (m) {
                        var fromMedia = cssSafeUrl(m.share_url || m.url || m.preview || '');
                        if (fromMedia) {
                            return fromMedia;
                        }
                    }
                }
                return cssSafeUrl(settings.bg_image);
            }

            function cssSafeSpacing(v) {
                v = String(v || '').trim();
                if (!/^(\d+(\.\d+)?(px|rem|em|%)\s*){1,4}$/.test(v)) {
                    return '';
                }
                return v.slice(0, 40);
            }

            function cssSafeHeight(v) {
                v = String(v || '').trim();
                if (!/^\d+(\.\d+)?(px|rem|em|%|vh|vw)$/.test(v)) {
                    return '';
                }
                return v.slice(0, 20);
            }

            function cssSafeFontSize(v) {
                v = String(v || '').trim();
                return /^\d+(\.\d+)?(px|rem|em)$/.test(v) ? v : '';
            }

            function cssSafeRadius(v) {
                v = String(v || '').trim();
                if (!/^(\d+(\.\d+)?(px|rem|em|%)\s*){1,4}$/.test(v)) {
                    return '';
                }
                return v.slice(0, 40);
            }

            function cssSafeBorderStyle(v) {
                v = String(v || '').trim().toLowerCase();
                return v === 'solid' || v === 'dashed' || v === 'dotted' || v === 'double' || v === 'none' ? v : '';
            }

            function cssSafeShadowKey(v) {
                v = String(v || '').trim().toLowerCase();
                return BOX_SHADOWS[v] ? v : '';
            }

            function cssSafeFontWeight(v) {
                v = String(v || '').trim();
                return v === '400' || v === '500' || v === '600' || v === '700' ? v : '';
            }

            function cssSafeLineHeight(v) {
                v = String(v || '').trim();
                if (!/^\d(\.\d{1,2})?$/.test(v)) {
                    return '';
                }
                var n = Number(v);
                return n >= 1 && n <= 2.5 ? v : '';
            }

            function appendChromeDecls(parts, bag, override) {
                bag = bag || {};
                var width = cssSafeSpacing(bag.border_width);
                var style = cssSafeBorderStyle(bag.border_style);
                var color = cssSafeColor(bag.border_color);
                var touches = Object.prototype.hasOwnProperty.call(bag, 'border_width')
                    || Object.prototype.hasOwnProperty.call(bag, 'border_style')
                    || Object.prototype.hasOwnProperty.call(bag, 'border_color');
                if (width || style || color) {
                    parts.push('border-width:' + (width || '1px'));
                    parts.push('border-style:' + (style || 'solid'));
                    if (color) {
                        parts.push('border-color:' + color);
                    }
                } else if (override && touches) {
                    parts.push('border-width:unset', 'border-style:unset', 'border-color:unset');
                }
                pushDecl(parts, 'border-radius', cssSafeRadius(bag.border_radius), override, Object.prototype.hasOwnProperty.call(bag, 'border_radius'));
                var shadowKey = cssSafeShadowKey(bag.box_shadow);
                pushDecl(parts, 'box-shadow', shadowKey ? BOX_SHADOWS[shadowKey] : '', override, Object.prototype.hasOwnProperty.call(bag, 'box_shadow'));
            }

            function cssSafeAlign(v) {
                v = String(v || '').trim();
                return v === 'left' || v === 'center' || v === 'right' || v === 'justify' ? v : '';
            }

            function declsFromSettings(settings, override, base) {
                settings = settings || {};
                base = base || {};
                var parts = [];
                pushDecl(parts, 'background-color', cssSafeColor(settings.bg_color), override, Object.prototype.hasOwnProperty.call(settings, 'bg_color'));
                pushDecl(parts, 'padding', cssSafeSpacing(settings.padding), override, Object.prototype.hasOwnProperty.call(settings, 'padding'));
                pushDecl(parts, 'min-height', cssSafeHeight(settings.min_height), override, Object.prototype.hasOwnProperty.call(settings, 'min_height'));
                if (Object.prototype.hasOwnProperty.call(settings, 'valign') || !override) {
                    var valign = String(settings.valign || '');
                    if (valign === 'center') {
                        parts.push('display:flex', 'flex-direction:column', 'justify-content:center');
                    } else if (valign === 'bottom') {
                        parts.push('display:flex', 'flex-direction:column', 'justify-content:flex-end');
                    } else if (override && (valign === '' || valign === 'top')) {
                        parts.push('justify-content:flex-start');
                    }
                }
                appendBackgroundDecls(parts, settings, override, base);
                appendChromeDecls(parts, settings, override);
                return parts.join(';');
            }

            function declsFromDesign(design, override) {
                design = design || {};
                var parts = [];
                var align = cssSafeAlign(design.text_align);
                if (align) {
                    parts.push('text-align:' + align);
                } else if (override && Object.prototype.hasOwnProperty.call(design, 'text_align')) {
                    parts.push('text-align:unset');
                }
                pushDecl(parts, 'color', cssSafeColor(design.text_color), override, Object.prototype.hasOwnProperty.call(design, 'text_color'));
                pushDecl(parts, 'background-color', cssSafeColor(design.bg_color), override, Object.prototype.hasOwnProperty.call(design, 'bg_color'));
                pushDecl(parts, 'padding', cssSafeSpacing(design.padding), override, Object.prototype.hasOwnProperty.call(design, 'padding'));
                pushDecl(parts, 'margin', cssSafeSpacing(design.margin), override, Object.prototype.hasOwnProperty.call(design, 'margin'));
                pushDecl(parts, 'font-size', cssSafeFontSize(design.font_size), override, Object.prototype.hasOwnProperty.call(design, 'font_size'));
                pushDecl(parts, 'font-weight', cssSafeFontWeight(design.font_weight), override, Object.prototype.hasOwnProperty.call(design, 'font_weight'));
                pushDecl(parts, 'line-height', cssSafeLineHeight(design.line_height), override, Object.prototype.hasOwnProperty.call(design, 'line_height'));
                appendChromeDecls(parts, design, override);
                return parts.join(';');
            }

            function pushDecl(parts, prop, value, override, present) {
                if (value) {
                    parts.push(prop + ':' + value);
                    return;
                }
                if (override && present) {
                    parts.push(prop + ':unset');
                }
            }

            function cssRule(id, decls) {
                var cls = elementCssClass(id);
                if (!cls || !decls) {
                    return '';
                }
                return '.' + cls + '{' + decls + '}';
            }

            function prefixDeviceRules(rules, devices) {
                if (!rules.length) {
                    return '';
                }
                return rules.map(function (rule) {
                    var i = rule.indexOf('{');
                    if (i < 0) {
                        return '';
                    }
                    var sel = rule.slice(0, i).trim();
                    var body = rule.slice(i);
                    return devices.map(function (d) {
                        return '.cms-builder-canvas-wrap[data-device="' + d + '"] ' + sel;
                    }).join(',') + body;
                }).join('');
            }

            function appendBackgroundDecls(parts, settings, override, base) {
                var keys = ['bg_media_id', 'bg_image', 'bg_overlay', 'bg_overlay_opacity'];
                var touches = keys.some(function (k) {
                    return Object.prototype.hasOwnProperty.call(settings, k);
                });
                if (override && !touches) {
                    return;
                }
                var imgSrc = (override && base && Object.keys(base).length) ? base : settings;
                var url = resolveBgUrl(imgSrc);
                var overlayColor = Object.prototype.hasOwnProperty.call(settings, 'bg_overlay') || !override
                    ? (settings.bg_overlay || '')
                    : (base.bg_overlay || '');
                var overlayOp = Object.prototype.hasOwnProperty.call(settings, 'bg_overlay_opacity') || !override
                    ? settings.bg_overlay_opacity
                    : (base.bg_overlay_opacity || '');
                if (override && overlayColor === '' && Object.prototype.hasOwnProperty.call(settings, 'bg_overlay')) {
                    if (url) {
                        parts.push('background-image:url("' + url + '")', 'background-size:cover', 'background-position:center', 'background-repeat:no-repeat');
                    } else {
                        parts.push('background-image:unset', 'background-size:unset');
                    }
                    return;
                }
                var overlay = overlayCss(overlayColor, overlayOp);
                if (!url && !overlay) {
                    if (override && touches) {
                        parts.push('background-image:unset');
                    }
                    return;
                }
                var image = url ? 'url("' + url + '")' : '';
                if (overlay && image) {
                    parts.push('background-image:' + overlay + ',' + image);
                } else if (image) {
                    parts.push('background-image:' + image);
                } else if (overlay) {
                    parts.push('background-image:' + overlay);
                }
                if (image || overlay) {
                    parts.push('background-size:cover', 'background-position:center', 'background-repeat:no-repeat');
                }
            }

            function addNodeCss(base, tablet, mobile, id, node, kind) {
                var bag = node && node[kind] ? node[kind] : {};
                var decls = kind === 'design' ? declsFromDesign(bag, false) : declsFromSettings(bag, false, {});
                var rule = cssRule(id, decls);
                if (rule) {
                    base.push(rule);
                }
                var t = node && node[kind + '_tablet'] ? node[kind + '_tablet'] : {};
                decls = kind === 'design' ? declsFromDesign(t, true) : declsFromSettings(t, true, bag);
                rule = cssRule(id, decls);
                if (rule) {
                    tablet.push(rule);
                }
                var m = node && node[kind + '_mobile'] ? node[kind + '_mobile'] : {};
                decls = kind === 'design' ? declsFromDesign(m, true) : declsFromSettings(m, true, bag);
                rule = cssRule(id, decls);
                if (rule) {
                    mobile.push(rule);
                }
                if (kind !== 'design') {
                    return;
                }
                var hover = node && node.design_hover ? node.design_hover : {};
                var hDecls = declsFromDesign(hover, false);
                var hoverBg = cssSafeColor(hover.bg_color);
                if (hoverBg) {
                    hDecls = hDecls ? hDecls + ';border-color:' + hoverBg : 'border-color:' + hoverBg;
                }
                rule = cssHoverRule(id, hDecls);
                if (rule) {
                    base.push(rule);
                }
            }

            function cssHoverRule(id, decls) {
                var cls = elementCssClass(id);
                if (!cls || !decls) {
                    return '';
                }
                var sel = '.' + cls + ':hover,.' + cls + ':hover .btn,.' + cls + ':hover a,.public-site .' + cls + ':hover .btn';
                return '.' + cls + '{transition:color .15s ease,background-color .15s ease,border-color .15s ease,box-shadow .15s ease}'
                    + sel + '{' + decls + '}';
            }

            function compileLayoutCss(tree) {
                var base = [];
                var tablet = [];
                var mobile = [];
                var sections = tree && Array.isArray(tree.sections) ? tree.sections : [];
                sections.forEach(function (section) {
                    if (!section) {
                        return;
                    }
                    addNodeCss(base, tablet, mobile, ensureNodeId(section), section, 'settings');
                    (section.rows || []).forEach(function (row) {
                        if (!row) {
                            return;
                        }
                        addNodeCss(base, tablet, mobile, ensureNodeId(row), row, 'settings');
                        (row.columns || []).forEach(function (col) {
                            if (!col) {
                                return;
                            }
                            addNodeCss(base, tablet, mobile, ensureNodeId(col), col, 'settings');
                            (col.modules || []).forEach(function (mod) {
                                if (!mod) {
                                    return;
                                }
                                addNodeCss(base, tablet, mobile, ensureNodeId(mod), mod, 'design');
                            });
                        });
                    });
                });
                return base.join('')
                    + prefixDeviceRules(tablet, ['tablet', 'mobile'])
                    + prefixDeviceRules(mobile, ['mobile']);
            }

            function deviceStyleName() {
                return currentDevice === 'tablet' || currentDevice === 'mobile' ? currentDevice : '';
            }

            function styleFieldValue(node, kind, name) {
                if (kind === 'design' && designState === 'hover') {
                    var hoverBag = node && node.design_hover;
                    if (hoverBag && Object.prototype.hasOwnProperty.call(hoverBag, name)) {
                        return hoverBag[name];
                    }
                    return node && node.design && node.design[name] != null ? node.design[name] : '';
                }
                var bp = deviceStyleName();
                var over = bp && node ? node[kind + '_' + bp] : null;
                if (over && Object.prototype.hasOwnProperty.call(over, name)) {
                    return over[name];
                }
                return node && node[kind] && node[kind][name] != null ? node[kind][name] : '';
            }

            function isStyleOverridden(node, kind, name) {
                if (kind === 'design' && designState === 'hover') {
                    return !!(node && node.design_hover && Object.prototype.hasOwnProperty.call(node.design_hover, name));
                }
                var bp = deviceStyleName();
                var over = bp && node ? node[kind + '_' + bp] : null;
                return !!(over && Object.prototype.hasOwnProperty.call(over, name));
            }

            function writeStyleValue(node, kind, name, val) {
                if (!node) {
                    return;
                }
                if (kind === 'design' && designState === 'hover') {
                    var hoverBase = node.design && node.design[name] != null ? node.design[name] : '';
                    if (String(val) === '' || String(val) === String(hoverBase)) {
                        if (node.design_hover) {
                            delete node.design_hover[name];
                            if (Object.keys(node.design_hover).length === 0) {
                                delete node.design_hover;
                            }
                        }
                        return;
                    }
                    node.design_hover = node.design_hover || {};
                    node.design_hover[name] = val;
                    return;
                }
                var bp = deviceStyleName();
                if (!bp) {
                    node[kind] = node[kind] || {};
                    node[kind][name] = val;
                    return;
                }
                var bagKey = kind + '_' + bp;
                var base = node[kind] && node[kind][name] != null ? node[kind][name] : '';
                if (String(val) === String(base)) {
                    if (node[bagKey]) {
                        delete node[bagKey][name];
                        if (Object.keys(node[bagKey]).length === 0) {
                            delete node[bagKey];
                        }
                    }
                    return;
                }
                node[bagKey] = node[bagKey] || {};
                node[bagKey][name] = val;
            }

            function clearDeviceStyle(node) {
                var bp = deviceStyleName();
                if (!node || !bp) {
                    return;
                }
                delete node['design_' + bp];
                delete node['settings_' + bp];
            }

            function hasDeviceStyle(node) {
                var bp = deviceStyleName();
                if (!node || !bp) {
                    return false;
                }
                var d = node['design_' + bp];
                var s = node['settings_' + bp];
                return (d && Object.keys(d).length > 0) || (s && Object.keys(s).length > 0);
            }

            function clearHoverStyle(node) {
                if (!node) {
                    return;
                }
                delete node.design_hover;
            }

            function hasHoverStyle(node) {
                var h = node && node.design_hover;
                return !!(h && Object.keys(h).length > 0);
            }

            function compileOneNodeCss(id, node, kind) {
                var base = [];
                var tablet = [];
                var mobile = [];
                addNodeCss(base, tablet, mobile, id, node, kind);
                return base.join('')
                    + prefixDeviceRules(tablet, ['tablet', 'mobile'])
                    + prefixDeviceRules(mobile, ['mobile']);
            }

            function cssRuleTouchesClass(rule, cls) {
                var i = rule.indexOf('{');
                var sel = i < 0 ? rule : rule.slice(0, i);
                var re = new RegExp('\\.' + cls.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(?![A-Za-z0-9_-])');
                return re.test(sel);
            }

            function stripClassRules(css, cls) {
                var out = [];
                var i = 0;
                while (i < css.length) {
                    var brace = css.indexOf('{', i);
                    if (brace < 0) {
                        out.push(css.slice(i));
                        break;
                    }
                    var end = css.indexOf('}', brace);
                    if (end < 0) {
                        out.push(css.slice(i));
                        break;
                    }
                    var rule = css.slice(i, end + 1);
                    if (!cssRuleTouchesClass(rule, cls)) {
                        out.push(rule);
                    }
                    i = end + 1;
                }
                return out.join('');
            }

            function patchLiveCssForNode(node, kind) {
                var el = liveCssEl || document.getElementById('cmsBuilderLiveCss');
                if (!el || !node) {
                    refreshLiveCss();
                    return;
                }
                var id = ensureNodeId(node);
                var cls = elementCssClass(id);
                if (!cls) {
                    refreshLiveCss();
                    return;
                }
                el.textContent = stripClassRules(el.textContent || '', cls) + compileOneNodeCss(id, node, kind);
            }

            function refreshLiveCss() {
                var el = liveCssEl || document.getElementById('cmsBuilderLiveCss');
                if (!el) {
                    return;
                }
                el.textContent = compileLayoutCss(layout);
            }

            function selectedChromeEl() {
                if (!canvas || !selection.kind) {
                    return null;
                }
                if (selection.kind === 'section') {
                    return canvas.querySelector('.cms-lb-section[data-si="' + selection.sectionIdx + '"]');
                }
                if (selection.kind === 'row') {
                    return canvas.querySelector('.cms-lb-row[data-si="' + selection.sectionIdx + '"][data-ri="' + selection.rowIdx + '"]');
                }
                if (selection.kind === 'column') {
                    return canvas.querySelector('.cms-lb-col[data-si="' + selection.sectionIdx + '"][data-ri="' + selection.rowIdx + '"][data-ci="' + selection.colIdx + '"]');
                }
                if (selection.kind === 'module') {
                    return canvas.querySelector('.cms-lb-mod[data-si="' + selection.sectionIdx + '"][data-ri="' + selection.rowIdx + '"][data-ci="' + selection.colIdx + '"][data-mi="' + selection.modIdx + '"]');
                }
                return null;
            }

            function patchLiveChrome(name, val) {
                var el = selectedChromeEl();
                if (!el) {
                    return;
                }
                if (name === 'width' && selection.kind === 'column') {
                    var w = Number(val) || 12;
                    if (w < 1) {
                        w = 1;
                    }
                    if (w > 12) {
                        w = 12;
                    }
                    el.className = el.className.replace(/\bcol-md-\d+\b/g, 'col-md-' + w);
                    var lab = el.querySelector('.cms-lb-chrome-label > span');
                    if (lab) {
                        lab.textContent = 'Col ' + w + '/12';
                    }
                    return;
                }
                if (name === 'valign' && selection.kind === 'column' && currentDevice === 'desktop') {
                    el.classList.remove('cms-layout-column--valign-center', 'cms-layout-column--valign-bottom');
                    if (val === 'center' || val === 'bottom') {
                        el.classList.add('cms-layout-column--valign-' + val);
                    }
                    return;
                }
                if ((name === 'hide_mobile' || name === 'hide_desktop') && selection.kind === 'module') {
                    var inner = el.querySelector('.cms-layout-module');
                    if (!inner) {
                        return;
                    }
                    if (name === 'hide_mobile') {
                        inner.classList.toggle('cms-lb-hide-mobile', !!val);
                    } else {
                        inner.classList.toggle('cms-lb-hide-desktop', !!val);
                    }
                }
            }

            ctx.isLiveStyleField = isLiveStyleField;
            ctx.elementCssClass = elementCssClass;
            ctx.ensureNodeId = ensureNodeId;
            ctx.compileLayoutCss = compileLayoutCss;
            ctx.refreshLiveCss = refreshLiveCss;
            ctx.patchLiveCssForNode = patchLiveCssForNode;
            ctx.patchLiveChrome = patchLiveChrome;
            ctx.selectedChromeEl = selectedChromeEl;
            ctx.deviceStyleName = deviceStyleName;
            ctx.styleFieldValue = styleFieldValue;
            ctx.isStyleOverridden = isStyleOverridden;
            ctx.writeStyleValue = writeStyleValue;
            ctx.clearDeviceStyle = clearDeviceStyle;
            ctx.hasDeviceStyle = hasDeviceStyle;
            ctx.clearHoverStyle = clearHoverStyle;
            ctx.hasHoverStyle = hasHoverStyle;
        }
    });
})();
