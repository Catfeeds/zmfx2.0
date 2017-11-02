<?php
namespace App\Controller;
class PublicController extends AppController{

    /**
     * 保存用户信息到SESSION
     * @param array $uInfo
     */
    private function saveSession($uInfo) {
        session('app.uid', $uInfo['uid']);
        session('app.username', $uInfo['username']);
        session('app.realname', $uInfo['realname']);
        session('app.phone', $uInfo['phone']);
        session('app.email', $uInfo['email']);
        session('app.rank_id', $uInfo['rank_id']);
        session('app.reg_time', $uInfo['reg_time']);
        session('app.last_logintime', $uInfo['last_logintime']);
        session('app.last_ip', $uInfo['last_ip']);  
        //session('app.is_validate', $uInfo['is_validate']);
		session('app.luozuan_discount', $uInfo['luozuan_discount']);
		session('app.sanhuo_discount', $uInfo['sanhuo_discount']);
		session('app.goods_discount', $uInfo['goods_discount']);
		session('app.consignment_discount', $uInfo['consignment_discount']);
		session('app_val.is_validate', $uInfo['is_validate']);
    }
    
    /**
     * 保存登陆日志
     * @param int $uid
     * @return boolean
     */ 
    private function saveLoginInfo($uid) {
        $loginInfo['login_ip'] = get_client_ip();
        $loginInfo['login_time'] = time();
        $loginInfo['browser'] = getUserBrowser();
        $loginInfo['uid'] = $uid;
        $loginInfo['client_type'] = getOs();
        $loginInfo['agent_id']    = C('agent_id');
        if (M('user_log')->data($loginInfo)->add()) {
            return true;
        }
        return false;
    }
    
    /**
     * 更新用户SESSION
     * @param int $userId
     */
    private function updateUserSession($userId){
        $where = "session='".$_COOKIE['PHPSESSID']."' AND agent_id='".C('agent_id')."' AND uid=0";
        $cartGoods = M('cart')->where($where)->select();
        if($cartGoods){
            foreach($cartGoods as $key=>$val){
                $cartGoods[$key]['uid'] = $userId;
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
     * 用户登录
     */
    public function login(){ 
        if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password');
            $rememberuser = I('post.rememberuser');
            if (!empty($username) && !empty($password)) {
                $UM = M('user');
                $where['username'] = $username;
                $where['agent_id'] = C('agent_id');                  
                $uInfo = $UM->where($where)->find();
                if ($uInfo && $uInfo['password'] === pwdHash(strval($password))) {
					if($uInfo['status']!='1'){
						$result['ret'] = 103;
						$result['msg'] = '账号已被锁定';
					}else{
						//指定cookie保存用户名和密码的时间
						if($rememberuser=='1'){
							cookie('username',urlencode($username),3600*24*30); 
							cookie('password',urlencode($password),3600*24*30); 
							cookie('rememberuser',1,3600*24*30); 
						}else{
							cookie('username',null); 
							cookie('password',null); 
							cookie('rememberuser',null); 
						}
						$this->saveSession($uInfo);
						$this->saveLoginInfo($uInfo['uid']);
						$this->updateUserSession($uInfo['uid']);
						$UM->where('uid='.$uInfo['uid'])->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip()));
						$result['ret'] = 100;
						$result['msg'] = '登陆成功！';
						$result['url'] = "/user/";
					}
                } else {
                    $result['ret'] = 102;
                    $result['msg'] = "帐号或密码不正确";
                }
            } else {
                $result['ret'] = 101;
                $result['msg'] = "请输入正确的帐号和密码";
            }
            $this->ajaxReturn($result);
        } else {
            $this->display();
        }
    }
    
    /**
     * 用户注册
     */
    public function register(){
        $this->catShow = 0;
        if (IS_POST) {
            $UM   = D('Common/User');
            
            $User['username']  = I('post.username');
            $User['email']     = I('post.email');
            $User['phone']     = I('post.phone');
            $User['password']  = I('post.password');

            $result = $UM->checkRegUserInfo($User);
            if($result !== true){
                $this->ajaxReturn($result);
                exit;
            }
           
            $User['reg_time']  = time();
            $User['last_logintime'] = time();
            $User['last_ip'] = get_client_ip();
            $User['rank_id'] = 0;
            $User['parent_type'] = $this->uType;
            $User['parent_id']   = $this->traderId; 
            $User['agent_id']    = C('agent_id'); 

            
            if ($UM->create($User)) {                 
                $User['password']  = pwdHash($User['password']);
                $insertId = $UM->data($User)->add();

                $userInfo = $UM->find($insertId);
                $this->saveLoginInfo($insertId);
                $this->saveSession($userInfo);
                $this->updateUserSession($userInfo['uid']);
				//注册成功发送邮件
				$User['uid'] = $insertId;
				$MessageModel   = new \Admin\Model\MessageModel();
				$message_status = $MessageModel->sendRegisterMsg($User);

				//发送站内信息
				$s = new \Common\Model\User();	
				$s->sendRegMsg($User);


                $result = array('ret'=>100,'msg'=>sprintf(L('L9012'), $User['username']), 'url'=>'/');
            } else {
                $error_str = $UM->getError();
                $result = array('success'=>false, 'msg'=>$error_str,'url'=>$_SERVER['HTTP_REFERER']);
            }
            $this->ajaxReturn($result);
        } else {
            $this->display();
        }
    }
    /**
     * 退出功能
     */
    public function logout(){
        unset($_SESSION['app']);
		$result = array('ret'=>100, 'msg'=>'', 'url'=>'/');
        $this->ajaxReturn($result);
    }

    /**
     * ajax根据上级ID获取地址，2省级3市区级4区县级
     * @param int $parent_id
     */
    public function getRegion($parent_id){
        $R = M('region');
        $data = $R->where('parent_id = '.I('get.parent_id'))->select();
        $this->ajaxReturn($data);
    }


    /**
     * 错误
     * @param string $msg
     */
    public function footer(){
		$this->display();
    }
}