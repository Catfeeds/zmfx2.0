<?php

namespace Common\Model\Dingzhi\Szzmzb;
class BanfangGoods extends Goods{


    public function getList($where = array(),$page_id=1,$page_size=20,$order='goods_id desc',$uid='',$field_add='',$join_add='',$field_add_need_to_condition=false){
		//新增3个参数$field_add,$join_add,$field_add_need_to_condition
		//$field_add_need_to_condition这个参数true表示需要把这个条件逮到count里面去,false代表不用
		if($order=='goods_id desc' && MODULE_NAME=='Home'){
			$order='sort desc,goods_id desc';
		}
		
        if( empty( $where ) ){
            $where   = array();
        }
        if( $page_id < 1 ){
            $page_id = 1;
        }
        $limit = (($page_id-1)*$page_size).','.$page_size;
		$join  = 'join zm_banfang_category on zm_banfang_goods.category_id = zm_banfang_category.category_id ';
        $join .= 'join zm_banfang_jewelry on zm_banfang_jewelry.jewelry_id = zm_banfang_goods.jewelry_id ';
		$join .= 'left join zm_admin_user on zm_admin_user.user_id = zm_banfang_goods.create_uid ';
		$join .= 'left join zm_banfang_goods_associate_attr on zm_banfang_goods.goods_id = zm_banfang_goods_associate_attr.goods_id ';
		if(!empty($join_add)){
			$join .= $join_add.' ';
		}
        $field = "zm_banfang_goods.*,zm_banfang_category.category_name,zm_banfang_jewelry.jewelry_name";
		if(!empty($field_add)){
			$field .= ','.$field_add;
		}
		if($field_add_need_to_condition){
			$count = $this ->field($field) -> where($where) -> join($join) -> getField('count( distinct `zm_banfang_goods`.`goods_id` )');
		}else{
			$count = $this -> where($where) -> join($join) -> getField('count( distinct `zm_banfang_goods`.`goods_id` )');
		}
        $list  = $this -> where( $where ) -> join($join) -> group('goods_id') -> limit($limit) -> order($order) -> field( $field )-> select(); 

		//2017-1-9，查询出收藏的商品
		$collection = M('collection')->where(array('uid'=>$uid,'goods_type'=>4))->select();
		//print_r($collection);exit;
        if( empty($list) ){
            $list = array();
        }
		foreach($list as $key=>$val){
			//如果有收藏的商品，则给予状态为1；
			foreach($collection as $k=>$v){
				if($val['goods_id'] == $v['goods_id']){
					$list[$key]['collection'] = 1 ;
					break;
				}else{
					$list[$key]['collection'] = 0 ;
				}
			}			
			$imageList	                 = $this->getGoodsImage($val['goods_id']);
			$list[$key]['image']         = $imageList;
            //$list[$key]['price_info']  = $this -> getGoodsPriceInfo( $val['goods_id'] ,0,0,'SAGH');
            //$list[$key]['goods_price'] = $list[$key]['price_info']['price'];
            if( !empty($val['create_uid']) ){
                $list[$key]['create_name']      = M('admin_user') -> where("user_id='$val[create_uid]'") -> getField('nickname');
            }
            if( !empty($val['check_uid']) ){
                $list[$key]['check_name']      = M('admin_user') -> where("user_id='$val[check_uid]'") -> getField('nickname');
            }
            $goods_id[]             = $val['goods_id'];
            $goods_id_str          .= $goods_id_str?','.$val['goods_id']:$val['goods_id'];
		}

        if(!empty($list)){
            $weight_list = M('banfang_goods_associate_luozuan') -> alias('l1')
                        -> where(array('`l1`.goods_id' => array('in',$goods_id))) 
                        -> group('`l1`.goods_id') 
                        -> order('`l1`.luozuan_weight ASC')
                        -> field('l1.goods_id,min(l1.`luozuan_weight`) as "luozuan_weight_min",max(l1.`luozuan_weight`) as "luozuan_weight_max",luozuan_size') -> select();
            $caizhi_list   =  M('banfang_goods_associate_luozuan')
                                -> alias('l1')
                                -> join('(
                                        SELECT
                                            max(
                                                zm_banfang_goods_associate_luozuan.associate_luozuan_id
                                            ) AS associate_luozuan_id,
                                            goods_id
                                        FROM
                                            zm_banfang_goods_associate_luozuan
                                        WHERE
                                            zm_banfang_goods_associate_luozuan.goods_id in ('.$goods_id_str.')
                                        GROUP BY
                                            goods_id
                                    ) AS l2 ON l1.goods_id = l2.goods_id and l2.associate_luozuan_id = l1.associate_luozuan_id ') 
                                -> where(array('`l1`.goods_id' => array('in',$goods_id))) -> getField('l1.goods_id,l1.material_weight');

            //查询情侣戒中男戒主石大小   add by huanglinfeng 2017/9/1
            $man_couple_where = array(
                'l1.goods_id'=>array('in',$goods_id),
                'l1.luozuan_sort'=>0,
                'zm.is_couple_ring'=>1
            );

            //求男戒钻石的最大重量值和最小重量值
            $couple_man_weight_list = M('banfang_goods_associate_luozuan') -> alias('l1')
                ->join('join zm_banfang_goods as zm on zm.goods_id = l1.goods_id')
                -> where($man_couple_where)
                -> group('`l1`.goods_id')
                -> order('`l1`.luozuan_weight ASC')
                -> field('l1.goods_id,min(l1.`luozuan_weight`) as "luozuan_weight_min",max(l1.`luozuan_weight`) as "luozuan_weight_max",luozuan_size') -> select();

            $man_couple_con = array(
                'l1.goods_id'=>array('in',$goods_id),
                'zm.is_couple_ring'=>1
            );

            //求男戒钻石中最后一条数据（同一个产品有多条数据）的金重值
            $man_weight_list   =  M('banfang_goods_associate_luozuan')
                -> alias('l1')
                ->join('join zm_banfang_goods as zm on zm.goods_id = l1.goods_id')
                -> join('(
                                        SELECT
                                            max(
                                                zm_banfang_goods_associate_luozuan.associate_luozuan_id
                                            ) AS associate_luozuan_id,
                                            goods_id
                                        FROM
                                            zm_banfang_goods_associate_luozuan
                                        WHERE
                                            zm_banfang_goods_associate_luozuan.goods_id in ('.$goods_id_str.')
                                            and  zm_banfang_goods_associate_luozuan.luozuan_sort=0
                                        GROUP BY
                                            goods_id
                                    ) AS l2 ON l1.goods_id = l2.goods_id and l2.associate_luozuan_id = l1.associate_luozuan_id ')
                -> where($man_couple_con) -> getField('l1.goods_id,l1.material_weight');


            //查询情侣戒中女戒主石大小和金重   add by huanglinfeng 2017/9/1
            $women_couple_where = array(
                'l1.goods_id'=>array('in',$goods_id),
                'l1.luozuan_sort'=>1,
                'zm.is_couple_ring'=>1
            );
            $couple_women_weight_list = M('banfang_goods_associate_luozuan') -> alias('l1')
                ->join('join zm_banfang_goods as zm on zm.goods_id = l1.goods_id')
                -> where($women_couple_where)
                -> group('`l1`.goods_id')
                -> order('`l1`.luozuan_weight ASC')
                -> field('l1.goods_id,min(l1.`luozuan_weight`) as "luozuan_weight_min",max(l1.`luozuan_weight`) as "luozuan_weight_max",luozuan_size') -> select();

            $women_couple_con = array(
                'l1.goods_id'=>array('in',$goods_id),
                'zm.is_couple_ring'=>1
            );
            $women_weight_list   =  M('banfang_goods_associate_luozuan')
                -> alias('l1')
                ->join('join zm_banfang_goods as zm on zm.goods_id = l1.goods_id')
                -> join('(
                                        SELECT
                                            max(
                                                zm_banfang_goods_associate_luozuan.associate_luozuan_id
                                            ) AS associate_luozuan_id,
                                            goods_id
                                        FROM
                                            zm_banfang_goods_associate_luozuan
                                        WHERE
                                            zm_banfang_goods_associate_luozuan.goods_id in ('.$goods_id_str.')
                                            and zm_banfang_goods_associate_luozuan.luozuan_sort=1
                                        GROUP BY
                                            goods_id
                                    ) AS l2 ON l1.goods_id = l2.goods_id and l2.associate_luozuan_id = l1.associate_luozuan_id ')
                -> where($women_couple_con) -> getField('l1.goods_id,l1.material_weight');

            foreach($list as $key=>$val){
                //如果有收藏的商品，则给予状态为1；
					foreach($weight_list as $k=>$v){
						if( $val['goods_id'] == $v['goods_id'] ){
							$v['material_weight'] = $caizhi_list[$v['goods_id']];
								//当规格为mm的时候，截取出来。		 zhy 	2017年7月7日 15:38:49
								if($val['category_id']!='7'){
									$v['luozuan_weight_min'] = strtok($v['luozuan_size'],'*');
									$v['luozuan_weight_max'] = ltrim(strstr($v['luozuan_size'], '*'),'*');
								}
							$list[$key]['caizhi'] = $v;
							break;
						}
					}

                foreach($couple_man_weight_list as $ke=>$ve){
                    if( $val['goods_id'] == $ve['goods_id'] ){
                        $ve['material_weight'] = $man_weight_list[$ve['goods_id']];
                        $list[$key]['couple_man'] = $ve;
                        break;
                    }
                }

                foreach($couple_women_weight_list as $kel=>$vel){
                    if( $val['goods_id'] == $vel['goods_id'] ){
                        $vel['material_weight'] = $women_weight_list[$vel['goods_id']];
                        $list[$key]['couple_women'] = $vel;
                        break;
                    }
                }
            }	
        }
        $data           = array(
            'page_id'   => $page_id,
            'page_size' => $page_size,
            'list'      => $list,
            'count'     => $count,
        );
        return $data;
        
    }

    public function getListAttr($goods_id){

    }


    /*查询情侣戒中男戒的金重和大小
     * @author huanglinfeng
     * @time 2017/9/1
     * */
    public function get(){
        //查询情侣戒中男戒主石大小   add by huanglinfeng 2017/9/1
        $man_couple_where = array(
            'l1.goods_id'=>array('in',$goods_id),
            'l1.luozuan_sort'=>0,
            'zm.is_couple_ring'=>1
        );
        $couple_man_weight_list = M('banfang_goods_associate_luozuan') -> alias('l1')
            ->join('join zm_banfang_goods as zm on zm.goods_id = l1.goods_id')
            -> where($man_couple_where)
            -> group('`l1`.goods_id')
            -> order('`l1`.luozuan_weight ASC')
            -> field('l1.goods_id,min(l1.`luozuan_weight`) as "luozuan_weight_min",max(l1.`luozuan_weight`) as "luozuan_weight_max",luozuan_size') -> select();

        $man_couple_con = array(
            'l1.goods_id'=>array('in',$goods_id),
            'zm.is_couple_ring'=>1
        );
        $man_weight_list   =  M('banfang_goods_associate_luozuan')
            -> alias('l1')
            ->join('join zm_banfang_goods as zm on zm.goods_id = l1.goods_id')
            -> join('(
                                        SELECT
                                            max(
                                                zm_banfang_goods_associate_luozuan.associate_luozuan_id
                                            ) AS associate_luozuan_id,
                                            goods_id
                                        FROM
                                            zm_banfang_goods_associate_luozuan
                                        WHERE
                                            zm_banfang_goods_associate_luozuan.goods_id in ('.$goods_id_str.')
                                            and  zm_banfang_goods_associate_luozuan.luozuan_sort=0
                                        GROUP BY
                                            goods_id
                                    ) AS l2 ON l1.goods_id = l2.goods_id and l2.associate_luozuan_id = l1.associate_luozuan_id ')
            -> where($man_couple_con) -> getField('l1.goods_id,l1.material_weight');


    }


    /**
     * 组装主石数据
     * 根据产品类型(普通，组合戒，对戒)组装不同的列表数据
     *
     * @author wangkun
     * @date 2017/8/18
     * @param array $luozuanList
     * @param array optional $data 附加数据
     *
     * @result array 组装后的数组
     */
    private function buildLuozuanList(array $luozuanList = [], array $data = [])
    {
        $buildResult = [];

        $isCoupleRing = isset($data['isCoupleRing']) ? $data['isCoupleRing'] : 0;
        $isCombinationRing = isset($data['isCombinationRing']) ? $data['isCombinationRing'] : 0;

        if($luozuanList){
            $deputystoneObj = M('szzmzb_goods_associate_deputystone');
            foreach($luozuanList as $k=>$r) {

                $r['luozuan_weight'] = doubleval($r['luozuan_weight']);
                $r['luozuan_size'] = trim($r['luozuan_size']);
                $r['material_weight'] = doubleval($r['material_weight']);
                $r['luozuan_shape_id'] = str_pad($r['luozuan_shape_id'], 3, '0', STR_PAD_LEFT);

                // 获取副石信息
                $deputyData = $deputystoneObj->where([
                    'associate_luozuan_id' => $r['associate_luozuan_id']
                ])->select();

                foreach($deputyData as &$_r) {
                    $_r['deputystone_shape_id'] = str_pad($_r['deputystone_shape_id'], 3, '0', STR_PAD_LEFT);
                    $_r['deputystone_weight'] = doubleval($_r['deputystone_weight']);
                    $_r['deputystone_size'] = trim($_r['deputystone_size']);
                }
                if($deputyData) {
                    $r['deputystone'] = $deputyData;
                } else {
                    $r['deputystone'] = [];
                }

                $buildResult['associate_luozuan'][] = $r;
                $buildResult['associate_luozuan_data_all'][$r['luozuan_sort']][$r['luozuan_relation_id']] = $r;
            }
        } else {
            $buildResult['associate_luozuan'] = [];
            $buildResult['associate_luozuan_data_all'] = [];
        }

        return $buildResult;
    }

	public function getInfo($goods_id,$where = array()){

		if(empty($where)){
            $where = array( 'zm_szzmzb_goods.goods_id' => $goods_id );
        }else{
            $where['zm_szzmzb_goods.goods_id'] = $goods_id;
        }

        $goodsInfo = M('szzmzb_goods')->alias('zm_szzmzb_goods')
                          ->field('zm_szzmzb_goods.*,zm_szzmzb_category.is_luozuan')
						  ->where($where)
						  ->join('left join zm_szzmzb_category on zm_szzmzb_goods.category_id = zm_szzmzb_category.category_id')
						  ->find();

        if(empty($goodsInfo) || !is_array($goodsInfo)){
            return false;
        }
        // 是否对戒/组合戒
        $isCoupleRing = isset($goodsInfo['is_couple_ring']) && $goodsInfo['is_couple_ring'] == 1 ? 1 : 0;
        $isCombinationRing = isset($goodsInfo['is_combination_ring']) && $goodsInfo['is_combination_ring'] == 1 ? 1 : 0;

        // 图片
        $imagesModel = M('szzmzb_goods_images');
        $images = $imagesModel->where( " goods_id = $goods_id ")->select();
        if($images){
            $goodsInfo['images'] = $images;
        }else{
            $goodsInfo['images'] = array();
        }

        //CAD文件
        $fileModel = M('szzmzb_goods_file');
        $files = $fileModel->where( " goods_id = $goods_id ")->select();
        if($files){
            $goodsInfo['files'] = $files;
        }else{
            $goodsInfo['files'] = array();
        }

        // 属性
        $attrsObj = M('szzmzb_goods_associate_attr');
        $attrs = $attrsObj->where(" goods_id = $goods_id ")->select();
        if($attrs){
            $goodsInfo['attrs'] = $attrs;
        }else{
            $goodsInfo['attrs'] = array();
        }

        // 主石
        $luozuanModel = M('szzmzb_goods_associate_luozuan');
        $associateLuozuanList = $luozuanModel
            ->alias('bgal')
            ->where( " goods_id = $goods_id ")
            ->join('left join zm_szzmzb_goods_luozuan_shape on bgal.luozuan_shape_id = zm_szzmzb_goods_luozuan_shape.shape_id')
            ->order('bgal.luozuan_sort asc, bgal.luozuan_relation_id asc, bgal.associate_luozuan_id desc')
            ->select();

        // 组装主石数据
        $buildResult = $this->buildLuozuanList($associateLuozuanList, compact('isCoupleRing', 'isCombinationRing'));

        if(isset($buildResult['associate_luozuan'])){
            $goodsInfo['associate_luozuan'] = $buildResult['associate_luozuan'];
        }

        if(isset($buildResult['associate_luozuan_data_all'])){
            $goodsInfo['associate_luozuan_data_all'] = $buildResult['associate_luozuan_data_all'];
        }

        $goodsInfo['price_info'] = $this->getGoodsPriceInfo($goodsInfo['goods_id'], 0, 0, 'SAGH');
        // 添加产品时已将价格计算后存入goods表，这里无需重新计算 wangkun 2017/8/29
//        $goodsInfo['goods_price'] = $goodsInfo['price_info']['price'];

        return $goodsInfo;
	}


    public function checkGoodsSn($goods_id,$goods_sn){
        
        $where             = array();
        $where['goods_id'] = array('not in',"$goods_id");
        $where['goods_sn'] = array('exp'," like '%$goods_sn%' ");
        $info              = $this->fetchsql( false ) -> where( $where ) -> order(' goods_id desc ') -> limit('0,15') -> field('goods_sn') -> select();
        return $info;
    
    }

    //添加产品数据
    public function saveInfo($info,$attrs=array(),$associate_luozuan=array(),$images=array(),$files=array()){
		
		$this -> startTrans();

        $category_name      = M('banfang_category') -> where("category_id = $info[category_id] ") -> getField('category_name');
        $jewelry_name       = M('banfang_jewelry')  -> where("jewelry_id  = $info[jewelry_id] ")  -> getField('jewelry_name');
        $info['goods_name'] = $category_name.$jewelry_name;
        $is_luozuan         = M('banfang_category') -> where("category_id = $info[category_id] ") -> getField('is_luozuan');

        if( $goods_id = intval($info['goods_id']) ){
            $this     -> where(" goods_id = $info[goods_id] ") -> save( $info );

            //CAD文件修改   add by haunglinfeng 2017/9/21
            $cad = array();
            for($i=0;$i<count($files['goods_number']);$i++){
                if(!empty($files['goods_file_name'][$i]) && !empty($files['file_path'][$i])){
                    $cad[$i]['goods_id'] = $goods_id;
                    $cad[$i]['goods_number'] = $files['goods_number'][$i];
                    $cad[$i]['goods_file_name'] = $files['goods_file_name'][$i];
                    $cad[$i]['file_path'] = $files['file_path'][$i];
                    $cad[$i]['edit_time'] = date('Y-m-d H:i:s',time());
                }
            }
            //因为编辑时还可以添加数据不能执行批量更新  所以直接删除重新添加数据
            if( !empty($cad) ){
                M('banfang_goods_file') -> where ( "goods_id = '$goods_id'" ) -> delete();
                M('banfang_goods_file') -> addAll( $cad );
                M('banfang_goods')->where(array('goods_id'=>$goods_id))->save(array('goods_cad_file'=>1));
            }

        }else{
            $info['sell_status']  = '1'; //默认上架
            $info['sell_on_time'] = date('Y-m-d H:i:s');
            $goods_id = $this -> add( $info );

            //CAD文件保存  add by haunglinfeng 2017/9/21
            $cad = array();
            for($i=0;$i<count($files['goods_number']);$i++){
                if(!empty($files['goods_file_name'][$i]) && !empty($files['file_path'][$i])){
                    $cad[$i]['goods_id'] = $goods_id;
                    $cad[$i]['goods_number'] = $files['goods_number'][$i];
                    $cad[$i]['goods_file_name'] = $files['goods_file_name'][$i];
                    $cad[$i]['file_path'] = $files['file_path'][$i];
                }
            }
            if(!empty($cad)){
                M('banfang_goods_file')->addAll($cad);
                M('banfang_goods')->where(array('goods_id'=>$goods_id))->save(array('goods_cad_file'=>1));
            }

			if( $this -> getError() ){
				$this -> rollback();
				return array(false,$this->getError());
			}
        }

        //属性
        if( $attrs ){
            $attrObj      = M('banfang_attr');
            $goodsAttrObj = M('banfang_goods_associate_attr');
            $data         = array();
            foreach($attrs['attr_id1'] as $key=>$row){
                $_info['goods_id']     = $goods_id;
                if( empty($attrs['attr_id2'][$row]) ){
                    $_info['level']    = '1';
                    $_info['attr_id']  = $row;
                    $_info['attr_id1'] = $row;
                    $_info['attr_id2'] = '0';
                    $_info['attr_id3'] = '0';
                    if( $material_items = $attrObj -> where(" attr_id = $_info[attr_id] ") -> getField('material_items') ){
                        $_info['material_items'] = $material_items;
                    } else {
                        $_info['material_items'] = '';
                    }
                    $data[] = $_info;
                    continue;
                }
                if( empty($attrs['attr_id3'][$attrs['attr_id2'][$row]]) ){
                    $_info['level']     = '2';
                    $_info['attr_id']   = $attrs['attr_id2'][$row];
                    $_info['attr_id1']  = $row;
                    $_info['attr_id2']  = $attrs['attr_id2'][$row];
                    $_info['attr_id3']  = '0';
                    if( $material_items = $attrObj -> where(" attr_id = $_info[attr_id] ") -> getField('material_items') ){
                        $_info['material_items'] = $material_items;
                    } else {
                        $_info['material_items'] = '';
                    }
                    $data[] = $_info;
                    continue;
                } else {
                    $_info['level']     = '3';
                    $_info['attr_id']   = intval($attrs['attr_id3'][$attrs['attr_id2'][$row]]);
                    $_info['attr_id1']  = $row;
                    $_info['attr_id2']  = intval($attrs['attr_id2'][$row]);
                    $_info['attr_id3']  = intval($attrs['attr_id3'][$attrs['attr_id2'][$row]]);
                    if( $material_items = $attrObj -> where(" attr_id = $_info[attr_id] ") -> getField('material_items') ){
                        $_info['material_items'] = $material_items;
                    } else {
                        $_info['material_items'] = '';
                    }
                    $data[] = $_info;
                    continue;
                }
            }
            $goodsAttrObj -> where ( "goods_id = '$goods_id'" ) -> delete();
            if( !empty($data) ){
                $goodsAttrObj -> addAll( $data );               
            }
            if( $goodsAttrObj -> getError() ){
				$goodsAttrObj    -> rollback();
				return array(false,$goodsAttrObj->getError());
			}
        }
        $luozuanObj               = M('banfang_goods_associate_luozuan');
        $deputystoneObj           = M('banfang_goods_associate_deputystone');
        $luozuanObj              -> where ( "goods_id = '$goods_id'" ) -> delete();
        $deputystoneObj          -> where ( "goods_id = '$goods_id'" ) -> delete();
        //默认计算主石的id
        $price_luozuan = array();
        //主石属性
        if( $associate_luozuan ){
            foreach($associate_luozuan as $row){
                $data                     = array();
                $data['goods_id']         = $goods_id;
                $data['material_weight']  = $row['material_weight'] ?: 0;
                $data['luozuan_shape_id'] = str_pad($row['luozuan_shape_id'],3,'0',STR_PAD_LEFT); //$row['luozuan_shape_id'];
                $data['luozuan_number']   = intval($row['luozuan_number']);

                if( $is_luozuan ){
                    $data['luozuan_weight'] = $row['luozuan_weight'] ?: 0;
                    $data['luozuan_size']   = $row['luozuan_size'] ?: 0;
                }else{
                    $data['luozuan_weight'] = '0';
                    $data['luozuan_size']   = $row['luozuan_size'] ?: 0;
                }
                $data['luozuan_sort']       = $row['luozuan_sort'];
                $data['luozuan_relation_id']= $row['luozuan_relation_id'];
                $goods_luozuan_id           = $luozuanObj -> add( $data );
                if($data['luozuan_relation_id']==0){
                    $price_luozuan[] = $goods_luozuan_id;
                }

				if( $luozuanObj -> getError() ){
					$this  -> rollback();
					return array(false,$luozuanObj->getError());
				}
                if($row['deputystone'] && !empty($row['deputystone']['deputystone_shape_id'])){
                    foreach($row['deputystone']['deputystone_shape_id'] as $k => $r){
                        $data                         = array();
                        $data['deputystone_shape_id'] = str_pad($row['deputystone']['deputystone_shape_id'][$k],3,'0',STR_PAD_LEFT);
                        $data['deputystone_size']     = $row['deputystone']['deputystone_size'][$k] ?: 0;
                        $data['deputystone_weight']   = $row['deputystone']['deputystone_weight'][$k] ?: 0;
                        $data['deputystone_number']   = $row['deputystone']['deputystone_number'][$k] ?: 0;
                        $data['associate_luozuan_id'] = $goods_luozuan_id;
                        $data['goods_id']             = $goods_id;
                        $deputystoneObj              -> add( $data );
                        
						if( $deputystoneObj -> getError() ){
							$this->rollback();
							return array(false,$deputystoneObj->getError());
						}
                    }
                }
            }
        }

        //图片
        if( $images ){
            $imagesObj        = M('banfang_goods_images');
            $where            = array();
            foreach( $images as $row ){
                $images_ids[] = $row; 
            }
            //存放刚刚上传的数据
            $where['images_id'] = array('in',$images_ids);
            $where['goods_id']  = array('in',"0,$goods_id");
            $imagesObj          -> where ( $where ) -> save(array('goods_id'=>$goods_id));
            //删除以删除的数据
            $where['images_id'] = array('not in',$images_ids);
            $where['goods_id']  = $goods_id;
            $imagesObj          -> where ( $where ) -> delete();
            //多个商品共享的数据
            $where['images_id'] = array('in',$images_ids);
            $where['goods_id']  = array('not in',"0,$goods_id");
            $list = $imagesObj -> where ( $where ) -> select();
            if( $list ){
                foreach($list as $row){
                    $row['goods_id'] = $goods_id;
                    unset($row['images_id'],$row['create_time']);
                    $imagesObj -> add($row);
                }
            }
			if( $imagesObj -> getError() ){
				$this      -> rollback();
				return array(false,$imagesObj->getError());
			}
        }
		$this               -> commit();
        $goods_price = 0;
        if(!empty($price_luozuan)){
            foreach($price_luozuan as $l_id){
                $price_info          = $this -> getGoodsPriceInfo( $goods_id,0,$l_id,'SAGH' );
                $goods_price += $price_info['price'];
            }
        }else{
            $price_info          = $this -> getGoodsPriceInfo( $goods_id,0,0,'SAGH' );
            $goods_price += $price_info['price'];
        }
        
        $data                = array();
        $data['goods_price'] = $goods_price;
        $info                = $this->getFilterFieldData($goods_id);
        $data                = array_merge($data,$info);
        $this               -> where(" goods_id = $goods_id ") -> save( $data );

        return array(true,$goods_id);
    }

	
    /**
     * 选择分销之后，导入到分销产品库
     * zhy	find404@foxmail.com
     * 2017年4月28日 10:15:37
     */
	 
    public function ImportIntoDistribution ($info,$attrs=array(),$associate_luozuan=array(),$images=array()){
		$ZmfxGoods						= M('goods'							,'zm_','ZMFX_DB');
		$ZmfxGoodsCategory				= M('goods_category'				,'zm_','ZMFX_DB');
		$ZmfxGoodsAttributesValue		= M('goods_attributes_value'		,'zm_','ZMFX_DB');
		$ZmfxGoodsAttributes			= M('goods_associate_attributes'	,'zm_','ZMFX_DB');
		$ZmfxGoodsAttributesInfo		= M('goods_associate_info'			,'zm_','ZMFX_DB');	
		$ZmfxGoodsAttributesLuozuan		= M('goods_associate_luozuan'		,'zm_','ZMFX_DB');
		$ZmfxGoodsAttributesDeputystone	= M('goods_associate_deputystone'	,'zm_','ZMFX_DB');
		$ZmfxGoodsImages				= M('goods_images'					,'zm_','ZMFX_DB');
        $category_name      			= M('banfang_category') -> where("category_id = $info[category_id] ") -> getField('category_name');
        $jewelry_name       			= M('banfang_jewelry')  -> where("jewelry_id  = $info[jewelry_id] ")  -> getField('jewelry_name');
		
		$default_agent					= '888888888';	
        $info['goods_name'] 			= $category_name.'('.$jewelry_name.')';																//商品名
		
		switch($info['category_id']){
			case '1':
				$category_id 			= '58';
				break;
			case '2':
				$category_id 			= '59';
				break;
			case '3':
				$category_id 			= '113';
				break;
			case '4':
				return false;			//配件
				break;
			case '5':
				$category_id 			= '113';
				break;
			case '6':
				$category_id 			= '93';
				break;
			case '7':
				$category_id 			= '54';
				break;
		}

		
		$this -> startTrans();
		
		if($category_id){																										//商品2级分类
			if($info['jewelry_id']=='3'	|| $info['jewelry_id']=='2'){
				$info['category_id'] =	'62';
			}else{
				$like_category = $ZmfxGoodsCategory->where(' pid = '.$category_id)->select();
				if($like_category){
					array_walk($like_category, function ($ClosureData) use ($jewelry_name, &$like_category_id){
									similar_text($ClosureData['category_name'], $jewelry_name,$like_num);
										if($like_num>1){
											$like_category_id[$ClosureData['category_id']]=$like_num;
										}
									}
								);
					if(count($like_category_id)>0){
						$info['category_id'] =array_search(max($like_category_id), $like_category_id);								//类别
					}else{
						$info['category_id'] = $category_id;
					}
				}
			}

		}
		
		$info['product_status']			= 0;
		$info['banfang_goods_sn']		= $info['goods_sn'];	
		$info['banfang_goods_id']		= $info['goods_id'];
		unset($info['goods_id']);		
		unset($info['goods_sn']);
		
		$info['goods_sn']       = date('Ymdhis',time()).rand(10000, 99999);														//默认分销编号	
		
        $price_info    			= $this -> getGoodsPriceInfo($info['banfang_goods_id'],0,0,'SAGH');
		$price					= $info['goods_price'] = $price_info['price'];	

		if($info['content']){
			$info['content'] = str_replace(array("/Public/Uploads/","http://szzmzb.com/Public/Uploads/"),array("http://szzmzb.com/Public/Uploads/","http://szzmzb.com/Public/Uploads/"), $info['content']);
		}		
		
		$info['thumb']			= M('banfang_goods_images')-> where('goods_id = '.$info['banfang_goods_id']) -> getField('small_path');

		$ZmfxGoodsId =$ZmfxGoods -> where(' banfang_goods_id ='.$info['banfang_goods_id'].' and agent_id = '.$default_agent) -> getField('goods_id');	
		
		if($ZmfxGoodsId){
			$ZmfxGoodsStatus							= $ZmfxGoods 						->where('banfang_goods_id ='.$info['banfang_goods_id']) -> save($info);			
			$DeZmfxGoodsImagesStatus					= $ZmfxGoodsImages					->where('goods_id = '.$ZmfxGoodsId)->delete();
			$DeZmfxGoodsAttributesStatus				= $ZmfxGoodsAttributes				->where('goods_id = '.$ZmfxGoodsId)->delete();
			$DeZmfxGoodsAttributesInfoStatus			= $ZmfxGoodsAttributesInfo			->where('goods_id = '.$ZmfxGoodsId)->delete();			
			$DeZmfxGoodsAttributesLuozuanStatus			= $ZmfxGoodsAttributesLuozuan		->where('goods_id = '.$ZmfxGoodsId)->delete();
			$DeZmfxGoodsAttributesDeputystoneStatus		= $ZmfxGoodsAttributesDeputystone 	->where('goods_id = '.$ZmfxGoodsId)->delete();
			if ($ZmfxGoodsStatus === false || $DeZmfxGoodsImagesStatus === false || $DeZmfxGoodsAttributesStatus === false || $DeZmfxGoodsAttributesInfoStatus === false || $DeZmfxGoodsAttributesLuozuanStatus === false || $DeZmfxGoodsAttributesDeputystoneStatus === false){
				$this->rollback();
				return false;
			}
		}else{
			$info['goods_type']			= '4';
			$info['agent_id']			= $default_agent;				
			$ZmfxGoodsId 				= $ZmfxGoods -> add($info);																		 
		}
		
		
		
 
		if($associate_luozuan){																		//主石筛选，只有钻石才有
				$associate_luozuan				  		= array_values($associate_luozuan);
				$associate_luozuan_luozuan_weight 		= array_column($associate_luozuan, 'luozuan_weight');
				$associate_luozuan_size_mm 				= array_column($associate_luozuan, 'luozuan_size');				
				$associate_luozuan_luozuan_weight_unique= array_unique($associate_luozuan_luozuan_weight);
				
		}
 
        //属性
		$attrs			= implode(',',call_user_func_array('array_merge',$attrs));
		if(!empty($attrs)){	
			$attr_name      = M('banfang_attr')->field('attr_name') -> where('attr_id in ('.$attrs.')') ->select();
			$attr_names 	= implode('\',\'',array_column($attr_name, 'attr_name'));		
			if(strpos($attr_names, 'CNC工艺') !== false) {																		//生产工艺
				$attr_names 	=	str_replace("CNC工艺","CNC",$attr_names);
			}
			if(strpos($attr_names, '分色') !== false) {																			//生产工艺
				$attr_names 	=	str_replace("分色","真分色",$attr_names);
				$material_match_28 = '35';
			}

			if(strpos($attr_names, '分件') !== false) {																			 
				$material_match_8 = '35';
			}
			
			if(strpos($attr_names, '六围一') !== false) {																		 
				$material_match_19 = '30';
			}		

			if(strpos($attr_names, '八围一') !== false) {																		 
				$material_match_21 = '45';
			}				
			
			$Zmfxattrs1 		= $ZmfxGoodsAttributesValue->where(' attr_value_name in ( \''.$attr_names.'\')')->select();		//属性
			$Zmfxattrs2 		= $ZmfxGoodsAttributesValue->where(" attr_id = '8'")->select();									//价格
			$Zmfxattrs3 		= $ZmfxGoodsAttributesValue->where(" attr_id = '7'")->select();									//价格			
			$Zmfxattrs 			= array_merge($Zmfxattrs1, $Zmfxattrs2);
 
			foreach ($Zmfxattrs3 as $key1=>$val1){
				foreach ($associate_luozuan_luozuan_weight_unique as $key=>$val){
					if(strpos($val1['attr_value_name'], '-') !== false) {											//钻重
						$min 		= (float)strstr($val1['attr_value_name'], '-',true);
						$max 		= (float)str_replace("-",'',str_replace("CT",'',strstr($val1['attr_value_name'], '-')));
						if($min<=(float)$val && (float)$val<=$max){
							$_AttributesValue[$key1+200]['attr_id']		= $val1['attr_id'];
							$_AttributesValue[$key1+200]['attr_code']	= $val1['attr_code'];										
						}
					}else if(($val1['attr_value_name']=='0.2CT以下') && (float)$val<0.2){
							$_AttributesValue[$key1+200]['attr_id']		= '7';
							$_AttributesValue[$key1+200]['attr_code']	= '1';
					}else if(($val1['attr_value_name']=='4CT以上') && (float)$val>4){
							$_AttributesValue[$key1+200]['attr_id']		= '7';
							$_AttributesValue[$key1+200]['attr_code']	= '256';							
					}
					
					if(isset($_AttributesValue[$key1+200]['attr_code'])){
							$_AttributesValue[$key1+200]['category_id']	= $category_id;
							$_AttributesValue[$key1+200]['goods_id']	= $ZmfxGoodsId;	
					}
				}
			}
			
 
			foreach ($Zmfxattrs as $key=>$val){
				if(strpos($val['attr_value_name'], '-') !== false) {											//空拖价格
					$min = (float)strstr($val['attr_value_name'], '-',true);
					$max = (float)str_replace("-",'',strstr($val['attr_value_name'], '-'));									
					if($min<=(float)$price && $max>=(float)$price){
						$_AttributesValue[888]['attr_id']		= $val['attr_id'];
						$_AttributesValue[888]['attr_code']		= $val['attr_code'];										
					}
				}else if(($val['attr_value_name']=='400以下') && (float)$price<400){
						$_AttributesValue[888]['attr_id']		= '8';
						$_AttributesValue[888]['attr_code']		= '1';
				}else if(($val['attr_value_name']=='2000以上')  && (float)$price>2000){
						$_AttributesValue[888]['attr_id']		= '8';
						$_AttributesValue[888]['attr_code']		= '256';							
				}else if(preg_match('/[\x80-\xff]./',$val['attr_value_name']) && ($val['attr_value_name']!='400以下')  && ($val['attr_value_name']!='2000以上')){
						$_AttributesValue[$key]['attr_id']		= $val['attr_id'];
						$_AttributesValue[$key]['attr_code']	= $val['attr_code'];
						$_AttributesValue[$key]['category_id']	= $category_id;
						$_AttributesValue[$key]['goods_id']		= $ZmfxGoodsId;	
				}else if($val['attr_value_name']=='CNC') {
						$_AttributesValue[$key]['attr_id']		= $val['attr_id'];
						$_AttributesValue[$key]['attr_code']	= $val['attr_code'];
						$_AttributesValue[$key]['category_id']	= $category_id;
						$_AttributesValue[$key]['goods_id']		= $ZmfxGoodsId;	
				}
				if(isset($_AttributesValue[888]['attr_code'])){
						$_AttributesValue[888]['category_id']	= $category_id;
						$_AttributesValue[888]['goods_id']		= $ZmfxGoodsId;
				}
			}
 
 
			$AddZmfxGoodsAttributesStatus 	=	$ZmfxGoodsAttributes -> addAll($_AttributesValue);  
        }

		//规格
		if($associate_luozuan){
 
			$associate_luozuan_material_weight_result	= array_column($associate_luozuan, 'material_weight');	//获取的金重		
			$associate_luozuan_number					= array_column($associate_luozuan, 'luozuan_number');	//获取的颗
			$associate_luozuan_shape_id					= array_column($associate_luozuan, 'luozuan_shape_id');	//获取的shenp_id
 
			
			$associate_luozuan_array_id		  =array('1','2','3');												//默认产品材质
			if(isset($_POST['material_match_type'])){															//是否选择pt950
				array_push($associate_luozuan_array_id,'4','5');	
			}
			
			if(isset($material_match_28)){																		//是否分色
				array_push($associate_luozuan_array_id,'51');
			}
			

		}
		
		
 
		//基本工费		
 		if(is_array($associate_luozuan)){
 
			list($k_work_price,$k_loss_price,$p_work_price,$p_loss_price)	= M('banfang_base_process_items')->alias('item')->join(' LEFT JOIN zm_banfang_base_process_fee as fee on fee.base_items_id = item.base_items_id ')->where(' jewelry_id = '.$info['jewelry_id'])->field("au750_fee_price_general as '0',au750_loss_general as '1' ,pt950_fee_price_general  as '2',pt950_loss_general  as '3'")->find();

			$i		= count($associate_luozuan_luozuan_weight);
			$idi	= count($associate_luozuan_array_id);
 
			if(isset($material_match_28)){
				$k_loss_price		= $k_loss_price+1;
				$p_loss_price		= $p_loss_price+1;					
			}
			if($i==1){
				$associate_luozuan_array_ids=$associate_luozuan_array_id;
			}else if($i==2){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==3){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==4){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==5){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==6){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==7){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==8){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==9){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==10){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==11){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==12){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}else if($i==13){
				$associate_luozuan_array_ids=array_merge($associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id,$associate_luozuan_array_id); 
			}

	
				
			foreach($associate_luozuan_array_ids as $key_luozuan=>$val_luozuan){
				$_AttributesLuozuanNumber								= '';
				$_AttributesLuozuan[$key_luozuan]['price']				= '';				
				$_AttributesLuozuan[$key_luozuan]['goods_id'] 			= $ZmfxGoodsId;
				$_AttributesLuozuan[$key_luozuan]['material_id'] 		= $val_luozuan;

	
				if($idi==3){
					$key_luozuan_start_num=2;
				}else if($idi==4){
					$key_luozuan_start_num=3;
				}else if($idi==5){
					$key_luozuan_start_num=4;
				}else if($idi==6){
					$key_luozuan_start_num=5;
				}else if($idi==7){
					$key_luozuan_start_num=6;
				}else if($idi==8){
					$key_luozuan_start_num=7;
				}else if($idi==9){
					$key_luozuan_start_num=8;
				}else if($idi==10){
					$key_luozuan_start_num=9;
				}else if($idi==11){
					$key_luozuan_start_num=10;
				}else if($idi==12){
					$key_luozuan_start_num=11;
				}else if($idi==13){
					$key_luozuan_start_num=12;
				}else{
					$key_luozuan_start_num=5;
				}
 

			
				if($key_luozuan<=$key_luozuan_start_num){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[0] ?	$associate_luozuan_luozuan_weight[0]	:$associate_luozuan_size_mm[0];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[0]=='12' ? 0 : $associate_luozuan_shape_id[0];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[0];
				}else if ($key_luozuan>($key_luozuan_start_num) && $key_luozuan<=($key_luozuan_start_num+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[1] ?	$associate_luozuan_luozuan_weight[1]	:$associate_luozuan_size_mm[1];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[1]=='12' ? 0 : $associate_luozuan_shape_id[1];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[1];						
				}else if($key_luozuan>($key_luozuan_start_num+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[2] ?	$associate_luozuan_luozuan_weight[2]	:$associate_luozuan_size_mm[2];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[2]=='12' ? 0 : $associate_luozuan_shape_id[2];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[2];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[3] ?	$associate_luozuan_luozuan_weight[3]	:$associate_luozuan_size_mm[3];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[3]=='12' ? 0 : $associate_luozuan_shape_id[3];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[3];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[4] ?	$associate_luozuan_luozuan_weight[4]	:$associate_luozuan_size_mm[4];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[4]=='12' ? 0 : $associate_luozuan_shape_id[4];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[4];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[5] ?	$associate_luozuan_luozuan_weight[5]	:$associate_luozuan_size_mm[5];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[5]=='12' ? 0 : $associate_luozuan_shape_id[5];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[5];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[6] ?	$associate_luozuan_luozuan_weight[6]	:$associate_luozuan_size_mm[6];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[6]=='12' ? 0 : $associate_luozuan_shape_id[6];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[6];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[7] ?	$associate_luozuan_luozuan_weight[7]	:$associate_luozuan_size_mm[7];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[7]=='12' ? 0 : $associate_luozuan_shape_id[7];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[7];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[8] ?	$associate_luozuan_luozuan_weight[8]	:$associate_luozuan_size_mm[8];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[8]=='12' ? 0 : $associate_luozuan_shape_id[8];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[8];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[9] ?	$associate_luozuan_luozuan_weight[9]	:$associate_luozuan_size_mm[9];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[9]=='12' ? 0 : $associate_luozuan_shape_id[9];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[9];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[10] ?	$associate_luozuan_luozuan_weight[10]	:$associate_luozuan_size_mm[10];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[10]=='12' ? 0 : $associate_luozuan_shape_id[10];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[10];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[11] ?	$associate_luozuan_luozuan_weight[11]	:$associate_luozuan_size_mm[11];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[12]=='12' ? 0 : $associate_luozuan_shape_id[12];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[12];					
				}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
						$_AttributesLuozuan[$key_luozuan]['weights_name']						= 	$associate_luozuan_luozuan_weight[12] ?	$associate_luozuan_luozuan_weight[12]	:$associate_luozuan_size_mm[12];
						$_AttributesLuozuan[$key_luozuan]['shape_id']							= 	$associate_luozuan_shape_id[13]=='12' ? 0 : $associate_luozuan_shape_id[13];
						$_AttributesLuozuanNumber												=	$associate_luozuan_number[13];					
				}
				
				$_AttributesLuozuan[$key_luozuan]['price']	= '0';
				if($category_id =='54') {
					$banfang_luozuan_premiums = M('banfang_luozuan_premiums')->where(' user_type = 0')->select();
					foreach($banfang_luozuan_premiums as $key_premiums=>$val_premiums){
						if($val_premiums['min_weight']<=$_AttributesLuozuan[$key_luozuan]['weights_name'] 	  && $val_premiums['max_weight']>=$_AttributesLuozuan[$key_luozuan]['weights_name']){
							$_AttributesLuozuan[$key_luozuan]['price']	= $val_premiums['fee_price'];					
						}
					}
				}
	 
 
				if($_AttributesLuozuan[$key_luozuan]['price']!='0'  && $_AttributesLuozuanNumber){
					$_AttributesLuozuan[$key_luozuan]['price']=$_AttributesLuozuan[$key_luozuan]['price']*$_AttributesLuozuanNumber;
				}
 
			}
 
 
 
 
			$AddZmfxGoodsAttributesLuozuanStatus 		=	$ZmfxGoodsAttributesLuozuan		->addAll($_AttributesLuozuan);

			
	 
			//副石
			$associate_luozuan_deputystone    = array();
			array_walk($associate_luozuan, function ($via,$keg) use ($ZmfxGoodsId,&$associate_luozuan_deputystone){
				if($via['deputystone']['deputystone_shape_id']){
					array_walk($via['deputystone']['deputystone_shape_id'], function ($via_deputystone,$keg_deputystone) use ($ZmfxGoodsId,$via,$keg,&$associate_luozuan_deputystone){
							$key=mt_rand(10, 2222).substr(microtime(), 2,2);
							if($via_deputystone=='001'){
								$associate_luozuan_deputystone[$key]['deputystone_name']					= 'SAGH'.$via['deputystone']['deputystone_number'][$keg_deputystone].'颗'.$via['deputystone']['deputystone_weight'][$keg_deputystone];
								$associate_luozuan_deputystone[$key+100]['deputystone_name']				= 'SBGH'.$via['deputystone']['deputystone_number'][$keg_deputystone].'颗'.$via['deputystone']['deputystone_weight'][$keg_deputystone];

								if(isset($associate_luozuan_deputystone[$key]['deputystone_name']) && isset($associate_luozuan_deputystone[$key+100]['deputystone_name'])){
									$associate_luozuan_deputystone[$key]['deputystone_price']				= '0';
									$associate_luozuan_deputystone[$key+100]['deputystone_price']			= '0';
								}
								
								$banfang_deputystone = M('banfang_deputystone')->where(' user_type = 0')->select();
								
								foreach($banfang_deputystone as $key_deputystone=>$val_deputystone){
									if($val_deputystone['min_weight']<=$via['deputystone']['deputystone_weight'][$keg_deputystone] && $val_deputystone['max_weight']>=$via['deputystone']['deputystone_weight'][$keg_deputystone]){
										$associate_luozuan_deputystone[$key]['deputystone_price']				= $via['deputystone']['deputystone_weight'][$keg_deputystone]*$val_deputystone['sagh_price']*$via['deputystone']['deputystone_number'][$keg_deputystone];
										$associate_luozuan_deputystone[$key+100]['deputystone_price']			= $via['deputystone']['deputystone_weight'][$keg_deputystone]*$val_deputystone['sbgh_price']*$via['deputystone']['deputystone_number'][$keg_deputystone];
									}			
 
								}	
								
								$associate_luozuan_deputystone[$key]['deputystone_num']						= $via['deputystone']['deputystone_number'][$keg_deputystone];
								$associate_luozuan_deputystone[$key+100]['deputystone_num']					= $via['deputystone']['deputystone_number'][$keg_deputystone];
								$associate_luozuan_deputystone[$key]['goods_id']							= $ZmfxGoodsId;
								$associate_luozuan_deputystone[$key+100]['goods_id']						= $ZmfxGoodsId;
							}
						});
				}
			});
 
 
			$associate_luozuan_deputystone					= array_values($associate_luozuan_deputystone);
 
			$AddZmfxGoodsAttributesDeputystoneStatus		= $ZmfxGoodsAttributesDeputystone	->addAll($associate_luozuan_deputystone);	
			
			if($associate_luozuan_deputystone){
				$_AttributesLuozuan_key_luozuan_number		= array_sum(array_column($associate_luozuan_deputystone, 'deputystone_num'))/2;	//获取副石颗
			}

			foreach($associate_luozuan_array_id as $key_luozuan_info=>$val_luozuan_info){
				if($val_luozuan_info=='4' || $val_luozuan_info=='5'){
					$_AttributesLuozuanInfo[$key_luozuan_info]['basic_cost']  	= isset($p_work_price) ? $p_work_price : 0;	
					$_AttributesLuozuanInfo[$key_luozuan_info]['loss_name']  	= isset($p_loss_price) ? $p_loss_price : 0;
					$_AttributesLuozuanInfo[$key_luozuan_info]['weights_name']  = floor($associate_luozuan_material_weight_result[0]*'1.31'*100)/100;
 				
				}else if($k_work_price && $k_loss_price){
					$_AttributesLuozuanInfo[$key_luozuan_info]['basic_cost']  	= $k_work_price;					
					$_AttributesLuozuanInfo[$key_luozuan_info]['loss_name']  	= $k_loss_price;
					$_AttributesLuozuanInfo[$key_luozuan_info]['weights_name'] 	= isset ($associate_luozuan_material_weight_result[0]) ? $associate_luozuan_material_weight_result[0] : 0;					
				}else{
					$_AttributesLuozuanInfo[$key_luozuan_info]['basic_cost']  	= 0;					
					$_AttributesLuozuanInfo[$key_luozuan_info]['loss_name']  	= 0;
					$_AttributesLuozuanInfo[$key_luozuan_info]['weights_name'] 	= isset ($associate_luozuan_material_weight_result[0]) ? $associate_luozuan_material_weight_result[0] : 0;	
				}

				if($_AttributesLuozuanInfo[$key_luozuan_info]['basic_cost']>0 && $_AttributesLuozuanInfo[$key_luozuan_info]['loss_name']>0){
					$_AttributesLuozuanInfo[$key_luozuan_info]['additional_price'] 		= $_AttributesLuozuan_key_luozuan_number*'5' + $material_match_28 + $material_match_8 + $material_match_19 + $material_match_21;
				}
					$_AttributesLuozuanInfo[$key_luozuan_info]['goods_id'] 				= $ZmfxGoodsId;
					$_AttributesLuozuanInfo[$key_luozuan_info]['material_id']  			= $val_luozuan_info;
			}
			
 
			$AddZmfxGoodsAttributesInfoStatus 			=	$ZmfxGoodsAttributesInfo		->addAll($_AttributesLuozuanInfo); 
			$ZmfxGoodsAttributesInfo->_sql();
		}
        //图片
        if(is_array($images)){
            $imagesObj          		= M('banfang_goods_images')->field('small_path,big_path,UNIX_TIMESTAMP(create_time) as create_time,'.$ZmfxGoodsId.' as goods_id,'.$default_agent.' as agent_id')
											-> where (' goods_id = '.$info['banfang_goods_id'].' and images_id in ('.implode(',',$images).')'  ) -> select();
			$AddZmfxGoodsImagesStatus 	=	$ZmfxGoodsImages	->addAll($imagesObj);
        }
 
 
        if ($AddZmfxGoodsImagesStatus === false || $AddZmfxGoodsAttributesDeputystoneStatus === false ){
            $this->rollback();
            return false;
        }else{
			$this->commit();
		}	
 
	}
	
	
	
	
	
	
	
	
    public  function getFilterFieldData($goods_id){
        $res  =  M('banfang_goods_associate_luozuan') -> alias('l1')
              -> where(array('`l1`.goods_id' => $goods_id))
              -> group('`l1`.goods_id')
              -> field('min(l1.`luozuan_weight`) as "luozuan_weight_min",max(l1.`luozuan_weight`) as "luozuan_weight_max",max(l1.`material_weight`) as "material_weight_max",min(l1.`material_weight`) as "material_weight_min"') -> select();
        $info =  $res[0];
        if($info){
            return $info;
        }else{
            return array(
                'luozuan_weight_min'=>'0',
                'luozuan_weight_max'=>'0',
                'material_weight_max'=>'0',
                'material_weight_min'=>'0'
            );
        }
    }
    public function updateSellStatus($goods_ids,$status=0){

        $data                      = array();
        if(empty($status)){
            $data['sell_status']   = 0;
            $data['sell_off_time'] = date('Y-m-d H:i:s');
        }else{
            $data['sell_status']   = 1;
            $data['sell_on_time']  = date('Y-m-d H:i:s');
        }
        $where                     = array();
        $where['goods_id']         = array('in',$goods_ids);
        $this -> where($where) -> save( $data );
        if( $this -> getError() ){
            return false;
        }else{
            return true;
        }
    }

    public function deleteGoods($goods_ids){

        $where                                   =  array();
        $where['goods_id']                       =  array('in',$goods_ids);
        M('banfang_goods_associate_luozuan')     -> where( $where ) -> delete();
        M('banfang_goods_associate_deputystone') -> where( $where ) -> delete();
        M('banfang_goods_images')                -> where( $where ) -> delete();
        M('banfang_goods_associate_attr')        -> where( $where ) -> delete();
        $this                                    -> where( $where ) -> delete();
        return true;

    }

	/**
	 * auth	：fangkai
	 * content：获取产品图片
	 * time	：2016-12-28
	**/
    public function getGoodsImage($gid){

		$Image	           = M('banfang_goods_images');
		$where['goods_id'] = $gid;
		$imageList         = $Image->field('small_path,big_path')->where($where)->select();	//产品图片
		return $imageList;
	
    }

    //返回产品价格以及必要信息
    public function getGoodsPriceInfo($goods_id,$material_id=0,$associate_luozuan_id=0,$deputystone_attribute='',$technology='1'){

        $info = array();
        list($goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone) = $this -> getGoodsParam($goods_id,$material_id,$associate_luozuan_id);
        list($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info,$price_info) = $this -> getPriceParam($goods_id,$goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone,$deputystone_attribute,$technology);
        $price = $this -> calculatePrice($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info);
        $info  = array(
            'material_info'         => $goods_material_info,
            'associate_luozuan'     => $goods_associate_luozuan,
            'associate_deputystone' => $goods_associate_deputystone,
            'price_info'            => $price_info,
            'price'                 => $price
        );
        return $info;
    }
    
    //自定义价格
    public function getGoodsPriceInfoByCustom($goods_id,$material_id=0,$associate_luozuan_info,$deputystone_attribute='',$technology='1'){
        $info = array();
        list($goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone) = $this -> getGoodsParam($goods_id,$material_id,0);
        $goods_associate_luozuan['luozuan_weight'] =  $associate_luozuan_info['luozuan_weight'];
        $goods_associate_luozuan['luozuan_number'] =  $associate_luozuan_info['luozuan_number'];
        list($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info,$price_info) = $this -> getPriceParam($goods_id,$goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone,$deputystone_attribute,$technology);
        $price = $this -> calculatePrice($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info);
        $info  = array(
            'material_info'         => $goods_material_info,
            'associate_luozuan'     => $goods_associate_luozuan,
            'associate_deputystone' => $goods_associate_deputystone,
            'price_info'            => $price_info,
            'price'                 => $price
        );
        return $info;
    }

    //获取价格相关参数
    public function getPriceParam($goods_id,$goods_material_info,$goods_associate_luozuan,$goods_associate_deputystone,$deputystone_attribute='',$technology='1'){

        $goods_info          = M('szzmzb_goods') -> where( " goods_id = $goods_id ") -> find();
        $material_info       = array();
        $base_process_info   = array();
        $attach_process_item = array();
        $luozuan_info        = array();
        $deputystone_item    = array();
        $price_info          = array(); //封装价格信息



        $goodsAttrObj = M('szzmzb_goods_associate_attr');
        $BBP          = new \Common\Model\Dingzhi\Szzmzb\BanfangBaseProcess();
        $BAP          = new \Common\Model\Dingzhi\Szzmzb\BanfangAttachProcess();

        //材质和价格
        $material_info['gold_price']      = $goods_material_info['gold_price'];
        $material_info['material_weight'] = $goods_associate_luozuan['material_weight'];

        //基本工费
        $_base_process_info = $BBP -> getBaseProcessInfoByJewelry($goods_info['jewelry_id']);
        if( $_base_process_info ){
            /*if($goods_material_info['material_type'] != '1'){
                //黄金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_good'];
                }
            } else {
                //铂金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_good'];
                }
                //铂金加1.31倍
                $material_info['material_weight']    = $material_info['material_weight'] * 1.31;
            }*/

            //添加银材质 luohaitao 20170823
            if($goods_material_info['material_type'] == '1'){
                //铂金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['pt950_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['pt950_loss_good'];
                }

                $material_info['material_weight']    = $material_info['material_weight'] * 1.31;

            } else if ($goods_material_info['material_type'] == '3') {
                //银
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['s925_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['s925_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['s925_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['s925_loss_good'];
                }

                $material_info['material_weight']   = $material_info['material_weight'] * 0.7;

            } else {
                //黄金
                if( $technology == 1 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_general'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_general'];
                } else if( $technology == 2 ){
                    $base_process_info['fee_price']  = $_base_process_info['au750_fee_price_good'];
                    $base_process_info['extra_loss'] = $_base_process_info['au750_loss_good'];
                }
            }

        } else {
            $_base_process_info['jewelry_name'] = M('szzmzb_jewelry') -> where(" jewelry_id >= '$goods_info[jewelry_id]' ")-> getField('jewelry_name');
            $base_process_info['fee_price']     = 0;
            $base_process_info['extra_loss']    = 0;
        }

        //附加工费
        $attach_process_total_price = 0;
        $data       = $goodsAttrObj -> where(" goods_id = $goods_id ") -> select();
        $goods_attr = array();
        foreach( $data as $row ){
            if($row['attr_id1']){
                $goods_attr[] = $row['attr_id1'];
            }
            if($row['attr_id2']){
                $goods_attr[] = $row['attr_id2'];
            }
            if($row['attr_id3']){
                $goods_attr[] = $row['attr_id3'];
            }
        }

        if( $goods_attr ){    
            $attach_process_item = $BAP -> getAttachProcessListByAttr($goods_attr);
            if( $attach_process_item ){
                foreach( $attach_process_item as $key => $row ){
                    $attach_process_total_price += $row['fee_price'];
                    $d['attach_item'] = $row['attach_item'];
                    $d['fee_price']   = $row['fee_price'];
                    $d['extra_loss']  = $row['extra_loss'];
                    $attach_process_item[$key] = $d;
                }
            }
        }
        
        //主石信息,当分类为钻石类的时候才计算保险费
        $is_luozuan = M('szzmzb_category') -> where(" category_id = $goods_info[category_id] ") -> getField('is_luozuan');
        if( $is_luozuan ){
            $luozuan_info['fee_price']      = M('szzmzb_luozuan_premiums') ->fetchsql(false)-> where(" max_weight >= '$goods_associate_luozuan[luozuan_weight]' and min_weight <= '$goods_associate_luozuan[luozuan_weight]' ")-> getField('fee_price');
            if( intval($goods_associate_luozuan['luozuan_shape_id']) != 1 ){
                //主石非圆形的时候保险费为100
                $luozuan_info['fee_price']  = 100;
            }
            $luozuan_info['fee_price']      = $luozuan_info['fee_price'] ? $luozuan_info['fee_price'] : 0;
            $luozuan_info['luozuan_number'] = $goods_associate_luozuan['luozuan_number'];
        }else{
            $luozuan_info['fee_price']      = 0;
            $luozuan_info['luozuan_number'] = 0;
        }

        //副石信息
        $deputystone_total_price        = 0;
        if( $goods_associate_deputystone ){
            foreach($goods_associate_deputystone as $row){
                $deputystone_info['deputystone_number'] = $row['deputystone_number'];
                $deputystone_info['deputystone_weight'] = $row['deputystone_weight'];
                $deputystone_data                       = M('szzmzb_deputystone') -> where(" max_weight >= '$row[deputystone_weight]' and min_weight <= '$row[deputystone_weight]' ") -> find();
                if( $deputystone_data && $deputystone_attribute ){
                    if( $deputystone_attribute          == 'SAGH' ){
                        $deputystone_info['fee_price']  = $deputystone_data['sagh_price'];
                    }else if( $deputystone_attribute    == 'SBGH' ){
                        $deputystone_info['fee_price']  = $deputystone_data['sbgh_price'];
                    }
                }else{
                    $deputystone_info['fee_price']      = 0;
                }
                $deputystone_total_price                += $deputystone_info['fee_price'] * $deputystone_info['deputystone_number'];
                $deputystone_item[]                     = $deputystone_info;
            }
        }

        //得到微镶费的价格
        $weixiang_info                     = $BAP -> getWeixiangInfo();

        //记录价格信息
        $price_info['base_process_info']   = array(
                                                'jewelry_name'=>"$_base_process_info[jewelry_name]",
                                                'fee_price'=>$base_process_info['fee_price'],
                                                'extra_loss'=>$base_process_info['extra_loss']
                                           );
        $price_info['attach_process_item'] = $attach_process_item;
        $price_info['material_info']       = array(
                                                'material_name'=>"$goods_material_info[material_name]",
                                                'gold_price'=>$material_info['gold_price'],
                                                'material_weight'=>$material_info['material_weight']
                                           );
        $price_info['luozuan_info']        = array(
                                                'luozuan_weight'=>"$goods_associate_luozuan[luozuan_weight]",
                                                'fee_price'=>$luozuan_info['fee_price'],
                                                'luozuan_number'=>$luozuan_info['luozuan_number']
                                           ); 
        $price_info['deputystone_item']           = $deputystone_item;
        $price_info['weixiang_info']              = array('attach_item'=>$weixiang_info['attach_item'],'fee_price'=>$weixiang_info['fee_price'] );

        $price_info['attach_process_total_price'] = $attach_process_total_price;
        $price_info['deputystone_total_price']    = $deputystone_total_price;

        return array(
            $material_info,
            $base_process_info,
            $attach_process_item,
            $luozuan_info,
            $deputystone_item,
            $weixiang_info,
            $price_info
        );
    }

    // 获取产品相关参数
    public function getGoodsParam($goods_id,$material_id=0,$associate_luozuan_id=0,$goods_deputystone_id=0){

        $materialObj                  = M('szzmzb_material');
        $goodsAssociateLuozuanObj     = M('szzmzb_goods_associate_luozuan');
        $goodsAssociateDeputystoneObj = M('szzmzb_goods_associate_deputystone');

        if(empty($material_id)){
            $material_id              = $materialObj -> order(' material_id asc ') -> getField('material_id');
        }

        if(empty($associate_luozuan_id)){
            $associate_luozuan_id     = $goodsAssociateLuozuanObj -> where(" goods_id = $goods_id ") -> order(' associate_luozuan_id desc ') -> getField('associate_luozuan_id');
        }

        $goods_material_info          = $materialObj -> where(" material_id = $material_id ") -> find();
        $goods_associate_luozuan      = $goodsAssociateLuozuanObj     -> where(" associate_luozuan_id = $associate_luozuan_id ") -> find();
        $goods_associate_luozuan['luozuan_weight_interval'] = $goodsAssociateLuozuanObj -> where(" goods_id = $goods_id ") -> getField(' concat(min(`luozuan_weight`),"-",max(`luozuan_weight`)) as `luozuan_weight_interval` ',false);
        $goods_associate_deputystone  = $goodsAssociateDeputystoneObj -> where(" associate_luozuan_id = $associate_luozuan_id ") -> select();

        return array(
            $goods_material_info,
            $goods_associate_luozuan,
            $goods_associate_deputystone
        );
    }


    //材料：   material_info,       包含重量和单价
    //基本工费:base_process_info:   包含损耗，和工费
    //附加工费:attach_process_info: 会有多项，每行一个，涉及到对应的属性，有工费和损耗两项
    //主石信息:luozuan_info         包含保险费和数量
    //副石信息:deputystone_info     会有多项，每行一个，包含价格和数量
    //副石镶嵌费:weixiang_info      包含单位费用
    //公式: 金料费用(重量 X 金价) + 金料损耗费(重量 X 金价 X 损耗) + 款式的基本工费 + 附加工艺费 + 主石保险费 + 副石费用（ 单价 X 数量 ） + 副石的镶嵌费（ 单价费用 X 数量 ） 
    public function calculatePrice($material_info,$base_process_info,$attach_process_info,$luozuan_info,$deputystone_info,$weixiang_info){

        $price      = 0;
        //金料价格
        $price      = $material_info['material_weight'] * $material_info['gold_price'];
        $price     += $base_process_info['fee_price'];
        //基本损耗
        $extra_loss = $base_process_info['extra_loss'];
        //附加工费
        if($attach_process_info){
            foreach($attach_process_info as $row){
                $price      += $row['fee_price'];
                $extra_loss += $row['extra_loss'];
            }
        }
        //工耗
        $price += $material_info['material_weight'] * $material_info['gold_price'] * $extra_loss / 100 ;
        //主石保险费
        $price += $luozuan_info['fee_price']        * $luozuan_info['luozuan_number'];
        //echo $luozuan_info['fee_price']             * $luozuan_info['luozuan_number'] . ',';
        //副石
        if( $deputystone_info ){
            foreach($deputystone_info as $row){
                $price += $row['fee_price']           * $row['deputystone_weight'] * $row['deputystone_number']; //副石费
                $price += $weixiang_info['fee_price'] * $row['deputystone_number']; //副石镶嵌费
            }
        }
        return round($price,2);
    }
}
?>