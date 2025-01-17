document.addEventListener('DOMContentLoaded', function () {
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
                    // Warten Sie mindestens 1 Sekunde, bevor das Video eingeblendet wird
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
        item.addEventListener('mousemove', (e) => {
            const rect = item.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Verstärkte Rotation (von /20 auf /10)
            const rotateX = (y - rect.height / 2) / 10;
            const rotateY = (rect.width / 2 - x) / 10;

            // Hinzufügung von:
            // - Stärkere Perspektive (von 1000px auf 800px)
            // - Leichte Skalierung
            // - Leichte Z-Achsen-Translation
            item.style.transform = `
                perspective(800px) 
                rotateX(${rotateX}deg) 
                rotateY(${rotateY}deg)
                scale(1.0)
                translateZ(20px)
            `;

            // Zusätzlicher Schatten-Effekt für mehr Tiefe
            item.style.boxShadow = `
                ${-rotateY/2}px 
                ${rotateX/2}px 
                20px rgba(0,0,0,0.2)
            `;
        });

        item.addEventListener('mouseleave', () => {
            item.style.transform = 'perspective(800px) rotateX(0) rotateY(0) scale(1) translateZ(0)';
            item.style.boxShadow = 'none';
        });

        // Sanftere Übergänge
        item.style.transition = 'transform 0.1s ease-out, box-shadow 0.1s ease-out';
    });
}

    async function loadVideo(container, lazyLoadInstance) {
        if (!container || container.dataset.videoLoaded === 'true') return;

        const lazyContainer = container.querySelector('.lazy-video-container');
        if (!lazyContainer) return;

        const params = JSON.parse(lazyContainer.dataset.videoParams);

        try {
            const response = await fetch('/video/render', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(params)
            });

            if (response.ok) {
                const html = await response.text();
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

        const isMobile = window.matchMedia('(max-width: 1023px)').matches;

        container.querySelectorAll('.swiper').forEach((column, index) => {
            const swiper = new Swiper(column, {
                direction: 'vertical',
                loop: true,
                loopedSlides: 3,
                slidesPerView: 3,
                spaceBetween: 0,
                speed: isMobile ? 12500 : 25000,
                autoplay: {
                    delay: 0,
                    disableOnInteraction: false,
                    reverseDirection: (index % 2) === 1,
                    enabled: !isMobile
                },
                allowTouchMove: false,
                watchSlidesProgress: true,
                observer: true,
                observeParents: true,
                on: {
                    init: function(swiper) {
                        swiper.slides.forEach(slide => {
                            const container = slide.querySelector('.video-preview');
                            if (container) loadVideo(container, gridLazyLoad);
                        });
                        init3DEffect(column);
                    },
                    slideChange: function(swiper) {
                        const currentIndex = swiper.activeIndex;
                        const slidesToLoad = swiper.params.slidesPerView + 2;

                        for (let i = 0; i < slidesToLoad; i++) {
                            const slide = swiper.slides[(currentIndex + i) % swiper.slides.length];
                            const container = slide?.querySelector('.video-preview');
                            if (container) loadVideo(container, gridLazyLoad);
                        }
                    }
                }
            });

            // Visibility Observer
            new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!isMobile) {
                        swiper.autoplay[entry.isIntersecting ? 'start' : 'stop']();
                    }
                });
            }, { threshold: 0.1 }).observe(column);
        });

        container.dataset.initialized = 'true';
    }

    // Init Observer
    new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initVerticalSlider(entry.target);
            }
        });
    }, {
        rootMargin: '50px',
        threshold: 0.1
    }).observe(document.querySelector('.ce_rsce_videogrid'));
});