<?php namespace Fgta5\Webservice;


class Device {
	const DESKTOP = 'desktop';
	const MOBILE = 'mobile';
	const TABLET = 'tablet';

	public static function isMobile() : bool {
		return is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "mobile"));
	}
	
	public static function isTablet() : bool {
		$tablet = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "tablet"));
		$ipad = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "ipad"));
		return $tablet | $ipad;
	}

	public static function getDeviceType() : string {
		if (self::isMobile()) {
			return self::MOBILE;
		} else if (self::isTablet()) {
			return self::TABLET;
		} else {
			return self::DESKTOP;
		}
	}

	
}


