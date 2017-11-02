<?php
namespace App\Controller;
class UserController extends AppController{

	//初始化
    protected function  _initialize(){
		parent::_initialize();		//子类继承需要显式调用父类否则会覆盖
        isUser();		//用户中心必须登陆
    }

    /**
     * 用户中心，用户首页
     */
    public function index(){
		$where ='msg_type = 1 and uid = '.session('app.uid').' and agent_id = '.C('agent_id');
		$this->msg_count=M('user_msg')->where($where)->count();
        $this->display();
    }
    
    /**
     * 用户资料
     */
    public function info(){
        $U  = M('user');
        if(IS_POST){
            $data              = $_POST;
			$data['birthday']  = strtotime($_POST['birthday']);
            $U    -> where('uid='.session('app.uid').' and agent_id = '.C('agent_id'))->field('birthday,sex,email,phone,realname,job,company_name,legal,company_address')->filter('htmlspecialchars')->save($data);
			if($U){
				$result  = array('ret'=>100, 'msg'=>'资料保存成功');
			}else{
				$result  = array('ret'=>101, 'msg'=>'资料保存失败');
			}			
			$this->ajaxReturn($result);
		}else{
			$info = $U->find(session('app.uid'));
			$info['birthday'] = date("Y-m-d",$info['birthday']);
			$this->info = $info;			
			$this->display();
		}
    }

    /**
     * 用户地址
     */
    public function address(){	
        $this->addresslist = D('Common/UserAddress')->getAddressList(session('app.uid'));	
        $this->display();
    }

    /**
     * 用户地址 - 添加
     */
    public function addressadd(){
        $M = M('user_address');
        if (IS_POST) {
            $data        =  $_POST;
            $data['uid'] =  session('app.uid');
            $data['agent_id'] =  C('agent_id');
			$data['country_id'] =  '1';
            if($data['is_default']=='1'){
                $M->where('uid = '.session('app.uid'))->field('is_default')->save(array('is_default'=>'2'));
            }
            $r = $M->field('uid,title,country_id,province_id,city_id,district_id,address,name,phone,code,is_default,agent_id')->filter('htmlspecialchars')->data($data)->add();
            if ($r) {
                $result  = array('ret'=>100, 'msg'=>'地址添加成功', 'url'=>'/user/address/');
            }else{
				$result  = array('ret'=>102, 'msg'=>'地址添加失败');
			}
			$this->ajaxReturn($result);
        } else {
            $R = M('region');
            $this->province = $R->where('parent_id = 1')->select();			
            $this->display();
        }
    }

    /**
     * 用户地址 - 编辑
     */
    public function addressedit($address_id = 0){
        $addM = M('user_address');
        if (IS_POST) {
            if (empty($address_id)) { // 添加
                //parent::error('缺少关键参数(agent_id)');
				$result  = array('ret'=>101, 'msg'=>'缺少关键参数(address_id)');
				$this->ajaxReturn($result);
            } 
			$editUid = $addM->where(array('address_id'=>$address_id))->find();
			if ($_POST['is_default'] == 1) { // 保证一个用户只有一个默认地址
				$noDef['uid'] = session('app.uid');
				$noDef['address_id'] = array('NEQ', $address_id);
				$addM->where($noDef['uid'])->save(array('is_default'=>2));
			}
			if ($addM->where(array('address_id'=>$address_id))->save($_POST) === false) {
				$result  = array('ret'=>102, 'msg'=>'修改失败');			
			} else {
				$result  = array('ret'=>100, 'msg'=>'修改成功', 'url'=>'/user/address/');
			}
			$this->ajaxReturn($result);
        } else {
            $R = M('region');
            $this->addOnce = $addM->where(array('address_id'=>$address_id))->find();
            $this->country = $R->where('parent_id = 0')->select();
            $this->province = $R->where('parent_id = 1')->select();
            $this->city = $R->where('parent_id = '.$this->addOnce['province_id'])->select();
            $this->district = $R->where('parent_id = '.$this->addOnce['city_id'])->select();
            $this->display();
        }
    }

	/**
     * 用户地址 - 删除
     */
    public function addressdel($address_id = 0){
        $M = M('user_address');
        if (IS_POST) {
            if (empty($address_id)) {
				$result  = array('ret'=>101, 'msg'=>'缺少关键参数(address_id)');
				$this->ajaxReturn($result);
            } 
            $r = $M->where('address_id = '.$address_id.' and uid = '.session('app.uid').' and agent_id = '.C('agent_id'))->delete();
            if ($r) {
                $result  = array('ret'=>100,'msg'=>"删除成功", 'url'=>'/user/address/');
            }else{
				$result  = array('ret'=>101, 'msg'=>'删除失败');
			}
			$this->ajaxReturn($result);
        }
    }


    /**
     * 用户密码
     */
    public function password(){
		if (IS_POST) {
			$map['old_password'] = I('post.old_password', 'htmlspecialchars');
			$map['new_password'] = I('post.new_password', 'htmlspecialchars');
			$map['confirm_password'] = I('post.confirm_password', 'htmlspecialchars');
			if ($map['new_password'] != $map['confirm_password']) {
				$result  = array('ret'=>101, 'msg'=>'两次输入密码不一致');
				$this->ajaxReturn($result);
			}
			if(strlen($map['new_password'])<6|| strlen($map['new_password'])>18 ){
				$result  = array('ret'=>101, 'msg'=>'密码长度请控制在6-18位之间！');
				$this->ajaxReturn($result);
			}
			if (!preg_match("/^[a-zA-Z0-9]{6,18}$/", $map['new_password'])){ 
				$result  = array('ret'=>101, 'msg'=>'密码格式请填写为字母数字！');
				$this->ajaxReturn($result);
			}
			$U = M('user');
			$where = "uid=".session('app.uid').' and agent_id = '.C('agent_id');
			$uInfo = $U->field('*')->where($where)->find();	
			if (empty($uInfo) || $uInfo['password'] != pwdHash($map['old_password'])) {
				$result  = array('ret'=>101, 'msg'=>'原始密码输入不正确');
				$this->ajaxReturn($result);
			}
			$pa['password'] = pwdHash(strval($map['new_password']));
			if ($U->where("uid=".session('app.uid'))->save($pa)===false) {
				$result  = array('ret'=>101, 'msg'=>'密码修改失败');
			}else{
				session ('app.uid', null );
				$result  = array('ret'=>100, 'msg'=>'密码修改成功', 'url'=>'/user');
			}	
			$this->ajaxReturn($result);
		} else {
			$this->display();
		}
    }

    //加入分销商
    public function trader(){  
		$T = M('trader');
    	if(IS_POST){
    		$_POST['create_time'] = time();
    		$_POST['trader_rank'] = 1;//默认等级是白银会员
    		$_POST['status'] = 0;
    		$_POST['trader_rank'] = 0;
    		$_POST['template_id'] = 0; 
    		$_POST['parent_id'] = C('agent_id'); 
    		$_POST['create_user_id'] = session('app.uid');
    		$_POST['agent_id'] = C('agent_id');
    		//数据验证
    		$rules = array(
    			array('trader_name','require','必须填写分销商名称！'),
				array('company_name','require','必须填写公司名称！'),
    			array('contacts','require','必须填写联系人！'),
    			array('phone','require','必须填写联系电话！'),
    			array('province_id','require','必须选择省份！'),
    			array('city_id','require','必须选择城市！'),
    			array('note','require','必须填写申请内容！'),
    		); 
			if (!$T->validate($rules)->create($_POST)){
				$result  = array('ret'=>101, 'msg'=>$T->getError());
				$this->ajaxReturn($result);
			}

			$res = $T->add();
			if(!$res){
				$result  = array('ret'=>101, 'msg'=>'申请分销商失败');
			}else{
				$result  = array('ret'=>100, 'msg'=>'');
			}
			$this->ajaxReturn($result);
    	}else{
			$where = "agent_id=".C('agent_id') . " AND create_user_id=".session('app.uid');
			$info = $T->where($where)->find();
			//省份列表
    		$R = M('region');
    		$this->province = $R->where('parent_id = 1')->select();   
			if($info){		//如果已申请过
				$this->info = $info;
				$this->display();
				exit;
			}
    		$this->display();
    	}
    }

	
	
		
    /**
     * 我的订单
	 * zhy
	 * 2016年6月3日 12:17:12
     */
	 public function order(){
        $this->display();
	 }
	 
    /**
     * 我的订单api
	 * zhy
	 * 2016年6月3日 14:40:18
     */
	 public function order_api(){
        $Order         = M('order');
    	$order_tips     = I("post.order_tips");
		$n     	    = I("post.n");
    	$order_status = I('post.order_status','-2');
        $where        = " uid ='".session('app.uid')."'";
        if($order_tips){
        	$where .= " AND order_sn like '%".$order_tips."%'";
        }
        if($order_status != -2 && $order_status != "" ){
        	$where .= " AND order_status = ".$order_status;						//订单编号
        }
		// if($certNo){															证书号
        	// $subQuery = M('order_goods')->field('order_id')->group('order_id')->where("agent_id='".C('agent_id')."' and certificate_no ='".$certNo."'")->order('order_id')->select(false);         
        	// $where .= " AND order_id In (".$subQuery.")";
        // }
		
        $where .= " AND agent_id='".C('agent_id')."' and order_status >=-1";
        $count =  $Order->where($where)->count();
		$page='10';
		$n =is_numeric ( $n  ) ? ($n ) : '0';
		$n=$n*$page;
		$limit=" $n, $page";
        $dataList = $Order->where($where)->limit($limit)->order('create_time DESC')->select();
        foreach($dataList as $key=>$val){
        	$dataList[$key]['status_txt'] = getOrderStatus($val['order_status']);  
        	if($val['order_status'] == 3){
        		$orderDeliverys = $this->getDelivery($val['order_id']);  
        		if(!empty($orderDeliverys)){
        			$dataList[$key]['status_txt'] = "部分发货，待收货"; 
				}
			}
        	$dataList[$key]['payment_price'] = $this->getPaymentPrice($val['order_id']);
        	if($val['order_status'] <= 0){  
        		$dataList[$key]['price'] = $val['order_price'];
        	}else if($val['order_status'] >0){
        		$dataList[$key]['price'] = $val['order_price_up'];
        	}
			if($val['order_id']){
				$dataList[$key]['count']= $Order->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.agent_id='".C('agent_id')."' and zm_order.order_id ='".$val['order_id']."'")->count();
				$dataList_being[$key]=$Order->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.agent_id='".C('agent_id')."' and zm_order.order_id ='".$val['order_id']."'")->select();
				 foreach($dataList_being[$key] as $a=>$b){
					 $dataListgoods[$key] =  unserialize($b['attribute']);
					  foreach($dataListgoods as $k=>$v){
						$dataList[$key]['thumb'] =  $v['thumb'];
					}
				 }	

			}
			
        }	

        $data['data']  = $dataList;
		$data['count']  = $count;
		if( $n=='0'){	$data['tip']  =1;	}else{	$data['tip']  =2;}
		$this->ajaxReturn($data);	
	 }	

public function getPaymentPrice($order_id){
	$temp = M('order_receivables')->where("agent_id='".C('agent_id')."' and order_id='$order_id'")->select();   
	if($temp){
		foreach($temp as $val){
			$payment_price += formatRound($val['payment_price'],2);
		}
	}else{
		$payment_price = 0;
	}
	return $payment_price;
}	
	
	 
    /**
     * 我的订单更新状态
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function update_order(){
		$order_id     	  = I("post.order_id");
		$order_status     = I("post.order_status");
		$where = "agent_id='".C('agent_id')."' and uid='".session('app.uid')."' AND order_id=".$order_id;
    	$data = array('status'=>0,'msg'=>'订单取消成功','href'=>$href);
        $order = M('order')->where($where)->find();
    	if(!$order || $order_id == 0){
    		$this->ajaxReturn($data);
    	}else{
    		switch($order_status){
    			case -1:
    				$order['order_status'] = -2;
    				$data['msg'] = "订单删除成功";
					$data['status'] = 6;
    			break;
    			case 0:
    				$order['order_status'] = -1;
    				$data['msg'] = "取消订单成功";
					$data['status'] = 1;
    			break;
    			case 4:
    				$order['order_status'] =5;
    				$data['msg'] = "确认收货成功";
					$data['status'] = 4;
    			break;
    		}
    		if(M('order')->data($order)->save()){
    			if($order_status == 4){
    				//更新发货表状态
    				$data['confirm_type'] = 2;
    				$data['confirm_time'] = time();
    				M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id=$order_id AND uid='".session('app.uid')."'")->data($data)->save();
    			}
    		}
    		$this->ajaxReturn($data);
    	}
	}
	
	
	/**
     * 我的订单更新状态
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	 public function msg(){
		if($_POST){
			$data		 = [];
			$data['ret'] = 100;
			$M 	   		 = M('user_msg');
			$where 		 = ' uid = '.session('m.uid').' and agent_id = '.C('agent_id');
			if(is_numeric($_POST['msg_id'])){
							echo '--111-';
					if ($M->where($where.'msg_id = '.$_POST['msg_id'])->delete()) {
						$data['msg']='删除成功！';
					} else {
						$data['ret'] = 101;
						$data['msg']='删除失败！';
					}
			}else{
				$data['data'] = $M->where($where.' and msg_type = 1')->order('is_show asc,msg_id desc')->limit($_POST['page'],$_POST['page_len'])->select();
			}
			$this->ajaxReturn($data);
		}else{
			$this->display();
		} 
	 }
	 
	 


}