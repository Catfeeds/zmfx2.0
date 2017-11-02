<?php
/*
*@note    订单控制器 
*@authoer 张超豪
*@addtime 2016-05-03
*/

namespace Supply\Controller;
class OrderController extends SupplyController{

    public function __construct() {
        parent::__construct();        
        //$SA = D('SupplyAccount');
        //$this->agent_id = $SA->getAccountField('agent_id_build');
    }

    //默认跳转
    public function index(){
        echo L('order_page_title'); exit;
        //$this->redirect('Order/orderList');//默认的跳转不起作用了，
    }

    //订单列表
    public function orderList(){
        $page_id                = I('page_id',1);
        $page_size              = getPageSize(I('page_size'));
        $certificate_number     = I('certificate_number','');
        $order_sn               = I('order_sn','');
	   
        $order_time_begin       = strtotime(I('order_time_begin', 0));
        $order_time_end         = strtotime(I('order_time_end', 0));
        $lanmu_status           = I('lanmu_status', 'treatment');  //默认显示最新的订单
        if(strstr($lanmu_status, '#')){$lanmu_status = substr($lanmu_status,1);}
       
        $where                           = array();
        if($order_sn){
            $where['order_sn']           = array('like', '%'.$order_sn.'%');
        }

        if($certificate_number){
            $sogwhere['certificate_no'] = array('like', '%'.$certificate_number.'%');
            //////////////////////////////////////$sogwhere['agent_id'] = array('eq', '0');                  
            $sog = M('supply_order_goods')->alias('sog')->where($sogwhere)->field('sog.order_id')->select();
            $tempArr = array();
            if($sog){
                foreach($sog as $key=>$val){
                    $tempArr[] = $val['order_id'];
                }
            }
            if($tempArr){
                $orderIds = implode(',',$tempArr);
                $where['order_id'] = array('in', $orderIds);
            }else{
                $where['order_id'] = 0;
            }
        }
        		
		if($order_time_begin and  $order_time_end){  //开始订单时间大于结束订单时间，互相交换
			if($order_time_begin > $order_time_end){			
				$temp             = $order_time_begin;
				$order_time_begin = $order_time_end;
				$order_time_end   = $temp;
			}
            $where['create_time'] = array(array('lt', $order_time_end + 24*3600), array('gt', $order_time_begin ), 'and');            
		}elseif(empty($order_time_begin)  and  $order_time_end){
            $where['create_time'] = array('lt', $order_time_end + 24*3600);  
        }elseif($order_time_begin  and  empty($order_time_end)){
            $where['create_time'] = array('gt', $order_time_begin);
        }
         
        // if($lanmu_status == 'pay'){
        //     $where['order_status'] = array('eq', 1);
        // }else

        if($lanmu_status == 'carried'){
            $where['order_status'] = array(array('lt', 6), array('gt', 1), 'and');
        }elseif($lanmu_status == 'completed'){
            $where['order_status'] = array('eq', 6);
        }elseif($lanmu_status == 'canceled'){
            $where['order_status'] = array('eq', -1);
        }else{
            $where['order_status'] =  array(array('lt', 2), array('gt', -1), 'and');
        }

		//}else{
        //    $order_new_start_time = strtotime(date('Y-m-d', time()));
        //    $order_new_end_time   = $order_new_start_time + 24*60*60;
        //    $where['create_time'] = array(array('lt', $order_new_end_time), array('gt', $order_new_start_time), 'and');
        //}

        $SO    = D('SupplyOrder');      

        $data  = $SO->getOrderList($where,'order_id desc',$page_id,$page_size, $this->agent_id);

        $data['lanmu_status'] = $lanmu_status;       
        $this->echoJson($data);
    }
    //订单详情
    public function orderInfo(){
        $order_id     = intval(I('order_id',0));
        $lanmu_status = I('lanmu_status', '');
    
        if($lanmu_status == 'kehusearch' or $lanmu_status == 'treatment'){
            if($lanmu_status == 'kehusearch'){
                $SO        = M('order');
                $SOG       = M('order_goods');
                $order = $SO->where('order_id = '.$order_id)->find();                  
                $goodslist =  $SOG->where('attribute like \'%;s:9:"supply_id";s:'.strlen($this->agent_id).':"'.$this->agent_id.'";%\' and order_id = '.$order['order_id'])->select();
                $SOL = M('order_log');
            }elseif($lanmu_status == 'treatment'){
                $SO        = M('order',       'zm_', 'ZMALL_DB');
                $SOG       = M('order_goods', 'zm_', 'ZMALL_DB');
                $order     = $SO->where('order_id = '.$order_id)->find();                  
                $goodslist = $SOG->where('attribute like \'%;s:9:"supply_id";s:'.strlen($this->agent_id).':"'.$this->agent_id.'";%\' and order_id = '.$order['order_id'])->select();
                 $SOL = M('order_log',       'zm_', 'ZMALL_DB');
                
			}

            // 生成‘供应商查询中’的文字到订单操作中
            // 管理员在订单中可以看到user_id=0 或者等于自己的
            // 10天内的订单才会添加供应商查询中
            if($order['create_time'] < (time() + 10*86400)){
                $SOL_data['order_id']    = $order_id;
                $SOL_data['note']        = '供应商查询中';
               
                $res_SOL = $SOL->where($SOL_data)->find();
                if(empty($res_SOL)){
                    $SOL_data['group_id'] = 0;
                    $SOL_data['user_id']  = 0;
                    $SOL_data['create_time'] = time();
                    $SOL->add($SOL_data); 
                }
            }
            
           
            if(empty($goodslist)){
                $this->error(L('error_msg_004'));//这订单不是你的，或者这个订单不存在
            }
            $order['order_price'] = 0;

            foreach($goodslist as $v){
                
                // if($v['goods_price_up'] > 0){
                //     $order['order_price'] = $value['order_price'] + $v['goods_price_up'];
                //     $v['goods_price_true'] = $v['goods_price_up'];
                //     $price = $v['goods_price_up'];
                // }else{
                //     $order['order_price'] = $value['order_price'] + $v['goods_price'];
                //     $v['goods_price_true'] = $v['goods_price'];
                //     $price = $v['goods_price'];
                // }

                $goods_dollar_price = getZMorderDollar($v);

                $order['order_price'] = $value['order_price'] + $goods_dollar_price;
                $v['goods_price_true'] = $goods_dollar_price;
                $price = $goods_dollar_price;

                
                if($v['goods_number_up']>0){
                    $v['goods_number_true'] = $v['goods_number_up'];
                }else{
                    $v['goods_number_true'] = $v['goods_number'];
                }

                if($v['fahuo_time'] > 0){
                    $v['fahuo_time_str'] = date("Y-m-d", $v['fahuo_time']);
                }else{
                    $v['fahuo_time_str'] = '';
                }
                
                $v['attribute'] = unserialize($v['attribute']);
				

			   	$orderInfo = $SO->find($order_id);

			  	$v['xian_advantage'] = formatPrice($price/$v['attribute']['weight']/$v['attribute']['dia_global_price']*100);

                if($v['goods_type'] == '1'){
                    $data['order_goods_list']['luozuan'][] = $v;
                }elseif($v['goods_type'] == '2'){
                    $data['order_goods_list']['sanhuo'][] = $v; 
                }else{
                    $data['order_goods_list']['consignment'][] = $v;
                }

            }
            $order['create_time'] = date("Y-m-d H:i", $order['create_time']);

            $data['order_info']      = $order;            
            $data['order_action'][]    = array('key'=>'orderUpdate', 'txt'=>L('text_have_goods_fahuo_time'));//订单操作按钮 确定有货发货日期

        }else{
            $SO        = D('SupplyOrder');
            $SOG       = D('SupplyOrderGoods');
            $SOL       = D('SupplyOrderLog');
            $SOR       = D('SupplyOrderReceivables');
            $SOP       = D('SupplyOrderPeriod');
            $data['order_info']   = $SO->getOrderInfo($order_id); //订单详情

            if($data['order_info']['agent_id'] != $this->agent_id){
                $this->error(L('error_msg_004'));//这订单不是你的，或者这个订单不存在
            }

            $data['order_info']['create_time'] = date("Y-m-d H:i", $data['order_info']['create_time']);

            $data['order_action'] = $SO->getSupplyOrderActionList($data['order_info']['order_status']);           
            $this->isBelongYourSupplyOrder($data['order_info']['agent_id'], $this->agent_id);//该订单是否属于该供应宝客户              
            $data['order_goods_list']       = $SOG->getOrderGoodsListInfo($order_id, $data['order_info']['dollar_huilv']);//订单商品详情
            $data['order_log']              = $SOL->getOrderLog($order_id);          //订单日志
            //订单操作按钮
            $data['order_receivables_list'] = $SOR->getOrderReceivables($order_id);        //应付信息        
            $data['order_period_list']      = $SOP->getOrderPeriodList($order_id);//获取订单分期信息
            if(empty($data['order_period_list']['luozuan'])){
                 $data['order_period_luozuan_is_quankuan'] = 1; ///是全款支付
            }
            if(empty($data['order_period_list']['sanhuo'])){
                 $data['order_period_sanhuo_is_quankuan'] = 1; ///是全款支付
            }
            if(empty($data['order_period_list']['consignment'])){
                 $data['order_period_consignment_is_quankuan'] = 1; ///是全款支付
            }
        }
        //$this->display();
        $data['order_info']['lanmu_status'] = $lanmu_status;

        $this->echoJson($data);
    }

    //订单操作
     public function orderAction($order_id='',$lanmu_status=''){    

         if($lanmu_status == 'kehusearch'){
            $goodslist =  M('order_goods')->where('attribute like \'%;s:9:"supply_id";s:'.strlen($this->agent_id).':"'.$this->agent_id.'";%\' and order_id = '.$order_id)->count();
            if($goodslist == 0){
                $this->error(L('error_msg_004'));
            }
            $this->orderHaveGoodsAndSendDateAction($order_id,'fenxiao');//修改分销商订单      

            exit;

         }elseif($lanmu_status == 'treatment'){
            $goodslist =  M('order_goods', 'zm_', 'ZMALL_DB')->where('attribute like \'%;s:9:"supply_id";s:'.strlen($this->agent_id).':"'.$this->agent_id.'";%\' and order_id = '.$order_id)->count();
            if($goodslist == 0){
                $this->error(L('error_msg_004'));
            }
            $this->orderHaveGoodsAndSendDateAction($order_id,'zm');//修改钻明订单      

            exit;

         }else{
             $SO        = D('SupplyOrder');
             $order_agent_id = $SO->getOrderField('agent_id', $order_id);
             $this->isBelongYourSupplyOrder($order_agent_id, $this->agent_id);//该订单是否属于该供应宝客户
         }    

         if($_POST['orderNote']){  //添加订单
            $note = trim(I('post.note'));            
            $this->addOrderNoteAction($order_id, $note);                        
         }elseif($_POST['orderCancle']){                   
            $this->orderCancleAction($order_id);                        
         }elseif($_POST['orderUpdate']){                   
            $this->orderUpdateAction($order_id);                        
         }elseif($_POST['orderConfirm']){                   
            $this->orderConfirmAction($order_id);                        
        // }elseif($_POST['orderCancleConfirm']){                   
        //    $this->orderCancleConfirmAction($order_id);                        
        // }elseif($_POST['orderPay']){                               
        //    $this->redirect('/Supply/Order/orderPay?order_id='.$order_id); 
            //$this->orderPay($order_id);                           
         }elseif($_POST['orderPeihuo']){                               
            $this->orderPeihuoAction($order_id);                           
         }elseif($_POST['orderFahuo']){                               
            $this->orderFahuoAction($order_id);                           
         }           
        

    //     // 钻明修改备注
    //     if($_POST['noteAction']){
    //         $res1 = $this->markAction($order_id, $_POST['mark_item']);//var_dump($_POST['mark_item']);die;
    //         if(!in_array_diy('7,8', $this->group)){
    //             $res2 = $this->editOrderLuozuanNumber($order_id);
    //         }
    //         if(!empty($_POST['note'])){
    //             $this->noteAction($order_id, htmlspecialchars($_POST['note']));
    //         }else{
    //             if($res1 and $res2){
    //                 $this->success('操作标记和修改订购数量成功！');
    //             }elseif ($res2){
    //                 $this->success('修改订购数量成功！');
    //             }elseif($res1){
    //                 $this->success('操作标记成功！');
    //             }else{
    //                 $this->success('你都没有做任何操作！');
    //             }
    //         }
    //     }
    //     // // 钻明货品确认
    //     // if($_POST['confirmAction']){
    //     //     $this->confirmAction($order_id,$_POST);
    //     // }
    //     // // 钻明手工入账
    //     // if($_POST['paymentAction']){
    //     //     $this->redirect('Admin/Business/payMoney?order_id='.$order_id);
    //     // }
    //     // //发货给客户
    //     // if($_POST['deliveryInfo']){
    //     //     $this->redirect('Admin/Order/deliveryInfo?order_id='.$order_id);
    //     // }
    //     // //取消订单
    //     // if($_POST['quxiaoAction']){
    //     //     $this->quxiaoAction($order_id);
    //     // }
    //     // //取消确认订单
    //     // if($_POST['quxiaoOrderAction']){
    //     //     $this->quxiaoOrderAction($order_id);
    //     // }
    //     // //发货方确认已发货
    //     // if($_POST['confirmDelivery']){
    //     //     $this->confirmDelivery($order_id);
    //     // }
    //     // //订单确认付款
    //     // if($_POST['confirmPay']){
    //     //     $this->confirmPay($order_id);
    //     // }
    //     // //订单配货完成
    //     // if($_POST['purchaseAction']){
    //     //     $this->purchaseAction($order_id);
    //     // }
    //     // //完成订单
    //     // if($_POST['completeAction']){
    //     //     $this->completeAction($order_id);
    //     // }
    //     // //订单补差价
    //     // if($_POST['orderDifference']){
    //     //     $this->orderDifference($order_id);
    //     // }
        
    }

   
    

    //这个订单是你的吗？
    private function isBelongYourSupplyOrder($order_agent,$agent_id){//订单的agent, 自己本身的agent
        $SO = D('SupplyOrder');
        $isBelongYourOrder = $SO->belongThisSupplyOrder($order_agent,$agent_id);//该订单是否属于该供应宝客户        
        if(!$isBelongYourOrder){
            $this->error(L('error_msg_004'));
        }
    }
    //添加订单备注
    public function addOrderNoteAction($order_id, $note){
        if(!$note){
            $this->error(L('text_msg_001'));
        }
        $SOL = D('supply_order_log');

        if($SOL->addOrderLog($order_id, $note, $this->uid, $this->agent_id)){
            $this->success(L('success_msg_003'));
        }else{
            $this->error(L('error_msg_005'));
        }
    }
    ///取消订单
    public function orderCancleAction($order_id){
        
        $SO  = D('supply_order') ;
        $SOL = D('supply_order_log');    

        $order_status = $SO->getOrderField('order_status', $order_id);
        //验证状态
        if($order_status == 1){////原先是0
            $data['order_status'] = '-1';
            $data['update_time'] = time();
            $res = $SO->where('agent_id = '.$this->agent_id.' and order_id = '.$order_id)->save($data);
            
            if($res){
                $SOL->addOrderLog($order_id, L('text_order_cancel'), $this->uid, $this->agent_id);
                $this->success(L('cancel_success'));
            }else{
                $this->error(L('cancel_failed'));
            }
        }else{
            $this->error(L('error_msg_006'));
        }
    }
    //修改订单
    public function orderUpdateAction($order_id = 0){

         $SO  = D('supply_order') ;
         $SOG = D('supply_order_goods');
         $SOL = D('supply_order_log'); 

         $SO->startTrans();

         $order_status    = $SO->getOrderField('order_status', $order_id);
        
         $dollar_huilv    = $SO->getOrderField('dollar_huilv', $order_id);

         
        if($order_status != 0){
            $this->error(L('error_msg_007'));
        }


        
         /////证书货保存
         $goodsLuozuan = I('post.goodsLuozuan');
         foreach ($goodsLuozuan as $key => $value) {    
            // $orderGoodsInfo = $SOG->find($key);  //获取订单商品的信息            
           
            // if($value['price'] <= 0){  //忘记了输入价格时，价格自动变成按照裸钻价格公式计算来的价格
            //     $GL = M('supply_goods_luozuan');
            //     $GL_info = $GL->where('agent_id ='.$this->agent_id.' and gid =' .$orderGoodsInfo['goods_id'])->find();                         
            //     $data['goods_price_up'] = formatPrice($GL_info['weight'] * $GL_info['dia_global_price'] * $GL_info['dia_discount'] * $dollar_huilv/100);              
            // }else{                      
            //     $data['goods_price_up'] = $value['price'];
            // }
            $data['have_goods'] = $value['have_goods']; 
            $data['update_time']= time();
            $data['fahuo_time'] = strtotime($value['fahuo_time']);            
            // $price = $price + ($data['goods_price_up'] - $SOG->where('og_id = '.$key)->getField('goods_price'));                           
            $res = $SOG->where('to_supply = 0 and og_id = '.$key)->save($data);                
            if($res === false){$OG->rollback();$this->error(L('error_msg_008'));}
        }

         /////散货保存
         $goodsSanhuo = I('post.goodsSanhuo');
         foreach ($goodsSanhuo as $key => $value) {    
            // $orderGoodsInfo = $SOG->find($key);  //获取订单商品的信息            
            $data['update_time']= time();
            $data['have_goods'] = $value['have_goods'];
            $data['fahuo_time'] = strtotime($value['fahuo_time']); 
            // if($value['goods_number'] <= 0){  //忘记了重量,默认是1克拉  
            //     $data['goods_number_up'] = 1;//     
            // }else{                      
            //     $data['goods_number_up'] = $value['goods_number'];
            // }

            // if($value['price'] <= 0){  //忘记了输入价格时，价格自动变成按照散货价格公式计算来的价格
            //     $GS = M('supply_goods_sanhuo');
            //     $GS_info = $GS->where('agent_id ='.$this->agent_id.' and goods_id =' .$orderGoodsInfo['goods_id'])->find();                
            //     $data['goods_price_up'] = formatPrice($GS_info['good_price'])*$data['goods_number_up'];              
            // }else{                      
            //     $data['goods_price_up'] = $value['price']*$data['goods_number_up'];
            // }
             
            // $price = $price + ($data['goods_price_up'] - $SOG->where('og_id = '.$key)->getField('goods_price'));                           
            $res = $SOG->where('to_supply = 0 and og_id = '.$key)->save($data);                
            if($res === false){$SOG->rollback();$this->error(L('error_msg_008'));}
        }

         /////成品保存
         $goodsConsignment = I('post.goodsConsignment');
         foreach ($goodsConsignment as $key => $value) {    
            // $orderGoodsInfo = $SOG->find($key);  //获取订单商品的信息            
            $data['update_time']= time();
            $data['have_goods'] = $value['have_goods'];
            $data['fahuo_time'] = strtotime($value['fahuo_time']);  
            // if($value['goods_number'] <= 0){  //忘记了数量,默认是1克拉  
            //     $data['goods_number_up'] = 1;//     
            // }else{                      
            //     $data['goods_number_up'] = $value['goods_number'];
            // }

            // if($value['price'] <= 0){  //忘记了输入价格时，价格自动变成按照散货价格公式计算来的价格
            //     $G = M('supply_goods');
            //     $G_info = $G->where('agent_id ='.$this->agent_id.' and goods_id =' .$orderGoodsInfo['goods_id'])->find();                
            //     $data['goods_price_up'] = formatPrice($G_info['good_price'])*$data['goods_number_up'];              
            // }else{                      
            //     $data['goods_price_up'] = $value['price']*$data['goods_number_up'];
            // }
             
            // $price = $price + ($data['goods_price_up'] - $SOG->where('og_id = '.$key)->getField('goods_price'));                           
            $res = $SOG->where('to_supply = 0 and og_id = '.$key)->save($data);                
            if($res === false){$SOG->rollback();$this->error(L('error_msg_009'));}
        }

        
        if($res === false){
            $SO->rollback();
            $SOL->addOrderLog($order_id,L('error_msg_010'), $this->uid, $this->agent_id); 
            $this->error(L('error_msg_011'));
            return false;
        }else{
            $SO->commit();            
            $SOL->addOrderLog($order_id, L('success_msg_004'), $this->uid, $this->agent_id); 
            $this->success(L('success_msg_004'));
            return true;
        }
    }
    //订单确定
    public function orderConfirmAction($order_id=0){
        $SO  = D('supply_order') ;
        $SOL = D('supply_order_log') ;
        $order_status    = $SO->getOrderField('order_status', $order_id);
        if($order_status != 1){
            $this->error(L('error_msg_042'));
        }
        
        $SOL->addOrderLog($order_id, L('success_msg_005'), $this->uid, $this->agent_id);
        $SO->where('order_id = '. $order_id)->setField('order_status', 2);
        $this->success(L('success_msg_005'));

    }
    //订单确定
    /*
    public function orderConfirmAction_bak($order_id=0){
        exit;
        $SO  = D('supply_order') ;
        $SOG = D('supply_order_goods');
        $SOL = D('supply_order_log'); 
        $SOP = M('supply_order_period');
        $SOR = M('supply_order_receivables');
        $SO->startTrans();

        $order_status    = $SO->getOrderField('order_status', $order_id);
        
        $dollar_huilv    = $SO->getOrderField('dollar_huilv', $order_id);
        $info = $SO->find($order_id);//单条订单数据
        
        if($order_status != 0){
            $this->error(L('error_msg_007'));
        }
        
        $goodsLuozuan = I('post.goodsLuozuan');//裸钻产品数据
        $goodsSanhuo  = I('post.goodsSanhuo');//散货产品数据
        $goodsConsignment  = I('post.goodsConsignment');//成品数据        
        $data         = I('post.');        
        $goods_list   = $SOG->getOrderGoodsListInfo($order_id, $dollar_huilv);//订单商品详情
        
        if(count($consignment)>0 or count($goods_list['consignment']) >0){
                $order_consignment_price = 0;//订单代销货价格
                //页面发过来的数据和数据表裸钻数据个数对比
                $goods_type = 4;
              
                if(count($goodsConsignment) != count($goods_list['consignment'])){
                    $SO->rollback();                    
                    $this->error(L('error_msg_013'));
                }                                              
               
                foreach ($goods_list['consignment'] AS $v){
                    // $num = $consignment[$v['og_id']]['goods_num'];//购买的数量
                    
                    //  //修改订单产品价格和数量语句
                    // $update['goods_price_up']   = $consignment[$v['og_id']]['price'];
                    // $update['goods_number_up']  = $consignment[$v['og_id']]['goods_num'];
                    // $update['update_time']      = time();
                    //$order_consignment_price    += $consignment[$v['og_id']]['price']* $consignment[$v['og_id']]['goods_number'];
                    
                     if($v['goods_price_up']>0){
                        $order_consignment_price += $v['goods_price_up'];
                    }else{
                        $order_consignment_price += $v['goods_price'];
                    }
                    // $res2 = $SOG->where('og_id = '.$v['og_id'])->save($update);
                    // unset($update);
                    // if(!$res2){
                    //     //失败事务回滚
                    //     $SO->rollback();
                    //     $this->error('修改成品信息出错！');
                    // }
                }
                //生成代销货订单应付信息
                if($data['consignmentPay'] == 1 and $order_consignment_price != 0){//做期支付
                    //对发送过来的数据进行验证
                    if(empty($data['consignment_period_num'])){
                        $SO->rollback();
                        $this->error(L('error_msg_014'));
                    }
                    //生成一条记录到订单分期表
                   
                    $periodArr['order_id']       = $order_id;
                    $periodArr['period_type']    = $goods_type;
                    $periodArr['period_num']     = $data['consignment_period_num'];
                    $periodArr['period_overdue'] = $data['consignment_period_overdue'];
                    $periodArr['agent_id']       = $this->agent_id;
                    if($SOP->add($periodArr)){
                        $order_price = 0;//当前分期支付总价格
                        foreach ($data['consignment_period'] as $key => $value) {
                            // 分期时间或金额不能为空!
                            if(empty($value['time']) or empty($value['price'])){
                                $SO->rollback();
                                 $this->error(L('error_msg_015'));
                            }
                            if($key){
                                // 做期支付后面一个时间不能小于等于前一个时间!
                                if($value['time'] <= $data['consignment_period'][$key-1]['time']){
                                    $SO->rollback();
                                     $this->error(L('error_msg_016'));
                                }
                            }else{
                                // 首期支付时间不能小于1天!
                                if($value['time']< 1){
                                    $SO->rollback();
                                    $this->error(L('error_msg_017'));
                                }
                            }
                            // 生成应付信息
                            $order_price                += $value['price'];
                            $add['order_id']            = $order_id;
                            $add['receivables_price']   = $value['price'];
                            $add['create_time']         = time();
                            $add['period_day']          = $value['time'];
                            $add['payment_time']        = time()+$value['time']*86400;
                            $add['period_current']      = $key+1;
                            $add['period_type']         = $goods_type;
                            $add['parent_type'] = $info['parent_type'];
                            $add['parent_id'] = $info['parent_id'];
                            $add['uid']                 = $info['uid'];
                            $add['tid']                 = $info['tid'];
                            $add['agent_id']            = $this->agent_id;
                            if(!$SOR->add($add)){
                                $SO->rollback();
                                $this->error(L('error_msg_018'));
                            }
                            unset($add);
                        }
                        // 代销货应付价格 和 代销产品总价格对比
                        if(round($order_price,2) != round($order_consignment_price,2)){
                            $SO->rollback();
                            $this->error(L('error_msg_019'));
                        }
                    }else{
                        $SO->rollback();
                        $this->error(L('error_msg_020'));
                    }
                }elseif($data['consignmentPay'] == 2 and $order_consignment_price != 0) {//全额支付
                    $add['order_id']            = $order_id;
                    $add['receivables_price']   = $order_consignment_price;
                    $add['create_time']         = time();
                    $add['period_day']          = 30;
                    $add['payment_time']        = time()+2592000;
                    $add['period_current']      = 1;
                    $add['period_type']         = $goods_type;
                    $add['parent_type']         = $info['parent_type'];
                    $add['parent_id']           = $info['parent_id'];
                    $add['uid']                 = $info['uid'];
                    $add['tid']                 = $info['tid'];
                    $add['agent_id']            = $this->agent_id;

                    if(!$SOR->add($add)){
                         $SO->rollback();
                         $this->error(L('error_msg_020'));
                    }
                   
                    unset($add);
                }else{
                    $SO->rollback();
                    $this->error(L('error_msg_021'));
                }
                $order_price_up += $order_consignment_price;
            }

        //裸钻数据验证
            if(count($goodsLuozuan)>0 or count($goods_list['luozuan']) >0){
                $order_luozuan_price = 0;//订单裸钻总价格
                 $goods_type = 1;
                //页面发过来的数据和数据表裸钻数据个数对比
                if(count($goods_list['luozuan']) != count($goodsLuozuan)){
                    $SO->rollback();
                    $this->error(L('error_msg_022'));
                }
                //裸钻库存检查,减少库存,修改订单产品价格和数量
                
                foreach ($goods_list['luozuan'] AS $v){
                    // if($goodsLuozuan[$v['og_id']]['goods_num']){//有购买产品修改库存

                    //     //修改订单产品价格和数量语句
                    //     $update['goods_price_up'] = $goodsLuozuan[$v['og_id']]['price'];
                    //     $update['have_goods'] = $goodsLuozuan[$v['og_id']]['have_goods'];
                    //     $update['update_time'] = time();
                    //     $order_luozuan_price += $goodsLuozuan[$v['og_id']]['price'];
                            
                            if($v['goods_price_up']>0){
                                $order_luozuan_price += $v['goods_price_up'];
                            }else{
                                $order_luozuan_price += $v['goods_price'];
                            }
                            if($v['have_goods'] == 0){
                                $SO->rollback();
                                $this->error(L('error_msg_023'));
                            }
                    //     $res2 = $OG->where('og_id = '.$v['og_id'])->save($update);
                    //     unset($update);
                    //     if(!$res2){
                    //         //失败事务回滚
                    //         $O->rollback();
                    //         $this->log('error', 'A22');
                    //     }
                    // }else{
                    //     //修改订单产品价格和数量语句
                    //     $update['goods_price_up'] = 0;
                    //     $update['goods_number_up'] = $goodsLuozuan[$v['og_id']]['goods_num'];
                    //     $update['update_time'] = time();
                    //     $res2 = $OG->where('og_id = '.$v['og_id'])->save($update);
                    //     unset($update);
                    //     if(!$res2){
                    //         //失败事务回滚
                    //         $O->rollback();
                    //         $this->log('error', 'A35');
                    //     }
                    // }
                }
                //生成裸钻订单应付信息
                if($data['luozuanPay'] == 1 and $order_luozuan_price != 0){//做期支付
                    //对发送过来的数据进行验证
                    if(empty($data['luozuan_period_num'])){
                        $SO->rollback();
                        $this->error(L('error_msg_024'));
                    }
                    //生成一条记录到订单分期表
                   
                    $periodArr['order_id']      = $order_id;
                    $periodArr['period_type']   = $goods_type;
                    $periodArr['period_num']    = $data['luozuan_period_num'];
                    $periodArr['period_overdue']= $data['luozuan_period_overdue'];
                    $periodArr['agent_id']      = $this->agent_id;
                    if($SOP->add($periodArr)){
                        $order_price = 0;//当前分期支付总价格
                        foreach ($data['luozuan_period'] as $key => $value) {
                            // 分期时间或金额不能为空!
                            if(empty($value['time']) or empty($value['price'])){
                                $SO->rollback();
                                $this->error(L('error_msg_025'));
                            }
                            if($key){
                                // 做期支付后面一个时间不能小于等于前一个时间!
                                if($value['time'] <= $data['luozuan_period'][$key-1]['time']){
                                    $SO->rollback();
                                    $this->error(L('error_msg_026'));
                                }
                            }else{
                                // 首期支付时间不能小于1天!
                                if($value['time']< 1){
                                    $SO->rollback();
                                    $this->error(L('error_msg_027'));
                                }
                            }
                            // 生成应付信息
                            $order_price              += $value['price'];
                            $add['order_id']          = $order_id;
                            $add['receivables_price'] = $value['price'];
                            $add['create_time']       = time();
                            $add['period_day']        = $value['time'];
                            $add['payment_time']      = time()+$value['time']*86400;
                            $add['period_current']    = $key+1;
                            $add['period_type']       = $goods_type;
                            $add['parent_type']       = $info['parent_type'];
                            $add['parent_id']         = $info['parent_id'];
                            $add['uid']               = $info['uid'];
                            $add['tid']               = $info['tid'];
                            $add['agent_id']          = $this->agent_id;
                            if(!$SOR->add($add)){
                                $SO->rollback();
                                $this->error(L('error_msg_028'));
                            }
                            unset($add);
                        }
                        // 裸钻应付价格 和 裸钻产品总价格对比
                        if(round($order_price,2) != round($order_luozuan_price,2)){
                            $SO->rollback();
                            $this->error(L('error_msg_029'));
                        }
                    }else{
                        $SO->rollback();
                        $this->error(L('error_msg_030'));
                    }
                }elseif($data['luozuanPay'] == 2 and $order_luozuan_price != 0) {//全额支付
                    $add['order_id']          = $order_id;
                    $add['receivables_price'] = $order_luozuan_price;
                    $add['create_time']       = time();
                    $add['period_day']        = 30;
                    $add['payment_time']      = time()+2592000;
                    $add['period_current']    = 1;
                    $add['period_type']       = $goods_type;
                    $add['parent_type']       = $info['parent_type'];
                    $add['parent_id']         = $info['parent_id'];
                    $add['uid']               = $info['uid'];
                    $add['tid']               = $info['tid'];
                    $add['agent_id']          = $this->agent_id;
                    if(!$SOR->add($add)){
                        $SO->rollback();
                        $this->error(L('error_msg_030'));
                    }
                    unset($add);
                }
                $order_price_up += $order_luozuan_price;
            }
            //散货数据验证
            if(count($goods_list['sanhuo']) > 0 or count($goodsSanhuo) > 0){
                $order_sanhuo_price = 0;//订单散货总价格
                //页面发过来的数据和数据表散货数据个数对比
                if(count($goods_list['sanhuo']) != count($goodsSanhuo)){
                    $SO->rollback();
                    $this->error(L('error_msg_031'));
                }
                $goods_type = 2;
                //散货库存检查和修改库存和价格
                //$SGS  = M('supply_goods_sanhuo');
                //$GSL = M('goods_sanhuo_lock');
                foreach ($goods_list['sanhuo'] AS $v){
                    //if($goodsSanhuo[$v['og_id']]['goods_num']){//有购买散货产品修改库存
                        //钻明确认,减库存，如果采购单给下级分销商加一个锁定库存
                        // if(!in_array_diy('7,8', $this->group)){
                        //     $number = $GS->where("goods_sn = '".$v['certificate_no']."'")->getField('goods_weight');
                            
                        //     //判断散货库存是否够，由于需要将散货缺货的自动转成订货单，这里取消这段代码，2015-12-21
                        //     $number = $number  - $goodsSanhuo[$v['og_id']]['goods_num'];//库存重量-当前销售重量
                        //     if($number < 0){
                        //         $O->rollback();
                        //         $this->log('error', 'A59');
                        //     }
                            
                            
                        // }
                        
                       
                        //修改订单产品价格和数量语句
                         // $update['goods_price_up'] = $goodsSanhuo[$v['og_id']]['price'];
                         // $update['goods_number_up'] = $goodsSanhuo[$v['og_id']]['goods_num'];
                        // $update['update_time'] = time();
                        //$order_sanhuo_price += $goodsSanhuo[$v['og_id']]['price'] *  $goodsSanhuo[$v['og_id']]['goods_number'];
                         if($v['goods_price_up']>0){
                            $order_sanhuo_price += $v['goods_price_up'];
                         }else{
                            $order_sanhuo_price += $v['goods_price'];
                         }
                         
                        // $res2 = $OG->where('og_id = '.$v['og_id'])->save($update);
                        // unset($update);
                        // if(!$res2){
                        //     //失败事务回滚
                        //     $O->rollback();
                        //     $this->log('error', 'A59');
                        // }
                    // }else{
                    //     //修改订单产品价格和数量语句
                    //     $update['goods_price_up'] = $goodsSanhuo[$v['og_id']]['price'];
                    //     $update['goods_number_up'] = $goodsSanhuo[$v['og_id']]['goods_num'];
                    //     $update['update_time'] = time();
                    //     $res2 = $OG->where('og_id = '.$v['og_id'])->save($update);
                    //     unset($update);
                    //     if(!$res2){
                    //         //失败事务回滚
                    //         $O->rollback();
                    //         $this->log('error', 'A35');
                    //     }
                    //}
                }
               
                //生成散货订单应付信息
                if($data['sanhuoPay'] == 1 and $order_sanhuo_price != 0){//做期支付
                    //对发送过来的数据进行验证
                    if(empty($data['sanhuo_period_num'])){
                       $SO->rollback();
                       $this->error(L('error_msg_031'));
                    }
                    //生成一条记录到订单分期表
                    $periodArr['agent_id']       = $this->agent_id;
                    $periodArr['order_id']       = $order_id;
                    $periodArr['period_type']    = $goods_type;
                    $periodArr['period_num']     = $data['sanhuo_period_num'];
                    $periodArr['period_overdue'] = $data['sanhuo_period_overdue'];
                    if($SOP->add($periodArr)){
                        $order_price = 0;//当前分期支付总价格
                        foreach ($data['sanhuo_period'] as $key => $value) {
                            // 分期时间或金额不能为空!
                            if(empty($value['time']) or empty($value['price'])){
                                $SO->rollback();
                                $this->error(L('error_msg_032'));
                            }
                            if($key){
                                // 做期支付后面一个时间不能小于等于前一个时间!
                                if($value['time'] <= $data['sanhuo_period'][$key-1]['time']){
                                    $SO->rollback();
                                    $this->error(L('error_msg_033'));
                                }
                            }else{
                                // 首期支付时间不能小于1天!
                                if($value['time']< 1){
                                    $SO->rollback();
                                    $this->error(L('error_msg_034'));
                                }
                            }
                            // 生成应付信息
                            $order_price                += $value['price'];
                            $add['order_id']            = $order_id;
                            $add['receivables_price']   = $value['price'];
                            $add['create_time']         = time();
                            $add['period_day']          = $value['time'];
                            $add['payment_time']        = time()+$value['time']*86400;
                            $add['period_current']      = $key+1;
                            $add['period_type']         = $goods_type;
                            $add['parent_type']         = $info['parent_type'];
                            $add['parent_id']           = $info['parent_id'];
                            $add['uid']                 = $info['uid'];
                            $add['tid']                 = $info['tid'];
                            $add['agent_id']            = $this->agent_id;
                            if(!$SOR->add($add)){
                                $SO->rollback();
                                $this->error(L('error_msg_035'));
                            }
                            unset($add);
                        }
                        // 散货应付价格 和 散货产品总价格对比
                        if(round($order_price,2) != round($order_sanhuo_price,2)){
                            
                            $SO->rollback();
                            $this->error(L('error_msg_036'));
                        }
                    }else{
                        $SO->rollback();
                        $this->error(L('error_msg_037'));
                    }
                }elseif($data['sanhuoPay'] == 2 and $order_sanhuo_price != 0) {//全额支付
                    $add['order_id']            = $order_id;
                    $add['receivables_price']   = $order_sanhuo_price;
                    $add['create_time']         = time();
                    $add['period_day']          = 30;
                    $add['payment_time']        = time()+2592000;
                    $add['period_current']      = 1;
                    $add['period_type']         = $goods_type;
                    $add['parent_type']         = $info['parent_type'];
                    $add['parent_id']           = $info['parent_id'];
                    $add['uid']                 = $info['uid'];
                    $add['tid']                 = $info['tid'];
                    $add['agent_id']            = $this->agent_id;
                   
                    if(!$SOR->add($add)){
                        $SO->rollback();
                        $this->error(L('error_msg_038'));
                    }
                    unset($add);
                }
                $order_price_up += $order_sanhuo_price;
            }
           
            //修改订单状态和总价格
            $orderData['order_status'] = 1;
            $orderData['order_price_up'] = $order_price_up;
            $orderData['update_time'] = time();
            
            //总价格等于0，不能确认订单
            if($order_price_up == 0){
                $SO->rollback();
                $this->error(L('error_msg_040'));
            }
            if($SO->where('order_id = '.$order_id)->save($orderData)){//成功事务提交
                $SO->commit();             
                $note = L('error_msg_041');
               
                if($SOL->addOrderLog($order_id, $note, $this->uid, $this->agent_id)){
                    $this->success(L('success_msg_005'));
                }else{
                    $this->error(L('error_msg_039'));
                }


            }else{//失败事务回滚和报错
                $SO->rollback();
                $this->error(L('error_msg_042'));;
            }

           
    }
    */
    //取消确定
    public function orderCancleConfirmAction($order_id = 0){

        exit;
        $SO = D('SupplyOrder');
        $SO->startTrans();
        $info = $SO->where('order_id = '.$order_id)->find();
        if(!$info){
            $this->error(L('error_msg_043'));
        }
        //订单状态为1，2，3才可以取消
        if(in_array($info['order_status'],array(1,2,3))){
            //实例化一些表
            $OG   = M('order_goods');//订单产品表
            $GL   = D('GoodsLuozuan');//裸钻表    
            $GS   = M('goods_sanhuo');//散货表
            $GSL  = M('goods_sanhuo_lock');//分销商散货库存表
            $G    = M('goods');//珠宝产品表
            $GSKU = M('goods_sku');//珠宝成品SKU库存表
            $OP   = M('order_payment');//收款记录表
            $OR   = M('order_receivables');//应收款表
            $OC   = M('order_compensate');//应退款表
            $ZOR  = M('order_refund');//退款表
            $OP2  = M('order_period');//分期表
            //库存恢复（1裸钻，2散货，3珠宝成品,4珠宝定制）
            $list = $OG->where($where)->select();

            foreach ($list as $key => $value) {
                if($value['goods_number_up'] <= 0){
                    $value['goods_number_up'] = $value['goods_number'];
                }
                
                if($value['goods_type'] == 1 and $value['goods_number_up'] > 0){//证书货恢复库存
                    //if(in_array(7, $this->group)){$number = '-1';}
                   // elseif (in_array(7, $this->group)){ $number = '-2';}
                   // else{ $number = '1'; }
                    $number=1;
                    $res1 = $GL->where(" certificate_number = '".$value['certificate_no']."'")->setField('goods_number',$number);
                    if(!$res1){
                        $O->rollback();
						$certificate_no  = $value['certificate_no'];
						$msg  = L('error_msg_044');
						$message = sprintf($msg,$certificate_no);
                        $this->error($message);
                    }
                }elseif ($value['goods_type'] == 2 and $value['goods_number_up'] > 0){//散货恢复库存
                    if(in_array_diy('7,8', $this->group)){
                        $where1 = 'agent_id = '.C('agent_id').' and tid ='.$this->tid.' and goods_id = '.$value['goods_id'];
                        $number = $GSL->where($where1)->getField('goods_weight');
                        $number = $number + $value['goods_number_up'];
                        $res2 = $GSL->where($where1)->setField('goods_weight',$number);
                    }else{
                        $where2 = "agent_id = ".C('agent_id')." and goods_sn = '".$value['certificate_no']."'";
                        $number = $GS->where($where2)->getField('goods_weight');
                        $number = $number + $value['goods_number_up'];
                        $res2 = $GS->where($where2)->setField('goods_weight',$number);
                    }
                    if(!$res2){
						$O->rollback();
						$certificate_no  = $value['certificate_no'];
						$msg  = L('error_msg_044');
						$message = sprintf($msg,$certificate_no);
                        $this->error($message);
                    }
                }elseif ($value['goods_type'] == 3  and $value['goods_number_up'] > 0){//珠宝产品恢复库存

                    
                    $info    = unserialize($value['attribute']);
                    $where3  = 'agent_id = '.C('agent_id').' and goods_id = '.$value['goods_id'];
                    $where4  = 'agent_id = '.C('agent_id').' and goods_id = '.$value['goods_id']." and sku_sn = '$info[sku_sn]'";
                    $number1 = $G->where($where3)->getField('goods_number');
                    $number2 = $GSKU->where($where4)->getField('goods_number');
                    $number3 = $number1 + $value['goods_number_up'];
                    $number4 = $number2 + $value['goods_number_up'];
                    $res3    = $G->where($where3)->setField('goods_number',$number3);
                    $res4    = $GSKU->where($where4)->setField('goods_number',$number4);

                    if(!$res3 or !$res4){
                        $O->rollback();
						$certificate_no  = $value['certificate_no'];
						$msg  = L('error_msg_044');
						$message = sprintf($msg,$certificate_no);
                        $this->error($message);
                    }

                }
                
            }
            //改变订单状态
            $data['order_status'] = 0;
            $data['update_time'] = time();
            $data['order_price_up'] = '0';
            $res4 = $O->where($where)->save($data);
            if(!$res4){
                $O->rollback();
                $this->error(L('error_msg_045'));
            }
            //把所有的已审核收款记录改为未收款
            $t = $OP->where($where.' and payment_status = 2')->count();
            $res5 = $OP->where($where.' and payment_status = 2')->setField('payment_status',1);
            if($t != $res5){
                $O->rollback();
                $this->error(L('error_msg_046'));
            }
            //散货应收款和和产品分期记录
            $t = $OR->where($where)->count();
            $res6 = $OR->where($where)->delete();
            if($t != $res6){
                $O->rollback();
                $this->error(L('error_msg_047'));
            }
            $t = $OP2->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->count();
            $res7 = $OP2->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->delete();
            if($t != $res7){
                $O->rollback();
                $this->error(L('error_msg_048'));
            }
            //应退款（只是退差价部分）
            $t = $OC->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->count();
            $res9 = $OC->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->delete();
            if($t != $res9){
                $O->rollback();
                $this->error(L('error_msg_049'));
            }
            //退款记录的清理（只是退差价部分）
            $t = $ZOR->where($where.' and refund_status = 2')->count();
            $res10 = $ZOR->where($where.' and refund_status = 2')->setField('refund_status',1);
            if($t != $res10){
                $O->rollback();
                $this->error(L('error_msg_050'));
            }
            //恢复订单产品数量价格
            $ogData['goods_number_up'] = '0';
            $ogData['goods_price_up'] = '0';
            $where = $where." and goods_number_up > 0 and goods_price_up > 0";
            $t = $OG->where($where)->count();
            $res8 = $OG->where($where)->save($ogData);
            if($t != $res8){
                $O->rollback();
                $this->error(L('error_msg_051'));
            }
            $O->commit();
            $notes =L('error_msg_052');
            $this->orderLog($order_id, $notes, L('success_msg_006'));
        }else{
            $this->error(L('error_msg_053'));
        }
    }

	
	
	
	/**
	*	删除订单
	*	zhy
	*	2016年11月15日 17:13:47
	*/
	public function	order_delete_canceled($order_id = 0){
		$res7 = D('SupplyOrder')->where('agent_id = '.$this->agent_id.' and order_id = '.$order_id)->delete();
		if($res7 ){
			$data['msg']=L('delete').L('success');
		}else{
			$data['msg']=L('delete').L('failed');
		}
		echo json_encode($data);exit();
	} 
	
	
    /////////////确定配货
    public function orderPeihuoAction($order_id = 0){
        $SO  = D('SupplyOrder');
        $SOL = D('SupplyOrderLog');
        
        //如果要恢复确定支付后再发货，只需将这四行删除
        // $res1 = M('order_payment')->where('payment_status = 2 and  order_id = '.I('get.order_id'))->select();        
        // if(empty($res1)){
        //  $this->error('至少要支付一次货款，或者您的支付信息还没通过审核!');
        // }
        
        
        $data['order_status'] = 3;
        $data['update_time']  = time();
        $res = $SO->where('order_status = 2 and order_id = '.intval(I('order_id')))->save($data);
        if($res){
            $note = L('success_msg_007');
            $SOL->addOrderLog($order_id, $note, $this->uid, $this->agent_id);
            $this->success($note);
        }else{
            $this->error(L('error_msg_054'));
        }
    }

    /////////////确定发货
    public function orderFahuoAction($order_id = 0){
        $SO  = D('SupplyOrder');
        $SOL = D('SupplyOrderLog');
       
        
        $data['order_status'] = 4;
        $data['update_time']  = time();
        $res = $SO->where('order_status = 3 and order_id = '.intval(I('order_id')))->save($data);
        if($res){
            $note = L('success_msg_009');
            $SOL->addOrderLog($order_id, $note, $this->uid, $this->agent_id);
            $this->success($note);
        }else{
            $this->error('发货失败');
        }
    }

    //////////////订单支付
    public function orderPay($order_id = 0 , $payment_id = 0){
        $SOR = D('SupplyOrderReceivables');
        $SO  = D('SupplyOrder');
        $PM  = M('payment_mode');
        $OP  = D('SupplyOrderPayment');
       

        if(IS_POST){           
            $info = $SO->getOrderInfo('', I('post.order_sn'));
           
            if($info){
                // 修改收款验证状态                
                if($payment_id){
                    $payInfo = $OP->where('agent_id = '.$this->agent_id)->find($payment_id);
                    if($payInfo['payment_status'] != 1){
                        $this->log('error', 'A85','ID:'.$payment_id);
                    }
                }
                //凭证上传
                header("Content-Type: text/html; charset=UTF-8");
                $upload = new \Think\Upload (); // 实例化上传类
                $upload->maxSize = 3145728; // 设置附件上传大小
                $upload->exts = array ('jpg','gif','png','jpeg' ); // 设置附件上传类型
                $upload->savePath = './Uploads/pay/'; // 设置附件上传目录
                $Finfo = $upload->uploadOne ($_FILES ['template_img'] ); // 上传文件
                $data = I('POST.');
                if($Finfo) $data['payment_voucher'] = $Finfo ['savepath'] . $Finfo ['savename'];

                //没有（入款折扣）权限删除入款折扣
                // $Auth = new Auth();
                // if(!$Auth->check('Admin/Business/discount', $this->uid) and ($this->is_superManage!=1)){
                //     unset($data['discount_price']);
                // }

                //收款金额和收款折扣必须有一个
                if(empty($data['discount_price']) and empty($data['payment_price'])) {
                    $this->error(L('error_msg_055'));
                }
                if( empty($data['create_time'])){
                    $this->error(L('error_msg_056'));
                }
                 if(empty($data['payment_mode'])){
                    $this->error(L('error_msg_057'));
                }
                //组装数据
                         
                $data['order_id']       = $info['order_id'];
                $data['uid']            = $info['uid'];
                $data['tid']            = $info['tid'];
                $data['parent_id']      =  $data['parent_id']?$data['parent_id']:0;
                $data['discount_price'] = round($data['discount_price']?$data['discount_price']:0,2);//折扣
                $data['payment_price']  = round($data['payment_price'],2);
                $data['create_time']    = strtotime($data['create_time']);//把页面时间转化为时间戳
                $data['payment_note']   = $data['note'];unset($data['note']);
                $data['payment_status'] = 1;//手工入款自动确认支付                
                $data['agent_id']       = $this->agent_id;  
                
                if($payment_id){
                    $res = $OP->where('agent_id = '.$this->agent_id.' and payment_id = '.$payment_id)->save($data);
                    if($res!==false){
                        //$this->log('success', 'A86','ID:'.$payment_id, U('Admin/Business/payment'));
                        $this->success(L('success_msg_008'));
                    }else{
                        $this->error(L('error_msg_058'));
                    }
                }else{
                    if($OP->add($data)){
                        $msg = L('text_msg_002').'￥：'.$data['payment_price'].L('text_product_dia_discount').'￥：'.($data['discount_price']?$data['discount_price']:0).L('text_msg_003').'ID:'.$res;
                        //$this->orderLog($info['order_id'], $msg, L('A38'), U('Admin/Business/payment'));
                        $this->success(L('success_msg_008'), U('/Supply/Order/orderInfo/order_id.html='.$info['order_id']));
                    }else{
                        $this->error(L('error_msg_058'));
                    }
                }
            }else{
                 $this->log('error','A27' );
            }
        }else{

            $order_agent_id = $SO->getOrderField('agent_id', $order_id);
            $this->isBelongYourSupplyOrder($order_agent_id, $this->agent_id);//该订单是否属于该供应宝客户             
            //获取手工入款方式
            //$Auth = new Auth();
            //if($Auth->check('Admin/Business/discount', $this->uid)  or ($this->is_superManage==1)){
                $this->discount = 1;  ///入款折扣权限
            //}
            $this->payModeList = $PM->where('agent_id = ' . $this->agent_id)->select();
            //支付收款id
            if($payment_id){
                $this->payment_id = $payment_id;               
                $field = 'op.*,pm.mode_name';
                $join = 'zm_payment_mode AS pm ON pm.mode_id = op.payment_mode';
                $where1 = $this->buildWhere('op.').' and payment_id = '.$payment_id;
                $where2 = 'op.agent_id = '.$this->agent_id.' and op.tid = '.$this->tid.' and payment_id = '.$payment_id;
                //商家获取收款信息
                $info = $OP->alias('op')->field($field)->where($where1)->join($join)->find();
                if($info){
                    $info['type'] = 1;
                    $this->info = $info;
                }else{
                    //客户方获取付款信息
                    $info = $OP->alias('op')->field($field)->where($where2)->join($join)->find($payment_id);
                    if($info){
                        $info['type'] = 2;
                        $this->info = $info;
                    }
                }
                if(!$info){
                    $this->error(L('error_msg_059'));
                }
                $this->receivablesList = $SOR->getOrderReceivables($this->info['order_id']);
            }else{
                //如果有订单ID发过来
                if($order_id){
                    $this->order_sn = $SO->getOrderField('order_sn',$order_id);
                    $this->receivablesList = $SOR->getOrderReceivables($order_id);                    
                }
            }
            $this->display('Manage/Default/Order/orderPay');
        }
    }


    //分销商订单列表
    public function traderOrderList(){

        $page_id                = I('page_id',1);
        $page_size              = getPageSize(I('page_size'));
        $certificate_number     = I('certificate_number','');
        $order_sn               = I('order_sn','');
       
        $order_time_begin       = strtotime(I('order_time_begin', 0));
        $order_time_end         = strtotime(I('order_time_end', 0));
        $lanmu_status           = trim(I('lanmu_status', 'kehusearch'));
   
        $where['is_yiji_caigou'] = array('eq', 0);
        $where['is_erji_caigou'] = array('eq', 0);
        if($order_sn){
            $where['order_sn']           = array('like', '%'.$order_sn.'%');
        }

        if($certificate_number){
            $ogwhere['certificate_no'] = array('like', '%'.$certificate_number.'%');
        }else{
            $ogwhere['attribute'] = array('like', '%;s:9:"supply_id";s:'.strlen($this->agent_id).':"'.$this->agent_id.'";%');
        }
        
        $og = M('order_goods') ->distinct(true)->where($ogwhere)->field(array('order_id', 'attribute', 'fahuo_time', 'have_goods'))->select();

       
        $tempArr = array();
        if($og){
            foreach($og as $key=>$val){
                $goods_attr = unserialize($val['attribute']);
                if($goods_attr['supply_id'] != $this->agent_id){
                    continue;
                }

                // if($val['have_goods'] == 2){  //有货跳过
                //      continue;
                // }

                 // if($val['have_goods'] == 1 and $val['fahuo_time'] >0 and $val['to_supply'] == 1){  //有货，且发货时间>0
                 //     continue;
                 // }

                $tempArr[] = $val['order_id'];
            }
        }
        $tempArr = array_unique($tempArr) ;
        if($tempArr){
            $orderIds = implode(',',$tempArr);
            $where['order_id'] = array('in', $orderIds);
           
        }else{
            $where['order_id'] = 0;
        }
      
                      
        if($order_time_begin and  $order_time_end){  //开始订单时间大于结束订单时间，互相交换
            if($order_time_begin > $order_time_end){            
                $temp             = $order_time_begin;
                $order_time_begin = $order_time_end;
                $order_time_end   = $temp;
            }
            $where['create_time'] = array(array('lt', $order_time_end + 24*3600), array('gt', $order_time_begin ), 'and');            
        }elseif(empty($order_time_begin)  and  $order_time_end){
            $where['create_time'] = array('lt', $order_time_end + 24*3600);  
        }elseif($order_time_begin  and  empty($order_time_end)){
            $where['create_time'] = array('gt', $order_time_begin);
        }
               
        $where['order_status'] = array('eq', 0);           
        $O    = D('Order');     
        $data  = $O->getTraderOrderList($where,'order_id desc',$page_id,$page_size, $this->agent_id);       
        $this->echoJson($data);
    } 


    //确定分销商的客户订单，钻明的客户订单 是否有货，发货时间
    public function orderHaveGoodsAndSendDateAction($order_id = 0, $act='fenxiao'){
        if($act=="fenxiao"){  //分销商
            $SO  = M('order') ;
            $SOG = M('order_goods');
        }else{     //钻明
            $SO  = M('order', 'zm_', 'ZMALL_DB') ;
            $SOG = M('order_goods', 'zm_', 'ZMALL_DB');
        }
        
        $SO->startTrans();
        $order_status    = $SO->where('order_id ='. $order_id)->getField('order_status');  
        if($order_status != 0){
            $this->error(L('error_msg_007'));
        }
     
         /////证书货保存
         $goodsLuozuan = I('post.goodsLuozuan');
         foreach ($goodsLuozuan as $key => $value) {               
            $data['have_goods'] = $value['have_goods'];         
            $data['fahuo_time'] = strtotime($value['fahuo_time']);                                              
            $res = $SOG->where('og_id = '.$key)->save($data);
            if($res === false){$OG->rollback();$this->error(L('error_msg_008'));}
        }

         /////散货保存
         $goodsSanhuo = I('post.goodsSanhuo');
         foreach ($goodsSanhuo as $key => $value) {                     
            $data['have_goods'] = $value['have_goods'];
            $data['fahuo_time'] = strtotime($value['fahuo_time']);                                
            $res = $SOG->where('og_id = '.$key)->save($data);                
            if($res === false){$SOG->rollback();$this->error(L('error_msg_008'));}
        }

         /////成品保存
         $goodsConsignment = I('post.goodsConsignment');
         foreach ($goodsConsignment as $key => $value) {    
            
            $data['have_goods'] = $value['have_goods'];
            $data['fahuo_time'] = strtotime($value['fahuo_time']);  
            $res = $SOG->where('og_id = '.$key)->save($data);                
            if($res === false){$SOG->rollback();$this->error(L('error_msg_009'));}
        }

        
        if($res === false){
            $SO->rollback();
            $this->error(L('error_msg_011'));
            return false;
        }else{
            $SO->commit();                       
            $this->success(L('success_msg_004'));
            return true;
        }
    }

    //钻明订单列表
    public function ZMOrderList(){
        $page_id                = I('page_id',1);      
        $page_size              = getPageSize(I('page_size'));
        $certificate_number     = I('certificate_number','');
        $order_sn               = I('order_sn','');
       
        $order_time_begin       = strtotime(I('order_time_begin', 0));
        $order_time_end         = strtotime(I('order_time_end', 0));
        $lanmu_status           = 'treatment';
		$order_type				= I('order_type');	
		$where ="   and o.agent_id = 0 ";
        //$where['agent_id']               = array('eq', 0);  //必需是钻明客户下的订单，不能是分销商的采购单
        if($order_sn){
			$where .= "o.order_sn like '%$order_sn' and ";
            //$where['order_sn']           = array('like', '%'.$order_sn.'%');
        }

        //start  判断商品里面的信息是否符合
        //这一快的SQL需要优化
        if($certificate_number){  //有商品号
            $ogwhere['certificate_no'] = array('like', '%'.$certificate_number.'%');
        }
        $ogwhere['attribute'] = array('like', '%;s:9:"supply_id";s:'.strlen($this->agent_id).':"'.$this->agent_id.'";%');
              
        $og = M('order_goods', 'zm_', 'ZMALL_DB')->where($ogwhere)->field(array('order_id', 'attribute', 'have_goods', 'fahuo_time', 'to_supply'))->select();
       //($og);
        $tempArr = array();
        if($og){
            foreach($og as $key=>$val){
                $goods_attr = unserialize($val['attribute']);

                if($goods_attr['supply_id'] != $this->agent_id){
                    continue;
                }
                if($val['have_goods'] == 2  ){ //无货跳过
                    continue;
                }
                if($val['have_goods'] == 1 and $val['fahuo_time'] > 0 and $val['to_supply'] == 1){//确定有货，但没有确定发货时间
                    continue;
                }
                $tempArr[] = $val['order_id'];
            }
        }
        $tempArr = array_unique($tempArr);
        
        if($tempArr){
            $orderIds = implode(',',$tempArr);
			$where .= " and o.order_id in ($orderIds)";
            //$where['order_id'] = array('in', $orderIds);
        }else{
			$where .= " and o.order_id = 0";
            //$where['order_id'] = 0;
        }
       
       //end  判断订单商品里面的信息是否符合要求

        if($order_time_begin and  $order_time_end){  //开始订单时间大于结束订单时间，互相交换
            if($order_time_begin > $order_time_end){            
                $temp             = $order_time_begin;
                $order_time_begin = $order_time_end;
                $order_time_end   = $temp;
            }
			$where .=' AND ( o.create_time <'.($order_time_end + 24*3600).' and o.create_time > '.$order_time_begin.' )';
            //$where['create_time'] = array(array('lt', $order_time_end + 24*3600), array('gt', $order_time_begin ), 'and');            
        }elseif(empty($order_time_begin)  and  $order_time_end){
			$where .=' AND ( o.create_time <'.($order_time_end + 24*3600).')';
           // $where['create_time'] = array('lt', $order_time_end + 24*3600);  
        }elseif($order_time_begin  and  empty($order_time_end)){
			$where .=' AND ( o.create_time >'.$order_time_begin.')';
            //$where['create_time'] = array('gt', $order_time_begin);
        }
		
		
		
		
		//订单类型
		if($order_type == '6'){//所有客户订单               
			$where .=' and o.uid <> 0 ';
		}else if($order_type == '7'){//采购单
			$where .=' and o.tid <> 0 ';
		}else if($order_type == '1'){//GIA订单
			$where .=" and og.goods_type = '1'";
		}else if($order_type == '2'){//散货订单
			$where .=" and og.goods_type = '2'";
		}else if($order_type == '3' ){//成品订单，原先3,6都是成品商品
			$where .=" and (og.goods_type = '3' or og.goods_type = '6' or og.goods_type = '3,6'  or og.goods_type = '6,3')";
		}else if($order_type == '4'){//订制商品订单，5,4都是订制商品
			$where .=" and (og.goods_type = '4' or og.goods_type = '5' or og.goods_type = '4,5'  or og.goods_type = '5,4')";
		}else if($order_type == '5'){//混合订单
			$where .=" and (og.goods_type != '1' and og.goods_type != '2' and og.goods_type != '3' og.and goods_type != '4' and og.goods_type != '5' and og.goods_type != '6' and og.goods_type != '3,6'  and og.goods_type != '6,3' and og.goods_type != '4,5'  and og.goods_type != '5,4')";
		}else{
			$where .=" ";
		}
		
		
        $where .=' and o.order_status = 0 ';  
        //$where['order_status'] = array('eq', 0);  //订单状态是0 开可以
       
        $O    = D('Order');

        //$data  = $O->getZMOrderList($where,$alias_where,'order_id desc',$page_id,$page_size, $this->agent_id);
		$data  = $O->getZMOrderList($where,'order_id desc',$page_id,$page_size, $this->agent_id);
		$_SESSION['Order_List_Wait_total'] =$data['total'];
		
        $SO    = D('SupplyOrder');      
        $wheres['order_status'] = array(array('lt', 6), array('gt', 1), 'and');		
        $Waiting_total  = $SO->getOrderList($wheres,'order_id desc',$page_id,$page_size, $this->agent_id,'2');
		$wheres['order_status'] = array(array('lt', 2), array('gt', -1), 'and');		
        $Waitpay_total  = $SO->getOrderList($wheres,'order_id desc',$page_id,$page_size, $this->agent_id,'2');
		$_SESSION['Order_List_Waiting_total'] =$Waiting_total;
		$_SESSION['Order_List_Waitpay_total'] =$Waitpay_total;
 
        $this->echoJson($data);
    } 
    

    

}
