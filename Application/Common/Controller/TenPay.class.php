<?php
/**
 * 财付通支付类
 * 所有财付通支付的功能集成在这个类里
 * @todo 还没有完成
 * @author adcbguo
 */
namespace Common\Controller;
class TenPay {
	
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