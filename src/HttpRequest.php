<?php namespace Fgta5\Webservice;

class HttpRequest {

	const DEFAULT_ROUTE = 'page';
	const DEFAULT_PAGE = 'home';
	const DEFFULT_ASSETROUTE = 'asset';
	const BROWSER_AUTO_REQ = ['favicon.ico'];

	private string $_usedroute = self::DEFAULT_ROUTE;
	private string $_requestedparameter = self::DEFAULT_PAGE;

	function __construct(string $urlrequeststring) {
		$reqparts = explode('/', $urlrequeststring);
		if (in_array($urlrequeststring, self::BROWSER_AUTO_REQ)) {
			// special request, automatically from browser
			$this->_usedroute = self::DEFFULT_ASSETROUTE;
			$this->_requestedparameter =  $urlrequeststring;	
		} else if (count($reqparts)>=2) {
			// normal request
			$this->_usedroute = array_shift($reqparts); // ambil elemen pertama sebagai route, dan remove dari reqparts 
			$this->_requestedparameter = implode('/', $reqparts);
		} else if (count($reqparts)==1) {
			// special request
			$this->_requestedparameter = $reqparts[0];
		}
	}

	public function getUsedRoute() : string {
		return $this->_usedroute;
	}

	public function getRequestedParameter() : string {
		return $this->_requestedparameter;
	}

}