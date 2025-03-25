document.addEventListener("DOMContentLoaded", function() {
    function setImageWidth() {
        // Alle twocolimagewall Elemente auswählen
        const mainContainers = document.querySelectorAll('.ce_rsce_twocolimagewall');

        mainContainers.forEach(mainContainer => {
            // Wenn der Container die Klasse "container" hat, diesen Container überspringen
            if (mainContainer.classList.contains('container')) {
                return;
            }

            const container = mainContainer.closest('.container') || document.querySelector('.container');
            const containerLeft = container.getBoundingClientRect().left;

            // Den vollen Container-Gutter für di   e Korrektur erhalten
            const gutter = parseFloat(getComputedStyle(document.documentElement)
                .getPropertyValue('--bs-container-gutter').trim());

            // Nur innerhalb des aktuellen mainContainer suchen
            mainContainer.querySelectorAll('.ce--imagetextwall--outer .row').forEach(row => {
                const contentCol = row.querySelector('.content--col');
                const imageCol = row.querySelector('.image--col');
                const contentZoom = contentCol?.querySelector('.zoom-container');
                const imageZoom = imageCol?.querySelector('.zoom-container');
                const isReverse = row.classList.contains('flex-row-reverse');

                if (window.innerWidth >= 992) {
                    if (contentZoom && contentCol) {
                        const contentWidth = isReverse
                            ? window.innerWidth - contentCol.getBoundingClientRect().left
                            : contentCol.getBoundingClientRect().right;

                        contentZoom.style.width = `${contentWidth - (gutter/2)}px`;
                        // contentZoom.style.marginLeft = isReverse ? '' : `${-containerLeft}px`;
                    }

                    if (imageZoom && imageCol) {
                        imageZoom.style.width = isReverse
                            ? `${imageCol.getBoundingClientRect().right - (gutter/2)}px`
                            : `${window.innerWidth - imageCol.getBoundingClientRect().left - (gutter/2)}px`;
                        // imageZoom.style.marginLeft = isReverse ? `${-containerLeft}px` : '';
                    }
                } else {
                    [contentZoom, imageZoom].forEach(zoom => {
                        if (zoom) {
                            zoom.style.width = '100%';
                            zoom.style.marginLeft = '';
                        }
                    });
                }
            });
        });
    }

    let resizeTimer;
    window.addEventListener('load', () => requestAnimationFrame(setImageWidth));
    window.addEventListener("resize", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(setImageWidth, 50);
    }, { passive: true });
    setTimeout(setImageWidth, 100);
}, { passive: true });