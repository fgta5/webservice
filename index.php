<?php 
define('__ROOT_DIR__', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use Fgta5\Webservice\Configuration;
use Fgta5\Webservice\CurrentState;
use Fgta5\Webservice\Router;

// baca konfiguration
$isdebug = (bool)getenv('DEBUG');
$conffile = $isdebug ? '/app-config-debug.php' : '/app-config.php';
Configuration::Read(__DIR__ . $conffile);


// setiap request akan diubah di htaccess, dilewatkan melalui parameter urlreq
$urlrequeststring = array_key_exists('urlreq', $_GET) ? $_GET['urlreq'] : Router::REQUEST_DEFAULT; 
CurrentState::Begin($urlrequeststring);

// tambahkan route, apabila tidak ada, akan default di route ke page
Router::Add('api', ['classname'=>'Fgta5\Webservice\ApiService']);	   // outputnya berupa json
Router::Add('asset', ['classname'=>'Fgta5\Webservice\AssetService']);  // outputnya berupa image, javascript, css, dll
Router::Add('page', ['classname'=>'Fgta5\Webservice\PageService']);	   // outputnya berupa halaman html

$output = Router::Route(CurrentState::getHttpRequest());

CurrentState::End($output);
