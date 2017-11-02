<?php
/**
 * 产品相关页面
 * 产品类型 goods_type  1:裸钻 2:散货 3：成品 4:戒托 5:代销
 */
namespace Home\Controller;
use Think\Page;

use Think\Model;
class GoodsController extends HomeController{

    public function index(){
        $this->redirect('/Home/Goods/diamond');
    }

    public function _before_diamond(){
        $this->seo_title   = "证书货查询";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->active      = "Goods";
    }

    public function _before_sanhuo(){
        $this->seo_title   = "散货查询";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->active      = "Goods";
    }

    public function _before_cart(){
        $this->seo_title   = "购物车";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->catShow     = 0;
        $this->active      = "Goods";
    }
    public function _before_cartStep2(){
        $this->seo_title   = "确认订单";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->active      = "Goods";
    }
    public function _before_orderComplete(){
        $this->seo_title   = "订单提交成功";
        $this->seo_keyword = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->active      = "Goods";
    }

    // 根据分销商的产品分类ID获取所有的子分类
    public function getProductChildrenCategorys($categoryId,$categorys){
        $data = M("goods_category_config")->where("pid IN('$categoryId') and agent_id = ".C('agent_id'))->select();
        $temp = array();
        if(!empty($data)){
            foreach($data as $key=>$val){
                $temp[] = $val['category_id'];
            }
            $categorys  = array_merge($categorys,$temp);
            $cate_str   = implode(',',$temp);
            $this      -> getProductChildrenCategorys($cate_str,$categorys);
            return implode(',',$categorys);
        }else{
            return implode(',',$categorys);
        }
    }

    public function initProducts(){
        $data  = $this -> getProductListAfterAddPoint();
        $this -> ajaxReturn($data);
    }

    public function getFilterProducts(){
        $data  = $this -> getProductListAfterAddPoint();
        $this -> ajaxReturn($data);
    }

    public function getProductListAfterAddPoint(){
        $categoryId = I('post.categroyId','0');
        $goodsType  = I('post.goodsType','3');
        $series_id  = I('post.series_id',0);
        $n          = I('post.n',20);
        $p          = $_POST['p'] = I('page',1);
        $sortType   = I('post.sortType')?I('post.sortType'):'create_time';
        $sortValue  = I('post.sortValue','ASC')?I('post.sortValue','ASC'):'ASC';
        //屏蔽超长数据
        if(empty($n)){
			$n = 20;
		}

        //获取具体要筛选的所有属性,cookie形式为1_60_filterAttbutes=1_60_2,1_60_3
        $filterKey  = C('agent_id').'_'.$categoryId.'_'.$goodsType.'_filterAttbutes';
        $filters    = I("cookie.$filterKey");
        $isAllFilterEmpty = true;
        if($filters){                               //如果此分类有要筛选的属性
            $filtersArray = explode(",",$filters);  //拆分成数组[1_60_4_2,1_60_4_3]
            $filterLen    = sizeof($filtersArray);
            if($filterLen<1)                        //没有筛选属性
                $this->ajaxReturn("has no filter attribute");//获取默认产品
            if($filterLen>=1){
                $isAllFilterEmpty = true;
                foreach($filtersArray as $filter){          //获取每个要筛选的属性 $filter=1_60_4_2
                    $combo_attr_code = I("cookie.$filter"); //获取具体某个要筛选的属性的筛选值,eg:1_60_4_2=3;
                    if($combo_attr_code){                   //如果有筛选值才发sql
                        $filterAttrs[array_pop(explode('_', $filter))] = $combo_attr_code;  //把要筛选的属性id和属性值存为数组,如：[1_60_2=3,1_60_3=4]=>[2->3,3-4]，最后的“2”为attributeId
                        $isAllFilterEmpty = false;
                    }
                }
            }
        }
        $M          = D('Common/Goods');
        $pid        = $this -> getCatPid($categoryId);
        if($pid == 0){
            $subIds = getSubCatIds($categoryId);
            if(implode(',', $subIds)){
                $categoryId = implode(',', $subIds);
                $M -> set_where( 'zm_goods.category_id' , " in ($categoryId) " , true );
            }
        }else{
            $M     -> set_where( 'zm_goods.category_id' , $categoryId );
        }

 

        if( $series_id > 0 && $goodsType == 3 ){
            $M -> set_where( 'zm_goods.goods_series_id' , $series_id );
        }
        $M     -> set_where( 'zm_goods.goods_type'  , "$goodsType");
        if(!$isAllFilterEmpty){
            $filterResult = array();
            foreach($filterAttrs as $attr_id => $combo_attr_code){
                $M -> set_where( 'zm_goods_associate_attributes.attr_id' , "$attr_id");
                $M -> set_where( 'zm_goods_associate_attributes.attr_code' , '&'.intval($combo_attr_code),true);
                $M -> _join( " join zm_goods_associate_attributes on zm_goods_associate_attributes.goods_id = zm_goods.goods_id " , true);
                $filterResult[] = $M -> getList(false,true,'zm_goods.goods_id'); //每个属性的筛选结果都存进数组(只查询goods_id)
            }
            foreach($filterResult as $result){
                if(count($result)==0){
                        $this->ajaxReturn("has no math product"); //只要有一个为空肯定没有交集
                }
            }
            //把所有goods_id放在一个array里，统计出goods_id出现的次数==count($filterResult)就是符合筛选的产品
            $all_goods_id = array();
            foreach($filterResult as $result){      //遍历每个属性的筛选结果
                foreach($result as $v)              //遍历具体的每条记录
                    $all_goods_id[] = $v['goods_id']; //把goods_id放进数组
            }
            $goods_id_count = array_count_values($all_goods_id);    //统计goods_id出现的次数
            $filterCount    = count($filterResult);
            $matchId        = array();
            foreach($goods_id_count as $k=>$v){
                if($v>=$filterCount)
                    $matchId[]=$k;  //筛选出 符合条件的goods_id
            }
            $M       -> sql['join'] = array();
            $M       -> set_where( 'zm_goods.goods_id' , implode(',', $matchId),false,true);
        }
		
        $M     	   -> set_where( 'zm_goods.price_model'  , "0");
		
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		
        $count    = $M -> get_count();
        $page     = new \Think\AjaxPage($count,$n,"setPage");
        $M       -> _limit($p,$n);
        $M       -> _order('zm_goods.'.$sortType,$sortValue);
        $products = $M -> getList();//根据符合筛选的goods_id查询表格
		if($products){
            $products = $M -> getProductListAfterAddPoint($products,$_SESSION['web']['uid']);
        }
		//print_r($products);
        $data['count'] = $count;
        $data['data']  = $products;
        $data['page']  = $page -> show();
        return $data;
    }

    public function dingzhi_ajax_goodsDetails(){
        check_dingzhi_gmac(I("gid"),'Home','Goods','dingzhi_ajax_goodsDetails');
    }
    public function dingzhi_ajax_goodsDetails_ljdz(){
        check_dingzhi_gmac(I("gid"),'Home','Goods','dingzhi_ajax_goodsDetails_ljdz');
    }
    public function dingzhi_addToCart_b2c(){
        check_dingzhi_gmac(I("gid"),'Home','Order','dingzhi_addToCart_b2c');
    }
    public function dingzhi_addCollection_b2c(){
        check_dingzhi_gmac(I("gid"),'Home','Order','dingzhi_addCollection_b2c');
    }
    
    // 分销商产品详情
    public function goodsInfo(){
        // check_dingzhi_gmac(I("gid"),'Home','Goods','goodsInfo');
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
        
		$this->display();
    }

    //周六福产品详情页面
    protected function _goodsInfo(){
        $GC = D('Common/BanfangUrl');
        $cate_lists = $GC->getTreeLists(array('na_status'=>1));
        $left_cates = array_values($cate_lists);
        $this->left_cates = $left_cates[0]['sub'];
        $GM = D('Common/Goods');
        $param = array(
            'goods_type'=>7,
            'agent_id'=>C('agent_id'),
            'gid'=>intval(I('goods_id')),
            'select_all'=>1,
            //'gid'=>intval(I('gid'))
        );
        $return_data = $GM->productGoodsInfo($param);
        if(!$return_data['status']){
            $this->error($return_data['msg']);
        }

        $this->data = $return_data['info'];
        $goodsHistorys = array(1,2,3,4,5);
        $this->goodsHistorys = $goodsHistorys;
        $this->display('dingzhi/zhouliufu/Goods/goodsInfo');
    }

    public function ajaxGetPrice(){
        $info = array(
            'status'=>0,
            'msg'=>'商品不存在'
        );
        $GM = D('Common/Goods');

        $data = array(
            'gid'=>intval(I('gid')),
            'g_data'=>I('post.g_data'),
            'note'=>trim(I('post.note')),
            'ma_id'=>intval(I('post.ma_id')),
            'goods_type_select_number'=>intval(I('post.goods_number')),
            'goods_type'=>7,
            'uid' => $_SESSION['web']['uid'],
            'session' => session_id(),
            'factory_id'=>array(),
            'diamond'=>array(),
            'color'=>array(),
            'clarity'=>array(),
            'deputystone_opened'=>array(),
            'hand'=>array(),
        );
        foreach($data['g_data'] as $val){
            if(!$val['fac_is_checked']){
                continue;
            }
            $data['factory_id'][] = $val['factory_id'];
            $data['diamond'][] = $val['diamond'];
            $data['color'][] = $val['color'];
            $data['clarity'][] = $val['clarity'];
            $data['deputystone_opened'][] = $val['deputystone_opened'];
            $data['hand'][] = $val['hand'];
        }

        unset($data['g_data']);
        $return_data = $GM->productGoodsInfo($data);
        if($return_data['status']){
            $info = array(
                'status'=>100,
                'msg'=>'获取数据成功',
            );
        }
        $info['data'] = $return_data['info'];
        $this->ajaxReturn($info);
    }

    public function ajaxSubmitCart(){
        $info = array(
            'status'=>0,
            'lists'=>array(),
            'count'=>0,
            'msg'=>'加入购物车失败',
        );
        $GM = D('Common/Goods');
        $CM = D('Common/Cart');
        /*$data = array(
            'goods_id'=>intval(I('post.goods_id')),
            'g_data'=>I('post.g_data'),
            'note'=>trim(I('post.note')),
            'goods_number'=>intval(I('post.goods_number')),
            'ma_id'=>intval(I('post.ma_id')),
        );*/
        $data = array(
            'gid'=>intval(I('gid')),
            'g_data'=>I('post.g_data'),
            'note'=>trim(I('post.note')),
            'ma_id'=>intval(I('post.ma_id')),
            'goods_type_select_number'=>intval(I('post.goods_number')),
            'goods_type'=>7,
            'uid' => $_SESSION['web']['uid'],
            'session' => session_id(),
            'factory_id'=>array(),
            'diamond'=>array(),
            'color'=>array(),
            'clarity'=>array(),
            'deputystone_opened'=>array(),
            'hand'=>array(),
            'serial_number'=>time(),
        );
        //用于存放将要加入购物车的数据
        $temp_info = array();
        $serial_number = time();
        $error_data = array();
        foreach($data['g_data'] as $val){
            if(!$val['fac_is_checked']){
                continue;
            }
            $param = $data;
            $param['factory_id'][] = $val['factory_id'];
            $param['diamond'][] = $val['diamond'];
            $param['color'][] = $val['color'];
            $param['clarity'][] = $val['clarity'];
            $param['deputystone_opened'][] = $val['deputystone_opened'];
            $param['hand'][] = $val['hand'];
            $return_data = $GM->productGoodsInfo($param);

            if($return_data['status']==100 && empty($return_data['info']['select_param']['error'])){
                $param['add_factory_sex'] = array($return_data['info']['factory_lists'][0]['factory_info']['sex']);
                $temp_info[] = $param;
            }else{
                $error_data[] = $return_data['info']['select_param']['error'][0];
            }
        }
        $temp_info_cart = array();
        if(!empty($temp_info) && empty($error_data)){
            foreach($temp_info as $oop){
                $return = $CM->addToCart($oop);
                if($return['status']==100){
                    $temp_info_cart['cart_id'] = $return['id'];
                    $temp_info_cart['banfang_cart_id'][] = $return['banfang_cart_id'];
                }
            }
            if($temp_info_cart['cart_id']){
                $info = array(
                    'status'=>100,
                    'lists'=>$temp_info['cart_id'],
                    'count'=>count($temp_info['banfang_cart_id']),
                    'msg'=>'加入购物成功',
                );
            }
        }

        if(!empty($error_data)){
            $info['msg'] = $error_data[0];
        }
        $this->ajaxReturn($info);
    }

    public function ajaxListsAddCart(){
        $info = array(
            'status'=>0,
            'lists'=>array(),
            'count'=>0,
            'msg'=>'加入购物车失败',
        );

        $param = array(
            'goods_type_select_number'=>1,
            'gid'=>intval(I('goods_id')),
            'uid'=>intval($_SESSION['web']['uid']),
            'session'=>session_id(),
            'goods_type'=>7,
            'serial_number'=>time()
        );
        $temp_info = array();
        $number = -1;

        $GM = D('Common/Goods');
        $CM = D('Common/Cart');
        $return_data = $GM->productGoodsInfo($param);


        if($return_data['status']==100){
            //$param['cart_id_couple'] = $temp_info['cart_id'][$number] ? $temp_info['cart_id'][$number] : 0;
            foreach($return_data['info']['factory_lists'] as $factory_list){
                $param['add_factory_sex'] = array($factory_list['factory_info']['sex']);
                $param['diamond'] = array($factory_list['factory_info']['zhushi_to']);

                $return = $CM->addToCart($param);
                if($return['status']==100){
                    $temp_info['cart_id'][] = $return['id'];
                    $temp_info['banfang_cart_id'][] = $return['banfang_cart_id'];
                    $number++;
                }
            }
        }

        if(!empty($temp_info)){
            $info = array(
                'status'=>100,
                'lists'=>$temp_info['cart_id'],
                'count'=>count($temp_info['banfang_cart_id']),
                'msg'=>'加入购物成功',
            );
        }
        $this->ajaxReturn($info);
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
    // 获取该商品的评论

    public function getAjaxComment(){
        $n        = 5;
        $p        = 1;
        $gid      = I("gid");
        $where    = "goods_id=".$gid;
        $count    = M("goods_comment")->where($where)->count();
        $page     = new \Think\AjaxPage($count,$n,'setPage');
        $leftjoin = " LEFT JOIN zm_user ON zm_goods_comment.user_id =  zm_user.uid";
        $data['data'] = M("goods_comment")->join($leftjoin)->where($where)->limit($page->firstRow.",".$page->listRows)->select();
        if($data['data']){
            foreach($data['data'] as $key=>$val){
                $data['data'][$key]['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            }
        }
        $data['count'] = $count;
        $this->ajaxReturn($data);
    }

    // 分销商白钻产品定制
    public function goodsDiy($n='10',$where='0'){
        $cartGoods       = M('cart')->where('agent_id='.C('agent_id').' and '. $this->userAgent)->count();
        $this->cartCount = $cartGoods;

        /*===========================定制流程编号处理  开始===========================*/
        $step1 = (int)isset($_COOKIE['step1'])?$_COOKIE['step1']:0; //step1存放裸钻商品编号
        $step2 = (int)isset($_COOKIE['step2'])?$_COOKIE['step2']:0; //step2存放戒托商品编号

        if($step1<>0 and $step2<>0){
            $diy_step = 'step11';
            $step1    = $this->getOneDiamondsData($step1);
            if($_COOKIE['dingzhi_szzmzb_step'] == 1){ //szzmzb 板房
                $step2 = json_decode(js_unescape($_COOKIE['dingzhi_szzmzb_step_goods_info']),true);
            }else{
                $step2    = $this->get_goods_info($step2); //获取商品详细信息
            }
            $step3    = array('luozuan'=>$step1['goods_price'],'jietuo'=>$step2['goods_price'],'total'=>$step1['goods_price']+$step2['goods_price']);
        //print_r($step3);die;
        }elseif($step1<>0 and $step2==0){
            $diy_step = 'step10';
            $step1 = $this->getOneDiamondsData($step1);
            $step3 = array('luozuan'=>$step1['goods_price'],'total'=>$step1['goods_price']);
        //print_r($step3);die;
        }elseif($step1==0 and $step2<>0){
            $diy_step = 'step01';
            if($_COOKIE['dingzhi_szzmzb_step'] == 1){ //szzmzb 板房
                $step2 = json_decode(js_unescape($_COOKIE['dingzhi_szzmzb_step_goods_info']),true);
            }else{
                $step2    = $this->get_goods_info($step2); //获取商品详细信息
            }
            $step3    = array('jietuo'=>$step2['goods_price'],'total'=>$step2['goods_price']);
        }else{
            $diy_step = 'step00';
        }
        $this->shape = $step2['associate_luozuan']['shape'];
        $this->weight= $step2['associate_luozuan']['weights_name'];
        $this->step1 = $step1;
        $this->step2 = $step2;
        $this->step3 = $step3;
        /*===========================定制流程编号处理 结束===========================*/
		$goods_sn = I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);

        $this->navActive          = "luozuan";
        $this->price_display_type = C('price_display_type');
        $this->display();
    }

	// 分销商彩钻产品定制
    public function goodsDiyColor($n='10',$where='0'){
        $cartGoods = M('cart')->where('agent_id='.C('agent_id').' and '. $this->userAgent)->count();
        $this->cartCount = $cartGoods;

        /*===========================定制流程编号处理  开始===========================*/
        $step1 = (int)isset($_COOKIE['step1'])?$_COOKIE['step1']:0; //step1存放裸钻商品编号
        $step2 = (int)isset($_COOKIE['step2'])?$_COOKIE['step2']:0; //step2存放戒托商品编号

        if($step1<>0 and $step2<>0){
            $diy_step = 'step11';
            $step1 = $this->getOneDiamondsData($step1);
            $step2 = $this->get_goods_info($step2); //获取商品详细信息
            $step3 = array('luozuan'=>$step1['goods_price'],'jietuo'=>$step2['goods_price'],'total'=>$step1['goods_price']+$step2['goods_price']);
        //print_r($step3);die;
        }elseif($step1<>0 and $step2==0){
            $diy_step = 'step10';
            $step1 = $this->getOneDiamondsData($step1);
            $step3 = array('luozuan'=>$step1['goods_price'],'total'=>$step1['goods_price']);
        //print_r($step3);die;
        }elseif($step1==0 and $step2<>0){
            $diy_step = 'step01';
            $step2    = $this->get_goods_info($step2); //获取商品详细信息
            $step3    = array('jietuo'=>$step2['goods_price'],'total'=>$step2['goods_price']);
        }else{
            $diy_step = 'step00';
        }

        $this->shape = $step2['associate_luozuan']['shape'];
        $this->weight= $step2['associate_luozuan']['weights_name'];
        $this->step1 = $step1;
        $this->step2 = $step2;
        $this->step3 = $step3;
        /*===========================定制流程编号处理 结束===========================*/
		$goods_sn = I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);

        $this->navActive          = "caizuan";
        $this->price_display_type = C('price_display_type');
        $this->display();
    }

    // 散货列表
    public function sanhuo($p=1,$n = 10){
        $this->goodsType     = M('goods_sanhuo_type')->order('tid ASC')->select();
        $GSobj               = D('GoodsSanhuo');
        $res                 = $GSobj -> getList(array(),'goods_id asc',$p,$n);
        $sanhuoGoods         = $res['list'];
        $count               = $res['total'];
        $locationList        = array("深圳","上海","北京","香港","国外");
        $this->sanhuoWeights = M("goods_sanhuo_weights")->where("pid=0")->order("sort ASC")->select();
        $this->sanhuoColor   = M("goods_sanhuo_color")->order("sort ASC")->select();
        $this->sanhuoClarity = M("goods_sanhuo_clarity")->order("sort ASC")->select();
        $this->sanhuoCut     = M("goods_sanhuo_cut")->order("sort ASC")->select();
        $this->locationList  = $locationList;
        $this->sanhuo_goods  = $sanhuoGoods;

        $Page  = new \Think\AjaxPage($count,$n,'setPage');
        $this->page = $Page->show();

        $this->catShow = 0;
        $this->display();
    }

    /**
     * 导出散货数据
     */
    public function exportSanhuoData()
    {
        $ids = I('etd_id');
        if($ids){
            $GSobj = D('GoodsSanhuo');
            $where = array();
            $where['goods_id'] = ['in', $ids];
            $ordersql = " goods_id asc ";
            $res = $GSobj -> getList($where, $ordersql, 1, 100);
            $sanhuoList = $res['list'];

            $sanhuoList = getGoodsListPrice($sanhuoList, $_SESSION['web']['uid'], 'sanhuo');

            if(!empty($sanhuoList) && is_array($sanhuoList)){
                ignore_user_abort(true);
                ini_set('memory_limit','1024M');
                import("Org.Util.PHPExcel");
                $objPHPExcel=new \PHPExcel();
                $PHPReader  =new \PHPExcel_Reader_Excel5();
                $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");
                $objPHPExcel->setActiveSheetIndex(0);
                $objActSheet = $objPHPExcel->getActiveSheet();
                $objRichText = new \PHPExcel_RichText();
                $objRichText->createText('');

                // 列宽
                $objActSheet->getColumnDimension('A')->setWidth(10);
                $objActSheet->getColumnDimension('B')->setWidth(10);
                $objActSheet->getColumnDimension('C')->setWidth(16);
                $objActSheet->getColumnDimension('D')->setWidth(30);
                $objActSheet->getColumnDimension('E')->setWidth(20);
                $objActSheet->getColumnDimension('F')->setWidth(14);
                $objActSheet->getColumnDimension('G')->setWidth(14);
                $objActSheet->getColumnDimension('H')->setWidth(10);
                $objActSheet->getColumnDimension('I')->setWidth(30);
                $objActSheet->getColumnDimension('J')->setWidth(100);

                $objActSheet->getRowDimension(1)->setRowHeight(30);

                foreach(array('A','B','C','D','E','F','G','H','I','J') as $k=>$v){
                    $objActSheet->getStyle($v.'1')->getAlignment()->setHorizontal("center");
                    $objActSheet->getStyle($v.'1')->getFont()->setBold(true);
                    $objActSheet->getStyle($v.'1')->getFont()->getColor()->setARGB('00000000');
                    $objActSheet->getStyle($v.'1')->getAlignment()->setVertical('center');
                    $objActSheet->getStyle($v.'1')->getFill()->setFillType('solid');
                    $objActSheet->getStyle($v.'1')->getFill()->getStartColor()->setARGB('00F4F4F4');
                }

                //设置单元格的值
                $objActSheet->setCellValue('A1', mb_convert_encoding('ID', "UTF-8", "auto"));
                $objActSheet->setCellValue('B1', mb_convert_encoding('来源', "UTF-8", "auto"));
                $objActSheet->setCellValue('C1', mb_convert_encoding('分类', "UTF-8", "auto"));
                $objActSheet->setCellValue('D1', mb_convert_encoding('货号', "UTF-8", "auto"));
                $objActSheet->setCellValue('E1', mb_convert_encoding('库存重量(CT)', "UTF-8", "auto"));
                $objActSheet->setCellValue('F1', mb_convert_encoding('颜色', "UTF-8", "auto"));
                $objActSheet->setCellValue('G1', mb_convert_encoding('净度', "UTF-8", "auto"));
                $objActSheet->setCellValue('H1', mb_convert_encoding('切工', "UTF-8", "auto"));
                $objActSheet->setCellValue('I1', mb_convert_encoding('统走定价(元/CT)', "UTF-8", "auto"));
                $objActSheet->setCellValue('J1', mb_convert_encoding('备注信息', "UTF-8", "auto"));

                //值从第二行开始
                $index = 2;

                //把值放进excel中
                foreach($sanhuoList as $key=>$v){
                    $objActSheet->getRowDimension($index)->setRowHeight(30);
                    $objActSheet->setCellValue('A'.$index,$v['goods_id']);
                    $objActSheet->setCellValue('B'.$index,$v['location']);
                    $objActSheet->setCellValue('C'.$index,$v['type_name']);
                    $objActSheet->setCellValue('D'.$index,$v['goods_sn']);
                    $objActSheet->setCellValue('E'.$index,$v['goods_weight']);

                    $color = html_entity_decode($v['color']);
                    $objActSheet->setCellValue('F'.$index, $color);

                    $clarity = html_entity_decode($v['clarity']);
                    $objActSheet->setCellValue('G'.$index,$clarity);

                    $objActSheet->setCellValue('H'.$index,$v['cut']);
                    $objActSheet->setCellValue('I'.$index,$v['goods_price']);

                    $goods_4c = html_entity_decode($v['goods_4c']);
                    $goods_4c = str_replace(["<br />", "<br/>", "<br>"], "  ", $goods_4c);
                    $goods_4c = str_replace("&lt;", "<", $goods_4c);
                    $goods_4c = str_replace("&gt;", ">", $goods_4c);

                    $objActSheet->setCellValue('J'.$index,$goods_4c);
                    $objActSheet->getStyle("J".$index)->getAlignment()->setHorizontal("left");
                    $objActSheet->getStyle("J".$index)->getAlignment()->setWrapText(true);

                    //设置边框
                    foreach(array('A','B','C','D','E','F','G','H','I','J') as $k=>$v){
                        $objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
                        $objActSheet->getStyle($v.$index)->getAlignment()->setHorizontal("center");
                        $objActSheet->getStyle($v.$index)->getAlignment()->setVertical('center');
                    }

                    $index++;
                }

                $fileName = date('YmdHis',time()).rand(100000,999999);
                $xlsTitle = "散货数据";
                ob_end_clean();
                header('pragma:public');
                header('Cache-Control: max-age=0');
                header('Content-type:application/vnd.ms-excel;name="'.$xlsTitle.'.xls"');
                header("Content-Disposition:attachment;filename=$fileName.xls");
                $PHPIF = new \PHPExcel_IOFactory();
                $objWriter = $PHPIF->createWriter($objPHPExcel, 'Excel5');
                $objWriter->save('php://output');
                exit;
            }else{
                $this->error('导入数据失败');
            }
        }else{
            $this->error('请选择要导出的数据');
        }
    }

    //散货Ajax获取列表
    public function getGoodsByParam(){
        $goodsTypeId     = I('GoodsType');
        $goods_sn        = I('GoodsSn');
        $Location        = I('Location');
        $p = $_POST['p'] = I('page','1');
        $n = $_POST['n'] = I('n','13');
        $Clarity         = I('Clarity');
        $Clarity         = str_replace('1','VVS',$Clarity);
        $Clarity         = str_replace('2','VVS-VS',$Clarity);
        $Clarity         = str_replace('3','VS',$Clarity);
        $Clarity         = str_replace('4','VS2-SI1',$Clarity);
        $Clarity         = str_replace('5','SI1-SI2',$Clarity);
        $Clarity         = str_replace('6','SI2-SI3',$Clarity);
        $Clarity         = str_replace('7','I1-I2',$Clarity); 
        $Clarity         = str_replace('8','<I2',$Clarity);
        $Clarity_data    = explode(',',$Clarity);
        $Clarity         = $Clarity_data;
        $Weight_data     = explode(',',I('Weight'));
        $Weight          = $Weight_data;
        $Cut_data        = explode(',',I('Cut'));
        $Cut             = $Cut_data;
        $Color_data      = str_replace(',','|',I('Color'));
        $Color_data      = explode('|',$Color_data);
        $Color           = $Color_data;

        $orderby = trim(I('orderby', ''));
        $orderway = trim(I('orderway', ''));

        $where           = array();
        $where['zm_goods_sanhuo.goods_weight'] = array('gt','0');
        if(!empty($Clarity) && $Clarity != "''" && $Clarity[0] != '' ){
            $where['zm_goods_sanhuo.clarity']  = array('in',$Clarity);
        }
        if(!empty($Cut) && $Cut != "''" && $Cut[0] != ''){
            $where['zm_goods_sanhuo.cut']      = array('in',$Cut);
        }
        if(!empty($Weight) && $Weight != "''" && $Weight[0] != '' ){
            $Weight = str_replace(' 300', '+300', $Weight);
            $where['zm_goods_sanhuo.weights']  = array('in',$Weight);
        }
        if(!empty($Location)){
            $where['zm_goods_sanhuo.location']  = array('in',$Location);
        }
        if($goodsTypeId != -1 && !empty($goodsTypeId)){
            $where['zm_goods_sanhuo.tid'] = array('in',$goodsTypeId);
            $obj = D('GoodsSanhuo');
            $id  = $obj -> get_where_agent();
            $sanhuoGoods['goods_sn']      = $obj -> field('goods_sn,goods_id')->where("tid='".$goodsTypeId."' and agent_id in ($id) ")->order('goods_sn ASC')->select();
        }
        if($goods_sn != -1 && !empty($goods_sn)){
            $where['zm_goods_sanhuo.goods_id']  = array('in',$goods_sn);
        }
        if(!empty($Color) && $Color != "''" && $Color[0] != '' ){
            $where['zm_goods_sanhuo.color']    = array('in',$Color);
        }

        if (!empty($orderby) && in_array(strtolower($orderby), ['goods_price', 'goods_weight', 'color', 'clarity']) && in_array(strtolower($orderway), ['asc', 'desc'])){  //排序
            $orderby = "{$orderby}";
            $orderway = empty($orderway) ? 'ASC' : $orderway;
            $ordersql = "  $orderby $orderway, goods_id asc ";
        }else{  //默认排序
            if(!empty($Color) or !empty($Clarity) or !empty($Cut) or !empty($Location)){
                $ordersql = " goods_id asc ";
            }
        }


        $GSobj               = D('GoodsSanhuo');
        $res                 = $GSobj -> getList($where,$ordersql,$p,$n);
        $sanhuoGoods['data'] = $res['list'];
        $count               = $res['total'];
        $sanhuoGoods['total'] = $count;
        if($sanhuoGoods){
            $sanhuoGoods['data'] = getGoodsListPrice($sanhuoGoods['data'], $_SESSION['web']['uid'], 'sanhuo');
        }
		$sanhuoGoods['goodsSn']  = $goods_sn;
        $Page                    = new \Think\AjaxPage($count,$n,'setPage');
        $sanhuoGoods['page']     = $Page->show();
        //购物车
        $sanhuoGoods['cartCount'] = M('cart')->where('agent_id='.C('agent_id').' and '. $this->userAgent)->count();
        $this->ajaxReturn($sanhuoGoods);
    }

    public function getListByGoodsSn(){
        $goodsSn = I('goodsSn', '');
        if(!empty($goodsSn)){
            $goodsDataList = D('GoodsSanhuo')->getListByGoodsSn($goodsSn);
        }
        $goodsList = [
            'data' => $goodsDataList,
        ];
        $this->ajaxReturn($goodsList);
    }

    // 钻石查询
    public function diamond($p=1,$n = 15){
        $GL    = D('GoodsLuozuan');
        $huilv = C('dollar_huilv');
        $count = $GL->count();
        $Page  = new \Think\AjaxPage($count,$n,'goods');

        $where    = " goods_number > 0 ";
        $dataList = $GL->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();


        foreach($dataList as $key=>$val){
            $dataList[$key]['shape'] = $this->getDiamondsShapeNo($val['shape']);
            $dataList[$key]['dia_rmb'] = formatRound($val['dia_global_price']*$huilv,2);
            $dataList[$key]['certificateUrl'] = $this->formatReportURL($val['certificate_type'],$val['certificate_number'],$val['weight']);
        }
        $dataList['total'] = $count;
        $this->dataList = getGoodsListPrice($dataList, $_SESSION['web']['uid'], 'luozuan');
        $this->page  = $Page->show();
        $this->huilv = $huilv;
        $this->count = $count;

        $this->cartCount = M('cart')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."'")->count();
        $this->display();
    }

    public function getGoodsDiamondDiy($p=1,$n=15){

        $orderby   = trim($_REQUEST['orderby']);                    //排序字段
        $orderway  = trim($_REQUEST['orderway']);                   //排序方式

        $step2            = (int)isset($_COOKIE['step2'])?$_COOKIE['step2']:0; //step2存放戒托商品编号
        $associateLuozuan = array();
        if($step2){
            $gidMaterialDiamonds = explode('_',$_COOKIE[ 'goods_id_' .$step2 ]);
            $diamondId           = $gidMaterialDiamonds[1];
            $associateLuozuan    = M("goods_associate_luozuan")->alias("GAL")->join("zm_goods_luozuan_shape GLS ON GLS.shape_id=GAL.shape_id")->where("GAL.goods_id=".$step2." AND GAL.gal_id=$diamondId")->find();
        }

        $where = " goods_number > 0 ";
		$goods_sn = I('goods_sn','','intval');
		$luozuan_type = I('luozuan_type',0,'intval');
        if($goods_sn){
            $where .= " AND (certificate_number like '%$goods_sn%' OR goods_name like '%$goods_sn%') ";
        }//货品编号

		$where .=" AND luozuan_type = ".$luozuan_type ;

        if($associateLuozuan['weights_name']){
            $where .= " AND (weight >= ".floatval($associateLuozuan['weights_name'])." AND weight <= ".floatval($associateLuozuan['weights_name']).")";
        }else{
			$weight = I('Weight');
            if($weight){
				//dump($weight);exit;
				$weight = explode('-',$weight);
				if(empty($weight[0]) || $weight[0] == 'undefined' ){
					$weight[0]=0;
				}
				if(empty($weight[1]) || $weight[1] == 'undefined' ){
					$weight[1]=500;
				}

				if($weight[1] < $weight[0]){
					$temp_weight = $weight[1];
					$weight[1] 	 = $weight[0];
					$weight[0] 	 = $temp_weight;
				}
				$where .= " AND (weight >= ".$weight[0]." AND weight <= ".$weight[1].")";
			}
        }

        $Intensity = urldecode($_REQUEST['Intensity']);
		if($Intensity){
			$where .= " AND intensity IN (".$this->paramStr($Intensity).")";
		}

        $uid = $_SESSION['web']['uid'];
        if($uid){    //如有登录，则获取会员折扣
			if($luozuan_type == 1){
				$userdiscount = getUserDiscount($uid, 'luozuan_discount');
			}else{
				$userdiscount = getUserDiscount($uid, 'caizuan_discount');
			}

        }

        if(I('Price')){
            if(I('Price') == "10000"){
                $price[0] = 10000;
                $price[1] = 50000000;
            }else{
                $price = explode('-',I('Price'));
                if(empty($price[0]) || $price[0] == 'undefined' )$price[0]=0;
                if(empty($price[1]) || $price[1] == 'undefined' )$price[1]=50000000;
                if($price[1] < $price[0]){
                    $temp = $price[1];
                    $price[1] = $price[0];
                    $price[0] = $temp;
                }
            }

            $point = D("GoodsLuozuan") -> setLuoZuanPoint();
            $point = $point + C('luozuan_advantage');
            ///////////这里的$this->luozuan_advantage home控制器，AutoMuban方法， 默认等于0
            //这里不用考虑销售加点，因为根据重量来计算的加点，对数据库的开支太大了。
            if(C('price_display_type') == 0) {   //直接加单
                 $where .= " AND (dia_global_price * " . C('dollar_huilv') . " * ( dia_discount + " . ($point + $userdiscount) . " ) * weight/100 >= ".$price[0].")
                 AND (dia_global_price * " . C('dollar_huilv') . " * ( dia_discount +".($point + $userdiscount) . " ) * weight / 100 <= ".$price[1].")";
            } else {
                $where .= " AND (dia_global_price * " . C('dollar_huilv') . " * dia_discount / 100 * (1 + (" . ($point + $userdiscount).")/100) * weight >= ".$price[0].")
                AND (dia_global_price * " . C('dollar_huilv') . " * dia_discount / 100 * ( 1 + (" . ($point + $userdiscount) . ")/100) * weight <= ".$price[1].")";
            }
        }

        if($associateLuozuan['shape']){
            $shape = $associateLuozuan['shape'];
            $where .= " AND shape in('".$shape."')";
        }else{
            if(I('Shape')){
                $shape = $this->paramStr(I("Shape"));
                $where .= " AND shape in(".$shape.")";
            }
        }


        if(I('Color')){
            $inputColor = I("Color");
            $inputColorArr = explode(',', $inputColor);
            $inputColorArrAll = [];
            foreach($inputColorArr as $col){
                $col = trim($col);
                $inputColorArrAll[] = "'".$col."'";
                $inputColorArrAll[] = "'".$col."+'";
                $inputColorArrAll[] = "'".$col."-'";
            }
            $color = implode(',', $inputColorArrAll);
            $where .= " AND color in(".$color.")";
        }
        if(I('Clarity')){
            $inputClarity = I("Clarity");
            $inputClarityArr = explode(',', $inputClarity);
            $inputClarityArrAll = [];
            foreach($inputClarityArr as $cla){
                $cla = trim($cla);
                $inputClarityArrAll[] = "'".$cla."'";
                $inputClarityArrAll[] = "'".$cla."+'";
                $inputClarityArrAll[] = "'".$cla."-'";
            }
            $clarity = implode(',', $inputClarityArrAll);
            $where .= " AND clarity in(".$clarity.")";

        }
        $encode = mb_detect_encoding($_REQUEST["Location"], array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        $Location = mb_convert_encoding($_REQUEST["Location"], 'UTF-8', $encode);

        if($Location){$where .=" AND `location` In (".$this->paramStr($Location).")";}      

        
        if($_REQUEST['Stock'] == 'xianhuo'){
            $where .= " AND `quxiang` NOT IN('订货','定货','散货')";
        }
        if($_REQUEST['Stock'] == 'dinghuo'){
            $where .= " AND `quxiang` IN('订货','定货')";
        }

        if(I('Cut')){
            $cut = $this->paramStr(I("Cut"));
            $where .= " AND cut in(".$cut.")";
        }
        if(I('Symmetry')){
            $symmetry = $this->paramStr(I("Symmetry"));
            $where .= " AND symmetry in(".$symmetry.")";
        }
        if(I('Polish')){
            $polish = $this->paramStr(I("Polish"));
            $where .= " AND polish in(".$polish.")";
        }
        if(I('Cert')){
            $cert = $this->paramStr(I("Cert"));
            $where .= " AND certificate_type in(".$cert.")";
        }

        if(I('Fluor')){
            $Fluor = I('Fluor');
            if(stristr($Fluor,'Other')){
                $where .= " AND fluor NOT IN ('N','F','M','S','VS')";
            }else{
                $where .= " AND fluor IN (".$this->paramStr($Fluor).")";
            }
        }

        if(I('NaiKa')){
            $NaiKa = I('NaiKa');
            if(stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
                $where .= " AND (milk like '%无奶%' AND coffee like '%无咖%'
                ) ";
            }elseif(stristr($NaiKa,'NoMilk') and !stristr($NaiKa,'NoCoffee')){
                $where .= " AND (milk like '%无奶%') ";
            }elseif(!stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
                $where .= " AND (coffee like '%无咖%') ";
            }
        }
        if (!empty($orderby)){  //排序
            if ($orderby=="shop_Price"){
                $orderby = "price";
            }else{
                $orderby = "{$orderby}";
            }
            $orderway = empty($orderway) ? 'ASC' : $orderway;
            $ordersql = "  $orderby $orderway,weight ASC ";
        }else{  //默认排序
            if(!empty($Shape) or !empty($Color) or !empty($Clarity) or !empty($Cut) or !empty($Polish) or !empty($Symmetry) or !empty($Cert) or !empty($Flour) or !empty($Location)){
				$ordersql = " weight ASC ";
            }else{
				$ordersql = " weight ASC ";
			}
        }

        $count = D("GoodsLuozuan")->where($where)->count();
        $Page = new \Think\AjaxPage($count,$n,'setPage');
		if($luozuan_type == '1'){
			$goodsluozuanobject = new \Common\Model\GoodsLuozuanModel(1);
        }else{
			$goodsluozuanobject = D('GoodsLuozuan');
		}
		$dataList['data'] = $goodsluozuanobject -> where($where)->limit($Page->firstRow.",".$Page->listRows)->order($ordersql)->select();
        if( is_array($dataList['data']) && count($dataList['data']) > 0 ){
            foreach ($dataList['data'] as &$value) {
                $value['dia_discount_all'] = $value['dia_discount'];
            }
        }



        if(!empty($dataList['data']) && $_SESSION['web']['uid']>0){
            $HM = M('collection');
            $collection_where = array(
                'uid'=>$_SESSION['web']['uid'],
                'agent_id'=>C('agent_id')
            );
            foreach($dataList['data'] as $keyer => $valuer){
                $collection_where['goods_id'] = $valuer['gid'];
                $collection_where['goods_type'] = $valuer['luozuan_type'] ? 1 : 0;
                $collection_info = $HM->field('id')->where($collection_where)->find();
                if($collection_info){
                    $dataList['data'][$keyer]['collection_id'] = $collection_info['id'];
                }
            }
        }

        $dataList['data']		= getGoodsListPrice($dataList['data'], $_SESSION['web']['uid'], 'luozuan');

        $dataList['count']		= $count;
        $dataList['page']		= $Page->show();
        $dataList['uType']		= $this->uType;
        $dataList['dollar_huilv'] = C('dollar_huilv');
        $dataList['price_display_type'] = C('price_display_type');
		$dataList['page_size']	= $n;
        $this->ajaxReturn($dataList);
    }

    public function getDiamondsData(){
        $GL        = D('GoodsLuozuan');
        $where     = " 1 = 1 ";
        $Shape     = I('Shape');
        $Color     = I('Color');
        $Clarity   = I('Clarity');
        $Weight    = explode('-',$_REQUEST["Weight"],2);        //重量
        $minWeight = floatval($Weight[0]);
        $maxWeight = floatval($Weight[1]);
        $Price     = explode('-',$_REQUEST["Price"],2);     //价格
        $minPrice  = floatval($Price[0]);
        $maxPrice  = floatval($Price[1]);
        $Cert      = urldecode($_REQUEST["Cert"]);          //证书
        $Cut       = trim($_REQUEST["Cut"]);
        $Polish    = trim($_REQUEST["Polish"]);
        $Symmetry  = trim($_REQUEST["Symmetry"]);
        $Fluor     = urldecode($_REQUEST['Fluor']);
        $NaiKa     = trim(urldecode($_REQUEST['NaiKa']));   //奶咖
        $Location  = trim(urldecode($_REQUEST["Location"]));            //所在地

        $orderby   = trim($_REQUEST['orderby']);                        //排序字段
        $orderway  = trim($_REQUEST['orderway']);                   //排序方式
        $goods_sn = I('goods_sn','','intval');
		$luozuan_type = I('luozuan_type',0,'intval');
		if($luozuan_type){
			$where .= " AND luozuan_type = ".$luozuan_type ;
		}
        if($goods_sn){
            $where .= " AND (certificate_number like '%$goods_sn%' OR goods_name like '%$goods_sn%') ";
        }//货品编号

        if(!empty($Shape)){
            $where .= " AND shape IN(".$this->paramStr($Shape).")";
        }
        if(!empty($Cert) and !stristr($Cert,'Other')){          //证书
            $where .= " AND certificate_type IN (".$this->paramStr($Cert).")";
        }elseif(stristr($Cert,'Other')){
            $where .= " AND certificate_type NOT IN ('GIA','HRD','IGI','NGTC')";
        }
        $gidMaterialDiamond = $_COOKIE["Gid_Material_Diamond"];

        if($gidMaterialDiamond){
            $temp    = explode("_",$gidMaterialDiamond);
            $diamond = $temp[1];
            $where  .= " AND weight >= ".($diamond-0.02)." AND weight <= ".($diamond+0.02);
            $dataList['cookieDingzhi'] = true;
        }

        if($Price){
            if($Price == "10000"){
                $price[0] = 10000;
                $price[1] = 50000000;
            }else{
                $price = explode('-',$Price);
                if(empty($price[0]))$price[0]=0;
                if(empty($price[1]))$price[1]=50000000;
                if($price[1]  < $price[0]){
                    $temp     = $price[1];
                    $price[1] = $price[0];
                    $price[0] = $temp;
                }
            }

            $uid = $_SESSION['web']['uid'];
            if($uid){    //如有登录，则获取会员折扣
                $userdiscount = getUserDiscount($uid, 'luozuan');
            }else{
                $userdiscount = 0;
            }
            $point = D("GoodsLuozuan") -> setLuoZuanPoint();
            $point = $point + C('luozuan_advantage');
            ///////////这里的$this->luozuan_advantage home控制器，AutoMuban方法， 默认等于0
            //这里不用考虑销售加点，因为根据重量来计算的加点，对数据库的开支太大了。
            if(C('price_display_type') == 0) {   //直接加单
                 $where .= " AND (dia_global_price * " . C('dollar_huilv') . " * ( dia_discount + " . ($point + $userdiscount) . " ) * weight/100 >= ".$price[0].")
                 AND (dia_global_price * " . C('dollar_huilv') . " * ( dia_discount +".($point + $userdiscount) . " ) * weight/100 <= ".$price[1].")";
            } else {
                $where .= " AND (dia_global_price * " . C('dollar_huilv') . " * dia_discount / 100 * (1 + (" . ($point + $userdiscount).")/100) * weight >= ".$price[0].")
                AND (dia_global_price * " . C('dollar_huilv') . " * dia_discount / 100 * ( 1 + (" . ($point + $userdiscount) . ")/100) * weight <= ".$price[1].")";
            }
        }

        if($Color){//颜色
            if(stristr($Color,'Fancy')){
                $where .= " AND color NOT IN ('D','D+','D-','E','E+','E-','F','F+','F-','G','G+','G-','H','H+','I','I+','I-','J','J+','K','K+','L','L+','M','M+','N','N+')";
            }else{
                $Color_data = explode('|',$Color);
                foreach($Color_data as $key=>$v){
                    $Color_data[$key] = $v.",$v+,$v-";
                }
                $Color = $this->paramStr(implode(',',$Color_data));
                //die;
                $where .= " AND color IN (".$Color.")";
            }
        }
        if(!empty($Clarity) and !stristr($Clarity,'Other')){            //净度
            $where .= " AND clarity IN (".$this->paramStr($Clarity).")";
        }elseif(stristr($Clarity,'Other')){
            $where .= " AND clarity NOT IN ('FL','IF','VVS1','VVS2','VS1','VS2','SI1','SI2','I1','I2','I3')";
        }

        if($Cut){//切工
            if(stristr($Cut,'Other')){
                $where .= " AND cut NOT IN ('I','EX','VG','GD','F')";
            }else{
                $where .= " AND cut IN (".$this->paramStr($Cut).")";
            }
        }
        if($Polish){                //抛光
            if(stristr($Polish,'Other')){
                $where .= " AND polish NOT IN ('I','EX','VG','GD','F')";
            }else{
                $where .= " AND polish IN (".$this->paramStr($Polish).")";
            }
        }
        if($Symmetry){      //对称
            if(stristr($Symmetry,'Other')){
                $where .= " AND symmetry NOT IN ('I','EX','VG','GD','F')";
            }else{
                $where .= " AND symmetry IN (".$this->paramStr($Symmetry).")";
            }
        }
        if($Fluor){//荧光
            if(stristr($Fluor,'Other')){
                $where .= " AND fluor NOT IN ('N','F','M','S','VS')";
            }else{
                $where .= " AND fluor IN (".$this->paramStr($Fluor).")";
            }
        }
        if(stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
            $where .= " AND (milk like '%无奶%' AND coffee like '%无咖%') ";
        }elseif(stristr($NaiKa,'无奶') and !stristr($NaiKa,'无咖')){
            $where .= " AND (milk like '%无奶%') ";
        }elseif(!stristr($NaiKa,'无奶') and stristr($NaiKa,'无咖')){
            $where .= " AND (coffee like '%无咖%') ";
        }

        if($Location){$where .=" AND `location` In (".$this->paramStr($Location).")";}          //所属地
        $where .= " AND `goods_number` >0";
        //print_r($_REQUEST['Stock']);die;
        if($_REQUEST['Stock']=='dinghuo' and empty($goods_sn)){//订货
            $where .= " AND `quxiang` IN ('订货','定货')  ";
        }elseif($_REQUEST['Stock']=='xianhuo' and empty($goods_sn)){//现货
            $where .= " AND `quxiang` NOT IN ('订货','定货','散货') AND `certificate_type` NOT IN('散货','国首') ";
        }elseif($_REQUEST['Stock']=='tejia_cert'){//特价 带证书
            $where .= " AND `quxiang` IN('优势') AND `certificate_type` = 'GIA' ";
        }elseif($_REQUEST['Stock'] == 'tejia_cert_no'){
            $where .= " AND `quxiang` IN('特价') AND `certificate_type` = '散货' ";
        }elseif($_REQUEST['Stock'] == 'no_cert'){
            $where .= " AND `certificate_type` IN('散货','国首')";
        }elseif($_REQUEST['Stock'] == 'SDG'){
            $where .= " AND type = 1";
        }elseif(stristr($Cert,'Other')){
            $where .= " AND `certificate_type` NOT IN ('GIA','HRD','IGI','NGTC')";
        }else{
            $where .= " AND `quxiang` NOT IN ('特价','散货')  AND `certificate_type` NOT IN('散货','国首')  ";
        }
        if (!empty($orderby)){  //排序
            if ($orderby=="shop_Price"){
                $orderby = "price";
            }else{
                $orderby = "{$orderby}";
            }
            $orderway = empty($orderway) ? 'ASC' : $orderway;
            $ordersql = "  $orderby $orderway,dia_discount ASC ";
        }else{  //默认排序
            if(!empty($Shape) or !empty($Color) or !empty($Clarity) or !empty($Cut) or !empty($Polish) or !empty($Symmetry) or !empty($Cert) or !empty($Flour) or !empty($Location)){
                $ordersql = " dia_discount ASC ";
            }
        }
        session('diamond_sql',$where);


        $count = $GL->where($where)->count();

        $Page = New \Think\AjaxPage($count,15,'setPage');
        $data = $GL->where($where)->limit($Page->firstRow.','.$Page->listRows)->order($ordersql)->select();
        foreach($data as $key=>$val){
            $data[$key]['shape'] = $this->getDiamondsShapeName($val['shape']);
            //会员折扣
            $userdiscount = getUserDiscount($_SESSION['web']['uid']);
            $price_zh     = $data[$key]['dia_global_price']*C('dollar_huilv');//每克拉的价格,人民币

            $dia_discount = get_luozuan_sale_jiadian($_SESSION['web']['uid'], $data[$key]['weight'], $data[$key]['dia_discount'] );
            //选择折扣价计算方式
            if(C('price_display_type') != 1) {
                $data[$key]['cur_price']    = formatRound( $price_zh * $dia_discount/100 , 2);;                //每克拉人民币价格
                $data[$key]['dia_discount'] = $dia_discount;                                     //最终折扣
                $data[$key]['price']        = formatRound( $price_zh * $dia_discount * $data[$key]['weight']/100, 2); //最终售价，单粒价格
            }else{
                //$dia_discount  = $data[$key]['dia_discount']   * (1+(C('luozuan_advantage') + $userdiscount)/100);
                $data[$key]['price']        = formatRound( $price_zh * $dia_discount * $data[$key]['weight']/100, 2); //最终售价，单粒价格
                $data[$key]['cur_price']    = 0;                //不显示价格
                $data[$key]['dia_discount'] = 0;                //不显示折扣
            }

            $data[$key]['certificateUrl'] = $this->formatReportURL($val['certificate_type'],$val['certificate_number'],$val['weight']);
            if($val['quxiang']=='优势'){
                $cRed = 'cRed';
                $data[$key]['quxiangTitle'] = '这颗钻石在钻明的货品库存中&#10;您提交订单后可以立即来我司提货';
            }elseif(stristr($val['quxiang'],'外借①')){
                $data[$key]['quxiangTitle'] = '这颗被外借到上海。&#10;提交订单后我们会尽快配货';
            }elseif(stristr($val['quxiang'],'外借②')){
                $data[$key]['quxiangTitle'] = '这颗被带到展会。&#10;提交订单后我们会尽快配货';
            }elseif(stristr($val['quxiang'],'外借')){
                $data[$key]['quxiangTitle'] = '这颗在深圳，被借出。&#10;提交订单后我们会尽快配货';
            }elseif(stristr($val['quxiang'],'现货')){
                $data[$key]['quxiangTitle'] = '这颗钻石是深圳现货&#10;您提交订单后三个小时左右即可来我司提货';
            }else{
                $data[$key]['quxiangTitle'] = $val['location'];
            }
        }
        //print_r($data);
        $dataList['data'] = $data;
        $dataList['total'] = $count;
        $dataList['language'] = array(
            'L55'=>L('L55')
            ,'L56'=>L('L56')
            ,'L82'=>L('L82')
            ,'L412'=>L('L412')
            ,'L84'=>L('L84')
            ,'L58'=>L('L58')
        );
        $dataList['page'] = $Page->show();
        $this->ajaxReturn($dataList);
    }

    /*获取钻石形状对应的编号 */
    public function getDiamondsShapeNo($shape){
        $shape = trim(strtolower($shape));  //转换为小写
        $shapeNo=0; //默认（标识非常规异形钻）
        if($shape=='圆形' or $shape=='rb' or $shape=='rd' or $shape=='formatRound' or $shape=='formatRounds'){$shapeNo=L('L24');}
        if($shape=='椭圆' or $shape=='ol' or $shape=='oval' or $shape=='ovals'){$shapeNo=L('L25');}
        if($shape=='马眼' or $shape=='mq' or $shape=='marquise' or $shape=='marquises'){$shapeNo=L('L26');}
        if($shape=='心形' or $shape=='ht' or $shape=='heart' or $shape=='hearts'){$shapeNo=L('L27');}
        if($shape=='水滴' or $shape=='pe' or $shape=='pear' or $shape=='pears'){$shapeNo=L('L28');}
        if($shape=='方形' or $shape=='公主方' or $shape=='pr' or $shape=='princess'){$shapeNo=L('L29');}
        if($shape=='祖母绿' or $shape=='em' or $shape=='emerald' or $shape=='emeralds'){$shapeNo=L('L30');}
        if($shape=='枕形' or $shape=='上丁方' or $shape=='上丁方形' or $shape=='ash' or $shape=='asscher' or $shape=='asschers' or $shape=='垫形' or $shape=='cu' or $shape=='cushion' or $shape=='cushions'){$shapeNo=L('L31');}
        if($shape=='雷迪恩' or $shape=='雷蒂恩' or $shape=='雷地恩' or $shape=='rd' or $shape=='rediant'){$shapeNo=L('L32');}
        if($shape=='梯方' or $shape=='rectangle' or $shape=='长方形'){$shapeNo=L('L33');}
        if($shape=='三角形' or $shape=='trilliant'){$shapeNo=L('L34');}
        return $shapeNo;
    }

    function formatReportURL($CertificateId,$CertificateNo,$weight){
        $url = '';
        switch (strtoupper($CertificateId)){//过滤证书参数

        case 'GIA'://1
            $url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=1";
            break;
        case 'IGI'://2
            $url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=2";
            break;
        case 'HRD'://3
            $url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=3";
            break;
        case 'NGTC'://4
            $url = "/Home/Search";
            break;
        default:
            $url = '/Home/Search';
            break;
        }
        return $url;
    }
    public function paramStr($param){   //mysql in 查询字符串处理
        $arr = explode(',',$param);
        $str = '';
        $count = count($arr);
        for($i=0 ; $i<$count ; $i++){
            if($i==0){
                $str = "'".$arr[$i]."'";
            }else{
                $str .= ",'".$arr[$i]."'";
            }
        }
        return $str;
    }

	//提交订制的判断
	public function submitDingzhiPanduan(){
		$step1 = (int)isset($_COOKIE['step1'])?$_COOKIE['step1']:0; //step1存放裸钻商品编号
        $step2 = (int)isset($_COOKIE['step2'])?$_COOKIE['step2']:0; //step2存放戒托商品编号

        if($step1<>0 and $step2<>0){
            $step1 = $this->getOneDiamondsData($step1);
            $step2 = $this->get_goods_info($step2); //获取商品详细信息

            if($step1['weight'] != $step2['associate_luozuan']['weights_name'] or $step1['shape']!=$step2['associate_luozuan']['shape_name']){
				$result['info'] = '主石的形状或重量不匹配！';
				$result['status'] = 0;
				$this->ajaxReturn($result);

			}
        }
	}
    public function add2cart(){
        $gids = $_POST['gid'];
        $dingzhi_id = I('dingzhiId');
        foreach($gids as $val){
            $goods    = M('goods_luozuan')->where("`gid`='".$val."' AND `goods_number` > 0 ") -> find();
            $goodsObj = D("GoodsLuozuan");//初始化默认的读取的是白钻的加点
            if($goods['luozuan_type'] == 1){
                //设置彩钻参数
                $goodsObj -> setLuoZuanPoint('0','1');
            }
            $goods = $goodsObj -> where("`gid`='".$val."' AND `goods_number` > 0 ") -> find();
            $error = array();
            if($goods){
                $goods      = getGoodsListPrice($goods,$_SESSION['web']['uid'],'luozuan', 'single');
                $goods_attr = serialize($goods);
                $data       = array();
                $data['goods_id'] = $goods['gid'];
                $data['goods_type'] = 1;
                $data[$this->userAgentKey] = $this->userAgentValue;
                $data['goods_attr'] = $goods_attr;
                $data['goods_sn'] = $goods['certificate_number'];
                $data['agent_id'] = C('agent_id');
				if($dingzhi_id){
					$data['dingzhi_id'] = $dingzhi_id;
					M('cart')->data($data)->add();
				}else{
					if(!M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_sn ='".$data['goods_sn']."'")->find()){
						M('cart')->data($data)->add();
					}
				}
            }
        }

        $result['count'] = M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."'")->count();

        $result['info'] = '添加成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);

    }

    //添加散货到购物车
    public function addSanhuo2cart(){

        $gids = $_POST['goods_id'];
        foreach($gids as $val){
            $GSobj = D('GoodsSanhuo');
            $where = array('goods_id'=>array('in',$val),'goods_weight'=>array('gt','0'));
            $goods = $GSobj->getInfo($where);
            if($goods){
                $goods = getGoodsListPrice($goods,$_SESSION['web']['uid'],'sanhuo', 'single');
                $goods_attr = serialize($goods);
                $data = array();
                $data[$this->userAgentKey] = $this->userAgentValue;
                $data['goods_id'] = $goods['goods_id'];
                $data['goods_type'] = 2;
                $data['goods_attr'] = $goods_attr;
                $data['goods_sn'] = $goods['goods_sn'];
                $data['goods_number'] = 0;
                $data['agent_id'] = C('agent_id');
                if(!M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_id ='".$val."' AND goods_type=2")->find()){
                    M('cart')->data($data)->add();
                }
            }
        }
        $result['count'] = M('cart')->where("agent_id='".C('agent_id')."' AND " . $this->userAgent)->count();
        $result['info'] = '添加成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);

    }

    // 显示购物车列表
    public function cart(){
        $huilv = C('dollar_huilv');
        $Cart  = M('cart');
        $where = " agent_id='".C('agent_id')."' and uid = '".$_SESSION['web']['uid']."' AND goods_type =1";
        $diamondDataList = $Cart->where($where)->select();
        if($diamondDataList){
            foreach($diamondDataList as $key=>$val){
                $diamondDataList[$key]['goods'] = $goods = unserialize($val['goods_attr']);
                $diamondDataList[$key]['goods']['certificate_url'] = $this->formatReportURL($goods['certificate_type'],$goods['certificate_number'],$goods['weight']);
                $diamondDataList[$key]['goods']['meikadanjia'] = $goods['dia_global_price']*$huilv;
                $Dtotal_amount += formatRound($goods['price'],2);
                $Dtotal_weight += $goods['weight'];
            }
        }

        $sanhuoDataList = $Cart->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."' AND goods_type=2")->select();
        if($sanhuoDataList){
            foreach($sanhuoDataList as $key=>$val){
                $sanhuoDataList[$key]['goods'] = $goods = unserialize($val['goods_attr']);
                $sanhuoDataList[$key]['goods']['goods_type'] = $this->getGoodsTypeByTid($goods['tid']);
                $sanhuoDataList[$key]['goods']['goods_sn'] = $goods['goods_sn'];
                $sanhuoDataList[$key]['goods']['goods_weight'] = $goods['goods_weight'];
                $sanhuoDataList[$key]['goods']['weights'] = $goods['weights'];
                $sanhuoDataList[$key]['goods']['color'] = $goods['color'];
                $sanhuoDataList[$key]['goods']['clarity'] = $goods['clarity'];
                $sanhuoDataList[$key]['goods']['cut'] = $goods['cut'];
                $sanhuoDataList[$key]['goods']['goods_price'] = $goods['goods_price'];
                $sanhuoDataList[$key]['price'] = formatRound($goods['goods_price']*$val['goods_number'],2);
                $Stotal_amount += formatRound($sanhuoDataList[$key]['price'],2);
                $Stotal_weight += $sanhuoDataList[$key]['goods_number'];
            }
        }
        $this->count           = M('cart') -> where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."'") -> count();
        $this->sanhuoDataList  = $sanhuoDataList;
        $this->diamondDataList = $diamondDataList;

        $this->Dtotal_amount = $Dtotal_amount;
        $this->Stotal_amount = formatRound($Stotal_amount,2);
        $this->Dtotal_weight = $Dtotal_weight;
        $this->Stotal_weight = $Stotal_weight;
        $this->total_amount  = $Dtotal_amount + $Stotal_amount;
        $this->total_weight  = $Dtotal_weight + $Stotal_weight;
        if($this->trader_id  > 0 ){
            $this->display('/Order/orderCart');
        }else{
            $this->display();
        }
    }

    /* 购物车 第二步 */
    public function cartStep2(){
        $dataList = $this->getCartByUID();

        $this->diamondDataList = $dataList['diamond']['data'];
        $this->sanhuoDataList = $dataList['sanhuo']['data'];
        //print_r($dataList['sanhuo']);
        $this->diamond = $dataList['diamond'];
        $this->sanhuo = $dataList['sanhuo'];
        $this->total_amount = $dataList['total_amount'];
        $user_address = M('user_address')->where("uid='".$_SESSION['web']['uid']."' AND country_id != 0 AND title !=''".' and agent_id = '.C('agent_id'))->select();
        $this->user_address = $user_address;
        $this->display();
    }

    public function orderComplete(){
        $order = M('order')->where("agent_id='".C('agent_id')."' and uid='".$_SESSION['web']['uid']."'")->order('create_time DESC')->find();

        $this->order = $order;
        $this->display();
    }

    public function getCartByUID(){
        $Cart  = M('cart');
        $huilv = 6.35;
        $dataList['sanhuo']['data']  = $Cart->where("uid='".$_SESSION['web']['uid']."' AND agent_id='".C('agent_id')."' AND goods_type=2")->select();
        $dataList['diamond']['data'] = $Cart->where("uid='".$_SESSION['web']['uid']."' AND agent_id='".C('agent_id')."' AND goods_type=1")->select();
        $dataList['count'] = $Cart->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."'")->count();
        if($dataList['sanhuo']['data']){
            foreach($dataList['sanhuo']['data'] as $key=>$val){
                $dataList['sanhuo']['data'][$key]['goods_attr']   = $goods = unserialize($val['goods_attr']);
                $dataList['sanhuo']['data'][$key]['goods_type']   = $this->getGoodsTypeByTid($goods['tid']);
                $dataList['sanhuo']['data'][$key]['goods_sn']     = $goods['goods_sn'];
                $dataList['sanhuo']['data'][$key]['goods_weight'] = $goods['goods_weight'];
                $dataList['sanhuo']['data'][$key]['weights'] = $goods['weights'];
                $dataList['sanhuo']['data'][$key]['color'] = $goods['color'];
                $dataList['sanhuo']['data'][$key]['clarity'] = $goods['clarity'];
                $dataList['sanhuo']['data'][$key]['cut'] = $goods['cut'];
                $dataList['sanhuo']['data'][$key]['goods_price'] = $goods['goods_price'];
                $dataList['sanhuo']['data'][$key]['clarity'] = $goods['clarity'];
                $dataList['sanhuo']['data'][$key]['price'] = formatRound($goods['goods_price']*$val['goods_number'],2);
                $dataList['sanhuo']['total_amount'] += $dataList['sanhuo']['data'][$key]['price'];
                $dataList['sanhuo']['total_weight'] += $val['goods_number'];
            }
        }
        if($dataList['diamond']['data']){
            foreach($dataList['diamond']['data'] as $key=>$val){
                $dataList['diamond']['data'][$key]['goods'] = $goods = unserialize($val['goods_attr']);
                $dataList['diamond']['data'][$key]['goods']['certificate_url'] = $this->formatReportURL($goods['certificate_type'],$goods['certificate_number'],$goods['weight']);
                $dataList['diamond']['data'][$key]['goods']['meikadanjia'] = $goods['dia_global_price']*$huilv;
                $dataList['diamond']['total_amount'] += $goods['price'];
                $dataList['diamond']['total_weight'] += $goods['weight'];
            }
        }

        $dataList['total_amount'] = $dataList['diamond']['total_amount'] + $dataList['sanhuo']['total_amount'];
        $dataList['total_weight'] = $dataList['diamond']['total_weight'] + $dataList['sanhuo']['total_weight'];
        return $dataList;

    }

    /* 根据重量改变货品的价格  */
    public function getPriceByWeight(){
        $GSobj = D('GoodsSanhuo');
        $where = array('goods_id'=>array('in',$_POST['goods_id']));
        $goods = $GSobj->getInfo($where);
        $data  = array();
        $data['status']     = 1;
        if($_POST['weights'] > $sanhuo['goods_weight']){
            $data['msg']    = "购买数量不能大于库存数量";
            $data['status'] = 0;
        }
        if($_POST['weights'] <0){
            $data['msg'] = "购买数量不能低于0";
            $data['status'] = 0;
        }
        $data['price']        = formatRound($_POST['weights']*$sanhuo['goods_price'],2);
        $cart['goods_number'] = $_POST['weights'];
        if( $data['status']  != 0 ){
            M('cart') -> where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."' AND goods_type=2 AND goods_id='".$_POST['goods_id']."'")->save($cart);
            $dataList =  M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."'")->select();
            if($dataList){
                foreach($dataList as $key=>$val){
                    $dataList[$key]['goods'] = $goods = unserialize($val['goods_attr']);
                    if($val['goods_type'] == 1){  //1表示裸钻
                        $data['diamond_total_amount'] += $goods['price'];
                        $data['total_weight'] += $goods['weight'];
                    }else if($val['goods_type']==2){
                        $data['total_weight'] += $val['goods_number'];
                        $data['sanhuo_total_amount'] += formatRound($goods['goods_price'] * $val['goods_number'],2);
                    }
                }
                $data['total_amount'] = $data['diamond_total_amount'] + $data['sanhuo_total_amount'];
            }
        }
        $this->ajaxReturn($data);
    }


    public function getGoodsTypeByTid($tid){
        return M("goods_sanhuo_type")->field('type_name')->where("tid='".$tid."'")->find();
    }


    /* 清空购物车 */
    public function clearCart(){
        $result['result'] = M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."'")->delete();
        $result['result'] = true;
        if($result['result']){
            $result['info']   = "购物车清空成功";
            $result['status'] = 1;
            $this->ajaxReturn($result);
        }else{
            $result['info']   = "购物车清空失败";
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }
    }

    /* 删除购物车中的货品 */
    public function clearOne2cart(){
        $result['result']     = M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."' AND goods_id='".$_POST['gid']."' AND goods_type = '".$_POST['goods_type']."'")->delete();
        $result['result']     = true;
        if($result['result']){
            $result['info']   = "商品删除成功";
            $result['status'] = 1;
            $this->ajaxReturn($result);
        }else{
            $result['info']   = "商品删除失败";
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }
    }

    /* 生成订单函数 **/
    public function createOrder($data){

        if($data== '' || $data == null){
            return false;
        }

        $Order = M('order');
        $Order->startTrans();
        $data['agent_id'] = C('agent_id');
        $order_id = $Order->data($data)->add();
        $order_goods_add = true;
        $order_delete = true;
        foreach($data['order_goods'] as $key=>$val){
            $val['order_id'] = $order_id;
            $val['agent_id'] = C('agent_id');
            if(!M('order_goods')->data($val)->add()){
                $order_goods_add = false;
            }
        }
        foreach($data['order_goods'] as $key=>$val){
            if(!M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['web']['uid']."' AND goods_id='".$val['goods_id']."'")->delete()){
                $order_delete = false;
            }
        }
        if($order_id && $order_goods_add && $order_delete){
            $Order->commit();
            return true;
        }else{
            $Order->rollback();
            return false;
        }

    }

    /*根据形状编号获取钻石形状名称 by aibhsc*/
    function getDiamondsShapeName($shapeID="ROUND"){
        switch(trim(strtoupper($shapeID))){
            case "ROUND":
                $shapeName='圆形';
                break;
            case "OVAL":
                $shapeName='椭圆';
                break;
            case "MARQUISE":
                $shapeName='马眼';
                break;
            case "HEART":
                $shapeName='心形';
                break;
            case "PEAR":
                $shapeName='水滴';
                break;
            case "PRINCESS":
                $shapeName='方形';
                break;
            case "EMERALD":
                $shapeName='祖母绿';
                break;
            case "CUSHION":
                $shapeName='枕形';
                break;
            case "RADIANT":
                $shapeName='雷迪恩';
                break;
            case "BAGUETTE":
                $shapeName='梯方';
                break;
            case "SQUARE EMERALD":
                $shapeName='方形祖母绿';
                break;
            default:
                $shapeName='其它';
                break;
        }
        return $shapeName;
    }

    public function getAjaxLuozuanByGid(){
        $gid       = I("gid",0);
        $luozuan   = M("goods_luozuan")->where("gid=$gid")->find();
		if($luozuan){
			$point = 0;
			if($luozuan['agent_id'] != C('agent_id')){
				if($luozuan['luozuan_type'] == 1){
					$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
				}else{
					$point = D("GoodsLuozuan") -> setLuoZuanPoint();
				}
			}
			$luozuan['dia_discount'] += $point;
            $luozuan = getGoodsListPrice($luozuan, $_SESSION['web']['uid'], 'luozuan', 'single');
            $luozuan['shape'] = M('goods_luozuan_shape')->where("shape = '".$luozuan['shape']."'")->getField('shape_name');
        }

        $data = array("status"=>1,"data"=>$luozuan);
        if(!$data){
            $data['status'] = 0;
        }
        //print_r($data);
        $this->ajaxReturn($data);
    }

    /* 定制页面函数 */
    public function diy(){
        $p = I('p',1);
        $n = I('n',20);
        // 查询所有戒托信息
        $this->catShow    = 0;
        $M                = D('Common/Goods');
        $M               -> set_where('goods_type', '4' );
        $M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
        $M               -> _limit($p,$n);
        $count            = $M -> get_count();
        $recommendZuantuo = $M -> getList();
        if($recommendZuantuo){
            $recommendZuantuo = $M->getProductListAfterAddPoint($recommendZuantuo,$_SESSION['web']['uid']);
        }
        $this->total            = $count;
        /*===========================定制流程编号处理  开始===========================*/
        $step1 = (int)isset($_COOKIE['step1'])?$_COOKIE['step1']:0; //step1存放裸钻商品编号
        $step2 = (int)isset($_COOKIE['step2'])?$_COOKIE['step2']:0; //step2存放戒托商品编号
        if($step1<>0 and $step2<>0){
            $diy_step = 'step11';
            $step1 = $this->getOneDiamondsData($step1);
            $step2 = $this->get_goods_info($step2); //获取商品详细信息
            $step3 = array('luozuan'=>$step1['goods_price'],'jietuo'=>$step2['goods_price'],'total'=>$step1['goods_price']+$step2['goods_price']);
        //print_r($step3);die;
        }elseif($step1<>0 and $step2==0){
            $diy_step = 'step10';
            $step1 = $this->getOneDiamondsData($step1);
            $step3 = array('luozuan'=>$step1['goods_price'],'total'=>$step1['goods_price']);
        //print_r($step3);die;
        }elseif($step1==0 and $step2<>0){
            $diy_step = 'step01';
            $step2 = $this->get_goods_info($step2); //获取商品详细信息
            $step3 = array('jietuo'=>$step2['goods_price'],'total'=>$step2['goods_price']);
        }else{
            $diy_step = 'step00';
        }

        $this->step1 = $step1;
        $this->step2 = $step2;
        $this->step3 = $step3;

//        $this->prepareHtmlByDefault(4,3);

        /*===========================定制流程编号处理 结束===========================*/
        $this->display();
    }

    public function getAjaxZuantuo(){	
        $step1 = (int)isset($_COOKIE['step1'])?$_COOKIE['step1']:0; //step1存放裸钻编号
        if($step1){
            $diamond   = M("goods_luozuan")->where( "gid=".$step1 ) -> find();
            $goods_Obj = D("GoodsLuozuan");
            if($diamond['luozuan_type'] == 1){
                $goods_Obj -> setLuoZuanPoint('0','1');
            }
            $associateLuozuan = $goods_Obj -> where( "gid=".$step1 ) -> find();
			$shape_id         = D('goods_luozuan_shape') -> where(array('shape'=>$associateLuozuan['shape']))->getField('shape_id');
            $weight           = floatval($associateLuozuan['weight']);
        }

        $M             = D('Common/Goods');
        $M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
        $p             = $_POST['p'] = I('page','1');
        $n             = I('n','20');
        
        if(empty($_POST['sortType'])){
            $sortType = 'zm_goods.create_time';
        }else{
            $sortType      = 'zm_goods.'.I('post.sortType','create_time');
        }

        if(empty($_POST['sortValue'])){
            $sortValue     = 'DESC';
        }else{
            $sortValue     = I('sortValue','DESC');
        }

        if(empty($_POST['Type'])){
            $Type          = 'all';
        }else{
            $Type          = I('Type','all'); 
        }

        if(empty($_POST['Material'])){
            $Material      = 'all';
        }else{
            $Material      = I('Material','all');
        }

        if(empty($_POST['Price'])){
            $Price         = '0-9000000000';
        }else{
            $Price         = I('Price','0-9000000000');
        }

       
		$main_stone    =          I('main_stone');
        $M             -> set_where( 'zm_goods.goods_type' , '4' );
        if($Price){
            $temp      = explode('-',$Price);
            $M         -> set_where( 'zm_goods.goods_price' , " between '$temp[0]' and '$temp[1]' " ,true);
        }

		//钻托类别
		if($Type){
			if($Type=='1'){
				$M	-> _join( " join zm_goods_associate_attributes as aa131 on aa131.attr_id = '13' and ( aa131.attr_code & '1' ) and aa131.goods_id = zm_goods.goods_id ",false);
			}else if($Type=='2'){
				$M	-> _join( " join zm_goods_associate_attributes as aa132 on aa132.attr_id = '13' and ( aa132.attr_code & '2' ) and aa132.goods_id = zm_goods.goods_id ",false);
			}else if($Type=='3'){
				$M            -> set_where( 'zm_goods.category_id' , '77' );
			}else if($Type=='4'){
				$M            -> set_where( 'zm_goods.category_id' , '62' );
			}
		}
         
        //材质
        if($Material != 'all'){
            $material_id_array = M('goods_material')->where('material_name like \''.$Material.'\' and agent_id in ('.($M->get_where_agent()).')')->field('material_id')->select();
            $material_id = "";
           
            foreach($material_id_array as $value){
                $material_id = $material_id.','.$value['material_id'];
            }
            $material_id = substr($material_id,1);      
            $M  -> set_where( "zm_goods.goods_id", " IN( 
                    select zm_goods_associate_info.goods_id 
                    from zm_goods_associate_info  
                    where zm_goods_associate_info.material_id in (".$material_id.") and zm_goods_associate_info.agent_id in (".($M->get_where_agent()).")
                )", true);
            
        }
        if($sortType == ''){
            $sortType  = "zm_goods.create_time";
        }
        if($sortValue == ''){
            $sortValue = "DESC";
        }
        $M -> _order($sortType,$sortValue);
        if($step1){
//            $M  -> _join( " join zm_goods_associate_luozuan as GAL on GAL.goods_id = zm_goods.goods_id and GAL.shape_id = '".$shape_id."' and GAL.weights_name = '".$weight."'" , true);
        }

        //主石属性
        if($main_stone && $main_stone!='all'){
            $before_the_comma=strtok($main_stone, ',');          
            $after_the_comma =substr($main_stone,2);
            $M  -> _join( " join zm_goods_associate_attributes as aa0 on aa0.attr_id = '$before_the_comma' and ( aa0.attr_code & '$after_the_comma' ) and aa0.goods_id = zm_goods.goods_id ",false);
        }

        $count    = $M -> get_count();
        $page     = NEW \Think\AjaxPage($count,$n,"setPage");
        $M       -> _limit($p,$n);
        $zuantuo  = $M -> getList(false,false,'zm_goods.goods_name,zm_goods.goods_id,zm_goods.thumb,zm_goods.goods_price,zm_goods.goods_type');
		//根据符合筛选的goods_id查询表格
        if($zuantuo){
            $zuantuo = $M->getProductListAfterAddPoint($zuantuo,$_SESSION['web']['uid']);
			foreach($zuantuo as $key=>$val){
				if($val['activity_status'] == 1){
					$zuantuo[$key]['goods_price'] = $zuantuo[$key]['activity_price'];
				}
			}
        }

        $data['data']  = $zuantuo;
        $data['page']  = $page->show();
        $data['total'] = $count;
        $this->ajaxReturn($data);
    }

    // 获取单个裸钻信息
    public function getOneDiamondsData($step){

        $diamond   = M("goods_luozuan")->where( "gid=".$step )->find();
        $goods_Obj = D("GoodsLuozuan");
        if($diamond['luozuan_type'] == 1){
            $goods_Obj -> setLuoZuanPoint('0','1');
        }
        $diamond =  $goods_Obj -> where( "gid=".$step )->find();
        if($diamond){
            $diamond                = getGoodsListPrice($diamond, $_SESSION['web']['uid'], 'luozuan', 'single');
            $diamond['goods_price'] = $diamond['price'];
            $diamond['shape']       = M('goods_luozuan_shape')->where("shape = '".$diamond['shape']."'")->getField('shape_name');
        }
        return $diamond;
    }

    // 获取定制步骤2中的单个产品信息
    public function get_goods_info($step){

        $M         = D('Common/Goods');
        $data      = $M -> get_info($step,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
        if($_COOKIE["goods_id_".$step] and $_COOKIE["goods_id_".$step]!='undefined'){
            $params            = explode('_',$_COOKIE["goods_id_".$step]);
            $materialId        = $params[0];
            $diamondId         = $params[1];
            $deputystoneId     = $params[2];
            $diamondWeight     = $params[3];
            $hand              = $params[4];
            $word              = $params[5];
            $sd_id             = intval($params[6]);
            $data['sd_id']     = $sd_id  ;
            $data['sd_images'] = '';
            if($sd_id > 0){  
                $S         = D('Common/SymbolDesign');
                $info      = $S -> getInfo($sd_id);
                $data['sd_images'] = $info['images_path'];
            }
            
            list($material,$associateInfo,$associateLuozuan,$deputystone) = $M -> getJingGongShiData($step,$materialId,$diamondId,$deputystoneId);
			$data['goods_price']           = $M -> getJingGongShiPrice($material,$associateInfo,$associateLuozuan,$deputystone);
			$data['associate_info']        = $associateInfo;
			$data['associate_luozuan']     = $associateLuozuan;
            $data['associate_deputystone'] = $deputystone;
			$data['hand']                  = $hand;
			$data['word']                  = $word;
        }
        $data             = getGoodsListPrice($data, $_SESSION['web']['uid'], 'consignment', 'single');

        return $data;
    }

    //成品
    public function goodsCat(){
        if(C('new_rules')['zgoods_show']){
            $this->_goodsCat();exit;
        }
        $categoryId = I("cid",0);
        if($categoryId>0) {    //如果有$categoryId
            $this->prepareHtmlByCat($categoryId, 3, 1);//3是goodstype,1是attr_type
        }else{              //如果没有$categoryId
            $this->prepareHtmlByDefault(3,1);
        }
        $this->display();
    }

    protected function _goodsCat(){
        pleaseLogin();
        $GC = D('Common/BanfangUrl');
        $cate_lists = $GC->getTreeLists(array('na_status'=>1));
        $this->banfang_jewelry = I('attr_id');


        $left_cates = array_values($cate_lists);
        $this->left_cates = $left_cates[0]['sub'];
        $right_attrs = $GC->setRightPartConditions();
        $this->right_attrs = $right_attrs;
        //$this->search_location = 1;
        $this->display('dingzhi/zhouliufu/Goods/goodsCat');
    }


    //定制产品
    public function goodsCategory(){

        $categoryId = I("gcid",0);
        if($categoryId == 0){
            $categoryId = M('goods_category_config')->where("agent_id=".C("agent_id")." AND pid<>0 AND is_show=1")->order('sort_id')->getField("category_id");
        }
        $navstr  = "<a href='/Home/Index/index'>首页</a>";
        $navstr .= $this->getRecCategory($categoryId,"", 4);
        $this->nav_cate =  $navstr ;
        if($categoryId>0) { // 如果有$categoryId
            $this->prepareHtmlByCat($categoryId, 4, 3);// 4是goodstype,3是attr_type
        }else{              // 如果没有$categoryId
            $this->prepareHtmlByDefault(4,3);
        }
        $this->display();
    }

      //系列产品  钻明钻石 网络工程师  zhy 2016年10月13日 17:26:54 改。
    public function goodsSeries(){
		$gsId     = I("gsid",0);
		if(IS_AJAX){
				$gSeriesM  = M("goods_series");
				$p         = I('page',1);
				$n         = I('n',20);
				$sortType  = I('post.sortType')?I('post.sortType'):'create_time';
				$sortValue = I('post.sortValue','ASC')?I('post.sortValue','ASC'):'ASC';

				if($gsId>0) {
					$gsId = intval($gsId);
					$nav_cate = $gSeriesM ->where("goods_series_id = ".$gsId.' and agent_id = '.C('agent_id'))-> getField("series_name");
				} else {
					$gsId  = 0;
					$nav_cate = "精品系列";
				}
				//所有系列
				$goodsType_data = $gSeriesM ->where('agent_id = '.C('agent_id'))-> select();
				//显示此分类的所有产品
				$M         = D('Common/Goods');
				if( $gsId > 0 ){
					$M    -> set_where( 'goods_series_id' , "$gsId" );
				}else{
					$M    -> set_where( 'goods_series_id' , " > 0 ",true);
				}
                $M        -> set_where( 'goods_type' , '3' );
                $M        -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
                $M        -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
				$count     = $M -> get_count();
				$page      = NEW \Think\AjaxPage($count,$n,"new_submitData_just_for_goodsSeries");
				$M        -> _limit($p,$n);
				$M        -> _order($sortType,$sortValue);
				$products  = $M -> getList();

				if($products){
					$products = $M->getProductListAfterAddPoint($products,$_SESSION['web']['uid']);
				}

				$data['goodsType_data']=$goodsType_data;
				$data['nav_cate']=$nav_cate;
				$data['count'] = $count;
				$data['data']  = $products;
				$data['page']  = $page->show();
				$this->ajaxReturn($data);
		}else{

				$this -> gsId      = $gsId;
				$this -> display();

		}

    }

    public function setFilterAttrCookie($cateAttrs, $goodsType){
        foreach($cateAttrs as $val){
            $filterAttributes[]= C("agent_id")."_".$val['category_id']."_".$goodsType."_".$val['attr_id'];
        }
		//2016-4-28 fangkai 由于$cateAttrs二维数组键不一定从零开始，固修改为获取第一个数组中的category_id；
		$category_info = reset($cateAttrs);
		$category_id   = $category_info['category_id'];
        $cookieKey     = C("agent_id")."_".$category_id."_".$goodsType."_"."filterAttbutes";
        $cookieVal     = implode(",", $filterAttributes);
        setcookie($cookieKey, $cookieVal, time() + 30*86400, '/'); //保存1个月
    }

    public function setCommonFilterAttrCookie($cateAttrs, $goodsType, $categoryId){
        foreach($cateAttrs as $val){
            $filterAttributes[]=C("agent_id")."_".$categoryId."_".$goodsType."_".$val['attr_id'];
        }
        $cookieKey = C("agent_id")."_".$categoryId."_".$goodsType."_"."filterAttbutes";
        $cookieVal = implode(",", $filterAttributes);
        setcookie($cookieKey, $cookieVal, time() + 30*86400, '/'); //保存1个月
    }


   public function prepareHtmlByCat($categoryId, $goodsType, $attrType){
        $pid = $this -> getCatPid($categoryId);
        if($pid==='0'){ //如果是顶级分类,获取所有子类的共同筛选属性
            $cateAttrs = getProductCommonAttrByCat($categoryId, $attrType);
            $this->setCommonFilterAttrCookie($cateAttrs, $goodsType, $categoryId);  //设置筛选的属性集合的cookie
        }
        else{       //如果是二级分类
            $cateAttrs = getProductAttrByCat($categoryId, $attrType); //根据CategoryId,attr_type获取筛选属性,1 表示成品属性2成品规格3金工石属性
            $this -> setFilterAttrCookie($cateAttrs, $goodsType); //设置筛选的属性集合的cookie
        }
        $this->cateAttrs = $cateAttrs;
        if($goodsType==3){
            $this->cat_show = 1;    //表示是珠宝成品
            $this->cid   = $categoryId;
            $goods_seriesM       = M('goods_series');
            $goods_series_array  = $goods_seriesM ->where(' agent_id = '.C('agent_id'))->select();
            if(empty($goods_series_array)){
                $this->goods_series_array = null;
            }else{
                $this->goods_series_array = $goods_series_array;
            }
        }else{
            $this->gcid  = $categoryId;
            $this->cid   = $categoryId;
        }
        $this->tid       = C("agent_id");
        $this->goodsType = $goodsType;
        return $this;
    }

    public function getCatPid($categoryId){
        return M('goods_category') -> where('category_id="'.$categoryId.'"') -> getField('pid');
    }

    public function prepareHtmlByDefault($goodsType, $attrType){
        $categoryId = M('goods_category_config') -> where("agent_id=".C("agent_id")." AND pid<>0 AND is_show=1") -> order('sort_id') -> getField("category_id");
        $this      -> prepareHtmlByCat($categoryId, $goodsType, $attrType);
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

    public function getZmCategorys($parent_id){
        $categorys = M("goods_category_config")->where("parent_id=$parent_id".' and agent_id = '.C('agent_id'))->select();
        $where = "1=1";
        if($categorys){
            foreach($categorys as $key=>$val){
                $temp[] = $val['category_id'];
            }
            $categorys_str = implode(",",$temp);
            $where .= " AND category_id IN(".$categorys_str.")";
        }
        $categorys = _arrayRecursive($categorys,'category_id','pid');
        return $categorys;
    }


    public function getCategorys($gcid,$parent_id){

        $where = "agent_id=".C('agent_id');
        if($gcid >0 ){
            $where .= " AND (category_id = $gcid OR pid = $gcid)";
        }
        $categorys = M("goods_category_config")->where($where)->select();
        if($categorys){
            foreach($categorys as $key=>$val){
                $temp[] = $val['category_id'];
            }
            $categorys_str = implode(",",$temp);
        }else{
            $categorys_str = "";
        }
        return $categorys_str;
    }

    public function getCategoryNameByCategoryId($gcid){
        return M("goods_category_config")->where("category_id=$gcid")->getField("name_alias");
    }

    //ajax 获取钻明系统的产品列表
    public function getGoodsByCategoryId(){
        $gcid      = I("gcid",0);
        $p         = I("p","1");
        $n         = I("n","8");
        $categorys = $this->getCategorys($gcid,$this->traderId);
        $M         = D('Common/Goods');
        $M        -> set_where('zm_goods.category_id',"$categorys");
        $M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
        $count     = $M -> get_count();
        $page      = NEW \Think\AjaxPage($count,$n,'setPage');
        $M       -> _limit($p,$n);
        $products  = $M -> getList();
        if($products){
            $products = $M->getProductListAfterAddPoint($products,$_SESSION['web']['uid']);
        }
        $data['categoryName'] = $this->getCategoryNameByCategoryId($gcid);
        $data['data'] = $products;
        $data['count'] = $count;
        $data['page'] = $page->show();
        $this->ajaxReturn($data);
    }

    public function getAssociateDeputystonePriceByGadid(){
         $gid        = I("gid");
         $M          = D('Common/Goods');
         $info       = $M -> get_info($gid,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
         $materialId = I("materialId",0);
         $luozuanId  = I("diamondId",0);
         $goods_type = I("goods_type",4);
         $gadId      = I("gadId");
         list($material,$associateInfo,$luozuan,$deputystone) = $M -> getJingGongShiData($gid,$materialId,$luozuanId,$gadId);
         $info['goods_price'] = $M->getJingGongShiPrice($material, $associateInfo,$luozuan,$deputystone);
         $info                = getGoodsListPrice($info, $_SESSION['web']['uid'], 'consignment', 'single');
		 //如果为活动商品，则为活动价格，否则为正常售卖价格
		if(in_array($info['activity_status'],array('0','1'))){
			$price = $info['activity_price'];
		}else{
			$price = $info['goods_price'];
		}
         $this -> ajaxReturn(array('price'=>$price));
    }

    public function getAssociateLuozuanByMaterialId(){
        $gid              = I("gid",0);
        $material_id      = I("material_id",0);
        $goods_type       = I("goods_type",4);
        $M                = D('Common/Goods');
        $info             = $M -> calculationPoint($gid);
        $associateLuozuan = $M -> getGoodsAssociateLuozuanAfterAddPoint(" goods_id = $gid AND zm_goods_associate_luozuan.material_id = $material_id ",'list');
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

    public function getDiamondByCart(){
        $shape_id = I("shape_id",0);
        $weight   = I("weight",0);
        $cartId   = I("cartId",0);
        if(!empty($shape_id)){
            $shape_id = sprintf("%03d",$shape_id);
            $shape    = $this -> getShapeNoDiamonds($shape_id);
        }
        $goods_attr      = M('cart')->where("agent_id='".C('agent_id')."' and id=$cartId")->getField("goods_attr");
        $goods_attr_ser  = unserialize($goods_attr);
        $minWeight       = $weight-0.02;
        $maxWeight       = $weight+0.02;
		$where['weight'] = array(array('egt',$minWeight),array('elt',$maxWeight));
        if($shape){
			$where['shape']      = $shape;
        }
		$where['goods_number']   = array('gt',0);
		$where['luozuan_type']   = 0;

        $data['data']['luozuan'] = D("GoodsLuozuan")->where($where)->order("price ASC") -> limit(0,20)->select();
		//获取裸钻售卖价格
		$data['data']['luozuan'] = getGoodsListPrice($data['data']['luozuan'],$_SESSION['web']['uid'],'luozuan','all');

		$where['luozuan_type']   = 1;
		$goodsluozuanobject      = new \Common\Model\GoodsLuozuanModel(1);
		$data['data']['caizuan'] = $goodsluozuanobject->where($where)->order("price ASC")->limit(0,20)->select();
		//获取彩钻售卖价格
		$data['data']['caizuan'] = getGoodsListPrice($data['data']['caizuan'],$_SESSION['web']['uid'],'luozuan','all');
        $data['price_display_type'] = C('price_display_type');
        $this->ajaxReturn($data);
    }

    public function getHistoryLists(){
        $uid = $_SESSION['web']['uid'];
        $goods_type = I('goods_type',-1);
        $agent_id = C('agent_id');
        $session_id = session_id();
        $GH = D('Common/History');
        $history_conditions = array(
            'uid'        => $uid,
            'agent_id'   => $agent_id,
            'session_id' => $session_id,
            'limit'      =>'0,100',
            'goods_type' => $goods_type
        );
        $goods_history = $GH->getMoreHistoryLists($history_conditions);
        $this->goods_history = $goods_history;
        $this->goods_type    = $goods_type;
        $this -> display();
    }
    public function getCollectionList(){
        $param = array(
            'uid'=>$_SESSION['web']['uid'],
            'agent_id'=>C('agent_id')
        );
        if(empty($param['uid'])){
            $this->redirect('Public/login');
        }
        $product               = D('Common/Collection')->getCollectionList($param);
        $this->product         = $product;
        $this->display();
    }
    //新增收藏
    public function addToCollection(){
        //$type_from代表来源
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
            $param['uid'] = $_SESSION['web']['uid'];
            $result = D('Common/Collection')->addCollection($param);
        }else{
            $result = array(
                'info'   => '操作失败',
                'status' => 0
            );
        }
        $this->ajaxReturn($result);
    }
    //删除收藏
    public function deleteColletion(){
        $data = array('status'=>0,'msg'=>"删除失败");
        $id_arr = I("id",0);
        if(!empty($id_arr) && is_array($id_arr)){
            $delete_where = 'id in ("'.implode('","',$id_arr).'")';
            if(M('collection')->where($delete_where)->delete()){
                $data['status'] = 100;
                $data['msg'] = "删除成功";
            }
        }
        $this->ajaxReturn($data);
    }

    //收藏加入购物车
    public function addCollectionToCart(){
        $data = array('status'=>0,'msg'=>"操作失败");
        $id_arr = I("id",0);
        if(!empty($id_arr) && is_array($id_arr)){
            $data = D('Common/Collection')->addCollectionToCart($id_arr);
        }
        $this->ajaxReturn($data);
    }

    //删除历史记录
    public function deleteHistory(){
        $agent_id = C("agent_id");
        $uid		= $_SESSION['web']['uid'] ? $_SESSION['web']['uid'] : 0;
        $data = array('status'=>0,'msg'=>"删除失败");
        $id_arr = I("id", 0);
        $delete_all = I("delete_all", 0);
        $session = session_id();
        $session = $session ? $session : '';
        $delete_where = 'uid="'.$uid.'" AND agent_id='.$agent_id;
        if($uid<=0 && !empty($session)){
            $delete_where .= ' AND session="'.$session.'"';
        }

        //判断是删除部分还是全部
        if(!$delete_all){
            if(!empty($id_arr) && is_array($id_arr)){
                $delete_where .= ' AND history_id in ("'.implode('","',$id_arr).'")';
            }else{
                $delete_where .= ' AND history_id=0';
            }
        }
        //登录或者有session_id才允许删除，否则不删除
        if($uid>0 || $session){
            if(M('history')->where($delete_where)->delete()){
                $data['status'] = 100;
                $data['msg'] = "删除成功";
            }
        }
        $this->ajaxReturn($data);
    }

    public function getGoodsListsNew(){

        $conditions = array(
            'p'=>intval(I('p')),
            'n'=>intval(I('n')),
            'sort_type'=>intval(I('sort_type')),
            'sort_id'=>intval(I('sort_id')),
            'attr_id'=>I('attr_id'),
            'search_rank_one'=>intval(I('search_rank_one')),
            'search_rank_two'=>intval(I('search_rank_two')),
            'search_rank_three'=>intval(I('search_rank_three')),
            'search_attr_id'=>I('search_attr_id'),
            'search_content'=>I('search_content'),

        );
        //排序
        $sort_id_arr = array(
            0=>'bg.sell_num',
            1=>'bg.g_sale_time',
            2=>'bg.goods_price',
        );
        $sort_type_arr = array(
            0=>'asc',
            1=>'desc'
        );
        $sort_order = array();
        if(isset($sort_id_arr[$conditions['sort_id']]) && isset($sort_type_arr[$conditions['sort_type']])){
            $sort_order[$sort_id_arr[$conditions['sort_id']]] = $sort_type_arr[$conditions['sort_type']];
        }

        //属性
        $attr_id_arr = array();
        if(!empty($conditions['attr_id']) && is_array($conditions['attr_id'])){
            foreach($conditions['attr_id'] as $attr_id){
                if($attr_id>0 && !in_array($attr_id,$attr_id_arr)){
                    $attr_id_arr[] = $attr_id;
                }
            }
        }

        $param = $conditions;
        unset($param['sort_type']);
        unset($param['sort_id']);
        $param['sort_order'] = $sort_order;
        $param['attr_id'] = $attr_id_arr;

        $data = D('Common/Goods')->getBanfangLists($param);
        /*$return = array(
            'status'=>0,
            'lists'=>array(),
            'count'=>0,
            'msg'=>'获取数据失败'
        );*/
        if($data['count'] == 0){
            $return = array(
                'status'=>0,
                'lists'=>array(),
                'count'=>0,
                'msg'=>'获取数据失败'
            );
        }else{
            $return = array(
                'status'=>100,
                'lists'=>$data['lists'],
                'count'=>$data['count'],
                'msg'=>'获取数据成功'
            );
        }
        
        $this->ajaxReturn($return);
    }
}
