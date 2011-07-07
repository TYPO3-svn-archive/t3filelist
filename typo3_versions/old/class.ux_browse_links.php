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

require_once(t3lib_extMgm::extPath('t3filelist').'class.tx_t3filelist_searchfiles.php');

class ux_browse_links extends browse_links {
	function expandFolder($expandFolder=0,$extensionList='')	{
		global $BACK_PATH;

		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{

				// Create header for filelisting:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('files').':');
			
			if($this->act != 'folder') {
				$out.=$this->displaySearchForm();
			}

				// Prepare current path value for comparison (showing red arrow)
			if (!$this->curUrlInfo['value'])	{
				$cmpPath='';
			} else {
				$cmpPath=PATH_site.$this->curUrlInfo['info'];
			}


				// Create header element; The folder from which files are listed.
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
			$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($expandFolder),$titleLen));
			$picon='<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($expandFolder,strlen(PATH_site))).'\');">'.$picon.'</a>';
			if ($this->curUrlInfo['act'] == 'folder' && $cmpPath == $expandFolder)	{
				$out.= '<img'.t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
			}
			$out.=$picon.'<br />';

				// Get files from the folder:
			if ($this->mode == 'wizard' && $this->act == 'folder') {
				$files = t3lib_div::get_dirs($expandFolder);
			} else {
				$files = t3lib_div::getFilesInDir($expandFolder, $extensionList, 1, 1);	// $extensionList='', $prependPath=0, $order='')

				$searchFilesObj = t3lib_div::makeInstance('tx_t3filelist_searchfiles');
				$searchFilesObj->searchFiles($files, $expandFolder);
			}

			$c=0;
			$cc=count($files);
			if (is_array($files))	{
				foreach($files as $filepath)	{
					$c++;
					$fI=pathinfo($filepath);

					if ($this->mode == 'wizard' && $this->act == 'folder') {
						$filepath = $expandFolder.$filepath.'/';
						$icon = '<img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/i/_icon_webfolders.gif', 'width="18" height="16"') . ' alt="" />';
					} else {
							// File icon:
						$icon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));

							// Get size and icon:
						$size = ' (' . t3lib_div::formatSize(filesize($filepath)) . 'bytes)';
						$icon = '<img' . t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/fileicons/' . $icon . '', 'width="18" height="16"') . ' title="' . htmlspecialchars($fI['basename'] . $size) . '" alt="" />';
					}

						// If the listed file turns out to be the CURRENT file, then show blinking arrow:
					if (($this->curUrlInfo['act'] == 'file' || $this->curUrlInfo['act'] == 'folder') && $cmpPath == $filepath) {
						$arrCol='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_left.gif','width="5" height="9"').' class="c-blinkArrowL" alt="" />';
					} else {
						$arrCol='';
					}

						// Put it all together for the file element:
					$out.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join'.($c==$cc?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
							$arrCol.
							'<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($filepath,strlen(PATH_site))).'\');">'.
							$icon.
							htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).
							'</a><br />';
				}
			}
		}
		return $out;
	}

	function TBE_expandFolder($expandFolder=0,$extensionList='',$noThumbs=0)	{
		global $LANG;

		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{
			$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order='')

			// Hook to handle own search filters or expand search
			$hookObjectsArr = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'] as $classRef) {
					$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
				}
			}
			foreach($hookObjectsArr as $hookObj) {
				if (method_exists($hookObj, 'searchFiles')) {
					$hookObj->searchFiles($files, $expandFolder, $this);
				}
			}

			$out.= $this->fileList($files, $expandFolder, $noThumbs);
		}

			// Return accumulated content for filelisting:
		return $out;
	}

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

	function fileList($files, $folderName='', $noThumbs=0) {
		global $LANG, $BACK_PATH;

		$out='';

			// Listing the files:
		if (is_array($files))	{

				// Create headline (showing number of files):
			$filesCount = count($files);
			$out.=$this->barheader(sprintf($GLOBALS['LANG']->getLL('files').' (%s):', $filesCount));
			$out.=$this->displaySearchForm();
			$out.=$this->getBulkSelector($filesCount);

			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

				// Create the header of current folder:
			if($folderName) {
				$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($folderName),$titleLen));
				$out.=$picon.'<br />';
			}

				// Init graphic object for reading file dimensions:
			$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				// Traverse the file list:
			$lines=array();
			foreach($files as $filepath)	{
				$fI=pathinfo($filepath);

					// Thumbnail/size generation:
				if (t3lib_div::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),strtolower($fI['extension'])) && !$noThumbs)	{
					$imgInfo = $imgObj->getImageDimensions($filepath);
					$pDim = $imgInfo[0].'x'.$imgInfo[1].' pixels';
					$clickIcon = t3lib_BEfunc::getThumbNail($BACK_PATH.'thumbs.php',$filepath,'hspace="5" vspace="5" border="1"');
				} else {
					$clickIcon = '';
					$pDim = '';
				}

					// Create file icon:
				$ficon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
				$size=' ('.t3lib_div::formatSize(filesize($filepath)).'bytes'.($pDim?', '.$pDim:'').')';
				$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$ficon,'width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" class="absmiddle" alt="" />';

					// Create links for adding the file:
				if (strstr($filepath,',') || strstr($filepath,'|'))	{	// In case an invalid character is in the filepath, display error message:
					$eMsg = $LANG->JScharCode(sprintf($LANG->getLL('invalidChar'),', |'));
					$ATag = $ATag_alt = "<a href=\"#\" onclick=\"alert(".$eMsg.");return false;\">";
					$bulkCheckBox = '';
				} else {	// If filename is OK, just add it:
					$filesIndex = count($this->elements);
					$this->elements['file_'.$filesIndex] = array(
						'md5' => t3lib_div::shortMD5($filepath),
						'type' => 'file',
						'fileName' => $fI['basename'],
						'filePath' => $filepath,
						'fileExt' => $fI['extension'],
						'fileIcon' => $ficon,
					);
					$ATag = "<a href=\"#\" onclick=\"return BrowseLinks.File.insertElement('file_$filesIndex');\">";
					$ATag_alt = substr($ATag,0,-4).",1);\">";
					$bulkCheckBox = '<input type="checkbox" class="typo3-bulk-item" name="file_'.$filesIndex.'" value="0" /> ';
				}
				$ATag_e='</a>';

					// Create link to showing details about the file in a window:
				$Ahref = $BACK_PATH.'show_item.php?table='.rawurlencode($filepath).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
				$ATag2='<a href="'.htmlspecialchars($Ahref).'">';
				$ATag2_e='</a>';

					// Combine the stuff:
				$filenameAndIcon=$bulkCheckBox.$ATag_alt.$icon.htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).$ATag_e;

					// Show element:
				if ($pDim)	{		// Image...
					$lines[]='
						<tr class="bgColor4">
							<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
							<td>'.$ATag.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet2.gif','width="18" height="16"').' title="'.$LANG->getLL('addToList',1).'" alt="" />'.$ATag_e.'</td>
							<td nowrap="nowrap">'.($ATag2.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLL('info',1).'" alt="" /> '.$LANG->getLL('info',1).$ATag2_e).'</td>
							<td nowrap="nowrap">&nbsp;'.$pDim.'</td>
						</tr>';
					$lines[]='
						<tr>
							<td colspan="4">'.$ATag_alt.$clickIcon.$ATag_e.'</td>
						</tr>';
				} else {
					$lines[]='
						<tr class="bgColor4">
							<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
							<td>'.$ATag.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet2.gif','width="18" height="16"').' title="'.$LANG->getLL('addToList',1).'" alt="" />'.$ATag_e.'</td>
							<td nowrap="nowrap">'.($ATag2.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLL('info',1).'" alt="" /> '.$LANG->getLL('info',1).$ATag2_e).'</td>
							<td>&nbsp;</td>
						</tr>';
				}
				$lines[]='
						<tr>
							<td colspan="3"><img src="clear.gif" width="1" height="3" alt="" /></td>
						</tr>';
			}

				// Wrap all the rows in table tags:
			$out.='



		<!--
			File listing
		-->
				<table border="0" cellpadding="0" cellspacing="1" id="typo3-fileList">
					'.implode('',$lines).'
				</table>';
		}
			// Return accumulated content for filelisting:
		return $out;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/typo3_versions/old/class.ux_browse_links.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/typo3_versions/old/class.ux_browse_links.php']);
}


?>
