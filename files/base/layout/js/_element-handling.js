window.addEventListener('DOMContentLoaded', function() {
    const headerContent = document.querySelector(".header-content.fixed");
    const wrapper = document.querySelector("#wrapper");

    function adjustWrapperPadding() {
        if (headerContent && wrapper) {
            const paddingTop = window.getComputedStyle(headerContent).height;
            wrapper.style.paddingTop = paddingTop;
        }
    }

    // Initial adjustment
    adjustWrapperPadding();

    // Adjust on resize
    window.addEventListener('resize', adjustWrapperPadding);

    // Handle scroll class
    if (headerContent) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) { // Punkt, an dem die Änderung eintritt
                headerContent.classList.add('is-scrolling');
            } else {
                headerContent.classList.remove('is-scrolling');
            }
        });
    }
});


/*
let elementsWithPullTop = Array.from(
    document.querySelectorAll(".content--element")
).filter((el) => el.querySelector(".pull-top"));

elementsWithPullTop.forEach((element) => {
    let previousSibling = element.previousElementSibling;
    if (
        previousSibling &&
        previousSibling.classList.contains("content--element")
    ) {
        previousSibling.style.marginBottom = "0";
    }
});
 */


var modalElements = document.querySelectorAll(".modal");

modalElements.forEach(function (modalElement) {
    if (modalElement) {
        modalElement.parentNode.removeChild(modalElement);
        document.body.appendChild(modalElement);
    }
});
(function() {
  function setupButtons() {
    document.querySelectorAll('.btn-outline-currentColor, .btn-currentColor').forEach(button => {
      const parentInfo = findEffectiveBackground(button);
      const containerBackgroundColor = parentInfo.backgroundColor;

      // Bestimme die Kontrastfarbe basierend auf der Helligkeit des Hintergrunds
      const contrastColor = getContrastColor(containerBackgroundColor);

      if (button.classList.contains('btn-currentColor')) {
        button.style.setProperty('--parent-bg-color', containerBackgroundColor);
        button.style.setProperty('--btn-context-color', contrastColor);
      } else if (button.classList.contains('btn-outline-currentColor')) {
        button.style.setProperty('--parent-bg-color', containerBackgroundColor);
        button.style.setProperty('--btn-context-color', contrastColor);
      }
    });
  }

  function findEffectiveBackground(element) {
    let current = element.parentElement;
    const transparentValues = ['rgba(0, 0, 0, 0)', 'rgb(0, 0, 0)', 'transparent'];

    while (current) {
      const style = window.getComputedStyle(current);
      const bgColor = style.backgroundColor;
      const bgImage = style.backgroundImage;

      if ((bgColor && !transparentValues.includes(bgColor)) ||
          (bgImage && bgImage !== 'none')) {
        return {
          element: current,
          backgroundColor: bgColor,
          hasImage: bgImage !== 'none'
        };
      }

      current = current.parentElement;

      if (!current || current === document.body) {
        const bodyStyle = window.getComputedStyle(document.body);
        return {
          element: document.body,
          backgroundColor: bodyStyle.backgroundColor || 'white',
          hasImage: bodyStyle.backgroundImage !== 'none'
        };
      }
    }

    return {
      element: document.body,
      backgroundColor: 'white',
      hasImage: false
    };
  }

  function getContrastColor(backgroundColor) {
    // Konvertiere rgb/rgba zu RGB-Werten
    const rgbMatch = backgroundColor.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)|rgba\((\d+),\s*(\d+),\s*(\d+)/);

    if (!rgbMatch) {
      return '#000000'; // Fallback zu schwarz
    }

    const r = parseInt(rgbMatch[1] || rgbMatch[4]);
    const g = parseInt(rgbMatch[2] || rgbMatch[5]);
    const b = parseInt(rgbMatch[3] || rgbMatch[6]);

    // Berechne die relative Helligkeit
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Für helle Hintergründe (wie das grüne rgb(222, 238, 198)) verwende dunkles Grün
    // Für dunkle Hintergründe verwende helles Grün oder Weiß
    if (luminance > 0.5) {
      return 'rgb(17, 54, 52)'; // Dunkles Grün
    } else {
      return 'rgb(222, 238, 198)'; // Helles Grün
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupButtons);
  } else {
    setupButtons();
  }

  window.addEventListener('load', setupButtons);

  const observer = new MutationObserver(mutations => {
    if (mutations.some(m => m.type === 'childList' ||
                          (m.type === 'attributes' &&
                          (m.attributeName === 'class' || m.attributeName === 'style')))) {
      setupButtons();
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['class', 'style']
  });
})();