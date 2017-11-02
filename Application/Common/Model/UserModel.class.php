<?php
/**
 * 订单支付模型
 */
namespace Common\Model;
use Think\Model;
class UserModel extends Model{
	protected $_validate = array(
		array('username', '3,20', '用户名长度为3-20个字符', self::MUST_VALIDATE, 'length', self::MODEL_BOTH ),
		array('username', '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u', '用户名只能是字母数字和汉字', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
		//array('email'   , '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i', 'Email格式错误', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
		array('password', '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u', '密码只能是字母数字和汉字', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
		array('password', '6,20', '密码长度为6-20个字符', self::MUST_VALIDATE, 'length', self::MODEL_BOTH ),	
		array('phone', '/^1[34578]\d{9}$/', '手机号码格式不正确', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH ),
	);


	
	public  $agent_id;

    public function __construct() {
		parent::__construct();
        $this -> agent_id = C('agent_id');
    }
	
	/**
	 * auth	：fangkai
	 * content：获取用户列表
	 * time	：2016-9-6
	**/
	public function getUserList($where='1=1',$sort='uid DESC',$pageid=1,$pagesize=20){
		$limit		= (($pageid-1)*$pagesize).','.$pagesize;
		$userList	= $this->where($where)->order($sort)->limit($limit)->select();
		if($userList){
			return $userList;
		}else{
			return false;
		}
	}

	public function todayRegUserNum(){
		$ip = get_client_ip();
		$time_strat = strtotime(date('Y-m-d', time()));
		$time_end = $time_strat + 86400;
		$num = $this->where("last_ip like '".$ip."' and reg_time > ".$time_strat ." and reg_time <=".$time_end .' and agent_id = ' . $this->agent_id)->count();
		return $num;
	}

	public function checkRegUserInfo($user = array()){

			if(empty($user['username'])){
            	 $result = array('ret'=>101, 'msg'=>'用户名不能为空。','url'=>$_SERVER['HTTP_REFERER']);
                return $result;
            }
			$uid = $this->where( ' username = "'.$user['username'].'" and agent_id = '.C('agent_id'))->field('uid')->select();
            if(count($uid)!==0){
                $result = array('ret'=>101, 'msg'=>'此用户名已被注册。','url'=>$_SERVER['HTTP_REFERER']);
                return $result;
            }
            // if(empty($user['email'])){
            	 // $result = array('ret'=>102, 'msg'=>'Email不能为空。','url'=>$_SERVER['HTTP_REFERER']);
                // return $result;
            // }
			if($user['email']){
				$uid = $this->where( ' email = "'.$user['email'].'" and agent_id = '.C('agent_id'))->field('uid')->select();;
				if(count($uid)!==0){
					$result = array('ret'=>102, 'msg'=>'邮箱已被其他账户使用。','url'=>$_SERVER['HTTP_REFERER']);
					return $result;
				}
			}

            if(empty($user['phone'])){
            	 $result = array('ret'=>103, 'msg'=>'手机号不能为空。','url'=>$_SERVER['HTTP_REFERER']);
                return $result;
            }
            $uid = $this->where( ' phone = "'.$user['phone'].'" and agent_id = '.C('agent_id'))->field('uid')->select();;           
            if(count($uid)!==0){
                $result = array('ret'=>103, 'msg'=>'手机号已被其他账户注册。','url'=>$_SERVER['HTTP_REFERER']);
                return $result;
            }

            $num = $this->todayRegUserNum();
            if($num>100){
               $result = array('success'=>false, 'msg'=>'一个IP每天只能注册100个账号！','url'=>$_SERVER['HTTP_REFERER']);
               return $result;
            }
            return true;
	}


	public function reg($param){
		$return = array(
			'status'=>0,
			'msg'=>'注册失败',
		);
		$not_verify = $param['not_verify'] ? 1 : 0;
		$data = array(
				'username'=>$param['username'],
				'password'=>$param['password'],
				'reg_time'=>time(),
				'last_logintime'=>time(),
				'rank_id'=>0,
				'parent_type'=>0,
				'realname'=>$param['realname'],
				'agent_id'=>$param['agent_id'] ? intval($param['agent_id']) : C('agent_id'),
				'is_zlf'=>$param['is_zlf'] ? 1 : 0,
				'ugroup'=>$param['ugroup'] ? intval($param['ugroup']) : 0,
		);
		if(empty($data['username'])){
			$return['msg'] = '用户名不能为空';
			return $return;
		}
		if(empty($data['password'])){
			$return['msg'] = '密码不能为空';
			return $return;
		}
		$data['password'] = pwdHash($data['password']);
		if(!$not_verify){
			//需要验证的情况	后期来完善现在没时间弄

		}else{
			//不需要验证，加入用户存在就更新数据，没有就注册
			$return = array(
					'status'=>100,
					'msg'=>'注册成功',
			);
			$where = array(
				'username'=>$data['username'],
				'agent_id'=>$data['agent_id']

			);
			$info = $this->where($where)->find();
			if(empty($info)){
				$id = $this->add($data);
			}else{
				//更新的时候展示不同步网点数据	后期再做修改
				$bool = $this->where($where)->save($data);
				$id = $info['uid'];
			}

		}
		$return['id'] = $id;
		return $return;
	}

	public function reg_zlf($param){
		$return = array(
			'status'=>0,
			'msg'=>'注册失败',
		);
		$not_verify = $param['not_verify'] ? 1 : 0;
		$data = array(
				'username'=>$param['username'],
				'password'=>$param['password'],
				'reg_time'=>time(),
				'last_logintime'=>time(),
				'rank_id'=>0,
				'parent_type'=>0,
				'realname'=>$param['realname'],
				'agent_id'=>$param['agent_id'] ? intval($param['agent_id']) : C('agent_id'),
				'is_zlf'=>$param['is_zlf'] ? 1 : 0,
				'ugroup'=>$param['ugroup'] ? intval($param['ugroup']) : 0,
		);
		if(empty($data['username'])){
			$return['msg'] = '用户名不能为空';
			return $return;
		}
		// if(empty($data['password'])){
		// 	$return['msg'] = '密码不能为空';
		// 	return $return;
		// }
		$data['password'] = pwdHash($data['password']);
		if(!$not_verify){
			//需要验证的情况	后期来完善现在没时间弄

		}else{
			//不需要验证，加入用户存在就更新数据，没有就注册
			$return = array(
					'status'=>100,
					'msg'=>'注册成功',
			);
			$where = array(
				'username'=>$data['username'],
				'agent_id'=>$data['agent_id']

			);

			$info = $this->where($where)->find();
			if(empty($info)){
				$id = $this->add($data);
			}else{
				//更新的时候展示不同步网点数据	后期再做修改
				$bool = $this->where($where)->save($data);
				$id = $info['uid'];
			}
		}
		$return['id'] = $id;
		return $return;
	}
	public function regZlfMany($count=10){
		$count_number = 0;
		if($count>0){
			$ZLF_UM = M('stusers','zlf_','ZLF_DB');
			$lists = $ZLF_UM->where('WDflag is null or WDflag=0')->limit($count)->select();
			// $lists = $ZLF_UM->where('Userid is not null')->limit($count)->select();
			// dump($lists);exit;
			//$lists = $ZLF_UM->where("WDflag is null or WDflag=0 AND Userid='zwl0034'")->limit(1)->select();
			//$bool = $ZLF_UM->where('1=1')->save(array('WDflag'=>0));
			foreach($lists as $value){
				$param = array(
						'username'=>$value['userid'],
						'password'=>$value['password'],
						'realname'=>$value['username'] ? $value['username'] : '',
						'not_verify'=>1,
						'is_zlf'=>1,
						'ugroup'=>$value['ugroup'] ? intval($value['ugroup']) : 0,
				);
				//&& !empty($value['password'])
				if(!empty($value['userid']) && !empty($value['username'])){
					$return = $this->reg_zlf($param);
					if($return['status']==100){
						$bool = $ZLF_UM->where(array('Userid'=>$value['userid']))->save(array('WDflag'=>1));
						//网店信息数据太多了不同步，直接读取信息
						//$this->getUserWeb($value['userid'],$return['id']);
						$count_number++;
					}else{
						$bool = $ZLF_UM->where(array('Userid'=>$value['userid']))->save(array('WDflag'=>2));
					}
				}else{
					$bool = $ZLF_UM->where(array('Userid'=>$value['userid']))->save(array('WDflag'=>2));
				}

			}
		}

		return $count_number;

	}

	public function getUserWeb($id,$uid){
		//获取用户网点
		$ZLF_UM = M('ygml','zlf_','ZLF_DB');
		$ZLF_AM = M('fhdzh','zlf_','ZLF_DB');
		//$lists = $ZLF_UM->where("(wdflag is null or wdflag=0) AND czID='$id'")->limit(100)->select();
		$lists = $ZLF_UM->query("SELECT * from zlf_fhdzh f LEFT JOIN zlf_ygml y on f.wdbm = y.wdbm WHERE czID = '$id' AND y.wdflag = 0");

		$SM = M('banfang_shop');
		$agent_id = C('agent_id');
		foreach($lists as $value){

			//$info = $ZLF_AM->where(array('wdbm'=>$value['wdbm']))->find();
			if(empty($value['wdbm'])){
				continue;
			}
			$data = array(
				'shop_name'=>$value['sswdmc'] ? $value['sswdmc'] : '',
				'shop_code'=>$value['wdbm'] ? $value['wdbm'] : '',
				'master_name'=>$value['khm'] ? $value['khm'] : '',
				'master_phone'=>$value['lxdh'] ? $value['lxdh'] : '',
				'shop_address'=>$value['wdjc'] ? $value['wdjc'] : '',
				'uid'=>$uid,
				'agent_id'=>$agent_id,
				'zlf_id'=>$value['djlsh']
			);

			if(empty($data['shop_code'])){
				continue;
			}
			$where = array(
					'agent_id'=>$data['agent_id'],
					'zlf_id'=>$data['zlf_id'],
					'uid'=>$uid
			);

			$shop_info = $SM->where($where)->find();
			if(empty($shop_info)){
				$bool1 = $SM->add($data);
			}else{
				$bool2 = $SM->where($where)->save($data);
			}
			$bool3 = $ZLF_UM->where(array('czID'=>$id,'wdbm'=>$data['shop_code']))->save(array('wdflag'=>1));
			$bool4 = $ZLF_AM->where(array('wdbm'=>$data['shop_code']))->save(array('WDflag'=>1));

		}

		$bool = $ZLF_UM->where(array('Userid'=>$id))->save(array('WDflag'=>1));
	}

}

