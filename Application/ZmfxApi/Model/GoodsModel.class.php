<?php
/**
 * 产品相关
 * Auth: 王松林
 * Date: 2016/3/30
 */
namespace Common\Model;
use Think\Model;

class GoodsModel extends Model {
    private $page;
    private $sql = array(
        'where'=>null,
        'order'=>'',
        'limit'=>'',
        'join' =>array(),
    );
    //构造函数
    public function __construct(){
        $this->sql['where'][] = "zm_goods.agent_id = ".C('agent_id');
    }
    public function category_id($category_id){
        $this->sql['where']['category_id'] = " zm_goods.category_id = '$category_id' ";
        return $this;
    }
    public function goods_type($goods_type){
        $this->sql['where']['goods_type'] = " zm_goods.goods_type = '$goods_type'";
        return $this;
    }
    public function seachr($seachr){
        $this->sql['where']['seachr'] = "( zm_goods.goods_name like '%$seachr%' or  zm_goods.content like '%$seachr%' or  zm_goods.goods_sn like '%$seachr%')";
        return $this;
    }
    public function get_count(){
        $productM = M('goods');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1 = 1';
        }
        $this->count = $productM->where($where)->count();
        return $this->count;
    }
    public function join($join_str){
        $this->sql['join'][] = $join_str;
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
    public function order($order,$desc){
        $this->sql['order'] = " zm_goods.$order $desc";
        return $this;
    }
    public function getList(){
        $productM = M('goods');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        if($this -> sql['join']){
            foreach($this -> sql['join'] as $row){
                $productM ->join($row);
            }
        }
        if(empty($this->sql['limit'])){
            $this->limit();
        }
        if(empty($this->sql['order'])){
            $this->order('goods_id','ASC');
        }
        $data = $productM->where($where)->limit($this->sql['limit'])->order($this->sql['order'])->field('zm_goods.*')->select();
        return $data;
    }
}