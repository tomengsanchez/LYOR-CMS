/**
 * Render Mermaid UML diagrams in the developer guide (#uml section).
 * Requires: mermaid (global), window.PAPER_UML_DIAGRAMS from uml-diagrams.js
 */
(function () {
    var diagrams = window.PAPER_UML_DIAGRAMS;
    var tablist = document.getElementById("umlTabs");
    var canvas = document.getElementById("umlCanvas");
    var noteEl = document.getElementById("umlNote");
    var statusEl = document.getElementById("umlStatus");
    var activeId = "components";
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
        var buttons = tablist.querySelectorAll("[data-uml-id]");
        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            var on = btn.getAttribute("data-uml-id") === activeId;
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
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true,
                curve: "basis"
            },
            sequence: {
                useMaxWidth: true,
                mirrorActors: false
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
                "Could not load Mermaid. Check network access to the CDN, then refresh. Full source remains in docs/UML.md.",
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
                        " See docs/UML.md for the source.",
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
            btn.setAttribute("data-uml-id", d.id);
            btn.id = "uml-tab-" + d.id;
            btn.setAttribute("aria-controls", "umlCanvas");
            btn.textContent = d.label;
            btn.addEventListener("click", function (ev) {
                var id = ev.currentTarget.getAttribute("data-uml-id");
                if (!id || id === activeId) return;
                activeId = id;
                renderActive();
            });
            tablist.appendChild(btn);
        }
    }

    buildTabs();
    activeId = diagrams[0].id;
    renderActive();

    window.addEventListener("dev-help-theme", function () {
        renderActive();
    });
})();
