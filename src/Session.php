<?php namespace Fgta5\Webservice;

class Session {

	public const SESSION_TABLE = 'session';
	public const TOKEN_TABLE = 'token';
	public const API_MAX_LIFETIME = 30; //5*60;
	public const PAGE_MAX_LIFETIME = 1*60; //30*60;
	public const TOKEN_MAX_LIFETIME = 10*60;

	private static \PDO $__dbConn;

	public static function setDbConnection(\PDO $dbConn) : void {
		self::$__dbConn = $dbConn;
	}

	public static function getTokenApiMaxLifeTime() : int {
		return Configuration::getAppConfig('TOKEN_API_MAX_LIFETIME', self::API_MAX_LIFETIME);
	} 

	public static function getTokenPageMaxLifeTime() : int {
		return Configuration::getAppConfig('TOKEN_PAGE_MAX_LIFETIME', self::PAGE_MAX_LIFETIME);
	} 


	public static function create_token(array $userinfo) : string {
		self::clearExpiredToken();


		// fungsi ini adalah digunakan pada saat login via API
		// buat sesi baru pada api, atau gunakan sesi yang sudah ada dari page
		$accesstype = 'api';
		$already_has_session = false;
		$db = self::$__dbConn;

		try {
			$maxlifetime = self::getTokenApiMaxLifeTime();
			if (is_array($_COOKIE)) {
				if (array_key_exists('PHPSESSID', $_COOKIE)) {
					$presessid = $_COOKIE['PHPSESSID'];
					$sessdata = self::get($presessid); // cek session di database, apakah sudah ada sesi yang di create dari page
					if ($sessdata!=null) {
						$sessid = $_COOKIE['PHPSESSID'];
						$already_has_session = true;
						$maxlifetime = $sessdata['session_maxlifetime'];
						$accesstype = 'page';
					} 
				}
			}

			$user_id = $userinfo['user_id'];

			$currentTime = time();
			$expireTime = $currentTime + $maxlifetime;
			$session_timestamp = date_format(date_timestamp_set(new \DateTime(), $currentTime), 'Y-m-d H:i:s');
			$session_timeexpired = date_format(date_timestamp_set(new \DateTime(), $expireTime), 'Y-m-d H:i:s');
			$timestamp = date_format(date_timestamp_set(new \DateTime(), time()), 'c');
			$raw = str_pad($user_id, 30, 'x', STR_PAD_RIGHT) . $timestamp;
			$token = hash('sha256', $raw);
			$token_id = uniqid();

			if ($already_has_session) {
				session_id($sessid);
				session_start();

				$sidlen = str_pad(strlen($sessid), 3, 0, STR_PAD_LEFT);
				$token = $sidlen . $sessid . $token_id . $token;

				self::update([
					'session_id' => $sessid,
					'session_lastaccess' => $session_timestamp,     // ubah tanggal terakhir akses
					'session_timeexpired' => $session_timeexpired,   // mundurkan waktu expirednya
					'session_token' => $token,
					'user_id' => $userinfo['user_id'],
					'user_fullname' => $userinfo['user_fullname']
				]);
			} else {
				session_start();
				$sessid = session_id();

				$sidlen = str_pad(strlen($sessid), 3, 0, STR_PAD_LEFT);
				$token = $sidlen . $sessid . $token_id . $token;

				self::create([
					'session_id' => $sessid,
					'session_timestart' =>  $session_timestamp,
					'session_timeexpired' => $session_timeexpired,
					'session_accesstype' => $accesstype,
					'session_maxlifetime' => $maxlifetime,
					'session_token' => $token,
					'user_id' => $userinfo['user_id'],
					'user_fullname' => $userinfo['user_fullname']
				]);
			}

			// simpan di fgt_token
			$maxlifetime = $accesstype=='api' ? self::getTokenApiMaxLifeTime() : self::getTokenPageMaxLifeTime();
			$data = [
				'token_id' => $token_id,
				'token_data' => $token,
				'token_timestart' => date_format(date_timestamp_set(new \DateTime(), time()), 'Y-m-d H:i:s'),
				'token_timeexpired' => date_format(date_timestamp_set(new \DateTime(), time()+$maxlifetime), 'Y-m-d H:i:s'),
				'user_id' => $userinfo['user_id'],
				'user_fullname' => $userinfo['user_fullname']
			];
			$cmd = SqlCommand::create(self::TOKEN_TABLE, $data);
			$query = $cmd->generateSQL_Insert();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']); 


			// login
			$sessdata = self::get($sessid); // ambil data sessid yang sudah update
			$_SESSION['islogin'] = true;
			$_SESSION['user_id'] = $sessdata['user_id'];
			$_SESSION['user_fullname'] = $sessdata['user_fullname'];
			$_SESSION['token'] = $token;

			
			return $token;
		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	public static function refresh_token() : string {
		self::clearExpiredToken();

		$db = self::$__dbConn;

		try {
			$newtoken = 'xxx';
			
			$sessid = session_id();
			$oldtoken = null;
			if (array_key_exists('FGTATOKEN', $_COOKIE)) {
				$oldtoken = $_COOKIE['FGTATOKEN'];
			} else if (array_key_exists('HTTP_X_TOKEN', $_SERVER)) {
				$oldtoken = $_SERVER['HTTP_X_TOKEN'];
			} 

			if ($oldtoken==null) {
				throw new \Exception('token lama tidak diketahui', 10010);
			}

			$sessidlength = (int)substr($oldtoken, 0, 3);
			$sessid = substr($oldtoken, 3, $sessidlength);
			$old_token_id = substr($oldtoken, 3+$sessidlength, 13);

			$old_tokendata = self::getToken($old_token_id); // ambil data user_id dan user_fullname dari fgt_token
			if ($old_tokendata==null) {
				throw new \Exception('Current Token tidak valid', 13003); 
			}
			
			$user_id = $old_tokendata['user_id'];
			$user_fullname = $old_tokendata['user_fullname'];


			// Create Token baru
			$sessdata = self::get($sessid); 
			$accesstype = $sessdata['session_accesstype'];
			$maxlifetime = $accesstype=='api' ? self::getTokenApiMaxLifeTime() : self::getTokenPageMaxLifeTime();
	
			$sidlen = str_pad(strlen($sessid), 3, 0, STR_PAD_LEFT);
			$timestamp = date_format(date_timestamp_set(new \DateTime(), time()), 'c');
			$raw = str_pad($user_id, 30, 'x', STR_PAD_RIGHT) . $timestamp;
			$token = hash('sha256', $raw);
			$new_token_id = uniqid();
			$new_token = $sidlen . $sessid . $new_token_id . $token;
	
			$data = [
				'token_id' => $new_token_id,
				'token_data' => $new_token,
				'token_timestart' => date_format(date_timestamp_set(new \DateTime(), time()), 'Y-m-d H:i:s'),
				'token_timeexpired' => date_format(date_timestamp_set(new \DateTime(), time()+$maxlifetime), 'Y-m-d H:i:s'),
				'user_id' => $user_id,
				'user_fullname' => $user_fullname
			];
			$cmd = SqlCommand::create(self::TOKEN_TABLE, $data);
			$query = $cmd->generateSQL_Insert();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']); 
			$_SESSION['token'] = $new_token;


			// hapus token lama
			$data = [
				'token_id' => $old_token_id,
			];
			$keys = ['token_id'];
			$cmd = SqlCommand::create(self::TOKEN_TABLE, $data, $keys);
			$query = $cmd->generateSQL_Delete();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);


			// update session
			$maxlifetime = $sessdata['session_maxlifetime'];
			$currentTime = time();
			$expireTime = $currentTime + $maxlifetime;
			$session_timestamp = date_format(date_timestamp_set(new \DateTime(), $currentTime), 'Y-m-d H:i:s');
			$session_timeexpired = date_format(date_timestamp_set(new \DateTime(), $expireTime), 'Y-m-d H:i:s');

			$data = [
				'session_id' => $sessid,
				'session_lastaccess' => $session_timestamp,     // ubah tanggal terakhir akses
				'session_timeexpired' => $session_timeexpired,   // mundurkan waktu expirednya
				'session_token' => $new_token
			];
			$keys = ['session_id'];
			$cmd = SqlCommand::create(self::SESSION_TABLE, $data, $keys);
			$query = $cmd->generateSQL_Update();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);

			return $new_token;
		} catch (\Exception $ex) {
			throw $ex;
		}
	}


	public static function clear_token() : void {
		self::clearExpiredToken();

		$db = self::$__dbConn;

		try {

			$sessid = session_id();
			$token = null;
			if (array_key_exists('FGTATOKEN', $_COOKIE)) {
				$token = $_COOKIE['FGTATOKEN'];
			} else if (array_key_exists('HTTP_X_TOKEN', $_SERVER)) {
				$token = $_SERVER['HTTP_X_TOKEN'];
			} 

			if ($token!=null) {
				$sessidlength = (int)substr($token, 0, 3);
				$sessid = substr($token, 3, $sessidlength);
				$token_id = substr($token, 3+$sessidlength, 13);

				$data = [
					'token_id' => $token_id
				];
				$keys = ['token_id'];
				$cmd = SqlCommand::create(self::TOKEN_TABLE, $data, $keys);
				$query = $cmd->generateSQL_Delete();
				$stmt = $db->prepare($query['sql']);
				$stmt->execute($query['parameter']); 
			}

			// update session
			$session_timestamp = date_format(date_timestamp_set(new \DateTime(), time()), 'Y-m-d H:i:s');
			self::update([
				'session_id' => $sessid,
				'session_lastaccess' => $session_timestamp,     // ubah tanggal terakhir akses
				'session_token' => null,
				'user_id' => null,
 				'user_fullname' => null
			]);

			$_SESSION['islogin'] = false;
			$_SESSION['user_id'] = null;
			$_SESSION['user_fullname'] = null;
			$_SESSION['token'] = null;

			//print_r($sessid);

		} catch (\Exception $ex) {
			throw $ex;
		}
	}



	public static function begin_api_session() : void {
		self::clearExpiredSession();

		$token_id = null;
		$accesstype = 'api';
		try {
			if (!array_key_exists('HTTP_X_TOKEN', $_SERVER)) {
				throw new \Exception('token tidak ditemukan', 11002);
			}
			$token = $_SERVER['HTTP_X_TOKEN'];

			$sessidlength = (int)substr($token, 0, 3);
			if ($sessidlength<=0 || $sessidlength>36) {
				throw new \Exception('panjang token yang dikirim tidak sesuai', 13001);
			}

			// cek apakah session id ada di database, dan tokennya sesuai
			$create_new_session = false;
			$token_id = substr($token, 3+$sessidlength, 13);
			$sessid = substr($token, 3, $sessidlength);
			$sessdata = self::get($sessid);
			if ($sessdata==null) {
				$maxlifetime = self::getTokenApiMaxLifeTime(); //self::API_MAX_LIFETIME;
				$create_new_session = true;
			} else {
				$maxlifetime = $sessdata['session_maxlifetime'];
				$create_new_session = false;
				if ($token!=$sessdata['session_token']) {   // cek apakah token sesuai
					throw new \Exception('Token tidak sesuai', 13002);
				}
			}

			$currentTime = time();
			$expireTime = $currentTime + $maxlifetime;
			$session_timestamp = date_format(date_timestamp_set(new \DateTime(), $currentTime), 'Y-m-d H:i:s');
			$session_timeexpired = date_format(date_timestamp_set(new \DateTime(), $expireTime), 'Y-m-d H:i:s');

			session_id($sessid);
			session_start();

			if ($create_new_session) {
				$data = [
					'session_id' => $sessid,
					'session_timestart' =>  $session_timestamp,
					'session_timeexpired' => $session_timeexpired,
					'session_accesstype' => $accesstype,
					'session_maxlifetime' => $maxlifetime
				];

				if ($token_id!=null) {
					$tokendata = self::getToken($token_id); // ambil data user_id dan user_fullname dari fgt_token
					if ($tokendata!=null) {
						$data['session_token'] = $tokendata['token_data'];
						$data['user_id'] = $tokendata['user_id'];
						$data['user_fullname'] = $tokendata['user_fullname'];
					}
				}

				self::create($data);
			} else {
				self::update([
					'session_id' => $sessid,
					'session_lastaccess' => $session_timestamp,     // ubah tanggal terakhir akses
					'session_timeexpired' => $session_timeexpired   // mundurkan waktu expirednya
				]);
			}
			
			$sessdata = self::get($sessid);
			if ($sessdata['user_id']!=null) {
				$_SESSION['islogin'] = true;
				$_SESSION['user_id'] = $sessdata['user_id'];
				$_SESSION['user_fullname'] = $sessdata['user_fullname'];
				$_SESSION['token'] = $sessdata['session_token'];
			}

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function begin_page_session() : void {
		self::clearExpiredSession();

		$token_id = null;
		$accesstype = 'page';
		$already_has_session = false;
		if (is_array($_COOKIE)) {
			if (array_key_exists('FGTATOKEN', $_COOKIE)) {
				$token = $_COOKIE['FGTATOKEN'];
				$sessidlength = (int)substr($token, 0, 3);
				$sessid = substr($token, 3, $sessidlength);
				$token_id = substr($token, 3+$sessidlength, 13);
				$already_has_session = true;
			} else if (array_key_exists('PHPSESSID', $_COOKIE)) {
				$sessid = $_COOKIE['PHPSESSID'];
				$already_has_session = true;
			}
		}

		$maxlifetime = self::getTokenPageMaxLifeTime(); //self::PAGE_MAX_LIFETIME; // 30 menit
		$currentTime = time();
		$expireTime = $currentTime + $maxlifetime;
		$session_timestamp = date_format(date_timestamp_set(new \DateTime(), $currentTime), 'Y-m-d H:i:s');
		$session_timeexpired = date_format(date_timestamp_set(new \DateTime(), $expireTime), 'Y-m-d H:i:s');

		$create_new_session = false;
		if ($already_has_session) {
			$sessdata = self::get($sessid);
			if ($sessdata==null) {
				$create_new_session = true; // session di database sudah expired, regenerate session baru
			} else {
				$create_new_session = false;
			}
			session_id($sessid);
			session_start();
		} else {
			$create_new_session = true;
			session_start();
			$sessid = session_id();
		}


		if ($create_new_session) {
			$data = [
				'session_id' => $sessid,
				'session_timestart' =>  $session_timestamp,
				'session_timeexpired' => $session_timeexpired,
				'session_accesstype' => $accesstype,
				'session_maxlifetime' => $maxlifetime
			];

			if ($token_id!=null) {
				$tokendata = self::getToken($token_id); // ambil data user_id dan user_fullname dari fgt_token
				if ($tokendata!=null) {
					$data['session_token'] = $tokendata['token_data'];
					$data['user_id'] = $tokendata['user_id'];
					$data['user_fullname'] = $tokendata['user_fullname'];
				}
			}

			self::create($data);
		} else {
			self::update([
				'session_id' => $sessid,
				'session_lastaccess' => $session_timestamp,     // ubah tanggal terakhir akses
				'session_timeexpired' => $session_timeexpired   // mundurkan waktu expirednya
			]);
		}


		$sessdata = self::get($sessid);
		if ($sessdata['user_id']!=null) {
			$_SESSION['islogin'] = true;
			$_SESSION['user_id'] = $sessdata['user_id'];
			$_SESSION['user_fullname'] = $sessdata['user_fullname'];
			$_SESSION['token'] = $sessdata['session_token'];
		} else {
			$_SESSION['islogin'] = false;
			$_SESSION['user_id'] = null;
			$_SESSION['user_fullname'] = null;
			$_SESSION['token'] = null;
		}

	}

	public static function getToken(string $token_id) : ?array {
		$db = self::$__dbConn;
		try {
			$data = [
				'token_id' => $token_id,
				'token_data' => null,
				'user_id' => null,
				'user_fullname' => null
			];
			$keys = ['token_id'];
			$cmd = SqlCommand::create(self::TOKEN_TABLE, $data,  $keys);
			$query = $cmd->generateSQL_Select();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);
			$row = $stmt->fetch();
			if (!$row) {
				return null;
			} else {
				return $row;
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function get(string $sessid) : ?array {
		$db = self::$__dbConn;
		try {
			$data = [
				'session_id' => $sessid,
				'session_maxlifetime' => null,
				'session_timeexpired' => null,
				'session_token' => null,
				'session_accesstype' => null,
				'user_id' => null,
				'user_fullname' => null
			];
			$keys = ['session_id'];
			$cmd = SqlCommand::create(self::SESSION_TABLE, $data,  $keys);
			$query = $cmd->generateSQL_Select();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);

			$row = $stmt->fetch();
			if (!$row) {
				return null;
			} else {
				return $row;
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function update(array $data) : void {

		$db = self::$__dbConn;
		try {
			$keys = ['session_id'];
			$cmd = SqlCommand::create(self::SESSION_TABLE, $data,  $keys);
			$query = $cmd->generateSQL_Update();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']);
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function create(array $data) : void {

		$db = self::$__dbConn;
		try {
			$cmd = SqlCommand::create(self::SESSION_TABLE, $data);
			$query = $cmd->generateSQL_Insert();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']); 
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function getCurrentLogin() : ?array {
		$islogin = array_key_exists('islogin', $_SESSION) ? $_SESSION['islogin'] : false;

		if ($islogin) {
			// cek token
			$client_token = null;
			if (array_key_exists('FGTATOKEN', $_COOKIE)) {
				$client_token = $_COOKIE['FGTATOKEN'];
			} else if (array_key_exists('HTTP_X_TOKEN', $_SERVER)) {
				$client_token = $_SERVER['HTTP_X_TOKEN'];
			}

			$server_token = array_key_exists('token', $_SESSION) ? $_SESSION['token'] : false;
			if ($client_token==$server_token) {
				return [
					'user_id' => $_SESSION['user_id'],
					'user_fullname' => $_SESSION['user_fullname']
				];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	public static function clearExpiredSession() : void {
		$db = self::$__dbConn;

		try {
			$now = date_format(date_timestamp_set(new \DateTime(), time()), 'Y-m-d H:i:s');
			$data = [
				'session_timeexpired' => ['<', $now]
			];
			$keys = ['session_timeexpired'];
			$cmd = SqlCommand::create(self::SESSION_TABLE, $data, $keys);
			$query = $cmd->generateSQL_Delete();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']); 

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

	public static function clearExpiredToken() : void {
		$db = self::$__dbConn;

		try {
			$now = date_format(date_timestamp_set(new \DateTime(), time()), 'Y-m-d H:i:s');
			$data = [
				'token_timeexpired' => ['<', $now]
			];
			$keys = ['token_timeexpired'];
			$cmd = SqlCommand::create(self::TOKEN_TABLE, $data, $keys);
			$query = $cmd->generateSQL_Delete();
			$stmt = $db->prepare($query['sql']);
			$stmt->execute($query['parameter']); 

		} catch (\Exception $ex) {
			throw $ex;
		}
	}

}






/*

CREATE TABLE public.session (
	session_id varchar(33) NOT NULL,
	session_timestart timestamp NOT NULL,
	session_maxlifetime int NOT NULL,
	session_timeexpired timestamp NOT NULL,
	session_lastaccess timestamp NULL,
	session_token varchar(1000) NULL,
	session_accesstype varchar(4) NULL,
	user_id varchar(30) NULL,
	user_fullname varchar(255) NULL,
	CONSTRAINT pk_session_id PRIMARY KEY (session_id)
);




CREATE TABLE public.token (
	token_id varchar(13) NOT NULL,
	token_data varchar(1000) NULL,
	token_timestart timestamp NOT NULL,
	token_timeexpired timestamp NOT NULL,
	user_id varchar(30) NULL,
	user_fullname varchar(255) NULL,
	CONSTRAINT pk_token_id PRIMARY KEY (token_id)	
);


create or replace function public.trigger_session_onupdate ()
	returns trigger
	language plpgsql
	as $$
	begin
		if (new.session_accesstype <> old.session_accesstype) then
			raise exception 'nilai kolom session_accesstype tidak boleh diganti' using ERRCODE=99001;
		end if;
		
		return old;
	end $$;


create or replace trigger onupdate
	before update of session_accesstype on public.session 
	for each row
	execute function trigger_session_onupdate()







CREATE TABLE fgt_session (
	session_id varchar(33) NOT NULL,
	session_timestart DATETIME NOT NULL,
	session_maxlifetime int NOT NULL,
	session_timeexpired DATETIME NOT NULL,
	session_lastaccess DATETIME NULL,
	session_token varchar(1000) NULL,
	session_accesstype varchar(4) NULL,
	user_id varchar(30) NULL,
	user_fullname varchar(255) NULL
)
ENGINE=MyISAM;

ALTER TABLE fgt_session ADD CONSTRAINT fgt_session_pk PRIMARY KEY (session_id);

CREATE DEFINER=`root`@`%` TRIGGER onupdate
AFTER UPDATE
ON fgt_session FOR EACH ROW
begin
	declare msg varchar(128);
    if new.session_accesstype <> old.session_accesstype then
        set msg = concat('Access Type: ', ' tidak dapat diganti');
        signal sqlstate '45000' set message_text = msg;
    end if;
end







CREATE TABLE fgt_token (
	token_id varchar(13) NOT NULL,
	token_data varchar(1000) NULL,
	token_timestart DATETIME NOT NULL,
	token_timeexpired DATETIME NOT NULL,
	user_id varchar(30) NULL,
	user_fullname varchar(255) NULL	
)
ENGINE=MyISAM;

ALTER TABLE fgt_token ADD CONSTRAINT fgt_token_pk PRIMARY KEY (token_id);




*/