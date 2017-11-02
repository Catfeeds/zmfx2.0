<?php
/**
 * 产品相关
 * Auth: 王松林
 * Date: 2016/3/30
 */
namespace Common\Model;
use Think\Model;

class GoodsSeriesModel extends Model {
    private $page;
    private $sql = array(
        'where'=>null,
        'order'=>'',
        'limit'=>'',
        'join' =>array(),
    );
    //构造函数
    public function __construct(){
        $this->sql['where'][] = "zm_goods_series.agent_id = ".C('agent_id');

    }
    public function get_count(){
        $productM = M('goods_series');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        $this->count = $productM->where($where)->count();
        return $this->count;
    }
    public function limit($per=20){
        $page               = new \Think\AjaxPage($this->count,$per,"setPage");
        $this->page         = $page;
        $this->sql['limit'] = $page->firstRow.','.$page->listRows;
        return $this;
    }
    public function getList(){
        $productM = M('goods_series');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        $data = $productM->where($where)->limit($this->sql['limit'])->select();
        return $data;
    }
}