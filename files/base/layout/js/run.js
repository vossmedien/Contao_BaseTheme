import {setSwitchingcardsHeight} from "./elementHeightAdjustments.js";
import {initializeSmoothScrolling, scrollToTop} from "./smoothScrolling.js";
import {setupFunctions} from "./cookieManager.js";
import {initializeMarginAdjustments} from "./marginPaddingAdjustments.js";
import {
    addBootstrapClasses,
    adjustTableResponsive,
} from "./classStyleManipulation.js";
import {addPlaceholders} from "./floatingLabels.js";

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

function initAnimations() {
    document.querySelectorAll('*:not([data-aos])[class*="animate__"]').forEach(function (element) {
        var classes = Array.from(element.classList)
            .filter(function (className) {
                return className.startsWith('animate__');
            })
            .join(' ');

        element.classList.remove(...classes.split(' '));
        element.setAttribute('data-aos', classes);
    });

    var elements = document.querySelectorAll('[data-aos]');
    var observer = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var container = entry.target.parentElement;
                var animateElements = Array.from(container.children).filter(function (element) {
                    return element.getAttribute('data-aos') && element.getAttribute('data-aos').includes('animate__');
                });


                animateElements.forEach(function (element, index) {
                    var animateClass = element.getAttribute('data-aos').match(/animate__[\w-]+/)[0];
                    //var newAnimateClass = animateClass.replace('animate-', 'animate__');

                    //element.classList.remove(animateClass);
                    element.classList.add(animateClass, 'animate__animated');
                    element.style.animationDelay = (index * 0.2) + 's';
                });

                animateElements.forEach(function (element) {
                    observer.unobserve(element);
                });
            }
        });
    }, {threshold: 0.25});

    elements.forEach(function (element) {
        observer.observe(element);
    });
}

DomLoadFunctions.push(initAnimations);


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
DomLoadFunctions.push(addStylesToArticlesWithBg);


const initializePopovers = () => {
    const popoverTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="popover"]'),
    ];
    popoverTriggerList.forEach((popoverTriggerEl) => {
        new bootstrap.Popover(popoverTriggerEl);
    });
};
DomLoadFunctions.push(initializePopovers);


const initializeTooltips = () => {
    const tooltipTriggerList = [
        ...document.querySelectorAll('[data-bs-toggle="tooltip"]'),
    ];
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
};
DomLoadFunctions.push(initializeTooltips);


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


DomLoadFunctions.push(setSwitchingcardsHeight);
DomLoadFunctions.push(initializeSmoothScrolling);
DomLoadFunctions.push(scrollToTop);
DomLoadFunctions.push(setupFunctions);
DomLoadFunctions.push(initializeMarginAdjustments);
DomLoadFunctions.push(addBootstrapClasses);
DomLoadFunctions.push(adjustTableResponsive);
DomLoadFunctions.push(addPlaceholders);

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
