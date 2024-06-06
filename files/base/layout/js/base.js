import {setupFunctions, resetCookies} from "./cookieManager.js";
import {
    changeAnchorLinks,
    changeNavLinksAfterLoad,
} from "./navigationHandling.js";


const lazyLoadInstance = new LazyLoad({});


window.addEventListener("cookiebar_save", setupFunctions);
const btn = document.querySelector(".reset-cookies");
if (btn) {
    btn.addEventListener("click", function (e) {
        e.preventDefault();
        resetCookies();
    });
}


window.addEventListener("scroll", changeAnchorLinks);
if (window.location.hash) {
    changeNavLinksAfterLoad();
}


//window.dispatchEvent(new Event("resize"));

// Clickhandler START

var searchActivator = document.querySelector(".searchActivator");

if (searchActivator) {
    var searchCol = document.querySelector(".search-col");

    searchActivator.addEventListener("touchstart", function () {
        if (searchCol) {
            searchCol.classList.toggle("is-visible");
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


const accordionIcons = document.querySelectorAll(".accordion-nav i");
accordionIcons.forEach((icon) => {
    icon.addEventListener("click", function () {
        this.closest("li").classList.toggle("expanded");
    });
});

// Clickhandler ENDE


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
