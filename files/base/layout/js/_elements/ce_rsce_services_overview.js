document.addEventListener('DOMContentLoaded', function () {
    const overviewElements = document.querySelectorAll('.services-overview');

    const setContentContainerHeight = (overview) => {
        const serviceList = overview.querySelector('.services-list');
        const contentContainer = overview.querySelector('.service-content-container');
        if (serviceList && contentContainer) {
            // Setze min-height zurück, um die tatsächliche Höhe zu messen
            contentContainer.style.minHeight = '0';
            const listHeight = serviceList.offsetHeight;
            contentContainer.style.minHeight = `${listHeight}px`;
        }
    };

    overviewElements.forEach(overview => {
        const serviceItems = overview.querySelectorAll('.service-item');
        const serviceContents = overview.querySelectorAll('.service-content');
        const disableHover = overview.hasAttribute('data-hover-disabled');
        const eventType = disableHover ? 'click' : 'mouseenter';
        const serviceList = overview.querySelector('.services-list'); // Liste für Observer finden

        // Initial Höhe setzen
        setContentContainerHeight(overview);

        // Höhe bei Fenstergrößenänderung anpassen (Fallback)
        window.addEventListener('resize', () => setContentContainerHeight(overview));

        // ResizeObserver für die Liste (moderner und performanter)
        if (serviceList && window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(() => {
                setContentContainerHeight(overview);
            });
            resizeObserver.observe(serviceList);
        }

        // Initial das erste Element als aktiv markieren (Item und Content)
        const initialItem = overview.querySelector('.service-item[data-index="0"]');
        const initialContent = overview.querySelector('.service-content[data-index="0"]');
        const initialLink = initialItem?.querySelector('.service-link'); // Finde den Link im ersten Item
        if (initialLink && initialContent) {
            // Sicherstellen, dass alle anderen Links nicht aktiv sind
            overview.querySelectorAll('.service-link').forEach(l => l.classList.remove('active'));
            serviceContents.forEach(c => c.classList.remove('active'));
            // Ersten Link und Content aktiv setzen
            initialLink.classList.add('active');
            initialContent.classList.add('active');
        }

        serviceItems.forEach(item => {
            const linkElement = item.querySelector('.service-link'); // Den Link oder das Div finden
            if (!linkElement) return; // Überspringen, wenn kein Link-Element vorhanden ist

            linkElement.addEventListener(eventType, function (event) {
                const parentItem = this.closest('.service-item'); // Das Elternelement 'li' finden
                if (!parentItem) return;

                if (disableHover && event.type === 'click') {
                    // Wenn es ein echter Link ist, verhindern wir die Navigation nur im Klickmodus
                    if (this.tagName === 'A' && this.getAttribute('href')) {
                       event.preventDefault();
                    }
                }

                const index = parentItem.getAttribute('data-index');

                // Aktiven Zustand bei Links umschalten
                overview.querySelectorAll('.service-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                serviceContents.forEach(content => content.classList.remove('active'));

                const activeContent = overview.querySelector(`.service-content[data-index="${index}"]`);
                if (activeContent) {
                    activeContent.classList.add('active');
                    // Trigger animations if needed here (optional)
                    // const animatedElements = activeContent.querySelectorAll('[data-animation]');
                    // animatedElements.forEach(el => {
                    //     el.classList.add('animate__animated', el.dataset.animation);
                    //     el.style.visibility = 'visible'; // Ensure visibility for animation
                    //     el.addEventListener('animationend', () => {
                    //          el.classList.remove('animate__animated', el.dataset.animation);
                    //     }, { once: true });
                    // });
                }
            });
        });
    });
});
