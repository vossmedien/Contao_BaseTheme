// Configuration
const CONFIG = {
    MOBILE_BREAKPOINT: 768,
    DEFAULT_ZOOM: 15,
    MOBILE_ZOOM: 14,
    DEFAULT_AREA_SIZE: 40, // Hektar
    MAX_AREA_SIZE: 700, // Hektar
    DELAYS: {
        INIT: 500,
        TUTORIAL_TRANSITION: 100,
        PLACE_SELECTED: 300,
        POLYGON_CREATED: 200,
        MAP_MOVE_RESET: 50,
        SEARCH: 300,
        POLYGON_UPDATE: 50
    },
    TUTORIAL_COOKIE: 'caeli_area_check_tutorial_completed',
    AREA_SIZES: {
        MIN: 3,
        MAX: 50,
        DEFAULT: 3
    }
};

// Hauptobjekt für alle App-Daten
const CaeliAreaCheck = {
    // Google Maps Objekte
    map: null,
    polygon: null,
    areaLabel: null,
    geocoder: null,
    infoWindow: null,
    
    // State Management
    state: {
        polygonRelativeOffset: null,
        isPolygonBeingEdited: false,
        currentTutorialStep: 0,
        tutorialActive: false,
        formSubmissionInProgress: false,
        consentCheckInitialized: false,
        mapInitialized: false
    }
};

// Utility Functions
const Utils = {
    isMobile() {
        return window.innerWidth <= CONFIG.MOBILE_BREAKPOINT;
    },
    
    getCountryFromPlace(place) {
        if (!place || !place.address_components) return null;
        
        const countryComponent = place.address_components.find(
            component => component.types.includes('country')
        );
        
        return countryComponent ? countryComponent.short_name.toLowerCase() : null;
    },

    getPostalCodeRegex(countryCode) {
        const regexMap = {
            'de': /\b\d{5}\b/,                    // Deutschland: 12345
            'at': /\b\d{4}\b/,                    // Österreich: 1234  
            'ch': /\b\d{4}\b/,                    // Schweiz: 1234
            'fr': /\b\d{5}\b/,                    // Frankreich: 12345
            'nl': /\b\d{4}\s?[A-Z]{2}\b/,         // Niederlande: 1234 AB
            'be': /\b\d{4}\b/,                    // Belgien: 1234
            'dk': /\b\d{4}\b/,                    // Dänemark: 1234
            'se': /\b\d{3}\s?\d{2}\b/,            // Schweden: 123 45
            'no': /\b\d{4}\b/,                    // Norwegen: 1234
            'fi': /\b\d{5}\b/,                    // Finnland: 12345
            'it': /\b\d{5}\b/,                    // Italien: 12345
            'es': /\b\d{5}\b/,                    // Spanien: 12345
            'pt': /\b\d{4}-?\d{3}\b/,             // Portugal: 1234-123
            'pl': /\b\d{2}-?\d{3}\b/,             // Polen: 12-345
            'cz': /\b\d{3}\s?\d{2}\b/,            // Tschechien: 123 45
            'sk': /\b\d{3}\s?\d{2}\b/,            // Slowakei: 123 45
            'hu': /\b\d{4}\b/,                    // Ungarn: 1234
            'gb': /\b[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}\b/, // UK: M1 1AA
            'ie': /\b[A-Z]\d{2}\s?[A-Z0-9]{4}\b/, // Irland: D02 XY45
            // Fallback für unbekannte Länder: flexiblere Validation
            'default': /\b\d{3,5}(\s?[A-Z]{0,3})?\b/
        };
        
        return regexMap[countryCode] || regexMap.default;
    },

    validatePLZ(input, place = null) {
        // Wenn ein Place-Objekt vorhanden ist, das Land daraus ermitteln
        const countryCode = place ? this.getCountryFromPlace(place) : null;
        const regex = this.getPostalCodeRegex(countryCode);
        
        // PLZ in Input oder Adresse suchen
        const hasPostalCode = regex.test(input) || (place && regex.test(place.formatted_address || ''));
        
        const alertElement = document.getElementById('plz-alert');
        
        if (!hasPostalCode && alertElement) {
            alertElement.style.display = 'block';
            return false;
        }
        
        if (hasPostalCode && alertElement) {
            alertElement.style.display = 'none';
        }
        
        return hasPostalCode;
    },
    
    safeElementAction(selector, action) {
        const element = document.querySelector(selector);
        if (element && typeof action === 'function') {
            action(element);
        }
    }
};

// Global function for updating submit button state
function updateSubmitButtonState(isValidPLZ) {
    const submitButton = document.getElementById('log-coordinates-button');
    if (submitButton) {
        if (isValidPLZ && CaeliAreaCheck.polygon) {
            submitButton.disabled = false;
            submitButton.classList.remove('btn-secondary');
            submitButton.classList.add('btn-primary');
        } else {
            submitButton.disabled = true;
            submitButton.classList.remove('btn-primary');
            submitButton.classList.add('btn-secondary');
        }
    }
}

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
            template: 'welcome'
        },
        {
            // Schritt 1: Adresse/Ort eingeben
            element: '#place-autocomplete',
            elementMobile: '#controls', // Auf Mobile an der Box ausrichten
            title: tutorial.plz_input?.title || 'Schritt 1: Ihr Standort zählt.',
            content: tutorial.plz_input?.content || 'Starten Sie, indem Sie Ihre Postleitzahl eingeben. Nach der Eingabe wird das Polygonfeld erscheinen, welches Sie dann an die korrekte Position verschieben können.',
            placement: 'right',
            placementMobile: 'bottom',
            trigger: 'manual',
            template: 'plz'
        },
        {
            // Schritt 2: Polygon zeichnen - am rechten Bildschirmrand
            element: '#controls',
            title: tutorial.polygon_edit?.title || 'Schritt 2: Fläche einzeichnen.',
            content: tutorial.polygon_edit?.content || 'Jetzt kommt der spannende Teil: Klicken Sie die Eckpunkte Ihrer Fläche nacheinander an. So zeichnen Sie präzise Ihr Grundstück auf der Karte ein.',
            placement: 'left',
            placementMobile: 'bottom',
            trigger: 'polygon',
            template: 'polygon'
        },
        {
            // Schritt 3: Bestätigen/Fertig
            element: '#log-coordinates-button',
            title: tutorial.area_confirm?.title || 'Schritt 3: Fast geschafft!',
            content: tutorial.area_confirm?.content || 'Fast geschafft: Verschieben Sie das Polygon an die korrekte Position und richten Sie die Eckpunkte an Ihrem Grundstück aus. Klicken Sie dann auf "Ergebnis anzeigen". Wir prüfen in Windeseile die Bedingungen auf Ihrer Fläche.',
            placement: 'top',
            placementMobile: 'top',
            trigger: 'manual',
            template: 'confirm'
        }
    ];
}

function initMap() {
    const mapDiv = document.getElementById("map");
    const mapId = mapDiv.getAttribute('data-map-id') || undefined;

    CaeliAreaCheck.map = new google.maps.Map(mapDiv, {
        center: { lat: 51.165691, lng: 10.451526 },
        zoom: CONFIG.DEFAULT_ZOOM,
        mapTypeId: 'satellite',
        mapId: mapId,
        scrollwheel: true,
        gestureHandling: 'greedy' // Bessere Mobile-Bedienung
    });

    CaeliAreaCheck.geocoder = new google.maps.Geocoder();
    CaeliAreaCheck.infoWindow = new google.maps.InfoWindow();

    // Eigene Autocomplete-Lösung mit Geocoding Service
    const input = document.getElementById('place-autocomplete');
    const dropdown = document.getElementById('autocomplete-dropdown');
    let debounceTimer;
    let currentSuggestions = [];
    let selectedIndex = -1;
    
    if (input && dropdown) {
        // Tutorial Event Listener für Focus
        input.addEventListener('focus', handleInputFocus);
        
            // Input Event für Live-Suche und PLZ-Validierung
    input.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // PLZ-Validierung bei jeder Eingabe
        const isValidPLZ = Utils.validatePLZ(query);
        updateSubmitButtonState(isValidPLZ);
        
        // Dropdown verstecken bei leerem Input
        if (query.length < 2) {
            hideDropdown();
            return;
        }
        
        // Debounce für bessere Performance
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            searchPlaces(query);
        }, 200);
    });
        
        // Keyboard Navigation
        input.addEventListener('keydown', function(e) {
            if (dropdown.style.display === 'none') return;
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, currentSuggestions.length - 1);
                    updateSelection();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection();
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && currentSuggestions[selectedIndex]) {
                        selectPlace(currentSuggestions[selectedIndex]);
                    }
                    break;
                case 'Escape':
                    hideDropdown();
                    break;
            }
        });
        
        // Klick außerhalb schließt Dropdown
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                hideDropdown();
            }
        });
    }
    
    function searchPlaces(query) {
        if (!CaeliAreaCheck.geocoder) return;
        
        CaeliAreaCheck.geocoder.geocode({
            address: query
        }, function(results, status) {
            if (status === 'OK' && results && results.length > 0) {
                // Nur die besten 5 Ergebnisse anzeigen
                const topResults = results.slice(0, 5);
                currentSuggestions = topResults;
                showSuggestions(topResults);
            } else {
                hideDropdown();
            }
        });
    }
    
    function showSuggestions(suggestions) {
        if (!dropdown || suggestions.length === 0) {
            hideDropdown();
            return;
        }
        
        dropdown.innerHTML = '';
        selectedIndex = -1;
        
        suggestions.forEach((place, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <svg class="autocomplete-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                ${place.formatted_address}
            `;
            
            item.addEventListener('click', function() {
                selectPlace(place);
            });
            
            item.addEventListener('mouseenter', function() {
                selectedIndex = index;
                updateSelection();
            });
            
            dropdown.appendChild(item);
        });
        
        dropdown.style.display = 'block';
    }
    
    function updateSelection() {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }
    
    function selectPlace(place) {
        if (!place || !place.geometry) return;
        
        // Input-Wert setzen
        input.value = place.formatted_address;
        hideDropdown();
        
        // PLZ-Validation
        const isValidPLZ = Utils.validatePLZ(input.value, place);
        if (!isValidPLZ) {
            updateSubmitButtonState(false);
            input.focus();
            return;
        }
        
        // Gleiche Logik wie vorher
        let targetLocation = place.geometry.location;
        
        // Mobile-Anpassung
        if (Utils.isMobile() && targetLocation) {
            const mapBounds = CaeliAreaCheck.map.getBounds();
            const latSpan = mapBounds.getNorthEast().lat() - mapBounds.getSouthWest().lat();
            const adjustedLat = targetLocation.lat() - (latSpan * 0.4);
            targetLocation = new google.maps.LatLng(adjustedLat, targetLocation.lng());
        }
        
        if (targetLocation) {
            CaeliAreaCheck.map.setCenter(targetLocation);
            const zoomLevel = Utils.isMobile() ? CONFIG.MOBILE_ZOOM : CONFIG.DEFAULT_ZOOM;
            CaeliAreaCheck.map.setZoom(zoomLevel);
        }
        
        CaeliAreaCheck.infoWindow.close();
        
        if (targetLocation) {
            createPolygonAtLocation(targetLocation);
            Utils.safeElementAction('#button-wrapper', el => el.style.display = 'flex');
            updateSubmitButtonState(isValidPLZ);
            handlePlaceSelected();
        }
    }
    
    function hideDropdown() {
        if (dropdown) {
            dropdown.style.display = 'none';
            selectedIndex = -1;
            currentSuggestions = [];
        }
    }
    


    // Map-Event-Listener für Polygon-Mitbewegen
    google.maps.event.addListener(CaeliAreaCheck.map, 'center_changed', function() {
        if (CaeliAreaCheck.polygon && CaeliAreaCheck.state.polygonRelativeOffset && !CaeliAreaCheck.state.isPolygonBeingEdited) {
            movePolygonWithMap();
        }
    });

    // Event Listener für Buttons - nur einmal registrieren
    if (!window.caeliEventListenersInitialized) {
        Utils.safeElementAction('#delete-button', el => el.addEventListener('click', deletePolygon));
        Utils.safeElementAction('#log-coordinates-button', el => el.addEventListener('click', logCoordinates));
        window.caeliEventListenersInitialized = true;
    }
    
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
    } else {
        // Button initial deaktivieren wenn kein Input vorhanden
        updateSubmitButtonState(false);
    }
}

function prepareInputForInteraction(input) {
    if (!input) return;
    
    // Verzögere den Focus, damit Google Maps vollständig geladen ist
    setTimeout(() => {
        // Input fokussieren und Cursor ans Ende setzen
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
        
        // Visueller Hinweis mit Animation
        input.style.transition = 'box-shadow 0.3s ease';
        input.style.boxShadow = '0 0 0 2px rgba(17, 53, 52, 0.3)';
        
        setTimeout(() => {
            input.style.boxShadow = '';
        }, 2000);
    }, CONFIG.DELAYS.INIT); // Verzögerung für vollständige Initialisierung
}

function geocodeInputValue(address) {
    if (!CaeliAreaCheck.geocoder) return;
    
    // PLZ-Validation mit Utility-Funktion (ohne Place-Objekt)
    const isValidPLZ = Utils.validatePLZ(address);
    if (!isValidPLZ) {
        updateSubmitButtonState(false);
        return;
    }
    
    CaeliAreaCheck.geocoder.geocode({
        address: address
    }, function(results, status) {
        if (status === 'OK' && results[0]) {
            const place = results[0];
            
            // PLZ nochmals prüfen mit Utility-Funktion
            const placeValidPLZ = Utils.validatePLZ(address, place);
            if (!placeValidPLZ) {
                updateSubmitButtonState(false);
                return;
            }
            
            // Eingabe über Geocoding - wird bei Polygon-Bewegung überschrieben
            
            let targetLocation = place.geometry.location;
            
            // Mobile-Anpassung: Zentrum weiter nach unten verschieben
            if (Utils.isMobile() && targetLocation) {
                const mapBounds = CaeliAreaCheck.map.getBounds();
                const latSpan = mapBounds.getNorthEast().lat() - mapBounds.getSouthWest().lat();
                const adjustedLat = targetLocation.lat() - (latSpan * 0.4);
                targetLocation = new google.maps.LatLng(adjustedLat, targetLocation.lng());
            }
            
            if (targetLocation) {
                CaeliAreaCheck.map.setCenter(targetLocation);
                // Mobile: Zoom weiter raus für bessere Übersicht
                const zoomLevel = Utils.isMobile() ? CONFIG.MOBILE_ZOOM : CONFIG.DEFAULT_ZOOM;
                CaeliAreaCheck.map.setZoom(zoomLevel);
                createPolygonAtLocation(targetLocation);
                
                // Buttons anzeigen
                Utils.safeElementAction('#button-wrapper', el => el.style.display = 'flex');
                
                // Button-Status aktualisieren
                updateSubmitButtonState(placeValidPLZ);
                
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
    if (CaeliAreaCheck.polygon) {
        CaeliAreaCheck.polygon.setMap(null);
    }
    if (CaeliAreaCheck.areaLabel) {
        CaeliAreaCheck.areaLabel.setMap(null);
        CaeliAreaCheck.areaLabel = null;
    }

    // Polygon-Center auf Mobile nach unten verschieben
    let polygonCenter = center;
    if (Utils.isMobile()) {
        // Auf Mobile das Polygon deutlich weiter nach unten verschieben
        const offsetLat = -0.001; // Etwa 100m nach Süden
        polygonCenter = new google.maps.LatLng(
            center.lat() + offsetLat,
            center.lng()
        );
    }

    // Standardgröße aus Config
    const size = CONFIG.DEFAULT_AREA_SIZE;
    const earthRadius = 6371000; // in meters
    const areaSideLength = Math.sqrt(size * 10000); // Convert ha to m²
    const latDiff = (areaSideLength / 2) / earthRadius * (180 / Math.PI);
    const lngDiff = latDiff / Math.cos(polygonCenter.lat() * Math.PI / 180);

    const bounds = {
        north: polygonCenter.lat() + latDiff,
        south: polygonCenter.lat() - latDiff,
        east: polygonCenter.lng() + lngDiff,
        west: polygonCenter.lng() - lngDiff
    };

    const path = [
        {lat: bounds.north, lng: bounds.west},
        {lat: bounds.north, lng: bounds.east},
        {lat: bounds.south, lng: bounds.east},
        {lat: bounds.south, lng: bounds.west}
    ];

    CaeliAreaCheck.polygon = new google.maps.Polygon({
        path: path,
        map: CaeliAreaCheck.map,
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
    if (Utils.isMobile()) {
        CaeliAreaCheck.state.polygonRelativeOffset = {
            lat: -0.002, // Polygon ist auf Mobile nach unten verschoben
            lng: 0
        };
    } else {
        CaeliAreaCheck.state.polygonRelativeOffset = {
            lat: 0, // Polygon ist zentriert
            lng: 0
        };
    }

    updateAreaLabel();
    updateGeometryField();

    // Event Listener für Polygon-Änderungen
    google.maps.event.addListener(CaeliAreaCheck.polygon.getPath(), 'set_at', function() {
        // Immer reagieren - auch während der Bearbeitung für Live-Updates
        updatePolygonOffset();
        updateAreaLabel();
        updateGeometryField();
    });
    google.maps.event.addListener(CaeliAreaCheck.polygon.getPath(), 'insert_at', function() {
        // Immer reagieren - auch während der Bearbeitung für Live-Updates
        updatePolygonOffset();
        updateAreaLabel();
        updateGeometryField();
    });
    
    // Event Listener für Drag-Operationen
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'dragstart', function() {
        CaeliAreaCheck.state.isPolygonBeingEdited = true;
    });
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'dragend', function() {
        updatePolygonOffset();
        updateAreaLabel();
        updateGeometryField();
        CaeliAreaCheck.state.isPolygonBeingEdited = false;
    });
    
    // Event Listener für Live-Updates während des Ziehens der Polygon-Punkte
    google.maps.event.addListener(CaeliAreaCheck.polygon.getPath(), 'remove_at', function() {
        updatePolygonOffset();
        updateAreaLabel();
        updateGeometryField();
    });

    // Tutorial: Polygon wurde erstellt
    handlePolygonCreated();
}

function deletePolygon() {
    if (CaeliAreaCheck.polygon) {
        CaeliAreaCheck.polygon.setMap(null);
        CaeliAreaCheck.polygon = null;
    }
    if (CaeliAreaCheck.areaLabel) {
        CaeliAreaCheck.areaLabel.setMap(null);
        CaeliAreaCheck.areaLabel = null;
    }

    // Polygon-Offset zurücksetzen
    CaeliAreaCheck.state.polygonRelativeOffset = null;
    CaeliAreaCheck.state.isPolygonBeingEdited = false;

    Utils.safeElementAction('#warning', el => el.style.display = 'none');
    updateGeometryField();
    
    // Button-Status zurücksetzen
    const input = document.getElementById('place-autocomplete');
    if (input && input.value) {
        const isValidPLZ = Utils.validatePLZ(input.value);
        updateSubmitButtonState(isValidPLZ);
    } else {
        updateSubmitButtonState(false);
    }

    // Neues Polygon an aktueller Position anzeigen (nach Neustarten)
    const currentCenter = CaeliAreaCheck.map.getCenter();
    if (currentCenter) {
        createPolygonAtLocation(currentCenter);
    }
}

function updateAreaLabel() {
    if (CaeliAreaCheck.areaLabel) {
        CaeliAreaCheck.areaLabel.map = null;
        CaeliAreaCheck.areaLabel = null;
    }

    const area = google.maps.geometry.spherical.computeArea(CaeliAreaCheck.polygon.getPath());
    const areaInHectares = (area / 10000).toFixed(2);

    const bounds = new google.maps.LatLngBounds();
    CaeliAreaCheck.polygon.getPath().forEach(function(latLng) {
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

    CaeliAreaCheck.areaLabel = new AdvancedMarkerElement({
        map: CaeliAreaCheck.map,
        position: center,
        content: labelDiv
    });

    // Check if area is greater than configured max size
    if (areaInHectares > CONFIG.MAX_AREA_SIZE) {
        Utils.safeElementAction('#warning', el => el.style.display = 'block');
        updateSubmitButtonState(false);
    } else {
        Utils.safeElementAction('#warning', el => el.style.display = 'none');
        // Button-Status basierend auf PLZ-Validierung setzen
        const input = document.getElementById('place-autocomplete');
        if (input && input.value) {
            const isValidPLZ = Utils.validatePLZ(input.value);
            updateSubmitButtonState(isValidPLZ);
        } else {
            updateSubmitButtonState(false);
        }
    }
    
    // Live Reverse Geocoding für bessere Adresserkennung (Optional - kann performance-intensiv sein)
    updatePolygonAddress(center);
}

// Live Adress-Update für Polygon-Zentrum (throttled)
let reverseGeocodingTimeout;
function updatePolygonAddress(center) {
    // Throttling: Nur alle 1 Sekunde ein Reverse Geocoding  
    clearTimeout(reverseGeocodingTimeout);
    reverseGeocodingTimeout = setTimeout(() => {
        if (CaeliAreaCheck.geocoder && center) {
            CaeliAreaCheck.geocoder.geocode({
                location: center
            }, function(results, status) {
                if (status === 'OK' && results && results.length > 0) {
                    // Beste verfügbare Adresse finden
                    const bestAddress = findBestAddress(results);
                    
                    if (bestAddress) {
                        // Vollständige Adresse für Live-Update verwenden
                        const fullAddress = extractFullAddress(bestAddress);
                        
                        // Direkt ins Input-Feld schreiben (Live-Update)
                        const searchInput = document.getElementById('place-autocomplete');
                        if (searchInput) {
                            // Immer überschreiben - User verschiebt Polygon und will neue Adresse sehen
                            searchInput.value = fullAddress;
                            
                            // PLZ-Validierung für live-aktualisierte Adresse
                            const isValidPLZ = Utils.validatePLZ(fullAddress);
                            updateSubmitButtonState(isValidPLZ);
                        }
                    }
                }
            });
        }
    }, 500); // 0.5 Sekunden throttling für bessere User Experience
}

// Beste Adresse aus Reverse Geocoding Ergebnissen finden (keine Plus Codes!)
function findBestAddress(results) {
    if (!results || results.length === 0) return null;
    
    // Prioritätsliste für die beste Adresse:
            // 1. Adresse mit Postleitzahl
    // 2. Adresse ohne Plus Code (keine Buchstaben+Zahlen Kombination am Anfang)
    // 3. Längste Adresse (meist detaillierter)
    
    for (let result of results) {
        const address = result.formatted_address;
        
        // Plus Codes vermeiden (Format: "F6R7+QH" am Anfang)
        if (address.match(/^[A-Z0-9]{4}\+[A-Z0-9]{2}/)) {
            continue; // Plus Code überspringen
        }
        
        // Postleitzahl bevorzugen (verwende das erste Ergebnis für Country-Detection)
        const firstResult = results[0];
        const countryCode = Utils.getCountryFromPlace(firstResult);
        const postalRegex = Utils.getPostalCodeRegex(countryCode);
        
        if (postalRegex.test(address)) {
            return address;
        }
    }
    
    // Fallback: Erste Adresse ohne Plus Code
    for (let result of results) {
        const address = result.formatted_address;
        if (!address.match(/^[A-Z0-9]{4}\+[A-Z0-9]{2}/)) {
            return address;
        }
    }
    
    // Notfall-Fallback: Erste verfügbare Adresse
    return results[0].formatted_address;
}

// Hilfsfunktion: Vollständige Adresse verwenden (inkl. Deutschland)
function extractFullAddress(fullAddress) {
    // Komplette Adresse zurückgeben für bessere Übersichtlichkeit
    // "Am alten bhf 22, 04420 Markranstädt, Deutschland" bleibt so
    
    // Nur Plus Codes am Anfang entfernen, Rest beibehalten
    if (fullAddress.match(/^[A-Z0-9]{4}\+[A-Z0-9]{2}/)) {
        const parts = fullAddress.split(',');
        // Plus Code entfernen, Rest zusammenfügen
        if (parts.length > 1) {
            return parts.slice(1).join(',').trim();
        }
    }
    
    // Standard: Vollständige Adresse zurückgeben
    return fullAddress;
}

function logCoordinates() {
    if (CaeliAreaCheck.polygon) {
        // Verhindere doppelte Submissions
        if (CaeliAreaCheck.state.formSubmissionInProgress) {
            return;
        }
        
        // Finale PLZ-Validierung vor Absenden
        const input = document.getElementById('place-autocomplete');
        if (input && input.value) {
            const isValidPLZ = Utils.validatePLZ(input.value);
            if (!isValidPLZ) {
                const translations = window.CaeliAreaCheckTranslations || {};
                showDynamicAlert('invalid_postal_code', translations);
                return;
            }
        }
        
        CaeliAreaCheck.state.formSubmissionInProgress = true;
        
        // Alle Popovers schließen vor dem Absenden
        hideAllPopovers();
        
        // Loading Overlay anzeigen
        showLoadingOverlay();
        
        const coordinates = CaeliAreaCheck.polygon.getPath().getArray().map(latLng => [latLng.lng(), latLng.lat()]);
        coordinates.push(coordinates[0]); // Polygon schließen
        const geometry = {
            geometry: {
                coordinates: [coordinates],
                type: "Polygon"
            }
        };
        
        // Geometrie in das versteckte Form-Feld schreiben
        Utils.safeElementAction('#geometry-field', el => el.value = JSON.stringify(geometry));
        
        // Polygon-Zentrum für Reverse Geocoding ermitteln
        const bounds = new google.maps.LatLngBounds();
        CaeliAreaCheck.polygon.getPath().forEach(function(latLng) {
            bounds.extend(latLng);
        });
        const polygonCenter = bounds.getCenter();
        
        // Reverse Geocoding für das Polygon-Zentrum
        if (CaeliAreaCheck.geocoder) {
            CaeliAreaCheck.geocoder.geocode({
                location: polygonCenter
            }, function(results, status) {
                if (status === 'OK' && results && results.length > 0) {
                    // Beste verfügbare Adresse finden (keine Plus Codes!)
                    const bestAddress = findBestAddress(results);
                    
                    if (bestAddress) {
                        // Adresse ins versteckte Feld schreiben (überschreibt manuell eingegebene Adresse)
                        const hiddenAddress = document.getElementById('searched-address-field');
                        if (hiddenAddress) {
                            hiddenAddress.value = bestAddress;
                        }
                    } else {
                        // Fallback: ursprünglich eingegebene Adresse verwenden
                        const searchInput = document.getElementById('place-autocomplete');
                        const hiddenAddress = document.getElementById('searched-address-field');
                        if (searchInput && hiddenAddress) {
                            hiddenAddress.value = searchInput.value;
                        }
                    }
                } else {
                    // Fallback: ursprünglich eingegebene Adresse verwenden
                    const searchInput = document.getElementById('place-autocomplete');
                    const hiddenAddress = document.getElementById('searched-address-field');
                    if (searchInput && hiddenAddress) {
                        hiddenAddress.value = searchInput.value;
                    }
                    console.warn('Reverse Geocoding fehlgeschlagen:', status);
                }
                
                            // AJAX Form-Submit starten (verzögert, damit Geocoding abgeschlossen ist)
            submitFormWithRedirect();
            });
        } else {
            // Fallback ohne Reverse Geocoding: ursprünglich eingegebene Adresse verwenden
            const searchInput = document.getElementById('place-autocomplete');
            const hiddenAddress = document.getElementById('searched-address-field');
            if (searchInput && hiddenAddress) {
                hiddenAddress.value = searchInput.value;
            }
            
            // AJAX Form-Submit starten
            submitFormWithRedirect();
        }
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
    if (CaeliAreaCheck.polygon) {
        const coordinates = CaeliAreaCheck.polygon.getPath().getArray().map(latLng => [latLng.lng(), latLng.lat()]);
        coordinates.push(coordinates[0]);
        const geometry = {
            geometry: {
                coordinates: [coordinates],
                type: "Polygon"
            }
        };
        Utils.safeElementAction('#geometry-field', el => el.value = JSON.stringify(geometry));
    } else {
        Utils.safeElementAction('#geometry-field', el => el.value = '');
    }
}

// Hilfsfunktionen für Polygon-Mitbewegen
function updatePolygonOffset() {
    if (!CaeliAreaCheck.polygon || !CaeliAreaCheck.map) return;
    
    // Aktuelles Polygon-Center berechnen
    const bounds = new google.maps.LatLngBounds();
    CaeliAreaCheck.polygon.getPath().forEach(function(latLng) {
        bounds.extend(latLng);
    });
    const polygonCenter = bounds.getCenter();
    const mapCenter = CaeliAreaCheck.map.getCenter();
    
    // Relativen Offset speichern
    let calculatedOffset = {
        lat: polygonCenter.lat() - mapCenter.lat(),
        lng: polygonCenter.lng() - mapCenter.lng()
    };
    
    // Auf Mobile: Mindest-Offset beibehalten, damit Polygon sichtbar bleibt
    if (Utils.isMobile()) {
        const minMobileOffset = -0.002;
        // Wenn das Polygon manuell nach oben verschoben wurde, trotzdem Mindest-Offset beibehalten
        if (calculatedOffset.lat > minMobileOffset) {
            calculatedOffset.lat = minMobileOffset;
        }
        // Wenn das Polygon weiter unten ist, den berechneten Wert beibehalten aber nicht über den Mindest-Offset hinausgehen
        if (calculatedOffset.lat < minMobileOffset && calculatedOffset.lat > -0.01) {
            // Akzeptiere moderate Verschiebungen nach unten
            // calculatedOffset.lat bleibt wie berechnet
        }
    }
    
    CaeliAreaCheck.state.polygonRelativeOffset = calculatedOffset;
}

function movePolygonWithMap() {
    if (!CaeliAreaCheck.polygon || !CaeliAreaCheck.state.polygonRelativeOffset || !CaeliAreaCheck.map) return;
    
    // Flag setzen, um rekursive Updates zu vermeiden
    CaeliAreaCheck.state.isPolygonBeingEdited = true;
    
    const mapCenter = CaeliAreaCheck.map.getCenter();
    
    // Auf Mobile: Mindest-Offset sicherstellen
    let finalOffset = CaeliAreaCheck.state.polygonRelativeOffset;
    if (Utils.isMobile() && finalOffset.lat > -0.002) {
        finalOffset = {
            lat: -0.002,
            lng: finalOffset.lng
        };
    }
    
    const targetCenter = {
        lat: mapCenter.lat() + finalOffset.lat,
        lng: mapCenter.lng() + finalOffset.lng
    };
    
    // Aktuelles Polygon-Center berechnen
    const bounds = new google.maps.LatLngBounds();
    CaeliAreaCheck.polygon.getPath().forEach(function(latLng) {
        bounds.extend(latLng);
    });
    const currentCenter = bounds.getCenter();
    
    // Bewegungsvektor berechnen
    const deltaLat = targetCenter.lat - currentCenter.lat();
    const deltaLng = targetCenter.lng - currentCenter.lng();
    
    // Alle Polygon-Punkte verschieben
    const path = CaeliAreaCheck.polygon.getPath();
    const newPath = [];
    for (let i = 0; i < path.getLength(); i++) {
        const point = path.getAt(i);
        newPath.push(new google.maps.LatLng(
            point.lat() + deltaLat,
            point.lng() + deltaLng
        ));
    }
    
    CaeliAreaCheck.polygon.setPath(newPath);
    
    // Area-Label und Geometry-Field nach Karten-Move updaten
    updateAreaLabel();
    updateGeometryField();
    
    // Flag zurücksetzen - verzögert für saubere Event-Behandlung
    setTimeout(() => { 
        CaeliAreaCheck.state.isPolygonBeingEdited = false; 
    }, CONFIG.DELAYS.MAP_MOVE_RESET);
}

// Form-Handler
const parkForm = document.getElementById('park-form');
if (parkForm) {
    parkForm.addEventListener('submit', function(e) {
        // Verhindere doppelte Submissions
        if (CaeliAreaCheck.state.formSubmissionInProgress) {
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
    if (CaeliAreaCheck.state.mapInitialized) return;

    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initMap();
        CaeliAreaCheck.state.mapInitialized = true;
        // Tutorial sofort starten
        setTimeout(initTutorial, 100);
    } else {
        let tries = 0;
        function tryInit() {
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                initMap();
                mapInitialized = true;
                // Tutorial sofort starten
                setTimeout(initTutorial, 100);
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
    if (CaeliAreaCheck.state.consentCheckInitialized) return;
    CaeliAreaCheck.state.consentCheckInitialized = true;

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
    if (getCookie(CONFIG.TUTORIAL_COOKIE)) {
        return;
    }

    CaeliAreaCheck.state.tutorialActive = true;
    CaeliAreaCheck.state.currentTutorialStep = 0;
    showTutorialStep(0);
}

function showTutorialStep(stepIndex) {
    console.log('showTutorialStep called with stepIndex:', stepIndex);
    
    const tutorialSteps = getTutorialSteps();
    if (stepIndex >= tutorialSteps.length) {
        completeTutorial();
        return;
    }

    const step = tutorialSteps[stepIndex];
    console.log('Step:', step);
    
    const isMobile = Utils.isMobile();
    const targetElement = isMobile && step.elementMobile ? step.elementMobile : step.element;
    console.log('Target element:', targetElement);
    
    const element = document.querySelector(targetElement);

    if (!element) {
        console.warn(`Tutorial element ${targetElement} not found`);
        return;
    }
    
    console.log('Element found:', element);

    // Vorherige Popovers schließen
    hideAllPopovers();

    // Responsive Placement
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
                // Adress-Schritt (1) - Zurück-Button nur auf Desktop anzeigen
                const backButton = popoverElement.querySelector('#tutorial-plz-back');
            
            if (backButton) {
                console.log('PLZ Back button found');
                backButton.addEventListener('click', () => {
                    console.log('PLZ Back button clicked');
                    previousTutorialStep(false);
                });
                console.log('PLZ Back button event listener added');
            } else {
                console.log('PLZ Back button NOT found');
            }
        } else if (step.template === 'polygon') {
            // Polygon-Schritt (2)
            const backButton = popoverElement.querySelector('#tutorial-polygon-back');
            const nextButton = popoverElement.querySelector('#tutorial-polygon-next');
            
            if (backButton) {
                backButton.addEventListener('click', () => previousTutorialStep(true));
            }
            if (nextButton) {
                nextButton.addEventListener('click', nextTutorialStep);
            }
        } else if (step.template === 'confirm') {
            // Bestätigen-Schritt (3)
            const backButton = popoverElement.querySelector('#tutorial-confirm-back');
            const nextButton = popoverElement.querySelector('#tutorial-confirm-next');
            
            if (backButton) {
                backButton.addEventListener('click', () => previousTutorialStep(true));
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

    CaeliAreaCheck.state.currentTutorialStep++;
    const tutorialSteps = getTutorialSteps();

    if (CaeliAreaCheck.state.currentTutorialStep >= tutorialSteps.length) {
        completeTutorial();
    } else {
        showTutorialStep(CaeliAreaCheck.state.currentTutorialStep);
    }
}

function previousTutorialStep(fromPolygonStep = false) {
    if (CaeliAreaCheck.state.currentTutorialStep > 0) {
        console.log('Previous step called, current step:', CaeliAreaCheck.state.currentTutorialStep, 'fromPolygonStep:', fromPolygonStep);
        
        // Aktuelles Popover schließen
        hideAllPopovers();

        // Spezielle Behandlung beim Zurückgehen von Schritt 2 zu Schritt 1
        if (CaeliAreaCheck.state.currentTutorialStep === 2) {
            // Input leeren, damit User PLZ neu eingeben muss
            const input = document.getElementById('place-autocomplete');
            if (input) {
                input.value = '';
                // Nur Fokus setzen wenn nicht vom Polygon-Back (fromPolygonStep = false)
                if (!fromPolygonStep) {
                    input.focus();
                }
            }
            
            // Polygon und Buttons verstecken
            deletePolygon();
            Utils.safeElementAction('#button-wrapper', el => el.style.display = 'none');
        }

        // Spezielle Behandlung beim Zurückgehen von Schritt 1 zu Schritt 0 (PLZ-Back)
        if (CaeliAreaCheck.state.currentTutorialStep === 1 && !fromPolygonStep) {
            console.log('Going from step 1 to step 0');
            // Input leeren, damit User wieder von vorne beginnt
            const input = document.getElementById('place-autocomplete');
            if (input) {
                input.value = '';
            }
        }

        CaeliAreaCheck.state.currentTutorialStep--;
        console.log('New step:', CaeliAreaCheck.state.currentTutorialStep);
        
        // Kurz warten bevor neues Popover angezeigt wird
        setTimeout(() => {
            showTutorialStep(CaeliAreaCheck.state.currentTutorialStep);
        }, 100);
    }
}

function hideAllPopovers() {
    console.log('hideAllPopovers called');
    
    // Alle sichtbaren Popovers entfernen
    const existingPopovers = document.querySelectorAll('.popover');
    console.log('Found existing popovers:', existingPopovers.length);
    existingPopovers.forEach(popover => {
        popover.remove();
    });

    // Alle möglichen Elemente durchgehen und Popover-Referenzen entfernen
    const allElements = [
        '#controls',
        '#place-autocomplete', 
        '#log-coordinates-button'
    ];
    
    allElements.forEach(selector => {
        const element = document.querySelector(selector);
        if (element && element._tutorialPopover) {
            console.log('Removing popover from:', selector);
            try {
                element._tutorialPopover.dispose();
            } catch (e) {
                console.warn('Fehler beim Dispose von Popover:', e);
            }
            delete element._tutorialPopover;
        }
    });
}

function completeTutorial() {
    hideAllPopovers();
    CaeliAreaCheck.state.tutorialActive = false;
    setCookie(CONFIG.TUTORIAL_COOKIE, 'true', 365); // 1 Jahr gültig
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
    if (CaeliAreaCheck.state.tutorialActive && CaeliAreaCheck.state.currentTutorialStep === 0) {
                    // Vom Willkommen-Schritt zum Adress-Schritt wechseln
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(1);
            CaeliAreaCheck.state.currentTutorialStep = 1;
        }, CONFIG.DELAYS.TUTORIAL_TRANSITION);
    }
}

function handlePlaceSelected() {
    if (CaeliAreaCheck.state.tutorialActive && CaeliAreaCheck.state.currentTutorialStep === 1) {
                    // Vom Adress-Schritt zum Polygon-Schritt wechseln
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(2);
            CaeliAreaCheck.state.currentTutorialStep = 2;
        }, CONFIG.DELAYS.PLACE_SELECTED);
    }
}

function handlePolygonCreated() {
    if (CaeliAreaCheck.state.tutorialActive && CaeliAreaCheck.state.currentTutorialStep === 2) {
        // Vom Polygon-Schritt zum Bestätigen-Schritt wechseln
        hideAllPopovers();
        setTimeout(() => {
            showTutorialStep(3);
            CaeliAreaCheck.state.currentTutorialStep = 3;
        }, CONFIG.DELAYS.POLYGON_CREATED);
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
    
    // KEINE Rotation mehr - SVG bleibt statisch
    const spinner = document.querySelector('.loading-spinner svg');
    if (spinner) {
        spinner.style.animation = 'none'; // Rotation entfernt
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
    console.log('=== submitFormWithRedirect aufgerufen ===');
    
    // AJAX-Konfiguration aus Template laden
    let ajaxConfig = window.CaeliAreaCheckConfig;
    
    // AJAX ist IMMER verfügbar - kein Fallback zu sync
    if (!ajaxConfig || !ajaxConfig.startUrl || !ajaxConfig.statusUrl) {
        console.error('❌ AJAX Config fehlt komplett - schwerwiegender Fehler!');
        showAsyncError('Konfigurationsfehler: AJAX-Endpoints nicht verfügbar. Bitte kontaktieren Sie den Support.');
        return false; // Verhindert Form-Submit komplett
    }
    
    console.log('✅ AJAX Config vollständig - starte AJAX-Processing');
    
    // Loading-Overlay sofort anzeigen
    showLoadingOverlay();
    
    // AJAX-Processing starten (verhindert normale Form-Submit)
    startAsyncAreaCheck();
    return false; // Verhindert normale Form-Submit IMMER
}

/**
 * Startet AJAX Area Check mit animierter Progress-Anzeige
 */
function startAsyncAreaCheck() {
    console.log('[DEBUG] AJAX: Start async area check aufgerufen');
    
    const form = document.getElementById('park-form');
    if (!form) {
        console.error('[DEBUG] Form nicht gefunden');
        window.caeliAjaxInProgress = false;
        return;
    }
    
    // Loading-Animation mit wechselnden Texten starten
    startLoadingAnimationWithTextRotation();
    
    // Form-Daten sammeln
    const formData = new FormData(form);
    const config = window.CaeliAreaCheckConfig || {};
    const startUrl = config.startUrl;
    
    console.log('[DEBUG] AJAX Config:', config);
    console.log('[DEBUG] Start URL:', startUrl);
    
    // Debug: Form Data loggen
    for (let [key, value] of formData.entries()) {
        console.log('[DEBUG] Form Data:', key, value);
    }
    
    console.log('[DEBUG] Sende AJAX Request an:', startUrl);
    
    fetch(startUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('[DEBUG] AJAX Start Response Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.text().then(text => {
            console.log('[DEBUG] Response Text:', text.substring(0, 200) + '...');
            return JSON.parse(text);
        });
    })
    .then(data => {
        console.log('[DEBUG] AJAX Start Response Data:', data);
        
        if (data.status === 'queued' && data.sessionId) {
            console.log('[DEBUG] Starting polling with sessionId:', data.sessionId);
            startPolling(data.sessionId);
        } else {
            throw new Error('Ungültige Response-Struktur: ' + JSON.stringify(data));
        }
    })
    .catch(error => {
        console.error('[DEBUG] AJAX Start Fehler:', error);
        handleAsyncError('AJAX-Fehler: ' + error.message);
    });
}

/**
 * Loading-Animation mit wechselnden Texten und simulierter Progress
 */
function startLoadingAnimationWithTextRotation() {
    // Übersetzungen laden
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
    let simulatedProgress = 0;
    
    // SVG-Spinner initialisieren (ohne Rotation)
    initLoadingSpinner();
    
    // Text-Element für animierte Rotation
    const messageElement = document.querySelector('.loading-message');
    const percentageElement = document.querySelector('.loading-percentage');
    
    if (messageElement) {
        // Erstes animiertes Einblenden
        messageElement.textContent = loadingTexts[currentTextIndex];
        messageElement.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        messageElement.style.transform = 'translateY(30px)';
        messageElement.style.opacity = '0';
        
        // Ersten Text sofort einblenden
        setTimeout(() => {
            messageElement.style.transform = 'translateY(0)';
            messageElement.style.opacity = '1';
        }, 30);
    }
    
    // Kontinuierliche simulierte Progress bis echte Daten kommen (KEIN STOPP bei 40%)
    const progressInterval = setInterval(() => {
        if (simulatedProgress < 85) { // Erhöht von 40% auf 85%
            simulatedProgress += Math.random() * 3 + 2; // 2-5% pro Schritt
            simulatedProgress = Math.min(simulatedProgress, 85);
            
            if (percentageElement) {
                percentageElement.textContent = `${Math.round(simulatedProgress)}%`;
            }
            updateSpinnerProgress(simulatedProgress);
        }
    }, 400); // Alle 400ms
    
    // Text-Rotation starten nach dem ersten Einblenden
    const textInterval = setInterval(() => {
        if (messageElement) {
            // Smooth fade out nach oben
            messageElement.style.transition = 'transform 0.6s cubic-bezier(0.55, 0.06, 0.68, 0.19), opacity 0.6s cubic-bezier(0.55, 0.06, 0.68, 0.19)';
            messageElement.style.transform = 'translateY(-30px)';
            messageElement.style.opacity = '0';
            
            setTimeout(() => {
                // Nächsten Text setzen
                currentTextIndex = (currentTextIndex + 1) % loadingTexts.length;
                messageElement.textContent = loadingTexts[currentTextIndex];
                
                // Von unten einblenden - sofort positionieren
                messageElement.style.transform = 'translateY(30px)';
                messageElement.style.opacity = '0';
                
                // Smooth fade in
                setTimeout(() => {
                    messageElement.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    messageElement.style.transform = 'translateY(0)';
                    messageElement.style.opacity = '1';
                }, 20);
            }, 300);
        }
    }, 2500); // Alle 2.5 Sekunden Text wechseln
    
    // Intervalles für Cleanup speichern
    window.caeliLoadingIntervals = { progressInterval, textInterval };
}

/**
 * Stoppt alle Loading-Animationen
 */
function stopLoadingAnimations() {
    if (window.caeliLoadingIntervals) {
        clearInterval(window.caeliLoadingIntervals.progressInterval);
        clearInterval(window.caeliLoadingIntervals.textInterval);
        window.caeliLoadingIntervals = null;
        console.log('[DEBUG] Loading-Animationen gestoppt');
    }
}

/**
 * Aktualisiert mit echten Progress-Daten vom Backend
 */
function updateRealProgress(progressData) {
    const percentage = progressData.percentage || 0;
    const message = progressData.message || 'Wird verarbeitet...';
    
    // Simulierte Progress stoppen wenn echte Daten kommen
    stopLoadingAnimations();
    
    // Loading-Percentage aktualisieren
    const percentageElement = document.querySelector('.loading-percentage');
    if (percentageElement) {
        percentageElement.textContent = `${percentage}%`;
    }
    
    // SVG-Progress Animation: Balken von 12 Uhr im Uhrzeigersinn färben
    updateSpinnerProgress(percentage);
    
    // Wechselnde Status-Texte im loading-message div (überschreibt Rotation)
    const messageElement = document.querySelector('.loading-message');
    if (messageElement && percentage < 100) {
        messageElement.textContent = message;
        messageElement.style.transform = 'translateY(0)';
        messageElement.style.opacity = '1';
    }
    
    console.log(`[Progress] ${percentage}%: ${message}`);
}

/**
 * Spinner initialisieren - alle Balken grau, OHNE Rotation
 */
function initLoadingSpinner() {
    const bars = document.querySelectorAll('.spinner-bar');
    bars.forEach((bar) => {
        bar.setAttribute('fill', '#DEEEC6'); // Standard-Farbe grau
    });
    
    // KEINE Rotation mehr - SVG bleibt statisch
    const spinner = document.querySelector('.loading-spinner svg');
    if (spinner) {
        spinner.style.animation = 'none'; // Rotation entfernt
    }
}

/**
 * Behandelt Async-Fehler
 */
function handleAsyncError(errorMessage) {
    // OVERLAY BLEIBT SICHTBAR - Keine hideLoadingOverlay()!
    
    const translations = window.CaeliAreaCheckTranslations || {};
    
    // Fehler-Message im Loading-Overlay anzeigen
    const messageElement = document.querySelector('.loading-message');
    if (messageElement) {
        messageElement.textContent = 'Fehler: ' + errorMessage;
        messageElement.style.color = '#dc3545'; // Bootstrap danger color
    }
    
    // Nach 5 Sekunden Fallback auf synchrone Form ABER OVERLAY BLEIBT
    setTimeout(() => {
        const messageElement = document.querySelector('.loading-message');
        if (messageElement) {
            messageElement.textContent = 'Verwende synchrone Verarbeitung...';
            messageElement.style.color = '#666';
        }
        
        const parkForm = document.getElementById('park-form');
        if (parkForm) {
            setTimeout(() => {
                parkForm.submit();
            }, 1000);
        }
    }, 5000);
}

/**
 * Startet Polling für Session-Status - SINGLETON PATTERN
 */
function startPolling(sessionId) {
    console.log('[DEBUG] startPolling aufgerufen mit sessionId:', sessionId);
    
    const maxPolls = 60; // 3 Minuten bei 3s Intervall
    let pollCount = 0;
    let isCompleted = false; // Verhindert doppelte Completion durch Race Conditions
    
    const baseUrl = window.CaeliAreaCheckConfig?.statusUrl;
    if (!baseUrl) {
        console.error('[DEBUG] Status URL nicht verfügbar');
        handleAsyncError('Status URL nicht konfiguriert');
        return;
    }
    
    console.log('[DEBUG] Starting polling for sessionId:', sessionId);
    
    const pollInterval = setInterval(() => {
        pollCount++;
        console.log(`[DEBUG] Polling #${pollCount} für Session: ${sessionId}`);
        
        const pollUrl = `${baseUrl}/${sessionId}`;
        
        fetch(pollUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Polling Response Data:', data);
            
            // Race Condition: Ignoriere weitere Responses wenn bereits completed
            if (isCompleted) {
                console.log('[DEBUG] Bereits completed - ignoriere Response');
                return;
            }
            
            if (!data || typeof data !== 'object') {
                console.error('[DEBUG] Invalid response data:', data);
                throw new Error('Ungültige Response-Daten');
            }
            
            if (data.status === 'completed') {
                console.log('[DEBUG] Status completed - stoppe Polling');
                isCompleted = true; // Flag setzen BEVOR clearInterval
                clearInterval(pollInterval);
                handleAsyncResult(data.result);
            } else if (data.status === 'error') {
                console.log('[DEBUG] Status error - stoppe Polling');
                isCompleted = true; // Flag setzen BEVOR clearInterval
                clearInterval(pollInterval);
                handleAsyncError(data.message);
            } else if (data.status === 'processing') {
                // Echten Progress verwenden falls verfügbar
                if (data.progress) {
                    updateRealProgress(data.progress);
                } else {
                    // Fallback auf Polling-basierte Progress
                    updateAsyncProgress(pollCount, maxPolls);
                }
            }
        })
        .catch(error => {
            console.error('[DEBUG] Polling Fehler:', error);
            // Race Condition: Ignoriere weitere Fehler wenn bereits completed
            if (isCompleted) {
                console.log('[DEBUG] Bereits completed - ignoriere Fehler');
                return;
            }
            
            // NICHT sofort abbrechen - erst nach mehreren Fehlern
            if (pollCount >= 5) {
                console.log('[DEBUG] Zu viele Polling-Fehler - stoppe Polling');
                isCompleted = true;
                clearInterval(pollInterval);
                handleAsyncError('Verbindungsfehler beim Statuscheck: ' + error.message);
            }
        });
        
        // Timeout nach max Polls
        if (pollCount >= maxPolls && !isCompleted) {
            console.log('[DEBUG] Polling Timeout erreicht - stoppe Polling');
            isCompleted = true;
            clearInterval(pollInterval);
            handleAsyncError('Timeout: Verarbeitung dauert zu lange (3 Minuten überschritten)');
        }
        
    }, 2000); // Alle 2 Sekunden prüfen (schneller)
    
    console.log('[DEBUG] Polling gestartet');
}

/**
 * Behandelt erfolgreiches Async-Ergebnis
 */
function handleAsyncResult(result) {
    console.log('[DEBUG] handleAsyncResult aufgerufen - Direkte Weiterleitung ohne Message');
    
    // Alle Animationen stoppen
    stopLoadingAnimations();
    
    // 100% Progress: Alle Balken grün färben
    updateSpinnerProgress(100);
    
    const percentageElement = document.querySelector('.loading-percentage');
    if (percentageElement) {
        percentageElement.textContent = '100%';
    }
    
    // Redirect zur Ergebnisseite basierend auf dem Result
    const checkId = result.checkId;
    const isSuccess = result.isSuccess;
    
    // AJAX-Konfiguration aus Template laden (jumpTo aus Backend)
    const config = window.CaeliAreaCheckConfig || {};
    let resultPageUrl = config.detailPageUrl;
    
    if (!resultPageUrl) {
        // Fallback: Smart-Detection der korrekten Result-URL
        const currentUrl = window.location.href;
        
        if (currentUrl.includes('/flaechencheck/')) {
            // Standardfall: Wir sind auf der Flaechencheck-Seite
            resultPageUrl = currentUrl.replace('/flaechencheck/', '/flaechencheck-ergebnis/');
        } else {
            // Fallback: Relative Navigation zur Ergebnisseite
            const urlParts = currentUrl.split('/');
            urlParts[urlParts.length - 1] = 'flaechencheck-ergebnis';
            resultPageUrl = urlParts.join('/');
        }
    }
    
    // URL-Parameter hinzufügen
    const separator = resultPageUrl.includes('?') ? '&' : '?';
    if (isSuccess) {
        resultPageUrl += `${separator}parkid=${encodeURIComponent(checkId)}`;
    } else {
        resultPageUrl += `${separator}checkid=${encodeURIComponent(checkId)}`;
    }
    
    console.log('[DEBUG] Leite weiter zu:', resultPageUrl);
    console.log('[DEBUG] Aktueller window.location:', window.location.href);
    console.log('[DEBUG] Result-Object:', result);
    
    // DEBUGGING: Mehrere Redirect-Methoden versuchen
    try {
        console.log('[DEBUG] Versuche window.location.href...');
        window.location.href = resultPageUrl;
        
        // Fallback falls href nicht funktioniert  
        setTimeout(() => {
            console.log('[DEBUG] Fallback 1: window.location.assign...');
            window.location.assign(resultPageUrl);
        }, 100);
        
        // Zweiter Fallback
        setTimeout(() => {
            console.log('[DEBUG] Fallback 2: window.location.replace...');
            window.location.replace(resultPageUrl);
        }, 200);
        
    } catch (error) {
        console.error('[DEBUG] Redirect Error:', error);
        // Notfall-Fallback mit Link
        alert('Redirect zu: ' + resultPageUrl);
    }
}

/**
 * Aktualisiert das SVG-Spinner basierend auf Progress-Prozent
 * Färbt Balken von 12 Uhr (data-index="0") im Uhrzeigersinn mit #113634
 */
function updateSpinnerProgress(percentage) {
    const spinnerContainer = document.querySelector('.loading-spinner');
    if (!spinnerContainer) return;
    
    const bars = spinnerContainer.querySelectorAll('.spinner-bar');
    if (bars.length === 0) return;
    
    // 12 Balken = 100% / 12 = ca. 8.33% pro Balken
    const barsToFill = Math.floor((percentage / 100) * bars.length);
    
    // Alle Balken zurücksetzen auf Standard-Farbe
    bars.forEach(bar => {
        bar.setAttribute('fill', '#DEEEC6');
    });
    
    // Balken von 12 Uhr (Index 0) im Uhrzeigersinn färben
    for (let i = 0; i < barsToFill; i++) {
        const bar = spinnerContainer.querySelector(`[data-index="${i}"]`);
        if (bar) {
            bar.setAttribute('fill', '#113634');
        }
    }
    
    // Bei exakt 100% alle Balken färben
    if (percentage >= 100) {
        bars.forEach(bar => {
            bar.setAttribute('fill', '#113634');
        });
    }
}

/**
 * Aktualisiert Progress-Indikator (Fallback auf Polling-Zeit)
 */
function updateAsyncProgress(currentPoll, maxPolls) {
    const progressPercent = Math.min(Math.round((currentPoll / maxPolls) * 100), 95);
    
    // Optional: Progress im Loading-Text anzeigen
    const textElement = document.querySelector('.loading-percentage');
    if (textElement && progressPercent > 30) {
        // Nach 30% gelegentlich Progress anzeigen
        if (currentPoll % 5 === 0) {
            const translations = window.CaeliAreaCheckTranslations || {};
            const progressText = translations.loading?.progress || 'Fortschritt';
            updateLoadingText(`${progressText}: ${progressPercent}%`);
        }
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
