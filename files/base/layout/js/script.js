function loadScript(url) {
    return new Promise(function(resolve, reject) {
        let script = document.createElement('script');
        script.src = url;
        script.async = false;
        script.onload = function() {
            resolve(url);
        };
        script.onerror = function() {
            reject(url);
        };
        document.body.appendChild(script);
    });
}

let scripts = [
    "/files/base/layout/_vendor/node_modules/dom7/dom7.min.js",
    "/files/base/layout/_vendor/node_modules/ssr-window/ssr-window.umd.min.js",
    "/files/base/layout/_vendor/node_modules/swiper/swiper-bundle.min.js",
    "/files/base/layout/_vendor/node_modules/vanilla-lazyload/dist/lazyload.min.js",
    "/files/base/layout/_vendor/node_modules/@popperjs/core/dist/umd/popper.min.js",
    "/files/base/layout/_vendor/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js",
    "/files/base/layout/_vendor/node_modules/aos/dist/aos.js",
];

// save all Promises as array
let promises = [];
scripts.forEach(function(url) {
    promises.push(loadScript(url));
});

Promise.all(promises)
    .then(function() {

        function isOnScreen(elem) {
            // if the element doesn't exist, abort
            if (elem.length == 0) {
                return;
            }
            var $window = jQuery(window)
            var viewport_top = $window.scrollTop()
            var viewport_height = $window.height()
            var viewport_bottom = viewport_top + viewport_height
            var $elem = jQuery(elem)
            var top = $elem.offset().top
            var height = $elem.height()
            var bottom = top + height

            return (top >= viewport_top && top < viewport_bottom) ||
                (bottom > viewport_top && bottom <= viewport_bottom) ||
                (height > viewport_height && top <= viewport_top && bottom >= viewport_bottom)
        }
        function changeNavLinks() {
            $('#mainNav li > *').each(function (index) {
                var hash = window.location.hash;

                if ($(this).attr('href') == hash) {
                    $('#mainNav .active').removeClass("active");
                    $(this).addClass("active");
                } else if ($(this).attr('href') == "#top") {
                    $('#mainNav .level_1 > .first > a').addClass("active");
                }
            });

            $('#mobileNav li > *').each(function (index) {
                var hash = window.location.hash;

                if ($(this).attr('href') == hash) {
                    $('#mobileNav .active').removeClass("active");
                    $('#mobileNav .mm-listitem_selected').removeClass("mm-listitem_selected");
                    $(this).addClass("active");
                    $(this).parent().addClass("mm-listitem_selected");
                } else if ($(this).attr('href') == "#top") {
                    $('#mobileNav .level_1 > .first').addClass("listitem_selected");
                }
            });
        }

        var lazyLoadInstance = new LazyLoad();
        changeNavLinks();

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
            offset: 100, // offset (in px) from the original trigger point
            delay: 150, // values from 0 to 3000, with step 50ms
            duration: 1550, // values from 0 to 3000, with step 50ms
            easing: 'ease', // default easing for AOS animations
            once: false, // whether animation should happen only once - while scrolling down
            mirror: false, // whether elements should animate out while scrolling past them
            anchorPlacement: 'top-bottom', // defines which position of the element regarding to window should trigger the animation

        });

        $('a[href^="#"]').click(function () {
            var href = $.attr(this, 'href');

            $('html, body').animate({
                scrollTop: $(href).offset().top
            }, 1000, function () {
                window.location.hash = href;
                changeNavLinks(href);
            });

            return false;
        });

        if (window.location.hash) {
            var hash = window.location.hash;

            if ($(hash).length) {
                changeNavLinks();
                $('html, body').animate({
                    scrollTop: $(hash).offset().top
                }, 1000);
            }
        }
        if ($('#main table').length) {
            $('#main table').each(function (index) {
                $(this).wrap('<div class="table-responsive"></div>')
                $(this).addClass("table").addClass("table-striped").addClass("table-hover");
            });
        }
        if ($('[data-bs-toggle="tooltip"]').length) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        }
        if ($('.ce--coloredrows').length) {
            $('.ce--coloredrows h1, .ce--coloredrows .h1, .ce--coloredrows h2, .ce--coloredrows .h2').each(function (i, v) {
                $(this).wrapInner("<span><span></span></span>");
            });
        }
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
        if ($('.header--content.type--1').length) {

            navWrapper = $('.hc--bottom');
            navContainer = $('.hc-bottom--right-col');
            navOffset = navContainer.offset().top - 15;


            function setHeaderHeight() {
                header = $('.header--content.type--1');
                headerHeight = navWrapper.innerHeight() + $('.hc--top').innerHeight();
                header.height(headerHeight);
            }

            function detectIfScrolled() {
                if ($(this).scrollTop() > navOffset) {
                    navWrapper.addClass('is--scrolling');
                } else {
                    navWrapper.removeClass('is--scrolling');
                }
            }

            setHeaderHeight();
            detectIfScrolled();


            $(window).scroll(function () {
                detectIfScrolled();
            });

            if (window.matchMedia('(min-width: 768px) and (orientation: portrait)').matches) {
                $(window).resize(function () {
                    setHeaderHeight();
                });
            }
        }
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
        if ($('.ce_form').length) {

            function addPlaceholders() {
                $('.widget.widget-text, .widget.widget-textarea').each(function (index) {
                    var placeholder = $(this).find("input, textarea").attr("placeholder");
                    var id = $(this).find("input, textarea").attr("id");
                    $(this).find("input, textarea").parent("div").addClass("form-floating")
                    $(this).find("input, textarea").parent("div").append("<label for='" + id + "'>" + placeholder + "</label>");
                });
            }


            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.ce_form form')

            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })


            $(".ce_form form").submit(function (e) {

                setTimeout(function () {
                    addPlaceholders();
                }, 250);

            })


            addPlaceholders();
        }
        if ($('.ce_rsce_headimagelogo').length) {
            $(".ce_rsce_headimagelogo .image--holder").css("min-height", "calc(100vh - " + $('header').height() + "px)");
        }
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


    }).catch(function(script) {
    console.log(script + ' failed to load');
});


