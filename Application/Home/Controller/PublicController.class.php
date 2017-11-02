<?php
/**
 * 用户相关操作类
 */
namespace Home\Controller;
use Think\Page;
class PublicController extends HomeController{
	
    // 保存用户信息到session
    private function saveSession($uInfo) {
        session('web.uid', $uInfo['uid']);
        session('web.username', $uInfo['username']);
        session('web.realname', $uInfo['realname']);
        session('web.phone', $uInfo['phone']);
        session('web.email', $uInfo['email']);
        session('web.rank_id', $uInfo['rank_id']);
        session('web.reg_time', $uInfo['reg_time']);
        session('web.last_logintime', $uInfo['last_logintime']);
        session('web.last_ip', $uInfo['last_ip']);
        session('web.is_validate', $uInfo['is_validate']);
    }
    
    
    //qq微信登录-------------------------------------------------------------------------------- by wxh 20171014---------------------------start
    //1发起qq登录,发起的地方跳转到这个地址
    public function qqloginstart(){ 
        $agent_id=C('agent_id');
        $openConfig=M('agent_open')->where("agent_id=%d",$agent_id)->find();          
        $qqobj=new \Org\Util\QQLogin($openConfig['qqappid'],$openConfig['qqappsecret']);
        $url=$qqobj->get_login_url(); 
        if(empty(session("login_url"))){
            session("login_url", $_SERVER['HTTP_REFERER']);
        }
        header("Location:".$url);
        exit();
    }
    
    //2qq登录回调
    public function qqlogin(){    
        $agent_id=C('agent_id');
        $openConfig=M('agent_open')->where("agent_id=%d",$agent_id)->find();
        
        $qqobj=new \Org\Util\QQLogin($openConfig['qqappid'],$openConfig['qqappsecret']);
        $access_token = $qqobj->get_access_token($_GET['code']);        
        $openidObj=$qqobj->get_user_openid($access_token['access_token']);  
        $openid=$openidObj['openid'];
        $agent_id=C('agent_id');
        //检查是否登录过,使用zm_user_open表
        $uInfo=M('user_open')
        ->join("zm_user on zm_user_open.uid=zm_user.uid")
        ->where("zm_user_open.qq_openid='%s' and zm_user.agent_id='%s' and zm_user.status=1",$openid,$agent_id)
        ->field("zm_user.*")
        ->find();
        if($uInfo){
            $this->saveSession($uInfo);
            $this->saveLoginInfo($uInfo['uid']);
            $this->updateUserSession($uInfo['uid']);
            M('user')->where('uid='.$uInfo['uid'].' and agent_id = '.$agent_id)->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip())); 
            $url=U('/Home/index');
            if(session("login_url")){
                $url=session("login_url");
            }
            header("Location:".$url);
            exit();            
        }else{
            session("qqopenid",$openid);
            header("Location:".U('qqbind'));
            exit();
        }
    }
  
    //3绑定保存ajax,需要验证码吗？
    public function qqbind($is_new=0){
        if (IS_POST) {           
            if($is_new){
                $this->regist();
            }else{
                $this->login();
            }
        }else{
            $this->display();
        }
    }
   
    //1发起微信登录,发起的地方跳转到这个地址
    public function wxloginstart(){
        $agent_id=C('agent_id');
        $openConfig=M('agent_open')->where("agent_id=%d",$agent_id)->find();
        
        $wxobj=new \Org\Util\WeChatLogin($openConfig['wxappid'],$openConfig['wxappsecret']);
        $url=$wxobj->get_login_url();
        if(empty(session("login_url"))){
            session("login_url", $_SERVER['HTTP_REFERER']);
        }
        header("Location:".$url);
        exit();
    }
    
    //2wx登录回调
    public function wxlogin(){
        $agent_id=C('agent_id');
        $openConfig=M('agent_open')->where("agent_id=%d",$agent_id)->find();
        $wxobj=new \Org\Util\WeChatLogin($openConfig['wxappid'],$openConfig['wxappsecret']);
        $access_token = $wxobj->get_access_token($_GET['code']);        
        $openid=$access_token['openid'];
        $agent_id=C('agent_id');
        //检查是否登录过,使用zm_user_open表
        $uInfo=M('user_open')
        ->join("zm_user on zm_user_open.uid=zm_user.uid")
        ->where("zm_user_open.wx_openid='%s' and zm_user.agent_id='%s' and zm_user.status=1",$openid,$agent_id)
        ->field("zm_user.*")
        ->find();
        if($uInfo){
            $this->saveSession($uInfo);
            $this->saveLoginInfo($uInfo['uid']);
            $this->updateUserSession($uInfo['uid']);
            M('user')->where('uid='.$uInfo['uid'].' and agent_id = '.$agent_id)->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip()));
            $url=U('/Home/index');
            if(session("login_url")){
                $url=session("login_url");
            }
            header("Location:".$url);
            exit();
        }else{
            session("wxopenid",$openid);
            header("Location:".U('wxbind'));
            exit();
        }
    }    
    
    //3微信绑定
    public function wxbind($is_new=0){
       if (IS_POST) {
            if($is_new){
                $this->regist();
            }else{
                $this->login();
            }
        }else{
            
            $this->display();
        }
    }
             
    //登录注册时检查第三方登录进行绑定
    private function checkbind($agent_id,$uid){
        $data['uid']=$uid;
        $data['agent_id']=$agent_id;
    
        $qqopenid=session("qqopenid");
        if(!empty($qqopenid)){
            $data['qq_openid']=$qqopenid;
        }
        $wxopenid=session("wxopenid");
        if(!empty($wxopenid)){
            $data['wx_openid']=$wxopenid;
        }
        
        if($qqopenid||$wxopenid){
            $id=M('user_open')->where("uid=%d and agent_id=%d",$uid,$agent_id)->getField("id");
            if($id){
                M('user_open')->where("id=%d",$id)->save($data);
            }else{
                M('user_open')->add($data);
            }
        }
    }
    
    //微信登录----------------------------------------------------------------------------------------------------------------------------end
    
    
    // 用户登陆
    public function login() {
        if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password','', 'htmlspecialchars');
            //&& !empty($password)
            if (!empty($username) ) {
                $UM = M('user');
                $map['username'] = $username;
                $map['phone']= $username;
                $map['email']= $username;
                $map['_logic']= 'OR';
                $where['_complex'] = $map;
                $where['status'] = array('eq',1); 
                $where['agent_id'] = C("agent_id");
                $uInfo = $UM->where($where)->find(); 
                $uInfo['password']?$uInfo['password'] = $uInfo['password']:$uInfo['password'] = pwdHash('');
                if ($uInfo['uid'] && $uInfo['password'] === pwdHash($password)) {
                    $this->saveSession($uInfo);
                    $this->saveLoginInfo($uInfo['uid']);
                    $this->updateUserSession($uInfo['uid']);
                    $UM->where('uid='.$uInfo['uid'].' and agent_id = '.C('agent_id'))->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip()));
                    $result['success'] = true;
                    $result['msg'] = '登陆成功！';
                    $result['url'] = "/Home/Index/index.html";
                } else {
                    $result['success'] = false;
                    $result['msg'] = L('L9104');
                }
            } else {
                $result['success'] = false;
                $result['msg'] = L('L9097');
            }
            
            //如果有登录前网址，直接跳转,by wxh upt 20171017
            if(session("login_url")){
                header("Location:".session("login_url"));
                exit();
            }else{
                $this->ajaxReturn($result);
            }            
            
        } else {
            if(C('new_rules')['zgoods_show']){
                $this->login_new();exit;
            }
			/* 登陆页背景广告图 */
			$TA = M ('template_ad');
			$where['agent_id'] 	= C('agent_id');
			$where['status']	= 1;
			$where['ads_id']	= 4;
			$LoginAdList		= $TA->where($where)->order('sort ASC')->select();
			$this->LoginAdList	= $LoginAdList;
			
			//检查是否需要第三方登录			
			$openConfig=M('agent_open')->where("agent_id=%d",C('agent_id'))->find();
			$this->OpenConfig	= $openConfig;	
			
            $this->display();
        }
    }
    public function login_new(){
        $TA = M ('template_ad');
        $where['agent_id']  = C('agent_id');
        $where['status']    = 1;
        $where['ads_id']    = 4;
        $LoginAdList        = $TA->where($where)->order('sort ASC')->select();
        $this->LoginAdList  = $LoginAdList;
        $this->display('dingzhi/zhouliufu/Index/login');
    }
    
    public function loginByCookie(){
    	$username = getEncodeCookie(I('cookie.username'));
    	$password = getEncodeCookie(I('cookie.password'));
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
    		} 
    	}
    	$this->display('Index:index');
    }
    
    /*public function updateUserSession($userId){
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
    }*/

    public function updateUserSession($userId){
        if($userId<=0 || empty($_COOKIE['PHPSESSID'])){
            return false;
        }
        $agent_id = C('agent_id');
        
        //检查第三方登录并绑定 ，by wxh 20171014
        $this->checkbind($agent_id, $userId);         
        
        $where = array(
            'session'=>$_COOKIE['PHPSESSID'],
            'agent_id'=>$agent_id,
            'uid'=>0
        );
        $save = array(
            'uid'=>$userId,
            'session'=>'',
        );
        $cartGoods = M('cart')->where($where)->find();
        if($cartGoods){
            M('cart')->where($where)->save($save);
        }
        $cartBanfangGoods = M('banfang_cart')->where($where)->find();
        if($cartBanfangGoods){

            M('banfang_cart')->where($where)->save($save);
        }
        $historys = M("history")->where($where)->find();
        if($historys){
            M('history')->where($where)->save($save);
        }
    }
    // 检查用户名
    public function checkusername($username ='') {
        $UM = M('user');
        if($username == ''){
        	$User = I('User');   
        	$username = trim($User['username']);
        }
        $uInfo = $UM->where("username='".$username."' and agent_id=".C("agent_id"))->find();
        if ($uInfo) {
            $result = array('success'=>false, 'msg'=>L('L823'),'error'=>true);
        } else {
            $result = array('success'=>true, 'msg'=>L('L9001'),'error'=>false);
        }
        $this->ajaxReturn($result);
    }
    // 注册
    public function regist() {
    	$this->catShow = 0;
        if (IS_POST) {
            $UM   = D('Common/User');
            $User = $_POST['User'];

            $result = $UM->checkRegUserInfo($User);
            if($result !== true){
                $this->ajaxReturn($result);
                exit;
            }


            $User['reg_time'] = time();
            $User['last_logintime'] = time();
            $User['last_ip'] = get_client_ip();
            $User['rank_id'] = 0;
            $User['parent_type'] = $this->uType;
            $User['agent_id'] = C("agent_id");

            if ($UM->create($User)) {
                $User['password']  = pwdHash($User['password']);
                $insertId = $UM->data($User)->add();
                $userInfo = $UM->find($insertId);
                $this->saveLoginInfo($insertId);
                $this->saveSession($userInfo);  
                $this->updateUserSession($userInfo['uid']);  
				//注册成功发送邮件
				$User['uid'] = $insertId;
				/*if($User['email']){
					$MessageModel   = new \Admin\Model\MessageModel();
					$message_status = $MessageModel->sendRegisterMsg($userInfo);
				}*/
                $MessageModel   = new \Admin\Model\MessageModel();
                $message_status = $MessageModel->sendRegisterMsg($userInfo);
				
				//站点名称
				$site_name_md_arr[] = C('site_name'); 
				//发送短信
				$SMS = new \Common\Model\Sms();					
				$SMS->SendSmsByType($SMS::USER_REGIST_SUCCESS,$User['phone'],$site_name_md_arr);
				
				//发送站内信息
				$s = new \Common\Model\User();	
				$s->sendRegMsg($User);

                $result = array(
                    'success'=>true,
                    'msg'=>sprintf(L('L9012'), $User['username']),
                    'url'=>'/Home/User/index');
            } else {
                $error_str = $UM->getError();
                $result = array('success'=>false, 'msg'=>$error_str,'url'=>$_SERVER['HTTP_REFERER']);
            }
            
            //如果有登录前网址，直接跳转,by wxh upt 20171017
            if(session("login_url")){
                header("Location:".session("login_url"));
                exit();
            }else{
                $this->ajaxReturn($result);
            }  
        } else {
            $this->display();
        }
    }
    // 用户退出
    public function loginout() {
      session('web', null);
      $this->uId = null;
      cookie('autologin',null);
      $this->ajaxReturn(array('success'=>true, 'msg'=>L('L9014')));
      //cookie('name',null);
    }
    // 找回密码
    public function forgetps() {
        $this->display();
    }
    
  
    /**
   * 递归数组
   * @param array $data 数组对象
   * @param int $id 一条记录的id
   * @param int $pid 上级id
   * @param int $objId 开始的id
   * @return multitype:multitype:unknown
   	*/
	function _arrayRecursive($data,$id,$pid,$objId=0){
	  $list = array();
	  foreach ($data AS $key => $val){
	      if($val[$pid] == $objId){
	          $val['sub'] = _arrayRecursive($data, $id, $pid, $val[$id]);
	          if(empty($val['sub'])){unset($val['sub']);}
	          $list[$val[$id]] = $val;
	      }
	  }
	  return $list;
	}
	
	
	/**
	*	用户体验页面
	*	zhy
	*	2016年11月3日 16:57:35
	*/
	public function exper(){
		   $this->display();
		
	}
    
}