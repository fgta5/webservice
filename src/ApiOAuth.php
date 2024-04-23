<?php namespace Fgta5\Webservice;

class ApiOAuth {
	const APIREQUEST_TABLE = 'fgt_apirequest';
	const MAXLIFETIME = 2*60;

	private static \PDO $__dbConn;

	public static function setDbConnection(\PDO $dbConn) : void {
		self::$__dbConn = $dbConn;
	}

	public static function createEmptyPostHeaders() : array {
		return [
			'X-APPID' => '',
			'X-REQID' => '',
			'X-TIMESTAMP' => '',
			'X-TOKEN' => '',
			'X-SIGN' => ''
		];
	}

	public static function createSignature(string $dataToSign, string $privateKeyString) : string {
		$privateKey = openssl_pkey_get_private($privateKeyString);
		openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
		unset($privateKey);
		$signatureHex = bin2hex($signature);
		return $signatureHex;
	}

	public static function verifyTimestamp(string $timestamp) : void {
		try {
			$currentTime = time();
			$oldestTimestampAllowed = $currentTime - self::MAXLIFETIME;
			$requestTimestamp = strtotime($timestamp);
			if ($requestTimestamp<$oldestTimestampAllowed) {
				throw new \Exception('Timestamp tidak valid', 12002);
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function verifyRequest(string $appid, string $reqid) : void {
		$db = self::$__dbConn;

		try {
			$data = [
				'apirequest_id' => $reqid,
				'app_id' => $appid
			];

			$keys = ['apirequest_id', 'app_id'];
			$cmd = SqlCommand::create(self::APIREQUEST_TABLE, $data,  $keys);
			$query = $cmd->generateSQL_Select();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);
			$row = $stmt->fetch();
			if ($row) {
				throw new \Exception('Request id tidak valid / duplikasi', 12001);
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function verifyRequestSignature(string $endpoint, string $postbody, array $headers, string $publicKeyString) : bool {
		try {
			if (!array_key_exists('X-SIGN', $headers)) {
				throw new \Exception('Signature tidak valid', 11001);
			}

			$signature = $headers['X-SIGN'];
			$signedData = self::getSignedDataToVerify($endpoint, $postbody, $headers);
			$isValid = self::verifyPostMessageSignature($signedData, $signature, $publicKeyString);
			if (!$isValid) {
				throw new \Exception('Signature tidak valid', 12000);
			} 
			return true;
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function getSignedDataToVerify(string $endpoint, string $postbody, array $headers) : string {
		//POST:${endpoint}:${appid}:${sessionId}:${requestId}:${timestamp}:${token}:${postDataJson}

		$data = ['POST', "api/$endpoint"];

		if (array_key_exists('X-APPID', $headers)) {
			$data[] = $headers['X-APPID'];
		}

		if (array_key_exists('X-REQID', $headers)) {
			$data[] = $headers['X-REQID'];
		}

		if (array_key_exists('X-TIMESTAMP', $headers)) {
			$data[] = $headers['X-TIMESTAMP'];
		}

		if (array_key_exists('X-TOKEN', $headers)) {
			$data[] = $headers['X-TOKEN'];
		}

		$data[] = $postbody;

		return implode(':', $data);
	}

	private static function verifyPostMessageSignature(string $signedData, string $signature, string $publicKeyString) : bool {
		try {
			$signatureBinary = hex2bin($signature);
			$publicKey = openssl_pkey_get_public($publicKeyString);
			if ($publicKey === false) {
				throw new \Exception("Gagal mengambil public key", 500);
			}
			$isSignatureValid = openssl_verify($signedData, $signatureBinary, $publicKey, OPENSSL_ALGO_SHA256);
			return ($isSignatureValid === 1);	
		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	public static function recordRequest(string $appid, string $reqid, string $apirequest_timestamp, string $endpoint) : void {
		self::clearOldRequest();
		
		$db = self::$__dbConn;
		try {
			
			$currentTime = time();
			$expireTime = $currentTime + self::MAXLIFETIME;
			$apirequest_timeexpired = date_format(date_timestamp_set(new \DateTime(), $expireTime), 'Y-m-d H:i:s');

			$data = [
				'apirequest_id' => $reqid,
				'apirequest_timestamp' => $apirequest_timestamp,
				'apirequest_timeexpired' => $apirequest_timeexpired,
				'apirequest_endpoint' => $endpoint,
				'app_id' => $appid,
			];
			
			$cmd = SqlCommand::create(self::APIREQUEST_TABLE, $data);
			$query = $cmd->generateSQL_Insert();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']); 
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function clearOldRequest() : void {
		$db = self::$__dbConn;

		try {
			$now = date_format(date_timestamp_set(new \DateTime(), time()), 'Y-m-d H:i:s');
			$data = [
				'apirequest_timeexpired' => ['<', $now]
			];
			$keys = ['apirequest_timeexpired'];
			$cmd = SqlCommand::create(self::APIREQUEST_TABLE, $data, $keys);
			$query = $cmd->generateSQL_Delete();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);
		}  catch (\Exception $ex) {
			throw $ex;
		}
	}

}


/*
	public static function createSignedPostHeaders($requestedparameter, $postbody, $privateKeyString) : array {
		try {
			$headers = self::createEmptyPostHeaders();
			$dataToSign = self::getSignedDataToVerify($requestedparameter, $postbody, $headers);
			$signature = self::createSignature($dataToSign,  $privateKeyString);
			$headers['X-SIGN'] = $signature;
			return $headers;
		} catch (\Exception $ex) {
			throw $ex;
		}
	}
*/


/*


CREATE TABLE fgt_apirequest (
	apirequest_id varchar(100) NOT NULL,
	apirequest_timestamp DATETIME NOT NULL,
	apirequest_endpoint varchar(512) NULL,
	app_id varchar(30) NOT NULL,
)
ENGINE=MyISAM;


ALTER TABLE fgt_apirequest ADD CONSTRAINT fgt_apirequest_pk PRIMARY KEY (apirequest_id,app_id);

ALTER TABLE fgt_apirequest ADD apirequest_timeexpired DATETIME NULL;
ALTER TABLE fgt_apirequest CHANGE apirequest_timeexpired apirequest_timeexpired DATETIME NULL AFTER apirequest_timestamp;


*/