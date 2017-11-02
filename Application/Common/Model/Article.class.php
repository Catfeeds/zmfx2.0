<?php
	/**
	* 文章模型类，包含列表，详情信息
	* auth: fangkai
	* time: 2016/9/30
	**/
namespace Common\Model;

class Article extends ArticleBase {
	
	public $error = '';
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-5
	**/
	public function __construct(){
		parent::__construct();
		$this->agent_id = C('agent_id');
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取分类列表,以二位数组显示
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getCategoryList($is_show=true){
		$model	= 'article_cat';
		if($is_show == true){
			$where['is_show']	= 1; 
		}
		$order	= 'sort ASC';
		$where['agent_id']		= $this->agent_id;
		$where['parent_id']		= 0;
		
		$categoryList = $this->baseGetList($model,$where,$order);								//获取父分类
		if($categoryList){
			foreach($categoryList as $key=>$val){
				$where['parent_id'] = $val['cid'];
				$categoryList[$key]['childCategory'] = $this->baseGetList($model,$where,$order);//获取子分类赋值到父分类
			}
		}
		if($categoryList){
			return $categoryList;
		}else{
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取分类列表，以一维数组显示
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getAllCategory($is_show=true){
		$model	= 'article_cat';
		$parentCategoryList		= array();
		$childrenCatgoryList	= array();
		if($is_show == true){
			$where['is_show']	= 1; 
		}
		$order	= 'sort ASC';
		$where['agent_id']		= $this->agent_id;
		$where['parent_id']		= 0;
		$parentCategoryList 	= $this->baseGetList($model,$where,$order);						//获取父分类
		if($parentCategoryList){
			$pid_array = array_column($parentCategoryList,'cid');
			$where['parent_id']	= array('in',$pid_array);
			$childrenCatgoryList= $this->baseGetList($model,$where,$order);						//获取子分类
		}
		$allCategory	= array_merge($parentCategoryList,$childrenCatgoryList);
		if($allCategory){
			return $allCategory;
		}else{
			return false;
		}
	}
	
	
	/**
	 * auth	：fangkai
	 * @param：获取分类详情
	 * time	：2016-10-5
	**/
	public function getCategoryInfo($cid=''){
		$model = 'article_cat';
		$categoryInfo	= $this->baseGetCategoryInfo($model,$cid);
		
		return $categoryInfo;
	}
	
	/**
	 * auth	：fangkai
	 * @param：更新分类数据
			  $data:要保存的数据
	 * time	：2016-10-5
	**/
	public function categorySave($cid='',$data){
		$model	= 'article_cat';
		$action	= $this->baseCategorySave($model,$cid,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：添加分类数据
			  $data:要保存的数据
	 * time	：2016-10-5
	**/
	public function categoryAdd($data){
		$model = 'article_cat';
		$action	= $this->baseCategoryAdd($model,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除分类数据
	 * time	：2016-10-5
	**/
	public function categoryDelete($cid){
		$model = 'article_cat';
		$where_article['cat_id']	= $cid;
		$checkArticle				= $this->baseGetList('article',$where_article);
		if($checkArticle){
			$this->error 			= '该分类下还存在文章，不能删除';
			return false;
		}
		$where_category['parent_id']= $cid; 
		$checkCategory	= $this->baseGetList($model,$where_category);
		if($checkArticle){
			$this->error 			= '该分类下还存在子分类，不能删除';
			return false;
		}
		
		$action	= $this->baseCategoryDelete($model,$cid,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：文章列表，以全部列表的一维数组的形式展示
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getArticleList($where,$order,$limit,$is_show=true ){
		$model = 'article_cat';
		if($is_show == true){
			$categoryList	= $this->getAllCategory($is_show);
			$cat_array		= array();
			if($categoryList){
				$cat_array	= array_column($categoryList,'cid');
				$where['zm_article.cat_id']	= array('in',$cat_array);
			}
			$where['zm_article.is_show']	= 1;
		}
		$field 	= 'zm_article.aid,zm_article.is_show,zm_article.cat_id,zm_article.title,zm_article.addtime,zm_article.link,zm_article.description,zm_article.thumb,zm_article.top,zm_article_cat.catname';
		$where['zm_article.agent_id']	= $this->agent_id;
		$join	= 'left join zm_article_cat on zm_article.cat_id = zm_article_cat.cid';
		if(empty($order)){
			$order	= 'zm_article.top DESC, zm_article.aid DESC';
		}
		$articleList = $this->baseGetArticleList('article',$field,$where,$join,$order,$limit);
		if($articleList){
			return $articleList;
		}else{
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：文章列表，以分类加列表的二维数组的形式展示
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getArticleListTwo($where,$limit,$is_show=true ){
		$model	= 'article';
		if($is_show == true){
			$where['article.is_show']	= 1;
		}
		$categoryList	= $this->getCategoryList($is_show);
		if($categoryList){
			foreach($categoryList as $key=>$val){
				if($val['childCategory']){
					$array_cid	= array_column($val['childCategory'],'cid');
				}else{
					$array_cid[]= $val['cid'];
				}
				$where['cat_id']	= array('in',$array_cid);
				$where['agent_id']	= $this->agent_id;
				$categoryList[$key]['articleList']	= $this->baseGetList($model,$where,'',$limit);
			}
		}
		
		return $categoryList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取文章总数
	 * time	：2016-10-5
	**/
	public function getArticleCount($where,$is_show=true){
		$model = 'article';
		$where['agent_id']	= $this->agent_id;
		if($is_show == true){
			$where['is_show']	= 1;
			$categoryList	= $categoryList	= $this->getAllCategory($is_show);
			if($categoryList){
				$array_cid	= array_column($categoryList,'cid');
				$where['cat_id']= array('in',$array_cid);
			}
		}
		$count = $this->baseGetArticleCount($model,$where);
		
		return $count;
	}
	/**
	 * auth	：fangkai
	 * @param：通过分类获取文章总数
	 * time	：2016-10-5
	**/
	public function getCategoryArticleCount($cid,$is_show=true){
		$model	= 'article_cat';
		if($is_show == true){
			$where['is_show']		= 1;
			$where_cat['is_show']	= 1;
		}
		$cat_array				= array();
		$where_cat['parent_id']	= $cid;
		$where_cat['agent_id']	= $this->agent_id;
		$order					= 'sort ASC';
		$childrenCatgoryList	= $this->baseGetList($model,$where_cat,$order);
		if($childrenCatgoryList){												
			$cat_array		= array_column($childrenCatgoryList,'cid');	//如果存在子分类，则获取子分类的文章列表
		}
		$cat_array[]		= $cid;
		$where['cat_id']	= array('in',$cat_array);
		$categoryArticleCount	= $this->baseGetArticleCount('article',$where);
		return $categoryArticleCount;
	}
	
	
	/**
	 * auth	：fangkai
	 * @param：获取文章详情
	 * time	：2016-10-6
	**/
	public function getArticleInfo($aid){
		$model = 'article';
		$articleInfo = $this->baseGetArticleInfo($model,$aid);
		
		return $articleInfo;
	}
	
	/**
	 * auth	：fangkai
	 * @param：更新文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function articleSave($aid,$data){
		$model = 'article';
		$data['addtime']	= time();
		$action = $this->baseArticleSave($model,$aid,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：添加文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function articleAdd($data){
		$model = 'article';
		$data['addtime']	= time();
		$action = $this->baseArticleAdd($model,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function articleDelete($aid){
		$model	= 'article';
		$action	= $this->baseArticleDelete($model,$aid);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取B2B首页文章数据
	 * time	：2016-10-8
	**/
	public function getIndexArticle(){
		$model	= 'article';
		$category = $this->getCategoryList();			//获取分类
		$where['agent_id']	= $this->agent_id;
		if($category){
			foreach($category as $key=>$val){
				$array_cid		= array();
				if($val['childCategory']){
					$array_cid	= array_column($val['childCategory'],'cid'); 
				}
				$array_cid[]= $val['cid'];
				$where['is_show']	= 1;
				$where['cat_id']	= array('in',$array_cid);
				$where['thumb']		= array('neq','');
				$articles_baike_thumb	= $this->baseGetList($model,$where,'addtime DESC',2);		//获取有图片的两篇文章
				
				$where['thumb']		= array('eq','');
				$articles_baike		= $this->baseGetList($model,$where,'addtime DESC',8);			//获取没有图片的八篇文章
				
				$category[$key]['articles_baike_thumb']	= $articles_baike_thumb;
				$category[$key]['articles_baike']		= $articles_baike;
			}
		}
		return $category;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取B2B首页最新文章
	 * time	：2016-10-10
	**/
	public function getNewIndexArticle(){
		$limit				= 9;
		$order				= 'zm_article.addtime DESC';
		$newIndexArticle	= $this->getArticleList($where,$order,$limit,$is_show=true);
	
		return $newIndexArticle;
	
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取B2B首页点击率最高的文章
	 * time	：2016-10-10
	**/
	public function getClickIndexArticle(){
		$limit				= 9;
		$order				= 'zm_article.click DESC';
		$clickIndexArticle	= $this->getArticleList($where,$order,$limit,$is_show=true);
	
		return $clickIndexArticle;
	}
	
	/**
	 * auth	：fangkai
	 * @param：文章点击量+1
	 * time	：2016-10-10
	**/
	public function setArticleClick($aid){
		$where['aid']	= $aid;
		$action	= M('article')->where($where)->setInc('click',1);
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：根据分类获取文章列表
	 * time	：2016-10-10
	**/
	public function getPartArticleList($cid,$limit,$is_show=true){
		$cat_array	= array();
		if($is_show == true){
			$where['is_show']		= 1;
			$where_cat['is_show']	= 1;
		}
		$model	= 'article_cat';
		$categoryInfo	= $this->baseGetCategoryInfo($model,$cid);
		if($categoryInfo == false){
			return false;
		}
		$where_cat['parent_id']	= $cid;
		$where_cat['agent_id']	= $this->agent_id;
		$order					= 'sort ASC';
		$childrenCatgoryList	= $this->baseGetList($model,$where_cat,$order);
		if($childrenCatgoryList){												
			$cat_array		= array_column($childrenCatgoryList,'cid');	//如果存在子分类，则获取子分类的文章列表
		}
		$cat_array[]		= $cid;
		$where['cat_id']	= array('in',$cat_array);
		$where['agent_id']	= $this->agent_id;
		
		$order	= 'addtime DESC';
		
		$limit_array	= explode(',',$limit);
		//如果为第一页，则查询出置顶的五篇文章，后续加上page_size-5的文章
		if($limit_array[0] == 0){
			$topArticleList	= $this->getTopArticleList($where,$order,5);
			if($topArticleList){
				$array_article_id	= array_column($topArticleList,'aid');
				$where['aid']		= array('not in',$array_article_id);
			}
			$limit = $limit_array[1]-count($topArticleList);
		}
		
		$partArticleList= $this->baseGetList('article',$where,$order,$limit);
		//合并数组
		if($limit_array[0] == 0){
			if($topArticleList){
				$partArticleList	= array_merge($topArticleList,$partArticleList);
			}	
			
		}
		
		return $partArticleList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取置顶文章
	 * time	：2016-1-12
	**/
	public function getTopArticleList($where,$order,$limit){
		$where['top']		= 1;
		$topArticleList= $this->baseGetList('article',$where,$order,$limit);
		
		return $topArticleList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：B2C首页文章列表
	 * time	：2016-10-10
	**/
	public function getCatgoryArticleList($cid,$limit,$is_show=true){
		if($cid){
			$catgoryArticleList	= $this->getPartArticleList($cid,$limit,$is_show);
		}else{
			$categoryList	= $this->getCategoryList(true);				//获取所有分类
			$cid			= $categoryList[0]['cid'];
			$catgoryArticleList	= $this->getPartArticleList($cid,$limit,$is_show);
		}
		return $catgoryArticleList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：B2C获取分类文章列表总数
	 * time	：2016-10-11
	**/
	public function getCatgoryArticleCount($cid,$is_show=true){
		$model	= 'article';
		if($is_show){
			$where['is_show']= 1;
		}
		$where['agent_id']	= $this->agent_id;
		
		if($cid){
			$where['parent_id']= $cid; 
		}else{
			$categoryList	= $this->getCategoryList(true);				//获取所有分类
			$where['parent_id']= $categoryList[0]['cid']; 
		}
		
		$newCategoryList	= $this->baseGetList('article_cat',$where);
		$cat_array		= array();
		if($newCategoryList){
			$cat_array		= array_column($newCategoryList,'cid');	//如果存在子分类，则获取子分类的文章列表
		}else{
			$cat_array[]	= $cid;
		}
		$where['cat_id']	= array('in',$cat_array);
		unset($where['parent_id']);
		$count	= $this->baseGetArticleCount($model,$where);
		return $count;
	}
	
	/**
	 * auth	：fangkai
	 * content：获取最上级文章分类
	 * time	：2016-10-11
	**/
	public function getParentCat($cat_id){
		$parentCat = $this->getMasterCat($cat_id);

		return $parentCat;
	}
	
	/**
	 * auth	：fangkai
	 * content：递归获取最上级文章分类
	 * time	：2016-7-7
	**/
	protected function getMasterCat($cat_id){
		$model			= 'article_cat';
		$where['cid']	= $cat_id;
		$catInfo = $this->baseGetInfo($model,$where);
		if($catInfo['parent_id'] != 0){
			$catInfo = self::getMasterCat($catInfo['parent_id']);
		}
		return $catInfo;
	}
	
}
?>
