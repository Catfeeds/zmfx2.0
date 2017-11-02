<?php

/**
 * 产品系列
 * @author 张超豪
 */
namespace Admin\Controller;
use Think\Page;
header("Content-Type: text/html; charset=UTF-8");
class OrderServiceController extends AdminController {

    // 模块跳转
    public function index(){
        foreach ($this->AuthListS AS $k){
            $val = stristr($k,strtolower(MODULE_NAME.'/'.CONTROLLER_NAME));
            if($val and strtolower($val) != strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME)){
               $this->redirect($val);
            }
        }
        $this->log('error', 'A1','',U('Admin/Public/login'));
    }

	public function serviceList($p=1,$n=13){

        $where             = array();
        $where['agent_id'] = C('agent_id');
		
        if( $select_type   = I('select_type') ){
            if( $select_keywords = I('select_keywords') ){
                if( $select_type == '1' ){
                    $where['user_name'] = array('in',"$select_keywords");
                }
                if( $select_type == "2" ){
                    $where['phone']     = array('in',"$select_keywords");
                }
            }
        }else{
            if( $select_keywords   = I('select_keywords') ){
                $w['user_name']    = array('like', "$user_name%");
                $w['phone']        = array('like', "$phone%");
                $w['_logic']       = 'or';
                $where['_complex'] = $where;
            }
        }

		$service_type          = I('service_type');
		if($service_type =='1' || $service_type=='0'|| $service_type=='2'){
			$where['service_type'] = array('in',"$service_type");
		}

		$status = I('status');
		if($status =='1' || $status=='0'|| $status=='2'){
			$where['status']       = array('in',"$status");
		}
 
        if($create_time=I('create_time')){
            $where['create_time']  = array('between',array($create_time,"$create_time 23:59:59"));//array('exp'," create_time >= '$create_time' and create_time <= '$create_time 23:59:59' ");
        }
        $OS    = M('order_service');
        $count = $OS -> where($where) -> count();
        $Page  = new Page($count,$n);
        $this  -> page = $Page -> show();
        $list  =  $OS -> where($where) -> limit($Page->firstRow,$Page->listRows) -> order('create_time desc') -> select();

        foreach($list as &$row){
            $info                   = M('order_goods') -> where("og_id=$row[og_id]") -> field('goods_type,attribute') -> find();
            if( $info['goods_type'] == '3' || $info['goods_type']== '4' ){
                $goods              = unserialize( $info['attribute'] );
                $goods              = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
                $row['goods_name']  = $goods['goods_name'];
                $row['thumb']       = $goods['thumb'];
            }
            if( $info['goods_type'] ==  '1' ){
                $goods              = unserialize($info['attribute']);
                $row['goods_name']  = $goods['goods_name'];
                $row['thumb']       = '';
            }
            if($row['service_type']==0){
                $row['service_type']='其它';
            }
            if($row['service_type']==1){
                $row['service_type']='换货';
            }
            if($row['service_type']==2){
                $row['service_type']='退货退款';
            }
            if($row['status']==0){
                $row['status']='待审核';
            }
            if($row['status']==1){
                $row['status']='已审核';
            }
            if($row['status']==2){
                $row['status']='完成';
            }
        }
        $this -> data = $list;
        $this -> select_type= $select_type;
        $this -> service_type = $service_type;
        $this -> status = $status;
        $this -> create_time = $create_time;
        $this -> select_keywords = $select_keywords;
    	$this -> display();
	}

	public function serviceInfo(){

        $order_service_id          = I('order_service_id','','intval');
        $where                     = array();
        $where['order_service_id'] = $order_service_id;
        $where['agent_id']         = C('agent_id');
        $OS                        = M('order_service');
        $info                      = $OS -> where($where) -> find();
        $order                     = M('order_goods')->where("og_id=$info[og_id]")->field('goods_type,attribute')->find();
        if( $order['goods_type'] == '3' || $order['goods_type']== '4' ){
            $goods                = unserialize($order['attribute']);
            $goods = D('Common/Goods')->completeUrl($goods['goods_id'],'goods',$goods);
            $info['goods_name']   = $goods['goods_name'];
            $info['thumb']        = $goods['thumb'];
        }
        if( $order['goods_type']  == '1' ){
            $goods                = unserialize($order['attribute']);
            $info['goods_name']   = $goods['goods_name'];
            $info['thumb']        = '';
        }
        if( $info['service_type'] == 0 ){
            $info['service_type'] = '其它';
        }
        if( $info['service_type'] == 1 ){
            $info['service_type'] = '换货';
        }
        if( $info['service_type'] == 2 ){
            $info['service_type']='退货退款';
        }
		if(IS_POST){
            $data                     = array();
            $data['admin_reply']      = I('admin_reply','','trim');
            $data['result_type']      = I('result_type','','trim');
            $data['express_company']  = I('express_company','','trim');
            $data['express_number']   = I('express_number','','trim');
            $data['express_price']    = I('express_price','','trim');
            $data['admin_reply_time'] = date('Y-m-d H:i:s');
            $data['admin_uid']        = $_SESSION['admin']['uid'];
    		$data['admin_name']       = $_SESSION['admin']['UserName'];
            $data['status']           = 1;
            $code = $OS -> where($where) -> save($data);
            if(!$OS->getError()){
                $this->success('提交成功',U('Admin/OrderService/serviceList',array('order_service_id'=>$order_service_id)));
            }else{
                $this->error('提交失败');
            }
    	}else{
            $this -> order_service_id = $order_service_id;
            $this -> info = $info;
    	    $this -> display();
		}
	}

    public function serviceOver(){
        $order_service_id          = I('order_service_id','','intval');
        if(IS_POST){
            if($order_service_id>0){
                $data           = array();
                $data['status'] = 2;
                $OS             = M('order_service');
                $code           = $OS -> where(" order_service_id = '$order_service_id' and agent_id = ".C('agent_id')) -> save($data);
                if( !$OS  -> getError() ){
                    $this -> success('关闭成功',U('Admin/OrderService/serviceList',array('order_service_id'=>$order_service_id)));
                }else{
                    $this -> error('关闭失败');
                }
            }
        }
    }
}
