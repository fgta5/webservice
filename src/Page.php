<?php namespace Fgta5\Webservice;

class Page {
	
	const DEF_PAGE_DATA_DIR = __DIR__ . '/Pages/data';

	public static string $__configfilepath; 


	protected string $_subpagepath;
	protected array $_config;
	protected string $_phtml;
	
	public ?string $css;
	public ?string $mjs;

	function __construct(array &$config, string $devicetype) {
		$this->_config = $config;
		$this->_subpagepath = '';

		$this->_phtml = $this->getPageFileByDevice('phtml', $devicetype);
		
		$this->css = $this->getPageFileByDevice('css', $devicetype);
		$this->mjs = $this->getPageFile('mjs');

		
		if (!is_file($this->_phtml)) {
			$configfilepath = self::$__configfilepath;
			Debug::die("Cek konfigurasi \$PAGE_DATA pada file '$configfilepath'\r\npage file '$this->_phtml' tidak ditemukan");
		}



	}


	protected function getPageConfig() : array {
		return $this->_config;
	}

	protected function setSubPagePath(string $path) : void {
		$this->_subpagepath = $path;
	}

	protected function getSubPagePath() : string {
		return $this->_subpagepath;
	}


	protected function getSubPageData(array &$pagedata) : array {
		$PAGE_DATA_DIR = Configuration::getAppConfig('PAGE_DATA_DIR');

		$requestedpage = $pagedata['requestedpage'];
		$config = $this->getPageConfig();
		$configpath = $config['path'];


		if (!array_key_exists('subpages', $config)) {
			Debug::die("Cek file '$configpath'\r\nArray 'subpages' belum didefinisikan");
		}

		$subPages = $config['subpages'];
		$pageparameter = $pagedata['pageparameter'];
		if ($pageparameter=='' || !array_key_exists($pageparameter, $subPages)) {
			if (!array_key_exists('defaultsubpage', $config)) {
				Debug::die("Cek file '$configpath'\r\nkey 'defaultsubpage' belum didefinisikan");
			}
			$pageparameter = $config['defaultsubpage'];
		} 
		
		if (!array_key_exists($pageparameter, $subPages)) {
			Debug::die("Cek file '$configpath'\r\nkey '$pageparameter' belum didefinisikan di subpages");
		}

		$subpage = $subPages[$pageparameter];	
		$subpagepath = implode('/', [$PAGE_DATA_DIR, $requestedpage , $subpage['file']]);
		if (!is_file($subpagepath)) {
			Debug::die("File '$subpagepath' tidak ditemukan");
		}

		$data = [
			'path' => $subpagepath,
			'title' => $config['title']
		];

		if (array_key_exists('title', $subpage)) {
			$data['title'] = $subpage['title'];
		}

		if (array_key_exists('mjs', $subpage)) {
			$data['mjs'] = $subpage['mjs'];
		}

		return $data;
	}

	public function getSubPageBody() : string {
		ob_start();
		$subpagepath = $this->getSubPagePath();
		if (is_file($subpagepath)) {
			require_once $subpagepath;
		} else {
			echo "subpage not set";
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function Load(array &$pagedata) : void {
		
	}

	public function Show() : string {
		ob_start();
		require_once $this->_phtml;	
		$output = ob_get_contents(); 
		ob_end_clean();
		return $output;
	}

	public function setTitle(string $title) : void {
		Template::$PageTitle = $title;
	}

	
	public function getAssetUrl(string $assetpath) : string {
		return Template::getAssetUrl($assetpath);
	}

	public function getPageAssetUrl(string $assetpath) : string {
		$dirname = dirname($this->_phtml);
		return Template::mapAssetPathToUrl($assetpath, 	$dirname);
	}

	public static function getPageConfigDirname(string $pagename) : ?string  {

		$pagedir = self::DEF_PAGE_DATA_DIR;
		if (Configuration::isAppConfigExists('PAGE_DATA_DIR')) {
			$pagedir = Configuration::getAppConfig('PAGE_DATA_DIR');
		}
		$confDir = implode('/', [$pagedir, $pagename]);

		if (is_dir($confDir)) {
			return $confDir;
		} else {
			return null;
		}
	}



	public static function getPageConfigFile(string $pagename) : ?string  {
		$confdirname = self::getPageConfigDirname($pagename);
		if ($confdirname===null) {
			return null;
		} 
		$confFile = implode('/', [$confdirname, '_pageconf.php']);
		if (is_file($confFile)) {
			self::$__configfilepath = $confFile;
			return $confFile;
		} else {
			Debug::die("'$confFile' tidak ditemukan");
			return null;
		}
	}

	protected function getPageFileByDevice(string $name, string $devicetype) : ?string {
		$configfilepath = self::$__configfilepath;
		$device_confname = "$name-$devicetype";
		if (array_key_exists($device_confname, $this->_config)) {
			return $this->_config[$device_confname];
		} else if (array_key_exists($name, $this->_config)) {
			return $this->_config[$name];
		} else {
			if ($name=='phtml') {
				Debug::die("Cek file '$configfilepath'\r\n'$name' atau '$device_confname' belum didefinisikan di array \$PAGE_DATA");
			}
			return null;
		}
	}

	protected function getPageFile(string $name) : ?string {
		if (array_key_exists($name, $this->_config)) {
			return $this->_config[$name];
		} else {
			return null;
		}
	}
	

}