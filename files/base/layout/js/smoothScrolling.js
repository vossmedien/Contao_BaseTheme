// Funktionen für Smooth Scrolling und Scroll-to-Top

/**
 * Funktion für sanftes Scrollen zu einem Ankerpunkt.
 */
export function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]:not(.reset-cookies)').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            let target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
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

