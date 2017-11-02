<?php
namespace App\Controller;
class IndexController extends AppController{
      
	/**
	 * auth	：fangkai
	 * content：APP首页
	 * time	：2016-5-23
	**/
    public function index(){
		
		

		//首页轮播
		$TAS = M ('template_ads');
		$this->tempAdsList = $TAS->where(array('template_id'=>4))->order('ads_id ASC')->select();
		foreach($this->tempAdsList as $key=>$val){
			$TA = M ('template_ad');
			$where['tas.ads_id']	= $val['ads_id'];
			$where['ta.agent_id'] 	= C('agent_id');
			$where['ta.status']		= 1;
			$join = 'zm_template_ads as tas on ta.ads_id = tas.ads_id';
			//查询数据
			$tempAdList[$key]['ad_type'] = $val['ad_type'];
			$tempAdList[$key]['ads_id'] = $val['ads_id'];
			$tempAdList[$key]['adlist'] = $TA
											->alias ('ta')
											->where($where)
											->field ('ta.*')
											->join($join)
											->order('sort ASC')
											->limit('0,5')
											->select();
			foreach($tempAdList[$key]['adlist'] as $k=>$v){				
				//根据链接类型拼接不同的跳转链接
				switch($v['url_type']){
					case 1:
						if(intval($tempAdList[$key]['adlist'][$k]['url'])){
							$goodsInfo	= M('goods')->field('goods_type')->where(array('goods_id'=>$v['url']))->find();
							$url= '/Goods/goodsInfo?agent_id=&type='.$goodsInfo['goods_type'].'&goods_id='.$v['url'];
							$tempAdList[$key]['adlist'][$k]['url']	= $url;
						}else{
							$tempAdList[$key]['adlist'][$k]['url']	= '';
						}
						break;
					case 2:
						if(intval($tempAdList[$key]['adlist'][$k]['url'])){
							$url= '/News/detail/?agent_id='.$where['agent_id'].'&id='.$v['url'];
							$tempAdList[$key]['adlist'][$k]['url']	= $url;
						}else{
							$tempAdList[$key]['adlist'][$k]['url']	= '';
						}
						break;
				}
			}								
			
		};
		
		//首页三块专栏
		$app_modswitch = M('app_modswitch');
		$modList = $app_modswitch->where(array('agent_id'=>C('agent_id')))->order('sort DESC')->select();	
		

		$modList_2=array();
		if(is_array($modList)){
			foreach ( $modList as $key => $val ) {
					foreach ( $val as $k => $v ) {
						if ($k == "url_id") {
							if ($v) {
								$val[$k] = explode ( ",", $v );
							}
						}
					}
					$modList_2['zhy'.$val['position_id']] = $val;
					unset($modList);
			}
			$modList_cust=$modList_2['zhy2'];		//定制
			unset ($modList_2['zhy2']);
		}
		//首页幻灯片下定制产品3154.59
		$goods     = D('Common/Goods');
		$goods         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $goods         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
        $goods-> set_where( 'zm_goods.goods_type','4');
		$goods-> _order('goods_id','DESC');
        $goods-> _limit('1','1');
		$list      = $goods -> getList();
		if($list){
			$goodsArray[0] = $list[0];
			$goodsinfo = $goods->getProductListAfterAddPoint($goodsArray,$_SESSION['app']['uid']); 
			$goods_img=$goods ->get_goods_images_one($goodsinfo[0]['goods_id']);
			$goodsinfo[0]['thumb']=$goods_img['small_path'];								//获取图片，存疑。
			$this->goodsinfo  = $goodsinfo[0];
		}
		if($modList_cust)	{$this->modList_cust	  = $modList_cust;}
		$this->modList	  = $modList_2;
		$this->tempAdList = $tempAdList;
		$this->display();
	}

 
	
}
