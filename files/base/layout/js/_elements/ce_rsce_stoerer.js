document.addEventListener('DOMContentLoaded', function () {
    const stoerers = document.querySelectorAll('.ce--stoerer.is-expandable');

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
        const edgeStoerers = document.querySelectorAll('.ce--stoerer[style*="left:"]', '.ce--stoerer[style*="right:"]');
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

    function adjustStoererLayout() {
        const stoererElements = document.querySelectorAll('.ce--stoerer');
        stoererElements.forEach(element => {
            const triggerElement = element.querySelector('.stoerer-trigger');
            if (triggerElement) {
                const triggerHeight = triggerElement.offsetHeight;
                const triggerWidth = triggerElement.offsetWidth; // Get width

                // Set CSS variables for both height and width
                element.style.setProperty('--trigger-height', `${triggerWidth}px`);
                element.style.setProperty('--trigger-width', `${triggerWidth}px`); // Set width variable
            } else {
                 console.warn('Stoerer trigger not found in:', element);
                 // Set defaults if trigger not found
                 element.style.setProperty('--trigger-height', `50px`); // Example fallback height
                 element.style.setProperty('--trigger-width', `50px`); // Example fallback width
            }
        });
    }

    // --- Initial and Resize Calls ---
    function runLayoutAdjustments() {
        adjustStoererLayout(); // First adjust layout to get dimensions
        stackFixedStoerers(); // Then stack based on dimensions
        checkEdgePositioning(); // Finally check edge positions
    }

    // Initial run after a short delay
    setTimeout(runLayoutAdjustments, 150);

    // Recalculate on resize (debounced)
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(runLayoutAdjustments, 250);
    });

});