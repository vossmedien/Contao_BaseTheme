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
            'gb': /([A-Z]{1,2}\d{1,2}[A-Z]?(\s?\d[A-Z]{2})?)|([A-Z]{2}\d{1,2}(\s?\d[A-Z]{2})?)/i, // UK: SW1A, SW1A 1AA, M1, M1 1AA, B33 8TH
            'ie': /\b[A-Z]\d{2}\s?[A-Z0-9]{4}\b/, // Irland: D02 XY45
            // Fallback für unbekannte Länder: flexiblere Validation
            'default': /\b\d{3,5}(\s?[A-Z]{0,3})?\b/
        };
        
        return regexMap[countryCode] || regexMap.default;
    },

    validatePLZ(input, place = null) {
        // Sehr lockere Validierung für bessere UX
        if (!input || input.length < 2) {
            return false;
        }
        
        // Bei Place-Objekt: Wenn Google es gefunden hat, ist es definitiv gültig
        if (place && place.formatted_address) {
            return true;
        }
        
        // Intelligent prüfen ob es eine sinnvolle Adresseingabe ist
        const trimmedInput = input.trim();
        
        // Vollständige Adressen (enthalten sowohl Zahlen als auch Buchstaben)
        if (/\d/.test(trimmedInput) && /[a-zA-ZäöüÄÖÜß]/.test(trimmedInput)) {
            return true;
        }
        
        // PLZ-Pattern für verschiedene Länder prüfen
        const plzPatterns = [
            /^\d{5}$/,                    // Deutschland: 12345
            /^\d{4}$/,                    // Österreich/Schweiz: 1234
            /^\d{4}\s?[A-Z]{2}$/i,        // Niederlande: 1234 AB
            /^\d{2}-?\d{3}$/,             // Polen: 12-345
            /^[A-Z]\d{2}\s?[A-Z0-9]{4}$/i // Irland: D02 XY45
        ];
        
        // Direkte PLZ-Eingabe
        if (plzPatterns.some(pattern => pattern.test(trimmedInput))) {
            return true;
        }
        
        // Stadtnamen oder längere Eingaben akzeptieren
        if (trimmedInput.length > 3 && /[a-zA-ZäöüÄÖÜß]/.test(trimmedInput)) {
            return true;
        }
        
        return false;
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
            title: tutorial.welcome?.title,
            content: tutorial.welcome?.content,
            placement: 'left',
            placementMobile: 'bottom',
            trigger: 'init',
            template: 'welcome'
        },
        {
            // Schritt 1: Adresse/Ort eingeben
            element: '#place-autocomplete',
            elementMobile: '#controls', // Auf Mobile an der Box ausrichten
            title: tutorial.plz_input?.title,
            content: tutorial.plz_input?.content,
            placement: 'right',
            placementMobile: 'bottom',
            trigger: 'manual',
            template: 'plz'
        },
        {
            // Schritt 2: Polygon zeichnen - am rechten Bildschirmrand (letzter Schritt)
            element: '#controls',
            title: tutorial.polygon_edit?.title,
            content: tutorial.polygon_edit?.content,
            placement: 'left',
            placementMobile: 'bottom',
            trigger: 'polygon',
            template: 'polygon'
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
        gestureHandling: 'greedy', // Bessere Mobile-Bedienung
        streetViewControl: false, // Street View Icon ausblenden
        fullscreenControl: false // Vollbild Icon ausblenden
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
        // Focus Event Listener für Tutorial UND Autocomplete-Wiederherstellung
        input.addEventListener('focus', function(e) {
            handleInputFocus(); // Tutorial-Logik
            
            // Autocomplete wieder anzeigen wenn bereits Text vorhanden
            const query = e.target.value.trim();
            if (query.length >= 2 && currentSuggestions.length > 0) {
                showSuggestions(currentSuggestions);
            } else if (query.length >= 2) {
                // Neue Suche starten wenn keine cached Suggestions vorhanden
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    searchPlaces(query);
                }, 200);
            }
        });
        
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
        
        // Länder-Beschränkung aus Config laden
        const config = window.CaeliAreaCheckConfig || {};
        const allowedCountries = config.allowedCountries || ['de']; // Fallback auf Deutschland
        
        const geocodeRequest = {
            address: query
        };
        
        // ComponentRestrictions für Länder-Beschränkung hinzufügen
        if (allowedCountries.length > 0) {
            // Google Maps API erwartet für Geocoding einen String (bei einem Land) oder Array (bei mehreren)
            // Wichtig: allowedCountries muss ein Array von ISO-Codes sein
            geocodeRequest.componentRestrictions = {
                country: allowedCountries.length === 1 ? allowedCountries[0] : allowedCountries
            };
        }
        
        CaeliAreaCheck.geocoder.geocode(geocodeRequest, function(results, status) {
            if (status === 'OK' && results && results.length > 0) {
                // Alle Ergebnisse erstmal anzeigen - PLZ-Check erst beim Auswählen
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
            
            // Bessere Anzeige: Nur Ort und Land, keine PLZ
            const displayText = getCleanDisplayText(place);
            item.innerHTML = displayText;
            
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
    
    function getCleanDisplayText(place) {
        if (!place || !place.address_components) {
            return place.formatted_address || '';
        }
        
        // Intelligente Anzeige basierend auf verfügbaren Adresskomponenten
        let streetNumber = '';
        let route = '';
        let locality = '';
        let postalCode = '';
        let country = '';
        let administrativeArea = '';
        
        // Alle relevanten Komponenten extrahieren
        place.address_components.forEach(component => {
            const types = component.types;
            
            if (types.includes('street_number')) {
                streetNumber = component.long_name;
            }
            if (types.includes('route')) {
                route = component.long_name;
            }
            if (types.includes('locality')) {
                locality = component.long_name;
            }
            if (types.includes('postal_code')) {
                postalCode = component.long_name;
            }
            if (types.includes('country')) {
                country = component.long_name;
            }
            if (types.includes('administrative_area_level_1')) {
                administrativeArea = component.long_name;
            }
        });
        
        // Straßenadresse vorhanden? (Vollständige Adresse anzeigen)
        if (route && locality) {
            let street = route;
            if (streetNumber) {
                street = `${route} ${streetNumber}`;
            }
            
            if (postalCode) {
                // Nur vollständige PLZ verwenden (mindestens 4 Stellen für Deutschland)
                if (country && country.toLowerCase().includes('deutsch') && postalCode.length >= 4) {
                    return `${street}, ${postalCode} ${locality}`;
                } else if (!country || !country.toLowerCase().includes('deutsch')) {
                    // Andere Länder: PLZ wie sie ist
                    return `${street}, ${postalCode} ${locality}`;
                } else {
                    // Unvollständige deutsche PLZ: Ohne PLZ anzeigen
                    return `${street}, ${locality}`;
                }
            } else {
                return `${street}, ${locality}`;
            }
        }
        
        // Nur Ort mit PLZ? (PLZ + Ort anzeigen)
        if (locality && postalCode) {
            // Nur vollständige PLZ verwenden (mindestens 4 Stellen für Deutschland)
            if (country && country.toLowerCase().includes('deutsch') && postalCode.length >= 4) {
                return `${postalCode} ${locality}`;
            } else if (!country || !country.toLowerCase().includes('deutsch')) {
                // Andere Länder: PLZ wie sie ist
                return `${postalCode} ${locality}`;
            } else {
                // Unvollständige deutsche PLZ: Nur Ort anzeigen
                return locality;
            }
        }
        
        // Nur Ort ohne PLZ? (Ort + Land anzeigen, aber Duplikate vermeiden)
        if (locality && country) {
            // Vermeide "Deutschland, Deutschland" - prüfe ob Ort bereits Länderinfo enthält
            if (locality.toLowerCase().includes(country.toLowerCase()) || 
                country.toLowerCase().includes(locality.toLowerCase())) {
                return locality;
            }
            return `${locality}, ${country}`;
        }
        
        // Nur PLZ-Bereich? Administrative Area verwenden
        if (postalCode && administrativeArea && !locality) {
            // Nur vollständige PLZ verwenden (mindestens 4 Stellen für Deutschland)
            if (country && country.toLowerCase().includes('deutsch') && postalCode.length >= 4) {
                if (country && !administrativeArea.toLowerCase().includes(country.toLowerCase())) {
                    return `${postalCode} ${administrativeArea}, ${country}`;
                } else {
                    return `${postalCode} ${administrativeArea}`;
                }
            } else if (!country || !country.toLowerCase().includes('deutsch')) {
                // Andere Länder: PLZ wie sie ist
                if (country && !administrativeArea.toLowerCase().includes(country.toLowerCase())) {
                    return `${postalCode} ${administrativeArea}, ${country}`;
                } else {
                    return `${postalCode} ${administrativeArea}`;
                }
            } else {
                // Unvollständige deutsche PLZ: Nur administrativeArea anzeigen
                if (country && !administrativeArea.toLowerCase().includes(country.toLowerCase())) {
                    return `${administrativeArea}, ${country}`;
                } else {
                    return administrativeArea;
                }
            }
        }
        
        // Fallback: Bereinige formatted_address
        let cleanAddress = place.formatted_address;
        
        // Entferne redundante Länderinformationen bei deutschen Adressen
        if (country && country.toLowerCase() === 'deutschland') {
            // Entferne ", Deutschland" am Ende
            cleanAddress = cleanAddress.replace(/, Deutschland$/, '');
            // Entferne ", Germany" am Ende (falls englische Version)
            cleanAddress = cleanAddress.replace(/, Germany$/, '');
        }
        
        // Entferne Plus Codes am Anfang
        cleanAddress = cleanAddress.replace(/^[A-Z0-9]{4}\+[A-Z0-9]{2},?\s*/, '');
        
        return cleanAddress || place.formatted_address;
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
        
        // Input-Wert mit intelligenter Anzeige setzen
        input.value = getCleanDisplayText(place);
        hideDropdown();
        
        // PLZ-Validation - bei vollständigen Adressen immer als gültig betrachten
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
    
    function showNoPLZMessage() {
        if (!dropdown) return;
        
        dropdown.innerHTML = '';
        selectedIndex = -1;
        currentSuggestions = [];
        
        const translations = window.CaeliAreaCheckTranslations || {};
        const message = translations.interface?.no_plz_message || 'Bitte geben Sie eine Adresse mit Postleitzahl ein.';
        
                const messageItem = document.createElement('div');
        messageItem.className = 'autocomplete-message';
        messageItem.innerHTML = `
            <small>${message}</small>
        `;
        
        dropdown.appendChild(messageItem);
        dropdown.style.display = 'block';
    }
    


    // Map-Event-Listener für sauberes Polygon-Mitbewegen
    google.maps.event.addListener(CaeliAreaCheck.map, 'center_changed', function() {
        if (CaeliAreaCheck.polygon && 
            CaeliAreaCheck.state.polygonRelativeOffset && 
            !CaeliAreaCheck.state.isPolygonBeingEdited) {
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
    
    // Länder-Beschränkung aus Config laden
    const config = window.CaeliAreaCheckConfig || {};
    const allowedCountries = config.allowedCountries || ['de']; // Fallback auf Deutschland
    
    const geocodeRequest = {
        address: address
    };
    
    // ComponentRestrictions für Länder-Beschränkung hinzufügen
    if (allowedCountries.length > 0) {
        // Google Maps API erwartet für Geocoding einen String (bei einem Land) oder Array (bei mehreren)
        // Wichtig: allowedCountries muss ein Array von ISO-Codes sein
        geocodeRequest.componentRestrictions = {
            country: allowedCountries.length === 1 ? allowedCountries[0] : allowedCountries
        };
        //console.log('[DEBUG] Geocoding country restriction:', geocodeRequest.componentRestrictions.country);
    }
    
    CaeliAreaCheck.geocoder.geocode(geocodeRequest, function(results, status) {
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

    // Event Listener für Polygon-Änderungen - LIVE UPDATES
    const polygonPath = CaeliAreaCheck.polygon.getPath();
    
    // Event Listener für alle Änderungen am Polygon-Pfad (Mobile-optimiert)
    google.maps.event.addListener(polygonPath, 'set_at', function() {
        //console.log('[DEBUG] Polygon set_at event - updating area');
        // Kurze Verzögerung für Mobile-Performance
        setTimeout(() => {
            updateAreaLabel();
            updateGeometryField();
        }, 50);
    });
    
    google.maps.event.addListener(polygonPath, 'insert_at', function() {
        //console.log('[DEBUG] Polygon insert_at event - updating area');
        setTimeout(() => {
            updateAreaLabel();
            updateGeometryField();
        }, 50);
    });
    
    google.maps.event.addListener(polygonPath, 'remove_at', function() {
        //console.log('[DEBUG] Polygon remove_at event - updating area');
        setTimeout(() => {
            updateAreaLabel();
            updateGeometryField();
        }, 50);
    });
    
    // Event Listener für Drag-Operationen des gesamten Polygons
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'dragstart', function() {
        //console.log('[DEBUG] Polygon dragstart');
        CaeliAreaCheck.state.isPolygonBeingEdited = true;
    });
    
    // Event Listener für Vertex-Editing Start
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'mousedown', function() {
        //console.log('[DEBUG] Polygon mousedown - potential vertex edit start');
        CaeliAreaCheck.state.isPolygonBeingEdited = true;
    });
    
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'drag', function() {
        // LIVE UPDATE während des Ziehens des gesamten Polygons
        //console.log('[DEBUG] Polygon drag - live update');
        updateAreaLabel();
        updateGeometryField();
    });
    
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'dragend', function() {
        //console.log('[DEBUG] Polygon dragend');
        updatePolygonOffset(); // Neuen Offset nach User-Dragging berechnen
        updateAreaLabel();
        updateGeometryField();
        CaeliAreaCheck.state.isPolygonBeingEdited = false;
    });
    
    // WICHTIG: Event Listener für Polygon-Editing (Eckpunkte ziehen mit Dots)
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'mouseup', function() {
        //console.log('[DEBUG] Polygon mouseup - vertex edit completed');
        CaeliAreaCheck.state.isPolygonBeingEdited = false; // Flag zurücksetzen
        setTimeout(() => {
            updateAreaLabel();
            updateGeometryField();
        }, 100); // Etwas länger für Edit-Operations
    });
    
    // Event für Touch-Geräte (Mobile)
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'touchend', function() {
        //console.log('[DEBUG] Polygon touchend - vertex edit completed');
        CaeliAreaCheck.state.isPolygonBeingEdited = false; // Flag zurücksetzen
        setTimeout(() => {
            updateAreaLabel();
            updateGeometryField();
        }, 100);
    });
    
    // Zusätzliche Events für robustes Vertex-Editing
    google.maps.event.addListener(CaeliAreaCheck.polygon, 'click', function() {
        //console.log('[DEBUG] Polygon click - potential vertex edit');
        setTimeout(() => {
            updateAreaLabel();
            updateGeometryField();
        }, 150);
    });
    
    // Global map mouseup Event als Fallback für alle Polygon-Änderungen
    google.maps.event.addListener(CaeliAreaCheck.map, 'mouseup', function() {
        // Nur wenn Polygon existiert und nicht gerade verschoben wird
        if (CaeliAreaCheck.polygon && !CaeliAreaCheck.state.isPolygonBeingEdited) {
            setTimeout(() => {
                updateAreaLabel();
                updateGeometryField();
            }, 200);
        }
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
    // Altes Label korrekt entfernen
    if (CaeliAreaCheck.areaLabel) {
        CaeliAreaCheck.areaLabel.map = null;
        CaeliAreaCheck.areaLabel = null;
    }

    // Sicherstellen, dass Polygon existiert und mindestens 3 Punkte hat
    if (!CaeliAreaCheck.polygon || !CaeliAreaCheck.polygon.getPath()) {
        console.warn('[DEBUG] updateAreaLabel: Polygon oder Path nicht verfügbar');
        return;
    }
    
    const path = CaeliAreaCheck.polygon.getPath();
    if (path.getLength() < 3) {
        console.warn('[DEBUG] updateAreaLabel: Polygon hat weniger als 3 Punkte');
        return;
    }

    // Prüfen ob Google Maps Geometry Library verfügbar ist
    if (!google.maps.geometry || !google.maps.geometry.spherical) {
        console.error('[DEBUG] updateAreaLabel: Google Maps Geometry Library nicht verfügbar');
        return;
    }

    try {
        const area = google.maps.geometry.spherical.computeArea(path);
        const areaInHectares = (area / 10000).toFixed(2);
        //console.log('[DEBUG] updateAreaLabel: Berechnet', areaInHectares, 'ha für', path.getLength(), 'Punkte');

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
        
    } catch (error) {
        console.error('[DEBUG] updateAreaLabel: Fehler bei Flächenberechnung:', error);
        // Fallback: Zeige Fehler im Label an
        const labelDiv = document.createElement('div');
        labelDiv.style.background = '#ffcccc';
        labelDiv.style.borderRadius = '12px';
        labelDiv.style.padding = '2px 8px';
        labelDiv.style.fontWeight = 'bold';
        labelDiv.style.fontSize = '16px';
        labelDiv.style.color = 'red';
        labelDiv.textContent = 'Fehler';
        
        if (CaeliAreaCheck.map) {
            const bounds = new google.maps.LatLngBounds();
            CaeliAreaCheck.polygon.getPath().forEach(function(latLng) {
                bounds.extend(latLng);
            });
            const center = bounds.getCenter();
            
            const { AdvancedMarkerElement } = google.maps.marker;
            CaeliAreaCheck.areaLabel = new AdvancedMarkerElement({
                map: CaeliAreaCheck.map,
                position: center,
                content: labelDiv
            });
        }
    }
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
                
                            // Form-Submit starten (verzögert, damit Geocoding abgeschlossen ist)
            submitFormWithRedirect();
            });
        } else {
            // Fallback ohne Reverse Geocoding: ursprünglich eingegebene Adresse verwenden
            const searchInput = document.getElementById('place-autocomplete');
            const hiddenAddress = document.getElementById('searched-address-field');
            if (searchInput && hiddenAddress) {
                hiddenAddress.value = searchInput.value;
            }
            
            // Form-Submit starten
            submitFormWithRedirect();
        }
    } else {
        const translations = window.CaeliAreaCheckTranslations || {};
        showDynamicAlert('select_area_first', translations);
    }
}

// Geocoding-Fallback-Funktion entfernt (wird nicht verwendet)

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

// Midpoint-Refresh und Event-Listener-Manipulation entfernt
// Polygon-Interaktion funktioniert jetzt stabil ohne komplexe Workarounds

// Saubere Implementierung des Polygon-Mitbewegens
function movePolygonWithMap() {
    if (!CaeliAreaCheck.polygon || !CaeliAreaCheck.state.polygonRelativeOffset || !CaeliAreaCheck.map) return;
    
    const mapCenter = CaeliAreaCheck.map.getCenter();
    
    // Mobile-Offset berücksichtigen
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
    
    // Nur verschieben wenn tatsächlich notwendig (Performance-Optimierung)
    if (Math.abs(deltaLat) < 0.000001 && Math.abs(deltaLng) < 0.000001) {
        return; // Kein merklicher Unterschied
    }
    
    // Temporär Editing-Flag setzen um Rekursion zu vermeiden
    const wasBeingEdited = CaeliAreaCheck.state.isPolygonBeingEdited;
    CaeliAreaCheck.state.isPolygonBeingEdited = true;
    
    // Neue Polygon-Punkte berechnen
    const path = CaeliAreaCheck.polygon.getPath();
    const newPath = [];
    for (let i = 0; i < path.getLength(); i++) {
        const point = path.getAt(i);
        newPath.push(new google.maps.LatLng(
            point.lat() + deltaLat,
            point.lng() + deltaLng
        ));
    }
    
    // Polygon verschieben
    CaeliAreaCheck.polygon.setPath(newPath);
    
    // Area-Label mitbewegen
    updateAreaLabel();
    updateGeometryField();
    
    // State sofort zurücksetzen
    CaeliAreaCheck.state.isPolygonBeingEdited = wasBeingEdited;
}

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
    CaeliAreaCheck.state.polygonRelativeOffset = {
        lat: polygonCenter.lat() - mapCenter.lat(),
        lng: polygonCenter.lng() - mapCenter.lng()
    };
}

// Form-Handler - NORMALE Form-Submit (kein AJAX mehr)
const parkForm = document.getElementById('park-form');
if (parkForm) {
    parkForm.addEventListener('submit', function(e) {
        e.preventDefault(); // IMMER verhindern
        
        // Verhindere doppelte Submissions
        if (CaeliAreaCheck.state.formSubmissionInProgress) {
            return;
        }
        
        // PLZ-Validierung vor Submit
        const input = document.getElementById('place-autocomplete');
        if (input && input.value) {
            const isValidPLZ = Utils.validatePLZ(input.value);
            if (!isValidPLZ) {
                const translations = window.CaeliAreaCheckTranslations || {};
                showDynamicAlert('invalid_postal_code', translations);
                return;
            }
        }
        
        // Polygon muss existieren
        if (!CaeliAreaCheck.polygon) {
            const translations = window.CaeliAreaCheckTranslations || {};
            showDynamicAlert('select_area_first', translations);
            return;
        }
        
        CaeliAreaCheck.state.formSubmissionInProgress = true;
        
        // Wert aus dem Suchfeld holen und ins Hidden-Feld schreiben
        const searchInput = document.getElementById('place-autocomplete');
        const hiddenAddress = document.getElementById('searched-address-field');
        if (searchInput && hiddenAddress) {
            hiddenAddress.value = searchInput.value;
        }
        
        // Loading anzeigen und normale Form-Submit
        submitFormWithRedirect();
    });
}

// Consent Management für Google Maps und HubSpot

function checkConsent() {
    let hasGoogleMapsConsent = false;

    if (typeof __cmp === 'function') {
        try {
            const cmpData = __cmp('getCMPData');
            if (cmpData && cmpData.vendorConsents) {
                hasGoogleMapsConsent = cmpData.vendorConsents.s1104 || false; // Google Maps
                // HubSpot Consent wird nicht mehr geprüft
            }
        } catch (e) {
            console.warn('CMP-Fehler:', e);
        }
    }

    return hasGoogleMapsConsent; // Nur Google Maps Consent erforderlich
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
            // HubSpot Consent wird nicht mehr automatisch aktiviert

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

    // Prüfe ob Google Maps bereits geladen ist
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        // Prüfe ob Geometry Library verfügbar ist
        if (!google.maps.geometry || !google.maps.geometry.spherical) {
            console.warn('[DEBUG] Google Maps Geometry Library nicht verfügbar - lade nach');
            // Versuche Geometry Library nachzuladen
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?libraries=geometry&callback=onGeometryLoaded';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
            
            window.onGeometryLoaded = function() {
                //console.log('[DEBUG] Geometry Library nachgeladen');
                initMap();
                CaeliAreaCheck.state.mapInitialized = true;
                setTimeout(initTutorial, 100);
            };
        } else {
            //console.log('[DEBUG] Google Maps und Geometry Library verfügbar');
            initMap();
            CaeliAreaCheck.state.mapInitialized = true;
            // Tutorial sofort starten
            setTimeout(initTutorial, 100);
        }
    } else {
        //console.log('[DEBUG] Google Maps Script wird nach Consent geladen');
        
        // Google Maps Script dynamisch laden
        const script = document.createElement('script');
        script.async = true;
        
        // API Key und Libraries aus Template-Variablen
        const googleMapsApiKey = window.CaeliAreaCheckConfig?.googleMapsApiKey || '';
        if (!googleMapsApiKey) {
            console.error('[DEBUG] Google Maps API Key nicht verfügbar');
            return;
        }
        
        script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey}&libraries=places,drawing,geometry,marker&v=weekly&loading=async&callback=onGoogleMapsLoaded`;
        
        // Callback für geladenes Script
        window.onGoogleMapsLoaded = function() {
            //console.log('[DEBUG] Google Maps Script erfolgreich geladen');
            let tries = 0;
            function tryInit() {
                if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                    initMap();
                    CaeliAreaCheck.state.mapInitialized = true;
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
        };
        
        document.head.appendChild(script);
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

// Polygon wird bei Ortswechsel direkt neu erstellt

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
    //console.log('showTutorialStep called with stepIndex:', stepIndex);
    
    const tutorialSteps = getTutorialSteps();
    if (stepIndex >= tutorialSteps.length) {
        completeTutorial();
        return;
    }

    const step = tutorialSteps[stepIndex];
    //console.log('Step:', step);
    
    const isMobile = Utils.isMobile();
    const targetElement = isMobile && step.elementMobile ? step.elementMobile : step.element;
    //console.log('Target element:', targetElement);
    
    const element = document.querySelector(targetElement);

    if (!element) {
        console.warn(`Tutorial element ${targetElement} not found`);
        return;
    }
    
    //console.log('Element found:', element);

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

    // Bootstrap Popover erstellen - Close-Button wird nachträglich hinzugefügt
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

        // Close-Button dynamisch zum Header hinzufügen
        const popoverHeader = popoverElement.querySelector('.popover-header');
        if (popoverHeader && !popoverHeader.querySelector('.tutorial-close-btn')) {
            // Header-Inhalt in Flex-Container wrappen
            const titleText = popoverHeader.innerHTML;
            popoverHeader.innerHTML = `
                <div class="d-flex justify-content-between align-items-start w-100">
                    <span class="tutorial-title">${titleText}</span>
                    <button type="button" class="tutorial-close-btn" aria-label="Tutorial schließen">&times;</button>
                </div>
            `;
        }

        // Event Listener für Schließen-Button hinzufügen
        const closeButton = popoverElement.querySelector('.tutorial-close-btn');
        if (closeButton) {
            closeButton.addEventListener('click', completeTutorial);
        }

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
                //console.log('PLZ Back button found');
                backButton.addEventListener('click', () => {
                    //console.log('PLZ Back button clicked');
                    previousTutorialStep(false);
                });
                //console.log('PLZ Back button event listener added');
            } else {
                //console.log('PLZ Back button NOT found');
            }
        } else if (step.template === 'polygon') {
            // Polygon-Schritt (2) - letzter Schritt
            const backButton = popoverElement.querySelector('#tutorial-polygon-back');
            const nextButton = popoverElement.querySelector('#tutorial-polygon-next');
            
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
        //console.log('Previous step called, current step:', CaeliAreaCheck.state.currentTutorialStep, 'fromPolygonStep:', fromPolygonStep);
        
        // Aktuelles Popover schließen
        hideAllPopovers();

        // Spezielle Behandlung beim Zurückgehen von Schritt 2 zu Schritt 1
        if (CaeliAreaCheck.state.currentTutorialStep === 2) {
            // Input leeren, damit User PLZ neu eingeben muss
            const input = document.getElementById('place-autocomplete');
            if (input) {
                input.value = '';
                // PLZ-Validierung zurücksetzen
                updateSubmitButtonState(false);
                // Nur Fokus setzen wenn nicht vom Polygon-Back (fromPolygonStep = false)
                if (!fromPolygonStep) {
                    setTimeout(() => input.focus(), 200);
                }
            }
            
            // Polygon und Buttons verstecken
            deletePolygon();
            Utils.safeElementAction('#button-wrapper', el => el.style.display = 'none');
            
            // Tutorial-State temporär deaktivieren um Auto-Sprung zu verhindern
            const wasActive = CaeliAreaCheck.state.tutorialActive;
            CaeliAreaCheck.state.tutorialActive = false;
            
            // Nach kurzer Verzögerung wieder aktivieren
            setTimeout(() => {
                CaeliAreaCheck.state.tutorialActive = wasActive;
            }, 500);
        }

        // Spezielle Behandlung beim Zurückgehen von Schritt 1 zu Schritt 0 (PLZ-Back)
        if (CaeliAreaCheck.state.currentTutorialStep === 1 && !fromPolygonStep) {
            //console.log('Going from step 1 to step 0');
            // Input leeren, damit User wieder von vorne beginnt
            const input = document.getElementById('place-autocomplete');
            if (input) {
                input.value = '';
                updateSubmitButtonState(false);
            }
        }

        CaeliAreaCheck.state.currentTutorialStep--;
        //console.log('New step:', CaeliAreaCheck.state.currentTutorialStep);
        
        // Kurz warten bevor neues Popover angezeigt wird
        setTimeout(() => {
            showTutorialStep(CaeliAreaCheck.state.currentTutorialStep);
        }, 200);
    }
}

function hideAllPopovers() {
    //console.log('hideAllPopovers called');
    
    // Alle sichtbaren Popovers entfernen
    const existingPopovers = document.querySelectorAll('.popover');
    //console.log('Found existing popovers:', existingPopovers.length);
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
            //console.log('Removing popover from:', selector);
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
    // Polygon wurde erstellt - Tutorial bleibt bei Schritt 2 (letzter Schritt)
    // Keine automatische Weiterleitung mehr, da Schritt 2 der letzte Schritt ist
    //console.log('[DEBUG] Polygon created - Tutorial bleibt bei Schritt 2');
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

// Loading-Text-Update-Funktion entfernt (wird nicht verwendet)

function startLoadingAnimation() {
    // Übersetzungen laden
    const translations = window.CaeliAreaCheckTranslations || {};
    const loadingTextsObj = translations.loading?.texts || {};
    
    // Texte für Wechsel vorbereiten
    const loadingTexts = [
        loadingTextsObj.checking_area,
        loadingTextsObj.wind_conditions,
        loadingTextsObj.restrictions_check,
        loadingTextsObj.grid_connection,
        loadingTextsObj.analyzing_potential,
        loadingTextsObj.checking_nature,
        loadingTextsObj.checking_distances,
        loadingTextsObj.evaluating_quality
    ];
    
    let currentTextIndex = 0;
    let hasReached100 = false; // Flag für 100% Status
    
    // Spinner initialisieren
    initLoadingSpinner();
    
    const messageElement = document.querySelector('.loading-message');
    const percentageElement = document.querySelector('.loading-percentage');
    
    if (messageElement) {
        messageElement.textContent = loadingTexts[currentTextIndex];
        messageElement.style.opacity = '1';
    }
    
    if (percentageElement) {
        percentageElement.textContent = '0%';
    }
    
    // Langsamere Progress-Simulation mit kontinuierlichem Fortschritt
    let progress = 0;
    const progressInterval = setInterval(() => {
        if (progress < 70) {
            // Etwas schneller bis 70%
            progress += Math.random() * 1.2 + 0.6; // 0.6-1.8% pro Schritt
        } else if (progress < 90) {
            // Etwas schneller von 70-90%
            progress += Math.random() * 0.7 + 0.3; // 0.3-1.0% pro Schritt
        } else if (progress < 99) {
            // Etwas schneller von 90-99%
            progress += Math.random() * 0.3 + 0.2; // 0.2-0.5% pro Schritt
        } else if (progress < 100) {
            // Erreiche 100% nach einer gewissen Zeit
            progress = 100;
            hasReached100 = true;
            
            // Text ändern wenn 100% erreicht ist
            if (messageElement) {
                const pleaseWaitText = loadingTextsObj.please_wait || 'Bitte haben Sie noch etwas Geduld';
                messageElement.textContent = pleaseWaitText;
            }
        }
        
        if (percentageElement) {
            percentageElement.textContent = Math.round(progress) + '%';
        }
        updateSpinnerProgress(progress);
        
        // Intervall niemals stoppen - läuft bis Seitenwechsel
    }, 400); // Etwas schnellere Intervalle: 400ms statt 500ms
    
    // Text-Wechsel alle 2 Sekunden (stoppt bei 100%)
    let textChangeCount = 0;
    const textInterval = setInterval(() => {
        // Text-Wechsel stoppen wenn 100% erreicht ist
        if (hasReached100) {
            return;
        }
        
        if (messageElement && loadingTexts.length > 1) {
            textChangeCount++;
            
            // Sanftes Ausblenden
            messageElement.style.transition = 'opacity 0.5s ease-out';
            messageElement.style.opacity = '0';
            
            setTimeout(() => {
                // Nur wechseln wenn noch nicht 100% erreicht
                if (!hasReached100) {
                    // Nächsten Text setzen
                    currentTextIndex = (currentTextIndex + 1) % loadingTexts.length;
                    messageElement.textContent = loadingTexts[currentTextIndex];
                    
                    // Sanftes Einblenden
                    messageElement.style.transition = 'opacity 0.7s ease-in';
                    messageElement.style.opacity = '1';
                }
            }, 500);
        }
    }, 2000);
    
    // Intervalle für eventuelles Cleanup speichern (optional)
    window.caeliLoadingIntervals = { progressInterval, textInterval };
}

function submitFormWithRedirect() {
    //console.log('=== submitFormWithRedirect aufgerufen ===');
    
    const parkForm = document.getElementById('park-form');
    if (!parkForm) {
        console.error('Form nicht gefunden');
        return false;
    }
    
    // Submit-Button finden und deaktivieren (ohne Text zu ändern)
    const submitButton = document.querySelector('button[onclick*="submitFormWithRedirect"], .btn[onclick*="submitFormWithRedirect"]');
    if (submitButton) {
        submitButton.disabled = true;
    }
    
    showLoadingOverlay();
    startLoadingAnimation();
    
    // Token-Refresh vor Submit (da submitFormWithRedirect direktes .submit() verwendet)
    const tokenInput = parkForm.querySelector('input[name="REQUEST_TOKEN"]');
    if (tokenInput) {
        refreshTokenIfNeeded(tokenInput, () => {
            setTimeout(() => {
                parkForm.submit();
            }, 300);
        });
    } else {
        setTimeout(() => {
            parkForm.submit();
        }, 300);
    }
    
    return false; // Verhindert doppeltes Submit
}

/**
 * Token-Refresh-Funktion für area-check (vereinfacht)
 */
function refreshTokenIfNeeded(tokenInput, callback) {
    const currentToken = tokenInput.value;
    
    if (!currentToken || currentToken.length < 10) {
        console.warn('[AreaCheck] Token fehlt oder ist zu kurz, fahre mit Submit fort');
        callback();
        return;
    }
    
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTokenInput = doc.querySelector('input[name="REQUEST_TOKEN"]');
        
        if (newTokenInput && newTokenInput.value && newTokenInput.value !== currentToken) {
            console.log('[AreaCheck] Neuen REQUEST_TOKEN erhalten, aktualisiere Form');
            tokenInput.value = newTokenInput.value;
        }
        
        callback();
    })
    .catch(error => {
        console.warn('[AreaCheck] Token-Refresh fehlgeschlagen, verwende bestehenden Token:', error);
        callback();
    });
}

// ===== VEREINFACHTE LOADING-ANIMATION =====
// Alle AJAX-Funktionen entfernt - wir verwenden normale Form-Submit mit HTTP-Redirect

/**
 * Stoppt alle Loading-Animationen (optional - bei Form-Submit nicht kritisch)
 */
function stopLoadingAnimations() {
    // Intervalle laufen bis Seitenwechsel weiter (ist ok)
}

/**
 * Spinner initialisieren - alle Balken grau, ohne Rotation
 */
function initLoadingSpinner() {
    const bars = document.querySelectorAll('.spinner-bar');
    
    if (Utils.isMobile()) {
        // Mobile: Alle Balken in Basis-Farbe, CSS-Animation übernimmt
        bars.forEach((bar) => {
            bar.setAttribute('fill', '#DEEEC6');
        });
        // CSS-Animation läuft automatisch via @media query
    } else {
        // Desktop: Normale Progress-basierte Darstellung
        bars.forEach((bar) => {
            bar.setAttribute('fill', '#DEEEC6');
        });
        
        const spinner = document.querySelector('.loading-spinner svg');
        if (spinner) {
            spinner.style.animation = 'none';
        }
    }
}

/**
 * Aktualisiert das SVG-Spinner basierend auf Progress-Prozent
 * Färbt Balken von 12 Uhr (data-index="0") im Uhrzeigersinn komplett mit #113634
 */
function updateSpinnerProgress(percentage) {
    const spinnerContainer = document.querySelector('.loading-spinner');
    if (!spinnerContainer) return;
    
    // Auf Mobile: Keine Progress-Updates, da dort endlose Rotation läuft
    if (Utils.isMobile()) {
        return; // Spinner dreht sich bereits via CSS-Animation
    }
    
    const bars = spinnerContainer.querySelectorAll('.spinner-bar');
    if (bars.length === 0) return;
    
    // 12 Balken = 100% / 12 = ca. 8.33% pro Balken
    const totalBars = bars.length;
    const barsToFill = Math.floor((percentage / 100) * totalBars);
    
    // Alle Balken zurücksetzen auf Standard-Farbe
    bars.forEach(bar => {
        bar.setAttribute('fill', '#DEEEC6');
        bar.removeAttribute('fill-opacity'); // Opacity komplett entfernen
    });
    
    // Balken von 12 Uhr (Index 0) im Uhrzeigersinn komplett färben
    for (let i = 0; i < barsToFill; i++) {
        const bar = spinnerContainer.querySelector(`[data-index="${i}"]`);
        if (bar) {
            bar.setAttribute('fill', '#113634');
        }
    }
    
    // Bei 100% alle Balken vollständig färben
    if (percentage >= 100) {
        bars.forEach(bar => {
            bar.setAttribute('fill', '#113634');
        });
    }
}

// ===== GLOBALE FUNKTIONEN UND ALERT-SYSTEM =====

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
