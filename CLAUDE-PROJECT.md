# CLAUDE-PROJECT.md

Diese Datei enthält projektspezifische Anweisungen für Claude Code, die zusätzlich zur allgemeinen CLAUDE.md gelten.

## Projekt-Informationen

### Projektname
MEA GmbH Website

### Kunde/Organisation
MEA GmbH - Merzweiler-Energie-Anlagen

### Projektbeschreibung
Unternehmenswebsite für MEA GmbH - Merzweiler-Energie-Anlagen, einem spezialisierten Anbieter für innovative Energielösungen. Die Website präsentiert Services, Kontaktmöglichkeiten und Unternehmensinformationen mit modernem Design und responsiver Darstellung.

### Live-URLs
- **Staging**: [Staging-URL nicht verfügbar]
- **Production**: [Production-URL aus composer.json ermitteln]

## Projektspezifische Abhängigkeiten

### Zusätzliche Contao-Bundles
Keine zusätzlichen Bundles - alle verwendet Bundles sind bereits in der Basis-CLAUDE.md dokumentiert.

### Projektspezifische Bibliotheken
Keine projektspezifischen Bibliotheken - Standard-Stack mit Bootstrap, Swiper, Venobox, Macy, mmenu-light und js-cookie.

## Projektspezifische RSCE-Elemente

### Besondere Inhaltselemente
Standard-RSCE-Sammlung - keine projektspezifischen Elemente, sondern systemweite Erweiterungen die in allen Projekten verwendet werden.

### Projektspezifische VSM-Helper-Erweiterungen
Standard VSM-Helper werden verwendet - keine projektspezifischen Erweiterungen implementiert.

## Theme-Konfiguration

### Farbschema
- **Primary**: #FE0B03 (Rot)
- **Secondary**: #FFFEEA (Cremeweiß)
- **Tertiary**: #D5D4C5 (Beige)
- **Body Background**: #ffffff
- **Body Color**: #4D4D4D (Dunkelgrau)
- **Link Color**: #FE0B03 (Primary)
- **Link Hover**: #D5D4C5 (Tertiary)

### Breakpoints
- **Mobile Max Width**: 992px
- **Container Gutter**: 15px
- **Left Column Width**: 275px
- **Right Column Width**: 250px
- **Modal Sizes**: SM: 520px, MD: 760px, LG: 940px, XL: 1140px

### Schriftarten
- **Font Family Base**: system-ui, "Segoe UI", Roboto, Helvetica, Arial, sans-serif
- **Font Weight Base**: 300
- **Font Weight Bold**: 800
- **Font Weight Black**: 800
- **Font Sizes**: XS: 10px, SM: 14px, Base: 16px, LG: 20px, XL: 28px
- **Headings Font Weight**: 700
- **Font Awesome**: Kit c9b4e661cb integriert

## Wichtige Hinweise

### Häufige Probleme
- **FontAwesome Overhead**: 9 verschiedene Styles werden geladen (~200KB+), nur 2-3 werden tatsächlich benötigt
- **Bootstrap-Komponenten**: Ungenutzte Komponenten (tooltip, popover, toasts) werden mitgeladen
- **CSS-Ladung**: RSCE-Elemente laden CSS einzeln statt kombiniert


**Hinweis**: Diese Datei sollte regelmäßig aktualisiert werden, wenn sich projektspezifische Anforderungen ändern.