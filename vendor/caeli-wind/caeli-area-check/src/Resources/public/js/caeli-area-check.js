let map;
let polygon = null;
let areaLabel = null;
let geocoder;
let infoWindow;

// Polygon-Offset tracking für Map-Bewegung
let polygonRelativeOffset = null;
let isPolygonBeingEdited = false;

// Tutorial System
let currentTutorialStep = 0;
let tutorialActive = false;
const TUTORIAL_COOKIE = 'caeli_area_check_tutorial_completed';

// Tutorial Steps werden dynamisch mit Übersetzungen erstellt
function getTutorialSteps() {
    const translations = window.CaeliAreaCheckTranslations || {};
    const tutorial = translations.tutorial || {};
    
    return [
        {
            // Schritt 0: Willkommen (Initial Popover) - an der Controls-Box ausrichten
            element: '#controls',
            title: tutorial.welcome?.title || 'Willkommen bei Ihrem Flächencheck.',
            content: tutorial.welcome?.content || 'Entdecken Sie in wenigen Schritten das Windpotenzial Ihres Grundstücks. Wir zeigen Ihnen kurz, wie es funktioniert. Einfach auf "Weiter" klicken.',
            placement: 'left',
            placementMobile: 'bottom',
            trigger: 'init',
            template: 'welcome',
            showSkipButton: true
        },
        {
            // Schritt 1: PLZ/Ort eingeben
            element: '#place-autocomplete',
            title: tutorial.plz_input?.title || 'Schritt 1: Ihr Standort zählt.',
            content: tutorial.plz_input?.content || 'Starten Sie, indem Sie Ihren Ort oder die Postleitzahl eingeben. So finden wir den richtigen Kartenausschnitt für Ihre Fläche.',
            placement: 'right',
            placementMobile: 'bottom',
            trigger: 'manual',
            template: 'plz',
            showBackButton: true
        },
        {
            // Schritt 2: Polygon zeichnen - am rechten Bildschirmrand
            element: '#controls',
            title: tutorial.polygon_edit?.title || 'Schritt 2: Fläche einzeichnen.',
            content: tutorial.polygon_edit?.content || 'Jetzt kommt der spannende Teil: Klicken Sie die Eckpunkte Ihrer Fläche nacheinander an. So zeichnen Sie präzise Ihr Grundstück auf der Karte ein.',
            placement: 'left',
            placementMobile: 'bottom',
            trigger: 'polygon',
            template: 'polygon',
            showBackButton: true
        },
        {
            // Schritt 3: Bestätigen/Fertig
            element: '#log-coordinates-button',
            title: tutorial.area_confirm?.title || 'Schritt 3: Fast geschafft!',
            content: tutorial.area_confirm?.content || 'Fast geschafft: Bestätigen Sie Ihre Eingabe mit einem Doppelklick auf den letzten Punkt oder klicken Sie auf "Ergebnis anzeigen". Wir prüfen in Windeseile die Bedingungen auf Ihrer Fläche und Sie erhalten unmittelbar das Ergebnis Ihres Flächenchecks.',
            placement: 'top',
            placementMobile: 'top',
            trigger: 'manual',
            template: 'confirm',
            showBackButton: true
        }
    ];
}

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
    
    // Autocomplete-Instanz global speichern für späteren Zugriff
    window.autocompleteInstance = autocomplete;
    
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry || (!place.geometry.location && !place.geometry.viewport)) {
            const translations = window.CaeliAreaCheckTranslations || {};
            showDynamicAlert('no_geodata', translations);
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
        hideDynamicAlert();

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

    // Map-Event-Listener für Polygon-Mitbewegen
    google.maps.event.addListener(map, 'center_changed', function() {
        if (polygon && polygonRelativeOffset && !isPolygonBeingEdited) {
            movePolygonWithMap();
        }
    });

    document.getElementById('delete-button').addEventListener('click', deletePolygon);
    document.getElementById('log-coordinates-button').addEventListener('click', logCoordinates);
    
    // Prüfe beim Seitenload, ob das Input bereits einen Wert hat
    checkInputValueOnLoad();
}

function checkInputValueOnLoad() {
    const input = document.getElementById('place-autocomplete');
    if (input && input.value && input.value.trim() !== '') {
        // Führe direkt das Geocoding aus für bessere UX
        geocodeInputValue(input.value.trim());
        
        // Mache das Input bereit für weitere Eingaben
        prepareInputForInteraction(input);
    }
}

function prepareInputForInteraction(input) {
    // Verzögere den Focus, damit Google Maps und Autocomplete vollständig geladen sind
    setTimeout(() => {
        // Input fokussieren und Cursor ans Ende setzen
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
        
        // Bei der nächsten Eingabe das Autocomplete triggern
        const triggerOnNextInput = function(event) {
            // Entferne den Event Listener nach der ersten Eingabe
            input.removeEventListener('input', triggerOnNextInput);
            
            // Trigger das normale Autocomplete-Verhalten
            setTimeout(() => {
                const inputEvent = new Event('input', { bubbles: true });
                input.dispatchEvent(inputEvent);
            }, 50);
        };
        
        input.addEventListener('input', triggerOnNextInput);
        
        // Visueller Hinweis mit Animation
        input.style.transition = 'box-shadow 0.3s ease';
        input.style.boxShadow = '0 0 0 2px rgba(17, 53, 52, 0.3)';
        
        setTimeout(() => {
            input.style.boxShadow = '';
        }, 2000);
    }, 1500); // 1.5 Sekunden Verzögerung für vollständige Initialisierung
}

function geocodeInputValue(address) {
    if (!geocoder) return;
    
    // PLZ-Validation
    const plzRegex = /\b\d{5}\b/;
    if (!plzRegex.test(address)) {
        document.getElementById('plz-alert').style.display = 'block';
        return;
    }
    
    geocoder.geocode({
        address: address,
        componentRestrictions: { country: 'de' }
    }, function(results, status) {
        if (status === 'OK' && results[0]) {
            const place = results[0];
            
            // PLZ nochmals prüfen in der Adresse
            const hasPLZ = plzRegex.test(address) || plzRegex.test(place.formatted_address || '');
            if (!hasPLZ) {
                document.getElementById('plz-alert').style.display = 'block';
                return;
            }
            
            // PLZ gefunden - Alerts ausblenden
            document.getElementById('plz-alert').style.display = 'none';
            hideDynamicAlert();
            
            let targetLocation = place.geometry.location;
            
            // Mobile-Anpassung: Zentrum weiter nach unten verschieben
            if (window.innerWidth <= 768 && targetLocation) {
                const mapBounds = map.getBounds();
                const latSpan = mapBounds.getNorthEast().lat() - mapBounds.getSouthWest().lat();
                const adjustedLat = targetLocation.lat() - (latSpan * 0.4);
                targetLocation = new google.maps.LatLng(adjustedLat, targetLocation.lng());
            }
            
            if (targetLocation) {
                map.setCenter(targetLocation);
                map.setZoom(15);
                createPolygonAtLocation(targetLocation);
                
                // Buttons anzeigen
                document.getElementById('button-wrapper').style.display = 'flex';
                
                // Tutorial: Place selected
                handlePlaceSelected();
            }
        } else {
            console.warn('Geocoding fehlgeschlagen für:', address, status);
        }
    });
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

    // Standardgröße von 40 Hektar
    const size = 40;
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

    // Relativen Offset zum Map-Center speichern
    polygonRelativeOffset = {
        lat: 0, // Polygon ist zentriert
        lng: 0
    };

    updateAreaLabel();
    updateGeometryField();

    // Event Listener für Polygon-Änderungen
    google.maps.event.addListener(polygon.getPath(), 'set_at', function() {
        // Nur NICHT reagieren wenn gerade movePolygonWithMap läuft
        if (!isPolygonBeingEdited) {
            updatePolygonOffset();
            updateAreaLabel();
            updateGeometryField();
        }
    });
    google.maps.event.addListener(polygon.getPath(), 'insert_at', function() {
        // Nur NICHT reagieren wenn gerade movePolygonWithMap läuft
        if (!isPolygonBeingEdited) {
            updatePolygonOffset();
            updateAreaLabel();
            updateGeometryField();
        }
    });
    
    // Event Listener für Drag-Operationen
    google.maps.event.addListener(polygon, 'dragstart', function() {
        isPolygonBeingEdited = true;
    });
    google.maps.event.addListener(polygon, 'dragend', function() {
        updatePolygonOffset();
        updateAreaLabel();
        updateGeometryField();
        isPolygonBeingEdited = false;
    });

    // Tutorial: Polygon wurde erstellt
    handlePolygonCreated();
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

    // Polygon-Offset zurücksetzen
    polygonRelativeOffset = null;
    isPolygonBeingEdited = false;

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
        // Verhindere doppelte Submissions
        if (formSubmissionInProgress) {
            return;
        }
        formSubmissionInProgress = true;
        
        // Alle Popovers schließen vor dem Absenden
        hideAllPopovers();
        
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
        const translations = window.CaeliAreaCheckTranslations || {};
        showDynamicAlert('select_area_first', translations);
    }
}

function searchAddressFallback(address) {
    if (!address) return;
    geocoder.geocode({ 'address': address }, function(results, status) {
        if (status === 'OK') {
            map.setCenter(results[0].geometry.location);
            map.setZoom(16);
        } else {
            const translations = window.CaeliAreaCheckTranslations || {};
            showDynamicAlert('geocoding_failed', translations, status);
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

// Hilfsfunktionen für Polygon-Mitbewegen
function updatePolygonOffset() {
    if (!polygon || !map) return;
    
    // Aktuelles Polygon-Center berechnen
    const bounds = new google.maps.LatLngBounds();
    polygon.getPath().forEach(function(latLng) {
        bounds.extend(latLng);
    });
    const polygonCenter = bounds.getCenter();
    const mapCenter = map.getCenter();
    
    // Relativen Offset speichern
    polygonRelativeOffset = {
        lat: polygonCenter.lat() - mapCenter.lat(),
        lng: polygonCenter.lng() - mapCenter.lng()
    };
}

function movePolygonWithMap() {
    if (!polygon || !polygonRelativeOffset || !map) return;
    
    // Flag setzen, um Event-Listener während dem Move zu deaktivieren
    isPolygonBeingEdited = true;
    
    const mapCenter = map.getCenter();
    const targetCenter = {
        lat: mapCenter.lat() + polygonRelativeOffset.lat,
        lng: mapCenter.lng() + polygonRelativeOffset.lng
    };
    
    // Aktuelles Polygon-Center berechnen
    const bounds = new google.maps.LatLngBounds();
    polygon.getPath().forEach(function(latLng) {
        bounds.extend(latLng);
    });
    const currentCenter = bounds.getCenter();
    
    // Bewegungsvektor berechnen
    const deltaLat = targetCenter.lat - currentCenter.lat();
    const deltaLng = targetCenter.lng - currentCenter.lng();
    
    // Alle Polygon-Punkte verschieben
    const path = polygon.getPath();
    const newPath = [];
    for (let i = 0; i < path.getLength(); i++) {
        const point = path.getAt(i);
        newPath.push(new google.maps.LatLng(
            point.lat() + deltaLat,
            point.lng() + deltaLng
        ));
    }
    
    polygon.setPath(newPath);
    
    // Nur EINMAL Area-Label und Geometry-Field updaten
    updateAreaLabel();
    updateGeometryField();
    
    // Flag zurücksetzen - kurz verzögert, damit set_at Events nicht mehr reagieren
    setTimeout(() => { 
        isPolygonBeingEdited = false; 
    }, 100);
}

let formSubmissionInProgress = false;

const parkForm = document.getElementById('park-form');
if (parkForm) {
    parkForm.addEventListener('submit', function(e) {
        // Verhindere doppelte Submissions
        if (formSubmissionInProgress) {
            e.preventDefault();
            return;
        }
        
        e.preventDefault(); // Form-Submit verhindern
        
        // Wert aus dem Suchfeld holen und ins Hidden-Feld schreiben
        const searchInput = document.getElementById('place-autocomplete');
        const hiddenAddress = document.getElementById('searched-address-field');
        if (searchInput && hiddenAddress) {
            hiddenAddress.value = searchInput.value;
        }
        
        // logCoordinates() aufrufen statt direktes Submit
        logCoordinates();
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
                const translations = window.CaeliAreaCheckTranslations || {};
                showDynamicAlert('google_maps_loading', translations);
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
    const tutorialSteps = getTutorialSteps();
    if (stepIndex >= tutorialSteps.length) {
        completeTutorial();
        return;
    }

    const step = tutorialSteps[stepIndex];
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

    // Popover Content basierend auf Template erstellen
    let content;
    
    if (step.template) {
        // Template als Function verwenden
        content = function() {
            const template = document.getElementById(`tutorial-${step.template}-content`);
            if (!template) {
                console.warn(`Tutorial template tutorial-${step.template}-content not found`);
                return `<div class="tutorial-content">${step.content}</div>`;
            }
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'block';
            return clone;
        };
    } else {
        // Fallback ohne Template
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

    const popover = new bootstrap.Popover(element, popoverOptions);
    popover.show();

    // Event Listener für Template-Buttons hinzufügen
    setTimeout(() => {
        const popoverElement = document.querySelector('.popover');
        if (!popoverElement) return;

        // Event Listener basierend auf Step-Template
        if (step.template === 'welcome') {
            // Willkommen-Schritt (0)
            const skipButton = popoverElement.querySelector('#tutorial-welcome-skip');
            const nextButton = popoverElement.querySelector('#tutorial-welcome-next');
            
            if (skipButton) {
                skipButton.addEventListener('click', completeTutorial);
            }
            if (nextButton) {
                nextButton.addEventListener('click', nextTutorialStep);
            }
        } else if (step.template === 'plz') {
            // PLZ-Schritt (1) - nur Zurück-Button, automatische Weiterleitung
            const backButton = popoverElement.querySelector('#tutorial-plz-back');
            
            if (backButton) {
                backButton.addEventListener('click', previousTutorialStep);
            }
        } else if (step.template === 'polygon') {
            // Polygon-Schritt (2)
            const backButton = popoverElement.querySelector('#tutorial-polygon-back');
            const nextButton = popoverElement.querySelector('#tutorial-polygon-next');
            
            if (backButton) {
                backButton.addEventListener('click', previousTutorialStep);
            }
            if (nextButton) {
                nextButton.addEventListener('click', nextTutorialStep);
            }
        } else if (step.template === 'confirm') {
            // Bestätigen-Schritt (3)
            const backButton = popoverElement.querySelector('#tutorial-confirm-back');
            const nextButton = popoverElement.querySelector('#tutorial-confirm-next');
            
            if (backButton) {
                backButton.addEventListener('click', previousTutorialStep);
            }
            if (nextButton) {
                nextButton.addEventListener('click', completeTutorial);
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
    const tutorialSteps = getTutorialSteps();

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
    const tutorialSteps = getTutorialSteps();
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
        // Vom Willkommen-Schritt zum PLZ-Schritt wechseln
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(1);
            currentTutorialStep = 1;
        }, 300);
    }
}

function handlePlaceSelected() {
    if (tutorialActive && currentTutorialStep === 1) {
        // Vom PLZ-Schritt zum Polygon-Schritt wechseln
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(2);
            currentTutorialStep = 2;
        }, 1000);
    }
}

function handlePolygonCreated() {
    if (tutorialActive && currentTutorialStep === 2) {
        // Vom Polygon-Schritt zum Bestätigen-Schritt wechseln
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(3);
            currentTutorialStep = 3;
        }, 500);
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

function initLoadingSpinner() {
    // Alle SVG Balken dunkelgrün machen
    const bars = document.querySelectorAll('.spinner-bar');
    bars.forEach((bar) => {
        bar.setAttribute('fill', '#113534'); // Alle dunkelgrün
    });
    
    // Rotation CSS hinzufügen falls nicht vorhanden
    const spinner = document.querySelector('.loading-spinner svg');
    if (spinner) {
        spinner.style.animation = 'spin 1.5s linear infinite';
    }
}

function updateLoadingText(text) {
    const textElement = document.querySelector('.loading-percentage');
    if (textElement) {
        textElement.textContent = text;
    }
}

function startLoadingAnimation() {
    // Übersetzungen laden, Fallback auf Deutsch falls nicht verfügbar
    const translations = window.CaeliAreaCheckTranslations || {};
    const loadingTextsObj = translations.loading?.texts || {};
    
    // Die Übersetzungen sind als Objekt definiert, konvertiere zu Array
    const loadingTexts = [
        loadingTextsObj.checking_area || "Wir prüfen Ihre Fläche",
        loadingTextsObj.wind_conditions || "Passen die Windgegebenheiten?",
        loadingTextsObj.restrictions_check || "Gibt es Restriktionen?",
        loadingTextsObj.grid_connection || "Ist ein Netzanschluss gegeben?",
        loadingTextsObj.analyzing_potential || "Analysiere Windpotential",
        loadingTextsObj.checking_nature || "Prüfe Naturschutzgebiete",
        loadingTextsObj.calculating_economics || "Berechne Wirtschaftlichkeit",
        loadingTextsObj.checking_distances || "Überprüfe Abstandsregelungen",
        loadingTextsObj.analyzing_capacity || "Analysiere Netzkapazität",
        loadingTextsObj.evaluating_quality || "Bewerte Standortqualität"
    ];
    
    let currentTextIndex = 0;
    let textInterval;
    
    // Spinner initialisieren
    initLoadingSpinner();
    
    const textElement = document.querySelector('.loading-percentage');
    if (textElement) {
        // Erstes animiertes Einblenden
        textElement.textContent = loadingTexts[currentTextIndex];
        textElement.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        textElement.style.transform = 'translateY(30px)';
        textElement.style.opacity = '0';
        
        // Ersten Text animiert einblenden
        setTimeout(() => {
            textElement.style.transform = 'translateY(0)';
            textElement.style.opacity = '1';
        }, 100);
    }
    
    // Formular sofort abschicken
    setTimeout(() => {
        const parkForm = document.getElementById('park-form');
        if (parkForm) {
            parkForm.submit();
        }
    }, 300);
    
    // Text-Rotation starten nach dem ersten Einblenden
    setTimeout(() => {
        textInterval = setInterval(() => {
            if (textElement) {
                // Smooth fade out nach oben
                textElement.style.transition = 'transform 0.6s cubic-bezier(0.55, 0.06, 0.68, 0.19), opacity 0.6s cubic-bezier(0.55, 0.06, 0.68, 0.19)';
                textElement.style.transform = 'translateY(30px)';
                textElement.style.opacity = '0';
                
                setTimeout(() => {
                    // Nächsten Text setzen
                    currentTextIndex = (currentTextIndex + 1) % loadingTexts.length;
                    textElement.textContent = loadingTexts[currentTextIndex];
                    
                    // Von unten einblenden - sofort positionieren
                    textElement.style.transform = 'translateY(30px)';
                    textElement.style.opacity = '0';
                    
                    // Smooth fade in
                    setTimeout(() => {
                        textElement.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                        textElement.style.transform = 'translateY(0)';
                        textElement.style.opacity = '1';
                    }, 50);
                }, 600);
            }
        }, 2500);
         }, 800); // Erste Rotation nach 0.8s
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

// Funktionen für dynamische Alert-Box
function showDynamicAlert(alertKey, translations, additionalInfo = '') {
    const alertBox = document.getElementById('dynamic-alert');
    const titleElement = document.getElementById('dynamic-alert-title');
    const messageElement = document.getElementById('dynamic-alert-message');
    
    if (!alertBox || !titleElement || !messageElement) {
        console.error('Alert-Elemente nicht gefunden');
        return;
    }
    
    // Übersetzungen aus der alerts-Sektion verwenden
    const alertData = translations.alerts?.[alertKey];
    
    if (alertData) {
        titleElement.textContent = alertData.title + ':';
        let message = alertData.message;
        if (additionalInfo) {
            message += ' ' + additionalInfo;
        }
        messageElement.textContent = message;
        
        // Alert-Typ setzen (danger, warning, etc.)
        alertBox.className = 'alert alert-' + (alertData.type || 'primary');
    } else {
        // Fallback auf error-Übersetzungen
        const errorData = translations.error?.[alertKey];
        titleElement.textContent = translations.error?.title || 'Fehler';
        let message = errorData || 'Ein Fehler ist aufgetreten.';
        if (additionalInfo) {
            message += ' ' + additionalInfo;
        }
        messageElement.textContent = message;
        alertBox.className = 'alert alert-danger';
    }
    
    alertBox.style.display = 'block';
    
    // Nach 5 Sekunden automatisch ausblenden
    setTimeout(() => {
        hideDynamicAlert();
    }, 5000);
}

function hideDynamicAlert() {
    const alertBox = document.getElementById('dynamic-alert');
    if (alertBox) {
        alertBox.style.display = 'none';
    }
}
