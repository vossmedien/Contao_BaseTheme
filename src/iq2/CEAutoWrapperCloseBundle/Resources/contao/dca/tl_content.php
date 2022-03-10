<?php


$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('iq2\CEAutoWrapperCloseBundle\Resources\contao\classes\RSCustomElementHelper','createWrapperStop');
$GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][] = array('iq2\CEAutoWrapperCloseBundle\Resources\contao\classes\RSCustomElementHelper','createWrapperStopOnCopy');

