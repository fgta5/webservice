<?php namespace Fgta5\Webservice;


class Configuration {

	public static mixed $APP_CONFIG;
	public static mixed $ACTIVE_CONFIG;


	public static function Read($file) {
		if (!is_file($file)) {
			Debug::die("Configuration '$file' not found");
		}
		require_once $file;
	}

	public static function getBaseAddress() : string {
		if (array_key_exists('HTTP_X_FORWARDED_HOST', $_SERVER)) {
			$server_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
			$server_host = $_SERVER['HTTP_X_FORWARDED_HOST']; 
			$baseaddress = "$server_proto://$server_host";
		} else {
			$baseaddress = self::getAppConfig('BASEADDRESS');
		}
		$baseaddress = trim($baseaddress, "/");
		return $baseaddress;
	}


	public static function isAppConfigExists(string $name) : bool {
		return array_key_exists($name, self::$APP_CONFIG);
	}

	public static function getAppConfig(string $name, mixed $valueIfBlank=null) : mixed {
		if (!array_key_exists($name, self::$APP_CONFIG)) {
			if ($valueIfBlank!=null) {
				return $valueIfBlank;
			} else {
				$bt = debug_backtrace();
				Debug::die("$name belum didefinisikan di APP_CONFIG", $bt);
			}
		}
		return self::$APP_CONFIG[$name];
	}


	public static function getActiveConfig(string $name) : mixed {
		if (!array_key_exists($name, self::$ACTIVE_CONFIG)) {
			$bt = debug_backtrace();
			Debug::die("$name belum didefinisikan di ACTIVE_CONFIG", $bt);
		}

		$appconfigpath = self::$ACTIVE_CONFIG[$name];
		$paths = explode('/', self::$ACTIVE_CONFIG[$name]);
		if (count($paths)!=2) {
			Debug::die("Format salah pada Active config '$name' ($appconfigpath). Seharusnya xxxx/yyyy");
		}

		$configgroup = $paths[0];
		$configname = $paths[1];

		$config = self::getAppConfig($configgroup);
		if (!array_key_exists($configname, $config)) {
			Debug::die("'$configname' belum didefinisi pada '$configgroup' di APP_CONFIG");
		}

		return $config[$configname];
	}




}