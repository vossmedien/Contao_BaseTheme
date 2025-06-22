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

    // --- Height Adjustment Logic for MOBILE (Optimized - Reduced Height) ---
     const setMobileContainerHeight = (overview) => {
         const contentContainer = overview.querySelector('.service-content-container');
         const serviceContents = overview.querySelectorAll('.service-content');
         
         if (!contentContainer || serviceContents.length === 0) return;

         if (window.matchMedia('(max-width: 767.98px)').matches) {
             let maxHeight = 0;
             
             // Store original states
             const originalStates = [];
             
             // Temporär alle Contents aktivieren für korrekte Messung (position: relative)
             serviceContents.forEach((content, index) => {
                 originalStates[index] = {
                     wasActive: content.classList.contains('active'),
                     position: content.style.position,
                     visibility: content.style.visibility,
                     opacity: content.style.opacity,
                     zIndex: content.style.zIndex
                 };
                 
                 // WICHTIG: Alle temporär auf relative Position und sichtbar setzen
                 content.classList.add('active');
                 content.style.position = 'relative';
                 content.style.visibility = 'visible';
                 content.style.opacity = '1';
                 content.style.zIndex = '1';
             });
             
             // Force layout recalc
             contentContainer.offsetHeight;
             
             // Measure heights of actual text content with detailed debugging
             serviceContents.forEach((content, index) => {
                 let contentHeight = 0;
                 let debugInfo = [];
                 
                 // Headlines messen (stehen außerhalb von service-text!)
                 const headlines = content.querySelectorAll('h1, h2, h3, h4, h5, h6, .ce--headline');
                 let headlinesHeight = 0;
                 headlines.forEach(headline => {
                     headlinesHeight += headline.offsetHeight;
                 });
                 if (headlinesHeight > 0) {
                     contentHeight += headlinesHeight;
                     debugInfo.push(`headlines: ${headlinesHeight}px`);
                 }
                 
                 // Service-Text messen (nur der reine Text-Content)
                 const serviceText = content.querySelector('.service-text');
                 if (serviceText) {
                     const textHeight = serviceText.offsetHeight;
                     contentHeight += textHeight;
                     debugInfo.push(`text: ${textHeight}px`);
                 }
                 
                 // Service-Image messen
                 const serviceImage = content.querySelector('.service-image');
                 if (serviceImage) {
                     const imageHeight = serviceImage.offsetHeight;
                     contentHeight += imageHeight;
                     debugInfo.push(`image: ${imageHeight}px`);
                 }
                 
                 // Service-Buttons messen
                 const serviceButtons = content.querySelector('.service-buttons');
                 if (serviceButtons) {
                     const buttonsHeight = serviceButtons.offsetHeight;
                     contentHeight += buttonsHeight;
                     debugInfo.push(`buttons: ${buttonsHeight}px`);
                 }
                 
                 // Abstände zwischen Elementen
                 const elementsCount = [headlines.length > 0 ? 1 : 0, serviceText ? 1 : 0, serviceImage ? 1 : 0, serviceButtons ? 1 : 0].reduce((a, b) => a + b, 0);
                 let spacingHeight = 0;
                 if (elementsCount > 1) {
                     spacingHeight = (elementsCount - 1) * 16;
                     contentHeight += spacingHeight;
                     debugInfo.push(`spacing: ${spacingHeight}px`);
                 }
                 
                 maxHeight = Math.max(maxHeight, contentHeight);
                 console.log(`Content ${index}: ${contentHeight}px total [${debugInfo.join(', ')}]`);
             });
             
             // Restore original states
             serviceContents.forEach((content, index) => {
                 const state = originalStates[index];
                 if (!state.wasActive) {
                     content.classList.remove('active');
                 }
                 content.style.position = state.position;
                 content.style.visibility = state.visibility;
                 content.style.opacity = state.opacity;
                 content.style.zIndex = state.zIndex;
             });
             
             if (maxHeight > 0) {
                 // Container auf 90% der höchsten Content-Höhe setzen (kompakter für Mobile)
                 const targetHeight = Math.floor(maxHeight * 0.6);
                 contentContainer.style.height = `${targetHeight}px`;
                 console.log(`Mobile: Container height set to: ${targetHeight}px (90% of max ${maxHeight}px)`);
             } else {
                 contentContainer.style.height = '';
             }
         } else {
             // Desktop: Container-Höhe entfernen
             contentContainer.style.height = '';
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
