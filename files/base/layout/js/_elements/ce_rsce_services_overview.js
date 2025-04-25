document.addEventListener('DOMContentLoaded', function () {
    const overviewElements = document.querySelectorAll('.services-overview');

    // --- Helper Function to Activate Service ---
    const activateService = (index, overview) => {
        const serviceContents = overview.querySelectorAll('.service-content');
        const desktopLinks = overview.querySelectorAll('.service-link');
        const mobileButtons = overview.querySelectorAll('.mobile-service-button');
        const mobileNavContainer = overview.querySelector('.mobile-services-nav');
        const prevArrow = overview.querySelector('.mobile-nav-arrow.prev');
        const nextArrow = overview.querySelector('.mobile-nav-arrow.next');
        const totalItems = serviceContents.length;
        const newIndex = parseInt(index);

        if (isNaN(newIndex) || newIndex < 0 || newIndex >= totalItems) {
            console.error('Invalid index for activation:', index);
            return; // Ungültigen Index ignorieren
        }

        // Deactivate all
        serviceContents.forEach(c => c.classList.remove('active'));
        desktopLinks.forEach(l => l.classList.remove('active'));
        mobileButtons.forEach(b => b.classList.remove('active'));

        // Activate selected content
        const activeContent = overview.querySelector(`.service-content[data-index="${newIndex}"]`);
        if (activeContent) {
            activeContent.classList.add('active');
        }

        // Activate selected desktop link
        const activeLink = overview.querySelector(`.service-item[data-index="${newIndex}"] .service-link`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Activate selected mobile button and scroll into view
        const activeButton = overview.querySelector(`.mobile-service-button[data-index="${newIndex}"]`);
        if (activeButton && mobileNavContainer) {
            activeButton.classList.add('active');
            // Scroll into view if needed (smoothly)
            const containerRect = mobileNavContainer.getBoundingClientRect();
            const buttonRect = activeButton.getBoundingClientRect();

            // Check if button is fully or partially outside the container
            if (buttonRect.left < containerRect.left || buttonRect.right > containerRect.right) {
                 mobileNavContainer.scrollTo({
                     left: mobileNavContainer.scrollLeft + buttonRect.left - containerRect.left - (containerRect.width / 2) + (buttonRect.width / 2),
                     behavior: 'smooth'
                 });
            }
        }

        // Update arrow states
        if (prevArrow && nextArrow) {
            prevArrow.disabled = newIndex === 0;
            prevArrow.classList.toggle('disabled', newIndex === 0);
            nextArrow.disabled = newIndex === totalItems - 1;
            nextArrow.classList.toggle('disabled', newIndex === totalItems - 1);
        }

        // Kein Höhen-Recalc mehr nötig NACH Aktivierung, da die Höhe vorher gesetzt wird.
    };

    // --- Function to Position Mobile Arrows Vertically ---
    const positionMobileArrows = (overview) => {
        if (window.matchMedia('(max-width: 767.98px)').matches) {
            const contentContainer = overview.querySelector('.service-content-container');
            const prevArrow = overview.querySelector('.mobile-nav-arrow.prev');
            const nextArrow = overview.querySelector('.mobile-nav-arrow.next');
            const navWrapper = overview.querySelector('.mobile-services-nav-wrapper'); // Bezugspunkt für top

            if (contentContainer && prevArrow && nextArrow && navWrapper) {
                 // Berechne die Mitte des Content-Containers relativ zum Viewport
                const contentRect = contentContainer.getBoundingClientRect();
                console.log('Arrow Positioning: Measured container height:', contentRect.height); // DEBUG
                const contentMidY = contentRect.top + contentRect.height / 2;

                // Berechne die Oberkante des Nav-Wrappers relativ zum Viewport
                const wrapperRect = navWrapper.getBoundingClientRect();
                const wrapperTop = wrapperRect.top;

                // Berechne die Höhe eines Pfeils
                 const arrowHeight = prevArrow.offsetHeight;

                // Berechne die gewünschte Top-Position für die Pfeile relativ zum Nav-Wrapper
                 // Mitte Content (rel. Viewport) - Oberkante Wrapper (rel. Viewport) - Halbe Pfeilhöhe
                let targetTop = contentMidY - wrapperTop - (arrowHeight / 2);

                 // Sicherstellen, dass top nicht negativ ist oder übermäßig groß
                targetTop = Math.max(0, targetTop);

                // Wende die berechnete Top-Position an
                prevArrow.style.top = `${targetTop}px`;
                nextArrow.style.top = `${targetTop}px`;
                // Entferne transform, da wir top direkt setzen
                prevArrow.style.transform = 'none';
                nextArrow.style.transform = 'none';
            } else {
                console.warn('Elements for arrow positioning not found');
            }
        } else {
            // Auf Desktop sicherstellen, dass keine Inline-Stile bleiben
             const prevArrow = overview.querySelector('.mobile-nav-arrow.prev');
             const nextArrow = overview.querySelector('.mobile-nav-arrow.next');
             if (prevArrow) {
                prevArrow.style.top = '';
                 prevArrow.style.transform = '';
            }
             if (nextArrow) {
                 nextArrow.style.top = '';
                nextArrow.style.transform = '';
            }
        }
    };

    // --- Height Adjustment Logic for MOBILE (Clone Method - Reinstated) ---
     const setMobileContainerHeight = (overview) => {
         const contentContainer = overview.querySelector('.service-content-container');
         const serviceContents = overview.querySelectorAll('.service-content');
         if (!contentContainer || serviceContents.length === 0) return;

         if (window.matchMedia('(max-width: 767.98px)').matches) {
             let maxHeight = 0;
             // console.log('Calculating mobile height (clone method)...');

             const tempContainer = document.createElement('div');
             tempContainer.style.position = 'absolute';
             tempContainer.style.left = '-9999px';
             tempContainer.style.top = '-9999px';
             tempContainer.style.visibility = 'hidden';
             tempContainer.style.pointerEvents = 'none';
             document.body.appendChild(tempContainer);

             serviceContents.forEach((content, index) => {
                 const clone = content.cloneNode(true);
                 clone.style.width = contentContainer.offsetWidth + 'px';
                 clone.style.padding = window.getComputedStyle(content).padding;
                 clone.style.display = 'block';
                 clone.style.position = 'static';
                 clone.style.visibility = 'visible';
                 clone.style.opacity = '1';
                 clone.classList.add('active'); // Wichtig für korrekte Styles/Höhe

                 tempContainer.appendChild(clone);
                 const currentHeight = clone.offsetHeight;
                 maxHeight = Math.max(maxHeight, currentHeight);
                 // console.log(`Index ${index} clone height: ${currentHeight}`);
                 tempContainer.removeChild(clone);
             });

             document.body.removeChild(tempContainer);

             if (maxHeight > 0) {
                 contentContainer.style.minHeight = `${maxHeight}px`;
                 // console.log(`Mobile min-height set to: ${maxHeight}px`);
             } else {
                 // console.log('Max height calculation resulted in 0');
                 contentContainer.style.minHeight = ''; // Fallback
             }
         } else {
             contentContainer.style.minHeight = ''; // Höhe auf Desktop entfernen
         }
     };

    // --- Initialize Each Overview Element ---
    overviewElements.forEach(overview => {
        const serviceItems = overview.querySelectorAll('.service-item');
        const mobileButtons = overview.querySelectorAll('.mobile-service-button');
        const prevArrow = overview.querySelector('.mobile-nav-arrow.prev');
        const nextArrow = overview.querySelector('.mobile-nav-arrow.next');
        const contentContainer = overview.querySelector('.service-content-container');
        const disableHover = overview.hasAttribute('data-hover-disabled');
        const desktopEventType = disableHover ? 'click' : 'mouseenter';
        let currentActiveIndex = 0;

        // Initial Setup
        activateService(0, overview);
        setMobileContainerHeight(overview); // Erst Höhe berechnen
        setTimeout(() => positionMobileArrows(overview), 50); // Dann Pfeile positionieren (leichte Verzögerung sicherheitshalber)

        // --- Resize Handlers ---
        let resizeTimer;
        const debouncedResizeHandler = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                setMobileContainerHeight(overview); // Höhe neu berechnen
                positionMobileArrows(overview); // Pfeile neu positionieren

                const activeMobileBtn = overview.querySelector('.mobile-service-button.active');
                 if (activeMobileBtn && window.matchMedia('(max-width: 767.98px)').matches) {
                     // Scroll active button into view again after resize/height change
                     activateService(parseInt(activeMobileBtn.dataset.index), overview);
                 }
            }, 150);
        };
        window.addEventListener('resize', debouncedResizeHandler);

       // --- Event Listeners (Desktop List, Mobile Buttons, Arrows, Swipe - unchanged logic, calls activateService) ---
        // Desktop List
         serviceItems.forEach(item => {
            const linkElement = item.querySelector('.service-link');
            if (!linkElement) return;
            linkElement.addEventListener(desktopEventType, function (event) {
                const parentItem = this.closest('.service-item');
                if (!parentItem) return;
                if (disableHover && event.type === 'click' && this.tagName === 'A' && this.getAttribute('href')) {
                    event.preventDefault();
                }
                const index = parentItem.getAttribute('data-index');
                currentActiveIndex = parseInt(index);
                activateService(currentActiveIndex, overview);
            });
        });
        // Mobile Buttons
         mobileButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                currentActiveIndex = parseInt(index);
                activateService(currentActiveIndex, overview);
                 // Mobile height is fixed, no recalculation needed after click
                 // Arrow position might need update if content height affects wrapper somehow, but unlikely
                 // setTimeout(() => positionMobileArrows(overview), 60);
             });
        });
         // Mobile Arrows
         const handleArrowClick = (direction) => {
            const activeMobileBtn = overview.querySelector('.mobile-service-button.active');
            let currentIndex = activeMobileBtn ? parseInt(activeMobileBtn.dataset.index) : 0;
            let newIndex = currentIndex + direction;
            const totalItems = mobileButtons.length;
             newIndex = Math.max(0, Math.min(newIndex, totalItems - 1));
             if (newIndex !== currentIndex) {
                currentActiveIndex = newIndex;
                activateService(newIndex, overview);
                 // Mobile height is fixed, no recalculation needed after click
                 // Arrow position might need update if content height affects wrapper somehow, but unlikely
                 // setTimeout(() => positionMobileArrows(overview), 60);
            }
        };
        if (prevArrow) { prevArrow.addEventListener('click', () => handleArrowClick(-1)); }
        if (nextArrow) { nextArrow.addEventListener('click', () => handleArrowClick(1)); }
        // Swipe
         let touchStartX = 0; let touchEndX = 0; const swipeThreshold = 50;
         if (contentContainer) {
            contentContainer.addEventListener('touchstart', (event) => { if (event.touches.length === 1) { touchStartX = event.touches[0].clientX; } }, { passive: true });
            contentContainer.addEventListener('touchmove', (event) => { if (event.touches.length === 1) { touchEndX = event.touches[0].clientX; } }, { passive: true });
            contentContainer.addEventListener('touchend', () => {
                if (window.matchMedia('(max-width: 767.98px)').matches) {
                    const diffX = touchStartX - touchEndX;
                    if (Math.abs(diffX) > swipeThreshold) { handleArrowClick(diffX > 0 ? 1 : -1); }
                }
                touchStartX = 0; touchEndX = 0;
            });
        }

    }); // End forEach overviewElement
});
