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