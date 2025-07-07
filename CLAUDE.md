# CLAUDE.md

Diese Datei bietet Leitlinien für Claude Code (claude.ai/code) beim Arbeiten mit Code in diesem Repository.

## Spracheinstellungen

- Schreibe Kommentare und antworte immer auf Deutsch
- Formuliere Dokumentationen wenn möglich auf Deutsch
- Generelle Kommunikation findet auf Deutsch statt

## Projekt-Übersicht

Dies ist ein Contao 5.5-basiertes Website-Projekt mit einem benutzerdefinierten Frontend-Build-System, das auf Symfony 7 läuft. Das Projekt umfasst:
- PHP-basiertes Contao CMS Backend
- Benutzerdefiniertes SCSS/CSS-Build-System mit Theme-Unterstützung
- JavaScript-Modulsystem mit bedingtem Laden
- Rock Solid Custom Elements (RSCE) für das Content-Management
- Deployment-Skripte für Staging/Produktionsumgebungen

### Wichtige Entwicklungshinweise
- Verwendet Contao 5.5 mit Symfony 7
- Die meisten Linter-Fehler können ignoriert werden
- Kompilierung und Deployment werden automatisch über die IDE abgewickelt
- Lokale Contao-Befehle sind nicht nützlich - alle Befehle müssen direkt auf dem Server ausgeführt werden
- Dateien werden automatisch über die IDE auf den Server hochgeladen
- **Sprache**: Antworte immer auf Deutsch, schreibe Kommentare auf Deutsch und formuliere Dokumentationen wenn möglich auf Deutsch

## Wichtige Entwicklungsbefehle

### Composer-Befehle
- `composer install` - PHP-Abhängigkeiten installieren
- `composer update` - PHP-Abhängigkeiten aktualisieren
- `php vendor/bin/contao-setup` - Contao-Setup ausführen (wird automatisch nach install/update ausgeführt)

### Deployment-Befehle
- `./xdeploystagingtolive.sh` - Staging auf Live-Umgebung deployen (erfordert Umgebungsvariablen)
- `./xrollbacklive.sh` - Live-Umgebung auf vorheriges Backup zurücksetzen
- `./xcleanupbackups.sh` - Alte Backup-Dateien aufräumen

Hinweis: Deployment-Skripte erfordern eine ordnungsgemäße Konfiguration der Umgebungsvariablen für Datenbankzugangsdaten und Pfade.

## Architektur-Übersicht

### Kern-Struktur
```
src/
├── EventListener/
│   └── TemplateOptimizationListener.php    # SEO- und Performance-Optimierungen
config/
├── services.yaml                           # Symfony-Service-Konfiguration
templates/
├── _sections/                              # Header/Footer-Abschnitte
├── rsce_*.html5                           # Rock Solid Custom Elements
├── rsce_*_config.php                      # RSCE-Konfigurationsdateien
├── theme/                                 # Theme-spezifische Templates
└── theme_asset_loader.php                 # Asset-Ladelogik
```

### Frontend-Assets
```
files/base/layout/
├── css/
│   ├── style.scss                         # Hauptstylesheet-Einstiegspunkt
│   ├── _theme/                           # Theme-spezifische Styles
│   ├── elements/                         # Komponenten-Styles
│   └── scaffolding/                      # Basis-Styles
├── js/
│   ├── _theme/                           # Theme-spezifisches JS
│   ├── _elements/                        # Komponenten-JS
│   └── dist/                             # Gebaute JS-Dateien
└── _fonts/                               # Font-Dateien mit SCSS-Imports
```

### Hauptfunktionen

#### Template-Optimierungssystem
- **Datei**: `src/EventListener/TemplateOptimizationListener.php`
- Bietet bedingtes Asset-Laden basierend auf Seiteninhalten
- Optimiert kanonische URLs für gefilterte/kategorisierte Seiten
- Erweitert Links mit Barrierefreiheits-Attributen

#### Asset-Ladesystem
- **Datei**: `templates/theme_asset_loader.php`
- Unterstützt manifestbasiertes Asset-Laden
- Bedingtes Laden für JavaScript-Bibliotheken (Swiper, Venobox)
- Font-Preloading-Optimierung

#### RSCE (Rock Solid Custom Elements)
- Umfangreiche Sammlung von benutzerdefinierten Inhaltselementen
- Jedes Element hat ein gepaartetes `.html5`-Template und eine `_config.php`-Datei
- Elemente umfassen: Slider, Galerien, Formulare, Hero-Abschnitte, etc.
- Beim Erstellen neuer RSCE-Elemente oder beim Bearbeiten bestehender, folge immer dem einheitlichen Schema anderer Elemente
- Nutze VSM-Helper-Utilities für häufige Elemente wie Buttons, Bilder, Videos, Überschriften, etc.

### Datenbankintegration
- Verwendet Contaos Datenbankstruktur
- Benutzerdefinierte Deployment-Skripte handhaben Datenbanksynchronisation
- Bewahrt spezifische Tabellen/Felder während Deployments (konfigurierbar über `TARGET_EXCEPTIONS`)

### Entwicklungsrichtlinien

#### Arbeiten mit Styles
- Haupteinstiegspunkt: `files/base/layout/css/style.scss`
- Theme-spezifische Styles in `_theme/`-Verzeichnissen
- Komponenten-Styles organisiert im `elements/`-Verzeichnis
- Font-Imports über individuelle `font.scss`-Dateien

#### Arbeiten mit JavaScript
- Modulare Struktur mit individuellen Feature-Dateien
- Theme-spezifische Konfiguration in `theme.js_vendors.php`
- Bedingtes Ladesystem für Performance-Optimierung

#### Arbeiten mit Templates
- Contao-Templates verwenden `.html5`-Endung
- Twig-Templates verwenden `.html.twig`-Endung
- RSCE-Elemente erfordern sowohl Template- als auch Config-Datei

#### Deployment-Überlegungen
- Niemals `.env.local`-Dateien committen
- Deployment-Skripte für Umgebungssynchronisation verwenden
- Datenbank-Migrationen über Deployment-Skripte abgewickelt
- Asset-Manifeste automatisch während des Build-Prozesses generiert

## VSM Custom Bundles
- `vsm/vsm-helper-tools` - Benutzerdefinierte Hilfswerkzeuge
- `vsm/vsm-ab-test` - A/B-Test-Funktionalität

Diese befinden sich als lokale Pfad-Repositories im `vendor/`-Verzeichnis.

## Wichtige Abhängigkeiten

### Contao-Bundles
- `contao/manager-bundle` ^5.4.0 - Contao Manager Integration
- `contao/news-bundle` ^5.4.0 - News-System
- `madeyourday/contao-rocksolid-custom-elements` ^2.4 - RSCE-Framework
- `oveleon/contao-component-style-manager` ^3.5 - Style-Management
- `oveleon/contao-cookiebar` ^2.0 - Cookie-Consent
- `terminal42/contao-changelanguage` ^3.6 - Mehrsprachigkeit
- `terminal42/contao-folderpage` ^3.2 - Ordner-Seiten
- `terminal42/contao-leads` ^3.0 - Formular-Leads
- `terminal42/notification_center` ^2.3 - Benachrichtigungen
- `trilobit-gmbh/contao-tiles-bundle` ^2.0 - Kachel-System

### Frontend-Bibliotheken (über theme.js_vendors.php)
- **Bootstrap** - CSS-Framework und Komponenten
- **Swiper** - Touch-Slider und Karussells
- **Venobox** - Lightbox für Bilder und Videos
- **Macy** - Masonry-Layout für Galerien
- **mmenu-light** - Mobile Navigation
- **js-cookie** - Cookie-Verwaltung

## VSM Helper Utilities - Detaillierte Übersicht

### ImageHelper
- `vsm_generate_image_html()` - Vollständiges responsives Bild-HTML
- **Features**: WebP/AVIF-Support, Lazy Loading, Schema.org, dynamische Qualität
- **Verwendung**: Für alle Bildausgaben in RSCE-Elementen

### ButtonHelper
- `ButtonHelper::getButtonConfig()` - DCA-Konfiguration für RSCE-Elemente
- `vsm_generate_button_html()` - Button-HTML aus Array-Konfiguration
- **Features**: Automatische externe Link-Erkennung, ARIA-Labels, Analytics-Support

### VideoHelper
- `vsm_render_video()` - HTML5-Video mit Fallbacks und Poster-Unterstützung
- **Features**: WebM/MP4-Fallbacks, Schema.org VideoObject, Lazy Loading

### HeadlineHelper
- `vsm_generate_headline_html()` - Strukturierte Headlines (Topline, Hauptüberschrift, Subline)
- **Features**: Animate.css-Integration, semantische HTML-Tags

### GlobalElementConfig
- `GlobalElementConfig::getAnimations()` - Vordefinierte Animate.css-Animationen
- `GlobalElementConfig::getHeadlineTagOptions()` - H1-H6 Optionen
- **Verwendung**: Für konsistente Konfigurationen in allen RSCE-Elementen

## Testbefehle und Qualitätssicherung

Da keine expliziten Test-Konfigurationsdateien vorhanden sind, prüfe vor Deployment:
- Contao Backend-Zugriff funktioniert
- Frontend-Seiten laden korrekt
- JavaScript-Bibliotheken sind verfügbar
- Asset-Compilation funktioniert über IDE

## Umgebungskonfiguration

### Wichtige Dateien (nicht im Repository)
- `.env.local` - Lokale Umgebungsvariablen (niemals committen)
- `config/parameters.yml` - Datenbankzugangsdaten
- Deployment-Skripte verwenden eigene Umgebungsvariablen

### Entwicklungsumgebung
- IDE übernimmt automatisches Deployment
- Lokale Contao-Befehle sind nicht verfügbar
- Alle Änderungen werden automatisch auf Server übertragen