<?php

namespace Common\Model\Dingzhi\Szzmzb;
use Think\Model;

class Goods extends Model{

	public $szzmzb_table;

	public function __construct(){
		$this->szzmzb_table = [
			'zm_goods_luozuan_shape'=>					'zm_szzmzb_goods_luozuan_shape',
			'zm_banfang_goods'=>						'zm_szzmzb_goods',
			'zm_banfang_category'=>						'zm_szzmzb_category',
			'zm_banfang_goods_images'=>					'zm_szzmzb_goods_images',
			'zm_banfang_goods_file'=>					'zm_szzmzb_goods_file',
			'zm_banfang_goods_associate_attr'=>			'zm_szzmzb_goods_associate_attr',
			'zm_banfang_goods_associate_luozuan'=>		'zm_szzmzb_goods_associate_luozuan',
			'zm_banfang_attach_process_fee'=>			'zm_szzmzb_attach_process_fee',
			'zm_banfang_attach_process_items'=>			'zm_szzmzb_attach_process_items',
			'zm_banfang_attr'=>							'zm_szzmzb_attr',
			'zm_banfang_base_process_fee'=>				'zm_szzmzb_base_process_fee',
			'zm_banfang_base_process_items'=>			'zm_szzmzb_base_process_items',
			'zm_banfang_category_jewelry'=>				'zm_szzmzb_category_jewelry',
			'zm_banfang_goods_associate_deputystone'=>	'zm_szzmzb_goods_associate_deputystone',
			'zm_banfang_material'=>						'zm_szzmzb_material',
			
			'goods_luozuan_shape'=>						'szzmzb_goods_luozuan_shape',
			'banfang_goods'=>							'szzmzb_goods',
			'banfang_category'=>						'szzmzb_category',
			'banfang_goods_images'=>					'szzmzb_goods_images',
			'banfang_goods_file'=>						'szzmzb_goods_file',
			'banfang_goods_associate_attr'=>			'szzmzb_goods_associate_attr',
			'banfang_goods_associate_luozuan'=>			'szzmzb_goods_associate_luozuan',
			'banfang_attach_process_fee'=>				'szzmzb_attach_process_fee',
			'banfang_attach_process_items'=>			'szzmzb_attach_process_items',
			'banfang_attr'=>							'szzmzb_attr',
			'banfang_base_process_fee'=>				'szzmzb_base_process_fee',
			'banfang_base_process_items'=>				'szzmzb_base_process_items',
			'banfang_category_jewelry'=>				'szzmzb_category_jewelry',
			'banfang_goods_associate_deputystone'=>		'szzmzb_goods_associate_deputystone',
			'banfang_material'=>						'szzmzb_material',
		];
	}

	/**
     * 获取板房商品ID
     * @param $goods_id 商品表ID
     * @return number or NULL
     */
	public function get_goods_id($goods_id){
		if($goods_id>0){
			return M('goods')->where(['goods_id'=>$goods_id,'banfang_goods_id'=>['gt',0]])->getField('banfang_goods_id');
		}
	}

	/**
     * 获取板房商品详情页数据
     * @param $dingzhi_goods_id 商品表ID $activity_status商品活动
     * @return array
     */
	public function get_dinzhi_goodsInfo($dingzhi_goods_id,$activity_status){
        $data['url'] = C('ZMALL_URL').'/Home/Zmfxapi/Api_get_goodsInfo.html';
        $data['not_json'] = 1;
        $data['data']['dingzhi_goods_id'] = $dingzhi_goods_id;
        $data['data']['activity_status'] = $activity_status;
        $data['data']['post'] = I();
        $data = _httpsRequest($data);
        $return = json_decode($data,true);

        if($return['status']!=100){ //获取数据失败
            return false;
        }

        $goodsInfo = $return['data']['goodsInfo'];
        $price = $return['data']['price'];

        $goods_price = 0;
        $activity_price = 0;
        foreach ($price as $k => $v) {
            //每个商品加点的价
            $goodsInfo['goods_price'] = $v['price'];
            $goodsInfo['goods_type'] = 16;
            $goodsInfo = getGoodsListPrice($goodsInfo,$_SESSION['web']['uid'],'szzmzb','single');
            $goodsInfo['selected']['goods_price_one'][$k] = $goodsInfo['goods_price']; //存储加点过后的价
            $goodsInfo['selected']['activity_goods_price_one'][$k] = $goodsInfo['activity_price']; //存储加点过后的活动价
            if($goodsInfo['selected']['checked'][$k] == 1){  //选中
                $goods_price += $goodsInfo['goods_price'];
                $activity_price += $goodsInfo['activity_price'];
            }
        }
        $goodsInfo['goods_price'] = $goods_price; //总价
        $goodsInfo['activity_price'] = $activity_price; //活动总价
		return $goodsInfo;
	}

    /**
     * 获取板房商品详情页数据
     * @param $dingzhi_goods_id 商品表ID $activity_status商品活动
     * @return array
     */
    // public function get_dinzhi_goodsInfo($dingzhi_goods_id,$activity_status){
    //     $BF = new \Common\Model\Dingzhi\Szzmzb\BanfangGoods();
    //     $goodsInfo = $BF->getInfo($dingzhi_goods_id, ['sell_status' => 1]);
    //     $goodsInfo['activity_status']   = $activity_status;
    //     $goodsInfo['materialPrice']     = $this->getMaterialPrice();                                                //黄金，铂金价格
    //     $getMaterialList                = $this->getMaterialList($goodsInfo['material_match_type']);                //材质列表
    //     $colourSeparation               = $this->getColourSeparation($dingzhi_goods_id);    
    //     $goodsInfo['materialList']      = array_merge($getMaterialList,$colourSeparation);

    //     $deputystone_type_show = [
    //         1=>'钻石',
    //         2=>'彩宝',
    //         3=>'合成立方氧化锆',
    //     ];
    //     $deputystone_attribute_show = [
    //         [
    //             'SAGH',
    //             'SBGH',
    //         ],
    //         '具体价格以报价单为准',
    //         '具体价格以报价单为准',
    //     ];
    //     $goodsInfo['deputystone_type_show'] = $deputystone_type_show;
    //     $goodsInfo['deputystone_attribute_show'] = $deputystone_attribute_show;

    //     $post = I();
    //     $data['dingzhi_goods_id'] = $dingzhi_goods_id;
    //     $data['material_id']    = $post['material_id']>0?$post['material_id']:$goodsInfo['materialList'][0]['material_id'];
    //     $data['gb']                     = I('gb',''); //国标字印
    //     $selected = $data;
    //     // 默认值
    //     foreach ($goodsInfo['associate_luozuan_data_all'] as $key => $item) {
    //         //选中 checked
    //         $selected['checked'][$key] = isset($post['checked'][$key])?$post['checked'][$key]:1;
    //         $selected['associate_luozuan_id'][$key] = $post['associate_luozuan_id'][$key]?$post['associate_luozuan_id'][$key]:$item[0]['associate_luozuan_id'];
    //         $selected['deputystone_type'][$key] = $post['deputystone_type'][$key]?$post['deputystone_type'][$key]:1;
    //         $selected['deputystone_attribute'][$key] = $post['deputystone_attribute'][$key]?$post['deputystone_attribute'][$key]:'SAGH';
    //         $selected['technology'][$key] = 1;
    //         $selected['luozuan_weight'][$key] = $post['luozuan_weight'][$key]?$post['luozuan_weight'][$key]:$item[0]['luozuan_weight'];
    //         $selected['luozuan_number'][$key] = $post['luozuan_number'][$key]?$post['luozuan_number'][$key]:$item[0]['luozuan_number'];
    //         $selected['goods_number'][$key] = $post['goods_number'][$key]?$post['goods_number'][$key]:'1';
    //         $selected['hand'][$key] = $post['hand'][$key]>=6&&$post['hand'][$key]<=27?$post['hand'][$key]:'6';
    //         $selected['word'][$key] = $post['word'][$key]?$post['word'][$key]:'';

    //         foreach ($item as $k => $v) {
    //             if($v['luozuan_number'] == 0){
    //                 break;
    //             }
    //             if($goodsInfo['is_luozuan'] == 1){
    //                 $ct_mm = 'CT';
    //                 $deputystone_weight = is_array($v['deputystone'][0]) ? $v['deputystone'][0]['deputystone_weight'] : 0;
    //             }else{
    //                 $ct_mm = 'mm';
    //                 $deputystone_weight = is_array($v['deputystone'][0]) ? $v['deputystone'][0]['deputystone_size'] : 0;
    //             }
                
    //             $goodsInfo['associate_luozuan_data_all'][$key][$k]['ct_mm'] = $ct_mm;
                
    //             if($selected['associate_luozuan_id'][$key] == $v['associate_luozuan_id']){ //主石选中
    //                 $deputystonePrice = $this->getDeputystonePrice($deputystone_weight, $goodsInfo['is_luozuan']);  //根据副石重量获取相关副石价格
    //                 if($selected['deputystone_attribute'][$key] == 'SAGH'){
    //                     $sagh_sbgh_price = 'sagh_price';
    //                 }else{
    //                     $sagh_sbgh_price = 'sbgh_price';
    //                 }
    //                 if($selected['deputystone_type'][$key] !=1){ //彩宝 合成立方氧化锆
    //                     $selected['deputystone_name'][$key] = $deputystone_type_show[$selected['deputystone_type'][$key]];
    //                     $selected['deputystone_number'][$key] = '';
    //                     $selected['deputystone_weight'][$key] = '';
    //                 }else{ //钻石
    //                     $selected['deputystone_name'][$key] = $selected['deputystone_attribute'][$key];
    //                     $selected['deputystone_number'][$key] = $v['deputystone'][0]['deputystone_number'];
    //                     $selected['deputystone_weight'][$key] = $v['deputystone'][0]['deputystone_weight'];
    //                 }

    //                 $selected['sagh_sbgh_price'][$key] = $deputystonePrice[$sagh_sbgh_price];
    //                 //板房计算石重
    //                 $material = $this->get_selected($goodsInfo['materialList'],['material_id',$data['material_id']]);
    //                 $material_weight = $this->materialWeight($v['material_weight'],$material['material_type']);
    //                 $selected['shape'][$key] = $v['shape'];
    //                 $selected['shape_name'][$key] = $v['shape_name'];
    //                 $selected['ct_mm'][$key] = $ct_mm;
    //                 $selected['material_weight'][$key] = $material_weight;
    //                 $selected['material_name'][$key] = $material['material_name'];
    //                 $selected['associate_luozuan_gal_id'][$key] = $v['associate_luozuan_id'];
    //                 $selected['associate_luozuan_weights_name'][$key] = $v['luozuan_size'];
    //             }

    //             if($goodsInfo['is_couple_ring'] == 1){ //情侣对戒
    //                 if($v['luozuan_sort'] == 0){
    //                     $selected['goods_name_bm'][$v['luozuan_sort']] = '男戒';
    //                 }else{
    //                     $selected['goods_name_bm'][$v['luozuan_sort']] = '女戒';
    //                 }
    //             }else if($goodsInfo['is_combination_ring'] == 1){ //组合
    //                 if($v['luozuan_sort'] == 0){
    //                     $selected['goods_name_bm'][$v['luozuan_sort']] = '主款';
    //                 }else{
    //                     $selected['goods_name_bm'][$v['luozuan_sort']] = '副款'.$v['luozuan_sort'];
    //                 }
    //             }
    //         }
    //     }

    //     $data['associate_luozuan_id']   = implode($selected['associate_luozuan_id'], ',');
    //     $data['deputystone_type']       = implode($selected['deputystone_type'], ',');
    //     $data['deputystone_attribute']  = implode($selected['deputystone_attribute'], ',');
    //     $data['technology']             = implode($selected['technology'], ',');
    //     $data['luozuan_weight']         = implode($selected['luozuan_weight'], ',');
    //     $data['luozuan_number']         = implode($selected['luozuan_number'], ',');
    //     $data['hand']                   = implode($selected['hand'], ',');
    //     $data['word']                   = implode($selected['word'], ',');

    //     $price = $this->getNewestPrice($data);
    //     $goods_price = 0;
    //     $activity_price = 0;
    //     foreach ($price as $k => $v) {
    //         //每个商品加点的价
    //         $goodsInfo['goods_price'] = $v['price'];
    //         $goodsInfo['goods_type'] = 16;
    //         $goodsInfo = getGoodsListPrice($goodsInfo,$_SESSION['web']['uid'],'szzmzb','single');
    //         $selected['goods_price_one'][$k] = $goodsInfo['goods_price']; //存储加点过后的价
    //         $selected['activity_goods_price_one'][$k] = $goodsInfo['activity_price']; //存储加点过后的活动价
    //         if($selected['checked'][$k] == 1){  //选中
    //             $goods_price += $goodsInfo['goods_price'];
    //             $activity_price += $goodsInfo['activity_price'];
    //         }
    //     }
    //     $goodsInfo['goods_price'] = $goods_price; //总价
    //     $goodsInfo['activity_price'] = $activity_price; //活动总价
    //     $goodsInfo['selected'] = $selected;
    //     return $goodsInfo;
    // }

    /**
     * 购物车价格更新  活动更新
     * @param 'goods_attr'(json) 'goods_id'(int)
     * @return array
     */
    function dinzhi_szzmzb_cart_price($goods_attr,$goods_id){
        $M          = D('Common/Goods');
        $field      = 'g.*,ga.product_status as activity_status';
        $agent_id = C('agent_id');

        //后台权限部分
        if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
            $sell_status=' and sell_status in(0,1)' ;
        }else{
            $sell_status=' and sell_status = 1' ;
        }
        $product    = $M -> get_info($goods_id,0,'shop_agent_id= '.C('agent_id').$sell_status,'',$field);
        $goodsInfo = unserialize($goods_attr);

        $goodsInfo['activity_status']   = $product['activity_status']; //活动更新
        $data['dingzhi_goods_id']       = $goodsInfo['selected']['dingzhi_goods_id'];
        $data['material_id']            = $goodsInfo['selected']['material_id'];
        $data['associate_luozuan_id']   = $goodsInfo['selected']['associate_luozuan_id'];
        $data['deputystone_type']       = $goodsInfo['selected']['deputystone_type'];
        $data['deputystone_attribute']  = $goodsInfo['selected']['deputystone_attribute'];
        $data['technology']             = $goodsInfo['selected']['technology'];
        $data['luozuan_weight']         = $goodsInfo['selected']['luozuan_weight'];
        $data['luozuan_number']         = $goodsInfo['selected']['luozuan_number'];
        $data['hand']                   = $goodsInfo['selected']['hand'];
        $data['word']                   = $goodsInfo['selected']['word'];

        $get_data['url'] = C('ZMALL_URL').'/Home/Zmfxapi/Api_getNewestPrice.html';
        $get_data['not_json'] = 1;
        $get_data['data']['data'] = $data;
        $data = _httpsRequest($get_data);
        $data = json_decode($data,true);
        $price = $data['data']['price'];

        if($price[0]['price']>0){
            //商品加点的价
            $goodsInfo['goods_price'] = $price[0]['price'];
            $goodsInfo['goods_type'] = 16;
            $goodsInfo = getGoodsListPrice($goodsInfo,$_SESSION['web']['uid'],'szzmzb','single');
            return serialize($goodsInfo);
        }else{
            return false;
        }
    }

    /**
     * 后台采购单调用价格更新  活动更新 
     * @param 'attribute'(json) 'goods_id'(int)
     * @return array
     */
    function admin_dinzhi_szzmzb_cart_price($goods_attr,$goods_id){
        $M          = D('Common/Goods');
        $field      = 'g.*,ga.product_status as activity_status';
        $agent_id = C('agent_id');

        //后台权限部分
        if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
            $sell_status=' and sell_status in(0,1)' ;
        }else{
            $sell_status=' and sell_status = 1' ;
        }
        $product    = $M -> get_info($goods_id,0,'shop_agent_id= '.C('agent_id').$sell_status,'',$field);
        $goodsInfo = unserialize($goods_attr);

        $goodsInfo['activity_status']   = $product['activity_status']; //活动更新
        $data['dingzhi_goods_id']       = $goodsInfo['selected']['dingzhi_goods_id'];
        $data['material_id']            = $goodsInfo['selected']['material_id'];
        $data['associate_luozuan_id']   = $goodsInfo['selected']['associate_luozuan_id'];
        $data['deputystone_type']       = $goodsInfo['selected']['deputystone_type'];
        $data['deputystone_attribute']  = $goodsInfo['selected']['deputystone_attribute'];
        $data['technology']             = $goodsInfo['selected']['technology'];
        $data['luozuan_weight']         = $goodsInfo['selected']['luozuan_weight'];
        $data['luozuan_number']         = $goodsInfo['selected']['luozuan_number'];
        $data['hand']                   = $goodsInfo['selected']['hand'];
        $data['word']                   = $goodsInfo['selected']['word'];

        $get_data['url'] = C('ZMALL_URL').'/Home/Zmfxapi/Api_getNewestPrice.html';
        $get_data['not_json'] = 1;
        $get_data['data']['data'] = $data;
        $data = _httpsRequest($get_data);
        $data = json_decode($data,true);
        $price = $data['data']['price'];

        if($price[0]['price']>0){
            //商品加点的价
            $goodsInfo['goods_price'] = $price[0]['price'];
            $goodsInfo['goods_type'] = 16;
            $product['dinzhi_goodsInfo'] = getCaigouGoodsListPrice($goodsInfo,0,'consignment_szzmzb','single');
            return $product;
        }else{
            return false;
        }
    }

    function get_selected($data,$kv){
        foreach ($data as $k => $v) {
            if($kv[1]){
                if($v[$kv[0]]==$kv[1]){
                    return $v;
                }
            }else{
                return $v;
            }        
        }
    }

    /**
     * 根据不同材质，返回材质重量
     *
     * @param float weight 重量
     * @param int   type   类型
     *
     * @return float
     */
    function materialWeight($weight, $type)
    {
        if ($type == '1') {
            //铂金
            return number_format($weight*1.31,2);
        } else if ($type == '3') {
            //银
            return number_format($weight*0.7,2);
        } else {
            //金
            return number_format($weight,2);
        }
    }

    /**
     * auth ：fangkai
     * content：获取最新价格
     * time ：2017-1-7
    **/
    public function getNewestPrice($data){
            $newestPrice = [];
            $BF             = new \Common\Model\Dingzhi\Szzmzb\BanfangGoods();

            $goods_id       = $data['dingzhi_goods_id'];
            $material_id    = $data['material_id'];
            // 以下参数可能有多个值，使用逗号分隔 wangkun 2017/8/23
            $associate_luozuan_id   = $data['associate_luozuan_id'];
            $deputystone_type       = $data['deputystone_type'];
            $deputystone_attribute  = $data['deputystone_attribute'];
            $technology             = $data['technology'];
            $luozuan_weight         = $data['luozuan_weight'];
            $luozuan_number         = $data['luozuan_number'];

            if(empty($goods_id)){
                // $result['code'] = 101;
                // $result['msg']  = '该商品不存在';
                // $this->ajaxReturn($result);
                return false;
            }

            $associate_luozuan_ids = explode(',', $associate_luozuan_id);
            $deputystone_types  = explode(',', $deputystone_type);
            $deputystone_attributes = explode(',', $deputystone_attribute);
            $technologies = explode(',', $technology);
            $luozuan_weights = explode(',', $luozuan_weight);
            $luozuan_numbers = explode(',', $luozuan_number);

            foreach ($associate_luozuan_ids as $k => $item) {

                $associate_luozuan_id = $item;

                // 裸钻ID为空时，不计算裸钻价格 wangkun 2017/8/31
                if($associate_luozuan_id == ''){
                    continue;
                }

                $luozuan_weight = isset($luozuan_weights[$k]) ? $luozuan_weights[$k] : 0;
                $luozuan_number = isset($luozuan_numbers[$k]) ? $luozuan_numbers[$k] : 0;

                if($luozuan_weight && $luozuan_number && $associate_luozuan_id == 0){
                    // 自定义主石获取价格
                    $associate_luozuan_info['luozuan_weight']   = $luozuan_weight;
                    $associate_luozuan_info['luozuan_number']   = $luozuan_number;
                    if(!isset($deputystone_type[$k]) || $deputystone_type[$k] !== 1){
                        $deputystone_attribute = '';
                    }
                    // 计算后的价格
                    $newestPrice[] = $BF->getGoodsPriceInfoByCustom($goods_id,$material_id,$associate_luozuan_info,$deputystone_attribute,$technology);
                }else{
                    // 固定主石获取价格
                    $deputystone_attribute = isset($deputystone_attributes[$k]) ? $deputystone_attributes[$k] : '';
                    if(!isset($deputystone_types[$k]) || $deputystone_types[$k] != 1){
                        $deputystone_attribute = '';
                    }
                    $technology = isset($technologies[$k]) ? $technologies[$k] : 0;
                    $newestPrice[] = $BF->getGoodsPriceInfo($goods_id, $material_id, $associate_luozuan_id, $deputystone_attribute, $technology);
                
                }
            }
            // $result['code'] = 100;
            // $result['msg']  = '获取成功';
            // $result['data'] = $newestPrice;
            return $newestPrice;
    }

	/**
	 * auth	：fangkai
	 * content：获取黄金，铂金价格
	 * time	：2016-12-28
	**/
    public function getMaterialPrice(){
		$GMaterial	= M('szzmzb_goods_material');
        //添加银材质 luohaitao 20170824
		$materialPrice		= $GMaterial->where(array('parent_type'=>1,'parent_id'=>0,'material_name'=>array('in',array('白18K金','PT950','S925'))))->select();
		
		return $materialPrice;
		
	}

	//材料：   material_info,       包含重量和单价
    //基本工费:base_process_info:   包含损耗，和工费
    //附加工费:attach_process_info: 会有多项，每行一个，涉及到对应的属性，有工费和损耗两项
    //主石信息:luozuan_info         包含保险费和数量
    //副石信息:deputystone_info     会有多项，每行一个，包含价格和数量
    //副石镶嵌费:weixiang_info      包含单位费用
    //公式: 金料费用(重量 X 金价) + 金料损耗费(重量 X 金价 X 损耗) + 款式的基本工费 + 附加工艺费 + 主石保险费 + 副石费用（ 单价 X 数量 ） + 副石的镶嵌费（ 单价费用 X 数量 ） 
    public function calculatePrice($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info){

        $price      = 0;
        //金料价格
        $price      = $material_info['material_weight'] * $material_info['gold_price'];
        $price     += $base_process_info['fee_price'];
        //基本损耗
        $extra_loss = $base_process_info['extra_loss'];
        //附加工费
        if($attach_process_info){
            foreach($attach_process_info as $row){
                $price      += $row['fee_price'];
                $extra_loss += $row['extra_loss'];
            }
        }
        //工耗
        $price += $material_info['material_weight'] * $material_info['gold_price'] * $extra_loss / 100 ;
        //主石保险费
        $price += $luozuan_info['fee_price']        * $luozuan_info['luozuan_number'];
        //echo $luozuan_info['fee_price']             * $luozuan_info['luozuan_number'] . ',';
        //副石
        if( $deputystone_info ){
            foreach($deputystone_info as $row){
                $price += $row['fee_price']           * $row['deputystone_weight'] * $row['deputystone_number']; //副石费
                $price += $weixiang_info['fee_price'] * $row['deputystone_number']; //副石镶嵌费
            }
        }
        return round($price,2);
    }

    //获取价格相关参数
    public function getPriceParam($goods_id,$goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone,$deputystone_attribute='',$technology='1'){

        $goods_info          = $this -> where( " goods_id = $goods_id ") -> find();
        $material_info       = array();
        $base_process_info   = array();
        $attach_process_item = array();
        $luozuan_info        = array();
        $deputystone_item    = array();
        $price_info          = array(); //封装价格信息



        $goodsAttrObj = M('szzmzb_goods_associate_attr');
        $BBP          = new \Common\Model\Dingzhi\Szzmzb\BanfangBaseProcess();
        $BAP          = new \Common\Model\Dingzhi\Szzmzb\BanfangAttachProcess();

        //材质和价格
        $material_info['gold_price']      = $goods_material_info['gold_price'];
        $material_info['material_weight'] = $goods_associate_luozuan['material_weight'];

        //基本工费
        $_base_process_info = $BBP -> getBaseProcessInfoByJewelry($goods_info['jewelry_id']);
        if( $_base_process_info ){
            /*if($goods_material_info['material_type'] != '1'){
                //黄金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_good'];
                }
            } else {
                //铂金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_good'];
                }
                //铂金加1.31倍
                $material_info['material_weight']    = $material_info['material_weight'] * 1.31;
            }*/

            //添加银材质 luohaitao 20170823
            if($goods_material_info['material_type'] == '1'){
                //铂金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_good'];
                }

                $material_info['material_weight']    = $material_info['material_weight'] * 1.31;

            } else if ($goods_material_info['material_type'] == '3') {
                //银
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['s925_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['s925_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['s925_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['s925_loss_good'];
                }

                $material_info['material_weight']   = $material_info['material_weight'] * 0.7;

            } else {
                //黄金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_good'];
                }
            }

        } else {
            $_base_process_info['jewelry_name'] = M('banfang_jewelry') -> where(" jewelry_id >= '$goods_info[jewelry_id]' ")-> getField('jewelry_name');
            $base_process_info['fee_price']     = 0;
            $base_process_info['extra_loss']    = 0;
        }

        //附加工费
        $attach_process_total_price = 0;
        $data       = $goodsAttrObj -> where(" goods_id = $goods_id ") -> select();
        $goods_attr = array();
        foreach( $data as $row ){
            if($row['attr_id1']){
                $goods_attr[] = $row['attr_id1'];
            }
            if($row['attr_id2']){
                $goods_attr[] = $row['attr_id2'];
            }
            if($row['attr_id3']){
                $goods_attr[] = $row['attr_id3'];
            }
        }
        if( $goods_attr ){    
            $attach_process_item = $BAP -> getAttachProcessListByAttr($goods_attr);
            if( $attach_process_item ){
                foreach( $attach_process_item as $key => $row ){
                    $attach_process_total_price += $row['fee_price'];
                    $d['attach_item'] = $row['attach_item'];
                    $d['fee_price']   = $row['fee_price'];
                    $d['extra_loss']  = $row['extra_loss'];
                    $attach_process_item[$key] = $d;
                }
            }
        }
        
        //主石信息,当分类为钻石类的时候才计算保险费
        $is_luozuan = M('szzmzb_category') -> where(" category_id = $goods_info[category_id] ") -> getField('is_luozuan');
        if( $is_luozuan ){
            $luozuan_info['fee_price']      = M('szzmzb_luozuan_premiums') ->fetchsql(false)-> where(" max_weight >= '$goods_associate_luozuan[luozuan_weight]' and min_weight <= '$goods_associate_luozuan[luozuan_weight]' ")-> getField('fee_price');
            if( intval($goods_associate_luozuan['luozuan_shape_id']) != 1 ){
                //主石非圆形的时候保险费为100
                $luozuan_info['fee_price']  = 100;
            }
            $luozuan_info['fee_price']      = $luozuan_info['fee_price'] ? $luozuan_info['fee_price'] : 0;
            $luozuan_info['luozuan_number'] = $goods_associate_luozuan['luozuan_number'];
        }else{
            $luozuan_info['fee_price']      = 0;
            $luozuan_info['luozuan_number'] = 0;
        }

        //副石信息
        $deputystone_total_price        = 0;
        if( $goods_associate_deputystone ){
            foreach($goods_associate_deputystone as $row){
                $deputystone_info['deputystone_number'] = $row['deputystone_number'];
                $deputystone_info['deputystone_weight'] = $row['deputystone_weight'];
                $deputystone_data                       = M('banfang_deputystone') -> where(" max_weight >= '$row[deputystone_weight]' and min_weight <= '$row[deputystone_weight]' ") -> find();
                if( $deputystone_data && $deputystone_attribute ){
                    if( $deputystone_attribute          == 'SAGH' ){
                        $deputystone_info['fee_price']  = $deputystone_data['sagh_price'];
                    }else if( $deputystone_attribute    == 'SBGH' ){
                        $deputystone_info['fee_price']  = $deputystone_data['sbgh_price'];
                    }
                }else{
                    $deputystone_info['fee_price']      = 0;
                }
                $deputystone_total_price                += $deputystone_info['fee_price'] * $deputystone_info['deputystone_number'];
                $deputystone_item[]                     = $deputystone_info;
            }
        }

        //得到微镶费的价格
        $weixiang_info                     = $BAP -> getWeixiangInfo();

        //记录价格信息
        $price_info['base_process_info']   = array(
                                                'jewelry_name'=>"$_base_process_info[jewelry_name]",
                                                'fee_price'=>$base_process_info['fee_price'],
                                                'extra_loss'=>$base_process_info['extra_loss']
                                           );
        $price_info['attach_process_item'] = $attach_process_item;
        $price_info['material_info']       = array(
                                                'material_name'=>"$goods_material_info[material_name]",
                                                'gold_price'=>$material_info['gold_price'],
                                                'material_weight'=>$material_info['material_weight']
                                           );
        $price_info['luozuan_info']        = array(
                                                'luozuan_weight'=>"$goods_associate_luozuan[luozuan_weight]",
                                                'fee_price'=>$luozuan_info['fee_price'],
                                                'luozuan_number'=>$luozuan_info['luozuan_number']
                                           ); 
        $price_info['deputystone_item']           = $deputystone_item;
        $price_info['weixiang_info']              = array('attach_item'=>$weixiang_info['attach_item'],'fee_price'=>$weixiang_info['fee_price'] );

        $price_info['attach_process_total_price'] = $attach_process_total_price;
        $price_info['deputystone_total_price']    = $deputystone_total_price;

        return array(
            $material_info,
            $base_process_info,
            $attach_process_item,
            $luozuan_info,
            $deputystone_item,
            $weixiang_info,
            $price_info
        );
    }

    //返回产品价格以及必要信息
    public function getGoodsPriceInfo($goods_id,$material_id=0,$associate_luozuan_id=0,$deputystone_attribute='',$technology='1'){

        $info = array();
        list($goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone) = $this -> getGoodsParam($goods_id,$material_id,$associate_luozuan_id);
        list($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info,$price_info) = $this -> getPriceParam($goods_id,$goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone,$deputystone_attribute,$technology);
        $price = $this -> calculatePrice($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info);
        $info  = array(
            'material_info'         => $goods_material_info,
            'associate_luozuan'     => $goods_associate_luozuan,
            'associate_deputystone' => $goods_associate_deputystone,
            'price_info'            => $price_info,
            'price'                 => $price
        );
        return $info;
    }
    
    // 获取产品相关参数
    public function getGoodsParam($goods_id,$material_id=0,$associate_luozuan_id=0,$goods_deputystone_id=0){

        $materialObj                  = M('szzmzb_material');
        $goodsAssociateLuozuanObj     = M('szzmzb_goods_associate_luozuan');
        $goodsAssociateDeputystoneObj = M('szzmzb_goods_associate_deputystone');

        if(empty($material_id)){
            $material_id              = $materialObj -> order(' material_id asc ') -> getField('material_id');
        }

        if(empty($associate_luozuan_id)){
            $associate_luozuan_id     = $goodsAssociateLuozuanObj -> where(" goods_id = $goods_id ") -> order(' associate_luozuan_id desc ') -> getField('associate_luozuan_id');
        }

        $goods_material_info          = $materialObj -> where(" material_id = $material_id ") -> find();
        $goods_associate_luozuan      = $goodsAssociateLuozuanObj     -> where(" associate_luozuan_id = $associate_luozuan_id ") -> find();
        $goods_associate_luozuan['luozuan_weight_interval'] = $goodsAssociateLuozuanObj -> where(" goods_id = $goods_id ") -> getField(' concat(min(`luozuan_weight`),"-",max(`luozuan_weight`)) as `luozuan_weight_interval` ',false);
        $goods_associate_deputystone  = $goodsAssociateDeputystoneObj -> where(" associate_luozuan_id = $associate_luozuan_id ") -> select();

        return array(
            $goods_material_info,
            $goods_associate_luozuan,
            $goods_associate_deputystone
        );
    }

	/**
	 * auth	：fangkai
	 * content：获取材质列表
	 * time	：2016-12-28
	**/
    public function getMaterialList($material_match_type){
		$BFMaterial	= M('szzmzb_material');
		if($material_match_type == 1){
			$where_bf['material_type']	= array('in',array('0','1', '3'));
		}else{
			$where_bf['material_type']	= array('in',array('0', '3'));
		}
		$materialList		= $BFMaterial->where($where_bf)->select();	//材质列表
		//前台不显示PT900材质  guihongbing 20170725
		foreach ($materialList as $key => $value) {
			if($value['material_name'] == 'PT900'){
				unset($materialList[$key]);
			}
		}
		return $materialList;
		
	}

	/**
	 * auth	：fangkai
	 * content：根据副石重量获取相关副石价格
	 * time	：2016-12-28
	**/
    public function getDeputystonePrice($deputystone_weight,$is_luozuan=1){
		$Deputystone		= M('szzmzb_deputystone');
		if( $deputystone_weight && $is_luozuan == 0 ){
			$GADeputystone		= M('szzmzb_size_weight');
			$deputystone_weight	= $GADeputystone->where($deputystone_weight." >= min_size AND ".$deputystone_weight." < max_size")->getField('weight');
		}
		$deputystonePrice	= 0;
		//用第一个副石重量获取相关副石价格
		if($deputystone_weight){
			$deputystonePrice	= $Deputystone->where($deputystone_weight." >= min_weight AND ".$deputystone_weight." < max_weight")->find();
		}
		
		return $deputystonePrice;
		
	}

	public function getColourSeparation($gid){
        $BM = M('szzmzb_material');
        $BGAA   = M('szzmzb_goods_associate_attr');
        $where['material_items']    = array('neq','');
        $where['goods_id']          = $gid;
        $material_id            = $BGAA->where($where)->getField('material_items');
        $colourSeparation       = array();
        if($material_id){
            //查询分类id,名称，类型  guihongbing 20170719
            $colourSeparation   = $BM->field('material_id,material_name,material_type')->where(array('material_id'=>array('in',$material_id)))->select();
        }
        
        return $colourSeparation;
    }
}
?>
