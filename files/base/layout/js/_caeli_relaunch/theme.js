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



document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('header');

  window.addEventListener('scroll', function() {
    if (window.scrollY > 0) {
      header.classList.add('is-scrolling');
    } else {
      header.classList.remove('is-scrolling');
    }
  });
});


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