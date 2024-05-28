// elementHeightAdjustments.js
// Modul für die Anpassung der Höhe von Elementen

/**
 * Passt die Höhe der umschaltenden Karten an.
 */
export function setSwitchingcardsHeight() {
    document.querySelectorAll(".ce_rsce_switchingcards").forEach(function (switchingCard) {
        let maxFrontHeight = 0;
        let maxBackHeight = 0;

        switchingCard.querySelectorAll(".flipping-card--wrapper").forEach(function (wrapper) {
            const frontHeight = wrapper.querySelector(".flipping-card--front .front--inner").offsetHeight;
            const backHeight = wrapper.querySelector(".flipping-card--back .back--inner").offsetHeight;

            maxFrontHeight = Math.max(maxFrontHeight, frontHeight);
            maxBackHeight = Math.max(maxBackHeight, backHeight);
        });

        const maxHeight = Math.max(maxFrontHeight, maxBackHeight);
        switchingCard.querySelectorAll(".flipping-card--front, .flipping-card--back").forEach(function (card) {
            card.style.height = maxHeight + 'px';
        });
    });
}