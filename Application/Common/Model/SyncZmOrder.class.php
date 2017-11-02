<?php
/**
 * 供应包和钻明之间的订单通讯
 * User: 王松林
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Common\Model;

class SyncZmOrder {

    Protected $autoCheckFields = false;

	
    //同步数据进入钻明官网
    Public function pushOrderToZm($order_id,$order_goods_id){
 
        $order_info_array           = M('order')->where(" order_id = '$order_id' ") -> select();

        if( count( $order_info_array ) > 0 ) {
            $order_info             = $order_info_array[0];
            if( is_array( $order_goods_id ) ){
                if( count( $order_goods_id ) == 1){
                    $order_goods_id = $order_goods_id[0];
                } else {
                    $order_goods_id = implode(',',$order_goods_id);
                }
            }elseif($order_goods_id){
                $order_goods_id     = $order_goods_id;
            }else{
				
                return false;
            }
			
 
			
			if(cookie('beizhu') && cookie('beizhu')!='___' && cookie('beizhu')!='0'){
				if(strpos(cookie('beizhu') , '___' )){
					$beizhu    = explode('___',cookie('beizhu'));
				}else{
					$beizhu[0] = cookie('beizhu');
				}
				cookie('beizhu',null);			
			}else{
				$beizhu[0] = '';
			}

 
            $order_goods_array      = M('order_goods')->where(" order_id = '$order_id' and og_id in ( $order_goods_id ) ")->select();

	 
	 
			//得到一级的agent_id , 汇率 ,在一级中的会员uid 
			$parent_id = get_parent_id();
 
			//官网汇率
			$dollar_huilv  			= M('config','zm_','ZMALL_DB')->where(" config_key = 'dollar_huilv'")->getField('config_value');

			$uid           			= get_create_user_id();
			//跟单业务员ID
			$yewuyuan_id        	= M('user','zm_','ZMALL_DB')->where(' uid = '.$uid)->getField('parent_id');
 
            $data                   = array();
            $data2                  = array();
            foreach($order_goods_array as $k=>$r){
				$row                   = array();
				$BRow                   = array(); 
				if($r['goods_type']=='1'){
					$GL   = D('GoodsLuozuan');
					$info_front = $GL->where("certificate_number = '".$r['certificate_no']."' and agent_id in(0,".C('agent_id').")") -> find();

					// if(empty($info_front)){ //数据库中没有了货品，但还可能有货
						// $info_front = unserialize($r['attribute']);
					// }
					
					// $info_front = unserialize($r['attribute']);
					$info = getGoodsListPrice($info_front, 0, 'luozuan', 'single', C('agent_id'));
					
 
					
					$point_yiji = 0; //白钻钻明给一级的加点
					$point_erji = 0; //白钻一级给二级的加点
					//一级二级的加点和
					$point_erji_and_yiji= D("Common/GoodsLuozuan") -> setLuoZuanPoint('0','0'); //白钻的加点
					$point_yiji = D("Common/GoodsLuozuan") -> setLuoZuanPoint(C('agent_id'),'0');
					$point_erji = $point_erji_and_yiji - $point_yiji;
				   
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
 
					$data['order_price'] = $data['order_price'] + $info["price"];
					
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
					$row['parent_type']     = 1;
 
					$row['luozuan_type']	= $info['luozuan_type'];
					$row['update_time']     = time();
					$row['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
					$row['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];
					$row['beizhu'] 			= $beizhu[$k];		
					$data2[]                = $row;							
				}elseif($r['goods_type']=='2'){
						$GS = D('GoodsSanhuo');
						$info_front = $GS->where("goods_id = ".$r['goods_id']." and goods_sn = '".$r['certificate_no']."' and agent_id in(0,".$parent_id.")") -> find();

						/*
						if(empty($info_front)){ //数据库中没有了货品，但还可能有货
							$info_front = unserialize($r['attribute']);
						}
						*/

						$info = getGoodsListPrice($info_front, 0, 'sanhuo', 'single', $parent_id);
						if($info_front['agent_id'] > 0){   //一级的散货
							$point_yiji = 100;
						}else{
							$point_yiji = 100 + M('trader')->where('t_agent_id = '. $parent_id)->getField('sanhuo_advantage');
						}

						$point_erji = 100 + M('trader')->where('t_agent_id = '. C('agent_id'))->getField('sanhuo_advantage');
						$info['caigou_price']   = round($info_front['goods_price']*$point_yiji*$point_erji*$r['goods_number']/10000, 0);
						$info['goods_price']    = $info['xiaoshou_price'] = round($info_front['goods_price'] * $r['goods_number'] * $point_yiji * $point_erji/10000, 0);
						$info['goods_price2']   = 0; //特价
						$info['attribute'] = serialize($info);
						$info['goods_type'] = '2';
						$data['order_price'] = $data['order_price'] + $info["goods_price"];
						$row['order_id']        = 0;
						$row['goods_id']        = $info['goods_id'];
						$row['certificate_no']  = $info['goods_sn'];
						$row['attribute']       = $info['attribute'];
						$row['goods_price']     = $info['goods_price'];
						$row['goods_price_up']  = 0;
						$row['goods_type']      = $info['goods_type'];
						$row['goods_number']    = $r['goods_number'];
						$row['goods_number_up'] = 0;
						$row['parent_id']       = $yewuyuan_id;
						$row['parent_type']     = 1;
						$row['update_time']     = time();
						$row['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
						$row['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];
						$row['luozuan_type']	= 0;
						$row['beizhu'] 			= $beizhu[$k];	
						$data2[]                = $row;						
				}elseif($r['goods_type']=='4'){
					//特指板房数据
					$info_front 				= unserialize($r['attribute']);	
					$info_front_banfang_goods_id= $info_front['banfang_goods_id'];
					if($info_front['banfang_goods_id']>0){
						//$info_front['goods_sn']= $row['certificate_no'];
						
						$AssociateLuozuanInfo 	= M('banfang_goods_associate_luozuan','zm_','ZMALL_DB')->where(' goods_id = '.$info_front['banfang_goods_id'].' and luozuan_weight = '.$info_front['luozuanInfo']['weights_name'])->find();
						$deputyStoneList		= M('banfang_goods_associate_deputystone','zm_','ZMALL_DB')
													->alias('bgad')
													->field('bgad.*,gls.shape_name as deputystone_shape_name')
													->where(array('bgad.associate_luozuan_id'=>$AssociateLuozuanInfo['associate_luozuan_id'],'bgad.goods_id'=>$info_front['banfang_goods_id']))
													->join('left join zm_goods_luozuan_shape as gls on gls.shape_id = bgad.deputystone_shape_id ')
													->select();			//副石列表		
													
						$info_front['images']	= M('banfang_goods_images','zm_','ZMALL_DB')->where('goods_id = '.$info_front['banfang_goods_id'])->select();	
						
						$deputy_stone_string 	= '';
						array_walk($deputyStoneList, function ($via) use (&$deputy_stone_string){
							if($via['deputystone_weight']>'0'){
								$deputy_stone_string .= $via['deputystone_shape_name'].' '.$via['deputystone_weight'].' ct '.$via['deputystone_number'].'颗  ';
							}
						});

						$AssociateMaterialId	= '';
						($info_front['associateInfo']['material_name'] == '18K真分色') && ($info_front['associateInfo']['material_name'] = '18K分色');
						$AssociateMaterialId 	= M('banfang_material','zm_','ZMALL_DB')->where(' material_name = \''.$info_front['associateInfo']['material_name'].'\'')->getField('material_id');

						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['luozuan_weight'] 																			= $AssociateLuozuanInfo['luozuan_weight'];
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['luozuan_number'] 																			= $AssociateLuozuanInfo['luozuan_number'];
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['luozuan_shape_name'] 																		= $info_front['luozuanInfo']['shape_name'];
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['luozuan_shape_id'] 																			= $AssociateLuozuanInfo['luozuan_shape_id'];
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['goods_number'] 					= $r['goods_number_up'] ? $r['goods_number_up'] : $r['goods_number'];
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['gb'] 							= (strpos($info_front['associateInfo']['material_name'], 'PT') !== false) ? 'PT950'	:	'AU750';							
							 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['lettering'] 						= $info_front['word'];	 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['technology'] 					= '1';	
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['basic_cost'] 					= $info_front['associateInfo']['basic_cost'];	
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['attach_cost'] 					= '100';	
						 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['loss'] 							= $info_front['associateInfo']['loss_name']; 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['gold_weight'] 					= $info_front['associateInfo']['weights_name']; 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['deputystone_type'] 				= '1'; 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['deputystone_attribute'] 			= substr($info_front['deputystone']['deputystone_name'],0,4);
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['deputy_stone_string'] 			= $deputy_stone_string; 
						$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['hand'] 							= $info_front['hand']; 
						
 
 
						$BRow['order_id']        = 0;
						$BRow['goods_id']        = $info_front['banfang_goods_id'];
						$BRow['certificate_no']  = M('banfang_goods','zm_','ZMALL_DB')->where('goods_id = '.$info_front['banfang_goods_id'])->getField('goods_sn');

						if(!$BRow['certificate_no']){
							$result = array('success'=>false, 'msg'=>'编号为'.$info_front['goods_sn'].'的商品在官网已经下架或者删除！','error'=>true);
							header('Content-Type:application/json; charset=utf-8');exit(json_encode($result));
						}
						$BRow['goods_sn']		 = $info_front['goods_sn'] = $BRow['certificate_no'];
						$BRow['attribute'] 		 = serialize($info_front);
						$BRow['goods_price']     = $this->GetDisGoodsPrice('goods_id='.$info_front['banfang_goods_id'].'&material_id='.$AssociateMaterialId.'&associate_luozuan_id='.$AssociateLuozuanInfo['associate_luozuan_id'].'&deputystone_attribute='.$banfang_data['zs_'.$AssociateLuozuanInfo['associate_luozuan_id']]['value'][$info_front['associateInfo']['material_name']][0]['deputystone_attribute'].'&technology=1') ? :   $info_front['caigou_price'] ;
						$BRow['goods_price']     = ($r['goods_number']>'1') ? $BRow['goods_price'] * $r['goods_number']  : $BRow['goods_price'];
						$BRow['goods_price_up']  = 0;
						$BRow['goods_type']      = '7';
						$BRow['goods_number']    = $r['goods_number'];

						$BRow['goods_number_up'] = 0;
						$BRow['parent_id']       = $yewuyuan_id;
						$BRow['parent_type']     = 1;
						$BRow['luozuan_type']	 = 0;						
						$BRow['update_time']     = time();
						$BRow['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
						$BRow['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];

						$BRow['banfang_data']    = json_encode($banfang_data);
						
						$BData['order_price'] 	 += $BRow["goods_price"];
						$data3[]                 = $BRow;						
					}
 
				}elseif($r['goods_type']=='16'){

				}
 
 
               // $data['order_price']   += $info['goods_price'];
            }


 			if(count($data2)){
				$data['order_sn']       = date("YmdHis").rand(00,100);
				$data['tid']            = $order_info['agent_id'];
				$data['uid']            = $uid;
				$data_address_id        = M('user_address','zm_','ZMALL_DB')->where(' uid = '.$uid)->getField('address_id');
				if($data_address_id){
					$data['address_id'] = $data_address_id;
				}else{
					return false;
				}
				$data['order_status']   = $order_info['order_status'];
				$data['delivery_mode']  = '1';						//写死送货上门。
				$data['order_price_up'] = 0;
				$data['parent_id']      = $yewuyuan_id;
				$data['parent_type']    = '1';
				$data['create_time']    = time();
				$data['update_time']    = 0;
				$data['dollar_huilv']   = $dollar_huilv;
				$data['agent_id']       = $order_info['agent_id'];
				$data['order_id']   	= $this->get_order_id();		
				$zmobj                  = M('order','zm_','ZMALL_DB');
				$new_order_id           = $zmobj -> add($data);			
			}
 

			
			
			if(count($data3)){
				$BData['order_sn']       = date("YmdHis").rand(00,100);
				$BData['tid']            = $order_info['agent_id'];
				$BData['uid']            = $uid;
				$data_address_id         = M('user_address','zm_','ZMALL_DB')->where(' uid = '.$uid)->getField('address_id');
				if($data_address_id){
					$BData['address_id'] = $data_address_id;
				}else{
					return false;
				}
				$BData['order_status']   = $order_info['order_status'];
				$BData['delivery_mode']  = '1';						//写死送货上门。
				$BData['order_price_up'] = 0;
				$BData['parent_id']      = $yewuyuan_id;
				$BData['parent_type']    = '1';
				$BData['create_time']    = time();
				$BData['update_time']    = 0;
				$BData['dollar_huilv']   = $dollar_huilv;
				$BData['agent_id']       = $order_info['agent_id'];
				$BData['order_id']   	 = $this->get_order_id();
				$zmobj                   = M('order','zm_','ZMALL_DB');
				$new_order_id            = $zmobj -> add($BData);		
			}
	
 
            if($new_order_id){
                foreach( $data2 as &$r ){
                    $r['order_id']      = $data['order_id'];				
                }
				$res = M('order_goods','zm_','ZMALL_DB') -> addAll($data2);
				
				if(count($data3)){
					foreach( $data3 as &$r ){
						$r['order_id']      = $BData['order_id'];				
					}
					$res = M('order_goods','zm_','ZMALL_DB') -> addAll($data3);
					$this->GetDisGoodsPrice('order_id='.$BData['order_id'].'&uid='.$uid);
				}
				
                if(!$res){
                    return false;
                }


            }else{
                return false;
            }
        }
        return true;
    }
	
    /**
     * 代销货详情
     * zhy	find404@foxmail.com
     * 2017年5月17日 15:11:45
     */
	 

 
    public function GetDisGoodsPrice($where){
		if (strpos($where, 'order_id') !== false) {
			$FunctionName = 'GetBanfangGoodsPrice';
		} else {
			$FunctionName = 'getNewestPrice';
		}
		$url 	= C('ZMALL_URL').'/Home/BanFang/'.$FunctionName.'?'.$where;
 
		$ch 	= curl_init();
		curl_setopt($ch,CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch,CURLOPT_HTTPHEADER, array("X-REQUESTED-WITH: XMLHTTPREQUEST"));
		curl_setopt($ch,CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		curl_setopt($ch,CURLOPT_NOSIGNAL,TRUE);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch,CURLOPT_BINARYTRANSFER,TRUE);
		$price 	= curl_exec($ch);
		$price	= json_decode($price);
		$price	= $price->data->price;
		curl_close($ch);
		return is_numeric($price) ? $price : null;
    }
	
	/*
    //同步数据进入钻明官网
    Public function pushOrderToZm($order_id,$order_goods_id){

        $order_info_array = M('order')->where("order_id = '$order_id'") -> select();
        if( count($order_info_array) > 0 ) {
            $order_info   = $order_info_array[0];
            if( is_array( $order_goods_id ) ){
                if( count( $order_goods_id ) == 1 ){
                    $order_goods_id = $order_goods_id[0];
                }else{
                    $order_goods_id = implode(',',$order_goods_id);
                }
            } elseif ( $order_goods_id ){
                $order_goods_id     = $order_goods_id;
            }else{
                return false;
            }
            $order_goods_array      = M('order_goods')->where(" order_id = '$order_id' and og_id in ($order_goods_id)")->select();
            $data                   = array();
            $data['order_sn']       = date("YmdHis").rand(00,100);
            $data['tid']            = $order_info['agent_id'];
            $data['uid']            = 4931;//$order_info['uid'];
            $data['address_id']     = '0';
            $data['order_status']   = $order_info['order_status'];
            $data['delivery_mode']  = $order_info['delivery_mode'];
            $data['order_price_up'] = '0';
            $data['parent_id']      = '26';
            $data['parent_type']    = '1';
            $data['create_time']    = time();
            $data['update_time']    = '0';
            $data['mark']           = '';
            $data['dollar_huilv']   = $order_info['dollar_huilv'];
            $data['agent_id']       = $order_info['agent_id'];
            $data2 = array();
            foreach($order_goods_array as $r){
                $row = array();
                $row['goods_id']        = $r['goods_id'];
                $row['certificate_no']  = $r['certificate_no'];
                $row['attribute']       = $r['attribute'];
                $row['goods_price']     = $r['goods_price'];
                $row['goods_price_up']  = $r['goods_price_up'];
                $row['goods_type']      = $r['goods_type'];
                $row['goods_number']    = $r['goods_number'];
                $row['goods_number_up'] = $r['goods_number_up'];
                $row['parent_id']       = '26';
                $row['parent_type']     = 1;
                $row['update_time']     = 0;
                $row['fahuo_time']      = $r['fahuo_time'];
                $row['have_goods']      = $r['have_goods'];
                $data2[] = $row;
                $data['order_price']   += $r['goods_price'];
            }
            $zmobj                      = M('order','zm_','ZMALL_DB');
            $new_order_id               = $zmobj -> add($data);
            if($new_order_id){
                foreach( $data2 as &$r ){
                    $r['order_id']      = $new_order_id;
                }
                M('order_goods','zm_','ZMALL_DB') -> addAll($data2);
            }
        }
        return true;
    }


    //拉取钻明官网数据
    Public function pullOrderOfZm($order_id){
    }

    //添加采购单	
    Public function addCaiGouDan($order_id,$order_goods_id){

        $order_info_array           = M('order')->where(" order_id = '$order_id' ") -> select();

        if( count( $order_info_array ) > 0 ) {
            $order_info             = $order_info_array[0];
            if( is_array( $order_goods_id ) ){
                if( count( $order_goods_id ) == 1){
                    $order_goods_id = $order_goods_id[0];
                } else {
                    $order_goods_id = implode(',',$order_goods_id);
                }
            }elseif($order_goods_id){
                $order_goods_id     = $order_goods_id;
            }else{
				
                return false;
            }
			
            $order_goods_array      = M('order_goods')->where(" order_id = '$order_id' and og_id in ( $order_goods_id ) ")->select();

			//得到一级的agent_id , 汇率 ,在一级中的会员uid  
			$parent_id = get_parent_id();
 
       
			if(!$dollar_huilv_type ){
				$dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');         
			}else{
				$dollar_huilv  = getAgentConfigValue('dollar_huilv', $parent_id);
			}			
 
			$uid           		= get_create_user_id();  
			$yewuyuan_id        = M('user')->where('agent_id =' .$parent_id. ' and uid = '.$uid)->getField('parent_id'); 
 
 
            $data                   = array();
            $data['order_sn']       = date("YmdHis").rand(00,100);
            $data['tid']            = $order_info['agent_id'];
            $data['uid']            = $uid;
            $data['address_id']     = $order_info['address_id'];
            $data['order_status']   = $order_info['order_status'];
            $data['delivery_mode']  = $order_info['delivery_mode'];
            $data['order_price_up'] = 0;
            $data['order_price']    = 0;
            $data['parent_id']      = $order_info['parent_id'];
            $data['parent_type']    = $order_info['parent_type'];
            $data['create_time']    = time();
            $data['update_time']    = 0;
            $data['mark']           = '';
            $data['dollar_huilv']   = $order_info['dollar_huilv'];
            $data['agent_id']       = $order_info['agent_id'];
            $data['is_yiji_caigou'] = 1;
            $data2                  = array();
            foreach($order_goods_array as $r){
				$row                    = array();
				if($r['goods_type']=='1'){
					$GL   = D('GoodsLuozuan');
					$info_front = $GL->where("certificate_number = '".$r['certificate_no']."' and agent_id in(0,".$data['agent_id'].")") -> find();
					$info = getGoodsListPrice($info_front, 0, 'luozuan', 'single', C('agent_id'));
					$point_yiji = 0; //白钻钻明给一级的加点
					$point_erji = 0; //白钻一级给二级的加点
					//一级二级的加点和
					$point_erji_and_yiji= D("Common/GoodsLuozuan") -> setLuoZuanPoint('0','0'); //白钻的加点
					$point_yiji = D("Common/GoodsLuozuan") -> setLuoZuanPoint(C('agent_id'),'0');
					$point_erji = $point_erji_and_yiji - $point_yiji;
				   
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
 
					$data['order_price'] = $data['order_price'] + $info["price"];
					
					$row['order_id']        = 0;
					$row['goods_id']        = $info['gid'];
					$row['certificate_no']  = $info['certificate_number'];
					$row['attribute']       = $info['attribute'];
					$row['goods_price']     = $info['price'];
					$row['goods_price_up']  = 0;
					$row['goods_type']      = $info['goods_type'];
					$row['goods_number']    = 1;
					$row['goods_number_up'] = 0;
					$row['parent_id']       = empty($yewuyuan_id)? 0 : $yewuyuan_id;
					$row['parent_type']     = 2;
					$row['agent_id']        = $parent_id;
					$row['luozuan_type']	= $info['luozuan_type'];
					$row['update_time']     = time();
					$row['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
					$row['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];

				}elseif($r['goods_type']=='2'){
						$GS = D('GoodsSanhuo');
						$info_front = $GS->where("goods_id = ".$r['goods_id']." and goods_sn = '".$r['certificate_no']."' and agent_id in(0,".$parent_id.")") -> find();

						if(empty($info_front)){ //数据库中没有了货品，但还可能有货
							$info_front = unserialize($r['attribute']);
						}

						$info = getGoodsListPrice($info_front, 0, 'sanhuo', 'single', $parent_id);
						if($info_front['agent_id'] > 0){   //一级的散货
							$point_yiji = 100;
						}else{
							$point_yiji = 100 + M('trader')->where('t_agent_id = '. $parent_id)->getField('sanhuo_advantage');
						}

						$point_erji = 100 + M('trader')->where('t_agent_id = '. C('agent_id'))->getField('sanhuo_advantage');
						$info['caigou_price']   = round($info_front['goods_price']*$point_yiji*$point_erji*$r['goods_number']/10000, 0);
						$info['goods_price']    = $info['xiaoshou_price'] = round($info_front['goods_price'] * $r['goods_number'] * $point_yiji * $point_erji/10000, 0);
						$info['goods_price2']   = 0; //特价
						$info['attribute'] = serialize($info);
						$info['goods_type'] = '2';
						$data['order_price'] = $data['order_price'] + $info["goods_price"];
						$row['order_id']        = 0;
						$row['goods_id']        = $info['goods_id'];
						$row['certificate_no']  = $info['goods_sn'];
						$row['attribute']       = $info['attribute'];
						$row['goods_price']     = $info['goods_price'];
						$row['goods_price_up']  = 0;
						$row['goods_type']      = $info['goods_type'];
						$row['goods_number']    = $r['goods_number'];
						$row['goods_number_up'] = 0;
						$row['parent_id']       = $yewuyuan_id;
						$row['parent_type']     = 2;
						$row['agent_id']        = $parent_id;
						$row['update_time']     = time();
						$row['fahuo_time']      = empty($info_front['fahuo_time'])?0:$info_front['fahuo_time'];
						$row['have_goods']      = empty($info_front['have_goods'])?0:$info_front['have_goods'];
						$row['luozuan_type']	= 0;
				}
                $data2[]                = $row;
               // $data['order_price']   += $info['goods_price'];
            }

            $new_order_id               = M('order') -> add($data);
            if($new_order_id){
                foreach( $data2 as &$r ){
                    $r['order_id']      = $new_order_id;
                }
                $res = M('order_goods')->addAll($data2);
                if(!$res){
                    return false;
                }
            }else{
                return false;
            }
        }
        return true;
    }
		*/
		protected function get_order_id(){
		//得到zm_order 的auto_increment   3602 3609  3625存在重复
		$sql_order="show table status where name ='zm_order'";
		$data_order=M('order_goods','zm_','ZMALL_DB')->query($sql_order);  
		//得到zm_order_jiehuodan 的auto_increment
		$sql_jiehuodan="show table status where name ='zm_order_jiehuodan'";
		$data_jiehuodan=M('order_goods','zm_','ZMALL_DB')->query($sql_jiehuodan);  
		//哪个大，orderid就取哪个值
		if($data_order[0]['auto_increment'] > $data_jiehuodan[0]['auto_increment']){
		  $order_id = $data_order[0]['auto_increment'];
		}elseif($data_order[0]['auto_increment'] == $data_jiehuodan[0]['auto_increment']){
		  $order_id = $data_order[0]['auto_increment'] + 1;
		}else{
		  $order_id = $data_jiehuodan[0]['auto_increment'];   
		}
		$SI =M('order_auto_id','zm_','ZMALL_DB');
		$SI->startTrans();
		$maxid = $SI->where("tablename = 'order_id'")->getField('maxid');
		if($maxid > $order_id){
		  $order_id = $maxid;
		}
		$number = $order_id + 1;
		$data['maxid'] = $number;   
		$res = $SI->where("tablename = 'order_id'")->save($data);   

		//修改两张表的auto_increment  
		//$sql1 = "ALTER TABLE 'zm_order' auto_increment = ".$number ;
		//$sql2 = "ALTER TABLE 'zm_order_jiehuodan' auto_increment = ".$number ;  
		if(!$res1){     
		  $SI->commit();
		}else{      
		  $SI->rollback();      
		  return 0;
		}     
		return $order_id;
	  }
	
}