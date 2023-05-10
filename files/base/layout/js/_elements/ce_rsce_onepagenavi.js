document.addEventListener("DOMContentLoaded", function (event) {
if ($(".ce_rsce_onepagenavi.with-smaller-containers").length) {
    $("#main").addClass("with-onepage-nav");
}
if ($(".ce_rsce_onepagenavi").length) {
    $(".onepage-nav--mobile-toggle").click(function () {
        $(this).parent("div").toggleClass("visible");
    });

    $(window).scroll(function () {
        var scroll = $(window).scrollTop();
        if (scroll >= $(".ce--onepagenavi").data("offset")) {
            $(".ce--onepagenavi").removeClass("d-none");
        } else {
            $(".ce--onepagenavi").addClass("d-none");
        }
    });
}
}, {passive: true});