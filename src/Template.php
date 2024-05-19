<?php namespace Fgta5\Webservice;

class Template {
	

	public static string $PageTitle = 'WEB';
	
	
	private static Page $__page;
	private static array $__DATA;
	private static array $__config;
	private static string $__file;
	private static string $__mjs;
	private static string $__dir;
	private static array $__variables = [];


	private static array $__tplVar = [];
	private static array $__javascripts = [];
	private static array $__javascriptsmodule = [];
	private static array $__css = [];


	public static function getVariable(string $name, mixed $defaultvalue = null) : mixed {
		if (array_key_exists($name, self::$__variables)) {
			return self::$__variables[$name];
		} else {
			return $defaultvalue;
		}
	}

	public static function setVariable(string $name, mixed $value) : void {
		self::$__variables[$name] = $value;
	}

	public static function Use(string $tpldir, ?string $devicetype=null) {
		self::$__dir = $tpldir;

		// cek apakah direktory ada
		if (!is_dir($tpldir)) {
			Debug::die("Template direcktory '$tpldir' tidak ditemukan");
		}

		if ($devicetype==null) {
			$devicetype = Device::getDeviceType();
		}

		$configfile = implode('/', [$tpldir, '_templateconfig.php']);
		self::ReadConfiguration($configfile);

		if (!array_key_exists($devicetype, self::$__DATA)) {
			Debug::die("Device '$devicetype' belum didefinisikan di template config '$configfile'");
		}
		self::$__config = self::$__DATA[$devicetype];


		if (!array_key_exists('templatefile', self::$__config)) {
			Debug::die("'templatefile' belum didefinisikan di '$devicetype' template config '$configfile'");
		}

		self::$__file = implode('/', [self::$__dir, self::$__config['templatefile']]);
		if (!is_file(self::$__file)) {
			Debug::die("File template '".self::$__file."' tidak ditemukan");
		}

		if (array_key_exists('mjs', self::$__config)) {
			$tplmjsfile = implode('/', [self::$__dir, self::$__config['mjs']]);
			if (is_file($tplmjsfile)) {
				self::$__mjs = $tplmjsfile;
			} else {
				Debug::die("File '$tplmjsfile' tidak ditemukan");
			}
		}	


	}

	public static function Render(Page &$page) : string {
		self::$__page = $page;

		ob_start();
		require_once self::$__file;
		$output = ob_get_contents(); 
		ob_end_clean();
		return $output;
	}

	static function getPage() : Page {
		return self::$__page;
	}

	static function getPageCssUrl() : ?string {
		$csspath = self::getPage()->css;
		if ($csspath!=null) {
			return self::getAssetUrlFromAbsPath($csspath);
		} else {
			return null;
		}
	}

	static function getPageMjsUrl() : ?string {
		$mjspath = self::getPage()->mjs;
		if ($mjspath!=null) {
			return self::getAssetUrlFromAbsPath($mjspath);
		} else {
			return null;
		}
	}


	static function ReadConfiguration(string $configfile) : array {
		if (!is_file($configfile)) {
			$bt = debug_backtrace();
			Debug::die("Template config file '$configfile' tidak ditemukan", $bt);
		}


		require_once $configfile;

		if (!isset($TEMPLATE_CONFIG)) {
			Debug::die("cek file '$configfile'\r\nArray \$TEMPLATE_CONFIG belum didefinisikan");
		}


		if (!is_array($TEMPLATE_CONFIG)) {
			Debug::die("Format Template config di '$configfile' tidak sesuai\r\n\$TEMPLATE_CONFIG harus Array yang berisi konfigurasi template");
		}

		self::$__DATA = &$TEMPLATE_CONFIG;
		return $TEMPLATE_CONFIG;
	}

	public static function mapAssetPathToUrl($assetpath, $dirname) : string {
		$assetpath = trim($assetpath, '/');

		$path = '';
		if (substr($dirname, 0, strlen(__ROOT_DIR__)) == __ROOT_DIR__) {
			$path = trim(substr($dirname, strlen(__ROOT_DIR__)), '/');
		} 

		if ($path!='') {
			$url = implode('/', [self::getBaseAddress(), 'asset', $path, $assetpath]);
			return $url;
		} else {
			$url = implode('/', [self::getBaseAddress(), 'asset', $assetpath]);
			return $url;
		}
	}

	public static function getAssetUrlFromAbsPath(string $abspath) : string {
		$dirname = dirname($abspath);
		$filename = basename($abspath);
		return self::mapAssetPathToUrl($filename, $dirname);
	}

	public static function getTemplateAssetUrl(string $assetpath) : string {
		return self::mapAssetPathToUrl($assetpath, self::$__dir);
	}

	public static function getAssetUrl(string $assetpath) : string {
		return self::mapAssetPathToUrl($assetpath, __ROOT_DIR__);
	}


	public static function getBaseAddress() : string {
		return Configuration::getBaseAddress();
	}

	static function getGlobalVars() : array {
		$data = [
			'baseAddress' => self::getBaseAddress()
		];

		foreach (self::$__tplVar as $name=>$value) {
			$data[$name] = $value;
		}

		return $data;
	} 

	public static function addVariable(string $name, mixed $value) : void {
		self::$__tplVar[$name] = $value;
	}

	public static function addJavascript(string $scripturl) : void {
		if (!in_array($scripturl, self::$__javascripts)) {
			self::$__javascripts[] = $scripturl;
		}
	}

	public static function addJavascriptModule(string $scripturl, string $varname) : void {
		if (!in_array($scripturl, self::$__javascriptsmodule)) {
			self::$__javascriptsmodule[] = ['scripturl'=>$scripturl, 'varname'=>$varname];
		}
	}


	public static function addCss(string $cssurl) : void {
		if (!in_array($cssurl, self::$__css)) {
			self::$__css[] = $cssurl;
		}
	}



	public static function includeAllJavascript()  {
		foreach (self::$__javascripts as $scripturl) {
			echo "<script src=\"$scripturl\"></script>\r\n";
		}
	}

	public static function includeAllCSS()  {
		foreach (self::$__css as $cssurl) {
			echo "<link rel=\"stylesheet\" href=\"$cssurl\">\r\n";	
		}
	}

	public static function pageMjsStart() : void {
		// base64 javascript library
		$window_base64 = 'window.Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/\r\n/g,"\n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}';


		// template variable
		$window_tpl = "window.\$tpl = {}\r\n";
		$vr = self::getGlobalVars();
		foreach ($vr as $name=>$value) {
			if (is_object($value) || is_array($value)) {
				$dval = base64_encode(json_encode($value));
				$window_tpl .= "window.\$tpl.$name = JSON.parse(window.Base64.decode(`$dval`))\r\n";
			} else if (is_numeric($value)) {
				$window_tpl .= "window.\$tpl.$name = $value\r\n";
			} else if (is_bool($value)) {
				$dval = $value ? 'true' : 'false';
				$window_tpl .= "window.\$tpl.$name = $dval\r\n";
			} else {
				$window_tpl .= "window.\$tpl.$name = `$value`\r\n";
			}
		}


		// UI
		$windowui = "\r\n";
		$pagemjsurl = Template::getPageMjsUrl();
		if ($pagemjsurl!=null) {
			$windowui .= "import * as ui from '$pagemjsurl'\r\n";
			$windowui .= "let uibase = Object.assign({}, uibaseclass)\r\n";
			$windowui .= "window.\$ui = Object.assign(uibase, ui)\r\n";
		} else {
			$windowui .= "window.\$ui = uibaseclass;\r\n";
		}
		$windowui .= "\r\n";


		$LIB_FGTA5WEBSERVICE_DIR = Configuration::getAppConfig('LIB_FGTA5WEBSERVICE_DIR');
		

		// other javascript modules need to be loaded
		$i = 0;
		$otherModuleImport = "";
		$otherModuleAssignToWindow = "";
		$otherModuleInit = "";
		foreach (self::$__javascriptsmodule as $md) {
			$i++;
			$scripturl = $md['scripturl'];
			$varname = $md['varname'];
			$otherModuleImport .= "			import * as mod$i from '$scripturl'\r\n";
			$otherModuleAssignToWindow .= "			$varname = mod$i\r\n";
			$otherModuleInit .= "				await $varname.Init();\r\n";
		}

		if (isset(self::$__mjs)) {
			$otherModuleImport .= "			import * as tpllib from '".Template::getAssetUrlFromAbsPath(self::$__mjs)."'\r\n";
			$otherModuleAssignToWindow .= "			window.\$tpl.lib = tpllib\r\n";
			$otherModuleInit .= "				await window.\$tpl.lib.Init();\r\n";
		}


		// hasil script
		$tempscripts =  "
		<script>
			$window_base64

			window.\$global = {}
			$window_tpl 


			window.\$ui = null;
			window.addEventListener(\"load\", async (event) => {

				// Prepare Template
				if (window.\$tpl.lib!==undefined) {
					if (typeof window.\$tpl.lib.Prepare==='function') {
						await window.\$tpl.lib.Prepare();
					}
				}

				// trigger UI Read
				await window.\$ui.Prepare();

				// trigger UI Init
				await window.\$ui.Init();
$otherModuleInit

				// trigger UI Read
				await window.\$ui.Ready();

				// Template Ready
				if (window.\$tpl.lib!==undefined) {
					if (typeof window.\$tpl.lib.Ready==='function') {
						window.\$tpl.lib.Ready();
					}
				}
		
			});
		</script>
		<script type=\"module\">
			import * as utils from '".Template::getAssetUrlFromAbsPath(implode('/', [$LIB_FGTA5WEBSERVICE_DIR, 'jslibs/web-utils-1.mjs']))."'
			import * as cookie from '".Template::getAssetUrlFromAbsPath(implode('/', [$LIB_FGTA5WEBSERVICE_DIR, 'jslibs/web-cookie-1.mjs']))."'
			import * as uibaseclass from '".Template::getAssetUrlFromAbsPath(implode('/', [$LIB_FGTA5WEBSERVICE_DIR, 'jslibs/web-uibase-1.mjs']))."'
$otherModuleImport

			window.\$utils = utils;
			window.\$cookie = cookie;
$otherModuleAssignToWindow

			$windowui

		</script>			
		
		";

		echo $tempscripts;
	}


}