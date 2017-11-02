<?php

/**
 * 售后管理
 * @author 王松林
 */
namespace Admin\Controller;
use Think\Page;
header("Content-Type: text/html; charset=UTF-8");
class SmsManageController extends AdminController {

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

	public function smsList($p=1,$n=13){
    	$M = M('menu');
    	$xjMenu       = $M->where("parent_id=100")->select();
		$this -> xjMenu = $xjMenu;
        $agent_id     = C('agent_id');
        $S            = new \Common\Model\Sms();
        $data         = $S -> getSmsList( $agent_id );
        $this -> data = $data;
    	$this -> display();
	}

	public function smsInfo(){

        $sms_type_key = I('sms_type_key','','trim');
        $agent_id     = C('agent_id');
        $S            = new \Common\Model\Sms();
		if(IS_POST && $sms_type_key){
            $sms_info = I('sms_info','','trim');
            $status   = I('status','','trim');
            $code     = $S -> saveSms( $sms_type_key , $sms_info , $status , $agent_id );
            if( $code ){
                $this -> success('提交成功',U('Admin/SmsManage/smsList'));
            }else{
                $this -> error('提交失败');
            }
            die;
        }
        $this -> info = $S -> getSmsInfo($sms_type_key,$agent_id);
        $this        -> display();

	}
	
	/**
	*	短信发送日志列表
	*	znegmingming
	*	2016-08-18
	*/
	public function smsSendLogList($p='1',$n='13'){
		$M = M('menu');
    	$xjMenu       = $M->where("mid=100")->find();
		$this -> xjMenu = $xjMenu;
		$SSG = M('sms_send_log');
        $count = $SSG->where('f_agent='.C("agent_id"))->count();
        $Page = new \Think\Page($count,$n);
        $this->list = $SSG->where('f_agent='.C("agent_id"))->limit($Page->firstRow.','.$Page->listRows)->order('f_time DESC')->select();
        $this->page = $Page->show();
		$this -> display();
	}
	/**
	*	删除短信发送日志
	*	znegmingming
	*	2016-08-18
	*/
    public function delSmsSendLog(){
        $SSG  	= M('sms_send_log');
		$f_id 	= I('f_id','','intval');
		$ssgArr = $SSG->where("f_id=".$f_id)->find();
		if(!empty($ssgArr)){
			if($SSG->where("f_id=".$f_id." and f_agent=".C("agent_id"))->delete()){
				$this->log('success', 'A3');
			}else{
				$this->log('error', 'A4');
			}
		}else{
			$this->log('error', 'A4');	
		}
    }
}
