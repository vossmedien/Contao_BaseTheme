document.addEventListener(
    "DOMContentLoaded",
    function (event) {
        function setImageWidth() {
            if (window.innerWidth >= 992) {
                var containers = document.querySelectorAll(
                    ".ce--imagetextwall > .content-holder"
                );

                containers.forEach(function (container) {
                    var imageCol = container.querySelector(".image--col");
                    var imageColImage = container.querySelector(".image-col--inner");

                    var contentCol = container.querySelector(".content--col");
                    var contentColImage = container.querySelector(".content-col--image");

                    var screenWidth = window.innerWidth;
                    var containerWidth = container.offsetWidth;

                    if (container.classList.contains("force-container")) {
                        var distance = (screenWidth - containerWidth) / 2 - 15; //die 15 sind der container-padding, workaround?
                    } else {
                        var distance = 0;
                    }


                    var contentWidth = window.getComputedStyle(contentCol).width;


                    if (imageCol) {
                        var imageWidth = window.getComputedStyle(imageCol).width;
                    }


                    var contentPercentage = (parseFloat(contentWidth) / containerWidth) * 100;
                    var imagePercentage = (parseFloat(imageWidth) / containerWidth) * 100;

                    imageWidth = 100 - contentPercentage;
                    contentWidth = 100 - imagePercentage;

                    if (imageColImage) {
                        imageColImage.style.width = distance + containerWidth * (imageWidth / 100) + "px";
                    }

                    if (contentColImage) {
                        contentColImage.style.width = distance + containerWidth * (contentWidth / 100) + "px";
                    }

                    // Find all .kachel-column-width--indicator elements in the container
                    var kachelDivs = container.querySelectorAll(".kachel-column-width--indicator");

                    // Iterate over each .kachel-column-width--indicator element
                    kachelDivs.forEach(function (kachelDiv) {
                        var parentDiv = kachelDiv.parentElement;
                        var widthDiv = parentDiv.querySelector('[class*="col"]');

                        setTimeout(function () {
                            var parentDivWidth = widthDiv.clientWidth;
                            kachelDiv.style.width = parentDivWidth + "px";
                        }, 0);

                    });
                });

            }
        }

        setImageWidth();

        var resizeTimer;
        window.addEventListener("resize", function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                setImageWidth();
            }, 0); // Ändere die Verzögerungszeit (in Millisekunden) nach Bedarf
        });
    }, {passive: true});