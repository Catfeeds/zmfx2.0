<?php
/**
 * 文章模块
 */
namespace Admin\Controller;
use Think\Page;
class EvaluateController extends AdminController {

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

    /**
	 * auth	：fangkai
	 * content：评价列表
	 * time	：2016-11-14
	**/
    public function evalList(){
		$Evaluate		= new \Common\Model\Evaluate();
		$keyword		= I('get.keyword','');
		$value			= I('get.value','');
		if($keyword && $value && in_array($keyword,array('username','phone'))){
			$where['zm_user.'.$keyword]	= $value;
			$this->value	= $value;
			$this->keyword	= $keyword;
		}

		$start			= I('get.start','','intval');
		if($start && in_array($start,array('1','2','3','4','5'))){
			$where['zm_order_eval.start']	= $start;
			$this->start	= $start;
		}
		$createtime		= I('get.createtime','');
		if($createtime){
			$where['zm_order_eval.createtime']	= array(array('gt',strtotime($createtime)),array('lt',strtotime($createtime)+3600*24));
			$this->createtime	= $createtime;
		}
		
		$count			= $Evaluate->evaluateCount($where,false);
		$Page			= new \Think\Page($count,20);
		$limit			= $Page->firstRow.','.$Page->listRows;
		$evaluateList	= $Evaluate->evaluateList($where,$limit,false);
		
		$this->page		= $Page->show();
		$this->evaluateList	= $evaluateList;
		$this->display();
    }


    /**
	 * auth	：fangkai
	 * content：评价详情
	 * time：2016-11-14
	**/
    public function evalInfo(){
		$oe_id		= I('get.oe_id','','intval');
		$Evaluate	= new \Common\Model\Evaluate();
		if($_POST){
			$data = I('post.');
			if($oe_id){
				$result		= $Evaluate->evaluateSave($oe_id,$data);
				if($result){
					$this->success('修改成功');
				}else{
					$this->error($Evaluate->error);
				}
			}
		}else{
			$evalInfo	= $Evaluate->getEvaluateInfo($oe_id);
			if($evalInfo == false){
				$this->error($Evaluate->error);
			}

			$this->evalInfo	= $evalInfo;

			$this->display();
			
		}

	}

	/**
	 * auth	：fangkai
	 * content：评价删除
	 * time：2016-11-14
	**/
    public function evalDelete(){
		$oe_id	= I('get.oe_id','','intval');
		
		$Evaluate	= new \Common\Model\Evaluate();
		$result		= $Evaluate->evaluateDetele($oe_id);
		if($result){
			$this->success('删除成功');
		}else{
			$this->error($Evaluate->error);
		}
	}

	
	
}