<?php
namespace App\Controller;
class NewsController extends AppController{
      
    /**
     * 新闻首页
	 * zhy
	 * 2016年5月23日 16:37:55
	 * updatetime	2016-10-13
     */
    public function index(){
		$Article		= new \Common\Model\Article();
		if(IS_AJAX){
			$cid			= I('cat_id','','intval');
			$page			= I('post.page',1,'intval');
			$n				= '20';							
			$firstrow		= ($page-1)*$n;
			$limit			= " $firstrow,$n";
			$data			= $Article->getCatgoryArticleList($cid,$limit);		//获取文章列表
			$this->ajaxReturn($data);
		}else{
			$cid			= I('cat_id','','intval');
			$articleList	= $Article->getCatgoryArticleList($cid,20);			//获取文章列表
			$categorys		= $Article->getCategoryList();						//获取分类
			if(empty(cid)){
				$cid		= $categorys[0]['cid'];								//默认为第一个分类
			}
			
			$this->cid			= $cid;											
			$this->countArticle	= count($articleList);
			$this->articleList	= $articleList;
			$this->categorys	= $categorys;
			$this->display();
		}
	}
	
 
    /**
     * 新闻详情
	 * zhy
	 * 2016年5月25日 16:11:15
	 * updatetime	2016-10-14
     */
	public function detail(){
		$aid			= I('get.id','','intval');
		$Article		= new \Common\Model\Article();
		
		$info	= $Article->getArticleInfo($aid);
		if($info == false){
			$this->error($Article->error);
		}
		$masterCat	= $Article->getParentCat($info['cat_id']);			//获取最上级分类信息
		
		$Article->setArticleClick($aid);								//进入详情页面，文章点击量+1
	    
		$this->masterCat	= $masterCat;
     	$this->info			= $info;
    	$this->display();
	}
	
 
 
	
}
