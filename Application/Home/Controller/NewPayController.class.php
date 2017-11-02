<?php
/**
 * 在线支付控制器
 */
namespace Home\Controller;
class NewPayController extends NewHomeController{
	
	//在线支付-支付宝订单请求
	public function online(){
		$order_id = I('order_id',0);
		if($order_id<=0){
			$this->error('传入订单ID错误');
		}
		$alipay = new \Common\Model\Alipay();
		$html_text = $alipay->request($order_id);
		echo $html_text;
		//dump($alipay);
	}

	//在线支付-支付宝订单回调提醒
	public function notify_url(){
		$alipay = new \Common\Model\Alipay();
		if($alipay->notify_url()){
			echo "success";
		}else{
			echo "fail";
		}
	}


	//在线支付-支付宝订单支付成功显示
	public function return_url(){
		$this->order_sn = $_POST['out_trade_no'];
        layout(false); // 临时关闭当前模板的布局功能
		$alipay = new \Common\Model\Alipay();
		$result = $alipay->return_url();
		if($result){		///显示成功页面
			$this->order_id = $alipay->order_id;
			$this->display('Order/pay_success');
		}else{				//显示失败页面
			$this->error = $alipay->error;
			$this->display('Order/pay_fail');
		}
	}
	
}