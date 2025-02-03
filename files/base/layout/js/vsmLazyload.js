class LazyMediaLoader {
    constructor() {
        this.options = {
            root: null,
            rootMargin: '100px 0px',
            threshold: 0.01
        };

        this.isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        this.loadingVideos = new Map();
        this.imageObserver = null;
        this.videoObserver = null;
        this.mutationObserver = null;
        this.init();
    }

    init() {
        if (!document.body) {
            window.addEventListener('DOMContentLoaded', () => {
                this.init();
            });
            return;
        }

        if ('IntersectionObserver' in window) {
            this.setupImageObserver();
            this.setupVideoObserver();
            this.setupMutationObserver();
            this.observeElements();
            this.setupSwiperListeners();
        } else {
            this.loadAllMediaDirectly();
        }
    }

    setupSwiperListeners() {
        // Globaler Event-Listener für Swiper-Events
        document.addEventListener('swiper:slideChange', () => {
            this.handleSwiperSlideChange();
        });

        document.addEventListener('swiper:init', () => {
            this.handleSwiperInit();
        });
    }

    handleSwiperInit(swiperEl) {
        const activeSlides = swiperEl ?
            swiperEl.querySelectorAll('.swiper-slide-active, .swiper-slide-next') :
            document.querySelectorAll('.swiper-slide-active, .swiper-slide-next');

        activeSlides.forEach(slide => {
            const videos = slide.querySelectorAll('video[data-src], video.lazy');
            videos.forEach(video => {
                if (!video.classList.contains('loaded')) {
                    this.loadVideo(video);
                }
            });
        });
    }

    handleSwiperSlideChange(swiperEl) {
        const slidesToCheck = swiperEl ?
            swiperEl.querySelectorAll('.swiper-slide-active, .swiper-slide-next') :
            document.querySelectorAll('.swiper-slide-active, .swiper-slide-next');

        slidesToCheck.forEach(slide => {
            const videos = slide.querySelectorAll('video[data-src], video.lazy');
            videos.forEach(video => {
                if (!video.classList.contains('loaded')) {
                    this.loadVideo(video);
                }
            });
        });
    }

    setupMutationObserver() {
        // Sicherstellen, dass document.body existiert
        if (!document.body) {
            window.addEventListener('DOMContentLoaded', () => {
                this.setupMutationObserver();
            });
            return;
        }

        this.mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            this.handleNewElement(node);
                            if (node.querySelectorAll) {
                                node.querySelectorAll('img[data-src], video.lazy source[data-src], video[data-src], .cms-html-video-container, .content-media').forEach(element => {
                                    this.handleNewElement(element);
                                });
                            }
                        }
                    });
                }
            });
        });

        try {
            this.mutationObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        } catch (e) {
            console.warn('MutationObserver konnte nicht initialisiert werden:', e);
        }
    }

    handleNewElement(element) {
        // Wenn es ein Video in einem Swiper-Slide ist, überlassen wir das Laden dem Swiper-Handler
        if (element.closest('.swiper-slide')) {
            return;
        }

        const addSpinner = (target) => {
            const existingSpinners = target.querySelectorAll('.lazy-loader-spinner');
            existingSpinners.forEach(spinner => spinner.remove());
            target.appendChild(this.createSpinner());
        };

        if ((element.classList.contains('cms-html-video-container') ||
                element.classList.contains('content-media')) &&
            !element.querySelector('.lazy-loader-spinner')) {
            addSpinner(element);
        }

        if (element.tagName.toLowerCase() === 'video' ||
            (element.tagName.toLowerCase() === 'source' && element.parentElement.tagName.toLowerCase() === 'video')) {
            const videoElement = element.tagName.toLowerCase() === 'source' ? element.parentElement : element;

            // Füge Spinner nur hinzu, wenn es kein Swiper-Video ist
            if (!videoElement.closest('.swiper-slide')) {
                if ((videoElement.classList.contains('lazy') || videoElement.hasAttribute('data-src'))) {
                    const container = videoElement.closest('.content-media') || videoElement.parentNode;
                    if (!container.querySelector('.lazy-loader-spinner')) {
                        addSpinner(container);
                    }
                }
                this.loadVideo(videoElement);
            }
        }
    }

    createSpinner() {
        const spinner = document.createElement('div');
        spinner.className = 'lazy-loader-spinner';
        return spinner;
    }

    setupImageObserver() {
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, this.options);
    }

    setupVideoObserver() {
        this.videoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting || entry.target.closest('.swiper-slide-active, .swiper-slide-next')) {
                    this.loadVideo(entry.target);
                    this.videoObserver.unobserve(entry.target);
                }
            });
        }, this.options);
    }

    observeElements() {
        document.querySelectorAll('.cms-html-video-container:not(:has(.lazy-loader-spinner)), .content-media:not(:has(.lazy-loader-spinner))').forEach(element => {
            element.appendChild(this.createSpinner());
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            if (!img.nextElementSibling?.classList.contains('lazy-loader-spinner')) {
                img.parentNode.insertBefore(this.createSpinner(), img.nextSibling);
            }
            this.imageObserver.observe(img);
        });

        document.querySelectorAll('video[data-src], video.lazy').forEach(video => {
            const isInActiveSlide = video.closest('.swiper-slide-active, .swiper-slide-next');
            if (!video.closest('.content-media')?.querySelector('.lazy-loader-spinner')) {
                const container = video.closest('.content-media') || video.parentNode;
                container.appendChild(this.createSpinner());
            }

            if (isInActiveSlide) {
                this.loadVideo(video);
            } else {
                this.videoObserver.observe(video);
            }
        });
    }

    loadImage(img) {
        return new Promise((resolve, reject) => {
            const src = img.dataset.src;
            const srcset = img.dataset.srcset;

            if (!src && !srcset) {
                reject(new Error('No source found'));
                return;
            }

            if (src) {
                img.src = src;
                img.removeAttribute('data-src');
            }

            if (srcset) {
                img.srcset = srcset;
                img.removeAttribute('data-srcset');
            }

            img.onload = () => {
                img.classList.add('loaded');
                const nextSibling = img.nextElementSibling;
                if (nextSibling?.classList.contains('lazy-loader-spinner')) {
                    nextSibling.remove();
                }
                resolve();
            };

            img.onerror = (error) => {
                reject(error);
            };
        });
    }

    loadVideo(video) {
        if (this.loadingVideos.has(video)) return;

        const videoId = Math.random().toString(36).substr(2, 9);
        this.loadingVideos.set(video, {
            id: videoId,
            startTime: Date.now()
        });

        video.setAttribute('playsinline', '');
        video.muted = true;

        if (video.dataset.src) {
            video.src = video.dataset.src;
            video.removeAttribute('data-src');
        }

        const sources = video.querySelectorAll('source[data-src]');
        sources.forEach(source => {
            source.src = source.dataset.src;
            source.removeAttribute('data-src');
        });

        const loadPromise = new Promise((resolve, reject) => {
            const removeSpinner = () => {
                const spinners = [
                    video.closest('.content-media')?.querySelector('.lazy-loader-spinner'),
                    video.parentElement?.querySelector('.lazy-loader-spinner'),
                    video.querySelector('.lazy-loader-spinner'),
                    video.nextElementSibling?.classList.contains('lazy-loader-spinner') ? video.nextElementSibling : null
                ].filter(Boolean);

                spinners.forEach(spinner => {
                    if (spinner && spinner.parentElement) {
                        spinner.parentElement.removeChild(spinner);
                    }
                });
            };

            const success = () => {
                video.classList.add('loaded');
                removeSpinner();
                this.loadingVideos.delete(video);
                resolve();
            };

            const error = (e) => {
                console.error('Video load error:', e);
                removeSpinner();
                this.loadingVideos.delete(video);
                this.showVideoFallback(video);
                reject(e);
            };

            const successEvents = ['loadedmetadata', 'loadeddata', 'canplay'];
            successEvents.forEach(event => {
                video.addEventListener(event, success, {once: true});
            });

            video.addEventListener('error', error, {once: true});

            setTimeout(() => {
                if (this.loadingVideos.has(video)) {
                    successEvents.forEach(event => {
                        video.removeEventListener(event, success);
                    });
                    video.removeEventListener('error', error);
                    this.loadingVideos.delete(video);
                    removeSpinner();
                }
            }, 5000);
        });

        video.load();

        if (video.hasAttribute('autoplay')) {
            loadPromise.then(() => {
                video.play().catch(() => {
                    video.muted = true;
                    video.play().catch(e => console.warn('Muted autoplay failed:', e));
                });
            });
        }

        return loadPromise;
    }

    showVideoFallback(video) {
        const spinners = [
            video.closest('.content-media')?.querySelector('.lazy-loader-spinner'),
            video.parentElement?.querySelector('.lazy-loader-spinner'),
            video.querySelector('.lazy-loader-spinner'),
            video.nextElementSibling?.classList.contains('lazy-loader-spinner') ? video.nextElementSibling : null
        ].filter(Boolean);

        spinners.forEach(spinner => {
            if (spinner && spinner.parentElement) {
                spinner.parentElement.removeChild(spinner);
            }
        });

        const container = video.closest('.content-media') || video.parentNode;
        const existingFallback = container.querySelector('.video-fallback');
        if (existingFallback) {
            existingFallback.remove();
        }

        const fallback = document.createElement('div');
        fallback.className = 'video-fallback';
        fallback.innerHTML = `
            <div class="video-error-message">
                <p>Video konnte nicht geladen werden</p>
                <button class="retry-video-btn">Video erneut laden</button>
                <button class="reload-page-btn">Seite neu laden</button>
            </div>
        `;

        fallback.querySelector('.retry-video-btn').addEventListener('click', (e) => {
            e.preventDefault();
            fallback.remove();
            this.loadVideo(video);
        });

        fallback.querySelector('.reload-page-btn').addEventListener('click', () => {
            location.reload();
        });

        container.appendChild(fallback);
    }

    loadAllMediaDirectly() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.loadImage(img);
        });
        document.querySelectorAll('video[data-src], video.lazy').forEach(video => {
            this.loadVideo(video);
        });
    }

    destroy() {
        this.loadingVideos.clear();

        if (this.imageObserver) {
            this.imageObserver.disconnect();
        }
        if (this.videoObserver) {
            this.videoObserver.disconnect();
        }
        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
        }
    }
}

// Namespace und Initialisierung
window.VSM = window.VSM || {};
window.VSM.lazyMediaLoader = new LazyMediaLoader();

// Für Abwärtskompatibilität
window.VSM.lazyLoadInstance = {
    update: function () {
        // Leere Methode für Kompatibilität
    }
};

// Event-Listener für dynamisch nachgeladene Videos
document.addEventListener('vsm:videoLoaded', function (e) {
    if (window.VSM.lazyMediaLoader) {
        window.VSM.lazyMediaLoader.handleNewElement(e.detail.videoElement);
    }
});