<?php
/**
 * 产品相关页面
 * 产品类型 goods_type  1:裸钻 2:散货 3：成品 4:戒托 5:代销
 */
namespace Home\Controller\Dingzhi\Szzmzb;
use Think\Model;
use Home\Controller\NewHomeController;
class Order extends NewHomeController{
	public function __construct() {
		parent::__construct();
		$this->agent_id = C("agent_id");
		$this->uid		= $_SESSION['web']['uid'];
	}

    //加入购物车
    public function dingzhi_addToCart_b2c(){
        $goodsInfo = (new \Common\Model\Dingzhi\Szzmzb\Goods())->get_dinzhi_goodsInfo(I('banfang_goods_id'),I('activity_status'));
        foreach ($goodsInfo['selected']['checked'] as $k => $v) { //判断选中加入购物车
            $goodsInfo_new = $goodsInfo;
            if($v == 1){ //选中商品 
                $goodsInfo_new['selected']['associate_luozuan_id'] = $goodsInfo['selected']['associate_luozuan_id'][$k];
                $goodsInfo_new['selected']['deputystone_type'] = $goodsInfo['selected']['deputystone_type'][$k];
                $goodsInfo_new['selected']['deputystone_attribute'] = $goodsInfo['selected']['deputystone_attribute'][$k];
                $goodsInfo_new['selected']['technology'] = $goodsInfo['selected']['technology'][$k];
                $goodsInfo_new['selected']['luozuan_weight'] = $goodsInfo['selected']['luozuan_weight'][$k];
                $goodsInfo_new['selected']['luozuan_number'] = $goodsInfo['selected']['luozuan_number'][$k];
                $goodsInfo_new['selected']['goods_number'] = $goodsInfo['selected']['goods_number'][$k];
                $goodsInfo_new['selected']['hand'] = $goodsInfo['selected']['hand'][$k];
                $goodsInfo_new['selected']['word'] = $goodsInfo['selected']['word'][$k];
                $goodsInfo_new['selected']['material_weight'] = $goodsInfo['selected']['material_weight'][$k];
                $goodsInfo_new['selected']['material_name'] = $goodsInfo['selected']['material_name'][$k];
                $goodsInfo_new['selected']['associate_luozuan_gal_id'] = $goodsInfo['selected']['associate_luozuan_gal_id'][$k];
                $goodsInfo_new['selected']['associate_luozuan_weights_name'] = $goodsInfo['selected']['associate_luozuan_weights_name'][$k];
                $goodsInfo_new['selected']['goods_name_bm'] = $goodsInfo['selected']['goods_name_bm'][$k];
                $goodsInfo_new['selected']['shape'] = $goodsInfo['selected']['shape'][$k];
                $goodsInfo_new['selected']['shape_name'] = $goodsInfo['selected']['shape_name'][$k];
                $goodsInfo_new['selected']['ct_mm'] = $goodsInfo['selected']['ct_mm'][$k];
                $goodsInfo_new['selected']['deputystone_name'] = $goodsInfo['selected']['deputystone_name'][$k];
                $goodsInfo_new['selected']['deputystone_number'] = $goodsInfo['selected']['deputystone_number'][$k];
                $goodsInfo_new['selected']['deputystone_weight'] = $goodsInfo['selected']['deputystone_weight'][$k];
                $goodsInfo_new['goods_price'] = $goodsInfo['selected']['goods_price_one'][$k];
                $goodsInfo_new['activity_price'] = $goodsInfo['selected']['activity_goods_price_one'][$k];
                unset($goodsInfo_new['selected']['checked']);//删除波动参数，否则购物车重复

                $data['uid'] = $this->uid;
                $data['goods_id'] = I('gid');
                $data['goods_type'] = 16;
                $data['goods_attr'] = serialize($goodsInfo_new);
                $data['goods_sn'] = I('goods_sn');
                $data['agent_id'] = $this->agent_id;
                $goods_number = $goodsInfo['selected']['goods_number'][$k];
                $cart = M('cart')->where($data)->find();
                if($cart){
                    $bool = M('cart')->where($data)->setInc('goods_number',$goods_number);
                    $bool?$id[] = $cart['id']:'';
                }else{
                    $data['goods_number'] = $goods_number;
                    $data['session'] = session_id();
                    $bool = M('cart')->add($data);
                    $bool?$id[] = $bool:'';
                }
            }
        }

        if($id){
            echo json_encode(['status'=>100,'msg'=>'定制成功','id' => implode($id, ',')]);
        }else{
            echo json_encode(['status'=>0,'msg'=>'定制失败，请重新提交']);
        }
    }

    //加入收藏
    public function dingzhi_addCollection_b2c(){
        $goodsInfo = (new \Common\Model\Dingzhi\Szzmzb\Goods())->get_dinzhi_goodsInfo(I('banfang_goods_id'),I('activity_status'));
        foreach ($goodsInfo['selected']['checked'] as $k => $v) { //判断选中加入收藏
            $goodsInfo_new = $goodsInfo;
            if($v == 1){ //选中商品 
                $goodsInfo_new['selected']['associate_luozuan_id'] = $goodsInfo['selected']['associate_luozuan_id'][$k];
                $goodsInfo_new['selected']['deputystone_type'] = $goodsInfo['selected']['deputystone_type'][$k];
                $goodsInfo_new['selected']['deputystone_attribute'] = $goodsInfo['selected']['deputystone_attribute'][$k];
                $goodsInfo_new['selected']['technology'] = $goodsInfo['selected']['technology'][$k];
                $goodsInfo_new['selected']['luozuan_weight'] = $goodsInfo['selected']['luozuan_weight'][$k];
                $goodsInfo_new['selected']['luozuan_number'] = $goodsInfo['selected']['luozuan_number'][$k];
                $goodsInfo_new['selected']['goods_number'] = $goodsInfo['selected']['goods_number'][$k];
                $goodsInfo_new['selected']['hand'] = $goodsInfo['selected']['hand'][$k];
                $goodsInfo_new['selected']['word'] = $goodsInfo['selected']['word'][$k];
                $goodsInfo_new['selected']['material_weight'] = $goodsInfo['selected']['material_weight'][$k];
                $goodsInfo_new['selected']['material_name'] = $goodsInfo['selected']['material_name'][$k];
                $goodsInfo_new['selected']['associate_luozuan_gal_id'] = $goodsInfo['selected']['associate_luozuan_gal_id'][$k];
                $goodsInfo_new['selected']['associate_luozuan_weights_name'] = $goodsInfo['selected']['associate_luozuan_weights_name'][$k];
                $goodsInfo_new['selected']['goods_name_bm'] = $goodsInfo['selected']['goods_name_bm'][$k];
                $goodsInfo_new['selected']['shape'] = $goodsInfo['selected']['shape'][$k];
                $goodsInfo_new['selected']['shape_name'] = $goodsInfo['selected']['shape_name'][$k];
                $goodsInfo_new['selected']['ct_mm'] = $goodsInfo['selected']['ct_mm'][$k];
                $goodsInfo_new['selected']['deputystone_name'] = $goodsInfo['selected']['deputystone_name'][$k];
                $goodsInfo_new['selected']['deputystone_number'] = $goodsInfo['selected']['deputystone_number'][$k];
                $goodsInfo_new['selected']['deputystone_weight'] = $goodsInfo['selected']['deputystone_weight'][$k];
                $goodsInfo_new['goods_price'] = $goodsInfo['selected']['goods_price_one'][$k];
                $goodsInfo_new['activity_price'] = $goodsInfo['selected']['activity_goods_price_one'][$k];
                unset($goodsInfo_new['selected']['checked']);//删除波动参数，否则购物车重复

                $data['uid'] = $this->uid;
                $data['goods_id'] = I('gid');
                $data['goods_type'] = 16;
                $data['goods_name'] = $goodsInfo_new['goods_name'];
                $data['goods_sn'] = $goodsInfo_new['goods_sn'];
                $data['goods_price'] = formatPrice($goodsInfo_new['goods_price']/$goodsInfo_new['selected']['goods_number']);
                $data['goods_attr'] = serialize($goodsInfo_new);
                $data['agent_id'] = $this->agent_id;
                $data['create_time'] = time();

                $where = 'uid='.$data['uid'].' AND goods_id='.$data['goods_id'].' AND goods_type='.$data['goods_type'].' AND agent_id='.$data['agent_id'];
                $collection = M('collection')->where($where)->find();
                if(!$collection){
                    $bool = M('collection')->add($data);
                }
            }
        }
        if($bool){
            echo json_encode(['status'=>100,'id'=>$bool,'src'=>'/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png']);
        }else{
            echo json_encode(['status'=>0]);
        }
    }

}
