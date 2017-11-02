<?php
/**
 * User: 张超豪
 * Date: 2016/7/18 
 */
namespace Admin\Model;
use Think\Model;

class OrderCartModel extends Model{

    
    public function getOrderCartGoodsList($where){
        $father_agent_id = get_parent_id();       
         /////////////////////的到where语句
        if(is_array($where)){
            foreach($where as $key => $value){
                $where_new['c.'.$key] = $value; 
            }
            $sql = $this->where($where_new)->fetchSql(true)->find();
            //SELECT * FROM `zm_cart` WHERE `agent_id` = 2 AND `uid` = 203923 AND `goods_type` <> 2 LIMIT 1
            $str_array = explode('WHERE',str_replace(array("LIMIT"),'WHERE',$sql)); //得到where 和 limit 中间的那一块
            $str_where = $str_array[1];
        }else{
            $str_where = $where;
        }
         //////////////查找购物车中商品
        $res = $this->alias('c')->field('c.*, gl.gid as have_goods')
                    ->join('left join zm_goods_luozuan  as gl on (gl.gid = c.goods_id and gl.goods_number=1)')
                    ->where('c.goods_type = 1 and '.$str_where)                  
                    ->union('select c.*, gs.goods_id as have_goods from zm_order_cart as c LEFT JOIN zm_goods_sanhuo  as gs on (gs.goods_id = c.goods_id and  gs.goods_weight > 0)  where c.goods_type = 2 and'.$str_where)
                    ->union('select c.*, g.goods_id as have_goods from zm_order_cart as c LEFT JOIN zm_goods_shop  as g on (g.shop_agent_id = '.$father_agent_id.' and g.goods_id = c.goods_id and g.sell_status=1) where c.goods_type > 2 and '.$str_where)                
                    ->select();
        return $res;
    }

    //返回当前客户的点数，is_parent>0的时候，只返回上级点数
    
 
    public function getUpdateOrderCartGoodsList($where = array()){
        $cart = M('order_cart');
        
        $res = $this->getOrderCartGoodsList($where);             
        ////商品进行检查
        foreach($res as $key=>$value ){

            if(empty($value['have_goods'])){
                continue;
            }

            if($value['goods_type'] == 1){  //更新裸钻信息
                $goods    = M('goods_luozuan')->where("`gid`='".$value['goods_id']."' AND `goods_number` = 1 ") -> find();
                $goodsObj = D("Common/GoodsLuozuan");//初始化默认的读取的是白钻的加点

                if($goods['luozuan_type'] == 1){
                    //设置彩钻参数
                    $goodsObj -> setLuoZuanPoint('0','1');
                }

                $goods = $goodsObj -> where("`gid`='".$value['goods_id']."' AND `goods_number` = 1 ") -> find();
                if($goods){
                    $goods      = getCaigouGoodsListPrice($goods,$uid,'luozuan', 'single');
                    $goods_attr = serialize($goods);

                    if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
                        $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField(array('goods_attr'=>$goods_attr,'goods_sn'=>$goods['certificate_number']));
                       
                        $res[$key]['goods_attr'] = $goods_attr;
                        $res[$key]['goods_sn']   = $goods['certificate_number'];
                    }
                }
            }elseif($value['goods_type'] == 2){//更新散货
                $GSobj = D('GoodsSanhuo');
                $where = array('goods_id'=>array('eq',$value['goods_id']));


                $goods         = $GSobj -> join('join zm_goods_sanhuo_type on zm_goods_sanhuo_type.tid = zm_goods_sanhuo.tid ') -> where($where) -> field('zm_goods_sanhuo.*,zm_goods_sanhuo_type.type_name,zm_goods_sanhuo_type.type_name_en') -> find();
                
                if($goods){
                    $goods = getCaigouGoodsListPrice($goods,$uid,'sanhuo', 'single');
                    $goods_attr = serialize($goods);
                    if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
                        $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField(array('goods_attr'=>$goods_attr,'goods_sn'=>$goods['goods_sn']));
                       
                        $res[$key]['goods_attr'] = $goods_attr;
                        $res[$key]['goods_sn']   = $goods['goods_sn'];
                    }
                }
            }elseif($value['goods_type'] == 3){//更新成品
                $G            = D("Common/Goods");
                $attr         = unserialize($value['goods_attr']);
                $sku_sn       = $attr['goods_sku']['sku_sn'];
                 
                $goods_type   = $value['goods_type'];
                $goods        = $G -> get_info($value['goods_id'],0,'shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
                if($goods){
                    
                    $temp = $G -> getGoodsSku($value['goods_id'] ," sku_sn = '$sku_sn' ");
                    if(empty($temp)){
                        continue;  //sku_sn发生变化则跳过
                    }
                    if($temp['goods_number'] < 1){ //某一sku无货
                        $res[$key]['have_goods'] = 0;//无货
                        continue;
                    }                   
                    $temp['goods_type']             = $goods_type;

                    $temp                           = getCaigouGoodsListPrice($temp, $uid, 'consignment', 'single');

                    $goods['goods_price']           = formatRound($temp['goods_price'],2);
                    $goods['activity_price']        = formatRound($temp['activity_price'],2);       //活动商品价格
                    $goods['marketPrice']           = formatRound($temp['marketPrice'],2);
                    $goods['goods_sku']             = $temp;

                    $goods['consignment_advantage'] = $temp["consignment_advantage"];
                    $goods['activity_status']       = $temp["activity_status"];                     //活动商品标识

                    unset($goodsData);
                    $goodsData['goods_attr']        = serialize($goods);
                    $goodsData['goods_sn']          = $goods['goods_sn'];

                    if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
                        $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField($goodsData);                 
                        $res[$key]['goods_attr'] = $goodsData['goods_attr'];
                        $res[$key]['goods_sn']   = $goodsData['goods_sn'];
                    }
                }           
            }elseif($value['goods_type'] == 4){//更新订制
                $attr = unserialize($value['goods_attr']);   

                unset($goodsData);
                $goodsData['goods_attr']        = serialize($this->getUpdateDingzhiGoodsInfo($attr, $value['goods_id']));

                if($cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->find()){
                    $cart->where("agent_id='".C('agent_id')."' AND id ='".$value['id']."'")->setField($goodsData);                 
                    $res[$key]['goods_attr'] = $goodsData['goods_attr'];
                }

            }
        }
        return $res;  
    }

    //去掉购物车中无货的
    public function delNullGoods ($goods_list = array()){
        foreach($goods_list as $key => $value){
            if(empty($value['have_goods'])){
                unset($goods_list[$key]);
            }
        }
        return $goods_list;

    }

    public function getUpdateDingzhiGoodsInfo($attr = array(), $gid=0){

        $hand          = $attr['hand'];
        $word          = $attr['word']; 
        $hand1         = $attr['hand1'];
        $word1         = $attr['word1']; 
        //$gid           = $value['goods_id'];
        $goods_type    = 4;
        $diamondId     = intval($attr['luozuanInfo']['gal_id']);

        $materialId    = intval($attr['associateInfo']['material_id']);
        $deputystoneId = intval($attr['deputystone']['gad_id']);
        
        $G               = D("Common/Goods");
        $goodsInfo       = $G -> get_info($gid, 0,' shop_agent_id= "'.C('agent_id').'" and sell_status = "1" ');
        $luozuanInfo     = $G -> getGoodsAssociateLuozuanAfterAddPoint("goods_id=$gid AND gal_id=$diamondId");
        $associateInfo   = $G -> getGoodsAssociateInfoAfterAddPoint("goods_id=$gid AND zm_goods_associate_info.material_id=$materialId ");
        
        if($deputystoneId and $deputystoneId!='undefined') {
            $deputystone = $G -> getGoodsAssociateDeputystoneAfterAddPoint("gad_id=$deputystoneId");
        }

        if($luozuan >0){
            $certificate_number           = D("GoodsLuozuan") -> where(" gid = $luozuan ") -> getField("certificate_number");
            $goodsInfo['matchedDiamond']  = $certificate_number;
        }  
                        
        if($luozuanInfo && $associateInfo && $goodsInfo){
            $material = $G -> getGoodsMaterialAfterAddPoint(" material_id = $materialId ");
            if( !$goodsInfo['price_model'] ){
                $goodsInfo['goods_price'] = $G -> getJingGongShiPrice($material,$associateInfo,$luozuanInfo,$deputystone);
            }else{
                $category_name = M('goods_category') -> where(" category_id = '".$goodsInfo['category_id']."'") -> getField('category_name');
                $is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
                if( $is_size ){
                    $size                     = $G->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand' and max_size >= '$hand' and material_id = '$materialId' and goods_id = '$gid' ");
                    $goodsInfo['goods_price'] = $size['size_price'];
                    if($hand1){
                        $size = $G->getGoodsAssociateSizeAfterAddPoint(" min_size <= '$hand1' and max_size >= '$hand1' and material_id = '$materialId' and goods_id = '$gid' ");
                        $goodsInfo['goods_price'] = $size['size_price']+$goodsInfo['goods_price'];
                    }
                } else {
                    $ass = $G->getGoodsAssociateInfoAfterAddPoint(" zm_goods_associate_info.material_id = '$materialId' and goods_id = '$gid' ");
                    $goodsInfo['goods_price'] = $ass['fixed_price'];
                }
            }
			
			$banfang_goods_id = M('goods') -> where("goods_id = '".$gid."'" ) -> getField('banfang_goods_id');
			$goods_agent_id   = M('goods') -> where("goods_id = '".$gid."'" ) -> getField('agent_id');							
			$additional_price = M('goods_associate_info') -> where("goods_id = '".$gid."'  and additional_price > 0 " ) -> getField('additional_price');
			$point2       	  = $trader = M('trader') -> where(' t_agent_id = ' . C('agent_id')) -> getField('consignment_advantage');
			if( $agent_id != $goods_agent_id || $banfang_goods_id >0){
				if($agent_id!='0'){
					$goodsInfo['goods_price']	= $goodsInfo['goods_price'] +( 100 + $point2 ) / 100 * $additional_price;
				}
			}
			
            $goodsInfo                  = getCaigouGoodsListPrice($goodsInfo, $uid, 'consignment', 'single');
            $goodsInfo['associateInfo'] = $associateInfo;
            $goodsInfo['luozuanInfo']   = $luozuanInfo; 
            $goodsInfo['deputystone']   = $deputystone;
            $goodsInfo['hand']          = $hand;
            $goodsInfo['word']          = $word;
            $goodsInfo['hand1']         = $hand1;
            $goodsInfo['word1']         = $word1;                   
            $goodsInfo['sd_images']     = $attr['sd_images'];                   
            //$goodsInfo['sd_images1']    = $attr['sd_images1'];                               
            return $goodsInfo;
        }       
    }
   
}

