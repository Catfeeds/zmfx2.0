<?php
/**
 * 活动商品模块
 */
namespace Admin\Controller;
use Think\Page;
header("Content-Type: text/html; charset=UTF-8");
class  PromotionController extends GoodsController {
	public function __construct() {
		parent::__construct();
		$this->agent_id = C("agent_id");
	}
	
	/**
	 * auth	：fangkai
	 * content：移动普通产品到活动产品
	 * time	：2016-6-29
	**/
	public function moveGoods(){
		if(IS_AJAX){
			$thisid = I('post.thisid','');
			if(empty($thisid)){
				$result['status'] = 0;
				$result['info'] = '请选择产品';
				$this->ajaxReturn($result);
			}
			$where['goods_id'] = array('in',$thisid);
			
			$thisid = substr($thisid,0,-1);
			$thisid_array = explode(',',$thisid);
			
			$Goods = D('Common/Goods');
			$GoodsActivity = D('Common/GoodsActivity');
			$GS			   = D('Common/GoodsHomeShow');
			$goods_list = $Goods->getGoodsList($where);
			if($goods_list){
				//删除(zm_goods_home_show 表数据)在首页展示的普通商品
				$where_home_show['gid'] = array('in',$thisid);
				$where_home_show['agent_id'] = $this->agent_id;
				$GS->GoodsHomeShowDel($where_home_show);
				
				$action_sign = 0;
				foreach($thisid_array as $key=>$val){
					//检查是否存在活动商品
					$check_where['gid'] = $val;
					$check_where['agent_id'] = $this->agent_id;
					$check = $GoodsActivity->goodsActivityList($check_where);
					if($check){
						continue;
					}
					$add['product_status'] = 1;
					$add['agent_id']		= $this->agent_id;
					$add['gid']			= $val;
					$add['home_show'] = 1;
					$action = $GoodsActivity->GoodsActivityAdd($add);
					if($action == true){
						$action_sign = 1;
					}
				}
				if($action_sign == 1){
					$result['status'] = 100;
					$result['info'] = '操作成功';
					$this->ajaxReturn($result);
				}else{
					$result['status'] = 0;
					$result['info'] = '操作失败';
					$this->ajaxReturn($result);
				}		
			}else{
				$result['status'] = 0;
				$result['info'] = '商品不存在';
				$this->ajaxReturn($result);
			}
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：设置产品首页展示
				type 1：普通产品列表设置首页展示,2：普通产品列表取消首页展示
	 * time	：2016-6-30
	**/
	public function HomeShow(){
		if(IS_AJAX){
			$GS		= D('Common/GoodsHomeShow');
			$Goods  = D('Common/Goods');
			$thisid = I('post.thisid','');
			$type   = I('post.type','','intval');
			if(empty($thisid)){
				$result['status'] = 0;
				$result['info'] = '请选择产品';
				$this->ajaxReturn($result);
			}
			if(!in_array($type,array(1,2))){
				$result['status'] = 0;
				$result['info'] = '操作错误';
				$this->ajaxReturn($result);
			}
			$where['goods_id'] = array('in',$thisid);
			$thisid            = substr($thisid,0,-1);
			$thisid_array      = explode(',',$thisid);
			$chekGoods         = $Goods->getGoodsList($where);
			if($chekGoods){
				//去掉已经在首页展示的普通商品ID,以便存储新增首页展示的普通商品;
				$where_home_show['agent_id'] = $this->agent_id;
				$goodsHomeShowList = $GS->GoodsHomeShowList($where_home_show,'id DESC','','');
				if($goodsHomeShowList){
					$check_id 	   = array_column($goodsHomeShowList,'gid');
					$thisid_array  = array_diff($thisid_array,$check_id);
				}
				switch($type){
					case 1:
						$action_sign = 0;
						foreach($thisid_array as $key=>$val){
							$add['setting_time']= date('Y-m-d H:i:s',time());
							$add['agent_id']	= $this->agent_id;
							$add['gid']			= $val;
							$add['home_show'] 	= 2;
							$action = $GS->GoodsHomeShowAdd($add);
							if($action == true){
								$action_sign = 1;
							}
						}
						if($action_sign == 1){
							$result['status'] = 100;
							$result['info'] = '操作成功';
						}else{
							$result['status'] = 0;
							$result['info'] = '操作失败';
						}
						break;
					case 2:
						unset($where['goods_id']);
						$where['agent_id']	= $this->agent_id;
						$where['gid']		= array('in',$thisid);
						$checkGoods = $GS->GoodsHomeShowList($where);
						if(!$checkGoods){
							$result['status'] = 0;
							$result['info'] = '没有首页展示的产品';
							$this->ajaxReturn($result);
						}
						$action 	= $GS->GoodsHomeShowDel($where);
						if(action == true){
							$result['status']	= 100;
							$result['info'] = '操作成功';
						}else{
							$result['status'] = 0;
							$result['info'] = '操作失败';
						}
						
						break;
				}
				$this->ajaxReturn($result);		
			}else{
				$result['status'] = 0;
				$result['info'] = '商品不存在';
				$this->ajaxReturn($result);
			}
		}
	}
	
	
	/**
	 * auth	：fangkai
	 * content：移动活动产品到普通产品
	 * time	：2016-8-23
	**/
	public function reverseMoveGoods(){
		if(IS_AJAX){
			$thisid = I('post.thisid','');
			if(empty($thisid)){
				$result['status'] = 0;
				$result['info'] = '请选择产品';
				$this->ajaxReturn($result);
			}
			$GA				   	= D('Common/GoodsActivity');
			$where['agent_id'] 	= $this->agent_id;
			$where['gid']	   	= array('in',$thisid);
			$checkGoods = $GA->GoodsActivityList($where);
			if(!$checkGoods){
				$result['status'] = 0;
				$result['info'] = '产品不存在';
				$this->ajaxReturn($result);
			}
			//判断是否存在已上架的产品，如果有，则不给予移动
			foreach($checkGoods as $key=>$val){
				if($val['product_status'] == 1){
					$result['status'] = 0;
					$result['info'] = '存在上架产品，请先下架产品，再移动';
					$this->ajaxReturn($result);
					break;
				}
			}
			$action = $GA->GoodsActivityDelete($where);
			if($action){
				$result['status'] = 100;
				$result['info'] = '操作成功';
			}else{
				$result['status'] = 0;
				$result['info'] = '操作失败';
			}
			$this->ajaxReturn($result);
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：设置活动产品首页展示 type 1：活动列表设置首页展示,2：活动列表取消首页展示
	 * time	：2016-6-30
	**/
	public function activityHomeShow(){
		if(IS_AJAX){
			$GA				= D('Common/GoodsActivity');
			$Goods 			= D('Common/Goods');
			$thisid = I('post.thisid','');
			$type = I('post.type','','intval');
			if(empty($thisid)){
				$result['status'] = 0;
				$result['info'] = '请选择产品';
				$this->ajaxReturn($result);
			}
			if(!in_array($type,array(1,2))){
				$result['status'] = 0;
				$result['info'] = '操作错误';
				$this->ajaxReturn($result);
			}
			$where['gid'] = array('in',$thisid);
			$where['agent_id'] = $this->agent_id;
			$chekGoods = $GA->GoodsActivityList($where,'id DESC','','');
			if($chekGoods){
				if($type == 1){
					foreach($chekGoods as $key=>$val){
						if($val['product_status'] == 0){
							$result['status'] = 0;
							$result['info'] = '存在下架产品，请先上架产品，再设置首页展示';
							$this->ajaxReturn($result);
							break;
						}
					}
				}
				switch($type){
					case 1:
						$save['home_show'] = 2;
						break;
					case 2:
						$save['home_show'] = 1;
						$save['setting_time'] = '';
						break;
				}
				$action = $GA->GoodsActivitySave($where,$save);
				if($action){
					$result['status'] = 100;
					$result['info'] = '操作成功';
					$this->ajaxReturn($result);
				}else{
					$result['status'] = 0;
					$result['info'] = '操作失败';
					$this->ajaxReturn($result);
				}
			}else{
				$result['status'] = 0;
				$result['info'] = '操作失败';
				$this->ajaxReturn($result);
			}
		}
	}
		
	/**
	 * auth	：fangkai
	 * content：活动列表
	 * time	：2016-6-29
	**/
	public function proList(){
		$Productno   	= I('get.Productno','');
		$Productname	= I('get.Productname','');
		$home_show		= I('get.home_show','');
		$GA				= D('Common/GoodsActivity');
		$Goods 			= D('Common/Goods');
		$Goods         -> my_table_name = 'goods';//重要
		
		//组装产品首页展示条件
		if($home_show){
			$Goods				-> set_where( 'ga.home_show'  , $home_show);
			$this->home_show	= $home_show;
		}
		//组装产品编号条件
		if($Productno){
			$Goods				-> set_where( 'zm_goods.goods_sn'  , "like '%$Productno%'" , true);
			$this->Productno	= $Productno;
		}
		//组装产品名称条件
		if($Productname){
			$where['goods_name'] = array('like','%'.$Productname.'%');
			$Goods				-> set_where( 'zm_goods.goods_name'  , "like '%$Productname%'" , true);
			$this->Productname  = $Productname;
		}
		$field					= 'zm_goods.*, ga.home_show, ga.setting_time, ga.product_status as activity_status';
		$Goods					-> set_where( 'ga.agent_id'  , $this->agent_id );
		$Goods					-> _join( ' left join zm_goods_activity as ga on ga.gid = zm_goods.goods_id ');
		
        $count 					= $Goods->get_count();
		$pageid 				= I('get.p',1,'intval');
		$pagesize 				= 12;
		$page           		= new Page($count,$pagesize);
        $this  					-> page  = $page -> show();
		
        $Goods             		-> _limit($pageid,$pagesize);
        $Goods             		-> _order('goods_id','DESC');
		$dataList       		= $Goods -> getList(false,false,$field,false,true);
		if($dataList){
			$dataList = $Goods->getProductListAfterAddPoint($dataList,0);
		}
		$this->dataList 		= $dataList;
		$this->display('Goods:proList');
	}
	
	/**
	 * auth	：fangkai
	 * content：批量上架，批量下架，单件商品上架，单件商品下架
				type： 1 下架， 2 上架
	 * time	：2016-6-29
	**/
	public function proOperation(){
		if(IS_ajax){
			$type = I('post.type','','intval');
			$thisid = I('post.thisid','');
			if(empty($thisid)){
				$result['status'] = 0;
				$result['info'] = '请选择产品';
				$this->ajaxReturn($result);
			}
			if(!in_array($type,array(1,2))){
				$result['status'] = 0;
				$result['info'] = '操作错误';
				$this->ajaxReturn($result);
			}
			$where['agent_id'] = $this->agent_id;
			$where['gid'] = array('in',$thisid);
			$where['promotion']= 2;
			switch($type){
				case 1:
					$where['product_status'] = '1';
					break;
				case 2:
					$where['product_status'] = '0';
					break;
			}
			$GA			= D('Common/GoodsActivity');
			$goods_list = $GA->GoodsActivityList($where);
			if($goods_list){
				switch($type){
					case 1:
						$save['product_status'] = '0';
						$save['setting_time'] = '';
						$save['home_show']	  = 1;
						break;
					case 2:
						$save['product_status'] = '1';
						$save['setting_time'] = date('Y-m-d H:i:s',time());
						break;
				}
				$action = $GA->GoodsActivitySave($where,$save);
				if($action == true){
					$result['status'] = 100;
					$result['info'] = '操作成功';
					$this->ajaxReturn($result);
				}else{
					$result['status'] = 0;
					$result['info'] = '操作失败';
					$this->ajaxReturn($result);
				}
			}else{
				$result['status'] = 0;
				$result['info'] = '操作失败';
				$this->ajaxReturn($result);
			}
			
		}
		
	}

	
	
}