<?php
/**
 * 产品模型类
 */
namespace Home\Model;
use Think\Model;
class GoodsModel extends Model{
	
	public  $count;
	private $page;
    private $sql = array(
        'where'=>null,
        'order'=>'',
        'limit'=>'',
        'join' =>array(),
    );

	public function __construct(){
		parent::__construct();
    }
	
	/**
	 * auth	：fangkai
	 * content：获取成品/定制产品列表
	 * time	：2016-6-29
	**/
    public function getGoodsList($where='1=1',$sort= 'goods_id DESC',$pageid=1,$pagesize=20) {
		$limit    = (($pageid-1)*$pagesize).','.$pagesize;
		$goods_list = $this->where($where)->order($sort)->limit($limit)->select();
		if($goods_list){
			return $goods_list;
		}else{
			return false;
		}
    }
	
	
	/**
	 * auth	：fangkai
	 * content：获取成品/定制产品总数
	 * time	：2016-6-29
	**/
    public function getcount($where='1=1'){
		$count = $this->where($where)->count();
		return $count;
	}
	
	/**
	 * auth	：fangkai
	 * content：获取首页展示产品
				dataList：二级分类列表
	 * time	：2016-7-1
	**/
    public function hotGoodsList($agent_id,$dataList,$uid){
		if(empty($agent_id)){
			return false;
		}
		if(empty($dataList)){
			return false;
		}
		foreach($dataList as $key=>$val){
			//首先查询出在首页展示的产品
			$show_where['promotion'] = 1;
			$show_where['home_show'] = 2;
			$show_where['agent_id']  = $agent_id;
			$show_where['category_id'] = array('in',$val['category_array']);
			$show_list = $this->where($show_where)->order('setting_time DESC')->limit('0,5')->select();
			//计算出首页展示产品的总数
			$show_count = count($show_list);
			if($show_count < 5){
				$limit = 5-$show_count;
				$default_where['agent_id']  = $agent_id;
				$default_where['promotion'] = 1;
				$default_where['home_show'] = 1;
				$default_where['category_id'] = array('in',$val['category_array']);
				$default_list = $this->where($default_where)->order('goods_id DESC')->limit($limit)->select();
			}
			if($default_list){
				$goods_list = array_merge($show_list,$default_list);
			}else{
				$goods_list = $show_list;
			}
			//获取产品的售卖价格
			$goods_list = getGoodsListPrice($goods_list,$uid,'consignment','all');
			$dataList[$key]['goods_list'] = $goods_list;
		}
		return $dataList;
		
	}
	
	public function set_agent_id($agent_id){
		$this->sql['where'][] = " zm_goods.agent_id = $agent_id ";
	}

	public function set_where($key,$value){
		$this->sql['where'][] = " $key in ($value) ";
	}
    
	public function category_id($category_id){
		$info = M('goods_category')->where("category_id=$category_id")->find();
		$where = "$category_id";
		if(empty($info['pid'])){
			$info  = M('goods_category')->where("pid=$category_id")->select();
			foreach($info as $r){
				$where .= ','.$r['category_id'];
			}
		}
        $this->sql['where'][] = " zm_goods.category_id in($where) ";
        return $this;
    }
    public function goods_type($goods_type){
        $this->sql['where'][] = " zm_goods.goods_type = '$goods_type'";
        return $this;
    }
    public function seachr($seachr){
        $this->sql['where'][] = "( zm_goods.goods_name like '%$seachr%' or  zm_goods.content like '%$seachr%' or  zm_goods.goods_sn like '%$seachr%')";
        return $this;
    }
    public function get_count(){
        $productM = M('goods');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1 = 1';
        }
		if($this -> sql['join']){
            foreach($this -> sql['join'] as $row){
                $productM -> join($row);
            }
        }
        $this->count = $productM->where($where)->count();
        return $this->count;
    }
    public function _join($join_str){
        $this->sql['join'][] = $join_str;
        return $this;
    }
    public function _limit($page_id=1,$page_size=20){
        if(empty($this->count)){
            $this->get_count();
        }
		$this->sql['limit'] = (($page_id-1)*$page_size).','.$page_size;
        return $this;
    }
    public function _order($order,$desc){
        $this->sql['order'] = " zm_goods.$order $desc";
        return $this;
    }
    public function getList(){
        $productM = M('goods');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = ' 1=1 ';
        }
        if($this -> sql['join']){
            foreach($this -> sql['join'] as $row){
                $productM -> join($row);
            }
        }
        if(empty($this->sql['limit'])){
            $this -> _limit();
        }
        if(empty($this->sql['order'])){
            $this -> _order('goods_id','ASC');
        }
        $data = $productM -> where($where) -> limit($this->sql['limit']) -> order($this->sql['order']) -> field('zm_goods.*') -> select();
        return $data;
    }
	public function get_info($goods_id=0,$agent_id=0){
		return $this->where(" goods_id = $goods_id and agent_id = $agent_id ")->find();
	}
	public function get_goods_images($goods_id=0){
		$my_m = M('goods_images');
		return $my_m->where(" goods_id = $goods_id ")->select();
	}
}
?>
