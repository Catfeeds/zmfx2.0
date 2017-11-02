<?php
namespace Api_view\Controller;
class ArticleController extends Api_viewController{
      
    /**
     * 分销商首页
     */
    public function getArticleInfo($aid){
		
		$id						= I('aid','','intval')?I('aid','','intval'):$aid;
		if(is_numeric($id)){
			$Article			= new \Common\Model\Article();		
			$data['info']		= $Article->getArticleInfo($id);
			if($data['info']){
				$data['masterCat'] = $Article->getParentCat($data['info']['cat_id']);			//文章上级分类，用作面包屑
				$Article		   ->setArticleClick($id);										//进入详情页面，文章点击量+1	
				$this -> data      = $data;
				$this -> display('getArticleInfo');
			}else{
				$this->data='没有该文章信息！';
				$this->display("Public/error");
			}
		}else{
			$this -> data = 'ID为非法值！';
			$this -> display("Public/error");
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：关于我们
	 * time	：2016-11-14
	**/
	public function aboutUs() {
		$where['title']		= '关于我们';
		$where['agent_id']	= C('agent_id');
		$where['is_show']	= 1;
		$Article			= new \Common\Model\ArticleBase();		
		$data				= $Article->baseGetInfo('article',$where);
		if($data){
			$aid = $data['aid'];
		}
		$this->getArticleInfo($aid);
		exit;
	}
	
	/**
	 * auth	：fangkai
	 * content：珠宝试戴分享页面
	 * time	：2016-11-14
	**/
	public function tryShare() {
		$SI					= M('share_image');
		$id					= I('get.id','','intval');
		$where['id']		= $id;
		$data				= $SI->where($where)->find();
		//print_r($data);exit;
		$this->data 		= $data;
		$this->display();
	}
	
}
