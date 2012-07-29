<?php

require_once(dirname(__FILE__).'/EventUtil.php');

class ExtSpecialEventsExport extends SpecialPage
{

	function __construct()
	{
		parent::__construct(
			'SpecialEventsExport', // name
			'', // mandatory user right
			true, // listed in Special:SpecialPages
			false, // no static call back function -> use execute()
			'default', // included file by execute()
			false); // includable with {{Special:EventsExport}}
	}

	/** Invoked when the special page should be executed.
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser, $wgServer;

		$serverNameOnly = $wgServer;
		if (substr($serverNameOnly,0,7) == "http://") $serverNameOnly = substr($serverNameOnly,7);
		
		// Parse special page arguments
		$args = array();
		parse_str($par, $args);

		$this->setHeaders();

		$dbr =& ExtEventUtil::getDatabase();

		// Build SQL query options
		$options = array( 'ORDER BY'=>'start_at ASC' );

		if (isset($args['limit']) && $args['limit']) {
					$options['LIMIT'] = $args['limit'];
		}
		
		// Run the SQL.
		$res = $dbr->select(
				'events', 
				array('page_id','start_at','end_at','summary','deleted','url'), 
				'(end_at > '.time().')',
				'Database::select',
				$options);			 

		
		$dateTimeObj = new DateTime("now",new DateTimeZone(("UTC")));


		$wgOut->disable();
		header( 'Content-type: text/calendar;' );

		$this->printLine('BEGIN','VCALENDAR');
		$this->printLine('VERSION','2.0');
		$this->printLine('PRODID','-//JarOfGreen//NONSGML MediaWiki BobEvents//EN'); 
		
		$pageTitleBuffer = array();
		
		while ($eventData = $dbr->fetchRow( $res )) {
			
			
			if (!isset($pageTitleBuffer[$eventData['page_id']])) $pageTitleBuffer[$eventData['page_id']] = Title::nameOf($eventData['page_id']);
			$pageTitle =  $pageTitleBuffer[$eventData['page_id']];
			
			$event = new ExtEventObject();
			$event->setFromDBRow($eventData, $pageTitle);

			
			$this->printLine('BEGIN','VEVENT');
			$this->printLine('UID','p'.$event->getPageId().'-s'.$event->getStartTimeStamp().'@'.$serverNameOnly);
			
			
			$dateTimeObj->setTimestamp($event->getStartTimeStamp());
			$this->printLine('DTSTART',$dateTimeObj->format("Ymd")."T".$dateTimeObj->format("His")."Z");

			$dateTimeObj->setTimestamp($event->getEndTimeStamp());
			$this->printLine('DTEND',$dateTimeObj->format("Ymd")."T".$dateTimeObj->format("His")."Z");

			if ($event->getDeleted()) {
				$this->printLine('METHOD', 'CANCEL');
				$this->printLine('STATUS', 'CANCELLED');
			} else {
				$this->printLine('SUMMARY',$event->getSummaryForFeed());
				if ($event->getURL()) {
					$this->printLine('DESCRIPTION',$event->getUrl() ." (From ". $wgServer."/index.php/".$pageTitle." )");
				} else {
					$this->printLine('DESCRIPTION',$wgServer."/index.php/".$pageTitle);
				}
			}
			$this->printLine('END','VEVENT');
			
		}
		
		echo 'END:VCALENDAR'."\r\n";

		
	}

	private function printLine($key,$value) {
		// TODO should br wrapping lines at a certain length and encoding newlines in the value.
		echo $key.':'.$value."\r\n";
	}
	
}

