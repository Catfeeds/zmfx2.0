<?php
/**
 * 用户相关操作类
 */
namespace Home\Controller;
use Think\Page;
class UserController extends HomeController{
	public function _befoe_index(){
		$this->seo_title = "用户中心";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active = "User";
	}
	public function _before_userInfo(){
		$this->seo_title = "用户中心";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->catShow = 0;
    	$this->active = "User";
	}
	public function _before_userMessages(){
		$this->seo_title = "用户中心";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active = "User";
	}
	public function _before_orderList(){
		$this->seo_title = "用户订单列表";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->catShow = 0;
    	$this->active = "User";
	}
	public function _before_orderInfo(){
		$this->seo_title = "用户订单详情";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->catShow = 0;
    	$this->active = "User";
	}

	public function _before_traderAdd(){
		$this->seo_title = "申请加入分销商";
		$this->seo_keyword = "申请加入分销商";
		$this->seo_content = "申请加入分销商";
		$this->active = "User";
	}

    // 用户注册协议
    public function registration($aid=0) {
      $this->article = M("article")->find($_GET['aid']);
      $this->display();
    }
    // 删除用户地址
    public function deleteUserAdd($address_id=0) {
      if (IS_POST) {
        $result = array('error'=>'yes','msg'=>L('L9075'));
        $address_id = I('post.address_id', '', 'htmlspecialchars');
        $M = M('user_address');
        if ($M->delete($address_id)) {
          $result = array('error'=>'no','msg'=>L('L298'), 'backUrl'=>'shopAddress.html', 'address_id'=>$address_id);
        } else {
          $result = array('error'=>'yes','msg'=>L('L299'));
        }
        $this->ajaxReturn($result);
      }
    }
    // 设置默认地址
    public function setDefAddress() {
      if (IS_POST) {
        $result = array('error'=>'yes','msg'=>L('L9075'));
        $whereDate['address_id'] = I('post.address_id', '', 'htmlspecialchars');
        $whereDate['uid'] = $this->uId;
        $noDef['address_id'] = array('NEQ', $whereDate['address_id']);
        $noDef['uid'] = array('EQ', $this->uId);
        $S = M('user_address');
        if (($S->where($whereDate)->save(array('is_default'=>1)) !== false)
          && ($S->where($noDef)->save(array('is_default'=>2)) !== false)) {
          $result = array('error'=>'no','msg'=>L('L9076'), 'address_id'=>$whereDate['address_id']);
        }
      }
      $this->sendRepsone($result);
    }

	/*
	*	我的订单，新版B2C
	*	zhy
	*	2016年7月12日 09:56:59
	*/

	public function olist(){
     	if (IS_POST) {
			$Order        = M('order');
			$order_status = I('order_status',-2);
			$is_where        = "  zm_order.uid ='".$_SESSION['web']['uid']."'";
			$is_where .= " and zm_order.is_yiji_caigou < 1 and zm_order.is_erji_caigou = 0";				//客户订单
			if($order_status != -2 ){
				$is_where .= " and zm_order.agent_id='".C('agent_id')."' and order_status in ( ".$order_status.')';
			}else{
				$is_where .= " and zm_order.agent_id='".C('agent_id')."' and zm_order.order_status >=-1";
			}
			$count = $Order->where($is_where)->count();
			$n = 4;
			$p = I('post.page');
			$page = NEW \Think\AjaxPage($count,$n,"show_list");
			$page->firstRow.','.$page->listRows;
			$limit = $page->firstRow.','.$page->listRows;
			$order_ids = M('order')->field('order_id,order_status')->where($is_where)->limit($limit)->order('create_time DESC')->select();
			//echo  M("order")->getLastSql();exit();
			if(!empty($order_ids)){
				foreach ($order_ids as  $v){
					$dataList[] =  M('order')->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.order_id =" .$v['order_id'])->order('zm_order_goods.dingzhi_id DESC')->select();
				}


				foreach($dataList as $key=>$val){
					foreach($val as $b=>$v){
						 switch($v['order_status']){
							case 0:  $val[$b]['status_txt'] = "待确认";
							break;
							case 1:  $val[$b]['status_txt'] = "待付款";
							break;
							case 2:  $val[$b]['status_txt'] = "待配货";
							break;
							case 3:  $val[$b]['status_txt'] = "待发货";
							break;
							case 4:  $val[$b]['status_txt'] = "待收货";
							break;
							case 5:  $val[$b]['status_txt'] = "已收货";
							break;
							case 6:  $val[$b]['status_txt'] = "已完成";
							break;
							case -1: $val[$b]['status_txt'] ="已取消";
							break;
							default: $val[$b]['status_txt'] = "未知状态";
							break;
						}
						if($val['order_status'] == 3){
							$orderDeliverys = $this->getDelivery($v['order_id']);
							if(!empty($orderDeliverys)){
								$val[$key][$b]['status_txt'] = "部分发货，待收货";
							}
						}

					$val[$b]['goods_data'] =$goods=  unserialize($v['attribute']);

					if($goods['selected']['goods_name_bm']){ //szzmzb 板房商品显示
						$val[$b]['goods_data']['goods_name'] = $val[$b]['goods_data']['goods_name'].'('.$goods['selected']['goods_name_bm'].')';
					}
					if($val[$b]['goods_type'] == 3 || $val[$b]['goods_type'] == 4){   // 3成品
						$val[$b]['goods_price']= ($val[$b]['goods_price_up']>0) ? $val[$b]['goods_price_up'] : $val[$b]['goods_price'];
						$val[$b]['goods_number']= ($val[$b]['goods_number_up']>0) ? $val[$b]['goods_number_up'] : $val[$b]['goods_number'];
						$val[$b]['goods_give_price']= formatRound($val[$b]['goods_price']/$val[$b]['goods_number'],2);
					}

					$val[$b]['payment_price'] = $this->getPaymentPrice($v['order_id']);
					if($val['order_status'] <= 0){
						$val[$b]['price'] = $v['order_price'];
					}else if($val['order_status'] >0){
						$val[$b]['price'] = $v['order_price_up'];
					}
					}
					$dataList [$key] = $val;
				}
				if(count($dataList)>0){
					$sglM = D('GoodsLuozuan');
					$dataList['yn_certificate_num_cv']=$sglM->yn_certificate_num();
					$dataList['order_status'] =$order_status;
					$dataList['page'] = $page->show();
				}
			}else{
					$dataList='';
			}
		   $this->ajaxReturn($dataList);
		}
		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1  and agent_id = '.C("agent_id"))->select();	//线下支付
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
		
		if($agent_config && $agent_config['wxappid']!='' && $agent_config['wxmchid']!='' && $agent_config['wxkey']!='' && $agent_config['wxappsecret']!=''){
			$this->wxpay = 1;
		}		
		
		
        $this->display();
	}

	/*
	*	我的订单详情，新版B2C
	*	zhy
	*	2016年7月12日 09:56:59
	*/

	public function odeta(){
		$order_id = I('orderid',0);
    	if($order_id == 0){
    		$this->redirect('/Home/User/olist');
    	}
    	$order = M('order')->alias('o')->where("o.agent_id='".C('agent_id')."' and o.uid='".$_SESSION['web']['uid']."' AND o.order_id ='".$order_id."'")->find();
    	if(!$order){
    		$this->redirect('/Home/User/olist');
    	}
    	$dataList = M('order')->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.agent_id='".C('agent_id')."' and zm_order.order_id ='".$order_id."'")->select();


		if($dataList[0]['address_id']){
			if($dataList[0]['address_info']){
				$this->user_address	 = D('Common/UserAddress')->fegeAddressInfo($dataList[0]['address_info']);
			}else{
				$UA = M('user_address');
				$field = 'ua.address,ua.title,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.'r2.region_name as province,r3.region_name as city,r4.region_name as district';
				$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
				$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
				$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
				$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
				$this->user_address = $UA->alias('ua')->where('ua.address_id = '.$dataList[0]['address_id'].' and ua.agent_id = '.C('agent_id'))->field($field)
				->join($join1)->join($join2)->join($join3)->join($join4)->find();
			}
			
			if($dataList[0]['delivery_mode']){
				$this->user_address	 = D('Common/OrderPayment')->this_dispatching_way($dataList[0]['delivery_mode'],$this->user_address);
			}
		}
        $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
        $deputystoneM        = M('goods_associate_deputystone');
    	if($dataList){
    		foreach($dataList as $key=>$val){
				$dataList[$key]['status_txt'] = getOrderStatus($val['order_status']);
				if($val['order_status'] == 3){
					$orderDeliverys = $this->getDelivery($val['order_id']);
					if(!empty($orderDeliverys)){
						$dataList[$key]['status_txt'] = "部分发货，待收货";
					}
				}
    			$dataList[$key]['goods'] =  unserialize($val['attribute']);
    			$dataList[$key]['goods']['goods_type'] = $this->getGoodsTypeByTid($goods['tid']);
                $dataList[$key]['goods'] = unserialize($val['attribute']);
                $val['attribute'] = unserialize($val['attribute']);
				$dataList[$key]['goods']['luozuanInfo']['shape_name'] =  $luozuan_shape_array[$dataList[$key]['goods']['luozuanInfo']['shape_id']];
                if($dataList[$key]['goods']['attribute']){
                    $dataList[$key]['goods']['attributes'] = unserialize($dataList[$key]['goods']['attribute']);
                }
                $dataList[$key]['goods']['deputystone_name']  = "";
                $dataList[$key]['goods']['deputystone_price'] = "";
				if($dataList[$key]['goods']['deputystone']['gad_id']){
				           $CG  = D('Common/Goods');
                    $CG -> calculationPoint($val['goods_id']);
                    $deputystone  = $CG -> getGoodsAssociateDeputystoneAfterAddPoint('gad_id = '.$dataList[$key]['goods']['deputystone']['gad_id']);//$deputystoneM -> where('gad_id = '.$dataList[$key]['goods']['deputystone']['gad_id'])->select();
                    if($deputystone){
                        $dataList[$key]['goods']['deputystone_name']  = '副石：'.$deputystone['deputystone_name'];
                        $dataList[$key]['goods']['deputystone_price'] = $deputystone['deputystone_price'];
                    }
                }

    			if($val['goods_type'] == 1){ // 1表示证书货
    				if($order['order_status'] <= 0){
    					$dataList['diamond_total_amount'] += $val['goods_price'];
    					$total_amount  += $val['goods_price'];
    					$dataList[$key]['diamond_price'] = $val['goods_price'];
    					$dataList[$key]['diamond_number'] = intval($val['goods_number']);
    				}else if($order['order_status'] > 0){
    					$dataList['diamond_total_amount'] += $val['goods_price_up'];
    					$total_amount  += $val['goods_price_up'];
    					$dataList[$key]['diamond_price'] = $val['goods_price_up'];
    					$dataList[$key]['diamond_number'] = intval($val['goods_number_up']);
    				}
    				$diamond[] = $dataList[$key];
    			}else if($val['goods_type'] == 2){ // 散货
				//获取分类名称
				$type_name = M("goods_sanhuo_type")->where("tid=".$val['attribute']['tid'])->getField("type_name");
				if($type_name){
					$dataList[$key]['goods']['type_name'] = $type_name;
				}
    			if($order['order_status'] <= 0){  //表示订单未确认
    					$dataList[$key]['goods_price'] = formatRound($dataList[$key]['goods_price']*(1+C('sanhuo_advantage')/100),2);
    					$dataList[$key]['sanhuo_price'] = $val['goods_price'];
    					$dataList[$key]['sanhuo_number'] = $val['goods_number'];
    					$dataList['sanhuo_total_amount'] += formatRound($val['goods_price'],2);
    					$total_amount  += formatRound($val['goods_price'],2);
					}else if($order['order_status'] > 0){
						$dataList[$key]['sanhuo_price'] = $val['goods_price_up'];
    					$dataList[$key]['sanhuo_number'] = $val['goods_number_up'];
						$dataList['sanhuo_total_amount'] += formatRound($val['goods_price_up'],2);
    					$total_amount  += formatRound($val['goods_price_up'],2);
					}
    				$sanhuo[] = $dataList[$key];
    			}else if($val['goods_type'] == 3 || $val['goods_type'] == 4){  // 成品
					//获取材质名称
					$material_name = M("goods_material")->where(array('material_id'=>$dataList[$key]['goods']['associateInfo']['material_id']))->getField('material_name');
					 if($material_name){
						 $dataList[$key]['goods']['associateInfo']['material_name'] = $material_name;
					 }
    				if($order['order_status'] <= 0){
    					$dataList['product_total_amount'] += $val['goods_price'];
    					$total_amount += formatRound($val['goods_price'],2);
    					$total_amount = formatRound($total_amount,2);
    					$dataList[$key]['product_price'] = $val['goods_price'];
    					$dataList[$key]['product_number'] += intval($val['goods_number']);
    				}else if($order['order_status'] >0){
    					$dataList['product_total_amount'] += $val['goods_price_up'];
    					$total_amount += $val['goods_price_up'];
    					$dataList[$key]['product_price'] = $val['goods_price_up'];
    					$dataList[$key]['product_number'] += intval($val['goods_number_up']);
    				}
					$dataList[$key]['is_show_hand'] = is_show_hand($val['category_id'], $val['goods_type']);
                    if($val['goods_type'] == 3){
                        $attr = explode("^",$dataList[$key]['goods']['goods_sku']['attributes']);
                        if($attr){
                            $attrs = "";
                            foreach($attr as $k=>$v){
                                $temp = explode(":",$v);
                                if($temp){
                                    $inputType = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                                    if($inputType == 3){ //如果是手填值,直接显示该值
                                        $attrs .= $temp[1];
                                    }else{  //如果不是手填值,则查出对应的属性值
                                        $attrs .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                                    }
                                    $attrs .= " ";
                                }
                            }
                            $dataList[$key]['goods']['attrs'] = trim($attrs);
                        }
                    }
					$product[] = $dataList[$key];
    			}else if($val['goods_type'] == 5 || $val['goods_type'] == 6){ // 代销
    				if($order['order_status'] <= 0){
    					$dataList['zmProduct_total_amount'] += $val['goods_price'];
    					$total_amount += formatRound($val['goods_price'],2);
    					$total_amount = formatRound($total_amount,2);
    					$dataList[$key]['product_price'] = $val['goods_price'];
    					$dataList[$key]['product_number'] = intval($val['goods_number']);
    				}else if($order['order_status'] >0){
    					$dataList['zmProduct_total_amount'] += $val['goods_price_up'];
    					$total_amount += $val['goods_price_up'];
    					$dataList[$key]['product_price'] = $val['goods_price_up'];
    					$dataList[$key]['product_number'] = intval($val['goods_number_up']);
    				}
    				if($goods['specification_id']>0){
    					$dataList[$key]['specification']= $this->getGoodsSpecificationById($val['goods_type'],$goods['specification_id'],'');
    				}
					$zmProduct[] = $dataList[$key];
    			}else if($val['goods_type'] == 16){ //szzmzb 板房
    				if($val['attribute']['selected']['goods_name_bm']){ //情侣戒副款显示
						$dataList[$key]['goods']['goods_name'] = $dataList[$key]['goods']['goods_name'].'('.$val['attribute']['selected']['goods_name_bm'].')';
					}
					$dataList[$key]['goods']['associateInfo']['material_name'] = $val['attribute']['selected']['material_name'];
					$dataList[$key]['goods']['associateInfo']['weights_name'] = $val['attribute']['selected']['material_weight'];
					$dataList[$key]['goods']['hand'] = $val['attribute']['selected']['hand'];
					$dataList[$key]['goods']['word'] = $val['attribute']['selected']['word'];
    				$dataList[$key]['product_number'] = $val['goods_number'];
    				$dataList[$key]['product_price'] = $val['order_price'];
    				$dataList[$key]['goods']['jewelry_name'] = $val['attribute']['price_info']['price_info']['base_process_info']['jewelry_name'];
    				$product[] = $dataList[$key];
    				$total_amount += $val['order_price'];
    			}
    		}
    		$dataList['sanhuo_total_amount'] = formatRound($dataList['sanhuo_total_amount'],2);
    		$dataList['product_total_amount'] = formatRound($dataList['product_total_amount'],2);
    		$dataList['zmProduct_total_amount'] = formatRound($dataList['zmProduct_total_amount'],2);
			
			//获取预约信息
			$Store		= new \Common\Model\Store();
			$booked_where['zm_store_booked.orderid']	= $order_id;
			$booked_where['zm_store_booked.agent_id']	= C('agent_id');
			$bookedInfo	= $Store->getBookedInfo($booked_where);
			$this->bookedInfo	= $bookedInfo;

			//重新拼接匹配数据					
			$i=0;
			foreach($diamond as $key=>$val){
				foreach($product as $k=>$v){
					if($val["dingzhi_id"]!="" && $v["dingzhi_id"]!="" && $val["dingzhi_id"]==$v["dingzhi_id"]){
						$dingzhiArr[]   = $val["id"].",".$v["id"];
						$dingzhi_list[$i]["product"] = $v;	
						$dingzhi_list[$i]["luozuan"] = $val;	
						unset($diamond[$key]);
						unset($product[$k]);
						$i++;
					}	
				}
			}
			$this->dingzhi_list = $dingzhi_list;
			
           	$this->order = $order;
    		$this->total_amount = formatRound($total_amount,2);
			$this->total_num=count($diamond)+count($product)+count($zmProduct)+count($sanhuo);
    		$this->diamond = $diamond;
    		$this->sanhuo = $sanhuo;
    		$this->product = $product;
    		$this->zmProduct = $zmProduct;
    		$this->dataList = $dataList;
			//发货单列表
			$orderDeliverys = $this->getDelivery($order_id);

			$this->orderDeliverys = $orderDeliverys;

			
			
			$sglM = D('GoodsLuozuan');
			$this->yn_certificate_num_cv=$sglM->yn_certificate_num();			
			
			
			/* 支付宝绑定状态 */
			$this->alipay = 0;
			$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
			if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
				$this->alipay = 1;
			}
			
			if($agent_config && $agent_config['wxappid']!='' && $agent_config['wxmchid']!='' && $agent_config['wxkey']!='' && $agent_config['wxappsecret']!=''){
				$this->wxpay = 1;
			}
			
			$this->LinePayMode = M('payment_mode_line')->where(' atype=1  and agent_id = '.C("agent_id"))->select();	//线下支付
    		$this->display();
		}else{
			$this->redirect('/Home/User/olist');
		}

	}

	/*
	*	我的订单评价
	*	zhy
	*	2016年7月12日 09:56:59
	*/

	public function oeval(){
		$order_id = I('oid',0);
		$dataList =  M('order_goods')->where("order_id =" .$order_id)->select();
		// if($dataList[0]['oeval']!='1'){
			// $this->redirect('/Home/User/olist');exit();		//已经评价，直接跳到主页。
		// }
		$deputystoneM        = M('goods_associate_deputystone');
    	if($dataList){
    		foreach($dataList as $key=>$val){
				$dataList[$key]['status_txt'] = getOrderStatus($val['order_status']);
				if($val['order_status'] == 3){
					$orderDeliverys = $this->getDelivery($val['order_id']);
					if(!empty($orderDeliverys)){
						$dataList[$key]['status_txt'] = "部分发货，待收货";
					}
				}
    			$dataList[$key]['goods'] =  unserialize($val['attribute']);
    			$dataList[$key]['goods']['goods_type'] = $this->getGoodsTypeByTid($goods['tid']);
                $dataList[$key]['goods'] = unserialize($val['attribute']);
                $val['attribute'] = unserialize($val['attribute']);
				$dataList[$key]['goods']['luozuanInfo']['shape_name'] =  $luozuan_shape_array[$dataList[$key]['goods']['luozuanInfo']['shape_id']];
                if($dataList[$key]['goods']['attribute']){
                    $dataList[$key]['goods']['attributes'] = unserialize($dataList[$key]['goods']['attribute']);
                }
                $dataList[$key]['goods']['deputystone_name']  = "";
                $dataList[$key]['goods']['deputystone_price'] = "";
                if($dataList[$key]['goods']['deputystone']){
                    $deputystone = $deputystoneM->where('gad_id = '.$dataList[$key]['goods']['deputystone']['gad_id'])->select();
                    if($deputystone){
                        $dataList[$key]['goods']['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
                        $dataList[$key]['goods']['deputystone_price'] = $deputystone[0]['deputystone_price'];
                    }
                }
    			if($val['goods_type'] == 1){ // 1表示证书货
    				if($order['order_status'] <= 0){
    					$dataList['diamond_total_amount'] += $val['goods_price'];
    					$total_amount  += $val['goods_price'];
    					$dataList[$key]['diamond_price'] = $val['goods_price'];
    					$dataList[$key]['diamond_number'] = intval($val['goods_number']);
    				}else if($order['order_status'] > 0){
    					$dataList['diamond_total_amount'] += $val['goods_price_up'];
    					$total_amount  += $val['goods_price_up'];
    					$dataList[$key]['diamond_price'] = $val['goods_price_up'];
    					$dataList[$key]['diamond_number'] = intval($val['goods_number_up']);
    				}
    				$diamond[] = $dataList[$key];
    			}else if($val['goods_type'] == 2){ // 散货
				//获取分类名称
				$type_name = M("goods_sanhuo_type")->where("tid=".$val['attribute']['tid'])->getField("type_name");
				if($type_name){
					$dataList[$key]['goods']['type_name'] = $type_name;
				}
    			if($order['order_status'] <= 0){  //表示订单未确认
    					$dataList[$key]['goods_price'] = formatRound($dataList[$key]['goods_price']*(1+C('sanhuo_advantage')/100),2);
    					$dataList[$key]['sanhuo_price'] = $val['goods_price'];
    					$dataList[$key]['sanhuo_number'] = $val['goods_number'];
    					$dataList['sanhuo_total_amount'] += formatRound($val['goods_price'],2);
    					$total_amount  += formatRound($val['goods_price'],2);
					}else if($order['order_status'] > 0){
						$dataList[$key]['sanhuo_price'] = $val['goods_price_up'];
    					$dataList[$key]['sanhuo_number'] = $val['goods_number_up'];
						$dataList['sanhuo_total_amount'] += formatRound($val['goods_price_up'],2);
    					$total_amount  += formatRound($val['goods_price_up'],2);
					}
    				$sanhuo[] = $dataList[$key];
    			}else if($val['goods_type'] == 3 || $val['goods_type'] == 4){  // 成品
					//获取材质名称
					$material_name = M("goods_material")->where(array('material_id'=>$dataList[$key]['goods']['associateInfo']['material_id']))->getField('material_name');
					 if($material_name){
						 $dataList[$key]['goods']['associateInfo']['material_name'] = $material_name;
					 }
    				if($order['order_status'] <= 0){
    					$dataList['product_total_amount'] += $val['goods_price'];
    					$total_amount += formatRound($val['goods_price'],2);
    					$total_amount = formatRound($total_amount,2);
    					$dataList[$key]['product_price'] = $val['goods_price'];
    					$dataList[$key]['product_number'] += intval($val['goods_number']);
    				}else if($order['order_status'] >0){
    					$dataList['product_total_amount'] += $val['goods_price_up'];
    					$total_amount += $val['goods_price_up'];
    					$dataList[$key]['product_price'] = $val['goods_price_up'];
    					$dataList[$key]['product_number'] += intval($val['goods_number_up']);
    				}
                    if($val['goods_type'] == 3){
                        $attr = explode("^",$dataList[$key]['goods']['goods_sku']['attributes']);
                        if($attr){
                            $attrs = "";
                            foreach($attr as $k=>$v){
                                $temp = explode(":",$v);
                                if($temp){
                                    $inputType = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                                    if($inputType == 3){ //如果是手填值,直接显示该值
                                        $attrs .= $temp[1];
                                    }else{  //如果不是手填值,则查出对应的属性值
                                        $attrs .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                                    }
                                    $attrs .= " ";
                                }
                            }
                            $dataList[$key]['goods']['attrs'] = trim($attrs);
                        }
                    }
					$product[] = $dataList[$key];
    			}else if($val['goods_type'] == 5 || $val['goods_type'] == 6){ // 代销
    				if($order['order_status'] <= 0){
    					$dataList['zmProduct_total_amount'] += $val['goods_price'];
    					$total_amount += formatRound($val['goods_price'],2);
    					$total_amount = formatRound($total_amount,2);
    					$dataList[$key]['product_price'] = $val['goods_price'];
    					//print_r($val['goods_price']);
    					$dataList[$key]['product_number'] = intval($val['goods_number']);
    				}else if($order['order_status'] >0){
    					$dataList['zmProduct_total_amount'] += $val['goods_price_up'];
    					$total_amount += $val['goods_price_up'];
    					$dataList[$key]['product_price'] = $val['goods_price_up'];
    					$dataList[$key]['product_number'] = intval($val['goods_number_up']);
    				}
    				if($goods['specification_id']>0){
    					$dataList[$key]['specification']= $this->getGoodsSpecificationById($val['goods_type'],$goods['specification_id'],'');
    				}
					$zmProduct[] = $dataList[$key];
    			}
    		}
		}
    		//$dataList['sanhuo_total_amount'] = formatRound($dataList['sanhuo_total_amount'],2);
    		//$dataList['product_total_amount'] = formatRound($dataList['product_total_amount'],2);
    		//$dataList['zmProduct_total_amount'] = formatRound($dataList['zmProduct_total_amount'],2);
           	//$this->order = $order;
    		$this->total_amount = formatRound($total_amount,2);
			//$this->total_num=count($diamond)+count($product)+count($zmProduct)+count($sanhuo);
    		$this->diamond = $diamond;
    		$this->sanhuo = $sanhuo;
    		$this->product = $product;
    		$this->zmProduct = $zmProduct;
        $this->display();
	}



	/*
	*	我的订单评价api
	*	zhy
	*	2016年7月14日 10:10:55
	*/

	public function oeval_api(){
		$is_stars = I('post.is_stars');
		$control = I('post.control');
		$og_id = I('post.og_id');
		$g_id = I('post.g_id');
		$uid=$_SESSION['web']['uid'];
		if(!is_numeric($is_stars)|| empty($control)){	echo ('请选择评分！'); exit();}
		if(empty($control)){	echo ('请填写评论内容！'); exit();}
		if(empty($uid)){		echo ('请先登录在评价！'); exit();}
		$oe = M("order_eval");
		$og = M("order_goods");
		$Dao= M();
		$oeval = $oe->where(' og_id = '.$og_id.' and uid ='.$uid)->find();
		if(!empty($oeval)){	echo ('该单已评价！'); exit();}
		$data['start'] = $is_stars;
		$data['og_id'] = $og_id;
		$data['control'] = $control;
		$data['goods_id'] = $g_id;
		$data['uid'] = $uid;
		$data['createip']=$this->real_ip();
		$data['username']=$this->username;
		$data['createtime']=time();
		if($oe->add($data)){
			$datas['oeval'] = '2';
			$og->where('og_id='.$og_id)->save($datas);
			$Dao->execute("update zm_goods set oeval_num = oeval_num+".$is_stars." where goods_id=".$g_id);
			$result='发表评论成功! ';
		}else{
			$result='发表评论失败，请稍后在试！';
		}
		echo ($result);
	}








    // 收货地址的添加与列表显示
    public function shopAddress() {
      $S = M('user_address');
      $this->act = 'shopAddress';
      if (IS_POST) {
        $result = array('error'=>'yes','msg'=>L('L9075'));
        $sData['uid'] = $this->uId;
        $sData['title'] = I('post.title', '', 'htmlspecialchars');
        $sData['country_id'] = I('post.country_id', '', 'htmlspecialchars');
        $sData['province_id'] = I('post.province_id', '', 'htmlspecialchars');
        $sData['city_id'] = I('post.city_id', '', 'htmlspecialchars');
        $sData['district_id'] = I('post.district_id', '', 'htmlspecialchars');
        $sData['address'] = I('post.address', '', 'htmlspecialchars');
        $sData['code'] = I('post.code', '', 'htmlspecialchars');
        $sData['name'] = I('post.name', '', 'htmlspecialchars');
        $sData['phone'] = I('post.phone', '', 'htmlspecialchars');
        $sData['is_default'] = I('post.is_default', '', 'htmlspecialchars');
        $sData['agent_id'] = C('agent_id');
        if ($sData['is_default'] == 1) { // 如果新添加的地址为默认的，则其它地址就要更新为非默认
          $S->where(array('uid'=>$this->uId))->save(array('is_default'=>2));
        }
        if ($S->data($sData)->add()) {
          $result = array('error'=>'no','msg'=>L('L9071'), 'backUrl'=>'shopAddress.html');
        }
        $this->ajaxReturn($result);
      } else {

        $this->userAddList = D('Common/UserAddress')->getAddressList($this->uId);
        $this->catShow = 0;
        $this->active = "shopAddress";
        $this->display();
      }
    }
    // 用户中心 首页
    public function index(){
		$this->active ='userinfo';
		$this->catShow = 0;
		$this->userInfo  = M("user")->where("uid=".$_SESSION['web']['uid'])->find();
		$this->trader    = M("trader")->where("tid=".$this->traderId.' and agent_id = '.C('agent_id'))->find();
		$latelyOrderList = M('order')->where(" agent_id='".C('agent_id')."' and uid=".$_SESSION['web']['uid'])->order('create_time DESC')->limit(7)->select();
		if($latelyOrderList){
		foreach($latelyOrderList as $key=>$val){
		    $latelyOrderList[$key]['status_txt'] = getOrderStatus($val['order_status']);
		}
		}
		$orders = M('order')->where("agent_id='".C('agent_id')."' and uid=".$_SESSION['web']['uid'])->order('create_time DESC')->select();
		$noHandles = 0;
		if($orders){
        	foreach($orders as $key=>$val){
        		if($val['order_status == 0']){
        			$noHandles += 1;
        		}
        	}
		}
		$this->latelyOrderList = $latelyOrderList;
		$this->rangDate = $this->yearMothDay();
		$this->active = "index";
		if($this->traderId>0){
			$this->display();
		}else{
			$this->redirect('/Home/User/userInfo');
		}
    }
    // 修改密码
    public function updatePwd() {
    	if(C('new_rules')['zgoods_show']){
      		//周六福
      		$this->updatePwd_zlf();
			exit;
		}
      if (IS_POST) {
        $result  = array('error'=>'yes', 'msg'=>L('L9017'));
        $map['old_password'] = I('post.old_password', 'htmlspecialchars');
        $map['new_password'] = I('post.new_password', 'htmlspecialchars');
        $map['confirm_password'] = I('post.confirm_password', 'htmlspecialchars');
        if ($map['new_password'] === $map['confirm_password']) {
          $U = M('user');
          $uInfo = $U->field('*')->where("uid=".$this->uId.' and agent_id = '.C('agent_id'))->find();
          if (!empty($uInfo) &&  ($uInfo['password'] == pwdHash($map['old_password']))) {
            $pa['password'] = pwdHash(strval($map['new_password']));
            if ($U->where("uid=".$this->uId)->save($pa) !== false) {
              session('web', null);
              $this->uId = null;
              $result  = array('error'=>'no', 'msg'=>L('L9039'), 'backUrl'=>'Home/Public/login');
            } else {
              $result  = array('error'=>'yes', 'msg'=>L('L9042'));
            }
          } else {
            $result  = array('error'=>'yes', 'msg'=>L('L9041'));
          }
        } else {
          $result  = array('error'=>'yes', 'msg'=>L('L9040'));
        }
        $this->ajaxReturn($result);
      } else {
        $this->active ='userinfo';
        $this->act ='updatePwd';
        $this->catShow = 0;
        $this->display('userInfo');
      }
    }

    // 修改密码 周六福
    public function updatePwd_zlf() {
    	if (IS_POST) {
        $result  = array('error'=>'yes', 'msg'=>L('L9017'));
        $map['old_password'] = I('post.old_password', 'htmlspecialchars');
        $map['new_password'] = I('post.new_password', 'htmlspecialchars');
        $map['confirm_password'] = I('post.confirm_password', 'htmlspecialchars');
        if ($map['new_password'] === $map['confirm_password']) {
          $U = M('user');
          $uInfo = $U->field('*')->where("uid=".$this->uId.' and agent_id = '.C('agent_id'))->find();
          if (!empty($uInfo) &&  ($uInfo['password'] == pwdHash($map['old_password']))) {
            $pa['password'] = pwdHash(strval($map['new_password']));
            if ($U->where("uid=".$this->uId)->save($pa) !== false) {
              session('web', null);
              $this->uId = null;
              $result  = array('error'=>'no', 'msg'=>L('L9039'), 'backUrl'=>'Home/Public/login');
            } else {
              $result  = array('error'=>'yes', 'msg'=>L('L9042'));
            }
          } else {
            $result  = array('error'=>'yes', 'msg'=>L('L9041'));
          }
        } else {
          $result  = array('error'=>'yes', 'msg'=>L('L9040'));
        }
        $this->ajaxReturn($result);
      } else {
        $this->active ='userinfo';
        $this->act ='updatePwd';
        $this->catShow = 0;
        $this->display('dingzhi/zhouliufu/User/userInfo');
      }
    }
    // 匹配输入的密码和之前的密码
    public function checkPwd(){
    	$old_password = I("old_password");
    	$password = M("user")->where("uid=".$_SESSION['web']['uid'])->getField("password");
    	$result = array('success'=>false, 'msg'=>"您输入的原密码不对",'error'=>true);
    	if($password == pwdHash($old_password)){
    		$result = array('success'=>true, 'msg'=>"原密码正确",'error'=>false);
    	}
    	$this->ajaxReturn($result);
    }
    // 订单列表
    public function orderList(){
		if(C('new_rules')['zgoods_show']){
			$this->_orderList();exit;
		}
    	$Order        = M('order');
    	$order_sn     = I("order_sn");
    	$dateFrom     = I("dateFrom");
    	$dateTo       = I("dateTo");
    	$certNo       = I("certificate_number");
    	$order_status = I('order_status',-2);
    	$dateFromTime = strtotime($dateFrom);
    	$dateToTime   = strtotime($dateTo)+24*3600;
        $where        = " uid ='".$_SESSION['web']['uid']."'";
        if($order_sn){
        	$where .= " AND order_sn = '".$order_sn."'";
        }
        if($dateFrom){
        	$where .= " AND create_time >= ".$dateFromTime;
        }
        if($dateTo){
        	$where .= " AND create_time <= ".$dateToTime;
        }

        if($order_status != -2 && $order_status != "" ){
        	$where .= " AND order_status = ".$order_status;
        }

        if($certNo){
        	$subQuery = M('order_goods')->field('order_id')->group('order_id')->where("agent_id='".C('agent_id')."' and certificate_no ='".$certNo."'")->order('order_id')->select(false);
        	$where .= " AND order_id In (".$subQuery.")";
        }
        $where .= " AND agent_id='".C('agent_id')."' and order_status >=-1";
        $count = M('order')->where($where)->count();
    	$n = 10;
    	$page = I('page');
        $Page = New \Think\Page($count,$n);
        $dataList = $Order->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('create_time DESC')->select();
        foreach($dataList as $key=>$val){
        	$dataList[$key]['status_txt'] = getOrderStatus($val['order_status']);
        	if($val['order_status'] == 3){
        		$orderDeliverys = $this->getDelivery($val['order_id']);
        		if(!empty($orderDeliverys)){
        			$dataList[$key]['status_txt'] = "部分发货，待收货";
				}
			}
        	$dataList[$key]['payment_price'] = $this->getPaymentPrice($val['order_id']);
        	if($val['order_status'] <= 0){
        		$dataList[$key]['price'] = $val['order_price'];
        	}else if($val['order_status'] >0){
        		$dataList[$key]['price'] = $val['order_price_up'];
        	}
        }

        $this->orderStatus = orderStatus();
        $this->order_status = $order_status;
        $this->certificate_no = $certNo;
        //print_r($order_status);
        $this->page = $Page->show();
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->orderList  = $dataList;
        $this->order_sn = $order_sn;
        $this->active = 'orderList';

		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1  and agent_id = '.C("agent_id"))->select();	//线下支付
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
	
        $this->display();
    }

	//周六福订单列表
	public function _orderList(){
		$this->order_status = [
			'-2' =>'全部订单',
			'0,1' => '待支付',
			'2,3' => '待发货',
			'4' => '待收货',
			'5,6' => '己完成',
			'-1' => '己取消',
		];

		$this->display('dingzhi/zhouliufu/User/orderList');
	}

	public function ajax_zlf_orderList(){
		$order_status = [
			'0,1' => '待支付',
			'2,3' => '待发货',
			'4' => '待收货',
			'5,6' => '己完成',
			'-1' => '己取消',
		];

		//搜索条件
		$order_status[I('order_status')]?$where['order_status']=['in',I('order_status')]:'';
		trim(I('search'))?$where['order_sn_zlf']=['like','%'.I('search').'%']:'';

		//固定条件
		$where['agent_id'] = C('agent_id');
		$where['uid'] = $_SESSION['web']['uid'];

		$model_banfang_order = M('banfang_order');
		$count = $model_banfang_order->where($where)->count();

		if($count>0){

			//获取订单
			$page = new \Common\Org\page_pc_ajax($count,10);
			$banfang_order = $model_banfang_order->where($where)->limit($page->firstRow.','.$page->listRows)->order('order_id desc')->select();
			foreach ($banfang_order as $k => $v) {
					$banfang_order_key[$v['order_id']] = $v['order_id'];
					$banfang_order_new[$v['order_id']] = $v;
			}

			//获取订单商品
			$where = '';
			$where ['order_id'] = ['in',implode(',', $banfang_order_key)];
			$banfang_order_goods = M('banfang_order_goods')->where($where)->select();

			//组成数组
			foreach ($banfang_order_goods as $k => $v) {
				// dump($v);
				$v['attribute']= unserialize($v['attribute']);
				$goods_attr = unserialize($v['attribute']['goods_attr']);
				$v['goods_name']= $goods_attr['goods_name'];
				// dump($goods_attr);exit;
				$v['my_img']= $goods_attr['my_img'];
				$banfang_order_new[$v['order_id']]['sub'][] = $v;
			}

			//赋值到模板文件
			$this->page = $page->show_zlf();
			$this->data = $banfang_order_new;
		}

		echo json_encode([
				'display'=>$this->fetch('dingzhi/zhouliufu/User/ajax_zlf_orderList'),
			]);
	}
    /* 更新订单状态 */

    public function updateOrder(){
    	$order_id = I('order_id',0);
    	$order_status = I('order_status',0);
    	$href = I('href');
    	$where = "agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND order_id=".$order_id;
    	$data = array('status'=>0,'msg'=>'订单取消成功','href'=>$href);
        $order = M('order')->where($where)->find();
    	if(!$order || $order_id == 0){
    		$this->ajaxReturn($data);
    	}else{
    		switch($order_status){
    			case -1:
    				$order['order_status'] = -2;
    				$data['msg'] = "订单删除成功";
    			break;
    			case 0:
    				$order['order_status'] = -1;
    				$data['msg'] = "取消订单成功";
    			break;
    			case 4:
    				$order['order_status'] =5;
    				$data['msg'] = "确认收货成功";
    			break;
    		}
			
			if($order_status=='-1'){
				  M('order')->data($order)->delete();
			}elseif(M('order')->data($order)->save()){
				
				//取消订单，发送信息给商家
				$nowAgent_id = C('agent_id');
				$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
				if($sms_agent_info_arr["sms_push_reminder"]){
					$SMS = new \Common\Model\Sms();
					$SMS->SendSmsByType($SMS::USER_ORDER_CANCEL,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
				}			
				
    			$data['status'] = 1;
    			if($order_status == 4){
    				//更新发货表状态
    				$data['confirm_type'] = 2;
    				$data['confirm_time'] = time();
    				M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id=$order_id AND uid='".$_SESSION['web']['uid']."'")->data($data)->save();
    			}
    		}

    		$this->ajaxReturn($data);
    	}
    }

    /* 删除订单 */

    public function deleteOrder(){
    	$order_id = I('order_id',0);
    	$order_status = I('order_status',0);
    	$href = I('href');
    	$where = "agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND order_id=".$order_id." AND order_status = -1 ";
    	$data = array('status'=>0,'msg'=>'订单删除失败','href'=>$href);
        $order = M('order')->where($where)->find();
    	if(!$order || $order_id == 0){
    		$this->ajaxReturn($data);
    	}else{
    		if(M('order')->data($order)->delete()){
    			$data['status'] = 1;
    			$data['msg']  = "订单删除成功";
    		}
    		$this->ajaxReturn($data);
    	}
    }

    public function getReceivables($order_id){
    	$join = " LEFT JOIN zm_order_receivables as zor ON o.order_id = zor.order_id";
    	$list = M('order')->alias('o')->JOIN($join)->where("o.agent_id='".C('agent_id')."' and o.order_id='$order_id'")->select();
        if($list){
        	foreach($list as $key=>$val){
        		switch($val['period_type']){
        			case 1:
        				$receivables['luozuan'][] = $val;
        			break;
        			case 2:
        			   $receivables['sanhuo'][] = $val;
        			break;
        			case 3:
        			case 4:
        				$receivables['product'][] = $val;
        			break;
        			case 5:
        				$receivables['zmProduct'][] = $val;
        			break;
        		}
        	}
        }
        return $receivables;
    }

    public function getPeriods($order_id){
    	$join = " LEFT JOIN zm_order_period as zor ON o.order_id = zor.order_id";
    	$list = M('order')->alias('o')->JOIN($join)->where("o.agent_id='".C('agent_id')."' and o.order_id='$order_id'")->select();
        if($list){
        	foreach($list as $key=>$val){
        		switch($val['period_type']){
        			case 1:
        				$periods['luozuan'] = $val;
        			break;
        			case 2:
        				$periods['sanhuo'] = $val;
        			break;
        			case 3:
        			case 4:
        			    $periods['product'] = $val;
        			break;
        			case 5:
        				$periods['zmProduct'] = $val;
        			break;
        		}
        	}
        }
        return $periods;
    }

    public function _orderInfo(){
    	$order_id = I('order_id',0);
    	if($order_id < 0){
    		$this->redirect('/Home/User/orderList');
    	}

    	$where['agent_id'] = C('agent_id');
    	$where['uid'] = $_SESSION['web']['uid'];
    	$where['order_id'] = $order_id;
    	$order = M('banfang_order')->where($where)->find();

    	if(!$order){
    		$this->redirect('/Home/User/orderList');
    	}
    	//获取订单商品
		$where = '';
		$where ['bog.order_id'] = $order['order_id'];

		$banfang_order_goods = M('banfang_order_goods bog')
								->field('bog.*,bc.is_hand')
								->join('zm_banfang_goods bg on bg.goods_id = bog.goods_id')
								->join('zm_banfang_cate bc on bg.banfang_jewelry = bc.id')
								->where($where)
								// ->fetchSql(true)->find(1);
								->select();
		$GMM = D('Common/Goods');
		//组成数组
		foreach ($banfang_order_goods as $k => $v) {
			$v['attribute'] = unserialize($v['attribute']);
			$banfang_order_goods[$k]['sub_info'] = $v['attribute']['sub_info'];
		}

    	$this->order = $order;
    	$this->banfang_order_goods = $banfang_order_goods;
    	$this->display('dingzhi/zhouliufu/User/orderInfo');
    }

    // 订单详情
    public function orderInfo(){
    	if(C('new_rules')['zgoods_show']){
			$this->_orderInfo();exit;
		}
    	$order_id = I('order_id',0);
    	if($order_id == 0){
    		$this->redirect('/Home/User/orderList');
    	}
    	$order = M('order')->alias('o')->where("o.agent_id='".C('agent_id')."' and o.uid='".$_SESSION['web']['uid']."' AND o.order_id ='".$order_id."'")->find();

    	if(!$order){
    		$this->redirect('/Home/User/orderList');
    	}
    	//获取配置好的支付信息
    	$PC = M('payment_config');
    	$where = 'mode_id = 1 and parent_type = '.$this->uType.' and parent_id = '.$this->traderId;
    	$paymentInfo = $PC->where($where)->find();
    	$paymentInfo = unserialize($paymentInfo['pay_attr']);
    	$this->payment_config = $paymentInfo['pay_info'];
    	//print_r($order);
    	$this->periods = $this->getPeriods($order_id);
    	//print_r($this->getPeriods($order_id));

    	$this->receivables = $this->getReceivables($order_id);
    	$dataList = M('order')->JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id')->where("zm_order.agent_id='".C('agent_id')."' and zm_order.order_id ='".$order_id."'")->select();

    	//发货单列表
    	$orderDeliverys = $this->getDelivery($order_id);
    	//print_r($orderDeliverys);
        $this->orderDeliverys = $orderDeliverys;

        //退货单列表
        $returnOrders = $this->getReturnGoods($order_id);
        $this->returnOrders = $returnOrders;

        //补差价
        $this->orderReceivables = $this->getOrderReceivables($order_id);

        //退款记录
        $this->returnFunds = $this->getOrderReturnFunds($order_id);

    	//订单支付信息
    	$payList = M('order_payment')->where("agent_id='".C('agent_id')."' and order_id = '$order_id' AND uid='".$_SESSION['web']['uid']."' AND payment_status >1")->order("create_time DESC")->select();
    	if($payList){
    		$no_payment = $order['order_price_up'];
    		foreach($payList as $key=>$val){
    			$no_payment -= $val['payment_price'];
    			if($no_payment <=0){
    				$no_payment = 0;
    			}
    			$payList[$key]['no_payment'] = formatRound($no_payment,2);
    		}
    	}

    	$this->payList = $payList;
        $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
        $deputystoneM        = M('goods_associate_deputystone');
		
    	if($dataList){
    		foreach($dataList as $key=>$val){

    			$dataList[$key]['goods'] =  unserialize($val['attribute']);
    			$dataList[$key]['goods']['goods_type'] = $this->getGoodsTypeByTid($goods['tid']);

                $dataList[$key]['goods'] = unserialize($val['attribute']);
                $val['attribute'] = unserialize($val['attribute']);

				$dataList[$key]['goods']['luozuanInfo']['shape_name'] =  $luozuan_shape_array[$dataList[$key]['goods']['luozuanInfo']['shape_id']];



                if($dataList[$key]['goods']['attribute']){
                    $dataList[$key]['goods']['attributes'] = unserialize($dataList[$key]['goods']['attribute']);
                }

                $dataList[$key]['goods']['deputystone_name']  = "";
                $dataList[$key]['goods']['deputystone_price'] = "";
                if($dataList[$key]['goods']['deputystone']['gad_id']){
                    $deputystone = $deputystoneM->where('gad_id = '.$dataList[$key]['goods']['deputystone']['gad_id'])->select();
                    if($deputystone){
                        $dataList[$key]['goods']['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
                        $dataList[$key]['goods']['deputystone_price'] = $deputystone[0]['deputystone_price'];
                    }
                }
 
    			if($val['goods_type'] == 1){ // 1表示证书货

    				if($order['order_status'] <= 0){
    					$dataList['diamond_total_amount'] += $val['goods_price'];
    					$total_amount  += $val['goods_price'];
    					$dataList[$key]['diamond_price'] = $val['goods_price'];
    					$dataList[$key]['diamond_number'] = intval($val['goods_number']);
    				}else if($order['order_status'] > 0){
    					$dataList['diamond_total_amount'] += $val['goods_price_up'];
    					$total_amount  += $val['goods_price_up'];
    					$dataList[$key]['diamond_price'] = $val['goods_price_up'];
    					$dataList[$key]['diamond_number'] = intval($val['goods_number_up']);
    				}
    				$diamond[] = $dataList[$key];

    			}else if($val['goods_type'] == 2){ // 散货
    				//获取分类名称
    				$type_name = M("goods_sanhuo_type")->where("tid=".$val['attribute']['tid'])->getField("type_name");
    				if($type_name){
    					$dataList[$key]['goods']['type_name'] = $type_name;
    				}
    			    if($order['order_status'] <= 0){  //表示订单未确认
    					$dataList[$key]['goods_price'] = formatRound($dataList[$key]['goods_price']*(1+C('sanhuo_advantage')/100),2);
    					$dataList[$key]['sanhuo_price'] = $val['goods_price'];
    					$dataList[$key]['sanhuo_number'] = $val['goods_number'];
    					$dataList['sanhuo_total_amount'] += formatRound($val['goods_price'],2);
    					$total_amount  += formatRound($val['goods_price'],2);
					}else if($order['order_status'] > 0){
						$dataList[$key]['sanhuo_price'] = $val['goods_price_up'];
    					$dataList[$key]['sanhuo_number'] = $val['goods_number_up'];
						$dataList['sanhuo_total_amount'] += formatRound($val['goods_price_up'],2);
    					$total_amount  += formatRound($val['goods_price_up'],2);
					}
    				$sanhuo[] = $dataList[$key];

    			}else if($val['goods_type'] == 3 || $val['goods_type'] == 4){  // 成品
					//获取材质名称
					$material_name = M("goods_material")->where(array('material_id'=>$dataList[$key]['goods']['associateInfo']['material_id']))->getField('material_name');
					 if($material_name){
						 $dataList[$key]['goods']['associateInfo']['material_name'] = $material_name;
					 }
					 $dataList[$key]['is_show_hand'] = is_show_hand($val['category_id'], $val['goods_type']);
					
    				if($order['order_status'] <= 0){
    					$dataList['product_total_amount'] += $val['goods_price'];
    					$total_amount += formatRound($val['goods_price'],2);
    					$total_amount = formatRound($total_amount,2);
    					$dataList[$key]['product_price'] = $val['goods_price'];
    					$dataList[$key]['product_number'] += intval($val['goods_number']);
    				}else if($order['order_status'] >0){
    					$dataList['product_total_amount'] += $val['goods_price'];
    					$total_amount += $val['goods_price_up'];
    					$dataList[$key]['product_price'] = $val['goods_price_up'];
    					$dataList[$key]['product_number'] += intval($val['goods_number_up']);
    				}
                    if($val['goods_type'] == 3){

                        $attr = explode("^",$dataList[$key]['goods']['goods_sku']['attributes']);
                        if($attr){
                            $attrs = "";
                            foreach($attr as $k=>$v){
                                $temp = explode(":",$v);
                                if($temp){
                                    $inputType = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                                    if($inputType == 3){ //如果是手填值,直接显示该值
                                        $attrs .= $temp[1];
                                    }else{  //如果不是手填值,则查出对应的属性值
                                        $attrs .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                                    }
                                    $attrs .= " ";
                                }
                            }
                            $dataList[$key]['goods']['attrs'] = trim($attrs);
                        }
                    }
                    if($val['goods_type'] == 4){
                        $dataList[$key]['goods']['goods_price'] = formatRound($dataList[$key]['goods_price']/$dataList[$key]['goods_number'],2);
                    }
					$product[] = $dataList[$key];
    			}else if($val['goods_type'] == 5 || $val['goods_type'] == 6){ // 代销
    				if($order['order_status'] <= 0){
    					$dataList['zmProduct_total_amount'] += $val['goods_price'];
    					$total_amount += formatRound($val['goods_price'],2);
    					$total_amount = formatRound($total_amount,2);
    					$dataList[$key]['product_price'] = $val['goods_price'];
    					$dataList[$key]['product_number'] = intval($val['goods_number']);
    				}else if($order['order_status'] >0){
    					$dataList['zmProduct_total_amount'] += $val['goods_price_up'];
    					$total_amount += $val['goods_price_up'];
    					$dataList[$key]['product_price'] = $val['goods_price_up'];
    					$dataList[$key]['product_number'] = intval($val['goods_number_up']);
    				}
    				if($goods['specification_id']>0){
    					$dataList[$key]['specification']= $this->getGoodsSpecificationById($val['goods_type'],$goods['specification_id'],'');
    				}
					$zmProduct[] = $dataList[$key];
    			}else if($val['goods_type'] == 16){ //szzmzb 板房
    				$dataList[$key]['product_number'] = $dataList[$key]['goods']['selected']['goods_number'];
    				$dataList[$key]['attrs'] = '';

					if($dataList[$key]['selected']['goods_name_bm']){ //情侣戒副款显示
						$dataList[$key]['goods']['goods_name'] = $dataList[$key]['goods']['goods_name'].'('.$dataList[$key]['selected']['goods_name_bm'].')';
					}
					$dataList[$key]['goods']['associateInfo']['material_name'] = $dataList[$key]['goods']['selected']['material_name'];
					$dataList[$key]['goods']['hand'] = $dataList[$key]['goods']['selected']['hand'];
					$dataList[$key]['goods']['word'] = $dataList[$key]['goods']['selected']['word'];
					$dataList[$key]['goods']['goods_price'] = formatRound($dataList[$key]['goods']['goods_price']/$dataList[$key]['goods']['selected']['goods_number'],2);
					$dataList[$key]['goods']['activity_price'] = formatRound($dataList[$key]['goods']['activity_price'],2);
					$dataList[$key]['product_price'] = formatRound($dataList[$key]['goods_price'],2);
					$dataList[$key]['goods']['activity_status'] = $dataList[$key]['activity_status'];
					$dataList[$key]['goods']['attrs'] = '';
					$dataList[$key]['goods']['associateInfo']['weights_name'] = formatRound($dataList[$key]['goods']['selected']['material_weight'],4);
					$dataList[$key]['goods']['luozuanInfo']['weights_name'] = formatRound($dataList[$key]['goods']['selected']['luozuan_weight'],4);
					$dataList[$key]['goods']['luozuanInfo']['shape_name'] = $dataList[$key]['goods']['selected']['shape_name'];
					$dataList[$key]['goods']['luozuanInfo']['ct_mm'] = $dataList[$key]['goods']['selected']['ct_mm'];
					$dataList[$key]['goods']['deputystone_name'] = '副石 : '.$dataList[$key]['goods']['selected']['deputystone_name'];
					if($dataList[$key]['goods']['selected']['deputystone_weight']){
						if($data['dingzhi_goods_attr']['selected']['deputystone_weight']){
							$dataList[$key]['goods']['deputystone_name'].= $dataList[$key]['goods']['selected']['deputystone_number'].'颗'.$dataList[$key]['goods']['selected']['deputystone_weight'];
						}else{
							$dataList[$key]['goods']['deputystone_name'].= $dataList[$key]['goods']['selected']['deputystone_number'];
						}
					}
					$dataList[$key]['goods']['jewelry_name'] = $dataList[$key]['goods']['price_info']['price_info']['base_process_info']['jewelry_name'];
    				$product[] = $dataList[$key];
    				$dataList['product_total_amount'] += $dataList[$key]['goods_price'];
    			}
    		}

    		$dataList['sanhuo_total_amount'] = formatRound($dataList['sanhuo_total_amount'],2);
    		$dataList['product_total_amount'] = formatRound($dataList['product_total_amount'],2);
    		$dataList['zmProduct_total_amount'] = formatRound($dataList['zmProduct_total_amount'],2);

           	$this->order = $order;
    		$this->total_amount = formatRound($total_amount,2);
    		$this->order_id = $order_id;
    		$this->diamond = $diamond;
    		$this->sanhuo = $sanhuo;
    		$this->product = $product;
    		$this->zmProduct = $zmProduct;
    		$this->dataList = $dataList;
    		$this->active = "orderList";
    		$this->display();
		}else{
			$this->redirect('/Home/User/orderList');
		}
    }

     public function getGoodsSpecificationById($goods_type,$speciation_id,$str){
		 return array();
    }

    public function getOrderReturnFunds($orderId){
    	return M('order_refund')->where("agent_id='".C('agent_id')."' and order_id=$orderId AND uid='".$_SESSION['web']['uid']."' AND refund_status = 2")->select();
    }

    public function getOrderReceivables($orderId){
    	$orderReceivables = M('orderReceivables')->where("period_type=12 AND order_id=$orderId AND uid=".$_SESSION['web']['uid'])->select();
    	return $orderReceivables;
    }

    public function getReturnGoods($orderId){
    	$join = " LEFT JOIN zm_order_return_goods ZORG ON ZOR.return_id = ZORG.return_id LEFT JOIN zm_order_goods ZOG ON ZOG.og_id=ZORG.og_id";
    	$where = " ZOR.agent_id='".C('agent_id')."' and ZOR.uid = '".$_SESSION['web']['uid']."' AND ZOR.order_id=$orderId AND ZOR.status = 3";
    	$data = M('order_return')->field("ZOG.certificate_no,ZORG.delivery_id,ZOR.goods_price,ZOG.attribute,ZOR.create_time,ZORG.goods_number")->alias("ZOR")->JOIN($join)->where($where)->select();

    	if($data){
    		foreach($data as $key=>$val){
    			$data[$key]['attrs'] = unserialize($val['attribute']);
    		}
    	}
    	//print_r($data);
    	return $data;
    }

    public function getOrdersByOrderId(){
    	$order_id = I('order_id',0);
    	$goods_type = I('goods_type',0);
    	if($order=M('order')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND order_id='".$order_id."'")->find()){
    		$dataList = M('order_goods')->where("agent_id='".C('agent_id')."' and order_id='".$order_id."' AND goods_type = '".$goods_type."'")->order('og_id ASC')->select();
    		if($dataList){
    			foreach($dataList as $key=>$val){
    				$dataList[$key]['goods'] = unserialize($val['attribute']);
    				if($val['goods_type'] == 2){ //散货

    					$dataList[$key]['type_name'] = getTypeNameByGoodsType($dataList[$key]['goods']['tid']);
    					$dataList[$key]['price'] += formatRound($val['goods_price'],2);
    					$data['sanhuo']['total_amount'] += formatRound($dataList[$key]['price'],2);
    					$dataList[$key]['price_update'] = formatRound($val['goods_price_up'],2);
    					$data['sanhuo']['total_amount_update'] += formatRound($dataList[$key]['price_update'],2);
					}else if($val['goods_type'] == 1){ // 裸钻
						$data['diamond']['total_amount'] += formatRound($val['goods_price'],2);
						$data['diamond']['total_amount_update'] += formatRound($val['goods_price_up'],2);
					}
    			}
    		}
    		$data['data'] = $dataList;
    		$data['status'] = 1;
		}else{
			$data['status'] = 0;
		}
		$this->ajaxReturn($data);
    }

    public function getGoodsTypeByTid($tid){
		return M("goods_sanhuo_type")->where("tid='".$tid."'")->getField('type_name');
	}
	
    // 取用户登陆日志
    public function logininfo($p=1,$n=10) {
    	if(C('new_rules')['zgoods_show']){
      		//周六福
			$this->logininfo_zlf();
			exit;
		}
      $L = M('user_log');
      $count = $L->where('uid='.$this->uId.' and agent_id = '.C('agent_id'))->count();
      $Page = new Page($count,$n);
      $this->loginInfoArr = $L->field('*')->where('uid='.$this->uId.' and agent_id = '.C('agent_id'))->limit($Page->firstRow,$Page->listRows)->select();
      $this->page = $Page->show();
      $this->active = "userinfo";
      $this->act = 'logininfo';
      $this->catShow = 0;
      $this->display('userInfo');
    }

    public function logininfo_zlf($p=1,$n=10){
      	$L = M('user_log');
      	$count = $L->where('uid='.$this->uId.' and agent_id = '.C('agent_id'))->count();
      	$Page = new Page($count,$n);
      	$this->loginInfoArr = $L->field('*')->where('uid='.$this->uId.' and agent_id = '.C('agent_id'))->limit($Page->firstRow,$Page->listRows)->select();
      	$this->page = $Page->show();
      	$this->active = "userinfo";
      	$this->act = 'logininfo';
      	$this->catShow = 0;
    	$this->display('dingzhi/zhouliufu/User/userInfo');
    }
    // 出生日期处理
    protected function makeBirthday($combination=true, $year='', $mothod='', $day='') {
      if ($combination) {
        $result = mktime(0, 0, 0, $mothod, $day, $year);
      } else {
        $date = getdate($year);
        $result->year = $date['year'];
        $result->mothod = $date['mon'];
        $result->day = $date['mday'];
      }
      return $result;
    }

    // 更新用户资料
    public function updateUserInfo() {
      if (IS_POST) {
        $this->active ='userinfo';
        $this->rangDate = $this->yearMothDay();
        $this->act = '';
        $updateDate['sex'] = I('post.sex','','htmlspecialchars');
        $updateDate['email'] = I('post.email','','htmlspecialchars');
        $updateDate['phone'] = I('post.phone','','htmlspecialchars');
        $updateDate['qq'] = I('post.qq','','htmlspecialchars');
        $updateDate['realname'] = I('post.realname','','htmlspecialchars');
        $updateDate['job'] = I('post.job','','htmlspecialchars');
        $updateDate['company_name'] = I('post.company_name','','htmlspecialchars');
        $updateDate['legal'] = I('post.legal','','htmlspecialchars');
        $updateDate['company_address'] = I('post.company_address','','htmlspecialchars');
        $updateDate['birthday'] = $this->makeBirthday(true, I('post.birthdayYear'), I('post.birthdayMonth'), I('post.birthdayDay'));
        $U = M('user');
        if ($U->where("uid=".$this->uId.' and agent_id = '.C('agent_id'))->save($updateDate) !== false) {
          $map['uid'] = htmlspecialchars($this->uId);
          $map['satatus']  = 1;
          $map['agent_id'] = C('agent_id');
          $info = $U->where($map)->find();
          $birthdayBack = $this->makeBirthday(false, $info['birthday']);
          $info['birthdayYear'] = $birthdayBack->year;
          $info['birthdayMonth'] = $birthdayBack->mothod;
          $info['birthdayDay'] = $birthdayBack->day;
          $this->saveUserInfoToSession($info); //
          $this->ajaxReturn(array('msg'=>L('L9044'),'success'=>'true','error'=>'false','url'=>'userInfo'));
        } else {
        	$this->ajaxReturn(array('msg'=>L('L9043'),'success'=>'false','error'=>'true','url'=>'userInfo'));
        }
      } else {
        $this->display('index');
      }
    }
    // 用户资料
    public function userInfo() {
    	//周六福
      	if(C('new_rules')['zgoods_show']){
			$this->userInfo_zlf();
			exit;
		}
    	$where = " uid = ".$_SESSION['web']['uid'];
		$userInfo = M("user")->where($where)->find();
		//print_r($userInfo);
		$this->userInfo = $userInfo;
		$this->active ='userinfo';
		$this->act ='userinfo';
      	$this->rangDate = $this->yearMothDay();
      	$this->display();
    }

    //周六福
    public function userInfo_zlf(){
    	$where = " uid = ".$_SESSION['web']['uid'];
		$userInfo = M("user")->where($where)->find();
		//print_r($userInfo);
		$this->userInfo = $userInfo;
		$this->active ='userinfo';
		$this->act ='userinfo';
      	$this->rangDate = $this->yearMothDay();
    	$this->display('dingzhi/zhouliufu/User/userInfo');
    }

    // 删除用户自己的消息
    public function deleteMyMsg() {
      if (IS_POST) {
        $result = array('error'=>'no','msg'=>L('L9017'));
        $msg_id = I('post.msg_id', '', 'htmlspecialchars');
        $M = M('user_msg');
        if ($M->where('msg_id = '.$msg_id .' and agent_id = '.C('agent_id'))->delete()) {
          $result = array('error'=>'no','msg'=>L('L298'), 'backUrl'=>'sendMessages.html');
        } else {
          $result = array('error'=>'yes','msg'=>L('L299'));
        }
        $this->sendRepsone($result);
      } else {
        $this->display('index');
      }
    }
    // 添加新的消息
    public function addMessage() {
      if (IS_PSOT) {
        $result = array('error'=>'yes','msg'=>L('L9051'));
        $map['msg_type'] = 2;
        $map['parent_type'] = 1;
        $map['parent_id'] = 0;
        $map['uid'] = $this->uId;
        $map['title'] = I('post.msg_title', '', 'htmlspecialchars');
        $map['content'] = I('post.msg_content', '', 'htmlspecialchars');
        $map['create_time'] = time();
        $map['agent_id'] = C('agent_id');
        $M = M('user_msg');
        if ($M->data($map)->add()) {
          $result = array('error'=>'no','msg'=>L('L9052'), 'backUrl'=>'sendMessages.html');
        }
        $this->sendRepsone($result);
      } else {
        $this->display('index');
      }
    }
    // 我的留言
    public function myMessages() {
      $this->act = 'myMessages';
      $this->active = "userinfo";
      $M = M('user_msg');
      $this->display('index');
    }
    // 已发送的消息
    public function sendMessages($p=1,$n=8) {
      $this->act = 'sendmessages';
      $M = M('user_msg');
      $count = $M->where('msg_type = 2 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->count();
      $Page = new Page($count,$n);
      $this->sendMsgList = $M->where('msg_type = 2 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->limit($Page->firstRow,$Page->listRows)->select();
      $this->page = $Page->show();
      $this->display('index');
    }
    // 消息中心
    public function userMessages($p=1,$n=10) {
      $this->act = 'userMessages';
      $this->active = 'userMessages';
      $M = M('user_msg');
      $count = $M->where('msg_type = 1 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))->count();
      $Page = new Page($count,$n);
      $this->messagesList = $M->where('msg_type = 1 and uid = '.$this->uId.' and agent_id = '.C('agent_id'))
        ->order('create_time desc, is_show asc')->limit($Page->firstRow,$Page->listRows)->select();
      $this->page = $Page->show();
      $this->catShow = 0;
      $this->display();
    }
    // 设置用户消息是否已看
    public function isShow($msg_id) {
      if (M('user_msg')->where('msg_id='.$msg_id.' and agent_id = '.C('agent_id'))->save(array('is_show'=>1))) {
        $result = array('error'=>'no', 'msg'=>L('L9076'), 'backUrl'=>'');
      } else {
        $result = array('error'=>'yes', 'msg'=>L('L9075'), 'backUrl'=>'');
      }
      $this->sendRepsone($result);
    }
    // 用户邮箱检查
    public function regcheckemail() {
      $checkRegUser = M('user');
      $map['email'] = htmlspecialchars($_POST['email']);
      $map['satatus']  = 1;
      $map['agent_id'] = C('agent_id');
      $info = $checkRegUser->field('*')->where($map)->find();
      if($info) {
        $result = array('error'=>'yes','msg'=>L('L824'));
      } else {
        $result = array('error'=>'no','msg'=>L('L9001'));
      }
      $this->sendRepsone($result);
    }
    // 用户名检查
	public function regcheckusername() {
       $checkRegUser    = M('user');
       $map['username'] = htmlspecialchars($_POST['reg_username']);
       $map['agent_id'] = C('agent_id');
       $info = $checkRegUser->field('*')->where($map)->find();
       if($info) {
	   		$result = array('error'=>'yes','msg'=>L('L823'));
       } else {
         	$result = array('error'=>'no','msg'=>L('L9001'));
       }
       $this->ajaxReturn($result);
    }
    // 保存用户信息到session
    public function saveUserInfoToSession($info) {
      $_SESSION['web']['uid'] = $info['uid'];
      $_SESSION['web']['username'] = $info['username'];
      $_SESSION['web']['realname'] = $info['realname'];
      $_SESSION['web']['sex'] = $info['sex'];
      $_SESSION['web']['phone'] = $info['phone'];
      $_SESSION['web']['birthday'] = $info['birthday'];
      $_SESSION['web']['birthdayYear'] = $info['birthdayYear'];
      $_SESSION['web']['birthdayMonth'] = $info['birthdayMonth'];
      $_SESSION['web']['birthdayDay'] = $info['birthdayDay'];
      $_SESSION['web']['email'] = $info['email'];
      $_SESSION['web']['qq'] = $info['qq'];
      $_SESSION['web']['job'] = $info['job'];
      $_SESSION['web']['legal'] = $info['legal'];
      $_SESSION['web']['company_name'] = $info['company_name'];
      $_SESSION['web']['company_address'] = $info['company_address'];
      $_SESSION['web']['company_lincense'] = $info['company_lincense'];
      $_SESSION['web']['rank_id'] = $info['rank_id'];
      $_SESSION['web']['reg_time'] = $info['reg_time'];
      $_SESSION['web']['last_logintime'] = $info['last_logintime'];
      $_SESSION['web']['last_ip'] = $info['last_ip'];
      $_SESSION['web']['is_validate'] = $info['is_validate'];
      $_SESSION['web']['note'] = $info['note'];
      $_SESSION['web']['parent_type'] = $info['parent_type'];
      $_SESSION['web']['parent_id'] = $info['parent_id'];
      $_SESSION['web']['shouXinEdu'] = $info['shouXinEdu'];
      $_SESSION['web']['diamondDiscount'] = $info['diamondDiscount'];
      $_SESSION['web']['sanhuoDiscount'] = $info['sanhuoDiscount'];
      $_SESSION['web']['interestRate'] = $info['interestRate'];
    }
    // 用户登陆
    public function login(){
        if (IS_POST) {
            if (empty($_POST['username']) || empty($_POST['password'])) {
                $result = array('error'=>'yes', 'msg'=>L('L9003'));
            } else {
              $U = M('user');
              $map['username'] = htmlspecialchars($_POST['username']);
              $map['parent_type'] = 1;
              $map['agent_id'] = C('agent_id');
              $info = $U->field('*')->where($map)->find();

              if($info && $info['password'] === pwdHash(I('post.password','', 'htmlspecialchars'))){
                // 保存到session
                $this->saveUserInfoToSession($info);
                $str = L('L9004')."：".date('Y-m-d H:i:s', empty($info['last_logintime']) ? time() : $info['last_logintime']);
                // 更新用户登陆信息
                $loginData['last_logintime'] = time();
                $loginData['last_ip'] = get_client_ip();
                // 保存用户登陆日志
                if (($this->saveLoginInfo($info['uid'])) && ($U->where("uid=".$info['uid'])->save($loginData))) {
                  $result = array('msg'=>L('L9005').$str,'error'=>'no',
                    'backUrl'=>'/Home/Goods/diamond.html');
                } else {
                  $result = array('msg'=>L('L9045'),'error'=>'yes','backUrl'=>'/index.html');
                }
              }else{
                $result = array('msg'=>L('L9006'),'error'=>'yes','backUrl'=>'/index.html');
              }
            }
          $this->sendRepsone($result);
        } else {
            $this->action = 'web_login';
            $this->display();
        }
    }
    // 用户退出
    public function loginout() {
      session('web', null);
      $this->uId = null;
      $this->sendRepsone(array('msg'=>L('L9014').$str,'error'=>'no','backUrl'=>'index.html'));
    }
    // 重置用户密码
    public function resetPwd() {
      if (IS_POST) {
        $result  = array('error'=>'yes', 'msg'=>L('L9017'));
        $email = I('post.getpassword_email', '', 'htmlspecialchars');
        if (!empty($email)) {
          if ($_SESSION['web']['getpassword_code'] == I('post.getpassword_code', '', 'htmlspecialchars')) {
            $U = M('user');
            $pa['password'] = pwdHash(strval(I('post.get_password','','htmlspecialchars')));
            if ($U->where("email='".$email."' and agent_id = ".C('agent_id'))->save($pa) !== false) {
              $result  = array('error'=>'no', 'msg'=>L('L9039'), 'backUrl'=>'login.html');
            } else {
              $result['msg'] = L('L9043');
            }
          } else {
            $result['msg'] = L('L9047');
          }
        } else {
          $result['msg'] = L('L9048');
        }
        $this->sendRepsone($result);
      } else {
        $this->display('login');
      }
    }
    // 发送邮件验证码
    public function sendEmailCode() {
      $email = I('post.email', '', 'htmlspecialchars');
      if (empty($email)) {
        $result = array('msg'=>L('L820'),'error'=>'yes');
      } else {
        $fortgotU = M('user');
        $map['email'] = $email;
        $map['satatus'] = 1;
        $map['agent_id'] = C('agent_id');
        $fortInfo = $fortgotU->field('*')->where($map)->find();
        if ($fortInfo) {
          $_SESSION['web']['getpassword_code'] = rand(100000,999999);
          $title = L('L9018');  //标题
          $content = L('L9046').':'.$_SESSION['web']['getpassword_code'];  //邮件内容
          if ($this->sendMail($email,$title,$content)) {
            $result = array('msg'=>L('L825').$email,'error'=>'no','backUrl'=>'login.html');
          } else {
            $result = array('msg'=>L('L9015').$email,'error'=>'yes','backUrl'=>'login.html');
          }
        } else {
          $result = array('msg'=>L('L820'),'error'=>'yes');
        }
      }
      $this->sendRepsone($result);
    }
    // 取指定用户的未读消息
    public function getIsShowMsg() {
        $where = array('uid'=>$this->uId, 'is_show'=>0, 'msg_type'=>1, 'agent_id'=>C('agent_id'));
        $total = M('user_msg')->where($where)->count();
        if (!empty($total)) {
            $result = array('error'=>'no', 'total'=>L('L9091').$total.L('L9092'));
        } else {
            $result = array('error'=>'yes', 'total'=>false);
        }
        $this->sendRepsone($result);
    }
    // 注册 在这里注册的用户是钻明的
    public function reg(){
        if (IS_POST) {
          $result  = array('error'=>'yes', 'msg'=>L('L9017'));
          $regDate['username'] = I('post.reg_username','','htmlspecialchars');
          $regDate['password'] = pwdHash($_POST['reg_password']);
          $regDate['email'] = I('post.email','','htmlspecialchars');
          $regDate['phone'] = I('post.phone','','htmlspecialchars');
          $regDate['agreement'] = I('post.agree','','htmlspecialchars');
          $regDate['reg_time'] = time();
          $regDate['last_logintime'] = time();
          $regDate['last_ip'] = get_client_ip();
          $regDate['rank_id'] = 1;
          $regDate['parent_type'] = 1;
          $regDate['parent_id'] = 0;
          $regDate['agent_id'] = C('agent_id');
          if (empty($regDate['agreement'])) {
            $result['msg'] = L('L9013');
          } elseif (strlen($regDate['username']) < 3) {
            $result['msg'] = L('L9011');
          } elseif (strlen($regDate['phone']) <= 0) {
            $result['msg'] = L('L9009');
          } elseif (strlen($regDate['password']) < 6) {
            $result['msg'] = L('L9010');
          } elseif (strpos($regDate['password'], ' ') > 0) {
            $result['msg'] = L('L9008');
          } else {
            $createU = M('user');
            $insertId = $createU->data($regDate)->add();
            if ($insertId) { // 用户注册成功
              $map['uid'] = htmlspecialchars($insertId);
              $map['satatus'] = 1;
              $map['agent_id'] = C('agent_id');
              $info = $createU->where($map)->find(); // 取到注册的用户信息
              $this->saveUserInfoToSession($info);// 更新session中的用户信息
              $this->saveLoginInfo($insertId); // 更新用户的登陆日志
              $result = array('error'=>'no','msg'=>sprintf(L('L9012'), $regDate['username']),'backUrl'=>U('Home/User/index'));
            } else {
              $result['msg'] = L('L9016');
            }
          }
          $this->ajaxReturn($result);
        } else {
          $this->action = '';
          $this->display('login');
        }
    }
    // 输出到前台
    protected function sendRepsone($result) {
      echo json_encode($result);
    }
    // 生成前台的日期选择
    protected function yearMothDay() {
      for ($i=1955; $i <= date('Y'); $i++) {
        $rangDate['year'][$i] = $i;
      }
      for ($j=1; $j <= 12; $j++) {
        $rangDate['mothod'][$j] = $j;
      }
      for ($k=1; $k <= 31; $k++) {
        $rangDate['day'][$k] = $k;
      }
      return $rangDate;
    }

    // 加入分销商
    public function traderAdd(){
    	if(IS_POST){
    		$_POST['trader']['create_time'] = time();
    		$_POST['trader']['trader_rank'] = 1;//默认等级是白银会员
    		$_POST['trader']['status'] = 0;
    		$_POST['trader']['trader_rank'] = 0;
    		$_POST['trader']['template_id'] = 0;
    		$_POST['trader']['parent_id'] = $this->traderId;
    		$_POST['trader']['create_user_id'] = $_SESSION['web']['uid'];
    		$_POST['trader']['agent_id'] = C('agent_id');
    		//数据验证
    		$rules = array(
    			array('trader_name','require','必须填写分销商名称！'),
    			array('contacts','require','必须填写联系人！'),
    			array('phone','require','必须填写联系电话！'),
    			array('province_id','require','必须选择省份！'),
    			array('city_id','require','必须选择城市！'),
    			array('name','require','必须填写申请内容！'),
    		);
    		$T = M('trader');
    		$res1 = $T->validate($rules)->create($_POST['trader']);
    		//执行数据操作
    		if($res1){
    			$res2 = $T->add();
    			if($res2){
    				$info = '申请成功，请耐心等待我们审核！';
    				$data = array('status'=>'1','msg'=>$info);
    			}else{
    				$info = '申请分销商失败，'.$T->getError();
    				$data = array('status'=>0,'msg'=>$info);
    			}
    		}else{
    			$info = '申请分销商失败，'.$T->getError();
    			$data = array('status'=>0,'msg'=>$info);
    		}
    		//返回信息
    		if(IS_AJAX) $this->ajaxReturn($data);
    		else $res2?$this->success($info):$this->error($info);
    	}else{
    		//省份列表
    		$R = M('region');
    		$this->provinceList = $R->where('parent_id = 1')->select();
    		$this->active = "traderAdd";
    		$this->display();
    	}
    }

    Public function uploadPic(){
        $upload = new \Think\Upload();
        $upload->autoSub = true;
      	$upload->maxSize   =     3145728 ;// 设置附件上传大小
	    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
	    $upload->rootPath  =     './Public/Uploads/Config/'.$this->traderId."/"; // 设置附件上传根目录
	    $upload->savePath  =     'trader/'; // 设置附件上传（子）目录
	    // 上传文件
	    $info   =   $upload->upload();
        $file_newname = $info['userfile']['savename'];
        $MAX_SIZE = 20000000;
        if($info['userfile']['type'] !='image/jpeg' && $info['userfile']['type'] !='image/jpg' && $info['userfile']['type'] !='image/pjpeg' && $info['userfile']['type'] != 'image/png' && $info['userfile']['type'] != 'image/x-png'){
            echo "2";exit;
        }
        if($info['userfile']['size']>$MAX_SIZE)
            echo "上传的文件大小超过了规定大小";

        if($info['userfile']['size'] == 0)
            echo "请选择上传的文件";
        switch($info['userfile']['error'])
        {
            case 0:
                echo '/Public/Uploads/Config/'.$this->traderId."/".$upload->savePath.date('Y-m-d',time())."/".$file_newname;
                break;
            case 1:
                echo "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                break;
            case 2:
                echo "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                break;
            case 3:
                echo "文件只有部分被上传";
                break;
            case 4:
                echo "没有文件被上传";
                break;
        }
    }
    // 根据省份获取城市列表
    public function getAjaxCity(){
    	$province_id = I('province_id',0);
    	$data['data']= "";
    	$data['status'] = 0;
    	if($province_id!=0){
    		$cities = M("region")->where("parent_id=$province_id AND region_type=2")->order("region_id ASC")->select();
    		$data['data'] = $cities;
    		$data['status'] = 1;
    	}
    	$this->ajaxReturn($data);
    }

    // 跳转登录分销商后台
    public function traderAdmin(){

    }

    public function traderList(){
    	$traderList = M("trader")->where("1=1 AND create_user_id=".$_SESSION['web']['uid'].' and agent_id = '.C('agent_id'))->order('create_time DESC')->select();
    	if($traderList){
    		foreach($traderList as $key=>$val){
    			if($val['status'] > 0){
    				$traderList[$key]['status_txt'] = "审核通过";
    			}else{
    				$traderList[$key]['status_txt'] = "未审核或审核未通过";
    			}
    		}
    	}
    	$this->active = "traderList";
    	$this->traderList = $traderList;
    	$this->catShow = 0;
    	$this->display();
    }

    //检测用户登录情况
    public function checkUserLogin(){
    	$data = array('status=0');
    	if($_SESSION['web']['uid']){
    		$data['status'] = 1;
    	}
    	$this->ajaxReturn($data);
    }

    public function getPaymentPrice($order_id){
    	$temp = M('order_receivables')->where("agent_id='".C('agent_id')."' and order_id='$order_id'")->select();
    	if($temp){
    		foreach($temp as $val){
    			$payment_price += formatRound($val['payment_price'],2);
    		}
    	}else{
    		$payment_price = 0;
    	}
    	return $payment_price;
    }

    // 获取多条发货信息
    public function getDelivery($orderId){

		//先获取该订单的退货信息

		$join = " LEFT JOIN zm_order_return ZOR ON ZOR.order_id=ORG.order_id";
		$return_goods = M('order_return_goods')->alias("ORG")->join($join)->where("ORG.agent_id='".C('agent_id')."' and ORG.order_id=$orderId AND ZOR.status = 3")->select();
		/*
		if($return_goods){
			$return_goods_id = "";
			foreach($return_goods as $key=>$val){
				$temp[] = $val['og_id'];
				$order_deliverys = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND uid=".$_SESSION['web']['uid']." AND goods_price !=".$val['goods_price'])->select();
			}
			$return_sql = " AND ( ODG.og_id=".$val['og_id']." AND ODG.goods_number!='".$val['goods_number']."')";
			$return_goods_id = implode(",",$temp);
		}else{

		}
		*/
		//print_r($order_deliverys);
		$order_deliverys = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND uid=".$_SESSION['web']['uid'])->select();
		if($order_deliverys){
			foreach($order_deliverys as $key=>$val){
				$count = M('order_return_goods')->where("agent_id='".C('agent_id')."' and delivery_id='".$val['delivery_id']."' AND order_id=".$orderId)->sum("goods_number");
				$total = M('order_delivery_goods')->where("agent_id='".C('agent_id')."' and delivery_id='".$val['delivery_id']."' AND order_id=".$orderId)->sum("goods_number");

				if($total == $count){
					$order_deliverys[$key]['confirm'] = 1;
				}
				if($val['confirm_type'] == 1){
					$order_deliverys[$key]['confirm_type_txt'] = "后台确认";
				}else if($val['confirm_type'] == 2){
					$order_deliverys[$key]['confirm_type_txt'] = "客户确认";
				}else{
					$order_deliverys[$key]['confirm_type_txt'] = "未确认";
				}

				$join = " LEFT JOIN zm_order_goods as OG ON OG.og_id = ODG.og_id ";
				$where = "ODG.agent_id='".C('agent_id')."' and ODG.delivery_id='".$val['delivery_id']."' AND ODG.order_id='$orderId'";
				if($return_goods_id != ''){
					$where .= $return_sql;
				}

				$orderDeliveryGoods = M('order_delivery_goods')->alias("ODG")->join($join)->field("ODG.goods_number,ODG.goods_price,OG.attribute,OG.goods_type")->where($where)->select();
				if($orderDeliveryGoods){
					foreach($orderDeliveryGoods as $k=>$v){

						$orderDeliveryGoods[$k]['attributes'] = unserialize($v['attribute']);
						$attributes = $orderDeliveryGoods[$k]['attributes'];
						if($v['goods_type'] == 1){
							$orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['certificate_type']." ".$orderDeliveryGoods[$k]['attributes']['certificate_number'];
							$orderDeliveryGoods[$k]['goods_number'] = (int)$orderDeliveryGoods[$k]['goods_number'];
						}else if($v['goods_type'] == 2){
							$orderDeliveryGoods[$k]['goods_number'] = (int)$orderDeliveryGoods[$k]['goods_number'];
							$orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_sn']." 颜色：".$attributes['color']." 净度：".$attributes['clarity'];
						}else if($v['goods_type'] == 3){
							$orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['goods_sn'];
						}else if($v['goods_type'] == 4){
							$orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['goods_sn']." ".$attributes['associateInfo']['material_name'];
						}elseif($v['goods_type'] == 5){
							$orderDeliveryGoods[$k]['goods_name_str'] = $attributes['goods_name']." ".$attributes['goods_sn']." ".$attributes['associateInfo']['material_name'];
						}
					}
				}
				$order_deliverys[$key]['deliveryGoods'] = $orderDeliveryGoods;
			}
		}
		//print_r($order_deliverys);
		return $order_deliverys;
	}

	public function confirmReceiptGoods(){
		$orderId = $_POST['order_id'];
		$deliveryId = $_POST['deliveryId'];
		$order_delivery = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND delivery_id='$deliveryId'")->find();
		$order = M('order')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND uid='".$_SESSION['web']['uid']."'")->find();
		$data = array('status'=>0,'msg'=>"确认收货失败");
		if($order_delivery){
			$order_delivery['confirm_type'] = 2;
			$order_delivery['confirm_time'] = time();

			if(M('order_delivery')->data($order_delivery)->save()){
				$temp = M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id='$orderId' AND confirm_type>0")->select();
				$orderDeliveryPrice = 0;
				foreach($temp as $key=>$val){
					$orderDeliveryPrice += $val['goods_price'];
				}
				$orderPrice = M('order')->where("agent_id='".C('agent_id')."' and order_id='$orderId'")->getField("order_price");
				if($orderDeliveryPrice == $orderPrice){
					$order['order_status'] = 5; // 更新订单表的确认收货。
					M('order')->data($order)->save();
				}
				
				//确认收货，发送信息给商家         2017-08-24		  zengmingming
				$nowAgent_id = C('agent_id');
				$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
				if($sms_agent_info_arr["sms_push_reminder"]){
					$SMS = new \Common\Model\Sms();
					$SMS->SendSmsByType($SMS::USER_CONFIRM_RECEIPT,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
				}

				$data['status'] = 1;
				$data['msg'] = "确认成功";
			}
		}
		$this->ajaxReturn($data);
	}

}
