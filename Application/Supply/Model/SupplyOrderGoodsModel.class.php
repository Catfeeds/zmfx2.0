<?php
/*
*@note    订单商品MODEL 
*@authoer 张超豪
*@addtime 2016-05-06
*/
namespace Supply\Model;
use Think\Model;
class SupplyOrderGoodsModel extends Model{
    /*
    *@note    订单商品详情 
    *@authoer 张超豪
    *@addtime 2016-05-06
    */ 
    public function getOrderGoodsListInfo($order_id = 0, $dollar_huilv = 0){
        //查询数据
        $info = $this->where('order_id = '.$order_id)->select();
        foreach($info as $value){
            $value['attribute'] = unserialize($value['attribute']);
            $value['update_time_str'] = date('Y-m-d H:i:s', $value['update_time']);
            if($value['fahuo_time'] > 0){
                $value['fahuo_time_str'] = date('Y-m-d', $value['fahuo_time']);
            }else{
                $value['fahuo_time_str'] = '';
            }
            if($value['goods_number_up']>0){
                $value['goods_number_true'] = $value['goods_number_up'];
            }else{
                $value['goods_number_true'] = $value['goods_number'];
            }
            if($value['goods_price_up']>0){
                $value['goods_price_true'] = $value['goods_price_up'];
            }else{
                $value['goods_price_true'] = $value['goods_price'];
            }

            if($value['goods_type'] == 1){
                $value['now_dia_discount'] = round(100 * $value['goods_price_true']/($dollar_huilv * $value['attribute']['dia_global_price'] *  $value['attribute']['weight']), 2);
                $goodsListInfo['all_luozuan_price'] = $goodsListInfo['all_luozuan_price']+$value['goods_price_true'];
                $goodsListInfo['luozuan'][] = $value;

            }elseif($value['goods_type'] == 2){
                $goodsListInfo['all_sanhuo_price'] = $goodsListInfo['all_sanhuo_price']+$value['goods_price_true'];
                $goodsListInfo['sanhuo'][] = $value;
            }else{
                $goodsListInfo['all_consignment_price'] = $goodsListInfo['all_consignment_price']+$value['goods_price_true'];
                $goodsListInfo['consignment'][] = $value;
            }           
            
        } 
        return $goodsListInfo;
    }
}