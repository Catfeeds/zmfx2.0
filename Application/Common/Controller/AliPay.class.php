<?php
/**
 * 支付宝支付类
 * 所有支付宝支付的功能集成在这个类里
 * @todo 只是这样规划
 * @author adcbguo
 */
namespace Common\Controller;
class AliPay {
	
	private $orderId;//订单id
	private $config;//银联支付配置
	
	//初始化配置，获取订单信息，获取配置信息
	public function __construct($orderId){
		dump('aaa');
	}
	
	//对外部支付方法
	public function pay(){
		
	}
}