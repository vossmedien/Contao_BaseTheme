import {setSwitchingcardsHeight} from "./elementHeightAdjustments";
import {initializeSmoothScrolling, scrollToTop} from "./smoothScrolling";
import {setupFunctions} from "./cookieManager";
import {initializeMarginAdjustments} from "./marginPaddingAdjustments";
import {
    addBootstrapClasses,
    adjustTableResponsive,
} from "./classStyleManipulation";
import {adjustFormLayout} from "./formAdjustments";
import {addPlaceholders} from "./floatingLabels";

const scrollFunctions = [];
const loadFunctions = [];
const touchMoveFunctions = [];
const DomLoadFunctions = [];
const ResizeFunctions = [];

const form = document.querySelector(".ce_form form");
if (form) {
    form.addEventListener("submit", function (e) {
        setTimeout(addPlaceholders, 250);
    });
}

const resetAOS = () => {
    if (window.innerWidth >= 932) {
        $('*[data-aos][class*="animate__"]').each(function (index) {
            var classes = this.className.split(/\s+/);
            for (var i = 0; i < classes.length; i++) {
                if (classes[i].startsWith("animate__")) {
                    $(this).removeClass(classes[i]);
                }
            }
        });
        AOS.refresh();
    }
};
ResizeFunctions.push(resetAOS);

const initAOS = () => {
    document.querySelectorAll('*:not([data-aos])').forEach(element => {
        if (element.className.includes('animate__')) {
            const classes = element.className.split(" ").filter(cls => cls.startsWith("animate__")).join(" ");
            element.classList.remove(...classes.split(" ")); // Entfernt alle Animate.css-Klassen
            element.setAttribute("data-aos", classes.replace(/,/g, ' ')); // Fügt die Animate.css-Klassen als data-aos Attribut hinzu
        }
    });


    AOS.init({
        // Global settings:
        disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
        startEvent: "DOMContentLoaded", // name of the event dispatched on the document, that AOS should initialize on
        initClassName: false, // class applied after initialization
        animatedClassName: "animate__animated", // class applied on animation
        useClassNames: true, // if true, will add content of `data-aos` as classes on scroll
        disableMutationObserver: true, // disables automatic mutations' detections (advanced)
        //debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
        //throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)

        // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
        offset: 0, // offset (in px) from the original trigger point
        //delay: 500,
        //duration: 5000,
        once: true, // whether animation should happen only once - while scrolling down
        mirror: true, // whether elements should animate out while scrolling past them
        anchorPlacement: "top-bottom", // defines which position of the element regarding to window should trigger the animation
    });
};
ResizeFunctions.push(initAOS);
loadFunctions.push(initAOS);

const addStylesToArticlesWithBg = () => {
    // Wählen Sie das letzte .mod_article Element aus
    var articleLast = document.querySelector(".mod_article:last-child");
    // Wählen Sie das erste .mod_article Element aus
    var articleFirst = document.querySelector(".mod_article:first-child");

    // Prüfen Sie, ob das letzte .mod_article Element existiert und ein style.with-bg Kind hat
    if (articleLast && articleLast.querySelector("style.with-bg")) {
        articleLast.style.padding =
            "var(--with-body-bg-spacing) 0 calc(var(--with-body-bg-spacing) * 2) 0";
        articleLast.style.marginBottom = "calc(-1 * var(--with-body-bg-spacing))";
    }

    // Prüfen Sie, ob das erste .mod_article Element existiert und ein style.with-bg Kind hat
    if (articleFirst && articleFirst.querySelector("style.with-bg")) {
        //articleFirst.style.padding =
        ("calc(var(--with-body-bg-spacing) * 2) 0 var(--with-body-bg-spacing) 0");
        //articleFirst.style.marginTop = "calc(-1 * var(--with-body-bg-spacing))";
    }
};
loadFunctions.push(addStylesToArticlesWithBg);
// Rufen Sie die Funktion auf, um die CSS-Regeln anzuwenden, wenn das Dokument geladen wird

const initializePopovers = () => {
    const popoverTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="popover"]'),
    ];
    popoverTriggerList.forEach((popoverTriggerEl) => {
        new bootstrap.Popover(popoverTriggerEl);
    });
};
loadFunctions.push(initializePopovers);
const initializeTooltips = () => {
    const tooltipTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="tooltip"]'),
    ];
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
};
loadFunctions.push(initializeTooltips);
/* MEGAMENÜ */
const initializeMegaMenu = () => {
    // Polyfill für die matches Methode
    if (!Element.prototype.matches) {
        Element.prototype.matches =
            Element.prototype.msMatchesSelector ||
            Element.prototype.webkitMatchesSelector;
    }

    // Handle mouseenter event
    document.addEventListener(
        "mouseenter",
        (event) => {
            let target = event.target;
            // Check if target is an Element and has matches function
            if (
                target instanceof Element &&
                target.matches(".mod_navigation li > *:first-child")
            ) {
                document
                    .querySelectorAll(".mod_navigation li.mm_container")
                    .forEach((elem) => {
                        elem.classList.remove("megamenu-active");
                    });

                let parentLi = target.closest("li.mm_container");
                if (parentLi) {
                    parentLi.classList.add("megamenu-active");
                }
            }
        },
        true
    ); // Enable event capturing

    // Handle mouseleave event
    document.addEventListener(
        "mouseleave",
        (event) => {
            let target = event.target;
            // Check if target is an Element and has matches function
            if (
                target instanceof Element &&
                target.matches(".mod_navigation li.mm_container .mm_dropdown > .inner")
            ) {
                setTimeout(() => {
                    // Ensure the target is still part of the DOM
                    if (
                        document.contains(target) &&
                        target.matches(
                            ".mod_navigation li.mm_container .mm_dropdown > .inner"
                        )
                    ) {
                        target
                            .closest("li.mm_container")
                            .classList.remove("megamenu-active");
                    }
                }, 500);
            }
        },
        true
    ); // Enable event capturing
};
loadFunctions.push(initializeMegaMenu);
/* MEGAMENÜ END */

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
loadFunctions.push(initializeNavToggle);

const loadSearchParams = () => {
    /** EXTRACT URL PARAMETERS  **/
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
};
DomLoadFunctions.push(loadSearchParams);

const type1NonFixedHeader = document.querySelector(
    ".header--content.type--1:not(.fixed)"
);
if (type1NonFixedHeader) {
    const navWrapper = document.querySelector(".hc--bottom");
    const navContainer = document.querySelector(".hc-bottom--right-col");
    const navOffset = navContainer.offsetTop;

    const detectIfScrolled = function () {
        if (window.scrollY > navOffset) {
            navWrapper.classList.add("is--scrolling");
            document.querySelector(".header--content .hc--top").style.marginBottom =
                navWrapper.offsetHeight + 30 + "px";
        } else {
            navWrapper.classList.remove("is--scrolling");
            document.querySelector(".header--content .hc--top").style.marginBottom =
                "0px";
        }
    };

    scrollFunctions.push(detectIfScrolled);
    loadFunctions.push(detectIfScrolled);
    touchMoveFunctions.push(detectIfScrolled);
    ResizeFunctions.push(detectIfScrolled);
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
    loadFunctions.push(detectIfScrolled);
    touchMoveFunctions.push(detectIfScrolled);
    ResizeFunctions.push(detectIfScrolled);
}

const type2Header = document.querySelector(".header--content.type--2");
if (type2Header) {
    const navWrapper = document.querySelector(".hc--bottom");
    let navOffset;

    const detectIfScrolled = function () {
        if (window.scrollY > navOffset - navWrapper.offsetHeight) {
            navWrapper.classList.add("is--scrolling");
            navWrapper.style.top = "0px";
        } else {
            navWrapper.classList.remove("is--scrolling");
            navWrapper.style.top = navOffset - navWrapper.offsetHeight + "px";
        }

        if (document.querySelector(".mod_pageImage")) {
            navOffset = navWrapper.offsetTop - 15;
        } else {
            navOffset = document.querySelector(".mainslider").offsetHeight;
            navWrapper.style.bottom = "auto";
            navWrapper.style.top = navOffset - navWrapper.offsetHeight + "px";
        }
    };

    scrollFunctions.push(detectIfScrolled);
    loadFunctions.push(detectIfScrolled);
    touchMoveFunctions.push(detectIfScrolled);
    ResizeFunctions.push(detectIfScrolled);
}

const type3Header = document.querySelector(".header--content.type--3");
if (type3Header) {
    function detectIfScrolled() {
        var navWrapper = document.querySelector(".header--content.type--3");
        if (!navWrapper) return;

        var navOffset;
        var mainslider = document.querySelector(".mainslider");
        var mainsliderHeight = mainslider ? mainslider.offsetHeight : 0;
        navOffset = mainsliderHeight;

        navWrapper.style.bottom = "auto";
        navWrapper.style.top =
            window.pageYOffset > navOffset - navWrapper.offsetHeight
                ? "0px"
                : navOffset - navWrapper.offsetHeight + "px";
        navWrapper.style.position =
            window.pageYOffset > navOffset - navWrapper.offsetHeight
                ? "fixed"
                : "absolute";
        navWrapper.classList.toggle(
            "is--scrolling",
            window.pageYOffset > navOffset - navWrapper.offsetHeight
        );

        if (document.querySelector(".mod_pageImage")) {
            var navContainer = navWrapper;
            navOffset = navContainer.getBoundingClientRect().top + window.pageYOffset;
        } else {
            var mainsliderHeight = document.querySelector(".mainslider")
                ? document.querySelector(".mainslider").offsetHeight
                : 0;
            navOffset = mainsliderHeight;
            navWrapper.style.bottom = "auto";
            navWrapper.style.top = navOffset - navWrapper.offsetHeight + "px";
        }
    }

    scrollFunctions.push(detectIfScrolled);
    loadFunctions.push(detectIfScrolled);
    touchMoveFunctions.push(detectIfScrolled);
    ResizeFunctions.push(detectIfScrolled);
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
    loadFunctions.push(detectIfScrolled);
    touchMoveFunctions.push(detectIfScrolled);
    ResizeFunctions.push(detectIfScrolled);
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
    loadFunctions.push(detectIfScrolled);
    touchMoveFunctions.push(detectIfScrolled);
    ResizeFunctions.push(detectIfScrolled);
}

const isHeadImageAndMoveContent = document.querySelector(
    ".ce_rsce_headimagelogo.move-content"
);
if (isHeadImageAndMoveContent) {
    function movingContent() {
        const movingHeadimagelogoElement = document.querySelector(
            ".ce_rsce_headimagelogo.move-content"
        );

        // Prüfen, ob 'moved-content' bereits existiert
        let articleContent = document.querySelector(".moved-content");
        if (!articleContent) {
            // Erstellen des neuen 'article-content' div, wenn es noch nicht existiert
            articleContent = document.createElement("div");
            articleContent.classList.add("moved-content");

            // Sammeln aller Elemente nach 'move-content' und zum 'article-content' hinzufügen
            let nextElement = movingHeadimagelogoElement.nextElementSibling;
            while (nextElement) {
                articleContent.appendChild(nextElement);
                nextElement = movingHeadimagelogoElement.nextElementSibling;
            }

            // Einfügen des 'article-content' div nach 'move-content'
            movingHeadimagelogoElement.parentNode.insertBefore(
                articleContent,
                movingHeadimagelogoElement.nextSibling
            );
        }

        const headerElement = document.getElementById("header"); // Ersetzen Sie "header" mit der tatsächlichen ID Ihres Header-Elements

        // Überprüfen, ob beide Elemente existieren, bevor Sie ihre Höhen abrufen
        if (headerElement && movingHeadimagelogoElement) {
            const totalOffsetHeight =
                movingHeadimagelogoElement.offsetHeight - headerElement.offsetHeight;
            articleContent.style.marginTop = `${totalOffsetHeight}px`;
        } else {
            articleContent.style.marginTop = "0px"; // Setzen Sie einen Standardwert, wenn die Elemente nicht vorhanden sind
        }
    }

    //DomLoadFunctions.push(movingContent);
    loadFunctions.push(movingContent);
    ResizeFunctions.push(movingContent);
}

loadFunctions.push(setSwitchingcardsHeight);
loadFunctions.push(initializeSmoothScrolling);
loadFunctions.push(scrollToTop);
loadFunctions.push(setupFunctions);
loadFunctions.push(initializeMarginAdjustments);
loadFunctions.push(addBootstrapClasses);
loadFunctions.push(adjustTableResponsive);
loadFunctions.push(adjustFormLayout);
loadFunctions.push(addPlaceholders);

function executeScrollFunctions() {
    scrollFunctions.forEach((func) => func());
}

function executeLoadFunctions() {
    loadFunctions.forEach((func) => func());
}

function executeDomLoadFunctions() {
    DomLoadFunctions.forEach((func) => func());
}

function executeTouchFunctions() {
    touchMoveFunctions.forEach((func) => func());
}

function executeResizeFunctions() {
    ResizeFunctions.forEach((func) => func());
}

//Functions from run.js
window.addEventListener("DOMContentLoaded", executeDomLoadFunctions);
window.addEventListener("load", executeLoadFunctions);
window.addEventListener("scroll", executeScrollFunctions);
window.addEventListener("touchmove", executeTouchFunctions);
window.addEventListener("resize", executeResizeFunctions);
