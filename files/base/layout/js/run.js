init(optionalScripts);

Promise.all(promises)
  .then(function () {
    const popoverTriggerList = document.querySelectorAll(
      '[data-bs-toggle="popover"]'
    );
    const popoverList = [...popoverTriggerList].map(
      (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl)
    );
    var navWrapperHeight = 0;

    if (options_lazyload) {
      var lazyLoadInstance = new LazyLoad({
        callback_loaded: function (element) {
          // Prüfen, ob das geladene Bild im <header> liegt
          if (element.closest("header")) {
            // Neue Zuweisung der Variable
            navWrapperHeight = $(".hc--bottom").outerHeight();
          }
        },
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

    /* MEGAMENÜ */
    $(document).on(
      "mouseenter",
      ".mod_navigation li > *:first-child",
      function () {
        $(".mod_navigation li.mm_container").removeClass("megamenu-active");

        if ($(this).parent().hasClass("mm_container")) {
          $(this).parent().addClass("megamenu-active");
        }
      }
    );

    $(document).on(
      "mouseleave",
      ".mod_navigation li.mm_container .mm_dropdown > .inner",
      function () {
        setTimeout(function () {
          $(".mod_navigation li.mm_container").removeClass("megamenu-active");
        }, 500);
      }
    );
    /* MEGAMENÜ END */

    /* MMENU ADDONS */
    if ($(".mod_mmenuHtml a.offCanvasBasketOpener").length) {
      $(".mod_mmenuHtml a.offCanvasBasketOpener").click(function (e) {
        setTimeout(function () {
          $("body").addClass("mm-wrapper_opened mm-wrapper_blocking");
        }, 1000);
      });
    }

    if ($(".mmenu_close_button").length) {
      $(".mmenu_close_button").click(function (e) {
        e.preventDefault();
      });
    }
    /* MMENU ADDONS END */

    if ($(".accordion-nav").length) {
      $(".accordion-nav")
        .find("i")
        .click(function (e) {
          $(this).closest("li").toggleClass("expanded");
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
        if (navWrapperHeight < $(".hc--bottom").outerHeight()) {
          navWrapperHeight = $(".hc--bottom").outerHeight();
        }

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
        navOffset = $(".mainslider").height();
        navWrapper.css("bottom", "auto");
        navWrapper.css("top", navOffset - navWrapper.height());
      }

      function detectIfScrolled() {
        if ($(this).scrollTop() > navOffset - navWrapper.height()) {
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
        var os = $("#header").offset().top;
        var ht = $("#header").height();
        if (scroll > os + ht) {
          $("body").addClass("is--scrolling");
        } else {
          $("body").removeClass("is--scrolling");
        }
      }

      detectIfScrolled();

      $(window).scroll(function () {
        detectIfScrolled();
      });
    }
    /* END */

    /* Behavior of Header Type 7 */
    if ($(".header--content.type--7").length) {
      function detectIfScrolled() {
        var scroll = $(window).scrollTop();
        var os = $("#header").offset().top;
        var ht = $("#header").height();

        if (scroll > os + ht) {
          $("#header").addClass("is--scrolling");
        } else {
          $("#header").removeClass("is--scrolling");
        }
      }

      detectIfScrolled();

      $(window).scroll(function () {
        detectIfScrolled();
      });

      var desktopNavActivator = document.querySelector(".desktopNavActivator");
      var expandableNav = document.querySelector(".expandable-nav");
      var closeButton = document.querySelector(".expandable-nav--close");

      desktopNavActivator.addEventListener("click", function () {
        expandableNav.classList.add("is-open");
      });

      closeButton.addEventListener("click", function () {
        expandableNav.classList.remove("is-open");
      });
    }
    /* END */

    if ($(".matrix").length) {
      $(".matrix td input").each(function (a, b) {
        $(b)
          .parent()
          .click(function (a) {
            "radio" == $(b).attr("type")
              ? $(b)
                  .parent()
                  .parent()
                  .find("input[type=radio]")
                  .each(function (a, c) {
                    $(c)[0] != $(b)[0]
                      ? $(c).prop("checked", !1)
                      : $(c).prop("checked", !0);
                  })
              : "checkbox" == $(b).attr("type") &&
                $(b)
                  .parent()
                  .parent()
                  .find("input[type=checkbox]")
                  .each(function (c, d) {
                    $(d)[0] == $(b)[0] &&
                      "TD" == a.target.nodeName &&
                      $(d).prop("checked", !$(d).prop("checked"));
                  });
          });
      });
    }

    setTimeout(
      function () {
        $("body").css("opacity", 1);
      },
      750,
      true
    );
  })
  .catch(function (script) {
    console.log(script + " failed to load");
  });
