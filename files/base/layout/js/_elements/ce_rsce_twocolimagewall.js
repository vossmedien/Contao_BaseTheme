function setImageWidth() {
    const rows = document.querySelectorAll('.row');
    const container = document.querySelector('.container');
    const viewportWidth = window.innerWidth;
    const containerWidth = container.offsetWidth;
    const gapLeft = (viewportWidth - containerWidth) / 2;
    const gapRight = viewportWidth - (containerWidth + gapLeft);

    rows.forEach(function (row) {
        const contentZoomContainer = row.querySelector('.content--col:not(.full-width) .zoom-container');
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

document.addEventListener(
    "DOMContentLoaded",
    function (event) {
        setImageWidth();
    },
    {passive: true}
);

window.addEventListener("resize", setImageWidth);
