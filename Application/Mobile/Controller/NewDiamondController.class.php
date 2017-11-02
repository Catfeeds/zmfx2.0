<?php
/**
* 裸钻查询控制器
* auth: zengmingming
* time: 2017/7/28/
**/
namespace Mobile\Controller;
class NewDiamondController extends NewMobileController{
	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= $_SESSION['m']['uid'];
	}
	
	/**
	 * auth	：zengmingming
	 * content：白钻查询
	 * time	：2017-08-03
	**/
    public function goodsDiy(){
		//查询戒托选择的钻重
		$dingzhi_arr 		= (array)json_decode($_COOKIE["dingzhi"]);
		$jietuo_info_arr 	= (array)$dingzhi_arr["jietuo_info"];
		$gal_id = $jietuo_info_arr["gal_id"];
		
		$keyword  = I('keyword','','trim');
		if(!empty($keyword)){
			$data = D("GoodsLuozuan")->where("(certificate_number like '%".$keyword."%' OR goods_name like '%".$keyword."%')")->limit(1)->select();
			if($data){
				if($data[0]['luozuan_type'] == 1){
					$this -> redirect("/Diamond/goodsDiyColor?goods_sn=$keyword");
				}
			}
		}
		//戒托信息
		$dingzhi = (array)json_decode($_COOKIE["dingzhi"]);
		$jietuoInfo = (array)$dingzhi["jietuo_info"];
       	$this->assign('gal_id',$gal_id);
       	$this->assign('dingzhi',$dingzhi);
       	$this->assign('jietuoInfo',$jietuoInfo);
		$this->assign('goodsSn',$keyword);
		$this->assign('cartNumber', $this->cartNumber);
		$this->display();
	}
	
	/**
	 * auth	：zengmingming
	 * content：彩钻查询
	 * time	：2017-08-03
	**/
    public function goodsDiyColor(){
		//查询戒托选择的钻重
		$dingzhi_arr 		= (array)json_decode($_COOKIE["dingzhi"]);
		$jietuo_info_arr 	= (array)$dingzhi_arr["jietuo_info"];
		$gal_id = $jietuo_info_arr["gal_id"];
       	$this->assign('gal_id',$gal_id);
		
		//p($this->getDiamondData()); 
		//存在搜索关键词的时候
		$goodsSn = I('goods_sn','');
        $this -> assign('goodsSn',$goodsSn);
		$this->assign('cartNumber', $this->cartNumber);		
		$this->display();
	}

	/**
	 * auth	：zengmingming
	 * content：钻石数据查询
	 * time	：2017-08-04
	**/
	public function getDiamondData(){
        $gal_id                    = I('gal_id','','intval');
		$page_id                   = I('page_id',1);
        $page_size                 = I('page_size',15);
		//传过来的字符串，需转化成数组
		$where['shape']			   = explode(",",I('Shape','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Shape','','htmlspecialchars'));
        $where['weight']		   = array(I('Weight','','htmlspecialchars'));
		$where['price']		   	   = array(I('Price','','htmlspecialchars'));
		$where['color'] 		   = explode(",",I('Color','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Color','','htmlspecialchars'));
        $where['symmetry']         = explode(",",I('Symmetry','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Symmetry','','htmlspecialchars'));
		$where['intensity'] 	   = explode(",",I('Intensity','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Intensity','','htmlspecialchars'));
		$where['clarity'] 		   = explode(",",I('Clarity','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Clarity','','htmlspecialchars'));
		$where['location'] 		   = explode(",",I('Location','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Location','','htmlspecialchars'));
		$where['cut'] 			   = explode(",",I('Cut','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Cut','','htmlspecialchars'));
		$where['fluor'] 		   = explode(",",I('Fluor','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Fluor','','htmlspecialchars'));
		$where['polish'] 		   = explode(",",I('Polish','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Polish','','htmlspecialchars'));
		$where['certificate_type'] = explode(",",I('Cert','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Cert','','htmlspecialchars'));
		$where['milk'] 	   		   = explode(",",I('Milk','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Milk','','htmlspecialchars'));
		$where['coffee'] 	   	   = explode(",",I('Coffee','','htmlspecialchars'))==array(0=>'')?array():explode(",",I('Coffee','','htmlspecialchars'));
		$where['luozuan_type'][0]  = I('luozuan_type','0','intval');

		$goods_sn				   = I('goods_sn','','intval');
        $order                     = I('order','weight','trim')?I('order','weight','trim'):'weight';
		$desc                      = I('desc','ASC','trim')?I('desc','ASC','trim'):'DESC';
        $is_own					   = I('is_own',array());			//斯特曼模板取裸钻列表 为空：默认模板取列表 0：国内货，1：比利时货
		$preferential			   = I('preferential',array());		//斯特曼特惠钻石 1 ：特

		if($where['luozuan_type'][0] == 1){
			$sglM 	= new \Common\Model\GoodsLuozuanModel(1);
		}else{
			$sglM   =  D('GoodsLuozuan');
		}
		
		if($goods_sn){
            if($this->agent_id!=2){
                $goods_where['certificate_number'] = array('like','%'.$goods_sn.'%');
            }
			$goods_where['goods_name']		   = array('like','%'.$goods_sn.'%');
			$goods_where['_logic']             = 'or';
			$goods_sn_where['_complex']        = $goods_where;
			$goods_sn_where['goods_number']    = array('gt',0);

			if($is_own[0] == '' ){
				$data                   = $sglM -> getLuozuanList($goods_sn_where,"$order $desc",$page_id,$page_size,$this->agent_id);
			}else{
				if($preferential[0] != 1){
					//去掉特惠钻石
					$preferentialID	= D("GoodsLuozuan")->getPreferentialID(C('agent_id'));
					$PreferentialID_string	= '';
					if($preferentialID){
						foreach($preferentialID as $val){
							$PreferentialID_string	.= $val['gid'].',';
						}
						$where['gid']	= array('not in',$PreferentialID_string);
					}
				}

				if($is_own[0] == 0 ){	//国内货
					$data				= $sglM -> getLuozuanListNew($goods_sn_where,"$order $desc",$page_id,$page_size,true,$preferential[0],$this->uid);
				}else{					//比利时货
					$data               = $sglM -> getLuozuanListNew($goods_sn_where,"$order $desc",$page_id,$page_size,false,$preferential[0],$this->uid);
				}

				foreach($data['list'] as $key=>$val){
					$point = 0;
					if($data['list'][$key]['agent_id'] != C('agent_id')){
						if($data['list'][$key]['luozuan_type'] == 1){
							$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
						}else{
							$point = D("GoodsLuozuan") -> setLuoZuanPoint();
						}
					}

					$data['list'][$key]['dia_discount'] += $point;
				}

			}

			$data['list']                      = getGoodsListPrice($data['list'],$_SESSION['m']['uid'],'luozuan');
			$this -> echoJson($data);
			exit;
		}

        $range                       = array(
            'weight',
            'price',
        );
		$range2                      = array(
            'color',
            'clarity',
        );

		foreach($where as $key=>$row){

            if( !is_array($row) ){
                $this->echoJson(null,400,'参数错误');
                die;
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
                    $uid            = $_SESSION['m']['uid'];
                    if($uid && $preferential[0] != 1){         //如有登录，则获取会员折扣
						if( $where['luozuan_type'][0] == 1 ){
							$userdiscount = getUserDiscount($uid, 'caizuan_discount');
						}else{
							$userdiscount = getUserDiscount($uid, 'luozuan_discount');
						}

                    }else{
                        $userdiscount     = 0;
                    }
					$point                =  0;
					if($where['luozuan_type'][0] == 1){
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
                    foreach( $row as $r ){
                        if( strpos($r,'-') !== false ){
                            $d      = explode('-',$r);
                            $args[] = array('between',array($d[0],$d[1]));
                        } else {
                            $args[] = array('egt',$r);
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
				foreach( $row as $r ){ //如果为订单来源，则查询quanxiang子段，unset掉原本的location；
					if($r == '订货'){
						$where['quxiang'] = array(array('like','%定货%'),array('like','%订货%'),array('like','%国外%'),'or');
					}else if($r == 'SDG'){
						$where['type']    = 1 ;
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
		$where['goods_number']   = array('gt',0);
        if( !empty($gal_id) ){
            $info                = M('goods_associate_luozuan') -> where( ' gal_id = ' . $gal_id ) -> find();
            $shape               = $this -> getShapeNoDiamonds($info['shape_id']);
            if($shape){
                $where['shape']  = array('in',$shape);
				if($info['weights_name']){
					$min_number   	 = substr(sprintf("%.2f", $info['weights_name']),0,-1)-'0.05';
					$max_number   	 = $min_number+'0.1';
					$where['weight'] = array(array('egt',$min_number),array('lt',$max_number));
				}
            }
        }

		if($is_own[0] == '' ){
			$data                   = $sglM -> getLuozuanList($where,"$order $desc",$page_id,$page_size,$this->agent_id);
		}else{
			if($preferential[0] != 1){
				//去掉特惠钻石
				$preferentialID	= D("GoodsLuozuan")->getPreferentialID(C('agent_id'));
				$PreferentialID_string	= '';
				if($preferentialID){
					foreach($preferentialID as $val){
						$PreferentialID_string	.= $val['gid'].',';
					}
					$where['gid']	= array('not in',$PreferentialID_string);
				}
			}
			if($is_own[0] == 0 ){	//国内货
				$data				= $sglM -> getLuozuanListNew($where,"$order $desc",$page_id,$page_size,true,$preferential[0],$this->uid);
			}else{					//比利时货
				$data               = $sglM -> getLuozuanListNew($where,"$order $desc",$page_id,$page_size,false,$preferential[0],$this->uid);
			}

			foreach($data['list'] as $key=>$val){
				$point = 0;
				if($data['list'][$key]['agent_id'] != C('agent_id')){
					if($data['list'][$key]['luozuan_type'] == 1){
						$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
					}else{
						$point = D("GoodsLuozuan") -> setLuoZuanPoint();
					}
				}

				$data['list'][$key]['dia_discount'] += $point;
			}

		}
		if(!empty($data['list']) && $_SESSION['m']['uid']>0){
			$HM = M('collection');
			$collection_where = array(
				'uid'=>$_SESSION['m']['uid'],
				'agent_id'=>C('agent_id')
			);
			foreach($data['list'] as $keyer => $valuer){
				$collection_where['goods_id'] = $valuer['gid'];
				$collection_where['goods_type'] = $valuer['luozuan_type'] ? 1 : 0;
				$collection_info = $HM->field('id')->where($collection_where)->find();
				if($collection_info){
					$data['list'][$keyer]['collection_id'] = $collection_info['id'];
				}
			}
		}
		$data['list']            = getGoodsListPrice($data['list'],$_SESSION['m']['uid'],'luozuan');
		$result = array('success'=>true, 'msg'=>'获取成功！', 'data'=>$data);	
		$this->ajaxReturn($result);
	}
	/**
	 * auth	：zengmingming
	 * content：钻石数据查询
	 * time	：2017-08-07
	**/
	public function getCertInfo(){
		$gid  = I('gid',0);
        $sglM = M('goods_luozuan');
        $data = $sglM
					-> field('zm_goods_preferential.id as preferential_id,zm_goods_preferential.discount as pre_discount,zm_goods_luozuan.*,zm_collection.id')
					-> where(" zm_goods_luozuan.gid = $gid ")
					-> join(' left join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid ')
					-> join(' left join zm_collection on zm_goods_luozuan.gid = zm_collection.goods_id ')
					-> find();
		$point = 0;
		if($data['agent_id'] != C('agent_id')){
			if($data['luozuan_type'] == 1){
				$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
			}else{
				$point = D("GoodsLuozuan") -> setLuoZuanPoint();
			}
		}
		$data['dia_discount'] += $point;
        $list = getGoodsListPrice(array(0=>$data ),$_SESSION['m']['uid'],'luozuan');
        $data = $list[0];

		$data['site_name']		= C('site_name');
		$data['site_contact']	= C('site_contact');
		$data['company_address']= C('company_address');
		$result = array('success'=>true, 'msg'=>'获取成功！', 'data'=>$data);	
		$this->ajaxReturn($result);
	}
	/**
	 * auth	：zengmingming
	 * content：钻石详情
	 * time	：2017-08-09
	**/
    public function getDiamondInfo(){
        $gid  = I('gid',0);
        $sglM = M('goods_luozuan');
        $data = $sglM
					-> field('zm_goods_preferential.id as preferential_id,zm_goods_preferential.discount as pre_discount,zm_goods_luozuan.*')
					-> where(" zm_goods_luozuan.gid = $gid ")
					-> join(' left join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid ')
					-> find();
		$point = 0;
		if($data['agent_id'] != C('agent_id')){
			if($data['luozuan_type'] == 1){
				$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
			}else{
				$point = D("GoodsLuozuan") -> setLuoZuanPoint();
			}
		}
		$data['dia_discount'] += $point;
        $list = getGoodsListPrice(array(0=>$data ),$_SESSION['m']['uid'],'luozuan');
        $data = $list[0];

		$data['site_name']		= C('site_name');
		$data['site_contact']	= C('site_contact');
		$data['company_address']= C('company_address');

		$result = array('success'=>true, 'msg'=>'获取成功！', 'data'=>$data);	
		$this->ajaxReturn($result);
    }
	
	/*获取钻石编号对应的型号 */
    public function getShapeNoDiamonds($shapeNo){
        $shape = '';	//默认（标识非常规异形钻）
        if($shapeNo=='001'){$shape='ROUND';}
        if($shapeNo=='002'){$shape='OVAL';}
        if($shapeNo=='003'){$shape='MARQUIS';}
        if($shapeNo=='004'){$shape='HEART';}
        if($shapeNo=='005'){$shape='PEAR';}
        if($shapeNo=='006'){$shape='PRINCESS';}
        if($shapeNo=='007'){$shape='EMERALD';}
        if($shapeNo=='008'){$shape='CUSHION';}
        if($shapeNo=='010'){$shape='RADIANT';}
        if($shapeNo=='011'){$shape='BAGUETTE';}
        if($shapeNo=='009'){$shape='TRILLIANT';}
        return $shape;
    }

}