document.addEventListener('DOMContentLoaded', function () {
    const videoCache = new Map();
    const loadedContainers = new WeakSet();

    function init3DEffect(container) {
        if (!window.matchMedia('(min-width: 1024px)').matches) return;

        container.querySelectorAll('.video-item').forEach(item => {
            const handleMouseMove = (e) => {
                const rect = item.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const rotateX = (y - rect.height / 2) / 10;
                const rotateY = (rect.width / 2 - x) / 10;

                // Bestehende translateY-Transformation beibehalten
                const currentTranslateY = item.style.transform?.match(/translateY\(([^)]+)\)/)?.[1] || '0px';

                item.style.transform = `translateY(${currentTranslateY}) perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.0) translateZ(20px)`;
                //item.style.boxShadow = `${-rotateY / 2}px ${rotateX / 2}px 20px rgba(0,0,0,0.2)`;
            };

            const handleMouseLeave = () => {
                // Bestehende translateY-Transformation beibehalten
                const currentTranslateY = item.style.transform?.match(/translateY\(([^)]+)\)/)?.[1] || '0px';
                item.style.transform = `translateY(${currentTranslateY})`;
               // item.style.boxShadow = 'none';
            };

            item.addEventListener('mousemove', handleMouseMove);
            item.addEventListener('mouseleave', handleMouseLeave);
        });
    }

    async function loadVideo(container) {
        if (!container || container.dataset.videoLoaded === 'true') return;

        const lazyContainer = container.querySelector('.lazy-video-container');
        if (!lazyContainer) return;

        const params = lazyContainer.dataset.videoParams;
        if (!params) return;

        try {
            if (videoCache.has(params)) {
                lazyContainer.innerHTML = videoCache.get(params);
                container.dataset.videoLoaded = 'true';
                initVideoAfterLoad(lazyContainer);
                return;
            }

            const response = await fetch('/video/render', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const html = await response.text();
            if (html) {
                videoCache.set(params, html);
                lazyContainer.innerHTML = html;
                container.dataset.videoLoaded = 'true';
                initVideoAfterLoad(lazyContainer);
            }
        } catch (error) {
            console.error('Fehler beim Laden des Videos:', error);
            container.dataset.videoLoaded = 'false';
        }
    }

    function initVideoAfterLoad(container) {
        const video = container.querySelector('video');
        if (!video) return;

        if (video.dataset.src) {
            video.src = video.dataset.src;
            video.removeAttribute('data-src');
        }

        const sources = video.querySelectorAll('source[data-src]');
        sources.forEach(source => {
            source.src = source.dataset.src;
            source.removeAttribute('data-src');
        });

        if (video.dataset.poster) {
            video.poster = video.dataset.poster;
        }

        video.load();

        if (video.autoplay) {
            video.play().catch(e => {
                console.warn('AutoPlay fehlgeschlagen, versuche mit muted:', e);
                video.muted = true;
                video.play().catch(e => console.warn('Auch muted AutoPlay fehlgeschlagen:', e));
            });
        }

        if (window.VSM && window.VSM.lazyMediaLoader) {
            video.classList.add('lazy-handled', 'loaded');
            document.dispatchEvent(new CustomEvent('vsm:videoLoaded', {
                detail: {videoElement: video}
            }));
        }
    }

function initInfiniteScroll(container) {
    if (!container || container.dataset.initialized === 'true') return;

    // Nur für Desktop initialisieren (ab 768px)
    if (!window.matchMedia('(min-width: 768px)').matches) {
        // Für Mobile nur Videos laden
        container.querySelectorAll('.video-preview').forEach(preview => {
            if (!loadedContainers.has(preview)) {
                loadVideo(preview);
                loadedContainers.add(preview);
            }
        });
        return;
    }

    container.querySelectorAll('.video-column').forEach((column, index) => {
        const scrollContent = column.querySelector('.scroll-content');
        if (!scrollContent) return;

        // Berechne sichtbare Items
        const columnHeight = column.offsetHeight;
        const items = Array.from(scrollContent.querySelectorAll('.video-item'));
        const itemHeight = items[0].offsetHeight;
        const visibleItems = Math.ceil(columnHeight / itemHeight);

        // Nur so viele Items duplizieren wie nötig (visibleItems + 2 für smooth transition)
        const neededItems = visibleItems + 2;
        while (items.length < neededItems) {
            const clonedItems = items.slice(0, Math.min(items.length, neededItems - items.length)).map(item => {
                const clone = item.cloneNode(true);
                scrollContent.appendChild(clone);
                return clone;
            });
            items.push(...clonedItems);
        }

        // Alle Videos laden
        scrollContent.querySelectorAll('.video-preview').forEach(preview => {
            if (!loadedContainers.has(preview)) {
                loadVideo(preview);
                loadedContainers.add(preview);
            }
        });

        let currentScroll = 0;
        const speed = 0.2;
        const direction = index % 2 === 0 ? 1 : -1;
        let isPaused = false;
        let animationFrameId = null;

        function animate() {
            if (!isPaused) {
                currentScroll += speed * direction;
                items.forEach(item => {
                    let itemPosition = parseFloat(item.style.transform?.match(/translateY\(([^)]+)\)/)?.[1] || 0);
                    itemPosition -= speed * direction;

                    // Wenn ein Element aus dem Viewport verschwindet
                    if (direction > 0) {
                        if (itemPosition < -itemHeight) {
                            itemPosition = columnHeight;
                        }
                    } else {
                        if (itemPosition > columnHeight) {
                            itemPosition = -itemHeight;
                        }
                    }

                    // Bestehende 3D-Transformationen beibehalten
                    const current3DTransform = item.style.transform?.match(/(perspective.*$)/) || [''];
                    item.style.transform = `translateY(${itemPosition}px) ${current3DTransform[0]}`;
                });
            }
            animationFrameId = requestAnimationFrame(animate);
        }

        // Initiale Positionen setzen
        items.forEach((item, i) => {
            item.style.position = 'absolute';
            item.style.top = '0';
            item.style.left = '0';
            item.style.width = '100%';
            item.style.transform = `translateY(${i * itemHeight}px)`;
        });

        // Hover-Effekte
        column.addEventListener('mouseenter', () => {
            isPaused = true;
        });

        column.addEventListener('mouseleave', () => {
            isPaused = false;
        });

        // 3D-Effekt initialisieren
        init3DEffect(column);

        // Animation starten
        animate();

        // Cleanup für Animation bei Unmount
        column.cleanup = () => {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
            }
        };
    });

    container.dataset.initialized = 'true';
}

    // Intersection Observer für lazy loading
    const gridObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initInfiniteScroll(entry.target);
            }
        });
    }, {
        rootMargin: '50px',
        threshold: 0.1
    });

    // Alle Video-Grids initialisieren
    document.querySelectorAll('.ce_rsce_videogrid').forEach(grid => {
        gridObserver.observe(grid);
    });

    // Cache aufräumen
    function cleanupCache() {
        videoCache.clear();
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cleanupCache();
        }
    });
});