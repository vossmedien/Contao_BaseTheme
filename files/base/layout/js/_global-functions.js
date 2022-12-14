function isOnScreen(elem) {
    // if the element doesn't exist, abort
    if (elem.length == 0) {
        return;
    }
    var $window = jQuery(window);
    var viewport_top = $window.scrollTop();
    var viewport_height = $window.height();
    var viewport_bottom = viewport_top + viewport_height;
    var $elem = jQuery(elem);
    var top = $elem.offset().top;
    var height = $elem.height();
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
    if (!elem) {
        return;
    }
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