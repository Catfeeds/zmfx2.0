<?php
namespace Admin\Controller;
use Think\Upload;
class ExcelController extends AdminController{
    private $ZMkey = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');


    //导出散货
    public function exportSanhuo($type,$goods_id=''){
        //头部标题
    	$dataTop   = array('散货所属地','散货类型','散货编号','库存重量','价格','特价','颜色','颜色百分比','分数',
        		'分数百分比','净度','净度百分比','切工','切工百分比',);
        //对应的数据
        $associate = array('location','type_name','goods_sn','goods_weight',
        		'goods_price','goods_price2','color','color_cc','weights','weights_cc','clarity','clarity_cc','cut','cut_cc',);
        //列宽
        $dataStyle = array('11','10','12','9','9','9','8','20','8','30','8','28','8','28');
        //给这些字段加上颜色
        $set_color_field = 'color_cc,weights_cc,clarity_cc,cut_cc';
        //实例化表
		$GS  = D('GoodsSanhuo');
        $GSC = M('goods_sanhuo_cc');
        if($type == 1){
        	//查询选中数据
        	$goods_id = htmlspecialchars($goods_id);
			$where    = array('goods_id'=>array('in',$goods_id));
            $res     = $GSobj->getLst($where,'zm_goods_sanhuo_type.sort',1,0);
        }elseif($type == 2){
        	//查询全部数据
			$res     = $GSobj->getLst(array(),'zm_goods_sanhuo_type.sort',1,0);
        }
        $data = $res['list'];
        $list = $this->_arrayIdToKey($data);
        foreach ($data as $key => $value) {
        	if($key){
        		$ids .= ','.$value['goods_id'];
        	}else{
        		$ids = $value['goods_id'];
        	}
        }
        $ccList = $GSC->where('goods_id in ('.$ids.')')->select();
        foreach ($ccList as $key => $value) {
        	if($value['cc_type'] == 1) $str = 'color_cc';
        	elseif($value['cc_type'] == 2) $str = 'clarity_cc';
        	elseif($value['cc_type'] == 3) $str = 'cut_cc';
        	elseif($value['cc_type'] == 4) $str = 'weights_cc';
        	$list[$value['goods_id']][$str] .= $value['cc_value'].':'.$value['cc_ku'].',';
        }
        foreach ($list as $key => $value) {
        	foreach ($associate as $k => $v) {
        		$arr[$v] = empty($value[$v])?'none':$value[$v];
        	}
        	$listS[] = $arr;
        }
        $this->exportFile($listS, $associate, $dataTop,$set_color_field,'',$dataStyle);
    }

    //导入散货
    public function importSanhuo(){
        if(IS_POST){
            $file     = $this->uploadsFile();
            $fileName = $file['savepath'].$file['savename'];
            $exts     = $file['ext'];
            //对应的数据
            $associate = array('location','type_name','goods_sn','goods_weight',
            		'goods_price','goods_price2','color','color_cc','weights','weights_cc','clarity','clarity_cc','cut','cut_cc',);
            $data = $this->readFile($fileName, $exts ,$associate);
            //实例化表
            $GS  = D('GoodsSanhuo');
            $GS  ->startTrans();
            $GSC = M('goods_sanhuo_cc');
            $GST = M('goods_sanhuo_type');
            $typeList1 = $GST->field('tid,type_name')->select();
            $typeList = $this->_arrayIdToKey($typeList1,'type_name');
            $false_str = '';
            foreach ($data as $key => $value) {
                if($value['goods_sn'] == '' or $value['goods_price']<=0){
                    continue;
                }
                if(is_null($typeList[$value['type_name']]['tid'])){
                    $false_str = $false_str . $value['goods_sn'].'类型错误<br>';
                    continue;
                }
                
            	$type_name = $value['type_name'];
                $dataS['location'] = $value['location'];
                $dataS['goods_sn'] = $value['goods_sn'];
                $value['type_name'] = trim($value['type_name']);
                $dataS['tid'] = $typeList[$value['type_name']]['tid'];
                $dataS['weights'] = $value['weights'];
                $dataS['clarity'] = $value['clarity'];
                $dataS['color'] = $value['color'];
                $dataS['cut'] = $value['cut'];
                if(empty($value['goods_weight'])) {$value['goods_weight'] = 0;}
                $dataS['goods_weight'] = $value['goods_weight'];
                $dataS['goods_price'] = $value['goods_price'];
                if(empty($value['goods_price2'])) {$value['goods_price2'] = 0;}
                $dataS['goods_price2'] = $value['goods_price2'];
                $dataS['goods_4c'] = '';
                $dataS['user_id'] = $this->uid;
                $dataS['goods_status'] = 1;
                $dataS['create_time'] = time();
                $dataS['update_time'] = time();
                $dataS['agent_id']    = C('agent_id');
                $res = $GS->add($dataS);
                if($res){
                    $ccData['agent_id'] = C('agent_id');
                    $ccData['goods_id'] = $res;
                    $cc = array('color_cc','clarity_cc','cut_cc','weights_cc');
                    foreach ($cc as $k => $v) {
                        $list = explode(',', $value[$v]);
                        if($v == 'color_cc') $str = '1';
                        elseif($v == 'clarity_cc') $str = '2';
                        elseif($v == 'cut_cc') $str = '3';
                        elseif($v == 'weights_cc') $str = '4';
                        $ccData['cc_type'] = $str;
                        foreach ($list as $kk => $vv) {
                            if($vv){
                                $arr = explode(':',$vv);
                                $ccData['cc_value'] = $arr[0];
                                $ccData['cc_ku'] = empty($arr[1])?0:$arr[1];
                            }
                            $ccDataS[] = $ccData;
                        }
                    }
                }else{
                    $GS->rollback();
                    $this->error('插入磁盘数据失败');
                }
            }
            $res = $GSC->addAll($ccDataS);
            if(!$res){
                $GS->rollback();
                $this->error('插入磁盘数据失败');
            }else{
                $GS->commit();
                if($false_str == ''){
                   
                    $this->success('导入散货成功', U('Admin/Goods/sanhuoList'));
                }else{
                    $false_str = '导入散货成功,但是'.$false_str;
                    $this->error($false_str, U('Admin/Goods/sanhuoList'));
                }
               
            }
        }else{
            $this->display();
        }
    }
    //导出珠宝产品
    public function exportGoods(){
    	$G   = M('goods');//产品表
    	$GAD = M($this->getGoodsTableName(7));//产品副石表
    	$GAI = M($this->getGoodsTableName(6));//产品关联材质信息表
    	$IMAGES = M($this->getGoodsTableName(2));//产品图片表
    	$GAL = M($this->getGoodsTableName(5));//主石工费表
    	foreach ($_POST['goods_id'] as $key => $value) {
    		if($key) $ids .= ','.$value;
    		else $ids = $value;
    	}
    	if($ids){
	    	$join    = 'LEFT JOIN zm_goods_material AS zgm ON zgm.material_id = gai.material_id';
	    	$joina[] = 'LEFT JOIN zm_goods_material AS zgm ON zgm.material_id =gal.material_id';
	    	$joina[] = 'LEFT JOIN zm_goods_luozuan_shape AS zgls ON zgls.shape_id = gal.shape_id';
	    	$joing[] = 'LEFT JOIN zm_goods_category AS zgc ON zgc.category_id = g.category_id';

	    	$list1 = $G->alias('g')->where("goods_id in($ids) and g.agent_id = ".C('agent_id'))->join($joing)->field('g.*,zgc.category_name')->select();

            $list= $this->_arrayIdToKey($list1);
	    	$listim = $IMAGES->alias('images')->where("goods_id in($ids) and agent_id = ".C('agent_id'))->select();
	    	$listfushi= $GAD->alias('gad')->where("goods_id in($ids) and agent_id = ".C('agent_id'))->select();
	    	$caizhi = $GAI->alias('gai')->where("goods_id in($ids) and gai.agent_id = ".C('agent_id'))->join($join)->field('zgm.material_name,gold_price,gai.*')->select();
	    	$gongfei= $GAL->alias('gal')->where("goods_id in($ids) and gal.agent_id = ".C('agent_id'))->join($joina)->field('zgls.shape_name,zgm.material_name,gold_price,gal.*')->select();
	    	foreach($listfushi as $key=>$value){
	    		$list[$value['goods_id']]['fushi'] = $value['deputystone_name'].':'.$value['deputystone_price'].';';
	    	}
	    	foreach($caizhi as $key=>$value){
	    		$list[$value['goods_id']]['caizhi'] = $value['material_name'].':'.$value['weights_name'].':'.$value['loss_name'].':'.$value['basic_cost'].',';
	    	}
	    	foreach($gongfei as $key=>$value){
	    		$list[$value['goods_id']]['gongfei'] = $value['material_name'].':'.$value['shape_name'].':'.$value['weights_name'].':'.$value['price'].',';
	    	}
	    	foreach($listim as $key=>$value){
	    		$img[$value['goods_id']][] = $value['big_path'];
	    		$list[$value['goods_id']]['img'] = array('fangshi'=>'img','data'=>$img[$value['goods_id']]);
	    	}

	    	$dataTop = array('产品编号','产品名称型','类别','产品类型','副石信息','产品材质信息','产品材质匹配主石镶嵌工费','产品价格','产品库存','图片');
	    	$associate = array('goods_sn','goods_name','category_name','goods_type','fushi','caizhi','gongfei','goods_price','goods_number','img');
	    	$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('你没有选中产品');
    	}
    }
    //导入珠宝产品
    public function importGoods(){
    	if(IS_POST){
    		$file = $this->uploadsFile();
            $fileName = $file['savepath'].$file['savename'];
            $exts = $file['ext'];
         	$associate = array('goods_sn','goods_name','category_name','goods_type','fushi','caizhi','gongfei','goods_price','goods_number','img');
            $data = $this->readFile($fileName, $exts ,$associate);
            $G = M('goods');//产品表
            $GAD = M($this->getGoodsTableName(7));//产品副石表
            $GAI = M($this->getGoodsTableName(6));//产品关联材质信息表
            $GAL = M($this->getGoodsTableName(5));//主石工费表
            $GC = M('goods_category');
            $CZ = M('goods_material');
            $SHAPE = M('goods_luozuan_shape');
            $gal = $GAL->alias('gal')->select();
         	$category_name=$data[0]['category_name'];
          	$gc =$GC->alias('gc')->where("category_name='$category_name'")->field('category_id')->find();
          	$category_id=$gc['category_id'];
            foreach($data as $key=>$value){
            	$date['goods_sn']=$value['goods_sn'];
            	$date['goods_name']=$value['goods_name'];
            	$date['category_id']=$category_id;
            	$date['goods_type']=$value['goods_type'];
            	$date['goods_price']=$value['goods_price'];
            	$date['goods_number']=$value['goods_number'];
            	$dateg[]=$date;
            }
            $res = $G->addAll($dateg);
            foreach($data as $k=>$v){
            	$arr=explode(',',$v['fushi']);
            	foreach($arr as $kf=>$vf){
            		$arrf=explode(':',$vf);
            		$dates['deputystone_name']=$arrf[0];
            		$dates['deputystone_price']=$arrf[1];
            	}
            	$datefushi[]=$dates;
            }
            $res1 = $GAD->addAll($datefushi);
            foreach($data as $kk=>$vv){
            	$arrc=explode(',',$vv['caizhi']);
            	foreach($arrc as $kc=>$vc){
            		$arrcc=explode(':',$vc);
            		$material_id=$arrcc[0];
            		$gai = $CZ->alias('cz')->where("material_name ='$material_id'")->field('material_id')->find();
	          		$datec['material_id']=$gai['material_id'];
	          		$datec['weights_name']=$arrcc[1];
	          		$datec['loss_name']=$arrcc[2];
	          		$datec['basic_cost']=$arrcc[3];
	          		$datecaizhi[]=$datec;
            	}
            }
            $res2 = $GAI->addAll($datefushi);
            foreach($data as $kkk=>$vvv){
            	$arrg=explode(',',$vv['gongfei']);
            	foreach($arrg as $kg=>$vg){
            		$arrgf=explode(':',$vg);
            		$material_id=$arrgf[0];
            		$shape_id=$arrgf[1];
            		$gai = $CZ->alias('cz')->where("material_name ='$material_id'")->field('material_id')->find();
            		$shape = $SHAPE->alias('shape')->where("shape_name = '$shape_id'")->field('shape_id')->find();
            		$dategf['material_id']=$material_id;
            		$dategf['shape_id']=$shape_id;
            		$dategf['weights_name']=$arrgf[2];
            		$dategf['price']=$arrgf[3];
            		$dategongfei[]=$dategf;
            	}


            }
            $res3 = $GAL->addAll($datefushi);
            if($res and $res1 and $res2 and $res3){
            	$this->success('导入散货成功',U('Admin/Goods/productList'));
            }else{
            	$this->error('插入磁盘数据失败');
            }
    	}else{
    		$this->display();
    	}
    }
    //导出应付款列表
    public function exportYingfu(){
    	$ZOR = M('order_receivables');
    	//$OR = M('order');
    	foreach ($_POST['yingfu'] as $key => $value) {
    		if($key) $ids .= ','.$value;
    		else $ids = $value;
    	}
    	if($ids){
    		$join  = 'LEFT JOIN zm_order AS o ON o.order_id = zor.order_id';
    		$list1 = $ZOR->alias('zor')->where("zor.agent_id = ".C('agent_id')."receivables_id in($ids)")->join($join)->field('zor.*,o.order_sn')->select();
    		$list  = $this->_arrayIdToKey($list1);
    		$dataTop = array('订单编号','类型','期数','应付金额','核销金额','状态');
    		$associate = array('order_sn','period_type','period_current','receivables_price','payment_price','receivables_status');
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error("您没有选择数据");
    	}
    }
    //导出付款列表
    public function exportFukuan(){
    	$dataTop = array('订单编号','付款金额','折扣金额','付款方式','状态');
    	$associate = array('order_sn','payment_price','discount_price','payment_mode','payment_status');
    	$OP = M('order_payment');
    	foreach ($_POST['fukuan'] as $key => $value) {
    		if($key) $ids .= ','.$value;
    		else $ids = $value;
    	}
    	if($ids){
    		$list1 = $OP->alias('op')->where("agent_id = ".C('agent_id')." and payment_id in ($ids)")->select();
    		$list = $this->_arrayIdToKey($list1);
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('您没有选择数据');
    	}
    }
    //导出裸钻
    public function exportluozuan(){
    	foreach ($_POST['luozuan'] as $key => $value) {
    		if($key) $ids .= ','.$value;
    		else $ids = $value;
    	}
    	if($ids){
    		$dataTop   = array('形状','钻石名称','证书编号','所在地','去向','重量','颜色','净度','切工','抛光','对称','咖色','奶色','全深比','大小','国际报价','折扣');
    		$associate = array('shape','goods_name','certificate_number','location','quxiang','weight','color','clarity','cut','polish','symmetry','coffee','milk','dia_depth','dia_size','dia_global_price','dia_discount');
    		$list1     = D('goodsLuozuan')->where('gid in ('.$ids.')')->select();
            $list      = $this->_arrayIdToKey($list1);
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('您没有选择数据');
    	}
    }
    //导出应收款列表
    public function exportYingshou(){
		if(empty($_POST['yingshou'])){
			$ids =$_POST['yingshou_null'];
		}else{
			foreach ($_POST['yingshou'] as $key => $value) {
				if($key) $ids .= ','.$value;
				else $ids = $value;
			}
		}
    	if($ids){
			//if(strlen($ids)>1130) $this->error('数据过大，请分条件选择打印，打印条数在230条以内。');
    		$OR = M('order_receivables');
    		$join = 'LEFT JOIN zm_order AS zo ON zo.order_id = zor.order_id';
    		$or = $OR->alias('zor')->where('zor.agent_id = '.C('agent_id').' and receivables_id in('.$ids.')')->join($join)->field('zor.*,zo.order_sn')->select();
			$list = $this->_arrayIdToKey($or);
    		foreach($list as $key=>$value){
    			if($value['period_type'] == 1){
    				$type = '证书货';
    			}elseif($value['period_type'] == 2){
    				$type = '散货';
    			}elseif($value['period_type'] == 3){
    				$type = '成品货';
    			}elseif($value['period_type'] == 4){
    				$type = '钻托定制';
    			}elseif($value['period_type'] == 5){
    				$type = '代销货(定制)';
    			}elseif($value['period_type'] == 6){
    				$type = '代销货(成品)';
    			}elseif($value['period_type'] == 11){
    				$type = '逾期';
    			}elseif($value['period_type'] == 12){
    				$type = '补差价';
    			}else{
    				$type = ' ';
    			}
    			if($value['receivables_status'] == 1){
    				$str ='未核销';
    			}elseif($value['receivables_status'] == 2){
    				$str ='核销部分';
    			}elseif($value['receivables_status'] == 3){
    				$str ='已核销';
    			}else{
    				$str =' ';
    			}
    			$list[$value['receivables_id']]['status'] = $str;
    			$list[$value['receivables_id']]['type'] = $type;
    			$list[$value['receivables_id']]['time']=date("Y-m-d H:i:s",$list[$value['receivables_id']]['payment_time']);
    		}
    		$dataTop = array('订单编号','应付类型','期数','应付金额','核销金额','支付时间','核销状态');
    		$associate = array('order_sn','type','period_current','receivables_price','payment_price','time','status');
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('您没有选择数据');
    	}
    }


	/*
	*	导出订单列表
	*	zhy
	*	2016年10月20日 10:56:30
	*/
    public function exportorderList(){
		if($_POST['orderList']){
			$ids =$_POST['orderList'];
		}else{
			$ids =$_POST['orderList_null'];
		}
		foreach ($ids as $key => $value) {
			if($key) $ids .= ','.$value;
			else $ids = $value;
		}
    	if(!empty($ids)){
			//if(strlen($ids)>1630) $this->error('数据过大，请分条件选择打印，打印条数在230条以内。');
			$field = 'o.*,u.realname,u.username,u.usernum,au.user_name';
			$where =' o.is_yiji_caigou < 1 and  o.order_id in ('.$ids.')';
			$join['user'] = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
			$join['admin_user'] = 'LEFT JOIN zm_admin_user AS au ON au.user_id = o.parent_id';
			//查询数据
			$or = M('order')->alias('o')->field($field)->where($where)->join($join)->order('create_time desc')->group('o.order_id')->select();
			$list = $this->_arrayIdToKey($or);
			//---已付价格
			$array =  M('order_receivables')->where(' agent_id = '.C('agent_id').' and order_id in('.$ids.') and payment_price > 0')->field('order_id,payment_price')->select();
			foreach ($array as $key => $value) {
				$arr[$value['order_id']]['payment_price'] += $value['payment_price'];
			}
			foreach ($list as $key => $value) {
				$list[$key]['payment_price'] = formatPrice($arr[$value['order_id']]['payment_price']);
			}
			//--
    		foreach($list as $key=>$value){
				if($value['order_status'] == 0 or $value['order_status'] == -1 or $value['order_status'] == -2){
					$list[$key]['order_price'] = $list[$key]['order_price'];
				}else{
					$list[$key]['order_price'] = $list[$key]['order_price_up'];
				}
				//订单对象，订单类型
				if($value['is_erji_caigou'] ==0){
					$list[$key]['order_type'] = '客户订单';
					$list[$key]['obj'] = empty($value['realname'])?$value['username']:$value['realname'];
				}else{
					$list[$key]['obj'] = $list[$key]['company_name'];
					$list[$key]['order_type'] = '采购单';
				}
				//订单状态检查
				if($value['order_status'] == 0){//确认状态判断
					$list[$key]['status'] = L('A01');
				}elseif($value['order_status'] == 1){//支付状态判断
					$list[$key]['status'] = L('A05');
				}elseif ($value['order_status'] == 2){//已支付，配货中
					if($value['uid']){//直批客户单
						$list[$key]['status'] .= L('A06');
					}else{//分销商客户单
						$list[$key]['status'] .= L('A08');
					}
				}elseif ($value['order_status'] == 3){//发货状态判断
					$list[$key]['status'] .= L('A010');
				}elseif ($value['order_status'] == 4){//已发货给客户，待确认
					$list[$key]['status'] .= L('A014');
				}elseif($value['order_status'] == 5){//已确认收货
					$list[$key]['status'] .= L('A015');
				}elseif($value['order_status'] == 6){//已完成订单
					$list[$key]['status'] .= L('A024');
				}elseif($value['order_status'] == -1){//已取消订单
					$list[$key]['status'] .= L('A022');
				}elseif($value['order_status'] == -2){//已删除订单
					$list[$key]['status'] .= L('A023');
				}elseif($value['order_status'] == 8){//已删除订单
					$list[$key]['status'] .= L('A023');
				}else{//其他错误状态
					$list[$key]['status'] = L('A038');
				}
    			$list[$key]['create_time']=date("Y-m-d H:i:s",$value['create_time']);
    		}
    		$dataTop = array('订单编号','订单类型','订单对象','下单时间','订单金额','已付金额','订单状态');
    		$associate = array('order_sn','order_type','obj','create_time','order_price','payment_price','status');
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('您没有选择数据');
    	}
    }

	
	/*
	*	导出产品列表
	*	zhy	  find404@foxmail.com
	*	2016年11月22日 11:45:58
	*/
	public function exportproductList(){
		$Goods                 = D('Common/Goods');
		if($_POST['productList']){
			$ids =$_POST['productList'][0];
			$ids = substr($ids,0,-1);
			$Goods->sql['where']['zm_goods.goods_id'] = array('in',$ids);			
		}else{
			$ids =session('productList_sql_where');
			foreach($ids as $k=>$v){
				$Goods->sql['where'][$k]=$v;
			}
		}

		$Goods -> my_table_name  = 'goods';//重要
		$Goods -> _join( ' left join zm_goods_shop as zgs on zgs.goods_id = zm_goods.goods_id and zgs.shop_agent_id = '.C('agent_id').session('productList_sql_where_home_show'));
		
		$field			= 'zm_goods.*, gs.home_show';
		$Goods			-> _join( ' left join zm_goods_home_show as gs on gs.gid = zm_goods.goods_id and gs.agent_id = '.C('agent_id').session('productList_sql_where_home_show'));
		$Goods          -> _order('goods_id','DESC');
		if($ids){
			$data =array();
	        $productList       = $Goods -> getList(false,true,$field,false,true);	
			if($productList){
				$this_productList= $Goods -> getProductListAfterAddPoint($productList,0);
				foreach ($this_productList as $k=>$v){
						$data[$k]['goods_id']			=  $v['goods_id']; 
						$data[$k]['goods_sn']			=  $v['goods_sn'];					
						$data[$k]['goods_name']			=  $v['goods_name'];		
						$data[$k]['goods_type']			=  ($v['goods_type']=='3') ? '珠宝成品' : '珠宝定制';		
						$data[$k]['goods_price']		=  $v['goods_price'];		
						$data[$k]['caigou_price']		=  $v['caigou_price'];		
						$data[$k]['goods_number']		=  $v['goods_number'];								
				}
				unset ($this_productList);
				unset ($productList);
				unset ($_POST);
				unset ($ids);
                                //判断是否有成本利益权限，是否显示产品的采购价
                        if(in_array('Admin/CostProfit/index',$this->AuthListS)){        
				$dataTop = array('产品ID','产品编号','产品名称','产品类别','销售价格','产品价格','产品库存');
				$associate = array('goods_id','goods_sn','goods_name','goods_type','goods_price','caigou_price','goods_number');
                        }else{
                                $dataTop = array('产品ID','产品编号','产品名称','产品类别','销售价格','产品库存');
				$associate = array('goods_id','goods_sn','goods_name','goods_type','goods_price','goods_number');
                        }	
                                $this->exportFile($data, $associate, $dataTop);
			}
		}else{
			$this->error('您没有选择数据');
		}
	}

	/*
	*	导出产品列表
	*	zhy	  find404@foxmail.com		
	*	2016年11月22日 11:45:58
	*/
	public function exportgoodsList(){
		if($_POST['goodsList']){
			$ids =$_POST['goodsList'][0];
			$ids = substr($ids,0,-1);
			$GoodsLuozuan = D('Common/GoodsLuozuan');
			$agent_id = $GoodsLuozuan->get_where_agent();			
			$where=" agent_id in ($agent_id)".' and gid in ('.$ids.')';
		}else{
			$where =unserialize(base64_decode($_POST['goodsList_null'][0]));
		}
		
		if($where){
			$dataList = array();
			$stopgap_dataList=array();
			$Goods       = M("goods_luozuan");
			$data  = $Goods->where( $where)->order('gid desc')->select();
			foreach($data as $key=>$val){
				$point = 0;
				if($val['agent_id'] != C('agent_id')){
					if($val['luozuan_type'] == 1){
						$point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
					}else{
						$point = D("GoodsLuozuan") -> setLuoZuanPoint();
					}
				}
				$val['dia_discount'] += $point;
                $stopgap_dataList[$key]       = getGoodsListPrice($val,0,'luozuan', 'single');
			}
			foreach ($stopgap_dataList as $key=>$v){
				$dataList[$key]['shape']=$v['shape'];
				$dataList[$key]['quxiang']=$v['quxiang'];
				$dataList[$key]['goods_name']=($v['luozuan_type']=='1') ? 	$v['goods_name'].'	(彩钻)' : $v['goods_name'];
				$dataList[$key]['certificate_type']=$v['certificate_type'];
				$dataList[$key]['certificate_number']=$v['certificate_number'];
				$dataList[$key]['weight']=$v['weight'];
				$dataList[$key]['color']=$v['color'];
				$dataList[$key]['clarity']=$v['clarity'];
				$dataList[$key]['cut']=$v['cut'];
				$dataList[$key]['polish']=$v['polish'];
				$dataList[$key]['symmetry']=$v['symmetry'];
				$dataList[$key]['fluor']=$v['fluor'];
				if($v['luozuan_type']=='1' && ($v['agent_id'] != C('agent_id'))){
				$dataList[$key]['dia_global_price']='';
				$dataList[$key]['dia_discount_all']='';
				}else{
				$dataList[$key]['dia_global_price']=$v['dia_global_price'];
				$dataList[$key]['dia_discount_all']=$v['dia_discount_all'];				
				}
				$dataList[$key]['xiaoshou_price']=$v['xiaoshou_price'];
				$dataList[$key]['caigou_price']=$v['caigou_price'];
				$dataList[$key]['location']=$v['location'];
			}
				unset ($stopgap_dataList);
				unset ($data);
				unset ($_POST);
				unset ($ids);
                //判断是否有成本利益权限，是否显示产品的采购价                
                if(in_array('Admin/CostProfit/index',$this->AuthListS)){
				$dataTop = array('形状','去向','货品编号','证书','证书号','重量','颜色','净度','切工','抛光','对称','荧光','国际报价','销售折扣','销售单价','采购单价','产地');
				$associate = array('shape','quxiang','goods_name','certificate_type','certificate_number','weight','color','clarity','cut','polish','symmetry','fluor','dia_global_price','dia_discount_all','xiaoshou_price','caigou_price','location');
                }else{
                               	$dataTop = array('形状','去向','货品编号','证书','证书号','重量','颜色','净度','切工','抛光','对称','荧光','国际报价','销售折扣','销售单价','产地');
                                $associate = array('shape','quxiang','goods_name','certificate_type','certificate_number','weight','color','clarity','cut','polish','symmetry','fluor','dia_global_price','dia_discount_all','xiaoshou_price','location');
                }
                                $this->exportFile($dataList, $associate, $dataTop);
			
	
		}else{
			$this->error('您没有选择数据！');
		}
 
		
		
	}
	
	
    //导出收款列表
    public function exportShoukuan(){
    	foreach ($_POST['payment'] as $key => $value) {
    		if($key) $ids .= ','.$value;
    		else $ids = $value;
    	}
    	if($ids){
    		$OP = M('order_payment');
    		$join = 'LEFT JOIN zm_payment_mode AS pm ON pm.mode_id = op.payment_mode';
    		$op = $OP->alias('op')->where('op.agent_id = '.C('agent_id').' and payment_id in('.$ids.')')->join($join)->field('op.*,pm.mode_name')->select();
    		$list = $this->_arrayIdToKey($op);
    		foreach($list as $key=>$value){
    			if($value['payment_status'] == -1){
    				$str ='已作废';
    			}elseif($value['payment_status'] == 0){
    				$str ='未支付';
    			}elseif($value['payment_status'] == 1){
    				$str ='已支付';
    			}elseif($value['payment_status'] == 2){
    				$str ='已审核';
    			}
    			$list[$value['payment_id']]['status'] = $str;
    			$list[$value['payment_id']]['time'] = date("Y-m-d H:i:s",$list[$value['payment_id']]['create_time']);
    		}
    		$dataTop = array('订单编号','收款金额','折扣金额','收款方式','收款时间','收款状态');
    		$associate = array('order_sn','payment_price','discount_price','mode_name','time','status');
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('您没有选择数据');
    	}
    }
    //导出销售统计
    public function exportSales(){
    	foreach ($_POST['sales'] as $key => $value) {
    		if($key) $ids .= ','.$value;
    		else $ids = $value;
    	}
    	if($ids){
    		$dataTop = array('订单编号','货品号','数量','金额');
    		$associate = array('order_sn','certificate_no','goods_number','goods_price');
    		$OG = M('order_goods');
    		$join = 'LEFT JOIN zm_order AS o ON o.order_id = og.order_id';
    		$list = $OG->alias('og')->where('og.agent_id = '.C('agent_id').' and og_id in ('.$ids.')')->join($join)->field('o.order_sn,og.*')->select();
    		$this->exportFile($list, $associate, $dataTop);
    	}else{
    		$this->error('您没有选择数据');
    	}
    }

    /**
     * 导出裸钻销售统计
     * @param int $type
     * @param string $selString
     */
    public function exportLuozuanStatistics($type,$selString){
        if($type == 1 || $type == 2){
            //获取数据
            if(strpos($selString,',')===0) $selString = substr($selString,1);
            $where = 'og_id IN ('.$selString.') and og.agent_id = '.C('agent_id');
            $OG = M('order_goods');
            $join[] = 'zm_order AS o ON o.order_id = og.order_id';
            $join[] = 'LEFT JOIN zm_user AS u ON u.uid = o.uid';
            $join[] = 'LEFT JOIN zm_trader AS t ON t.tid = o.tid';
            $join[] = 'LEFT JOIN zm_admin_user AS au ON au.user_id = o.parent_id';
            $fleld = 'og.*,u.username,t.trader_name,o.order_id,o.order_sn,o.order_status,o.order_price_up,o.order_price,au.user_name,o.tid,t.trader_name,o.uid';
            $list = $OG->alias('og')->join($join)->field($fleld)->where($where)->select();
            //把涉及到的订单折扣找出来
            $O = M('order');
            $oJoin = 'zm_order_goods AS og ON og.og_id IN ('.$selString.') and og.order_id = o.order_id and o.agent_id = '.C('agent_id');
            $orderList = $O->alias('o')->join($oJoin)->field('o.order_id,o.dollar_huilv')->select();
            $orderList = $this->_arrayIdToKey($orderList,'order_id');
            foreach ($list as $key => $value) {
                $info = unserialize($value['attribute']);
                $arr['order_sn'] = ' '.$value['order_sn'];
                $arr['quxiang'] = $info['quxiang'];
                $arr['goods_name'] = $info['goods_name'];
                $arr['shape'] = $info['shape'];
                $arr['certificate'] = $info['certificate_type'].$info['certificate_number'];
                $arr['weight'] = $info['weight'];
                $arr['color'] = $info['color'];
                $arr['clarity'] = $info['clarity'];
                $arr['cut'] = $info['cut'];
                $arr['polish'] = $info['polish'];
                $arr['symmetry'] = $info['symmetry'];
                $arr['fluor'] = $info['fluor'];
                $arr['dia_table'] = intval($info['dia_table']);
                $arr['dia_depth'] = intval($info['dia_depth']);
                $arr['milk'] = $info['milk'];
                $arr['coffee'] = $info['coffee'];
                $arr['dia_global_price'] = floatval($info['dia_global_price']);
                $arr['dia_discount'] = $info['dia_discount'];
                //计算销售折扣
                if($value['order_status'] >= 1){
                    $price = floatval($value['goods_price_up']);
                }else{
                    $price = floatval($value['goods_price']);
                }
                //按订单折扣计算销售折扣
                $dollar_huilv = $orderList[$value['order_id']]['dollar_huilv'];
                $arr['xiao_dia_discount'] = round($price/$arr['weight']/$dollar_huilv/$arr['dia_global_price']*100,2);
                $arr['goods_price'] = '=Q[$index]*S[$index]*'.C('DOLLAR_HUILV').'/100';
                $arr['goods_global_price'] = '=T[$index]*F[$index]';
                if($value['tid']) $arr['user_name'] = $value['trader_name'];
                else $arr['user_name'] = $value['username'];
                $data[] = $arr;
            }
            $dataTop = array('订单号','去向','编号','形状','证书','重量','颜色','净度','切工','抛光',
                '对称','荧光','全深','台宽','奶色','咖色','国际报价','原折扣','现折扣','每卡单价','每粒单价','客户');
            $associate = array('order_sn','quxiang','goods_name','shape','certificate',
                'weight','color','clarity','cut','polish','symmetry','fluor','dia_table','dia_depth','milk','coffee',
                'dia_global_price','dia_discount','xiao_dia_discount','goods_price','goods_global_price','user_name'
            );
            $dataStyle = array('18','5','11','9','14','5','4','5','4','4',
                '4','4','4','4','4','6','7','6','6','9','9','9');
			if($type == 1) {
				$this->exportFile($data, $associate, $dataTop,'','',$dataStyle,1);
			}elseif ($type == 2){
				$this->exportFile($data, $associate, $dataTop,'',1,$dataStyle,1);//使用另一种样式
			}
        }
    }

    /**
     * 上传文件
     */
    protected function uploadsFile(){
    	foreach ($_POST['pids'] as $key => $value) {
    		$where[]=$value;
    	}
    	$owhere = implode(",",$where);
    	if($owhere){
    		$odwhere=" goods_id in($owhere)";
    	}else{
    		$odwhere=" goods_id = 0";
    	}
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('xls', 'xlsx');
        $upload->savePath = '/Uploads/sanhuo/';
        $upload->autoSub = false;
        // 上传文件
        $info   =   $upload->uploadOne($_FILES['excelData']);
        if(!$info) $this->error('文件上传失败');
        else return $info;
    }

    /**
     * 处理图片写入
     * @param obj $Excel
     * @param string $address
     * @param array $data
     */
    public function imgCount($Excel,$address,$data,$width=65,$height=65,$mareg=''){
    	foreach ($data as $key => $value) {
	        $objS = new \PHPExcel_Worksheet_Drawing();
	        if(!file_exists('./Public/'.$value)){
	        	$this->error('找不到图片');
	        }else{
			$objS->setPath('./Public/'.$value);
			$offSetX = 75*($key+1)-65+10;
			$objS->setOffsetX($offSetX);
			$objS->setOffsetY(10);
			$objS->setHeight($height);
			$objS->setWidth($width);
			$objS->setCoordinates($address);
			$objS->setWorksheet($Excel->getActiveSheet());}
			if($mareg !== ''){
				$Excel->getActiveSheet()->getRowDimension('1')->setRowHeight($height);
				$Excel->getActiveSheet()->mergeCells($address.':'.$mareg);
			}
    	}
    }

    public function getImg(){

    }

    /**
     * 设置列宽
     * @param unknown $objActSheet
     * @param unknown $k
     * @param unknown $index
     * @param unknown $dataStyle
     */
    public function setRowWidth($objActSheet,$k,$index,$dataStyle){
//         $objStyleA5 = $objActSheet->getStyle($this->ZMkey[$k].$index);
//         $objStyleA5->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
//         //设置字体
//         $objFontA5 = $objStyleA5->getFont();
//         $objFontA5->setName('微软雅黑');
//         $objFontA5->setSize(9);
//         $objFontA5->getColor()->setARGB('00000000');
//         //设置边框
//         $objBorderA5 = $objStyleA5->getBorders();
//         $objBorderA5->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//         $objBorderA5->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//         $objBorderA5->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//         $objBorderA5->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $objActSheet->getColumnDimension($this->ZMkey[$k])->setWidth($dataStyle[$k]);
    }



    /**
     * 设置散货4C字体颜色
     * @param obj $objActSheet
     * @param string $position 位置
     * @param string $value 值
     */
    protected function setColorField($objActSheet,$position,$value){
		$col_array = explode(',',substr($value,0,strlen($value)-1));
		$objRichText = new \PHPExcel_RichText();
		$red = new \PHPExcel_Style_Color(\PHPExcel_Style_Color::COLOR_RED);
		foreach($col_array as $val){
			$text = explode(':', $val);
			$objRed = $objRichText->createTextRun($text[0]);
			$objRed->getFont()->setColor($red);
			$objRichText->createText(':' . $text[1] . ',');
		}
		$objActSheet->setCellValue($position,$objRichText);
    }

    /**
     * 导出Excel文件
     * @param array $data
     * @param array $associate
     * @param array $dataTop
     * @param int   $set_color_field 是否设置字体
     * @param int   $style 头部样式
     * @param int   $dataStyle 设置列宽
     */
    protected function exportFile($data,$associate,$dataTop,$set_color_field = '',$style=0,$dataStyle='',$isBorder=''){
        ob_end_clean();
        import("Org.Util.PHPExcel");
        $PHPExcel = new \PHPExcel();
        $objActSheet = $PHPExcel->getActiveSheet();
        $fileName = date('YmdHis',time()).rand(100000,999999);//excel文件名称
		$index = 1;
		if($style ==1) {
			$this  ->imgCount($PHPExcel, 'A'.$index, array('images/exportExcel_logo.png'), 424, 67, 'V'.$index);
			$index ++;
			$str   = '日期：'.date('Y/m/d').' （网站更新日期）货币单位：RMB·元';
			$objActSheet -> setCellValue('A'.$index,$str);
			$objActSheet -> mergeCells('A'.$index.':I'.$index);
			$str   = '销售员：';
			$objActSheet -> setCellValue('N'.$index,$str);
			$objActSheet -> mergeCells('N'.$index.':Q'.$index);
			$str   = '审批员：';
			$objActSheet -> setCellValue('R'.$index,$str);
			$objActSheet -> mergeCells('R'.$index.':V'.$index);
			$index ++;
		}
        //写头文字
        foreach ($associate as $key => $value) {
            $objActSheet->setCellValue($this->ZMkey[$key].$index,$dataTop[$key]);
            $objActSheet->getColumnDimension($this->ZMkey[$key])->setWidth(15);
            $objActSheet->getStyle($this->ZMkey[$key].$index)->getAlignment()->setHorizontal("center");
        }
		$index++;
		//写数据
        foreach ($data as  $value) {
            foreach ($associate as $k => $v) {
            	$writeValue = $value[$v];
            	if(is_array($writeValue)){

            		//处理图片相关
            		$this -> imgCount($PHPExcel,$this->ZMkey[$k].$index,' '.$writeValue['data']);
            		$objActSheet -> getColumnDimension($this->ZMkey[$k])->setWidth(70);
            		$objActSheet -> getStyle($this->ZMkey[$k])->getAlignment()->getVertical("VERTICAL_CENTER");
            	}else{

            		//处理文字颜色
					if(!empty($set_color_field) AND array_search($associate[$k],explode(',',$set_color_field))!==false AND $writeValue != 'none'){
						$this->setColorField($objActSheet,$this->ZMkey[$k].$index,$writeValue);
					}else{
						//处理字符替换
					    $writeValue = "\t".str_replace('[$index]',$index,$writeValue)."\t";
						$objActSheet->setCellValue($this->ZMkey[$k].$index,$writeValue);
					}
					//是否设置列宽还是默认20的列宽
					if(is_array($dataStyle)){
					    $this->setRowWidth($objActSheet, $k, $index, $dataStyle);
					}else{
					    $objActSheet->getColumnDimension($this->ZMkey[$k])->setWidth(23);
					}
            		$objActSheet->getStyle($this->ZMkey[$k])->getAlignment()->getVertical("VERTICAL_JUSTIFY");
            	}
            }
            $index++;
        }
		if($style ==1) {
			$str   = '货品总量(Ct) ';
			$objActSheet -> setCellValue('A'.$index,$str);
			$objActSheet -> mergeCells('A'.$index.':B'.$index);
			$str   = '=SUM(F4:F'.($index-1).')';
			$objActSheet -> setCellValue('C'.$index,$str);
			$objActSheet -> mergeCells('C'.$index.':E'.$index);
			$str   = '总金额：';
			$objActSheet -> setCellValue('F'.$index,$str);
			$objActSheet -> mergeCells('F'.$index.':H'.$index);
			$str   = '=SUM(U4:U'.($index-1).')';
			$objActSheet -> setCellValue('I'.$index,$str);
			$objActSheet -> mergeCells('I'.$index.':L'.$index);
			$str   = '每卡平均单价';
			$objActSheet -> setCellValue('M'.$index,$str);
			$objActSheet -> mergeCells('M'.$index.':P'.$index);
			$str   = '=SUM(T4:T'.($index-1).')/C'.$index;
			$objActSheet -> setCellValue('Q'.$index,$str);
			$objActSheet -> mergeCells('Q'.$index.':V'.$index);
			$index ++;
			$str   = '客户公司：';
			$objActSheet -> setCellValue('A'.$index,$str);
			$objActSheet -> mergeCells('A'.$index.':B'.$index);
			$str   = '';
			$objActSheet -> setCellValue('C'.$index,$str);
			$objActSheet -> mergeCells('C'.$index.':L'.$index);
			$str   = '客户提货签名：';
			$objActSheet -> setCellValue('M'.$index,$str);
			$objActSheet -> mergeCells('M'.$index.':P'.$index);
			$str   = '';
			$objActSheet -> setCellValue('Q'.$index,$str);
			$objActSheet -> mergeCells('Q'.$index.':V'.$index);
		}
		ob_clean();
		header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 打印视图
     * @param number $order_id
     * @param string $type
     */
	public function printView($order_id=0,$type='goods'){
		if( $type == 'goods' ){
			//获取数据 获取裸钻数据
			$where  = 'og.agent_id = '.C('agent_id').' and o.order_id IN ('.$order_id.') and goods_type = 1 and og.agent_id = '.C('agent_id');
			$OG     = M('order_goods');
			$join[] = 'zm_order AS o ON o.order_id = og.order_id';
			$join[] = 'LEFT JOIN zm_user AS u ON u.uid = o.uid';
			$join[] = 'LEFT JOIN zm_trader AS t ON t.tid = o.tid';
			$join[] = 'LEFT JOIN zm_admin_user AS au ON au.user_id = o.parent_id';
			$fleld  = 'og.*,u.username,o.order_id,o.order_sn,o.order_status,o.order_price_up,o.order_price,au.user_name,o.tid,t.trader_name,o.uid';
			$count  = $OG->alias('og')->join($join)->where($where)->count();
			$list   = $OG->alias('og')->join($join)->field($fleld)->where($where)->select();
			foreach ($list as $key => $value) {
				$info = unserialize($value['attribute']);
				$arr['order_sn'] = $value['order_sn'];
				$arr['quxiang'] = $info['quxiang'];
				$arr['goods_name'] = $info['goods_name'];
				$arr['shape'] = $info['shape'];
				$arr['certificate_type'] = $info['certificate_type'];
				$arr['certificate_number'] = $info['certificate_number'];
				$arr['weight'] = $info['weight'];
				$arr['color'] = $info['color'];
				$arr['clarity'] = $info['clarity'];
				$arr['cut'] = $info['cut'];
				$arr['polish'] = $info['polish'];
				$arr['symmetry'] = $info['symmetry'];
				$arr['fluor'] = $info['fluor'];
				$arr['dia_table'] = $info['dia_table'];
				$arr['dia_depth'] = $info['dia_depth'];
				$arr['milk'] = $info['milk'];
				$arr['coffee'] = $info['coffee'];
				$arr['dia_global_price'] = $info['dia_global_price'];
				$arr['dia_discount'] = $info['dia_discount'];
				if($value['order_status'] > 1){
					$arr['goods_global_price'] = $value['order_price_up'];
				}else{
					$arr['goods_global_price'] = $value['order_price'];
				}
				$arr['goods_price'] = formatPrice($arr['goods_global_price']/$arr['weight']);
				$arr['username'] = $value['username'];
				$data[] = $arr;
			}
			$dataTop = array('订单号','去向','编号','形状','证书','证书号码','重量','颜色','净度','切工','抛光',
				'对称','荧光','全深','台宽','奶色','咖色','国际报价','折扣','每卡单价','每粒单价','客户名');
			$associate = array('order_sn','quxiang','goods_name','shape','certificate_type','certificate_number',
				'weight','color','clarity','cut','polish','symmetry','fluor','dia_table','dia_depth','milk','coffee',
				'dia_global_price','dia_discount','goods_price','goods_global_price','username'
			);
			$this->exportFile($data, $associate, $dataTop);
		}
	}

    /**
     * 读取数据，返回数组
     * @param string $fileName
     * @param int $associate
     */
    protected function readFile($fileName,$exts='xls',$associate){
        import("Org.Util.PHPExcel");
        $PHPExcel=new \PHPExcel();
        if($exts == 'xls'){
            import("Org.Util.PHPExcel.Reader.Excel5");
            $PHPReader = new \PHPExcel_Reader_Excel5();
        }else if($exts == 'xlsx'){
            import("Org.Util.PHPExcel.Reader.Excel2007");
            $PHPReader = new \PHPExcel_Reader_Excel2007();
        }
        $PHPExcel = $PHPReader->load('./Public/'.$fileName,$exts);
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $PHPExcel->getSheet(0);
        $allRow = $currentSheet->getHighestRow();
        $index = 2;
        for($row=2;$row<=$allRow;$row++){
            foreach ($associate as $key => $value) {
                $address = $this->ZMkey[$key].$row;
                if($value['img']){
                	$data[$index][$value] = $currentSheet->getCell($address)->getValue();
                }else{

                }
            }
            $index++;
        }
        return $data;
    }
}
