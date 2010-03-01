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
 * Displays a search input field for searching for files
 *
 * @author	Dimitri Koenig <dk@cabag.ch>
 */

require_once(t3lib_extMgm::extPath('t3filelist').'hooks/class.tx_t3filelist_searchfiles.php');

class ux_fileList extends fileList {
	function displaySearchForm() {
		$out = '<div id="fileSearch" style="margin: 5px 0">
				<form action="" method="post" name="searchFilesForm">
				<label for="fileSearchInput">'.$GLOBALS['LANG']->sL('LLL:EXT:t3filelist/locallang.php:searchFormLabel', 1).'</label>
				<input name="fileSearchInput" value="'.htmlspecialchars(t3lib_div::_GP('fileSearchInput')).'"/>
				<input type="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:t3filelist/locallang.php:SearchFormSend', 1).'"/>
				</form>
			</div>';

		return $out;
	}

	function generateList()	{
		$this->dirs = $this->readDirectory($this->path,'dir,link');
		$this->files = $this->readDirectory($this->path,'file');

		$this->totalItems=count($this->dirs['sorting'])+count($this->files['sorting']);
		$this->HTMLcode.=$this->displaySearchForm().$this->getTable($this->files, $this->dirs, 'fileext,tstamp,size,rw,_REF_');
	}

	function readDirectory($path,$type,$extList='')	{
		$items = Array('files'=>array(), 'sorting'=>array());
		$path = $GLOBALS['SOBE']->basicFF->is_directory($path);	// Cleaning name...

		if($path && $GLOBALS['SOBE']->basicFF->checkPathAgainstMounts($path.'/'))	{
			$d = @dir($path);
			$tempArray=Array();
			if (is_object($d))	{
				while(false !== ($entry=$d->read())) {
					if ($entry!='.' && $entry!='..')	{
						$wholePath = $path.'/'.$entry;		// Because of odd PHP-error where  <br />-tag is sometimes placed after a filename!!
						if (@file_exists($wholePath) && (!$type || t3lib_div::inList($type,filetype($wholePath))))	{
							if ($extList)	{
								$fI = t3lib_div::split_fileref($entry);
								if (t3lib_div::inList($extList,$fI['fileext']))	{
									$tempArray[] = $wholePath;
								}
							} else {
								$tempArray[] = $wholePath;
							}
						}
					}
				}
				$d->close();
			}

			if($type != 'file' && t3lib_div::_GP('fileSearchInput')) {
				$tempArray = array();
			}
			
			// Hook to handle own search filters or expand search
			$hookObjectsArr = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'searchFiles')) {
					if($type == 'file') {
						$hookObj->searchFiles($tempArray, $path, $this);
					}
				}
			}

			// Get fileinfo
			reset($tempArray);
			while (list(,$val)=each($tempArray))	{
				$temp = $GLOBALS['SOBE']->basicFF->getTotalFileInfo($val);
				$items['files'][] = $temp;
				if ($this->sort)	{
					$items['sorting'][] = strtoupper($temp[$this->sort]);
				} else {
					$items['sorting'][] = '';
				}
			}
				// Sort if required
			if ($this->sort)	{
				if (!$this->sortRev)	{
					asort($items['sorting']);
				} else {
					arsort($items['sorting']);
				}
			}
		}
		return $items;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/class.ux_file_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/class.ux_file_list.php']);
}

?>
