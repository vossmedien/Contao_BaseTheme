class VSMLazyLoader {
    constructor(options = {}) {
        this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        this.options = {
            root: null,
            rootMargin: this.isMobile ? '50px 0px' : '100px 0px',
            threshold: this.isMobile ? 0.15 : 0.25,
            excludeSelectors: [],
            spinnerEnabled: true,
            timeout: 6000,
            maxSimultaneousLoads: this.isMobile ? 2 : 4,
            ...options
        };

        this.loadedElements = new Set();
        this.loadingVideos = new Map();
        this.imageObserver = null;
        this.videoObserver = null;
        this.mutationObserver = null;

        // Performance-Caches
        this.viewportCache = { width: 0, height: 0, lastUpdate: 0 };
        this.bestVideoFormat = null;
        
        // Debug-Flag
        this.debug = false;

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
        // Performance: Bestimme Video-Format einmal beim Start
        this.bestVideoFormat = this.determineBestVideoFormat();
        
        if (document.readyState === 'loading') {
            window.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }

        // Reduzierter Fallback-Check
        setTimeout(() => {
            if (!this.videoObserver || !this.imageObserver) {
                console.warn('Observer nicht initialisiert, versuche erneut...');
                this.init();
            }
        }, 2000);
    }

    init() {
        if (!document.body) {
            console.warn('Document body noch nicht bereit, warte...');
            window.addEventListener('DOMContentLoaded', () => this.init());
            return;
        }

        try {
            if ('IntersectionObserver' in window) {
                this.setupImageObserver();
                this.setupVideoObserver();
                this.setupMutationObserver();

                if (!this.isMobile) {
                    this.setupScrollListeners();
                }

                if (!this.videoObserver || !this.imageObserver) {
                    console.error('Observer konnten nicht initialisiert werden');
                    return;
                }

                this.observeElements();

                if (!this.isMobile) {
                    setInterval(() => this.checkLostElements(), 15000);
                }
            } else {
                console.warn('IntersectionObserver nicht verfügbar, verwende Fallback');
                this.loadAllMediaDirectly();
            }
        } catch (e) {
            console.error('Fehler bei der Initialisierung:', e);
        }
    }

    // Performance: Gecachte Viewport-Berechnung
    updateViewportCache() {
        const now = Date.now();
        if (now - this.viewportCache.lastUpdate > 100) {
            this.viewportCache.width = window.innerWidth || document.documentElement.clientWidth;
            this.viewportCache.height = window.innerHeight || document.documentElement.clientHeight;
            this.viewportCache.lastUpdate = now;
        }
    }

    checkUnobservedVideos() {
        const videos = document.querySelectorAll('video.lazy, video[data-poster]');
        videos.forEach(video => {
            const hasDirectSources = Array.from(video.querySelectorAll('source')).some(source =>
                source.hasAttribute('src') && !source.hasAttribute('data-src'));

            if (hasDirectSources) {
                if (!video.classList.contains('loaded')) {
                    video.classList.add('loaded');
                    this.loadedElements.add(video);
                }
                return;
            }

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
            rootMargin: this.options.rootMargin,
            threshold: this.options.threshold
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
                rootMargin: '100px 0px',
                threshold: 0.1
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

                if (this.loadedElements.has(video) || video.classList.contains('loaded')) {
                    if (entry.isIntersecting && video.paused && video.hasAttribute('autoplay')) {
                        video.play().catch(err => this.log('Autoplay error:', err));
                    } else if (!entry.isIntersecting && video.hasAttribute('autoplay')) {
                        video.pause();
                        if (this.isMobile) {
                            this.unloadVideo(video);
                        }
                    }
                    return;
                }

                if (entry.isIntersecting) {
                    if (entry.intersectionRatio >= this.options.threshold &&
                        !video.classList.contains('loaded') &&
                        !video.classList.contains('loading')) {
                        this.loadVideo(video);
                    } else if (video.paused && video.hasAttribute('autoplay')) {
                        video.play().catch(err => this.log('Autoplay error:', err));
                    }
                } else if (this.loadingVideos.has(video)) {
                    this.log('Video rolled out of viewport, cancelling load:', video);
                    this.cancelVideoLoad(video);
                } else if (this.isMobile && video.classList.contains('loaded')) {
                    this.unloadVideo(video);
                }
            });
        }, {
            root: null,
            rootMargin: this.options.rootMargin,
            threshold: [0, this.options.threshold, 0.5, 1.0]
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

        const clonedVideo = video.cloneNode(true);
        if (video.parentNode) {
            video.parentNode.replaceChild(clonedVideo, video);
        }
        video = clonedVideo;

        this.loadedElements.delete(video);
        video.removeAttribute('src');
        video.load();

        if (video.hasAttribute('poster')) {
            video.setAttribute('data-poster', video.getAttribute('poster'));
            video.removeAttribute('poster');
        }

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
                this.updateViewportCache();
                this.checkVisibleElements();
            });
        };

        window.addEventListener('scroll', scrollHandler, {passive: true});

        document.querySelectorAll('.scroll-wrapper').forEach(scrollWrapper => {
            scrollWrapper.addEventListener('scroll', this.debounce(() => {
                this.checkVisibleElements(scrollWrapper);
            }, 150), {passive: true});
        });
    }

    setupMutationObserver() {
        this.mutationObserver = new MutationObserver((mutations) => {
            const elementsToProcess = [];
            
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && this.shouldHandleElement(node)) {
                            elementsToProcess.push(node);
                            if (node.querySelectorAll) {
                                const childElements = node.querySelectorAll('[data-src], [data-bg], video.lazy');
                                childElements.forEach(element => {
                                    if (this.shouldHandleElement(element)) {
                                        elementsToProcess.push(element);
                                    }
                                });
                            }
                        }
                    });
                }
            });

            if (elementsToProcess.length > 0) {
                requestAnimationFrame(() => {
                    elementsToProcess.forEach(element => this.handleNewElement(element));
                });
            }
        });

        try {
            this.mutationObserver.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: false
            });
        } catch (e) {
            console.warn('MutationObserver konnte nicht initialisiert werden:', e);
        }
    }

    checkVisibleElements(container = document) {
        if (this.isMobile) return;

        const elements = container.querySelectorAll('[data-src]:not(.loaded), [data-bg]:not(.loaded), video.lazy:not(.loaded)');
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

        document.querySelectorAll('[data-src]:not(.loaded), video.lazy:not(.loaded)').forEach(element => {
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
        this.updateViewportCache();
        
        const rect = element.getBoundingClientRect();
        const viewHeight = this.viewportCache.height;
        const viewWidth = this.viewportCache.width;

        const visibleHeight = Math.min(rect.bottom, viewHeight) - Math.max(rect.top, 0);
        const visibleWidth = Math.min(rect.right, viewWidth) - Math.max(rect.left, 0);

        if (visibleHeight <= 0 || visibleWidth <= 0) {
            return false;
        }

        const visibleArea = visibleHeight * visibleWidth;
        const elementArea = rect.height * rect.width;

        const visiblePercentage = elementArea > 0 ? (visibleArea / elementArea) * 100 : 0;
        return visiblePercentage >= (this.isMobile ? 15 : 25);
    }

    handleNewElement(element) {
        if (!this.shouldHandleElement(element)) return;

        if (this.loadedElements.has(element) ||
            element.classList.contains('loaded') ||
            element.classList.contains('loading')) {
            return;
        }

        if (this.options.spinnerEnabled) {
            this.handleSpinner(element);
        }

        if (!this.videoObserver || !this.imageObserver) {
            if (!this.videoObserver) this.setupVideoObserver();
            if (!this.imageObserver) this.setupImageObserver();

            if (!this.videoObserver || !this.imageObserver) {
                console.error('Observer konnte nicht initialisiert werden');
                return;
            }
        }

        if (element.hasAttribute('data-bg')) {
            this.imageObserver.observe(element);
        } else if (element.tagName.toLowerCase() === 'video') {
            const hasDirectSources = Array.from(element.querySelectorAll('source')).some(source =>
                source.hasAttribute('src') && !source.hasAttribute('data-src'));

            if (hasDirectSources) {
                if (!element.classList.contains('loaded')) {
                    element.classList.add('loaded');
                    this.loadedElements.add(element);
                }

                if (element.hasAttribute('autoplay') && this.isInViewport(element)) {
                    element.play().catch(() => {
                        element.muted = true;
                        element.play().catch(console.warn);
                    });
                }
                return;
            }

            if (element.classList.contains('lazy') ||
                element.hasAttribute('data-src') ||
                element.hasAttribute('data-poster') ||
                element.querySelectorAll('source[data-src]').length > 0) {
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
        if (this.loadedElements.has(video) || this.loadingVideos.has(video)) {
            return Promise.resolve();
        }

        if (!this.isInViewport(video)) {
            this.log('Video nicht im Viewport, lade nicht:', video);
            return Promise.resolve();
        }

        if (this.isMobile) {
            const currentlyLoading = document.querySelectorAll('video.loading').length;
            if (currentlyLoading >= this.options.maxSimultaneousLoads) {
                setTimeout(() => this.loadVideo(video), 1000);
                return Promise.resolve();
            }
        }

        const bestFormat = this.bestVideoFormat;
        
        const originalSources = [];
        video.querySelectorAll('source[data-src]').forEach(source => {
            originalSources.push({
                type: source.getAttribute('type'),
                src: source.getAttribute('data-src')
            });
        });

        if (video.getAttribute('data-src')) {
            video.setAttribute('data-original-src', video.getAttribute('data-src'));
        }

        const videoId = Math.random().toString(36).substr(2, 9);
        this.loadingVideos.set(video, {
            id: videoId,
            startTime: Date.now(),
            originalSources: originalSources
        });

        video.classList.add('loading');
        this.log('Video wird geladen:', video);

        return new Promise((resolve, reject) => {
            video.setAttribute('playsinline', '');
            video.muted = true;

            if (video.hasAttribute('data-poster')) {
                video.poster = video.getAttribute('data-poster');
                video.removeAttribute('data-poster');
            }

            while (video.firstChild) {
                video.removeChild(video.firstChild);
            }

            let bestSource = null;
            for (const source of originalSources) {
                const type = source.type || '';
                if (type.includes(bestFormat)) {
                    bestSource = source;
                    break;
                }
            }

            if (!bestSource && originalSources.length > 0) {
                bestSource = originalSources[0];
            }

            if (bestSource) {
                const newSource = document.createElement('source');
                newSource.src = bestSource.src;
                newSource.type = bestSource.type;
                video.appendChild(newSource);

                this.log(`Lade AUSSCHLIESSLICH ${bestSource.type} Format:`, bestSource.src);

                const cleanup = () => {
                    this.removeSpinner(video);
                    this.loadingVideos.delete(video);
                    video.classList.remove('loading');
                };

                const success = () => {
                    video.classList.add('loaded');
                    this.loadedElements.add(video);
                    cleanup();

                    if (video.hasAttribute('autoplay') && this.isInViewport(video)) {
                        video.play().catch(err => {
                            video.muted = true;
                            video.play().catch(e => this.log('Autoplay error:', e));
                        });
                    }

                    resolve();
                };

                const error = (e) => {
                    this.log('Video load error:', e);
                    cleanup();

                    const remainingSources = originalSources.filter(s => s !== bestSource);
                    if (remainingSources.length > 0 && video.parentNode) {
                        this.log('Versuche alternatives Format nach Fehler');
                        video.innerHTML = '';

                        const alternativeSource = document.createElement('source');
                        alternativeSource.src = remainingSources[0].src;
                        alternativeSource.type = remainingSources[0].type;
                        video.appendChild(alternativeSource);

                        video.load();
                    } else {
                        reject(e);
                    }
                };

                video.addEventListener('loadeddata', success, {once: true});
                video.addEventListener('error', error, {once: true});

                const timeoutId = setTimeout(() => {
                    if (this.loadingVideos.has(video)) {
                        this.log('Video-Ladevorgang Timeout erreicht');
                        cleanup();
                    }
                }, this.options.timeout);

                const checkVisibilityInterval = setInterval(() => {
                    if (!this.loadingVideos.has(video)) {
                        clearInterval(checkVisibilityInterval);
                        return;
                    }

                    if (!this.isInViewport(video)) {
                        this.log('Video nicht mehr im Viewport während des Ladens, breche ab');
                        clearInterval(checkVisibilityInterval);
                        this.cancelVideoLoad(video);
                    }
                }, 500);

                video.addEventListener('loadeddata', () => {
                    clearTimeout(timeoutId);
                    clearInterval(checkVisibilityInterval);
                }, {once: true});

                video.addEventListener('error', () => {
                    clearTimeout(timeoutId);
                    clearInterval(checkVisibilityInterval);
                }, {once: true});

                video.load();
            } else {
                this.loadingVideos.delete(video);
                video.classList.remove('loading');
                this.log('Keine Videoquelle gefunden');
                resolve();
            }
        });
    }

    determineBestVideoFormat() {
        const testVideo = document.createElement('video');

        const formats = [
            {
                name: 'webm',
                type: 'video/webm; codecs="vp9, opus"',
                fallback: 'video/webm; codecs="vp8, vorbis"'
            },
            {
                name: 'mp4',
                type: 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
                fallback: 'video/mp4; codecs="avc1.4D401E, mp4a.40.2"'
            },
            {
                name: 'ogg',
                type: 'video/ogg; codecs="theora, vorbis"'
            }
        ];

        for (const format of formats) {
            const mainSupport = testVideo.canPlayType(format.type);
            if (mainSupport === 'probably') {
                return format.name;
            }

            if (format.fallback) {
                const fallbackSupport = testVideo.canPlayType(format.fallback);
                if (fallbackSupport === 'probably') {
                    return format.name;
                }
            }
        }

        for (const format of formats) {
            const mainSupport = testVideo.canPlayType(format.type);
            if (mainSupport === 'maybe') {
                return format.name;
            }

            if (format.fallback) {
                const fallbackSupport = testVideo.canPlayType(format.fallback);
                if (fallbackSupport === 'maybe') {
                    return format.name;
                }
            }
        }

        return 'mp4';
    }

    cancelVideoLoad(video) {
        if (!this.loadingVideos.has(video)) return;

        this.log('Breche Videoladen ab für:', video);

        const loadingInfo = this.loadingVideos.get(video);
        const originalSources = loadingInfo.originalSources || [];

        this.loadingVideos.delete(video);

        video.pause();
        video.removeAttribute('src');

        while (video.firstChild) {
            video.removeChild(video.firstChild);
        }

        originalSources.forEach(sourceInfo => {
            const source = document.createElement('source');
            source.setAttribute('data-src', sourceInfo.src);
            if (sourceInfo.type) {
                source.setAttribute('type', sourceInfo.type);
            }
            video.appendChild(source);
        });

        video.load();

        video.classList.remove('loading', 'loaded');
        if (!video.classList.contains('lazy')) {
            video.classList.add('lazy');
        }

        this.removeSpinner(video);
    }

    showVideoFallback(video) {
        this.removeSpinner(video);
        console.warn('Video konnte nicht geladen werden:', video);
    }

    observeElements() {
        document.querySelectorAll('[data-src], [data-bg], video.lazy, video[data-poster]').forEach(element => {
            if (element.tagName.toLowerCase() === 'video') {
                const hasDirectSources = Array.from(element.querySelectorAll('source')).some(source =>
                    source.hasAttribute('src') && !source.hasAttribute('data-src'));

                if (hasDirectSources) {
                    if (!element.classList.contains('loaded')) {
                        element.classList.add('loaded');
                        this.loadedElements.add(element);
                    }

                    if (element.hasAttribute('autoplay') && this.isInViewport(element)) {
                        element.play().catch(() => {
                            element.muted = true;
                            element.play().catch(console.warn);
                        });
                    }
                    return;
                }
            }

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
        document.querySelectorAll('[data-src], [data-bg], video.lazy, video[data-poster]').forEach(element => {
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

        this.scrollHandler = null;
        this.debouncedCheck = null;
    }

    log(...args) {
        if (this.debug) {
            console.log(...args);
        }
    }

    debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}

window.VSM = window.VSM || {};
window.VSM.lazyLoader = new VSMLazyLoader();

window.VSM.lazyLoadInstance = {
    update: () => {
    }
};

document.addEventListener('vsm:videoLoaded', function (e) {
    if (window.VSM.lazyLoader && e.detail.videoElement) {
        window.VSM.lazyLoader.handleNewElement(e.detail.videoElement);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    if (window.VSM && window.VSM.lazyLoader) {
        window.VSM.lazyLoader.checkUnobservedVideos();
    } else {
        window.VSM = window.VSM || {};
        window.VSM.lazyLoader = new VSMLazyLoader();
    }
});