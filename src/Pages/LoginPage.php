<?php namespace Fgta5\Webservice\Pages;

use Fgta5\Webservice\Page;
use Fgta5\Webservice\Configuration;
use Fgta5\Webservice\Template;
use Fgta5\Webservice\Debug;

class LoginPage extends Page {

	const SubPages = [
		'switch' => ['subpage'=>'login-switch.phtml'],
		'viawa' => ['subpage'=>'login-viawa.phtml'],
		'viampc' => ['subpage'=>'login-viampc.phtml'],
		'result' => ['subpage'=>'login-result.phtml']
	];

	private string $_subpagepath;
	

	protected string $waLoginLink;


	#[\Override]
	public function Load(array &$pagedata) : void {

		/*
		$LIB_FGTA5WEBSERVICE_DIR = Configuration::getAppConfig('LIB_FGTA5WEBSERVICE_DIR');
		$subpagejs = Template::getAssetUrlFromAbsPath(implode('/', [$LIB_FGTA5WEBSERVICE_DIR , 'jslibs/web-subpage-1.mjs']));
		Template::addJavascriptModule($subpagejs, 'window.$subpage');
		*/

		$this->_subpagepath = self::getSubPagePath($pagedata);


		$waloginnumber = Configuration::getAppConfig('WA_LOGIN_NUMBER');
		$lgCode = self::createLgCode();
		Template::addVariable('lgcode', $lgCode);
		$this->waLoginLink = self::createWaLoginURL($lgCode, $waloginnumber);



	}

	public function getSubPageBody() : string {
		ob_start();
		require_once $this->_subpagepath;
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}



	protected static function getSubPagePath(array &$pagedata) : string {
		$pageparameter = $pagedata['pageparameter'];
		if ($pageparameter=='' || !array_key_exists($pageparameter, self::SubPages)) {
			$pageparameter = 'switch';
		} 

		$config = self::SubPages[$pageparameter];
		$PAGE_DATA_DIR = Configuration::getAppConfig('PAGE_DATA_DIR');		
		$subpagepath = implode('/', [$PAGE_DATA_DIR, 'login', $config['subpage']]);
		if (!is_file($subpagepath)) {
			Debug::die("File '$subpagepath' tidak ditemukan");
		}
		return $subpagepath;
	}

	protected static function createLgCode() : string {
		return uniqid();
	}

	protected static function createWaLoginURL(string $lgCode, string $wanumber) : string {
		$message = "Halo Trans Fashion\r\n\r\nSaya mau #login ke website, kode referal ^$lgCode^";
		$message = urlencode($message);
		$waloginlink = "https://wa.me/$wanumber?text=$message";
		return $waloginlink;
	}
}
