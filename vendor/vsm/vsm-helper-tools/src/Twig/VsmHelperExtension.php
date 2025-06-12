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
use Vsm\VsmHelperTools\Helper\EmailHelper;
use Vsm\VsmHelperTools\Helper\EnvHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmHelperTools\Helper\HeadlineHelper;
use Vsm\VsmHelperTools\Helper\ImageHelper;
use Vsm\VsmHelperTools\Helper\SocialMetaHelper;
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
            new TwigFunction('vsm_get_button_config', [ButtonHelper::class, 'getButtonConfig']),
            
            // ImageHelper
            new TwigFunction('vsm_generate_image_html', [ImageHelper::class, 'generateImageHTML']),
            new TwigFunction('vsm_generate_image_url', [ImageHelper::class, 'generateImageURL']),
            new TwigFunction('vsm_get_svg_code', [ImageHelper::class, 'getSvgCode']),
            
            // VideoHelper
            new TwigFunction('vsm_render_video', [VideoHelper::class, 'renderVideo']),
            new TwigFunction('vsm_is_video_format', [VideoHelper::class, 'isVideoFormat']),
            
            // EnvHelper
            new TwigFunction('vsm_is_backend', [EnvHelper::class, 'isBackend']),
            new TwigFunction('vsm_is_frontend', [EnvHelper::class, 'isFrontend']),
            
            // EmailHelper
            new TwigFunction('vsm_load_template', [EmailHelper::class, 'loadTemplate']),
            new TwigFunction('vsm_render_email_template', [EmailHelper::class, 'renderTemplate']),
            new TwigFunction('vsm_get_rendered_email', [EmailHelper::class, 'getRenderedEmail']),
            new TwigFunction('vsm_wrap_email_template', [EmailHelper::class, 'wrapInBasicEmailTemplate']),
            
            // SocialMetaHelper
            new TwigFunction('vsm_generate_hero_social_meta', [SocialMetaHelper::class, 'generateHeroSocialMeta']),
            
            // GlobalElementConfig
            new TwigFunction('vsm_get_animations', [GlobalElementConfig::class, 'getAnimations']),
            new TwigFunction('vsm_get_color_options', [GlobalElementConfig::class, 'getColorOptions']),
            new TwigFunction('vsm_get_text_align_options', [GlobalElementConfig::class, 'getTextAlignOptions']),
            new TwigFunction('vsm_get_image_size_options', [GlobalElementConfig::class, 'getImageSizeOptions']),
        ];
    }

    public function getName(): string
    {
        return 'vsm_helper_extension';
    }
} 