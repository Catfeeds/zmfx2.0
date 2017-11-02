<?php
namespace Mobile\Model;

class OrderModel extends MobileModel{
	
    public function getOrderList($uid, $start, $per, $order_status){
	    if($uid < 1){
	   		$this->r(101, 'UID错误');
	    }
	    if($start < 0){
    		$start = 0;
    	}
    	if($per>20 or $per<1){  
            $per = 20;
        }
    	if($order_status < -1 or $order_status > 6){
    		$order_status = -2;
    	}

    	$map['uid'] 		= $uid;
    	$map['agent_id']	= C('agent_id');

    	if($order_status != '' and $order_status != -2){
    		$map['order_status'] = $order_status;
    	}

    	$order_data = M('order')->where($map)->field('order_id, order_sn, order_status,order_price, order_price_up, create_time')->limit($start, $per)->order('create_time desc')->select();///订单信息

    	foreach($order_data as $value){
    		$value['goods_num'] = M('order_goods')->where('order_id = '. $value['order_id'])->count();//订单中商品数量

            if($value['goods_num'] == 0){
                $this->r(102, '订单没有商品！');
            }

    		$value['goods_pic'] = '';

    		$order_goods = M('order_goods')->where('order_id = ' . $value['order_id'])->limit(0,3)->select();///订单商品

    		foreach($order_goods as $key=>$val){
    			$attribute = unserialize($val['attribute']);

                ////图片
                if($val['goods_type'] == 1){
                    $value['goods_pic'] = $value['goods_pic'].'zhengshuhuo_pic;';
                }elseif($val['goods_type'] == 2){
                    $value['goods_pic'] = $value['goods_pic'].'sanhuo_pic;';
                }else{
                    if($attribute['thumb']){
    			        $value['goods_pic'] = $value['goods_pic'].$attribute['thumb'].';';
                    }else{
                        $value['goods_pic'] = $value['goods_pic'].'chenpin_pic;';
                    }
                }

                //名字
                if($value['goods_num'] == 1){
                    if($val['good_type'] == 1 or $val['good_type'] == 2){
                        $value['goods_name']  = $value['certificate_no'];
                    }else{
                        $value['goods_name']  = $attribute['goods_name'];
                    } 
                }                                                
    		}

    		$data[] = $value;
    	}

    	return $data;

   }

    public function getOrderInfo($order_id, $uid, $agent_id){
        $O = M('order');
        // 查询订单关联的数据
        $field = 'o.*,u.realname,u.username,t.trader_name,';
        $field .='au.nickname as parent_admin_name,tu.trader_name as parent_trader_name';
        //获取订单对象
        $join['u'] = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
        $join['t'] = 'LEFT JOIN `zm_trader` AS t ON t.tid = o.tid';
        //获取订单所属
        $join['au'] = 'LEFT JOIN `zm_admin_user` AS au ON o.parent_id = au.user_id';
        $join['tu'] = 'LEFT JOIN `zm_trader` AS tu ON o.parent_id = tu.tid';
        //查询数据
        $map['o.order_id']    = $order_id;        
        $map['o.agent_id']    = $agent_id;
        $map['o.uid']         = $uid; 
        $data = $O->alias('o')->where($map)->field($field)->join($join)->find();        
        return $data;
    }

    //返回地址
    public function getAddressInfo($id){
        $UA = M('user_address');
        $field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
                'r2.region_name as province,r3.region_name as city,r4.region_name as district';
        $join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
        $join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
        $join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
        $join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
        $data = $UA->alias('ua')->where('ua.address_id = '.$id.' and ua.agent_id = '.C('agent_id'))->field($field)
            ->join($join1)->join($join2)->join($join3)->join($join4)->find();
        $data['addressInfo'] = $data['country'].$data['province'].$data['city'].$data['district'].$data['address'];
        return $data;
    }

    //得到订单的商品列表
    public function getOrderGoodsList($order_id, $uid, $agent_id){       
        //查询订单产品(分裸钻，成品，散货)
        $O = M('order');
        $field = 'og.*,gl.goods_number as goods_number_luozuan';//获取证书货字段（库存）
        $field .= ',gs.goods_weight as goods_number_sanhuo';//获取散货（库存）
        $field .=',g.goods_number as goods_number_goods';//获取珠宝产品库存
        //外连接查询
        $join[1] = 'left join zm_order_goods AS og ON og.order_id = o.order_id';//订单产品
        $join[2] = 'left JOIN zm_goods_luozuan AS gl ON gl.certificate_number = og.certificate_no';//裸钻产品
        $join[3] = 'left JOIN zm_goods_sanhuo AS gs ON gs.goods_sn = og.certificate_no AND gs.goods_id = og.goods_id';//散货产品
        $join[4] = 'left JOIN zm_goods AS g ON g.goods_id = og.goods_id';//成品产品
    
        //获取订单产品数据
        $goodsList = $O->alias('o')->field($field)->where('o.uid='.$uid.' and o.agent_id = '.$agent_id.' and o.order_id = '.$order_id)->join($join)->group('og_id')->select();
        
        $orderInfo = $O->where('agent_id = '.$agent_id)->find($order_id);

        //把数据遍历到页面数据
        foreach ($goodsList as $key => $value) {
            $value['attribute'] = unserialize($value['attribute']);
            $info = $value['attribute'];//订单产品信息
            if($value['goods_type'] == 1){//裸钻产品
                $value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
                $value['goods_number_up'] = intval($value['goods_number_up']);
                $value['goods_number'] = intval($value['goods_number']);
                $value['goods_sn_a'] = $info['certificate_type'].$value['certificate_no'];
                //所有库存不为0的裸钻都是1
                if($value['goods_number_luozuan']){
                    $value['goods_number_luozuan'] = 1;
                }
               
                //销售折扣（根据销售价格计算）
                if(round($value['goods_price_up'],2) > 0) $price = $value['goods_price_up'];
                else $price = $value['goods_price'];
                $dollar_huilv = $orderInfo['dollar_huilv'];
                $value['xian_advantage'] = formatPrice($price/$info['weight']/$dollar_huilv/$info['dia_global_price']*100);            
                $array['luozuan'][] = $value;
            }elseif ($value['goods_type'] == 2){//散货产品
                //计算每卡单价
                //if($info['goods_price2'] > 0){ $info['goods_price'] = $info['goods_price2'];}
                if($value['goods_number_up']>0){
                  $value['goods_price_sanhuo'] = formatPrice($value['goods_price']/$value['goods_number_up']);//散货单价
                }else{
                  $value['goods_price_sanhuo'] = formatPrice($value['goods_price']/$value['goods_number']);//散货单价
                }

            
                //没产品或没库存都是0库存
                if(!isset($value['goods_number_sanhuo'])){
                  $value['goods_number_sanhuo'] = '0';
                }
                //4c信息
                $value['4c'] = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
                //$value['is_site_goods'] = M('goods_sanhuo')->where('goods_id = ' . $value['goods_id'] . ' and agent_id = ' . C('agent_id'))->count();    //是自己站点的商品
                $array['sanhuo'][] = $value;
            }elseif ($value['goods_type'] == 3 or $value['goods_type'] == 4){//珠宝成品和珠宝定制
                //没确认前订单产品单价
                $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
                if($value['goods_type'] == 3){
                    if($info['sku_sn']){
                        $attributes = $info['goods_sku']['attributes'];//使用订单里面的SKU规格，防止修改产品
                        $sku = $this->getGoodsSku($info['category_id'],$info['goods_id'],$info['sku_sn'],$attributes);
                        $value['4c'] = $sku['sku'];
                        $value['goods_sku_number'] = $sku['goods_number'];
                    }else{
                        $value['4c'] = '你没有选择规格';
                    }
                }elseif ($value['goods_type'] == 4){
                    $value['4c'] = $this->getJGSdata($info);
                }
                $value['goods_sn'] = $info['goods_sn'];
                $value['goods_name'] = $info['goods_name'];
                $value['goods_number'] = round($value['goods_number']);
                //$value['is_site_goods'] = M('goods')->where('goods_id='.$value['goods_id'].' and agent_id ='.C('agent_id'))->count();    //是自己站点的商品

                $array['consignment'][] = $value;
            }
        }
        return $array;
    }

    


    /**
   * 获取产品的金工石数据
   * @param 订单产品数据
   */
    public function getJGSdata($info){

        $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
        $deputystoneM        = M('goods_associate_deputystone');
        $info['deputystone_name']  = "";
        $info['deputystone_price'] = "";
            
        if($info['deputystone']){
            $deputystone = $deputystoneM->where('gad_id = '.$info['deputystone']['gad_id'])->select();
            if($deputystone){
                $info['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
                $info['deputystone_price'] = $deputystone[0]['deputystone_price'];
            }
        }

        $material = $info['associateInfo'];
        $luozuan = $info['luozuanInfo'];
        $string = '材质:'.$material['material_name'].',金重:'.$material['weights_name'];
        $string .=',损耗:'.$material['loss_name'].',基本工费：'.$material['basic_cost'];
        $string .=',材质金价:'.$material['gold_price'].'<br />';
        
        $shape_name = M('goods_luozuan_shape')->where('shape_id = '.$luozuan['shape_id'])->field('shape_name')->find();

        $string .= '//主石:'.$luozuan_shape_array[$info['luozuanInfo']['shape_id']] .',分数:'.$luozuan['weights_name'].'CT,'.$info['deputystone_name'].',镶嵌工费:'.$luozuan['price'].'元';
        if($info['hand']){ $string .= '//手寸:'.$info['hand'].';';}
        if($info['word']){ $string .= '//刻字:'.$info['word'].';';}
        if($info['matchedDiamond']){$string .= '//匹配主石:'.$info['matchedDiamond'].';';};
        
        return $string;
    }


    /**
   * 获取产品的SKU
   * @param int $sku_id
   */
    public function getGoodsSku($category_id,$goods_id,$sku_sn,$attributes){
      $GS   = M('goods_sku');
      $info = $GS->where('goods_id = '.$goods_id." and sku_sn = '$sku_sn'")->find();
      $GA   = M('goods_attributes');//公共
      $GCA  = M('goods_category_attributes');
      $GAV  = M('goods_attributes_value');
      $gacList = $GCA->where('category_id = '.$category_id.' and type = 2 ')->select();
      $ids = $this->parIn($gacList, 'attr_id');
      $list = $GA->where('attr_id in('.$ids.')')->select();
      $list = $this->_arrayIdToKey($list);
      $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
      foreach ($attrValueList as $key => $value) {
          $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
      }
      $attr = explode('^', $attributes);
      $arr['sku'] = 'SKU编号:'.$sku_sn.';';
      $arr['goods_number'] = $info['goods_number'];
      foreach ($attr as $key => $value) {
          $active = explode(':', $value);
          $arr['sku'] .= $list[$active[0]]['attr_name'].':';
          $sub = $list[$active[0]]['sub'];
          if($list[$active[0]]['input_type'] == 2){
              $arr['sku'] .= $sub[$active[1]]['attr_value_name'].';';
          }elseif ($list[$active[0]]['input_type'] == 3){
              $arr['sku'] .= $active[1].';';
          }
      }
      return $arr;
  }
}