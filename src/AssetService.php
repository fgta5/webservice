<?php namespace Fgta5\Webservice;

class AssetService extends Service {

	const ALLOWED_EXTENSIONS = array(
		'js' => ['contenttype'=>'application/javascript'],
		'mjs' => ['contenttype'=>'application/javascript'],
		'css' => ['contenttype'=>'text/css'],
		'gif' => ['contenttype'=>'image/gif'],
		'bmp' => ['contenttype'=>'image/bmp'],
		'png' => ['contenttype'=>'image/png'],
		'jpg' => ['contenttype'=>'image/jpeg'],
		'svg' => ['contenttype'=>'image/svg+xml'],
		'pdf' => ['contenttype'=>'application/pdf'],
		'woff2' => ['contenttype'=>'font/woff2'],
		'ico' => ['contenttype'=>'image/x-icon']
	);

	

	#[\Override]
	public function Serve(string $requestedparameter) : string {
		if ($requestedparameter=='favicon.ico') {
			if (array_key_exists('FAVICON_URLPATH', Configuration::$APP_CONFIG)) {
				$requestedparameter = Configuration::$APP_CONFIG['FAVICON_URLPATH'];
			} else {
				$requestedparameter = 'assets/favicon.ico';
			}
		}
		
		$assetfile = implode('/', [__ROOT_DIR__, $requestedparameter]);
		$extension = pathinfo($assetfile, PATHINFO_EXTENSION);
		if (!array_key_exists($extension, self::ALLOWED_EXTENSIONS)) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 403 Not Allowed', true, 403);
			die("<h1>403 - Tidak diperbolehkan untuk diakses</h1>");
		}

		if (!is_file($assetfile)) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
			die("<h1>404 - Asset tidak ditemukan</h1>");
		}

		ob_start();
		header("Content-type: " . self::ALLOWED_EXTENSIONS[$extension]['contenttype']);
		header('Content-Length: ' . filesize($assetfile));
		readfile($assetfile);	
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}