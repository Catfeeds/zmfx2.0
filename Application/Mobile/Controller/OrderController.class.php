<?php
namespace Mobile\Controller;
class OrderController extends MobileController{

    /**
     * 购物车
     */
    public function cart(){
        $data = $this->getCartGoods();
        $this->data = $data;
        $this->display();
    }

    /**
     * 把定制产品添加到购物车
     */
    public function DiyToCart(){
        //把裸钻加入到购物车
        if(cookie('step1')){
			$C = M('cart');
            $luozuan = getLuozuanInfo($_COOKIE['step1']);
			if($_SESSION['m']['uid']) $data['uid'] = $_SESSION['m']['uid'];
			else $data['session'] = $this->userAgentValue;
			$data['goods_id']     = $luozuan['gid'];
			$data['goods_type']   = '1';
			$data['goods_attr']   = serialize($luozuan);
			$data['goods_sn']     = $luozuan['certificate_number'];
			$data['goods_number'] = '1';
			$data['agent_id']     = C('agent_id');
			if( !$C -> add($data) ){
				$this -> success('裸钻添加到购物车失败!');
			}
        }else{
            $this -> error('你没有选择钻石',U('Goods/luozuan'));
        }
        //把金工石产品加入到购物车
        if(cookie('goods_id')){
            $goods_id  = cookie('goods_id');
            $type      = cookie('goods_type');
            $selString = cookie('selString');
            $this -> cartAddGoods($goods_id, $type, $selString,1);
        }else{
            $this->error('你没有选择钻石',U('Goods/goodsDiy'));
        }
    }

    /**
     * 添加产品到购物车
     * @param int $goods_id 产品id
	 * @param int $type 产品类型
     * @param array $selString 选中数据
	 * @param number $isDiy
     */
    public function cartAddGoods($goods_id,$type,$selString,$isDiy=0,$id_type=''){
		if(empty(isDiy)){
			$isDiy = 0;
		}
		$G                  = D('Common/Goods');
		$C                  = M('cart');
		$data['goods_id']   = $goods_id;
		$data['goods_type'] = $type;
		$goods_number		= I('goods_number',1);
		if($_SESSION['m']['uid']) $data['uid'] = $_SESSION['m']['uid'];
		else $data['session'] = $this->userAgentValue;
		if($type == 3){//珠宝成品
			$info                 = $G -> get_info($goods_id,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
			if($goods_id<0 || empty($info)){
				$result['info']	= "不存在该货品";
				$result['status']	= 0;
				$this->ajaxReturn($result);
			}
			if($info['goods_type'] == 3 &&  $info['goods_number'] <= 0 ){
				$result['info']	= "该商品库存不足";
				$result['status']	= 0;
				$this->ajaxReturn($result);
			}
			$info['sku_sn']       = getArrayLastValue(explode(',',$selString));
			$temp                 = $G -> getGoodsSku($goods_id," sku_sn = '$info[sku_sn]' ");
			$temp['goods_type']   = '3';
			$temp                 = getGoodsListPrice($temp, $_SESSION['m']['uid'], 'consignment', 'single');
			$info['activity_status'] = $temp["activity_status"];						//活动商品标识
			$info['activity_price']	 = formatRound($temp['activity_price'],2);		//活动商品价格
			$info['goods_price']  = formatRound($temp['goods_price'],2);
			$info['goods_sku']    = $temp;
			$data['goods_attr']   = serialize($info);
			$data['goods_sn']     = $info['goods_sn'];
			$data['goods_number'] = $goods_number;
			$data['sku_sn'] 	  = $temp['sku_sn'];
		}elseif($type == 4){//珠宝金工石产品
			$info        = $G -> get_info($goods_id,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
			$selArr      = explode(',',$selString);
			$material_id = $selArr[0];//材质
			$gad_id      = $selArr[1];//副石选中id
			$gal_id      = $selArr[2];//主石选中id
			$hand        = $selArr[3];//手寸
			$word        = $selArr[4];//刻字
			$sd_id       = $selArr[5];//刻字
			$hand1       = $selArr[6];//手寸
			$word1       = $selArr[7];//刻字
			$sd_id1      = $selArr[8];//刻字
			$info['associateInfo'] = array();
			$info['luozuanInfo']   = array();
			$info['deputystone']   = array();
			list($material,$info['associateInfo'],$info['luozuanInfo'],$info['deputystone']) = $G -> getJingGongShiData($goods_id,$material_id,$gal_id,$gad_id);
			if( !$info['price_model'] ){
				$info['goods_price']   = $G -> getJingGongShiPrice($material,$info['associateInfo'],$info['luozuanInfo'],$info['deputystone']);
			}else{
				$info['category_name'] = M('goods_category') -> where(" category_id = '$info[category_id]' ") -> getField('category_name');
				$is_size               = InStringByLikeSearch($info['category_name'],array( '项链' ,'手链','钻戒','戒','对戒'));
				if( $is_size ){
					$size                     = $G->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$material_id' and goods_id = '$goods_id' ");
					$info['goods_price']      = $size['size_price'];
					if($hand1){
						$size                 = $G->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$material_id' and goods_id = '$goods_id' ");
						$info['goods_price']  = $size['size_price']+$info['goods_price'];
					}
				} else {
					$ass                      = $G->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$material_id' and goods_id = '$goods_id' ");
					$info['goods_price']      = $ass['fixed_price'];
				}
			}
			$info                  = getGoodsListPrice($info, $_SESSION['m']['uid'], 'consignment', 'single');
			$info['hand']          = $hand;
			$info['word']          = $word;
			if($sd_id){
				$S                 = D('Common/SymbolDesign');
				$in                = $S -> getInfo($sd_id);
				$info['sd_images'] = $in['images_path'];
			}
			$info['hand1']         = $hand1;
			$info['word1']         = $word1;
			if($sd_id1){
				$S                  = D('Common/SymbolDesign');
				$in                 = $S -> getInfo($sd_id1);
				$info['sd_images1'] = $in['images_path'];
			}
			$data['goods_attr']     = serialize($info);
			$data['goods_sn']       = $info['goods_sn'];
			$data['goods_number']   = $goods_number;
		}
		$data['agent_id'] = C('agent_id');
		switch($type){
			case 3:
				$where  = " agent_id='".C('agent_id')."' and goods_id=$goods_id AND goods_type=".$type." AND sku_sn='".$data['sku_sn']."' ";
				$where .= " AND ".$this->userAgent;
				$cartGood = M('cart')->where($where)->find();
				break;
			case 4:
				$where  = " agent_id='".C('agent_id')."' and goods_id=$goods_id AND goods_type=".$type." AND goods_attr= '".addslashes($data['goods_attr'])."'";
				$where .= " AND ".$this->userAgent;
				$cartGood = M('cart')->where($where)->find();
				break;
		}
		if(!empty($cartGood)){
			$save['goods_number']	= $cartGood['goods_number'];
			$saveAction = M('cart')->where($where)->save($save);
			
			$result['id']				= $cartGood['id'];
			$result['status']			= 100;
			$result['info']				= '添加购物车成功';
			$this->ajaxReturn($result);
		}
		$action	= $C->add($data);
		if($action){
			if($isDiy){
				cookie('goods_id',null);
				cookie('goods_type',null);
				cookie('selString',null);
				cookie('step1',null);
				cookie('diamondsDataUrl',null);
				$this->success('添加到购物车成功',U('Goods/goodsDiy'));
			}else{
				if($id_type == 'rapidBuy'){
					$result['status'] = 100;
					$result['id']	  = $action;
					$result['info']	  = '添加到购物车成功';
					$this->ajaxReturn($result);
				}else{
					$this->success('添加到购物车成功');
				}
			}
		}else{
			if($id_type == 'rapidBuy'){
					$result['status'] = 0;
					$result['info']	  = '添加到购物车失败';
					$this->ajaxReturn($result);
			}else{
				$this->error('添加到购物车失败');
			}
		}
    }

    /*	订单确认
	 *	2016-9-24增加立即购买直接跳转到订单确认页面功能			--fangkai
	 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
	*/
    public function orderConfirm(){
		$id = I('get.id','','intval');
	 	setcookie('cartStep','confirm');
	 	if($_SESSION['m']['uid'] == ''){
    		$this->redirect("/Public/login","请先登录再购买");
    	}

		$this->user_address = D('Common/UserAddress')->getAddressList($_SESSION['m']['uid']);

	 	$data =  $this -> getCartGoods($id, 1);


	 	if($data['total'] == 0){
	 		$this->error('您的购物车是空的或者无货，请添加商品!');
	 	}
		$this->user_delivery_mode	 = D('Common/OrderPayment')->this_dispatching_way();
	 	$this->id		= $id;
	 	$this->data 	= $data;
    	$this->total	= $data['total'];
    	$this->catShow	= 0;
    	$this->display();
	 }

    /**
     * 订单提交
	*/
    public function orderSubmit(){
		$id	= I('post.id','','intval');
		$DId= I('post.DId','','intval');		
    	if($_SESSION['m']['uid'] == ''){
    		$this->redirect("/Public/login","请先登录再提交订单");
    	}
		if($id){
			$where['id']		=	$id;
		}
		$where['uid']	 		=	$_SESSION['m']['uid'];
		$where['agent_id']		=	C('agent_id');
	 	$dataList = $this->getCartGoods($id, 1, 1);
	 	if(empty($dataList)){
	 		$this->redirect("/Order/orderConfirm");
	 	}
		$amount      = 0;
		$order_goods = array();
		$GC		     = D('Common/GoodsCategory');

		foreach($dataList as $key=>$val){
			$dataList[$key]['data'] = $goods = unserialize($val['goods_attr']);
			$order_goods[$key]['goods_price_a_up'] = 0;
			$order_goods[$key]['goods_price_b']    = 0;
			$order_goods[$key]['goods_price_b_up'] = 0;
			$order_goods[$key]['goods_price_c']    = 0;
			$order_goods[$key]['goods_price_c_up'] = 0;
			$order_goods[$key]['attribute']        = $val['goods_attr'];
			$order_goods[$key]['goods_type']       = $val['goods_type'];
			$order_goods[$key]['parent_type']      = $this->uType;
			$order_goods[$key]['parent_id'] = $this->traderId;
			if($val['goods_type'] ==1 ){      // 1为裸钻

				$order_goods[$key]['goods_number']   = 1;
				$order_goods[$key]['certificate_no'] = $goods['certificate_number'];
				$goods = getGoodsListPrice($goods, $_SESSION['m']['uid'], 'luozuan', 'single');
				$dataList[$key]['data']['price']        = $goods['price'];  //国际报价*汇率*折扣*重量/100
				$order_goods[$key]['goods_price'] = formatRound($dataList[$key]['data']['price'],2);
				$order_goods[$key]['goods_id']    = $dataList[$key]['data']['gid'];
				$order_goods[$key]['luozuan_type']   = $dataList[$key]['data']['luozuan_type'];
				$amount += formatRound($dataList[$key]['data']['price'],2);

			}else if($val['goods_type'] ==2){  //散货
				$order_goods2[$key]['attribute'] = unserialize($order_goods[$key]['attribute']);
				//查出产品的原价格
				$goods = D('GoodsSanhuo')->getInfo(array('goods_id'=>$val['goods_id']));
				$goods = getGoodsListPrice($goods, $_SESSION['m']['uid'], 'sanhuo', 'single');
				$dataList[$key]['data']['goods_price'] = $goods['goods_price'];
				$order_goods[$key]['goods_price2'] = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);
				if($order_goods[$key]['attribute']['goods_price2'] != $order_goods[$key]['goods_price2']){
					$order_goods2[$key]['attribute']['goods_price'] = $dataList[$key]['data']['goods_price'];
					$order_goods2[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
					$save['goods_attr'] = serialize($order_goods2[$key]['attribute']);
					$action = M('cart')->where(array('agent_id'=>C('agent_id'),'uid'=>$_SESSION['m']['uid'],'goods_id'=>$val['goods_id']))->save($save);
				}
				$order_goods[$key]['goods_number']   = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['goods_price']    = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute']['goods_price']  = $dataList[$key]['data']['goods_price'];
				$order_goods[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute'] = serialize($order_goods2[$key]['attribute']);
				$order_goods[$key]['goods_id'] = $dataList[$key]['data']['goods_id'];
				$amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);

			}else if($val['goods_type'] == 3){   // 3成品
				$order_goods[$key]['goods_number']   = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['data']['goods_attrs']['goods_name'] = addslashes($order_goods[$key]['data']['goods_attrs']['goods_name']);
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
					$price = $dataList[$key]['data']['goods_sku']['activity_price'];
				}else{
					$price = $dataList[$key]['data']['goods_price'];
				}
				$order_goods[$key]['goods_price']    = formatRound($price*$val['goods_number'],2);
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['goods_sku']['activity_price'];
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];

				$amount += formatRound($price*$val['goods_number'],2);
			}else if($val['goods_type'] ==4){  //戒托
				$order_goods[$key]['goods_number']   = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $goods['goods_sn'];
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
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['activity_price'];
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];

				$amount += formatRound($price*$val['goods_number'],2);
			}
			$order_goods[$key]['goods_number_up'] = 0;
			$order_goods[$key]['update_time']     = time();
			$order_goods[$key]['agent_id']        = C('agent_id');
		}
		$data['note_a']        = I('orderInfo');
		$data['order_goods']   = $order_goods;
		$data['order_sn']      = date("YmdHis").rand(00,100);
		$data['uid']           = $_SESSION['m']['uid'];
		$data['order_status']  = 0;
		$data['order_confirm'] = 0;
		$data['order_payment'] = 0;
		$data['delivery_mode'] = $DId;
		$UserControll = new UserController();
		$data['parent_id'] = $UserControll->getParentIDByMUID(); // 对应业务员ID
		//$data['address_id'] = I('post.address_id',0);//$_POST['address_id'];
		$data['address_id'] = $_POST['address_id'];
		$data['address_info']  = D('Common/UserAddress')->getAddressInfo($data['address_id']);
		$data['order_price'] = $amount;
		$data['create_time'] = time();
		$data['note'] = I("note",'');
		$data['parent_type'] = $this->uType; //订单所属分销商
		$data['agent_id'] = C('agent_id');
		$data['dollar_huilv'] = C("dollar_huilv");
		//print_r($data);die;
		if($this->createOrder($data)){
			$result['status'] = 1;
			$result['info'] = "订单提交成功";
			
			if($_SESSION['m']['phone']){
				//站点名称
				$site_name_md_arr[] = $data['order_sn'];
				$site_name_md_arr[] = C('site_name'); 
				$site_name_md_arr[] = C('site_contact');
				//发送短信
				$SMS = new \Common\Model\Sms();		
				$SMS->SendSmsByType($SMS::ORDER_SUBMIT_SUCCESS,$_SESSION['m']['phone'],$site_name_md_arr);		
			}	
			
			
		}else{
			$result['status'] = 0;
			$result['info'] = "请勿提交相同的产品";
		}
		$this->ajaxReturn($result);
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
			if(!M('cart')->where("uid='".$_SESSION['m']['uid']."' AND agent_id='".C('agent_id')."' AND goods_id='".$val['goods_id']."' AND goods_attr='".$val['attribute']."'")->delete()){
				$order_delete = false;
			}
		}
		if($order_id && $order_goods_add && $order_delete){
			$Order->commit();
			return true;
		}else{
			$Order->rollback();
			return false;
		}
	}

    /**
     * 订单提交完成页面
     */
    public function orderDone(){
        $this->display();
    }


    /*	订单购物车
	 *	2016-3-30修复散货计算最新价格
	 *	2016-9-24增加立即购买直接跳转到订单确认页面功能			--fangkai
	 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
	 *	goods_jiagong    1,不对购物车中的货品进行加工；0加工
	 *	del_no_goods 去掉无货  1是 0否

	*/
	public function getCartGoods($id, $del_no_goods = 0, $goods_jiagong=0){
		if($_SESSION['m']['uid'] or $_COOKIE['PHPSESSID']) {
			if($id){
				$where['id'] = $id;
			}
			$where['agent_id']	=	C('agent_id');
			$where[$this->userAgentKey]		=	$this->userAgentValue ;
		    //$cartGoods = M('cart')->where($where)->select();
		    $cartGoods = D('Common/Cart')->getUpdateCartGoodsList($where, $_SESSION['m']['uid']);
		    if($del_no_goods == 1){
		    	$cartGoods = D('Common/Cart')->delNullGoods($cartGoods);
		    }
		    if($goods_jiagong == 1){
			    return $cartGoods;
			}

		}
		$luozuan = array();
		$sanhuo = array();
		$product = array();
		$zmProduct = array();
		$total = 0; // 订单总金额
		if($cartGoods){
			foreach($cartGoods as $key=>$val){
				$cartGoods[$key]['goods_attrs'] = unserialize($val['goods_attr']);
				$val['goods_attrs'] = unserialize($val['goods_attr']);
				if($cartGoods[$key]['goods_attrs']['attribute']){
					$cartGoods[$key]['goods_attrs']['attributes'] = unserialize($cartGoods[$key]['goods_attrs']['attribute']);
				}
				switch($val['goods_type']){
					case 1: // 1 表示裸钻
						$luozuan['count']  += 1;
						$luozuan['weight'] += $cartGoods[$key]['goods_attrs']['weight'];
						$xiuzheng = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $_SESSION['m']['uid'], 'luozuan',  'single');

						$cartGoods[$key]['goods_attrs']['meikadanjia']   = $xiuzheng['cur_price'];
						$cartGoods[$key]['goods_attrs']['price']  		 = $xiuzheng['price'];

						$cartGoods[$key]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
						//判断钻石是否匹配到戒托中。
						$cartGoods[$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
						$luozuan['data'][] = $cartGoods[$key];
						$luozuan['total'] += formatRound($cartGoods[$key]['goods_attrs']['price'],2);
					break;
					case 2:// 散货
						//查出产品的原价格

						$goodsInfo = D('GoodsSanhuo')->getInfo(array('goods_id'=>$cartGoods[$key]['goods_id']));
						$xiuzheng = getGoodsListPrice($goodsInfo, $_SESSION['m']['uid'], 'sanhuo', 'single');
						$price 	  = $xiuzheng['goods_price'];
						//计算新价格（不用购车价格，防止修改散货加点导致数据不正确）
						$cartGoods[$key]['goods_attrs']['goods_price'] = $price;

						$sanhuo['weight'] += $cartGoods[$key]['goods_number']; //钻石重量
						$cartGoods[$key]['goods_attrs']['type_name'] = M("goods_sanhuo_type")->where("tid=".$cartGoods[$key]['goods_attrs']['tid'])->getField("type_name");
						$cartGoods[$key]['goods_attrs']['marketPrice'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*1.5,2);
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*$cartGoods[$key]['goods_number'],2);

						$sanhuo['data'][] = $cartGoods[$key];
						$sanhuo['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
						$sanhuo['total']  = formatRound($sanhuo['total'],2);
						$sanhuo['count'] += 1;
					break;
					case 3:     // 成品

						$product['count'] += 1;
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

						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$chengpin_price = $cartGoods[$key]['goods_attrs']['goods_sku']['activity_price'];
						}else{
							$chengpin_price = $cartGoods[$key]['goods_attrs']['goods_sku']['goods_price'];
						}
						$cartGoods[$key]['goods_attrs']['activity_price']	= $chengpin_price;
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($chengpin_price*$cartGoods[$key]['goods_number'],2);

						$product['data'][]                              = $cartGoods[$key];
						$product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
						break;
					case 4:// 戒托
						$product['count'] += 1;
						//获取材质名称
						$cartGoods[$key]['goods_attrs']['associateInfo']['material_name'] = M("goods_material")->where(array('material_id'=>$cartGoods[$key]['goods_attrs']['associateInfo']['material_id']))->getField('material_name');
						//获取主钻形状
						$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_name']      = M("goods_luozuan_shape")->where(array('shape_id'=>$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_id']))->getField('shape_name');
						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$dingzhi_price = $cartGoods[$key]['goods_attrs']['activity_price'];
						}else{
							$dingzhi_price = $cartGoods[$key]['goods_attrs']['goods_price'];
						}
						$cartGoods[$key]['goods_attrs']['goods_price2']  = formatRound($dingzhi_price*$cartGoods[$key]['goods_number'],2);

						$product['data'][] = $cartGoods[$key];
						$product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
					break;
				}
			}
		}
		$total = $product['total'] + $luozuan['total']+$sanhuo['total'];
		$data['luozuan']      = $luozuan;
		$data['sanhuo']       = $sanhuo;
		$data['count']        = $product['count'] + $luozuan['count'] + $sanhuo['count'];
		$data['product']      = $product;
		$data['total']        = formatRound($total,2);
		$data['total_weight'] = $sanhuo['weight'] + $luozuan['weight'];
		return $data;
	}

	//更改成品的订购数量

	public function changeGoodsNumber(){
		$goods_number = I('goods_number',1);
		$cartId       = I('cartId',0);
		$goods_type   = I("goods_type",1);
		$cartGoods    = M('cart') -> where($this->userAgent." AND agent_id='".C('agent_id')."' AND id=$cartId")->find();
		$data         = array('status'=>0,'msg'=>"购物车中的货品修改失败");
		if($cartId != 0 && !empty($cartGoods)){
			if($goods_type == 2){
				$cartGoods['goods_number'] = $goods_number;
			}else{
				$cartGoods['goods_number'] = intval($goods_number);
			}
			if(M('cart')->data($cartGoods)->save()){
				$data['status'] = 1;
				$data['msg'] = "购物车中的货品修改成功";
			}
		}
		$this->ajaxReturn($data);
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

	public function cartClear(){
		$data = array('status'=>0,'msg'=>"购物车中的货品删除失败");
		if(M('cart')->where("agent_id='".C('agent_id')."' AND ".$this->userAgent)->delete()){
			$data['status'] = 1;
			$data['msg'] = "购物车中的货品删除成功";
		}
		$this->ajaxReturn($data);
	}

	public function cartDelete(){
		$id         = I("id",0);
		$goods_type = I("goods_type",0);
		$where      = "agent_id='".C('agent_id')."' AND id=$id AND ".$this->userAgent;
		if($goods_type>0){
			$where .= " AND goods_type=$goods_type";
		}
		$cartGoods  = M('cart')->where($where)->find();
		$data       = array('status'=>0,'msg'=>"购物车中的货品删除失败");
		if(empty($cartGoods) || $id == 0){
			$data['msg'] = "购物车中不存在该货品";
		}else{
			if(M('cart')->where("agent_id='".C('agent_id')."' AND id=$id AND ".$this->userAgent)->delete()){
				$data['status'] = 1;
				$data['msg'] = "购物车中的货品删除成功";
			}
		}
		$this->ajaxReturn($data);
	}

    public function orderComplete(){ 
		$order = M('order')->where("uid='".$_SESSION['m']['uid']."' and agent_id='".C('agent_id')."'")->order('create_time DESC')->find();
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1  and agent_id = '.C("agent_id"))->select();	//线下支付
		if($order['delivery_mode']){
				$order	 = D('Common/OrderPayment')->this_dispatching_way($order['delivery_mode'],$order);
		}
		$this->order = $order;
		$this->display();
	}
}
