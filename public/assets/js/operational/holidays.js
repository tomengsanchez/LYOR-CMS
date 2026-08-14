document.addEventListener("DOMContentLoaded", function () {
    var modalEl = document.getElementById("holidayModal");
    var formEl = document.getElementById("holidayForm");
    if (!modalEl || !formEl) {
        return;
    }

    var modalTitle = document.getElementById("holidayModalLabel");
    var nameInput = document.getElementById("holiday_name");
    var dateInput = document.getElementById("holiday_date");
    var descriptionInput = document.getElementById("holiday_description");
    var addBtn = document.getElementById("holidayAddBtn");
    var storeAction = "/system/operational/holidays/store";

    function resetForm() {
        formEl.action = storeAction;
        if (modalTitle) {
            modalTitle.textContent = "Add Holiday";
        }
        if (nameInput) {
            nameInput.value = "";
        }
        if (dateInput) {
            dateInput.value = "";
        }
        if (descriptionInput) {
            descriptionInput.value = "";
        }
    }

    if (addBtn) {
        addBtn.addEventListener("click", resetForm);
    }

    document.querySelectorAll(".holiday-edit-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var id = btn.getAttribute("data-id") || "";
            formEl.action = "/system/operational/holidays/update/" + id;
            if (modalTitle) {
                modalTitle.textContent = "Edit Holiday";
            }
            if (nameInput) {
                nameInput.value = btn.getAttribute("data-name") || "";
            }
            if (dateInput) {
                dateInput.value = btn.getAttribute("data-date") || "";
            }
            if (descriptionInput) {
                var rawDescription = btn.getAttribute("data-description") || "\"\"";
                try {
                    descriptionInput.value = JSON.parse(rawDescription);
                } catch (err) {
                    descriptionInput.value = "";
                }
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    modalEl.addEventListener("hidden.bs.modal", resetForm);
});
