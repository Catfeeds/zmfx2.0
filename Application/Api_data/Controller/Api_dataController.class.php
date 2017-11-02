<?php
/**
 * 后端公共类
 * 所有后端控制器类都会继承
 */
namespace Api_data\Controller;
use Think\Controller;
class Api_dataController extends Controller {
    protected $result = array("ret"=>100, "msg"=>"", "data"=>"");		//返回值定义
	public	  $is_auth= false;											//是否验证登录
    // 初始化方法
	protected function _initialize() {
        $this->checkAgent();
		$this->checkUserLogin();
	}

	/**
	 * 根据域名获取agent_id
     * @return void
	 */
	private function checkAgent(){
		$agent_id = I('get.agent_id',0,'intval');
		if($agent_id){
			C("agent_id",$agent_id);
			getAdvantage();
		}else{
			$this->result['ret'] = 101;
			$this->result['msg'] = '缺少关键参数(agent_id)';
			$this->ajaxReturn($this->result);
		}
		$this->getConfig();
	}

	/**
	 * auth	：fangkai
	 * content：检查用户登录状态
	 * time	：2016-11-9
	**/
	private function checkUserLogin(){
		$uid	= I('get.uid',0,'intval');
		$token	= I('get.token','','');
		if($uid && $token){
			$this->checkTokenUid($uid,$token);
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：检查用户token 和 uid
	 * time	：2016-1-12
	**/
	public function checkTokenUid($uid,$token){
		if($this->is_auth){
			return true;
		}
		$UM   			= M('user');
		$user_login_verify	= M('user_login_verify');

		$checkUser		= $UM->where(array('agent_id'=>C('agent_id'),'uid'=>$uid))->find();
		if(empty($checkUser)){
			$this->echo_data('299','该用户不存在');
		}
		$userLoginInfo		= $user_login_verify->where(array('uid'=>$uid,'agent_id'=>C('agent_id')))->find();
		$key				= 'szzm'.$userLoginInfo['agent_id'].$checkUser['uid'];
		if (password_verify($key,$userLoginInfo['token']) && $token == $userLoginInfo['token']) {
			$save['check_time']	= time();
			$save['last_activity_time']	= time();
			if($save['check_time'] >= ($userLoginInfo['check_time']+2*3600)){
				$this->echo_data('102','登录超时');
			}
			$this->is_auth	= true;
			C("uid",$uid);
			C("token",$token);
			C("user_is_validate",$checkUser['is_validate']);
			$action			= $user_login_verify->where(array('uid'=>$userLoginInfo['uid'],'agent_id'=>C('agent_id')))->save($save);
		} else {  
			$this->echo_data('298','登录失败或已在其他设备登录');
		}
		
	}

	/**
	 * 返回结果JSON
     * @return void
	 */
	public function echo_data($ret=100 , $msg='', $data=array()){
		$this->result['ret'] = $ret;
		$this->result['msg'] = $msg;
		$this->result['data'] = $data;
		$this->ajaxReturn($this->result);
	}
	
    /**
     * 把数据库的配置读到系统中
     * @return void
     */
    protected function getConfig(){
        //取出所有的项
        $data = M('config')->field('config_key,config_value')->select();
        $data1 = M('config_value')->where('agent_id='.C("agent_id"))->field('config_key,config_value')->select();
		
        //初始化默认值
        foreach ($data as $key => $value) {
            C($value['config_key'],$value['config_value']);
        }
		
        //注入设置的配置
        foreach ($data1 as $key => $value) {
            C($value['config_key'],$value['config_value']);
        }
		//分销商名称，PC端域名，移动端域名，分销等级，上级分销商
        $agentinfo = M('agent')->field('agent_name,domain,mobile_domain,level,parent_id')->find(C("agent_id"));
		$agentinfo['domain'] = 'api-view.btbzm.com';
        C("agent",$agentinfo);
		//注入钻明汇率
        if( ! C('dollar_huilv_type') ){
            $dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
            C('dollar_huilv',$dollar_huilv);
        }
    }
	
	
    /**
     * 购物车总数
     * zhy	find404@foxmail.com
     * 2017年2月23日 10:22:16
     */
    protected function GetCartGoodsNumber() {
		if(C("uid")>1){
			$CartGoodsNumber = M('cart')->where(array('uid'=>C("uid"),'agent_id'=>C('agent_id')))->sum('goods_number');
		}
		return isset($CartGoodsNumber) ? floor($CartGoodsNumber) : 0;
	}	

}