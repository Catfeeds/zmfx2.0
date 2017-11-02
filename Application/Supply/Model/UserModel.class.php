<?php
namespace Supply\Model;
use Think\Model;
class UserModel extends Model{

    // 数据验证
    public $_validate	=	array(
        array('username','require','{$error_username_must}'),
        array('username','checkAccount','{%error_username_has}',0,'callback'),
        array('password','checkPassword','{%error_password_must}',0,'callback'),
        array('reset_password','password','{%error_conpassword_noaggree}',self::EXISTS_VALIDATE,'confirm'),
    );

    // 自动对密码加密
    public $_auto		=	array(
        array('status','1'),
        array('is_supply','1'),
        array('password','pwdHash',self::MODEL_BOTH,'callback'),
        array('reg_time','time',self::MODEL_INSERT,'function'),
        array('last_logintime','time',self::MODEL_UPDATE,'function'),
        array('last_ip','get_client_ip',self::MODEL_INSERT,'function'),
    );

    // 检查帐号
    public function checkAccount($username) {
        if($username){
            $username    = $_POST['username'];
        }
        $map['username'] = $username;
        $map['status']   = 1;
        if(!empty($_POST['uid'])) {
            $map['uid']	 = array('neq',$_POST['uid']);
        }
        $result	= $this->where($map)->field('uid')->find();
        if($result) {
            return false;
        }else{
            return true;
        }
    }

    // 检查密码
    public function checkPassword() {
        if(empty($_POST['password'])) {
            return false;
        }else{
            return true;
        }
    }

    // 密码加密
    protected function pwdHash($password='') {
        if($password){
            $password = $_POST['password'];
        }
        if(isset($password)) {
            return pwdHash($password);
        }else{
            return false;
        }
    }

    //检查登陆，返回userinfo
    public function checkLogin($username,$password){
        $password = $this -> pwdHash($password);
        $_array   = array(
            'username'=>array('eq',$username),
            'password'=>array('eq',$password),
            'status'  =>array('eq','1'),
            'agent_id'=>array('eq','0'),
        );
        $userinfo = $this -> where($_array)->find();
        if(empty($userinfo)){
            return array( 0 , null );
        }else{
            unset($userinfo['password']);
            $data['last_ip']        = get_client_ip();
            $data['last_logintime'] = trim();
            $this -> where ($_array) -> data($data) -> save();
            return array( 1 , $userinfo );
        }
    }

    //检查有没有开通供应宝,0:表示账户靠同，1表示用户已提交申请，2表示钻明已通过审核
    public function checkOpenSupply( $uid = 0 ){
        if(empty($uid)){
            return false;
        }
        $status = $this->where( 'uid = '.$uid)->getField('supply_status');
        if( $status != 2 ){
            return false;
        }
        return true;
    }

    public function getUserInfoByUid($uid){
		if(empty($uid)){
			return false;
		}
        return $this->where('uid = '.$uid)->find();
    }
	/**
	 * auth	：fangkai
	 * content：用户修改密码
	 * time	：2016-4-29
	**/
	public function updatepassword($old_password,$new_password,$uid){
		if(empty($uid)){
			return false;
		}
		$uinfo = $this->where(array('uid'=>$uid))->find();
		if (!empty($uinfo) &&  ($uinfo['password'] == pwdHash($old_password))) {
			$save['password'] = pwdHash(strval($new_password));
			if ($this->where(array('uid'=>$uid))->save($save) !== false) {
				unset($_SESSION['supply']);
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

    

}