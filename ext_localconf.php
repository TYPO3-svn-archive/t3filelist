<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extfilefunc.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_t3lib_extfilefunc.php';
$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_file_list.php';

if (t3lib_extMgm::isLoaded('cabag_langlink')) {
	if($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']) {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cabag_langlink/typo3_versions/'.TYPO3_VERSION.'/class.ux_tx_rtehtmlarea_browse_links.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_tx_rtehtmlarea_browse_links.php'; 
	} else {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_tx_rtehtmlarea_browse_links.php';
	}

	if($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php']) {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cabag_langlink/typo3_versions/'.TYPO3_VERSION.'/class.ux_browse_links.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_browse_links.php'; 
	} else {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_browse_links.php';
	}
} else {
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_tx_rtehtmlarea_browse_links.php';
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_browse_links.php';

}

//search filters/engines

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3filelist/ux_browse_links.php']['searchHooks'][] = 'EXT:t3filelist/hooks/class.tx_t3filelist_searchfiles.php:tx_t3filelist_searchfiles';

?>