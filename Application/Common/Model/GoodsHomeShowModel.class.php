<?php
/**
 * 活动产品模型类
 */
namespace Common\Model;
use Think\Model;
class GoodsHomeShowModel extends Model{
  
	/**
	 * auth	：fangkai
	 * content：获取普通首页展示的产品
	 * time	：2016-8-22
	**/
    public function GoodsHomeShowList($where='1=1',$sort= 'id DESC',$pageid=1,$pagesize=20) {
		$limit    = (($pageid-1)*$pagesize).','.$pagesize;
		$goodsHomeShowList = $this->where($where)->order($sort)->limit($limit)->select();
		if($goodsHomeShowList){
			return $goodsHomeShowList;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：首页展示普通产品添加
	 * time	：2016-8-22
	**/
    public function GoodsHomeShowAdd($data) {
		$action = $this->add($data);
		if($action){
			return true;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：首页展示普通产品删除
	 * time	：2016-8-22
	**/
    public function GoodsHomeShowDel($where='1=1') {
		$action = $this->where($where)->delete();
		if($action){
			return true;
		}else{
			return false;
		}
    }

}