
function setupFunctions() {
    initFrames();
    InitBasefeatures();
}

function initFrames() {
    if (document.cookie.includes("cookie_iframes")) {
        const iframes = document.querySelectorAll("iframe[data-source],embed[data-source]");
        iframes.forEach(iframe => {
            iframe.src = iframe.getAttribute("data-source");
        });

        // Note: The colorbox functionality requires jQuery.
        // If you wish to move away from jQuery completely, you'll need to find a vanilla JS alternative to colorbox.
    } else {
        const iframes = document.querySelectorAll("iframe[data-source],embed[data-source]");
        iframes.forEach(iframe => {
            iframe.src = "iframe.php";
        });

        // Note: As mentioned above, the colorbox functionality requires jQuery.
    }
}

function InitBasefeatures() {
    // This function is currently empty in your original script
    // Logic related to 'cookie_basefeatures' can be added here if needed
}

window.addEventListener("cookiebar_save", setupFunctions);

setupFunctions();

const btn = document.querySelector(".reset-cookies");

if (btn) {
    btn.addEventListener("click", function (e) {
        e.preventDefault();

        if (confirm("Dadurch werden alle Cookies gelöscht und die Seite wird neu geladen, fortfahren?")) {
            window.localStorage.clear();

            const cookies = document.cookie.split(";");
            cookies.forEach(cookie => {
                document.cookie = cookie.trim().split('=')[0] + "=;expires=" + new Date().toUTCString() + ";path=/";
            });

            window.location.reload();
        }
    });
}
