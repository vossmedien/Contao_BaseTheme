document.addEventListener(
    "DOMContentLoaded",
    function (event) {
        function setImageWidth() {
            const rows = document.querySelectorAll('.row');
            const container = document.querySelector('.container');
            const viewportWidth = window.innerWidth;
            const containerWidth = container.offsetWidth;
            const gapLeft = (viewportWidth - containerWidth) / 2;
            const gapRight = viewportWidth - (containerWidth + gapLeft);

            rows.forEach(function (row) {
                const contentZoomContainer = row.querySelector('.content--col .zoom-container');
                const imageZoomContainer = row.querySelector('.image--col .zoom-container');
                const isRowReverse = row.classList.contains('flex-row-reverse');

                if (window.innerWidth >= 992) {
                    if (contentZoomContainer) {
                        const contentColumnWidth = contentZoomContainer.parentElement.offsetWidth;
                        const contentImageWidth = contentColumnWidth + (isRowReverse ? gapRight : gapLeft);
                        const contentImageMoveX = isRowReverse ? 0 : -gapLeft;

                        contentZoomContainer.style.width = contentImageWidth + 'px';
                        contentZoomContainer.style.marginLeft = isRowReverse ? 'auto' : contentImageMoveX + 'px';
                        contentZoomContainer.style.marginRight = isRowReverse ? contentImageMoveX + 'px' : 'auto';
                    }

                    if (imageZoomContainer) {
                        const imageColumnWidth = imageZoomContainer.parentElement.offsetWidth;
                        const imageImageWidth = imageColumnWidth + (isRowReverse ? gapLeft : gapRight);
                        const imageImageMoveX = isRowReverse ? -gapRight : 0;

                        imageZoomContainer.style.width = imageImageWidth + 'px';
                        imageZoomContainer.style.marginLeft = isRowReverse ? imageImageMoveX + 'px' : 'auto';
                        imageZoomContainer.style.marginRight = isRowReverse ? 'auto' : imageImageMoveX + 'px';
                    }
                } else {
                    if (contentZoomContainer) {
                        contentZoomContainer.style.width = '';
                        contentZoomContainer.style.marginLeft = '';
                        contentZoomContainer.style.marginRight = '';
                    }

                    if (imageZoomContainer) {
                        imageZoomContainer.style.width = '';
                        imageZoomContainer.style.marginLeft = '';
                        imageZoomContainer.style.marginRight = '';
                    }
                }
            });
        }

        setImageWidth();

        var resizeTimer;
        window.addEventListener("resize", function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                setImageWidth();
            }, 0); // Ändere die Verzögerungszeit (in Millisekunden) nach Bedarf
        });


        /*
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

              var contentPercentage =
                (parseFloat(contentWidth) / containerWidth) * 100;
              var imagePercentage = (parseFloat(imageWidth) / containerWidth) * 100;

              imageWidth = 100 - contentPercentage;
              contentWidth = 100 - imagePercentage;

              if (imageColImage) {
                imageColImage.style.width =
                  distance + containerWidth * (imageWidth / 100) + "px";
              }

              if (contentColImage) {
                contentColImage.style.width =
                  distance + containerWidth * (contentWidth / 100) + "px";
              }

              // Find all .kachel-column-width--indicator elements in the container
              var kachelDivs = container.querySelectorAll(
                ".kachel-column-width--indicator"
              );

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


         */

    },
    {passive: true}
);
