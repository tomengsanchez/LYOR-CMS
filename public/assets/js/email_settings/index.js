document.addEventListener("DOMContentLoaded", function () {
    var provider = document.getElementById("email_provider");
    var smtp = document.getElementById("smtp_settings_group");
    var mailersend = document.getElementById("mailersend_settings_group");

    var updateProviderView = function () {
        var value = provider ? provider.value : "smtp";
        if (smtp) smtp.classList.toggle("d-none", value !== "smtp");
        if (mailersend) mailersend.classList.toggle("d-none", value !== "mailersend");
    };

    if (provider) {
        provider.addEventListener("change", updateProviderView);
    }
    updateProviderView();
});
