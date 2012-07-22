<?php

require_once(dirname(__FILE__).'/EventUtil.php');

class ExtEvents
{

	var $events = array();
	private static $popupBoxOutput = false;

	function __construct()
	{
		if ( function_exists( 'wfLoadExtensionMessages' ) ) {
			wfLoadExtensionMessages( 'Events' );
		}
	}

	private function forceTempCache($parser)
        {
                # Force this page to be rendered after a delay.
                # Pass the number of seconds after which the
                # page must be rendered again.
                # 21600 seconds = 6 hours
                $parser->getOutput()->setCacheTime(time()+21600); // old version style
                #$parser->getOutput()->updateCacheExpiry(21600); // new version style

        }

	public function clearState($parser)
	{
		return true;
	}

	/**
	 * Get the marker regex. Cached.
	 */
	protected function getMarkerRegex( $parser )
	{
		if ( isset( $parser->pf_markerRegex ) ) {
			return $parser->pf_markerRegex;
		}

		wfProfileIn( __METHOD__ );

		$prefix = preg_quote( $parser->uniqPrefix(), '/' );

		// The first line represents Parser from release 1.12 forward.
		// subsequent lines are hacks to accomodate old Mediawiki versions.
		if ( defined('Parser::MARKER_SUFFIX') )
			$suffix = preg_quote( Parser::MARKER_SUFFIX, '/' );
		elseif ( isset($parser->mMarkerSuffix) )
			$suffix = preg_quote( $parser->mMarkerSuffix, '/' );
		elseif ( defined('MW_PARSER_VERSION') && 
				strcmp( MW_PARSER_VERSION, '1.6.1' ) > 0 )
			$suffix = "QINU\x07";
		else $suffix = 'QINU';
		
		$parser->pf_markerRegex = '/' .$prefix. '(?:(?!' .$suffix. ').)*' . $suffix . '/us';

		wfProfileOut( __METHOD__ );
		return $parser->pf_markerRegex;
	}

	// Removes unique markers from passed parameters, used by string functions.
	private function killMarkers ( $parser, $text )
	{
		return preg_replace( $this->getMarkerRegex( $parser ), '' , $text );
	}

	/** Invoked when an article should be saved.
	 *
	 * @return success state.
	 */
	public function saveArticlePre()
	{
		$this->clearEventBuffer();
		return true;
	}

	/** Invoked when an article is just saved.
	 *
	 * @return success state.
	 */
	public function saveArticlePost( &$article, &$user, $text, $summary,
 $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId, &$redirect=null )
	{
		global $wgParser;
		// Retreive page informations
		$pageId = $article->getID();

		// Rebuild the $events array (in case we're on a <calendar> page)
                $this->clearEventBuffer();
		
		//    avoid <nowiki> parsing
		$text1 = Parser::extractTagsAndParams(array('nowiki'), $text, $matches1 );
		//    parse <events>
		Parser::extractTagsAndParams(array('event'), $text1, $matches2 );
		foreach( $matches2 as $marker => $data ) {
			list( $element, $content, $params, $tag ) = $data;
			$event = ExtEventUtil::parseText($content);
			// put back <nowiki> tags
			foreach($matches1 as $nowikikey => $nowikidesc) {
				$event->setDescription(str_replace(
					$nowikikey,
					$nowikidesc[3],
					$event->getDescription()));
			}
			$this->events[] = $event;
		}

		$dbr =& ExtEventUtil::getDatabase(EVENTS_DB_ACCESS_ALL);

		$dbr->delete(
			'events',
			array( 'page_id' => $pageId ));

		// Add events in the database
	        foreach ($this->events as $event) {
			$dbevent = array(
				'page_id' => $pageId,
				'summary' => $event->getSummary(),
				'start_at' => date("Y-m-d H:i:s",$event->getStartTimeStamp()),
				'end_at' => date("Y-m-d H:i:s",$event->getEndTimeStamp()),
			);
                	$dbr->insert(
                        	'events',
                        	$dbevent
                	);
        	}

        	$this->clearEventBuffer();

		return true;
	}

	

	/** Clear the buffer of events.
	 */
	private function clearEventBuffer()
	{
		$this->events = array();
	}

	/** Replies the CSS classname which may be usefull for the event.
	 */
	private function getAdditionalStyle($event)
	{
		return "publicEvent";
	}

}

