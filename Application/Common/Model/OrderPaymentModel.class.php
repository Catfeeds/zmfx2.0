<?php
/**
 * 订单支付模型
 */
namespace Common\Model;
use Think\Model;
class OrderPaymentModel extends Model{
	
	public  $agent_id;

    public function __construct() {
		parent::__construct();
        $this -> agent_id = C('agent_id');
    }
	
	/**
	 * auth	：fangkai
	 * content：分销获取支付订单总数
	 * time	：2016-9-1
	**/
	public function getOrderPayCount($where='1=1',$group=''){
		$orderPayList  = $this->where($where)->group($group)->select();
		$orderPayCount = count($orderPayList);
		if($orderPayCount){
			return $orderPayCount;
		}else{
			return 0;
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：获取支付订单列表
	 * time	：2016-9-2
	**/
	public function getOrderPayList($where='1=1',$order,$group=''){
		$orderPayList  = $this->where($where)->order($order)->group($group)->select();
		if($orderPayList){
			return $orderPayList;
		}else{
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：获取支付订单某字段值总数
	 * time	：2016-9-5
	**/
	public function getOrderCount($where='1=1',$count=''){
		$orderCount  = $this->where($where)->sum($count);
		if($orderCount){
			return $orderCount;
		}else{
			return false;
		}
	}
	
	/**
	*	配送方式方法
	*	zhy
	*	2016年11月8日 11:05:40
	*/
	function this_dispatching_way($id,$data){
		$zpm		= M('delivery_mode');
		if($id && $data){
			$name   = $zpm->where('mode_id = "'.$id.'"')->field('mode_name')->find();
			if($name) 	$data   = array_merge($data,$name);
		}else{
			$data   = $zpm->where("agent_id =" .C('agent_id'))->select();	//获取该分销商的配送方式。
		}	
		return $data ? $data : null;
	}
    
}
?>
