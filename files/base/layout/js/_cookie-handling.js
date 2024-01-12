function setupFunctions() {
    initFrames();
    InitBasefeatures();
}

function initFrames() {
    if (document.cookie.includes("cookie_iframes")) {
        const iframes = document.querySelectorAll("*[data-source]");


        iframes.forEach(iframe => {
            const source = iframe.getAttribute("data-source");
            iframe.src = iframe.getAttribute("data-source");

            if (iframe.tagName.toLowerCase() === "script") {
                const script = document.createElement("script");
                script.src = source;
                iframe.parentNode.replaceChild(script, iframe);
            }
        });


    } else {
        const iframes = document.querySelectorAll("*[data-source]");
        iframes.forEach(iframe => {
            if (iframe.tagName.toLowerCase() != "script") {
                iframe.src = "iframe.php";
            }
        });
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
