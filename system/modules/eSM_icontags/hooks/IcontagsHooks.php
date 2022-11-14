<?php

/**
 * eSales Media Icontags for Contao Open Source CMS
 *
 * Copyright (C) 2015 eSalesMedia
 *
 * @package    eSM_icontags
 * @link       http://www.esales-media.de
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 *
 * @author     Benjamin Roth <benjamin@esales-media.de>
 */

namespace eSM_icontags;

class IcontagsHooks extends \Controller
{
	public function eSMReplaceInsertTags($strTag)
	{
		$elements = explode('::', $strTag);

		if ($elements[0] == 'icon')
		{
			if ($elements[1])
			{
				$fragments = explode(':', $elements[1]);
			}
			if ($fragments[0] && $fragments[1])
			{
				$chunks = explode(' ',$fragments[1]);

				$strPrefix = $fragments[0];
				$strClasses = '';
				foreach ($chunks as $chunk)
				{
					$strClasses .= ' '.$strPrefix.'-'.$chunk;
				}

				if ($fragments[2])
				{
					$strClasses .= ' '.$fragments[2];
				}

				return sprintf('<i class="%s%s"></i>',$strPrefix,$strClasses);
			}
		} elseif ($elements[0] == 'icon_pl')
		{
			if ($elements[1])
			{
				$fragments = explode(':', $elements[1]);
			}
			if ($fragments[0] && $fragments[1])
			{
				$chunks = explode(' ',$fragments[1]);

				$strClasses = '';
				foreach ($chunks as $chunk)
				{
					$strClasses .= ' '.$chunk;
				}

				if ($fragments[2])
				{
					$strClasses .= ' '.$fragments[2];
				}

				return sprintf('<i class="%s%s"></i>',$fragments[0],$strClasses);
			}
		}

		return false;
	}
}