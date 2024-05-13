// Funktionen zur Navigation und zum aktiven Zustand von Links
export function changeAnchorLinks() {
  const scrollPos = window.pageYOffset;

  document
    .querySelectorAll(
      ' #mainNav  a[href*="#"]:not(.invisible), .onepagenavi--wrapper a'
    )
    .forEach((currElement) => {
      const currLink = currElement.getAttribute("href");
      const refElement = document.querySelector(
        currLink.substring(currLink.indexOf("#"))
      );

      if (refElement) {
        const refElementPos =
          refElement.getBoundingClientRect().top + scrollPos - 130; // --bs-scrolloffset + 5
        const refElementHeight = refElement.offsetHeight;

        if (
          refElementPos <= scrollPos &&
          refElementPos + refElementHeight > scrollPos
        ) {
          // Entferne active-Klasse von anderen Elementen
          const activeElem = document.querySelector(
            "#mainNav .active"
          );
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

export function changeNavLinksAfterLoad() {
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

