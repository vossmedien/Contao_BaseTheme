document.addEventListener('DOMContentLoaded', function () {
    const stoererLinks = document.querySelectorAll('.ce--stoerer.is-expandable a');

    function handleClick(event) {
        const link = event.currentTarget;
        const stoerer = link.closest('.ce--stoerer');

        if (window.innerWidth < 768) {
            if (!stoerer.classList.contains('clicked')) {
                event.preventDefault();
                removeClickedClass();
                stoerer.classList.add('clicked');
            }
        }
    }

    function removeClickedClass() {
        const clickedStoerer = document.querySelector('.ce--stoerer.clicked');
        if (clickedStoerer) {
            clickedStoerer.classList.remove('clicked');
        }
    }

    stoererLinks.forEach(function (link) {
        link.addEventListener('click', handleClick);
    });

    document.addEventListener('click', function (event) {
        const clickedElement = event.target;
        const clickedStoerer = clickedElement.closest('.ce--stoerer');

        if (!clickedStoerer && window.innerWidth < 768) {
            removeClickedClass();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const stoerers = document.querySelectorAll('.ce--stoerer.is-expandable');

    function calculateAndSetExpandedWidth(stoerer) {
        // Temporär die max-width entfernen, um die volle Breite zu messen
        const originalMaxWidth = stoerer.style.maxWidth;
        stoerer.style.maxWidth = 'none';

        const fullWidth = stoerer.offsetWidth;

        // max-width zurücksetzen
        stoerer.style.maxWidth = originalMaxWidth;

        // Setze die berechnete Breite als CSS-Variable
        stoerer.style.setProperty('--expanded-width', `${fullWidth}px`);
    }

    stoerers.forEach(calculateAndSetExpandedWidth);

    // Neuberechnung der Breiten bei Fenstergrößenänderung
    window.addEventListener('resize', function() {
        stoerers.forEach(calculateAndSetExpandedWidth);
    });
});