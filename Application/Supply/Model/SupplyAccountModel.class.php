<?php
namespace Supply\Model;
use Think\Model;
class SupplyAccountModel extends Model{

    protected $patchValidate = true;
    // 数据验证
    public $_validate	=	array(
        array('corp_name','require','{%error_company_name_must}'),
        array('legal_representative','require','{%error_legal_name_must}'),
        array('business_contact_telphone','require','{%error_phone_must}'),
        array('id_number','require','{%error_id_number_must}'),
        array('business_liaisons','require','{%error_business_liaisons_must}'),
		array('business_contact_telphone','require','{%error_tel_must}'),
		//array('business_contact_telphone','/^(13[0-9]|15[0-9]|17[678]|18[0-9]|14[57])[0-9]{8}$/','联系电话格式错误',1,'regex'),
		array('corp_email','require','{%error_email_must}'),
		//array('corp_email','email','邮箱格式错误'),
		//array('corp_telphone','/^([0-9]{3,4}-)?[0-9]{7,8}$/','办公电话格式不正确',2,'regex'),
		//array('corp_fax','/^(\d{3,4}-)?\d{7,8}$/','传真格式不正确',2,'regex'),
		array('corp_address','require','{%error_addr_must}'),
		array('license_imgs','strlen','{%error_imgs_must}',1,'function'),
		array('registration_imgs','strlen','{%error_imgs_must}',1,'function'),
		array('organization_imgs','strlen','{%error_imgs_must}',1,'function'),
    );

    // 写入默认数据
    public $_auto		=	array(
        array('uid','getUid',self::MODEL_INSERT,'callback'),
        array('business_type','getBusinessType',self::MODEL_INSERT,'callback'),
    );

    public function getBusinessType(){
        $data = I('business_type');
        return implode(',',$data);
    }

    public function getUid(){
		$uid = $_SESSION['supply']['uid'];
		if(!$uid){
			return '0';
		}
        return $uid;
    }
	public function getAgentId(){
		if(empty($_SESSION['supply']['uid'])){
			return '0';
		}else{
			if(!$_SESSION['supply']['agent_id_build']){
				$_SESSION['supply']['agent_id_build'] = $this->getAccountField('agent_id_build',$_SESSION['supply']['uid']);
			}
		}
		return $_SESSION['supply']['agent_id_build'];
	}
    public function getSupplyStatus(){
		$_SESSION['supply']['supply_status'] = $this->getAccountField('supply_status',$_SESSION['supply']['uid']);
		return $_SESSION['supply']['supply_status'];
    }

    public function getCurSupplyAccount($uid=0){
		if(empty($uid)){
			$uid = $_SESSION['supply']['uid'];
		}
		if(!$uid){
			return '0';
		}
		return $this->where(' uid = '.$uid )->find();
    }
    public function getAccountField($key,$uid = 0){
        if(empty($uid) && !$_SESSION['supply']['uid']){
            $uid = $_SESSION['supply']['uid'];
        }
		if(!$uid){
			return '0';
		}
		$field = $this->where(' uid = '.$uid )->getField($key);
		if($field){
			return $field;
		}else{
			return '0';
		}
    }
	/**
	 * auth	：fangkai
	 * content：用户资料修改
	 * time	：2016-4-29
	**/
	public function updateAccount($info){
		if(!$info){
			return false;
		}
		if($this->create($info)){
			if(empty($uid)){
				$uid = $_SESSION['supply']['uid'];
			}
			$check = $this->where(array('uid'=>$uid))->find();
			if($check['corp_email'] != $info['corp_email']){
				$info['is_email'] = 0;
			}
			$action = $this->where(array('uid'=>$uid))->save($info);
			if($action){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	/**
	 * auth	：fangkai
	 * content：用户邮箱绑定
	 * time	：2016-4-29
	**/
	public function updateemail($uid){
		if(empty($uid)){
			return false;
		}
		$info['is_email'] = 1;
		$action = $this->where(array('uid'=>$uid))->save($info);
		if($action){
			return true;
		}else{
			return false;
		}
	}
	
}