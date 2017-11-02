<?php
/**
 * 下订单流程相关
 * @author ggc
 */
namespace Home\Controller;
class NewOrderController extends NewHomeController {
	public function _before_orderCart(){
    	$this->seo_title = "购物车";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->catShow = 0;
    	$this->active = "Goods";
    }
    
    public function _before_orderConfirm(){
    	$this->seo_title = "购物车";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->catShow = 0;
    	$this->active = "Goods";
    }
    
    public function _before_orderComplete(){
    	$this->catShow = 0;
    }
    public function orderCart(){
		$where['agent_id'] = C('agent_id');
		$where[$this->userAgentKey] = $this->userAgentValue;
    	$data = $this->getCartGoods($where);
		$sglM = D('GoodsLuozuan');
		$this->yn_certificate_num_cv=$sglM->yn_certificate_num();
		
		//重新拼接匹配数据					
		$i=0;
		foreach($data['luozuan']["data"] as $key=>$val){
			foreach($data['product']["data"] as $k=>$v){
				if($val["dingzhi_id"]!="" && $v["dingzhi_id"]!="" && $val["dingzhi_id"]==$v["dingzhi_id"]){
					$dingzhiArr[]   = $val["id"].",".$v["id"];
					$dingzhi_list[$i]["product"] = $v;	
					$dingzhi_list[$i]["luozuan"] = $val;	
					unset($data['luozuan']["data"][$key]);
					unset($data['product']["data"][$k]);
					$i++;
				}	
			}
		}
    	$this->dingzhi_list = $dingzhi_list;
		
    	$this->luozuan = $data['luozuan'];
        $this->sanhuo  = $data['sanhuo'];
    	$this->product = $data['product'];
    	$this->data = $data['cartGoods'];
    	$this->count = $data['count'];
    	$this->total = $data['total'];
		header("Expires: -1");
		header("Cache-Control: no_cache");
		header("Pragma: no-cache");
    	$this->display();
    }
    
    public function getOrderProduct($goods_type,$gid){
    	switch($goods_type){
    		case 1:
    		// 裸钻
    		
    		break;
    		case 2:
    		// 散货
    		break;
    		case 3:
    		// 成品
    		break;
    		case 4:
    		// 戒托
    		break;
    		case 5:
    		// 代销
    		break;
    	}
    	return $product;
    }
    
    public function getLuozuan($gid){
    	
    }
	
	// 订单购物车
	//2016-3-30修复散货计算最新价格
	//	 *	del_no_goods 去掉无货  1是 0否
	//	 *	goods_jiagong    1,不对购物车中的货品进行加工；0加工
	public function getCartGoods($where, $del_no_goods = 0, $goods_jiagong = 0){
		
		$cartGoods = D('Common/Cart')->getUpdateCartGoodsList($where, $_SESSION['web']['uid']);
		if($del_no_goods == 1){
	    	$cartGoods = D('Common/Cart')->delNullGoods($cartGoods);
	    }
	    if($goods_jiagong == 1){
		    return $cartGoods;
		}

		$luozuan_shape_array      = M('goods_luozuan_shape')->getField('shape_id,shape_name');
		$luozuan_shape_name_array = M('goods_luozuan_shape')->getField('shape,shape_name');
		$deputystoneM             = M('goods_associate_deputystone');
		$luozuan = array();
		$sanhuo  = array();
		$product = array();
		if($cartGoods){  
			foreach($cartGoods as $key=>$val){
				$cartGoods[$key]['goods_attrs'] = $val['goods_attrs'] = unserialize($val['goods_attr']);

				if($cartGoods[$key]['goods_attrs']['attribute']){
					$cartGoods[$key]['goods_attrs']['attributes'] = unserialize($cartGoods[$key]['goods_attrs']['attribute']);
				}                       
				$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_name'] =  $luozuan_shape_array[$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_id']];
				
				 $cartGoods[$key]['goods_attrs']['deputystone_name']  = "";
				 $cartGoods[$key]['goods_attrs']['deputystone_price'] = "";

				 if($cartGoods[$key]['goods_attrs']['deputystone']){
				 	$deputystone = $deputystoneM->where(array('gad_id'=>$cartGoods[$key]['goods_attrs']['deputystone']['gad_id']))->select();
				 	if($deputystone){
				 		$cartGoods[$key]['goods_attrs']['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
				 		$cartGoods[$key]['goods_attrs']['deputystone_price'] = $deputystone[0]['deputystone_price'];
				 	}
				 }

				switch($val['goods_type']){
					case 1: // 1 表示裸钻
						$luozuan['count'] += 1;
						$luozuan['weight'] += $cartGoods[$key]['goods_attrs']['weight'];
						
						//查出是否存在特惠钻石
						$preferential_luozuan	= M('goods_preferential')->where(array('agent_id'=>C('agent_id'),'gid'=>$cartGoods[$key]['goods_attrs']['gid']))->find();
						$cartGoods[$key]['goods_attrs']['preferential_id']	= $preferential_luozuan['id'];
						$cartGoods[$key]['goods_attrs']['pre_discount']		= $preferential_luozuan['discount'];
						
						$xiuzheng = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $_SESSION['web']['uid'], 'luozuan',  'single');						
						
						$cartGoods[$key]['goods_attrs']['meikadanjia']   = $xiuzheng['cur_price'];  
						$cartGoods[$key]['goods_attrs']['price']  		 = $xiuzheng['price'];  							
						$cartGoods[$key]['goods_attrs']['shape_name']	 = $luozuan_shape_name_array[$cartGoods[$key]['goods_attrs']['shape']];
						$cartGoods[$key]['status']  = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
						//判断钻石是否匹配到戒托中。
						$cartGoods[$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
						$luozuan['data'][] = $cartGoods[$key];
						$luozuan['total'] += formatRound($cartGoods[$key]['goods_attrs']['price'],2);   
					break;
					case 2:// 散货
						//查出产品的原价格
						$goodsInfo = M('goods_sanhuo')->where(array('agent_id'=>C('agent_id'),'goods_id'=>$cartGoods[$key]['goods_id']))->find();
						$xiuzheng  = getGoodsListPrice($goodsInfo, $_SESSION['web']['uid'], 'sanhuo', 'single');
						$price 	   = $xiuzheng['goods_price'];
						$sanhuo['weight'] += $cartGoods[$key]['goods_number']; //钻石重量

						$cartGoods[$key]['goods_attrs']['type_name'] = M("goods_sanhuo_type")->where("tid=".$cartGoods[$key]['goods_attrs']['tid'])->getField("type_name"); 
						$cartGoods[$key]['goods_attrs']['marketPrice'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*1.5,2);
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*$cartGoods[$key]['goods_number'],2);     
						$sanhuo['data'][] = $cartGoods[$key]; 
						$sanhuo['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2); 
						$sanhuo['count'] += 1;
					break;
					case 3:     // 成品 
						$product['count'] += $val['goods_number'];
						//$cartGoods[$key]['goods_attrs']['category_name'] = M("goods_category_config")->where("category_id=".$cartGoods[$key]['goods_attrs']['category_id'])->getField("name_alias");
						$attr = explode("^",$cartGoods[$key]['goods_attrs']['goods_sku']['attributes']);		                
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
		                    $cartGoods[$key]['goods_attrs']['attrs']    = trim($attrs);		                   
		                }
						//print_r($cartGoods[$key]);exit;
						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$chengpin_price = $cartGoods[$key]['goods_attrs']['activity_price'];
						}else{
							$chengpin_price = $cartGoods[$key]['goods_attrs']['goods_price'];
						}
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($chengpin_price*$cartGoods[$key]['goods_number'],2);                       
						$product['data'][] = $cartGoods[$key]; 
						$product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
						break;
					case 4:// 戒托   
						//获取材质名称
						$cartGoods[$key]['goods_attrs']['associateInfo']['material_name'] = M("goods_material") -> where(array('material_id'=>$cartGoods[$key]['goods_attrs']['associateInfo']['material_id']))->getField('material_name');
						$product['count']                               += $val['goods_number'];
						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$dingzhi_price = $cartGoods[$key]['goods_attrs']['activity_price'];
						}else{
							$dingzhi_price = $cartGoods[$key]['goods_attrs']['goods_price'];
						}
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($dingzhi_price*$cartGoods[$key]['goods_number'],2);      
						$product['data'][]                              = $cartGoods[$key];  
						$product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
					break;
					case 16://szzmzb 板房商品
						$data['dingzhi_goods_attr'] = unserialize($val['goods_attr']);
							
						if($data['dingzhi_goods_attr']['selected']['goods_name_bm']){ //情侣戒副款显示
							$cartGoods[$key]['goods_attrs']['goods_name'] = $cartGoods[$key]['goods_attrs']['goods_name'].'('.$data['dingzhi_goods_attr']['selected']['goods_name_bm'].')';
						}
						$cartGoods[$key]['goods_attrs']['associateInfo']['material_name'] = $data['dingzhi_goods_attr']['selected']['material_name'];
						$cartGoods[$key]['goods_attrs']['hand'] = $data['dingzhi_goods_attr']['selected']['hand'];
						$cartGoods[$key]['goods_attrs']['word'] = $data['dingzhi_goods_attr']['selected']['word'];
						$cartGoods[$key]['goods_attrs']['goods_price'] = formatRound($data['dingzhi_goods_attr']['goods_price'],2);
						$cartGoods[$key]['goods_attrs']['activity_price'] = formatRound($data['dingzhi_goods_attr']['activity_price'],2);
						$cartGoods[$key]['goods_attrs']['activity_status'] = $data['dingzhi_goods_attr']['activity_status'];
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($data['dingzhi_goods_attr']['activity_price']*$cartGoods[$key]['goods_number'],2);
						}else{
							$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($data['dingzhi_goods_attr']['goods_price']*$cartGoods[$key]['goods_number'],2);
						}
						
						$cartGoods[$key]['goods_attrs']['attrs'] = '';
						$cartGoods[$key]['goods_attrs']['jewelry_name'] = $data['goods_attrs']['price_info']['price_info']['base_process_info']['jewelry_name'];

						$product['count'] += $val['goods_number'];
						$product['data'][] = $cartGoods[$key];
						$product['total'] += $cartGoods[$key]['goods_attrs']['goods_price2'];
					break;
				}
			}
		}                 
		$total                = $product['total'] + $luozuan['total'] + $sanhuo['total'];
		$data['luozuan']      = $luozuan;
		$data['sanhuo']       = $sanhuo;  
		$data['count']        = $product['count'] + $luozuan['count'] + $sanhuo['count'];
		$data['product']      = $product;
		$data['cartGoods']    = $cartGoods; 
		$data['total']        = $total;
		$data['total_weight'] = $sanhuo['weight'] + $luozuan['weight'];
		return $data;
	}
	
	public function matchedDiamondInCart($certificate_number){
		$data = M('cart')->where("agent_id='".C('agent_id')."' and goods_attr like '%$certificate_number%'")->select();
		if($data){
			foreach($data as $key=>$val){
				$val['goods_attr'] = unserialize($val['goods_attr']);
				if(strstr($val['goods_attr']['matchedDiamond'],$certificate_number)){
					return true;
				}
			}
		}
	}
	
	// 根据裸钻的证书查询购物车中是否有客户订购
	public function getDiamondStatus($certificate_number){
		$array = array("certificate_number"=>$certificate_number);
		$arraySerialize = serialize($array);
		$arraySerialize =  substr($arraySerialize,5,-1);
		$where = "agent_id='".C('agent_id')."' and goods_attr like '%$arraySerialize%' AND uid!='".$_SESSION['web']['uid']."'";
		
		if(M('cart')->where($where)->find()){
			return "待定";
		}else{
			return "有货";
		}
	}
	
	//更改成品的订购数量 
	
	public function changeGoodsNumber(){
		$goods_number = I('post.goods_number',1);
		$cart_id = I('post.cart_id',0); 
		$goods_type = I("post.goods_type",1,'intval');
		$agent_id = C('agent_id');
		//限购判断
		switch($goods_type){
			case 3:
				if($goods_number >500 ){
					$result['status'] = 0;
					$result['msg'] = '成品限购500个';
					$this->ajaxReturn($result);
				}
				break;
			case 4:
				if($goods_number >50 ){
					$result['status'] = 0;
					$result['msg'] = '订制产品限购50个';
					$this->ajaxReturn($result);
				}
				break;
		}
		
		$cartGoods = M('cart')->where(array($this->userAgentKey=>$this->userAgentValue,'agent_id'=>$agent_id,'id'=>$cart_id))->find();
		if(!$cartGoods){
			$result['status'] = 0;
			$result['msg'] = '该产品不存在';
			$this->ajaxReturn($result);
		}
		$cartGoods['goods_attrs'] = unserialize($cartGoods['goods_attr']);
		if($goods_type == 3){
			$stock = $cartGoods['goods_attrs']['goods_number'];
			if($goods_number > $stock){
				$result['status'] = 101;
				$result['msg'] = '购买数量超过库存！现库存为'.$stock;
				$result['goods_number'] = $cartGoods['goods_number'];
				$this->ajaxReturn($result);
			}
		}
		
		$change_number = $goods_number-$cartGoods['goods_number'];
		
		if($cart_id != 0 && !empty($cartGoods)){
			$cartGoods['goods_number'] = floatval($goods_number);
			if(M('cart')->data($cartGoods)->save()){
				//如果为活动商品则取值活动价格，否则取值普通售卖价格
				if(in_array($cartGoods['goods_attrs']['activity_status'],array('0','1'))){
					$price = $cartGoods['goods_attrs']['activity_price'];
				}else{
					$price = $cartGoods['goods_attrs']['goods_price'];
				}
				$change_price = formatRound($change_number*$price);
				$result['status'] = 100;
				$result['msg'] = "修改成功";
				$result['goods_type'] = $goods_type;
				$result['goods_number'] = $cartGoods['goods_number'];
				$result['change_number']= $change_number;
				$result['change_price'] = $change_price;
			}else{
				$result['status'] = 102;
				$result['msg'] = "修改失败";
			}
		}                    
		$this->ajaxReturn($result);
	}
	
	// 删除购物车中的货品
	
	public function cartDelete(){
		$id = I("post.thisid",'');
		$goods_type = I("goods_type",0);
		if(empty($id)){
			$data['status'] = 0;
			$data['msg'] = '请选择产品';
			$this->ajaxReturn($result);
		}
		$where['agent_id'] = C('agent_id');
		$where['id'] = array('in',$id);
		$where[$this->userAgentKey] = $this->userAgentValue;
		if($goods_type>0){
			$where['goods_type'] = $goods_type;
		}
		$cartGoods = M('cart')->where($where)->select();
		$data = array('status'=>0,'msg'=>"购物车中的货品删除失败");
		if(empty($cartGoods)){
			$data['msg'] = "购物车中不存在该货品";
		}else{
			if(M('cart')->where($where)->delete()){
				$data['status'] = 100;
				$data['msg'] = "购物车中的货品删除成功";
			}
		}
		$this->ajaxReturn($data);
	}
	 
	/**
	 * auth	：fangkai
	 * content：购物车选中商品跳转中间项，进行base64加密，重定向到orderConfirm页面
	 * time	：2016-7-12
	**/
	public function choseOrder(){
		
		$uid = $_SESSION['web']['uid'];
		if(IS_POST){
			$thisid = I('post.thisid');
			if(empty($uid)){
				$data['msg']= "请先登陆再购买";$data['status'] = 101;$data['url']="/Home/Public/login";	$this->ajaxReturn($data);
			}
			if(empty(id)){
				$data['msg']= "没有选择产品";$data['status'] = 101; $this->ajaxReturn($data);
			}
			$data['status'] = 100; $this->ajaxReturn($data);
			exit();
		}
		if(empty($uid)){
			$this->error('请先登陆再购买',U('/Home/Public/login'));
		}
		$id = I('get.id','');
		if(empty(id)){
			$this->error('没有选择产品',U('/Home/Order/orderCart'));
		}
		$encryption_id = base64_encode($id);
		$url = '/Home/Order/orderConfirm?cart_id='.$encryption_id;
		$this->redirect($url);
	}
	
	/**
	 * auth	：someone ，fangkai
	 * content：购物车确认页面
	 * updatetime	：2016-7-12
	**/
	public function orderConfirm(){
	 	setcookie('cartStep','confirm');
		$cart_id = I('cart_id','');
		if(empty($cart_id)){
			$this->error('没有选择产品',U('/Home/Order/orderCart'));
		}
		$cart_id = base64_decode($cart_id);
		$where['agent_id'] = C('agent_id');
		$where['id']  = array('in',$cart_id);
		$where[$this->userAgentKey] = $this->userAgentValue;
		//获取购买的商品信息
		$data = $this->getCartGoods($where, 1);
	 	if($data['total'] == 0){
	 		$this->error('产品不存在',U('/Home/Order/orderCart'));
	 	}
		//获取地址
	 	
		$this->user_address = D('Common/UserAddress')->getAddressList($_SESSION['web']['uid']);
		
		/* 查出省级地区 */
		$region = M('region');
		$province = $region->where(array('region_type' => 1))->select(); 
		$this->province	= $province;
		
		/* 支付模式 */
		$PM = M('payment_mode');
		$pay_where['pm.agent_id'] =  C('agent_id');
		$pay_where['pc.pay_status'] =  1;
		$join[] = 'LEFT JOIN zm_payment_config AS pc ON pc.mode_id = pm.mode_id';
		$field = 'pm.*,pc.pay_status,pc.pay_attr';
		$this->payModeList = $PM->alias('pm')->join($join)->field($field)->where($pay_where)->select();
		
		/* 配送方式 */
		$this->deliModeList = D('Common/OrderPayment')->this_dispatching_way();
		

		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}

		// 环迅支付绑定
        $this->ipspay = 0;
        if($agent_config && strlen($agent_config['ips_account'])==10 && strlen($agent_config['ips_mer_code'])==6 && !empty($agent_config['ips_mer_cert'])){
            $this->ipspay = 1;
        }

		$this->LinePayMode = M('payment_mode_line')->where(' atype = 1 and  agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付
		
		$sglM = D('GoodsLuozuan');
		$this->yn_certificate_num_cv=$sglM->yn_certificate_num();		
		
		$this->cart_id = $cart_id;
		
		//重新拼接匹配定制产品数据					
		$i=0;
		foreach($data['luozuan']["data"] as $key=>$val){
			foreach($data['product']["data"] as $k=>$v){
				if($val["dingzhi_id"]!="" && $v["dingzhi_id"]!="" && $val["dingzhi_id"]==$v["dingzhi_id"]){
					
					
					$dingzhiArr[]   = $val["id"].",".$v["id"];
					$dingzhi_list[$i]["product"] = $v;	
					$dingzhi_list[$i]["luozuan"] = $val;	
					unset($data['luozuan']["data"][$key]);
					unset($data['product']["data"][$k]);
					$i++;
				}	
			}
		}
    	$this->dingzhi_list = $dingzhi_list;
		
    	$this->luozuan = $data['luozuan'];
        $this->sanhuo = $data['sanhuo'];
    	$this->product = $data['product'];
    	$this->total = $data['total']; 
		$this->count = $data['count'];
    	$this->display();
	 }
	 
	 public function orderSubmit(){
		$GC				= D('Common/GoodsCategory');
	 	$cart_id        = I('post.cart_id','');		 //选中购买的商品在购物车中的ID
		$psfs	        = I('post.psfs','','intval'); //配送方式
		$zffs	        = I('post.zffs','','intval'); //支付方式
		$address_id     = I('post.address_id',''); //收货地址ID,若值为booked，则为预约到店,2016-11-22
		
 
		if($zffs != 0 && $zffs != 1 && $zffs != 2&& $zffs != 3&& $zffs != 4){
			$result['status']	= 0;
			$result['info']		= '请选择支付方式';
			$this->ajaxReturn($result);
		}
		
		if($address_id == 'booked'){
			$Store		= new \Common\Model\Store();
			$saveData['store_id']		= I('post.store_id','','intval');
			$saveData['time']			= I('post.time');
			$saveData['name']			= I('post.name');
			$saveData['type']			= I('post.type');
			$saveData['phone']			= I('post.phone');
			$saveData['content']		= I('post.content');
			if(empty($saveData['store_id'])){
				$result['status']	= 0;
				$result['info']		= '请选择体验店';
				$this->ajaxReturn($result); 
			}
			$checkStore	= $Store->getStoreInfo($saveData['store_id']);
			if($checkStore == false){
				$result['status']	= 0;
				$result['info']		= '该门店不存在';
				$this->ajaxReturn($result); 
			}
			if(strtotime($saveData['time']) < time()){
				$result['status']	= 0;
				$result['info']		= '预约时间不能小于当前时间';
				$this->ajaxReturn($result); 
			}
			if($saveData['name'] == ''){
				$result['status']	= 0;
				$result['info']		= '收货人不能为空';
				$this->ajaxReturn($result);  
			}
			if(!preg_match('/^(13[0-9]|15[0-9]|17[678]|18[0-9]|14[57])[0-9]{8}$/',$saveData['phone'])){
				$result['status'] 	= 0;
				$result['info']		= '手机格式不正确';
				$this->ajaxReturn($result);  
			}
			if(mb_strlen($saveData['content']) > 200 ){
				$result['status'] 	= 0;
				$result['info']		= '留言不能超过200个字符';
				$this->ajaxReturn($result);  
			}
			
		}
		$where['agent_id'] = C('agent_id');
		$where['uid']      = $_SESSION['web']['uid'];
		$where['id']       = array('in',$cart_id);
	 	$dataList = $this->getCartGoods($where, 1,1);

	 	if(empty($dataList)){
	 		$this->redirect("/Home/Order/orderConfirm");
	 	} 
		$amount            = '0';
		$order_goods       = array(); 
		foreach($dataList as $key=>$val){ 
			$dataList[$key]['data'] = $goods = unserialize($val['goods_attr']);
			$order_goods[$key]['goods_price_a_up'] = 0; 
			$order_goods[$key]['goods_price_b'] = 0;
			$order_goods[$key]['goods_price_b_up'] = 0;
			$order_goods[$key]['goods_price_c'] = 0;
			$order_goods[$key]['goods_price_c_up'] = 0; 
			$order_goods[$key]['attribute'] = $val['goods_attr'];
			$order_goods[$key]['goods_type'] = $val['goods_type'];
			$order_goods[$key]['parent_type'] = 1;
			$order_goods[$key]['parent_id'] = $this->traderId;
			if($val['goods_type'] ==1 ){      // 1为裸钻

				$order_goods[$key]['goods_number']  = 1;
				$order_goods[$key]['certificate_no']= $goods['certificate_number'];	
				//查出是否存在特惠钻石
				$preferential_luozuan	= M('goods_preferential')->where(array('agent_id'=>C('agent_id'),'gid'=>$goods['gid']))->find();
				$goods['preferential_id']	= $preferential_luozuan['id'];
				$goods['pre_discount']		= $preferential_luozuan['discount'];
				
				$goods = getGoodsListPrice($goods, $_SESSION['web']['uid'], 'luozuan', 'single');
				$dataList[$key]['data']['price']    = $goods['price'];  //国际报价*汇率*折扣*重量/100
				$order_goods[$key]['goods_price'] 	= formatRound($dataList[$key]['data']['price'],2);
				$order_goods[$key]['goods_id']    	= $dataList[$key]['data']['gid'];
				$order_goods[$key]['luozuan_type']  = $dataList[$key]['data']['luozuan_type'];
				$amount += formatRound($dataList[$key]['data']['price'],2);
				
			}else if($val['goods_type'] ==2){  //散货

				$order_goods2[$key]['attribute'] = unserialize($order_goods[$key]['attribute']);
				//查出产品的原价格
                $GSobj = D('GoodsSanhuo');
                $where = array('goods_id'=>array('in',$val['goods_id']));
                $goods = $GSobj -> getInfo($where);
				$goods = getGoodsListPrice($goods, $_SESSION['web']['uid'], 'sanhuo', 'single');
				$dataList[$key]['data']['goods_price'] = $goods['goods_price'];
				$order_goods[$key]['goods_price2'] = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);
				if($order_goods[$key]['attribute']['goods_price2'] != $order_goods[$key]['goods_price2']){
					$order_goods2[$key]['attribute']['goods_price'] = $dataList[$key]['data']['goods_price'];
					$order_goods2[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
					$save['goods_attr'] = serialize($order_goods2[$key]['attribute']);
					$action = M('cart')->where(array('agent_id'=>C('agent_id'),'uid'=>$_SESSION['web']['uid'],'goods_id'=>$val['goods_id']))->save($save);
				}
				
				
				$order_goods[$key]['goods_number']              = $val['goods_number'];
				$order_goods[$key]['certificate_no']            = $val['goods_sn'];
				$order_goods[$key]['goods_price']               = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute']['goods_price']  = $dataList[$key]['data']['goods_price'];
				$order_goods[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute']                 = serialize($order_goods2[$key]['attribute']);
				$order_goods[$key]['goods_id']                  = $dataList[$key]['data']['goods_id']; 
				$amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2); 			 	
			}else if($val['goods_type'] == 3){   // 3成品
				$order_goods[$key]['goods_number']                      = $val['goods_number'];
				$order_goods[$key]['certificate_no']                    = $dataList[$key]['data']['goods_sn'];
				$order_goods[$key]['data']['goods_attrs']['goods_name'] = addslashes($order_goods[$key]['data']['goods_attrs']['goods_name']);
				$order_goods[$key]['goods_id']                          = $dataList[$key]['data']['goods_id'];
				$order_goods[$key]['category_id']	 = $dataList[$key]['data']['category_id'];
				$cate_where['category_id']			 = $dataList[$key]['data']['category_id'];
				$catInfo = $GC->getCategoryInfo($cate_where);
				if($catInfo[0]['pid'] == 0){
					$order_goods[$key]['p_category_id'] = $catInfo[0]['category_id'];
				}else{
					$order_goods[$key]['p_category_id'] = $catInfo[0]['pid'];
				}
				
				//如果为活动商品则取值活动价格，否则取值普通售卖价格
				if(in_array($dataList[$key]['data']['activity_status'],array('0','1'))){
					$price = $dataList[$key]['data']['activity_price'];
				}else{
					$price = $dataList[$key]['data']['goods_price'];
				}
				$order_goods[$key]['goods_price']    = formatRound($price*$val['goods_number'],2); 
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['activity_price'];
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];
				
				$amount += formatRound($price*$val['goods_number'],2);  
			}else if($val['goods_type'] ==4){  //戒托 
				$order_goods[$key]['goods_number']   = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $dataList[$key]['data']['goods_sn']; 
				$order_goods[$key]['goods_id']       = $dataList[$key]['data']['goods_id']; 
				$order_goods[$key]['category_id']	 = $dataList[$key]['data']['category_id'];
				$cate_where['category_id']			 = $dataList[$key]['data']['category_id'];
				$catInfo = $GC->getCategoryInfo($cate_where);
				if($catInfo[0]['pid'] == 0){
					$order_goods[$key]['p_category_id'] = $catInfo[0]['category_id'];
				}else{
					$order_goods[$key]['p_category_id'] = $catInfo[0]['pid'];
					
				}
				//如果为活动商品则取值活动价格，否则取值普通售卖价格
				if(in_array($dataList[$key]['data']['activity_status'],array('0','1'))){
					$price = $dataList[$key]['data']['activity_price'];
				}else{
					$price = $dataList[$key]['data']['goods_price'];
				}
				$order_goods[$key]['goods_price']    = formatRound($price*$val['goods_number'],2); 
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['activity_price'];				//活动价格单价
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];				//是否为活动商品
				
				$amount += formatRound($price*$val['goods_number'],2); 			 	
			}else if($val['goods_type'] == 16){ //szzmzb 板房
				$data['dingzhi_goods_attr'] = unserialize($val['goods_attr']);

				$order_goods[$key]['goods_number']   = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['goods_id'] = $val['goods_id'];
				$order_goods[$key]['category_id'] = 0;
				$order_goods[$key]['p_category_id'] = 0;

				//如果为活动商品则取值活动价格，否则取值普通售卖价格
				if(in_array($data['activity_status'],array('0','1'))){
					$price = $data['dingzhi_goods_attr']['activity_price'];
				}else{
					$price = $data['dingzhi_goods_attr']['goods_price'];
				}
				$order_goods[$key]['goods_price']    = formatRound($price*$val['goods_number'],2); 
				$order_goods[$key]['activity_price'] = $data['dingzhi_goods_attr']['activity_price'];				//活动价格单价
				$order_goods[$key]['activity_status']= $data['dingzhi_goods_attr']['activity_status'];

				$amount += formatRound($price*$val['goods_number'],2); 
			}

			$order_goods[$key]['goods_number_up'] = 0; 
			$order_goods[$key]['update_time'] = time(); 
			$order_goods[$key]['agent_id'] = C('agent_id'); 
			$order_goods[$key]['dingzhi_id']  = $val['dingzhi_id'];  
		}
		$data['note_a']        = I('orderInfo');
		$data['order_goods']   = $order_goods;
		$data['order_sn']      = date("YmdHis").rand(10,99);
		$data['uid']           = $_SESSION['web']['uid'];
		$data['order_status']  = 0;
		$data['order_confirm'] = 0;
		$data['order_payment'] = $zffs;
		$data['delivery_mode'] = $psfs;
		$data['parent_id']     = getParentIDByUID(); // 对应业务员ID
		$data['address_id']    = intval($address_id);
		$data['address_info']  = D('Common/UserAddress')->getAddressInfo($data['address_id']);
		$data['order_price']   = $amount;
		$data['note']   	   = trim(I('note'));
		$data['create_time']   = time();
		$data['dollar_huilv']  = C("dollar_huilv");
		$data['parent_type']   = $this->uType; //订单所属分销商
		$data['agent_id']      = C('agent_id');
		$order_id = $this->createOrder($data);
		if($order_id){
			//如果为下单预约，则将预约信息存表
			if($address_id == 'booked'){
				$saveData['orderid']		= $order_id; 
				$action		= $Store->onlineBookedAdd($saveData);
			}
			$result['status'] = 100;
			$result['data'] = $order_id;
			$result['info'] = "订单提交成功";
			//发送用户消息
			$username = M("User")->where('uid='.$_SESSION['web']['uid'])->getField('username');
			
			$title = "下单成功";
			if($address_id == 'booked'){
				$content	= "亲爱的用户 ".$username." 您已成功下单预约线下体验门店，订单编号：".$data['order_sn']."，配送方式：预约到店，预约时间：".$saveData['time']."， 预约店名：".$checkStore['name']."，电话：".$checkStore['phone']."，地址：".$checkStore['province_name']."省".$checkStore['city_name']."市".$checkStore['district_name'].$checkStore['address'];
			}else{
				$delivery_mode 	= M("delivery_mode")->where('mode_id='.$data['delivery_mode'])->getField('mode_name');
				$content 	= "用户".$username." 您已下单成功！订单编号：".$data['order_sn']."，配送方式：".$delivery_mode."，支付方式：线下转账， 订单金额：￥". $amount;
			}
			
			//$s = new \Common\Model\User();	
			//$s->sendMsg($_SESSION['web']['uid'],$content, $title);

			
			
			if($_SESSION['web']['phone'] && $zffs=='0'){
				//站点名称
				$site_name_md_arr[] = $data['order_sn'];
				$site_name_md_arr[] = C('site_name'); 
				$site_name_md_arr[] = C('site_contact');
				//发送短信
				$SMS = new \Common\Model\Sms();		
				$SMS->SendSmsByType($SMS::ORDER_SUBMIT_SUCCESS,$_SESSION['web']['phone'],$site_name_md_arr);		
			}	
			
 
		}else{
			$result['status'] = 0;
			$result['info'] = "请勿提交相同的产品";
		}

		$this->ajaxReturn($result);  	
	}
	 
	/**
	 * auth	：someone ，fangkai
	 * content：购物车购物成功页面
	 * updatetime	：2016-7-12
	**/
	public function orderSuccess(){
		$order_id = I('get.order_id','','intval');
		$where['agent_id'] = C('agent_id');
		$where['uid'] 	   = $_SESSION['web']['uid'];
		$where['order_id'] = $order_id;
		$Order = M('order');
		$check = $Order->where($where)->find();
		if(empty($check)){
			$this->error('操作错误',U('/Home/Order/orderConfirm'));
		}
		
		/* 查询出预约信息 */
		$Store		= new \Common\Model\Store();
		$storeInfo	= $Store->getOrderStoreInfo($order_id);
		$this->storeInfo	= $storeInfo;
	
		/* 查询出配送方式 */
		$deliMode = M('delivery_mode');
		$deli_where['agent_id'] =  C('agent_id');
		$deli_where['mode_id']  =  $check['delivery_mode'];
		$this->psfs = $deliMode->field('*')->where($deli_where)->find();
		$this->orderInfo = $check; 

		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1 and agent_id = '.C("agent_id"))->select();	//线下支付
		$this->display();
	}
		
	public function orderComplete(){
		$order = M('order')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."'")->order('create_time DESC')->find();
		$this->order = $order;
		$this->display();
	}
	 
	 
	 /* 生成订单函数 **/
	public function createOrder($data){
		if($data== '' || $data == null){
			return false;
		}                     
		$Order = M('order');
		$Order->startTrans();
		$order_id = $Order->data($data)->add(); 
		$order_goods_add = true;
		$order_delete = true;
		foreach($data['order_goods'] as $key=>$val){
			$val['order_id'] = $order_id; 
			if(!M('order_goods')->data($val)->add()){
				$order_goods_add = false;
			}
		} 
		foreach($data['order_goods'] as $key=>$val){
			//$val['attribute'] = addslashes($val['attribute']);
			$where['agent_id'] 	= C('agent_id');
			$where['uid']		= $_SESSION['web']['uid'];
			$where['goods_id']  = $val['goods_id'];
			$where['goods_attr']= $val['attribute'];
			if(!M('cart')->where($where)->delete()){
				$order_delete = false;
			}
		}                                    
		if($order_id && $order_goods_add && $order_delete){
			$data['order_id'] = $order_id;
			sendemail($data);
			$Order -> commit();
			return $order_id;
		}else{
			$Order->rollback();
			return false;
		} 
	}
	
}