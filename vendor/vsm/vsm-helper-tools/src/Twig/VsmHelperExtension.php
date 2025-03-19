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

namespace Vsm\VsmHelperTools\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Vsm\VsmHelperTools\Helper\BasicHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\EnvHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmHelperTools\Helper\HeadlineHelper;
use Vsm\VsmHelperTools\Helper\ImageHelper;
use Vsm\VsmHelperTools\Helper\VideoHelper;

class VsmHelperExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            // BasicHelper
            new TwigFunction('vsm_clean_color', [BasicHelper::class, 'cleanColor']),
            new TwigFunction('vsm_get_file_info', [BasicHelper::class, 'getFileInfo']),
            
            // HeadlineHelper
            new TwigFunction('vsm_generate_headline_html', [HeadlineHelper::class, 'generateHeadlineHTML']),
            
            // ButtonHelper
            new TwigFunction('vsm_generate_button_html', [ButtonHelper::class, 'generateButtonHTML']),
            
            // ImageHelper Functions
            new TwigFunction('vsm_generate_image_html', [ImageHelper::class, 'generateImageHTML']),
            new TwigFunction('vsm_generate_image_url', [ImageHelper::class, 'generateImageURL']),
            new TwigFunction('vsm_get_svg_code', [ImageHelper::class, 'getSvgCode']),
            
            // VideoHelper
            new TwigFunction('vsm_get_video_src', [VideoHelper::class, 'getVideoSrc']),
            new TwigFunction('vsm_get_video_attributes', [VideoHelper::class, 'getVideoAttributes']),
            
            // EnvHelper
            new TwigFunction('vsm_is_dev', [EnvHelper::class, 'isDev']),
            
            // Global Element Config
            new TwigFunction('vsm_element_config', [GlobalElementConfig::class, 'getGlobalConfig']),
            
            // Weitere Funktionen können hier hinzugefügt werden
            // ...
        ];
    }

    public function getName(): string
    {
        return 'vsm_helper_extension';
    }
} 