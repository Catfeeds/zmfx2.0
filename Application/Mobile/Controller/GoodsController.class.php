<?php
namespace Mobile\Controller;
class GoodsController extends MobileController{

	/**
	 * 产品列表页
	 * @param number $n
	 */
	public function index($n=20,$p=1){
		//获取产品
		$goodsList = getGoodsList($this->domain,$n,$p,0,'3');
		$this->goodsList = $goodsList['list'];
		if($p>1){$this->ajaxReturn($this->fetch('Goods/goodsList'));}
		else $this->display();
	}

	/**
	 * 产品首页
	 * @param number $n
	 */
	public function goodsCatelog(){
		//获取产品
		$goods_seriesM      	    =  M('goods_series');
		$this -> goods_cat_info		=  D('Common/GoodsCategory') ->this_product_category();
		$this -> goods_series_info  =  $goods_seriesM     -> where('agent_id = '.C('agent_id'))->select();
		$this -> display();
	}


	/**
	 * 产品搜索
	 * @param string $search
	 */
	public function search($search='',$n=20,$p=1){
		if($search){
			$this->search = $search;
		}
		//2016年3月22日修改增加裸钻证书查询;author:fangkai
		$search = trim(I('search','','htmlspecialchars'));
		$search = addslashes($search);
		if(!empty($search)){
			$luozuan = D('GoodsLuozuan');

			$where1=array(
				'certificate_number' => array('LIKE','%'.$search.'%'),
				'goods_name' => array('LIKE','%'.$search.'%'),
				'_logic'=>'or'
			 );
			$where2=array(
				'_complex' => $where1,
				'_logic'=>'and'
			 );
			$data = getGoodsListPrice($luozuan->where($where2)->limit(1)->select(), $_SESSION['m']['uid']);

			if($data){
				if($data[0]['luozuan_type'] == 1){
					$this -> redirect("/Goods/luozuanColor?goods_sn=$search");
				}else{
					$this -> redirect("/Goods/luozuan?goods_sn=$search");
				}
			}
		}
		$M                   = D('Common/Goods');
		$where['goods_name'] = array('like', "%$search%");
		$where['goods_sn']   = array('like', "%$search%");
		$where['_logic']     = 'or';
		$M                  -> sql['where']['_complex'] = $where;
		$M                  -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M                  -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		$M                  -> _limit($p,$n);
		$products            = $M -> getList();
		if($products){
			$products        = $M -> getProductListAfterAddPoint($products,$_SESSION['m']['uid']);
		}
		$this -> goodsList   = $products;
		if($p>1){$this->ajaxReturn($this->fetch('Goods/goodsList'));}
		else $this->display();
	}

	/**
	 * 产品定制重新选择裸钻
	 */
	public function goodsDiyReElectionLuozuan(){
		if(cookie('step1')){
			cookie('step1',null);
			cookie('diamondsDataUrl',null);
			redirect(U('Goods/luozuan'));
		}else{
			$this->error('你都没有选中裸钻定制!');
		}
	}

	/**
	 * 产品定制重新选择产品
	 */
	public function goodsDiyReElectionGoods(){
		if(cookie('goods_id')){
			cookie('goods_id',null);
			cookie('goods_type',null);
			cookie('selString',null);
			redirect(U('Goods/goodsDiy'));
		}else{
			$this->error('你都没有选中定制产品!');
		}
	}

	/**
	 * 产品定制
	 */
	public function goodsDiy($n=30,$p=1){
		//获取产品
		$category_id = I('category_id',54,'intval');
		if(empty($_SESSION['web']['uid'])) {
			$tags                           = 1;
			$_SESSION['web']['uid']     	= $_SESSION['m']['uid'];//为了绕过user的登录验证
			$_SESSION['web']['is_validate'] = $_SESSION['m']['is_validate'];//为了绕过user的登录验证
		}
		$config                     = C('TMPL_PARSE_STRING');//取出路径配置
		$theme                      = C('DEFAULT_THEME');
		$_SESSION['is_inside_call'] = 1;
		$HomeGoodsController        = new \Home\Controller\GoodsController();
		$obj                        = $HomeGoodsController -> prepareHtmlByCat($category_id, 4, 3);
		//print_r($obj);exit;
		C('TMPL_PARSE_STRING',$config); //置换回mobile项目路径配置
		C('DEFAULT_THEME',$theme);
		if($tags == 1 && $_SESSION['web']['uid']) {
			unset($_SESSION['web'],$tags,$_SESSION['is_inside_call']);
		}
		$this->home_this   = $obj;
		$this->category_id = $category_id;


		//获取选中的裸钻
		if(cookie('step1')){
			$luozuan   = getLuozuanInfo($_COOKIE['step1']);
			$advantage = getAdvantage($this->domain,1,2);
			if($advantage){
				$luozuan['price']    = formatPrice(($luozuan['dia_global_price']*C('DOLLAR_HUILV')*$luozuan['weight'])*($luozuan['dia_discount']+$advantage)/100);
			}
			$this -> luozuan         = $luozuan;
			$this -> diamondsDataUrl = $_COOKIE['diamondsDataUrl'];
		}
		//获取选中的戒托
		if($_GET['goods_id'] or cookie('goods_id')){
			$goods_id   = $_GET['goods_id']?$_GET['goods_id']:cookie('goods_id');
			$goods_type = $_GET['goods_type']?$_GET['goods_type']:cookie('goods_type');
			$selStringS = $_GET['selString']?$_GET['selString']:cookie('selString');
			$selString  = explode(',', $selStringS);
			$goods      = getGoodsJGS($goods_id, $goods_type, $this->domain,$selString[0],$selString[1],$selString[2],$selString[3],$selString[6]);
			$goodsInfo  = getGoodsInfo($goods_id,$goods_type,$this->domain);//珠宝信息
			foreach ($goods as $key => $val) {
				if($key == 'material'){
					foreach ($val as $v) {
						if($v['css'] == 'active'){
							$goodsInfo['material_name'] = $v['material_name'];
							$goodsInfo['weights_name']  = $v['weights_name'];
						}
					}
				}elseif($key == 'deputystone'){
					foreach ($val as $v) {
						if($v['css'] == 'active'){
							$goodsInfo['deputystone_name']     = $v['deputystone_name'];
						}
					}
				}elseif($key == 'luozuan'){
					foreach ($val as $v) {
						if($v['css'] == 'active'){
							$goodsInfo['Luozuan_weights_name'] = $v['weights_name'];
							$goodsInfo['Luozuan_shape_id']     = $v['shape_id'];
						}
					}
				}
			}
			$goodsInfo['head']   = $selString[3];
			$goodsInfo['word']   = $selString[4];
			$goodsInfo['sd_id']  = $selString[5];
			$goodsInfo['head1']  = $selString[6];
			$goodsInfo['word1']  = $selString[7];
			$goodsInfo['sd_id1'] = $selString[8];
			$goodsInfo['price']  = $goods['price'];
			cookie('goods_id',$goods_id);
			cookie('goods_type',$goods_type);
			cookie('selString',$selStringS);
			$this -> goods       = $goodsInfo;
		}
		//完成12步，不显示产品
		if($this->luozuan and $this->goods){
			$this->isNoGoods = 1;
			$this->price     = formatPrice($this->luozuan['price']) + formatPrice($this->goods['price']);
		}
		$this->display();
	}

	/**
	 * 产品分类
	 * @param int $category_id
	 * @param number $n
	 * @param number $p
	 */
	public function goodsCat($category_id=0,$n=20,$p=1){
		$category_id = I('category_id',0,'intval');

		if(empty($_SESSION['web']['uid'])) {
			$tags                           = 1;
			$_SESSION['web']['uid']     	= $_SESSION['m']['uid'];//为了绕过user的登录验证
			$_SESSION['web']['is_validate'] = $_SESSION['m']['is_validate'];//为了绕过user的登录验证
		}
		$config                     = C('TMPL_PARSE_STRING');//取出路径配置
		$theme                      = C('DEFAULT_THEME');
		$_SESSION['is_inside_call'] = 1;
		$HomeGoodsController        = new \Home\Controller\GoodsController();
		$obj                        = $HomeGoodsController -> prepareHtmlByCat($category_id, 3, 1);
		//print_r($obj);exit;
		C('TMPL_PARSE_STRING',$config); //置换回mobile项目路径配置
		C('DEFAULT_THEME',$theme);
		if($tags == 1 && $_SESSION['web']['uid']) {
			unset($_SESSION['web'],$tags,$_SESSION['is_inside_call']);
		}
		$this->home_this   = $obj;
		$this->category_id = $category_id;

		//获取经典系列名称
		$series_id = I('get.series_id','','intval');

		if($series_id){
			$series_name = M('goods_series')->field('series_name')->where(array('goods_series_id'=>$series_id,'agent_id'=>C('agent_id')))->find();
			$this->assign('series_name',$series_name);
		}
		//获取产品类型名称
		if($category_id){
			$cate_name = M('goods_category')->field('category_name')->where(array('category_id'=>$category_id))->find();
			$this->assign('cate_name',$cate_name);
		}
		$this->series_id = I('get.series_id','','intval');
		$this->display();

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

        //获取具体要筛选的所有属性,cookie形式为1_60_filterAttbutes=1_60_2,1_60_3，
        $filterKey  = C('agent_id').'_'.$categoryId.'_'.$goodsType.'_filterAttbutes';
        $filters    = I("cookie.$filterKey");
        $isAllFilterEmpty = true;
        if($filters){                               //如果此分类有要筛选的属性
            $filtersArray = explode(",",$filters);  //拆分成数组[1_60_4_2,1_60_4_3]，才不会，我是缺人
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
		//zhy 2016年10月6日 14:45:26 。categoryId传入时候不能为0，否则会为其他查询代入不必要条件。
		if(is_numeric($categoryId) && $categoryId>0){
			$pid        = $this -> getCatPid($categoryId);
			if( $pid == 0 ){
				$subIds = getSubCatIds($categoryId);
				if(implode(',', $subIds)){
					$categoryId = implode(',', $subIds);
					$M -> set_where( 'zm_goods.category_id' , " in ($categoryId)" , true );
				}
			}else{
				$M     -> set_where( 'zm_goods.category_id' , $categoryId );
			}
		}

        if( $series_id > 0 && $goodsType == 3 ){
            $M -> set_where( 'zm_goods.goods_series_id' , $series_id );
        }
        $M     -> set_where( 'zm_goods.goods_type'  , "$goodsType");
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
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
			$M       -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
			$M       -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架		
        }
 
        $count    = $M -> get_count();
        $page     = new \Think\AjaxPage($count,$n,"setPage");
        $M       -> _limit($p,$n);
        $M       -> _order($sortType,$sortValue);
        $products = $M -> getList();//根据符合筛选的goods_id查询表格

        if($products){
            $products = $M -> getProductListAfterAddPoint($products,$_SESSION['m']['uid']);
        }
        $data['count'] = $count;
        $data['data']  = $products;
        $data['page']  = $page -> show();
        return $data;
    }

    public function getCatPid($categoryId){
        return M('goods_category') -> where('category_id="'.$categoryId.'"') -> getField('pid');
    }

	/**
	 * 产品信息页面
	 * @param int $goods_id
	 */
	public function goodsInfo($goods_id,$type){
		$goodsInfo            = getGoodsInfo($goods_id,$type,$this->domain);//珠宝信息
		$goodsInfo['content'] = html_entity_decode($goodsInfo['content']);
		$goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $type);
		$this->goodsInfo      = $goodsInfo;
		$this->goodsImages    = getGoodsImages($goods_id,$type,$this->domain);//珠宝缩略图
		//获取规格数据或者金工石数据
		if($this->goodsInfo['goods_type'] == 3 ){
			$goodsSpec = getGoodsSpec($goods_id,'',$this->domain);
			$this->goodsSpec = $goodsSpec['data'];
		}elseif ($this->goodsInfo['goods_type'] == 4 ){
			$this->goodsJgs = getGoodsJGS($goods_id,$type,$this->domain);
			if(!$this->goodsJgs['luozuan'][0]['gal_id']){$this->error('本产品没有主石匹配，请修改产品数据');}
		}

        $expectedDeliveryTime = C('expected_delivery_time');
        $this->expectedDeliveryTime = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? intval($expectedDeliveryTime) : 15;

        //获取属性参数数据
		$this -> attr           = getGoodsAttr($goods_id,$type,$this->domain);
		$this -> public_content = "";
		if( $goodsInfo['category_id'] && $goodsInfo['goods_type'] ){
			$agent_id = C('agent_id');
			$info     = M('goods_category_config') -> where(" category_id = $goodsInfo[category_id] and agent_id = $agent_id ") -> find();
			if($info){
				if($goodsInfo['goods_type'] =='3'){
					$this -> public_content   = $info['public_goods_desc_chengpin_down'];
				}else if($goodsInfo['goods_type'] =='4'){
					$this -> public_content   = $info['public_goods_desc_dingzhi_down'];
				}
			}
		}

		if($this->goodsInfo['goods_type'] =='4'){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
        }else{
            $sd_list       = '';
        }
        $this->sd_list = $sd_list;
		$this->display();
	}

	/**
	 * 获取产品价格
	 * @param int $goods_id
	 * @param int $type
	 * @param int $selMaterial
	 * @param int $selDeputystone
	 * @param int $selLuozuan
	 */
	public function getGoodsPrice($goods_id,$type,$selString){
		if($type == 3 or $type == 6){
			$goodsSpec = getGoodsSpec($goods_id,$selString,$this->domain);
			$this->goodsSpec = $goodsSpec['data'];
			$data['html'] = $this->fetch('Goods/goodsSpec');
			$data['price'] = $goodsSpec['price'];
			$this->ajaxReturn($data);
		}elseif ($type == 4 or $type == 5){
			$goodsInfo = getGoodsInfo($goods_id,$type,$this->domain);//珠宝信息
			$goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $type);

            $expectedDeliveryTime = C('expected_delivery_time');
            $this->expectedDeliveryTime = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? intval($expectedDeliveryTime) : 15;

            $this->goodsInfo = $goodsInfo;
			$selString       = explode(',', $selString);
			$this->goodsJgs  = getGoodsJGS($goods_id, $type, $this->domain,$selString[0],$selString[1],$selString[2],$selString[3],$selString[6]);
			$this->head  = $selString[3];
			$this->word  = $selString[4];
			$this->head1 = $selString[6];
			$this->word1 = $selString[7];
			if($this->goodsInfo['goods_type'] =='4'){
				$S             = D('Common/SymbolDesign');
				$agent_id      = C('agent_id');
				$sd_list       = $S -> getList($agent_id);
			}else{
				$sd_list       = '';
			}
			$this->sd_list = $sd_list;
			$data['html']  = $this -> fetch('Goods/goodsJgs');
			$data['price'] = $this -> goodsJgs['price'];
			$this->ajaxReturn($data);
		}
	}

	/**
	 * 裸钻列表
	 */
	public function luozuan($n=10,$p=1){
		$list = getLuozuan($this->domain,$n,$p);
		$this->list = $list;
		
		$cartCount  = M('cart')->where($this->userAgent." AND agent_id=".C('agent_id'))->count();
		if($cartCount == 0){
			$cartCount = '';
		}
		$this->cartCount	= $cartCount;
		
		$goods_sn 	= I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);
		
		$this->agent_id 	= C('agent_id');
		$this->navActive	= "luozuan";
		$this->display();
	}
	/**
	 * 彩钻列表
	 */
	public function luozuanColor($n=10,$p=1){
		$list = getLuozuan($this->domain,$n,$p);
		$this->list = $list;
		
		$cartCount  = M('cart')->where($this->userAgent." AND agent_id=".C('agent_id'))->count();
		if($cartCount == 0){
			$cartCount = '';
		}
		$this->cartCount	= $cartCount;
		
		$goods_sn = I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);
		
		$this->agent_id 	= C('agent_id');
		$this->navActive	= "caizuan";
		$this->display();
	}

	// Ajax 获取证书货信息
	public function getGoodsDiamondDiy(){
		$page      = I("page",1);	//当前页码
		$where     = "1=1 AND goods_number > 0 ";
		$n         = 10;
		$weight    = explode('-',I("Weight"),2);
		$price     = explode('-',I("Price"),2);
		$minWeight = $weight[0];
		$maxWeight = $weight[1];
		$minPrice  = $price[0];
		$maxPrice  = $price[1];

		$uid = $_SESSION['m']['uid'];
		if($uid){    //如有登录，则获取会员折扣
			if($luozuan_type == 1){
				$userdiscount = getUserDiscount($uid, 'luozuan_discount');
			}else{
				$userdiscount = getUserDiscount($uid, 'caizuan_discount');
			}
        }
		$point = D("GoodsLuozuan") -> setLuoZuanPoint();
        $point = $point + C('luozuan_advantage');
		if($minWeight) $where .= " AND weight >= $minWeight";

		if($maxWeight) $where .= " AND weight <= $maxWeight";

		if($minPrice) $where .= " AND (dia_global_price*".C('dollar_huilv')."*(dia_discount+".($point+$userdiscount).")*weight/100 >= $minPrice )";

		if($maxPrice) $where .= " AND (dia_global_price*".C('dollar_huilv')."*(dia_discount+".($point+$userdiscount).")*weight/100 <= $maxPrice )";

		$goods_sn   = I('goods_sn','','intval');

		$luozuan_type = I('luozuan_type',0,'intval');
        if($goods_sn){
            $where .= " AND (certificate_number like '%$goods_sn%' OR goods_name like '%$goods_sn%') ";
        }//货品编号

		$where     .= " AND luozuan_type = ".$luozuan_type ;

		$Intensity  = urldecode($_REQUEST['Intensity']);

		if($Intensity){
			$Intensity = paramStr(I("Intensity"));
			$where .= " AND intensity in(".$Intensity.")";
		}

		if(I('Shape')){
			$shape = paramStr(I("Shape"));
			$where .= " AND shape in(".$shape.")";
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
		if($Location){
			if($Location == "订货"){
				$where .= " AND (quxiang like '%定货%' OR quxiang like '订货' OR quxiang like '%国外%')";
			}else if($Location == "SDG"){
				$where .= " AND type = 1";
			}else{
				$where .= " AND quxiang = '现货'";
			}
		}

		if(I('Cut')){
			$cut = paramStr(I("Cut"));
			$where .= " AND cut in(".$cut.")";
		}
		if(I('Symmetry')){
			$symmetry = paramStr(I("Symmetry"));
			$where .= " AND symmetry in(".$symmetry.")";
		}
		if(I('Polish')){
			$polish = paramStr(I("Polish"));
			$where .= " AND polish in(".$polish.")";
		}
		if(I('Cert')){
			$cert = paramStr(I("Cert"));
			$where .= " AND certificate_type in(".$cert.")";
		}
		$Fluor = urldecode($_REQUEST['Fluor']);
		$NaiKa = urldecode($_REQUEST['NaiKa']);
		if($Fluor){//荧光
			if(stristr($Fluor,'Other')){
				$where .= " AND fluor NOT IN ('N','F','M','S','VS')";
			}else{
				$where .= " AND fluor IN (".paramStr($Fluor).")";
			}
		}
		if(stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
			$where .= " AND (milk like '%无奶%' AND coffee like '%无咖%') ";
		}elseif(stristr($NaiKa,'NoMilk') and !stristr($NaiKa,'NoCoffee')){
			$where .= " AND (milk like '%无奶%') ";
		}elseif(!stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
			$where .= " AND (coffee like '%无咖%') ";
		}

		$count = D("GoodsLuozuan")->where($where)->count();
		$Page  = new \Think\Page($count,$n);

		$orderby  = I("orderby");
		$orderway = I("orderway");
		if (!empty($orderby)){	//排序
			$orderway = empty($orderway) ? 'ASC' : $orderway;
			$ordersql = "  $orderby $orderway,weight ASC ";
		}else{	//默认排序
			if(!empty($Shape) or !empty($Color) or !empty($Clarity) or !empty($Cut) or !empty($Polish) or !empty($Symmetry) or !empty($Cert) or !empty($Flour) or !empty($Location)){
				$ordersql = " weight ASC ";
			}else{
				$ordersql = " weight ASC ";
			}
		}
		if($luozuan_type == '1'){
			$goodsluozuanobject = new \Common\Model\GoodsLuozuanModel(1);
        }else{
			$goodsluozuanobject = D('GoodsLuozuan');
		}
		$dataList['data'] = $goodsluozuanobject->where($where)->limit(($page-1)*$n.",".$Page->listRows)->order($ordersql)->select();
		$dataList['data'] = getGoodsListPrice($dataList['data'], $uid, 'luozuan');
		
		$cartList	= M('cart')->field('goods_id')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_type=1")->select();
		
		foreach($dataList['data'] as $key=>$val){
			foreach($cartList as $k=>$v){
				if($v['goods_id'] == $val['gid']){
					$dataList['data'][$key]['is_cart'] = 1;
				}
			}
			if($val['imageurl'] == '-' || $val['imageurl'] == '\"\"' || $val['imageurl'] == "\r\n" || $val['imageurl'] == "\n"){
				$dataList['data'][$key]['imageurl'] = '';
			}
			$dataList['data'][$key]['shape'] = getDiamondsShapeName($val['shape']);
		}

		$dataList['dollar_huilv']       = C('dollar_huilv');
		$dataList['price_display_type'] = C('price_display_type');
		$dataList['count']    = $count;
		$dataList['page']     = $Page -> show();
		$dataList['page_size'] = $n;
		$dataList['thisPage'] = $page;
		$dataList['uType']    = $this -> uType;
		$this -> ajaxReturn($dataList);
	}

	public function add2cart(){
		$gids = $_POST['gid'];
		foreach($gids as $val){
			$goods = M('goods_luozuan') -> where("`gid`='".$val."' AND `goods_number`>0 ") -> find();
			$error = array();
			if($goods){
				if($goods['agent_id'] != C('agent_id')){
					if($goods['luozuan_type'] == 1){
						$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
					}else{
						$point = D("GoodsLuozuan") -> setLuoZuanPoint();
					}
				}
				$goods['dia_discount'] += $point;
                $goods              = getGoodsListPrice($goods,$_SESSION['web']['uid'],'luozuan', 'single');
				$goods_attr         = serialize($goods);
				$data               = array();
				$data['goods_id']   = $goods['gid'];
				$data['goods_type'] = 1;
				$data[$this->userAgentKey] = $this->userAgentValue;
				$data['goods_attr'] = $goods_attr;
				$data['goods_sn'] = $goods['certificate_number'];
				$data['agent_id'] = C('agent_id');
				if(!M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_sn ='".$data['goods_sn']."'")->find()){
					M('cart')->data($data)->add();
				}
			}
		}
		$count			  = M('cart')->where($this->userAgent." AND agent_id=".C('agent_id'))->count();
		if($count == 0){
			$count = '';
		}
		$result['count']  = $count;
		$result['info']   = '添加成功';
		$result['status'] = 1;
		$this->ajaxReturn($result);

	}
	/* 裸钻取消购物车 */
	public function deleteLuozuan(){
		$gid        = I("gid",0,'intval');
		$where['goods_id']	= $gid;
		$where['agent_id']	= C('agent_id');
		$where[$this->userAgentKey]		=	$this->userAgentValue ;
		$cartGoods  = M('cart')->where($where)->find();
		$data       = array('status'=>0,'msg'=>"购物车中的货品删除失败");
		if(empty($cartGoods) || $gid == 0){
			$data['msg'] = "购物车中不存在该货品";
		}else{
			if(M('cart')->where($where)->delete()){
				$data['status'] = 1;
				$data['msg'] = "购物车中的货品删除成功";
			}
		}
		$count			 = M('cart')->where($this->userAgent." AND agent_id=".C('agent_id'))->count();
		if($count == 0){
			$count = '';
		}
		$data['count']  = $count;
		$this->ajaxReturn($data);
	}

	/**
	 * 散货列表
	 */
	public function sanhuo(){
		$this  -> goodsType = M('goods_sanhuo_type')->order('tid ASC')->select();


		$count = D('GoodsSanhuo')->getCount(array());///->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where('zm_goods_sanhuo.agent_id='.C("agent_id"))->count();


		$Page = new \Think\AjaxPage($count,$n,'setPage');

		$locationList = array("深圳","上海","北京","香港","国外");
		$this->sanhuoWeights = M("goods_sanhuo_weights")->where("pid=0")->order("sort ASC") -> select();
		$this->sanhuoColor = M("goods_sanhuo_color")->order("sort ASC")->select();
		$this->sanhuoClarity = M("goods_sanhuo_clarity")->order("sort ASC")->select();
		$this->sanhuoCut = M("goods_sanhuo_cut")->order("sort ASC")->select();
		$this->locationList = $locationList;
		$this->display();
	}

	//添加散货到购物车
	public function addSanhuo2cart(){
		$gids = $_POST['goods_id'];
		foreach($gids as $val){
			$goods = D('GoodsSanhuo')->getInfo(array('zm_goods_sanhuo.goods_id'=>array('in',$val),'goods_weight'=>array('gt',0)));
			if($goods){
				$goods      = getGoodsListPrice($goods, $_SESSION['m']['uid'], 'sanhuo', 'single');
				$goods_attr = serialize($goods);
				$data       = array();
				$data[$this->userAgentKey] = $this->userAgentValue;
				$data['goods_id']     = $goods['goods_id'];
				$data['goods_type']   = 2;
				$data['goods_attr']   = $goods_attr;
				$data['goods_sn']     = $goods['goods_sn'];
				$data['goods_number'] = 0;
				$data['agent_id']     = C('agent_id');
				if(!M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_id ='".$val."' AND goods_type=2")->find()){
					M('cart')->data($data)->add();
				}
			}
		}

		$result['count']  = M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."'")->count();
		$result['info']   = '添加成功';
		$result['status'] = 1;
		$this->ajaxReturn($result);

	}

	//散货Ajax获取列表
	public function getGoodsByParam(){
		$goodsTypeId  = I('GoodsType');
		$goods_sn     = I('GoodsSn');

		$p = $thisPage   = $_POST['p'] = I('page','1');
        $n = $_POST['n'] = I('n','10');
		$Location        = I('Location');
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
		$where           = array();
        $where['zm_goods_sanhuo.goods_weight'] = array('gt','0');

		if(!empty($Clarity) && $Clarity != "''" && $Clarity[0] != '' ){
			$where['zm_goods_sanhuo.clarity']  = array('in',$Clarity);
		}
		if(!empty($Cut) && $Cut != "''" && $Cut[0] != '' ){
			$where['zm_goods_sanhuo.cut']      = array('in',$Cut);
		}
		if(!empty($Weight) && $Weight != "''" && $Weight[0] != '' ){
            $Weight = str_replace(' 300', '+300', $Weight);
            $where['zm_goods_sanhuo.weights']   = array('in',$Weight);
		}
		if(!empty($Location)){
			$where['zm_goods_sanhuo.location']  = array('in',$Location);
		}

		if($goodsTypeId != -1 && !empty($goodsTypeId)){
			$where['zm_goods_sanhuo.tid'] = array('in',$goodsTypeId);
			$obj                     = D('GoodsSanhuo');
			$id                      = $obj -> get_where_agent();
			$sanhuoGoods['goods_sn'] = $obj -> field('goods_sn,goods_id')->where("tid='".$goodsTypeId."' and agent_id in ( $id ) ")->order('goods_sn ASC')->select();
		}
		if($goods_sn != -1 && !empty($goods_sn)){
			$where['zm_goods_sanhuo.goods_id']  = array('in',$goods_sn);
		}
		if(!empty($Color) && $Color != "''" && $Color[0] != '' ){
			$where['zm_goods_sanhuo.color']    = array('in',$Color);
		}

        $GSobj               = D('GoodsSanhuo');
        $res                 = $GSobj -> getList($where,'goods_id asc',$p,$n);
        $sanhuoGoods['data'] = $res['list'];
        $count               = $res['total'];
		if($sanhuoGoods){
			$sanhuoGoods['data'] = getGoodsListPrice($sanhuoGoods['data'], $_SESSION['m']['uid'], 'sanhuo');
		}

		$sanhuoGoods['goodsSn']   = $goods_sn;
		$sanhuoGoods['thisPage']  = $thisPage;
		$sanhuoGoods['pagesize']  = $n;
		$sanhuoGoods['count']     = $count;
		$sanhuoGoods['cartCount'] = M('cart')->where("uid='".$_SESSION['m']['uid']."' AND agent_id='".C('agent_id')."'")->count();
		$this->ajaxReturn($sanhuoGoods);
	}

	/* 定制页面函数 */
	public function diy(){
		// 查询所有戒托信息
		$this->catShow = 0;
        $p = I('p',1);
        $n = I('n',20);
        // 查询所有戒托信息
        $this->catShow        = 0;
        $M                    = D('Common/Goods');
        $M                   -> set_where('goods_type', '4' );
		$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
        $M                   -> _limit($p,$n);
        $count                = $M -> get_count();
        $recommendZuantuo     = $M -> getList();
        if($recommendZuantuo){
            $recommendZuantuo = $M->getProductListAfterAddPoint($recommendZuantuo,$_SESSION['web']['uid']);
        }
        $this->total          = $count;
		/*===========================定制流程编号处理  开始===========================*/
		$step1 = (int)isset($_COOKIE['step1'])?$_COOKIE['step1']:0;	//step1存放裸钻商品编号
		$step2 = (int)isset($_COOKIE['step2'])?$_COOKIE['step2']:0;	//step2存放戒托商品编号
		if($step1<>0 and $step2<>0){
			$diy_step = 'step11';
			$step1 = $this->getOneDiamondsData($step1);
			$step2 = $this->get_goods_info($step2);	//获取商品详细信息
			$step3 = array('luozuan'=>$step1['goods_price'],'jietuo'=>$step2['goods_price'],'total'=>$step1['goods_price']+$step2['goods_price']);
			//print_r($step3);die;
		}elseif($step1<>0 and $step2==0){
			$diy_step = 'step10';
			$step1 = $this->getOneDiamondsData($step1);
			$step3 = array('luozuan'=>$step1['goods_price'],'total'=>$step1['goods_price']);
			//print_r($step3);die;
		}elseif($step1==0 and $step2<>0){
			$diy_step = 'step01';
			$step2 = $this->get_goods_info($step2);	//获取商品详细信息
			$step3 = array('jietuo'=>$step2['goods_price'],'total'=>$step2['goods_price']);
		}else{
			$diy_step = 'step00';
		}
		$this->step1 = $step1;
		$this->step2 = $step2;
		$this->step3 = $step3;
		/*===========================定制流程编号处理 结束===========================*/
		$this->redirect("/Goods/goodsDiy");
	}

	// 获取单个裸钻信息
	public function getOneDiamondsData($step){
		$diamond = D("GoodsLuozuan")->where("gid=".$step)->find();
		if($diamond){
			$diamond['goods_price'] = formatRound($diamond['dia_global_price']*C('dollar_huilv')*$diamond['weight']*($this->luozuan_advantage+$diamond['dia_discount'])/100,2);
		}
		return $diamond;
	}
	// 获取定制步骤2中的单个产品信息
	public function get_goods_info($step){
		$M                 = D('Common/Goods');
		$data              = $M -> get_info($step,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
		if($_COOKIE["goods_id_".$step]){
			$params        = explode('_',$_COOKIE["goods_id_".$step]);
			$materialId    = $params[1];
			$diamondId     = $params[2];
			$diamondWeight = $params[3];
			$hand          = $params[4];
			$word          = $params[5];
			list($material,$associateInfo,$associateLuozuan,$deputystone) = $M -> getJingGongShiData($step,$materialId,$diamondId,0);
			$data['goods_price']                                          = $M -> getJingGongShiPrice($material,$associateInfo,$associateLuozuan,$deputystone);
			$data['associate_info']                                       = $associateInfo;
			$data['associate_luozuan']                                    = $associateLuozuan;
			$data['hand']                                                 = $hand;
			$data['word']                                                 = $word;
		}
		$info             = getGoodsListPrice($info, $_SESSION['m']['uid'], 'consignment', 'single');
		return $data;
	}

	public function detail($certNumber){
		$certNumber = I("certNumber");
		$data       = D("GoodsLuozuan") -> where(" certificate_number = '$certNumber' ") -> find();
		if($data){
			$this->certNumber = $certNumber;
			$this->diamond    = $data;
			$this->display();
		}else{
			$this->redirect("/Goods/luozuan","该钻石不存在");
		}
	}

	
	public function getCertPDF(){
		$reportNo = I('certNumber');
		$url = 'https://www.gia.edu/cs/Satellite?c=Page&childpagename=GIA%2FPage%2FReportCheck&cid=1355954554547&go=Look+Up&pagename=GIA%2FDispatcher&reportno='.$reportNo;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//禁止直接显示获取的内容
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
		curl_setopt($curl, CURLOPT_REFERER, "http://www.baidu.com/");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5");		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书下同
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
		$doc = curl_exec($ch); //获取
		curl_close($ch);
		preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
		$url = 'https://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber='.$reportNo.'&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber='.$matchs[1];
		header("Location: $url");die;
	}	
	
	// public function getCertPDF(){
		// $reportNo = I('certNumber');
		// if(empty($reportNo))die('请输入GIA证书编号');
		// $url = "http://www.gia.edu/cs/Satellite?c=Page&childpagename=GIA%2FPage%2FReportCheck&cid=1355954554547&go=Look+Up&pagename=GIA%2FDispatcher&reportno=$reportNo";
		// $doc = $this->getContent($url);
		// preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
		// $url = "http://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber=$reportNo&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber=".$matchs[1];
		// header("Location: $url");die;
	// }

	public function getContent($url,$httpheader=0){ //CURL远程数据采集
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if($httpheader==0){
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));  /*/伪造IP（REMOTE_ADDR无法伪造，若想隐藏REMOTE_ADDR就只有使用CURL代理功能了，使用代理对目标进行握手。）/*/
			curl_setopt($curl, CURLOPT_REFERER, "http://www.baidu.com/");   /*/构造来路/*/
		}
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 0); /*/尝试连接等待的时间，以毫秒为单位。如果设置为0，则无限等待/*/
		curl_setopt($curl, 156, 99999); // problem solved
		curl_setopt($curl, CURLOPT_REFERER, $url);
		$content = curl_exec($curl);
		curl_close($curl);
		return $content;
	}
	
	
	/**
	*	APP查询接口页面
	*	zhy		find404@foxmail.com
	*	2016年11月26日 16:00:59
	*/
	public function zhengshuInfo(){

		$type   = I('type','1','intval');//1.gia 2.IGI 3.HRD 4.NGTC
        $number = I('number','');
		$weight = I('weight','');
        define('cacheDir','./zs/data.cache/');
        $cacheDir  = './zs/data.cache/';
        if($number){
            $where = " zs_id = '".$number."'";
        }else{
			$this -> data = null;
            $this -> display();
			die;
        }
        if($weight){
            $where .= " AND zs_weight = '".$weight."' ";
        }
        $res        = M('report') -> where($where) -> find(); 

        // 引用 think\Report.class.php
        $Report  = new \Think\Report(); 
        $Report -> report($number,$weight,$type);
        $zs_data = $Report -> getReportData();  
        if($zs_data){	//若返回数据为数组则输出数据，否则输出错误信息
            $data = array();
            foreach($zs_data as $row){
                foreach($row as $k => $r){
                    $v['title'] = $k;
                    $v['value'] = $r;
                    $data[]     = $v;
                }
            }
        }else{
            $data = null;
        }
		$this -> data = $data;
		$this -> display();
	}
}
