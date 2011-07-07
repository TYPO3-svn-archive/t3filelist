<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

if (version_compare(TYPO3_version, '4.5.0') == -1) {
	$t3filelist_version_folder = 'old';
} else {
	$t3filelist_version_folder = TYPO3_version;
}

checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.file_list.inc'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/class.ux_file_list.php');
checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_list.php'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/ux_SC_file_list.php');

if (t3lib_extMgm::isLoaded('cabag_dam')) {
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cabag_dam/class.ux_tx_cabagdam_tce_file_tx_dam_extFileFunctions.php'], t3lib_extMgm::extPath($_EXTKEY).'class.ux_tx_dam_tce_file.php');
} elseif (t3lib_extMgm::isLoaded('dam')) {
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam/lib/class.tx_dam_tce_file.php'], t3lib_extMgm::extPath($_EXTKEY).'class.ux_tx_dam_tce_file.php');
} else {
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extfilefunc.php'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/class.ux_t3lib_extfilefunc.php'); 
}

if (t3lib_extMgm::isLoaded('cabag_langlink')) {
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cabag_langlink/typo3_versions/'.TYPO3_version.'/class.ux_tx_rtehtmlarea_browse_links.php'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/class.ux_tx_rtehtmlarea_browse_links.php');
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cabag_langlink/typo3_versions/'.TYPO3_version.'/class.ux_browse_links.php'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/class.ux_browse_links.php');
} else {
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/class.ux_tx_rtehtmlarea_browse_links.php');
	checkFileExistsAndAssignAsXClass($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php'], t3lib_extMgm::extPath($_EXTKEY).'typo3_versions/'.$t3filelist_version_folder.'/class.ux_browse_links.php');
}

//search filters/engines

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks']['find'] = 'EXT:t3filelist/hooks/class.tx_t3filelist_searchfiles_find.php:tx_t3filelist_searchfiles_find';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks']['scandir'] = 'EXT:t3filelist/hooks/class.tx_t3filelist_searchfiles_scandir.php:tx_t3filelist_searchfiles_scandir';

function checkFileExistsAndAssignAsXClass(&$xclass, $newFile) {
	if (file_exists($newFile)) {
		$xclass = $newFile;
	}
}

?>