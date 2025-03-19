# Verwendung der VSM Helper in Twig-Templates

Mit der VsmHelperExtension können Sie alle Helper-Funktionen aus dem vsm-helper-tools Bundle auch in Twig-Templates verwenden.

## Verfügbare Funktionen

Alle Helper-Funktionen sind mit dem Präfix `vsm_` verfügbar.

### BasicHelper

```twig
{# Farbe bereinigen #}
{{ vsm_clean_color('#ff0000;') }}

{# Dateiinformationen abrufen #}
{% set fileInfo = vsm_get_file_info('41a238d7-4412-11eb-b378-0242ac130002') %}
{{ fileInfo.filename }} {# Ausgabe: path/to/file.jpg #}
{{ fileInfo.ext }} {# Ausgabe: jpg #}
```

### HeadlineHelper

```twig
{# Headline HTML generieren #}
{{ vsm_generate_headline_html(
    'Topline Text', 
    'Headline Text', 
    'Subline Text', 
    'h2', 
    'animate__fadeInUp', 
    '#ff0000'
) | raw }}
```

### ButtonHelper

```twig
{# Button HTML generieren #}
{{ vsm_generate_button_html(
    'Klick mich', 
    'https://example.com', 
    'btn-primary', 
    '_blank'
) | raw }}
```

### ImageHelper

```twig
{# Bild-HTML generieren #}
{{ vsm_generate_image_html(
    '41a238d7-4412-11eb-b378-0242ac130002', 
    'Alternativer Text', 
    'Überschrift', 
    '400x300', 
    'my-image-class', 
    false, 
    false, 
    true, 
    'Bildunterschrift'
) | raw }}

{# Bild-URL generieren #}
{% set imageUrl = vsm_generate_image_url('41a238d7-4412-11eb-b378-0242ac130002', '400x300') %}

{# SVG-Code abrufen #}
{{ vsm_get_svg_code('41a238d7-4412-11eb-b378-0242ac130002', 'Alt-Text', '100x100', 'svg-class') | raw }}
```

### VideoHelper

```twig
{# Video-Quelle abrufen #}
{% set videoSrc = vsm_get_video_src('41a238d7-4412-11eb-b378-0242ac130002') %}

{# Video-Attribute abrufen #}
{% set videoAttrs = vsm_get_video_attributes(
    '41a238d7-4412-11eb-b378-0242ac130002',
    true,
    true,
    false
) %}
```

### EnvHelper

```twig
{# Prüfen, ob Entwicklungsumgebung #}
{% if vsm_is_dev() %}
    <div class="dev-info">Nur in der Entwicklungsumgebung sichtbar</div>
{% endif %}
```

### GlobalElementConfig

```twig
{# Globale Elementkonfiguration abrufen #}
{% set config = vsm_element_config('element_key', 'default_value') %}
```

### PaymentFormHelper

```twig
{# Zahlungsformular generieren #}
{{ vsm_payment_form('payment_id', 'form_id') | raw }}
```

## Wichtige Hinweise

1. Funktionen, die HTML zurückgeben, sollten mit dem `|raw` Filter verwendet werden, damit Twig das HTML nicht escaped.
2. Alle Parameter können wie in PHP übergeben werden, mit den gleichen Standardwerten.
3. Bei Verwendung in Twig-Templates müssen Sie das Präfix `vsm_` vor jedem Funktionsnamen verwenden.

## Weitere Informationen

Bei Fragen wenden Sie sich bitte an den Autor des Bundles oder erstellen Sie ein Issue im [GitHub-Repository](https://github.com/vsm/vsm-helper-tools). 