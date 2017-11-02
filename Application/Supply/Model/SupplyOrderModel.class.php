<?php
/*
*@note    订单MODEL 
*@authoer 张超豪
*@addtime 2016-05-03
*/
namespace Supply\Model;
use Think\Model;
class SupplyOrderModel extends Model{
    //protected $patchValidate = true;

    /*
    *@note    订单列表 
    *@authoer 张超豪
    *@addtime 2016-05-03
    */
	public function getOrderList($where,$sort='order_id desc',$pageid=1,$pagesize=10,$agent_id=0,$tips=0){
        $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));                
        $totalnum  = $this->where($where)->count();
		if($tips==2){        return $totalnum; }
        $limit     = (($pageid-1)*$pagesize).','.$pagesize;
        $orderList = $this->where($where)->order($sort)->limit($limit)->select();       
 
        foreach($orderList as $value){
            $value['order_status_str'] = supplyGetOrderStatus($value['order_status']);
            $value['create_time_str']  = date('Y-m-d H:i', $value['create_time']);
            // if($value['fahuo_time']){
            //     $value['fahuo_time_str']   = date('Y-m-d', $value['fahuo_time']);
            // }
            $list[] = $value;
        }
   
        $data['total']     = $totalnum;
        $data['page_size'] = $pagesize;
        $data['page_id']   = $pageid;
        $data['list']      = $list;
        return $data;
    }

    /*
    *@note    订单详情 
    *@authoer 张超豪
    *@addtime 2016-05-06
    */
    public function getOrderInfo($order_id = 0 , $order_sn = ''){
        //查询数据      
        $where = '1=1';
        if($order_sn) $where .= ' and order_sn = '.$order_sn;
        if($order_id) $where .= ' and order_id = '.$order_id;
        
        $info = $this->where($where)->find();
        if($info){       
            $info['create_time_str']  = date('Y-m-d H:i:s', $info['create_time']);
            if($info['fahuo_time']){
                $info['fahuo_time_str']   = date('Y-m-d', $info['fahuo_time']);
            }
            $info['order_status_str'] = supplyGetOrderStatus($info['order_status']);
            if($info['order_price_up']>0)$info['order_price'] = $info['order_price_up'];
        }                   
        return $info;
    }
    /*
    *@note    判断是否是该供应商的订单 
    *@authoer 张超豪
    *@addtime 2016-05-06
    */
    public function belongThisSupplyOrder($order_agent_id, $agent_id){
        if(empty($order_agent_id) or empty($agent_id)){
            return false;
        }elseif($order_agent_id != $agent_id){
            return false;
        }else{
            return true;
        }

    }

    /**
     * author ：张超豪
     * content：订单表表的某一列信息
     * time ：2016-05-07
    **/
    public function getOrderField($key,$order_id = 0){
        if(empty($order_id)){
            return false;
        }
        return $this->where(' order_id = '.$order_id )->getField($key);
    }

    /**
     * author ：张超豪
     * content：统计订单数量
     * time ：2016-05-24
    **/
    public function getOrderCount( $agent_id, $order_status = 0, $order_status_end = 0){
        if(empty($order_status)){
            $order_status = 0;
        }
        if($order_status_end){           
            return $this->where('agent_id = '.$agent_id.' and order_status <= '.$order_status_end.' and order_status >= '.$order_status )->count();
        }else{
            return $this->where('agent_id = '.$agent_id.' and order_status = '.$order_status )->count();
        }
        
    }

    /**
     * author ：张超豪
     * content：统计订单一天的金额
     * time ：2016-05-24
    **/
    public function getOneDayOrderPrice($agent_id, $day = 0, $order_status = '100'){
        $where = ' 1=1  and agent_id = '. $agent_id;
        if($order_status != 100){
           $where = $where . ' and order_status in ('. $order_status.')';
        }
        $shijian = strtotime(date("Y-m-d", time()));
        $start_time = $shijian - $day * 24*3600;
        $end_time = $start_time + 24*3600;
        $where = $where. ' and create_time >= '.$start_time.' and create_time <'.$end_time;

        $sql =  'select SUM(`order_prices`) as order_price from (
                            SELECT   
                                    CASE
                                    WHEN `order_price_up` = 0 THEN
                                        `order_price`
                                    ELSE
                                        `order_price_up`
                                    END
                                 AS `order_prices` 
                            FROM
                                zm_supply_order 
                            WHERE '.$where.'
                                ) as orderTable'; 
        $res = M()->query($sql);
        if(empty($res[0]['order_price'])){
            $res[0]['order_price'] = 0;
        } 
        return $res[0]['order_price'];
                     
    }
    /**
     * author ：张超豪
     * content：统计订单一天的数量
     * time ：2016-05-24
    **/
    public function getOneDayOrderNum($agent_id, $day = 0, $order_status = '100'){
        $where = ' 1=1  and  agent_id = '. $agent_id;
        if($order_status != '100'){
           $where = $where . ' and order_status in ('. $order_status.')';
        }
        $shijian = strtotime(date("Y-m-d", time()));
        $start_time = $shijian - $day * 24*3600;
        $end_time = $start_time + 24*3600;
        $where = $where. ' and create_time >= '.$start_time.' and create_time <'.$end_time;
        return $this->where($where)->count();
        
    }

    /**
     * author ：张超豪
     * content：统计订单一天的支付数量
     * time ：2016-05-24
    **/
    public function getOneDayOrderPayNum($agent_id, $day = 0, $order_status = '100'){
        $where = ' 1=1  and payment_status = 2 and  agent_id = '. $agent_id;
        
        $shijian = strtotime(date("Y-m-d", time()));
        $start_time = $shijian - $day * 24*3600;
        $end_time = $start_time + 24*3600;
        $where = $where. ' and create_time >= '.$start_time.' and create_time <'.$end_time;
        return M('supply_order_payment')->where($where)->field('order_id')->distinct(true)->count();
        
    }

    /**
     * author ：张超豪
     * content：统计订单一天的支付金额
     * time ：2016-05-24
    **/
    public function getOneDayOrderPayPrice($agent_id, $day = 0, $order_status = '100'){
        $where = ' 1=1  and payment_status = 2 and  agent_id = '. $agent_id;
        
        $shijian = strtotime(date("Y-m-d", time()));
        $start_time = $shijian - $day * 24*3600;
        $end_time = $start_time + 24*3600;
        $where = $where. ' and create_time >= '.$start_time.' and create_time <'.$end_time;
        $price = M('supply_order_payment')->where($where)->sum('payment_price');
        if(empty($price))$price=0;
        return $price;
        
    }


    function getSupplyOrderActionList($orderStatus=0){
        $list[0] = array('key'=>'orderNote',            'txt'=>L('text_order_note'));
        //$list[1] = array('key'=>'orderUpdate',          'txt'=>'确定有货和发货日期');
        $list[2] = array('key'=>'orderCancle',          'txt'=>L('text_order_cancel'));//'取消订单'
        $list[3] = array('key'=>'orderConfirm',         'txt'=>L('text_order_confirm'));

        //$list[4] = array('key'=>'orderCancleConfirm',   'txt'=>'取消订单确认');
        //$list[5] = array('key'=>'orderPay',             'txt'=>'订单付款');
        //$list[6] = array('key'=>'confirmPay',             'txt'=>'确定付款');
        $list[7] = array('key'=>'orderPeihuo',          'txt'=>L('text_order_peihuo'));

        //$list[8] = array('key'=>'orderBuchajia',        'txt'=>'订单补差价');
        $list[9] = array('key'=>'orderFahuo',           'txt'=>L('text_order_fahuo'));
        $list[10]= array('key'=>'orderShouhuo',         'txt'=>L('text_order_shouhuo'));
        //$list[11]= array('key'=>'orderWancheng',            'txt'=>'订单完成');


        $orderActionList[] = $list[0];
        if($orderStatus == 0){  //待确定  

        }elseif($orderStatus == 1){ //待付款
        //    $orderActionList[] = $list[2];
        //    $orderActionList[] = $list[3];      
        }elseif($orderStatus == 2){ //待配货            
            $orderActionList[] = $list[7];
        }elseif($orderStatus == 3){ //待发货
            $orderActionList[] = $list[9];
        }elseif($orderStatus == 4){ //待收货
        //    $orderActionList[] = $list[10];
        }elseif($orderStatus == 5){ //待完成
            
        }

        return $orderActionList;
    }

  

}