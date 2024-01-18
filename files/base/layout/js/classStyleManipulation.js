// classStyleManipulation.js
// Modul für die Manipulation von Klassen und Stilen

/**
 * Fügt Bootstrap-Klassen zu verschiedenen Elementen hinzu.
 */
export function addBootstrapClasses() {
    // Füge Klassen zu Buttons hinzu
    document.querySelectorAll("p.back > a:not(.btn), .widget-submit > button").forEach(el => {
        el.classList.add("btn", "btn-primary");
    });

    document.querySelectorAll(".submit_container > input.button").forEach(el => {
        el.classList.add("btn", "btn-lg");
        if (el.classList.contains("next")) {
            el.classList.add("btn-primary");
        }
        if (el.classList.contains("previous")) {
            el.classList.add("btn-outline-primary");
        }
        if (el.classList.contains("confirm")) {
            el.classList.add("btn-success");
        }
    });


    document
        .querySelectorAll(".submit_container button:not(.btn)")
        .forEach((el) => {
            el.classList.add("btn");
            if (
                el.classList.contains("button_update") ||
                el.classList.contains("button_checkout")
            ) {
                el.classList.add("btn-outline-primary");
            }
        });

    document.querySelectorAll(".actions_container .submit").forEach((el) => {
        el.classList.add("btn", "btn-primary");
    });

    document.querySelectorAll(".filter-toggle-control").forEach((el) => {
        el.classList.add("btn", "btn-primary");
    });

    document.querySelectorAll(".mod_iso_orderhistory td.link > a").forEach((el) => {
        el.classList.add("btn", "btn-sm");
    });

    let firstLink = document.querySelector(
        ".mod_iso_orderhistory td.link > a:first-child"
    );
    if (firstLink) {
        firstLink.classList.add("btn-primary");
    }

    let lastLink = document.querySelector(
        ".mod_iso_orderhistory td.link > a:last-child"
    );
    if (lastLink) {
        lastLink.classList.add("btn-secondary");
    }

    document.querySelectorAll(".mod_iso_addressbook a.add").forEach((el) => {
        el.classList.add("btn", "btn-outline-primary");
    });

    document.querySelectorAll("#footerNav ul").forEach((el) => {
        el.classList.add("list-inline");
    });

    document.querySelectorAll("#footerNav ul > li").forEach((el) => {
        el.classList.add("list-inline-item");
    });

    document.querySelectorAll("p.empty:not(.message)").forEach((el) => {
        el.classList.add("alert", "alert-primary");
    });

    document.querySelectorAll("p.error:not(.message)").forEach((el) => {
        el.classList.add("alert", "alert-danger");
    });

    document.querySelectorAll(".tl_confirm:not(.message)").forEach((el) => {
        el.classList.add("alert", "alert-success");
    });

    document.querySelectorAll(".widget-radio span.note").forEach((el) => {
        el.classList.add("alert", "alert-primary");
    });
}

/**
 * Passt die Responsive-Tabelle an.
 */
export function adjustTableResponsive() {
    if (document.querySelector("#main .ce_text table")) {
        document.querySelectorAll("#main table").forEach(el => {
            let wrapper = document.createElement("div");
            wrapper.className = "table-responsive";
            el.parentNode.insertBefore(wrapper, el);
            wrapper.appendChild(el);
            el.classList.add("table", "table-striped", "table-hover");
        });
    }
}

// Weitere spezifische Funktionen zur Stil- und Klassenmanipulation können hier hinzugefügt werden.
