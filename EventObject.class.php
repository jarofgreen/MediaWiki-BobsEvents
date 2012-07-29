<?php


class ExtEventObject {
	
	private $page_id;
	private $page_title;
	private $startTimeStamp;
	private $endTimeStamp;
	private $summary;
	private $deleted;
	private $url;
	
	function setStartTimeStamp($start) {  $this->startTimeStamp = $start; }
	function getStartTimeStamp() { return $this->startTimeStamp; }
	
	function setEndTimeStamp($end) {  $this->endTimeStamp = $end; }
	function getEndTimeStamp() { return $this->endTimeStamp ? $this->endTimeStamp : $this->startTimeStamp+60*60; }
	
	function setSummary($summary) {  $this->summary = $summary; }
	function getSummary() { return $this->summary; }
	
	function getSummaryForFeed() { return $this->page_title." ".$this->summary; }
	
	function getURL() { return $this->url; }
	
	function getPageId() { return $this->page_id; }
	function getDeleted() { return $this->deleted; }
	
	function parseText($text, $pageTitle=null) {
		global $wfEventsDefaultTimeZone;
		$data = parse_ini_string($text);
		
		$timeZone = new DateTimeZone($wfEventsDefaultTimeZone);
		
		if (isset($data['Summary'])) {
			$this->summary = $data['Summary'];
		}
		$this->page_title = $pageTitle;
		
		if (isset($data['URL']) && filter_var($data['URL'], FILTER_VALIDATE_URL)) {
			$this->url = $data['URL'];
		}
		
		if (isset($data['Start'])) {
			try {
				$obj = new DateTime($data['Start'],$timeZone);
				$this->startTimeStamp = $obj->getTimestamp();
			} catch (Exception $e) {
				// error parsing. Ignore
			}
		}
		
		if (isset($data['End'])) {
			try {
				$obj = new DateTime($data['End'],$timeZone);
				$this->endTimeStamp = $obj->getTimestamp();
			} catch (Exception $e) {
				// error parsing. Ignore
			}
		}
		
	}
	
	function setFromDBRow($data, $pageTitle=null) {
		$this->page_id = $data['page_id'];
		$this->page_title = $pageTitle;
		$this->deleted = isset($data['deleted']) ? $data['deleted'] : 0;
		$this->summary = $data['summary'];
		$this->startTimeStamp = $data['start_at'];
		$this->endTimeStamp = $data['end_at'];
		$this->url = $data['url'];
	}

	function isValid() {
		return ($this->startTimeStamp > 0);
	}
}


