<?php
/**
 * 公共模型
 * User: Administrator
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Supply\Model;
Class SaleStatusModel extends \Think\Model{
    protected $autoCheckFields = false;
    private   $objs;

    public function __construct() {
        parent::__construct();
        $this->objs[] = D('ZmSaleStatus');//钻明上架
        $this->objs[] = D('ZmfxSaleStatus');//钻明上架
    }

    //上架
    public function on_sale($goods_ids,$type='',$agent_id=0){
        foreach ($this->objs as $row){
            $status    = $row->on_sale($goods_ids,$type,$agent_id);
            if($status == false){
                break;
            }
        }
        return true;
    }

    //下架
    public function off_shelves($goods_ids,$type='',$agent_id=0){
        foreach ($this->objs as $row){
            $status    = $row->off_shelves($goods_ids,$type,$agent_id);
            if($status == false){
                break;
            }
        }
        return true;
    }
}