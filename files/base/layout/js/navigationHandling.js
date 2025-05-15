// navigationHandling.js
import {getCSSVariableValue} from "./smoothScrolling.js";

const NAVIGATION_SELECTORS = {
    MAIN_NAV: '#mainNav',
    MOBILE_NAV: '#mobileNav',
    ONEPAGE_NAV: '.onepagenavi--wrapper'
};

class NavigationManager {
    constructor() {
        this.links = this.initializeLinks();
        this.currentActive = null;
    }

    initializeLinks() {
        const mainNavLinks = Array.from(document.querySelectorAll(`${NAVIGATION_SELECTORS.MAIN_NAV} a[href*="#"]:not(.invisible)`));
        const mobileNavLinks = Array.from(document.querySelectorAll(`${NAVIGATION_SELECTORS.MOBILE_NAV} a[href*="#"]:not(.invisible)`));
        const onepageLinks = Array.from(document.querySelectorAll(`${NAVIGATION_SELECTORS.ONEPAGE_NAV} a`));
        const articleLinksDesktop = Array.from(document.querySelectorAll('#articleNav a[href^="#"]')); // Nur Ankerlinks
        const articleLinksMobileStoerer = Array.from(document.querySelectorAll('#stoerer-130-1 .article-nav-content a[href^="#"]')); // Nur Ankerlinks im mobilen Störer
        
        // Kombiniere alle relevanten Links
        return [...mainNavLinks, ...mobileNavLinks, ...onepageLinks, ...articleLinksDesktop, ...articleLinksMobileStoerer];
    }

    setActiveLink(targetHref) {
        // Entferne alle aktiven Zustände
        document.querySelectorAll('li.active, a.active').forEach(el => {
            el.classList.remove('active');
            if (el.tagName.toLowerCase() === 'li') {
                el.classList.add('sibling');
            }
        });

        // Setze neue aktive Zustände
        this.links.forEach(link => {
            if (link.getAttribute('href').endsWith(targetHref)) {
                link.classList.remove('sibling');
                link.classList.add('active');

                const parentLi = link.closest('li');
                if (parentLi) {
                    parentLi.classList.remove('sibling');
                    parentLi.classList.add('active');
                }
            }
        });
    }

    updateActiveSection() {
        const scrollPos = window.pageYOffset;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollOffset = getCSSVariableValue('--bs-scrolloffset');

        // Spezialfall: Scroll am Ende der Seite
        if (scrollPos + windowHeight > documentHeight - 50) {
            const lastValidLink = this.findLastValidLink();
            if (lastValidLink) {
                this.setActiveLink(lastValidLink.getAttribute('href'));
                return;
            }
        }

        // Normale Scroll-Position
        for (let i = this.links.length - 1; i >= 0; i--) {
            const link = this.links[i];
            const href = link.getAttribute('href');
            const sectionId = href.split('#').pop();
            const section = document.getElementById(sectionId);

            if (section) {
                const sectionTop = section.getBoundingClientRect().top + window.pageYOffset - scrollOffset;
                if (scrollPos >= sectionTop - 5) {
                    this.setActiveLink(href);
                    return;
                }
            }
        }
    }

    findLastValidLink() {
        for (let i = this.links.length - 1; i >= 0; i--) {
            const link = this.links[i];
            const href = link.getAttribute('href');
            const sectionId = href.split('#').pop();
            if (document.getElementById(sectionId)) {
                return link;
            }
        }
        return null;
    }

    handleInitialState() {
        const hash = window.location.hash;
        if (hash) {
            // Prüfe ob der Anker existiert
            const sectionId = hash.substring(1); // Entferne das # am Anfang
            if (document.getElementById(sectionId)) {
                this.setActiveLink(hash);
            }
        } else {
            // Wenn kein Hash, prüfe ob der erste Link einen gültigen Anker hat
            const firstLink = this.links[0];
            if (firstLink) {
                const href = firstLink.getAttribute('href');
                const sectionId = href.split('#').pop();
                if (document.getElementById(sectionId)) {
                    this.setActiveLink(href);
                }
            }
        }
    }
}

// Erstelle eine Instanz
const navigationManager = new NavigationManager();

// Exportiere die benötigten Funktionen
export function changeAnchorLinks() {
    navigationManager.updateActiveSection();
}

export function changeNavLinksAfterLoad() {
    navigationManager.handleInitialState();
}

// Mache die Funktionen auch global verfügbar
window.changeAnchorLinks = changeAnchorLinks;
window.changeNavLinksAfterLoad = changeNavLinksAfterLoad;

// Event Listener
document.addEventListener('DOMContentLoaded', () => {
    navigationManager.handleInitialState();
    window.addEventListener('scroll', () => navigationManager.updateActiveSection());
});

// Event Listener für dynamisch generierte Artikel-Navigation
document.addEventListener('articleNavGenerated', () => {
    navigationManager.links = navigationManager.initializeLinks();
    navigationManager.handleInitialState();
});