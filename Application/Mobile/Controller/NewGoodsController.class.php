<?php
/**
 * Created by PhpStorm.
 * User: Kwan Wong
 * Date: 2017/7/27
 * Time: 10:08
 */
namespace Mobile\Controller;

class NewGoodsController extends NewMobileController{

    private $agentId;
    private $uid;

    public function __construct()
    {
        parent::__construct();
        $this->agentId = C('agent_id');
        $this->uid = $_SESSION['m']['uid'];
    }

    /**
     * 珠宝定制页面
     *
     * @author wangkun
     * @date 2017/7/27
     */
    public function goodsDiy()
    {
        // 珠宝定制广告位
        $templateAdModel = M('template_ad');

        $goodsDiyAd = $templateAdModel->where([
            'agent_id' => $this->agentId,
            'status'   => 1,
            'ads_id'   => 54,
        ])->order('sort ASC')->find();

        $categoryId = I('gcid', 60, 'intval');
        // 定制产品
        $goodsType = 4;

        $categoryList =  D('Common/GoodsCategory')->this_product_category($categoryId);

        $this->assign('categoryList', $categoryList);
        $this->assign('goodsDiyAd', $goodsDiyAd);

        $this->assign('title', '珠宝定制');
        $this->assign('categoryId', $categoryId);
        $this->assign('goodsType', $goodsType);
        $this->assign('agentId', $this->agentId);

        $this->display();
    }

    /**
     * 产品详情页(定制|成品)
     * 
     * @author wangkun
     * @date 2017/8/1
     */
    public function detail()
    {
        $goodsModel = D('Common/Goods');
        $historyModel = D('Common/History');

        // 产品ID及产品类型
        $gid = I("gid",'0');
        $goodsType = I('goods_type','3');

        $field = 'g.*, ga.product_status as activity_status';
        $session_id = session_id();

        // 预计交货日期
        $expectedDeliveryTime = C('expected_delivery_time');

        // 后台权限部分
        if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
            $sell_status=' and sell_status in(0,1)' ;
        }else{
            $sell_status=' and sell_status = 1' ;
        }

        // 产品原始信息
        $goodsInfo = $goodsModel->get_info($gid, 0, 'shop_agent_id= '.$this->agentId.$sell_status, '', $field);
        // 获取产品加点后信息及附加信息
        $goodsInfo = $goodsModel->getProductInfoAfterAddPoint($goodsInfo, $this->uid);

        if(!$goodsInfo || $gid <0){
            $this->error("您所访问的页面不存在");
        }

        $goodsHistorys = $historyModel->getHistoryLists([
            'uid'        => $this->uid,
            'agent_id'   => $this->agentId,
            'gid'        => $gid,
            'session_id' => $session_id,
            'limit'      =>'0,5'
        ]);

        $goodsHistorys = !empty($goodsHistorys) && is_array($goodsHistorys['lists']) ? $goodsHistorys['lists'] : [];

        // 获取分类别名
        $goodsInfo['category_name'] = M('goods_category_config')->where([
            'category_id' => $goodsInfo['category_id'],
            'agent_id'    => $this->agentId
        ])->getField('name_alias');

        // 获取分类名称
        $categoryName = M('goods_category')->where([
            'category_id' => $goodsInfo['category_id']
        ])->getField('category_name');

        // 格式化部分产品数据
        $this->adjustToShow($goodsInfo);

        // 判断分类是否显示手寸
        $goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goodsType);

        // 预计收货时间
        $goodsInfo['expected_delivery_time'] = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? $expectedDeliveryTime : 15;

        //是否收藏
        $goodsInfo['collection_id'] = '0';
        $goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/shoucang.png';
        if($this->uid > 0){
            $goodsCollection = M('collection')->where([
                'uid'        => $this->uid,
                'goods_id'   => $gid,
                'goods_type' => $goodsType,
                'agent_id'   => $this->agentId,
            ])->find();

            if(!empty($goodsCollection)){
                $goodsInfo['collection_id'] = $goodsCollection['id'];
                $goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
            }
        }

        // 商品详情通用部分
        $publicContent = "";
        if( $goodsInfo['category_id'] && $goodsInfo['goods_type'] ){
            $info = M('goods_category_config')->where([
                'category_id' => $goodsInfo['category_id'],
                'agent_id'    => $this->agentId,
            ])->find();
            if(!empty($info) && is_array($info)){
                if($goodsInfo['goods_type'] == '3'){
                    $publicContent = $info['public_goods_desc_chengpin_down'];
                }else if($goodsInfo['goods_type'] == '4'){
                    $publicContent = $info['public_goods_desc_dingzhi_down'];
                }
            }
        }

        // 获取定制产品个性符号及手寸
        if($goodsInfo['goods_type'] == '4'){
            $symbolDesignList = D('Common/SymbolDesign')->getList($this->agentId);
            $handSize = implode(',', range(6, 25));
        }else{
            $symbolDesignList = '';
        }

        if($goodsInfo['price_model'] && InStringByLikeSearch($this->category_name, array('对戒'))){
            $productType = 'coupleRing';
            $sizeLabel = '手寸(男戒)';
        }else if($goodsInfo['price_model'] && (InStringByLikeSearch($this->category_name, array('钻戒','戒指')))){
            $productType = 'ring';
            $sizeLabel = '手寸';
        }else if($goodsInfo['price_model'] && (InStringByLikeSearch($this->category_name,array('项链','手链')))){
            $productType = 'necklace';
            $sizeLabel = '尺寸';
        }else{
            $productType = 'other';
            $sizeLabel = '尺寸';
        }
		//裸钻信息
		$dingzhi = (array)json_decode($_COOKIE["dingzhi"]);
		$luozuanInfo = (array)$dingzhi["luozuan_info"];
       	$this->assign('luozuanInfo',$luozuanInfo);
		//匹配信息
		if($goodsInfo["activity_status"]=='0' || $goodsInfo["activity_status"]=='1'){
			$jietuoArr["goods_price"] 	= $goodsInfo["activity_price"]; 
		}else{
			$goodsPrice 	= $goodsInfo["goods_price"]; 
		}
		
		
		
		$video        = $goodsModel -> get_goods_videos($gid);
        $this->assign('video',$video);
        $this->assign('goodsPrice',$goodsPrice);

        $this->assign('title', '商品详情');
        $this->assign('handSize', $handSize);
        $this->assign('categoryName', $categoryName);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('productType', $productType);
        $this->assign('sizeLabel', $sizeLabel);
        $this->assign('publicContent', $publicContent);
        $this->assign('cartNumber', $this->cartNumber);

        $this->assign('goodsHistorys', $goodsHistorys);
        $this->assign('symbolDesignList', $symbolDesignList);

        $this -> display();
    }

    /**
     * 根据材质获取主石列表
     *
     * @author wangkun
     * @date 2017/8/1
     *
     * @return json 主石列表
     */
    public function getAssociateLuozuanByMaterialId(){
        $gid              = I("gid", 0);
        $material_id      = I("material_id", 0);
        $M                = D('Common/Goods');
        $info             = $M -> calculationPoint($gid);
        $associateLuozuan = $M -> getGoodsAssociateLuozuanAfterAddPoint(" goods_id = $gid AND zm_goods_associate_luozuan.material_id = $material_id ", 'list');
        if($associateLuozuan){
            $data['data']   = $associateLuozuan;
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
            $data['data']   = array();
        }
        $this->ajaxReturn($data);
    }

    public function getAssociateLuozuanPriceByGalid(){
        $gid        = I("gid",0);
        $galid      = I("galid","0");
        $gadId      = I("deputystoneId","0");
        $materialId = I('materialId','0');
        $hand       = I('hand','0');
        $hand1      = I('hand1','0');

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
        $info                                                = getGoodsListPrice($info, $_SESSION['web']['uid'], 'consignment', 'single');
        $marketPrice                                         = $info['marketPrice'];
        //如果为活动商品，则为活动价格，否则为正常售卖价格
        if(in_array($info['activity_status'],array('0','1'))){
            $price = $info['activity_price'];
        }else{
            $price = $info['goods_price'];
        }
        $price     = $price;
        $data      = array('status'=>1,'price'=>$price,'marketPrice'=>$marketPrice);
        $this -> ajaxReturn($data);
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

    public function getRecCategory($categoryId, $str, $type=3){
        $M= M("goods_category_config");
        $temp = $M->where("category_id='".$categoryId."' and agent_id = ".C('agent_id'))->find();
        if($temp){
            if($type == 3){
                $temp_str = "&gt;<a href='/Home/Goods/goodsCat/cid/".$categoryId."'>".$temp['name_alias']."</a>".$str;
            }else{
                $temp_str = "&gt;<a href='/Home/Goods/goodsCategory/gcid/".$categoryId."'>".$temp['name_alias']."</a>".$str;
            }
            if($temp['pid'] != 0){
                return $this->getRecCategory($temp['pid'],$temp_str, $type);
            }else{
                return $temp_str;
            }
        }else{
            return $str;
        }
    }

    /**
     * 格式化产品属性
     * 某些属性值要加上单位在前台显示，如：材质金重：10G,20;某些多选属性的值要合并成一条记录在前端显示，如：材质颜色：白色 黄色  玫瑰金
     *
     * @author wangkun
     * @date 2017/8/1
     * @param $goodsInfo 产品信息数组
     */
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

    /**
     * 产品可筛选的属性列表
     *
     * @author wangkun
     * @date 2017/7/27
     *
     * @return json JSON格式属性列表
     */
    public function goodsAttributes(){
        $goodsType  = I('goods_type', 3, 'intval');
        $categoryId = I('category_id', 0, 'intval');

        $goodsCategoryModel = D('GoodsCategory');
        if(!empty($categoryId)){
            $goodsCategoryModel->category_id($categoryId);

            $categoryInfo = $goodsCategoryModel->getInfo();
            if(empty($categoryInfo['pid'])){
                $goodsCategoryModel->parent_category_id($categoryId);
                $categoryIds = $goodsCategoryModel->getChildUserGoodsCategoryList();
                if($categoryIds){
                    $categoryId = implode(',', $categoryIds);
                }
            }
        }else{
            $goodsCategoryModel->parent_category_id(0);
            $categoryIds = $goodsCategoryModel->getChildUserGoodsCategoryList();
            if($categoryIds){
                $categoryId = implode(',', $categoryIds);
                $goodsCategoryModel->parent_category_id($categoryId);
                $categoryIds   = $goodsCategoryModel->getChildUserGoodsCategoryList();
                if($categoryIds){
                    $categoryId = implode(',', $categoryIds);
                }
            }
            $categoryId = implode(',', $categoryIds);
        }

        $type = 1;
        if($goodsType == 3){
            $type = 1;
        }elseif($goodsType == 4){
            $type = 3;
        }

        //获取栏目已有属性
        $goods_category_attributes = M("goods_category_attributes")->where("category_id in ($categoryId) and type = $type ")->group('attr_id')->getField('attr_id',true);


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
        $this -> echoJson($goods_attributes);
    }

    /**
     * 添加单位
     *
     * @author wangkun
     * @date 2017/8/3
     * @param $value
     * @param $id
     * @return string
     */
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

    /**
     * 获取产品评论列表
     *
     * @author wangkun
     * @date 2017/8/1
     *
     * @return json 评论列表
     */
    public function getAjaxComment(){
        $page   = I("page_id", 1, 'intval');
        $pageSize = I("page_size", 100, 'intval');
        $gid = I("gid",0,'intval');
        $where = "goods_id=".$gid;
        if(empty($page)){
            $page = 1;
        }
        $limit = (($page-1)*$pageSize).','.$pageSize;
        $count = M("order_eval")->where($where)->count();
        $data['data'] = M("order_eval")->where($where)->limit($limit)->select();
        $data['count'] = $count;
        $this->ajaxReturn($data);
    }

    /**
     * 获取产品列表数据
     *
     * @author wangkun
     * @date 2017/7/27
     *
     * @return json JSON格式产品列表
     */
    public function goodsData()
    {
        $p                 = I('page_id',1,'intval');
        $per               = I('page_size',20,'intval');
        $order             = I('order','goods_id','trim');
        $desc              = I('desc','desc','trim');

        $goods_type        = I('goods_type',0,'intval');
        $category_id       = I('category_id',0,'intval');
        $seachr            = I('keyword','','trim');
        $goods_series_id   = I('goods_series_id','','trim');

        $shape             = I('shape','','trim');
        $weight            = I('weight','','trim');
        $Preferential      = I('Preferential','','trim');
        $price		  		= I('price','','trim');

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
                    $on_attrcode[]              = " aa$key.attr_code & $attr_value_array[1] ";
                }
                $on_attrcode_or                 = implode(' or ',$on_attrcode);
                $goods_attr_filter_join        .= " join zm_goods_associate_attributes aa$key on aa$key.attr_id = $attr_value_array[0] and ( $on_attrcode_or ) and aa$key.goods_id = zm_goods.goods_id ";
                if( $key > 0 ){
                    $key_jian = $key - 1 ;
                    $goods_attr_filter_join .= " and aa$key_jian.goods_id = aa$key.goods_id ";
                }
            }
            $productM -> _join ( $goods_attr_filter_join );
        }

        if($goods_type && !$Preferential){
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
                $shape_id  = M('goods_luozuan_shape')->where(" shape = '$shape' ")->getField('shape_id');
                $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where $weight_where and shape_id = '$shape_id' and agent_id in ($agent_id) ) " , true );
            } else {
                if( $shape ){
                    $shape_id  = M('goods_luozuan_shape')->where(" shape = '$shape' ")->getField('shape_id');
                    $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where shape_id = '$shape_id' and agent_id in ($agent_id) )" , true );
                } else {
                    $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where $weight_where and agent_id in ($agent_id) )" , true );
                }
            }
        }
        if($price){
            $price		= explode('-',$price);
            $price[0]	= intval($price[0]);
            $price[1]	= intval($price[1]);
            $productM  -> set_where( 'zm_goods.goods_price', " between  $price[0] and $price[1] " , true );         //价格区间判断
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

        if($goods_list){
            $goods_list = $productM -> getProductListAfterAddPoint($goods_list,$_SESSION['m']['uid']);
        }

        $data              = array();
        $data['page_size'] = $per;
        $data['page_id']   = $p;
        $data['data']      = $goods_list;
        $data['total']     = $productM -> count;
        $this             -> echoJson( $data );
    }

    /**
     * 添加收藏
     *
     * @author wangkun
     * @date 2017/8/3
     *
     * @return json 操作结果
     */
    public function addToCollection(){
        // $type_from代表来源(1:裸钻 2:散货 3:成品 4:定制)
        $type_from = I('type_from',0,'intval');
        $param = array();
        $continue = true;
        switch($type_from){
            case 1:
                $param = array(
                    'gid' =>I("gid", 0),
                    'goods_type'=>I('goods_type',0)
                );
                break;
            case 2:
                $param = array(
                    'gid' =>I("gid", 0),
                    'goods_type'=>I('goods_type',0)
                );
                break;
            case 3:
                $param = array(
                    'gid'           => I("gid", 0),
                    'goods_type'    => 3,
                    'id_type'       => I('id_type', ''),
                    'sku_sn'    => I("sku_sn",0),
                    'goods_number'     => I("goods_number",1,'intval'),
                );
                break;
            case 4:
                $param = array(
                    'gid'           => I("gid", 0),
                    'goods_type'    => 4,
                    'id_type'       => I('id_type', ''),
                    'materialId'    => I("materialId", 0),
                    'diamondId'     => I("diamondId", 0),
                    'deputystoneId' => I("deputystoneId", 0),
                    'hand'          => I("hand", ""),
                    'word'          => I("word", ""),
                    'sd_id'         => I('sd_id', ''),
                    'goods_number'  => I("goods_number", 1),
                    'luozuan'       => I("luozuan", 0),
                    'word1'         => I("word1", ""),
                    'hand1'         => I("hand1", ""),
                    'sd_id1'        => I("sd_id1", "")
                );
                break;
            default:
                $continue = false;
                break;
        }
        if($continue){
            $param['uid'] = $this->uid;
            $result = D('Common/Collection')->addCollection($param);
        }else{
            $result = array(
                'info'   => '操作失败',
                'status' => 0
            );
        }
        $this->ajaxReturn($result);
    }

    /**
     * 删除收藏
     *
     * @author wangkun
     * @date 2017/8/3
     *
     * @return json 操作结果
     */
    public function deleteCollection(){
        $data = [
            'status' => 0,
            'msg'    => '删除失败',
        ];
        $id_arr = I("id",0);
        if(!empty($id_arr)){
            if(is_array($id_arr)){
                $delete_where = 'id in ("'.implode('","',$id_arr).'")';
            }else{
                $delete_where = ['id' => $id_arr];
            }

            if(M('collection')->where($delete_where)->delete()){
                $data['status'] = 100;
                $data['msg'] = "删除成功";
            }
        }
        $this->ajaxReturn($data);
    }
	
    /**
     * 产品分类
     * zhy	find404@foxmail.com
     * 2017年8月8日 15:02:11
     */
    public function category(){
		$this -> publicdataList			=  D('Common/GoodsCategory') ->this_product_category();
		$this -> display();
	}
    /**
     * 品牌系列
     * zhy	find404@foxmail.com
     * 2017年8月8日 15:02:11
     */
    public function brand(){
		$this -> publicdataList			=  M('goods_series') ->where("agent_id in ('".C('agent_id')."','0')")->select();
		$this -> display();
	}
	
	
    /**
     * 产品分类，品牌系列 子类列表
     * zhy	find404@foxmail.com
     * 2017年8月8日 15:02:11
     */
    public function cbList(){
		if(is_numeric($_GET['category_id'])){
			$model = 'goods_category_config';
			$where = ' category_id = '.$_GET['category_id'];
			$Field = 'name_alias';
		}

		if(is_numeric($_GET['goods_series_id'])){
			$model = 'goods_series';
			$where = ' goods_series_id = '.$_GET['goods_series_id'];
			$Field = 'series_name';
		}
		
		if($model){
			$title = M($model)->where($where.' and agent_id = '.C('agent_id'))->getField($Field);
			if($title){
				$this->title = $title;
			}else{
				redirect('/Goods/category.html');
			}
		}else{
				redirect('/Goods/category.html');
		}
		
		$this -> display();
	}	
	
}