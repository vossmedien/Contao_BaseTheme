import {scrollToTop} from "./smoothScrolling.js";
import {setupFunctions} from "./cookieManager.js";
import {adjustPullElements} from "./marginPaddingAdjustments.js";
//import {addPlaceholders} from "./floatingLabels.js";
import {
    changeAnchorLinks,
    changeNavLinksAfterLoad,
} from "./navigationHandling.js";
import {initVenoBox, initVideoLightbox, initImageLightbox} from "./lightboxHandling.js";

const scrollFunctions = [];
const loadFunctions = [];
const touchMoveFunctions = [];
const DomLoadFunctions = [];
const ResizeFunctions = [];

// Cache häufig verwendete DOM-Elemente
const cachedElements = {
    form: document.querySelector(".ce_form form"),
    mobileNavElement: document.querySelector("#mobileNav"),
    triggerElement: document.querySelector('a[href="#mobileNav"]'),
    body: document.querySelector('body'),
};

if (cachedElements.form) {
    cachedElements.form.addEventListener("submit", (e) => {
       // setTimeout(addPlaceholders, 250);
    });
}

const initMobileNav = () => {
    const {mobileNavElement, triggerElement, body} = cachedElements;

    if (mobileNavElement && triggerElement) {
        const menu = new MmenuLight(mobileNavElement);

        const navigator = menu.navigation({
            theme: "dark"
        });
        const drawer = menu.offcanvas({
            position: 'right'
        });

        triggerElement.addEventListener('click', (evnt) => {
            evnt.preventDefault();
            body.classList.contains("mm-ocd-opened") ? drawer.close() : drawer.open();
        });

        mobileNavElement.querySelectorAll('a').forEach(item => {
            item.addEventListener('click', () => {
                drawer.close();
            });
        });
    }
};

// Optimierte Animationsinitialisierung
const initAnimations = () => {
    const CONFIG = {
        mobile: {
            breakpoint: 768,
            rootMargin: '0px 0px -15% 0px',
        },
        desktop: {
            rootMargin: '0px 0px -10% 0px',
        },
        animation: {
            class: 'animate__animated',
            baseDelay: 0.1,
            increment: 0.15,
            reducedMotionClass: 'fade-in-gentle' // CSS-Klasse für reduzierte Bewegung
        }
    };

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let animatedElements = new Set();
    let observer;
    let animationDuration = '1s'; // Standardwert, wird überschrieben

    // Liest die Animationsdauer einmalig
    const readAnimationDuration = () => {
        try {
            animationDuration = getComputedStyle(document.documentElement).getPropertyValue('--animate-duration').trim() || '1s';
        } catch (e) {
            console.warn('Could not read --animate-duration, using default 1s.', e);
            animationDuration = '1s';
        }
    };

    // Bereitet Elemente für die Animation vor
    const initAnimateElements = () => {
        document.querySelectorAll('[class*="animate__"]:not([data-animation])').forEach(element => {
            if (element.getAttribute('data-animation') === 'no-animation') return;

            const animateClasses = Array.from(element.classList)
                .filter(cls => cls.startsWith('animate__') && cls !== CONFIG.animation.class);

            if (animateClasses.length > 0) {
                const animateClassString = animateClasses.join(' ');
                element.classList.remove(...animateClasses, CONFIG.animation.class);
                element.setAttribute('data-animation', animateClassString);
            }
        });
    };

    // Führt die Animation für ein Element aus
    const animateElement = (element, indexInGroup) => {
        const animationValue = element.getAttribute('data-animation');
        if (!element || animatedElements.has(element) || !animationValue || animationValue === 'no-animation') {
            return;
        }

        animatedElements.add(element);
        observer?.unobserve(element); // Stoppe Beobachtung nach erster Animation

        // Wenn reduzierte Bewegung bevorzugt wird, nur einfache Klasse hinzufügen
        if (prefersReducedMotion) {
            element.classList.add(CONFIG.animation.reducedMotionClass);
            // Nachfahren direkt "animieren" (falls sie auch nur die reduzierte Klasse bekommen)
            triggerDescendantAnimations(element);
            return;
        }

        element.style.animationDuration = animationDuration;
        const delay = CONFIG.animation.baseDelay + indexInGroup * CONFIG.animation.increment;
        element.style.animationDelay = `${delay}s`;

        requestAnimationFrame(() => {
            element.classList.add(...animationValue.split(' '), CONFIG.animation.class);

            element.addEventListener('animationend', () => {
                triggerDescendantAnimations(element);
            }, { once: true });
        });
    };

    // Hilfsfunktion zur Berechnung des Animationsindexes relativ zu animierbaren Geschwistern
    const getAnimationIndex = (element) => {
        const parentContainer = element.parentElement;
        if (!parentContainer) return 0; // Fallback

        // Finde alle *animierbaren* Geschwister im direkten Parent
        const animatableSiblings = Array.from(parentContainer.children)
            .filter(child => {
                const anim = child.getAttribute('data-animation');
                return anim && anim !== 'no-animation';
            });

        const indexInGroup = animatableSiblings.findIndex(el => el === element);
        return Math.max(0, indexInGroup); // Sicherstellen, dass der Index >= 0 ist
    };

    // Löst Animationen für Nachfahren aus
    const triggerDescendantAnimations = (parentElement) => {
        const descendantsToAnimate = Array.from(parentElement.querySelectorAll('[data-animation]:not([data-animation="no-animation"])'))
            .filter(descendant => !animatedElements.has(descendant)); // Nur noch nicht animierte Elemente

        if (descendantsToAnimate.length > 0) {
            // Sortiere potenzielle Nachfahren nach DOM-Reihenfolge, um Konsistenz zu gewährleisten
            descendantsToAnimate.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);

            // Gehe die sortierten Nachfahren durch
            descendantsToAnimate.forEach((descendant) => {
                // Finde den nächsten animierenden Parent
                const closestAnimatingParent = descendant.parentElement?.closest('[data-animation]:not([data-animation="no-animation"])');

                // Ein Nachfahre wird durch diese Funktion nur animiert, wenn sein nächster animierender Parent
                // entweder nicht existiert ODER der Parent ist, dessen Animation gerade geendet hat (parentElement).
                // Dies verhindert, dass tiefere Kinder zu früh animiert werden, wenn ihr direkter Parent noch animieren muss.
                if (!closestAnimatingParent || closestAnimatingParent === parentElement) {
                    // Prüfe sicherheitshalber erneut, ob es nicht doch schon animiert wurde (z.B. durch Observer)
                    if (!animatedElements.has(descendant)) {
                        const index = getAnimationIndex(descendant); // Korrekten Index holen
                        animateElement(descendant, index);
                    }
                }
            });
        }
    };

    // Richtet den IntersectionObserver ein
    const setupObserver = () => {
        // rootMargin basierend auf aktueller Breite bestimmen
        const rootMargin = window.innerWidth <= CONFIG.mobile.breakpoint ? CONFIG.mobile.rootMargin : CONFIG.desktop.rootMargin;

        observer = new IntersectionObserver((entries) => {
            const elementsToPotentiallyAnimate = new Map();

            entries.forEach(entry => {
                const element = entry.target;
                const animationValue = element.getAttribute('data-animation');

                // Prüfen: Ist sichtbar? Hat Animation? Wurde noch nicht animiert?
                if (entry.isIntersecting && animationValue && animationValue !== 'no-animation' && !animatedElements.has(element)) {
                    // Prüfen: Hat das Element einen animierenden Parent, der *noch nicht* animiert ist?
                    const closestAnimatingParent = element.parentElement?.closest('[data-animation]:not([data-animation="no-animation"])');

                    // Nur animieren, wenn kein animierender Parent ODER der Parent bereits animiert ist.
                    if (!closestAnimatingParent || animatedElements.has(closestAnimatingParent)) {
                        const indexInGroup = getAnimationIndex(element); // Korrekten Index holen

                        // Nur zur Map hinzufügen, wenn noch nicht vorhanden
                        if (!elementsToPotentiallyAnimate.has(element)) {
                            elementsToPotentiallyAnimate.set(element, indexInGroup);
                        }
                    }
                } else if (animationValue === 'no-animation') {
                    observer?.unobserve(element); // Elemente ohne Animation nicht beobachten
                }
            });

            if (elementsToPotentiallyAnimate.size === 0) return;

            // Sortiere Elemente nach ihrer DOM-Reihenfolge
            const sortedElements = Array.from(elementsToPotentiallyAnimate.keys()).sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);

            sortedElements.forEach(element => {
                const index = elementsToPotentiallyAnimate.get(element);
                animateElement(element, index);
            });

        }, {
            threshold: 0.01, // Element muss leicht sichtbar sein
            rootMargin
        });
    };

    // Fügt Elemente zum Observer hinzu oder entfernt sie
    const handleElements = () => {
        if (!observer) return; // Sicherstellen, dass der Observer existiert

        document.querySelectorAll('[data-animation]').forEach(element => {
            const animationValue = element.getAttribute('data-animation');
            if (animationValue === 'no-animation' || animatedElements.has(element)) {
                 observer.unobserve(element); // Nicht (mehr) beobachten
            } else if (!prefersReducedMotion || element.classList.contains(CONFIG.animation.reducedMotionClass) === false) {
                 // Beobachten, wenn Animation gewünscht/aktiv und noch nicht animiert
                 observer.observe(element);
            } else {
                // Wenn reduced motion aktiv ist und die Klasse schon hat, nicht mehr beobachten
                observer.unobserve(element);
            }
        });
    };


    // --- Initialisierung und Event Listener ---
    try {
        readAnimationDuration(); // Einmalig Dauer lesen
        initAnimateElements();  // Klassen vorbereiten

        // Nur initialisieren, wenn keine reduzierte Bewegung gewünscht ist oder wenn wir eine Fallback-Klasse haben
        if (!prefersReducedMotion || CONFIG.animation.reducedMotionClass) {
            setupObserver();        // Observer erstellen
            handleElements();       // Elemente initial beobachten

            // Resize Handling mit Debounce und Observer-Neuerstellung
            let resizeTimeout;
            let lastWindowWidth = window.innerWidth; // Merken der Breite für Breakpoint-Check

            const handleResize = () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    const currentWindowWidth = window.innerWidth;
                    // Prüfen, ob sich die Fensterbreite über den Breakpoint geändert hat
                    const breakpointCrossed = (lastWindowWidth <= CONFIG.mobile.breakpoint && currentWindowWidth > CONFIG.mobile.breakpoint) ||
                                              (lastWindowWidth > CONFIG.mobile.breakpoint && currentWindowWidth <= CONFIG.mobile.breakpoint);

                    // Nur neu initialisieren, wenn der Breakpoint überschritten wurde
                    if (breakpointCrossed && observer) {
                        observer.disconnect(); // Alten Observer trennen
                        setupObserver();       // Neuen Observer mit korrektem rootMargin erstellen
                        handleElements();      // Elemente neu hinzufügen
                    } else if (observer) {
                         // Wenn Breakpoint nicht überschritten, reicht evtl. handleElements,
                         // falls Elemente durch Resize sichtbar/unsichtbar wurden (optional, aktuell nicht implementiert)
                         // handleElements(); // Könnte man machen, aber setupObserver deckt das meiste ab.
                    }
                    lastWindowWidth = currentWindowWidth; // Aktuelle Breite merken
                }, 250); // Debounce-Zeit
            };

            // Alten Resize Handler entfernen (falls vorhanden) und neuen hinzufügen
            const oldResizeHandlerIndex = ResizeFunctions.findIndex(f => f.name === 'handleResize');
             if (oldResizeHandlerIndex > -1) ResizeFunctions.splice(oldResizeHandlerIndex, 1);
            ResizeFunctions.push(handleResize);

        } else {
             // Wenn reduzierte Bewegung bevorzugt wird und keine Fallback-Klasse definiert ist,
             // können wir hier optional alle data-animation Attribute entfernen oder auf no-animation setzen.
             console.log("Reduced motion preferred, animations disabled.");
        }

    } catch (error) {
        console.error('Animation initialization failed:', error);
    }

    // Cleanup-Funktion
    return () => {
        observer?.disconnect();
        const resizeIndex = ResizeFunctions.findIndex(f => f.name === 'handleResize');
        if (resizeIndex > -1) ResizeFunctions.splice(resizeIndex, 1);
        animatedElements.clear();
    };
};


// Optimierte Bildrotation
const rotateImage = () => {
    const images = document.querySelectorAll('.rotateImage');
    if (images.length === 0) return; // Frühzeitig beenden, wenn keine Bilder vorhanden sind

    let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
    let ticking = false; // Für requestAnimationFrame Throttling

    // Die Funktion, die tatsächlich die Rotation durchführt
    const performRotation = () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        // Nur fortfahren, wenn tatsächlich gescrollt wurde
        if (scrollTop === lastScrollTop) {
            ticking = false;
            return;
        }

        const scrollDirection = scrollTop > lastScrollTop ? 0.5 : -0.5; // Reduzierte Rotationsgeschwindigkeit

        images.forEach(image => {
            // Nur rotieren, wenn das Bild im oder nahe am Viewport ist (Performance)
            const rect = image.getBoundingClientRect();
            const isInView = rect.bottom >= -window.innerHeight && rect.top <= 2 * window.innerHeight; // Großzügiger Puffer

            if (isInView) {
                 // Initialisiere Rotation, falls nicht vorhanden
                if (image.rotation === undefined) {
                    image.rotation = 0;
                }
                image.rotation += scrollDirection;
                image.style.transform = `rotate(${image.rotation}deg)`;
            }
        });

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        ticking = false; // Freigabe für nächsten Frame
    };

    // Scroll-Event-Handler (gedrosselt)
    const onScroll = () => {
        if (!ticking) {
            window.requestAnimationFrame(performRotation);
            ticking = true;
        }
    };

    // IntersectionObserver, um den Scroll-Listener nur bei Bedarf zu aktivieren/deaktivieren
    const observer = new IntersectionObserver(entries => {
        let anyImageVisible = false;
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                anyImageVisible = true;
                // Initialisiere Rotation für sichtbare Bilder
                if (entry.target.rotation === undefined) entry.target.rotation = 0;
            }
        });

        if (anyImageVisible) {
            // Listener hinzufügen (wird intern nur einmal hinzugefügt)
            window.addEventListener('scroll', onScroll, { passive: true });
             // Einmal initiale Rotation ausführen, falls schon gescrollt wurde
             if(!ticking) {
                 window.requestAnimationFrame(performRotation);
                 ticking = true;
             }
        } else {
            // Listener entfernen, wenn kein beobachtetes Bild mehr sichtbar ist
            window.removeEventListener('scroll', onScroll);
        }
    }, {
        // Beobachte großzügiger als nur den Viewport, um Rotation frühzeitig zu starten/stoppen
        rootMargin: '100% 0px 100% 0px',
        threshold: 0 // Sobald ein Pixel sichtbar/unsichtbar wird
    });

    // Füge alle Bilder zum Observer hinzu
    images.forEach(image => observer.observe(image));

     // Optional: Cleanup-Funktion zurückgeben, falls benötigt
     return () => {
         observer.disconnect();
         window.removeEventListener('scroll', onScroll);
     };
};

const addStylesToArticlesWithBg = () => {
    const articleLast = document.querySelector(".mod_article:last-child");
    const articleFirst = document.querySelector(".mod_article:first-child");

    if (articleLast?.querySelector("style.with-bg")) {
        articleLast.style.padding = "var(--with-body-bg-spacing) 0 calc(var(--with-body-bg-spacing) * 2) 0";
        articleLast.style.marginBottom = "calc(-1 * var(--with-body-bg-spacing))";
    }

    if (articleFirst?.querySelector("style.with-bg")) {
        articleFirst.style.padding = "calc(var(--with-body-bg-spacing) * 2) 0 var(--with-body-bg-spacing) 0";
    }
};

const initializePopovers = () => {
    const popoverTriggerList = [...document.querySelectorAll('[data-bs-toggle="popover"]')];
    popoverTriggerList.forEach((popoverTriggerEl) => {
        new bootstrap.Popover(popoverTriggerEl);
    });
};

const initializeTooltips = () => {
    const tooltipTriggerList = [...document.querySelectorAll('[data-bs-toggle="tooltip"]')];
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
};

const loadSearchParams = () => {
    const urlParams = new URLSearchParams(window.location.search);

    for (const [parameterName, parameterValue] of urlParams) {
        const inputField = document.querySelector(`input[name="${parameterName}"]`);
        if (inputField) {
            inputField.value = parameterValue;
        }
    }
};

// Füge die initialisierenden Funktionen zum DomLoadFunctions Array hinzu
DomLoadFunctions.push(
    initMobileNav,
    initAnimations, // Verwendet die optimierte Funktion
    rotateImage,    // Verwendet die optimierte Funktion
    addStylesToArticlesWithBg,
    initializePopovers,
    initializeTooltips,
    loadSearchParams,
    initVenoBox,
    initVideoLightbox,
    initImageLightbox,
    changeNavLinksAfterLoad,
    scrollToTop,
    setupFunctions,
    adjustPullElements,
    //addPlaceholders
);

scrollFunctions.push(changeAnchorLinks);

// Stelle sicher, dass adjustPullElements im ResizeFunctions Array ist
const adjustPullIndex = ResizeFunctions.findIndex(f => f.name === 'adjustPullElements');
if (adjustPullIndex === -1) {
    ResizeFunctions.push(adjustPullElements);
}
// Hinweis: Der Resize-Handler für die Animationen wird jetzt dynamisch in initAnimations hinzugefügt/entfernt.

const executeFunctions = (functions) => {
    functions.forEach((func) => {
        try {
            const cleanup = func(); // Führe Funktion aus und fange evtl. Cleanup-Funktion auf
            // Optional: Cleanup-Funktionen sammeln und bei Bedarf ausführen (z.B. bei SPA-Navigation)
        } catch (error) {
            console.error(`Error executing function ${func.name || 'anonymous'}:`, error);
        }
    });
};

window.addEventListener("DOMContentLoaded", () => executeFunctions(DomLoadFunctions));
window.addEventListener("load", () => executeFunctions(loadFunctions));
// Scroll-, Touchmove- und Resize-Listener sind jetzt teilweise in den Modulen (initAnimations, rotateImage) selbst verwaltet
// window.addEventListener("scroll", () => executeFunctions(scrollFunctions)); // Nur noch für changeAnchorLinks
// window.addEventListener("touchmove", () => executeFunctions(touchMoveFunctions)); // Falls hier noch was ist
// window.addEventListener("resize", () => executeFunctions(ResizeFunctions)); // Nur noch für adjustPullElements und den Animations-Handler

// Zentralisierte Listener für globale Funktionen beibehalten
window.addEventListener("scroll", () => scrollFunctions.forEach(func => { try { func(); } catch (e) { console.error(`Error in scroll func ${func.name}:`, e); } }));
window.addEventListener("touchmove", () => touchMoveFunctions.forEach(func => { try { func(); } catch (e) { console.error(`Error in touchmove func ${func.name}:`, e); } }));
window.addEventListener("resize", () => ResizeFunctions.forEach(func => { try { func(); } catch (e) { console.error(`Error in resize func ${func.name}:`, e); } }));