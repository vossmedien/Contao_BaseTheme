class VSMSliderMediaLoader {
    constructor() {
        this.activeSliders = new Map();
        this.preloadDistance = 1; // Reduziert auf 1, da wir gezielter laden
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
            return;
        }

        // Direkte Integration mit Swiper
        if (window.Swiper) {
            this.extendSwiper();
        } else {
            // Warten auf Swiper
            document.addEventListener('swiper:loaded', () => this.extendSwiper());
        }

        this.setupMutationObserver();
        this.handleExistingSliders();
    }

    handleExistingSliders() {
        // Alle existierenden Swiper auf der Seite finden und initialisieren
        document.querySelectorAll('.swiper').forEach(sliderElement => {
            this.handleNewSlider(sliderElement);
        });
    }

    extendSwiper() {
        const originalInit = Swiper.prototype.init;
        Swiper.prototype.init = function(...args) {
            const result = originalInit.apply(this, args);

            // Event für neuen Slider auslösen
            const event = new CustomEvent('swiper:init', {
                detail: { swiper: this }
            });
            document.dispatchEvent(event);

            // Slide Change Event registrieren
            this.on('slideChange', () => {
                const event = new CustomEvent('swiper:slideChange', {
                    detail: { swiper: this }
                });
                document.dispatchEvent(event);
            });

            return result;
        };
    }

    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.classList?.contains('swiper')) {
                            this.handleNewSlider(node);
                        }
                        // Auch in Kind-Elementen suchen
                        const sliders = node.querySelectorAll?.('.swiper');
                        if (sliders) {
                            sliders.forEach(slider => this.handleNewSlider(slider));
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    handleNewSlider(sliderElement) {
        if (!this.activeSliders.has(sliderElement)) {
            this.activeSliders.set(sliderElement, {
                element: sliderElement,
                loadedSlides: new Set()
            });

            // Initiales Laden der ersten Slides
            if (sliderElement.swiper) {
                this.preloadInitialSlides(sliderElement.swiper);
            } else {
                // Warten auf Swiper-Initialisierung
                const observer = new MutationObserver((mutations, obs) => {
                    if (sliderElement.swiper) {
                        this.preloadInitialSlides(sliderElement.swiper);
                        obs.disconnect();
                    }
                });

                observer.observe(sliderElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        }
    }

    async preloadInitialSlides(swiperInstance) {
        if (!swiperInstance || !swiperInstance.slides) return;

        const activeIndex = swiperInstance.activeIndex;
        const nextIndex = (activeIndex + 1) % swiperInstance.slides.length;

        // Aktuelle Slide laden
        await this.loadSlideMedia(swiperInstance.slides[activeIndex]);

        // Nächste Slide vorladen
        await this.loadSlideMedia(swiperInstance.slides[nextIndex]);
    }

    async loadSlideMedia(slide) {
        if (!slide || !window.VSM.lazyLoader) return;

        const mediaElements = slide.querySelectorAll('img[data-src], video[data-src]');
        const loadingPromises = [];

        mediaElements.forEach(media => {
            if (!media.classList.contains('loaded')) {
                const promise = media.tagName.toLowerCase() === 'video'
                    ? window.VSM.lazyLoader.loadVideo(media)
                    : window.VSM.lazyLoader.loadImage(media);
                loadingPromises.push(promise);
            }
        });

        try {
            await Promise.all(loadingPromises);
        } catch (error) {
            console.warn('Fehler beim Laden der Medien:', error);
        }
    }

    // Hilfsmethode zum Aufräumen
    destroy() {
        this.activeSliders.clear();
    }
}

// Initialization
window.VSM = window.VSM || {};
window.VSM.sliderMediaLoader = new VSMSliderMediaLoader();