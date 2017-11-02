<?php
namespace Api_view\Controller;
class OrderServiceController extends Api_viewController{

    //订单列表
	public function oList(){

		$page			            = I('page_id',1,'intval');
        $where                      = array();
        $where['zm_order.agent_id'] = C('agent_id');
        $where['zm_order.order_status'] = '6';
		$where['zm_order.uid']      = C('uid');
		$where['zm_order.order_id'] = array('exp'," in (select zm_order_goods.order_id from zm_order_goods where agent_id = '".C('agent_id')."' and goods_type in (3,4)) ");
        $n				            = '5';
        $firstrow		            = ($page-1)*$n;
        $limit			            = " $firstrow,$n";
        $order_list                 = M('order') ->fetchsql(false)-> where($where) -> group('zm_order.order_id desc') -> limit($limit) -> field('zm_order.order_sn,zm_order.create_time,zm_order.order_id') -> select();
		//echo $order_list;
		foreach($order_list as &$row){
			$re                     = M('order_goods') ->fetchsql(false)->join('
				left join zm_order_service on zm_order_service.og_id = zm_order_goods.og_id
			') -> where('goods_type in (3,4) and zm_order_goods.order_id='.$row['order_id']) -> field('zm_order_goods.og_id,goods_number,goods_type,attribute,zm_order_service.order_service_id as status') -> select();
			foreach($re as $k=>$r){
				$goods                                 = unserialize( $r['attribute'] );
				$goods                                 = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
				$row['goods_list'][$k]['goods_name']   = $goods['goods_name'];
				$row['goods_list'][$k]['goods_number'] = intval($r['goods_number']);
				$row['goods_list'][$k]['thumb']        = $goods['thumb'];
				$row['goods_list'][$k]['status']       = $r['status'];
				$row['goods_list'][$k]['goods_type']   = $r['goods_type'];
				$row['goods_list'][$k]['og_id']        = $r['og_id'];
			}
			$row['create_time'] = date( 'Y-m-d H:i:s', $row['create_time'] );
		}
		if($page>1){
			layout(false); 
			if($order_list){
				$this -> list = $order_list;
				$this->display('oListItem'); 
			}else{
				echo '';
			}
			die;
		}
		$this -> list = $order_list;
  		$this -> display();

    }
    //表单填写
	public function oInfo(){

		$og_id		          = I('og_id',1,'intval');
        $where                = array();
        $where['agent_id']    = C('agent_id');
        $where['og_id']       = $og_id;
		$r                    = M('order_goods') -> where( $where ) -> field('og_id,order_id,goods_type,attribute,goods_price,goods_number') -> find();
		$goods                = unserialize( $r['attribute'] );
		$goods                = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
		$info['goods_name']   = $goods['goods_name'];
		$info['thumb']        = $goods['thumb'];
		$info['goods_number'] = intval($r['goods_number']);
		$info['goods_price']  = $r['goods_price'];
		$info['order_id']     = $r['order_id'];
		$info['og_id']        = $r['og_id'];
		$this -> info         = $info;
  		$this -> display();
    }

    //表单填写
	public function sumitService(){

		$og_id                = I('og_id','','trim');
		$info                 = M('order_service') -> where("og_id = '$og_id'")->find();
		if($info){
			$data['ret']      = 0;
			$data['msg']      = '不要重复提交';
			$this->ajaxReturn($data);
			die;
		}
		$data                 = array();
		$data['order_id']     = I('order_id','','trim');
		$data['og_id']        = I('og_id','','trim');
		$data['service_type'] = I('service_type','','trim');
		$data['description']  = I('description','','trim');
		$data['user_id']      = $_SESSION['app']['uid'];
		$data['user_name']    = $_SESSION['app']['username'];
		$data['phone']        = $_SESSION['app']['phone'];
		$data['agent_id']     = C('agent_id');
		$info                 = array();
		if(M('order_service') -> add($data)){
			$info['ret']      = 100;
			$info['msg']      = '操作成功';
			$this->ajaxReturn($info);
		}else{
			$info['ret']      = 0;
			$info['msg']      = '操作失败';
			$this->ajaxReturn($info);
		}
		die;
    }

	public function overService(){
        $order_service_id   = I('order_service_id','','intval');
		$info               = array();
		if($order_service_id>0){
			$data           = array();
			$data['status'] = 2;
			$code           = M('order_service') -> where(" order_service_id = '$order_service_id' and agent_id = ".C('agent_id')) -> save($data);
			if( $code ){
				$info['ret'] = 100;
				$info['msg'] = '操作成功';
			}else{
				$info['ret'] = 0;
				$info['msg'] = '操作失败';
			}

		}
		$this->ajaxReturn($info);die;
    }

    //订单列表
	public function sList(){
		$page			                = I('page_id',1,'intval');
        $where                          = array();
        $where['zm_order.agent_id']     = C('agent_id');
        $where['zm_order.order_status'] = '6';
		$where['zm_order.uid']          = C('uid');
		$where['zm_order.order_id']     = array('exp'," in (select zm_order_service.order_id from zm_order_service where agent_id = '".C('agent_id')."') ");
        $n				   = '5';
        $firstrow		   = ($page-1)*$n;
        $limit			   = " $firstrow,$n";
        $order_list        = M('order') -> where( $where ) -> group('zm_order.order_id desc') -> limit($limit) -> field('zm_order.order_id,zm_order.order_sn,zm_order.create_time') -> select();
		//var_dump($order_list);
		foreach($order_list as &$row){
			$re = M('order_goods') ->fetchsql(false)->join('
		    	join zm_order_service on zm_order_service.og_id = zm_order_goods.og_id
			') -> where('goods_type in (3,4) and zm_order_goods.order_id='.$row['order_id']) -> field('zm_order_service.create_time,zm_order_goods.og_id,goods_number,goods_type,attribute,zm_order_service.order_service_id,zm_order_service.status') -> select();
			foreach($re as $k=>$r){
				$goods                                 = unserialize( $r['attribute'] );
				$goods                                 = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
				$row['goods_list'][$k]['goods_name']   = $goods['goods_name'];
				$row['goods_list'][$k]['goods_number'] = intval($r['goods_number']);
				$row['goods_list'][$k]['thumb']        = $goods['thumb'];
				$row['goods_list'][$k]['status']       = $r['status'];
				$row['goods_list'][$k]['goods_type']   = $r['goods_type'];
				$row['goods_list'][$k]['og_id']        = $r['og_id'];
				$row['goods_list'][$k]['create_time']      = $r['create_time'];
				$row['goods_list'][$k]['order_service_id'] = $r['order_service_id'];
			}
		}
		if($page>1){
			layout(false); 
			if($order_list){
				$this -> list = $order_list;
				$this -> display('sListItem'); 
			} else {
				echo '';
			}
			die;
		}
		$this -> list = $order_list;
 		$this -> display();
    }
    //表单填写
	public function sInfo(){

		$order_service_id        = I('order_service_id',1,'intval');
        $where                   = array();
        $where['zm_order_service.agent_id'] = C('agent_id');
		$where['zm_order_service.user_id']  = $_SESSION['app']['uid'];
        $where['zm_order_service.order_service_id']   = $order_service_id;
		$info                    = M('order_service') -> join('
			join zm_order on zm_order.order_id = zm_order_service.order_id
			join zm_order_goods on zm_order_goods.og_id = zm_order_service.og_id
		')->fetchsql(false) -> where( $where ) -> field('zm_order_service.*,order_sn,attribute') -> find();
		$goods                   = unserialize( $info['attribute'] );
		$goods                   = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
		$info['goods_name']      = $goods['goods_name'];
		$info['thumb']        	 = $goods['thumb'];
		$this -> info            = $info;
  		$this -> display();
    }

}
