document.addEventListener('DOMContentLoaded', function () {
    const stoerers = document.querySelectorAll('.ce--stoerer.is-expandable');

    // --- START: Dynamic Width Calculation for Expandable Stoerers ---
    stoerers.forEach(stoerer => {
        const content = stoerer.querySelector('.stoerer--content');
        if (!content) return;

        const setMaxWidth = () => {
            // Temporarily remove transition and set max-width to measure content
            const originalTransition = content.style.transition;
            content.style.transition = 'none';
            content.style.maxWidth = 'max-content'; // Or 'fit-content' could also work
            const scrollWidth = content.scrollWidth;
            // Reset temporary styles immediately
            content.style.maxWidth = '';
            content.style.transition = originalTransition;

            // Force reflow might be needed in some browsers, uncomment if necessary
            // void content.offsetWidth;

            // Set the measured width as CSS variable *after* the next frame
            requestAnimationFrame(() => {
              content.style.setProperty('--stoerer-content-max-width', scrollWidth + 'px');
            });
        };

        const resetMaxWidth = () => {
            // Remove the CSS variable to allow CSS to collapse it back to 0
            content.style.removeProperty('--stoerer-content-max-width');
        };

        // Add listeners for mouse hover
        stoerer.addEventListener('mouseenter', setMaxWidth);
        stoerer.addEventListener('mouseleave', resetMaxWidth);

        // Add listeners for focus (for keyboard navigation/accessibility)
        stoerer.addEventListener('focusin', setMaxWidth);
        stoerer.addEventListener('focusout', resetMaxWidth);
    });
    // --- END: Dynamic Width Calculation ---

    function handleClick(event) {
        if (window.innerWidth < 768) {
            const stoerer = event.currentTarget.closest('.ce--stoerer');
            if (!stoerer.classList.contains('clicked')) {
                event.preventDefault();
                removeClickedClass();
                stoerer.classList.add('clicked');
            }
        }
    }

    function removeClickedClass() {
        document.querySelectorAll('.ce--stoerer.clicked').forEach(el => el.classList.remove('clicked'));
    }

    stoerers.forEach(stoerer => {
        const link = stoerer.querySelector('a');
        if (link) {
            link.addEventListener('click', handleClick);
        }
    });

    // Klick außerhalb schließt den geöffneten Störer
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.ce--stoerer') && window.innerWidth < 768) {
            removeClickedClass();
        }
    }); 

    // Optional: Recalculate on resize (consider debouncing for performance)
    let resizeTimer; // Declare resizeTimer once here

    // --- New logic for vertical stacking ---
    function stackFixedStoerers() {
        // Select the inner stoerer elements that are fixed and have a top style
        const fixedStoerers = Array.from(document.querySelectorAll('.ce--stoerer.is-expandable[style*="top:"]'));
        // Filter only those whose parent is also fixed
        const trulyFixedStoerers = fixedStoerers.filter(el => el.parentElement.classList.contains('is-fixed'));

        const totalStoerers = trulyFixedStoerers.length; // Gesamtzahl der Störer für z-index Berechnung
        if (totalStoerers <= 1) return; // No need to stack or set z-index if only one or zero

        // Get the gap from CSS variable or default (read from the parent)
        const parentContainer = trulyFixedStoerers.length > 0 ? trulyFixedStoerers[0].parentElement : null;
        let gapValuePx = 0.5 * parseFloat(getComputedStyle(document.documentElement).fontSize); // Default to 0.5rem in px

        if (parentContainer) {
             const gapStyle = getComputedStyle(parentContainer).getPropertyValue('--stoerer-vertical-gap');
             if (gapStyle) {
                 const gapMatch = gapStyle.match(/^(\d*\.?\d+)(px|rem|em|vh|%)?/);
                 if (gapMatch && gapMatch[1]) {
                    let value = parseFloat(gapMatch[1]);
                    const unit = gapMatch[2] || 'px';
                    // Convert known units to px
                    if (unit === 'rem') value = value * parseFloat(getComputedStyle(document.documentElement).fontSize);
                    else if (unit === 'em') value = value * parseFloat(getComputedStyle(parentContainer).fontSize); // Use parent context for em
                    // Add other unit conversions if needed (vh, %) - though less common for gaps
                    gapValuePx = value;
                 }
             }
         }


        let cumulativeTop = 0;
        let firstElementTop = 0;
        let previousElementHeight = 0;

        trulyFixedStoerers.forEach((element, index) => {
            // --- START: Z-Index Logic ---
            element.style.zIndex = totalStoerers - index;
            // --- END: Z-Index Logic ---

            const triggerElement = element.querySelector('.stoerer-trigger');
            if (!triggerElement) {
                console.warn('Stoerer trigger not found in:', element);
                return; // Skip if trigger not found
            }
            const currentTriggerHeight = triggerElement.offsetHeight;
            const currentElementTopStyle = element.style.top;
            let currentElementTopValue = 0;

            if (currentElementTopStyle) {
                const match = currentElementTopStyle.match(/^(\d*\.?\d+)(px|vh|%|em|rem)?/);
                if (match && match[1]) {
                     let value = parseFloat(match[1]);
                     const unit = match[2] || 'px';
                     if (unit === 'vh') value = value * window.innerHeight / 100;
                     else if (unit === 'rem') value = value * parseFloat(getComputedStyle(document.documentElement).fontSize);
                     else if (unit === 'em') value = value * parseFloat(getComputedStyle(element).fontSize);
                    currentElementTopValue = value;
                 }
            }

            if (index === 0) {
                firstElementTop = currentElementTopValue;
                cumulativeTop = firstElementTop;
                element.style.top = `${cumulativeTop}px`;
                previousElementHeight = currentTriggerHeight;
            } else {
                const newTop = cumulativeTop + previousElementHeight + gapValuePx;
                element.style.top = `${newTop}px`;
                cumulativeTop = newTop;
                previousElementHeight = currentTriggerHeight;
            }
        });
    }

    // Delay execution slightly to ensure accurate offsetHeight measurements
    // setTimeout(stackFixedStoerers, 100); // Moved call further down

    // window.addEventListener('resize', () => { // Moved listener further down
    //     clearTimeout(resizeTimer);
    //     resizeTimer = setTimeout(stackFixedStoerers, 250);
    // });


    // --- New logic for border radius on screen edge ---
    function checkEdgePositioning() {
        const edgeStoerers = document.querySelectorAll('.ce--stoerer[style*="left:"], .ce--stoerer[style*="right:"]');
        edgeStoerers.forEach(element => {
            element.classList.remove('is-flush-left', 'is-flush-right'); // Reset classes
            const computedStyle = getComputedStyle(element);
             // Check the position relative to the viewport, not just the style property
             const rect = element.getBoundingClientRect();
             const viewportWidth = window.innerWidth;

             // Add a small tolerance for calculations
             const tolerance = 2;

            if (rect.left <= tolerance) {
                element.classList.add('is-flush-left');
            } else if (rect.right >= viewportWidth - tolerance) {
                element.classList.add('is-flush-right');
            }
        });
    }

    // Initial check moved down

    /*
    // Entfernt, da die Größenberechnung jetzt rein über CSS/Bildgröße erfolgt
    function adjustStoererLayout() {
        const stoererElements = document.querySelectorAll('.ce--stoerer');
        stoererElements.forEach(element => {
            console.log("Element in adjustStoererLayout:", element);
            const triggerElement = element.querySelector('.stoerer-trigger');
            console.log("TriggerElement:", triggerElement);
            if (triggerElement && triggerElement.offsetHeight > 0) {
                const triggerHeight = triggerElement.offsetHeight;
                const triggerWidth = triggerElement.offsetWidth; // Get width
                console.log(`Trigger dimensions for ${element.id}: Width=${triggerWidth}, Height=${triggerHeight}`);
                // Set CSS variables for both height and width
                element.style.setProperty('--trigger-height', `${triggerHeight}px`);
                element.style.setProperty('--trigger-width', `${triggerWidth}px`); // Set width variable
            } else {
                console.warn('Stoerer trigger not found or has no height in:', element);
                // Set defaults if trigger not found or has no dimensions yet
                // element.style.setProperty('--trigger-height', `50px`); // Fallback removed
                // element.style.setProperty('--trigger-width', `50px`);  // Fallback removed
            }
        });
    }
    */

    // --- Initial and Resize Calls ----
    function runLayoutAdjustments() {
        const stoererContainers = document.querySelectorAll('.content--element.ce_rsce_stoerer'); // Get the outer containers
        // adjustStoererLayout(); // Removed call
        stackFixedStoerers(); // Then stack based on dimensions
        checkEdgePositioning(); // Finally check edge positions

        // Add ready class to make them visible AFTER calculations
        stoererContainers.forEach(container => container.classList.add('js-stoerer-ready'));
    }

    // Initial run after a short delay
    // setTimeout(runLayoutAdjustments, 50); // Use a minimal timeout if direct call is too early, otherwise remove
    runLayoutAdjustments(); // Try running directly first

    // Recalculate on resize (debounced)
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(runLayoutAdjustments, 250);
    });

});