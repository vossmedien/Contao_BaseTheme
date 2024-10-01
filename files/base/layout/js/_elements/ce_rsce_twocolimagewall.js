document.addEventListener("DOMContentLoaded", function (event) {
    function setImageWidth() {
        const rows = document.querySelectorAll('.ce--imagetextwall--outer .row');
        const container = document.querySelector('.container');
        const wrapper = document.querySelector('#wrapper');
        const viewportWidth = window.innerWidth;
        const containerWidth = container.offsetWidth;
        const wrapperWidth = wrapper.offsetWidth;
        const wrapperMaxWidth = parseInt(window.getComputedStyle(wrapper).maxWidth) || viewportWidth;

        const effectiveWidth = Math.min(viewportWidth, wrapperMaxWidth);
        const gapToEdge = (effectiveWidth - containerWidth) / 2;

        rows.forEach(function (row) {
            const contentCol = row.querySelector('.content--col');
            const contentZoomContainer = contentCol ? contentCol.querySelector('.zoom-container') : null;
            const imageCol = row.querySelector('.image--col');
            const imageZoomContainer = imageCol ? imageCol.querySelector('.zoom-container') : null;
            const isRowReverse = row.classList.contains('flex-row-reverse');

            if (window.innerWidth >= 992) {
                if (contentZoomContainer && contentCol) {
                    const contentColWidth = contentCol.offsetWidth;
                    const contentZoomWidth = isRowReverse
                        ? contentColWidth + gapToEdge
                        : contentColWidth;

                    contentZoomContainer.style.width = contentZoomWidth + 'px';
                    contentZoomContainer.style.marginLeft = isRowReverse ? '' : '0';
                    contentZoomContainer.style.marginRight = isRowReverse ? '0' : '';
                }

                if (imageZoomContainer && imageCol) {
                    const imageColWidth = imageCol.offsetWidth;
                    const imageZoomWidth = imageColWidth + gapToEdge;

                    imageZoomContainer.style.width = imageZoomWidth + 'px';

                    if (isRowReverse) {
                        imageZoomContainer.style.marginLeft = -gapToEdge + 'px';
                        imageZoomContainer.style.marginRight = '';
                    } else {
                        imageZoomContainer.style.marginLeft = '';
                        imageZoomContainer.style.marginRight = -gapToEdge + 'px';
                    }
                }
            } else {
                // Reset styles for mobile view
                [contentZoomContainer, imageZoomContainer].forEach(container => {
                    if (container) {
                        container.style.width = '100%';
                        container.style.marginLeft = '';
                        container.style.marginRight = '';
                    }
                });
            }

            // Einblenden der Container nach der Berechnung
            [contentZoomContainer, imageZoomContainer].forEach(container => {
                if (container) {
                    container.style.opacity = '1';
                }
            });
        });
    }

    // Initial alle Zoom-Container ausblenden
    document.querySelectorAll('.zoom-container').forEach(container => {
        container.style.opacity = '0';
        container.style.transition = 'opacity 0.3s ease-in-out';
    });

    // Funktion zum Verzögern der initialen Ausführung
    function delayedSetImageWidth() {
        requestAnimationFrame(setImageWidth);
    }

    // Initiale Ausführung nach dem Laden aller Ressourcen
    window.addEventListener('load', delayedSetImageWidth);

    // Ausführung bei Resize
    var resizeTimer;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(setImageWidth, 50);
    });

    // Zusätzliche Ausführung nach einem kurzen Timeout
    setTimeout(setImageWidth, 100);
}, {passive: true});