class VSMLazyLoader {
    constructor(options = {}) {
        this.options = {
            root: null,
            rootMargin: '100px 0px',
            threshold: 0.01,
            excludeSelectors: [],
            spinnerEnabled: true,
            timeout: 8000, // Erhöht für bessere Mobile-Performance
            ...options
        };

        this.loadedElements = new Set();
        this.loadingVideos = new Map();
        this.imageObserver = null;
        this.videoObserver = null;
        this.mutationObserver = null;
        this.init();
    }

    init() {
        if (!document.body) {
            window.addEventListener('DOMContentLoaded', () => this.init());
            return;
        }

        if ('IntersectionObserver' in window) {
            this.setupImageObserver();
            this.setupVideoObserver();
            this.setupMutationObserver();
            this.setupScrollListeners();
            this.observeElements();

            // Periodische Überprüfung für verlorene Elemente
            setInterval(() => this.checkLostElements(), 10000);
        } else {
            this.loadAllMediaDirectly();
        }
    }

    setupImageObserver() {
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loadedElements.has(entry.target) &&
                    this.shouldHandleElement(entry.target)) {
                    this.loadImage(entry.target);
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, this.options);

        // Scroll-Container spezifische Observer
        document.querySelectorAll('.scroll-wrapper').forEach(scrollWrapper => {
            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.loadedElements.has(entry.target) &&
                        this.shouldHandleElement(entry.target)) {
                        this.loadImage(entry.target);
                        scrollObserver.unobserve(entry.target);
                    }
                });
            }, {
                root: scrollWrapper,
                rootMargin: '50% 0px',
                threshold: 0
            });

            scrollWrapper.querySelectorAll('img[data-src], img.lazy').forEach(img => {
                if (!img.classList.contains('loaded')) {
                    scrollObserver.observe(img);
                }
            });
        });
    }

    setupVideoObserver() {
        this.videoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loadedElements.has(entry.target) &&
                    this.shouldHandleElement(entry.target)) {
                    this.loadVideo(entry.target);
                    this.videoObserver.unobserve(entry.target);
                }
            });
        }, this.options);
    }

    setupScrollListeners() {
        let scrollTimeout;
        const scrollHandler = () => {
            if (scrollTimeout) {
                window.cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = window.requestAnimationFrame(() => {
                this.checkVisibleElements();
            });
        };

        window.addEventListener('scroll', scrollHandler, {passive: true});

        document.querySelectorAll('.scroll-wrapper').forEach(scrollWrapper => {
            scrollWrapper.addEventListener('scroll', debounce(() => {
                this.checkVisibleElements(scrollWrapper);
            }, 100), {passive: true});
        });
    }

    setupMutationObserver() {
        this.mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && this.shouldHandleElement(node)) {
                            this.handleNewElement(node);
                            if (node.querySelectorAll) {
                                node.querySelectorAll('img[data-src], video source[data-src], video[data-src]')
                                    .forEach(element => {
                                        if (this.shouldHandleElement(element)) {
                                            this.handleNewElement(element);
                                        }
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
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        } catch (e) {
            console.warn('MutationObserver konnte nicht initialisiert werden:', e);
        }
    }

    checkVisibleElements(container = document) {
        const elements = container.querySelectorAll('img[data-src], video[data-src]');
        elements.forEach(element => {
            if (!this.loadedElements.has(element) && this.isInViewport(element)) {
                if (element.tagName.toLowerCase() === 'video') {
                    this.loadVideo(element);
                } else {
                    this.loadImage(element);
                }
            }
        });
    }

    checkLostElements() {
        document.querySelectorAll('img.lazy:not(.loaded), video.lazy:not(.loaded)').forEach(element => {
            if (!this.loadedElements.has(element) && this.isInViewport(element)) {
                if (element.tagName.toLowerCase() === 'video') {
                    this.loadVideo(element);
                } else {
                    this.loadImage(element);
                }
            }
        });
    }

    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= -rect.height &&
            rect.left >= -rect.width &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) + rect.height &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth) + rect.width
        );
    }

    handleNewElement(element) {
        if (!this.shouldHandleElement(element)) return;

        if (this.options.spinnerEnabled) {
            this.handleSpinner(element);
        }

        if (element.tagName.toLowerCase() === 'video' ||
            (element.tagName.toLowerCase() === 'source' && element.parentElement.tagName.toLowerCase() === 'video')) {
            const videoElement = element.tagName.toLowerCase() === 'source' ? element.parentElement : element;
            if (videoElement.classList.contains('lazy') || videoElement.hasAttribute('data-src')) {
                this.loadVideo(videoElement);
            }
        } else if (element.tagName.toLowerCase() === 'img') {
            if (element.classList.contains('lazy') || element.hasAttribute('data-src')) {
                this.loadImage(element);
            }
        }
    }

    shouldHandleElement(element) {
        return !this.options.excludeSelectors.some(selector =>
            element.matches(selector) || element.closest(selector)
        );
    }

    createSpinner() {
        const spinner = document.createElement('div');
        spinner.className = 'lazy-loader-spinner';
        return spinner;
    }

    handleSpinner(element) {
        if ((element.classList.contains('cms-html-video-container') ||
                element.classList.contains('content-media')) &&
            !element.querySelector('.lazy-loader-spinner')) {
            this.addSpinner(element);
        }
    }

    addSpinner(target) {
        if (!this.options.spinnerEnabled) return;
        const existingSpinners = target.querySelectorAll('.lazy-loader-spinner');
        existingSpinners.forEach(spinner => spinner.remove());
        target.appendChild(this.createSpinner());
    }

    removeSpinner(element) {
        const spinners = [
            element.closest('.content-media')?.querySelector('.lazy-loader-spinner'),
            element.parentElement?.querySelector('.lazy-loader-spinner'),
            element.querySelector('.lazy-loader-spinner'),
            element.nextElementSibling?.classList.contains('lazy-loader-spinner') ?
                element.nextElementSibling : null
        ].filter(Boolean);

        spinners.forEach(spinner => {
            if (spinner && spinner.parentElement) {
                spinner.parentElement.removeChild(spinner);
            }
        });
    }

    loadImage(img) {
        if (this.loadedElements.has(img)) return Promise.resolve();

        return new Promise((resolve, reject) => {
            const handleLoad = () => {
                img.classList.remove('lazy', 'loading');
                img.classList.add('loaded');
                this.loadedElements.add(img);
                this.removeSpinner(img);
                resolve();
            };

            const handleError = (error) => {
                console.error('Fehler beim Laden des Bildes:', error);
                this.removeSpinner(img);
                reject(error);
            };

            img.classList.add('loading');

            if (img.closest('picture')) {
                const sources = img.closest('picture').querySelectorAll('source[data-srcset]');
                sources.forEach(source => {
                    if (source.dataset.srcset) {
                        source.srcset = source.dataset.srcset;
                        source.removeAttribute('data-srcset');
                    }
                });
            }

            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
                img.removeAttribute('data-srcset');
            }
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }

            if (img.complete) {
                handleLoad();
            } else {
                img.addEventListener('load', handleLoad, {once: true});
                img.addEventListener('error', handleError, {once: true});

                setTimeout(() => {
                    if (img.classList.contains('loading')) {
                        handleLoad();
                    }
                }, this.options.timeout);
            }
        });
    }

    loadVideo(video) {
        if (this.loadedElements.has(video) || this.loadingVideos.has(video)) return Promise.resolve();

        const videoId = Math.random().toString(36).substr(2, 9);
        this.loadingVideos.set(video, {id: videoId, startTime: Date.now()});

        return new Promise((resolve, reject) => {
            video.setAttribute('playsinline', '');
            video.muted = true;

            video.querySelectorAll('source[data-src]').forEach(source => {
                source.src = source.dataset.src;
                source.removeAttribute('data-src');
            });

            if (video.dataset.src) {
                video.src = video.dataset.src;
                video.removeAttribute('data-src');
            }

            const cleanup = () => {
                this.removeSpinner(video);
                this.loadingVideos.delete(video);
            };

            const success = () => {
                video.classList.remove('lazy');
                video.classList.add('loaded');
                this.loadedElements.add(video);
                cleanup();
                this.videoObserver?.unobserve(video);

                if (video.hasAttribute('autoplay')) {
                    video.play().catch(() => {
                        video.muted = true;
                        video.play().catch(console.warn);
                    });
                }
                resolve();
            };

            const error = (e) => {
                console.error('Video load error:', e);
                cleanup();
                this.showVideoFallback(video);
                reject(e);
            };

            video.addEventListener('loadeddata', success, {once: true});
            video.addEventListener('error', error, {once: true});

            setTimeout(() => {
                if (this.loadingVideos.has(video)) {
                    cleanup();
                }
            }, this.options.timeout);

            video.load();
        });
    }

    showVideoFallback(video) {
        this.removeSpinner(video);
        const container = video.closest('.content-media') || video.parentNode;

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

    observeElements() {
        document.querySelectorAll('img[data-src], video[data-src]').forEach(element => {
            if (!this.loadedElements.has(element) && this.shouldHandleElement(element)) {
                if (this.options.spinnerEnabled) {
                    this.handleSpinner(element);
                }
                if (element.tagName.toLowerCase() === 'video') {
                    this.videoObserver.observe(element);
                } else {
                    this.imageObserver.observe(element);
                }
            }
        });
    }

    loadAllMediaDirectly() {
        document.querySelectorAll('img[data-src], video[data-src]').forEach(element => {
            if (this.shouldHandleElement(element)) {
                if (element.tagName.toLowerCase() === 'video') {
                    this.loadVideo(element);
                } else {
                    this.loadImage(element);
                }
            }
        });
    }

    destroy() {
        this.loadedElements.clear();
        this.loadingVideos.clear();
        if (this.imageObserver) this.imageObserver.disconnect();
        if (this.videoObserver) this.videoObserver.disconnect();
        if (this.mutationObserver) this.mutationObserver.disconnect();
    }
}

// Initialisierung
window.VSM = window.VSM || {};
window.VSM.lazyLoader = new VSMLazyLoader();

// Abwärtskompatibilität
window.VSM.lazyLoadInstance = {
    update: () => {
    }
};

// Event-Listener für dynamisch nachgeladene Videos
document.addEventListener('vsm:videoLoaded', function (e) {
    if (window.VSM.lazyLoader && e.detail.videoElement) {
        window.VSM.lazyLoader.handleNewElement(e.detail.videoElement);
    }
});

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}