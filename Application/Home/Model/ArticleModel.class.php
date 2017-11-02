<?php
/**
 * 文章模型类
 */
namespace Home\Model;
use Think\Model;
class ArticleModel extends Model{
	/**
	 * auth	：fangkai
	 * content：获取文章列表
	 * time	：2016-7-4
	**/
    public function getArticleList($where='1=1',$sort= 'aid DESC',$pageid=1,$pagesize=20,$field="*") {
		$limit    = (($pageid-1)*$pagesize).','.$pagesize;
		$ArticleList = $this->field($field)->where($where)->order($sort)->limit($limit)->select();
		if($ArticleList){
			return $ArticleList;
		}else{
			return false;
		}
    }
	/**
	 * auth	：fangkai
	 * content：获取文章总数
	 * time	：2016-7-6
	**/
    public function getcount($where='1=1') {
		$count = $this->where($where)->count();
		return $count;
    }
	/**
	 * auth	：fangkai
	 * content：获取文章详情
	 * time	：2016-7-6
	**/
    public function getArticleInfo($where='1=1',$field="*") {
		$ArticleInfo = $this->field($field)->where($where)->find();
		if($ArticleInfo){
			return $ArticleInfo;
		}else{
			return false;
		}
	}
	/**
	 * auth	：fangkai
	 * content：单个字段加减操作,$type：1 加法，2 减法
	 * time	：2016-7-6
	**/
    public function setArticle($where='1=1',$setfield,$number,$type) {
		if(empty($setfield) || empty($number)){
			return false;
		}
		switch($type){
			case 1:
				$action = $this->where($where)->setInc($setfield,$number);
				break;
			case 2:
				$action = $this->where($where)->setDec($setfield,$number);
				break;
			default:
				return false;
				break;
		}
		if($action){
			return true;
		}else{
			return false;
		}
	}
	
	
}
?>
