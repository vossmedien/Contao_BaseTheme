function changeAnchorLinks() {
    const scrollPos = window.pageYOffset;

    // Höhe von .header--content ermitteln, falls es die CSS-Eigenschaft position: fixed besitzt
    const header = document.querySelector('.header--content');
    const headerHeight = (header && getComputedStyle(header).position === 'fixed') ? header.offsetHeight : 0;

    document.querySelectorAll('#mainNav  a[href*="#"]:not(.invisible), .onepagenavi--wrapper a').forEach(currElement => {
        const currLink = currElement.getAttribute("href");
        const refElement = document.querySelector(currLink.substring(currLink.indexOf("#")));

        if (refElement) {
            const refElementPos = refElement.getBoundingClientRect().top + scrollPos;
            const refElementHeight = refElement.offsetHeight;

            if (refElementPos - headerHeight <= scrollPos && refElementPos - headerHeight + refElementHeight > (scrollPos + 100)) {
                // Entferne active-Klasse von anderen Elementen
                const activeElem = document.querySelector("#mainNav .level_2 .active");
                if (activeElem) {
                    activeElem.classList.remove("active");
                    /*
                    const parentActiveElem = activeElem.closest('li');
                    if (parentActiveElem) parentActiveElem.classList.remove("active");

                     */
                }

                // Setze active-Klasse auf aktuelles Element
                if (!currElement.classList.contains("active")) {
                    currElement.classList.add("active");
                }
/*
                const parentElem = currElement.closest('li');
                if (parentElem && !parentElem.classList.contains("active")) {
                    parentElem.classList.add("active");
                }

 */
            } else {
                currElement.classList.remove("active");


                /*
                const parentElem = currElement.closest('li');
                if (parentElem) {
                    parentElem.classList.remove("active");
                }
                 */

            }
        }
    });
}



function changeNavLinksAfterLoad() {
    const hash = window.location.hash;

    document.querySelectorAll("#mobileNav li > *, #mainNav  li > *, .onepagenavi--wrapper li > *").forEach(currElement => {
        if (currElement.getAttribute("href") === hash) {
            const activeElem = document.querySelector("#mobileNav .active, #mainNav .active");
            const selectedElem = document.querySelector("#mobileNav .mm-listitem_selected");

            if (activeElem) activeElem.classList.remove("active");
            if (selectedElem) selectedElem.classList.remove("mm-listitem_selected");

            currElement.classList.add("active");
          /*  const parentElem = currElement.closest('li');
            if (parentElem) parentElem.classList.add("mm-listitem_selected");

           */
        } else if (currElement.getAttribute("href") === "#top") {
            const firstElem = document.querySelector("#mobileNav .level_1 > .first");
            if (firstElem) firstElem.classList.add("mm-listitem_selected");
        }
    });

    changeAnchorLinks();
}

if (window.location.hash && document.querySelector(window.location.hash)) {
    changeNavLinksAfterLoad();
}

window.addEventListener('scroll', changeAnchorLinks);


document.querySelectorAll(".scrollToTop, .BodyScrollToTop").forEach(elem => {
    elem.addEventListener('click', function (event) {
        event.preventDefault();
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
});


/* Smooth Scrolling and set correct Item active */
if (window.location.hash) {
    var hash = window.location.hash;

    if ($(hash).length) {
        changeNavLinksAfterLoad();
    }
}

/* Smooth Scrolling and set correct Item active */

var anchorHandling = function (e) {
    e.preventDefault();

    changeAnchorLinks();
    var id = this.attributes.href.value;
    var scrollTo = false;
    window.location.hash = id;

    if ($(id).hasClass("modal")) {
        var myModal = new bootstrap.Modal(document.getElementById(id.substring(1)), {});
        myModal.show();
    } else {
        if (id.length > 1) {
            var scrollTo = document.querySelector(id);
        }
        if (scrollTo) {
            var current_position = document.documentElement.scrollTop;
            animate(
              document.scrollingElement,
              "scrollTop",
              "",
              current_position,
              scrollTo.offsetTop - 100,
              750,
              true
            );
        }
    }
};


window.onload = function () {
    var anchorLinks = document.querySelectorAll('a[href^="#"]:not(.mm-btn):not(.carousel-control):not(.navActivator)');
    for (var i = 0; i < anchorLinks.length; i++) {
        anchorLinks[i].addEventListener("click", anchorHandling);
    }
};

window.onload = function () {
    const anchorLinks = document.querySelectorAll('a[href^="#"]:not(.mm-btn):not(.carousel-control):not(.navActivator)');

    anchorLinks.forEach(anchor => {
        anchor.addEventListener('click', function (event) {
            event.preventDefault();

            changeAnchorLinks();

            const id = this.getAttribute('href');
            window.location.hash = id;

            const modal = document.querySelector(id);

            if (modal && modal.classList.contains('modal')) {
                // Replace this line with your modal display function
                console.log('Modal Show Function Needed');
            } else {
                if (id.length > 1) {
                    const scrollTo = document.querySelector(id);

                    if (scrollTo) {
                        const current_position = document.documentElement.scrollTop;
                        window.scrollTo({
                            top: scrollTo.getBoundingClientRect().top - 100 + current_position,
                            behavior: 'smooth'
                        });
                    }
                }
            }
        });
    });
};


changeAnchorLinks();
window.onscroll = function () {
    changeAnchorLinks();
    if (document.documentElement.scrollTop > 50) {
        const bodyScrollTop = document.querySelector(".BodyScrollToTop");
        if (bodyScrollTop) bodyScrollTop.classList.add("visible");
    } else {
        const bodyScrollTop = document.querySelector(".BodyScrollToTop");
        if (bodyScrollTop) bodyScrollTop.classList.remove("visible");
    }
};
