<?php
class SampleController extends AppController
{

	var $naem = "Sample";
	var $uses = array();

	var $components = array('YahooTextAnalysis');

	function beforeFilter() {
		$this->YahooTextAnalysis->appId = "develperのAPIキーをここで設定";
	}

	function index() {
		// キーワードを抽出
		$str = "AKB48が2月16日にリリースした新曲「桜の木になろう」";
		$result = $this->YahooTextAnalysis->jpKeyphrase($str);
		var_dump($result);
	}
}