const scrollFunctions = [];

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

// Sammle alle [data-aos] Elemente
var aosElements = document.querySelectorAll('[data-aos]');
var filteredElements = Array.from(aosElements).filter(function (element) {
    return !element.style.animationDelay;
});
// Eine Map, um Elemente basierend auf ihren übergeordneten Elementen zu gruppieren
var parentGroups = new Map();

filteredElements.forEach(function (elem) {
    // Finde das nächstgelegene übergeordnete Element
    var parent = elem.parentElement;

    // Prüfe, ob die Gruppe für dieses übergeordnete Element bereits existiert
    if (!parentGroups.has(parent)) {
        parentGroups.set(parent, []);
    }

    // Füge das aktuelle [data-aos] Element zur entsprechenden Gruppe hinzu
    parentGroups.get(parent).push(elem);
});

// Wende nun die Logik für jedes [data-aos] Element in jeder Gruppe separat an
parentGroups.forEach(function (group) {
    group.forEach(function (elem, index) {
        // Das erste Element bekommt kein Delay, für die nachfolgenden Elemente wird das Delay um 0.25s pro Element erhöht
        var delay = index * 0.35; // Das erste Element erhält 0s, das zweite 0.25s, das dritte 0.5s usw.
        elem.style.animationDelay = delay + "s";
    });
});

setTimeout(function () {
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
        offset: 50, // offset (in px) from the original trigger point
        //delay: 250000,
        //duration: 5000,
        once: true, // whether animation should happen only once - while scrolling down
        mirror: true, // whether elements should animate out while scrolling past them
        anchorPlacement: "top-bottom", // defines which position of the element regarding to window should trigger the animation
    });
}, 500); // 500ms Verzögerung vor der Initialisierung von AOS

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
        articleFirst.style.padding =
            "calc(var(--with-body-bg-spacing) * 2) 0 var(--with-body-bg-spacing) 0";
        articleFirst.style.marginTop = "calc(-1 * var(--with-body-bg-spacing))";
    }
};

// Rufen Sie die Funktion auf, um die CSS-Regeln anzuwenden, wenn das Dokument geladen wird

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

/* MEGAMENÜ */
const initializeMegaMenu = () => {
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

// Hauptfunktion zur Initialisierung von allem
const main = async () => {
    addStylesToArticlesWithBg();
    initializePopovers();
    initializeTooltips();
    initializeMegaMenu();
    initializeNavToggle();
};

// Ausführen der Hauptfunktion nachdem alle Promises aufgelöst wurden
Promise.all(promises)
    .then(main)
    .catch((script) => {
        console.error(`${script} failed to load`);
    });


const lazyLoadInstance = new LazyLoad({
    callback_loaded: onImageLoaded, function(element) {
        if (element.closest("header")) {
            const navWrapper = document.querySelector(".hc--bottom");
            if (navWrapper) {
                const navWrapperHeight = navWrapper.offsetHeight;
            }
        }
    },
});


var searchActivator = document.querySelector('.searchActivator');

if (searchActivator) {
    var searchCol = document.querySelector('.search-col');

    searchActivator.addEventListener('click', function () {
        if (searchCol) {
            searchCol.classList.toggle('is-visible');
        }
    });
}


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

function startCounter(element) {
    if (element.classList.contains("doneCounting")) {
        return;
    }
    element.classList.add("doneCounting");

    const fullText = element.textContent;
    const matches = fullText.match(/(\d+([.,]\d+)?)([^\d]*)/);
    if (!matches) return;

    const originalNumber = matches[1].replace(",", ".");
    const decimalPlaces = (originalNumber.split(".")[1] || []).length;
    const targetNumber = parseFloat(originalNumber);
    const text = matches[3];
    const duration = 2000;
    let startTime = null;

    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = timestamp - startTime;
        const progressPercentage = Math.min(progress / duration, 1);

        const current = progressPercentage * targetNumber;
        element.textContent = current.toFixed(decimalPlaces) + text;

        if (progress < duration) {
            requestAnimationFrame(step);
        } else {
            element.textContent = targetNumber.toFixed(decimalPlaces) + text;
        }
    }

    requestAnimationFrame(step);
}

const observer = new IntersectionObserver(
    (entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                startCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    },
    {
        rootMargin: "0px",
        threshold: 0.1,
    }
);

document.querySelectorAll(".count").forEach((el) => {
    observer.observe(el);
});

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


const type3Header = document.querySelector(".header--content.type--3");
if (type3Header) {
    function detectIfScrolled() {
        var navWrapper = document.querySelector(".header--content.type--3");
        if (!navWrapper) return;

        var navOffset;
        var mainslider = document.querySelector(".mainslider");
        var mainsliderHeight = mainslider ? mainslider.offsetHeight : 0;
        navOffset = mainsliderHeight;

        navWrapper.style.bottom = 'auto';
        navWrapper.style.top = (window.pageYOffset > navOffset - navWrapper.offsetHeight) ? '0px' : (navOffset - navWrapper.offsetHeight) + 'px';
        navWrapper.style.position = (window.pageYOffset > navOffset - navWrapper.offsetHeight) ? 'fixed' : 'absolute';
        navWrapper.classList.toggle("is--scrolling", window.pageYOffset > navOffset - navWrapper.offsetHeight);


        if (document.querySelector(".mod_pageImage")) {
            var navContainer = navWrapper;
            navOffset = navContainer.getBoundingClientRect().top + window.pageYOffset;
        } else {
            var mainsliderHeight = document.querySelector(".mainslider") ? document.querySelector(".mainslider").offsetHeight : 0;
            navOffset = mainsliderHeight;
            navWrapper.style.bottom = 'auto';
            navWrapper.style.top = (navOffset - navWrapper.offsetHeight) + 'px';
        }

    }

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

const isHeadImageAndMoveContent = document.querySelector(".ce_rsce_headimagelogo.move-content");

if (isHeadImageAndMoveContent) {
    function movingContent() {
        const movingHeadimagelogoElements = document.querySelectorAll(".ce_rsce_headimagelogo.move-content");

        movingHeadimagelogoElements.forEach((el) => {
            const nextElement = el.nextElementSibling;
            if (nextElement) {
                nextElement.style.marginTop = `${el.offsetHeight}px`;
            }
        });
    }

    scrollFunctions.push(movingContent);
}


window.addEventListener("scroll", () => {
    scrollFunctions.forEach((func) => func());
});

window.addEventListener("load", () => {
    scrollFunctions.forEach((func) => func());
});

window.addEventListener("touchmove", () => {
    scrollFunctions.forEach((func) => func());
});

window.addEventListener("resize", () => {
    scrollFunctions.forEach((func) => func());
});