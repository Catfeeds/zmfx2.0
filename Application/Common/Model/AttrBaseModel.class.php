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
class AttrBaseModel extends Model{
    /**
     * @param int $agent_id
     * @return mixed
     * @author 孙阳
     * @time 2017/10/18
     */
    public function getAttrTopLists($agent_id=0){
        $agent_id = $agent_id ? intval($agent_id): C('agent_id');
        list(,$join,$order) = $this->getSelectParam($agent_id);
        $get_fields = 'b.id,b.id id,IFNULL(ca_name,ba_name) name,IFNULL(ca_sort,ba_sort) sort,IFNULL(ca_status,ba_status) status,IFNULL(c.sort_time,0) sort_time';
        $where = array('b.pid'=>0);
        $top_name_arr = $this->alias('b')->join($join)->where($where)->order($order)->getfield($get_fields);
        return $top_name_arr;
    }

    public function getAttrInfo($id,$agent_id=0){
        if($id){
            $where = array(
                'b.id'=>$id
            );
            list($fields,$join,$order) = $this->getSelectParam($agent_id);
            $info = $this->where($where)->field($fields)->alias('b')->join($join)->order($order)->find();
        }

        if(empty($info)){
            $info = array();
        }

        return $info;
    }

    public function getSelectParam($agent_id=0){
        $agent_id = $agent_id ? intval($agent_id): C('agent_id');
        $fields = 'b.*,IFNULL(ca_name,ba_name) name,IFNULL(ca_sort,ba_sort) sort,IFNULL(ca_status,ba_status) status,ba_is_base is_base,IFNULL(c.sort_time,0) sort_time';
        $join = 'left join zm_attr_base_config c on b.id=c.bid AND c.agent_id='.$agent_id;
        $order = 'sort asc,sort_time desc,b.id asc';
        //注意这里不要修改返回参数的顺序
        $select_param = array($fields, $join, $order );

        return $select_param;
    }

    public function getAttrLists($param){
        $agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
        $top_id = $param['top_id'] ? $param['top_id'] : 0;
        $status = $param['status'] ? $param['status'] : 0;
        $is_base = $param['is_base'] ? $param['is_base'] : 0;
        $where['_string'] = "1=1";
        if($top_id>0){
            $where['_string'] .= " AND (b.top_id=$top_id OR b.id=$top_id)";
        }
        if($status>0){
            if($status==2){
                $status = 0;
            }
            $where['_string'] .= " AND IFNULL(ca_status,ba_status)=$status";
        }
        if($is_base>0){
            if($is_base==2){
                $is_base = 0;
            }
            $where['_string'] .= " AND ba_is_base=$is_base";
        }

        list($fields,$join,$order) = $this->getSelectParam($agent_id);
        $lists = $this->where($where)->field($fields)->alias('b')->join($join)->order($order)->select();
        return $lists;
    }

    public function getAttrTreeLists($param){
        $lists = $this->getAttrLists($param);
        $tree = _arrayRecursiveNew($lists,'id','pid',0,1);
        return $tree;
    }

    public function setTreeToLists($data,$string_start='&nbsp;&nbsp;',$string_add='&nbsp;&nbsp;&nbsp;&nbsp;',$arr=array(),$count=0){
        if(!empty($data)){
            $count++;
            $number = 0;
            foreach($data as $value){
                $number++;
                $value['name'] = $string_start.$value['name'];
                $temp = $value;
                unset($temp['sub']);
                $arr[] = $temp;
                if(!empty($value['sub'])){
                    $temp_arr = $this->setTreeToLists($value['sub'],$string_start.$string_add,$string_add,array(),$count);
                    $arr = array_merge($arr,$temp_arr);
                }
            }
        }
        return $arr;
    }

    //获取商品分类
    public function getGoodsAttrCategory($type=1,$agent_id=0){
        $agent_id = $agent_id ? $agent_id : C('agent_id');
        list($fields,$join,$order) = $this->getSelectParam($agent_id);
        $where = array(
            'attr_type'=>$type
        );
        $lists = $this->where($where)->field($fields)->alias('b')->join($join)->order($order)->select();
        return $lists;
        
    }
    public function getAttrConditions($attr_id,$alias=''){
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