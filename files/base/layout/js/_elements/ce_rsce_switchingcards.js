        if ($(".ce_rsce_switchingcards").length) {
            $(".flipping-card--wrapper").each(function (index) {
                var front = $(this).find(".flipping-card--front");
                var back = $(this).find(".flipping-card--back");
                var frontHeight = front.height();
                var backHeight = back.height();

                if (backHeight > frontHeight) {
                    front.css("height", backHeight);
                } else {
                    back.css("height", frontHeight);
                }
            });
        }