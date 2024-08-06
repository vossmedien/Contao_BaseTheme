export function changeAnchorLinks() {
  const scrollPos = window.pageYOffset;
  const windowHeight = window.innerHeight;
  const documentHeight = document.documentElement.scrollHeight;

  const links = Array.from(document.querySelectorAll(
    '#mainNav a[href*="#"]:not(.invisible), .onepagenavi--wrapper a'
  ));

  let lastLink = links[links.length - 1];
  let activeLink = null;

  // Zuerst prüfen wir, ob wir am Ende der Seite sind
  if (scrollPos + windowHeight > documentHeight - 50) {
    activeLink = lastLink;
  } else {
    // Wenn nicht, gehen wir die Links von unten nach oben durch
    for (let i = links.length - 1; i >= 0; i--) {
      const currElement = links[i];
      const currLink = currElement.getAttribute("href");
      const refElement = document.querySelector(
        currLink.substring(currLink.indexOf("#"))
      );

      if (refElement) {
        const refElementPos =
          refElement.getBoundingClientRect().top + scrollPos - 130;
        const refElementHeight = refElement.offsetHeight;

        if (
          refElementPos <= scrollPos &&
          refElementPos + refElementHeight > scrollPos
        ) {
          activeLink = currElement;
          break; // Wir haben den aktiven Link gefunden, also brechen wir die Schleife ab
        }
      }
    }
  }

  // Jetzt setzen wir den aktiven Link
  setActiveLink(activeLink);
}

function setActiveLink(element) {
  // Entferne 'active' Klasse von allen Links
  document.querySelectorAll("#mainNav .active, .onepagenavi--wrapper .active").forEach(el => {
    el.classList.remove("active");
  });

  // Füge 'active' Klasse zum neuen aktiven Element hinzu, falls vorhanden
  if (element) {
    element.classList.add("active");
  }
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

