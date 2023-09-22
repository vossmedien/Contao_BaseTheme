$(function () {
    var headerContent = document.querySelector(".header--content.fixed");
    var firstArticle = document.querySelector(".mod_article:first-of-type");
    //var firstElement = firstArticle.firstElementChild;

    if (headerContent) {
        var paddingTop = window.getComputedStyle(headerContent).height;
        document.body.style.paddingTop = paddingTop;
    }


// Funktion, um die Verschiebung für einzelne Elemente durchzuführen
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

// Für ".pull-top"
    adjustMargin(document.querySelectorAll('.pull-top'), 'top', true);

// Für ".pull-bottom"
    adjustMargin(document.querySelectorAll('.pull-bottom'), 'bottom', false);

// Für ".pull-start"
    adjustMargin(document.querySelectorAll('.pull-start'), 'left', true);

// Für ".pull-end"
    adjustMargin(document.querySelectorAll('.pull-end'), 'right', false);


    $("p.back > a:not(.btn), .widget-submit > button").each(function (index) {
        $(this).addClass("btn btn-primary");
    });

    $(".submit_container > input.button").each(function (index) {
        $(this).addClass("btn btn-lg");

        if ($(this).hasClass("next")) {
            $(this).addClass("btn-primary");
        }

        if ($(this).hasClass("previous")) {
            $(this).addClass("btn-outline-primary");
        }

        if ($(this).hasClass("confirm")) {
            $(this).addClass("btn-success");
        }
    });

    $(".submit_container button:not(.btn)").each(function (index) {
        $(this).addClass("btn");

        if (
            $(this).hasClass("button_update") ||
            $(this).hasClass("button_checkout")
        ) {
            $(this).addClass("btn-outline-primary");
        }
    });

    $(".actions_container .submit").each(function (index) {
        $(this).addClass("btn btn-primary");
    });

    $(".filter-toggle-control").each(function (index) {
        $(this).addClass("btn btn-primary");
    });

    $(".mod_iso_orderhistory td.link > a").each(function (index) {
        $(this).addClass("btn btn-sm");
    });

    $(".mod_iso_orderhistory td.link > a:first-child").each(function (index) {
        $(this).addClass("btn-primary");
    });

    $(".mod_iso_orderhistory td.link > a:last-child").each(function (index) {
        $(this).addClass("btn-secondary");
    });

    $(".mod_iso_addressbook a.add").each(function (index) {
        $(this).addClass("btn btn-outline-primary");
    });

    $("#footerNav ul").each(function (index) {
        $(this).addClass("list-inline");
    });

    $("#footerNav ul > li ").each(function (index) {
        $(this).addClass("list-inline-item");
    });

    //Alerts
    $("p.empty:not(.message)").each(function (index) {
        $(this).addClass("alert alert-primary");
    });

    $("p.error:not(.message)").each(function (index) {
        $(this).addClass("alert alert-danger");
    });

    $(".tl_confirm:not(.message)").each(function (index) {
        $(this).addClass("alert alert-success");
    });

    $(".widget-radio span.note").each(function (index) {
        $(this).addClass("alert alert-primary");
    });

    $(".message").each(function (index) {
        $(this).addClass("alert");

        if ($(this).hasClass("success")) {
            $(this).addClass("alert-success");
        }

        if ($(this).hasClass("empty")) {
            $(this).addClass("alert-primary");
        }

        if ($(this).hasClass("error")) {
            $(this).addClass("alert-danger");
        }
    });

    $(
        "#main .mod_article > .article-content > *:not(.content--element):not(.container):not(.ce_html):not(.mod_catalogMasterView):not(.mod_iso_productreader):not(.mod_catalogUniversalView):not(.mod_pageimage):not(style)"
    ).each(function (index) {
        $(this).wrapInner("<div class='container'></div>");
    });

    $(
        '#main > .inside > div[class^="mod_"]:not(.mod_article):not(.mod_iso_productreader):not(.mod_pageimage):not(.container):not(style)'
    ).each(function (index) {
        $(this).wrapInner("<div class='container'></div>");
    });

    $(
        "form:not(#iso_mod_checkout_review) > .formbody:not(.row):not(.no-row)"
    ).each(function (index) {
        $(this).addClass("row");
    });

    $("form .formbody > .fields > *").unwrap();
    $("form .formbody .address_new").addClass("row");
    $("form .formbody  p.alert ")
        .addClass("mt-0")
        .wrap('<div class="col-12"></div>');

    $("form .widget.form-control").each(function (index) {
        $(this).removeClass("form-control");
    });

    var labels = document.querySelectorAll("form label:not(.form-label)");
    labels.forEach(function (label) {
        label.className = "";
    });

    $("form >  .formbody:not(.no-row) > fieldset").each(function (index) {
        $(this).addClass("row").wrap('<div class="col-12"></div>');
    });

    var formBodies = document.querySelectorAll("form > .formbody:not(.no-row)");

    formBodies.forEach(function (formBody) {
        var elements = formBody.querySelectorAll(':scope > *:not([class^="col-"])');

        elements.forEach(function (element) {
            element.classList.add("col-12");
        });
    });

    var formFieldsetBodies = document.querySelectorAll(
        "form > .formbody fieldset"
    );

    formFieldsetBodies.forEach(function (FormFieldset) {
        var elements = FormFieldset.querySelectorAll(
            ':scope > *:not([class^="col-"])'
        );

        elements.forEach(function (element) {
            element.classList.add("col-12");
        });
    });

    if ($(".modal").length) {
        $(".modal").appendTo("body");
    }

    if ($("#main .ce_text table").length) {
        $("#main table").each(function (index) {
            $(this).wrap('<div class="table-responsive"></div>');
            $(this)
                .addClass("table")
                .addClass("table-striped")
                .addClass("table-hover");
        });
    }

    // Alle Elemente mit den Klassen .widget und .widget-submit auswählen
    var widgets = document.querySelectorAll(".widget.widget-submit");

    // Durch jedes Element iterieren
    widgets.forEach(function (widget) {
        // Die Klassen dieses Elements in ein Array konvertieren
        var classes = widget.className.split(" ");

        // Durch die Klassen iterieren
        classes.forEach(function (cls) {
            // Wenn die Klasse mit "btn" beginnt, entferne sie
            if (cls.startsWith("btn")) {
                widget.classList.remove(cls);
            }
        });
    });
});
