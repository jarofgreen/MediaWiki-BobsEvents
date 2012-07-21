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
		global $wgOut, $wgUser;

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
                	array('page_id','start_at','end_at','summary'), 
	                '(end_at > current_date)',
        	        'Database::select',
                	$options);
        	if (!$res) {
			$wgOut->addHTML(ExtEventUtil::errorMsg('specialevents_empty_list'));
			return;
		}

		$out = '';

		// Generate HTML list
		$pageTitleBuffer = array();

		while ($eventData = $dbr->fetchRow( $res )) {

			$event = new ExtEventObject();
			$event->setFromDBRow($eventData);
			
			$pageTitle = isset($pageTitleBuffer[$event->getPageId()]) ? $pageTitleBuffer[$event->getPageId()] : null;
			if (!$pageTitle) {
				$pageTitle = $pageTitleBuffer[$event->getPageId()] = Title::nameOf($event->getPageId());
			}

			$date = ExtEventUtil::formatTimestamp($event->getStartTimeStamp(),true);
			$text = preg_replace("/[\n\r\f]+/s", '<br>', $event->getSummary());
			$pagelink = "[[$pageTitle|&rarr; $pageTitle]]";

			if ($out) $out .= "|-\n";

			$out .= "| width=5% | ".$date."\n";
			$out .= "| $text\n";
			$out .= "| width=20% | $pagelink\n";
	
		}

		if ($out) {
			$out = "{| class=\"frametable\"\n".
				"! ".wfMsgExt(
					'specialevents_header_date',
		                        array( 'escape', 'parsemag', 'content' ))."\n".
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

