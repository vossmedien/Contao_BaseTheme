function setupFunctions() {
    initFrames();
    InitBasefeatures()
}


function initFrames() {
    if (Cookies.get('cookie_iframes')) {
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
    } else {
        $('iframe[data-source]').each(function (index) {
            $(this).attr('src', 'iframe.php');
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
                    return 'iframe.php';
                }
            }
        });
    }
}

function InitBasefeatures() {
    if (Cookies.get('cookie_basefeatures')) {

    }
}


window.addEventListener('cookiebar_save', function (e) {
    setupFunctions()
}, false);


setupFunctions()