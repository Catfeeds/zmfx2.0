<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/29
 * Time: 18:38
 */
namespace Home\Controller;
class SgoodsController extends HomeController {
    //首页菜单列表
    public function index($p=1,$n=13){

    }
	public function TongBuZuanMingCate(){
		$zmfx_category = M('goods_category')->select();
		foreach($zmfx_category as $value){

		}
	}
    public function TongBuZuanMingGoods(){
		$limit_time = date('Y-m-d H:i:s',time()-2400*3600);
		$domain										=  C('ZMALL_URL');
		$BG_Y = M('banfang_goods','zm_','ZMALL_DB');
		$BGAA_Y = M('banfang_goods_associate_attr','zm_','ZMALL_DB');

		$G_M = M('goods');
		$GC_M = M('goods_category_banfang_mp');

		$where = array(
			//'g.create_time'=>array('gt',$limit_time),

		);
		$limit = 4;
		//$zuanming_goods = $BG_Y->alias('g')->field('g.*,c.category_name c_category_name,j.jewelry_name j_jewelry_name')->where($where)->join('left join zm_banfang_category c on g.category_id= c.category_id left join zm_banfang_jewelry j on g.jewelry_id= j.jewelry_id')->limit($limit)->select();
		$zuanming_goods = $BG_Y->alias('g')->field('g.*,c.category_name c_category_name,j.jewelry_name j_jewelry_name')->where($where)->join('left join zm_banfang_category c on g.category_id= c.category_id left join zm_banfang_jewelry j on g.jewelry_id= j.jewelry_id')->select();
		//1：商品详情	2：商品属性
		$add_count = 0;
		$edit_count = 0;

		$all_add_data = array();
		if(!empty($zuanming_goods)){
			foreach($zuanming_goods as $key=>$value){
				$goods_name = ($zuanming_goods['c_category_name'] && $zuanming_goods['j_jewelry_name'] ? ($zuanming_goods['c_category_name'].' '.$zuanming_goods['j_jewelry_name'])  : $value['goods_name']);
				$category_id = $value['category_id'];
				$jewelry_id = $value['jewelry_id'];

				$my_category_id = 0;
				if($category_id && $jewelry_id){
					$my_category_id = $GC_M->where(array('banfang_category_id'=>$category_id,'banfang_jewely_id'=>$jewelry_id))->getField('category_id');
					$my_category_id = $my_category_id ? $my_category_id : 0;
				}
				if($value['content']){
					if(strpos($value['content'], $domain.'/Public/Uploads/') !== false) {																		//生产工艺
						$value['content'] 				= str_replace($domain.'/Public/Uploads/',$domain.'/Public/Uploads/', $value['content']);
					}else{
						$value['content'] 				= str_replace('/Public/Uploads/',$domain.'/Public/Uploads/', $value['content']);
					}
				}

				//商品详情数据
				$save_goods_info = array(
					'goods_name'=>$goods_name,
					'goods_sn'=>date('Ymdhis',time()).rand(10000, 99999).'_'.$value['goods_sn'],
					'agent_id'=>0,
					'goods_type'=>4,
					'category_id'=>$my_category_id,
					'content'=>$value['content'],
					'banfang_goods_id'=>$value['goods_id'],
					'is_banfang_base_goods'=>1,
					'product_status'=>($value['on_agent_sell'] && $value['sell_status']) ? 1 : 2,
					'attr_base_all'=>'',
				);
				$goods_banfang_attrs = $BGAA_Y->where('goods_id='.$value['goods_id'])->getField('attr_id id,attr_id');
				if(!empty($goods_banfang_attrs)){
					$goods_banfang_attrs = array_values($goods_banfang_attrs);
					$attr_where = array(
						'ba_code'=>'szzmzb',
						'ba_val'=>array('in',$goods_banfang_attrs),
					);
					$attr_base_all = M('attr_base')->where($attr_where)->getField('GROUP_CONCAT(id)');
					$attr_base_all = $attr_base_all ? ','.$attr_base_all.',' : '';

					$save_goods_info['attr_base_all'] = $attr_base_all;
				}
				$where_history = 'banfang_goods_id='.$value['goods_id'].' AND is_banfang_base_goods=1';
				$my_info = $G_M->where($where_history)->find();
				if(!empty($my_info) && $my_info['goods_id']>0){
					$edit_count++;
					$bool = $G_M->where(array('goods_id'=>$my_info['goods_id']))->save($save_goods_info);
				}else{
					$add_count++;
					$all_add_data[] = $save_goods_info;
				}

			}
		}

		if(!empty($all_add_data)){
			$bool = $G_M->addAll($all_add_data);
		}

		$all_count = $add_count+$edit_count;

		echo '一共同步了'.$all_count.'条记录<br/>';
		echo '新增了'.$all_count.'条记录<br/>';
		echo '更新了'.$all_count.'条记录<br/>';

	}

}