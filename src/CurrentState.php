<?php namespace Fgta5\Webservice;

class CurrentState {

	private static HttpRequest $__httprequest;
	private static \PDO $__mainDbConnection;



	public static function Begin(string $urlrequeststring) : void {
		self::$__httprequest = new HttpRequest($urlrequeststring);
		ob_start();
	}


	public static function End($output) : void {
		ob_end_clean();
		echo $output;
	}


	public static function getHttpRequest() : HttpRequest {
		return self::$__httprequest;
	}

	public static function isLogin() : bool {
		$currentUser = Session::getCurrentLogin();
		if ($currentUser==null) {
			return false;
		} else {
			return true;
		}
	}


	public static function getMainDbConnection() : \PDO {
		return self::$__mainDbConnection;
	}

	public static function connectToMainDB() : void {
		try {
			$dbConfig = Configuration::getActiveConfig('MAINDB');
			self::$__mainDbConnection = new \PDO(
				$dbConfig['DSN'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['param']
			);
		} catch (\Exception $ex) {
			throw $ex;
		}
	}


}

