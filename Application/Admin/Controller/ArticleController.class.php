<?php
/**
 * 文章模块
 */
namespace Admin\Controller;
use Think\Page;
class ArticleController extends AdminController {

    // 模块跳转
    public function index(){
        foreach ($this->AuthListS AS $k){
            $val = stristr($k,strtolower(MODULE_NAME.'/'.CONTROLLER_NAME));
            if($val and strtolower($val) != strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME)){
               $this->redirect($val);
            }
        }
        $this->log('error', 'A1','',U('Admin/Public/login'));
    }   

    /**
	 * auth	：fangkai
	 * content：文章列表
	 * updatetime	：2016-10-5
	**/
    public function articleList($p=1,$n=13){
		$p					= I('get.p',1,'intval');
		$title				= I('get.title');
		$cat_id				= I('get.cat_id');
    	if($title){
    		$where['zm_article.title']	= array('like','%'.$title.'%');
    	}
    	if($cat_id){
    		$where['zm_article.cat_id']	= $cat_id;
    	}
		$Article			= new \Common\Model\Article();
		$categorys			= $Article->getCategoryList(false);			//获取分类
		
		$count				= $Article->getArticleCount($where,false);
		$Page				= new \Think\Page($count,20);
		$limit				= $Page->firstRow.','.$Page->listRows;
		$articleList = $Article->getArticleList($where,'',$limit,false);	//获取带分页的文章列表
		
    	$this->articleList	= $articleList;
    	$this->page			= $Page->show();
		
    	$this->categorys	= $categorys;
    	$this->title		= $title;
		$this->cat_id		= $cat_id;
		$this->site_name	= C('site_name');
        $this->display();
    }


    /**
	 * auth	：fangkai
	 * content：文章删除
	 * updatetime	：2016-10-6
	**/
    public function articleDelete(){
		$aid = I('get.aid','','intval');
		$Article = new \Common\Model\Article();
		$result		= $Article->articleDelete($aid);
		if($result){
			$this->success('删除文章成功');
		}else{
			$this->error($Article->error);
		}
    }

     /**
	 * auth	：fangkai
	 * content：文章详情，修改，添加
	 * updatetime	：2016-10-6
	**/
    public function articleInfo(){
    	$aid = I('get.aid','','intval');
		$Article			= new \Common\Model\Article();
		if($_POST){
			$data = I('post.');
			if($aid){
				$result		= $Article->articleSave($aid,$data);
				if($result){
					$this->success('修改成功');
				}else{
					$this->error($Article->error);
				}
			}else{
				$result		= $Article->articleAdd($data);
				if($result){
					$this->success('添加成功');
				}else{
					$this->error($Article->error);
				}
			}
		}else{
			//帮助分类
			$categorys	= $Article->getCategoryList(false);
			if($aid) {
				$articleInfo = $Article->getArticleInfo($aid);
				if($articleInfo == false){
					$this->error($Article->error);
				}
				$this->articleInfo	= $articleInfo;
			}
			
			$this->categorys		= $categorys;
			$this->display();
		}
    }

    // 百度编辑器
    public function ueditor(){
    	$data = new \Org\Util\Ueditor();
		echo $data->output();
    }

    /**
	 * auth	：fangkai
	 * content：文章分类详情，修改，添加
	 * updatetime	：2016-10-7
	**/
    public function categoryInfo(){
		$cid		= I('get.cid','','intval');
		$Article	= new \Common\Model\Article();
		if(IS_POST){
			$data	= I('post.');
			if($cid){
				$result		= $Article->categorySave($cid,$data);
				if($result){
					$this->success('修改成功');
				}else{
					$this->error($Article->error);
				}
			}else{
				$result		= $Article->categoryAdd($data);
				if($result){
					$this->success('添加成功');
				}else{
					$this->error($Article->error);
				}
			}
		}else{
			if($cid){
				$categoryInfo	= $Article->getCategoryInfo($cid);
				if($categoryInfo == false){
					$this->error($Article->error);
				}
				$this->categoryInfo = $categoryInfo;
			}
			
			$categorys	= $Article->getCategoryList(false);
			$this->categorys = $categorys;
			
			$this->display();
		}
	}
	
	public function articleCategoryDelete(){
		$cid		= I('get.cid','','intval');
		$Article	= new \Common\Model\Article();
		$result		= $Article->categoryDelete($cid);
		if($result){
			$this->success('删除分类成功');
		}else{
			$this->error($Article->error);
		}
	}

	/**
	 * auth	：fangkai
	 * content：文章分类列表
	 * updatetime	：2016-10-7
	**/
	public function categoryList(){
		$Article	= new \Common\Model\Article();
		$categorys	= $Article->getCategoryList(false);
		
		$this->categorys = $categorys;
		$this->display();
	}
		
	/**
	 * 广告位列表
	 * @param number $p 页数
	 * @param number $n 条数
	 */
	public function adsManage($p=1,$n=12,$template_type=1) {
		$agent_id    = C("agent_id");
		$res         = $this->getTemplateId($template_type);
		$template_id = $res['template_id'];
		$TAS         = M ('template_ads');
		if($template_type == 1){
			$this->xm = 'Home';
			if(empty($template_id)){
				$template_id = 2;//如果还没有配置当前模板
			}
		}elseif($template_type == 2){
			$this->xm = 'Mobile';
			if(empty($template_id)){
				$template_id = 3;//如果还没有配置当前模板
			}
		}elseif($template_type == 3){
			$this->xm = 'App';
			if(empty($template_id)){
				$template_id = 4;//如果还没有配置当前模板
			}
		}
		//默认模板，中国疯，欧洲风，温馨风，B2C模板广告位统一使用默认模板的广告位
		if(in_array($template_id,array(5,6,7,8))){
			$template_id = 2;
		}

		//手机B2C模板广告位
        if(in_array($template_id,array(10))){
            $template_id = 3;
        }

		//分页和查询数据
		$totalRows = $TAS->where('template_id = '.$template_id)->count();
		
		$Page = new \Think\Page($totalRows,$n);
		$this->page = $Page->show();
		$this->tempAdsList = $TAS->where('template_id = '.$template_id)->limit($Page->firstRow,$Page->rollPage)->select();
		foreach($this->tempAdsList as $key=>$val){
			$TA    = M ('template_ad');
			$where = 'tas.ads_id = '.$val['ads_id']." AND ta.agent_id=".C("agent_id");
			$join  = 'zm_template_ads as tas on ta.ads_id = tas.ads_id';
			//查询数据
			$tempAdList[$key]['ad_type'] = $val['ad_type'];
			$tempAdList[$key]['title']   = $val['title'];
			$tempAdList[$key]['theme']   = $val['theme'];
			$tempAdList[$key]['ads_id']  = $val['ads_id'];
			$tempAdList[$key]['template_status'] = $val['template_status'];
			$tempAdList[$key]['adlist'] = $TA
								->alias ('ta')
								->where($where)
								->field ('ta.*')
								->join($join)
								->order('sort ASC')
								->limit('0,5')
								->select();
		}
		$this->template_type = $template_type;
		$this->tempAdList = $tempAdList;
		$this->display ();
	}
	
	/**
	 * 生成广告位
	 * @param unknown $ads_id
	 */
	public function buildAds($ads_id){
		//获取广告位相关信息
		$join = 'zm_template AS t ON t.template_id = ta.template_id';
    	$this->info = M('template_ads')->alias('ta')->join($join)->where('ta.ads_id ='.$ads_id)->find();
    	//获取广告信息
    	if(cookie('think_language') == 'zh-CN')$lang = 1; 
    	$where = "ads_id = $ads_id  and lang = $lang and agent_id=".C("agent_id")." AND status > 0";
		$this->list = M('template_ad')->where($where)->order('sort')->select(); 
    	layout(false);
		if($this->info['template_type'] == 1){$xm = 'Home';}
		elseif($this->info['template_type'] == 2){$xm = 'Mobile';}
		elseif($this->info['template_type'] == 3){$xm = 'APP';}
		$file = './Application/'.$xm.'/View/'.$this->info['template_path'].'/Ad/ad_'.$ads_id.'_'.C("agent_id").'.html';
		if(!$this->list){
		    file_put_contents($file,'');
		    $this->error('当前广告位下面没有广告,不能生成广告位,已经删除原来的广告位',U('Admin/Article/adsManage'));
		}
    	$content = $this->fetch('./Application/'.$xm.'/View/Ad/'.$this->info['theme'].'.html');
		
    	unset($this->info,$this->list);
    	layout(true);
		if (!file_exists('./Application/'.$xm.'/View/'.$this->info['template_path'].'/Ad')){
			mkdir ('./Application/'.$xm.'/View/'.$this->info['template_path'].'/Ad');
		}
		unset($this->info,$this->list);
		if(file_put_contents($file,$content)){
			$this->success('生成广告成功！',U('Admin/Article/adsManage'));
		}else{
			$this->error('生成广告失败！',U('Admin/Article/adsManage'));
		}
	}
	
	/**
	 * 广告列表
	 * @param number $p 页数
	 * @param number $n 条数
	 * @param number $ads_id 广告位id
	 */
	public function adList($p=1,$n=13,$ads_id=0) {
		if(!$ads_id) $this->error('错误的广告位ID');
		$TA = M ('template_ad');
		$where = 'tas.ads_id = '.$ads_id." AND ta.agent_id=".C("agent_id");
		$join = 'zm_template_ads as tas on ta.ads_id = tas.ads_id';
		$this->ads_id = $ads_id;
		//分页
		$totalRows = $TA->alias ('ta')->where($where)->field ('ta.*, tas.title as adsTitle')->join($join)->count();
		$Page = new \Think\Page($totalRows,$n);
		$this->page = $Page->show();
		//查询数据
		$this->tempAdList = $TA->alias ('ta')->where($where)->field ('ta.*, tas.title as adsTitle')
			->join($join)->order('sort')->limit($Page->firstRow,$Page->rollPage)->select();
		$this->display ();
	}
	
	/**
	 * 添加/修改广告
	 * @param number $ad_id        	
	 */
	public function adInfo($ads_id,$ad_id = 0) {
		$adM = M ('template_ad');
		if (IS_POST) {
			$adData = I('post.');
			if (! empty ( $_FILES ['images_path'] ['name'] )) {
				$upload = new \Think\Upload(); // 实例化上传类
				$upload->maxSize = 3145728; // 设置附件上传大小
				$upload->exts = array ('jpg','gif','png','jpeg'); // 设置附件上传类型
				$upload->savePath = '/Uploads/ad/'.C('agent_id').'/'; // 设置附件上传目录
				$info = $upload->uploadOne ( $_FILES ['images_path'] ); // 上传单个文件
				if (! $info) {
					$this->log ( 'error', L ( 'A8013' ) . ',' . $upload->getError () );
				} else {
					$adData ['images_path'] = $info ['savepath'] . $info ['savename'];
				}
			}
			$adData['ads_id']   =  $ads_id;
			$adData['agent_id'] =  C("agent_id");
			$adData = array_merge($adData,$this->buildData());
			if($ad_id and $adData ['images_path']){
				$info = $adM->find($ad_id);
				$this->imagesDel($info['images_path']);
			}
			if (empty ( $ad_id )) {
				$ad_id = $adM->data ( $adData )->add ();
				if ($ad_id) {
					$this->log ( 'success', 'A8014', 'ID:' . $ad_id, U ( 'Admin/Article/adList?ads_id='.$ads_id ) );
				} else {
					$this->log ( 'error', 'A8015' );
				}
			} else {
				if ($adM->where ('ad_id = '.$ad_id )->save ( $adData )) {
					$this->log ( 'success', 'A8010', 'ID:' . $ad_id, U ( 'Admin/Article/adList?ads_id='.$ads_id ) );
				} else {
					$this->log ( 'error', 'A8011' );
				}
			}
		} else {
			// 取到相应的广告位
			$this->tempAdsInfo = M('template_ads')->where('ads_id = '.$ads_id)->find();
			$this->ads_id = $ads_id;
			//根据ID取到指定的广告
			if ($ad_id) {$this->adOnce = $adM->where ('ad_id = '.$ad_id)->find ();}
			$this->display();
		}
	}
		
	/**
	 * 删除广告
	 * @param number $ad_id
	 */
	public function deleteAd($ad_id = 0) {
		$TA = M ('template_ad');
		$info = $TA->find ($ad_id);
		$res = $TA->where('ad_id = '.$ad_id)->delete();
		if($res){
		    $this->imagesDel ($info ['images_path']);
			//$this->log('success','A8016','ID:'.$ad_id,U('Admin/Article/buildAds?ads_id='.$info['ads_id']));
			$this->log('success','A8016');
		}else{
			$this->log('error','A8017');
		}
	}

	/**
	 * auth	：fangkai
	 * content：帮助中心分类页
	 * time	：2016-10-5
	**/
	public function help(){
		$Help = new \Common\Model\Help();
		$categoryList = $Help->getCategoryList(false);
		
		$this->categoryList = $categoryList;
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：帮助中心分类详细信息和添加
	 * time	：2016-10-5
	**/
    public function helpCategoryInfo(){
		$cid = I('get.cid','','intval');
		$Help = new \Common\Model\Help();
		if(IS_POST){
			$data = I('post.');
			if($cid){
				$result		= $Help->categorySave($cid,$data);
				if($result){
					$this->success('修改成功');
				}else{
					$this->error($Help->error);
				}
			}else{
				$result		= $Help->categoryAdd($data);
				if($result){
					$this->success('添加成功');
				}else{
					$this->error($Help->error);
				}
			}
		}else{
			if($cid){
				$categoryInfo		= $Help->getCategoryInfo($cid);
				if($categoryInfo == false){
					$this->error($Help->error);
				}
				$this->categoryInfo = $categoryInfo;
			}
			$this->display();
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：帮助中心分类删除
	 * time	：2016-10-5
	**/
    public function helpCategoryDelete(){
		$cid    = I('get.cid','','intval');
		$Help   = new \Common\Model\Help();
		$result	= $Help->categoryDelete($cid);
		if($result){
			$this->success('删除分类成功');
		}else{
			$this->error($Help->error);
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：帮助列表
	 * time	：2016-10-5
	**/
    public function helpList(){
		$p		= I('get.p',1,'intval');
		$title	= I('get.title');
		$cat_id	= I('get.cat_id');
		//帮助分类
		$Help	= new \Common\Model\Help();
		$categoryList	= $Help->getCategoryList(false);
		
		//帮助列表
		if($title){
    		$where['zm_help.title']	= array('like','%'.$title.'%');
    	}
    	if($cat_id){
    		$where['zm_help.cat_id']= $cat_id;
    	}
		
		$count				= $Help->getArticleCount($where,false);
		$Page				= new \Think\Page($count,20);
		$limit				= $Page->firstRow.','.$Page->listRows;
		
		$helpList			= $Help->getArticleList($where,$limit,false);
		
		$this->page 		= $Page->show();
		$this->title		= $title;
		$this->cat_id		= $cat_id;
		$this->site_name	= C('site_name');
		$this->categoryList	= $categoryList;
		$this->helpList		= $helpList;
		$this->display();
	}	
	
	/**
	 * auth	：fangkai
	 * content：帮助文章详情
	 * time	：2016-10-5
	**/
    public function helpArticleInfo(){
		$aid = I('get.aid','','intval');
		$Help	= new \Common\Model\Help();
		if($_POST){
			$data = I('post.');
			if($aid){
				$result		= $Help->articleSave($aid,$data);
				if($result){
					$this->success('修改成功');
				}else{
					$this->error($Help->error);
				}
			}else{
				$result		= $Help->articleAdd($data);
				if($result){
					$this->success('添加成功');
				}else{
					$this->error($Help->error);
				}
			}
		}else{
			//帮助分类
			$categoryList	= $Help->getCategoryList(false);
			
			if($aid) {
				$helpArticleInfo = $Help->getArticleInfo($aid);
				if($helpArticleInfo == false){
					$this->error($Help->error);
				}
				$this->helpArticleInfo	= $helpArticleInfo;
			}
			
			$this->categoryList		= $categoryList;
			$this->display();
		}
	}

	/**
	 * auth	：fangkai
	 * content：帮助中心文章删除
	 * time	：2016-10-5
	**/
    public function helpArticleDelete(){
		$aid = I('get.aid','','intval');
		$Help = new \Common\Model\Help();
		$result		= $Help->articleDelete($aid);
		if($result){
			$this->success('删除帮助文章成功');
		}else{
			$this->error($Help->error);
		}
	}
}