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

    // Tausenderpunkte Funktion hier verfügbar machen
    function tausenderpunkte(zahl = 0, modus = 0, tz = ".") {
        if (isNaN(zahl)) {
            return "Eingabe ist keine Zahl!";
        }

        // Zahl in String umwandeln und Ganzzahl-Teil extrahieren
        let ganzzahl = Math.floor(Math.abs(zahl)).toString();
        let nachkomma = "";

        // Nachkommastellen behandeln je nach Modus
        if (modus === 1) {
            const originalStr = String(zahl);
            const dotIndex = originalStr.indexOf(".");
            if (dotIndex !== -1) {
                nachkomma = originalStr.slice(dotIndex + 1);
            } else {
                nachkomma = "0";
            }
        }

        // Tausendertrennung von rechts nach links
        let result = "";
        for (let i = ganzzahl.length - 1, count = 0; i >= 0; i--, count++) {
            if (count > 0 && count % 3 === 0) {
                result = tz + result;
            }
            result = ganzzahl[i] + result;
        }

        // Vorzeichen wieder hinzufügen falls negativ
        if (zahl < 0) {
            result = "-" + result;
        }

        // Nachkommastellen je nach Modus anhängen
        switch (modus) {
            case 1:
                result += "," + nachkomma;
                break;
            case 2:
                result += ",00";
                break;
            case 3:
                result += ",-";
                break;
            case 4:
                result += ",--";
                break;
            // case 0: bleibt ohne Nachkommastellen
        }

        return result;
    }

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
        // Regex erweitert um Tausendertrennzeichen (Punkte) zu erkennen
        const regex = /(\d{1,3}(?:\.\d{3})*(?:,\d+)?|\d+(?:,\d+)?)([^\d]*)/g;
        let matches;
        let lastIndex = 0;
        const fragments = [];

        while ((matches = regex.exec(fullText)) !== null) {
            // Text vor der Zahl hinzufügen
            if (matches.index > lastIndex) {
                fragments.push(document.createTextNode(fullText.substring(lastIndex, matches.index)));
            }

            // Zahl und nachfolgenden Text extrahieren
            let numberStr = matches[1];
            const text = matches[2];

            // Tausendertrennzeichen entfernen und Komma durch Punkt ersetzen für parseFloat
            const cleanNumber = numberStr.replace(/\./g, '').replace(',', '.');
            const targetNumber = parseFloat(cleanNumber);
            const decimalPlaces = (cleanNumber.split(".")[1] || []).length;

            // Span für die Zahl erstellen
            const numberSpan = document.createElement("span");
            numberSpan.className = "number-counter";
            numberSpan.textContent = numberStr + text;
            fragments.push(numberSpan);

            // Counter für dieses Span starten
            animateCounter(numberSpan, targetNumber, decimalPlaces, text, tausenderpunkte);

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

function animateCounter(element, targetNumber, decimalPlaces, text, tausenderpunkteFunc) {
    const duration = 3000;
    let startTime = null;

    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = timestamp - startTime;
        const progressPercentage = Math.min(progress / duration, 1);

        let currentDisplayNumber = progressPercentage * targetNumber;

        // Verwende die Tausenderpunkte-Funktion für die Formatierung
        if (decimalPlaces > 0) {
            // Runden auf die korrekte Anzahl von Nachkommastellen
            currentDisplayNumber = parseFloat(currentDisplayNumber.toFixed(decimalPlaces));
            element.textContent = tausenderpunkteFunc(currentDisplayNumber, 1) + text;
        } else {
            currentDisplayNumber = Math.floor(currentDisplayNumber);
            element.textContent = tausenderpunkteFunc(currentDisplayNumber, 0) + text;
        }

        if (progress < duration) {
            requestAnimationFrame(step);
        } else {
            // Finale Zahl mit korrekter Formatierung
            // targetNumber hat bereits die korrekte Präzision.
            // tausenderpunkteFunc(targetNumber, 1) extrahiert die Nachkommastellen direkt von targetNumber.
            if (decimalPlaces > 0) {
                element.textContent = tausenderpunkteFunc(targetNumber, 1) + text;
            } else {
                element.textContent = tausenderpunkteFunc(Math.floor(targetNumber), 0) + text;
            }
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






// Allgemeine Token-Refresh-Funktionalität für alle Formulare
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    /**
     * Versucht ein neues REQUEST_TOKEN zu holen um Token-Fehler zu vermeiden
     * @param {HTMLInputElement} tokenInput - Das Token Input-Feld
     * @param {Function} callback - Callback-Funktion nach Token-Refresh
     */
    function refreshTokenIfNeeded(tokenInput, callback) {
        const currentToken = tokenInput.value;

        // Prüfe ob Token vorhanden und gültig aussieht
        if (!currentToken || currentToken.length < 10) {
            console.warn('[TokenRefresh] Token fehlt oder ist zu kurz, fahre mit Submit fort');
            callback();
            return;
        }

        // Versuche über AJAX einen neuen Token zu holen
        fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Extrahiere neuen Token aus Response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTokenInput = doc.querySelector('input[name="REQUEST_TOKEN"]');

            if (newTokenInput && newTokenInput.value && newTokenInput.value !== currentToken) {
                tokenInput.value = newTokenInput.value;

                // Alle TOKEN-Fields im gleichen Formular aktualisieren
                const form = tokenInput.closest('form');
                if (form) {
                    const allTokenInputs = form.querySelectorAll('input[name="REQUEST_TOKEN"]');
                    allTokenInputs.forEach(input => {
                        if (input !== tokenInput) {
                            input.value = newTokenInput.value;
                        }
                    });
                }
            }

            callback();
        })
        .catch(error => {
            callback();
        });
    }

    /**
     * Behandelt Formular-Submit mit Token-Refresh
     * @param {Event} event - Das Submit-Event
     */
    function handleFormSubmitWithTokenRefresh(event) {
        const form = event.target;
        const tokenInput = form.querySelector('input[name="REQUEST_TOKEN"]');

        if (!tokenInput) {
            // Kein Token-Field vorhanden, normales Submit
            return true;
        }

        // Submit verhindern für Token-Refresh
        event.preventDefault();

        // Submit-Button temporär deaktivieren
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
        }

                 // Token refreshen und dann submitten
         refreshTokenIfNeeded(tokenInput, () => {
             // Submit nach Token-Refresh mit kurzer Verzögerung
             setTimeout(() => {
                 // Button wieder aktivieren
                 if (submitButton) {
                     submitButton.disabled = false;
                 }

                 // Formular wirklich submitten
                 // Event-Listener temporär entfernen um Endlosschleife zu vermeiden
                 form.removeEventListener('submit', handleFormSubmitWithTokenRefresh);
                 form.submit();
             }, 300);
         });

        return false;
    }

    // Event-Listener für alle Formulare registrieren
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        // Nur Formulare mit REQUEST_TOKEN behandeln
        const tokenInput = form.querySelector('input[name="REQUEST_TOKEN"]');

        // Area-Check Formular überspringen (hat eigenes Token-Handling)
        const isAreaCheckForm =
            form.id === 'park-form' ||
            form.closest('.mod_caeli_area_check');

        if (tokenInput && !isAreaCheckForm) {
            form.addEventListener('submit', handleFormSubmitWithTokenRefresh);
            form.dataset.tokenRefreshEnabled = 'theme';
        }
    });

    // Auch für dynamisch hinzugefügte Formulare (falls vorhanden)
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Neue Formulare finden
                    const newForms = node.tagName === 'FORM'
                        ? [node]
                        : Array.from(node.querySelectorAll ? node.querySelectorAll('form') : []);

                    newForms.forEach(form => {
                        const tokenInput = form.querySelector('input[name="REQUEST_TOKEN"]');

                        // Area-Check Formular überspringen (hat eigenes Token-Handling)
                        const isAreaCheckForm =
                            form.id === 'park-form' ||
                            form.closest('.mod_caeli_area_check');

                        if (tokenInput && !isAreaCheckForm && !form.dataset.tokenRefreshEnabled) {
                            form.addEventListener('submit', handleFormSubmitWithTokenRefresh);
                            form.dataset.tokenRefreshEnabled = 'theme';
                        }
                    });
                }
            });
        });
    });

    // Observer für dynamische Inhalte starten
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

});

