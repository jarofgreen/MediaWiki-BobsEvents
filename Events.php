<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * CONFIGURATION 
 * These variables may be overridden in LocalSettings.php after you include the
 * extension file.
 */

/** Number of days in the past before which events are discarted.
 * null means default value (30).
 */
$wgEventsOldAge = null;

/** Number of days in the future after which events are discarted.
 * null means default value (365).
 */
$wgEventsYoungAge = null;

/** Indicates if the box for important events should be rendered.
 * To enable the event box, this global variable must be true,
 * AND the current skin must implement a function named
 * isEventsExtensionImportantEventEnabled() which replies
 * if the skin support the event box style.
 */
$wgEventExtensionRenderImportantEventBox = true;

/** Is the URL to the icon which is displayed to close the event popup box.
 * Expected sizes: 12x12 or 16x16
 */
$wgEventsCloseButtonIcon = '/extensions/Events/images/close.png';


/** REGISTRATION */
$wgExtensionFunctions[] = 'wfSetupEvents';
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'Events',
	'version' => '3.1',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Events',
	'author' => array('Aran Clary Deltac', 'Sylvain Machefert', '[[:Wikipedia:User:sgalland-arakhne|Stéphane GALLAND]]'),
	'descriptionmsg' => 'events_desc',
);

$wgAutoloadClasses['ExtEvents'] = dirname(__FILE__).'/Events.class.php';
$wgAutoloadClasses['ExtSpecialEvents'] = dirname(__FILE__).'/SpecialEvents.class.php';
$wgAutoloadClasses['ExtSpecialClearEvents'] = dirname(__FILE__).'/SpecialClearEvents.class.php';
$wgExtensionMessagesFiles['Events'] = dirname(__FILE__) . '/Events.i18n.php';
$wgExtensionMessagesFiles['SpecialEvents'] = dirname(__FILE__) . '/Special.i18n.php';
$wgExtensionAliasesFiles['SpecialEvents'] = dirname(__FILE__) . '/Special.alias.php';
$wgExtensionMessagesFiles['SpecialClearEvents'] = dirname(__FILE__) . '/Special.i18n.php';

/** USER RIGHT REGISTRATION */
$wgAvailableRights[]='events_clear';
$wgGroupPermissions['sysop']['events_clear'] = true;

function wfSetupEvents()
{
	global $wgEventsHookStub, $wgHooks, $wgParser;
	global $wgSpecialPages, $wgSpecialPageGroups;

	$wgEventsHookStub = new Events_HookStub;

	$wgHooks['LanguageGetMagic'][] = array( &$wgEventsHookStub, 'getMagicWords' );
	$wgHooks['ParserFirstCallInit'][] = array( &$wgEventsHookStub, 'registerParser' );
	$wgHooks['ParserClearState'][] = array( &$wgEventsHookStub, 'clearState' );
	$wgHooks['ArticleSave'][] = array( &$wgEventsHookStub, 'saveArticlePre' );
	$wgHooks['ArticleSaveComplete'][] = array( &$wgEventsHookStub, 'saveArticlePost' );
	$wgHooks['OutputPageBeforeHTML'][] = array( &$wgEventsHookStub, 'outputPageBeforeHTML' );

	$wgSpecialPages['SpecialEvents'] = 'ExtSpecialEvents';
	$wgSpecialPageGroups['SpecialEvents'] = 'other';
	$wgSpecialPages['SpecialClearEvents'] = 'ExtSpecialClearEvents';
        $wgSpecialPageGroups['SpecialClearEvents'] = 'other';	
}

/**
 * Stub class to defer loading of the bulk of the code until a function is
 * actually used.
 */
class Events_HookStub
{
	var $realObj = null;
	var $evtMagicWords = null;

	public function registerParser( $parser )
	{
		require( dirname(__FILE__) . '/Events.mapping.magic.php');
		foreach($tagMapping as $magicWord => $phpFunction) {
			$parser->setHook( $magicWord, array( &$this, $phpFunction ) );
		}
		foreach($functionMapping as $magicWord => $phpFunction) {
			$parser->setFunctionHook( $magicWord, array( &$this, $phpFunction ) );
                }
		return true;
	}

	/** Replies magic word for given language.
	 */
	public function getMagicWords( &$globalMagicWords, $langCode = 'en' )
	{
		if ( is_null( $this->evtMagicWords ) ) {
			$magicWords = array();
			$dirname = dirname(__FILE__).'/i18n';
        		$dir = @opendir($dirname);
		        if ($dir) {
		                while ($file = @readdir($dir)) {
                		        if (preg_match("/\\.magic\\.php\$/s", $file)) {
                                		$fn = "$dirname/$file";
		                                require_once($fn);
                		        }
		                }
                		@closedir($dir);
		        }

			if (array_key_exists($langCode, $magicWords)) {
				$this->evtMagicWords = $magicWords[$langCode];
			}
			else {
				$this->evtMagicWords = $magicWords['en'];
			}
		}

		foreach($this->evtMagicWords as $word => $language) {
			$globalMagicWords[$word] = $language;
		}
		return true;
	}

	/** Defer ParserClearState */
	public function clearState( $parser )
	{
		if ( !is_null( $this->realObj ) ) {
			$this->realObj->clearState( $parser );
		}
		$this->evtMagicWords = null;
		return true;
	}

	/** Pass through function call */
	public function __call( $name, $args )
	{
		if ( is_null( $this->realObj ) ) {
			$this->realObj = new ExtEvents;
			$this->realObj->clearState( $args[0] );
		}
		return call_user_func_array( array( $this->realObj, "$name" ), $args );
	}
}

?>
