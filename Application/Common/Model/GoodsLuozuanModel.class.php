<?php
/**
 * 裸钻获取类，根据级别自动加点，
 * User: 王松林
 * Date: 2016/6/23 0023
 * Time: 17:26
 */
namespace Common\Model;
use Think\Model;
Class GoodsLuozuanModel extends \Think\Model{

    private $agent_id          = 0;
    private $set_current_field = false;
    private $point             = 0;//总的加点（包含钻明，或是钻明+一级）
    private $point_up          = 0;//上级加点（是钻明，或者一级的加点）

    //0为裸钻，1为彩钻
    public function __construct($luozuan_type='0') {
        parent::__construct();
        $this -> agent_id = C('agent_id');
        $this -> setLuoZuanPoint('0',$luozuan_type);
    }

    //返回当前客户的点数，is_parent>0的时候，只返回上级点数
    public function setLuoZuanPoint( $is_parent = '0' , $type = '0' ){
        $agent_id = $this -> agent_id;
        $point    = '0';
        if( $type == '1' ){
            $type_string = "caizuan_advantage";
        }else{
			$type_string = 'luozuan_advantage';
		}
        $T        = M('trader');
        $trader   = $T -> where(' t_agent_id = '.$agent_id)->find();
        if($trader) {
            $point = $trader[$type_string];
            $this -> point_up = $point;
            if( $is_parent ) {
                $point = '0';
            }
            $parent_id = get_parent_id();
            if ( $parent_id ) {
                $trader = $T -> where( ' t_agent_id = ' . $parent_id )->find();
                if ( $trader ) {
                    $point += intval($trader[$type_string]);
                }
            }
        }
		if(empty($point)){
			$point = '0';
		}
        $this -> point  = $point;
        return $point;
    }

    //判断是否开启裸钻同步功能，这里要判断上级是否开启，上级如果没开启，下级的开启功能就失效
    private function isSyncLuozuan($agent_id = 0){
        if(empty($agent_id)){
            $agent_id  = $this -> agent_id;
        }
        $info1 = M('config_value') -> where("agent_id = $agent_id and config_key = 'is_sync_luozuan'") -> find();
        return $info1['config_value']?'1':'0';
    }

    public function get_where_agent(){
        $agent_id  = $this -> agent_id;
        if( $this -> isSyncLuozuan() ){
            $parent_id = get_parent_id();
            if($parent_id){
				$string   = $agent_id.','.$parent_id;
               if( $this -> isSyncLuozuan($parent_id) ){
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
	
	//斯特曼获取分销商ID
	public function get_where_agent_new($is_own = true){		//新版b2c裸钻模板查询自己的货(国内货或比利时货)（$is_own = true agent_id只为自己的agent_id）和不是自己的货($is_own = false 上级的agent_id 和 钻明的 agent_id)
        $agent_id  = $this -> agent_id;
		if($is_own == false){
			if( $this -> isSyncLuozuan()){
				$parent_id = get_parent_id();
				if($parent_id){
					$string   = $parent_id;
				   if( $this -> isSyncLuozuan($parent_id) ){
						$string .= $string.',0';
					}
				}else{
					$string = '0';
				}
				return $string;
			} else {
				
				return " -1 ";
			}
		}else{
			return " $agent_id ";
		}
        
    }
	
	public function getcount($where='1=1',$custom_agent_id = false){
		
		if($custom_agent_id == false){
			$agent_id                       = $this -> get_where_agent();
			if( is_string($where) ){
				$where                     .= ( empty($where) ? '' : " and " )." agent_id in ($agent_id) ";
			} else {
				$where['zm_goods_luozuan.agent_id'] = array('in',$agent_id);
			}
		}
		
		$count = $this -> where($where) -> count();
		
		return $count;
	}
	
	//斯特曼获取裸钻总数
	public function get_count($where='1=1',$is_own = true){
		$agent_id                       = $this -> get_where_agent_new($is_own);
		
		if( is_string($where) ){
			$where                     .= ( empty($where) ? '' : " and " )." agent_id in ($agent_id) ";
		} else {
			$where['zm_goods_luozuan.agent_id'] = array('in',$agent_id);
		}
		$cache_where	= json_encode($where);
		$cache_key		= MD5($cache_where);
		$count	= S($cache_key);
		if(!$count && $count !=='0'){
			$count = $this -> where($where) -> count();
			if(empty($count)){
				$count = '0';
			}
			S($cache_key,$count,7200);
		}

		return $count;
	}
	
	public function where_native($where){
		parent::where($where);
		return $this;
		
	}
	
	
    public function get_fields( $field = '*' ){
        if( $this -> isSyncLuozuan() ){
            if( empty($field) || $field == '*' ){
                $field = true; //全部字段
            }
            $point     = $this -> point;
            $point_up  = $this -> point_up;
            $agent_id  = $this -> agent_id;
            if( $field === true ){
                $field = $this -> getDbFields();
                foreach( $field as $key => $row ){
                    if($row == 'dia_discount'){
                        unset($field[$key]);
                    }
                }
                if( is_string($field) ){
                    $field .= ",(case when agent_id = 0 then dia_discount + $point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end as dia_discount)";
                } else if( is_array($field) ){
                    $field["case when agent_id = 0 then dia_discount + $point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end"] = 'dia_discount';
                    $str    = '';
                    foreach($field as $key => $row){
                        if(is_numeric($key)){
                            if( $str == '' ){
                                $str  = $row;
                            } else {
                                $str .= ','.$row;
                            }
                        }else{
                            if($str  == ''){
                                $str  = $key .' as '.$row;
                            } else {
                                $str .= ','.$key .' as '.$row;
                            }
                        }
                    }
                    $field = $str;
                }
            }else{
                if( is_string($field) ){
                    if( strpos($field,'`dia_discount`') !== false ){
                        $field = str_replace('`dia_discount`',"case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end as dia_discount",$field);
                    }else{
                        if( strpos($field,'dia_discount') !== false ){
                            $field = str_replace('dia_discount',"case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end as dia_discount",$field);
                        }
                    }
                }else if( is_array($field) ) {
                    foreach( $field as $key => $row ){
                        if($row == 'dia_discount'){
                            unset($field[$key]);
                            $field["case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end"] = 'dia_discount';
                        }
                    }
                    $str = '';
                    foreach($field as $key => $row){
                        if( is_numeric( $key ) ){
                            if( $str == '' ){
                                $str  = $row;
                            } else {
                                $str .= ','.$row;
                            }
                        }else{
                            if($str == ''){
                                $str  = $key .' as '.$row;
                            } else {
                                $str .= ','.$key .' as '.$row;
                            }
                        }
                    }
                    $field = $str;
                }
            }
        }
        return $field;
    }

    public function where($where,$parse=null){
        $agent_id       = $this->agent_id;
        if( is_string($where) ){
            if( $this  -> isSyncLuozuan() ){
                $agent_id1 = $this->get_where_agent();
                $where .= " and agent_id in ($agent_id1) ";
            }else{
                $where .= " and agent_id = $agent_id ";
            }
        }else if( is_array($where) ) {
            if( $this -> isSyncLuozuan() ){
                $agent_id1 = $this->get_where_agent();
                $where['agent_id'] = array('in',"$agent_id1");
            }else{
                $where['agent_id'] = array('eq',"$agent_id");
            }
        }
        parent::where($where,$parse);
        return $this;
    }
	
	public function get_where($where){
		if( is_string($where) ){
            if( $this  -> isSyncLuozuan() ){
                $agent_id1 = $this->get_where_agent();
                $where .= " and agent_id in ($agent_id1) ";
            }else{
                $where .= " and agent_id = $agent_id ";
            }
        }else if( is_array($where) ) {
            if( $this -> isSyncLuozuan() ){
                $agent_id1 = $this->get_where_agent();
                $where['agent_id'] = array('in',"$agent_id1");
            }else{
                $where['agent_id'] = array('eq',"$agent_id");
            }
        }
        return $where;
    }
	
	//斯特曼组装where条件
	public function get_where_new($where,$is_own = true){
		if( is_string($where) ){
			if($is_own == false){
				if( $this  -> isSyncLuozuan() ){
					$agent_id1 = $this->get_where_agent_new($is_own);
					$where .= " and zm_goods_luozuan.agent_id in ($agent_id1) ";
				}else{
					$where .= " and zm_goods_luozuan.agent_id = -1 ";
				}
			}else{
				$where .= " and zm_goods_luozuan.agent_id = $this->agent_id ";
			}
        }else if( is_array($where) ) {
			if($is_own == false){
				if( $this -> isSyncLuozuan() ){
					$agent_id1 = $this->get_where_agent_new($is_own);
					$where['zm_goods_luozuan.agent_id'] = array('in',"$agent_id1");
				}else{
					$where['zm_goods_luozuan.agent_id'] = array('eq'," -1 ");
				}
			}else{
				$where['zm_goods_luozuan.agent_id'] = array('eq',"$this->agent_id");
			}
        }
		return $where;
    }

    public function field($field='*',$except=false){
        if( empty($field) || $field == '*' ){
            $field  = true; //全部字段 
        }
        if( $this -> isSyncLuozuan() ){
            $point     = $this -> point;
            $point_up  = $this -> point_up;
            $agent_id  = $this -> agent_id;
            if( $field === true ){
                $field = $this -> getDbFields();
                foreach( $field as $key => $row ){
                    if($row == 'dia_discount'){
                        unset($field[$key]);
                    }
                }
                if( is_string($field) ){
                    $field .= ",(case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end as dia_discount)";
                } else if( is_array($field) ){
                    $field["case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end"] = 'dia_discount';
                }
            } else {
                if( is_string($field) ){
                    if( strpos($field,'`dia_discount`') !== false ){
                        $field = str_replace('`dia_discount`',"case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end as dia_discount",$field);
                    }else{
                        if( strpos($field,'dia_discount') !== false ){
                            $field = str_replace('dia_discount',"case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end as dia_discount",$field);
                        }
                    }
                }else if( is_array($field) ) {
                    foreach( $field as $key => $row ){
                        if($row == 'dia_discount'){
                            unset($field[$key]);
                            $field["case when agent_id = 0 then dia_discount+$point when agent_id > 0 and agent_id <> '$agent_id' then dia_discount + $point_up else dia_discount end"] = 'dia_discount';
                        }
                    }
                }
            }
        }
        $this -> set_current_field = true;
        parent::field($field,$except);
    }

    public function select($options=array()){
        if( $this -> set_current_field === false ){
            $this -> field();
        }
        $this -> set_current_field = false;
        return parent::select($options);
    }

    public function find($options=array()){
        if( $this -> set_current_field === false ){
            $this -> field();
        }
        $this -> set_current_field = false;
        return parent::find($options);
    }

    public function getLuozuanList($where,$sort='gid desc',$pageid=1,$pagesize=50,$agent_id=0)
    {
        if($agent_id){
            $where = array_merge($where,array('agent_id'=>array('eq',$agent_id)));
        }
		$cache_where	= json_encode($this->get_where($where));
		$cache_key		= MD5($cache_where);
		$totalnum	= S($cache_key);
		if(!$totalnum && $totalnum!=='0'){
			$totalnum          = $this -> where($where) -> count();
			if(empty($totalnum)){
				$totalnum = '0';
			}
			S($cache_key,$totalnum,7200);
		}
        
		$limit             = (($pageid-1)*$pagesize).','.$pagesize;
        $list              = $this -> where($where) -> order($sort)->limit($limit)->select();
        if($this->yn_certificate_num()==1){
        //85是兰柏的，87是兰柏的二级，有些特别，不显示证书号  
            foreach($list as $key=>$value){
                $list[$key]['certificate_number'] = '';
            }
        }
        
        $data['total']     = $totalnum;
        $data['page_size'] = $pagesize;
        $data['page_id']   = $pageid;
        $data['list']      = $list;
        return $data;
    }
	
	public function assemble_where($where){
		$new_where	= array();
		foreach($where as $key=>$val){
			if($key != '_complex' ){
				if(strstr($key,'`dia_global_price`') != false && strstr($key,'`weight`') != false){
					$key = str_replace('`dia_global_price`','`zm_goods_luozuan`.`dia_global_price`',$key);
					$key = str_replace('`weight`','`zm_goods_luozuan`.`weight`',$key);
					$key = str_replace('`dia_discount`','`zm_goods_luozuan`.`dia_discount`',$key);
					$new_where[$key]	= $val;
				}else{
					$key	= 'zm_goods_luozuan.'.$key;
					$new_where[$key]	= $val;
				}
			}else{
				$new_where['_complex']	= $val;
			}
			
		}
		
		return $new_where;
	}
	
	//斯特曼获取裸钻列表
	public function getLuozuanListNew($where,$sort='gid desc',$pageid=1,$pagesize=50,$is_own=true,$preferential=0,$uid)
    {
		if(empty($uid)){
			$uid	= 0;
		}
		
		if($where){
			$where = $this->assemble_where($where);
		}
		
		$where = $this->get_where_new($where,$is_own);

		$cache_where	= json_encode($where).$preferential;
		$cache_key		= MD5($cache_where);
		$totalnum	= S($cache_key);
		if(!$totalnum && $totalnum !=='0'){
			if($preferential == 1){
				$join = ' right join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid AND zm_goods_preferential.agent_id = '.$this -> agent_id;
			}
			$totalnum          = $this -> join($join) -> where_native($where) -> count();

			if(empty($totalnum)){
				$totalnum = '0';
			}
			S($cache_key,$totalnum,7200);
		}

		$limit	= (($pageid-1)*$pagesize).','.$pagesize;
		if($preferential == 1){
			$where['zm_goods_preferential.agent_id']	= $this -> agent_id;
			$list   = M('goods_luozuan') -> field(' zm_goods_luozuan.*,zm_goods_preferential.id as preferential_id ,zm_goods_preferential.discount as pre_discount,zm_goods_compare.id as compare_id ')->join(' left join zm_goods_compare on zm_goods_luozuan.gid = zm_goods_compare.gid AND zm_goods_compare.uid = '.$uid.'   right join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid AND zm_goods_preferential.agent_id = '.$this -> agent_id )->where($where)->order($sort)->limit($limit)->select();
		}else{
			$list   = M('goods_luozuan') -> field(' zm_goods_luozuan.*,zm_goods_preferential.id as preferential_id ,zm_goods_preferential.discount as pre_discount,zm_goods_compare.id as compare_id ')-> where($where)->join('  left join zm_goods_compare on zm_goods_luozuan.gid = zm_goods_compare.gid AND zm_goods_compare.uid = '.$uid.'  left join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid AND zm_goods_preferential.agent_id = '.$this -> agent_id )->order($sort)->limit($limit)->select();
		}

        if($this->yn_certificate_num()==1){
        //85是兰柏的，87是兰柏的二级，有些特别，不显示证书号  
            foreach($list as $key=>$value){
                $list[$key]['certificate_number'] = '';
            }
        }
        
        $data['total']     = $totalnum;
        $data['page_size'] = $pagesize;
        $data['page_id']   = $pageid;
        $data['list']      = $list;
        return $data;
    }
	
	//查出特惠钻石ID
	public function getPreferentialID($agent_id = 0)
    {
		$preferential_luozuan_id	= M('goods_preferential')->field('gid')->where(array('agent_id'=>$agent_id))->select();
		
		return $preferential_luozuan_id;
	}
	
    /**
     * 后台开启关闭证书号
     * zhy	find404@foxmail.com
     * 2016年12月21日 11:40:21
     */
    public function yn_certificate_num(){
		$config_value = M('config_value') -> where('agent_id = '.C('agent_id')." and config_key = 'show_hide_gia_num'") -> getField('config_value');
		$config_value = isset($config_value) ? $config_value :'0';
		cookie('yn_certificate_num_cv',$config_value,3600); 
		return $config_value;
	}

     //批量删除没有了的裸钻数据
    //luozuan_type 要删除的裸钻类型，-1 包含彩钻白钻
    public function delLuozuanBatchData($type, $biaoji_time, $luozuan_type = -1, $agent_id = 0){
        if(empty($type)){
            return false;
        }
        $where['goods_name']           = array('like', $type.'%');
        $where['biaoji_time']          = array('neq',  $biaoji_time);
        if($agent_id == 0){
            $agent_id = C('agent_id');
        }
        $where['agent_id']             = array('eq',  $agent_id);
        if($luozuan_type == 1){
            $where['luozuan_type']          = array('eq',  1);  //彩钻
        }else if($luozuan_type == 0){
            $where['luozuan_type']          = array('eq',  0);  //白钻
        }
        $this->where($where)->delete();        
    }	
	
}