<?php
/**
 * 产品相关
 * Auth: 王松林
 * Date: 2016/3/30
 */
namespace Common\Model;
use Think\Model;

class GoodsAttributesValueModel extends Model {
    private $page;
    private $sql = array(
        'where'=>null,
        'order'=>'',
        'limit'=>'',
        'join' =>array(),
    );
    //构造函数
    public function __construct(){
    }
    public function setAttrId($attr_id){
        $this->sql['where']['attr_id'] = " attr_id in ($attr_id)";
        return $this;
    }
    public function get_count(){
        $productM = M('goods_attributes_value');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1 = 1';
        }
        $this->count = $productM->where($where)->count();
        return $this->count;
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
    public function order($order,$desc){
        $this->sql['order'] = "$order $desc";
        return $this;
    }
    public function getList(){
        $productM = M('goods_attributes_value');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        if(empty($this->sql['limit'])){
            $this->limit();
        }
        if(empty($this->sql['order'])){
            $this->order('attr_value_id','ASC');
        }
        $data = $productM->where($where)->limit($this->sql['limit'])->order($this->sql['order'])->select();
        return $data;
    }

}