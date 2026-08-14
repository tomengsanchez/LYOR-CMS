/**
 * Render Mermaid ER diagrams in the developer guide (#erd section).
 * Requires: mermaid (global), window.PAPER_ERD_DIAGRAMS from erd-diagrams.js
 */
(function () {
    var diagrams = window.PAPER_ERD_DIAGRAMS;
    var tablist = document.getElementById("erdTabs");
    var canvas = document.getElementById("erdCanvas");
    var noteEl = document.getElementById("erdNote");
    var statusEl = document.getElementById("erdStatus");
    var activeId = "overview";
    var renderSeq = 0;

    if (!tablist || !canvas || !diagrams || !diagrams.length) {
        return;
    }

    function isLightTheme() {
        return document.body.getAttribute("data-theme") === "light";
    }

    function setStatus(msg, isError) {
        if (!statusEl) return;
        statusEl.textContent = msg || "";
        statusEl.hidden = !msg;
        statusEl.classList.toggle("erd-status--error", !!isError);
    }

    function findDiagram(id) {
        for (var i = 0; i < diagrams.length; i++) {
            if (diagrams[i].id === id) return diagrams[i];
        }
        return diagrams[0];
    }

    function syncTabs() {
        var buttons = tablist.querySelectorAll("[data-erd-id]");
        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            var on = btn.getAttribute("data-erd-id") === activeId;
            btn.setAttribute("aria-selected", on ? "true" : "false");
            btn.classList.toggle("is-active", on);
            btn.tabIndex = on ? 0 : -1;
        }
    }

    function ensureMermaid() {
        return typeof window.mermaid !== "undefined" && window.mermaid;
    }

    function configureMermaid() {
        var m = ensureMermaid();
        if (!m) return false;
        m.initialize({
            startOnLoad: false,
            securityLevel: "strict",
            theme: isLightTheme() ? "neutral" : "dark",
            er: {
                diagramPadding: 16,
                layoutDirection: "TB",
                minEntityWidth: 100,
                minEntityHeight: 40,
                entityPadding: 12,
                useMaxWidth: true
            }
        });
        return true;
    }

    function renderActive() {
        var diagram = findDiagram(activeId);
        var seq = ++renderSeq;

        syncTabs();
        if (noteEl) {
            noteEl.textContent = diagram.note || "";
        }

        if (!configureMermaid()) {
            canvas.innerHTML = "";
            setStatus(
                "Could not load Mermaid. Check network access to the CDN, then refresh. Full source remains in docs/ERD.md.",
                true
            );
            return;
        }

        setStatus("Rendering diagram…", false);
        canvas.innerHTML = "";
        var host = document.createElement("div");
        host.className = "mermaid erd-mermaid";
        host.textContent = diagram.source;
        canvas.appendChild(host);

        window.mermaid
            .run({ nodes: [host] })
            .then(function () {
                if (seq !== renderSeq) return;
                setStatus("", false);
            })
            .catch(function (err) {
                if (seq !== renderSeq) return;
                canvas.innerHTML = "";
                setStatus(
                    "Diagram failed to render" +
                        (err && err.message ? ": " + err.message : ".") +
                        " See docs/ERD.md for the source.",
                    true
                );
            });
    }

    function buildTabs() {
        tablist.innerHTML = "";
        for (var i = 0; i < diagrams.length; i++) {
            var d = diagrams[i];
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "erd-tab";
            btn.setAttribute("role", "tab");
            btn.setAttribute("data-erd-id", d.id);
            btn.id = "erd-tab-" + d.id;
            btn.setAttribute("aria-controls", "erdCanvas");
            btn.textContent = d.label;
            btn.addEventListener("click", function (ev) {
                var id = ev.currentTarget.getAttribute("data-erd-id");
                if (!id || id === activeId) return;
                activeId = id;
                renderActive();
            });
            tablist.appendChild(btn);
        }
    }

    function onThemeChange() {
        renderActive();
    }

    buildTabs();
    activeId = diagrams[0].id;
    renderActive();

    window.addEventListener("dev-help-theme", onThemeChange);
})();
