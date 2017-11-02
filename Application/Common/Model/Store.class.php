<?php
	/**
	* 门店模型类，包含门店管理，预约消息
	* auth: fangkai
	* time: 2016/10/14
	**/
namespace Common\Model;

class Store {
	
	public $error = '';
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-14
	**/
	public function __construct(){
		$this->uid		= $_SESSION['web']['uid'];
		$this->agent_id = C('agent_id');
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取门店列表
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-5
	**/
	public function getStoreList($where,$order,$limit,$is_show=true){
		$where['zm_store.agent_id']	= $this->agent_id;
		if($is_show){
			$where['zm_store.is_show']	= 1;
		}
		if(empty($order)){
			$order	= 'zm_store.sort DESC';
		}
		$storeList	= M('store')
						->field('zm_store.id,zm_store.name,zm_store.contacts,zm_store.email,zm_store.is_show,zm_store.sort,zm_store.addtime,zm_store.phone,zm_store.address,zm_store.thumb,zm_store.description,zm_store.province_id,zm_store.city_id,zm_store.district_id,zm_store.lng,zm_store.lat,zm_store.area,A.region_name as province_name,B.region_name as city_name,C.region_name as district_name ')
						->where($where)
						->join('left join zm_region as A on A.region_id = zm_store.province_id')
						->join('left join zm_region as B on B.region_id = zm_store.city_id')
						->join('left join zm_region as C on C.region_id = zm_store.district_id')
						->order($order)
						->limit($limit)
						->select();
		return $storeList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取门店总数
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-10-17
	**/
	public function getStoreCount($where,$is_show=true){
		if($is_show == true){
			$where['is_show']	= 1;
		}
		$where['agent_id']	= $this->agent_id;
		$count	= M('store')->where($where)->count();
		return $count;
	}
	
	
	/**
	 * auth	：fangkai
	 * @param：获取门店详情
	 * time	：2016-10-17
	**/
	public function getStoreInfo($id=''){
		$where['zm_store.agent_id']	= $this->agent_id;
		$where['zm_store.id']		= $id;
		$storeInfo	= M('store')
						->field('zm_store.*,A.region_name as province_name,B.region_name as city_name,C.region_name as district_name ')
						->where($where)
						->join('left join zm_region as A on A.region_id = zm_store.province_id')
						->join('left join zm_region as B on B.region_id = zm_store.city_id')
						->join('left join zm_region as C on C.region_id = zm_store.district_id')
						->find();
		if($storeInfo){
			return $storeInfo;
		}else{
			$this->error = '该门店不存在';
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：门店点击量+1
	 * time	：2016-10-18
	**/
	public function setStoreClick($id){
		$where['aid']		= $id;
		$where['agent_id']	= $this->agent_id;
		$action	= M('store')->where($where)->setInc('click',1);
		return $action;
	}
	
	
	/**
	 * auth	：	fangkai
	 * @param：	更新门店数据
				$id：门店ID
				$data：要保存的数据
	 * time	：	2016-10-17
	**/
	public function storeSave($id='',$data){
		$checkStore	= $this->getStoreInfo($id);
		if($checkStore == false){
			return false;
		}
		$data = $this->dataProcessing($data);
		if($data['province_id'] != $checkStore['province_id'] || $data['city_id'] != $checkStore['city_id'] || $data['district_id'] != $checkStore['district_id']){
			$data['lng']	= '';
			$data['lat']	= '';
		}
		
		$where['agent_id']	= $this->agent_id;
		$where['id']		= $id;
		$action	= M('store')->where($where)->save($data);
		if($action){
			return true;
		}else{
			if($this->error == ''){
				$this->error = '修改失败';
			}
			$this->error	= $this->error;
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：添加门店数据
			  $data:要保存的数据
	 * time	：2016-10-17
	**/
	public function storeAdd($data){
		$data = $this->dataProcessing($data);

		$action	= M('store')->add($data);
		if($action){
			return true;
		}else{
			if($this->error == ''){
				$this->error = '添加失败';
			}
			$this->error	= $this->error;
			return false;
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：数据处理
			  $data:要保存的数据
	 * time	：2016-10-17
	**/
	public function dataProcessing($data){
		if($data['name'] == '' || mb_strlen($data['name']) > 50  ){
			$this->error 	= '门店名称不能为空且不能超过50个字符';
		}
		if($data['contacts'] == ''){
			$this->error	= '联系人不能为空';
			return false;
		}
		if($data['email'] == '' ){
			$this->error	= '邮箱不能为空';
			return false;
		}
		if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
			$this->error	= '邮箱格式不正确';
			return false;
		}
		if($data['phone'] == '' ){
			$this->error	= '联系电话不能为空';
			return false;
		}
		if($data['area'] == ''){
			$this->error	= '请选择所属地区';
			return false;
		}
		if(!in_array($data['area'],array('华北地区','华中地区','华东地区','华南地区','东北地区','西北地区','西南地区'))){
			$this->error	= '所属地区不存在';
			return false;
		}
		if($data['province_id'] == ''){
			$this->error	= '请选择省份';
			return false;
		}
		if($data['city_id'] == ''){
			$this->error	= '请选择城市';
			return false;
		}
		if($data['district_id'] == ''){
			$this->error	= '请选择县区';
			return false;
		}
		if($data['address'] == '' || mb_strlen($data['address']) > 200 ){
			$this->error	= '详细地址不能为空且不能超过200个字符';
			return false;
		}
		// if($data['content'] == '' ){
			// $this->error	= '门店描述不能为空';
			// return false;
		// }

		$data['addtime']	= time();
		$data['agent_id']	= $this->agent_id;
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize	=     2048000 ;// 设置附件上传大小
		$upload->exts		=     array('jpg', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath	=     './Public/Uploads/store/'; // 设置附件上传根目录
		$upload->savePath	=     ''; // 设置附件上传（子）目录
		// 上传文件
		$info   =   $upload->upload();

		if($info){// 上传成功
			$data['thumb'] = $info['thumb']['savepath'].$info['thumb']['savename'];
		}
		
		return $data;
	}
	
	
	/**
	 * auth	：fangkai
	 * @param：删除门店数据
	 * time	：2016-10-18
	**/
	public function storeDelete($id){
		$checkStore	= $this->getStoreInfo($id);
		if($checkStore == false){
			return false;
		}
		$where['id']		= $id;
		$where['agent_id']	= $this->agent_id;
		$action		= M('store')->where($where)->delete();
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：在线，下单预约 type: 0 在线预约 ， 1 下单预约  ， 3 电话预约
	 * time	：2016-10-18
	**/
	public function onlineBookedAdd($data){
		if($data['type'] != 3){
			if($this->uid == ''){
				$this->error	= '请先登录再预约';
				return false;
			}
		}
		
		$result	= $this->onlineBookedAddbase($data);
		
		if($result){
			/* 查出门店的邮箱 */
			$storeInfo	= $this->getStoreInfo($data['store_id']);
			
			$Message	= new \Admin\Model\MessageModel();
			if($data['type'] == 1){
				$msg	= '下单预约';
				$where['zm_store_booked.orderid']	= $data['orderid'];  
			}else if($data['type'] == 3){
				$msg	= '电话预约';
				$where['zm_store_booked.id']		= $result;
			}else{
				$msg	= '在线预约';
				$where['zm_store_booked.id']		= $result;
			}
			$where['zm_store_booked.agent_id']	= $this->agent_id;
			$bookedInfo	= $this->getBookedInfo($where);
			
			if($data['type'] != 3){
				$info		= '您的客户 '.$_SESSION['web']['username'].' 在 '.date('Y-m-d H:i',$bookedInfo['addtime']).' 成功<span style="color:red">'.$msg.'</span>到店服务<br />';
			}
			$info		.= '预约店名：'.$storeInfo['name'].'<br />';
			$info		.= '预约时间：'.date('Y年m月d号',$bookedInfo['time']).'<br />';
			if($data['type'] != 3){
				$info		.= '预约人：'.$bookedInfo['name'].'<br />';
			}
			$info		.= '联系电话：'.$bookedInfo['phone'].'<br />';
			$info		.= '请及时与客户取得联系！';
			$address	= $storeInfo['email'];
			$resMessage	= $Message->sendMail($address,$info);
			return true;
		}else{
			$this->error	= '预约失败';
			return false;
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：在线，下单预约 type: 0 在线预约 ， 1 下单预约  ， 3 电话预约
	 * time	：2016-10-18
	**/
	public function onlineBookedAddbase($data){
		$checkStore	= $this->getStoreInfo($data['store_id']);

		if($checkStore == false){
			return false;
		}
		if($data['type'] != 3){
			if(strtotime($data['time']) < time()){
				$this->error	= '预约时间不能小于当前时间';
				return false;
			}
			if($data['name'] == ''){
				$this->error	= '联系人不能为空';
				return false;
			}
		}
		
		if(!preg_match('/^(13[0-9]|15[0-9]|17[678]|18[0-9]|14[57])[0-9]{8}$/',$data['phone'])){
			$this->error	= '手机格式不正确';
			return false;
		}
		$data['addtime']	= time();
		$data['time']		= strtotime($data['time']);
		$data['agent_id']	= $this->agent_id;
		$data['name']       = $data['name'] ? $data['name'] : '';
		$data['sex']        = $data['sex'] ? $data['sex'] : 0;
		$action	= M('store_booked')->add($data);
		
		if($action){
			//发送门店预约成功短信		2018-08-18      zengmingming
			$SMS = new \Common\Model\Sms();		
			$sms_agent_info_arr = M('sms_agent_info')->where("agent_id=".C('agent_id'))->find();
			if(empty($sms_agent_info_arr)){ $sms_agent_info_arr = M('sms_agent_info')->where("agent_id=7")->find(); }

			//发送信息给客户
			$site_name_arr[] = $checkStore['name'];	
			$SMS->SendSmsByType($SMS::CUSTOMER_BOOKING_SUCCESS,$data['phone'],$site_name_arr);	
			
			//发送信息给门店
			$site_name_md_arr[] = $data['phone']; 
			$site_name_md_arr[] = $data['name']; 
			$SMS->SendSmsByType($SMS::STORE_BOOKING_SUCCESS,$checkStore['phone'],$site_name_md_arr);	

			//发送信息给商家
			if($sms_agent_info_arr["sms_push_reminder"]!=0){
				$site_name_sj_arr[] = $data['phone']; 
				$site_name_sj_arr[] = $data['name']; 
				$site_name_sj_arr[] = $checkStore['name']; 
				$SMS->SendSmsByType($SMS::BUSINESS_BOOKING_SUCCESS,$sms_agent_info_arr['sms_push_reminder'],$site_name_sj_arr);	
			}
			return $action;
		}else{
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：在线，下单预约列表
	 * time	：2016-10-18
	**/
	public function bookedList($where,$order,$limit){
		$where['zm_store_booked.agent_id']	= $this->agent_id;
		if(empty($order)){
			$order	= 'zm_store_booked.id DESC';
		}
		$bookedList	= M('store_booked')
						->field('zm_store_booked.*,A.region_name as province_name,B.region_name as city_name,C.region_name as district_name,D.name as store_name,D.province_id,D.city_id,D.district_id')
						->where($where)
						->join('left join zm_store as D on D.id = zm_store_booked.store_id')
						->join('left join zm_region as A on A.region_id = D.province_id')
						->join('left join zm_region as B on B.region_id = D.city_id')
						->join('left join zm_region as C on C.region_id = D.district_id')
						->order($order)
						->limit($limit)
						->select();
		return $bookedList;
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取预约详情
	 * time	：2016-10-20
	**/
	public function getBookedInfo($where){
		$bookedInfo	= M('store_booked')
						->field('zm_store_booked.*,A.region_name as province_name,B.region_name as city_name,C.region_name as district_name ,D.name as store_name,D.province_id,D.city_id,D.district_id,D.address')
						->where($where)
						->join('left join zm_store as D on D.id = zm_store_booked.store_id')
						->join('left join zm_region as A on A.region_id = D.province_id')
						->join('left join zm_region as B on B.region_id = D.city_id')
						->join('left join zm_region as C on C.region_id = D.district_id')
						->find();
		if($bookedInfo){
			return $bookedInfo;
		}else{
			$this->error = '该预约信息不存在';
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取预约总数
	 * time	：2016-10-20
	**/
	public function getBookedCount($where){
		$where['agent_id']	= $this->agent_id;
		$count	= M('store_booked')->where($where)->count();
		return $count;
	}
	
	/**
	 * auth	：fangkai
	 * @param：预约保存
	 * time	：2016-10-20
	**/
	public function bookedSave($id,$visit_record){
		$where_check['zm_store_booked.agent_id']= $this->agent_id;
		$where_check['zm_store_booked.id']		= $id;
		$checkBooked	= $this->getBookedInfo($where_check);
		if($checkBooked == false){
			return false;
		}
		if(mb_strlen($visit_record) > 1000 || empty($visit_record)){
			$this->error	= '回访记录不能为空且不能超过1000个字符';
			return false;
		}
		$where['id']		= $id;
		$where['agent_id']	= $this->agent_id;
		$save['visit_record']	= $visit_record;
		$save['is_visit']	= 1;
		
		$action	= M('store_booked')->where($where)->save($save);
		
		return $action;
	}
	
	/**
	 * auth	：fangkai
	 * @param：通过订单号查询门店信息
	 * time	：2016-10-21
	**/
	public function getOrderStoreInfo($order_id){
		$where_check['zm_store_booked.agent_id']	= $this->agent_id;
		$where_check['zm_store_booked.orderid']	= $order_id;
		$checkBooked	= M('store_booked')->where($where_check)->find();
		if($checkBooked == false){
			$this->error	= '该预约信息不存在';
			return false;
		}
		$storeInfo	= $this->getStoreInfo($checkBooked['store_id']);
		if($storeInfo){
			return $storeInfo;
		}else{
			return false;
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：根据省，市，区获取门店列表
	 * time	：2016-10-21
	**/
	public function storeList($data){
		if(!empty($data['province_id'])){
			$where['zm_store.province_id']	= $data['province_id'];
		}
		if(!empty($data['city_id'])){
			$where['zm_store.city_id']		= $data['city_id'];
		}
		if(!empty($data['district_id'])){
			$where['zm_store.district_id']	= $data['district_id'];
		}
		$storeList	 = $this->getStoreList($where);
		
		return $storeList;
		
	}
	
}
?>
