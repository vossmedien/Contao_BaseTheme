import { changeAnchorLinks } from "./navigationHandling.js";

// IIFE für initiales Hash-Handling
(() => {
    if (window.location.hash) {
        window.scrollTo(0, 0);
        const scrollToAnchor = () => {
            window.removeEventListener('load', scrollToAnchor);
            handleInitialHash();
        };
        window.addEventListener('load', scrollToAnchor);
    }
})();

export function getCSSVariableValue(variableName) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
    const numericValue = parseFloat(value);
    return isNaN(numericValue) ? 100 : numericValue;
}

export function setActiveLink(element) {
    document.querySelectorAll("ul .active").forEach(el => {
        el.classList.remove("active");
    });

    if (element) {
        element.classList.add("active");
    }
}

function handleInitialHash() {
    const hash = window.location.hash;
    if (hash) {
        const targetId = hash.substring(1);
        setTimeout(() => {
            scrollToTarget(targetId);
        }, 300);
    }
}

function scrollToTarget(targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;

    const scrollOffset = getCSSVariableValue('--bs-scrolloffset');
    const currentScroll = window.pageYOffset;
    const targetRect = target.getBoundingClientRect();
    const targetOffset = targetRect.top + window.pageYOffset;

    const allLazyImages = document.querySelectorAll('.lazy');
    const relevantLazyImages = Array.from(allLazyImages).filter(img => {
        const imgOffset = img.getBoundingClientRect().top + window.pageYOffset;
        return imgOffset > currentScroll && imgOffset <= targetOffset;
    });

    let loadedImages = 0;

    const performFinalScroll = () => {
        const finalTargetRect = target.getBoundingClientRect();
        const finalPosition = finalTargetRect.top + window.pageYOffset - scrollOffset;

        window.scrollTo({
            top: finalPosition,
            behavior: 'smooth'
        });

        setTimeout(() => {
            const checkPosition = target.getBoundingClientRect();
            if (Math.abs(checkPosition.top - scrollOffset) > 5) {
                window.scrollTo({
                    top: window.pageYOffset + checkPosition.top - scrollOffset,
                    behavior: 'smooth'
                });
            }

            // Setze aktive Links nach dem Scrollen
            const activeAnchor = document.querySelector(`a[href="#${targetId}"]`);
            if (activeAnchor) {
                setActiveLink(activeAnchor);
                changeAnchorLinks();
            }
        }, 300);

        window.location.hash = `#${targetId}`;
    };

    let targetPosition = targetRect.top + window.pageYOffset - scrollOffset;
    window.scrollTo({
        top: targetPosition,
        behavior: 'smooth'
    });

    if (relevantLazyImages.length > 0) {
        relevantLazyImages.forEach((img) => {
            if (img.complete) {
                loadedImages++;
                if (loadedImages === relevantLazyImages.length) {
                    performFinalScroll();
                }
            } else {
                img.addEventListener('load', () => {
                    loadedImages++;
                    if (loadedImages === relevantLazyImages.length) {
                        performFinalScroll();
                    }
                });
            }
        });
    } else {
        performFinalScroll();
    }
}

function scrollToAnchor(e) {
    e.preventDefault();
    const targetId = this.getAttribute('href').substring(1);
    scrollToTarget(targetId);
}

export function scrollToTop() {
    const scrollToTopBtn = document.querySelectorAll(".scrollToTop, .BodyScrollToTop, .scrolltop");

    scrollToTopBtn.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    });

    window.addEventListener('scroll', function () {
        if (document.documentElement.scrollTop > 50) {
            scrollToTopBtn.forEach(btn => btn.classList.add("visible"));
        } else {
            scrollToTopBtn.forEach(btn => btn.classList.remove("visible"));
        }
    });
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    scrollToTop();

    document.querySelectorAll('a[href^="#"]:not(.reset-cookies,.navActivator,[href*="Nav"],.venobox,.mm-btn)').forEach(link => {
        link.addEventListener('click', scrollToAnchor);
    });

    window.addEventListener('scroll', changeAnchorLinks);
    changeAnchorLinks();
    handleInitialHash();
});

window.addEventListener('hashchange', (event) => {
    const newHash = window.location.hash;
    if (newHash) {
        const targetId = newHash.substring(1);
        scrollToTarget(targetId);
    }
});