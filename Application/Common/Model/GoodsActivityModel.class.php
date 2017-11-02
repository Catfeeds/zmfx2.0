<?php
/**
 * 活动产品模型类
 */
namespace Common\Model;
use Think\Model;
class GoodsActivityModel extends Model{
  
	/**
	 * auth	：fangkai
	 * content：获取活动产品
	 * time	：2016-8-22
	**/
    public function GoodsActivityList($where='1=1',$sort= 'id DESC',$pageid=1,$pagesize=20) {
		$limit    = (($pageid-1)*$pagesize).','.$pagesize;
		$goodsActivityList = $this->where($where)->order($sort)->limit($limit)->select();
		if($goodsActivityList){
			return $goodsActivityList;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：活动产品添加
	 * time	：2016-8-22
	**/
    public function GoodsActivityAdd($data) {
		$action = $this->add($data);
		if($action){
			return true;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：活动产品保存
	 * time	：2016-8-23
	**/
    public function GoodsActivitySave($where='1=1',$save) {
		$action = $this->where($where)->save($save);
		if($action){
			return true;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：活动产品删除
	 * time	：2016-8-23
	**/
    public function GoodsActivityDelete($where='1=1') {
		$action = $this->where($where)->delete();
		if($action){
			return true;
		}else{
			return false;
		}
    }
	

}