document.addEventListener(
  "DOMContentLoaded",
  function (event) {
    function setImageWidth() {
      if (window.innerWidth >= 767) {
        var containers = document.querySelectorAll(
          ".ce--imagetextwall:not(.container) .force-container"
        );

        containers.forEach(function (container) {
          var imageCol = container.querySelector(".image--col");
          var imageColImage = container.querySelector(".image-col--inner");
          var kachelDivA = container.querySelector(
            ".content-col--image + .kachel-column-width--indicator"
          );
          var kachelDivB = container.querySelector(
            ".image-col--inner + .kachel-column-width--indicator"
          );

          var contentCol = container.querySelector(".content--col");
          var contentColImage = container.querySelector(".content-col--image");

          var screenWidth = window.innerWidth;
          var containerWidth = container.offsetWidth;
          var distance = (screenWidth - containerWidth) / 2;

          // Berechnen Sie die tatsächliche Breite der Content-Spalte in Pixeln
          var contentWidth = window.getComputedStyle(contentCol).width;
          var imageWidth = window.getComputedStyle(imageCol).width;

          // Konvertieren Sie die Pixelbreite in einen Prozentsatz der Containerbreite
          var contentPercentage =
            (parseFloat(contentWidth) / containerWidth) * 100;
          var imagePercentage = (parseFloat(imageWidth) / containerWidth) * 100;

          // Berechnen Sie die Breite der Image-Spalte basierend auf dem Prozentsatz der Content-Spalte
          imageWidth = 100 - contentPercentage;
          contentWidth = 100 - imagePercentage;

          // Verwenden Sie den berechneten Abstand, um die Breite der Image-Spalte festzulegen

          if (imageColImage) {
            imageColImage.style.width =
              distance + containerWidth * (imageWidth / 100) - 16 + "px";
          }

          if (contentColImage) {
            contentColImage.style.width =
              distance + containerWidth * (contentWidth / 100) - 16 + "px";
          }

          if (kachelDivA) {
            kachelDivA.style.width =
              distance + containerWidth * (imageWidth / 100) - 16 + "px";
          }

          if (kachelDivB) {
            kachelDivA.style.width =
              distance + containerWidth * (contentWidth / 100) - 16 + "px";
          }
        });
      }
    }

    setImageWidth();

    window.onresize = function () {
      setImageWidth();
    };
  },
  { passive: true }
);
