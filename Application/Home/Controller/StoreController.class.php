<?php
/**
* 门店管理，预约控制器
* auth: fangkai
* time: 2016/10/17
**/
namespace Home\Controller;
class StoreController extends NewHomeController{
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-17
	**/
	/*public function __construct(){
		parent::__construct();
		$this->agent_id = C('agent_id');
		$this->templateSetting	= C("templateSetting");
		if($this->templateSetting['store_show'] == 0){
			$this->error('没有开启门店管理功能，请联系管理员开通',U('/Home/index'));
		}
	}*/
	
	/**
	 * auth	：fangkai
	 * @param：门店管理列表页
	 * time	：2016-10-17
	**/
	public function index(){
		$Store		= new \Common\Model\Store();
		$area_array	= [
			'0'=>'华南地区',
			'1'=>'华东地区',
			'2'=>'华中地区',
			'3'=>'华北地区',
			'4'=>'西南地区',
			'5'=>'西北地区',
			'6'=>'东北地区'
		];
		//print_r($area_array);exit;
		$storeList	= array();
		foreach($area_array as $key=>$val){
			$where['area']	= $val;
			$storeList[$val]	= $Store->getStoreList($where);
			
		}

		
		
		/* 查出带分页的门店列表 */
		//$count				= $Store->getStoreCount();
		//$Page				= new \Think\Page($count,8);
		//$limit				= $Page->firstRow.','.$Page->listRows;
		//$storeList	 		= $Store->getStoreList($where,'',$limit);
		
		$this->storeList	= $storeList;
		//$this->page			= $Page->show();
		
		$StoreAdList		= M ('template_ad')->where(' status = 1 and ads_id = 57 and agent_id = '. C('agent_id'))->order('sort ASC')->find();
		if($StoreAdList)	$this->StoreAdList	= $StoreAdList;
		else				$this->StoreAdList	= null;

		$this->display();
		
	}

	/**
	 * auth	：fangkai
	 * @param：门店管理详情页
	 * time	：2016-10-18
	**/
	public function detail(){
		$Store		= new \Common\Model\Store();
		$id			= I('get.id','','intval');
		$storeInfo	= $Store->getStoreInfo($id);
		if($storeInfo == false){
			$this->error($Store->error);
		}
		/* 进入门店详情页面，点击量+1 */
		$Store->setStoreClick($id);
		
		$this->storeInfo	= $storeInfo;
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * @param：ajax获取在线预约页面信息
	 * time	：2016-10-18
	**/
	public function onlineBooked(){
		$data		= I('post.');
		$Store		= new \Common\Model\Store();
		$action		= $Store->onlineBookedAdd($data);
		if($action){
			$result['msg']		= '预约成功';
			$result['status']	= 100;
		}else{
			$result['msg']		= $Store->error;
			$result['status']	= 0;
		}
		$this->ajaxReturn($result);
	}
	
	/**
	 * auth	：fangkai
	 * @param：ajax根据省，市，区获取门店列表
	 * time	：2016-10-21
	**/
	public function getStoreList(){
		if(IS_AJAX){
			$Store				= new \Common\Model\Store();
			$data['province_id']= I('post.province_id','','intval');
			$data['city_id']	= I('post.city_id','','intval');
			$data['district_id']= I('post.district_id','','intval');
			
			$storeList	 		= $Store->storeList($data);
			if($storeList){
				$result['status']	= 100;
				$result['data']		= $storeList;
			}else{
				$result['status']	= 0;
			}
			$this->ajaxReturn($result);
		}
	}
	
	
	
	
	
}