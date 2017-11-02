<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/22
 * Time: 17:18
 */
namespace Mobile\Controller;

use \Home\Model\ArticleModel as ArticleModel;
use \Home\Model\ArticleCatModel as ArticleCatModel;
use \Admin\Model\TemplateSeeting as TemplateSeeting;

class NewIndexController extends NewMobileController{

    private $agentId;
    private $uid;

    public function __construct() {
        parent::__construct();
        $this->agentId = C("agent_id");
        $this->uid = $_SESSION['m']['uid'];
    }

    public function index(){
        $templateAdModel = M('template_ad');
        $customModuleModel = M('b2c_module_config');

        // 首页焦点图
        $focusAdList = $templateAdModel->where([
            'agent_id' => $this->agentId,
            'status'   => 1,
            'ads_id'   => 48,
        ])->order('sort ASC')->select();

        // 焦点图底部自定义模块
        $customModuleList = [];
        $customModules = $customModuleModel->where([
            'agent_id' => $this->agentId,
            'status'   => 1,
        ])->order('sort ASC')->select();

        foreach($customModules as $k => $module){
            switch($module['position_id']){
                case 1:
                    $module['url'] = '/Diamond/goodsDiy';
                    break;
                case 2:
                    $module['url'] = '/Goods/goodsDiy';
                    break;
                case 3:
                    $module['url'] = '/Goods/category';
                    break;
                case 4:
                    $module['url'] = '/Store/index';
                    break;
                default:
                    $module['url'] = '/';
            }
            $customModuleList[] = $module;
        }

        // 活动热卖
        $goodsModel = D('Common/Goods');
        $agentId = $goodsModel->get_where_agent();

        $field = 'g.*,ga.product_status AS activity_status';
        $join = 'LEFT JOIN zm_goods_activity AS ga ON ga.gid = g.goods_id';
        $sort = 'ga.setting_time DESC';

        $hotsWhere = [
            'ga.agent_id'       => $this->agentId,
            'g.agent_id'        => $agentId,
            'g.sell_status'     => 1,
            'g.shop_agent_id'   => $this->agentId,
            'ga.home_show'      => 2,
            'ga.product_status' => 1,
        ];
        $hotGoods = $goodsModel->getCustomGoodsList($hotsWhere, $field, $join, $sort, '', '');
        // 获取售卖价格
        $hotGoodsList = getGoodsListPrice($hotGoods, $this->uid, 'consignment', 'all');

        // 钻石定制
        $goodsCustomAdList = $templateAdModel->where([
            'agent_id' => $this->agentId,
            'status'   => 1,
            'ads_id'   => 49,
        ])->order('sort ASC')->select();

        // 商城热卖
        $goodsCategory      = D('GoodsCategory');
        //查询出所有的二级分类
        $field = 'cg.category_id, cg.category_name, cg.pid, cc.name_alias, cc.img, cc.agent_id, cc.sort_id';
        $where = [
            'cg.pid' => 0,
            'cc.agent_id' => $this->agentId,
            'cc.is_show' => 1,
            'cc.home_show' => 1,
        ];
        $join = 'LEFT JOIN zm_goods_category_config AS cc ON cg.category_id = cc.category_id';
        $sort = 'cc.sort_id ASC';
        $categoryList = $goodsCategory->getCategoryListTwo($field, $join, $where, $sort);

        //查询出二级分类下面的三级分类并取出category_id合并为一维数组赋值到原二级分类数组中
        foreach($categoryList as $key=>$val){
            $where['cg.pid'] = $val['category_id'];
            //查询出所有三级分类
            $categoryArr = $goodsCategory->getCategoryListTwo($field, $join, $where);
            if($categoryArr){
                $categoryList[$key]['category_array'] = array_column($categoryArr, 'category_id');
            }else{
                $categoryList[$key]['category_array'] = array('0' => $val['category_id']);
            }
        }
        //查询出二级分类里面首页展示的商品,
        $mallHotList = $goodsModel->hotGoodsList($this->agentId, $categoryList, $this->uid);
        $mallHotListCount = $mallHotList['count'];
        if(!empty($mallHotList) && count($mallHotList) > 2){
            $mallHotList = array_slice($mallHotList, 0, 2);
            $mallHotList['count'] = $mallHotListCount;
        }

        // 商城热卖广告图
        $mallHotAdList = $templateAdModel->where([
            'agent_id' => $this->agentId,
            'status'   => 1,
            'ads_id'   => 50,
        ])->order('sort ASC')->select();

        // 品牌系列
        $brandSeriesModel = D('GoodsSeries');
        $sort = 'goods_series_id ASC';
        $brandList = $brandSeriesModel->getSeriesList([
            'agent_id'  => $this->agentId,
            'home_show' => 1,
        ], $sort, 1, 2);

        // 品牌系列广告图
        $brandAdList = $templateAdModel->where([
            'agent_id' => $this->agentId,
            'status'   => 1,
            'ads_id'   => 51,
        ])->order('sort ASC')->select();

        // 首页新闻
        $articleCatModel = new ArticleCatModel();
        $articleModel = new ArticleModel();

        $articleList = [];

        $articleCatWhere = [
            'agent_id'  => $this->agentId,
            'is_show'   => 1,
            'parent_id' => 0,
        ];

		
		

		
		//自定义
        $this->content = M('b2c_template_config')
						->alias('zbtc')
						->where("zbtc.type = 2 and zbtc.template_id = 10 and zbtc.is_show = 1 and zbtc.title = '自定义编辑' and zbtv.agent_id = ".$this->agentId)
						->join('LEFT JOIN zm_b2c_template_value as zbtv ON zbtc.id=zbtv.position_id ')
						->getField('zbtv.content');

		
        // 获取排序值最低的文章分类
        $articleCats = $articleCatModel->getCategoryList($articleCatWhere, 'sort ASC', '1');
        if(is_array($articleCats) && !empty($articleCats)){
            $articleCat = $articleCats[0];
        }

        if(!empty($articleCat)){
            // 查询出当前二级分类并根据二级分类查询出文章列表赋值到一级分类数组中
            $articleCatWhere['parent_id'] = $articleCat['cid'];
            $categories = $articleCatModel->getCategoryList($articleCatWhere);
            $categoryIds = array();
            if($categories){
                $categoryIds = array_column($categories, 'cid');
            }
            $categoryIds[] = $articleCat['cid'];

            $articleList = $articleModel->getArticleList([
                'agent_id' => $this->agentId,
                'is_show'  => 1,
                'cat_id'   => ['in', $categoryIds],
            ], 'aid DESC', 1, 7);
        }



        if(I('cookie.autologin') == 1){
            $login = new \Home\Controller\PublicController();
            $login->loginByCookie();
            exit;
        }

        $this->assign('title', '首页');
        $this->assign('keywords', '裸钻查询');
        $this->assign('description', '裸钻查询');
        $this->assign('positionList', $this->getPositionList(10));

        $this->assign('focusAdList', $focusAdList);
        $this->assign('customModuleList', $customModuleList);
        $this->assign('hotGoodsList', $hotGoodsList);
        $this->assign('goodsCustomAdList', $goodsCustomAdList);
        $this->assign('mallHotList', $mallHotList);
        $this->assign('mallHotAdList', $mallHotAdList);
        $this->assign('brandList', $brandList);
        $this->assign('brandAdList', $brandAdList);
        $this->assign('articleList', $articleList);

        $this->display();
    }

    /**
     * 获取首页模块配置信息
     *
     * @param $template_id 模板ID(10:B2C手机模板; 3:B2C PC模板)
     * @author wangkun
     * @date 2017/7/25
     */
    private function getPositionList($template_id){
        $templateSetting = new TemplateSeeting();
        $positionList = $templateSetting->getPositionList($template_id);
        if(is_array($positionList) && !empty($positionList)){
            $positionList	= arraySort($positionList, 0, 'position_id');
        }

        return $positionList;
    }
}