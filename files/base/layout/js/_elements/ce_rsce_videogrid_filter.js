document.addEventListener('DOMContentLoaded', function () {
    // Finde alle Filter-Gruppen
    document.querySelectorAll('.filter-bubbles').forEach((filterGroup, groupIndex) => {
        // Suche das nächste Grid für diese Filter-Gruppe
        let nextGrid = filterGroup.closest('.content--element');
        while (nextGrid && !nextGrid.nextElementSibling?.querySelector('.ce_rsce_videogrid')) {
            nextGrid = nextGrid.nextElementSibling;
        }
        const targetGrid = nextGrid?.nextElementSibling?.querySelector('.ce_rsce_videogrid');
        if (!targetGrid) return;

        const filterBubbles = filterGroup.querySelectorAll('.btn');
        const filterSelect = filterGroup.closest('.container').querySelector('.filter-select');
        const allButton = filterGroup.querySelector('[data-filter="all"]');
        let activeFilters = new Set();

        // Helper function to check if we're on mobile
        const isMobile = () => window.innerWidth < 992;

        function filterItems(activeFilters) {
            const items = targetGrid.querySelectorAll('.video-item');
            items.forEach(item => {
                const parentSlide = item.closest('[data-schwerpunkte]');
                const schwerpunkte = parentSlide?.dataset.schwerpunkte?.split(' ') || [];
                const shouldShow = activeFilters.size === 0 ||
                    schwerpunkte.some(s => activeFilters.has(s));

                if (isMobile()) {
                    item.classList.toggle('d-none', !shouldShow);
                } else {
                    item.classList.toggle('filtered-hidden', !shouldShow);
                }
            });

            // Aktualisiere die Scroll-Container
            if (!isMobile()) {
                targetGrid.querySelectorAll('.scroll-wrapper').forEach(wrapper => {
                    const visibleItems = wrapper.querySelectorAll('.video-item:not(.filtered-hidden)');
                    wrapper.classList.toggle('empty-column', visibleItems.length === 0);
                });
            }
        }

        // Desktop Filter Bubbles
        filterBubbles.forEach(bubble => {
            bubble.addEventListener('click', function () {
                const filter = this.dataset.filter;

                if (filter === 'all') {
                    activeFilters.clear();
                    filterBubbles.forEach(b => {
                        b.classList.remove('btn-primary');
                        b.classList.add('btn-outline-white');
                    });
                    allButton.classList.remove('btn-outline-white');
                    allButton.classList.add('btn-primary');
                    if (filterSelect) filterSelect.value = 'all';
                } else {
                    if (this.classList.contains('btn-outline-white')) {
                        this.classList.remove('btn-outline-white');
                        this.classList.add('btn-primary');
                        activeFilters.add(filter);
                    } else {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline-white');
                        activeFilters.delete(filter);
                    }

                    if (activeFilters.size === 0) {
                        allButton.classList.remove('btn-outline-white');
                        allButton.classList.add('btn-primary');
                        if (filterSelect) filterSelect.value = 'all';
                    } else {
                        allButton.classList.remove('btn-primary');
                        allButton.classList.add('btn-outline-white');
                        if (filterSelect && activeFilters.size === 1) {
                            filterSelect.value = Array.from(activeFilters)[0];
                        }
                    }
                }

                filterItems(activeFilters);
            });
        });

        // Mobile Select
        if (filterSelect) {
            filterSelect.addEventListener('change', function () {
                const selectedFilter = this.value;
                activeFilters.clear();

                if (selectedFilter !== 'all') {
                    activeFilters.add(selectedFilter);
                }

                filterBubbles.forEach(bubble => {
                    const filter = bubble.dataset.filter;
                    if (filter === 'all') {
                        bubble.classList.toggle('btn-primary', selectedFilter === 'all');
                        bubble.classList.toggle('btn-outline-white', selectedFilter !== 'all');
                    } else {
                        bubble.classList.toggle('btn-primary', filter === selectedFilter);
                        bubble.classList.toggle('btn-outline-white', filter !== selectedFilter);
                    }
                });

                filterItems(activeFilters);
            });
        }

        // Handle resize events
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                filterItems(activeFilters);
            }, 250);
        });
    });
});