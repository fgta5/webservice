<?php namespace Fgta5\Webservice\Tester;

class query {

	public static function main() : void {

		try {
			echo "Test Query\r\n";
			echo "==========\r\n";
		
			$data = [
				'id' => '1234',
				'nama' => 'agung',
				'alamat' => 'jakarta',
				'nik' => '12345'
			];
			$query = Query::create('mst_session', $data, ['id']);
			$query->setQuote('`', '`');
			$query->generateSQL_Select();
			$sql = $query->getSQL();
			$params = $query->getParameter();
			$keys = $query->getKeys();
			$keysvalue = $query->getKeysValue();
		
			print_r($sql);
			print_r($params);
			print_r($keys);
			print_r($keysvalue);
			echo "\r\n\r\n";
		
		} catch (\Exception $ex) {
			echo "ERROR:\r\n";
			echo $ex->getMessage();
		} finally {
			echo "\r\n\r\n";
		}
		
	}

}
