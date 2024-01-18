// urlParameterHandling.js
// Modul für die Analyse und Handhabung von URL-Parametern

/**
 * Ruft den Wert eines URL-Parameters ab.
 * @param {string} name - Der Name des URL-Parameters.
 * @returns {string|null} - Der Wert des URL-Parameters oder null, wenn er nicht existiert.
 */
export function getURLParameter(name) {
    name = name.replace(/[\[\]]/g, "\\$&");
    const regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)");
    const results = regex.exec(window.location.href);

    if (!results) return null;
    if (!results[2]) return '';

    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
