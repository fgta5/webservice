<?php namespace Fgta5\Webservice\Tester;

use Fgta5\Webservice\Configuration;
use Fgta5\Webservice\ApiOAuth;

class apiclient {
	public static function call_login(string $username, string $password) : ?array {
		$baseurl = "http://localhost";
		$endpoint = 'login';
		$url = implode('/', [$baseurl, 'api', $endpoint]);
		$privateKeyString = Configuration::getAppConfig('APP_PRIVATE_KEY');
		$headers = [
			'Content-Type' => 'application/json',
			'X-APPID' => Configuration::getAppConfig('APP_ID'),
			'X-REQID' => uniqid(),
			'X-TIMESTAMP' => (new \DateTime("now", new \DateTimeZone("UTC")))->format("Y-m-d\TH:i:s.u\Z"),
			'X-SIGN' => ''
		];

		try {
			self::printHeader($url);

			$data = [
				'requestData' => [
					'username' => $username,
					'password' => $password
				]
			];
			$postbody = json_encode($data);
			$dataToSign = ApiOAuth::getSignedDataToVerify($endpoint, $postbody, $headers);

			$sign = ApiOAuth::createSignature($dataToSign,  $privateKeyString);
			$headers['X-SIGN'] = $sign;
			$http_header = self::getHttpHeader($headers);


			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postbody);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);

			$result = curl_exec($ch);
			curl_close($ch);


			self::printResult($result);

			$ret = json_decode($result, true);
			if (json_last_error()!=0) {
				throw new \Exception(json_last_error_msg());
			}

			return $ret;
		} catch (\Exception $ex) {
			self::printError($ex);
			return null;
		} finally {
			self::printFooter();
		}

	}


	public static function call_auth_signed_api(string $token) : void {
		$baseurl = "http://localhost";

		$data = self::getData();
		$endpoint = 'myfirst-api-auth-signed';
		//$endpoint = 'myfirst-api-auth-unsigned';
		//$endpoint = 'myfirst-api-noauth-signed';
		//$endpoint = 'myfirst-api-noauth-unsigned';

		$privateKeyString = Configuration::getAppConfig('APP_PRIVATE_KEY');

		$headers = [
			'Content-Type' => 'application/json',
			'X-APPID' => Configuration::getAppConfig('APP_ID'),
			'X-REQID' => uniqid(),
			'X-TIMESTAMP' => (new \DateTime("now", new \DateTimeZone("UTC")))->format("Y-m-d\TH:i:s.u\Z"),
			'X-TOKEN' => $token,
			'X-SIGN' => ''
		];

		try {

			$url = implode('/', [$baseurl, 'api', $endpoint]);
			self::printHeader($url);

			$postbody = json_encode($data);
			$dataToSign = ApiOAuth::getSignedDataToVerify($endpoint, $postbody, $headers);

			$sign = ApiOAuth::createSignature($dataToSign,  $privateKeyString);
			$headers['X-SIGN'] = $sign;
			$http_header = self::getHttpHeader($headers);


			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postbody);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);

			$result = curl_exec($ch);
			curl_close($ch);


			self::printResult($result);


		} catch (\Exception $ex) {
			self::printError($ex);
		} finally {
			self::printFooter();
		}

	}


	public static function getData() : array {
		return [
			'nama' => 'Agung Nugroho',
			'alamat' => 'Taman Royal'
		];
	}

	public static function printResult($result) : void {
		echo "\r\n";
		echo $result;
		echo "\r\n";
	}

	public static function getHttpHeader($headers) : array {
		$h = [];
		foreach ($headers as $name=>$value) {
			$h[] = "$name: $value";
		}
		return $h;
	}

	public static function printHeader(string $url) : void {
		echo "API Clinet Test\r\n";
		echo "===============\r\n";
		echo $url;
		echo "\r\n\r\n";
	}

	public static function printFooter() : void {
		echo "\r\n\r\n";
	}

	public static function printError($ex) : void {
		echo "Error\r\n";
		echo $ex->getMessage();
	} 



}


/*
POST:api/api/myfirst-api-auth-signed:TFIWEB:66134db1e4ddc:2024-04-08T01:51:45.937544Z:-:{"nama":"Agung Nugroho","alamat":"Taman Royal"}
POST:api/myfirst-api-auth-signed:TFIWEB:66134db1e4ddc:2024-04-08T01:51:45.937544Z:-:{"nama":"Agung Nugroho","alamat":"Taman Royal"}

*/