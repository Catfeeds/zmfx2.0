<?php
namespace Mobile\Controller;
use Think\Controller;
class MobileController extends Controller{
    
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
		if(CONTROLLER_NAME == "User" && !$_SESSION['m']['uid']){
    			 $this->redirect("/Public/login"); 
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

        if( $templateInfo['is_new'] ){
            $controller_name = CONTROLLER_NAME;
            $action_name     = ACTION_NAME;
            $obj             = \Mobile\Controller\CtrFactoryController::createCtr($controller_name);
            if( $obj !== false && method_exists($obj,$action_name)){
                $obj->$action_name();
                unset($obj,$this);
                die();
            }
            unset($obj);
        }
        //
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
                $join       = 'zm_template_config AS tc ON t.template_id = tc.template_id';
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
                $this->nav = $nav;
            }else{ // 钻明
                $this->uType = 1;
                $this->group = 1; 
                $this->traderId = 0;
				$this->trader_id = 0;
                $theme = 'default';
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
            '__CSS__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Css',
            '__IMG__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Img',
            '__JS__'=>__ROOT__.'/Application/Mobile/View/'.$theme.'/Js',
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
}