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
				array('page_id','start_at','end_at','summary','deleted'), 
				'(end_at > current_date)',
				'Database::select',
				$options);			 


		$wgOut->disable();
		//header( 'Content-type: text/calendar; charset='.$wgInputEncoding );

		$this->printLine('BEGIN','VCALENDAR');
		$this->printLine('VERSION','2.0');
		$this->printLine('PRODID','-//JarOfGreen//NONSGML MediaWiki BobEvents//EN'); 
		
		$pageTitleBuffer = array();
		
		while ($eventData = $dbr->fetchRow( $res )) {
			
			$event = new ExtEventObject();
			$event->setFromDBRow($eventData);
			
			$pageTitle = isset($pageTitleBuffer[$event->getPageId()]) ? $pageTitleBuffer[$event->getPageId()] : null;
			if (!$pageTitle) {
				$pageTitle = $pageTitleBuffer[$event->getPageId()] = Title::nameOf($event->getPageId());
			}
			
			$this->printLine('BEGIN','VEVENT');
			$this->printLine('UID','p'.$event->getPageId().'-s'.$event->getStartTimeStamp().'@'.$serverNameOnly);
			$this->printLine('DTSTART',str_replace("-", "", $event->getStartTimeStamp()).'T000000Z');
			$this->printLine('DTEND',str_replace("-", "", $event->getEndTimeStamp()).'T230000Z');
			if ($event->getDeleted()) {
				$this->printLine('METHOD', 'CANCEL');
				$this->printLine('STATUS', 'CANCELLED');
			} else {
				$this->printLine('SUMMARY',$event->getSummary());
				$this->printLine('DESCRIPTION',$wgServer."/index.php/".$pageTitle);
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

