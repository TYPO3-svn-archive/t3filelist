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

class ux_tx_rtehtmlarea_browse_links extends tx_rtehtmlarea_browse_links {
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

	function main_rte($wiz=0)	{
		global $LANG, $BE_USER, $BACK_PATH;

			// Starting content:
		$content=$this->doc->startPage($LANG->getLL('Insert/Modify Link',1));

			// Initializing the action value, possibly removing blinded values etc:
		$this->allowedItems = explode(',','page,file,url,mail,spec');

			// Calling hook for extra options
		foreach($this->hookObjects as $hookObject) {
			$this->allowedItems = $hookObject->addAllowedItems($this->allowedItems);
		}

		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->buttonConfig['options.']['removeItems'],1));
		} else {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1));
		}
		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems)) {
			$this->act = current($this->allowedItems);
		}

			// Making menu in top:
		$menuDef = array();
		if (!$wiz && $this->curUrlArray['href'])	{
			$menuDef['removeLink']['isActive'] = $this->act=='removeLink';
			$menuDef['removeLink']['label'] = $LANG->getLL('removeLink',1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="plugin.unLink();return false;"';
		}
		if (in_array('page',$this->allowedItems)) {
			$menuDef['page']['isActive'] = $this->act=='page';
			$menuDef['page']['label'] = $LANG->getLL('page',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=page&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('file',$this->allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='file';
			$menuDef['file']['label'] = $LANG->getLL('file',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=file&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('url',$this->allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='url';
			$menuDef['url']['label'] = $LANG->getLL('extUrl',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=url&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('mail',$this->allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='mail';
			$menuDef['mail']['label'] = $LANG->getLL('email',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=mail&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec',$this->allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act=='spec';
			$menuDef['spec']['label'] = $LANG->getLL('special',1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=spec&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}

			// call hook for extra options
		foreach($this->hookObjects as $hookObject) {
			$menuDef = $hookObject->modifyMenuDefinition($menuDef);
		}

		$content .= $this->doc->getTabMenuRaw($menuDef);

			// Adding the menu and header to the top of page:
		$content.=$this->printCurrentUrl($this->curUrlInfo['info']).'<br />';

			// Depending on the current action we will create the actual module content for selecting a link:
		switch($this->act)	{
			case 'mail':
				$extUrl='
			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td>'.$LANG->getLL('emailAddress',1).':</td>
								<td><input type="text" name="lemail"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='mail'?$this->curUrlInfo['info']:'').'" /> '.
									'<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="browse_links_setTarget(\'\');browse_links_setHref(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
				$content.=$this->addAttributesForm();
			break;
			case 'url':
				$extUrl='
			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td>URL:</td>
								<td><input type="text" name="lurl"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='url'?$this->curUrlInfo['info']:'http://').'" /> '.
									'<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="if (/^[A-Za-z0-9_+]{1,8}:/.test(document.lurlform.lurl.value)) { browse_links_setHref(document.lurlform.lurl.value); } else { browse_links_setHref(\'http://\'+document.lurlform.lurl.value); }; return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
				$content.=$this->addAttributesForm();
			break;
			case 'file':
				$content.=$this->addAttributesForm();

				$foldertree = t3lib_div::makeInstance('tx_rtehtmlarea_folderTree');
				$tree=$foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act']!='file')	{
					$cmpPath='';
				} elseif (substr(trim($this->curUrlInfo['info']),-1)!='/')	{
					$cmpPath=PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder)) $this->expandFolder = $cmpPath;
				} else {
					$cmpPath=PATH_site.$this->curUrlInfo['info'];
				}

				list(,,$specUid) = explode('_',$this->PM);
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);

				// Create upload/create folder forms, if a path is given:
				if ($BE_USER->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
					$path=$this->expandFolder;
					if (!$path || !@is_dir($path))	{
						$path = $this->fileProcessor->findTempFolder().'/';	// The closest TEMP-path is found
					}
					if ($path!='/' && @is_dir($path)) {
						$uploadForm=$this->uploadForm($path);
						$createFolder=$this->createFolder($path);
					} else {
						$createFolder='';
						$uploadForm='';
					}
					$content.=$uploadForm;
					if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.createFoldersInEB')) {
						$content.=$createFolder;
					}
				}



				$content.= '
			<!--
			Wrapper table for folder tree / file list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($LANG->getLL('folderTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$files.'</td>
						</tr>
					</table>
					';
			break;
			case 'spec':
				if (is_array($this->thisConfig['userLinks.']))	{
					$subcats=array();
					$v=$this->thisConfig['userLinks.'];
					reset($v);
					while(list($k2)=each($v))	{
						$k2i = intval($k2);
						if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{

								// Title:
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i.'.']['url'];
							} else {
								$title=$LANG->sL($title);
							}
								// Description:
							$description=$v[$k2i.'.']['description'] ? $LANG->sL($v[$k2i.'.']['description'],1).'<br />' : '';

								// URL + onclick event:
							$onClickEvent='';
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="browse_links_setTarget('".$v[$k2i.'.']['target']."');";
							$v[$k2i.'.']['url'] = str_replace('###_URL###',$this->siteURL,$v[$k2i.'.']['url']);
							if (substr($v[$k2i.'.']['url'],0,7)=="http://" || substr($v[$k2i.'.']['url'],0,7)=='mailto:')	{
								$onClickEvent.="cur_href=unescape('".rawurlencode($v[$k2i.'.']['url'])."');link_current();";
							} else {
								$onClickEvent.="link_spec(unescape('".$this->siteURL.rawurlencode($v[$k2i.'.']['url'])."'));";
							}

								// Link:
							$A=array('<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">','</a>');

								// Adding link to menu of user defined links:
							$subcats[$k2i]='
								<tr>
									<td class="bgColor4">'.$A[0].'<strong>'.htmlspecialchars($title).($this->curUrlInfo['info']==$v[$k2i.'.']['url']?'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" />':'').'</strong><br />'.$description.$A[1].'</td>
								</tr>';
						}
					}

						// Sort by keys:
					ksort($subcats);

						// Add menu to content:
					$content.= '
			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>'.$LANG->getLL('special',1).'</strong></td>
							</tr>
							'.implode('',$subcats).'
						</table>
						';
				}
			break;
			case 'page':
				$content.=$this->addAttributesForm();

				$pagetree = t3lib_div::makeInstance('tx_rtehtmlarea_pageTree');
				$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
				$pagetree->addField('nav_title');
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();
				$content.= '
			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($LANG->getLL('pageTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$cElements.'</td>
						</tr>
					</table>
					';
			break;
			default:
					// call hook
				foreach($this->hookObjects as $hookObject) {
					$content .= $hookObject->getTab($this->act);
				}

			break;
		}

			// End page, return content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/typo3_versions/old/class.ux_tx_rtehtmlarea_browse_links.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/typo3_versions/old/class.ux_tx_rtehtmlarea_browse_links.php']);
}

?>