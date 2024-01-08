// Default script paths
const defaultScripts = [
    "/files/base/layout/js/_global-functions.js",
    "/files/base/layout/js/_cookie-handling.js",
    "/files/base/layout/js/_scrollToAnchor-handling.js",
    "/files/base/layout/_vendor/node_modules/aos/dist/aos.js",
    "/files/base/layout/js/_element-handling.js",
];

const themeScripts = [
    "/files/base/layout/js/_theme/theme.js",
    "/files/base/layout/js/run.js",
];

let promises = [];

const loadScript = async (url) => {
    return new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = url;
        script.async = false;
        script.onload = () => resolve(url);
        script.onerror = () => reject(url);
        document.body.appendChild(script);
    });
};

const activateScriptOptions = () => {
    const scriptsToAdd = [];
    scriptsToAdd.push(
        "/files/base/layout/_vendor/node_modules/vanilla-lazyload/dist/lazyload.min.js"
    );
    scriptsToAdd.push(
        "/files/base/layout/_vendor/node_modules/swiper/swiper-bundle.min.js"
    );
    scriptsToAdd.push(
        "/files/base/layout/_vendor/node_modules/@popperjs/core/dist/umd/popper.min.js"
    );
    scriptsToAdd.push(
        "/files/base/layout/_vendor/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"
    );
    scriptsToAdd.push("/files/base/layout/js/_floating-labels.js");

    return [...defaultScripts, ...scriptsToAdd];
};

const init = () => {
    const scripts = activateScriptOptions();
    scripts.forEach((url) => {
        promises.push(loadScript(url));
    });

    themeScripts.forEach((url) => {
        promises.push(loadScript(url));
    });
};

// Activate scripts based on options


window.addEventListener("DOMContentLoaded", function () {
init();
});