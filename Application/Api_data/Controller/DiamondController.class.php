<?php
/**
*	裸钻接口
*	zhy
*	2016年11月4日 16:29:13
*/
namespace Api_data\Controller;
class DiamondController extends Api_dataController{
 

    public function getList(){

        $page_id                   = I('page_id',1);
        $page_size                 = I('page_size',50);

        $where['weight']           = I('Weight')?explode(',',I('Weight')):array();
        $where['price']            = I('Price')?explode(',',I('Price')):array();

        $where['shape']            = I('Shape')?explode(',',I('Shape')):array();

        $where['color']            = I('Color')?explode(',',I('Color')):array();
        $where['clarity']          = I('Clarity')?explode(',',I('Clarity')):array();
        $where['location']         = I('Location')?explode(',',I('Location')):array();
        $where['cut']              = I('Cut')?explode(',',I('Cut')):array();
        $where['fluor']            = I('Fluor')?explode(',',I('Fluor')):array();
        // 奶色，咖色筛选
        if(I('MilkCoffee')){
            $milkCoffee = explode(',',I('MilkCoffee'));
            if(in_array('NOMILK', $milkCoffee)){
                // 无奶
                $where['milk'] = ['无奶'];
            }
            if(in_array('NOCOFFEE', $milkCoffee)){
                // 无咖
                $where['coffee'] = ['无咖'];
            }
        }

        $where['polish']           = I('Polish')?explode(',',I('Polish')):array();
        $where['symmetry']         = I('Symmetry')?explode(',',I('Symmetry')):array();
        $where['intensity'] 	   = I('Intensity')?explode(',',I('Intensity')):array();
		$where['certificate_type'] = I('Cert')?explode(',',I('Cert')):array();
		$luozuan_type       	   = I('luozuan_type','0','intval');
		$goods_sn				   = I('goods_sn','','intval');
        $order                     = I('order','gid','trim');
		$desc                      = I('desc','desc','trim');
		$part                      = I('part', 0);
        $agent_id                  = C('agent_id');
        $uid				       = C('uid');
		if($luozuan_type == 1){
			$sglM = new \Common\Model\GoodsLuozuanModel(1);
		}else{
			$sglM = D('GoodsLuozuan');
		}

		if($goods_sn){
            $goods_where['certificate_number'] = array('like','%'.$goods_sn.'%');	
			$goods_where['goods_name']		   = array('like','%'.$goods_sn.'%');
			$goods_where['_logic']             = 'or';
			$goods_sn_where['_complex']        = $goods_where;
			$goods_sn_where['goods_number']    = array('gt',0);
			$data                              = $sglM -> getLuozuanList($goods_sn_where,'gid desc',$page_id,$page_size,$agent_id );
            $data['list']                      = getGoodsListPrice($data['list'],$uid,'luozuan');
			$this -> echo_data('100','获取成功',$data);
		}

        $range = array(
            'weight',
            'price',
        );
		$range2 = array(
            'color',
            'clarity',
        );

        foreach($where as $key=>$row){

            if( !is_array($row) ){
                $this -> echo_data( '100', '参数错误', new \stdclass() );
            }
            if( count($row) == 0 ){
                unset($where[$key]);
                continue;
            }
            $args           = array();
            //范围判断
            if( array_search($key,$range) !== false ){
                if($key == 'price'){
                    foreach( $row as $r ){
                        if( strpos($r,'-') !== false ){
                            $d      = explode('-',$r);
                            $args[] = array('between',array($d[0],$d[1]));
                        } else {
                            $args[] = array('egt',$r);
                        }
                    }
                    if($uid){         //如有登录，则获取会员折扣
						if( $where['luozuan_type'] == 1 ){
							$userdiscount = getUserDiscount($uid, 'caizuan_discount');
						}else{
							$userdiscount = getUserDiscount($uid, 'luozuan_discount');
						}

                    }else{
                        $userdiscount     = 0;
                    }
					$point                = 0;
					if( $luozuan_type == 1 ){
						 $point           = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
						 $point           = $point + get_luozuan_advantage(0,1);
					}else{
						 $point           = D("GoodsLuozuan") -> setLuoZuanPoint('0','0');
						 $point           = $point + get_luozuan_advantage(0,0);
					}

                    $dollar_huilv    = C('dollar_huilv');
                    $point           = $point + $userdiscount;
                    if( count($args) > 1 ){
                        $args[]                    = 'and';
                        if(C('price_display_type') == 0) {   //直接加点
                            $where["(`dia_global_price` * $dollar_huilv * ($point+`dia_discount`)/100 * `weight`)"]       = $args;
                        }else{
                            $where["(`dia_global_price` * $dollar_huilv * `dia_discount` / 100 * (1 + $point/100) * `weight`)"] = $args;
                        }
                    }else{
                        if(C('price_display_type') == 0) {   //直接加点
                            $where["(`dia_global_price` * $dollar_huilv * ($point+`dia_discount`)/100 * `weight`)"]       = $args[0];
                        }else{
                            $where["(`dia_global_price` * $dollar_huilv * `dia_discount` / 100 * (1 + $point/100) * `weight`)"] = $args[0];
                        }
                    }
                    unset($where[$key]);
                }else{
                    foreach( $row as $r ) {
                        if (strpos($r, '-') !== false) {
                            $d = explode('-', $r);
                            // 如果是匹配钻石，则匹配相邻区间; 如果是挑选裸钻，则精准匹配
//                            if($part==1){
//                                $args[] = array('between',array(substr(sprintf("%.2f", $d[0]),0,-1)-'0.05',substr(sprintf("%.2f", $d[1]),0,-1)+'0.1'));
//                            }else{
                            $args[] = array('between', array($d[0], $d[1]));
//                            }
                        } else {
                            $args[] = array('egt', $r);
                        }
                    }
                    if(count($args)>1){
                        $args[]      = 'or';
                        $where[$key] = $args;
                    }else{
                        $where[$key] = $args[0];
                    }
                }
            }else if(array_search($key,$range2) !== false){
				foreach( $row as $r ){
                    $args[] = trim($r);//如果为颜色或者净度，则每次搜索key,同时搜索key+,key-
					$args[]	= trim($r).'+';
					$args[]	= trim($r).'-';
                }
                $where[$key] = array('in',$args);
			}else if($key == 'location'){
				foreach( $row as $r ){ //如果为订单来源，则查询quxiang子段，unset掉原本的location；
					if($r == '订货'){
						$where['quxiang'] = array(array('like','%定货%'),array('like','%订货%'),array('like','%国外%'),'or');
					}else if($r == 'SDG'){
						$where['type'] = 1 ;
					}else{
						$where['quxiang'] = '现货';
					}
                }
				unset($where[$key]);
			}else {
				foreach( $row as $r ){
                    $args[]  = trim($r);//这里可能需要加上更强力的检查过滤函数
				}
                $where[$key] = array('in',$args);
			}
        }
		$where['goods_number'] = array('gt',0);
		$where['luozuan_type'] = 0;
        
		
		$data                  = $sglM -> getLuozuanList($where,"$order $desc",$page_id,$page_size,$agent_id);
		$data['huilv']		   = C('DOLLAR_HUILV');
		$data['price_display_type']	= C('price_display_type');
        $data['list']          = getGoodsListPrice($data['list'],$uid,'luozuan');
		if($data['list'] == false){
			$data['list']	= '';
		}else{
			foreach($data['list'] as &$row){
				$c_info               = array();
				$c_info['uid']        = $uid;
				$c_info['goods_id']   = $row['gid'];
				$c_info['agent_id']   = $agent_id;
				$c = M('collection') -> where($c_info) -> find(); //避免重复
				if( $c ){
					$row['is_collection'] = '1';
				}else{
					$row['is_collection'] = '0';
				}
			}
		}
        $this                 -> echo_data('100','获取成功',$data);
	}

    public function getCertInfo(){
		$type   = I('type','1','intval');//1.gia 2.IGI 3.HRD 4.NGTC
        $number = I('number','');
        $weight = I('weight','');

        define('cacheDir','./zs/data.cache/');
        $cacheDir  = './zs/data.cache/';
        if($number){
            $where = " zs_id = '".$number."'";
        }else{
            $this -> echo_data('100','获取成功',new \stdclass());
        }
        if($weight){
            $where .= " AND zs_weight = '".$weight."' ";
        }
        $res        = M('report') -> where($where) -> find(); 

        // 引用 think\Report.class.php
        $Report  = new \Think\Report(); 
        $Report -> report($number,$weight,$type);
        $zs_data = $Report -> getReportData();  
        if($zs_data){	//若返回数据为数组则输出数据，否则输出错误信息
            $data = array();
            foreach($zs_data as $row){
                foreach($row as $k => $r){
                    $v['title'] = $k;
                    $v['value'] = $r;
                    $data[]     = $v;
                }
            }
        }else{
            $data = new \stdclass();
        }
        $this -> echo_data('100','获取成功',$data);
    }
	
	
	/**
	 * auth	：fangkai
	 * content：组装裸钻搜索条件
	 * time	：2016-11-3
	**/
    public function searchCriteria(){
		$data	= array(
			'0'	=>array(
				'name'	=>'形状',
				'type'	=>'Shape',
				'value'	=>array(
					'0'=>array(
						'name'	=> '圆形',
						'value'	=> 'ROUND'
					),
					'1'=>array(
						'name'	=> '椭圆',
						'value'	=> 'OVAL'
					),
					'2'=>array(
						'name'	=> '心形',
						'value'	=> 'HEART'
					),
					'3'=>array(
						'name'	=> '马眼',
						'value'	=> 'MARQUISE'
					),
					'4'=>array(
						'name'	=> '水滴',
						'value'	=> 'PEAR'
					),
					'5'=>array(
						'name'	=> '方形',
						'value'	=> 'PRINCESS'
					),
					'6'=>array(
						'name'	=> '枕形',
						'value'	=> 'CUSHION'
					),
					'7'=>array(
						'name'	=> '梯形',
						'value'	=> 'BAGUETTE'
					),
					'8'=>array(
						'name'	=> '蕾蒂恩',
						'value'	=> 'RADIANT'
					),
					'9'=>array(
						'name'	=> '祖母绿',
						'value'	=> 'EMERALD'
					),
					'10'=>array(
						'name'	=> '方形祖母绿',
						'value'	=> 'SQUARE EMERALD'
					)
					
					
				)
			),
			'1'	=>array(
				'name'	=>'颜色',
				'type'	=>'Color',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'D',
						'value'	=> 'D'
					),
					'1'=>array(
						'name'	=> 'E',
						'value'	=> 'E'
					),
					'2'=>array(
						'name'	=> 'F',
						'value'	=> 'F'
					),
					'3'=>array(
						'name'	=> 'G',
						'value'	=> 'G'
					),
					'4'=>array(
						'name'	=> 'H',
						'value'	=> 'H'
					),
					'5'=>array(
						'name'	=> 'I',
						'value'	=> 'I'
					),
					'6'=>array(
						'name'	=> 'J',
						'value'	=> 'J'
					),
					'7'=>array(
						'name'	=> 'K',
						'value'	=> 'K'
					),
                    '8'=>array(
                        'name'	=> 'L',
                        'value'	=> 'L'
                    ),
                    '9'=>array(
                        'name'	=> 'M',
                        'value'	=> 'M'
                    ),
                    '10'=>array(
                        'name'	=> 'N',
                        'value'	=> 'N'
                    )
				)
			),
			'2'	=>array(
				'name'	=>'净度',
				'type'	=>'Clarity',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'IF',
						'value'	=> 'IF'
					),
					'1'=>array(
						'name'	=> 'VVS1',
						'value'	=> 'VVS1'
					),
					'2'=>array(
						'name'	=> 'VVS2',
						'value'	=> 'VVS2'
					),
					'3'=>array(
						'name'	=> 'VS1',
						'value'	=> 'VS1'
					),
					'4'=>array(
						'name'	=> 'VS2',
						'value'	=> 'VS2'
					),
					'5'=>array(
						'name'	=> 'SI1',
						'value'	=> 'SI1'
					),
					'6'=>array(
						'name'	=> 'SI2',
						'value'	=> 'SI2'
					)
				)
			),
			'3'	=>array(
				'name'	=>'证书',
				'type'	=>'Cert',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'GIA',
						'value'	=> 'GIA'
					),
					'1'=>array(
						'name'	=> 'HRD',
						'value'	=> 'HRD'
					),
					'2'=>array(
						'name'	=> 'IGI',
						'value'	=> 'IGI'
					),
					'3'=>array(
						'name'	=> '国检',
						'value'	=> 'NGTC'
					)
				)
			),
			'4'	=>array(
				'name'	=>'切工',
				'type'	=>'Cut',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'EX',
						'value'	=> 'EX'
					),
					'1'=>array(
						'name'	=> 'VG',
						'value'	=> 'VG'
					),
					'2'=>array(
						'name'	=> 'GOOD',
						'value'	=> 'GD'
					)
				)
			),
			'5'	=>array(
				'name'	=>'抛光',
				'type'	=>'Polish',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'EX',
						'value'	=> 'EX'
					),
					'1'=>array(
						'name'	=> 'VG',
						'value'	=> 'VG'
					),
					'2'=>array(
						'name'	=> 'GOOD',
						'value'	=> 'GD'
					)
				)
			),
			'6'	=>array(
				'name'	=>'对称',
				'type'	=>'Symmetry',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'EX',
						'value'	=> 'EX'
					),
					'1'=>array(
						'name'	=> 'VG',
						'value'	=> 'VG'
					),
					'2'=>array(
						'name'	=> 'GOOD',
						'value'	=> 'GD'
					)
				)
			),
			'7'=>array(
				'name'	=>'荧光',
				'type'	=>'Fluor',
				'value'	=>array(
					'0'=>array(
						'name'	=> 'F',
						'value'	=> 'F'
					),
					'1'=>array(
						'name'	=> 'N',
						'value'	=> 'N'
					),
					'2'=>array(
						'name'	=> 'S',
						'value'	=> 'S'
					),
                    '3'=>array(
                        'name'	=> 'M',
                        'value'	=> 'M'
                    ),
                    '4'=>array(
                        'name'	=> 'VS',
                        'value'	=> 'VS'
                    ),
				)
			),
			'8'=>array(
				'name'	=>'筛选',
				'type'	=>'Location',
				'value'	=>array(
					'0'=>array(
						'name'	=> '现货',
						'value'	=> '现货'
					),
					'1'=>array(
						'name'	=> '订货',
						'value'	=> '订货'
					)
				)
			),
            '9'=>array(
                'name' => '奶咖',
                'type' => 'MilkCoffee',
                'value'	=>array(
                    '0'=>array(
                        'name'	=> '无奶',
                        'value'	=> 'NOMILK'
                    ),
                    '1'=>array(
                        'name'	=> '无咖',
                        'value'	=> 'NOCOFFEE'
                    )
                )
            ),
		);
		
		$this -> echo_data('100','获取成功',$data);
		
	}

}