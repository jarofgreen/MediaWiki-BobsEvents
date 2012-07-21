<?php


class ExtEventObject {
	
	private $startTimeStamp;
	private $description;
	
	function setStartTimeStamp($start) {  $this->startTimeStamp = $start; }
	function getStartTimeStamp() { return $this->startTimeStamp; }
	
	function setDescription($description) {  $this->description = $description; }
	function getDescription() { return $this->description; }
	
	function parseText($text) {
		$data = parse_ini_string($text);
		
		if (isset($data['Description'])) {
			$this->description = $data['Description'];
		}
		
		if (isset($data['Start'])) {
			$this->startTimeStamp = strtotime($data['Start']);
		}
		
	}
}


