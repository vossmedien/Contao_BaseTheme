init(optionalScripts);

Promise.all(promises)
    .then(function () {

        if (options_lazyload) {
            var lazyLoadInstance = new LazyLoad();
        }
        if (options_aos) {
            $("*:not([data-aos])[class*=\"animate__\"]").each(function (index) {
                var classes = $.grep(this.className.split(" "), function (v, i) {
                    return v.indexOf('animate__') === 0;
                }).join();
                $(this).removeClass(classes);
                $(this).attr("data-aos", classes);
            });

            AOS.init({
                // Global settings:
                disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
                startEvent: 'DOMContentLoaded', // name of the event dispatched on the document, that AOS should initialize on
                initClassName: false, // class applied after initialization
                animatedClassName: 'animate__animated', // class applied on animation
                useClassNames: true, // if true, will add content of `data-aos` as classes on scroll
                disableMutationObserver: false, // disables automatic mutations' detections (advanced)
                debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
                throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)

                // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
                offset: 60, // offset (in px) from the original trigger point

                //Settings VIA CSS under :root {}
                    //delay: 0, // values from 0 to 3000, with step 50ms
                    //duration: 400, // values from 0 to 3000, with step 50ms
                    //easing: 'ease', // default easing for AOS animations

                once: false, // whether animation should happen only once - while scrolling down
                mirror: false, // whether elements should animate out while scrolling past them
                anchorPlacement: 'top-bottom', // defines which position of the element regarding to window should trigger the animation
            });
        }
        if (options_popper) {
            if ($('[data-bs-toggle="tooltip"]').length) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                })
            }
        }

        /* Wrap Elements that are not custom-elements in container (as fallback)
        $("#main .mod_article > div:not(.content--element):not(.container)").each(function (index) {
            $(this).wrap('<div class="container"></div>');
        });
        /* END */

        /* Smooth Scrolling and set correct Item active */
        $('body.onepage').on('click', '#mainNav a[href^="#"]', function (e) {
            e.preventDefault();
            var targetSelector = this.hash;
            var $target = $(targetSelector);
            var href = $.attr(this, 'href');

            window.location.hash = href;
            changeNavLinks(href);

            $('html, body').animate(
                {
                    scrollTop: $target.offset().top
                }, {
                    duration: 2500,
                    step: function (now, fx) {
                        var newOffset = $target.offset().top - 50;
                        if (fx.end !== newOffset)
                            fx.end = newOffset;
                    }
                }
            );
        });
        $('body').on('click', '#main .mod_article a[href^="#"]', function (e) {
            e.preventDefault();
            var targetSelector = this.hash;
            var $target = $(targetSelector);
            var href = $.attr(this, 'href');

            window.location.hash = href;
            changeNavLinks(href);

            $('html, body').animate(
                {
                    scrollTop: $target.offset().top
                }, {
                    duration: 2500,
                    step: function (now, fx) {
                        var newOffset = $target.offset().top - 50;
                        if (fx.end !== newOffset)
                            fx.end = newOffset;
                    }
                }
            );
        });
        if (window.location.hash) {
            var hash = window.location.hash;

            if ($(hash).length) {
                changeNavLinks();

                $('html, body').animate(
                    {
                        scrollTop: $(hash).offset().top
                    }, {
                        duration: 2500,
                        step: function (now, fx) {
                            var newOffset = $(hash).offset().top - 50;
                            if (fx.end !== newOffset)
                                fx.end = newOffset;
                        }
                    }
                );

            }
        }
        /* END */

        /* Transform tables in Elements to Bootstrap Tables (styled) */
        if ($('#main .ce_text table').length) {
            $('#main table').each(function (index) {
                $(this).wrap('<div class="table-responsive"></div>')
                $(this).addClass("table").addClass("table-striped").addClass("table-hover");
            });
        }
        /* END */

        /* Wrap Headlines from colored-rows in extra span */
        if ($('.ce--coloredrows').length) {
            $('.ce--coloredrows h1, .ce--coloredrows .h1, .ce--coloredrows h2, .ce--coloredrows .h2').each(function (i, v) {
                $(this).wrapInner("<span><span></span></span>");
            });
        }
        /* END */

        /* Animated Upcounting when Element is in Viewport */
        if ($('.count').length) {
            function startCounter() {
                $('.count').each(function (index) {
                    if (isOnScreen($(this)) && !$(this).hasClass("doneCounting")) {

                        var size = $(this).text().split(".")[1] ? $(this).text().split(".")[1].length : 0;
                        $(this).prop('Counter', 0).animate({
                            Counter: $(this).text()
                        }, {
                            duration: 2000,
                            easing: 'swing',
                            step: function (now) {
                                $(this).text(parseFloat(now).toFixed(size));
                                $(this).addClass("doneCounting")
                            }
                        });
                    }
                });
            }

            startCounter();

            $(window).on('resize scroll', function () {
                startCounter();
            });
        }
        /* END */

        /* Behavior of Header Type 1 (fixed) */
        if ($('.header--content.type--1:not(.fixed)').length) {

            navWrapper = $('.hc--bottom');
            navWrapperHeight = $('.hc--bottom').outerHeight();
            navContainer = $('.hc-bottom--right-col');
            navOffset = navContainer.offset().top - 30;
            imageHeight = $('.ce--mainimage > .image--holder').data("height");

            if (!imageHeight) {
                imageHeight = 100;
            }


            $(".ce--mainimage .image--holder").css({'max-height': 'calc(' + imageHeight + 'vh - ' + $('header').height() + 'px)'});


            function detectIfScrolled() {
                if ($(this).scrollTop() > navOffset) {
                    navWrapper.addClass('is--scrolling');
                    $('.header--content .hc--top').css("margin-bottom", navWrapperHeight + 'px')

                } else {
                    navWrapper.removeClass('is--scrolling');
                    $('.header--content .hc--top').css("margin-bottom", '0px')
                }
            }


            detectIfScrolled();


            $(window).scroll(function () {
                detectIfScrolled();
            });


        }
        /* END */

        /* Behavior of Header Type 1 (not fixed) */
        if ($('.header--content.type--1.fixed').length) {

            navWrapper = $('.hc--bottom');
            navContainer = $('.hc-bottom--right-col');
            navOffset = navContainer.offset().top - 15;


            function detectIfScrolled() {
                if ($(this).scrollTop() > navOffset) {
                    navWrapper.addClass('is--scrolling');
                } else {
                    navWrapper.removeClass('is--scrolling');
                }
            }


            detectIfScrolled();


            $(window).scroll(function () {
                detectIfScrolled();
            });


        }
        /* END */

        /* Behavior of Header Type 2 */
        if ($('.header--content.type--2').length) {

            navWrapper = $('.hc--bottom');
            navContainer = navWrapper;
            navOffset = navContainer.offset().top - 15;


            function detectIfScrolled() {
                if ($(this).scrollTop() > navOffset) {
                    navWrapper.addClass('is--scrolling');
                } else {
                    navWrapper.removeClass('is--scrolling');
                }
            }


            detectIfScrolled();


            $(window).scroll(function () {
                detectIfScrolled();
            });
        }
        /* END */

        /* Add Floating Placeholders to Inputs */
        if ($('.ce_form').length) {
            function addPlaceholders() {
                $('.widget.widget-text, .widget.widget-textarea').each(function (index) {
                    var placeholder = $(this).find("input, textarea").attr("placeholder");
                    var id = $(this).find("input, textarea").attr("id");
                    if (placeholder) {
                        $(this).find("input, textarea").parent("div").addClass("form-floating")
                        $(this).find("input, textarea").parent("div").append("<label for='" + id + "'>" + placeholder + "</label>");
                    }
                });
            }

            $(".ce_form form").submit(function (e) {
                setTimeout(function () {
                    addPlaceholders();
                }, 250);
            })

            addPlaceholders();
        }
        /* END */

        /* Add Floating Placeholders to Inputs */
        if ($('.scrollToTop').length) {
            $(".scrollToTop").click(function () {
                $("html,body").animate({scrollTop: $("#top").offset().top}, "500");
                return false
            })
            $(window).scroll(function () {
                if ($(this).scrollTop() > 50) {
                    $('.scrolltop:hidden').stop(true, true).fadeIn();
                } else {
                    $('.scrolltop').stop(true, true).fadeOut();
                }
            });
        }

    }).catch(function (script) {
    console.log(script + ' failed to load');
});
