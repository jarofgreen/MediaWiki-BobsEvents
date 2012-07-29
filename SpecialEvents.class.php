<?php

require_once(dirname(__FILE__).'/EventUtil.php');

class ExtSpecialEvents extends SpecialPage
{

	function __construct()
	{
		parent::__construct(
			'SpecialEvents', // name
			'', // mandatory user right
			true, // listed in Special:SpecialPages
			false, // no static call back function -> use execute()
			'default', // included file by execute()
			false); // includable with {{Special:Events}}
		if ( function_exists( 'wfLoadExtensionMessages' ) ) { 	
			wfLoadExtensionMessages('SpecialEvents');
		}
	}

	/** Invoked when the special page should be executed.
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser, $wfEventsDefaultTimeZone;
		
		$this->setHeaders();

		$search = new ExtEventSearch();
		$search->parseArgsString($par);		
		$dbr =& ExtEventUtil::getDatabase();
		$res = $search->getDataBaseResults($dbr);
		if (!$res) {
			$wgOut->addHTML(ExtEventUtil::errorMsg('specialevents_empty_list'));
			return;
		}

		$out = '';
		$dateTimeObj = new DateTime("now",new DateTimeZone($wfEventsDefaultTimeZone));
	
		// Generate HTML list
		$pageTitleBuffer = array();

		while ($eventData = $dbr->fetchRow( $res )) {
			
			if (!isset($pageTitleBuffer[$eventData['page_id']])) $pageTitleBuffer[$eventData['page_id']] = Title::nameOf($eventData['page_id']);
			$pageTitle =  $pageTitleBuffer[$eventData['page_id']];
			
			$event = new ExtEventObject();
			$event->setFromDBRow($eventData, $pageTitleBuffer[$eventData['page_id']]);

			$text = preg_replace("/[\n\r\f]+/s", ' ', $event->getSummaryForFeed());
			$pagelink = "[[$pageTitle|&rarr; $pageTitle]]";

			if ($out) $out .= "|-\n";

			$out .= "| $text";
			if ($event->getURL()) $out .= " ".$event->getURL();
			$out .= "\n";
			$out .= "| width=20% | \n";
			
			$out .= "|-\n";
			
			$dateTimeObj->setTimestamp($event->getStartTimeStamp());
			$out .= " | From ". $dateTimeObj->format("g:ia D jS M Y");

			$dateTimeObj->setTimestamp($event->getEndTimeStamp());
			$out .= " To ". $dateTimeObj->format("g:ia D jS M Y")."\n";

			$out .= "| width=20% | $pagelink\n";
		}

		if ($out) {
			$out = "[[Special:SpecialEventsExport/".$search->getArgsString()."|Download events (iCal)]]\n". 
					"{| class=\"frametable\"\n".
				"! ".wfMsgExt(
                                        'specialevents_header_text',
                                        array( 'escape', 'parsemag', 'content' ))."\n".
				"! ".wfMsgExt(
                                        'specialevents_header_follow',
                                        array( 'escape', 'parsemag', 'content' ))."\n".
				"|-\n".
				$out.
				"|}\n";
		}

		$wgOut->addWikiText( $out );
	}

}

