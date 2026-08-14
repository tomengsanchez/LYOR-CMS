/**
 * Repeatable contact person + number rows (profile and user forms).
 */
$(function () {
    var contactRowTemplate = '<div class="row g-2 align-items-end contact-number-row">'
        + '<div class="col-md-5"><label class="form-label small text-muted mb-0">Person</label>'
        + '<input type="text" name="contacts_person[]" class="form-control" value="" placeholder="e.g. Self, Spouse" autocomplete="name"></div>'
        + '<div class="col-md-6"><label class="form-label small text-muted mb-0">Number</label>'
        + '<input type="text" name="contacts_number[]" class="form-control" value="" placeholder="Phone" inputmode="tel" autocomplete="tel"></div>'
        + '<div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm w-100 contact-row-remove" title="Remove">&times;</button></div>'
        + '</div>';

    $("#contactRowAdd").on("click", function () {
        $("#contactNumberRows").append(contactRowTemplate);
    });

    $(document).on("click", ".contact-row-remove", function () {
        var $rows = $("#contactNumberRows .contact-number-row");
        if ($rows.length <= 1) {
            $(this).closest(".contact-number-row").find("input").val("");
            return;
        }
        $(this).closest(".contact-number-row").remove();
    });
});
