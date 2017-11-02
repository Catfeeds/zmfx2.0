<?php
/**
 * auth	：fangkai
 * content：支付表相关操作
 * time	：2016-5-18
**/
namespace Supply\Model;
use Think\Model;
class SupplyOrderPaymentModel extends Model{

	/*
	*@note    支付列表
	*@authoer fangkai
	*@addtime 2016-05-18
	*/
	public function paymentList($where,$sort='payment_id desc',$pageid=1,$pagesize=10,$status=0){

        $limit     = (($pageid-1)*$pagesize).','.$pagesize;
		$orderList  = $this->alias('op')
						->field('op.*,sum(op.discount_price) as total_discount_price,sum(op.payment_price) as total_payment_price,o.order_price,o.create_time as order_create_time')
						->join('left join zm_supply_order as o on op.order_id = o.order_id')
						->where($where)
						->order($sort)
						->group('op.order_id')
						->limit($limit)
						->select();

		$totalnum = count($this->alias('op')
						 ->join('left join zm_supply_order as o on op.order_id = o.order_id')
						 ->where($where)
						 ->group('op.order_id')
						 ->select()
						 //->count()  //不知道为什么不能用这个
						 );
		
        foreach($orderList as $value){
			//查询出最后的支付时间
			$create_time = $this->field('create_time')->where(array('order_id'=>$value['order_id'],'payment_status'=>array('neq','-1')))->order('payment_id DESC')->find();
			 $value['number'] = $this->where(array('order_id'=>$value['order_id'],'payment_status'=>array('neq','-1')))->order('payment_id DESC')->count();
           // $value['order_status_str'] = supplyGetOrderStatus($value['order_status']);
            $value['create_time_str']  = date('Y-m-d H:i', $create_time['create_time']);
			$value['order_create_time_str']  = date('Y-m-d H:i', $value['order_create_time']);
			//收款总金额+折扣总金额 与 订单的总金额 比较
			if(($value['total_payment_price']+$value['total_discount_price']) >= $value['order_price']){
				//已付款
				$value['status'] = 1;
			}else{
				//收款中
				$value['status'] = 2;
			}
			if($value['status'] != $status && $status != 0){
				unset($value);
				break;
			}
            $list[] = $value;
        }
        $data['total']  = $totalnum;
        $data['page_size']	= $pagesize;
        $data['page_id']	= $pageid;
        $data['list']   = $list;
        return $data;
    }
	
	/*
	*@note    支付列表
	*@authoer fangkai
	*@addtime 2016-05-18
	*/
	public function getOrderPaymentList($order_id){
		$data = $this->where(array('order_id'=>$order_id,'payment_status'=>array('neq','-1')))->order('payment_id ASC')->select();
		foreach($data as $key=>$val){
			$data[$key]['create_time_str'] = date('Y-m-d H:i',$val['create_time']);
			$data[$key]['key'] = $key+1;
		}
		return $data;
		
	}
	
	/*
	*@note    支付审核
	*@authoer fangkai
	*@addtime 2016-05-19
	*/
	public function examine($payment_id='',$agent_id=''){
		if(empty($payment_id) || empty($agent_id)){
			return false;
		}
		$where['payment_id'] = $payment_id;
		$where['agent_id'] = $agent_id;
		$check = $this->where($where)->find();
		if(!$check){
			return false;
		}
		$save['payment_status'] = 2;
		$action = $this-where($where)->save($save);
		if($action){
			return true;
		}else{
			return false;
		}
		
		
	}
	
}