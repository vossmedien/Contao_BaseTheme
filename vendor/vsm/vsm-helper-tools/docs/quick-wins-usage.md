# VSM Helper Tools - Quick Wins Usage Guide

## 🚀 Neue Features

### 1. HelperTrait - Gemeinsame Funktionalität

Alle Helper können jetzt den `HelperTrait` verwenden:

```php
use Vsm\VsmHelperTools\Helper\Traits\HelperTrait;

class MyHelper
{
    use HelperTrait;
    
    public static function doSomething()
    {
        // Container-Zugriff (gecached!)
        $container = self::getContainer();
        
        // Einheitliches Logging
        self::logInfo('Operation gestartet');
        self::logWarning('Achtung: Langsame Operation');
        self::logError('Fehler aufgetreten', ['details' => 'xyz']);
        
        // Input-Bereinigung
        $clean = self::cleanInput($dirtyInput);
        
        // Backend-Check
        if (self::isBackend()) {
            // Nur im Backend
        }
    }
}
```

### 2. DebuggableTrait - Performance & Debug

```php
use Vsm\VsmHelperTools\Helper\Traits\DebuggableTrait;

class ImageHelper
{
    use DebuggableTrait;
    
    public static function processImage($path)
    {
        // Debug aktivieren
        self::enableDebug();
        
        // Performance messen
        return self::profile('image_processing', function() use ($path) {
            self::debug('Starte Bildverarbeitung', ['path' => $path]);
            
            // ... Bildverarbeitung ...
            
            self::debug('Bildverarbeitung abgeschlossen');
            return $result;
        });
    }
}

// Debug-Report abrufen
echo ImageHelper::getDebugReport();

// Performance-Daten
$metrics = ImageHelper::getProfilingData();
```

### 3. HelperConstants - Zentrale Konstanten

```php
use Vsm\VsmHelperTools\Helper\Constants\HelperConstants;

// Statt hardcoded Werte:
$quality = HelperConstants::DEFAULT_IMAGE_QUALITY; // 85
$sizes = HelperConstants::SOCIAL_MEDIA_SIZES['opengraph']; // [1200, 630, 'crop']
$formats = HelperConstants::ALL_IMAGE_FORMATS; // ['jpg', 'jpeg', ...]

// In Helpern verwenden:
class SocialMetaHelper
{
    private const SOCIAL_MEDIA_SIZES = HelperConstants::SOCIAL_MEDIA_SIZES;
    // oder direkt:
    $size = HelperConstants::SOCIAL_MEDIA_SIZES[$type];
}
```

### 4. HelperException - Bessere Fehlerbehandlung

```php
use Vsm\VsmHelperTools\Helper\Exception\HelperException;

class VideoHelper
{
    public static function processVideo($path)
    {
        // Datei nicht gefunden
        if (!file_exists($path)) {
            throw HelperException::fileNotFound($path, 'video_processing');
        }
        
        // Ungültiger Parameter
        if (!is_string($path)) {
            throw HelperException::invalidParameter('path', $path, 'string');
        }
        
        // Verarbeitungsfehler mit Kontext
        try {
            // ... Video verarbeiten ...
        } catch (\Exception $e) {
            throw HelperException::processingFailed(
                'video_conversion',
                'FFmpeg Fehler',
                ['path' => $path, 'format' => 'mp4']
            );
        }
    }
}

// Exception behandeln
try {
    VideoHelper::processVideo($video);
} catch (HelperException $e) {
    // Detaillierte Informationen
    echo $e->getDetailedMessage();
    $context = $e->getContext();
    $helperClass = $e->getHelperClass();
    
    // Für Logging
    logger()->error('Helper Fehler', $e->toArray());
}
```

## 📝 Migration bestehender Helper

### Schritt 1: Traits hinzufügen

```php
class MyHelper
{
    use HelperTrait;
    use DebuggableTrait;
    
    // Alte Methoden entfernen:
    // - private static function getContainer()
    // - private static function logError()
    // - private static function cleanInput()
}
```

### Schritt 2: Konstanten ersetzen

```php
// Alt:
private const DEFAULT_QUALITY = 85;

// Neu:
$quality = HelperConstants::DEFAULT_IMAGE_QUALITY;
```

### Schritt 3: Exceptions modernisieren

```php
// Alt:
throw new \Exception('Datei nicht gefunden: ' . $path);

// Neu:
throw HelperException::fileNotFound($path);
```

## 🎯 Best Practices

1. **Immer Traits verwenden** für neue Helper
2. **Konstanten zentralisieren** statt Duplikation
3. **HelperException** für aussagekräftige Fehler
4. **Debug-Mode** nur in Entwicklung aktivieren
5. **Profile** für kritische Operationen

## 🔧 Debugging in Produktion

```php
// Temporär für einen Request aktivieren
if ($_GET['debug'] === 'secret_key') {
    ImageHelper::enableDebug();
}

// Nach Verarbeitung
if (ImageHelper::isDebugEnabled()) {
    file_put_contents(
        '/tmp/helper_debug.log', 
        ImageHelper::getDebugReport()
    );
    ImageHelper::clearDebugData();
}
```

## 📊 Performance-Monitoring

```php
// In einem Monitoring-Hook
$helpers = [ImageHelper::class, VideoHelper::class, SocialMetaHelper::class];

foreach ($helpers as $helper) {
    if (method_exists($helper, 'getProfilingData')) {
        $metrics = $helper::getProfilingData();
        // An Monitoring-System senden
        foreach ($metrics as $operation => $data) {
            monitoring()->gauge(
                "helper.{$helper}.{$operation}.avg_time", 
                $data['avg_time'] * 1000
            );
        }
    }
}
```

## 🔍 Schema.org Integration für Bilder

### Warum Schema.org für Bilder?

- **Bessere SEO**: Google versteht Bildinhalte besser
- **Rich Snippets**: Bilder können in Suchergebnissen prominenter erscheinen
- **Image Search**: Verbesserte Auffindbarkeit in der Bildersuche
- **Lizenz-Info**: Copyright und Urheber werden korrekt kommuniziert

### Verwendung im SchemaOrgHelper

```php
use Vsm\VsmHelperTools\Helper\SchemaOrgHelper;

// Einfaches Bild-Schema
$schema = SchemaOrgHelper::generateImageSchema($imageUuid, [
    'creator' => 'Max Mustermann',
    'copyrightHolder' => 'Caeli Wind GmbH',
    'license' => 'https://creativecommons.org/licenses/by/4.0/',
    'keywords' => ['Windenergie', 'Nachhaltigkeit']
]);

// In Templates
<?= SchemaOrgHelper::generateImageSchema($this->singleSRC) ?>

// Bildergalerie
$gallerySchema = SchemaOrgHelper::generateImageGallerySchema($images, [
    'name' => 'Produktgalerie',
    'description' => 'Unsere Windkraftanlagen im Einsatz'
]);

// WebPage mit Hauptbild
$pageSchema = SchemaOrgHelper::generateWebPageWithImageSchema([
    'name' => $objPage->title,
    'description' => $objPage->description
], $heroImage);
```

### Integration in ImageHelper

```php
// Schema.org ist STANDARDMÄSSIG AKTIVIERT (includeSchema: true)
$imageHtml = ImageHelper::generateImageHTML(
    $imageSource,
    altText: 'Windkraftanlage'
    // Schema.org wird automatisch generiert!
);

// Schema explizit deaktivieren (z.B. in Galerien um Duplikate zu vermeiden)
$imageHtml = ImageHelper::generateImageHTML(
    $imageSource,
    includeSchema: false  // Nur wenn nötig deaktivieren
);

// Empfehlung: In Galerien nur für das erste Bild aktivieren
foreach ($images as $index => $image) {
    echo ImageHelper::generateImageHTML(
        $image,
        includeSchema: ($index === 0)  // Nur erstes Bild mit Schema
    );
}
```

### Automatisch extrahierte Metadaten

Der SchemaOrgHelper nutzt automatisch Contao-Metadaten:

- **title** → Schema name
- **alt** → Schema description  
- **caption** → Schema caption
- **photographer** → Schema creator
- **copyright** → Schema copyrightHolder
- Bildabmessungen → width/height
- Dateigröße → contentSize
- Upload-Datum → uploadDate

### Best Practices

1. **Immer Alt-Text setzen** - wird als description verwendet
2. **Copyright angeben** - wichtig für Lizenzierung
3. **Nur einmal pro Bild** - Schema nicht duplizieren
4. **Hauptbild markieren** - `representativeOfPage: true`

### Beispiel: Komplettes Hero-Bild mit Schema

```php
// In einem Hero-Template
$heroImageSchema = SchemaOrgHelper::generateImageSchema($this->heroImage, [
    'representativeOfPage' => true,
    'isPartOf' => [
        '@type' => 'WebPage',
        '@id' => $this->currentUrl
    ]
]);

// Oder direkt im ImageHelper
$heroHtml = ImageHelper::generateImageHTML(
    $this->heroImage,
    altText: $this->heroAlt,
    includeSchema: true  // Automatisch!
);
```

### Debugging Schema.org

```php
// Schema validieren
SchemaOrgHelper::enableDebug();
$schema = SchemaOrgHelper::generateImageSchema($image);

// Debug-Info ausgeben
if (SchemaOrgHelper::isDebugEnabled()) {
    dump(SchemaOrgHelper::getDebugLog());
}

// Google Rich Results Test:
// https://search.google.com/test/rich-results
``` 