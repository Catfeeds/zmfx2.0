<?php
/**
	* 用户模块
	* User: YangChao
	* Date: 2016/03/03
**/
namespace Mobile\Controller;
class UserController extends MobileController{
    /**
     * 备用
     */
    public function index(){
		echo md5("testcc");
    }
    
    /**
     * 登录验证
     */
    public function login(){
		$username = I('username');		//帐号
		$pwd = I('pwd');				//密码

		$UM = M('user');
        $where['username'] = I('username');
        $where['agent_id'] = C("agent_id");
		$uInfo = $UM->where($where)->find(); 

		if(!$uInfo){
			$this->r(201, '用户或密码不正确');
		}

		$valite = md5($pwd . md5(C('SALT')));
		if($uInfo['password'] != $valite){
			$this->r(202, '用户名或密码不正确');	//区别上面
		}

		if($uInfo['status']!='1' ){
			$this->r(203, '用户已被系统禁止登录');
		}

		//$this->saveSession($uInfo);
		//$this->saveLoginInfo($uInfo['uid']);
		//$this->updateUserSession($uInfo['uid']);
		$UM->where('uid='.$uInfo['uid'].' and agent_id = '.C('agent_id'))->save(array('last_logintime'=>time(), 'last_ip'=>get_client_ip()));
		$s = $uInfo['uid'] .','. date("Y-m-d");
		$o = new \Common\Model\Des();
		$key = $o->encrypt($s);
		$data = array(
			'key' => $key,
			'uid' => $uInfo['uid'],
			'username' => $username
		);
		$this->r(100, '', $data);
    }

    // 用户退出
    public function logout() {
		$this->r();
    }

	/**
	用户注册
	**/
	public function register(){
		$username	= I('username', 'htmlspecialchars');
		$password	= I('password', 'htmlspecialchars');
		$email		= I('email','',validate_email);
		$phone		= I('phone', 'htmlspecialchars');

		if($username=='' || $password==''){
			$this->r(201, '注册必要参数不完整');
		}

		if($email==''){
			$this->r(301, '邮箱地址格式不正确');
		}

		if(!check_telnum($phone)){
			$this->r(302, '手机格式不正确');
		}

		$UM  = M('user');
		$uid = $UM->where( ' username = "'.$username.'" and agent_id = '.C('agent_id'))->field('uid')->select();
        if(count($uid)!==0){
			$this->r(202, '注册用户已存在');
        }

		$User['username']  = $username;
		$User['email']     = $email;
		$User['phone']     = $phone;
		$password = md5($password . md5(C('SALT')));	//密码加密
		$User['password']  = $password;
		$User['reg_time']  = time();
		$User['last_logintime'] = time();
		$User['last_ip'] = get_client_ip();
		$User['rank_id'] = 0;
		$User['parent_type'] = 1;
		$User['parent_id']   = C('agent_id'); 
		$User['agent_id']    = C('agent_id'); 
		$insertId = $UM->data($User)->add();
		if ($insertId) {
			$this->r();
		} else {
			$this->r(304, '注册失败，请重试，如有疑问请联系客服');
		}
	}

    /**
     * 获取用户信息
     */
    public function info(){
		$uid = I('uid', 0, 'intval');			//用户ID
		$UM = M('user');
        $where['uid'] = $uid;
        $where['agent_id'] = C("agent_id");
		$uInfo = $UM->where($where)->find(); 

		if(!$uInfo){
			$this->r(301, '用户信息不存在');
		}

		$this->r(100, '', $uInfo);
    }

    /**
     * 修改用户密码
     */
    public function pwdmodi(){
		$uid		= I('uid', 0, 'intval');			//用户ID
		$password	= I('password', 'htmlspecialchars');		//新密码MD5之后的值
		$oldpassword= I('oldpassword', 'htmlspecialchars');		//原始MD5密码

		$UM = M('user');
        $where['username'] = I('username');
        $where['agent_id'] = C("agent_id");
		$uInfo = $UM->where($where)->find(); 

		if(!$uInfo){
			$this->r(201, '用户不存在');
		}

		$valite = md5($oldpassword . md5(C('SALT')));
		if($uInfo['password'] != $valite){
			$this->r(202, '原始密码不正确');
		}
		
		$pa['password'] = md5($password . md5(C('SALT')));	//密码加密
		if ($UM->where("uid=".$uid)->save($pa) !== false) {
			$this->r(301, '密码修改失败');
		}
		$this->r();
    }


    /**
     * 获取用户地址列表
     */
    public function addresslist(){
		$uid	= I('uid', 0, 'intval');			//用户ID
		$start	= I('start', 0, 'intval');			//起始值
		$per	= I('per', 20, 'intval');			//取条数

		$M = M('user_address');

		$join = 'zm_region AS r ON r.region_id = s.country_id';
        $join2 = 'zm_region AS r2 ON r2.region_id = s.province_id';
        $join3 = 'zm_region AS r3 ON r3.region_id = s.city_id';
        $join4 = 'zm_region AS r4 ON r4.region_id = s.district_id';
        $field = 's.*, r.region_name AS country_name, r2.region_name AS province_name,
          r3.region_name AS city_name, r4.region_name AS district_name';
        $data = $M->alias('s')->field($field)->where('uid='.$uid . ' and s.agent_id = '.C('agent_id'))->join($join)->join($join2)->join($join3)->join($join4)->order('address_id desc')->limit($start.','.$per)->select();
		$this->r(100, '', $data);
    }

   /**
     * 用户地址添加/修改
     */
    public function address(){
		//参数获取与校验
		$uid		= I('uid', 0, 'intval');
		$address_id = I('address_id', 0, 'intval');

        $sData['uid'] = $uid;			//用户ID
        $sData['title'] = I('title', '', 'htmlspecialchars');
        $sData['country_id'] = I('country_id', '', 'htmlspecialchars');
        $sData['province_id'] = I('province_id', '', 'htmlspecialchars');
        $sData['city_id'] = I('city_id', '', 'htmlspecialchars');
        $sData['district_id'] = I('district_id', '', 'htmlspecialchars');
        $sData['address'] = I('address', '', 'htmlspecialchars');
        $sData['code'] = I('code', '', 'htmlspecialchars');
        $sData['name'] = I('name', '', 'htmlspecialchars');
        $sData['phone'] = I('phone', '', 'htmlspecialchars');
        $sData['is_default'] = I('is_default', '', 'htmlspecialchars');
        $sData['agent_id'] = C('agent_id');

		$M = M('user_address');

        if ($sData['is_default'] == 1) { // 如果新添加的地址为默认的，则其它地址就要更新为非默认
			$M->where(array('uid'=>$uid))->save(array('is_default'=>2));
        }
		if($address_id>0){		//编辑保存
			$sData['address_id'] = $address_id;
			$M->data($sData)->save();
		}else{		//新增
			$M->data($sData)->add();
		}
		$this->r();
    }

   /**
     * 设置缺省收货地址
     */
    public function addressset(){
		//参数获取与校验
		$uid		= I('uid', 0, 'intval');	//用户ID
		$address_id = I('address_id', 0, 'intval');

        $sData['uid'] = $uid;			
        $sData['agent_id'] = C('agent_id');
		$sData['address_id'] = $address_id;
		$M = M('user_address');
		$M->where($sData)->save(array('is_default'=>1));
		$this->r();
    }

   /**
     * 删除地址
     */
    public function addressdel(){
		//参数获取与校验
		$uid		= I('uid', 0, 'intval');		//用户ID
		$address_id = I('address_id', 0, 'intval');

        $sData['uid'] = $uid;			
        $sData['is_default'] = 1;
        $sData['agent_id'] = C('agent_id');
		$M = M('user_address');
		$M->delete($address_id);
		$this->r();
    }

}