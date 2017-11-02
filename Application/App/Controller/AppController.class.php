<?php
namespace App\Controller;
use Think\Controller;
class AppController extends Controller{
    
    /**
     * 初始化方法
     */
    protected function  _initialize(){
        //ob_end_clean();
		$this->checkAgent();
		$this->checkUser();
    }
	
	/**
	 * 根据agent_id参数获取分销商配置信息
     * @return void
	 */
	private function checkAgent(){
		$agent_id = I('agent_id',0,'intval');
		$cookie_agent_id = cookie('agent_id');
		if($agent_id){
			C("agent_id",$agent_id);
			cookie('agent_id', $agent_id); 
		}elseif ($cookie_agent_id){
			C("agent_id",$cookie_agent_id);
		}else{
			$this->error('缺少关键参数(agent_id)');
		}
		$this->getConfig($agent_id);
	}
	
     // 保存用户信息到session
    public function checkUser(){

    	if($_SESSION['app']['uid']){
    		$this->userAgent = "uid='".$_SESSION['app']['uid']."'";
    		$this->userAgentKey = "uid";
    		$this->userAgentValue = $_SESSION['app']['uid'];
    	}else{
    		$this->userAgent = "session='".$_COOKIE['PHPSESSID']."'";
    		$this->userAgentKey = "session";
    		$this->userAgentValue = $_COOKIE['PHPSESSID'];
    	}
		if(C('user_login_show_product')=='1' && $_SESSION['app']['uid']==''){
			if(CONTROLLER_NAME == 'Goods' || CONTROLLER_NAME == 'LuoZuan'){
				$this->redirect('/User/');
			}
        }
		
		if(C('user_login_show_product')=='1' && $_SESSION['app']['uid'] && $_SESSION['app_val']['is_validate']=='0'){
			if(CONTROLLER_NAME == 'Goods' || CONTROLLER_NAME == 'LuoZuan'){
				$this->redirect('/User/');
			}
        }
		
    }
    
     //通用错误页面输出
    public function error($msg='', $tilte=''){
		$msg = $msg==''?"请求出现了一个错误":$msg;
		$tilte = $tilte==''?"访问错误":$tilte;
		$this->error_msg = $msg;
		$this->error_tilte = $tilte;
		$this->display('Public/error');
		exit;
    }



    /**
     * 把数据库的配置读到系统中
     * @return void
     */
    protected function getConfig(){
        $C    = M('config');
        $data = $C->field('config_key,config_value')->select();
        $data1 = M('config_value') -> where('agent_id = '.C("agent_id"))->field('config_key,config_value')->select();
        //初始化默认值
        foreach ($data as $key => $value) {
            C($value['config_key'],$value['config_value']);
        }
        //注入设置的配置
        foreach ($data1 as $key => $value) {
            C($value['config_key'],$value['config_value']);
        }
		//设置热搜关键词
		$hot_search_val = C('hot_search');
        if( isset($hot_search_val) && !empty($hot_search_val) ){
        	$hot_search = explode(',',$hot_search_val);
        	$this->hot_search  = $hot_search;
        }
        //注入钻明汇率
        if( ! C('dollar_huilv_type') ){
            $dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
            C('dollar_huilv',$dollar_huilv);
        }
    }
	
	public function paramStr($param){   //mysql in 查询字符串处理
        $arr = explode(',',$param);
        $str = '';
        $count = count($arr);
        for($i=0 ; $i<$count ; $i++){
            if($i==0){
                $str  = "'".$arr[$i]."'";
            }else{
                $str .= ",'".$arr[$i]."'";
            }
        }
        return $str;
    }
	
	/**
     * 获得用户的真实IP地址
     * @access  public
     * @return  string
     */
    protected function real_ip() {
      static $realip = NULL;
      if ($realip !== NULL) { return $realip; }
      if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
          /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
          foreach ($arr AS $ip) {
            $ip = trim($ip);
            if ($ip != 'unknown') {
              $realip = $ip;
              break;
            }
          }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
          $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
          if (isset($_SERVER['REMOTE_ADDR']))
          {
              $realip = $_SERVER['REMOTE_ADDR'];
          }
          else
          {
              $realip = '0.0.0.0';
          }
        }
      } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
           $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
          $realip = getenv('HTTP_CLIENT_IP');
        } else {
          $realip = getenv('REMOTE_ADDR');
        }
      }
      preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
      $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
      return $realip;
    }
	
	
	
}