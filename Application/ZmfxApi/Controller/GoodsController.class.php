<?php
namespace Mobile\Controller;
class GoodsController extends MobileController{

	/**
	 * 产品首页
	 * @param number $n
	 */
	public function index($n=20,$p=1){
		//获取产品
		$goodsList = getGoodsList($this->domain,$n,$p,0,'3,6');
		$this->goodsList = $goodsList['list'];
		if($p>1){$this->ajaxReturn($this->fetch('Goods/goodsList'));}
		else $this->display();
	}

	/**
	 * 产品搜索
	 * @param string $search
	 */
	public function search($search='',$n=20,$p=1){
		if($search){
			$this->search = $search;
		}
		//获取产品
		$goodsList = getGoodsList($this->domain,$n,$p,array('search'=>$search));
		$this->goodsList = $goodsList['list'];
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
			$luozuan = getLuozuanInfo($_COOKIE['step1']);
			$advantage = getAdvantage($this->domain,1,2);
			if($advantage){
				$luozuan['price'] = formatPrice(($luozuan['dia_global_price']*C('DOLLAR_HUILV')*$luozuan['weight'])*($luozuan['dia_discount']+$advantage)/100);
			}
			$this->luozuan = $luozuan;
			$this->diamondsDataUrl = $_COOKIE['diamondsDataUrl'];
		}
		//获取选中的戒托
		if($_GET['goods_id'] or cookie('goods_id')){
			$goods_id = $_GET['goods_id']?$_GET['goods_id']:cookie('goods_id');
			$goods_type = $_GET['goods_type']?$_GET['goods_type']:cookie('goods_type');
			$selStringS = $_GET['selString']?$_GET['selString']:cookie('selString');
			$selString = explode(',', $selStringS);
			$goods = getGoodsJGS($goods_id, $goods_type, $this->domain,$selString[0],$selString[1],$selString[2]);
			$goodsInfo = $goodsInfo = getGoodsInfo($goods_id,$goods_type,$this->domain);//珠宝信息
			foreach ($goods as $key => $val) {
				if($key == 'material'){
					foreach ($val as $v) {
						if($v['css'] == 'active'){
							$goodsInfo['material_name'] = $v['material_name'];
							$goodsInfo['weights_name'] = $v['weights_name'];
						}
					}
				}elseif($key == 'deputystone'){
					foreach ($val as $v) {
						if($v['css'] == 'active'){
							$goodsInfo['deputystone_name'] = $v['deputystone_name'];
						}
					}
				}elseif($key == 'luozuan'){
					foreach ($val as $v) {
						if($v['css'] == 'active'){
							$goodsInfo['Luozuan_weights_name'] = $v['weights_name'];
							$goodsInfo['Luozuan_shape_id'] = $v['shape_id'];
						}
					}
				}
			}
			$goodsInfo['head'] = $selString[3];
			$goodsInfo['word'] = $selString[4];
			$goodsInfo['price'] = $goods['price'];
			cookie('goods_id',$goods_id);
			cookie('goods_type',$goods_type);
			cookie('selString',$selStringS);
			$this->goods = $goodsInfo;
		}
		//完成12步，不显示产品
		if($this->luozuan and $this->goods){
			$this->isNoGoods = 1;
			$this->price =  formatPrice($this->luozuan['price']) + formatPrice($this->goods['price']);
		}
		$this->display();
	}

	/**
	 * 产品分类
	 * @param int $category_id
	 * @param number $n
	 * @param number $p
	 */
	public function goodsCat($category_id,$n=20,$p=1){
		//获取产品
		$goodsList = getGoodsList($this->domain,$n,$p,$category_id,'3,6');
		$this->goodsList = $goodsList['list'];
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

	/**
	 * 产品信息页面
	 * @param int $goods_id
	 */
	public function goodsInfo($goods_id,$type){
		$goodsInfo            = getGoodsInfo($goods_id,$type,$this->domain);//珠宝信息
		$goodsInfo['content'] = html_entity_decode($goodsInfo['content']);
		$this->goodsInfo      = $goodsInfo;
		$this->goodsImages = getGoodsImages($goods_id,$type,$this->domain);//珠宝缩略图
		//获取规格数据或者金工石数据
		if($this->goodsInfo['goods_type'] == 3 or $this->goodsInfo['goods_type'] == 6){
			$goodsSpec = getGoodsSpec($goods_id,'',$this->domain);
			$this->goodsSpec = $goodsSpec['data'];
		}elseif ($this->goodsInfo['goods_type'] == 4 or $this->goodsInfo['goods_type'] == 5){
			$this->goodsJgs = getGoodsJGS($goods_id,$type,$this->domain);
			if(!$this->goodsJgs['luozuan'][0]['gal_id']){$this->error('本产品没有主石匹配，请修改产品数据');}
		}
		//获取属性参数数据
		$this->attr = getGoodsAttr($goods_id,$type,$this->domain);
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
			$selString = explode(',', $selString);
			$this->goodsJgs = getGoodsJGS($goods_id, $type, $this->domain,$selString[0],$selString[1],$selString[2]);
			$this->head = $selString[3];
			$this->word = $selString[4];
			$data['html'] = $this->fetch('Goods/goodsJgs');
			$data['price'] = $this->goodsJgs['price'];
			$this->ajaxReturn($data);
		}
	}

	/**
	 * 裸钻列表
	 */
	public function luozuan($n=10,$p=1){
		$list = getLuozuan($this->domain,$n,$p);
		foreach ($list as $key => $value) {

		}
		$this->list = $list;
		$this->display();
	}
	// Ajax 获取证书货信息
	public function getGoodsDiamondDiy(){
		$page  = I("page",1);	//当前页码
		$where = "1=1 AND goods_number > 0 ";
		$n = 10;
		$weight = explode('-',I("Weight"),2);
		$price = explode('-',I("Price"),2);
		$minWeight = $weight[0];
		$maxWeight = $weight[1];
		$minPrice  = $price[0];
		$maxPrice  = $price[1];

		$uid = $_SESSION['m']['uid'];
		if($uid)	//如有登录，则获取会员折扣
		$userdiscount = getUserDiscount($uid);
		$luozuan_advantage = 0;//C('luozuan_advantage')	
		if($minWeight) $where .= " AND weight >= $minWeight";
		if($maxWeight) $where .= " AND weight <= $maxWeight";
		if($minPrice) $where .= " AND (dia_global_price*".C('dollar_huilv')."*(dia_discount+".($luozuan_advantage+$userdiscount).")*weight/100 >= $minPrice )";
		if($maxPrice) $where .= " AND (dia_global_price*".C('dollar_huilv')."*(dia_discount+".($luozuan_advantage+$userdiscount).")*weight/100 <= $maxPrice )";
		if(I('Shape')){
			$shape = paramStr(I("Shape"));
			$where .= " AND shape in(".$shape.")";
		}
		if(I('Color')){
			$color = paramStr(I("Color"));
			$where .= " AND color in(".$color.")";
		}
		if(I('Clarity')){
			$clarity = paramStr(I("Clarity"));
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
		$Page = new \Think\Page($count,$n);

		$orderby = I("orderby");
		$orderway = I("orderway");
		if (!empty($orderby)){	//排序
			$orderway = empty($orderway) ? 'ASC' : $orderway;
			$ordersql = "  $orderby $orderway,dia_discount ASC ";
		}else{	//默认排序
			if(!empty($Shape) or !empty($Color) or !empty($Clarity) or !empty($Cut) or !empty($Polish) or !empty($Symmetry) or !empty($Cert) or !empty($Flour) or !empty($Location)){
				$ordersql = " dia_discount ASC ";
			}else{
				$ordersql = "price ASC";
			}
		}
		$dataList['data'] = D("GoodsLuozuan")->where($where)->limit(($page-1)*$n.",".$Page->listRows)->order($ordersql)->select();

		foreach($dataList['data'] as $key=>$val){
			$dataList['data'][$key]['shape'] = getDiamondsShapeName($val['shape']);
			$userdiscount                    = getUserDiscount($_SESSION['m']['uid']);
			
			$dataList['data'][$key]['dia_discount']  = get_luozuan_sale_jiadian($_SESSION['m']['uid'], $val['weight'], $dataList['data'][$key]['dia_discount']);			

			$dataList['data'][$key]['price']         = formatRound( $dataList['data'][$key]['dia_global_price'] * C('dollar_huilv') * $dataList['data'][$key]['dia_discount'] * $val['weight']/100, 2);  //国际报价*汇率*折扣*重量/100

			$dataList['data'][$key]['meika']         = formatRound( $dataList['data'][$key]['dia_global_price'] * C('dollar_huilv') * $dataList['data'][$key]['dia_discount']/100, 2);  //国际报价*汇率*折扣*重量/100
			
			if(C('price_display_type') == 1) {	//前台 视图 js/diamond.js	中html += "<td>$" + e.dia_global_price + "</td>";html += "<td>&yen;" + e.cur_price + "</td>";html += "<td>" + e.dia_discount_all + "</td>";	 								
				$dataList['data'][$key]['meika']        = 0;
				$dataList['data'][$key]['dia_discount'] = 0;
				$dataList['data'][$key]['dia_global_price'] = 0;
			}
		}
		$dataList['dollar_huilv']    = C('dollar_huilv');
		$dataList['count']    = $count;
		$dataList['page']     = $Page->show();
		$dataList['pagesize'] = $n;
		$dataList['thisPage'] = $page;
		$dataList['uType']    = $this->uType;
		$this->ajaxReturn($dataList);
	}

	public function add2cart(){
		$gids = $_POST['gid'];
		foreach($gids as $val){
			$goods = D('GoodsLuozuan')->where("`gid`='".$val."' AND `goods_number`>0")->find();
			$error = array();
			if($goods){
				$goods_attr = serialize($goods);
				$data = array();
				$data['goods_id'] = $goods['gid'];
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

		$result['count'] = M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."' AND goods_type=1")->count();
		$result['info'] = '添加成功';
		$result['status'] = 1;
		$this->ajaxReturn($result);

	}

	/**
	 * 散货列表
	 */
	public function sanhuo(){
		$this->goodsType = M('goods_sanhuo_type')->order('tid ASC')->select();
		$count = $sanhuoGoods = M('goods_sanhuo')->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where('1=1')->count();

		$Page = new \Think\AjaxPage($count,$n,'setPage');
		$sanhuoGoods = M('goods_sanhuo')->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where('1=1')->limit($Page->firstRow.','.$Page->listRows)->order('goods_id ASC')->select();
		$locationList = array("深圳","上海","北京","香港","国外");
		$this->sanhuoWeights = M("goods_sanhuo_weights")->where("pid=0")->order("sort ASC")->select();
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
			$goods = M('goods_sanhuo')->where("`goods_id`='".$val."' AND `goods_weight`>0")->find();
			if($goods){
				$goods['goods_price'] = $goods['goods_price']*(1+C("sanhuo_advantage")/100);
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

		$result['count'] = M('cart')->where($this->userAgent." AND agent_id='".C('agent_id')."'")->count();
		$result['info'] = '添加成功';
		$result['status'] = 1;
		$this->ajaxReturn($result);

	}

	//散货Ajax获取列表
	public function getGoodsByParam(){
		$goodsTypeId = I('GoodsType');
		$goods_sn = I('GoodsSn');
		$where = ' 1=1 ';
		$Clarity_data = explode('|',I('Clarity'));
		$Clarity = paramStr(implode(',',$Clarity_data));
		$Location = I('Location');
		$Color_data = explode('|',I('Color'));
		$Color = paramStr(implode(',',$Color_data));
		$Weight_data = explode('|',I('Weight'));
		$Weight = paramStr(implode(',',$Weight_data));
		$thisPage = I("page",1);
		$Cut_data = explode('|',I('Cut'));
		$Cut = paramStr(implode(',',$Cut_data));
		if($color_data){
			$where .= " AND zm_goods_sanhuo.color IN (".$Color.")";
		}

		$Clarity = str_replace('1','VVS',$Clarity);
		$Clarity = str_replace('2','VVS-VS',$Clarity);
		$Clarity = str_replace('3','VS',$Clarity);
		$Clarity = str_replace('4','VS2-SI1',$Clarity);
		$Clarity = str_replace('5','SI1-SI2',$Clarity);
		$Clarity = str_replace('6','SI2-SI3',$Clarity);
		$Clarity = str_replace('7','I1-I2',$Clarity);
		$Clarity = str_replace('8','<I2',$Clarity);

		if(!empty($Clarity) && $Clarity != "''"){
			$where .= " AND zm_goods_sanhuo.clarity IN (".$Clarity.")";
		}
		if(!empty($Cut) && $Cut != "''"){
			$where .= " AND zm_goods_sanhuo.cut IN (".$Cut.")";
		}
		if(!empty($Weight) && $Weight != "''"){
			$where .= " AND zm_goods_sanhuo.weights IN (".$Weight.")";
		}
		if(!empty($Location)){
			$where .= " AND zm_goods_sanhuo.location ='".$Location."'";
		}

		if($goodsTypeId != -1 && !empty($goodsTypeId)){
			$where .= ' AND zm_goods_sanhuo.tid="'.$goodsTypeId.'"';
			$sanhuoGoods['goods_sn'] = M('goods_sanhuo')->field('goods_sn,goods_id')->where("tid='".$goodsTypeId."'")->order('goods_sn ASC')->select();
		}
		if($goods_sn != -1 && !empty($goods_sn)){
			$where .= ' AND zm_goods_sanhuo.goods_id="'.$goods_sn.'"';
		}
		if(!empty($Color) && $Color != "''"){
			$where .= " AND color IN (".$Color.")";
		}
		$n     = 10;
		$count =  M('goods_sanhuo')->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where($where)->count();
		$Page  = new \Think\Page($count,$n);
		$sanhuoGoods['data'] = M('goods_sanhuo')->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where($where)->limit(($thisPage-1)*$n.','.$Page->listRows)->order('zm_goods_sanhuo.tid ASC')->select();
		if($sanhuoGoods){
			foreach($sanhuoGoods['data'] as $key=>$val){
				$sanhuoGoods['data'][$key]['goods_price'] = formatRound($val['goods_price']*(1+C("sanhuo_advantage")/100),2);
			}
		}
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
		$where = "1=1 AND goods_type=4 and agent_id = ".C('agent_id');
		$recommendZuantuo = M("goods")->where($where)->limit(4)->select();
		$this->recommendZuantuo = $recommendZuantuo;
		$this->total = $count;
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
		$diamond = M("GoodsLuozuan")->where("gid=".$step)->find();
		if($diamond){
			$diamond['goods_price'] = formatRound($diamond['dia_global_price']*C('dollar_huilv')*$diamond['weight']*($this->luozuan_advantage+$diamond['dia_discount'])/100,2);
		}
		return $diamond;
	}
	// 获取定制步骤2中的单个产品信息
	public function get_goods_info($step){
		$data = M("goods")->where("goods_id=".$step.' and agent_id = '.C('agent_id'))->find();

		if($_COOKIE["goods_id_".$step]){
			$params = explode('_',$_COOKIE["goods_id_".$step]);
			$materialId = $params[1];
			$diamondId = $params[2];
			$diamondWeight = $params[3];
			$hand = $params[4];
			$word = $params[5];
			$associateInfo = M("goods_associate_info")->alias("GAI")->join("zm_goods_material GM ON GM.material_id=GAI.material_id")->where("GAI.goods_id=".$step." AND GAI.material_id=$materialId")->find();
			$associateLuozuan = M("goods_associate_luozuan")->alias("GAL")->join("zm_goods_luozuan_shape GLS ON GLS.shape_id=GAL.shape_id")->where("GAL.goods_id=".$step." AND GAL.gal_id=$diamondId")->find();
			$material = M("goods_material")->where("material_id=".$materialId)->find();
			if($associateInfo && $associateLuozuan){
				$data['goods_price'] = $material["gold_price"]*($associateInfo['weights_name']*(1+$associateInfo['loss_name']/100))+$associateLuozuan['price']+$associateInfo['basic_cost'];
			}
			$data['associate_info'] = $associateInfo;
			$data['associate_luozuan'] = $associateLuozuan;
			$data['hand'] = $hand;
			$data['word'] = $word;
		}
		return $data;
	}

	public function detail($certNumber){
		$certNumber = I("certNumber");
		$data = M("GoodsLuozuan")->where("certificate_number='$certNumber' ")->find();
		if($data){
			$this->certNumber = $certNumber;
			$this->diamond = $data;
			$this->display();
		}else{
			$this->redirect("/Goods/luozuan","该钻石不存在");
		}
	}

	public function getCertPDF(){
		$reportNo = I('certNumber');
		if(empty($reportNo))die('请输入GIA证书编号');
		$url = "http://www.gia.edu/cs/Satellite?c=Page&childpagename=GIA%2FPage%2FReportCheck&cid=1355954554547&go=Look+Up&pagename=GIA%2FDispatcher&reportno=$reportNo";
		$doc = $this->getContent($url);
		preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
		$url = "http://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber=$reportNo&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber=".$matchs[1];
		header("Location: $url");die;
	}

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
}