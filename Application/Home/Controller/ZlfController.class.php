<?php
//导入周六福数据接口
namespace Home\Controller;
class ZlfController extends HomeController {
    protected $result = array("ret"=>100, "msg"=>"", "data"=>"");		//返回值定义
	/**
	 * 返回结果JSON
     * @return void
	 */
	public function echo_data($ret=100 , $msg='', $data=array()){
		$this->result['ret'] = $ret;
		$this->result['msg'] = $msg;
		$this->result['data'] = $data;
		$this->ajaxReturn($this->result);
	}
	/**
	 * auth	：zengmingming
	 * content：用户表
	 * time	：2017-09-01
	**/
    public function dockingLogin(){
		$DUM   					= M('docking_user');
		$key 			= I('get.key','');
		$token 			= I('get.token','');
		if(empty($key)){
			$this->echo_data('201','用户登录钥匙不能为空');
			return false;
		}
		if(empty($token)){
			$this->echo_data('202','用户登录令牌不能为空');
			return false;
		}
		$userStr = $DUM->where("key='".$key."' and token='".$token."'")->find();
		if(empty($userStr["status"]==1)){ 
			//修改token
			$tokenStr			= 'szzm'.time().$userStr["user_id"];
			$User['id']			= $userStr["id"];
			$User['token']		= password_hash($tokenStr, PASSWORD_DEFAULT);
			if($updUser = $DUM->save($User)){
				$this->result['ret']				= '100';
				$this->result['msg']				= '请求成功';
				$this->result['data']["key"]		= $userStr["key"];
				$this->result['data']["token"]		= $userStr["token"];		
			}else{
				$this->result['ret']	= '201';
				$this->result['msg']	= '请求失败';	
				$this->result['data'] 	= array();
			}	
		}else{
			$this->result['ret']	= '201';
			$this->result['msg']	= '请求失败';	
			$this->result['data'] 	= array();
		}		
		$this->echo_data($this->result['ret'],$this->result['msg'],$this->result['data']);
	}
	/**
	 * auth	：zengmingming
	 * content：用户对接表
	 * time	：2017-09-01
	**/
    public function regist(){
		$DUM   			= M('docking_user');
		$UM   			= M('user');
		$key 			= I('post.key','');
		$token			= I('post.token','','');
		$Userid			= I('post.Userid','');
		$Username		= I('post.Username','','');
		$password		= I('post.Password','','');
		$Ugroup			= I('post.Ugroup','0','intval');
		/*
		$dockingUser	= $DUM->where("user_id='".$key."' and token='".$token."'")->find();
		if($dockingUser["status"]==0){
			$this->echo_data('205','你没有权限执行此次操作！');
			return false;	
		}
		*/
		if($key!='20171504261263' || $token!='$2y$10$OAIK1I3pcSN1coJZyEJTweEbbpCpG9oXVfO8MJZcXhSIY13/fnEDu'){
			$this->echo_data('205','你没有权限执行此次操作！');
			return false;	
		}
		if(empty($Userid)){
			$this->echo_data('201','用户ID不能为空');
			return false;
		}
		if(empty($Username)){
			$this->echo_data('202','用户姓名不能为空');
			return false;
		}
		if(empty($password)){
			$this->echo_data('203','密码不能为空');
			return false;
		}
		if(mb_strlen($password) < 6){
			$this->echo_data('204','密码长度不能少于6个字符');
			return false;
		}
		if(mb_strlen($password) > 20){
			$this->echo_data('204','密码长度不能超过20个字符');
			return false;
		}
		$User['userid']  	= $Userid;
		$User['username']  	= $Username;
		$User['password']  	= $password;
		$User['ugroup']  	= $Ugroup;
		$userStr = $UM->where("userid='".$userid."'")->find();
		if(empty($userStr["uid"])){
			$updId = $UM->data($User)->add();
		}else{
			if($userStr["ugroup"]==1 || $userStr["status"]!=1){
				$this->result['ret']	= '201';
				$this->result['msg']	= '该用户已被禁用';	
			}else{
				$updId = $UM->where("uid=".$userStr["uid"])->seve($User);	
			}
		}
		if ($updId) {
			$this->result['ret']	= '100';
			$this->result['msg']	= '数据变更成功';
		}else{
			$this->result['ret']	= '201';
			$this->result['msg']	= '数据变更失败';
		}
		$this->echo_data($this->result['ret'],$this->result['msg']);
	}

	/**
	 * 周六福项目开始
	 *
	 */

	//1：网点：fhdzH
	//2：公司款号子表：gskhb
	//3：公司款号表头：gskhh
	//4：用户表:STUsers
	//5：网点表：wdmlh
	//6：订单子表:wsddb
	//7：订单表头：wsddh

	//同步用户用户
	public function regUser(){
		set_time_limit(0);
		ini_set('memory_limit', '1024M');
		$UM = D('Common/User');
		$ZLF_UM = M('stusers','zlf_','ZLF_DB');
		$BL = M('banfang_log');
		$limit_time = time()-1800;
		$limit_type = 2;
		$log = $BL->where(array('add_time'=>array('gt',$limit_time),'type'=>$limit_type))->find();
		if(empty($log)){
			$BL->add(array('add_time'=>time(),'type'=>$limit_type));
			$count = $ZLF_UM->count();
			if($count>2000){
				$count=2000;
			}
			$count = $UM->regZlfMany($count);

			echo '操作成功，共更新'.$count.'条数据';
		}else{
			echo '操作太频繁请5分钟后再尝试';
		}
	}

	public function addGoods(){

		set_time_limit(0);
		ini_set('memory_limit', '1024M');
		$ZLF_UM = M('gskhh','zlf_','ZLF_DB');
		$BL = M('banfang_log');
		$limit_time = time()-1800;
		$limit_type = 1;
		$log = $BL->where(array('add_time'=>array('gt',$limit_time),'type'=>$limit_type))->find();
		if(empty($log)){
			$BL->add(array('add_time'=>time(),'type'=>$limit_type));
			$UM = D('Common/Goods');
			$count = $ZLF_UM->count();
			//$count = $UM->addZlfGoods($count);
			if($count>2000){
				$count=2000;
			}
			$count = $UM->addZlfGoods($count);
			echo '操作成功，共更新'.$count.'条数据';
		}else{
			echo '操作太频繁请5分钟后再尝试';
		}
	}


	public function AddOrder(){
		$data = $_POST['data'];
		$order = $data['order'];
		$order_goods = $data['order_goods'];
		$ZLF_O = M('wsddh','zlf_','ZLF_DB');
		$ZLF_OG = M('wsddb','zlf_','ZLF_DB');
		$bool1 = $ZLF_O->add($order);
		$bool2 = $ZLF_OG->addAll($order_goods);
		$this->ajaxReturn(array('status'=>100,'info'=>array($bool1,$bool2)));
	}

	public function ajaxGetWangdian(){
		$return = array(
				'status'=>0,
				'data'=>array(),
				'count'=>0,
				'msg'=>'获取数据失败'
		);
		$ZLF_UM = M('ygml','zlf_','ZLF_DB');

		$id = I('id');
		$shop_address = I('shop_address');
		//$lists = $ZLF_UM->where("(wdflag is null or wdflag=0) AND czID='$id'")->limit(100)->select();
		if(!empty($id)){
			//$sql = "SELECT TOP 10 * from zlf_fhdzh f LEFT JOIN zlf_ygml y on f.wdbm = y.wdbm WHERE czID = '$id'";
			$sql = "SELECT TOP 10 * from zlf_ygml WHERE czID = '$id'";
			if(!empty($shop_address)){
				$sql .= " AND wdmc like '%$shop_address%'";
			}

			$lists = $ZLF_UM->query($sql);
			$agent_id = C('agent_id');
			$return = array(
					'status'=>100,
					'data'=>array(),
					'count'=>0,
					'msg'=>'获取数据成功'
			);
			foreach($lists as $value){
				if(empty($value['wdbm'])){
					continue;
				}
				$data = array(
						'shop_name'=>$value['sswdmc'] ? $value['sswdmc'] : '',
						'shop_code'=>$value['wdbm'] ? $value['wdbm'] : '',
						//'master_name'=>$value['khm'] ? $value['khm'] : '',
						//'master_phone'=>$value['lxdh'] ? $value['lxdh'] : '',
						'shop_address'=>$value['wdmc'] ? $value['wdmc'] : '',

						'agent_id'=>$agent_id,
						'zlf_id'=>$value['djlsh']
				);
				if(empty($data['shop_code'])){
					continue;
				}
				$return['data'][] = $data;
				$return['count']++;
			}
		}

		$this->ajaxReturn($return);

	}







}	