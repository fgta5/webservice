<?php namespace Fgta5\Webservice;

class Router {

	const REQUEST_DEFAULT = 'page/home';

	
	private static array $__DATA = [];
	private static array $__FNINIT = [];
	private static string $_usedroute;
	private static string $_requestedparameter;
	private static array $_routedata;


	public static function getUsedRoute() : string {
		return self::$_usedroute;
	}

	public static function getRequestedParameter() : string {
		return self::$_requestedparameter;
	}

	public static function getRouteData() : array {
		return self::$_routedata;
	}

	public static function Add(string $routename, array $routedata) {
		self::$__DATA[$routename] = $routedata;
	}

	public static function PreloadDefaultReouter() : void {
		if (!array_key_exists('api', self::$__DATA)) {
			self::Add('api', ['classname'=>'Fgta5\Webservice\ApiService']);		// outputnya berupa json
		}

		if (!array_key_exists('asset', self::$__DATA)) {
			self::Add('asset', ['classname'=>'Fgta5\Webservice\AssetService']); // outputnya berupa image, javascript, css, dll
		}

		if (!array_key_exists('page', self::$__DATA)) {
			self::Add('page', ['classname'=>'Fgta5\Webservice\PageService']);	// outputnya berupa halaman html
		}

	}

	public static function Init(string $routename, callable $fn_init) : void {
		self::$__FNINIT[$routename] = $fn_init;
	}

	public static function Route(HttpRequest &$req) : string {
		$buff = ob_get_contents(); // ambil output buffer sebelumnya
		ob_end_clean();
		
		// Start Routing
		ob_start(); 

		self::PreloadDefaultReouter();


		$usedroute = $req->getUsedRoute();
		$requestedparameter = $req->getRequestedParameter();

		if (!array_key_exists($usedroute, self::$__DATA)) {
			$usedroute = 'page';
		}

		$routedata = self::$__DATA[$usedroute];
		$routedata['servicename'] = $usedroute; // tambahkan nama usedroute di routedata


		self::$_usedroute = $usedroute;
		self::$_requestedparameter = $requestedparameter;
		self::$_routedata = $routedata;


		if (array_key_exists($usedroute, self::$__FNINIT)) {
			$fn_init =  self::$__FNINIT[$usedroute];
			$fn_init();
		}


		$output = self::ProcessRequest($routedata, $requestedparameter);

		$prevoutput = ob_get_contents(); 
		ob_end_clean();
		ob_start();

		if (!empty($prevoutput)) {
				return $prevoutput;
		} else {
			return $output;
		}
		
	}

	public static function ProcessRequest(array $routedata, string $requestedparameter) : string {
		$requestedparameter = trim($requestedparameter, '/');

		$name = $routedata['servicename'];
		$ServiceClassName = $routedata['classname'];

		if (!class_exists($ServiceClassName)) {
			return "$ServiceClassName tidak ditemukan! userroute: $name";			
		} else {
			$ServiceClassName::PrepareService();

			$svc = new $ServiceClassName();
			$output = $svc->Serve($requestedparameter);
			return $output;
		}
	}
}