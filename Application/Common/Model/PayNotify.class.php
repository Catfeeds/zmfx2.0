<?php
/* *
 * 类名：Alipay
 * 功能：内部针对支付进行的二次封装
 * 详细：初始化支付数据以及生成支付订单
 * 日期：2016-09-20
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
 */

namespace Common\Model;

class PayNotify{

 
	/**
	* 返回结果页面处理
	**/
	function ReturnPay($order,$wxdata,$mode_name){

		//支付宝支付方式获取
		$PM = M('payment_mode');
		$paymode = $PM->where("agent_id='".C('agent_id')."' and mode_name='$mode_name'")->getField('mode_id');
		//如果订单是未确定状态下
		if($order['order_status']==0){
			//$OPE = M('order_period');
			$OR = M('order_receivables');
			//更改货品数量和价格
			$OG = M('order_goods');
			for ($i = 1; $i <= 4; $i++) {
				$goodslist = $OG->where('agent_id = '.C('agent_id').' AND order_id = '.$order['order_id'] .' AND goods_type='.$i)->select();
				if(!$goodslist){		//如果货品不存在
					continue;
				}

				$order_goods_price = 0;	//同类产品累积价格形成应收
				foreach ($goodslist as $key => $value) {				
					$update['goods_price_up'] = $value['goods_price'];
					$update['goods_number_up'] = $value['goods_number'];
					$update['update_time'] = time();
					$res3 = $OG->where('agent_id = '.C('agent_id').' AND og_id = '.$value['og_id'])->save($update);

					$order_goods_price += $value['goods_price'];
				}

				/** 默认全款支付不产生分期信息
				//生成订单分期表
				$periodArr['order_id'] = $order['order_id'];
				$periodArr['period_type'] = 1;
				$periodArr['period_num'] = 1;
				$periodArr['period_overdue'] = 0;
				$periodArr['agent_id'] = C('agent_id');
				$OPE->add($periodArr);
				**/

				//生成应收款
				$add['order_id'] = $order['order_id'];
				$add['receivables_price'] = $order_goods_price;
				$add['create_time'] = time();
				$add['period_day'] = 30;
				$add['payment_time'] = time()+2592000;
				$add['period_current'] = 1;
				$add['period_type'] = 3;
				$add['parent_type'] = $order['parent_type'];
				$add['parent_id'] = $order['parent_id'];
				$add['uid'] = $order['uid'];
				$add['tid'] = $order['tid'];
				$add['agent_id'] = C('agent_id');
				$OR->add($add);
			}
			
			//未确定订单需要修改订单确认价格
			$order_data['order_price_up'] = $order['order_price'];
		}

		//组装数据
		$data = array();				
		$data['order_id'] = $order['order_id'];
		$data['order_sn'] = $wxdata['attach'];
		$data['uid'] = $order['uid'];
		$data['tid'] = $order['tid'];
		$data['parent_id'] =  0;
		$data['discount_price'] = 0;//折扣
        if($mode_name == '环迅支付'){
            $data['payment_price'] = $wxdata['total_fee'];				//收款金额
        }else{
            $data['payment_price'] = $wxdata['total_fee']/100;				//收款金额
        }

		$data['create_time'] = time();
		$data['payment_mode'] = intval($paymode);
		$data['payment_user'] =  $wxdata['mch_id'];
		$data['payment_note'] = $mode_name;
		$data['trade_no'] = $wxdata['transaction_id'];
		$data['payment_status'] = 1;									//手工入款自动确认支付				
		$data['agent_id'] = C('agent_id');

		//订单日志信息
		$msg = $mode_name.'￥：'.$data['payment_price'].' 折扣￥：0';
		//支付数据写入
		$OrderPayment = new \Common\Model\OrderPayment();
		if(!$OrderPayment->pay($data, $msg)){
			if($notity == 1){
				@error_log(serialize($data).  "\n", 3, $this->logdir . "/notify_error.log");
			}else{
				@error_log(serialize($data).  "\n", 3, $this->logdir . "/return_error.log");
			}			
			return false;	//内部处理失败
		}

		//更改订单状态
		$order_data['order_status']  = 2;
		$order_data['order_payment'] = 1;
		$order_data['update_time']   = time();
		$res 						 = M('order')->where(' agent_id = '.C('agent_id').' and order_id = '.$order['order_id'])->save($order_data);
		if($res){
				$phone          	 = M("User")->where('uid='.$order['uid'])->getField('phone');
				$s              	 = new \Common\Model\Sms();
				$s           		 -> sendSms($phone,'paymet_ok_send_user',C('agent_id'));
				$getParentIDByUID 	 = M('user')->where("uid='".$order['uid']."' and agent_id = ".C('agent_id'))->getField('parent_id');
				$phone            	 = M("admin_user")->where('user_id='.$getParentIDByUID)->getField('phone');
				$s           		 -> sendSms($phone,'paymet_ok_send_admin',C('agent_id'));
				return true;
		}
	}

}
?>