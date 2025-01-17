import {setSwitchingcardsHeight} from "./elementHeightAdjustments.js";
import {scrollToTop} from "./smoothScrolling.js";
import {setupFunctions} from "./cookieManager.js";
import {adjustPullElements} from "./marginPaddingAdjustments.js";
import {
    addBootstrapClasses,
    adjustTableResponsive,
    adjustContentBox
} from "./classStyleManipulation.js";
import {addPlaceholders} from "./floatingLabels.js";
import {
    changeAnchorLinks,
    changeNavLinksAfterLoad,
} from "./navigationHandling.js";
import {initVenoBox, initVideoLightbox, initImageLightbox} from "./lightboxHandling.js";

const scrollFunctions = [];
const loadFunctions = [];
const touchMoveFunctions = [];
const DomLoadFunctions = [];
const ResizeFunctions = [];

// Cache häufig verwendete DOM-Elemente
const cachedElements = {
    form: document.querySelector(".ce_form form"),
    mobileNavElement: document.querySelector("#mobileNav"),
    triggerElement: document.querySelector('a[href="#mobileNav"]'),
    body: document.querySelector('body'),
};

if (cachedElements.form) {
    cachedElements.form.addEventListener("submit", (e) => {
        setTimeout(addPlaceholders, 250);
    });
}

const initMobileNav = () => {
    const {mobileNavElement, triggerElement, body} = cachedElements;

    if (mobileNavElement && triggerElement) {
        const menu = new MmenuLight(mobileNavElement);

        const navigator = menu.navigation({
            theme: "dark"
        });
        const drawer = menu.offcanvas({
            position: 'right'
        });

        triggerElement.addEventListener('click', (evnt) => {
            evnt.preventDefault();
            body.classList.contains("mm-ocd-opened") ? drawer.close() : drawer.open();
        });

        mobileNavElement.querySelectorAll('a').forEach(item => {
            item.addEventListener('click', () => {
                drawer.close();
            });
        });
    }
};

const initAnimations = () => {
    const CONFIG = {
        // Mobile-Konfiguration (Bildschirmbreite <= 768px)
        mobile: {
            breakpoint: 768,
            delay: 0.1,
            rootMargin: '0px 0px -25% 0px',
            baseDelay: 0.1,
            visibilityThreshold: 0.3
        },

        // Desktop-Konfiguration (Bildschirmbreite > 768px)
        desktop: {
            delay: 0.1,
            rootMargin: '0px 0px -35% 0px',
            baseDelay: 0.15,
            visibilityThreshold: 0.4
        },

        animationClass: 'animate__animated'
    };

    let animatedElements = new Set();
    let observer;

    const getElementVisibilityThreshold = (element) => {
        const viewportHeight = window.innerHeight;
        const elementHeight = element.offsetHeight;
        const isMobile = window.innerWidth <= CONFIG.mobile.breakpoint;
        const baseThreshold = isMobile ? CONFIG.mobile.visibilityThreshold : CONFIG.desktop.visibilityThreshold;

        if (elementHeight > viewportHeight) {
            return Math.max(0.1, baseThreshold * (viewportHeight / elementHeight));
        }

        return baseThreshold;
    };

    const initAnimateElements = () => {
        const animateElements = document.querySelectorAll('[class*="animate__"]');
        animateElements.forEach(element => {
            const animateClasses = Array.from(element.classList)
                .filter(cls => cls.startsWith('animate__'));

            if (animateClasses.length > 0) {
                const animateClassString = animateClasses.join(' ');
                element.classList.remove(...animateClasses);
                element.setAttribute('data-animation', animateClassString);
            }
        });
    };

    const isElementInViewport = (element) => {
        const rect = element.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const windowWidth = window.innerWidth || document.documentElement.clientWidth;

        const visibleHeight = Math.min(rect.bottom, windowHeight) - Math.max(rect.top, 0);
        const totalHeight = rect.bottom - rect.top;
        const visibilityRatio = visibleHeight / totalHeight;

        const threshold = getElementVisibilityThreshold(element);

        return visibilityRatio >= threshold;
    };

    const getVisibleSiblings = (element) => {
        const container = element.closest('.team-members') || element.parentElement;
        if (!container) return [];

        return Array.from(container.querySelectorAll('[data-animation], [data-aos]'))
            .filter(el => !animatedElements.has(el) && isElementInViewport(el));
    };

    const animateElement = (element, visibleSiblings) => {
        if (animatedElements.has(element)) return false;

        const animateClass = element.getAttribute('data-animation') || element.getAttribute('data-aos');
        if (animateClass) {
            const hasExistingDelay = element.style.animationDelay ||
                element.getAttribute('data-animation-delay') ||
                window.getComputedStyle(element).animationDelay !== '0s';

            requestAnimationFrame(() => {
                element.classList.add(...animateClass.split(' '), CONFIG.animationClass);

                if (!hasExistingDelay) {
                    const isMobile = window.innerWidth <= CONFIG.mobile.breakpoint;
                    const {delay, baseDelay} = isMobile ? CONFIG.mobile : CONFIG.desktop;

                    if (visibleSiblings.length > 1) {
                        const elementIndex = visibleSiblings.indexOf(element);
                        element.style.animationDelay = `${baseDelay + (elementIndex * delay)}s`;
                    } else {
                        element.style.animationDelay = `${baseDelay}s`;
                    }
                }
            });
            animatedElements.add(element);
            return true;
        }
        return false;
    };

    const setupObserver = () => {
        const isMobile = window.innerWidth <= CONFIG.mobile.breakpoint;
        const {rootMargin} = isMobile ? CONFIG.mobile : CONFIG.desktop;

        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                const element = entry.target;
                const threshold = getElementVisibilityThreshold(element);

                if (entry.intersectionRatio >= threshold) {
                    if (!animatedElements.has(element)) {
                        const visibleSiblings = getVisibleSiblings(element);

                        if (visibleSiblings.length > 0) {
                            visibleSiblings.forEach(sibling => {
                                animateElement(sibling, visibleSiblings);
                                observer.unobserve(sibling);
                            });
                        } else {
                            animateElement(element, [element]);
                            observer.unobserve(element);
                        }
                    }
                }
            });
        }, {
            threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5],
            rootMargin
        });
    };

    const handleElements = () => {
        const elements = document.querySelectorAll('[data-animation], [data-aos]');
        elements.forEach(element => {
            if (!animatedElements.has(element)) {
                if (isElementInViewport(element)) {
                    const visibleSiblings = getVisibleSiblings(element);
                    if (visibleSiblings.length > 0) {
                        visibleSiblings.forEach(sibling => {
                            animateElement(sibling, visibleSiblings);
                            observer.unobserve(sibling);
                        });
                    }
                } else {
                    observer.observe(element);
                }
            }
        });
    };

    try {
        initAnimateElements();
        setupObserver();
        handleElements();

        scrollFunctions.push(handleElements);
        ResizeFunctions.push(handleElements);

    } catch (error) {
        console.error('Animation initialization failed:', error);
    }

    return () => {
        observer?.disconnect();
        const scrollIndex = scrollFunctions.indexOf(handleElements);
        if (scrollIndex > -1) scrollFunctions.splice(scrollIndex, 1);
        const resizeIndex = ResizeFunctions.indexOf(handleElements);
        if (resizeIndex > -1) ResizeFunctions.splice(resizeIndex, 1);
    };
};


const rotateImage = () => {
    const images = document.querySelectorAll('.rotateImage');
    let lastScrollTop = 0;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                window.addEventListener('scroll', rotateImages);
            } else if (entry.intersectionRatio === 0) {
                window.removeEventListener('scroll', rotateImages);
            }
        });
    }, {
        threshold: [0]
    });

    images.forEach(image => {
        image.rotation = 0;
        observer.observe(image);
    });

    const rotateImages = () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollDirection = scrollTop > lastScrollTop ? .5 : -.5;

        images.forEach(image => {
            image.rotation += scrollDirection;
            image.style.transform = `rotate(${image.rotation}deg)`;
        });

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    };
};

const addStylesToArticlesWithBg = () => {
    const articleLast = document.querySelector(".mod_article:last-child");
    const articleFirst = document.querySelector(".mod_article:first-child");

    if (articleLast?.querySelector("style.with-bg")) {
        articleLast.style.padding = "var(--with-body-bg-spacing) 0 calc(var(--with-body-bg-spacing) * 2) 0";
        articleLast.style.marginBottom = "calc(-1 * var(--with-body-bg-spacing))";
    }

    if (articleFirst?.querySelector("style.with-bg")) {
        articleFirst.style.padding = "calc(var(--with-body-bg-spacing) * 2) 0 var(--with-body-bg-spacing) 0";
    }
};

const initializePopovers = () => {
    const popoverTriggerList = [...document.querySelectorAll('[data-bs-toggle="popover"]')];
    popoverTriggerList.forEach((popoverTriggerEl) => {
        new bootstrap.Popover(popoverTriggerEl);
    });
};

const initializeTooltips = () => {
    const tooltipTriggerList = [...document.querySelectorAll('[data-bs-toggle="tooltip"]')];
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
};

const loadSearchParams = () => {
    const urlParams = new URLSearchParams(window.location.search);

    for (const [parameterName, parameterValue] of urlParams) {
        const inputField = document.querySelector(`input[name="${parameterName}"]`);
        if (inputField) {
            inputField.value = parameterValue;
        }
    }
};

DomLoadFunctions.push(
    initMobileNav,
    initAnimations,
    rotateImage,
    addStylesToArticlesWithBg,
    initializePopovers,
    initializeTooltips,
    loadSearchParams,
    initVenoBox,
    initVideoLightbox,
    initImageLightbox,
    setSwitchingcardsHeight,
    //initializeSmoothScrolling,
    changeNavLinksAfterLoad,
    scrollToTop,
    setupFunctions,
    //initializeMarginAdjustments,
    adjustPullElements,
    addBootstrapClasses,
    adjustTableResponsive,
    adjustContentBox,
    addPlaceholders
);

scrollFunctions.push(changeAnchorLinks);
ResizeFunctions.push(adjustPullElements);


const executeFunctions = (functions) => {
    functions.forEach((func) => {
        try {
            func();
        } catch (error) {
            console.error(`Error executing function ${func.name}:`, error);
        }
    });
};

window.addEventListener("DOMContentLoaded", () => executeFunctions(DomLoadFunctions));
window.addEventListener("load", () => executeFunctions(loadFunctions));
window.addEventListener("scroll", () => executeFunctions(scrollFunctions));
window.addEventListener("touchmove", () => executeFunctions(touchMoveFunctions));
window.addEventListener("resize", () => executeFunctions(ResizeFunctions));