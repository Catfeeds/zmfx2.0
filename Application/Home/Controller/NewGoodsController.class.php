<?php
/**
 * 产品相关页面
 * 产品类型 goods_type  1:裸钻 2:散货 3：成品 4:戒托 5:代销
 */
namespace Home\Controller;
use Think\Model;
class NewGoodsController extends NewHomeController{
	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= $_SESSION['web']['uid'];
	}

    public function getDiamondData(){

        $gal_id                    = I('gal_id','','intval');
        $page_id                   = I('page_id',1);
        $page_size                 = I('page_size',50);
        $where['shape']            = I('Shape',array());
        $where['weight']           = I('Weight',array());
        $where['price']            = I('Price',array());
        $where['color']            = I('Color',array());
        $where['clarity']          = I('Clarity',array());
        $where['location']         = I('Location',array());
        $where['cut']              = I('Cut',array());
        $where['fluor']            = I('Fluor',array());
        $where['polish']           = I('Polish',array());
        $where['symmetry']         = I('Symmetry',array());
        $where['intensity'] 	   = I('Intensity',array());
		$where['polish']           = I('Polish',array());
		$where['certificate_type'] = I('Cert',array());
		$where['milk'] 	   		   = I('Milk',array());
		$where['coffee'] 	   	   = I('Coffee',array());

		$where['luozuan_type']	   = I('luozuan_type','0','intval');
		$goods_sn				   = I('goods_sn','','intval');
        $order                     = I('order','weight','trim')?I('order','weight','trim'):'weight';
		$desc                      = I('desc','ASC','trim')?I('desc','ASC','trim'):'DESC';
        $is_own					   = I('is_own',array());			//斯特曼模板取裸钻列表 为空：默认模板取列表 0：国内货，1：比利时货
		$preferential			   = I('preferential',array());		//斯特曼特惠钻石 1 ：特惠
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

			$data['list']                      = getGoodsListPrice($data['list'],$_SESSION['web']['uid'],'luozuan');
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
                    $uid            = $_SESSION['web']['uid'];
                    if($uid && $preferential[0] != 1){         //如有登录，则获取会员折扣
						if( $where['luozuan_type'] == 1 ){
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
            if(I('szzmzb_gal_id')>0){
            	$info = M('szzmzb_goods_associate_luozuan') -> where( ' associate_luozuan_id = ' . I('szzmzb_gal_id') ) -> find();
            	$info['shape_id'] = $info['luozuan_shape_id'];
            	$info['weights_name'] = $info['luozuan_size'];
            }else{
            	$info = M('goods_associate_luozuan') -> where( ' gal_id = ' . $gal_id ) -> find();
            }
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
		if(!empty($data['list']) && $_SESSION['web']['uid']>0){
			$HM = M('collection');
			$collection_where = array(
				'uid'=>$_SESSION['web']['uid'],
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
		$data['list']            = getGoodsListPrice($data['list'],$_SESSION['web']['uid'],'luozuan');
        $this                   -> echoJson($data);
    }

    public function getCategroyItem(){
        $id     = I('id',0,'intval');
        if( $id > 0 ){
			//zhy 2016年10月21日 14:50:04 分类修改。
			$data =  D('Common/GoodsCategory') -> this_product_category($id);
        }else{
            $data = null;
        }
        if(empty($data)){
            $data = array();
        }
        $this     -> echoJson($data);
    }

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
        $list = getGoodsListPrice(array(0=>$data ),$_SESSION['web']['uid'],'luozuan');
        $data = $list[0];

		$data['site_name']		= C('site_name');
		$data['site_contact']	= C('site_contact');
		$data['company_address']= C('company_address');

        $this -> echoJson($data);
    }

    // 白钻查询
    public function goodsDiy(){
		/* 钻石定制广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 14;
		$customAdList		= $TA->where($where)->order('sort ASC')->select();

		$goods_sn = I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);
		$this -> assign('customAdList',$customAdList);
		if($this->templateSetting['diamonds_template_show'] == 1){
			$sglM   =  D('GoodsLuozuan');
			//去掉特惠钻石
			$preferentialID	= $sglM->getPreferentialID(C('agent_id'));
			$PreferentialID_string	= '';
			if($preferentialID){
				foreach($preferentialID as $val){
					$PreferentialID_string	.= $val['gid'].',';
				}
				$where_count['gid']	= array('not in',$PreferentialID_string);
			}

			$where_count['luozuan_type']	= 0;
			$where_count['goods_number']	= array('gt',0);
			$domestic_count	= $sglM -> get_count($where_count, true);	//获取国内钻石总数
			$belgium_count	= $sglM -> get_count($where_count, false);	//获取比利时钻石总数

			if($this->uid){
				$Compare	= new \Common\Model\Compare();
				$compare_count		=	$Compare->get_count($this->uid);
			}else{
				$compare_count	= 0;
			}


			$this->domestic_count	= $domestic_count;
			$this->belgium_count	= $belgium_count;
			$this->compare_count	= $compare_count;
			$this -> display('new_goodsDiy');
		}else{
			$this -> display('goodsDiy');
		}

    }
	// 彩钻查询
    public function goodsDiyColor(){
		/* 钻石定制广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 14;
		$customAdList		= $TA->where($where)->order('sort ASC')->select();

        $goods_sn = I('goods_sn','');
        $this -> assign('goods_sn',$goods_sn);
		$this -> assign('customAdList',$customAdList);
        $this -> display();
    }

	//裸钻详情
	public function diamondDetails(){
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
        $list = getGoodsListPrice(array(0=>$data ),$_SESSION['web']['uid'],'luozuan');
        $data = $list[0];

		$data['site_name']		= C('site_name');
		$data['site_contact']	= C('site_contact');
		$data['company_address']= C('company_address');
		$data['collection_id'] = '0';
		$data['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/shoucang.png';
		if($_SESSION['web']['uid']>0){
			$collection_where = array(
					'uid'=>$_SESSION['web']['uid'],
					'goods_id'=>$gid,
					'goods_type'=>$data['luozuan_type'] ? 1 : 0,
					'agent_id'=>C('agent_id')
			);
			$goodsCollection = M('collection')->where($collection_where)->find();
			if(!empty($goodsCollection)){
				$data['collection_id'] = $goodsCollection['id'];
				$data['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
			}
		}


		$this->diamond_info	= $data;
		$this->display();
	}

	//裸钻对比页面
	public function compare(){
		$compare	= M('goods_compare');
		$gid_array	= $compare->field('gid')->where(array('agent_id'=>C('agent_id'),'uid'=>$this->uid))->select();

		if(!empty($gid_array) && is_array($gid_array)){
            $gid_array	= array_column($gid_array,'gid');
            $where['zm_goods_compare.gid']	= array('in',$gid_array);
            $where['zm_goods_compare.uid']	= $this->uid;
            $where['zm_goods_luozuan.goods_number']   = array('gt',0);
            $data		= M('goods_luozuan')
                ->field('zm_goods_luozuan.*,zm_goods_preferential.id as preferential_id ,zm_goods_preferential.discount as pre_discount,zm_goods_compare.id as compare_id')
                ->where($where)
                ->join(' left join zm_goods_compare on zm_goods_luozuan.gid = zm_goods_compare.gid ')
                ->join(' left join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid ')
                ->order(' zm_goods_compare.id DESC ')
                ->select();

            if($data){
                foreach($data as $key=>$val){
                    $point = 0;
                    if($data[$key]['agent_id'] != C('agent_id')){
                        if($data[$key]['luozuan_type'] == 1){
                            $point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
                        }else{
                            $point = D("GoodsLuozuan") -> setLuoZuanPoint();
                        }
                    }

                    $data[$key]['dia_discount'] += $point;
                }
                $list = getGoodsListPrice($data,$_SESSION['web']['uid'],'luozuan');

                $this->compare_list	= $list;
                $this->display();
            }else{

                $this->error('没有对比裸钻！',U('/Home/Goods/goodsDiy'));
            }
        }else{
            $this->error('没有对比裸钻！',U('/Home/Goods/goodsDiy'));
        }
	}

	//裸钻加入对比
	public function compare_add(){
		if(IS_AJAX){
			$compare	= M('goods_compare');
			$gid	= I('post.gid','','intval');
			$type	= I('post.type','','intval');
			$uid	= $this->uid;

			if(empty($uid)){
				$result['msg'] 	= '请先登录再添加！';
				$result['code'] = 0;
				$result['url']	= U('/Home/Public/login');
				$this->ajaxReturn($result);
			}
			$CompareModel	= new \Common\Model\Compare();
			$where['uid']	= $this->uid;
			$count	= $CompareModel->get_count($uid);
			if($type == 1){					//type 1: 追加  2：取消
				if(($count+1) > C('compare_max_number')){
					$result['msg'] = '超过最大对比数量！';
					$result['code'] = 0;
					$this->ajaxReturn($result);
				}
				$where['gid']	= $gid;
				$data	= M('goods_luozuan')->field('gid,supply_gid as supply_goods_id,zm_gid as zm_goods_id ')->where($where)->find();
				if($data['supply_goods_id'] == '' ){
					$data['supply_goods_id'] = 0;
				}
				$data['create_time']	= date('Y-m-d H:i:s');
				$data['agent_id']		= C('agent_id');
				$data['type']			= 0;
				$data['uid']			= $uid;
				$check	= $compare->where(array('gid'=>$gid,'agent_id'=>C('agent_id'),'type'=>0,'uid'=>$uid))->find();
				if(!$check){
					$action = $compare->add($data);
					$count += 1;
				}

				$result['msg'] = '添加成功';


			}else{
				$check	= $compare->where(array('gid'=>$gid,'agent_id'=>C('agent_id'),'type'=>0))->find();
				if(!$check){
					$result['msg'] = '对比裸钻不存在！';
					$result['code'] = 0;
					$this->ajaxReturn($result);
				}
				$action = $compare->where(array('gid'=>$gid,'agent_id'=>C('agent_id'),'type'=>0,'uid'=>$uid))->delete();

				$result['msg'] = '取消成功';
				$count = $count-1;
			}

			$result['count'] = $count;

			$result['code'] = 100;

			$this->ajaxReturn($result);
		}

	}

	/**
	 * 产品可查讯的属性
	 * @param int $attributes
	 */
	public function attributes(){

		$goods_type  = I('goods_type', 3,'intval');
		$category_id = I('category_id',0,'intval');

		$productM    = D('GoodsCategory');
        if(!empty($category_id)){
            $productM     -> category_id($category_id);
            $category_info = $productM  -> getInfo();
            if(empty($category_info['pid'])){
                $productM               -> parent_category_id($category_id);
                $category_id_itemarray   =  $productM -> getChildUserGoodsCategoryList();
                if($category_id_itemarray){
                    $category_id_itemstr = implode(',', $category_id_itemarray);
                    $category_id         = $category_id_itemstr;
                } else {
                    $category_id = $category_id;
                }
            } else {
                $category_id     = $category_id;
            }
        } else {
            $productM              -> parent_category_id('0');
            $category_id_itemarray  = $productM -> getChildUserGoodsCategoryList();
            if($category_id_itemarray){
                $category_id_itemstr     = implode(',', $category_id_itemarray);
                $category_id             = $category_id_itemstr;
                $productM               -> parent_category_id($category_id);
                $category_id_itemarray   = $productM -> getChildUserGoodsCategoryList();
                if($category_id_itemarray){
                    $category_id_itemstr = implode(',', $category_id_itemarray);
                    $category_id         = $category_id_itemstr;
                }
            } else {
                $category_id = $category_id;
            }
            $category_id_itemstr    = implode(',', $category_id_itemarray);
            $category_id            = $category_id_itemstr;
        }
        $type     = 1;
		if( $goods_type == 3 ){
			$type = 1;
		}elseif( $goods_type == 4 ){
			$type = 3;
		}
		//获取栏目已有属性
		$goods_category_attributes = M("goods_category_attributes")->where("category_id in ($category_id) and type = $type ")->group('attr_id')->getField('attr_id',true);


        if($goods_category_attributes){
			$goods_category_attributes_itemstr = implode(',',$goods_category_attributes);
		}else{
			$this -> echoJson(new \stdclass());
		}

		//哪些属性加入筛选
		$goods_attributes     = M("goods_attributes")  -> where('attr_id in ('.$goods_category_attributes_itemstr.') and is_filter = 1') -> getField('attr_id,attr_name',true);
		//判断goods_attrcontorl有没有值
		$attrcontorl          = M("goods_attrcontorl") -> where('attr_id in ('.$goods_category_attributes_itemstr.') and agent_id = '.C('agent_id')) -> select();
		if($attrcontorl){
			$goods_attributes = M("goods_attrcontorl") -> join('join zm_goods_attributes on zm_goods_attributes.attr_id = zm_goods_attrcontorl.attr_id')
				-> where('zm_goods_attrcontorl.attr_id in ('.$goods_category_attributes_itemstr.')  and zm_goods_attrcontorl.is_filter = 1 and zm_goods_attrcontorl.agent_id = '.C('agent_id'))
				-> field('zm_goods_attributes.*') -> select();
		} else {
            foreach($goods_attributes as $key=>$row){
                $data = array();
                $data['attr_id']        = $key;
                $data['attr_name']      = $row;
				$goods_attributes[$key] = $data;
                unset($data);
			}
        }

		if($goods_attributes){
			//取出参与排序属性的值
			$attr             = array();
			foreach($goods_attributes as $row){
				$attr[]       = intval($row['attr_id']);
			}
			$attr_itemstr     = implode(',',$attr);
			$attr_value_array = M("goods_attributes_value") -> where('attr_id in ('.$attr_itemstr.') ') -> select();
			$attr_value       = array();
			foreach($attr_value_array as $row){
				unset($row['agent_id']);
				$attr_value[$row['attr_id']][] = $row;
			}
		}else{
			$this -> echoJson(new \stdclass());
		}

		foreach($goods_attributes as &$row){
			if($attr_value[$row['attr_id']]){
				$row['value'] = $attr_value[$row['attr_id']];
			}
		}
		$this -> echoJson($goods_attributes);
	}

    public function goodsCategory(){
		/* 钻石定制广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 14;
		$customAdList		= $TA->where($where)->order('sort ASC')->select();

        $category_id = I('gcid',0,'intval');
        $goods_type  = 4;
		//zhy 2016年10月21日 14:50:04 分类修改。
		$c_list =  D('Common/GoodsCategory') -> this_product_category($category_id);
		$this        -> assign('c_list',$c_list);
		$this        -> assign('customAdList',$customAdList);
		$this->assign('category_id',$category_id);
		$this->assign('goods_type',$goods_type);
        $this->assign('agent_id',C('agent_id'));
        $this->display();
    }

    public function goodsCat(){
		$goods_series_id = I('goods_series_id',0,'intval');
        $category_id = I('cid',0,'intval');
        $goods_type  = 3;
        $obj         = M('goods_series');
        $data        = $obj -> where('agent_id = '.C('agent_id')) -> select();
        $this->assign('goods_series_list',$data);
		$this->assign('category_id',$category_id);
		$this->assign('goods_type',$goods_type);
        $this->assign('goods_series_id',$goods_series_id);
        $this->display();
    }

    public function goodsList(){
		$category_id  = I('cid',0,'intval');
        $goods_type   = I('goods_type',3,'intval');
		$keyword      = I('keyword','','trim');
        $Preferential = I('Preferential','','trim');
		if(!empty($keyword)){
			$data = D("GoodsLuozuan")->where("(certificate_number like '%".$keyword."%' OR goods_name like '%".$keyword."%')")->limit(1)->select();
			if($data){
				if($data[0]['luozuan_type'] == 1){
					$this -> redirect("/Home/Goods/goodsDiyColor?goods_sn=$keyword");
				}else{
					$this -> redirect("/Home/Goods/goodsDiy?goods_sn=$keyword");
				}
			}
            //当有查询条件的时候，此页面是查询界面，那么不区分产品类别，统一显示
            $goods_type = '0';
		}
		//zhy 2016年10月21日 14:50:04 分类修改。
		$c_list =  D('Common/GoodsCategory') -> this_product_category($category_id);
        $this   -> assign('c_list',$c_list);
        $this   -> assign('category_id',$category_id);
        $this   -> assign('goods_type',$goods_type);
        $this   -> assign('keyword',$keyword);
        $this   -> assign('Preferential',$Preferential);
        $this   -> display();
    }

	//商城热卖
	public function hotGoodsList(){
		$category_id	= I('cid',0,'intval');
		$goods_type		= 0;

		//zhy 2016年10月21日 14:50:04 分类修改。
		$c_list =  D('Common/GoodsCategory') -> this_product_category($category_id);	//获取一级分类展示在页面

        $this   -> assign('c_list',$c_list);
		$this 	-> assign('goods_type',$goods_type);
        $this 	-> assign('category_id',$category_id);

        $this -> display();
    }


	/**
	 * 产品列表
	 * @param int $attributes
	 */
	public function goods_list(){

		$p                 = I('page_id',1,'intval');
		$per               = I('page_size',20,'intval');
		$order             = I('order','goods_id','trim');
		$desc              = I('desc','desc','trim');

		$goods_type        = I('goods_type',0,'intval');
		$category_id       = I('category_id',0,'intval');
		$seachr            = I('keyword','','trim');
        $goods_series_id   = I('goods_series_id','','trim');

        $shape             = I('shape','','trim');
        $weight            = I('weight','','trim');
        $Preferential      = I('Preferential','','trim');
		$price		  		= I('price','','trim');

		//规则:使用冒号，逗号，分号分别隔开多个属性和多个属性的值, 例如:attr_id:attr_value,attr_value,attr_value;attr_id:attr_value,attr_value...
		$goods_attr_filter = I('goods_attr_filter','','trim');
		$productM          = D('Common/Goods');

		//对属性查询进行处理
		if($goods_attr_filter) {
			$goods_attr_filter_join_array  = array();
			if( strpos($goods_attr_filter,';') !== false ){
				$goods_attr_filter_array   = explode(';',$goods_attr_filter);
			}else{
				$goods_attr_filter_array[] = $goods_attr_filter;
			}
			foreach( $goods_attr_filter_array as $key => $row ){
				if( strpos($row,':') === false ) {
					$this -> echoJson(new \stdclass());
				}
				$attr_value_array      = explode(':',$row);
				$on_attrcode           = array();
				if( strpos($attr_value_array[1],',') !== false ){
					$_attr_value_array = explode(',',$attr_value_array[1]);
					foreach($_attr_value_array as $r) {
						$on_attrcode[] = " aa$key.attr_code & $r ";
					}
				} else {
					$on_attrcode[]              = " aa$key.attr_code & $attr_value_array[1] ";
				}
				$on_attrcode_or                 = implode(' or ',$on_attrcode);
                $goods_attr_filter_join        .= " join zm_goods_associate_attributes aa$key on aa$key.attr_id = $attr_value_array[0] and ( $on_attrcode_or ) and aa$key.goods_id = zm_goods.goods_id ";
                if( $key > 0 ){
                    $key_jian = $key - 1 ;
                    $goods_attr_filter_join .= " and aa$key_jian.goods_id = aa$key.goods_id ";
                }
            }
			$productM -> _join ( $goods_attr_filter_join );
		}

        if($goods_type && !$Preferential){
            $productM  -> goods_type($goods_type);
        }


		if($category_id){
			$productM -> category_id($category_id);
		}

		if($seachr){
			$productM -> seachr($seachr);
		}

        if($goods_series_id){
            $productM -> set_where('zm_goods.goods_series_id',$goods_series_id);
        }

        if($Preferential){
            $productM -> set_where('ga.agent_id',C('agent_id'));
            $productM -> set_where('ga.product_status','1');
            $productM -> _join( ' left join zm_goods_activity as ga on ga.gid = zm_goods.goods_id and ga.agent_id = ' . C('agent_id') );
        }

        if( $shape || $weight ){
            $agent_id = $productM -> get_where_agent();

			if($weight){
				$min_number   = substr(sprintf("%.2f", $weight),0,-1);
				$max_number   = $min_number+'0.1';
				$weight_where = " weights_name < $max_number and  weights_name >=$min_number";
			}

            if( $shape && $weight ){
                $shape_id  = M('goods_luozuan_shape')->where(" shape = '$shape' ")->getField('shape_id');
                $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where $weight_where and shape_id = '$shape_id' and agent_id in ($agent_id) ) " , true );
            } else {
                if( $shape ){
                    $shape_id  = M('goods_luozuan_shape')->where(" shape = '$shape' ")->getField('shape_id');
                    $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where shape_id = '$shape_id' and agent_id in ($agent_id) )" , true );
                } else {
                    $productM -> set_where('zm_goods.goods_id'," in ( SELECT goods_id from zm_goods_associate_luozuan where $weight_where and agent_id in ($agent_id) )" , true );
                }
            }
        }

        // 如果系统设置关闭同步后台产品
        if(!C('is_sync_goods')){
            $productM->sql['where']['zm_goods.banfang_goods_id'] = 0;
        }

		if($price){
			$price		= explode('-',$price);
			$price[0]	= intval($price[0]);
			$price[1]	= intval($price[1]);
			$productM  -> set_where( 'zm_goods.goods_price', " between  $price[0] and $price[1] " , true );         //价格区间判断
		}

        $productM      -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
        $productM      -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架

		$productM      -> _order($order, $desc);
		$productM      -> _limit($p,$per);

        if($Preferential){
            $goods_list = $productM -> getList( false, false , "zm_goods.*, ga.product_status as activity_status, ga.setting_time, ga.home_show",false,true);
        }else{
            $goods_list = $productM -> getList();
        }

        if($goods_list){
            $goods_list = $productM -> getProductListAfterAddPoint($goods_list,$_SESSION['web']['uid']);
        }

		$data              = array();
		$data['page_size'] = $per;
		$data['page_id']   = $p;
		$data['data']      = $goods_list;
        $data['total']     = $productM -> count;
		$this             -> echoJson( $data );
	}

    /**
	 * 产品列表
	 * @param int $attributes
	 */
	public function goods_info(){

		$goods_id       = I('goods_id',1,'intval');
        $goods_type     = I('goods_type',3,'intval');
		$productM       = D('Common/Goods');
		$goods_info     = $productM -> get_info($goods_id,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
        $goods_list[]   = $goods_info;
        if($goods_list){
            $goods_list = $productM -> getProductListAfterAddPoint($goods_list,$_SESSION['web']['uid']);
        }
		$data           = $goods_list[0];
		$this          -> echoJson( $data );
	}

	//产品定制获取价格，根据主石，金重，工费，附石计算而来
	public function getGoodsAssociatePrice(){
		$gid           = I("goods_id",'0');              //商品id
		$materialId    = I("material_id",'0');           //金价
		$luozuanId     = I("diamond_id",'0');            //裸钻id
		$gadId         = I("gad_id");                    //副石id
        $M             = D('Common/Goods');
		$info                = $M -> get_info( $gid ,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ' );   //计算产品加价点数
        list($material,$associateInfo,$luozuan,$deputystone) = $M -> getJingGongShiData($gid,$materialId,$luozuanId,$gadId);
        $info['goods_price'] = $M -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
        $product             = getGoodsListPrice($info,$_SESSION['web']['uid'],'consignment','single');
		$data['price']       = $info['goods_price'];
		$this -> echoJson($data);
	}

	public function addUnit($value, $id){
        switch($id){
            case 18:
                $unit = 'G';break;
            case 22:
                $unit = 'CT';break;
            case 27:
                $unit = '粒';break;
            case 28:
                $unit = 'CT';break;
        }
        if(strpos($value, ',')===false){
            if(!empty($value)){
            $result = $value.$unit;
        }else{
                $result = '';
            }
        }else{
            $array = explode(',', $value);
            foreach($array as $k => $v){
                if(!empty($v)){
                $array[$k] = $v.$unit;
                }else{
                    $array[$k] = $v;
                }
            }
            $result = implode(',', $array);
        }
        return $result;
    }

	public function getRecCategory($categoryId,$str, $type=3){
        $M= M("goods_category_config");
        $temp = $M->where("category_id='".$categoryId."' and agent_id = ".C('agent_id'))->find();
        if($temp){
            if($type == 3){
                 $temp_str = "&gt;<a href='/Home/Goods/goodsCat/cid/".$categoryId."'>".$temp['name_alias']."</a>".$str;
            }else{
                 $temp_str = "&gt;<a href='/Home/Goods/goodsCategory/gcid/".$categoryId."'>".$temp['name_alias']."</a>".$str;
            }
            if($temp['pid'] != 0){
                return $this->getRecCategory($temp['pid'],$temp_str, $type);
            }else{
                return $temp_str;
            }
        }else{
            return $str;
        }
    }

    public function adjustToShow(&$goodsInfo){
        foreach($goodsInfo['attributes'] as $k => $v){
            $attr_ids[] = $v['attr_id'];
        }
        $attr_ids = array_unique($attr_ids);    //获取所有属性id(不重复的id)
        foreach($attr_ids as $k1 => $v1){
            foreach($goodsInfo['attributes'] as $k2 => $v2){
                if($v2['attr_id'] == $v1){
                    $adjustGoodsAttributes[$v1] = $v2;
                    $adjustGoodsAttributes[$v1]['attr_value_name'] = '';
                }
            }
        }
        //某些多选属性的值要合并成一条记录在前端显示，如：材质颜色：白色 黄色  玫瑰金
        foreach($adjustGoodsAttributes as $k1 => $v1){
            foreach($goodsInfo['attributes'] as $k2 => $v2){
                if($v2['attr_id'] == $k1){  //如查attr_id相同，则$adjustGoodsAttributes的attr_value_name拼接上该值
                    $adjustGoodsAttributes[$k1]['attr_value_name'] .= $v2['attr_value_name'].' ';
                }
            }
        }

        //某些属性值要加上单位在前台显示，如：材质金重：10G,20G;
        foreach($adjustGoodsAttributes as $k => $v){
            if(in_array($v['attr_id'], array(18,22,27,28))){    // 18:  材质金重   22:主石大小     27:副石数量   28:副石大小
                $adjustGoodsAttributes[$k]['attr_value'] = $this->addUnit($v['attr_value'], $v['attr_id']);
            }
        }
        $goodsInfo['attributes'] = $adjustGoodsAttributes;
    }

	public function goodsDetails(){
		// check_dingzhi_gmac(I("gid"),'Home','Goods','goodsDetails');
		$gid        = I("gid",'0');
		$goods_type = I('goods_type','3');
		$M          = D('Common/Goods');
		$field		= 'g.*,ga.product_status as activity_status';
		$uid = $_SESSION['web']['uid'];
		$agent_id = C('agent_id');
		$session_id = session_id();
		$GH = D('Common/History');
		$expectedDeliveryTime = C('expected_delivery_time');

		//后台权限部分
		if (strpos($_SERVER['HTTP_REFERER'], '/Admin/Goods/productList') !== false) {
			$sell_status=' and sell_status in(0,1)' ;
		}else{
			$sell_status=' and sell_status = 1' ;
		}

        $product    = $M -> get_info($gid,0,'shop_agent_id= '.C('agent_id').$sell_status,'',$field);
		$goodsInfo  = $M -> getProductInfoAfterAddPoint($product,$_SESSION['web']['uid']);

		if(!$goodsInfo || $gid <0){
            $this -> error("您所访问的页面不存在");
        }
        $goodsInfo['productService'] = M("article") -> where(" is_show = 1 AND title = '产品服务' and agent_id = ".C('agent_id'))->find();
        $goodsInfo['payment']        = M("article") -> where(" is_show = 1 AND title = '支付配送' and agent_id = ".C('agent_id'))->find();
        //取出历史纪录
        /*$goodsHistorys               = M("history") -> field('zm_goods.*, ga.product_status as activity_status ')-> join(' left join zm_goods on zm_history.goods_id = zm_goods.goods_id  left join zm_goods_activity as ga on ga.gid = zm_history.goods_id and ga.agent_id = '.C('agent_id') ) -> where($this->userAgentKey."='".$this->userAgentValue."' and zm_history.goods_id <> '$gid'")->order('history_id desc')->limit('0,5')->select();

        foreach( $goodsHistorys as $key=>$row ){
            if( !empty($row['goods_id']) ){
                $point = $M -> calculationPoint($row['goods_id']);
                if( $point ){
                    $row['goods_price'] = ( 100 + $point ) / 100 * $row['goods_price'];
                }
                $goodsHistorys[$key]    = $M -> completeUrl($row['goods_id'],'goods',$row);
            }else{
                unset($goodsHistorys[$key]);
            }
        }
        $goodsHistorys             = $M -> getProductListAfterAddPoint($goodsHistorys,$_SESSION['web']['uid']);*/
		$history_conditions = array(
				'uid'        => $uid,
				'agent_id'   => $agent_id,
				'gid'        => $gid,
				'session_id' => $session_id,
				'limit'      =>'0,5'
		);
		$goodsHistorys = $GH->getHistoryLists($history_conditions);

        $this -> goodsHistorys     = $goodsHistorys['lists'];
        // 将浏览信息记录到  zm_history
        /*$data                      = array();
        $data['goods_id']          = $gid;
        $data['agent_id']          = C('agent_id');
        $data[$this->userAgentKey] = $this -> userAgentValue;
        $data['create_time']       = time();
        if(M("history")  -> where(" goods_id = $gid AND ".$this->userAgentKey." = '".$this->userAgentValue."' ")->find()){
            M("history") -> data($data) -> save();
        }else{
            M("history") -> data($data) -> add();
        }*/

		$history_conditions['goods_type'] = $goodsInfo['goods_type'] ? $goodsInfo['goods_type'] : 3;
		$history_add_return = $GH->addHistory($history_conditions);
        switch($goodsInfo['technology_level']){
            case 1:$goodsInfo['technology_level'] = "A";
            break;
            case 2:$goodsInfo['technology_level'] = "AA";
            break;
            case 3:$goodsInfo['technology_level'] = "AAA";
            break;
            case 4:$goodsInfo['technology_level'] = "AAAA";
            break;
        }

        if( $goodsInfo['goods_type'] == 3 ){
            $this -> cat_show = 1;
        } else {
            $this -> cat_show = 0;
        }
        $navstr                     = "<a href='/Home/Index/index'>首页</a>";
        $navstr                    .= $this -> getRecCategory($goodsInfo['category_id'],"", $goods_type);
        $goodsInfo['category_name'] = M('goods_category_config')->where(" category_id = '$goodsInfo[category_id]' and agent_id = ".C('agent_id'))->getField('name_alias');
        $this -> category_name      = M('goods_category')->where(" category_id = '$goodsInfo[category_id]' ")->getField('category_name');
        $this -> nav_cate  = $navstr;
        $this -> adjustToShow( $goodsInfo );//某些属性值要加上单位在前台显示，如：材质金重：10G,20;某些多选属性的值要合并成一条记录在前端显示，如：材质颜色：白色 黄色  玫瑰金
        $goodsInfo['is_show_hand'] = is_show_hand($goodsInfo['category_id'] , $goods_type);

        $goodsInfo['expected_delivery_time'] = (!empty($expectedDeliveryTime) && $expectedDeliveryTime > 0 && $expectedDeliveryTime <= 200) ? $expectedDeliveryTime : 15;

		//是否收藏
		$goodsInfo['collection_id'] = '0';
		$goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/shoucang.png';
		if($_SESSION['web']['uid']>0){
			$collection_where = array(
					'uid'=>$_SESSION['web']['uid'],
					'goods_id'=>$gid,
					'goods_type'=>$goods_type,
					'agent_id'=>C('agent_id')
			);
			$goodsCollection = M('collection')->where($collection_where)->find();
			if(!empty($goodsCollection)){
				$goodsInfo['collection_id'] = $goodsCollection['id'];
				$goodsInfo['collection_img'] = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
			}
		}
		//是否收藏
		$this -> goodsInfo = $goodsInfo;
        $this -> bigImg    = $goodsInfo['bigImg'];
        $this -> public_content = "";
        if( $goodsInfo['category_id'] && $goodsInfo['goods_type'] ){
            $agent_id = C('agent_id');
            $info     = M('goods_category_config') -> where(" category_id = $goodsInfo[category_id] and agent_id = $agent_id ") -> find();
            if($info){
                if( $goodsInfo['goods_type'] == '3' ){
                    $this -> public_content   = $info['public_goods_desc_chengpin_down'];
                }else if( $goodsInfo['goods_type'] == '4' ){
                    $this -> public_content   = $info['public_goods_desc_dingzhi_down'];
                }
            }
        }
        if( $goodsInfo['goods_type'] == '4' ){
            $S             = D('Common/SymbolDesign');
            $agent_id      = C('agent_id');
            $sd_list       = $S -> getList($agent_id);
            $hand = array();
            for($i=6;$i<=25;$i++){
                $hand[] = $i;
            }
            $this -> hand = $hand;
        }else{
            $sd_list       = '';
        }
		//视频列表
	    $this -> vdesList        = $M -> get_goods_videos($gid);
        $this -> sd_list   = $sd_list;

        $this -> display();

	}

    public function getAjaxComment(){

        $page_id   = I("page_id",1,'intval');
        $page_size = I("page_size",100,'intval');
        $gid       = I("gid",0,'intval');
        $where     = "goods_id=".$gid;
        if(empty($page_id)){
            $page_id   = 1;
        }
        $limit         = (($pageid-1)*$pagesize).','.$pagesize;
        $count         = M("order_eval") -> where($where) -> count();
        $data['data']  = M("order_eval") -> where($where) -> limit($limit)->select();
        $data['count'] = $count;
        $this          -> ajaxReturn($data);

    }


	/*
	*	导航条品牌系列链接跳转
	*	zhy
	*	2016年10月12日 14:15:32
	*/
	public function goodsSeries(){
        $gsid   = I("gsid",1,'intval');
		if(is_numeric($gsid))	$this->redirect('Goods/goodsCat', array('goods_series_id' => $gsid));
		else return false;
	}

	/**
	 * auth	：fangkai
	 * content：报价与设计
	 * time	：2016-12-12
	**/
	public function designQuotation(){
		/* 首页的轮播广告,钻石定制广告位 */
		$TA = M ('template_ad');
		$where['agent_id'] 	= C('agent_id');
		$where['status']	= 1;
		$where['ads_id']	= 46;
		$LuozuanAd			= $TA->where($where)->order('sort ASC')->limit('1')->select();
		$where['ads_id']	= 47;
		$ChengpinAd			= $TA->where($where)->order('sort ASC')->select();

		$GoodsCategory = D('Common/GoodsCategory');
		$field = 'cg.category_id,cg.category_name,cg.pid,cc.name_alias,cc.img,cc.agent_id,cc.sort_id';
		$category_where['cg.pid'] = 0;
		$category_where['cc.agent_id'] = C('agent_id');
		$join = 'left join zm_goods_category_config as cc on cg.category_id = cc.category_id AND  cc.is_show = 1 ';
		$sort = 'cc.sort_id ASC';
		$category_list = $GoodsCategory->getCategoryListTwo($field,$join,$category_where,$sort);	//查询出显示的一级分类

		$goodsAttributes= M('goods_attributes_value');
		$priceList	= $goodsAttributes->field('attr_id,attr_value_name,attr_code')->where(array('attr_id'=>31))->select();  //查询出价格标签

		$this->priceList	= $priceList;
		$this->category_list= $category_list;
		$this->LuozuanAd	= $LuozuanAd;
		$this->ChengpinAd	= $ChengpinAd;
		$this->display();
	}

	/**
	 * auth	：fangkai
	 * content：获取二级分类
	 * time	：2016-12-12
	**/
	public function getCategory(){
		if(IS_AJAX){
			$GoodsCategory = D('Common/GoodsCategory');
			$field = 'cg.category_id,cc.name_alias';
			$category_where['cg.pid'] = I('cid','','intval');
			$category_where['cc.agent_id'] = C('agent_id');
			$join = 'left join zm_goods_category_config as cc on cg.category_id = cc.category_id AND  cc.is_show = 1 ';
			$sort = 'cc.sort_id ASC';
			$category_list = $GoodsCategory->getCategoryListTwo($field,$join,$category_where,$sort);	//查询出显示的二级级分类
			$this->ajaxReturn($category_list);
		}
	}

    //报价列表
    function goodsOfferInfo(){
        $GOI = D('Common/GoodsOfferInfo');
        $setting_param = $GOI->getOfferParam();

		if($this->uid){
			$Compare		= new \Common\Model\Compare();
			$compare_count	= $Compare->get_count($this->uid);
		}else{
			$compare_count	= 0;
		}

		$this->compare_count	= $compare_count;

        $this->assign('setting_param',$setting_param);
        $this->display();
    }
    //报价列表ajax数据
    function ajaxGetOfferList(){
        $weight = I('post.weight');
        $GOI = D('Common/GoodsOfferInfo');
        $weight_type = I('post.weight_type');
        $weight_type_display = 0;
        $weight_type = $weight_type ? 1: 0;
        if(preg_match('/[-]+/',$weight)){
            //范围搜索只能按每克拉搜索价格
            $weight_type = 1;
            $weight_type_display = 1;
        }
        $param = $weight_type==1 ? array('type'=>'carat_arr') : array('type'=>'single_arr');
        $weight_has_ct = preg_match('/[ct]+/',$weight);
        $weight_name = $weight_has_ct ? $weight : $weight.'ct';
        $data = $GOI->getWeightOfferLists($weight,$param);
        //根据数据拼接html字符串
        $html = '';
        $html .= '<caption>钻重'.$weight_name.'</caption>';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
				$html .= '<col width="95">';
        $html .= '<thead><tr><th class="xiexian" width="120">';
        $html .='<table class="child-table"><thead><tr><th></th><th>净度</th></tr><tr><th>颜色</th><th></th></tr></thead></table></th>';
        foreach($data['title_top'] as $val_t){
            $html .= '<th>'.$val_t.'</th>';
        }
        $html .='</tr></thead><tbody class="td-data">';
        foreach($data['title_left'] as $val_l){
            $html .= '<tr><th>'.$val_l.'</th>';
            foreach($data['lists'][$val_l] as $val_sin){
                $func_pram = '\''.$val_sin['color'].'\',\''.$val_sin['clarity'].'\',\''.$val_sin['weight'].'\'';

                /*if($weight_type){
                    $func_pram .= ',\''.$val_sin['price'].'\'';
                }else{
                    $func_pram .= ',\''.$val_sin['add_price'].'\'';
                }*/
                $func_pram .= ',\''.$val_sin['add_price'].'\'';
                $html .='<td onclick="search_info('.$func_pram.')">'.$val_sin['add_price'].'</td>';
            }
            $html .= '</tr>';
        }
        $html .='</tbody>';
        $html .= '</tbody>';
        //根据数据拼接html字符串
        $this ->ajaxReturn(array('data'=>$html,'weight_type'=>$weight_type,'weight_type_display'=>$weight_type_display),'JSON');
    }

	public function getHistoryLists(){
		$uid = $_SESSION['web']['uid'];
		$goods_type = I('goods_type',-1);
		$agent_id = C('agent_id');
		$session_id = session_id();
		$GH = D('Common/History');
		$history_conditions = array(
				'uid'        => $uid,
				'agent_id'   => $agent_id,
				'session_id' => $session_id,
				'limit'      =>'0,100',
				'goods_type' => $goods_type
		);
		$goods_history = $GH->getMoreHistoryLists($history_conditions);
		$this->goods_history = $goods_history;
		$this->goods_type    = $goods_type;
		$this -> display();
	}

	public function getCollectionList(){
		$param = array(
				'uid'=>$_SESSION['web']['uid'],
				'agent_id'=>C('agent_id')
		);
		if(empty($param['uid'])){
			$this->redirect('Public/login');
		}
		$product               = D('Common/Collection')->getCollectionList($param);
		$this->product         = $product;
		$this->display();
	}





}
