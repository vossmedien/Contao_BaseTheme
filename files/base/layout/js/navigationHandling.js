import {
    getCSSVariableValue, setActiveLink
} from "./smoothScrolling.js";

const links = Array.from(document.querySelectorAll(
    '#mainNav a[href*="#"]:not(.invisible), .onepagenavi--wrapper a, #mobileNav a[href*="#"]:not(.invisible)'
));

export function changeAnchorLinks() {
    const scrollPos = window.pageYOffset;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const scrollOffset = getCSSVariableValue('--bs-scrolloffset');


    let activeLink = null;

    // Überprüfen, ob nahe am unteren Rand gescrollt wurde
    if (scrollPos + windowHeight > documentHeight - 50) {
        if (links.length > 0) {
            const lastLink = links[links.length - 1];
            if (lastLink && lastLink.getAttribute("href")) {
                const lastLinkHref = lastLink.getAttribute("href");
                const lastElementId = lastLinkHref.split('#')[1];
                const lastElement = document.getElementById(lastElementId);

                if (lastElement) {
                    activeLink = lastLink;
                }
            }
        }
    } else {
        for (let i = links.length - 1; i >= 0; i--) {
            const currElement = links[i];
            if (currElement && currElement.getAttribute("href")) {
                const currLink = currElement.getAttribute("href");
                const refElementId = currLink.split('#')[1];
                const refElement = document.getElementById(refElementId);

                if (refElement) {
                    const refElementPos = refElement.getBoundingClientRect().top + window.pageYOffset - scrollOffset;

                    // Überprüfen, ob der obere Teil des Abschnitts sichtbar ist
                    if (scrollPos >= refElementPos - 5) {
                        activeLink = currElement;
                        break; // Beim ersten sichtbaren Abschnitt abbrechen
                    }
                }
            }
        }
    }

    if (activeLink) {
        setActiveLink(activeLink);
    }
}

export function changeNavLinksAfterLoad() {
    let hash = window.location.hash;

    // Extrahiere den reinen Hash ohne Parameter
    const hashParts = hash.split('?');
    const pureHash = hashParts[0];

    links.forEach(currElement => {
        if (currElement.getAttribute("href") === pureHash) {
            setActiveLink(currElement);
        } else if (pureHash === '' && currElement.getAttribute("href") === "#top") {
            setActiveLink(currElement);
        }
    });

    // Scroll to the correct position after setting the active link
    if (pureHash) {
        setTimeout(() => {
            const target = document.querySelector(pureHash);
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

    // Handle the event parameter if present
    const params = new URLSearchParams(hashParts[1] || '');
    const eventName = params.get('event');
    if (eventName) {
        const eventField = document.querySelector('input[name="Event"]');
        if (eventField) {
            eventField.value = decodeURIComponent(eventName);
        }
    }

    changeAnchorLinks();
}


