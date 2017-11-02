<?php
/**
 * 后端公共类
 * 所有后端控制器类都会继承
 */
namespace Admin\Controller;
use Think\Controller;
class AdminController extends Controller {

	// 初始化方法
	protected function _initialize() {
	    ob_end_clean();
		$this->checkAgent();
	    $this->checkUser();   // 检测管理用户,获取类型和分销商对应的id
		$this->getConfig();   // 配置数据初始化
		$this->checkAuth();   // 检测用户登录,检测权限和获得权限列表
		$this->checkDomain(); // 检测域名对应的分销商数据 //test
		$this->loadLuozuanPoint();// 裸钻点数
		$this->loadSyncLuozuanConfig();// 加载同步裸钻config
		$this->setMyAllParam();
	}

	/**
	 * 同步裸钻的config设置
	 * @return void
	 */
	private function loadSyncLuozuanConfig(){
		$code = isSyncLuozuan();
		C('is_sync_luozuan',$code);
	}
	/**
	 * 设置参数
	 */
	private function setMyAllParam(){
		$my_all_param = array(
			'submit_url'=>U(MODULE_NAME . '/' . CONTROLLER_NAME.'/'.ACTION_NAME),
		);
		$this->my_all_param = $my_all_param;
	}

	/**
	 * 获取裸钻点数
	 * @return void
	 */
	private function loadLuozuanPoint(){
		$point = getLuoZuanPoint();
		C('LuozuanPoint',$point);
	}

	/**
	 * 根据域名获取agent_id
   * @return void
	 */
	private function checkAgent(){
		$agent_id = get_agent_id();
		if($agent_id){
			C("agent_id",$agent_id);
		}else{
			$this->error('非法域名!');
		}
	}

	/**
	 * 获取当前站点使用的模板id
	 * @param int $template_type 1电脑版 2手机版
	 */
	protected  function getTemplateId($template_type=1){
		//获取开启的模板和颜色
		$T    = M('template');
		$join = 'zm_template_config AS tc ON tc.template_id = t.template_id';
		$list = $T->alias('t')->join($join)->where('t.template_type = '.$template_type.' AND tc.template_status = 1 and tc.agent_id = '.C("agent_id"))->field('t.template_id,t.template_path')->find();
		return $list;
	}

	/**
	 * 检测域名对应的数据
	 * @return
	 */
	public function checkDomain(){
		$info = M('agent')->where("agent_id = '".C('agent_id')."'")->find();
		if($info and $info['parent_id']){
			$traderInfo = M('trader')->where('t_agent_id = '.$info['agent_id'])->field('admin_id,tid')->find();
			if($traderInfo){
				$this->admin_id    = $traderInfo['admin_id'];
			}
			$this->parent_type = $info['level'];
			$this->level       = $info['level'];
			$this->parent_id   = $info['parent_id'];
			$this->domain      = $info['domain'];
		}else{
			$this->parent_type = 1;
			$this->level       = 1;
			$this->parent_id   = 0;
			$this->domain = $info['domain'];
		}

	}

	/**
	 * 把数据库的配置读到系统中
	 * @return void
	 */
	protected function getConfig(){
		//取出所有的项
		$data = M('config')->field('config_key,config_value')->select();
		if($this->tid){
			//获得分销商的配置      where()中去掉了    and parent_type = 1
			$data1        = M('config_value')->where('agent_id='.C('agent_id').'  and parent_id = '.$this->tid)->field('config_key,config_value')->select();
		}else{
			//获取主站配置
			$data1        = M('config_value')->where('agent_id='.C('agent_id'))->field('config_key,config_value')->select();////where()中去掉了  .' and  parent_type = 0 '
		}
		//初始化默认值
		foreach ($data as $key => $value) {
			C($value['config_key'],$value['config_value']);
		}
		//注入设置的配置
		foreach ($data1 as $key => $value) {
			C($value['config_key'],$value['config_value']);
		}
    //注入钻明汇率
    if( ! C('dollar_huilv_type') ){
        $dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
        C('dollar_huilv',$dollar_huilv);
    }

	}

	/**
	 * 自己写的一个获取用户所有权限规则
	 * @param string $uid
	 * @return unknown
	 */
	protected function getUserAuth($uid=''){
		if(!$uid){
			$id = $this->uid;
		}else{
			$id = $uid;
		}
		//获取全部用户组和用户组的权限id
		$user_groups = M()->table('zm_auth_group_access AS a')->where("a.agent_id=".C('agent_id')." and (g.agent_id='".C('agent_id')."' or g.id=1) and a.uid='$id' and g.status='1'")->join('zm_auth_group AS g on a.group_id=g.id')->field('uid,group_id,title,rules')->select();
		$groups      = $user_groups?:array();
		$ids         = array();

	   	if(!$uid){         ///////////当前登陆账户的权限
	   		$this->group = array();
	   		foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));//所有的权限id
            $this->group = array_merge($this->group, explode(',', trim($g['group_id'], ',')));//所属的全部用户组id
                if($g['group_id'] == 1){//如果是超级管理员；
                  $this->is_superManage = 1;
            $this->is_yewuyuan = 0;   //超级管理员则默认不是业务员，因为超级管理员可以看到所有的业务员数据
            break;
          }else{
					  $this->is_yewuyuan = $this->groupISyewuyuan();
          }
	   		}

	   		$ids = array_unique($ids);
	   		$map = array('id'=>array('in',$ids), 'status'=>1);

	   		//读取用户组所有权限规则
  			if($this->is_superManage){
				$rules = M()->table('zm_auth_rule')->order('sort_id')->getField('id,name');
  			}else{
				$rules = M()->table('zm_auth_rule')->where($map)->order('sort_id')->getField('id,name');
  			}
  			return $rules;
	   	}else{            //编辑查看某一个账号的权限
	   		$group = array();
	   		foreach ($groups as $g) {
	   			$ids = array_merge($ids, explode(',', trim($g['rules'], ',')));//所有的权限id
	   			$group = array_merge($group, explode(',', trim($g['group_id'], ',')));//所属的全部用户组id
			    if($g['group_id'] == 1){//如果是超级管理员；
					 $is_superManage = 1;
			  	}
	   		}
	   		//读取用户组所有权限规则
	   		$ids = array_unique($ids);
	   		$map = array('id'=>array('in',$ids), 'status'=>1);
  			if($is_superManage){
  			  $rules = M()->table('zm_auth_rule')->order('sort_id')->getField('id,name');
  			}else{

			  if(count($ids) != 0){  //先删除权限组，再删除管理员，ids会为空
				  $rules = M()->table('zm_auth_rule')->where($map)->order('sort_id')->getField('id,name');
			  }

  			}
  	   	return array('group'=>$group,'rules'=>$rules);
  	  }
	}

	// 用户登录检测,登录用户获取用户信息
	protected function checkUser() {
		if (session ( 'admin.uid' )) {
			$this->uid = session ( 'admin.uid' );
			$this->UserName = session ( 'admin.UserName' );
			$this->currentUrl = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;//当前模块控制器方法
			$this->menuUrl = MODULE_NAME.'/'.CONTROLLER_NAME.'/index';//做菜单选中
			// 获取用户组,用户组是分销商id那么获取分销商id
			$user_groups = M ()->table ( 'zm_auth_group_access AS a' )->where ( "a.agent_id='".C('agent_id')."' and g.agent_id='".C('agent_id')."' and a.uid='$this->uid' and g.status='1'" )->join ( 'zm_auth_group AS g on a.group_id=g.id' )->field ( 'uid,group_id,title,rules' )->select ();
			$this->group = array ();
			foreach ( $user_groups as $g ) {
				$this->group = array_merge ( $this->group, explode ( ',', trim ( $g ['group_id'], ',' ) ) );
			}
			// 没有所属用户组退出
			if (! is_array ( $user_groups )) {
				session ( 'admin', null );
				$this->log ( 'error', 'A13' );
			}
			// 获取分销商管理相关信息
			// if (in_array ( '7', $this->group ) or in_array ( '8', $this->group )) {
			// 	$T = M ( 'trader' );
			// 	$info = $T->where ( 'admin_id = ' . $this->uid .' and agent_id = '.C('agent_id'))->Field ( 'tid,parent_id' )->find ();
			// 	$this->tid = $info ['tid']; // 当前分销商的id
			// 	$this->traderType = $info ['parent_id']; // 分销商的类型（0.1级分销商；大于1.2级分销商）
			// }
		} else {
			// 没有登录，先去登录
			if (strtolower ( CONTROLLER_NAME ) != 'public') {
				$this->redirect ( 'Admin/Public/login' );
			}
		}
	}

  // 用户权限检查
  protected function checkAuth(){
      $Auth = new \Think\Auth();
      if(strtolower(CONTROLLER_NAME) != 'public'){
            $url = MODULE_NAME . '/' . CONTROLLER_NAME.'/'.ACTION_NAME;

            //判断是不是超级管理员组
            $is_admin_group = M('auth_group_access')->where('agent_id ='.C('agent_id').' and group_id = 1 and uid = '. $this->uid)->count();

            if(!$Auth->check($url,$this->uid) and ($is_admin_group == 0)){  //不是则提示没有权限
                $this->log('error', 'A1',$url,U('Admin/Public/login'));
            }else{
                $AuthList = $this->getUserAuth();
                $this->AuthListS = $AuthList;//用作跳转的操作数组
                foreach ($AuthList AS $k=>$v){
                    $arr[] = strtolower($v);
                }
                $this->AuthList = $arr;//用作菜单的操作数组
                $this->getMenu();//获取对应的菜单
                $this->getTask();//获取对应的任务
            }
      }
  }

  /**
   * 内部权限检查
   * @param string $url
   * @return boolean
   */
  protected function checkActionAuth($url) {
  	$Auth = new \Think\Auth ();
  	if(!$Auth->check($url,$this->uid) and ($this->is_superManage != 1)) return false;
  	else return true;
  }

  // 获取用户菜
  protected function getMenu(){
    $M = M('menu');
    $menuList = $M->where('s_status=1')->order('sort_id')->select();
    // 干掉权限里没有的菜单
    $is_super_agent = isSuperAgent();
	$new_rules = get_zm_agent_setting();
    foreach ($menuList AS $K => $V){
        if(!in_array(strtolower($V['url']), $this->AuthList)){
            unset($menuList[$K]);
        }

		/////在菜单中不显示,暂时屏蔽       超级权限的可以看到
		if(!$is_super_agent){
			///////////一级，二级都不能看到的
			$hidden_menu_all = array(strtolower('Admin/System/systemSet?parent_type=2'), ////////分销商默认配置
								  //strtolower('Admin/Goods/productAttributeList'),     //产品属性列表
								  strtolower('Admin/Statistics/productstatistics'),     //产品统计
								  strtolower('Admin/Statistics/sanhuoStat'),     //散货统计
								  strtolower('Admin/Statistics/luozuanStatistics'),     //裸钻统计
								  strtolower('Admin/Statistics/productStat'),     //成品统计
								);
			if(in_array(strtolower($V['url']), $hidden_menu_all)){
			  unset($menuList[$K]);
			}




			/////一级的不用看到 代销货列表   应付款列表   付款列表
			$this->level=M('agent')->where('agent_id = '. C('agent_id'))->getField('level');
			if($this->level == 1){
			  $hidden_menu_level1 = array(
				   // strtolower('Admin/Goods/disGoodsList'),
				   strtolower('Admin/Business/accountpayablelist')
				  ,strtolower('Admin/Business/apayablelistlist')
				 // ,strtolower('Admin/Order/purchaseOrderList')//采购单列表
				 // ,strtolower('Admin/Order/purchaseOrderInfo')//添加采购单
				);

			  if(in_array(strtolower($V['url']), $hidden_menu_level1)){
				unset($menuList[$K]);
			  }
			}else{
					$hidden_menu_level2 = array(
					strtolower('Admin/Goods/disGoodsList'),
						strtolower('Admin/Trader/traderList'), ////////分销商默认配置
						strtolower('Admin/Trader/index')
					);
					if(in_array(strtolower($V['url']), $hidden_menu_level2)){
						unset($menuList[$K]);
					}
				  }
			}
			
			//如果未开通门店预约，则不展示该模块
			/*if(C('templateSetting')['store_show'] != 1){
				if($V['url'] == 'Admin/Store/index' || $V['url'] == 'Admin/Store/booked' ){
					unset($menuList[$K]);
				}
			}*/
			
			//暂时屏蔽统计模块。
			if($V['url'] == 'Admin/Statistics/index'){
				unset($menuList[$K]);
			}
			if(!$new_rules['zgoods_show'] && $V['url'] == 'Admin/Zgoods/index'){
				unset($menuList[$K]);
			}
			
		}



		$this->new_rules = $new_rules;
		$this->menuList = $this->_arrayRecursive($menuList, 'mid', 'parent_id');
		//获取未读订单总数
		$this->orderunreadcount = M('order')->where('agent_id = '.C('agent_id').' and is_read = 0')->count();
		//获取未回访的预约消息总数
		$Store			= new \Common\Model\Store();
		$this->bookedCount	= $Store->getBookedCount(array('is_visit'=>0),false);
  }

  //获取待处理的任务
  protected function getTask(){

  }

  /**
   * 1级分销商获取下面2级分销商的id
   * @param unknown $tid
   * @return array
   */
  protected  function getTraderId($tid){
    return M('trader')->field('tid')->where('parent_id = '.$tid.' and agent_id'.C('agent_id'))->select();
  }

  /**
   * 递归数组
   * @param array $data 数组对象
   * @param int $id 一条记录的id
   * @param int $pid 上级id
   * @param int $objId 开始的id
   * @return multitype:multitype:unknown
   */
  protected function _arrayRecursive($data,$id,$pid,$objId=0,$isKey='0'){
      $list = array();
      foreach ($data AS $key => $val){
          if($val[$pid] == $objId){
              $val['sub'] = self::_arrayRecursive($data, $id, $pid, $val[$id],$isKey);
              if(empty($val['sub'])){unset($val['sub']);}
              if($isKey) $list[] = $val;
              else $list[$val[$id]] = $val;
          }
      }
      return $list;
  }

  /**
   * 后台记录操作日志,有区分中英文语言
   * @param string $type 日志类型
   * @param string $message 语言包KEY
   * @param string $key 操作对象(比如ID:56)
   * @param string $jumpUrl 跳转地址
   * @param string $ajax 是否ajax返回
   */
  protected function log($type,$message,$key='',$jumpUrl='',$ajax=false){
      $LOG = M('admin_log');
      $LOG->user_id = $this->uid;
      $LOG->log_type = $type;
      $LOG->agent_id = C('agent_id');
      $messageS = L($message);
      $lang = include LANG_PATH.'zh-cn.php';
      $LOG->log_content_zh = $lang[$message].$key;
      $lang = include LANG_PATH.'en-us.php';
      $LOG->log_content_en = $lang[$message].$key;
      $LOG->create_time = time();
      if($LOG->add()){
          $this->$type($messageS,$jumpUrl,$ajax);
      }
  }

  /**
   * 根据订单编号获得订单id
   * @param string $order_sn
   * @return int $ID
   */
  protected function getOrderId($order_sn){
    $O = M('order');
    return $O->where('agent_id = '.C('agent_id').' and order_sn = '.$order_sn)->getField('order_id');
  }

  /**
   * 根据订单ID获得订单编号
   * @param int $order_id
   * @return string
   */
  protected function getOrderSn($order_id){
    $O = M('order');
    return $O->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->getField('order_sn');
  }

  /**
   * 生成归属条件
   * @param string $as 不同的表AS名称
   * @return string
   */
  protected function buildWhere($as=''){
    // if(in_array('3',$this->group)) $where = $as.'parent_type = 1 and '.$as.'parent_id = '.$this->uid;//业务人员
    // elseif(in_array('7',$this->group)) $where = $as.'parent_type = 2 and '.$as.'parent_id = '.$this->tid;//1级分销商
    // elseif(in_array('8',$this->group)) $where = $as.'parent_type = 3 and '.$as.'parent_id = '.$this->tid;//2级分销商
    // else $where = $as.'parent_type = 1';//超级管理员和其他

    //以前7是一级分销商，8是二级分销商。现在有了zm_agent,parent_type就不用管理; parent_id仅仅是管理员的id。注意使用agent_id;
    //$is_yewuyuan = $this->groupISyewuyuan();////////判断是否是业务员
    //if($is_yewuyuan) $where = $as.'agent_id = '.C('agent_id').' and '.$as.'parent_id = '.$this->uid;//业务人员
    //else $where = $as.'agent_id = '.C('agent_id');//超级管理员和其他

	  $where = $as.'agent_id = '.C('agent_id');//超级管理员和其他
    return $where;
  }
  /**
   * 权限组是否是业务员组
   * @return int
   */
  protected function groupISyewuyuan(){
    foreach($this->group as $v){
      $is_yewuyuan = M('auth_group')->where('agent_id = '.C('agent_id').'  and id='.$v)->getField('is_yewuyuan');
      if($is_yewuyuan == 1){return 1;}
    }
    return 0;
  }

  /**
   * 获取业务员的id串，逗号隔开，为空则返回''
   * @return int
   */
  protected function getYewuyuanItem(){
	  $yewuyuan = M('auth_group')->where('agent_id = '.C('agent_id').'  and is_yewuyuan=1')->getField('id');
    if(!empty($yewuyuan)){
        if(is_array($yewuyuan)){
            $item = implode(',', $yewuyuan);
            return $item;
        }else{
            return $yewuyuan;
        }
	  }
    return 0;
  }

  protected function buildUuid($key){
  	  //获取已有的Uuid
      $sess      = session('admin.uuid');
      $arr       = $sess[$key];
      for ($i=0;$i<999;$i++){
          $chars = md5(uniqid(mt_rand(), true));
          $uuid  = substr($chars,0,8) . '-';
          $uuid .= substr($chars,8,4) . '-';
          $uuid .= substr($chars,12,4) . '-';
          $uuid .= substr($chars,16,4) . '-';
          $uuid .= substr($chars,20,12);
          if(!in_array($i, $arr)){
              $newID = $uuid;
              break;
          }
      }
	    $newArray = array_merge($arr,array($newID));
  	  session('admin.uuid',array($key=>$newArray));
      return $newID;
  }

  /**
   * 生成归属DATA
   * @return array
  */
  protected function buildData(){
  	/*
    if(in_array('3',$this->group)){ // 业务员
      $data['parent_type'] = 1;
      $data['parent_id'] = $this->uid;
    } elseif (in_array('7',$this->group)) { //  一级分销商
      $data['parent_type'] = 2;
      $data['parent_id'] = $this->tid;
    } elseif (in_array('8',$this->group)) { // 二级分销商
      $data['parent_type'] = 3;
      $data['parent_id'] = $this->tid;
    } else {
      $data['parent_type'] = 1; // 超级管理员和其他
    }
    */
    //if($this->is_yewuyuan){
    //	$data['parent_id'] = $this->uid;
    //}else{
    //	$data['parent_id'] = $this->uid;
    //}
    //这个函数一定要返回一个数组
	  $data['parent_id'] = $this->uid;
    return $data;
  }

  /**
   * 生成导航地址
   * @param int $type
   * @param int $id
   */
  public function buildNavUrl($type,$id){
  	if($type == 1){
  		$Url = U('Home/Goods/goodsCat?cid='.$id);
  	}elseif($type == 2){
  		if($id == 1){//裸钻
  			$Url = U('Home/Goods/goodsDiy');
  		}elseif ($id == 2){//散货
  			$Url = U('Home/Goods/sanhuo');
  		}elseif ($id == 3){//百科
  			$Url = U('Home/Article/index');
  		}elseif ($id == 4){//用户中心
  			$Url = U('Home/User/index');
  		}elseif ($id == 5){//我的订单
  			$Url = U('Home/User/orderList');
  		}elseif ($id == 6){//加入分销
			if($this->parent_type == '1') {
				$Url = U('Home/User/traderAdd');
			}else{
				$Url = '';
			}
		}elseif ($id == 7){//产品定制
  			$Url = U('Home/Goods/diy');
  		}elseif($id == 8){//门店预约
			$Url = U('Home/Store/index');
		}elseif($id == 9){//设计与报价
			$Url = U('Home/Goods/designQuotation');
		}elseif($id == 10){//设计与报价
			$Url = U('Home/Goods/goodsOfferInfo');
		}
		
  	}elseif($type == 4){
		$Url = U('Home/Goods/goodsCategory?gcid='.$id);
	}else{
  		$Url = '地址错误';
  	}
  	return $Url;
  }

  /**
   * 根据订单编号或ID获取单个订单信息
   * @param string $order_sn
   * @param int $order_id
   * @param Boolean $is_where 是否添加订单所属条件
   * @return array
   */
  protected function getOrderInfo($order_sn,$order_id,$is_where){
    if($is_where) {
		$where = $this->buildWhere();  //如果is_where=0怎么办
	}else{
		$where = 'agent_id = '.C('agent_id');
	}
    if($order_sn) $where .= ' and order_sn = '.$order_sn;
    else if($order_id) $where .= ' and order_id = '.$order_id;
    return M('order')->where($where)->find();
  }


  /**
   * 获取业务员下面的客户
   * @param int $id
   */
  protected function getBusinessUser($id){
    $U = M('user');
    $data = $U->alias('u')->where(' status>=0 and is_validate = 1 and parent_id = '.I('get.id').' and agent_id = '.C('agent_id'))->select();
    $this->ajaxReturn($data);
  }


  /**
   * 发送网站消息
   * @param  integer $uid     客户ID
   * @param  string $content  内容
   * @return integer/boolean          信息ID或false
   */
  protected function sendMsg($uid, $content, $title='', $msg_type){
    $parment = $this->buildData();
    $parment['msg_type'] = $msg_type; // 系统发送给用户
    $parment['uid'] = $uid; // 发送消息的用户ID
    $parment['content'] = $content;
	$parment['title'] = $title;
    $parment['create_time'] = time();
    $parment['is_show'] = 0;
    $parment['agent_id'] = C('agent_id');
    $result = M('user_msg')->data($parment)->add();
    if ($result) {
      return $result;
    } else {
      return false;
    }
  }

  /**
   * 发送邮件
   * @param  string $address 邮箱地址
   * @param  string $message 邮件内容
   * @return boolean         发送成功(true)或失败(false)
   */
  protected function sendMail($address, $message) {
    vendor('PHPMailer.class#phpmailer');
    $mail = new \PHPMailer();
    $mail->IsSMTP();                // 设置PHPMailer使用SMTP服务器发送Email
    $mail->CharSet='UTF-8';          // 设置邮件的字符编码，若不指定，则为'UTF-8'
    $mail->AddAddress($address);    // 添加收件人地址，可以多次使用来添加多个收件人
    $mail->Body=$message;           // 设置邮件正文
    $mail->From=C('MAIL_ADDRESS');  // 设置邮件头的From字段。
    $mail->FromName=L('A8020');  // 设置发件人名字
    $mail->Subject=L('A8020');         // 设置邮件标题
    $mail->Host=C('MAIL_SMTP');     // 设置SMTP服务器。
    $mail->SMTPAuth=true;           // 设置为"需要验证" ThinkPHP 的C方法读取配置文件
    $mail->Username=C('MAIL_LOGINNAME'); // 设置用户名和密码。
    $mail->Password=C('MAIL_PASSWORD');
    return($mail->Send()); // 发送邮件。
  }

  /**
   * 发送手机消息
   * @param int $phone
   * @param string $content
   */
  protected function sendPhone($phone,$content){
    return true;
  }
  /**
   * 发送系统信息
   * @param  int $muban_id  消息模板ID
   * @param  int $uid 客户ID
   * @param  int $id  数据ID
   * @param  time $optiontime  操作时间，毫秒数
   * @return array    array('success'=>true/false, 'msg'=>'')
   */
  protected function sendSystemMsg($muban_id, $uid, $id, $optiontime){
    $result     = array('success'=>false, 'msg'=>'请求异常！');
    $msgSetting = C('SET_MESSAGE');
    if (empty($msgSetting)) {
      $result['msg'] = '系统未开放任何消息通知！';
    } elseif (empty($muban_id) || empty($uid)) {
      $result['msg'] = '方法调用异常！请检查！';
    } else {
      $mubanData =  M('msg_muban')->find($muban_id);
      if (empty($mubanData)) {
        $result['msg'] = '无模板数据，请检查！';
      } else {
        if ($mubanData['type'] == 1) { // 发给客户，需要客户数据
          $U =  M('user');
          $join = ' zm_admin_user b ON a.parent_id = b.user_id';
          $field = 'a.uid, a.username, a.email, a.phone, b.user_name AS adminname';
          $userData = $U->alias('a')->field($field)->where('a.uid='.$uid.' and b.agent_id = '.C('agent_id'))->join($join)->find();
        } else { // 发给系统，需要客户对应的业务员数据
          $adminU = M('admin_user');
          $join = 'zm_user b on a.user_id = b.parent_id';
          $field = 'a.user_id uid, a.user_name adminname, a.email, a.phone, b.username';
          $userData = $adminU->alias('a')->field($field)->where('b.uid='.$uid.' and a.agent_id = '.C('agent_id'))->join($join)->find();
        }
        if (empty($userData)) {
          $result['msg'] = '无用户数据，请检查！';
        } else {
          // 判断调用的模板，根据模板ID决定查询的数据表
          $tableData = $this->chooseTableByMubanId($muban_id, $id);
          $tableData['optiontime'] = $optiontime;
          $result = $this->sendMsgByConfig($msgSetting, $userData, $tableData, $mubanData);
        }
      }
    }
    return $result;
  }
  // 根据模板ID，去不同的表，根据表的主键ID取数据，然后做替换，返回替换好的内容
  private function chooseTableByMubanId($muban_id, $id) {
    switch ($muban_id) {
      case 1:   // 客户下订单 ---> 发给用户
      case 2:   // 客户下订单 ---> 发给系统
      case 6:   // 后台配货 ---> 发给用户
      case 10: // 取消订单 ---> 发给系统
        $fields = 'a.order_sn';
        $result = M('order')->alias('a')->where('a.agent_id = '.C('agent_id'))->field($fields)->find($id);
        break;
      case 3:   // 订单确认 ---> 发给用户(其中有一个支付方式，需要在 ‘订单支付记录’表中查询)
      case 4:  // 客户支付 ---> 发给用户(其中有一个支付方式，需要在 ‘订单支付记录’表中查询)
      case 5:  // 客户支付 ---> 发给系统
        $join = ' zm_payment_mode b on a.payment_mode = b.mode_id';
        $field = 'a.order_sn, a.create_time paytime, a.payment_price, b.mode_name paymode';
        $result = M('order_payment')->alias('a')->where('a.agent_id = '.C('agent_id'))->field($field)->join($join)->find($id);
        break;
      case 9:  // 完成订单 ---> 发给用户
        $field = 'a.order_sn, a.create_time paytime, a.payment_price';
        $result = M('order_payment')->alias('a')->where('agent_id = '.C('agent_id'))->field($field)->find($id);
        break;
      case 7: // 后台发货 ---> 发给用户
      case 8: // 确认收货 ---> 发给系统
        $M = M('order_delivery');
        $join = 'left join zm_delivery_mode b on b.mode_id = a.delivery_mode';
        $join2 = 'left join zm_order c on c.order_id = a.order_id';
        $field = 'a.create_time deliverytime, c.order_sn, b.mode_name deliverymode';
        $result = $M->alias('a')->where('a.agent_id = '.C('agent_id'))->field($field)->join($join)->join($join2)->find($id);
        break;
      case 11: // 生成超期信息 ---> 发给用户
      case 12: // 生成超期信息 ---> 发给系统
        // $result = M('')->find($id);
        break;
    }
    return $result;
  }
  private function replaceContent($userData, $tableData, $content) {
      $search = array(
        '{$admin.adminname}',// 后台业务员姓名
        '{$user.username}',  // 前台用户姓名
        '{$order.order_sn}', // 订单替换的
        '{$order.optiontime}',
        '{$order.paymode}',
        '{$order.paytime}',
        '{$order.payment_price}',
        '{$order.total}',
        '{$order.deliverymode}');
      $replace = array(
        $userData['adminname'],
        $userData['username'],
        $tableData['order_sn'],
        date('Y-m-d H:i:s', $tableData['optiontime']),
        $tableData['paymode'],
        $tableData['payment_price'],
        $tableData['total'],
        $tableData['deliverymode']);
     return str_replace($search,$replace,$content);
  }
  /**
   * 根据配置项发送消息
   * @param  string $msgSetting 配置项字符串
   * @return array              arrya('success'=>true, 'msg'='')
   */
  private function sendMsgByConfig($msgSetting, $userData, $tableData, $mubanData) {
    $msgConfig = explode(',', $msgSetting);
    $msg = true;
    $phone = true;
    $mail = true;
    foreach ($msgConfig as $key => $mc) {
      switch ($mc) {
        case 'websys': // 系统消息
            $webSysCon = $this->replaceContent($userData, $tableData, $mubanData['websys_content']);
            if (!$this->sendMsg($userData['uid'],$webSysCon,'', $mubanData['type'])) {
                $msg = false;
                $result['msg'] .= '系统消息发送错误！';
            }
          break;
        case 'phone': // 短信消息
          $phoneCon = $this->replaceContent($userData, $tableData, $mubanData['phone_content']);
          $phoneMsg = M('user_msg_phone');
          $parment = $this->buildData();
          $parment['msg_type'] = $mubanData['type']; // 系统发送给用户
          $parment['uid'] = $userData['uid']; // 发送消息的用户ID
          $parment['content'] = $phoneCon;
          $parment['create_time'] = time();
          $parment['agent_id']    = C('agent_id');
          if ($phoneMsg->add($parment)) {
              if (!$this->sendPhone($userData['phone'], $phoneCon)) {
                  $phone = false;
                  $result['msg'] .= '短信消息发送错误！';
              }
          }
          break;
        case 'email': // 邮件消息
          $emailCon = $this->replaceContent($userData, $tableData, $mubanData['email_content']);
          $emailMsg = M('user_msg_mail');
          $parment  = $this->buildData();
          $parment['msg_type']    = $mubanData['type']; // 系统发送给用户
          $parment['uid']         = $userData['uid']; // 发送消息的用户ID
          $parment['content']     = $emailCon;
          $parment['create_time'] = time();
          $parment['agent_id']    = C('agent_id');
          if ($emailMsg->add($parment)) {
              if (!$this->sendMail($userData['email'], $emailCon)) {
                  $mail = false;
                  $result['msg'] .= '邮件消息发送错误！';
              }
          }
          break;
      }
    }
    if ($msg && $phone && $mail) {
      $result = array('success'=>true, 'msg'=>'');
    } else {
      $result['success'] = false;
    }
    return $result;
  }

  /**
   * 同级数组转树数组，发进来的数组必须有一个键是level;
   * 树极从小到大排
   * @param array $array
   * @param int $level
   * @return array
   */
  protected function _arrayToTree($array,$level=0){
  	foreach ($array as $key => $value) {
  		if($value['level'] == $level){
  			for($i=1;$i<100;$i++){
  				$arr = self::_arrayToTree($array,$level+$i);
  				if(is_array($arr)){break;}
  			}
  			if(is_array($arr)){$value['sub'] = $arr;}
  			$list[] = $value;
  		}
  	}
  	return 	$list;
  }

  /**
   * 把数组里的字段值作为KEY存放
   * @param array $array
   * @param string $id 没有键名，第一个就是键名
   */
  protected function _arrayIdToKey($array,$id=''){
	if(!$id){$ids = array_keys($array[0]);$id = $ids[0];}
  	foreach ($array as $key => $value) {
		$arr[$value[$id]] = $value;
	}
	return $arr;
  }

  /**
   * 把Key回复成从0开始
   * @param array $array
   */
  protected function _arrayAutoKey($array){
      foreach ($array as $key => $value) {
          $list[] = $value;
      }
      return $list;
  }

   /**
   * 获取分表的表名
   * @param int $type
   * @return string 表名
   */
  protected function getGoodsTableName($type=1) {
  	if($type == 1) $table = 'goods';//1.产品表
  	elseif ($type == 2) $table = 'goods_images';//2.产品图片表
  	elseif ($type == 3) $table = 'goods_associate_attribute';//3.产品属性关联表
  	elseif ($type == 4) $table = 'goods_associate_specification';//4.产品规格关联表
  	elseif ($type == 5) $table = 'goods_associate_luozuan';//5.产品材质裸钻关联表
  	elseif ($type == 6) $table = 'goods_associate_info';//6.产品材质损耗金重关联表
  	elseif ($type == 7) $table = 'goods_associate_deputystone';//7.产品副石关联表
  	return $table;
  }

  /**
   * 1级分销商获取2级分销商id
   * @param int $parent_id
   * @return array
   */
  protected function getTwoTraderId($parent_id){
	$T = M('trader');
	$arrar = $T->field('tid')->where('parent_id = '.$parent_id.' and agent_id = '.C('agent_id'))->select();
	foreach ($arrar as $key => $value) {$arr[] = $value['tid'];}
	return $arr;
  }

  /**
   * 计算上级产品加点，不计算自己的产品
   * 分为销售折扣，和采购折扣
   * @param number $type 1销售折扣2采购折扣
   * @param number $goods_type 1证书货2散货
   * @return number
   */
  protected function countLuozuanAdvantage($type=1,$goods_type='1'){
  	//产品类型加点配置键
  	if($goods_type == 1){
  		$config_key = 'luozuan_advantage';
  	}elseif ($goods_type == 2){
  		$config_key = 'sanhuo_advantage';
  	}
  	if($type == 1){//销售折扣
  		if(in_array(7,$this->group)){
  			return C($config_key);
  		}else{
  			return 0;
  		}
  	}elseif($type == 2){//采购折扣
  		if(in_array(7,$this->group)){
  			return '0';
  		}else{
  			return 0;
  		}
  	}
  }

	/**
	 * 删除实际图片,写在这里后期扩展
	 * @param string $path
	 */
	protected function imagesDel($path) {
		return unlink('./Public/'.$path);
	}


  /**
   * 获取订单产品明细
   * @param int $order_id 订单id
   * @param string $order_sn 订单编号
   * @return array
   */
  protected function getOrderGoodsList($order_id='0',$order_sn='0'){
  	//查询订单产品(分裸钻，成品，散货)
  	$O      = M('order');
  	$field  = 'og.*,gl.goods_number as goods_number_luozuan';//获取证书货字段（库存）
  	$field .= ',gs.goods_weight as goods_number_sanhuo';//获取散货（库存）
  	$field .=',g.goods_number as goods_number_goods';//获取珠宝产品库存
  	//外连接查询
  	$join[1] = 'left join zm_order_goods AS og ON og.order_id = o.order_id';//订单产品
  	$join[2] = 'left JOIN zm_goods_luozuan AS gl ON gl.certificate_number = og.certificate_no';//裸钻产品
  	$join[3] = 'left JOIN zm_goods_sanhuo AS gs ON gs.goods_sn = og.certificate_no AND gs.goods_id = og.goods_id';//散货产品
  	$join[4] = 'left JOIN zm_goods AS g ON g.goods_id = og.goods_id';//成品产品
  	//获取订单产品数据
  	$goodsList = $O->alias('o')->field($field)->where('o.agent_id = '.C('agent_id').' and o.order_id = '.$order_id)->join($join)->group('og_id')->select();
  	$luozuanAdvantage2 = $this->countLuozuanAdvantage(1,1);
  	$orderInfo = $O->where('agent_id = '.C('agent_id'))->find($order_id);
  	//把数据遍历到页面数据
  	foreach ($goodsList as $key => $value) {
  		$value['attribute'] = unserialize($value['attribute']);
  		$info               = $value['attribute'];//订单产品信息
  		if($value['goods_type'] == 1){//裸钻产品
  			$value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
  			$value['goods_number_up'] = intval($value['goods_number_up']);
  			$value['goods_number'] = intval($value['goods_number']);
  			$value['goods_sn_a'] = $info['certificate_type'].$value['certificate_no'];
  			//所有库存不为0的裸钻都是1
  			if($value['goods_number_luozuan']){
  				$value['goods_number_luozuan'] = 1;
  			}
  			$value['advantage'] = $info['dia_discount']+$luozuanAdvantage2;
  			//销售折扣（根据销售价格计算）
  			if(round($value['goods_price_up'],2) > 0) $price = $value['goods_price_up'];
  			else $price = $value['goods_price'];
  			$dollar_huilv = $orderInfo['dollar_huilv'];
  			$value['xian_advantage'] = formatPrice($price/$info['weight']/$dollar_huilv/$info['dia_global_price']*100);
        $array['luozuan'][]      = $value;
  		}elseif ($value['goods_type'] == 2){//散货产品
        //计算每卡单价
        //if($info['goods_price2'] > 0){ $info['goods_price'] = $info['goods_price2'];}
        if($value['goods_number_up']>0){
          $value['goods_price_sanhuo'] = formatPrice($value['goods_price']/$value['goods_number_up']);//散货单价
        }else{
          $value['goods_price_sanhuo'] = formatPrice($value['goods_price']/$value['goods_number']);//散货单价
        }


        //没产品或没库存都是0库存
        if(!isset($value['goods_number_sanhuo'])){
          $value['goods_number_sanhuo'] = '0';
        }
        //4c信息
        $value['4c'] = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
        $array['sanhuo'][] = $value;
      }elseif ($value['goods_type'] == 3 or $value['goods_type'] == 4){//珠宝成品和珠宝定制
  			//没确认前订单产品单价
  		    $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
  		    if($value['goods_type'] == 3){
  		        if($info['goods_sku']['sku_sn']){                  
  		            $attributes = $info['goods_sku']['attributes'];//使用订单里面的SKU规格，防止修改产品
  		            $sku = $this->getGoodsSku($info['category_id'],$info['goods_id'],$info['goods_sku']['sku_sn'],$attributes);
  		            $value['4c'] = $sku['sku'];
  		            $value['goods_sku_number'] = $sku['goods_number'];
  		        }else{
  		            $value['4c'] = '你没有选择规格';
  		        }
  		    }elseif ($value['goods_type'] == 4){
  		        $value['4c'] = $this->getJGSdata($info);
  		    }
  			$value['goods_sn']      = $info['goods_sn'];
  			$value['goods_name']    = $info['goods_name'];
  			$value['goods_number']  = round($value['goods_number']);
       		$value['is_site_goods'] = D('Common/Goods')->where('goods_id='.$value['goods_id'].' and agent_id ='.C('agent_id'))->count();    //是自己站点的商品
  			$array['consignment'][] = $value;
  		}else if($value['goods_type'] == 16){ //szzmzb 板房
  			$value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
			$value['4c'] = '';
			$value['goods_sn']      = $info['goods_sn'];
  			$value['goods_name']    = $info['goods_name'];
  			$value['goods_number']  = round($value['goods_number']);
  			$array['consignment_szzmzb'][] = $value;
  		}
  	}
  	return $array;
  }

  /**
   * 获取产品的SKU
   * @param int $sku_id
   */
  protected function getGoodsSku($category_id,$goods_id,$sku_sn,$attributes){
      $GS   = M('goods_sku');
      $info = $GS->where('goods_id = '.$goods_id." and sku_sn = '$sku_sn'")->find();
      $GA   = M('goods_attributes');//公共
      $GCA  = M('goods_category_attributes');
      $GAV  = M('goods_attributes_value');
      $gacList = $GCA->where('category_id = '.$category_id.' and type = 2 ')->select();
      $ids = $this->parIn($gacList, 'attr_id');
      $list = $GA->where('attr_id in('.$ids.')')->select();
      $list = $this->_arrayIdToKey($list);
      $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
      foreach ($attrValueList as $key => $value) {
          $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
      }
      $attr = explode('^', $attributes);
      $arr['sku'] = 'SKU编号:'.$sku_sn.';';
      $arr['goods_number'] = $info['goods_number'];
      foreach ($attr as $key => $value) {
          $active = explode(':', $value);
          $arr['sku'] .= $list[$active[0]]['attr_name'].':';
          $sub = $list[$active[0]]['sub'];
          if($list[$active[0]]['input_type'] == 2){
              $arr['sku'] .= $sub[$active[1]]['attr_value_name'].';';
          }elseif ($list[$active[0]]['input_type'] == 3){
              $arr['sku'] .= $active[1].';';
          }
      }
      return $arr;
  }

  /**
   * 获取产品的金工石数据
   * @param 订单产品数据
   */
  protected function getJGSdata($info){

    $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
    $deputystoneM        = M('goods_associate_deputystone');


    $info['deputystone_name']  = "";
    $info['deputystone_price'] = "";
    if($info['deputystone']['gad_id']){
        $CG = D('Common/Goods');
        $CG->calculationPoint($info['goods_id']);
        $deputystone = $CG -> getGoodsAssociateDeputystoneAfterAddPoint('gad_id = '.$info['deputystone']['gad_id']);//$deputystoneM -> where('gad_id = '.$dataList[$key]['goods']['deputystone']['gad_id'])->select();
        if($deputystone){
            $info['deputystone_name']  = '副石：'.$deputystone['deputystone_name'];
            $info['deputystone_price'] = $deputystone['deputystone_price'];
        }
    }

    $material                  = $info['associateInfo'];
    //获取材质名称
    $materialinfo              = M("goods_material")->field('material_name,gold_price')->where(array('material_id'=>$material['material_id']))->find();
    $material['material_name'] = $materialinfo['material_name'];
    $material['gold_price']    = $materialinfo['gold_price'];
  	$luozuan = $info['luozuanInfo'];
  	$string  = '材质:'.$material['material_name'].',金重:'.$material['weights_name'].'g';
  	$string .=',损耗:'.$material['loss_name'].'%,基本工费：'.$material['basic_cost'];
  	$string .=',材质金价:'.$material['gold_price'].'<br />';
    $shape_name = M('goods_luozuan_shape')->where("shape_id = '$luozuan[shape_id]'")->field('shape_name')->find();
  	$string .= '//主石:'.$luozuan_shape_array[$info['luozuanInfo']['shape_id']] .',分数:'.$luozuan['weights_name'].'CT,'.$info['deputystone_name'].',镶嵌工费:'.$luozuan['price'].'元';
  	if($info['hand']){ $string .= '//手寸:'.$info['hand'].';';}
  	if($info['word']){ $string .= '//刻字:'.$info['word'].';';}
    if($info['sd_images']){$string .=',个性符号:<img src="'.$info['sd_images'].'" />';}
    if($info['hand1']){ $string .= '//手寸1:'.$info['hand1'].';';}
  	if($info['word1']){ $string .= '//刻字1:'.$info['word1'].';';}
    if($info['sd_images1']){$string .=',个性符号1:<img src="'.$info['sd_images1'].'" />';}
  	if($info['matchedDiamond']){$string .= '//匹配主石:'.$info['matchedDiamond'].';';};

  	return $string;
  }

  /**
   * 计算产品价格<br>
   * 这个方法是实时从数据库获取数据，实时计算
   * 产品总价格=(金重*(1+损耗)*金价+基本工费+钻石工费+副石价格)*加点<br>
   * @param int $goods_id 产品id
   * @param int $material_id 材质id(根据这个获取金价,基本工费,金重，损耗)
   * @param int $gal_id 匹配钻石id(根据这个获取钻石镶嵌工费)
   * @param int $gad_id 匹配副石id(根据这个获取匹配副石价格)
   * @param int $advantage 加点(分销商加点)
   * @param int $type 1是代销货 2是珠宝产品（代销货获取钻明的数据计算，珠宝产品获取客户自己的数据计算）
   */
  public function recalculatingPrice($goods_id,$material_id,$gal_id,$gad_id='',$advantage='0',$type=1){
  	//获取材质金价
  	$GM = M('goods_material');
  	//if($type == 1){
  	//	$where = 'parent_type = 1';
  	//}else{
  		//if(in_array(7,$this->group)){
  		//	$where = 'parent_type = 2 and parent_id = '.$this->tid;
  		//}elseif (in_array(8,$this->group)){
  		//	$where = 'parent_type = 3 and parent_id = '.$this->tid;
  		//}else{
  		//	$where = 'parent_type = 1';
  		//}
  	//}
  	$gold_price = $GM->where(' agent_id= '.C('agent_id').' and material_id = '.$material_id)->getField('gold_price');
  	//获取基本工费，金重，损耗
  	if($type == 1){
  		$GAI = M('goods_associate_info');
  	}else{
  		$GAI = $this->getGoodsTableName(6);
  	}
  	$where = ' agent_id= '.C('agent_id').' and goods_id = '.$goods_id.' and material_id = '.$material_id;
  	$material = $GAI->where($where)->field('weights_name,loss_name,basic_cost')->find();
  	//获取钻石镶嵌工费
  	if($type == 1){
  		$GAL = M('goods_associate_luozuan');
  	}else{
  		$GAL = $this->getGoodsTableName(5);
  	}
  	$where = ' agent_id= '.C('agent_id').' and gal_id = '.$gal_id;
	$luozuan_price = $GAL->where($where)->getField('price');
  	//获取副石价格
  	if($gad_id){
  		if($type == 1){
  			$GAD = M('goods_associate_deputystone');
  		}else{
  			$GAD = $this->getGoodsTableName(7);
  		}
  		$where = '  agent_id= '.C('agent_id').' and gad_id = '.$gad_id;
  		$deputystone_price = $GAD->where($where)->getField('deputystone_price');
  	}
  	return $this->recalculatingPriceDo($material['weights_name'], $material['loss_name'],
  			 $gold_price, $material['basic_cost'], $luozuan_price, $deputystone_price, $advantage);
  }


  /**
   * 计算产品价格<br>
   * 产品总价格=(金重*(1+损耗)*金价+基本工费+钻石工费+副石价格)*加点<br>
   * 实际计算过程  
   */
  function recalculatingPriceDo($weights_name,$loss_name,$gold_price,$basic_cost,$luozuan_price,$deputystone_price,$advantage=0){
    $loss_name = $loss_name/100;

    return formatPrice(($gold_price*$weights_name*(1+$loss_name)+$basic_cost+$luozuan_price+$deputystone_price)*($advantage/100), 2);
  }

  /**
   * 订单添加日志,日志内容可以替换<br>
   * array('证书货:{$goods_sn}已经无货',array('goods_sn'=>'534787979'))
   * @param int $order_id
   * @param array/string $note
   * @param int $type 1系统操作成功 2系统操作失败
   */
  protected function orderLog($order_id,$notes,$msg,$url){
      $OL = M('order_log');
      $arr['order_id'] = $order_id;
      $arr['group_id'] = $this->group[0];
      $arr['user_id'] = $this->uid;
      $arr['create_time'] = time();
      $arr['agent_id'] = C('agent_id');
      //数组的方式可以替换内容
      if(is_array($notes)){
          $note = $notes[0];
          $sub = $notes[1];
          foreach ($sub as $key => $value) {
              $note = str_replace('{$'.$key.'}', $value, $note);
          }
          $arr['note'] = $note;
      }else{
          $arr['note'] = $notes;
      }
      if($OL->add($arr)){
          $this->success($msg,$url);
      }else{
          $this->error('订单添加备注信息失败!');
      }
  }

   /**
   * 重新计算产品价格
   */
    protected function recalculatingPriceS(){
      $G   = D('Common/Goods');//产品表
      $G  -> startTrans();
      $GM  = M('goods_material');//材质表
      $GAL = M($this->getGoodsTableName(5));//产品材质裸钻关联表
      $GAI = M($this->getGoodsTableName(6));//产品材质损耗金重金价关联表
      $GAD = M($this->getGoodsTableName(7));//产品副石关联表
      //公共条件
      $where = $this->buildWhere();
      //获取所有产品数据
      $list  = $G->field('goods_id')->where(' goods_type = 4 and agent_id = '.C('agent_id'))->select();
      //获取所有的材质
      $material = $GM->where($where)->select();
      $material = $this->_arrayIdToKey($material,'material_id');
      //获取所有的产品的第一个匹配主石工费
      $join1 = 'zm_goods AS g ON g.goods_id = gal.goods_id and g.agent_id = '.C('agent_id');
      $galList = $GAL->alias('gal')->join($join1)->field('gal.*')->group('gal.goods_id')->select();
      $galList = $this->_arrayIdToKey($galList,'goods_id');
      //获取所有产品的第一个匹配金重损耗基础工费
      $join2 = 'zm_goods AS g ON g.goods_id = gai.goods_id and g.agent_id = '.C('agent_id');
      $gaiList = $GAI->alias('gai')->join($join2)->field('gai.*')->group('gai.goods_id')->select();
      $gaiList = $this->_arrayIdToKey($gaiList,'goods_id');
      //获取所有的产品的第一个匹配副石价格
      $join3 = 'zm_goods AS g ON g.goods_id = gad.goods_id and g.agent_id = '.C('agent_id');
      $gadList = $GAD->alias('gad')->join($join3)->field('gad.*')->group('gad.goods_id')->select();
      $gadList = $this->_arrayIdToKey($gadList,'goods_id');
      foreach ($list as $key => $value) {
          $infoS        = $gaiList[$value['goods_id']];
          $luozuanS     = $galList[$value['goods_id']];
          $deputystoneS = $gadList[$value['goods_id']];
          $gp = $material[$infoS['material_id']]['gold_price'];//材质金价
          $wn = $infoS['weights_name'];//默认第一个金重
          $ln = $infoS['loss_name'];//默认第一个损耗
          $bc = $infoS['basic_cost'];//默认第一个为基础工费
          $lp = $luozuanS['price'];//默认第一个主石工费为镶嵌工费
          $dp = $deputystoneS['deputystone_price'];//默认第一个副石工费
          $data['goods_price'] = $this->recalculatingPriceDo($wn,$ln,$gp,$bc,$lp,$dp,0);
          $data['update_time'] = time();
          $res = $G->where('goods_id = '.$value['goods_id'])->save($data);
          if(!$res){
              $G->rollback();
              $this->error('更新产品数据出错!');
          }
      }
      $G->commit();
      return true;
  }

  /**
   * 生成IN参数
   * @param array $array
   * @param int $id
   * @return Ambigous <string, unknown>
   */
  protected function parIn($array,$id){
  	$ids ='';
  	foreach ($array as $key => $value) {
  		if($key) $ids .= ','.$value[$id];
  		else $ids .= $value[$id];
  	}
  	return $ids;
  }
  /**
   * 用于分销商出的判断
   * @param int $needBackLevel  是否需要返回等级：0不需要，1需要;0，提示没有权限访问
   */
  protected function _isZifenxiaoshang($needBackLevel = 0){
    	if($this->level != 1)$this->error('你没有访问的权限!');
  }
  /**
   * 用于返回订单的订单id，可以是多个订单
   * @param str $order_sn  订单编号
   */
  protected function returnGoodsid($order_sn = ''){

    if($order_sn == '' or !intval($order_sn)){
      return 0;
    }
    $order_str = '0';
    $order_ids = M('order')->where("order_sn like '%".$order_sn."' and agent_id=".C('agent_id'))->field('order_id')->select();

    foreach($order_ids as $value){
      $order_str = $order_str . ','.$value['order_id'];
    }
    return  $order_str;
  }

  /**
   * 获取采购单产品明细
   * @param int $order_id 订单id
   * @param string $order_sn 订单编号
   * @return array
   */
  protected function getOrderPeriodGoodsList($order_id='0',$order_sn='0'){
    //查询订单产品(分裸钻，成品，散货)
    $O = M('order');
    $field = 'og.*,gl.goods_number as goods_number_luozuan';//获取证书货字段（库存）
    $field .= ',gs.goods_weight as goods_number_sanhuo';//获取散货（库存）
    $field .=',g.goods_number as goods_number_goods';//获取珠宝产品库存
    //外连接查询
    $join[1] = 'left join zm_order_goods AS og ON og.order_id = o.order_id';//订单产品
    $join[2] = 'left JOIN zm_goods_luozuan AS gl ON gl.certificate_number = og.certificate_no';//裸钻产品
    $join[3] = 'left JOIN zm_goods_sanhuo AS gs ON gs.goods_sn = og.certificate_no AND gs.goods_id = og.goods_id';//散货产品
    $join[4] = 'left JOIN zm_goods AS g ON g.goods_id = og.goods_id';//成品产品
    //获取订单产品数据
    if( get_create_user_id() && get_parent_id() ) {
		$goodsList = $O->alias('o')->field($field)->where('o.agent_id = ' . get_parent_id() . ' and uid=' . get_create_user_id() . ' and o.order_id = ' . $order_id)->join($join)->group('og_id')->select();
	}else{
		$goodsList = $O->alias('o')->field($field)->where('o.order_id = ' . $order_id)->join($join)->group('og_id')->select();
	}
	$luozuanAdvantage2 = $this->countLuozuanAdvantage(1,1);
    $orderInfo = $O->where('agent_id = '.C('agent_id'))->find($order_id);
    //把数据遍历到页面数据
    foreach ($goodsList as $key => $value) {
      $value['attribute'] = unserialize($value['attribute']);
      $info = $value['attribute'];//订单产品信息
      if($value['goods_type'] == 1){//裸钻产品
        $value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
        $value['goods_number_up'] = intval($value['goods_number_up']);
        $value['goods_number'] = intval($value['goods_number']);
        $value['goods_sn_a'] = $info['certificate_type'].$value['certificate_no'];
        //所有库存不为0的裸钻都是1
        if($value['goods_number_luozuan']){
          $value['goods_number_luozuan'] = 1;
        }
        $value['advantage'] = $info['dia_discount']+$luozuanAdvantage2;
        //销售折扣（根据销售价格计算）
        if(round($value['goods_price_up'],2) > 0) $price = $value['goods_price_up'];
        else $price = $value['goods_price'];
        $dollar_huilv = $orderInfo['dollar_huilv'];
        $value['xian_advantage'] = formatPrice($price/$info['weight']/$dollar_huilv/$info['dia_global_price']*100);

        $array['luozuan'][] = $value;
      }elseif ($value['goods_type'] == 2){//散货产品
        //计算每卡单价
        if($info['goods_price2'] > 0){ $info['goods_price'] = $info['goods_price2'];}
        $value['goods_price_sanhuo'] = $info['goods_price'];//散货单价
        //没产品或没库存都是0库存
        if(!isset($value['goods_number_sanhuo'])){
          $value['goods_number_sanhuo'] = '0';
        }
        //4c信息
        $value['4c'] = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
        $array['sanhuo'][] = $value;
      }elseif ($value['goods_type'] == 3 or $value['goods_type'] == 4){//珠宝成品和珠宝定制
        //没确认前订单产品单价
        $value['unit_price']    = formatPrice($value['goods_price']/$value['goods_number']);
        if($value['goods_type'] == 3){
            if($info['goods_sku']['sku_sn']){
                $attributes  = $info['goods_sku']['attributes'];//使用订单里面的SKU规格，防止修改产品
                $sku         = $this->getGoodsSku($info['category_id'],$info['goods_id'],$info['goods_sku']['sku_sn'],$attributes);
                $value['4c'] = $sku['sku'];
                $value['goods_sku_number'] = $sku['goods_number'];
            }else{
                $value['4c'] = '你没有选择规格';
            }
        } elseif ($value['goods_type'] == 4){
            $value['4c'] = $this->getJGSdata($info);
        }
        $value['goods_sn']      = $info['goods_sn'];
        $value['goods_name']    = $info['goods_name'];
        $value['goods_number']  = round($value['goods_number']);
        $value['is_site_goods'] = D('Common/Goods')->where('goods_id='.$value['goods_id'].' and agent_id ='.C('agent_id'))->count();    //是自己站点的商品
        $array['consignment'][] = $value;
      }elseif($value['goods_type'] == 16){
      	$value['unit_price']    = formatPrice($value['goods_price']/$value['goods_number']);
      	$value['4c'] = '';
      	$value['goods_sn']      = $info['goods_sn'];
        $value['goods_name']    = $info['goods_name'];
        $value['goods_number']  = round($value['goods_number']);
        $value['is_site_goods'] = D('Common/Goods')->where('goods_id='.$value['goods_id'].' and agent_id ='.C('agent_id'))->count();    //是自己站点的商品
      	$array['consignment_szzmzb'][] = $value;
      }
    }
    return $array;
  }
  
  
  
   // 证书号
  public function formatReportURL2($CertificateId,$CertificateNo,$weight){
  	$url = '';
  	switch (strtoupper($CertificateId)){//过滤证书参数
  		case 'GIA'://1
  			$url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=1";
  			break;
  		case 'IGI'://2
  			$url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=2";
  			break;
  		case 'HRD'://3
  			$url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=3";
  			break;
  		case 'NGTC'://4
  			$url = "/Home/Search";
  			break;
  		default:
  			$url = '/Home/Search';
  			break;
  	}
  	return $url;
  } 
  
  
    protected function getZmOrderPeriodGoodsList($order_id='0',$order_sn='0'){
		$O      = M('order','zm_','ZMALL_DB');
		$field  = 'og.*,gl.goods_number as goods_number_luozuan';//获取证书货字段（库存）
		$field .= ',gs.goods_weight as goods_number_sanhuo';//获取散货（库存）
		$field .=',g.goods_number as goods_number_goods';//获取成品货字段（库存）
		//外连接查询
		$join[1] = 'left join `zm_order_goods` AS og ON og.order_id = o.order_id';//订单产品
		$join[2] = 'left JOIN zm_goods_luozuan AS gl ON gl.certificate_number = og.certificate_no';//裸钻产品
		$join[3] = 'left JOIN zm_goods_sanhuo AS gs ON gs.goods_sn = og.certificate_no AND gs.goods_id = og.goods_id';//散货产品
		$join[4] = 'left JOIN zm_goods AS g ON g.goods_id = og.goods_id';//成品产品
		//获取订单产品数据
		$goodsList = $O->alias('o')->field($field)->where('o.order_id = '.$order_id)->join($join)->group('og_id')->select();
		$orderInfo = $O->find($order_id);
		
		// $luozuanAdvantage2 = $this->countLuozuanAdvantage(1,1);
		$luozuanAdvantage2 = 0;
		//把数据遍历到页面数据
 
		foreach ($goodsList as $key => $value) {
			$value['attribute']     = unserialize($value['attribute']);
			$info = $value['attribute'];//订单产品信息
			
			
		/*
		  if($info['supply_id'] > 0){  //是供应商的货
 
				$SO      = M('supply_order');
				$SOG     = M('supply_order_goods');
				$ACCOUNT = M('supply_account');     
				$where = 'order_id in (select order_id from zm_supply_order where main_order_id = '.$order_id.' ) and   
						  goods_type = '.$orderInfo['goods_type'].' and 
						  goods_id = '.$orderInfo['goods_id'] .' and
						  attribute like \'%;s:9:"supply_id";s:'.strlen($orderInfo['attribute']['supply_id']).':"'.$orderInfo['attribute']['supply_id'].'";%\'';
				$sog_info = $SOG->where($where)->find();
				$value['have_goods'] = $sog_info['have_goods'];
				
				//发货时间
				if($sog_info['fahuo_time'] > 0){
					$value['fahuo_time']  = date('Y-m-d',$sog_info['fahuo_time']);
				}else{
					$value['fahuo_time'] = '';
				}
				
				//供应商信息
				$gongyingshang_info = $ACCOUNT->where('agent_id_build ='.$orderInfo['attribute']['supply_id'])->find();
				$value['supply_id']  = $gongyingshang_info['supply_account_id'];
				$value['corp_name'] = $gongyingshang_info['corp_name'];
		  }
		  */
		  
		  

		  if($value['fahuo_time']>0){
			  $value['fahuo_time']=date('Y-m-d',  $value['fahuo_time']);      
		  }else{
			  $value['fahuo_time']='';
		  }        

			if($value['goods_type'] == 1){//裸钻产品
				$value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
				$value['goods_number_up'] = intval($value['goods_number_up']);
				$value['goods_number'] = intval($value['goods_number']);
				$value['goods_sn_a'] = $info['certificate_type'].$value['certificate_no'];
				$value['goods_name'] = $info['goods_name'];
				//所有库存不为0的裸钻都是1
				if($value['goods_number_luozuan']){
					$value['goods_number_luozuan'] = 1;
				}
				//原折扣
			
			if($info['luozuan_type']==0){
			  $value['advantage'] = $info['dia_discount']+$luozuanAdvantage2;
			}else{
			  $value['advantage'] = $info['dia_discount'];
			}
				
				//销售折扣（根据销售价格计算）
				if(round($value['goods_price_up'],2) > 0) $price = $value['goods_price_up'];
				else $price = $value['goods_price'];
				//订单汇率
				$dollar_huilv = $orderInfo['dollar_huilv'];
				$value['xian_advantage'] = formatPrice($price/$info['weight']/$dollar_huilv/$info['dia_global_price']*100);
				$value['certificate_url'] = $this->formatReportURL2($info['certificate_type'],$value['certificate_no'],$info['weight']);
				  $value['meika_danjia'] = formatPrice($price/$info['weight']);/////////每克拉的价格 
				if($info['matchedDingzhi'] != ''){			
					$value['pipeidingzhi'] = explode(';', $info['matchedDingzhi']);
					$value['pipeidingzhi_str'] = '<a href="/Admin/Goods/productInfo/goods_id/'.$value['pipeidingzhi'][0].'.html" target="_blank">'.$value['pipeidingzhi'][1].','.$value['pipeidingzhi'][2].'</a>';
				}
				$array['luozuan'][] = $value;
			}elseif ($value['goods_type'] == 2){//散货产品
				//计算每卡单价
				if($info['goods_price2'] > 0){ $info['goods_price'] = $info['goods_price2'];}
				$value['goods_price_sanhuo'] = $info['goods_price'];//散货单价
				//没产品或没库存都是0库存
				if(!isset($value['goods_number_sanhuo'])){
					$value['goods_number_sanhuo'] = '0';
				}
				//计算销售每卡单价
				if($value['goods_number_up']>0){$value['goods_number'] = $value['goods_number_up'];}
				$number = $value['goods_number'];
				if($value['goods_price_up']>0){$value['goods_price'] = $value['goods_price_up'];}
				$price = $value['goods_price'];
				$value['xiao_goods_price'] = formatPrice($price/$number);
				//产品标题
				  $value['goods_name'] = $info['goods_name'];
				//4c信息
				$value['4c'] = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
				  $array['sanhuo'][] = $value;
			}elseif ($value['goods_type'] == 3){//成品产品   or $value['goods_type'] == 4 
			//没确认前订单产品单价
			  $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
			  if($value['goods_type'] == 3){
				if($info['specification_id']){
					//goods_associate_specification
					$GAS      = M('goods_associate_specification','zm_','ZMALL_DB');
					$info = $GAS->where('specification_id = '.$info['specification_id'])->find();
					if($info['pid']){//有上级规格获取上级的,这是第2级
					  $info2 = $GAS->where('specification_id = '.$info['pid'])->find();
					  $spec2 = $info2['attribute_name'].':'.$info2['specification_name'].',';
					  if($info2['pid']){//有上级规格获取上级的,这是第3级，最多3级
						  $info3 = $GAS->where('specification_id = '.$info2['pid'])->find();
						  $spec3 = $info3['attribute_name'].':'.$info3['specification_name'].',';
					  }
					}
					$spec1 = $info['attribute_name'].':'.$info['specification_name'];
					$value['4c'] = '选择的规格('.$spec3.$spec2.$spec1.')';
				}else{
					$value['4c'] = '你没有选择规格';
				}
			  }elseif ($value['goods_type'] == 4){
				$material = $info['associateInfo'];
				$luozuan = $info['luozuanInfo'];
				$string = '材质:'.$material['material_name'].',金重:'.$material['weights_name'];
				$string .=',损耗:'.$material['loss_name'].',基本工费：'.$material['basic_cost'];
				$string .=',材质金价:'.$material['gold_price'];
				$string .= '//主石:圆形,分数:'.$luozuan['weights_name'].'CT,镶嵌工费:'.$luozuan['price'].'元';

				if(!empty($info['hand']) and $info['hand'] !='undefined'){
				  if(empty($info['hand_title'])){
					$string = $string.   '//手寸:'.$info['hand'].';';
				  }else{
					$string = $string. '//'. $info['hand_title'].':'.$info['hand'].';';
				  } 
				} 

				if($info['word']){ $string .= '//刻字:'.$info['word'].';';}
				if($info['matchedDiamond']){$string .= '//匹配主石:'.$info['matchedDiamond'].';';}

				$value['4c'] = $string;
			  }
			  $value['goods_sn'] = $info['goods_sn'];
			  $value['goods_name'] = $info['goods_name'];
			  $value['goods_number'] = round($value['goods_number']);
			  $array['goods'][] = $value;
		  }elseif ($value['goods_type'] == 4){//成品订制
			//没确认前订单产品单价
			  $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
			  $value['goods_number_up'] = intval($value['goods_number_up']);
			  $value['goods_number'] = intval($value['goods_number']);
			  
			  
				$material = $info['associateInfo'];
				$luozuan = $info['luozuanInfo'];
				$Cstring = '材质:'.$material['material_name'].',金重:'.$material['weights_name'];
				$Cstring .=',损耗:'.$material['loss_name'].',基本工费：'.$material['basic_cost'];
				$Cstring .=',材质金价:'.$material['gold_price'];
				$Cstring .= '//主石:圆形,分数:'.$luozuan['weights_name'].'CT,镶嵌工费:'.$luozuan['price'].'元';

				if(!empty($info['hand']) and $info['hand'] !='undefined'){
				  if(empty($info['hand_title'])){
					$Cstring = $Cstring.   '//手寸:'.$info['hand'].';';
				  }else{
					$Cstring = $Cstring. '//'. $info['hand_title'].':'.$info['hand'].';';
				  } 
				} 

				if($info['word']){ $Cstring .= '//刻字:'.$info['word'].';';}
				if($info['matchedDiamond']){$Cstring .= '//匹配主石:'.$info['matchedDiamond'].';';}
			  
			  $value['4c'] = $Cstring;
			  
			  $value['goods_price2']     = formatPrice($value['goods_price']/$value['goods_number']);
			  $value['goods_price3']     = formatPrice($value['goods_price_up']/$value['goods_number_up']);
			  $value['goods_sn']         = $info['goods_sn'];
			  $value['goods_name']       = $info['goods_name'];
			  $value['goods_number_up']  = round($value['goods_number_up']);
			  $value['goods_number']     = round($value['goods_number']);


			//得到未税价；  
			if($value['goods_number_up']>0){
			  $number = $value['goods_number_up'];
			}else{
			  $number = $value['goods_number'];
			}
			if($value['goods_price_up']>0){
			  $price = $value['goods_price_up'];
			}else{
			  $price = $value['goods_price'];
			}
			$value['xiao_goods_price'] = formatPrice($price/$number);
			$array['consignment'][]    = $value;

		  }elseif ($value['goods_type'] == 5 or $value['goods_type'] == 6){//代销产品
			  //没确认前订单产品单价
			  $value['unit_price'] = formatPrice($value['goods_price']/$value['goods_number']);
			  $value['goods_number_up'] = intval($value['goods_number_up']);
				$value['goods_number'] = intval($value['goods_number']);
				if($value['goods_type'] == 6){
					if($info['specification_id']){
						$GAS      = M('goods_associate_specification','zm_','ZMALL_DB');
						$info = $GAS->where('specification_id = '.$specification_id)->find();
						  if($info['pid']){//有上级规格获取上级的,这是第2级
							  $info2 = $GAS->where('specification_id = '.$info['pid'])->find();
							  $spec2 = $info2['attribute_name'].':'.$info2['specification_name'].',';
							  if($info2['pid']){//有上级规格获取上级的,这是第3级，最多3级
								  $info3 = $GAS->where('specification_id = '.$info2['pid'])->find();
								  $spec3 = $info3['attribute_name'].':'.$info3['specification_name'].',';
							  }
						  }
						$spec1 = $info['attribute_name'].':'.$info['specification_name'];
						$value['4c'] = '选择的规格('.$spec3.$spec2.$spec1.')';
					}else{
						$value['4c'] = '你没有选择规格';
					}
				  }elseif ($value['goods_type'] == 5){
						$material = $info['associateInfo'];
						$luozuan = $info['luozuanInfo'];
						$C5string = '材质:'.$material['material_name'].',金重:'.$material['weights_name'];
						$C5string .=',损耗:'.$material['loss_name'].',基本工费：'.$material['basic_cost'];
						$C5string .=',材质金价:'.$material['gold_price'];
						$C5string .= '//主石:圆形,分数:'.$luozuan['weights_name'].'CT,镶嵌工费:'.$luozuan['price'].'元';

						if(!empty($info['hand']) and $info['hand'] !='undefined'){
						  if(empty($info['hand_title'])){
							$C5string = $C5string.   '//手寸:'.$info['hand'].';';
						  }else{
							$C5string = $C5string. '//'. $info['hand_title'].':'.$info['hand'].';';
						  } 
						} 

						if($info['word']){ $C5string .= '//刻字:'.$info['word'].';';}
						if($info['matchedDiamond']){$C5string .= '//匹配主石:'.$info['matchedDiamond'].';';}
						$value['4c'] = $C5string;
				  }
				  $value['goods_price2']     = formatPrice($value['goods_price']/$value['goods_number']);
				  $value['goods_price3']     = formatPrice($value['goods_price_up']/$value['goods_number_up']);
				  $value['goods_sn']         = $info['goods_sn'];
				  $value['goods_name']       = $info['goods_name'];
				$value['goods_number_up']  = round($value['goods_number_up']);
				$value['goods_number']     = round($value['goods_number']);
				$array['consignment'][]    = $value;
			}elseif($value['goods_type']=='7'){		//板房数据
 
				  $banfang_info                 = M('order_goods_banfang_info','zm_','ZMALL_DB') -> where('order_goods_id = "'.$value['og_id'].'"') -> select();
				  foreach($banfang_info as $xyz=>$abc){
					$banfang_info[$xyz]['zhushi_info']  = json_decode($abc['zhushi_info'],true);
					$banfang_info[$xyz]['fushi_info']   = json_decode($abc['fushi_info'],true);
				  }
				  $value['banfang_info'] = $banfang_info;
					//$value['luozuan_list'] = json_decode($value['matched_diamonds'],true);
					//从关联的裸钻订单表获取裸钻信息
					$luozuan_list_info = M('order_goods','zm_','ZMALL_DB') -> where(" relation_og_id = ".$value['og_id']) -> select();
					  foreach($luozuan_list_info as $k=>$v){
						$luozuaninfo = unserialize($v['attribute']);
						$luozuaninfo['price'] = $luozuaninfo['rmb']= $v['goods_price_up']>0?$v['goods_price_up']:$v['goods_price'];
						$value['luozuan_list'][]  = $luozuaninfo;
					  }
					$order_custom_stone    = M('order_custom_stone','zm_','ZMALL_DB') -> where(" og_id = '$value[og_id]' ") -> select();
					foreach( $order_custom_stone as $k => $r ){
						$order_custom_stone[$k]['attribute']               = json_decode($r['attribute'],true);
						$order_custom_stone[$k]['attribute']['number']     = $r['goods_number'];
						$order_custom_stone[$k]['attribute']['goods_type'] = $r['goods_type'];
						if($r['goods_type']=='0'){
							$value['luozuan_list_kelai'][] = $order_custom_stone[$k]['attribute'];
						}else if($r['goods_type']=='1'){
							$value['caizuan_list_kelai'][] = $order_custom_stone[$k]['attribute'];
						}else if($r['goods_type']=='2'){
							$value['sanhuo_list_kelai'][]  = $order_custom_stone[$k]['attribute'];
						}
					}
				  $value['unit_price']      = formatPrice($value['goods_price']/$value['goods_number']);
				  $value['goods_number_up'] = intval($value['goods_number_up']);
				  $value['goods_number']    = intval($value['goods_number']);
				  
				  $value['4c']              = $this->getJGSdata($info);
				  $value['goods_price2']    = formatPrice($value['goods_price']/$value['goods_number']);
				  $value['goods_price3']    = formatPrice($value['goods_price_up']/$value['goods_number_up']);
				  $value['goods_sn']        = $info['goods_sn'];
				  $value['goods_name']      = $info['goods_name'];
				  $value['goods_number_up'] = round($value['goods_number_up']);
				  $value['goods_number']    = round($value['goods_number']);
				  //得到未税价；  
				  if($value['goods_number_up']>0){
					$number = $value['goods_number_up'];
				  }else{
					$number = $value['goods_number']; 
				  }
				  if($value['goods_price_up']>0){
					$price = $value['goods_price_up'];
				  }else{
					$price = $value['goods_price'];
				  }
				  $value['xiao_goods_price'] = formatPrice($price/$number);
				  $array['banfang'][]        = $value;
				
				
				
			}
		}
		return $array;
	}

	public function addRuleAll(){
		if(IS_POST){
			$param = array(
					'type'=>I('type'),
					'parent'=>I('parent'),
					'title'=>I('title'),
					'url'=>I('url'),
					'sort'=>intval(I('sort'))
			);
			if(!in_array($param['type'],array(1,2,3,4))){
				$this->error('请选择类型');
			}

			$modes = array(
					'1'=>'menu',
					'2'=>'menu',
					'3'=>'auth_rule_type',
					'4'=>'auth_rule',
			);
			$datas = array(
					'1'=>array(
						'menu_name'=>$param['title'],
						'url'=>$param['url'],
						'parent_id'=>$param['parent'],
						'menu_ico'=>'menuE',
						'sort_id'=>$param['sort'],
					),
					'2'=>array(
							'menu_name'=>$param['title'],
							'url'=>$param['url'],
							'parent_id'=>$param['parent'],
							'menu_ico'=>'',
							'sort_id'=>$param['sort'],
					),
					'3'=>array(
							'auth_rule_type'=>$param['title'],
							'agent_id'=>0
					),
					'4'=>array(
							'name'=>$param['url'],
							'title'=>$param['title'],
							'type'=>1,
							'status'=>1,
							'condition'=>'',
							'pid'=>$param['parent'],
							'sort_id'=>$param['sort'],
							'agent_id'=>0,
					),
			);
			$bool = M($modes[$param['type']])->add($datas[$param['type']]);
			$this->success('添加成功');
		}else{
			$this->display('User/addRuleAll');
		}
	}

	public function ajaxRuleAll(){
		$param = array(
			'type'=>I('type'),
			'parent'=>I('parent'),
			'title'=>I('title'),
			'url'=>I('url'),
		);
		$modes = array(
			'2'=>'menu',
			'4'=>'auth_rule_type',
		);
		$fields = array(
			'2'=>'mid as pid,menu_name as name',
			'4'=>'pid,auth_rule_type as name',
		);
		$wheres = array(
			'2'=>array('parent_id'=>0),
			'4'=>array()
		);

		$data = array(
				'status' => 0,
				'msg'    => '获取信息失败',
				'info' 	 =>array()
		);
		if(in_array($param['type'],array(2,4))){
			$info = M($modes[$param['type']])->where($wheres[$param['type']])->field($fields[$param['type']])->select();
		}
		if(!empty($info)){
			$data = array(
					'status' => 100,
					'msg'    => '获取信息成功',
					'info'	 =>$info
			);
		}
		$this->ajaxReturn($data);
	}
  
  
  
  
  
  
  
  
  
  
}
