<?php
namespace Supply\Controller;
class IndexController extends SupplyController{
    public function index(){
        $this              -> go();
        if(D('SupplyUser') -> checkOpenSupply($this->uid)){
            //进入主程序窗口
            $this -> redirect('Manage/index');
        }else{
            //进入申请
            $this -> redirect('Index/apply_open');
        }
    }
	
	//退出
	public function logout(){
		unset($_SESSION['supply']);
		//进入登陆页面
		$this -> success(L('logout_success'),U('Index/login'));
	}
	
    public function login(){
        if($_POST){
            $type                    = I('post.type','login');            
            if($type=='login'){
               
				if($_SESSION['login']['stutas']>2){
					$verification_code = I('post.verification_code','');
					if( !$this -> checkVerify($verification_code) ){
						$info  = L('text_verification_code');
                        $_SESSION['login']['stutas'] = $_SESSION['login']['stutas'] + 1;
						$array = array( 'code' => 0 ,'login_stutas'=>$_SESSION['login']['stutas'], 'error_text' => $info );
						echo json_encode( $array );die;
					}
				}
                $user_name                  = I('post.username','');
                $password                   = I('post.password','');
                list($code,$info)          = D('SupplyUser') -> checkLogin($user_name,$password);
                if( $code ){
                    if( $info['status'] == 0 ){ //未验证
                        if( time() > $info['verify_expire_time'] ) {
                            D('Message') -> sendUserVerifyOfEmail($info['uid'],$info['username']);
                        }
						$msg = L('error_msg_081');
                        $array = array('code' => 2, 'error_text' => $msg);
                        echo json_encode($array);die;
                    }
                    $_SESSION['supply']     = $info;
                    if( D('SupplyUser')    -> checkOpenSupply( $info['uid']) ){
                        $info               = D('SupplyAccount') -> getCurSupplyAccount();//供应宝信息写入session
                        $_SESSION['supply'] = array_merge( $_SESSION['supply'] , $info );
                        $url                = U('Manage/index');
                    }else{
                        $url                = U('Index/apply_open');
                    }
                    $array                  = array('code'=>1,'error_text'=>'','url'=>$url);
                    echo json_encode( $array );die;
                }else{
					$login_stutas_num = $_SESSION['login']['stutas'];
					$login_stutas_num = ++$login_stutas_num;
					$_SESSION['login']['stutas']=$login_stutas_num;
                    $error_text = L('text_login_error');
                    $array      = array( 'code' => 0 ,'error_text' => $error_text,'login_stutas'=>$login_stutas_num );
                    echo json_encode( $array );die;
                }
            }else{
                $verification_code = I('post.verification_code','');
                if( !$this->checkVerify($verification_code) ){
                    $info  = L('text_verification_code');
                    $array = array( 'code' => 0 , 'error_text' => $info );
                    echo json_encode( $array );
                    die;
                }
                $userObj   = D('SupplyUser');
 
               if( $userObj -> create() ){
                    $uid      = $userObj -> add();
                    $userinfo = $userObj -> getUserInfoByUid($uid);
                    if($userinfo['status'] != '1' ){
                        if( C('VERIFY_EMAIL') == true ){
							$msg = L('error_msg_081');
                            D('Message') -> sendUserVerifyOfEmail($uid,$userinfo['username']);
                            $array = array('code'=>2,'error_text'=>$msg);
                            echo json_encode($array);die;
                        }
                    }else{
                        $_SESSION['supply'] = $userinfo;
                        $url                = U('Index/apply_open');
                        $array              = array('code'=>1,'error_text'=>'','url'=>$url);
                    }
                    echo json_encode($array);die;
               }else{
                    $info  = $userObj->getError();
                    $array = array('code'=>0,'error_text'=>$info);
                    echo json_encode($array);die;
               }
            }
        }
        if( $this->checkUser() ) {
           
            if( D('SupplyUser') -> checkOpenSupply($_SESSION['supply']['uid']) ){
                $this->redirect('Manage/index');
            }          
            $this->redirect('Index/apply_open');
        }else {
            $this->display($this->Template_Path.':Index:login');
        }
    }

    //生成验证码
    public function verify(){
        ob_end_clean();
        $config           = array(
            'fontSize'    =>    30,     // 验证码字体大小
            'length'      =>    4,      // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
            'useCurve'    =>    false, // 关闭验证码杂点
        );
        $Verify  = new \Think\Verify($config);
        $Verify -> entry();
    }
	
    public function apply_open(){
		//没登陆
        $obj  = D('Config');
        $this->supply_kefu_tel   = $obj->getOneZmConfigValue('supply_kefu_tel');
        $this->supply_kefu_email = $obj->getOneZmConfigValue('supply_kefu_email');
       
        if($this -> checkUser()){
            $saM  = D('SupplyAccount');
            $info = $saM -> getCurSupplyAccount();
            //检查是否通过审核
            if(D('SupplyUser') -> checkOpenSupply($_SESSION['supply']['uid'])){
                $this -> redirect('Manage/index');
            }
            //检查是否有post数据
            if($_POST){
				$data = I('post.');
				if($data['business_type']){
					$data['business_type']     = implode(',',$data['business_type']);
				}
				if($saM->create($data)){
					//若证件照存在，则状态赋值1，反之则赋值0；
					$data['supply_status']='1';
                    if($info){
						$saM  -> where('supply_account_id = '.$info['supply_account_id'])->save($data);
                    }else{
						$data['uid'] = $_SESSION['supply']['uid'];
                        $saM  -> add($data);
                    }
                    $_SESSION['supply']['supply_status'] = $data['supply_status'];
                }else{
                    //error_字段名
                    $error_info = $saM->getError();
                    array_walk($error_info,function($v,$k){
                        $this -> assign( 'error_' . $k , $v );
                    });
                }
				$data = array_merge($data,$info);
				$this     -> assign('info',$data);
				$this -> assign('supply_status',$saM->getSupplyStatus());
				$this -> display($this->Template_Path.'Index:apply_wait');die;
			}
				/* 查出省级地区 */
				$region = M('region');
				$province = $region->where(array('parent_id' => 1))->select(); 
				$this->province = $province;
				/* 查询出城市 */
				if($info['province']){
					$pro_id   = $region->where(array('region_name' => $info['province']))->getField('region_id');
					$city	  = $region->where(array('parent_id'=>$pro_id))->select();
					$this->city = $city;
				}
				
				$this -> info = $info;
				$this -> assign('supply_status',$saM->getSupplyStatus());	
				$this -> display($this->Template_Path.'Index:apply_open');
		
        }else {
            $this -> redirect('Index/login');
        }
    }

	/**
	 * auth	：fangkai
	 * content：图片上传
	 * time	：2016-6-3
	**/
    public function supplyImg() {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 2097152 ;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath = './Uploads/supply/'; // 设置附件上传目录    // 上传文件
		$name = I('get.name');
        $info = $upload->uploadOne($_FILES[$name]);
        if(!$info) {// 上传错误提示错误信息
			$result = array('success'=>false, 'msg'=>'成功', 'data'=>$upload->getError());
        }else{// 上传成功
			$webname = 'http://'.$_SERVER['HTTP_HOST'];
			$img_path = substr($info['savepath'].$info['savename'],1);
            $data['img_path'] = $webname."/Public".$img_path;
            $result = array('success'=>true, 'msg'=>'成功', 'data'=>$data);
        }
		echo json_encode($result);
    }

    public function verifyEmail(){
        $code     = $_GET['code'];
        if(empty($code)){
            $this -> error(L('entry_account_name'));
        }
        if( $code = base64_decode($code) ){
            $data            = array();
            if( json_decode($code,true) && is_array($data) ){
                $data        = json_decode($code,true);
                $verify_code = base64_decode($data['verify_code']);
                $email       = base64_decode($data['email']);
                $userinfo    = D('SupplyUser') -> where(" username= '".$email."' and verify_code = '$verify_code'") -> find();
                if(empty($userinfo)){
                    $this     -> error(L('error_email_verify_fail'));
                }else{
                    if( time()  < $userinfo['verify_code'] ){
                        $this -> error(L('error_email_verify_overdue'));
                    }
                    if( $userinfo['status'] != 0 ){
                        $this -> error(L('error_email_verify_repeat'));
                    }
                    D('SupplyUser') -> where(" username= '".$email."' and verify_code = '$verify_code'")
                              -> save(array('status'=>'1'));
                    $this     -> success(L('text_email_verify_ok'),U('Supply/Index/login'));
                }
            } else {
                $this -> error(L('entry_account_name'));
            }
        }else{
            $this     -> error(L('entry_account_name'));
        }
    }

    public function sendEmailCode(){
		$time = time ();
        $email = $_POST['email'];
		if (! empty ( $_SESSION['login']['code_time'] )) {
            $endtime = $time - $_SESSION['login']['code_time'];
            if ($endtime < 60) {
                echo ( L('warning_wait_60') );
                exit();
            }
		}
		if(empty($email)){
            echo L('error_email_verify_empty');
            exit();
        }
		if(!preg_match("/^([0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})$/",$email) ) {
            echo L('error_email_verify_format');
            exit();
        }
        $info  = D('SupplyUser') -> where(" username = '$email' ") -> find();
        if($info['status']<0){
            echo L('error_email_verify_lock');
            die;
        }
        $code  = D('Message') -> sendVerifyCodeOfEmail($email);
		$_SESSION['login']['code_time']=$time;
        if($code){
            echo L('error_email_verify_send_ok');
        }else{
            echo L('error_email_verify_send_fail');
        }
    }

    public function reset_one(){
        if($_POST){
            $email       = I('post.email','');
            $verify_code = I('post.verify_code','');
            $info        = D('SupplyUser') -> where(" username = '$email' and verify_code = '$verify_code'") -> find();
            if( empty($info) ){
                $this -> assign('status',2);   //失败
            } else if( time() > $info['verify_expire_time'] ){
                $this -> assign('status',3);   //过期
            } else {
                $_SESSION['uid'] = $info['uid'];
                $this -> assign('status',1); //验证成功
            }
        }
        $this->display($this->Template_Path."Index:reset_one");
    }

    public function reset_next(){
        if( empty($_SESSION['uid']) ) {
            $this -> redirect(U('Supply/Index/reset_one','',true,true));
        }else{
			$id=$_SESSION['uid'];
		}
        if($_POST){
            $password       = I('post.password','');
            $reset_password = I('post.reset_password','');
            if( !$password || !$reset_password ){
                $this  -> assign('status',2); //为空
            } elseif( $password != $reset_password ){
                $this  -> assign('status',3); //不一样
            } elseif( strlen($password) < 6 ||strlen($password)>20 ){
                $this  -> assign('status',4); //太短
            } elseif (!preg_match('/^[A-Za-z0-9_]+$/',$password)) {
				$this  -> assign('status',2); //用户名只能是字母、数字以及下划线( _ )的组合。;
			} else {
				$data_status=D('SupplyUser') -> where( "uid = $id") -> getField ('status');
                $data = D('SupplyUser') -> field('password,reset_password,') -> create($_POST);
				if($data_status!=$data['status']){
					$data['status']=$data_status;
				}
                D('SupplyUser') -> where( "uid = $id") -> save($data);
                $this  -> assign('status',1); //成功
            }
        }
        $this->display($this->Template_Path."Index:reset_next");
    }

    public function reset_out(){
        $this->display($this->Template_Path."Index:reset_out");
    }
}
