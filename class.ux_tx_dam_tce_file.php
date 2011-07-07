<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Dimitri König (dk@cabag.ch)
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
 * + renames all references on file/directory rename/move
 *
 * @author	Dimitri König <dk@cabag.ch>
 */

class ux_tx_dam_extFileFunctions extends tx_dam_extFileFunctions {
	function getAllTypoLinkFields() {
		$result = array();

		foreach($GLOBALS['TCA'] as $tablename => $table) {
			if(!empty($table['columns'])) {
				foreach($table['columns'] as $columnname => $column) {
					if($column['config']['type'] == 'text' || $column['config']['type'] == 'input') {
						if(!empty($column['config']['softref']) && (stripos($column['config']['softref'], "typolink") !== FALSE || stripos($column['config']['softref'], "url") !== FALSE)) {
							$result[$tablename][] = $columnname;
						}
					}
					if($column['config']['type'] == 'flex') {
						$result[$tablename][] = $columnname;
					}
				}
			}
		}

		return $result;
	}

	function getAllLinks($searchFieldsArray, $theTarget, $theRenameName, $folder) {
		$results = array();

		// let's traverse all configured tables
		foreach ($searchFieldsArray as $table => $fields) {
			
			// if table is not configured, we assume the ext is not installed and therefore no need to check it
			if (!is_array($GLOBALS['TCA'][$table])) continue;
			
			// re-init selectFields for table
			$selectFields = 'uid, pid';
			$selectFields.= ', ' . $GLOBALS['TCA'][$table]['ctrl']['label'] . ', ' . implode(', ', $fields);
			
			// TODO: only select rows that have content in at least one of the relevant fields (via OR)
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $table, 'deleted=0');
			
			// Get record rows of table
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				#t3lib_div::debug($row);
				
				// array to store urls from relevant field contents
				$urls = array();
				
				// flag whether row contains a broken link in some field or not
				$rowContainsBrokenLink = false;
				
				// put together content of all relevant fields
				$haystack = '';

				// get all references
				foreach ($fields as $field) {
					$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
					if ($conf['softref'] && strlen($row[$field]))	{	// Check if a TCA configured field has softreferences defined (see TYPO3 Core API document)
						$softRefs = t3lib_BEfunc::explodeSoftRefParserList($conf['softref']);		// Explode the list of softreferences/parameters
						foreach($softRefs as $spKey => $spParams)	{	// Traverse soft references
							$softRefObj = &t3lib_BEfunc::softRefParserObj($spKey);	// create / get object
							if (is_object($softRefObj))	{	// If there was an object returned...:
								$resultArray = $softRefObj->findRef($table, $field, $row['uid'], $row[$field], $spKey, $spParams);	// Do processing
								if(!empty($resultArray['elements'])) {
									//debug($resultArray['elements'], $table.':'.$field.':'.$row['uid'] . 'vorher');
									foreach($resultArray['elements'] as $element) {
										$r = $element['subst'];
										if(!empty($r)) {
											if(substr($r['recordRef'], 0, 7) == 'tx_dam:') {
												$r['tokenValue'] = $element['matchString'];
											}
											if(!$folder) {
												if($r['tokenValue'] == $theTarget) {
													$results[$table][$row['uid']][$field] = $this->replaceData($row[$field], $theTarget, $theRenameName, $field);
												}
											} else {
												if(strpos($r['tokenValue'], $theTarget) !== false) {
													$results[$table][$row['uid']][$field] = $this->replaceData($row[$field], $theTarget, $theRenameName, $field);
												}
											}
										}
									}
									//debug($resultArray['elements'], $table.':'.$field.':'.$row['uid'] . 'nachher');
								}
							}
						}
					}
					if ($conf['type'] == 'flex') {
						if ($row[$field] && strpos($row[$field], $theTarget) !== false) {
							$results[$table][$row['uid']][$field] = $this->replaceData($row[$field], $theTarget, $theRenameName, $field);
						}
					}
				}
			}
		}
		//die(debug($results, 'results'));
		return $results;
	}

	function replaceData($data, $rawTarget, $rawRenameName, $field) {
		$theTarget = $this->encodeFilenameWithoutPath($rawTarget);
		$theRenameName = $this->encodeFilenameWithoutPath($rawRenameName);

		$result = '';
		switch($field) {
			case 'bodytext':
				$result = str_replace(' '.$theTarget, ' '.$theRenameName, $data);
				break;
			default:
				$result = str_replace($theTarget, $theRenameName, $data);
				break;
		}
		return $result;
	}

	function encodeFilenameWithoutPath($rawFilename) {
		$encodedFilename = rawurlencode($rawFilename);
		return str_replace('%2F', '/', $encodedFilename);
	}
	
	function updateReferences($updateData) {
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->start($updateData,array());
		$tce->process_datamap();
	}
	
	function renameReferences($theTarget, $theRenameName, $folder = false) {
		$theTarget = str_replace(PATH_site, "", $theTarget);
		$theRenameName = str_replace(PATH_site, "", $theRenameName);
		$searchFieldsArray = $this->getAllTypoLinkFields();
		if (!empty($theTarget) && !empty($theRenameName)) {
			$updateData = $this->getAllLinks($searchFieldsArray, $theTarget, $theRenameName, $folder);
			$this->updateReferences($updateData);
		}
	}
	
	function func_move($cmds, $id)	{

		if (!$this->isInit) return FALSE;

			// Initialize and check basic conditions:
		$theFile = $cmds['data'];
		$theDest = $this->is_directory($cmds['target']);	// Clean up destination directory
		$altName = $cmds['altName'];



			// main log entry
		$this->log['cmd']['move'][$id] = array(
				'errors' => array(),
				'orig_filename' => $theFile,
				'target_file' => '',
				'target_folder' => '',
				'target_path' => $theDest,
				);


		if (!$theDest)	{
			$this->writelog(3,2,100,'Destination "%s" was not a directory',array($cmds['target']), 'move', $id);
			return FALSE;
		}
		if (!$this->isPathValid($theFile) || !$this->isPathValid($theDest))	{
			$this->writelog(3,2,101,'Target or destination had invalid path (".." and "//" is not allowed in path). T="%s", D="%s"',array($theFile,$theDest), 'move', $id);
			return FALSE;
		}

			// Processing of file or directory:
		if (@is_file($theFile))	{	// If we are moving a file...
			if ($this->actionPerms['moveFile'])	{
				if (filesize($theFile) < ($this->maxMoveFileSize*1024))	{
					$fI = t3lib_div::split_fileref($theFile);
					if ($altName)	{	// If altName is set, we're allowed to create a new filename if the file already existed
						$theDestFile = $this->getUniqueName($fI['file'], $theDest);
						$fI = t3lib_div::split_fileref($theDestFile);
					} else {
						$theDestFile = $theDest.'/'.$fI['file'];
					}
					if ($theDestFile && !@file_exists($theDestFile))	{
						if ($this->checkIfAllowed($fI['fileext'], $theDest, $fI['file'])) {
							if ($this->checkPathAgainstMounts($theDestFile) && $this->checkPathAgainstMounts($theFile))	{
								if ($this->PHPFileFunctions)	{
									rename($theFile, $theDestFile);
								} else {
									$cmd = 'mv '.escapeshellarg($theFile).' '.escapeshellarg($theDestFile);
									exec($cmd);
								}
								clearstatcache();
								if (@is_file($theDestFile))	{
									$this->renameReferences($theFile, $theDestFile);
									$this->log['cmd']['move'][$id]['target_file'] = $theDestFile;
									
										// update meta data
									if ($this->processMetaUpdate) {
										tx_dam::notify_fileMoved($theFile, $theDestFile);
									}

									$this->writelog(3,0,1,'File "%s" moved to "%s"',array($theFile,$theDestFile), 'move', $id);
									return $theDestFile;
								} else $this->writelog(3,2,109,'File "%s" WAS NOT moved to "%s"! Write-permission problem?',array($theFile,$theDestFile), 'move', $id);
							} else $this->writelog(3,1,110,'Target or destination was not within your mountpoints! T="%s", D="%s"',array($theFile,$theDestFile), 'move', $id);
						} else $this->writelog(3,1,111,'Fileextension "%s" is not allowed in "%s"!',array($fI['fileext'],$theDest.'/'), 'move', $id);
					} else $this->writelog(3,1,112,'File "%s" already exists!',array($theDestFile), 'move', $id);
				} else $this->writelog(3,1,113,'File "%s" exceeds the size-limit of %s bytes',array($theFile,$this->maxMoveFileSize*1024), 'move', $id);
			} else $this->writelog(3,1,114,'You are not allowed to move files','', 'move', $id);
			// FINISHED moving file

		} elseif (@is_dir($theFile)) {	// if we're moving a folder
			if ($this->actionPerms['moveFolder'])	{
				$theFile = $this->is_directory($theFile);
				if ($theFile)	{
					$fI = t3lib_div::split_fileref($theFile);
					if ($altName)	{	// If altName is set, we're allowed to create a new filename if the file already existed
						$theDestFile = $this->getUniqueName($fI['file'], $theDest);
						$fI = t3lib_div::split_fileref($theDestFile);
					} else {
						$theDestFile = $theDest.'/'.$fI['file'];
					}
					if ($theDestFile && !@file_exists($theDestFile))	{
						if (!t3lib_div::isFirstPartOfStr($theDestFile.'/',$theFile.'/'))	{			// Check if the one folder is inside the other or on the same level... to target/dest is the same?
							if ($this->checkIfFullAccess($theDest) || $this->is_webPath($theDestFile)==$this->is_webPath($theFile))	{	// // no moving of folders between spaces
								if ($this->checkPathAgainstMounts($theDestFile) && $this->checkPathAgainstMounts($theFile))	{
									if ($this->PHPFileFunctions)	{
										rename($theFile, $theDestFile);
									} else {
										$cmd = 'mv '.escapeshellarg($theFile).' '.escapeshellarg($theDestFile);
										$errArr = array();
										$retVar = 0;
										exec($cmd,$errArr,$retVar);
									}
									clearstatcache();
									if (@is_dir($theDestFile))	{
										$this->renameReferences($theFile, $theDestFile, true);
										$this->log['cmd']['move'][$id]['target_folder'] = $theDestFile;

											// update meta data
										if ($this->processMetaUpdate) {
											tx_dam::notify_fileMoved($theFile, $theDestFile);
										}

										$this->writelog(3,0,2,'Directory "%s" moved to "%s"',array($theFile,$theDestFile), 'move', $id);
										return $theDestFile;
									} else $this->writelog(3,2,119,'Directory "%s" WAS NOT moved to "%s"! Write-permission problem?',array($theFile,$theDestFile), 'move', $id);
								} else $this->writelog(3,1,120,'Target or destination was not within your mountpoints! T="%s", D="%s"',array($theFile,$theDestFile), 'move', $id);
							} else $this->writelog(3,1,121,'You don\'t have full access to the destination directory "%s"!',array($theDest.'/'), 'move', $id);
						} else $this->writelog(3,1,122,'Destination cannot be inside the target! D="%s", T="%s"',array($theDestFile.'/',$theFile.'/'), 'move', $id);
					} else $this->writelog(3,1,123,'Target "%s" already exists!',array($theDestFile), 'move', $id);
				} else $this->writelog(3,2,124,'Target seemed not to be a directory! (Shouldn\'t happen here!)','', 'move', $id);
			} else $this->writelog(3,1,125,'You are not allowed to move directories','', 'move', $id);
			// FINISHED moving directory

		} else {
			$this->writelog(3,2,130,'The item "%s" was not a file or directory!',array($theFile), 'move', $id);
		}
	}


	/**
	 * Renaming files or foldes (action=5)
	 *
	 * @param	array		$cmds['data'] is the new name. $cmds['target'] is the target (file or dir).
	 * @param	string		$id: ID of the item
	 * @return	string		Returns the new filename upon success
	 */
	function func_rename($cmds, $id)	{

		if (!$this->isInit) return FALSE;


		$theNewName = tx_dam::file_makeCleanName($cmds['data'], true);
#		$theNewName = $this->cleanFileName($cmds['data']);

		if (empty($theNewName))	{ return; }


			// main log entry
		$this->log['cmd']['rename'][$id] = array(
				'errors' => array(),
				'orig_filename' => $cmds['target'],
				'target_file' => $theNewName,
				);


		if (!$this->checkFileNameLen($theNewName))	{
			$this->writelog(5,1,124,'New name "%s" was too long (max %s characters)',array($theNewName,$this->maxInputNameLen), 'rename', $id);
			return;
		}

		$theTarget = $cmds['target'];
		$type = filetype($theTarget);

			// $type MUST BE file or dir
		if (!($type=='file' || $type=='dir'))	{
			$this->writelog(5,2,123,'Target "%s" was neither a directory nor a file!',array($theTarget), 'rename', $id);
			return;
		}

			// Fetches info about path, name, extention of $theTarget
		$fileInfo = t3lib_div::split_fileref($theTarget);

			// The name should be different from the current. And the filetype must be allowed
		if ($fileInfo['file']==$theNewName)	{
			$this->writelog(5,1,122,'Old and new name is the same (%s)',array($theNewName), 'rename', $id);
			return;
		}

		$theRenameName = $fileInfo['path'].$theNewName;

			// check mountpoints
		if (!$this->checkPathAgainstMounts($fileInfo['path']))	{
			$this->writelog(5,1,121,'Destination path "%s" was not within your mountpoints!',array($fileInfo['path']), 'rename', $id);
			return;
		}
			// check if dest exists
		if (@file_exists($theRenameName))	{
			$this->writelog(5,1,120,'Destination "%s" existed already!',array($theRenameName), 'rename', $id);
			return;
		}

		if ($type=='file')	{

				// user have permissions for action
			if (!$this->actionPerms['renameFile'])	{
				$this->writelog(5,1,102,'You are not allowed to rename files!','', 'rename', $id);
				return;
			}

			$fI = t3lib_div::split_fileref($theRenameName);

			if (!$this->checkIfAllowed($fI['fileext'], $fileInfo['path'], $fI['file'])) {
				$this->writelog(5,1,101,'Fileextension "%s" was not allowed!',array($fI['fileext']), 'rename', $id);
				return;
			}

			if (!@rename($theTarget, $theRenameName))	{
				$this->writelog(5,1,100,'File "%s" was not renamed! Write-permission problem in "%s"?',array($theTarget,$fileInfo['path']), 'rename', $id);
				return;
			}
			$this->renameReferences($theTarget, $theRenameName);
			$this->writelog(5,0,1,'File renamed from "%s" to "%s"',array($fileInfo['file'],$theNewName), 'rename', $id);

				// update meta data
			if ($this->processMetaUpdate) {
				tx_dam::notify_fileMoved($theTarget, $theRenameName);
			}

		} elseif ($type=='dir')	{

				// user have permissions for action
			if (!$this->actionPerms['renameFolder'])	{
				$this->writelog(5,1,111,'You are not allowed to rename directories!','', 'rename', $id);
				return;
			}

			if (!@rename($theTarget, $theRenameName))	{
				$this->writelog(5,1,110,'Directory "%s" was not renamed! Write-permission problem in "%s"?',array($theTarget,$fileInfo['path']), 'rename', $id);
				return;
			}
			$this->renameReferences($theTarget, $theRenameName, true);
			$this->writelog(5,0,2,'Directory renamed from "%s" to "%s"',array($fileInfo['file'],$theNewName), 'rename', $id);

				// update meta data
			if ($this->processMetaUpdate) {
				tx_dam::notify_fileMoved($theTarget, $theRenameName);
			}

		} else {
			return;
		}


			// add file to log entry
		$this->log['cmd']['rename'][$id]['target_'.$type] = $theRenameName;

		return $theRenameName;
	}

	/* function func_upload($cmds, $id=false)	{
		if ($id===false) $id=$cmds['data'];

		if (!$this->isInit) return FALSE;

		if (!$_FILES['upload_'.$id]['name'])	{
			return;
		}

			// filename of the uploaded file
		$theFile = $_FILES['upload_'.$id]['tmp_name'];
			// filesize of the uploaded file
		$theFileSize = $_FILES['upload_'.$id]['size'];
			// The original filename

		$theName = tx_dam::file_makeCleanName($_FILES['upload_'.$id]['name']);
#		$theName = $this->cleanFileName($_FILES['upload_'.$id]['name']);

			// main log entry
		$this->log['cmd']['upload'][$id] = array(
				'errors' => array(),
				'orig_filename' => $theName,
				'target_file' => '',
				'target_path' => $this->fileCmdMap['upload'][$id]['target'],
				);

			// Check if the file is uploaded
		if (!(is_uploaded_file($theFile) && $theName))	{
			$this->writelog(1,2,106,'The uploaded file did not exist!','', 'upload', $id);
			return;
		}

			// check upload permissions
		if (!$this->actionPerms['uploadFile'])	{
			$this->writelog(1,1,105,'You are not allowed to upload files!','', 'upload', $id);
			return;
		}

			// check if the file size exceed permissions
		$maxBytes = $this->getMaxUploadSize();
		if (!($theFileSize<($maxBytes)))	{
			$this->writelog(1,1,104,'The uploaded file exceeds the size-limit of %s (%s Bytes).',array(t3lib_div::formatSize($maxBytes), $maxBytes), 'upload', $id);
			return;
		}

			// Check the target dir
		$theTarget = $this->is_directory($cmds['target']);

			// check if target is inside of a mount point
		if (!($theTarget && $this->checkPathAgainstMounts($theTarget.'/')))	{
			$this->writelog(1,1,103,'Destination path "%s" was not within your mountpoints!',array($theTarget.'/'), 'upload', $id);
			return;
		}


			// check if the file extension is allowed
		$fI = t3lib_div::split_fileref($theName);
		if (!($this->checkIfAllowed($fI['fileext'], $theTarget, $fI['file']))) {
			$this->writelog(1,1,102,'Fileextension "%s" is not allowed in "%s"!',array($fI['fileext'],$theTarget.'/'), 'upload', $id);
			return;
		}

			// Create unique file name
		$theNewFile = $this->getUniqueName($theName, $theTarget, $this->dontCheckForUnique);
		if (!$theNewFile)	{
			$this->writelog(1,1,101,'No unique filename available in "%s"!',array($theTarget.'/'), 'upload', $id);
			return;
		}

			// move uploaded file to target location
		t3lib_div::upload_copy_move($theFile,$theNewFile);
		clearstatcache();

			// moving file did not work
		if (!@is_file($theNewFile))	{
			$this->writelog(1,1,100,'Uploaded file could not be moved! Write-permission problem in "%s"?',array($theTarget.'/'), 'upload', $id);
			return;
		}
		$this->internalUploadMap[$id] = $theNewFile;
		$this->writelog(1,0,1,'Uploading file "%s" to "%s"',array($theName, $theNewFile, $id), 'upload', $id);

			// add file to log entry
		$this->log['cmd']['upload'][$id]['target_file'] = $theNewFile;

		$meta = tx_dam::meta_getDataByUid($cmds['data']);
		$this->renameReferences($meta['file_path'] . $meta['file_name'], $theNewFile);

		return $theNewFile;

	} */
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/class.ux_tx_dam_tce_file.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/class.ux_tx_dam_tce_file.php']);
}
?>
