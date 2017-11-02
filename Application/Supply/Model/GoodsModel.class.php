<?php
namespace Supply\Model;
use Think\Model;
class GoodsModel extends Model
{

    protected $patchValidate = true;
    // 数据验证
    public $_validate = array(
        array('goods_name', 'require', '{%error_product_name_must}'),
    );

    // 写入默认数据
    public $_auto = array(
        //array('uid', 'getUid', self::MODEL_INSERT, 'callback'),
    );

    public function getList($where,$sort='goods_id desc',$pageid=1,$pagesize=50,$agent_id=0)
    {
        //$files = $this->getDbFields();
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
        $totalnum          = $this->where($where)->count();
        $limit             = (($pageid-1)*$pagesize).','.$pagesize;
        $list              = $this -> where($where) -> order($sort) -> limit($limit) -> select();
        $data['total']     = $totalnum;
        $data['page_size'] = $pagesize;
        $data['page_id']   = $pageid;
        $data['list']      = $list;
        return $data;
    }

    public function onSale($goods_ids,$agent_id=0){
        if(!is_array($goods_ids) || count($goods_ids) == 0 ){
            return false;
        }
        $where                   = array();
        $where['product_status'] = array('eq','0');
        $where['goods_id']       = array('in',$goods_ids);
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
        $date_str = date('Y-m-d H:i:s');
        $this -> where($where)->save(array('product_status'=>'1','on_sale_time'=>$date_str));
        return true;
    }

    public function offShelves($goods_ids,$agent_id=0){
        if(!is_array($goods_ids) || count($goods_ids) == 0 ){
            return false;
        }
        $where                   = array();
        $where['product_status'] = array('eq','1');
        $where['goods_id']       = array('in',$goods_ids);
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id),'on_sale_time'=>null));
        }
        $this->where($where)->save(array('product_status'=>'0'));
        return true;
    }

    public function deleteGoods($goods_ids,$agent_id=0){
        if(!is_array($goods_ids) || count($goods_ids) == 0 ){
            return false;
        }
        $where                   = array();
        //$where['product_status'] = array('eq','0');
        $where['goods_id']       = array('in',$goods_ids);
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
        $this->where($where)->delete();
        return true;
    }
}
