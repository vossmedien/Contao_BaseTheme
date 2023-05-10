let promises = [];
let scripts = [];
let optionalScripts = [];


function loadScript(url) {
    return new Promise(function (resolve, reject) {
        let script = document.createElement("script");
        script.src = url;
        script.async = false;
        script.onload = function () {
            resolve(url);
        };
        script.onerror = function () {
            reject(url);
        };
        document.body.appendChild(script);
    });
}

function init(optionalScripts) {
    scripts.forEach(function (url) {
        promises.push(loadScript(url));
    });

    if (optionalScripts) {
        optionalScripts.forEach(function (url) {
            promises.push(loadScript(url));
        });
    }
}

function scriptsActivator(
    lazyload = false,
    swiper = false,
    popper = false,
    bootstrap = false,
    aos = false,
    floatingLabels = false
) {
    scripts = [];
    scripts.push(
        "/files/base/layout/js/_global-functions.js",
        "/files/base/layout/js/_cookie-handling.js",
        "/files/base/layout/js/_element-handling.js",
        "/files/base/layout/js/_scrollToAnchor-handling.js"
    );

    if (lazyload) {
        scripts.push(
            "/files/base/layout/_vendor/node_modules/vanilla-lazyload/dist/lazyload.min.js"
        );
        options_lazyload = true;
    } else {
        options_lazyload = false;
    }

    if (swiper) {
        scripts.push(
            "/files/base/layout/_vendor/node_modules/swiper/swiper-bundle.min.js",
        );
        options_swiper = true;
    } else {
        options_swiper = false;
    }

    if (popper) {
        scripts.push(
            "/files/base/layout/_vendor/node_modules/@popperjs/core/dist/umd/popper.min.js"
        );
        options_popper = true;
    } else {
        options_popper = false;
    }

    if (bootstrap) {
        scripts.push(
            "/files/base/layout/_vendor/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"
        );
        options_bootstrap = true;
    } else {
        options_bootstrap = false;
    }

    if (aos) {
        scripts.push(
            "/files/base/layout/_vendor/node_modules/aos/dist/aos.js",
        );
        options_aos = true;
    }

    if (floatingLabels) {
        scripts.push(
            "/files/base/layout/js/_floating-labels.js",
        );
        options_floatingLabels = true;
    }
    else {
        options_floatingLabels = false;
    }
}


let themeScripts = [
    "/files/base/layout/js/_theme/theme.js",
    "/files/base/layout/js/run.js",
];

let finalPromise = [];

themeScripts.forEach(function (url) {
    finalPromise.async = true;
    finalPromise.push(loadScript(url));
});
