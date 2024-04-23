<?php namespace Fgta5\Webservice\Apis;

use Fgta5\Webservice\Api;

class ApiTest extends Api {

	public function myfirstapi(string $requestedparameter, array $postdata, array $postheaders) : array {
		try {


			return [
				'status' => 'success'
			];
		} catch (\Exception $ex) {
			throw $ex;
		}
	}

}