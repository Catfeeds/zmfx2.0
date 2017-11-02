<?php
/**
 * 下订单流程相关
 * @author ggc
 */
namespace Home\Controller;
class OrderController extends HomeController {
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
    
    
    public function orderCart($p=1,$n=13){
		if(C('new_rules')['zgoods_show']){
			$this->_orderCart($p,$n);exit;
		}
    	$data = $this -> getCartGoods(); 
    	$this->luozuan = $data['luozuan'];
    	$this->sanhuo  = $data['sanhuo'];
    	$this->product = $data['product'];
    	$this->data    = $data['cartGoods'];
    	$this->count   = $data['count'];
    	$this->total   = $data['total'];
    	$this->total_weight = $data['total_weight'];
    	$this->display();
    }
	protected function _orderCart($p=1,$n=13){
		pleaseLogin();
		$param = array();
		$param['n'] = $n;
		$param['agent_id']	=	C('agent_id');
		$uid = $_SESSION['web']['uid'];
		$session_id = session_id();
		if($uid){
			$param['uid'] = $uid;
		}else{
			$param['session_id'] = $session_id;
		}
		$cart_data = D('Common/Cart')->getBanfanCart($param);


		$this->cart_data = $cart_data;

		$this->display('dingzhi/zhouliufu/Order/orderCart');
	}

	public function ajax_get_price(){
		$cart_id_arr = I('cart_id');
		$return = array(
			'status'=>0,
			'msg'=>'获取价格失败',
		);
		$data = array();
		$goods_price = 0;
		if(is_array($cart_id_arr) && !empty($cart_id_arr)){
			$CM = M('banfang_cart');
			$GM = D('Common/goods');
			$where = array(
				'cart_id'=>array('in',$cart_id_arr),
			);
			$lists = $CM->where($where)->select();
			foreach($lists as $value){
				$goods = unserialize($value['goods_attr']);
				$param = $goods['goods_attr_param'];
				$temp = $GM->productGoodsInfo($param);
				if($temp['status']==100){
					$info = $temp['info'];
					$goods_price += $info['goods_price']*$value['goods_number'];
				}


			}
		}
		$data['goods_price'] = $goods_price;
		$return['data'] = $data;
		$this->ajaxReturn($return);
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
    
	
	/*	订单购物车
	 *	2016-3-30修复散货计算最新价格
	 *	2016-9-24增加立即购买直接跳转到订单确认页面功能			--fangkai
	 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
	 *	del_no_goods 去掉无货  1是 0否
	 *	goods_jiagong    1,不对购物车中的货品进行加工；0加工
	*/
	
	public function getCartGoods($id, $del_no_goods= 0, $goods_jiagong = 0){

		if($_SESSION['web']['uid'] or $_COOKIE['PHPSESSID']) {
			if($id){
				$where['id'] = $id;
			}
			$where['agent_id']	=	C('agent_id');
			$where[$this->userAgentKey]		=	$this->userAgentValue ;
		    $cartGoods = D('Common/Cart')->getUpdateCartGoodsList($where, $_SESSION['web']['uid']);
		    if($del_no_goods == 1){
		    	$cartGoods = D('Common/Cart')->delNullGoods($cartGoods);
		    	
		    }
		    if($goods_jiagong == 1){
			    return $cartGoods;
			}

		}

		$luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
		$deputystoneM        = M('goods_associate_deputystone');
		$luozuan 			 = array();
		$sanhuo  		     = array();
		$product 			 = array();
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
						$xiuzheng = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $_SESSION['web']['uid'], 'luozuan',  'single');						
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
						if( $cartGoods[$key]['goods_id'] ){
							$GSobj     = D('GoodsSanhuo');
							$where     = array('goods_id'=>array('in',$cartGoods[$key]['goods_id']));
							$goodsInfo = $GSobj -> getInfo($where);
							$xiuzheng         = getGoodsListPrice($goodsInfo, $_SESSION['web']['uid'], 'sanhuo', 'single');
							$price 	          = $xiuzheng['goods_price'];
							$sanhuo['weight'] += $cartGoods[$key]['goods_number']; //钻石重量
							$cartGoods[$key]['goods_attrs']['type_name'] = M("goods_sanhuo_type")->where("tid=".$cartGoods[$key]['goods_attrs']['tid'])->getField("type_name"); 
							$cartGoods[$key]['goods_attrs']['marketPrice'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*1.5,2);
							$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*$cartGoods[$key]['goods_number'],2);     
							$sanhuo['data'][] = $cartGoods[$key]; 
							$sanhuo['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2); 
							$sanhuo['count'] += 1;
						}
					break;
					case 3:     // 成品 
						$product['count'] += $val['goods_number'];
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
						
						//如果为活动商品则取值活动价格，否则取值普通售卖价格
						if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
							$dingzhi_price = $data['dingzhi_goods_attr']['activity_price'];
						}else{
							$dingzhi_price = $data['dingzhi_goods_attr']['goods_price'];
						}
						$cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($dingzhi_price*$cartGoods[$key]['goods_number'],2);
						
						$cartGoods[$key]['goods_attrs']['attrs'] = '';
						$cartGoods[$key]['goods_attrs']['associateInfo']['weights_name'] = formatRound($data['dingzhi_goods_attr']['selected']['material_weight'],4);
						$cartGoods[$key]['goods_attrs']['luozuanInfo']['weights_name'] = formatRound($data['dingzhi_goods_attr']['selected']['luozuan_weight'],4);
						$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_name'] = $data['dingzhi_goods_attr']['selected']['shape_name'];
						$cartGoods[$key]['goods_attrs']['luozuanInfo']['ct_mm'] = $data['dingzhi_goods_attr']['selected']['ct_mm'];
						$cartGoods[$key]['goods_attrs']['deputystone_name'] = '副石 : '.$data['dingzhi_goods_attr']['selected']['deputystone_name'];
						if($data['dingzhi_goods_attr']['selected']['deputystone_weight']){
							if($data['dingzhi_goods_attr']['selected']['deputystone_weight']){ //钻石
								$cartGoods[$key]['goods_attrs']['deputystone_name'].=$data['dingzhi_goods_attr']['selected']['deputystone_number'].'颗'.$data['dingzhi_goods_attr']['selected']['deputystone_weight'];
							}else{ //彩宝
								$cartGoods[$key]['goods_attrs']['deputystone_name'].=$data['dingzhi_goods_attr']['selected']['deputystone_number'];
							}
							
						}
						$cartGoods[$key]['goods_attrs']['jewelry_name'] = $data['goods_attrs']['price_info']['price_info']['base_process_info']['jewelry_name'];
						
						$product['count'] += $val['goods_number'];
						$product['data'][] = $cartGoods[$key];
						$product['total'] += $cartGoods[$key]['goods_attrs']['goods_price2'];
					break;
				}
			}
		}
		$total                = $product['total'] + $luozuan['total']+$sanhuo['total'];
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
		$array          = array("certificate_number"=>$certificate_number);
		$arraySerialize = serialize($array);
		$arraySerialize =  substr($arraySerialize,5,-1);
		$where          = "agent_id='".C('agent_id')."' and goods_attr like '%$arraySerialize%' AND uid!='".$_SESSION['web']['uid']."'";
		
		if( M('cart') -> where($where) -> find() ){
			return "待定";
		}else{
			return "有货";
		}
	}
	
	//更改成品的订购数量 
	
	public function changeGoodsNumber(){
		$goods_number = I('goods_number',1);
		$cartId       = I('cartId',0); 
		$goods_type   = I("goods_type",1); 
		$cartGoods    = M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND id=$cartId")->find(); 
		$data = array('status'=>0,'msg'=>"购物车中的货品修改失败");
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
	
	// 删除购物车中的货品
	
	public function cartDelete(){
		$id = I("id",0); 
		$goods_type = I("goods_type",0);
		$where = "agent_id='".C('agent_id')."' and id=$id AND ".$this->userAgent;
		if($goods_type>0){
			$where .= " AND goods_type=$goods_type";
		}
		$cartGoods = M('cart')->where($where)->find();
		$data = array('status'=>0,'msg'=>"购物车中的货品删除失败");
		if(empty($cartGoods) || $id == 0){
			$data['msg'] = "购物车中不存在该货品";
		}else{
			if(M('cart')->where("agent_id='".C('agent_id')."' and id=$id AND ".$this->userAgent)->delete()){
				$data['status'] = 1;
				$data['msg'] = "购物车中的货品删除成功";
			}
		}
		$this->ajaxReturn($data);
	}
	
	public function cartClear(){
		$data = array('status'=>0,'msg'=>"购物车中的货品删除失败"); 
		if(M('cart')->where("agent_id='".C('agent_id')."' and ".$this->userAgent)->delete()){
			$data['status'] = 1;
			$data['msg'] = "购物车中的货品删除成功";
		}
		$this->ajaxReturn($data);
	}
	
	/*	订单确认
	 *	2016-9-24增加立即购买直接跳转到订单确认页面功能			--fangkai
	 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
	*/	
	public function orderConfirm(){
		if(C('new_rules')['zgoods_show']){
			$this->_orderConfirm();exit;
		}
		$id = I('get.id','','intval');
		if($_SESSION['web']['uid'] == ''){
    		$this->error('请先登录再购买',U('Home/Public/login'));
    	}
	 	setcookie('cartStep','confirm');
		 	
		$this->user_address = $this->userAddList = D('Common/UserAddress')->getAddressList($this->uId);
	 	$data = $this->getCartGoods($id, 1);

	 	if($data['total'] == 0){  
	 		//$this->redirect("/Home/Order/orderCart");
	 		$this->error('您的购物车无货或商品无货',U('Home/Order/orderCart'));
	 	}

		/* 支付模式 */
		$PM = M('payment_mode');
		$pay_where['pm.agent_id'] =  C('agent_id');
		$pay_where['pc.pay_status'] =  1;
		$join[] = 'LEFT JOIN zm_payment_config AS pc ON pc.mode_id = pm.mode_id';
		$field = 'pm.*,pc.pay_status,pc.pay_attr';
		$this->payModeList = $PM->alias('pm')->join($join)->field($field)->where($pay_where)->select();
		
		/* 支付宝绑定状态 */
		$this->alipay = 0;
		$agent_config = M("agent")->where("agent_id = ".C('agent_id'))->find();
		if($agent_config && $agent_config['alipayid']!='' && $agent_config['alipaykey']!=''){
			$this->alipay = 1;
		}
		$this->LinePayMode = M('payment_mode_line')->where(' atype = 1 and  agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付
		
		$this->id		= $id;
    	$this->luozuan 	= $data['luozuan'];
    	$this->sanhuo  	= $data['sanhuo'];
    	$this->product 	= $data['product'];
    	$this->jietuo  	= $data['jietuo'];   
    	$this->zmProduct= $data['zmProduct'];
    	$this->data 	= $data['cartGoods'];
    	$this->total 	= $data['total']; 
		$this->total_weight	= $data['total_weight'];
		$this->deliModeList = D('Common/OrderPayment')->this_dispatching_way();		
		//$this->count = $data['count'];
    	$this->display();
	 }

	protected function _orderConfirm(){
		$uid = intval($_SESSION['web']['uid']);
		if(!$uid){
			$this->error('请先登录再购买',U('Home/Public/login'));
		}
		$cart_id_arr = session('cart_arr');
		if(empty($cart_id_arr)){
			$this->error('请先加入商品到购物车');
		}
		$cart_id_arr = unserialize($cart_id_arr);
		if(empty($cart_id_arr)){
			$this->error('请先加入商品到购物车');
		}
		$param = array();
		$param['agent_id']	=	C('agent_id');
		$param['cart_id_arr']	=	$cart_id_arr;
		$uid = session('web.uid');
		//$this->my_shop_arr = M('banfang_shop')->where(array('uid'=>$uid,'agent_id'=>C('agent_id'),'shop_name'=>array('neq','')))->find();

		$session_id = session_id();
		if($uid){
			$param['uid'] = $uid;
		}else{
			$param['session_id'] = $session_id;
		}
		$cart_data = D('Common/Cart')->getBanfanCart($param);
		$this->cart_data = $cart_data;

		$this->display('dingzhi/zhouliufu/Order/orderConfirm');
	}

	public function getUserShop(){

		$conditions = array(
				'url'=>'http://zlfyun.btbzm.com/Home/Zlf/ajaxGetWangdian.html',
				'data'=>array('id'=>session('web.username'),'shop_address'=>trim(I('shop_address'))),
				'not_json'=>1
		);
		$result = _httpsRequest($conditions);
		$result = substr($result,3);
		$this->ajaxReturn(json_decode($result));
	}
	public function ajax_set_cart($p=1,$n=13){
		//type  1 添加板房商品   2 修改板房商品 3 删除单个板房商品    4 删除单个购物车商品  5 清除购物车
		$param = array(
				'uid'=>intval(session('web.uid')),
				'session'=>session_id(),
				//cart_id为选中的cart_id	id为当前操作的元素
				'cart_id'=>I('cart_id'),
				'id'=>I('id'),
				'n'=>$n,
				'agent_id'=>C('agent_id'),
				'obj_data'=>I('obj_data'),

		);

		$type = intval(I('post.type'));
		$this->type = $type;
		$this->ban_o  = intval(I('ban_o'));
		$CM = D('Common/Cart');
		$data = array();
		$where = array();
		$return = array(
				'status'=>0,
				'msg'=>'操作失败',

		);

		switch($type){
			case 1:
				$param['id'] = $param['id'][0];
				$vo = $CM->fuzhiBanfangCartInfo($param)['data'];
				$this->vo = $vo;
				$data  = $this       -> fetch('dingzhi/zhouliufu/Order/ajaxGetMyCart');
				$return = array(
						'status'=>100,
						'msg'=>'操作成功',
						'cart_money'=>$vo['cart_money'],
						'data'=>$data
				);
				break;
			case 2:
				$param['id'] = $param['id'][0];
				$param['obj_data']['is_change'] = 1;
				$return = $CM->updateMyBanfangCart($param);
				break;
			case 3:
				$param['id'] = $param['id'][0];
				$return = $CM->deleteMyOneBanfangCart($param);
				break;
			case 4:
				$return = $CM->deleteMyBanfangCart($param);
				break;
			case 5:
				$return = $CM->deleteMyBanfangCartAll($param);
				break;
			case 6:
				$cart_data = $CM->getBanfanCart($param);
				$this->cart_data = $cart_data;
				// p($this->cart_data);exit;
				$data  = $this       -> fetch('dingzhi/zhouliufu/Order/ajaxGetMyCart');
				$return = array(
						'status'=>100,
						'msg'=>'操作成功',
						'data'=>$data,
						'money_all'=>$cart_data['cart_money_all'],
						'count_number'=>$cart_data['count_number']
				);
				break;
			case 7:
				$param['id'] = $param['id'][0];
				$param['obj_data']['is_change'] = 1;
				$return = $CM->updateMyBanfangCart($param);
				break;
			default:
				break;
		}
		$this->ajaxReturn($return);
	}
	public function ajax_set_cart_number(){
		if(is_numeric(I('id'))){
			$where = "agent_id='".C('agent_id')."' and ".$this->userAgent.' and id='.I('id');
			$model = M('banfang_cart');
			$cart = $model->where($where)->find();
			if($cart['goods_number']>1){
				if(I('type')=='reduce'){
					$bool = $model->where($where)->setDec('goods_number');
				}
			}
			if(I('type')=='add'){
				$bool = $model->where($where)->setInc('goods_number');
			}
			if($bool){
				echo 1;
			}
		}
	}
	public function OrderScanPre(){
		$return = array(
				'status'=>0,
				'msg'=>'请先登录'
		);
		if($_SESSION['web']['uid'] == ''){
			$this->ajaxReturn($return);
		}
		$cart_id_arr = I('cart_id');
		$return = array(
				'status'=>0,
				'msg'=>'请选择商品',
		);
		session('cart_arr',serialize($cart_id_arr));
		$cart_goods = array();
		if(is_array($cart_id_arr) && !empty($cart_id_arr)){
			$CM = M('banfang_cart');
			$GM = D('Common/goods');
			$where = array(
					'uid'=>intval($_SESSION['web']['uid']),
					'cart_id'=>array('in',$cart_id_arr),
			);
			$lists = $CM->where($where)->select();

			foreach($lists as $value){
				$goods = unserialize($value['goods_attr']);
				$param = $goods['goods_attr_param'];
				$temp = $GM->productGoodsInfo($param);
				if(!empty($temp['info']['select_param']['error'])){
					$return['msg'] = $temp['info']['select_param']['error'][0];
					break;
				}
				if($temp['status']==100){
					$cart_goods[] = $temp['info'];
				}
			}
		}

		if(!empty($cart_goods)){
			$return = array(
					'status'=>100,
					'msg'=>'确认订单',
			);
		}
		$this->ajaxReturn($return);
	}
	protected function _orderSubmit(){
		$uid = intval($_SESSION['web']['uid']);
		if(!$uid){
			$this->error('请先登录再购买',U('Home/Public/login'));
		}
		$cart_id_arr = session('cart_arr');
		if(empty($cart_id_arr)){
			$this->error('请先加入商品到购物车');
		}
		$cart_id_arr = unserialize($cart_id_arr);
		if(empty($cart_id_arr)){
			$this->error('请先加入商品到购物车');
		}

		$lxr = trim(I('lxr'));
		$lxfs = trim(I('lxfs'));
		$shop_name = trim(I('shop_name'));
		// if(!$lxr){
		// 	$this->ajaxReturn(['msg'=>'请输入联系人']);
		// }
		// if(!is_mobile($lxfs)){
		// 	$this->ajaxReturn(['msg'=>'请输入正确的电话号码']);
		// }
		if(!$shop_name){
			$this->ajaxReturn(['msg'=>'请输入网店名称']);
		}
		$order_type_arr = array(
				'1'=>'客户订货',
				'2'=>'公司订货'
		);

		$order_type = intval(I('order_type'));
		$order_type_name = $order_type_arr[$order_type] ? $order_type_arr[$order_type] : '';
		if(!$order_type_name){
			$this->ajaxReturn(['msg'=>'请选择订单类型']);
		}
		$data = array();
		$goods_price = 0;
		$cart_goods = array();

		if(is_array($cart_id_arr) && !empty($cart_id_arr)){
			$CM = M('banfang_cart');
			$GM = D('Common/goods');
			$where = array(
				'uid'=>intval($_SESSION['web']['uid']),
				'cart_id'=>array('in',$cart_id_arr),
			);
			$lists = $CM->where($where)->select();
			foreach($lists as $value){
				$goods = unserialize($value['goods_attr']);
				$param = $goods['goods_attr_param'];
				$temp = $GM->productGoodsInfo($param);
				if($temp['status']==100){
					$goods_price += $temp['goods_price'];
					$cart_goods[] = $temp['info'];
				}

			}
		}

		$param = array(
				//'shop_id'=>intval(I('shop_id')),
				'shop_code'=>trim(I('shop_code')),
				'shop_address'=>$shop_name,
				'uid'=>$uid,
				'order_type_name'=>$order_type_name,
				'note'=>trim(I('note')),
				'cart_id_arr'=>$cart_id_arr,
				'lxr'=>$lxr,
				'lxfs'=>$lxfs,
		);


		$return = D('Common/Order')->CreateBanfangOrder($param);
		$this->ajaxReturn($return);
	}

	public function orderSubmit(){
		if(C('new_rules')['zgoods_show']){
			 $this->_orderSubmit();exit;
		}
		$id	= I('post.id','','intval');
		$result['status'] = -1;
    	if($_SESSION['web']['uid'] == ''){
			$result['info'] = "登录已失效，请重新登录";
			$this->ajaxReturn($result);
    	}
		$DId	= I('post.DId','','intval');
		if(empty($DId)){
			$result['info'] = "请选择配送方式";
			$this->ajaxReturn($result);
		}
		$payment	= I('post.payment','','intval');
		if($id){
			$where['id']		= $id;
		}
		$where['uid']	 		= $_SESSION['web']['uid'];
		$where['agent_id']		= C('agent_id');
	 	$dataList = $this->getCartGoods($id, 1, 1);

	 	if(empty($dataList)){
	 		$this->redirect("/Home/Order/orderConfirm");
	 	}

		$amount = 0;
		$order_goods = array();
		$GC			 = D('Common/GoodsCategory');
		foreach($dataList as $key=>$val){
			$dataList[$key]['data'] = $goods = unserialize($val['goods_attr']);
			$order_goods[$key]['goods_price_a_up'] = 0;
			$order_goods[$key]['goods_price_b']    = 0;
			$order_goods[$key]['goods_price_b_up'] = 0;
			$order_goods[$key]['goods_price_c']    = 0;
			$order_goods[$key]['goods_price_c_up'] = 0;
			$order_goods[$key]['attribute']        = $val['goods_attr'];
			$order_goods[$key]['goods_type']       = $val['goods_type'];
			$order_goods[$key]['parent_type']      = 1;
			$order_goods[$key]['parent_id']        = $this->traderId;
			if($val['goods_type'] ==1 ){           // 1为裸钻

				$order_goods[$key]['goods_number']   = 1;
				$order_goods[$key]['certificate_no'] = $goods['certificate_number'];
				$goods                               = getGoodsListPrice($goods, $_SESSION['web']['uid'], 'luozuan', 'single');
				$dataList[$key]['data']['price']     = $goods['price'];  //国际报价*汇率*折扣*重量/100
				$order_goods[$key]['goods_price']    = formatRound($dataList[$key]['data']['price'],2);
				$order_goods[$key]['goods_id']       = $dataList[$key]['data']['gid'];
				$order_goods[$key]['luozuan_type']   = $dataList[$key]['data']['luozuan_type'];
				$amount                             += formatRound($dataList[$key]['data']['price'],2);

			}else if($val['goods_type'] ==2){  //散货

				$order_goods2[$key]['attribute']                     = unserialize($order_goods[$key]['attribute']);
				//查出产品的原价格
				$GSobj = D('GoodsSanhuo');
				$where = array('goods_id'=>array('in',$val['goods_id']));
				$goods = $GSobj -> getInfo($where);
				$goods                                               = getGoodsListPrice($goods, $_SESSION['web']['uid'], 'sanhuo', 'single');
				$dataList[$key]['data']['goods_price']               = $goods['goods_price'];
				$order_goods[$key]['goods_price2']                   = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);
				if($order_goods[$key]['attribute']['goods_price2']  != $order_goods[$key]['goods_price2']){
					$order_goods2[$key]['attribute']['goods_price']  = $dataList[$key]['data']['goods_price'];
					$order_goods2[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
					$save['goods_attr']                              = serialize($order_goods2[$key]['attribute']);
					$action = M('cart') -> where(array('agent_id'=>C('agent_id'),'uid'=>$_SESSION['web']['uid'],'goods_id'=>$val['goods_id']))->save($save);
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
				$order_goods[$key]['certificate_no']                    = $val['goods_sn'];
				$order_goods[$key]['data']['goods_attrs']['goods_name'] = addslashes($order_goods[$key]['data']['goods_attrs']['goods_name']);
				$order_goods[$key]['goods_id']    						= $dataList[$key]['data']['goods_id'];
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
			}else if( $val['goods_type'] == 4 ){  //戒托
				$order_goods[$key]['goods_number']     = $val['goods_number'];
				$order_goods[$key]['certificate_no']   = $dataList[$key]['data']['goods_sn'];
				$order_goods[$key]['goods_id']         = $dataList[$key]['data']['goods_id'];
				$order_goods[$key]['category_id']	   = $dataList[$key]['data']['category_id'];
				$cate_where['category_id']			   = $dataList[$key]['data']['category_id'];
				$catInfo = $GC->getCategoryInfo($cate_where);
				if($catInfo[0]['pid'] == 0){
					$order_goods[$key]['p_category_id']= $catInfo[0]['category_id'];
				}else{
					$order_goods[$key]['p_category_id']= $catInfo[0]['pid'];
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
			}else if($val['goods_type'] == 16){ //szzmzb 板房
				$order_goods[$key]['goods_number']     = $val['goods_number'];
				$order_goods[$key]['certificate_no']   = $dataList[$key]['data']['goods_sn'];
				$order_goods[$key]['goods_id']         = $dataList[$key]['goods_id'];
				$order_goods[$key]['category_id']	   = $dataList[$key]['data']['category_id'];
				
				$where = [];
				$where['banfang_category_id'] = $dataList[$key]['data']['category_id'];
				$where['banfang_jewely_id'] = $dataList[$key]['data']['jewelry_id'];
				$goods_category_banfang_mp = M('goods_category_banfang_mp')->where($where)->find();

				$cate_where['category_id']			   = $goods_category_banfang_mp['category_id'];
				$catInfo = $GC->getCategoryInfo($cate_where);

				if($catInfo[0]['pid'] == 0){
					$order_goods[$key]['p_category_id']= $catInfo[0]['category_id'];
				}else{
					$order_goods[$key]['p_category_id']= $catInfo[0]['pid'];
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
		$data['order_sn']      = date("YmdHis").rand(10,99);
		$data['uid']           = $_SESSION['web']['uid'];
		$data['order_status']  = 0;
		$data['order_confirm'] = 0;
		$data['order_payment'] = $payment;
		$data['delivery_mode'] = $DId;
		$data['parent_id']     = getParentIDByUID(); // 对应业务员ID
		$data['address_id']    = $_POST['address_id'];
		$data['address_info']  = D('Common/UserAddress')->getAddressInfo($data['address_id']);
		$data['order_price']   = $amount;
		$data['create_time']   = time();
		$data['dollar_huilv']  = C("dollar_huilv");
		$data['parent_type']   = $this->uType; //订单所属分销商
		$data['agent_id']      = C('agent_id');
		if($order_id = $this->createOrder($data)){
			$result['status'] = 1;
			$result['info'] = "订单提交成功";
			$result['order_id'] = $order_id;
			//发送用户消息
			$username = M("User")->where('uid='.$_SESSION['web']['uid'])->getField('username');
			$delivery_mode = M("delivery_mode")->where('mode_id='.$data['delivery_mode'])->getField('mode_name');
			$title = "下单成功";
			$content = "用户".$username." 您已下单成功！订单编号：".$data['order_sn']."，配送方式：".$delivery_mode."，支付方式：线下转账， 订单金额：￥". $amount;
			$s = new \Common\Model\User();
			$s->sendMsg($_SESSION['web']['uid'],$content, $title);

			if($_SESSION['web']['phone'] && $payment=='0'){
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
			$result['info'] = "订单提交错误";
		}

		$this->ajaxReturn($result);
	}

	 
	public function orderComplete(){
		if(C('new_rules')['zgoods_show']){
			$this->_orderComplete();exit;
		}
		$order = M('order')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."'")->order('create_time DESC')->find();
		if($order['delivery_mode']){
				$order	 = D('Common/OrderPayment')->this_dispatching_way($order['delivery_mode'],$order);
		}		
		$this -> order = $order;

		$this->LinePayMode = M('payment_mode_line')->where(' atype = 1 and  agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付
		$this -> display();
	}

	public function _orderComplete(){
		$order_id = I('order_id');
		$uid = session('web.uid');
		$param = array('uid'=>$uid,'order_id'=>$order_id);
		$order_info = D('Common/Order')->getBanfangOrderInfo($param);
		$this->order_info = $order_info;
		$this->display('dingzhi/zhouliufu/Order/orderComplete');
	}
	 
	 
	 /* 生成订单函数 **/
	public function createOrder($data){
		if($data== '' || $data == null){
			return false;
		}                     
		$Order    =  M('order');
		$Order    -> startTrans();             
		$order_id =  $Order->data($data)->add();  
		$order_goods_add = true;
		$order_delete    = true;
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
            $cart = M('cart')->where($where)->find();
            if(!M('cart')->where($where)->delete()){
                $order_delete = false;
            }
            
//			if(!M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."' AND goods_id='".$val['goods_id']."' AND goods_attr='".$val['attribute']."'")->delete()){
//				$order_delete = false;
//			}
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

	//添加到购物车 成品
	public function add2cart(){
		$id_type	  = I('id_type','');
		$gid          = I("gid"); 
		$sku_sn       = I("sku_sn",0);
		$goods_type   = 3; 
		$goods_number = I("goods_number",1,'intval'); 

		$G            = D("Common/Goods");
		$goods        = $G -> get_info($gid,0,'shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');

        // 列表页直接加入购物车，则设置默认属性
        $M          = D('Common/Goods');
        $goodsInfoMore  = $M -> getProductInfoAfterAddPoint($goods, $_SESSION['web']['uid']);
        // 若没选择sku_sn
        if(empty($sku_sn)){
            if(isset($goodsInfoMore['similar']) && !empty($goodsInfoMore['similar'])){
                $sku_sn = $goodsInfoMore['similar'][0]['sku_sn'];
            }
        }

		$data         = array('status'=>0,'msg'=>'添加购物车失败');
		if($gid<0 || empty($goods)){
			$data['msg'] = "不存在该货品";
			$this->ajaxReturn($data);
		} 
		if($goods['goods_type']==3 && $goods_number>$goods['goods_number']){
			$data['msg'] = "该商品库存不足";
			$this->ajaxReturn($data);
		}
		$where  = " agent_id='".C('agent_id')."' and goods_id=$gid AND goods_type=".$goods_type." AND sku_sn='$sku_sn' ";
		$where .= " AND ".$this->userAgent;
		$cartGood = M('cart')->where($where)->find(); 
		
		if(!empty($cartGood)){
			$save['goods_number']	= $cartGood['goods_number']+1;
			$saveAction = M('cart')->where($where)->save($save);
			$data['id']				= $cartGood['id'];
			$data['status']			= 100;
			$data['msg']			= '添加购物车成功';
		}else{                                                 
			$goods['sku_sn'] = $sku_sn;
			if($goods_type == 3){
				$temp                 = $G -> getGoodsSku($gid ," sku_sn = '$sku_sn' ");
				
				$temp['goods_type']   = $goods_type;
				$temp                 = getGoodsListPrice($temp, $_SESSION['web']['uid'], 'consignment', 'single');
				$goods['goods_price'] = formatRound($temp['goods_price'],2);
				$goods['activity_price']	= formatRound($temp['activity_price'],2);		//活动商品价格
				$goods['marketPrice'] = formatRound($temp['marketPrice'],2);
				$goods['goods_sku']   = $temp;
				$goods['consignment_advantage'] = $temp["consignment_advantage"];
				$goods['activity_status']	= $temp["activity_status"];						//活动商品标识
			} 
			$goodsData['goods_attr']        = serialize($goods);  
			$goodsData[$this->userAgentKey] = $this->userAgentValue;
			$goodsData['goods_type']        = $goods_type;
			$goodsData['goods_id']          = $gid;
			$goodsData['goods_sn']          = $goods['goods_sn'];
			$goodsData['goods_number']      = $goods_number;
			$goodsData['sku_sn']            = $sku_sn;
			$goodsData['agent_id']          = C('agent_id');
			$action	= M('cart')->add($goodsData);
			if($action){
				$data['id']		= $action;
				$data['msg']    = "添加成功";
				$data['status'] = 100;
			}else{
				$data['msg']    = "添加失败";
				$data['status'] = 0;
			}
		}
		$this->ajaxReturn($data);
	}
	
	public function addZMGoods2cart(){
		$id_type	   = I('id_type','');
		$gid           = I("gid",0);
		$dingzhi_id     = I("dingzhiId");
		$goods_type    = I("goods_type",4);

		$luozuan       = I("luozuan",0);
		$goods_number  = I("goods_number",1);

		$materialId    = I("materialId",0);
		$diamondId     = I("diamondId",0);
		$deputystoneId = I("deputystoneId",0);

		$word          = I("word",""); 
		$hand          = I("hand","");
		$sd_id         = I('sd_id', 0);
		$word1         = I("word1",""); 
		$hand1         = I("hand1","");
		$sd_id1        = I("sd_id1","");

		$G             = D("Common/Goods");
		$goodsInfo     = $G -> get_info($gid,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');

		// 列表页直接加入购物车，则设置默认属性
        $M          = D('Common/Goods');
        $goodsInfoMore  = $M -> getProductInfoAfterAddPoint($goodsInfo, $_SESSION['web']['uid']);
		// 若没选择材质，则取默认材质
        if(empty($materialId)){
            if(isset($goodsInfoMore['associate_info']) && !empty($goodsInfoMore['associate_info'])){
                $materialId = $goodsInfoMore['associate_info'][0]['material_id'];
            }
        }

        // 若没选择diamondId和diamondWeight
        if(empty($diamondId)){
            if(isset($goodsInfoMore['associate_luozuan']) && !empty($goodsInfoMore['associate_luozuan'])){
                $diamondId = $goodsInfoMore['associate_luozuan'][0]['gal_id'];
                $dismondWeight = $goodsInfoMore['associate_luozuan'][0]['weights_name'];
            }
        }

        // 若没选择deputystoneId
        if(empty($deputystoneId)){
            if(isset($goodsInfoMore['associate_deputystone']) && !empty($goodsInfoMore['associate_deputystone'])){
                $deputystoneId = $goodsInfoMore['associate_deputystone'][0]['gad_id'];
            }
        }

		$luozuanInfo   = $G -> getGoodsAssociateLuozuanAfterAddPoint("goods_id=$gid AND gal_id=$diamondId");
		$associateInfo = $G -> getGoodsAssociateInfoAfterAddPoint("goods_id=$gid AND zm_goods_associate_info.material_id=$materialId ");
		if($deputystoneId and $deputystoneId!='undefined') {
			$deputystone = $G -> getGoodsAssociateDeputystoneAfterAddPoint("gad_id=$deputystoneId");
		}
		if($luozuan >0){
			$certificate_number           = D("GoodsLuozuan") -> where(" gid = $luozuan ") -> getField("certificate_number");
			$goodsInfo['matchedDiamond']  = $certificate_number;
		}                                                   
		$returnData                       = array("status"=>100,"msg"=>"定制成功"); 	
		if($luozuanInfo && $associateInfo && $goodsInfo){
			$material                     = $G -> getGoodsMaterialAfterAddPoint(" material_id = $materialId ");
			if( !$goodsInfo['price_model'] ){
				$goodsInfo['goods_price'] = $G -> getJingGongShiPrice($material,$associateInfo,$luozuanInfo,$deputystone);				
			}else{
				$category_name = M('goods_category') -> where(" category_id = '$goodsInfo[category_id]' ") -> getField('category_name');
				$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
				if( $is_size ){
					$size                     = $G -> getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
					$goodsInfo['goods_price'] = $size['size_price'];
					$is_dui                   = InStringByLikeSearch($category_name,array('对戒'));
					if($is_dui){
						$size                     = $G -> getGoodsAssociateSizeAfterAddPoint(" sex = '1' and min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
						$goodsInfo['goods_price'] = $size['size_price'];
						$size                     = $G -> getGoodsAssociateSizeAfterAddPoint(" sex = '2' and min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
						$goodsInfo['goods_price'] = $size['size_price']+$goodsInfo['goods_price'];
					}
				} else {
					$ass                      = $G->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
					$goodsInfo['goods_price'] = $ass['fixed_price'];
				}
			}
			$goodsInfo['associateInfo'] = $associateInfo;
			$goodsInfo['luozuanInfo']   = $luozuanInfo; 
			$goodsInfo['deputystone']   = $deputystone;
			$goodsInfo['hand']          = $hand;
			$goodsInfo['word']          = $word;
			$goodsInfo['hand1']         = $hand1;
			$goodsInfo['word1']         = $word1;
			$goodsInfo['sd_id']          = $sd_id;
			$goodsInfo['sd_id1']         = $sd_id1;
			if($sd_id){
				$S                      = D('Common/SymbolDesign');
				$info                   = $S -> getInfo($sd_id);
				$goodsInfo['sd_images'] = $info['images_path'];
			}else{
				$goodsInfo['sd_images']     = '';
			}
			if($sd_id1){
				$S                       = D('Common/SymbolDesign');
				$info                    = $S -> getInfo($sd_id1);
				$goodsInfo['sd_images1'] = $info['images_path'];
			}else{
				$goodsInfo['sd_images1']    = '';
			}
			$goodsInfo                  = getGoodsListPrice($goodsInfo, $_SESSION['web']['uid'], 'consignment', 'single');
			$data['goods_attr']         = serialize($goodsInfo);
			$data[$this->userAgentKey]  = $this->userAgentValue;
			$data['goods_id']           = $gid;
			$data['goods_type']         = $goods_type;
			$data['goods_number']       = $goods_number;
			$data['agent_id']           = C('agent_id');
			$where  = " agent_id='".C('agent_id')."' and goods_id=$gid AND goods_type=".$goods_type." AND goods_attr= '".addslashes($data['goods_attr'])."'";
			$where .= " AND ".$this->userAgent;
			$cartGood = M('cart')->where($where)->find();
			if($dingzhi_id!=""){
				//定制产品加  dingzhi_id  特殊字段
				$data['dingzhi_id']           = $dingzhi_id;
				$action	= M('cart')->data($data)->add();
				if(!$action){
					$returnData['status']	= 0;
					$returnData['msg']		= "定制失败，请重新提交";
				}
				$returnData['id']	= $action;	
			}else{
				if($cartGood){
					$save['goods_number'] = $cartGood['goods_number']+1;
					$saveAction   = M('cart')->where($where)->save($save);
				
					$returnData['id'] = $cartGood['id'];
				}else{
	
					$action	= M('cart')->data($data)->add();
					if(!$action){
						$returnData['status']	= 0;
						$returnData['msg']		= "定制失败，请重新提交";
					}
					$returnData['id']	= $action;
				}
			}
		}else{
			$returnData['status']		= 0;
			$returnData['msg']			= "定制失败，请重新提交";
		}
		$this->ajaxReturn($returnData);
	}


	public function addDingzhi2Cart(){
		$matchDiamonds = I("matchDiamonds",null);
		$cartId        = I("cartId",0);

		if($matchDiamonds != null){
			$matchDiamonds  = implode(",",$matchDiamonds);
			$diamonds       = M("goods_luozuan")->where("gid IN($matchDiamonds)")->select();
			$diamondsCartId = array();
			if($diamonds){
				foreach($diamonds as $key=>$val){
					$data                       = array();
					$data[$this->userAgentKey]  = $this->userAgentValue;
					if($val['luozuan_type'] == 1){
						D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
					}
					$val                = D("GoodsLuozuan") -> where("gid = $val[gid]") -> find();
					$val                = getGoodsListPrice($val,$_SESSION['web']['uid'],'luozuan', 'single');
					$data['goods_attr'] = serialize($val);   
					$data['goods_id']   = $val['gid'];
					$data['goods_type'] = 1;       
					$data[$this->userAgentKey] = $this->userAgentValue;  
					$data['goods_sn']   = $val['certificate_number'];
					$data['agent_id']   = C('agent_id'); 
					if(!M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_sn ='".$data['goods_sn']."'")->find()){
						$insertId = M('cart')->data($data)->add();  
						if($insertId){
							$diamondsCartId[] = $data['goods_sn'];
						}
					}else{
						$diamondsCartId[] = $data['goods_sn'];	
					}                  
				}
			} 
		}else{
			$diamondsCartId[] = '';	
		}                    
		$cartProduct = M('cart')->where("agent_id='".C('agent_id')."' and id=$cartId")->find();
		$cartProduct['goods_attr'] = unserialize($cartProduct['goods_attr']);
		$diamondsCartId = implode(",",$diamondsCartId);
		$cartProduct['goods_attr']['matchedDiamond'] = $diamondsCartId;
		$cartProduct['goods_attr'] = serialize($cartProduct['goods_attr']);
		M('cart')->where("agent_id='".C('agent_id')."' and id=$cartId")->setField("goods_attr",$cartProduct['goods_attr']); 
		$this->ajaxReturn(array("status"=>100));  
	}

}