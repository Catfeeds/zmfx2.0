<?php
/**
 * 在线支付控制器
 */
namespace Home\Controller;

Vendor('IPSPay.IPSPay');
use Vendor\IPSPay\IPSPay;
use Vendor\IPSPay\IpsPayRequest;
use Vendor\IPSPay\IpsPayVerify;
use Vendor\IPSPay\IpsPayNotify;


class PayController extends HomeController{
	
	//在线支付-支付宝订单请求
	public function online(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误');
		}
		$alipay = new \Common\Model\Alipay();
		$html_text = $alipay->request($order_id);
		echo $html_text;
		//dump($alipay);
	}

    /**
     * 在线支付-环迅支付请求
     */
    public function ipsPay()
    {
        $order_id = I('order_id', 0);
        if($order_id <= 0){
            $this->error('传入订单ID错误');
        }

        // 检查分销商信息
        $agent = new \Common\Model\AgentModel();
        if($agent->init()){
            $agentInfo = $agent->getAgent();
        }else{
            $this->error($agent->error);
        }

        //获取订单信息
        $order = new \Common\Model\OrderModel();
        $orderDetail = $order->getOrderDetail($order_id);

        if(!empty($orderDetail) && is_array($orderDetail)){
            $data = array(
                'merBillNo' => $orderDetail['merBillNo'],
                'amount' => $orderDetail['amount'], //0.1
                'goodsName' => $agentInfo['agent_name'].'订单'.$orderDetail['merBillNo'].'付款',
                'merchantUrl' => 'http://'.$agentInfo['domain'].'/Home/Pay/ipsResponse',
                'serverUrl' => 'http://'.$agentInfo['domain'].'/Home/Pay/ipsResponseS2S',
                'date' => $orderDetail['orderDate']
            );
            $ipsPay = new IPSPay($agentInfo['ips_mer_code'], $agentInfo['ips_mer_cert'], $agentInfo['ips_account']);
            $ipsPay->setParams($data);
            $ipsPay->submit();
        }else{
            $this->error('订单信息有误');
        }
	}
	
	/**
	 * 在线支付-环迅扫码支付请求
	 */
	public function ipsPayCode($gatewayType=10){
	    C('LAYOUT_ON',false);
	    $order_id = I('order_id', 0);
	    if($order_id <= 0){
	        $this->error('传入订单ID错误');
	    }
	    
	    // 检查分销商信息
	    $agent = new \Common\Model\AgentModel();
	    if($agent->init()){
	        $agentInfo = $agent->getAgent();
	    }else{
	        $this->error($agent->error);
	    }
	    
	    //获取订单信息
	    $order = new \Common\Model\OrderModel();
	    $orderDetail = $order->getOrderDetail($order_id);
	    
	    if(!empty($orderDetail) && is_array($orderDetail)){
	        $ipspay_config['Version']	 = 'v1.0.0';
	        //商戶号
	        $ipspay_config['MerCode']	 = $agentInfo['ips_mer_code'];
	        //交易账户号
	        $ipspay_config['Account']	 = $agentInfo['ips_account'];
	        //商戶证书
	        $ipspay_config['MerCert']	 = $agentInfo['ips_mer_cert'];
	        //请求地址
	        $ipspay_config['PostUrl']	 = 'https://thumbpay.e-years.com/psfp-webscan/services/scan?wsdl';
	        
	        //构造要请求的参数数组
	        $parameter = array(	   
	            "MsgId"        => time(),
	            "ReqDate"	    => date("YmdHis"),
	            "MerCode"	    => $agentInfo['ips_mer_code'],	            
	            "Account"	    =>$agentInfo['ips_account'],
	            "MerBillNo"	    => $orderDetail['merBillNo'],
	            "GatewayType"	    => $gatewayType,
	            "Date"	        => $orderDetail['orderDate'],
	            "RetEncodeType"	        => '17',
	            "CurrencyType"	        => '156',
	            "Amount"	    =>$orderDetail['amount'],	            
	            "GoodsName"	    => '环迅',
	            "ServerUrl"	    => 'http://'.$agentInfo['domain'].'/Home/Pay/ipsPayCodes2snotify_url',//"http://demo.btbzm.com/payipstest/s2snotify_url.php",//
	            "Lang"          =>'GB',
	            "Attach"        =>'商户数据',
	            "BillEXP"      =>'2'
	        );
	        //建立请求
	        $ipspayRequest = new IpsPayRequest($ipspay_config);
	        $html_text = $ipspayRequest->buildRequest($parameter);
	        //dump($html_text);exit();
	        $xmlResult = new \SimpleXMLElement($html_text);
	        $strRspCode = $xmlResult->GateWayRsp->head->RspCode;	       
	        if($strRspCode == "000000")
	        {
	            //返回报文验签
	            $ipspayVerify = new IpsPayVerify($ipspay_config);
	            $verify_result = $ipspayVerify->verifyReturn($html_text);	            
	            
	            // 验证成功
	            if ($verify_result) {
	                $strQrCodeUrl = $xmlResult->GateWayRsp->body->QrCode;	                
	                $this->assign("orderNo",$orderDetail['merBillNo']);
	                $this->assign("orderAmount",$orderDetail['amount']);
	                
	                if($gatewayType==10){
	                    $this->assign("payment","微信");
	                }else{
	                    $this->assign("payment","支付宝");
	                }	                
	                
	                $this->assign("strQrCodeUrl",urlencode($strQrCodeUrl));
	                $this->display("Public:ipspaycode");
	                
	            } else {
	                $message ="验签失败";
	            }
	        }else{
	            $message = $xmlResult->GateWayRsp->head->RspMsg;
	        }
	        
	    }else{
	        $this->error('订单信息有误');
	    }
	}
	
	
	public function ipsPayCodes2snotify_url(){
	    // 检查分销商信息
	    $agent = new \Common\Model\AgentModel();
	    if($agent->init()){
	        $agentInfo = $agent->getAgent();
	    }else{
	        $this->error($agent->error);
	    }
	    
	    $ipspay_config['Version']	 = 'v1.0.0';
	    //商戶号
	    $ipspay_config['MerCode']	 = $agentInfo['ips_mer_code'];
	    //交易账户号
	    $ipspay_config['Account']	 = $agentInfo['ips_account'];
	    //商戶证书
	    $ipspay_config['MerCert']	 = $agentInfo['ips_mer_cert'];
	    //请求地址
	    $ipspay_config['PostUrl']	 = 'https://thumbpay.e-years.com/psfp-webscan/services/scan?wsdl';
	    $ipspayNotify = new IpsPayNotify($ipspay_config);
	    $result = $ipspayNotify->verifyReturn();
	    
	    if ($result) { // 验证成功
	        $data = array(
	            'attach'         => $result['data']['merBillNo'],
	            'total_fee'      => $result['data']['amount'],
	            'mch_id'         => $result['data']['ipsBillNo'],
	            'transaction_id' => $result['data']['ipsTradeNo']
	        );
	        
	        $PayNotify  	= new \Common\Model\PayNotify();
	        
	        $order = D('Order')->getOrderBySn($result['data']['merBillNo']);
	        if($order['order_status'] != '2'){
	            // 支付成功后订单操作
	            $PayNotifyData  = $PayNotify->ReturnPay($order, $data, '环迅支付');	           
	        }
	         
	        echo "ipscheckok";
	    } else {
	        echo "ipscheckfail";
	    }
	}
	
	
	//检查订单状态
	public function ipsPayCodeCheckOrderStatus($order_sn){
	    $order = new \Common\Model\OrderModel();
	    $orderdetail = $order->getOrderBySn($order_sn);	
	    //p($orderdetail);
	    if($orderdetail['order_status']){	       
	        $this->ajaxReturn($orderdetail['order_id']);
	    }else{
	        $this->ajaxReturn(0);
	    }
	}
	

    /**
     * 环迅支付返回页面
     */
    public function ipsResponse()
    {
        $paymentResult = !empty($_REQUEST['paymentResult']) ? $_REQUEST['paymentResult'] : '';
        $this->ipsResponseHandle($paymentResult, 'normal');
	}

    /**
     * 环迅支付Server to Server返回
     */
    public function ipsResponseS2S()
    {
        $paymentResult = !empty($_REQUEST['paymentResult']) ? $_REQUEST['paymentResult'] : '';
        \Think\Log::write('IPS Server to Server:'.$paymentResult, 'INFO');
        $this->ipsResponseHandle($paymentResult, 's2s');
	}

    /**
     * 环迅支付返回结果统一处理
     *
     * @param $paymentResult
     * @param string $type 返回类型(表单|Server to Server)
     * @return mixed
     */
    private function ipsResponseHandle($paymentResult, $type='normal')
    {
        $agent = new \Common\Model\AgentModel();
        if($agent->init()){
            $agentInfo = $agent->getAgent();
        }else{
            if($type == 'normal'){
                $this->error($agent->error);
            }else{
                return $agent->error;
            }
            \Think\Log::write('IPS Pay Error:'.$agent->error, 'ERR');
        }

        $ipsPay = new IPSPay($agentInfo['ips_mer_code'], $agentInfo['ips_mer_cert'], $agentInfo['ips_account']);
        $result = $ipsPay->handleResponse($paymentResult) ;

        if(is_array($result) && isset($result['status'])){
            if($result['status'] == 'error'){
                if($type == 'normal'){
                    $this->error($result['msg']);
                }else{
                    return $result['msg'];
                }
                \Think\Log::write('IPS Pay Error:'.$result['msg'], 'ERR');
            }

            // 支付成功
            if($result['status'] == 'success' && !empty($result['data'])){
                $data = array(
                    'attach'         => $result['data']['merBillNo'],
                    'total_fee'      => $result['data']['amount'],
                    'mch_id'         => $result['data']['ipsBillNo'],
                    'transaction_id' => $result['data']['ipsTradeNo']
                );

                $PayNotify  	= new \Common\Model\PayNotify();

                $order = D('Order')->getOrderBySn($result['data']['merBillNo']);
                if($order['order_status'] != '2'){
                    // 支付成功后订单操作
                    $PayNotifyData  = $PayNotify->ReturnPay($order, $data, '环迅支付');
                    if($PayNotifyData == true&&$type != 'normal'){
                        return 'pay success';
                        \Think\Log::write('IPS Pay Success! OrderID:'.$order['order_id'], 'INFO');
                    }
                }
                if($type == 'normal'){
                    $this->order_id = $order['order_id'];
                    $this->display('Order/pay_success');
                }                

            }else{
                if($type == 'normal'){
                    $this->display('Order/pay_fail');
                }else{
                    return 'pay failed';
                }
                \Think\Log::write('IPS Pay Failed!'.$paymentResult, 'INFO');
            }
        }else{
            if($type == 'normal'){
                $this->error('非法交易');
            }else{
                return 'invalid transaction';
            }
            \Think\Log::write('IPS Pay Failed! Invalid Transaction.'.$paymentResult, 'INFO');
        }
	}

	//在线支付-支付宝订单回调提醒
	public function notify_url(){
		$alipay = new \Common\Model\Alipay();
		if($alipay->notify_url()){
			echo "success";
		}else{
			echo "fail";
		}
	}


	//在线支付-支付宝订单支付成功显示
	public function return_url(){
		$this->order_sn = $_POST['out_trade_no'];
        //layout(false); // 临时关闭当前模板的布局功能
		$alipay = new \Common\Model\Alipay();
		$result = $alipay->return_url();
		$this->result = $result;
		if($result){		///显示成功页面
			$this->order_id = $alipay->order_id;
		}else{				//显示失败页面
			$this->error = $alipay->error;
		}
		$this->display('Order/payOnline');
	}
	
	
    /**
     * 微信支付
     * zhy	find404@foxmail.com
     * 2017年4月25日 17:29:10
     */
	public function WeChatPay(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误');
		}
		$order = M("order")->where(" order_id= ".$order_id." and agent_id = ".C('agent_id'))->field('order_id,order_sn,if(order_price_up>0,order_price_up,order_price) as order_price')->find();
		$WeChatPay  = new \Common\Model\Pay\WxPay\StartPay();
		$data  		= $WeChatPay->GetPayCodeUrl($order);
		if(count($data)>1){
			$this->ajaxReturn($data);
		}
	}
 
	
    /**
     * 是否支付完成
     * zhy	find404@foxmail.com
     * 2017年4月25日 17:29:10
     */
	public function WeChatOrderStatus(){
	/*
		[appid] => wx15fa24d0fe699d91
		[attach] => 2017041716532055
		[bank_type] => CFT
		[cash_fee] => 1
		[fee_type] => CNY
		[is_subscribe] => N
		[mch_id] => 1219913101
		[nonce_str] => Qhlosh5goZdWyO39
		[openid] => ofACfjrcSDHVCAWZ0S8TkNXO9UCM
		[out_trade_no] => 121991310120170422154935
		[result_code] => SUCCESS
		[return_code] => SUCCESS
		[return_msg] => OK
		[sign] => 1B6AD959D4538CFFE91957F911993534
		[time_end] => 20170422155025
		[total_fee] => 1
		[trade_state] => SUCCESS
		[trade_type] => NATIVE
		[transaction_id] => 4007032001201704227936197024
		
		[appid] => wx15fa24d0fe699d91 
		[mch_id] => 1219913101 
		[nonce_str] => DdkoMGTaZTirVh3g 
		[out_trade_no] => 121991310120170425160229 
		[result_code] => SUCCESS 
		[return_code] => SUCCESS 
		[return_msg] => OK 
		[sign] => 5A8B2473B24098C204ECE696ED72F6A3 
		[trade_state] => NOTPAY 
		[trade_state_desc] => 订单未支付	
		
	*/
		$out_trade_no = I('outtradenum',0);
		if(is_numeric($out_trade_no)){
			$WeChatPay  = new \Common\Model\Pay\WxPay\StartPay();
			$data  		= $WeChatPay->WxOrderStatus($out_trade_no);
			if(isset($data['total_fee'])){
				$PayNotify  	= new \Common\Model\PayNotify();
				$order = M('order')->where("agent_id='".C('agent_id')."' and order_sn='".$data['attach']."'")->find();
				if($order['order_status']!='2'){
					$PayNotifyData  = $PayNotify->ReturnPay($order,$data,'微信支付');
					if($PayNotifyData==true){
						$Result= '1';
					}
				}else{
					$Result= '1';
				}
			}else{
					$Result = '0';
			}
			$this->ajaxReturn($Result);
		}
	}
	
    /**
     * 支付完成跳转
     * zhy	find404@foxmail.com
     * 2017年4月25日 17:29:10
     */
	public function WeChatOrderSuccess(){
		$order_id = I('order_id');
		$order_sn = I('order_sn');		
		if(is_numeric($order_id) && is_numeric($order_sn)){
			$order_status = M('order')->where("agent_id='".C('agent_id')."' and order_sn='".$order_sn."'")->getField('order_status');
			if($order_status=='2'){
				$this->order_id =$order_id;
				$this->display('Order/WeChatOrderSuccess');
			}else{
				exit();
			}
		}else{
			exit();
		}
	}	
	
	
	/*
	//在线支付-支付宝订单回调提醒
	public function WeChatPayNotifyUrl(){
		$alipay = new \Common\Model\Alipay();
		if($alipay->notify_url()){
			echo "success";
		}else{
			echo "fail";
		}
	}


	//在线支付-支付宝订单支付成功显示
	public function WeChatPayReturn_Url(){
		$this->order_sn = $_POST['out_trade_no'];
        //layout(false); // 临时关闭当前模板的布局功能
		$alipay = new \Common\Model\Alipay();
		$result = $alipay->return_url();
		$this->result = $result;
		if($result){		///显示成功页面
			$this->order_id = $alipay->order_id;
		}else{				//显示失败页面
			$this->error = $alipay->error;
		}
		$this->display('Order/payOnline');
	}
	*/
	
	
	
}