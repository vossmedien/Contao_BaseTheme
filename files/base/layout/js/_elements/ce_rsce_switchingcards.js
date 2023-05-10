document.addEventListener("DOMContentLoaded", function (event) {
    if ($(".ce_rsce_switchingcards").length) {
        $(".flipping-card--wrapper").each(function (index) {
            var front = $(this).find(".flipping-card--front");
            var back = $(this).find(".flipping-card--back");
            var efrontHeight = $(this).find(".flipping-card--front .front--inner");
            var ebackHeight = $(this).find(".flipping-card--back .back--inner");
            var frontHeight = efrontHeight.height();
            var backHeight = ebackHeight.height();

            if (backHeight > frontHeight) {
                front.css("height", backHeight + 40);
            } else {
                back.css("height", frontHeight + 40);
            }
        });
    }
}, {passive: true});