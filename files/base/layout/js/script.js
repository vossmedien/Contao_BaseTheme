let promises = [];
let scripts = [];
let optionalScripts = [];

function isOnScreen(elem) {
    // if the element doesn't exist, abort
    if (elem.length == 0) {
        return;
    }
    var $window = jQuery(window)
    var viewport_top = $window.scrollTop()
    var viewport_height = $window.height()
    var viewport_bottom = viewport_top + viewport_height
    var $elem = jQuery(elem)
    var top = $elem.offset().top
    var height = $elem.height()
    var bottom = top + height

    return (top >= viewport_top && top < viewport_bottom) ||
        (bottom > viewport_top && bottom <= viewport_bottom) ||
        (height > viewport_height && top <= viewport_top && bottom >= viewport_bottom)
}

function changeNavLinks() {
    $('#mainNav li > *').each(function (index) {
        var hash = window.location.hash;

        if ($(this).attr('href') == hash) {
            $('#mainNav .active').removeClass("active");
            $(this).addClass("active");
        } else if ($(this).attr('href') == "#top") {
            $('#mainNav .level_1 > .first > a').addClass("active");
        }
    });

    $('#mobileNav li > *').each(function (index) {
        var hash = window.location.hash;

        if ($(this).attr('href') == hash) {
            $('#mobileNav .active').removeClass("active");
            $('#mobileNav .mm-listitem_selected').removeClass("mm-listitem_selected");
            $(this).addClass("active");
            $(this).parent().addClass("mm-listitem_selected");
        } else if ($(this).attr('href') == "#top") {
            $('#mobileNav .level_1 > .first').addClass("listitem_selected");
        }
    });
}

function loadScript(url) {
    return new Promise(function (resolve, reject) {
        let script = document.createElement('script');
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

function scriptsActivator(lazyload = false, swiper = false, popper = false, bootstrap = false, aos = false) {
    scripts = [];
    scripts.push("/files/base/layout/_vendor/node_modules/dom7/dom7.min.js", "/files/base/layout/_vendor/node_modules/ssr-window/ssr-window.umd.min.js");

    if (lazyload) {
        scripts.push("/files/base/layout/_vendor/node_modules/vanilla-lazyload/dist/lazyload.min.js");
        options_lazyload = true;
    } else {
        options_lazyload = false;
    }

    if (swiper) {
        scripts.push("/files/base/layout/_vendor/node_modules/swiper/swiper-bundle.min.js")
        options_swiper = true;
    } else {
        options_swiper = false;
    }

    if (popper) {
        scripts.push("/files/base/layout/_vendor/node_modules/@popperjs/core/dist/umd/popper.min.js");
        options_popper = true;
    } else {
        options_popper = false;
    }

    if (bootstrap) {
        scripts.push("/files/base/layout/_vendor/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js")
        options_bootstrap = true;
    } else {
        options_bootstrap = false;
    }

    if (aos) {
        scripts.push("/files/base/layout/_vendor/node_modules/aos/dist/aos.js");
        options_aos = true;
    } else {
        options_aos = false;
    }
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

    changeNavLinks();
}

//scriptsActivator();


let themeScripts = ["/files/base/layout/js/_theme/theme.js", "/files/base/layout/js/run.js"];
let finalPromise = []

themeScripts.forEach(function (url) {
    finalPromise.push(loadScript(url));
});

