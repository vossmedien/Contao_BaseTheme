document.addEventListener("DOMContentLoaded", function (event) {
    if ($(".ce_rsce_sitemap").length) {
        $($(".mm_dropdown a")).each(function (index) {
            var originalUrl = window.location.pathname;
            if (originalUrl.length > 1) {
                originalUrl = window.location.pathname.substring(1);
            }

            if ($(this).attr("href") == originalUrl) {
                $(this).addClass("active");
            }
        });
    }
});