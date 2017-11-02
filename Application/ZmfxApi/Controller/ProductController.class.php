<?php
namespace Mobile\Controller;
use Common\Model\Product;

class ProductController extends MobileController{

	public function category(){
		$per           = I('per',100,'intval');
		$order         = I('order','category_id','trim');
		$desc          = I('desc','desc','trim');
		$productM      = new GoodsCategoryModel();
		$productM     -> order($order, $desc);
		$data['total'] = $productM->get_count();
		$productM     -> limit($per);
		$data['per']   = $per;
		$data['ret']   = 100;
		$data['msg']   = '获取成功';
		$data['data']  = $productM->getUserGoodsCategoryList();
		echo json_encode($data);
	}

	public function series(){
		$p             = I('p',1,'intval');
		$per           = I('per',20,'intval');
		$productM      = new GoodsSeriesModel();
		$data['total'] = $productM -> get_count();
		$productM     -> limit( $per );
		$data['per']   = $per;
		$data          = array();
		$data['ret']   = 100;
		$data['msg']   = '获取成功';
		$data['data']  = $productM -> getList();
		echo json_encode($data);
	}

	private function get_jingongshi_default_id($gid,$materialId=0,$luozuanId=0,$deputystoneId=0){
		//取得一个默认金工石价格
		if( $materialId  == 0){
			$materialId  = M("goods_material") -> join('join zm_goods_associate_info on zm_goods_associate_info.material_id = zm_goods_material.material_id') -> where("agent_id=".C("agent_id")) -> limit(1)
				-> order("material_id ASC") -> getField('material_id');
		}
		//取得一个默认匹配材质黄金关联的钻石
		if($luozuanId    == 0){
			$luozuanId   = M("goods_associate_luozuan") -> where(" goods_id=$gid AND material_id=$materialId".' and agent_id = '.C('agent_id')) -> limit(1)
				-> order('gal_id ASC') -> getField('gal_id');
		}
		//取得一个默认附石
		if($deputystoneId  == 0){
			$deputystoneId = M("goods_associate_deputystone") -> where("goods_id=$gid and agent_id = ".C('agent_id')) -> limit(1)
				-> order('gad_id ASC') -> getField('gad_id');
		}
		return array($materialId,$luozuanId,$deputystoneId);
	}
	//金工石各项数据
	private function get_jingongshi_data($gid,$materialId=0,$luozuanId=0,$deputystoneId=0){
		$material      = null; // 金价
		$associateInfo = null; // 工费
		$luozuan       = null; // 主石
		$deputystone   = null; // 副石

		if($materialId > 0) {
			//取得金价
			$material = M("goods_material")->where(" material_id = $materialId and agent_id = " . C('agent_id'))->find();
		}
		if($materialId > 0 && $gid > 0) {
			//取得基础工费
			$associateInfo = M("goods_associate_info")->where(" goods_id= $gid AND material_id = $materialId ")
				->order('goods_info_id ASC')->find();
		}
		if($luozuanId > 0) {
			//取得主石工费
			$luozuan = M("goods_associate_luozuan")->where(" gal_id=$luozuanId and agent_id = " . C('agent_id'))->find();
		}
		/* 以上三个属性是互相关联的*/
		if($deputystoneId){
			$deputystone = M("goods_associate_deputystone") -> where("gad_id=$deputystoneId") -> find();
			if(empty($deputystone)){
				$deputystone['deputystone_price'] = 0;
			}
		}
		return array($material,$associateInfo,$luozuan,$deputystone);
	}
	//定制产品价格计算
	private function get_jingongshi_price($material,$associateInfo,$luozuan,$deputystone){
		//价格计算
		$price = formatRound($associateInfo['weights_name']*(1+$associateInfo['loss_name']/100)*$material['gold_price']+$luozuan['price']+$deputystone['deputystone_price']+$associateInfo['basic_cost'],2);
		if( $this -> parent_id > 1 && C('agent_id') !=  $luozuan['agent_id'] ){
			$price = formatRound($price*(1+C("consignment_advantage")/100),2);
		}
		return $price;
	}

	//获取价格
	private function calculatePrice($gid,$materialId=0,$luozuanId=0,$deputystoneId=0){
		list($materialId,$luozuanId,$deputystoneId)          = $this->get_jingongshi_default_id($gid,$materialId,$luozuanId,$deputystoneId);
		list($material,$associateInfo,$luozuan,$deputystone) = $this->get_jingongshi_data($gid,$materialId,$luozuanId,$deputystoneId);
		$price  = $this->get_jingongshi_price($material,$associateInfo,$luozuan,$deputystone);
		return $price;
	}




	//产品定制获取价格，根据主石，金重，工费，附石计算而来
	public function getGoodsAssociatePrice(){
		$gid           = I("goods_id",0,'intval');//商品id
		$materialId    = I("material_id",0);       //金价
		$luozuanId     = I("diamond_id",0);        //裸钻id
		$gadId         = I("gad_id");              //副石id
		$price         = $this->calculatePrice($gid,$materialId,$luozuanId,$gadId);
		$data['price'] = $price;
		$data['ret']   = 100;
		$data['msg']   = '获取成功';
		echo json_encode($data);die;
	}

	/**
	 * 产品可查讯的属性
	 * @param int $attributes
	 */
	public function attributes(){

		$goods_type    =  I('goods_type', 3,'intval');
		$category_id   =  I('category_id',0,'intval');

		$productM      =  new GoodsCategoryModel();
		$productM      -> category_id($category_id);
		$category_info =  $productM  -> getInfo();
		if(empty($category_info['pid'])){
			$productM                -> parent_category_id($category_id);
			$category_id_itemarray   =  $productM -> getChildUserGoodsCategoryList();
			if($category_id_itemarray){
				$category_id_itemstr =  implode(',', $category_id_itemarray);
				$category_id         =  $category_id_itemstr.','.$category_id_itemstr;
			}else{
				$category_id = $category_id;
			}
		}else{
			$category_id     = $category_id;
		}
		$type                = 1;
		if( $goods_type == 3 ){
			$type = 1;
		}elseif( $goods_type == 4 ){
			$type = 3;
		}

		//获取栏目已有属性
		$goods_category_attributes = M("goods_category_attributes")->where("category_id in ($category_id) and type = $type and agent_id = ".C('agent_id'))->groupby('attr_id')->getField('attr_id');
		if($goods_category_attributes){
			$goods_category_attributes_itemstr =implode(',',$goods_category_attributes);
		}else{
			$res['ret']  = 100;
			$res['msg']  = '获取成功';
			$res['data'] = new stdclass();//空类型
			echo json_encode($res);die;
		}

		//哪些属性加入筛选
		$goods_attributes     = M("goods_attributes")  -> where('attr_id in ('.$goods_category_attributes_itemstr.') and is_filter = 1') -> getField('attr_id,attr_name');
		//判断goods_attrcontorl有没有值
		$attrcontorl          = M("goods_attrcontorl") -> where('attr_id in ('.$goods_category_attributes_itemstr.') and agent_id = '.C('agent_id')) -> select();
		if($attrcontorl){
			$goods_attributes = M("goods_attrcontorl") -> join('join zm_goods_attributes on zm_goods_attributes.attr_id = zm_goods_attrcontorl.attr_id')
				-> where('zm_goods_attrcontorl.attr_id in ('.$goods_category_attributes_itemstr.')  and zm_goods_attrcontorl.is_filter = 1 and zm_goods_attrcontorl.agent_id = '.C('agent_id'))
				-> field('zm_goods_attributes.*') -> select();
		}
		if($goods_attributes){
			//取出参与排序属性的值
			$attr             = array();
			foreach($goods_attributes as $row){
				$attr[]       = $row['attr_id'];
			}
			$attr_itemstr     = implode(',',$attr);
			$attr_value_array = M("goods_attributes_value") -> where('attr_id in ('.$attr_itemstr.') ') -> select();
			$attr_value       = array();
			foreach($attr_value_array as $row){
				unset($row['agent_id']);
				$attr_value[$row['attr_id']][] = $row;
			}
		}else{
			$res['ret']  = 100;
			$res['msg']  = '获取成功';
			$res['data'] = new stdclass();//空类型
			echo json_encode($res);die;
		}

		foreach($goods_attributes as &$row){
			if($attr_value[$row['attr_id']]){
				$row['value'] = $attr_value[$row['attr_id']];
			}
		}
		$res['ret']  = 100;
		$res['msg']  = '获取成功';
		$res['data'] = $goods_attributes;
		echo json_encode($res);die;
	}

	/**
	 * 产品可查讯的属性
	 * @param int $attributes
	 */
	public function goods_list(){

		$p                 = I('p',1,'intval');
		$per               = I('per',20,'intval');
		$order             = I('order','category_id','trim');
		$desc              = I('desc','desc','trim');

		$goods_type        = I('goods_type',4,'intval');
		$category_id       = I('category_id',0,'intval');
		$seachr            = I('seachr','','trim');

		//规则:使用冒号，逗号，分号分别隔开多个属性和多个属性的值, 例如:attr_id:attr_value,attr_value,attr_value;attr_id:attr_value,attr_value...
		$goods_attr_filter = I('goods_attr_filter','','trim');
		$productM          = new GoodsModel();

		//对属性查询进行处理
		if($goods_attr_filter) {
			$goods_attr_filter_join_array = array();
			if( strpos($goods_attr_filter,';') !== false ){
				$goods_attr_filter_array   = explode(';',$goods_attr_filter);
			}else{
				$goods_attr_filter_array[] = $goods_attr_filter;
			}
			foreach($goods_attr_filter_array as $row){
				if( strpos($row,':') !== false ) {
					$data['ret']   = 0;
					$data['msg']   = 'goods_attr_filter参数格式错误';
					$data['data']  = new \stdClass();
					echo json_encode($data);die;
				}
				$attr_value_array = explode(':',$row);
				$on_attrcode      = array();
				if( strpos($attr_value_array[1],',') !== false ){
					$_attr_value_array = explode(',',$attr_value_array[1]);
					foreach($_attr_value_array as $r) {
						$on_attrcode[] = " zm_goods_associate_attributes.attr_code&$r ";
					}
				}else{
					$on_attrcode[]     = " zm_goods_associate_attributes.attr_code&$attr_value_array[1] ";
				}
				$on_attrcode_or                 = implode(' or ',$on_attrcode);
				$goods_attr_filter_join_array[] = " ( zm_goods_associate_attributes.attr_id = $attr_value_array[0] and ( $on_attrcode_or ) )";
			}
			$goods_attr_filter_join = ' join zm_goods_associate_attributes on '.implode(' and ',$goods_attr_filter_join_array);
			$productM  -> join($goods_attr_filter_join);
		}

		$productM      -> goods_type($goods_type);
		if($category_id){
			$productM  -> category_id($category_id);
		}
		if($seachr){
			$productM  -> seachr($seachr);
		}

		$data['total'] =  $productM->get_count();
		$productM      -> order($order, $desc);
		$productM      -> limit($per);
		$goods_list    =  $productM->getList();

		if($goods_list) {
			$goods_id_itemarray = array();
			foreach($goods_list as $row){
				$goods_id_itemarray[] = $row['goods_id'];
			}
			$goods_id_itemstr = implode(',',$goods_id_itemarray);

			//金工石和成品属性加价处理
			if ( $goods_type == 3 ) {

				//成品
				$_goods_sku   = M("goods_sku")  -> where('goods_id in ('.$goods_id_itemstr.') and agent_id = '.C('agent_id'))
					-> group('goods_id,sku_id DESC') -> select();//每个产品取一条默认sku数据
				if($_goods_sku) {
					$goods_sku = array();
					foreach ($_goods_sku as $row) {
						//解析goods_sku的attributes字段
						$attributes = $row['attributes'];
						if (strpos($attributes, '^') !== false) {
							$attributes_array = explode('^', $attributes);
						} else {
							$attributes_array[] = $attributes;
						}
						foreach ($attributes_array as $row) {
							$attr_array = explode(':', $row);
							$attrs[]    = M("goods_attributes")->where(' attr_id = ' . $attr_array[0])->find('attr_name');
						}
						unset($row['agent_id'], $row['attributes']);
						$row['attributes']           = $attrs;
						$goods_sku[$row['goods_id']] = $row;
					}
					foreach($goods_list as &$row){
						if( !empty($goods_sku[$row['goods_id']]) ){
							$row['goods_sku_default'] = $goods_sku[$row['goods_id']];
							$row['goods_price']       = $goods_sku[$row['goods_id']]['goods_price'];
						}
					}
				}
			} else if ( $goods_type == 4 ) {
				//定制,附加默认价格
				foreach($goods_list as &$row) {
					list($materialId, $luozuanId, $deputystoneId)           = $this -> get_jingongshi_default_id($row['goods_id'], 0, 0, 0);
					list($material, $associateInfo, $luozuan, $deputystone) = $this -> get_jingongshi_data($row['goods_id'], $materialId, $luozuanId, $deputystoneId);
					$row['material']      = $material;
					$row['associateInfo'] = $associateInfo;
					$row['luozuan']       = $luozuan;
					$row['deputystone']   = $deputystone;
					$row['price']         = $this -> get_jingongshi_price($material, $associateInfo, $luozuan, $deputystone);
				}
			}
		}

		$data          = array();
		$data['per']   = $per;
		$data['ret']   = 100;
		$data['data']  = $goods_list;
		$data['msg']   = '获取成功';
		echo json_encode($data);die;
	}

	/**
	 * 产品信息页面
	 * @param int $goods_id
	 * @param int $type
	 */
	public function goods_info(){
		$goods_id             = I("goods_id",0,'intval');
		$type                 = I("type",0,'intval');
		$data                 = array();
		$goodsInfo            = getGoodsInfo($goods_id,$type,$this->domain);//珠宝信息
		$goodsInfo['content'] = html_entity_decode($goodsInfo['content']);
		$data['goodsInfo']    = $goodsInfo;
		$data['goodsImages']  = getGoodsImages($goods_id,$type,$this->domain);//珠宝缩略图
		//获取规格数据或者金工石数据
		if($goodsInfo['goods_type'] == 3 or $goodsInfo['goods_type'] == 6){
			$goodsSpec         = getGoodsSpec($goods_id,'',$this->domain);
			$data['goodsSpec'] = $goodsSpec['data'];
		}elseif ($goodsInfo['goods_type'] == 4 or $goodsInfo['goods_type'] == 5){
			$data['goodsJgs']              = getGoodsJGS($goods_id,$type,$this->domain);
			if(!$data['goodsJgs']['luozuan'][0]['gal_id']){$data['error'] = '本产品没有主石匹配，请修改产品数据';}
		}
		//获取属性参数数据
		$this->attr  = getGoodsAttr($goods_id,$type,$this->domain);
		$res['ret']  = 100;
		$res['msg']  = '获取成功';
		$res['data'] = $data;
		echo json_encode($res);die;
	}
}