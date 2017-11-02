<?php
/**
 * 用户模型类
 */
namespace Admin\Model;
class UserModel extends AdminModel{
    
    // 数据验证
    public $_validate	=	array(
        array('user_name','/^[a-z]\w{3,}$/i','帐号格式错误'),
        array('user_name','checkAcount','用户已经存在了',0,'callback'),
        array('password','require','密码必须'),
        array('nickname','require','昵称必须'),
        array('email','require','邮箱必须'),
        array('repassword','require','确认密码必须'),
        array('repassword','password','确认密码不一致',self::EXISTS_VALIDATE,'confirm'),
        array('user_name','','帐号已经存在',self::EXISTS_VALIDATE,'unique',self::MODEL_INSERT),
    );
    
    // 模型自动执行
    public $_auto		=	array(
        array('password','pwdHash',self::MODEL_BOTH,'callback'),
        array('create_time','time',self::MODEL_INSERT,'function'),
        array('update_time','time',self::MODEL_UPDATE,'function'),
    );
    
    // 检查帐号
    public function checkAcount() {
        $map['user_name']	=	 $_POST['user_name'];
        $map['status'] = 1;
        if(!empty($_POST['id'])) {
            $map['id']	=	array('neq',$_POST['id']);
        }
        $result	=	$this->where($map)->field('id')->find();
        if($result) {
            return false;
        }else{
            return true;
        }
    }
    
    // 密码加密
    protected function pwdHash() {
        if(isset($_POST['password'])) {
            return pwdHash($_POST['password']);
        }else{
            return false;
        }
    }
}