<?php
/**
 * 供应包和钻明之间的订单通讯
 * User: 王松林
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Common\Model;
Class JoinCaigouOrderModel extends \Think\Model{

    Protected $autoCheckFields = false;

    //二级加入到一级的采购单中
    public function joinYijiAgentCaigou($order_id, $order_goods_id){

        $O      = M('order');
        $OG     = M('order_goods');

        $O->startTrans();
        //得到订单信息
        $order_info = $O->where(" order_id = '$order_id' and agent_id = '".C('agent_id')."'") -> select();
        if(count($order_info)<1){ //没有订单，或者该订单不是供应商的
            $O->rollback();
            return '没有该订单！';
        }
		
 
		if(cookie('beizhu') && cookie('beizhu')!='___'){
			if(strpos(cookie('beizhu') , '___' )){
				$beizhu    = explode('___',cookie('beizhu'));
			}else{
				$beizhu[0] = cookie('beizhu');
			}
			cookie('beizhu',null);			
		}
 
		


        //得到一级的agent_id , 汇率 ,在一级中的会员uid  
        $parent_id = get_parent_id();
        if($parent_id < 1){ //不是一级
            $O->rollback();
            return '你不能向自己添加采购单！';
        }
        $dollar_huilv_type = getAgentConfigValue('dollar_huilv_type', $parent_id);
        if(!$dollar_huilv_type ){
            $dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');         
        }else{
            $dollar_huilv  = getAgentConfigValue('dollar_huilv', $parent_id);
        }

        $uid           	  = get_create_user_id();  
        $yewuyuan_id      = M('user')->where('agent_id =' .$parent_id. ' and uid = '.$uid)->getField('parent_id');


        //得到商品信息       
        $field  = 'og.*,gl.goods_number as goods_number_luozuan';  //获取证书货字段（库存）
        $field .= ',gs.goods_weight as goods_number_sanhuo';       //获取散货（库存）
        //外连接查询
        $join[1] = 'left join zm_order_goods AS og ON og.order_id = o.order_id';//订单产品
        $join[2] = 'left JOIN zm_goods_luozuan AS gl ON gl.certificate_number = og.certificate_no and gl.agent_id in ( 0,' .$parent_id .')';//裸钻产品
        $join[3] = 'left JOIN zm_goods_sanhuo AS gs ON gs.goods_sn = og.certificate_no AND gs.goods_id = og.goods_id AND gs.agent_id in ( 0,' .$parent_id .')';//散货产品
        //获取订单产品数据
        $goodsList = $O->alias('o')->field($field)->where('o.agent_id = '.C('agent_id').' and og.og_id in ('.$order_goods_id.') and o.order_id = '.$order_id)->join($join)->group('og_id')->select();

        if(count($goodsList) < 1){
            $O->rollback();
            return '没有选择要添加的商品';
        }

        
        $order_goods_data = array();
        $order_data       = array();
        $order_data['order_price'] = 0;
        $i = 0;
        foreach ($goodsList as $key => $value) {
            $row = array();

            if($value['goods_type'] == 1){//裸钻产品 

                $GL   = D('GoodsLuozuan');

                //证书货不在时，跳过加入                
                $info_front = $GL->where("certificate_number = '".$value['certificate_no']."' and agent_id in(0,".$parent_id.")") -> find();

               
                // if(empty($info_front)){ //数据库中没有了货品，但还可能有货
                //     $info_front = unserialize($value['attribute']);
                // }
                // 
                if(empty($info_front)){ //数据库中没有了货品，但还可能有货
                   continue;
                }  
                $info = getGoodsListPrice($info_front, 0, 'luozuan', 'single', C('agent_id'));
                $point_yiji = 0; //白钻钻明给一级的加点
                $point_erji = 0; //白钻一级给二级的加点
                //一级二级的加点和
                $point_erji_and_yiji	  = D("Common/GoodsLuozuan") -> setLuoZuanPoint('0','0'); //白钻的加点
                $point_yiji 			  = D("Common/GoodsLuozuan") -> setLuoZuanPoint(C('agent_id'),'0');
                $point_erji 			  = $point_erji_and_yiji - $point_yiji;
               
                $info['dia_discount_all'] = $info_front['dia_discount'];
                $info['dia_discount']     = $info['dia_discount'] - $point_erji; 

                if($info['luozuan_type'] == 1){   
                    //$info_front 得到的是白钻加点后的信息，不是彩钻的。这里需要先将白钻的加点捡到，然后加上彩钻的加点                    
                    $point_erji_and_yiji_caizuan= D("Common/GoodsLuozuan") -> setLuoZuanPoint('0','1'); //彩钻的加点
                    $point_yiji_caizuan = D("Common/GoodsLuozuan") -> setLuoZuanPoint(C('agent_id'),'1');
                    $point_erji_caizuan = $point_erji_and_yiji_caizuan - $point_yiji_caizuan;

                    if($info['agent_id'] == 0){
                        $info['dia_discount_all'] = $info_front['dia_discount'] - $point_erji_and_yiji + $point_erji_and_yiji_caizuan;
                        $info['dia_discount']     = $info_front['dia_discount'] - $point_erji_and_yiji + $point_yiji_caizuan; 
                    }else{
                        $info['dia_discount_all'] = $info_front['dia_discount'] - $point_erji + $point_erji_caizuan;
                        $info['dia_discount']     = $info_front['dia_discount'] - $point_erji; 
                    }                   
                }
                
            
                $info["cur_price"] = $info["meikadanjia"] = $dollar_huilv * $info['dia_global_price'] * $info['dia_discount_all']/100;
                $info["xiaoshou_price"] = $info["price"] = round($info["cur_price"] * $info['weight'], 0);
                $info['caigou_price'] = formatRound($info['dia_global_price'] * $dollar_huilv * $info['weight'] * $info['dia_discount']/100 , 2);//采购单价
                $info['price_display_type'] = 0;                
                $info['attribute'] = serialize($info);
                $info['goods_type'] = '1';
               
                $order_data['order_price'] = $order_data['order_price'] + $info["price"];

                $row['order_id']        = 0;
                $row['goods_id']        = $info['gid'];
                $row['certificate_no']  = $info['certificate_number'];
                $row['attribute']       = $info['attribute'];
                $row['goods_price']     = $info['price'];
                $row['goods_price_up']  = 0;
                $row['goods_type']      = $info['goods_type'];
                $row['goods_number']    = 1;
                $row['goods_number_up'] = 0;
                $row['parent_id']       = $yewuyuan_id;
                $row['parent_type']     = 2;
                $row['agent_id']        = $parent_id;
                $row['update_time']     = time();
                $row['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
                $row['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];

            }elseif ($value['goods_type'] == 2){//散货产品   
                     
                $GS = D('GoodsSanhuo');
                $info_front = $GS->where("goods_id = ".$value['goods_id']." and goods_sn = '".$value['certificate_no']."' and agent_id in(0,".$parent_id.")") -> find();

                if(empty($info_front)){ //数据库中没有了货品，但还可能有货
                    $info_front = unserialize($value['attribute']);
                }

                $info = getGoodsListPrice($info_front, 0, 'sanhuo', 'single', $parent_id);
                if($info_front['agent_id'] > 0){   //一级的散货
                    $point_yiji = 100;
                }else{
                    $point_yiji = 100 + M('trader')->where('t_agent_id = '. $parent_id)->getField('sanhuo_advantage');
                }

                $point_erji = 100 + M('trader')->where('t_agent_id = '. C('agent_id'))->getField('sanhuo_advantage');
               
                
                $info['caigou_price']   = round($info_front['goods_price']*$point_yiji*$point_erji*$value['goods_number']/10000, 0);
                $info['goods_price']    = $info['xiaoshou_price'] = round($info_front['goods_price'] * $value['goods_number'] * $point_yiji * $point_erji/10000, 0);

                
                $info['goods_price2']   = 0; //特价

                $info['attribute'] = serialize($info);
                $info['goods_type'] = '2';
                
                $order_data['order_price'] = $order_data['order_price'] + $info["goods_price"];

                $row['order_id']        = 0;
                $row['goods_id']        = $info['goods_id'];
                $row['certificate_no']  = $info['goods_sn'];
                $row['attribute']       = $info['attribute'];
                $row['goods_price']     = $info['goods_price'];
                $row['goods_price_up']  = 0;
                $row['goods_type']      = $info['goods_type'];
                $row['goods_number']    = $value['goods_number'];
                $row['goods_number_up'] = 0;
                $row['parent_id']       = $yewuyuan_id;
                $row['parent_type']     = 2;
                $row['agent_id']        = $parent_id;
                $row['update_time']     = time();
                $row['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
                $row['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];

              
            }elseif($value['goods_type']=='4'){
				//特指板房数据
				$info					= unserialize($value['attribute']);	
				$infoas['goods_sn'] 	= $info['goods_sn'];
				$info 					= D('OrderCart')->getUpdateDingzhiGoodsInfo($info, $value['goods_id']);
				if(!$info){
					$result = array('success'=>false, 'msg'=>'编号为'.$infoas['goods_sn'].'的商品在已经下架或者删除！','error'=>true);
					header('Content-Type:application/json; charset=utf-8');exit(json_encode($result));
				} 
 
                $row['order_id']        = 0;
                $row['goods_id']        = $info['goods_id'];
                $row['certificate_no']  = $info['goods_sn'];
                $row['attribute']       = serialize($info);
                $row['goods_price']     = $info['caigou_price'] * $value['goods_number'];
                $row['goods_price_up']  = 0;
                $row['goods_type']      = $info['goods_type'];
                $row['goods_number']    = $value['goods_number'];
                $row['goods_number_up'] = 0;
                $row['parent_id']       = $yewuyuan_id;
                $row['parent_type']     = 2;
                $row['agent_id']        = $parent_id;
                $row['update_time']     = time();
                $row['fahuo_time']      = empty($info['fahuo_time'])?0:$info['fahuo_time'];
                $row['have_goods']      = empty($info['have_goods'])?0:$info['have_goods'];
                $order_data['order_price'] = $order_data['order_price'] + $row["goods_price"];
			}elseif($value['goods_type']=='16'){ //szzmzb 板房
                $product = (new \Common\Model\Dingzhi\Szzmzb\Goods())->admin_dinzhi_szzmzb_cart_price($value['attribute'],$value['goods_id']);
                $info = $product['dinzhi_goodsInfo']; 
                if(!$product){
                    $result = array('success'=>false, 'msg'=>'编号为'.$product['goods_sn'].'的商品在已经下架或者删除！','error'=>true);
                    header('Content-Type:application/json; charset=utf-8');exit(json_encode($result));
                } 
 
                $row['order_id']        = 0;
                $row['goods_id']        = $product['goods_id'];
                $row['certificate_no']  = $product['goods_sn'];
                $row['attribute']       = serialize($info);
                $row['goods_price']     = $info['goods_price'] * $value['goods_number'];
                $row['goods_price_up']  = 0;
                $row['goods_type']      = 16;
                $row['goods_number']    = $value['goods_number'];
                $row['goods_number_up'] = 0;
                $row['parent_id']       = $yewuyuan_id;
                $row['parent_type']     = 2;
                $row['agent_id']        = $parent_id;
                $row['update_time']     = time();
                $row['fahuo_time']      = empty($info['fahuo_time'])?0:$info['fahuo_time'];
                $row['have_goods']      = empty($info['have_goods'])?0:$info['have_goods'];
                $order_data['order_price'] = $order_data['order_price'] + $row["goods_price"];
            }
 
			$row['beizhu'] 			    = empty($beizhu[$key])?0:$beizhu[$key];
 
            $order_goods_data[] = $row; 
            $i++;
        }
 
		
        if($i == 0){
            $O->rollback();
            return '商品不存在，没有加入采购单';
        }
        //得到地址
        $UA = M('user_address');
        $field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
                'r2.region_name as province,r3.region_name as city,r4.region_name as district';
        $join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
        $join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
        $join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
        $join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';

        $address_info = $UA->alias('ua')->where('uid='.$uid.' and ua.agent_id = '.$parent_id)->field($field)
            ->join($join1)->join($join2)->join($join3)->join($join4)->order('ua.is_default ASC')->find();

        $address_info_str = $address_info['country'].$address_info['province'].$address_info['city'].$address_info['district'].$address_info['address'];
        
       //写入订单

        $order_data['order_sn']       = date("YmdHis").rand(10,99);
        $order_data['tid']            = C('agent_id');
        $order_data['uid']            = $uid;
        $order_data['address_id']     = $address_info['address_id'];
        $order_data['address_info']   = $address_info_str;
        $order_data['order_status']   = 0;
        //$order_data['order_payment']  = ;//支付方式
        $DM   = M('delivery_mode')->where("agent_id =" .$parent_id)->find();   //获取一级分销商的配送方式。
        $order_data['delivery_mode']  = $DM['mode_id'];
        $order_data['order_price_up'] = 0;
        $order_data['order_price']    = $order_data['order_price'];
        $order_data['parent_id']      = $yewuyuan_id;
        $order_data['parent_type']    = 2;
        $order_data['create_time']    = time();
        $order_data['update_time']    = 0;
        $order_data['mark']           = '';
        $order_data['dollar_huilv']   = $dollar_huilv;
        $order_data['agent_id']       = $parent_id;
        $order_data['is_erji_caigou'] = 1;
 
 
        $order_id = $O -> add($order_data);
        if($order_id){
            foreach( $order_goods_data as &$r ){
                $r['order_id']      = $order_id;
            }
            $res = $OG->addAll($order_goods_data);
         
            if(!$res){
                $O->rollback();
                return '添加商品失败';
            }
        }else{
            $O->rollback();
            return '添加订单失败';
        }
        $O->commit();
        return true;

        //写入订单产品        
    }

    
}