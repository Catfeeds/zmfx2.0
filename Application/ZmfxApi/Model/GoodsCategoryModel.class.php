<?php
/**
 * 产品相关
 * Auth: 王松林
 * Date: 2016/3/30
 */
namespace Common\Model;
use Think\Model;

class GoodsCategoryModel extends Model {
    private $page;
    private $sql = array(
        'where'=>null,
        'order'=>'',
        'limit'=>'',
        'join' =>array(),
    );
    //构造函数
    public function __construct(){
        $this->sql['where'][] = "agent_id = ".C('agent_id');

    }
    public function category_id($category_id){
        $this->sql['where']['category_id'] = " category_id = '$category_id' ";
        return $this;
    }
    public function parent_category_id($category_id){
        $this->sql['where']['pid'] = " pid = '$category_id' ";
        return $this;
    }
    public function get_count(){
        $productM = M('goods_category_config');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1 = 1';
        }
        $this->count = $productM->where($where)->count();
        return $this->count;
    }
    public function order($order,$desc){
        $this->sql['order'] = "$order $desc";
        return $this;
    }
    public function limit($per=20){
        if(empty($this->count)){
            $this->get_count();
        }
        $page               = new \Think\AjaxPage($this->count,$per,"setPage");
        $this->page         = $page;
        $this->sql['limit'] = $page->firstRow.','.$page->listRows;
        return $this;
    }
    public function getUserGoodsCategoryList(){
        $productM = M('goods_category_config');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        if(empty($this->sql['limit'])){
            $this->limit();
        }
        if(empty($this->sql['order'])){
            $this->order('sort_id','ASC');
        }
        $data = $productM->where($where)->limit($this->sql['limit'])->order($this->sql['order'])->select();
        $this->sql['where'] = array();
        return $data;
    }
    public function getInfo(){
        $productM = M('goods_category_config');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        $data  = $productM->where($where)->finde();
        $this -> sql['where'] = array();
        return $data;
    }
    public function getChildUserGoodsCategoryList(){
        $productM  = M('goods_category_config');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        } else {
            $where = ' 1 = 1 ';
        }
        $data                 = $productM -> where($where) -> getField('category_id',true);
        $this -> sql['where'] = array();
        return $data;
    }
}