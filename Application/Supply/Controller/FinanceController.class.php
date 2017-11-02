<?php
/**
 * auth	：fangkai
 * content：财务
			财务列表
 * time	：2016-5-16
**/
namespace Supply\Controller;
class FinanceController extends SupplyController{
	public function __construct() {
		parent::__construct();
		//$this->agent_id=;
	}
	
    public function index(){
        $this->display();
    }
	
	/**
	 * auth	：fangkai
	 * content：收款记录
	 * time	：2016-5-16
	**/
	public function finance_list(){
		$page_id   = I('page_id',1);
        $page_size = getPageSize(I('page_size'));

		$order_sn = I('order_sn','','intval');
		if($order_sn){
			$where['o.order_sn'] = $order_sn;
		}
		$status = I('receiving_status',0,'intval');
		$order_time = I('order_time','');
		if($order_time){
			$end_time = strtotime('+1 day',strtotime($order_time));
			$where['o.create_time'] = array(array('EGT',strtotime($order_time)),array('ELT',$end_time));
		}

		$SupplyOrderPayment = D('SupplyOrderPayment');
		$where['op.agent_id'] = $this->agent_id;
		$where['op.payment_status'] = array('neq','-1');
		$data	= $SupplyOrderPayment->paymentList($where,'op.payment_id desc',$page_id,$page_size,$status);
				
		if($data){
			$this->echoJson($data,100,L('success_msg_002'));
			exit;
		}else{
			$this->echoJson($data,0,L('error_msg_003'));
			exit;
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：订单详情
	 * time	：2016-5-17
	**/
	public function financeInfo(){
		$order_id = I('order_id','','intval');

        $SO        = D('SupplyOrder');
        $SOG       = D('SupplyOrderGoods');
        $SOR       = D('SupplyOrderPayment');

        $data['order_info']       = $SO->getOrderInfo($order_id); //订单详情
		$data['order_info']['create_time_cre'] = date('Y-m-d H:i',$data['order_info']['create_time']);
		//剩余支付价格
		$data['order_info']['remaining_collection'] = $data['order_info']['order_price']-$data['order_info']['order_price_up'];
        $this->isBelongYourSupplyOrder($data['order_info']['agent_id'], $this->agent_id);//该订单是否属于该供应宝客户              
        $data['order_goods_list']       = $SOG->getOrderGoodsListInfo($order_id, $data['order_info']['dollar_huilv']);//订单商品详情
		
        $data['order_payment_list'] = $SOR->getOrderPaymentList($order_id);        //已支付信息       

        if(empty($data['order_period_list']['luozuan'])){
             $data['order_period_luozuan_is_quankuan'] = 1; ///是全款支付
        }
        if(empty($data['order_period_list']['sanhuo'])){
             $data['order_period_sanhuo_is_quankuan'] = 1; ///是全款支付
        }
        if(empty($data['order_period_list']['consignment'])){
             $data['order_period_consignment_is_quankuan'] = 1; ///是全款支付
        }
        $this->echoJson($data);
	}
	
	/**
	 * auth	：fangkai
	 * content：支付审核
	 * time	：2016-5-19
	**/
	public function examine(){
		if(IS_AJAX){
			$payment_id = I('post.payment','','intval');
			$SupplyOrderPayment = D('SupplyOrderPayment');
			$action =  $SupplyOrderPayment->examine($payment_id,$this->agent_id);
			if($action == true){
				$this->echoJson($data,100,L('change_success'));
			}else{
				$this->echoJson($data,0,L('change_failed'));
			}
		}
	}

    /**
	 * auth	：zhaochaohao
	 * content：判断订单是否是本人的
	 * time	：2016-5-17
	**/
    private function isBelongYourSupplyOrder($order_agent,$agent_id){//订单的agent, 自己本身的agent
        $SO = D('SupplyOrder');
        $isBelongYourOrder = $SO->belongThisSupplyOrder($order_agent,$agent_id);//该订单是否属于该供应宝客户        
        if(!$isBelongYourOrder){
            $this->error(L('error_msg_004'));
        }
    }







	
}

?>