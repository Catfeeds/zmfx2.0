<?php
/**
 * 收藏模型
 * Created by PhpStorm.
 * User: Sunyang
 * Date: 2017/6/15
 * Time: 11:48
 */
namespace Common\Model;
use Think\Model;
class CollectionModel extends Model{
    public function addCollection($param){
        $result = array(
            'msg'   => '操作失败',
            'status' => 0,
            'id'=>0
        );
        if($param['uid']<=0){
            $result = array(
                'msg'   => '请先登录',
                'status' => -100,
                'id'=>0
            );
            return $result;
        }
        if($param['gid'] <= 0){
            $result['请选择商品'];
            return $result;
        }
        if(!isset($param['goods_type'])){
            return $result;
        }
        $goods_type        = $param['goods_type'];
        $agent_id          = isset($param['agent_id']) ? $param['agent_id'] : C('agent_id');
        $param['agent_id'] = $agent_id;
        //处理过的$param将序列化之后存入数据库
        $time   = time();
        $data = array();
        $tempgoods = D('Common/Goods')->productGoodsInfo($param);
        $continue = true;
        if($tempgoods['status']==100){
            $goods = $tempgoods['info'];
            switch($goods_type){
                case 0:
                case 1:
                    //0白钻1彩钻
                    $data['goods_price'] = $goods['price'];
                    $goods_type          = $goods['luozuan_type'] ? 1 : 0;
                    break;
                case 2:
                    //2:散货
                    $data['goods_price'] = $goods['goods_price'];
                    break;
                case 3:
                case 4:
                    //3:珠宝成品 4:钻托定制
                    if(in_array($goods['activity_status'],array('0','1'))){
                        $data['goods_price'] = $goods['activity_price'];
                    }else{
                        $data['goods_price'] = $goods['goods_price'];
                    }
                    $SKUM  = M('goods_sku');
                    if($goods_type==3){
                        $skuinfo = $SKUM->field('goods_number')->where('goods_id='.$param['gid'].' AND sku_sn="'.$goods['sku_sn'].'"')->find();
                        if(empty($skuinfo) || $skuinfo['goods_number']<=0){
                            $continue = false;
                            $result['msg'] = '商品库存不足';
                        }
                    }
                break;

                    break;
                default:
                    $continue = false;
                    break;
            }
            if(!$continue){
                return $result;
            }
            $data['goods_name'] = isset($goods['goods_name']) ? $goods['goods_name'] : '';
            $data['goods_sn'] = isset($goods['goods_sn']) ? $goods['goods_sn'] : '';
            $data['uid'] = $param['uid'];
            $data['goods_id'] = $param['gid'];
            $data['goods_type'] = $goods_type;
            $data['agent_id'] = $param['agent_id'];
            $data['create_time'] = $time;
            $data['goods_attr'] = serialize($goods);
            $history_where = 'uid='.$data['uid'].' AND goods_id='.$data['goods_id'].' AND goods_type='.$data['goods_type'].' AND agent_id='.$data['agent_id'];
            $history = $this->where($history_where)->find();

            if($history){
                $id = $history['id'];
                if($id>0){
                    $bool = $this->where('id='.$id)->save($data);
                }
            }else{
                $id = $this->add($data);
            }
            if($id){
                $result['msg'] = '收藏成功！';
                $result['status'] = 100;
                $result['id']   = $id;
            }

        }else{
            $result['msg'] = $tempgoods['msg'];
        }
        return $result;
    }

    public function getCollectionList($param){
        $data = array(
            'count'=>0,
            'data'=>array()
        );
        $uid = $param['uid'] ? $param['uid'] : -1;
        $goods_type    = isset($param['goods_type']) ? $param['goods_type'] : -1;
        $agent_id      = isset($param['agent_id']) ? $param['agent_id'] : C('agent_id');

        $where = array(
            'uid'=>$uid,
            'agent_id'=>$agent_id,
        );
        if($goods_type>-1){
            $where['goods_type'] = $goods_type;
        }
        $lists = $this->where($where)->select();
        $GMOD  = M('goods');
        $LMOD  = M('goods_luozuan');
        //$SMOD  = M('goods_sanhuo');
        $SKUM  = M('goods_sku');
        if(!empty($lists)){
            foreach($lists as $value){
                $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
                $value['goods_attrs'] = unserialize($value['goods_attr']);
                $value['have_goods']  = 0;
                switch($value['goods_type']){
                    case 0:
                    case 1:
                        $luozhuaninfo = $LMOD->field('goods_number')->where('gid='.$value['goods_id'])->find();
                        $value['have_goods'] = $luozhuaninfo['goods_number']==1 ? 1 : 0;
                        break;
                    case 2:
                        $value['have_goods']  = 1;
                        break;
                    case 3:
                        $skuinfo = $SKUM->field('goods_number')->where('goods_id='.$value['goods_id'].' AND sku_sn="'.$value['goods_attrs']['sku_sn'].'"')->find();
                        $value['have_goods'] = $skuinfo['goods_number']>0 ? 1 : 0;
                        break;
                    case 4:
                        $value['have_goods']  = 1;
                        break;
                    case 16:
                        $value['have_goods']  = 1;
                        $value['goods_attrs']['attrs'] = '';
                        $value['goods_attrs']['associateInfo']['material_name'] = $value['goods_attrs']['selected']['material_name'];
                        break;
                    default:
                        break;
                }
                unset($value['goods_attr']);
                $data['data'][] = $value;
            }
        }
        return $data;
    }
    public function addCollectionToCart($conllection_arr){
        $data = array('status'=>0,'msg'=>"操作失败",'info'=>array());
        $add_count = 0;
        $G = D('Common/Cart');
        if(!empty($conllection_arr) && is_array($conllection_arr)){
            foreach($conllection_arr as $value){
                $contionsInfo = $this->where('id="'.$value.'"')->find();
                if(!empty($contionsInfo)){
                    $contionsInfo['goods_attr'] = unserialize($contionsInfo['goods_attr']);
                    $param = array(
                        'uid'           => $contionsInfo['uid'],
                        'gid'           => $contionsInfo['goods_id'],
                        'agent_id'      => $contionsInfo['agent_id'],
                        'goods_type'    => $contionsInfo['goods_type'],
                        'id_type'       => isset($contionsInfo['goods_attr']['id_type']) ? $contionsInfo['goods_attr']['id_type'] : '',
                        'materialId'    => isset($contionsInfo['goods_attr']['materialId']) ? $contionsInfo['goods_attr']['materialId'] : 0,
                        'diamondId'     => isset($contionsInfo['goods_attr']['diamondId']) ? $contionsInfo['goods_attr']['diamondId'] : 0,
                        'deputystoneId' => isset($contionsInfo['goods_attr']['deputystoneId']) ? $contionsInfo['goods_attr']['deputystoneId'] : 0,
                        'hand'          => isset($contionsInfo['goods_attr']['hand']) ? $contionsInfo['goods_attr']['hand'] : '',
                        'word'          => isset($contionsInfo['goods_attr']['word']) ? $contionsInfo['goods_attr']['word'] : '',
                        'sd_id'         => isset($contionsInfo['goods_attr']['sd_id']) ? $contionsInfo['goods_attr']['sd_id'] : '',
                        'goods_number'  => isset($contionsInfo['goods_attr']['goods_type_select_number']) ? $contionsInfo['goods_attr']['goods_type_select_number'] : 1,
                        'word1'         => isset($contionsInfo['goods_attr']['word1']) ? $contionsInfo['goods_attr']['word1'] : '',
                        'hand1'         => isset($contionsInfo['goods_attr']['hand1']) ? $contionsInfo['goods_attr']['hand1'] : '',
                        'sd_id1'        => isset($contionsInfo['goods_attr']['sd_id1']) ? $contionsInfo['goods_attr']['sd_id1'] : '',
                        'sku_sn'        => isset($contionsInfo['goods_attr']['sku_sn']) ? $contionsInfo['goods_attr']['sku_sn'] : 0,
                    );
                    $return = $G->addToCart($param);


                    if($return['status']==100){
                        $add_count++;
                        $data['info'][] = $return['id'];
                        $bool = $this->where('id="'.$value.'"')->delete();
                    }else{
                        $data['msg'] = $return['msg'];
                    }
                }
            }
        }
        if($add_count>0){
            $data['status'] = 100;
            $data['msg']    = '添加成功';
            if($add_count!=count($conllection_arr)){
                $data['msg']    = '商品部分添加成功';
            }
        }else{
            $data['msg']    = '商品已下架';
        }
        return $data;
    }


}