<?php

// HTML in News-Überschriften erlauben
$GLOBALS['TL_DCA']['tl_news']['fields']['headline']['eval']['preserveTags'] = true;
$GLOBALS['TL_DCA']['tl_events']['fields']['headline']['eval']['preserveTags'] = true;