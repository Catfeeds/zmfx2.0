<?php
/**
 * 门店模块
 */
namespace Admin\Controller;
use Think\Page;

class  StoreController extends AdminController {
	/*public function __construct() {
		parent::__construct();
		$this->store_show	= C("templateSetting")['store_show'];
		$this->agent_id 	= C("agent_id");
		if($this->store_show == 0){
			$this->error('没有开启门店管理功能，请联系管理员开通');
		}
	}*/
	
	/**
	 * auth	：fangkai
	 * content：门店列表
	 * time	：2016-10-14
	**/
	public function index(){
		$PbulicBase	= new \Common\Model\Common\PublicBase();
		$Store		= new \Common\Model\Store();
		
		$name				= I('get.name');
		$province_id		= I('get.province_id','','intval');
		$city_id			= I('get.city_id','','intval');
    	if($name){
    		$where['zm_store.name']			= array('like','%'.$name.'%');
    	}
    	if($province_id){
    		$where['zm_store.province_id']	= $province_id;
    	}
		if($city_id){
    		$where['zm_store.city_id']		= $city_id;
    	}
		
		/* 查出带分页的门店列表 */
		$count				= $Store->getStoreCount($where,false);
		$Page				= new \Think\Page($count,20);
		$limit				= $Page->firstRow.','.$Page->listRows;
		$storeList	 		= $Store->getStoreList($where,'',$limit,false);

		/* 查出省级地区 */
		$parent_id	= 1;
		$province = $PbulicBase->getRegion($parent_id); 
		
		$this->storeList	= $storeList;
		$this->province		= $province;
		$this->page			= $Page->show();
		
		$this->name			= $name;
		$this->province_id	= $province_id;
		$this->city_id		= $city_id;
		$this->seo_title	= '门店列表';
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：门店详情
	 * time	：2016-10-15
	**/
	public function info(){
		$Store		= new \Common\Model\Store();
		$id			= I('get.id','','intval');
		if($_POST){
			$data 	= I('post.');
			if($id){
				$result		= $Store->storeSave($id,$data);
				if($result){
					$this->success('修改成功');
				}else{
					$this->error($Store->error);
				}
			}else{
				$result		= $Store->storeAdd($data);
				if($result){
					$this->success('添加成功');
				}else{
					$this->error($Store->error);
				}
			}
		}else{
			if($id) {
				$storeInfo	= $Store->getStoreInfo($id);
				if($storeInfo == false){
					$this->error($Store->error);
				}
				$this->storeInfo	= $storeInfo;
			}
			
			/* 查出省级地区 */
			$PbulicBase	= new \Common\Model\Common\PublicBase();
			$parent_id	= 1;
			$province	= $PbulicBase->getRegion($parent_id); 
			
			$this->province	= $province;
			$this->seo_title= '门店详情';
			$this->display();
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：门店删除
	 * time	：2016-10-18
	**/
	public function storeDelete(){
		$Store		= new \Common\Model\Store();
		$id			= I('get.id','','intval');
		$action		= $Store->storeDelete($id);
	
		if($action){
			$this->success('删除成功',U('/Admin/Store/index/'));
		}else{
			$this->error($this->error);
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：预约列表
	 * time	：2016-10-20
	**/
	public function booked(){
		$Store		= new \Common\Model\Store();
		$store_id	= I('get.store_id','','intval');
		$type		= I('get.type');
		$keyword	= I('get.keyword');
		$starTime	= I('get.startTime','');
		$endTime	= I('get.endTime','');
		if(!empty($store_id)){
			$where['zm_store_booked.store_id']	= $store_id;
		}
		if(!empty($type) || $type == '0'){
			$where['zm_store_booked.type']		= $type;
		}
		if($keyword){
			$where['zm_store_booked.name|zm_store_booked.phone']	= array('like','%'.$keyword.'%') ;
		}
		if(!empty($starTime) && !empty($endTime)){
			if(strtotime($starTime) > strtotime($endTime)){
				$this->error('开始时间不能大于结束时间');
			}
			$where['zm_store_booked.time']	= array(array('egt',strtotime($starTime)),array('elt',strtotime($endTime)));
		}else if (!empty($starTime) && $endTime == ''){
			$where['zm_store_booked.time']	= array('egt',strtotime($starTime));
		}else if ($starTime == '' && !empty($endTime)){
			$where['zm_store_booked.time']	= array('elt',strtotime($endTime));
		}
		
		/* 查出带分页的预约列表 */
		$count				= $Store->getBookedCount($where,false);
		$Page				= new \Think\Page($count,18);
		$limit				= $Page->firstRow.','.$Page->listRows;
		$bookedList	 		= $Store->bookedList($where,'',$limit);
		$storeList	 		= $Store->getStoreList('','','',false);
		
		$this->bookedList	= $bookedList;
		$this->storeList	= $storeList;
		$this->page			= $Page->show();
		
		$this->store_id		= $store_id;
		$this->keyword		= $keyword;
		$this->type			= $type;
		$this->starTime		= $starTime;
		$this->endTime		= $endTime;
		$this->seo_title	= '预约列表';
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：预约回访记录保存
	 * time	：2016-10-20
	**/
	public function bookedSave(){
		if(IS_AJAX){
			$id				= I('post.id','','intval');
			$visit_record	= I('visit_record');
	
			$Store		= new \Common\Model\Store();
			$action		= $Store->bookedSave($id,$visit_record);
			if($action){
				$result['status']	= 100;
				$result['msg']		= '保存成功';
			}else{
				$result['status']	= 0;
				$result['msg']		= '保存失败';
			}
			$this->ajaxReturn($result);
		}
	
	}
	
	
	
	
	
	
	
	
	
}