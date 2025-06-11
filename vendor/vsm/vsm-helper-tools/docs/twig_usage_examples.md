# Verwendung der VSM Helper in Twig-Templates

Mit der VsmHelperExtension können Sie alle Helper-Funktionen aus dem vsm-helper-tools Bundle auch in Twig-Templates verwenden.

## Verfügbare Funktionen

Alle Helper-Funktionen sind mit dem Präfix `vsm_` verfügbar und entsprechen den aktuell implementierten Methoden.

### BasicHelper

```twig
{# Farbe bereinigen - entfernt HTML-Entities aus Farbwerten #}
{{ vsm_clean_color('#ff0000&#41;') }}
{# Ausgabe: #ff0000) #}

{# Dateiinformationen per UUID abrufen #}
{% set fileInfo = vsm_get_file_info('41a238d7-4412-11eb-b378-0242ac130002') %}
{{ fileInfo.filename }} {# Ausgabe: path/to/file.jpg #}
{{ fileInfo.ext }} {# Ausgabe: jpg #}
```

### HeadlineHelper

```twig
{# Vollständiges Headline-HTML generieren #}
{{ vsm_generate_headline_html(
    'Topline Text', 
    'Hauptüberschrift', 
    'Subline Text', 
    'h2', 
    'animate__fadeInUp', 
    '#333333',
    false,
    'custom-headline-class'
) | raw }}

{# Minimale Variante - nur Headline #}
{{ vsm_generate_headline_html('', 'Meine Überschrift', '', 'h1') | raw }}
```

### ButtonHelper

```twig
{# Buttons aus Array generieren #}
{% set buttons = [
    {
        'link_text': 'Jetzt kaufen',
        'link_url': 'https://shop.example.com',
        'link_type': 'btn-primary',
        'link_size': 'btn-lg',
        'new_tab': '1',
        'animation_type': 'animate__fadeInUp'
    },
    {
        'link_text': 'Mehr erfahren',
        'link_url': '/info',
        'link_type': 'btn-outline-secondary',
        'link_size': ''
    }
] %}

{{ vsm_generate_button_html(buttons, 'text-center', true) | raw }}

{# Button-Konfiguration für DCA abrufen #}
{% set buttonConfig = vsm_get_button_config(true) %}
```

### ImageHelper

```twig
{# Responsives Bild mit allen Optionen #}
{{ vsm_generate_image_html(
    image_uuid,
    'Alternativer Text',
    'Bildunterschrift',
    '800x600',
    'img-responsive rounded',
    true,    {# lazy loading #}
    true,    {# webp support #}
    true,    {# responsive #}
    'Bildunterschrift für Schema.org'
) | raw }}

{# Einfache Bild-URL generieren #}
{% set imageUrl = vsm_generate_image_url(image_uuid, '400x300') %}
<img src="{{ imageUrl }}" alt="Bild">

{# SVG-Code inline einbinden #}
{{ vsm_get_svg_code(
    svg_uuid, 
    'Icon-Beschreibung', 
    '24x24', 
    'icon icon-arrow'
) | raw }}
```

### VideoHelper

```twig
{# HTML5-Video mit allen Features rendern #}
{{ vsm_render_video(
    video_uuid,
    'video-player lazy',    {# CSS-Klassen #}
    'Video-Titel',          {# Name für Schema.org #}
    'Video-Beschreibung',   {# Beschreibung #}
    '2024-01-15T10:00:00Z', {# Upload-Datum #}
    poster_image_url,       {# Poster-URL (optional) #}
    'autoplay muted loop playsinline', {# Video-Parameter #}
    true                    {# Lazy Loading #}
) | raw }}

{# Prüfen ob Datei ein Videoformat ist #}
{% if vsm_is_video_format('mp4') %}
    <p>MP4 ist ein unterstütztes Videoformat</p>
{% endif %}
```

### EnvHelper

```twig
{# Backend-spezifische Inhalte #}
{% if vsm_is_backend() %}
    <div class="be-info">Nur im Backend sichtbar</div>
{% endif %}

{# Frontend-spezifische Inhalte #}
{% if vsm_is_frontend() %}
    <div class="fe-content">Nur im Frontend sichtbar</div>
{% endif %}
```

## Erweiterte Beispiele

### Responsive Bildergalerie

```twig
{% set images = [
    {'uuid': 'uuid1', 'alt': 'Bild 1', 'caption': 'Erstes Bild'},
    {'uuid': 'uuid2', 'alt': 'Bild 2', 'caption': 'Zweites Bild'}
] %}

<div class="gallery">
    {% for image in images %}
        <div class="gallery-item">
            {{ vsm_generate_image_html(
                image.uuid,
                image.alt,
                image.caption,
                '600x400',
                'gallery-image',
                true,
                true,
                true
            ) | raw }}
        </div>
    {% endfor %}
</div>
```

### Bedingte Button-Anzeige

```twig
{% if vsm_is_frontend() %}
    {% set ctaButtons = [
        {
            'link_text': 'Kontakt aufnehmen',
            'link_url': '/kontakt',
            'link_type': 'btn-primary with-arrow',
            'link_size': 'btn-lg'
        }
    ] %}
    {{ vsm_generate_button_html(ctaButtons, 'text-center mt-4') | raw }}
{% endif %}
```

### Strukturierte Inhaltssektion

```twig
<section class="hero-section">
    {{ vsm_generate_headline_html(
        'Willkommen bei',
        'Unserer neuen Website',
        'Entdecken Sie alle Möglichkeiten',
        'h1',
        'animate__fadeInUp',
        '#2c3e50'
    ) | raw }}
    
    {{ vsm_generate_image_html(
        hero_image_uuid,
        'Hero-Bild der Website',
        '',
        '1200x600',
        'hero-image',
        true,
        true,
        true
    ) | raw }}
    
    {% set heroButtons = [
        {
            'link_text': 'Jetzt starten',
            'link_url': '/start',
            'link_type': 'btn-primary',
            'link_size': 'btn-xl'
        },
        {
            'link_text': 'Mehr erfahren',
            'link_url': '/info',
            'link_type': 'btn-outline-white',
            'link_size': 'btn-xl'
        }
    ] %}
    {{ vsm_generate_button_html(heroButtons, 'hero-buttons') | raw }}
</section>
```

## Wichtige Hinweise

1. **Raw-Filter**: Funktionen, die HTML zurückgeben, sollten mit dem `|raw` Filter verwendet werden
2. **Parameter-Reihenfolge**: Die Parameter entsprechen der PHP-Methodensignatur
3. **Standardwerte**: Alle Parameter haben sinnvolle Standardwerte
4. **Performance**: Lazy Loading wird empfohlen für bessere Performance
5. **SEO**: Structured Data wird automatisch generiert wo sinnvoll

## Debugging

```twig
{# Umgebung prüfen #}
{% if vsm_is_backend() %}
    <pre>{{ dump(vsm_get_file_info(image_uuid)) }}</pre>
{% endif %}

{# Dateiinfo anzeigen #}
{% set info = vsm_get_file_info(file_uuid) %}
<p>Datei: {{ info.filename }}, Typ: {{ info.ext }}</p>
```

## Performance-Tipps

1. **Lazy Loading**: Aktivieren Sie Lazy Loading für Bilder und Videos
2. **WebP**: Nutzen Sie WebP-Support für bessere Kompression
3. **Responsive Images**: Verwenden Sie responsive Bilder für verschiedene Bildschirmgrößen
4. **Caching**: Die Helper-Funktionen nutzen Contao's integriertes Caching

Weitere Informationen finden Sie in der [Hauptdokumentation](../README.md). 