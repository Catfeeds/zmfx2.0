<?php
/* *
 * 类名：Sms
 * 功能：短信发送对接模块
 * 详细：与一信通对接进行短信发送
 * 日期：2016-11-07
 * 说明：
 * 第一版先实现一信通短信发送，后续再考虑回执以及其他功能，或对接其他第三方短信服务
 */

namespace Common\Model;

class Sms {

	public $error = '';		//错误信息描叙

	public $limit = 30;		//单个号码当日发送次数限制

	private $logdir	= "/home/data/log/sms/";		//错误日志目录
	
	/*private $SpCode	= '103904';						//一信通企业编号
	private $LoginName	= 'sz_zmwl';				//一信通登录帐号
	private $Password	= 'szzmzb2016';				//一信通密码*/
	
	/*const ZUNJUE_SPCODE  				= '239723';				// 尊爵用。
	const ZUNJUE_LOGINNAME  			= 'admin2';				
	const ZUNJUE_PASSWORDE  			= 'zunjuezuanshi888';	*/
	

	const USER_REGIST_SUCCESS  			= 'A';		//用户注册成功
	const ORDER_SUBMIT_SUCCESS  		= 'B';		//选择线下转账，订单提交成功
	const ONLINE_PAYMENT_SUCCESS  		= 'C';		//在线支付成功			*	
	const ONLINE_PAYMENT_FAIL  			= 'D';		//在线支付失败			*	
	const BUSINESS_DELIVER  			= 'E';		//后台商家发货
	const USER_ORDER_SUBMIT_SUCCESS  	= 'F';		//用户订单提交成功		*		
	const USER_ONLINE_PAYMENT_FINISH  	= 'G'; 		//用户在线支付完成		*
	const USER_CONFIRM_RECEIPT  		= 'H';		//客户确认收货			
	const USER_ORDER_CANCEL  			= 'I';		//订单取消
	const USER_IDENTIFYING_CODE			= 'J';		//用户验证码
	const CUSTOMER_BOOKING_SUCCESS 		= 'K';		//门店预约成功（发送信息给客户）
	const STORE_BOOKING_SUCCESS			= 'L';		//门店预约成功（发送信息给商家门店业务员）
	const BUSINESS_BOOKING_SUCCESS		= 'M';		//门店预约成功（发送信息给商家）
	
	//初始化配置信息
	function __construct(){
	}

	/**
	* 说明： 简单短信发送(一信通)
	**/
	function sendE($mobile, $content,$user,$smsNum=0){
		//参数判断
		if(!is_mobile($mobile)){
			$this->error = "手机号码格式不正确";
			return false;
		}
	
		if(strlen($content)>250 || strlen($content)<=0) {
			$this->error = "短信内容不能为空或超过170字符";
			return false;
		}

		//$curtime = mktime(0, 0, 0, date("m"), date("d"), date("Y"));	//今天的起点 2016-11-07 00:00:00

		$curtime = date("Y-m-d 00:00:01");	//今天的起点 2016-11-07 00:00:00

	
		//获取本号码当日所有短信数
		$total = M('sms_send_log')->where("f_mobile='".$mobile."' and f_time>='".$curtime."'")->count();
		
		if($smsNum==0){ $smsNum = $this->limit; }
		if($total >= $smsNum){
			$this->error = "本手机号当日发送短信已超过限制(每日".$smsNum."条)";
			return false;
		}
		//生成发送序列号
		$serial_number = date("YmdHis").mt_rand(100000,999999);

        $url = "https://api.ums86.com:9600/sms/Api/Send.do";
		// 参数数组
		//SpCode=103904&LoginName=sz_zmwl&Password=szzmzb2016&MessageContent=testst123456&UserNumber=13751085440&SerialNumber=20161104165622291914&ScheduleTime=&f=1
 
		/*$body = array (
		    'SpCode' => isset($user) ? self::ZUNJUE_SPCODE : $this->SpCode,
		    'LoginName'  => isset($user) ? self::ZUNJUE_LOGINNAME : $this->LoginName,
			'Password'  => isset($user) ? self::ZUNJUE_PASSWORDE : $this->Password,
			'MessageContent'  => iconv("UTF-8", "GBK", $content),
			'UserNumber'  => $mobile,
			'SerialNumber'  => $serial_number,
			'ScheduleTime'  => '',
		    'f'   => '1'
		);*/
		//获取商家一信通信息
		$smsData = M('sms_agent_info')->where("agent_id=".C('agent_id'))->find();
		if(empty($smsData)){ $smsData = M('sms_agent_info')->where("agent_id=7")->find(); }
		
		//根据
		$body = array (
		    'SpCode' => $smsData["spcode"],
		    'LoginName'  => $smsData["login_name"],
			'Password'  => $smsData["password"],
			'MessageContent'  => iconv("UTF-8", "GBK", $content),
			'UserNumber'  => $mobile,
			'SerialNumber'  => $serial_number,
			'ScheduleTime'  => '',
		    'f'   => '1'
		);
 
		$ch = curl_init ();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_HEADER, 0 );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
		$data = curl_exec($ch);
		curl_close($ch);
 
		$error = '';
		$isok = 0;
		if($data){
			$data = iconv("GBK", "UTF-8", $data);
			parse_str($data);
			if(!(isset($result) && $result==0)){		//失败
				$isok = 1;
				$error = $description;
				//$this->error = $description;
				$this->error = "发送失败";
				$result = false;
			}else{
				$result = true;
			}
		}
 
		
		//生成短信发送记录
		$sms_log['f_mobile']  = $mobile;
		$sms_log['f_type']    = 1;
		$sms_log['f_content'] = $content;
		$sms_log['f_agent']   = C('agent_id');
		$sms_log['f_serial']  = $serial_number;
		$sms_log['f_time']    = date("Y-m-d H:i:s");
		$sms_log['f_error']   = $error;
		$sms_log['f_isok']    = $isok;
		$r = M('sms_send_log') -> add($sms_log);
		return $result;
	}


 
	
	
	
	public function sendSms( $mobile , $key , $agent_id = 0 ){

		$info = $this -> getSmsInfo( $key , $agent_id );
		if( !empty($info) && $info['status'] ){
			$this -> sendE($mobile,$info['sms_info']);
		}

	}

	public function getSmsList( $agent_id ){

		$ST   = M('sms_type');
		$S    = M('sms');
		$list = $ST -> select();
		$data = array();

		foreach($list as $row){
			$info = $S -> where('agent_id = '.$agent_id." and sms_type_key = '".$row['sms_type_key']."' ") -> find();
			$_row = array();
			$_row['sms_type_key']    = $row['sms_type_key'];
			$_row['push_where_text'] = $row['push_where_text'];
			$_row['push_who_text']   = $row['push_who_text'];
			if(empty($info)){
				$_row['sms_info']    = $row['default_sms_info'];
				$_row['status']      = 0;
			}else{
				$_row['sms_info']    = $info['sms_info'];
				$_row['status']      = $info['status'];
			}
			$data[]                  = $_row;
		}
		return $data;

	}

	public function getSmsInfo( $key , $agent_id ){

		$ST   = M('sms_type');
		$S    = M('sms');
		$row  = $ST -> where( " sms_type_key = '$key' ") -> find();
		$info = $S  -> where( ' agent_id = '.$agent_id." and sms_type_key = '$key' ") -> find();
		$_row = array();
		$_row['sms_type_key']    = $row['sms_type_key'];
		$_row['push_where_text'] = $row['push_where_text'];
		$_row['push_who_text']   = $row['push_who_text'];
		if( empty($info) ){
			$_row['sms_info']    = $row['default_sms_info'];
			$_row['status']      = 0;
		} else {
			$_row['sms_info']    = $info['sms_info'];
			$_row['status']      = $info['status'];
		}
		$data                    = $_row;
		return $data;

	}

	public function saveSms( $key , $info , $status = 0 , $agent_id = 0 ){

		$ST                   = M('sms_type');
		$S                    = M('sms');
		$re                   = $ST -> where( " sms_type_key = '$key' ") -> find();
		if( !$re ){
			return false;
		}
		$data                 = array();
		$data['agent_id']     = $agent_id;
		$data['sms_type_key'] = $key;
		$data['sms_info']     = $info;
		$data['status']       = $status;
		$re                   = $S -> where( ' agent_id = '.$agent_id." and sms_type_key = '$key' ") -> find();
		if( empty( $re ) ){
			$S -> add( $data );
		} else {
			$S -> where('sms_type_key = "'.$key.'"') -> save( $data );		
		}
		return true;

	}

	
    /**
     * 根据商家业务逻辑发送短信		
     * zhy	find404@foxmail.com
     * 2017年4月17日 11:22:36
     */
	public function SendSmsByType($type,$mobile,$content){
		//商家发送信息的管理范围     2017-08-18    zengmingming
 		$smsData = M('sms_agent_info')->where("agent_id=".C('agent_id'))->field("sms_jurisdiction")->find();
		$sms_jurisdiction = $smsData["sms_jurisdiction"];	
		if($sms_jurisdiction!=""){
			if (strpos($smsData["sms_jurisdiction"], $type) !== false) {
				$smsdata= M('sms_template')->where("as_name = '$type'")->find();
					$before = array();
					if($smsdata){
						if($type == self::USER_REGIST_SUCCESS || $type == self::ORDER_SUBMIT_SUCCESS || $type == ONLINE_PAYMENT_SUCCESS || $type == self::BUSINESS_DELIVER  || $type ==self::USER_CONFIRM_RECEIPT  || $type ==self::USER_ORDER_CANCEL || $type==self::USER_IDENTIFYING_CODE || $type==self::CUSTOMER_BOOKING_SUCCESS || $type==self::STORE_BOOKING_SUCCESS || $type==self::BUSINESS_BOOKING_SUCCESS){
							$before[] = '{#NUM#}';
							if (strpos($smsdata['content'], '{#NNUM#}') !== false) {
								$before[] = '{#NNUM#}';	
							} 
							if(strpos($smsdata['content'], '{#ONUM#}') !== false){
								$before[] = '{#ONUM#}';		
							}
							$smsdata['content'] = str_replace($before,$content,$smsdata['content']);
						}
						
						if($type !=self::USER_ORDER_CANCEL && $type !=self::USER_CONFIRM_RECEIPT && $type!=self::BUSINESS_BOOKING_SUCCESS){
							if($type==self::USER_IDENTIFYING_CODE){
								//验证码短信限制为3条
								$this -> sendE($mobile,$smsdata['content'],true,3);
							}else{
								$this -> sendE($mobile,$smsdata['content'],true);
							}
						}
							
						if($smsdata['to_who']=='2'){
							if($type == self::ORDER_SUBMIT_SUCCESS){
								$smsdata['content']='尊敬的用户，您有新的客户订单提交成功，请及时关注！';
							}
							
							if($type == self::USER_REGIST_SUCCESS){
								$smsdata['content']='尊敬的用户，您有新的会员注册成功！';
							}
							
							$business_mobile = M('sms_agent_info')->where("agent_id=".C('agent_id'))->getField('sms_push_reminder');
							if($business_mobile){
								$this -> sendE($business_mobile,$smsdata['content'],true);
							}
						}
					}else{
						return false;
					}
			}else{
				return false;
			}
		}else{
			return false;
		}	
	}
	
	
	
	
	
	
	
}
?>