<?php
namespace Mobile\Controller;
class IndexController extends MobileController{
      
    /**
     * 分销商首页
     */
    public function index($n=20,$p=1){
        $this->domain = top_domain($_SERVER['HTTP_HOST']);
		/* 首页的轮播广告,商品活动页广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 7;
		$CarouselAdList		= $TA->where($where)->order('ads_id ASC')->select();
		
        //获取首页产品
        $goodsList = getGoodsList($this->domain,$n,$p);
		
        $this->goodsList = $goodsList['list'];
		$this->ads_id = 7;
		$this->CarouselAdList 	= 	$CarouselAdList;
		
        if($p>1){$this->ajaxReturn($this->fetch('Goods/goodsList'));}
        else $this->display();
	}
	
}
