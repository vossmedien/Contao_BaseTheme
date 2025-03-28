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


document.addEventListener('DOMContentLoaded', function () {
    // Alle Eingabefelder finden und die 'filled' Klasse initial setzen
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(updateFilledClass);

    // Event Listener für Änderungen an den Eingabefeldern
    allInputs.forEach(input => {
        input.addEventListener('change', function () {
            updateFilledClass(this);
        });
        // Für Textfelder und Textareas auch auf 'input' Event hören
        if (input.type === 'text' || input.tagName === 'TEXTAREA') {
            input.addEventListener('input', function () {
                updateFilledClass(this);
            });
        }
    });

    // Alle .rsce_list_item Elemente durchgehen
    document.querySelectorAll('.rsce_list_item').forEach(function (item) {
        let hasFilledFields = false;

        // Alle Eingabefelder innerhalb des .rsce_list_item überprüfen
        item.querySelectorAll('input, select, textarea').forEach(function (input) {
            if (isElementFilled(input)) {
                hasFilledFields = true;
            }
        });

        // Klasse 'active' hinzufügen, wenn ausgefüllte Felder gefunden wurden
        if (hasFilledFields) {
            //item.classList.add('active');
        }
    });

    var saveButton = document.getElementById('save');
    if (saveButton) {
        saveButton.addEventListener('click', function (e) {
            e.preventDefault(); // Verhindert den Standard-Button-Klick
            var form = document.querySelector('form.tl_edit_form');
            if (form) {
                form.submit();
            }
        });
    }
});


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

/*
// Handler für Duplizieren- und Neu-Buttons
document.addEventListener('click', function (event) {
    var target = event.target.closest('.rsce_list_toolbar_duplicate, .rsce_list_toolbar_new, .rsce_list_toolbar_delete');
    if (target) {
        event.preventDefault();


        // Warten Sie kurz, um sicherzustellen, dass die RSCE-Aktion abgeschlossen ist
        setTimeout(function () {
            var form = document.querySelector('form#tl_content');
            if (form) {
                form.submit();
            }
        }, 200);
    }
});

 */

// Funktion zum Verschieben des Submit-Bereichs
function moveSubmitElement() {
    const submitElement = document.querySelector('.tl_formbody_submit');
    const contentElement = document.querySelector('#tl_content');

    if (submitElement && contentElement && submitElement.parentNode !== contentElement) {
        contentElement.appendChild(submitElement);
    }
}

// Initialer Aufruf
document.addEventListener('turbo:load', function initialSetup() {
    moveSubmitElement();

    // MutationObserver für dynamische Änderungen
    const observer = new MutationObserver(function(mutations) {
        moveSubmitElement();
    });

    // Beobachte Änderungen im DOM
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Event-Listener entfernen nach erstem Aufruf
    document.removeEventListener('turbo:load', initialSetup);
});