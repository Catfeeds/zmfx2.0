<?php
/**
 * 钻明上架下架管理
 * User: Administrator
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Supply\Model;
Class ZmSaleStatusModel extends SaleStatusImplModel{

    //上架
    public function on_sale($goods_ids,$type='',$agent_id=0){
        if( !is_array($goods_ids) && count($goods_ids) == 0 ){
            return false;
        }
        if(count($goods_ids) == 1){
            $goods_id_str = $goods_ids[0];
        }else{
            $goods_id_str = implode(',',$goods_ids);
        }
        $dollar_huilv = D('Config')->getOneZmConfigValue('dollar_huilv');
        switch ($type){
            case 'sanhuo':
                   $res  =  M('supply_goods_sanhuo')->join('
                    join zm_supply_account on zm_supply_goods_sanhuo.agent_id = zm_supply_account.agent_id_build
                   ') -> where("goods_id in ($goods_id_str) and zm_supply_goods_sanhuo.agent_id = $agent_id ") 
                   -> field('zm_supply_goods_sanhuo.*,zm_supply_account.supply_advantage') ->select();
                   $data                       = array();
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
                       $row['goods_status']    = $v['goods_status']?$v['goods_status']:'';
                       $row['cut']             = $v['cut']?$v['cut']:'';
                       $row['goods_weight']    = $v['goods_weight']?$v['goods_weight']:'';
                       $row['goods_price']     = $v['goods_price']?($v['goods_price'] * (1+ $v['supply_advantage']/100)):'';
                       $row['goods_price2']    = $v['goods_price2']?($v['goods_price2'] * (1+ $v['supply_advantage']/100)):'';
                       $row['goods_4c']        = $v['goods_4c']?$v['goods_4c']:'';
                       $row['create_time']     = time();
                       $row['update_time']     = time();
                       $row['goods_quantity']  = $v['goods_quantity']?$v['goods_quantity']:'';
                       $row['supply_id']       = $agent_id;
                       $data[] = $row;
                   };
                   $zmobj     = M('goods_sanhuo','zm_','ZMALL_DB');
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
                   $zmobj     =  M('goods_sanhuo_cc','zm_','ZMALL_DB');
                   $zmobj    -> addAll($data,array(),'');
                   if($zmobj -> getError()){
                        return false;
                   }
                   $sql  = "
                        UPDATE zm_goods_sanhuo_cc,zm_goods_sanhuo set zm_goods_sanhuo_cc.goods_id = zm_goods_sanhuo.goods_id WHERE
                        zm_goods_sanhuo_cc.supply_goods_id = zm_goods_sanhuo.supply_goods_id;
                        DELETE FROM `zm_goods_sanhuo_cc` WHERE goods_id = 0;
                   ";
                   $zmobj->execute($sql);
                   if($zmobj->getError()){
                        return false;
                   }
                break;
            case 'luozuan':
                    $res  = M('supply_goods_luozuan')->join('
                    join zm_supply_account on zm_supply_goods_luozuan.agent_id = zm_supply_account.agent_id_build
                   ') -> where("gid in ($goods_id_str) and zm_supply_goods_luozuan.agent_id = $agent_id") -> field('zm_supply_goods_luozuan.*,zm_supply_account.supply_advantage') -> select();
                    $data = array();
                    foreach($res as $v){
                        $row                       = array();
                        $row['supply_gid']         = $v['gid']?$v['gid']:'';
                        $row['supply_id']          = $agent_id;
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
                        $row['price']              = $v['dia_global_price'] * $dollar_huilv * ( $v['supply_advantage'] + $v['dia_discount'] ) / 100  * $v['weight'];
                        $row['goods_number']       = $v['goods_number']?$v['goods_number']:'0';
                        $row['imageURL']           = $v['imageURL']?$v['imageURL']:'';
                        $row['videoURL']           = $v['videoURL']?$v['videoURL']:'';
                        $row['belongs_id']         = 1;
                        $row['type']               = $v['type']?$v['type']:'';
                        $data[]                    = $row;
                    };
                    $zmobj      = M('goods_luozuan','zm_','ZMALL_DB');
                    $zmobj     -> addAll($data,array(),' type ');
                    if( $zmobj -> getError() ){
                        return false;
                    }
                break;
            case 'goods':
                //主表
                $res  = M('goods') -> join('
                    join zm_supply_account on zm_goods.agent_id = zm_supply_account.agent_id_build
                   ') -> where("goods_id in ($goods_id_str) and zm_goods.agent_id = $agent_id") -> field('zm_goods.*,zm_supply_account.supply_advantage') -> select();
                if(count($res)==0){
                    return false;
                }
                $supply_advantage           = $res[0]['supply_advantage']; //加点
                $chengpin                   = array();
                $dingzhi                    = array();
                foreach($res as $v){
                    $row                    = array();
                    $row['supply_goods_id'] = $v['goods_id'];
                    $row['goods_sn']        = $v['goods_sn'];
                    $row['number']          = '';//批次号
                    $row['goods_number']    = $v['goods_number'];
                    $row['goods_name']      = $v['goods_name'];
                    $row['category_id']     = $v['category_id'];
                    $row['goods_price']     = $v['goods_price']*(100+$supply_advantage)/100;
                    $row['content']         = $v['content'];
                    $row['create_time']     = time();
                    $row['update_time']     = time();
                    $row['thumb']           = $v['thumb'];
                    $row['goods_type']      = $v['goods_type'];
                    $row['supply_id']       = $v['agent_id'];
                    $data[]                 = $row;
                    if( $v['goods_type']    == '3' ){
                        $chengpin[]         = $v['goods_id'];
                    } else if( $v['goods_type'] == '4' ){
                        $dingzhi[]          = $v['goods_id'];
                    }
                }
                M('goods','zm_','ZMALL_DB') -> addAll($data,array(),' goods_id ');
                $res                        = M('goods_associate_attributes') -> where(array('goods_id'=>array('in',$goods_ids))) -> select();
                $data                       = array();
                foreach($res as $v){
                    $row                    = array();
                    $row['category_id']     = $v['category_id'];
                    $row['goods_id']        = 0;
                    $row['supply_goods_id'] = $v['goods_id'];
                    $row['attr_id']         = $v['attr_id'];
                    $row['attr_code']       = $v['attr_code'];
                    $row['attr_value']      = $v['attr_value'];
                    $data[]                 = $row;
                }
                $zmobj = M('goods_associate_attributes','zm_','ZMALL_DB');
                $zmobj -> addAll($data,array(),' goods_id ');
                $sql   = "
                    UPDATE 
                        `zm_goods_associate_attributes`,
                        `zm_goods`
                    SET 
                        `zm_goods_associate_attributes`.goods_id = `zm_goods`.goods_id
                    WHERE 
                        `zm_goods_associate_attributes`.supply_goods_id > 0 and 
                        `zm_goods`.supply_goods_id               = `zm_goods_associate_attributes`.supply_goods_id
                ";
                $zmobj->execute($sql);
                //图片表
                $res  = M('goods_images')        -> where(array('goods_id'=>array('in',$goods_ids))) -> select();
                $data = array();
                foreach($res as $v){
                    $row                    = array();
                    $row['small_path']      = $v['small_path'];
                    $row['big_path']        = $v['big_path'];
                    $row['goods_id']        = 0;
                    $row['supply_goods_id'] = $v['goods_id'];
                    $row['create_time']     = time();
                    $data[]                 = $row;
                }
                $zmobj  = M('goods_images','zm_','ZMALL_DB');
                $zmobj -> addAll($data,array(),' goods_id ');
                $sql    = "
                    UPDATE 
                        `zm_goods_images`,
                        `zm_goods`
                    SET 
                        `zm_goods_images`.goods_id = `zm_goods`.goods_id
                    WHERE 
                        `zm_goods_images`.supply_goods_id > 0 and 
                        `zm_goods`.supply_goods_id = `zm_goods_images`.supply_goods_id
                ";
                $zmobj->execute($sql);
                if( count($chengpin) > 0 ){
                    //成品sku
                    $res  = M('goods_sku') -> where(array('goods_id'=>array('in',$chengpin))) -> select();
                    $data = array();
                    foreach($res as $v){
                        $row                    = array();
                        $row['sku_sn']          = $v['sku_sn'];
                        $row['goods_sn']        = $v['goods_sn'];
                        $row['goods_id']        = 0;
                        $row['supply_goods_id'] = $v['goods_id'];
                        $row['goods_number']    = $v['goods_number'];
                        $row['category_id']     = $v['category_id'];
                        $row['goods_price']     = $v['goods_price']*(100+$supply_advantage)/100;
                        $row['attributes']      = $v['attributes'];
                        $data[]                 = $row;
                    }
                    $zmobj  = M('goods_sku','zm_','ZMALL_DB');
                    $zmobj -> addAll($data,array(),' sku_sn ');
                    $sql    = "
                        UPDATE 
                            `zm_goods_sku`,
                            `zm_goods`
                        SET 
                            `zm_goods_sku`.goods_id        = `zm_goods`.goods_id
                        WHERE 
                            `zm_goods_sku`.supply_goods_id > 0 and 
                            `zm_goods`.supply_goods_id     = `zm_goods_sku`.supply_goods_id
                    ";
                    $zmobj->execute($sql);
                }
                if( count($dingzhi) > 0 ){
                    //主石
                    $res  = M('goods_associate_luozuan') -> where(array('goods_id'=>array('in',$dingzhi))) -> select();
                    $data = array();
                    foreach($res as $v){
                        $row                    = array();
                        $row['goods_id']        = 0;
                        $row['supply_goods_id'] = $v['goods_id'];
                        $row['material_id']     = $v['material_id'];
                        $row['shape_id']        = $v['shape_id'];
                        $row['weights_name']    = $v['weights_name'];
                        $row['price']           = $v['price']*(100+$supply_advantage)/100;
                        $data[]                 = $row;
                    }
                    $zmobj  = M('goods_associate_luozuan','zm_','ZMALL_DB');
                    $zmobj -> addAll($data,array(),' goods_id ');
                    $sql    = "
                        UPDATE 
                            `zm_goods_associate_luozuan`,
                            `zm_goods`
                        SET 
                            `zm_goods_associate_luozuan`.goods_id    = `zm_goods`.goods_id
                        WHERE 
                            `zm_goods_associate_luozuan`.supply_goods_id > 0 and 
                            `zm_goods`.supply_goods_id                   = `zm_goods_associate_luozuan`.supply_goods_id
                    ";
                    $zmobj->execute($sql);
                    //工艺
                    $res   = M('goods_associate_info') -> where(array('goods_id'=>array('in',$dingzhi))) -> select();
                    $data  = array();
                    foreach($res as $v){
                        $row                    = array();
                        $row['goods_id']        = 0;
                        $row['supply_goods_id'] = $v['goods_id'];
                        $row['material_id']     = $v['material_id'];
                        $row['weights_name']    = $v['weights_name'];
                        $row['loss_name']       = $v['loss_name'];
                        $row['basic_cost']      = $v['basic_cost']*(100+$supply_advantage)/100;
                        $data[]                 = $row;
                    }
                    $zmobj  = M('goods_associate_info','zm_','ZMALL_DB'); 
                    $zmobj -> addAll($data,array(),' goods_id ');
                    $sql    = "
                        UPDATE 
                            `zm_goods_associate_info`,
                            `zm_goods`
                        SET 
                            `zm_goods_associate_info`.goods_id        = `zm_goods`.goods_id
                        WHERE 
                            `zm_goods_associate_info`.supply_goods_id > 0 and 
                            `zm_goods`.supply_goods_id                = `zm_goods_associate_info`.supply_goods_id
                    ";
                    $zmobj->execute($sql);
                    //副石
                    $res  = M('goods_associate_deputystone') -> where(array('goods_id'=>array('in',$dingzhi))) -> select();
                    $data = array();
                    foreach($res as $v){
                        $row                      = array();
                        $row['goods_id']          = 0;
                        $row['supply_goods_id']   = $v['goods_id'];
                        $row['deputystone_name']  = $v['deputystone_name'];
                        $row['deputystone_price'] = $v['deputystone_price'];
                        $data[]                   = $row;
                    }
                    //副石
                    $zmobj  = M('goods_associate_deputystone','zm_','ZMALL_DB');
                    $zmobj -> addAll($data,array(),' goods_id ');
                    $sql    = "
                        UPDATE 
                            `zm_goods_associate_deputystone`,
                            `zm_goods`
                        SET 
                            `zm_goods_associate_deputystone`.goods_id        = `zm_goods`.goods_id
                        WHERE 
                            `zm_goods_associate_deputystone`.supply_goods_id > 0 and 
                            `zm_goods`.supply_goods_id                       = `zm_goods_associate_deputystone`.supply_goods_id
                    ";
                    $zmobj->execute($sql);
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
                $zmobj     = M('goods_sanhuo','zm_','ZMALL_DB');
                $zmobj    -> where("supply_goods_id in ($goods_id_str)") -> delete();
                if($zmobj -> getError()){
                    return false;
                }
                $zmobj     = M('goods_sanhuo_cc','zm_','ZMALL_DB');
                $zmobj    -> where("supply_goods_id in ($goods_id_str)") -> delete();
                if($zmobj -> getError()){
                    return false;
                }
                break;
            case 'luozuan':
                $zmobj  = M('goods_luozuan','zm_','ZMALL_DB');
                $zmobj -> where("supply_gid in ($goods_id_str)")->delete();
                if($zmobj -> getError()){
                    return false;
                }
                break;
            case 'goods':
                    M('goods','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                    M('goods_images','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                    M('goods_sku','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                    M('goods_associate_luozuan','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                    M('goods_associate_info','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                    M('goods_associate_deputystone','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                    M('goods_associate_attributes','zm_','ZMALL_DB')->where(array('supply_goods_id'=>array('in',$goods_ids)))->delete();
                break;
        }
        return true;
    }
    public function on_sale_all($type='',$agent_id=0){}
    public function off_shelves_all($type='',$agent_id=0){}
}