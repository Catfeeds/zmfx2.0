<?php
/**
 * auth	：wsl
 
 * content：基本工费
 * time	：2016-12-19
**/
namespace Common\Model\Dingzhi\Szzmzb;

class BanfangBaseProcess extends Goods{

 	
    //返回列表
    public function getBaseProcessList( $user_type = '0' ){

        $BBPI    = M('banfang_base_process_items');
        $BBPI   -> join(' join zm_banfang_base_process_fee on zm_banfang_base_process_items.base_items_id = zm_banfang_base_process_fee.base_items_id ');

        $fields  = " jewelry_id,jewelry_name,unit_format,remark ";
        $fields .= " ,zm_banfang_base_process_fee.* ";

        $list    = $BBPI -> where(" zm_banfang_base_process_fee.user_type = '$user_type' ") -> field( $fields ) -> select();
        return $list;

    }

    /*返回钻石和彩宝的基本工费列表
     * @param type  0 查询钻石 1查询彩宝
     * @author huanglinfeng  2017/10/10
     * */
    public function getFeeList($type){
        $where = array(
            'i.type'=>$type,
            'f.user_type'=>0,
        );
        $fields  = " i.jewelry_id,i.jewelry_name,i.unit_format,i.remark,i.type,f.*  ";
        $list = M()
              ->table('zm_banfang_base_process_items i')
              ->join('zm_banfang_base_process_fee f on i.base_items_id=f.base_items_id')
              ->field($fields)
              ->where($where)
              ->select();
        return $list;
    }

    //返回列表
    public function getBaseProcessInfoByJewelry( $jewelry_id = '0' , $user_type = '0'){

        $BBPI    = M('szzmzb_base_process_items');
        $BBPI   -> join(' join zm_szzmzb_base_process_fee on zm_szzmzb_base_process_items.base_items_id = zm_szzmzb_base_process_fee.base_items_id ');
        
        $fields  = " jewelry_id,jewelry_name,unit_format,remark ";
        $fields .= " ,zm_szzmzb_base_process_fee.* ";

        $list    = $BBPI -> where(" zm_szzmzb_base_process_items.jewelry_id = '$jewelry_id' and user_type = '$user_type' ") -> field( $fields ) -> find();
        return   $list;

    }


    //需要添加的规格列表
    public function getNeedAddOfJewelryList(){

        $BBPI   =  M('banfang_jewelry');
        $BBPI   -> join(" left join zm_banfang_base_process_items on zm_banfang_base_process_items.jewelry_id = zm_banfang_jewelry.jewelry_id ");
        $fields =  " zm_banfang_jewelry.jewelry_id , zm_banfang_jewelry.jewelry_name ";
        $list   =  $BBPI -> fetchsql(false) -> where(" zm_banfang_base_process_items.base_items_id IS NULL ") -> field( $fields ) -> select();
        return $list;
    }

    //添加一个基础工项目
    public function saveBaseProcess($baseProcesslist,$user_type='0'){
        
        if( empty($baseProcesslist) ){
            return false;
        }
        $base_items_id            = $baseProcesslist['base_items_id'];
        $jewelry_id               = $baseProcesslist['jewelry_id'];
        $unit_format              = $baseProcesslist['unit_format'];
        $remark                   = $baseProcesslist['remark'];
        $type                     = $baseProcesslist['type'];

        $bpf_id                   = $baseProcesslist['bpf_id'];
        $au750_fee_price_general  = $baseProcesslist['au750_fee_price_general'];
        $au750_loss_general       = $baseProcesslist['au750_loss_general'];
        $au750_fee_price_good     = $baseProcesslist['au750_fee_price_good'];
        $au750_loss_good          = $baseProcesslist['au750_loss_good'];
        $pt950_fee_price_general  = $baseProcesslist['pt950_fee_price_general'];
        $pt950_loss_general       = $baseProcesslist['pt950_loss_general'];
        $pt950_fee_price_good     = $baseProcesslist['pt950_fee_price_good'];
        $pt950_loss_good          = $baseProcesslist['pt950_loss_good'];

        $BBPI                     = M('banfang_base_process_items');
        $BBPF                     = M('banfang_base_process_fee');
        if('base_zhuanshi_cost' == $type){
            $style = 0;
        }else if('base_caibao_cost' == $type){
            $style = 1;
        }

        //区分钻石和彩宝基本工费操作 modify huanglinfeng 2017/10/11
        $arr                    = array();
        $arr['base_items_id']   = array('not in',$base_items_id);
        $BBPF -> where($arr)   -> delete();

        $arr['type'] = $style;
        $BBPI -> where($arr)   -> delete();
        unset($arr);

        foreach($base_items_id as $key => $row){
            $data                 = array();
            $data['jewelry_id']   = $jewelry_id[$key];
            $data['jewelry_name'] = M('banfang_jewelry') -> where(" jewelry_id = $jewelry_id[$key] ") -> getField('jewelry_name');
            $data['unit_format']  = $unit_format[$key];
            $data['remark']       = $remark[$key];
            $data['type']         = $style;
            if( !empty( $row ) ){
                $BBPI -> where(" base_items_id = '$row' ") -> save( $data );
            } else {
                $row  = $BBPI -> add( $data );
            }
            $data                            = array();
            $data['user_type']               = $user_type;
            $data['base_items_id']           = $row;
            $data['au750_fee_price_general'] = round($au750_fee_price_general[$key],2);
            $data['au750_loss_general']      = intval($au750_loss_general[$key]);
            $data['au750_fee_price_good']    = round($au750_fee_price_good[$key],2);
            $data['au750_loss_good']         = intval($au750_loss_good[$key]);
            $data['pt950_fee_price_general'] = round($pt950_fee_price_general[$key],2);
            $data['pt950_loss_general']      = intval($pt950_loss_general[$key]);
            $data['pt950_fee_price_good']    = round($pt950_fee_price_good[$key],2);
            $data['pt950_loss_good']         = intval($pt950_loss_good[$key]);
            //同步修改银属性 luohaitao 20170824
            $data['s925_fee_price_general'] = $data['au750_fee_price_general'];
            $data['s925_fee_price_good']    = $data['au750_fee_price_general'];
            if( !empty($bpf_id[$key]) ){
                $BBPF -> where(" bpf_id = '$bpf_id[$key]' ") -> save( $data );
            } else {
                $BBPF -> add( $data );
            }
        }
        return true;
    }

    public function deleteBaseProcess($base_items_id,$user_type='0'){

        $BBPI = M('banfang_base_process_items');
        $BBPF = M('banfang_base_process_fee');
        $BBPI -> where(" base_items_id = '$base_items_id' ") -> delete();
        $BBPF -> where(" base_items_id = '$base_items_id' ") -> delete();
        return true;

    }
}






