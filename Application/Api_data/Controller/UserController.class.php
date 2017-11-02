<?php
 /**
	 * auth	：fangkai
	 * content：用户模块
	 * time	：2016-11-8
	**/
namespace Api_data\Controller;
class UserController extends Api_dataController{
	
	 /**
	 * auth	：fangkai
	 * content：构造函数
	 * time	：2016-11-8
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid")?C('uid'):I('get.uid',0,'intval');
			$this->token	= C("token")?C('token'):I('get.token','','');
	} 
    /**
	 * auth	：fangkai
	 * content：APP注册
	 * time	：2016-11-8
	**/
   public function regist() {
 
		$UM   			= M('user');
		$userCode		= M('user_phone_code');
		$username		= I('username','','intval');
		$code			= I('code','');
		$pwd			= I('password','');
		$confirm_pwd	= I('confirm_password','');
		$agreement		= I('agreement','');
		
		$checkUser	= $UM->fetchsql(false)->where(array('agent_id'=>$this->agent_id,'username'=>$username))->find();

		if($checkUser){
			$this->echo_data('209','该手机号已注册');
			return false;
		}
		if(!is_mobile($username)){
			$this->echo_data('207','手机号码格式不正确');
			return false;
		}
		if(empty($pwd)){
			$this->echo_data('202','密码不能为空');
			return false;
		}
		if(empty($confirm_pwd)){
			$this->echo_data('203','确认密码不能为空');
			return false;
		}
		if(mb_strlen($pwd) < 6){
			$this->echo_data('204','密码长度不能少于6个字符');
			return false;
		}
		if(mb_strlen($pwd) > 20){
			$this->echo_data('204','密码长度不能超过20个字符');
			return false;
		}
		if($pwd != $confirm_pwd){
			$this->echo_data('205','密码和确认密码不一致');
			return false;
		}
		if(empty($agreement)){
			$this->echo_data('206','请勾选用户协议');
			return false;
		}
		$codeInfo	= $userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$username))->order('id DESC')->find();
		if(empty($code) || mb_strlen($code) != 6 || $codeInfo['code'] != $code){
			$this->echo_data('210','验证码错误');
			return false;
		}
		
		$User['username']  	= $username;
		$User['password']  	= pwdHash($pwd);
		$User['reg_time'] 	= time();
		$User['last_logintime'] = time();
		$User['last_ip'] 	= get_client_ip();
		$User['rank_id'] 	= 0;
		$User['parent_type']= C('agent')['level'];
		$User['agent_id'] 	= $this->agent_id;
		$User['phone_validate'] 	= 1;//手机注册自动加上已经验证
		$insertId = $UM->data($User)->add();
		if ($insertId) {
				//发送站内信息
				$s = new \Common\Model\User();	
				$s ->sendRegMsg($User);
				//$s = new \Common\Model\Sms();
				//$s->sendSms($username,'register_ok_send_user',C('agent_id'));
				
				//站点名称
				$site_name_md_arr[] = C('site_name');
				//发送短信
				$SMS = new \Common\Model\Sms();		
				$SMS->SendSmsByType($SMS::USER_REGIST_SUCCESS,$username,$site_name_md_arr);				
				
				
				
				$userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$username,'code'=>$code))->save(array('is_check'=>1));
				$this->result['ret']	= '100';
				$this->result['msg']	= '注册成功';
		} else {
			$this->result['ret']	= '201';
			$this->result['msg']	= '注册失败';
		}
        $this->echo_data($this->result['ret'],$this->result['msg']);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：获取注册的手机验证码
	 * time	：2016-11-8
	**/
	public function getCode() {
		$UM   		= M('user');
		$phone		= I('phone','','intval');
		$chars 		= '0123456789';
		$code  		= code(6,$chars);		//组装手机验证码
		//$code		= '123456';
		if(!is_mobile($phone)){
			$this->echo_data('207','手机号码格式不正确');
		}
		
		$checkUser	= $UM->where(array('agent_id'=>$this->agent_id,'username'=>$phone))->find();
		if($checkUser){
			$this->echo_data('208','该手机号已注册');
		}else{
			if($this->agent_id=='91'){
				$SMS = new \Common\Model\Sms();
				$SMS->SendSmsByType($SMS::USER_IDENTIFYING_CODE,$phone,array($code),3);
 
				$save['agent_id']	 = $this->agent_id; 
				$save['phone']		 = $phone; 
				$save['code']		 = $code; 
				$save['create_time'] = time();
				$save['is_check']	 = 0; 
				$action            	 = M('user_phone_code')->add($save);
				$this->echo_data('100','短信已发送');
				
			}else{
				$result = $this->sendCode($phone,$code);
			}	

			$this->echo_data($this->result['ret'],$this->result['msg']);
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：获取找回密码的手机验证码
	 * time	：2016-11-25
	**/
	public function getPasswordCode() {
		$UM   		= M('user');
		$phone		= I('phone','','intval');
		$chars 		= '0123456789';
		$code  	= code(6,$chars);		//组装手机验证码
		//$code		= '123456';
		if(!is_mobile($phone)){
			$this->echo_data('207','手机号码格式不正确');
		}

		$result = $this->sendCode($phone,$code);
		$this->echo_data($this->result['ret'],$this->result['msg']);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：发送手机验证码
	 * time	：2016-11-25
	**/
	public function sendCode($phone,$code){
		$userCode	= M('user_phone_code');
		$save['agent_id']	= $this->agent_id; 
		$save['phone']		= $phone; 
		$save['code']		= $code; 
		$save['create_time']= time();
		$save['is_check']	= 0; 
		$action	= $userCode->add($save);
		
		/* $this->result['ret']	= 100;
		$this->result['msg']	= '短信已发送';
		return true; */
		$content	= "您的验证码为:".$code;
		$obj = new \Common\Model\Sms();
		$r = $obj->sendE($phone, $content,true,3);
		if($r){
			$save['agent_id']	= $this->agent_id; 
			$save['phone']		= $phone; 
			$save['code']		= $code; 
			$save['create_time']= time();
			$save['agent_id']	 = $this->agent_id; 
			$save['phone']		 = $phone; 
			$save['code']		 = $code; 
			$save['create_time'] = time();
			$save['is_check']	 = 0; 
			$action            	 = $userCode->add($save);
			$this->echo_data('100','短信已发送');
		}else{
			$this->echo_data('232',$obj->error);
		} 
		
	}
	
	/**
	 * auth	：fangkai
	 * content：用户登录
	 * time	：2016-11-9
	**/
	public function login() {
		$UM   			= M('user');
	    $username		= I('username','');
	    $pwd			= I('password','');

		if($username && $pwd){
		    $checkUser		= $UM->where(array('agent_id'=>$this->agent_id,'username'=>$username))->find();
			if(empty($checkUser)){
				$this->echo_data('211','用户名或密码错误');
				return false;
			}
			if(pwdHash($pwd) == $checkUser['password']){
				$user_login_verify	= M('user_login_verify');
				$save['check_time']		= time();
				$save['last_activity_time']	= time();
				$key				= 'szzm'.$this->agent_id.$checkUser['uid'];
				$save['token']		= password_hash($key, PASSWORD_DEFAULT);
				$save['agent_id']	= $this->agent_id;
				$save['uid']		= $checkUser['uid'];
				//登录成功先删除旧的登录信息，保存新的登录信息
				$delete				= $user_login_verify->where(array('uid'=>$checkUser['uid'],'agent_id'=>$this->agent_id))->delete();
				$action				= $user_login_verify->add($save);
				$data				= array();
				$data[0]			= $save;
				$this->result['ret']	= '100';
				$this->result['msg']	= '登录成功';
				$this->result['data']	= $data;
			}else{
				$this->result['ret']	= '212';
				$this->result['msg']	= '用户名或密码错误';
			}
		}else{
			$this->result['ret']		= '213';
			$this->result['msg']		= '用户名或密码错误';
		}
		$this->echo_data($this->result['ret'],$this->result['msg'],$this->result['data']);
	   
   }
 
	/**
	 * auth	：fangkai
	 * content：用户退出
	 * time	：2016-11-10
	**/
    public function loginout() {
	  $user_login_verify	= M('user_login_verify');
	  $save['last_activity_time']	= time();
	  $save['check_time']			= '';
	  $save['token']				= '';
      $update	= $user_login_verify->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save($save);
	  $this->echo_data('100','退出成功');
    }
   
   /**
	 * auth	：fangkai
	 * content：用户信息
	 * time	：2016-11-9
	**/
	public function userInfo() {
		$this->checkTokenUid($this->uid,$this->token);
		$UM   	= M('user');
		$where['agent_id']	= $this->agent_id;
		$where['uid']		= $this->uid;
		$field				= 'uid,username,userimage,birthday,sex,email,phone,realname,job,company_name,legal,company_address';
		$userInfo[]			= $UM->field($field)->where($where)->find();
		$userInfo[0]['birthday']	= date('Y-m-d',$userInfo[0]['birthday']);
		$userInfo[0]['userimage']	= 'http://'.C('agent')['domain'].$userInfo[0]['userimage'];

		$this->echo_data('100','获取成功',$userInfo);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：用户信息保存
	 * time	：2016-11-9
	**/
	public function userInfoSave() {
		$this->checkTokenUid($this->uid,$this->token);
		$UM 		= M('user');
		$birthday  	= I('post.birthday','');
		$userSave['email'] 		= I('post.email','');
		$userSave['phone'] 		= I('post.phone','');
		$userSave['realname'] 	= I('post.realname','');
		$userSave['company_name'] = I('post.company_name','');
		$userSave['legal'] 		= I('post.legal','');
		$userSave['company_address'] = I('post.company_address','');
		$userSave['birthday'] 	= strtotime($birthday);
		$userSave['sex']		= I('post.sex','');
		if(!filter_var($userSave['email'], FILTER_VALIDATE_EMAIL) && $userSave['email'] ){
			$this->echo_data('216','邮箱格式不正确');
		}
		if(!is_mobile($userSave['phone']) && $userSave['phone']){
			$this->echo_data('217','手机号码格式不正确');
		}
		
		$action		= $UM->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save($userSave);
		if($action === false){
			$this->echo_data('214','保存失败');
		}else{
			$this->echo_data('100','保存成功');
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：用户地址列表
	 * time	：2016-11-9
	**/
	public function addressList() {
		$this->checkTokenUid($this->uid,$this->token);
		$S			= M('user_address');
		$join2		= 'zm_region AS r2 ON r2.region_id = s.province_id';
		$join3		= 'zm_region AS r3 ON r3.region_id = s.city_id';
		$join4		= 'zm_region AS r4 ON r4.region_id = s.district_id';
		$field		= 's.address_id,s.name,s.phone,s.is_default,s.address, s.province_id,s.city_id, s.district_id, r2.region_name AS province_name,r3.region_name AS city_name, r4.region_name AS district_name';
		$addressList= $S->alias('s')->field($field)->where(array('s.uid'=>$this->uid,'s.agent_id'=>$this->agent_id))->join($join)->join($join2)->join($join3)->join($join4)->order('address_id desc')->select();

		$this->echo_data('100','获取成功',$addressList);
	
	}
	
	/**
	 * auth	：fangkai
	 * content：设置默认地址
	 * time	：2016-11-10
	**/
	public function setDefaddress() {
		$this->checkTokenUid($this->uid,$this->token);
		$US						= M('user_address');
		$address_id				= I('address_id', '', 'intval');
		$where['uid']			= $this->uid;
		$where['agent_id']		= $this->agent_id;
		$where['address_id']	= $address_id;
		
		$checkAddress			= $US->where($where)->find();
		if(empty($checkAddress)){
			$this->echo_data('218','收货地址不存在');
		}
		$actionOne				= $US->where($where)->save(array('is_default'=>1));
		if($actionOne){
			$whereDef['uid']		= $this->uid;
			$whereDef['agent_id']	= $this->agent_id;
			$whereDef['address_id'] = array('neq',$address_id);
			$actionTwo				= $US->where($whereDef)->save(array('is_default'=>2));
			$this->echo_data('100','设置成功');
		}else{
			$this->echo_data('219','设置失败');
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：收货地址详情
	 * time	：2016-11-10
	**/
	public function addressInfo() {
		$this->checkTokenUid($this->uid,$this->token);
		$address		= M('user_address');
		$address_id		= I('address_id','','intval');
		$agent_id		= $this->agent_id;
		$checkaddress	= $address->where(array('address_id'=>$address_id,'agent_id'=>$agent_id))->find();
		if(!$checkaddress){
			$this->echo_data('220','收货地址不存在');
		}
		$join1 = 'zm_region AS r ON r.region_id = s.province_id';
		$join2 = 'zm_region AS r1 ON r1.region_id = s.city_id';
		$join3 = 'zm_region AS r2 ON r2.region_id = s.district_id';
		$field = 's.address_id,s.name,s.phone,s.is_default,s.address, r.region_name AS province_name,r1.region_name AS city_name, r2.region_name AS district_name';
		$addressinfo[] = $address->alias('s')->field($field)->where(array('uid'=>$this->uid,'agent_id'=>$agent_id,'address_id'=>$address_id))->join($join1)->join($join2)->join($join3)->order('s.address_id desc')->find();
		
		
		$this->echo_data('100','获取成功',$addressinfo);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：收货地址添加/修改
	 * time	：2016-11-10
	**/
	public function addressSave() {
		$this->checkTokenUid($this->uid,$this->token);
		$address				= M('user_address');
		$sData['uid']			= $this->uid;
		$sData['agent_id']		= $this->agent_id;
		$sData['province_id']	= I('province_id', '', 'htmlspecialchars');
		$sData['city_id']		= I('city_id', '', 'htmlspecialchars');
		$sData['district_id']	= I('district_id', '', 'htmlspecialchars');
		$sData['address']		= I('address', '', 'htmlspecialchars');
		$sData['name']			= I('name');
		$sData['phone']			= I('phone');
		$sData['is_default']	= I('is_default', '', 'intval');
		$sData['country_id']	= 1;
		$address_id				= I('address_id','','intval');
		if(empty($sData['name'])){
			$this->echo_data('221','收货人姓名不能为空！');
		}
		if(empty($sData['phone'])){
			$this->echo_data('221','联系方式不能为空！');
		}		
		if(!is_mobile($sData['phone'])){
			$this->echo_data('221','手机号码格式不正确');
		}
		if($address_id){
			//修改收货地址
			$checkaddress = $address->where(array('address_id'=>$address_id,'agent_id'=>$sData['agent_id']))->find();
			if(!$checkaddress){
				$this->echo_data('222','收货地址不存在');
			}
			$action = $address->where(array('address_id'=>$address_id,'agent_id'=>$sData['agent_id']))->save($sData);
			if($action){
				if($sData['is_default'] == 1){
					$action2 = $address->where(array('address_id'=>array('neq',$address_id),'agent_id'=>$sData['agent_id']))->save(array('is_default'=>2));
				}
				$this->echo_data('100','修改成功');
			}else{
				$this->echo_data('223','修改失败');
			}
		}else{
			//添加收货地址
			if ($sData['is_default'] == 1) { // 如果新添加的地址为默认的，则其它地址就要更新为非默认
				$address->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save(array('is_default'=>2));
			}
			
			$action	= $address->data($sData)->add();
			if ($action) {
				$this->echo_data('100','添加成功');
			}else{
				$this->echo_data('224','添加失败');
			}
		}
	
		
	}
	
	/**
	 * auth	：fangkai
	 * content：收货地址删除
	 * time	：2016-11-10
	**/
	public function addressDelete() {
		$this->checkTokenUid($this->uid,$this->token);
		$address		= M('user_address');
		$address_id		= I('address_id', '', 'intval');
		$checkaddress	= $address->fetchsql(true)->where(array('address_id'=>$address_id,'agent_id'=>$this->agent_id))->find();
		if(!$checkaddress){
			$this->echo_data('225','收货地址不存在');
		}
		
		$action			= $address->delete($address_id);
		if($action) {
			$this->echo_data('100','删除成功');
		} else {
			$this->echo_data('226','删除失败');
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：密码找回
	 * time	：2016-11-11
	**/
	public function passwordSave() {
		$userCode			= M('user_phone_code');
		$map['code']		= I('post.code','');
		$map['phone']		= I('phone','','intval');
		$map['new_password']= I('post.new_password', 'htmlspecialchars');
		$map['confirm_password']	= I('post.confirm_password', 'htmlspecialchars');
		$map['agreement']			= I('agreement','');
		$U = M('user');
		$uInfo = $U->field('*')->where(array('username'=>$map['phone'],'agent_id'=>$this->agent_id))->find();
		if(empty($uInfo)){
			$this->echo_data('230','该用户不存在');
			return false;
		}
		if(empty($map['new_password'])){
			$this->echo_data('202','密码不能为空');
			return false;
		}
		if(empty($map['confirm_password'])){
			$this->echo_data('203','确认密码不能为空');
			return false;
		}
		if(mb_strlen($map['new_password']) < 6){
			$this->echo_data('204','密码长度不能少于6个字符');
			return false;
		}
		if(mb_strlen($map['new_password']) > 20){
			$this->echo_data('204','密码长度不能超过20个字符');
			return false;
		}
		if ($map['new_password'] != $map['confirm_password']) {
			$this->echo_data('229','新密码与确认密码不一致');
			return false;
		}
		if(empty($map['agreement'])){
			$this->echo_data('231','请勾选用户协议');
			return false;
		}
		$codeInfo	= $userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$map['phone']))->order('id DESC')->find();
		if(empty($map['code']) || mb_strlen($map['code']) != 6 || $codeInfo['code'] != $map['code']){
			$this->echo_data('210','验证码错误');
			return false;
		}
		
		$pa['password'] = pwdHash(strval($map['new_password']));
		if ($U->where(array('agent_id'=>$this->agent_id,'username'=>$map['phone']))->save($pa) !== false) {
			$userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$map['phone'],'code'=>$map['code']))->save(array('is_check'=>1));
			$this->echo_data('100','密码修改成功');
		} else {
			$this->echo_data('227','密码修改失败');
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：密码修改
	 * time	：2016-12-2
	**/
	public function passwordChange() {
		$this->checkTokenUid($this->uid,$this->token);
		$map['old_password'] = I('post.old_password', 'htmlspecialchars');
		$map['new_password'] = I('post.new_password', 'htmlspecialchars');
		$map['confirm_password'] = I('post.confirm_password', 'htmlspecialchars');
		if(empty($map['old_password'])){
			$this->echo_data('231','旧密码不能为空');
		}
		if(empty($map['new_password'])){
			$this->echo_data('202','新密码不能为空');
		}
		if(empty($map['confirm_password'])){
			$this->echo_data('203','确认密码不能为空');
			return false;
		}
		if(mb_strlen($map['new_password']) < 6){
			$this->echo_data('204','密码长度不能少于6个字符');
			return false;
		}
		if(mb_strlen($map['new_password']) > 20){
			$this->echo_data('204','密码长度不能超过20个字符');
			return false;
		}
		$U = M('user');
		$uInfo = $U->field('*')->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->find();
		if(empty($uInfo)){
			$this->echo_data('230','该用户不存在');
		}
		if ($uInfo['password'] == pwdHash($map['old_password'])) {
			if ($map['new_password'] === $map['confirm_password'] ) {
				$pa['password'] = pwdHash(strval($map['new_password']));
				if ($U->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save($pa) !== false) {
					$user_login_verify	= M('user_login_verify');
					$key				= 'szzm'.$this->agent_id.$this->uid;
					$save['token']		= password_hash($key, PASSWORD_DEFAULT);
					$save['check_time']	= time();
					$save['agent_id']	= $this->agent_id;
					$save['uid']		= $this->uid;
					$save['last_activity_time']	= time();
					//修改密码成功先删除旧的登录信息，保存新的登录信息
					$update				= $user_login_verify->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save($save);
					$data[0]['token']	= $save['token'];
					
					$this->echo_data('100','密码修改成功',$data);
				} else {
					$this->echo_data('227','密码修改失败');
				}
			} else {
				$this->echo_data('228','新密码与确认密码不一致');
			}
		} else {
			$this->echo_data('229','原始密码不正确');
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：消息列表
	 * time	：2016-11-11
	**/
	public function messageList() {
		$this->checkTokenUid($this->uid,$this->token);
		$page			= I('page','','intval');
		$n 				= I('n','','intval');
		$limit			= ($page-1)*$n.','.$n;
		$user_msg		= M('user_msg');
		$where['msg_type']	= 1;
		$where['uid']		= $this->uid;
		$where['agent_id']	= $this->agent_id;
		$field				= 'msg_id,uid,title,content,FROM_UNIXTIME(create_time,'.'"%m-%d %H:%i"'.') as create_time,is_show';
		$messageList		= $user_msg->field($field)->where($where)->order('create_time DESC')->limit($limit)->select();
		foreach($messageList as $key=>$val){
			$messageList[$key]['create_time'] = date('Y-m-d');
		}
		$this->echo_data('100','获取成功',$messageList);
	}
	
	/**
	*	用户删除消息
	*	zhy		find404@foxmail.com
	*	2016年12月1日 17:09:39
	*/
	public function dmessageList() {
		$msg_id     	  = I("msg_id");
		$user_msg		  = M('user_msg');
		if(is_numeric($msg_id) && $this->uid && $this->agent_id){
			$where['msg_id']	= $msg_id;
			$messageinfo		= $user_msg->where($where)->find();
			if($messageinfo){
				if ($user_msg->where('msg_id = '.$msg_id .' and uid = '.$this->uid.' and agent_id = '.$this->agent_id)->delete()) {
				  	$code='100';
					$msg ='删除成功！';
				} else {
					$code='101';
					$msg ='删除失败！';
				}
			}else{
				$code='101';
				$msg ='没有此条数据！';
			}
		}else{
			$code='101';
			$msg ='接收数据有误！';
		}		
		$this->echo_data($code,$msg ,null);
	}
	
	
	/**
	 * auth	：fangkai
	 * content：用户图像上传
	 * time	：2016-11-30
	**/
	public function userImageUplode() {
		$this->checkTokenUid($this->uid,$this->token);
		$UM 		= M('user');
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize	= 3145728 ;// 设置附件上传大小
		$upload->exts		= array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath	= './Public/Uploads/userface/'; // 设置附件上传根目录
		$upload->savePath	= ''; // 设置附件上传（子）目录
		$upload->saveName 	= strval($this->uid);
		$upload->replace 	= true;
		
		// 上传文件
		$info   =   $upload->uploadOne($_FILES['userimage']);
		
		if($info){// 上传成功
			$userSave['userimage']	= $upload->rootPath.$info['savepath'].$upload->saveName.'.'.$info['ext'];
			$data[0]['userimage']	= 'http://'.C('agent')['domain'].$userSave['userimage'];
			$action		= $UM->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save($userSave);
			$this->echo_data('100','上传成功',$data);
		}else{
			$error	= $upload->getError();
			$this->echo_data('215',$error);
		}
		
	}
	
	
	//城市地区结构重组
	/* public function region() {
		$region	= M('region');
		$data	= $region->where(array('region_type'=>1))->select();

		foreach($data as $city_key=>$city_val){
			$data[$city_key]['city']	= $region->where(array('parent_id'=>$city_val['region_id']))->select();
			foreach($data[$city_key]['city'] as $district_key=>$district_val){
				$data[$city_key]['city'][$district_key]['district']	= $region->where(array('parent_id'=>$district_val['region_id']))->select();
			}
		}
		
		
		print_r(json_encode($data));exit;
		
		
		
	} */

	
	
	
}
