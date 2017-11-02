<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/22
 * Time: 17:18
 */
namespace Mobile\Controller;
class NewPublicController extends NewMobileController{

	/**
	 * auth	：zengmingming
	 * content：构造函数
	 * time	：2017-07-25
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
	}

    function test(){
        echo 'qqqx';
    }
	
	/**
	 * auth	：zengmingming
	 * content：用户登陆
	 * time	：2017-7-25
	 * updatetime:2017-7-25
	**/
    public function login() {
		 if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password','', 'htmlspecialchars');
            if (!empty($username) && !empty($password)) {
                $UM = M('user');
                $map['username'] = $username;
                $map['phone']= $username;
                $map['email']= $username;
                $map['_logic']= 'OR';
                $where['_complex'] = $map;
                $where['status'] = array('eq',1); 
                $where['agent_id'] = C("agent_id");
                $uInfo = $UM->where($where)->find(); 
                if ($uInfo && $uInfo['password'] === pwdHash($password)) {
                    $this->saveSession($uInfo);
                    $this->saveLoginInfo($uInfo['uid']);
                    $this->updateUserSession($uInfo['uid']);
                    $UM->where('uid='.$uInfo['uid'].' and agent_id = '.C('agent_id'))->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip()));
                    $result['success'] = true;
                    $result['msg'] = '登陆成功！';
                    $result['url'] = "/Index/index.html";
                } else {
                    $result['success'] = false;
                    $result['msg'] = "用户名和密码错误！";
                }
            } else {
                $result['success'] = false;
                $result['msg'] = "用户名或密码不能为空，请检查";
            } 
            $this->ajaxReturn($result);
        } else {
            $this->display();
        }
	}
	// 保存用户信息到session
    private function saveSession($uInfo) {
        session('m.uid', $uInfo['uid']);
        session('m.username', $uInfo['username']);
        session('m.realname', $uInfo['realname']);
        session('m.phone', $uInfo['phone']);
        session('m.email', $uInfo['email']);
        session('m.rank_id', $uInfo['rank_id']);
        session('m.reg_time', $uInfo['reg_time']);
        session('m.last_logintime', $uInfo['last_logintime']);
        session('m.last_ip', $uInfo['last_ip']);
        session('m.is_validate', $uInfo['is_validate']);
    }
	public function updateUserSession($userId){
        $where = "session='".$_COOKIE['PHPSESSID']."' AND agent_id='".C('agent_id')."' AND uid=0";
        $cartGoods = M('cart')->where($where)->select();
        if($cartGoods){
            foreach($cartGoods as $key=>$val){
                $cartGoods[$key]['uid'] = $userId;
                $cartGoods[$key]['agent_id'] = C('agent_id');
                M('cart')->data($cartGoods[$key])->save();
            }
        }
        $historys = M("history")->where($where)->select();
        if($historys){
            foreach($historys as $key=>$val){
                $historys[$key]['uid'] = $userId;
                M("history")->data($historys[$key])->save();
            }
        }
    }
	
	/**
	 * auth	：zengmingming
	 * content：用户注册
	 * time	：2017-7-25
	**/
	public function regist(){
        if (IS_POST) {
			$UM   			= M('user');
			$userCode		= M('user_phone_code');
			$username		= I('username','','number_int');
			$code			= I('code','');
			$pwd			= I('password','');
			$confirm_pwd	= I('confirm_password','');
			$agreement		= I('agreement','');
			if(!is_mobile($username)){
				$result = array('success'=>false, 'msg'=>"手机号码格式不正确");
            	$this->ajaxReturn($result);
			}
			$checkUser	= $UM->fetchsql(false)->where("(agent_id=".$this->agent_id." and username='".$username."') or (agent_id=".$this->agent_id." and phone='".$username."')")->field("uid")->find();
			if($checkUser["uid"]){
				$result = array('success'=>false, 'msg'=>"该手机号已注册");
            	$this->ajaxReturn($result);
			}
			if(empty($pwd)){
				$result = array('success'=>false, 'msg'=>"密码不能为空");
            	$this->ajaxReturn($result);
			}
			if(empty($confirm_pwd)){
				$result = array('success'=>false, 'msg'=>"确认密码不能为空");
            	$this->ajaxReturn($result);
			}
			if(mb_strlen($pwd) < 6){
				$result = array('success'=>false, 'msg'=>"密码长度不能少于6个字符");
            	$this->ajaxReturn($result);
			}
			if(mb_strlen($pwd) > 20){
				$result = array('success'=>false, 'msg'=>"密码长度不能超过20个字符");
            	$this->ajaxReturn($result);
			}
			if($pwd != $confirm_pwd){
				$result = array('success'=>false, 'msg'=>"密码和确认密码不一致");
            	$this->ajaxReturn($result);
			}
			if(empty($agreement)){
				$result = array('success'=>false, 'msg'=>"请勾选用户协议");
            	$this->ajaxReturn($result);
			}
			$codeInfo	= $userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$username))->order('id DESC')->find();
			if(empty($code) || mb_strlen($code) != 6 || $codeInfo['code'] != $code){
				$result = array('success'=>false, 'msg'=>"验证码错误");
            	$this->ajaxReturn($result);
			}

			$User['username']  	= $username;
			$User['phone']  	= $username;
			$User['password']  	= pwdHash($pwd);
			$User['reg_time'] 	= time();
			$User['last_logintime'] = time();
			$User['last_ip'] 	= get_client_ip();
			$User['rank_id'] 	= 0;
			$User['parent_type']= $this->uType;
			$User['agent_id'] 	= $this->agent_id;
			$insertId = $UM->data($User)->add();
			if ($insertId) {
					//发送站内信息
					$s = new \Common\Model\User();	
					$s ->sendRegMsg($User);
					
					//站点名称
					$site_name_md_arr[] = C('site_name'); 	
					//发送短信
					$SMS = new \Common\Model\Sms();		
					$SMS->SendSmsByType($SMS::USER_REGIST_SUCCESS,$username,$site_name_md_arr);				
					
					$userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$username,'code'=>$code))->save(array('is_check'=>1));
					$result = array('success'=>true, 'msg'=>"注册成功",'url'=>"/Public/login.html");	
					
			} else {
				$result = array('success'=>false, 'msg'=>"注册失败");	
			}
            $this->ajaxReturn($result);
        } else {
            $this->display();
        }
	}
	/**
	 * auth	：zengmingming 
	 * content：获取注册的手机验证码
	 * time	：2017-07-25
	**/
	public function getCode() {
		$UM   		= M('user');
		$phone		= I('phone','','number_int');
		$chars 		= '0123456789';
		$code  		= code(6,$chars);		//组装手机验证码
		if(!is_mobile($phone) || $phone==0){
			$result = array('success'=>false, 'msg'=>"手机号码格式不正确");	
		}else{
			$checkUser	= $UM->where("(agent_id=".$this->agent_id." and username='".$phone."') or (agent_id=".$this->agent_id." and phone='".$phone."')")->field("uid")->find();
			if($checkUser["uid"]){
				$result = array('success'=>false, 'msg'=>"该手机号已注册");	
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
					$result = array('success'=>false, 'msg'=>"短信已发送");
				}else{
					$result = $this->sendCode($phone,$code);
				}	
			}
		}
		$this->ajaxReturn($result);
	}
	/**
	 * auth	：zengmingming
	 * content：发送手机验证码
	 * time	：2017-07-25
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
			//$this->echo_data('100','短信已发送');
			$result = array('success'=>true, 'msg'=>"短信已发送");
		}else{
			$result = array('success'=>false, 'msg'=>$obj->error);
		} 
		return $result;
	}
	
	/**
	 * auth	：zengmingming
	 * content：密码找回
	 * time	：2017-7-25
	**/
	public function passwordSave(){
		if (IS_POST) {	
			$userCode			= M('user_phone_code');
			$map['code']		= I('post.code','');
			$map['phone']		= I('phone','','number_int');
			$map['new_password']= I('post.new_password', 'htmlspecialchars');
			$map['confirm_password']	= I('post.confirm_password', 'htmlspecialchars');
			$map['agreement']			= I('agreement','');
			$U = M('user');
			if(!is_mobile($map['phone'])){
				$result = array('success'=>false, 'msg'=>"手机号码格式不正确");
				$this->ajaxReturn($result);
			}
			$uInfo = $U->where("(agent_id=".$this->agent_id." and username='".$map['phone']."') or (agent_id=".$this->agent_id." and phone='".$map['phone']."')")->field('*')->find();
			if(empty($uInfo["uid"])){
				$result = array('success'=>false, 'msg'=>"该用户不存在");
				$this->ajaxReturn($result);
			}
			if(empty($map['new_password'])){
				$result = array('success'=>false, 'msg'=>"密码不能为空");
				$this->ajaxReturn($result);
			}
			if(empty($map['confirm_password'])){
				$result = array('success'=>false, 'msg'=>"确认密码不能为空");
				$this->ajaxReturn($result);
			}
			if(mb_strlen($map['new_password']) < 6){
				$result = array('success'=>false, 'msg'=>"密码长度不能少于6个字符");
				$this->ajaxReturn($result);
			}
			if(mb_strlen($map['new_password']) > 20){
				$result = array('success'=>false, 'msg'=>"密码长度不能超过20个字符");
				$this->ajaxReturn($result);
			}
			if ($map['new_password'] != $map['confirm_password']) {
				$result = array('success'=>false, 'msg'=>"新密码与确认密码不一致");
				$this->ajaxReturn($result);
			}
			$codeInfo	= $userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$map['phone']))->order('id DESC')->find();
			if(empty($map['code']) || mb_strlen($map['code']) != 6 || $codeInfo['code'] != $map['code']){
				$result = array('success'=>false, 'msg'=>"验证码错误");
				$this->ajaxReturn($result);
			}
			$pa['password'] = pwdHash(strval($map['new_password']));
			if ($U->where(array('agent_id'=>$this->agent_id,'username'=>$map['phone']))->save($pa)==true) {
				//通过用户名修改
				$userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$map['phone'],'code'=>$map['code']))->save(array('is_check'=>1));
				$result = array('success'=>true, 'msg'=>"密码修改成功",'url'=>"/Public/login.html");
			} else {
				//用户名不为手机号的时候，通过手机号修改
				if($U->where(array('agent_id'=>$this->agent_id,'phone'=>$map['phone']))->save($pa) !== false){
					$userCode->where(array('agent_id'=>$this->agent_id,'phone'=>$map['phone'],'code'=>$map['code']))->save(array('is_check'=>1));
					$result = array('success'=>true, 'msg'=>"密码修改成功",'url'=>"/Public/login.html");
				}else{
					$result = array('success'=>false, 'msg'=>"密码修改失败");
				}
			}
			$this->ajaxReturn($result);
		}else{
			$this->display();
		}
	}
	/**
	 * auth	：zengmingming
	 * content：获取找回密码的手机验证码
	 * time	：2017-07-26
	**/
	public function getPasswordCode() {
		$UM   		= M('user');
		$phone		= I('phone','','number_int');
		$chars 		= '0123456789';
		$code  	= code(6,$chars);		//组装手机验证码
		//$code		= '123456';
		if(!is_mobile($phone)){
			$result = array('success'=>false, 'msg'=>"手机号码格式不正确");
		}else{
			$U = M('user');
			$uInfo = $U->where("(agent_id=".$this->agent_id." and username='".$phone."') or (agent_id=".$this->agent_id." and phone='".$phone."')")->field('*')->find();
			if(empty($uInfo["uid"])){
				$result = array('success'=>false, 'msg'=>"该用户不存在");
			}else{
				$result = $this->sendCode($phone,$code);
			}
		}
		$this->ajaxReturn($result);
	}
	
	
    public function loginout() {
        session('m', null);
        $this->uId = null;
        cookie('autologin',null);
        $this->ajaxReturn(array('iden'=>100, 'msg'=>'退出成功！'));
    }
	
    public function addCollection() {
        $type_from = I('type_from',0,'intval');
        $param = array();
        $continue = true;
        switch($type_from){
            case 1:
                $param = array(
                    'gid' =>I("gid", 0),
                    'goods_type'=>I('goods_type',0)
                );
                break;
            case 2:
                $param = array(
                    'gid' =>I("gid", 0),
                    'goods_type'=>I('goods_type',0)
                );
                break;
            case 3:
                $param = array(
                    'gid'           => I("gid", 0),
                    'goods_type'    => 3,
                    'id_type'       => I('id_type', ''),
                    'sku_sn'    => I("sku_sn",0),
                    'goods_number'     => I("goods_number",1,'intval'),
                );
                break;
            case 4:
                $param = array(
                    'gid'           => I("gid", 0),
                    'goods_type'    => 4,
                    'id_type'       => I('id_type', ''),
                    'materialId'    => I("materialId", 0),
                    'diamondId'     => I("diamondId", 0),
                    'deputystoneId' => I("deputystoneId", 0),
                    'hand'          => I("hand", ""),
                    'word'          => I("word", ""),
                    'sd_id'         => I('sd_id', ''),
                    'goods_number'  => I("goods_number", 1),
                    'luozuan'       => I("luozuan", 0),
                    'word1'         => I("word1", ""),
                    'hand1'         => I("hand1", ""),
                    'sd_id1'        => I("sd_id1", "")
                );
                break;
            default:
                $continue = false;
                break;
        }
        if($continue){
            $param['uid'] = $_SESSION['m']['uid'];
			$coll 		  = M('collection');
			$where 		  = ' uid='.$param['uid'].' AND goods_id='.$param['gid'].' AND goods_type='.$param['goods_type'];
			if($coll->where($where)->getField('1')){
				if($coll->where($where)->delete()){
					$result['iden'] = 100;
					$result['msg']  = '取消收藏成功！';
				}else{
					$result['iden'] = 101;
					$result['msg']  = '取消收藏失败！';
				}
			}else{
				$result = D('Common/Collection')->addCollection($param);
				if($result['status']=='100'){
					$result['iden'] = 100;
					unset ($result['status']);
				}
			}
        }else{
            $result = array(
                'info'   => '操作失败',
                'status' => 0
            );
        }
        $this->ajaxReturn($result);
 
		
	}	

	
	
	
}