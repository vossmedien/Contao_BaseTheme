import {
    getCSSVariableValue, setActiveLink
} from "./smoothScrolling.js";


export function changeAnchorLinks() {
    const scrollPos = window.pageYOffset;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const scrollOffset = getCSSVariableValue('--bs-scrolloffset');

    const links = Array.from(document.querySelectorAll(
        '#mainNav a[href*="#"]:not(.invisible), .onepagenavi--wrapper a'
    ));

    let activeLink = null;

    // Check if scrolled close to the bottom
    if (scrollPos + windowHeight > documentHeight - 50) {
        const lastLink = links[links.length - 1];
        const lastLinkHref = lastLink.getAttribute("href");
        const lastElementId = lastLinkHref.split('#')[1];
        const lastElement = document.getElementById(lastElementId);

        if (lastElement) {
            activeLink = lastLink;
        }
    } else {
        for (let i = links.length - 1; i >= 0; i--) {
            const currElement = links[i];
            const currLink = currElement.getAttribute("href");
            const refElementId = currLink.split('#')[1];
            const refElement = document.getElementById(refElementId);

            if (refElement) {
                const refElementPos = refElement.getBoundingClientRect().top + window.pageYOffset - scrollOffset;

                // Check if the top of the section is visible
                if (scrollPos >= refElementPos - 5) {
                    activeLink = currElement;
                    break; // Stop at the first section that is visible
                }
            }
        }
    }

    if (activeLink) {
        setActiveLink(activeLink);
    }
}

export function changeNavLinksAfterLoad() {
    const hash = window.location.hash;

    document.querySelectorAll("#mobileNav li > *, #mainNav li > *, .onepagenavi--wrapper li > *").forEach(currElement => {
        if (currElement.getAttribute("href") === hash) {
            setActiveLink(currElement);
        } else if (hash === '' && currElement.getAttribute("href") === "#top") {
            setActiveLink(currElement);
        }
    });

    // Scroll to the correct position after setting the active link
    if (hash) {
        setTimeout(() => {
            const target = document.querySelector(hash);
            if (target) {
                const scrollOffset = getCSSVariableValue('--bs-scrolloffset');
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - scrollOffset;
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        }, 0);
    }

    changeAnchorLinks();
}


