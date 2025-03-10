import {setupFunctions, resetCookies} from "./cookieManager.js";



/*
document.addEventListener('DOMContentLoaded', () => {
    window.VSM = window.VSM || {};

    // Haupt-Instanz erstellen
    if (!window.VSM.lazyLoader) {
        window.VSM.lazyLoader = new VSMLazyLoader({
            excludeSelectors: ['.swiper-slide']
        });
    }

    // Slider-Instanz erstellen
    if (!window.VSM.sliderMediaLoader) {
        window.VSM.sliderMediaLoader = new VSMSliderMediaLoader();
    }

    // Alias für Abwärtskompatibilität
    window.VSM.lazyMediaLoader = window.VSM.lazyLoader;

    // LazyLoadInstance für alte API-Kompatibilität
    window.VSM.lazyLoadInstance = {
        update: () => {}
    };
});




window.addEventListener("cookiebar_save", setupFunctions);
const btn = document.querySelector(".reset-cookies");
if (btn) {
    btn.addEventListener("click", function (e) {
        e.preventDefault();
        resetCookies();
    });
}

 */




function startCounter(element) {
    if (element.classList.contains("doneCounting")) {
        return;
    }
    element.classList.add("doneCounting");

    // Finde alle Textknoten innerhalb des Elements
    const textNodes = [];
    function getTextNodes(node) {
        if (node.nodeType === 3) { // Textknoten
            textNodes.push(node);
        } else {
            for (let i = 0; i < node.childNodes.length; i++) {
                getTextNodes(node.childNodes[i]);
            }
        }
    }
    getTextNodes(element);

    // Verarbeite jeden Textknoten
    textNodes.forEach(textNode => {
        const fullText = textNode.nodeValue;
        const regex = /(\d+([.,]\d+)?)([^\d]*)/g;
        let matches;
        let lastIndex = 0;
        const fragments = [];

        while ((matches = regex.exec(fullText)) !== null) {
            // Text vor der Zahl hinzufügen
            if (matches.index > lastIndex) {
                fragments.push(document.createTextNode(fullText.substring(lastIndex, matches.index)));
            }

            // Zahl und nachfolgenden Text extrahieren
            const originalNumber = matches[1].replace(",", ".");
            const decimalPlaces = (originalNumber.split(".")[1] || []).length;
            const targetNumber = parseFloat(originalNumber);
            const text = matches[3];

            // Span für die Zahl erstellen
            const numberSpan = document.createElement("span");
            numberSpan.className = "number-counter";
            numberSpan.textContent = originalNumber + text;
            fragments.push(numberSpan);

            // Counter für dieses Span starten
            animateCounter(numberSpan, targetNumber, decimalPlaces, text);

            lastIndex = regex.lastIndex;
        }

        // Rest des Textes hinzufügen
        if (lastIndex < fullText.length) {
            fragments.push(document.createTextNode(fullText.substring(lastIndex)));
        }

        // Original-Textknoten ersetzen
        if (fragments.length > 0) {
            const parent = textNode.parentNode;
            fragments.forEach(fragment => {
                parent.insertBefore(fragment, textNode);
            });
            parent.removeChild(textNode);
        }
    });
}

function animateCounter(element, targetNumber, decimalPlaces, text) {
    const duration = 2000;
    let startTime = null;

    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = timestamp - startTime;
        const progressPercentage = Math.min(progress / duration, 1);

        const current = progressPercentage * targetNumber;
        element.textContent = current.toFixed(decimalPlaces) + text;

        if (progress < duration) {
            requestAnimationFrame(step);
        } else {
            element.textContent = targetNumber.toFixed(decimalPlaces) + text;
        }
    }

    requestAnimationFrame(step);
}

const observer = new IntersectionObserver(
    (entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                startCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    },
    {
        rootMargin: "0px",
        threshold: 0.1,
    }
);

document.querySelectorAll(".count").forEach((el) => {
    observer.observe(el);
});



/*
window.pushToDataLayer = function (type, position, element, additional) {
    dataLayer.push({
        "event": "navigationClick",
        "navigationType": type,
        "navigationPosition": position,
        "navigationElement": element,
        "navigationAdditional": additional
    });
};

// Event-Listener hinzufügen

var trackingLinks = document.querySelectorAll('[data-event-type]');
trackingLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
        var type = this.getAttribute('data-event-type');
        var position = this.getAttribute('data-event-position');
        var element = this.getAttribute('data-event-element');
        var additional = this.getAttribute('data-event-additional');

        window.pushToDataLayer(type, position, element, additional);
    });
});


 */






