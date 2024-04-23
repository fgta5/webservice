<?php namespace Fgta5\Webservice;

class Service {

	public static function PrepareService() : void {
	}

	public function Serve(string $requestedparameter) : string {
		$classname = get_class($this);
	
		ob_start();
		echo "<div><b>CLass</b> $classname</div>";
		echo "<p>";
		echo "<div>Requested Parameter: $requestedparameter</div>";
		echo "<div>Ini adalah default Service Serve function</div>";
		echo "<div>Silakan buat <tt>Serve(string \$requestedparameter) : string</tt> function di <tt>$classname</tt></div>";
		echo "</p>";

		$output = ob_get_contents(); 
		ob_end_clean();
		return $output;
	}

	protected static function getHttpPostHeaders() : array {
		$headers = ApiOAuth::createEmptyPostHeaders();	

		if (array_key_exists('HTTP_X_APPID', $_SERVER)) {
			$headers['X-APPID'] = $_SERVER['HTTP_X_APPID'];
		}

		if (array_key_exists('HTTP_X_TOKEN', $_SERVER)) {
			$headers['X-TOKEN'] = $_SERVER['HTTP_X_TOKEN'];
		} else {
			unset($headers['X-TOKEN']);
		}

		if (array_key_exists('HTTP_X_TIMESTAMP', $_SERVER)) {
			$headers['X-TIMESTAMP'] = $_SERVER['HTTP_X_TIMESTAMP'];
		}

		if (array_key_exists('HTTP_X_SESSID', $_SERVER)) {
			$headers['X-SESSID'] = $_SERVER['HTTP_X_SESSID'];
		} else {
			unset($headers['X-SESSID']);
		}

		if (array_key_exists('HTTP_X_REQID', $_SERVER)) {
			$headers['X-REQID'] = $_SERVER['HTTP_X_REQID'];
		}

		if (array_key_exists('HTTP_X_SIGN', $_SERVER)) {
			$headers['X-SIGN'] = $_SERVER['HTTP_X_SIGN'];
		}

		return $headers;
	}

	protected static function getHttpPostBody() : string {
		return file_get_contents('php://input');
	}

}