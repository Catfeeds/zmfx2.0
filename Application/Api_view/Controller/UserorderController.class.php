<?php
namespace Api_view\Controller;
class UserorderController extends Api_viewController{

	//初始化
    protected function  _initialize(){
		parent::_initialize();		//子类继承需要显式调用父类否则会覆盖
        //isUser();		//用户中心必须登陆
    }

		
    /**
     * 我的订单
	 * zhy
	 * 2016年6月3日 12:17:12
     */
    public function index(){
        $this->display();
    }

	 
    /**
     * 我的订单api
	 * zhy
	 * 2016年6月3日 14:40:18
     */
	 public function order_api(){
        $Order         = M('order');
    	$order_tips    = I("post.order_tips");
		$p     	       = I("post.page");
		$uid		   = C('uid');
		$n 			   = 10;
    	$order_status  = I('post.order_status','-2');
        $where         = " zm_order.uid ='".$uid."'";
        if($order_tips){
        	$where    .= " AND zm_order.order_sn like '%".$order_tips."%'";
        }
        if($order_status != -2 && $order_status != "" ){
        	$where    .= " AND zm_order.order_status = ".$order_status;								//订单编号
        }
        $where .= " and zm_order.is_yiji_caigou < 1 and zm_order.is_erji_caigou = 0";				//客户订单

		// if($certNo){															证书号 
        	// $subQuery = M('order_goods')->field('order_id')->group('order_id')->where("agent_id='".C('agent_id')."' and certificate_no ='".$certNo."'")->order('order_id')->select(false);         
        	// $where .= " AND order_id In (".$subQuery.")";
        // }
		
        $where .= " AND zm_order.agent_id='".C('agent_id')."' and zm_order.order_status >=-1";
        $count =  $Order->where($where)->count();
		$page = NEW \Think\AjaxPage($count,$n,"submit_data");
        $page->firstRow.','.$page->listRows;
        $limit = $page->firstRow.','.$page->listRows;
		$order_ids = M('order')->field('order_id,order_status')->where($where)->limit($limit)->order('create_time DESC')->select();
			if(!empty($order_ids)){	
				foreach ($order_ids as  $v){
					$dataList[] =  M('order')->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.order_id =" .$v['order_id'])->select();
				}
				foreach($dataList as $key=>$val){
						foreach($val as $b=>$v){
							 switch($v['order_status']){
								case 0:  $val[$b]['status_txt'] = "待确认";
								break;
								case 1:  $val[$b]['status_txt'] = "待付款";
								break;
								case 2:  $val[$b]['status_txt'] = "待配货";
								break;
								case 3:  $val[$b]['status_txt'] = "待发货";
								break;
								case 4:  $val[$b]['status_txt'] = "待收货";
								break;
								case 5:  $val[$b]['status_txt'] = "已收货";
								break;
								case 6:  $val[$b]['status_txt'] = "已完成";
								break;
								case -1: $val[$b]['status_txt'] ="已取消";
								break;
								default: $val[$b]['status_txt'] = "未知状态";
								break;
							}
							if($val['order_status'] == 3){
								$orderDeliverys = $this->getDelivery($v['order_id']);  
								if(!empty($orderDeliverys)){
									$val[$key][$b]['status_txt'] = "部分发货，待收货"; 
								}
							}
						$val[$b]['goods_data'] =$goods=  unserialize($v['attribute']);
						if($val[$b]['goods_type'] == 3 || $val[$b]['goods_type'] == 4){   // 3成品	
							$val[$b]['goods_price']= ($val[$b]['goods_price_up']>0) ? $val[$b]['goods_price_up'] : $val[$b]['goods_price']; 
							$val[$b]['goods_number']= ($val[$b]['goods_number_up']>0) ? $val[$b]['goods_number_up'] : $val[$b]['goods_number']; 
							$val[$b]['goods_give_price']= formatRound($val[$b]['goods_price']/$val[$b]['goods_number'],2);
						} 

						$val[$b]['payment_price'] = $this->getPaymentPrice($v['order_id']);
						if($val['order_status'] <= 0){  
							$val[$b]['price'] = $v['order_price'];
						}else if($val['order_status'] >0){
							$val[$b]['price'] = $v['order_price_up'];
						}
						//物流信息
						$val[$b]['order_express']= M('order_express')->where(' order_id = '.$v['order_id'])->count();
						
					}
					$dataList [$key] = $val;
				}
				$dataList['page']= $page->show();
				if( $n=='0'){	$data['tip']  =1;	}else{	$data['tip']  =2;}
			}else{
				$dataList='';
			}
		   $this->ajaxReturn($dataList);
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
			
			if($order_status=='-1'){
				  M('order')->data($order)->delete();
			}elseif(M('order')->data($order)->save()){
    			$nowAgent_id = C('agent_id');
				$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
				if($order_status == 4){
					//确认收货，发送信息给商家       	zengmingming		2017-08-24
					if($sms_agent_info_arr["sms_push_reminder"]){
						$SMS = new \Common\Model\Sms();
						$SMS->SendSmsByType($SMS::USER_CONFIRM_RECEIPT,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
					}
				
    				//更新发货表状态
    				$data['confirm_type'] = 2;
    				$data['confirm_time'] = time();
    				M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id=$order_id AND uid='".session('app.uid')."'")->data($data)->save();
    			}else{
					//取消订单，发送信息给商家			zengmingming		2017-08-24
					if($sms_agent_info_arr["sms_push_reminder"]){
						$SMS = new \Common\Model\Sms();
						$SMS->SendSmsByType($SMS::USER_ORDER_CANCEL,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
					}
				}
    		}
    		$this->ajaxReturn($data);
    	}
	}
	
	
	/**
     * 我的订单支付成功页面
	 * zhy
	 * 2016年6月11日 12:05:55
     */
    public function payment(){
		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1  and agent_id = '.C("agent_id"))->select();	//线下支付


		 $order_id   = I("get.order_id", 0, 'intval');

        $O          = M('order');
        $where      = 'o.agent_id = '.C('agent_id').' and o.uid = '.$_SESSION['app']['uid'] .' and ';        
        $info       = $O->alias('o')->where($where.' o.order_id = '.$order_id)->field($field)->join($join)->find();
        
        // if(empty($info)){           
        //     $this->echo_data('101','你没有该订单!');
        // }
        // if($info['order_status'] < 1){        
        //     $this->echo_data('102','订单是取消或待确认状态!');
        // }

        //$this->orderInfo = $this->countOrder($info);                
        //订单的信息
        if($info['order_price_up'] > 0){
        	$info['order_price'] = $info['order_price_up'];
        }
        $this->orderInfo = array('order_sn' => $info['order_sn'], 'order_price' => $info['order_price'], 'order_status' => $info['order_status'],'order_id' => $info['order_id']);


        $this->display();
    }

	/**
     * 在线支付-选择支付方式
	 * yc
	 * 2017/1/13
     */
	public function onlineselect(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误','未知错误！');
		}
		$O = M('order');
		$where = 'agent_id = '.C('agent_id').'  and uid = '.$_SESSION['app']['uid'];
		$field = 'order_id,order_sn,order_price';
		$this->orderInfo = $O->where($where.' and order_id = '.$order_id)->field($field)->find();
		//var_dump($this->orderInfo);
		$this->display();
	}
	
	/**
     * 在线支付-支付宝支付
	 * yc
	 * 2017/1/13
     */
	public function payonline(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误','未知错误！');
		}
		$alipay = new \Common\Model\Alipay();
		$this->html_text = $alipay->request($order_id);
		$this->display();
	}
		 
    /**
     * 我的订单更新状态
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function Info(){
		$order_id     	  = I("get.goodid");
		$O = M('order');
		$where = 'o.agent_id = '.C('agent_id').'  and ';
		$field = 'o.*,u.realname,u.username,t.trader_name,';
		$field .='au.nickname as parent_admin_name,tu.trader_name as parent_trader_name';
		$join['u'] = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
		$join['t'] = 'LEFT JOIN `zm_trader` AS t ON t.tid = o.tid';
		$join['au'] = 'LEFT JOIN `zm_admin_user` AS au ON o.parent_id = au.user_id';
		$join['tu'] = 'LEFT JOIN `zm_trader` AS tu ON o.parent_id = tu.tid';
		$info = $O->alias('o')->where($where.' o.order_id = '.$order_id)->field($field)->join($join)->find();
		if($info && $info['address_id']){
			$this->orderInfo = $this->countOrder($info);
			if($this->orderInfo['order_price']){
				$_SESSION['info_price_to_payment'] =$this->orderInfo['order_price'] ;
			}
			//订单产品数据
			$this->goodsList = $this->getOrderGoodsList($order_id);
			//地址
			$UA = M('user_address');
			$field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
					'r2.region_name as province,r3.region_name as city,r4.region_name as district';
			$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
			$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
			$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
			$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
			$data = $UA->alias('ua')->where('ua.address_id = '.$info['address_id'].' and ua.agent_id = '.C('agent_id'))->field($field)
				->join($join1)->join($join2)->join($join3)->join($join4)->find();
			$data['addressInfo'] = $data['country'].$data['province'].$data['city'].$data['district'].$data['address'];
			if($info['delivery_mode']){
					//$OrderPayment = new \Common\Model\OrderPayment();
				$data= D('Common/OrderPayment')->this_dispatching_way($info['delivery_mode'],$data);
			}
			$this->addressInfo =$data;
		}else{
			$this->error('网页打开错误！','未知错误！');
		}
		//订单详情
		$this->display();
	}
    /**
     * 订单评价列表 526
     * zhy	find404@foxmail.com
     * 2017年1月10日 15:17:20
     */
	public function gcomment(){
		$id								= I('get.id');
		$uid							= C('uid');	
		$agent_id						= C('agent_id');			
		$data							= array();
		if(is_numeric($id)){
				$is_order=M('order')->where(' order_id='.$id.' and agent_id = '.$agent_id.' and uid = '.$uid.' and order_status = 6')->find();
				if(!$is_order){
					$this->error('该订单不存在或者还没有完成','未知错误！');
				}
				$order_goods	= M('order_goods');
				$data			= $order_goods->where(' order_id='.$id.' and agent_id = '.$agent_id)->select();
				if($data){
					foreach($data as $k=>$v){
									$sto_data=unserialize($v['attribute']);
						switch ($v['goods_type']) {
							case 1://裸钻
								
								$data[$k]['img']['small_path']='/Public/images/default/luozuan.jpg';
								$data[$k]['name']='裸钻'.'---'.$v['certificate_no'];
								break;
							case 2://散货
								$data[$k]['img']['small_path']='/Public/images/default/sanhuo.jpg';
								$data[$k]['name']='散货'.'---'.$v['certificate_no'];
								break;
							case 3://产品
								$data[$k]['name']='成品'.'---'.$sto_data['goods_name'];
								$data[$k]['img']= D('Common/Goods')->get_goods_images_one($v['goods_id']);
							case 4://定制							
							default:
								$data[$k]['name']='定制'.'---'.$sto_data['goods_name'];
								$data[$k]['img']= D('Common/Goods')->get_goods_images_one($v['goods_id']);
						}
						if(in_array($v['activity_status'],array('0','1'))){
							$data[$k]['goods_price']	= $v['activity_price']; 
						}else{
							if($v['goods_price_up']>'0.00'){
								$data[$k]['goods_price']	=$v['goods_price_up'];
							}else{
								$data[$k]['goods_price']	=$v['goods_price'];
							}
						}
						if($v['goods_number_up']>'0.00'){
								$data[$k]['goods_number']	=$v['goods_number_up'];
						}
						unset($data[$k]['attribute']);
						unset($sto_data);						
					}
					
					$this->data	= $data;
				}
		}else{
			$this->error('传入参数有误！','传值错误！');
		}
		$this->display();		
	} 

	
	
	
	/**
	 * auth	：fangkai
	 * content：APP订单商品评价
	 * time	：2016-11-17
	**/
    public function goodsEval(){
		$og_id	= I('og_id','','intval');
		$order_goods	= M('order_goods');
		$check_order_goods	= $order_goods
									->alias('og')
									->field('og.og_id,og.order_id,og.goods_id,og.goods_price,og.goods_number,oe.control,oe.start,oe.createtime,og.oeval,og.attribute')
									->where(array('og.og_id'=>$og_id,'og.agent_id'=>C('agent_id')))
									->join('left join zm_order_eval as oe on oe.og_id = og.og_id')
									->find(); 

		if(empty($check_order_goods)){
			$this->error('该订单商品不存在');
		}
		$check_order_goods['attribute']	= unserialize($check_order_goods['attribute']);

		$this->orderGoodsInfo = $check_order_goods;
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：APP订单商品评价保存
	 * time	：2016-11-17
	**/
    public function evalSave(){
		if(IS_AJAX){
			$order_eval		= M("order_eval");
			$order_goods	= M('order_goods');
			$start			= I('post.start','','intval');
			$control		= I('post.control');
			$og_id			= I('og_id','','intval');

			$check_order_goods	= $order_goods
										->alias('og')
										->field('og.og_id,og.order_id,og.goods_id,o.uid,u.username')
										->where(array('og.og_id'=>$og_id,'og.agent_id'=>C('agent_id')))
										->join('left join zm_order as o on o.order_id = og.order_id')
										->join('left join zm_user as u on u.uid = o.uid')
										->find();
			if(empty($start)){
				$result['status']	= 0;
				$result['msg']		= '评分不能为空';
				$this->ajaxReturn($result);
			}
			if(empty($check_order_goods)){
				$result['status']	= 0;
				$result['msg']		= '该订单商品不存在';
				$this->ajaxReturn($result);
			}
			if($check_order_goods['eval'] == 2){
				$result['status']	= 0;
				$result['msg']		= '该订单已评论';
				$this->ajaxReturn($result);
			}
			if(empty($control)){
				$result['status']	= 0;
				$result['msg']		= '评论内容不能为空';
				$this->ajaxReturn($result);
			}
			if(mb_strlen($control) > 200){
				$result['status']	= 0;
				$result['msg']		= '评论内容不能超过两百个字符';
				$this->ajaxReturn($result);
			}

			$save['start'] 		= $start;
			$save['og_id'] 		= $og_id;
			$save['control'] 	= $control;
			$save['createtime'] = time();
			$save['createip'] 	= $this->real_ip();
			$save['uid'] 		= $check_order_goods['uid'];
			$save['username']	= $check_order_goods['username'];
			$save['goods_id']	= $check_order_goods['goods_id'];
			if($order_eval->add($save)){
				$datas['oeval'] = '2';
				$order_goods->where('og_id='.$og_id)->save($datas);
				//评论完成，给该商品的总评分上加上本次评分数，但目前存在（goos_id每天变化）BUG，暂时屏蔽
				//M('goods')->where(array('goods_id'=>$check_order_goods['goods_id']))->setInc('oeval_num',$start);
				$result['status']	= 100;
				$result['msg']		= '评论成功';
			}else{
				$result['status']	= 0;
				$result['msg']		= '评论失败';
			}
			
			$this->ajaxReturn($result);
		}
	}
	
	
    /**
     * 物流信息	523
     * zhy	find404@foxmail.com
     * 2016年12月30日 14:19:57
     */
    public function exper(){
		$id								= I('get.id');
		$oe								= M('order_express');
		$order_express					= $oe->where(' order_id='.$id.' and status = 1 ')->order('ctime desc')->find();
		if($order_express){
			if($order_express['type']=='0'){
				$EI 						= new \Think\ExpressInter();	
				$order_express['data']		= $EI::get_data($order_express);
			}
			$this->order_express		= $order_express;
		}
		$this->display();
	}
	
	
    /**
     * 物流列表	524
     * zhy	find404@foxmail.com
     * 2016年12月30日 14:19:57
     */
    public function exper_list(){
		$id								= I('get.id');
		$uid							= C('uid');
		// $uid='704053';
		if(is_numeric($id)){
				$count_uid=M('order')->where(' order_id = '.$id.' and uid =  ' .$uid)->count();
				if(!$count_uid){
					$this->error('该单不存在或者你没有权限！');
				}
		}else{
			$this->error('传入参数有误！');
		}
		$dataList						= M('order_express')->field('delivery_id')->where(' order_id = '.$id.' and status = 1 ')->getField('delivery_id',true);
		foreach($dataList as $key=>$val){
			$data[$key]   				= M()->query("SELECT og.*,odg.goods_price AS delivery_price,odg.goods_number AS delivery_number,delivery_id,odg.dg_id FROM zm_order_delivery_goods odg INNER JOIN zm_order_goods AS og ON og.og_id = odg.og_id WHERE ( odg.agent_id = 7 and delivery_id = ".$val." )");
			foreach($data[$key] as $k=>$v){
				switch ($v['goods_type']) {
					case 1:
						$data[$key][$k]['img']['small_path']='/Public/images/default/luozuan.jpg';
						break;
					case 2:
						$data[$key][$k]['img']['small_path']='/Public/images/default/sanhuo.jpg';
						break;
					default:
						$data[$key][$k]['img']= D('Common/Goods')->get_goods_images_one($v['goods_id']);
				}
				
				if(in_array($v['activity_status'],array('0','1'))){
					$data[$key][$k]['goods_price']	= $v['activity_price']; 
				}else{
					if($v['goods_price_up']){
						$data[$key][$k]['goods_price']	=$v['goods_price_up'];
					}else{
						$data[$key][$k]['goods_price']	=$v['goods_price'];
					}
				}
				$data[$key]['sum_price'] += 	$data[$key][$k]['goods_price'];
			}
		}
		$this->data	=$data;
		$this->display();
	}
	
    /**
     * 物流列表	525
     * zhy	find404@foxmail.com
     * 2016年12月30日 14:19:57
     */
    public function expers(){
		$id								= I('get.id');
		$oe								= M('order_express');
		$order_express					= $oe->where(' delivery_id='.$id.' and status = 1 ')->order('ctime desc')->find();
		if($order_express){
			if($order_express['type']=='0'){
				$EI 						= new \Think\ExpressInter();	
				$order_express['data']		= $EI::get_data($order_express);
			}
			$this->order_express		= $order_express;
		}
		$this->display('exper');
	}	
	
    /**
     * 我的订单状态文字
	 * zhy
	 * 2016年6月7日 10:48:40
     */
		private function countOrder($list){
		// 单个订单和多个订单检查
		if(!empty($list['order_id'])){
			//订单金额
			if($list['order_status'] == 0 or $list['order_status'] == -1 or $list['order_status'] == -2){
				$list['order_price'] = $list['order_price'];
			}else{
				$list['order_price'] = $list['order_price_up'];
			}
			//订单对象，订单类型
			if($list['is_erji_caigou'] ==0){
				$list['order_type'] = '客户订单';
				$list['obj'] = empty($list['realname'])?$list['username']:$list['realname'];
				$list['objUrl'] = U('Admin/User/userInfo?uid='.$list['uid']);
			}else{
				$list['order_type'] = '采购单';
				$list['obj'] = $list['company_name'];
				$list['objUrl'] = U('Admin/Trader/traderInfo?tid='.$list['trader_id']);
			}
			//订单状态检查
			if($list['order_status'] == 0){//确认状态判断
				$list['status'] = L('A01');
			}elseif($list['order_status'] == 1){//支付状态判断
				$list['status'] = L('A05');
			}elseif ($list['order_status'] == 2){//已支付，配货中
				if($list['uid']){//直批客户单
					$list['status'] .= L('A06');
				}else{//分销商客户单
					$list['status'] .= L('A08');
				}
			}elseif ($list['order_status'] == 3){//发货状态判断
				$list['status'] .= L('A010');
			}elseif ($list['order_status'] == 4){//已发货给客户，待确认
				$list['status'] .= L('A014');
			}elseif($list['order_status'] == 5){//已确认收货
				$list['status'] .= L('A015');
			}elseif($list['order_status'] == 6){//已完成订单
				$list['status'] .= L('A024');
			}elseif($list['order_status'] == -1){//已取消订单
				$list['status'] .= L('A022');
			}elseif($list['order_status'] == -2){//已删除订单
				$list['status'] .= L('A023');
			}elseif($list['order_status'] == 8){//已删除订单
				$list['status'] .= L('A023');
			}else{//其他错误状态
				$list['status'] = L('A038');
			}
			return $list;
		}else{
			foreach ($list AS $array){
				$arr[] = $this->countOrder($array);
			}
			return $arr;
		}
	}
	
	
	
	
	  /**
   * 获取订单产品明细
   * @param int $order_id 订单id
   * @param string $order_sn 订单编号
   * @return array
   */
  protected function getOrderGoodsList($order_id='0',$order_sn='0'){
  	//查询订单产品(分裸钻，成品，散货)
  	$O = M('order');
  	$field = 'og.*,gl.goods_number as goods_number_luozuan';//获取证书货字段（库存）
  	$field .= ',gs.goods_weight as goods_number_sanhuo';//获取散货（库存）
  	$field .=',g.goods_number as goods_number_goods';//获取珠宝产品库存
  	//外连接查询
  	$join[1] = 'left join zm_order_goods AS og ON og.order_id = o.order_id';//订单产品
  	$join[2] = 'left JOIN zm_goods_luozuan AS gl ON gl.certificate_number = og.certificate_no';//裸钻产品
  	$join[3] = 'left JOIN zm_goods_sanhuo AS gs ON gs.goods_sn = og.certificate_no AND gs.goods_id = og.goods_id';//散货产品
  	$join[4] = 'left JOIN zm_goods AS g ON g.goods_id = og.goods_id';//成品产品
  	//获取订单产品数据
  	$goodsList = $O->alias('o')->field($field)->where('o.agent_id = '.C('agent_id').' and o.order_id = '.$order_id)->join($join)->group('og_id')->select();
  	$luozuanAdvantage2 = $this->countLuozuanAdvantage(1,1);
  	$orderInfo = $O->where('agent_id = '.C('agent_id'))->find($order_id);
  	//把数据遍历到页面数据
  	foreach ($goodsList as $key => $value) {
  		$value['attribute'] = unserialize($value['attribute']);
  		$info = $value['attribute'];//订单产品信息
  		if($value['goods_type'] == 1){//裸钻产品
  			$value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
  			$value['goods_number_up'] = intval($value['goods_number_up']);
  			$value['goods_number'] = intval($value['goods_number']);
  			$value['goods_sn_a'] = $info['certificate_type'].$value['certificate_no'];
  			//所有库存不为0的裸钻都是1
  			if($value['goods_number_luozuan']){
  				$value['goods_number_luozuan'] = 1;
  			}
  			$value['advantage'] = $info['dia_discount']+$luozuanAdvantage2;
  			//销售折扣（根据销售价格计算）
  			if(round($value['goods_price_up'],2) > 0) $price = $value['goods_price_up'];
  			else $price = $value['goods_price'];
  			$dollar_huilv = $orderInfo['dollar_huilv'];
  			$value['xian_advantage'] = formatPrice($price/$info['weight']/$dollar_huilv/$info['dia_global_price']*100);
  			$array['luozuan'][] = $value;
  		}elseif ($value['goods_type'] == 2){//散货产品
        //计算每卡单价
        //if($info['goods_price2'] > 0){ $info['goods_price'] = $info['goods_price2'];}
        if($value['goods_number_up']>0){
          $value['goods_price_sanhuo'] = formatPrice($value['goods_price']/$value['goods_number_up']);//散货单价
        }else{
          $value['goods_price_sanhuo'] = formatPrice($value['goods_price']/$value['goods_number']);//散货单价
        }

        
        //没产品或没库存都是0库存
        if(!isset($value['goods_number_sanhuo'])){
          $value['goods_number_sanhuo'] = '0';
        }
        //4c信息
        $value['4c'] = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
        //$value['is_site_goods'] = M('goods_sanhuo')->where('goods_id = ' . $value['goods_id'] . ' and agent_id = ' . C('agent_id'))->count();    //是自己站点的商品
        $array['sanhuo'][] = $value;
      }elseif ($value['goods_type'] == 3 or $value['goods_type'] == 4){//珠宝成品和珠宝定制
  			//没确认前订单产品单价
  		    $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
  		    if($value['goods_type'] == 3){
  		        if($info['sku_sn']){
  		            $attributes = $info['goods_sku']['attributes'];//使用订单里面的SKU规格，防止修改产品
  		            $sku = $this->getGoodsSku($info['category_id'],$info['goods_id'],$info['sku_sn'],$attributes);
  		            $value['4c'] = $sku['sku'];
  		            $value['goods_sku_number'] = $sku['goods_number'];
  		        }else{
  		            $value['4c'] = '你没有选择规格';
  		        }
  		    }elseif ($value['goods_type'] == 4){
  		        $value['4c'] = $this->getJGSdata($info);
  		    }
  			$value['goods_sn'] = $info['goods_sn'];
  			$value['goods_name'] = $info['goods_name'];
  			$value['goods_number'] = round($value['goods_number']);
            $value['is_site_goods'] = D('Common/Goods')->where('goods_id='.$value['goods_id'].' and agent_id ='.C('agent_id'))->count();    //是自己站点的商品

  			$array['consignment'][] = $value;
  		}
  		//暂时先屏蔽代销货，后期还需要根据新的产品结构修改 2015年11月23号10:44 郭冠常
//   		elseif ($value['goods_type'] == 4){
//   		    //没确认前订单产品单价
//   		    $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
//   		    $value['goods_number_up'] = intval($value['goods_number_up']);
//   			$value['goods_number'] = intval($value['goods_number']);
// 			$value['4c'] = $this->getJGSdata($info);
// 			$value['goods_price2'] = formatPrice($value['goods_price']/$value['goods_number']);
// 			$value['goods_price3'] = formatPrice($value['goods_price_up']/$value['goods_number_up']);
// 			$value['goods_sn'] = $info['goods_sn'];
// 			$value['goods_name'] = $info['goods_name'];
//   			$value['goods_number_up'] = round($value['goods_number_up']);
//   			$value['goods_number'] = round($value['goods_number']);
//   			$array['consignment'][] = $value;
//   		}
  	}
  	return $array;
  }
  
	
  /**
   * 计算上级产品加点，不计算自己的产品
   * 分为销售折扣，和采购折扣
   * @param number $type 1销售折扣2采购折扣
   * @param number $goods_type 1证书货2散货
   * @return number
   */
  protected function countLuozuanAdvantage($type=1,$goods_type='1'){
  	//产品类型加点配置键
  	if($goods_type == 1){
  		$config_key = 'luozuan_advantage';
  	}elseif ($goods_type == 2){
  		$config_key = 'sanhuo_advantage';
  	}
  	if($type == 1){//销售折扣
  		if(in_array(7,$this->group)){
  			return C($config_key);
  		}else{
  			return 0;
  		}
  	}elseif($type == 2){//采购折扣
  		if(in_array(7,$this->group)){
  			return '0';
  		}else{
  			return 0;
  		}
  	}
  }
	
  /**
   * 获取产品的SKU
   * @param int $sku_id
   */
  protected function getGoodsSku($category_id,$goods_id,$sku_sn,$attributes){
      $GS   = M('goods_sku');
      $info = $GS->where('goods_id = '.$goods_id." and sku_sn = '$sku_sn'")->find();
      $GA   = M('goods_attributes');//公共
      $GCA  = M('goods_category_attributes');
      $GAV  = M('goods_attributes_value');
      $gacList = $GCA->where('category_id = '.$category_id.' and type = 2 ')->select();
      $ids = $this->parIn($gacList, 'attr_id');
      $list = $GA->where('attr_id in('.$ids.')')->select();
      $list = $this->_arrayIdToKey($list);
      $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
      foreach ($attrValueList as $key => $value) {
          $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
      }
      $attr = explode('^', $attributes);
      $arr['sku'] = 'SKU编号:'.$sku_sn.';';
      $arr['goods_number'] = $info['goods_number'];
      foreach ($attr as $key => $value) {
          $active = explode(':', $value);
          $arr['sku'] .= $list[$active[0]]['attr_name'].':';
          $sub = $list[$active[0]]['sub'];
          if($list[$active[0]]['input_type'] == 2){
              $arr['sku'] .= $sub[$active[1]]['attr_value_name'].';';
          }elseif ($list[$active[0]]['input_type'] == 3){
              $arr['sku'] .= $active[1].';';
          }
      }
      return $arr;
  }
  
  
  
  
  
  
  
  /**
   * 生成IN参数 
   * @param array $array
   * @param int $id
   * @return Ambigous <string, unknown>
   */
  protected function parIn($array,$id){
  	$ids ='';
  	foreach ($array as $key => $value) {
  		if($key) $ids .= ','.$value[$id];
  		else $ids .= $value[$id];
  	}
  	return $ids;
  }
  
  
  
  
  /**
   * 把数组里的字段值作为KEY存放
   * @param array $array
   * @param string $id 没有键名，第一个就是键名
   */
  protected function _arrayIdToKey($array,$id=''){
	if(!$id){$ids = array_keys($array[0]);$id = $ids[0];}
  	foreach ($array as $key => $value) {
		$arr[$value[$id]] = $value;
	}
	return $arr;
  }
  
  
  
    /**
   * 获取产品的金工石数据
   * @param 订单产品数据
   */
    public function getJGSdata($info){
        $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
        $deputystoneM        = M('goods_associate_deputystone');
        $info['deputystone_name']  = "";
        $info['deputystone_price'] = "";
        if($info['deputystone']['gad_id']){
            $deputystone = $deputystoneM->where('gad_id = '.$info['deputystone']['gad_id'])->select();
            if($deputystone){
                $info['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
                $info['deputystone_price'] = $deputystone[0]['deputystone_price'];
            }
        }

        $material = $info['associateInfo'];
        $luozuan = $info['luozuanInfo'];
        $string = '材质:'.$material['material_name'].',金重:'.$material['weights_name'];
        $string .=',损耗:'.$material['loss_name'].',基本工费：'.$material['basic_cost'];
        $string .=',材质金价:'.$material['gold_price'].'<br />';
        
        if($luozuan['shape_id']){
			$shape_name = M('goods_luozuan_shape')->where('shape_id = '.$luozuan['shape_id'])->field('shape_name')->find();
		}

        $string .= '//主石:'.$luozuan_shape_array[$info['luozuanInfo']['shape_id']] .',分数:'.$luozuan['weights_name'].'CT,'.$info['deputystone_name'].',镶嵌工费:'.$luozuan['price'].'元';
        if($info['hand']){ $string .= '//手寸:'.$info['hand'].';';}
        if($info['word']){ $string .= '//刻字:'.$info['word'].';';}
        if($info['matchedDiamond']){$string .= '//匹配主石:'.$info['matchedDiamond'].';';};
        
        return $string;
    }

    /**
     * 上传支付凭证
     * zhangchaohao
     * 2016年1月10日
    */
    public function paymentUpload(){
        //$this->checkTokenUid($this->uid,$this->token);
        //$order_id   = 903615;//I("get.goodid");
        $order_id   = I("get.order_id", 0, 'intval');

        $O          = M('order');
        $where      = 'o.agent_id = '.C('agent_id').' and o.uid = '.$_SESSION['app']['uid'] .' and ';        
        $info       = $O->alias('o')->where($where.' o.order_id = '.$order_id)->field($field)->join($join)->find();
		$OP = M('order_payment');
		$voucher_info = $OP->where('agent_id ='.C('agent_id').' AND order_id = '.$order_id)->select();
		$this->voucher_count = $voucher_info ? count($voucher_info) : 0;

        // if(empty($info)){           
        //     $this->echo_data('101','你没有该订单!');
        // }
        // if($info['order_status'] < 1){        
        //     $this->echo_data('102','订单是取消或待确认状态!');
        // }

        //$this->orderInfo = $this->countOrder($info);                
        //订单的信息
        if($info['order_price_up'] > 0){
        	$info['order_price'] = $info['order_price_up'];
        }
        $this->order_info = array('order_sn' => $info['order_sn'], 'order_price' => $info['order_price'], 'order_status' => $info['order_status'],'order_id' => $info['order_id']);
        //获取入款方式
        $this->payment_mode = M('payment_mode')->where('agent_id = '.C('agent_id'))->select();
        $this->display();
        
    }

    /**
     * 上传支付凭证
     * zhangchaohao
     * 2016年1月10日
    */
    public function paymentUploadSave(){
        //$this->checkTokenUid($this->uid, $this->token);
        $order_id   = I("post.order_id", 0, 'intval');
        $this->order_id = $order_id;
        $payment_mode = I('post.payment_mode', 0, 'intval');
        $PM  = M('payment_mode');
        $res = $PM->where('mode_id = '.$payment_mode.' and agent_id = '.C('agent_id'))->find();
        
        if(!$res){
            $result['info'] 	= '请选择正确的支付方式';
			$result['status']	= 202;
            $this->result = $result;
			$this->display();
			exit;  
        }

        $payment_price = I('post.payment_price', 0, 'floatval');
        $payment_user = I('post.payment_user', '');
        $create_time = I('post.create_time', '0');     
        $payment_note    = I('post.payment_note', '');
        $payment_voucher = '';
      		
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize	= 3145728 ;// 设置附件上传大小
		$upload->exts		= array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath	= './Public/Uploads/pay/'; // 设置附件上传根目录
		$upload->savePath	= ''; // 设置附件上传（子）目录
		$upload->saveName 	= 'time';
		$upload->replace 	= true;
		
		// 上传文件
		$info   =   $upload->uploadOne($_FILES['payment_voucher']);

		
		if($info){// 上传成功
			$payment_voucher       = str_replace('./Public/', '', $upload->rootPath.$info['savepath'].$info['savename']);

		}else{			
			$result['info']  =  $upload->getError();
            $result['status']= 101;
            $this->result = $result;	        
			$this->display();  
			exit;
		}
        
        $O          = M('order');
        $where      = 'agent_id = '.C('agent_id').' and uid = '.$_SESSION['app']['uid'] .' and ' ;     
        $info       = $O->where($where.' order_id = '.$order_id)->find();        
        if(empty($info)){           
            $result['info'] 	= '你没有该订单';
			$result['status']	= 101;
            $this->result = $result;
			$this->display();  
			exit;
        }

        $payment['order_id']      = $info['order_id'];
        $payment['order_sn']      = $info['order_sn'];
        $payment['uid']           = $_SESSION['app']['uid'] ;
        //$payment['tid'] = ;
        $payment['payment_price'] = $payment_price;//收款金额
        //$payment['discount_price'] = 0;//折扣金额
        //$payment['more_price'] = 0; //截余金额
        //$payment['verification_price'] = 0; //实际核销金额
        $payment['payment_mode']  = $payment_mode;//收款方式
        $payment['payment_user']  = $payment_user;//收款账号
        $payment['payment_note']  = $payment_note; //收款备注
        $payment['payment_status'] = 1;
        $payment['payment_voucher'] = $payment_voucher;//收款凭证
        //$payment['parent_type'] = 0;
        //$payment['trade_no'] = '';
        $payment['create_time']    = strtotime($create_time);
        $payment['agent_id']       = C('agent_id');
        //$payment['trade_no'] = '';
        $OP = M('order_payment');
        $action   = $OP->add($payment);
        
        if($action === false){
            $result['info']  = '上传凭证失败';
            $result['status']	= 301;
        }else{
            $result['info']  = '上传凭证成功';
            $result['status']= 100;
        }
        $this->result = $result;
		$this->display();  
    }	


    /**
     * 发票信息页
     * zhy	find404@foxmail.com
     * 2017年1月21日 10:00:21
     */
    public function invoice(){
		
	$this->display();  
	}

	
}