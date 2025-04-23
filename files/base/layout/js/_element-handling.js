var headerContent = document.querySelector(".header-content.fixed");

if (headerContent) {
    var paddingTop = window.getComputedStyle(headerContent).height;
    document.body.style.paddingTop = paddingTop;

    window.addEventListener('scroll', function () {
        if (window.scrollY > 50) { // Punkt, an dem die Änderung eintritt
            headerContent.classList.add('is-scrolling');
        } else {
            headerContent.classList.remove('is-scrolling');
        }
    });
}


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
      const textColor = getEffectiveColor(button, parentInfo.element);

      button.style.setProperty('--parent-bg-color', parentInfo.backgroundColor);
      button.style.setProperty('--btn-context-color', textColor);
    });
  }

  function findEffectiveBackground(element) {
    let current = element.parentElement;
    const transparentRgba = 'rgba(0, 0, 0, 0)';
    const transparentRgb = 'rgb(0, 0, 0)';

    while (current) {
      const style = window.getComputedStyle(current);
      const bgColor = style.backgroundColor;
      const bgImage = style.backgroundImage;

      if ((bgColor && bgColor !== transparentRgba && bgColor !== transparentRgb) ||
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
          backgroundColor: bodyStyle.backgroundColor,
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

  function getEffectiveColor(button, container) {
    let buttonColor = window.getComputedStyle(button).color;

    if (!buttonColor || buttonColor === 'rgba(0, 0, 0, 0)') {
      buttonColor = window.getComputedStyle(container).color;
    }

    return buttonColor || '#000000';
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