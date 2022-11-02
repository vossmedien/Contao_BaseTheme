function changeAnchorLinks() {
    var scrollPos = $(document).scrollTop();

    if ($('#mainNav a[href*="#"]').length) {
        $('#mainNav a[href*="#"]:not(.invisible)').each(function () {
            var currElement = $(this);
            var currLink = $(this).attr("href");
            var refElement = currLink.substring(currLink.indexOf("#"));

            if ($(refElement).position()) {
                if (($(refElement).position().top - 500) <= scrollPos && ($(refElement).position().top - 500) + $(refElement).height() > scrollPos) {
                    $("#mainNav .active").removeClass("active");
                    currElement.addClass("active");
                } else {
                    //currElement.removeClass("active");
                }
            }
        });
    }

    if ($(".onepagenavi--wrapper a").length) {
        $(".onepagenavi--wrapper a").each(function () {
            var currElement = $(this);
            var currLink = $(this).attr("href");
            var refElement = currLink.substring(currLink.indexOf("#"));

            if ($(refElement).position()) {
                if (($(refElement).position().top - 500) <= scrollPos && ($(refElement).position().top - 500) + $(refElement).height() > scrollPos) {
                    $(".onepagenavi--wrapper .active").removeClass("active");
                    currElement.addClass("active");
                } else {
                    //currElement.removeClass("active");
                }
            }
        });
    }
}

function changeNavLinksAfterLoad() {
    $("#mobileNav li > *, #mainNav li > *, .onepagenavi--wrapper li > *").each(
        function (index) {
            var hash = window.location.hash;
            if ($(this).attr("href") == hash) {
                $("#mobileNav .active").removeClass("active");
                $("#mobileNav .mm-listitem_selected").removeClass(
                    "mm-listitem_selected"
                );
                $(this).addClass("active");
                $(this).parent().addClass("mm-listitem_selected");
            } else if ($(this).attr("href") == "#top") {
                $("#mobileNav .level_1 > .first").addClass("listitem_selected");
            }
        }
    );
    changeAnchorLinks();
}


if ($(".scrollToTop, .BodyScrollToTop").length) {
    $(".scrollToTop, .BodyScrollToTop").click(function () {
        $("html,body").animate({scrollTop: $("#top").offset().top}, "500");
        return false;
    });
    $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
            $(".BodyScrollToTop").addClass("visible");
        } else {
            $(".BodyScrollToTop").removeClass("visible");
        }
    });
}


/* Smooth Scrolling and set correct Item active */
if (window.location.hash) {
    var hash = window.location.hash;

    if ($(hash).length) {
        changeNavLinksAfterLoad();
    }
}


changeAnchorLinks();

$(document).on("scroll", function () {
    changeAnchorLinks();
});
