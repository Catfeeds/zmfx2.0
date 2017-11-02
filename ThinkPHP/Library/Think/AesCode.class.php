<?php
/**
 * aes
 * +
 * -
 * zhy	find404@foxmail.com
 * 2017年2月21日 15:14:29
 */
namespace Think;
class AesCode{

	const AESCODE_IV 	= '1234567891234567'; 
	const AESCODE_KEY 	= '0123456789abcdef';



    static public  function encrypt($str){
		$td = mcrypt_module_open('rijndael-128','','cbc',self::AESCODE_IV);
		mcrypt_generic_init($td,self::AESCODE_KEY,self::AESCODE_IV);
		$encrypted = mcrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return bin2hex($encrypted);
    }

    static public function decrypt($code){
		$code = $this->hex2bin($code);
		$td = mcrypt_module_open('rijndael-128','','cbc',self::AESCODE_IV);
		mcrypt_generic_init($td,self::AESCODE_KEY,self::AESCODE_IV);
		$decrypted = mdecrypt_generic($td,$code);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return utf8_encode(trim($decrypted));
    }

	protected function hex2bin($hexdata) {
		$bindata = '';
		for ($i = 0; $i < strlen($hexdata); $i += 2) {
			$bindata .= chr(hexdec(substr($hexdata, $i, 2)));
		}
		return $bindata;
	}

}

?>