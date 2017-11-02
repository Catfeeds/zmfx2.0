<?php
/**
 * 产品模型类
 */
namespace Home\Model;
use Think\Model;
class ArticleCatModel extends Model{
	/**
	 * auth	：fangkai
	 * content：获取单个文章分类
	 * time	：2016-7-4
	**/
    public function getCategoryOne($where='1=1',$field="*") {
		$CategoryOne = $this->field($field)->where($where)->find();
		if($CategoryOne){
			return $CategoryOne;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：获取文章分类列表
	 * time	：2016-7-4
	**/
    public function getCategoryList($where='1=1',$sort= 'cid DESC',$limit,$field="*") {
		$articl_cat = $this->field($field)->where($where)->order($sort)->limit($limit)->select();
		if($articl_cat){
			return $articl_cat;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：获取首页展示的文章
	 * time	：2016-7-4
	**/
    public function article_list($where='1=1',$sort,$limit='0,5',$field="*") {
		$article_list = $this
						->alias('c')
						->field($field)
						->join('left join zm_article as a on c.cid = a.cat_id')
						->where($where)
						->order($sort)
						->limit($limit)
						->select();
		if($article_list){
			return $article_list;
		}else{
			return false;
		}
    }
	
	
	
	
	
}
?>
