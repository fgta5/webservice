<?php namespace Fgta5\Webservice\Pages;

use Fgta5\Webservice\Page;
use Fgta5\Webservice\Session;

class LogoutPage extends Page {

	#[\Override]
	public function Load(array &$pagedata) : void {
		Session::clear_token();
	}




}