export function addPlaceholders() {
    document.querySelectorAll(".widget:not(.widget-upload):not(.widget-radio):not(.widget-checkbox):not(.widget-captcha)").forEach(widget => {
        const inputOrTextareaOrSelect = widget.querySelector("input, textarea, select");

        if (inputOrTextareaOrSelect) {
            let placeholder = inputOrTextareaOrSelect.getAttribute("placeholder");
            let label = widget.querySelector('label') ? widget.querySelector('label').textContent : '';
            const id = inputOrTextareaOrSelect.getAttribute("id");

            label = label.replace('Pflichtfeld ', '');

            // Spezielle Behandlung für select Elemente
            if (inputOrTextareaOrSelect.tagName.toLowerCase() === 'select') {
                if (!inputOrTextareaOrSelect.classList.contains('form-select')) {
                    inputOrTextareaOrSelect.classList.add('form-select');
                }
                
                // Wenn das Label vor dem Select liegt, verschiebe es nach unten
                const existingLabel = widget.querySelector('label');
                if (existingLabel && existingLabel.compareDocumentPosition(inputOrTextareaOrSelect) === Node.DOCUMENT_POSITION_FOLLOWING) {
                    existingLabel.remove();
                    widget.appendChild(existingLabel);
                }
            } else if (!inputOrTextareaOrSelect.classList.contains('form-control')) {
                inputOrTextareaOrSelect.classList.add('form-control');
            }

            if (placeholder) {
                if (widget.querySelector("label")) widget.querySelector("label").remove();
                let parentDiv = inputOrTextareaOrSelect.closest("div:not(.form-floating)");
                if (parentDiv) parentDiv.classList.add("form-floating");
                widget.insertAdjacentHTML("beforeend", "<label for='" + id + "'>" + placeholder + "</label>");
            } else if (label) {
                if (widget.querySelector("label")) widget.querySelector("label").remove();
                let parentDiv = inputOrTextareaOrSelect.closest("div:not(.form-floating)");
                if (parentDiv) parentDiv.classList.add("form-floating");
                widget.insertAdjacentHTML("beforeend", "<label for='" + id + "'>" + label + "</label>");
                inputOrTextareaOrSelect.setAttribute('placeholder', label);
            }
        }
    });
}