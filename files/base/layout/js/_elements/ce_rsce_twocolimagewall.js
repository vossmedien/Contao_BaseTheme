document.addEventListener("DOMContentLoaded", function (event) {
    // Die Funktion setImageWidth und alle zugehörigen Aufrufe wurden entfernt,
    // da das Layout jetzt rein über CSS gesteuert wird.

    // Bestehender Code für andere Funktionalitäten (z.B. Swiper, Lightbox Initialisierung,
    // Animationen) bleibt erhalten oder wird hier hinzugefügt, falls nötig.

    // Beispiel: Initialisierung von Animationen, falls noch verwendet
    // const observer = new IntersectionObserver(entries => { ... });
    // document.querySelectorAll('[data-animation]').forEach(el => observer.observe(el));

    function adjustImageColumnWidths() {
        const mainContent = document.querySelector('.main-content'); // Use .main-content selector
        const rows = document.querySelectorAll('.ce_rsce_twocolimagewall .ce--imagetextwall--outer .row');

        if (!mainContent) {
            console.warn('.main-content area not found for image width calculation.');
            return; 
        }
        const mainRect = mainContent.getBoundingClientRect();

        rows.forEach(row => {
            const imageCol = row.querySelector('.image--col');
            const imageInner = imageCol ? imageCol.querySelector('.image-col--inner') : null;
            
            // Select the actual visual element: img (even inside picture), otherwise swiper, then video
            let imageElement = imageInner ? imageInner.querySelector('picture > img, img:not(picture > img), .swiper, video') : null;

            // Only proceed if we have the necessary elements and are on desktop view (e.g., >= 992px)
            if (!imageCol || !imageElement || window.innerWidth < 992) {
                if(imageElement) {
                    imageElement.style.width = ''; 
                    imageElement.style.maxWidth = '';
                     // Reset inner container alignment if needed
                    if (imageInner) {
                        imageInner.style.justifyContent = ''; 
                    }
                }
                return; 
            }

            const imageColRect = imageCol.getBoundingClientRect();
            const isRowReverse = row.classList.contains('flex-row-reverse');

            let targetWidth = 0;

            if (isRowReverse) {
                // Image is on the left (col-lg-N), should extend to the left edge of .main-content
                const spaceRightOfImageCol = window.innerWidth - imageColRect.right;
                const spaceLeftOfMain = mainRect.left;
                targetWidth = window.innerWidth - spaceRightOfImageCol - spaceLeftOfMain;

            } else {
                // Image is on the right (col-lg), should extend to the right edge of .main-content
                const spaceLeftOfImageCol = imageColRect.left;
                const spaceRightOfMain = window.innerWidth - mainRect.right;
                targetWidth = window.innerWidth - spaceLeftOfImageCol - spaceRightOfMain;
            }
            
            targetWidth = Math.max(0, Math.min(targetWidth, window.innerWidth));

            // Apply the calculated width specifically to the img/swiper/video element
            imageElement.style.width = `${targetWidth}px`;
            imageElement.style.maxWidth = `${targetWidth}px`;

            if (imageInner) {
                imageInner.style.width = 'auto'; 
                if(isRowReverse) {
                    // Align the inner container's content (the imageElement) to the right edge
                    imageInner.style.justifyContent = 'flex-end'; 
                } else {
                     // Align the inner container's content (the imageElement) to the left edge
                    imageInner.style.justifyContent = 'flex-start';
                }
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