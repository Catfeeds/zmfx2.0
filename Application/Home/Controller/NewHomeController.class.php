<?php
/**
 * 前端公共类
 */
namespace Home\Controller;
use Think\Controller;
class NewHomeController extends Controller{

    // 前端初始化方法
    public function _initialize(){
        $this->checkAgent();
        $this->AutoMuban();
        //$this->site_statistics_code_tsz = htmlspecialchars_decode(C('site_statistics_code_tsz'));
        $this->checkUser();
		if(sp_is_mobile()){			
  			$mobile_domain = M('agent')->where("agent_id =".C("agent_id"))->getField('mobile_domain');
  			if($mobile_domain!=''){
  				$http_host = "http://".$mobile_domain; 
  				header("Location:$http_host");   
  				exit();
  			}
        }
		
		
		//zhy 2016年11月3日 17:39:34  用户体验功能
 		$is_company_name_callback = I('is_company_name_callback');
		$cookie_is_company_name_callback = cookie('is_company_name_callback');
		if($is_company_name_callback){
			C("site_name",$is_company_name_callback);
			cookie('is_company_name_callback',$is_company_name_callback);
		}elseif($cookie_is_company_name_callback){
			C("site_name",$cookie_is_company_name_callback);
		}
		

		
		
		$this->templateSetting	= C("templateSetting");		//模块控制开关 
		$this->getPositionList();
        $this->getCat();
        $this->getCartNumber();
		$this->getlinkList();
		$this -> getBookedData();
		$this->getBottomHelpList();
		
		//zhy  2016年12月12日 14:17:00	首页广告。
		$this->index_ad_bbox();
    }

    //data 表示数据，ret:100表示成功，非100表示失败，msg:用数组表示详情
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
        echo json_encode($data);die;
    }
    /**
     * 根据域名获取agent_id
     * @return void
     */
    private function checkAgent(){
		$is_inside_callback = I('is_inside_callback',0,'intval');
		if($is_inside_callback)	cookie('is_inside_callback_to', '1'); 
        $agent_id = get_agent_id();
        if($agent_id){
            C("agent_id",$agent_id);
        }else{
            $this->error('非法域名!');
        }
    }
	
    /**
     * 把配置读到系统中
     * @param int $type 0钻明官网1分销商
     * @return void
     */
    protected function getConfig(){
        $C    = M('config');
        $data = $C->field('config_key,config_value')->select();
        $data1 = M('config_value') -> where('agent_id = '.C("agent_id"))->field('config_key,config_value')->select();
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
			C('contact_qqs',$contact_qqs);
            $this->contact_qq  = $contact_qqs;
        }

        $hot_search_val = C('hot_search');
        if( isset($hot_search_val) && !empty($hot_search_val) ){
            $hot_search = explode(',',$hot_search_val);
            $this->hot_search  = $hot_search;
        }

        if( ! C('dollar_huilv_type') ){
            $dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
            C('dollar_huilv',$dollar_huilv);
        }
    }

    /**
     * 发送邮件
     * @param  string $address 接收的邮件地址
     * @param  string $title   邮件标题
     * @param  string $message 邮件内容
     * @return boolean
     */
    protected function sendMail($address,$title,$message) {
        vendor('PHPMailer.class#phpmailer');
        $mail = new \PHPMailer();            // 设置PHPMailer使用SMTP服务器发送Email
        $mail->IsSMTP();                      // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->CharSet='UTF-8';               // 添加收件人地址，可以多次使用来添加多个收件人
        $mail->AddAddress($address);          // 设置邮件正文
        $mail->Body=$message;                 // 设置邮件头的From字段。
        $mail->From=C('MAIL_ADDRESS');        // 设置发件人名字
        $mail->FromName=L('L9018');           // 设置邮件标题
        $mail->Subject=$title;                // 设置SMTP服务器。
        $mail->Host=C('MAIL_SMTP');           // 设置为"需要验证" ThinkPHP 的C方法读取配置文件
        $mail->SMTPAuth=true;                // 设置用户名和密码。
        $mail->Username=C('MAIL_LOGINNAME');
        $mail->Password=C('MAIL_PASSWORD');   // 发送邮件。
        return($mail->Send());
    }
    // 保存用户信息到session
    public function checkUser(){
        if(CONTROLLER_NAME == "User" && !$_SESSION['web']['uid']){
            $this->redirect("/Home/Public/login");
        }
        if($_SESSION['web']['uid']){
            $this->userAgent = "uid='".$_SESSION['web']['uid']."'";
            $this->userAgentKey = "uid";
            $this->userAgentValue = $_SESSION['web']['uid'];
        }else{
            $this->userAgent = "session='".$_COOKIE['PHPSESSID']."'";
            $this->userAgentKey = "session";
            $this->userAgentValue = $_COOKIE['PHPSESSID'];
        }

        if(C('user_login_show_product')=='1'){
            if(session('web.is_validate') == 0 && CONTROLLER_NAME == 'Goods'){
                $this->redirect('Home/User/index');
            }
        }

        /*
        if(session('web.rank_id') ==1 && CONTROLLER_NAME == 'Goods'){
            $this->redirect('Home/User/index');
        }
        */

        $this->uId = session('web.uid');
        $this->username = session('web.username');
        $this->realname = session('web.realname');
        $this->birthday = session('web.birthday');
        $this->birthdayYear = session('web.birthdayYear');
        $this->birthdayMonth = session('web.birthdayMonth');
        $this->birthdayDay = session('web.birthdayDay');
        $this->sex = session('web.sex');
        $this->phone = session('web.phone');
        $this->email = session('web.email');
        $this->qq = session('web.qq');
        $this->job = session('web.job');
        $this->legal = session('web.legal');
        $this->company_name = session('web.company_name');
        $this->company_address = session('web.company_address');
        $this->company_lincense = session('web.company_lincense');
        $this->rank_id = session('web.rank_id');
        $this->reg_time = session('web.reg_time');
        $this->last_logintime = session('web.last_logintime');
        $this->last_ip = session('web.last_ip');
        $this->is_validate = session('web.is_validate');
        $this->note = session('web.note');
        $this->parent_type = session('web.parent_type');
        $this->parent_id = session('web.parent_id');
        $this->shouXinEdu = session('web.shouXinEdu');
        $this->diamondDiscount = session('web.diamondDiscount');
        $this->sanhuoDiscount = session('web.sanhuoDiscount');
        $this->interestRate = session('web.interestRate');
    }

    //根据域名检测类型,检测分销商id.检测模板
    public function AutoMuban(){
        $info = M('agent')->where("agent_id = '".C('agent_id')."'")->find();
        if($info and $info['parent_id']){
            $this->uType    = $info['level'];
            $this->traderId = $info['parent_id'];
        }else{
            $this->uType    = 1;
            $this->traderId = 0;
        }
		
		if($info['domain']){
			C('agent_domain_once',$info['domain']);
		}
        // 配置数据初始化
        $this->getConfig();

        if(empty($_SESSION['is_inside_call'])) {//标记手机端调用的时候跳过此段
            // 获取分销商的分类和导航
            $this->categorys = $this->getGoodsCategory();
            $this->nav = $this->getTraderNav();
            //设置模板相关
            $this->setTemplate();
            //裸钻加点
            ////////////////////////////大问题
            //$this->luozuan_advantage = 0;//C('luozuan_advantage');
        }
		
		
        //获取分销商模块开启状态
        $new_rules = get_zm_agent_setting();
        C('new_rules',$new_rules);
    }

    /**
     * 获取分类
     */
    protected function getGoodsCategory(){
        $GC = M('goods_category');
        $where = 'gcc.agent_id = '.C("agent_id");
        $join[] = 'zm_goods_category_config AS gcc ON gcc.category_id = gc.category_id and gcc.is_show = 1 and '.$where;
        $list = $GC->alias('gc')->join($join)->select();
        return $this->_arrayRecursive($list, 'category_id', 'pid','0','1');
    }

    /**
     * 设置模板相关
     */
    private function setTemplate(){
        //获取开启的模板和颜色
        $T     = M('template');
        $join  = 'zm_template_config AS tc ON tc.template_id = t.template_id and tc.template_status = 1 and tc.agent_id = '.C("agent_id");
        $field = 't.*,tc.template_status,tc.template_color AS color,tc.style';
        $templateInfo = $T->alias('t')->join($join)->where('t.template_type = 1')->field($field)->find();
        if(!$templateInfo){
            $templateInfo['color'] = "";
            $templateInfo['template_path'] = "default";
        }
        //组装文件地址
        $color    = strtolower($templateInfo['color']);
        $file1    = './Application/Home/View/'.$templateInfo['template_path'].'/Styles/Css/color/'.$color.'.css';
        $file2    = './Application/Home/View/'.$templateInfo['template_path'].'/Styles/Css/color/'.$color.'_'.C('agent_id').'.css';
        if(file_exists($file2)){
            $file = $file2;
        }else {
            $file = $file1;
        }
		$this->css_zidingyi	   = htmlspecialchars_decode(html_entity_decode($templateInfo['style']));
        $this->getCssColorFile = substr($file, 1);
        // 模板替换
        C('DEFAULT_THEME',$templateInfo['template_path']);
        $tmplParseString = array(
    			'__CSS__'=>__ROOT__.'/Application/Home/View/'.$templateInfo['template_path'].'/Styles/Css',
    			'__IMG__'=>__ROOT__.'/Application/Home/View/'.$templateInfo['template_path'].'/Styles/Img',
    			'__JS__'=>__ROOT__.'/Application/Home/View/Base/Js',
    			'__Public__'=>__ROOT__.'/Public',
    			'__Upload__'=>__ROOT__.'/Public/Uploads',
    	);
        /*
        if($templateInfo['template_modle']=="*"){
            $modle = '/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.。ACTION_NAME;
            $mg = M('module_group');
            $join = 'left JOIN zm_module on zm_module_group.module_group_id = zm_module.module_group_id and  zm_module_group.name = "'.$modle.'"';
            $field = 'group_concat(zm_module.code_tags) as code_tags';
            $templateInfo = $mg->join($join)->where('zm_module_group.name = "'.$modle.'"')->field($field)->find();
            if(empty($templateInfo['code_tags'])) {
                //表示全部权限
                $this->template_modle = null;
            }else{
                $this->template_modle = explode(',',$templateInfo['code_tags']);
            }
        }else{
            $this->template_modle = unserialize($templateInfo['template_modle']);
        }*/
        C('TMPL_PARSE_STRING',$tmplParseString);
    }


    // 获取分销商百科分类的所有子分类
    public function getCategory($cid){
        return M("article_cat")->where(" is_show = 1 AND agent_id=".C("agent_id")." AND parent_id='$cid'")->select();
    }

    // 获取分销商的导航
    public function getTraderNav(){
        $nav = M("nav")->where("agent_id=".C("agent_id")." AND status=1")->order('sort asc')->select();
        $nav = _arrayRecursive($nav,'nid','pid');
        return $nav;
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

    // 分销商获取全部分类
    public function getCat(){
        //不显示产品分类页面
        $noCat = array(
            'home/public/login',//登录
            'home/article/index',//文章首页
            'home/goods/goodsdiy',//产品定制
            'home/public/registr',//注册
            'home/article/detail',//文章详情
            'home/article/articlelist',//文章分类
            'home/public/forgetps',//密码找回
            'home/user/index',//用户中心
            'home/goods/cart',//用户中心
        );
        if(!in_array(strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME), $noCat)){
            $this->catShow = 1;
        }
    }

    // 获取登录后的用户的购物车的数量

    public function getCartNumber(){
        if($this->userAgent == "session=''"){
            $this->cartNumber = 0;
        }else{
            $this->cartNumber = M('cart')->where("agent_id='".C('agent_id')."' and ".$this->userAgent)->count();
        }
    }
	
	/**
	 * auth	：fangkai
	 * content：获取友情链接
	 * time	：2016-7-23
	**/
    public function getlinkList(){
		$links = D('links');
		$where['agent_id'] 		= C('agent_id');
		$where['link_status'] 	= 1;
		$this->linkList = $links->where($where)->order('listorder asc')->select();
	}
    
	/*
     * 获取分销商的分类
     * @param fxsType 
     * @param fxsId 分销商Id
     * 
     */
    public function getCategoryByTrader($fxsType,$fxsId){
        //$where = " parent_type=$fxsType AND parent_id=$fxsId";
        $where = " agent_id=".C("agent_id")." AND is_show=1";
        $categorys = M("goods_category_config")->where($where)->select();
        if($categorys){
            foreach($categorys as $key=>$val){
                $categorys[$key]['children'] = M("goods_category_config")->where($where." AND pid=".$val['category_id'])->select();
            }
        }
        return $categorys;
    }


    /**
     * 后台移植过来的递归数组
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
    /*获取钻石编号对应的型号 */
    public function getShapeNoDiamonds($shapeNo){
        $shape = '';	//默认（标识非常规异形钻）
        if($shapeNo=='001'){$shape='ROUND';}
        if($shapeNo=='002'){$shape='OVAL';}
        if($shapeNo=='003'){$shape='MARQUISE';}
        if($shapeNo=='004'){$shape='HEART';}
        if($shapeNo=='005'){$shape='PEAR';}
        if($shapeNo=='006'){$shape='PRINCESS';}
        if($shapeNo=='007'){$shape='EMERALD';}
        if($shapeNo=='008'){$shape='CUSHION';}
        if($shapeNo=='010'){$shape='RADIANT';}
        if($shapeNo=='011'){$shape='BAGUETTE';}
        if($shapeNo=='009'){$shape='TRILLIANT';}
        return $shape;
    }
	
	/**
	 * auth	：fangkai
	 * @param：底部帮助分类和文章
	 * time	：2016-10-7
	**/
	public function getBottomHelpList(){
		$Help		= new \Common\Model\Help();
		$helpArticleList= $Help->getArticleListTwo();
		$this->helpArticleList	= $helpArticleList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取在线，下单预约门店列表，省份列表
	 * time	：2016-10-18
	**/
	public function getBookedData(){
		$PbulicBase	= new \Common\Model\Common\PublicBase();
		$Store		= new \Common\Model\Store();
		
		/* 查出门店列表 */
		$onlineStoreList	= $Store->getStoreList();

		/* 查出省级地区 */
		$parent_id	= 1;
		$province	= $PbulicBase->getRegion($parent_id); 
			
		$this->onlineStoreList	= $onlineStoreList;
		$this->province			= $province;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取B2C楼层设置信息
	 * time	：2016-10-28
	**/
	public function getPositionList(){
		$TemplateSeeting	= new \Admin\Model\TemplateSeeting();
		$positionList	= $TemplateSeeting->getPositionList();
		$positionList	= arraySort($positionList,0,'position_id');

		$this->positionList	= $positionList;
	}
	
	
	
	/**
	*	首页弹框广告
	*	zhy find404@foxmail
	*	2016年12月12日 10:08:22
	*/
	public function index_ad_bbox(){
		if(!$_SESSION['web']['uid']){
			$TA = M ('template_ad');
			$where['agent_id'] 	= C('agent_id');
			$where['status']	= 1;
			$where['ads_id']	= 45;
			$this->index_ad_bbox= $TA->where($where)->order('sort ASC')->find();
		}
	}	
	
	
}