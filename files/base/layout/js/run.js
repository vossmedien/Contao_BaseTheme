import {setSwitchingcardsHeight} from "./elementHeightAdjustments.js";
import {initializeSmoothScrolling, scrollToTop} from "./smoothScrolling.js";
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

        const navigator = menu.navigation();
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
    const initAnimateElements = () => {
        const animateElements = document.querySelectorAll('[class*="animate__"]');
        animateElements.forEach(element => {
            const animateClasses = Array.from(element.classList).filter(cls => cls.startsWith('animate__'));
            if (animateClasses.length > 0) {
                const animateClassString = animateClasses.join(' ');
                element.classList.remove(...animateClasses);
                element.setAttribute('data-animation', animateClassString);
            }
        });
    };

    const initAosElements = () => {
        return document.querySelectorAll('[data-aos]');
    };

    const observeElements = (elements) => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const parentContainer = element.parentElement;

                    // Finden Sie alle zu animierenden Elemente im selben unmittelbaren Elternelement
                    const siblingElements = Array.from(parentContainer.children).filter(child =>
                        child.hasAttribute('data-animation') || child.hasAttribute('data-aos')
                    );

                    siblingElements.forEach((siblingElement, index) => {
                        const animateClass = siblingElement.getAttribute('data-animation') || siblingElement.getAttribute('data-aos');

                        if (animateClass) {
                            requestAnimationFrame(() => {
                                siblingElement.classList.add(...animateClass.split(' '), 'animate__animated');
                                siblingElement.style.animationDelay = `${index * 0.2}s`;
                            });

                            observer.unobserve(siblingElement);
                        }
                    });
                }
            });
        }, {threshold: 0.1, rootMargin: '50px'});

        elements.forEach(element => observer.observe(element));
    };

    initAnimateElements();
    const aosElements = initAosElements();
    const allAnimatedElements = [...document.querySelectorAll('[data-animation]'), ...aosElements];
    observeElements(allAnimatedElements);
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
    initializeSmoothScrolling,
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