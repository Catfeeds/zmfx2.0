<?php
/**
 * 前端文章显示类
 */
namespace Home\Controller;
class ArticleController extends HomeController{
	
	public function _before_index(){
    	$this->seo_title = "行业信息";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active = "News";
    	
    	/* 获取百科分类下所有的分类
    	$cid = M("article_cat")->where("is_show = 1 AND agent_id=".C("agent_id")." AND catname = '行业信息'")->getField('cid');
    	if($cid){
    		$categorys = $this->getCategory($cid);
    		$this->categorys = $categorys;
    	}  */
	}
    
    public function _before_detail(){   
    	
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
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

	// 文章首页  分销商百科页面
	public function index($p='1',$n='12'){
		
		$Article			= new \Common\Model\Article();
		$categorys			= $Article->getIndexArticle();					//获取B2B文章首页的分类文章列表
		
		$articles_new		= $Article->getNewIndexArticle();				//获取最新的九篇文章
		$articles_hot		= $Article->getClickIndexArticle();				//获取点击率最高的九篇文章
		
		/* 文章广告轮播图 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 6;
		$ArticleAdList		= $TA->where($where)->order('sort ASC')->select();
		$this->ArticleAdList= $ArticleAdList;
		
		$this->articles_new = $articles_new;
		$this->articles_hot = $articles_hot;
		$this->categorys    = $categorys;
		$this->actActive    = "article";
		$this->display();
	}

	// 文章列表/分销商文章分类/分销商搜索文章
    public function articleList($p='1',$n='15'){
    	$cid			= I('get.cat_id','','intval');
			
     	$Article		= new \Common\Model\Article();
		$ArticleBase	= new \Common\Model\ArticleBase();
		
		$count			= $Article->getCategoryArticleCount($cid);
		$Page			= new \Think\Page($count,25);
		$limit			= $Page->firstRow.','.$Page->listRows;
		$articleList	= $Article->getPartArticleList($cid,$limit);
		$categorys		= $Article->getCategoryList();					//获取B2B文章详情页右侧分类
		$hot_article	= $Article->getClickIndexArticle();				//获取点击率最高的九篇文章
		$categoryInfo	= $ArticleBase->baseGetCategoryInfo('article_cat',$cid);
		
		$this->categoryInfo	= $categoryInfo;
		$this->articleList	= $articleList;
		$this->categorys	= $categorys;
		$this->hot_article	= $hot_article;
    	$this->page    		= $Page->show();
    	$this->display();
    }
    
    // 文章详情   分销商文章详情
    public function detail($aid='',$name=''){
		$aid			= I('get.aid','','intval');
		if($name && empty($aid)){
			$aid = M("article")->where("is_show = 1 AND agent_id=".C("agent_id")." AND title = '$name'")->getField('aid');	
		}
     	$Article		= new \Common\Model\Article();
		
		$articleInfo	= $Article->getArticleInfo($aid);
		if($articleInfo == false){
			$this->error($Article->error);
		}
		$categorys		= $Article->getCategoryList();					//获取B2B文章详情页右侧分类
		$hot_article	= $Article->getClickIndexArticle();				//获取点击率最高的九篇文章
		
		$Article->setArticleClick($aid);								//进入详情页面，文章点击量+1
     	
		//$this->hotGoods = $this->getHotGoods();                           
     	$this->aid = $aid;                      
     	$this->article		= $articleInfo;
     	$this->hot_article	= $hot_article;
		$this->categorys	= $categorys;
     	$this->seo_title	= $articleInfo['title'];
		if($articleInfo['title']){
			C("seo_title",$articleInfo['title'].' - '.C("seo_title"));
		}		
    	$this->display();
    }

	/**
	 * auth	：fangkai
	 * content：帮助中心
	 * time	：2016-10-7
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