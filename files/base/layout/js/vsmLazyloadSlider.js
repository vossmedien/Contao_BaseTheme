class VSMSliderMediaLoader {
    constructor() {
        this.activeSliders = new Map();
        this.preloadDistance = 1; // Optimal für Performance
        this.isLoading = false; // Verhindere parallele Ladevorgänge
        this.loadQueue = new Set(); // Queue für wartende Slides
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
            return;
        }

        // Performance: Direkte Integration mit Swiper
        if (window.Swiper) {
            this.extendSwiper();
        } else {
            document.addEventListener('swiper:loaded', () => this.extendSwiper());
        }

        this.setupMutationObserver();
        this.handleExistingSliders();

        // LazyLoader-Integration optimiert
        if (window.VSM && window.VSM.lazyLoader) {
            this.lazyLoader = window.VSM.lazyLoader;
        } else {
            document.addEventListener('vsm:lazyLoaderInit', (e) => {
                this.lazyLoader = e.detail.lazyLoader;
            });
        }
    }

    handleExistingSliders() {
        // Performance: Batch-Verarbeitung existierender Slider
        const sliders = document.querySelectorAll('.swiper');
        if (sliders.length > 0) {
            requestAnimationFrame(() => {
                sliders.forEach(sliderElement => this.handleNewSlider(sliderElement));
            });
        }
    }

    extendSwiper() {
        const originalInit = Swiper.prototype.init;
        Swiper.prototype.init = function (...args) {
            const result = originalInit.apply(this, args);

            // Performance: Verzögerte Event-Auslösung
            requestAnimationFrame(() => {
                const event = new CustomEvent('swiper:init', {
                    detail: {swiper: this}
                });
                document.dispatchEvent(event);

                // Performance: Throttled slide change events
                let slideChangeTimeout;
                this.on('slideChange', () => {
                    clearTimeout(slideChangeTimeout);
                    slideChangeTimeout = setTimeout(() => {
                        const event = new CustomEvent('swiper:slideChange', {
                            detail: {swiper: this}
                        });
                        document.dispatchEvent(event);
                    }, 100); // Debounce für schnelle Swipes
                });
            });

            return result;
        };
    }

    setupMutationObserver() {
        // Performance: Weniger häufige Observer-Checks
        let observerTimeout;
        const observer = new MutationObserver((mutations) => {
            clearTimeout(observerTimeout);
            observerTimeout = setTimeout(() => {
                const slidersToProcess = new Set();
                
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            if (node.classList?.contains('swiper')) {
                                slidersToProcess.add(node);
                            }
                            const childSliders = node.querySelectorAll?.('.swiper');
                            if (childSliders) {
                                childSliders.forEach(slider => slidersToProcess.add(slider));
                            }
                        }
                    });
                });

                // Performance: Batch-Verarbeitung
                if (slidersToProcess.size > 0) {
                    requestAnimationFrame(() => {
                        slidersToProcess.forEach(slider => this.handleNewSlider(slider));
                    });
                }
            }, 200); // Debounce für bessere Performance
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    handleNewSlider(sliderElement) {
        if (this.activeSliders.has(sliderElement)) return;

        this.activeSliders.set(sliderElement, {
            element: sliderElement,
            loadedSlides: new Set(),
            lastActiveIndex: 0
        });

        // Performance: Intelligente Initialisierung
        if (sliderElement.swiper) {
            this.preloadInitialSlides(sliderElement.swiper);
        } else {
            // Performance: Effizienter Warte-Mechanismus
            let attempts = 0;
            const checkInterval = setInterval(() => {
                if (sliderElement.swiper || attempts > 20) { // Max 2 Sekunden warten
                    clearInterval(checkInterval);
                    if (sliderElement.swiper) {
                        this.preloadInitialSlides(sliderElement.swiper);
                    }
                }
                attempts++;
            }, 100);
        }
    }

    async preloadInitialSlides(swiperInstance) {
        if (!swiperInstance || !swiperInstance.slides || this.isLoading) return;

        this.isLoading = true;

        try {
            const activeIndex = swiperInstance.activeIndex;
            const totalSlides = swiperInstance.slides.length;
            
            // Performance: Priorisierte Slide-Auswahl
            const slidesToLoad = this.getSlidesToLoad(swiperInstance, activeIndex, totalSlides);

            // Performance: Sequentielle Ladung nach Priorität
            for (const slide of slidesToLoad) {
                await this.loadSlideMedia(slide);
                
                // Performance: Kurze Pause zwischen Slides für bessere UX
                await new Promise(resolve => setTimeout(resolve, 50));
            }

            // Performance: Event für erfolgreiche Initialisierung
            const event = new CustomEvent('swiper:slidesPreloaded', {
                detail: { swiper: swiperInstance, loadedCount: slidesToLoad.length }
            });
            document.dispatchEvent(event);

        } catch (error) {
            console.warn('Fehler beim Vorladen der Slides:', error);
        } finally {
            this.isLoading = false;
        }
    }

    getSlidesToLoad(swiperInstance, activeIndex, totalSlides) {
        const slidesToLoad = [];
        
        // 1. Priorität: Aktuelle Slide
        if (swiperInstance.slides[activeIndex]) {
            slidesToLoad.push(swiperInstance.slides[activeIndex]);
        }

        // 2. Priorität: Nächste Slide
        const nextIndex = (activeIndex + 1) % totalSlides;
        if (swiperInstance.slides[nextIndex] && nextIndex !== activeIndex) {
            slidesToLoad.push(swiperInstance.slides[nextIndex]);
        }

        // 3. Priorität: Vorherige Slide (nur wenn Loop oder nicht erste Slide)
        if (swiperInstance.params.loop || activeIndex > 0) {
            const prevIndex = activeIndex > 0 ? activeIndex - 1 : totalSlides - 1;
            if (swiperInstance.slides[prevIndex] && prevIndex !== activeIndex && prevIndex !== nextIndex) {
                slidesToLoad.push(swiperInstance.slides[prevIndex]);
            }
        }

        return slidesToLoad;
    }

    async loadSlideMedia(slide) {
        if (!slide || !window.VSM?.lazyLoader) return;

        // Performance: Prüfe ob Slide bereits geladen
        const slideId = slide.dataset.slideId || slide.getAttribute('data-swiper-slide-index') || 
                        Array.from(slide.parentNode.children).indexOf(slide);
        
        if (this.loadQueue.has(slideId)) return;
        this.loadQueue.add(slideId);

        try {
            // Performance: Priorisiere Videos vor Bildern
            const videos = slide.querySelectorAll('video[data-src], video.lazy');
            const images = slide.querySelectorAll('img[data-src]');
            
            const loadingPromises = [];

            // Erst Videos laden (meist wichtiger für UX)
            videos.forEach(video => {
                if (!video.classList.contains('loaded')) {
                    loadingPromises.push(
                        window.VSM.lazyLoader.loadVideo(video).catch(err => {
                            console.warn('Video load failed:', err);
                            return null; // Fehler nicht weiterwerfen
                        })
                    );
                }
            });

            // Dann Bilder laden
            images.forEach(img => {
                if (!img.classList.contains('loaded')) {
                    loadingPromises.push(
                        window.VSM.lazyLoader.loadImage(img).catch(err => {
                            console.warn('Image load failed:', err);
                            return null; // Fehler nicht weiterwerfen
                        })
                    );
                }
            });

            // Performance: Warte auf alle Medien mit Timeout
            if (loadingPromises.length > 0) {
                await Promise.race([
                    Promise.allSettled(loadingPromises),
                    new Promise(resolve => setTimeout(resolve, 3000)) // 3s Timeout
                ]);
            }

        } catch (error) {
            console.warn('Fehler beim Laden der Slide-Medien:', error);
        } finally {
            this.loadQueue.delete(slideId);
        }
    }

    // Performance: Optimierte Aufräumung
    destroy() {
        this.activeSliders.clear();
        this.loadQueue.clear();
        this.isLoading = false;

        // Event-Handler-Referenzen freigeben
        if (this.scrollHandler) {
            window.removeEventListener('scroll', this.scrollHandler);
            this.scrollHandler = null;
        }
        
        if (this.debouncedCheck) {
            document.querySelectorAll('.scroll-wrapper').forEach(wrapper => {
                wrapper.removeEventListener('scroll', this.debouncedCheck);
            });
            this.debouncedCheck = null;
        }
    }

    // Performance: Lazy-Loading für Slides außerhalb des Viewports
    loadSlideOnDemand(swiper, slideIndex) {
        if (!swiper || !swiper.slides || this.isLoading) return;

        const slide = swiper.slides[slideIndex];
        if (slide) {
            requestAnimationFrame(() => {
                this.loadSlideMedia(slide);
            });
        }
    }
}

// Performance: Singleton-Pattern für bessere Speicherverwaltung
window.VSM = window.VSM || {};
if (!window.VSM.sliderMediaLoader) {
    window.VSM.sliderMediaLoader = new VSMSliderMediaLoader();
}

// Performance: Event-Listener für Swiper-Events
document.addEventListener('swiper:slideChange', function(e) {
    const swiper = e.detail.swiper;
    if (swiper && window.VSM.sliderMediaLoader) {
        // Performance: Debounced loading für schnelle Swipes
        clearTimeout(window.VSM.sliderMediaLoader.slideChangeTimeout);
        window.VSM.sliderMediaLoader.slideChangeTimeout = setTimeout(() => {
            window.VSM.sliderMediaLoader.preloadInitialSlides(swiper);
        }, 200);
    }
});

// Performance: Cleanup bei Page-Unload
window.addEventListener('beforeunload', () => {
    if (window.VSM.sliderMediaLoader) {
        window.VSM.sliderMediaLoader.destroy();
    }
});