<?php
/**
 * 散货获取类，根据级别自动加点，
 * User: 王松林
 * Date: 2016/8/2 0023
 * Time: 17:26
 */
namespace Common\Model;
use Think\Model;
Class GoodsSanhuoModel extends \Think\Model{

    public $agent_id;
    public $point1;//一级点数
    public $point2;//二级点数

    public function __construct() {
        $this -> agent_id = C('agent_id');
        parent::__construct();
    }

    //判断是否开启裸钻同步功能，这里要判断上级是否开启，上级如果没开启，下级的开启功能就失效
    private function isSyncSanhuo( $agent_id=0 ){
        if(empty($agent_id)){
            $agent_id  = $this -> agent_id;
        }
        $info1     = M('config_value') -> where("agent_id = $agent_id and config_key = 'is_sync_sanhuo'") -> find();
        return $info1['config_value']?'1':'0';
    }

    public function get_where_agent(){
        $agent_id  = $this -> agent_id;
        if( $this -> isSyncSanhuo() ){
            $parent_id = get_parent_id();
            if($parent_id){
                $string     = $agent_id.','.$parent_id;
                if( $this   -> isSyncSanhuo($parent_id) ){
                    $string .= $string.',0';
                }
            }else{
                $string = $agent_id.',0';
            }
            return $string;
        } else {
            return " $agent_id ";
        }
    }

    public function calculationPoint( $goods_id ){
        $point1 = 0;
        $point2 = 0;
		$agent_id      = $this -> where(" goods_id = $goods_id ") -> getField('agent_id');
		if( $agent_id != $this -> agent_id ){//是不是自己的货
			if( empty($agent_id) ){
				//是钻明的货
                $agent_id = $this -> agent_id;
                $T        = M('trader');
                $point2   = $T -> where(' t_agent_id = '.$agent_id)->getField('sanhuo_advantage');
                $parent_id = get_parent_id();
                if ( $parent_id ) {
                    $point1 = $T -> where( ' t_agent_id = ' . $parent_id )->getField('sanhuo_advantage');
                }
			} else {
				//是上级的货
        		$point2       = $trader = M('trader') -> where( ' t_agent_id = ' . $this -> agent_id ) -> getField('sanhuo_advantage');
			}
		}
        $this->point1 = intval($point1);
        $this->point2 = intval($point2);
		return true;
	}

    public function getCount( $where = array() , $agent_id = 0 ){
        if( empty($agent_id) ){
            $agent_id = $this -> get_where_agent();
            $where    = array_merge($where,array('agent_id'=>array('in',$agent_id)));
        }
        $totalnum     = $this -> where($where) -> count();
        return $totalnum;
    }

	/**
	 * auth	：fangkai
	 * content：获取白钻或彩钻库存总重量
	 * time	：2016-6-29
	**/
    public function getSum($where='1=1',$sum,$custom_agent_id = false){
		if($custom_agent_id == false){
			$agent_id                       = $this -> get_where_agent();
			if( is_string($where) ){
				$where                     .= ( empty($where) ? '' : " and " )." zm_goods_sanhuo.agent_id in ($agent_id) ";
			} else if(is_array($where)){
				$where['zm_goods_sanhuo.agent_id'] = array('in',$agent_id);
			}
		}
		if(empty($sum)){
			$sum = ' `goods_weight` ';
		}
		$sum = $this -> where($where) -> sum($sum);
		return $sum;
	}

    public function getInfo($where,$agent_id = 0){

        if( empty($agent_id) ){
            $agent_id = $this->get_where_agent();
            $where    = array_merge($where,array('zm_goods_sanhuo.agent_id'=>array('in',$agent_id)));
        }
        $info         = $this -> join('join zm_goods_sanhuo_type on zm_goods_sanhuo_type.tid = zm_goods_sanhuo.tid ') -> where($where) -> field('zm_goods_sanhuo.*,zm_goods_sanhuo_type.type_name,zm_goods_sanhuo_type.type_name_en') -> find();
        if( $info ){
            $this        -> calculationPoint($info['goods_id'] );
            $info['goods_price']      = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100 * $info['goods_price']);
            if( $info['goods_price2'] ){
                $info['goods_price2'] = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100 * $info['goods_price2']);
            }
        }
        return $info;
    }

    public function getOne($field,$where,$agent_id = 0){

        $info = $this -> getInfo($where,$agent_id);
        if( $info && $info[$field] ){
            $r = $info[$field];
        }else{
            $r = '';
        }
        return $r;
    }

    public function getList($where = array(),$sort='goods_id asc',$pageid=1,$pagesize=50,$agent_id=0){

        if( empty($agent_id) ){
            $agent_id = $this -> get_where_agent();
            $where    = array_merge($where,array('zm_goods_sanhuo.agent_id'=>array('in',$agent_id)));
        }
        $totalnum     = $this -> where($where) -> count();
        $limit        = (($pageid-1)*$pagesize).','.$pagesize;
        if( $pagesize ){ //分页数大于0才分页，等于0不分页，查全部
            $this -> limit($limit);
        }
        $list         = $this -> join('join zm_goods_sanhuo_type on zm_goods_sanhuo_type.tid = zm_goods_sanhuo.tid ') -> where($where) -> order($sort) ->
         field('zm_goods_sanhuo.*,zm_goods_sanhuo_type.type_name,zm_goods_sanhuo_type.type_name_en,"" as color_cc,"" as clarity_cc,"" as cut_cc,"" as weights_cc') -> select();

        $HM = M('collection');
        $collection_where = array(
            'uid'=>$_SESSION['web']['uid'],
            'agent_id'=>C('agent_id'),
            'goods_type'=>2
        );
        foreach ($list as $key => &$info) {
            $this->calculationPoint($info['goods_id'] );
            $info['goods_price']       = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100 * $info['goods_price']);
            if( $info['goods_price2']  ){
                $info['goods_price2']  = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100 * $info['goods_price2']);
            }
            $collection_where['goods_id'] = $info['goods_id'];
            $collection_info = $HM->field('id')->where($collection_where)->find();
            if($collection_info){
                $info['collection_id'] = $collection_info['id'];
            }
        }

        $data['total']     = $totalnum;
        $data['page_size'] = $pagesize;
        $data['page_id']   = $pageid;
        $data['list']      = $list;
        return $data;
    }

    public function getListByGoodsSn($goodsSn, $agent_id=0)
    {
        $where['goods_sn'] = array('like', $goodsSn.'%');

        if( empty($agent_id) ){
            $agent_id = $this -> get_where_agent();
            $where    = array_merge($where, array('zm_goods_sanhuo.agent_id'=>array('in', $agent_id)));
        }

        $sort = 'zm_goods_sanhuo.goods_sn asc';

        $list         = $this -> distinct(true)->join('join zm_goods_sanhuo_type on zm_goods_sanhuo_type.tid = zm_goods_sanhuo.tid ') -> where($where) -> order($sort) ->
        field('zm_goods_sanhuo.goods_id, zm_goods_sanhuo.goods_sn') -> select();

        return $list;
    }
}
