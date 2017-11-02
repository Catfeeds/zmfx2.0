<?php
namespace Home\Controller;
class IndexController extends HomeController {
	public function _before_index(){
    	$this->seo_title = "钻明官网";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active = "Index";
    }
	
    // 前端首页
    public function index(){
		if(C('new_rules')['zgoods_show']){
			$this->index_new();exit;
		}
    	if( $this -> uType > 1 ){
      		// 首页的产品
      		//$categoryId = M("goods_category")->where("category_name='钻石' AND parent_type=".$this->uType." AND parent_id=".$this->traderId)->getField('category_id');          
      		//$categoryId = 2228;
      		 /*
      		if($categoryId){
      			$Goods = new GoodsController();  
      			$categorys = $Goods->getProductChildrenCategorys($categoryId,array($categoryId));  
      			$products = getProductsByTrader($categorys,$this->traderId);   
      			$this->products = $products;    	
			}
			*/
			// 钻托
			/*
			$zuantuoId = M("goods_category")->where("category_name='钻托' AND parent_type=".$this->uType." AND parent_id=".$this->traderId)->getField('category_id');
      		if($zuantuoId){
      			$Goods = new GoodsController();
      			$zuantuoCategory = $Goods->getProductChildrenCategorys($zuantuoId,array($zuantuoId));
      			$zuantuo = getProductsByTrader($zuantuoCategory,$this->trader_id);   
      			$this->zuantuo = $zuantuo;    	
			}
			
      		//分销商下的钻石百科分类
      		$where = "1=1 AND is_show = 1 AND catname = '钻石百科'";
      		if($this->traderId){
      			$where .= " AND  belongs_id = ".$this->traderId;
			}
      		*/
      		
      		$articleCate = M("article_cat")->where($where)->find(); 
      		$this->articleCate = $articleCate;
      		if($articleCate){
      			$where2  = "parent_id=".$articleCate['cid'];   
      			$where2 .= " AND agent_id=".C('agent_id');
      			$baikeCates = M("article_cat")->where($where2)->order('sort ASC')->select();
      			$this->baikeCates = $baikeCates;
      			$cates = array();
      			if($baikeCates){
      				foreach($baikeCates as $key=>$val){
      					$cates[] = $val['cid'];
      				}
      				$cates = implode(',',$cates);
      				$where = " is_show = 1 AND agent_id=".C('agent_id')." AND cat_id IN(".$cates.")";
      				$thumb_articles = M('article')->where($where." AND thumb != ''")->limit(2)->select();
      				$articles = M('article')->where($where." AND thumb = ''")->limit(8)->select();
      				$this->articles = $articles;
      				$this->thumb_articles = $thumb_articles;
      			}
      		}                               
		} else {
			// 首页的产品
      		//$categoryId = M("goods_category")->where("category_name='彩钻' AND parent_type=".$this->uType." AND parent_id=".$this->traderId)->getField('category_id');           
      		//$categoryId = 2228;
      		/*
      		if($categoryId){
      			$Goods = new GoodsController();  
      			$categorys = $Goods->getProductChildrenCategorys($categoryId,array($categoryId));  
      			$products = $Goods->getProductsByTraderFirst($categorys);   
      			$this->products = $products;    	
			}
			*/
		}
      	/* 首页的轮播广告,钻石定制广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 3;
		$CarouselAdList		= $TA->where($where)->order('sort ASC')->select();
		$where['ads_id']	= 43;
		$CustomAdList		= $TA->where($where)->order('sort ASC')->select();
      	$this->seo_title = "钻明官网首页";
    	$this->seo_keywords = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->navActive = "index";
		$this->CarouselAdList = $CarouselAdList;
		$this->CustomAdList   = $CustomAdList;
    	if(I('cookie.autologin')==1){
    		$login = new \Home\Controller\PublicController();
    		$login->loginByCookie();
			exit;
    	}
		$this->agent_id = C('agent_id');
    	$this->display();
    }

	protected function index_new(){
		$uid = intval(session('web.uid'));
		if($uid>0){
			$this->redirect('Home/Goods/goodsCat');
			/*$GC = D('Common/BanfangUrl');
			$cate_lists = $GC->getTreeLists(array('na_status'=>1));
			$this->banfang_jewelry = I('attr_id');


			$left_cates = array_values($cate_lists);
			$this->left_cates = $left_cates[0]['sub'];
			$right_attrs = $GC->setRightPartConditions();
			$this->right_attrs = $right_attrs;
			//$this->search_location = 1;
			$this->display('dingzhi/zhouliufu/Index/index');*/
		}else{
			$TA = M ('template_ad');
			$where['agent_id'] 	= C('agent_id');
			$where['status']	= 1;
			$where['ads_id']	= 4;
			$LoginAdList		= $TA->where($where)->order('sort ASC')->select();
			$this->LoginAdList	= $LoginAdList;
			$this->display('dingzhi/zhouliufu/Index/login');
		}
	}


    // 把查询结果集转为字符串
    protected function arrryToString($result, $separator=',') {
      foreach ($result as $key => $v) {
        $ids .= $v['ads_id'].$separator;
      }
      return substr($ids, 0, strlen($ids) -1);
    }
    public function cat(){

    }
}