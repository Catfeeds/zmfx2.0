<?php
/**
 * 产品模型类
 */
namespace Common\Model;
use Think\Model;
class GoodsCategoryConfigModel extends Model{

	/**
	 * auth	：fangkai
	 * content：获取分类列表
	 * time	：2016-7-16
	**/
    public function getCategoryAlias($where='1=1',$sort= 'category_config_id DESC',$field="*") {
			$categoryAlias_list = $this->field($field)->where($where)->order($sort)->select();
			if( $categoryAlias_list ){
				return $categoryAlias_list;
			}else{
				return false;
			}
    }
	
	/**
	 * auth	：fangkai
	 * content：修改分类配置信息
	 * time	：2016-8-1
	**/
    public function updategetCategory($where='1=1',$save) {
			$result = $this -> where($where) -> save($save);
			if($result){
				return true;
			}else{
				return false;
			}
    }
}
?>
