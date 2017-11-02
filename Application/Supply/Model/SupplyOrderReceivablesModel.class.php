<?php
/*
*@note    订单应收MODEL 
*@authoer 张超豪
*@addtime 2016-05-08
*/
namespace Supply\Model;
use Think\Model;
class SupplyOrderReceivablesModel extends Model{
    /*
    *@note    订单商品应收详情 
    *@authoer 张超豪
    *@addtime 2016-05-08
    */ 
   
    public function getOrderReceivables($order_id){
        //订单退款信息
        $SOC = M('supply_order_compensate');
        $list2 = $SOC->where('order_id = '.$order_id.' and period_type in(12)')->order('create_time')->select();
        foreach ($list2 as $key => $value) {
            if($value['period_type'] == 12){//退差价
                $arr['consignment'][] = $value;
            }
        }
        //订单应付款信息
       
        $OY = M('order_receivables');
        $list = $OY->where('order_id = '.$order_id.' and period_type in(1,2,3,4,12)')->order('period_current')->select();
        foreach ($list as $key => $value) {

            if($value['period_current'] == 1){
                $price1 += $value['receivables_price'];
            }else{
                $price2 += $value['receivables_price'];
            }

            $price += $value['receivables_price'];

            if($value['period_type'] == 1){
                $arr['luozuan'][] = $value;
            }elseif ($value['period_type'] == 2){//散货
                $arr['sanhuo'][] = $value;
            }elseif ($value['period_type'] == 3 or $value['period_type'] == 4){//成品和钻托              
                $arr['consignment'][] = $value;
            }elseif($value['period_type'] == 12){//补差价
                $arr['consignment'][] = $value;
            }


        }

        $arr['total']      = $price;
        $arr['firstPhase'] = $price1?$price1:'0.00';
        $arr['balanceDue'] = $price2?$price2:'0.00';
        return $arr;
    }
	
	/*
    *@note    支付列表 
    *@authoer fangkai
    *@addtime 2016-05-16
    */
	public function paymentList($where,$sort='receivables_id desc',$pageid=1,$pagesize=10){
		$totalnum  = $this
						->alias('os')
						->join('left join zm_supply_order as o on os.order_id = o.order_id')
						->where($where)
						->count();
        $limit     = (($pageid-1)*$pagesize).','.$pagesize;
		$orderList  = $this
						->alias('os')
						->field('os.*,o.order_sn')
						->join('left join zm_order as o on os.order_id = o.order_id')
						->where($where)
						->order($sort)
						->limit($limit)
						->select();
        foreach($orderList as $value){
            $value['order_status_str'] = supplyGetOrderStatus($value['order_status']);
			$value['payment_time_str']  = date('Y-m-d H:i', $value['payment_time']);
            $value['create_time_str']  = date('Y-m-d H:i', $value['create_time']);
            $list[] = $value;
        }
   
        $data['total']  = $totalnum;
        $data['size']	= $pagesize;
        $data['page']	= $pageid;
        $data['list']   = $list;
        return $data;
    }
	
	
	
	
}