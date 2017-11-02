<?php
	
/**
 * 根据商家微信支付，模式2	
 * zhy	find404@foxmail.com
 * 2017年4月18日 15:02:50
 */

namespace Common\Model\Pay\WxPay;

class StartPay {

	//初始化配置信息
	function __construct(){
		
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if(!$agent_config){
			$this->error = "初始化分销商信息失败，分销商不存在";
			return false;
		}

		if($agent_config['wxappid']=='' || $agent_config['wxmchid']==''|| $agent_config['wxkey']==''|| $agent_config['wxappsecret']==''){
			$this->error = "初始化分销商信息失败，分销商未正确开通微信支付";
			return false;
		}

		$domain = $agent_config['domain'];
		//如果客户域名为主域名，则添加www
		if(substr_count($agent_config['domain'], '.')==1){
			$domain = 'www.'.$domain;
		}

		C('WXPAY_APPID',$agent_config['wxappid']);
		C('WXPAY_MCHID',$agent_config['wxmchid']);
		C('WXPAY_KEY',$agent_config['wxkey']);
		C('WXPAY_APPSECRET',$agent_config['wxappsecret']);
		C('WXPAY_NOTIFY_URL',"http://".$domain."/Home/Pay/notify_url");		
		C('WXPAY_RETURN_URL',"http://".$domain."/Home/Pay/return_url");
		C('WXPAY_CURL_PROXY_HOST',"0.0.0.0");
		C('WXPAY_CURL_PROXY_PORT',0);
		C('WXPAY_REPORT_LEVENL',1);
	}

 
	function GetPayCodeUrl($order){
		$out_trade_no=C('WXPAY_MCHID').date("YmdHis");
		$notify = new  NativePay();
		$input  = new  WxPayUnifiedOrder();
		$input->SetBody($order['order_sn']);
		$input->SetAttach($order['order_sn']);
		$input->SetOut_trade_no($out_trade_no);
		$input->SetTotal_fee($order['order_price']*100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetGoods_tag("test");
		$input->SetNotify_url(C('WXPAY_NOTIFY_URL'));
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id($order['order_id']);
		$result = $notify->GetPayUrl($input);
		return isset($result["code_url"]) ? array($result["code_url"],$out_trade_no) : null;
	}
	

	
	/**
	 根据回调信息对订单进行处理
	**/
	function WxOrderStatus($out_trade_no){
		$input = new WxPayOrderQuery();
		$input->SetOut_trade_no($out_trade_no);
		$data=Api::orderQuery($input);
		return isset($data) ? $data : null;
	}
	
	
 
	
}
?>