function isOnScreen(elem) {
  if (!elem) return false;
  var viewport_top = window.scrollY;
  var viewport_height = window.innerHeight;
  var viewport_bottom = viewport_top + viewport_height;
  var top = elem.getBoundingClientRect().top + viewport_top;
  var height = elem.offsetHeight;
  var bottom = top + height;

  return (
    (top >= viewport_top && top < viewport_bottom) ||
    (bottom > viewport_top && bottom <= viewport_bottom) ||
    (height > viewport_height &&
      top <= viewport_top &&
      bottom >= viewport_bottom)
  );
}

function animate(elem, style, unit, from, to, time, prop) {
  if (!elem) return;
  var start = new Date().getTime(),
    timer = setInterval(function () {
      var step = Math.min(1, (new Date().getTime() - start) / time);
      if (prop) {
        elem[style] = from + step * (to - from) + unit;
      } else {
        elem.style[style] = from + step * (to - from) + unit;
      }
      if (step === 1) {
        clearInterval(timer);
      }
    }, 25);
  if (prop) {
    elem[style] = from + unit;
  } else {
    elem.style[style] = from + unit;
  }
}

function getURLParameter(name) {
  name = name.replace(/[\[\]]/g, "\\$&");
  var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)");
  var results = regex.exec(window.location.href);
  if (!results) return null;
  if (!results[2]) return "";
  return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function setSwitchingcardsHeight() {
    setTimeout(function () {
        document.querySelectorAll(".ce_rsce_switchingcards").forEach(function (switchingCard) {
            var maxFrontHeight = 0;
            var maxBackHeight = 0;

            // Finde die maximale Höhe für Front und Back in diesem Container
            switchingCard.querySelectorAll(".flipping-card--wrapper").forEach(function (wrapper) {
                var frontHeight = wrapper.querySelector(".flipping-card--front .front--inner").offsetHeight;
                var backHeight = wrapper.querySelector(".flipping-card--back .back--inner").offsetHeight;

                if (frontHeight > maxFrontHeight) {
                    maxFrontHeight = frontHeight;
                }
                if (backHeight > maxBackHeight) {
                    maxBackHeight = backHeight;
                }
            });

            var maxHeight = Math.max(maxFrontHeight, maxBackHeight);

            // Setze die maximale Höhe für alle Front- und Back-Elemente in diesem Container
            switchingCard.querySelectorAll(".flipping-card--front, .flipping-card--back").forEach(function (card) {
                card.style.height = maxHeight + 'px';
            });
        });
    }, 500); // 500 Millisekunden Verzögerung
}

setSwitchingcardsHeight();


function onImageLoaded(element) {
  // Ihre Logik hier, z.B. die Anpassung der Höhe der Karten
  setSwitchingcardsHeight();
}
