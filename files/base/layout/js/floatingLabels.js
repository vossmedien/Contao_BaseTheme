// Exportieren der Funktion zur Hinzufügung von Floating Placeholders

export function addPlaceholders() {
    document.querySelectorAll(".widget:not(.widget-upload):not(.widget-select):not(.widget-radio):not(.widget-checkbox):not(.widget-captcha)").forEach(widget => {
        const inputOrTextarea = widget.querySelector("input, textarea");

        // Überprüfen, ob das Element existiert
        if (inputOrTextarea) {
            let placeholder = inputOrTextarea.getAttribute("placeholder");
            let label = widget.querySelector('label') ? widget.querySelector('label').textContent : '';
            const id = inputOrTextarea.getAttribute("id");

            label = label.replace('Pflichtfeld ', '');

            if (!inputOrTextarea.classList.contains('form-control')) {
                inputOrTextarea.classList.add('form-control');
            }

            if (placeholder) {
                   if (widget.querySelector("label")) widget.querySelector("label").remove();
            let parentDiv = inputOrTextarea.closest("div:not(.form-floating)");
            if (parentDiv) parentDiv.classList.add("form-floating");
            widget.insertAdjacentHTML("beforeend", "<label for='" + id + "'>" + placeholder + "</label>");
            } else if (label) {
               if (widget.querySelector("label")) widget.querySelector("label").remove();
            let parentDiv = inputOrTextarea.closest("div:not(.form-floating)");
            if (parentDiv) parentDiv.classList.add("form-floating");
            widget.insertAdjacentHTML("beforeend", "<label for='" + id + "'>" + label + "</label>");
            inputOrTextarea.setAttribute('placeholder', label);
            }
        }
    });
}
