window.addEventListener("load", function (event) {
    var ceOnePageNavSmaller = document.querySelector(".ce_rsce_onepagenavi.with-smaller-containers");
    var ceOnePageNav = document.querySelector(".ce_rsce_onepagenavi");
    var mainElement = document.getElementById("main");
    var mobileToggle = document.querySelector(".onepage-nav--mobile-toggle");
    var styleOneElement = document.querySelector(".ce--onepagenavi.style-1");
    var styleTwoElement = document.querySelector(".ce--onepagenavi.style-2");
    var offset;

    if (ceOnePageNavSmaller) {
        mainElement.classList.add("with-onepage-nav");
    }

    if (ceOnePageNav) {
        mobileToggle.addEventListener("click", function () {
            this.parentElement.classList.toggle("visible");
        });

        if (styleOneElement) {
            offset = styleOneElement.getAttribute("data-offset");
        }

        window.addEventListener("scroll", function () {
            var scroll = window.pageYOffset;

            if (styleOneElement && scroll >= parseInt(offset, 10)) {
                styleOneElement.classList.remove("d-none");
            } else if (styleOneElement) {
                styleOneElement.classList.add("d-none");
            }
        }, {passive: true});

        var divElement = document.getElementById('onePageNav');
        var headerElement = document.querySelector('.header--content');
        var onepagenaviElement = document.querySelector('.ce_rsce_onepagenavi');
        var initialDivOffset = onepagenaviElement.offsetTop;


        window.addEventListener('scroll', function () {
            var headerHeight = headerElement.offsetHeight;
            var fixPosition = initialDivOffset - headerHeight;

            if (window.pageYOffset >= fixPosition && !divElement.classList.contains('is-scrolling')) {
                divElement.classList.add('is-scrolling');

                if (styleOneElement) {
                    divElement.style.top = (headerHeight + 20) + 'px';
                } else {
                    divElement.style.top = headerHeight + 'px';
                }

                if (styleTwoElement) {
                    if (window.innerWidth > 768) {
                        onepagenaviElement.style.height = divElement.offsetHeight + 'px';
                    }
                }
            } else if (window.pageYOffset < fixPosition) {
                divElement.classList.remove('is-scrolling');
                if (styleTwoElement) {
                    if (window.innerWidth > 768) {
                        onepagenaviElement.style.height = 'auto';
                    }
                }
            }
        }, {passive: true});


    }
}, {passive: true});
