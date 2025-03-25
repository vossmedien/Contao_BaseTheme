class VSMLazyLoader {
    constructor(options = {}) {
        this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        this.options = {
            root: null,
            rootMargin: this.isMobile ? '20px 0px' : '50px 0px',
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
        const videos = document.querySelectorAll('video.lazy, video[data-poster]');
        videos.forEach(video => {
            // Prüfe, ob das Video bereits direkt HTML-seitig geladen wurde (ohne src als data-src)
            const hasDirectSources = Array.from(video.querySelectorAll('source')).some(source =>
                source.hasAttribute('src') && !source.hasAttribute('data-src'));

            // Wenn das Video bereits direkt Quellen hat, markiere es als geladen und nicht erneut beobachten
            if (hasDirectSources) {
                if (!video.classList.contains('loaded')) {
                    video.classList.add('loaded');
                    this.loadedElements.add(video);
                }
                return;
            }

            // Normaler Lazy-Load-Prozess für nicht geladene Videos
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

                // Prüfe, ob das Video bereits geladen wurde
                if (this.loadedElements.has(video) || video.classList.contains('loaded')) {
                    // Video ist bereits geladen, nur wiedergeben wenn nötig
                    if (entry.isIntersecting && video.paused && video.hasAttribute('autoplay')) {
                        video.play().catch(err => this.log('Autoplay error:', err));
                    } else if (!entry.isIntersecting && video.hasAttribute('autoplay')) {
                        // Beim Verlassen des Viewports pausieren
                        video.pause();
                        // Auf Mobile komplett entladen
                        if (this.isMobile) {
                            this.unloadVideo(video);
                        }
                    }
                    return;
                }

                if (entry.isIntersecting) {
                    // Nur laden, wenn ein signifikanter Teil sichtbar ist
                    if (entry.intersectionRatio >= 0.25 &&
                        !video.classList.contains('loaded') &&
                        !video.classList.contains('loading')) {
                        this.loadVideo(video);
                    } else if (video.paused && video.hasAttribute('autoplay')) {
                        video.play().catch(err => this.log('Autoplay error:', err));
                    }
                } else if (this.loadingVideos.has(video)) {
                    // Wenn das Video noch am Laden ist und aus dem Viewport rollt
                    this.log('Video rolled out of viewport, cancelling load:', video);
                    this.cancelVideoLoad(video);
                } else if (this.isMobile && video.classList.contains('loaded')) {
                    this.unloadVideo(video);
                }
            });
        }, {
            root: null,
            rootMargin: this.isMobile ? '20px 0px' : this.options.rootMargin,
            threshold: [0, 0.25, 0.5, 0.75, 1.0]
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

        // Alle Event-Listener entfernen
        const clonedVideo = video.cloneNode(true);
        if (video.parentNode) {
            video.parentNode.replaceChild(clonedVideo, video);
        }
        video = clonedVideo;

        this.loadedElements.delete(video);
        video.removeAttribute('src');
        video.load();

        // Speichere das poster-Attribut als data-poster
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

        document.querySelectorAll('[data-src]:not(.loaded), [data-bg]:not(.loaded), video.lazy:not(.loaded), video[data-poster]:not(.loaded)').forEach(element => {
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
        // Strenge Viewport-Erkennung
        const rect = element.getBoundingClientRect();
        const viewHeight = window.innerHeight || document.documentElement.clientHeight;
        const viewWidth = window.innerWidth || document.documentElement.clientWidth;

        // Berechne den sichtbaren Bereich
        const visibleHeight = Math.min(rect.bottom, viewHeight) - Math.max(rect.top, 0);
        const visibleWidth = Math.min(rect.right, viewWidth) - Math.max(rect.left, 0);

        if (visibleHeight <= 0 || visibleWidth <= 0) {
            return false;
        }

        const visibleArea = visibleHeight * visibleWidth;
        const elementArea = rect.height * rect.width;

        // Mindestens 30% des Elements müssen sichtbar sein
        const visiblePercentage = elementArea > 0 ? (visibleArea / elementArea) * 100 : 0;
        return visiblePercentage >= 30;
    }

    handleNewElement(element) {
        if (!this.shouldHandleElement(element)) return;

        // Wenn das Element bereits geladen ist, nicht erneut verarbeiten
        if (this.loadedElements.has(element) ||
            element.classList.contains('loaded') ||
            element.classList.contains('loading')) {
            return;
        }

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
            // Prüfe, ob das Video bereits direkt HTML-seitig geladen wurde
            const hasDirectSources = Array.from(element.querySelectorAll('source')).some(source =>
                source.hasAttribute('src') && !source.hasAttribute('data-src'));

            // Wenn absolut Pfad erkannt und keine data-src Attribute
            if (hasDirectSources) {
                if (!element.classList.contains('loaded')) {
                    element.classList.add('loaded');
                    this.loadedElements.add(element);
                }

                // Wenn das Video autoplay haben sollte und im Viewport ist, abspielen
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
        if (this.loadedElements.has(video) || this.loadingVideos.has(video)) {
            return Promise.resolve();
        }

        // Stelle sicher, dass das Video wirklich im Viewport ist
        if (!this.isInViewport(video)) {
            this.log('Video nicht im Viewport, lade nicht:', video);
            return Promise.resolve();
        }

        if (this.isMobile) {
            const currentlyLoading = document.querySelectorAll('video.loading').length;
            if (currentlyLoading >= this.options.maxSimultaneousLoads) {
                setTimeout(() => this.loadVideo(video), 500);
                return Promise.resolve();
            }
        }

        // Originale Quellen vor dem Laden erfassen und speichern
        const originalSources = [];
        video.querySelectorAll('source[data-src]').forEach(source => {
            originalSources.push({
                type: source.getAttribute('type'),
                src: source.getAttribute('data-src')
            });
        });

        // Originale src speichern, falls vorhanden
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

            // Verarbeite das data-poster-Attribut
            if (video.hasAttribute('data-poster')) {
                video.poster = video.getAttribute('data-poster');
                video.removeAttribute('data-poster');
            }

            // RADIKALER ANSATZ: Entferne ALLE source-Elemente
            while (video.firstChild) {
                video.removeChild(video.firstChild);
            }

            // Bestimme das beste Format
            const bestFormat = this.determineBestVideoFormat();
            this.log('Bestes Videoformat:', bestFormat);

            // Finde die passende Quelle
            let bestSource = null;
            for (const source of originalSources) {
                const type = source.type || '';
                if (type.includes(bestFormat)) {
                    bestSource = source;
                    break;
                }
            }

            // Fallback, wenn keine passende Quelle gefunden wurde
            if (!bestSource && originalSources.length > 0) {
                bestSource = originalSources[0];
            }

            if (bestSource) {
                // Erstelle nur EINEN source-Element mit dem besten Format
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

                    // Bei Fehler: Versuche ein anderes Format, falls verfügbar
                    const remainingSources = originalSources.filter(s => s !== bestSource);
                    if (remainingSources.length > 0 && video.parentNode) {
                        this.log('Versuche alternatives Format nach Fehler');
                        // Entferne das fehlerhafte source-Element
                        video.innerHTML = '';

                        // Erstelle ein neues source-Element mit der nächsten verfügbaren Quelle
                        const alternativeSource = document.createElement('source');
                        alternativeSource.src = remainingSources[0].src;
                        alternativeSource.type = remainingSources[0].type;
                        video.appendChild(alternativeSource);

                        // Lade das Video erneut
                        video.load();
                    } else {
                        reject(e);
                    }
                };

                // Event-Listener für erfolgreiche Ladung
                video.addEventListener('loadeddata', success, {once: true});
                video.addEventListener('error', error, {once: true});

                // Timeout für Ladevorgang
                const timeoutId = setTimeout(() => {
                    if (this.loadingVideos.has(video)) {
                        this.log('Video-Ladevorgang Timeout erreicht');
                        cleanup();
                    }
                }, this.options.timeout);

                // Überwachung: Bleibt Video im Viewport?
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
                }, 300); // Häufigere Prüfung

                // Aufräumen der Intervalle und Timeouts
                video.addEventListener('loadeddata', () => {
                    clearTimeout(timeoutId);
                    clearInterval(checkVisibilityInterval);
                }, {once: true});

                video.addEventListener('error', () => {
                    clearTimeout(timeoutId);
                    clearInterval(checkVisibilityInterval);
                }, {once: true});

                // Starte den Ladevorgang
                video.load();
            } else {
                // Keine Quelle gefunden
                this.loadingVideos.delete(video);
                video.classList.remove('loading');
                this.log('Keine Videoquelle gefunden');
                resolve();
            }
        });
    }

    // Ermittelt das beste Videoformat für den aktuellen Browser
    determineBestVideoFormat() {
        // Test-Video-Element erstellen
        const testVideo = document.createElement('video');

        // Formate nach Priorität
        const formats = [
            {
                name: 'webm',
                type: 'video/webm; codecs="vp9, opus"', // Moderne WebM-Variante
                fallback: 'video/webm; codecs="vp8, vorbis"' // Ältere WebM-Variante
            },
            {
                name: 'mp4',
                type: 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"', // H.264 High Profile
                fallback: 'video/mp4; codecs="avc1.4D401E, mp4a.40.2"' // H.264 Baseline
            },
            {
                name: 'ogg',
                type: 'video/ogg; codecs="theora, vorbis"'
            }
        ];

        // Teste Format-Unterstützung
        for (const format of formats) {
            // Prüfe zuerst die primäre Codec-Variante
            const mainSupport = testVideo.canPlayType(format.type);
            if (mainSupport === 'probably') {
                return format.name;
            }

            // Prüfe dann die Fallback-Variante, falls vorhanden
            if (format.fallback) {
                const fallbackSupport = testVideo.canPlayType(format.fallback);
                if (fallbackSupport === 'probably') {
                    return format.name;
                }
            }
        }

        // Zweiter Durchlauf für 'maybe' Support
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

        // Fallback auf MP4, wenn nichts anderes unterstützt wird
        return 'mp4';
    }

    cancelVideoLoad(video) {
        if (!this.loadingVideos.has(video)) return;

        this.log('Breche Videoladen ab für:', video);

        // Speichere die Original-Quellen
        const loadingInfo = this.loadingVideos.get(video);
        const originalSources = loadingInfo.originalSources || [];

        // Aus Tracking entfernen
        this.loadingVideos.delete(video);

        // Video stoppen und zurücksetzen
        video.pause();
        video.removeAttribute('src');

        // VOLLSTÄNDIGES Zurücksetzen: Entferne alle Kinder
        while (video.firstChild) {
            video.removeChild(video.firstChild);
        }

        // Stelle die originalen source-Elemente mit data-src wieder her
        originalSources.forEach(sourceInfo => {
            const source = document.createElement('source');
            source.setAttribute('data-src', sourceInfo.src);
            if (sourceInfo.type) {
                source.setAttribute('type', sourceInfo.type);
            }
            video.appendChild(source);
        });

        // Video komplett neu laden, um den Download zu stoppen
        video.load();

        // Entferne Statusklassen
        video.classList.remove('loading', 'loaded');
        if (!video.classList.contains('lazy')) {
            video.classList.add('lazy');
        }

        this.removeSpinner(video);
    }

    showVideoFallback(video) {
        // Fallback entfernt, stattdessen nur sauberes Aufräumen
        this.removeSpinner(video);
        console.warn('Video konnte nicht geladen werden:', video);
    }

    observeElements() {
        document.querySelectorAll('[data-src], [data-bg], video.lazy, video[data-poster]').forEach(element => {
            // Prüfe, ob das Element bereits verarbeitet wurde oder direkte src-Attribute hat
            if (element.tagName.toLowerCase() === 'video') {
                const hasDirectSources = Array.from(element.querySelectorAll('source')).some(source =>
                    source.hasAttribute('src') && !source.hasAttribute('data-src'));

                if (hasDirectSources) {
                    if (!element.classList.contains('loaded')) {
                        element.classList.add('loaded');
                        this.loadedElements.add(element);
                    }

                    // Autoplay für Videos im Viewport aktivieren
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

        // Event-Handler-Referenzen freigeben
        this.scrollHandler = null;
        this.debouncedCheck = null;
    }

    // Hilfsfunktion zum Loggen, nur wenn Debug aktiv ist
    log(...args) {
        if (this.debug) {
            console.log(...args);
        }
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