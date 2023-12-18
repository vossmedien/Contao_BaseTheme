document.addEventListener(
    "DOMContentLoaded",
    function (event) {
        const movingHeadimagelogoElements = document.querySelectorAll(".ce_rsce_headimagelogo.move-content");

        movingHeadimagelogoElements.forEach((el) => {
            const nextElement = el.nextElementSibling;
            if (nextElement) {
                nextElement.style.marginTop = `${el.offsetHeight}px`;
            }
        });
    },
    { passive: true }
);
