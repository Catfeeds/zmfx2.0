<?php
/**
* 门店管理，预约控制器
* auth: fangkai
* time: 2016/11/11
**/
namespace Api_data\Controller;
class StoreController extends Api_dataController{
	
	 /**
	 * auth	：fangkai
	 * content：构造函数
	 * time	：2016-11-8
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid");
	} 
	
	/**
	 * auth	：fangkai
	 * @param：门店管理列表页
	 * time	：2016-11-11
	**/
	public function storeList($data=''){
		$Store		= new \Common\Model\Store();
		$page		= I('page',1,'intval');
		$n			= I('n',20,'intval');
		$limit		= ($page-1)*$n.','.$n;
		$data['zm_store.agent_id']	= $this->agent_id;
		$storeList	= $Store->getStoreList($data,'',$limit);

		foreach($storeList as $key=>$val){
			if($val['thumb']){
				$storeList[$key]['thumb']	= 'http://'.C('agent')['domain'].'/Public/Uploads/store/'.$val['thumb'];
			}else{
				$storeList[$key]['thumb']	= '';
			}
			if(empty($val['lng']) && empty($val['lat'])){
				$url	= 'http://api.map.baidu.com/geocoder/v2/?address='.$val['province_name'].'省'.$val['city_name'].'市'.$val['district_name'].'&city='.$val['city_name'].'市&output=json&ak=ZCGAYajULRfRFYrsUDndRHdu';
				$data	= $this->getLngLat($url);
				if($data['code'] == 100){
					$storeList[$key]['lng']	= $data['lng'];
					$storeList[$key]['lat']	= $data['lat'];
					$storeList[$key]['ck']	= 1;
					$save['lng']	= $data['lng'];
					$save['lat']	= $data['lat'];
					$action	= M('store')->where(array('agent_id'=>$this->agent_id,'id'=>$val['id']))->save($save);
				}
			}
		}
		
		$this->echo_data('100','获取成功',$storeList);
		
	}

	/**
	 * auth	：fangkai
	 * @param：ajax在线预约提交
	 * time	：2016-11-12
	**/
	public function onlineBooked(){
		$this->checkTokenUid($this->uid,$this->token);
		$data		= I('post.');
		$Store		= new \Common\Model\Store();
		$action		= $Store->onlineBookedAddbase($data);
		if($action){
			$this->result['msg']		= '预约成功';
			$this->result['ret']		= 100;
		}else{
			$this->result['msg']		= $Store->error;
			$this->result['ret']		= 235;
		}
		
		$this->echo_data($this->result['ret'],$this->result['msg']);
	}
	
	/**
	 * auth	：fangkai
	 * @param：ajax根据省，市，区获取门店列表
	 * time	：2016-11-12
	**/
	public function getStoreList(){
		$Store				= new \Common\Model\Store();
		$data['province_id']= I('post.province_id','','intval');
		$data['city_id']	= I('post.city_id','','intval');
		$data['district_id']= I('post.district_id','','intval');
		
		$storeList	 		= $Store->storeList($data);
		if($storeList){
			$this->echo_data('100','获取成功',$storeList);
		}else{
			$this->echo_data('233',$Store->error);
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：获取经纬度
	 * time	：2016-12-23
	**/
	public function getLngLat($url){
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch,CURLOPT_REFERER,'HTTP://demo.btbzm.com');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
		$result	= curl_exec ($ch);
		curl_close ($ch);
		$data['code']	= 101;
		if($result){
			$result	= json_decode($result,true);
			if(isset($result['status']) && $result['status'] == 0){
				$data['code']	= 100;
				$data['lng']	= $result['result']['location']['lng'];
				$data['lat']	= $result['result']['location']['lat'];
			}
		}
		return $data;
	}
	
	
	
}