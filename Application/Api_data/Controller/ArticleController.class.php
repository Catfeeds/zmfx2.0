<?php
/**
*	文章控制
*	zhy
*	2016年11月4日 16:29:13
*/
namespace Api_data\Controller;
class ArticleController extends Api_dataController{
 
	/**
	*	文章分类
	*	zhy
	*   2016年11月7日 16:18:42
	*/
	public function getCategoryList(){
		$Article			= new \Common\Model\Article();
       	$stopgap_data				= $Article->getCategoryList(true);
		$data=array();
		if($stopgap_data){
			foreach ($stopgap_data as $k=>$v){
				if($v['catname'] || $v['cid'] ){
					$data[$k]['cid']			=  $v['cid']; 
					$data[$k]['catname']		=  $v['catname'];					
				}
			}
			unset($stopgap_data);
			$this       	->echo_data('100','获取成功',$data);exit();
		}else{
			$this       	->echo_data('101','暂无分类',(object)null);exit();
		}		
 
    }
 
	/**
	*	文章分类列表
	*	zhy
	*   2016年11月7日 16:18:42
	*/
	public function getArticleCategoryList(){
		$id				    = I('cid' ,'intval');
		if(is_numeric($id)){
			$page				= I('page',1,'intval');		
			$Article			= new \Common\Model\Article();
			$n					= '20';							
			$firstrow			= ($page-1)*$n;
			$limit				= " $firstrow,$n";
			$stopgap_data				= $Article->getCatgoryArticleList($id,$limit);
			$data=array();
			if($stopgap_data){
				foreach ($stopgap_data as $k=>$v){
					if($v['title'] || $v['aid'] || $v['addtime']){		 
						$data[$k]['title']		=  $v['title']; 
						$data[$k]['aid']		=  $v['aid'];
						$data[$k]['addtime']	=  date("m-d", $v['addtime']);	
						$data[$k]['thumb']		=  'http://'.C('agent')['domain'].'/Public/Uploads/article/'.$v['thumb'];							
					}
				}
				unset($stopgap_data);
				$this       	->echo_data('100','获取成功',$data);exit();
			}else{
				$this       	->echo_data('101','分类下面没有文章',(object)null);exit();
			}
		}else{
			$this->echo_data('101','ID为非法值！');exit();
		}
    }
 
 
	/**
	*	文章详情
	*	zhy
	*   2016年11月7日 16:18:42

	public function getArticleInfo(){
		$id					= I('aid','intval');
		if(is_numeric($id)){
			$Article			= new \Common\Model\Article();		
			$data['info']		= $Article->getArticleInfo($id);
			if($data['info']){
				$data['masterCat']	= $Article->getParentCat($data['cat_id']);			//文章上级分类，用作面包屑
				$Article			->setArticleClick($id);								//进入详情页面，文章点击量+1	
			}else{
				$this       	->echo_data('101','没有文章',(object)null);exit();
			}
		}else{
			$this->echo_data('101','ID为非法值！');exit();
		}
    }
	*/
	
	 /**
	 *	新闻
	 *	zhy
	 *	2016年11月4日 17:37:42

    public function MasterArticle(){
		$type							= I('type','','intval');
		$cid							= I('cid' ,'','intval');
		$page							= I('page',1,'intval');
		if(in_array($type,array(1,2,3))){
			$Article					= new \Common\Model\Article();
			switch($type){
				case 1:		//新闻分类
					$data				= $Article->getCategoryList(true);
					break;
				case 2:		//新闻列表
					$n					= '20';							
					$firstrow			= ($page-1)*$n;
					$limit				= " $firstrow,$n";
					$data				= $Article->getCatgoryArticleList($cid,$limit);		
					break;
				case 3:		//新闻详情
					$data				= $Article->getArticleInfo($cid);
					$data['masterCat']	= $Article->getParentCat($data['cat_id']);			//文章上级分类，用作面包屑
					$Article			->setArticleClick($cid);							//进入详情页面，文章点击量+1
					break;
				default:
					//zzz
					break;
			}
		}
		
		$this -> echo_data('100','获取成功',$data);
	}
	*/
}