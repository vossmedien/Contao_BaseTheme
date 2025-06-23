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
        return val; // Fallback für reine Zahlen oder unbekannte Einheiten
    } 

    // --- Hilfsfunktionen für Breitenberechnung ---
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

    // --- Vereinfachte State-Verwaltung ---
    class StoererState {
        constructor(element) {
            this.element = element;
            this.isExpanded = false;
            this.isActive = false; // Vereinfacht: nur ein Active-State statt mehrerer
            this.isInteracting = false;
        }

        expand() {
            if (!this.isExpanded) {
                this.isExpanded = true;
                this.element.classList.add('is-expanded');
                applyMaxWidth(this.element);
            }
        }

        collapse() {
            if (this.isExpanded) {
                this.isExpanded = false;
                this.element.classList.remove('is-expanded');
                removeMaxWidth(this.element);
            }
        }

        activate() {
            this.isActive = true;
            this.element.classList.add('is-active');
        }

        deactivate() {
            this.isActive = false;
            this.element.classList.remove('is-active');
        }

        setInteracting(state) {
            this.isInteracting = state;
            this.element.classList.toggle('is-interacting', state);
        }
    }

    // --- Globale State-Map ---
    const stoererStates = new Map();

    function getStoererState(element) {
        if (!stoererStates.has(element)) {
            stoererStates.set(element, new StoererState(element));
        }
        return stoererStates.get(element);
    }

    // --- Mobile-Handler vereinfacht ---
    function handleMobileClick(event) {
        if (window.innerWidth >= 768) return; // Nur mobile
        
        const stoerer = event.currentTarget.closest('.ce--stoerer');
        if (!stoerer) return;
        
        const state = getStoererState(stoerer);
        
        if (!state.isActive) {
            event.preventDefault(); // Verhindert Link-Navigation nur beim ersten Klick
            closeAllMobileStates(); // Schließe andere
            state.activate();
            state.expand();
        }
    }

    function closeAllMobileStates() {
        stoererStates.forEach(state => {
            if (window.innerWidth < 768) {
                state.deactivate();
                state.collapse();
            }
        });
    }

    // --- Fixed Störer Stacking optimiert ---
    function stackFixedStoerers() {
        const fixedStoererWrappers = document.querySelectorAll('.is-fixed.content--element.ce_rsce_stoerer');

        fixedStoererWrappers.forEach(wrapper => {
            const stoerers = Array.from(wrapper.querySelectorAll(':scope > .ce--stoerer.is-expandable'));
            
            if (stoerers.length <= 1) {
                stoerers.forEach(el => el.style.zIndex = '');
                return;
            }

            // Gap-Berechnung
            const gapStyle = getComputedStyle(wrapper).getPropertyValue('--stoerer-vertical-gap') || '0.75rem';
            const gapValuePx = getPixelValue(gapStyle.trim(), wrapper);

            // Sortierung basierend auf Position
            stoerers.sort((a, b) => {
                const getTopValue = (el) => {
                    if (!el.dataset.initialTopPx) {
                        const styleTop = el.style.top || getComputedStyle(el).top;
                        el.dataset.originalStyleTop = styleTop;
                        el.dataset.initialTopPx = getPixelValue(styleTop, el);
                    }
                    return parseFloat(el.dataset.initialTopPx);
                };
                return getTopValue(a) - getTopValue(b);
            });

            // Positionierung
            let firstStoererOriginalTop = "0px";
            let firstStoererTriggerHeight = 0;

            stoerers.forEach((element, index) => {
                element.style.zIndex = stoerers.length - index;

                if (index === 0) {
                    firstStoererOriginalTop = element.dataset.originalStyleTop || "0px";
                    element.style.top = firstStoererOriginalTop;
                    
                    const trigger = element.querySelector('.stoerer-trigger');
                    firstStoererTriggerHeight = trigger ? trigger.offsetHeight : 0;
                } else {
                    element.style.top = `calc(${firstStoererOriginalTop} + ${firstStoererTriggerHeight}px + ${gapValuePx}px)`;
                }
            });
        });
    }

    // --- Edge-Position Check optimiert ---
    function checkEdgePositioning() {
        const edgeStoerers = document.querySelectorAll('.ce--stoerer[style*="left:"], .ce--stoerer[style*="right:"]');
        
        edgeStoerers.forEach(element => {
            element.classList.remove('is-flush-left', 'is-flush-right');
            
            const rect = element.getBoundingClientRect();
            const tolerance = 2;

            if (rect.left <= tolerance) {
                element.classList.add('is-flush-left');
            } else if (rect.right >= window.innerWidth - tolerance) {
                element.classList.add('is-flush-right');
            }
        });  
    }

    // --- Hauptinitialisierung ---
    function initializeAllStoerers() {
        const allStoerers = document.querySelectorAll('.ce--stoerer.is-expandable');
        const stoererContainers = document.querySelectorAll('.content--element.ce_rsce_stoerer');

        allStoerers.forEach(stoerer => {
            const state = getStoererState(stoerer);

            // Event-Listener für Trigger-Klick
            const trigger = stoerer.querySelector('.stoerer-trigger');
            if (trigger && !trigger.dataset.listenerAttached) {
                addSafeEventListener(trigger, 'click', (event) => {
                    state.setInteracting(true);

                    if (state.isExpanded) {
                        state.collapse();
                        state.deactivate();
                    } else {
                        state.expand();
                        state.activate();
                    }

                    // Remove interaction flag
                    setTimeout(() => state.setInteracting(false), 50);
                });
                trigger.dataset.listenerAttached = 'true';
            }

            // Hover-Events (nur Desktop)
            if (!stoerer.dataset.hoverListenerAttached) {
                addSafeEventListener(stoerer, 'mouseenter', () => {
                    if (window.innerWidth < 768 || state.isInteracting) return;
                    if (!state.isActive) {
                        state.expand();
                    }
                });

                addSafeEventListener(stoerer, 'mouseleave', () => {
                    if (window.innerWidth < 768 || state.isInteracting) return;
                    if (!state.isActive) { 
                        state.collapse();
                    } 
                });
                stoerer.dataset.hoverListenerAttached = 'true';
            }
        });

        stackFixedStoerers();
        checkEdgePositioning();

        // Ready-Klasse hinzufügen
        stoererContainers.forEach(container => container.classList.add('js-stoerer-ready'));
    }

    // --- Event Listeners ---
    let resizeTimer;

    // Initialer Aufruf
    initializeAllStoerers();

    // Resize (debounced)
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(initializeAllStoerers, 250);
    });

    // Klick außerhalb (vereinfacht)
    addSafeEventListener(document, 'click', function (event) {
        const clickedStoerer = event.target.closest('.ce--stoerer.is-expandable');

        stoererStates.forEach((state, element) => {
            if (element !== clickedStoerer && state.isExpanded) {
                state.collapse();
                state.deactivate();
            }
        });

        // Mobile: Schließe alle wenn außerhalb geklickt
        if (window.innerWidth < 768 && !clickedStoerer) {
            closeAllMobileStates();
        }
    });

    // Custom Event für dynamische Störer
    document.body.addEventListener('stoererAdded', function(e) {
        console.log('Event stoererAdded empfangen.');
        requestAnimationFrame(() => {
            initializeAllStoerers();
        });
    });

});  