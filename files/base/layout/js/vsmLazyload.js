class VSMLazyLoader {
    constructor(options = {}) {
        this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        this.options = {
            root: null,
            rootMargin: this.isMobile ? '50px 0px' : '250px 0px',
            threshold: 0.01,
            excludeSelectors: [],
            spinnerEnabled: true,
            timeout: 8000,
            maxSimultaneousLoads: this.isMobile ? 3 : 6,
            ...options
        };

        this.loadedElements = new Set();
        this.loadingVideos = new Map();
        this.imageObserver = null;
        this.videoObserver = null;
        this.mutationObserver = null;

        // Verhindere mehrfache Initialisierung
        if (window.VSM && window.VSM.lazyLoader) {
            console.warn('VSMLazyLoader bereits initialisiert!');
            return window.VSM.lazyLoader;
        }

        // Starte Initialisierung
        this._initialize();

        const event = new CustomEvent('vsm:lazyLoaderInit', {
            detail: {lazyLoader: this}
        });
        document.dispatchEvent(event);
    }

    _initialize() {
        // Initialisierung sofort versuchen
        if (document.readyState === 'loading') {
            // Dokument wird noch geladen, warten wir auf DOMContentLoaded
            window.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            // Dokument bereits geladen, sofort initialisieren
            this.init();
        }

        // Sicherheitscheck: Falls init() nicht richtig ausgeführt wurde
        // oder die Observer nicht initialisiert wurden, versuche es nach einer Verzögerung erneut
        setTimeout(() => {
            if (!this.videoObserver || !this.imageObserver) {
                console.warn('Observer nicht initialisiert, versuche erneut...');
                this.init();

                // Prüfe auf unbeobachtete Videos nach der Re-Initialisierung
                setTimeout(() => this.checkUnobservedVideos(), 500);
            }
        }, 1000);
    }

    init() {
        if (!document.body) {
            console.warn('Document body noch nicht bereit, warte...');
            window.addEventListener('DOMContentLoaded', () => this.init());
            return;
        }

        try {
            if ('IntersectionObserver' in window) {
                // Observers in definierter Reihenfolge initialisieren
                this.setupImageObserver();
                this.setupVideoObserver();
                this.setupMutationObserver();

                if (!this.isMobile) {
                    this.setupScrollListeners();
                }

                // Prüfe, ob die Observer initialisiert wurden
                if (!this.videoObserver || !this.imageObserver) {
                    console.error('Observer konnten nicht initialisiert werden');
                    return;
                }

                // Nun die DOM-Elemente beobachten
                this.observeElements();

                if (!this.isMobile) {
                    setInterval(() => this.checkLostElements(), 10000);
                }
            } else {
                console.warn('IntersectionObserver nicht verfügbar, verwende Fallback');
                this.loadAllMediaDirectly();
            }
        } catch (e) {
            console.error('Fehler bei der Initialisierung:', e);
        }
    }


    checkUnobservedVideos() {
        const videos = document.querySelectorAll('video.lazy');
        videos.forEach(video => {
            if (!this.loadedElements.has(video) &&
                !this.loadingVideos.has(video) &&
                !video.classList.contains('loading') &&
                !video.classList.contains('loaded')) {
                this.handleNewElement(video);
                if (this.isInViewport(video)) {
                    this.loadVideo(video);
                }
            }
        });
    }

    setupImageObserver() {
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loadedElements.has(entry.target) &&
                    this.shouldHandleElement(entry.target)) {
                    if (entry.target.hasAttribute('data-bg')) {
                        this.loadBackgroundImage(entry.target);
                    } else if (entry.target.hasAttribute('data-src')) {
                        this.loadImage(entry.target);
                    }
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: this.isMobile ? '50px 0px' : this.options.rootMargin,
            threshold: 0.01
        });

        document.querySelectorAll('.scroll-wrapper').forEach(scrollWrapper => {
            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.loadedElements.has(entry.target) &&
                        this.shouldHandleElement(entry.target)) {
                        if (entry.target.hasAttribute('data-bg')) {
                            this.loadBackgroundImage(entry.target);
                        } else if (entry.target.hasAttribute('data-src')) {
                            this.loadImage(entry.target);
                        }
                        scrollObserver.unobserve(entry.target);
                    }
                });
            }, {
                root: scrollWrapper,
                rootMargin: '50% 0px',
                threshold: 0
            });

            scrollWrapper.querySelectorAll('[data-src], [data-bg]').forEach(element => {
                if (!element.classList.contains('loaded')) {
                    scrollObserver.observe(element);
                }
            });
        });
    }

    setupVideoObserver() {
        this.videoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target;

                if (this.isMobile) {
                    if (entry.isIntersecting) {
                        if (!video.classList.contains('loaded')) {
                            this.loadVideo(video);
                        } else if (video.paused && video.hasAttribute('autoplay')) {
                            video.play().catch(console.warn);
                        }
                    } else {
                        this.unloadVideo(video);
                    }
                } else if (entry.isIntersecting && !this.loadedElements.has(video) &&
                    this.shouldHandleElement(video)) {
                    this.loadVideo(video);
                    this.videoObserver.unobserve(video);
                }
            });
        }, {
            root: null,
            rootMargin: this.isMobile ? '50px 0px' : this.options.rootMargin,
            threshold: 0.01
        });
    }

    unloadVideo(video) {
        if (!this.isMobile) return;

        video.pause();
        video.currentTime = 0;

        if (this.loadingVideos.has(video)) {
            this.loadingVideos.delete(video);
            this.removeSpinner(video);
        }

        this.loadedElements.delete(video);
        video.removeAttribute('src');
        video.load();

        video.querySelectorAll('source').forEach(source => {
            const currentSrc = source.getAttribute('src');
            if (currentSrc) {
                source.setAttribute('data-src', currentSrc);
                source.removeAttribute('src');
            }
        });

        const originalSrc = video.getAttribute('data-original-src');
        if (originalSrc) {
            video.setAttribute('data-src', originalSrc);
        }

        video.classList.remove('loading', 'loaded');
        video.classList.add('lazy');
    }

    setupScrollListeners() {
        if (this.isMobile) return;

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
                                node.querySelectorAll('[data-src], [data-bg], video.lazy').forEach(element => {
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
        if (this.isMobile) return;

        const elements = container.querySelectorAll('[data-src], [data-bg], video.lazy');
        elements.forEach(element => {
            if (!this.loadedElements.has(element) && this.isInViewport(element)) {
                if (element.hasAttribute('data-bg')) {
                    this.loadBackgroundImage(element);
                } else if (element.tagName.toLowerCase() === 'video') {
                    this.loadVideo(element);
                } else {
                    this.loadImage(element);
                }
            }
        });
    }

    checkLostElements() {
        if (this.isMobile) return;

        document.querySelectorAll('[data-src]:not(.loaded), [data-bg]:not(.loaded), video.lazy:not(.loaded)').forEach(element => {
            if (!this.loadedElements.has(element) && this.isInViewport(element)) {
                if (element.hasAttribute('data-bg')) {
                    this.loadBackgroundImage(element);
                } else if (element.tagName.toLowerCase() === 'video') {
                    this.loadVideo(element);
                } else {
                    this.loadImage(element);
                }
            }
        });
    }

    isInViewport(element) {
        // Verwende eine einfachere Berechnung für häufige Aufrufe
        const rect = element.getBoundingClientRect();
        const viewHeight = window.innerHeight || document.documentElement.clientHeight;
        const viewWidth = window.innerWidth || document.documentElement.clientWidth;

        // Optimierte Berechnung
        return (
            rect.bottom > 0 &&
            rect.right > 0 &&
            rect.top < viewHeight + rect.height &&
            rect.left < viewWidth + rect.width
        );
    }

    handleNewElement(element) {
        if (!this.shouldHandleElement(element)) return;

        if (this.options.spinnerEnabled) {
            this.handleSpinner(element);
        }

        // Prüfe, ob die Observer initialisiert wurden
        if (!this.videoObserver || !this.imageObserver) {
            // Wenn nicht, initialisiere sie
            if (!this.videoObserver) this.setupVideoObserver();
            if (!this.imageObserver) this.setupImageObserver();

            // Falls immer noch nicht initialisiert, logge einen Fehler und kehre zurück
            if (!this.videoObserver || !this.imageObserver) {
                console.error('Observer konnte nicht initialisiert werden');
                return;
            }
        }

        if (element.hasAttribute('data-bg')) {
            this.imageObserver.observe(element);
        } else if (element.tagName.toLowerCase() === 'video') {
            if (element.classList.contains('lazy') ||
                element.hasAttribute('data-src') ||
                element.querySelectorAll('source[data-src]').length > 0) {
                // Verwende nur den Observer, wenn er existiert
                if (this.videoObserver) {
                    this.videoObserver.observe(element);
                } else {
                    console.warn('Video Observer nicht verfügbar für:', element);
                }
            }
        } else if (element.tagName.toLowerCase() === 'source' && element.parentElement.tagName.toLowerCase() === 'video') {
            const videoElement = element.parentElement;
            if (videoElement.classList.contains('lazy') && !this.loadedElements.has(videoElement)) {
                if (this.videoObserver) {
                    this.videoObserver.observe(videoElement);
                }
            }
        } else if (element.tagName.toLowerCase() === 'img') {
            if (element.hasAttribute('data-src')) {
                this.imageObserver.observe(element);
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
        if (element.parentElement) {
            if ((element.classList.contains('cms-html-video-container') ||
                    element.classList.contains('content-media') ||
                    element.tagName.toLowerCase() === 'video' ||
                    element.parentElement.classList.contains('video-wrapper')) &&
                !element.querySelector('.lazy-loader-spinner')) {
                this.addSpinner(element);
            }
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

    loadBackgroundImage(element) {
        if (this.loadedElements.has(element)) return Promise.resolve();

        return new Promise((resolve, reject) => {
            const bgUrl = element.getAttribute('data-bg');
            if (!bgUrl) {
                reject('Kein data-bg Attribut gefunden');
                return;
            }

            const img = new Image();
            img.onload = () => {
                element.style.backgroundImage = `url('${bgUrl}')`;
                element.classList.add('loaded');
                element.removeAttribute('data-bg');
                this.loadedElements.add(element);
                this.removeSpinner(element);
                resolve();
            };

            img.onerror = (error) => {
                console.error('Fehler beim Laden des Hintergrundbildes:', error);
                this.removeSpinner(element);
                reject(error);
            };

            if (this.options.spinnerEnabled) {
                this.handleSpinner(element);
            }

            img.src = bgUrl;

            setTimeout(() => {
                if (!this.loadedElements.has(element)) {
                    element.style.backgroundImage = `url('${bgUrl}')`;
                    element.classList.add('loaded');
                    this.loadedElements.add(element);
                    this.removeSpinner(element);
                    resolve();
                }
            }, this.options.timeout);
        });
    }

    loadImage(img) {
        if (this.loadedElements.has(img)) return Promise.resolve();

        return new Promise((resolve, reject) => {
            const handleLoad = () => {
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
                    if (!this.loadedElements.has(img)) {
                        handleLoad();
                    }
                }, this.options.timeout);
            }
        });
    }

    loadVideo(video) {
        if (this.loadedElements.has(video) || this.loadingVideos.has(video)) return Promise.resolve();

        if (this.isMobile) {
            const currentlyLoading = document.querySelectorAll('video.loading').length;
            if (currentlyLoading >= this.options.maxSimultaneousLoads) {
                setTimeout(() => this.loadVideo(video), 500);
                return Promise.resolve();
            }
        }

        if (this.isMobile && video.getAttribute('data-src')) {
            video.setAttribute('data-original-src', video.getAttribute('data-src'));
        }

        const videoId = Math.random().toString(36).substr(2, 9);
        this.loadingVideos.set(video, {id: videoId, startTime: Date.now()});
        video.classList.add('loading');

        return new Promise((resolve, reject) => {
            video.setAttribute('playsinline', '');
            video.muted = true;

            const sources = video.querySelectorAll('source[data-src]');
            let hasLoadedSources = false;

            sources.forEach(source => {
                hasLoadedSources = true;
                source.src = source.dataset.src;
                source.removeAttribute('data-src');
            });

            if (video.dataset.src) {
                hasLoadedSources = true;
                video.src = video.dataset.src;
                video.removeAttribute('data-src');
            }

            if (!hasLoadedSources && sources.length > 0) {
                sources.forEach(source => {
                    if (source.hasAttribute('src')) {
                        hasLoadedSources = true;
                    }
                });
            }

            if (!hasLoadedSources) {
                this.loadingVideos.delete(video);
                video.classList.remove('loading');
                return resolve();
            }

            const cleanup = () => {
                this.removeSpinner(video);
                this.loadingVideos.delete(video);
                video.classList.remove('loading');
            };

            const success = () => {
                video.classList.add('loaded');
                this.loadedElements.add(video);
                cleanup();

                if (!this.isMobile) {
                    this.videoObserver.unobserve(video);
                }

                if (video.hasAttribute('autoplay') && this.isInViewport(video)) {
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
                if (this.isMobile) {
                    this.unloadVideo(video);
                } else {
                    this.showVideoFallback(video);
                }
                reject(e);
            };

            video.addEventListener('loadeddata', success, {once: true});
            video.addEventListener('error', error, {once: true});

            const timeoutId = setTimeout(() => {
                if (this.loadingVideos.has(video)) {
                    if (this.isMobile) {
                        this.unloadVideo(video);
                    } else {
                        cleanup();
                        this.showVideoFallback(video);
                    }
                }
            }, this.options.timeout);

            video.addEventListener('loadeddata', () => clearTimeout(timeoutId), {once: true});

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
                <button class="btn btn-primary btn-sm retry-video-btn">Video erneut laden</button>
                <button class="btn btn-outline-primary btn-sm reload-page-btn">Seite neu laden</button>
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
        document.querySelectorAll('[data-src], [data-bg], video.lazy').forEach(element => {
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
        document.querySelectorAll('[data-src], [data-bg], video.lazy').forEach(element => {
            if (this.shouldHandleElement(element)) {
                if (element.hasAttribute('data-bg')) {
                    this.loadBackgroundImage(element);
                } else if (element.tagName.toLowerCase() === 'video') {
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

        window.removeEventListener('scroll', this.scrollHandler);
        document.querySelectorAll('.scroll-wrapper').forEach(wrapper => {
            wrapper.removeEventListener('scroll', this.debouncedCheck);
        });

        // Event-Handler-Referenzen freigeben
        this.scrollHandler = null;
        this.debouncedCheck = null;
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

// Zusätzlicher Event-Listener für die Seiten-Initialisierung
document.addEventListener('DOMContentLoaded', function () {
    if (window.VSM && window.VSM.lazyLoader) {
        window.VSM.lazyLoader.checkUnobservedVideos();
    } else {
        window.VSM = window.VSM || {};
        window.VSM.lazyLoader = new VSMLazyLoader();
    }
});

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}