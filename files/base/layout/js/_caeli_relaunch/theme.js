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

// Header Scrolling Class (is-scrolling)
document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('header');

  window.addEventListener('scroll', function() {
    // Check both window.pageYOffset and document.documentElement.scrollTop
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (scrollTop > 0) {
      header.classList.add('is-scrolling');
    } else {
      header.classList.remove('is-scrolling');
    }
  });
});

// Main Nav Level 2 Sizing (Full Width & Alignment)
document.addEventListener('DOMContentLoaded', function() {
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
    document.querySelectorAll('#mainNav .level_2').forEach(function(level2) {
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

        console.log('Wrapper Left:', wrapperLeft, 'Margin applied:', marginLeft);
      }
    });
  }

  // Initialisierung ausführen
  updateSizes();

  // Bei Fenstergrößenänderung aktualisieren (mit Debounce)
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateSizes, 100);
  });

  // Bei Laden von Bildern oder Schriften auch aktualisieren
  window.addEventListener('load', updateSizes);
  document.fonts.ready.then(updateSizes);
});

// Scroll Progress Bar & Scroll-to-Top Button
document.addEventListener('DOMContentLoaded', function() {
  // Erstelle Progress-Bar
  const progressBar = document.createElement('div');
  progressBar.className = 'scroll-progress-bar';
  document.body.appendChild(progressBar);

  // Erstelle oder finde den Scroll-to-Top Button
  let scrollTopBtn = document.querySelector('.BodyScrollToTop');

  if (!scrollTopBtn) {
    // Button erstellen
    scrollTopBtn = document.createElement('div');
    scrollTopBtn.className = 'BodyScrollToTop';

    // Ring-Container erstellen
    const ringContainer = document.createElement('div');
    ringContainer.className = 'progress-ring-container';

    // Ring erstellen
    const ring = document.createElement('div');
    ring.className = 'progress-ring';

    // Pfeil-Container (wird durch CSS erstellt)
    const arrowContainer = document.createElement('div');
    arrowContainer.className = 'arrow-container';

    // Zusammenbauen
    ringContainer.appendChild(ring);
    scrollTopBtn.appendChild(ringContainer);
    scrollTopBtn.appendChild(arrowContainer);
    document.body.appendChild(scrollTopBtn);

    // Klick-Ereignis
    scrollTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  // Update beim Scrollen
  window.addEventListener('scroll', function() {
    // Berechne Scroll-Fortschritt
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrollProgress = scrollTop / scrollHeight;

    // Aktualisiere Progressbar oben
    progressBar.style.width = (scrollProgress * 100) + '%';

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
  });
});

// Consent Management Form Handling (AGB Toggle & HubSpot Submit Trigger)
document.addEventListener('DOMContentLoaded', function() {
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
                agbNotice.classList.remove('d-none');
                agbNotice.classList.add('d-block');
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
                agbNotice.classList.remove('d-block');
                agbNotice.classList.add('d-none');
            }
        }

        function checkConsentAndAdjustForm() {
            let hasConsent = false;
            let adjustmentNeeded = false;

            if (typeof __cmp === 'function') {
                try {
                    const cmpData = __cmp('getCMPData');
                    if (cmpData && cmpData.vendorConsents && cmpData.vendorConsents.s10) {
                        hasConsent = true;
                    }
                } catch (e) {
                    hasConsent = false;
                }
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
           } catch (e) {}
        }

        function setupInitialConsentListener() {
             if (typeof __cmp === 'function') {
                 try {
                     __cmp("addEventListener", ["consent", handleInitialConsent, false], null);
                     return true;
                 } catch (e) {}
             }
             return false;
        }

        function initializeConsentHandling() {
             cmpCheckCounter++;
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
            submitButton.addEventListener('click', function() {
               if (form.checkValidity() && typeof __cmp === 'function') { 
                    try {
                        __cmp('setVendorConsent', ['s10', 1]);
                   } catch (e) {}
                }
            });
        }
    });
});