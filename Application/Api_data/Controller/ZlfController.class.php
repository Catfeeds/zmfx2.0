<?php
namespace Api_data\Controller;
class ZlfController extends Api_dataController{
	/**
	 * auth	：zengmm
	 * content：构造函数
	 * time	：2017-09-25
	**/
    public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= C("uid")?C('uid'):I('get.uid',0,'intval');
			$this->token	= C("token")?C('token'):I('get.token','','');
	}

	/**
	 * auth	：zengmm
	 * content：用户登录
	 * time	：2017-09-25
	**/
	public function login(){
		$UM   			= M('user');
	    $username		= I('username','');
	    $pwd			= I('password','');

		if($username && $pwd){
		    $checkUser		= $UM->where(array('agent_id'=>$this->agent_id,'username'=>$username))->find();
			if(empty($checkUser)){
				$this->echo_data('211','用户名或密码错误');
				return false;
			}
			if(pwdHash($pwd) == $checkUser['password']){
				$user_login_verify	= M('user_login_verify');
				$save['check_time']		= time();
				$save['last_activity_time']	= time();
				$key				= 'szzm'.$this->agent_id.$checkUser['uid'];
				$save['token']		= password_hash($key, PASSWORD_DEFAULT);
				$save['agent_id']	= $this->agent_id;
				$save['uid']		= $checkUser['uid'];
				//登录成功先删除旧的登录信息，保存新的登录信息
				$delete				= $user_login_verify->where(array('uid'=>$checkUser['uid'],'agent_id'=>$this->agent_id))->delete();
				$action				= $user_login_verify->add($save);
				$data				= array();
				$data[0]			= $save;
				$this->result['ret']	= '100';
				$this->result['msg']	= '登录成功';
				$this->result['data']	= $data;
			}else{
				$this->result['ret']	= '212';
				$this->result['msg']	= '用户名或密码错误';
			}
		}else{
			$this->result['ret']		= '213';
			$this->result['msg']		= '用户名或密码错误';
		}
		$this->echo_data($this->result['ret'],$this->result['msg'],$this->result['data']);
	}
	
	/**
	 * auth	：zengmm
	 * content：产品分类（产品列表页）
	 * time	：2017-09-25
	**/
	public function getCategoryList(){
		$GC = D('Common/BanfangUrl');
        $cate_lists = $GC->getTreeLists(array('na_status'=>1));
        $left_cates = array_values($cate_lists);
        $c_list 	= $left_cates[0]['sub']; 
		/*foreach($c_list as $key=>$val){
			foreach($val as $k=>$v){
				if($k=="sub"){
					unset($c_list[$key][$k]);
				}
				if($k=="sub"){
					foreach($c_list[$key]["sub"] as $kk=>$vv){
						foreach($vv as $kt=>$vt){
							if($kt=="na_status_name"){
								unset($c_list[$key]["sub"][$kk]["url_link"]);
							}	
						}	
					}	
				}
			}
		}*/
		$this      -> echo_data('100','获取成功',$c_list);   
	}
	
	/**
	 * auth	：zengmm
	 * content：产品筛选条件（产品列表页）
	 * time	：2017-09-25
	**/
	public function getSeriesList(){
		$GC = D('Common/BanfangUrl');
		$right_attrs = $GC->setRightPartConditions();
		$right_attrs = array_values($right_attrs);
		$this      -> echo_data('100','获取成功',$right_attrs);   
	}
	
	/**
	 * auth	：zengmm
	 * content：产品列表页
	 * time	：2017-09-25
	**/
	public function getGoodsList(){
		$this->checkTokenUid($this->uid,$this->token);
        $conditions = array(
            'p'=>intval(I('p')),
            'n'=>intval(I('n')),
            'sort_type'=>intval(I('sort_type')),
            'sort_id'=>intval(I('sort_id')),
            'attr_id'=>I('attr_id'),
            'search_rank_one'=>intval(I('search_rank_one')),
            'search_rank_two'=>intval(I('search_rank_two')),
            'search_rank_three'=>intval(I('search_rank_three')),
            'search_attr_id'=>I('search_attr_id'),
            'search_content'=>I('search_content'),

        );
		
        //排序
        $sort_id_arr = array(
            0=>'bg.sell_num',
            1=>'bg.g_sale_time',
            2=>'bg.goods_price',
        );
        $sort_type_arr = array(
            0=>'asc',
            1=>'desc'
        );
        $sort_order = array();
        if(isset($sort_id_arr[$conditions['sort_id']]) && isset($sort_type_arr[$conditions['sort_type']])){
            $sort_order[$sort_id_arr[$conditions['sort_id']]] = $sort_type_arr[$conditions['sort_type']];
        }

        //属性
        $attr_id_arr = array();
        if(!empty($conditions['attr_id']) && is_array($conditions['attr_id'])){
            foreach($conditions['attr_id'] as $attr_id){
                if($attr_id>0 && !in_array($attr_id,$attr_id_arr)){
                    $attr_id_arr[] = $attr_id;
                }
            }
        }
		
        $param = $conditions;
        unset($param['sort_type']);
        unset($param['sort_id']);
        $param['sort_order'] = $sort_order;
        $param['attr_id'] = $attr_id_arr;

        $data = D('Common/Goods')->getBanfangLists($param);

        $return = array(
            'page_size'=>$conditions['n'],
            'page_id'=>$conditions['p'],
            'total'=>$data['count'],
            'lists'=>$data['lists']
        );
        $this -> echo_data('100','获取成功',$return);  
	}

	/**
	 * auth	：zengmm
	 * content：产品详情页面
	 * time	：2017-09-25
	**/
    public function goodsInfo(){
		$this->checkTokenUid($this->uid,$this->token);
		$GC = D('Common/BanfangUrl');
        $cate_lists = $GC->getTreeLists(array('na_status'=>1));
        $left_cates = array_values($cate_lists);
        $this->left_cates = $left_cates[0]['sub'];
        $GM = D('Common/Goods');
		$goods_id = I('goods_id');
		if(!$goods_id){
			$this      -> echo_data('202','请输入产品编号');
		}
        $param = array(
            'goods_type'=>7,
            'agent_id'=>C('agent_id'),
            'gid'=>intval(I('goods_id')),
            'select_all'=>1,
        );
        $return_data = $GM->productGoodsInfo($param);
        if(!$return_data['status']){
			$this      -> echo_data('203',$return_data['msg']);
        }
        $data = $return_data['info'];
		if(empty($data['goods_id'])){
			$this      -> echo_data('201','获取失败');
		}else{
        	$this      -> echo_data('100','获取成功',$data);
		} 
	}
	
	/**
	 * auth	：zengmm
	 * content：加入购物车（产品详情页）
	 * time	：2017-09-26
	**/
	public function addCart(){
		$this->checkTokenUid($this->uid,$this->token);
        $GM = D('Common/Goods');
        $CM = D('Common/Cart');
		$info = array(
            'status'=>0,
            'lists'=>array(),
            'count'=>0,
            'msg'=>'加入购物车失败',
        );
        $data = array(
            'gid'=>intval(I('post.gid')),
            'g_data'=>I('post.g_data'),
            'note'=>trim(I('post.note')),
            'ma_id'=>intval(I('post.ma_id')),
            'goods_type_select_number'=>intval(I('post.goods_number')),
            'goods_type'=>7,
            'uid' => $this->uid,
            'factory_id'=>array(),
            'diamond'=>array(),
            'color'=>array(),
            'clarity'=>array(),
            'deputystone_opened'=>array(),
            'hand'=>array(),
            'serial_number'=>time(),
        );
        //用于存放将要加入购物车的数据
        $temp_info = array();
        $serial_number = time();
        $error_data = array();
        foreach($data['g_data'] as $val){
            if(!$val['fac_is_checked']){
                continue;
            }
            $param = $data;
            $param['factory_id'][] = $val['factory_id'];
            $param['diamond'][] = $val['diamond'];
            $param['color'][] = $val['color'];
            $param['clarity'][] = $val['clarity'];
            $param['deputystone_opened'][] = $val['deputystone_opened'];
            $param['hand'][] = $val['hand'];
            $return_data = $GM->productGoodsInfo($param);

            if($return_data['status']==100 && empty($return_data['info']['select_param']['error'])){
				$param['add_factory_sex'] = array($return_data['info']['factory_lists'][0]['factory_info']['sex']);
                $temp_info[] = $param;
            }else{
                $error_data[] = $return_data['info']['select_param']['error'][0];
            }
        }
        $temp_info_cart = array();
        if(!empty($temp_info) && empty($error_data)){
            foreach($temp_info as $oop){
                $return = $CM->addToCart($oop);
                if($return['status']==100){
                    $temp_info_cart['cart_id'] = $return['id'];
                    $temp_info_cart['banfang_cart_id'][] = $return['banfang_cart_id'];
                }
            }
            if($temp_info_cart['cart_id']){
                $info = array(
                    'status'=>100,
                    'lists'=>$temp_info['cart_id'],
                    'count'=>count($temp_info['banfang_cart_id']),
                    'msg'=>'加入购物车成功',
                );
            }
        }

        if(!empty($error_data)){
            $info['msg'] = $error_data[0];
        }
		
		if(!empty($error_data)){
			$this      -> echo_data('101',$info['msg']);
		}else{
			$this      -> echo_data('100',$info['msg']);
		} 
	}
	/**
	 * auth	：zengmm
	 * content：加入购物车（产品列表页）
	 * time	：2017-09-28
	**/
	public function listsAddCart(){
		$this->checkTokenUid($this->uid,$this->token);
        $info = array(
            'status'=>0,
            'lists'=>array(),
            'count'=>0,
            'msg'=>'加入购物车失败',
        );
        $param = array(
            'goods_type_select_number'=>1,
            'gid'=>intval(I('goods_id')),
            'uid'=>$this->uid,
            'goods_type'=>7,
            'serial_number'=>time()
        );
        $temp_info = array();
        $number = -1;

        $GM = D('Common/Goods');
        $CM = D('Common/Cart');
        $return_data = $GM->productGoodsInfo($param);


        if($return_data['status']==100){
            //$param['cart_id_couple'] = $temp_info['cart_id'][$number] ? $temp_info['cart_id'][$number] : 0;
            foreach($return_data['info']['factory_lists'] as $factory_list){
                $param['add_factory_sex'] = array($factory_list['factory_info']['sex']);
                $param['diamond'] = array($factory_list['factory_info']['zhushi_to']);

                $return = $CM->addToCart($param);
                if($return['status']==100){
                    $temp_info['cart_id'][] = $return['id'];
                    $temp_info['banfang_cart_id'][] = $return['banfang_cart_id'];
                    $number++;
                }
            }
        }

        if(!empty($temp_info)){
            $info = array(
                'status'=>100,
                'lists'=>$temp_info['cart_id'],
                'count'=>count($temp_info['banfang_cart_id']),
                'msg'=>'加入购物车成功',
            );
        }
		
		if($info["status"]==100){
			$this      -> echo_data('100',$info['msg']);
		}else{
			$this      -> echo_data('101',$info['msg']);
		}
	}
	
	/**
	 * auth	：zengmm
	 * content：购物车列表
	 * time	：2017-09-26
	**/
	public function orderCart(){
		$this->checkTokenUid($this->uid,$this->token);
		$param = array();
		$param['p'] 		= I('get.p')==''?1:I('get.p');
		$param['n'] 		= I('get.n')==''?13:I('get.n');
		$param['agent_id']	= $this->agent_id;
		$param['uid'] 		= $this->uid;
		if($param['uid']==""){
			$this      -> echo_data('101',"请输入用户ID！");		
		}
		$cart_data = D('Common/Cart')->getBanfanCart($param);
		$this      -> echo_data('100',"获取成功！",$cart_data);	
	}
	/**
	 * auth	：zengmm
	 * content：设置购物车商品
	 * time	：2017-09-26
	**/
	public function set_cart($p=1,$n=13){
		//type  1 添加板房商品   2 修改板房商品 3 删除单个板房商品    4 删除单个购物车商品  5 清除购物车
		$param = array(
				'uid'=>$this->uid,
				//cart_id为选中的cart_id	id为当前操作的元素
				'cart_id'=>I('post.cart_id'),
				'id'=>I('post.id'),
				'n'=>$n,
				'agent_id'=>C('agent_id'),
				'obj_data'=>I('post.obj_data'),
		);

		$type = intval(I('post.type'));
		$CM = D('Common/Cart');
		$data = array();
		$where = array();

		switch($type){
			case 1:
				$param['id'] = $param['id'][0];
				$return = $CM->fuzhiBanfangCartInfo($param);
				$this      -> echo_data($return["status"],$return["msg"],$return["data"]);	
				
				break;
			case 2:
				$param['id'] = $param['id'][0];
				$param['obj_data']['is_change'] = 1;
				$return = $CM->updateMyBanfangCart($param);
				if($return["status"]=="100"){
					$this      -> echo_data('100',"修改成功！",$return["data"]);	
				}else{
					$this      -> echo_data('101',"修改失败！",$return["data"]);	
				}
				
				break;
			case 3:
				$param['id'] = $param['id'][0];
				$return = $CM->deleteMyOneBanfangCart($param);
				$this      -> echo_data($return["status"],$return["msg"]);	
				
				break;
			case 4:
				$return = $CM->deleteMyBanfangCart($param);
				$this      -> echo_data($return["status"],$return["msg"]);	
				
				break;
			case 5:
				$return = $CM->deleteMyBanfangCartAll($param);
				$this      -> echo_data($return["status"],$return["msg"]);	
				
				break;
			case 6:
				$cart_data = $CM->getBanfanCart($param);
				$this      -> echo_data('100',"操作成功！",$cart_data);	
				
			default:

				break;
		}
	}

	/**
	 * auth	：zengmm
	 * content：提交购物车产品（生成订单）
	 * time	：2017-09-27
	**/
	public function orderSubmit(){
		$this->checkTokenUid($this->uid,$this->token);
		if($this->uid==0){
			$this -> echo_data('102','请先登录再购买','');
		}
		$cart_id_arr = I('cart_arr');
		if(empty($cart_id_arr)){
			$this -> echo_data('103','请先加入商品到购物车','');
		}
		$cart_id_arr = unserialize($cart_id_arr);
		if(empty($cart_id_arr)){
			$this -> echo_data('104','请先加入商品到购物车','');
		}
		$order_type_arr = array(
				'1'=>'日常补货',
				'2'=>'顾客订做'
		);
		$order_type = intval(I('order_type'));
		$order_type_name = $order_type_arr[$order_type] ? $order_type_arr[$order_type] : '';

		$data = array();
		$goods_price = 0;
		$cart_goods = array();

		if(is_array($cart_id_arr) && !empty($cart_id_arr)){
			$CM = M('banfang_cart');
			$GM = D('Common/goods');
			$where = array(
				'uid'=>$this->uid,
				'cart_id'=>array('in',$cart_id_arr),
			);
			$lists = $CM->where($where)->select();
			foreach($lists as $value){
				$goods = unserialize($value['goods_attr']);
				$param = $goods['goods_attr_param'];
				$temp = $GM->productGoodsInfo($param);
				if($temp['status']==100){
					$goods_price += $temp['goods_price'];
					$cart_goods[] = $temp['info'];
				}

			}
		}

		$param = array(
				'shop_id'=>intval(I('shop_id')),
				'uid'=>$this->uid,
				'order_type_name'=>$order_type_name,
				'note'=>trim(I('note')),
				'cart_id_arr'=>$cart_id_arr,
				'lxr'=>trim(I('lxr')),
				'lxfs'=>trim(I('lxfs')),
		);

		$return = D('Common/Order')->CreateBanfangOrder($param);
		
		if($return["status"]!=100){
			$this -> echo_data('100',$return["msg"],$return["order_id"]);	
		}else{
			$this -> echo_data('101',$return["msg"],'');
		}
	}
	
	/**
	 * auth	：zengmm
	 * content：订单列表
	 * time	：2017-09-27
	**/
	public function orderList(){
		$this->checkTokenUid($this->uid,$this->token);
		$order_status = [
			'0,1' => '待支付',
			'2,3' => '等发货',
			'4' => '等收货',
			'5,6' => '己完成',
			'-1' => '己取消',
		];
		//搜索条件
		$order_status[I('order_status')]?$where['order_status']=['in',I('order_status')]:'';
		trim(I('search'))?$where['order_sn_zlf']=['like','%'.I('search').'%']:'';

		//固定条件
		$where['agent_id'] = $this->agent_id;
		$where['uid'] = $this->uid;

		$model_banfang_order = M('banfang_order');
		$count = $model_banfang_order->where($where)->count();
			
		if($count>0){

			//获取订单
			$page = new \Common\Org\page_pc_ajax($count,10);
			$banfang_order = $model_banfang_order->where($where)->limit($page->firstRow.','.$page->listRows)->order('order_id desc')->select();
			foreach ($banfang_order as $k => $v) {
					$banfang_order_key[$v['order_id']] = $v['order_id'];
					$banfang_order_new[$v['order_id']] = $v;
			}

			//获取订单商品
			$where = '';
			$where ['order_id'] = ['in',implode(',', $banfang_order_key)];
			$banfang_order_goods = M('banfang_order_goods')->where($where)->select();

			//组成数组
			foreach ($banfang_order_goods as $k => $v) {
				// dump($v);
				$v['attribute']= unserialize($v['attribute']);
				$goods_attr = unserialize($v['attribute']['goods_attr']);
				$v['goods_name']= $goods_attr['goods_name'];
				// dump($goods_attr);exit;
				$v['my_img']= $goods_attr['my_img'];
				$banfang_order_new[$v['order_id']]['sub'][] = $v;
			}

			//赋值到模板文件
			$page = $page->show_zlf();
			$data = $banfang_order_new;
			
			$return = array(
				'page_size'=>10,
				'page_id'=>$page,
				'total'=>$count,
				'lists'=>$data
			);
        	$this -> echo_data('100','获取成功',$return);
		}else{
			$this -> echo_data('101','获取失败',array());
		}
	}
	
	/**
	 * auth	：zengmm
	 * content：订单详情
	 * time	：2017-09-27
	**/
	public function orderInfo(){
		$this->checkTokenUid($this->uid,$this->token);
    	$order_id = I('order_id',0,'intval');
    	if($order_id==0){
			$this -> echo_data('201','请输入订单编号！');	
    	}
    	$where['agent_id'] = $this->agent_id;
    	$where['uid'] = $this->uid;
    	$where['order_id'] = $order_id;
    	$order = M('banfang_order')->where($where)->find();

    	if(!$order){
    		$this -> echo_data('202','订单不存在！');	
    	}
    	//获取订单商品
		$where = '';
		$where ['order_id'] = $order['order_id'];
		$banfang_order_goods = M('banfang_order_goods')->where($where)->select();

		$GMM = D('Common/Goods');
		//组成数组
		foreach ($banfang_order_goods as $k => $v) {
			$v['attribute'] = unserialize($v['attribute']);
			$banfang_order_goods[$k]['sub_info'] = $v['attribute']['sub_info'];
		}

    	$order = $order;
    	$banfang_order_goods = $banfang_order_goods;
		$return = array(
			'order'=>$order,
			'banfang_order_goods'=>$banfang_order_goods,
		);
		$this -> echo_data('100','获取成功',$return);
	}
	
	/**
	 * auth	：zengmm
	 * content：会员信息
	 * time	：2017-09-27
	**/
	public function userInfo(){
		$this->checkTokenUid($this->uid,$this->token);
    	$where = " uid = ".$this->uid;
		$userInfo = M("user")->where($where)->find();
		$userInfo = $userInfo;	
		if(empty($userInfo)){
			$this -> echo_data('101','该用户不存在',array());
		}else{	
			$this -> echo_data('100','获取成功',$userInfo);
		}
	}
	
	/**
	 * auth	：zengmm
	 * content：更新用户资料
	 * time	：2017-09-27
	**/
    public function updateUserInfo() {
		$this->checkTokenUid($this->uid,$this->token);
        $updateDate['sex'] = I('post.sex','','htmlspecialchars');
        $updateDate['email'] = I('post.email','','htmlspecialchars');
        $updateDate['phone'] = I('post.phone','','htmlspecialchars');
        $updateDate['qq'] = I('post.qq','','htmlspecialchars');
        $updateDate['realname'] = I('post.realname','','htmlspecialchars');
        $updateDate['job'] = I('post.job','','htmlspecialchars');
        $updateDate['company_name'] = I('post.company_name','','htmlspecialchars');
        $updateDate['legal'] = I('post.legal','','htmlspecialchars');
        $updateDate['company_address'] = I('post.company_address','','htmlspecialchars');
        $updateDate['birthday'] = $this->makeBirthday(true, I('post.birthdayYear'), I('post.birthdayMonth'), I('post.birthdayDay'));
		$uid = I('uid',0,'intval');
		if($uid==0){
			$this -> echo_data('101','请输入用户ID');	
		}
        $U = M('user');
        if ($U->where("uid=".$this->uid.' and agent_id = '.$this->agent_id)->save($updateDate) !== false) {
			$this -> echo_data('100','修改成功');	
        } else {
			$this -> echo_data('102','修改失败');	
        }
    }

	/**
	 * auth	：zengmm
	 * content：修改密码
	 * time	：2017-09-27
	**/
    public function updatePwd() {
		$this->checkTokenUid($this->uid,$this->token);
        $map['old_password'] = I('get.old_password','','htmlspecialchars');
        $map['new_password'] = I('get.new_password','','htmlspecialchars');
        $map['confirm_password'] = I('get.confirm_password','','htmlspecialchars');
        if ($map['new_password'] === $map['confirm_password']) {
          $U = M('user');
          $uInfo = $U->field('*')->where("uid=".$this->uid.' and agent_id = '.$this->agent_id)->find();
          if (!empty($uInfo) &&  ($uInfo['password'] == pwdHash($map['old_password']))) {
			if(empty($map['new_password'])){
				$this -> echo_data('104','请输入新密码！',array());	
			}else{
				$pa['password'] = pwdHash(strval($map['new_password']));
				if ($U->where("uid=".$this->uid)->save($pa) !== false) {
				  $this -> echo_data('100',L('L9039'));	
				} else {
				  $this -> echo_data('103',L('L9042'));	
				}
			}
          } else {
		  	$this -> echo_data('102',L('L9041'));	
          }
        } else {
			$this -> echo_data('101',L('L9040'));	
        }
	}

	/**
	 * auth	：zengmm
	 * content：登录日志
	 * time	：2017-09-27
	**/
    public function loginLog() {
		$this->checkTokenUid($this->uid,$this->token);
		$page_id       = I("page_id",1,'intval');
        $page_size     = I("page_size",10,'intval');
      	$L 			   = M('user_log');
      	$count 		   = $L->where('uid='.$this->uid.' and agent_id = '.$this->agent_id)->count();
        $limit         = (($pageid-1)*$pagesize).','.$pagesize;
      	$loginInfoArr = $L->field('*')->where('uid='.$this->uid.' and agent_id = '.$this->agent_id)->limit($limit)->select();
		$return = array(
			'page_size'=>$page_size,
			'page_id'=>$page_id,
			'total'=>$count,
			'lists'=>$loginInfoArr
		);
		$this -> echo_data('100','获取成功',$return);
    }
	
	/**
	 * auth	：fangkai
	 * content：用户退出
	 * time	：2017-09-27
	**/
    public function loginout() {
	  $this->checkTokenUid($this->uid,$this->token);
	  $user_login_verify	= M('user_login_verify');
	  $save['last_activity_time']	= time();
	  $save['check_time']			= '';
	  $save['token']				= '';
      $update	= $user_login_verify->where(array('uid'=>$this->uid,'agent_id'=>$this->agent_id))->save($save);
	  $this->echo_data('100','退出成功');
    }
	
}