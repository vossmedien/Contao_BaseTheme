// marginPaddingAdjustments.js

/**
 * Passt den Margin von Elementen an.
 * @param {NodeList} elements - Die zu bearbeitenden DOM-Elemente.
 * @param {string} direction - Die Richtung des Margins ('top', 'bottom', 'left', 'right').
 * @param {boolean} isNegative - Gibt an, ob der Margin negativ sein soll.
 */
export function adjustMargin(elements, direction, isNegative) {
  elements.forEach((element) => {
    let size =
      direction === "top" || direction === "bottom"
        ? element.offsetHeight
        : element.offsetWidth;
    let rootFontSize = parseFloat(
      getComputedStyle(document.documentElement).fontSize
    );
    let bsBasicSpacingRem = parseFloat(
      getComputedStyle(document.documentElement).getPropertyValue(
        "--bs-basic-spacing"
      )
    );
    let bsBasicSpacingPx = bsBasicSpacingRem * rootFontSize;

    let marginValue =
      (isNegative ? -1 : 1) * (size / 2) - bsBasicSpacingPx * 2 + "px";

    if (direction === "top" || direction === "bottom") {
      element.style.marginTop = marginValue;
    } else {
      element.style.marginLeft = marginValue;
    }

    // Vom ausgehenden Element das nächste vorhandene .article-content div (im parent nach oben) finden
    let articleContentAbove = element
      .closest(".mod_article")
      .querySelector(".article-content");
    if (articleContentAbove) {
      articleContentAbove.style.paddingTop = `calc(var(--bs-basic-spacing)*2)`;
    }

    // Vom ausgehenden Element zum aktuellen parent .mod_article
    let currentArticle = element.closest(".mod_article");
    if (currentArticle) {
      // Das vorherige .mod_article Geschwisterelement finden
      let previousArticle = currentArticle.previousElementSibling;
      while (
        previousArticle &&
        !previousArticle.classList.contains("mod_article")
      ) {
        previousArticle = previousArticle.previousElementSibling;
      }

      if (previousArticle) {
        let articleContentPrevious =
          previousArticle.querySelector(".article-content");
        if (articleContentPrevious) {
          articleContentPrevious.style.paddingBottom = `calc(var(--bs-basic-spacing)*3)`;
        }
      }
    }
  });
}

let resizeTimeout;

/**
 * Optimiert die Funktion `adjustMargin` für den Einsatz bei Resize-Events.
 */
function optimizedAdjustMargin() {
  if (resizeTimeout) {
    clearTimeout(resizeTimeout);
  }

  resizeTimeout = setTimeout(() => {
    window.requestAnimationFrame(() => {
      adjustMargin(document.querySelectorAll(".pull-top"), "top", true);
      adjustMargin(document.querySelectorAll(".pull-bottom"), "bottom", false);
      adjustMargin(document.querySelectorAll(".pull-start"), "left", true);
      adjustMargin(document.querySelectorAll(".pull-end"), "right", false);
    });
  }, 100);
}

/**
 * Initialisiert die Margin-Anpassungen.
 */
export function initializeMarginAdjustments() {
  optimizedAdjustMargin();
  window.addEventListener("resize", optimizedAdjustMargin);
}
