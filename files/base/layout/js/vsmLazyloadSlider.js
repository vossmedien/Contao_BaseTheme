class VSMSliderMediaLoader {
    constructor() {
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupSwiperListeners());
        } else {
            this.setupSwiperListeners();
        }
    }

    setupSwiperListeners() {
        document.addEventListener('swiper:init', this.handleSwiperInit.bind(this));
        document.addEventListener('swiper:slideChange', this.handleSwiperSlideChange.bind(this));
    }

    handleSwiperInit(event) {
        const swiperEl = event.detail?.swiper?.el || document;
        this.loadActiveSlideMedia(swiperEl);
    }

    handleSwiperSlideChange(event) {
        const swiperEl = event.detail?.swiper?.el || document;
        this.loadActiveSlideMedia(swiperEl);
    }

    loadActiveSlideMedia(swiperEl) {
        const activeSlides = swiperEl.querySelectorAll('.swiper-slide-active, .swiper-slide-next');
        activeSlides.forEach(slide => {
            // Videos laden
            slide.querySelectorAll('video[data-src], video.lazy').forEach(video => {
                if (!video.classList.contains('loaded')) {
                    this.loadVideo(video);
                }
            });

            // Bilder laden
            slide.querySelectorAll('img[data-src], img[data-srcset]').forEach(img => {
                if (!img.classList.contains('loaded')) {
                    this.loadImage(img);
                }
            });
        });
    }

    loadVideo(video) {
        if (window.VSM.lazyLoader) {
            return window.VSM.lazyLoader.loadVideo(video);
        }

        // Fallback wenn kein LazyLoader verfügbar
        if (video.dataset.src) {
            video.src = video.dataset.src;
            video.removeAttribute('data-src');
        }

        video.querySelectorAll('source[data-src]').forEach(source => {
            source.src = source.dataset.src;
            source.removeAttribute('data-src');
        });

        video.load();
        video.classList.add('loaded');

        if (video.hasAttribute('autoplay')) {
            video.play().catch(console.warn);
        }
    }

    loadImage(img) {
        if (window.VSM.lazyLoader) {
            return window.VSM.lazyLoader.loadImage(img);
        }

        // Fallback wenn kein LazyLoader verfügbar
        if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset;
            img.removeAttribute('data-srcset');
        }
        if (img.dataset.src) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        }
        img.classList.add('loaded');
    }
}

// Initialization
window.VSM = window.VSM || {};
if (!window.VSM.sliderMediaLoader) {
    window.VSM.sliderMediaLoader = new VSMSliderMediaLoader();
}