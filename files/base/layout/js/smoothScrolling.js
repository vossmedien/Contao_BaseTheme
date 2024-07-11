// Funktionen für Smooth Scrolling und Scroll-to-Top

/**
 * Funktion für sanftes Scrollen zu einem Ankerpunkt.
 */
export function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]:not(.reset-cookies):not(.navActivator)').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            let target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const scrollOffset = getCSSVariableValue('--bs-scrolloffset');
                window.scrollTo({
                    top: target.offsetTop - scrollOffset,
                    behavior: 'smooth'
                });
            }
        });
    });
}

function getCSSVariableValue(variableName) {
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
            window.scrollTo({ top: 0, behavior: 'smooth' });
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

