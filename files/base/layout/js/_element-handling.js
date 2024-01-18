var headerContent = document.querySelector(".header--content.fixed");

if (headerContent) {
  var paddingTop = window.getComputedStyle(headerContent).height;
  document.body.style.paddingTop = paddingTop;
}

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

var modalElements = document.querySelectorAll(".modal");

modalElements.forEach(function (modalElement) {
  if (modalElement) {
    modalElement.parentNode.removeChild(modalElement);
    document.body.appendChild(modalElement);
  }
});


document
  .querySelectorAll(
    "#main .mod_article > .article-content > *:not(.content--element):not(.container):not(.ce_html):not(.mod_catalogMasterView):not(.mod_iso_productreader):not(.mod_catalogUniversalView):not(.mod_pageimage):not(style):not(.body-slider)"
  )
  .forEach(function (element) {
    var container = document.createElement("div");
    container.classList.add("container");
    element.parentNode.insertBefore(container, element);
    container.appendChild(element);
  });

document
  .querySelectorAll(
    '#main > .inside > div[class^="mod_"]:not(.mod_article):not(.mod_iso_productreader):not(.mod_pageimage):not(.container):not(style)'
  )
  .forEach(function (element) {
    var container = document.createElement("div");
    container.classList.add("container");
    element.parentNode.insertBefore(container, element);
    container.appendChild(element);
  });
