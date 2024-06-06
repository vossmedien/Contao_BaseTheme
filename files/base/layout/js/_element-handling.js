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