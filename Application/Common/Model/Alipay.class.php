<?php
/* *
 * 类名：Alipay
 * 功能：内部针对支付进行的二次封装
 * 详细：初始化支付数据以及生成支付订单
 * 日期：2016-09-20
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
 */

namespace Common\Model;

class Alipay {

	public $error = '';		//错误信息描叙

	public $alipay_config;		//商户配置信息

	public $order_id;		//订单ID

	private $logdir	= "/home/data/log/alipay/";				//错误日志目录
	
	//初始化配置信息
	function __construct(){
		//初始化分销商支付信息
		if(!C('agent_id')){
			$this->error = "初始化分销商信息失败，agent_id为零或空";
			return false;
		}

		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if(!$agent_config){
			$this->error = "初始化分销商信息失败，分销商不存在";
			return false;
		}

		if($agent_config['alipayid']=='' || $agent_config['alipaykey']==''){
			$this->error = "初始化分销商信息失败，分销商未正确开通支付宝支付";
			return false;
		}

		$domain = $agent_config['domain'];
		//如果客户域名为主域名，则添加www
		if(substr_count($agent_config['domain'], '.')==1){
			$domain = 'www.'.$domain;
		}

		//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
		//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
		$alipay_config['partner']		= $agent_config['alipayid'];

		//收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
		$alipay_config['seller_id']	= $alipay_config['partner'];

		// MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
		$alipay_config['key']			= $agent_config['alipaykey'];

		// 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
		$alipay_config['notify_url'] = "http://".$domain."/Home/Pay/notify_url";

		// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
		$alipay_config['return_url'] = "http://".$domain."/Home/Pay/return_url";

		//签名方式
		$alipay_config['sign_type']    = strtoupper('MD5');

		//字符编码格式 目前支持 gbk 或 utf-8
		$alipay_config['input_charset']= strtolower('utf-8');

		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		$alipay_config['cacert']    = getcwd().'\\cacert.pem';

		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$alipay_config['transport']    = 'http';

		// 支付类型 ，无需修改
		$alipay_config['payment_type'] = "1";
				
		// 产品类型，无需修改
		$alipay_config['service'] = (C('H5Pay')) ? 'alipay.wap.create.direct.pay.by.user' : 'create_direct_pay_by_user' ;
 
		//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


		//↓↓↓↓↓↓↓↓↓↓ 请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
			
		// 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
		$alipay_config['anti_phishing_key'] = "";
			
		// 客户端的IP地址 非局域网的外网IP地址，如：221.0.0.1
		$alipay_config['exter_invoke_ip'] = "";

		$this->alipay_config = $alipay_config;
	}

	/**

	* 说明： 传入订单编号，生成支付内容，跳转支付
	**/
	function request($order_id){
		//获取订单相关信息
		$order = M('order')->where("agent_id='".C('agent_id')."' and order_id='".$order_id."'")->find();
		if(!$order){
			$this->error = "初始化订单信息失败，订单不存在";
			return false;
		}

		if($order['order_status']<0 || $order['order_status']=='6'){
			$this->error = "初始化订单信息失败，订单已取消或已完成，不需要支付";
			return false;
		}

		if($order['order_price_up']<=0 && $order['order_price']<=0){
			$this->error = "初始化订单信息失败，订单价格非法";
			return false;
		}

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order['order_sn'];

        //订单名称，必填
        $subject =  $agent_config['agent_name'].'订单付款';

        //付款金额，必填
		if($order['order_price_up']>0){
			$total_fee = $order['order_price_up'];
		}else{
			$total_fee = $order['order_price'];
		}
		
		//$total_fee = '0.10'; //测试金额
        //商品描述，可空
        $body = '';
		
		$alipay_config = $this->alipay_config;		//获取初始化信息

		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service"       => $alipay_config['service'],
				"partner"       => $alipay_config['partner'],
				"seller_id"  => $alipay_config['seller_id'],
				"payment_type"	=> $alipay_config['payment_type'],
				"notify_url"	=> $alipay_config['notify_url'],
				"return_url"	=> $alipay_config['return_url'],
				
				"anti_phishing_key"=>$alipay_config['anti_phishing_key'],
				"exter_invoke_ip"=>$alipay_config['exter_invoke_ip'],
				"out_trade_no"	=> $out_trade_no,
				"subject"	=> $subject,
				"total_fee"	=> $total_fee,
				"body"	=> $body,
				"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
				//其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
				//如"参数名"=>"参数值"
				
		);

		//建立请求
		$alipaySubmit = new \Common\Model\AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		return $html_text;

	}

	/**
	* 说明： 传入订单编号，生成支付内容，跳转支付
	**/
	function notify_url(){
		$alipay_config = $this->alipay_config;		//获取初始化信息

		//日志记录
		$log_text = $_SERVER["QUERY_STRING"];
		@error_log(date("Y-m-d H:i:s") . '-' . $log_text.  "\n", 3, $this->logdir . "/notify_url.log");
		@error_log(serialize($_POST).  "\n", 3, $this->logdir . "/notify_url.log");
		
		//测试数据
		//$_POST = unserialize('a:22:{s:8:"discount";s:4:"0.00";s:12:"payment_type";s:1:"1";s:7:"subject";s:12:"订单付款";s:8:"trade_no";s:28:"2016092621001004040217251320";s:11:"buyer_email";s:15:"53472453@qq.com";s:10:"gmt_create";s:19:"2016-09-26 17:31:17";s:11:"notify_type";s:17:"trade_status_sync";s:8:"quantity";s:1:"1";s:12:"out_trade_no";s:16:"2016082316032896";s:9:"seller_id";s:16:"2088421675110784";s:11:"notify_time";s:19:"2016-09-27 02:55:50";s:12:"trade_status";s:13:"TRADE_SUCCESS";s:19:"is_total_fee_adjust";s:1:"N";s:9:"total_fee";s:4:"0.10";s:11:"gmt_payment";s:19:"2016-09-26 17:31:33";s:12:"seller_email";s:16:"sghszb888@qq.com";s:5:"price";s:4:"0.10";s:8:"buyer_id";s:16:"2088002014085042";s:9:"notify_id";s:34:"924cb304df7783ea82e15c79d037500gb6";s:10:"use_coupon";s:1:"N";s:9:"sign_type";s:3:"MD5";s:4:"sign";s:32:"d3af96f6295ce4046c1be3614f5914a8";}');

		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		if($verify_result) {//验证成功
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表			
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			$total_fee = $_POST['total_fee'];

			//获取对应订单信息
			$order = M('order')->where("agent_id='".C('agent_id')."' and order_sn='".$out_trade_no."'")->find();
			if(!$order){
				$this->error = "订单不存在";
				@error_log(date("Y-m-d H:i:s") . "-订单不存在\n", 3, $this->logdir . "/notify_error.log");
				return true;
			}
			
			if($order['order_status']<0){
				@error_log(date("Y-m-d H:i:s") . "-订单已取消，不需要支付\n", 3, $this->logdir . "/notify_error.log");
				@error_log(serialize($order).  "\n", 3, $this->logdir . "/notify_error.log");
				$this->error = "订单已取消，不需要支付";
				return true;
			}

			$payment = M('order_payment')->where("agent_id='".C('agent_id')."' and trade_no='".$trade_no."'")->find();
			if($payment){
				$this->error = "支付请求已处理";
				@error_log(date("Y-m-d H:i:s") . "-支付请求已处理\n", 3, $this->logdir . "/notify_error.log");
				return true;
			}


			if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

				$pay_process['trade_no']		= $trade_no;
				$pay_process['total_fee']		= $total_fee;
				$pay_process['out_trade_no']	= $out_trade_no;
				$pay_process['partner']			= $alipay_config['partner'];
				$this->pay_process($order, $pay_process, 1);

				return true;
			}
			else{
				//未交易成功
				$this->error = "未交易成功";
				return false;
			}			
		}
		else {
			//验证失败
			@error_log(date("Y-m-d H:i:s") . "-验证失败\n", 3, $this->logdir . "/notify_error.log");
			$this->error = "验证失败";
			return false;
		}

	}

	/**
	* 返回结果页面处理
	**/
	function return_url(){
		//测试链接		http://zmfx.com/Home/Pay/return_url?out_trade_no=2016110417292326
		$alipay_config = $this->alipay_config;		//获取初始化信息
		
		$log_text = $_SERVER["QUERY_STRING"];
		@error_log(date("Y-m-d H:i:s") . '-' . $log_text.  "\n", 3, $this->logdir . "return_url.log");
		@error_log(serialize($_POST).  "\n", 3, $this->logdir . "return_url.log");
		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		//if(1) {//验证成功			
		if($verify_result) {//验证成功
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表			
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];
			//交易状态
			$trade_status = $_GET['trade_status'];
			//支付宝交易号
			$trade_no = $_GET['trade_no'];
			$total_fee = $_GET['total_fee'];

			//测试数据
			//$out_trade_no = "2016110416562229";
			//$trade_no = "2016110321001004530273527182";
			//$trade_status = "TRADE_SUCCESS";
			//$total_fee = 500.00; 

			//获取对应订单信息
			$order = M('order')->where("agent_id='".C('agent_id')."' and order_sn='".$out_trade_no."'")->find();
			if(!$order){
				$this->error = "订单不存在";
				return false;
			}
			
			$this->order_id = $order['order_id'];

			if($order['order_status']<0){
				$this->error = "订单已取消，不需要支付";
				$phone          = M("User")->where('uid='.$order['uid'])->getField('phone');
				$s              = new \Common\Model\Sms();
				$s           -> sendSms($phone,'paymet_fail_send_user',C('agent_id'));
				return false;
			}

			$payment = M('order_payment')->where("agent_id='".C('agent_id')."' and trade_no='".$trade_no."'")->find();
			if($payment){
				$this->error    = "支付请求已处理";
				$phone          = M("User")->where('uid='.$order['uid'])->getField('phone');
				$s              = new \Common\Model\Sms();
				$s           -> sendSms($phone,'paymet_fail_send_user',C('agent_id'));
				return true;
			}
			
			
			if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

				$pay_process['trade_no']		= $trade_no;
				$pay_process['total_fee']		= $total_fee;
				$pay_process['out_trade_no']	= $out_trade_no;
				$pay_process['partner']			= $alipay_config['partner'];
				
				
				$this->pay_process($order, $pay_process, 0);

				return true;
			}else{
				$order          = M('order')->where("agent_id='".C('agent_id')."' and order_sn='".$out_trade_no."'")->find();
				$phone          = M("User")->where('uid='.$order['uid'])->getField('phone');
				$s              = new \Common\Model\Sms();
				$s           -> sendSms($phone,'paymet_fail_send_user',C('agent_id'));
				//未交易成功
				$this->error = "未交易成功";
				return false;
			}

		}
		else {
			//验证失败
			//如要调试，请看alipay_notify.php页面的verifyReturn函数
			
			$this->error = "验证失败";
			return false;
		}
	}

	/**
	 根据回调信息对订单进行处理
	**/
	function pay_process($order, $pay_process, $notity = 1){
		//修改收款验证状态
		$OP = M('order_payment');
		//收款金额和收款折扣必须有一个

		//支付宝支付方式获取
		$PM = M('payment_mode');
		$paymode = $PM->where("agent_id='".C('agent_id')."' and mode_name='支付宝支付'")->getField('mode_id');

		//如果订单是未确定状态下
		if($order['order_status']==0){
			//$OPE = M('order_period');
			$OR = M('order_receivables');

			//更改货品数量和价格
			$OG = M('order_goods');
			for ($i = 1; $i <= 4; $i++) {
				$goodslist = $OG->where('agent_id = '.C('agent_id').' AND order_id = '.$order['order_id'] .' AND goods_type='.$i)->select();
				if(!$goodslist){		//如果货品不存在
					continue;
				}

				$order_goods_price = 0;	//同类产品累积价格形成应收
				foreach ($goodslist as $key => $value) {				
					$update['goods_price_up'] = $value['goods_price'];
					$update['goods_number_up'] = $value['goods_number'];
					$update['update_time'] = time();
					$res3 = $OG->where('agent_id = '.C('agent_id').' AND og_id = '.$value['og_id'])->save($update);

					$order_goods_price += $value['goods_price'];
				}

				/** 默认全款支付不产生分期信息
				//生成订单分期表
				$periodArr['order_id'] = $order['order_id'];
				$periodArr['period_type'] = 1;
				$periodArr['period_num'] = 1;
				$periodArr['period_overdue'] = 0;
				$periodArr['agent_id'] = C('agent_id');
				$OPE->add($periodArr);
				**/

				//生成应收款
				$add['order_id'] = $order['order_id'];
				$add['receivables_price'] = $order_goods_price;
				$add['create_time'] = time();
				$add['period_day'] = 30;
				$add['payment_time'] = time()+2592000;
				$add['period_current'] = 1;
				$add['period_type'] = 3;
				$add['parent_type'] = $order['parent_type'];
				$add['parent_id'] = $order['parent_id'];
				$add['uid'] = $order['uid'];
				$add['tid'] = $order['tid'];
				$add['agent_id'] = C('agent_id');
				$OR->add($add);
			}
			
			//未确定订单需要修改订单确认价格
			$order_data['order_price_up'] = $order['order_price'];
		}

		//组装数据
		$data = array();				
		$data['order_id'] = $order['order_id'];
		$data['order_sn'] = $pay_process['out_trade_no'];
		$data['uid'] = $order['uid'];
		$data['tid'] = $order['tid'];
		$data['parent_id'] =  0;
		$data['discount_price'] = 0;									//折扣
		$data['payment_price'] = $pay_process['total_fee'];				//收款金额
		$data['create_time'] = time();
		$data['payment_mode'] = intval($paymode);
		$data['payment_user'] =  $pay_process['partner'];
		$data['payment_note'] = "支付宝在线支付";
		$data['trade_no'] = $pay_process['trade_no'];
		$data['payment_status'] = 1;									//手工入款自动确认支付				
		$data['agent_id'] = C('agent_id');

		//订单日志信息
		$msg = '支付宝在线支付￥：'.$data['payment_price'].' 折扣￥：0';
		//支付数据写入
		$OrderPayment = new \Common\Model\OrderPayment();
		if(!$OrderPayment->pay($data, $msg)){
			if($notity == 1){
				@error_log(serialize($data).  "\n", 3, $this->logdir . "/notify_error.log");
			}else{
				@error_log(serialize($data).  "\n", 3, $this->logdir . "/return_error.log");
			}			
			return false;	//内部处理失败
		}

		//更改订单状态
		$O = M('order');
		$order_data['order_status']  = 2;
		$order_data['order_payment'] = 1;		
		$order_data['update_time']   = time();
		$res = $O->where(' agent_id = '.C('agent_id').' and order_id = '.$order['order_id'])->save($order_data);
		$phone          = M("User")->where('uid='.$order['uid'])->getField('phone');
		$s              = new \Common\Model\Sms();
		$s           -> sendSms($phone,'paymet_ok_send_user',C('agent_id'));
		$getParentIDByUID = M('user')->where("uid='".$order['uid']."' and agent_id = ".C('agent_id'))->getField('parent_id');
		$phone            = M("admin_user")->where('user_id='.$getParentIDByUID)->getField('phone');
		$s           -> sendSms($phone,'paymet_ok_send_admin',C('agent_id'));

	}
}
?>