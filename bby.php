<?php
//ini_set("display_errors","on");
//======================================================
//					百宝云PHP类
//										-红船作品
//	哈希算法：	全部支持
//	加密算法：	部分支持（AES/DES/BlowFish/RSA）
//	编码算法：	全部支持
//	通讯接口：	部分支持（GET/POST/GET下载/POST上传）
//------------------------------------------------------
//	更新日志：
//	v0.1 b170117
//		[发布]Alpha版本
//	v0.2 b170129
//		[优化]加解密函数逻辑结构
//		[修复]文件加解密模式与百宝云不对应
//	v0.3 b170130
//		[新增]bby::get/post函数发送字符串
//	v1.0 b170213
//		[新增]bbyApp类和例程
//		[新增]bby::upload函数timeout参数
//		[新增]bby::download函数result参数
//		[新增]bby::upload函数以变量方式上传文件
//		[优化]加解密加解密函数报警告的问题
//		[优化]加解密函数执行效率
//	v1.1 b170318
//		[修复]bbyApp类构造函数报警告
//		[修复]加解密/哈希函数大小写错误(感谢:kuai)
//		[修复]bbyApp类调用加解密成员函数报错
//		[优化]加解密函数向下兼容性
//=======================================================

trait bbyComm
{
	//http://php.net/manual/zh/context.http.php
	static private function _request($url, $options, &$response_headers = []){
		$context = stream_context_create($options);
		@$result = file_get_contents($url, false, $context);
		if($result === false) return false;
		$response_header = [];
		foreach ($http_response_header as $header) {
			$header = explode(':', $header);
			@$response_headers[$header[0]] = $header[1];
		}
		return $result;
	}
	static public function get($token, $data, $timeout = 15){
		if(is_array($data)){
			$data_encoded = http_build_query($data);
			$url = 'http://get.baibaoyun.com/api/'.$token.'?'.$data_encoded;
		}
		else{
			$data_encoded = urlencode($data);
			$url = 'http://get.baibaoyun.com/cloudapi/cloudappapi?token='.$token.'&funparams='.$data_encoded;
		}
		$options = array(
			'http' => array(
				'method' => 'GET',
				'timeout' => $timeout
			)
		);
		return self::_request($url, $options);
	}
	static public function post($token, $data, $timeout = 15){
		if(is_array($data)){
			$data_encoded = json_encode($data);
			$url = 'http://post.baibaoyun.com/api/'.$token;
		}
		else{
			$data_encoded = 'token='.$token.'&funparams='.urlencode($data);
			$url = 'http://post.baibaoyun.com/cloudapi/cloudappapi';
		}
		$options = array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/x-www-form-urlencoded',
				'content' => $data_encoded,
				'timeout' => $timeout
			)
		);
		return self::_request($url, $options);
	}
	static public function download($token, $file, $path = '', &$result = [], $timeout = 60){
		$data = array('access_token'=>$token, 'method'=>'download', 'filename'=>$file, 'path'=>$path);
		$data_encoded = http_build_query($data);
		$url = 'http://apicloudupload.baibaoyun.com/cloudupload.php?'.$data_encoded;
		$options = array(
			'http' => array(
				'method' => 'GET',
				'timeout' => $timeout
			)
		);
		$file = self::_request($url, $options, $headers);
		if($file === false) return false;
		$result['msg'] = urldecode($headers['msg']);
		$result['result'] = (int)$headers['result'];
		if($result['result'] != 0) return false;
		return $file;
	}
	static public function upload($token, $file, $path = '', $ondup = 'overwrite', $timeout = 60){
		if(!extension_loaded('cURL')){
			return false;
		}
		if(is_array($file)){
			$file_key = "file\"; filename=\"{$file[0]}\nContent-Type: application/octet-stream";
			$file_value = $file[1];
		}else{
			$file_key = 'file';
			$file_value = new CURLFile(realpath($file));
		}
		$data = array(
			'access_token' => $token,
			'method' => 'upload',
			$file_key => $file_value,
			'path' => $path,
			'ondup' => $ondup
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://apicloudupload.baibaoyun.com/cloudupload.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}

trait bbyAlgoBase
{
	//base hash function
	static public function hash($algo, $str){
		return strtoupper(hash($algo, $str));
	}
	
	//base crypt functions for string
	static private function _encrypt($str, $pass, $algo)	{
		$data = self::_encryptBinary($str, $pass, $algo);
		if($data === false) return false;
		$res = bin2hex($data);
		$res = strtoupper($res);
		return $res;
	}
	static private function _decrypt($str, $pass, $algo)	{
		$data = pack("H*", $str);
		$res = self::_decryptBinary($data, $pass, $algo);
		if($res === false) return false;
		return $res;
	}
	
	//base crypt functions for file
	static private function _encryptFile($src_file, $file, $pass, $algo)	{
		if(($src = file_get_contents($src_file)) === false) return false;
		if(($data = self::_encryptBinary($src, $pass, $algo)) === false) return false;
		return file_put_contents($file, $data, LOCK_EX);
	}
	static private function _decryptFile($src_file, $file, $pass, $algo)	{
		if(($src = file_get_contents($src_file)) === false) return false;
		if(($data = self::_decryptBinary($src, $pass, $algo)) === false) return false;
		return file_put_contents($file, $data, LOCK_EX);
	}
	
	//base crypt functions
	static private function CRYPT_IV(){
		return "\x1A\x54\x43\x42\x42\x59\x5A\x00\x00\x00\x00\x00\x00\x00\x00\x00";
	}
	static private function _encryptBinary($data, $pass, $algo)	{
		if(!extension_loaded('OpenSSL')){
			return false;
		}
		$data = openssl_encrypt($data, $algo, $pass, OPENSSL_RAW_DATA, self::CRYPT_IV());
		return $data;
	}
	static private function _decryptBinary($data, $pass, $algo)	{
		if(!extension_loaded('OpenSSL')){
			return false;
		}
		$data = openssl_decrypt($data, $algo, $pass, OPENSSL_RAW_DATA, self::CRYPT_IV());
		return $data;
	}
}

trait bbyAlgo
{
	use bbyAlgoBase;
	//hash algos for string
	static public function md5($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function sha1($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function sha224($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function sha256($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function sha384($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function sha512($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function crc32($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function adler32($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function ripemd128($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function ripemd160($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function ripemd256($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function ripemd320($str)	{
		return self::hash(__FUNCTION__, $str);
	}
	static public function tiger($str)	{
		return self::hash('tiger192,3', $str);
	}
	
	//hash algos for file
	static public function md5file($file)	{
		return self::md5(file_get_contents($file));
	}
	static public function sha1file($file)	{
		return self::sha1(file_get_contents($file));
	}
	static public function sha224file($file)	{
		return self::sha224(file_get_contents($file));
	}
	static public function sha256file($file)	{
		return self::sha256(file_get_contents($file));
	}
	static public function sha384file($file)	{
		return self::sha384(file_get_contents($file));
	}
	static public function sha512file($file)	{
		return self::sha512(file_get_contents($file));
	}
	static public function crc32file($file)	{
		return self::crc32(file_get_contents($file));
	}
	static public function adler32file($file)	{
		return self::adler32(file_get_contents($file));
	}
	static public function ripemd128file($file)	{
		return self::ripemd128(file_get_contents($file));
	}
	static public function ripemd160file($file)	{
		return self::ripemd160(file_get_contents($file));
	}
	static public function ripemd256file($file)	{
		return self::ripemd256(file_get_contents($file));
	}
	static public function ripemd320file($file)	{
		return self::ripemd320(file_get_contents($file));
	}
	static public function tigerfile($file)	{
		return self::tiger(file_get_contents($file));
	}
	
	//crypt algos for string
	static public function aesencrypt($str, $pass)	{
		return self::_encrypt($str, $pass, 'AES-128-CBC');
	}
	static public function aesdecrypt($str, $pass)	{
		return self::_decrypt($str, $pass, 'AES-128-CBC');
	}
	static public function desencrypt($str, $pass)	{
		return self::_encrypt($str, $pass, 'DES-CBC');
	}
	static public function desdecrypt($str, $pass)	{
		return self::_decrypt($str, $pass, 'DES-CBC');
	}
	static public function blowfishencrypt($str, $pass)	{
		return self::_encrypt($str, $pass, 'BF-CBC');
	}
	static public function blowfishdecrypt($str, $pass)	{
		return self::_decrypt($str, $pass, 'BF-CBC');
	}
	static public function rsaencrypt($str, $pass)	{
		if(!extension_loaded('OpenSSL')){
			return false;
		}
		$key = base64_encode(pack('H*', $pass));//rtrim($key,'==').'AB';
		$key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
		$pubkey = openssl_pkey_get_public($key);
		if(!$pubkey) {
			return false;
		}
		$data = '';
		if(!openssl_public_encrypt($str, $data, $pubkey, OPENSSL_PKCS1_OAEP_PADDING)){
			return false;
		}
		openssl_pkey_free($pubkey);
		$data = unpack("H*",$data);
		return current($data);
	}
	static public function rsadecrypt($str, $pass)	{
		if(!extension_loaded('OpenSSL')){
			return false;
		}
		$key = base64_encode(pack('H*', $pass));
		$key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
		$prikey = openssl_pkey_get_private($key);
		if(!$prikey) {
			return false;
		}
		$data = '';
		$str = pack('H*', $str);
		if(!openssl_private_decrypt($str, $data, $prikey, OPENSSL_PKCS1_OAEP_PADDING)){
			return false;
		}
		openssl_pkey_free($prikey);
		return $data;
	}
	
	//crypt algos for file
	static public function aesencryptfile($src, $dest, $pass)	{
		return self::_encryptFile($src, $dest, $pass, 'AES-128-CBC');
	}
	static public function aesdecryptfile($src, $dest, $pass)	{
		return self::_decryptFile($src, $dest, $pass, 'AES-128-CBC');
	}
	static public function desencryptfile($src, $dest, $pass)	{
		return self::_encryptFile($src, $dest, $pass, 'DES-CFB');
	}
	static public function desdecryptfile($src, $dest, $pass)	{
		return self::_decryptFile($src, $dest, $pass, 'DES-CFB');
	}
	static public function blowfishencryptfile($src, $dest, $pass)	{
		return self::_encryptFile($src, $dest, $pass, 'BF-CFB');
	}
	static public function blowfishdecryptfile($src, $dest, $pass)	{
		return self::_decryptFile($src, $dest, $pass, 'BF-CFB');
	}
	
	//encode and decode for string
	static public function base64encode($str)	{
		return base64_encode($str);
	}
	static public function base64decode($str)	{
		return base64_decode($str);
	}
	
	//encode and decode for file
	static public function base64encodefile($file)	{
		return base64_encode(file_get_contents($file));
	}
	static public function base64decodefile($str, $file)	{
		return file_put_contents($file, base64_decode($str), LOCK_EX);
	}
}

class bby
{
	use bbyAlgo;
	use bbyComm;
}

class bbyAdapter
{
	public $error = '';
	public function _recv($data, $method){
		if(!is_null($json = json_decode($data, true))) $data = $json;
		return $data;
	}
	public function _send($result, $method){
		return $result;
	}
}

class bbyApp
{
	use bbyAlgo;
	
	public $token	= '';		//云应用Token
	public $timeout	= 0;		//全局超时
	private $adapter = null;	//请求Adapter实例
	
	public $result	= null;		//最后一次请求返回的结果（经过Adapter处理）
	public $response = null;	//最后一次请求返回的原始内容（包括错误信息）
	
	function __construct($token = '', $timeout = 20, bbyAdapter $adapter = null){
		$this->token = $token;
		$this->timeout = $timeout;
		if(is_null($adapter))
			$adapter = new bbyAdapter();
		$this->adapter = $adapter;
	}
	
	public function setAdapter(bbyAdapter $adapter){
		$this->adapter = $adapter;
	}
	
	public function request($data, $method = 'post'){
		$method = strtolower($method);
		switch($method){
			case 'post': break;
			case 'get': break;
			default:
			return false;
		}
		$data = $this->adapter->_send($data, $method);
		if($data === false){
			$this->setError($this->adapter->error);
			return false;
		}
		$this->response = bby::$method($this->token, $data, $this->timeout);
		$json = json_decode($this->response, true);
		if(is_array($json) && array_key_exists('errCode', $json) && array_key_exists('errMsg', $json)){
			$this->setError($json['errMsg']);
			$this->result = false;
		}else{
			$this->result = $this->adapter->_recv($this->response, $method);
			if($this->result === false){
				$this->setError($this->adapter->error);
				return false;
			}
		}
		return $this->result;
	}
	
	public function download($remote_file, $local_file = ''){
		setlocale(LC_ALL, 'zh_CN.GBK');
		$file_content = bby::download($this->token, basename($remote_file), dirname($remote_file), $res, $this->timeout);
		setlocale(LC_ALL, '');
		if($file_content === false) return false;
		if($res['result'] == 0) return $local_file == '' ? $file_content : file_put_contents($local_file, $file_content, LOCK_EX);
		$this->setError($res['msg']);
		return false;
	}
	
	public $upload_ondup = 'overwrite';//newcopy
	public function upload($local_file, $remote_file = '', $ondup = ''){
		if(($local_file_content = file_get_contents($local_file)) === false) return false;
		if($ondup == '') $ondup = $this->upload_ondup;
		setlocale(LC_ALL, 'zh_CN.GBK');
		if(($remote_file_name = basename($remote_file)) == '')
			$remote_file_name = $local_file;
		elseif(($remote_file = dirname($remote_file)) === '.')
			$remote_file = '';
		setlocale(LC_ALL, '');
		$res = bby::upload($this->token, [$remote_file_name, $local_file_content], $remote_file, $ondup, $this->timeout);
		$res = json_decode($res, true);
		if(is_null($res)) return false;
		if($res['result'] == 0) return $res['path'];
		$this->setError($res['msg']);
		return false;
	}
	
	private $last_error = '';
	private function setError($error)	{ $this->last_error = $error; }
	public function getError()			{ return $this->last_error; }
}