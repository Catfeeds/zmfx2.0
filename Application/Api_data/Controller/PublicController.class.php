<?php
namespace Api_data\Controller;
class PublicController extends Api_dataController{
      
    /**
	 * auth	：fangkai
	 * content：构造函数
	 * time	：2016-11-25
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid");
	}
	
	/**
	 * auth	：fangkai
	 * content：APP信息获取
		userlogincheck:是否登录才能查看产品 ：0 否，1：是
	 * time	：2016-11-25
	**/
    public function appInfo(){
		$gConfig	= M('config');
		$data	= array();
		$data['appname']		= C('SITE_NAME');
		$data['logo']			= 'http://'.C('AGENT')['domain'].'/Public/'.C('web_logo');
		$data['userlogincheck']	= C('user_login_show_product'); 
		$data['huilv']			= C('DOLLAR_HUILV');
		$data['site_contac']	= C('site_contact');
		//$data['version']		= C('version');
		$configStr = $gConfig->where("config_key='version' and config_type='12'")->field("config_value")->find();
		$data['version']		= $configStr["config_value"];
		$this->echo_data('100','获取成功',$data);
		
	}
	
	/**
	 * auth	：fangkai
	 * content：APP底部菜单栏图表
	 * time	：2017-2-10
	**/
    public function appSysImage(){
		$agent_id	= $this->agent_id;
		$appConfig	= M('app_config');
		$appConfig	= $appConfig->where(array('agent_id'=>$agent_id))->find();
		$data[0] = array(
			'ico_image_index'=>'http://'.C('agent')['domain'].'/Application/Admin/View/img/ico_image_index.png',
			'ico_image_category'=>'http://'.C('agent')['domain'].'/Application/Admin/View/img/ico_image_category.png',
			'ico_image_new'=>'http://'.C('agent')['domain'].'/Application/Admin/View/img/ico_image_new.png',
			'ico_image_cart'=>'http://'.C('agent')['domain'].'/Application/Admin/View/img/ico_image_cart.png',
			'ico_image_my'=>'http://'.C('agent')['domain'].'/Application/Admin/View/img/ico_image_my.png',
		);
		if($appConfig['ico_image_index']){
			$data[0]['ico_image_index']		= 'http://'.C('agent')['domain'].'/Public'.$appConfig['ico_image_index'];
		}
		if($appConfig['ico_image_category']){
			$data[0]['ico_image_category']	= 'http://'.C('agent')['domain'].'/Public'.$appConfig['ico_image_category'];
		}
		if($appConfig['ico_image_new']){
			$data[0]['ico_image_new']		= 'http://'.C('agent')['domain'].'/Public'.$appConfig['ico_image_new'];
		}
		if($appConfig['ico_image_cart']){
			$data[0]['ico_image_cart']		= 'http://'.C('agent')['domain'].'/Public'.$appConfig['ico_image_cart'];
		}
		if($appConfig['ico_image_my']){
			$data[0]['ico_image_my']		= 'http://'.C('agent')['domain'].'/Public'.$appConfig['ico_image_my'];
		}
		
		$this->echo_data('100','获取成功',$data);
	}
	
	/**
	 * auth	：fangkai
	 * content：APP导航栏和底部背景颜色配置
	 * time	：2017-2-10
	**/
    public function appSysStyle(){
		$agent_id	= $this->agent_id;
		$appConfig	= M('app_config');
		$appConfig	= $appConfig->field('nav_bgcolor,nav_colors,ico_chose,foot_bgcolor,foot_colors')->where(array('agent_id'=>$agent_id))->find();
		$data[0]	= $appConfig;
		
		$this->echo_data('100','获取成功',$data);
		
	}
	
}
