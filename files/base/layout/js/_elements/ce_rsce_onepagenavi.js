window.addEventListener("load", function (event) {
    var mainElement = document.getElementById("main");
    var mobileToggle, styleOneElement, styleTwoElement, ceOnePageNav, divElement, onepagenaviElement, offset, initialDivOffset;
    var isMobile = window.innerWidth <= 768; // Schwellenwert für mobile Ansicht

    function initializeVariables() {
        ceOnePageNav = document.querySelector(".ce_rsce_onepagenavi");
        mobileToggle = document.querySelector(".onepage-nav--mobile-toggle");
        styleOneElement = document.querySelector(".ce--onepagenavi.style-1");
        styleTwoElement = document.querySelector(".ce--onepagenavi.style-2");
        divElement = document.getElementById('onePageNav');
        onepagenaviElement = document.querySelector('.ce_rsce_onepagenavi');

        if (styleOneElement) {
            offset = parseInt(styleOneElement.getAttribute("data-offset"), 10);
        }

        if (divElement) {
            initialDivOffset = divElement.getBoundingClientRect().top + window.pageYOffset;
        }

        var ceOnePageNavSmaller = document.querySelector(".ce_rsce_onepagenavi.with-smaller-containers");
        if (ceOnePageNavSmaller) {
            mainElement.classList.add("with-onepage-nav");
        }
    }

    function updateNavPosition() {
        if (!divElement) return;

        var currentDivOffset = divElement.getBoundingClientRect().top + window.pageYOffset;

        if (!isMobile && window.pageYOffset >= initialDivOffset && !divElement.classList.contains('is-scrolling')) {
            divElement.classList.add('is-scrolling');

            if (styleOneElement) {
                divElement.style.top = '20px';
            } else {
                divElement.style.top = '0';
            }

            if (styleTwoElement) {
                onepagenaviElement.style.height = divElement.offsetHeight + 'px';
            }
        } else if (isMobile || window.pageYOffset < initialDivOffset) {
            divElement.classList.remove('is-scrolling');
            divElement.style.top = '';
            if (styleTwoElement) {
                onepagenaviElement.style.height = 'auto';
            }
        }
    }

    function handleScroll() {
        var scroll = window.pageYOffset;

        if (styleOneElement && scroll >= offset) {
            styleOneElement.classList.remove("d-none");
        } else if (styleOneElement) {
            styleOneElement.classList.add("d-none");
        }

        updateNavPosition();
    }

    function handleResize() {
        var wasDesktop = !isMobile;
        isMobile = window.innerWidth <= 768;

        initializeVariables();

        if (wasDesktop && isMobile) {
            // Wechsel von Desktop zu Mobile
            divElement.classList.remove('is-scrolling');
            divElement.style.top = '';
            if (styleTwoElement) {
                onepagenaviElement.style.height = 'auto';
            }
        } else if (!wasDesktop && !isMobile) {
            // Wechsel von Mobile zu Desktop
            updateNavPosition();
        }
    }

    initializeVariables();

    if (ceOnePageNav) {
        if (mobileToggle) {
            mobileToggle.addEventListener("click", function () {
                this.parentElement.classList.toggle("visible");
            });
        }

        window.addEventListener("scroll", handleScroll, {passive: true});
        window.addEventListener('resize', handleResize, {passive: true});

        // Führe updateNavPosition sofort nach dem Laden der Seite aus
        updateNavPosition();
    }
}, {passive: true});