<?php
namespace Mobile\Controller;
class OrderController extends MobileController{
    //订单列表
    //http://api.btbzm.com/mobile/Order/getlist?agent_id=7&uid=704053&order_status=-1
    public function getlist(){    	
    	$uid 			= I('get.uid', 			 0, 'intval');  	
    	$start  		= I('get.start', 		 0, 'intval');   	
    	$per			= I('get.per', 			20, 'intval');
        
    	if(empty(I('get.order_status'))){
    		$order_status = '';
    	}else{  	
    		$order_status 	= I('get.order_status', -2, 'intval');  //
    	}
    	
    	$order = D('Order');    	
    	$data = $order->getOrderList($uid, $start, $per, $order_status);
    	$this->r(100, '', $data);
    }

    //确定收货
    public function confirm(){
       
        $order_id       = I('get.order_id',      0, 'intval'); 
        $uid            = I('get.uid',           0, 'intval');
        $agent_id       = I('get.agent_id',      0, 'intval');
        $delivery_id    = I('get.delivery_id',    0, 'intval');

        $map['order_id']    = $order_id;
        $map['uid']         = $uid;
        $map['agent_id']    = $agent_id;
        $map['delivery_id']  = $delivery_id;  

        $order_delivery = M('order_delivery')->where($map)->find();
        $order = M('order')->where("agent_id='".$agent_id."' and order_id='$order_id' AND uid='".$uid."'")->find();

        if($order_delivery){
            $order_delivery['confirm_type'] = 2;
            $order_delivery['confirm_time'] = time();
           
            if(M('order_delivery')->data($order_delivery)->save()){
                $temp = M('order_delivery')->where("agent_id='".$agent_id."' and order_id='$order_id' AND confirm_type>0")->select();
                       
                $orderDeliveryPrice = 0;
                foreach($temp as $key=>$val){
                    $orderDeliveryPrice += $val['goods_price'];
                }
                $orderPrice = M('order')->where("agent_id='".$agent_id."' and order_id='$order_id'")->getField("order_price");
                
                if($orderDeliveryPrice == $orderPrice){
                    $order['order_status'] = 5; // 更新订单表的确认收货。
                    M('order')->data($order)->save();
                }
                $this->r(100, '确认收货成功');
            }
        }else{
             $this->r(101, '确认收货失败');
        }     
    }

    //取消订单
    public function cancel(){
        $O = M('order');

        $order_id       = I('get.order_id',      0, 'intval'); 
        $uid            = I('get.uid',           0, 'intval');
        $agent_id       = I('get.agent_id',      0, 'intval');

        $map['order_id']    = $order_id;
        $map['uid']         = $uid;
        $map['agent_id']    = $agent_id;        

        $info = $O->find($order_id);
        //验证状态
        if($info['order_status'] == 0){
            $data['order_status'] = '-1';
            $data['update_time'] = time();
            if($O->where($map)->save($data)){
                $this->r(100, '订单取消成功');
            }else{
                
                $this->r(101, '取消出错');
            }
        }else{
            $this->r(102, '订单确认后不可以取消');            
        }
    }

    //删除已经取消的订单
    public function delete(){
        $order_id       = I('get.order_id',      0, 'intval'); 
        $uid            = I('get.uid',           0, 'intval');
        $agent_id       = I('get.agent_id',      0, 'intval');

        $map['order_id']    = $order_id;
        $map['uid']         = $uid;
        $map['agent_id']    = $agent_id;        

        $order_data = M('order')->where($map)->find();

        if($order_data){
            if($order_data['order_status'] != -1){
                $this->r(101, '订单不是已取消状态!');
            }
        }else{
            $this->r(102, '你没有该订单!');
        }

        $order_del = M('order')->where($map)->delete();
        if($order_del){
            $this->r(100, '订单删除成功!');
        }else{
            $this->r(103, '订单删除失败!');
        }

    }


    //订单信息
    //info?agent_id=31&uid=704058&order_id=700869

    public function Info(){
    	$order_id       = I('get.order_id',      0, 'intval'); 
        $uid            = I('get.uid',           0, 'intval');
        $agent_id       = I('get.agent_id',      0, 'intval');
        $map['order_id']    = $order_id;        
        $map['agent_id']    = $agent_id;
        $map['uid']         = $uid;        
        $order = D('Order');

        $data['order_info'] = $order->getOrderInfo($order_id, $uid, $agent_id); //商品信息
        if(empty($data['order_info'])){
             $this->r(101, '没有该订单!');         
        }       
        //Array ( [order_id] => 700484 [order_sn] => 2016031416411746 [tid] => 0 [uid] => 704053 [address_id] => 700474 [order_status] => 0 [delivery_mode] => 0 [order_price_up] => 0.00 [order_price] => 8490.63 [note] => [parent_id] => 0 [parent_type] => 1 [create_time] => 1457944877 [update_time] => 0 [mark] => [dollar_huilv] => 6.50 [agent_id] => 7 [realname] => 黄力宏 [username] => test320 [trader_name] => [parent_admin_name] => [parent_trader_name] => )     

        $data['order_addressInfo'] = $order->getAddressInfo( $data['order_info']['address_id']);//收货地址
        //print_r($order_addressInfo);
        //Array([address] => 天河低山    [name] => 王柏银    [phone] => 13452247361    [address_id] => 700483    [country] => 中国    [province] => 广东    [city] => 广州    [district] => 天河区    [addressInfo] => 中国广东广州天河区天河低山)

        $data['order_goodsList'] = $order->getOrderGoodsList($order_id, $uid, $agent_id);

        $this->r(100, '', $data);  	    	
    }


    //订单提交
    public function add(){
        $address_id     = I('get.address_id',    0, 'intval'); 
        $uid            = I('get.uid',           0, 'intval');
        $agent_id       = I('get.agent_id',      0, 'intval');     

        $map['agent_id']    = $agent_id;
        $map['uid']         = $uid;        

        $dataList = M('cart')->where($map)->order("goods_type ASC")->select();
        if(empty($dataList)){
             $this->r(101, '购物车中没有商品！');
        }
        if(empty($address_id)){
            $this->r(102, '没有收货地址！');
        }
 
        $amount = 0;
        $order_goods = array(); 
        foreach($dataList as $key=>$val){ 
            $dataList[$key]['data'] = $goods = unserialize($val['goods_attr']);
            $order_goods[$key]['goods_price_a_up']  = 0; 
            $order_goods[$key]['goods_price_b']     = 0;
            $order_goods[$key]['goods_price_b_up']  = 0;
            $order_goods[$key]['goods_price_c']     = 0;
            $order_goods[$key]['goods_price_c_up']  = 0; 
            $order_goods[$key]['attribute']         = $val['goods_attr'];
            $order_goods[$key]['goods_type']        = $val['goods_type'];
            $order_goods[$key]['parent_type']       = 1;
            $order_goods[$key]['parent_id']         = $this->traderId;
            if($val['goods_type'] ==1 ){      // 1为裸钻
                $order_goods[$key]['goods_number']      = 1;
                $order_goods[$key]['certificate_no']    = $goods['certificate_number'];
                $userdiscount                           = getUserDiscount($uid);
                $dataList[$key]['data']['dia_discount'] = get_luozuan_sale_jiadian($_SESSION['m']['uid'], $dataList[$key]['data']['weight'], $dataList['data'][$key]['dia_discount']);
                $dataList[$key]['data']['price']        = formatRound( $dataList[$key]['data']['dia_global_price'] * C('dollar_huilv') * $dataList[$key]['data']['dia_discount'] * $dataList[$key]['data']['weight']/100 , 2);//每克拉人民币价格
                


                $order_goods[$key]['goods_price'] = formatRound($dataList[$key]['data']['price'],2);
                $order_goods[$key]['goods_id']    = $dataList[$key]['data']['gid'];
                $amount += formatRound($dataList[$key]['data']['price'],2);   
            }else if($val['goods_type'] ==2){  //散货
                $order_goods[$key]['goods_number']   = $val['goods_number'];
                $order_goods[$key]['certificate_no'] = $val['goods_sn'];                                                       
                $order_goods[$key]['goods_price']    = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2); 
                $order_goods[$key]['goods_id']       = $dataList[$key]['data']['goods_id']; 
                $amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);              
            }else if($val['goods_type'] == 3){   // 3成品
                $order_goods[$key]['goods_number']   = $val['goods_number'];
                $order_goods[$key]['certificate_no'] = $val['goods_sn'];
                $order_goods[$key]['data']['goods_attrs']['goods_name'] = addslashes($order_goods[$key]['data']['goods_attrs']['goods_name']);
                if($val['sku_sn'] != ''){
                    $dataList[$key]['data']['goods_price'] = M("goods_sku")->where("goods_id=".$val['goods_id']." AND sku_sn='".$dataList[$key]['data']['sku_sn']."'")->getField('goods_price');


                }
                $order_goods[$key]['goods_price'] = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2); 
                $order_goods[$key]['goods_id']    = $dataList[$key]['data']['goods_id']; 
                $amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);  
            }else if($val['goods_type'] ==4){  //戒托  
                $order_goods[$key]['goods_number']   = $val['goods_number'];
                $order_goods[$key]['certificate_no'] = $goods['goods_sn']; 
                $order_goods[$key]['goods_price']    = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2); 
                $order_goods[$key]['goods_id']       = $dataList[$key]['data']['goods_id']; 
                $amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);              
            }
            $order_goods[$key]['goods_number_up'] = 0; 
            $order_goods[$key]['update_time']     = time(); 
            $order_goods[$key]['agent_id']        = C('agent_id');  
        }                       
        $data['note_a']         = I('orderInfo');
        $data['order_goods']    = $order_goods; 
        $data['order_sn']       = date("YmdHis").rand(10,99);
        $data['uid']            = $uid;
        $data['order_status']   = 0;
        $data['order_confirm']  = 0;
        $data['order_payment']  = 0;
        $data['delivery_mode']  = 0;

        $parent_id = M('user')->where("uid='".$uid."' and agent_id = ".$agent_id)->getField('parent_id');

        $data['parent_id']      = $parent_id; // 对应业务员ID
        $data['address_id']     = $address_id;
        $data['order_price']    = $amount;
        $data['create_time']    = time();
        $data['dollar_huilv']   = C("dollar_huilv");  
        $data['parent_type']    = $this->uType; //订单所属分销商
        $data['agent_id']       = C('agent_id');    
        $Goods = A('Home/Goods');
        $data_return = $Goods->createOrder($data, $uid);
        if($data_return){          
            $this->r(100, '订单提交成功！', $data_return);
        }else{           
            $this->r(103, '订单提交失败！');
        }                     
        
    }
    
}
