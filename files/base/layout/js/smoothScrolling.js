// smoothScrolling.js
import {changeAnchorLinks} from "./navigationHandling.js";

// IIFE für initiales Hash-Handling
(() => {
    if (window.location.hash) {
        // window.scrollTo(0, 0); // Entfernt, um Sprung zu vermeiden
        const handleLoadForHash = () => { // Eindeutigerer Name
            window.removeEventListener('load', handleLoadForHash);
            handleInitialHash();
        };
        // Sicherstellen, dass das Dokument geladen ist, bevor wir scrollen
        if (document.readyState === 'complete') {
            handleInitialHash();
        } else {
            window.addEventListener('load', handleLoadForHash);
        }
    }
})();

export function getCSSVariableValue(variableName) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
    const numericValue = parseFloat(value);
    return isNaN(numericValue) ? 100 : numericValue; // Standard-Offset, falls nicht definiert
}

function handleInitialHash() {
    const hash = window.location.hash;
    if (hash) {
        const targetId = hash.substring(1);
        // requestAnimationFrame stellt sicher, dass scrollToTarget ausgeführt wird,
        // bevor der Browser das nächste Mal rendert, was besser für Performance ist.
        requestAnimationFrame(() => {
            scrollToTarget(targetId);
        });
    }
}

function scrollToTarget(targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;

    const scrollOffset = getCSSVariableValue('--bs-scrolloffset');
    const currentScroll = window.pageYOffset;

    const allLazyImages = document.querySelectorAll('.lazy');
    // Filter für Bilder, die zwischen der aktuellen Position und dem Ziel liegen
    // und das Layout beim Laden beeinflussen könnten.
    const relevantLazyImages = Array.from(allLazyImages).filter(img => {
        const imgRect = img.getBoundingClientRect();
        const imgOffsetTop = imgRect.top + window.pageYOffset;
        const targetRect = target.getBoundingClientRect(); // Muss hier für die Filterung bekannt sein
        const targetOffset = targetRect.top + window.pageYOffset;
        // Nur Bilder berücksichtigen, die noch nicht geladen sind und sich im Pfad zum Ziel befinden
        return imgOffsetTop > currentScroll && imgOffsetTop <= targetOffset && !img.complete;
    });

    const performFinalScroll = () => {
        const finalTargetRect = target.getBoundingClientRect(); // Endgültige Position nach Laden der Bilder
        const finalPosition = finalTargetRect.top + window.pageYOffset - scrollOffset;

        window.scrollTo({
            top: finalPosition,
            behavior: 'smooth'
        });

        // Timeout, um das Ende des 'smooth' Scrollens abzuwarten und Position zu prüfen
        setTimeout(() => {
            const checkPositionRect = target.getBoundingClientRect();
            // Wenn Ziel nicht innerhalb einer Toleranz von `scrollOffset` vom oberen Rand des Viewports ist
            if (Math.abs(checkPositionRect.top - scrollOffset) > 5) { // Toleranz von 5px
                window.scrollTo({
                    top: window.pageYOffset + checkPositionRect.top - scrollOffset,
                    behavior: 'auto' // Sofortige Korrektur, um weiteres Ruckeln zu vermeiden
                });
            }
            changeAnchorLinks(); // Navigation aktualisieren

            // Hash nur aktualisieren, wenn nötig und nach allen Scroll-Anpassungen
            const expectedHash = `#${targetId}`;
            if (window.location.hash !== expectedHash) {
                 // Temporär den hashchange Listener entfernen, um Endlosschleife zu vermeiden
                window.removeEventListener('hashchange', handleHashChangeEvent);
                window.location.hash = expectedHash;
                // Listener wieder hinzufügen, nachdem der DOM aktualisiert wurde
                requestAnimationFrame(() => {
                    window.addEventListener('hashchange', handleHashChangeEvent);
                });
            }
        }, 400); // Zeit für 'smooth' scroll und Puffer
    };

    // Das initiale Scrollen wurde entfernt.

    if (relevantLazyImages.length > 0) {
        let imagesProcessed = 0;
        relevantLazyImages.forEach((img) => {
            // Wenn das Bild bereits geladen ist (z.B. aus Cache oder nicht lazy), direkt weiter
            if (img.complete) {
                imagesProcessed++;
                if (imagesProcessed === relevantLazyImages.length) {
                    requestAnimationFrame(performFinalScroll);
                }
                return; // Weiter zum nächsten Bild
            }

            const imageLoadOrErrorHandler = () => {
                img.removeEventListener('load', imageLoadOrErrorHandler);
                img.removeEventListener('error', imageLoadOrErrorHandler);
                imagesProcessed++;
                if (imagesProcessed === relevantLazyImages.length) {
                    requestAnimationFrame(performFinalScroll);
                }
            };
            img.addEventListener('load', imageLoadOrErrorHandler);
            img.addEventListener('error', imageLoadOrErrorHandler); // Auch Fehler beim Laden behandeln
        });
    } else {
        requestAnimationFrame(performFinalScroll);
    }
}

function scrollToAnchor(e) {
    e.preventDefault();
    const targetId = this.getAttribute('href').split('#')[1];
    // Kein requestAnimationFrame hier, da es direkt durch User-Interaktion ausgelöst wird und sofort reagieren soll
    scrollToTarget(targetId);
}

export function scrollToTop() {
    const scrollToTopBtn = document.querySelectorAll(".scrollToTop, .BodyScrollToTop, .scrolltop");

    scrollToTopBtn.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    });

    // Sichtbarkeit des Buttons steuern mit Debounce
    let scrollTimeout;
    window.addEventListener('scroll', function () {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(() => {
            if (window.pageYOffset > 50) { // window.pageYOffset für bessere Kompatibilität
                scrollToTopBtn.forEach(btn => btn.classList.add("visible"));
            } else {
                scrollToTopBtn.forEach(btn => btn.classList.remove("visible"));
            }
        }, 100); // Kurze Verzögerung für Debounce
    });
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    scrollToTop();

    document.querySelectorAll('a[href^="#"]:not(.reset-cookies,.navActivator,[href*="Nav"],.venobox,.mm-btn)').forEach(link => {
        link.addEventListener('click', scrollToAnchor);
    });

    window.addEventListener('scroll', changeAnchorLinks); // Ggf. auch debouncen, falls performance-kritisch
    changeAnchorLinks(); // Initialaufruf

    // handleInitialHash() wird nun durch die IIFE oben und das 'load'-Event gesteuert
});

// Hashchange Event-Handler als benannte Funktion, um ihn entfernen und hinzufügen zu können
const handleHashChangeEvent = (event) => {
    const newHash = window.location.hash;
    if (newHash) {
        const targetId = newHash.substring(1);
        requestAnimationFrame(() => {
            scrollToTarget(targetId);
        });
    }
};
window.addEventListener('hashchange', handleHashChangeEvent);