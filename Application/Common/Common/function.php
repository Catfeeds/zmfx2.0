<?php 
function getOrderStatus($status){
	$status_txt = "";
	switch($status){
		case 0: $status_txt = "已下单，待确认";
		break;
		case 1: $status_txt = "已确认，待付款";
		break;
		case 2: $status_txt = "已支付，待配货";
		break;
		case 3: $status_txt = "已配货，待发货";
		break;
		case 4: $status_txt = "已发货，待收货";
		break;
		case 5: $status_txt = "已收货";
		break;
		case 6: $status_txt = "已完成";
		break;
		case -1:$status_txt ="已取消";
		break;
		default: $status_txt = "未知状态";
		break;
	}
	return $status_txt;
}

function orderStatus(){
	$orderStatus = array(
		'0'=>'未确认'
		,'1'=>'未支付'
		,'2'=>'未配货'
		,'3'=>'待发货'
		,'4'=>'待收货'
		,'5'=>'已收货'
		,'6'=>'已完成'
		,'-1'=>'已取消'
	);
	return $orderStatus;
}

function getTypeNameByGoodsType($goods_type){
	return M('goods_sanhuo_type')->where("tid='$goods_type'")->getField('type_name');
}

  /**
   * 递归数组
   * @param array $data 数组对象
   * @param int $id 一条记录的id
   * @param int $pid 上级id
   * @param int $objId 开始的id
   * @return multitype:multitype:unknown
   */
function _arrayRecursive($data,$id,$pid,$objId=0,$isKey='0'){
	$list = array(); 
	foreach ($data AS $key => $val){
	  	if($val[$pid] == $objId){  
	      	$val['sub'] = _arrayRecursive($data, $id, $pid, $val[$id],$isKey);
	      	if(empty($val['sub'])){unset($val['sub']);} 
	      	if($isKey) $list[] = $val;
	      	else $list[] = $val; 
	  	}
	}
	return $list;
}

/**
 * 递归数组 参照上面的函数，修改了$isKey的用处
 * @param array $data 数组对象
 * @param int $id 一条记录的id
 * @param int $pid 上级id
 * @param int $objId 开始的id
 * @return multitype:multitype:unknown
 */
function _arrayRecursiveNew($data,$id,$pid,$objId=0,$isKey=0){
	$list = array();
	foreach ($data AS $key => $val){
		if($val[$pid] == $objId){
			$val['sub'] = _arrayRecursive($data, $id, $pid, $val[$id],$isKey);
			if(empty($val['sub'])){unset($val['sub']);}
			if($isKey) $list[$val[$id]] = $val;
			else $list[] = $val;
		}
	}
	return $list;
}

function InStringByLikeSearch($str,$arr){
	$is_in = false;
	foreach($arr as $r){
		if(strstr($str,$r)){
			$is_in = true;
			break;
		}
	}
	return $is_in;
}

/* 截取中文字符串 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true){
	if(function_exists("mb_substr")){
		if($suffix)
			return mb_substr($str, $start, $length, $charset);
        else
			return mb_substr($str, $start, $length, $charset);
	}
	elseif(function_exists('iconv_substr')) {
	 	if($suffix)
		  	return iconv_substr($str,$start,$length,$charset);
	 	else
		  	return iconv_substr($str,$start,$length,$charset);  
	}  
	$re['utf-8']  = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";  
	$re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";  
	$re['gbk']    = "/[x01-x7f]|[x81-xfe][x40-xfe]/";  
	$re['big5']   = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";  
	preg_match_all($re[$charset], $str, $match);  
	$slice = join("",array_slice($match[0], $start, $length));  
	if($suffix) return $slice;  
	return $slice;
}

/* 根据分类生成导航 
 *
 * 仅适用于二级分类
 * 
 */

function createNavByProductCategory($trader_type,$trader_id,$cid){
	
	$where =  '';
	$category = M("goods_category")->where("category_id=$cid".$where)->find();
	$nav_cate = "<a href='/Home/Index/index'>首页</a>";  
	if($category['pid']!=0){
		$parent_category=M("goods_category")->where("category_id=".$category['pid'].$where)->find();
		$nav_cate .= "<a href='/Home/Goods/goodsCat/".$parent_category['category_id']."'>&gt;".$parent_category['category_name']."</a>";
	} 
	$nav_cate .= "&gt;".$category['category_name'];	    
	return $nav_cate;
}

function getAttributes(){                                                          
	$attrs = M("goods_attributes")->alias("GA")->where("1=1")->select(); 
	if($attrs){
		foreach($attrs as $key=>$val){
			$attrs[$key]['attr_values'] = M("goods_attributes_value")->where("attr_id=".$val['attr_id'])->order("attr_value_id ASC")->select();
		}
	}
	return $attrs;
}

/**
 * 根据分类id,attrType返回对应类型的属性
 * @param string $categoryId 分类id
 * @param string $attrType 属性分类，1成品属性2成品规格3金工石属性
 * @return array 
 */
function getProductAttrByCat($categoryId, $attrType){
	$GCA = M("goods_category_attributes");
	$condition['category_id'] = $categoryId;
	$condition['type'] = $attrType;				//1成品属性2成品规格3金工石属性，只有成品属性才参与筛选
	$condition['input_type']  = array('neq',3);	//1单选2多选3手填，手填不参与筛选
	//$condition['is_filter']=1;					//只选出要筛选的属性
	//2016-1-2 修改，两表关联查出所有的属性列表
	$attrs = $GCA->alias('gca')->join('zm_goods_attributes as ga ON gca.attr_id=ga.attr_id','INNER')->order('sort_id')->where($condition)->select();

	//2016-4-2 新增三张表关联得到分销商属性筛选开启状态的数据
	$GAC = M('goods_attrcontorl');
	$list_contorl = $GAC->where(array('agent_id'=>C('agent_id')))->select();
	if($list_contorl && $attrs){
		foreach($attrs as $key=>$value){
			foreach($list_contorl as $k=>$v){
				if($value['attr_id'] == $v['attr_id']){
					$attrs[$key]['is_filter'] = $v['is_filter'];
					break;
				}
			}
		}
	}
	//删除数组中is_filter 不为1 的一维数组；
	foreach( $attrs as $keys => $values ) {
        if($values['is_filter'] != 1){
			unset($attrs[$keys]);
		} 
	}
 	//获取每种属性的所有属性值
 	$GAV = M("goods_attributes_value");
 	if($attrs)
 		foreach ($attrs as $key=>$val)
 			$attrs[$key]['attr_values'] = $GAV->where("attr_id=".$val['attr_id'])->order("attr_value_id ASC")->select();
	return $attrs;
}

function getProductCommonAttrByCat($categoryId, $attrType){
	$subIds = getSubCatIds($categoryId);
	$GCA    = M("goods_category_attributes");
	$condition['category_id'] = array('in', implode(',', $subIds));
	$condition['type'] = $attrType;				//1成品属性2成品规格3金工石属性，只有成品属性才参与筛选
	$condition['input_type'] = array('neq',3);	//1单选2多选3手填，手填不参与筛选
	$condition['is_filter']=1;					//只选出要筛选的属性
	//$condition['ga.agent_id']=C('agent_id');					//只选出要筛选的属性
	//$attrs = $GCA->alias('gca')->join('zm_goods_attributes as ga ON gca.attr_id=ga.attr_id','INNER')->order('sort_id')->where($condition)->getField('category_attr_id, attr_name');
	$attrs = $GCA->alias('gca')->join('zm_goods_attributes as ga ON gca.attr_id=ga.attr_id','INNER')->group('attr_name')->where($condition)->field('count(*),ga.attr_id,attr_name')->select();//count();
	//筛选出共有的属性

	//2016-4-2 新增三张表关联得到分销商属性筛选开启状态的数据
	$GAC = M('goods_attrcontorl');
	$list_contorl = $GAC->where(array('agent_id'=>C('agent_id')))->select();
	if($list_contorl && $attrs){
		foreach($attrs as $key=>$value){
			foreach($list_contorl as $k=>$v){
				if($value['attr_id'] == $v['attr_id']){
					$attrs[$key]['is_filter'] = $v['is_filter'];
					break;
				}
			}
		}
	}
	//删除数组中is_filter 不为1 的一维数组；
	foreach( $attrs as $keys => $values ) {
        if($values['is_filter'] != 1){
			unset($attrs[$keys]);
		} 
	}
	
	//获取每种属性的所有属性值
	$GAV = M("goods_attributes_value");
	if($attrs)
	foreach ($attrs as $key=>$val) {
		$attrs[$key]['attr_values'] = $GAV->where("attr_id=" . $val['attr_id'])->order("attr_value_id ASC")->select();
		$attrs[$key]['category_id'] = $categoryId;
	}
	return $attrs;
}

function getSubCatIds($categoryId){
	return M('goods_category')->where('pid='.$categoryId)->getField('category_id', true);
}

function getParentIDByUID(){
	return M('user')->where("uid='".$_SESSION['web']['uid']."' and agent_id = ".C('agent_id'))->getField('parent_id');
}

function getProductsByTrader($categorys,$trader_id){
	return M("goods")->where("thumb != '' AND category_id IN($categorys)")->order("create_time DESC")->limit(8)->select();
}

function getRegion($regionId){

	return M("region")->where("region_id=$regionId")->getField("region_name");
}
function formatRound($str,$decimal=2){
	return sprintf("%.".$decimal."f",round($str,$decimal));
}
function getTableByName($tablename){
	if($uType >1){
		$tablename .= "_".$this->traderId;
	}
	return $tablename;
}
function sp_is_mobile() {
	static $sp_is_mobile;
	if(!empty($_SESSION['is_inside_call'])){
		unset($_SESSION['is_inside_call']);
		return false;
	}
	if(!empty(cookie('is_inside_callback_to')))	return false;	

	if ( isset($sp_is_mobile) )
		return $sp_is_mobile;


	if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
		$sp_is_mobile = false;
	} elseif ( strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false // many mobile devices (all iPhone, iPad, etc.)
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false ) {
		$sp_is_mobile = true;
	} else {
		$sp_is_mobile = false;
	}

	return $sp_is_mobile;
}


//根据agent_id 查询指定 ID的用户的折扣
//没有默认是本域名下的agent_id;
function getUserDiscount($uid, $type='luozuan_discount',$agent_id = 0){//默认是裸钻，
	if(($type=='luozuan_discount' or $type=='sanhuo_discount' or $type=='goods_discount' or $type=='consignment_discount' or $type=="caizuan_discount") and $uid>0){

		if($agent_id == 0){
			$agent_id = C('agent_id');
		}
		return getUserConfigValue($uid,$type,$agent_id)	;	 
	}else{
		return 0;
	}	
}

//根据agent_id 查询钻明给分销商的加点
//yiji  
//如果$yiji=1,则只计算上级给自己的加点（自己是二级，则计算一级给的加单；自己是一级，则计算钻明给一级的加点），
//如果yiji=0 则返回（一级给二级的加点 + 钻明给一级的加点）的和，主要是针对二级的加点计算
function getAgentDiscount($type='luozuan_advantage', $yiji = 1, $agent_id = 0){//默认是裸钻，
	if($agent_id == 0 ){
		$agent_id = C('agent_id');
	}

	if(in_array($type,array('luozuan_advantage','caizuan_advantage','sanhuo_advantage','consignment_advantage'))){
		
		$jiadian   = M("trader")->where(" t_agent_id = ".$agent_id)->getField($type);
		if(!in_array($type,array('luozuan_advantage','caizuan_advantage'))){					
			$jiadian   = 100 + $jiadian;
		}
		$parent_id = get_parent_id();

		if($parent_id>0 and $yiji ==1){
			if(in_array($type,array('luozuan_advantage','caizuan_advantage'))){
				$jiadian = $jiadian + M("trader")->where(" t_agent_id = ".$parent_id)->getField($type);
			}else{
				$jiadian = ($jiadian/100) * (100+M("trader")->where(" t_agent_id = ".$parent_id)->getField($type));
			}
			
		}
		return $jiadian ;
	}else{
		return 0;
	}
}

//x汇率x钻重x（折扣+后台+用户）
function calcDiamondPrice($data){
    $price = $data['dia_global_price']*C('dollar_huilv')/100;
    if($data['uid']!=0){
    	$userdiscount = getUserDiscount($data['uid']);
    	$price *= ($data['dia_discount']+$userdiscount);
    }else{
    	$price *= $data['dia_discount'];
    }
    if($data['weight']){
    	$price *= $data['weight'];
    }
    return formatRound($price,2);
}

function getEncodeCookie($str){
	$arr = json_decode($str);
	foreach ($arr as $ascii)
		$res .= chr($ascii-8);//在前端login.html中，存cookie时加了8，解密时要减8
	return $res;
}


/**
	根据访问域名获取分销商ID
**/
function get_agent_id(){
	$http_host = $_SERVER['HTTP_HOST'];
	$http_host_array = explode('.',$http_host);
	$i = count($http_host_array);
	if( $i <= 3 ){
		if($http_host_array[0] == "www" || $http_host_array[0] == 'm') {
			unset($http_host_array[0]);
		}
	}else if( $i > 3 ){
		$i = $i - 2;
		for($i;$i>0;$i--) {
			unset($http_host_array[$i-1]);
		}
	}
	$http_host = implode($http_host_array, '.');
	$agent  = M('agent')->field('agent_id')->where(" domain='%s' or mobile_domain = '%s'",array($http_host,$http_host))->find();
	if(!$agent){
		$agent  = M('agent')->field('agent_id')->where(" domain='%s' or mobile_domain = '%s'",array($http_host,$http_host))->find();
	}
	
	$templateSetting	= M('agent_setting')->where(array('agent_id'=>$agent['agent_id']))->find();

	if(!$agent_id){
		$agent_id = 0;
	}
	if(is_null($templateSetting)){//没有值的话
		$templateSetting['custom_view_show'] = 0;//显示订制
	}
	C("templateSetting",$templateSetting);
	if(!$agent['agent_id']){
		//由于nginx做了通配
		header("HTTP/1.0 404 Not Found");
		header('location:http://127.0.0.1/?cdn=hisnum');
		exit;	//备案需要设置非正常域名不能返回正确状态码
	}
	return $agent['agent_id'];
}

/** 获取上级分销商ID **/
function get_parent_id(){
	return M('agent')->where("agent_id =".C("agent_id"))->getField('parent_id');
}
//获取二级分销商在一级中的用户id
function get_create_user_id(){
	return  M("trader")->where('agent_id = '.get_parent_id().' and t_agent_id='.C('agent_id'))->getField('create_user_id');
}
/**
	判断超级管理
 **/
function isSuperAgent(){
	$agent_id = C('agent_id');
	if( $agent_id == '7' ){
		return true;
	}else{
		return false;;
	}
}

function js_unescape($str) {  
    $ret = '';  
    $len = strlen($str);  
    for ($i = 0; $i < $len; $i++) {  
        if ($str[$i] == '%' && $str[$i+1] == 'u') {  
            $val = hexdec(substr($str, $i+2, 4));  
            if ($val < 0x7f) $ret .= chr($val);  
            else if($val < 0x800) $ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f));  
            else $ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f));  
            $i += 5;  
        } else if ($str[$i] == '%') {  
            $ret .= urldecode(substr($str, $i, 3));  
            $i += 2;  
        } else $ret .= $str[$i];  
    }  
    return $ret;  
}  
////给二级的代销加点
function get_pifa_consignment_advantage(){
	$pifa_consignment_advantage = M('trader')->where('t_agent_id ='. C('agent_id'))->getField('consignment_advantage');
	return $pifa_consignment_advantage/100;
}

//搜索过滤
function Search_Filter_var($var){
	$keyword = trim($var);
	$keyword = addslashes($var);
	$hostarray=array('#', '+','select','insert','-','"','/','--','and','or','update','delete');
	return str_replace($hostarray, " ", $var); 
}

//计算当前网站的裸钻加点
//0当前级别，1二级
function getLuoZuanPoint( $is_parent = '0' ,$type = 'luozuan_advantage'){
	$agent_id = C('agent_id');
	$point    = '0';
	$T        = M('trader');
	$trader   = $T -> where(' t_agent_id = '.$agent_id)->find();
	if($trader) {
		if( !$is_parent ) {
			$point  = $trader[$type];
		}else{
			$point  = '0';
		}
		$parent_id  = get_parent_id();
		if ( $parent_id ) {
			$trader = $T->where( ' t_agent_id = ' . $parent_id )->find();
			if ( $trader ) {
				$point += intval($trader[$type]);
			}
		}
	}
	return $point;
}


//如果是二级分销商，那么需要确定上级是否开启了同步裸钻数据，如果上级没有开启，二级自己的配置就会失效。
function isSyncLuozuan(){
	$agent_id  = C('agent_id');
	$info1     = M('config_value') -> where("agent_id = $agent_id and config_key = 'is_sync_luozuan'") -> find();
	$parent_id = get_parent_id();

	if( empty($parent_id) ){
		return $info1['config_value']?'1':'0';
	} else {
		$info  = M('config_value') -> where("agent_id = $parent_id and config_key = 'is_sync_luozuan'") -> find();
		if( $info['config_value'] ){
			return $info1['config_value']?'1':'0';
		}else{
			return '0';
		}
	}
}


    /**
     * 价格区间加点
     * zhy	find404@foxmail.com
     * 2017年1月16日 10:26:09
     */
	function getGoodsListPrice($data, $uid=0, $type='luozuan',  $shuliang = 'all', $agent_id = 0){
		if($agent_id == 0){
			$agent_id 						= C('agent_id');
			$sanhuo_advantage   			= C('sanhuo_advantage');//散货零售加点
			$consignment_advantage 			= C('consignment_advantage');//成品/订制的零售加点
			$dollar_huilv 					= C('dollar_huilv');
			$new_luozuan_advantage_type 	= C('luozuan_advantage_type');	
			$new_sanhuo_advantage_type  	= C('sanhuo_advantage_type');
			$new_consignment_advantage_type = C('consignment_advantage_type');
			$new_caizuan_advantage_type 	= C('caizuan_advantage_type');
			$price_display_type 			= C('price_display_type');			
		}else{
			$sanhuo_advantage      			= getAgentConfigValue('sanhuo_advantage',      		$agent_id); 
			$consignment_advantage 			= getAgentConfigValue('consignment_advantage', 		$agent_id); 
			$dollar_huilv          			= getAgentConfigValue('dollar_huilv',          		$agent_id);
			$new_luozuan_advantage_type     = getAgentConfigValue('luozuan_advantage_type', 	$agent_id);
			$new_sanhuo_advantage_type      = getAgentConfigValue('sanhuo_advantage_type',  	$agent_id);
			$new_consignment_advantage_type = getAgentConfigValue('consignment_advantage_type', $agent_id);
			$new_caizuan_advantage_type     = getAgentConfigValue('caizuan_advantage_type',     $agent_id);
			$price_display_type     		= getAgentConfigValue('price_display_type',     	$agent_id);			
		}
 
		if($shuliang == 'single'){
			$dataList[0] = $data;
		}else{
			$dataList    = $data;
		}
		
		//获取零售加点, 零售加点在后台【系统】【系统设置】中设置			
		if($dataList){
			if($type == 'luozuan'){
				$goods_type = 1;
				//裸钻的零售加点（依据重量，白钻/彩钻）设置灵活，裸钻的零售加点在下面的foreach语句中实现
			}elseif($type == 'sanhuo'){
				$goods_type         = 2 ;
				if($uid>0){	                                //会员折扣
					if($new_sanhuo_advantage_type=='1'){
						$sale_jiadian = getUserDiscount($uid, 'sanhuo_discount',$agent_id);
					}else{
						$sale_jiadian = intval($sanhuo_advantage) + getUserDiscount($uid, 'sanhuo_discount',$agent_id);
					}
				}else{
					if($new_sanhuo_advantage_type=='1'){
						$sale_jiadian = 0;
					}else{
						$sale_jiadian = intval($sanhuo_advantage);
					}
				}
			}elseif($type == 'consignment'){
				$goods_type = 4 ;
			}elseif($type == 'szzmzb'){ //szzmzb 板房
				$goods_type = 16 ;
			}

 
			foreach($dataList as $key=>$val){
				if($val['is_banfang_base_goods']){
					$goods_type = 16;
				}
				switch($goods_type){
					case 1:
						//零售加点，					
						//依据重量，白钻/彩钻来计算  
						$luozuan_advantage = get_luozuan_advantage($dataList[$key]['weight'],$val['luozuan_type'],$agent_id);
						$dataList[$key]['shape_name'] = M('goods_luozuan_shape')->where(array('shape'=>$val['shape']))->getField('shape_name');//裸钻形状
						// 供应商加点					
						// 松林已经在 Common/Model/GoodsLuozuanModel.class.php 中的 public function field()实现了裸钻的自动加点,dia_discount 中已经包含了分销商的加点 所以这里不用计算裸钻分销商的加点。
						//获取裸钻信息请用D('Common/GoodsLuozuan');
						$caigou_jiadian = $dataList[$key]['dia_discount'];
						//这里存在一个问题，就是裸钻的国际报价发生了变化怎么办，购物车中
						
						//最终的折扣（供应商加点，会员折扣，零售加点）
						$sale_jiadian = get_luozuan_sale_jiadian($uid, $val['weight'], $dataList[$key]['dia_discount'],$val['luozuan_type'], $agent_id,$val['preferential_id']);
				
						// var_dump($sale_jiadian);
						//用户折扣,若为特惠钻石，则不加用户折扣
						if($uid>0 && empty($val['preferential_id'])){
							if($val['luozuan_type'] == 1){
								$userdiscount = getUserDiscount($uid, 'caizuan_discount',$agent_id);
							}else{
								$userdiscount = getUserDiscount($uid, 'luozuan_discount',$agent_id);
							}
						}else{
								$userdiscount = 0;					
						}
 
						if(!$dollar_huilv){
							$dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
						}
						
						$price = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight']; 			//每个单价  4093.2
					
						$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] = formatRound($price*$sale_jiadian/100, 2);//销售单价  god， price和xiaoshou_price是一样的，被前台页面使用过的绕晕死掉了
						
						//彩钻加点
						if($val['luozuan_type']=='1'){
							if($new_caizuan_advantage_type=='1'){												//价格区间
								$point = M('config_interval_point')->where('  config_key = \'caizuan_advantage_type\' and agent_id = '.$agent_id .' and interval_type =1 and min_value <= '.$dataList[$key]['price'].' and max_value >='. $dataList[$key]['price'] )->getField('point');
								if($point){
									$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	* $dataList[$key]['dia_discount']/100 * (100+$userdiscount+$point)/100, 2);	
									$dia_discount_all_type=	$dataList[$key]['dia_discount'] * (1+$userdiscount+$point)/10;									
								}else{
									$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	* $dataList[$key]['dia_discount']/100 * (100+$userdiscount)/100, 2);
									$dia_discount_all_type= $dataList[$key]['dia_discount'] * (1+$userdiscount)/10;											
								}
							}elseif($new_caizuan_advantage_type=='2'){												//重量区间
 
								$point = M('config_interval_point')->where('  config_key = \'caizuan_advantage_type\' and agent_id = '.$agent_id .' and interval_type =2 and min_value <= '.$val['weight'].' and max_value >='. $val['weight'] )->getField('point');
								if($point){
									$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	* $dataList[$key]['dia_discount']/100 * (100+$userdiscount+$point)/100, 2);	
									$dia_discount_all_type   = $dataList[$key]['dia_discount'] * (1+$userdiscount+$point)/10;									
								}else{
									$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	* $dataList[$key]['dia_discount']/100 * (100+$userdiscount)/100, 2);
									$dia_discount_all_type   = $dataList[$key]['dia_discount'] * (1+$userdiscount)/10;											
								}
							}
							if($point){
								$dataList[$key]['cur_price']    = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] * $dollar_huilv * $dataList[$key]['dia_discount']/100 * (100+$userdiscount+$point)/100, 2) ;//每卡单价 
							}else{
								$dataList[$key]['cur_price']    = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] * $dollar_huilv * $dataList[$key]['dia_discount']/100 * (100+$userdiscount)/100, 2) ;//每卡单价 
							}
						}else{
						//裸钻加点
							if($new_luozuan_advantage_type=='1'){												//价格区间
								$point = M('config_interval_point')->where(' config_key = \'luozuan_advantage_type\' and agent_id = '.$agent_id .'  and interval_type =1 and min_value <='.$dataList[$key]['price'].' and max_value >='. $dataList[$key]['price'] )->getField('point');
								if($point){
	 								if($price_display_type != 1) {
										//直接加点:价格X(折扣+证书货加点+用户折扣)		
										$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	*	($sale_jiadian+$point)/100, 2);
										$dia_discount_all_type=	($sale_jiadian+$point);									
									}else{
										//基础上加点:价格X折扣*(1+证书货加点+用户折扣)
										$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	*	($dataList[$key]['dia_discount']/100) *	((100+$userdiscount+$point)/100),2);
										$dia_discount_all_type	 = $dataList[$key]['dia_discount'] *(100+$userdiscount+$point)/100;
									}
								}
							}elseif($new_luozuan_advantage_type=='2'){											//重量区间
								$point = M('config_interval_point')->where('  config_key = \'luozuan_advantage_type\' and agent_id = '.$agent_id .'  and interval_type =2 and min_value <= '.$val['weight'].' and max_value >='. $val['weight'] )->getField('point');
								if($point){
	 								if($price_display_type != 1) {
										//直接加点:价格X(折扣+证书货加点+用户折扣)		
										$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	*	($sale_jiadian+$point)/100, 2);
										$dia_discount_all_type=	($sale_jiadian+$point);									
									}else{
										//基础上加点:价格X折扣*(1+证书货加点+用户折扣)
										$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] =	formatRound($price	*	($dataList[$key]['dia_discount']/100) *	((100+$userdiscount+$point)/100),2);
										$dia_discount_all_type=$dataList[$key]['dia_discount'] *(1+$userdiscount+$point);
									}							
								}					
							}
							
							//裸钻特惠价格
							if($val['preferential_id']){
								$dataList[$key]['price'] = $dataList[$key]['preferential_price'] = formatRound($dataList[$key]['price']*(100-$val['pre_discount'])/100 , 2);
							}
							
	 
							if($point){
								$dataList[$key]['cur_price']    = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] * $dollar_huilv* ($sale_jiadian+$point)/100 , 2) ;//每卡单价 
							}else{
								$dataList[$key]['cur_price']    = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] * $dollar_huilv* $sale_jiadian/100 , 2) ;//每卡单价 
							}
						}
						////////不能更改的字段名称。start
						//$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] = formatRound($price*$sale_jiadian/100, 2);//销售单价  god， price和xiaoshou_price是一样的，被前台页面使用过的绕晕死掉了
						if( C('agent_id') != $dataList[$key]['agent_id'] ){
							$top_dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
						}else{
							$top_dollar_huilv = $dollar_huilv;
						}
						$zuanming_price                 = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight'];
						$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['dia_global_price'] * $top_dollar_huilv * $val['weight'] * $caigou_jiadian/100 , 2);//采购单价

						///////不能更改的end，
						/////////////////////////////////////////////////////////购物车中的选项
						$dataList[$key]['userdiscount']                = $userdiscount; 
						$dataList[$key]['luozuan_advantage']           = $luozuan_advantage;
						if( $val['luozuan_type'] == 1 ){
							$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('caizuan_advantage', 0, $agent_id);
						}else{
							$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('luozuan_advantage', 0, $agent_id);
						}
						$dataList[$key]['price_display_type']  	   = getAgentConfigValue('price_display_type', $agent_id);
						if($dia_discount_all_type){
							$dataList[$key]['dia_discount_all']        = $dia_discount_all_type;
						}else{
							$dataList[$key]['dia_discount_all']        = $sale_jiadian;	                      
						}	
					break;

					case 2:				
						
						$dataList[$key]['caigou_price']   = formatRound($val['goods_price'], 2);
						$dataList[$key]['xiaoshou_price'] = formatRound($val['goods_price'] * (100 + $sale_jiadian)/100, 2);
						if (empty ( $val['nickname'] ))$dataList[$key]['nickname'] = '系统'; // 录入人

						////////////////////////////////////////////////////////////////购物车中的信息
						$dataList[$key]['userdiscount']           = getUserDiscount($uid, 'sanhuo_discount',$agent_id); //会员折扣

						$dataList[$key]['agent_sanhuo_advantage'] = 0;//不需要  分销加点不计算？？
						$dataList[$key]['sanhuo_advantage']       = 0;//不需要  零售加点为什么要为0	                
						//sale_jiaidan 已经加了用户折扣和零售加点，这里没有加上分销商加点。
						
						$dataList[$key]['goods_price'] 			  = formatRound($val['goods_price'] * (100 + $sale_jiadian)/100, 2);
						$dataList[$key]['goods_price2']           = formatRound($val['goods_price2'] * (100 + $sale_jiadian)/100, 2);
						if($new_sanhuo_advantage_type=='1'){
							$point = M('config_interval_point')->where(' config_key = \'sanhuo_advantage_type\' and agent_id = '.$agent_id .'  and interval_type =1 and min_value <= '.$dataList[$key]['goods_price'].' and max_value >='.$dataList[$key]['goods_price'] )->getField('point');
							if($point){
								$dataList[$key]['goods_price'] 		  = $dataList[$key]['xiaoshou_price'] = formatRound($val['goods_price'] * (100 + $sale_jiadian+$point)/100, 2);
								$dataList[$key]['goods_price2'] 	  = $dataList[$key]['xiaoshou_price'] = formatRound($val['goods_price'] * (100 + $sale_jiadian+$point)/100, 2);							
							}					 
						}

					break;

					case 3://成品和定制是一样的
					case 4:

						$xiaoshou_jiadian     = $consignment_advantage; //+ $caigou_jiadian;



						/*if( $val['agent_id']  == $agent_id && !$val['banfang_goods_id']){// 自己的货不用加点
							$xiaoshou_jiadian = 0;
						}*/
						if($uid>0){	//有会员id的时候，定制和成品的会员加减点不一样
							$consignmentDiscount = getUserDiscount($uid, 'consignment_discount',$agent_id);//定制
							$goodsDiscount       = getUserDiscount($uid, 'goods_discount',$agent_id);//成品
						}else{
							$consignmentDiscount = 0;
							$goodsDiscount       = 0;
						}
						//活动产品折扣，如果activity_status存在值（0或1），则为活动商品  
						if($val['activity_status'] == '1' || $val['activity_status'] == '0' ){   //活动折扣
							$activity_advantage	= getAgentConfigValue('activity_advantage',$agent_id);
						}else{
							$activity_advantage = 0;
						}
						if($val['goods_type'] == 3){
							$xiaoshou_jiadian = $xiaoshou_jiadian + $goodsDiscount ;
							$dataList[$key]['userdiscount'] = $goodsDiscount; 
						}elseif($val['goods_type'] == 4) {
							$xiaoshou_jiadian = $xiaoshou_jiadian + $consignmentDiscount;	
							if(!empty($dataList[$key]['goods_attrs'])){ //不为空时是订单中的
								$dataList[$key]['goods_price'] = getConsignmentPrice($dataList[$key]['goods_attrs']['goods_id'], $dataList[$key]['goods_attrs']['associateInfo']['material_id'],  $dataList[$key]['goods_attrs']['luozuanInfo']['gal_id'], $dataList[$key]['goods_attrs']['deputystone']['gad_id']);
							}
							
							$banfang_goods_id = M('goods') -> where("goods_id = '".$val['goods_id']."'" ) -> getField('banfang_goods_id');
							$goods_agent_id   = M('goods') -> where("goods_id = '".$val['goods_id']."'" ) -> getField('agent_id');							
							$additional_price = M('goods_associate_info') -> where("goods_id = '".$val['goods_id']."'  and additional_price > 0 " ) -> getField('additional_price');
							if( $agent_id != $goods_agent_id ){
								if($banfang_goods_id >0 && $additional_price ){

									$point2       	  = M('trader') -> where(' t_agent_id = ' . $agent_id) -> getField('consignment_advantage');
									$dataList[$key]['goods_price'] = $dataList[$key]['goods_price'] +( 100 + $point2 ) / 100 * $additional_price;
								}
							}else{
								if($banfang_goods_id >0 && $additional_price ){
									$dataList[$key]['goods_price']	= $dataList[$key]['goods_price'] + $additional_price;
								}
							}
							$dataList[$key]['userdiscount'] = $consignmentDiscount;
						}


						//caigou_price  和goods_price不能变换位置，否则caigou_price会变的很大
						$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['goods_price'],2);

						if($new_consignment_advantage_type=='1'){
							$dataList[$key]['goods_price']=formatRound($dataList[$key]['goods_price'] * (100 + $dataList[$key]['userdiscount'])/100, 2);
							$point = M('config_interval_point')->where(' config_key = \'consignment_advantage_type\'  and agent_id = '.$agent_id .'  and interval_type =1 and min_value <= '.$dataList[$key]['goods_price'].' and max_value >='.$dataList[$key]['goods_price'] )->getField('point');
							if($point){
								/*if( $val['agent_id']  == $agent_id && $val['banfang_goods_id']=='0'){		// 自己的货不用加点
									$dataList[$key]['goods_price'] 		  	= formatRound($dataList[$key]['caigou_price'] * (100 + $dataList[$key]['userdiscount'])/100,2);
								}else{
									$dataList[$key]['goods_price'] 		  	= formatRound($dataList[$key]['caigou_price'] * (100 + $dataList[$key]['userdiscount']+$point)/100,2);
								}*/
								$dataList[$key]['goods_price'] 		  	= formatRound($dataList[$key]['caigou_price'] * (100 + $dataList[$key]['userdiscount']+$point)/100,2);
							}
						}else{
								$dataList[$key]['goods_price']  		= formatRound($dataList[$key]['goods_price'] * (100 + $xiaoshou_jiadian)/100,2);
						}
						//2016-11-7 追加活动时间区间功能，如果在活动时间则显示活动价格，否则显示普通售卖价格
						$c_activity_time        = getAgentConfigValue('activity_time',$agent_id);
						$activity_time			= explode(',',$c_activity_time);
						$nowHour				= date('H',time());

						
						
						if($nowHour >= intval($activity_time[0]) && $nowHour < intval($activity_time[1]) && $activity_advantage!=0){
							$dataList[$key]['activity_price']=formatRound($dataList[$key]['goods_price']*(100 + $activity_advantage)/100,2);
						}else{
							$dataList[$key]['activity_price']=$dataList[$key]['goods_price'];
						}
						// if($nowHour >= intval($activity_time[0]) && $nowHour < intval($activity_time[1]) && $activity_advantage!=0){
							// $dataList[$key]['activity_price']=formatRound($dataList[$key]['goods_price']*(100 + $activity_advantage)/100,2);					
							// if($new_consignment_advantage_type=='1'){
								// $point = M('config_interval_point')->where(' config_key = \'consignment_advantage_type\' and agent_id = '.$agent_id .'  and interval_type =1 and min_value <= '.$dataList[$key]['activity_price'].' and max_value >='.$dataList[$key]['activity_price'] )->getField('point');
								// if($point){
									// $dataList[$key]['activity_price'] 	=	$dataList[$key]['goods_price']=	formatRound($dataList[$key]['caigou_price']*(100 + $activity_advantage+$point)/100,2);
								// }
							// }
						// }else{			
							    // $dataList[$key]['activity_price']=$dataList[$key]['goods_price'];
						// }	
 
						
						$dataList[$key]['marketPrice']  = formatRound($dataList[$key]['goods_price']*1.5, 2);
						//成品订制中没有$caigou_jiadian 这里改要讲分销加点算上。
						$dataList[$key]['agent_consignment_advantage'] = $caigou_jiadian;
						$dataList[$key]['consignment_advantage']       = $consignment_advantage; //零售加点
					break;
					case 16://szzmzb

						$parent_id = get_parent_id();
						$trader_consignment_advantage = M('trader')->where(' t_agent_id = '.C('agent_id'))->getField('consignment_advantage');
						if($parent_id > 0){ //如果有上级 添加上级加点
							$trader_consignment_advantage += M('trader')->where(' t_agent_id = '.$parent_id)->getField('consignment_advantage');
						}
						$dataList[$key]['goods_price'] = $val['goods_price']*(100+$trader_consignment_advantage)/100;

						$xiaoshou_jiadian     = $consignment_advantage; //+ $caigou_jiadian;
						
						if( $val['agent_id']  == $agent_id && !$val['banfang_goods_id']){// 自己的货不用加点
							$xiaoshou_jiadian = 0;
						}

						if($uid>0){	//有会员id的时候，定制和成品的会员加减点不一样
							$consignmentDiscount = getUserDiscount($uid, 'consignment_discount',$agent_id);//定制
							$goodsDiscount       = getUserDiscount($uid, 'goods_discount',$agent_id);//成品
						}else{
							$consignmentDiscount = 0;
							$goodsDiscount       = 0;
						}
						//活动产品折扣，如果activity_status存在值（0或1），则为活动商品  
						if($val['activity_status'] == '1' || $val['activity_status'] == '0' ){   //活动折扣
							$activity_advantage	= getAgentConfigValue('activity_advantage',$agent_id);
						}else{
							$activity_advantage = 0;
						}
						if($val['goods_type'] == 15){ //szzmzb 成品 暂时没有
							$xiaoshou_jiadian = $xiaoshou_jiadian + $goodsDiscount ;
							$dataList[$key]['userdiscount'] = $goodsDiscount; 
						}elseif($val['goods_type'] == 16) { //szzmzb 定制
							$xiaoshou_jiadian = $xiaoshou_jiadian + $consignmentDiscount;	
							
							$dataList[$key]['userdiscount'] = $consignmentDiscount;
						}

						//caigou_price  和goods_price不能变换位置，否则caigou_price会变的很大
						$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['goods_price'],2);
						
						if($new_consignment_advantage_type=='1'){
							$dataList[$key]['goods_price']=formatRound($dataList[$key]['goods_price'] * (100 + $dataList[$key]['userdiscount'])/100, 2);
							$point = M('config_interval_point')->where(' config_key = \'consignment_advantage_type\'  and agent_id = '.$agent_id .'  and interval_type =1 and min_value <= '.$dataList[$key]['goods_price'].' and max_value >='.$dataList[$key]['goods_price'] )->getField('point');
							if($point){
								if( $val['agent_id']  == $agent_id && $val['banfang_goods_id']=='0'){		// 自己的货不用加点		
									$dataList[$key]['goods_price'] 		  	= formatRound($dataList[$key]['caigou_price'] * (100 + $dataList[$key]['userdiscount'])/100,2);
								}else{				
									$dataList[$key]['goods_price'] 		  	= formatRound($dataList[$key]['caigou_price'] * (100 + $dataList[$key]['userdiscount']+$point)/100,2);
								}			
							}			 
						}else{
								$dataList[$key]['goods_price']  		= formatRound($dataList[$key]['goods_price'] * (100 + $xiaoshou_jiadian)/100,2);
						}
						//2016-11-7 追加活动时间区间功能，如果在活动时间则显示活动价格，否则显示普通售卖价格
						$c_activity_time        = getAgentConfigValue('activity_time',$agent_id);
						$activity_time			= explode(',',$c_activity_time);
						$nowHour				= date('H',time());

						
						
						if($nowHour >= intval($activity_time[0]) && $nowHour < intval($activity_time[1]) && $activity_advantage!=0){
							$dataList[$key]['activity_price']=formatRound($dataList[$key]['goods_price']*(100 + $activity_advantage)/100,2);
						}else{
							$dataList[$key]['activity_price']=$dataList[$key]['goods_price'];
						}

						$dataList[$key]['marketPrice']  = formatRound($dataList[$key]['goods_price']*1.5, 2);
						//成品订制中没有$caigou_jiadian 这里改要讲分销加点算上。
						$dataList[$key]['agent_consignment_advantage'] = $caigou_jiadian;
						$dataList[$key]['consignment_advantage']       = $consignment_advantage; //零售加点
					break;
				}
			}

			if($shuliang == 'single'){
				return $dataList[0];
			}else{
				return $dataList;
			}
			
		}else{
			return false;
		}

	}


	
	
    /**
     * 价格重量区间加点
     * zhy	find404@foxmail.com
     * 2017年1月16日 10:26:09
     */	
	function GoodsPriceWeightSection(){
		
		
	}
	
	

/////////////得到裸钻的销售加点
//weight裸钻重量 $dia_discount裸钻最初的折扣,luozuan_type: 0 白钻 1 彩钻 
function get_luozuan_sale_jiadian($uid=0,$weight=0, $dia_discount=0,$luozuan_type=0, $agent_id = 0,$preferential_id=''){

	$sale_jiadian = 0;

	if($agent_id == 0){
		$agent_id = C('agent_id');
		$price_display_type = C('price_display_type');
	}else{
		$price_display_type = getAgentConfigValue('price_display_type',$agent_id);
	}
	//裸钻零售加点
	$luozuan_advantage = get_luozuan_advantage($weight,$luozuan_type,$agent_id);

	//用户折扣
	if($uid>0  && empty($preferential_id) ){
		if($luozuan_type == 1){
			$userdiscount = getUserDiscount($uid, 'caizuan_discount', $agent_id);
		}else{
			$userdiscount = getUserDiscount($uid, 'luozuan_discount', $agent_id);
		}	
	}else{
		$userdiscount = 0;
	}

	$sale_jiadian = $luozuan_advantage + $userdiscount;
 
	//$dia_discount 裸钻的折扣，包含了供应商的加点
	if($luozuan_type == 1){//彩钻加点只有基础加点
		$sale_jiadian = $dia_discount * (1+$sale_jiadian/100);
	}else{
		if($price_display_type != 1) {
			//直接加点:价格X(折扣+证书货加点+用户折扣)		
			$sale_jiadian = $dia_discount + $sale_jiadian;			
		}else{
			//基础上加点:价格X折扣*(1+证书货加点+用户折扣)
			$sale_jiadian = $dia_discount * (1+$sale_jiadian/100);
		}
	}
	return 	$sale_jiadian;;
}

/////依据重量获取裸钻的销售加点,$luozuan_type:0 白钻，1 彩钻
function get_luozuan_advantage($weight = 0,$luozuan_type = 0, $agent_id = 0){

	if($agent_id == 0 or $agent_id==C('agent_id')){
		$agent_id 						= C('agent_id');
		$c_caizuan_advantage 			= C('caizuan_advantage');
		$c_luozuan_advantage 			= C('luozuan_advantage');
		$new_caizuan_advantage_type 	= C('caizuan_advantage_type');
		$new_luozuan_advantage_type 	= C('luozuan_advantage_type');			
	}else{
		$c_caizuan_advantage 			= getAgentConfigValue('caizuan_advantage',$agent_id) ;
		$c_luozuan_advantage 			= getAgentConfigValue('luozuan_advantage',$agent_id) ;
		$new_caizuan_advantage_type     = getAgentConfigValue('caizuan_advantage_type',     $agent_id);
		$new_luozuan_advantage_type     = getAgentConfigValue('luozuan_advantage_type', 	$agent_id);		
	}
	$luozuan_advantage = 0; //裸钻加点
	if( $luozuan_type == 1 ){					//彩钻加点
		if($new_caizuan_advantage_type=='1' || $new_caizuan_advantage_type=='2'){
			return 0;
		}else{
			return $c_caizuan_advantage;
		}
	}else{
		if($new_luozuan_advantage_type=='1' || $new_luozuan_advantage_type=='2'){
			return 0;
		}else{		//白钻加点
			return $c_luozuan_advantage;
		}
	}
	
}

//依据分销商的加点设置来进行加点
function get_luozuan_agent_point($agent_id = 0, $type = 0){
	if($agent_id === 0){
		return 0;
	}
	$point    = 0;
	$type_string = 'luozuan_advantage';
	if($type=='1'){
		$type_string = "caizuan_advantage";
	}
	$T        = M('trader');
	$trader   = $T -> where(' t_agent_id = '.$agent_id)->find();
	if($trader) {
		$point = $trader[$type_string];
		$parent_id = get_parent_id();
		if ($parent_id) {
			$trader = $T -> where( ' t_agent_id = ' . $parent_id )->find();
			if ($trader) {
				$point += intval($trader[$type_string]);
			}
		}
	}
	return $point;
}



/**
 * @author：张超豪
 * content：根据产品列表获取出产品真正的价格(散货，成品，裸钻)
 * time	：2016-7-20
 * type = 'luozuan', 'sanhuo', 'consignment'
 * shuliang = 'all' =>select()的数据， 'single'=>find()的数据
**/
function getGoodsListPrice1($data, $uid=0, $type='luozuan',  $shuliang = 'all', $agent_id = 0){

	if($agent_id == 0){
		$agent_id = C('agent_id');
		$sanhuo_advantage   = C('sanhuo_advantage');//散货零售加点
		$consignment_advantage = C('consignment_advantage');//成品/订制的零售加点
		$dollar_huilv = C('dollar_huilv');			
	}else{
		$sanhuo_advantage      = getAgentConfigValue('sanhuo_advantage',      $agent_id); 
		$consignment_advantage = getAgentConfigValue('consignment_advantage', $agent_id); 
		$dollar_huilv          = getAgentConfigValue('dollar_huilv',          $agent_id); 
	}

	
	if($shuliang == 'single'){
		$dataList[0] = $data;
	}else{
		$dataList    = $data;
	}
	
	//获取零售加点, 零售加点在后台【系统】【系统设置】中设置			
	if($dataList){
		if($type == 'luozuan'){       
			$goods_type = 1;
			//裸钻的零售加点（依据重量，白钻/彩钻）设置灵活，裸钻的零售加点在下面的foreach语句中实现
		}elseif($type == 'sanhuo'){
			$goods_type         = 2 ;
			if($uid>0){	                                //会员折扣
				$sale_jiadian = intval($sanhuo_advantage) + getUserDiscount($uid, 'sanhuo_discount',$agent_id);
			}else{
				$sale_jiadian = intval($sanhuo_advantage);
			}
		}elseif($type == 'consignment'){
			$goods_type = 4 ;
		}

		foreach($dataList as $key=>$val){			
			switch($goods_type){
				case 1:
					//零售加点，					
					//依据重量，白钻/彩钻来计算  
					$luozuan_advantage = get_luozuan_advantage($dataList[$key]['weight'],$val['luozuan_type'],$agent_id);

					$dataList[$key]['shape_name'] = M('goods_luozuan_shape')->where(array('shape'=>$val['shape']))->getField('shape_name');//裸钻形状

					// 供应商加点					
					// 松林已经在 Common/Model/GoodsLuozuanModel.class.php 中的 public function field()实现了裸钻的自动加点,dia_discount 中已经包含了分销商的加点 所以这里不用计算裸钻分销商的加点。
					//获取裸钻信息请用D('Common/GoodsLuozuan');
				

					$caigou_jiadian = $dataList[$key]['dia_discount'];
					//这里存在一个问题，就是裸钻的国际报价发生了变化怎么办，购物车中
					
					//最终的折扣（供应商加点，会员折扣，零售加点）
					$sale_jiadian = get_luozuan_sale_jiadian($uid, $val['weight'], $dataList[$key]['dia_discount'],$val['luozuan_type'], $agent_id);
					//用户折扣
					if($uid>0){
						if($val['luozuan_type'] == 1){
							$userdiscount = getUserDiscount($uid, 'caizuan_discount',$agent_id);
						}else{
							$userdiscount = getUserDiscount($uid, 'luozuan_discount',$agent_id);
						}
					}else{
					 	$userdiscount = 0;					
					}	
					
					////////不能更改的字段名称。start
					$price = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight']; //每个单价					
					$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] = formatRound($price*$sale_jiadian/100, 2);//销售单价  god， price和xiaoshou_price是一样的，被前台页面使用过的绕晕死掉了
					
					if( C('agent_id') != $dataList[$key]['agent_id'] ){
					 	$top_dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
					}else{
					 	$top_dollar_huilv = $dollar_huilv;
					}
					 
					
					$zuanming_price                 = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight'];
					$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['dia_global_price'] * $top_dollar_huilv * $val['weight'] * $caigou_jiadian/100 , 2);//采购单价
	        		$dataList[$key]['cur_price']    = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] * $dollar_huilv* $sale_jiadian/100 , 2) ;//每卡单价 
	        		///////不能更改的end，

	        		/////////////////////////////////////////////////////////购物车中的选项
	        		$dataList[$key]['userdiscount']                = $userdiscount; 
	                $dataList[$key]['luozuan_advantage']           = $luozuan_advantage;
					if( $val['luozuan_type'] == 1 ){
						$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('caizuan_advantage', 0, $agent_id);
					}else{
						$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('luozuan_advantage', 0, $agent_id);
					}
	                
	                $dataList[$key]['price_display_type']  	   = getAgentConfigValue('price_display_type', $agent_id);
	                $dataList[$key]['dia_discount_all']        = $sale_jiadian;	                          		
				break;

				case 2:									
					$dataList[$key]['caigou_price']   = formatRound($val['goods_price'], 2);
					$dataList[$key]['xiaoshou_price'] = formatRound($val['goods_price'] * (100 + $sale_jiadian)/100, 2);
					if (empty ( $val['nickname'] ))$dataList[$key]['nickname'] = '系统'; // 录入人

					////////////////////////////////////////////////////////////////购物车中的信息
					$dataList[$key]['userdiscount']           = getUserDiscount($uid, 'sanhuo_discount',$agent_id); //会员折扣

					
	                $dataList[$key]['agent_sanhuo_advantage'] = 0;//不需要  分销加点不计算？？
	                $dataList[$key]['sanhuo_advantage']       = 0;//不需要  零售加点为什么要为0	                
	                //sale_jiaidan 已经加了用户折扣和零售加点，这里没有加上分销商加点。


	                $dataList[$key]['goods_price'] 			  = formatRound($val['goods_price'] * (100 + $sale_jiadian)/100, 2);
                    $dataList[$key]['goods_price2']           = formatRound($val['goods_price2'] * (100 + $sale_jiadian)/100, 2);
				break;

				case 3://成品和定制是一样的
				case 4:
					
					$xiaoshou_jiadian     = $consignment_advantage; //+ $caigou_jiadian;
					if( $val['agent_id']  == $agent_id ){// 自己的货不用加点
						$xiaoshou_jiadian = 0;
					}
					if($uid>0){	//有会员id的时候，定制和成品的会员加减点不一样
						$consignmentDiscount = getUserDiscount($uid, 'consignment_discount',$agent_id);//定制
						$goodsDiscount       = getUserDiscount($uid, 'goods_discount',$agent_id);//成品
					}else{
						$consignmentDiscount = 0;
						$goodsDiscount       = 0;
					}
					//活动产品折扣，如果activity_status存在值（0或1），则为活动商品  
					if($val['activity_status'] == '1' || $val['activity_status'] == '0' ){   //活动折扣
						$activity_advantage	= getAgentConfigValue('activity_advantage',$agent_id);
					}else{
						$activity_advantage = 0;
					}
				 	if($val['goods_type'] == 3){
				 		$xiaoshou_jiadian = $xiaoshou_jiadian + $goodsDiscount ;
				 		$dataList[$key]['userdiscount'] = $goodsDiscount; 
				 	}elseif($val['goods_type'] == 4) {
				 		$xiaoshou_jiadian = $xiaoshou_jiadian + $consignmentDiscount;	
						if(!empty($dataList[$key]['goods_attrs'])){ //不为空时是订单中的
							$dataList[$key]['goods_price'] = getConsignmentPrice($dataList[$key]['goods_attrs']['goods_id'], $dataList[$key]['goods_attrs']['associateInfo']['material_id'],  $dataList[$key]['goods_attrs']['luozuanInfo']['gal_id'], $dataList[$key]['goods_attrs']['deputystone']['gad_id']);
						}
						$dataList[$key]['userdiscount'] = $consignmentDiscount;
				 	}
					//caigou_price  和goods_price不能变换位置，否则caigou_price会变的很大
				 	$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['goods_price'],2);
					$dataList[$key]['goods_price']  = formatRound($dataList[$key]['goods_price']*(100 + $xiaoshou_jiadian)/100,2);
					//2016-11-7 追加活动时间区间功能，如果在活动时间则显示活动价格，否则显示普通售卖价格
					$c_activity_time        = getAgentConfigValue('activity_time',$agent_id);
					$activity_time			= explode(',',$c_activity_time);
					$nowHour				= date('H',time());
					if($nowHour >= intval($activity_time[0]) && $nowHour < intval($activity_time[1])){
						$dataList[$key]['activity_price']=formatRound($dataList[$key]['goods_price']*(100 + $activity_advantage)/100,2);
					}else{
						$dataList[$key]['activity_price']=$dataList[$key]['goods_price'];
					}
					$dataList[$key]['marketPrice']  = formatRound($dataList[$key]['goods_price']*1.5, 2);
					//成品订制中没有$caigou_jiadian 这里改要讲分销加点算上。
	                $dataList[$key]['agent_consignment_advantage'] = $caigou_jiadian;
	                $dataList[$key]['consignment_advantage']       = $consignment_advantage; //零售加点
				break;
			}
		}

		if($shuliang == 'single'){
			return $dataList[0];
		}else{
			return $dataList;
		}
		
	}else{
		return false;
	}
}

function priceForMat($price){
	return intval($price).'.00';
}

//////////获取一级域名
function regular_domain($domain) {
	if (substr ( $domain, 0, 7 ) == 'http://') {
	    $domain = substr ( $domain, 7 );
	}
	if (substr ($domain, 0, 8) == 'https://') {
	    $domain = substr ( $domain, 8);
	}
	if (strpos ( $domain, '/' ) !== false) {
	    $domain = substr ( $domain, 0, strpos ( $domain, '/' ) );
	}
	return strtolower ( $domain );
}
//////////获取一级域名
function top_domain($domain) {
    $domain = regular_domain ( $domain );
    $iana_root = array ('ac',
      'ad',      'ae',      'aero',    'af',      'ag',      'ai',      'al',      'am',      'an',
      'ao',      'aq',      'ar',      'arpa',    'as',      'asia',    'at',      'au',      'aw',
      'ax',      'az',      'ba',      'bb',      'bd',      'be',      'bf',      'bg',      'bh',
      'bi',      'biz',     'bj',      'bl',      'bm',      'bn',      'bo',      'bq',
      'br',      'bs',      'bt',      'bv',      'bw',      'by',      'bz',      'ca',
      'cat',     'cc',      'cd',      'cf',      'cg',      'ch',      'ci',      'ck',
      'cl',      'cm',      'cn',      'co',      'com',     'coop',    'cr',      'cu',
      'cv',      'cw',      'cx',      'cy',      'cz',      'de',      'dj',      'dk',
      'dm',      'do',      'dz',      'ec',      'edu',     'ee',      'eg',      'eh',
      'er',      'es',      'et',      'eu',      'fi',      'fj',      'fk',      'fm',
      'fo',      'fr',      'ga',      'gb',      'gd',      'ge',      'gf',      'gg',
      'gh',      'gi',      'gl',      'gm',      'gn',      'gov',     'gp',      'gq',
      'gr',      'gs',      'gt',      'gu',      'gw',      'gy',      'hk',      'hm',
      'hn',      'hr',      'ht',      'hu',      'id',      'ie',      'il',      'im',
      'in',      'info',    'int',     'io',      'iq',      'ir',      'is',      'it',
      'je',      'jm',      'jo',      'jobs',    'jp',      'ke',      'kg',      'kh',
      'ki',      'km',      'kn',      'kp',      'kr',      'kw',      'ky',      'kz',
      'la',      'lb',      'lc',      'li',      'lk',      'lr',      'ls',      'lt',
      'lu',      'lv',      'ly',      'ma',      'mc',      'md',      'me',      'mf',
      'mg',      'mh',      'mil',     'mk',      'ml',      'mm',      'mn',      'mo',
      'mobi',    'mp',      'mq',      'mr',      'ms',      'mt',      'mu',      'museum',
      'mv',      'mw',      'mx',      'my',      'mz',      'na',      'name',    'nc',
      'ne',      'net',     'nf',      'ng',      'ni',      'nl',      'no',      'np',
      'nr',      'nu',      'nz',      'om',      'org',     'pa',      'pe',      'pf',
      'pg',      'ph',      'pk',      'pl',      'pm',      'pn',      'pr',      'pro',
      'ps',      'pt',      'pw',      'py',      'qa',      're',      'ro',      'rs',
      'ru',      'rw',      'sa',      'sb',      'sc',      'sd',      'se',      'sg',
      'sh',      'si',      'sj',      'sk',      'sl',      'sm',      'sn',      'so',
      'sr',      'ss',      'st',      'su',      'sv',      'sx',      'sy',      'sz',
      'tc',      'td',      'tel',     'tf',      'tg',      'th',      'tj',      'tk',
      'tl',      'tm',      'tn',      'to',      'tp',      'tr',      'travel',  'tt',
      'tv',      'tw',      'tz',      'ua',      'ug',      'uk',      'um',      'us',
      'uy',      'uz',      'va',      'vc',      've',      'vg',      'vi',      'vn',
      'vu',      'wf',      'ws',      'xxx',      'ye',     'yt',      'za',      'zm',      'zw' 
    );
    $sub_domain = explode ( '.', $domain );
    $top_domain = '';
    $top_domain_count = 0;
    for($i = count ( $sub_domain ) - 1; $i >= 0; $i --) {
	    if ($i == 0) {
	       break;
	    }
	    if (in_array ( $sub_domain [$i], $iana_root )) {
		    $top_domain_count ++;
		    $top_domain = '.' . $sub_domain [$i] . $top_domain;
		    if($top_domain_count >= 2){
		        break;
		    }
	    }
    }
    $top_domain = $sub_domain [count ( $sub_domain ) - $top_domain_count - 1] . $top_domain;
    return $top_domain;
}

function getParentIDByUsername(){
	$user_id = getParentIDByUID();
	return M('admin_user')->where("user_id='".$user_id."' and agent_id = ". C('agent_id'))->getField('user_name');
}

function getParentIDByEmail(){
	$user_id = getParentIDByUID();
	return M('admin_user')->where("user_id='".$user_id."' and agent_id = ". C('agent_id'))->getField('email');
}

//得到定制产品的价格
function getConsignmentPrice($goods_id, $material_id=0,  $gal_id=0, $gad_id=0){
	$G   = D('Common/Goods');
	$GM  = M('goods_material');//材质表	
    $GAI = M('goods_associate_info');//产品材质损耗金重金价关联表    
    $GAL = M('goods_associate_luozuan');//产品材质裸钻关联表						    
    $GAD = M('goods_associate_deputystone');//产品副石关联表

    $agent_id = $G -> where('goods_type = 4 and goods_id = '.$goods_id)->getField('agent_id');

    if(empty($material_id)){
    	return false;
    }
    //材质如18K金，18K白金等的价格
	$where = 'goods_id = '. intval($goods_id).' and agent_id = '.$agent_id;

    $gold_price   = $GM  -> where("agent_id = $agent_id and material_id = ".$material_id)->getField('gold_price');
    $GAI_info     = $GAI -> where($where . ' and material_id = '.$material_id)->find();
    $basic_cost   = $GAI_info['basic_cost'];
    $weights_name = $GAI_info['weights_name'];
    $loss_name    = $GAI_info['loss_name'];

    if($gal_id > 0){
    	$luozuan_price     = $GAL->where($where . ' and gal_id = '.$gal_id.' and material_id = '.$material_id)->getField('price');
    }
	if($gad_id > 0){
		$deputystone_price = $GAD->where($where . ' and gad_id = '.$gad_id)->getField('deputystone_price');
	}
	
	$price = formatRound(($basic_cost + $deputystone_price + $luozuan_price + $gold_price * $weights_name * (1 + $loss_name/100)) ,2); 
	return $price;
}

//分类是否显示手寸
function is_show_hand($category_id, $goods_type=4){
	$categorys = array(60,65,71,80,86,94,104,105,119,182);
	return $goods_type==4 && in_array($category_id, $categorys);
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".urlencode($val)."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;
}
/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para) {
	$para_filter = array();
	while (list ($key, $val) = each ($para)) {
		if($key == "sign" || $key == "sign_type" || $val == "")continue;
		else	$para_filter[$key] = $para[$key];
	}
	return $para_filter;
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort($para) {
	ksort($para);
	reset($para);
	return $para;
}
/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult($word='') {
	$fp = fopen("log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

/**
 * 远程获取数据，POST模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * @param $para 请求的数据
 * @param $input_charset 编码格式。默认值：空值
 * return 远程输出的数据
 */
function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

	if (trim($input_charset) != '') {
		$url = $url."_input_charset=".$input_charset;
	}
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
	curl_setopt($curl,CURLOPT_POST,true); // post传输数据
	curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
	curl_close($curl);
	
	return $responseText;
}


/**
 * 以get方式提交请求
 * @param $url
 * @return bool|mixed
 */
function httpGet($url)
{
    $oCurl = curl_init();
    if (stripos($url, "https://") !== false) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if (intval($aStatus["http_code"]) == 200) {
        return $sContent;
    }
    return false;
}

/**
 * 远程获取数据，GET模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * return 远程输出的数据
 */
function getHttpResponseGET($url,$cacert_url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址	
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
	curl_close($curl);
	
	return $responseText;
}

/**
 * 实现多种字符编码方式
 * @param $input 需要编码的字符串
 * @param $_output_charset 输出的编码格式
 * @param $_input_charset 输入的编码格式
 * return 编码后的字符串
 */
function charsetEncode($input,$_output_charset ,$_input_charset) {
	$output = "";
	if(!isset($_output_charset) )$_output_charset  = $_input_charset;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset change.");
	return $output;
}
/**
 * 实现多种字符解码方式
 * @param $input 需要解码的字符串
 * @param $_output_charset 输出的解码格式
 * @param $_input_charset 输入的解码格式
 * return 解码后的字符串
 */
function charsetDecode($input,$_input_charset ,$_output_charset) {
	$output = "";
	if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset changes.");
	return $output;
}

/**
 * 签名字符串
 * @param $prestr 需要签名的字符串
 * @param $key 私钥
 * return 签名结果
 */
function md5Sign($prestr, $key) {
	$prestr = $prestr . $key;
	return md5($prestr);
}

/**
 * 验证签名
 * @param $prestr 需要签名的字符串
 * @param $sign 签名结果
 * @param $key 私钥
 * return 签名结果
 */
function md5Verify($prestr, $sign, $key) {
	$prestr = $prestr . $key;
	$mysgin = md5($prestr);

	if($mysgin == $sign) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * auth	：fangkai
 * @param：根据键值排序
 * time	：2016-11-1
**/
function arraySort($arr,$sort,$v){    //$arr->数组   $sort->排序顺序标志   $value->排序字段

	if($sort == "0"){                   //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			$sort = "SORT_ASC";
	}elseif ($sort == "1") {
			$sort = "SORT_DESC";
	}
	  
	foreach($arr as $uniqid => $row){  
		foreach($row as $key=>$value){                     
				$arrsort[$key][$uniqid] = $value;
			}  
		}  
		if($sort){
		array_multisort($arrsort[$v], constant($sort), $arr);  
	}       
	return $arr;
}

//判断是否为手机号码
function is_mobile($mobile)
{
	return preg_match('/^(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[0-9])[0-9]{8}$/',$mobile);
}

/**
 * auth	：fangkai
 * content：获取随机数
			$length:随机数长度，$chars:组合的字符串
 * time	：2016-5-3
**/
function code($length,$chars){
	$code = '';
	$max = strlen($chars) - 1;
	for($i = 0; $i < $length; $i++) {
		$code .= $chars[mt_rand(0, $max)];
	}
	return $code;
}

/*

getCaigouGoodsListPrice 只用在了后台的添加订单中
 */
function getCaigouGoodsListPrice($data, $uid=0, $type='luozuan',  $shuliang = 'all'){

	$agent_id = get_parent_id();
	// if($agent_id == 0){
	// 	$agent_id = C('agent_id');
	// 	$sanhuo_advantage   = C('sanhuo_advantage');//散货零售加点
	// 	$consignment_advantage = C('consignment_advantage');//成品/订制的零售加点
	// 	$dollar_huilv = C('dollar_huilv');			
	// }else{
	// 	$sanhuo_advantage      = getAgentConfigValue('sanhuo_advantage',      $agent_id); 
	// 	$consignment_advantage = getAgentConfigValue('consignment_advantage', $agent_id); 
 	//  $dollar_huilv          = getAgentConfigValue('dollar_huilv',          $agent_id); 
	// }
	$dollar_huilv          = getAgentConfigValue('dollar_huilv',          $agent_id);
	$sanhuo_advantage      = 0;//散货零售加点
	$consignment_advantage = 0;//成品/订制的零售加点 


	
	if($shuliang == 'single'){
		$dataList[0] = $data;
	}else{
		$dataList    = $data;
	}
	
	//获取零售加点, 零售加点在后台【系统】【系统设置】中设置			
	if($dataList){
		if($type == 'luozuan'){       
			$goods_type = 1;
			//裸钻的零售加点（依据重量，白钻/彩钻）设置灵活，裸钻的零售加点在下面的foreach语句中实现
		}elseif($type == 'sanhuo'){
			$goods_type         = 2 ;
		}elseif($type == 'consignment'){
			$goods_type = 4 ;
		}elseif($type == 'consignment_szzmzb'){
			$goods_type = 16;
		}

		foreach($dataList as $key=>$val){
			switch($goods_type){
				case 1:
					//零售加点，					
					//依据重量，白钻/彩钻来计算  
					//$luozuan_advantage = get_luozuan_advantage($dataList[$key]['weight'],$val['luozuan_type'],$agent_id);

					$dataList[$key]['shape_name'] = M('goods_luozuan_shape')->where(array('shape'=>$val['shape']))->getField('shape_name');//裸钻形状

					// 供应商加点					
					// 松林已经在 Common/Model/GoodsLuozuanModel.class.php 中的 public function field()实现了裸钻的自动加点,dia_discount 中已经包含了分销商的加点 所以这里不用计算裸钻分销商的加点。
					//获取裸钻信息请用D('Common/GoodsLuozuan');
				

					//$caigou_jiadian = $dataList[$key]['dia_discount'];
					if($val['luozuan_type'] == 1){
						$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('caizuan_advantage', 0, $agent_id);
					}else{
						$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('luozuan_advantage', 0, $agent_id);

					}
					$caigou_jiadian = $dataList[$key]['dia_discount'] - $dataList[$key]['agent_luozuan_advantage']; 
					//这里存在一个问题，就是裸钻的国际报价发生了变化怎么办，购物车中
					
					//最终的折扣（供应商加点，会员折扣，零售加点）
					//$sale_jiadian = get_luozuan_sale_jiadian($uid, $val['weight'], $dataList[$key]['dia_discount'],$val['luozuan_type'], $agent_id);
					$sale_jiadian = $dataList[$key]['dia_discount'];


					//用户折扣
					// if($uid>0){
					// 	if($val['luozuan_type'] == 1){
					// 		$userdiscount = getUserDiscount($uid, 'caizuan_discount',$agent_id);
					// 	}else{
					// 		$userdiscount = getUserDiscount($uid, 'luozuan_discount',$agent_id);
					// 	}
					// }else{
					//  	$userdiscount = 0;					
					// }

					$userdiscount = 0;		
					
					////////不能更改的字段名称。start
					$price = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight']; //每个单价					
					$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] = formatRound($price*$sale_jiadian/100, 2);//销售单价  god， price和xiaoshou_price是一样的，被前台页面使用过的绕晕死掉了
					
					// if( C('agent_id') != $dataList[$key]['agent_id'] ){
					// 	$dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
					// }else{
					// 	$dollar_huilv = $dollar_huilv;
					// }
					// 
					
					$zuanming_price                 = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight'];
					$dataList[$key]['caigou_price'] = formatRound($zuanming_price * $caigou_jiadian/100 , 2);//采购单价
	        		$dataList[$key]['cur_price']    = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] * $dollar_huilv* $sale_jiadian/100 , 2) ;//每卡单价 
	        		///////不能更改的end，

	        		/////////////////////////////////////////////////////////购物车中的选项
	        		$dataList[$key]['userdiscount']                = 0;//$userdiscount; 
	                $dataList[$key]['luozuan_advantage']           = 0;//$luozuan_advantage;
					
	                
	                $dataList[$key]['price_display_type']  	   = 0;//getAgentConfigValue('price_display_type', $agent_id);
	                $dataList[$key]['dia_discount_all']        = $sale_jiadian;	                          		
				break;

				case 2:
					//判断是一级，还是钻明的货
					if($dataList[$key]['agent_id'] == 0 ){
						$sale_jiadian = getAgentDiscount('sanhuo_advantage', 1, C('agent_id')); 
					}else{
						$sale_jiadian = getAgentDiscount('sanhuo_advantage', 0, C('agent_id')); 
					}

					$dataList[$key]['caigou_price']   = formatRound($val['goods_price'], 2);
					$dataList[$key]['xiaoshou_price'] = formatRound($val['goods_price'] * $sale_jiadian/100, 2);
					if (empty ( $val['nickname'] ))$dataList[$key]['nickname'] = '系统'; // 录入人

					////////////////////////////////////////////////////////////////购物车中的信息
					$dataList[$key]['userdiscount']           = 0;//getUserDiscount($uid, 'sanhuo_discount',$agent_id); //会员折扣

					
	                $dataList[$key]['agent_sanhuo_advantage'] = 0;//不需要  分销加点不计算？？
	                $dataList[$key]['sanhuo_advantage']       = 0;//不需要  零售加点为什么要为0	                
	                //sale_jiaidan 已经加了用户折扣和零售加点，这里没有加上分销商加点。
	              


	                $dataList[$key]['goods_price'] 			  = formatRound($val['goods_price'] * ($sale_jiadian)/100, 2);
                    $dataList[$key]['goods_price2']           = formatRound($val['goods_price2'] * ($sale_jiadian)/100, 2);
				break;

				case 3://成品和定制是一样的
				case 4:
					$consignment_advantage = 0;//成品/订制的零售加点 

					$xiaoshou_jiadian     = $consignment_advantage; //+ $caigou_jiadian;
					// if( $val['agent_id']  == $agent_id ){// 自己的货不用加点
					// 	$xiaoshou_jiadian = 0;
					// }
					// if($uid>0){	//有会员id的时候，定制和成品的会员加减点不一样
					// 	$consignmentDiscount = getUserDiscount($uid, 'consignment_discount',$agent_id);//定制
					// 	$goodsDiscount       = getUserDiscount($uid, 'goods_discount',$agent_id);//成品
					// }else{
						$consignmentDiscount = 0;
						$goodsDiscount       = 0;
					// }
					//活动产品折扣，如果activity_status存在值（0或1），则为活动商品  
					// if($val['activity_status'] == '1' || $val['activity_status'] == '0' ){   //活动折扣
					// 	$activity_advantage	= getAgentConfigValue('activity_advantage',$agent_id);
					// }else{
						$activity_advantage = 0;
					// }
					$dataList[$key]['userdiscount'] = 0; //用户折扣是0

					//判断是一级，还是钻明的货
					// dump($dataList[$key]['agent_id']);
					if($dataList[$key]['agent_id'] == 0 ){
						$xiaoshou_jiadian = getAgentDiscount('consignment_advantage', 1, C('agent_id')); 
					}else{
						$xiaoshou_jiadian = getAgentDiscount('consignment_advantage', 0, C('agent_id')); 
					}
					// echo $xiaoshou_jiadian.';';


				 	if($val['goods_type'] == 4) {
						if(!empty($dataList[$key]['goods_attrs'])){ //不为空时是订单中的
							$dataList[$key]['goods_price'] = getConsignmentPrice($dataList[$key]['goods_attrs']['goods_id'], $dataList[$key]['goods_attrs']['associateInfo']['material_id'],  $dataList[$key]['goods_attrs']['luozuanInfo']['gal_id'], $dataList[$key]['goods_attrs']['deputystone']['gad_id']) ;
						}
						$dataList[$key]['goods_price'] = $dataList[$key]['goods_price'];
						$dataList[$key]['agent_consignment_advantage'] = $xiaoshou_jiadian;
				 	}else{
				 		//成品订制中没有$caigou_jiadian 这里改要讲分销加点算上。
	                	$dataList[$key]['agent_consignment_advantage'] = 0;
				 	}

					//caigou_price  和goods_price不能变换位置，否则caigou_price会变的很大
				 	//$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['goods_price']*100/$xiaoshou_jiadian,2);
					//$dataList[$key]['goods_price']  = formatRound($dataList[$key]['goods_price'],2);
					
					
					//2016-11-7 追加活动时间区间功能，如果在活动时间则显示活动价格，否则显示普通售卖价格
					//$c_activity_time        = getAgentConfigValue('activity_time',$agent_id);
					//$activity_time			= explode(',',$c_activity_time);
					//$nowHour				= date('H',time());
					//if($nowHour >= intval($activity_time[0]) && $nowHour < intval($activity_time[1])){
					//	$dataList[$key]['activity_price']=formatRound($dataList[$key]['goods_price']*(100 + $activity_advantage)/100,2);
					//}else{
					//	$dataList[$key]['activity_price']=$dataList[$key]['goods_price'];
					//}
					
					$dataList[$key]['marketPrice']  = formatRound($dataList[$key]['goods_price']*1.5, 2);					
	                $dataList[$key]['consignment_advantage']       = 0; //零售加点
				break;
				case 16: //szzmzb
					$parent_id = get_parent_id();
					$trader_consignment_advantage = M('trader')->where(' t_agent_id = '.C('agent_id'))->getField('consignment_advantage');
					if($parent_id > 0){ //如果有上级 添加上级加点
						$trader_consignment_advantage += M('trader')->where(' t_agent_id = '.$parent_id)->getField('consignment_advantage');
					}
					$dataList[$key]['goods_price'] = $val['goods_price']*(100+$trader_consignment_advantage)/100;
				break;
			}
		}

		if($shuliang == 'single'){
			return $dataList[0];
		}else{
			return $dataList;
		}
		
	}else{
		return false;
	}
}

//王松林的加入采购单
function getCaigouGoodsListPrice_songlin_bak($data, $uid=0, $type='luozuan',  $shuliang = 'all'){
	if($shuliang == 'single'){
		$dataList[0] = $data;
	}else{
		$dataList    = $data;
	}
	$agent_id = get_parent_id();
	        
	if($dataList){					
		foreach($dataList as $key=>$val){			
			switch($goods_type){
				case 1:
					$dataList[$key]['shape_name'] = M('goods_luozuan_shape')->where(array('shape'=>$val['shape']))->getField('shape_name');
				
					$caigou_jiadian = $dataList[$key]['dia_discount'];
					
					////////不能更改的字段名称。
					$dollar_huilv = M("config_value")->where('config_key=\'dollar_huilv\' and agent_id = '.$agent_id);
					$price = $dataList[$key]['dia_global_price'] * $dollar_huilv * $val['weight']; //每个单价					
					$dataList[$key]['price'] = $dataList[$key]['xiaoshou_price'] = formatRound($price*$sale_jiadian/100, 2);//销售单价  god， price和xiaoshou_price是一样的，被前台页面使用过的绕晕死掉了
	        		$dataList[$key]['caigou_price']   = formatRound($price * $caigou_jiadian/100 , 2);//采购单价
	        		$dataList[$key]['cur_price'] = $dataList[$key]['meikadanjia']     = formatRound($dataList[$key]['dia_global_price'] *dollar_huilv* $sale_jiadian/100 , 2) ;//每卡单价 
	        		///////不能更改的end，

	        		/////////////////////////////////////////////////////////购物车中的选项
	        		$dataList[$key]['userdiscount']            = 0; 
	                $dataList[$key]['luozuan_advantage']       = 0;
					if($val['luozuan_type'] == 1){
						$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('caizuan_advantage');
					}else{
						$dataList[$key]['agent_luozuan_advantage'] = getAgentDiscount('luozuan_advantage');
					}
	                
	                $dataList[$key]['price_display_type']  	   = 0;
	                $dataList[$key]['dia_discount_all']        = $sale_jiadian;	                          		
				break;

				case 2:									
					$dataList[$key]['caigou_price']   = formatRound($val['goods_price'], 2);
					$dataList[$key]['xiaoshou_price'] = formatRound($val['goods_price'] * (100 + $sale_jiadian)/100, 2);
					if (empty ( $val['nickname'] ))$dataList[$key]['nickname'] = '系统'; // 录入人

					////////////////////////////////////////////////////////////////购物车中的信息
					$dataList[$key]['userdiscount']           = 0; 
	                $dataList[$key]['agent_sanhuo_advantage'] = getAgentDiscount('sanhuo_advantage');//不需要
	                $dataList[$key]['sanhuo_advantage']       = 0;//不需要
	                $dataList[$key]['goods_price'] 			  = formatRound($val['goods_price'] * (100 + $sale_jiadian)/100, 2);
                    $dataList[$key]['goods_price2']           = formatRound($val['goods_price2'] * (100 + $sale_jiadian)/100, 2);
				break;

				case 3://成品和定制是一样的
				case 4:
					
					$xiaoshou_jiadian        = 0; //+ $caigou_jiadian;					
					$consignmentDiscount = 0;
					$goodsDiscount       = 0;
					
				 	if($val['goods_type'] == 3){
				 		$xiaoshou_jiadian = $xiaoshou_jiadian + $goodsDiscount ;
				 	}elseif($val['goods_type'] == 4) {

				 		$xiaoshou_jiadian = $xiaoshou_jiadian + $consignmentDiscount;	
						if(!empty($dataList[$key]['goods_attrs'])){ //不为空时是订单中的
							$dataList[$key]['goods_price'] = getConsignmentPrice($dataList[$key]['goods_attrs']['goods_id'], $dataList[$key]['goods_attrs']['associateInfo']['material_id'],  $dataList[$key]['goods_attrs']['luozuanInfo']['gal_id'], $dataList[$key]['goods_attrs']['deputystone']['gad_id']);
						}
						
				 	}
					
					//caigou_price  和goods_price不能变换位置，否则caigou_price会变的很大
				 	$dataList[$key]['caigou_price'] = formatRound($dataList[$key]['goods_price'],2);
					$dataList[$key]['goods_price']  = formatRound($dataList[$key]['goods_price']*(100 + $xiaoshou_jiadian)/100,2);
					$dataList[$key]['marketPrice']  = formatRound($dataList[$key]['goods_price']*1.5, 2);
					$dataList[$key]['userdiscount'] = 0; 
	                $dataList[$key]['agent_consignment_advantage'] = $caigou_jiadian;		
	                $dataList[$key]['consignment_advantage']       = getAgentDiscount('consignment_advantage');
				break;
			}
		}

		if($shuliang == 'single'){
			return $dataList[0];
		}else{
			return $dataList;
		}
		
	}else{
		return false;
	}
}

function getUserConfigValue($uid=0,$key='',$agent_id=0){
	if($key == ''){
		return '';
	}
	if($agent_id == ''){
		$agent_id = C('agent_id');
	}
	return M("user")->where("uid='".$uid."' and agent_id = ".$agent_id)->getField($key);
}

function getAgentConfigValue($key='',$agent_id=0){	
	if($agent_id == ''){
		$agent_id = C('agent_id');
	}
	return M('config_value')->where("agent_id =".$agent_id." and config_key='".$key."'")->getField('config_value');
}
//获取分销商模块开启状态
function get_zm_agent_setting($name=null) {
	$param = array(
		'zgoods_show'=>0,
		'kefu_show'=>0
	);
	$agent_id = C('agent_id');
	$info = M('agent_setting')->where('agent_id="'.$agent_id.'"')->find();
 
	if(!empty($info)){
		$param['zgoods_show'] = $info['zgoods_show'];
		$param['kefu_show'] = $info['kefu_show'];
		$param['lease_components'] = $info['lease_components'];
	}
	if($name!==null){
		if(isset($param[$name])){
			return $param[$name];
		}else{
			return false;
		}

	}

	return $param;
}

function _httpsRequest($param){
	$url = $param['url'];
	$data = $param['data'] ? $param['data'] : '';
	$not_json = $param['not_json'] ? 1 : 0;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (!empty($data)){
		$data = http_build_query($data);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	$output = curl_exec($curl);
	curl_close($curl);
	if($not_json==1){
		return $output;
	}

	return json_decode($output,true);

}

function p($a){
	echo '<pre>';
	print_r($a);
	exit;
}


function pleaseLogin($param=array()){
	$url = $param['url']  ? $param['url'] : U('Home/Index/index');
	$uid = $param['uid'] ? intval($param['uid']) : intval($_SESSION['web']['uid']);
	if(!$uid){
		echo "<script>alert('请先登录');window.location.href='".$url."';</script>";exit;
	}
}

//获取当前网址
function getFullUrl(){
	# 解决通用问题
	/*$requestUri = '';
	if (isset($_SERVER['REQUEST_URI'])) {
		$requestUri = $_SERVER['REQUEST_URI'];
	} else {
		if (isset($_SERVER['argv'])) {
			$requestUri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
		} else if(isset($_SERVER['QUERY_STRING'])) {
			$requestUri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
		}
	}
	$scheme = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = strstr(strtolower($_SERVER["SERVER_PROTOCOL"]), "/",true) . $scheme;
	//端口还是蛮重要的，毕竟需要兼容特殊的场景
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	# 获取的完整url
	$_fullUrl = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $requestUri;
	return $_fullUrl;*/
	$requestUri = '';
	if (isset($_SERVER['REQUEST_URI'])) {
		$requestUri = $_SERVER['REQUEST_URI'];
	} else {
		if (isset($_SERVER['argv'])) {
			$requestUri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
		} else if(isset($_SERVER['QUERY_STRING'])) {
			$requestUri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
		}
	}
	//$scheme = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$scheme = "";
	$protocol = strstr(strtolower($_SERVER["SERVER_PROTOCOL"]), "/",true) . $scheme;
	//端口还是蛮重要的，毕竟需要兼容特殊的场景
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	# 获取的完整url
	$_fullUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $requestUri;
	return $_fullUrl;
}

function check_dingzhi_gmac($goods_id,$m,$a,$c){
    if($goods_id>0){
        $domain_name = 'Szzmzb';
        $model = '\Common\Model\Dingzhi\\'.$domain_name.'\Goods';
        $dingzhi_goods_id = (new $model())->get_goods_id($goods_id);
        if($dingzhi_goods_id){
            $model = '\\'.$m.'\\Controller\Dingzhi\\'.$domain_name.'\\'.$a;
            (new $model())->$c($dingzhi_goods_id);
            exit;
        }
    }
    
}

/*多维数组排序
 $multi_array:多维数组名称
 $sort_key:二维数组的键名
 $sort:排序常量	SORT_ASC || SORT_DESC
 */
function multi_array_sort(&$multi_array,$sort_key,$sort=SORT_DESC){
    if(is_array($multi_array)){
        foreach ($multi_array as $row_array){
            if(is_array($row_array)){
                //把要排序的字段放入一个数组中，
                $key_array[] = $row_array[$sort_key];
            }else{
                return false;
            }
        }
    }else{
        return false;
    }
    //对多个数组或多维数组进行排序
    array_multisort($key_array,$sort,$multi_array);
    return $multi_array;
}
//往商品表添加一条基础数据记录
function addBaseGoods($default_data){
	//记得传 agent_id 跟 goods_type
	$GM = M('goods');
	$data = array(
		'goods_name'=>'商品名称',
		'parent_type'=>1,
		'parent_id'=>0,
		'agent_id'=>0,
		'goods_type'=>0,
	);
	if(!empty($default_data)){
		foreach($default_data as $key=>$value){
			$data[$key] = $value;
		}
	}
	$goods_id = $GM->add($data);
	if(!$goods_id){
		$goods_id = 0;
	}
	return $goods_id;
}

// 板房hand
function dingzhi_szzmzb_jewelry_name($v){
	if($v=='男戒'||$v=='女戒'||$v=='情侣对戒'){
		return '手寸';
	}
	if($v=='手链'){
		return '链长(厘米)';
	}
	if($v=='手镯'){
		return '圈口内径(厘米)';
	}
}
