<?php
/**
 * Yahoo Web Apiの日本語形態素解析
 * http://developer.yahoo.co.jp/webapi/jlp/
 * @author polidog
 */
class YahooTextAnalysisComponent extends Object
{
	/**
	 * yahoo application key
	 * @var int
	 */
	var $appId = "";

	/**
	 * リクエスト時のオプション
	 * @var array
	 */
	var $options = array(
		'result'		=> '',
		'response'		=> '',
		'filter'		=> '',
		'ma_response'	=> '',
		'ma_filter'		=> '',
		'uniq_filter'	=> '',
		'uniq_by_baseform'	=> ''
	);
	
	var $baseUrl = "http://jlp.yahooapis.jp";
	
	/**
	 * リクエストを送る際のタイプを指定する
	 * @var string GET or POST
	 */
	var $requestMethod = "GET";
	
	/**
	 * HttpSocket instance
	 * @var HttpSocket
	 */
	var $httpSocket = null;
	
	function initialize(&$controller) {
		
		// 関数のオーバーロード機能を使用しない
		$func_overload = ini_get('mbstring.func_overload');
		if ( $func_overload !== false ) {
			$func_overload = (int) $func_overload;
			if ( $func_overload > 0 ) {
				ini_set('mbstring.func_overload', 0);
			}
		}
	}
	
	/**
	 * 日本語要素解析
	 * @param string $str
	 * @return string (XML)
	 * 
	 * @see http://developer.yahoo.co.jp/webapi/jlp/ma/v1/parse.html
	 */
	function jpTextAnalysis($str,$options = array()) {
		//　文字のサイズ制限
		if ( !$this->_str_byte_check($str) ) {
			return false;
		}
		return $this->_callApi("/MAService/V1/parse", $this->_parameter($str,$options));
	}
	
	/**
	 * かな漢字変換
	 * @param string $str
	 * @param array $options
	 * @return string
	 * 
	 * @see http://developer.yahoo.co.jp/webapi/jlp/jim/v1/conversion.html
	 */
	function jpKana2Kanji($str,$options = array()) {
		//　文字のサイズ制限 10KB
		if ( !$this->_str_byte_check($str,1024 * 10) ) {
			return false;
		}
		return $this->_callApi('/JIMService/V1/conversion',$this->_parameter($str, $options));
	}
	
	/**
	 * ふりがなをつける
	 * @param string $str
	 * @param array $options
	 * @return string
	 * 
	 * @see http://developer.yahoo.co.jp/webapi/jlp/furigana/v1/furigana.html
	 */
	function jpFurigana($str,$options = array()) {
		//　文字のサイズ制限 10KB
		if ( !$this->_str_byte_check($str) ) {
			return false;
		}
		return $this->_callApi('/FuriganaService/V1/furigana',$this->_parameter($str, $options));
	}
	
	/**
	 * 校正支援
	 * @param string $str
	 * @param array $options
	 * @return string
	 * 
	 * @see http://developer.yahoo.co.jp/webapi/jlp/kousei/v1/kousei.html
	 */
	function jpKousei($str, $options = array() ) {
		//　文字のサイズ制限 10KB
		if ( !$this->_str_byte_check($str) ) {
			return false;
		}
		return $this->_callApi('/KouseiService/V1/kousei',$this->_parameter($str, $options));
	}
	
	/**
	 * 日本語係り受け解析
	 * @param string $str
	 * @param array $options
	 * @return string
	 * 
	 * @see http://developer.yahoo.co.jp/webapi/jlp/da/v1/parse.html
	 */
	function jpDependency($str,$options = array() ) {
		//　文字のサイズ制限 4KB
		if ( !$this->_str_byte_check($str,1024 * 4) ) {
			return false;
		}
		return $this->_callApi('/DAService/V1/parse',$this->_parameter($str, $options));
	}
	
	/**
	 * キーワードを抽出する
	 * @param string $str
	 * @param array $options
	 * @return mixed 
	 * 
	 * @see http://developer.yahoo.co.jp/webapi/jlp/keyphrase/v1/extract.html
	 */
	function jpKeyphrase($str, $options = array()) {
		//　文字のサイズ制限
		if ( !$this->_str_byte_check($str) ) {
			return false;
		}
		return $this->_callApi("/KeyphraseService/V1/extract", $this->_parameter($str,$options));	
	}
	
	/**
	 * yahooのAPIをコールする
	 * @param string $apiPath
	 * @param array $parameter
	 * @return string 
	 * 
	 * @access private
	 */
	function _callApi($apiPath,$parameter) {
		if ( empty($this->appId) ) {
			return false;
		}
		
		if ( !$this->_load_http_socket() ) {
			return false;
		}
		$method = strtolower($this->requestMethod);
		$result = $this->httpSocket->$method($this->baseUrl.$apiPath, $parameter);
		return $result;
	}
	
	/**
	 * パラメータを生成する
	 * @param string $str
	 * @param array $options
	 * @return array
	 * 
	 * @access private
	 */
	function _parameter($str,$options) {
		$parameter = array();
		$options = array_merge($options,$this->options);
		
		foreach( $options as $key => $value ) {
			if ( !empty($value) ) {
				$parameter[$key] = $value;
			}
		}
		$parameter['appid']		= $this->appId;
		$parameter['sentence']	= $str;
		return $parameter;
	}

	/**
	 * cakePHPのHttpSocketオブジェクトを生成する
	 * @access private
	 */
	function _load_http_socket() {
		if ( $this->httpSocket instanceof HttpSocket ) {
			return true;
		}
		
		$result = false;
		if ( !class_exists('HttpSocket') ) {
			$result = App::import('Core','HttpSocket');	
			if ( $result ) {
				$this->httpSocket = new HttpSocket();
			}
		}
		return $result;
	}
	
	/**
	 * 文字のバイト数チェック
	 * @param string $str
	 * @param int $maxbyte デフォルトは100KB
	 * @return boolean
	 * 
	 * @access private
	 */
	function _str_byte_check($str,$maxbyte = 102400 ) {
		$data = strlen($str);
		if ( $data > $maxbyte ) {
			return false;
		}
		return true;
	}
}