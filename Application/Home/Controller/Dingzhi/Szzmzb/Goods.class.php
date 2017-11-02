<?php
/**
 * 产品相关页面
 * 产品类型 goods_type  1:裸钻 2:散货 3：成品 4:戒托 5:代销
 */
namespace Home\Controller\Dingzhi\Szzmzb;
use Think\Model;
use Home\Controller\NewHomeController;
class Goods extends NewHomeController{
	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= $_SESSION['web']['uid'];
	}

    //default 商品详情
    public function goodsInfo($dingzhi_goods_id){
        $gid        = I("gid");
        $goods_type = I('goods_type',3);
        $M          = D('Common/Goods');
        $uid = $_SESSION['web']['uid'];
        $agent_id = C('agent_id');
        $session_id = session_id();
        $GH = D('Common/History');

        $expectedDeliveryTime = C('expected_delivery_time');
        
        //后台权限部分
        if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
            $sell_status=' and sell_status   in(0,1)' ;
        }else{
            $sell_status=' and sell_status = 1' ;
        }       
 
        
        $product    = $M -> get_info($gid,0,' shop_agent_id= '.C('agent_id').$sell_status);
        $goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$_SESSION['web']['uid']);
        if(!$goodsInfo || $gid <0){
            $this  -> error("您所访问的页面不存在");
        }

        $goodsInfo['dinzhi_goodsInfo'] = (new \Common\Model\Dingzhi\Szzmzb\Goods())->get_dinzhi_goodsInfo($dingzhi_goods_id,$goodsInfo['activity_status']);
        if(!$goodsInfo['dinzhi_goodsInfo']){
            $this  -> error("您所访问的页面不存在");
        }

        $goodsInfo['productService'] = M("article")->where(" is_show = 1 AND title = '产品服务' and agent_id = ".C('agent_id'))->find();
        $goodsInfo['payment']        = M("article")->where(" is_show = 1 AND title = '支付配送' and agent_id = ".C('agent_id'))->find();
        $this -> goodsHistorys       = '';
        // 将浏览信息记录到  zm_history
        /*$data['goods_id']    = $gid;
        $data['agent_id']    = C('agent_id');
        $data[$this->userAgentKey] = $this->userAgentValue;
        $data['create_time'] = time();
        if( M("history") -> where("goods_id=$gid AND ".$this->userAgentKey."='".$this->userAgentValue."'")->find() ){
            M("history") -> data($data) -> save();
        }else{
            M("history") -> data($data) -> add();
        }*/
        //获取历史记录
        $history_conditions = array(
            'uid'        => $uid,
            'agent_id'   => $agent_id,
            'gid'        => $gid,
            'session_id' => $session_id,
            'limit'      =>'0,5'
        );
        $goodsHistorys = $GH->getHistoryLists($history_conditions);
        $this -> goodsHistorys     = $goodsHistorys['lists'];
        //新增历史记录
        $history_conditions['goods_type'] = $goodsInfo['goods_type'] ? $goodsInfo['goods_type'] : 3;
        $history_add_return = $GH->addHistory($history_conditions);

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
        if($goods_type==3){
            $this -> cat_show = 1;
        }else{
            $this -> cat_show = 0;
        }
        $hand = array();
        for($i=6;$i<=25;$i++){
            $hand[] = $i;
        }
        $this -> hand = $hand;
        $navstr  = "<a href='/Home/Index/index'>首页</a>";
        $navstr .= $this->getRecCategory($goodsInfo['category_id'],"", $goods_type);
        $this -> nav_cate = $navstr;
        $this -> adjustToShow($goodsInfo);//某些属性值要加上单位在前台显示，如：材质金重：10G,20;某些多选属性的值要合并成一条记录在前端显示，如：材质颜色：白色 黄色  玫瑰金
        $goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goods_type);

        $goodsInfo['expected_delivery_time'] = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? $expectedDeliveryTime : 15;

        //是否收藏
        $goodsInfo['collection_id'] = '0';
        $goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/shoucang.png';
        if($_SESSION['web']['uid']>0){
            $collection_where = array(
                'uid'=>$_SESSION['web']['uid'],
                'goods_id'=>$gid,
                'goods_type'=>$goods_type,
                'agent_id'=>C('agent_id')
            );
            $goodsCollection = M('collection')->where($collection_where)->find();
            if(!empty($goodsCollection)){
                $goodsInfo['collection_id']  = $goodsCollection['id'];
                $goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
            }
        }
        //是否收藏
        $this -> goodsInfo = $goodsInfo;
        $this -> bigImg    = $goodsInfo['bigImg'];
        $this -> public_content = "";
        if( $goodsInfo['category_id'] && $goodsInfo['goods_type'] ){
            $agent_id = C('agent_id');
            $info     = M('goods_category_config') -> where(" category_id = $goodsInfo[category_id] and agent_id = $agent_id ") -> find();
            if($info){
                if( $goodsInfo['goods_type'] == '3' ){
                    $this -> public_content   = $info['public_goods_desc_chengpin_down'];
                }else if( $goodsInfo['goods_type'] == '4' ){
                    $this -> public_content   = $info['public_goods_desc_dingzhi_down'];
                }
            }
        }
        if( $goodsInfo['goods_type'] == '4' ){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
        }else{
            $sd_list       = '';
        }
        $this->sd_list = $sd_list;
        
        //视频列表
        $this -> vdesList        = $M -> get_goods_videos($gid);
        
        $this->display('Dingzhi/Szzmzb/Goods/goodsDetails');
    }

    //b2c 商品详情
	public function goodsDetails($dingzhi_goods_id){

		$gid        = I("gid",'0');
		$goods_type = I('goods_type','3');
		$M          = D('Common/Goods');
		$field		= 'g.*,ga.product_status as activity_status';
		$uid = $_SESSION['web']['uid'];
		$agent_id = C('agent_id');
		$session_id = session_id();
		$GH = D('Common/History');
		$expectedDeliveryTime = C('expected_delivery_time');

		//后台权限部分
		if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
			$sell_status=' and sell_status in(0,1)' ;
		}else{
			$sell_status=' and sell_status = 1' ;
		}

        $product    = $M -> get_info($gid,0,'shop_agent_id= '.C('agent_id').$sell_status,'',$field);
		$goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$_SESSION['web']['uid']);
		
        if(!$goodsInfo || $gid <0){
            $this -> error("您所访问的页面不存在");
        }

        $goodsInfo['dinzhi_goodsInfo'] = (new \Common\Model\Dingzhi\Szzmzb\Goods())->get_dinzhi_goodsInfo($dingzhi_goods_id,$goodsInfo['activity_status']);
        if(!$goodsInfo['dinzhi_goodsInfo']){
            $this  -> error("您所访问的页面不存在");
        }

        $goodsInfo['productService'] = M("article") -> where(" is_show = 1 AND title = '产品服务' and agent_id = ".C('agent_id'))->find();
        $goodsInfo['payment']        = M("article") -> where(" is_show = 1 AND title = '支付配送' and agent_id = ".C('agent_id'))->find();

        //取出历史纪录
        /*$goodsHistorys               = M("history") -> field('zm_goods.*, ga.product_status as activity_status ')-> join(' left join zm_goods on zm_history.goods_id = zm_goods.goods_id  left join zm_goods_activity as ga on ga.gid = zm_history.goods_id and ga.agent_id = '.C('agent_id') ) -> where($this->userAgentKey."='".$this->userAgentValue."' and zm_history.goods_id <> '$gid'")->order('history_id desc')->limit('0,5')->select();

        foreach( $goodsHistorys as $key=>$row ){
            if( !empty($row['goods_id']) ){
                $point = $M -> calculationPoint($row['goods_id']);
                if( $point ){
                    $row['goods_price'] = ( 100 + $point ) / 100 * $row['goods_price'];
                }
                $goodsHistorys[$key]    = $M -> completeUrl($row['goods_id'],'goods',$row);
            }else{
                unset($goodsHistorys[$key]);
            }
        }
        $goodsHistorys             = $M -> getProductListAfterAddPoint($goodsHistorys,$_SESSION['web']['uid']);*/
		$history_conditions = array(
				'uid'        => $uid,
				'agent_id'   => $agent_id,
				'gid'        => $gid,
				'session_id' => $session_id,
				'limit'      =>'0,5'
		);
		$goodsHistorys = $GH->getHistoryLists($history_conditions);

        $this -> goodsHistorys     = $goodsHistorys['lists'];
        // 将浏览信息记录到  zm_history
        /*$data                      = array();
        $data['goods_id']          = $gid;
        $data['agent_id']          = C('agent_id');
        $data[$this->userAgentKey] = $this -> userAgentValue;
        $data['create_time']       = time();
        if(M("history")  -> where(" goods_id = $gid AND ".$this->userAgentKey." = '".$this->userAgentValue."' ")->find()){
            M("history") -> data($data) -> save();
        }else{
            M("history") -> data($data) -> add();
        }*/

		$history_conditions['goods_type'] = $goodsInfo['goods_type'] ? $goodsInfo['goods_type'] : 3;
		$history_add_return = $GH->addHistory($history_conditions);
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

        if( $goodsInfo['goods_type'] == 3 ){
            $this -> cat_show = 1;
        } else {
            $this -> cat_show = 0;
        }
        $navstr                     = "<a href='/Home/Index/index'>首页</a>";
        $navstr                    .= $this -> getRecCategory($goodsInfo['category_id'],"", $goods_type);
        $goodsInfo['category_name'] = M('goods_category_config')->where(" category_id = '$goodsInfo[category_id]' and agent_id = ".C('agent_id'))->getField('name_alias');
        $this -> category_name      = M('goods_category')->where(" category_id = '$goodsInfo[category_id]' ")->getField('category_name');
        $this -> nav_cate  = $navstr;
        $this -> adjustToShow( $goodsInfo );//某些属性值要加上单位在前台显示，如：材质金重：10G,20;某些多选属性的值要合并成一条记录在前端显示，如：材质颜色：白色 黄色  玫瑰金
        $goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goods_type);

        $goodsInfo['expected_delivery_time'] = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? $expectedDeliveryTime : 15;

		//是否收藏
		$goodsInfo['collection_id'] = '0';
		$goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/shoucang.png';
		if($_SESSION['web']['uid']>0){
			$collection_where = array(
					'uid'=>$_SESSION['web']['uid'],
					'goods_id'=>$gid,
					'goods_type'=>16,
					'agent_id'=>C('agent_id')
			);
			$goodsCollection = M('collection')->where($collection_where)->find();
			if(!empty($goodsCollection)){
				$goodsInfo['collection_id'] = $goodsCollection['id'];
				$goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
			}
		}
		//是否收藏
		$this -> goodsInfo = $goodsInfo;
        $this -> bigImg    = $goodsInfo['bigImg'];
        $this -> public_content = "";
        if( $goodsInfo['category_id'] && $goodsInfo['goods_type'] ){
            $agent_id = C('agent_id');
            $info     = M('goods_category_config') -> where(" category_id = $goodsInfo[category_id] and agent_id = $agent_id ") -> find();
            if($info){
                if( $goodsInfo['goods_type'] == '3' ){
                    $this -> public_content   = $info['public_goods_desc_chengpin_down'];
                }else if( $goodsInfo['goods_type'] == '4' ){
                    $this -> public_content   = $info['public_goods_desc_dingzhi_down'];
                }
            }
        }
        if( $goodsInfo['goods_type'] == '4' ){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
            $hand = array();
            for($i=6;$i<=25;$i++){
                $hand[] = $i;
            }
            $this -> hand = $hand;
        }else{
            $sd_list       = '';
        }
		//视频列表
	    $this -> vdesList        = $M -> get_goods_videos($gid);
        $this -> sd_list   = $sd_list;
        
        $this -> display('Dingzhi/Szzmzb/Goods/goodsDetails');

	}

    public function get_goods_info($dingzhi_goods_id){
        $gid        = I("gid",'0');
        $goods_type = I('goods_type','3');
        $M          = D('Common/Goods');
        $field      = 'g.*,ga.product_status as activity_status';
        $uid = $_SESSION['web']['uid'];
        $agent_id = C('agent_id');
        $session_id = session_id();
        $GH = D('Common/History');
        $expectedDeliveryTime = C('expected_delivery_time');

        //后台权限部分
        if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
            $sell_status=' and sell_status in(0,1)' ;
        }else{
            $sell_status=' and sell_status = 1' ;
        }

        $product    = $M -> get_info($gid,0,'shop_agent_id= '.C('agent_id').$sell_status,'',$field);
        $goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$_SESSION['web']['uid']);
        $goodsInfo['dinzhi_goodsInfo'] = (new \Common\Model\Dingzhi\Szzmzb\Goods())->get_dinzhi_goodsInfo($dingzhi_goods_id,$goodsInfo['activity_status']);
        return $goodsInfo;
    }

    public function dingzhi_ajax_goodsDetails($dingzhi_goods_id){
        $goodsInfo = $this->get_goods_info($dingzhi_goods_id);
        layout(false);
        $this -> assign('goodsInfo',$goodsInfo);
        $this -> display('Dingzhi/Szzmzb/Goods/dingzhi_ajax_goodsDetails');
    }
    
    //立即定制
    public function dingzhi_ajax_goodsDetails_ljdz($dingzhi_goods_id){
        $goodsInfo = $this->get_goods_info($dingzhi_goods_id);
        foreach ($goodsInfo['dinzhi_goodsInfo']['selected']['checked'] as $k => $v) {
            if(I('k') == $k){ //立即定制选中
                $goodsInfo['dinzhi_goodsInfo']['selected']['associate_luozuan_id'] = $goodsInfo['dinzhi_goodsInfo']['selected']['associate_luozuan_id'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_type'] = $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_type'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_attribute'] = $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_attribute'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['technology'] = $goodsInfo['dinzhi_goodsInfo']['selected']['technology'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['luozuan_weight'] = $goodsInfo['dinzhi_goodsInfo']['selected']['luozuan_weight'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['luozuan_number'] = $goodsInfo['dinzhi_goodsInfo']['selected']['luozuan_number'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['goods_number'] = $goodsInfo['dinzhi_goodsInfo']['selected']['goods_number'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['hand'] = $goodsInfo['dinzhi_goodsInfo']['selected']['hand'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['word'] = $goodsInfo['dinzhi_goodsInfo']['selected']['word'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['material_weight'] = $goodsInfo['dinzhi_goodsInfo']['selected']['material_weight'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['material_name'] = $goodsInfo['dinzhi_goodsInfo']['selected']['material_name'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['associate_luozuan_gal_id'] = $goodsInfo['dinzhi_goodsInfo']['selected']['associate_luozuan_gal_id'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['associate_luozuan_weights_name'] = $goodsInfo['dinzhi_goodsInfo']['selected']['associate_luozuan_weights_name'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['goods_name_bm'] = $goodsInfo['dinzhi_goodsInfo']['selected']['goods_name_bm'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['shape'] = $goodsInfo['dinzhi_goodsInfo']['selected']['shape'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['shape_name'] = $goodsInfo['dinzhi_goodsInfo']['selected']['shape_name'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['ct_mm'] = $goodsInfo['dinzhi_goodsInfo']['selected']['ct_mm'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_name'] = $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_name'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_number'] = $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_number'][$k];
                $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_weight'] = $goodsInfo['dinzhi_goodsInfo']['selected']['deputystone_weight'][$k];
                $goodsInfo['dinzhi_goodsInfo']['goods_price'] = $goodsInfo['dinzhi_goodsInfo']['selected']['goods_price_one'][$k];
                $goodsInfo['dinzhi_goodsInfo']['activity_price'] = $goodsInfo['dinzhi_goodsInfo']['selected']['activity_goods_price_one'][$k];
            }
        }
        echo json_encode($goodsInfo);
    }

	public function getRecCategory($categoryId,$str, $type=3){
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

    public function getDeputystonePrice($deputystone_weight,$is_luozuan=1){
        $Deputystone        = M('banfang_deputystone');
        if( $deputystone_weight && $is_luozuan == 0 ){
            $GADeputystone      = M('banfang_size_weight');
            $deputystone_weight = $GADeputystone->where($deputystone_weight." >= min_size AND ".$deputystone_weight." < max_size")->getField('weight');
        }
        $deputystonePrice   = 0;
        //用第一个副石重量获取相关副石价格
        if($deputystone_weight){
            $deputystonePrice   = $Deputystone->where($deputystone_weight." >= min_weight AND ".$deputystone_weight." < max_weight")->find();
        }
        
        return $deputystonePrice;
        
    }

    public function getMaterialPrice(){
        $GMaterial  = M('goods_material');
        //添加银材质 luohaitao 20170824
        $materialPrice      = $GMaterial->where(array('parent_type'=>1,'parent_id'=>0,'material_name'=>array('in',array('白18K金','PT950','S925'))))->select();
        
        return $materialPrice;
        
    }

    public function getMaterialList($material_match_type){
        $BFMaterial = M('banfang_material');
        if($material_match_type == 1){
            $where_bf['material_type']  = array('in',array('0','1', '3'));
        }else{
            $where_bf['material_type']  = array('in',array('0', '3'));
        }
        $materialList       = $BFMaterial->where($where_bf)->select();  //材质列表
        //前台不显示PT900材质  guihongbing 20170725
        foreach ($materialList as $key => $value) {
            if($value['material_name'] == 'PT900'){
                unset($materialList[$key]);
            }
        }
        return $materialList;
        
    }

    public function getBaseProcessFee($jewelry_id){
        $BBPF   = M('banfang_base_process_fee');
        $BBPI   = M('banfang_base_process_items');
        $where['bbpi.jewelry_id']   = $jewelry_id;
        $baseProcessFee = $BBPI
                            ->alias('bbpi')
                            ->field('bbpf.*')
                            ->where($where)
                            ->join('left join zm_banfang_base_process_fee as bbpf on bbpf.base_items_id = bbpi.base_items_id')
                            ->find();
        
        return $baseProcessFee;
    }


}
