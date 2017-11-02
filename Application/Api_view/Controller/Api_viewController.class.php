<?php
/**
 * 后端公共类
 * 所有后端控制器类都会继承
 */
namespace Api_view\Controller;
use Think\Controller;
class Api_viewController extends Controller {


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
		$agent_id        = I('agent_id',0,'intval');
		$cookie_agent_id = cookie('agent_id');
		if($agent_id){
			C("agent_id",$agent_id);
			cookie('agent_id', $agent_id); 
		} else {
            if ($cookie_agent_id){
			    C("agent_id",$cookie_agent_id);
		    } else {
		    	$this->error('缺少关键参数(agent_id)');
		    }
        }
		$this -> getConfig($agent_id);
	}
	
	
 
	
	
     // 保存用户信息到session
    public function checkUser(){
		$AndroidSession 			= I('AndroidSession'); 
        $uid                      	= I('uid',0,'intval');
        $token                      = I('token');		
        if( $uid > 0 ){
            $UM           = M('user');
            $where['uid'] = $uid;                  
            $uInfo        = $UM -> where($where) -> find();
			if(empty($uInfo)){
				$this->error('该用户不存在！');
			}
			$user_login_verify	= M('user_login_verify');
			$userLoginInfo		= $user_login_verify->where(array('uid'=>$uid,'agent_id'=>C('agent_id')))->find();
			$key				= 'szzm'.$userLoginInfo['agent_id'].$uInfo['uid'];
			if(password_verify($key,$userLoginInfo['token']) && $token == $userLoginInfo['token']){		
			// if(substr($token,0,15)==substr($userLoginInfo['token'],0,15)){		
				$save['check_time']	= time();
				$save['last_activity_time']	= time();
				if($save['check_time'] >= ($userLoginInfo['check_time']+2*60*3600)){
					$this->error('登录超时！');
				}
				if($AndroidSession){
						$this->AndroidSession_once = $AndroidSession;
				}				
				
                $this->saveSession($uInfo);
                $this->updateUserSession($uid);

                $this->userAgent      = "uid='".$uid."'";
                $this->userAgentKey   = "uid";
                $this->userAgentValue = $uid;
				C('uid',$uid);
				C('token',$token);
				$user_login_verify->where(array('uid'=>$userLoginInfo['uid'],'agent_id'=>C('agent_id')))->save($save);
            }else{
				//$this->error('登录错误！');
				if($AndroidSession){
						$this->userAgent      = "session='".$AndroidSession."'";
						$this->userAgentKey   = "session";
						$this->userAgentValue = $AndroidSession;
						C('AndroidSession',$AndroidSession);
					/*
					$AesCode 					= new \Think\AesCode();	
					$AndroidSession				= $AesCode::decrypt($AndroidSession);
					if(ctype_alnum($AndroidSession) && strlen($AndroidSession)>15){
						$this->userAgent      = "session='".$AndroidSession."'";
						$this->userAgentKey   = "session";
						$this->userAgentValue = $AndroidSession;
					}else{
						$this->userAgent      = "session='".$_COOKIE['PHPSESSID']."'";
						$this->userAgentKey   = "session";
						$this->userAgentValue = $_COOKIE['PHPSESSID'];	
					}
					*/
					
					
					
				}else{
					$this->userAgent      = "session='".$_COOKIE['PHPSESSID']."'";
					$this->userAgentKey   = "session";
					$this->userAgentValue = $_COOKIE['PHPSESSID'];	
				}
	
				if(!strstr($_SERVER['REQUEST_URI'],"/Cart/index")){
					echo '<!doctype html>    <meta http-equiv="content-type" content="text/html;charset=utf-8"> <meta http-equiv="X-UA-Compatible" content="IE=Edge"><head>	<meta charset="UTF-8">	<title>298</title>	';
					echo '<script type="text/javascript">'."\n";
					echo ' window.demo.demoTest(\'298\'); '."\n";
					echo '</script>'."\n";
					echo ' </head><body></body></html>';
					exit();
				}
            }
        } else {
				if($AndroidSession){
					$this->userAgent      = "session='".$AndroidSession."'";
					$this->userAgentKey   = "session";
					$this->userAgentValue = $AndroidSession;
					C('AndroidSession',$AndroidSession);
				}else{
					$this->userAgent      = "session='".$_COOKIE['PHPSESSID']."'";
					$this->userAgentKey   = "session";
					$this->userAgentValue = $_COOKIE['PHPSESSID'];	
				}
            // if( empty($_SESSION['app']) ){				
                // $this->userAgent      = "session='".$_COOKIE['PHPSESSID']."'";
                // $this->userAgentKey   = "session";
                // $this->userAgentValue = $_COOKIE['PHPSESSID'];
            // } else{			
                // $this->userAgent      = "uid='".$_SESSION['app']['uid']."'";
                // $this->userAgentKey   = "uid";
                // $this->userAgentValue = $_SESSION['app']['uid'];
            // }
        }
    }

    /**
     * 保存用户信息到SESSION
     * @param array $uInfo
     */
    private function saveSession($uInfo) {
        session('app.uid', $uInfo['uid']);
        session('app.username', $uInfo['username']);
        session('app.realname', $uInfo['realname']);
        session('app.phone', $uInfo['phone']);
        session('app.email', $uInfo['email']);
        session('app.rank_id', $uInfo['rank_id']);
        session('app.reg_time', $uInfo['reg_time']);
        session('app.last_logintime', $uInfo['last_logintime']);
        session('app.last_ip', $uInfo['last_ip']);  
        //session('app.is_validate', $uInfo['is_validate']);
		session('app.luozuan_discount', $uInfo['luozuan_discount']);
		session('app.sanhuo_discount', $uInfo['sanhuo_discount']);
		session('app.goods_discount', $uInfo['goods_discount']);
		session('app.consignment_discount', $uInfo['consignment_discount']);
		session('app_val.is_validate', $uInfo['is_validate']);
    }

    /**
     * 更新用户SESSION
     * @param int $userId
     */
    private function updateUserSession($userId){
		if($this->AndroidSession_once){
			$where = "session='".$this->AndroidSession_once."' AND agent_id='".C('agent_id')."' AND uid=0";			
		}else{
			$where = "session='".$_COOKIE['PHPSESSID']."' AND agent_id='".C('agent_id')."' AND uid=0";
		}
        $cartGoods = M('cart')->where($where)->select();
        if($cartGoods){
            foreach($cartGoods as $key=>$val){
                $cartGoods[$key]['uid'] = $userId;
				$cartGoods[$key]['session'] = '';				
                M('cart')->data($cartGoods[$key])->save();
            }
        }
		
        // $historys = M("history")->where($where)->select();
        // if($historys){
            // foreach($historys as $key=>$val){
                // $historys[$key]['uid'] = $userId;
                // M("history")->data($historys[$key])->save();
            // }
        // }
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
        $C     = M('config');
        $data  = $C->field('config_key,config_value')->select();
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
    /**
     * 购物车总数
     * zhy	find404@foxmail.com
     * 2017年2月23日 10:22:16
     */
    protected function GetCartGoodsNumber() {
		$CartGoodsNumber = M('cart')->where(array($this->userAgentKey=>$this->userAgentValue,'agent_id'=>C('agent_id')))->sum('goods_number');
		return isset($CartGoodsNumber) ? floor($CartGoodsNumber) : 0;
	}
	

	

}