<?php
namespace Supply\Controller;
class ManageController extends SupplyController{

    /**
     * 供应宝框架首页
     */
    public function index(){
        $this             -> go();
		$message           = D('SupplyMessage');
		$data              = $message -> messagelist($this->uid,'','',0,'id desc',array('0'));
		$this -> count_msg = $data['total'];
        if( D('SupplyAccount') -> getAgentId() == '0' ){
            $this -> redirect('Index/apply_open');die;
        }
        $this -> display($this -> Template_Path.'main');
    }
    //路由，输出模板
    public function route(){
        $this -> go();
        if( D('SupplyAccount') -> getAgentId() == '0'){
            $this -> redirect('Index/apply_open');die;
        }
        list($mo,$vi)                   = $this->getRoute();
        if($mo=='Index'){
            //订单的数量情况

            $O = D('Order');
           
            $order_num['daichuli']      = $O->getDaichuliNum($this->agent_id);
            
            $order                      = D('SupplyOrder');          
            $order_num['jingxingzhong'] = $order->getOrderCount($this->agent_id, 2, 5);
            $order_num['pay']           = $order->getOrderCount($this->agent_id, 0, 1);
            $order_num['wancheng']      = $order->getOrderCount($this->agent_id, 6);
            $order_num['quxiao']        = $order->getOrderCount($this->agent_id, -1);
            if($order_num['daichuli']   + $order_num['jingxingzhong'] +$order_num['pay']+ $order_num['wancheng'] + $order_num['quxiao'] == 0){
                $this->order_num_count  = 1;
            }
            $this->order_num     = $order_num;
            
            
            $product_num['luozuan']  = M('supply_goods_luozuan')->where('agent_id ='. $this->agent_id)->count();
            $product_num['sanhuo']   = M('supply_goods_sanhuo')->where('agent_id ='. $this->agent_id)->count();             
            $product_num['chengpin'] = M('goods')->where('goods_type = 3 and agent_id ='. $this->agent_id)->count();  
            $product_num['dingzhi']  = M('goods')->where('goods_type = 4 and agent_id ='. $this->agent_id)->count(); 

            if($product_num['luozuan']      +  $product_num['sanhuo']  + $product_num['chengpin'] + $product_num['dingzhi'] == 0){
                $this->product_num_count  = 1;
            }
            $this->product_num    = $product_num;
             
            

            for($i=0;$i<7;$i++){
                $order_qitian_price[$i]     = $order->getOneDayOrderPrice($this->agent_id, $i, '2,3,4,5,6');// 0 当天    100表示所有的订单，
                $order_qitian_num[$i]       = $order->getOneDayOrderNum($this->agent_id, $i, '2,3,4,5,6');////  0 当天



                $order_qitian_pay_price[$i] = $order->getOneDayOrderPayPrice($this->agent_id, $i, '100');// 0 当天    100表示所有的订单，
                $order_qitian_pay_num[$i]   = $order->getOneDayOrderPayNum($this->agent_id, $i, '100');////  0 当天
            }

            $this->order_qitian_price     = $order_qitian_price;
            $this->order_qitian_num       = $order_qitian_num;
            $this->order_qitian_pay_price = $order_qitian_pay_price;
            $this->order_qitian_pay_num   = $order_qitian_pay_num;

            $this->display($this->Template_Path.$vi);die;
        }else{
            $this->display($this->Template_Path."$mo:$vi");die;
        }
    }
}
