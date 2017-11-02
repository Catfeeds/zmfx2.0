<?php
/**
 * 钻名分销上架下架管理
 * User: Administrator
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Supply\Model;
Class ZmfxSaleStatusModel extends SaleStatusImplModel{

    //上架
    public function on_sale($goods_ids,$type='',$agent_id=0){
        if( !is_array($goods_ids) && count($goods_ids) == 0 ){
            return false;
        }
        if( count($goods_ids) == 1 ){
            $goods_id_str = $goods_ids[0];
        } else {
            $goods_id_str = implode(',',$goods_ids);
        }
        $dollar_huilv     = D('Config')->getOneZmConfigValue('dollar_huilv');
        switch ($type){
            case 'sanhuo':
                   $res  = M('supply_goods_sanhuo') -> join('
                    join zm_supply_account on zm_supply_goods_sanhuo.agent_id = zm_supply_account.agent_id_build
                   ') -> where("goods_id in ($goods_id_str) and zm_supply_goods_sanhuo.agent_id = $agent_id ") -> field('zm_supply_goods_sanhuo.*,zm_supply_account.supply_advantage') -> select();
                   $data = array();
                   foreach($res as $v){
                       $row                    = array();
                       $row['supply_goods_id'] = $v['goods_id']?$v['goods_id']:'';
                       $row['location']        = $v['location']?$v['location']:'';
                       $row['goods_sn']        = $v['goods_sn']?$v['goods_sn']:'';
                       $row['goods_number']    = $v['goods_number']?$v['goods_number']:'';
                       $row['tid']             = $v['tid']?$v['tid']:'';
                       $row['weights']         = $v['weights']?$v['weights']:'';
                       $row['clarity']         = $v['clarity']?$v['clarity']:'';
                       $row['color']           = $v['color']?$v['color']:'';
                       $row['cut']             = $v['cut']?$v['cut']:'';
                       $row['goods_weight']    = $v['goods_weight']?$v['goods_weight']:'';
                       $row['goods_price']     = $v['goods_price']?($v['goods_price'] * (1+ $v['supply_advantage']/100)):'';
                       $row['goods_price2']    = $v['goods_price2']?($v['goods_price2'] * (1+ $v['supply_advantage']/100)):'';
                       $row['goods_4c']        = $v['goods_4c']?$v['goods_4c']:'';
                       $row['create_time']     = time();
                       $row['update_time']     = time();
                       $row['goods_quantity']  = $v['goods_quantity']?$v['goods_quantity']:'';
                       $row['goods_status']    = $v['goods_status']?$v['goods_status']:'0';
                       $row['supply_id']       = $v['agent_id'];
                       $data[]                 = $row;
                   };
                   $zmobj     = M('goods_sanhuo');
                   $zmobj    -> addAll($data,array(),' tid ');
                   if($zmobj -> getError()){
                        return false;
                   }
                   $res  = M('supply_goods_sanhuo_cc') -> where("goods_id in ($goods_id_str)") -> select();
                   $data = array();
                   foreach($res as $v){
                       $row                    = array();
                       $row['supply_goods_id'] = $v['goods_id'] ? $v['goods_id']:'';
                       $row['cc_type']         = $v['cc_type']  ? $v['cc_type']:'';
                       $row['cc_value']        = $v['cc_value'] ? $v['cc_value']:'';
                       $row['cc_ku']           = $v['cc_ku']    ? $v['cc_ku']:'';
                       $data [] = $row;
                   };
                   $zmobj     = M('goods_sanhuo_cc');
                   $zmobj    -> addAll($data);
                   if($zmobj -> getError()){
                        return false;
                   }
                   $sql  = "
                        UPDATE zm_goods_sanhuo_cc,zm_goods_sanhuo set zm_goods_sanhuo_cc.goods_id = zm_goods_sanhuo.goods_id WHERE
                        zm_goods_sanhuo_cc.supply_goods_id = zm_goods_sanhuo.supply_goods_id;
                        DELETE FROM `zm_goods_sanhuo_cc` WHERE goods_id = 0;
                   ";
                   $zmobj -> execute($sql);
                   if( $zmobj -> getError() ){
                        return false;
                   }
                break;
            case 'luozuan':
                    $res  = M('supply_goods_luozuan') -> join('
                    join zm_supply_account on zm_supply_goods_luozuan.agent_id = zm_supply_account.agent_id_build
                   ') -> where("gid in ($goods_id_str) and zm_supply_goods_luozuan.agent_id = $agent_id ") -> field('zm_supply_goods_luozuan.*,zm_supply_account.supply_advantage') -> select();
                    $data = array();
                    foreach($res as $v){
                        $row                       = array();
                        $row['supply_gid']         = $v['gid'] ? $v['gid']:'';
                        $row['supply_id']          = $v['agent_id'];
                        $row['goods_name']         = $v['goods_name']?$v['goods_name']:'';
                        $row['certificate_type']   = $v['certificate_type']?$v['certificate_type']:'';
                        $row['luozuan_type']       = $v['luozuan_type']?$v['luozuan_type']:'';
                        $row['Intensity']          = $v['Intensity']?$v['Intensity']:'';
                        $row['certificate_number'] = $v['certificate_number']?$v['certificate_number']:'';
                        $row['location']           = $v['location']?$v['location']:'';
                        $row['quxiang']            = '订货';
                        $row['shape']              = $v['shape']?$v['shape']:'';
                        $row['weight']             = $v['weight']?$v['weight']:'';
                        $row['color']              = $v['color']?$v['color']:'';
                        $row['clarity']            = $v['clarity']?$v['clarity']:'';
                        $row['cut']                = $v['cut']?$v['cut']:'';
                        $row['polish']             = $v['polish']?$v['polish']:'';
                        $row['symmetry']           = $v['symmetry']?$v['symmetry']:'';
                        $row['fluor']              = $v['fluor']?$v['fluor']:'';
                        $row['milk']               = $v['milk']?$v['milk']:'';
                        $row['coffee']             = $v['coffee']?$v['coffee']:'';
                        $row['dia_table']          = $v['dia_table']?$v['dia_table']:'';
                        $row['dia_depth']          = $v['dia_depth']?$v['dia_depth']:'';
                        $row['dia_size']           = $v['dia_size']?$v['dia_size']:'';
                        $row['dia_global_price']   = $v['dia_global_price']?$v['dia_global_price']:'';
                        $row['dia_discount']       = $v['dia_discount'] ? ( $v['dia_discount'] + $v['supply_advantage'] ):'';
                        $row['price']              = $v['dia_global_price'] * $dollar_huilv * ( $v['supply_advantage'] + $v['dia_discount'] ) / 100 * $v['weight'];
                        $row['goods_number']       = $v['goods_number']?$v['goods_number']:'1';
                        $row['imageURL']           = $v['imageURL']?$v['imageURL']:'';
                        $row['videoURL']           = $v['videoURL']?$v['videoURL']:'';
                        $row['belongs_id']         = 1;
                        $row['type']               = $v['type']?$v['type']:'';
                        $data[]                    = $row;
                    };
                    $zmobj      = M('goods_luozuan');
                    $zmobj     -> addAll($data,array(),' type ');
                    if( $zmobj -> getError() ){
                        return false;
                    }
                break;
            default:
                return false;
        }
        return true;
    }
    //下架
    public function off_shelves($goods_ids,$type='',$agent_id=0){
        if( !is_array($goods_ids) && count($goods_ids) == 0 ){
            return false;
        }
        if(count($goods_ids) == 1){
            $goods_id_str = $goods_ids[0];
        }else{
            $goods_id_str = implode(',',$goods_ids);
        }
        switch ($type){
            case 'sanhuo':
                $zmobj     = M('goods_sanhuo');
                $zmobj    -> where("supply_goods_id in ($goods_id_str)") -> delete();
                if($zmobj -> getError()){
                    return false;
                }
                $zmobj     = M('goods_sanhuo_cc');
                $zmobj    -> where("supply_goods_id in ($goods_id_str)") -> delete();
                if($zmobj -> getError()){
                    return false;
                }
                break;
            case 'luozuan':
                $zmobj    = M('goods_luozuan');
                $zmobj    -> where("supply_gid in ($goods_id_str)")->delete();
                if($zmobj -> getError()){
                    return false;
                }
                break;
        }
        return true;
    }
    public function on_sale_all($type='',$agent_id=0){}
    public function off_shelves_all($type='',$agent_id=0){}
}