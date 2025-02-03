class VSMSliderMediaLoader {
    constructor() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        document.addEventListener('swiper:init', this.handleSwiperEvent.bind(this));
        document.addEventListener('swiper:slideChange', this.handleSwiperEvent.bind(this));
    }

    handleSwiperEvent(event) {
        const swiperEl = event.detail?.swiper?.el;
        if (!swiperEl) return;

        // Aktive und nächste Slides vorausladen
        const slidesToLoad = swiperEl.querySelectorAll('.swiper-slide-active, .swiper-slide-next');

        slidesToLoad.forEach(slide => {
            if (window.VSM.lazyLoader) {
                // Alle Medien im Slide zum Laden markieren
                slide.querySelectorAll('img.lazy, img[data-src], video.lazy, video[data-src]')
                    .forEach(media => {
                        if (!media.classList.contains('loaded')) {
                            if (media.tagName.toLowerCase() === 'img') {
                                window.VSM.lazyLoader.loadImage(media);
                            } else {
                                window.VSM.lazyLoader.loadVideo(media);
                            }
                        }
                    });
            }
        });
    }
}

// Initialization
window.VSM = window.VSM || {};
if (!window.VSM.sliderMediaLoader) {
    window.VSM.sliderMediaLoader = new VSMSliderMediaLoader();
}