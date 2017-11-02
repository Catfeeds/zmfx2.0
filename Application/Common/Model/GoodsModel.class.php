<?php
/**
 * 产品模型类
 */
namespace Common\Model;
use Think\Model;
use Think\Page;
class GoodsModel extends Model{
	
	public $agent_id;
    public $point;
	public $count;
	public $page;

	public $point1;
	public $point2;

    public $sql = array(
        'where' => null,
        'order' => '',
        'limit' => '',
        'join'  => array(),
    );

	//默认读取视图，可以省去join代码，不过在后台需要在外部设置成goods
	public $my_table_name = "vm_shop_goods";

    public function __construct() {
        $this -> agent_id = C('agent_id');
        $this -> point    = $this -> getGoodsPoint();
        parent::__construct();
    }

    //判断是否开启裸钻同步功能，这里要判断上级是否开启，上级如果没开启，下级的开启功能就失效
    public function isSyncGoods( $agent_id=0 ){

        if(empty($agent_id)){
            $agent_id  = $this -> agent_id;
        }
        $info1     = M('config_value') -> where("agent_id = $agent_id and config_key = 'is_sync_goods'") -> find();
        return $info1['config_value']?'1':'0';
    }

    public function get_where_agent(){
        $agent_id  = $this -> agent_id;
        if( $this -> isSyncGoods() ){
            $parent_id = get_parent_id();
            if($parent_id){
                $string     = $agent_id.','.$parent_id;
                if( $this   -> isSyncGoods($parent_id) ){
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
	
	/**
	 * auth	：fangkai
	 * content：获取成品/定制产品总数
	 * time	：2016-6-29
	**/
    public function getcount($where='1=1',$custom_agent_id = false){
		if($custom_agent_id == false){
			$agent_id                       = $this -> get_where_agent();
			if( is_string($where) ){
				$where                     .= ( empty($where) ? '' : " and " )." zm_goods.agent_id in ($agent_id) ";
			} else if(is_array($where)){
				$where['zm_goods.agent_id'] = array('in',$agent_id);
			}
		}
		$count = $this -> where($where) -> count();
		return $count;
	}
	
	/**
	 * auth	：fangkai
	 * content：获取成品/定制产品库存总数
	 * time	：2016-9-8
	**/
    public function getSum($where='1=1',$sum,$custom_agent_id = false){
		if($custom_agent_id == false){
			$agent_id                       = $this -> get_where_agent();
			if( is_string($where) ){
				$where                     .= ( empty($where) ? '' : " and " )." zm_goods.agent_id in ($agent_id) ";
			} else if(is_array($where)){
				$where['zm_goods.agent_id'] = array('in',$agent_id);
			}
		}
		if(empty($sum)){
			$sum = ' `goods_number` ';
		}
		$sum = $this -> where($where) -> sum($sum);
		return $sum;
	}
	
	/**
	 * auth	：fangkai
	 * content：关联查询获取产品列表
	 * time	：2016-8-24
	**/
	public function getCustomGoodsList($where='1=1',$field='g.*',$join,$sort='g.goods_id DESC',$pageid=1,$pagesize=20){
		if(empty($field)){
			$field = 'g.*';
		}
		$limit = ($pageid-1)*$pagesize.','.$pagesize;
		$customGoodsList =  M($this->my_table_name)
							->alias('g')
							->field($field)
							->where($where)
							->join($join)
							->order($sort)
							->limit($limit)
							->select();
		if($customGoodsList){
			foreach ($customGoodsList as $key => &$info) {
				$this -> calculationPoint($info['goods_id']);
				if( $info['goods_type'] == '4' ){
					//定制
					list($material, $associateInfo, $luozuan, $deputystone) = $this -> getJingGongShiData($info['goods_id'], 0, 0, 0);
					if( empty($info['price_model']) ){
						$info['goods_price']                                = $this -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
					}
					if( $info['price_model'] == '1' ){
						$category_name = M('goods_category') -> where(" category_id = '$info[category_id]' ") -> getField('category_name');
						$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
						if( $is_size ){
							$info['size_info']    = $this -> getGoodsAssociateSizeAfterAddPoint( " goods_id = $info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
							$info['goods_price']  = $info['size_info']['size_price'];
							$is_dui               = InStringByLikeSearch($category_name,array('对戒'));
							if( $is_dui ){
								$info['size_info1']  = $this -> getGoodsAssociateSizeAfterAddPoint( " sex = '1' and goods_id = $info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
								$info['size_info2']  = $this -> getGoodsAssociateSizeAfterAddPoint( " sex = '2' and goods_id = $info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
								$info['goods_price'] = $info['size_info1']['size_price'] + $info['size_info2']['size_price'];
							}
						} else {
							$info['goods_price'] = $associateInfo['fixed_price'];
						}
					}
				} else if( $info['goods_type'] == '3' ){
					//成品
					$sku                 = $this -> getGoodsSku($info['goods_id']);
					$info['goods_price'] = $sku['goods_price'];
				}
				$info = $this->completeUrl($info['goods_id'],'goods',$info);
			}
			return $customGoodsList;
		}else{
			return false;
		}
	}
	
	/**
	 * auth	：fangkai
	 * content：获取首页展示产品 dataList：二级分类列表
	 * time	：2016-7-1
	**/
    public function hotGoodsList($agent_id,$dataList,$uid,$custom_agent_id = false){
		if( $custom_agent_id == false ){
			$agent_id = $this -> get_where_agent();
		}
		if(empty($agent_id)){
			return false;
		}
		if(empty($dataList)){
			return false;
		}
		
		//用来判断商城热卖是否有产品展示，以用来在前台隐藏该楼层
		$GoodsCount        = 0;
		
		//查询出活动商品的ID,以便用来获取产品列表的时候去掉这些ID， fangkai 2016/8/22
		$GA				   = D('Common/GoodsActivity');
		$where['agent_id'] = $this->agent_id;
		$activityGoods     = $GA->GoodsActivityList($where,'',1,'');
		if($activityGoods){
			$activityGoods_id         = array_column($activityGoods,'gid');
			$id_array		          = implode(',',$activityGoods_id);
			$show_where['g.goods_id'] = array('not in',$id_array);
		}else{
			$activityGoods_id = array();
		}
		
		foreach($dataList as $key=>$val){
			//首先查询出在首页展示的普通产品
			$show_where['ghs.home_show']   = 2;
			$show_where['ghs.agent_id']    = $this->agent_id;
			$show_where['g.category_id']   = array('in',$val['category_array']);
			$show_where['g.agent_id'] 	   = array('in',$agent_id);
			$show_where['g.sell_status']   = 1;
			$show_where['g.shop_agent_id'] = $this->agent_id;
			$field				= 'g.*,ghs.setting_time';
			$join				= 'left join zm_goods_home_show as ghs on ghs.gid = g.goods_id';
			$sort				= 'ghs.setting_time DESC';
			$show_list          = $this->getCustomGoodsList($show_where,$field,$join,$sort,1,6);
			
			//计算出首页展示的普通产品的总数
			if($show_list){
				$show_count = count($show_list);
			}else{
				$show_count = 0;
				$show_list  = array();
			}
			//如果首页展示的普通产品小于5个，则另外通过普通商品补充（其中查询的时候，不包含首页展示商品，不包含活动列表商品）
			if($show_count < 5){
				$limit 		= 5-$show_count;
				//合并活动列表的商品ID 和 首页展示的商品ID，去掉这些ID，以便用来查询出普通商品首页展示
				if($show_list){
					$show_id	               = array_column($show_list,'goods_id');
					$show_id_array             = array_merge($show_id,$activityGoods_id);
					if( $show_id_array ){
						$default_where['goods_id'] = array('not in',$show_id_array);
					} else {
						unset($default_where['goods_id']);
					}
				}else{
					if( $activityGoods_id ){
						$default_where['goods_id'] = array('not in',$activityGoods_id);
					} else {
						unset($default_where['goods_id']);
					}
				}
				
				$default_list = array();
				$default_where['agent_id']      = array('in',$agent_id);
				$default_where['category_id']   = array('in',$val['category_array']);
				$default_where['sell_status']   = 1;
				$default_where['shop_agent_id'] = $this->agent_id;
				$default_list = M($this->my_table_name) -> where($default_where)->order('goods_id DESC')->limit($limit)->select();
				foreach ($default_list as $k1 => $info) {
					$this -> calculationPoint($info['goods_id']);
					if( $info['goods_type'] == '4' ){
						//定制
						list($material, $associateInfo, $luozuan, $deputystone) = $this -> getJingGongShiData($info['goods_id'], 0, 0, 0);
						if( empty($info['price_model']) ){
							$info['goods_price']                                = $this -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
						}
						if( $info['price_model'] == '1' ){
							$category_name = M('goods_category')->where(" category_id = '$info[category_id]' ")->getField('category_name');
							$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
							if( $is_size ){
								$info['size_info']   = $this -> getGoodsAssociateSizeAfterAddPoint( " goods_id = $info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
								$info['goods_price'] = $info['size_info']['size_price'];
								$is_dui              = InStringByLikeSearch($category_name,array('对戒'));
								if($is_dui){
									$info['size_info1']    = $this -> getGoodsAssociateSizeAfterAddPoint( " sex = '1' and goods_id = $info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
									$info['size_info2']    = $this -> getGoodsAssociateSizeAfterAddPoint( " sex = '2' and goods_id = $info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
									$info['goods_price']   = $info['size_info1']['size_price'] + $info['size_info2']['size_price'];
								}
							} else {
								$info['goods_price']  = $associateInfo['fixed_price'];
							}
						}
					} else if( $info['goods_type'] == '3' ){
						//成品
						$sku                 = $this -> getGoodsSku($info['goods_id']);
						$info['goods_price'] = $sku['goods_price'];
					}
					$default_list[$k1] = $this -> completeUrl($info['goods_id'],'goods',$info);
				}
				
			}
			
			//合并首页展示的普通商品和默认的普通商品，组合成每个分类五个首页展示的普通商品
			$goods_list = array();
			if($default_list){
				$goods_list = array_merge($show_list,$default_list);
				//dump($show_list);
			}else{
				$goods_list = $show_list;
			}

			//获取产品的售卖价格
			$goods_list     = getGoodsListPrice($goods_list,$uid,'consignment','all');
			if($goods_list){
				$GoodsCount += count($goods_list);
			}
			
			$dataList[$key]['goods_list'] = $goods_list;

		};
		$dataList['count']	= $GoodsCount;

		return $dataList;
	}
	
	public function set_agent_id($agent_id = 0,$custom_agent_id = false){
		if( $custom_agent_id == false ){
			$agent_id        = $this -> get_where_agent();
		}
		$this->sql['where']['zm_goods.agent_id'] = array('in',$agent_id);
	}

	public function set_where($key,$value,$is_exp=false,$is_cover=false){
		if( $is_cover ){
			$this->sql['where'] = array();
		}
		if( $is_exp == false ){
			$this->sql['where'][$key] = array('in',"$value");
		}else{
			$this->sql['where'][$key] = array('exp',"$value");
		}
	}
    
	public function category_id($category_id){
		$info     = M('goods_category') -> where("category_id=$category_id") -> find();
		$where    = " $category_id ";
		if(empty($info['pid'])){
			$info = M('goods_category') -> where("pid=$category_id")->select();
			foreach($info as $r){
				$where .= ','.$r['category_id'];
			}
		}
        $this -> sql['where']['zm_goods.category_id'] = array('in',$where);
        return $this;
    }
    public function goods_type($goods_type){
        $this->sql['where']['zm_goods.goods_type'] = $goods_type;
        return $this;
    }
    public function seachr($seachr){
        $this->sql['where'][] = "( zm_goods.goods_name like '%$seachr%' or  zm_goods.content like '%$seachr%' or  zm_goods.goods_sn like '%$seachr%')";
        return $this;
    }
    public function get_count($custom_agent_id = false){
        $productM          = M($this->my_table_name);

		if( $custom_agent_id == false ){
			$this -> set_agent_id();
		}
        if( empty($this -> sql['where']) ) {
			$this->sql['where'][] = " 1 = 1 ";
        }
		$where   = $this->sql['where']; ///implode(' and ',$this->sql['where']);
		if($this -> sql['join']){
            foreach($this -> sql['join'] as $row){
                $productM -> join($row);
            }
        }
        $this -> count = $productM -> alias('zm_goods') -> fetchsql(false)-> where($where) -> count();
        return $this -> count;
    }
    public function _join($join_str,$is_cover=false){
		if($is_cover){
			$this -> sql['join']   = array();
		}
		if($join_str){
        	$this -> sql['join'][] = $join_str;
		}
        return $this;
    }
    public function _limit($page_id=1,$page_size=20){
        if(empty($this->count)){
            $this -> get_count();
        }
		if(empty($page_id)){
			$page_id = 1;
		}
		$this->sql['limit'] = (($page_id-1)*$page_size).','.$page_size;
        return $this;
    }
    public function _order($order,$desc){
        $this->sql['order'] = " $order $desc";
        return $this;
    }
    public function getListBase( $custom_agent_id = false, $is_all = false , $field = "zm_goods.*",$field_except=false){
        $productM          = M($this->my_table_name);

		if( $custom_agent_id == false ){
			$this -> set_agent_id();
		}
        if( empty($this -> sql['where']) ) {
			$this -> sql['where'][] = " 1 = 1 ";
        }
		$where            =  $this->sql['where'];
        if($this          -> sql['join']){
            foreach($this -> sql['join'] as $row){
                $productM -> join($row);
            }
        }
        if(empty($this->sql['order'])){
            $this -> _order('zm_goods.goods_id','ASC');
        }

		if( $is_all == false ){
			if(empty($this->sql['limit'])){
				$this -> _limit();
			}
			
        	$data = $productM -> alias('zm_goods') -> where($where) -> limit($this->sql['limit']) -> order($this->sql['order']) -> field($field,$field_except) -> select();
		} else {
			$data = $productM -> alias('zm_goods') -> where($where) -> order($this->sql['order']) -> field($field,$field_except) -> select();
		}
 
 
		foreach ($data as $key => &$info) {
			$point = $this -> calculationPoint($info['goods_id']);
			if( $point ){
				$info['goods_price']	= ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100  * $info['goods_price']);
				
			}
			$info  = $this -> completeUrl($info['goods_id'],'goods',$info);
		}
		return $data;
    }

	public function getList( $custom_agent_id = false, $is_all = false , $field = "zm_goods.*",$field_except=false,$close_activity=false){
		if( $close_activity == true ){
			$list = $this->getListBase($custom_agent_id,$is_all,$field,$field_except);
		} else {
			$this	   -> _join( ' left join zm_goods_activity as ga on ga.gid = zm_goods.goods_id and ga.agent_id ='.$this->agent_id);
			if($field){
				$field .= ', ga.product_status as activity_status, ga.setting_time, ga.home_show ';
			}else{
				$field = 'zm_goods.*, ga.product_status as activity_status, ga.setting_time, ga.home_show ';
			}
			$list = $this->getListBase($custom_agent_id,$is_all,$field,$field_except);
			array_pop($this->sql['join']);
		}
		return $list;
	}

	public function get_info($goods_id=0,$agent_id=0,$where='',$custom_agent_id = false,$field='g.*'){
		if( $custom_agent_id == false ){
			if(empty($agent_id)){
            	$agent_id = $this -> get_where_agent();
			}
		}
		if($field){
			$field			  .= ', ga.product_status as activity_status, ga.setting_time, ga.home_show ';
		}else{
			$field			  = 'zm_goods.*, ga.product_status as activity_status, ga.setting_time, ga.home_show ';
		}
		$info  =    M($this->my_table_name)
					->alias('g')
					->field($field)
					->join('left join zm_goods_activity as ga on ga.gid = g.goods_id and ga.agent_id = '.$this->agent_id)
					->where(" g.goods_id = $goods_id and g.agent_id in ($agent_id) ".($where?(' and '.$where):''))
					->find();
		$point = $this -> calculationPoint($goods_id);
		if( $info && $point ){
            $info['goods_price'] = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100  * $info['goods_price']);
        }
		$info = $this -> completeUrl($goods_id,'goods',$info);
		return $info;
	}

	/**
	 * auth	：fangkai	
	 * content：获取成品/定制产品列表
	 * time	：2016-6-29
	**/
    public function getGoodsList($where='1=1',$sort= 'goods_id DESC',$pageid=1,$pagesize=20,$custom_agent_id = false ) {
		if( $custom_agent_id == false ){
			$agent_id              = $this -> get_where_agent();
			if( is_string($where) ){
				$where            .= ( empty($where) ? '' : " and " )." zm_goods.agent_id in ($agent_id) ";
			} else if(is_array($where)){
				$where['agent_id'] = array('in',$agent_id);
			}
		}		
		$limit      = ( ($pageid - 1) * $pagesize ).','.$pagesize;
		$goods_list = M($this->my_table_name) -> alias('zm_goods') -> where($where) -> order($sort) -> limit($limit) -> select();
		if( $goods_list ){
			foreach ($goods_list as $key => &$info) {
				$point = $this -> calculationPoint($info['goods_id']);
				if( $point ){
					$info['goods_price'] = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100  *  $info['goods_price']);
				}
				$info  = $this -> completeUrl($info['goods_id'],'goods',$info);
			}
			return $goods_list;
		} else {
			return false;
		}
    }

    //返回当前客户的点数，is_parent > 0的时候，只返回上级点数
    public function getGoodsPoint( $is_parent = '0' ){
        $agent_id = $this -> agent_id;
        $point    = '0';
        $T        = M('trader');
        $trader   = $T -> where(' t_agent_id = '.$agent_id) -> find();
        if($trader) {
            if( !$is_parent ) {
                $point = $trader['consignment_advantage'];
            }else{
                $point = '0';
            }
            $parent_id = get_parent_id();
            if ( $parent_id ) {
                $trader = $T -> where( ' t_agent_id = ' . $parent_id )->find();
                if ( $trader ) {
                    $point += intval($trader['consignment_advantage']);
                }
            }
        }
        return $point;
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
                $point2   = $T -> where(' t_agent_id = '.$agent_id)->getField('consignment_advantage');
                $parent_id = get_parent_id();
                if ( $parent_id ) {
                    $point1 = $T -> where( ' t_agent_id = ' . $parent_id )->getField('consignment_advantage');
                }
			} else {
				//是上级的货
        		$point2       = $trader = M('trader') -> where(' t_agent_id = ' . $this -> agent_id) -> getField('consignment_advantage');
			}
		}
        $this->point1 = intval($point1);
        $this->point2 = intval($point2);
		return true;
	}
	//这是一个获取加点数据方法，用来方便的处理加点
	public function getDataAfterAddPoint($table_name,$where,$add_point_fields,$orderby='',$join='',$format_type='list'){
		$str_array = explode(',',$add_point_fields);
		$fields    = "*";
		switch($table_name){
			case 'goods_associate_info':
				$fields		= ' zm_goods_associate_info.*,zm_goods_material.material_name,zm_goods_material.gold_price';
			break;
			case 'goods_associate_luozuan':
				$fields		= ' zm_goods_associate_luozuan.*,zm_goods_luozuan_shape.shape,zm_goods_luozuan_shape.shape_name';
			break;
			case 'goods_sku':
				$join 		= ' left join zm_goods_activity as ga on ga.gid = zm_goods_sku.goods_id and ga.agent_id = '.$this->agent_id;
				$fields		= ' zm_goods_sku.*, ga.product_status as activity_status, ga.setting_time, ga.home_show ';
			break;
		}
		switch ($format_type){
			case 'list': 
				$obj     = M($table_name);
				if($orderby){
					$obj = $obj->order($orderby);
				}
				if($join){
					$obj = $obj->join($join);
				}
				$data    = $obj -> where($where) -> field($fields) -> select();
				
				foreach($data as &$row){
					$banfang_goods_id = $this -> where("goods_id = '".$row['goods_id']."'" ) -> getField('banfang_goods_id');
						if(!$banfang_goods_id){
							foreach( $str_array as $field ){
								if( !empty($field) ){
									$row[$field] = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100  * $row[$field]);
								}
							}
						}
				}
				break;
			case 'info':
				$obj     = M($table_name);
				if($orderby){
					$obj = $obj->order($orderby);
				}
				if($join){
					$obj = $obj->join($join);
				}
				$data = $obj -> where($where) -> field($fields) -> find();
				foreach( $str_array as $field ){
					$banfang_goods_id = $this -> where("goods_id = '".$row['goods_id']."'" ) -> getField('banfang_goods_id');
						if(!$banfang_goods_id){
							if( !empty($field) ){
								$data[$field] = ( 100 + $this -> point2 ) / 100 * (( 100 + $this -> point1 ) / 100 * $data[$field]);
							}
						}
				}
				break;
		}
		return $data;
	}

	//金价
	public function getGoodsMaterialAfterAddPoint($where,$format_type='info',$orderby='material_id asc'){
		return $this -> getDataAfterAddPoint('goods_material',$where,'gold_price',$orderby,'',$format_type);
	}
	//材质属性
	public function getGoodsAssociateInfoAfterAddPoint($where,$format_type='info',$orderby='goods_info_id asc'){
		$join  = " join zm_goods_material ON zm_goods_material.material_id = zm_goods_associate_info.material_id ";
		return $this -> getDataAfterAddPoint('goods_associate_info',$where,'basic_cost,fixed_price',$orderby,$join,$format_type);
	}
	//主石属性
	public function getGoodsAssociateLuozuanAfterAddPoint($where,$format_type='info',$orderby='gal_id asc'){
		$join  = " join zm_goods_luozuan_shape ON zm_goods_luozuan_shape.shape_id = zm_goods_associate_luozuan.shape_id"; 
		return $this -> getDataAfterAddPoint('goods_associate_luozuan',$where,'price',$orderby,$join,$format_type);
	}
	//返回经过加点的尺寸属性
	public function getGoodsAssociateSizeAfterAddPoint($where,$format_type='info',$orderby='size_price asc'){
		return $this -> getDataAfterAddPoint('goods_associate_size',$where,'size_price','zm_goods_associate_size.size_price asc','',$format_type);
	}
	//副石属性
	public function getGoodsAssociateDeputystoneAfterAddPoint($where,$format_type='info',$orderby='gad_id asc'){
		return $this -> getDataAfterAddPoint('goods_associate_deputystone',$where,'deputystone_price',$orderby,$join,$format_type);
	}
	//获取默认产品属性id
	public function getJingGongShiDefaultId($gid,$materialId=0,$luozuanId=0,$deputystoneId=0){
		if( $materialId == 0 && $luozuanId == 0 ){
			$associate_info        = $this -> getGoodsAssociateInfoAfterAddPoint("goods_id = $gid");
			$materialId            = $associate_info['material_id'];
			if($materialId){
				$associate_luozuan = $this -> getGoodsAssociateLuozuanAfterAddPoint("goods_id = $gid and zm_goods_associate_luozuan.material_id = '".$materialId."'");
				$luozuanId         = $associate_luozuan['gal_id'];
			}else{
				$luozuanId         = 0;
			}
		} else if ( $materialId == 0 || $luozuanId == 0 ){
			if( $luozuanId > 0 ){
				$associate_luozuan = $this -> getGoodsAssociateLuozuanAfterAddPoint("goods_id = $gid and zm_goods_associate_luozuan.gal_id = $luozuanId ");
				$materialId        = $associate_luozuan['material_id'];
			}
			if( $materialId > 0 ){
				$associate_luozuan = $this -> getGoodsAssociateLuozuanAfterAddPoint("goods_id = $gid and zm_goods_associate_luozuan.material_id = '".$materialId."'");
				$luozuanId         = $associate_luozuan['gal_id'];
			}
		}
		if( $deputystoneId > 0 ){
			$deputystone           = $this -> getGoodsAssociateDeputystoneAfterAddPoint("goods_id = $gid and gad_id = '".$deputystoneId."'");
		}else{
			$deputystone           = $this -> getGoodsAssociateDeputystoneAfterAddPoint("goods_id = $gid");
		}
		if($deputystone){
			$deputystoneId         = $deputystone['gad_id'];
		}else{
			$deputystoneId         = 0;
		}
		if(empty($materialId)){$materialId='0';}
		if(empty($luozuanId)){$luozuanId='0';}
		if(empty($deputystoneId)){$deputystoneId='0';}
		return array($materialId,$luozuanId,$deputystoneId);
	}

	//金工石各项数据
	public function getJingGongShiData($gid,$materialId=0,$luozuanId=0,$deputystoneId=0){
	    $this -> calculationPoint($gid);//计算点数
		list( $materialId , $luozuanId , $deputystoneId ) = $this -> getJingGongShiDefaultId( $gid , $materialId , $luozuanId , $deputystoneId );
		$material      = $this -> getGoodsMaterialAfterAddPoint(" material_id = '$materialId' ");
		$associateInfo = $this -> getGoodsAssociateInfoAfterAddPoint(" goods_id= '$gid' AND `zm_goods_associate_info`.material_id = '$materialId' ");
		$luozuan       = $this -> getGoodsAssociateLuozuanAfterAddPoint(" gal_id = '$luozuanId' ");
		$deputystone   = $this -> getGoodsAssociateDeputystoneAfterAddPoint(" gad_id = '$deputystoneId' ");
 
		return array($material,$associateInfo,$luozuan,$deputystone);
	}
	//计算定制的价格
	public function getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone){
		//给定默认值防止报错。
		if(empty($material)){$material=array('gold_price'=>0);}// 金价
		if(empty($associateInfo)){$associateInfo=array('weights_name'=>0,'loss_name'=>0,'basic_cost'=>0);}// 工费
		if(empty($luozuan)){$luozuan=array('price'=>0);}// 主石
		if(empty($deputystone)){$deputystone=array('deputystone_price'=>0);}// 副石
		$price = formatRound($associateInfo['weights_name']*(1+$associateInfo['loss_name']/100)*$material['gold_price']+$luozuan['price']+$deputystone['deputystone_price']+$associateInfo['basic_cost'],2);
		return $price;
	}
	//返回经过加点的sku数据
	public function getGoodsSku($goods_id,$where='',$format_type='info'){
		if( $goods_id ){
			$sql   = " zm_goods_sku.goods_id = $goods_id ";
		} else {
			$sql   = ' 1 = 1 ';
		}
		if( $where ){
			$sql   = " $sql and $where ";
		}
		return $this -> getDataAfterAddPoint('goods_sku',$sql,'goods_price','zm_goods_sku.sku_id asc','',$format_type);
	}

	
	public function getProductListAfterAddPoint($products,$uid = 0){
		foreach($products as $key=>&$val){
			$this -> calculationPoint($val['goods_id']); //很关键，计算需要加点的点数 
			if( $val['goods_type'] == '3' ){
				$attributes_array = array();
				$attrs            = array();
				$_goods_sku       = $this -> getGoodsSku($val['goods_id']);
				if($_goods_sku){
					$attributes   = $_goods_sku['attributes'];
					if (strpos($attributes, '^') !== false) {
						$attributes_array   = explode('^', $attributes);
					} else {
						$attributes_array[] = $attributes;
					}
					foreach ($attributes_array as $_r) {
						if( !empty($_r) ){
							$attr_array = explode(':', $_r);
							$attr_name  = M("goods_attributes") -> where(' attr_id = ' .  $attr_array[0]) -> getField('attr_name');
							$attrs[]    = array( 'attr_name' => $attr_name , 'attr_id' => $attr_array[0] , 'attr_value' => $attr_array[1] );
						}
					}
					$_goods_sku['attributes'] = $attrs;
					$val['goods_sku']         = $_goods_sku;
					$val['goods_price']       = $_goods_sku['goods_price'];
				}
			} else if ( $val['goods_type'] == '4' ){
				//定制,附加默认价格
				list($material, $associateInfo, $luozuan, $deputystone) = $this -> getJingGongShiData($val['goods_id'], 0, 0, 0);
				$val['material']      = $material;
				$val['associateInfo'] = $associateInfo;
				$val['luozuan']       = $luozuan;
				$val['deputystone']   = $deputystone;
				if( empty($val['price_model']) ){
					$val['size_info']   = array();
					$val['goods_price'] = $this -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
				}
				if( $val['price_model'] == '1' ){
					$category_name = M('goods_category')->where(" category_id = '$val[category_id]' ")->getField('category_name');
					$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
					if( $is_size ){
						$val['size_info']    = $this -> getGoodsAssociateSizeAfterAddPoint( " goods_id = $val[goods_id] and ".'material_id = '.$associateInfo['material_id']);
						$val['goods_price']  = $val['size_info']['size_price'];
						$is_dui              = InStringByLikeSearch($category_name,array('对戒'));
						if($is_dui){
							$size_info1           = $this -> getGoodsAssociateSizeAfterAddPoint( " sex = '1' and goods_id = $val[goods_id] and ".'material_id = '.$associateInfo['material_id']);
							$size_info2           = $this -> getGoodsAssociateSizeAfterAddPoint( " sex = '2' and goods_id = $val[goods_id] and ".'material_id = '.$associateInfo['material_id']);
							$val['goods_price']   = $size_info1['size_price'] + $size_info2['size_price'];
						}
					} else {
						$val['goods_price']  = $associateInfo['fixed_price'];
					}
				}

			}
			$products[$key]['goods_name_txt'] = msubstr($val['goods_name'],0,50,'utf-8');
			if($val['thumb'] == ''){
				$products[$key]['thumb']      = __ROOT__."/Public/Uploads/product/nopic.png";
			}
		}
		$products                             = getGoodsListPrice($products, $uid, 'consignment');
		return $products;
	}
	
	public function getProductInfoAfterAddPoint($product,$uid=0 ){
		if(!$product){
			return null;
		}
		$this                     -> calculationPoint($product['goods_id']); //很关键，计算需要加点的点数 
		$product['images']         = $this->get_goods_images($product['goods_id']);
		if( $product['images'] ){
			$product['big_path']   = $product['images'][0]['big_path'];
		}else{
			$product['big_path']   = "/Public/Uploads/product/nopic.png";
		}
		$product['content']        = html_entity_decode($product['content']);
		if( $product['goods_type'] == '3' ){
            $product['attributes']      = $this->getGoodsAttributes(3,$product['goods_id']);
            $product['similar']         = $this->getSimilarProduct($product['goods_id'],$uid,$product['activity_status']);
            if(!empty($product['similar'][0])){
                $product['goods_price'] = $product['similar'][0]['goods_price'];
				$product['activity_price'] = $product['similar'][0]['activity_price'];
                $product['marketPrice'] = $product['similar'][0]['marketPrice'];
            }else{
                $product                = getGoodsListPrice($product, $uid, 'consignment', 'single');
            }
		} else if( $product['goods_type'] == '4' ){
			$product['associate_deputystone'] = $this -> getGoodsAssociateDeputystoneAfterAddPoint("goods_id = $product[goods_id] AND deputystone_name NOT LIKE '%颗0'",'list');
            $product['associate_info']        = $this -> getGoodsAssociateInfoAfterAddPoint("goods_id = $product[goods_id]",'list');       
			if($product['associate_info']){
            	$product['associate_luozuan'] = $this -> getGoodsAssociateLuozuanAfterAddPoint("goods_id = $product[goods_id] and zm_goods_associate_luozuan.material_id = '".$product['associate_info'][0]['material_id']."'",'list');    
				$material                     = $this -> getGoodsMaterialAfterAddPoint('material_id = '.$product['associate_info'][0]['material_id']);
			}
			if( empty($product['price_model']) ){
            	$product['goods_price']           = $this -> getJingGongShiPrice($material,$product['associate_info'][0],$product['associate_luozuan'][0],$product['associate_deputystone'][0]);
			}
			if( $product['price_model'] == '1' ){
				$category_name = M('goods_category')->where(" category_id = '$product[category_id]' ")->getField('category_name');
				$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
				if( $is_size ){
					$product['size_info']   = $this -> getGoodsAssociateSizeAfterAddPoint( " goods_id = $product[goods_id] and ".'material_id = '.$product['associate_info'][0]['material_id'],'list');
					
					$product['hands']       = $this -> get_hand_list($product['goods_id'],$product['associate_info'][0]['material_id']);
					$is_dui                 = InStringByLikeSearch($category_name,array('对戒'));
					if($is_dui){
						list($product['hands1'],$product['hands2']) = $this -> get_hand_list_duijie($product['goods_id'],$product['associate_info'][0]['material_id']);
						$code = 0;$man=0;$women=0;
						foreach($product['size_info'] as $row){
							if(empty($row['sex'])){
								$product['goods_price'] += $row['size_price'];
								$code ++;
							}
							if($row['sex']=='1' && empty($man)){
								$product['goods_price'] += $row['size_price'];
								$man ++;
							}
							if($row['sex']=='2' && empty($woman)){
								$product['goods_price'] += $row['size_price'];
								$woman ++;
							}
							$num = $code+$man+$woman;
							if($num==2){
								break;
							}
						}
					}else{
						$product['goods_price'] = $product['size_info'][0]['size_price'];
						$product['hands']       = $this -> get_hand_list($product['goods_id'],$product['associate_info'][0]['material_id']);
					}
				} else {
					$product['goods_price'] = $product['associate_info'][0]['fixed_price'];
				}
			}
            $product                        = getGoodsListPrice($product,$uid,'consignment','single');
		}
		return $product;
	}
	public function getSimilarProduct($gid,$uid=0,$activity_status=''){
         $similar = $this -> getGoodsSku("$gid",'','list');
         if($similar){
            foreach($similar as $key=>$val){
                $attr      = explode("^",$val['attributes']);
                if($attr){
                    $attrs = "";
                    foreach($attr as $k=>$v){
                        $temp              = explode(":",$v);
                        if($temp){
                            $inputType     = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                            if($inputType == 3){ //如果是手填值,直接显示该值
                                $attrs    .= $temp[1];
                            } else {  //如果不是手填值,则查出对应的属性值
                                $attrs    .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                            }
                            $attrs        .= " ";
                        }
                        $similar[$key]['attrs']   = trim($attrs);
                    }
                    $val['goods_type']            = '3';  //告诉是成品或定制
					$val['activity_status']		  = $activity_status; //赋值是否为活动商品（为空则不是活动商品，为0 ，1则是活动商品）
                    $jiadian_val                  = getGoodsListPrice($val, $uid , 'consignment', 'single');
                    $similar[$key]['goods_price'] = $jiadian_val['goods_price'];
					$similar[$key]['activity_price'] = $jiadian_val['activity_price'];
                    $similar[$key]['marketPrice'] = $jiadian_val['marketPrice'];
                }
            }
         }
         return $similar;
    }

	public function getGoodsAttributes($goods_type,$goods_id){
        $join1       = " LEFT JOIN zm_goods_attributes ZGA ON ZGA.attr_id=GAA.attr_id";
        $join2       = " LEFT JOIN zm_goods_attributes_value ZGAV ON ZGAV.attr_id=GAA.attr_id AND ZGAV.attr_code&GAA.attr_code";
        $fieldToShow = " GAA.goods_attr_id, GAA.category_id, GAA.goods_id, GAA.attr_id, ZGAV.attr_code, GAA.attr_value, ZGA.attr_name, ZGA.input_type, ZGA.input_mode, ZGA.is_filter, ZGAV.attr_value_id, ZGAV.attr_value_name";
        $data        = M("goods_associate_attributes")->alias("GAA")->join($join1)->join($join2)->field($fieldToShow)->where("GAA.goods_id=$goods_id")->select();
        return $data;
    }

	public function completeUrl($goods_id,$type='images',$info){
		if( empty($info) || !is_array($info) ){
			return $info;
		}
		$url_header_string = '';
		$agent_id          = $this -> where(" goods_id = '$goods_id' ") -> getField('agent_id');
		$banfang_goods     = $this -> where(" goods_id = '$goods_id' ") -> getField('banfang_goods_id');
		$zlf_goods     = M('banfang_goods') -> where(" goods_id = '$goods_id' ") -> getField('goods_id');

		if( $agent_id > 0 && $banfang_goods ==0){
			//$url           = 'http://api-view.btbzm.com';
			if(!empty(C('ZMFX_IMG_URL'))){
				$url           = C('ZMFX_IMG_URL');
			}else{
				$url           = 'http://api-view.btbzm.com';
			}
		} else if( $agent_id == 0 || $banfang_goods >0){
			$url           = C('ZMALL_URL');
		}
		switch ( $type ){
			case 'images':
					$key = key($info);
					if(is_numeric($key)){
						foreach( $info as &$r ){
							$r['small_path'] = str_replace('./',$url.'/Public/',$r['small_path']);
							$r['big_path']   = str_replace('./',$url.'/Public/',$r['big_path']);
						}
					} else {
						$info['small_path']  = str_replace('./',$url.'/Public/',$info['small_path']);
						$info['big_path']    = str_replace('./',$url.'/Public/',$info['big_path']);
					}
				break;
			case 'goods':
					if( $info['thumb']   != '' ){
						$info['thumb']   = str_replace('./',$url.'/Public/',$info['thumb']);
					}
					if( $info['content'] != '' ){
						$info['content'] = str_replace('&quot;/Public/Uploads/article/','&quot;'.$url.'/Public/Uploads/article/',$info['content']);
					}
				break;
			default:
				break;
		}
		return $info;
	}

	public function get_goods_images($goods_id=0){
		$my_m = M('goods_images');
		$list = $my_m -> where(" goods_id = $goods_id ") -> select();
		$list = $this -> completeUrl($goods_id,'images',$list);
		return $list;
	}
	
	public function get_goods_images_one($goods_id=0){
		$my_m = M('goods_images');
		$data = $my_m -> where(" goods_id = $goods_id ") -> find();
		$data = $this -> completeUrl($goods_id,'images',$data);
		return $data;
	}
	
    /**
     * 代销货加点
     * zhy	find404@foxmail.compoint
     * 2017年5月12日 11:36:59
     */
	public function dis_goods_point($goods_id=0){
		$my_m = M('goods_images');
		$data = $my_m -> where(" goods_id = $goods_id ") -> find();
		$data = $this -> completeUrl($goods_id,'images',$data);
		return $data;
	}
	 
 
	
	/**
	*	获取商品视频
	*	zhy
	*	2016年11月15日 14:24:07
	*/
	public function get_goods_videos($goods_id=0){
		$zgv 		= M('goods_videos');
		$list   	= $zgv -> where(" goods_id = $goods_id ") -> select();
		if($list)	  return $list;
		else	      return null;
	}

	public function get_hand_list( $goods_id , $materialId ){
		$list  = $this -> getGoodsAssociateSizeAfterAddPoint(" goods_id = '$goods_id' and material_id = '$materialId' ",'list');
		$hands = array();
		if($list){
			foreach($list as $row){
				if( $row['max_size'] >= $row['min_size'] ){
					for( $i = (int)$row['min_size']; $i <= (int)$row['max_size']; $i++){
						$hands[] = $i;
					}
				}
			}
			$hands = array_unique($hands);
			foreach($hands as $key=>$row){
				if(empty($row)){
					unset($hands[$key]);
				}
			}
		}
		return $hands;
	}
	public function get_hand_list_duijie( $goods_id , $materialId ){

		$hands1  = array();
		$hands2  = array();
		$list1   = $this -> getGoodsAssociateSizeAfterAddPoint(" goods_id = '$goods_id' and material_id = '$materialId' and sex = '1' ",'list');
		$list2   = $this -> getGoodsAssociateSizeAfterAddPoint(" goods_id = '$goods_id' and material_id = '$materialId' and sex = '2' ",'list');
		if(!empty($list1)){
			foreach($list1 as $row){
				if( $row['max_size'] >= $row['min_size'] ){
					for( $i = (int)$row['min_size']; $i <= (int)$row['max_size']; $i++){
						$hands1[] = $i;
					}
				}
			}
			$hands1 = array_unique($hands1);
			foreach($hands1 as $key=>$row){
				if(empty($row)){
					unset($hands1[$key]);
				}
			}
		}
		if(!empty($list2)){
			foreach($list2 as $row){
				if( $row['max_size'] >= $row['min_size'] ){
					for( $i = (int)$row['min_size']; $i <= (int)$row['max_size']; $i++){
						$hands2[] = $i;
					}
				}
			}
			$hands2 = array_unique($hands2);
			foreach($hands2 as $key=>$row){
				if(empty($row)){
					unset($hands2[$key]);
				}
			}
		}

		return array($hands1,$hands2);
	}

	//获取4种商品信息归档
	public function productGoodsInfo($param){
		$goods_type    = $param['goods_type'];

		//0,1裸砖，2散货，3:珠宝成品4:钻托定制
		$goodsInfo = array(
			'status'=>0,
			'info'=>array(),
			'msg'=>'商品不存在'
		);
		switch($goods_type){
			case 0:
			case 1:
				$goodsInfo = $this->productGoodsOne($param);
				break;
			case 2:
				$goodsInfo = $this->productGoodsTwo($param);
				break;
			case 3:
				$goodsInfo = $this->productGoodsThree($param);
				break;
			case 4:
				$goodsInfo = $this->productGoodsFour($param);
				break;
			case 7:
				$goodsInfo = $this->productGoodsSeven($param);
				break;
			default:

				break;
		}
		return $goodsInfo;
	}

	protected function productGoodsOne($param){
		$info = array(
				'status'=>0,
				'info'=>array(),
				'msg'=>'商品已下架'
		);
		$gid           = $param['gid'];
		$uid           = isset($param['uid']) ? $param['uid'] : $_SESSION['web']['uid'];
		$GLM        = D("Common/GoodsLuozuan");
		$goods      = M('goods_luozuan')->where('gid='.$gid)->find();

		if($goods['luozuan_type'] == 1){
			//设置彩钻参数
			$GLM -> setLuoZuanPoint('0','1');
		}
		//GoodsLuozuan中where方法被重写，因此这里需要重新查询，不然价格不对
		$goods      = $GLM->where('gid='.$gid)->find();
		if($goods){
			$goods              = getGoodsListPrice($goods, $uid, 'luozuan', 'single');
			/*$goods_name = $goods['certificate_number'];
			$goods_sn   = $goods['goods_name'];*/
			$goods_sn = $goods['certificate_number'];
			$goods_name   = $goods['goods_name'];
			$goods['goods_name'] = $goods_name;
			$goods['goods_sn'] = $goods_sn;
			$goods['goods_type'] = $goods['luozuan_type'];
			$goods['goods_type_select_number'] = 1;

			$info = array(
					'status'=>100,
					'info'=>$goods,
					'msg'=>'获取数据成功'
			);
		}
		return $info;
	}

	protected function productGoodsTwo($param){
		$info = array(
				'status'=>0,
				'info'=>array(),
				'msg'=>'商品已下架'
		);
		$gid = $param['gid'];
		$GSobj = D('GoodsSanhuo');
		$where = array('goods_id'=>$gid);
		$goods = $GSobj->getInfo($where);
		if($goods){
			$goods['goods_type_select_number'] = 0;
			$goods = getGoodsListPrice($goods,$param['uid'],'sanhuo', 'single');
			$info = array(
					'status'=>100,
					'info'=>$goods,
					'msg'=>'获取数据成功'
			);
		}
		return $info;
	}

	protected function productGoodsThree($param){
		$uid           = isset($param['uid']) ? $param['uid'] : $_SESSION['web']['uid'];
		$info = array(
				'status'=>0,
				'info'=>array(),
				'msg'=>'商品已下架'
		);
		//$id_type      = $param['id_type'];
		$gid          = $param['gid'];
		$sku_sn       = $param['sku_sn'];
		$goods_number = isset($param['goods_number']) ? $param['goods_number'] : 1;
		$goods_type   = 3;
		$agent_id     = isset($param['agent_id']) ? $param['agent_id'] : C('agent_id');

		$G            = $this;
		$goods        = $G->get_info($gid, 0, 'shop_agent_id= "' . $agent_id . '" and sell_status = "1" ');

		if($gid && !empty($goods)){
			$goods['sku_sn']                = $sku_sn;
			$temp                           = $G->getGoodsSku($gid, " sku_sn = '$sku_sn' ");
			$temp['goods_type']             = $goods_type;
			$temp                           = getGoodsListPrice($temp, $uid, 'consignment', 'single');
			$goods['goods_price']           = formatRound($temp['goods_price'], 2);
			$goods['activity_price']        = formatRound($temp['activity_price'], 2);        //活动商品价格
			$goods['marketPrice']           = formatRound($temp['marketPrice'], 2);
			$goods['goods_sku']             = $temp;
			$goods['consignment_advantage'] = $temp["consignment_advantage"];
			$goods['activity_status']       = $temp["activity_status"];                        //活动商品标识
			$goods['goods_type_select_number'] = $goods_number;
			$info = array(
					'status'=>100,
					'info'=>$goods,
					'msg'=>'获取数据成功'
			);
		}
		return $info;
	}

	protected function productGoodsFour($param){
		$info = array(
				'status'=>0,
				'info'=>array(),
				'msg'=>'商品已下架'
		);
		//参数
		$uid           = isset($param['uid']) ? $param['uid'] : $_SESSION['web']['uid'];
		$gid           = $param['gid'];
		//$goods_type    = $param['goods_type'];



		$agent_id      = isset($param['agent_id']) ? $param['agent_id'] : C('agent_id');
		//材质
		$materialId    = isset($param['materialId']) ? $param['materialId'] : 0;
		//主石
		$diamondId     = isset($param['diamondId']) ? $param['diamondId'] : 0;
		//附石
		$deputystoneId = isset($param['deputystoneId']) ? $param['deputystoneId'] : 0;
		//手寸
		$hand          = isset($param['hand']) ? $param['hand'] : '';
		//个性刻字
		$word          = isset($param['word']) ? $param['word'] : '';
		//？？？
		$sd_id         = isset($param['sd_id']) ? $param['sd_id'] : '';
		$goods_number  = isset($param['goods_number']) ? $param['goods_number'] : 1;
		//$luozuan       = isset($param['luozuan']) ? $param['luozuan'] : 0;
		$word1         = isset($param['word1']) ? $param['word1'] : '';
		$hand1         = isset($param['hand1']) ? $param['hand1'] : '';
		$sd_id1        = isset($param['sd_id1']) ? $param['sd_id1'] : '';
		//参数

		$G             = $this;
		$goodsInfo     = $G -> get_info($gid,0,' shop_agent_id= "'.$agent_id.'" and sell_status = "1" ');
		$luozuanInfo   = $G -> getGoodsAssociateLuozuanAfterAddPoint("goods_id=$gid AND gal_id=$diamondId");
		$associateInfo = $G -> getGoodsAssociateInfoAfterAddPoint("goods_id=$gid AND zm_goods_associate_info.material_id=$materialId ");

		if($deputystoneId and $deputystoneId!='undefined') {
			$deputystone = $G -> getGoodsAssociateDeputystoneAfterAddPoint("gad_id=$deputystoneId");
		}


		/*if($luozuan >0){
			$certificate_number           = D("GoodsLuozuan") -> where(" gid = $luozuan ") -> getField("certificate_number");
			$goodsInfo['matchedDiamond']  = $certificate_number;
		}*/

		if($luozuanInfo && $associateInfo && $goodsInfo){
			$material                     = $G -> getGoodsMaterialAfterAddPoint(" material_id = $materialId ");
			if( !$goodsInfo['price_model'] ){
				$goodsInfo['goods_price'] = $G -> getJingGongShiPrice($material,$associateInfo,$luozuanInfo,$deputystone);
			}else{
				$category_name = M('goods_category') -> where(" category_id = '$goodsInfo[category_id]' ") -> getField('category_name');
				$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
				if( $is_size ){
					$size                     = $G -> getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
					$goodsInfo['goods_price'] = $size['size_price'];
					$is_dui                   = InStringByLikeSearch($category_name,array('对戒'));
					if($is_dui){
						$size                     = $G -> getGoodsAssociateSizeAfterAddPoint(" sex = '1' and min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
						$goodsInfo['goods_price'] = $size['size_price'];
						$size                     = $G -> getGoodsAssociateSizeAfterAddPoint(" sex = '2' and min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
						$goodsInfo['goods_price'] = $size['size_price']+$goodsInfo['goods_price'];
					}
				} else {
					$ass                      = $G->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
					$goodsInfo['goods_price'] = $ass['fixed_price'];
				}
			}

			$goodsInfo['associateInfo']  		= $associateInfo;
			$goodsInfo['luozuanInfo']    		= $luozuanInfo;
			$goodsInfo['deputystoneId']    		= $deputystoneId;
			$goodsInfo['deputystone']    		= $deputystone;
			$goodsInfo['hand']           		= $hand;
			$goodsInfo['word']           		= $word;
			$goodsInfo['hand1']          		= $hand1;
			$goodsInfo['word1']          		= $word1;
			$goodsInfo['materialId']          	= $materialId;
			$goodsInfo['diamondId']          	= $diamondId;
			$goodsInfo['materialId']          	= $materialId;
			if($sd_id){
				$S                       		= D('Common/SymbolDesign');
				$info                    		= $S -> getInfo($sd_id);
				$goodsInfo['sd_images']  		= $info['images_path'];
			}
			if($sd_id1){
				$S                       		= D('Common/SymbolDesign');
				$info                    		= $S -> getInfo($sd_id1);
				$goodsInfo['sd_images1'] 		= $info['images_path'];
			}
			$goodsInfo['sd_id']          		= $sd_id;
			$goodsInfo['sd_id1']         		= $sd_id1;
			$goods['goods_type_select_number']  = $goods_number;
			$goodsInfo                   		= getGoodsListPrice($goodsInfo, $uid, 'consignment', 'single' ,$agent_id);
			$info = array(
					'status'=>100,
					'info'=>$goodsInfo,
					'msg'=>'获取数据成功'
			);
		}

		return $info;
	}

	protected function productGoodsSeven($param){
		$info = array(
				'status'=>0,
				'info'=>array(),
				'msg'=>'商品已下架'
		);


		//参数
		$uid           = $param['uid'] ? $param['uid'] : $_SESSION['web']['uid'];

		$gid           = $param['gid'];
		$agent_id      = isset($param['agent_id']) ? $param['agent_id'] : C('agent_id');
		//材质
		$ma_id    = $param['ma_id'] ? $param['ma_id'] : 0;
		$goods_number  = $param['goods_type_select_number'] ? intval($param['goods_type_select_number']) : 1;
		//备注
		$note          = $param['note'] ? trim($param['note']) : '';
		$add_factory_sex          = $param['add_factory_sex'] ? $param['add_factory_sex'] : array();

		//主石		这里通过工厂款号确定	由于情侣戒的影响，因此这个字段修改为数组
		$factory_id     = $param['factory_id'] ? (is_array($param['factory_id']) ? $param['factory_id'] : array($param['factory_id'])) : array();
		$diamond = $param['diamond'] ? (is_array($param['diamond']) ? $param['diamond'] : array($param['diamond'])) : array();
		$deputystone_opened = $param['deputystone_opened'] ? (is_array($param['deputystone_opened']) ? $param['deputystone_opened'] : array($param['deputystone_opened'])) : array();
		$hand = $param['hand'] ? (is_array($param['hand']) ? $param['hand'] : array($param['hand'])) : array();
		$color = $param['color'] ? (is_array($param['color']) ? $param['color'] : array($param['color'])) : array();
		$clarity = $param['clarity'] ? (is_array($param['clarity']) ? $param['clarity'] : array($param['clarity'])) : array();

		//是否需要获取所有的可选属性
		//$select_all         = $param['select_all'] ? 1 : 0;
		//$select_param 用于验证商品的部分信息存不存在用的
		$select_param   = array(
				'error'=>array()
		);
		//参数

		$BG             = M('banfang_goods');
		$where = array(
			'bg.g_status'=>1,
			'bg.goods_id'=>$gid,
			'bg.agent_id'=>$agent_id
		);

		$goods_info = $BG->alias('bg')->where($where)->find();
		if($goods_info['zlf_id']>0){
			$banfang_cate = M('banfang_cate')->where(['id'=>$goods_info['banfang_jewelry']])->find();
		}
		

		$CM = M('banfang_cate');

		if($goods_info){
			$BF = M('banfang_factory');
			$BGM = M('banfang_goods_material');
			$BD = M('banfang_diamond');

			//男戒，女戒多有的一个字符串
			$six_name_arr = array('','女戒：','男戒：');
			//商品数量
			$goods_info['goods_type_select_number'] = $goods_number;
			//商品图片
			$imgsList  = $this -> get_goods_images($goods_info['goods_id']);
			//商品默认图片
			$goods_info["my_img"] = $imgsList[0]["small_path"] ? $imgsList[0]["small_path"] : '';
			//商品所有图片
			$goods_info["imgs_list"]	= $imgsList ? $imgsList : array();
			//商品大系列
			if($goods_info['banfang_big_series']){
				$banfang_big_series_info = $CM->where(array('agent_id'=>$agent_id,'id'=>$goods_info['banfang_big_series']))->find();
				$goods_info['banfang_big_series_info'] = $banfang_big_series_info ? $banfang_big_series_info : array();
			}
			//商品小系列
			if($goods_info['banfang_small_series']){
				$banfang_small_series_info = $CM->where(array('agent_id'=>$agent_id,'id'=>$goods_info['banfang_small_series']))->find();
				$goods_info['banfang_small_series_info'] = $banfang_small_series_info ? $banfang_small_series_info : array();
			}
			//商品分类
			if($goods_info['banfang_category']){
				$banfang_category_info = $CM->where(array('agent_id'=>$agent_id,'id'=>$goods_info['banfang_category']))->find();
				$goods_info['banfang_category_info'] = $banfang_category_info ? $banfang_category_info : array();
			}
			//商品款式
			if($goods_info['banfang_jewelry']){
				$banfang_jewelry_info = $CM->where(array('agent_id'=>$agent_id,'id'=>$goods_info['banfang_jewelry']))->find();
				$goods_info['banfang_jewelry_info'] = $banfang_jewelry_info ? $banfang_jewelry_info : array();
			}
			//商品属性
			$banfang_attrs_as = explode(',',trim($goods_info['banfang_attrs'],','));
			if($banfang_attrs_as){
				$banfang_attrs_info = $CM->where(array('agent_id'=>$agent_id,'id'=>array('in',$banfang_attrs_as)))->select();
				$goods_info['banfang_attrs_info'] = $banfang_attrs_info;
			}
			//手寸
			$hand_start = $goods_info['hand_start'];
			$hand_end = $goods_info['hand_end'];
			$hands_lists = array();
			for($k=$hand_start;$k<=$hand_end;$k++){
				$hands_lists[] = $k;
			}
			$setting_param = D('Common/BanfangDiamond')->getOfferParam(1);
			$color_lists = $setting_param['color_arr'];
			$clarity_lists = $setting_param['clarity_arr'];

			$factory_lists = array();
			$goods_price = 0;

			//材质
			$where_ma = array(
					'gm.goods_id'=>$gid,
					'gm.agent_id'=>$agent_id
			);
			if($ma_id>0){
				$where_ma['gm.id'] = $ma_id;
			}
			$material_info_temp_temp = $BGM->alias('gm')->join('zm_banfang_material m on gm.ma_id=m.ma_id')->where($where_ma)->find();

			//工厂款号
			$where_fa = array(
					'goods_id'=>$gid,
					'agent_id'=>$agent_id,
					'zhushi_from'=>array('gt',0)
			);

			if(!empty($add_factory_sex)){

				//$add_factory_sex不为空	则表示用户没有选择主石范围(目购物车那里没有选择主石范围导致的)，但填了主石大小 因此则根据主石大小来查询工厂款号
				if(!empty($diamond)){
					$factory_id = array();
					foreach($add_factory_sex as $key => $vxl){
						$zhushi_set_weight = $diamond[$key] ? $diamond[$key] : 0;

						$texp_where = $where_fa;
						$texp_where['zhushi_from'] = array('elt',$zhushi_set_weight);
						$texp_where['zhushi_to'] = array('egt',$zhushi_set_weight);
						$texp_where['sex'] = $vxl;

						$factory_dia_search_id = $BF->where($texp_where)->getField('f_id');
						$factory_id[] = $factory_dia_search_id>0 ? $factory_dia_search_id : 0;

						if(!$factory_dia_search_id){
							$texp_where = $where_fa;
							$texp_where['sex'] = $vxl;
							$factory_lists_arr_temp = $BF->where($texp_where)->select();
							$string_a_error = '主石范围为"';
							foreach($factory_lists_arr_temp as $fax_tm){
								$string_a_error .= $fax_tm['zhushi_from'].' - '.$fax_tm['zhushi_to'].';';
							}
							$string_a_error .= '",请重新输入主石重量';
							$select_param['error'][] = $string_a_error;
						}
					}
				}
			}else{
				if(empty($factory_id)){
					//如果没有择取默认的前面几条数据	情侣对戒有两条
					$factory_first_setting = $BF->where($where_fa)->group('sex')->getField('f_id,f_id,sex');
					if(!empty($factory_first_setting)){
						$factory_id = array_keys($factory_first_setting);
					}
				}
			}




			if(!empty($factory_id)){
				foreach($factory_id as $f_key=>$f_id){
					$factory_lists_temp = array();
					//根据工厂款号获取材料的重量，根据主石重，颜色净度获取 主石信息
					$where_fa['f_id'] = $f_id;
					$material_info_temp = $material_info_temp_temp;
					$factory_info_temp = $BF->where($where_fa)->find();


					if(!empty($factory_info_temp)){
						$diamond_from = $factory_info_temp['zhushi_from'];
						$diamond_to = $factory_info_temp['zhushi_to'];
						$factory_info_temp['zhushi_name'] = $factory_info_temp['zhushi_from'].' - '.$factory_info_temp['zhushi_to'];
						$factory_info_temp['fushi_name'] = $factory_info_temp['fushi_from'].' - '.$factory_info_temp['fushi_to'];
						$factory_info_temp['sex_name'] = $six_name_arr[$factory_info_temp['sex']] ? $six_name_arr[$factory_info_temp['sex']] : '';
						$weight_arr = explode(',',$factory_info_temp['material_weight']);
						$material_info_temp['weight'] = $weight_arr[$material_info_temp['type']] ? $weight_arr[$material_info_temp['type']] : 0;
						$material_info_temp['sex_name'] = $six_name_arr[$factory_info_temp['sex']] ? $six_name_arr[$factory_info_temp['sex']] : '';
						$diamond_temx = $diamond[$f_key] ? $diamond[$f_key] : 0;
						$color_info = $color[$f_key] ? $color[$f_key] : '';
						$clarity_info = $clarity[$f_key] ? $clarity[$f_key] : '';
						$hand_info = $hand[$f_key] ? $hand[$f_key] : 0;
						$deputystone_opened_info = $deputystone_opened[$f_key] ? $deputystone_opened[$f_key] : 0;

						if($diamond[$f_key]>$diamond_to || $diamond[$f_key]<$diamond_from){
							//默认取最大的值
							$diamond_temx = $diamond_to;
							$select_param['error'][] = '请输入正确的主石重量';
						}

						//主石
						$where_diamond = array(
								'carat'=>$diamond_temx,
								'color'=>$color_info,
								'clarity'=>$clarity_info,
						);
						$diamond_info_temp = $BD->where($where_diamond)->find();

						if(empty($diamond_info_temp)){
							$where_diamond = array(
									'carat'=>$diamond_temx,
							);
							$diamond_info_temp = $BD->where($where_diamond)->find();
						}

						if(empty($diamond_info_temp)){
							$select_param['error'][] = '主石不存在';
						}
						if(empty($material_info_temp)){
							$select_param['error'][] = '材料不存在';
						}

						/*if(!in_array($color_info,$color_lists)){
							$select_param['error'][] = '请选择颜色';
						}
						if(!in_array($clarity_info,$clarity_lists)){
							$select_param['error'][] = '请选择净度';
						}*/

						if($goods_info['zlf_id']>0){
							if(!in_array($hand_info,$hands_lists)&&$banfang_cate['is_hand']==1){
								$select_param['error'][] = '请选择手寸';
							}
						}else{
							if(!in_array($hand_info,$hands_lists)){
								$select_param['error'][] = '请选择手寸';
							}
						}

						if(!empty($diamond_info_temp) && !empty($material_info_temp) && !empty($factory_info_temp)){
							$factory_lists_temp['factory_info'] = $factory_info_temp;
							$factory_lists_temp['material_info'] = $material_info_temp;
							$factory_lists_temp['diamond_info'] = $diamond_info_temp;
							$factory_lists_temp['color_info'] = $color_info;
							$factory_lists_temp['clarity_info'] = $clarity_info;
							$factory_lists_temp['hand_info'] = $hand_info;
							$factory_lists_temp['note_info'] = $note;
							$factory_lists_temp['deputystone_opened_info'] = $deputystone_opened_info;

							//可选主石列表
							$where_fa_temp = $where_fa;
							$where_ma_temp = $where_ma;
							unset($where_fa_temp['f_id']);
							unset($where_ma_temp['gm.id']);
							$where_fa_temp['sex'] = $factory_info_temp['sex'];
							$factory_lists_arr_temp = $BF->field("*,concat(zhushi_from,'-',zhushi_to) zhushi_name,concat(fushi_from,'-',fushi_to) fushi_name")->where($where_fa_temp)->select();

							//可选材料列表
							$material_lists_arr_temp = $BGM->alias('gm')->join('zm_banfang_material m on gm.ma_id=m.ma_id')->where($where_ma_temp)->select();

							$diamond_info_temp['diamond_main_number'] = $goods_info['diamond_main_number'];
							$g_price_info_temp = $this->cateGoodsPrice($material_info_temp,$diamond_info_temp);
							$factory_lists_temp['g_price_info'] = $g_price_info_temp;
							$goods_price += $g_price_info_temp['goods_price'];
							/*foreach($factory_lists_arr_temp as $l_ke => $ley){
								$material_lists_arr_temp[$l_ke]['weight'] = $weight_arr[$material_lists_arr_temp[$l_ke]['type']] ? $weight_arr[$material_lists_arr_temp[$l_ke]['type']] : 0;
								$material_lists_arr_temp[$l_ke]['sex_name'] = $six_name_arr[$ley['sex']] ? $six_name_arr[$ley['sex']] : '普通戒指';
							}*/
							$factory_lists_temp['factory_lists_arr'] = $factory_lists_arr_temp;
							$factory_lists_temp['material_lists_arr'] = $material_lists_arr_temp;
							$factory_lists_temp['hands_lists'] = $hands_lists;
							$factory_lists_temp['color_lists'] = $color_lists;
							$factory_lists_temp['clarity_lists'] = $clarity_lists;

							$factory_lists[] = $factory_lists_temp;
						}

					}else{
						$select_param['error'][] = '请选择主石范围';
					}

				}
			}

			if(empty($factory_lists)){
				$select_param['error'][] = '请选择主石范围';
			}
			
			//商品传入参数
			$goods_info['goods_attr_param'] = $param;
			$goods_info['factory_lists'] = $factory_lists;
			$goods_info['goods_price'] = $goods_price;
			$goods_info['select_param'] = $select_param;


			$info = array(
					'status'=>100,
					'info'=>$goods_info,
					'msg'=>'获取数据成功'
			);

		}


		return $info;
	}

	public function getBanfangLists($param){


		$BG = M('banfang_goods');
		$sort_order = $param['sort_order'] ? $param['sort_order'] : array();
		$sort_order['bg.goods_id'] = 'desc';
		$attr_id = (!empty($param['attr_id']) && is_array($param['attr_id'])) ? $param['attr_id'] : array();
		//参数开始
		$agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
		$n = $param['n'] ? $param['n'] : 13;
		$n_count = false;
		$g_status = isset($param['g_status']) ? $param['g_status'] : 0;

		$search_rank_one = $param['search_rank_one'] ? $param['search_rank_one'] : 0;
		$search_rank_two = $param['search_rank_two'] ? $param['search_rank_two'] : 0;
		$search_rank_three = $param['search_rank_three'] ? $param['search_rank_three'] : 0;
		$search_attr_id = $param['search_attr_id'] ? explode(',',$param['search_attr_id']) : array();
		$search_content = trim($param['search_content']);

		$no_page = $param['no_page'] ? $param['no_page'] : false;
		$no_foeach = $param['no_foeach'] ? $param['no_foeach'] : false;
		//参数结束

		//设置where条件开始
		$where = array(
			'bg.g_status'=>1,
		);
		$where['_string'] = '1=1';
		$join = array();

		$search_menu_arr = array();
		if($search_rank_one>0){
			$rank_one_arr = M('banfang_url')->where(array('id'=>$search_rank_one))->find();
			if(!empty($rank_one_arr) && $rank_one_arr['ca_id']>0){
				$search_menu_arr[] = $rank_one_arr['ca_id'];
			}
			if($rank_one_arr['condition_type']==1){
				//$n = 100;
				$n_count = 100;
			}
		}
		if($search_rank_two>0){
			$rank_one_arr = M('banfang_url')->where(array('id'=>$search_rank_two))->find();
			if(!empty($rank_one_arr) && $rank_one_arr['ca_id']>0){
				$search_menu_arr[] = $rank_one_arr['ca_id'];
			}
			if($rank_one_arr['condition_type']==1){
				//$n = 100;
				$n_count = 100;
			}
		}
		if($search_rank_three>0){
			$rank_one_arr = M('banfang_url')->where(array('id'=>$search_rank_three))->find();
			if(!empty($rank_one_arr) && $rank_one_arr['ca_id']>0){
				$search_menu_arr[] = $rank_one_arr['ca_id'];
			}
			if($rank_one_arr['condition_type']==1){
				//$n = 100;
				$n_count = 100;
			}
		}

		if(!empty($search_menu_arr)){
			$where['_string'] .= D('Common/BanfangCate')->getCateConditions($search_menu_arr,'bg');
		}


		$attr_id_arr = array_merge($search_attr_id,$attr_id);
		if(!empty($search_content)){
			$where['_string'] .= " AND (bg.goods_name like '%$search_content%' or bg.goods_sn like '%$search_content%')";
		}

		$where['_string'] .= D('Common/BanfangCate')->getCateConditions($attr_id_arr,'bg');

		//
		$agent_id_arr = is_array($agent_id) ? $agent_id : array($agent_id);
		$where['bg.agent_id'] = array('in',$agent_id_arr);
		if($g_status>0){
			$where['bg.g_status'] = $param['g_status'];
		}

		if(!$no_page){
			//默认是有分页
			$count = $BG->alias('bg')->join($join)->where($where)->count();
			if($n_count && $count>$n_count){
				$count = $n_count;
			}

			$Page = new Page($count,$n);
			$data = $BG->alias('bg')->join($join)->where($where)->order($sort_order)->limit($Page->firstRow,$Page->listRows)->select();
			$page = $Page->show();

		}else{
			$data = $BG->alias('bg')->join($join)->where($where)->order($sort_order)->select();
			$page = '';
			$count = count($data);
		}
		//处理数据
		$lists = array();


		foreach($data as $key=>$value){
			$imgsList  = $this -> get_goods_images($value['goods_id']);
			$value["my_img"] = $imgsList[0]["small_path"];
			/*$info = $this->productGoodsInfo(array('goods_type'=>7,'gid'=>$value['goods_id']));
			$value['goods_price'] = $info['info']['goods_price'];*/

			$value["imgs_list"]	= $imgsList;
			$lists[] = $value;
		}
		$return = array(
				'lists'=>$lists,
				'count'=>$count,
				'page'=>$page
		);

		return $return;
	}


	public function addZlfGoods($count=10){


		$agent_id = C('agent_id');
		$count_number = 0;
		$banfang_cates = M('banfang_cate')->select();
		$cate_arr = array();
		foreach($banfang_cates as $cates){
			if(!empty($cates['zlf_field']) && !empty($cates['zlf_val'])){
				$cate_arr[$cates['zlf_field']][$cates['zlf_val']] = $cates;
			}

		}


		if($count>0){
			$ZLF_UM = M('gskhh','zlf_','ZLF_DB');
			$ZLFGB = M('gskhb','zlf_','ZLF_DB');
			$BG = M('banfang_goods');
			$BF = M('banfang_factory');
			$BGF = M('banfang_goods_material');
			$BM = M('banfang_material');
			$where = 'WDflag is null or WDflag=0';
			//$where['gskh'] = '210D084';
			$lists = $ZLF_UM->where($where)->limit($count)->select();


			foreach($lists as $temp){
				$BM    -> startTrans();
				$data_1 = array(
					'agent_id'=>$agent_id,
					//商品编号->公司款号
						'goods_sn'=>$temp['gskh'] ? $temp['gskh'] : '',
						'goods_name'=>$temp['dlmc'].' '.$temp['plmc'],
					//商品上架->订单是否可用
						//'g_status'=>$temp['ddsy']=='Y' ? 1 : 0,
						'g_status'=>1,
					//手寸最小值->起始规格
						'hand_start'=>intval($temp['qsgg']),
					//手寸最小值->起始规格
						'hand_end'=>intval($temp['jzgg']),
					//主石数量->主石数量
						'diamond_main_number'=>intval($temp['zssl']),
					//副石数量->副石数量
						'diamond_sub_number'=>intval($temp['fssl']),
					//上架时间->上架日期
						'g_sale_time'=>strtotime($temp['sjrq']),
					//备注->备注
						'note'=>$temp['bz'] ? $temp['bz'] : '',
						'zlf_id'=>$temp['djlsh']
				);


				$data_2 = array(
					//部分没有编码的暂时用名称代替编码进行使用
					//对于Y,N的采用 key作为编码

					//attr3->大类名称
						'dlmc'=>$temp['dlmc'],
					//attr4->品类名称
						'plmc'=>$temp['plmc'],
					//attr1->系列名称
						'xlmc'=>$temp['xlmc'] ? $temp['xlmc'] : '',
					//attr2->小系列名
						'xxlm'=>$temp['xxlm'],
					//attr->是否耙金
						'sfbj'=>$temp['sfbj'],
					//attr->是否群镶
						'sfqx'=>$temp['sfqx'],
					//attr->是否无款
						'sfwk'=>$temp['sfwk'],
					//attr->是否新款
						'sfxk'=>$temp['sfxk'],
					//attr->是否精品
						'sfjp'=>$temp['sfjp'],
					//款式镶法
						'ksxf'=>$temp['ksxf'],
					//副石配宝石
						'fspps'=>$temp['fspps'],
					//是否做车花
						'sfzch'=>$temp['sfzch'],
					//臂形
						'bx'=>$temp['bx'],
						'zxsl'=>$temp['zxsl'],
					//款式类型
						'kslx'=>$temp['kslx'],
				);
				//暂时用名称作为编码
				$attrs = '';
				//fspps还有问题
				foreach($data_2 as $tex_key => $tex_val){
					if(!empty($tex_key) && !empty($tex_val) && $cate_arr[$tex_key][$tex_val]){
						if($cate_arr[$tex_key][$tex_val]['top_id']==1){
							$data_1['banfang_big_series'] = $cate_arr[$tex_key][$tex_val]['id'];
						}elseif($cate_arr[$tex_key][$tex_val]['top_id']==2){
							$data_1['banfang_small_series'] = $cate_arr[$tex_key][$tex_val]['id'];
						}elseif($cate_arr[$tex_key][$tex_val]['top_id']==3){
							$data_1['banfang_category'] = $cate_arr[$tex_key][$tex_val]['id'];
						}elseif($cate_arr[$tex_key][$tex_val]['top_id']==4){
							$data_1['banfang_jewelry'] = $cate_arr[$tex_key][$tex_val]['id'];
						}else{
							if(!in_array($tex_val,array('N','否'))){
								$attrs .= $cate_arr[$tex_key][$tex_val]['id'].',';
							}
						}
					}
				}
				//周六福那边修改数据之后没有/分割的数据
				/*$ksxf = $temp['ksxf'];
				if(!empty($ksxf)){
					$tempx = explode('/',$ksxf);
					foreach($tempx as $xxqw){
						if(in_array($xxqw,$cate_arr['ksxf'][$xxqw])){
							$attrs .= $cate_arr['ksxf'][$xxqw]['id'].',';
						}
					}
				}*/
				$kzsg = trim($temp['kzsg']);
				if(!empty($kzsg)){
					if(strpos($kzsg,'，')){
						$tempx = explode('，',$kzsg);
					}elseif(strpos($kzsg,'/')){
						$tempx = explode('/',$kzsg);
					}elseif(strpos($kzsg,'、')){
						$tempx = explode('、',$kzsg);
					}elseif(strpos($kzsg,' ')){
						$tempx = explode(' ',$kzsg);
					}else{
						$tempx = array($kzsg);
					}
					foreach($tempx as $xxqw){
						if(in_array($xxqw,$cate_arr['kzsg'][$xxqw])){
							$attrs .= $cate_arr['kzsg'][$xxqw]['id'].',';
						}
					}
				}

				$fenshu_arr = $this->cateFenshu($temp['zsqsz'],$temp['zsjzz']);
				if(!empty($fenshu_arr)){
					$attrs .= implode(',',$fenshu_arr).',';
				}

				if(!empty($attrs)){
					$attrs = ','.$attrs;
				}

				$data_1['banfang_attrs'] = $attrs;


				$info_my_goods = $BG->where(array('zlf_id'=>$data_1['zlf_id']))->find();

				if(!empty($info_my_goods)){
					$boolx = $BG->where(array('goods_id'=>$info_my_goods['goods_id']))->save($data_1);
					$goods_id = $info_my_goods['goods_id'];
				}else{
					$goods_id = $BG->add($data_1);
				}

				$ZLF_UM->where(array('DjLsh'=>$data_1['zlf_id']))->save(array('WDflag'=>1));

				/*$jpcslb = $temp['jpcslb'];
				if(!empty($jpcslb)){
					$material = explode(',',$jpcslb);
				}
				$ma_arr = array();
				foreach($material as $mat){
					$mater = $BM->where('name like "%'.$mat.'%"')->getField('ma_id');
					if($mater){
						$ma_arr[] = array(
								'ma_id'=>$mater,
								'status'=>1,
								'goods_id'=>$goods_id,
								'agent_id'=>$agent_id
						);
					}
				}
				if(empty($ma_arr)){
					$BM->rollback();
					continue;
				}*/

				$childs = $ZLFGB->where(array('DjLsh'=>$temp['djlsh']))->select();

				$kyclbm = $childs[0]['kyclbm'];
				if(!empty($kyclbm)){
					$material = explode(',',$kyclbm);
				}
				$ma_arr = array();
				foreach($material as $mat){
					$mater = $BM->where(array('code'=>$mat))->getField('ma_id');
					if($mater){
						$ma_arr[] = array(
								'ma_id'=>$mater,
								'status'=>1,
								'goods_id'=>$goods_id,
								'agent_id'=>$agent_id,

						);
					}
				}


				if(empty($ma_arr)){
					$BM->rollback();
					continue;
				}

				$data_3 = array();
				foreach($childs as $child){
					if(empty($child['gckh'])){
						continue;
					}
					$data_4 = array(
						'agent_id'=>$agent_id,
						'goods_id'=>$goods_id,
						//'gysbm'->'gysbm',		//供应商编码
						//'gysmc'->'gysmc',		//供应商名称
						//工厂款号->工厂款号
						'banfang_sn'=>$child['gckh'],
						//工厂款号->工厂款号
						'zhushi_from'=>$child['zsqszb'],
						//工厂款号->工厂款号
						'zhushi_to'=>$child['zsjzzb'],
						//工厂款号->工厂款号
						'fushi_from'=>$child['fsqszb'],
						//工厂款号->工厂款号
						'fushi_to'=>$child['fsjzzb'],
						'material_weight'=>$child['ptckjz'].','.$child['kjckjz'],
						'sex'=>($child['nl']=='男') ? 1 : ($child['nl']=='女' ? 2 : 0),
					);

					$data_4_info = $BF->where(array('banfang_sn'=>$child['gckh'],'goods_id'=>$goods_id))->find();
					if(!empty($data_4_info)){
						$bool = $BF->where(array('f_id'=>$data_4_info['f_id']))->save($data_4);
					}else{
						$f_id = $BF->add($data_4);
					}

					$data_3[] = $data_4;
				}



				if(empty($data_3)){
					$BM->rollback();
					continue;
				}
				//添加商品
				if($goods_id){
					if(!empty($ma_arr)){
						$bool = $BGF->where(array('goods_id'=>$goods_id))->delete();
						$bool = $BGF->addAll($ma_arr);
					}
					$ZLF_UM->where(array('DjLsh'=>$data_1['zlf_id']))->save(array('WDflag'=>1));
					$BM -> commit();
					$count_number++;
					//$bool = $ZLF_UM->where(array('DjLsh'=>$temp['djLsh']))->save(array('WDflag'=>1));
				}else{
					$BM->rollback();
				}


			}

		}

		return $count_number;


	}

	public function cateGoodsPrice($material,$diamond){
		$return = array(
			//商品价格
			'goods_price'=>0,
			//材料价格
			'material_price'=>0,
			//主石价格
			'diamond_price'=>0,
			//主石单价
			'material_unit_price'=>0,
			//主石单价
			'diamond_unit_price'=>0,
		);
		$GDM = D('Common/BanfangDiamond');
		if(!empty($material)){
			$return['goods_price'] += $material['weight']*$material['price'];
			$return['material_price'] += $material['weight']*$material['price'];
			$return['material_unit_price'] += $material['price'];
		}

		if(!empty($diamond)){
			$diamond['price'] = $diamond['price']*$diamond['carat'];
			$diamond_price = $GDM->getCatePrice($diamond);
			$return['diamond_unit_price'] += $diamond_price;
			$return['goods_price'] += $diamond_price*$diamond['diamond_main_number'];
			$return['diamond_price'] += $diamond_price*$diamond['diamond_main_number'];
		}

		return $return;
	}

	public function cateFenshu($start,$end){
		$arrData = array(
				518=>array('min'=>'0.01','max'=>'0.02'),
				519=>array('min'=>'0.03','max'=>'0.07'),
				520=>array('min'=>'0.08','max'=>'0.12'),
				521=>array('min'=>'0.13','max'=>'0.17'),
				522=>array('min'=>'0.18','max'=>'0.22'),
				523=>array('min'=>'0.23','max'=>'0.29'),
				524=>array('min'=>'0.30','max'=>'0.39'),
				525=>array('min'=>'0.40','max'=>'0.49'),
				526=>array('min'=>'0.50','max'=>'0.69'),
				527=>array('min'=>'0.70','max'=>'0.89'),
				528=>array('min'=>'0.90','max'=>'0.99'),
				529=>array('min'=>'1.00','max'=>'1.49'),
				530=>array('min'=>'1.50','max'=>'1.99'),
				531=>array('min'=>'2.00','max'=>'2.99'),
				//532=>array('min'=>'3.00','max'=>''),

		);

		$return = array();
		foreach($arrData as $key=>$val){
			if(($start>=$val['min'] && $start<=$val['max']) || ($end>=$val['min'] && $end<=$val['max'])){
				$return[] = $key;
			}
		}

		if($end>3.00){
			$return[] = 532;
		}
		return $return;

	}


}
?>
