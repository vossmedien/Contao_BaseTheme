$(function () {
    var headerContent = document.querySelector(".header--content.fixed");

    if (headerContent) {
        var paddingTop = window.getComputedStyle(headerContent).height;
        document.body.style.paddingTop = paddingTop;
    }

    function adjustMargin(elements, direction, isNegative) {
        elements.forEach(element => {
            let size = (direction === 'top' || direction === 'bottom') ? element.offsetHeight : element.offsetWidth;
            let marginValue = (isNegative ? -1 : 1) * (size / 2) + 'px';

            if (direction === 'top' || direction === 'bottom') {
                element.style.marginTop = marginValue;
            } else {
                element.style.marginLeft = marginValue;
            }
        });
    }

    adjustMargin(document.querySelectorAll('.pull-top'), 'top', true);
    adjustMargin(document.querySelectorAll('.pull-bottom'), 'bottom', false);
    adjustMargin(document.querySelectorAll('.pull-start'), 'left', true);
    adjustMargin(document.querySelectorAll('.pull-end'), 'right', false);

    var modalElement = document.querySelector(".modal");
    if (modalElement) {
        modalElement.parentNode.removeChild(modalElement);
        document.body.appendChild(modalElement);
    }


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

    document.querySelectorAll(".submit_container button:not(.btn)").forEach(el => {
        el.classList.add("btn");
        if (el.classList.contains("button_update") || el.classList.contains("button_checkout")) {
            el.classList.add("btn-outline-primary");
        }
    });

    document.querySelectorAll(".actions_container .submit").forEach(el => {
        el.classList.add("btn", "btn-primary");
    });

    document.querySelectorAll(".filter-toggle-control").forEach(el => {
        el.classList.add("btn", "btn-primary");
    });

    document.querySelectorAll(".mod_iso_orderhistory td.link > a").forEach(el => {
        el.classList.add("btn", "btn-sm");
    });

    let firstLink = document.querySelector(".mod_iso_orderhistory td.link > a:first-child");
    if (firstLink) {
        firstLink.classList.add("btn-primary");
    }

    let lastLink = document.querySelector(".mod_iso_orderhistory td.link > a:last-child");
    if (lastLink) {
        lastLink.classList.add("btn-secondary");
    }

    document.querySelectorAll(".mod_iso_addressbook a.add").forEach(el => {
        el.classList.add("btn", "btn-outline-primary");
    });

    document.querySelectorAll("#footerNav ul").forEach(el => {
        el.classList.add("list-inline");
    });

    document.querySelectorAll("#footerNav ul > li").forEach(el => {
        el.classList.add("list-inline-item");
    });

    document.querySelectorAll("p.empty:not(.message)").forEach(el => {
        el.classList.add("alert", "alert-primary");
    });

    document.querySelectorAll("p.error:not(.message)").forEach(el => {
        el.classList.add("alert", "alert-danger");
    });

    document.querySelectorAll(".tl_confirm:not(.message)").forEach(el => {
        el.classList.add("alert", "alert-success");
    });

    document.querySelectorAll(".widget-radio span.note").forEach(el => {
        el.classList.add("alert", "alert-primary");
    });

    document.querySelectorAll(".message").forEach(el => {
        el.classList.add("alert");
        if (el.classList.contains("success")) {
            el.classList.add("alert-success");
        }
        if (el.classList.contains("empty")) {
            el.classList.add("alert-primary");
        }
        if (el.classList.contains("error")) {
            el.classList.add("alert-danger");
        }
    });

    // Hier bleibt wrapInner, da es keine einfache native Alternative gibt
    $("#main .mod_article > .article-content > *:not(.content--element):not(.container):not(.ce_html):not(.mod_catalogMasterView):not(.mod_iso_productreader):not(.mod_catalogUniversalView):not(.mod_pageimage):not(style)").wrapInner("<div class='container'></div>");
    $('#main > .inside > div[class^="mod_"]:not(.mod_article):not(.mod_iso_productreader):not(.mod_pageimage):not(.container):not(style)').wrapInner("<div class='container'></div>");

    document.querySelectorAll("form:not(#iso_mod_checkout_review) > .formbody:not(.row):not(.no-row)").forEach(el => {
        el.classList.add("row");
    });

    document.querySelectorAll("form .formbody > .fields > *").forEach(el => {
        el.parentNode.removeChild(el);
    });

    document.querySelectorAll("form .formbody .address_new").forEach(el => {
        el.classList.add("row");
    });

    document.querySelectorAll("form .formbody  p.alert").forEach(el => {
        el.classList.add("mt-0");
        let wrapper = document.createElement('div');
        wrapper.className = 'col-12';
        el.parentNode.insertBefore(wrapper, el);
        wrapper.appendChild(el);
    });

    document.querySelectorAll("form .widget.form-control").forEach(el => {
        el.classList.remove("form-control");
    });

    document.querySelectorAll("form label:not(.form-label)").forEach(label => {
        label.className = "";
    });

    document.querySelectorAll("form >  .formbody:not(.no-row) > fieldset").forEach(el => {
        el.classList.add("row");
        let wrapper = document.createElement('div');
        wrapper.className = 'col-12';
        el.parentNode.insertBefore(wrapper, el);
        wrapper.appendChild(el);
    });

    let formBodies = document.querySelectorAll("form > .formbody:not(.no-row)");
    formBodies.forEach(function (formBody) {
        let elements = formBody.querySelectorAll(':scope > *:not([class^="col-"])');
        elements.forEach(function (element) {
            element.classList.add("col-12");
        });
    });

    let formFieldsetBodies = document.querySelectorAll("form > .formbody fieldset");
    formFieldsetBodies.forEach(function (FormFieldset) {
        let elements = FormFieldset.querySelectorAll(':scope > *:not([class^="col-"])');
        elements.forEach(function (element) {
            element.classList.add("col-12");
        });
    });


    if (document.querySelector("#main .ce_text table")) {
        document.querySelectorAll("#main table").forEach(el => {
            let wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            el.parentNode.insertBefore(wrapper, el);
            wrapper.appendChild(el);
            el.classList.add("table", "table-striped", "table-hover");
        });
    }

    let elementsWithPullTop = document.querySelectorAll('.content--element:has(.pull-top)');
    elementsWithPullTop.forEach(element => {
        let previousSibling = element.previousElementSibling;
        if (previousSibling && previousSibling.classList.contains('content--element')) {
            previousSibling.style.marginBottom = '0';
        }
    });

    document.querySelectorAll(".widget.widget-submit").forEach(widget => {
        Array.from(widget.classList).forEach(cls => {
            if (cls.startsWith("btn")) {
                widget.classList.remove(cls);
            }
        });
    });
});
