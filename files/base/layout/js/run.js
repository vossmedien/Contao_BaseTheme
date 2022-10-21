init(optionalScripts);

Promise.all(promises)
    .then(function () {
        if (options_lazyload) {
            var lazyLoadInstance = new LazyLoad();
        }
        if (options_aos) {
            $('*:not([data-aos])[class*="animate__"]').each(function (index) {
                var classes = $.grep(this.className.split(" "), function (v, i) {
                    return v.indexOf("animate__") === 0;
                }).join();
                $(this).removeClass(classes);
                $(this).attr("data-aos", classes);
            });

            AOS.init({
                // Global settings:
                disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
                startEvent: "DOMContentLoaded", // name of the event dispatched on the document, that AOS should initialize on
                initClassName: false, // class applied after initialization
                animatedClassName: "animate__animated", // class applied on animation
                useClassNames: true, // if true, will add content of `data-aos` as classes on scroll
                disableMutationObserver: false, // disables automatic mutations' detections (advanced)
                //debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
                //throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)

                // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
                //offset: 0, // offset (in px) from the original trigger point

                once: true, // whether animation should happen only once - while scrolling down
                mirror: false, // whether elements should animate out while scrolling past them
                anchorPlacement: "top-bottom", // defines which position of the element regarding to window should trigger the animation
            });
        }
        if (options_popper) {
            if ($('[data-bs-toggle="tooltip"]').length) {
                var tooltipTriggerList = [].slice.call(
                    document.querySelectorAll('[data-bs-toggle="tooltip"]')
                );
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        }

        /* Smooth Scrolling and set correct Item active */
        if (window.location.hash) {
            var hash = window.location.hash;

            if ($(hash).length) {
                changeNavLinks();
            }
        }

        /* END */

        $(document).on("mouseenter", ".mod_navigation li > a", function () {
            $(".mod_navigation li.mm_container").removeClass("active");

            if ($(this).parent().hasClass("mm_container")) {
                $(this).parent().addClass("active");
            }
        });

        $(document).on(
            "mouseleave",
            ".mod_navigation li.mm_container .mm_dropdown > .inner",
            function () {
                setTimeout(function () {
                    $(".mod_navigation li.mm_container").removeClass("active");
                }, 500);
            }
        );

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

        /* Wrap Headlines from colored-rows in extra span */
        if ($(".ce--coloredrows").length) {
            $(
                ".ce--coloredrows h1, .ce--coloredrows .h1, .ce--coloredrows h2, .ce--coloredrows .h2"
            ).each(function (i, v) {
                $(this).wrapInner("<span><span></span></span>");
            });
        }
        /* END */

        if ($(".ce_rsce_onepagenavi.with-smaller-containers").length) {
            $("#main").addClass("with-onepage-nav");
        }
        if ($(".ce_rsce_onepagenavi").length) {
            $(".onepage-nav--mobile-toggle").click(function () {
                $(this).parent("div").toggleClass("visible");
            });

            $(window).scroll(function () {
                var scroll = $(window).scrollTop();
                if (scroll >= $(".ce--onepagenavi").data("offset")) {
                    $(".ce--onepagenavi").removeClass("d-none");
                } else {
                    $(".ce--onepagenavi").addClass("d-none");
                }
            });
        }
        if ($(".ce_rsce_colorpalettes").length) {
            $(".color-list--element").click(function () {
                var storeDesc = $(this).data("desc");
                var storeHeadline = $(this).data("headline");
                var storeTitle = $(this).data("title");
                var storeImg = $(this).data("img");
                var parentDiv = $(this).closest(".ce_rsce_colorpalettes");
                var basicHeadline = parentDiv
                    .find(".colorpalettes-top")
                    .data("basicheadline");
                var basicDesc = parentDiv.find(".colorpalettes-top").data("basicdesc");

                if (storeDesc) {
                    parentDiv.find(".color-palette--desc").html(storeDesc);
                } else {
                    parentDiv.find(".color-palette--desc").html(basicDesc);
                }

                if (storeHeadline) {
                    parentDiv.find(".color-palette--headline").html(storeHeadline);
                } else {
                    parentDiv.find(".color-palette--headline").html(basicHeadline);
                }

                if (storeTitle) {
                    parentDiv.find(".selected-color-element--title").html(storeTitle);
                } else {
                }

                if (storeImg) {
                    parentDiv
                        .find(".image-holder")
                        .css("background-image", "url(" + storeImg + ")");
                } else {
                }
            });
        }

        if ($(".ce_rsce_sitemap").length) {
            $($(".mm_dropdown a")).each(function (index) {
                var originalUrl = window.location.pathname;
                if (originalUrl.length > 1) {
                    originalUrl = window.location.pathname.substring(1);
                }

                if ($(this).attr("href") == originalUrl) {
                    $(this).addClass("active");
                }
            });
        }

        if ($(".ce_rsce_switchingcards").length) {
            $(".flipping-card--wrapper").each(function (index) {
                var front = $(this).find(".flipping-card--front");
                var back = $(this).find(".flipping-card--back");
                var frontHeight = front.height();
                var backHeight = back.height();

                if (backHeight > frontHeight) {
                    front.css("height", backHeight);
                } else {
                    back.css("height", frontHeight);
                }
            });
        }

        /* Animated Upcounting when Element is in Viewport */
        if ($(".count").length) {
            function startCounter() {
                $(".count").each(function (index) {
                    if (isOnScreen($(this)) && !$(this).hasClass("doneCounting")) {
                        var size = $(this).text().split(".")[1]
                            ? $(this).text().split(".")[1].length
                            : 0;
                        $(this)
                            .prop("Counter", 0)
                            .animate(
                                {
                                    Counter: $(this).text(),
                                },
                                {
                                    duration: 2000,
                                    easing: "swing",
                                    step: function (now) {
                                        $(this).text(parseFloat(now).toFixed(size));
                                        $(this).addClass("doneCounting");
                                    },
                                }
                            );
                    }
                });
            }

            startCounter();

            $(window).on("resize scroll", function () {
                startCounter();
            });
        }
        /* END */

        /* Behavior of Header Type 1 (not fixed) */
        if ($(".header--content.type--1:not(.fixed)").length) {
            navWrapper = $(".hc--bottom");
            navWrapperHeight = $(".hc--bottom").outerHeight();
            navContainer = $(".hc-bottom--right-col");
            navOffset = navContainer.offset().top - 30;
            imageHeight = $(".ce--mainimage > .image--holder").data("height");

            if (!imageHeight) {
                imageHeight = 100;
            }

            $(".ce--mainimage .image--holder:not(.with-maxheight)").css({
                "max-height":
                    "calc(" + imageHeight + "vh - " + $("header").height() + "px)",
            });

            function detectIfScrolled() {
                if ($(this).scrollTop() > navOffset) {
                    navWrapper.addClass("is--scrolling");
                    $(".header--content .hc--top").css(
                        "margin-bottom",
                        navWrapperHeight + "px"
                    );
                } else {
                    navWrapper.removeClass("is--scrolling");
                    $(".header--content .hc--top").css("margin-bottom", "0px");
                }
            }

            detectIfScrolled();

            $(window).scroll(function () {
                detectIfScrolled();
            });
        }
        /* END */

        /* Behavior of Header Type 1 (fixed) */
        if ($(".header--content.type--1.fixed").length) {
            navWrapper = $(".hc--bottom");
            navContainer = $(".hc-bottom--right-col");
            navOffset = navContainer.offset().top - 15;

            function detectIfScrolled() {
                if ($(this).scrollTop() > navOffset) {
                    navWrapper.addClass("is--scrolling");
                } else {
                    navWrapper.removeClass("is--scrolling");
                }
            }

            detectIfScrolled();

            $(window).scroll(function () {
                detectIfScrolled();
            });
        }
        /* END */

        /* Behavior of Header Type 2 */
        if ($(".header--content.type--2").length) {
            navWrapper = $(".hc--bottom");

            if ($(".mod_pageImage").length) {
                navContainer = navWrapper;
                navOffset = navContainer.offset().top - 15;

            } else {
                navOffset = $('.mainslider').height();
                navWrapper.css("bottom", "auto");
                navWrapper.css("top", navOffset - navWrapper.height());
            }

            function detectIfScrolled() {
                if ($(this).scrollTop() > (navOffset - navWrapper.height())) {
                    navWrapper.addClass("is--scrolling");
                    navWrapper.css("top", "0px");
                } else {
                    navWrapper.removeClass("is--scrolling");
                    navWrapper.css("top", navOffset - navWrapper.height());
                }
            }

            detectIfScrolled();

            $(window).scroll(function () {
                detectIfScrolled();
            });

        }
        /* END */


        /* Behavior of Header Type 6 */
        if ($(".header--content.type--6").length) {

            function detectIfScrolled() {
                var scroll = $(window).scrollTop();
                var os = $('#header').offset().top;
                var ht = $('#header').height();
                if (scroll > os + ht) {
                    $('body').addClass('is--scrolling');
                } else {
                    $('body').removeClass('is--scrolling');
                }
            }

            detectIfScrolled();

            $(window).scroll(function () {
                detectIfScrolled();
            });
        }
        /* END */


        if ($(".accordion-nav").length) {
            $(".accordion-nav").find("i").click(function (e) {
                $(this).closest("li").toggleClass("expanded");
            });
        }


        if ($(".mod_mmenuHtml a.offCanvasBasketOpener").length) {
            $(".mod_mmenuHtml a.offCanvasBasketOpener").click(function (e) {
                setTimeout(function () {
                    $('body').addClass("mm-wrapper_opened mm-wrapper_blocking")
                }, 1000);
            });
        }

        if ($(".mmenu_close_button").length) {
            $(".mmenu_close_button").click(function (e) {
                e.preventDefault();
            });
        }


        /* Add Floating Placeholders to Inputs */
        if ($(".ce_form").length) {
            function addPlaceholders() {
                $(".widget").each(function (
                    index
                ) {
                    var placeholder = $(this).find("input, textarea").attr("placeholder");
                    var id = $(this).find("input, textarea").attr("id");
                    if (placeholder) {
                        $(this)
                            .find("input, textarea")
                            .parent("div")
                            .addClass("form-floating");
                        $(this)
                            .find("input, textarea")
                            .parent("div")
                            .append("<label for='" + id + "'>" + placeholder + "</label>");

                        $(this).find("label:first-child").remove();
                    }
                });
            }

            $(".ce_form form").submit(function (e) {
                setTimeout(function () {
                    addPlaceholders();
                }, 250);
            });

            addPlaceholders();
        }
        /* END */


        /* Change Buttons with JS instead of CSS */


        $('p.back > a, .widget-submit > button').each(function (index) {
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

        $('.submit_container  button').each(function (index) {
            $(this).addClass("btn");

            if ($(this).hasClass("button_update") || $(this).hasClass("button_checkout")) {
                $(this).addClass("btn-outline-primary");
            }
        });

        $('.submit_container .submit').each(function (index) {
            //$(this).addClass("btn btn-primary btn-lg");
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

        $('p.empty').each(function (index) {
            $(this).addClass("alert bg-info");
        });

        $('p.error').each(function (index) {
            $(this).addClass("alert bg-danger");
        });

        $('.widget-radio span.note').each(function (index) {
            $(this).addClass("alert bg-primary");
        });


        $('.message').each(function (index) {
            $(this).addClass("alert");

            if ($(this).hasClass("success")) {
                $(this).addClass("bg-success");
            }

            if ($(this).hasClass("empty")) {
                $(this).addClass("bg-primary");
            }

            if ($(this).hasClass("error")) {
                $(this).addClass("bg-danger");
            }
        });

        $('#footerNav ul').each(function (index) {
            $(this).addClass("list-inline");
        });

        $('#footerNav ul > li ').each(function (index) {
            $(this).addClass("list-inline-item");
        });


        $('#main .mod_article > *:not(.content--element):not(.container):not(.ce_html):not(.mod_catalogMasterView):not(.mod_catalogUniversalView)').each(function (index) {
            $(this).addClass("container");
        });

        $('#main > .inside > div[class^="mod_"]:not(.mod_article)').each(function (index) {
            $(this).addClass("container");
        });

        $('.formbody:not(.row)').each(function (index) {
            $(this).addClass("row");
        });

        if ($(".modal").length) {
            $(".modal").appendTo("body");
        }

        if ($(".scrollToTop, .BodyScrollToTop").length) {
            $(".scrollToTop, .BodyScrollToTop").click(function () {
                $("html,body").animate({scrollTop: $("#top").offset().top}, "500");
                return false;
            });
            $(window).scroll(function () {
                if ($(this).scrollTop() > 50) {
                    $(".BodyScrollToTop").addClass("visible");
                } else {
                    $(".BodyScrollToTop").removeClass("visible");
                }
            });
        }

        $(document).on("scroll", function () {
            changeAnchorLinks();
        });


        function setupFunctions() {
            if (Cookies.get('cookie_iframes')) {
                initFrames();
            }

            if (Cookies.get('cookie_basefeatures')) {
                InitBasefeatures()
            }
        }


        function initFrames() {
            $('iframe[data-source]').each(function (index) {
                $(this).attr('src', $(this).data("source"));
            });

            $('.video-link').colorbox({
                iframe: true,
                width: '95%',
                height: '95%',
                maxWidth: '1024px',
                maxHeight: '576px',
                href: function () {
                    var videoId = new RegExp('[\\?&]v=([^&#]*)').exec(this.href);
                    if (videoId && videoId[1]) {
                        return 'https://www.youtube-nocookie.com/embed/' + videoId[1] + '?rel=0&wmode=transparent&autoplay=1';
                    }
                }
            });
        }

        function InitBasefeatures() {

        }


        window.addEventListener('cookiebar_save', function (e) {
            setupFunctions()
        }, false);


        setupFunctions()

    })
    .catch(function (script) {
        console.log(script + " failed to load");
    });


