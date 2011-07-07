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
 * search files with 'grob' php method especially for windows systems
 *
 * @author	Dimitri Koenig <dk@cabag.ch>
 */

class tx_t3filelist_searchfiles_scandir {
	var $searchWord = '';

	function searchFiles(&$files, $expandFolder) {
		$this->searchWord = htmlspecialchars(t3lib_div::_GP('fileSearchInput'));
		if(!empty($this->searchWord)) {
			$files = $this->get_files($expandFolder);
		}
	}

	function get_files($root_dir, $all_data = array()) {
		$dir_content = array_diff(scandir($root_dir), array('.', '..'));
		foreach ($dir_content as $key => $content) {
			$path = $root_dir . '/' . $content;
			if (is_file($path) && is_readable($path)) {
				if (strpos($content, $this->searchWord) !== FALSE) {
					$all_data[] = $path;
				}
			} else if (is_dir($path) && is_readable($path)) {
				$all_data = $this->get_files($path, $all_data);
			}
		}
		return $all_data;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/hooks/class.tx_t3filelist_searchfiles_scandir.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/hooks/class.tx_t3filelist_searchfiles_scandir.php']);
}


?>
