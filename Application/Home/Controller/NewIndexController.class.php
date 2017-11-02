<?php
namespace Home\Controller;
class NewIndexController extends NewHomeController {
	
	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= $_SESSION['web']['uid'];
	}
	
	/**
	 * auth	：fangkai
	 * content：首页
	 * time	：2016-6-30
	**/
    public function index(){ 
		/* 首页的轮播广告,钻石定制广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 3;
		$CarouselAdList		= $TA->where($where)->order('sort ASC')->select();
		$where['ads_id']	= 13;
		$CustomAdList		= $TA->where($where)->order('sort ASC')->select();
		
		/* 特价抢购商品 */
		$Goods 				= D('Common/Goods');
		$agent_id			= $Goods->get_where_agent();
		$where_activity['ga.agent_id']	     = $this->agent_id;
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
		$promotion_list     = getGoodsListPrice($promotion_goods,$this->uid,'consignment','all');
		//商城热卖
		$GoodsCategory      = D('GoodsCategory');
		$getCategoryAlias   = D('GoodsCategoryConfig');
		
		//查询出所有的二级分类
		$field = 'cg.category_id,cg.category_name,cg.pid,cc.name_alias,cc.img,cc.agent_id,cc.sort_id';
		$category_where['cg.pid']       = 0;
		$category_where['cc.agent_id']  = $this->agent_id;
		$category_where['cc.is_show']   = 1;
		$category_where['cc.home_show'] = 1;
		$join = 'left join zm_goods_category_config as cc on cg.category_id = cc.category_id';
		$sort = 'cc.sort_id ASC';
		$category_list = $GoodsCategory->getCategoryListTwo($field,$join,$category_where,$sort);

		//查询出二级分类下面的三级分类并取出category_id合并为一维数组赋值到原二级分类数组中
		foreach($category_list as $key=>$val){
			$category_where['cg.pid'] = $val['category_id'];
			//查询出所有三级分类
			$category_array = $GoodsCategory->getCategoryListTwo($field,$join,$category_where);
			if($category_array){
				$category_list[$key]['category_array'] = array_column($category_array,'category_id');
			}else{
				$category_list[$key]['category_array'] = array('0'=>$val['category_id']);
			}
		}
		//查询出二级分类里面首页展示的商品,
		$hot_list = $Goods->hotGoodsList($this->agent_id,$category_list,$this->uid);//方法内部已经完成了所有加点			
		
		/* 产品系列列表 */
		$Series = D('GoodsSeries');
		$series_where['agent_id'] = $this->agent_id;
		$series_where['home_show']= 1;
		$sort = 'goods_series_id DESC';
		$series_list = $Series->getSeriesList($series_where,$sort,1,90);

		/* 首页大类新闻 */
		$ArticleCat = D('article_cat');
		$article 	= D('Article');
		$article_where['agent_id'] = $this->agent_id;
		$article_where['is_show']  = 1;
		$article_where['parent_id'] = 0;
		//获取文章分类
		$article_data = $ArticleCat->getCategoryList($article_where,'sort ASC','2');
		
		//查询出当前二级分类并根据二级分类查询出文章列表赋值到一级分类数组中
		foreach($article_data as $key=>$val){
			$article_where['parent_id'] = $val['cid'];
			$category_array = $ArticleCat->getCategoryList($article_where);
			$category_cid = array();
			if($category_array){
				$category_cid = array_column($category_array,'cid');
			}
			$category_cid[] = $val['cid'];
			$articleList_where['agent_id'] = $this->agent_id;
			$articleList_where['is_show']  = 1;
			$articleList_where['cat_id']   = array('in',$category_cid);
			$article_data[$key]['articleList'] = $article->getArticleList($articleList_where,'aid DESC',1,7);
		}

		$this->CarouselAdList = $CarouselAdList;
		$this->CustomAdList   = $CustomAdList;
		$this->promotion_list = $promotion_list;
		$this->hot_list	  	  = $hot_list;
		$this->series_list	  = $series_list;
		$this->article_data	  = $article_data;
		
      	$this->seo_title = "钻明官网首页";
    	$this->seo_keywords = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->navActive = "index";
    	if(I('cookie.autologin')==1){
    		$login = new \Home\Controller\PublicController();
    		$login->loginByCookie();
			exit;
    	}
    	$this->display();
    }



    /**
     * auth	：wangxh
     * content：新的首页模板
     * time	：2017-10-13
     **/
    public function index_b2c_design(){
        /* 首页的轮播广告,钻石定制广告位 */
        $TA = M ('template_ad');
        $where['agent_id'] 	= C('agent_id');
        $where['status']	= 1;
        $where['ads_id']	= 3;
        $CarouselAdList		= $TA->where($where)->order('sort ASC')->select();
        $where['ads_id']	= 13;
        $CustomAdList		= $TA->where($where)->order('sort ASC')->select();
        
        /* 特价抢购商品 */
        $Goods 				= D('Common/Goods');
        $agent_id			= $Goods->get_where_agent();
        $where_activity['ga.agent_id']	     = $this->agent_id;
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
        $promotion_list     = getGoodsListPrice($promotion_goods,$this->uid,'consignment','all');
        //商城热卖
        $GoodsCategory      = D('GoodsCategory');
        $getCategoryAlias   = D('GoodsCategoryConfig');
        
        //查询出所有的二级分类
        $field = 'cg.category_id,cg.category_name,cg.pid,cc.name_alias,cc.img,cc.agent_id,cc.sort_id';
        $category_where['cg.pid']       = 0;
        $category_where['cc.agent_id']  = $this->agent_id;
        $category_where['cc.is_show']   = 1;
        $category_where['cc.home_show'] = 1;
        $join = 'left join zm_goods_category_config as cc on cg.category_id = cc.category_id';
        $sort = 'cc.sort_id ASC';
        $category_list = $GoodsCategory->getCategoryListTwo($field,$join,$category_where,$sort);
        
        //查询出二级分类下面的三级分类并取出category_id合并为一维数组赋值到原二级分类数组中
        foreach($category_list as $key=>$val){
            $category_where['cg.pid'] = $val['category_id'];
            //查询出所有三级分类
            $category_array = $GoodsCategory->getCategoryListTwo($field,$join,$category_where);
            if($category_array){
                $category_list[$key]['category_array'] = array_column($category_array,'category_id');
            }else{
                $category_list[$key]['category_array'] = array('0'=>$val['category_id']);
            }
        }
        //查询出二级分类里面首页展示的商品,
        $hot_list = $Goods->hotGoodsList($this->agent_id,$category_list,$this->uid);//方法内部已经完成了所有加点
        
        $hot_list_goods=[];
        foreach ($hot_list as $valhot){
            if($valhot['goods_list']){
                foreach ($valhot['goods_list'] as $valhotgoods){
                    if($valhotgoods['thumb']&&count($hot_list_goods)<8){
                        array_push($hot_list_goods,$valhotgoods);
                    }
                }                
            }
        }
        
        $this->assign("hot_list_goods",multi_array_sort($hot_list_goods,"goods_id"));   
        
        /* 产品系列列表 */
        $Series = D('GoodsSeries');
        $series_where['agent_id'] = $this->agent_id;
        $series_where['home_show']= 1;
        $sort = 'goods_series_id DESC';
        $series_list = $Series->getSeriesList($series_where,$sort,1,90);
        
        /* 首页大类新闻 */
        $ArticleCat = D('article_cat');
        $article 	= D('Article');
        $article_where['agent_id'] = $this->agent_id;
        $article_where['is_show']  = 1;
        $article_where['parent_id'] = 0;
        //获取文章分类
        $article_data = $ArticleCat->getCategoryList($article_where,'sort ASC','2');  
        //查询出当前二级分类并根据二级分类查询出文章列表赋值到一级分类数组中
        foreach($article_data as $key=>$val){
            $article_where['parent_id'] = $val['cid'];
            $category_array = $ArticleCat->getCategoryList($article_where);
            $category_cid = array();
            if($category_array){
                $category_cid = array_column($category_array,'cid');
            }
            $category_cid[] = $val['cid'];
            $articleList_where['agent_id'] = $this->agent_id;
            $articleList_where['is_show']  = 1;
            $articleList_where['cat_id']   = array('in',$category_cid);
            $article_data[$key]['articleList'] = $article->getArticleList($articleList_where,'aid DESC',1,7);
        }        
        
        $article_list=[];
        foreach ($article_data as $valhot){
            if($valhot['articleList']){
                foreach ($valhot['articleList'] as $valhotgoods){
                    if(count($article_list)<4){
                        array_push($article_list,$valhotgoods);
                    }
                }
            }
        }       
        $this->assign("article_list",multi_array_sort($article_list,"addtime"));
        
        
        $this->CarouselAdList = $CarouselAdList;
        $this->CustomAdList   = $CustomAdList;
        $this->promotion_list = $promotion_list;
        $this->hot_list	  	  = $hot_list;
        
        $this->series_list	  = $series_list;        
        $this->article_data	  = $article_data;
        
        $this->seo_title = "钻明官网首页";
        $this->seo_keywords = "裸钻查询";
        $this->seo_content = "裸钻查询";
        $this->navActive = "index";
        if(I('cookie.autologin')==1){
            $login = new \Home\Controller\PublicController();
            $login->loginByCookie();
            exit;
        }
        $this->display('index');
    }    

}