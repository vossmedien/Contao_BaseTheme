// animations.js
// Modul für Animationen

/**
 * Führt eine Animation auf einem Element aus.
 * @param {Element} elem - Das DOM-Element, das animiert werden soll.
 * @param {string} style - Der CSS-Stil, der animiert wird (z.B. 'opacity').
 * @param {string} unit - Die Einheiten des Stils (z.B. 'px', '%').
 * @param {number} from - Startwert der Animation.
 * @param {number} to - Endwert der Animation.
 * @param {number} time - Dauer der Animation in Millisekunden.
 * @param {boolean} prop - Wenn true, wird die Eigenschaft des Elements direkt gesetzt, sonst der CSS-Stil.
 */
export function animate(elem, style, unit, from, to, time, prop) {
    if (!elem) return;

    const start = new Date().getTime();
    const timer = setInterval(function () {
        const step = Math.min(1, (new Date().getTime() - start) / time);

        if (prop) {
            elem[style] = from + step * (to - from) + unit;
        } else {
            elem.style[style] = from + step * (to - from) + unit;
        }

        if (step === 1) {
            clearInterval(timer);
        }
    }, 25);

    if (prop) {
        elem[style] = from + unit;
    } else {
        elem.style[style] = from + unit;
    }
}
