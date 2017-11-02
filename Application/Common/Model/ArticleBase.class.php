<?php
	/**
	* 文章模型基类
	* auth: fangkai
	* time: 2016/9/30
	* updatetime：2016/10/6
	**/
namespace Common\Model;

class ArticleBase {
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-5
	**/
	public function __construct(){
		$this->agent_id = C('agent_id');
	}
	
	/**
	 * auth	：fangkai
	 * @param：添加数据
			  $data:要保存的数据
	 * time	：2016-10-5
	**/
	public function baseAdd($model,$where,$data){
		$model = M($model);
		$action	= $model->where($where)->add($data);

		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除数据
	 * time	：2016-10-5
	**/
	public function baseDelete($model,$where){
		$model = M($model);
		$action = $model->where($where)->delete();

		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：更新数据
			  $data:要保存的数据
	 * time	：2016-10-5
	**/
	public function baseSave($model,$where,$data){
		$model	= M($model);
		$action	= $model->where($where)->save($data);
			
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取详情
	 * time	：2016-10-5
	**/
	public function baseGetInfo($model,$where){
		$model = M($model);
		$getCategoryInfo = $model->where($where)->find();

		return $getCategoryInfo;
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取列表
	 * time	：2016-10-5
	**/
	public function baseGetList($model,$where,$order,$limit=''){
		$model = M($model);
		$categoryList = $model->field('content',true)->where($where)->order($order)->limit($limit)->select();
		return $categoryList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：关联查询获取文章列表
	 * time	：2016-10-5
	**/
	public function baseGetArticleList($model,$field,$where,$join,$order,$limit){
		$model = M($model);
		if(empty($field)){
			$field = 'zm_'.$model.'.*';
		}
		$articleList = $model
						->field($field)
						->where($where)
						->join($join)
						->order($order)
						->limit($limit)
						->select();
		return $articleList;
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取文章总数
	 * time	：2016-10-5
	**/
	public function baseGetArticleCount($model,$where){
		$model = M($model);
		$count = $model->where($where)->count();
		
		return $count;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取文章详情
	 * time	：2016-10-6
	**/
	public function baseGetArticleInfo($model,$aid){
		if(empty(trim($aid))){
			$this->error	= '该文章不存在';
			return false;
		}
		$where['agent_id']	= $this->agent_id;
		$where['aid']		= $aid;
		$getArticleInfo		= $this->baseGetInfo($model,$where);
		if($getArticleInfo){
			return $getArticleInfo;
		}else{
			$this->error	= '该文章不存在';
			return false;
		}
	}

	/**
	 * auth	：fangkai
	 * @param：更新文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function baseArticleSave($model,$aid,$data){
		if(empty(trim($data['title']))){
			$this->error	= '名称不能为空';
			return false;
		};
		if(empty(trim($data['editorValue']))){
			$this->error	= '内容不能为空';
			return false;
		}
		if(empty(trim($data['cat_id']))){
			$this->error	= '分类不能为空';
			return false;
		}
		$data['content']	= $data['editorValue'];
		
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize	=     3145728 ;// 设置附件上传大小
		$upload->exts		=     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath	=     './Public/Uploads/article/'; // 设置附件上传根目录
		$upload->savePath	=     ''; // 设置附件上传（子）目录
		// 上传文件
		$info   =   $upload->upload();

		if($info){// 上传成功
			$data['thumb'] = $info['thumb']['savepath'].$info['thumb']['savename'];
		}
		
		$where['aid']	= $aid;
		$where['agent_id'] = $this->agent_id;
		$getArticleInfo = $this->baseGetInfo($model,$where);

		if($getArticleInfo){
			$action	= $this->baseSave($model,$where,$data);
			if($action){
				return true;
			}else{
				$this->error = '修改失败';
				return false;
			}
		}else{
			$this->error = '该文章不存在';
			return false;
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：添加文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function baseArticleAdd($model,$data){
		if(empty(trim($data['title']))){
			$this->error	= '名称不能为空';
			return false;
		};
		if(empty(trim($data['editorValue']))){
			$this->error	= '内容不能为空';
			return false;
		}
		if(empty(trim($data['cat_id']))){
			$this->error	= '分类不能为空';
			return false;
		}
		$data['content']	= $data['editorValue'];
		
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize	=     3145728 ;// 设置附件上传大小
		$upload->exts		=     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath	=     './Public/Uploads/article/'; // 设置附件上传根目录
		$upload->savePath	=     ''; // 设置附件上传（子）目录
		// 上传文件
		$info   =   $upload->upload();

		if($info){// 上传成功
			$data['thumb'] = $info['thumb']['savepath'].$info['thumb']['savename'];
		}

		$where['title']		= trim($data['title']);
		$where['agent_id']	= $this->agent_id;
		$getCategoryInfo = $this->baseGetInfo($model,$where);

		if($getCategoryInfo){
			$this->error = '该篇帮助文章已存在，请忽重复添加';
			return false;
		}else{
			$data['agent_id'] = $this->agent_id;
			$action	= $this->baseAdd($model,$where,$data);
			if($action){
				return true;
			}else{
				$this->error = '添加失败';
				return false;
			}
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除文章数据
			  $data:要保存的数据
	 * time	：2016-10-6
	**/
	public function baseArticleDelete($model,$aid){
		$where['aid']		= $aid;
		$where['agent_id']	= $this->agent_id;
		$getCategoryInfo = $this->baseGetInfo($model,$where);
		
		if($getCategoryInfo){
			$action	= $this->baseDelete($model,$where);
			if($action){
				return true;
			}else{
				$this->error = '删除失败';
				return false;
			}
		}else{
			$this->error = '该文章不存在';
			return false;
		}
	}

	/**
	 * auth	：fangkai
	 * @param：更新分类数据
			  $data:要保存的数据
	 * time	：2016-10-7
	**/
	public function baseCategorySave($model,$cid,$data){
		if(empty(trim($data['catname']))){
			$this->error = '分类名称不能为空';
			return false;
		};

		$where['cid']		= $cid;
		$where['agent_id']	= $this->agent_id;
		$getCategoryInfo	= $this->baseGetInfo($model,$where);

		if($getCategoryInfo){
			if($data['parent_id'] == $cid){
				$data['parent_id']	= 0;
			}
			$action	= $this->baseSave($model,$where,$data);
			if($action){
				return true;
			}else{
				$this->error	= '修改失败';
				return false;
			}
		}else{
			$this->error = '该分类不存在';
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取分类详情
	 * time	：2016-10-7
	**/
	public function baseGetCategoryInfo($model,$cid=''){
		$where['cid']		= $cid;
		$where['agent_id']	= $this->agent_id;
		$getCategoryInfo	= $this->baseGetInfo($model,$where);
		if($getCategoryInfo){
			return $getCategoryInfo;
		}else{
			$this->error = '该分类不存在';
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：添加分类数据
			  $data:要保存的数据
	 * time	：2016-10-7
	**/
	public function baseCategoryAdd($model,$data){
		if(empty(trim($data['catname']))){
			$this->error = '分类名称不能为空';
			return false;
		};
		
		$where['catname']	= trim($data['catname']);
		$where['agent_id']	= $this->agent_id;
		$getCategoryInfo = $this->baseGetInfo($model,$where);

		if($getCategoryInfo){
			$this->error = '该分类已存在，请忽重复添加';
			return false;
		}else{
			$data['agent_id'] = $this->agent_id;
			$action	= $this->baseAdd($model,$where,$data);
			if($action){
				return true;
			}else{
				$this->error = '添加失败';
				return false;
			}
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：删除分类数据
	 * time	：2016-10-7
	**/
	public function baseCategoryDelete($model,$cid){
		$where['cid']		= $cid;
		$where['agent_id']	= $this->agent_id;
		$getCategoryInfo = $this->baseGetInfo($model,$where);
		
		if($getCategoryInfo){
			$where_cat['parent_id']	= $cid;
			$where_cat['agent_id']	= $this->agent_id;
			$getCategoryList = $this->baseGetList($model,$where_cat);
			if($getCategoryList){
				$this->error = '该分类下还有二级分类，请先删除二级分类';
				return false;
			}
			$action	= $this->baseDelete($model,$where);
			if($action){
				return true;
			}else{
				$this->error = '删除失败';
				return false;
			}
		}else{
			$this->error = '该分类不存在';
			return false;
		}
	}
	
	
	
}
?>
