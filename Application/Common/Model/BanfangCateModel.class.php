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
use Think\Page;
class BanfangCateModel extends Model{
    public function getCateRankName($type=0){
        switch($type){
            case 1:
                $ca_rank_name_arr = array(
                    '1'=>array(
                        'id'=>1,
                        'name'=>'大系列'
                    ),
                    '2'=>array(
                        'id'=>2,
                        'name'=>'小系列'
                    ),
                );
                break;
            default:
                $ca_rank_name_arr = $this->where(['pid'=>0])->getField('id,id,ca_name name');
                break;

        }
        return $ca_rank_name_arr;
    }

    public function getCateLists($param){
        $agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
        $n = $param['n'] ? $param['n'] : 13;
        $ca_rank = $param['ca_rank'] ? $param['ca_rank'] : 0;
        $no_page = $param['no_page'] ? $param['no_page'] : false;
        $no_foeach = $param['no_foeach'] ? $param['no_foeach'] : false;
        $ca_rank_name_arr = $this->getCateRankName();
        $where = array(
            'agent_id'=>$agent_id,
            'pid'=>array('gt',0)
        );
        if($ca_rank>0){
            $where['ca_rank'] = $ca_rank;
        }
        if(!$no_page){
            $count = $this->where($where)->count();
            $Page = new Page($count,$n);
            $data = $this->where($where)->order('ca_sort asc,id desc')->limit($Page->firstRow,$Page->listRows)->select();
            $page = $Page->show();
        }else{
            $data = $this->where($where)->order('ca_sort asc,id desc')->select();
            $page = '';
            $count = count($data);
        }


        $lists = array();
        if(!$no_foeach){
            foreach($data as $key=>$value){
                if(isset($ca_rank_name_arr[$value['top_id']])){
                    $value['ca_rank_name'] = $ca_rank_name_arr[$value['top_id']]['name'];
                }
                $lists[] = $value;
            }
        }else{
            $lists = $data;
        }
        $return = array(
            'lists'=>$lists,
            'count'=>$count,
            'page'=>$page
        );

        return $return;
    }

    public function getCateConditions($attr_id,$alias=''){
        if($alias){
            $alias.='.';
        }
        if(!empty($attr_id)){
            $attrs = $this->where(array('id'=>array('in',$attr_id)))->select();
        }

        $return = array();
        if(!empty($attrs)){
            foreach($attrs as $value){
                $return[$value['top_id']][] = $value['id'];
            }
        }
        $where = "";
        foreach($return as $key => $val){

            if($key==1){
                $where .= " AND (".$alias."banfang_big_series=".implode(" Or ".$alias."banfang_big_series=",$val).")";
            }elseif($key==2){
                $where .= " AND (".$alias."banfang_small_series=".implode(" Or ".$alias."banfang_small_series=",$val).")";
            }elseif($key==3){
                $where .= " AND (".$alias."banfang_category=".implode(" Or ".$alias."banfang_category=",$val).")";
            }elseif($key==4){
                $where .= " AND (".$alias."banfang_jewelry=".implode(" Or ".$alias."banfang_jewelry=",$val).")";
            }else{
                $where .= " AND (".$alias."banfang_attrs like '%,".implode("%,' Or ".$alias."banfang_attrs like'%,",$val).",%')";
            }
        }
        return $where;
    }


}