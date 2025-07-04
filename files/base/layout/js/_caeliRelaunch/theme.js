// Footer Accordion Indicator (+/-)
document.addEventListener('DOMContentLoaded', function () {
    const footerAccordions = document.querySelectorAll('.footer-accordion-item .collapse');

    footerAccordions.forEach(accordion => {
        accordion.addEventListener('show.bs.collapse', event => {
            const button = event.target.previousElementSibling;
            const indicator = button.querySelector('.indicator');
            if (indicator) {
                indicator.textContent = '-';
            }
        });

        accordion.addEventListener('hide.bs.collapse', event => {
            const button = event.target.previousElementSibling;
            const indicator = button.querySelector('.indicator');
            if (indicator) {
                indicator.textContent = '+';
            }
        });
    });
});

// Navigation Button Toggle (Accessibility)
document.addEventListener('DOMContentLoaded', function () {
    // Desktop Navigation Buttons
    const navButtons = document.querySelectorAll('.nav-button[aria-expanded]');
    navButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            
            // Toggle der level_2-wrapper Sichtbarkeit
            const wrapper = this.nextElementSibling;
            if (wrapper && wrapper.classList.contains('level_2-wrapper')) {
                wrapper.classList.toggle('show');
            }
        });
        
        // Escape-Taste zum Schließen
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && this.getAttribute('aria-expanded') === 'true') {
                this.setAttribute('aria-expanded', 'false');
                const wrapper = this.nextElementSibling;
                if (wrapper && wrapper.classList.contains('level_2-wrapper')) {
                    wrapper.classList.remove('show');
                }
                this.focus();
            }
        });
    });
    
    // Mobile Navigation Toggle Buttons
    const mobileNavToggles = document.querySelectorAll('.nav-toggle[aria-expanded]');
    mobileNavToggles.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            
            const submenuId = this.getAttribute('aria-controls');
            const submenu = document.getElementById(submenuId);
            if (submenu) {
                submenu.classList.toggle('show');
            }
        });
    });
});

// Header Scrolling Class (is-scrolling & is-scrolling-up)
document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('header');
    if (!header) return;
    
    let lastScrollTop = 0;
    let isScrollingUp = false;
    let hasScrolled = false;
    
    // Deutlich höhere Schwellenwerte für mehr Stabilität
    const SCROLL_THRESHOLD = 100; // Erst ab 100px Scroll wird überhaupt reagiert
    const DIRECTION_THRESHOLD = 100; // Mindestens 50px in eine Richtung scrollen

    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Ganz oben - alle Klassen entfernen
        if (scrollTop <= 0) {
            header.classList.remove('is-scrolling', 'is-scrolling-up');
            lastScrollTop = 0;
            isScrollingUp = false;
            hasScrolled = false;
            return;
        }

        if (!hasScrolled) {
            header.classList.add('is-scrolling');
            hasScrolled = true;
        }
        
        // Nur bei ausreichender Scroll-Position Richtung prüfen
        if (scrollTop < SCROLL_THRESHOLD) {
            lastScrollTop = scrollTop;
            return;
        }
        
        // Richtungsänderung nur bei ausreichender Bewegung
        const scrollDiff = scrollTop - lastScrollTop;
        
        if (Math.abs(scrollDiff) >= DIRECTION_THRESHOLD) {
            if (scrollDiff > 0 && isScrollingUp) {
                // Nach unten scrollen - Meta-Nav verstecken
                header.classList.remove('is-scrolling-up');
                isScrollingUp = false;
            } else if (scrollDiff < 0 && !isScrollingUp) {
                // Nach oben scrollen - Meta-Nav anzeigen
                header.classList.add('is-scrolling-up');
                isScrollingUp = true;
            }
            lastScrollTop = scrollTop;
        }
    }

    window.addEventListener('scroll', function () {
         handleScroll();
    });
});

// Combined DOMContentLoaded Listener for Article Störer and Navigation
document.addEventListener('DOMContentLoaded', function () {

    // --- Variables and Functions for Article Navigation ---
    const articleNavContainer = document.getElementById('articleNav');
    const articleContent = document.querySelector('.col-12.col-lg-7[data-animation="animate__fadeIn"]');
    let navList = articleNavContainer ? articleNavContainer.querySelector('.list-arrow') : null;
    const headings = articleContent ? articleContent.querySelectorAll('h2') : []; //h3,h4 entfernt

    function scrollToAnchorHandler(e) {
        e.stopPropagation(); // Verhindert, dass das Event weiter nach oben blubbert (direkt am Anfang)
        e.preventDefault(); // Standard-Redirect verhindern
        const targetId = this.getAttribute('href').substring(1);
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
            const headerOffset = document.querySelector('header') ? document.querySelector('header').offsetHeight : 0;
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset - 20;

            window.scrollTo({
                top: offsetPosition,
                behavior: "smooth"
            });
        }
    }

    const generateNavList = (listElement) => {
        if (!listElement || headings.length === 0) {
             // Hide entire articleNav if no headings or list element
            if(articleNavContainer) {
                articleNavContainer.style.display = 'none';
            }
            return;
        }

        listElement.innerHTML = ''; // Clear list
        headings.forEach((heading, index) => {
            const headingText = heading.textContent.trim();
            const headingId = 'anker-' + index + '-' + headingText.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
            // Ensure heading has an ID for targeting
             if (!heading.id) {
                 heading.id = headingId;
             }

            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = '#' + heading.id; // Use the actual or newly set ID
            a.textContent = headingText;

            if (heading.tagName === 'H3') {
                li.classList.add('ms-2');
            } else if (heading.tagName === 'H4') {
                li.classList.add('ms-4');
            }

            li.appendChild(a);
            listElement.appendChild(li);
        });

        // Show articleNav again when headings are available
        if(articleNavContainer) {
            articleNavContainer.style.display = '';
        }

        // Attach scroll listeners
        listElement.querySelectorAll('a[href^="#"]').forEach(anchor => {
             anchor.removeEventListener('click', scrollToAnchorHandler);
             anchor.addEventListener('click', scrollToAnchorHandler);
        });
    };

    // --- Variables and Functions for Article Info Störer ---
    const articleInfoOriginal = document.querySelector('.col12.col-lg-5[data-animation="animate__fadeIn"] .article-info');
    const articleNavOriginal = document.getElementById('articleNav'); // Needed for content cloning
    const existingStoererWrapper = document.querySelector('.is-fixed.content--element.ce_rsce_stoerer.js-stoerer-ready');
    const existingStoerer130_0 = existingStoererWrapper ? existingStoererWrapper.querySelector('#stoerer-130-0') : null;
    let stoerer130_1 = null;
    let isMobileLayoutActive = false;

    function createStoerer130_1() {
        // Größen vom bestehenden Störer ableiten
        let desktopIconSize = '30px'; // Fallback
        let mobileIconSize = '30px';  // Fallback
        
        if (existingStoerer130_0) {
            // Desktop-Größe vom bestehenden SVG (d-none d-md-block)
            const desktopSvg = existingStoerer130_0.querySelector('.stoerer-trigger svg.d-none.d-md-block');
            if (desktopSvg && desktopSvg.style.width) {
                desktopIconSize = desktopSvg.style.width;
            }
            
            // Mobile-Größe vom bestehenden SVG (d-block d-md-none)
            const mobileSvg = existingStoerer130_0.querySelector('.stoerer-trigger svg.d-block.d-md-none');
            if (mobileSvg && mobileSvg.style.width) {
                mobileIconSize = mobileSvg.style.width;
            }
        }

        const newStoerer = document.createElement('div');
        newStoerer.id = 'stoerer-130-1';
        newStoerer.className = 'ce--stoerer is-expandable is-flush-right article-info-nav-stoerer';
        newStoerer.style.right = '0px';
        newStoerer.style.setProperty('--stoerer-padding', '10px'); 
        newStoerer.style.zIndex = '1';

        newStoerer.innerHTML = `
          <div class="stoerer-inner-wrapper" data-animation="animate__fadeIn">
            <div class="stoerer-trigger">
                 <svg role="img" class="svg-image d-none d-md-block" style="width: ${desktopIconSize};" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" width="32" height="32" viewBox="0 0 416.979 416.979" xml:space="preserve">
                     <g>
                     	<path d="M356.004,61.156c-81.37-81.47-213.377-81.551-294.848-0.182c-81.47,81.371-81.552,213.379-0.181,294.85   c81.369,81.47,213.378,81.551,294.849,0.181C437.293,274.636,437.375,142.626,356.004,61.156z M237.6,340.786   c0,3.217-2.607,5.822-5.822,5.822h-46.576c-3.215,0-5.822-2.605-5.822-5.822V167.885c0-3.217,2.607-5.822,5.822-5.822h46.576   c3.215,0,5.822,2.604,5.822,5.822V340.786z M208.49,137.901c-18.618,0-33.766-15.146-33.766-33.765   c0-18.617,15.147-33.766,33.766-33.766c18.619,0,33.766,15.148,33.766,33.766C242.256,122.755,227.107,137.901,208.49,137.901z" fill="#F3F3F3"/>
                     </g>
                 </svg>
                 <svg role="img" class="svg-image d-block d-md-none" style="width: ${mobileIconSize};" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" width="32" height="32" viewBox="0 0 416.979 416.979" xml:space="preserve">
                     <g>
                     	<path d="M356.004,61.156c-81.37-81.47-213.377-81.551-294.848-0.182c-81.47,81.371-81.552,213.379-0.181,294.85   c81.369,81.47,213.378,81.551,294.849,0.181C437.293,274.636,437.375,142.626,356.004,61.156z M237.6,340.786   c0,3.217-2.607,5.822-5.822,5.822h-46.576c-3.215,0-5.822-2.605-5.822-5.822V167.885c0-3.217,2.607-5.822,5.822-5.822h46.576   c3.215,0,5.822,2.604,5.822,5.822V340.786z M208.49,137.901c-18.618,0-33.766-15.146-33.766-33.765   c0-18.617,15.147-33.766,33.766-33.766c18.619,0,33.766,15.148,33.766,33.766C242.256,122.755,227.107,137.901,208.49,137.901z" fill="#F3F3F3"/>
                     </g>
                 </svg> <!-- Info-Icon -->
            </div>
            <div class="stoerer--content">
              <div class="stoerer-content--inner">
                <div class="article-info-content mb-1"> 
                  <!-- Placeholder for article info -->
                </div>
                <div class="article-nav-content">
                  <!-- Placeholder for article nav --> 
                </div> 
              </div>
            </div>
          </div>
        `; 

        return newStoerer;
      }

    function handleResize() {
        if (!articleInfoOriginal || !existingStoererWrapper) return; // Exit if elements are missing

        const viewportWidth = window.innerWidth;

        if (viewportWidth < 992) {
          // --- Mobile Ansicht (< 992px) ---
          if (!isMobileLayoutActive) {
            articleInfoOriginal.style.display = 'none';

            // 1. Störer #stoerer-130-1 erstellen und einfügen (falls noch nicht da)
            if (!existingStoererWrapper.querySelector('#stoerer-130-1')) {
              stoerer130_1 = createStoerer130_1();
              existingStoererWrapper.appendChild(stoerer130_1);
              existingStoererWrapper.dispatchEvent(new CustomEvent('stoererAdded', {bubbles: true}));
            } else {
                 stoerer130_1 = existingStoererWrapper.querySelector('#stoerer-130-1'); // Ensure we have the reference
            }

            // 2. Inhalte für #stoerer-130-1 kopieren/generieren
            if (stoerer130_1) {
              const infoContentArea = stoerer130_1.querySelector('.article-info-content');
              const navContentContainer = stoerer130_1.querySelector('.article-nav-content');

              // Artikel-Info (ohne Nav)
              if (infoContentArea && articleInfoOriginal) {
                const clonedInfo = articleInfoOriginal.cloneNode(true);
                const navToRemove = clonedInfo.querySelector('#articleNav');
                if (navToRemove) navToRemove.remove();
                infoContentArea.innerHTML = clonedInfo.innerHTML;
              }

              // Artikel-Nav
              if (navContentContainer && articleNavOriginal) {
                    navContentContainer.innerHTML = '';
                    const originalStrong = articleNavOriginal.querySelector('strong');
                     if (originalStrong) {
                        navContentContainer.appendChild(originalStrong.cloneNode(true));
                     } else {
                        const strongTag = document.createElement('strong');
                        strongTag.className = 'd-block mt-2 mb-1';
                        strongTag.textContent = 'Direkt zu:';
                        navContentContainer.appendChild(strongTag);
                     }

                     const mobileNavList = document.createElement('ul');
                     mobileNavList.className = 'list-arrow';
                     navContentContainer.appendChild(mobileNavList);

                     // *** HIER ist der Aufruf ***
                     generateNavList(mobileNavList);
                     
                     // --- NEU: Event auslösen, damit navigationHandling.js aktualisiert wird ---
                     document.dispatchEvent(new CustomEvent('articleNavGenerated'));
                     // --- ENDE NEU ---
               }
            }

            isMobileLayoutActive = true;
          }

        } else {
          // --- Desktop Ansicht (>= 992px) ---
          if (isMobileLayoutActive) {
            articleInfoOriginal.style.display = '';

            if (stoerer130_1) {
              stoerer130_1.remove();
              stoerer130_1 = null;
            }
            // Desktop-Liste neu generieren
            if (navList) generateNavList(navList);

            isMobileLayoutActive = false;
          }
        }
      }

    // --- Initial Setup ---
    if (articleContent && headings.length > 0) {
         if(navList) {
             generateNavList(navList); // Generate initial desktop nav list
             document.dispatchEvent(new CustomEvent('articleNavGenerated'));
         }
    } else if (articleNavContainer) {
        // Hide entire articleNav if no headings found initially
        articleNavContainer.style.display = 'none';
    }

    // Add listener for Störer logic if relevant elements exist
    if (articleInfoOriginal && existingStoererWrapper) {
         // Initial check
         handleResize();
         // Listener for resize
         let resizeTimerStorer;
         window.addEventListener('resize', function () {
             clearTimeout(resizeTimerStorer);
             resizeTimerStorer = setTimeout(handleResize, 150);
         });
    }

});

// Main Nav Level 2 Sizing (Full Width & Alignment)
document.addEventListener('DOMContentLoaded', function () {
    // MainNav Element finden
    const mainNav = document.getElementById('mainNav');

    // Hilfsfunktion zur genauen Breitenberechnung
    function getActualWidth(element) {
        const styles = window.getComputedStyle(element);
        const width = element.offsetWidth;
        return width;
    }

    // Hilfsfunktion zur Berechnung der genauen Position
    function getOffset(element) {
        const rect = element.getBoundingClientRect();
        return {
            left: rect.left + window.scrollX,
            width: rect.width
        };
    }

    function updateSizes() {
        if (!mainNav) return;

        // Genaue Berechnung der mainNav-Größe
        const mainNavOffset = getOffset(mainNav);
        const mainNavWidth = getActualWidth(mainNav);
        const mainNavLeft = mainNavOffset.left;

        // Für jedes .level_2 Element
        document.querySelectorAll('#mainNav .level_2').forEach(function (level2) {
            // Setze die Breite des .level_2 Elements
            level2.style.width = mainNavWidth + 'px';

            // Berechne, wie weit von links der Wrapper ist
            const wrapper = level2.closest('.level_2-wrapper');
            if (wrapper) {
                const wrapperOffset = getOffset(wrapper);
                const wrapperLeft = wrapperOffset.left;

                // Berechne den Unterschied und wende ihn als margin-left an
                const marginLeft = mainNavLeft - wrapperLeft;
                level2.style.marginLeft = marginLeft + 'px';
            }
        });
    }

    // Initialisierung ausführen
    updateSizes();

    // Bei Fenstergrößenänderung aktualisieren (mit Debounce)
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateSizes, 100);
    });

    // Bei Laden von Bildern oder Schriften auch aktualisieren
    window.addEventListener('load', updateSizes);
    document.fonts.ready.then(updateSizes);
});

// Scroll Progress Bar & Scroll-to-Top Button
document.addEventListener('DOMContentLoaded', function () {
    // Erstelle Progress-Bar
    //const progressBar = document.createElement('div');
    //progressBar.className = 'scroll-progress-bar';
    //document.body.appendChild(progressBar);

    // Erstelle oder finde den Scroll-to-Top Button
    let scrollTopBtn = document.querySelector('.BodyScrollToTop');

    if (!scrollTopBtn) {
        // Button erstellen
        scrollTopBtn = document.createElement('div');
        scrollTopBtn.className = 'BodyScrollToTop';

        // Ring-Container erstellen
        const ringContainer = document.createElement('div');
        ringContainer.className = 'progress-ring-holder';

        // Ring erstellen
        const ring = document.createElement('div');
        ring.className = 'progress-ring';

        // Pfeil-Container (wird durch CSS erstellt)
        const arrowContainer = document.createElement('div');
        arrowContainer.className = 'arrow-holder';

        // Zusammenbauen
        ringContainer.appendChild(ring);
        scrollTopBtn.appendChild(ringContainer);
        scrollTopBtn.appendChild(arrowContainer);
        document.body.appendChild(scrollTopBtn);

        // Klick-Ereignis
        scrollTopBtn.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    function updateScrollProgress() {
        // Berechne Scroll-Fortschritt
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollProgress = scrollTop / scrollHeight;

        // Aktualisiere Progressbar oben
        //progressBar.style.width = (scrollProgress * 100) + '%';

        // CSS-Variable für den Fortschritt setzen
        document.documentElement.style.setProperty('--scroll-progress', scrollProgress);

        // Zeige/verstecke den Button
        if (scrollTop > 300) {
            scrollTopBtn.style.opacity = '1';
            scrollTopBtn.style.visibility = 'visible';
        } else {
            scrollTopBtn.style.opacity = '0';
            scrollTopBtn.style.visibility = 'hidden';
        }
    }

    // Update beim Scrollen mit requestAnimationFrame für smoothe Animation
    let ticking = false;
    
    function requestTick() {
        if (!ticking) {
            window.requestAnimationFrame(updateScrollProgress);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', function () {
        requestTick();
        ticking = false;
    });
});

// Consent Management Form Handling (AGB Toggle & HubSpot Submit Trigger)
document.addEventListener('DOMContentLoaded', function () {
    // Konstante zum temporären Deaktivieren des CMP Toggles
    const CMP_TOGGLING_ENABLED = false; // true = CMP aktiv, false = CMP deaktiviert (normale AGB-Checkbox)
    
    // Konstante zum Steuern der AGB Notice Anzeige
    const SHOW_AGB_NOTICE = false; // true = Notice wird bei Consent angezeigt, false = Notice immer ausgeblendet
    
    document.querySelectorAll('.ce_form').forEach(formContainer => {
        const form = formContainer.querySelector('form');
        if (!form) return;

        const agbCheckboxContainer = form.querySelector('.agb_checkbox');
        const agbNotice = form.querySelector('.agb_notice');

        if (!agbCheckboxContainer || !agbNotice) {
            return;
        }

        const agbCheckbox = agbCheckboxContainer.querySelector('input[type="checkbox"][name="agb_akzeptiert"]');
        const agbHiddenInput = agbCheckboxContainer.querySelector('input[type="hidden"][name="agb_akzeptiert"]');
        const submitButton = form.querySelector('button[type="submit"]');

        agbCheckboxContainer.classList.add('d-none');
        agbNotice.classList.add('d-none');
        if (agbCheckbox) {
            agbCheckbox.required = false;
            agbCheckbox.disabled = true;
        }
        if (agbHiddenInput) {
            agbHiddenInput.value = '';
        }

        let initialSetupDone = false;
        let lastKnownConsentForS10 = null;
        let cmpCheckInterval = null;
        let cmpCheckCounter = 0;
        const MAX_CMP_CHECKS = 50;

        function adjustFormForConsent(hasConsent) {
            if (!agbCheckboxContainer || !agbNotice) return;
            const fieldset = agbCheckboxContainer.querySelector('fieldset.checkbox_container');

            if (hasConsent) {
                agbCheckboxContainer.classList.add('d-none');
                agbCheckboxContainer.classList.remove('d-block', 'mandatory');
                if (fieldset) fieldset.classList.remove('mandatory');
                if (agbCheckbox) {
                    agbCheckbox.required = false;
                    agbCheckbox.disabled = true;
                }
                if (agbHiddenInput && agbCheckbox) {
                    agbHiddenInput.value = agbCheckbox.value;
                }
                
                // AGB Notice nur anzeigen wenn SHOW_AGB_NOTICE aktiviert ist
                if (SHOW_AGB_NOTICE) {
                    agbNotice.classList.remove('d-none');
                    agbNotice.classList.add('d-block');
                } else {
                    agbNotice.classList.remove('d-block');
                    agbNotice.classList.add('d-none');
                }
            } else {
                agbCheckboxContainer.classList.remove('d-none');
                agbCheckboxContainer.classList.add('d-block', 'mandatory');
                if (fieldset) fieldset.classList.add('mandatory');
                if (agbCheckbox) {
                    agbCheckbox.required = true;
                    agbCheckbox.disabled = false;
                }
                if (agbHiddenInput) {
                    agbHiddenInput.value = '';
                }
                
                // AGB Notice immer ausblenden wenn kein Consent
                agbNotice.classList.remove('d-block');
                agbNotice.classList.add('d-none');
            }
        }

        function checkConsentAndAdjustForm() {
            let hasConsent = false;
            let adjustmentNeeded = false;

            // Wenn CMP Toggling deaktiviert ist, immer hasConsent = false verwenden
            if (CMP_TOGGLING_ENABLED && typeof __cmp === 'function') {
                try {
                    const cmpData = __cmp('getCMPData');
                    if (cmpData && cmpData.vendorConsents && cmpData.vendorConsents.s10) {
                        hasConsent = true;
                    }
                } catch (e) {
                    hasConsent = false;
                }
            } else {
                // CMP deaktiviert - normales Verhalten (keine automatische Consent-Annahme)
                hasConsent = false;
            }

            adjustmentNeeded = hasConsent !== lastKnownConsentForS10;

            if (adjustmentNeeded) {
                adjustFormForConsent(hasConsent);
                lastKnownConsentForS10 = hasConsent;
            }
        }

        function handleConsentChangeEvent() {
            if (!initialSetupDone) return;
            checkConsentAndAdjustForm();
        }

        function handleInitialConsent() {
            if (initialSetupDone) return;
            initialSetupDone = true;
            checkConsentAndAdjustForm();
            try {
                __cmp("addEventListener", ["consent", handleConsentChangeEvent, false], null);
                __cmp('addEventListener', ["settings", handleConsentChangeEvent, false], null);
            } catch (e) {
            }
        }

        function setupInitialConsentListener() {
            // Wenn CMP Toggling deaktiviert ist, keine Event Listener registrieren
            if (CMP_TOGGLING_ENABLED && typeof __cmp === 'function') {
                try {
                    __cmp("addEventListener", ["consent", handleInitialConsent, false], null);
                    return true;
                } catch (e) {
                }
            }
            return false;
        }

        function initializeConsentHandling() {
            cmpCheckCounter++;
            
            // Wenn CMP Toggling deaktiviert ist, sofort normales Verhalten aktivieren
            if (!CMP_TOGGLING_ENABLED) {
                if (cmpCheckInterval) clearInterval(cmpCheckInterval);
                if (lastKnownConsentForS10 !== false) {
                    adjustFormForConsent(false);
                    lastKnownConsentForS10 = false;
                }
                return;
            }
            
            if (typeof __cmp === 'function') {
                if (cmpCheckInterval) clearInterval(cmpCheckInterval);
                const listenerRegistered = setupInitialConsentListener();
                checkConsentAndAdjustForm();
                if (!listenerRegistered) {
                }
            } else if (cmpCheckCounter >= MAX_CMP_CHECKS) {
                if (cmpCheckInterval) clearInterval(cmpCheckInterval);
                if (lastKnownConsentForS10 !== false) {
                    adjustFormForConsent(false);
                    lastKnownConsentForS10 = false;
                }
            }
        }

        cmpCheckInterval = setInterval(initializeConsentHandling, 100);

        if (submitButton) {
            submitButton.addEventListener('click', function () {
                // Nur wenn CMP Toggling aktiviert ist, CMP Consent setzen
                if (form.checkValidity() && CMP_TOGGLING_ENABLED && typeof __cmp === 'function') {
                    try {
                        __cmp('setVendorConsent', ['s10', 1]);
                    } catch (e) {
                    }
                }
            });
        }
    });
});

// Flaechencheck ZIP Code Input
document.addEventListener('DOMContentLoaded', function() {
  const flaechencheckWrappers = document.querySelectorAll('.enterFlaechencheck');

  flaechencheckWrappers.forEach(wrapper => {
    const zipCodeInput = wrapper.querySelector('#zipCodeInput, .zipCodeInput');
    const zipCodeSubmit = wrapper.querySelector('#zipCodeSubmit, .zipCodeSubmit');
    let messageContainer = wrapper.querySelector('#messageContainer, .messageContainer');
    
    // MessageContainer erstellen wenn nicht vorhanden
    if (!messageContainer) {
      messageContainer = document.createElement('div');
      messageContainer.className = 'messageContainer';
      // Als erstes Element in den wrapper einfügen
      wrapper.insertBefore(messageContainer, wrapper.firstChild);
    }

    function showAlert(message, type = 'primary') {
      if (messageContainer) {
        const alertHtml = `
          <div class="alert alert-${type} small p-2 border-0 fade mt-0 mb-0 show" role="alert">
            ${message}
          </div>`;
        messageContainer.innerHTML = alertHtml;
      }
    }

    function clearAlert() {
      if (messageContainer) {
        messageContainer.innerHTML = '';
      }
    }

    if (zipCodeInput && zipCodeSubmit) {
      zipCodeSubmit.addEventListener('click', function() {
        clearAlert(); // Vorherige Nachrichten entfernen
        const query = zipCodeInput.value.trim();
        if (query) {
          const areaCheckUrl = window.caeliLang?.areaCheckUrl || '/flaechencheck';
          window.location.href = areaCheckUrl + '?search=' + encodeURIComponent(query);
        } else {
          const errorMessage = window.caeliLang?.zipSearchError || 'Bitte geben Sie eine PLZ oder einen Ort ein.';
          showAlert(errorMessage, 'primary');
        }
      });

      zipCodeInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          zipCodeSubmit.click();
        }
      });

      // Optional: Alert entfernen, wenn der Nutzer anfängt zu tippen
      zipCodeInput.addEventListener('input', function() {
          if (zipCodeInput.value.trim() !== '') {
              clearAlert();
          }
      });
    }
  });
});

// Open Modals via Hash or Link Click
document.addEventListener('DOMContentLoaded', function() {
    // Hash-basierte Modal-Öffnung
    function openModalFromHash() {
        const hash = window.location.hash;
        if (hash) {
            const modalId = hash.substring(1); // # entfernen
            const modalElement = document.getElementById(modalId);
            
            if (modalElement && modalElement.classList.contains('modal')) {
                try {
                    const modalInstance = new bootstrap.Modal(modalElement);
                    modalInstance.show();
                } catch (e) {
                    console.warn('Modal konnte nicht geöffnet werden:', modalId, e);
                }
            }
        }
    }

    // Beim Laden der Seite Hash prüfen
    openModalFromHash();

    // Bei Hash-Änderung reagieren
    window.addEventListener('hashchange', openModalFromHash);

    // Bestehende Link-basierte Modal-Öffnung für Pachtrechner (Rückwärtskompatibilität)
    const modalLinks = document.querySelectorAll('a[href$="#pachtrechnerModal"]');
    const modalElement = document.getElementById('pachtrechnerModal');

    if (modalElement) {
        const modalInstance = new bootstrap.Modal(modalElement);

        modalLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                modalInstance.show();
            });
        });
    }

    // Generische Link-basierte Modal-Öffnung für alle Modals
    const allModalLinks = document.querySelectorAll('a[href^="#"]');
    allModalLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.length > 1) {
            const targetId = href.substring(1);
            const targetModal = document.getElementById(targetId);
            
            if (targetModal && targetModal.classList.contains('modal')) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    try {
                        const modalInstance = new bootstrap.Modal(targetModal);
                        modalInstance.show();
                        // Hash zur URL hinzufügen ohne Scroll
                        history.pushState(null, null, href);
                    } catch (e) {
                        console.warn('Modal konnte nicht geöffnet werden:', targetId, e);
                    }
                });
            }
        }
    });

    // Hash entfernen wenn Modal geschlossen wird
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            if (window.location.hash === '#' + modal.id) {
                history.pushState(null, null, window.location.pathname + window.location.search);
            }
        });
    });
});
// Pachtrechner-Funktionalität
document.addEventListener('DOMContentLoaded', function () {
    // Funktion zur Tausendertrennung
    function tausenderpunkte(zahl = 0, modus = 0, tz = ".") {
        if (isNaN(zahl)) {
            return "Eingabe ist keine Zahl!";
        }

        // Zahl in String umwandeln und Ganzzahl-Teil extrahieren
        let ganzzahl = Math.floor(Math.abs(zahl)).toString();
        let nachkomma = "";

        // Nachkommastellen behandeln je nach Modus
        if (modus === 1) {
            const originalStr = String(zahl);
            const dotIndex = originalStr.indexOf(".");
            if (dotIndex !== -1) {
                nachkomma = originalStr.slice(dotIndex + 1);
            } else {
                nachkomma = "0";
            }
        }

        // Tausendertrennung von rechts nach links
        let result = "";
        for (let i = ganzzahl.length - 1, count = 0; i >= 0; i--, count++) {
            if (count > 0 && count % 3 === 0) {
                result = tz + result;
            }
            result = ganzzahl[i] + result;
        }

        // Vorzeichen wieder hinzufügen falls negativ
        if (zahl < 0) {
            result = "-" + result;
        }

        // Nachkommastellen je nach Modus anhängen
        switch (modus) {
            case 1:
                result += "," + nachkomma;
                break;
            case 2:
                result += ",00";
                break;
            case 3:
                result += ",-";
                break;
            case 4:
                result += ",--";
                break;
            // case 0: bleibt ohne Nachkommastellen
        }

        return result;
    }

    const pachtrechnerInstances = document.querySelectorAll('.pachtrechner-instance');

    pachtrechnerInstances.forEach(instance => {
        let pachtrechnerForm;
        let pachtrechnerErgebnis;

        // Fall 1: `instance` ist der Wrapper, Form und Ergebnis sind direkte Kinder. (z.B. pachtrechner.html5)
        const formChild = instance.querySelector('.js-pachtrechner-form');
        const ergebnisChild = instance.querySelector('.js-pachtrechner-ergebnis');

        if (instance.classList.contains('js-pachtrechner-form')) {
            // Fall 2: `instance` ist das Formular selbst. (z.B. geändertes pachtrechner_box.html5)
            pachtrechnerForm = instance;
            let nextSibling = instance.nextElementSibling;
            while(nextSibling) {
                if (nextSibling.classList.contains('js-pachtrechner-ergebnis')) {
                    pachtrechnerErgebnis = nextSibling;
                    break;
                }
                nextSibling = nextSibling.nextElementSibling;
            }
        } else if (instance.classList.contains('js-pachtrechner-ergebnis')) {
            // Fall 3: `instance` ist das Ergebnis selbst (unwahrscheinlich für die aktuelle Logik, aber zur Vollständigkeit)
            pachtrechnerErgebnis = instance;
            let prevSibling = instance.previousElementSibling;
            while(prevSibling) {
                if (prevSibling.classList.contains('js-pachtrechner-form')) {
                    pachtrechnerForm = prevSibling;
                    break;
                }
                prevSibling = prevSibling.previousElementSibling;
            }
        } else if (formChild && ergebnisChild) {
            // Zurück zu Fall 1, wenn `instance` weder Form noch Ergebnis ist, aber beides als Kinder hat.
            pachtrechnerForm = formChild;
            pachtrechnerErgebnis = ergebnisChild;
        }


        if (!pachtrechnerForm || !pachtrechnerErgebnis) {
            console.warn('Pachtrechner-Formular oder Ergebnis-Container konnte nicht eindeutig für die Instanz gefunden werden:', instance);
            return; // Mit der nächsten Instanz fortfahren
        }

        // Elemente innerhalb des Formulars
        const pachtrechnerHaInput = pachtrechnerForm.querySelector('.js-pachtrechner-ha-input');
        const resetHaButton = pachtrechnerForm.querySelector('.js-reset-ha-button');
        const pachtrechnerCheckButton = pachtrechnerForm.querySelector('.js-pachtrechner-check-btn');
        const pachtrechnerInputError = pachtrechnerForm.querySelector('.js-pachtrechner-input-error');

        // Elemente innerhalb des Ergebnisbereichs
        const sizeHaSpan = pachtrechnerErgebnis.querySelector('.js-size-ha');
        const jahresPachtSpan = pachtrechnerErgebnis.querySelector('.js-jahres-pacht');
        const barRangeStartSpan = pachtrechnerErgebnis.querySelector('.js-bar-range-start');
        const barRangeEndSpan = pachtrechnerErgebnis.querySelector('.js-bar-range-end');
        const barRangePercentDiv = pachtrechnerErgebnis.querySelector('.js-bar-range-percent');


        // Random text logic: Diese Logik wird für jede Instanz ausgeführt.
        // Wenn 'calculator_img_text_tipp_' und 'calculator_img_text_top_' globale, einmalige IDs sind,
        // sollte dieser Codeblock aus der forEach-Schleife herausgenommen und nur einmal global ausgeführt werden.
        // Aktuell (wie im ursprünglichen theme.js) ist es pro Instanz.
        const randomTextNTipp = document.querySelectorAll('[id^="calculator_img_text_tipp_"]').length;
        if (randomTextNTipp > 0) {
            const randomTxtTipp = Math.floor(Math.random() * randomTextNTipp) + 1;
            const tippElement = document.getElementById('calculator_img_text_tipp_' + randomTxtTipp);
            if (tippElement) {
                tippElement.classList.remove('d-none');
            }
        }

        const randomTextNTop = document.querySelectorAll('[id^="calculator_img_text_top_"]').length;
        let randomTxtTop;
        if (randomTextNTop > 0) {
            randomTxtTop = Math.floor(Math.random() * randomTextNTop) + 1;
        }

        // Event listener für Anchor tags mit class 'pachtrechner':
        // Ähnlich wie oben: Wenn 'a.pachtrechner' globale Links sind, gehört dieser Listener nicht in die Schleife,
        // da er sonst mehrfach für dieselben Elemente registriert wird.
        document.querySelectorAll('a.pachtrechner').forEach(function (element) {
            // Um mehrfache Listener zu vermeiden, könnte man prüfen, ob schon einer hängt, oder eine einmalige ID verwenden.
            // Fürs Erste belasse ich es, um dem Original-JS nahe zu bleiben, aber es ist ein potenzielles Problem.
            element.addEventListener('click', function () {
                if (window.location.hash === '#pachtrechner') {
                    window.location.reload();
                }
            });
        });

        if (resetHaButton) {
            resetHaButton.addEventListener('click', function () {
                if (pachtrechnerErgebnis) pachtrechnerErgebnis.classList.add('d-none');
                if (pachtrechnerForm) pachtrechnerForm.classList.remove('d-none');

                if (randomTextNTop > 0) {
                    const topElement = document.getElementById('calculator_img_text_top_' + randomTxtTop);
                    if (topElement) {
                        topElement.classList.add('d-none');
                    }
                }
            });
        }

        if (pachtrechnerCheckButton) {
            pachtrechnerCheckButton.addEventListener('click', function () {
                if (!pachtrechnerHaInput) return;
                const ha = parseFloat(pachtrechnerHaInput.value);

                if (ha > 0 && ha <= 999999) {
                    if (pachtrechnerInputError) pachtrechnerInputError.classList.add('d-none');

                    const jahresPacht = 8674 * ha;
                    const barRangeStart = 4251 * ha;
                    const barRangeEnd = 14667 * ha;

                    if (sizeHaSpan) sizeHaSpan.textContent = ha.toString();
                    if (jahresPachtSpan) jahresPachtSpan.textContent = tausenderpunkte(jahresPacht, 0, ".");
                    if (barRangeStartSpan) barRangeStartSpan.textContent = tausenderpunkte(barRangeStart, 0, ".");
                    if (barRangeEndSpan) barRangeEndSpan.textContent = tausenderpunkte(barRangeEnd, 0, ".");

                    if (pachtrechnerErgebnis) pachtrechnerErgebnis.classList.remove('d-none');
                    if (pachtrechnerForm) pachtrechnerForm.classList.add('d-none');

                    // Scroll zum Ergebnis-Container
                    if (pachtrechnerErgebnis) {
                        const headerOffset = document.querySelector('header') ? document.querySelector('header').offsetHeight : 0;
                        const elementPosition = pachtrechnerErgebnis.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset - 20; // 20px zusätzlicher Abstand

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: "smooth"
                        });
                    }

                    if (barRangePercentDiv) {
                        const percent = (jahresPacht * 100) / barRangeEnd;
                        setTimeout(function () {
                            barRangePercentDiv.style.width = percent + "%";
                            barRangePercentDiv.setAttribute('aria-valuenow', percent.toString());
                        }, 50);
                    }

                    if (randomTextNTop > 0) {
                        const topElement = document.getElementById('calculator_img_text_top_' + randomTxtTop);
                        if (topElement) {
                            topElement.classList.remove('d-none');
                        }
                    }

                } else {
                    if (pachtrechnerHaInput) pachtrechnerHaInput.value = '';
                    if (pachtrechnerInputError) {
                        pachtrechnerInputError.classList.remove('d-none');
                    }
                }
            });
        }

        if (pachtrechnerHaInput) {
            pachtrechnerHaInput.addEventListener('keyup', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (pachtrechnerCheckButton) {
                        pachtrechnerCheckButton.click();
                    }
                }
            });

            pachtrechnerHaInput.addEventListener('input', function () {
                if (pachtrechnerInputError && !pachtrechnerInputError.classList.contains('d-none')) {
                    pachtrechnerInputError.classList.add('d-none');
                }
            });
        }
    });
});

// Meinungsumfrage-Formular
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('meinungsumfrage');
    if (!form) {
        return; // Formular nicht auf dieser Seite vorhanden
    }

    // Datentransfer aus dem Div in die Formularfelder - ENTFERNT, da Werte jetzt direkt in hidden fields stehen
    // const dataDiv = document.querySelector('div[data-form-umfrage_berater]');
    // if (dataDiv) { ... }

    // Elemente selektieren
    const formBody = form.querySelector('.formbody');
    const radioGroups = Array.from(form.querySelectorAll('.widget-radio'));
    const gruendeTextareaWidget = form.querySelector('.widget-textarea');
    const submitButtonWidget = form.querySelector('.widget-submit');
    const globalBackButton = form.querySelector('.back-btn .btn'); // Der neue globale Zurück-Button (das Span darin)
    const successMessage = form.querySelector('.alert.alert-primary.text-center');

    let currentVisibleQuestionIndex = 0;

    // Alle Radio-Gruppen außer der ersten, die Textarea und den Submit-Button initial ausblenden
    radioGroups.forEach((group, index) => {
        if (index > 0) {
            group.style.display = 'none';
        }
    });
    if (gruendeTextareaWidget) {
        gruendeTextareaWidget.style.display = 'none';
    }
    if (submitButtonWidget) {
        submitButtonWidget.style.display = 'none';
    }
    if (globalBackButton) {
        globalBackButton.style.display = 'none';
    }
    if (successMessage) {
        successMessage.style.display = 'none';
    }

    // Event-Listener für die Radio-Buttons hinzufügen
    radioGroups.forEach((group, index) => {
        const radios = Array.from(group.querySelectorAll('input[type="radio"]'));
        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                // Die dynamische Erstellung der Back-Buttons hier wurde entfernt
                handleRadioChange(index, radio.value);
            });
        });
    });

    // Event-Listener für den globalen Zurück-Button
    if (globalBackButton) {
        globalBackButton.addEventListener('click', () => {
            handleBackButtonClick();
        });
    }

    function showSuccessAndSubmit() {
        // Der Zurück-Button-Container (.back-btn) soll ausgeblendet werden.
        // Annahme: globalBackButton ist das Span, der Container ist .back-btn im formBody
        const backButtonContainer = form.querySelector('.formbody .back-btn'); // Genauer selektieren, falls es im formBody ist
        if (backButtonContainer) {
            backButtonContainer.style.display = 'none';
        }

        // Die Erfolgsmeldung (.alert) anzeigen. Sie wird nicht mehr verschoben.
        if (successMessage) {
            successMessage.style.display = 'block';
        }

        // Kurze Verzögerung, damit der User die Nachricht sieht, bevor die Seite ggf. neu lädt
        setTimeout(() => {
            form.submit();
        }, 1500); // 1,5 Sekunden
    }

    function handleRadioChange(selectedIndex, selectedValue) {
        currentVisibleQuestionIndex = selectedIndex; // Index der Frage, die gerade beantwortet wurde

        if (radioGroups[selectedIndex]) {
            radioGroups[selectedIndex].style.display = 'none';
        }

        const nextIndex = selectedIndex + 1;

        if (selectedIndex === 0) { // Erste Radio-Gruppe
            if (selectedValue === 'Nein') {
                if (gruendeTextareaWidget) gruendeTextareaWidget.style.display = 'block';
                if (submitButtonWidget) {
                     submitButtonWidget.style.display = 'block';
                     // Event Listener für den expliziten Submit-Button im "Nein"-Pfad
                     const neinSubmitBtn = submitButtonWidget.querySelector('button[type="submit"]');
                     if(neinSubmitBtn && !neinSubmitBtn.dataset.listenerAttached) {
                        neinSubmitBtn.addEventListener('click', (e) => {
                            e.preventDefault(); // Verhindert sofortiges Absenden durch Button-Klick
                            showSuccessAndSubmit();
                        });
                        neinSubmitBtn.dataset.listenerAttached = 'true';
                     }
                }
                if (globalBackButton) globalBackButton.style.display = 'inline-block'; // Zurück anzeigen, da Textarea sichtbar ist
                currentVisibleQuestionIndex = -1; // Spezialfall für Textarea, nächste reguläre Frage ist nicht direkt folgend
                for (let i = nextIndex; i < radioGroups.length; i++) {
                    if (radioGroups[i]) radioGroups[i].style.display = 'none';
                }
                return;
            } else { // "Ja" wurde gewählt
                if (gruendeTextareaWidget) gruendeTextareaWidget.style.display = 'none';
                if (submitButtonWidget) submitButtonWidget.style.display = 'none';
                // Fallthrough zur normalen Logik für die nächste Frage
            }
        }

        // Regulärer Ablauf: nächste Radio-Gruppe anzeigen
        if (nextIndex < radioGroups.length) {
            if (radioGroups[nextIndex]) {
                radioGroups[nextIndex].style.display = 'block';
                currentVisibleQuestionIndex = nextIndex;
                if (globalBackButton) globalBackButton.style.display = 'inline-block';
            }
        } else {
            // Letzte Radio-Gruppe wurde beantwortet (und es war nicht der "Nein"-Pfad der ersten Frage)
            const isNeinPathActive = gruendeTextareaWidget && gruendeTextareaWidget.style.display === 'block';
            if (!isNeinPathActive) {
                showSuccessAndSubmit();
            }
        }
    }

    function handleBackButtonClick() {
        let previousRadioGroupToClear = null;

        if (currentVisibleQuestionIndex === -1) { // Wir sind im "Nein"-Pfad (Textarea sichtbar)
            if (gruendeTextareaWidget) gruendeTextareaWidget.style.display = 'none';
            if (submitButtonWidget) submitButtonWidget.style.display = 'none';
            if (radioGroups[0]) {
                radioGroups[0].style.display = 'block';
                previousRadioGroupToClear = radioGroups[0];
            }
            currentVisibleQuestionIndex = 0;
            if (globalBackButton) globalBackButton.style.display = 'none'; // Bei erster Frage keinen Zurück-Button
        } else if (currentVisibleQuestionIndex > 0) {
            if (radioGroups[currentVisibleQuestionIndex]) {
                radioGroups[currentVisibleQuestionIndex].style.display = 'none';
            }

            const prevIndex = currentVisibleQuestionIndex - 1;
            if (radioGroups[prevIndex]) {
                radioGroups[prevIndex].style.display = 'block';
                previousRadioGroupToClear = radioGroups[prevIndex];
                currentVisibleQuestionIndex = prevIndex;
                if (prevIndex === 0) {
                    if (globalBackButton) globalBackButton.style.display = 'none';
                } else {
                    if (globalBackButton) globalBackButton.style.display = 'block';
                }
            }
            // Falls Textarea/Submit vom "Nein"-Pfad noch sichtbar waren
            if (gruendeTextareaWidget && gruendeTextareaWidget.style.display === 'block') {
                gruendeTextareaWidget.style.display = 'none';
            }
            if (submitButtonWidget && submitButtonWidget.style.display === 'block') {
                submitButtonWidget.style.display = 'none';
            }
        }

        // Auswahl der Radio-Buttons in der wieder angezeigten Gruppe zurücksetzen
        if (previousRadioGroupToClear) {
            const radiosToClear = previousRadioGroupToClear.querySelectorAll('input[type="radio"]');
            radiosToClear.forEach(radio => {
                radio.checked = false;
            });
        }
    }
});

// Sprachwechsler URL-ID Ergänzung
document.addEventListener('DOMContentLoaded', function() {
    const languageSwitcher = document.querySelector('.mod_changelanguage');
    if (!languageSwitcher) return;

    // Aktuelle URL analysieren
    const currentPath = window.location.pathname;
    
    // Prüfen ob wir auf einer Details-Seite mit ID sind
    const detailsMatch = currentPath.match(/\/(en\/)?(?:marketplace|marktplatz)\/details\/(\d+)$/);
    
    if (detailsMatch) {
        const detailId = detailsMatch[2]; // Die ID extrahieren
        
        // Alle Links im Sprachwechsler durchgehen
        const languageLinks = languageSwitcher.querySelectorAll('a[href*="/details"]');
        
        languageLinks.forEach(link => {
            const currentHref = link.getAttribute('href');
            // Prüfen ob die URL bereits mit /details endet (ohne ID)
            if (currentHref.endsWith('/details')) {
                link.setAttribute('href', currentHref + '/' + detailId);
            }
        });
    }
});

/*
// Automatische Calendly-Link Verschleierung
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Alle Calendly-Links finden
        const calendlyLinks = document.querySelectorAll('a[href*="calendly.com"]');

        calendlyLinks.forEach(function(link) {
            // Original URL speichern
            const originalUrl = link.href;

            // Link "entschärfen"
            link.href = 'javascript:void(0)';
            link.removeAttribute('target');

            // Click-Handler hinzufügen
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // Direkt zu Calendly weiterleiten (URLs sind jetzt versteckt)
                window.open(originalUrl, '_blank', 'noopener,noreferrer');
            });
        });
    });
})();
 */


// Auf der caeli.co.uk Webseite hinzufügen
if (window.location.search.includes('contact=1')) {
    window.history.replaceState({}, document.title, '/#contact');
    document.getElementById('contact')?.scrollIntoView();
}