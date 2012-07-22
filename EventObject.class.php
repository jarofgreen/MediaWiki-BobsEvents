<?php


class ExtEventObject {
	
	private $page_id;
	private $startTimeStamp;
	private $endTimeStamp;
	private $summary;
	private $deleted;
	
	function setStartTimeStamp($start) {  $this->startTimeStamp = $start; }
	function getStartTimeStamp() { return $this->startTimeStamp; }
	
	function setEndTimeStamp($end) {  $this->endTimeStamp = $end; }
	function getEndTimeStamp() { return $this->endTimeStamp ? $this->endTimeStamp : $this->startTimeStamp+60*60; }
	
	function setSummary($summary) {  $this->summary = $summary; }
	function getSummary() { return $this->summary; }
	
	function getPageId() { return $this->page_id; }
	function getDeleted() { return $this->deleted; }
	
	function parseText($text) {
		$data = parse_ini_string($text);
		
		if (isset($data['Summary'])) {
			$this->summary = $data['Summary'];
		}
		
		if (isset($data['Start'])) {
			$this->startTimeStamp = strtotime($data['Start']);
		}
		
		if (isset($data['End'])) {
			$this->endTimeStamp = strtotime($data['End']);
		}
		
	}
	
	function setFromDBRow($data) {
		$this->page_id = $data['page_id'];
		$this->deleted = isset($data['deleted']) ? $data['deleted'] : 0;
		$this->summary = $data['summary'];
		$this->startTimeStamp = strtotime($data['start_at']);
		$this->endTimeStamp = strtotime($data['end_at']);
	}
	
}


