jQuery(document).ready(function() {
    $("#clerk_shop_select, #clerk_language_select").on("change", function () {
        $("#ignore_changes").val(1);
        $("#clerk_language_switch").prop('disabled', true);
        $(this).closest("form").submit();
    });
});