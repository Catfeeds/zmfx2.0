<?php
namespace Mobile\Controller;
class PublicController extends MobileController{

    /**
     * 保存用户信息到SESSION
     * @param array $uInfo
     */
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
            if (!empty($username) && !empty($password)) {
                $UM = M('user');
                $map['username'] = $username;
                $map['phone']= $username;
                $map['email']= $username;
                $map['_logic']= 'OR';
                $where['_complex'] = $map;
                $where['status'] = array('eq',1);  
                $where['agent_id'] = C('agent_id');  
                $uInfo = $UM->where($where)->find();
                if ($uInfo && $uInfo['password'] === pwdHash(strval($password))) {
                    $this->saveSession($uInfo);
                    $this->saveLoginInfo($uInfo['uid']);
                    $this->updateUserSession($uInfo['uid']);
                    $UM->where('uid='.$uInfo['uid'])->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip()));
                    $result['success'] = true;
                    $result['msg'] = '登陆成功！';
                    $result['url'] = "/Index/index.html";
                } else {
                    $result['success'] = false;
                    $result['msg'] = L('L9104');
                }
            } else {
                $result['success'] = false;
                $result['msg'] = L('L9097');
            }
            $this->ajaxReturn($result);
        } else {
            $this->display();
        }
    }
 
    /**
     * 用户注册
     */
    public function regist(){
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
				
				//站点名称
				$site_name_md_arr[] = C('site_name'); 	
				//发送短信
				$SMS = new \Common\Model\Sms();		
				$SMS->SendSmsByType($SMS::USER_REGIST_SUCCESS,$User['phone'],$site_name_md_arr);		
				
                $result = array(
                    'success'=>true,
                    'msg'=>sprintf(L('L9012'), $User['username']),
                    'url'=>'/User/index.html');
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
    public function setSignOut(){
        unset($_SESSION['m']);
		$_SESSION['setSignOut_tips']='1';
		$this->redirect('Index/index');  
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
}