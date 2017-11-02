<?php
/**
 * 历史记录模型
 * Created by PhpStorm.
 * User: Sunyang
 * Date: 2017/6/15
 * Time: 11:48
 */
namespace Common\Model;
use Think\Model;
class HistoryModel extends Model{
    public function addHistory($param){
        $uid        = $param['uid']>0                 ? $param['uid']             : 0;
        $agent_id   = isset($param['agent_id'])       ? $param['agent_id']        : C('agent_id');
        $gid        = $param['gid']                   ? $param['gid']             : 0;
        $session_id = $param['session_id']            ? $param['session_id']      : '';
        //0彩砖，1白钻，2散货，3成品定制,4品牌
        $goods_type = $param['goods_type']            ? $param['goods_type']      : 0;
        $return = array(
            'status'=>0,
            'msg'=>'添加历史记录失败'
        );
        if($gid<=0 || ($uid<=0 && empty($session_id)) || $agent_id<=0){
            return $return;
        }
        $where = 'uid="'.$uid.'"';
        if($uid<=0){
            $where .= ' AND session="'.$session_id.'"';
        }
        $where                 .= ' AND goods_id = '.$gid;
        $history_zhis_data         = $this  -> where($where)->find();

        $now_time                  = time();
        $data                      = array();
        $data['goods_id']          = $gid;
        $data['agent_id']          = $agent_id;
        $data['uid']               = $uid;
        $data['edit_time']         = $now_time;
        $data['session']           = $uid>0 ? '' : $session_id;
        $data['goods_type']        = $goods_type;
        if(!$history_zhis_data){
            $data['create_time']   = $now_time;
            $data['h_count']       = 1;
        }else{
            //60秒内重进进入则总计数不加1
            $data['h_count']       = ($history_zhis_data['edit_time']+60<$now_time) ? $history_zhis_data['h_count']+1 : $history_zhis_data['h_count'];
        }
        if($history_zhis_data){
            $return = array(
                'status'=>100,
                'msg'=>'修改成功'
            );
            $history_id = $history_zhis_data['history_id'];
            $this -> where($where) -> save($data);
        }else{
            $return = array(
                'status'=>100,
                'msg'=>'添加成功'
            );
            $history_id = $this -> data($data) -> add();
        }
        $return['id'] = $history_id;
        return $return;
    }
    //获取历史记录 lists列表  count总记录数
    public function getHistoryLists($param){
        $data = array(
            'lists'=>array(),
            'count'=>0
        );
        $uid        = isset($param['uid'])            ? $param['uid']             : 0;
        $agent_id   = isset($param['agent_id'])       ? $param['agent_id']        : C('agent_id');
        $gid        = $param['gid']                   ? $param['gid']             : 0;
        $session_id = $param['session_id']            ? $param['session_id']      : '';
        $goods_type = isset($param['goods_type'])     ? $param['goods_type']      : -1;
        $limit      = $param['limit']                 ? $param['limit']           : '';
        $M = D('Common/Goods');
        if(($uid<=0 && empty($session_id)) || $agent_id<=0){
            return null;
        }
        $where = 'h.uid="'.$uid.'"';
        if($uid<=0){
            $where .= ' AND h.session="'.$session_id.'"';
        }
        if($goods_type>-1){
            $where .= ' AND h.goods_type="'.$goods_type.'"';
        }
        $where .= ' And h.goods_id <> '.$gid.' AND h.agent_id='.$agent_id;
        $data['count'] = $this
            ->alias('h')
            ->join(' left join zm_goods g on h.goods_id = g.goods_id  left join zm_goods_activity ga on ga.gid = h.goods_id and ga.agent_id = ' . $agent_id)
            ->where($where)
            ->count();
        if(!empty($limit)){
            $goodsHistorys = $this
                ->alias('h')
                ->join(' left join zm_goods g on h.goods_id = g.goods_id  left join zm_goods_activity ga on ga.gid = h.goods_id and ga.agent_id = ' . $agent_id)
                ->where($where)
                ->field('g.*, ga.product_status as activity_status , h.edit_time , h.history_id ')
                ->limit($limit)
                ->order('h.edit_time desc,h.history_id desc')
                ->select();
        }else{
            $goodsHistorys = $this
                ->alias('h')
                ->join(' left join zm_goods g on h.goods_id = g.goods_id  left join zm_goods_activity ga on ga.gid = h.goods_id and ga.agent_id = ' . $agent_id)
                ->where($where)
                ->field('g.*, ga.product_status as activity_status , h.edit_time ')
                ->order('h.edit_time desc,h.history_id desc')
                ->select();
        }
        if(!empty($goodsHistorys)){
            foreach( $goodsHistorys as $key=>$row ){
                if( !empty($row['goods_id']) ){
                    $point = $M -> calculationPoint($row['goods_id']);
                    if( $point ){
                        $row['goods_price'] = ( 100 + $point ) / 100 * $row['goods_price'];
                    }
                    $goodsHistorys[$key]    = $M -> completeUrl($row['goods_id'],'goods',$row);
                }else{
                    unset($goodsHistorys[$key]);
                }
            }
            $data['lists']             = $M -> getProductListAfterAddPoint($goodsHistorys,$_SESSION['web']['uid']);
        }
        return $data;
    }

    public function getMoreHistoryLists($param){
        $today = strtotime(date('Y-m-d'));
        $yesterday = $today-3600*24;
        $before_yesterday = $today-3600*24*2;
        $data = array(
            '0'=>array(
                'name'=>'今天',
                'date'=>date('Y-m-d',$today),
                'lists'=>array(),
                'count'=>0
            ),
            '1'=>array(
                'name'=>'昨天',
                'date'=>date('Y-m-d',$yesterday),
                'lists'=>array(),
                'count'=>0
            ),
            '2'=>array(
                'name'=>'前天',
                'date'=>date('Y-m-d',$before_yesterday),
                'lists'=>array(),
                'count'=>0
            ),
            '3'=>array(
                'name'=>'更早之前',
                'date'=>'',
                'lists'=>array(),
                'count'=>0
            )
        );
        $historys = $this->getHistoryLists($param);
        if(!empty($historys['lists'])){
            foreach($historys['lists'] as $info){
                $info['now_price'] = $info['goods_price'];
                if(in_array($info['activity_status'],array('0','1'))){
                    $info['now_price'] = $info['activity_price'];
                }
                $key_data = 3;
                if($info['edit_time']>$today){
                    $key_data = 0;
                }elseif($info['edit_time']>$yesterday){
                    $key_data = 1;
                }elseif($info['edit_time']>$before_yesterday){
                    $key_data = 2;
                }
                $data[$key_data]['lists'][] = $info;
                $data[$key_data]['count']++;
            }
        }
        return $data;
    }
}