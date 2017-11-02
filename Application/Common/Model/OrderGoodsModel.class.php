<?php
/**
 * 订单支付模型
 */
namespace Common\Model;
use Think\Model;
class OrderGoodsModel extends Model{
	
	public  $agent_id;

    public function __construct() {
		parent::__construct();
        $this -> agent_id = C('agent_id');
    }
    
	/**
	 * auth	：fangkai
	 * content：分销获取购买的产品总数
	 * time	：2016-9-2
	**/
	public function getOrderSaleNumberSum($where='1=1',$field,$group=''){
		$orderSaleNumberSum  = $this->field($field)->where($where)->group($group)->select();
		
		return $orderSaleNumberSum;
	}
	
}
?>
