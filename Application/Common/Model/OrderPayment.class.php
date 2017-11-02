<?php
/* *
 * 类名：OrderPayment
 * 功能：订单支付封装类
 * 详细：订单付款以及日志等处理
 * 日期：2016-09-22
 * 说明：
 * 。
 */

namespace Common\Model;

class OrderPayment {

	public	$error = '';		//错误信息描叙
	private $db;		//付款单数据库对象
	private	$dblog;		//订单操作日志对象

	//初始化配置信息
	function __construct(){
		$this->db = M('order_payment');
		$this->dblog = M('order_log');
	}

	/**
	* 说明： 传入相应付款信息处理
	**/
	function pay($data , $msg=''){
		if(!$this->db->add($data)){
			return false;	//付款失败
		}		
		$this->orderLog($data['order_id'], $msg);
		//支付成功
		return true;
	}

	/**
	* 说明： 记录相应日志
	**/
	function orderLog($order_id, $msg, $uid=0){
		$arr['order_id'] = $order_id;
		$arr['group_id'] = 0;
		$arr['user_id'] = $uid;
		$arr['create_time'] = time();
		$arr['agent_id'] = C('agent_id');
		$arr['note'] = $msg;
		if($this->dblog->add($arr)){
			return true;
		}else{
			return false;	//日志记录成功
		}	

	}


}
?>