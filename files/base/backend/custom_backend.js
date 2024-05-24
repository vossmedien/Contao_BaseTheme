document.addEventListener('click', function (event) {
    var target = event.target;

    while (target && !elementMatches(target, '.rsce_list_inner > .rsce_list_item h2')) {
        if (elementMatches(target, '.rsce_list_toolbar')) {
            return;
        }
        target = target.parentNode;

        if (!target) {
            return;
        }
    }

    if (target) {
        target.parentNode.classList.toggle('active');
    }
});

// Hilfsfunktion zur Überprüfung der Übereinstimmung von Elementselektoren
function elementMatches(element, selector) {
    if (element.matches) {
        return element.matches(selector);
    } else if (element.matchesSelector) {
        return element.matchesSelector(selector);
    } else if (element.msMatchesSelector) {
        return element.msMatchesSelector(selector);
    } else if (element.webkitMatchesSelector) {
        return element.webkitMatchesSelector(selector);
    } else {
        var elements = document.querySelectorAll(selector);
        return Array.prototype.indexOf.call(elements, element) !== -1;
    }
}

// Alle Elemente mit der Klasse .rsce_group auswählen


document.addEventListener('DOMContentLoaded', function () {
    var rsceGroups = document.querySelectorAll('.rsce_group:not(.rsce_group_no_legend), .rsce_list');

// Die Klasse "collapsed" zu jedem Element hinzufügen, wenn sie nicht bereits vorhanden ist
    rsceGroups.forEach(function (group) {
        group.classList.add('collapsed');
        group.classList.add('changed');
    });
});