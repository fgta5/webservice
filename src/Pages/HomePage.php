<?php namespace Fgta5\Webservice\Pages;

use Fgta5\Webservice\Page;

class HomePage extends Page {


	public string $iniVariable;

	#[\Override]
	public function Load(array &$pagedata) : void {
		$this->iniVariable = "isi variable";
	}

}
