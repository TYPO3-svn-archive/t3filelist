<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Dimitri Koenig <dk@cabag.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Handles searching files with different engines
 *
 * @author	Dimitri Koenig <dk@cabag.ch>
 */

require_once(t3lib_extMgm::extPath('t3filelist').'hooks/class.tx_t3filelist_searchfiles_find.php');
require_once(t3lib_extMgm::extPath('t3filelist').'hooks/class.tx_t3filelist_searchfiles_scandir.php');

class tx_t3filelist_searchfiles {
	function searchFiles(&$files, $expandFolder)	{
		$ext_conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['t3filelist']);
		$searchEngine = trim($ext_conf['searchEngine']);

		if (empty($searchEngine) || !$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'][$searchEngine]) {
			$searchEngine = 'find';
		}

		$engineClassRef = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'][$searchEngine];
		$engineClassObj = &t3lib_div::getUserObj($engineClassRef);
		$engineClassObj->searchFiles($files, $expandFolder);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/class.tx_t3filelist_searchfiles.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/class.tx_t3filelist_searchfiles.php']);
}

?>
