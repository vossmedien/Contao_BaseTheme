if ($(".ce_rsce_colorpalettes").length) {
    $(".color-list--element").click(function () {
        var storeDesc = $(this).data("desc");
        var storeHeadline = $(this).data("headline");
        var storeTitle = $(this).data("title");
        var storeImg = $(this).data("img");
        var parentDiv = $(this).closest(".ce_rsce_colorpalettes");
        var basicHeadline = parentDiv
            .find(".colorpalettes-top")
            .data("basicheadline");
        var basicDesc = parentDiv.find(".colorpalettes-top").data("basicdesc");

        if (storeDesc) {
            parentDiv.find(".color-palette--desc").html(storeDesc);
        } else {
            parentDiv.find(".color-palette--desc").html(basicDesc);
        }

        if (storeHeadline) {
            parentDiv.find(".color-palette--headline").html(storeHeadline);
        } else {
            parentDiv.find(".color-palette--headline").html(basicHeadline);
        }

        if (storeTitle) {
            parentDiv.find(".selected-color-element--title").html(storeTitle);
        } else {
        }

        if (storeImg) {
            parentDiv
                .find(".image-holder")
                .css("background-image", "url(" + storeImg + ")");
        } else {
        }
    });
}