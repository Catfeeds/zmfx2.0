<?php
/**
* 搜索管理
* auth: zengmingming
* time: 2017/7/29/
**/
namespace Mobile\Controller;
class NewSearchController extends NewMobileController{
	/**
	 * auth	：zengmingming
	 * content：搜索列表
	 * time	：2017-07-31
	**/
	public function searchList(){
		$keyword    = trim(I('keyword'));
		$keyword    = Search_Filter_var($keyword);
        $uid        = C('uid');
		$pageSize	= 10;
		if(!empty($keyword)){
			$luozuan = D("Goods_luozuan")->where("certificate_number='".$keyword."' or goods_name='".$keyword."'")->field("gid")->find();
			if($luozuan["gid"]!=false){
				//跳转至裸钻页面
				
			}else{
				$M                    = D('Common/Goods');
				$M                   -> _limit(1,$pageSize);
				$where['zm_goods.goods_name'] = array('like', "%$keyword%");
				$where['zm_goods.goods_sn']   = array('like', "%$keyword%");
				$where['_logic']              = 'or';
				$M                   -> sql['where']['_complex'] = $where;
				$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
				$M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
				$products = $M -> getList();
				if($products){
					$this->goodsList	= $M -> getProductListAfterAddPoint($products,$uid);
					$this->getCount		= $M -> get_count();
				}
			}
		}
		$this->assign("pageSize",$pageSize);
		$this->assign("keyword",$keyword);
		$this->display();
	}
	/**
	 * auth	：zengmingming
	 * content：ajax获取搜索列表
	 * time	：2017-07-31
	**/
	public function getSearchList(){
		$M          = D('Common/Goods');
		$getCount	= I('getCount','','int');
		$goodsPage	= I('goodsPage','','int');
		$keyword    = trim(I('searchKeyword'));
		$keyword    = Search_Filter_var($keyword);
		$pageSize 	= 10;
		if($start>$getCount){
			$result = array('success'=>true, 'data'=>$data,'page'=>$start);
			$this->ajaxReturn($result);
			$result = array('success'=>false, 'msg'=>"已无数据");
		}else{
			$M					-> _limit($goodsPage,$pageSize);
			$where['zm_goods.goods_name'] = array('like', "%$keyword%");
			$where['zm_goods.goods_sn']   = array('like', "%$keyword%");
			$where['_logic']              = 'or';
			$M                   -> sql['where']['_complex'] = $where;
			$M         -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
			$M         -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
			$products = $M -> getList();
			if($products){
				$data	= $M -> getProductListAfterAddPoint($products,$uid);
			}
			$newPage = $goodsPage+1;
			$result = array('success'=>true, 'data'=>$data,'page'=>$newPage);
			if($data[0]['goods_id']!=""){
				$result = array('success'=>true, 'data'=>$data,'page'=>$newPage);
			}else{
				$result = array('success'=>false, 'msg'=>"已无数据");
			}
		}
		$this->ajaxReturn($result);
	}
	
	
	
	
}