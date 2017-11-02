<?php
/*
*@note    订单分期支付MODEL 
*@authoer 张超豪
*@addtime 2016-05-08
*/
namespace Supply\Model;
use Think\Model;
class SupplyOrderPeriodModel extends Model{
    /*
    *@note    保存分期数据 
    *@authoer 张超豪
    *@addtime 2016-05-08
    */ 
    public function getOrderPeriodList($order_id){
		//获取订单的分期信息		
		$periodList = $this->where('order_id = '.$order_id)->select();
		foreach ($periodList as $key => $value) {
			if($value['period_type'] == 1){
				$list['luozuan'] = $value;
			}elseif ($value['period_type'] == 2){//散货
				$list['sanhuo'] = $value;
			}else{
				$list['consignment'] = $value;
			}
		}
		return $list;
	}
}
