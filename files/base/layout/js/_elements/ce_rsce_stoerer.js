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
            
            // Force blur auf allen fokussierbaren Elementen im Störer
            const focusableElements = this.element.querySelectorAll('a, button, input, textarea, select, [tabindex]');
            focusableElements.forEach(el => el.blur());
            
            // CSS-Hover States explizit zurücksetzen (für alle Geräte)
            this.element.classList.add('force-collapsed');
            // Nach kurzer Zeit wieder entfernen, damit normale Hover-Logic funktioniert
            setTimeout(() => {
                this.element.classList.remove('force-collapsed');
            }, 100);
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

    // --- Touch-Device Detection ---
    function isTouchDevice() {
        return ('ontouchstart' in window) || 
               (navigator.maxTouchPoints > 0) || 
               (navigator.msMaxTouchPoints > 0) ||
               (window.innerWidth < 768);
    }

    // --- Mobile/Touch-Handler vereinfacht ---
    function handleTouchClick(event, stoerer) {
        const state = getStoererState(stoerer);
        
        // Touch-Geräte: Immer Toggle-Verhalten
        if (isTouchDevice()) {
            // Wenn bereits expandiert/aktiv: Schließen
            if (state.isActive || state.isExpanded) {
                state.deactivate();
                state.collapse();
                return true; // Event wurde behandelt
            } 
            // Wenn geschlossen: Öffnen
            else {
                event.preventDefault(); // Verhindert Link-Navigation nur beim ersten Klick
                closeAllTouchStates(); // Schließe andere
                state.activate();
                state.expand();
                return true; // Event wurde behandelt
            }
        }
        
        return false; // Event nicht behandelt, Desktop-Logic verwenden
    }

    function closeAllTouchStates() {
        stoererStates.forEach(state => {
            state.deactivate();
            state.collapse();
        });
    }

    // --- Fixed Störer Stacking optimiert ---
    function stackFixedStoerers() {
        const fixedStoererWrappers = document.querySelectorAll('.is-fixed.content--element.ce_rsce_stoerer');

        fixedStoererWrappers.forEach(wrapper => {
            const stoerers = Array.from(wrapper.querySelectorAll(':scope > .ce--stoerer.is-expandable'));
            

            if (stoerers.length <= 1) {
                stoerers.forEach(el => {
                    el.style.zIndex = '';
                    // Reset auch die ursprüngliche Position für dynamische Störer
                    if (el.dataset.originalStyleTop && el.dataset.originalStyleTop !== el.style.top) {
                        el.style.top = el.dataset.originalStyleTop;
                    }
                });
                return;
            }

            // Gap-Berechnung (etwas größer für bessere Sichtbarkeit)
            const gapStyle = getComputedStyle(wrapper).getPropertyValue('--stoerer-vertical-gap') || '0.75rem';
            const gapValuePx = Math.max(getPixelValue(gapStyle.trim(), wrapper), 8); // Mindestens 8px Gap

            // Sortierung basierend auf Position - dynamische Störer immer nach festen
            stoerers.sort((a, b) => {
                const aIsDynamic = a.classList.contains('article-info-nav-stoerer') || a.id === 'stoerer-130-1';
                const bIsDynamic = b.classList.contains('article-info-nav-stoerer') || b.id === 'stoerer-130-1';
                
                // Dynamische Störer immer nach festen Störern
                if (aIsDynamic && !bIsDynamic) return 1;  // a nach b
                if (!aIsDynamic && bIsDynamic) return -1; // a vor b
                if (aIsDynamic && bIsDynamic) return 0;   // beide dynamisch, Reihenfolge egal
                
                // Beide sind feste Störer - normale Positionssortierung
                const getTopValue = (el) => {
                    if (!el.dataset.initialTopPx) {
                        // Priorität: CSS Custom Property > style.top > getComputedStyle
                        let styleTop = el.style.top;
                        
                        // ZUERST CSS Custom Properties prüfen (wichtiger als style.top)
                        let customTop = getComputedStyle(el).getPropertyValue('--stoerer-page-top').trim();
                        
                        // Mobile-spezifische Positionierung bei kleinen Bildschirmen
                        if (window.innerWidth < 768) {
                            const mobileTop = getComputedStyle(el).getPropertyValue('--stoerer-top-mobile').trim();
                            if (mobileTop && mobileTop !== '') {
                                customTop = mobileTop;
                            }
                        }
                        
                        if (customTop && customTop !== '') {
                            styleTop = customTop;
                            console.log('Verwende CSS Custom Property für Störer:', el.id, 'Wert:', customTop);
                        } else if (!styleTop || styleTop === 'auto' || styleTop === '' || styleTop === '0px') {
                            // Fallback: getComputedStyle, aber nur wenn es einen sinnvollen Wert liefert
                            const computedTop = getComputedStyle(el).top;
                            if (computedTop && computedTop !== 'auto' && computedTop.includes('px') && parseInt(computedTop) < 5000) {
                                styleTop = computedTop;
                            } else {
                                // Default-Wert wenn nichts anderes funktioniert
                                styleTop = '0px';
                            }
                        }
                        
                        el.dataset.originalStyleTop = styleTop;
                        el.dataset.initialTopPx = getPixelValue(styleTop, el);
                        
                        console.log('getTopValue für', el.id, 'styleTop:', styleTop, 'initialTopPx:', el.dataset.initialTopPx);
                        
                        // Debug-Info für problematische Werte
                        if (parseInt(el.dataset.initialTopPx) > 5000) {
                            console.warn('Störer mit sehr hohem top-Wert gefunden:', el, 'initialTopPx:', el.dataset.initialTopPx, 'originalStyleTop:', styleTop);
                        }
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
                    // Zuerst CSS Custom Properties prüfen (höchste Priorität)
                    let customTop = getComputedStyle(element).getPropertyValue('--stoerer-page-top').trim();
                    
                    // Mobile-spezifische Positionierung bei kleinen Bildschirmen
                    if (window.innerWidth < 768) {
                        const mobileTop = getComputedStyle(element).getPropertyValue('--stoerer-top-mobile').trim();
                        if (mobileTop && mobileTop !== '') {
                            customTop = mobileTop;
                        }
                    }
                    
                    if (customTop && customTop !== '') {
                        firstStoererOriginalTop = customTop;
                        // Setze die korrekte Position wenn sie fehlt
                        if (!element.style.top || element.style.top === '0px' || element.style.top === 'auto') {
                            element.style.top = firstStoererOriginalTop;
                        }
                    } else if (element.style.top && element.style.top !== '0px' && element.style.top !== 'auto') {
                        // Fallback: verwende existierende style.top
                        firstStoererOriginalTop = element.style.top;
                    } else if (element.dataset.originalStyleTop) {
                        // Fallback: verwende originalStyleTop
                        firstStoererOriginalTop = element.dataset.originalStyleTop;
                    }
                    
                    console.log('Erster Störer behält Position:', element.id, 'Position:', element.style.top);
                    console.log('firstStoererOriginalTop gesetzt auf:', firstStoererOriginalTop);
                    
                    const trigger = element.querySelector('.stoerer-trigger');
                    firstStoererTriggerHeight = trigger ? trigger.offsetHeight : 0;
                } else {
                    // Debug-Info für Höhenberechnung
                    console.log('Trigger-Höhe für ersten Störer:', firstStoererTriggerHeight, 'Gap:', gapValuePx);
                    
                    // Für den zweiten (und weitere) Störer: Position unter dem ersten
                    if (index === 1) {
                        const totalOffsetPx = firstStoererTriggerHeight + gapValuePx;
                        // KORRIGIERTE calc() Syntax: Basis-Position ZUERST, dann Offset
                        element.style.top = `calc(${firstStoererOriginalTop} + ${totalOffsetPx}px)`;
                    } else {
                        // Für weitere Störer: Berechne Position basierend auf vorherigen
                        let totalOffset = firstStoererTriggerHeight;
                        for (let i = 1; i < index; i++) {
                            const prevTrigger = stoerers[i].querySelector('.stoerer-trigger');
                            totalOffset += (prevTrigger ? prevTrigger.offsetHeight : 40) + gapValuePx;
                        }
                        const finalOffsetPx = totalOffset + gapValuePx;
                        // KORRIGIERTE calc() Syntax: Basis-Position ZUERST, dann Offset
                        element.style.top = `calc(${firstStoererOriginalTop} + ${finalOffsetPx}px)`;
                    }
                    
                    // Debug-Info für dynamische Störer
                    console.log('Störer positioniert:', element.id, 'Position:', element.style.top, 'Index:', index, 'basierend auf firstStoererOriginalTop:', firstStoererOriginalTop);
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
                    // Prüfe ob der Störer einen Content-Link hat und NICHT expandierbar ist
                    const hasContentLink = stoerer.parentElement.querySelector('a.stoerer-inner-wrapper');
                    const isExpandable = stoerer.classList.contains('is-expandable');
                    
                    if (hasContentLink && !isExpandable) {
                        // Trigger den Link-Klick auf den stoerer-inner-wrapper
                        hasContentLink.click();
                        return;
                    }
                    
                    // Touch-Handling hat Vorrang
                    if (handleTouchClick(event, stoerer)) {
                        return; // Touch-Handler hat das Event behandelt
                    }

                    // Desktop-Verhalten: Toggle
                    state.setInteracting(true);

                    if (state.isActive || state.isExpanded) {
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

            // Hover-Events (nur echte Desktop-Geräte)
            if (!stoerer.dataset.hoverListenerAttached) {
                addSafeEventListener(stoerer, 'mouseenter', () => {
                    if (isTouchDevice() || state.isInteracting || state.isActive) return;
                    state.expand();
                });

                addSafeEventListener(stoerer, 'mouseleave', () => {
                    if (isTouchDevice() || state.isInteracting || state.isActive) return;
                    state.collapse();
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
            if (element !== clickedStoerer) {
                // Vereinfacht: Alle States zurücksetzen, unabhängig vom Gerät
                if (state.isExpanded || state.isActive) {
                    state.collapse();
                    state.deactivate();
                }
            }
        });
    });

    // Custom Event für dynamische Störer
    document.body.addEventListener('stoererAdded', function(e) {
        console.log('Event stoererAdded empfangen.');
        requestAnimationFrame(() => {
            // Zusätzliche Verzögerung für dynamische Störer aus theme.js
            setTimeout(() => {
                initializeAllStoerers();
                // Force-Update für Stacking nach dynamischer Erstellung
                setTimeout(() => {
                    stackFixedStoerers();
                    checkEdgePositioning();
                }, 50);
            }, 100);
        });
    });

});  