<?php
/**
 * 银联支付类
 * 所有银联支付的功能集成在这个类里
 * @todo 还没有完成
 * @author adcbguo
 */
namespace Common\Controller;
class pay{
	
	private $orderId;//订单id
	private $config;//银联支付配置
	
	//初始化配置，获取订单信息，获取配置信息
	public function __construct($orderId){
		dump('aaa');
	}
	
	//对外部支付方法
	public function Pay(){
		// 初始化日志
		$params = array(
				'version' => '5.0.0',				//版本号
				'encoding' => 'utf-8',				//编码方式
				'certId' => $this->getSignCertId (),			//证书ID
				'txnType' => '01',				//交易类型
				'txnSubType' => '01',				//交易子类
				'bizType' => '000201',				//业务类型
				'frontUrl' =>  SDK_FRONT_NOTIFY_URL,  		//前台通知地址
				'backUrl' => SDK_BACK_NOTIFY_URL,		//后台通知地址
				'signMethod' => '01',		//签名方法
				'channelType' => '08',		//渠道类型，07-PC，08-手机
				'accessType' => '0',		//接入类型
				'merId' => '777290058115092',		        //商户代码，请改自己的测试商户号
				'orderId' => date('YmdHis'),	//商户订单号
				'txnTime' => date('YmdHis'),	//订单发送时间
				'txnAmt' => '100',		//交易金额，单位分
				'currencyCode' => '156',	//交易币种
				'defaultPayType' => '0001',	//默认支付方式
				//'orderDesc' => '订单描述',  //订单描述，网关支付和wap支付暂时不起作用
				'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		);
		// 签名
		$this->sign ( $params );
		// 前台请求地址
		$front_uri = SDK_FRONT_TRANS_URL;
		F('pay',"前台请求地址为>" . $front_uri);
		// 构造 自动提交的表单
		$html_form = $this->create_html ( $params, $front_uri );
		F('pay',$html_form);
		echo $html_form;
	}
	
	/**
	 * 构造自动提交表单
	 *
	 * @param array $params
	 * @param unknown_type $action
	 * @return string
	 */
	protected function create_html($params, $action) {
		$encodeType = isset ( $params ['encoding'] ) ? $params ['encoding'] : 'UTF-8';
		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset='.$encodeType.'" /></head>'+
				'<body  onload="javascript:document.pay_form.submit();">'+
				'<form id="pay_form" name="pay_form" action="'.$action.'" method="post">';
		foreach ( $params as $key => $value ) {
			$html .= '<input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$value.'" />';
		}
		$html .= '<input type="submit" type="hidden"></form></body></html>';
		return $html;
	}
	
	/**
	 * 数组 排序后转化为字体串
	 *
	 * @param array $params
	 * @return string
	 */
	protected function coverParamsToString($params) {
		$sign_str = '';
		// 排序
		ksort ( $params );
		foreach ( $params as $key => $val ) {
			if ($key == 'signature') { continue;}
			$sign_str .= sprintf ( "%s=%s&", $key, $val );
		}
		return substr ( $sign_str, 0, strlen ( $sign_str ) - 1 );
	}
	
	/**
	 * 签名
	 * @param String $params_str
	 */
	protected function sign(&$params){
		global $log;
		$log->LogInfo ( '=====签名报文开始======' );
		if(isset($params['transTempUrl'])){
			unset($params['transTempUrl']);
		}
		// 转换成key=val&串
		$params_str = $this->coverParamsToString ( $params );
		$log->LogInfo ( "签名key=val&...串 >" . $params_str );
		
		$params_sha1x16 = sha1 ( $params_str, FALSE );
		$log->LogInfo ( "摘要sha1x16 >" . $params_sha1x16 );
		// 签名证书路径
		$cert_path = SDK_SIGN_CERT_PATH;
		$private_key = $this->getPrivateKey ( $cert_path );
		// 签名
		$sign_falg = openssl_sign ( $params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1 );
		if ($sign_falg) {
			$signature_base64 = base64_encode ( $signature );
			$log->LogInfo ( "签名串为 >" . $signature_base64 );
			$params ['signature'] = $signature_base64;
		} else {
			$log->LogInfo ( ">>>>>签名失败<<<<<<<" );
		}
		$log->LogInfo ( '=====签名报文结束======' );
	}
	
	/**
	 * 返回(签名)证书私钥 
	 * @return unknown
	 */
	function getPrivateKey($cert_path) {
		$pkcs12 = file_get_contents ( $cert_path );
		openssl_pkcs12_read ( $pkcs12, $certs, SDK_SIGN_CERT_PWD );
		return $certs ['pkey'];
	}

	//获取证书id
	protected function getSignCertId(){
		return $this->getCertId ( SDK_SIGN_CERT_PATH );// 签名证书路径
	}
	
	/**
	 * 取证书ID(.pfx)
	 * @return unknown
	 */
	protected function getCertId($cert_path) {
		$pkcs12certdata = file_get_contents ( $cert_path );
		openssl_pkcs12_read ( $pkcs12certdata, $certs, SDK_SIGN_CERT_PWD );
		$x509data = $certs ['cert'];
		openssl_x509_read ( $x509data );
		$certdata = openssl_x509_parse ( $x509data );
		$cert_id = $certdata ['serialNumber'];
		return $cert_id;
	}
}