export function adjustPullElements() {
  setTimeout(() => {
    const pullElements = document.querySelectorAll('.pull-bottom');
    const mobileMaxWidth = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--bs-mobile-max-width'));

    if (window.innerWidth <= mobileMaxWidth) {
      pullElements.forEach(element => {
        let contentElement = findTopLevelContentElement(element);
        if (!contentElement) return;

        let nextContentElement = findNextContentElement(contentElement);

        if (nextContentElement) {
          nextContentElement.style.transform = '';
          nextContentElement.style.marginBottom = '';
          nextContentElement.classList.remove('pulled-up-element');
          element.style.paddingBottom = '';
        }
      });
      return;
    }

    pullElements.forEach(element => {
      let contentElement = findTopLevelContentElement(element);
      if (!contentElement) return;

      let nextContentElement = findNextContentElement(contentElement);

      if (nextContentElement) {
        const pullPercentage = 0.35;
        let pullAmount = nextContentElement.offsetHeight * pullPercentage;
        pullAmount = Math.min(pullAmount, 150);

        nextContentElement.style.transform = `translateY(-${pullAmount}px)`;
        nextContentElement.style.marginBottom = `-${pullAmount}px`;
        nextContentElement.classList.add('pulled-up-element');
        //element.style.paddingBottom = `calc(var(--main-gap) + ${pullAmount}px)`;
      }
    });
  }, 0);
}

function findTopLevelContentElement(element) {
  let contentElement = element.closest('.content--element');
  if (!contentElement) return null;

  let parentContentElement = contentElement.parentElement.closest('.content--element');
  return parentContentElement || contentElement;
}

function findNextContentElement(element) {
  let nextElement = element.nextElementSibling;
  while (nextElement && !nextElement.classList.contains('content--element')) {
    nextElement = nextElement.nextElementSibling;
  }
  return nextElement;
}