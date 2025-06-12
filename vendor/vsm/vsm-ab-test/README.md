![Alt text](docs/logo.png?raw=true "logo")


# Caeli A/B Test Bundle für Contao

Dieses Bundle ermöglicht A/B Tests auf Inhaltselement- oder Artikel-Ebene in Contao 5.5.

## Features

- ✅ A/B Tests für Inhaltselemente
- ✅ A/B Tests für Artikel  
- ✅ Bis zu 3 Test-Varianten (A, B, C)
- ✅ Session-basierte Konsistenz
- ✅ Zufällige Ausspielung für echte Tests
- ✅ Backend-Integration mit visuellen Hinweisen
- ✅ Insert Tags für Template-Integration

## Installation

```bash
composer require caeli-wind/caeli-ab-test
```

Nach der Installation führen Sie das Contao Install Tool aus, um die Datenbank zu aktualisieren.

## Verwendung

### Backend-Konfiguration

1. **Inhaltselemente**: Öffnen Sie ein Inhaltselement und aktivieren Sie "A/B Test aktivieren"
2. **Artikel**: Öffnen Sie einen Artikel und aktivieren Sie "A/B Test aktivieren"
3. Wählen Sie eine Test-Variante aus (Test A, Test B oder Test C)

### Funktionsweise

- Wenn auf einer Seite mehrere Elemente/Artikel mit A/B Tests vorhanden sind, wird zufällig eine Variante ausgewählt
- Alle Elemente der GLEICHEN Variante werden angezeigt, alle anderen werden ausgeblendet
- Die Auswahl wird in der Session gespeichert für Konsistenz während des Besuchs

### Beispiel-Szenario

Sie haben 5 Inhaltselemente auf einer Seite:
- 2x Test A
- 2x Test B  
- 1x Test C

Das System wählt zufällig eine Variante (z.B. Test B) und zeigt nur die 2 Test B Elemente an.

### Insert Tags

```html
<!-- Zeigt die aktuelle Variante an -->
{{abtest::variant::content}}

<!-- Zeigt 1 an wenn A/B Tests aktiv sind -->
{{abtest::active}}
```

## Technische Details

### Session-Management

A/B Test Entscheidungen werden pro Seite in der Session gespeichert:
- `ab_test_content_{page_id}` für Inhaltselemente
- `ab_test_article_{page_id}` für Artikel

### Hooks

Das Bundle nutzt folgende Contao Hooks:
- `getContentElement` - Filtert Inhaltselemente
- `getArticle` - Filtert Artikel
- `replaceInsertTags` - Fügt Insert Tags hinzu
- `parseWidget` - Alternative Content-Filterung
- `parseTemplate` - Template-Variablen

### Services

- `AbTestManager` - Kern-Logik für A/B Tests
- `AbTestListener` - Hook-Integration
- `ContentElementListener` - Erweiterte Content-Behandlung
- `DataContainerListener` - Backend-Integration

## Entwicklung

### Linter-Fehler

Linter-Fehler können ignoriert werden, da Contao-Klassen zur Laufzeit verfügbar sind.

### Tests erweitern

Um weitere Test-Varianten hinzuzufügen, bearbeiten Sie:
1. `DataContainerListener::getAbTestVariantOptions()`
2. Sprachdateien in `contao/languages/de/`

## Systemanforderungen

- PHP ^8.1
- Contao ^5.0
- Symfony ^7.0

## Lizenz

MIT License - siehe LICENSE Datei für Details.

## Support

Bei Fragen oder Problemen öffnen Sie ein Issue auf GitHub:
https://github.com/caeli-wind/caeli-ab-test/issues
