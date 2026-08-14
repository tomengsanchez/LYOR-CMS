(function () {
    var btn = document.getElementById("themeToggle");
    if (!btn) return;

    var key = "dev-help-theme";
    function apply(t) {
        if (t === "light") {
            document.body.setAttribute("data-theme", "light");
        } else {
            document.body.removeAttribute("data-theme");
        }
        btn.setAttribute("aria-pressed", t === "light" ? "true" : "false");
        btn.textContent = t === "light" ? "Dark" : "Light";
        try {
            localStorage.setItem(key, t);
        } catch (e) {
            /* ignore */
        }
    }

    var saved = null;
    try {
        saved = localStorage.getItem(key);
    } catch (e2) {
        /* ignore */
    }
    if (saved === "light" || saved === "dark") {
        apply(saved);
    }

    btn.addEventListener("click", function () {
        var next = document.body.getAttribute("data-theme") === "light" ? "dark" : "light";
        apply(next);
        try {
            window.dispatchEvent(new CustomEvent("dev-help-theme", { detail: { theme: next } }));
        } catch (e3) {
            /* ignore */
        }
    });
})();
