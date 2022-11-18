$(function () {

    $('p.back > a:not(.btn), .widget-submit > button').each(function (index) {
        $(this).addClass("btn btn-primary");
    });

    $('.submit_container > input.button').each(function (index) {
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

    $('.submit_container  button:not(.btn)').each(function (index) {
        $(this).addClass("btn");

        if ($(this).hasClass("button_update") || $(this).hasClass("button_checkout")) {
            $(this).addClass("btn-outline-primary");
        }
    });

    $('.actions_container .submit').each(function (index) {
        $(this).addClass("btn btn-primary");
    });

    $('.filter-toggle-control').each(function (index) {
        $(this).addClass("btn btn-primary");
    });

    $('.mod_iso_orderhistory td.link > a').each(function (index) {
        $(this).addClass("btn btn-sm");
    });

    $('.mod_iso_orderhistory td.link > a:first-child').each(function (index) {
        $(this).addClass("btn-primary");
    });

    $('.mod_iso_orderhistory td.link > a:last-child').each(function (index) {
        $(this).addClass("btn-secondary");
    });

    $('.mod_iso_addressbook a.add').each(function (index) {
        $(this).addClass("btn btn-outline-primary");
    });


    $('#footerNav ul').each(function (index) {
        $(this).addClass("list-inline");
    });

    $('#footerNav ul > li ').each(function (index) {
        $(this).addClass("list-inline-item");
    });


    /* ALERTS */
    $('p.empty:not(.message)').each(function (index) {
        $(this).addClass("alert alert-primary");
    });

    $('p.error:not(.message)').each(function (index) {
        $(this).addClass("alert alert-danger");
    });

    $('.tl_confirm:not(.message)').each(function (index) {
        $(this).addClass("alert alert-success");
    });

    $('.widget-radio span.note').each(function (index) {
        $(this).addClass("alert alert-primary");
    });


    $('.message').each(function (index) {
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
    /* ALERTS ENDE */


    $('#main .mod_article > *:not(.content--element):not(.container):not(.ce_html):not(.mod_catalogMasterView):not(.mod_iso_productreader):not(.mod_catalogUniversalView):not(.mod_pageimage)').each(function (index) {
        $(this).wrapInner("<div class='container'></div>");
    });

    $('#main > .inside > div[class^="mod_"]:not(.mod_article):not(.mod_iso_productreader):not(.mod_pageimage):not(.container)').each(function (index) {
        $(this).wrapInner("<div class='container'></div>");
    });


    $('form:not(#iso_mod_checkout_review) >  .formbody:not(.row)').each(function (index) {
        $(this).addClass("row");
    });

    $('form .formbody > .fields > *').unwrap();
    $('form .formbody .address_new').addClass("row");
    $('form .formbody  p.alert ').addClass("mt-0").wrap('<div class="col-12"></div>');


    $('form .widget.form-control').each(function (index) {
        $(this).removeClass("form-control");
    });

    $('form >  .formbody > fieldset').each(function (index) {
        $(this).addClass("row");
    });


    if ($(".modal").length) {
        $(".modal").appendTo("body");
    }


    /* Transform tables in Elements to Bootstrap Tables (styled) */
    if ($("#main .ce_text table").length) {
        $("#main table").each(function (index) {
            $(this).wrap('<div class="table-responsive"></div>');
            $(this)
                .addClass("table")
                .addClass("table-striped")
                .addClass("table-hover");
        });
    }
    /* END */

});
