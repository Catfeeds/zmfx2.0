<?php
/**
 * 上架下架基类
 * User: Administrator
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Supply\Model;
abstract Class SaleStatusImplModel extends \Think\Model{
    Protected $autoCheckFields = false;
    //上架
    abstract public function on_sale($goods_ids,$type='',$agent_id=0);
    //上架
    abstract public function off_shelves($goods_ids,$type='',$agent_id=0);
    //上架所有
    abstract public function on_sale_all($type='',$agent_id=0);
    //下架所有
    abstract public function off_shelves_all($type='',$agent_id=0);
}