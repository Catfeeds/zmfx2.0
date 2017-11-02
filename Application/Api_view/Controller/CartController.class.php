<?php
namespace Api_view\Controller;
class CartController extends Api_viewController{
	/**
	 * auth	：fangkai
	 * content：购物车主页
	 * time	：2016-5-26
	**/
	public function index(){
		$data = $this->getCartGoods(); 
        $this->data = $data;
		$this->display();	
        // $this->data = $data;
		// $this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：获取购物车商品信息 2016-3-30修复散货计算最新价格 2016-5-28速易宝APP修改结构
	 * time	：2016-5-26
	**/
	public function getCartGoods($goods_id){
		$agent_id       = C('agent_id');
		$uid     		= C('uid');		
		$cart           = M('cart');
		$AndroidSession	= C('AndroidSession');
		$userdiscount 	= getUserDiscount($_SESSION['app']['uid']);
		
		
		
		if($uid){
			$where['agent_id']	=	C('agent_id');
			$where[$this->userAgentKey]		=	$this->userAgentValue ;
			$cartGoods = D('Common/Cart')->getUpdateCartGoodsList($where, $uid);
		}
		
		
		
		if($_SESSION['app']['uid'] or $_COOKIE['PHPSESSID'] or $AndroidSession) {
			if(!empty($goods_id)){
				$cartGoods = M('cart')->where('agent_id='.C('agent_id').' AND '.$this->userAgentKey.'='.$this->userAgentValue.' and id in ('.$goods_id.')')->select(); 
			}else{
				$cartGoods = $cart->where(array($this->userAgentKey=>$this->userAgentValue,'agent_id'=>$agent_id))->select();
			} 
		}
 
		$total     = 0; // 订单总金额  
 
		if($cartGoods){
			$Goods	= D('Common/Goods');
			foreach($cartGoods as $key=>$val){
				$cartGoods[$key]['goods_attrs'] = $goods = unserialize($val['goods_attr']);
				$val['goods_attrs'] = unserialize($val['goods_attr']);
				if($cartGoods[$key]['goods_attrs']['attribute']){
					$cartGoods[$key]['goods_attrs']['attributes'] = unserialize($cartGoods[$key]['goods_attrs']['attribute']);
				}               
				switch($val['goods_type']){
					case 1: // 1 表示裸钻
 						$xiuzheng = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $uid, 'luozuan',  'single');		
						//如果cart表里面的价格和算出来的价格不对，就替换，并且更新该字段。
						if($cartGoods[$key]['goods_attrs']['price'] !=$xiuzheng['price']){
							$cartGoods[$key]['goods_attrs']['price']=$xiuzheng['price'];
							$cart->where("agent_id='".C('agent_id')."' AND id ='".$cartGoods[$key]['id']."'")->setField('goods_attr',serialize($cartGoods[$key]['goods_attrs']));
						}
						
						$cartGoods[$key]['goods_attrs']['meikadanjia']   = $xiuzheng['cur_price'];  
						$cartGoods[$key]['goods_attrs']['price']  		 = $xiuzheng['price'];  						
					
					
						if($goods_id){
							$luozuan['count'] += 1;
							$luozuan['weight'] += $cartGoods[$key]['goods_attrs']['weight'];
							//$cartGoods[$key]['goods_attrs'] = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $uid, 'luozuan',  'single');				
							$cartGoods[$key]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
							//判断钻石是否匹配到戒托中。
							$cartGoods[$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
							$luozuan['data'][] = $cartGoods[$key];
							$luozuan['total'] += formatRound($cartGoods[$key]['goods_attrs']['price'],2); 
						}else{
								//此条查找没有裸钻关联信息。
								$luozuan_relation_custom = $cart->where(array('goods_cart_id'=>'0','id'=>$val['id'],'agent_id'=>$agent_id,'goods_type'=>1,$this->userAgentKey=>$this->userAgentValue))->order('id DESC')->select();	
								//此处判断购物车时候，有关联关系，不显示，无关联关系即显示。
								 if($luozuan_relation_custom){
									 foreach ($luozuan_relation_custom as $zk=>$zv){
										  if($zv['id']==$val['id']){
												$luozuan['count'] += 1;
												$luozuan['weight'] += $cartGoods[$key]['goods_attrs']['weight'];
												//$cartGoods[$key]['goods_attrs'] = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $uid, 'luozuan',  'single');				
												$cartGoods[$key]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
												//判断钻石是否匹配到戒托中。
												$cartGoods[$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
												$luozuan['data'][] = $cartGoods[$key];
												$luozuan['total'] += formatRound($cartGoods[$key]['goods_attrs']['price'],2); 
										  } 
									 }
								 }else{
									$find_goods_cart_id = $cart->where(array('id'=>$val['id']))->getField('goods_cart_id');
									if($find_goods_cart_id){
										$is_goods_cart_id = $cart->where(array('id'=>$find_goods_cart_id))->find();
										if(!$is_goods_cart_id){
												$luozuan['count'] += 1;
												$luozuan['weight'] += $cartGoods[$key]['goods_attrs']['weight'];
												//$cartGoods[$key]['goods_attrs'] = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $uid, 'luozuan',  'single');				
												$cartGoods[$key]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
												//判断钻石是否匹配到戒托中。
												$cartGoods[$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
												$luozuan['data'][] = $cartGoods[$key];
												$luozuan['total'] += formatRound($cartGoods[$key]['goods_attrs']['price'],2); 
										} 
									}
								 } 
						}


						 
						break;
					case 2:// 散货
						//查出产品的原价格
						//$price = M('goods_sanhuo')->where(array('agent_id'=>C('agent_id'),'goods_id'=>$cartGoods[$key]['goods_id']))->find();
						//$cartGoods[$key]['goods_attrs']  = getGoodsListPrice($price, $_SESSION['app']['uid'], 'sanhuo', 'single');
						
						//计算新价格（不用购车价格，防止修改散货加点导致数据不正确）
						//$cartGoods[$key]['goods_attrs']['goods_price'] = formatRound($price*(100+$sanhuo_advantage)/100,2);
			 
						$sanhuo['weight'] += $cartGoods[$key]['goods_number']; //钻石重量
						$cartGoods[$key]['goods_attrs']['type_name'] = M('goods_sanhuo_type')->where(array('tid'=>$cartGoods[$key]['goods_attrs']['tid']))->getField('type_name');
						$cartGoods[$key]['goods_attrs']['marketPrice'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*1.5,2); 
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*$cartGoods[$key]['goods_number'],2);     
						$sanhuo['data'][] = $cartGoods[$key];
						$sanhuo['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);  
						$sanhuo['total'] = formatRound($sanhuo['total'],2);$sanhuo['count'] += 1;
						break;
					case 3:     // 成品
						//$new_cartGoods 				= getGoodsListPrice($cartGoods[$key]['goods_attrs'], $uid, 'consignment','single');
						$product['count'] += 1;
 
						/*
						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
 
							$chengpin_price = $new_cartGoods['activity_price'];
							//如果cart表里面的价格和算出来的价格不对，就替换，并且更新该字段。
							if($cartGoods[$key]['goods_attrs']['activity_price'] !=$chengpin_price){
								$cartGoods[$key]['goods_attrs']['activity_price']=$chengpin_price;
								$cart->where("agent_id='".C('agent_id')."' AND id ='".$cartGoods[$key]['id']."'")->setField('goods_attr',serialize($cartGoods[$key]['goods_attrs']));
							}
						}else{
							$chengpin_price = $new_cartGoods['goods_price'];
							//如果cart表里面的价格和算出来的价格不对，就替换，并且更新该字段。
							if($cartGoods[$key]['goods_attrs']['goods_price'] !=$chengpin_price){
								$cartGoods[$key]['goods_attrs']['goods_price']=$chengpin_price;
								//$cart->where("agent_id='".C('agent_id')."' AND id ='".$cartGoods[$key]['id']."'")->setField('goods_attr',serialize($cartGoods[$key]['goods_attrs']));
							}							
						} 
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($chengpin_price*$cartGoods[$key]['goods_number'],2);
						*/
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
 
						//$uid_goods_discount = isset($_SESSION['app']['consignment_discount']) ? $_SESSION['app']['consignment_discount'] : 0;						
						$product['count'] += 1;
						//获取材质名称
						/*
						$cartGoods[$key]['goods_attrs']['associateInfo']['material_name'] = M("goods_material")->where(array('material_id'=>$cartGoods[$key]['goods_attrs']['associateInfo']['material_id']))->getField('material_name');
						//获取主钻形状
						$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_name'] = M("goods_luozuan_shape")->where(array('shape_id'=>$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_id']))->getField('shape_name');
						*/
						/* 2016-5-28速易宝APP查询定制商品匹配的裸钻 */
						$cartGoods[$key]['pp_luozuan_weight'] = '';
						// $pp_luozuan1 =$cart->where(array('goods_cart_id'=>$val['id'],'agent_id'=>$agent_id.'888','goods_type'=>1))->order('id DESC')->select();
						
						$pp_luozuan = $cart->where(array('goods_cart_id'=>$val['id'],'agent_id'=>$agent_id,'goods_type'=>1))->order('id DESC')->select();
						
						
						$cartGoods[$key]['pp_luozuan_count'] = count($pp_luozuan);
					
 						if($pp_luozuan){
							// if($pp_luozuan1){
									// $pp_luozuan	= array_merge($pp_luozuan,$pp_luozuan1);
							// }
							foreach($pp_luozuan as $k=>$v){
								$pp_luozuan[$k]['goods_attrs'] = getGoodsListPrice(unserialize($v['goods_attr']), $uid, 'luozuan',  'single');	
								/*
								$pp_luozuan[$k]['goods_attrs'] = unserialize($v['goods_attr']);
								$cartGoods[$key]['pp_luozuan_weight'] += $pp_luozuan[$k]['goods_attrs']['weight'];
								$luozuan_advantage           = get_luozuan_advantage($pp_luozuan[$k]['goods_attrs']['weight']);	
								if(C('price_display_type') != 1){
									$pp_luozuan[$k]['goods_attrs']['dia_discount'] = $pp_luozuan[$k]['goods_attrs']['dia_discount'] + $luozuan_advantage  + $userdiscount;
									//$pp_luozuan[$k]['goods_attrs']['price']        = formatRound( $pp_luozuan[$k]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $pp_luozuan[$k]['goods_attrs']['dia_discount'] * $pp_luozuan[$k]['goods_attrs']['weight']/100, 2);  //国际报价*汇率*折扣*重量/100
									$pp_luozuan[$k]['goods_attrs']['meikadanjia']  = formatRound( $pp_luozuan[$k]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $pp_luozuan[$k]['goods_attrs']['dia_discount']/100, 2);  //国际报价*汇率*折扣*重量/100
								} else {
									$pp_luozuan[$k]['goods_attrs']['dia_discount'] = $pp_luozuan[$k]['goods_attrs']['dia_discount'] * (1+($luozuan_advantage  + $userdiscount)/100);
									//$pp_luozuan[$k]['goods_attrs']['price']        = formatRound( $pp_luozuan[$k]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $pp_luozuan[$k]['goods_attrs']['dia_discount'] * $pp_luozuan[$k]['goods_attrs']['weight']/100, 2);  //国际报价*汇率*折扣*重量/100
									$pp_luozuan[$k]['goods_attrs']['meikadanjia']  = formatRound( $pp_luozuan[$k]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $pp_luozuan[$k]['goods_attrs']['dia_discount']/100, 2);  //国际报价*汇率*折扣*重量/100
								}
								*/
								$pp_luozuan[$k]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
								//判断钻石是否匹配到戒托中。
								$pp_luozuan[$k]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
								$cartGoods[$key]['pp_luozuan_totalprice'] += formatRound($pp_luozuan[$k]['goods_attrs']['price'],2); 
								$cartGoods[$key]['pp_luozuan'][$k] = $pp_luozuan[$k]; 
								// $stopgap_id[$key][]=$v['id'];
								$stopgap_id[]=$v['id'];
							}
						}

						$cartGoods[$key]['pp_luozuan']['stopgap_id']=$stopgap_id;
						$attr = $cartGoods[$key]['goods_attrs'];
						$hand          = $attr['hand'];
						$word          = $attr['word']; 
						$hand1         = $attr['hand1'];
						$word1         = $attr['word1']; 
						$gid           = $val['goods_id'];
						$goods_type    = 4;
						$diamondId     = intval($attr['luozuanInfo']['gal_id']);
						$materialId    = intval($attr['associateInfo']['material_id']);
						$deputystoneId = intval($attr['deputystone']['gad_id']);
						$goodsInfo       = $Goods -> get_info($gid, 0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
						$luozuanInfo     = $Goods -> getGoodsAssociateLuozuanAfterAddPoint("goods_id=$gid AND gal_id=$diamondId");
						$associateInfo   = $Goods -> getGoodsAssociateInfoAfterAddPoint("goods_id=$gid AND zm_goods_associate_info.material_id=$materialId ");
						if($deputystoneId and $deputystoneId!='undefined') {
							$deputystone = $Goods -> getGoodsAssociateDeputystoneAfterAddPoint("gad_id=$deputystoneId");
						}
						$material = $Goods -> getGoodsMaterialAfterAddPoint(" material_id = $materialId ");
						if( !$goodsInfo['price_model'] ){
							$goodsInfo['goods_price'] = $Goods -> getJingGongShiPrice($material,$associateInfo,$luozuanInfo,$deputystone);
						}else{
							$category_name = M('goods_category') -> where(" category_id = '".$goodsInfo['category_id']."'") -> getField('category_name');
							$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
							if( $is_size ){
								$size                     = $Goods->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
								$goodsInfo['goods_price'] = $size['size_price'];
								if($hand1){
									$size = $Goods->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
									$goodsInfo['goods_price'] = $size['size_price']+$goodsInfo['goods_price'];
								}
							} else {
								$ass = $Goods->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
								$goodsInfo['goods_price'] = $ass['fixed_price'];
							}
						}	
 
						$new_cartGoods              = getGoodsListPrice($goodsInfo, $uid, 'consignment', 'single');
 						
 
						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$dingzhi_price = $cartGoods[$key]['goods_attrs']['activity_price'];
							//如果cart表里面的价格和算出来的价格不对，就替换，并且更新该字段。
							if($cartGoods[$key]['goods_attrs']['activity_price'] !=$new_cartGoods['activity_price']){
								$cartGoods[$key]['goods_attrs']['activity_price']=$new_cartGoods['activity_price'];
								$cart->where("agent_id='".C('agent_id')."' AND id ='".$cartGoods[$key]['id']."'")->setField('goods_attr',serialize($cartGoods[$key]['goods_attrs']));
							}							
						}else{
							$dingzhi_price = $cartGoods[$key]['goods_attrs']['goods_price'];
							//如果cart表里面的价格和算出来的价格不对，就替换，并且更新该字段。
							if($cartGoods[$key]['goods_attrs']['goods_price']  !=$new_cartGoods['goods_price']){
								$cartGoods[$key]['goods_attrs']['goods_price']=$new_cartGoods['goods_price'];
								$cart->where("agent_id='".C('agent_id')."' AND id ='".$cartGoods[$key]['id']."'")->setField('goods_attr',serialize($cartGoods[$key]['goods_attrs']));
							}							
						}
						$cartGoods[$key]['goods_attrs']['goods_price2']  = formatRound($dingzhi_price*$cartGoods[$key]['goods_number'],2);  	
						
						$product['data'][] = $cartGoods[$key];
						$product['pp_total'] += formatRound($cartGoods[$key]['pp_luozuan_totalprice'],2);
						$product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);						
						break;  
				}
			}
		}
		//print_r($product);exit;
		$total = $product['total'] + $product['pp_total'] + $luozuan['total']+$sanhuo['total'];
		if(isset($luozuan)){
			$data['luozuan'] =$luozuan;
		}
		if(isset($sanhuo)){
			$data['sanhuo'] =$sanhuo;
		}
		if(isset($product)){
			$data['product'] =$product;
		}
		$data['count'] = $product['count'] + $luozuan['count'] + $sanhuo['count'];	//购物车数量
		$data['total'] = formatRound($total,2);										//购物车总价格
		$data['total_weight'] = $sanhuo['weight'] + $luozuan['weight'];				//购物车总重量
		
 
		return $data;
	}
	
	/**
	 * auth	：fangkai
	 * content：匹配钻石
	 * time	：2016-5-27
	**/
	public function cartMatch(){
		$cart_id 	= I('get.cart_id','','intval');
		$agent_id 	= C('agent_id');
		$uid 		= C('uid');
		$sort 		= I('get.sort','ASC','string');
		$check 		= M('cart')->where(array('id'=>$cart_id,'agent_id'=>$agent_id))->find();
		if(!$check){
		   $this->error('购物车没有该商品');
		}
		$goods_info = unserialize($check['goods_attr']);
 
		$shape = M('goods_luozuan_shape')->where(array('shape_id'=>$goods_info['luozuanInfo']['shape_id']))->find();
		
		$this->shape_name = $shape['shape_name'];
		$this->weights = $goods_info['luozuanInfo']['weights_name'];
		
		$minWeight = $this->weights-0.02;
        $maxWeight = $this->weights+0.02;
		$where['goods_number'] = array('GT',0);
		$where['weight'] = array(array('EGT',$minWeight),array('ELT',$maxWeight));
		$where['shape'] = $shape['shape'];
		$where['luozuan_type'] = '0';

		//排序
		switch($sort){
			case 'ASC':
				$order = 'price ASC';
				break;
			case 'DESC':
				$order = 'price DESC';
				break;
			default:
				$order = 'price ASC';
				break;
		}
		$luozuan_count = D("GoodsLuozuan")->where($where)->count();
		$data['data']  = D("GoodsLuozuan")->where($where)->order($order)->limit(0,10)->select();
		$data['data']          = getGoodsListPrice($data['data'], $uid, 'luozuan');
		$data['cart_id']       = $cart_id;
		$data['luozuan_count'] = $luozuan_count;
		$data['dollar_huilv']  = C('dollar_huilv');
		$data['price_display_type'] = C('price_display_type');
		$this->data = $data;
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：匹配的裸钻列表
	 * time	：2016-5-30
	**/
	public function cartMatchList(){
		$cart_id  = I('get.cart_id','','intval');
		$agent_id = C('agent_id');
		$cart     = M('cart');
		$check    = $cart->where(array('id'=>$cart_id,'agent_id'=>$agent_id))->limit(0,10)->find();
		if(!$check){
		   $this->error('购物车没有该商品');
		}
		$where['goods_cart_id'] = $cart_id;
		$where['goods_type'] = 1;
		$where['agent_id'] = $agent_id;
		$data = $this->getLuozuan($where,'');
		$this->data = $data;
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：添加裸钻或者散货到购物车
	 * time	：2016-5-27
	**/
	public function addCart(){
		$gids 			= I('goodId');
		$type 			= I('type','','intval');
		$agent_id 		= C('agent_id');
		$sku_sn 		= I('sku_sn');				//成品需要
		$tips 			= I('tips');				//标识，1代表加入购物车，2代表立即购买，3代表立即定制
		$hand 			= I("hand","8");				//手寸
		$hand1 			= I("hand1","8");			//手寸
		$word 			= I("word",""); 			//十字留言
		$materialId 	= I("materialId",0);		//主石
		$diamondId 		= I("diamondId",0);			//材质
		$deputystoneId 	= I("deputystoneId",0);		//副石
		$goods_number	= I('goods_number',1);
		$sd_id          = I('sd_id','');
		$sd_id1         = I('sd_id1','');
		$word1 			= I("word1",""); 
		$uid 			= C("uid");
		$token 			= C("token");
 	

		if($gids){
				$gids   = explode(",",$gids); 
		}else{
			exit();
		}

		
		if($tips==2){
			if(empty($uid)){
				$result['info'] = '请先登录之后再立即购买';
				$result['status'] = 2;
				$this->ajaxReturn($result);
			}
		}

		
		if(($type=='4' && $tips=='4') || ($type=='4' && $tips=='3')){
			 $agent_id='8888';
		}
		
		
		if(!in_array($type,array(1,2,3,4))){
			$result['info'] = '添加购物车失败';
			$result['status'] = 0;
			$this->ajaxReturn($result);
			exit;
		}		
		foreach($gids as $val){
			switch($type){
				case 1:
					$datamodel = D("GoodsLuozuan");
					$where['gid'] = $val;
					$where['goods_number'] = array('GT',0);
					break;
				case 2:
					$datamodel           = D('GoodsSanhuo');
					$where['goods_id'] = $val;
					$where['goods_weight'] = array('GT',0);
					break;
				case 3:
					$datamodel = D('Common/Goods');
					break;
				case 4:
					$datamodel = D('Common/Goods');
					list($material,$associateInfo,$luozuan,$deputystone) = $datamodel -> getJingGongShiData($val,$materialId,$diamondId,$deputystoneId);
					break;
			}
            if( $type== 3 || $type == 4 ){

                $goods = $datamodel->get_info($val,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
				if($goods['goods_type'] == 3 &&  $goods['goods_number'] <= 0 ){
					$result['info']	= "该商品库存不足";
					$result['status']	= 0;
					$this->ajaxReturn($result);
				}
				
				
 
				
			}elseif($type== 2){
					$goods = $datamodel -> getInfo($where);
			}else {
					$goods = $datamodel -> where($where) -> find();
            }


			if($goods){
				$data = array();
				//根据type的值来组成不同的数组以便存储
				switch($type){
					case 1:
						$goods        = getGoodsListPrice($goods, $uid, 'luozuan','single');
						$userdiscount = getUserDiscount($uid);
						$goods['userdiscount']        = $userdiscount;
						$goods['luozuan_advantage']   = get_luozuan_advantage($goods['weight']); ;
						$goods['price_display_type']  = C('price_display_type');
						$data['goods_id']   = $goods['gid'];
						$data['goods_type'] = 1;
						$data['goods_number'] 		  = 1;
						$data['goods_sn'] = $goods['certificate_number'];
						$wherecart['goods_sn'] = $data['goods_sn'];
						break;
					case 2:
						$goods         					= getGoodsListPrice($goods, $uid, 'sanhuo', 'single');
						$data['goods_id'] 				= $goods['goods_id'];
						$data['goods_type'] 			= 2;
						$data['goods_sn'] 				= $goods['goods_sn'];
						$data['goods_number'] 			= 0;
						$wherecart['goods_id'] 			= $data['goods_id'];
						break;
					case 3:
						$temp                           = $datamodel -> getGoodsSku($val ," sku_sn = '$sku_sn' ");
						$temp['goods_type']   			= 3;
						$temp 							= getGoodsListPrice($temp, $uid, 'consignment','single');
						$goods['goods_price'] 			= formatRound($temp['goods_price'],2);
						$goods['activity_price']		= formatRound($temp['activity_price'],2);		//活动商品价格
						$goods['marketPrice'] 			= formatRound($temp['marketPrice'],2);
						$goods['sku_sn']				= $sku_sn;
						$goods['goods_sku']   			= $temp;
						$goods['consignment_advantage'] = $temp["consignment_advantage"];
						$goods['activity_status']		= $temp["activity_status"];						//活动商品标识
						$data['goods_id'] 				= $goods['goods_id'];
						$data['goods_type'] 			= 3;
						$data['goods_number'] 			= $goods_number;
						$data['goods_sn'] 				= $goods['goods_sn'];
						$data['sku_sn']   				= $sku_sn;
						$wherecart['goods_id'] 			= $data['goods_id'];
						$wherecart['sku_sn']   			= $sku_sn;
						break;
					case 4:
						$data       					  = array('status'=>0);
 
						if( !$goods['price_model'] ){
							$goods['goods_price']         = $datamodel -> getJingGongShiPrice($material, $associateInfo,$luozuan,$deputystone);
						}else{
							$category_name = M('goods_category') -> where(" category_id = '$goods[category_id]' ") -> getField('category_name');
							$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
							if( $is_size ){
								$size                   = $datamodel->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$val' ");
								if($hand1){
									$size1              = $datamodel->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$val' ");
									$size['size_price'] = $size1['size_price'] + $size['size_price'];
								}
								$goods['goods_price'] = $size['size_price'];
							} else {
								$ass                 = $datamodel->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$val' ");
								$goods['goods_price'] = $ass['fixed_price'];
							}
						}
						$goods['activity_status']		= $goods['activity_status'];	
						$info                		  	= getGoodsListPrice($goods, $uid, 'consignment', 'single');
						$stopgap_goods_id 				= isset($deputystone['goods_id']) ? $deputystone['goods_id'] : $luozuan['goods_id'];
						$goods['associateInfo'] 		= $associateInfo;
						$goods['luozuanInfo'] 			= $luozuan;
						$goods['deputystone'] 			= $deputystone;
						$goods['hand'] 					= $hand;
						$goods['word'] 					= $word;
						$goods['hand1'] 				= $hand1;
						$goods['word1'] 				= $word1;
						$goods['activity_price']		= $info['activity_price'];
						$goods['goods_price'] 			= formatRound($info['goods_price'],2);
						$goods['marketPrice'] 			= formatRound($info['marketPrice'],2);
						$goods['consignment_advantage'] = $info["consignment_advantage"];
						$goods['goods_type'] 			= 4;
						if($sd_id){
							$S                      	= D('Common/SymbolDesign');
							$info                   	= $S -> getInfo($sd_id);
							$goods['sd_images'] 		= $info['images_path'];
						}
						if($sd_id1){
							$S          	            = D('Common/SymbolDesign');
							$info           	        = $S -> getInfo($sd_id1);
							$goods['sd_images1'] 		= $info['images_path'];
						}						
						$data['goods_id'] 				= $stopgap_goods_id;
						$data['goods_type'] 			= $goods['goods_type'];
						$data['goods_sn'] 				= $goods['goods_sn'];
						$data['goods_number'] 			= $goods_number;
						$wherecart['goods_sn'] 			= $data['goods_sn'];

						$wherecart['goods_attr'] 		= serialize($goods);
						break; 
				}				
				$data['goods_attr'] = serialize($goods);
				$data['agent_id'] = $agent_id;
				$data[$this->userAgentKey] = $this->userAgentValue;
				//检测该商品是否已经加入购物车
				$cart = M('cart');
				$wherecart['agent_id'] 			= $agent_id;
				$wherecart[$this->userAgentKey] = $this->userAgentValue;
				$wherecart['goods_type'] = $type;
				$cartGood	= $cart->where($wherecart)->find();
                if(!empty($cartGood)){
					if($type == 3 || $type == 4){
						//立即购买
							if($goods_number != intval($cartGood['goods_number'])){
								$save['goods_number']	= $goods_number;
								$saveAction = M('cart')->where($wherecart)->save($save);
							}
							$result['url']				= $cartGood['id'];
							$result['status']			= 100;
							if($tips==3){	
								$result['info'] = '请先为选好的戒托挑选裸钻';
							}else{
								$result['info']	= '添加购物车成功';
							}
							
							if($tips==1){
								$result['num'] 	= parent::GetCartGoodsNumber();
							}
							
							
							// $this->ajaxReturn($result);						
					}
				}
				$action = $cart->data($data)->add();
			}
		}
 
		
		if($action){
			$result['num'] 	= parent::GetCartGoodsNumber();
			$result['info'] 	= '添加购物车成功';
			$result['status']	= 100;
			if($tips==3){	
				$result['info'] = '请先为选好的戒托挑选裸钻';
			}
			$result['url']		= $action;
		}else{
			$result['info'] 	= '添加购物车失败！';
			$result['status'] 	= 0;
		}
		$this->ajaxReturn($result);
	}
	/**
	 * auth	：fangkai
	 * content：根据购物车ID获取匹配的裸钻列表
	 * time	：2016-5-27
	**/
	public function getLuozuan($where='',$order='id DESC',$page=1,$n=10){
		$cart = M('cart');
		$data['data']  = $cart->where($where)->order($order)->limit(($page-1)*$n,$n)->select();
		if($data['data']){
			foreach($data['data'] as $key=>$val){
				$data['data'][$key]['goods_attrs'] = unserialize($val['goods_attr']);
				
				/* zhy 2016年10月7日 10:58:40	屏蔽老版本加点，后续改动在上
				if(C('price_display_type') != 1){
					$luozuan_advantage     = get_luozuan_advantage($data['data'][$key]['goods_attrs']['weight']);

					$data['data'][$key]['goods_attrs']['dia_discount'] = $data['data'][$key]['goods_attrs']['dia_discount'] + $luozuan_advantage + $userdiscount;
					$data['data'][$key]['goods_attrs']['price']        = formatRound( $data['data'][$key]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $data['data'][$key]['goods_attrs']['dia_discount'] * $data['data'][$key]['goods_attrs']['weight']/100, 2);  //国际报价*汇率*折扣*重量/100
					$data['data'][$key]['goods_attrs']['meikadanjia']  = formatRound( $data['data'][$key]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $data['data'][$key]['goods_attrs']['dia_discount']/100, 2);  //国际报价*汇率*折扣*重量/100
				} else {
					$data['data'][$key]['goods_attrs']['dia_discount'] = $data['data'][$key]['goods_attrs']['dia_discount'] * (1+($luozuan_advantage + $userdiscount)/100);
					$data['data'][$key]['goods_attrs']['price']        = formatRound( $data['data'][$key]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $data['data'][$key]['goods_attrs']['dia_discount'] * $data['data'][$key]['goods_attrs']['weight']/100, 2);  //国际报价*汇率*折扣*重量/100
					$data['data'][$key]['goods_attrs']['meikadanjia']  = formatRound( $data['data'][$key]['goods_attrs']['dia_global_price'] * C('dollar_huilv') * $data['data'][$key]['goods_attrs']['dia_discount']/100, 2);  //国际报价*汇率*折扣*重量/100
				}
				*/
				
				
				$data['data'][$key]['goods_attrs'] = getGoodsListPrice($data['data'][$key]['goods_attrs'], $_SESSION['app']['uid'], 'luozuan', 'single');
				$data['data'][$key]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
				//判断钻石是否匹配到戒托中。
				$data['data'][$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
				$data['totalprice'] += formatRound($data['data'][$key]['goods_attrs']['price'],2);
				
			}
		}
		$data['count'] = $cart->where($where)->count();
		return $data;
	}
	
	/**
	 * auth	：fangkai
	 * content：选择裸钻添加购物车（用以匹配定制商品）
	 * time	：2016-5-28
	**/
	public function lzppAddCart(){
		$gids 		= I('goodId');
		$type 		= I('type','','intval');
		$agent_id 	= C('agent_id');
		$cart_id 	= I('cart_id','','intval');
		$uid		= C('uid');
		if($gids){
			$gids   = explode(",",$gids); 
		}else{
			exit();
		}			
		$count_luozuan_two = count($gids);
		if(empty($gids) || $count_luozuan_two == 0 ){
			$result['status'] = 0;
			$result['msg'] = '您还没有选择商品';
			$this->ajaxReturn($result);
			exit;
		}
		
		$check = M('cart')->where(array('id'=>$cart_id))->find();
		
 
		
		if(!$check){
			$result['status'] = 0;
			$result['msg'] = '购物车没有该商品';
			$this->ajaxReturn($result);
			exit;
		}
		$count_dingzhi = intval($check['goods_number']);

		if($count_luozuan_two > $count_dingzhi){
			$result['status'] = 0;
			$result['msg'] = '匹配的钻石数量大于定制数量';
			$this->ajaxReturn($result);
			exit;
		}
		$cart = M('cart');
		$datamodel = D("GoodsLuozuan");
		foreach($gids as $val){
			$where['gid'] = $val;
			$where['goods_number'] = array('GT',0);
			// $where['agent_id'] = $agent_id;
			//检测该商品是否存在
			$goods = $datamodel->where($where)->find();
 
			if($goods){
				$goods = getGoodsListPrice($goods, $uid, 'luozuan',  'single');	
				$userdiscount = getUserDiscount($uid);
				$goods['userdiscount']        = $userdiscount;
				$goods['luozuan_advantage']   = get_luozuan_advantage($goods['weight']); 
				$goods['price_display_type']  = C('price_display_type');
				/* 组装保存的数据 */
				$data['goods_id'] = $goods['gid'];
				$data['goods_type'] = 1;
				$data['goods_sn'] = $goods['certificate_number'];
				$wherecart['goods_sn'] = $data['goods_sn'];
				$goods_attr = serialize($goods);
				$data['goods_attr'] = $goods_attr;
				$data['agent_id'] = $agent_id;
				$data[$this->userAgentKey] = $this->userAgentValue;
				$data['goods_cart_id'] = $cart_id;
				$wherecart[$this->userAgentKey] = $this->userAgentValue;
				$wherecart['agent_id'] = $agent_id;	
				$wherecart['goods_type'] = 1;
				if($cart->where($wherecart)->find()){
					$save['goods_cart_id'] = $cart_id;
					$action = $cart->where($wherecart)->save($save);
				}else{
					$data['goods_number'] = '1';
					$action = $cart->data($data)->add();
				}
				
				$action1 =$cart->where('id = '.$cart_id)->setField('agent_id',$agent_id);
				
			}
		}
		$count_luozuan_one = M('cart')->where(array('goods_cart_id'=>$cart_id,'agent_id'=>$agent_id))->count();
		/* 如果接收的裸钻数量加上购物车裸钻的数量大于等于零，则删除多余的数据 */
		$delte_number = $count_luozuan_one-$count_dingzhi;
		if($delte_number > 0){
			$cart->where(array('agent_id'=>$agent_id,'goods_cart_id'=>$cart_id,'type'=>1))->order('id ASC')->limit($delte_number)->delete();
		}
		$result['status'] = 100;
		$result['msg'] = '匹配成功';
		$this->ajaxReturn($result);
	}
	
	/**
	 * auth	：fangkai
	 * content：删除购物车 type:1 匹配裸钻列表页面删除，2 购物车页面的删除
	 * time	：2016-5-31
	**/
	public function deleteCart(){
		if(IS_AJAX){
			$agent_id = C('agent_id');
			$cart_ids = I('post.cart_ids');
			$type = I('post.type','','intval');
			if(!in_array($type,array(1,2))){
				$result['status'] = 0;
				$result['msg'] 	  = '操作错误';
				$this->ajaxReturn($result);
			}
			if(!isset($cart_ids)){
				$result['status'] = 0;
				$result['msg'] 	  = '操作错误';
				$this->ajaxReturn($result);
			}
			
			$cart = M('cart');
			$where['id'] = array('in',$cart_ids);
			$where['agent_id'] = $agent_id;
			$check = $cart->where($where)->select();
			if(!$check){
				$result['status'] = 0;
				$result['msg'] 	  = '购物车没有该商品';
				$this->ajaxReturn($result);
			}
			
			$action = $cart->where($where)->delete();
			if($action){
				//如果删掉了定制产品，则解绑匹配的裸钻
				if($type == 2){
					$where2['goods_cart_id'] = array('in',$cart_ids);
					$where2['agent_id']      = $agent_id;
					$where['goods_type']     = 1;
					$save['goods_cart_id']   = 0;
					$action2 = $cart->where($where2)->save($save);
					$result['type'] = 2;
				}
				$result['status'] = 100;
				$result['msg'] 	  = '删除成功';
			}else{
				$result['status'] = 0;
				$result['msg'] 	  = '删除失败';
			}
			$this->ajaxReturn($result);
		}
	}
	
	
	/**
	 * auth	：someone
	 * content：改变购物车商品数量 goods_type:2 散货；3 成品； 4 定制；
	 * updatetime	：2016-5-31
	**/
	public function changeGoodsNumber(){
		$goods_number = I('post.goods_number',1);
		$cart_id = I('post.cart_id',0); 
		$goods_type = I("post.goods_type",1,'intval');
		$agent_id = C('agent_id');
		$cartGoods = M('cart')->where(array($this->userAgentKey=>$this->userAgentValue,'agent_id'=>$agent_id,'id'=>$cart_id))->find();
		if(!$cartGoods){
			$result['status'] = 0;
			$result['msg'] = '该商品不存在';
			$this->ajaxReturn($result);
		}
		$cartGoods['goods_attrs'] = unserialize($cartGoods['goods_attr']);
		if($goods_type == 2){
			$stock = $cartGoods['goods_attrs']['goods_weight'];
		}else{
			$stock = $cartGoods['goods_attrs']['goods_number'];
		}
		if($goods_type != 4 ){
			if($goods_number > $stock){
				$result['status'] = 101;
				$result['msg'] = '购买数量超过库存！现库存为'.$stock;
				$result['goods_number'] = $cartGoods['goods_number'];
				$this->ajaxReturn($result);
			}
		}
		$change_number = $goods_number-$cartGoods['goods_number'];

		if($cart_id != 0 && !empty($cartGoods)){
			if($goods_type == 2){
				$cartGoods['goods_number'] = $goods_number; 
			}else{
				$cartGoods['goods_number'] = intval($goods_number); 
			}
			
			if(M('cart')->data($cartGoods)->save()){
				
				if(in_array($cartGoods['goods_attrs']['activity_status'],array('0','1'))){
					$price	= $cartGoods['goods_attrs']['activity_price'];
				}else{
					$price	= $cartGoods['goods_attrs']['goods_price'];
				}
				$change_price = formatRound($change_number*$price);
				$result['status'] = 100;
				$result['msg'] = "修改成功";
				$result['goods_type'] = $goods_type;
				$result['goods_number'] = $cartGoods['goods_number'];
				$result['change_price'] = $change_price;
			}else{
				$result['status'] = 102;
				$result['msg'] = "修改失败";
			}
		}                    
		$this->ajaxReturn($result);
	}
	
	// 根据裸钻的证书查询购物车中是否有客户订购
	public function getDiamondStatus($certificate_number){
		$array = array("certificate_number"=>$certificate_number);
		$arraySerialize = serialize($array);
		$arraySerialize =  substr($arraySerialize,5,-1);
		$where = "goods_attr like '%$arraySerialize%' AND agent_id='".C('agent_id')."' AND uid!='".$_SESSION['m']['uid']."'";
		
		if(M('cart')->where($where)->find()){
			return "待定";
		}else{
			return "有货";
		}
	}
	
	public function matchedDiamondInCart($certificate_number){
		$data = M('cart')->where("agent_id='".C('agent_id')."' AND goods_attr like '%$certificate_number%'")->select();
		if($data){
			foreach($data as $key=>$val){
				$val['goods_attr'] = unserialize($val['goods_attr']);
				if(strstr($val['goods_attr']['matchedDiamond'],$certificate_number)){
					return true;
				}
			}
		}
	}
	/**
	 * 购物车提交订单api
	 * zhy
	 * 2016年6月14日 16:54:12
	**/
	public function  Confirm_Cartapi(){
		$goods_id = I('post.goodsid');
		$uid=C('uid');
		if(!is_array($goods_id) || empty($goods_id)){
			$data['status'] = 2;	$data['msg'] = '请选择要确认支付的商品！';$this->ajaxReturn($data);exit();
		}
		if(empty($uid)){
			$data['status'] = 1;	$data['msg'] = '请登录之后在来提交订单！';$this->ajaxReturn($data);exit();
		}
		$goods_id=array_filter($goods_id);
		foreach($goods_id as $v){
			if(!is_numeric($v))  {
				$data['status'] = 2;	$data['msg'] = '数据有误！';$this->ajaxReturn($data);exit();	
			}
		}
		$_SESSION['Confirm_Cart_goodsid']=$goods_id;
		$data['status'] = 3;	$this->ajaxReturn($data);
	}

	/**
	 * 购物车提交订单
	 * zhy
	 * 2016年6月15日 17:14:04
	**/
	public function  Confirm_Cart(){
		$id = I('get.cart_id','','intval');
		$uid= C('uid');
		if(empty($_SESSION['app']['uid'])){		$this->redirect('/public/login');	}
		$data_address=array();
		$user_address = M('user_address')->where("uid='".$uid."' AND country_id != 0 "."and is_default = 1".' and agent_id = '.C('agent_id'))->find();	
 
		if(!$user_address){
			$user_address = M('user_address')->where("uid='".$uid."' AND country_id != 0 ".' and agent_id = '.C('agent_id'))->find();	
		}
		
		$user_delivery_mode	 = D('Common/OrderPayment')->this_dispatching_way();
		$user_pay_mode = M('payment_mode')->where("agent_id =" .C('agent_id'))->select();	
		if($user_address){
			$data_address=M("region")->where("(region_id IN (".$user_address['country_id'].','.$user_address['province_id'].','.$user_address['city_id'].','.$user_address['district_id']."))")->getField('region_name',true); 
			$data_address['address']=$user_address['address'];
			$data_address['name']=$user_address['name'];
			$data_address['phone']=$user_address['phone'];
			$data_address['address_id']=$user_address['address_id'];
		}
		$this->id = $id;
		$this->user_pay_mode=$user_pay_mode;
		$this->user_delivery_mode=$user_delivery_mode;
		$this->user_address = $data_address;
 		$this->Get_Goods_Cart($id);
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1 and agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付
		$this->display();
	}

	/**
	 * 购物车提交订单商品清单
	 * zhy
	 * 2016年6月15日 18:05:55
	**/
		
	public function  Cart_List(){
		$id = I('get.cart_id','','intval');
		$this->Get_Goods_Cart($id);
		$this->display();	
	}
	
	
 
	
	/**
	 * 购物车感觉商品ID查找公共方法
	 * zhy
	 *	2016-9-27增加立即购买直接跳转到订单确认页面功能			--fangkai
	 *	id：购物车表中ID，如果存在该值，则是立即购买跳转过来的，确认页面直接取该ID的商品
	 * 2016年6月15日 18:26:27
	**/
	public function Get_Goods_Cart($id){
		if($id){
			$goods_id	= $id;
		}else{
			$goods_id = $_SESSION['Confirm_Cart_goodsid'];
			$goods_id=array_unique($goods_id);
			$goods_id =implode(',',$goods_id);
		}
		if(empty($goods_id))  $this->redirect('/Cart');
		$data = $this->getCartGoods($goods_id);
		$this->luozuan = $data['luozuan'];
		$this->sanhuo = $data['sanhuo'];
		$this->product = $data['product'];
		$this->jietuo = $data['jietuo'];   
		$this->count_tip = $data['count'];
		$luozuan_total=isset($data ['luozuan']['total']) ? $data ['luozuan']['total'] : 0 ;	 
		$sanhuo_total=isset($data ['sanhuo']['total']) ? $data ['sanhuo']['total'] : 0 ;	 
		$product_total=isset($data ['product']['total']) ? $data ['product']['total'] : 0 ;	 
		$jietuo_total=isset($data ['jietuo']['total']) ? $data ['jietuo']['total'] : 0 ;	 
		$_SESSION['Corder_succeed_total'] =$luozuan_total+$sanhuo_total+$product_total+$jietuo_total;
		unset($data);
	}
	
	
	/**
	 * 购物车提交订单
	 * zhy
	 * 2016年6月16日 10:18:39
	**/
	public function Submit_Corder(){
		$id = I('id'); 
		$address_id = I('address_id');
		$radio_tips = I('radio_tips');
		$radio_name = $_COOKIE['invoice_name'];	
		$uid		= C('uid');
		if(!is_array($id)){
			$data['status'] = 0;	$data['msg'] = '请在购物车里面选择商品之后在提交！';$this->ajaxReturn($data);exit();
		}
		if(!is_numeric($radio_tips)){
			$data['status'] = 0;	$data['msg'] = '请选择物流配送方式之后在提交！';$this->ajaxReturn($data);exit();
		}
		if(!is_numeric($address_id)){
			$data['status'] = 0;	$data['msg'] = '请选择默认地址或者编辑地址之后在提交！';$this->ajaxReturn($data);exit();
		}
		$delivery_mode = M("delivery_mode")->where('mode_id='.$radio_tips)->find();
		if(!$delivery_mode){
			$data['status'] = 0;	$data['msg'] = '配送方式有误！';$this->ajaxReturn($data);exit();
		}
		$id = implode(',',$id);
	    $dataList = M('cart')->where("agent_id=".C('agent_id')." AND uid = $uid and id in ($id)")->order("goods_type ASC")->select(); 
		$getParentIDByUID = M('user')->where("uid=$uid and agent_id = ".C('agent_id'))->getField('parent_id');
	 	if(empty($dataList)){	$this->redirect("/Cart");	} 
		$amount = 0;
		$order_goods = array(); 
		$GC			 = D('Common/GoodsCategory');
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
			
			
				$dataList[$key]['data'] = getGoodsListPrice($dataList[$key]['data'], $uid, 'luozuan',  'single');		
			 
				$order_goods[$key]['goods_number']          = 1;
				$order_goods[$key]['certificate_no']        = $goods['certificate_number'];
				$userdiscount                               = getUserDiscount($uid);
				$luozuan_advantage           				= get_luozuan_advantage($dataList[$key]['data']['weight']);
				$order_goods[$key]['luozuan_type']   		= $dataList[$key]['data']['luozuan_type'];
				
 
				$order_goods[$key]['goods_price'] = formatRound($dataList[$key]['data']['price'],2);
				$order_goods[$key]['goods_id']    = $dataList[$key]['data']['gid'];
			 
				
				$amount += formatRound($dataList[$key]['data']['price'],2);   
			}else if($val['goods_type'] ==2){  //散货
				$order_goods2[$key]['attribute'] = unserialize($order_goods[$key]['attribute']);
				//查出产品的原价格
				$price = M('goods_sanhuo')->where(array('agent_id'=>C('agent_id'),'goods_id'=>$val['goods_id']))->getField('goods_price');
				//查出现阶段的散货加点
				$sanhuo_advantage = C('sanhuo_advantage');
				
				//计算新价格（不用购车价格，防止修改散货加点导致数据不正确）
				//$dataList[$key]['data']['goods_price'] = formatRound($price*(100+$sanhuo_advantage)/100,2);
				$dataList[$key]['data']                   = getGoodsListPrice($dataList[$key]['data'],$uid,'luozuan', 'single');
				$order_goods[$key]['goods_price2'] = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);
				if($order_goods[$key]['attribute']['goods_price2'] != $order_goods[$key]['goods_price2']){
					$order_goods2[$key]['attribute']['goods_price'] = $dataList[$key]['data']['goods_price'];
					$order_goods2[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
					$save['goods_attr']  = serialize($order_goods2[$key]['attribute']);
					$action = M('cart') -> where(array('agent_id'=>C('agent_id'),'uid'=>$uid,'goods_id'=>$val['goods_id']))->save($save);
				}
				$order_goods[$key]['goods_number'] = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['goods_price'] = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute']['goods_price'] = $dataList[$key]['data']['goods_price'];
				$order_goods[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute'] = serialize($order_goods2[$key]['attribute']);
				$order_goods[$key]['goods_id'] = $dataList[$key]['data']['goods_id']; 
				$amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2); 			 	
			}else if($val['goods_type'] == 3){   // 3成品
			
			
						//$dataList[$key]['data'] 				= getGoodsListPrice($dataList[$key]['data'], $uid, 'consignment','single'); 
						//print_r($temp);exit;
						
						//$uid_goods_discount = isset($_SESSION['app']['goods_discount']) ? $_SESSION['app']['goods_discount'] : 0;	

						
						/*
						$cartGoods[$key]['goods_attrs']['category_name'] = M("goods_category")->where("category_id=".$cartGoods[$key]['goods_attrs']['category_id'])->getField("category_name");
						$cartGoods[$key]['goods_attrs']['marketPrice'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*1.5,2); 
						*/
						
						
			
				$order_goods[$key]['goods_number'] = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['data']['goods_attrs']['goods_name'] = addslashes($order_goods[$key]['data']['goods_attrs']['goods_name']);
				//$dataList[$key]['data'] = getGoodsListPrice($dataList[$key]['data'], $_SESSION['App']['uid'], 'consignment','single');
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
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['goods_sku']['activity_price'];
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];
				 
				$order_goods[$key]['goods_id']    	 = $dataList[$key]['data']['goods_id']; 
				$amount += formatRound($price*$val['goods_number'],2);  
			}else if($val['goods_type'] ==4){  //戒托  
				//$dataList[$key]['data'] = getGoodsListPrice($dataList[$key]['data'], $uid, 'consignment','single');
				
				$order_goods[$key]['goods_number'] = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $goods['goods_sn']; 
				//$dataList[$key]['data'] = getGoodsListPrice($dataList[$key]['data'], $_SESSION['App']['uid'], 'consignment','single');
				$order_goods[$key]['goods_id'] = $dataList[$key]['data']['goods_id']; 
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
			}
			$order_goods[$key]['goods_number_up'] = 0; 
			$order_goods[$key]['update_time']     = time(); 
			$order_goods[$key]['agent_id']        = C('agent_id');  
		}    
		if(is_numeric($delivery_mode['mode_safep']) && is_numeric($delivery_mode['mode_expressp'])){
			$amount=$amount+$delivery_mode['mode_safep']+$delivery_mode['mode_expressp'];
		}
		$result['note_a']        = I('orderInfo');
		$result['order_goods']   = $order_goods;
		$result['order_sn']      = date("YmdHis").rand(00,100);
		$result['uid']           = $uid;
		$result['order_status']  = 0;
		$result['order_confirm'] = 0;
		$result['order_payment'] = 0;
		$result['delivery_mode'] = $radio_tips;
		$result['parent_id']     = $getParentIDByUID; // 对应业务员ID
		$result['address_id']    = $address_id;
		$result['address_info']  = D("Common/UserAddress")->getAddressInfo($address_id);
		$result['order_price']   = $amount;
		$result['create_time']   = time();
		$result['dollar_huilv']  = C("dollar_huilv");
		$result['invoice']  	 = urldecode($_COOKIE['invoice_name']);
		//$data['parent_type']   = $this->uType; //订单所属分销商
		$result['agent_id']      = C('agent_id');
		if( $this -> createOrder($result) ){
			$_SESSION['Corder_succeed_total_amount']=$amount;
			$data['status'] = 1;
			$data['msg']    = "订单提交成功";
			//发送用户消息
			$username      = M("User")->where('uid='.$uid)->getField('username');
			$title         = "下单成功";
			$content       = "用户".$username." 您已下单成功！订单编号：".$result['order_sn']."，配送方式：".$delivery_mode['mode_name']."，支付方式：线下转账， 订单金额：￥". $amount;
			$s             = new \Common\Model\User();	
			$s             -> sendMsg($uid,$content, $title);
			unset($_COOKIE['invoice_name']);
			// $phone          = M("User")->where('uid='.$uid)->getField('phone');
			// $s              = new \Common\Model\Sms();
			// $obj           -> sendSms($phone,'order_submit_ok_send_user',C('agent_id'));
			// $phone          = M("admin_user")->where('user_id='.$getParentIDByUID)->getField('phone');
			// $obj           -> sendSms($phone,'order_submit_ok_send_admin',C('agent_id'));
		}else{
			$data['status'] = 0;
			$data['msg'] = "请勿提交相同的产品";
		}
		$this->ajaxReturn($data);  	
	}
	
	
	
	
		 /* 生成订单函数 **/
	public function createOrder($result){
		if($result== '' || $result == null){
			return false;
		}    
		$Order = M('order');
		$Order->startTrans();             
		$order_id = $Order->data($result)->add();  
		$order_goods_add = true;
		$order_delete = true;
		foreach($result['order_goods'] as $key=>$val){
		$val['order_id'] = $order_id; 
			if(!M('order_goods')->data($val)->add()){
				$order_goods_add = false;
			}
		} 
		foreach($result['order_goods'] as $key=>$val){
			//$val['attribute'] = addslashes($val['attribute']);
			// if(!M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['app']['uid']."' AND goods_id='".$val['goods_id']."' AND goods_attr='".$val['attribute']."'")->delete()){
			$where['agent_id'] 	= C('agent_id');
			$where['uid']		= $_SESSION['app']['uid'];
			$where['goods_id']  = $val['goods_id'];
			$where['goods_attr']= $val['attribute'];
			if(!M('cart')->where($where)->delete()){
				$order_delete = false;
			}
		}                                    
		if($order_id && $order_goods_add && $order_delete){
			$Order -> commit();
			return true;
		}else{
			$Order->rollback();
			return false;
		} 
	}
	
	
	/**
	 * 购物车提交订单
	 * zhy
	 * 2016年6月16日 11:45:12
	**/
	public function Corder_succeed(){
		$this->order = M('order')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['app']['uid']."'")->order('create_time DESC')->find();
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1 and agent_id = '.C("agent_id"))->select();	//线下支付
		$this->order_total=$_SESSION['Corder_succeed_total_amount'];
		$this->display();	
	}
	
    /**
     * 定制匹配裸钻，定制商品信息
     * zhy	find404@foxmail.com
     * 2017年2月8日 17:46:40
     */
	public function custom_goodinfo(){
		$uid        = C('uid');
		$agent_id	= C('agent_id');
		$cart_id    = I('cart_id');
        $galid   	= I('material_id');		//材质ID
		$materialId = I('tid');				//副石ID
        $gadId 		= I('deputystoneId','0');
		if(is_numeric($cart_id)){
			$gid = M('cart')->where(' id='.$cart_id)->getField('goods_id');
			if($gid){
				$M          		= D('Common/Goods');
				$info       		= $M -> get_info($gid,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
				if( !$info['price_model'] ){
					list($material,$associateInfo,$luozuan,$deputystone) = $M -> getJingGongShiData($gid,$materialId,$galid,$gadId);
					$info['goods_price']                                 = $M -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
				}else{
					$category_name = M('goods_category')->where(" category_id = '$info[category_id]' ")->getField('category_name');
					$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
					if( $is_size ){
						$size                   = $M->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
						if($hand1){
							$size1              = $M->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
							$size['size_price'] = $size1['size_price'] + $size['size_price'];
						}
						$info['goods_price'] = $size['size_price'];
					} else {
						$ass                 = $M->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
						$info['goods_price'] = $ass['fixed_price'];
					}
				}
				$info                                                = getGoodsListPrice($info, $uid, 'consignment', 'single');
 

				$data['status']			= '100';
				$data['cart_id']		= $cart_id;
				$data['goods_name'] 	= $info['goods_name'];
				if(in_array($info['activity_status'],array('0','1'))){
					$data['goods_price']	= $info['activity_price'];
				}else{
					$data['goods_price']	= $info['goods_price'];
				}
				$data['thumb']			= $info['thumb'];
				$data['galid']			= $galid;	
 
					$check = M('cart')->where(array('id'=>$cart_id))->find();
					if($check){
						$goods_info = unserialize($check['goods_attr']);
						$shape = M('goods_luozuan_shape')->where(array('shape_id'=>$goods_info['luozuanInfo']['shape_id']))->find();
						$data['shape_name']			= $shape['shape_name'];	
						$data['weights']			= $goods_info['luozuanInfo']['weights_name'];
					}
			}else{
				$data['status']='101';
				$data['text']='没有数据！';	
			}
		}else{
			$data['status']='101';
			$data['text']='传参错误！';			
		}
		$this->ajaxReturn($data);  	
	}
    /**
     * 定制匹配裸钻，定制商品信息
     * zhy	find404@foxmail.com
     * 2017年2月8日 17:46:40
     */
	public function GetCartGoodsNumber(){
		$data['num'] 	= parent::GetCartGoodsNumber();
		$data['status']	='100';
		$this->ajaxReturn($data);
	}
	
	
	 
    /**
     * 购物车信息
     * zhy	find404@foxmail.com
     * 2017年2月8日 17:46:40
     */
	public function data_index(){
		$data = $this->getCartGoods();
		$result['ret'] = '100';
		$result['msg'] = '获取成功！';
		$result['data'] = $data;
		
		$this->ajaxReturn($result);	
	}

 
	
	
	
	
	//修改购物车数量
	public function Data_ChangeGoodsNumber(){
		$goods_number = I('get.goods_number',1);
		$cart_id = I('get.cart_id',0); 
		$goods_type = I("get.goods_type",1,'intval');
		$agent_id = C('agent_id');
		$cartGoods = M('cart')->where(array($this->userAgentKey=>$this->userAgentValue,'agent_id'=>$agent_id,'id'=>$cart_id))->find();
		if(!$cartGoods){
			$result['status'] = 0;
			$result['msg'] = '该商品不存在';
			$this->ajaxReturn($result);
		}
		$cartGoods['goods_attrs'] = unserialize($cartGoods['goods_attr']);
		if($goods_type == 2){
			$stock = $cartGoods['goods_attrs']['goods_weight'];
		}elseif($goods_type == 3){
			$stock = $cartGoods['goods_attrs']['goods_sku']['goods_number'];
		}else{
			$stock = $cartGoods['goods_attrs']['goods_number'];
		}
		if($goods_type != 4 ){
			if($goods_number > $stock){
				$result['status'] = 101;
				$result['msg'] = '购买数量超过库存！现库存为'.$stock;
				$result['goods_number'] = $cartGoods['goods_number'];
				$this->ajaxReturn($result);
			}
		}
		$change_number = $goods_number-$cartGoods['goods_number'];

		if($cart_id != 0 && !empty($cartGoods)){
			if($goods_type == 2){
				$cartGoods['goods_number'] = $goods_number; 
			}else{
				$cartGoods['goods_number'] = intval($goods_number); 
			}
			
			if(M('cart')->data($cartGoods)->save()){
				
				if(in_array($cartGoods['goods_attrs']['activity_status'],array('0','1'))){
					$price	= $cartGoods['goods_attrs']['activity_price'];
				}else{
					$price	= $cartGoods['goods_attrs']['goods_price'];
				}
				$change_price = formatRound($change_number*$price);
				$result['status'] = 100;
				$result['msg'] = "修改成功";
				$result['goods_type'] = $goods_type;
				$result['goods_number'] = $cartGoods['goods_number'];
				$result['change_price'] = $change_price;
			}else{
				$result['status'] = 102;
				$result['msg'] = "修改失败";
			}
		}                    
		$this->ajaxReturn($result);
	}	
	
	
	//删除购物
	public function Data_deleteCart(){
 
		$agent_id = C('agent_id');
		$cart_ids = I('get.cart_ids');
		$type = I('get.type','','intval');
		if(!in_array($type,array(1,2))){
			$result['status'] = 0;
			$result['msg'] 	  = '操作错误';
			$this->ajaxReturn($result);
		}
		if(!isset($cart_ids)){
			$result['status'] = 0;
			$result['msg'] 	  = '操作错误';
			$this->ajaxReturn($result);
		}
		
		$cart = M('cart');
		$where['id'] = array('in',$cart_ids);
		$where['agent_id'] = $agent_id;
		$check = $cart->where($where)->select();
		if(!$check){
			$result['status'] = 0;
			$result['msg'] 	  = '购物车没有该商品';
			$this->ajaxReturn($result);
		}
		
		$action = $cart->where($where)->delete();
		if($action){
			//如果删掉了定制产品，则解绑匹配的裸钻
			if($type == 2){
				$where2['goods_cart_id'] = array('in',$cart_ids);
				$where2['agent_id']      = $agent_id;
				$where['goods_type']     = 1;
				$save['goods_cart_id']   = 0;
				$action2 = $cart->where($where2)->save($save);
				$result['type'] = 2;
			}
			$result['status'] = 100;
			$result['msg'] 	  = '删除成功';
		}else{
			$result['status'] = 0;
			$result['msg'] 	  = '删除失败';
		}
		$this->ajaxReturn($result);
 
	}
	
	//提交购物车
	public function  Data_Confirm_Cart(){
		$cart_id = I('get.cart_id');
		$uid= C('uid');
		if(empty($uid)){		
			$data['status']='101';
			$data['msg']='用户不能为空！';
			$this->ajaxReturn($data);			
		}
		
		if(empty($cart_id)){		
			$data['status']='101';
			$data['msg']='提交购物车ID不能为空！';
			$this->ajaxReturn($data);			
		}
 
		$data = $this->getCartGoods($cart_id);
		$data_address=array();
		$user_address = M('user_address')->where("uid='".$uid."' AND country_id != 0 "."and is_default = 1".' and agent_id = '.C('agent_id'))->find();	
 
		if(!$user_address){
			$user_address = M('user_address')->where("uid='".$uid."' AND country_id != 0 ".' and agent_id = '.C('agent_id'))->find();	
		}
		
		$user_delivery_mode	 = D('Common/OrderPayment')->this_dispatching_way();
		$user_pay_mode = M('payment_mode')->where("agent_id =" .C('agent_id'))->select();	
		if($user_address){
			$data_address=M("region")->where("(region_id IN (".$user_address['country_id'].','.$user_address['province_id'].','.$user_address['city_id'].','.$user_address['district_id']."))")->getField('region_name',true); 
			$data_address['address']=$user_address['address'];
			$data_address['name']=$user_address['name'];
			$data_address['phone']=$user_address['phone'];
			$data_address['address_id']=$user_address['address_id'];
		}
		$data['user_pay_mode']=$user_pay_mode;
		$data['user_delivery_mode']=$user_delivery_mode;
		$data['user_address'] = $data_address;
		$data['LinePayMode']=M('payment_mode_line')->where(' atype=1 and agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付

		$luozuan_total=isset($data ['luozuan']['total']) ? $data ['luozuan']['total'] : 0 ;	 
		$sanhuo_total=isset($data ['sanhuo']['total']) ? $data ['sanhuo']['total'] : 0 ;	 
		$product_total=isset($data ['product']['total']) ? $data ['product']['total'] : 0 ;	 
		$jietuo_total=isset($data ['jietuo']['total']) ? $data ['jietuo']['total'] : 0 ;	 
		$data['count'] =$luozuan_total+$sanhuo_total+$product_total+$jietuo_total;
		$data['status'] ='100';
		$this->ajaxReturn($data);
	}


}