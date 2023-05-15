document.addEventListener(
    "DOMContentLoaded",
    function (event) {
        function setImageHeight() {
            var containers = document.querySelectorAll(
                ".mainimage--content-inner"
            );

            containers.forEach(function (container) {
                container.parentElement.style.minHeight = container.offsetHeight + 75 + "px";
            });
        }

        setImageHeight();

        window.onresize = function () {
            setImageHeight();
        };
    },
    {passive: true}
);
