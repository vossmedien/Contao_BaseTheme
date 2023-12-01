/* Add Floating Placeholders to Inputs */

function addPlaceholders() {
    $(".widget:not(.widget-upload):not(.widget-select):not(.widget-radio):not(.widget-checkbox):not(.widget-captcha)").each(function (index) {

        var placeholder = $(this).find("input,textarea").attr("placeholder");
        var label = $(this).find('label').text();

        var id = $(this).find("input,textarea").attr("id");

        label = label.replace('Pflichtfeld ', '');

        $(this).find("input:not(.form-control), textarea:not(.form-control)").addClass('form-control', '');

        if (placeholder) {

            $(this).find("label").remove();
            $(this)
                .find("input, textarea")
                .parent("div:not(.form-floating)")
                .addClass("form-floating");
            $(this)
                .find("input, textarea")
                .parent("div")
                .append("<label for='" + id + "'>" + placeholder + "</label>");


        } else if (label) {

            $(this).find("label").remove();

            $(this)
                .find("input, textarea")
                .parent("div:not(.form-floating)")
                .addClass("form-floating");

            $(this)
                .find("input, textarea")
                .parent("div")
                .append("<label for='" + id + "'>" + label + "</label>");

            $(this)
                .find("input, textarea").attr('placeholder', label);


        }
    });
}


$(".ce_form form").submit(function (e) {
    setTimeout(function () {
        addPlaceholders();
    }, 250);
});


$(function () {
    setTimeout(function () {
        addPlaceholders();
    }, 250, true);
});


/* END */