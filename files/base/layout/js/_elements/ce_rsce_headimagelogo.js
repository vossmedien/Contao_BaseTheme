function movingContent() {
    const movingHeadimagelogoElement = document.querySelector(
        ".ce_rsce_headimagelogo.move-content"
    );

    if (!movingHeadimagelogoElement) return;

    let articleContent = document.querySelector(".moved-content");
    if (!articleContent) {
        // Platzhalter erstellen und sofort einfügen
        const placeholder = document.createElement("div");
        placeholder.classList.add("moved-content-placeholder");
        placeholder.style.height = "100vh"; // Beispielhöhe anpassen
        movingHeadimagelogoElement.parentNode.insertBefore(placeholder, movingHeadimagelogoElement.nextSibling);

        articleContent = document.createElement("div");
        articleContent.classList.add("moved-content");
        articleContent.style.opacity = 0; // Unsichtbar machen

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
        let totalOffsetHeight = movingHeadimagelogoElement.offsetHeight;
        if (headerElement) {
            totalOffsetHeight -= headerElement.offsetHeight;
        }

        articleContent.style.marginTop = `${totalOffsetHeight}px`;

        // Verwende requestAnimationFrame für besseres Rendering
        requestAnimationFrame(() => {
            articleContent.style.transition = "opacity 0.25s ease-in-out";
            articleContent.style.opacity = 1;
            // Platzhalter entfernen, nachdem die Animation gestartet wurde
            requestAnimationFrame(() => placeholder.remove());
        });


    }
}

document.addEventListener("DOMContentLoaded", movingContent);
window.addEventListener("resize", movingContent);

