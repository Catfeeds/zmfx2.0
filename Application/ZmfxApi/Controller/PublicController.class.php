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
                $where['parent_type'] = $this->domain['trader_type'];
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
                    $result['url'] = "/Goods/luozuan.html";
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

            $UM  = M('user');
            $uid = $UM->where( ' username = "'.I('post.username').'" and agent_id = '.C('agent_id'))->field('uid')->select();
            if(count($uid)!==0){
                $result = array('success'=>false, 'msg'=>'此用户名已被注册。','url'=>$_SERVER['HTTP_REFERER']);
                $this->ajaxReturn($result);
                die;
            }
            $uid = $UM->where( ' email = "'.I('post.email').'" and agent_id = '.C('agent_id'))->field('uid')->select();;
            if(count($uid)!==0){
                $result = array('success'=>false, 'msg'=>'邮箱已被其他账户使用。','url'=>$_SERVER['HTTP_REFERER']);
                $this->ajaxReturn($result);
                die;
            }
            $uid = $UM->where( ' phone = "'.I('post.phone').'" and agent_id = '.C('agent_id'))->field('uid')->select();;
            if(count($uid)!==0){
                $result = array('success'=>false, 'msg'=>'手机号已被其他账户注册。','url'=>$_SERVER['HTTP_REFERER']);
                $this->ajaxReturn($result);
                die;
            }

            $User['username']  = I('post.username');
            $User['email']     = I('post.email');
            $User['phone']     = I('post.phone');
            $User['password']  = I('post.password');
            $User['password']  = pwdHash($User['password']);
            $User['reg_time']  = time();
            $User['last_logintime'] = time();
            $User['last_ip'] = get_client_ip();
            $User['rank_id'] = 0;
            $User['parent_type'] = $this->uType;
            $User['parent_id']   = $this->traderId; 
            $User['agent_id']    = C('agent_id'); 
            $insertId = $UM->data($User)->add();
            if ($insertId) {
                $userInfo = $UM->find($insertId);
                $this->saveLoginInfo($insertId);
                $this->saveSession($userInfo);
                $this->updateUserSession($userInfo['uid']);
                $result = array(
                    'success'=>true,
                    'msg'=>sprintf(L('L9012'), $User['username']),
                    'url'=>'/User/index.html');
            } else {
                $result = array('success'=>false, 'msg'=>L('L9016'),'url'=>$_SERVER['HTTP_REFERER']);
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
        $this -> success('你已经退出登录！','/',3);
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