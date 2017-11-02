<?php 

/**
 * 后端数据处理价格格式化
 * @param unknown $price
 * @return string
 */
function formatPrice($price){
    $price = round($price,2);
    return number_format($price,2,'.','');
}
/**
 * 获取产品信息
 * @param int $goods_id
 * @return array
 */
function getGoodsInfo($goods_id,$type,$domain){
    $G = D('Common/Goods');
    $info = $G->get_info($goods_id);
    $info['goods_price'] = formatPrice($info['goods_price']);
    return $info;
}

/**
 * 获取产品图片
 * @param int $goods_id
 * @return array
 */
function getGoodsImages($goods_id,$type,$domain){
    $GI = M('goods_images');
    $list = $GI->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->select();
    foreach ($list as $key => $value) {
        $list[$key]['small_path'] = ltrim($value['small_path'],'.');
        $list[$key]['big_path']   = ltrim($value['big_path'],'.');
    }
    return $list;
}
/**
 *发送邮件
*/
function sendemail($data){      
    if(C('MAIL_SMTP')=='' or C('MAIL_LOGINNAME')=='' or C('MAIL_PASSWORD')==''){            
        return false;
    }

    $address        = $_SESSION['web']['email'];
            
    $MessageModel   = new \Admin\Model\MessageModel();
    $adminname      = getParentIDByUsername();      //      
    $systemAddress  = getParentIDByEmail();
    try{                
        $message_status = $MessageModel  -> sendOrderMsg($data['order_sn'],date('Y年m月d日H时i分'),$_SESSION['web']['username'],$adminname,$address,$systemAddress,$_SESSION['web']['uid'],$data['parent_id']);
    }catch (Exception $e){
        
    }

    if($message_status < 0){
        ///////////通过order_sn找到订单ID
        $Order = M('order');
        $OrderInfo = $Order->where("order_sn = '".$data['order_sn']."' and agent_id =".C('agent_id'))->find();
        $order_id = $OrderInfo['order_id'];
        //////////将失败的信息加入到zm_order_log
        $OL = M('order_log');
        $arr['order_id'] = $order_id;
        //zm_order中,group_id,user_id都是是业务员的信息                   
        $parent_id = M('auth_group_access')->where("uid='".$data['parent_id']."'")->getField('group_id');
        $arr['group_id']    = $parent_id?$parent_id:0;
        $arr['user_id']     = $data['parent_id'];
        $arr['agent_id']    = C('agent_id');
        $arr['create_time'] = time();
        if($message_status == -1){
            $arr['note'] = '客户['.$_SESSION['web']['username'].'],id['.$_SESSION['web']['uid'].']的订单'.$data['order_sn'].',确定订单时的提醒Email发送失败';
        }else if($message_status == -2){
            $arr['note'] = '业务员['.$adminname.'],id['.$data['parent_id'].']的订单'.$data['order_sn'].',确定订单时的提醒Email发送失败';
        }else if($message_status == -3){
            $arr['note'] = '客户['.$_SESSION['web']['username'].'],id['.$_SESSION['web']['uid'].'] 和 业务员['.$adminname.'],id['.$data['parent_id'].']的订单'.$data['order_sn'].'确定订单时的提醒Email发送失败';
        }
        $OL->add($arr); 
    }   
    
}