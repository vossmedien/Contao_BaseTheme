// viewportChecks.js
// Modul für Viewport-Überprüfungen

/**
 * Überprüft, ob das angegebene Element im sichtbaren Bereich des Viewports ist.
 * @param {Element} elem - Das DOM-Element, das überprüft werden soll.
 * @returns {boolean} - Gibt zurück, ob das Element sichtbar ist oder nicht.
 */
export function isOnScreen(elem) {
    if (!elem) return false;

    const viewportTop = window.scrollY;
    const viewportHeight = window.innerHeight;
    const viewportBottom = viewportTop + viewportHeight;
    const top = elem.getBoundingClientRect().top + viewportTop;
    const height = elem.offsetHeight;
    const bottom = top + height;

    return (
        (top >= viewportTop && top < viewportBottom) ||
        (bottom > viewportTop && bottom <= viewportBottom) ||
        (height > viewportHeight && top <= viewportTop && bottom >= viewportBottom)
    );
}
