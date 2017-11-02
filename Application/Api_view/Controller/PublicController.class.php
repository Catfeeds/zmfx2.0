<?php
namespace Api_view\Controller;
class PublicController extends Api_viewController{
    
    /**
     * 用户登录
     */
    public function login(){ 
        $this -> error( "访问失败，需要登陆！",'错误');
    }
    
    /**
     * 用户注册
     */
    public function register(){
        $this -> error( "访问失败，需要登陆！",'错误');
    }
    /**
     * 退出功能
     */
    public function logout(){
        $this -> error( "访问失败，需要登陆！",'错误');
    }

    /**
     * ajax根据上级ID获取地址，2省级3市区级4区县级
     * @param int $parent_id
     */
    public function getRegion($parent_id){
        $R = M('region');
        $data = $R->where('parent_id = '.I('get.parent_id'))->select();
        $this->ajaxReturn($data);
    }
    /**
     * 错误
     * @param string $msg
     */
    public function footer(){
		$this->display();
    }
	
	/**
     *  退出登录
     *  zhy	find404@foxmail.com
     *  2016年12月2日 16:11:10
     */
	public function login_out(){
		unset($_SESSION['app']);
		C('uid','');
	}
	
	/**
     *  退出登录
     *  zhy	find404@foxmail.com
     *  2017年2月15日 17:29:32
     */
	public function GetSessionId(){
        $this->ajaxReturn($_COOKIE['PHPSESSID']);
	}
}