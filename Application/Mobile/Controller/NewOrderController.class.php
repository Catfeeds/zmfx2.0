<?php
/**
 * Created by PhpStorm.
 * User: Kwan Wong
 * Date: 2017/8/1
 * Time: 12:25
 */

namespace Mobile\Controller;

class NewOrderController extends NewMobileController{

    private $agentId;
    private $uid;

    public function __construct()
    {
        parent::__construct();

        $this->agentId = C('agent_id');
        $this->uid = $_SESSION['m']['uid'];
    }

    /**
     * 显示购物车页面
     * 
     * @author wangkun
     * @date 2017/8/1
     */
    public function orderCart()
    {
        $data = $this -> getCartGoods(false,false,false,1);
		
 
		
        $this->luozuan = $data['luozuan'];
        $this->sanhuo  = $data['sanhuo'];
        $this->product = $data['product'];
        $this->data    = $data['cartGoods'];
        $this->count   = $data['count'];
        $this->total   = $data['total'];
        $this->total_weight = $data['total_weight'];
        $this->display();
    }

	
	
	
	
	public function BindCartId(){
		$good_id 			= $_POST['cart_good_id'];	//裸钻ID	
		$gid 				= $_POST['cart_gid'];		//定制ID

		$cart				= M('cart');
		$where 				= ' id = '.$gid;
		if($cart->where($where)->find()){
		$action 		= $cart->where($where)->setField('goods_cart_id',$good_id);
		}
		
		if($action){
			$result['info'] 	= '匹配成功';
			$result['status']	= 100;
		}else{
			$result['info'] 	= '匹配失败！';
			$result['status'] 	= 0;
		}
		$this->ajaxReturn($result);
	}	
	
	
	
	
	
	
	/**
	 * auth	：fangkai
	 * content：添加裸钻或者散货到购物车
	 * time	：2016-5-27
	**/
	public function addCart(){
		$gids 			= $_POST['goodId'];
		$type 			= I('post.type','','intval');
		$agent_id 		= C('agent_id');
		$sku_sn 		= I('post.sku_sn');			//成品需要
		$tips 			= I('post.tips');			//标识，1代表加入购物车，2代表立即购买，3代表立即定制
		$hand 			= I("hand","");				//手寸
		$hand1 			= I("hand1","");			//手寸
		$word 			= I("word",""); 			//十字留言
		$materialId 	= I("materialId",0);		//主石
		$diamondId 		= I("diamondId",0);			//材质
		$deputystoneId 	= I("deputystoneId",0);		//副石
		$goods_number	= I('goods_number',1);
		$sd_id          = I('sd_id','');
		$sd_id1         = I('sd_id1','');
		$word1 			= I("word1","");
		$dingzhi_id     = I("dingzhiId");

		if($tips==2){
			if(empty($_SESSION['app']['uid'])){
				$result['info'] = '请先登录之后再立即购买';
				$result['status'] = 2;
				$this->ajaxReturn($result);
			}
		}

		if(!in_array($type,array(1,2,3,4))){
			$result['info'] = '添加购物车失败';
			$result['status'] = 0;
			$this->ajaxReturn($result);
			exit;
		}		
		foreach($gids as $val){
			switch($type){
				case 1:
					$datamodel = D("GoodsLuozuan");
					$where['gid'] = $val;
					$where['goods_number'] = array('GT',0);
					break;
				case 2:
					$datamodel           = D('GoodsSanhuo');
					$where['goods_id'] = $val;
					$where['goods_weight'] = array('GT',0);
					break;
				case 3:
					$datamodel = D('Common/Goods');
					break;
				case 4:
					$datamodel = D('Common/Goods');
					list($material,$associateInfo,$luozuan,$deputystone) = $datamodel -> getJingGongShiData($val,$materialId,$diamondId,$deputystoneId);
					break;
			}
            if( $type== 3 || $type == 4 ){

                $goods = $datamodel->get_info($val,0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
				if($goods['goods_type'] == 3 &&  $goods['goods_number'] <= 0 ){
					$result['info']	= "该商品库存不足";
					$result['status']	= 0;
					$this->ajaxReturn($result);
				}
			}elseif($type== 2){
					$goods = $datamodel -> getInfo($where);
			}else {
					$goods = $datamodel -> where($where) -> find();
            }
 
			if($goods){
				$data = array();
				//根据type的值来组成不同的数组以便存储
				switch($type){
					case 1:
						$goods        = getGoodsListPrice($goods, $_SESSION['app']['uid'], 'luozuan','single');
						$userdiscount = getUserDiscount($_SESSION['app']['uid']);
						$goods['userdiscount']        = $userdiscount;
						$goods['luozuan_advantage']   = get_luozuan_advantage($goods['weight']); ;
						$goods['price_display_type']  = C('price_display_type');
						$data['goods_id']   = $goods['gid'];
						$data['goods_type'] = 1;
						$data['goods_number'] 		  = 1;
						$data['goods_sn'] = $goods['certificate_number'];
						$wherecart['goods_sn'] = $data['goods_sn'];
						break;
					case 2:
						$goods         					= getGoodsListPrice($goods, $_SESSION['app']['uid'], 'sanhuo', 'single');
						$data['goods_id'] 				= $goods['goods_id'];
						$data['goods_type'] 			= 2;
						$data['goods_sn'] 				= $goods['goods_sn'];
						$data['goods_number'] 			= 0;
						$wherecart['goods_id'] 			= $data['goods_id'];
						break;
					case 3:
						$temp                           = $datamodel -> getGoodsSku($val ," sku_sn = '$sku_sn' ");
						$temp['goods_type']   			= 3;
						$temp 							= getGoodsListPrice($temp, $_SESSION['app']['uid'], 'consignment','single');
						$goods['goods_price'] 			= formatRound($temp['goods_price'],2);
						$goods['activity_price']		= formatRound($temp['activity_price'],2);		//活动商品价格
						$goods['marketPrice'] 			= formatRound($temp['marketPrice'],2);
						$goods['sku_sn']				= $sku_sn;
						$goods['goods_sku']   			= $temp;
						$goods['consignment_advantage'] = $temp["consignment_advantage"];
						$goods['activity_status']		= $temp["activity_status"];						//活动商品标识
						$data['goods_id'] 				= $goods['goods_id'];
						$data['goods_type'] 			= 3;
						$data['goods_number'] 			= $goods_number;
						$data['goods_sn'] 				= $goods['goods_sn'];
						$data['sku_sn']   				= $sku_sn;
						$wherecart['goods_id'] 			= $data['goods_id'];
						$wherecart['sku_sn']   			= $sku_sn;
						break;
					case 4:
						$data       					  = array('status'=>0);
						if( !$goods['price_model'] ){
							$goods['goods_price']         = $datamodel -> getJingGongShiPrice($material, $associateInfo,$luozuan,$deputystone);
						}else{
							$category_name = M('goods_category') -> where(" category_id = '$goods[category_id]' ") -> getField('category_name');
							$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
							if( $is_size ){
								$size                     = $datamodel -> getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
								$goods['goods_price']     = $size['size_price'];
								$is_dui                   = InStringByLikeSearch($category_name,array('对戒'));
								if( $is_dui ){
									$size                 = $datamodel -> getGoodsAssociateSizeAfterAddPoint(" sex = '1' and min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
									$goods['goods_price'] = $size['size_price'];
									$size                 = $datamodel -> getGoodsAssociateSizeAfterAddPoint(" sex = '2' and min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
									$goods['goods_price'] = $size['size_price'] + $info['goods_price'];
								}
							} else {
								$ass                      = $datamodel->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$val' ");
								$goods['goods_price']     = $ass['fixed_price'];
							}
						}

						$goods['activity_status']		= $goods['activity_status'];	
						$info                		  	= getGoodsListPrice($goods, $_SESSION['app']['uid'], 'consignment', 'single');
						$stopgap_goods_id 				= isset($deputystone['goods_id']) ? $deputystone['goods_id'] : $luozuan['goods_id'];
						$goods['associateInfo'] 		= $associateInfo;
						$goods['luozuanInfo'] 			= $luozuan; 
						$goods['deputystone'] 			= $deputystone;
						$goods['hand'] 					= $hand;
						$goods['word'] 					= $word;
						$goods['hand1'] 				= $hand1;
						$goods['word1'] 				= $word1;
						$goods['activity_price']		= $info['activity_price'];
						$goods['goods_price'] 			= formatRound($info['goods_price'],2);
						$goods['marketPrice'] 			= formatRound($info['marketPrice'],2);
						$goods['consignment_advantage'] = $info["consignment_advantage"];
						$goods['goods_type'] 			= 4;
						if($sd_id){
							$S                      	= D('Common/SymbolDesign');
							$info                   	= $S -> getInfo($sd_id);
							$goods['sd_images'] 		= $info['images_path'];
						}
						if($sd_id1){
							$S          	            = D('Common/SymbolDesign');
							$info           	        = $S -> getInfo($sd_id1);
							$goods['sd_images1'] 		= $info['images_path'];
						}						
						$data['goods_id'] 				= $stopgap_goods_id;
						$data['goods_type'] 			= $goods['goods_type'];
						$data['goods_sn'] 				= $goods['goods_sn'];
						$data['goods_number'] 			= $goods_number;
						$wherecart['goods_sn'] 			= $data['goods_sn'];

						$wherecart['goods_attr'] 		= serialize($goods);
						break; 
				}				
				$data['goods_attr'] = serialize($goods);
				$data['agent_id'] = $agent_id;
				$data[$this->userAgentKey] = $this->userAgentValue;
				//检测该商品是否已经加入购物车
				$cart = M('cart');
				$wherecart['agent_id'] 			= $agent_id;
				$wherecart[$this->userAgentKey] = $this->userAgentValue;
				$wherecart['goods_type'] = $type;
				$cartGood	= $cart->where($wherecart)->find();
				if($dingzhi_id==""){
					if(!empty($cartGood)){
						if($type == 3 || $type == 4){
							//立即购买
								if($goods_number != intval($cartGood['goods_number'])){
									$save['goods_number']	= $goods_number;
									$saveAction = M('cart')->where($wherecart)->save($save);
								}
								$result['url']				= $cartGood['id'];
								$result['status']			= 100;
								if($tips==3){	
									$result['info'] = '请先为选好的戒托挑选裸钻';
								}else{
									$result['info']	= '添加购物车成功';
								}
								$this->ajaxReturn($result);						
						}
					}
				}else{
					//定制产品加  dingzhi_id  特殊字段
					$data['dingzhi_id']           = $dingzhi_id;
				}
				$action = $cart->data($data)->add();
				
				
			}
		}
		if($action){
			$result['info'] 	= '添加购物车成功';
			$result['status']	= 100;
			if($tips==3){	
				$result['info'] = '请先为选好的戒托挑选裸钻';
			}
			$result['url']		= $action;
		}else{
			$result['info'] 	= '添加购物车失败！';
			$result['status'] 	= 0;
		}
		$this->ajaxReturn($result);
	}	
	
    /**
     * 添加成品到购物车
     *
     * @author wangkun
     * @date 2017/8/2
     *
     * @return json 添加结果
     */
    public function add2cart(){
        $id_type	  = I('id_type','');
        $gid          = I("gid");
        $sku_sn       = I("sku_sn",0);
        $goods_type   = 3;
        $goods_number = I("goods_number",1,'intval');

        $G            = D("Common/Goods");
        $goods        = $G -> get_info($gid,0,'shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');

        // 列表页直接加入购物车，则设置默认属性
        $M          = D('Common/Goods');
        $goodsInfoMore  = $M -> getProductInfoAfterAddPoint($goods, $_SESSION['m']['uid']);
        // 若没选择sku_sn
        if(empty($sku_sn)){
            if(isset($goodsInfoMore['similar']) && !empty($goodsInfoMore['similar'])){
                $sku_sn = $goodsInfoMore['similar'][0]['sku_sn'];
            }
        }

        $data         = array('status'=>0,'msg'=>'添加购物车失败');
        if($gid<0 || empty($goods)){
            $data['msg'] = "不存在该货品";
            $this->ajaxReturn($data);
        }
        if($goods['goods_type']==3 && $goods_number>$goods['goods_number']){
            $data['msg'] = "该商品库存不足";
            $this->ajaxReturn($data);
        }
        $where  = " agent_id='".C('agent_id')."' and goods_id=$gid AND goods_type=".$goods_type." AND sku_sn='$sku_sn' ";
        $where .= " AND ".$this->userAgent;
        $cartGood = M('cart')->where($where)->find();

        if(!empty($cartGood)){
            $save['goods_number']	= $cartGood['goods_number']+1;
            $saveAction = M('cart')->where($where)->save($save);
            $data['id']				= $cartGood['id'];
            $data['status']			= 100;
            $data['msg']			= '添加购物车成功';
        }else{
            $goods['sku_sn'] = $sku_sn;
            if($goods_type == 3){
                $temp                 = $G -> getGoodsSku($gid ," sku_sn = '$sku_sn' ");

                $temp['goods_type']   = $goods_type;
                $temp                 = getGoodsListPrice($temp, $_SESSION['m']['uid'], 'consignment', 'single');
                $goods['goods_price'] = formatRound($temp['goods_price'],2);
                $goods['activity_price']	= formatRound($temp['activity_price'],2);		//活动商品价格
                $goods['marketPrice'] = formatRound($temp['marketPrice'],2);
                $goods['goods_sku']   = $temp;
                $goods['consignment_advantage'] = $temp["consignment_advantage"];
                $goods['activity_status']	= $temp["activity_status"];						//活动商品标识
            }
            $goodsData['goods_attr']        = serialize($goods);
            $goodsData[$this->userAgentKey] = $this->userAgentValue;
            $goodsData['goods_type']        = $goods_type;
            $goodsData['goods_id']          = $gid;
            $goodsData['goods_sn']          = $goods['goods_sn'];
            $goodsData['goods_number']      = $goods_number;
            $goodsData['sku_sn']            = $sku_sn;
            $goodsData['agent_id']          = C('agent_id');
            $action	= M('cart')->add($goodsData);
            if($action){
                $data['id']		= $action;
                $data['msg']    = "添加成功";
                $data['status'] = 100;
            }else{
                $data['msg']    = "添加失败";
                $data['status'] = 0;
            }
        }
        $this->ajaxReturn($data);
    }

    /**
     * 添加定制产品到购物车
     *
     * @author wangkun
     * @date 2017/8/2
     *
     * @return json 添加结果
     */
    public function addZMGoods2cart(){
        $goodsModel = D("Common/Goods");

        $id_type	   = I('id_type', '');
        $gid           = I("gid", 0);
        $goods_type    = I("goods_type", 4);
		$dingzhi_id    = I("dingzhiId");
        $goods_number  = I("goodsNumber", 1);

        $luozuan       = I("luozuan", 0);

        // 材质，主石，副石
        $materialId    = I("materialId", 0);
        $diamondId     = I("diamondId", 0);
        $deputystoneId = I("deputystoneId", 0);

        // 刻字，手寸，自定义图案
        $word          = I("word", "");
        $word1         = I("word1","");
        $hand          = I("hand", "");
        $hand1         = I("hand1","");
        $symbolId      = I('symbolId', 0);
        $symbolId1     = I("symbolId1", "");

        // 获取产品初始信息
        $goodsInfo = $goodsModel->get_info($gid, 0, ' shop_agent_id= "'.$this->agentId.'" and sell_status = "1" ');

        // 获取产品加点后信息及附加信息
        $goodsInfoMore = $goodsModel->getProductInfoAfterAddPoint($goodsInfo, $this->uid);

        // 列表页直接加入购物车，则设置默认属性
        // 若没选择材质，则取默认材质
        if(empty($materialId)){
            if(isset($goodsInfoMore['associate_info']) && !empty($goodsInfoMore['associate_info'])){
                $materialId = $goodsInfoMore['associate_info'][0]['material_id'];
            }
        }

        // 若没选择diamondId和diamondWeight
        if(empty($diamondId)){
            if(isset($goodsInfoMore['associate_luozuan']) && !empty($goodsInfoMore['associate_luozuan'])){
                $diamondId = $goodsInfoMore['associate_luozuan'][0]['gal_id'];
                $dismondWeight = $goodsInfoMore['associate_luozuan'][0]['weights_name'];
            }
        }

        // 若没选择deputystoneId
        if(empty($deputystoneId)){
            if(isset($goodsInfoMore['associate_deputystone']) && !empty($goodsInfoMore['associate_deputystone'])){
                $deputystoneId = $goodsInfoMore['associate_deputystone'][0]['gad_id'];
            }
        }

        // 获取主石信息
        $luozuanInfo = $goodsModel->getGoodsAssociateLuozuanAfterAddPoint("goods_id=$gid AND gal_id=$diamondId");

        // 获取材质信息
        $associateInfo = $goodsModel->getGoodsAssociateInfoAfterAddPoint("goods_id=$gid AND zm_goods_associate_info.material_id=$materialId ");

        // 获取副石信息
        if(!empty($deputystoneId)) {
            $deputystone = $goodsModel->getGoodsAssociateDeputystoneAfterAddPoint("gad_id=$deputystoneId");
        }
        if($luozuan > 0){
            $certificate_number = D("GoodsLuozuan")->where(['gid' => $luozuan])->getField("certificate_number");
            $goodsInfo['matchedDiamond'] = $certificate_number;
        }

        // 默认返回值
        $returnData = [
            'status' => 100,
            'msg'    => '定制成功',
        ];

        if($luozuanInfo && $associateInfo && $goodsInfo){
            $material = $goodsModel->getGoodsMaterialAfterAddPoint(" material_id = $materialId ");
            // 若产品模式为定制自定义计价
            if(!$goodsInfo['price_model'] ){
                $goodsInfo['goods_price'] = $goodsModel->getJingGongShiPrice($material, $associateInfo, $luozuanInfo, $deputystone);
            }else{
                $categoryName = M('goods_category')->where(['category_id' => $goodsInfo['category_id']])->getField('category_name');
                $is_size = InStringByLikeSearch($categoryName, ['项链', '手链', '钻戒', '戒', '对戒']);
                if($is_size){
                    // 获取尺寸加点
                    $size = $goodsModel->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
                    $goodsInfo['goods_price'] = $size['size_price'];

                    // 对戒
                    $is_dui = InStringByLikeSearch($categoryName, ['对戒']);
                    if($is_dui){
                        $size = $goodsModel->getGoodsAssociateSizeAfterAddPoint(" sex = '1' and min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
                        $goodsInfo['goods_price'] = $size['size_price'];
                        $size = $goodsModel->getGoodsAssociateSizeAfterAddPoint(" sex = '2' and min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
                        $goodsInfo['goods_price'] = $size['size_price']+$goodsInfo['goods_price'];
                    }
                } else {
                    $ass = $goodsModel->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
                    $goodsInfo['goods_price'] = $ass['fixed_price'];
                }
            }
            $goodsInfo['associateInfo'] = $associateInfo;
            $goodsInfo['luozuanInfo'] = $luozuanInfo;
            $goodsInfo['deputystone'] = $deputystone;
            $goodsInfo['hand'] = $hand;
            $goodsInfo['hand1'] = $hand1;
            $goodsInfo['word'] = $word;
            $goodsInfo['word1'] = $word1;

            // 获取图案符号
            $symbolDesignModel = D('Common/SymbolDesign');
            if($symbolId){
                $info = $symbolDesignModel->getInfo($symbolId);
                $goodsInfo['sd_images'] = $info['images_path'];
            }
            if($symbolId1){
                $info = $symbolDesignModel->getInfo($symbolId1);
                $goodsInfo['sd_images1'] = $info['images_path'];
            }

            $goodsInfo['sd_id']          = $symbolId;
            $goodsInfo['sd_id1']         = $symbolId1;
            $goodsInfo                  = getGoodsListPrice($goodsInfo, $this->uid, 'consignment', 'single');
            $data['goods_attr']         = serialize($goodsInfo);
            $data[$this->userAgentKey]  = $this->userAgentValue;
            $data['goods_id']           = $gid;
            $data['goods_type']         = $goods_type;
            $data['goods_number']       = $goods_number;
            $data['agent_id']           = $this->agentId;

            $cartWhere = [
                'agent_id' => $this->agentId,
                'goods_id' => $gid,
                'goods_type' => $goods_type,
                'goods_attr' => addslashes($data['goods_attr']),
                'uid' => $this->uid,
            ];

            $cartGood = M('cart')->where($cartWhere)->find();
			if($dingzhi_id!=""){
				//添加购物车（定制产品）
				$data['dingzhi_id']           = $dingzhi_id;
				$action	= M('cart')->data($data)->add();
				if(!$action){
					$returnData['status'] = 0;
					$returnData['msg'] = "定制失败，请重新提交";
				}
				$returnData['id'] = $action;	
			}else{
				if($cartGood){
					// 若购物车已存在该产品，则更新数量
					$save['goods_number'] = $cartGood['goods_number']+$data['goods_number'];
					$saveAction = M('cart')->where($cartWhere)->save($save);
	
					if(!$saveAction){
						$returnData['status'] = 0;
						$returnData['msg'] = "定制失败，请重新提交";
					}
	
					$returnData['id'] = $cartGood['id'];
				}else{
					$action	= M('cart')->data($data)->add();
					if(!$action){
						$returnData['status'] = 0;
						$returnData['msg'] = "定制失败，请重新提交";
					}
					$returnData['id'] = $action;
				}
			}
        }else{
            $returnData['status'] = 0;
            $returnData['msg'] = "定制失败，请重新提交";
        }
        $this->ajaxReturn($returnData);
    }

    public function matchedDiamondInCart($certificate_number){
        $data = M('cart')->where("agent_id='".C('agent_id')."' and goods_attr like '%$certificate_number%'")->select();
        if($data){
            foreach($data as $key=>$val){
                $val['goods_attr'] = unserialize($val['goods_attr']);
                if(strstr($val['goods_attr']['matchedDiamond'],$certificate_number)){
                    return true;
                }
            }
        }
    }

    // 根据裸钻的证书查询购物车中是否有客户订购
    public function getDiamondStatus($certificate_number){
        $array = array("certificate_number"=>$certificate_number);
        $arraySerialize = serialize($array);
        $arraySerialize =  substr($arraySerialize,5,-1);
        $where = "agent_id='".C('agent_id')."' and goods_attr like '%$arraySerialize%' AND uid!='".$_SESSION['web']['uid']."'";

        if(M('cart')->where($where)->find()){
            return "待定";
        }else{
            return "有货";
        }
    }
	

    /**
     * 获取购物车数据
     *
     * @param $id
     * @param int $del_no_goods
     * @param int $goods_jiagong
     * @return mixed
     */
    public function getCartGoods($id, $del_no_goods= 0, $goods_jiagong = 0,$relation=0){

        if($_SESSION['m']['uid'] or $_COOKIE['PHPSESSID']) {
            if($id){
                $where['id'] = array('in',$id);
            }
            $where['agent_id']	=	C('agent_id');
            $where[$this->userAgentKey]		=	$this->userAgentValue ;
            $cartGoods = D('Common/Cart')->getUpdateCartGoodsList($where, $_SESSION['m']['uid']);
            if($del_no_goods == 1){
                $cartGoods = D('Common/Cart')->delNullGoods($cartGoods);

            }
            if($goods_jiagong == 1){
                return $cartGoods;
            }

        }
		
		

        $luozuan_shape_array = M('goods_luozuan_shape')->getField('shape_id,shape_name');
        $deputystoneM        = M('goods_associate_deputystone');
        $luozuan 			 = array();
        $sanhuo  		     = array();
        $product 			 = array();

 
        if($cartGoods){
            foreach($cartGoods as $key=>$val){
                $cartGoods[$key]['goods_attrs'] = $val['goods_attrs'] = unserialize($val['goods_attr']);

                if($cartGoods[$key]['goods_attrs']['attribute']){
                    $cartGoods[$key]['goods_attrs']['attributes'] = unserialize($cartGoods[$key]['goods_attrs']['attribute']);
                }
                $cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_name'] =  $luozuan_shape_array[$cartGoods[$key]['goods_attrs']['luozuanInfo']['shape_id']];

                $cartGoods[$key]['goods_attrs']['deputystone_name']  = "";
                $cartGoods[$key]['goods_attrs']['deputystone_price'] = "";

                if($cartGoods[$key]['goods_attrs']['deputystone']){
                    $deputystone = $deputystoneM->where(array('gad_id'=>$cartGoods[$key]['goods_attrs']['deputystone']['gad_id']))->select();
                    if($deputystone){
                        $cartGoods[$key]['goods_attrs']['deputystone_name'] = '副石：'.$deputystone[0]['deputystone_name'];
                        $cartGoods[$key]['goods_attrs']['deputystone_price'] = $deputystone[0]['deputystone_price'];
                    }
                }

                switch($val['goods_type']){
                    case 1: // 1 表示裸钻
						$luozuan['count'] += 1;
						$luozuan['weight'] += $cartGoods[$key]['goods_attrs']['weight'];
						
						//查出是否存在特惠钻石
						$preferential_luozuan	= M('goods_preferential')->where(array('agent_id'=>C('agent_id'),'gid'=>$cartGoods[$key]['goods_attrs']['gid']))->find();
						$cartGoods[$key]['goods_attrs']['preferential_id']	= $preferential_luozuan['id'];
						$cartGoods[$key]['goods_attrs']['pre_discount']		= $preferential_luozuan['discount'];
						
						$xiuzheng = getGoodsListPrice($cartGoods[$key]['goods_attrs'], $_SESSION['m']['uid'], 'luozuan',  'single');						
						
						$cartGoods[$key]['goods_attrs']['meikadanjia']   = $xiuzheng['cur_price'];  
						$cartGoods[$key]['goods_attrs']['price']  		 = $xiuzheng['price'];  							
						$cartGoods[$key]['goods_attrs']['shape_name']	 = $luozuan_shape_name_array[$cartGoods[$key]['goods_attrs']['shape']];
						$cartGoods[$key]['status']  = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
						//判断钻石是否匹配到戒托中。
						$cartGoods[$key]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);

						if($relation){
							if(!$val['goods_cart_id']){
								$luozuan['data'][] = $cartGoods[$key];
							}
						}else{
							$luozuan['data'][] = $cartGoods[$key];
						} 
						
						$luozuan['total'] += formatRound($cartGoods[$key]['goods_attrs']['price'],2); 
                        break;
                    case 2:// 散货
                        //查出产品的原价格
                        if( $cartGoods[$key]['goods_id'] ){
                            $GSobj     = D('GoodsSanhuo');
                            $where     = array('goods_id'=>array('in',$cartGoods[$key]['goods_id']));
                            $goodsInfo = $GSobj -> getInfo($where);
                            $xiuzheng         = getGoodsListPrice($goodsInfo, $_SESSION['m']['uid'], 'sanhuo', 'single');
                            $price 	          = $xiuzheng['goods_price'];
                            $sanhuo['weight'] += $cartGoods[$key]['goods_number']; //钻石重量
                            $cartGoods[$key]['goods_attrs']['type_name'] = M("goods_sanhuo_type")->where("tid=".$cartGoods[$key]['goods_attrs']['tid'])->getField("type_name");
                            $cartGoods[$key]['goods_attrs']['marketPrice'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*1.5,2);
                            $cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($cartGoods[$key]['goods_attrs']['goods_price']*$cartGoods[$key]['goods_number'],2);
                            $sanhuo['data'][] = $cartGoods[$key];
                            $sanhuo['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
                            $sanhuo['count'] += 1;
                        }
                        break;
                    case 3:     // 成品
                        $product['count'] += $val['goods_number'];
                        $attr = explode("^",$cartGoods[$key]['goods_attrs']['goods_sku']['attributes']);
                        if($attr){
                            $attrs = "";
                            foreach($attr as $k=>$v){
                                $temp = explode(":",$v);
                                if($temp){
                                    $inputType = M("goods_attributes")->where("attr_id='".$temp[0]."'")->getField('input_type');
                                    if($inputType == 3){ //如果是手填值,直接显示该值
                                        $attrs .= $temp[1];
                                    }else{  //如果不是手填值,则查出对应的属性值
                                        $attrs .= M("goods_attributes_value")->where("attr_value_id='".$temp[1]."'")->getField('attr_value_name');
                                    }
                                    $attrs .= " ";
                                }
                            }
                            $cartGoods[$key]['goods_attrs']['attrs']    = trim($attrs);
                        }
                        //如果为活动商品则取值活动价格，否则取值普通售卖价格
                        if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
                            $chengpin_price = $cartGoods[$key]['goods_attrs']['goods_sku']['activity_price'];
                        }else{
                            $chengpin_price = $cartGoods[$key]['goods_attrs']['goods_sku']['goods_price'];
                        }
                        $cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($chengpin_price*$cartGoods[$key]['goods_number'],2);

                        $product['data'][] = $cartGoods[$key];
                        $product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
                        break;
                    case 4:// 戒托
                        //获取材质名称
                        $cartGoods[$key]['goods_attrs']['associateInfo']['material_name'] = M("goods_material") -> where(array('material_id'=>$cartGoods[$key]['goods_attrs']['associateInfo']['material_id']))->getField('material_name');
                        $product['count']                               += $val['goods_number'];

                        //如果为活动商品则取值活动价格，否则取值普通售卖价格
                        if(in_array($cartGoods[$key]['goods_attrs']['activity_status'],array('0','1'))){
                            $dingzhi_price = $cartGoods[$key]['goods_attrs']['activity_price'];
                        }else{
                            $dingzhi_price = $cartGoods[$key]['goods_attrs']['goods_price'];
                        }
                        $cartGoods[$key]['goods_attrs']['goods_price2'] = formatRound($dingzhi_price*$cartGoods[$key]['goods_number'],2);

						$pp_luozuan = M('cart')->where(array('goods_cart_id'=>$val['id'],'agent_id'=>C('agent_id'),'goods_type'=>1))->order('id DESC')->select();
						
						$cartGoods[$key]['pp_luozuan_count'] = count($pp_luozuan);
 						if($pp_luozuan){
							foreach($pp_luozuan as $k=>$v){
								$pp_luozuan[$k]['goods_attrs'] = unserialize($v['goods_attr']);
								$cartGoods[$key]['pp_luozuan_weight'] += $pp_luozuan[$k]['goods_attrs']['weight'];
								$luozuan_advantage           = get_luozuan_advantage($pp_luozuan[$k]['goods_attrs']['weight']);	
								$xiuzheng 					= getGoodsListPrice($pp_luozuan[$k]['goods_attrs'], $_SESSION['m']['uid'], 'luozuan',  'single');
								$pp_luozuan[$k]['goods_attrs']['price']        = $xiuzheng['price'];  
								$pp_luozuan[$k]['goods_attrs']['meikadanjia']  = $xiuzheng['cur_price'];  
								$pp_luozuan[$k]['status'] = $this->getDiamondStatus($cartGoods[$key]['goods_attrs']['certificate_number']);
								//判断钻石是否匹配到戒托中。
								$pp_luozuan[$k]['matched'] = $this->matchedDiamondInCart($cartGoods[$key]['goods_attrs']['certificate_number']);
								$cartGoods[$key]['pp_luozuan_totalprice'] += formatRound($pp_luozuan[$k]['goods_attrs']['price'],2); 
								$cartGoods[$key]['pp_luozuan'][$k] = $pp_luozuan[$k]; 
								$cartGoods[$key]['pp_luozuan']['stopgap_id'][]=$v['id'];
							}
						}					
						
						
						
						$product['data'][]                              = $cartGoods[$key];
                        $product['total'] += formatRound($cartGoods[$key]['goods_attrs']['goods_price2'],2);
						
						
						
						
                        break;
                }
            }
        }
        $total                = $product['total'] + $luozuan['total']+$sanhuo['total'];
        $data['luozuan']      = $luozuan;
        $data['sanhuo']       = $sanhuo;
        $data['count']        = $product['count'] + $luozuan['count'] + $sanhuo['count'];
        $data['product']      = $product;
        $data['cartGoods']    = $cartGoods;
        $data['total']        = $total;
        $data['total_weight'] = $sanhuo['weight'] + $luozuan['weight'];
        return $data;
    }
	
	
	
	/**
	 * auth	：someone
	 * content：改变购物车商品数量 goods_type:2 散货；3 成品； 4 定制；
	 * updatetime	：2016-5-31
	**/
	public function changeGoodsNumber(){
		$goods_number = I('post.goods_number',1);
		$cart_id = I('post.cart_id',0); 
		$goods_type = I("post.goods_type",1,'intval');
		$agent_id = C('agent_id');
		$cartGoods = M('cart')->where(array($this->userAgentKey=>$this->userAgentValue,'agent_id'=>$agent_id,'id'=>$cart_id))->find();
		if(!$cartGoods){
			$result['status'] = 0;
			$result['msg'] = '该商品不存在';
			$this->ajaxReturn($result);
		}
		$cartGoods['goods_attrs'] = unserialize($cartGoods['goods_attr']);
		if($goods_type == 2){
			$stock = $cartGoods['goods_attrs']['goods_weight'];
		}else{
			$stock = $cartGoods['goods_attrs']['goods_number'];
		}
		if($goods_type != 4 ){
			if($goods_number > $stock){
				$result['status'] = 101;
				$result['msg'] = '购买数量超过库存！现库存为'.$stock;
				$result['goods_number'] = $cartGoods['goods_number'];
				$this->ajaxReturn($result);
			}
		}
		$change_number = $goods_number-$cartGoods['goods_number'];

		if($cart_id != 0 && !empty($cartGoods)){
			if($goods_type == 2){
				$cartGoods['goods_number'] = $goods_number; 
			}else{
				$cartGoods['goods_number'] = intval($goods_number); 
			}
			
			if(M('cart')->data($cartGoods)->save()){
				
				if(in_array($cartGoods['goods_attrs']['activity_status'],array('0','1'))){
					$price	= $cartGoods['goods_attrs']['activity_price'];
				}else{
					$price	= $cartGoods['goods_attrs']['goods_price'];
				}
				$change_price = formatRound($change_number*$price);
				$result['status'] = 100;
				$result['msg'] = "修改成功";
				$result['goods_type'] = $goods_type;
				$result['goods_number'] = $cartGoods['goods_number'];
				$result['change_price'] = $change_price;
			}else{
				$result['status'] = 102;
				$result['msg'] = "修改失败";
			}
		}                    
		$this->ajaxReturn($result);
	}


	/**
	 * auth	：fangkai
	 * content：删除购物车 type:1 匹配裸钻列表页面删除，2 购物车页面的删除
	 * time	：2016-5-31
	**/
	public function deleteCart(){
		if(IS_AJAX){
			$agent_id = C('agent_id');
			$cart_ids = I('post.cart_ids');
			$type = I('post.type','','intval');
			if(!in_array($type,array(1,2))){
				$result['status'] = 0;
				$result['msg'] 	  = '操作错误';
				$this->ajaxReturn($result);
			}
			if(!isset($cart_ids)){
				$result['status'] = 0;
				$result['msg'] 	  = '操作错误';
				$this->ajaxReturn($result);
			}
			
			$cart = M('cart');
			$where['id'] = array('in',$cart_ids);
			$where['agent_id'] = $agent_id;
			$check = $cart->where($where)->select();
			if(!$check){
				$result['status'] = 0;
				$result['msg'] 	  = '购物车没有该商品';
				$this->ajaxReturn($result);
			}
			
			$action = $cart->where($where)->delete();
			if($action){
				//如果删掉了定制产品，则解绑匹配的裸钻
				if($type == 2){
					$where2['goods_cart_id'] = array('in',$cart_ids);
					$where2['agent_id'] = $agent_id;
					$where['goods_type'] = 1;
					$save['goods_cart_id'] = 0;
					$action2 = $cart->where($where2)->save($save);
					$result['type'] = 2;
				}
				$result['status'] = 100;
				$result['msg'] 	  = '删除成功';
			}else{
				$result['status'] = 0;
				$result['msg'] 	  = '删除失败';
			}
			$this->ajaxReturn($result);
		}
	}


	/**
	 * auth	：fangkai
	 * 确认订单页面
	 * 2017年8月15日 17:51:02
	**/
	public function orderConfirm(){
		$id = base64_decode(I('get.id'));
		
		if(empty($_SESSION['m']['uid'])){		$this->redirect('/public/login');		}
		if(empty($id))				{		$this->redirect('/Order/orderCart');	}
		$data_address=array();
		$user_address = M('user_address')->where("uid='".$_SESSION['m']['uid']."' AND country_id != 0  "."and is_default = 1".' and agent_id = '.C('agent_id'))->find();	
		// print_r($user_address);exit();
		if(!$user_address){
			$user_address = M('user_address')->where("uid='".$_SESSION['m']['uid']."' AND country_id != 0 AND title !=''".' and agent_id = '.C('agent_id'))->find();	
		}
		$user_delivery_mode	 = D('Common/OrderPayment')->this_dispatching_way();
		$user_pay_mode = M('payment_mode')->where("agent_id =" .C('agent_id'))->select();	
		if($user_address){
			$data_address=M("region")->where("(region_id IN (".$user_address['country_id'].','.$user_address['province_id'].','.$user_address['city_id'].','.$user_address['district_id']."))")->getField('region_name',true); 
			$data_address['address']=$user_address['address'];
			$data_address['name']=$user_address['name'];
			$data_address['phone']=$user_address['phone'];
			$data_address['address_id']=$user_address['address_id'];
			$data_address['title']=$user_address['title'];
		}
 
		$this->user_pay_mode=$user_pay_mode;
		$this->user_delivery_mode=$user_delivery_mode;
		$this->user_address = $data_address;
 		$data = $this->getCartGoods($id);
		if($data['count']){
			$this->luozuan = $data['luozuan'];
			$this->sanhuo  = $data['sanhuo'];
			$this->product = $data['product'];
			$this->data    = $data['cartGoods'];
			$this->count   = $data['count'];
			$this->total   = $data['total'];
			$this->total_weight = $data['total_weight'];
			$this->LinePayMode = M('payment_mode_line')->where(' atype=1 and agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付
			$this->display();
		}else{
			$this->redirect("/Order/orderCart");
		}

	}
	
	

	/**
	 * zhy
	 * 发票信息
	 * 2017年8月15日 17:51:02
	**/
	public function invoice(){
		
		$this->display();
	}

	
	/**
	 * 购物车提交订单商品清单
	 * zhy
	 * 2017年8月16日 15:29:07
	**/
		
	public function  CartList(){
		$id = base64_decode(I('get.id'));
 		$data = $this->getCartGoods($id);
        $this->luozuan = $data['luozuan'];
        $this->sanhuo  = $data['sanhuo'];
        $this->product = $data['product'];
        $this->data    = $data['cartGoods'];
        $this->count   = $data['count'];
        $this->total   = $data['total'];
		$this->display();	
	}	
	
	
	
	/**
	 * 购物车提交订单
	 * zhy
	 * 2016年6月16日 10:18:39
	**/
	public function Submit_Corder(){
		$id = base64_decode(I('post.id')); 
		$address_id = I('post.address_id'); 
		$radio_tips = I('post.radio_tips'); 
		// if(!is_array($id)){
			// $data['status'] = 0;	$data['msg'] = '请在购物车里面选择商品之后在提交！';$this->ajaxReturn($data);exit();
		// }
		if(!is_numeric($radio_tips)){
			$data['status'] = 0;	$data['msg'] = '请选择物流配送方式之后在提交！';$this->ajaxReturn($data);exit();
		}
		if(!is_numeric($address_id)){
			$data['status'] = 0;	$data['msg'] = '请选择默认地址或者编辑地址之后在提交！';$this->ajaxReturn($data);exit();
		}
		//$id = implode(',',$id);
	    $dataList = M('cart')->where("agent_id=".C('agent_id')." AND uid=".$_SESSION['m']['uid']." and id in ($id)")->order("goods_type ASC")->select();

		$getParentIDByUID = M('user')->where("uid='".$_SESSION['m']['uid']."' and agent_id = ".C('agent_id'))->getField('parent_id');
	 	if(empty($dataList)){	$this->redirect("/Cart");	} 
		$amount = 0;
		$order_goods = array(); 
		$GC			 = D('Common/GoodsCategory');
		foreach($dataList as $key=>$val){ 
			$dataList[$key]['data'] = $goods = unserialize($val['goods_attr']);
			$order_goods[$key]['goods_price_a_up'] = 0; 
			$order_goods[$key]['goods_price_b'] = 0;
			$order_goods[$key]['goods_price_b_up'] = 0;
			$order_goods[$key]['goods_price_c'] = 0;
			$order_goods[$key]['goods_price_c_up'] = 0; 
			$order_goods[$key]['attribute'] = $val['goods_attr'];
			$order_goods[$key]['goods_type'] = $val['goods_type'];
			$order_goods[$key]['parent_type'] = 1;
			$order_goods[$key]['parent_id'] = $this->traderId;
			if($val['goods_type'] ==1 ){      // 1为裸钻
				$order_goods[$key]['goods_number']  = 1;
				$order_goods[$key]['certificate_no']= $goods['certificate_number'];	
				//查出是否存在特惠钻石
				$preferential_luozuan	= M('goods_preferential')->where(array('agent_id'=>C('agent_id'),'gid'=>$goods['gid']))->find();
				$goods['preferential_id']	= $preferential_luozuan['id'];
				$goods['pre_discount']		= $preferential_luozuan['discount'];
				$goods = getGoodsListPrice($goods, $_SESSION['m']['uid'], 'luozuan', 'single');
				$dataList[$key]['data']['price']    = $goods['price'];  //国际报价*汇率*折扣*重量/100
				$order_goods[$key]['goods_price'] 	= formatRound($dataList[$key]['data']['price'],2);
				$order_goods[$key]['goods_id']    	= $dataList[$key]['data']['gid'];
				$order_goods[$key]['luozuan_type']  = $dataList[$key]['data']['luozuan_type'];
				$amount += formatRound($dataList[$key]['data']['price'],2);
			}else if($val['goods_type'] ==2){  //散货
				$order_goods2[$key]['attribute'] = unserialize($order_goods[$key]['attribute']);
				//查出产品的原价格
				$price = M('goods_sanhuo')->where(array('agent_id'=>C('agent_id'),'goods_id'=>$val['goods_id']))->getField('goods_price');
				//查出现阶段的散货加点
				$sanhuo_advantage = C('sanhuo_advantage');
				
				//计算新价格（不用购车价格，防止修改散货加点导致数据不正确）
				//$dataList[$key]['data']['goods_price'] = formatRound($price*(100+$sanhuo_advantage)/100,2);
				$dataList[$key]['data']                   = getGoodsListPrice($dataList[$key]['data'],$_SESSION['m']['uid'],'luozuan', 'single');
				$order_goods[$key]['goods_price2'] = formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2);
				if($order_goods[$key]['attribute']['goods_price2'] != $order_goods[$key]['goods_price2']){
					$order_goods2[$key]['attribute']['goods_price'] = $dataList[$key]['data']['goods_price'];
					$order_goods2[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
					$save['goods_attr']  = serialize($order_goods2[$key]['attribute']);
					$action = M('cart') -> where(array('agent_id'=>C('agent_id'),'uid'=>$_SESSION['m']['uid'],'goods_id'=>$val['goods_id']))->save($save);
				}
				$order_goods[$key]['goods_number'] = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['goods_price'] = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute']['goods_price'] = $dataList[$key]['data']['goods_price'];
				$order_goods[$key]['attribute']['goods_price2'] = $order_goods[$key]['goods_price2'];
				$order_goods[$key]['attribute'] = serialize($order_goods2[$key]['attribute']);
				$order_goods[$key]['goods_id'] = $dataList[$key]['data']['goods_id']; 
				$amount += formatRound($dataList[$key]['data']['goods_price']*$val['goods_number'],2); 			 	
			}else if($val['goods_type'] == 3){   // 3成品
				$order_goods[$key]['goods_number'] = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $val['goods_sn'];
				$order_goods[$key]['data']['goods_attrs']['goods_name'] = addslashes($order_goods[$key]['data']['goods_attrs']['goods_name']);
				//$dataList[$key]['data'] = getGoodsListPrice($dataList[$key]['data'], $_SESSION['App']['uid'], 'consignment','single');
				$order_goods[$key]['category_id']	 = $dataList[$key]['data']['category_id'];
				$cate_where['category_id']			 = $dataList[$key]['data']['category_id'];
				$catInfo = $GC->getCategoryInfo($cate_where);
				if($catInfo[0]['pid'] == 0){
					$order_goods[$key]['p_category_id'] = $catInfo[0]['category_id'];
				}else{
					$order_goods[$key]['p_category_id'] = $catInfo[0]['pid'];
				}
				
				//如果为活动商品则取值活动价格，否则取值普通售卖价格
				if(in_array($dataList[$key]['data']['activity_status'],array('0','1'))){
					$price = $dataList[$key]['data']['goods_sku']['activity_price'];
				}else{
					$price = $dataList[$key]['data']['goods_price'];
				}
				$order_goods[$key]['goods_price']    = formatRound($price*$val['goods_number'],2); 
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['goods_sku']['activity_price'];
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];
				 
				$order_goods[$key]['goods_id']    	 = $dataList[$key]['data']['goods_id']; 
				$amount += formatRound($price*$val['goods_number'],2);  
			}else if($val['goods_type'] ==4){  //戒托  
				$order_goods[$key]['goods_number'] = $val['goods_number'];
				$order_goods[$key]['certificate_no'] = $goods['goods_sn']; 
				//$dataList[$key]['data'] = getGoodsListPrice($dataList[$key]['data'], $_SESSION['App']['uid'], 'consignment','single');
				$order_goods[$key]['goods_id'] = $dataList[$key]['data']['goods_id']; 
				$order_goods[$key]['category_id']	 = $dataList[$key]['data']['category_id'];
				$cate_where['category_id']			 = $dataList[$key]['data']['category_id'];
				$catInfo = $GC->getCategoryInfo($cate_where);
				if($catInfo[0]['pid'] == 0){
					$order_goods[$key]['p_category_id'] = $catInfo[0]['category_id'];
				}else{
					$order_goods[$key]['p_category_id'] = $catInfo[0]['pid'];
				}
				
				//如果为活动商品则取值活动价格，否则取值普通售卖价格
				if(in_array($dataList[$key]['data']['activity_status'],array('0','1'))){
					$price = $dataList[$key]['data']['activity_price'];
				}else{
					$price = $dataList[$key]['data']['goods_price'];
				}
				$order_goods[$key]['goods_price']    = formatRound($price*$val['goods_number'],2); 
				$order_goods[$key]['activity_price'] = $dataList[$key]['data']['activity_price'];
				$order_goods[$key]['activity_status']= $dataList[$key]['data']['activity_status'];
				
				$amount += formatRound($price*$val['goods_number'],2); 			 	
			}
			$order_goods[$key]['goods_number_up'] = 0; 
			$order_goods[$key]['update_time']     = time(); 
			$order_goods[$key]['agent_id']        = C('agent_id');  
			$order_goods[$key]['dingzhi_id']   	  = $val['dingzhi_id'];  
		}    
 
		$result['note_a']        = I('orderInfo');
		$result['order_goods']   = $order_goods;
		$result['order_sn']      = date("YmdHis").rand(00,100);
		$result['uid']           = $_SESSION['m']['uid'];
		$result['order_status']  = 0;
		$result['order_confirm'] = 0;
		$result['order_payment'] = 0;
		$result['delivery_mode'] = $radio_tips;
		$result['parent_id']     = $getParentIDByUID; // 对应业务员ID
		$result['address_id']    = $address_id;
		$result['address_info']  = D("Common/UserAddress")->getAddressInfo($address_id);
		$result['order_price']   = $amount;
		$result['note']   	     = trim(I('note'));
		$result['create_time']   = time();
		$result['dollar_huilv']  = C("dollar_huilv");
		//$data['parent_type']   = $this->uType; //订单所属分销商
		$result['agent_id']      = C('agent_id');
		if($this->createOrder($result)){
			$data['status'] = 100;
			$data['result'] = base64_encode($amount);
			$data['msg'] = "订单提交成功";
			//发送用户消息
			$username = M("User")->where('uid='.$_SESSION['m']['uid'])->getField('username');
			$delivery_mode = M("delivery_mode")->where('mode_id='.$radio_tips)->getField('mode_name');
			$title = "下单成功";
			$content = "用户".$username." 您已下单成功！订单编号：".$result['order_sn']."，配送方式：".$delivery_mode."，支付方式：线下转账， 订单金额：￥". $amount;
			$s = new \Common\Model\User();	
			$s->sendMsg($_SESSION['m']['uid'],$content, $title);
		}else{
			$data['status'] = 0;
			$data['msg'] = "请勿提交相同的产品";
		}
		$this->ajaxReturn($data);  	
	}
	
	
	
		 /* 生成订单函数 **/
	public function createOrder($result){
		if($result== '' || $result == null){
			return false;
		}    
		$Order = M('order');
		$Order->startTrans();             
		$order_id = $Order->data($result)->add();  
		$order_goods_add = true;
		$order_delete = true;
		foreach($result['order_goods'] as $key=>$val){
			$val['order_id'] = $order_id; 
			if(!M('order_goods')->data($val)->add()){
				$order_goods_add = false;
			}
		} 
		foreach($result['order_goods'] as $key=>$val){
			//$val['attribute'] = addslashes($val['attribute']);
			// if(!M('cart')->where("agent_id='".C('agent_id')."' AND uid='".$_SESSION['app']['uid']."' AND goods_id='".$val['goods_id']."' AND goods_attr='".$val['attribute']."'")->delete()){
			$where['agent_id'] 	= C('agent_id');
			$where['uid']		= $_SESSION['m']['uid'];
			$where['goods_id']  = $val['goods_id'];
			$where['goods_attr']= $val['attribute'];
			if(!M('cart')->where($where)->delete()){
				$order_delete = false;
			}
		}
	
		if($order_id && $order_goods_add && $order_delete){
			$Order -> commit();
			return true;
		}else{
			$Order->rollback();
			return false;
		} 
	}
	
	
    /**
     * 订单提交成功
     * zhy	find404@foxmail.com
     * 2017年8月17日 11:27:15
     */	
	public function OrderSucceed(){
		
		$this->LinePayMode = M('payment_mode_line')->where(' atype=1 and agent_id = '.C("agent_id"))->order('ctime DESC')->find();	//线下支付
		$this->display();	
		
	}	 
	
	
}