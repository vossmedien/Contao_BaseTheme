![VSM Helper Tools Logo](docs/logo.png?raw=true "VSM Helper Tools Logo")

# VSM Helper & Tools

Ein umfassendes Helper-Bundle für Contao 5.x mit praktischen Funktionen für die Frontend-Entwicklung.

[![Version](https://img.shields.io/badge/version-0.1-blue.svg)](https://github.com/vsm/vsm-helper-tools)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://github.com/vsm/vsm-helper-tools/blob/main/LICENSE)
[![Contao](https://img.shields.io/badge/contao-5.x-orange.svg)](https://contao.org)
[![Symfony](https://img.shields.io/badge/symfony-7.x-black.svg)](https://symfony.com)

## 🚀 Features

- **ImageHelper**: Erweiterte Bildverarbeitung mit responsiven Bildern, Lazy Loading und WebP-Support
- **VideoHelper**: Video-Rendering mit HTML5-Videos, Structured Data und Lazy Loading
- **ButtonHelper**: Konsistente Button-Generierung mit Tracking-Support
- **HeadlineHelper**: Strukturierte Headlines mit Animationen
- **BasicHelper**: Utility-Funktionen für alltägliche Aufgaben
- **EnvHelper**: Umgebungserkennung (Frontend/Backend)
- **Twig-Integration**: Alle Helper als Twig-Funktionen verfügbar

## 📦 Installation

### Via Composer

```bash
composer require vsm/vsm-helper-tools
```

### Contao Manager

1. Im Contao Manager unter "Pakete" nach `vsm/vsm-helper-tools` suchen
2. Installieren und Datenbank aktualisieren

## 🛠 Verwendung

### PHP-Verwendung

```php
use Vsm\VsmHelperTools\Helper\ImageHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;

// Bild-HTML generieren
$imageHtml = ImageHelper::generateImageHTML(
    $imageUuid,
    $altText,
    $caption,
    $size,
    $cssClass,
    $lazy,
    $webp,
    $responsive
);

// Button-HTML generieren
$buttonHtml = ButtonHelper::generateButtonHTML($buttonsArray);
```

### Twig-Templates

Alle Helper-Funktionen sind als Twig-Funktionen mit dem Präfix `vsm_` verfügbar:

```twig
{# Bild rendern #}
{{ vsm_generate_image_html(
    image_uuid,
    'Alt-Text',
    'Bildunterschrift',
    '800x600',
    'img-responsive',
    true,
    true,
    true
) | raw }}

{# Button generieren #}
{{ vsm_generate_button_html(buttons, 'btn-wrapper') | raw }}

{# Headline erstellen #}
{{ vsm_generate_headline_html(
    'Topline',
    'Hauptüberschrift',
    'Subline',
    'h2',
    'animate__fadeInUp',
    '#333333'
) | raw }}

{# Video einbinden #}
{{ vsm_render_video(
    video_uuid,
    'video-player',
    'Video-Titel',
    'Beschreibung'
) | raw }}
```

## 📚 Helper-Klassen im Detail

### ImageHelper

**Hauptfunktionen:**
- `generateImageHTML()` - Generiert komplettes responsive Bild-HTML
- `generateImageURL()` - Erstellt optimierte Bild-URLs
- `getSvgCode()` - Lädt SVG-Inhalte inline

**Features:**
- WebP-Unterstützung mit Fallback
- Responsive Bilder mit `srcset`
- Lazy Loading mit Intersection Observer
- Structured Data für SEO
- Verschiedene Bildgrößen und Zuschnitte

### VideoHelper

**Hauptfunktionen:**
- `renderVideo()` - Komplettes Video-HTML mit Fallbacks
- `isVideoFormat()` - Prüft Videoformate

**Features:**
- HTML5-Video mit WebM/MP4-Fallback
- Automatische Poster-Erkennung
- Structured Data (VideoObject)
- Lazy Loading für Videos

### ButtonHelper

**Hauptfunktionen:**
- `generateButtonHTML()` - Button-HTML aus Array-Konfiguration
- `getButtonConfig()` - DCA-Konfiguration für Buttons

**Features:**
- Verschiedene Button-Stile (Primary, Secondary, Outline)
- Link-Tracking-Integration
- Animation-Support
- Neue-Tab/Lightbox-Support

### HeadlineHelper

**Hauptfunktionen:**
- `generateHeadlineHTML()` - Strukturierte Headlines

**Features:**
- Topline, Headline, Subline
- Verschiedene HTML-Tags (H1-H6)
- Animation-Support
- Benutzerdefinierte Farben

### BasicHelper

**Hauptfunktionen:**
- `cleanColor()` - Bereinigt Farbwerte von HTML-Entities
- `getFileInfo()` - Holt Dateiinformationen per UUID

### EnvHelper

**Hauptfunktionen:**
- `isBackend()` - Prüft Backend-Kontext
- `isFrontend()` - Prüft Frontend-Kontext

## 🎨 Verfügbare Twig-Funktionen

| Funktion | Beschreibung |
|----------|--------------|
| `vsm_clean_color()` | Bereinigt Farbwerte |
| `vsm_get_file_info()` | Holt Dateiinfos per UUID |
| `vsm_generate_headline_html()` | Generiert Headline-HTML |
| `vsm_generate_button_html()` | Generiert Button-HTML |
| `vsm_get_button_config()` | Holt Button-Konfiguration |
| `vsm_generate_image_html()` | Generiert Bild-HTML |
| `vsm_generate_image_url()` | Generiert Bild-URL |
| `vsm_get_svg_code()` | Holt SVG-Code |
| `vsm_render_video()` | Rendert Video-HTML |
| `vsm_is_video_format()` | Prüft Videoformat |
| `vsm_is_backend()` | Backend-Kontext |
| `vsm_is_frontend()` | Frontend-Kontext |

## ⚙️ Konfiguration

### Services

Das Bundle registriert sich automatisch über die `services.yaml`:

```yaml
services:
    Vsm\VsmHelperTools\:
        resource: '../src/'
        exclude: '../src/{DependencyInjection,Model,Resources,ContaoManager,Entity,Migrations,Tests}'

    Vsm\VsmHelperTools\Twig\VsmHelperExtension:
        tags: ['twig.extension']
```

### Parameter

```yaml
parameters:
    vsm_helper_tools.default_member_group: 1
```

## 🔧 Entwicklung

### Anforderungen

- PHP ^8.1
- Contao ^5.0
- Symfony ^7.0

### Code-Style

```bash
composer run-script cs-fixer
```

## 🤝 Beitragen

1. Fork des Repositories
2. Feature-Branch erstellen (`git checkout -b feature/AmazingFeature`)
3. Änderungen committen (`git commit -m 'Add some AmazingFeature'`)
4. Branch pushen (`git push origin feature/AmazingFeature`)
5. Pull Request erstellen

## 📄 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Siehe [LICENSE](LICENSE) für Details.

## 👨‍💻 Autor

**Vossmedien - Christian Voss**
- Website: [https://www.vossmedien.de](https://www.vossmedien.de)
- Email: christian@vossmedien.de
- GitHub: [@vsm](https://github.com/vsm)

## 🐛 Support

Bei Problemen oder Fragen:
- [GitHub Issues](https://github.com/vsm/vsm-helper-tools/issues)
- [Dokumentation](https://github.com/vsm/vsm-helper-tools/tree/main/docs)

---

Made with ❤️ for the Contao Community
