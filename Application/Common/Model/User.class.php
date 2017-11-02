<?php
	/**
	* 向用户发送消息 以后可以扩展用户其他功能
	* User: YangChao
	* Date: 2016/7/19
	**/

namespace Common\Model;

class User {

	//构造函数
	public function __construct()
	{
		
	}

	/**
	 * 发送网站消息
	 * @param  integer	$uid     客户ID
	 * @param  string	$content  内容
	 * @param  string	$title  标题,允许空
	 * @param  int		$msg_type  消息类型默认给客户发送
	 * @return integer/boolean          信息ID或false
	 */
	public function sendMsg($uid, $content, $title='', $msg_type=1){
		$parment = array();
		$parment['msg_type']	= $msg_type; // 系统发送给用户
		$parment['uid']			= $uid; // 发送消息的用户ID
		$parment['content']		= $content;
		$parment['title']		= $title;
		$parment['create_time'] = time();
		$parment['is_show']		= 0;
		$parment['agent_id']	= C('agent_id');
		$result = M('user_msg')->data($parment)->add();
		if ($result) {
			return $result;
		} else {
			return false;
		}
	}


	/**
	 * 发送用户注册消息
	 * @param  array	$data     客户信息
	 */
	public function sendRegMsg($data){
		if(count($data)<=0){
			$this->error = "传入参数不正确";
			return false;
		}

		if(!isset($data['uid']) || $data['uid']==0){
			$this->error = "用户ID不能为空";
			return false;
		}

		if(!isset($data['username']) || $data['username']==''){
			$this->error = "用户帐号不能为空";
			return false;
		}

		$title = "注册成功";
		$content = "尊敬的用户您已注册成功，用户名：".$data['username'];
		if(isset($data['phone']) && $data['phone']!=''){
			$content .= " 电话 " . $data['phone'];
		}
		if(isset($data['email']) && $data['email']!=''){
			$content .= " 邮箱 " . $data['email'];
		}

		return $this->sendMsg($data['uid'],$content,$title);
	}
}
?>