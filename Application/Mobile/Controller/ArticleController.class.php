<?php
namespace Mobile\Controller;
class ArticleController extends MobileController{
    /**
     * 活动首页
     */
    public function index($catId=0,$p=1,$n=30){
        //$catId = M("article_cat")->where("catname like '%动态%' and agent_id = ".C('agent_id'))->getField("cid");
        $catId = 0;
        $this->list = getArticle($this->domain,$catId,$p,$n);
        $this->display();
    }
    
    /**
     * 文章详情页面
     * @param int $aid
     */
    public function articleInfo($aid=0){
        $this->info = getArticleInfo($aid);
        $this->display();
    }
}