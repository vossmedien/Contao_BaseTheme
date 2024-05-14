function movingContent() {
    const movingHeadimagelogoElement = document.querySelector(
        ".ce_rsce_headimagelogo.move-content"
    );


    let articleContent = document.querySelector(".moved-content");
    if (!articleContent && movingHeadimagelogoElement) {
        articleContent = document.createElement("div");
        articleContent.classList.add("moved-content");

        let nextElement = movingHeadimagelogoElement.nextElementSibling;
        while (nextElement) {
            articleContent.appendChild(nextElement);
            nextElement = movingHeadimagelogoElement.nextElementSibling;
        }

        movingHeadimagelogoElement.parentNode.insertBefore(
            articleContent,
            movingHeadimagelogoElement.nextSibling
        );

        const headerElement = document.getElementById("header");
        const totalOffsetHeight = movingHeadimagelogoElement.offsetHeight;
        if (headerElement && movingHeadimagelogoElement) {
            const totalOffsetHeight = movingHeadimagelogoElement.offsetHeight - headerElement.offsetHeight;
        }

        articleContent.style.marginTop = `${totalOffsetHeight}px`;
    }
}

document.addEventListener(
    "DOMContentLoaded",
    function (event) {
        movingContent();
    },
    {passive: true}
);


window.addEventListener("resize", movingContent);
