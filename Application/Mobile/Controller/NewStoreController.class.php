<?php
/**
* 门店管理，预约控制器
* auth: zengmingming
* time: 2017/7/28/
**/
namespace Mobile\Controller;
class NewStoreController extends NewMobileController{
 	/**
	 * auth	：zengmingming
	 * content：构造函数
	 * time	：2017-07-28
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid");
	} 
	
	/**
	 * auth	：zengmingming
	 * @param：门店管理列表页
	 * time	：2017-07-28
	**/
	public function index(){
		$Store		= new \Common\Model\Store();
		$storeList	= $Store->getStoreList();	
		$domain = $_SERVER['HTTP_HOST'];
		foreach($storeList as $key=>$val){
			if($val['thumb']){
				$storeList[$key]['thumb']	= 'http://'.$domain.'/Public/Uploads/store/'.$val['thumb'];
			}else{
				$storeList[$key]['thumb']	= '';
			}
		}	
		$this->storeList = $storeList;		
		$this ->display();
	}
	
	/**
	 * auth	：zengmingming
	 * @param：在线预约
	 * time	：2017-07-28
	**/
	public function online(){
		if(IS_POST){
			$data['agent_id']	= $this->agent_id; 
			$data["phone"]		= I('phone','','number_int');
			$data["name"]		= I('name','');
			$data["store_id"]	= I('store_id','');
			$data["time"]		= I('time','');
			$data["is_show"]		= 1;
			$Store	= new \Common\Model\Store();
			$action	= $Store->onlineBookedAddbase($data);
			if($action){
				$result = array('success'=>true, 'msg'=>'预约成功','url'=>'/Store/index.html');
			}else{
				$result = array('success'=>false, 'msg'=>$Store->error);
			}
			$this->ajaxReturn($result);
		}else{
			//省份列表
			$R = M('region');
			$provinceList = $R->where('parent_id = 1')->select();
			foreach($provinceList as $key=>$val){
				$this->provinceArr .= "\"{$val['region_name']}\","; 	
			}
			$Store		= new \Common\Model\Store();
			$storeList	= $Store->getStoreList();	
			$domain = $_SERVER['HTTP_HOST'];
			foreach($storeList as $key=>$val){
				$storeStr .= "\"<input type='hidden' id='store_id' name='store_id' value='{$val['id']}' />{$val['name']}\",";
			}	
			$this->storeArr = $storeStr;	
			$this ->display();	
		}
	}
	/**
	 * auth	：zengmingming
	 * content：切换城市
	 * time	：2017-7-27
	**/
    public function loadcity(){
		//省份列表
		$R = M('region');
		//获取省份的数组的Key值
		$region_name = I('region_name','','string');
		$region_type = I('region_type','');
		
		if($region_type==2){
			$rArr = $R->where("region_name='".$region_name."' and parent_id=1")->field("region_id")->find();
			$parent_id = $rArr["region_id"];
		}elseif($region_type==3){
			$rArr = $R->where("region_name='".$region_name."' and parent_id<>1")->field("region_id")->find();
			$parent_id = $rArr["region_id"];	
		}
		
		$cityList = $R->where('parent_id='.$parent_id)->field("region_id,region_name")->select();
		$cityArr = array();	
		foreach($cityList as $key=>$val){
			$cityArr[] = "{$val['region_name']}";
		}
		$result = array('success'=>true, 'data'=>$cityArr);
        $this->ajaxReturn($result);	
	}

	
	
}