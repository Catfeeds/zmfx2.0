<?php
namespace Supply\Model;
use Think\Model;
class OrderModel extends Model{

    // 数据验证
    public $_validate	=	array(
        
    );

    // 自动对密码加密
    public $_auto		=	array(
      
    );


    /*
    *@note    订单列表 
    *@authoer 张超豪
    *@addtime 2016-06-20
    */
    public function getDaichuliNum($agent_id){ 
        $where_zm['agent_id']  = array('eq', 0);  //必需是钻明客户下的订单，不能是分销商的采购单
        $ogwhere['attribute']  = array('like', '%;s:9:"supply_id";s:'.strlen($agent_id).':"'.$agent_id.'";%');      
        $og_zm = M('order_goods', 'zm_', 'ZMALL_DB') ->distinct(true)->where($ogwhere)->field(array('order_id', 'attribute', 'fahuo_time', 'have_goods', 'to_supply'))->select();
        $tempArr = array();
        if($og_zm){
            foreach($og_zm as $key=>$val){
                $goods_attr = unserialize($val['attribute']);
                if($goods_attr['supply_id'] != $agent_id){
                    continue;
                }
                if($val['have_goods'] == 2  ){ //无货跳过
                    continue;
                }
                if($val['have_goods'] == 1 and $val['fahuo_time'] > 0 and $val['to_supply'] == 1){//确定有货，但没有确定发货时间
                    continue;
                }
                $tempArr[] = $val['order_id'];
            }
        }
       
        
        if($tempArr){
            $orderIds = implode(',',$tempArr);
            $where_zm['order_id'] = array('in', $orderIds);
        }else{
            $where_zm['order_id'] = 0;
        }
        $where_zm['order_status'] = array('eq', 0);  //订单状态是0 才可以
        
        $totalnum_zm = M('order', 'zm_', 'ZMALL_DB')->where($where_zm)->count();
        

       $og_fenxiao = M('order_goods') ->distinct(true)->where($ogwhere)->field(array('order_id', 'attribute', 'fahuo_time', 'have_goods'))->select();
        $where_fenxiao['order_status'] = array('eq', 0);   
        $tempArr = array();
        if($og_fenxiao){
            foreach($og_fenxiao as $key=>$val){
                $goods_attr = unserialize($val['attribute']);
                if($goods_attr['supply_id'] != $agent_id){
                    continue;
                }
                $tempArr[] = $val['order_id'];
            }
        }
        $tempArr = array_unique($tempArr) ;
        if($tempArr){
            $orderIds = implode(',',$tempArr);
            $where_fenxiao['order_id'] = array('in', $orderIds);
           
        }else{
            $where_fenxiao['order_id'] = 0;
        }
       
        $where_fenxiao['is_yiji_caigou'] = array('eq', 0);
        $where_fenxiao['is_erji_caigou'] = array('eq', 0);
        $totalnum_fenxiao  = M('order')->where($where_fenxiao)->count();      
        
        return $totalnum_fenxiao + $totalnum_zm;
           
        


    }
    /*
    *@note    订单列表 
    *@authoer 张超豪
    *@addtime 2016-05-03
    */
	public function getTraderOrderList($where,$sort='order_id desc',$pageid=1,$pagesize=10,$agent_id=0) {              
        $totalnum  = $this->where($where)->count();
        $limit     = (($pageid-1)*$pagesize).','.$pagesize;
        $orderList = $this->where($where)->order($sort)->limit($limit)->select();   
        
        foreach($orderList as $value){            
            $value['create_time_str']  = date('Y-m-d H:i', $value['create_time']); 
            $value['order_status_str'] = L('index_order_daichuli');
            $goodslist = M('order_goods')->where('attribute like \'%;s:9:"supply_id";s:'.strlen($agent_id).':"'.$agent_id.'";%\' and order_id = '.$value['order_id'])->select();
            $value['order_price'] = 0;

            $goodsStatus = 0;
            $goodsnum = count($goodslist);
            foreach($goodslist as $v){
                if($v['goods_price_up'] > 0){
                    $value['order_price'] = $value['order_price'] + $v['goods_price_up'];
                }else{
                    $value['order_price'] = $value['order_price'] + $v['goods_price'];
                }
                if( $v['have_goods'] > 0){
                    $goodsStatus = $goodsStatus + 1;
                // }else if( $v['have_goods'] == 1 and $v['fahuo_time'] > 0){
                //     $goodsStatus = $goodsStatus + 1;
                }
               
            }

            if($goodsStatus == 0){
                $value['yichuli'] = L('index_order_daichuli');
            }elseif($goodsStatus == $goodsnum){
                $value['yichuli'] = L('index_order_yichuli');
            }else{
                $value['yichuli'] = L('index_order_chulizhong');
            }

          

            $list[] = $value;
        }
   
        $data['total']        = $totalnum;
        $data['page_size']    = $pagesize;
        $data['page_id']      = $pageid;
        $data['list']         = $list;
        $data['lanmu_status'] = 'kehusearch';
        return $data;
    }


    /*
    *@note    订单列表 
    *@authoer 张超豪
    *@addtime 2016-05-03
    */
    public function getZMOrderList($where,$sort='order_id desc',$pageid=1,$pagesize=10,$agent_id=0){
		/*
        $O = M('order', 'zm_', 'ZMALL_DB');
        $totalnum  = $O->where($where)->count();
        $limit     = (($pageid-1)*$pagesize).','.$pagesize;
        $orderList = $O->where($where)->order($sort)->limit($limit)->select();   
		*/
 
	  	$Dao = M();
		$totalnum = $Dao->query('select count(distinct o.order_id)  as  totalnum from zuanming.zm_order o, zuanming.zm_order_goods og where og.order_id = o.order_id '.$where);
		if($totalnum[0]['totalnum'])	$totalnum =$totalnum[0]['totalnum'];
		else	$totalnum =0;
        $limit     = (($pageid-1)*$pagesize).','.$pagesize;
		$orderList = $Dao->query('select o.*  from zuanming.zm_order o, zuanming.zm_order_goods og where og.order_id = o.order_id '.$where.' Group By o.order_id '."limit $limit");		

        foreach($orderList as $value){            
            $value['create_time_str']  = date('Y-m-d H:i', $value['create_time']); 
            $value['order_status_str'] = L('index_order_daichuli');
            $goodslist = M('order_goods', 'zm_', 'ZMALL_DB')->where('attribute like \'%;s:9:"supply_id";s:'.strlen($agent_id).':"'.$agent_id.'";%\' and order_id = '.$value['order_id'])->select();
            $value['order_price'] = 0;

            $goodsStatus = 0;
            $goodsnum = count($goodslist);

            foreach($goodslist as $v){
                $value['order_price'] = $value['order_price'] + getZMorderDollar($v);
                
                if( $v['have_goods'] > 0){
                    $goodsStatus = $goodsStatus + 1;
                // }else if( $v['have_goods'] == 1 and $v['fahuo_time'] > 0){
                //     $goodsStatus = $goodsStatus + 1;
                }
            }

            if($goodsStatus == 0){
                $value['yichuli'] = L('index_order_daichuli');
            }elseif($goodsStatus == $goodsnum){
                $value['yichuli'] = L('index_order_yichuli');
            }else{
                $value['yichuli'] = L('index_order_chulizhong');
            }

            $list[] = $value;
        }
   
        $data['total']        = $totalnum;
        $data['page_size']    = $pagesize;
        $data['page_id']      = $pageid;
        $data['list']         = $list;
        $data['lanmu_status'] = 'treatment';
        return $data;
    }

    

}