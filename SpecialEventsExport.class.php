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
		global $wgOut, $wgEventsOldAge, $wgEventsYoungAge, $wgUser, $wgServer;

		// Parse special page arguments
		$args = array();
		parse_str($par, $args);

		$this->setHeaders();

		$dbr =& ExtEventUtil::getDatabase();

		// Build SQL query options
		$options = array( 'ORDER BY'=>'date ASC' );

		if (isset($args['limit']) && $args['limit']) {
					$options['LIMIT'] = $args['limit'];
		}

		$oldAge = isset($wgEventsOldAge) ? -$wgEventsOldAge : -30;
		if (isset($args['old'])) {
			$oldAge = intval($args['old']);
		}
		$youngAge = isset($wgEventsYoungAge) ? $wgEventsYoungAge : 365;
		if (isset($args['young'])) {
			$youngAge = intval($argv['young']);
		}

		// Run the SQL.
		$res = $dbr->select(
				'events', 
				array('page_id','date','description'), 
				'(date-current_date <= '.$youngAge.' ) and (date-current_date >= '.$oldAge.')',
				'Database::select',
				$options);			 


		$wgOut->disable();
		//header( 'Content-type: text/calendar; charset='.$wgInputEncoding );

		$this->printLine('BEGIN','VCALENDAR');
		$this->printLine('VERSION','2.0');
		$this->printLine('PRODID','-//JarOfGreen//NONSGML MediaWiki BobEvents//EN'); 
		
		$pageTitleBuffer = array();
		
		while ($event = $dbr->fetchRow( $res )) {
			
			$pageTitle = isset($pageTitleBuffer[$event['page_id']]) ? $pageTitleBuffer[$event['page_id']] : null;
			if (!$pageTitle) {
				$pageTitle = $pageTitleBuffer[$event['page_id']] = Title::nameOf($event['page_id']);
			}
			
			$this->printLine('BEGIN','VEVENT');
			$this->printLine('DTSTART',str_replace("-", "", $event['date']).'T000000Z');
			$this->printLine('DTEND',str_replace("-", "", $event['date']).'T230000Z');
			$this->printLine('DESCRIPTION',$event['description']. " ".$wgServer."/index.php/".$pageTitle);
			$this->printLine('END','VEVENT');
			
		}
		
		echo 'END:VCALENDAR'."\r\n";

		
	}

	private function printLine($key,$value) {
		// TODO should br wrapping lines at a certain length and encoding newlines in the value.
		echo $key.':'.$value."\r\n";
	}
	
}

