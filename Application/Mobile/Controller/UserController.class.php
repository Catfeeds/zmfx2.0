<?php
namespace Mobile\Controller;
class UserController extends MobileController{
    
    /**
     * 用户中心，用户首页
     */
    public function index(){
        isUser();
        $this->display();
    }
    
    /**
     * 加入分销商
     */
    public function traderAdd(){
        $se = isUser();
        $m  = M('trader');
        if(IS_POST) {
            $data = $m->where('create_user_id = "'.$se['uid'].'"')->find();
            if(is_array($data)){
                if(!$data['status']){
                    $this->success("你已经提交申请，请等待管理员审核...",'/User/traderAdd',3);
                }else{
                    $this->success("你的申请已经通过，已经成为了我们的分销商...",'/User/traderAdd',3);
                }
            }else{
                $data = $_POST;
                $data['create_user_id'] = $se['uid'];
                $data['create_time']    = time();
                $data['parent_id']      = $this->trader_id;
                $data['agent_id']       = C('agent_id');
                $m    -> field('parent_id,trader_name,company_name,contacts,phone,business_license,funds,country_id,province_id,city_id,note,create_user_id,create_time,agent_id')->filter('htmlspecialchars') ->data($data)->add();
                $this -> success('申请提交成功，我们的工作人员需要一段时间进行审核处理，请您耐心等待。','/User/traderAdd',3);
            }
        }else{
            $this->display();
        }
    }
    
    /**
     * 用户信息
     */
    public function UserInfo(){
        $se = isUser();
        $U  = M('user');
        if(IS_POST){
            $data              = $_POST;
            $birthday          = $data['year'].'-'.$data['month'].'-'.$data['day'];
            $data['birthday']  = strtotime($birthday);
            $uid = $U->where( ' email = "'.I('post.email').'" and uid <> '.$se['uid'].' and agent_id = '.C('agent_id'))->field('uid')->select();;
            if(count($uid)!==0){
                $this->error('修改失败，您写入的邮箱已经被他人使用。');die;
            }
            $uid = $U->where( ' phone = "'.I('post.phone').'" and uid <> '.$se['uid'].' and agent_id = '.C('agent_id'))->field('uid')->select();;
            if(count($uid)!==0){
                $this->error('修改失败，您写入的手机号已经被他人使用。');die;
            }
            $U    -> where('uid='.$se['uid'].' and agent_id = '.C('agent_id'))->field('birthday,sex,email,phone,realname,job,company_name,legal,company_address')->filter('htmlspecialchars')->save($data);
            $this -> info = $U->find($se['uid']);
            $this -> success('操作成功','/User/UserInfo',2);
        }else{
            $this -> info = $U->find($se['uid']);
            $this -> display();
        }
    }
    
    /**
     * 用户地址列表
     */
    public function addressList(){
        $se              = isUser();
        $m               = M('user_address');
        if(IS_POST){
            $data        =  $_POST;
            $data['uid'] =  $se['uid'];
            $data['agent_id'] =  C('agent_id');
            if($data['is_default']){
                $m -> where('uid = '.$se['uid']) -> field('is_default')->save(array('is_default'=>'0'));
            }
            $code        =  $m -> field('uid,title,country_id,province_id,city_id,district_id,address,name,phone,code,is_default,agent_id')->filter('htmlspecialchars')->data($data)->add();
            if ($code) {
                $result  = array('error'=>'no','msg'=>L('L9071'), 'backUrl'=>'addressList.html');
            }
            $this       -> ajaxReturn($result);
        }else{
            $this ->list = $m->where('uid = '.$se['uid'].' and agent_id = '.C('agent_id'))->select();
            $this ->display();
        }
    }

    /**
     * 删除用户地址
     */
    public function deleteAddress(){
        $se              = isUser();
        $m               = M('user_address');
        if(IS_POST){
            $address_id   = intval($_POST['id']);
            $uid          = intval($se['uid']);
            $code         = $m -> where(' address_id = '.$address_id.' and uid = '.$uid.' and agent_id = '.C('agent_id'))->delete();
            if ($code) {
                $result  = array('error'=>'no','msg'=>"删除成功", 'backUrl'=>'addressList.html');
            }
            $this       -> ajaxReturn($result);
        }
    }

    /**
     * 设置默认地址
     */
    public function setDefaultAddress(){
        $se               = isUser();
        $m                = M('user_address');
        if(IS_POST){
            $address_id   = intval($_POST['id']);
            $uid          = intval($se['uid']);

            $m -> where(' uid = '.$uid.' and agent_id = '.C('agent_id'))->save(array('is_default'=>0));
            $code         = $m -> where(' address_id = '.$address_id.' and uid = '.$uid.' and agent_id = '.C('agent_id'))->save(array('is_default'=>1));

            if ($code) {
                $result   = array('error'=>'no','msg'=>"更新成功", 'backUrl'=>'addressList.html');
            }
            $this       -> ajaxReturn($result);
        }
    }
    
    /**
     * 用户订单列表
     */
    public function orderList(){
        $se                = isUser();
        $this -> orderStatus = orderStatus();
        if(IS_GET){
            $where_items = array();
            foreach($_GET as $k=>$row){
                $this->$k = $row;
                if($this->$k!=='') {
                	if($k=='order_status'){
                		$where_items[$k] = array('eq', $row);
                    }
                    elseif($k!='page'){
                    	$where_items[$k] = array('like', '%'.$row.'%');//模糊查询，eq 是精确查询
                    }
                }
            }
            if($where_items['certificate_no']){
            	$temp = $where_items['certificate_no'][1];
            	$pos = strpos($temp, '_');	
            	$len = strlen($temp);
            	if($pos)	//如果证书号包含"GIA_",则去除
            		$where_items['certificate_no'][1] = '%'.substr($temp, $pos+1, $len);
            }

            $this->order_status = I('get.order_status') ;
            $this->certificate_no = I('get.certificate_no') ;
            $this->order_sn = I('get.order_sn') ;
        }
        
        if(isset($_GET['page'])&&is_numeric($_GET['page'])){
            $this->page = $_GET['page'];
        }else{
            $this->page = 1;
        }
        
        list($this ->total_page,$this ->list) = getUserOrderList($se['uid'],$where_items,$this->page);

        $this ->display();
    }
    
    /**
     * 用户订单信息
     * @param int $order_id
     */
    public function orderInfo($order_id){
        $se     = isUser();
        getUserOrderInfo($se['uid'], $order_id,$this);
        $this->userdiscount = getUserDiscount($_SESSION['m']['uid']);
        $this -> display();
    }
    
    //检测用户登录情况 
    public function checkUserLogin(){  
    	$data = array('status=0');            
    	if($_SESSION['m']['uid']){
    		$data['status'] = 1;
    	}               
    	$this->ajaxReturn($data);
    }

    /**
     * 确认收获
     * @param int $order_id
     */
    public function confirmReceive($order_id){
        $se    = isUser();
        setConfirmReceive($se['uid'],intval($order_id));
		
		//确认收货，发送信息给商家         2017-08-24		  zengmingming
		$order = M('order')->where("agent_id='".C('agent_id')."' and order_id='$order_id' AND uid='".$se['uid']."'")->field("order_sn")->find();
		$nowAgent_id = C('agent_id');
		$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
		if($sms_agent_info_arr["sms_push_reminder"]){
			$SMS = new \Common\Model\Sms();
			$SMS->SendSmsByType($SMS::USER_CONFIRM_RECEIPT,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
		}
		
        $result = array('error'=>'no','msg'=>"操作成功", 'backUrl'=>$_SERVER['HTTP_REFERER']);
        $this->ajaxReturn($result);
    }
	
    public function getParentIDByMUID(){
		return M('user')->where("uid='".$_SESSION['m']['uid']."' and agent_id = ".C('agent_id'))->getField('parent_id');
	}

     public function updateOrder(){
        $order_id = I('order_id',0);
        $order_status = I('order_status',0);
        $href = I('href');
        $where = "agent_id='".C('agent_id')."' and uid='".$_SESSION['m']['uid']."' AND order_id=".$order_id;
        $data = array('status'=>0,'msg'=>'订单取消错误','href'=>$href);
        $order = M('order')->where($where)->find();
        if(!$order || $order_id == 0){
            $this->ajaxReturn($data);
        }else{           
           switch($order_status){
    			case -1:
    				$order['order_status'] = -2;
    				$data['msg'] = "订单删除成功";
    			break;
    			case 0:
    				$order['order_status'] = -1;
    				$data['msg'] = "取消订单成功";
    			break;
    			case 4:
    				$order['order_status'] =5;
    				$data['msg'] = "确认收货成功";
    			break;
    		}
			if($order_status=='-1'){
				  M('order')->data($order)->delete();
			}elseif(M('order')->data($order)->save()){
			
				//取消订单，发送信息给商家
				$nowAgent_id = C('agent_id');
				$sms_agent_info_arr = M("sms_agent_info")->where("agent_id=".$nowAgent_id)->field("sms_push_reminder")->find();
				if($sms_agent_info_arr["sms_push_reminder"]){
					$SMS = new \Common\Model\Sms();
					$SMS->SendSmsByType($SMS::USER_ORDER_CANCEL,$sms_agent_info_arr["sms_push_reminder"],array($order['order_sn']));
				}				
								
    			$data['status'] = 1;
    			if($order_status == 4){
    				//更新发货表状态

    				$data['confirm_type'] = 2;
    				$data['confirm_time'] = time();
    				M('order_delivery')->where("agent_id='".C('agent_id')."' and order_id=$order_id AND uid='".$_SESSION['web']['uid']."'")->data($data)->save();
    			}
    		}

    		$this->ajaxReturn($data); 
        }
    }
}