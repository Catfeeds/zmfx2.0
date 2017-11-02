<?php
/**
 * 用户相关操作类
 */
namespace Home\Controller;
use Think\Page;
class NewUserController extends NewHomeController{
	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			//查询出未读消息个数
			$this->count_message = M('user_msg')->where(array('agent_id'=>$this->agent_id,'uid'=>$this->uId,'is_show'=>0))->count();
	}
    public function _befoe_index(){
        $this->seo_title = "用户中心";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->active = "User";
    }
    public function _before_userInfo(){
        $this->seo_title = "用户中心";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->catShow = 0;
        $this->active = "User";
    }
    public function _before_userMessages(){
        $this->seo_title = "用户中心";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->active = "User";
    }
    public function _before_orderList(){
        $this->seo_title = "用户订单列表";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->catShow = 0;
        $this->active = "User";
    }
    public function _before_orderInfo(){
        $this->seo_title = "用户订单详情";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->catShow = 0;
        $this->active = "User";
    }

    public function _before_traderAdd(){
        $this->seo_title = "申请加入分销商";
        $this->seo_keyword = "申请加入分销商";
        $this->seo_content = "申请加入分销商";
        $this->active = "User";
    }

	// 用户注册协议
    public function registration($aid=0) {
        $this->article = M("article")->find($_GET['aid']);
        $this->display();
    }
    // 删除用户地址
    public function deleteUserAdd($address_id=0) {
        if (IS_POST) {
            $result = array('error'=>'yes','msg'=>L('L9075'));
            $address_id = I('post.address_id', '', 'htmlspecialchars');
            $M = M('user_address');
            if ($M->delete($address_id)) {
                $result = array('error'=>'no','msg'=>L('L298'), 'address_id'=>$address_id);
            } else {
                $result = array('error'=>'yes','msg'=>L('L299'));
            }
            $this->ajaxReturn($result);
        }
    }
    // 设置默认地址
    public function setDefAddress() {
        if (IS_POST) {
            $result = array('error'=>'yes','msg'=>L('L9075'));
            $whereDate['address_id'] = I('post.address_id', '', 'htmlspecialchars');
            $whereDate['uid'] = $this->uId;
            $noDef['address_id'] = array('NEQ', $whereDate['address_id']);
            $noDef['uid'] = array('EQ', $this->uId);
            $S = M('user_address');
            if (($S->where($whereDate)->save(array('is_default'=>1)) !== false)
                && ($S->where($noDef)->save(array('is_default'=>2)) !== false)) {
                $result = array('error'=>'no','msg'=>L('L9076'), 'address_id'=>$whereDate['address_id']);
            }
        }
        $this->sendRepsone($result);
    }
    // 收货地址的添加与列表显示
    public function shopAddress() {
        $S = M('user_address');
        if (IS_POST) {
            $sData['uid'] = $this->uId;
            $sData['title'] = I('post.title', '', 'htmlspecialchars');
            $sData['country_id'] = I('post.country_id', '', 'htmlspecialchars');
            $sData['province_id'] = I('post.province_id', '', 'htmlspecialchars');
            $sData['city_id'] = I('post.city_id', '', 'htmlspecialchars');
            $sData['district_id'] = I('post.district_id', '', 'htmlspecialchars');
            $sData['address'] = I('post.address', '', 'htmlspecialchars');
            $sData['code'] = I('post.code', '', 'htmlspecialchars');
            $sData['name'] = I('post.name', '', 'htmlspecialchars');
            $sData['phone'] = I('post.phone', '', 'htmlspecialchars');
            $sData['is_default'] = I('post.is_default', '', 'intval');
            $sData['agent_id'] = C('agent_id');
			$address_id = I('post.address_id','','intval');
			if($address_id){
				//修改收货地址
				$checkaddress = $S->where(array('address_id'=>$address_id,'agent_id'=>$sData['agent_id']))->find();
				if(!$checkaddress){
					$result = array('error'=>'yes','msg'=>'收货地址不存在');
					$this->ajaxReturn($result);
				}
				$action = $S->where(array('address_id'=>$address_id,'agent_id'=>$sData['agent_id']))->save($sData);
				if($action){
					if($sData['is_default'] == 1){
						$action = $S->where(array('address_id'=>array('neq',$address_id),'agent_id'=>$sData['agent_id']))->save(array('is_default'=>2));
					}
					$result = array('error'=>'no','msg'=>'修改成功');
				}else{
					$result = array('error'=>'yes','msg'=>'修改失败');
				}
			}else{
				//添加收货地址
				if ($sData['is_default'] == 1) { // 如果新添加的地址为默认的，则其它地址就要更新为非默认
					$S->where(array('uid'=>$this->uId))->save(array('is_default'=>2));
				}
				if ($S->data($sData)->add()) {
					$result = array('error'=>'no','msg'=>L('L9071'), 'backUrl'=>'shopAddress.html');
				}
			}

            $this->ajaxReturn($result);
        } else {
            $join = 'zm_region AS r ON r.region_id = s.country_id';
            $join2 = 'zm_region AS r2 ON r2.region_id = s.province_id';
            $join3 = 'zm_region AS r3 ON r3.region_id = s.city_id';
            $join4 = 'zm_region AS r4 ON r4.region_id = s.district_id';
            $field = 's.*, r.region_name AS country_name, r2.region_name AS province_name,
          r3.region_name AS city_name, r4.region_name AS district_name';
            $this->userAddList = $S->alias('s')->field($field)->where('uid='.$this->uId . ' and s.agent_id = '.C('agent_id'))->join($join)->join($join2)->join($join3)->join($join4)
                ->order('address_id desc')->select();
			/* 查出省级地区 */
			$region = M('region');
			$province = $region->where(array('region_type' => 1))->select(); 
			
			$this->province	= $province;
            $this->catShow 	= 0;
            $this->display();
        }
    }
    
	/**
	 * auth	：fangkai
	 * content：ajax获取用户收货地址详细信息
	 * time	：2016-7-5
	**/
	public function getaddress(){
		if(IS_AJAX){
			$address = M('user_address');
			$address_id = I('post.address_id','','intval');
			$agent_id = C('agent_id');
			$checkaddress = $address->where(array('address_id'=>$address_id,'agent_id'=>$agent_id))->find();
			if(!$checkaddress){
				$result['status'] = 0;
				$result['msg'] = '收货地址不存在';
				$this->ajaxReturn($result);
			}
			$join = 'zm_region AS r ON r.region_id = s.country_id';
			$join2 = 'zm_region AS r2 ON r2.region_id = s.province_id';
			$join3 = 'zm_region AS r3 ON r3.region_id = s.city_id';
			$join4 = 'zm_region AS r4 ON r4.region_id = s.district_id';
			$field = 's.*, r.region_name AS country_name, r2.region_name AS province_name,r3.region_name AS city_name, r4.region_name AS district_name';
			$addressinfo = $address->alias('s')->field($field)->where(array('uid'=>$this->uId,'agent_id'=>$agent_id,'address_id'=>$address_id))->join($join)->join($join2)->join($join3)->join($join4)->order('address_id desc')->find();
			if($addressinfo){
				$result['status'] = 100;
				$result['data'] = $addressinfo;
				$result['msg'] = '查询成功';
				
			}else{
				$result['status'] = 0;
				$result['msg'] = '查询失败';
			}
			$this->ajaxReturn($result);
		}
		
	}
	// 用户中心 首页
    public function index(){
        $this->active ='userinfo';
        $this->catShow = 0;
        $this->userInfo  = M("user")->where("uid=".$_SESSION['web']['uid'])->find();
        $this->trader    = M("trader")->where("tid=".$this->traderId.' and agent_id = '.C('agent_id'))->find();
        $latelyOrderList = M('order')->where(" agent_id='".C('agent_id')."' and uid=".$_SESSION['web']['uid'])->order('create_time DESC')->limit(7)->select();
        if($latelyOrderList){
            foreach($latelyOrderList as $key=>$val){
                $latelyOrderList[$key]['status_txt'] = getOrderStatus($val['order_status']);
            }
        }
        $orders = M('order')->where("agent_id='".C('agent_id')."' and uid=".$_SESSION['web']['uid'])->order('create_time DESC')->select();
        $noHandles = 0;
        if($orders){
            foreach($orders as $key=>$val){
                if($val['order_status == 0']){
                    $noHandles += 1;
                }
            }
        }
        $this->latelyOrderList = $latelyOrderList;
        $this->active = "index";
        $this->redirect('/Home/User/userInfo');
    }
    // 修改密码
    public function updatePwd() {
        if (IS_POST) {
            $result  = array('error'=>'yes', 'msg'=>L('L9017'));
            $map['old_password'] = I('post.old_password', 'htmlspecialchars');
            $map['new_password'] = I('post.new_password', 'htmlspecialchars');
            $map['confirm_password'] = I('post.confirm_password', 'htmlspecialchars');
            if ($map['new_password'] === $map['confirm_password']) {
                $U = M('user');
                $uInfo = $U->field('*')->where("uid=".$this->uId.' and agent_id = '.C('agent_id'))->find();
                if (!empty($uInfo) &&  ($uInfo['password'] == pwdHash($map['old_password']))) {
                    $pa['password'] = pwdHash(strval($map['new_password']));
                    if ($U->where("uid=".$this->uId)->save($pa) !== false) {
                        session('web', null);
                        $this->uId = null;
                        $result  = array('error'=>'no', 'msg'=>L('L9039'), 'backUrl'=>'Home/Public/login');
                    } else {
                        $result  = array('error'=>'yes', 'msg'=>L('L9042'));
                    }
                } else {
                    $result  = array('error'=>'yes', 'msg'=>L('L9041'));
                }
            } else {
                $result  = array('error'=>'yes', 'msg'=>L('L9040'));
            }
            $this->ajaxReturn($result);
        } else {
            $this->display('updatePassword');
        }
    }
    // 匹配输入的密码和之前的密码
    public function checkPwd(){
        $old_password = I("old_password");
        $password = M("user")->where("uid=".$_SESSION['web']['uid'])->getField("password");
        $result = array('success'=>false, 'msg'=>"您输入的原密码不对",'error'=>true);
        if($password == pwdHash($old_password)){
            $result = array('success'=>true, 'msg'=>"原密码正确",'error'=>false);
        }
        $this->ajaxReturn($result);
    }
    // 订单列表
    public function orderList(){
        $Order        = M('order');
        $order_sn     = I("order_sn");
        $dateFrom     = I("dateFrom");
        $dateTo       = I("dateTo");
        $certNo       = I("certificate_number");
        $order_status = I('order_status',-2);
        $dateFromTime = strtotime($dateFrom);
        $dateToTime   = strtotime($dateTo)+24*3600;
        $where        = " uid ='".$_SESSION['web']['uid']."'";
        if($order_sn){
            $where .= " AND order_sn = '".$order_sn."'";
        }
        if($dateFrom){
            $where .= " AND create_time >= ".$dateFromTime;
        }
        if($dateTo){
            $where .= " AND create_time <= ".$dateToTime;
        }

        if($order_status != -2 && $order_status != "" ){
            $where .= " AND order_status = ".$order_status;
        }

        if($certNo){
            $subQuery = M('order_goods')->field('order_id')->group('order_id')->where("agent_id='".C('agent_id')."' and certificate_no ='".$certNo."'")->order('order_id')->select(false);
            $where .= " AND order_id In (".$subQuery.")";
        }
        $where .= " AND agent_id='".C('agent_id')."' and order_status >=-1";
        $count = M('order')->where($where)->count();
        $n = 10;
        $page = I('page');
        $Page = New \Think\Page($count,$n);
        $dataList = $Order->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('create_time DESC')->select();
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
        }

        $this->orderStatus = orderStatus();
        $this->order_status = $order_status;
        $this->certificate_no = $certNo;
        //print_r($order_status);
        $this->page = $Page->show();
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->orderList  = $dataList;
        $this->order_sn = $order_sn;
        $this->active = 'orderList';
        $this->display();
    }

    /* 更新订单状态 */

    public function updateOrder(){
				//echo'----';exit();
        $order_id = I('order_id',0);
        $order_status = I('order_status',0);
        $href = I('href');
        $where = "agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND order_id=".$order_id;
        $data = array('status'=>0,'msg'=>'订单取消成功','href'=>$href);
        $order = M('order')->where($where)->find();
        if(!$order || $order_id == 0){
            $this->ajaxReturn($data);
        }else{
            switch($order_status){
                case -1:
                    $order['order_status'] = -2;
                    $data['msg'] = "订单删除成功";
                    break;
                case 0:
                    $order['order_status'] = -1;
                    $data['msg'] = "取消订单成功";
                    break;
                case 4:
                    $order['order_status'] =5;
                    $data['msg'] = "确认收货成功";
                    break;
            }
			
			if($order_status=='-1'){
				  M('order')->data($order)->delete();
			}elseif(M('order')->data($order)->save()){
				
				//取消订单，发送信息给商家
				$nowAgent_id = C('agent_id');
				$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
				if($sms_agent_info_arr["sms_push_reminder"]){
					$SMS = new \Common\Model\Sms();
					$SMS->SendSmsByType($SMS::USER_ORDER_CANCEL,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
				}

                $data['status'] = 1;
                if($order_status == 4){
                    //更新发货表状态
                    $data['confirm_type'] = 2;
                    $data['confirm_time'] = time();
                    M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id=$order_id AND uid='".$_SESSION['web']['uid']."'")->data($data)->save();
                }
            }

            $this->ajaxReturn($data);
        }
    }

    /* 删除订单 */
    public function deleteOrder(){
        $order_id = I('order_id',0);
        $order_status = I('order_status',0);
        $href = I('href');
        $where = "agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND order_id=".$order_id." AND order_status = -1 ";
        $data = array('status'=>0,'msg'=>'订单删除失败','href'=>$href);
        $order = M('order')->where($where)->find();
        if(!$order || $order_id == 0){
            $this->ajaxReturn($data);
        }else{
            if(M('order')->data($order)->delete()){
                $data['status'] = 1;
                $data['msg']  = "订单删除成功";
            }
            $this->ajaxReturn($data);
        }
    }

    public function getReceivables($order_id){
        $join = " LEFT JOIN zm_order_receivables as zor ON o.order_id = zor.order_id";
        $list = M('order')->alias('o')->JOIN($join)->where("o.agent_id='".C('agent_id')."' and o.order_id='$order_id'")->select();
        if($list){
            foreach($list as $key=>$val){
                switch($val['period_type']){
                    case 1:
                        $receivables['luozuan'][] = $val;
                        break;
                    case 2:
                        $receivables['sanhuo'][] = $val;
                        break;
                    case 3:
                    case 4:
                        $receivables['product'][] = $val;
                        break;
                    case 5:
                        $receivables['zmProduct'][] = $val;
                        break;
                }
            }
        }
        return $receivables;
    }

    public function getPeriods($order_id){
        $join = " LEFT JOIN zm_order_period as zor ON o.order_id = zor.order_id";
        $list = M('order')->alias('o')->JOIN($join)->where("o.agent_id='".C('agent_id')."' and o.order_id='$order_id'")->select();
        if($list){
            foreach($list as $key=>$val){
                switch($val['period_type']){
                    case 1:
                        $periods['luozuan'] = $val;
                        break;
                    case 2:
                        $periods['sanhuo'] = $val;
                        break;
                    case 3:
                    case 4:
                        $periods['product'] = $val;
                        break;
                    case 5:
                        $periods['zmProduct'] = $val;
                        break;
                }
            }
        }
        return $periods;
    }

    // 订单详情
    public function orderInfo(){
        $order_id = I('order_id',0);
        if($order_id == 0){
            $this->redirect('/Home/User/orderList');
        }
        $order = M('order')->alias('o')->where("o.agent_id='".C('agent_id')."' and o.uid='".$_SESSION['web']['uid']."' AND o.order_id ='".$order_id."'")->find();

        if(!$order){
            $this->redirect('/Home/User/orderList');
        }
        //获取配置好的支付信息
        $PC = M('payment_config');
        $where = 'mode_id = 1 and parent_type = '.$this->uType.' and parent_id = '.$this->traderId;
        $paymentInfo = $PC->where($where)->find();
        $paymentInfo = unserialize($paymentInfo['pay_attr']);
        $this->payment_config = $paymentInfo['pay_info'];
        //print_r($order);
        $this->periods = $this->getPeriods($order_id);
        //print_r($this->getPeriods($order_id));

        $this->receivables = $this->getReceivables($order_id);
        $dataList = M('order')->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.agent_id='".C('agent_id')."' and zm_order.order_id ='".$order_id."'")->select();

        //发货单列表
        $orderDeliverys = $this->getDelivery($order_id);
        //print_r($orderDeliverys);
        $this->orderDeliverys = $orderDeliverys;

        //退货单列表
        $returnOrders = $this->getReturnGoods($order_id);
        $this->returnOrders = $returnOrders;

        //补差价
        $this->orderReceivables = $this->getOrderReceivables($order_id);

        //退款记录
        $this->returnFunds = $this->getOrderReturnFunds($order_id);

        //订单支付信息
        $payList = M('order_payment')->where("agent_id='".C('agent_id')."' and order_id = '$order_id' AND uid='".$_SESSION['web']['uid']."' AND payment_status >1")->order("create_time DESC")->select();
        if($payList){
            $no_payment = $order['order_price_up'];
            foreach($payList as $key=>$val){
                $no_payment -= $val['payment_price'];
                if($no_payment <=0){
                    $no_payment = 0;
                }
                $payList[$key]['no_payment'] = formatRound($no_payment,2);
            }
        }
        $this->payList = $payList;
        $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
        $deputystoneM        = M('goods_associate_deputystone');

        if($dataList){
            foreach($dataList as $key=>$val){

                $dataList[$key]['goods'] =  unserialize($val['attribute']);
                $dataList[$key]['goods']['goods_type'] = $this->getGoodsTypeByTid($goods['tid']);

                $dataList[$key]['goods'] = unserialize($val['attribute']);
                $val['attribute'] = unserialize($val['attribute']);

                $dataList[$key]['goods']['luozuanInfo']['shape_name'] =  $luozuan_shape_array[$dataList[$key]['goods']['luozuanInfo']['shape_id']];



                if($dataList[$key]['goods']['attribute']){
                    $dataList[$key]['goods']['attributes'] = unserialize($dataList[$key]['goods']['attribute']);
                }

                $dataList[$key]['goods']['deputystone_name']  = "";
                $dataList[$key]['goods']['deputystone_price'] = "";
                if($dataList[$key]['goods']['deputystone']){
                    $deputystone = $deputystoneM->where('gad_id = '.$dataList[$key]['goods']['deputystone']['gad_id'])->select();
                    if($deputystone){
                        $dataList[$key]['goods']['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
                        $dataList[$key]['goods']['deputystone_price'] = $deputystone[0]['deputystone_price'];
                    }
                }

                if($val['goods_type'] == 1){ // 1表示证书货
                    if($order['order_status'] <= 0){
                        $dataList['diamond_total_amount'] += $val['goods_price'];
                        $total_amount  += $val['goods_price'];
                        $dataList[$key]['diamond_price'] = $val['goods_price'];
                        $dataList[$key]['diamond_number'] = intval($val['goods_number']);
                    }else if($order['order_status'] > 0){
                        $dataList['diamond_total_amount'] += $val['goods_price_up'];
                        $total_amount  += $val['goods_price_up'];
                        $dataList[$key]['diamond_price'] = $val['goods_price_up'];
                        $dataList[$key]['diamond_number'] = intval($val['goods_number_up']);
                    }
                    $diamond[] = $dataList[$key];
                }else if($val['goods_type'] == 2){ // 散货
                    //获取分类名称
                    $type_name = M("goods_sanhuo_type")->where("tid=".$val['attribute']['tid'])->getField("type_name");
                    if($type_name){
                        $dataList[$key]['goods']['type_name'] = $type_name;
                    }
                    if($order['order_status'] <= 0){  //表示订单未确认
                        $dataList[$key]['goods_price'] = formatRound($dataList[$key]['goods_price']*(1+C('sanhuo_advantage')/100),2);
                        $dataList[$key]['sanhuo_price'] = $val['goods_price'];
                        $dataList[$key]['sanhuo_number'] = $val['goods_number'];
                        $dataList['sanhuo_total_amount'] += formatRound($val['goods_price'],2);
                        $total_amount  += formatRound($val['goods_price'],2);
                    }else if($order['order_status'] > 0){
                        $dataList[$key]['sanhuo_price'] = $val['goods_price_up'];
                        $dataList[$key]['sanhuo_number'] = $val['goods_number_up'];
                        $dataList['sanhuo_total_amount'] += formatRound($val['goods_price_up'],2);
                        $total_amount  += formatRound($val['goods_price_up'],2);
                    }
                    $sanhuo[] = $dataList[$key];
                }else if($val['goods_type'] == 3 || $val['goods_type'] == 4){  // 成品
                    //获取材质名称
                    $material_name = M("goods_material")->where(array('material_id'=>$dataList[$key]['goods']['associateInfo']['material_id']))->getField('material_name');
                    if($material_name){
                        $dataList[$key]['goods']['associateInfo']['material_name'] = $material_name;
                    }
                    if($order['order_status'] <= 0){
                        $dataList['product_total_amount'] += $val['goods_price'];
                        $total_amount += formatRound($val['goods_price'],2);
                        $total_amount = formatRound($total_amount,2);
                        $dataList[$key]['product_price'] = $val['goods_price'];
                        $dataList[$key]['product_number'] += intval($val['goods_number']);
                    }else if($order['order_status'] >0){
                        $dataList['product_total_amount'] += $val['goods_price_up'];
                        $total_amount += $val['goods_price_up'];
                        $dataList[$key]['product_price'] = $val['goods_price_up'];
                        $dataList[$key]['product_number'] += intval($val['goods_number_up']);
                    }
                    if($val['goods_type'] == 3){

                        $attr = explode("^",$dataList[$key]['goods']['goods_sku']['attributes']);
                        if($attr){
                            $attrs = "";
                            foreach($attr as $k=>$v){
                                $temp = explode(":",$v);
                                if($temp){
                                    $inputType = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                                    if($inputType == 3){ //如果是手填值,直接显示该值
                                        $attrs .= $temp[1];
                                    }else{  //如果不是手填值,则查出对应的属性值
                                        $attrs .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                                    }
                                    $attrs .= " ";
                                }
                            }
                            $dataList[$key]['goods']['attrs'] = trim($attrs);
                        }
                    }


                    $product[] = $dataList[$key];
                }else if($val['goods_type'] == 5 || $val['goods_type'] == 6){ // 代销
                    if($order['order_status'] <= 0){
                        $dataList['zmProduct_total_amount'] += $val['goods_price'];
                        $total_amount += formatRound($val['goods_price'],2);
                        $total_amount = formatRound($total_amount,2);
                        $dataList[$key]['product_price'] = $val['goods_price'];
                        //print_r($val['goods_price']);
                        $dataList[$key]['product_number'] = intval($val['goods_number']);
                    }else if($order['order_status'] >0){
                        $dataList['zmProduct_total_amount'] += $val['goods_price_up'];
                        $total_amount += $val['goods_price_up'];
                        $dataList[$key]['product_price'] = $val['goods_price_up'];
                        $dataList[$key]['product_number'] = intval($val['goods_number_up']);
                    }
                    if($goods['specification_id']>0){
                        $dataList[$key]['specification']= $this->getGoodsSpecificationById($val['goods_type'],$goods['specification_id'],'');
                    }
                    $zmProduct[] = $dataList[$key];
                }
            }
            //print_r($zmProduct);
            //print_r($dataList);
            $dataList['sanhuo_total_amount'] = formatRound($dataList['sanhuo_total_amount'],2);
            $dataList['product_total_amount'] = formatRound($dataList['product_total_amount'],2);
            $dataList['zmProduct_total_amount'] = formatRound($dataList['zmProduct_total_amount'],2);
            $this->order = $order;
            //print_r($order);
            //print_r($dataList);
            $this->total_amount = formatRound($total_amount,2);
            $this->order_id = $order_id;
            $this->diamond = $diamond;
            $this->sanhuo = $sanhuo;
            $this->product = $product;
            $this->zmProduct = $zmProduct;
            $this->dataList = $dataList;
            $this->active = "orderList";
            //print_r($dataList);
            $this->display();
        }else{
            $this->redirect('/Home/User/orderList');
        }
    }

    public function getGoodsSpecificationById($goods_type,$speciation_id,$str){
        return array();
    }

    public function getOrderReturnFunds($orderId){
        return M('order_refund')->where("agent_id='".C('agent_id')."' and order_id=$orderId AND uid='".$_SESSION['web']['uid']."' AND refund_status = 2")->select();
    }

    public function getOrderReceivables($orderId){
        $orderReceivables = M('orderReceivables')->where("period_type=12 AND order_id=$orderId AND uid=".$_SESSION['web']['uid'])->select();
        return $orderReceivables;
    }

    public function getReturnGoods($orderId){
        $join = " LEFT JOIN zm_order_return_goods ZORG ON ZOR.return_id = ZORG.return_id LEFT JOIN zm_order_goods ZOG ON ZOG.og_id=ZORG.og_id";
        $where = " ZOR.agent_id='".C('agent_id')."' and ZOR.uid = '".$_SESSION['web']['uid']."' AND ZOR.order_id=$orderId AND ZOR.status = 3";
        $data = M('order_return')->field("ZOG.certificate_no,ZORG.delivery_id,ZOR.goods_price,ZOG.attribute,ZOR.create_time,ZORG.goods_number")->alias("ZOR")->JOIN($join)->where($where)->select();

        if($data){
            foreach($data as $key=>$val){
                $data[$key]['attrs'] = unserialize($val['attribute']);
            }
        }
        //print_r($data);
        return $data;
    }

    public function getOrdersByOrderId(){
        $order_id = I('order_id',0);
        $goods_type = I('goods_type',0);
        if($order=M('order')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND order_id='".$order_id."'")->find()){
            $dataList = M('order_goods')->where("agent_id='".C('agent_id')."' and order_id='".$order_id."' AND goods_type = '".$goods_type."'")->order('og_id ASC')->select();
            if($dataList){
                foreach($dataList as $key=>$val){
                    $dataList[$key]['goods'] = unserialize($val['attribute']);
                    if($val['goods_type'] == 2){ //散货

                        $dataList[$key]['type_name'] = getTypeNameByGoodsType($dataList[$key]['goods']['tid']);
                        $dataList[$key]['price'] += formatRound($val['goods_price'],2);
                        $data['sanhuo']['total_amount'] += formatRound($dataList[$key]['price'],2);
                        $dataList[$key]['price_update'] = formatRound($val['goods_price_up'],2);
                        $data['sanhuo']['total_amount_update'] += formatRound($dataList[$key]['price_update'],2);
                    }else if($val['goods_type'] == 1){ // 裸钻
                        $data['diamond']['total_amount'] += formatRound($val['goods_price'],2);
                        $data['diamond']['total_amount_update'] += formatRound($val['goods_price_up'],2);
                    }
                }
            }
            $data['data'] = $dataList;
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        $this->ajaxReturn($data);
    }

    public function getGoodsTypeByTid($tid){
        return M("goods_sanhuo_type")->where("tid='".$tid."'")->getField('type_name');
    }
    // 取用户登陆日志
    public function logininfo($p=1,$n=10) {
        $L = M('user_log');
        $count = $L->where('uid='.$this->uId.' and agent_id = '.C('agent_id'))->count();
        $Page = new Page($count,$n);
        $this->loginInfoArr = $L->field('*')->where('uid='.$this->uId.' and agent_id = '.C('agent_id'))->limit($Page->firstRow,$Page->listRows)->select();
        $this->page = $Page->show();
        $this->active = "userinfo";
        $this->act = 'logininfo';
        $this->catShow = 0;
        $this->display('userInfo');
    }

    // 更新用户资料
    public function updateUserInfo() {
        if (IS_POST) {
            $this->act = '';
			$birthday  = I('post.birthday','');
            $updateDate['sex'] = I('post.sex','','htmlspecialchars');
            $updateDate['email'] = I('post.email','','htmlspecialchars');
            $updateDate['phone'] = I('post.phone','','htmlspecialchars');
            $updateDate['qq'] = I('post.qq','','intval');
            $updateDate['realname'] = I('post.realname','','htmlspecialchars');
            $updateDate['job'] = I('post.job','','htmlspecialchars');
            $updateDate['company_name'] = I('post.company_name','','htmlspecialchars');
            $updateDate['legal'] = I('post.legal','','htmlspecialchars');
            $updateDate['company_address'] = I('post.company_address','','htmlspecialchars');
			$updateDate['birthday'] = strtotime($birthday);
            $U = M('user');
            if ($U->where("uid=".$this->uId.' and agent_id = '.C('agent_id'))->save($updateDate) !== false) {
                $map['uid'] = htmlspecialchars($this->uId);
                $map['satatus']  = 1;
                $map['agent_id'] = C('agent_id');
                $info = $U->where($map)->find();
				$info['birthday']		=	$birthday;
                $info['birthdayYear'] 	= 	date('Y',$updateDate['birthday']);
                $info['birthdayMonth'] 	= 	date('m',$updateDate['birthday']);
                $info['birthdayDay'] 	= 	date('d',$updateDate['birthday']);
                $this->saveUserInfoToSession($info); //
                $this->ajaxReturn(array('msg'=>L('L9044'),'success'=>'true','error'=>'false','url'=>'userInfo'));
            } else {
                $this->ajaxReturn(array('msg'=>L('L9043'),'success'=>'false','error'=>'true','url'=>'userInfo'));
            }
        } else {
            $this->display('index');
        }
    }
    // 用户资料
    public function userInfo() {
        $where = " uid = ".$_SESSION['web']['uid'];
        $userInfo = M("user")->where($where)->find();
        //print_r($userInfo);exit;
        $this->userInfo = $userInfo;
        $this->display();
    }

    // 添加新的消息
    public function addMessage() {
        if (IS_PSOT) {
            $result = array('error'=>'yes','msg'=>L('L9051'));
            $map['msg_type'] = 2;
            $map['parent_type'] = 1;
            $map['parent_id'] = 0;
            $map['uid'] = $this->uId;
            $map['title'] = I('post.msg_title', '', 'htmlspecialchars');
            $map['content'] = I('post.msg_content', '', 'htmlspecialchars');
            $map['create_time'] = time();
            $map['agent_id'] = C('agent_id');
            $M = M('user_msg');
            if ($M->data($map)->add()) {
                $result = array('error'=>'no','msg'=>L('L9052'), 'backUrl'=>'sendMessages.html');
            }
            $this->sendRepsone($result);
        } else {
            $this->display('index');
        }
    }
    // 我的留言
    public function myMessages() {
        $this->act = 'myMessages';
        $this->active = "userinfo";
        $M = M('user_msg');
        $this->display('index');
    }
    // 已发送的消息
    public function sendMessages($p=1,$n=8) {
        $this->act = 'sendmessages';
        $M = M('user_msg');
        $count = $M->where('msg_type = 2 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->count();
        $Page = new Page($count,$n);
        $this->sendMsgList = $M->where('msg_type = 2 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->limit($Page->firstRow,$Page->listRows)->select();
        $this->page = $Page->show();
        $this->display('index');
    }
    // 消息中心
    public function userMessages($p=1,$n=10) {
        $this->act = 'userMessages';
        $this->active = 'userMessages';
        $M = M('user_msg');
        $count = $M->where('msg_type = 1 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->count();
        $Page = new Page($count,$n);
        $this->messagesList = $M->where('msg_type = 1 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))
            ->order('is_show asc,msg_id desc')->limit($Page->firstRow,$Page->listRows)->select();
        $this->page = $Page->show();
        $this->catShow = 0;
        $this->display();
    }
	
	/**
	 * auth	：fangkai
	 * content：批量，单独删除消息
	 * time	：2016-7-19
	**/
    public function deleteMyMsg() {
		if(IS_AJAX){
			$userMsg = M('user_msg');
			$thisId  = I('post.thisid','');
			if(empty($thisId)){
				$result['info']   = '请选择消息再进行操作';
				$result['status'] = 0;
				$this->ajaxReturn($result);
			}
			$where['agent_id']	= C('agent_id');
			$where['msg_id']	= array('in',$thisId);
			$check = $userMsg->where($where)->select();
			if(empty($check)){
				$result['info']   = '消息不存';
				$result['status'] = 0;
				$this->ajaxReturn($result);
			}
			$action = $userMsg->where($where)->delete();
			if($action){
				$result['info']   = L('L298');
				$result['status'] = 100;
			}else{
				$result['info']   = L('L299');
				$result['status'] = 0;
			}
			$this->ajaxReturn($result);
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：设置消息为已读
	 * time	：2016-7-19
	**/
    public function isShow() {
		if(IS_AJAX){
			$userMsg = M('user_msg');
			$thisId  = I('post.thisid','');
			if(empty($thisId)){
				$result['info']   = '请选择消息再进行操作';
				$result['status'] = 0;
				$this->ajaxReturn($result);
			}
			$where['agent_id']	= C('agent_id');
			$where['is_show']   = 0;
			$where['msg_id']	= array('in',$thisId);
			$check = $userMsg->where($where)->select();
			if(empty($check)){
				$result['info']   = '消息不存在或已读';
				$result['status'] = 0;
				$this->ajaxReturn($result);
			}
			$save['is_show'] = 1;
			$action = $userMsg->where($where)->save($save);
			if($action){
				$result['info']   = L('L9076');
				$result['status'] = 100;
			}else{
				$result['info']   = L('L9075');
				$result['status'] = 0;
			}
			$this->ajaxReturn($result);
		}
    }
    // 用户邮箱检查
    public function regcheckemail() {
        $checkRegUser = M('user');
        $map['email'] = htmlspecialchars($_POST['email']);
        $map['satatus']  = 1;
        $map['agent_id'] = C('agent_id');
        $info = $checkRegUser->field('*')->where($map)->find();
        if($info) {
            $result = array('error'=>'yes','msg'=>L('L824'));
        } else {
            $result = array('error'=>'no','msg'=>L('L9001'));
        }
        $this->sendRepsone($result);
    }
    // 用户名检查
    public function regcheckusername() {
        $checkRegUser    = M('user');
        $map['username'] = htmlspecialchars($_POST['reg_username']);
        $map['agent_id'] = C('agent_id');
        $info = $checkRegUser->field('*')->where($map)->find();
        if($info) {
            $result = array('error'=>'yes','msg'=>L('L823'));
        } else {
            $result = array('error'=>'no','msg'=>L('L9001'));
        }
        $this->ajaxReturn($result);
    }
    // 保存用户信息到session
    public function saveUserInfoToSession($info) {
        $_SESSION['web']['uid'] = $info['uid'];
        $_SESSION['web']['username'] = $info['username'];
        $_SESSION['web']['realname'] = $info['realname'];
        $_SESSION['web']['sex'] = $info['sex'];
        $_SESSION['web']['phone'] = $info['phone'];
        $_SESSION['web']['birthday'] = $info['birthday'];
        $_SESSION['web']['birthdayYear'] = $info['birthdayYear'];
        $_SESSION['web']['birthdayMonth'] = $info['birthdayMonth'];
        $_SESSION['web']['birthdayDay'] = $info['birthdayDay'];
        $_SESSION['web']['email'] = $info['email'];
        $_SESSION['web']['qq'] = $info['qq'];
        $_SESSION['web']['job'] = $info['job'];
        $_SESSION['web']['legal'] = $info['legal'];
        $_SESSION['web']['company_name'] = $info['company_name'];
        $_SESSION['web']['company_address'] = $info['company_address'];
        $_SESSION['web']['company_lincense'] = $info['company_lincense'];
        $_SESSION['web']['rank_id'] = $info['rank_id'];
        $_SESSION['web']['reg_time'] = $info['reg_time'];
        $_SESSION['web']['last_logintime'] = $info['last_logintime'];
        $_SESSION['web']['last_ip'] = $info['last_ip'];
        $_SESSION['web']['is_validate'] = $info['is_validate'];
        $_SESSION['web']['note'] = $info['note'];
        $_SESSION['web']['parent_type'] = $info['parent_type'];
        $_SESSION['web']['parent_id'] = $info['parent_id'];
        $_SESSION['web']['shouXinEdu'] = $info['shouXinEdu'];
        $_SESSION['web']['diamondDiscount'] = $info['diamondDiscount'];
        $_SESSION['web']['sanhuoDiscount'] = $info['sanhuoDiscount'];
        $_SESSION['web']['interestRate'] = $info['interestRate'];
    }

    // 重置用户密码
    public function resetPwd() {
        if (IS_POST) {
            $result  = array('error'=>'yes', 'msg'=>L('L9017'));
            $email = I('post.getpassword_email', '', 'htmlspecialchars');
            if (!empty($email)) {
                if ($_SESSION['web']['getpassword_code'] == I('post.getpassword_code', '', 'htmlspecialchars')) {
                    $U = M('user');
                    $pa['password'] = pwdHash(strval(I('post.get_password','','htmlspecialchars')));
                    if ($U->where("email='".$email."' and agent_id = ".C('agent_id'))->save($pa) !== false) {
                        $result  = array('error'=>'no', 'msg'=>L('L9039'), 'backUrl'=>'login.html');
                    } else {
                        $result['msg'] = L('L9043');
                    }
                } else {
                    $result['msg'] = L('L9047');
                }
            } else {
                $result['msg'] = L('L9048');
            }
            $this->sendRepsone($result);
        } else {
            $this->display('login');
        }
    }
    // 发送邮件验证码
    public function sendEmailCode() {
        $email = I('post.email', '', 'htmlspecialchars');
        if (empty($email)) {
            $result = array('msg'=>L('L820'),'error'=>'yes');
        } else {
            $fortgotU = M('user');
            $map['email'] = $email;
            $map['satatus'] = 1;
            $map['agent_id'] = C('agent_id');
            $fortInfo = $fortgotU->field('*')->where($map)->find();
            if ($fortInfo) {
                $_SESSION['web']['getpassword_code'] = rand(100000,999999);
                $title = L('L9018');  //标题
                $content = L('L9046').':'.$_SESSION['web']['getpassword_code'];  //邮件内容
                if ($this->sendMail($email,$title,$content)) {
                    $result = array('msg'=>L('L825').$email,'error'=>'no','backUrl'=>'login.html');
                } else {
                    $result = array('msg'=>L('L9015').$email,'error'=>'yes','backUrl'=>'login.html');
                }
            } else {
                $result = array('msg'=>L('L820'),'error'=>'yes');
            }
        }
        $this->sendRepsone($result);
    }
    // 取指定用户的未读消息
    public function getIsShowMsg() {
        $where = array('uid'=>$this->uId, 'is_show'=>0, 'msg_type'=>1, 'agent_id'=>C('agent_id'));
        $total = M('user_msg')->where($where)->count();
        if (!empty($total)) {
            $result = array('error'=>'no', 'total'=>L('L9091').$total.L('L9092'));
        } else {
            $result = array('error'=>'yes', 'total'=>false);
        }
        $this->sendRepsone($result);
    }
    // 输出到前台
    protected function sendRepsone($result) {
        echo json_encode($result);
    }

    // 加入分销商
    public function traderAdd(){
        if(IS_POST){
            $_POST['create_time'] = time();
            $_POST['trader_rank'] = 1;//默认等级是白银会员
            $_POST['status'] = 0;
            $_POST['trader_rank'] = 0;
            $_POST['template_id'] = 0;
            $_POST['parent_id'] = $this->traderId;
            $_POST['create_user_id'] = $_SESSION['web']['uid'];
            $_POST['agent_id'] = C('agent_id');
            //数据验证
            $rules = array(
                array('trader_name','require','必须填写分销商名称！'),
                array('contacts','require','必须填写联系人！'),
                array('phone','require','必须填写联系电话！'),
                array('province_id','require','必须选择省份！'),
                array('city_id','require','必须选择城市！'),
                array('name','require','必须填写申请内容！'),
            );
            $T = M('trader');
            $res1 = $T->validate($rules)->create($_POST);
            //执行数据操作
            if($res1){
                $res2 = $T->add();
                if($res2){
                    $info = '申请成功，请耐心等待我们审核！';
                    $data = array('status'=>100,'msg'=>$info);
                }else{
                    $info = '申请分销商失败，'.$T->getError();
                    $data = array('status'=>0,'msg'=>$info);
                }
            }else{
                $info = '申请分销商失败，'.$T->getError();
                $data = array('status'=>0,'msg'=>$info);
            }
            //返回信息
            if(IS_AJAX) $this->ajaxReturn($data);
            else $res2?$this->success($info):$this->error($info);
        }else{
            //省份列表
            $R = M('region');
            $this->provinceList = $R->where('parent_id = 1')->select();
            $this->active = "traderAdd";
            $this->display();
        }
    }

    Public function uploadPic(){
        $upload = new \Think\Upload();
        $upload->autoSub = true;
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Public/Uploads/Config/'.$this->traderId."/"; // 设置附件上传根目录
        $upload->savePath  =     'trader/'; // 设置附件上传（子）目录
        // 上传文件
        $info   =   $upload->upload();
        $file_newname = $info['userfile']['savename'];
        $MAX_SIZE = 20000000;
        if($info['userfile']['type'] !='image/jpeg' && $info['userfile']['type'] !='image/jpg' && $info['userfile']['type'] !='image/pjpeg' && $info['userfile']['type'] != 'image/png' && $info['userfile']['type'] != 'image/x-png'){
            echo "2";exit;
        }
        if($info['userfile']['size']>$MAX_SIZE)
            echo "上传的文件大小超过了规定大小";

        if($info['userfile']['size'] == 0)
            echo "请选择上传的文件";
        switch($info['userfile']['error'])
        {
            case 0:
                echo '/Public/Uploads/Config/'.$this->traderId."/".$upload->savePath.date('Y-m-d',time())."/".$file_newname;
                break;
            case 1:
                echo "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                break;
            case 2:
                echo "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                break;
            case 3:
                echo "文件只有部分被上传";
                break;
            case 4:
                echo "没有文件被上传";
                break;
        }
    }

    // 跳转登录分销商后台
    public function traderAdmin(){

    }

    public function traderList(){
        $traderList = M("trader")->where("1=1 AND create_user_id=".$_SESSION['web']['uid'].' and agent_id = '.C('agent_id'))->order('create_time DESC')->select();
        if($traderList){
            foreach($traderList as $key=>$val){
                if($val['status'] > 0){
                    $traderList[$key]['status_txt'] = "审核通过";
                }else{
                    $traderList[$key]['status_txt'] = "未审核或审核未通过";
                }
            }
        }
        $this->active = "traderList";
        $this->traderList = $traderList;
        $this->catShow = 0;
        $this->display();
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

    // 获取多条发货信息
    public function getDelivery($orderId){

        //先获取该订单的退货信息

        $join = " LEFT JOIN zm_order_return ZOR ON ZOR.order_id=ORG.order_id";
        $return_goods = M('order_return_goods')->alias("ORG")->join($join)->where("ORG.agent_id='".C('agent_id')."' and ORG.order_id=$orderId AND ZOR.status = 3")->select();
        /*
        if($return_goods){
            $return_goods_id = "";
            foreach($return_goods as $key=>$val){
                $temp[] = $val['og_id'];
                $order_deliverys = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND uid=".$_SESSION['web']['uid']." AND goods_price !=".$val['goods_price'])->select();
            }
            $return_sql = " AND ( ODG.og_id=".$val['og_id']." AND ODG.goods_number!='".$val['goods_number']."')";
            $return_goods_id = implode(",",$temp);
        }else{

        }
        */
        //print_r($order_deliverys);
        $order_deliverys = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND uid=".$_SESSION['web']['uid'])->select();
        if($order_deliverys){
            foreach($order_deliverys as $key=>$val){
                $count = M('order_return_goods')->where("agent_id='".C('agent_id')."' and delivery_id='".$val['delivery_id']."' AND order_id=".$orderId)->sum("goods_number");
                $total = M('order_delivery_goods')->where("agent_id='".C('agent_id')."' and delivery_id='".$val['delivery_id']."' AND order_id=".$orderId)->sum("goods_number");

                if($total == $count){
                    $order_deliverys[$key]['confirm'] = 1;
                }
                if($val['confirm_type'] == 1){
                    $order_deliverys[$key]['confirm_type_txt'] = "后台确认";
                }else if($val['confirm_type'] == 2){
                    $order_deliverys[$key]['confirm_type_txt'] = "客户确认";
                }else{
                    $order_deliverys[$key]['confirm_type_txt'] = "未确认";
                }

                $join = " LEFT JOIN zm_order_goods as OG ON OG.og_id = ODG.og_id ";
                $where = "ODG.agent_id='".C('agent_id')."' and ODG.delivery_id='".$val['delivery_id']."' AND ODG.order_id='$orderId'";
                if($return_goods_id != ''){
                    $where .= $return_sql;
                }

                $orderDeliveryGoods = M('order_delivery_goods')->alias("ODG")->join($join)->field("ODG.goods_number,ODG.goods_price,OG.attribute,OG.goods_type")->where($where)->select();
                if($orderDeliveryGoods){
                    foreach($orderDeliveryGoods as $k=>$v){

                        $orderDeliveryGoods[$k]['attributes'] = unserialize($v['attribute']);
                        $attributes = $orderDeliveryGoods[$k]['attributes'];
                        if($v['goods_type'] == 1){
                            $orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['certificate_type']." ".$orderDeliveryGoods[$k]['attributes']['certificate_number'];
                            $orderDeliveryGoods[$k]['goods_number'] = (int)$orderDeliveryGoods[$k]['goods_number'];
                        }else if($v['goods_type'] == 2){
                            $orderDeliveryGoods[$k]['goods_number'] = (int)$orderDeliveryGoods[$k]['goods_number'];
                            $orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_sn']." 颜色：".$attributes['color']." 净度：".$attributes['clarity'];
                        }else if($v['goods_type'] == 3){
                            $orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['goods_sn'];
                        }else if($v['goods_type'] == 4){
                            $orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['goods_sn']." ".$attributes['associateInfo']['material_name'];
                        }elseif($v['goods_type'] == 5){
                            $orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['goods_sn']." ".$attributes['associateInfo']['material_name'];
                        }
                    }
                }
                $order_deliverys[$key]['deliveryGoods'] = $orderDeliveryGoods;
            }
        }
        //print_r($order_deliverys);
        return $order_deliverys;
    }

    public function confirmReceiptGoods(){
        $orderId = $_POST['order_id'];
        $deliveryId = $_POST['deliveryId'];
        $order_delivery = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND delivery_id='$deliveryId'")->find();
        $order = M('order')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND uid='".$_SESSION['web']['uid']."'")->find();
	    $data = array('status'=>0,'msg'=>"确认收货失败");
        if($order_delivery){
            $order_delivery_save['confirm_type'] = 2;
            $order_delivery_save['confirm_time'] = time();
            if(M('order_delivery')->where('order_id= '.$orderId)->save($order_delivery_save)){
                $temp = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND confirm_type>0")->select();
                $orderDeliveryPrice = 0;
                foreach($temp as $key=>$val){
                    $orderDeliveryPrice += $val['goods_price'];
                }
/*
				$orderPrice = M('order')->where("agent_id='".C('agent_id')."' and order_id='$orderId'")->getField("order_price_up");
				if($orderPrice>0){
					$orderPrice=$orderPrice;
				}else{
					$orderPrice = M('order')->where("agent_id='".C('agent_id')."' and order_id='$orderId'")->getField("order_price");
				}
				 if($orderDeliveryPrice == $orderPrice){
*/ 
                if($orderDeliveryPrice>0){
                    $order['order_status'] = 5; // 更新订单表的确认收货。
                    M('order')->data($order)->save();
                }
				
				//确认收货，发送信息给商家         2017-08-24		  zengmingming
				$nowAgent_id = C('agent_id');
				$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
				if($sms_agent_info_arr["sms_push_reminder"]){
					$SMS = new \Common\Model\Sms();
					$SMS->SendSmsByType($SMS::USER_CONFIRM_RECEIPT,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
				}
				
                $data['status'] = 1;
                $data['msg'] = "确认成功";
            }
        }
        $this->ajaxReturn($data);
    }

}                    
