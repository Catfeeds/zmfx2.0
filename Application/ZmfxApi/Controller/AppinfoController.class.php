<?php
/**
	APP信息模块
**/

namespace Mobile\Controller;
class AppinfoController extends MobileController{
    
	/**
     * APP信息
     */
    public function index($catId=0,$p=1,$n=30){
    }
    
    /**
     * APP配置信息
     */
    public function config(){
		$data = array(
			'appname' => C("site_name"),
			'logo' => "http://www." . C("agent.domain")."/Public/".C("web_logo"),
			'userlogincheck' => C("user_login_show_product")
		);
		$this->r(100, '', $data);
    }

    /**
     * 时间校验
     */
    public function timecheck(){
		$timeflag = I('timeflag',0,'intval');
		$curtime = time();
		$data = array(
			'servertime' => $curtime,
			'timeflag'   => $timeflag,
			'timediff'   => $curtime - $timeflag
		);
		$this->r(100, '', $data);
    }
}