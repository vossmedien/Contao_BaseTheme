// cookieManager.js
// Modul für Cookie-Management und iframe-Handling

/**
 * Initialisiert Funktionen nach Cookie-Zustand.
 */
export function setupFunctions() {
    initFrames();
    initBaseFeatures();
    initExternalScripts();
}

/**
 * Initialisiert externe Scripts basierend auf Cookie-Zustand.
 */
function initExternalScripts() {
    if (document.cookie.includes("cookie_basefeatures")) {
        const scriptPlaceholders = document.querySelectorAll("script[data-external-source]");

        scriptPlaceholders.forEach(placeholder => {
            const source = placeholder.getAttribute("data-external-source");
            const script = document.createElement("script");
            script.src = source;

            // Kopiere alle weiteren Attribute
            Array.from(placeholder.attributes).forEach(attr => {
                if (attr.name !== 'data-external-source') {
                    script.setAttribute(attr.name, attr.value);
                }
            });

            placeholder.parentNode.replaceChild(script, placeholder);
        });
    }
}

/**
 * Initialisiert iframes basierend auf dem Cookie-Zustand.
 */
function initFrames() {
    if (document.cookie.includes("cookie_iframes")) {
        const iframes = document.querySelectorAll("*[data-source]");
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    const source = iframe.getAttribute("data-source");
                    iframe.src = source;

                    // Falls es sich um ein Script-Tag handelt
                    if (iframe.tagName.toLowerCase() === "script") {
                        const script = document.createElement("script");
                        script.src = source;
                        iframe.parentNode.replaceChild(script, iframe);
                    }

                    observer.unobserve(iframe);
                }
            });
        }, {
            root: null,
            rootMargin: "200px 0px 0px 0px",
            threshold: 0
        });

        iframes.forEach(iframe => {
            observer.observe(iframe);
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

function initBaseFeatures() {
    if (document.cookie.includes("cookie_basefeatures")) {
        // Basisfunktionen hier
    }
}

function getLanguage() {
    return document.documentElement.lang || 'de';
}

export function resetCookies() {
    const language = getLanguage();
    let message = "This will delete all cookies and reload the page, continue?";

    if (language === 'de') {
        message = "Dadurch werden alle Cookies gelöscht und die Seite wird neu geladen, fortfahren?";
    } else if (language === 'en') {
        message = "This will delete all cookies and reload the page, continue?";
    }

    if (confirm(message)) {
        window.localStorage.clear();
        const cookies = document.cookie.split(";");
        cookies.forEach(cookie => {
            document.cookie = cookie.trim().split('=')[0] + "=;expires=" + new Date().toUTCString() + ";path=/";
        });
        window.location.reload();
    }
}