class VSMLazyLoader {
    constructor(options = {}) {
        this.options = {
            root: null,
            rootMargin: '100px 0px',
            threshold: 0.01,
            excludeSelectors: [], // Slider-spezifische Ausschlüsse entfernt
            spinnerEnabled: true,
            timeout: 5000,
            ...options
        };

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


            // Generischer Scroll-Event-Listener
            let scrollTimeout;
            window.addEventListener('scroll', () => {
                if (scrollTimeout) {
                    window.cancelAnimationFrame(scrollTimeout);
                }
                scrollTimeout = window.requestAnimationFrame(() => {
                    document.querySelectorAll('img[data-src], img.lazy').forEach(img => {
                        if (!img.classList.contains('loaded') && this.shouldHandleElement(img)) {
                            this.imageObserver.observe(img);
                        }
                    });
                });
            }, {passive: true});
        } else {
            this.loadAllMediaDirectly();
        }
    }


    shouldHandleElement(element) {
        return !this.options.excludeSelectors.some(selector =>
            element.matches(selector) || element.closest(selector)
        );
    }



setupScrollListeners() {
    document.querySelectorAll('.scroll-wrapper').forEach(scrollWrapper => {
        scrollWrapper.addEventListener('scroll', debounce(() => {
            scrollWrapper.querySelectorAll('img[data-src], img.lazy').forEach(img => {
                if (!img.classList.contains('loaded') && this.shouldHandleElement(img)) {
                    this.loadImage(img);
                }
            });
        }, 100), { passive: true });
    });
}



    setupMutationObserver() {
        this.mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && this.shouldHandleElement(node)) {
                            this.handleNewElement(node);
                            if (node.querySelectorAll) {
                                node.querySelectorAll('img[data-src], video.lazy source[data-src], video[data-src], .cms-html-video-container, .content-media')
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
        }
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

setupImageObserver() {
    // Standard Observer für normale Bilder
    this.imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && this.shouldHandleElement(entry.target)) {
                this.loadImage(entry.target);
                this.imageObserver.unobserve(entry.target);
            }
        });
    }, this.options);

    // Zusätzliche Observer für Scroll-Container
    document.querySelectorAll('.scroll-wrapper').forEach(scrollWrapper => {
        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.shouldHandleElement(entry.target)) {
                    this.loadImage(entry.target);
                    scrollObserver.unobserve(entry.target);
                }
            });
        }, {
            root: scrollWrapper,
            rootMargin: '50% 0px',
            threshold: 0
        });

        // Beobachte alle Bilder im Scroll-Container
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
                if (entry.isIntersecting && this.shouldHandleElement(entry.target)) {
                    this.loadVideo(entry.target);
                    this.videoObserver.unobserve(entry.target);
                }
            });
        }, this.options);
    }

observeElements() {
    // Für Bilder außerhalb von Scroll-Containern
    document.querySelectorAll('img[data-src], img.lazy').forEach(img => {
        if (!img.closest('.scroll-wrapper') && this.shouldHandleElement(img)) {
            if (this.options.spinnerEnabled && !img.nextElementSibling?.classList.contains('lazy-loader-spinner')) {
                const spinnerContainer = img.closest('picture') || img.parentNode;
                spinnerContainer.appendChild(this.createSpinner());
            }
            this.imageObserver.observe(img);
        }
    });

    // Videos wie gehabt behandeln
    document.querySelectorAll('video[data-src], video.lazy').forEach(video => {
        if (this.shouldHandleElement(video)) {
            if (this.options.spinnerEnabled && !video.closest('.content-media')?.querySelector('.lazy-loader-spinner')) {
                const container = video.closest('.content-media') || video.parentNode;
                this.addSpinner(container);
            }
            this.videoObserver.observe(video);
        }
    });
}

loadImage(img) {
    return new Promise((resolve, reject) => {
        // Wenn das Bild bereits geladen wird oder bereits geladen wurde, early return
        if (img.classList.contains('loaded')) {
            return resolve();
        }

        // Wenn das Bild bereits src/srcset hat aber noch loading ist,
        // direkt als geladen markieren
        if (img.src && img.srcset && img.classList.contains('loading')) {
            img.classList.remove('loading');
            img.classList.remove('lazy');
            img.classList.add('loaded');
            this.removeSpinner(img);
            return resolve();
        }

        // Neue Loading-Session starten
        img.classList.add('loading');

        // Sources in picture Element laden
        if (img.closest('picture')) {
            const sources = img.closest('picture').querySelectorAll('source[data-srcset]');
            sources.forEach(source => {
                if (source.dataset.srcset) {
                    source.srcset = source.dataset.srcset;
                    source.removeAttribute('data-srcset');
                }
            });
        }

        // Haupt-Bild laden
        if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset;
            img.removeAttribute('data-srcset');
        }
        if (img.dataset.src) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        }

        const handleLoad = () => {
            img.classList.remove('loading');
            img.classList.remove('lazy');
            img.classList.add('loaded');
            this.removeSpinner(img);
            resolve();
        };

        const handleError = (error) => {
            img.classList.remove('loading');
            img.classList.remove('lazy');
            console.error('Fehler beim Laden des Bildes:', error);
            this.removeSpinner(img);
            reject(error);
        };

        // Wenn das Bild bereits vollständig geladen ist
        if (img.complete) {
            handleLoad();
        } else {
            img.addEventListener('load', handleLoad, { once: true });
            img.addEventListener('error', handleError, { once: true });

            // Timeout als Fallback
            setTimeout(() => {
                if (img.classList.contains('loading')) {
                    handleLoad(); // Force completion after timeout
                }
            }, this.options.timeout);
        }
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

        video.querySelectorAll('source[data-src]').forEach(source => {
            source.src = source.dataset.src;
            source.removeAttribute('data-src');
        });

        return new Promise((resolve, reject) => {
            const cleanup = () => {
                this.removeSpinner(video);
                this.loadingVideos.delete(video);
            };

            const success = () => {
                video.classList.remove('lazy');
                video.classList.add('loaded');
                cleanup();
                resolve();
            };

            const error = (e) => {
                console.error('Video load error:', e);
                cleanup();
                this.showVideoFallback(video);
                reject(e);
            };

            const successEvents = ['loadedmetadata', 'loadeddata', 'canplay'];
            successEvents.forEach(event => {
                video.addEventListener(event, success, {once: true});
            });

            video.addEventListener('error', error, {once: true});

            // Timeout-Handler
            setTimeout(() => {
                if (this.loadingVideos.has(video)) {
                    successEvents.forEach(event => {
                        video.removeEventListener(event, success);
                    });
                    video.removeEventListener('error', error);
                    cleanup();
                }
            }, this.options.timeout);

            video.load();

            if (video.hasAttribute('autoplay')) {
                video.play().catch(() => {
                    video.muted = true;
                    video.play().catch(console.warn);
                });
            }
        });
    }

    showVideoFallback(video) {
        this.removeSpinner(video);

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
            if (this.shouldHandleElement(img)) {
                this.loadImage(img);
            }
        });

        document.querySelectorAll('video[data-src], video.lazy').forEach(video => {
            if (this.shouldHandleElement(video)) {
                this.loadVideo(video);
            }
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
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}