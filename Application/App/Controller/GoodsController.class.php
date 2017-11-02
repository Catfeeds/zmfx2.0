<?php
namespace App\Controller;
class GoodsController extends AppController{
      
    /**
     * 商品分类
	 * zhy
	 * 2016年5月23日 15:19:58
     */
    public function index(){
		$this -> categorys		=  D('Common/GoodsCategory') ->this_product_category();
		$this -> display();
	}
	
	
	/**
     * 品牌系列
	 * zhy
	 * 2016年5月24日 10:03:57
     */
	public function brand(){
		$categoryId = I("cid",0);
        if($categoryId>0) {    
            $this->prepareHtmlByCat($categoryId, 3, 1);//3是goodstype,1是attr_type
        }else{         
            $this->prepareHtmlByDefault(3,1);
        }
		$this->display();
	}
	
	/**
     * 个性定制
	 * zhy
	 * 2016年5月24日 10:03:57
     */
	public function custom($id=''){
		$categoryId='60';//此处写死。
		$this->cateAttrs  = $this->cateAttrs_attributes(4,$categoryId); 
		$this->data=  $this->custom_pro($categoryId,4);  
		$this->display();
	}
	
	/**
     * 公共获取分类
	 * zhy
	 * 2016年10月25日 11:45:30
     */
	public function cateAttrs_attributes($goods_type,$category_id){
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
			$this -> echoJson(new \stdclass());
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
			$this -> echoJson(new \stdclass());
		}

		foreach($goods_attributes as &$row){
			if($attr_value[$row['attr_id']]){
				$row['value'] = $attr_value[$row['attr_id']];
			}
		}
		return ($goods_attributes);
 
	}
	
	/**
     * 封装公用查询方法
	 * zhy
	 * 2016年5月25日 16:49:00
     */
	public function custom_pro($categoryId,$goodsType){
        $p = I('p',1);
        $n = I('n',10);
        $M = D('Common/Goods');	
        $M -> goods_type($goodsType);
		if($categoryId){
            $M -> set_where( 'zm_goods.category_id' , $categoryId );
		}
		$M->_order('category_id','desc');
        $M         -> _limit($p,$n);
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		$data = $M -> getList();
		if($data){
			$data  = $M -> getProductListAfterAddPoint($data,$_SESSION['app']['uid']);
			$count = $M ->get_count();
			$data[0]['num']=$count;
		}
		return $data;
	}
	
	/**
     * 个性定制搜索ajax接入点
	 * zhy
	 * 2016年5月25日 11:54:18
     */
	public function customcall(){
		$series_id =  I('post.series_id');
        $categoryId = I('post.categroyId');		//分类顶级ID
        $goodsType  = I('post.goodsType');		//商品分类
		$attr_id    = I('post.attr_id');		 
		$categorygoodsId = I('post.categorygoodsId');	//商品分类。	
		$content    = I('post.content');		//搜索专用		
		$p          = I('post.page');	
		$n='10';
		$pid = $this->getCatPid($categoryId);

        $M   = D('Common/Goods');
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		if($categorygoodsId>0){
            $M -> set_where( 'zm_goods.goods_series_id'  , $categorygoodsId);
            $M -> set_where( 'zm_goods.goods_type',$goodsType);
			$M -> _order('goods_id','desc');
            $M -> _limit($p+1,$n);
			$stopgap_data = $M->getList();
			if($stopgap_data){
					$stopgap_data  = $M -> getProductListAfterAddPoint($stopgap_data,$_SESSION['app']['uid']);
			}
			$data['data'] =$stopgap_data;
			$data['tip']= '2';
			$this->ajaxReturn($data);exit();
		}
		if(!empty($content)){					//商品成品搜索
            $M -> _limit($p+1,$n);
            $M -> set_where( 'zm_goods.goods_type'  , $goodsType);
            $M -> seachr($content);
			$M -> _order('goods_id','desc');
			$stopgap_data         = $M -> getList();
			if($stopgap_data){
				$stopgap_data  = $M -> getProductListAfterAddPoint($stopgap_data,$_SESSION['app']['uid']);
				
			}
			$data['data']          =$stopgap_data;
			$data['Searchcontent'] = $content;
			$data['tip']           = '2';					//2标志为搜索过来的数
		}elseif($pid=='0'){
			$cateAttrs = getProductCommonAttrByCat($categoryId,1);
			$subIds    = getSubCatIds($categoryId);
            $M -> _limit($p,$n);
            $M -> set_where( 'zm_goods.category_id'  , implode(',', $subIds));
            $M -> set_where( 'zm_goods.goods_type'  , $goodsType);
			$M -> _order('goods_id','desc');
			$data['data']=$M -> getList(); 
			if($data['data']){		$data['tip']='2';	}		
			$data['data']= $this->goods_price($data['data']);
		}else{
			$filterAttrs=$attr_id;
			$data=$this->customapi($series_id, $categoryId, $goodsType, $filterAttrs, $p, $orderby);
 
		}
		$this->ajaxReturn($data);
	}
	
	
	/**
     * 个性定制api搜索接口
	 * zhy
	 * 2016年5月24日 10:03:57
     */
	public function customapi($series_id, $category_id, $goodsType, $filterAttrs, $p, $orderby){
		$page='10';				//分页内置为十页一翻。
		$n = (!$p) ? 1 : $p+1;
		$M            = D('Common/Goods');
		if($goodsType=='3'){
			$M-> category_id($category_id);
		}
		
		if($filterAttrs){	
			$goods_attr_filter_array = explode('--',$filterAttrs);				
			foreach( $goods_attr_filter_array as $key => $row ){
				if($row){
					$attr_value_array      = explode(',',$row);
					$on_attrcode           = array();
					if($attr_value_array){
						foreach($attr_value_array as $e=>$r) {
								if($e!=0 && $r){
									$on_attrcode[] = " aa$key.attr_code & $r ";
								}
						} 
					} else {
						$on_attrcode[]              = " aa$key.attr_code & $attr_value_array[1] ";
					}
					$on_attrcode_or                 = implode(' or ',$on_attrcode);
					$goods_attr_filter_join        .= " join zm_goods_associate_attributes aa$key on aa$key.attr_id = $attr_value_array[0] and ( $on_attrcode_or ) and aa$key.goods_id = zm_goods.goods_id ";
					if( $key > 0 ){
						//$key_jian = $key - 1 ;
						$goods_attr_filter_join .= " and aa$key.goods_id = aa$key.goods_id ";
					}
				}
            }
			//$M-> set_where( 'zm_goods.category_id',$category_id);		
			$M -> set_where( 'zm_goods.goods_type'  , "$goodsType");
			$M -> _join ( $goods_attr_filter_join);
			$field='zm_goods.*';
			$tip='1';
		}else{		
			//if($goodsType=='3')		$M-> set_where( 'zm_goods.category_id',$category_id);	
			$M -> goods_type($goodsType);
			$tip='2';
		}
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		$M->_order('zm_goods.category_id','desc');
        $M->_limit($n,$page);
		$products  = $M -> getList(false,false,$field,false,false);

		if($n=='0'){$tip='3';}
		if($products){
			$products  = $M -> getProductListAfterAddPoint($products,$_SESSION['app']['uid']);
			$data['data'] = $products;
			if($filterAttrs){
				$count = $M ->get_count();
				if($n==1){
						$tip='1';			//html
				}elseif($n>1){	
					if(count($products)<10){
						$tip='2';			//append				
					}elseif(count($products)==10){		
						if($count/10!=0){			
							$tip='2';			//append
						}
					}else{	
						$tip='1';			//html
					}

				}else{
						$tip='1';
				}
			}
			$data['tip']= $tip;
			return ($data);
		}
 
	}
	/**
     * 公共价格处理
	 * zhy
	 * 2016年6月1日 14:56:38
     */
	 public function goods_price($products,$uid_goods_discount){
		 if($products){
			 if(empty($uid_goods_discount)){
				$uid_goods_discount = isset($_SESSION['app']['goods_discount']) ? $_SESSION['app']['goods_discount'] : 0;	
			 }
            foreach($products as $key=>$val){
                $products[$key]['goods_name_txt'] = msubstr($val['goods_name'],0,50,'utf-8');
                if($val['thumb'] == ''){
                    $products[$key]['thumb'] =  __ROOT__."/Public/Uploads/product/nopic.png";
                }else{
                    $products[$key]['thumb'] =  __ROOT__."/Public/".$val['thumb'];
                }
                //if($val['goods_type'] == 6 && $this->uType>1){
                //    $products[$key]['goods_price'] = formatRound((C("consignment_advantage")/100+1)*$val['goods_price'],2);
                //}
                if(C('agent_id') !=  $products[$key]['agent_id']){
                    $pifa_consignment_advantage = M('trader')->where('t_agent_id='.C('agent_id'))->getField("consignment_advantage");                    
                    $products[$key]['goods_price'] = formatRound((($pifa_consignment_advantage+100+($uid_goods_discount))/100)*$products[$key]['goods_price'],2);
                }
                $products[$key]['goods_price'] = formatRound(((C("consignment_advantage")+100+($uid_goods_discount))/100)*$products[$key]['goods_price'],2);
            }
        }
		return ($products);
	 }
	 
	/**
     * 商品数据查询函数
	 * zhy
	 * 2016年6月1日 15:25:29
     */
	 public function goods_sql($where,$limit){
		 $limit = empty($limit)?10:$limit;
		 $M     = M('goods');
		 $count = $M->where($where)->count(); 
		 $data  = $M->where($where)->limit($limit)-> order('category_id desc')->select();  
		 if($data){
			$data[0]['num'] = $count;	
		 }
		 return $data;
	 }
	
	/**
     * 珠宝成品系列
	 * zhy
	 * 2016年5月25日 16:36:04
     */
	 public function goodsCat(){
		$p               = I('p','1');				    //分类ID
		$n               = I('n','10');				    //分类ID
		$categoryId      = I('get.cid');				//分类ID
		$categorygoodsId = I('get.gid');				//商品分类过来ID
		$content         = I('get.content','','trim');	//分类搜索专用
		$category_id     = 60;
		$goodsType='3';									//成品
		$pid = $this->getCatPid($categoryId);

 
        $M               = D('Common/Goods');
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		if(is_numeric($categorygoodsId) && empty($categoryId)){					//品牌系列搜索分类ID
            $M -> set_where( 'zm_goods.goods_series_id'  , $categorygoodsId);
            $M -> set_where( 'zm_goods.goods_type'  , $goodsType);
            $M -> _limit($p,$n);
			$M->_order('goods_id','desc');
			$products = $M->getList();
			if($products){
				$count	= $M -> get_count();
				$data	= $M -> getProductListAfterAddPoint($products,$_SESSION['app']['uid']);
				$data[0]['num']=$count;
			}
			if($data[0]['goods_id']){	$data[0]['is_gid']='1';				}   //商品分类标志
			$this->data= $data;
		}elseif(!empty($content)){												//分类搜索专用
            $M -> set_where( 'zm_goods.goods_type', $goodsType);
            $M -> seachr($content);			
			$count =$M->get_count();
			$M->_order('goods_id','desc');
			$M -> _limit($p,$n);
			$products = $M->getList();
			if($products){
				$data = $M -> getProductListAfterAddPoint($products,$_SESSION['app']['uid']);
			}
			$data[0]['num']=$count;
			$data[0]['Searchcontent']=$content;
			$this->data=$data;
		}elseif($pid=='0'){										    //如果是顶级分类,获取所有子类的共同筛选属性
			$subIds    = getSubCatIds($categoryId);
            $M -> set_where( 'zm_goods.category_id'  , implode(',', $subIds));
            $M -> set_where( 'zm_goods.goods_type'  , $goodsType);
			$M -> _order('goods_id','desc');
            $data = $M->getList(); 
			if($data){	$data[0]['category_id']=$categoryId;	$data[0]['crumbs_master']='3';	}		//代表为分类主。
			$this->data= $this->goods_price($data);
		}else{		
			$this->data=  $this->custom_pro($categoryId,$goodsType);
		}
		$this->cateAttrs  = $this->cateAttrs_attributes($goodsType,$categoryId);
		//$this->cateAttrs = getProductAttrByCat($categoryId,1); //根据CategoryId,attr_type获取筛选属性,1 表示成品属性2成品规格3金工石属性
		$this->display(); 
	 }
	 
	/*
	 * 商品详情
	 * zhy
	 * 2016年5月27日 11:02:39
	 */
	public function goodsInfo(){
		$goods_id = I("get.goods_id");
		$goods_type = I('get.type');
		
		
        $M          = D('Common/Goods');
		$field		= 'g.*,ga.product_status as activity_status';
        $product    = $M -> get_info($goods_id,0,'shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ','',$field);
		$goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$_SESSION['app']['uid']);		
		
		
		
		// if($goods_type=='3'){			//成品
			// $goodsInfo = $this->getProduct($goods_id);
		// }elseif($goods_type=='4'){		//定制
			// $goodsInfo = $this->getJietuo($goods_id);
		// }else{
			// exit();
		// }
		// 商品信息
		// $goodsInfo  = D('Common/Goods') -> getProductInfoAfterAddPoint($goodsInfo,$_SESSION['app']['uid']);
		
		
		
		$goodsInfo['productService'] = M("article")->where(" is_show = 1 AND title = '产品服务' and agent_id in ('".C('agent_id')."','0')")->find();
		$goodsInfo['payment'] = M("article")->where("is_show = 1 AND title = '支付配送' and agent_id in ('".C('agent_id')."','0') ")->find();
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

		$hand = array();
		for($i=6;$i<=25;$i++){	$hand[] = $i;	}
		$this->hand = $hand;		//手寸,尺寸从最小的6，到最大的40.


		$this                     -> adjustToShow($goodsInfo);
		$goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goods_type);
		$this->goodsInfo           = $goodsInfo;

		if( $goodsInfo['goods_type'] == '4' ){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
        }else{
            $sd_list       = '';
        }
        $this -> sd_list       = $sd_list;
		$this -> category_name = M('goods_category')->where(" category_id = '$goodsInfo[category_id]' ")->getField('category_name');

		// 自定义交货日期
        $expectedDeliveryTime = C('expected_delivery_time');
        $this->expectedDeliveryTime = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? intval($expectedDeliveryTime) : 15;

        $_SESSION['stopgap_tip_for_goodsinfo']='';
		$this->display();
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
		$gid 			= I('post.goodsid');			//商品ID
        $material_id  	= I('post.material_id');		//材质ID
        $where = "1=1";
        $where .= " AND GSL.goods_id=$gid AND material_id=$material_id";
        $join = " LEFT JOIN zm_goods_luozuan_shape AS GLS ON GLS.shape_id = GSL.shape_id";
		$associateLuozuan   = M("goods_associate_luozuan")->alias("GSL")->join($join)->where($where)->select();
        if($associateLuozuan){
            $data['data']   = $associateLuozuan;
            $data['status'] = 1;
        }
        $this->ajaxReturn($data);		 
	 }
	
	
	/*
	 * 商品详情主石副石价格接口
	 * zhy
	 * 2016年5月30日 14:19:09
	 */
	public function get_prince(){	
		$gid 	 	= I('post.goodsid');			//商品ID
        $galid   	= I('post.material_id');		//材质ID
		$materialId = I('post.tid');				//副石ID
        $gadId 		= I('post.deputystoneId','0');
        $goods_type = I('post.goods_type',4);
        $hand       = I('post.hand','0');		//手寸
        $hand1      = I('post.hand1','0');
        $M          = D('Common/Goods');
        $info       = $M -> get_info($gid,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" '); 
        $data       = array('status'=>0);
        if( !$info['price_model'] ){
            list($material,$associateInfo,$luozuan,$deputystone) = $M -> getJingGongShiData($gid,$materialId,$galid,$gadId);
            $info['goods_price']                                 = $M -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
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
        $info                                                = getGoodsListPrice($info, $_SESSION['app']['uid'], 'consignment', 'single');
        $marketPrice                                         = $info['marketPrice'];
		if(in_array($info['activity_status'],array('0','1'))){
			$price	= $info['activity_price']; 
		}else{
			$price	= $info['goods_price'];
		}
        $data       = array('status'=>1,'price'=>$price,'marketPrice'=>$marketPrice,'luozuan_price'=>$luozuan['price']);
        $this->ajaxReturn($data);
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
        $data = array('status'=>1,'data'=>$data,'data1'=>$data1);
        $this -> ajaxReturn($data);
    }	
	
	
	/*
	 * 通过商品ID获取商品SKU并组合数组
	 * 定制-戒托
	 * zhy
	 * 2016年5月27日 16:34:21
	 */
	public function getJietuo($goods_id){
		$product                     = D('Common/Goods') -> get_info($goods_id,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
		if($product){	
			$product['images']= D('Common/Goods')->get_goods_images($goods_id);
			if($product['images']){
				$product['big_path'] = $product['images'][0]['big_path'];
				$product['bigImg']   = $product['images'][0];
			}else{
				$bigImg['big_path']  = "/Uploads/product/nopic.png";
				$product['big_path'] = "/Uploads/product/nopic.png";
				$product['bigImg']   = $bigImg;
			}
			$product['content']      = html_entity_decode($product['content']);
			//$product                          = getGoodsListPrice($product, $_SESSION['app']['uid'], 'consignment', 'single');		
			$product['market_price']          = formatRound($product['goods_price']*1.5,2);
			$product['associate_info']        = $this->getTraderAssociateInfoByGid($goods_id);
			$product['associate_luozuan']     = $this->getTraderAssociateLuozuan($goods_id);
			$product['associate_deputystone'] = $this->getTraderDeputystone($goods_id);
			return $product;
		}else{
			$this->redirect('/goods/custom');
		}
	}
	
	public function getTraderAssociateInfoByGid($gid){
		$join = " LEFT JOIN zm_goods_material AS GM ON GM.material_id=GAI.material_id";
		$where = " GAI.goods_id=$gid";
		$goodsAssociateInfo = M("goods_associate_info")->alias("GAI")->join($join)->where($where)->select();
		if($goodsAssociateInfo){
			foreach($goodsAssociateInfo as $key=>$val){
				$goodsAssociateInfo[$key]['price'] = formatRound((float)$val['weights_name']*(100+(float)$val['loss_name'])/100*C("gold_price") * (C("consignment_advantage")/100+1),2);                
			}
		}
		return $goodsAssociateInfo;
	}
	public function getTraderAssociateLuozuan($gid){
		//$materialId = M('goods_associate_luozuan')->where('goods_id='.$gid.' and agent_id = '.C('agent_id'))->getField('material_id');
		$materialId = M('goods_associate_luozuan')->where('goods_id='.$gid)->getField('material_id');
		$where = "1=1";
		$where .= " AND GSL.goods_id=$gid AND material_id='$materialId'";
		$join  = " LEFT JOIN zm_goods_luozuan_shape AS GLS ON GLS.shape_id = GSL.shape_id";
		$associateLuozuan = M("goods_associate_luozuan")->alias("GSL")->join($join)->where($where)->select();
		return $associateLuozuan;
	}

	public function getTraderDeputystone($gid){
		return M("goods_associate_deputystone")->where("goods_id=$gid and (agent_id = ".get_parent_id().' or agent_id = '.C('agent_id').')')->select();
	}
	
	
	
	/*
	 * 通过商品ID获取商品SKU并组合数组
	 * 成品-珠宝
	 * zhy
	 * 2016年5月27日 16:34:21
	 */
	public function getProduct($goods_id){
        $M       = D('Common/Goods');
		$product = $M ->get_info($goods_id,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
		if($product){
			$product['images']= $M->get_goods_images($goods_id);
			if($product['images']){
				$product['big_path'] = $product['images'][0]['big_path'];
			}else{
				$bigImg['big_path'] = "/Uploads/product/nopic.png";
				$product['bigImg'] = $bigImg;
			}
			$product['content']      = html_entity_decode($product['content']);
			$product['attributes']   = $this->getGoodsAttributes(3,$goods_id);
			$product                = getGoodsListPrice($product, $_SESSION['app']['uid'], 'consignment','single');
			$product['market_price'] = formatRound($product['goods_price']*1.5,2);
			$product['similar']      = $this->getSimilarProduct($goods_id);
			return $product;
		}else{
			$this->redirect('/Goods');
		}
	}

public function getGoodsAttributes($goods_type,$goods_id){
	$join1 = " LEFT JOIN zm_goods_attributes ZGA ON ZGA.attr_id=GAA.attr_id";
	$join2 = " LEFT JOIN zm_goods_attributes_value ZGAV ON ZGAV.attr_id=GAA.attr_id AND ZGAV.attr_code&GAA.attr_code";
	$fieldToShow = "GAA.goods_attr_id, GAA.category_id, GAA.goods_id, GAA.attr_id, ZGAV.attr_code, GAA.attr_value, ZGA.attr_name, ZGA.input_type, ZGA.input_mode, ZGA.is_filter, ZGAV.attr_value_id, ZGAV.attr_value_name";
	$data = M("goods_associate_attributes")->alias("GAA")->join($join1)->join($join2)->field($fieldToShow)->where("GAA.goods_id=$goods_id")->select();
	return $data;
	}
public function getSimilarProduct($gid){
       $similar = D('Common/Goods') -> getGoodsSku("$gid",'','list');
         if($similar){
            foreach($similar as $key=>$val){
                $attr      = explode("^",$val['attributes']);
                if($attr){
                    $attrs = "";
                    foreach($attr as $k=>$v){
                        $temp             = explode(":",$v);
                        if($temp){
                            $inputType    = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                            if($inputType == 3){ //如果是手填值,直接显示该值
                                $attrs    .= $temp[1];
                            } else {  //如果不是手填值,则查出对应的属性值
                                $attrs    .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                            }
                            $attrs        .= " ";
                        }
                        $similar[$key]['attrs']   = trim($attrs);
                    }
                    $val['goods_type']            = '3';  //告诉是成品或定制
                    $jiadian_val                  = getGoodsListPrice($val, $_SESSION['app']['uid'], 'consignment', 'single');
                    $similar[$key]['goods_price'] = $jiadian_val['goods_price'];
                    $similar[$key]['marketPrice'] = $jiadian_val['marketPrice'];
                }
            }
         }
	 return $similar;
	}

	/**
     * 品牌系列	通过条件寻找分类系列9宫格
	 * zhy
	 * 2016年5月24日 10:03:57
     */
	public function prepareHtmlByCat($categoryId, $goodsType, $attrType){
        if($goodsType==3){
            $this->cat_show = 1;    //表示是珠宝成品
            $this->cid   = $categoryId;
            $goods_seriesM       = M('goods_series');
            $goods_series_array  = $goods_seriesM ->where("agent_id in ('".C('agent_id')."','0')")->select();
            if(empty($goods_series_array)){
                $this->goods_series_array = null;
            }else{
                $this->goods_series_array = $goods_series_array;
            }
        }else{
            $this->gcid  = $categoryId;
            $this->cid  = $categoryId;
        }
        $this->tid       = C("agent_id");
        $this->goodsType = $goodsType;
        return $this;
    }
		
	/**
     * 当没有传递过来的ID时候执行
	 * zhy
	 * 2016年5月24日 10:03:57
     */
    public function prepareHtmlByDefault($goodsType, $attrType){
        $categoryId = M('goods_category_config')->where("agent_id in ('".C('agent_id')."','0') AND pid<>0 AND is_show=1")->order('sort_id')->getField("category_id");
        $this->prepareHtmlByCat($categoryId, $goodsType, $attrType);
    }
	

	 //获取顶级分类ID标识
	public function getCatPid($categoryId){
        return M('goods_category')->where('category_id="'.$categoryId.'"')->getField('pid');
    }
	
    /**
     * 商品分类数组祛重
	 * zhy
	 * 2016年5月23日 15:19:58
    */
	protected function _arrayRecursive($data,$id,$pid,$objId=0,$isKey='0'){
      $list = array();
      foreach ( $data AS $key => $val ){
          if($val[$pid]   == $objId){
              $val['sub'] = self::_arrayRecursive($data, $id, $pid, $val[$id],$isKey);
              if(empty($val['sub'])){unset($val['sub']);}
              if($isKey) $list[]    = $val;
              else $list[$val[$id]] = $val;
          }
      }
      return $list;
	}
	
	/**
	 * auth	：fangkai
	 * content：商品搜索
	 * time	：2016-8-4
	**/
	public function search(){
		$this->display();
	}
	/**
	 * auth	：fangkai
	 * content：商品搜索列表页
	 * time	：2016-8-4
	**/
	public function searchList(){
		$keyword = I('keyword');
		
		if(!empty($keyword)){
			//如果存在裸钻，则跳转到裸钻页面
			$data = D("GoodsLuozuan")->where("(certificate_number like '%".$keyword."%' OR goods_name like '%".$keyword."%')")->limit(1)->select();
			if($data){
				$this -> redirect("/LuoZuan/luoZuan/?goods_sn=$keyword");
				exit;
			}
						
			$products = $this->getProductList($keyword,1,10);
			if($products){
				session('GOODS_SEARCHLIST_KEYWORD_FOR', $keyword);
				$this->assign('keyword',$keyword);
				$this->assign('products',$products);
			}
			$this->display();
		}else{
			$this->redirect("/Goods/search");
		}	
	}
	/**
	 * auth	：fangkai
	 * content：商品搜索列表页分页ajax
	 * time	：2016-8-4
	**/
	public function ajax_searchList(){
		if(IS_AJAX){
			$keyword = I('keyword');
			$page = I('page',1,'intval');
			$pagesize = I('post.pagesize',10,'intval');
			if($keyword){
				$products = $this->getProductList($keyword,$page,$pagesize);
				$result['status'] = 100;
				$result['data']	  = $products;
				$result['msg']	  = '获取成功';
			}else{
				$result['status'] = 0;
				$result['msg']	  = '获取失败';
			}
			$this->ajaxReturn($result);
		}		
	}
	/**
	 * auth	：fangkai
	 * content：获取商品列表的公共方法
	 * time	：2016-8-4
	**/
	public function getProductList($keyword,$page=1,$pagesize=10){
        $M        = D('Common/Goods');
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
        $count    = $M -> get_count();
		if($keyword)	$M  -> seachr($keyword);
		else return false;
		$M      -> _order('goods_id', 'ASC');
        $p        = new \Think\AjaxPage($count,$page,"setPage");
        $M       -> _limit($page,$pagesize);
		$products = $M->getList();
		if($products){
						$products  = $M -> getProductListAfterAddPoint($products,$_SESSION['app']['uid']);
		}
		
		return $products;
	}

 
}
