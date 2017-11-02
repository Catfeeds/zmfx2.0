<?php
namespace Mobile\Controller;
class SanhuoController extends MobileController{

	/**
	 * 裸钻列表数据获取
	 */
	public function getlist(){
		$uid	= I('uid', 0, 'intval');			//用户ID
		$start	= I('start', 0, 'intval');			//起始值
		$per	= I('per', 20, 'intval');			//取条数
	
		$start	= $start<=0?1:$start;
		$per	= $per<=0? 20 :$per;

		$goodsTypeId = I('GoodsType');
		$goods_sn = I('GoodsSn');
		$where = "goods_weight > 0 and zm_goods_sanhuo.agent_id = " . C('agent_id');
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
			$data['goods_sn'] = M('goods_sanhuo')->field('goods_sn,goods_id')->where("tid='".$goodsTypeId."'")->order('goods_sn ASC')->select();
		}
		if($goods_sn != -1 && !empty($goods_sn)){
			$where .= ' AND zm_goods_sanhuo.goods_id="'.$goods_sn.'"';
		}
		if(!empty($Color) && $Color != "''"){
			$where .= " AND color IN (".$Color.")";
		}

		$n = 10;
		$count =  M('goods_sanhuo')->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where($where)->count();


		$data = M('goods_sanhuo')->join('LEFT JOIN zm_goods_sanhuo_type on zm_goods_sanhuo.tid = zm_goods_sanhuo_type.tid')->where($where)->limit($start * $per,$per)->order('zm_goods_sanhuo.tid ASC')->select();
		if($data){
			foreach($$data as $key=>$val){
				$$data[$key]['goods_price'] = formatRound($val['goods_price']*(1+C("sanhuo_advantage")/100),2);
			}
		}
		$data['count']     = $count;

		$this->r(100, '', $data);
	}

}