<?php
/***************************************************************
*  Copyright notice
*
*  (c) Dimitri Koenig <dk@cabag.ch>
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
 * searches Files
 *
 * @author	Dimitri Koenig <dk@cabag.ch>
 */

class tx_t3filelist_searchfiles {
	function searchFiles(&$files, $expandFolder, &$reference) {
		$value = htmlspecialchars(t3lib_div::_GP('fileSearchInput'));
		if(!empty($value)) {
			$foundFiles = array();
			exec('find "'.escapeshellcmd($expandFolder).'" -type f -iname "*'.escapeshellcmd($value).'*"', $output);
			if(!empty($output)) {
				foreach($output as $filename) {
					$foundFiles[md5($filename)] = $filename;
				}
			}
			$files = $foundFiles;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/hooks/class.tx_t3filelist_searchfiles.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/hooks/class.tx_t3filelist_searchfiles.php']);
}


?>
