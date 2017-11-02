<?php
namespace Mobile\Controller;
use Think\Controller;
class MobileController extends Controller{
    
	protected $data = array("ret"=>100, "msg"=>"", "data"=>"");		//返回值定义
    /**
     * 初始化方法
     */
    protected function  _initialize(){
		$this->checkAgent();
    }
	
	/**
	 * 根据域名获取agent_id
     * @return void
	 */
	private function checkAgent(){
		$agent_id = I('agent_id',0,'intval');
		if($agent_id){
			C("agent_id",$agent_id);
			$this->data        = getAdvantage();
		}else{
			$this->data['ret'] = 101;
			$this->data['msg'] = '缺少关键参数(agent_id)';
			$this->ajaxReturn($this->data);
		}
		$this->getConfig($agent_id);
	}

	/**
	 * 返回结果JSON
     * @return void
	 */
	public function r($ret=100 , $msg='', $data=array()){
		$this->data['ret'] = $ret;
		$this->data['msg'] = $msg;
		$this->data['data'] = $data;
		$this->ajaxReturn($this->data);
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
        C("agent",$agentinfo);
    }

}