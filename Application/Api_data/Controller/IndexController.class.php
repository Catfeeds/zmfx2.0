<?php
namespace Api_data\Controller;
class IndexController extends Api_dataController{
      
    /**
	 * auth	：fangkai
	 * content：构造函数
	 * time	：2016-11-8
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid");
	}
	
	/**
	 * auth	：fangkai
	 * content：APP首页广告接口
	 * time	：2016-11-3
	**/
    public function indexBanner(){
		//首页轮播
		$TA	= M ('template_ad');
		$where['agent_id']	= C('agent_id');
		$where['ads_id']	= 12;
		$where['status']	= 1;
		$order	= 'sort ASC';
		$field	= 'ad_id,title,images_path,url,url_type';
		$bannerList	= $TA->field($field)->where($where)->order($order)->select();
		foreach($bannerList as $key=>$val){
			//返回完整的图片路径
			if($val['images_path']){
				$bannerList[$key]['images_path']	= 'http://'.C('agent')['domain'].'/Public'.$val['images_path'];
			}else{
				$bannerList[$key]['images_path']	= '';
			}
			
			//根据链接类型拼接不同的跳转链接
			$bannerList[$key]['goods_type']	= '';
			switch($val['url_type']){
				case 1:
					if(intval($val['url'])){
						$goodsInfo	= M('goods')->field('goods_type')->where(array('goods_id'=>$val['url']))->find();
						$link= 'http://api-view.btbzm.com/Goods/goodsInfo?agent_id='.$where['agent_id'].'&type='.$goodsInfo['goods_type'].'&uid='.$this->uid.'&goods_id='.$val['url'];
						$bannerList[$key]['url']	= $link;
						$bannerList[$key]['goods_type']	= $goodsInfo['goods_type'];
					}else{
						$bannerList[$key]['url']	= '';
					}
					$bannerList[$key]['url_id']	= $val['url'];
					
					break;
				case 2:
					if(intval($val['url'])){
						$link= 'http://api-view.btbzm.com/Article/getArticleInfo/?agent_id='.$where['agent_id'].'&aid='.$val['url'];
						$bannerList[$key]['url']	= $link;
					}else{
						$bannerList[$key]['url']	= '';
					}
					$bannerList[$key]['url_id']	= $val['url'];
					break;
			}
		}
		$this->echo_data('100','获取成功',$bannerList);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：APP首页菜单栏
	 * time	：2016-11-3
	**/
    public function indexMenu(){
		$app_modswitch 		= M('app_modswitch');
		$where['agent_id']	= C("agent_id");
		$where['status']	= 1;
		$field				= 'name,position_id,status,ico_img_path';
		$menuList 			= $app_modswitch->field($field)->where($where)->select();
		foreach($menuList as $key=>$val){
			if($val['ico_img_path']){
				$menuList[$key]['ico_img_path']	= 'http://'.C('agent')['domain'].'/Public'.$val['ico_img_path'];
				$menuList[$key]['ceshi']	= 'http://api-view.btbzm.com/Public/Uploads/ad/7/2016-11-23/583518fa4a68f123.png';
			}else{
				$menuList[$key]['ico_img_path']	= '';
				$menuList[$key]['ceshi']	= 'http://api-view.btbzm.com/Public/Uploads/ad/7/2016-11-23/583518fa4a68f123.png';
			}
		}

		$this->echo_data('100','获取成功',$menuList);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：APP首页产品系列推荐
	 * time	：2016-11-3
	**/
    public function indexRecommendSeries(){
		$Series 					= M('goods_series');
		$where['agent_id'] 			= C("agent_id");
		$where['app_home_show']		= 1;
		$field						= 'goods_series_id,series_name,images_path';
		$order 						= 'goods_series_id DESC';
		$series_list 				= $Series->field($field)->where($where)->order($order)->limit(2)->select();
		foreach($series_list as $key=>$val){
			if($val['images_path']){
				$series_list[$key]['images_path']	= 'http://'.C('agent')['domain'].'/Public/'.$val['images_path'];
			}else{
				$series_list[$key]['images_path']	= '';
			}
			$series_list[$key]['series_name']       = htmlspecialchars_decode($val['series_name']);
		}
		
		$this->echo_data('100','获取成功',$series_list);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：APP首页产品推荐
	 * time	：2016-11-4
	**/
    public function indexRecommendProduct(){
		$Goods						= D('Common/Goods');
		$HomeShow 					= M('goods_home_show');
		$field						= 'zm_goods_home_show.gid as goods_id,zm_goods_home_show.home_show,zm_goods.goods_name,zm_goods.thumb,zm_goods.goods_type,zm_goods.category_id';
		$Goods     					-> set_where('zm_goods_home_show.agent_id',C("agent_id"));
		$Goods     					-> set_where('zm_goods_home_show.home_show','2');
		$Goods         				-> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $Goods         				-> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
		$Goods     					-> _join('inner join zm_goods_home_show on zm_goods_home_show.gid = zm_goods.goods_id ');
		$Goods     					-> _order('zm_goods_home_show.setting_time', 'DESC');
		$Goods     					-> _limit('1','10');
		$goods_list					= $Goods->getList();
		if($goods_list){
			$uid					= $this->uid;
			$goods_list 			= $Goods -> getProductListAfterAddPoint($goods_list,$uid);
        }
		$data	= array();
		foreach($goods_list as $key=>$val){
			$data[$key]['gid']				= $val['goods_id'];
			$data[$key]['goods_name']		= $val['goods_name'];
			$data[$key]['goods_price']		= $val['goods_price'];
			$image_path						= $Goods->completeUrl($val['goods_id'],$type='goods',$val);
			$data[$key]['images_path']		= $image_path['thumb'];
			$data[$key]['goods_type']		= $val['goods_type'];
			
		}

		$this->echo_data('100','获取成功',$data);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：APP首页准时抢购
	 * time	：2016-11-7
	**/
    public function indexRushBuyProduct(){
		$Activity 				= M('goods_activity');
		$Goods					= D('Common/Goods');
		$agent_id				= $Goods->get_where_agent();
		$field					= 'ga.gid,g.thumb';
		$where['ga.agent_id'] 	= C("agent_id");
		$where['ga.home_show']	= 2;
		$where['g.agent_id'] 	= array('in',$agent_id);
		$where['g.sell_status']    = 1;
		$where['g.shop_agent_id']  = C('agent_id');
		$order 					= 'ga.setting_time DESC';
		$goodsInfo 					= $Activity
									->alias('ga')
									->field($field)
									->join('inner join zm_vm_shop_goods as g on ga.gid = g.goods_id and g.shop_agent_id= "'.C('agent_id').'" and g.sell_status = "1" ')
									->where($where)
									->order($order)
									->find();
		$data = array();
		if($goodsInfo){
			$goods_list[]			= $Goods->get_info($goodsInfo['gid'],0,'shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
			if($goods_list){
				$uid				= $this->uid;
				$goods_list 		= $Goods -> getProductListAfterAddPoint($goods_list,$uid);
			}
			$productInfo           	= $goods_list[0];
			$activity_time			= explode(',',C('activity_time'));
			$nowHour				= date('H',time());
			if($nowHour >= intval($activity_time[0]) && $nowHour < intval($activity_time[1])){
				$data[0]['goods_price']= $productInfo['activity_price'];
			}else{
				$data[0]['goods_price']= $productInfo['goods_price'];
			}
			$data[0]['activity_start_time']		= $activity_time[0].':00';
			$data[0]['activity_end_time']		= $activity_time[1].':00';
			$data[0]['goods_name']	= $productInfo['goods_name'];
			$data[0]['images_path']	= $productInfo['thumb'];
		}
		

		
		$this->echo_data('100','获取成功',$data);
		
	}
	
	/**
	 * auth	：zengmingming
	 * content：APP首页特价抢购商品
	 * time	：2017-08-02
	**/
    public function indexSnapBuyingProduct(){
		/* 特价抢购商品 */
		$Goods 				= D('Common/Goods');
		$agent_id			= $Goods->get_where_agent();
		$where_activity['ga.agent_id']	     = C("agent_id");
		$where_activity['g.agent_id']	     = array('in',$agent_id);
		$where_activity['g.sell_status']	 = '1';
		$where_activity['g.shop_agent_id'] 	 = C('agent_id');
		$where_activity['ga.home_show']      = 2;
		$where_activity['ga.product_status'] = 1;
		$field				= 'g.*,ga.product_status as activity_status';
		$join				= 'left join zm_goods_activity as ga on ga.gid = g.goods_id';
		$sort				= 'ga.setting_time DESC';
		$promotion_goods	= $Goods->getCustomGoodsList($where_activity,$field,$join,$sort,'','');
		//获取产品的售卖价格 
		$promotion_goods_list     = getGoodsListPrice($promotion_goods,$this->uid,'consignment','all');
		$data = array();
		foreach($promotion_goods_list as $key=>$val){
			$activity_time			= explode(',',C('activity_time'));
			$data[$key]["goods_id"] 			= $val["goods_id"]; 
			$data[$key]["goods_price"] 			= $val["goods_price"]; 
			$data[$key]["activity_start_time"] 	= $activity_time[0].':00';
			$data[$key]["activity_end_time"] 	= $activity_time[1].':00';
			$data[$key]["goods_name"] 			= htmlspecialchars_decode($val["goods_name"]); 
			$data[$key]["goods_type"] 			= $val["goods_type"];
			$data[$key]["thumb"] 			= $val["thumb"]; 
		}
		if(empty($data)){
			$this->echo_data('201','获取失败',$data);
		}else{
			$this->echo_data('100','获取成功',$data);
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：珠宝试戴分享
	 * time	：2016-12-8
	**/
	public function tryShare() {
		$SI 		= M('share_image');
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize	=     3145728 ;// 设置附件上传大小
		$upload->exts		=     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath	=     './Public/Uploads/share/'; // 设置附件上传根目录
		$upload->savePath	=     ''; // 设置附件上传（子）目录
		// 上传文件
		$info   =   $upload->uploadOne($_FILES['userimage']);

		if($info){// 上传成功
			$imagePath = $upload->rootPath.$info['savepath'].$info['savename'];
			$save['image_path']	= 'http://'.C('agent')['domain'].$imagePath;
			$save['create_time']= time();
			$action		= $SI->add($save);
			if($action === false){
				$this->echo_data('214','上传失败');
			}else{
				$data[0]['url']		= 'http://api-view.btbzm.com/Article/tryshare/id/'.$action.'/agent_id/'.$this->agent_id.'/';
				$data[0]['title']	= '迁迁君子';
				$data[0]['describe']= '我就是我，不一样的烟火，我看见自己都冒火';
				$this->echo_data('100','上传成功',$data);
			}	
		}else{
			$error	= $upload->getError();
			$this->echo_data('215',$error);
		}	
	}
	
}
