<?php
namespace Mobile\Controller;
use Think\Exception;
class NewUserController extends NewMobileController{
	/**
	 * auth	：zengmingming
	 * content：构造函数
	 * time	：2017-07-26
	**/
	
    private static $LinkPoolKey     = 1;     //mysql使用的第几个连接池	
	
	
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid");
	}
	
	
	
 
	
    /**
     * 我的主页
     * zhy	find404@foxmail.com
     * 2017年8月10日 15:35:08
     */
	public function index() {
		//$this->msg_count=M('user_msg')->where('msg_type = 1 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->count();
		$this->display();
	}
	/**
	 * auth	：zengmingming
	 * content：加入分销
	 * time	：2017-7-26
	**/
    public function traderAdd() {
		if (IS_POST) {
			$T    				= M('trader');
			$trader['agent_id']			= $this->agent_id; 
			$trader["trader_name"]		= I('trader_name','');
			$trader["company_name"]		= I('company_name','');
			$trader["domain"]			= I('domain','');
			$trader["contacts"]			= I('contacts','');
			$trader["phone"]			= I('phone','','number_int');
			$trader["business_license"]	= I('business_license','');
			$trader["funds"]			= I('funds','');
			$trader["province_id"]		= I('province_id','');
			$trader["city_id"]			= I('city_id','');
			$trader["note"]				= I('note','');
			if(empty($trader["trader_name"])){
				$result = array('success'=>false, 'msg'=>"必须填写分销商名称！");
            	$this->ajaxReturn($result);
			}
			if(empty($trader["company_name"])){
				$result = array('success'=>false, 'msg'=>"必须填写公司名称");
            	$this->ajaxReturn($result);
			}
			if(empty($trader["contacts"])){
				$result = array('success'=>false, 'msg'=>"必须填写联系人！");
            	$this->ajaxReturn($result);
			}
			if(!is_mobile($trader["phone"])){
				$result = array('success'=>false, 'msg'=>"手机号码格式不正确！");
            	$this->ajaxReturn($result);
			}
			//判断是否已审核成为分销商
			$traderArr = $T->where("phone=".$trader["phone"]." and status=1")->field("tid")->find();
			if($traderArr["tid"]!=""){
				$result = array('success'=>false, 'msg'=>"你已是分销商，无需再申请！");
            	$this->ajaxReturn($result);
			}
			if(empty($trader["province_id"])){
				$result = array('success'=>false, 'msg'=>"必须选择省市");
            	$this->ajaxReturn($result);
			}
			if(empty($trader["city_id"])){
				$result = array('success'=>false, 'msg'=>"必须选择地区");
            	$this->ajaxReturn($result);
			}
			if(empty($trader["note"])){
				$result = array('success'=>false, 'msg'=>"必须填写申请内容");
            	$this->ajaxReturn($result);
			}
			$Tid = $T->data($trader)->add();
			if ($Tid) {
				$result = array('success'=>true, 'msg'=>"预约成功，请耐心等待我们审核！",url=>"/Index/index.html");	
			}else{
				$result = array('success'=>false, 'msg'=>"预约失败");	
			}
			$this->ajaxReturn($result);
		}else{
			//省份列表
            $R = M('region');
			$provinceList = $R->where('parent_id = 1')->select();
			foreach($provinceList as $key=>$val){
				$this->provinceArr .= "\"<input type='hidden' name='province_id' value='{$val['region_id']}' />{$val['region_name']}\","; 	
			}
            $this->active = "traderAdd";
			$this->display();
		}
	}
	/**
	 * auth	：zengmingming
	 * content：切换城市
	 * time	：2017-7-27
	**/
    public function loadcity(){
		//省份列表
		$R = M('region');
		//获取省份的数组的Key值
		$provinceKey = I('provinceKey','','int');
		$provinceList = $R->where('parent_id = 1')->field("region_id")->select();
		$parent_id = $provinceList[$provinceKey]["region_id"];
		$cityList = $R->where('parent_id='.$parent_id)->field("region_id,region_name")->select();
		$cityArr = array();	
		foreach($cityList as $key=>$val){
			$cityArr[] = "<input type='hidden' name='city_id' value='{$val['region_id']}' />{$val['region_name']}";
		}
		$result = array('success'=>false, 'data'=>$cityArr);
        $this->ajaxReturn($result);	
	}
	
    /**
     * 我的订单列表
     * zhy	find404@foxmail.com
     * 2017年8月10日 15:35:08
     */
    public function orderList(){
		if($_POST){
			$order_status 		= ' >=-1' ;
			$limit 				= '';
			if($_POST['order_status']){
				$order_status 	= ' in ( '.$_POST['order_status'].')';
			}
			
			$where        		= "  agent_id ='".C('agent_id')."' and  uid ='".$this->uId."'  and is_yiji_caigou < 1 and is_erji_caigou = 0  and order_status " .$order_status; 
 
 			if(is_numeric(intval($_POST['order_sn']))){
				$where 		   .= " AND order_sn like '%".$_POST['order_sn']."%'";
			}
 
 
 			if(is_numeric(intval($_POST['page']))  &&  is_numeric(intval($_POST['page_len']))){
				$limit 			= $_POST['page']*$_POST['page_len'].','.$_POST['page_len']; 
			} 
			$data 				= M('order')-> where($where)-> field('order_sn,order_price_up,order_price,order_status,order_id') ->order('create_time DESC')->limit($limit)->select();
 
			
			if($data){
				foreach($data as $key=>$val){
					if($val){
						 switch($val['order_status']){
							case 0:  $data[$key]['status_txt'] = '待确认';
							break;
							case 1:  $data[$key]['status_txt'] = '待付款';
							break;
							case 2:  $data[$key]['status_txt'] = '待配货';
							break;
							case 3:  $data[$key]['status_txt'] = '待发货';
							break;
							case 4:  $data[$key]['status_txt'] = '待收货';
							break;
							case 5:  $data[$key]['status_txt'] = '已收货';
							break;
							case 6:  $data[$key]['status_txt'] = '已完成';
							break;
							case -1: $data[$key]['status_txt'] = '已取消';
							break;
							default: $data[$key]['status_txt'] = '未知状态'; 
							break;
						}
						$attribute = M('order_goods')-> where(' order_id = '.$val['order_id'])->getField('attribute',true);

						foreach($attribute as $key1=>$val1){
							if($val1){
								$goods = unserialize($val1);
								if(!$goods['thumb']){
									if($goods['luozuan_type']){
										$goods['thumb'] = 'http://'.C('DEFAULT_AGENT_MOBILE_DOMAIN').'/Public/images/default/luozuan.jpg';
									}else{
										$goods['thumb'] = 'http://'.C('DEFAULT_AGENT_MOBILE_DOMAIN').'/Public/images/default/luozuan.jpg';
									}
								}
								$data[$key]['thumb'][] 		= $goods['thumb'];							
							}
						}
						$data[$key]['goodsNum'] = count($attribute);
					}
				}
			}
			$this             -> echoJson( array('code'=>100, 'data'=>$data) );
		}else{
			$this->display();
		}
	}	
		
		
		
    /**
     * 我的订单列表
     * zhy	find404@foxmail.com
	 * 2016年6月6日 16:23:18
     */
    public function orderInfo(){
		$order_id     	  	= I("get.order_id");
		$O 					= M('order');
		$where 				= 'o.agent_id = '.C('agent_id').' and ';
		$field 				= 'o.*,u.realname,u.username,t.trader_name,';
		$field 			   .= 'au.nickname as parent_admin_name,tu.trader_name as parent_trader_name';
		$join['u'] 			= 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
		$join['t'] 			= 'LEFT JOIN `zm_trader` AS t ON t.tid = o.tid';
		$join['au'] 		= 'LEFT JOIN `zm_admin_user` AS au ON o.parent_id = au.user_id';
		$join['tu'] 		= 'LEFT JOIN `zm_trader` AS tu ON o.parent_id = tu.tid';
		$info 				= $O->alias('o')->where($where.' o.order_id = '.$order_id)->field($field)->join($join)->find();
		if($info && $info['address_id']){
			$this->orderInfo = $this->countOrder($info);
			if($this->orderInfo['order_price']){
				$_SESSION['info_price_to_payment'] =$this->orderInfo['order_price'] ;
			}
			
			$data = $this->getOrderGoodsList($order_id);
			//重新拼接匹配数据					
			$i=0;
			foreach($data['luozuan'] as $key=>$val){
				foreach($data['consignment'] as $k=>$v){
					if($val["dingzhi_id"]!="" && $v["dingzhi_id"]!="" && $val["dingzhi_id"]==$v["dingzhi_id"]){
						$data["dingzhi"][$i]["product"] = $v;	
						$data["dingzhi"][$i]["luozuan"] = $val;	
						unset($data['luozuan'][$key]);
						unset($data['consignment'][$k]);
						$i++;
					}	
				}
			}
			
			//订单产品数据
			$this->goodsList = $data;
			//print_r($this->goodsList);exit;
			//地址
			
			if(empty($info['address_info'])){
				$data['addressInfo'] =  D("Common/UserAddress")->getAddressInfo($info['address_id']);
			}else{
				$data['addressInfo'] = $info['address_info'];
			}


			if($info['delivery_mode']){
					//$OrderPayment = new \Common\Model\OrderPayment();
				$data= D('Common/OrderPayment')->this_dispatching_way($info['delivery_mode'],$data);
			}
			$this->addressInfo =$data;
		}else{
			$this->redirect('/User/orderList');
		}
		//订单详情
		$this->display();
	}
		
		
    /**
     * 我的订单更新状态
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function EvaluateList(){
		$id								= I('get.order_id');
		$uid							= $this->uId;	
		$agent_id						= C('agent_id');			
		$data							= array();
		if(is_numeric($id)){
				$is_order=M('order')->where(' order_id='.$id.' and agent_id = '.$agent_id.' and uid = '.$uid.' and order_status in(5, 6)')->find();
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
     * 我的订单更新状态
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function Evaluate(){
		if($_POST){
			$order_eval		= M("order_eval");
			$order_goods	= M('order_goods');
			$start			= I('post.start','','intval');
			$control		= I('post.control');
			$og_id			= I('og_id','','intval');

			$check_order_goods	= $order_goods
										->alias('og')
										->field('og.og_id,og.order_id,og.goods_id,o.uid,u.username,og.oeval')
										->where(array('og.og_id'=>$og_id,'og.agent_id'=>C('agent_id')))
										->join('left join zm_order as o on o.order_id = og.order_id')
										->join('left join zm_user as u on u.uid = o.uid')
										->find();
			if(empty($start)){
				$result['iden']	= 0;
				$result['msg']		= '评分不能为空';
				$this->ajaxReturn($result);
			}
			if(empty($check_order_goods)){
				$result['iden']	= 0;
				$result['msg']		= '该订单商品不存在';
				$this->ajaxReturn($result);
			}
			if($check_order_goods['oeval'] == 2){
				$result['iden']	= 0;
				$result['msg']		= '该订单已评论';
				$this->ajaxReturn($result);
			}
			if(empty($control)){
				$result['iden']	= 0;
				$result['msg']		= '评论内容不能为空';
				$this->ajaxReturn($result);
			}
			if(mb_strlen($control) > 200){
				$result['iden']	= 0;
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
				$order_goods->where('og_id='.$og_id)->setField('oeval',2);
				//评论完成，给该商品的总评分上加上本次评分数，但目前存在（goos_id每天变化）BUG，暂时屏蔽
				//M('goods')->where(array('goods_id'=>$check_order_goods['goods_id']))->setInc('oeval_num',$start);
				$result['iden']	= 100;
				$result['msg']		= '评论成功';
			}else{
				$result['iden']	= 0;
				$result['msg']		= '评论失败';
			}
			$this->ajaxReturn($result);
		}else{
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
	}
		
		
    /**
     * 我的订单物流信息列表
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function ExperList(){
		$id								= I('get.order_id');
		$uid							= $this->uId;
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
     * 我的订单物流信息
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function Exper(){
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
     * 我的订单更新状态
	 * zhy
	 * 2016年6月6日 16:23:18
     */
	public function update_order(){
		$order_id     	  = I("post.order_id");
		$order_status     = I("post.order_status");
		$where = "agent_id='".C('agent_id')."' and uid='".$this->uId."' AND order_id=".$order_id;
    	$data = array('status'=>0,'msg'=>'订单取消成功','href'=>$href);
        $order = M('order')->where($where)->find();
    	if(!$order || $order_id == 0){
    		$this->ajaxReturn($data);
    	}else{
    		switch($order_status){
    			case '-1':
    				$order['order_status'] = -2;
    				$data['msg'] = "订单删除成功";
					$data['status'] = 6;
    			break;
    			case '0':
    				$order['order_status'] = -1;
    				$data['msg'] = "取消订单成功";
					$data['status'] = 1;
    			break;
    			case '4':
    				$order['order_status'] =5;
    				$data['msg'] = "确认收货成功";
					$data['status'] = 4;
    			break;
    		}
			
			if($order_status=='-1'){
				  M('order')->data($order)->delete();
			}elseif(M('order')->data($order)->save()){
    			if($order_status == 4){
    				//更新发货表状态
    				$data['confirm_type'] = 2;
    				$data['confirm_time'] = time();
    				M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id=$order_id AND uid='".$this->uId."'")->data($data)->save();
    			}
    		}
    		$this->ajaxReturn($data);
    	}
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
   * 获取产品的金工石数据，
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
     * 我消息
     * zhy	find404@foxmail.com
	 * 2016年6月6日 16:23:18
     */
	 public function msg(){
		if($_POST){
			$data		 = [];
			$data['iden'] = 100;
			$M 	   		 = M('user_msg');
			$where 		 = ' uid = '.session('m.uid').' and agent_id = '.C('agent_id');
			if(is_numeric($_POST['msg_id'])){
					if ($M->where($where.' and msg_id = '.$_POST['msg_id'])->delete()) {
						$data['msg']='删除成功！';
					} else {
						$data['iden'] = 101;
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
	
	/**
     * 个人消息
     * zhy	find404@foxmail.com
	 * 2016年6月6日 16:23:18
     */
	public function Info(){
        $U  = M('user');
		if($_POST){
			$_POST['birthday']  = strtotime($_POST['birthday']);
            $U    -> where('uid='.$this->uId.' and agent_id = '.$this->agent_id)->field('birthday,sex,email,phone,realname')->filter('htmlspecialchars')->save($_POST);
			if($U){
				$result  = array('iden'=>100, 'msg'=>'资料保存成功');
			}else{
				$result  = array('iden'=>101, 'msg'=>'资料保存失败');
			}			
			$this->ajaxReturn($result);
		}else{
			$info = $U->find($this->uId);
			$info['birthday'] = date("Y-m-d",$info['birthday']);
			$this->info = $info;			
			$this->display();
		}
	}	 
	
	
	
 
	
	
	/**
     * 收藏
     * zhy	find404@foxmail.com
	 * 2016年6月6日 16:23:18
     */
	public function Collect(){
		$coll 	   	 = M('collection');
		$where 		 = ' uid = '.$this->uId.' and agent_id = '.$this->agent_id;
		if($_POST){
			$data		 = [];
			$data['iden'] = 100;
			if($_POST['deid']){
					if ($coll->where($where.' and id in ( '.$_POST['deid'].')')->delete()) {
						$data['msg']='删除成功！';
					} else {
						$data['iden'] = 101;
						$data['msg']='删除失败！';
					}
			}
			
			if($_POST['addid']){
				 $data = D('Common/Collection')->addCollectionToCart($_POST['addid']);
				 if($data['status']=='100'){
					 $data['msg']='加入购物车成功！'; 
				 }else{
					$data['iden'] = 101;
					$data['msg']='加入购物车失败！';
				 }
			}
			
			$this->ajaxReturn($data);
		}else{
			$data = $coll->where($where)->limit($_POST['page'],$_POST['page_len'])->select();
			if(!empty($data)){
				foreach($data as &$row){
					$row['goods_attr'] = unserialize($row['goods_attr']);
				}
			}
			$this->publicdataList = $data;
			$this->display();
		}
	}
	
	
	
	/**
     * 售后服务主页
     * zhy	find404@foxmail.com
	 * 2017年8月21日 18:17:55
     */
	public function AfterSer(){	
		if($_POST){
			$data		 				= [];
			$data['iden'] 			    = 100;
			$where                      = array();
			$where['zm_order.agent_id'] = $this->agent_id;
			$where['zm_order.order_status'] = '6';
			$where['zm_order.uid']      = $this->uId;
			$where['zm_order.order_id'] = array('exp'," in (select zm_order_goods.order_id from zm_order_goods where agent_id = '".C('agent_id')."' and goods_type in (3,4)) ");
			$order_list                 = M('order') 
											->fetchsql(false)
											-> where($where) 
											-> group('zm_order.order_id desc') 
											-> limit($_POST['page'],$_POST['page_len']) 
											-> field('zm_order.order_sn,zm_order.create_time,zm_order.order_id') 
											-> select();
			if($order_list){
				foreach($order_list as &$row){
					$re                 = M('order_goods') 
											->fetchsql(false)->join('left join zm_order_service on zm_order_service.og_id = zm_order_goods.og_id') 
											-> where('goods_type in (3,4) and zm_order_goods.order_id='.$row['order_id']) 
											-> field('zm_order_goods.og_id,goods_number,goods_type,attribute,zm_order_service.order_service_id as status') 
											-> select();
					foreach($re as $k=>$r){
						$goods                                 = unserialize( $r['attribute'] );
						$goods                                 = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
						$row['goods_list'][$k]['goods_name']   = $goods['goods_name'];
						$row['goods_list'][$k]['goods_number'] = intval($r['goods_number']);
						$row['goods_list'][$k]['thumb']        = $goods['thumb'];
						$row['goods_list'][$k]['status']       = $r['status'];
						$row['goods_list'][$k]['goods_type']   = $r['goods_type'];
						$row['goods_list'][$k]['og_id']        = $r['og_id'];
						$row['goods_list'][$k]['goods_id']     = $goods['goods_id'];
						
					}
					$row['create_time'] = date( 'Y-m-d H:i:s', $row['create_time'] );
				}
			}
			$data['data'] = $order_list;
			$this->ajaxReturn($data);			
		}else{
			$this->display();
		}
	}
	
	
	
	/**
     * 申请售后详情页
     * zhy	find404@foxmail.com
	 * 2017年8月22日 15:54:59
     */
	public function ApplySer(){
		if($_POST){
				$info                 = M('order_service') -> where("og_id = '".$_POST['og_id']."'")->find();
				if($info){
					$data['iden']      = 0;
					$data['msg']      = '不要重复提交';
					$this->ajaxReturn($data);
					die;
				}
				$data                 = array();
				$data['order_id']     = $_POST['order_id'];
				$data['og_id']        = $_POST['og_id'];
				$data['service_type'] = $_POST['service_type'];
				$data['description']  = $_POST['description'];		
				$data['user_id']      = $_SESSION['m']['uid'];
				$data['user_name']    = $_SESSION['m']['username'];
				$data['phone']        = $_SESSION['m']['phone'];
				$data['agent_id']     = $this->agent_id;
				$info                 = array();
				if(M('order_service') -> add($data)){
					$info['iden']      = 100;
					$info['msg']      = '操作成功';
					$this->ajaxReturn($info);
				}else{
					$info['iden']      = 0;
					$info['msg']      = '操作失败';
					$this->ajaxReturn($info);
				}
		}else{
			$where                = array();
			$where['agent_id']    = $this->agent_id;
			$where['og_id']       = $_GET['og_id'];
			$r                    = M('order_goods') -> where( $where ) -> field('og_id,order_id,goods_type,attribute,goods_price,goods_number') -> find();
			$goods                = unserialize( $r['attribute'] );
			$goods                = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
			$info['goods_name']   = $goods['goods_name'];
			$info['goods_id']     = $goods['goods_id'];
			$info['goods_type']   = $goods['goods_type'];			
			$info['thumb']        = $goods['thumb'];
			$info['goods_number'] = intval($r['goods_number']);
			$info['goods_price']  = $r['goods_price'];
			$info['order_id']     = $r['order_id'];
			$info['og_id']        = $r['og_id'];
			$this -> info         = $info;
			$this -> display();
		}
	}		
	
	
	/**
     * 售后进度
     * zhy	find404@foxmail.com
	 * 2017年8月21日 18:17:55
     */
	public function Schedule(){
		if($_POST){
			$data		 					= [];
			$data['iden'] 			    	= 100;
			$where                          = array();
			$where['zm_order.agent_id']     = $this->agent_id;
			$where['zm_order.order_status'] = '6';
			$where['zm_order.uid']          = $this->uId;
			$where['zm_order.order_id']     = array('exp'," in (select zm_order_service.order_id from zm_order_service where agent_id = '".$this->agent_id."') ");
			$order_list        				= M('order') 
												-> where( $where ) 
												-> group('zm_order.order_id desc') 
												-> limit($_POST['page'],$_POST['page_len'])
												-> field('zm_order.order_id,zm_order.order_sn,zm_order.create_time') 
												-> select();
			if($order_list){									
				foreach($order_list as &$row){
					$re 						= M('order_goods') 
													->fetchsql(false)
													->join('join zm_order_service on zm_order_service.og_id = zm_order_goods.og_id') 
													-> where('goods_type in (3,4) and zm_order_goods.order_id='.$row['order_id']) 
													-> field('zm_order_service.create_time,zm_order_goods.og_id,goods_number,goods_type,attribute,zm_order_service.order_service_id,zm_order_service.status') 
													-> select();
					foreach($re as $k=>$r){
						$goods                                 = unserialize( $r['attribute'] );
						$goods                                 = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
						$row['goods_list'][$k]['goods_name']   = $goods['goods_name'];
						$row['goods_list'][$k]['goods_number'] = intval($r['goods_number']);
						$row['goods_list'][$k]['thumb']        = $goods['thumb'];
						$row['goods_list'][$k]['status']       = $r['status'];
						$row['goods_list'][$k]['goods_type']   = $r['goods_type'];
						$row['goods_list'][$k]['og_id']        = $r['og_id'];
						$row['goods_list'][$k]['create_time']      = date( 'Y-m-d H:i:s', $r['create_time'] );
						$row['goods_list'][$k]['order_service_id'] = $r['order_service_id'];
						$row['goods_list'][$k]['goods_id']      = $goods['goods_id'];
					}
					$row['create_time'] = date( 'Y-m-d H:i:s', $row['create_time'] );
				}
			}
			$data['data'] = $order_list;
			$this->ajaxReturn($data);
		}else{
			$this -> display();
		}
	}
	
	
	/**
     * 售后进度详情
     * zhy	find404@foxmail.com
	 * 2017年8月25日 17:41:14
     */
	public function SCInfo(){
		$order_service_id   = I('id','','intval');
		if($_POST){
				$info               = array();
				if($order_service_id>0){
					$data           = array();
					$data['status'] = 2;
					$code           = M('order_service') -> where(" order_service_id = '$order_service_id' and agent_id = ".$this->agent_id) -> save($data);
					if( $code ){
						$info['iden'] = 100;
						$info['msg'] = '操作成功';
					}else{
						$info['iden'] = 0;
						$info['msg'] = '操作失败';
					}
				}
			$this->ajaxReturn($info);
		}else{
			$where                   = array();
			$where['zm_order_service.agent_id'] = $this->agent_id;
			$where['zm_order_service.user_id']  = $this->uId;
			$where['zm_order_service.order_service_id']   = $order_service_id;
			$info                    = M('order_service') -> join('
				join zm_order on zm_order.order_id = zm_order_service.order_id
				join zm_order_goods on zm_order_goods.og_id = zm_order_service.og_id
			')->fetchsql(false) -> where( $where ) -> field('zm_order_service.*,order_sn,attribute') -> find();
			$goods                   = unserialize( $info['attribute'] );
			$goods                   = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
			$info['goods_name']      = $goods['goods_name'];
			$info['thumb']        	 = $goods['thumb'];
			$this -> info            = $info;
			$this -> display();
		}
	}	
	
	
	
	/**
     * 地址管理
     * zhy	find404@foxmail.com
	 * 2017年8月21日 18:17:55
     */
	public function Ress(){
		if($_POST){
			$data		 			= [];
			$data['iden'] 			= 100;
			$data['msg'] 			= '';
			$_POST['id'] 			= isset($_POST['id']) ? base64_decode($_POST['id']) : '';
			$ua						= M('user_address');
			try{
				switch ($_POST['action']){
					case 'list':
						$data['data'] 		= D('Common/UserAddress')->getAddressList($this->uId);
						break;  
					case 'delete':
						if(is_numeric($_POST['id'])){
							$data['data']  = $ua->where('address_id = '.$_POST['id'] .' and uid = '.$this->uId.' and agent_id = '.$this->agent_id)->delete();
						}else{
							throw new Exception('ID标识无效！');
						}
						break;
					case 'default':
						unset($_POST['action']);
						if($_POST['is_default']=='1'){
							$ua->where('uid = '.$this->uId)->setField('is_default','2');
						}
						
						if(is_numeric($_POST['id'])){																					//更新
							$data['data'] = $ua->where(array('address_id'=>$_POST['id']))->save($_POST);
						}else{																											//插入
							if(empty($_POST['name'])){
								throw new Exception('姓名不能为空！');
							}
							
							if(empty($_POST['phone'])){
								throw new Exception('联系不能为空！');
							}							
							
							if(!is_numeric($_POST['province_id'])){
								throw new Exception('请选择省份！');
							}
							
							if(!is_numeric($_POST['city_id'])){
								throw new Exception('请选择城市！');
							}
							if(!is_numeric($_POST['district_id'])){
								throw new Exception('请选择区县！');
							}							
							
							if(empty($_POST['address'])){
								throw new Exception('具体地址不能为空！');
							}						

							$_POST['uid'] =  $this->uId;
							$_POST['country_id'] =  '1';					 
							$_POST['agent_id'] = $this->agent_id;
							$data['data'] = $ua->add($_POST);
						}
						break;
					default:
				}
				
				if($data['data']){
					$data['msg'] 			= '成功';								
				}else{
					throw new Exception('失败！');
				}
			}catch (Exception $e) {
				$data['iden'] 			= 101;
				$data['msg'] 			= $e->getMessage();
			}
			$this->ajaxReturn($data);
		}else{
			$this -> display();
		}

	
	
	

	}	
	
	
	/**
     * 添加或者修改地址
     * zhy	find404@foxmail.com
	 * 2017年8月21日 18:17:55
     */
	public function Address(){
		$_GET['id'] 			= isset($_GET['id']) ? base64_decode($_GET['id']) : '';
		$R = M('region');
		$ua = M('user_address');
		$this->provinces = $R->where('region_type = 1')->getField('region_name',true);
		if($_GET['id']){
            $this->addOnce  = $ua->where(array('address_id'=>$_GET['id']))->find();
            $this->province = $R->where('region_id = '.$this->addOnce['province_id'])->getField('region_name');
            $this->city 	= $R->where('region_id = '.$this->addOnce['city_id'])	 ->getField('region_name');
			$this->district = $R->where('region_id = '.$this->addOnce['district_id'])->getField('region_name');		
			$this->position = array_search($this->province,$this->provinces);
		}
 
 
		$this -> display();
	}	
	
	/**
     * 添加或者修改地址
     * zhy	find404@foxmail.com
	 * 2017年8月21日 18:17:55
     */
	public function GetAddress(){
		if($_POST['parent_id']){
			$R = M('region');
			if(!is_numeric($_POST['parent_id'])){
				if($_POST['parent_id']=='北京' && $_POST['tips']== 2){
						$_POST['parent_id'] = $R->where(" parent_id = 2 and region_name = '".$_POST['parent_id']."'")->getField('region_id');
				}else{
						$_POST['parent_id'] = $R->where("region_name = '".$_POST['parent_id']."'")->getField('region_id');
				}
			}
			$data['parent_id'] = $_POST['parent_id'];
			$data['data'] = $R->where('parent_id = '.$_POST['parent_id'])->getField('region_name',true);
			$data['iden'] = 100;
			$this->ajaxReturn($data);
		}
	}	
	
	/**
     * 修改密码
     * zhy	find404@foxmail.com
	 * 2017年8月24日 15:21:01
     */
	public function ModifyPassword(){
		if($_POST['password']){
			$data = [];
			try{		
				$u = M('user');
				$password = $u->where(" uid = '".$this->uId."'")->getField('password');
				if(pwdHash($_POST['password']) != $password){
					throw new Exception('登录密码输入错误。');
				}
				if($_POST['password1'] != $_POST['password2']){
					throw new Exception('两次确认密码输入不一致');
				}
				if($_POST['password'] == $_POST['password1']){
					throw new Exception('修改密码不能和登录密码一样！');
				}
				$u->where('uid = '.$this->uId)->setField('password',pwdHash($_POST['password1']));
				$data['iden'] 			= 100;
				$data['msg'] 			= '密码修改成功！';
			}catch (Exception $e) {
				$data['iden'] 			= 101;
				$data['msg'] 			= $e->getMessage();
			}
				$this->ajaxReturn($data);
		}else{
			$this -> display();
		}
	}
	
	
	/**
     * 修改密码
     * zhy	find404@foxmail.com
	 * 2017年8月24日 15:21:01
     */
	public function Contact(){

	
		$this -> display();
	}
	
	
	/**
     * 我的订单支付成功页面
	 * zhy
	 * 2017年8月26日 15:14:50
     */
    public function payment(){
		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$agent_config = M("agent")->where("agent_id = ".$this->agent_id)->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1  and agent_id = '.$this->agent_id)->select();	//线下支付


		 $order_id   = I("get.order_id", 0, 'intval');

        $O          = M('order');
        $where      = 'o.agent_id = '.$this->agent_id.' and o.uid = '.$this->uId .' and ';        
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
     * 上传支付凭证
     * zhangchaohao
     * 2016年1月10日
    */
    public function paymentUpload(){
        //$this->checkTokenUid($this->uid,$this->token);
        //$order_id   = 903615;//I("get.goodid");
        $order_id   = I("get.order_id", 0, 'intval');

        $O          = M('order');
        $where      = 'o.agent_id = '.$this->agent_id.' and o.uid = '.$this->uId .' and ';        
        $info       = $O->alias('o')->where($where.' o.order_id = '.$order_id)->field($field)->join($join)->find();
		$OP = M('order_payment');
		$voucher_info = $OP->where('agent_id ='.$this->agent_id.' AND order_id = '.$order_id)->select();
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
        $this->payment_mode = M('payment_mode')->where('agent_id = '.$this->agent_id)->getField('mode_name',true);
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
		 
        $payment_mode = I('post.payment_mode');
 
        $PM  	= M('payment_mode');
        $payment_mode = $PM->where(" mode_name = '".$payment_mode."' and agent_id = ".$this->agent_id)->getField('mode_id');
        
        if(!$payment_mode){
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
        $where      = 'agent_id = '.$this->agent_id.' and uid = '.$this->uId .' and ' ;     
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
        $payment['uid']           = $this->uId;
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
        $payment['agent_id']       = $this->agent_id;
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
     * 在线支付-选择支付方式
	 * yc
	 * 2017年9月1日 15:40:47
     */
	public function onlineselect(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误','未知错误！');
		}
		$O = M('order');
		$where = 'agent_id = '.$this->agent_id.'  and uid = '.$this->uId;
		$field = 'order_id,order_sn,order_price';
		$this->orderInfo = $O->where($where.' and order_id = '.$order_id)->field($field)->find();
	
	
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
		
		//var_dump($this->orderInfo);
		$this->display();
	}
	
	/**
     * 在线支付-支付宝支付
	 * zhy
	 * 2017年9月1日 15:40:42
     */
	public function payonline(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误','未知错误！');
		}
		C('H5Pay',1);
		$alipay = new \Common\Model\Alipay();
		$this->html_text = $alipay->request($order_id);
		$this->display();
	}
		 
		
	
}