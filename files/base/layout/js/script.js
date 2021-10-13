function isOnScreen(elem) {
    // if the element doesn't exist, abort
    if( elem.length == 0 ) {
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



$(function () {
    var lazyLoadInstance = new LazyLoad();


    //Anchor Scrolling
    function changeNavLinks(){
        $('#mainNav li > *').each(function (index) {
            var hash = window.location.hash;

            if($(this).attr('href') == hash){
                $('#mainNav .active').removeClass("active");
                $(this).addClass("active");
            }
        });

        $('#mobileNav li > *').each(function (index) {
            var hash = window.location.hash;

            if($(this).attr('href') == hash){
                $('#mobileNav .active').removeClass("active");
                $('#mobileNav .mm-listitem_selected').removeClass("mm-listitem_selected");
                $(this).addClass("active");
                $(this).parent().addClass("mm-listitem_selected");
            }
        });
    }

    $('nav a[href^="#"]').click(function() {
        var href = $.attr(this, 'href');

        $('html, body').animate({
            scrollTop: $(href).offset().top
        }, 850, function () {
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
            }, 850);
        }
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
    if ($('[data-sal]').length) {
        const scrollAnimations = sal({
            threshold: .2,
            once: true,
            selector: "[data-sal]",
            animateClassName: "sal-animate",
            disabledClassName: "sal-disabled",
            rootMargin: "0% 50%",
            enterEventName: "sal:in",
            exitEventName: "sal:out"
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


        function setHeaderHeight(){
            header  = $('.header--content.type--1');
            headerHeight = navWrapper.innerHeight() + $('.hc--top').innerHeight();
            header.height(headerHeight);
        }

        function detectIfScrolled(){
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

        $(window).resize(function () {
            //setHeaderHeight();
        });
    }
    if ($('.ce_form').length) {
        $('.input-group-text').each(function (index) {
            $(this).html($(this).text());
            $(this).addClass("transformed");
        });

        $('.widget.widget-text, .widget.widget-textarea').each(function (index) {
            var placeholder = $(this).find("input, textarea").attr("placeholder");
            var id = $(this).find("input, textarea").attr("id");
            $(this).find("input, textarea").parent("div").addClass("form-floating")
            $(this).find("input, textarea").parent("div").append("<label for='" + id + "'>" + placeholder + "</label>");
        });
    }
    if ($('.ce_rsce_headimagelogo').length) {
        $(".ce_rsce_headimagelogo .image--holder").css("min-height", "calc(100vh - " + $('header').height() + "px)");
    }
});

if ($('.scrollToTop').length) {
    $(function () {
        $(".").click(function () {
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
    });
}
if ($('.swiper:not(.custom)').length) {
    const basicSlider = () => {
        let basicSliders = document.querySelectorAll('.swiper:not(.custom)')
        let prevArrow = document.querySelectorAll('.swiper:not(.custom) > .swiper-button-prev')
        let nextArrow = document.querySelectorAll('.swiper:not(.custom) > .swiper-button-next')
        let pagination = document.querySelectorAll('.swiper:not(.custom) >.swiper-pagination')
        basicSliders.forEach((slider, index) => {
            const swiper = new Swiper(slider, {
                direction: 'horizontal',
                loop: true,
                preloadImages: true,
                lazy: false,
                navigation: {
                    // the 'index' bit below is just the order of the class in the queryselectorAll array, so the first one would be NextArrow[0] etc
                    nextEl: nextArrow[index],
                    prevEl: prevArrow[index],
                },
                pagination: {
                    el: pagination[index],
                    clickable: true
                }
            });
        })
    }
    window.addEventListener('load', basicSlider)
}



