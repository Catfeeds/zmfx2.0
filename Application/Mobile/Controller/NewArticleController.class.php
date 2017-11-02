<?php
/**
 * 文章部分
 * zhy	find404@foxmail.com
 * 2017年9月5日 10:54:42
 */
namespace Mobile\Controller;
class NewArticleController extends NewMobileController{
 
     /**
     * 列表
     * zhy	find404@foxmail.com
     * 2017年9月5日 11:12:16
     */
	public function index(){
			$Article		= new \Common\Model\Article();
		if($_POST){
			$data		 	= [];
			$data['iden'] 	= 100;
			$limit			= $_POST['page'].','.$_POST['page_len'];
			$data['data'] 	= $Article->getCatgoryArticleList($_POST['cid'],$limit);
			$this->ajaxReturn($data);
		}else{
			$this->publicdataList = $Article->getCategoryList();
			$this->display();
		}
	}
 
	/**
     * 详情
     * zhy	find404@foxmail.com
     * 2017年9月5日 10:54:18
     */
	public function info(){
		if(is_numeric($_GET['aid'])){
			$this ->info = getArticleInfo($_GET['aid']);
		}else{
			$this ->info = M('help')->where(['agent_id' => C('agent_id'),'title'   => '售后保障'])->find();
		}
		$this ->display();
	}
}