<?php
/**
 * 主要是对模块业务的操作
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/25 0025
 * Time: 10:55
 */
namespace Home\Model;
use Think\Model;
class LayoutModel extends Model{

    private $layout_name;
    private $template_id;
    private $parent_type;
    private $parent_id;

    protected $autoCheckFields = false;

    public function __construct($template_id=0,$parent_id=0){

        $this->template_id = $template_id;
        $this->parent_id   = $parent_id;
        if($parent_id){
            $this->parent_type = 2;
        }else{
            $this->parent_type = 1;
        }
        $this->layout_name = '/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
    }

    public function getLayoutItemSettingInfo(){

        $LayoutItemM         = M("layout_item");
        $LayoutItemSettingM  = M("layout_item_setting");
        $setting_items_all   = $LayoutItemM->where('where template_id = '.$this->template_id .' and layout_name = "'.$this->layout_name.'"')->select();
        $setting_items       = $LayoutItemSettingM->where("where layout_name = '$this->layout_name' and template_id = '$this->template_id'' and parent_type = $this->parent_type and parent_id = $this->parent_id ")->select();

        foreach($setting_items_all as &$setting_item){
            foreach($setting_items as $_setting_items){
                if($setting_item['layout_item_key'] == $_setting_items['layout_item_key']){
                    $setting_item = $_setting_items;
                }
            }
            if(!isset($setting_item['id'])){
                $row['id']     = 0;
                $row['status'] = 1;
            }
        }
        return $setting_items_all;
    }

    public function getAllInfo(){

        $setting_items_all = $this->getLayoutItemSettingInfo();
        $res = array();
        foreach($setting_items_all as $setting_item){
            $module_type    = $setting_item['module_type'];
            $module_title   = $setting_item['module_title'];
            $module_where   = $setting_item['module_where'];
            $module_limit   = $setting_item['module_limit'];
            $status         = $setting_item['status'];
            $data           = array();
            $data['status'] = $status;
            $data['title']  = $module_title;
            switch ($module_type){
                case 0://不做任何操作
                    break;
                case 1://返回裸钻数据
                    break;
                case 2://返回散货数据
                    break;
                case 3://珠宝成品
                        $data['category_id']   = '';
                        $data['category_name'] = '';
                        $data['list']          = array();
                        if($status) {
                            $where             = "where $module_where";
                            $limit             = "limit $module_limit";
                            $category_info     = M('goods_category')->where($where)->find();
                            if($category_info){
                                $data['category_id']   = $category_info['category_id'];
                                $data['category_name'] = $category_info['category_name'];
                                $data['list']          = M('goods')->where('goods_type = 3 and category_id = '.$category_info['category_id'])->limit($limit)->select();
                            }
                        }
                    break;
                case 4://珠宝定制
                        $data['category_id']   = '';
                        $data['category_name'] = '';
                        $data['list']          = array();
                        if($status) {
                            $where             = "where $module_where";
                            $limit             = "limit $module_limit";
                            $category_info     = M('goods_category')->where($where)->find();
                            if($category_info){
                                $data['category_id']   = $category_info['category_id'];
                                $data['category_name'] = $category_info['category_name'];
                                $data['list'] = M('goods')->where('goods_type = 4 and category_id = '.$category_info['category_id'])->limit($limit)->select();
                            }
                        }
                    break;
                case 5://文章
                        $data['list']      = array();
                        $data['catname']   = '';
                        $data['catid']     = '';
                        if($status) {
                            $where         = "where $module_where";
                            $limit         = "limit $module_limit";
                            $category_info = M('article_cat')->where($where)->find();
                            if($category_info){
                                $data['catname'] = $category_info['catname'];
                                $data['cid']     = $category_info['cid'];
                                $data['list']    = M('goods')->where('is_show = 1 and cat_id = '.$category_info['cid'])->limit($limit)->select();
                            }
                        }
                    break;
            }
            $res[$setting_item['layout_item_key']][] = $data;
            return $res;
        }
    }
}