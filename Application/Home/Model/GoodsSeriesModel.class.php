<?php
/**
 * 产品系列模型类
 */
namespace Home\Model;
use Think\Model;
class GoodsSeriesModel extends Model{
	/**
	 * auth	：fangkai
	 * content：获取产品系列分类列表
	 * time	：2016-7-2
	**/
    public function getSeriesList($where='1=1',$sort= 'goods_series_id DESC',$pageid=1,$pagesize=20) {
		$limit    = (($pageid-1)*$pagesize).','.$pagesize;
		$series_list = $this->where($where)->order($sort)->limit($limit)->select();
		if($series_list){
			return $series_list;
		}else{
			return false;
		}
    }	
	
	
	
	
}
?>
