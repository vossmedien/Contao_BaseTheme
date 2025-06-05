let map;
let polygon = null;
let areaLabel = null;
let geocoder;
let infoWindow;

// Tutorial System
let currentTutorialStep = 0;
let tutorialActive = false;
const TUTORIAL_COOKIE = 'caeli_area_check_tutorial_completed';

const tutorialSteps = [
    {
        element: '#place-autocomplete',
        title: 'PLZ eingeben',
        content: 'Geben Sie hier eine Postleitzahl oder einen Ort ein, um zu beginnen.',
        placement: 'right', // desktop
        placementMobile: 'bottom',
        trigger: 'init'
    },
    {
        element: '.pac-container',
        title: 'Ort auswählen',
        content: 'Wählen Sie einen Eintrag aus der Liste aus.',
        placement: 'right',
        placementMobile: 'bottom',
        trigger: 'dropdown'
    },
    {
        element: '.mod_caeli_area_check',
        title: 'Polygon bearbeiten',
        content: 'Ziehen Sie das Polygon oder die Eckpunkte, um die Fläche anzupassen.',
        placement: 'left', // Pfeil zeigt nach rechts
        placementMobile: 'left',
        trigger: 'polygon',
        showButton: true,
        buttonText: 'Weiter'
    },
    {
        element: '#log-coordinates-button',
        title: 'Fläche bestätigen',
        content: 'Klicken Sie hier, um die Prüfung abzuschließen und zum Ergebnis zu gelangen.',
        placement: 'top',
        placementMobile: 'top',
        trigger: 'manual',
        showButton: true,
        buttonText: 'Weiter'
    },
    {
        element: '#delete-button',
        title: 'Neu starten',
        content: 'Hier können Sie die Flächenzeichnung neu beginnen.',
        placement: 'top',
        placementMobile: 'top',
        trigger: 'manual',
        showButton: true,
        buttonText: 'Fertig',
        showBackButton: true
    }
];

function initMap() {
    const mapDiv = document.getElementById("map");
    const mapId = mapDiv.getAttribute('data-map-id') || undefined;

    map = new google.maps.Map(mapDiv, {
        center: { lat: 51.165691, lng: 10.451526 },
        zoom: 15,
        mapTypeId: 'satellite',
        mapId: mapId,
        scrollwheel: true,
        gestureHandling: 'greedy' // Bessere Mobile-Bedienung
    });

    geocoder = new google.maps.Geocoder();
    infoWindow = new google.maps.InfoWindow();

    // Klassisches Place Autocomplete
    const input = document.getElementById('place-autocomplete');
    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['geocode'], // Nur geografische Orte
        componentRestrictions: { country: 'de' }, // Nur Deutschland
        fields: ['place_id', 'formatted_address', 'address_components', 'geometry', 'name']
    });
    autocomplete.bindTo('bounds', map);
    
    // Tutorial Event Listener für Input
    input.addEventListener('focus', handleInputFocus);
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry || (!place.geometry.location && !place.geometry.viewport)) {
            alert('Keine Geodaten für diesen Ort gefunden!');
            return;
        }

        // Einfache PLZ-Validation: 5-stellige PLZ erforderlich
        const inputValue = input.value;
        const plzRegex = /\b\d{5}\b/; // 5 Ziffern

        // Prüfen ob eine vollständige PLZ in der Auswahl enthalten ist
        const hasPLZ = plzRegex.test(inputValue) || plzRegex.test(place.formatted_address || '');

        if (!hasPLZ) {
            document.getElementById('plz-alert').style.display = 'block';
            input.focus();
            return;
        }

        // PLZ gefunden - Alert ausblenden
        document.getElementById('plz-alert').style.display = 'none';

        let targetLocation = place.geometry.location;
        
        // Mobile-Anpassung: Zentrum weiter nach unten verschieben, horizontal zentriert lassen
        if (window.innerWidth <= 768 && targetLocation) {
            const mapBounds = map.getBounds();
            const latSpan = mapBounds.getNorthEast().lat() - mapBounds.getSouthWest().lat();
            const adjustedLat = targetLocation.lat() - (latSpan * 0.4); // 40% nach unten
            targetLocation = new google.maps.LatLng(adjustedLat, targetLocation.lng()); // Longitude bleibt unverändert
        }
        
        if (targetLocation) {
            map.setCenter(targetLocation);
            map.setZoom(15);
        }
        // Kein Popup mehr anzeigen
        infoWindow.close();

        // Nach Ortssuche: Polygon direkt erstellen
        if (targetLocation) {
            createPolygonAtLocation(targetLocation);
            // Buttons nach erfolgreicher PLZ-Eingabe anzeigen
            document.getElementById('button-wrapper').style.display = 'flex';
            
            // Tutorial: Place selected
            handlePlaceSelected();
        }
    });

    // Event Listener für Map-Interaktionen entfernt - nicht mehr benötigt

    // Event Listener für Map-Interaktionen entfernt - nicht mehr benötigt

    document.getElementById('delete-button').addEventListener('click', deletePolygon);
    document.getElementById('log-coordinates-button').addEventListener('click', logCoordinates);
}

function createPolygonAtLocation(center) {
    // Vorheriges Polygon entfernen falls vorhanden
    if (polygon) {
        polygon.setMap(null);
    }
    if (areaLabel) {
        areaLabel.setMap(null);
        areaLabel = null;
    }

    // Standardgröße von 50 Hektar
    const size = 50;
    const earthRadius = 6371000; // in meters
    const areaSideLength = Math.sqrt(size * 10000); // Convert ha to m²
    const latDiff = (areaSideLength / 2) / earthRadius * (180 / Math.PI);
    const lngDiff = latDiff / Math.cos(center.lat() * Math.PI / 180);

    const bounds = {
        north: center.lat() + latDiff,
        south: center.lat() - latDiff,
        east: center.lng() + lngDiff,
        west: center.lng() - lngDiff
    };

    const path = [
        {lat: bounds.north, lng: bounds.west},
        {lat: bounds.north, lng: bounds.east},
        {lat: bounds.south, lng: bounds.east},
        {lat: bounds.south, lng: bounds.west}
    ];

    polygon = new google.maps.Polygon({
        path: path,
        map: map,
        strokeColor: 'yellow',
        strokeOpacity: 1,
        strokeWeight: 3,
        fillColor: '#113634',
        fillOpacity: 0.2,
        editable: true,
        draggable: true,
        // Gestrichelter Rand für Polygon
        strokePattern: [10, 5, 10, 5]
    });

    updateAreaLabel();
    updateGeometryField();

    // Event Listener für Polygon-Änderungen
    google.maps.event.addListener(polygon.getPath(), 'set_at', function() {
        updateAreaLabel();
        updateGeometryField();
    });
    google.maps.event.addListener(polygon.getPath(), 'insert_at', function() {
        updateAreaLabel();
        updateGeometryField();
    });
    
    // Polygon Event Listener entfernt - kein Tracking mehr benötigt
}

function deletePolygon() {
    if (polygon) {
        polygon.setMap(null);
        polygon = null;
    }
    if (areaLabel) {
        areaLabel.setMap(null);
        areaLabel = null;
    }

    document.getElementById('warning').style.display = 'none';
    updateGeometryField();

    // Neues Polygon an aktueller Position anzeigen (nach Neustarten)
    const currentCenter = map.getCenter();
    if (currentCenter) {
        createPolygonAtLocation(currentCenter);
    }
}

function updateAreaLabel() {
    if (areaLabel) {
        areaLabel.map = null;
        areaLabel = null;
    }

    const area = google.maps.geometry.spherical.computeArea(polygon.getPath());
    const areaInHectares = (area / 10000).toFixed(2);

    const bounds = new google.maps.LatLngBounds();
    polygon.getPath().forEach(function(latLng) {
        bounds.extend(latLng);
    });

    const center = bounds.getCenter();

    // AdvancedMarkerElement für Flächenlabel
    const { AdvancedMarkerElement } = google.maps.marker;
    const labelDiv = document.createElement('div');
    labelDiv.style.background = 'white';
    labelDiv.style.borderRadius = '12px';
    labelDiv.style.padding = '2px 8px';
    labelDiv.style.fontWeight = 'bold';
    labelDiv.style.fontSize = '16px';
    labelDiv.style.color = 'black';
    labelDiv.textContent = `${areaInHectares} ha`;

    areaLabel = new AdvancedMarkerElement({
        map: map,
        position: center,
        content: labelDiv
    });

    // Check if area is greater than 700 ha
    if (areaInHectares > 700) {
        document.getElementById('warning').style.display = 'block';
        document.getElementById('log-coordinates-button').disabled = true;
    } else {
        document.getElementById('warning').style.display = 'none';
        document.getElementById('log-coordinates-button').disabled = false;
    }
}

function logCoordinates() {
    if (polygon) {
        // Loading Overlay anzeigen
        showLoadingOverlay();
        
        const coordinates = polygon.getPath().getArray().map(latLng => [latLng.lng(), latLng.lat()]);
        coordinates.push(coordinates[0]); // Polygon schließen
        const geometry = {
            geometry: {
                coordinates: [coordinates],
                type: "Polygon"
            }
        };
        
        // Geometrie in das versteckte Form-Feld schreiben
        document.getElementById('geometry-field').value = JSON.stringify(geometry);
        
        // Suchfeld-Wert ins versteckte Adress-Feld schreiben
        const searchInput = document.getElementById('place-autocomplete');
        const hiddenAddress = document.getElementById('searched-address-field');
        if (searchInput && hiddenAddress) {
            hiddenAddress.value = searchInput.value;
        }
        
        // Loading Animation starten
        startLoadingAnimation();
    } else {
        alert("Bitte zuerst eine Fläche auswählen.");
    }
}

function searchAddressFallback(address) {
    if (!address) return;
    geocoder.geocode({ 'address': address }, function(results, status) {
        if (status === 'OK') {
            map.setCenter(results[0].geometry.location);
            map.setZoom(16);
        } else {
            alert('Geocodierung war aus folgendem Grund nicht erfolgreich: ' + status);
        }
    });
}

function updateGeometryField() {
    if (polygon) {
        const coordinates = polygon.getPath().getArray().map(latLng => [latLng.lng(), latLng.lat()]);
        coordinates.push(coordinates[0]);
        const geometry = {
            geometry: {
                coordinates: [coordinates],
                type: "Polygon"
            }
        };
        document.getElementById('geometry-field').value = JSON.stringify(geometry);
    } else {
        document.getElementById('geometry-field').value = '';
    }
}

const parkForm = document.getElementById('park-form');
if (parkForm) {
    parkForm.addEventListener('submit', function(e) {
        // Wert aus dem Suchfeld holen und ins Hidden-Feld schreiben
        const searchInput = document.getElementById('place-autocomplete');
        const hiddenAddress = document.getElementById('searched-address-field');
        if (searchInput && hiddenAddress) {
            hiddenAddress.value = searchInput.value;
        }
        parkForm.submit();
    });
}

// Consent Management für Google Maps und HubSpot
let consentCheckInitialized = false;
let mapInitialized = false;

function checkConsent() {
    let hasGoogleMapsConsent = false;
    let hasHubSpotConsent = false;

    if (typeof __cmp === 'function') {
        try {
            const cmpData = __cmp('getCMPData');
            if (cmpData && cmpData.vendorConsents) {
                hasGoogleMapsConsent = cmpData.vendorConsents.s1104 || false; // Google Maps
                hasHubSpotConsent = cmpData.vendorConsents.s10 || false; // HubSpot
            }
        } catch (e) {
            console.warn('CMP-Fehler:', e);
        }
    }

    return hasGoogleMapsConsent && hasHubSpotConsent;
}

function showConsentOverlay() {
    document.getElementById('consent-overlay').style.display = 'block';
    document.getElementById('controls').style.display = 'none';
}

function hideConsentOverlay() {
    document.getElementById('consent-overlay').style.display = 'none';
    document.getElementById('controls').style.display = 'block';
}

function activateConsent() {
    if (typeof __cmp === 'function') {
        try {
            __cmp('setVendorConsent', ['s1104', 1]); // Google Maps
            __cmp('setVendorConsent', ['s10', 1]); // HubSpot

            // Kurz warten und dann Maps laden
            setTimeout(() => {
                if (checkConsent()) {
                    hideConsentOverlay();
                    loadGoogleMaps();
                }
            }, 500);
        } catch (e) {
            console.error('Consent-Aktivierung fehlgeschlagen:', e);
        }
    }
}

function loadGoogleMaps() {
    if (mapInitialized) return;

    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initMap();
        mapInitialized = true;
        // Tutorial nach Map-Initialisierung starten
        setTimeout(initTutorial, 1000);
    } else {
        let tries = 0;
        function tryInit() {
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                initMap();
                mapInitialized = true;
                // Tutorial nach Map-Initialisierung starten
                setTimeout(initTutorial, 1000);
            } else if (tries < 20) {
                tries++;
                setTimeout(tryInit, 200);
            } else {
                console.error('Google Maps API konnte nicht geladen werden!');
            }
        }
        tryInit();
    }
}

function initConsentHandling() {
    if (consentCheckInitialized) return;
    consentCheckInitialized = true;

    const consentOverlay = document.getElementById('consent-overlay');
    const activateBtn = document.getElementById('activate-consent-btn');

    if (!consentOverlay || !activateBtn) return;

    activateBtn.addEventListener('click', activateConsent);

    // Consent-Status prüfen
    if (checkConsent()) {
        hideConsentOverlay();
        loadGoogleMaps();
    } else {
        showConsentOverlay();
    }

    // Event Listener für Consent-Änderungen
    if (typeof __cmp === 'function') {
        try {
            __cmp("addEventListener", ["consent", function() {
                if (checkConsent()) {
                    hideConsentOverlay();
                    loadGoogleMaps();
                } else {
                    showConsentOverlay();
                }
            }, false], null);
        } catch (e) {
            console.warn('CMP Event Listener konnte nicht registriert werden:', e);
        }
    }
}

window.addEventListener('load', function() {
    // CMP-Verfügbarkeit prüfen und Consent-Handling initialisieren
    let cmpCheckCount = 0;
    const maxCmpChecks = 50;

    function checkCmpAndInit() {
        cmpCheckCount++;

        if (typeof __cmp === 'function') {
            initConsentHandling();
        } else if (cmpCheckCount >= maxCmpChecks) {
            // Fallback: Wenn kein CMP verfügbar, Maps direkt laden
            console.warn('CMP nicht verfügbar, lade Maps direkt');
            hideConsentOverlay();
            loadGoogleMaps();
        } else {
            setTimeout(checkCmpAndInit, 100);
        }
    }

    checkCmpAndInit();
});

// movePolygonTo entfernt - nicht mehr benötigt, da Polygon direkt neu erstellt wird

function initTutorial() {
    // Prüfen ob Tutorial bereits abgeschlossen
    if (getCookie(TUTORIAL_COOKIE)) {
        return;
    }

    tutorialActive = true;
    currentTutorialStep = 0;
    showTutorialStep(0);
}

function showTutorialStep(stepIndex) {
    if (stepIndex >= tutorialSteps.length) {
        completeTutorial();
        return;
    }

    const step = tutorialSteps[stepIndex];

    // Alle Schritte werden gleich behandelt

    const element = document.querySelector(step.element);

    if (!element) {
        console.warn(`Tutorial element ${step.element} not found`);
        return;
    }

    // Vorherige Popovers schließen
    hideAllPopovers();

    // Responsive Placement
    const isMobile = window.innerWidth <= 768;
    const placement = isMobile ? step.placementMobile : step.placement;

    // Popover Content erstellen
    let content;
    if (stepIndex === 2) {
        // "Polygon bearbeiten" - Template als Function verwenden
        content = function() {
            const template = document.getElementById('tutorial-polygon-content');
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'block';
            return clone;
        };
    } else if (stepIndex === 3) {
        // "Fläche bestätigen" - Template als Function verwenden
        content = function() {
            const template = document.getElementById('tutorial-confirm-content');
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'block';
            return clone;
        };
    } else if (stepIndex === 4) {
        // "Neu starten" - Template als Function verwenden
        content = function() {
            const template = document.getElementById('tutorial-restart-content');
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'block';
            return clone;
        };
    } else {
        // Normale Schritte (PLZ, Dropdown)
        content = `<div class="tutorial-content">${step.content}</div>`;
    }

    // Bootstrap Popover erstellen
    const popoverOptions = {
        title: step.title,
        content: content,
        html: true,
        placement: placement,
        trigger: 'manual',
        container: 'body'
    };

    // Spezielle CSS-Klasse für Polygon-Popover
    if (stepIndex === 2) {
        popoverOptions.customClass = 'tutorial-polygon-popover';
    }

    const popover = new bootstrap.Popover(element, popoverOptions);

    popover.show();

    // Event Listener für Template-Buttons hinzufügen
    setTimeout(() => {
        if (stepIndex === 2) {
            // Polygon-Schritt
            const nextButton = document.querySelector('.popover .tutorial-buttons .btn-primary');
            if (nextButton) {
                nextButton.addEventListener('click', nextTutorialStep);
            }
        } else if (stepIndex === 3) {
            // Bestätigen-Schritt
            const backButton = document.querySelector('.popover .tutorial-buttons .btn-outline-secondary');
            const nextButton = document.querySelector('.popover .tutorial-buttons .btn-primary');
            if (backButton) {
                backButton.addEventListener('click', previousTutorialStep);
            }
            if (nextButton) {
                nextButton.addEventListener('click', nextTutorialStep);
            }
        } else if (stepIndex === 4) {
            // Restart-Schritt
            const backButton = document.querySelector('.popover .tutorial-buttons .btn-outline-secondary');
            const finishButton = document.querySelector('.popover .tutorial-buttons .btn-primary');
            if (backButton) {
                backButton.addEventListener('click', previousTutorialStep);
            }
            if (finishButton) {
                finishButton.addEventListener('click', completeTutorial);
            }
        }
    }, 100);

    // Popover Referenz speichern für cleanup
    element._tutorialPopover = popover;
}

function nextTutorialStep() {
    // Aktuelles Popover schließen
    hideAllPopovers();

    currentTutorialStep++;

    if (currentTutorialStep >= tutorialSteps.length) {
        completeTutorial();
    } else {
        showTutorialStep(currentTutorialStep);
    }
}

function previousTutorialStep() {
    if (currentTutorialStep > 0) {
        // Aktuelles Popover schließen
        hideAllPopovers();

        currentTutorialStep--;
        showTutorialStep(currentTutorialStep);
    }
}

function hideAllPopovers() {
    tutorialSteps.forEach(step => {
        const element = document.querySelector(step.element);
        if (element && element._tutorialPopover) {
            element._tutorialPopover.dispose();
            delete element._tutorialPopover;
        }
    });
}

function completeTutorial() {
    hideAllPopovers();
    tutorialActive = false;
    setCookie(TUTORIAL_COOKIE, 'true', 365); // 1 Jahr gültig
}

function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Tutorial Event Handlers
function handleInputFocus() {
    if (tutorialActive && currentTutorialStep === 0) {
        // Erstes Popover NICHT schließen, nur Dropdown-Popover vorbereiten
        setTimeout(() => {
            const dropdown = document.querySelector('.pac-container');
            if (dropdown && dropdown.style.display !== 'none') {
                showTutorialStep(1);
                currentTutorialStep = 1;
            }
        }, 500);
    }
}

function handlePlaceSelected() {
    if (tutorialActive && currentTutorialStep <= 1) {
        // Dropdown-Popover schließen und Polygon-Popover zeigen
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(2);
            currentTutorialStep = 2;
        }, 1000);
    }
}

function findPolygonHandle() {
    // Suche nach Polygon-Handles (Google Maps generiert diese dynamisch)
    const handles = document.querySelectorAll('#map div[style*="width: 9px"][style*="height: 9px"]');
    return handles.length > 0 ? handles[0] : null;
}

// Polygon Tutorial Funktion entfernt - wird jetzt über normale showTutorialStep() abgewickelt

// Loading System
function showLoadingOverlay() {
    document.getElementById('loading-overlay').style.display = 'flex';
}

function hideLoadingOverlay() {
    document.getElementById('loading-overlay').style.display = 'none';
}

function updateLoadingProgress(percentage) {
    // Prozentanzeige aktualisieren
    document.querySelector('.loading-percentage').textContent = `${percentage} %`;
    
    // SVG Balken einfärben - Paths sind bereits in korrekter Reihenfolge (12 Uhr bis 11 Uhr)
    const totalBars = 12;
    const activeBars = Math.floor((percentage / 100) * totalBars);
    
    const bars = document.querySelectorAll('.spinner-bar');
    bars.forEach((bar, index) => {
        if (index < activeBars) {
            bar.setAttribute('fill', '#113534'); // Dunkelgrün = geladen
        } else {
            bar.setAttribute('fill', '#DEEEC6'); // Hellgrün = nicht geladen
        }
    });
}

function startLoadingAnimation() {
    let progress = 0;
    let loadingInterval;
    
    // Animation parallel starten - langsamer und realistischer
    loadingInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 3 + 1; // 1-4% pro Update (viel langsamer)
            updateLoadingProgress(Math.floor(progress));
        } else {
            progress = 90;
            updateLoadingProgress(90);
            clearInterval(loadingInterval);
        }
    }, 150); // Alle 150ms
    
    // Request mit fetch machen um Redirect zu kontrollieren
    const parkForm = document.getElementById('park-form');
    const formData = new FormData(parkForm);
    
    fetch(parkForm.action, {
        method: 'POST',
        body: formData
    }).then(response => {
        // Animation auf 100% setzen
        updateLoadingProgress(100);
        
        // Kurz warten damit 100% sichtbar ist
        setTimeout(() => {
            // Redirect zur Ergebnisseite
            window.location.href = response.url;
        }, 500);
    }).catch(error => {
        console.error('Request failed:', error);
        // Bei Fehler trotzdem Form normal submitten
        setTimeout(() => {
            submitFormWithRedirect();
        }, 500);
    });
}

function submitFormWithRedirect() {
    // Form direkt submitten (führt zu Redirect)
    const parkForm = document.getElementById('park-form');
    if (parkForm) {
        parkForm.submit();
    }
}

// Globale Funktionen für Button-Callbacks
window.nextTutorialStep = nextTutorialStep;
window.previousTutorialStep = previousTutorialStep;
