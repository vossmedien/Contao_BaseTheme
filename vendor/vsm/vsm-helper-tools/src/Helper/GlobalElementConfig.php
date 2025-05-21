<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

namespace Vsm\VsmHelperTools\Helper;

class GlobalElementConfig
{
    public static function getAnimations()
    {
        return [
            'animate__fadeIn' => 'fadeIn (Standard)',
            'no-animation' => 'Keine Animation',
            'animate__fadeInUp' => 'fadeInUp ',
            'animate__fadeInDown' => 'fadeInDown',
            'animate__fadeInDownBig' => 'fadeInDownBig',
            'animate__fadeInLeft' => 'fadeInLeft',
            'animate__fadeInLeftBig' => 'fadeInLeftBig',
            'animate__fadeInRight' => 'fadeInRight',
            'animate__fadeInRightBig' => 'fadeInRightBig',
            'animate__fadeInUpBig' => 'fadeInUpBig',
            'animate__fadeInTopLeft' => 'fadeInTopLeft',
            'animate__fadeInTopRight' => 'fadeInTopRight',
            'animate__fadeInBottomLeft' => 'fadeInBottomLeft',
            'animate__fadeInBottomRight' => 'fadeInBottomRight',
            /* Attention seekers  */
            'animate__bounce' => 'bounce',
            'animate__flash' => 'flash',
            'animate__pulse' => 'pulse',
            'animate__rubberBand' => 'rubberBand',
            'animate__shakeX' => 'shakeX',
            'animate__shakeY' => 'shakeY',
            'animate__headShake' => 'headShake',
            'animate__swing' => 'swing',
            'animate__tada' => 'tada',
            'animate__wobble' => 'wobble',
            'animate__jello' => 'jello',
            'animate__heartBeat' => 'heartBeat',
            /* Back entrances */
            'animate__backInDown' => 'backInDown',
            'animate__backInLeft' => 'backInLeft',
            'animate__backInRight' => 'backInRight',
            'animate__backInUp' => 'backInUp',
            /* Back exits
            'animate__backOutDown' => 'backOutDown',
            'animate__backOutLeft' => 'backOutLeft',
            'animate__backOutRight' => 'backOutRight',
            'animate__backOutUp' => 'backOutUp', */
            /* Bouncing entrances  */
            'animate__bounceIn' => 'bounceIn',
            'animate__bounceInDown' => 'bounceInDown',
            'animate__bounceInLeft' => 'bounceInLeft',
            'animate__bounceInRight' => 'bounceInRight',
            'animate__bounceInUp' => 'bounceInUp',
            /* Bouncing exits
            'animate__bounceOut' => 'bounceOut',
            'animate__bounceOutDown' => 'bounceOutDown',
            'animate__bounceOutLeft' => 'bounceOutLeft',
            'animate__bounceOutRight' => 'bounceOutRight',
            'animate__bounceOutUp' => 'bounceOutUp', */
            /* Fading exits
            'animate__fadeOut' => 'fadeOut',
            'animate__fadeOutDown' => 'fadeOutDown',
            'animate__fadeOutDownBig' => 'fadeOutDownBig',
            'animate__fadeOutLeft' => 'fadeOutLeft',
            'animate__fadeOutLeftBig' => 'fadeOutLeftBig',
            'animate__fadeOutRight' => 'fadeOutRight',
            'animate__fadeOutRightBig' => 'fadeOutRightBig',
            'animate__fadeOutUp' => 'fadeOutUp',
            'animate__fadeOutUpBig' => 'fadeOutUpBig',
            'animate__fadeOutTopLeft' => 'fadeOutTopLeft',
            'animate__fadeOutTopRight' => 'fadeOutTopRight',
            'animate__fadeOutBottomRight' => 'fadeOutBottomRight',
            'animate__fadeOutBottomLeft' => 'fadeOutBottomLeft', */
            /* Flippers */
            'animate__flip' => 'flip',
            'animate__flipInX' => 'flipInX',
            'animate__flipInY' => 'flipInY',
            'animate__flipOutX' => 'flipOutX',
            'animate__flipOutY' => 'flipOutY',
            /* Lightspeed */
            'animate__lightSpeedInRight' => 'lightSpeedInRight',
            'animate__lightSpeedInLeft' => 'lightSpeedInLeft',
            'animate__lightSpeedOutRight' => 'lightSpeedOutRight',
            'animate__lightSpeedOutLeft' => 'lightSpeedOutLeft',
            /* Rotating entrances */
            'animate__rotateIn' => 'rotateIn',
            'animate__rotateInDownLeft' => 'rotateInDownLeft',
            'animate__rotateInDownRight' => 'rotateInDownRight',
            'animate__rotateInUpLeft' => 'rotateInUpLeft',
            'animate__rotateInUpRight' => 'rotateInUpRight',
            /* Rotating exits
            'animate__rotateOut' => 'rotateOut',
            'animate__rotateOutDownLeft' => 'rotateOutDownLeft',
            'animate__rotateOutDownRight' => 'rotateOutDownRight',
            'animate__rotateOutUpLeft' => 'rotateOutUpLeft',
            'animate__rotateOutUpRight' => 'rotateOutUpRight',*/
            /* Specials */
            'animate__hinge' => 'hinge',
            'animate__jackInTheBox' => 'jackInTheBox',
            'animate__rollIn' => 'rollIn',
            'animate__rollOut' => 'rollOut',
            /* Zooming entrances */
            'animate__zoomIn' => 'zoomIn',
            'animate__zoomInDown' => 'zoomInDown',
            'animate__zoomInLeft' => 'zoomInLeft',
            'animate__zoomInRight' => 'zoomInRight',
            'animate__zoomInUp' => 'zoomInUp',
            /* Zooming exits
            'animate__zoomOut' => 'zoomOut',
            'animate__zoomOutDown' => 'zoomOutDown',
            'animate__zoomOutLeft' => 'zoomOutLeft',
            'animate__zoomOutRight' => 'zoomOutRight',
            'animate__zoomOutUp' => 'zoomOutUp',*/
            /* Sliding entrances */
            'animate__slideInDown' => 'slideInDown',
            'animate__slideInLeft' => 'slideInLeft',
            'animate__slideInRight' => 'slideInRight',
            'animate__slideInUp' => 'slideInUp',
            /* Sliding exits
            'animate__slideOutDown' => 'slideOutDown',
            'animate__slideOutLeft' => 'slideOutLeft',
            'animate__slideOutRight' => 'slideOutRight',
            'animate__slideOutUp' => 'slideOutUp',*/
        ];
    }

    public static function getHeadlineTagOptions()
    {
        return [
            'h1' => 'H1',
            'h2' => 'H2',
            'h3' => 'H3',
            'h4' => 'H4',
            'h5' => 'H5',
            'h6' => 'H6',
        ];
    }

    public static function getButtonTypes()
    {
        return [
            // Hauptfarbe-Buttons
            'btn-primary' => 'Hauptfarbe',
            'btn-primary with-arrow' => 'Hauptfarbe mit Pfeil',
            'btn-outline-primary' => 'Hauptfarbe (Outline)',
            'btn-outline-primary with-arrow' => 'Hauptfarbe (Outline) mit Pfeil',

            // Sekundär-Buttons
            'btn-secondary' => 'Sekundär-Farbe',
            'btn-secondary with-arrow' => 'Sekundär-Farbe mit Pfeil',
            'btn-outline-secondary' => 'Sekundär-Farbe (Outline)',
            'btn-outline-secondary with-arrow' => 'Sekundär-Farbe (Outline) mit Pfeil',

            // Tertiär-Buttons
            'btn-tertiary' => 'Tertiär-Farbe',
            'btn-tertiary with-arrow' => 'Tertiär-Farbe mit Pfeil',
            'btn-outline-tertiary' => 'Tertiär-Farbe (Outline)',
            'btn-outline-tertiary with-arrow' => 'Tertiär-Farbe (Outline) mit Pfeil',

            // CurrentColor-Buttons
            'btn-outline-currentColor' => 'Farbübernahme vom Elternelement (Outline)',
            'btn-outline-currentColor with-arrow' => 'Farbübernahme vom Elternelement (Outline) mit Pfeil',

            // Weiße Buttons
            'btn-white' => 'Weißer Button mit schwarzer Schrift',
            'btn-white with-arrow' => 'Weißer Button mit schwarzer Schrift und Pfeil',
            'btn-outline-white' => 'Transparenter Button mit weißer Schrift und Rahmen',
            'btn-outline-white with-arrow' => 'Transparenter Button mit weißer Schrift und Rahmen sowie Pfeil',

            // Schwarze Buttons
            'btn-black' => 'Schwarzer Button mit weißer Schrift',
            'btn-black with-arrow' => 'Schwarzer Button mit weißer Schrift und Pfeil',
            'btn-outline-black' => 'Transparenter Button mit schwarzer Schrift und Rahmen',
            'btn-outline-black with-arrow' => 'Transparenter Button mit schwarzer Schrift und Rahmen sowie Pfeil',

            // Rote/Danger Buttons
            'btn-danger' => 'Roter Button',
            'btn-danger with-arrow' => 'Roter Button mit Pfeil',
            'btn-outline-danger' => 'Roter Button (Outline)',
            'btn-outline-danger with-arrow' => 'Roter Button (Outline) mit Pfeil',

            // Link-Buttons
            'btn-link' => 'Link-Optik',
            'btn-link with-arrow' => 'Link-Optik mit Pfeilen',
        ];
    }

    public static function getButtonSizes()
    {
        return [
            '' => 'Standard',
            'btn-sm' => 'Klein',
            'btn-lg' => 'Groß',
            'btn-xl' => 'Sehr groß',
        ];
    }

    public static function getButtonConfig()
    {
        return [
            'animation_type' => [
                'label' => ['Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'],
                'inputType' => 'select',
                'options' => self::getAnimations(),
                'eval' => ['chosen' => 'true']
            ],
            'link_text' => [
                'label' => ['Link-Beschriftung', ''],
                'inputType' => 'text',
                'eval' => ['allowHtml' => true, 'tl_class' => 'w50'],
            ],
            'link_id' => [
                'label' => ['Button-ID', 'Beispielsweise für Analytics-Events'],
                'inputType' => 'text',
                'eval' => ['tl_class' => 'w50'],
            ],
            'link_url' => [
                'label' => ['Verlinkung', ''],
                'inputType' => 'url',
                'eval' => ['tl_class' => 'w50'],
            ],
            'link_betreff' => [
                'label' => ['Betreffzeile für "mailto:"-Buttons', '(optional, falls Link eine neue Email öffnen soll)'],
                'inputType' => 'text',
                'eval' => ['tl_class' => 'w50'],
            ],
            'new_tab' => [
                'label' => ['Link in neuen Tab öffnen', ''],
                'inputType' => 'checkbox',
                'eval' => ['tl_class' => 'clr'],
            ],
            'link_type' => [
                'label' => ['Optik des Buttons', ''],
                'inputType' => 'select',
                'options' => self::getButtonTypes(),
                'eval' => ['tl_class' => 'w50'],
            ],
            'link_size' => [
                'label' => ['Größe des Buttons', ''],
                'inputType' => 'select',
                'options' => self::getButtonSizes(),
                'eval' => ['tl_class' => 'w50'],
            ],
            /*
              'enable_tracking' => array(
                'label' => array('Klicktracking aktivieren', 'Aktiviert das Klick-Tracking'),
                'inputType' => 'checkbox',
                'eval' => array('tl_class' => 'w50'),
            ),
             */

            'tracking_position' => array(
                'label' => array('Tracking-Position', 'Wird im Analytics Tool definiert'),
                'inputType' => 'text',
                'eval' => array('tl_class' => 'w50'),
                'dependsOn' => array(
                    'field' => 'enable_tracking',
                ),
            ),
        ];
    }
}
