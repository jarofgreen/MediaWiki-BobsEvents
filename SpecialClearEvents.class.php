<?php

require_once('SpecialPage.php');
require_once(dirname(__FILE__).'/EventUtil.php');

class ExtSpecialClearEvents extends SpecialPage
{

	function __construct()
	{
		parent::__construct(
			'SpecialClearEvents', // name
			'events_clear', // mandatory user right
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

		$skin = $wgUser->getSkin();

		$this->setHeaders();

		ExtEventUtil::clearDatabase();

		$wgOut->redirect($skin->makeSpecialUrl('SpecialPages'));
	}

}

?>
