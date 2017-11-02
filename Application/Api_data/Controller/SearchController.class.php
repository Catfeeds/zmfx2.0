<?php
namespace Api_data\Controller;
class SearchController extends Api_dataController{


    public function getList(){

		$keyword    = trim(I('keyword'));
        $page_id    = I("page_id",1,'intval');
        $page_size  = I("page_size",10,'intval');
        $goods_type = I("goods_type",0,'intval');
		$keyword    = Search_Filter_var($keyword);
        $uid        = C('uid');

        $data             = array();
        $data['luozuan']  = new \stdclass();
        $data['chanpin']  = new \stdclass();
        $limit            = (($page_id-1)*$page_size).','.$page_size;

		if(!empty($keyword)){
            if(empty($goods_type) || $goods_type == 1){
                $luozuan = D("GoodsLuozuan") -> where("(certificate_number like '%".$keyword."%' OR goods_name like '%".$keyword."%')") -> limit($limit) -> select();
                $count   = D("GoodsLuozuan") -> where("(certificate_number like '%".$keyword."%' OR goods_name like '%".$keyword."%')") -> count();
                if($luozuan){
                    $data['luozuan']              = array();
                    $data['luozuan']['data']      = $luozuan;
                    $data['luozuan']['total']     = $count;
                    $data['luozuan']['page_size'] = $page_size;
		            $data['luozuan']['page_id']   = $page_id;
                }
            }

            $M                    = D('Common/Goods');
            $M                   -> _limit($page_id,$page_size);
            $where['zm_goods.goods_name'] = array('like', "%$keyword%");
            $where['zm_goods.goods_sn']   = array('like', "%$keyword%");
            $where['_logic']              = 'or';
            $M                   -> sql['where']['_complex'] = $where;
            $M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
            $M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
            if(empty($goods_type) || $goods_type == 3 || $goods_type == 4){
                $products = $M -> getList();
                if($products){
                    $products                      = $M -> getProductListAfterAddPoint($products,$uid);
                    $data['chanpin']              = array();  
                    $data['chanpin']['data']      = $products;
                    $data['chanpin']['total']     = $M -> get_count();
                    $data['chanpin']['page_size'] = $page_size;
		            $data['chanpin']['page_id']   = $page_id;
                }
            }
		}
        $this -> echo_data('100','获取成功',$data['chanpin']);     
    }

    public function getKeywords(){

        $agent_id = C('agent_id');
        $res      = M('config_value') -> where(" agent_id = $agent_id and config_key = 'hot_search' ") -> getField('config_value');
        if($res){ 
            $list = explode(',',$res);
            foreach($list as $row){
                $data[] = array('name'=>"$row");
            }
        }else{
            $data = array();
        }
        $this    -> echo_data('100','获取成功',$data);     

    }
}