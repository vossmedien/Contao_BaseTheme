document.addEventListener('DOMContentLoaded', function () {
    // Globaler Cache für Video-HTML
    const videoCache = new Map();

    const lazyLoadConfig = {
        elements_selector: ".lazy",
        threshold: 300,
        callback_loading: (element) => {
            if (element.tagName === 'VIDEO') {
                const placeholder = element.closest('.video-placeholder');
                if (placeholder && !placeholder.dataset.opacitySet) {
                    placeholder.style.opacity = '0';
                    placeholder.dataset.opacitySet = 'true';
                }
            }
        },
        callback_loaded: (element) => {
            if (element.tagName === 'VIDEO') {
                const placeholder = element.closest('.video-placeholder');
                if (placeholder) {
                    setTimeout(() => {
                        element.play().catch(console.error);
                        requestAnimationFrame(() => {
                            placeholder.style.opacity = '1';
                        });
                    }, 750);
                }
            }
        },
        callback_error: (element) => {
            if (element.tagName === 'VIDEO') {
                const placeholder = element.closest('.video-placeholder');
                if (placeholder) {
                    placeholder.style.opacity = '1';
                }
            }
        }
    };

    function init3DEffect(container) {
        if (!window.matchMedia('(min-width: 1024px)').matches) return;

        container.querySelectorAll('.video-item').forEach(item => {
            const handleMouseMove = (e) => {
                const rect = item.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const rotateX = (y - rect.height / 2) / 10;
                const rotateY = (rect.width / 2 - x) / 10;

                item.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.0) translateZ(20px)`;
                item.style.boxShadow = `${-rotateY / 2}px ${rotateX / 2}px 20px rgba(0,0,0,0.2)`;
            };

            const handleMouseLeave = () => {
                item.style.transform = 'perspective(800px) rotateX(0) rotateY(0) scale(1) translateZ(0)';
                item.style.boxShadow = 'none';
            };

            item.addEventListener('mousemove', handleMouseMove);
            item.addEventListener('mouseleave', handleMouseLeave);
            item.style.transition = 'transform 0.1s ease-out, box-shadow 0.1s ease-out';
        });
    }

    async function loadVideo(container, lazyLoadInstance) {
        if (!container || container.dataset.videoLoaded === 'true') return;

        const lazyContainer = container.querySelector('.lazy-video-container');
        if (!lazyContainer) return;

        const params = lazyContainer.dataset.videoParams;

        // Prüfe Cache
        if (videoCache.has(params)) {
            lazyContainer.innerHTML = videoCache.get(params);
            container.dataset.videoLoaded = 'true';
            lazyLoadInstance?.update();
            return;
        }

        try {
            const response = await fetch('/video/render', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params
            });

            if (response.ok) {
                const html = await response.text();
                videoCache.set(params, html);
                lazyContainer.innerHTML = html;
                container.dataset.videoLoaded = 'true';
                lazyLoadInstance?.update();
            }
        } catch (error) {
            console.error('Error loading video:', error);
        }
    }

    function initVerticalSlider(container) {
        if (!container || container.dataset.initialized === 'true') return;

        const gridLazyLoad = new LazyLoad({
            ...lazyLoadConfig,
            container: container
        });

        const loadedContainers = new WeakSet();
        const isDesktop = window.matchMedia('(min-width: 769px)').matches;

        function loadSlideVideo(slide, force = false) {
            if (!slide) return false;
            const container = slide.querySelector('.video-preview');
            if (!container || loadedContainers.has(container)) return false;

            const isVisible = force || slide.classList.contains('swiper-slide-visible');
            if (!isVisible) return false;

            requestAnimationFrame(() => {
                loadVideo(container, gridLazyLoad);
                loadedContainers.add(container);
            });

            return true;
        }

        if (isDesktop) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const column = entry.target;
                    const swiper = column.swiper;
                    if (!swiper) return;

                    if (entry.isIntersecting) {
                        swiper.autoplay.start();
                        const visibleSlides = Array.from(swiper.slides)
                            .filter(slide => slide.classList.contains('swiper-slide-visible'));
                        visibleSlides.forEach(slide => loadSlideVideo(slide));
                    } else {
                        swiper.autoplay.stop();
                    }
                });
            }, {threshold: 0.1});

            container.querySelectorAll('.swiper').forEach((column, index) => {
    console.log(`Initialisiere Swiper Spalte ${index}`);

    const swiper = new Swiper(column, {
        direction: 'vertical',
        loop: true,

        spaceBetween: 0,
        speed: 50000,
        preloadImages: false,
        watchSlidesProgress: true,
                loopedSlides: 5, // Anpassung: Setze auf die Gesamtanzahl der Slides
        slidesPerView: 4,
        lazy: {
            loadPrevNext: true,
            loadPrevNextAmount: 1,
            loadOnTransitionStart: true
        },
        autoplay: {
            delay: 0,
            disableOnInteraction: false,
            reverseDirection: (index % 2) === 1
        },
        allowTouchMove: false,
        observer: true,
        observeParents: true,
        on: {
            init: function(swiper) {
                console.log('Swiper Init', {
                    totalSlides: swiper.slides.length,
                    activeIndex: swiper.activeIndex,
                    realIndex: swiper.realIndex,
                    slidesPerView: swiper.params.slidesPerView,
                    columnIndex: index
                });

                // Lade nur die initial sichtbaren Slides
                const visibleSlides = Array.from(swiper.slides)
                    .slice(swiper.activeIndex, swiper.activeIndex + swiper.params.slidesPerView);

                console.log(`Lade ${visibleSlides.length} sichtbare Slides für Spalte ${index}`);
                visibleSlides.forEach(slide => {
                    loadSlideVideo(slide, true);
                });

                init3DEffect(column);
            },
            slideChange: function(swiper) {
                // Lade das nächste Slide vorausschauend
                const direction = swiper.params.autoplay.reverseDirection ? -1 : 1;
                const nextIndex = (swiper.activeIndex + (direction * swiper.params.slidesPerView)) % swiper.slides.length;
                const nextSlide = swiper.slides[nextIndex];

                if (nextSlide) {
                    loadSlideVideo(nextSlide);
                }

                // Prüfe und lade fehlende sichtbare Slides nach
                const currentVisibleSlides = Array.from(swiper.slides)
                    .slice(swiper.activeIndex, swiper.activeIndex + swiper.params.slidesPerView);

                currentVisibleSlides.forEach(slide => {
                    loadSlideVideo(slide);
                });
            },
            reachEnd: function(swiper) {
                // Stelle sicher, dass der Loop funktioniert
                if (swiper.params.loop) {
                    swiper.slideToLoop(0, 0);
                }
            }
        }
    });

    observer.observe(column);
});
        } else {
            const mobileList = container.querySelector('.video-list');
            if (mobileList) {
                const videoObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const container = entry.target;
                            if (!loadedContainers.has(container)) {
                                loadVideo(container, gridLazyLoad);
                                loadedContainers.add(container);
                            }
                            videoObserver.unobserve(container);
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.1
                });

                mobileList.querySelectorAll('.video-preview').forEach(container => {
                    videoObserver.observe(container);
                });
            }
        }

        container.dataset.initialized = 'true';
    }

    // Init Observer für alle .ce_rsce_videogrid Elemente
    const gridObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initVerticalSlider(entry.target);
            }
        });
    }, {
        rootMargin: '50px',
        threshold: 0.1
    });

    document.querySelectorAll('.ce_rsce_videogrid').forEach(grid => {
        gridObserver.observe(grid);
    });

    // Optimierter Resize Handler
    const debouncedResize = debounce(() => {
        document.querySelectorAll('.ce_rsce_videogrid').forEach(container => {
            if (container.dataset.initialized === 'true') {
                const swipers = container.querySelectorAll('.swiper');
                swipers.forEach(column => {
                    if (column.swiper) {
                        column.swiper.update();
                    }
                });
            }
        });
    }, 250);

    window.addEventListener('resize', debouncedResize);

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

    // Cleanup-Funktion für den Cache
    function cleanupCache() {
        videoCache.clear();
    }

    // Cache leeren wenn Seite nicht sichtbar
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cleanupCache();
        }
    });
});