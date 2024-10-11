import {
    changeAnchorLinks
} from "./navigationHandling.js";


/**
 * Funktion für sanftes Scrollen zu einem Ankerpunkt.
 */
export function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]:not(.reset-cookies,.navActivator,[href*="Nav"], .venobox)').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                const scrollOffset = getCSSVariableValue('--bs-scrolloffset');
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - scrollOffset;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Setze den aktiven Link nach einer kurzen Verzögerung
                setTimeout(() => {
                    setActiveLink(this);
                    changeAnchorLinks();
                }, 50);
            }
        });
    });
}

export function setActiveLink(element) {
    document.querySelectorAll("ul .active").forEach(el => {
        el.classList.remove("active");
    });

    if (element) {
        element.classList.add("active");
    }
}

export function getCSSVariableValue(variableName) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
    const numericValue = parseFloat(value);
    return isNaN(numericValue) ? 0 : numericValue;
}

/**
 * Funktion zum sanften Scrollen an den Anfang der Seite.
 */
export function scrollToTop() {
    const scrollToTopBtn = document.querySelectorAll(".scrollToTop, .BodyScrollToTop");

    scrollToTopBtn.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    });

    window.addEventListener('scroll', function () {
        if (document.documentElement.scrollTop > 50) {
            scrollToTopBtn.forEach(btn => btn.classList.add("visible"));
        } else {
            scrollToTopBtn.forEach(btn => btn.classList.remove("visible"));
        }
    });
}

