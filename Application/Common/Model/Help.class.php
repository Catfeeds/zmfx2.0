<?php
	/**
	* 帮助模型类
	* auth: fangkai
	* time: 2016/10/6
	**/
namespace Common\Model;

class Help extends ArticleBase {
	
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
	 * @param：获取分类列表
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getCategoryList($is_show=true){
		$model = 'help_category';
		if($is_show == true){
			$where['is_show'] = 1; 
		}
		$order = 'sort ASC';
		$where['agent_id'] = $this->agent_id;
		
		$categoryList = $this->baseGetList($model,$where,$order);

		if($categoryList){
			return $categoryList;
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
		$model			= 'help_category';
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
		$model	= 'help_category';
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
		$model	= 'help_category';
		$action	= $this->baseCategoryAdd($model,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除分类数据
	 * time	：2016-10-5
	**/
	public function categoryDelete($cid){
		$model = 'help_category';
		
		$where_help['cat_id']		= $cid;
		$checkArticle				= $this->baseGetList('help',$where_help);
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
	 * @param：获取文章列表，以全部列表的一维数组的形式展示
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getArticleList($where,$limit,$is_show=true ){
		$model = 'help';
		if($is_show == true){
			$where['zm_help.is_show'] = 1;
		}
		
		$field 	= 'zm_help.aid,zm_help.is_show,zm_help.cat_id,zm_help.title,zm_help.addtime,zm_help.link,zm_help.description,zm_help.thumb,zm_help_category.catname';
		$where['zm_help.agent_id']	= $this->agent_id;
		$join	= 'left join zm_help_category on zm_help.cat_id = zm_help_category.cid';
		$order	= 'zm_help.aid DESC';
		
		$articleList = $this->baseGetArticleList($model,$field,$where,$join,$order,$limit);
		if($articleList){
			return $articleList;
		}else{
			return false;
		}
	
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取文章列表，以分类加列表的二维数组的形式展示
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getArticleListTwo($where,$limit,$is_show=true ){
		$model = 'help';
		if($is_show == true){
			$where['zm_help.is_show'] = 1;
		}
		$categoryList	= $this->getCategoryList($is_show);
		if($categoryList){
			foreach($categoryList as $key=>$val){
				$where['cat_id']	= $val['cid'];
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
		$model = 'help';
		$where['agent_id']	= $this->agent_id;
		if($is_show == true){
			$where['is_show']	= 1;
			$categoryList	= $this->baseGetList('help_category',$where);
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
	 * @param：获取文章详情
	 * time	：2016-10-6
	**/
	public function getArticleInfo($aid){
		$model = 'help';
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
		$model	= 'help';
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
		$model	= 'help';
		$data['addtime']	= time();
		$action	= $this->baseArticleAdd($model,$data);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function articleDelete($aid){
		$model	= 'help';
		$action	= $this->baseArticleDelete($model,$aid);
		
		return $action;
	}
	
	
	
}
?>
