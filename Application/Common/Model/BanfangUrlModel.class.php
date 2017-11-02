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
class BanfangUrlModel extends Model{
    public function getNavUrlTypeLists($type=0){
        switch($type){
            default:
                $ca_rank_name_arr = array(
                    '1'=>array(
                        'id'=>1,
                        'name'=>'首页',
                        'url'=>'Home/Index/index'
                    ),
                    '2'=>array(
                        'id'=>2,
                        'name'=>'搜索列表',
                        'url'=>'Home/Goods/goodsCat'
                    ),
                );
                break;

        }
        return $ca_rank_name_arr;
    }
    //获取    菜单列表
    public function getNavUrlLists($param){
        $agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
        $n = $param['n'] ? $param['n'] : 13;
        $na_status = isset($param['na_status']) ? 1 : 0;
        $no_page = $param['no_page'] ? $param['no_page'] : false;
        $no_foeach = $param['no_foeach'] ? $param['no_foeach'] : false;

        $na_status_arr = array(
            0=>'关闭',
            1=>'开启'
        );
        $where = array('agent_id'=>$agent_id);
        if($na_status){
            $where['na_status'] = $param['na_status'];
        }
        if(!$no_page){
            $count = $this->where($where)->count();
            $Page = new Page($count,$n);
            $data = $this->where($where)->order('na_sort asc,id desc')->limit($Page->firstRow,$Page->listRows)->select();
            $page = $Page->show();
        }else{
            $data = $this->where($where)->order('na_sort asc,id desc')->select();
            $page = '';
            $count = count($data);
        }

        $lists = array();
        $url_type_arr = $this->getNavUrlTypeLists();
        if(!$no_foeach){
            foreach($data as $key=>$value){
                if(isset($na_status_arr[$value['na_status']])){
                    $value['na_status_name'] = $na_status_arr[$value['na_status']];
                    $value['url_link'] = isset($url_type_arr[$value['url_link']]) ? U($url_type_arr[$value['url_link']]['url']) : U($url_type_arr[1]['url']);
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
    //获取    树形架构菜单列表
    public function getTreeLists($param){
        $agent_id = $param['agent_id'] ? $param['agent_id'] : C('agent_id');
        $na_status = isset($param['na_status']) ? 1 : 0;
        $all_top = $param['all_top'] ? 1 : 0;

        $na_status_arr = array(
            0=>'关闭',
            1=>'开启'
        );
        $url_type_arr = $this->getNavUrlTypeLists();
        $where = array('agent_id'=>$agent_id);
        if($na_status){
            $where['na_status'] = $param['na_status'];
        }
        $data = $this->where($where)->order('na_sort asc,id asc')->select();
        $lists = array();

        foreach($data as $key=>$value){
            if(isset($na_status_arr[$value['na_status']])){
                $value['na_status_name'] = $na_status_arr[$value['na_status']];
                $value['url_link'] = isset($url_type_arr[$value['url_link']]) ? U($url_type_arr[$value['url_link']]['url']) : U($url_type_arr[1]['url']);
            }
            $lists[] = $value;
        }
        $data = _arrayRecursiveNew($lists,'id','na_parent_id',0,1);

        if(!$all_top){
            $all_data = array();
            foreach($data as $teno){
                if($teno['na_status']){
                    $all_data[] = $teno;
                }
            }
        }else{
            $all_data = $data;
        }
        
        return $all_data;

    }

    public function setRightPartConditions(){
        $BC = M('banfang_cate');
        $return = array();
        $types = $BC
            ->where(array('ca_status'=>1,'pid'=>0))->order('ca_sort asc,id desc')
            ->select();
        foreach($types as $ty){
            $return[$ty['id']] = array(
                'name'=>$ty['ca_name'],
                'count'=>0,
                'lists'=>array()
            );
        }
        $lists = $BC
            ->where(array('ca_status'=>1,'pid'=>array('gt',0)))->order('ca_sort asc,id desc')
            ->select();

        foreach($lists as $value){
            if(!empty($return[$value['pid']])){
                $return[$value['pid']]['sub'][] = $value;
                $return[$value['pid']]['count']++;
            }
        }

        return $return;
    }
    public function setListsAll($data,$string_start='&nbsp;&nbsp;',$string_add='&nbsp;&nbsp;&nbsp;&nbsp;',$arr=array(),$count=0){
        if(!empty($data)){
            $count++;
            foreach($data as $value){
                $value['na_name'] = $string_start.$value['na_name'];
                $temp = $value;
                unset($temp['sub']);
                $arr[] = $temp;
                if(!empty($value['sub'])){
                    $temp_arr = $this->setListsAll($value['sub'],$string_start.$string_add,$string_add,array(),$count);
                    $arr = array_merge($arr,$temp_arr);
                }
            }
        }
        return $arr;
    }
    //获取    数据元素以及元素的上级
    public function getNavUrlInfo($my_id=0,$limit=0,$arr=array()){
        if($my_id<=$limit){
            $arrData=array_reverse($arr);
            return $arrData;
        }else{
            $search_arr = $this->where('id='.$my_id)->find();
            if(!empty($search_arr)){
                $arr[]=$search_arr;
                return $this->getNavUrlInfo($search_arr['na_parent_id'],$limit,$arr);
            }
        }
    }
    //获取    当前元素以及所有上级的下拉列表
    public function getNavUrlAllRank($my_id=0,$limit=0,$arr=array(),$count=0){
        $count++;
        if($my_id<=$limit){
            if(empty($arr)){
                $rank_arr = $this->field('id,na_name as name')->where('na_parent_id="'.$my_id.'"')->select();
                $rank_arr = array_merge(array(0=>array('id'=>0,'name'=>'顶级菜单')),$rank_arr);
                $arrData = array(
                    array(
                        'lists'=>$rank_arr,
                        'count'=>count($rank_arr),
                        'rank'=>1
                    )
                );
            }else{
                $arrData=array_reverse($arr,true);
                return $arrData;
            }
            return $arrData;
        }else{
            $search_arr = $this->where('id='.$my_id)->find();
            if(!empty($search_arr)){
                //找出同级的数据
                $rank_arr = $this->field('id,na_name as name')->where('na_parent_id="'.$search_arr['na_parent_id'].'"')->select();
                if($search_arr['na_parent_id']==0){
                    $rank_arr = array_merge(array(0=>array('id'=>0,'name'=>'顶级菜单')),$rank_arr);
                }else{
                    $rank_arr = array_merge(array(0=>array('id'=>0,'name'=>'请选择')),$rank_arr);
                }
                if($count>1){
                    $arr[]= array(
                        'lists'=>$rank_arr,
                        'count'=>count($rank_arr),
                        'rank'=>$search_arr['na_rank'],
                        'checked'=>$search_arr
                    );
                }
                return $this->getNavUrlAllRank($search_arr['na_parent_id'],$limit,$arr,$count);
            }
        }
    }
    //保存    当数据的上下级关系修改之后   更新它的子集信息
    public function saveNavAllChilds($my_id=0){
        $my_info = $this->where('id='.$my_id)->find();
        $my_lists = $this->where('na_parent_id='.$my_id)->select();
        if(!empty($my_info) && !empty($my_lists)){
            foreach($my_lists as $valx){
                $save_data = array(
                    'na_url'=>$my_info['na_url'],
                    'na_rank'=>$my_info['na_rank']+1
                );
                $bool = $this->where('id='.$valx['id'])->save($save_data);
                $this->saveNavAllChilds($valx['id']);
            }
        }
    }

    public function getCaSearchId($id=0){
        $id = intval($id);
        $ca_search_id = ',';
        if($id>0){
            $all_parent_id_arr = $this->getNavUrlInfo($id);
            foreach($all_parent_id_arr as $tem){
                $ca_search_id .= $tem['id'].',';
            }
        }else{
            $ca_search_id .= '0,';
        }
        return $ca_search_id;
    }

}