// cookieManager.js
// Modul für Cookie-Management und iframe-Handling

/**
 * Initialisiert Funktionen nach Cookie-Zustand.
 */
export function setupFunctions() {
    initFrames();
    initBaseFeatures();
}



/**
 * Initialisiert iframes basierend auf dem Cookie-Zustand.
 */
function initFrames() {
    if (document.cookie.includes("cookie_iframes")) {
        const iframes = document.querySelectorAll("*[data-source]");
        iframes.forEach(iframe => {
            const source = iframe.getAttribute("data-source");
            iframe.src = source;

            if (iframe.tagName.toLowerCase() === "script") {
                const script = document.createElement("script");
                script.src = source;
                iframe.parentNode.replaceChild(script, iframe);
            }
        });
    } else {
        const iframes = document.querySelectorAll("*[data-source]");
        iframes.forEach(iframe => {
            if (iframe.tagName.toLowerCase() !== "script") {
                iframe.src = "iframe.php";
            }
        });
    }
}

/**
 * Initialisiert Basisfunktionen abhängig von Cookies.
 */
function initBaseFeatures() {
      if (document.cookie.includes("cookie_basefeatures")) {

      }
}

/**
 * Löscht alle Cookies und lädt die Seite neu.
 */
export function resetCookies() {
    if (confirm("Dadurch werden alle Cookies gelöscht und die Seite wird neu geladen, fortfahren?")) {
        window.localStorage.clear();
        const cookies = document.cookie.split(";");
        cookies.forEach(cookie => {
            document.cookie = cookie.trim().split('=')[0] + "=;expires=" + new Date().toUTCString() + ";path=/";
        });
        window.location.reload();
    }
}
