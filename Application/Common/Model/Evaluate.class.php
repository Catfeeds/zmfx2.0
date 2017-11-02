<?php
	/**
	* 评价模型类
	* auth: fangkai
	* time: 2016/11/14
	**/
namespace Common\Model;

class Evaluate extends ArticleBase {
	
	public $error = '';
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-5
	**/
	public function __construct(){
		parent::__construct();
		$this->agent_id = C('agent_id');
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取列表
	 * time	：2016-11-14
	**/
	public function getEvaluateList($field,$where,$join,$order,$limit){
		$OEVAL 			= M('order_eval');
		$evaluateList	= $OEVAL
							->field($field)
							->where($where)
							->join($join)
							->order($order)
							->limit($limit)
							->select();

		if($evaluateList){
			return $evaluateList;
		}else{
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取评价列表
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-11-14
	**/
	public function evaluateList($where,$limit,$is_show=true){
		if($is_show == true){
			$where['zm_order_eval.status'] = 1; 
		}
		$field	= 'zm_order_eval.*,zm_user.phone';
		$order 	= 'zm_order_eval.oe_id DESC';
		$join	= 'left join zm_user on zm_user.uid = zm_order_eval.uid';
		$evaluateList	= $this->getEvaluateList($field,$where,$join,$order,$limit);

		if($evaluateList){
			return $evaluateList;
		}else{
			return false;
		}

	}
	
	/**
	 * auth	：fangkai
	 * @param：获取评价总数
				is_show: true 查询显示; false 查询显示和不显示
	 * time	：2016-11-14
	**/
	public function evaluateCount($where,$is_show=true){
		if($is_show == true){
			$where['zm_order_eval.status'] = 1; 
		}
		$join	= 'left join zm_user on zm_user.uid = zm_order_eval.uid';

		$OEVAL 	= M('order_eval');
		$count	= $OEVAL
					->where($where)
					->join($join)
					->count();
		if(empty($count)){
			$count = 0;
		}
		
		return $count;
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取评价详情
	 * time	：2016-11-14
	**/
	public function getEvaluateInfo($oe_id){
		$OEVAL	= M('order_eval');
		$Goods	= D('Common/Goods');
		$where['zm_order_eval.oe_id'] = $oe_id;
		$checkEval	= $OEVAL->where($where)->find();
		if(empty($checkEval)){
			$this->error = '该评价不存在';
			return false;
		}
		
		$field	= 'zm_order_eval.*,zm_user.phone,zm_goods.thumb,zm_goods.goods_name,zm_goods.goods_type';
		$join	= 'left join zm_user on zm_user.uid = zm_order_eval.uid left join zm_goods on zm_goods.goods_id = zm_order_eval.goods_id';
		
		$evaluateList	= $this->getEvaluateList($field,$where,$join,$order,$limit);
		$evaluateInfo	= $Goods->completeUrl($evaluateList[0]['goods_id'],$type='goods',$evaluateList[0]);

		if($evaluateInfo){
			return $evaluateInfo;
		}else{
			return false;
		}
	
	}
	
	/**
	 * auth	：fangkai
	 * @param：评价修改
	 * time	：2016-11-15
	**/
	public function evaluateSave($oe_id,$data){
		$OEVAL	= M('order_eval');
		$where['zm_order_eval.oe_id'] = $oe_id;
		$checkEval	= $OEVAL->where($where)->find();
		if(empty($checkEval)){
			$this->error = '该评价不存在';
			return false;
		}
		$save['status']	= $data['status'];
		$result	= $OEVAL->where($where)->save($save);
		if($result){
			return true;
		}else{
			$this->error = '修改失败';
			return false;
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * @param：评价删除
	 * time	：2016-11-15
	**/
	public function evaluateDetele($oe_id){
		$OEVAL	= M('order_eval');
		$where['zm_order_eval.oe_id'] = $oe_id;
		$checkEval	= $OEVAL->where($where)->find();
		if(empty($checkEval)){
			$this->error = '该评价不存在';
			return false;
		}
		$result	= $OEVAL->where($where)->delete();
		if($result){
			return true;
		}else{
			$this->error = '删除失败';
			return false;
		}
	
	}
}
?>
