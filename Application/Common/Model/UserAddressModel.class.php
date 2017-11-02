<?php
namespace Common\Model;
use Think\Model;
class UserAddressModel extends Model{
 	
 	//得到地址的详细信息
 	//$is_return_array=1 返回一个地址的数组， $is_return_array = 0 ,返回地址字符串
 	function getAddressInfo($address_id , $is_return_array = 0){

		$field = 'ua.*,r1.region_name as country,r2.region_name as province,r3.region_name as city,r4.region_name as district';
		$join[] = 'LEFT JOIN zm_region AS r1 ON r1.region_id = ua.country_id';
		$join[] = 'LEFT JOIN zm_region AS r2 ON r2.region_id = ua.province_id';
		$join[] = 'LEFT JOIN zm_region AS r3 ON r3.region_id = ua.city_id';
		$join[] = 'LEFT JOIN zm_region AS r4 ON r4.region_id = ua.district_id';
		$data = $this->alias('ua')->where(' agent_id= '.C('agent_id').' and  ua.country_id != 0 AND ua.address_id = '.$address_id)->field($field)
			->join($join)->find();
		if(!$data){
			return '';
		}
			
		if($is_return_array == 0){
			$user_address = $data['country'].' '.$data['province'].' '.$data['city'].' '.$data['district'].' '.$data['address'].','.$data['code'].','.$data['name'].','.$data['phone'].','.$data['title'];
			return $user_address;
		}elseif($is_return_array == 1){
			$data['addressInfo'] = $data['country'].$data['province'].$data['city'].$data['district'].$data['address'];
			return $data;
		}elseif($is_return_array == 2){
			return $data['country'].$data['province'].$data['city'].$data['district'].$data['address'];
		}		
		return '';
 	}
 	//用在b2c模板中 和上面的函数有关系  ,分割地址
 	function fegeAddressInfo($addressInfo){
 		//$field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.'r2.region_name as province,r3.region_name as city,r4.region_name as district';
 		//$info                = explode(' ', $addressInfo);	
 		list($address['country'],$address['province'],$address['city'],$address['district'],$address['address'],$address['code'],$address['name'],$address['phone'],$address['title'])=split('[, ]',$addressInfo);
 		return $address;		
 	}

 	function getAddressList($uid=0){
 		$uid = intval($uid);
		if($uid < 1){
			return '';
		}

		$field = 'ua.*,r1.region_name as country_name,r2.region_name as province_name,r3.region_name as city_name,r4.region_name as district_name';
		$join[] = 'LEFT JOIN zm_region AS r1 ON r1.region_id = ua.country_id';
		$join[] = 'LEFT JOIN zm_region AS r2 ON r2.region_id = ua.province_id';
		$join[] = 'LEFT JOIN zm_region AS r3 ON r3.region_id = ua.city_id';
		$join[] = 'LEFT JOIN zm_region AS r4 ON r4.region_id = ua.district_id';
		$user_address = $this->alias('ua')->where(' ua.agent_id= '.C('agent_id').' and ua.country_id != 0 AND ua.uid = '.$uid)->field($field)->join($join)->order('address_id ASC')->select();
	 
	 	$hasDefault = 0;
	 	if($user_address){
	 		foreach($user_address as $key=>$val){
	 			if($val['is_default'] ==1){
	 				$hasDefault = 1;
	 			}
	 		}
	 	}
	 	if($hasDefault ==0){
	 		$data = $this->where("uid='".$uid."'")->order("address_id ASC")->find();
	 		if($data){
	 			$data['is_default'] = 1;
	 			$this->where(" agent_id= ".C('agent_id')." and  address_id=".$data['address_id']." AND uid='".$uid."'")->save($data);
	 		}
	 	}
	 	return $user_address ;
 	}
}






