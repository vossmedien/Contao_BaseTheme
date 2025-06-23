document.addEventListener("DOMContentLoaded", function (event) {
    function adjustImageColumnWidths() {
        const rows = document.querySelectorAll('.ce_rsce_twocolimagewall .ce--imagetextwall--outer .row');

        rows.forEach(row => {
            const imageCol = row.querySelector('.image--col');
            const contentCol = row.querySelector('.content--col');
            const imageInner = imageCol ? imageCol.querySelector('.image-col--inner') : null;
            const darkenOverlayImage = imageCol ? imageCol.querySelector('.darkened-content') : null;
            const darkenOverlayContent = contentCol ? contentCol.querySelector('.darkened-content') : null;

            const containerElement = row.closest('.ce_rsce_twocolimagewall');
            let refElement = document.querySelector('.main-content');

            if (containerElement) {
                const hasContainerClass = Array.from(containerElement.classList).some(cls => cls.startsWith('container'));
                if (hasContainerClass) {
                    // Create a virtual reference that represents the actual container content area
                    // by calculating where the container padding should end
                    const containerRect = containerElement.getBoundingClientRect();
                    const computedStyle = window.getComputedStyle(containerElement);
                    const paddingLeft = parseFloat(computedStyle.paddingLeft) || 0;
                    const paddingRight = parseFloat(computedStyle.paddingRight) || 0;
                    
                    refElement = {
                        getBoundingClientRect: () => ({
                            left: containerRect.left + paddingLeft,
                            right: containerRect.right - paddingRight,
                            width: containerRect.width - paddingLeft - paddingRight
                        })
                    };
                }
            }

            if (!refElement) {
                console.warn('Reference area (.main-content or specific .ce_rsce_twocolimagewall.container*) not found for row:', row);
                return;
            }
            const refRect = refElement.getBoundingClientRect();

            const isDesktop = window.innerWidth >= 992;
            const isRowReverse = row.classList.contains('flex-row-reverse');
            const notAsBg = imageInner && imageInner.classList.contains('not-as-bg');
            const isRowBg = imageInner && imageInner.classList.contains('is-row-bg');
            const isFullwidth = containerElement && containerElement.classList.contains('is-fullwidth');

            const resetStyles = (el) => {
                 if (el) {
                     el.style.width = '';
                     el.style.maxWidth = '';
                     el.style.left = '';
                     el.style.right = '';
                     if (el === darkenOverlayImage || el === darkenOverlayContent) {
                         el.style.opacity = '';
                     }
                 }
            };

            resetStyles(darkenOverlayImage);
            resetStyles(darkenOverlayContent);
            resetStyles(imageCol);

            if (!isDesktop) return;
            if (!imageCol || !contentCol) return;

            const imageColRect = imageCol.getBoundingClientRect();
            const contentColRect = contentCol.getBoundingClientRect();

            if (!notAsBg && !isRowBg) {
                let targetWidthCol = 0;

                if (isFullwidth) {
                    // For fullwidth elements, extend to absolute screen edges
                    if (isRowReverse) {
                        // Image visually on left: extend from current position to left screen edge (0)
                        targetWidthCol = imageColRect.left + imageColRect.width;
                    } else {
                        // Image visually on right: extend from current position to right screen edge
                        targetWidthCol = window.innerWidth - imageColRect.left;
                    }
                } else {
                    // Container elements: extend to container edge, not viewport edge
                    if (isRowReverse) {
                        // Image on left: extend from container left edge to current right edge of image
                        targetWidthCol = imageColRect.right - refRect.left;
                    } else {
                        // Image on right: extend from current left edge to container right edge
                        targetWidthCol = refRect.right - imageColRect.left;
                    }
                }

                targetWidthCol = Math.max(0, Math.min(targetWidthCol, window.innerWidth));
                imageCol.style.width = `${targetWidthCol}px`;
                imageCol.style.maxWidth = `${targetWidthCol}px`;
            }

            if (darkenOverlayImage) {
                darkenOverlayImage.style.opacity = '1';
            }

            if (darkenOverlayContent) {
                let targetWidthContentOverlay = 0;
                if (isFullwidth) {
                    // For fullwidth: extend to viewport edges
                    if (isRowReverse) {
                        targetWidthContentOverlay = contentColRect.right;
                        darkenOverlayContent.style.left = '0';
                        darkenOverlayContent.style.right = 'auto';
                    } else {
                        targetWidthContentOverlay = window.innerWidth - contentColRect.left;
                        darkenOverlayContent.style.right = '0';
                        darkenOverlayContent.style.left = 'auto';
                    }
                } else {
                    // For container: extend to container edges
                    if (isRowReverse) {
                        targetWidthContentOverlay = contentColRect.right - refRect.left;
                        darkenOverlayContent.style.left = '0';
                        darkenOverlayContent.style.right = 'auto';
                    } else {
                        targetWidthContentOverlay = refRect.right - contentColRect.left;
                        darkenOverlayContent.style.right = '0';
                        darkenOverlayContent.style.left = 'auto';
                    }
                }
                targetWidthContentOverlay = Math.max(0, Math.min(targetWidthContentOverlay, window.innerWidth));
                darkenOverlayContent.style.width = `${targetWidthContentOverlay}px`;
                darkenOverlayContent.style.maxWidth = `${targetWidthContentOverlay}px`;
                darkenOverlayContent.style.opacity = '1';
            }
        });
    }

    function runAdjustments() {
        requestAnimationFrame(adjustImageColumnWidths);
    }

    // Run immediately to prevent flicker
    runAdjustments();

    if (document.readyState === 'complete') {
        runAdjustments();
    } else {
        window.addEventListener('load', runAdjustments);
    }

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(runAdjustments, 100);
    }, { passive: true });

    // Additional runs to ensure everything is loaded
    setTimeout(runAdjustments, 100);
    setTimeout(runAdjustments, 250);

}, { passive: true });