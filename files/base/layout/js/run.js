// Polyfill für die matches Methode
if (!Element.prototype.matches) {
    Element.prototype.matches =
        Element.prototype.msMatchesSelector ||
        Element.prototype.webkitMatchesSelector;
}

// Alle Parameter aus der URL extrahieren
var urlParams = new URLSearchParams(window.location.search);

// Alle Parameter-Namen aus der URL erhalten
var parameterNames = urlParams.keys();

// Iteriere über alle Parameter-Namen und fülle die entsprechenden Eingabefelder
for (var parameterName of parameterNames) {
    var fieldValue = getURLParameter(parameterName);
    var inputField = document.querySelector(
        'input[name="' + parameterName + '"]'
    );
    if (inputField) {
        inputField.value = fieldValue;
    }
}

$('*:not([data-aos])[class*="animate__"]').each(function (index) {
    var classes = $.grep(this.className.split(" "), function (v, i) {
        return v.indexOf("animate__") === 0;
    }).join();
    $(this).removeClass(classes);
    $(this).attr("data-aos", classes);
});

//initAnimations();
/*
var scrollTimeout = null;
$(window).scroll(function () {
    if (scrollTimeout) clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function () {
        window.addEventListener("load", AOS.refresh());
    }, 500);
});
 */


AOS.init({
    // Global settings:
    disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
    startEvent: "DOMContentLoaded", // name of the event dispatched on the document, that AOS should initialize on
    initClassName: false, // class applied after initialization
    animatedClassName: "animate__animated", // class applied on animation
    useClassNames: true, // if true, will add content of `data-aos` as classes on scroll
    disableMutationObserver: false, // disables automatic mutations' detections (advanced)
    //debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
    //throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)

    // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
    offset: -50, // offset (in px) from the original trigger point
    //delay: 5000,
    //duration: 5000,
    once: false, // whether animation should happen only once - while scrolling down
    mirror: true, // whether elements should animate out while scrolling past them
    anchorPlacement: "top-bottom", // defines which position of the element regarding to window should trigger the animation
});


const initializePopovers = () => {
    const popoverTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="popover"]'),
    ];
    popoverTriggerList.forEach((popoverTriggerEl) => {
        new bootstrap.Popover(popoverTriggerEl);
    });
};

const initializeTooltips = () => {
    const tooltipTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="tooltip"]'),
    ];
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
};

const initializeMegaMenu = () => {
    document.addEventListener("mouseenter", (event) => {
        if (
            event.target instanceof Element &&
            event.target.matches(".mod_navigation li > *:first-child")
        ) {
            [...document.querySelectorAll(".mod_navigation li.mm_container")].forEach(
                (elem) => {
                    elem.classList.remove("megamenu-active");
                }
            );
            if (event.target.parentElement.classList.contains("mm_container")) {
                event.target.parentElement.classList.add("megamenu-active");
            }
        }
    });

    document.addEventListener("mouseleave", (event) => {
        if (
            event.target instanceof Element &&
            event.target.matches(
                ".mod_navigation li.mm_container .mm_dropdown > .inner"
            )
        ) {
            setTimeout(() => {
                [
                    ...document.querySelectorAll(".mod_navigation li.mm_container"),
                ].forEach((elem) => {
                    elem.classList.remove("megamenu-active");
                });
            }, 500);
        }
    });
};

const initializeNavToggle = () => {
    const desktopNavActivator = document.querySelector(".desktopNavActivator");
    const expandableNav = document.querySelector(".expandable-nav");
    const closeButton = document.querySelector(".expandable-nav--close");

    // Überprüfen, ob desktopNavActivator und expandableNav existieren, bevor EventListener hinzugefügt wird
    if (desktopNavActivator && expandableNav) {
        desktopNavActivator.addEventListener("click", () => {
            expandableNav.classList.add("is-open");
        });
    }

    // Überprüfen, ob closeButton und expandableNav existieren, bevor EventListener hinzugefügt wird
    if (closeButton && expandableNav) {
        closeButton.addEventListener("click", () => {
            expandableNav.classList.remove("is-open");
        });
    }
};

// Hauptfunktion zur Initialisierung von allem
const main = async () => {
    initializePopovers();
    initializeTooltips();
    initializeMegaMenu();
    initializeNavToggle();

    setTimeout(() => {
        document.body.style.opacity = "1";
    }, 0);
};

// Ausführen der Hauptfunktion nachdem alle Promises aufgelöst wurden
Promise.all(promises)
    .then(main)
    .catch((script) => {
        console.error(`${script} failed to load`);
    });

const lazyLoadInstance = new LazyLoad({
    callback_loaded: function (element) {
        if (element.closest("header")) {
            const navWrapper = document.querySelector(".hc--bottom");
            if (navWrapper) {
                const navWrapperHeight = navWrapper.offsetHeight;
            }
        }
    },
});

const matrixCells = document.querySelectorAll(".matrix td");
matrixCells.forEach((cell) => {
    const input = cell.querySelector("input");
    if (input) {
        cell.addEventListener("click", function (e) {
            if (input.type === "radio") {
                const radios = cell.parentNode.querySelectorAll("input[type=radio]");
                radios.forEach((radio) => {
                    radio.checked = radio === input;
                });
            } else if (input.type === "checkbox" && e.target.nodeName === "TD") {
                input.checked = !input.checked;
            }
        });
    }
});


// Weiterer Code, der jQuery verwendet hat und nun in Vanilla JavaScript umgewandelt wurde
const offCanvasBasketOpener = document.querySelector(
    ".mod_mmenuHtml a.offCanvasBasketOpener"
);
if (offCanvasBasketOpener) {
    offCanvasBasketOpener.addEventListener("click", function () {
        setTimeout(function () {
            document.body.classList.add("mm-wrapper_opened", "mm-wrapper_blocking");
        }, 1000);
    });
}

const mmenuCloseButton = document.querySelector(".mmenu_close_button");
if (mmenuCloseButton) {
    mmenuCloseButton.addEventListener("click", function (e) {
        e.preventDefault();
    });
}

const accordionIcons = document.querySelectorAll(".accordion-nav i");
accordionIcons.forEach((icon) => {
    icon.addEventListener("click", function () {
        this.closest("li").classList.toggle("expanded");
    });
});

const counters = document.querySelectorAll(".count");
counters.forEach((counter) => {
    function startCounter(element) {
        if (isOnScreen(element) && !element.classList.contains("doneCounting")) {
            const size = element.textContent.includes(".")
                ? element.textContent.split(".")[1].length
                : 0;
            let startValue = 0;
            const endValue = parseFloat(element.textContent);
            const duration = 2000;
            const stepTime = Math.abs(Math.floor(duration / (endValue - startValue)));

            let currentVal = startValue;
            const increment = endValue > startValue ? 1 : -1;

            const timer = setInterval(() => {
                currentVal += increment;
                counter.textContent = parseFloat(currentVal).toFixed(size);
                if (currentVal == endValue) {
                    counter.classList.add("doneCounting");
                    clearInterval(timer);
                }
            }, stepTime);
        }
    }

    startCounter(counter);
});

const scrollFunctions = [];

const type1NonFixedHeader = document.querySelector(
    ".header--content.type--1:not(.fixed)"
);
if (type1NonFixedHeader) {
    const navWrapper = document.querySelector(".hc--bottom");
    const navContainer = document.querySelector(".hc-bottom--right-col");
    const navOffset = navContainer.offsetTop - 30;

    const detectIfScrolled = function () {
        if (window.scrollY > navOffset) {
            navWrapper.classList.add("is--scrolling");
            document.querySelector(".header--content .hc--top").style.marginBottom =
                navWrapper.offsetHeight + "px";
        } else {
            navWrapper.classList.remove("is--scrolling");
            document.querySelector(".header--content .hc--top").style.marginBottom =
                "0px";
        }
    };

    scrollFunctions.push(detectIfScrolled);
}
const type1FixedHeader = document.querySelector(
    ".header--content.type--1.fixed"
);
if (type1FixedHeader) {
    const navWrapper = document.querySelector(".hc--bottom");
    const navContainer = document.querySelector(".hc-bottom--right-col");
    const navOffset = navContainer.offsetTop - 15;

    const detectIfScrolled = function () {
        if (window.scrollY > navOffset) {
            navWrapper.classList.add("is--scrolling");
        } else {
            navWrapper.classList.remove("is--scrolling");
        }
    };

    scrollFunctions.push(detectIfScrolled);
}

const type2Header = document.querySelector(".header--content.type--2");
if (type2Header) {
    const navWrapper = document.querySelector(".hc--bottom");
    let navOffset;
    if (document.querySelector(".mod_pageImage")) {
        navOffset = navWrapper.offsetTop - 15;
    } else {
        navOffset = document.querySelector(".mainslider").offsetHeight;
        navWrapper.style.bottom = "auto";
        navWrapper.style.top = navOffset - navWrapper.offsetHeight + "px";
    }

    const detectIfScrolled = function () {
        if (window.scrollY > navOffset - navWrapper.offsetHeight) {
            navWrapper.classList.add("is--scrolling");
            navWrapper.style.top = "0px";
        } else {
            navWrapper.classList.remove("is--scrolling");
            navWrapper.style.top = navOffset - navWrapper.offsetHeight + "px";
        }
    };

    scrollFunctions.push(detectIfScrolled);
}

const type6Header = document.querySelector(".header--content.type--6");
if (type6Header) {
    const headerElement = document.querySelector("#header");

    const detectIfScrolled = function () {
        if (window.scrollY > headerElement.offsetTop + headerElement.offsetHeight) {
            document.body.classList.add("is--scrolling");
        } else {
            document.body.classList.remove("is--scrolling");
        }
    };

    scrollFunctions.push(detectIfScrolled);
}

const type7Header = document.querySelector(".header--content.type--7");
if (type7Header) {
    const headerElement = document.querySelector("#header");

    const detectIfScrolled = function () {
        if (window.scrollY > headerElement.offsetTop + headerElement.offsetHeight) {
            headerElement.classList.add("is--scrolling");
        } else {
            headerElement.classList.remove("is--scrolling");
        }
    };

    scrollFunctions.push(detectIfScrolled);
}

window.addEventListener("scroll", () => {
    scrollFunctions.forEach((func) => func());
});
