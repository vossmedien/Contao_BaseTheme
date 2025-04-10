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
            class: 'animate__animated',
            baseDelay: 0.1, // Basis-Delay für das erste Element in einer Gruppe (in Sekunden)
            increment: 0.15 // Inkrement für nachfolgende Elemente (in Sekunden)
        }
    };

    let animatedElements = new Set();
    let observer;
    // Cache für Parent-Elemente, um doppelte Verarbeitung im selben Observer-Callback zu vermeiden
    let processedParentsInCallback = new Set();

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

    const getImmediateAnimatableChildren = (container) => {
        // Find direct children that are animatable and not yet animated
        return Array.from(container.children)
            .filter(child => {
                return child.hasAttribute('data-animation') && !animatedElements.has(child);
            });
    };

    const animateElement = (element, indexInGroup) => {
        // Check if element exists, has animation data and hasn't been animated yet
        if (!element || animatedElements.has(element) || !element.hasAttribute('data-animation')) {
            return;
        }

        // Mark as animating immediately
        animatedElements.add(element);

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

        // Calculate and set staggered delay
        const delay = CONFIG.animation.baseDelay + indexInGroup * CONFIG.animation.increment;
        element.style.animationDelay = `${delay}s`;
        // console.log(`Animating ${element.tagName}#${element.id} with delay: ${delay}s`);


        requestAnimationFrame(() => {
            // Ensure no fixed delay is applied via attribute if we overwrite it
            // element.style.removeProperty('animation-delay'); // Already set above
            element.classList.add(...animateClass.split(' '), CONFIG.animation.class);

            // Optional: Clean up classes after animation finishes
            element.addEventListener('animationend', () => {
                 // Example: Remove animation classes to prevent re-triggering issues
                 // element.classList.remove(CONFIG.animation.class, ...animateClass.split(' '));
                 // Or set a state like element.dataset.animated = true;
            }, { once: true });

            // No longer need to trigger the next sibling here
        });
    };

    const setupObserver = () => {
        const isMobile = window.innerWidth <= CONFIG.mobile.breakpoint;
        // const { rootMargin: mobileRootMargin } = CONFIG.mobile; // Original mobile margin
        // const desktopRootMargin = '0px 0px -15% 0px'; // Adjusted desktop margin from previous step
        // Set bottom margin to 0% for both mobile and desktop to trigger sooner
        const rootMargin = '0px 0px 0% 0px';

        observer = new IntersectionObserver((entries) => {
            // Clear processed parents for this callback cycle
             processedParentsInCallback.clear();

            // Collect parents of intersecting, non-animated elements
            const parentsToProcess = new Map();
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.hasAttribute('data-animation') && !animatedElements.has(entry.target)) {
                    const parent = entry.target.parentElement;
                     // Ensure parent exists before adding
                     if (parent && !processedParentsInCallback.has(parent)) {
                         if (!parentsToProcess.has(parent)) {
                             parentsToProcess.set(parent, []);
                         }
                         // We only need to know *which* parents have newly visible children
                         // The actual children will be gathered later.
                         // Storing entry.target helps debug but isn't strictly needed for the group logic.
                         // parentsToProcess.get(parent).push(entry.target);
                     }
                }
            });

            if (parentsToProcess.size === 0) return;

            // Process each parent group
            parentsToProcess.forEach((_, parent) => {
                // Mark this parent as processed for this callback instance
                 processedParentsInCallback.add(parent);

                // Find *all* animatable children of this parent that are not yet animated
                const animatableSiblings = Array.from(parent.children)
                    .filter(child => child.hasAttribute('data-animation') && !animatedElements.has(child));

                if (animatableSiblings.length === 0) return;

                // Sort by DOM order to ensure sequential animation delays
                animatableSiblings.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1);

                // Animate all eligible siblings in this group with staggered delay
                animatableSiblings.forEach((element, index) => {
                    // Double-check element hasn't been animated by another process/entry
                    if (!animatedElements.has(element)) {
                         animateElement(element, index); // Pass index for delay calculation
                         observer.unobserve(element); // Unobserve once animation starts
                    }
                });
            });

        }, {
            threshold: [0.01, 0.1], // Reduced thresholds slightly, starting near 0
            rootMargin
        });
    };

    const handleElements = () => {
        const elements = document.querySelectorAll('[data-animation]');
        elements.forEach(element => {
            // Observe elements that haven't been animated yet.
            // The observer callback will handle triggering the animation when visible.
            if (!animatedElements.has(element)) {
                observer.observe(element);
            } else {
                // If already animated, ensure it's not observed.
                 observer.unobserve(element);
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

        // Keep handleElements for resize events if needed, remove potential old ones first
        let resizeIndexOld = ResizeFunctions.findIndex(f => f.name === 'handleElements');
        while(resizeIndexOld > -1) { // Remove all instances if multiple exist
            ResizeFunctions.splice(resizeIndexOld, 1);
            resizeIndexOld = ResizeFunctions.findIndex(f => f.name === 'handleElements');
        }
        ResizeFunctions.push(handleElements); // Add the new version for resize handling

    } catch (error) {
        console.error('Animation initialization failed:', error);
    }

    return () => {
        observer?.disconnect();
        // Ensure the correct handleElements reference is removed on cleanup from ResizeFunctions
        const resizeIndex = ResizeFunctions.findIndex(f => f.name === 'handleElements');
        if (resizeIndex > -1) ResizeFunctions.splice(resizeIndex, 1);
        // Clear animated elements set if animations should re-run on dynamic content changes etc.
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