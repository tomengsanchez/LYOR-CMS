/**
 * Render SES import UML diagrams (#ses-uml).
 * Requires: mermaid, window.PAPER_SES_UML_DIAGRAMS
 */
(function () {
    var diagrams = window.PAPER_SES_UML_DIAGRAMS;
    var tablist = document.getElementById("sesUmlTabs");
    var canvas = document.getElementById("sesUmlCanvas");
    var noteEl = document.getElementById("sesUmlNote");
    var statusEl = document.getElementById("sesUmlStatus");
    var activeId = "context";
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
        var buttons = tablist.querySelectorAll("[data-ses-uml-id]");
        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            var on = btn.getAttribute("data-ses-uml-id") === activeId;
            btn.setAttribute("aria-selected", on ? "true" : "false");
            btn.classList.toggle("is-active", on);
            btn.tabIndex = on ? 0 : -1;
        }
    }

    function configureMermaid() {
        if (typeof window.mermaid === "undefined") return false;
        window.mermaid.initialize({
            startOnLoad: false,
            securityLevel: "strict",
            theme: isLightTheme() ? "neutral" : "dark",
            flowchart: { useMaxWidth: true, htmlLabels: true, curve: "basis" },
            sequence: { useMaxWidth: true, mirrorActors: false }
        });
        return true;
    }

    function renderActive() {
        var diagram = findDiagram(activeId);
        var seq = ++renderSeq;
        syncTabs();
        if (noteEl) noteEl.textContent = diagram.note || "";

        if (!configureMermaid()) {
            canvas.innerHTML = "";
            setStatus(
                "Could not load Mermaid. See docs/UML_SES_IMPORT.md for sources.",
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
                        " See docs/UML_SES_IMPORT.md.",
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
            btn.setAttribute("data-ses-uml-id", d.id);
            btn.id = "ses-uml-tab-" + d.id;
            btn.setAttribute("aria-controls", "sesUmlCanvas");
            btn.textContent = d.label;
            btn.addEventListener("click", function (ev) {
                var id = ev.currentTarget.getAttribute("data-ses-uml-id");
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
