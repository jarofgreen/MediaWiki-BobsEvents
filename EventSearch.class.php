<?php


/** Common class used for interperting params and building search SQL **/
class ExtEventSearch {
	
	
	public static $DEFAULT_LIMIT = 400;
	
	
	private $limit;

	function __construct() {
		$this->limit = self::$DEFAULT_LIMIT;
	}
	
	function parseArgsString($par) {
		$args = array();
		parse_str($par, $args);
		
		if (isset($args['limit']) && intval($args['limit']) > 0) {
			$this->limit = intval($args['limit']);
		}
	}
	
	
	
	function getDataBaseResults($db) {
		$options = array( 'ORDER BY'=>'start_at ASC', 'LIMIT' =>$this->limit );
		return $db->select(
				'events', 
				array('page_id','start_at','end_at','summary','url'), 
				'(end_at > '.time().' AND deleted=0)',
				'Database::select',
				$options);
	}
	
	function getArgsString() {
		$out = array();
		
		if ($this->limit != self::$DEFAULT_LIMIT) $out['limit'] =  $this->limit;
				
		return http_build_query($out);
	}
	
}

