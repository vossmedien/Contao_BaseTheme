import LazyLoad from "/files/base/layout/_vendor/node_modules/vanilla-lazyload/dist/lazyload.esm.js";
import { setupFunctions, resetCookies } from "./cookieManager.js";
import {
  changeAnchorLinks,
  changeNavLinksAfterLoad,
} from "./navigationHandling.js";
import { onImageLoaded } from "./elementHeightAdjustments.js";

//import { isOnScreen } from "./viewportChecks.js";
//import { animate } from "./animations.js";

const lazyLoadInstance = new LazyLoad({
  callback_loaded: onImageLoaded,
  function(element) {
    if (element.closest("header")) {
      const navWrapper = document.querySelector(".hc--bottom");
      if (navWrapper) {
        const navWrapperHeight = navWrapper.offsetHeight;
      }
    }
  },
});



window.addEventListener("cookiebar_save", setupFunctions);
window.addEventListener("scroll", changeAnchorLinks);


document.addEventListener("aos:in", ({ detail }) => {
  if (isFirstChild(detail)) {
    // Wenn das Element das erste Kind ist, setze das Delay zurück
    currentDelay = 0;
    clearTimeout(resetDelayTimer);
  }

  // Setze das Delay für das aktuelle Element
  detail.style.animationDelay = `${currentDelay}s`;
  currentDelay += delayIncrement;

  // Timer zurücksetzen
  clearTimeout(resetDelayTimer);
  resetDelayTimer = setTimeout(resetDelay, resetDelayTime);
});

// Weitere Logik zur Initialisierung
if (window.location.hash) {
  changeNavLinksAfterLoad();
}

function isFirstChild(element) {
  return (
    element.parentElement && element.parentElement.firstElementChild === element
  );
}

var delayIncrement = 0.15; // Inkrement für das Delay in Sekunden
var resetDelayTime = 500; // Zeit in Millisekunden, nach der das Delay zurückgesetzt wird
var currentDelay = 0;
var resetDelayTimer;

function resetDelay() {
  currentDelay = 0; // Delay zurücksetzen
}




const btn = document.querySelector(".reset-cookies");
if (btn) {
  btn.addEventListener("click", function (e) {
    e.preventDefault();
    resetCookies();
  });
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
