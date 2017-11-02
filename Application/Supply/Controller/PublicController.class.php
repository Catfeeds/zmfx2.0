<?php
namespace Supply\Controller;
class PublicController extends SupplyController{
	
	
	/**
	 * ajax根据上级ID获取地址，2省级3市区级4区县级
	 * @param int $parent_id
	 */
	public function getRegion($parent_id){
		$R = M('region');
		$data = $R->where('parent_id = '.I('get.parent_id'))->select(); 
		$this->ajaxReturn($data);
	}	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}


?>