<?php
/**
 * auth	：fangkai
 * content：用户留言，系统消息等数据操作
 * time	：2016-5-3
**/
namespace Supply\Model;
use Think\Model;
class SupplyMessageModel extends Model{
	/**
	 * auth	：fangkai
	 * content：保存用户提交的反馈
	 * time	：2016-5-3
	**/
	public function addopinion($info,$uid){
		if(empty($uid) || empty($info)){
			return false;
		}
		$info['type'] = 1;
		$info['uid']  = $uid;
		$info['createtime'] = date('Y-m-d H:i:s',time());
		if($this->create($info)){
			$action = $this->add($info);
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
	 * content：查出系统消息列表
				page:当前页数，size：每页个数，type：0为系统消息，1为用户反馈的消息，status：0未阅读，1已阅读
	 * time	：2016-5-3
	**/
	public function messagelist($uid,$page=1,$size=20,$type=0,$order='id desc',$status=array('0','1')){
		if(empty($uid)){
			return false;
		}
		$where['uid']  = $uid;
		$where['type'] = $type;
		$where['status'] = array('in',$status);
		$total = $this->where($where)->count();
		$limit = (($page-1)*$size).','.$size;
		$list = $this->where($where)->order($order)->limit($limit)->select();
		if($list){
			$data['list']  = $list;
			$data['total'] = $total;
			$data['page']  = $page;
			$data['size']  = $size;
			return $data;
		}else{
			return false;
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：改变系统消息的状态
				status:0,未读，1，已读；type:0,系统消息，1用户反馈的消息
	 * time	：2016-5-4
	**/
    public function updatestatus($id,$uid,$status=0,$type=0){
		if(empty($uid) || empty($id)){
			return false;
		}
		$where['id']	= $id;
		$where['uid']	= $uid;
		$where['type']	= $type;
		$where['status']= $status;
		$checkmessage = $this->where($where)->find();
		if($checkmessage){
			$save['status'] = 1;
			$action = $this->where($where)->save($save);
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
	 * content：删除消息
				type:0,系统消息，1用户反馈的消息
	 * time	：2016-5-4
	**/
    public function deletemessage($id,$uid,$type=0){
		if(empty($uid) || empty($id)){
			return false;
		}
		$where['id']	= $id;
		$where['uid']	= $uid;
		$where['type']	= $type;
		$checkmessage = $this->where($where)->find();
		if($checkmessage){
			$action = $this->where($where)->delete();
			if($action){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	
	
	
}

?>