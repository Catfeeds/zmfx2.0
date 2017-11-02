<?php
/**
 * auth	：wsl
 
 * content：附加工费
 * time	：2016-12-19
**/
namespace Common\Model\Dingzhi\Szzmzb;

class BanfangAttachProcess extends Goods{
 	
    public function getAttachProcessList($attach_type='0',$user_type='0'){
         
        $BAPI    = M('banfang_attach_process_items');
        $BAPI   -> join(' join zm_banfang_attach_process_fee on zm_banfang_attach_process_fee.attach_items_id = zm_banfang_attach_process_items.attach_items_id ');
        $BAPI   -> join(' left join zm_banfang_attr on zm_banfang_attr.attr_id = zm_banfang_attach_process_items.attr_id ');
        
        $fields  = "user_type,attach_type,unit_format,attach_item,remark,is_weixiang ";
        $fields .= ",zm_banfang_attach_process_fee.* ";
        $fields .= ",zm_banfang_attr.attr_name";
        $fields .= ",zm_banfang_attach_process_items.attr_id";
        
        $list    = $BAPI -> where(" attach_type = '$attach_type' and user_type = '$user_type' ") -> field( $fields ) -> select();
        return $list;

    }
    public function getAttachProcessListByAttr($attr_id,$user_type="0"){
         
        $BAPI    = M('szzmzb_attach_process_items');
        $BAPI   -> join(' join zm_szzmzb_attach_process_fee on zm_szzmzb_attach_process_fee.attach_items_id = zm_szzmzb_attach_process_items.attach_items_id ');
        $fields  = "user_type,attach_type,unit_format,attach_item,attr_id,remark,is_weixiang";
        $fields .= ",zm_szzmzb_attach_process_fee.*";
        $list    = $BAPI -> where(array('attr_id'=>array('in',$attr_id),'user_type'=>$user_type)) -> field( $fields ) -> select();
        return $list;
 
    }

    //得到微镶费的价格
    public function getWeixiangInfo($user_type="0"){
         
        $BAPI    = M('szzmzb_attach_process_items');
        $BAPI   -> join(' join zm_szzmzb_attach_process_fee on zm_szzmzb_attach_process_fee.attach_items_id = zm_szzmzb_attach_process_items.attach_items_id ');
        $fields  = "user_type,attach_type,unit_format,attach_item,attr_id,remark";
        $fields .= ",zm_szzmzb_attach_process_fee.*";
        $info    = $BAPI -> where(array('is_weixiang'=>'1','user_type'=>$user_type)) -> field( $fields ) -> find();
        return $info;
 
    }


    //添加一个基础工项目
    public function saveAttachProcess($attachProcesslist,$user_type='0'){
        
        if( empty($attachProcesslist) ){
            return false;
        }
        $attach_items_id          = $attachProcesslist['attach_items_id'];
        $attach_type              = $attachProcesslist['attach_type'];
        $unit_format              = $attachProcesslist['unit_format'];
        $attach_item              = $attachProcesslist['attach_item'];
        $attr_id                  = $attachProcesslist['attr_id'];
        $remark                   = $attachProcesslist['remark'];
        $is_weixiang              = $attachProcesslist['is_weixiang'];

        $apf_id                   = $attachProcesslist['apf_id'];
        $attach_items_id          = $attachProcesslist['attach_items_id'];
        $fee_price                = $attachProcesslist['fee_price'];
        $extra_loss               = $attachProcesslist['extra_loss'];

        $BAPI                     = M('banfang_attach_process_items');
        $BAPF                     = M('banfang_attach_process_fee');

        $arr                    = array();
        $arr['attach_items_id'] = array('not in',$attach_items_id);
        $BAPI -> where($arr) -> delete();
        $BAPF -> where($arr) -> delete();

        foreach($attach_items_id as $key => $row){
            $data                    = array();

            $data['attach_type']     = intval($attach_type[$key]);
            $data['unit_format']     = trim($unit_format[$key]);
            $data['attach_item']     = trim($attach_item[$key]);
            $data['attr_id']         = intval($attr_id[$key]);
            $data['remark']          = trim($remark[$key]);
            $data['is_weixiang']     = trim($is_weixiang[$key]);
            if( !empty( $row ) ){
                $BAPI -> where(" attach_items_id = '$row' ") -> save( $data );
            } else {
                $row  = $BAPI -> add( $data );
            }
            
            $data                            = array();
            $data['user_type']               = intval($user_type);
            $data['attach_items_id']         = intval($row);
            $data['fee_price']               = doubleval($fee_price[$key]);
            $data['extra_loss']              = doubleval($extra_loss[$key]);

            if( !empty($apf_id[$key]) ){
                $BAPF -> where(" apf_id = '$apf_id[$key]' ") -> save( $data );
            } else {
                $BAPF -> add( $data );
            }
        }
        return true;
    }

    public function deleteBaseProcess($attach_items_id,$user_type='0'){

        $arr                    = array();
        $arr['attach_items_id'] = array('in',$attach_items_id);
        $BAPI = M('banfang_attach_process_items');
        $BAPF = M('banfang_attach_process_fee');
        $BAPI -> where($arr) -> delete();
        $BAPF -> where($arr) -> delete();
        return false;

    }
}






