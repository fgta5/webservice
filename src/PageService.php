<?php namespace Fgta5\Webservice;


class PageService extends Service {

	const CONTENT_PREFIX_IDENTIFIER = 'content';

	private string $_requestedpage;
	private string $_pageparameter;
	private ?string $_pageconffile;

	#[\Override]
	public static function PrepareService() : void {
		try {
			parent::PrepareService();

			CurrentState::connectToMainDB();

			$db = CurrentState::getMainDbConnection();
			Session::setDbConnection($db);
			Session::begin_page_session();

			$appid = Configuration::getAppConfig('APP_ID');
			$keypair = [
				'privatekey' => Configuration::getAppConfig('APP_PRIVATE_KEY')
			];
			Template::addVariable('appid', $appid); // untuk diakses dari javascript
			Template::addVariable('keypair', $keypair); // untuk diakses dari javascript

		} catch (\Exception $ex) {
			// Preparation error, hentikan proses
			Debug::die($ex->getMessage());
		}
	}

	#[\Override]
	public function Serve(string $requestedparameter) : string {

		$devicetype = Device::getDeviceType();
		$pagedata = self::processRequestedParameter($requestedparameter);

		$this->_requestedpage = $pagedata['requestedpage'];
		$this->_pageparameter = $pagedata['pageparameter'];
		$this->_pageconffile = $pagedata['pageconffile'];

		// jika confile masih tetap null, berarti ada kesalahan
		if ($this->_pageconffile===null) {
			Debug::die("tidak bisa menemukan '".$this->_requestedpage."' conffile : '" . $this->_pageconffile . "'");
		}

		require_once $this->_pageconffile;
		if (!isset($PAGE_CONFIG)) {
			Debug::die("\$PAGE_CONFIG tidak didefinisikan di conffile : '" . $this->_pageconffile . "'");
		}

		if (!is_array($PAGE_CONFIG)) {
			Debug::die("Cek file'$this->_pageconffile'\r\n\$PAGE_CONFIG harus bertipe array.\r\n\$PAGE_CONFIG = ['classname'=>'your\\class\\name']");
		}

		if (!array_key_exists('classname', $PAGE_CONFIG)) {
			Debug::die("Cek file'$this->_pageconffile'\r\nclassname belum didefinisikan di \$PAGE_CONFIG.\r\n\$PAGE_CONFIG = ['classname'=>'your\\class\\name']");
		}


		$authorized = array_key_exists('authorized', $PAGE_CONFIG) ? $PAGE_CONFIG['authorized'] : true;
		if ($authorized!==false) {
			try {
				$this->VerifyAuthorization();
			} catch (\Exception $ex) {
				echo "login dulu";	
				die();
			}
		}

		$page_classname = $PAGE_CONFIG['classname'];
		if (!class_exists($page_classname)) {
			Debug::die("Class '$page_classname' tidak ditemukan");
		}

		if (!is_subclass_of($page_classname, 'Fgta5\Webservice\Page')) {
			Debug::die("Class '$page_classname' tidak inherit dari 'Fgta5\Webservice\Page'");
			
		}

		$PAGE_CONFIG['path'] = $this->_pageconffile;
		$PAGE_CONFIG['pagename'] = $this->_requestedpage;
		if (array_key_exists('title', $PAGE_CONFIG)) {
			Template::$PageTitle = $PAGE_CONFIG['title'];	
		}
		
		$page = new $page_classname($PAGE_CONFIG, $devicetype);
		$page->Load($pagedata);

		$tpldir = Configuration::getActiveConfig('TEMPLATE');

		Template::Use($tpldir, $devicetype);
		$output = Template::Render($page);
		return $output;

	}

	public static function processRequestedParameter(string $requestedparameter) : array {
		$_requestedpage = null;
		$_pageconffile = null;
		$_pageparameter = null;

		// apakah kontent
		$params = explode('/', $requestedparameter);
		$_requestedpage = $params[0];


		

		if ($params[0]==self::CONTENT_PREFIX_IDENTIFIER) {
			// jika diawali dengan content/xxxxx/yyyyy/zzzzz isinya content
			$_pageconffile = Page::getPageConfigFile($_requestedpage);
			$_pageparameter = self::getPageRequestParameter($requestedparameter, $_requestedpage);
		} else {
			// selain itu seharusnya page yang didefine pada direktori data/pages
			$confdir = Page::getPageConfigDirname($_requestedpage);
			$_pageconffile = Page::getPageConfigFile($_requestedpage);
			if ($_pageconffile===null && $confdir==null) {
				// conffile null, request akan dilarikan ke content
				$_requestedpage = self::CONTENT_PREFIX_IDENTIFIER;
				$_pageconffile = Page::getPageConfigFile($_requestedpage);
				$_pageparameter = $requestedparameter;
				
			} else {
				$_pageconffile = Page::getPageConfigFile($_requestedpage);
				$_pageparameter = self::getPageRequestParameter($requestedparameter, $_requestedpage);
			}
		}

		return [
			'requestedpage' => $_requestedpage,
			'pageconffile' => $_pageconffile,
			'pageparameter' => $_pageparameter
		];
	}


	public static function getPageRequestParameter(string $requestedparameter, string $pagename) : string {
		if (substr($requestedparameter, 0, strlen($pagename)) == $pagename) {
			$requestedparameter = substr($requestedparameter, strlen($pagename));
		} 
		return trim($requestedparameter, '/');
	}

	protected function VerifyAuthorization() : void {
		try {
			if (Session::getCurrentLogin()==null) {
				throw new \Exception('Sesi ini tidak diauthorisasi', 10020); 
			}
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

}

