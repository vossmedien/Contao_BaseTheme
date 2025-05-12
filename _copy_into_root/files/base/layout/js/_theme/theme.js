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