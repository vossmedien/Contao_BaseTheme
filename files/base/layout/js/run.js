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
            rootMargin: '0px 0px -25% 0px',
            visibilityThreshold: 0.1
        },
        desktop: {
            rootMargin: '0px 0px -15% 0px',
            visibilityThreshold: 0.2
        },
        animation: {
            class: 'animate__animated'
        }
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

        const visibleHeight = Math.min(rect.bottom, windowHeight) - Math.max(rect.top, 0);
        const totalHeight = rect.bottom - rect.top;
        const visibilityRatio = visibleHeight / totalHeight;

        return visibilityRatio >= getElementVisibilityThreshold(element);
    };

    const getImmediateAnimatableChildren = (container) => {
        // Find direct children that are animatable and not yet animated
        return Array.from(container.children)
            .filter(child => {
                return child.hasAttribute('data-animation') && !animatedElements.has(child);
            });
    };

    const animateElement = (element) => {
        // Check if element exists, has animation data and hasn't been animated yet
        if (!element || animatedElements.has(element) || !element.hasAttribute('data-animation')) {
            return;
        }

        // Explicitly set animation duration from CSS variable
        try {
            const duration = getComputedStyle(document.documentElement).getPropertyValue('--animate-duration').trim();
            if (duration) {
                element.style.animationDuration = duration;
            }
        } catch (e) {
            console.warn('Could not apply --animate-duration', e);
            // Set a default duration as fallback
            element.style.animationDuration = '1s';
        }

        const animateClass = element.getAttribute('data-animation');
        if (!animateClass) {
            console.warn('Animation class not found for:', element);
            return;
        }

        animatedElements.add(element); // Mark as animating immediately

        requestAnimationFrame(() => {
            // Ensure no fixed delay is applied
            element.style.removeProperty('animation-delay');
            element.classList.add(...animateClass.split(' '), CONFIG.animation.class);

            // Listen for animation end to trigger the next sibling
            const animationEndHandler = (event) => {
                // Ensure the event is for the element itself, not a child animation
                if (event.target !== element) {
                    return;
                }

                let nextSibling = element.nextElementSibling;
                let foundNextAnimatable = false;
                // Find the next sibling that should be animated
                while (nextSibling) {
                    if (nextSibling.hasAttribute('data-animation')) {
                        // Directly trigger animation for the next sibling in the sequence
                        if (isElementInViewport(nextSibling)) {
                            // Use setTimeout to introduce the desired delay
                            try {
                                const delayStr = getComputedStyle(document.documentElement).getPropertyValue('--animate-delay').trim() || '0ms';
                                const delayMs = parseFloat(delayStr);
                                if (!isNaN(delayMs) && delayMs > 0) {
                                    setTimeout(() => {
                                        animateElement(nextSibling);
                                    }, delayMs);
                                } else {
                                    // If delay is 0 or invalid, run immediately
                                    animateElement(nextSibling);
                                }
                            } catch (e) {
                                console.warn('Could not apply --animate-delay, running next animation immediately.', e);
                                animateElement(nextSibling); // Fallback: run immediately
                            }
                        } else {
                            // If the next sibling is not visible, do nothing.
                            // The observer is already watching it and will trigger
                            // animateElement when it becomes visible and canStartAnimation is true.
                        }
                        foundNextAnimatable = true;
                        break; // Only trigger the immediate next one in the chain
                    }
                    nextSibling = nextSibling.nextElementSibling;
                }

                // Always unobserve the current element once its animation (and check for next) is done.
                observer.unobserve(element);
            };

            element.addEventListener('animationend', animationEndHandler, { once: true });
        });
    };

    // Helper function to check if an element is the first in its sibling group
    // that needs to be animated (no preceding siblings waiting for animation)
    const canStartAnimation = (element) => {
        if (!element || !element.hasAttribute('data-animation') || animatedElements.has(element)) {
            return false; // Already animated, not animatable, or doesn't exist
        }

        // Check previous siblings
        let prevSibling = element.previousElementSibling;
        while (prevSibling) {
            if (prevSibling.hasAttribute('data-animation') && !animatedElements.has(prevSibling)) {
                // A previous sibling needs to animate first, so this one can't start yet
                return false;
            }
            prevSibling = prevSibling.previousElementSibling;
        }
        // No un-animated previous siblings found, this element can start a chain
        return true;
    };

    const setupObserver = () => {
        const isMobile = window.innerWidth <= CONFIG.mobile.breakpoint;

        // Adjust rootMargin: Use mobile margin always OR make desktop bottom margin less aggressive
        const desktopRootMargin = '0px 0px -5% 0px'; // Less aggressive bottom margin
        const { rootMargin: mobileRootMargin } = CONFIG.mobile;
        const rootMargin = isMobile ? mobileRootMargin : desktopRootMargin;

        observer = new IntersectionObserver((entries) => {
            const elementsToPotentiallyAnimate = new Set();

            // First pass: Identify elements that *could* start based on intersection and not being animated yet
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.hasAttribute('data-animation') && !animatedElements.has(entry.target)) {
                    elementsToPotentiallyAnimate.add(entry.target);
                }
            });

            if (elementsToPotentiallyAnimate.size === 0) return;

            // Group by parent to handle siblings correctly
            const parentGroups = new Map();
            elementsToPotentiallyAnimate.forEach(element => {
                const parent = element.parentElement;
                if (!parentGroups.has(parent)) {
                    parentGroups.set(parent, []);
                }
                parentGroups.get(parent).push(element);
            });

            // Process each parent group to find the first element to animate
            parentGroups.forEach(targets => {
                // Sort targets by their DOM order to ensure sequential animation
                targets.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);

                // Iterate through the sorted, potentially animatable elements in this group
                for (const target of targets) {
                    // Check previous animatable sibling *again* right before animating
                    if (animatedElements.has(target)) continue; // Skip if already processing/animated

                    let previousAnimatableSibling = target.previousElementSibling;
                    while (previousAnimatableSibling && !previousAnimatableSibling.hasAttribute('data-animation')) {
                        previousAnimatableSibling = previousAnimatableSibling.previousElementSibling;
                    }

                    let canThisElementStart = false;

                    if (!previousAnimatableSibling || animatedElements.has(previousAnimatableSibling)) {
                        // Case 1 & 2: No previous, or previous is already animated.
                        canThisElementStart = true;
                    } else {
                        // Case 3: Previous exists but is not animated yet.
                        // Check if the previous sibling is currently OUTSIDE the viewport.
                        if (!isElementInViewport(previousAnimatableSibling)) {
                            // If previous is NOT visible, the current element can start the chain.
                            canThisElementStart = true;
                        }
                        // Else (previous exists, not animated, IS visible): wait for the chain.
                    }

                    if (canThisElementStart) {
                        animateElement(target);
                        break; // Start only the first eligible element in this group and break
                    }
                }
            });

        }, {
            threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5], // Keep multiple thresholds
            rootMargin
        });
    };

    const handleElements = () => {
        const elements = document.querySelectorAll('[data-animation]');
        elements.forEach(element => {
            // Simplified: Always observe non-animated elements.
            // The observer callback logic now handles when to start animations.
            if (!animatedElements.has(element)) {
                observer.observe(element);
            } else {
                // Optional: If element IS already animated, we might consider unobserving it
                // if we are certain it won't need re-animation.
                // observer.unobserve(element);
            }
        });
    };

    try {
        initAnimateElements();
        setupObserver();
        handleElements(); // Initial check and setup observers

        // Remove the scroll listener for handleElements as Observer handles scrolling into view
        const scrollIndexOld = scrollFunctions.findIndex(f => f.name === 'handleElements'); // Find potentially old handleElements
         if (scrollIndexOld > -1) scrollFunctions.splice(scrollIndexOld, 1);

        // Keep handleElements for resize events
        const resizeIndexOld = ResizeFunctions.findIndex(f => f.name === 'handleElements'); // Find potentially old handleElements
        if (resizeIndexOld > -1) ResizeFunctions.splice(resizeIndexOld, 1); // Remove if exists
        ResizeFunctions.push(handleElements); // Add the new version

    } catch (error) {
        console.error('Animation initialization failed:', error);
    }

    return () => {
        observer?.disconnect();
        // Ensure the correct handleElements reference is removed on cleanup
        const resizeIndex = ResizeFunctions.indexOf(handleElements);
        if (resizeIndex > -1) ResizeFunctions.splice(resizeIndex, 1);
        // Consider clearing animatedElements if dynamic content can reset animations
        // animatedElements.clear();
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

// Keep handleElements in ResizeFunctions
const resizeIndexOld = ResizeFunctions.findIndex(f => f.name === 'handleElements');
if (resizeIndexOld === -1) { // Avoid adding duplicates if run multiple times
    ResizeFunctions.push(adjustPullElements); // Keep original adjustPullElements
}
// Note: The push inside initAnimations adds the new handleElements to ResizeFunctions

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