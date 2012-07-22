<?php

define('EVENTS_DB_ACCESS_EVENTS',		0x01);
define('EVENTS_DB_ACCESS_EVENTGLOBAL',		0x02);
define('EVENTS_DB_ACCESS_ALL',			0xFF);

class ExtEventUtil
{



	/** Generates Error message from html text */
        public static function error($message, $title='')
        {
                if ($title)
                        $title = " title=\"".addslashes($title)."\"";
                return "<strong class=\"error\"$title>$message</strong>"; 
        }

	/** Generates Error message for internationalized message id */
        public static function errorMsg($messageId, $solutionId, $method='')
        {
                $solution = '';
                if ($solutionId) {
                        $solution = wfMsgExt( 
                                $solutionId,
                                 array( 'escape', 'parsemag', 'content' ),
                                $method);
                        if ($solution==$solutionId)
                                $solution = '';
                }
                $msg = $this->error(wfMsgExt( $messageId,
                         array( 'escape', 'parsemag', 'content' )),
                        $solution);
                return $msg;
        }

	/** Replies a Wiki url */
	public static function wikiUrl($url, $text='', $title='')
        {
                if (!$text) {
                        if (!$title) $text = $title;
                        else $text = $url;
                }
                if (!$title) $title = $url;
                return "<a href=\"".trim($url)."\" title=\"".
                           addslashes(strip_tags(trim($title))).
                           "\" class=\"external text\" ".
                           "rel=\"nofollow\">".trim($text)."</a>";

        }

	/** Delete all database tables related to this extension.
	 */
	public static function clearDatabase()
	{
		$dbr =& wfGetDB( DB_SLAVE );
		$type = get_class($dbr);
                $query = null;
		//
                //================= POSTGRESQL
                //
                if ($type == "DatabasePostgres") {
			$query =        'DROP TYPE IF EXISTS eventVisibility CASCADE;'.
                                        'DROP TABLE IF EXISTS events CASCADE;';
			
		}
		//
                //================= MYSQL
                //
                elseif ($type == "DatabaseMysql") {
			$query =        'DROP TABLE IF EXISTS events CASCADE;';
		}
		//
                //=================== OTHERS DB
                //
		if ($query) {
                        $dbr->query($query);
                }
                return $dbr;
	}

	
	/** Replies a connection to an initialized database.
	 */
	public static function getDatabase($access = EVENTS_DB_ACCESS_EVENTS)
	{
		$dbr =& wfGetDB( DB_SLAVE );
		return $dbr;
	}

	/** Format the event date.
	 */
	public static function formatDate($event,$shortFormat=false)
	{
		return ExtEventUtil::formatTimestamp($event['timestamp'],$shortFormat);
	}

	/** Format a date given in textual form, ie.
	 * YYYY-MM-DD.
	 * @return the formated string or $text itself.
	 */
	public static function formatDateText($text,$shortFormat=false)
	{
		if (preg_match("/^\\s*([0-9]+)\\-?([0-9]+)\\-?([0-9]+)\\s*\$/s", $text, $matches)) {
			 $timestamp = mktime(
                                        0, // hour
                                        0, // min
                                        0, // second
                                        intval($matches[2]), // month
                                        intval($matches[3]), // day
                                        intval($matches[1])); // year
			return ExtEventUtil::formatTimestamp($timestamp,$shortFormat);
		}
		return $text;
	}

	/** Format a date from a timestamp.
	 */
	public static function formatTimestamp($timestamp=-1, $shortFormat=false)
	{
		global $wgLang;
		if ($timestamp<0) $timestamp = time();
		if ($shortFormat) {
			return date('Y-m-d', $timestamp);
		}
		else {
	                $dt = date('Ymd', $timestamp);
        	        $wd = intval(date('w', $timestamp))+1;
                	$txt = $wgLang->date($dt, true);
	                $wd = $wgLang->getWeekdayName($wd);
        	        return "$wd $txt";
		}
	}

	/** Replies if the given event description is visible or not.
	 * But now all events are public!
	 */
	public static function isVisible($eventDescription)
	{
		return true;
	}

	/** Parse the given text to extract events.
	 */
	public static function parseText($text)
	{

		
		$event = new ExtEventObject();
		$event->parseText($text);
		return $event;
		
	}

}

