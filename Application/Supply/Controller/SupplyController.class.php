<?php
/**
	基类，信息初始化，以及一些系统必须操作
**/

namespace Supply\Controller;
use Think\Controller;
class SupplyController extends Controller{

	protected $L;

	public function __construct() {
		parent::__construct();
		$this->loadLang();//加载多语言
		$this->loadTemplate();
		$this->uid = $_SESSION['supply']['uid'];
		$lang = LANG_SET;
		if(empty($lang)){
			$lang = I('get.lang','zh-cn');
		}
		switch($lang){
			case 'zh-cn':
				$this -> nowlang = '中文';
				break;
			case 'zh-tw':
				$this -> nowlang = '繁體';
				break;
			case 'en-us':
				$this -> nowlang = 'English';
				break;
		}
		if($this->uid){
			$this->agent_id = D('SupplyAccount')->getAgentId();
		}
    }

	public function go(){
		if( $this->checkUser() == false ) {
			//进入登录
			//$this->redirect('Index/login');
			echo "<script>top.location.href='/Index/login'</script>";die;
		}
	}

	public function logout(){
		unset($_SESSION['supply']);
	}

	//AJAX验证码验证
	public function checkVerify($code){
		$verify = new \Think\Verify();
		return $verify->check($code);
	}

	//data 表示数据，ret:100表示成功，非100表示失败，msg:用数组表示详情
	public function echoJson( $_data = array() , $ret=100 , $msg=''){
		if($ret==100){
			$data['ret']   = 100;
			if($msg){
				$data['msg']   = $msg;
			}else{
				$data['msg']   = L('read_success');
			}
			$data['data']  = $_data;
		}else{
			$data['ret']  = $ret;
			$data['msg']  = $msg;
			$data['data'] = new \stdClass();//空对象
		}
		echo json_encode($data);die;
	}

	protected function checkUser(){
		$uid                = $_SESSION['supply']['uid'];
		if( !empty($uid) ){
			$this -> supply = $_SESSION['supply'];
			return true;
		}else{
			return false;
		}
	}
	protected function getModelInfo(){
		//后面改用数据库获取
		$model = array(
			'Index' => array(
				'index' => 'Index:index'
			),
			'Product' => array(
				'index'  => 'Product:index',
				'upload' => 'Product:upload'
			),
			'Order' => array(
				'index'     		=> 'Order:orderList',
				'orderList' 		=> 'Order:orderList',
				'orderInfo' 		=> 'Order:orderInfo'
			),
			'Setting' => array(
				'index' => 'Setting:index'
			),
			'Finance' => array(
				'index'       => 'Finance:index',
				'financeInfo' => 'Finance:financeInfo'
			),
		);
		return $model;
	}

	//返回路由信息，array(Model,view)
	protected function getRoute(){
		$model  = $this -> getModelInfo();                 //模型信息
		$mo     = I('get.mo','Index','htmlspecialchars');  //业务模型
		$ve     = I('get.vi','index','htmlspecialchars');  //功能视图
		if(array_key_exists($mo,$model)){
			if(isset($model[$mo][$ve])){
				return explode(':',$model[$mo][$ve]);
			}
		}
	}

	//加载多语言,默认当前用户浏览器给定
	protected function loadLang($lang=LANG_SET){

		$lang_path = MODULE_PATH.'Lang/'.$lang;
		if(file_exists($lang_path.'/default.php')){
			$lang_array = include_once($lang_path.'/default.php');
			if(!is_array($lang_array)){
				$this->error('language file is error !');
			}
		}else{
			$this->error('language file is error !');
		}
		list($mo,$vi) = $this->getRoute();

		if(file_exists($lang_path."/$mo/$vi.php")){
			$view_lang_array = include_once($lang_path."/$mo/$vi.php");					
			if( !empty($view_lang_array) && !is_array($view_lang_array)){
				$this->error($vi.' language file is error !');
			}

		}
		if( isset($view_lang_array) && is_array($view_lang_array) ){
			$lang_array = array_merge($lang_array,$view_lang_array);
		}
		$this -> language_code = $lang;        //提供控制器使用
		$this -> L             = $lang_array;  //提供控制器使用		
		array_walk($lang_array,function($value,$key){
			L($key,$value);
		});
		$this -> assign('L',$lang_array);      //提供模板使用
		return true;
	}
	public function LoadTemplate(){
		$this->Template_Path = 'Manage:'.C('DEFAULT_TEMPLATE').':';
	}
}