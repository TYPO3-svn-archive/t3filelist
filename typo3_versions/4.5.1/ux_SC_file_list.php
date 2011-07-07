<?php
/**
 * Adds search file form
 */
class ux_SC_file_list extends SC_file_list {
	function printContent()	{
		$this->content = preg_replace('/(<form[^>]*dblistForm[^>]*>)/is', $this->displaySearchForm() . '$1', $this->content);

		echo $this->content;
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/typo3_versions/'.TYPO3_version.'/ux_SC_file_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3filelist/typo3_versions/'.TYPO3_version.'/ux_SC_file_list.php']);
}

?>
