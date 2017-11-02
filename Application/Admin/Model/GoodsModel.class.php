<?php
/**
 * 产品模型类
 */
namespace Admin\Model;
use Think\Model;
class GoodsModel extends Model{
  
	/**
	 * auth	：fangkai
	 * content：获取成品/定制产品列表
	 * time	：2016-6-29
	**/
    public function getGoodsList($where='1=1',$sort= 'goods_id DESC',$pageid=1,$pagesize=20) {
		$limit    = (($pageid-1)*$pagesize).','.$pagesize;
		$goods_list = $this->where($where)->order($sort)->limit($limit)->select();
		if($goods_list){
			return $goods_list;
		}else{
			return false;
		}
    }
	
	/**
	 * auth	：fangkai
	 * content：获取成品/定制产品总数
	 * time	：2016-6-29
	**/
    public function getcount($where='1=1'){
		$count = $this->where($where)->count();
		return $count;
	
	}
	
	/**
	 * auth	：fangkai
	 * content：活动商品与普通商品转换保存
		  save：需要保存的数据
	 * time	：2016-6-29
	**/
	public function changeGoods($where='1=1',$save){
		$result = $this->where($where)->save($save);
		if($result){
			return true;
		}else{
			return false;
		}
	}
	

}