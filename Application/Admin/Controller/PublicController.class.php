<?php
/**
 * 公共模块
 * 这个模块里的方法不需要权限
 */
namespace Admin\Controller;
class PublicController extends AdminController{

    //登陆过程
    public function loginDo(){
    	//已登陆跳走
    	if(session('admin.uid')){
    		$url = 'Admin/Index/index';
    		$this->redirect($url);
    	}
    	$verify = new \Think\Verify();
    	if ($verify->check($_POST['verify'])) {
    		if (empty($_POST['username'])) {
    			$this->log('error', 'A5',U('Admin/Public/login'));
    		} elseif (empty($_POST['password'])) {
    			$this->log('error', 'A6',U('Admin/Public/login'));
    		}
    		// 提取登陆账户信息
    		$map["user_name"] = $_POST['username'];
    		$map["status"] = array('gt',0);
            $map["agent_id"] = C('agent_id');
    		$authInfo = M('admin_user')->Where($map)->find();
    		//分销商域名只能分销商自己的管理员账号登陆，其他登陆提示没有账号
    		//if($this->parent_type != 1 and $authInfo['user_id'] != $this->admin_id){
    			//$this->error('没有当前管理员账号');
    		//}
    		if (false == $authInfo) {
    			$this->log('error', 'A7',U('Admin/Public/login'));
    		} else {
    			if ($authInfo['password'] != pwdHash($_POST['password'])) {
    				$this->log('error', 'A8',U('Admin/Public/login'));
    			}
    			$_SESSION['admin']['uid'] = $authInfo['user_id'];
    			$_SESSION['admin']['email'] = $authInfo['email'];
    			$_SESSION['admin']['UserName'] = $authInfo['nickname'];
    			$_SESSION['admin']['lastLoginTime'] = $authInfo['last_login_time'];
    			$_SESSION['admin']['login_count'] = $authInfo['login_count'];
    			// 保存登录信息
    			$User = M('admin_user');
    			$ip = get_client_ip();
    			$time = time();
    			$data = array();
    			$data['user_id'] = $authInfo['user_id'];
    			$data['last_login_time'] = $time;
    			$data['login_count'] = array('exp','login_count+1');
    			$data['last_login_ip'] = $ip;
    			$User->save($data);
    			$this->uid = $authInfo['user_id'];
    			//获取用户组
    			//用户组是分销商获取分销商id
    			$user_groups = M()->table('zm_auth_group_access AS a')
    			->where("(a.agent_id=".C('agent_id')." and (g.agent_id='".C('agent_id')."' or g.id = 1)) and a.uid='$this->uid' and g.status='1'")
                ->join('zm_auth_group AS g on a.group_id=g.id')
    			->field('uid,group_id,title,rules')->select();
                //(a.agent_id=".C('agent_id')." and (g.agent_id='".C('agent_id')."' or g.id = 1)  如果是超级管理员g.id=1， 不用判断他的agent_id,g.id=1的权限是公共的。

    			$this->group = array();
    			foreach ($user_groups as $g) {
    				$this->group = array_merge($this->group, explode(',', trim($g['group_id'], ',')));
    			}
    			if(is_array($user_groups)){
    				$url = 'Admin/Index/index';
    			}else{
    				session('admin',null);
    				$this->log('error', 'A12',U('Admin/Public/login'));
    			}
    			$this->log('success', 'A9','UID:'.$authInfo['user_id'],U($url));
    		}
    	} else {
    		$this->log('error', 'A10',U('Admin/Public/login'));
    	}
    }


    // 用户登录
    public function login(){
    	//已登陆跳走
        if(session('admin.uid')){
            $url = 'Admin/Index/index';
            $this->redirect($url);
        }
        if (! isset($_SESSION['admin'][C('USER_AUTH_KEY')])) {
        	layout(false);
        	$this->display();
        } else {
        	$this->redirect('Admin/Index/index');
        }
    }

    // 清除缓存
    public function clearCache(){
        $this->error('暂时关闭模板缓存');
    }

    //生成验证码
    public function verify(){
        ob_end_clean();
        $config = array('fontSize' => 40, 'length' => 4,'expire'=>'120');
        $Verify = new \Think\Verify($config);
        $Verify->fontttf = '5.ttf';
        $Verify->entry();
    }

    //AJAX验证码验证
    public function checkVerify($code){
        $verify = new \Think\Verify();
        return $verify->check($code);
    }

    //用户退出
    public function logout(){
        session('admin',NULL);
        $this->log('success', 'A11','',U('Admin/Public/login'));
    }

    /**
     * ajax根据上级ID获取地址，2省级3市区级4区县级
     * @param int $parent_id
     */
    public function getRegion($parent_id){
    	$R = M('region');
    	$data = $R->where('parent_id = '.I('get.parent_id'))->select();
    	$this->ajaxReturn($data);
    }

    /**
     * ajax根据1级支付ID获取2级支付列表
     * @param int $id 1级ID
     */
    public function getPayment($id){
    	$PM = M(payment_mode);
    	$data = $PM->where('agent_id='.C('agent_id').' and parent_id = '.I('get.id'))->select();
    	$this->ajaxReturn($data);
    }

    /**
     * 根据父级ID获取属性列表
     */
    public function getAttributeList($pid=0){
    	$GA = M('goods_attribute');
    	$where = 'agnet_id ='.C('agent_id').' and pid = '.$pid;
    	$data = $GA->where($where)->select();
    	$this->ajaxReturn($data);
    }

    /**
     * 根据SKU选中规格字符串给数组加选中
     * @param array $array
     * @param string $string  /例1:"16:112^19:10.55g/0.22ct"
     */
    public function _arrayActive($array,$sku,$key){
        $attr = explode('^', $sku[$key]['attributes']);
        foreach ($attr as $kk => $vv) {
            $active = explode(':', $vv);
            $attr_top[]['attr_name'] = $array[$active[0]]['attr_name'];
            if($array[$active[0]]['input_type'] == 2){
                $array[$active[0]]['sub'][$active[1]]['active'] = 1;
                $sku[$key]['attr_name'][]['attr_name'] = $array[$active[0]]['sub'][$active[1]]['attr_value_name'];
            }elseif ($array[$active[0]]['input_type'] == 3){
                $arr['attr_value_name'] = $active[1];
                $arr['active'] = 1;
                $array[$active[0]]['sub'][$active[1]] = $arr;
                $sku[$key]['attr_name'][]['attr_name'] = $active[1];
            }
        }
        $data['array'] = $array;
        $data['attr_top'] = $attr_top;
        return $data;
    }

    /**
     * 产品信息页根据分类ID获取属性分类
     * @param int $id
     * @param int $type 1成品属性2成品规格3定制属性
     * @param int
     */
    public function getGoodsAttribute($category_id,$goods_type,$goods_id=''){

		$price_model           = I('price_model','0');		
		$this -> price_model   = $price_model;
		$this -> category_id   = $category_id;
		$this -> category_name = M('goods_category')->where(" category_id = '$category_id' ") -> getField('category_name');
		//var_dump(InStringByLikeSearch($this -> category_name,array('钻戒','对戒','项链','手链')));
		$point               = 0;
        $G                   = D('Common/Goods');
		if( !empty($goods_id) ){			
			$goodsinfo = $G -> get_info($goods_id);
			if(empty($goodsinfo['agent_id'])){
				$agent_id = '0';
			}else{
				$agent_id = $goodsinfo['agent_id'];
				$point    = $G -> point;
			}
		}else{			
			$G -> point = 0;
			$agent_id   = C('agent_id');
		}
		$GA  = M('goods_attributes');
    	$GCA = M('goods_category_attributes');
    	$GAV = M('goods_attributes_value');
    	//按分类和类型获取属性
    	if( $goods_type == 3 ){
			$where = 'type = 1';
		} elseif ( $goods_type == 4 ) {
			$where = 'type = 3';
		}
    	$gacList = $GCA->where($where.' and category_id = '.$category_id)->select();
    	$ids     = $this->parIn($gacList, 'attr_id');
    	$list    = $GA->where('attr_id in('.$ids.')')->select();
    	$list    = $this->_arrayIdToKey($list);
    	$attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
    	foreach ($attrValueList as $key => $value) {
    	    $list[$value['attr_id']]['sub'][] = $value;
    	}
    	//有产品ID获取产品的属性数据
    	if($goods_id){
    	    $GAA = M('goods_associate_attributes');
    	    $attr = $GAA->where('goods_id = '.$goods_id." and agent_id in ($agent_id)")->select();
    	    foreach ($attr as $key => $value) {
    	        if($list[$value['attr_id']]['input_type'] == 2 or $list[$value['attr_id']]['input_type'] == 1){
    	            foreach ($list[$value['attr_id']]['sub'] as $kk => $vv) {
    	                if(intval($value['attr_code'])&intval($vv['attr_code'])){
    	                    $list[$value['attr_id']]['sub'][$kk]['active'] = 1;
    	                }
    	            }
    	        }elseif ($list[$value['attr_id']]['input_type'] == 3){
    	            if($value['attr_value']){
    	                $sub = explode(',', $value['attr_value']);
    	                foreach ($sub as $kk => $vv) {
    	                    $list[$value['attr_id']]['sub'][]['attr_value_name'] = $vv;
    	                }
    	            }
    	        }
    	    }
    	}
    	$this->list = $list;
    	layout(false);
    	$data['attrHtml'] = $this->fetch('GoodsAttr');
    	if($goods_type == 3){
    	    //获取分类成品产品的规格
    	    $gacList = $GCA->where('category_id = '.$category_id.' and type = 2')->select();
    	    $ids  = $this->parIn($gacList, 'attr_id');
    	    $list = $GA->where('attr_id in('.$ids.')')->select();
    	    $list = $this->_arrayIdToKey($list);
    	    $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
    	    foreach ($attrValueList as $key => $value) {
    	        $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
    	    }
    	    //有产品ID获取产品的SKU数据
    	    if($goods_id){
				$sku = $G -> getGoodsSku($goods_id,'','list');
    	    }

    	    //根据SKU给规格做选中
    	    if($sku){
    	        foreach ($sku as $key => $value) {
    	            $attr = explode('^', $sku[$key]['attributes']);
    	            unset($skuTop);
    	            foreach ($attr as $kk => $vv) {
    	                $active = explode(':', $vv);
    	                $skuTop[]['attr_name'] = $list[$active[0]]['attr_name'];
    	                if($list[$active[0]]['input_type'] == 2){
    	                    $list[$active[0]]['sub'][$active[1]]['active'] = 1;
    	                    $sku[$key]['attr_name'][]['attr_name'] = $list[$active[0]]['sub'][$active[1]]['attr_value_name'];
    	                }elseif ($list[$active[0]]['input_type'] == 3){
    	                    $arr['attr_value_name'] = $active[1];
    	                    $arr['active'] = 1;
    	                    $list[$active[0]]['sub'][$active[1]] = $arr;
    	                    $sku[$key]['attr_name'][]['attr_name'] = $active[1];
    	                }
    	            }
    	        }
    	        $this->sku    = $sku;
    	        $this->skuTop = $skuTop;
    	    }
    	    $this->list = $list;
    	    layout(false);
    	    $data['spacHtml'] = $this->fetch('GoodsSpec');
    	}elseif ($goods_type == 4){
    	    if($goods_id){
                //获取分类定制产品的金工石数据
				$list              = $G->getGoodsAssociateLuozuanAfterAddPoint('goods_id = '.$goods_id,'list');
				$goodsMaterialInfo = $G->getGoodsAssociateInfoAfterAddPoint('goods_id = '.$goods_id,'list');
				$size              = $G->getGoodsAssociateSizeAfterAddPoint('goods_id = '.$goods_id,'list');
        	    foreach ($goodsMaterialInfo as $key => $value) {
        	        foreach ($list as $k => $v) {
        	            if($v['material_id'] == $value['material_id']){
        	                $goodsMaterialInfo[$key]['sub'][] = $v;
        	            }
        	        }
					foreach ($size as $k => $v) {
        	            if($v['material_id'] == $value['material_id']){
        	                $goodsMaterialInfo[$key]['size'][] = $v;
        	            }
        	        }
        	    }
				$material                = array();
				$material['list']        = $goodsMaterialInfo;
        	    $material['deputystone'] = $G -> getGoodsAssociateDeputystoneAfterAddPoint('goods_id = '.$goods_id,'list');
				$this->material          = $material;
        	    $this->goodsLuozuanShape = M('goods_luozuan_shape') -> select();
    	    }
    	    //获取产品材质数据
    	    $this->materialList = $G    -> getGoodsMaterialAfterAddPoint("agent_id in ($agent_id)",'list');//getGoodsMaterial("agent_id in ($agent_id)");
    	    $data['matchHtml']  = $this -> fetch('GoodsMatch');
    	}
        if( $category_id && $goods_type ){
            $agent_id            = C('agent_id');
            $this -> goods_type  = $goods_type;
            $info  = M('goods_category_config') -> where(" category_id = $category_id and agent_id = $agent_id ") -> find();
            if($goods_type=='3'){
                $this -> public_content   = $info['public_goods_desc_chengpin_down'];
            }else if($goods_type=='4'){
                $this -> public_content   = $info['public_goods_desc_dingzhi_down'];
            }
            $data['pubulicGoodsDescHtml'] = $this -> fetch('PublicGoodsDesc');
        }else{
            $data['pubulicGoodsDescHtml'] = "";
        }
    	$this->ajaxReturn($data);
    }

    /**
     * 生成导航地址返回
     * @param int $type
     * @param int $id
     * @return string
     */
    public function getNavUrl($type,$id){
    	$this -> ajaxReturn($this->buildNavUrl($type, $id));
    }

    /**
     * 选中不同的规格返回不同的库存和价格
     * @param string $sel 选中的字符串(例 51，52，53分别表示3级选中的值)	
     * @param int $goods_id 产品ID
     */
    public function getWebGoodsSpecification($sel,$goods_id){
    	$data  = $this->getWebGoodsSpecificationC($sel,$goods_id);
  		$this -> ajaxReturn($data);
    }

    /**
     * 添加产品到采购单或者订单
     * @param string $par 参数数组
     */
    public function addOrder($par){
        $yiji_agent_id= get_parent_id();    
        $uid          = get_create_user_id();
    	$par          = explode(',', $par);
    	$orderType    = $par[0];//添加的订单类型
    	$goodsType    = $par[1];//添加的产品类型
    	$goodsSn      = $par[2];//产品编号
    	$goods_number = $par[3];//产品数量
    	$og_id        = $par[4];//订单产品id
		$is_caigoudan = $par[5];
		if($is_caigoudan == 1){
			$alert_str = '采购单';
		}else{
			$alert_str = '订单';
		}
		if( ! C('dollar_huilv_type') ){
			$dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
			C('dollar_huilv',$dollar_huilv);
		}
    	$OC                = M('order_cart');
        $OG                = M('order_goods');
		$arr['order_type'] = $orderType;

		if ( $goodsType == 'supply'){  //王松林的代码
			if(strpos( $og_id , ':' )){
				$og_array = explode(':',$og_id);
				$og_id    = implode(',',$og_array);
			}else{
				$og_id    = $og_id;
			}
			$order_id = $OG->where( ' og_id in ( ' . $og_id.' ) ')->select();
			if($og_id) {
                if($yiji_agent_id < 1){
						if(!$uid){
							$result = array('success'=>false, 'msg'=>'请绑定钻明官网帐号在做操作！','error'=>true);
							$this->ajaxReturn($result);
						}

						if(IS_AJAX){
							//D('SyncZmOrder')->addCaiGouDan($order_id[0]['order_id'], $og_id); 
							$SyncZmOrder			= new \Common\Model\SyncZmOrder();
							$SyncZmOrder->pushOrderToZm($order_id[0]['order_id'], $og_id);
						}

                }else{
                    $return_str = D('JoinCaigouOrder')->joinYijiAgentCaigou($order_id[0]['order_id'], $og_id);
                    if($return_str !== true){
                        $result = array('success'=>false, 'msg'=> $return_str,'error'=>true);    
                    }
                }
				
				$result = array('success'=>true, 'msg'=>'添加到'.$alert_str.'成功！','error'=>true);
			}else{
				$result = array('success'=>false, 'msg'=>'添加到'.$alert_str.'失败！','error'=>true);
			}
			$this->ajaxReturn($result);
			exit;
		}

		if($yiji_agent_id < 1){    //一级不能添加商品到采购单
			//$this->error('操作有误，请与管理员联系');
            $result = array('success'=>false, 'msg'=>'您不能添加商品到采购单','error'=>true);
            $this->ajaxReturn($result);
		}

		$erji_info = M('trader')->where('t_agent_id='.C('agent_id'))->find();  //二级信息
        $og_info = $OG->where('agent_id = '.C('agent_id').' and og_id ='.$og_id)->find();
        if(!$og_info){
            //$this->error('你的订单中没有该产品！');
            $result = array('success'=>false, 'msg'=>'你的订单中没有该产品！','error'=>true);
            $this->ajaxReturn($result);
        }

		if($goodsType == 'luozuan'){//裸钻  //1,luozuan,{$vo.certificate_no},1,{$vo['og_id']},1
    		$arr['goods_type'] = '1';
			$GL   = D('GoodsLuozuan');
            $GL  -> setLuoZuanPoint(1);
			$info = $GL->where("certificate_number = '$goodsSn' and agent_id in(0,".$yiji_agent_id.")") -> find();

            if(!$info){
                $result = array('success'=>false, 'msg'=>'供应商没有这款证书货，或者证书货是你自己的！','error'=>true);
                $this -> ajaxReturn($result);
            }
            if($info['agent_id'] == $yiji_agent_id){
                $info['dia_discount'] = $info['dia_discount'] -  $erji_info['luozuan_advantage'];
            }          
            $info = getGoodsListPrice($info, 0, 'luozuan', 'single', $yiji_agent_id);
    		$arr['goods_id']      = $info['gid'];
                         
    	}elseif($goodsType == 'sanhuo'){//散货
    		$arr['goods_type']         = '2';
            $sanhuo_attr['goods_attr'] = $og_info['attribute'];
            $sanhuo_info               = unserialize($sanhuo_attr['goods_attr']);
            $obj                       = D('GoodsSanhuo');
            $obj                       -> agent_id = $yiji_agent_id;
			$info                      = $obj -> getInfo(array('zm_goods_sanhuo.zm_goods_id'=>array('in',$sanhuo_info['zm_goods_id'])));
            if(!$info){
                $result = array('success'=>false, 'msg'=>'供应商没有这款散货，或者散货是你自己的！','error'=>true);
                $this->ajaxReturn($result);
            }
            
            $info = getGoodsListPrice($info,0 , 'sanhuo', 'single', $yiji_agent_id);
    		$arr['goods_id']               = $info['goods_id'];
    		$arr['goods_number']           = $goods_number;
            $info['pifa_sanhuo_advantage'] = $erji_info['sanhuo_advantage'];
    	}elseif ($goodsType == 'consignment3'){//代销货（成品）
    		$arr['goods_type'] = '3';

            $arr['goods_number'] = $goods_number;
            $arr['goods_attr']   = $og_info['attribute'];
            $info  				 = unserialize($arr['goods_attr']);
            $sku_sn 			 = $info['sku_sn'];
            
            $G                   = D('Common/Goods');

            $temp                = $G->getGoodsSku(0,"sku_sn = '$sku_sn'");
            $info['goods_price'] = formatRound($temp['goods_price'],2);
            $info['goods_sku']   = $temp;
            $arr['goods_id']     = $gid;
            $arr['sku_sn']       = $sku_sn;
            $arr['goods_id']     = $info['goods_id'];

            $info = getGoodsListPrice($info, 0, 'consignment', 'single', $yiji_agent_id);
    	}elseif ($goodsType == 'consignment4'){//代销货（定制）
    		$arr['goods_type']   = '4';
            $arr['goods_number'] = $goods_number;
            $arr['goods_attr']   = $og_info['attribute'];
            $info                = unserialize($arr['goods_attr']);
            $arr['goods_id']     = $info['goods_id'];
            $info = getGoodsListPrice($info, $uid, 'consignment', 'single', $yiji_agent_id);
    	}
        //不允许是自己的商品
        if($goodsType == 'consignment4' or $goodsType == 'consignment3'){
            $is_site_goods = M('goods')->where('goods_id='.$info['goods_id'].' and agent_id ='.C('agent_id'))->count();//是自己站点的商品
            if($is_site_goods > 0){
                $result = array('success'=>false, 'msg'=>'供应商没有这款产品，或者产品是你自己的！','error'=>true);
                    $this->ajaxReturn($result);
            }
        }

        //加入到采购单，直接将订单商品的信息复制进去，
        //在采购单详情中，我们需要对商品信息进行实时更新，确保商品有货
        //故这里就做简单的处理。
        if($is_caigoudan == 1){
            $arr['goods_id']   = $og_info['goods_id'];
            $arr['goods_attr'] = $og_info['attribute'];
        }else{
            $arr['goods_attr'] = serialize($info);
        }
        //$arr['goods_attr'] = serialize($info);
    	$arr['goods_sn']   = $goodsSn;
		$arr['agent_id']   = C('agent_id');
    	$arr               = array_merge($arr,$this->buildData());

    	//不允许有同一件产品
    	$where = "goods_sn = '$goodsSn' and goods_type =".$arr['goods_type'].' and '.$this->buildWhere();
    	$info = $OC->where($where)->find();


    	if($info and ($goodsType == 'luozuan' or $goodsType == 'sanhuo')){
			$result = array('success'=>false, 'msg'=>'添加到'.$alert_str.'失败,不允许有同一件产品！','error'=>true);
    	}else{
    		if( $id = $OC -> add($arr) ){
    			$result = array('success'=>true, 'msg'=>'添加到'.$alert_str.'成功！','error'=>false);
    		}else{
    			$result = array('success'=>false, 'msg'=>'添加到'.$alert_str.'失败！','error'=>true);
    		}
    	}
    	$this->ajaxReturn($result);
    }

    /**
     * 显示订单信息页面
     * @param string $order_sn
     */
    public function showOrder($order_id){
    	$goodsList = $this->getOrderGoodsList($order_id);
    	layout(false);
    	$data = $this->fetch();
    	$this->ajaxReturn($data);
    }
    /**
     * 根据散货分数名称获取分数或者筛号
     * @param string $name
     */
    public function getSanhuoWeights($name){
    	$GSW = M('goods_sanhuo_weights');
    	$pid = $GSW->where("weights_name = '$name'")->Field('weights_id')->buildSql();
    	$data = $GSW->where('pid = '.$pid)->select();
    	$this->ajaxReturn($data);
    }

    /**
     * 添加裸钻匹配记录
     */
    public function addLuozuanMatch($material_id){
		$material_id         = I('material_id','0');
		$price_model         = I('price_model','0');		
    	$this -> price_model = $price_model;
		//裸钻匹配形状
    	$GLS = M('goods_luozuan_shape');
    	$this->goodsLuozuanShape = $GLS->select();
    	layout(false);
    	$this->material_id = $material_id;
    	$this->uuid = $this->buildUuid('luozuanMatch');
    	$data = $this->fetch();
    	$this->ajaxReturn($data);
    }
    /**
     * 添加材质数据
     * @param int $material_id
     */
    public function addMaterial($material_id){
		$price_model         = I('price_model','0');	
		$material_id         = I('material_id','0');	
		$category_id         = I('category_id','0');
		$this -> category_id   = $category_id;
		$this -> category_name = M('goods_category')->where(" category_id = '$category_id' ") -> getField('category_name');
    	$this -> price_model = $price_model;
    	layout(false);
    	$GAM  = M('goods_material');
    	$this ->info = $GAM->find($material_id);
    	$data = $this->fetch();
    	$this->ajaxReturn($data);
    }

    /**
     * 添加匹配副石
     */
    public function addLuozuanMatch2(){
		$price_model         = I('price_model','0');		
    	$this -> price_model = $price_model;
    	layout(false);
    	$this -> uuid = $this->buildUuid('luozuanMatch2');
    	$data =  $this->fetch();
    	$this -> ajaxReturn($data);
    }

	/**
     * 添加匹配副石
     */
    public function addGoodsSize(){
		$price_model           = I('price_model','0');		
		$material_id           = I('material_id','0');
    	$this -> price_model   = $price_model;
		$this -> material_id   = $material_id;
		$category_id           = I('category_id','0');
		$this -> category_name = M('goods_category')->where(" category_id = '$category_id' ") -> getField('category_name');
    	layout(false);
    	$this -> uuid  = $this -> buildUuid('addGoodsSize');
    	$data =  $this -> fetch();
    	$this -> ajaxReturn($data);
    }

    //ajax修改goods_category_config的name_alias
    public function changeCategoryAlias($id, $alias, $cid, $pid, $sort_id){
    	if(!isset($id) || !isset($cid) || !isset($alias) || !isset($pid) || !isset($sort_id))
    		$this->ajaxReturn(false);
    	if( $this -> tid ){
    		$parent_type= 2;
    		$parent_id= $this->tid;
    	}else{
    		$parent_type=1;
    		$parent_id= 0;
    	}
    	$isNew = $this->isNewCatConfig($id,$cid,$pid,$parent_type,$parent_id);	//检查在config表中有无此记录
    	if($isNew)
    		$this->ajaxReturn("请先添加此分类再修改别名！");
    	else
    		$this->updateCategoryAlias($alias, $cid, $pid, $parent_type, $parent_id);
    }

    public function updateCategoryAlias($alias, $cid, $pid, $parent_type, $parent_id){
    	if(!isset($alias) || !isset($cid) || !isset($pid) || !isset($parent_type) || !isset($parent_id))
    		$this->ajaxReturn(false);
    	$GCC = M('goods_category_config');
    	//更新is_show字段
    	$where = ' category_id='.$cid.' AND pid='.$pid.' AND agent_id='.C('agent_id');
    	$result = $GCC->where($where)->setField('name_alias',$alias);
    	$this->ajaxReturn($result);
    }

    public function changeCategoryShow($id,$cid,$alias,$pid,$sort_id,$is_show){
    	if(!isset($id) || !isset($cid) || !isset($alias) || !isset($pid) || !isset($sort_id) || !isset($is_show))
    		$this->ajaxReturn(false);

		$parent_type = $this->parent_type;
		$parent_id   = $this->parent_id;
    	$isNew = $this->isNewCatConfig($id,$cid,$pid,$parent_type,$parent_id);	//检查在config表有无此记录

    	if($isNew)
    		$this->insertCategoryShow($cid,$pid,$parent_type,$parent_id,$alias,$sort_id,$is_show);
    	else
    		$this->toggleCategoryShow($cid,$pid,$parent_type,$parent_id,$is_show);
    }

    /**
     * 修改goods_category_config的is_show
     * @param int $id			要修改的记录的id
     * @param int $is_show		is_show要设置的值
     */
    public function toggleCategoryShow($cid, $pid, $parent_type, $parent_id, $is_show){
    	if(!isset($cid) || !isset($pid) || !isset($parent_type) || !isset($parent_id) || !isset($is_show))
    		$this->ajaxReturn(false);
    	$GCC = M('goods_category_config');
    	//更新is_show字段
    	$where = ' category_id='.$cid.' AND pid='.$pid.' AND agent_id='.C('agent_id');
    	$result = $GCC->where($where)->setField('is_show',$is_show);
    	$this->ajaxReturn($result);
    }

    /**
     * 添加goods_category_config记录
     * @param int $id			要修改的记录的id
     * @param int $is_show		is_show要设置的值
     */
    public function insertCategoryShow($cid,$pid,$parent_type,$parent_id,$alias,$sort_id,$is_show){
    	if(!isset($cid) || !isset($pid) || !isset($parent_type) || !isset($parent_id) || !isset($alias) || !isset($sort_id) || !isset($is_show))
    		$this->ajaxReturn(false);
    	$GCC = M('goods_category_config');
    	$data['category_id'] = $cid;
    	$data['name_alias']=$alias;
    	$data['pid']=$pid;
    	$data['sort_id']=$sort_id;
    	$data['parent_type']=$parent_type;
    	$data['parent_id']=$parent_id;
    	$data['is_show']=$is_show;
    	$data['agent_id']=C('agent_id');
    	$result = $GCC->data($data)->add();
    	$this->ajaxReturn($result);
    }

    public function isNewCatConfig($id,$cid,$pid,$parent_type,$parent_id){
    	$isNew = false;
    	if(!empty($cid)){
    	 	$where = ' category_id='.$cid.' AND pid='.$pid.' AND agent_id='.C('agent_id');
    	 	$result=M('goods_category_config')->where($where)->getField('category_config_id');
    	    if(!$result)
    	    	$isNew = true;	//如果在config表中没有有记录则为true
    	}
    	return $isNew;
    }

	/**
	 * auth	：fangkai
	 * content：产品分类排序，首页展示修改
	 * time	：2016-8-1
	**/
	public function updateCategory(){
		if(IS_Ajax){
			$categoryConfigId = I('post.categoryConfigId','','intval');
			$nowvalue		  = I('post.nowValue','');
			$type			  = I('post.type','');
			if(!in_array($type,array(1,2))){
				$result['status'] = 0;
				$result['msg'] 	  = '操作错误';
				$this -> ajaxReturn($result);
			}

			$categoryConfig	  = D('Common/GoodsCategoryConfig');
			$where['category_config_id'] = $categoryConfigId;
			$where['agent_id']           = C('agent_id');
			$check = $categoryConfig     -> getCategoryAlias($where);
			if(empty($check)){
				$result['status'] = 0;
				$result['msg'] 	  = '分类不存在';
				$this->ajaxReturn($result);
			}
			switch($type){
				case 1:
					if(!in_array($nowvalue,array(0,1))){
						$result['status'] = 0;
						$result['msg'] 	  = '操作错误';
						$this->ajaxReturn($result);
					}
					$save['home_show'] = $nowvalue;
					break;
				case 2:
					$save['sort_id']   = $nowvalue;
					break;
			}
			$action    = $categoryConfig->updategetCategory($where,$save);
			if($action == true){
				$result['status'] = 100;
				$result['msg'] 	  = '操作成功';
			}else{
				$result['status'] = 0;
				$result['msg'] 	  = '操作失败';
			}
			$this->ajaxReturn($result);
		}

	}


    /**
     *  订单添加裸钻产品,获取证书下拉列表
     * @param string $certificate_number
     * @author 张超豪
     */
    public function orderAddGoodsLuozuanXiala(){
        $GL = D('GoodsLuozuan');
        //$order_leixing = I('get.order_leixing');
        $certificate_number = I('get.certificate_number');
        $data = $GL->where("certificate_number like '%".$certificate_number."%' and goods_number = 1 ")->limit(0,5)->select();
        $this->ajaxReturn($data);
    }

    /**
     *  订单添加散货产品,获取散货下拉列表
     * @param string $certificate_number
     * @author 张超豪
     */
    public function orderAddGoodsSanhuoXiala(){

        $goods_sn = I('get.goods_sn');
        $GL       = D('GoodsSanhuo');
        $data     = $GL->getList(array('goods_sn'=>array('EXP'," like '%$goods_sn%' ")),'goods_id',1,5);
		//如果$data为null，直接返回
        if(empty($data)) {
            return $data;
        }else{
            $list =  $data['list'];
            $this -> ajaxReturn($list);
        }
    }
    /*
     * @author 张超豪
     * @date 2016-10-17
     * 批量导入兰柏数据
     *
     */
    public function synLanpoLuozuan(){
        $synAgentApiGoods = new \Admin\Model\SynAgentApiGoodsModel();
        // $synAgentApiGoods->synLanpoLuozuan();
        $synAgentApiGoods->synCadiamondLuozuan();//中美钻石同步http://www.cadiamond.net/

    }

	/**
	   * 获取二级分类
	   * @param int $cateid
	   */
	  public function getChildrenCate($cateid){
		$G                 = M('goods_category');
		$cateId = I('get.cateid');
		$data = $G->where('pid='.$cateId)->select();
		$this->ajaxReturn($data);
	  }

}
