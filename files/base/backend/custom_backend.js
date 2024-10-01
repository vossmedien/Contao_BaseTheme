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


/*
document.addEventListener('DOMContentLoaded', function () {
    var rsceGroups = document.querySelectorAll('.rsce_group:not(.rsce_group_no_legend), .rsce_list, .tl_box:not(.rsce_group)');

    rsceGroups.forEach(function (group) {
        //group.classList.add('collapsed');
        group.classList.add('changed');
    });
});
 */




document.addEventListener('DOMContentLoaded', function() {

    // Funktion zur Überprüfung, ob ein Element ausgefüllt ist
    function isElementFilled(element) {
        if (element.type === 'checkbox' || element.type === 'radio') {
            return element.checked;
        } else if (element.tagName === 'SELECT') {
            return element.value !== '';
        } else {
            return element.value.trim() !== '';
        }
    }

    // Funktion zum Hinzufügen oder Entfernen der 'filled' Klasse
    function updateFilledClass(element) {
        if (isElementFilled(element)) {
            element.classList.add('filled');
        } else {
            element.classList.remove('filled');
        }
    }

    // Alle Eingabefelder finden und die 'filled' Klasse initial setzen
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(updateFilledClass);

    // Event Listener für Änderungen an den Eingabefeldern
    allInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateFilledClass(this);
        });
        // Für Textfelder und Textareas auch auf 'input' Event hören
        if (input.type === 'text' || input.tagName === 'TEXTAREA') {
            input.addEventListener('input', function() {
                updateFilledClass(this);
            });
        }
    });

    // Alle .rsce_list_item Elemente durchgehen
    document.querySelectorAll('.rsce_list_item').forEach(function(item) {
        let hasFilledFields = false;

        // Alle Eingabefelder innerhalb des .rsce_list_item überprüfen
        item.querySelectorAll('input, select, textarea').forEach(function(input) {
            if (isElementFilled(input)) {
                hasFilledFields = true;
            }
        });

        // Klasse 'active' hinzufügen, wenn ausgefüllte Felder gefunden wurden
        if (hasFilledFields) {
            item.classList.add('active');
        }
    });

    /*
     // Alle .rsce_group Elemente durchgehen
    document.querySelectorAll('.rsce_group ,.tl_box:not(.rsce_list_stop)').forEach(function(group) {
        let hasFilledFields = false;

        // Alle Eingabefelder innerhalb der .rsce_group überprüfen
        group.querySelectorAll('input, select, textarea').forEach(function(input) {
            if (isElementFilled(input)) {
                hasFilledFields = true;
            }
        });

        // Klasse 'collapsed' hinzufügen, wenn keine ausgefüllten Felder gefunden wurden
        if (!hasFilledFields) {
            group.classList.add('collapsed');
        }
    });
     */

});
