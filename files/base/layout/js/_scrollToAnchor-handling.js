function changeAnchorLinks() {
    const scrollPos = window.pageYOffset;

    document.querySelectorAll('#mainNav  a[href*="#"]:not(.invisible), .onepagenavi--wrapper a').forEach(currElement => {
        const currLink = currElement.getAttribute("href");
        const refElement = document.querySelector(currLink.substring(currLink.indexOf("#")));

        if (refElement) {
            const refElementPos = refElement.getBoundingClientRect().top + scrollPos - 130; // --bs-scrolloffset + 5
            const refElementHeight = refElement.offsetHeight;

            if (refElementPos <= scrollPos && refElementPos + refElementHeight > scrollPos) {
                // Entferne active-Klasse von anderen Elementen
                const activeElem = document.querySelector("#mainNav .level_2 .active");
                if (activeElem) {
                    activeElem.classList.remove("active");
                }

                // Setze active-Klasse auf aktuelles Element
                if (!currElement.classList.contains("active")) {
                    currElement.classList.add("active");
                }
            } else {
                currElement.classList.remove("active");
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


var menus = document.querySelectorAll('.mod_mmenu');

menus.forEach(function (menu) {
    // Für jedes Menü, wählen Sie die entsprechenden Links aus
    var links = menu.querySelectorAll('a[href*="#"]');

    links.forEach(function (link) {
        link.addEventListener('click', function () {
            // Abrufen der mmenu-API-Instanz für das aktuelle Menü
            var myMenuApi = menu.mmApi;
            // Schließen des Menüs
            myMenuApi.close();
        });
    });
});