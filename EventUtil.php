<?php

define('EVENTS_DB_ACCESS_EVENTS',		0x01);
define('EVENTS_DB_ACCESS_EVENTGLOBAL',		0x02);
define('EVENTS_DB_ACCESS_ALL',			0xFF);

class ExtEventUtil
{

	private static $popupHTMLText = null;


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
                                        'DROP TABLE IF EXISTS events CASCADE;'.
                                        'DROP TABLE IF EXISTS eventglobal CASCADE;';
			
		}
		//
                //================= MYSQL
                //
                elseif ($type == "DatabaseMysql") {
			$query =        'DROP TABLE IF EXISTS events CASCADE;'.
                                        'DROP TABLE IF EXISTS eventglobal CASCADE;';
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

	/** Build and replies the HTML code which is corresponding
	* to the event popup box.
	* If $parser parameter is null, the event descriptions are
	* not recursively parsed.
	*/
	public static function computeEventPopupBoxHTML(&$parser = null)
	{
		global $wgEventExtensionRenderImportantEventBox;
                global $wgEventsCloseButtonIcon;
                global $wgUser;
                global $wgEventsOldAge, $wgEventsYoungAge;
		$skin = ($wgUser) ? $wgUser->getSkin() : null;
                if ($wgEventExtensionRenderImportantEventBox
                     && ($skin!=null)
                     && (method_exists($skin,'isEventsExtensionImportantEventEnabled'))
                     && ($skin->isEventsExtensionImportantEventEnabled())
                     && !isset(self::$popupHTMLText)) {
                        $dbr =& ExtEventUtil::getDatabase();

                        $oldAge = isset($wgEventsOldAge) ? -$wgEventsOldAge : -30;
                        $youngAge = isset($wgEventsYoungAge) ? $wgEventsYoungAge : 365;

                        $options = array( 'ORDER BY'=>'date ASC' );
                        $res = $dbr->select(
                                'events',
                                array('page_id','date','description'),
                                '( (date-current_date) <= '.$youngAge.' ) AND '.
                                '( (date-current_date) >= '.$oldAge.' ) ',
                                'Database::select',
                                $options);
                        if ($res) {
                                $eventlist = '';
                                while ($event = $dbr->fetchRow( $res )) {
                                        if (ExtEventUtil::isVisible($event)) {
                                                if ($event['visibility']=='important') {
                                                        $type1 = 'eventPopupImportantDate';
                                                        $type2 = 'eventPopupImportantDescription';
                                                }
                                                else {
                                                        $type1 = 'eventPopupDate';
                                                        $type2 = 'eventPopupDescription';
                                                }
                                                $date = ExtEventUtil::formatDateText($event['date'],true);
                                                $description = $event['description'];
						if ($parser!=null) {
							$description = $parser->recursiveTagParse($description);
						}
                                                $eventlist .=
                                                        "<span class=\"$type1\">".
                                                        $date.
                                                        "</span><span class=\"$type2\">".
                                                        $description.
                                                        "</span><br>";
                                        }
                                }
                                if ($eventlist) {
					self::$popupHTMLText =
                                        	"<div class=\"eventPopup\">".
                                     		/*"<div class=\"closeButton\">".
                                           	"[".
                                               	"X".
                                               	"]".
                                               	"</div>".*/
                                               	$eventlist.
                                               	"</div>";
                                }
                        }
		}
		return self::$popupHTMLText;
	}

	/** Replies the HTML code which is corresponding
	 * to the event popup box. This function does
	 * not compute this HTML code. It simply replies
	 * it. To compute the HTML code please invoke
	 * computeEventPopupBoxHTML() before invoking
	 * this function.
	 */
	public static function getEventPopupBoxHTML()
	{
		if (!isset(self::$popupHTMLText)) {
			$dbr =& ExtEventUtil::getDatabase(EVENTS_DB_ACCESS_EVENTGLOBAL);
			$res = $dbr->select('eventglobal', array('popuphtml'));
			if ($res && ($text = $dbr->fetchRow( $res ))) {
				self::$popupHTMLText = $text['popuphtml'];
			}
			else {
				self::$popupHTMLText = '';
			}
		}
		return self::$popupHTMLText;
	}

}

