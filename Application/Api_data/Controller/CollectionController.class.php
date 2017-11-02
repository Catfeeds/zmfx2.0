<?php
/**
*	货品搜藏
*	wsl
*/
namespace Api_data\Controller;
class CollectionController extends Api_dataController{
 

    public function get_list(){

        $goods_type        = I('goods_type','');
        $goods_id          = I('goods_id','0');
        $agent_id          = C('agent_id');
		$uid     	       = C('uid');
        $where             = array();

        $where['agent_id'] = $agent_id;
        $where['uid']      = $uid;
        
        if( $goods_type ){
            $where['goods_type'] = $goods_type;
        }
        if( $goods_id ){
            $where['goods_id']   = $goods_id;
        }

        $data                      = M('collection') -> where($where) -> select();
        if(!empty($data)){
            foreach($data as &$row){
                $row['goods_attr'] = unserialize($row['goods_attr']);
            }
        }else{
            $data = new \stdclass();
        }
        $this                     -> echo_data('100','获取成功',$data);

    }
    
    
    public function cancel(){
        $goods_type        = I('goods_type','0');
        $goods_id          = I('goods_id','0');
        $id                = I('id','0','trim');
        $agent_id          = C('agent_id');
		$uid     	       = C('uid');
        if(empty($id) && empty($goods_id)){
            $this      -> echo_data('101','数据错误!');
        }
        $where             = array();
        $where['agent_id'] = $agent_id;
        $where['uid']      = $uid;
        if( empty($goods_id) ){
            if(is_numeric($id)){
                $where['id']   = array('in',array($id));
            }else{
                $id            = explode(',',$id);
                $where['id']   = array('in',$id);
            }
        }else{
            $where['goods_id']   = array('in',array($goods_id));
            $where['goods_type'] = array('in',array($goods_type));
        }
        M('collection')   -> where($where) -> delete();
        $this             -> echo_data('100','取消收藏成功!', new \stdclass());
    }
    
    /*public function add(){
        
        
        $goods_id      = I('goods_id','0','trim');
        $goods_type    = I('goods_type','0');
		
        //定制
        $materialId    = I("material_id",0);
		$diamondId     = I("luozuan_id",0);
		$deputystoneId = I("deputystone_id",0);
        $hand          = I('hand','0');			    //手寸男			
        $hand1         = I('hand1','0');			//手寸女
        //成品
        $sku_sn        = I("sku_sn",0);

        $agent_id             = C('agent_id');
		$uid     	          = C('uid');
        $c_info               = array();
        $c_info['uid']        = $uid;
        $c_info['goods_id']   = $goods_id;
        $c_info['goods_type'] = $goods_type;
        $c_info['agent_id']   = $agent_id;
        $c = M('collection') -> where($c_info) -> find(); //避免重复
        if( $c ){
            $this      -> echo_data('101','此货品已经收藏!');
        }
        $c_info['goods_conditions'] = serialize($c_info);
        $c_info['create_time']      = time();

        if(empty($goods_type) || $goods_type == '1'){
            $sglM = M('goods_luozuan');
            $data = $sglM
                        -> field('zm_goods_preferential.id as preferential_id,zm_goods_preferential.discount as pre_discount,zm_goods_luozuan.*')
                        -> where(" zm_goods_luozuan.gid = $goods_id ")
                        -> join(' left join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid ')
                        -> find();
            if(empty($data)){
                $this      -> echo_data('101','没有该商品!');die();
            }
            $point = 0;
            if($data['agent_id'] != $agent_id){
                if($data['luozuan_type'] == 1){
                    $point = D("GoodsLuozuan") -> setLuoZuanPoint('0','1');
                }else{
                    $point = D("GoodsLuozuan") -> setLuoZuanPoint();
                }
            }
            $data['dia_discount'] += $point;
            $list = getGoodsListPrice(array(0=>$data ),$uid,'luozuan');
            $data = $list[0];
            $c_info['goods_name']  = $data['goods_name'];
            $c_info['goods_sn']    = '';
            $c_info['goods_price'] = $data['price'];
            $c_info['goods_attr']  = serialize($data);
        }else if($goods_type == '3' || $goods_type == '4'){ 
            $productM       = D('Common/Goods');
            $productM      -> calculationPoint($goods_id);
            $goods_info     = $productM -> get_info($goods_id,0,' shop_agent_id= "'.$agent_id.'" and sell_status = "1" ');
            if(empty($goods_info)){
                $this      -> echo_data('101','没有该商品!');die();
            }
            if( $goods_type == '3' ){
				$temp       = $productM -> getGoodsSku($goods_id ," sku_sn = '$sku_sn' ");
                $temp       = getGoodsListPrice($temp, $uid, 'consignment', 'single');
				$goods_info['goods_price']           = formatRound($temp['goods_price'],2);
				$goods_info['activity_price']        = formatRound($temp['activity_price'],2);		//活动商品价格
				$goods_info['marketPrice']           = formatRound($temp['marketPrice'],2);
				$goods_info['goods_sku']             = $temp;
				$goods_info['consignment_advantage'] = $temp["consignment_advantage"];
				$goods_info['activity_status']	     = $temp["activity_status"];						//活动商品标识
                $c_info['goods_attr']                = serialize($goods_info);
            }else if( $goods_type == '4' ){
                list($material, $associateInfo, $luozuan, $deputystone) = $productM -> getJingGongShiData($goods_info['goods_id'], $materialId, $diamondId, $deputystoneId);
                $goods_info['size_info']    = array();
                $goods_info['size_info1']   = array();
                $goods_info['size_info2']   = array();   
                if( empty($goods_info['price_model']) ){
					$goods_info['goods_price'] = $productM -> getJingGongShiPrice($material,$associateInfo,$luozuan,$deputystone);
				}
				if( $goods_info['price_model'] == '1' ){
					$category_name = M('goods_category')->where(" category_id = '$goods_info[category_id]' ")->getField('category_name');
					$is_size       = InStringByLikeSearch($category_name,array( '项链' ,'手链','钻戒','戒','对戒'));
					if( $is_size ){
                        if($hand){
                            $where_ext = "min_size <= '$hand' and max_size >= '$hand' and ";
                        }else{
                            $where_ext = "";
                        }
						$goods_info['size_info']    = $productM -> getGoodsAssociateSizeAfterAddPoint( " $where_ext goods_id = $goods_info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
						$goods_info['goods_price']  = $goods_info['size_info']['size_price'];
						$is_dui                     = InStringByLikeSearch($category_name,array('对戒'));
						if($is_dui){
                            if($hand){
                                $where_ext1 = "min_size <= '$hand' and max_size >= '$hand' and ";
                            }else{
                                $where_ext1 = "";
                            }
                            if($hand1){
                                $where_ext2 = "min_size <= '$hand1' and max_size >= '$hand1' and ";
                            }else{
                                $where_ext2 = "";
                            }
							$size_info1                  = $productM -> getGoodsAssociateSizeAfterAddPoint( " $where_ext1 sex = '1' and goods_id = $goods_info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
							$size_info2                  = $productM -> getGoodsAssociateSizeAfterAddPoint( " $where_ext2 sex = '2' and goods_id = $goods_info[goods_id] and ".'material_id = '.$associateInfo['material_id']);
							$goods_info['goods_price']   = $size_info1['size_price'] + $size_info2['size_price'];
                            $goods_info['size_info1']    = $size_info1;
                            $goods_info['size_info2']    = $size_info2;
                            unset($goods_info['size_info']);
						}
					} else {
						$goods_info['goods_price']       = $associateInfo['fixed_price'];
					}
                    $goods_info['material']      = $material;
                    $goods_info['associateInfo'] = $associateInfo;
                    $goods_info['luozuan']       = $luozuan;
                    $goods_info['deputystone']   = $deputystone;
				}
                $goods_info                          = getGoodsListPrice($goods_info, $uid, 'consignment','single');
                $c_info['goods_attr']                = serialize($goods_info);
            }
            $c_info['goods_name']  = $goods_info['goods_name'];
            $c_info['goods_sn']    = $goods_info['goods_sn'];
            $c_info['goods_price'] = $goods_info['goods_price'];
        }else{
            $this      -> echo_data('101','没有该商品!');die();
        }
        M('collection')   -> add($c_info);
        $this             -> echo_data('100','添加收藏成功!', new \stdclass());
    }*/

    public function add(){
        $goods_type = I('goods_type', '0', 'intval');
        $param      = array(
            'goods_type' => $goods_type,
            'gid'        => I('goods_id', '0', 'trim'),
            'agent_id'   => C('agent_id'),
            'uid'        => C('uid')
        );
        $continue   = true;
        switch($goods_type){
            case 0:
                break;
            case 1:
                break;
            case 2:
                break;
            case 3:
                $param['sku_sn']       = I("sku_sn", 0);
                $param['goods_number'] = 1;
                break;
            case 4:
                $param['materialId']     = I("material_id", 0);
                $param['diamondId']      = I("luozuan_id", 0);
                $param['deputystoneId'] = I("deputystone_id", 0);
                $param['hand']           = I('hand', '0');
                $param['hand1']          = I('hand1', '0');
                $param['goods_number']   = 1;
                $param['luozuan']        = I("luozuan", 0);
                $param['word']           = I("word", "");
                $param['word1']          = I("word1", "");
                $param['sd_id']          = I('sd_id', '');
                $param['sd_id1']         = I("sd_id1", "");
                break;
            default:
                $continue = false;
                break;
        }
        if($continue){
            $result = D('Common/Collection')->addCollection($param);
        }else{
            $result = array(
                'msg'    => '添加失败',
                'status' => 0,
                'id'     => 0
            );
        }
        if($result['status']!=100){
            $result['status'] = 101;
        }
        $this             -> echo_data($result['status'],$result['msg'], new \stdclass());
    }

    public function check(){
        $goods_id   = I('goods_id','0','trim');
        $goods_type = I('goods_type','0');
        $agent_id   = C('agent_id');
		$uid     	= C('uid');
        $c_info     = array();
        $c_info['uid']        = $uid;
        $c_info['goods_id']   = $goods_id;
        $c_info['goods_type'] = $goods_type;
        $c_info['agent_id']   = $agent_id;
        $c = M('collection') -> where($c_info) -> find(); //避免重复
        if( $c ){
            $this      -> echo_data('101','此货品已经收藏!');
        }
        $this      -> echo_data('100','可以收藏!');
    }

    public function add_collection_to_cart(){
        $id   = I("id",0);
        if(is_numeric($id)){
            $id_arr   = array($id);
        }else{
            $id_arr   = explode(',',$id);
        }
        /*if(!empty($id_arr) && is_array($id_arr)){
            $data =  D('Common/Collection')->addCollectionToCart($id_arr);
            $this -> echo_data('100','添加购物车成功!');
        }else{
            $this -> echo_data('101','参数错误!');
        }*/
        $data = array(
            'status'=>0,
            'msg'=>'添加购物车失败'
        );
        if(!empty($id_arr) && is_array($id_arr)){
            $data =  D('Common/Collection')->addCollectionToCart($id_arr);
        }
        if($data['status']!=100){
            $data['status'] = 101;
        }
        $this -> echo_data($data['status'],$data['msg']);

    }
}