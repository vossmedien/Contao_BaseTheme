class VSMSliderMediaLoader {
    constructor() {
        this.activeSliders = new Map();
        this.preloadDistance = 2; // Anzahl der Slides, die vorgeladen werden sollen
        this.loadingPromises = new Map();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        this.setupEventListeners();
        this.handleExistingSliders();
    }

    setupEventListeners() {
        // Swiper Events
        document.addEventListener('swiper:init', this.handleSwiperEvent.bind(this));
        document.addEventListener('swiper:slideChange', this.handleSwiperEvent.bind(this));
        document.addEventListener('swiper:destroy', this.handleSwiperDestroy.bind(this));

        // Mutation Observer für dynamisch hinzugefügte Slider
        this.setupMutationObserver();
    }

    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.classList?.contains('swiper')) {
                        this.handleNewSlider(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: false
        });
    }

    handleExistingSliders() {
        document.querySelectorAll('.swiper').forEach(slider => {
            this.handleNewSlider(slider);
        });
    }

    handleNewSlider(sliderElement) {
        if (!this.activeSliders.has(sliderElement)) {
            this.activeSliders.set(sliderElement, {
                element: sliderElement,
                loadedSlides: new Set()
            });
            this.preloadInitialSlides(sliderElement);
        }
    }

    handleSwiperEvent(event) {
        const swiperInstance = event.detail?.swiper;
        if (!swiperInstance?.el) return;

        const sliderElement = swiperInstance.el;

        // Slider-Daten aktualisieren oder erstellen
        if (!this.activeSliders.has(sliderElement)) {
            this.handleNewSlider(sliderElement);
        }

        this.loadVisibleAndUpcomingSlides(swiperInstance);
    }

    handleSwiperDestroy(event) {
        const sliderElement = event.detail?.swiper?.el;
        if (sliderElement) {
            this.activeSliders.delete(sliderElement);
            this.loadingPromises.delete(sliderElement);
        }
    }

    async loadVisibleAndUpcomingSlides(swiperInstance) {
        const sliderData = this.activeSliders.get(swiperInstance.el);
        if (!sliderData) return;

        const currentIndex = swiperInstance.activeIndex;
        const slidesToLoad = new Set();

        // Bestimme zu ladende Slides
        for (let i = -1; i <= this.preloadDistance; i++) {
            const slideIndex = currentIndex + i;
            const slide = swiperInstance.slides[slideIndex];
            if (slide && !sliderData.loadedSlides.has(slide)) {
                slidesToLoad.add(slide);
            }
        }

        // Lade alle ermittelten Slides parallel
        const loadingPromises = Array.from(slidesToLoad).map(slide =>
            this.loadSlideMedia(slide, sliderData));

        try {
            await Promise.all(loadingPromises);
        } catch (error) {
            console.warn('Fehler beim Laden der Slider-Medien:', error);
        }
    }

    async loadSlideMedia(slide, sliderData) {
        if (sliderData.loadedSlides.has(slide)) return;

        const mediaElements = slide.querySelectorAll('img[data-src],  video[data-src]');
        const loadingPromises = [];

        mediaElements.forEach(media => {
            if (!media.classList.contains('loaded')) {
                const loadPromise = this.loadMediaElement(media);
                loadingPromises.push(loadPromise);
            }
        });

        try {
            await Promise.all(loadingPromises);
            sliderData.loadedSlides.add(slide);
        } catch (error) {
            console.warn('Fehler beim Laden der Medien für Slide:', error);
        }
    }

    async loadMediaElement(media) {
        if (!window.VSM.lazyLoader) return;

        try {
            if (media.tagName.toLowerCase() === 'video') {
                await window.VSM.lazyLoader.loadVideo(media);
            } else {
                await window.VSM.lazyLoader.loadImage(media);
            }
        } catch (error) {
            console.warn('Fehler beim Laden des Medienelements:', error);
        }
    }

    preloadInitialSlides(sliderElement) {
        const swiperInstance = sliderElement.swiper;
        if (swiperInstance) {
            this.loadVisibleAndUpcomingSlides(swiperInstance);
        }
    }

    destroy() {
        this.activeSliders.clear();
        this.loadingPromises.clear();
    }
}

// Initialization
window.VSM = window.VSM || {};
if (!window.VSM.sliderMediaLoader) {
    window.VSM.sliderMediaLoader = new VSMSliderMediaLoader();
}