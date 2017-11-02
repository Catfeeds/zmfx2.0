<?php
/**
 * 用户相关操作类
 */
namespace Home\Controller;
use Think\Page;
class NewPublicController extends NewHomeController{

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
    // 用户登陆
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
                    $result['url'] = "/Home/Index/index.html";
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
			$LoginAdList		= M ('template_ad')->where(' status = 1 and ads_id = 4 and agent_id = '. C('agent_id'))->order('sort ASC')->find();
			if($LoginAdList)	$this->LoginAdList	= $LoginAdList;
			else				$this->LoginAdList	= null;
			
			//检查是否需要第三方登录
			$openConfig=M('agent_open')->where("agent_id=%d",C('agent_id'))->find();
			$this->OpenConfig	= $openConfig;
			
			
            $this->display();
        }
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
    // 检查用户名
    public function checkusername() {
        $UM = M('user');
		$username = I('username','');
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
        
    }
    // 注册
    public function regist() {
        $this->catShow = 0;
        if (IS_POST) {
            $UM   = D('Common/User');
            $User = I('post.');

            $User['username']  = I('post.username');
            $User['email']     = I('post.email');
            $User['phone']     = I('post.phone');
            $User['password']  = I('post.password');

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
				$User['uid'] = $insertId;
				//注册成功发送邮件
				/*if($User['email']){
				$MessageModel   = new \Admin\Model\MessageModel();
				$message_status = $MessageModel->sendRegisterMsg($User);
				}*/
                //这里是给分销商发邮件不懂就不要乱改
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
                    'url'=>'/Home/Index/index');
            } else {           
                $error_str = $UM->getError();
                $result = array('success'=>false, 'msg'=>$error_str,'url'=>$_SERVER['HTTP_REFERER']);
            }
            $this->ajaxReturn($result);
        } else {
			$RegistAdList		= M ('template_ad')->where(' status = 1 and ads_id = 42 and agent_id = '. C('agent_id'))->order('sort ASC')->find();
			if($RegistAdList)	$this->RegistAdList	= $RegistAdList;
			else				$this->RegistAdList	= null;
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
     * ajax根据上级ID获取地址，2省级3市区级4区县级
     * @param int $parent_id
     */
    public function getRegion($parent_id){
    	$R = M('region');
    	$data = $R->where('parent_id = '.I('get.parent_id'))->select(); 
    	$this->ajaxReturn($data);
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

    public function get_curcatname(){
        $str = $_SERVER['PHP_SELF'];
        if($_SERVER['QUERY_STRING']){
            $str .= '?'.$_SERVER['QUERY_STRING'];
        }
        $cat_info = M('nav')->where( ' agent_id = '.C('agent_id').' and nav_url = "'+$str+'"' )->find();
        //if(){

        //}
    }
	
	/**
	*	用户体验页面
	*	zhy
	*	2016年11月3日 16:57:35
	*/
	public function exper(){
		   layout(false);
		   $this->display();
	}
}