<?php
namespace Mobile\Controller;
use Think\Controller;
class NewMobileController extends Controller{
    protected $cartNumber;
    /**
     * 初始化方法
     */
    protected function  _initialize(){
        ob_end_clean();
		$this->checkAgent();
        $this->domain = getDomainTrader();
        //获取所有分类
        $this->goodsCat = getGoodsCat($this->domain);
        $this->AutoMuban();
        $this->checkUser();
        //设置配置
        setConfig($this->domain);
        // 获取系统主题设置
        $this->themeConfig = $this->getThemeConfig();

        // 获取购物车数量
        $this->getCartNumber();
    }

    /**
     * 获取当前购物车产品数量
     */
    public function getCartNumber(){
        if($this->userAgent == "session=''"){
            $this->cartNumber = 0;
        }else{
            $this->cartNumber = M('cart')->where("agent_id='".C('agent_id')."' and ".$this->userAgent)->count();
        }
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
	
     // 保存用户信息到session
    public function checkUser(){
        // 注册界面用户名检查，邮箱检查，注册，登陆，用户协议，不需要做重定向
/*		
    	if($this->traderId>0){
    		 
    	}else{
    		$noAction  = array('regcheckusername','checkusername', 'regist','regcheckemail', 'reg', 'login', 'registration'); 
    		if(!in_array(strtolower(ACTION_NAME), $noAction) &&
	            session('m.uid') == '' && ACTION_NAME != 'login'
	            && CONTROLLER_NAME != 'Article'
	            && CONTROLLER_NAME != 'Search'
	            && CONTROLLER_NAME != 'Index'){ 
			   		$this->redirect('/Public/login');  
    		}  
    	}
		
		*/
		if(CONTROLLER_NAME.'/'.ACTION_NAME != 'User/index'){
			if(CONTROLLER_NAME == "User" && !$_SESSION['m']['uid']){
					 $this->redirect("/Public/login"); 
			} 
		}
    	if($_SESSION['m']['uid']){
    		$this->userAgent = "uid='".$_SESSION['m']['uid']."'";
    		$this->userAgentKey = "uid";
    		$this->userAgentValue = $_SESSION['m']['uid'];
    	}else{
    		$this->userAgent = "session='".$_COOKIE['PHPSESSID']."'";
    		$this->userAgentKey = "session";
    		$this->userAgentValue = $_COOKIE['PHPSESSID'];
    	}
    	
		if(C('user_login_show_product')=='1'){
			if(session('m.is_validate') == 0 && CONTROLLER_NAME == 'Goods'){
				$this->redirect('User/index');
			}
		}
        $this->uId = session('m.uid');
        $this->username = session('m.username');
        $this->realname = session('m.realname');
        $this->birthday = session('m.birthday');
        $this->birthdayYear = session('m.birthdayYear');
        $this->birthdayMonth = session('m.birthdayMonth');
        $this->birthdayDay = session('m.birthdayDay');
        $this->sex = session('m.sex');
        $this->phone = session('m.phone');
        $this->email = session('m.email');
        $this->qq = session('m.qq');
        $this->job = session('m.job');
        $this->legal = session('m.legal');
        $this->company_name = session('m.company_name');
        $this->company_address = session('m.company_address');
        $this->company_lincense = session('m.company_lincense');
        $this->rank_id = session('m.rank_id');
        $this->reg_time = session('m.reg_time');
        $this->last_logintime = session('m.last_logintime');
        $this->last_ip = session('m.last_ip');
        $this->is_validate = session('m.is_validate');
        $this->note = session('m.note');
        $this->parent_type = session('m.parent_type');
        $this->parent_id = session('m.parent_id');
        $this->shouXinEdu = session('m.shouXinEdu');
        $this->diamondDiscount = session('m.diamondDiscount');
        $this->sanhuoDiscount = session('m.sanhuoDiscount');
        $this->interestRate = session('m.interestRate');
    }
    
     //根据域名检测类型,检测分销商id.检测模板
    public function AutoMuban(){
        //2017/7/24 sunyang
        //如果是B2C模板则走New控制器
        $T            = M('template');
        $join         = 'zm_template_config AS tc ON tc.template_id = t.template_id and tc.template_status = 1 and tc.agent_id = '.C("agent_id");
        $field        = 't.*,tc.template_status,tc.template_color AS color';
        $templateInfo = $T->alias('t')->join($join)->where('t.template_type = 2')->field($field)->find();
        $theme = $templateInfo['template_path'] ? $templateInfo['template_path'] : 'default';

        $info = M('agent')->where("agent_id = '".C('agent_id')."'")->find();
        if($info){
			//如果手机网站不存在于数据库，则不开启。zhy 	2017年6月9日 15:23:07
			empty($info['mobile_domain']) && header('Location:'.'http://'.$info['domain']);
            if($info['parent_id']){ // 分销商
                //分销商类型判断
                $this->uType = $this->trader_type = $info['level'];
                //分销商id
                $this->traderId  = C('agent_id');
                $this->trader_id = C('agent_id');
                /*$join       = 'zm_template_config AS tc ON t.template_id = tc.template_id';
                $field      = 't.template_path';
                $traderInfo = M('template')->alias('t')->field($field)->join($join)->where('t.status = 1 and t.template_type = 2  and tc.agent_id = '.C('agent_id'))->find();
                if($traderInfo){
                    $theme = $traderInfo['template_path'];
                }else{
                    $theme = 'default';
                }
                $this->author      = $info["agent_name"];
                // 获取分销商的分类
                $this->categorys   = $this->getCategoryByTrader($this->uType,$this->traderId);
                // 获取分销商的导航
                $nav = $this->getTraderNav($this->uType,$this->traderId);
                $this->nav = $nav;*/
            }else{ // 钻明
                $this->uType = 1;
                $this->group = 1; 
                $this->traderId = 0;
				$this->trader_id = 0;
                //$theme = 'default';
            }
			C('DEFAULT_AGENT_MOBILE_DOMAIN',$info['mobile_domain']);
			C('DEFAULT_AGENT_DOMAIN',$info['domain']);
        }else{
            $this->error(L('you_domain_error'));
        }
        $where = "config_key='luozuan_advantage'";
        if($this->uType > 1){
        	$parent_luozuan_advantage = M("config_value")->where($where.' and agent_id= '.$info['parent_id'])->getField("config_value");
        	C("luozuan_advantage",C("luozuan_advantage") + $parent_luozuan_advantage); 
        }
        C('DEFAULT_THEME',$theme);

		$this->getConfig();
        $this->setMyAllParam();

        //模板替换
        $tmplParseString = array(
            '__CSS__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Styles/css',
            '__IMG__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Styles/img',
            '__JS__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Styles/js',
            '__Public__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Public',
            '__Upload__'=>__ROOT__.'/Public/Uploads',
            '__ASS__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/assets'
        );
        C('TMPL_PARSE_STRING',$tmplParseString);
    }
    private function setMyAllParam(){
        //获取分销商模块开启状态
        $new_rules = get_zm_agent_setting();
        $this->dingzhi_type = 0;
        C('new_rules',$new_rules);
    }

    /**
     * 把数据库的配置读到系统中
     * @return void
     */
    protected function getConfig(){
        $C     = M('config');
        $data  = $C -> field('config_key,config_value') -> select();
        $data1 = M('config_value') -> where('agent_id = '.C("agent_id")) -> field('config_key,config_value') -> select();
        //初始化默认值
        foreach ($data as $key => $value) {
            C($value['config_key'],$value['config_value']);
        }
        //注入设置的配置
        foreach ($data1 as $key => $value) {
            C($value['config_key'],$value['config_value']);
        }
        $contact_qq = C('contact_qq');
        if( isset($contact_qq) && !empty($contact_qq) ){
            $contact = explode(';',$contact_qq);
            foreach($contact as $k=>$v){
                $contact_qqs[] = explode(':',$v);
            }                       
            $this->contact_qq  = $contact_qqs;
        }
        
        $hot_search_val = C('hot_search');
        if( isset($hot_search_val) && !empty($hot_search_val) ){
        	$hot_search = explode(',',$hot_search_val);
        	$this->hot_search  = $hot_search;
        }
        //注入钻明汇率
        if( ! C('dollar_huilv_type') ){
            $dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
            C('dollar_huilv',$dollar_huilv);
        }
    }

    // 获取分销商的导航
    public function getTraderNav($trader_type,$trader_id){
    	$nav = M("nav") -> where("parent_type=$trader_type AND parent_id=$trader_id AND status=1 and agent_id = ".C('agent_id')) -> order('sort asc') -> select();
    	$nav = _arrayRecursive($nav,'nid','pid');
    	if($nav){
    		foreach($nav as $key=>$val){ 
    			switch($val['nav_type']){
    				case 1: 
    					$nav[$key]['nav_url'] = "/Goods/goodsCat/cid/".$val['category_id'].".html"; 
    				break;
    				case 2: 
    					switch($val['function_id']){
    						case 1:$nav[$key]['nav_url'] = "/Goods/goodsDiy"; 
    						break;
    						case 2:$nav[$key]['nav_url'] = "/Goods/sanhuo"; // 散货功能待定 
    						break;
    						case 3:$nav[$key]['nav_url'] = "/Article/index"; // 百科
    						break;
    						case 4:$nav[$key]['nav_url'] = "/User/index"; // 用户中心
    						break;
    						case 5:$nav[$key]['nav_url'] = "/User/orderList"; // 用户订单
    						break;
    						case 6:$nav[$key]['nav_url'] = "/User/traderAdd"; // 加入分销商
    						break;
    					}
    				break;
    				case 3:  
    				default:
    					$nav[$key]['nav_url'] = $val['nav_url'];
    				break;
    			}
    		}
    	}
    	return $nav;
    }
    
    /*
     * 获取分销商的分类
     * @param fxsType 
     * @param fxsId 分销商Id
     * 
     */
    public function getCategoryByTrader($fxsType=0,$fxsId=0){
    	$where     = " agent_id=".C("agent_id")." AND is_show=1";
    	$categorys = M("goods_category_config")->where($where)->select();
    	if($categorys){
    		foreach($categorys as $key=>$val){
    			$categorys[$key]['children'] = M("goods_category_config")->where($where." AND pid=".$val['category_id'])->select();
    		}
    	} 
    	return $categorys;
    }
	
	// 保存登陆日志
    protected function saveLoginInfo($uid) {
        $loginInfo['login_ip'] = $this->real_ip();
        $loginInfo['login_time'] = time();
        $loginInfo['browser'] = $this->get_user_browser();
        $loginInfo['uid'] = $uid;
        $loginInfo['agent_id'] = C("agent_id");
        $loginInfo['client_type'] = $this->get_os();
        if (M('user_log')->data($loginInfo)->add()) {
            return true;
        }
        return false;
    }
	/**
     * 获得用户的真实IP地址
     * @access  public
     * @return  string
     */
    protected function real_ip() {
        static $realip = NULL;
        if ($realip !== NULL) { return $realip; }
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
                foreach ($arr AS $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realip = $ip;
                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER['REMOTE_ADDR']))
                {
                    $realip = $_SERVER['REMOTE_ADDR'];
                }
                else
                {
                    $realip = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }
        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
        return $realip;
    }
	/**
     * 获得浏览器名称和版本
     *
     * @access  public
     * @return  string
     */
    protected function get_user_browser() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) { return ''; }
        $agent       = $_SERVER['HTTP_USER_AGENT'];
        $browser     = '';
        $browser_ver = '';
        if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)){
            $browser     = 'Internet Explorer';
            $browser_ver = $regs[1];
        }elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)){
            $browser     = 'FireFox';
            $browser_ver = $regs[1];
        }elseif (preg_match('/Maxthon/i', $agent, $regs)){
            $browser     = '(Internet Explorer ' .$browser_ver. ') Maxthon';
            $browser_ver = '';
        }elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)){
            $browser     = 'Opera';
            $browser_ver = $regs[1];
        }elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)){
            $browser     = 'OmniWeb';
            $browser_ver = $regs[2];
        }elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)){
            $browser     = 'Netscape';
            $browser_ver = $regs[2];
        }elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs) and stristr($agent,'Chrome')==false){
            $browser     = 'Safari';
            $browser_ver = $regs[1];
        }elseif (preg_match('/Chrome\/([^\s]+)/i', $agent, $regs)){
            $browser     = 'Chrome';
            $browser_ver = $regs[1];
        }elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)){
            $browser     = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
            $browser_ver = $regs[1];
        }elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)){
            $browser     = 'Lynx';
            $browser_ver = $regs[1];
        }
        if (!empty($browser)) {
            return addslashes($browser . ' ' . $browser_ver);
        } else {
            return 'Unknow browser';
        }
    }
    /**
     * 获得客户端的操作系统
     *
     * @access  private
     * @return  void
     */
    protected function get_os(){
        if (empty($_SERVER['HTTP_USER_AGENT'])){
            return 'Unknown';
        }
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $os    = '';
        if (strpos($agent, 'win') !== false){
            if (strpos($agent, 'nt 5.1') !== false) {
                $os = 'Windows XP';
            } elseif (strpos($agent, 'nt 5.2') !== false) {
                $os = 'Windows 2003';
            } elseif (strpos($agent, 'nt 5.0') !== false) {
                $os = 'Windows 2000';
            } elseif (strpos($agent, 'nt 6.0') !== false) {
                $os = 'Windows Vista';
            } elseif (strpos($agent, 'nt') !== false) {
                $os = 'Windows NT';
            } elseif (strpos($agent, 'win 9x') !== false && strpos($agent, '4.90') !== false) {
                $os = 'Windows ME';
            } elseif (strpos($agent, '98') !== false) {
                $os = 'Windows 98';
            } elseif (strpos($agent, '95') !== false) {
                $os = 'Windows 95';
            } elseif (strpos($agent, '32') !== false) {
                $os = 'Windows 32';
            } elseif (strpos($agent, 'ce') !== false) {
                $os = 'Windows CE';
            }
        } elseif (strpos($agent, 'linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($agent, 'unix') !== false) {
            $os = 'Unix';
        } elseif (strpos($agent, 'sun') !== false && strpos($agent, 'os') !== false) {
            $os = 'SunOS';
        } elseif (strpos($agent, 'ibm') !== false && strpos($agent, 'os') !== false) {
            $os = 'IBM OS/2';
        } elseif (strpos($agent, 'mac') !== false && strpos($agent, 'pc') !== false) {
            $os = 'Macintosh';
        } elseif (strpos($agent, 'powerpc') !== false) {
            $os = 'PowerPC';
        } elseif (strpos($agent, 'aix') !== false) {
            $os = 'AIX';
        } elseif (strpos($agent, 'hpux') !== false) {
            $os = 'HPUX';
        } elseif (strpos($agent, 'netbsd') !== false) {
            $os = 'NetBSD';
        } elseif (strpos($agent, 'bsd') !== false) {
            $os = 'BSD';
        } elseif (strpos($agent, 'osf1') !== false) {
            $os = 'OSF1';
        } elseif (strpos($agent, 'irix') !== false) {
            $os = 'IRIX';
        } elseif (strpos($agent, 'freebsd') !== false) {
            $os = 'FreeBSD';
        } elseif (strpos($agent, 'teleport') !== false) {
            $os = 'teleport';
        } elseif (strpos($agent, 'flashget') !== false) {
            $os = 'flashget';
        } elseif (strpos($agent, 'webzip') !== false) {
            $os = 'webzip';
        } elseif (strpos($agent, 'offline') !== false) {
            $os = 'offline';
        } else {
            $os = 'Unknown';
        }
        return $os;
    }

    /**
     * 获取B2C手机版主题设置
     * 
     * @author wangkun
     * @date 2017/7/26
     *
     * @return array 返回主题配置数组或空
     */
    public function getThemeConfig()
    {
        $themeConfig = M('b2c_wap_theme')->where([
            'agent_id' => C('agent_id'),
        ])->find();

        return $themeConfig;
	}

    /**
     * 数据转换为JSON格式
     *
     * @author wangkun
     * @date 2017/7/27
     * @param array $_data
     * @param int $ret
     * @param string $msg
     *
     * @return json 转换后的数据
     */
    public function echoJson( $_data = array() , $ret=100 , $msg=''){
        if($ret==100){
            $data['ret']     = 100;
            if($msg){
                $data['msg'] = $msg;
            }else{
                $data['msg'] = '操作成功';
            }
            $data['data'] = $_data;
        }else{
            $data['ret']  = $ret;
            $data['msg']  = $msg;
            $data['data'] = new \stdClass();//空对象
        }
		header ( 'Content-type: application/json;charset=utf-8' );
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );die;
    }
}