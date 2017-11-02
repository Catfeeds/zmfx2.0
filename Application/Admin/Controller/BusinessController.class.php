<?php
/**
 * 财务操作模块
 * @author 钻明郭冠常
 */
namespace Admin\Controller;
use Think\Page;
use Think\Auth;
class BusinessController extends AdminController {
	
    // 模块跳转
    public function index(){
        foreach ($this->AuthListS AS $k){
            $val = stristr($k,strtolower(MODULE_NAME.'/'.CONTROLLER_NAME));
            if($val and strtolower($val) != strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME)){
            	$this->redirect($val);
            }
        }
        $this->log('error', 'A1','',U('Admin/Public/login'));
    }
	
	// 应收款记录列表
	public function receivablesList($p=1,$n=13,$order_id=0,$pay_start=0,$pay_end=0){
		$OR = M('order_receivables');
		$where = $this->buildWhere('oy.');
		//订单编号查询
		if(I('get.order_sn')!=''){
			//$order_id = $this->getOrderId(I('get.order_sn'));
			$this->order_sn = I('get.order_sn');			
			$order_ids = $this->returnGoodsid($this->order_sn);					
			$where .= " and oy.order_id in (".$order_ids.")";
		}
		//订单点击过来和上面订单
		if($order_id){
			$where .= ' and oy.order_id = '.$order_id;
		}
		//支付时间
		if(is_numeric(substr($pay_start, 0, 3))){
			$pay_start_str=strtotime($pay_start);
			$pay_end_str=strtotime($pay_end);
			if($pay_start_str){
				if($pay_start_str==$pay_end_str){
					$pay_end_str=$pay_start_str +60*60*24;
				}
				$where .= ' and oy.payment_time >'.$pay_start_str.' and oy.payment_time < '.$pay_end_str;
			}
		}
		//状态
		if(I('get.receivables_status')){
			$where .= ' and oy.receivables_status = '.I('get.receivables_status');
			$this->receivables_status = I('get.receivables_status');
		}
		//获取订单编号的最后
		$field = 'oy.*,o.order_sn';
		$join = ' LEFT JOIN zm_order AS o ON o.order_id = oy.order_id';
		$count = $OR->alias('oy')->where($where)->count();
		$Page = new Page($count,$n);
		$list = $OR->alias('oy')->field($field)->where($where)->join($join)->order('oy.create_time DESC,oy.receivables_id DESC')->limit($Page->firstRow,$Page->listRows)->select();
		$this->list = $list;
		$receivables_ids= $OR->alias('oy')->field('receivables_id')->where($where)->join($join)->order('oy.create_time DESC,oy.receivables_id DESC')->select();
		if($receivables_ids){
			foreach($receivables_ids as $v){
				$yingshou_ids[]=$v['receivables_id'];
			}
			$this->yingshou_null=implode(',',$yingshou_ids);
		}
		$this->pay_start=$pay_start;
		$this->pay_end=$pay_end;
		$this->page = $Page->show();
	    $this->display();
	}
	
	// 应付信息
	public function receivablesInfo($receivables_id){
		//先获取应支付信息
		$OY = M('order_receivables');
		$where = $this->buildWhere('ay.').' and ay.receivables_id = '.I('get.receivables_id');
		$field = 'ay.*,o.order_sn';
		$join = 'zm_order As o ON o.order_id = ay.order_id';
		$info = $OY->alias('ay')->field($field)->join($join)->where($where)->find();
		if(IS_POST){
			$data = I('post.');
			//支付部分
			if($data['payment_price'] > 0){
				$data['receivables_status'] = 2;
			}
			//完成支付
			if($data['payment_price'] == $info['receivables_price']){
				$data['receivables_status'] = 3;
			}
			//支付金额不能大于应付金额
			if($data['payment_price'] > $info['receivables_price']){
				$this->log('error', 'A40');
			}
			//重新改成未支付
			if($data['payment_price'] == 0){
				$data['receivables_status'] = 1;
			}
			if($OY->alias('ay')->where($where)->save($data)){//完成应付款消息修改
				$this->log('success', 'A41','',U('Admin/Business/receivablesList'));
			}else{//修改错误
				$this->log('error', 'A42');
			}
		}else{
			$this->info = $info;
			$this->display();
		}
	}
	
	// 支付列表
	public function payment($p=1,$n=13,$order_id=0,$receipt_time=0,$receipt_status=3){
		$OP = M('order_payment');
		$where = $this->buildWhere('op.');
		//订单编号查询
		if(I('get.order_sn')!= ''){
			//$order_id = $this->getOrderId(I('get.order_sn'));
			$this->order_sn = I('get.order_sn');			
			$order_ids = $this->returnGoodsid($this->order_sn);	
			$where .= " and op.order_id in (".$order_ids.")";
		}
		//订单点击过来和上面订单
		if($order_id){
			$where .= ' and order_id = '.$order_id;
		}
		//收款状态
		$this->receipt_status=$receipt_status;
		if($receipt_status == 0){
			$where .= ' and payment_status = 0 ';
		}else if($receipt_status == 1){
			$where .= ' and payment_status = 1 ';
		}else if($receipt_status == 2){
			$where .= ' and payment_status = 2 ';
		}else if($receipt_status == -1){
			$where .= ' and payment_status = -1 ';
		}
		//收款时间
		$time=strtotime($receipt_time);
		if($time){
			$this->receipt_time=$receipt_time;
			$time1=$time + 60*60*24;
			$where .= ' and create_time < '.$time1.' and create_time = '.$time;
		}
		//查询支付模式
		$field = 'op.*,pm.mode_name';
		$join = 'zm_payment_mode AS pm ON pm.mode_id = op.payment_mode';
		$count = $OP->alias('op')->where($where)->count();
		$Page = new Page($count,$n);
		$list = $OP->alias('op')->field($field)->join($join)
			->where($where)->limit($Page->firstRow,$Page->listRows)->order('payment_id desc')->select();
			
		$this->list = $list;
		$this->page = $Page->show();
		$this->display();
	}
	
	/**
	 * 核销应收款项
	 * @param obj $Obj
	 * @param int $order_id
	 * @param int $price 
	 * @param int $type
	 * @param int $objId
	 * @return boolean
	 */
	protected function verificationReceivables($Obj,$order_id,$price,$type=1,$objId){
		$verification_price = 0;//实际核销金额
		//查询处理应收记录
		$OY = M('order_receivables');
		$where = 'agent_id = '.C('agent_id').' and order_id = '.$order_id.' and receivables_status <> 3 and period_type in(1,2,3,4,5,11,12)';
		$list = $OY->where($where)->order('period_current')->select();
		//循环扣款
		foreach ($list as $key => $value) {
			if(round($price) > 0){//每次循环支付的钱还有余额
				$priceDo  = formatPrice(formatPrice($value['receivables_price']) - formatPrice($value['payment_price']));
				if($price >= $priceDo){//支付的钱大于等于应付，那么完成一条应付记录
					$verification_price += $priceDo;
					$add['payment_price'] = formatPrice($value['payment_price'] + $priceDo);
					$add['receivables_status'] = 3;
					if(!$OY->where('agent_id = '.C('agent_id').' and receivables_id = '.$value['receivables_id'])->save($add)){
						$Obj->rollback();
						$this->error('核销应收款项失败,不能修改收款状态!');
					}
					$price = formatPrice($price - $priceDo);//支付钱-应付的钱，余到下一次循
				}else{//支付的钱不够的话，完成部分支付
					$verification_price += $price;
					$add['payment_price'] = formatPrice($value['payment_price']) + formatPrice($price);
					$add['receivables_status'] = 2;
					if(!$OY->where('agent_id = '.C('agent_id').' and receivables_id = '.$value['receivables_id'])->save($add)){
						$Obj->rollback();
						$this->error('核销应收款项失败,不能修改收款状态!');
					}
					$price = 0;
					break;
				}
			}
		}
		//有多余金额自动截余款项
		if(round($price) > 0){
			if($type == 1){
				$info = $Obj->find($objId);
				if(round($info['discount_price']) > 0){
					$Obj->rollback();
					$this->error('当前入款有截余，不允许有入款折扣');
				}
			}elseif ($type == 2){
				$this->error('支付的款项不能大于应收的款项');
				$Obj->rollback();
			}
			$data['more_price'] = $price;
		}
		$data['verification_price'] = $verification_price;
		return $data;
	}
	
	// 审核支付信息
	public function checkPayment($payment_id){
		$OP = M('order_payment');
		$OP->startTrans();
		$info = $OP->where('agent_id = '.C('agent_id'))->find($payment_id);
		//收款时间
		//折扣也参与金额审核
		$price = formatPrice($info['payment_price']+$info['discount_price']);
		$resData = $this->verificationReceivables($OP, $info['order_id'], $price,1,$payment_id);
		if($resData){
			//改变收款记录状态
			$data['payment_status'] = 2;
			$data['more_price'] = round($resData['more_price'],2);
			$data['verification_price'] = round($resData['verification_price'],2);
			if(round($data['verification_price']) == 0){
				$OP->rollback();
				$this->error('请检查当前这笔款项是否是重复款项!');
			}
			$where = $this->buildWhere().' and payment_id = '.$payment_id;
			$res = $OP->where($where)->save($data);
			if($res){
				$res = $this->autoConfirmPay($info['order_id']);
				$OP->commit();
				$notes = '财务对入款ID:'.$payment_id.'进行了审核';
				$this->orderLog($info['order_id'], $notes, L('A39'),U('Admin/Business/payment'));
			}else{
				$OP->rollback();
				$this->log('error', 'A1');
			}
		}else{
			$OP->rollback();
			$this->error('核销应收款项失败!');
		}
	}
	
	/**
	 * 自动确认付款
	 * @param unknown $order_id
	 * @return unknown
	 */
	protected function autoConfirmPay($order_id){
		$url   = 'Admin/Order/confirmPay';
		if(!$this->checkActionAuth($url)) return false;
		$OR = M('order_receivables');
		$O = M('order');
		$field = 'oy.receivables_price,oy.payment_price,oy.receivables_status';
		$array = $OR->alias('oy')->field($field)->where('oy.agent_id = '.C('agent_id').' and oy.order_id = '.$order_id.' and oy.period_current = 1')->select();
		$isConfirmPay = 1;
		foreach ($array as $key => $value) {
			if($value['receivables_status'] != 3){
				$isConfirmPay = 0;
			}
			if($value['receivables_status'] == $value['payment_price']){
				$isConfirmPay = 0;
			}
		}
		if($isConfirmPay == 1){
			$data['order_status'] = 2;
			$data['order_payment'] = 1;
			$data['update_time'] = time();
			$res = $O->where(' agent_id = '.C('agent_id').' and order_status = 1 and order_id = '.$order_id)->save($data);
			return $res;
		}else{
			return false;
		}
	}

	/**
	 * 订单操作(手工入款)
	 * @param number $order_id
	 * @param number $payment_id
	 */
	public function payMoney($order_id=0,$payment_id=0){
		if(IS_POST){
			$url = MODULE_NAME . '/Order/paymentAction';
			if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
			$info = $this->getOrderInfo(I('post.order_sn'),'',true);
			if($info){
			    //修改收款验证状态
				$OP = M('order_payment');
				if($payment_id){
					$payInfo = $OP->where('agent_id = '.C('agent_id'))->find($payment_id);
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
				$Auth = new Auth();
				if(!$Auth->check('Admin/Business/discount', $this->uid) and ($this->is_superManage!=1)){
					unset($data['discount_price']);
				}
				//收款金额和收款折扣必须有一个
				if((empty($data['discount_price']) and empty($data['payment_price'])) or empty($data['create_time']) or empty($data['payment_mode'])){
					$this->log('error', 'A36');
				}
				//组装数据
				$data = array_merge($data,$this->buildData());				
				$data['order_id'] = $info['order_id'];
				$data['uid'] = $info['uid'];
				$data['tid'] = $info['tid'];
				$data['parent_id'] =  $data['parent_id']?$data['parent_id']:0;
				$data['discount_price'] = round($data['discount_price']?$data['discount_price']:0,2);//折扣
				$data['payment_price'] = round($data['payment_price'],2);
				$data['create_time'] = strtotime($data['create_time']);//把页面时间转化为时间戳
				$data['payment_note'] = $data['note'];unset($data['note']);
				$data['payment_status'] = 1;//手工入款自动确认支付				
				$data['agent_id'] = C('agent_id');	
				
				if($payment_id){
					$res = $OP->where('agent_id = '.C('agent_id').' and payment_id = '.$payment_id)->save($data);
					if($res!==false){
						$this->log('success', 'A86','ID:'.$payment_id, U('Admin/Business/payment'));
					}else{
						$this->log('error', 'A87');
					}
				}else{
					if($OP->add($data)){
						$msg = '手工入款￥：'.$data['payment_price'].'折扣￥：'.($data['discount_price']?$data['discount_price']:0).'入款ID:'.$res;
						$this->orderLog($info['order_id'], $msg, L('A38'), U('Admin/Business/payment'));
					}else{
						$this->log('error','A84');
					}
				}
			}else{
				$this->log('error','A27' );
			}
		}else{
			//是否有折扣权限
			$Auth = new Auth();
			if($Auth->check('Admin/Business/discount', $this->uid)  or ($this->is_superManage==1)){
				$this->discount = 1;
			}
			//获取手工入款方式
			$PM = M('payment_mode');
			$this->payModeList = $PM->where($this->buildWhere())->select();
			//支付收款id
			if($payment_id){
				$this->payment_id = $payment_id;
				$OP = M('order_payment');
				$field = 'op.*,pm.mode_name';
				$join = 'zm_payment_mode AS pm ON pm.mode_id = op.payment_mode';
				$where1 = $this->buildWhere('op.').' and payment_id = '.$payment_id;
				$where2 = 'op.agent_id = '.C('agent_id').' and op.tid = '.$this->tid.' and payment_id = '.$payment_id;
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
				    $this->error('没有当前收款单!');
				}
				$this->receivablesList = $this->GetOrderReceivables($this->info['order_id']);
			}else{
				//如果有订单ID发过来
				if($order_id){
					$this->order_sn = $this->getOrderSn($order_id);
					$this->receivablesList = $this->GetOrderReceivables($order_id);
				}
			}
			$this->display();
		}
	}
	
	/**
	 *  取消收款记录
	 *  取消审核
	 * @param int $payment_id
	 */
	public function quxiaoPayment($payment_id){
	   $OP = M('order_payment');
	   $where = $this->buildWhere();
	   $info = $OP->where($where.' and payment_id = '.$payment_id)->find();
	   if($info['payment_status'] != 2){
	       $data['payment_status'] = '-1';
	       $res = $OP->where($where.' and payment_id = '.$payment_id)->save($data);
	       if($res){
	           $notes = '财务作废了收款单ID:'.$payment_id;
	           $this->orderLog($info['order_id'], $notes, '收款单已经作废');
	       }else{
	           $this->error('收款单已经作废失败');
	       }
	   }else{
	       $this->error('这张收款单已经审核，不能作废处理');
	   }
	}
	
	/**
	 * 获取订单应付信息
	 * @param int $order_id
	 */
	protected function GetOrderReceivables($order_id){
		$OY = M('order_receivables');
		$list = $OY->where('agent_id = '.C('agent_id').' and order_id = '.$order_id.' and period_type in(1,2,3,4,5,6,11,12)')->order('period_current')->select();
		//计算总金额，首期金额和尾款
		$price='';$price1 = '';$price2 = '';
		foreach ($list as $key => $value) {
			if($value['period_current'] == 1){
				$price1 += $value['receivables_price'];
			}else{
				$price2 += $value['receivables_price'];
			}
			$price += $value['receivables_price'];
		}
		$this->total = $price;
		$this->firstPhase = $price1?$price1:'0.00';
		$this->balanceDue = $price2?$price2:'0.00';
		return $list;
	}
	
	/**
	 * 应退款记录
	 */
	public function compensateList($p=1,$n=13,$order_sn='',$compensate_status='0',$refund_time=0,$period_type=0){
		$OC = M('order_compensate');
		//筛选(郭冠常)
		$where = $this->buildWhere('oc.');
		if($order_sn!=''){
		    //$where .= ' and oc.order_id = '.$this->getOrderId($order_sn);
		    $this->order_sn = I('get.order_sn');
		    $order_ids = $this->returnGoodsid($this->order_sn);			
			$where .= " and oc.order_id in (".$order_ids.")";
		}
		if($compensate_status){
		    $where .= ' and oc.compensate_status = '.I('get.compensate_status');
		    $this->compensate_status = $compensate_status;
		}
		//退款类型
		if($period_type==21){
			$where .=' and period_type = 21 ';
		}else if($period_type == 12){
			$where .=' and period_type = 12 ';
		}
		$this->period_type = $period_type;
		//退款时间
		$time=strtotime($refund_time);
		if($time){
			$this->time=$refund_time;
			$time1=$time +60*60*24;
			$where.=' and oc.create_time > '.$time.' and oc.create_time < '.$time1;
		}
		//查询数据
		$join[] = 'JOIN zm_order AS o ON o.order_id = oc.order_id';
		$field = 'o.order_sn,oc.*';
		//分页
		$count = $OC->alias('oc')->where($where)->join($join)->count();
		$Page = new Page($count,$n);
		$this->page = $Page->show();
		$list = $OC->alias('oc')->join($join)->where($where)->field($field)->limit($Page->firstRow,$Page->listRows)->order('compensate_id DESC')->select();
		foreach ($list as $key => $value) {
			$array[] = $value;
		}
		$this->list = $array;
		$this->display();
	}
	
	/**
	 * 退款记录
	 */
	public function refundList($p=1,$n=13,$order_sn='',$refund_time=0){
        $OR = M('order_refund');
		$where = " refund_status <> '-1'";
		//退款时间
		$time=strtotime($refund_time);
		if($time){
			$this->time=$refund_time;
			$time1=$time +60*60*24;
			$where.=' and create_time > '.$time.' and create_time < '.$time1;
		}
        if($order_sn!=''){
            //$order_id = $this->getOrderId($order_sn);
            //if(!$order_id){$this->error('没有这张订单!');}
            ///$where .= ' and order_id = '.$order_id;
            $this->order_sn = I('get.order_sn');
            $order_ids = $this->returnGoodsid($this->order_sn);
			$where .= " and order_id in (".$order_ids.")";
        }
        //分页
        $count = $OR->where(" agent_id = ".C('agent_id')." and ".$where)->count();
        $Page = new Page($count,$n);
        $this->page = $Page->show();
        //查询数据
        $join = 'zm_payment_mode AS pm ON pm.mode_id = zor.refund_mode';
        $field = 'zor.*,pm.mode_name';
        $list = $OR->alias('zor')->field($field)->where(" zor.agent_id = ".C('agent_id')." and ".$where)->join($join)->limit($Page->firstRow,$Page->listRows)->order('create_time DESC')->select();
		$this->list = $list;
        $this->display();
	}
	
	/**
	 * 手工退款
	 * @param int $refund_id
	 */
	public function refundInfo($refund_id='0',$order_id=''){
	    if(IS_POST){ 
	        $info = $this->getOrderInfo(I('post.order_sn'),'',true);
	        if($info){
	            //凭证上传
	            header("Content-Type: text/html; charset=UTF-8");
	            $upload = new \Think\Upload (); // 实例化上传类
	            $upload->maxSize = 3145728; // 设置附件上传大小
	            $upload->exts = array ('jpg','gif','png','jpeg' ); // 设置附件上传类型
	            $upload->savePath = './Uploads/pay/'; // 设置附件上传目录
	            $Finfo = $upload->uploadOne ($_FILES ['template_img'] ); // 上传文件
	            $data = I('POST.');
	            $data['refund_voucher'] = $Finfo ['savepath'] . $Finfo ['savename'];
	            if(empty($data['refund_price']) or empty($data['create_time']) or empty($data['refund_mode'])){
	                $this->log('error', 'A36');
	            }
	            $data = array_merge($data,$this->buildData());
	            $data['order_id'] = $info['order_id'];
	            $data['uid'] = $info['uid'];
	            $data['tid'] = $info['tid'];
	            $data['refund_status'] = 1;
	            $data['create_time'] = strtotime($data['create_time']);
				$data['agent_id'] = C('agent_id');
	            //实例化退款数据表
	            $OR = M('order_refund');
	            $res = $OR->add($data);
	            if($res){
	                $msg = '手工退款￥：'.$data['refund_price'].'退款ID:'.$res;
	                $this->orderLog($info['order_id'], $msg, '退款成功!', U('Admin/Business/refundList'));
	            }else{
	                $this->error('退款失败');
	            }
	        }else{
	            $this->log('error','A27' );
	        }
	    }else{
	        //退款id
	        if($refund_id){
	            $OR = M('order_refund');
	            $this->refund_id = $refund_id;
	            $OP = M('order_payment');
	            $field = 'zor.*,pm.mode_name';
	            $join = 'zm_payment_mode AS pm ON pm.mode_id = zor.refund_mode';
	            $this->info = $OR->alias('zor')->where('zor.agent_id = '.C('agent_id'))->field($field)->join($join)->find($refund_id);
	        }
	        //如果有订单ID发过来
	        if($order_id){
	            $this->order_sn = $this->getOrderSn($order_id);
	        }
	        //获取手工入款方式
	        $PM = M('payment_mode');
	        $this->payModeList = $PM->where($this->buildWhere(''))->select();
	        $this->display();
	    }
	}
	
	/**
	 * 退款操作（审核和作废）
	 * @param int $refund_id
	 */
	public function refundAction($refund_id,$type='0'){
	    //获取退款单信息
	    $where = $this->buildWhere().' and refund_id = '.$refund_id;
	    $OR = M('order_refund');
	    $info = $OR->where($where)->find();
	    if($type == 1){//审核
	        $price = $info['refund_price'];
	        $res = $this->checkRefund($price, $info['order_id']);
	        if($res){
	            $data['create_time'] = time();
	            $data['refund_status'] = 2;
	            $res1 = $OR->where($where)->save($data);
	            if($res1){
	                $notes = '操作审核退款成功!退款ID:'.$refund_id;
	                $msg = '退款审核成功!';
	                $this->orderLog($info['order_id'], $notes, $msg ,U('Admin/Business/refundList'));
	            }else{
	                $this->error('核销退款失败！');
	            }
	        }else{
	            $this->error('核销退款失败！');
	        }
	    }elseif ($type == 2){//作废
	        if($info['refund_status'] == 1){
	            $data['create_time'] = time();
	            $data['refund_status'] = '-1';
	            $res1 = $OR->where($where)->save($data);
	            if($res1){
	                $notes = '操作作废退款成功!退款ID:'.$refund_id;
	                $msg = '作废退款成功!';
	                $this->orderLog($info['order_id'], $notes, $msg ,U('Admin/Business/refundList'));
	            }else{
	                $this->error('作废退款失败！');
	            }
	        }else{
	            $this->error('已审核退款信息不能作废!');
	        }
	    }
	}
	
	/**
	 * 核销退款，核销到应退款去<br>
	 * 先退差价，在退货款
	 * @param double $price
	 * @param int $order_id
	 */
	protected function checkRefund($price,$order_id){
	    $where = $this->buildWhere('oc.').' and oc.order_id = '.$order_id." and oc.compensate_status <> '3'";
	    $OC = M('order_compensate');
	    $OC->startTrans();
	    $field = 'oc.compensate_id,oc.compensate_price,oc.payment_price,oc.compensate_status,oc.period_type';
	    $list = $OC->alias('oc')->where($where)->order('period_type,create_time')->field($field)->select();
	    if(!$list){
	        $this->error('这张订单：'.$this->getOrderSn($order_id).'没有要退款的应退款记录!');
	    }
	    //循环核销款项
	    foreach ($list as $key => $value) {
	        //待核销款项
	        $dPrice = formatPrice($value['compensate_price'] - $value['payment_price']);
	        //退款的钱大于等于当前待核销的款项,那就核销一笔
	        if(round($price,2) >= round($dPrice,2)){
	            $data[$key]['compensate_id'] = $value['compensate_id'];
	            $data[$key]['payment_price'] = $value['compensate_price'];
	            $data[$key]['compensate_status'] = 3;
	        }
	        //退款的钱小于,那就核销部分
	        if(round($price,2) < round($dPrice,2)){
	            $data[$key]['compensate_id'] = $value['compensate_id'];
	            $data[$key]['payment_price'] = $price;
	            $data[$key]['compensate_status'] = 2;
	        }
	        //核销后的余款,不大于0退出循环
	        $price = $price - $dPrice;
	        if(round($price,2) <= 0){break;}
	    }
	    //核销到最后退款不能大于0,大于0表示不要退那么多款项，需要作废从新添加一条记录
	    if(round($price,2) > 0){
	        $this->error('退款金额不能大于应退款的金额,你都不要退那么多钱!');
	    }
	    //实际修改数据库记录
	    foreach ($data as $key => $value) {
	        $res = $OC->save($value);
	        if(!$res){
	            $OC->rollback();
	            $this->error('修改应退款记录出错!');
	        }
	    }
	    return $OC->commit();
	}
	
	/**
	 * 核销应退款
	 * @param int $compensate_id
	 */
	public function checkRefundInfo($compensate_id){
		//退款的款项
		$OR = M('order_compensate');
		$OR->startTrans();
		$where = $this->buildWhere().' and compensate_id = '.$compensate_id;
		$info = $OR->where($where)->find();
		//需要核销应付款项的金额
		$price = formatPrice($info['compensate_price'] - $info['payment_price']);
		if($this->verificationReceivables($OR,$info['order_id'], $price,2,$compensate_id)){
			//改变收款记录状态,应收款核销掉了这边的应退款也是核销掉了
			$data['compensate_status'] = 3;
			$data['payment_price'] = $info['compensate_price'];
			if($OR->where($where)->save($data)){
				$res = $this->autoConfirmPay($info['order_id']);
				$OR->commit();
				$notes = '操作应退款核销到应付款项成功!核销金额:'.$price.';应退款记录ID:'.$info['compensate_id'];
				$msg = '当前退款记录已核销掉到应收款项!';
				$this->orderLog($info['order_id'], $notes, $msg);
			}else{
				$OR->rollback();
				$this->error('核销应收款项失败!');
			}
		}else{
			$OR->rollback();
			$this->error('核销应收款项失败!');
		}
	}
	
	//应付款列表
	public function accountpayablelist($p=1,$n=13,$pay=0){
		$OR = M('order_receivables');
		//if(!in_array(7, $this->group)){
		//	$this->error('应付款只能分销商查看');
		//}
		$where = 'oy.agent_id = '.get_parent_id();
		//订单编号查询
		if(I('get.order_sn')!=''){
			//$order_id = $this->getOrderId(I('get.order_sn'));
			$this->order_sn = I('get.order_sn');
			$order_ids = $this->returnGoodsid($this->order_sn);
			$where .= " and oy.order_id in (".$order_ids.")";
		}
		//订单点击过来和上面订单
		if($order_id){
			$where .= ' AND oy.order_id = '.$order_id;
		}
		//支付时间
		$pay_time=strtotime($pay);
		if($pay_time){
			$this->pay=$pay;
			$pay_time1=$pay_time +60*60*24;
			$where .= ' AND oy.payment_time >'.$pay_time.' and oy.payment_time < '.$pay_time1;
		}
		//状态
		if(I('get.receivables_status')){
			$where .= ' AND oy.receivables_status = '.I('get.receivables_status');
			$this->receivables_status = I('get.receivables_status');
		}
		$field = 'oy.*,o.order_sn';
		$join = 'zm_order AS o ON o.order_id = oy.order_id AND o.uid='.get_create_user_id();
		$count = $OR->alias('oy')->where($where)->join($join)->count();

		$Page = new Page($count,$n);
		$list = $OR->alias('oy')->field($field)
				->where($where)->join($join)
				->limit($Page->firstRow,$Page->listRows)->order('oy.receivables_id desc')->select();	
		$this->list = $list;
		$this->page = $Page->show();
		$this->display();
	}
	//付款记录
	public function apayablelistlist($p=1,$n=13,$order_id=0,$receipt_time=0,$receipt_status=3){
		$OP = M('order_payment');
		//if(!in_array(7, $this->group)){
		//	$this->error('应付款只能分销商查看');
		//}		
		$where = 'op.agent_id = '.get_parent_id();
		
		//订单编号查询
		if(I('get.order_sn')!=''){
			//$order_id = $this->getOrderId(I('get.order_sn'));
			$this->order_sn = I('get.order_sn');			
		    $order_id_array = M('order')->where("order_sn like '%".$order_sn."' and uid=".get_create_user_id()." and agent_id=".get_parent_id())->field('order_id')->select();	   		     
		}else{
			$order_id_array = M('order')->where("uid=".get_create_user_id()." and agent_id=".get_parent_id())->field('order_id')->select();  		    
		}

		$order_str = '0';
		foreach($order_id_array as $value){      
		      $order_str = $order_str . ','.$value['order_id'];
		}
		$order_ids = $order_str;
		$where .= " and op.order_id in (".$order_ids.")";   

		//订单点击过来和上面订单
		if($order_id){
			$where .= ' AND order_id = '.$order_id;
		}
		//收款状态
		$this->receipt_status=$receipt_status;
		if($receipt_status == 0){
			$where .= ' AND payment_status = 0';
		}else if($receipt_status == 1){
			$where .= ' AND payment_status = 1';
		}else if($receipt_status == 2){
			$where .= ' AND payment_status = 2';
		}
		//收款时间
		$time=strtotime($receipt_time);
		if($time){
			$this->receipt_time=$receipt_time;
			$time1=$time + 60*60*24;
			$where .= ' AND create_time < '.$time1.' and create_time = '.$time;
		}

		//查询支付模式
		$field = 'op.*,pm.mode_name';
		$join = 'zm_payment_mode AS pm ON pm.mode_id = op.payment_mode';
		$count = $OP->alias('op')->where($where)->count();
		$Page = new Page($count,$n);
		$list = $OP->alias('op')->field($field)->join($join)
			->where($where)->limit($Page->firstRow,$Page->listRows)->order('payment_id desc')->select();
		$this->list = $list;
		$this->page = $Page->show();
		$this->display();
	}

	
}