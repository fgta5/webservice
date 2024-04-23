<?php namespace Fgta5\Webservice\Apis;

use Fgta5\Webservice\Api;
use Fgta5\Webservice\CurrentState;
use Fgta5\Webservice\Configuration;
use Fgta5\Webservice\Session;


class WebAuth extends Api {

	public function login(string $requestedparameter, array $postdata, array $postheaders) : array {
		try {
			$requestData = $postdata['requestData'];

			//$username = $requestData['username'];
			//$password = $requestData['password'];

		

			//TODO: buat script untuk check data client	
			//Cek apakah client Valid


			//TODO: buat script untuk login
			// ...
			// ...

			// sampai di sini login sudah sukses
			// buat session
			$token = Session::create_token([
				'user_id' => 'dummy_user_id',
				'user_fullname' => 'dummy_user_fullname'
			]);
			$expired = Session::getTokenMaxLifeTime(); 

			return [
				'token' => $token,
				'expired' => $expired
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function logout(string $requestedparameter, array $postdata, array $postheaders) : array {
		try {
			$requestData = $postdata['requestData'];
			Session::clear_token();

			return [
				'success' => true
			];
		} catch (\Exception $ex) {
			throw $ex;
		}	
	}


	public function refreshToken(string $requestedparameter, array $postdata, array $postheaders) : array {
		try {

			$newtoken = Session::refresh_token();
			$expired = Session::getTokenMaxLifeTime(); 

			return [
				'newtoken' => $newtoken,
				'expired' => $expired
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}
	
	/*
	public function refreshSessionToken(string $requestedparameter, array $postdata, array $postheaders) : array {
		try {
			$session_maxlifetime = Configuration::getAppConfig('SESSION_API_MAX_LIFETIME', 300);
			Session::refresh($session_maxlifetime);
			return self::createSessionToken($requestedparameter, $postdata, $postheaders);
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public function createSessionToken(string $requestedparameter, array $postdata, array $postheaders) : array {
		try {
			$requestData = $postdata['requestData'];
			$username = $requestData['username'];
			$hashedPassword = $requestData['hashedPassword'];
			
			
			// TODO: Validasi Client
			// cek data client dari database
			$validClient = true; // dummy asumsi valid client
			if (!$validClient) {
				throw new \Exception('Client not valid', 10025); 
			}


			$timestamp = date_format(date_timestamp_set(new \DateTime(), time()), 'c');
			$raw = str_pad($username, 30, 'x', STR_PAD_RIGHT) . $timestamp;
			$sessid = session_id();
			$token = hash('sha256', $raw);;

			// TODO: Database AuthorizedSession
			// simpan di database authsession
			// appid, sessionid, username, token

			$currentTime = time();
			$expired = $_SESSION['expired'] - $currentTime;


			return [
				'username' => $username,
				'sessid'=>$sessid,
				'token'=>$token,
				'expired' => $expired
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}
	*/


}