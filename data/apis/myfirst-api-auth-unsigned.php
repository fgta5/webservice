<?php 
/* myfirst-api-auth-unsigned
 * 
 * dipanggil dengan endpoint:   api/myfirst-api-test.php
 */

$API_CONFIG = [
	'classname' => 'Fgta5\\Webservice\\Apis\\ApiTest',
	'functionname' => 'myfirstapi',
	'authorized' => true,  // optional, default: true
	'signed' => false,  // optional, default: true
];