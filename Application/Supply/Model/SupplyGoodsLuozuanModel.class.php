<?php
namespace Supply\Model;
use Think\Model;
class SupplyGoodsLuozuanModel extends Model
{

    protected $patchValidate = false;
    // 数据验证
    public $_validate = array(
        array('goods_name', 'require', '{%error_product_name_must}'),
    );

    // 写入默认数据
    public $_auto = array(
        array('uid', 'getAgentId', self::MODEL_INSERT, 'callback'),
        array('price', 'getPrice', self::MODEL_BOTH, 'callback'),
    );

    public function getAgentId(){
        $SA         = D('SupplyAccount');
        return $SA -> getAccountField('agent_id_build');
    }

    public function getPrice(){
        $dollar_huilv = D('Config')->getOneZmConfigValue('dollar_huilv');
        $price        = round(doubleval($_POST['dia_global_price']) * (doubleval($_POST['dia_discount'])/100) * $dollar_huilv * doubleval($_POST['weight']),2);
        return $price;
    }

    public function getLuozuanList($where,$sort='gid desc',$pageid=1,$pagesize=50,$agent_id=0)
    {
        //$files = $this->getDbFields();
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
        $totalnum          = $this->where($where)->count();
        $limit             = (($pageid-1)*$pagesize).','.$pagesize;
        $list              = $this->where($where)->order($sort)->limit($limit)->select();
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
        $where['gid']            = array('in',$goods_ids);
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
        $date_str = date('Y-m-d H:i:s');
        $this->where($where)->save(array('product_status'=>'1','on_sale_time'=>$date_str));
        return true;
    }

    public function offShelves($goods_ids,$agent_id=0){
        if(!is_array($goods_ids) || count($goods_ids) == 0 ){
            return false;
        }
        $where                   = array();
        $where['product_status'] = array('eq','1');
        $where['gid']            = array('in',$goods_ids);
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id),'on_sale_time'=>null));
        }
        $this->where($where)->save(array('product_status'=>'0'));
        return true;
    }

    public function  deleteLuozuan($goods_ids,$agent_id=0){
        if(!is_array($goods_ids) || count($goods_ids) == 0 ){
            return false;
        }
        $where                   = array();
        $where['product_status'] = array('eq','0');
        $where['gid']            = array('in',$goods_ids);
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
        $this->where($where)->delete();
        return true;
    }

    public function addLuozuan(){
        $this->create();
        return $this->add();
    }

    public function modifyLuozuan($agent_id = 0){
        $this->create();
        $this->where(' agent_id = '.$agent_id .' and gid = '.$_POST['gid'])->save();
        return true;
    }
}
