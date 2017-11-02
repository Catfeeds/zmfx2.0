<?php
/**
 * 前端文章显示类
 */
namespace Home\Controller;
class NewArticleController extends NewHomeController{
	
	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
	}
	
	public function _before_index(){
    	$this->seo_title = "行业信息";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active = "News";
	}
    
    public function _before_detail(){
		$this->seo_title = "文章详情-钻明官网";
    	$this->seo_keyword = "新闻，文章，文章详情";
    	$this->seo_content = "新闻，文章，文章详情";
    }
    
    public function _before_articleList(){
    	$this->seo_title = "行业信息";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active = "ArticleList";
    	/* 获取百科分类下所有的分类*/
    	$cid = M("article_cat")->where("is_show = 1 AND agent_id=".C("agent_id")." AND catname = '行业信息'")->getField('cid');
    	if($cid){
    		$categorys = $this->getCategory($cid);
    		$this->categorys = $categorys;
    	} 
    }

	/**
	 * auth	：fangkai
	 * content：文章首页
	 * time	：2016-7-6
	 * updatetime:2016-10-11
	**/
	public function index($p='1',$n='20'){
		$cid			= I('get.cat_id','','intval');
			
     	$Article		= new \Common\Model\Article();
		$ArticleBase	= new \Common\Model\ArticleBase();
		
		$count			= $Article->getCatgoryArticleCount($cid);
		$Page			= new \Think\Page($count,20);
		$limit			= $Page->firstRow.','.$Page->listRows;
		$articleList	= $Article->getCatgoryArticleList($cid,$limit);
		$categorys		= $Article->getCategoryList();					//获取B2B文章详情页右侧分类
		$hotArticle		= $Article->getClickIndexArticle();				//获取点击率最高的九篇文章
		
		$categoryInfo	= $ArticleBase->baseGetCategoryInfo('article_cat',$cid);
		
		$this->categoryInfo	= $categoryInfo;
		$this->articleList	= $articleList;
		$this->categorys	= $categorys;
		$this->hotArticle	= $hotArticle;
		$this->cid			= $cid;
    	$this->page     	= $Page->show();
    	$this->display();
	}
    
	/**
	 * auth	：fangkai
	 * content：文章详情
	 * time	：2016-7-7
	**/
	public function detail(){
		$aid			= I('get.aid','','intval');
		$Article		= new \Common\Model\Article();
		
		$info	= $Article->getArticleInfo($aid);
		if($info == false){
			$this->error($Article->error);
		}
		$hotArticle	= $Article->getClickIndexArticle();					//获取点击率最高的九篇文章
		$Article->setArticleClick($aid);								//进入详情页面，文章点击量+1
		$masterCat	= $Article->getParentCat($info['cat_id']);			//获取最上级分类信息
		
     	$this->aid = $aid;                      
     	$this->info			= $info;
     	$this->hotArticle	= $hotArticle;
		$this->masterCat	= $masterCat;
     	$this->seo_title	= $info['title'];
		if($info['title']){
			C("seo_title",$info['title'].' - '.C("seo_title"));
		}
    	$this->display();

	}	
	
    /**
	 * auth	：fangkai
	 * content：帮助中心
	 * time	：2016-7-7
	 * updatetime:2016-10-7
	**/
	public function help(){
		$aid	= I('get.aid','','intval');
		$Help	= new \Common\Model\Help();
		
		$count		= $Help->getArticleCount('');
		$Page		= new \Think\Page($count,20);
		$limit		= $Page->firstRow.','.$Page->listRows;
		
		$helpArticleList= $Help->getArticleListTwo('',$limit);			//获取帮助文章分类
		if($aid){
			$helpArticleInfo = $Help->getArticleInfo($aid);				//获取帮助文章详细信息
			if($helpArticleInfo == false){
				$this->error($Help->error);
			}
		}else{
			$helpArticleInfo = $helpArticleList[0]['articleList'][0];	//如果aid不存在则取第一个分类的第一篇文章
			$aid	= $helpArticleList[0]['articleList'][0]['aid'];
		}
		
		$this->helpArticleInfo	= $helpArticleInfo;
		$this->helpArticleList	= $helpArticleList;
		$this->aid	= $aid;
		$this->display();
	}

}