<?php
namespace Api_data\Controller;
class GoodsController extends Api_dataController{


    public function getCategoryList(){

        $id         = I('id',0,'intval');
        $goods_type = 4;
        $c_list     = D('Common/GoodsCategory') -> this_product_category2($id);
		$this      -> echo_data('100','获取成功',$c_list);       

    }

    public function getSeriesList(){

        $id          = I('id',0,'intval');
        $obj         = M('goods_series');
        $s_list      = $obj -> where('agent_id = '.C('agent_id')) -> select();
        if( $s_list ){
            foreach($s_list as &$row){
                if($row['images_path']){
                    $row['images_path'] = 'http://'.C('agent')['domain'].'/Public/'.$row['images_path'];
                    $row['series_name'] = htmlspecialchars_decode($row['series_name']);
                }
            }
        }
        $this       -> echo_data('100','获取成功',$s_list);       
    }

    public function getAttributes(){

		$goods_type  = I('goods_type', 3,'intval');
		$category_id = I('category_id',0,'intval');

		$productM    = D('GoodsCategory');
        if(!empty($category_id)){
            $productM     -> category_id($category_id);
            $category_info = $productM  -> getInfo();
            if(empty($category_info['pid'])){
                $productM               -> parent_category_id($category_id);
                $category_id_itemarray   =  $productM -> getChildUserGoodsCategoryList();
                if($category_id_itemarray){
                    $category_id_itemstr = implode(',', $category_id_itemarray);
                    $category_id         = $category_id_itemstr;
                } else {
                    $category_id = $category_id;
                }
            } else {
                $category_id     = $category_id;
            }
        } else {
            $productM              -> parent_category_id('0');
            $category_id_itemarray  = $productM -> getChildUserGoodsCategoryList();
            if($category_id_itemarray){
                $category_id_itemstr     = implode(',', $category_id_itemarray);
                $category_id             = $category_id_itemstr;
                $productM               -> parent_category_id($category_id);
                $category_id_itemarray   = $productM -> getChildUserGoodsCategoryList();
                if($category_id_itemarray){
                    $category_id_itemstr = implode(',', $category_id_itemarray);
                    $category_id         = $category_id_itemstr;
                }
            } else {
                $category_id = $category_id;
            }
            $category_id_itemstr    = implode(',', $category_id_itemarray);
            $category_id            = $category_id_itemstr;
        }
        $type     = 1;
		if( $goods_type == 3 ){
			$type = 1;
		}elseif( $goods_type == 4 ){
			$type = 3;
		}
		//获取栏目已有属性
		$goods_category_attributes = M("goods_category_attributes")->where("category_id in ($category_id) and type = $type ")->group('attr_id')->getField('attr_id',true);


        if($goods_category_attributes){
			$goods_category_attributes_itemstr = implode(',',$goods_category_attributes);
		}else{
	    	$this      -> echo_data('100','获取成功',array());       
		}

		//哪些属性加入筛选
		$goods_attributes     = M("goods_attributes")  -> where('attr_id in ('.$goods_category_attributes_itemstr.') and is_filter = 1') -> getField('attr_id,attr_name',true);
		//判断goods_attrcontorl有没有值
		$attrcontorl          = M("goods_attrcontorl") -> where('attr_id in ('.$goods_category_attributes_itemstr.') and agent_id = '.C('agent_id')) -> select();
		if($attrcontorl){
			$goods_attributes = M("goods_attrcontorl") -> join('join zm_goods_attributes on zm_goods_attributes.attr_id = zm_goods_attrcontorl.attr_id')
				-> where('zm_goods_attrcontorl.attr_id in ('.$goods_category_attributes_itemstr.')  and zm_goods_attrcontorl.is_filter = 1 and zm_goods_attrcontorl.agent_id = '.C('agent_id'))
				-> field('zm_goods_attributes.*') -> select();
		} else {
            foreach($goods_attributes as $key=>$row){
                $data = array();
                $data['attr_id']        = $key;
                $data['attr_name']      = $row;
				$goods_attributes[$key] = $data;
                unset($data);
			}
        }

		if($goods_attributes){
			//取出参与排序属性的值
			$attr             = array();
			foreach($goods_attributes as $row){
				$attr[]       = intval($row['attr_id']);
			}
			$attr_itemstr     = implode(',',$attr);
			$attr_value_array = M("goods_attributes_value") -> where('attr_id in ('.$attr_itemstr.') ') -> select();
			$attr_value       = array();
			foreach($attr_value_array as $row){
				unset($row['agent_id']);
				$attr_value[$row['attr_id']][] = $row;
			}
		}else{
            $this -> echo_data('100','获取成功',array());       
		}

		foreach($goods_attributes as &$row){
			if($attr_value[$row['attr_id']]){
				$row['value'] = $attr_value[$row['attr_id']];
			}
		}
        $goods_attributes = array_values($goods_attributes);
        $this -> echo_data('100','获取成功',$goods_attributes);       
    }

    public function getGoodsList(){

		$p                 = I('page_id',1,'intval');
		$per               = I('page_size',20,'intval');
		$order             = I('order','category_id','trim');
		$desc              = I('desc','desc','trim');

		$goods_type        = I('goods_type',0,'intval');
		$category_id       = I('category_id',0,'intval');
		$seachr            = I('keyword','','trim');
        $goods_series_id   = I('goods_series_id','','trim');

        $shape             = I('shape','','trim');
        $weight            = I('weight','','trim');

        $Preferential      = I('preferential','','trim');
        $uid			   = C('uid');


		//规则:使用冒号，逗号，分号分别隔开多个属性和多个属性的值, 例如:attr_id:attr_value,attr_value,attr_value;attr_id:attr_value,attr_value...
		$goods_attr_filter = I('goods_attr_filter','','trim');
		$productM          = D('Common/Goods');
        
		//对属性查询进行处理
		if($goods_attr_filter) {
			$goods_attr_filter_join_array  = array();
			if( strpos($goods_attr_filter,';') !== false ){
				$goods_attr_filter_array   = explode(';',$goods_attr_filter);
			}else{
				$goods_attr_filter_array[] = $goods_attr_filter;
			}
			foreach( $goods_attr_filter_array as $key => $row ){
				if( strpos($row,':') === false ) {
					$this -> echoJson(new \stdclass());
				}
				$attr_value_array      = explode(':',$row);
				$on_attrcode           = array();
				if( strpos($attr_value_array[1],',') !== false ){
					$_attr_value_array = explode(',',$attr_value_array[1]);
					foreach($_attr_value_array as $r) {
						$on_attrcode[] = " aa$key.attr_code & $r ";
					}
				} else {
					$on_attrcode[]          = " aa$key.attr_code & $attr_value_array[1] ";
				}
				$on_attrcode_or             = implode(' or ',$on_attrcode);
                $goods_attr_filter_join     .= " join zm_goods_associate_attributes aa$key on aa$key.attr_id = $attr_value_array[0] and ( $on_attrcode_or ) and aa$key.goods_id = zm_goods.goods_id ";
                if( $key > 0 ){
                    $key_jian = $key - 1 ;
                    $goods_attr_filter_join .= " and aa$key_jian.goods_id = aa$key.goods_id ";
                }
            }
			$productM -> _join ( $goods_attr_filter_join );
		}

        if($goods_type){
            $productM  -> goods_type($goods_type);
        }

		if($category_id){
			$productM -> category_id($category_id);
		}

		if($seachr){
			$productM -> seachr($seachr);
		}

        if($goods_series_id){
            $productM -> set_where('zm_goods.goods_series_id',$goods_series_id);
        }

        if($Preferential){
            $productM -> set_where('ga.agent_id',C('agent_id'));
            $productM -> set_where('ga.product_status','1');
            $productM -> _join( ' left join zm_goods_activity as ga on ga.gid = zm_goods.goods_id and ga.agent_id = ' . C('agent_id') );
        }

        if( $shape || $weight ){
            $agent_id = $productM -> get_where_agent();
			
			if($weight){
				$min_number   = substr(sprintf("%.2f", $weight),0,-1);
				$max_number   = $min_number+'0.1';
				$weight_where = " weights_name < $max_number and  weights_name >=$min_number";
			}			
			
			
            if( $shape && $weight ){
                $shape_id  = M('goods_luozuan_shape') -> where(" shape = '$shape' ") -> getField('shape_id');
                $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where $weight_where and shape_id = '$shape_id' and agent_id in ($agent_id) ) " , true );
            } else {
                if( $shape ){
                    $shape_id  = M('goods_luozuan_shape') -> where(" shape = '$shape' ") -> getField('shape_id');
                    $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where shape_id = '$shape_id' and agent_id in ($agent_id) )" , true );
                } else {
                    $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where $weight_where and agent_id in ($agent_id) )" , true );
                }
            } 
        }
        $productM      -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $productM      -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		$productM      -> _order($order, $desc);
		$productM      -> _limit($p,$per);
        if($Preferential){
			$goods_list = $productM -> getList( false, false , "zm_goods.*, ga.product_status as activity_status, ga.setting_time, ga.home_show",false,true);
        }else{
            $goods_list = $productM -> getList();
        }

        $goods_list_new = array();
        if($goods_list){
            $goods_list = $productM -> getProductListAfterAddPoint($goods_list,$uid);
            foreach($goods_list as $row){
                $r                    = array();
                $r['goods_id']        = $row['goods_id'];
                $r['goods_sn']        = $row['goods_sn'];
                $r['goods_name']      = $row['goods_name'];
                $r['category_id']     = $row['category_id'];
                $r['goods_series_id'] = $row['goods_series_id'];
                $r['goods_type']      = $row['goods_type'];
				if($row['activity_status']=='1' || $row['activity_status']=='0'){
					$r['goods_price']     = $row['activity_price'];
				}else{
					$r['goods_price']     = $row['goods_price'];
				}
                $r['marketPrice']     = $row['marketPrice'];
                if(empty($row['thumb'])){
                    $r['thumb']       = '';
                }else{
                    $r['thumb']       = $row['thumb'];
                }
                if(empty($row['goods_name_txt'])){
                    $r['goods_name']  = $row['goods_name'];
                }else{
                    $r['goods_name']  = $row['goods_name_txt'];
                }
                $r['category_id']     = $row['category_id'];
                $goods_list_new[]     = $r;
            }
        }
		$data              = array();
		$data['page_size'] = $per;
		$data['page_id']   = $p;
		$data['data']      = $goods_list_new;
        $data['total']     = $productM -> count;
        $this -> echo_data('100','获取成功',$data);     

    }

    public function getComment(){

        $page_id       = I("page_id",1,'intval');
        $page_size     = I("page_size",100,'intval');
        $gid           = I("goods_id",0,'intval');
        $where         = " goods_id= '$gid' ";
        if( empty( $page_id ) ){
            $page_id   = 1;
        }
        $limit         = (($pageid-1)*$pagesize).','.$pagesize;
        $count         = M("order_eval") -> where( $where ) -> count();
        $data['data']  = M("order_eval") -> where( $where ) -> limit( $limit )->select();
        $data['total'] = $count;
        $data['page_size'] = $page_size;
		$data['page_id']   = $page_id;
        $this -> echo_data('100','获取成功',$data);     

    }
	
	/**
	 * auth	：fangkai
	 * content：成品图片列表
	 * time	：2016-12-5
	**/
	public function chengpinImageList() {
		$page	= I('page',1,'intval');
		$n		= I('n',20,'intval');
		$Goods	= D('Common/Goods');
		$sku 	= M("Goods_sku");
		$type	= I('type',0,'intval');
		$GoodsCategory = D('Common/GoodsCategory');
		$field = 'cg.category_id,cg.category_name,cg.pid,cc.name_alias,cc.img,cc.agent_id,cc.sort_id';
		if($type == 3){
			$category_where['cg.category_name'] = '情侣对戒';
		}else{
            $category_where['cg.category_name'] = array('like','%戒%');
		}
		
		$category_where['cc.agent_id'] = C('agent_id');
		$join = 'left join zm_goods_category_config as cc on cg.category_id = cc.category_id AND  cc.is_show = 1 ';
		
		$sort = 'cc.sort_id ASC';
		$category_list = $GoodsCategory->getCategoryListTwo($field,$join,$category_where,$sort);	//查询出显示戒指的二级分类
		$category_id_array	= array_column($category_list,'category_id');
		$category_id_array	= implode(',',$category_id_array);

		$Goods  -> set_where( 'zm_goods.category_id ' , $category_id_array );             //指定所有为戒指类的产品
        $Goods  -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $Goods  -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		$Goods	-> set_where('zm_goods.goods_type','3');
		$Goods	-> set_where("zm_goods.tryThumb"," != '' ",true);
		//$Goods	->_join("left join zm_goods_sku as gs on gs.goods_id = zm_goods.goods_id ");
		$field	= "zm_goods.goods_id,zm_goods.goods_type,zm_goods.thumb,zm_goods.tryThumb,zm_goods.goods_name";
		if(in_array($type,array(1,2))){
			$Goods  -> set_where( 'gaa.attr_code' ,' & '.$type, true ); //男戒或者女戒
			$Goods  -> set_where( 'gaa.attr_id' , '13' );             	//男戒或者女戒
			$Goods	->_join("left join zm_goods_associate_attributes as gaa on gaa.goods_id = zm_goods.goods_id ");
		}
		$Goods	-> _limit($page,$n);
		
		$goods_list = $Goods -> getList(false,false,$field);
		foreach($goods_list as $key=>$val){
			if(empty($val['thumb'])){
				$goods_list[$key]['thumb']	= '';
			}else{
				$goods_list[$key]['thumb']	= $val['thumb'];
			}
			if($goods_list[$key]['trythumb']){
				$goods_list[$key]['trythumb']	= 'http://'.C('agent')['domain'].'/Public/'.$val['trythumb'];
			}
			unset($goods_list[$key]['activity_status']);
			unset($goods_list[$key]['setting_time']);
			unset($goods_list[$key]['home_show']);
			unset($goods_list[$key]['goods_price']);
			unset($goods_list[$key]['goods_type']);
			$skuStr = $sku->where("goods_id=".$val["goods_id"])->field("sku_sn")->order("sku_id asc")->select();	
			$goods_list[$key]["sku_sn"] = $skuStr[0]["sku_sn"];
		}
		
		$this -> echo_data('100','获取成功',$goods_list);
	}
	

	
	
    /*
	 * 商品详情
	 * wsl
	 * 2016年5月27日 11:02:39
	 */
	public function goodsInfo(){

        $uid        = C('uid');
		$goods_id   = I("get.goods_id");
		$goods_type = I('get.type');
        $agent_id   = I('agent_id');
		//$field		= 'g.*,ga.product_status as activity_status';
        $M          = D('Common/Goods');
        $product    = $M -> get_info($goods_id);
		$goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$uid);
		
		switch($goodsInfo['technology_level']){
			case 1:$goodsInfo['technology_level'] = "A";
				break;
			case 2:$goodsInfo['technology_level'] = "AA";
				break;
			case 3:$goodsInfo['technology_level'] = "AAA";
				break;
			case 4:$goodsInfo['technology_level'] = "AAAA";
				break;
		}
		//$hand = array();
		for($i=6;$i<=25;$i++){	 
            $hand[] = $i;
        }

		$goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goods_type);
        $goodsInfo['category_name'] = M('goods_category')->where(" category_id = '$goodsInfo[category_id]' ")->getField('category_name');
		if( $goodsInfo['goods_type'] == '4' ){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
        }else{
            //$sd_list       = array();
			if(isset($goodsInfo['similar'])){
				foreach($goodsInfo['similar'] as $key => $val){
					 $attr      = explode("^",$val['attributes']);
						if($attr){
							foreach($attr as $k=>$v){
								$temp              = explode(":",$v);
								if($temp){
									if($temp[0]=='19'){
										$attrs_size[] 	=trim($temp[1]);
									}elseif($temp[0]=='18'){
										$attrs_weight[]	=trim($temp[1]);						
									}else{
										$attrs[]   	= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');											
									}
								}
							}
						}
				}	
 				$goodsInfo['similar_19']=count($attrs_size)		? array_values(array_unique($attrs_size)) 					: null;		//尺寸
				$goodsInfo['similar_18']=count($attrs_weight) 	? array_values(array_unique($attrs_weight)) 				: null;		//金重
				$goodsInfo['similar_16']=count($attrs)			? array_values(array_unique($attrs)) 						: null;		//材质
			}			
        }
 
		$this->adjustToShow($goodsInfo);
		$goodsInfo['hand'] 					= $hand;					//手寸,尺寸从最小的6，到最大的40.
		$goodsInfo['sd_list']        		= $sd_list;
 
		
		if(C('user_login_show_product')=='1' && C('user_is_validate')=='0'){
			$goodsInfo['user_is_validate_login_show_product']='1';						//用户权限开关
		}else{
			$goodsInfo['user_is_validate_login_show_product']='0';						//用户权限开关
		}			
		
 
		
		if(isset($goodsInfo['associate_deputystone'])){
			//暂时屏蔽此字段			zengmingming		2017-08-29
			$goodsInfo['associate_deputystone'] = array();
			/*foreach($goodsInfo['associate_deputystone'] as $key=>$val){
					if($val['deputystone_num']=='0'){
						unset($goodsInfo['associate_deputystone'][$key]);
					}
			}*/
		}
		
		if(isset($goodsInfo['associate_luozuan'])){
			foreach($goodsInfo['associate_luozuan'] as $key=>&$val){
					$val['weights_name'] = round($val['weights_name'], 2);
					if($val['weights_name']=='0'){
						unset($goodsInfo['associate_luozuan'][$key]);
					}
			}
		}

		//试戴功能的判断
		
		if($goodsInfo['trythumb']){
			$goodsInfo['is_try']	= "1";
			$goodsInfo['trythumb']	= 'http://'.C('agent')['domain'].'/Public/'.$goodsInfo['trythumb'];
		}else{
			$goodsInfo['is_try']	= "0";
		}
		
		$goodsInfo['CartGoodsNumber']       = parent::GetCartGoodsNumber();
		$_SESSION['stopgap_tip_for_goodsinfo']='';	
		
        $c_info               = array();
        $c_info['uid']        = $uid;
        $c_info['goods_id']   = $goods_id;
        $c_info['goods_type'] = $goods_type;
        $c_info['agent_id']   = $agent_id;
        $c = M('collection') -> where($c_info) -> find(); //避免重复
        if( $c ){
            $goodsInfo['is_collection'] = '1';
        }else{
            $goodsInfo['is_collection'] = '0';
        }

        $expectedDeliveryTime = C('expected_delivery_time');
        $goodsInfo['expected_delivery_time'] = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? $expectedDeliveryTime : 15;
		if(empty($goodsInfo['goods_id'])){
			$this      -> echo_data('201','获取失败');
		}else{
        	$this      -> echo_data('100','获取成功',$goodsInfo);
		}
	}	
	
    public function adjustToShow(&$goodsInfo){
        foreach($goodsInfo['attributes'] as $k => $v){
            $attr_ids[] = $v['attr_id'];
        }
        $attr_ids = array_unique($attr_ids);    //获取所有属性id(不重复的id)
        foreach($attr_ids as $k1 => $v1){
            foreach($goodsInfo['attributes'] as $k2 => $v2){
                if($v2['attr_id'] == $v1){
                    $adjustGoodsAttributes[$v1] = $v2;
                    $adjustGoodsAttributes[$v1]['attr_value_name'] = '';
                }
            }
        }
        //某些多选属性的值要合并成一条记录在前端显示，如：材质颜色：白色 黄色  玫瑰金
        foreach($adjustGoodsAttributes as $k1 => $v1){
            foreach($goodsInfo['attributes'] as $k2 => $v2){
                if($v2['attr_id'] == $k1){  //如查attr_id相同，则$adjustGoodsAttributes的attr_value_name拼接上该值
                    $adjustGoodsAttributes[$k1]['attr_value_name'] .= $v2['attr_value_name'].' ';
                }
            }
        }

        //某些属性值要加上单位在前台显示，如：材质金重：10G,20G;
        foreach($adjustGoodsAttributes as $k => $v){
            if(in_array($v['attr_id'], array(18,22,27,28))){    // 18:  材质金重   22:主石大小     27:副石数量   28:副石大小
                $adjustGoodsAttributes[$k]['attr_value'] = $this->addUnit($v['attr_value'], $v['attr_id']);
            }
        }
        $goodsInfo['attributes'] = $adjustGoodsAttributes;
    }
	
    //添加单位。
	public function addUnit($value, $id){
        switch($id){
            case 18:
                $unit = 'G';break;
            case 22:
                $unit = 'CT';break;
            case 27:
                $unit = '粒';break;
            case 28:
                $unit = 'CT';break;
        }
        if(strpos($value, ',')===false){
            if(!empty($value)){
            $result = $value.$unit;
        }else{
                $result = '';
            }
        }else{ 
            $array = explode(',', $value);
            foreach($array as $k => $v){
                if(!empty($v)){
                $array[$k] = $v.$unit;
                }else{
                    $array[$k] = $v;
                }
            }
            $result = implode(',', $array);
        }
        return $result;
    }
	
	
	
	
	
	
	/*
	 * 商品详情根据材质寻找可选主石
	 * zhy
	 * 2016年5月31日 10:55:00
	 */
	 public function get_material(){
		$gid 			= I('goodsid');			//商品ID
        $material_id  	= I('material_id');		//材质ID
        $where = "1=1";
        $where .= " AND GSL.goods_id=$gid AND material_id=$material_id";
        $join = " LEFT JOIN zm_goods_luozuan_shape AS GLS ON GLS.shape_id = GSL.shape_id";
		$associateLuozuan   = M("goods_associate_luozuan")->alias("GSL")->join($join)->where($where)->select();
        if($associateLuozuan){
            $data['data']   = $associateLuozuan;
        }
		$this      -> echo_data('100','获取成功',$data);
	 }
	
	
	/*
	 * 商品详情主石副石价格接口
	 * zhy
	 * 2016年5月30日 14:19:09
	 */
	public function get_price(){
        $gid        = I("gid",0);				//商品ID
        $galid      = I("galid","0");			//可选主石
        $gadId      = I("deputystoneId","0");	//副石ID
        $materialId = I('materialId','0');		//材质
        $hand       = I('hand','0');			//手寸男			
        $hand1      = I('hand1','0');			//手寸女

		
        $uid        = C('uid');				
        $goods_type = I("goods_type",4);
        $M          = D('Common/Goods');
        $info       = $M -> get_info($gid,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');//这个函数很重要
        $data       = array('status'=>0);
        if( !$info['price_model'] ){
            list($material,$associateInfo,$luozuan,$deputystone) = $M -> getJingGongShiData($gid,$materialId,$galid,$gadId);
            $info['goods_price']                                 = $M -> getJingGongShiPrice($material, $associateInfo,$luozuan,$deputystone);
        }else{
            $category_name = M('goods_category')->where(" category_id = '$info[category_id]' ")->getField('category_name');
            $is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
            if( $is_size ){
                $size                    = $M -> getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
                $info['goods_price']     = $size['size_price'];
                $is_dui                  = InStringByLikeSearch($category_name,array('对戒'));
                if( $is_dui ){
                    $size                = $M -> getGoodsAssociateSizeAfterAddPoint(" sex = '1' and min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
                    $info['goods_price'] = $size['size_price'];
                    $size                = $M -> getGoodsAssociateSizeAfterAddPoint(" sex = '2' and min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
                    $info['goods_price'] = $size['size_price'] + $info['goods_price'];
                }
            } else {
                $ass                 = $M->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
                $info['goods_price'] = $ass['fixed_price'];
            }
        }
        $info                                                = getGoodsListPrice($info,  $uid , 'consignment', 'single');
        $marketPrice                                         = $info['marketPrice'];
		//如果为活动商品，则为活动价格，否则为正常售卖价格
		if(in_array($info['activity_status'],array('0','1'))){
			$price = $info['activity_price'];
		}else{
			$price = $info['goods_price'];
		}
        $price     = $price;
        $data      = array('price'=>$price,'marketPrice'=>$marketPrice);
		$this      -> echo_data('100','获取成功',$data);
	}

	
	
 
    public function getHand(){
        $gid        = I('gid','0','intval');
        $materialId = I('materialId','0','intval');
        $M          = D('Common/Goods');
        $goods_info = $M -> get_info($gid);
        $category_name = M('goods_category')->where(" category_id = '$goods_info[category_id]' ")->getField('category_name');
        $is_dui        = InStringByLikeSearch($category_name,array('对戒'));
        if( $is_dui ){
            list($data,$data1) = $M -> get_hand_list_duijie($gid,$materialId);
        } else {
            $data1     = array();
            $data      = $M -> get_hand_list($gid,$materialId);
        }
        $data = array('data'=>array_values($data),'data1'=>array_values($data1));
		$this      -> echo_data('100','获取成功',$data);
    }		
	
	
    
}