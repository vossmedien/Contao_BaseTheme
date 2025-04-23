import {scrollToTop} from "./smoothScrolling.js";
import {setupFunctions} from "./cookieManager.js";
import {adjustPullElements} from "./marginPaddingAdjustments.js";
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
        mobile: {
            breakpoint: 768,
            rootMargin: '0px 0px -5% 0px',
            visibilityThreshold: 0.1
        },
        desktop: {
            rootMargin: '0px 0px -2.5% 0px',
            visibilityThreshold: 0.2
        },
        animation: {
            class: 'animate__animated',
            baseDelay: 0.1,
            increment: 0.15
        }
    };

    let animatedElements = new Set();
    let observer;
    let processedParentsInCallback = new Set(); // Verhindert doppelte Verarbeitung pro Callback

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
        document.querySelectorAll('[class*="animate__"]:not([data-animation])').forEach(element => {
             // Check if the element ALREADY has no-animation set, skip if so
             if (element.getAttribute('data-animation') === 'no-animation') return;

             const animateClasses = Array.from(element.classList)
                 .filter(cls => cls.startsWith('animate__') && cls !== 'animate__animated'); // Exclude base class

             if (animateClasses.length > 0) {
                 const animateClassString = animateClasses.join(' ');
                 element.classList.remove(...animateClasses, 'animate__animated'); // Remove animation and state class
                 element.setAttribute('data-animation', animateClassString);
                 element.style.visibility = 'hidden'; // Hide until animated
             }
         });
         // Ensure elements explicitly marked with no-animation are visible
         document.querySelectorAll('[data-animation="no-animation"]').forEach(el => {
            el.style.visibility = 'visible';
         });
    };

    const animateElement = (element, indexInGroup) => {
        const animationValue = element.getAttribute('data-animation');
        // Exit if no animation defined, already animated, or explicitly set to no-animation
        if (!element || animatedElements.has(element) || !animationValue || animationValue === 'no-animation') {
            // Ensure visibility is set correctly for no-animation elements even if somehow targeted
            if (animationValue === 'no-animation') {
                element.style.visibility = 'visible';
            }
            return;
        }

        animatedElements.add(element);
        observer?.unobserve(element); // Stop observing

        try {
            const duration = getComputedStyle(document.documentElement).getPropertyValue('--animate-duration').trim() || '1s';
            element.style.animationDuration = duration;
        } catch (e) {
            console.warn('Could not apply --animate-duration', e);
            element.style.animationDuration = '1s';
        }

        const animateClass = animationValue; // Already checked it's not null/no-animation

        const delay = CONFIG.animation.baseDelay + indexInGroup * CONFIG.animation.increment;
        element.style.animationDelay = `${delay}s`;

        requestAnimationFrame(() => {
            element.style.visibility = 'visible'; // Make visible before starting animation
            element.classList.add(...animateClass.split(' '), CONFIG.animation.class);

            element.addEventListener('animationend', () => {
                 // Find ALL non-no-animation descendants that need animating AFTER parent finishes
                 const descendantsToAnimate = Array.from(element.querySelectorAll('[data-animation]:not([data-animation="no-animation"])'))
                     .filter(descendant => !animatedElements.has(descendant)); // Filter out those already processed

                 if (descendantsToAnimate.length > 0) {
                    descendantsToAnimate.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);

                    descendantsToAnimate.forEach((descendant, index) => {
                        animateElement(descendant, index);
                    });
                 }
            }, { once: true });
        });
    };

   const setupObserver = () => {
        const rootMargin = window.innerWidth <= CONFIG.mobile.breakpoint ? CONFIG.mobile.rootMargin : CONFIG.desktop.rootMargin;

        observer = new IntersectionObserver((entries) => {
             processedParentsInCallback.clear();
             const elementsToPotentiallyAnimate = new Map();

             entries.forEach(entry => {
                  const animationValue = entry.target.getAttribute('data-animation');
                  if (entry.isIntersecting && animationValue && animationValue !== 'no-animation' && !animatedElements.has(entry.target)) {
                     const element = entry.target;
                     // Find closest parent that *can* be animated (not no-animation)
                     const closestAnimatingParent = element.parentElement?.closest('[data-animation]:not([data-animation="no-animation"])');

                     const parentContainer = element.parentElement || document.body;
                     // Filter siblings to exclude no-animation
                     const animatableSiblings = Array.from(parentContainer.children)
                         .filter(child => {
                             const anim = child.getAttribute('data-animation');
                             return anim && anim !== 'no-animation';
                         });
                     const indexInGroup = animatableSiblings.findIndex(el => el === element);

                     if (!closestAnimatingParent || animatedElements.has(closestAnimatingParent)) {
                         if (!elementsToPotentiallyAnimate.has(element)) {
                            elementsToPotentiallyAnimate.set(element, Math.max(0, indexInGroup));
                         }
                     } else {
                         const parentEntry = entries.find(e => e.target === closestAnimatingParent);
                         if (parentEntry && parentEntry.isIntersecting && !animatedElements.has(closestAnimatingParent)) {
                            const grandParentContainer = closestAnimatingParent.parentElement || document.body;
                             // Filter parent siblings to exclude no-animation
                            const parentAnimatableSiblings = Array.from(grandParentContainer.children)
                                .filter(child => {
                                    const anim = child.getAttribute('data-animation');
                                    return anim && anim !== 'no-animation';
                                });
                            const parentIndexInGroup = parentAnimatableSiblings.findIndex(el => el === closestAnimatingParent);

                            if (!elementsToPotentiallyAnimate.has(closestAnimatingParent)){
                                elementsToPotentiallyAnimate.set(closestAnimatingParent, Math.max(0, parentIndexInGroup));
                            }
                         }
                     }
                 } else if (entry.target.getAttribute('data-animation') === 'no-animation') {
                      // Ensure no-animation elements become visible when they intersect, if hidden initially
                      entry.target.style.visibility = 'visible';
                      observer?.unobserve(entry.target); // No need to observe further
                 }
             });

             if (elementsToPotentiallyAnimate.size === 0) return;

             const sortedElements = Array.from(elementsToPotentiallyAnimate.keys()).sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);

             sortedElements.forEach(element => {
                const index = elementsToPotentiallyAnimate.get(element);
                if (!animatedElements.has(element)){
                    animateElement(element, index);
                }
             });

         }, {
             threshold: [0.1, 0.2],
             rootMargin
         });
    };


    const handleElements = () => {
        // Observe only elements that have a data-animation attribute which is not "no-animation"
        const elements = document.querySelectorAll('[data-animation]:not([data-animation="no-animation"])');
        elements.forEach(element => {
            if (!animatedElements.has(element)) {
                observer.observe(element);
            } else {
                 observer.unobserve(element);
            }
        });
        // Ensure elements marked no-animation that might have been missed are visible
         document.querySelectorAll('[data-animation="no-animation"]').forEach(el => {
            el.style.visibility = 'visible';
            observer?.unobserve(el); // Also unobserve them explicitly
         });
    };

    try {
        initAnimateElements();
        setupObserver();
        handleElements();

        // Cleanup potentially old listeners (safer approach)
        const oldResizeHandler = ResizeFunctions.find(f => f.name === 'handleElementsResizeWrapper');
        if(oldResizeHandler) {
            const index = ResizeFunctions.indexOf(oldResizeHandler);
            if (index > -1) ResizeFunctions.splice(index, 1);
        }

        // Debounced resize handler
        let resizeTimeout;
        const handleElementsResizeWrapper = () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                 // Potentially disconnect and reconnect observer if rootMargin needs changing
                 // observer?.disconnect();
                 // setupObserver(); // Re-setup observer with potentially new rootMargin
                 handleElements(); // Re-observe elements (useful for dynamically added content)
            }, 250); // Debounce resize events
        };
        ResizeFunctions.push(handleElementsResizeWrapper);


    } catch (error) {
        console.error('Animation initialization failed:', error);
    }

    // Return a cleanup function
    return () => {
        observer?.disconnect();
        const resizeIndex = ResizeFunctions.findIndex(f => f.name === 'handleElementsResizeWrapper');
        if (resizeIndex > -1) ResizeFunctions.splice(resizeIndex, 1);
        animatedElements.clear(); // Clear state if needed
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
    changeNavLinksAfterLoad,
    scrollToTop,
    setupFunctions,
    adjustPullElements,
    addPlaceholders
);

scrollFunctions.push(changeAnchorLinks);

// Ensure adjustPullElements is in ResizeFunctions if not already added by the loop above
const adjustPullIndex = ResizeFunctions.findIndex(f => f.name === 'adjustPullElements');
if (adjustPullIndex === -1) {
    ResizeFunctions.push(adjustPullElements);
}
// The handleElements function is added to ResizeFunctions within initAnimations itself.

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