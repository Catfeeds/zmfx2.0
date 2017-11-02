<?php
/**
 * 裸钻获取类，根据级别自动加点，
 * User: 王松林
 * Date: 2016/6/23 0023
 * Time: 17:26
 */
namespace Common\Model;
use Think\Model;
use Think\Page;
Class CartModel extends \Think\Model{

   
    //返回当前客户的点数，is_parent>0的时候，只返回上级点数
 
    public function getUpdateCartGoodsList($where = array(), $uid){
    	$cart = M('cart');

    	/////////////////////的到where语句
    	if(is_array($where)){
    		foreach($where as $key => $value){
    			$where_new['c.'.$key] = $value; 
    		}
    		$sql = $this->where($where_new)->fetchSql(true)->find();
    		//SELECT * FROM `zm_cart` WHERE `agent_id` = 2 AND `uid` = 203923 AND `goods_type` <> 2 LIMIT 1
    		$str_array = explode('WHERE',str_replace(array("LIMIT"),'WHERE',$sql)); //得到where 和 limit 中间的那一块
    		$str_where = $str_array[1];
    	}else{
    		$str_where = $where;
    	}
  
  		//////////////查找购物车中商品
    	$res = $this->alias('c')->field('c.*, gl.gid as have_goods')
                    ->join('left join zm_goods_luozuan  as gl on (gl.gid = c.goods_id and gl.goods_number=1)')
                    ->where('c.goods_type = 1 and '.$str_where)                  
                    ->union('select c.*, gs.goods_id as have_goods from zm_cart as c LEFT JOIN zm_goods_sanhuo  as gs on (gs.goods_id = c.goods_id and  gs.goods_weight > 0)  where c.goods_type = 2 and'.$str_where)
                    ->union('select c.*, g.goods_id as have_goods from zm_cart as c LEFT JOIN zm_goods_shop  as g on (g.shop_agent_id = '.C('agent_id').' and g.goods_id = c.goods_id and g.sell_status=1) where c.goods_type > 2 and '.$str_where)                
                    ->select();
    	////商品进行检查
    	foreach($res as $key=>$value ){
    		if(empty($value['have_goods'])){
    			continue;
    		}

    		if($value['goods_type'] == 1){  //更新裸钻信息
    			$goods    = M('goods_luozuan')->where("`gid`='".$value['goods_id']."' AND `goods_number` = 1 ") -> find();
	            $goodsObj = D("Common/GoodsLuozuan");//初始化默认的读取的是白钻的加点
	            if($goods['luozuan_type'] == 1){
	                //设置彩钻参数
	                $goodsObj -> setLuoZuanPoint('0','1');
	            }else{
					$goodsObj -> setLuoZuanPoint('0','0');
				}
	            $goods = $goodsObj -> where("`gid`='".$value['goods_id']."' AND `goods_number` = 1 ") -> find();
	            if($goods){
	                $goods      = getGoodsListPrice($goods,$uid,'luozuan', 'single');
	                $goods_attr = serialize($goods);

	                if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
	                    $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField(array('goods_attr'=>$goods_attr,'goods_sn'=>$goods['certificate_number']));
	                   
	                    $res[$key]['goods_attr'] = $goods_attr;
	                    $res[$key]['goods_sn']   = $goods['certificate_number'];
	                }
	            }
    		}elseif($value['goods_type'] == 2){//更新散货
    			$GSobj = D('GoodsSanhuo');
	            $where = array('goods_id'=>array('eq',$value['goods_id']));
	            $goods = $GSobj->getInfo($where);
	            if($goods){
	                $goods = getGoodsListPrice($goods,$uid,'sanhuo', 'single');
	                $goods_attr = serialize($goods);
	                if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
	                    $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField(array('goods_attr'=>$goods_attr,'goods_sn'=>$goods['goods_sn']));
	                   
	                    $res[$key]['goods_attr'] = $goods_attr;
	                    $res[$key]['goods_sn']   = $goods['goods_sn'];
	                }
	            }
    		}elseif($value['goods_type'] == 3){//更新成品
    			$G            = D("Common/Goods");
    			$sku_sn 	  = $value['sku_sn'];
    			$goods_type   = $value['goods_type'];
				$goods        = $G -> get_info($value['goods_id'],0,'shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
				if($goods){
					$temp = $G -> getGoodsSku($value['goods_id'] ," sku_sn = '$sku_sn' ");
					if(empty($temp)){
						continue;  //sku_sn发生变化则跳过
					}
					if($temp['goods_number'] < 1){ //某一sku无货
						$res[$key]['have_goods'] = 0;//无货
						continue;
					}					
					$temp['goods_type']             = $goods_type;
					$temp                           = getGoodsListPrice($temp, $uid, 'consignment', 'single');

					$goods['goods_price']           = formatRound($temp['goods_price'],2);
					$goods['activity_price']        = formatRound($temp['activity_price'],2);		//活动商品价格
					$goods['marketPrice']           = formatRound($temp['marketPrice'],2);
					$goods['goods_sku']             = $temp;
					$goods['consignment_advantage'] = $temp["consignment_advantage"];
					$goods['activity_status']	    = $temp["activity_status"];						//活动商品标识

					unset($goodsData);
					$goodsData['goods_attr']        = serialize($goods);
					$goodsData['goods_sn']          = $goods['goods_sn'];

				 	if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
	                    $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField($goodsData);                 
	                    $res[$key]['goods_attr'] = $goodsData['goods_attr'];
	                    $res[$key]['goods_sn']   = $goodsData['goods_sn'];
	                }
				} 			
    		}elseif($value['goods_type'] == 4){//更新订制
    			$attr = unserialize($value['goods_attr']);
				$hand          = $attr['hand']==""?12:$attr['hand'];
				$word          = $attr['word']; 
				$hand1         = $attr['hand1'];
				$word1         = $attr['word1']; 
				$gid           = $value['goods_id'];
				$goods_type    = 4;
				$diamondId     = intval($attr['luozuanInfo']['gal_id']);

				$materialId    = intval($attr['associateInfo']['material_id']);
				$deputystoneId = intval($attr['deputystone']['gad_id']);
				
				$G               = D("Common/Goods");
				$goodsInfo       = $G -> get_info($gid, 0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
				$luozuanInfo     = $G -> getGoodsAssociateLuozuanAfterAddPoint("goods_id=$gid AND gal_id=$diamondId");
				$associateInfo   = $G -> getGoodsAssociateInfoAfterAddPoint("goods_id=$gid AND zm_goods_associate_info.material_id=$materialId ");
				
				if($deputystoneId and $deputystoneId!='undefined') {
					$deputystone = $G -> getGoodsAssociateDeputystoneAfterAddPoint("gad_id=$deputystoneId");
				}

				if($luozuan >0){
					$certificate_number           = D("GoodsLuozuan") -> where(" gid = $luozuan ") -> getField("certificate_number");
					$goodsInfo['matchedDiamond']  = $certificate_number;
				}  
								
				if($luozuanInfo && $associateInfo && $goodsInfo){
					$material = $G -> getGoodsMaterialAfterAddPoint(" material_id = $materialId ");
					if( !$goodsInfo['price_model'] ){
						$goodsInfo['goods_price'] = $G -> getJingGongShiPrice($material,$associateInfo,$luozuanInfo,$deputystone);
					}else{
						$category_name = M('goods_category') -> where(" category_id = '".$goodsInfo['category_id']."'") -> getField('category_name');
						$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
						if( $is_size ){
							$size                     = $G->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
							$goodsInfo['goods_price'] = $size['size_price'];
							$is_dui                   = InStringByLikeSearch($category_name,array('对戒'));
							if($is_dui){
								$size                 = $G -> getGoodsAssociateSizeAfterAddPoint(" sex = '1' and min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
								$size1                = $G -> getGoodsAssociateSizeAfterAddPoint(" sex = '2' and min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
								$goodsInfo['goods_price']  = $size['size_price'] + $size1['size_price'];
							}
						} else {
							$ass = $G->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
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
					$goodsInfo['sd_id']     = $attr['sd_id']?$attr['sd_id']:'0';					
					$goodsInfo['sd_id1']    = $attr['sd_id1']?$attr['sd_id']:'';					
					$goodsInfo['sd_images']     = $attr['sd_images'];					
					$goodsInfo['sd_images1']    = $attr['sd_images1'];
					$goodsInfo                  = getGoodsListPrice($goodsInfo, $uid, 'consignment', 'single');					
					unset($goodsData);
					$goodsData['goods_attr']        = serialize($goodsInfo);

				 	if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
						$cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField($goodsData);                 
	                    $res[$key]['goods_attr'] = $goodsData['goods_attr'];
	                }
					
				}    			
    		}elseif($value['goods_type'] == 16){//更新szzmzb 板房 购物车价格
   				$goods_attr = (new \Common\Model\Dingzhi\Szzmzb\Goods())->dinzhi_szzmzb_cart_price($value['goods_attr'],$value['goods_id']);
    			if($goods_attr){
    				$cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField(['goods_attr'=>$goods_attr]);
    				$res[$key]['goods_attr'] = $goods_attr;
    			}else{ //获取官网价格失败 待处理
    				
    			}
    		}
    	}
    	return $res;  
    }

    //去掉购物车中无货的
    public function delNullGoods ($goods_list = array()){
    	foreach($goods_list as $key => $value){
    		if(empty($value['have_goods'])){
    			unset($goods_list[$key]);
    		}
    	}
    	return $goods_list;

    }
	public function addToCart($param){
		$tempgoods  = D('Common/Goods')->productGoodsInfo($param);

		$return = array(
				'status' => 0,
				'msg'    => $tempgoods['msg']
		);
		if($tempgoods['status']==100){
			$goods							= $tempgoods['info'];
			if($goods['goods_id_couple']){
				$data['goods_id'] = $goods['goods_id_couple'];
			}else{
				$data['goods_id'] 				= $param['gid'];
			}

			$data['goods_type'] 			= $param['goods_type']>0 ? $param['goods_type'] : 1;
			$data['goods_sn'] 				= $goods['goods_sn'] ? $goods['goods_sn'] : '';
			$goods_type_select_number 		= isset($goods['goods_type_select_number']) ? $goods['goods_type_select_number'] : 1;
			$data['goods_number'] 			= $goods_type_select_number;
			$data['goods_attr']             = serialize($goods);
			$data['agent_id']               = isset($param['agent_id']) ? $param['agent_id'] : C('agent_id');
			$data['uid'] 		            = $param['uid'] ? intval($param['uid']) : intval($_SESSION['web']['uid']);
			$data['session'] 		        = $param['session'] ? $param['session'] : '';
			$data['sku_sn'] 		        = $param['sku_sn'] ? $param['sku_sn'] : '';
			$data['serial_number'] 		= $param['serial_number'] ? intval($param['serial_number']) : 0;

			$where  = "1=1 AND agent_id='".$data['agent_id']."' AND goods_id=".$data['goods_id']." AND goods_type=".$data['goods_type'];
			if($data['uid']){
				$where .= " AND uid ='".$data['uid']."'";
				$data['session'] = '';
			}else{
				$where .= " AND session ='".$data['session']."'";
			}
			$cartGood = $this->where($where)->find();

			if($cartGood){
				$id = $cartGood['id'];
				$data['goods_number'] = $cartGood['goods_number']+$goods_type_select_number;
				$bool   = M('cart')->where(array('id'=>$id))->save($data);
			}else{
				$id = $this->add($data);
			}
			$banfang_cart_id = 0;
			if($id>0){
				$factory_id = $goods['factory_lists'][0]['factory_info']['f_id'];
				if($factory_id>0){
					$BCM = M('banfang_cart');

					$f_data = array(
						'cart_id'=>$id,
						'factory_id'=>$factory_id,
						'goods_id'=>$param['gid'],
						'goods_number'=>$goods_type_select_number,
						'goods_attr'=>$data['goods_attr'],
						'uid'=>$data['uid'],
						'session'=>$data['session'],
						'agent_id'=>$data['agent_id'],
						'serial_number'=>$data['serial_number']
					);

					//把$serial_number进行排序得到的就是它的序号，直接存的这个值，加入被删除了还需要重新计算序号，因此需要在查询的时候遍历一次重新给序号
					$banfang_cart_id   = $BCM->add($f_data);

				}
				$return = array(
						'id'=>$id,
						'banfang_cart_id'=>$banfang_cart_id,
						'status'=>100,
						'msg'=>'加入购物车成功'
				);
			}
		}

		return $return;

	}
	//删除单个周六福购物车数据
	public function deleteMyOneBanfangCart($param){
		$BC = M('banfang_cart');
		$uid = intval($param['uid']);
		$session = $param['session'];
		$id = $param['id'];
		$agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
		$return = array(
				'status'=>0,
				'msg'=>'删除失败'
		);
		if(!$id || (!$uid && !$session)){
			return $return;
		}
		if(!is_array($param['id'])){
			$id = array($id);
		}
		$where = array(
				'id'=>array('in',$id),
				'agent_id'=>$agent_id
		);
		if($uid){
			$where['uid'] = $uid;
		}else{
			$where['session'] = $session;
		}
		$info = M('banfang_cart')->where($where)->find();
		$where_b_cart = $where;
		unset($where_b_cart['id']);
		$where_b_cart['cart_id'] = $info['cart_id'];
		$count = M('banfang_cart')->where($where_b_cart)->count();
		if($count==1){
			$where_cart = $where_b_cart;
			unset($where_cart['cart_id']);
			$where_cart['id'] = $where_b_cart['cart_id'];
			$bool1 = $this->where($where_cart)->delete();
		}
		$bool2 = M('banfang_cart')->where($where)->delete();
		$count = M('banfang_cart')->where($where_b_cart)->count();
		if($count>0){
			M('cart')->where(['id'=>$info['cart_id']])->save(['goods_number'=>$count]);
		}
		$return = array(
				'status'=>100,
				'msg'=>'删除成功'
		);
		return $return;

	}

	//删除购物车以及周六福的购物车数据
	public function deleteMyBanfangCart($param){
		$uid = intval($param['uid']);
		$session = $param['session'];
		$cart_id = $param['id'];
		$agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
		$return = array(
			'status'=>0,
			'msg'=>'删除失败'
		);
		if(!$cart_id || (!$uid && !$session)){
			return $return;
		}
		if(!is_array($param['id'])){
			$cart_id = array($cart_id);
		}
		$where = array(
			'id'=>array('in',$cart_id),
			'agent_id'=>$agent_id
		);
		if($uid){
			$where['uid'] = $uid;
		}else{
			$where['session'] = $session;
		}	

		$bool1 = $this->where($where)->delete();
		$bool2 = M('banfang_cart')->where($where)->delete();
		$cart_count = M('cart')->where("agent_id='".$agent_id."' and uid=".$uid)->count();
		$return = array(
			'status'=>100,
			'cart_count'=>$cart_count,
			'msg'=>'删除成功'
		);
		return $return;

	}

	//清除购物车 以及以及周六福的清除购物车数据
	public function deleteMyBanfangCartAll($param){
		$uid = intval($param['uid']);
		$session = $param['session'];

		$agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
		$return = array(
				'status'=>0,
				'msg'=>'删除失败'
		);
		if((!$uid && !$session)){
			return $return;
		}
		$where = array(
				'agent_id'=>$agent_id
		);
		if($uid){
			$where['uid'] = $uid;
		}else{
			$where['session'] = $session;
		}

		$bool1 = $this->where($where)->delete();
		$bool2 = M('banfang_cart')->where($where)->delete();
		$return = array(
				'status'=>100,
				'msg'=>'删除成功'
		);
		return $return;

	}

	//
	public function deleteBanfangCart(){

	}

	public function updateMyBanfangCart($param){
		$BCM = M('banfang_cart');
		$uid = intval($param['uid']);
		$session = $param['session'];
		$id = $param['id'];
		$agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
		$return_data = array(
				'status'=>0,
				'msg'=>'删除失败',
				'cart_money'=>0,
		);
		if(!$id || (!$uid && !$session)){
			return $return_data;
		}

		$where = array(
				'id'=>$id,
				'agent_id'=>$agent_id
		);
		if($uid){
			$where['uid'] = $uid;
		}else{
			$where['session'] = $session;
		}



		$return_data = $this->getBanfangCartInfo($param);
		if($return_data['status']){
			$cart_info = $return_data['data'];
			if(!empty($cart_info['sub_info']['select_param']['error'])){
				$return_data['status'] = 0;
				$return_data['msg'] = $cart_info['sub_info']['select_param']['error'][0];
				return $return_data;
			}
			$banfang_cart_number = $BCM->where(array('id'=>$cart_info['id']))->getField('goods_number');

			$cart_number = $this->where(array('id'=>$cart_info['cart_id']))->getField('goods_number');
			$f_data = array(
					'cart_id'=>$cart_info['cart_id'],
					'factory_id'=>$cart_info['sub_info']['factory_lists'][0]['factory_info']['f_id'],
					'goods_number'=>$cart_info['goods_number'],
					'goods_attr'=>serialize($cart_info['sub_info']),
			);
			$c_data = array();
			$c_data['goods_number'] = $cart_number + $f_data['goods_number']-$banfang_cart_number;
			$bool1   = $BCM->where(array('id'=>$id))->save($f_data);
			$bool2   = $this->where(array('id'=>$cart_info['cart_id']))->save($c_data);

		}

		return $return_data;
	}

	//获取周六福购物车数据列表
	public function getBanfanCart($param){
		$no_page = $param['no_page'] ? $param['no_page'] : false;
		$no_page = 1;
		$cart_id_arr = $param['cart_id_arr'] ? $param['cart_id_arr'] : false;
		$n = $param['n'] ? $param['n'] : 13;

		$CM = M('cart');
		$BCM = M('banfang_cart');
		$where = array();
		$where['goods_type'] = 7;
		$where['agent_id'] = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
		$where['uid'] = $param['uid'] ? intval($param['uid']) : intval($_SESSION['web']['uid']);
		if($where['uid']<=0){
			$where['session'] = $param['session'] ? trim($param['session']) : session_id();
		}
		if(!empty($cart_id_arr) && is_array($cart_id_arr)){
			$where['id'] = array('in',$cart_id_arr);
		}
		$page = '';
		if(!$no_page){
			$count = $CM->where($where)->count();
			$Page = new Page($count,$n);
			$data = $CM->where($where)->limit($Page->firstRow,$Page->listRows)->order('id desc')->select();
			$page = $Page->show();


		}else{
			$data = $CM->where($where)->order('id desc')->select();
			$count = count($data);
		}

		$GMM = D('Common/Goods');//$GMM->productGoodsInfo()
		$lists = array();
		//购物车总金额
		$cart_money_all = 0;
		$count_number = 0;

		foreach($data as $key=>$value){
			$goods_param = unserialize($value['goods_attr'])['goods_attr_param'];
			//购物车商品
			$value['main_info'] = $GMM->productGoodsInfo($goods_param)['info'];

			$b_where = array(
					'cart_id'=>$value['id']
			);
			$banfang_carts = $BCM->where($b_where)->select();


			$carts_list = array();
			//单个购物金额
			$cart_money = 0;
			foreach($banfang_carts as $val){
				$goods_param = unserialize($val['goods_attr'])['goods_attr_param'];
				$val['goods_param'] = $goods_param;
				$val['sub_info'] = $GMM->productGoodsInfo($goods_param)['info'];
				$carts_list['lists'][] = $val;
				$carts_list['count']++;

				$cart_money += $val['sub_info']['goods_price']*$val['goods_number'];

				$count_number += $val['goods_number'];
			}
			$cart_money_all += $cart_money;
			//周六福购物车商品
			$value['banfang_carts_info'] = $carts_list;
			$value['cart_money'] = $cart_money;
			$lists[] = $value;
		}

		$return = array(
				'lists'=>$lists,
				'count'=>$count,
				'count_number'=>$count_number,
				'cart_money_all'=>$cart_money_all,
				'page'=>$page
		);

		return $return;
	}

	//获取周六福购物车数据详情
	public function getBanfangCartInfo($param){
		$return = array(
			'status'=>0,
			'msg'=>'商品已下架',
			'data'=>array()
		);
		$BCM = M('banfang_cart');
		$GMM = D('Common/Goods');
		$agent_id = $param['agent_id'] ? intval($param['agent_id']) : C('agent_id');
		$id = $param['id'] ? intval($param['id']) : 0;
		$uid = $param['uid'] ? intval($param['uid']) : 0;
		$session = $param['session'] ? trim($param['session']) : '';

		if(!$id || (!$uid && !$session)){
			return $return;
		}
		$where = array(
			'id'=>$id,
			'agent_id'=>$agent_id
		);
		if($uid){
			$where['uid'] = $uid;
		}elseif($session){
			$where['session'] = $session;
		}

		$info = $BCM->where($where)->find();

		if(!empty($info)){
			$goods_param = unserialize($info['goods_attr'])['goods_attr_param'];
			if(!empty($param['obj_data']) && $param['obj_data']['is_change']){
				if($param['obj_data']['ma_id']){
					$goods_param['ma_id'] = $param['obj_data']['ma_id'];
				}
				if($param['obj_data']['diamond']){
					$goods_param['diamond'] = $param['obj_data']['diamond'];
				}
				if($param['obj_data']['hand']){
					$goods_param['hand'] = $param['obj_data']['hand'];
				}
				if($param['obj_data']['color']){
					$goods_param['color'] = $param['obj_data']['color'];
				}
				if($param['obj_data']['clarity']){
					$goods_param['clarity'] = $param['obj_data']['clarity'];
				}
				if($param['obj_data']['note']){
					$goods_param['note'] = $param['obj_data']['note'];
				}
				$param['obj_data']['goods_number'] = $param['obj_data']['goods_number']>1 ? intval($param['obj_data']['goods_number']) : 1;

				$goods_param['goods_type_select_number'] = $param['obj_data']['goods_number'];

				$info['goods_number'] = $param['obj_data']['goods_number'];
			}

			$info['goods_param'] = $goods_param;
			$info['sub_info'] = $GMM->productGoodsInfo($goods_param)['info'];

			$info['cart_money'] = $info['goods_number']*$info['sub_info']['goods_price'];
			$return = array(
					'status'=>100,
					'msg'=>'获取信息成功',
					'data'=>$info
			);
		}

		return $return;
	}

	//复制一个周六福购物车数据
	public function fuzhiBanfangCartInfo($param){
		$return = array(
				'status'=>0,
				'msg'=>'商品已下架',
				'data'=>array()
		);
		$return_data = $this->getBanfangCartInfo($param);

		if($return_data['status']==100){
			$info = $return_data['data'];
			$goods_param = $info['goods_param'];
			$goods_param['note'] = '';
			$goods_param['goods_type_select_number'] = 1;
			$return_cart = $this->addToCart($goods_param);
			if($return_cart['status']){
				$param['id'] = $return_cart['banfang_cart_id'];
				$return = $this->getBanfangCartInfo($param);
			}
		}
		return $return;

	}


}