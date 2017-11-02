<?php
/**
 * auth	：fangkai
 * content：供应宝用户设置
			资料管理，账户密码修改，邮箱绑定，消息通知等操作
 * time	：2016-4-29
**/
namespace Supply\Controller;
class SettingController extends SupplyController{
	public function __construct() {
		parent::__construct();
	}
	
    public function index(){
        $this->display();
    }
	/**
	 * auth	：fangkai
	 * content：用户资料展示
	 * time	：2016-4-29
	**/
	public function datamanage(){
		$user  		=  D('SupplyUser');
		$account 	=  D('SupplyAccount');
		$uid   		=  $this->uid;
        $data['SupplyUser']  = $user->getUserInfoByUid($uid);
		$data['account'] = $account->getCurSupplyAccount($uid);
		switch($data['account']['id_type']){
			case '居民身份证':
				$data['account']['id_type'] = L('text_id_type_zh');
				break;
			case '港台身份证':
				$data['account']['id_type'] = L('text_id_type_hk_mc_tw');
				break;
			case '护照':
				$data['account']['id_type'] = L('text_passport');
				break;
		}
		if($data['account']['business_type']){
			$business_type = explode(',',$data['account']['business_type']);
			foreach($business_type as $key=>$val){
				switch($val){
					case '裸钻':
						$business_type[$key] = L('text_business_type_luozuan');
						break;
					case '钻饰':
						$business_type[$key] = L('text_business_type_zuanshi');
						break;
					case '素金':
						$business_type[$key] = L('text_business_type_sujin');
						break;
					case '彩宝':
						$business_type[$key] = L('text_business_type_caibao');
						break;
					case '玉石':
						$business_type[$key] = L('text_business_type_yushi');
						break;
					case '其它':
						$business_type[$key] = L('text_business_type_other');
						break;
				}
			}
			$data['account']['business_type'] = implode(',',$business_type);
		}
		$this -> echoJson($data);
	}
	/**
	 * auth	：fangkai
	 * content：用户资料修改
	 * time	：2016-4-29
	**/
    public function update_date(){
		if($_POST){
			$account 	=  D('SupplyAccount');
			$uid   		=  $this->uid;
			$info 		=  I('post.');
			$action 	=  $account->updateAccount($info);
			if($action == true){
				$this->echoJson($data,100,L('change_success'));
			}else{
				$this->echoJson($data,0,L('change_failed'));
			}
		}
    }
	/**
	 * auth	：fangkai
	 * content：密码修改
	 * time	：2016-4-29
	**/
    public function update_password(){
		if($_POST){
			$old_password		= I('post.old_password', 'htmlspecialchars');
			$new_password 		= I('post.new_password', 'htmlspecialchars');
			$confirm_password 	= I('post.confirm_password', 'htmlspecialchars');
			$uid   		=  $this->uid;
			if($old_password && $new_password && $confirm_password){
				if($old_password == $new_password){
					$this->echoJson($data,0,L('error_old_newpassword'));
					exit;
				}
				if ($new_password === $confirm_password && $old_password != $new_password) {
					$action = D('SupplyUser')->updatepassword($old_password,$new_password,$uid);
					if($action == 1){
						$data['url'] = U('Index/login');
						$this->echoJson($data,100,L('change_success'));
					}else if($action == 2){
						$this->echoJson($data,0,L('error_oldpassword'));
					}else{
						$this->echoJson($data,0,L('change_failed'));
					}	
				}else{
					$this->echoJson($data,0,L('error_new_conpassword'));
				}
			}else{
				$this->echoJson($data,0,L('change_failed'));
			}
			
		}
	}
	/**
	 * auth	：fangkai
	 * content：获取邮箱验证码
	 * time	：2016-5-3
	**/
    public function getemailcode(){
		$User 	=  D('SupplyUser');
		$new_username = I('post.new_username','');
		$type  = I('post.type','','intval');
		if(!in_array($type,array(1,2))){
			$this->echoJson($data,0,L('operation_errors'));
			exit;
		}
		$uid   =  $this->uid;
		$check =  $User->getUserInfoByUid($uid);
		if(empty($check)){
			$this->echoJson($data,0,L('error_msg_076'));
			exit;
		}
		switch($type){
			case 1:
				$emailaddr = $check['username'];
				break;
			case 2:
				$emailaddr = $new_username;
				break;
		}
		$time = time();
		$email_time = $_SESSION[$emailaddr]['time'];
		if($email_time && ($email_time+60*2) > $time){
			$this->echoJson($data,0,L('error_msg_001'));
			exit;
		}
		$chars  = '0123456789';
		$code  = $this->code(6,$chars);
		$msg  = L('send_email_msg');
		$message = sprintf($msg,$code);
		
		$send_email = D('Mail')->sendMail($emailaddr,$message);
		if($send_email){
			//session($emailaddr['code'],$code);
			$_SESSION[$emailaddr]['code'] = $code;
			$_SESSION[$emailaddr]['time'] = $time;
			$this->echoJson($data,100,L('success_msg_001'));
		}else{
			$this->echoJson($data,0,L('error_msg_002'));
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：邮箱验证
	 * time	：2016-5-3
	**/
    public function checkemail(){
		if(IS_POST){
			$User 	=  D('SupplyUser');
			$type   = I('post.type','','intval');
			if(!in_array($type,array(1,2))){
				$this->echoJson($data,0,L('operation_errors'));
				exit;
			}
			switch($type){
				case 1:
					$email_code = I('post.email_code','');
					break;
				case 2:
					$new_username  = I('post.new_username','');
					$email_code = I('post.new_email_code','');
					break;
			}
			
			if(empty($email_code)){
				$this->echoJson($data,0,L('verification_failed'));
				exit;
			}
			$uid   =  $this->uid;
			$check =  $User->getUserInfoByUid($uid);
			if(empty($check)){
				$this->echoJson($data,0,L('error_msg_076'));
				exit;
			}
			switch($type){
				case 1:
					$key = $check['username'];
					break;
				case 2:
					$key = $new_username;
					break;
			}
			$code  = $_SESSION[$key]['code'];
			$uid   =  $this->uid;
			
			if($email_code == $code ){
				//$action = $account->updateemail($uid);
				switch($type){
					case 1:
						$this->echoJson($data,100,L('verification_success'));
						break;
					case 2:
						if($check['username'] == $new_username){
							$this->echoJson($data,0,L('error_old_newusername'));
							exit;
						}
						$action = $User->updateUsername($new_username,$uid);
						if($action == 1){
							$this->echoJson($data,100,L('change_success'));
						}else if($action == 2){
							$this->echoJson($data,0,L('error_username_has'));
						}else{
							$this->echoJson($data,0,L('change_failed'));
						}
						break;
				}
				exit;
			}else{
				$this->echoJson($data,0,L('verification_failed'));
				exit;
			}
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：获取系统消息列表
	 * time	：2016-5-3
	**/
    public function message_list(){
		$page = I('page','1');
		$size = I('size','10');
		$uid = $this->uid;
		$message 	=  D('SupplyMessage');
		$data = $message->messagelist($uid,$page,$size,0,'id desc');
		if($data){
			$this->echoJson($data,100,L('success_msg_002'));
			exit;
		}else{
			$this->echoJson($data,0,L('error_msg_003'));
			exit;
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：改变系统消息的状态
	 * time	：2016-5-4
	**/
    public function update_status(){
		if(IS_POST){
			$id  = I('id','','intval');
			$uid = $this->uid;
			$message=  D('SupplyMessage');
			$return = $message->updatestatus($id,$uid,0,0);
			$messageList   = $message -> messagelist($uid,'','',0,'id desc',array('0'));
			$data['total_count'] = $messageList['total'];
			if($return == true){
				$this->echoJson($data,100,L('change_success'));
				exit;
			}else{
				$this->echoJson($data,0,L('operation_error'));
				exit;
			}
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：删除消息
	 * time	：2016-5-4
	**/
    public function delete_message(){
		if(IS_POST){
			$id = I('post.id','','intval');
			$uid = $this->uid;
			$message=  D('SupplyMessage');
			$return = $message->deletemessage($id,$uid,0);
			if($return == true){
				$this->echoJson($data,100,L('delete_success'));
				exit;
			}else{
				$this->echoJson($data,100,L('delete_failed'));
				exit;
			}
		}
	}
	
	
	/**
	 * auth	：fangkai
	 * content：意见反馈
	 * time	：2016-5-3
	**/
    public function add_opinion(){
		if(IS_POST){
			$message 	=  D('SupplyMessage');
			$info['title'] = I('post.title');
			$info['content'] = I('post.content');
			$info['email'] = I('post.email');
			$uid   = $this->uid;
			$action = $message->addopinion($info,$uid);
			if($action == true){
				$this->echoJson($data,100,L('submit_success'));
			}else{
				$this->echoJson($data,0,L('submit_failed'));
			}
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：帮助指引
	 * time	：2016-6-21
	**/
    public function expand(){
		$articleList = M('supply_article')->where(array('is_show'=>1))->order('sort DESC')->select();
		$data = $articleList;
		$this->echoJson($data,100,L('submit_success'));
	}
	
	/**
	 * auth	：fangkai
	 * content：获取随机数
				$length:随机数长度，$chars:组合的字符串
	 * time	：2016-5-3
	**/
	public function code($length,$chars){
		$code = '';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) {
		$code .= $chars[mt_rand(0, $max)];
		}
		return $code;
	}

	
	
}