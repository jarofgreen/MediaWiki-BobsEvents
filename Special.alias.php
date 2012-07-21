<?php
/**
 * Internationalisation file for extension Events.
 * See files in subdirectory ./i18n for message
 * translations.
 *
 * @file
 * @ingroup Extensions
 */
$aliases = array();

{

	$dirname = dirname(__FILE__).'/i18n';
	$dir = @opendir($dirname);
	$loadedFiles = '';
	$tmpaliases = array();

	if ($dir) {

		while ($file = @readdir($dir)) {
			if (preg_match("/\\.specialalias\\.php\$/s", $file)) {
				$fn = "$dirname/$file";
				try {
					require_once($fn);
				}
				catch(Exception $e) {
					throw new MWException("unable to load alias file: $file");
				}
				if ($loadedFiles) $loadedFiles .= ', ';
				$loadedFiles .= $file;
			}
		}
		
		@closedir($dir);
		$tmpaliases = $aliases;
	}
	$enset = isset($aliases['en']);
	if (!$enset) {
 		throw new MWException('you must define aliases for English. Loaded files are: '.$loadedFiles.".\nAliases = ".print_r($aliases,true)."\nTmp = ".print_r($tmpaliases,true));
	}
}

?>
