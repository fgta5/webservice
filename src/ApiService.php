<?php namespace Fgta5\Webservice;

class ApiService extends Service {


	#[\Override]
	public static function PrepareService() : void {
		try {
			parent::PrepareService();
			CurrentState::connectToMainDB();

			$db = CurrentState::getMainDbConnection();
			Session::setDbConnection($db);
			ApiOAuth::setDbConnection($db);

		} catch (\Exception $ex) {
			// Preparation error, hentikan proses
			$output = ob_get_contents();
			die(self::ApiError($ex->getCode(), $ex->getMessage(), $output));
		}
	}


	#[\Override]
	public function Serve(string $requestedparameter) : string {
		ob_start();

		try {
			$endpoint = $requestedparameter;

			$apiroutedata = static::class::getRequestedApiRouteData($endpoint);
			$postheaders = self::getHttpPostHeaders();
			$postbody = self::getHttpPostBody();

			// apabila dikonfigurasi dengan signed
			// cek apakah sign data sesuai
			$signed = array_key_exists('signed', $apiroutedata) ? $apiroutedata['signed'] : true;
			if ($signed!==false) {
				$this->VerifyRequest($endpoint, $postbody, $postheaders); 
			}

			$authorized = array_key_exists('authorized', $apiroutedata) ? $apiroutedata['authorized'] : true;
			if ($authorized!==false) {
				$this->VerifyAuthorization();
			}


			$ApiClassName = $apiroutedata['classname'];
			$ApiFunctionName = $apiroutedata['functionname'];

			if (!class_exists($ApiClassName)) {
				throw new \Exception ("Internal Error, $ApiClassName tidak ditemukan!");			
			} 

			if (!is_subclass_of($ApiClassName, 'Fgta5\Webservice\Api')) {
				throw new \Exception("Internal Error, Class '$ApiClassName' tidak inherit dari 'Fgta5\Webservice\Api'");
			}

			if (!method_exists($ApiClassName, $ApiFunctionName)) {
				throw new \Exception ("Internal Error, method $ApiFunctionName tidak ditemukan di class $ApiClassName!");			
			}
			
			$postbody = trim($postbody);
			$postbody = $postbody!=""?$postbody:"{}";
			$postdata = json_decode($postbody, true);
			if (json_last_error()!=0) {
				throw new \Exception("Body API Request salah");
			}

			$api = new $ApiClassName(); 
			$result = $api->$ApiFunctionName($requestedparameter, $postdata, $postheaders);

			$debugoutput = ob_get_contents();
			ob_end_clean();

			return json_encode([
				'code' => 0,
				'responseData' => $result,
				'debugoutput' => $debugoutput
			]);
		} catch (\Exception $ex) {
			$debugoutput = ob_get_contents();
			ob_end_clean();
			$code = $ex->getCode();
			$message = $ex->getMessage();
			if ($code==0 || $code==500) {
				$message = "API Internal Error: " . $ex->getMessage();
			} 
			return self::ApiError($code, $message, $debugoutput);
		}

	}


	protected function VerifyRequest(string $endpoint, string $postbody, array $postheaders) : void {
		
		
		try {
			if (!array_key_exists('X-APPID', $postheaders)) {
				throw new \Exception('Header appid error', 10015);
			}

			if (!array_key_exists('X-REQID', $postheaders)) {
				throw new \Exception('Header reqid error', 10015);
			}

			if (!array_key_exists('X-TIMESTAMP', $postheaders)) {
				throw new \Exception('Header timestamp error', 10015);
			}

			$appid = $postheaders['X-APPID'];
			$reqid = $postheaders['X-REQID'];
			$utc_timestamp = $postheaders['X-TIMESTAMP'];

			// Convert dulu dari UTC ke localtime
			$default_timezone = date_default_timezone_get();
			$req_datetime = new \DateTime($utc_timestamp);
			$local_timezone = new \DateTimeZone($default_timezone);
			$req_datetime->setTimezone($local_timezone);
			$timestamp = $req_datetime->format('Y-m-d H:i:s.u');
			
			// cek apakah timestamp valid
			ApiOAuth::verifyTimestamp($timestamp);

			// cek duplikasi appid & reqid
			ApiOAuth::verifyRequest($appid, $reqid);


			// cek validitas request dengan signature
			$publicKeyString = Configuration::getAppConfig('APP_PUBLIC_KEY');
			ApiOAuth::verifyRequestSignature($endpoint, $postbody, $postheaders, $publicKeyString);

			// insert request ke database
			ApiOAuth::recordRequest($appid, $reqid, $timestamp, $endpoint);

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	protected function VerifyAuthorization() : void {
		try {
			Session::begin_api_session();		
			if (Session::getCurrentLogin()==null) {
				throw new \Exception('Sesi ini tidak diauthorisasi', 10020); 
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	/*
	protected function VerifyAuthorization() : void {
		$token = $_SERVER['HTTP_X_TOKEN'];
		try {
			$sessidlength = (int)substr($token, 0, 3);
			if ($sessidlength<=0 || $sessidlength>36) {
				throw new \Exception('panjang token yang dikirim tidak sesuai', 10010);
			}

			// cek apakah session id ada di database, dan tokennya sesuai
			$sessid = substr($token, 3, $sessidlength);
			$sessdata = Session::get($sessid);
			if ($sessdata==null) {
				throw new \Exception('Sesi anda belum terdaftar', 10020);
			}

			// cek apakah sessdata sesuai
			if ($token!=$sessdata['session_token']) {
				throw new \Exception('Token tidak sesuai', 10020);
			}

			Session::start($sessid);
			if (!$_SESSION['islogin']) {
				throw new \Exception('Belum login', 10020);
			}

		} catch (\Exception $ex) {
			throw $ex;
		}
	}
	*/


	protected static function ApiError(int $code, string $message, string $debugoutput) : string {
		return json_encode([
			'code' => $code!=0 ? $code : 500,
			'message' => $message,
			'debugoutput' => $debugoutput
		]);		
	}

	protected static function getRequestedApiRouteData(string $requestedparameter) : array {
		//Debug::die($requestedparameter);
		try {

			$apiconfdir = Configuration::getAppConfig('API_DATA_DIR');
			$apiconffile = implode('/', [$apiconfdir, "$requestedparameter.php"]);
			if (!is_file($apiconffile)) {
				throw new \Exception("$requestedparameter tidak ditemukan", 404);
			}

			require_once $apiconffile;

			if (!isset($API_CONFIG)) {
				echo "array \$API_CONFIG belum didefinisikan di file '$apiconffile'. \$API_CONFIG=>['classname'=>'','functionname'=>'d']";
				throw new \Exception("\$API_CONFIG belum didefinisikan");
			}

			if (!array_key_exists('classname', $API_CONFIG)) {
				echo "cek file '$apiconffile'";
				throw new \Exception("key 'classname' belum didefinisikan di array \$API_CONFIG");
			}

			if (!array_key_exists('functionname', $API_CONFIG)) {
				echo "cek file '$apiconffile'";
				throw new \Exception("key 'functionname' belum didefinisikan di array \$API_CONFIG");
			}

			return $API_CONFIG;
		} catch (\Exception $ex) {
			throw $ex;
		}

		
		
	}


}


/*
// Contoh sign dengan PHP
//$privateKeyString = Configuration::getAppConfig('APP_PRIVATE_KEY');
//$postheaders = ApiAuthorization::createSignedPostHeaders($requestedparameter, $postbody, $privateKeyString);
*/