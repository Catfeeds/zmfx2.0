<?php
namespace Supply\Model;
use Think\Model;
class SupplyGoodsSanhuoCcModel extends Model
{
    public $cc_type_int = array(
        'color'   => 1,
        'clarity' => 2,
        'cut'     => 3,
        'weights' => 4
    );
    public $cc_type_str = array(
        1 => 'color',
        2 => 'clarity',
        3 => 'cut',
        4 => 'weights'
    );
    public function getList( $goods_id )
    {
        $res    = $this -> where('goods_id='.$goods_id)->select();
        $data   = array();
        foreach( $res as $row ){
            $row['cc_type']  = $this -> cc_type_str[$row['cc_type']];
            $row['cc_value'] = htmlspecialchars_decode($row['cc_value']);
            unset($row['zm_goods_id'],$row['agent_id']);
            $data[]          = $row;
        }
        return $data;
    }
    public function addGoods4c( $goods_id , $obj ,$agent_id ){
        $goods_info            = D('SupplyGoodsSanhuo') -> where(" goods_id = $goods_id ") -> find();
        $goods_info['color']   = htmlspecialchars($goods_info['color']);
        $goods_info['clarity'] = htmlspecialchars($goods_info['clarity']);
        $goods_info['cut']     = htmlspecialchars($goods_info['cut']);
        $goods_info['weights'] = htmlspecialchars($goods_info['weights']);
        $this                 -> where('goods_id = '.$goods_id) ->delete();
        $string                = '这包货的';
        foreach($obj as $key => $row){
            if($string !== '这包货的'){
                $string .= '<br />';
            }
            switch  ( $row[0]['cc_type'] ){
                case 'color':
                    $string .= '颜色是'.$goods_info['color'].'：';
                break;
                case 'clarity':
                    $string .= '净度是'.$goods_info['clarity'].'：';
                break;
                case 'cut':
                    $string .= '切工是'.$goods_info['cut'].'：';
                break;
                case 'weights':
                    $string .= '分数或筛号是'.$goods_info['weights'].'：';
                break;
            }
            foreach($row as $r){
                $data['cc_type']  = $this -> cc_type_int[$r['cc_type']];
                $data['cc_ku']    = $r['cc_ku'];
                $data['cc_value'] = htmlspecialchars_decode($r['cc_value']);
                $data['goods_id'] = $goods_id;
                $data['agent_id'] = $agent_id;
                $this            -> add($data);
                $percentage       = number_format(round($r['cc_ku']/$goods_info['goods_weight']*1000/10,2),2,'.','').'%';
                $string          .= $data['cc_value'].'为'.$percentage.';';
            }
        }
        $data                     = array();
        $data['goods_4c']         = $string;
        D('SupplyGoodsSanhuo') -> data($data) -> where(" goods_id = $goods_id ") -> save();
        return true;
    }
}
