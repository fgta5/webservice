<?php namespace Fgta5\Webservice\Pages;

use Fgta5\Webservice\Configuration;
use Fgta5\Webservice\Page;
use Fgta5\Webservice\Template;


class ContentPage extends Page {


	private string $_contentbody; 
	private string $_contentdir;
	private array $_contentsetting;
	private string $_lasterror;

	#[\Override]
	public function Load(array &$pagedata) : void {
		$pageparameter = $pagedata['pageparameter'];

		$contentbody = $this->getContentData($pageparameter);
		if ($contentbody==null) {
			$this->_contentbody = "Content not found!";
		} else {
			$this->_contentbody = $contentbody;
		}

	}
	
	public function getContentBody() : string {
		return $this->_contentbody;
	}

	public function getContentData($id) : ?string {
		return $this->getContentDataFromFS($id);
	}

	public function getContentDataFromFS($id) : ?string {
		$datadir = Configuration::getAppConfig('CONTENT_DATA_DIR');
		if (!is_dir($datadir)) {
		 	Debug::die("Direktory '$datadir' tidak ditemukan!");
		}


		try {

			$this->_contentdir = implode('/', [$datadir, $id]);
			if (!is_dir($this->_contentdir)) {
				throw new \Exception("direktori '" . $this->_contentdir . "' tidak ditemukan!");
			}
	
			$contentsetting = implode('/', [$this->_contentdir, 'contentsetting.php']);
			if (is_file($contentsetting)) {
				require_once $contentsetting;
				if (isset($CONTENT_SETTING)) {
					$this->_contentsetting = $CONTENT_SETTING;
				} else {
					$this->_contentsetting = [];
				}
			}
	
			$contentdata = implode('/', [$this->_contentdir, 'contentdata.phtml']);
			if (!is_file($contentdata)) {
				throw new \Exception("contentdata tidak ditemukan di '" . $this->_contentdir . "'");
			}
	
			ob_start();
			require_once $contentdata;
			$output = ob_get_contents();
			ob_end_clean();

			return $output;
		} catch (\Exception $ex) {
			return "Content not found.<br>" . $ex->getMessage();
		}
		
	}



	public function getContentAssetUrl(string $assetpath) : string {
		$dirname = $this->_contentdir;
		return Template::mapAssetPathToUrl($assetpath, 	$dirname);
	}

	public function getContentSetting() : array {
		return $this->_contentsetting;
	}

}
