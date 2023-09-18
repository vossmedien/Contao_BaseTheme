document.addEventListener("DOMContentLoaded", function() {
    const locationsSelect = document.getElementById("locations-select");
    const locationItems = document.querySelectorAll(".location-item");

    locationsSelect.addEventListener("change", function() {
        const selectedValue = this.value;
        const selectedClass = selectedValue.replace(/[^a-z0-9]+/ig, '_');


        locationItems.forEach(function(item) {

            item.classList.remove('is-active'); // Erst alle Boxen ausblenden

            if (item.dataset.location.replace(/[^a-z0-9]+/ig, '_') === selectedClass) {
                item.classList.add('is-active'); // Dann die ausgewählte Box anzeigen
            }
        });
    });
});
