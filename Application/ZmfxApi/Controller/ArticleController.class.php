<?php
namespace Mobile\Controller;
class ArticleController extends MobileController{
    /**
     * 文章接口首页
     */
    public function index(){
    }
    
    /**
     * 获取文章分类列表
     */
    public function cat(){
		$Category = M('article_cat');
		$where = "agent_id=".C("agent_id");
		$categorys = $Category->where($where)->order('sort')->select();
		$cates = $this->_arrayRecursive($categorys,'cid','parent_id',0);
		$this->r(100, '', $cates);
    }

    /**
     * 获取文章列表
     */
    public function getlist(){
		$uid	= I('uid', 0, 'intval');			//用户ID
		$start	= I('start', 0, 'intval');			//起始值
		$per	= I('per', 20, 'intval');			//取条数
			
		$start	= $start<=0?1:$start;
		$per	= $per<=0? 20 :$per;

 		$Article = M("article");
    	$where = " agent_id= ".C("agent_id");
    	if(I('title')){
    		$where .= " AND title like '%".$_GET['title']."%'";
    	}
    	if(I('cat_id')){
    		$where .= " AND cat_id = ".I('cat_id');
    	}
		$categorys = M('article_cat')->where($this->buildWhere(''))->order('sort')->select();
    	$count = $Article->where($where)->count();
        $Page = new \Think\Page($count,$n);
        $articleList = $Article->where($where)->limit($start * $per,$per)->order('addtime DESC')->select();


		$this->r(100, '', $data);
    }

    /**
     * 文章详情页面
     * @param int $aid
     */
    public function view($aid=0){
        $this->info = getArticleInfo($aid);
        $this->display();
    }


}