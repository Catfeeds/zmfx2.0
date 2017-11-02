<?php
/*
*@note    订单商品MODEL 
*@authoer 张超豪
*@addtime 2016-05-06
*/
namespace Supply\Model;
use Think\Model;
class SupplyOrderLogModel extends Model{
    /*
    *@note    订单商品详情 
    *@authoer 张超豪
    *@addtime 2016-05-06
    */ 
    public function getOrderLog($order_id = 0){
        //查询数据
        $info = $this->where('order_id = '.$order_id)->order('ol_id desc')->select();
        $user = new \Supply\Model\SupplyUserModel();  
        //$szzmzb = M('user', 'zm_', );
        foreach($info as $value){     
            $value['create_time_str'] = date('Y-m-d H:i:s', $value['create_time']);
            if($value['is_szzmzb_caozuo'] == 1){  ////是钻明操作的跳过,不显示
                continue;
            }
            $value['user_name']     = $user->getUserField('username', $value['user_id']);
            $logList[] = $value;                 
        }
        
        return $logList;
    }

    //添加日志
     public function addOrderLog($order_id = 0, $note, $user_id, $agent_id){
        $arr['order_id']    = $order_id;
        $arr['group_id']    = 0;
        $arr['user_id']     = $user_id;
        $arr['create_time'] = time();
        $arr['note']        = $note;
        $arr['agent_id']    = $agent_id;
       
        $res = $this->add($arr);
        if($res){
            return true;
        }else{
            return false;
        }
    }
}