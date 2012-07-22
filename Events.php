<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

/**
 * CONFIGURATION 
 * These variables may be overridden in LocalSettings.php after you include the
 * extension file.
 */

/** TimeZone used for all events **/
$wfEventsDefaultTimeZone = "Europe/London";

/** REGISTRATION */
$wgExtensionFunctions[] = 'wfSetupEvents';
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'BobEvents',
	'version' => '0.1',
	'url' => 'http://jarofgreen.co.uk',
	'author' => array('[http://jarofgreen.co.uk James Baster]','Aran Clary Deltac', 'Sylvain Machefert', '[[:Wikipedia:User:sgalland-arakhne|Stéphane GALLAND]]'),
	'descriptionmsg' => 'events_desc',
);

$wgAutoloadClasses['ExtEvents'] = dirname(__FILE__).'/Events.class.php';
$wgAutoloadClasses['ExtEventObject'] = dirname(__FILE__).'/EventObject.class.php';
$wgAutoloadClasses['ExtSpecialEvents'] = dirname(__FILE__).'/SpecialEvents.class.php';
$wgAutoloadClasses['ExtSpecialEventsExport'] = dirname(__FILE__).'/SpecialEventsExport.class.php';
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

	$wgSpecialPages['SpecialEvents'] = 'ExtSpecialEvents';
	$wgSpecialPageGroups['SpecialEvents'] = 'other';
	$wgSpecialPages['SpecialClearEvents'] = 'ExtSpecialClearEvents';
	$wgSpecialPageGroups['SpecialClearEvents'] = 'other';	
	$wgSpecialPages['SpecialEventsExport'] = 'ExtSpecialEventsExport';
	$wgSpecialPageGroups['SpecialEventsExport'] = 'other';	
}

function expandEvents( $input, array $args, Parser $parser, PPFrame $frame ) {
	global $wfEventsDefaultTimeZone;
	$event = new ExtEventObject();
	$event->parseText($input);

	$dateTimeObj = new DateTime("now",new DateTimeZone($wfEventsDefaultTimeZone));
	
	
	$out = '<div class="catlinks">';
	$out .= "Event ".htmlspecialchars($event->getSummary());
	
	$dateTimeObj->setTimestamp($event->getStartTimeStamp());
	$out .= " From ". $dateTimeObj->format("g:ia D jS M Y");
	
	$dateTimeObj->setTimestamp($event->getEndTimeStamp());
	$out .= " To ". $dateTimeObj->format("g:ia D jS M Y");
			
	$out .= "</div>";
	return $out;
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
		$parser->setHook( 'event', 'expandEvents' );
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

