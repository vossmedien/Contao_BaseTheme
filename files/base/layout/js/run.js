import {setSwitchingcardsHeight} from "./elementHeightAdjustments.js";
import {initializeSmoothScrolling, scrollToTop} from "./smoothScrolling.js";
import {setupFunctions} from "./cookieManager.js";
import {initializeMarginAdjustments} from "./marginPaddingAdjustments.js";
import {
    addBootstrapClasses,
    adjustTableResponsive,
} from "./classStyleManipulation.js";
import {addPlaceholders} from "./floatingLabels.js";
import {
    changeAnchorLinks,
    changeNavLinksAfterLoad,
} from "./navigationHandling.js";

const scrollFunctions = [];
const loadFunctions = [];
const touchMoveFunctions = [];
const DomLoadFunctions = [];
const ResizeFunctions = [];

const form = document.querySelector(".ce_form form");
if (form) {
    form.addEventListener("submit", function (e) {
        setTimeout(addPlaceholders, 250);
    });
}


const initMobileNav = () => {
    const mobileNavElement = document.querySelector("#mobileNav");
    const triggerElement = document.querySelector('a[href="#mobileNav"]');
    const body = document.querySelector('body');

    if (mobileNavElement && triggerElement) {
        const menu = new MmenuLight(mobileNavElement);

        const navigator = menu.navigation();
        const drawer = menu.offcanvas({
            position: 'right'  // Das Menü von rechts öffnen
        });


        triggerElement.addEventListener('click', (evnt) => {
            evnt.preventDefault();

            if (body.classList.contains("mm-ocd-opened")) {
                drawer.close();
            } else {
                drawer.open();
            }
        });

        // Event-Listener für alle Menüpunkte innerhalb der Navigation hinzufügen
        const menuItems = mobileNavElement.querySelectorAll('a');
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                drawer.close();
            });
        });
    }
}

DomLoadFunctions.push(initMobileNav);

const initAnimations = () => {
    // Sammle alle Elemente einmalig
    const elements = document.querySelectorAll('*:not(html):not([data-aos])[class*="animate__"], [data-aos]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const animateClass = element.getAttribute('data-aos') || Array.from(element.classList).find(cls => cls.startsWith('animate__'));

                if (animateClass) {
                    // Nutze requestAnimationFrame für flüssigere Animationen
                    requestAnimationFrame(() => {
                        element.classList.add(animateClass, 'animate__animated');
                    });

                    // Entferne den Observer nach der Animation
                    observer.unobserve(element);
                }
            }
        });
    }, {threshold: 0.1, rootMargin: '50px'}); // Niedrigerer Threshold und rootMargin für früheres Triggern

    elements.forEach(element => {
        if (element.classList.contains('animate__')) {
            const animateClass = Array.from(element.classList).find(cls => cls.startsWith('animate__'));
            element.classList.remove(animateClass);
            element.setAttribute('data-aos', animateClass);
        }
        observer.observe(element);
    });
}
DomLoadFunctions.push(initAnimations);


const initVenobox = () => {
    new VenoBox({
        selector: 'a[href*="webm"]', //Items selector
        infinigall: true, // Ermöglicht eine endlose Navigation durch die Galerie. Standardwert: false
        maxWidth: '80%', // Maximale Breite des Lightbox-Fensters. Standardwert: '100%'
        numeration: true, // Zeigt Nummerierung der aktuellen und Gesamtanzahl der Elemente in der Galerie an. Standardwert: false
        spinner: 'flow', //  'plane' | 'chase' | 'bounce' | 'wave' | 'pulse' | 'flow' | 'swing' | 'circle' | 'circle-fade' | 'grid' | 'fold | 'wander'
        initialScale: 0.9, // Anfangsgröße der Skalierungstransformation für Elemente. Standardwert: 0.9
        transitionSpeed: 200, // Übergangsgeschwindigkeit für eingehende Elemente in Millisekunden. Standardwert: 500
        fitView: true, // Passt Bilder an, um innerhalb der Höhe des Viewports zu passen. Standardwert: true
    });


    const lightboxLinks = document.querySelectorAll('a[href*="webm"],a[href*="mp4"]');

    lightboxLinks.forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && (href.endsWith('.mp4') || href.endsWith('.webm'))) {
            link.setAttribute('data-autoplay', 'true');
            link.setAttribute('data-vbtype', 'video');
            link.setAttribute('data-ratio', 'full');
        }
    });
}
DomLoadFunctions.push(initVenobox);

const rotateImage = () => {
    const images = document.querySelectorAll('.rotateImage');
    let lastScrollTop = 0;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                window.addEventListener('scroll', rotateImages);
            } else if (entry.intersectionRatio === 0) {
                window.removeEventListener('scroll', rotateImages);
            }
        });
    }, {
        threshold: [0] // Beobachte, wenn das Bild vollständig außerhalb des sichtbaren Bereichs ist
    });

    images.forEach(image => {
        image.rotation = 0; // Initialisiere die Rotation für jedes Bild
        observer.observe(image);
    });

    function rotateImages() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollDirection = scrollTop > lastScrollTop ? .5 : -.5; // Bestimme die Scroll-Richtung

        images.forEach(image => {
            image.rotation += scrollDirection;
            image.style.transform = `rotate(${image.rotation}deg)`;
        });

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // Für Mobile oder negativen Wert
    }
};


DomLoadFunctions.push(rotateImage);


const addStylesToArticlesWithBg = () => {
    // Wählen Sie das letzte .mod_article Element aus
    var articleLast = document.querySelector(".mod_article:last-child");
    // Wählen Sie das erste .mod_article Element aus
    var articleFirst = document.querySelector(".mod_article:first-child");

    // Prüfen Sie, ob das letzte .mod_article Element existiert und ein style.with-bg Kind hat
    if (articleLast && articleLast.querySelector("style.with-bg")) {
        articleLast.style.padding =
            "var(--with-body-bg-spacing) 0 calc(var(--with-body-bg-spacing) * 2) 0";
        articleLast.style.marginBottom = "calc(-1 * var(--with-body-bg-spacing))";
    }

    // Prüfen Sie, ob das erste .mod_article Element existiert und ein style.with-bg Kind hat
    if (articleFirst && articleFirst.querySelector("style.with-bg")) {
        //articleFirst.style.padding =
        ("calc(var(--with-body-bg-spacing) * 2) 0 var(--with-body-bg-spacing) 0");
        //articleFirst.style.marginTop = "calc(-1 * var(--with-body-bg-spacing))";
    }
};
DomLoadFunctions.push(addStylesToArticlesWithBg);


const initializePopovers = () => {
    const popoverTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="popover"]'),
    ];
    popoverTriggerList.forEach((popoverTriggerEl) => {
        new bootstrap.Popover(popoverTriggerEl);
    });
};
DomLoadFunctions.push(initializePopovers);


const initializeTooltips = () => {
    const tooltipTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="tooltip"]'),
    ];
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
};
DomLoadFunctions.push(initializeTooltips);


const loadSearchParams = () => {
    /** EXTRACT URL PARAMETERS  **/
        // Alle Parameter aus der URL extrahieren
    var urlParams = new URLSearchParams(window.location.search);

    // Alle Parameter-Namen aus der URL erhalten
    var parameterNames = urlParams.keys();

    // Iteriere über alle Parameter-Namen und fülle die entsprechenden Eingabefelder
    for (var parameterName of parameterNames) {
        var fieldValue = getURLParameter(parameterName);
        var inputField = document.querySelector(
            'input[name="' + parameterName + '"]'
        );
        if (inputField) {
            inputField.value = fieldValue;
        }
    }
};
DomLoadFunctions.push(loadSearchParams);


DomLoadFunctions.push(setSwitchingcardsHeight);
DomLoadFunctions.push(initializeSmoothScrolling);
DomLoadFunctions.push(changeNavLinksAfterLoad);
DomLoadFunctions.push(scrollToTop);
DomLoadFunctions.push(setupFunctions);
DomLoadFunctions.push(initializeMarginAdjustments);
DomLoadFunctions.push(addBootstrapClasses);
DomLoadFunctions.push(adjustTableResponsive);
DomLoadFunctions.push(addPlaceholders);


scrollFunctions.push(changeAnchorLinks);


function executeScrollFunctions() {
    scrollFunctions.forEach((func) => func());
}

function executeLoadFunctions() {
    loadFunctions.forEach((func) => func());
}

function executeDomLoadFunctions() {
    DomLoadFunctions.forEach((func) => func());
}

function executeTouchFunctions() {
    touchMoveFunctions.forEach((func) => func());
}

function executeResizeFunctions() {
    ResizeFunctions.forEach((func) => func());
}

//Functions from run.js
window.addEventListener("DOMContentLoaded", executeDomLoadFunctions);
window.addEventListener("load", executeLoadFunctions);
window.addEventListener("scroll", executeScrollFunctions);
window.addEventListener("touchmove", executeTouchFunctions);
window.addEventListener("resize", executeResizeFunctions);
