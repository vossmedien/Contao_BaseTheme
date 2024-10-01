document.addEventListener('DOMContentLoaded', function () {
    const stoerers = document.querySelectorAll('.ce--stoerer.is-expandable');

    function handleClick(event) {
        if (window.innerWidth < 768) {
            const stoerer = event.currentTarget.closest('.ce--stoerer');
            if (!stoerer.classList.contains('clicked')) {
                event.preventDefault();
                removeClickedClass();
                stoerer.classList.add('clicked');
            }
        }
    }

    function removeClickedClass() {
        document.querySelectorAll('.ce--stoerer.clicked').forEach(el => el.classList.remove('clicked'));
    }

    stoerers.forEach(stoerer => {
        const link = stoerer.querySelector('a');
        if (link) {
            link.addEventListener('click', handleClick);
        }
    });

    // Klick außerhalb schließt den geöffneten Störer
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.ce--stoerer') && window.innerWidth < 768) {
            removeClickedClass();
        }
    });
});