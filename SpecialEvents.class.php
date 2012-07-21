<?php

require_once('SpecialPage.php');
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
		global $wgOut, $wgEventsOldAge, $wgEventsYoungAge, $wgUser;

		// Parse special page arguments
		$args = array();
		parse_str($par, $args);

		$this->setHeaders();

		$dbr =& ExtEventUtil::getDatabase();

		// Build SQL query options
		$options = array( 'ORDER BY'=>'date ASC' );

		if ($args['limit']) {
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
                	array('page_id','date','description','visibility'), 
	                '(date-current_date <= '.$youngAge.' ) and (date-current_date >= '.$oldAge.')',
        	        'Database::select',
                	$options);
        	if (!$res) {
			$wgOut->addHTML(ExtEventUtil::errorMsg('specialevents_empty_list'));
			return;
		}

		$out = '';

		// Generate HTML list
		$pageTitleBuffer = array();

		while ($event = $dbr->fetchRow( $res )) {

			if (ExtEventUtil::isVisible($event)) {
				$pageTitle = $pageTitleBuffer[$event['page_id']];
				if (!$pageTitle) {
					 $pageTitle = $pageTitleBuffer[$event['page_id']] =
						Title::nameOf($event['page_id']);
				}

				$date = ExtEventUtil::formatDateText($event['date'],true);
				$text = preg_replace("/[\n\r\f]+/s", '<br>', $event['description']);
				$pagelink = "[[$pageTitle|&rarr; $pageTitle]]";

				if ($out) $out .= "|-\n";

				$out .= "| width=5% | $date\n";
				$out .= "| $text\n";
				$out .= "| width=20% | $pagelink\n";
			}

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

?>
