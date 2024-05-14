<?php

date_default_timezone_set('Asia/Jakarta');

use Fgta5\Webservice\Configuration;

const DB_PARAM = [
	\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
	\PDO::ATTR_PERSISTENT=>true			
];

Configuration::$APP_CONFIG = [
	'BASEADDRESS' => 'http://localhost:8000',
	'PAGE_DATA_DIR' => __ROOT_DIR__ . '/data/pages',
	'API_DATA_DIR' => __ROOT_DIR__ . '/data/apis',
	'CONTENT_DATA_DIR' => __ROOT_DIR__ . '/data/contents',

	'FAVICON_URLPATH' => 'data/assets/ferrineicon.ico',

	'LOGIN_PAGE' => 'page/login',
	'TOKEN_API_MAX_LIFETIME' => 60, 		// 1 menit
	'TOKEN_PAGE_MAX_LIFETIME' => 2592000,	// 30 hari

	'TEMPLATES' => [
		'DEFAULT' => __ROOT_DIR__ .'/data/templates/fgta5_webservice_v1',
	],

	'LIB_FGTA5WEBSERVICE_DIR' => __ROOT_DIR__ .'/src',
	'DB' => [
		'FGTAMAIN' => [
			'DSN' => "mysql:host=mariadblocal;dbname=kalistadblocal",
			'user' => "root",
			'pass' => "",
			'param' => DB_PARAM			
		],
		'E_FRM2_MGP' => [],
		'E_FRM2_BACKUP' => [],
	],

	'APP_ID' => 'TFIWEB',
	'APP_PRIVATE_KEY' => '', // defined later in app-keypair
	'APP_PUBLIC_KEY' => '', // defined later in app-keypair

];

Configuration::$ACTIVE_CONFIG = [
	'MAINDB' => 'DB/FGTAMAIN',
	'TEMPLATE' => 'TEMPLATES/DEFAULT'
];

require_once __DIR__ . '/app-keypair-debug.php';
