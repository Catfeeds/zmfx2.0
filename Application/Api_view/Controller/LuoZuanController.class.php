<?php
/**
 * auth	：fangkai
 * content：裸钻控制器
			裸钻列表，裸钻相关操作
 * time	：2016-5-23
**/
namespace Api_view\Controller;
class LuoZuanController extends Api_viewController{
   	/**
	 * auth	：fangkai
	 * content：裸钻列表
	 * time	：2016-5-23
	**/
	public function luoZuan(){
		$goods_sn = I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);
		$this->display();
	}
	
	/**
	 * auth	：someone
	 * content：Ajax 获取证书货信息
	 * time	：2016-5-23
	**/
	public function getGoodsDiamondDiy(){
		$page  = I("page",1);	//当前页码
    	$where = "goods_number > 0"; 
    	$n = 10;
    	$weight = explode('-',I("Weight"),2);
    	$price = explode('-',I("Price"),2);
    	$minWeight = $weight[0];
    	$maxWeight = $weight[1];
    	$minPrice  = $price[0];
    	$maxPrice  = $price[1];
    	
    	$uid = C('uid');
    	if($uid){
			//如有登录，则获取会员折扣
    		$userdiscount = getUserDiscount($uid);
		}else{
			$userdiscount = 0;	//屏蔽前端折扣
		}	
			
    	$goods_sn = implode(',', explode("\n",trim(urldecode($_REQUEST['goods_sn']))));	//货品编号
		if($goods_sn){
			$where .= " AND (certificate_number IN (".$this->paramStr($goods_sn).") OR goods_name like '%$goods_sn%')";
		}//货品编号
		
		$luozuan_advantage = 0;//C('luozuan_advantage'
    	if($minWeight) $where .= " AND weight >= $minWeight";
    	if($maxWeight) $where .= " AND weight <= $maxWeight";
    	if($minPrice) $where .= " AND (dia_global_price*".C('dollar_huilv')."*(dia_discount+".($luozuan_advantage+$userdiscount).")*weight/100 >= $minPrice )";
    	if($maxPrice) $where .= " AND (dia_global_price*".C('dollar_huilv')."*(dia_discount+".($luozuan_advantage+$userdiscount).")*weight/100 <= $maxPrice )"; 
		
		if(I('Shape')){
    		$shape = $this->paramStr(I("Shape"));  
    		$where .= " AND shape in(".$shape.")";
    	}   
		
    	if(I('Color')){
    		$color = I("Color");  
			$Color_data = explode('|',$color);
			foreach($Color_data as $key=>$v){
				$Color_data[$key] = $v.",$v+,$v-";
			}
			$Color = $this->paramStr(implode(',',$Color_data));
			$where .= " AND color IN (".$Color.")";
		}

        if(I('Intensity') and (I('Luozuan_type')==1)){
            $Intensity = $this->paramStr(I("Intensity")); 
            $where .= " AND Intensity in(".$Intensity.")";
        }
    	
		if(I('Clarity')){			//净度
			$Claritys = "";
			$Clarityarr = explode(",", I('Clarity'));
			foreach($Clarityarr as $k=>$v){
				$Claritys .= $v.",";
				$Claritys .= $v."+,";
			}
			$Claritys = substr($Claritys,0,-1);
			$where .= " AND clarity IN (".$this->paramStr($Claritys).")";
		}
		
		$Location  = trim(urldecode($_REQUEST["Location"]));            //所在地
		if($Location){$where .=" AND `location` In (".$this->paramStr($Location).")";}          //所属地
        $where .= " AND `goods_number` >0";
		
        $encode = mb_detect_encoding($_REQUEST["Stock"], array("ASCII",'UTF-8',"GB2312","GBK",'BIG5')); 
		$Stock = mb_convert_encoding($_REQUEST["Stock"], 'UTF-8', $encode);
    	if($Stock){           
			if($Stock == 'xianhuo'){
				$where .= " AND `quxiang` NOT IN('订货','定货','散货')";
			}
			if($Stock == 'dinghuo'){
				$where .= " AND `quxiang` IN('订货','定货')";
			}
    	}

		//2016-4-7 增加切工other属性值查询；
		$Cut = I('Cut');
		if($Cut){//切工
			if(stristr($Cut,'Other')){
				$array_ct1 = array('I','EX','VG','GD','F');
				$array_ct2 = explode(',',$Cut);
				$array_ct  = array_diff($array_ct1,$array_ct2);
				$result_ct = implode('\',\'',$array_ct);
				if($result_ct){
					$where .= " AND cut NOT IN ('".$result_ct."')";
				}
			}else{
				$where .= " AND cut IN (".$this->paramStr($Cut).")";
			}
		}
	
		

		//2016-4-7 增加对称other属性值查询；
		$Symmetry = I('Symmetry');
		if($Symmetry){		//对称
			if(stristr($Symmetry,'Other')){
				//2016-3-31修复同时选中同一属性的other和其他属性值，只显示other的数据；
				$array_sy1 = array('I','EX','VG','GD','F');
				$array_sy2 = explode(',',$Symmetry);
				$array_sy  = array_diff($array_sy1,$array_sy2);
				$result_sy = implode('\',\'',$array_sy);
				if($result_sy){
					$where .= " AND symmetry NOT IN ('".$result_sy."')";
				}
			}else{
				$where .= " AND symmetry IN (".$this->paramStr($Symmetry).")";
			}
		}
    	
		//2016-4-7 增加抛光other属性值查询；
		$Polish = I('Polish');
    	if($Polish){				//抛光
			if(stristr($Polish,'Other')){
				$array_pl1 = array('I','EX','VG','GD','F');
				$array_pl2 = explode(',',$Polish);
				$array_pl  = array_diff($array_pl1,$array_pl2);
				$result_pl = implode('\',\'',$array_pl);
				if($result_pl){
					$where .= " AND polish NOT IN ('".$result_pl."')";
				}
			}else{
				$where .= " AND polish IN (".$this->paramStr($Polish).")";
			}
		}
		
		//2016-4-7修复同时选中同一属性的other和其他属性值，只显示other的数据；
		$Cert = I('Cert');
		if(!empty($Cert) and !stristr($Cert,'Other')){			//证书
			$where .= " AND certificate_type IN (".$this->paramStr($Cert).")";
		}elseif(stristr($Cert,'Other')){
			
			$array_cet1 = array('GIA','HRD','IGI','NGTC');
			$array_cet2 = explode(',',$Cert);
			$array_cet  = array_diff($array_cet1,$array_cet2);
			$result_cet = implode('\',\'',$array_cet);
			if($result_cet){
				$where .= " AND certificate_type NOT IN ('".$result_cet."')";
			}
		}
		
		
    	$Fluor = urldecode($_REQUEST['Fluor']);		
    	$NaiKa = urldecode($_REQUEST['NaiKa']);		
		//2016-4-7修复同时选中同一属性的other和其他属性值，只显示other的数据；
		if($Fluor){//荧光
			if(stristr($Fluor,'Other')){
				$array_fr1 = array('N','F','M','S','VS');
				$array_fr2 = explode(',',$Fluor);
				$array_fr  = array_diff($array_fr1,$array_fr2);
				$result_fr = implode('\',\'',$array_fr);
				if($result_fr){
					$where .= " AND fluor NOT IN ('".$result_fr."')";
				}
			}else{
				$where .= " AND fluor IN (".$this->paramStr($Fluor).")";
			}
		}
		
		if(stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
			$where .= " AND (milk like '%无奶%' AND coffee like '%无咖%') ";
		}elseif(stristr($NaiKa,'NoMilk') and !stristr($NaiKa,'NoCoffee')){
			$where .= " AND (milk like '%无奶%') ";
		}elseif(!stristr($NaiKa,'NoMilk') and stristr($NaiKa,'NoCoffee')){
			$where .= " AND (coffee like '%无咖%') ";
		}
			$where .= " and  luozuan_type IN ('0')";
    	$count = D("GoodsLuozuan")->where($where)->count();
    	$Page = new \Think\Page($count,$n); 
    	
    	$orderby = I("orderby");
    	$orderway = I("orderway");
    	if (!empty($orderby)){	//排序 
			$orderway = empty($orderway) ? 'ASC' : $orderway;
			$ordersql = "  $orderby $orderway ";
		}else{	//默认排序
			if(!empty($Shape) or !empty($Color) or !empty($Clarity) or !empty($Cut) or !empty($Polish) or !empty($Symmetry) or !empty($Cert) or !empty($Flour) or !empty($Location)){
				$ordersql = " dia_discount ASC ";
			}else{
				$ordersql = "price ASC";
			}
		}
		$dataList['data'] = D("GoodsLuozuan")->where($where)->limit(($page-1)*$n.",".$Page->listRows)->order($ordersql)->select();
	    if( is_array($dataList['data']) && count($dataList['data']) > 0 ){
            foreach ($dataList['data'] as &$value) {
                $value['dia_discount_all'] = $value['dia_discount'];
            }
        }
		$dataList['data']          = getGoodsListPrice($dataList['data'],$uid,'luozuan'); 

		$dataList['dollar_huilv']    = C('dollar_huilv');
		$dataList['price_display_type'] = C('price_display_type');
		$dataList['count']    = $count;
		$dataList['page']     = $Page->show();
		$dataList['pagesize'] = $n;
		$dataList['thisPage'] = $page;		
		$dataList['uType'] = $this->uType;
		$this->ajaxReturn($dataList);
	}
	
	/**
	 * auth	：fangkai
	 * content：匹配钻石分页
	 * time	：2016-6-15
	**/
	public function ajax_cartMatch(){
		if(IS_AJAX){
			$cart_id 	= I('post.cart_id','','intval');
			$agent_id 	= C('agent_id');
			$uid 		= C('uid');
			$sort 		= I('post.sort','ASC','string');
			$check 		= M('cart')->where(array('id'=>$cart_id,'agent_id'=>$agent_id))->find();
			if(!$check){
				$result['status'] = 101;
				$result['msg'] = '购物车没有该商品';
				$this->ajaxReturn($result);
				exit;
			}
			$goods_info = unserialize($check['goods_attr']);
			$shape = M('goods_luozuan_shape')->where(array('shape_id'=>$goods_info['luozuanInfo']['shape_id']))->find();
			
			$this->shape_name = $shape['shape_name'];
			$this->weights = $goods_info['luozuanInfo']['weights_name'];
			
			$minWeight = $this->weights-0.02;
			$maxWeight = $this->weights+0.02;
			$where['goods_number'] = array('GT',0);
			$where['weight'] = array(array('EGT',$minWeight),array('ELT',$maxWeight));
			$where['shape'] = $shape['shape'];
			
			//排序
			switch($sort){
				case 'ASC':
					$order = 'price ASC';
					break;
				case 'DESC':
					$order = 'price DESC';
					break;
				default:
					$order = 'price ASC';
					break;
			}
			$page = I('post.page','1','intval');
			$n = I('post.n','20','intval');
			
			$luozuan_count = D("GoodsLuozuan")->where($where)->count();
		
			$data['data']  = D("GoodsLuozuan")->where($where)->order($order)->limit(($page-1)*$n,$n)->select();
			if($data['data']){
				foreach($data['data'] as $key=>$val){
					$data['data'][$key]['shape'] = getDiamondsShapeName($val['shape']);

					$userdiscount  = getUserDiscount($uid);
					$data['data'][$key]['dia_discount']  = get_luozuan_sale_jiadian($uid, $val['weight'], $data['data'][$key]['dia_discount']);  

					$data['data'][$key]['price']         = formatRound( $data['data'][$key]['dia_global_price'] * C('dollar_huilv') * $data['data'][$key]['dia_discount'] * $val['weight']/100, 2);  //国际报价*汇率*折扣*重量/100
					
					$data['data'][$key]['meika']         = formatRound( $data['data'][$key]['dia_global_price'] * C('dollar_huilv') * $data['data'][$key]['dia_discount']/100, 2);  //国际报价*汇率*折扣/100
					
				}
			}
			$data['cart_id'] = $cart_id;
			$data['luozuan_count'] = $luozuan_count;
			$data['dollar_huilv'] = C('dollar_huilv');
			$data['price_display_type'] = C('price_display_type');
			
			$result['status'] = 100;
			$result['data'] = $data;
			$this->ajaxReturn($result);
		}
	}
	
	public function zhengshu(){
		$this -> display();
	}

	public function zhengshuInfo(){

		$type   = I('type','1','intval');//1.gia 2.IGI 3.HRD 4.NGTC
        $number = I('number','');
		$weight = I('weight','');
		if (!preg_match("/^[a-zA-Z0-9]*$/", $number)){
			$this->error('输入的查询编号应该是英文字母和数字！');
		}
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
	
	
	/**
	*	下载和预览
	*	zhy
	*	2016年11月24日 17:32:20
	*/
	public function	zs_ld(){
		$type   = I('type','1','intval');	//1，l:look:预览 2，2:dowlond:下载
        $number = I('number','');			//证书号
		preg_match('/([0-9]+)/i',$number,$match);
		$number = $match[1];
		if(empty($number)){
				$this->data='证书号有误！';
				$this->display("Public/error");
				exit();
		}
		if($type=='1'){
 			if(!function_exists("getContent")){
				function getContent($url,$httpheader=0){ //CURL远程数据采集
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
			$url = "http://www.gia.edu/cs/Satellite?c=Page&childpagename=GIA%2FPage%2FReportCheck&cid=1355954554547&go=Look+Up&pagename=GIA%2FDispatcher&reportno=$number";
			$doc = getContent($url);
			preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
			$url = "http://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber=$number&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber=".$matchs[1];
			$Report = new \Think\Report(); 
			$Report->report($number,0,1);
			$Report->downloadGIAPDF();
			header("Location: $url");
		}elseif($type=='2'){
			define('cacheDir','./zs/data.cache/');
			if(file_exists(cacheDir.$number.'.pdf.png')){	//若存在PNG图片缓存则输出图片
				header('Content-type: image/jpg');
				echo file_get_contents(cacheDir.$number.'.pdf.png');  
				exit();
			}elseif(file_exists(cacheDir.$number.'.pdf')){	//非IE6用户则优先输出PDF文件
				header('Content-Type: application/pdf');
				echo file_get_contents(cacheDir.$number.'.pdf');
				exit();
			}else{
				$this->data='Sorry,暂时还没有此证书的最新缓存,请先下载该证书，在行预览,若有其他问题,请联系客服,感谢您的来访，再见！';
				$this->display("Public/error");
			}	
		}else{
				$this->data='类型有误！';
				$this->display("Public/error");
		}
		
		
		
		
	} 
	
}