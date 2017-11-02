<?php
/**
 * auth	：fangkai
 * content：散货控制器
			散货列表，散货相关操作
 * time	：2016-5-24
**/
namespace App\Controller;
class SanHuoController extends AppController{
	/**
	 * auth	：fangkai
	 * content：散货列表
	 * time	：2016-5-24
	**/
	public function sanHuo(){
		$GL = M('goods_sanhuo');
		//类型
		$this->goodsType = M('goods_sanhuo_type')->order('tid ASC')->select();
		//分数
		$this->sanhuoWeights = M("goods_sanhuo_weights")->where("pid=0")->order("sort ASC")->select();
		//颜色
		$this->sanhuoColor = M("goods_sanhuo_color")->order("sort ASC")->select();
		//净度
		$this->sanhuoClarity = M("goods_sanhuo_clarity")->order("sort ASC")->select();
		//切工
		$this->sanhuoCut = M("goods_sanhuo_cut")->order("sort ASC")->select();
		//所属地
		$locationList = array("深圳","上海","北京","香港","国外");
		$this->locationList = $locationList;
		
		$this->display();
	}
	
 
	//散货Ajax获取列表
	public function getGoodsByParam(){
		$goodsTypeId = I('GoodsType');
		$goods_sn = I('GoodsSn');
		$bargain = I('bargain');
		$where['zm_goods_sanhuo.goods_weight'] = array('gt',0);
		$Location = I('Location');
 		$Color_data = I('Color');
		if($Color_data)	$Color=explode('|',$Color_data);
		$Weight = I('Weight');
		$thisPage = I("page",1,'intval')<=0?1:I("page",1,'intval');
		$Cut=I('Cut');
		if($Color){
			$where['zm_goods_sanhuo.color'] = array('IN',$Color);
		}
		$Clarity = I('Clarity');
		if($Clarity){
			$Clarity = str_replace('1','VVS',$Clarity);
			$Clarity = str_replace('2','VVS-VS',$Clarity);
			$Clarity = str_replace('3','VS',$Clarity);
			$Clarity = str_replace('4','VS2-SI1',$Clarity);
			$Clarity = str_replace('5','SI1-SI2',$Clarity);
			$Clarity = str_replace('6','SI2-SI3',$Clarity);
			$Clarity = str_replace('7','I1-I2',$Clarity);
			$Clarity = str_replace('8','&lt;I2',$Clarity);
		}
		if(!empty($Clarity) && $Clarity != "''"){
			$where['zm_goods_sanhuo.clarity'] = array('IN',$Clarity);
		}
		if(!empty($Cut) && $Cut != "''"){ 
			$where['zm_goods_sanhuo.cut'] = array('IN',$Cut);
		}
		if(!empty($Weight) && $Weight != "''"){
			 $Weight = str_replace(' 300', '+300', $Weight);
			 $where['zm_goods_sanhuo.weights'] = array('IN',$Weight);
		}
		if(!empty($Location)){
			$where['zm_goods_sanhuo.location'] = array('in',$Location);
		}
		if($bargain){
			if((strpos($bargain,'2')==true) || $bargain=='2'){  
				$where['goods_price2'] = array('gt',0);
			}
		}
		if($goodsTypeId != -1 && !empty($goodsTypeId)){
			$where['zm_goods_sanhuo.tid'] = $goodsTypeId;
			$sanhuoGoods['goods_sn'] = M('goods_sanhuo')->field('goods_sn,goods_id')->where("tid='".$goodsTypeId."'")->order('goods_sn ASC')->select();
		}
		if(!empty($Color) && $Color != "''"){
			$where['color'] = array('IN',$Color);
		}
		if(!empty($goods_sn) && $goods_sn != -1){
			$where['zm_goods_sanhuo.goods_id'] = array('IN',$goods_sn);
		}
		$GoodsSanhuo = D('Common/GoodsSanhuo');
		$n     = 10;
		/*
		//2016-3-23修改；authon：fangkai；备注：若证书货号存在，则进行单个查询，若不存在则进行带有分页的批量查询
		*/
		if(!empty($goods_sn) && $goods_sn != -1){
			$p=1; 
		}else{
			$p=$thisPage;
		}
		$sanhuoGoods        = $GoodsSanhuo->getList($where,'zm_goods_sanhuo.goods_id asc',$p,$n);
		if($sanhuoGoods){	
			$sanhuoGoods['list'] = getGoodsListPrice($sanhuoGoods['list'], $_SESSION['app']['uid'], 'sanhuo');		
		}
		$sanhuoGoods['goodsSn']   = $goods_sn;
		$this->ajaxReturn($sanhuoGoods);
	}

	
	
	
	
}