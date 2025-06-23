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

        // Event Listener für Schließen-Button
        const closeButton = mobileNavElement.querySelector('.mmenu-close-btn');
        if (closeButton) {
            closeButton.addEventListener('click', (evnt) => {
                evnt.preventDefault();
                drawer.close();
            });
        }

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
            rootMargin: '0px 0px -5% 0px', // Leicht früher als vorher
        },
        desktop: {
            rootMargin: '0px 0px 0px 0px', // Startet sobald Element sichtbar wird
        },
        animation: {
            class: 'animate__animated',
            baseDelay: 0.05,     // Reduziert von 0.1
            increment: 0.1,      // Reduziert von 0.15
            reducedMotionClass: 'fade-in-gentle'
        }
    };

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const animatedElements = new Set();
    let observer;
    let animationDuration = '1s';

    // Cache für bessere Performance
    const elementCache = new Map();

    const readAnimationDuration = () => {
        try {
            animationDuration = getComputedStyle(document.documentElement)
                .getPropertyValue('--animate-duration').trim() || '1s';
        } catch (e) {
            console.warn('Could not read --animate-duration, using default 1s.', e);
        }
    };

    const prepareAnimationElements = () => {
        document.querySelectorAll('[class*="animate__"]:not([data-animation])').forEach((element, index) => {
            if (element.getAttribute('data-animation') === 'no-animation') return;

            const animateClasses = Array.from(element.classList)
                .filter(cls => cls.startsWith('animate__') && cls !== CONFIG.animation.class);

            if (animateClasses.length > 0) {
                const animationData = animateClasses.join(' ');
                element.classList.remove(...animateClasses, CONFIG.animation.class);
                element.setAttribute('data-animation', animationData);

                // Cache für Performance
                elementCache.set(element, {
                    animation: animationData,
                    originalIndex: index
                });
            }
        });
    };

    const animateElement = (element, delay = 0) => {
        if (!element || animatedElements.has(element)) return;

        const cachedData = elementCache.get(element);
        const animationValue = element.getAttribute('data-animation') || cachedData?.animation;

        if (!animationValue || animationValue === 'no-animation') return;

        animatedElements.add(element);
        observer?.unobserve(element);

        if (prefersReducedMotion) {
            element.classList.add(CONFIG.animation.reducedMotionClass);
            return;
        }

        element.style.animationDuration = animationDuration;
        element.style.animationDelay = `${delay}s`;

        requestAnimationFrame(() => {
            element.classList.add(...animationValue.split(' '), CONFIG.animation.class);
        });
    };

    const setupObserver = () => {
        const rootMargin = window.innerWidth <= CONFIG.mobile.breakpoint
            ? CONFIG.mobile.rootMargin
            : CONFIG.desktop.rootMargin;

        observer = new IntersectionObserver((entries) => {
            // Batch-Verarbeitung für bessere Performance
            const visibleElements = entries
                .filter(entry => {
                    // Für sehr große Elemente: Animation starten wenn mindestens ein Pixel sichtbar ist
                    // Für normale Elemente: Standard-Intersection verwenden
                    if (!entry.isIntersecting) return false;
                    if (animatedElements.has(entry.target)) return false;

                    const element = entry.target;
                    const elementHeight = element.getBoundingClientRect().height;
                    const viewportHeight = window.innerHeight;

                    // Bei Elementen die höher als 1.5x Viewport sind, bereits bei wenig Sichtbarkeit animieren
                    if (elementHeight > viewportHeight * 1.5) {
                        return entry.intersectionRatio > 0;
                    }

                    // Normale Elemente benötigen mindestens 10% Sichtbarkeit
                    return entry.intersectionRatio >= 0.1;
                })
                .map(entry => entry.target)
                .filter(element => {
                    const animation = element.getAttribute('data-animation');
                    return animation && animation !== 'no-animation';
                });

            if (visibleElements.length === 0) return;

            // Gruppiere Elemente nach Parent für Staggering
            const elementGroups = new Map();
            visibleElements.forEach(element => {
                const parent = element.parentElement;
                if (!elementGroups.has(parent)) {
                    elementGroups.set(parent, []);
                }
                elementGroups.get(parent).push(element);
            });

            // Animiere Gruppen mit Staggering
            elementGroups.forEach(group => {
                group.forEach((element, index) => {
                    const delay = CONFIG.animation.baseDelay + (index * CONFIG.animation.increment);
                    setTimeout(() => animateElement(element, delay), index * 50);
                });
            });

        }, {
            threshold: [0, 0.1, 0.25], // Mehrere Schwellenwerte für verschiedene Elementgrößen
            rootMargin
        });
    };

    const observeElements = () => {
        if (!observer) return;

        document.querySelectorAll('[data-animation]').forEach(element => {
            const animationValue = element.getAttribute('data-animation');
            if (animationValue && animationValue !== 'no-animation' && !animatedElements.has(element)) {
                observer.observe(element);
            }
        });
    };

    // Cleanup alter Resize Handler
    const cleanupOldHandlers = () => {
        const oldIndex = ResizeFunctions.findIndex(f => f.name === 'handleResize');
        if (oldIndex > -1) ResizeFunctions.splice(oldIndex, 1);
    };

    // Vereinfachter Resize Handler
    const handleResize = () => {
        const newRootMargin = window.innerWidth <= CONFIG.mobile.breakpoint
            ? CONFIG.mobile.rootMargin
            : CONFIG.desktop.rootMargin;

        if (observer) {
            observer.disconnect();
            setupObserver();
            observeElements();
        }
    };

    // Debounced Resize Handler
    let resizeTimeout;
    const debouncedResize = () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResize, 250);
    };

    // Initialisierung
    try {
        readAnimationDuration();
        prepareAnimationElements();

        if (!prefersReducedMotion || CONFIG.animation.reducedMotionClass) {
            setupObserver();
            observeElements();

            cleanupOldHandlers();
            ResizeFunctions.push(debouncedResize);
        }

    } catch (error) {
        console.error('Animation initialization failed:', error);
    }

    // Cleanup-Funktion
    return () => {
        observer?.disconnect();
        elementCache.clear();
        animatedElements.clear();

        const resizeIndex = ResizeFunctions.findIndex(f => f === debouncedResize);
        if (resizeIndex > -1) ResizeFunctions.splice(resizeIndex, 1);
    };
};


// Optimierte Bildrotation
const rotateImage = () => {
    const images = document.querySelectorAll('.rotateImage');
    if (images.length === 0) return;

    let lastScrollTop = window.pageYOffset;
    let ticking = false;
    let scrollListener = null;

    // Performance-optimierte Rotation
    const performRotation = () => {
        const scrollTop = window.pageYOffset;
        if (scrollTop === lastScrollTop) {
            ticking = false;
            return;
        }

        const scrollDirection = scrollTop > lastScrollTop ? 0.3 : -0.3; // Reduzierte Geschwindigkeit

        // Nur sichtbare Bilder rotieren
        images.forEach(image => {
            const rect = image.getBoundingClientRect();
            if (rect.bottom >= -100 && rect.top <= window.innerHeight + 100) {
                image.rotation = (image.rotation || 0) + scrollDirection;
                image.style.transform = `rotate(${image.rotation}deg)`;
            }
        });

        lastScrollTop = Math.max(0, scrollTop);
        ticking = false;
    };

    const onScroll = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(performRotation);
        }
    };

    // Simplified Observer - aktiviert/deaktiviert Scroll-Listener
    const observer = new IntersectionObserver(entries => {
        const hasVisibleImages = entries.some(entry => entry.isIntersecting);

        if (hasVisibleImages && !scrollListener) {
            scrollListener = onScroll;
            window.addEventListener('scroll', scrollListener, { passive: true });
        } else if (!hasVisibleImages && scrollListener) {
            window.removeEventListener('scroll', scrollListener);
            scrollListener = null;
        }
    }, {
        rootMargin: '50px',
        threshold: 0
    });

    images.forEach(image => {
        image.rotation = 0; // Initialisierung
        observer.observe(image);
    });

    return () => {
        observer.disconnect();
        if (scrollListener) {
            window.removeEventListener('scroll', scrollListener);
        }
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
// Event-Listener für globale Funktionen

// Zentralisierte Listener für globale Funktionen beibehalten
window.addEventListener("scroll", () => scrollFunctions.forEach(func => { try { func(); } catch (e) { console.error(`Error in scroll func ${func.name}:`, e); } }));
window.addEventListener("touchmove", () => touchMoveFunctions.forEach(func => { try { func(); } catch (e) { console.error(`Error in touchmove func ${func.name}:`, e); } }));
window.addEventListener("resize", () => ResizeFunctions.forEach(func => { try { func(); } catch (e) { console.error(`Error in resize func ${func.name}:`, e); } }));