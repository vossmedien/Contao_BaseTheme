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
            navContainer = navWrapper;
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

        /* Add Floating Placeholders to Inputs */
        if ($(".ce_form").length) {
            function addPlaceholders() {
                $(".widget.widget-text, .widget.widget-textarea").each(function (
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
    })
    .catch(function (script) {
        console.log(script + " failed to load");
    });
