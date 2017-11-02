<?php
/**
 * DES算法加解密
 * Auth: YangChao
 * Date: 2016/3/4
 */
namespace Common\Model;

class Des{
	
	//校验KEY （八个字符內）
	private $key = 'ZfVoTmt4';
	
	/**
	 * PHP DES 加密程式
	 *
	 * @param $key 密钥（八个字符內）
	 * @param $encrypt 要加密的明文
	 * @return string 密文
	 */
	function encrypt($encrypt, $key='')
	{
		// 根据 PKCS#7 RFC 5652 Cryptographic Message Syntax (CMS) 修正 Message 加入 Padding
		if($key==''){
			$key = $this->key;
		}
		
		if(strlen($key)>8){ return ''; }
		
		$block = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);	
		$pad = $block - (strlen($encrypt) % $block);
		$encrypt .= str_repeat(chr($pad), $pad);
	 
		// 不需要设定 IV 进行加密
		$passcrypt = mcrypt_encrypt(MCRYPT_DES, $key, $encrypt, MCRYPT_MODE_ECB);
		//return bin2hex($passcrypt);
		return base64_encode($passcrypt);
	}
	 
	/**
	 * PHP DES 解密程式
	 *
	 * @param $key 密钥（八个字元內）
	 * @param $decrypt 要解密的密文
	 * @return string 明文
	 */
	function decrypt($decrypt, $key='')
	{
		if($key==''){
			$key = $this->key;
		}
		
		if(strlen($key)>8){ return ''; }
		
		// 不需要设定 IV
		//$str = mcrypt_decrypt(MCRYPT_DES, $key, $decrypt, MCRYPT_MODE_ECB);
		$str = mcrypt_decrypt(MCRYPT_DES, $key, base64_decode($decrypt), MCRYPT_MODE_ECB);
	 
		// 根据  PKCS#7 RFC 5652 Cryptographic Message Syntax (CMS) 修正 Message 移除 Padding
		$pad = ord($str[strlen($str) - 1]);
		return substr($str, 0, strlen($str) - $pad);
	}
}
?>