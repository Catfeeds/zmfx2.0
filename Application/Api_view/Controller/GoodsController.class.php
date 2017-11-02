<?php
namespace Api_view\Controller;
class GoodsController extends Api_viewController{

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
        $uid        = C('uid');		
        $info       = $M -> get_info($gid,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" '); 
        $data       = array('status'=>0);
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
		$goodsInfo['productService'] = M("article")->where(" is_show = 1 AND title = '产品服务' and agent_id in ('".C('agent_id')."','0')")->find();
		$goodsInfo['payment']        = M("article")->where(" is_show = 1 AND title = '支付配送' and agent_id in ('".C('agent_id')."','0')")->find();
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
		for($i=6;$i<=25;$i++){	 
            $hand[] = $i;
        }
		$this->hand = $hand;		//手寸,尺寸从最小的6，到最大的40.
		$this->adjustToShow($goodsInfo);
		$goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goods_type);

		if( $goodsInfo['goods_type'] == '4' ){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
        }else{
            $sd_list       = '';

			if(isset($goodsInfo['similar'])){
				$attrs 			= array();
				$attrs_size		= array();
				$attrs_weight	= array();

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
 				$goodsInfo['similar_19']=count($attrs_size)		? array_unique($attrs_size) 	: null;		//尺寸
				$goodsInfo['similar_18']=count($attrs_weight) 	? array_unique($attrs_weight) 	: null;		//金重
				$goodsInfo['similar_16']=count($attrs)			? array_unique($attrs) 			: null;		//材质
			}			
        }
 
		$this->goodsInfo           		= $goodsInfo;
        $this -> sd_list       			= $sd_list;
		$this ->CartGoodsNumber       	=parent::GetCartGoodsNumber();
		$_SESSION['stopgap_tip_for_goodsinfo']='';	
		$this->display();
	}
	
	
	
 
	
    /**
     * 商品评论
     * zhy	find404@foxmail.com
     * 2017年1月13日 15:43:04
     */
    public function getAjaxComment(){
        $n        = 20;
        $p        = 1;
        $gid      = I("gid");
		$oe		  =	M("order_eval");
        $where    = 'oe.goods_id='.$gid.' and oe.status = 1';
		$field	  = 'oe.*,u.userimage';
		$join[]   = 'LEFT JOIN `zm_user` AS u ON u.uid = oe.uid';		
        $count    = $oe->alias('oe')->where($where)->field($field)->join($join)->count();
        $page     = new \Think\AjaxPage($count,$n,'setPage');
        $data 	  = $oe->alias('oe')->where($where)->limit($page->firstRow.",".$page->listRows)->field($field)->join($join)->select();
        if($data){
            foreach($data as $key=>$val){
                $data[$key]['createtime'] = date("Y-m-d H:i:s",$val['createtime']);
            }
        }
        $this->ajaxReturn($data);
    }
	
	public function goodsComment(){
        $this->goods_id     = I("goods_id");
		$this->display();
	}
	
	
	
    /**
     * 商品评论
     * zhy	find404@foxmail.com
     * 2017年1月13日 15:43:04
     */
    public function goodsContent(){
		$goods_id=I("get.goods_id");
        $M          = D('Common/Goods');
		$product    = $M -> get_info($goods_id);
        $this->content = $product['content'];
		$this->display();
    }	

	
    /**
     * 商品评论
     * zhy	find404@foxmail.com
     * 2017年1月13日 15:43:04
     */
    public function goodsSku(){
		$goods_id=I("get.goods_id");
        $M          = D('Common/Goods');
		$product    = $M -> get_info($goods_id);
		$goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$uid);		
        $this->goodsInfo = $goodsInfo;
		$this->display();
    }	
	
	
}
