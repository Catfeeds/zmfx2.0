<?php
namespace Mobile\Controller;
class LuozuanController extends MobileController{

	/**
	 * 裸钻列表数据获取
	 */
	public function getlist(){
		$uid	   = I('uid', 0, 'intval');			    //用户ID
		$start	   = I('start', 0, 'intval');			//起始值
		$per	   = I('per', 20, 'intval');			//取条数
	
		$start	   = $start<=0?1:$start;
		$per	   = $per<=0? 20 :$per;

		$page      = I("page",1);	//当前页码
		$where     = " goods_number > 0 ";
		$n = 10;
		$weight    = explode('-',I("Weight"),2);
		$price     = explode('-',I("Price"),2);
		$minWeight = $weight[0];
		$maxWeight = $weight[1];
		$minPrice  = $price[0];
		$maxPrice  = $price[1];

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
			$where  .= " AND clarity in(".$clarity.")";
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
		$Page = new \Think\Page($count,$per);

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
		$data = D("GoodsLuozuan")->where($where)->limit($start * $per,$per)->order($ordersql)->select();
		foreach($data as $key=>$val){
			$data[$key]['shape'] = getDiamondsShapeName($val['shape']);

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
		$data['dollar_huilv']    = C('dollar_huilv');
		$data['count']    = $count;

		$this->r(100, '', $data);
	}

}