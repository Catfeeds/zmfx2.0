<?php
	/**
	* 公共模型基类
	* auth: fangkai
	* time: 2016/10/15
	**/
namespace Common\Model\Common;

class PublicBase {
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-15
	**/
	public function __construct(){
		$this->agent_id = C('agent_id');
	}
	
	/**
	 * auth	：fangkai
	 * ajax根据上级ID获取地址，2省级3市区级4区县级
     * @param int $parent_id
	 * time	：2016-10-5
	**/
    public function getRegion($parent_id){
    	$region = M('region');
		$where['parent_id']	= $parent_id;
		
    	$data	= $region->where($where)->select(); 
		
		return $data;
    }
	
}
?>
