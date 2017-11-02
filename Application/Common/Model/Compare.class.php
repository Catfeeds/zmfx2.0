<?php
	/**
	* 裸钻比较模型
	* auth: fangkai
	* time: 2017/4/20
	**/
namespace Common\Model;

class Compare {
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2017-4-20
	**/
	public function __construct(){
		$this->agent_id = C('agent_id');
	}
   
    /**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2017-4-20
	**/
	public function get_count($uid){
		$compare	= M('goods_compare');
		$gid_array	= $compare->field('gid')->where(array('agent_id'=>$this->agent_id,'uid'=>$uid))->select();
		if($gid_array){
			$gid_array	= array_column($gid_array,'gid');
			$where['zm_goods_compare.gid']	= array('in',$gid_array);
			$where['zm_goods_compare.uid']	= $uid;
			$where['zm_goods_compare.agent_id']	= $this->agent_id;
			$where['zm_goods_luozuan.goods_number']   = array('gt',0);
			$count		= M('goods_luozuan')
							->where($where)
							->join(' left join zm_goods_compare on zm_goods_luozuan.gid = zm_goods_compare.gid ')
							->join(' left join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid ')
							->count();
		}else{
			$count	= 0;
		}
		
		return $count;
	}

   
}