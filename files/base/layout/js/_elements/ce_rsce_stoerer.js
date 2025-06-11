document.addEventListener('DOMContentLoaded', function () {

    // --- Hilfsfunktion: Event Listener sicher hinzufügen (verhindert Duplikate) ---
    function addSafeEventListener(element, eventType, handler, options) {
        const listenerKey = `__${eventType}_handler__`;
        // Alten Listener entfernen, falls vorhanden
        if (element[listenerKey]) {
            element.removeEventListener(eventType, element[listenerKey], options);
        }
        // Neuen Listener hinzufügen und speichern
        element.addEventListener(eventType, handler, options);
        element[listenerKey] = handler;
    }

    // --- Hilfsfunktion: Pixelwert aus CSS-Wert berechnen ---
    function getPixelValue(value, baseElement = document.documentElement) {
        if (typeof value !== 'string') return parseFloat(value) || 0;
        const val = parseFloat(value);
        if (isNaN(val)) return 0;

        if (value.endsWith('px')) return val;
        if (value.endsWith('rem')) return val * parseFloat(getComputedStyle(document.documentElement).fontSize);
        if (value.endsWith('em')) return val * parseFloat(getComputedStyle(baseElement).fontSize); 
        if (value.endsWith('vh')) return val * window.innerHeight / 100;
        if (value.endsWith('vw')) return val * window.innerWidth / 100;
        // Prozentwerte sind kontextabhängig, hier ignoriert oder Standard-Fallback
        return val; // Fallback für reine Zahlen oder unbekannte Einheiten
    } 

    // --- START: Neue Hilfsfunktionen für Breitenberechnung ---
    function applyMaxWidth(stoerer) {
        const content = stoerer.querySelector('.stoerer--content'); 
        if (!content) return;
        
        const originalTransition = content.style.transition;
        content.style.transition = 'none';
        content.style.maxWidth = 'max-content';
        const scrollWidth = content.scrollWidth;
        content.style.maxWidth = '';
        content.style.transition = originalTransition;
        requestAnimationFrame(() => {
            content.style.setProperty('--stoerer-content-max-width', scrollWidth + 'px');
        });
    }

    function removeMaxWidth(stoerer) {
        const content = stoerer.querySelector('.stoerer--content');
        if (!content) return;
        content.style.removeProperty('--stoerer-content-max-width');
    }
    // --- ENDE: Neue Hilfsfunktionen für Breitenberechnung ---

    // --- Kernfunktionen (vereinfacht) ---
    // Funktion calculateDynamicWidth und Hover/Focus-Listener entfernt

    function handleMobileClick(event) {
         if (window.innerWidth < 768) {
            const stoerer = event.currentTarget.closest('.ce--stoerer');
            if (stoerer && !stoerer.classList.contains('clicked')) {
                event.preventDefault(); // Verhindert Link-Navigation nur beim ersten Klick
                removeAllClickedClasses(); // Schließe andere
                stoerer.classList.add('clicked');
            }
        }
        // Bei > 768px wird der Link normal verfolgt
    }

    function removeAllClickedClasses() {
        document.querySelectorAll('.ce--stoerer.clicked').forEach(el => el.classList.remove('clicked'));
    }

    function stackFixedStoerers() {
        const fixedStoererWrappers = document.querySelectorAll('.is-fixed.content--element.ce_rsce_stoerer');

        fixedStoererWrappers.forEach(wrapper => {
            const trulyFixedStoerers = Array.from(wrapper.querySelectorAll(':scope > .ce--stoerer.is-expandable'));
            const totalStoerers = trulyFixedStoerers.length;

            if (totalStoerers <= 1) {
                if(trulyFixedStoerers.length === 1) {
                    trulyFixedStoerers[0].style.zIndex = '';
                }
                return;
            }

            // --- Calculate Gap ---
            let gapValuePx = getPixelValue('0.75rem');
            const gapStyle = getComputedStyle(wrapper).getPropertyValue('--stoerer-vertical-gap') || '0.75rem';
            gapValuePx = getPixelValue(gapStyle.trim(), wrapper);

            // --- Sort Stoerers based on initial intended position ---
             trulyFixedStoerers.sort((a, b) => {
                 const getInitialTopPx = (el) => {
                      // Berechne initialen Top-Wert in Pixel für Sortierung, speichere Original-Style
                      if (el.dataset.initialTopPx === undefined) {
                          const styleTop = el.style.top || getComputedStyle(el).top;
                          if (!el.dataset.originalStyleTop) {
                              el.dataset.originalStyleTop = styleTop; // Store "25vh" etc.
                          }
                          el.dataset.initialTopPx = getPixelValue(styleTop, el);
                      }
                      return parseFloat(el.dataset.initialTopPx);
                  };
                  return getInitialTopPx(a) - getInitialTopPx(b);
              });

            // --- Position Stoerers ---
            let firstStoererOriginalTopStyle = "0px"; // Fallback
            let firstStoererTriggerHeight = 0;

            trulyFixedStoerers.forEach((element, index) => {
                element.style.zIndex = totalStoerers - index;

                if (index === 0) {
                    // FIRST element: Get its original top style (e.g., "25vh") and trigger height.
                    // Ensure original style is stored and applied.
                    if (!element.dataset.originalStyleTop) {
                        element.dataset.originalStyleTop = element.style.top || getComputedStyle(element).top || "0px";
                    }
                    firstStoererOriginalTopStyle = element.dataset.originalStyleTop;
                    element.style.top = firstStoererOriginalTopStyle; // Stelle sicher, dass der Originalwert drin steht

                    const triggerElement = element.querySelector('.stoerer-trigger');
                    firstStoererTriggerHeight = triggerElement ? triggerElement.offsetHeight : 0;

                } else {
                    // SUBSEQUENT elements: Set top using calc() based on the FIRST element's original top and trigger height.
                    element.style.top = `calc(${firstStoererOriginalTopStyle} + ${firstStoererTriggerHeight}px + ${gapValuePx}px)`;

                    // If more than 2 stoerers need stacking relative to each other, this part needs adjustment.
                    // For now, it positions all subsequent stoerers relative to the first one's trigger.
                }
            });
        });
    }

    function checkEdgePositioning() {
        // Selektiere direkt die Elemente, die potenziell am Rand sind
        const edgeStoerers = document.querySelectorAll('.ce--stoerer[style*="left:"]:not([style*="left: auto"]), .ce--stoerer[style*="right:"]:not([style*="right: auto"])');
        edgeStoerers.forEach(element => {
            element.classList.remove('is-flush-left', 'is-flush-right');
             const rect = element.getBoundingClientRect();
             const viewportWidth = window.innerWidth;
             const tolerance = 2;

            if (rect.left <= tolerance) {
                element.classList.add('is-flush-left');
            } else if (rect.right >= viewportWidth - tolerance) {
                element.classList.add('is-flush-right');
            }
        });  
    }

    // --- Initialisierungsfunktion ---
    function initializeAllStoerers() {
        const allStoerers = document.querySelectorAll('.ce--stoerer.is-expandable');
        const stoererContainers = document.querySelectorAll('.content--element.ce_rsce_stoerer');

        allStoerers.forEach(stoerer => {

            // Event-Listener für Trigger-Klick
            const trigger = stoerer.querySelector('.stoerer-trigger');
            if (trigger && !trigger.dataset.stoererClickListenerAttached) {
                addSafeEventListener(trigger, 'click', (event) => {
                    stoerer.classList.add('is-interacting'); // Set temporary interaction flag

                    // Störer öffnen/schließen Logik
                    if (stoerer.classList.contains('is-expanded')) {
                        stoerer.classList.remove('is-expanded');
                        stoerer.classList.remove('is-clicked-active');
                        removeMaxWidth(stoerer);
                    } else {
                        stoerer.classList.add('is-expanded');
                        stoerer.classList.add('is-clicked-active'); // Markiert als durch Klick aktiviert
                        applyMaxWidth(stoerer);
                    }

                    // Remove interaction flag after a short delay
                    setTimeout(() => {
                        stoerer.classList.remove('is-interacting');
                    }, 50); // 50ms delay
                });
                trigger.dataset.stoererListenerAttached = 'true';
            }

            // Event-Listener für Hover auf dem gesamten Störer-Element
            if (!stoerer.dataset.stoererHoverListenerAttached) {
                addSafeEventListener(stoerer, 'mouseenter', () => {
                    if (stoerer.classList.contains('is-interacting')) return; // Ignore if interacting
                    // Nur öffnen, wenn nicht durch Klick bereits aktiv
                    if (!stoerer.classList.contains('is-clicked-active')) {
                        stoerer.classList.add('is-expanded');
                        applyMaxWidth(stoerer);
                    }
                });

                addSafeEventListener(stoerer, 'mouseleave', () => {
                    if (stoerer.classList.contains('is-interacting')) return; // Ignore if interacting
                    // Nur schließen, wenn nicht durch Klick aktiviert
                    if (!stoerer.classList.contains('is-clicked-active')) { 
                        stoerer.classList.remove('is-expanded');
                        removeMaxWidth(stoerer);
                    } 
                });
                stoerer.dataset.stoererHoverListenerAttached = 'true';
            }
        });

        stackFixedStoerers();
        checkEdgePositioning();

        // Ready-Klasse hinzufügen, um sie sichtbar zu machen
        stoererContainers.forEach(container => container.classList.add('js-stoerer-ready'));
    }

    // --- Event Listeners ---
    let resizeTimer;

    // Initialer Aufruf
    initializeAllStoerers();

    // Bei Resize (debounced)
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(initializeAllStoerers, 250);
    });

    // Bei Klick ausserhalb (für Mobile UND Desktop zum Schließen)
    addSafeEventListener(document, 'click', function (event) {
        // Prüfen, ob außerhalb eines Störers geklickt wurde
        const clickedStoerer = event.target.closest('.ce--stoerer.is-expandable');

        // Alle Störer durchgehen, die aktuell geöffnet sind
        document.querySelectorAll('.ce--stoerer.is-expandable.is-expanded').forEach(expandedStoerer => {
            // Wenn der Klick NICHT innerhalb des gerade geprüften, geöffneten Störers war
            // ODER wenn der Klick auf dem Trigger eines ANDEREN Störers war (optional, kann zu Verwirrung führen)
            if (!clickedStoerer || clickedStoerer !== expandedStoerer) {
                expandedStoerer.classList.remove('is-expanded');
                expandedStoerer.classList.remove('is-clicked-active'); // Klick-Aktivierung aufheben
                removeMaxWidth(expandedStoerer); // Breite entfernen
            }
        });

        // Mobile: .clicked-Klasse entfernen, wenn außerhalb geklickt wird
        if (window.innerWidth < 768 && !event.target.closest('.ce--stoerer.clicked')) {
            removeAllClickedClasses();
        }
    });

    // Listener für Custom Event -> wenn theme.js einen Störer hinzufügt
    document.body.addEventListener('stoererAdded', function(e) {
        console.log('Event stoererAdded empfangen.');
        // *** NEU: Mit requestAnimationFrame aufrufen ***
        requestAnimationFrame(() => {
             initializeAllStoerers(); // Initialisierung neu ausführen
        });
    });

});  