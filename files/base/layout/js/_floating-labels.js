/* Add Floating Placeholders to Inputs */

function addPlaceholders() {
    $(".widget:not(.widget-upload):not(.widget-select):not(.widget-radio):not(.widget-checkbox)").each(function (index) {
        var placeholder = $(this).find("input, textarea").attr("placeholder");
        var label = $(this).find("input, textarea").prev('label').text();
        var id = $(this).find("input, textarea").attr("id");

        label = label.replace('Pflichtfeld ', '');

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

            //$(this)
                //.find("input, textarea").attr('placeholder', '');

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


$(function() {
    addPlaceholders();
});



/* END */