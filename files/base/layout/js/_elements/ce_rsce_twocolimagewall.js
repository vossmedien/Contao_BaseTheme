document.addEventListener("DOMContentLoaded", function (event) {

    function adjustImageColumnWidths() {
        const mainContent = document.querySelector('.main-content');
        const rows = document.querySelectorAll('.ce_rsce_twocolimagewall .ce--imagetextwall--outer .row');

        if (!mainContent) {
            console.warn('.main-content area not found for image width calculation.');
            return;
        }
        const mainRect = mainContent.getBoundingClientRect();

        rows.forEach(row => {
            const imageCol = row.querySelector('.image--col');
            const contentCol = row.querySelector('.content--col');
            const imageInner = imageCol ? imageCol.querySelector('.image-col--inner') : null;
            const darkenOverlayImage = imageCol ? imageCol.querySelector('.darkened-content') : null;
            const darkenOverlayContent = contentCol ? contentCol.querySelector('.darkened-content') : null;

            const isDesktop = window.innerWidth >= 992;
            const isRowReverse = row.classList.contains('flex-row-reverse');
            const notAsBg = imageInner && imageInner.classList.contains('not-as-bg');
            const isRowBg = imageInner && imageInner.classList.contains('is-row-bg');

            // Helper function to reset styles for overlays and column
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

            // Reset styles first
            resetStyles(darkenOverlayImage);
            resetStyles(darkenOverlayContent);
            resetStyles(imageCol); // Reset imageCol styles

            // Stop adjustments if not on Desktop
            if (!isDesktop) return;
            // Stop adjustments if columns not found
            if (!imageCol || !contentCol) return;

            const imageColRect = imageCol.getBoundingClientRect();
            const contentColRect = contentCol.getBoundingClientRect();

            // --- Adjust Image Column Width ---
            // Apply width adjustment ONLY if image IS acting as background AND is NOT a row background
            if (!notAsBg && !isRowBg) {
                let targetWidthCol = 0;
                if (isRowReverse) { // Image col left
                    const spaceRightOfImageCol = window.innerWidth - imageColRect.right;
                    const spaceLeftOfMain = mainRect.left;
                    targetWidthCol = window.innerWidth - spaceRightOfImageCol - spaceLeftOfMain;
                } else { // Image col right
                    const spaceLeftOfImageCol = imageColRect.left;
                    const spaceRightOfMain = window.innerWidth - mainRect.right;
                    targetWidthCol = window.innerWidth - spaceLeftOfImageCol - spaceRightOfMain;
                }
                targetWidthCol = Math.max(0, Math.min(targetWidthCol, window.innerWidth));
                imageCol.style.width = `${targetWidthCol}px`;
                imageCol.style.maxWidth = `${targetWidthCol}px`; // Important to override BS col width

                // No need to adjust imageElement or justify-content anymore
            }

            // --- Adjust Image Overlay --- (Now only opacity)
            if (darkenOverlayImage) {
                // Width/position adjustments removed, CSS handles 100% width of column
                darkenOverlayImage.style.opacity = '1';
            }

            // --- Adjust Content Column Overlay --- (Remains the same)
            if (darkenOverlayContent) {
                let targetWidthContentOverlay = 0;
                 if (isRowReverse) { // Content right
                    const spaceLeftOfContentCol = contentColRect.left;
                    const spaceRightOfMain = window.innerWidth - mainRect.right;
                    targetWidthContentOverlay = window.innerWidth - spaceLeftOfContentCol - spaceRightOfMain;
                    darkenOverlayContent.style.left = '0';
                    darkenOverlayContent.style.right = 'auto';
                 } else { // Content left
                    const spaceRightOfContentCol = window.innerWidth - contentColRect.right;
                    const spaceLeftOfMain = mainRect.left;
                    targetWidthContentOverlay = window.innerWidth - spaceRightOfContentCol - spaceLeftOfMain;
                    darkenOverlayContent.style.right = '0';
                    darkenOverlayContent.style.left = 'auto';
                 }
                targetWidthContentOverlay = Math.max(0, Math.min(targetWidthContentOverlay, window.innerWidth));
                darkenOverlayContent.style.width = `${targetWidthContentOverlay}px`;
                darkenOverlayContent.style.maxWidth = `${targetWidthContentOverlay}px`;
                darkenOverlayContent.style.opacity = '1';
            }
        });
    }

    // --- Function Execution ---
    function runAdjustments() {
        requestAnimationFrame(adjustImageColumnWidths);
    }

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

    setTimeout(runAdjustments, 250);

}, { passive: true });