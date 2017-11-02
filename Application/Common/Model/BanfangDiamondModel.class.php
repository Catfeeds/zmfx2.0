<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/20
 * Time: 16:19
 */
namespace Common\Model;
use Think\Model;
class BanfangDiamondModel extends Model{
    public function verify_data($data_all){
        $data = $data_all['data'];
        $offer_id = $data_all['offer_id']>0 ? $data_all['offer_id'] : 0;
        $setting_param = $data_all['setting_param'];
        $is_excel = $data_all['is_excel'] ? $data_all['is_excel'] : 0;
        $agent_id = $data_all['agent_id'] ? $data_all['agent_id'] : C('agent_id');

        $return_data = array(
            'status'=>0,
            'message'=>'验证失败'
        );
        if($data['carat']<=0){
            $return_data['message'] = '请选择重量';
            return $return_data;
        }
        if(!in_array($data['color'],$setting_param['color_arr'])){
            $return_data['message'] = '请选择正确的颜色';
            return $return_data;
        }
        if(!in_array($data['clarity'],$setting_param['clarity_arr'])){
            $return_data['message'] = '请选择正确的净度';
            return $return_data;
        }
        if($data['price']<=0){
            $return_data['message'] = '价格必须大于0';
            return $return_data;
        }
        $banfang_carat = M('banfang_carat')->where(array('agent_id'=>$agent_id,'carat_from'=>array('elt',$data['carat']),'carat_to'=>array('egt',$data['carat'])))->find();
        if(empty($banfang_carat)){
            $return_data['message'] = '重量区间不存在';
            return $return_data;
        }
        $where = array(
            'color'=>$data['color'],
            'clarity'=>$data['clarity'],
            'carat'=>$data['carat'],
            'offer_id'=>array('neq',$offer_id),
            'agent_id'=>C('agent_id')
        );
        $offer_info_ifs = $this->where($where)->find();
        if(!empty($offer_info_ifs) && !$is_excel){
            $return_data['message'] = '不要添加相同的数据';
            return $return_data;
        }
        $offer_id = $offer_info_ifs['offer_id'] ? $offer_info_ifs['offer_id'] : 0;


        $return_data = array(
            'status'=>1,
            'offer_id'=>$offer_id,
            'carat_section'=>$banfang_carat['carat_section'],
        );
        return $return_data;
    }
    //计算通过加价后的数据
    public function cateAllData($lists,$agent_id=0){
        if(!$agent_id){
            $agent_id = C('agent_id');
        }
        if(!empty($lists) && is_array($lists)){
            foreach($lists as $key=>$vale){
                $vale['price'] = $vale['price']*$vale['carat'];
                $vale['add_price'] = $this->getCatePrice($vale,$agent_id);
                $lists[$key] = $vale;
            }
        }
        return $lists;
    }

    public function getCatePrice($data,$agent_id=0){
        if(!$agent_id){
            $agent_id = C('agent_id');
        }
        $add_price = $data['price'];
        $GOP = M('banfang_point');
        $where_arr = array(
            'agent_id'=>$agent_id,
            'min_value'=>array('elt',$data['carat']),
            'max_value'=>array('gt',$data['carat']),
            'color'=>$data['color'],
            'clarity'=>$data['clarity']
        );
        $point_value = $GOP->where($where_arr)->getField('point_value');
        if($point_value){
            $add_price = formatRound($add_price*(100+$point_value)/100);
        }
        return $add_price;
    }
    //导出excel数据
    public function exportExcel(){
        import("Org.Util.Tools");
        $TOOLS = new \Org\Util\Tools();
        $name='石价维护表格';
        $title = array('钻重','颜色','净度','价格');
        $data = array();
        $data[] = $title;
        $TOOLS->exportExcel($data,array('name'=>$name));exit;
    }
    //校验加点数据
    public function verify_point($data){
        //设置最小值
        $min_setting = 0;
        //设置最大值,o则表示不设置
        $max_setting = 0;
        $return_data = array(
            'status'=>1,
            'message'=>'验证成功'
        );
        $bool = array_multisort(array_column($data,'min_value'),SORT_ASC,$data);
        foreach($data as $key=>$value){
            if($value['min_value']<$min_setting){
                $return_data['status'] = 0;
                $return_data['message'] = '最小值不能小于'.$min_setting;
                break;
            }
            if($max_setting && $value['max_value']>$max_setting){
                $return_data['status'] = 0;
                $return_data['message'] = '最大值不能大于'.$max_setting;
                break;
            }
            if($value['min_value']>=$value['max_value']){
                $return_data['status'] = 0;
                $return_data['message'] = '最小值'.$value['min_value'].'大于最大值'.$value['max_value'];
                break;
            }
            if(!empty($data[$key+1])){
                if($data[$key+1]['min_value']<$value['max_value']){
                    $return_data['status'] = 0;
                    $return_data['message'] = $data[$key+1]['min_value'].'~'.$value['max_value'].'之间数据存在交集';
                    break;
                }
            }
        }
        return $return_data;

    }
    //一些固定的参数的配置(由于没有专门的数据库来存这些数据因此放到这个文件来配置,加入需要加参数则可以多写一个case)
    public function getOfferParam($type){
        switch($type){
            case 1:
                //商品详情
                $setting_param = array(
                    // 'color_arr'=>array('D','E','F','G','H','I','J','K','L','M'),
                    // 'clarity_arr'=>array('IF','VVS1','VVS2','VS1','VS2','SI1','SI2','SI3','I1','I2','I3'),
                    'color_arr'=>array('H','D-E','F-G','I-J','K-L'),
                    'clarity_arr'=>array('SI','VVS','VS'),
                );
                break;
            default:
                //钻石报价
                $setting_param = array(
                    /*'weight_arr'=>array(
                        30=>array('from'=>'0.30','to'=>'0.39','value'=>'30','name'=>'0.30-0.39ct'),
                        40=>array('from'=>'0.40','to'=>'0.49','value'=>'40','name'=>'0.40-0.49ct'),
                        50=>array('from'=>'0.50','to'=>'0.69','value'=>'50','name'=>'0.50-0.69ct'),
                        70=>array('from'=>'0.70','to'=>'0.89','value'=>'70','name'=>'0.70-0.89ct'),
                        90=>array('from'=>'0.90','to'=>'0.99','value'=>'90','name'=>'0.90-0.99ct'),
                        100=>array('from'=>'1.00','to'=>'1.49','value'=>'100','name'=>'1.00-1.49ct'),
                        150=>array('from'=>'1.50','to'=>'1.99','value'=>'150','name'=>'1.50-1.99ct'),
                        200=>array('from'=>'2.00','to'=>'2.99','value'=>'200','name'=>'2.00-2.99ct'),
                        300=>array('from'=>'3.00','to'=>'3.99','value'=>'300','name'=>'3.00-3.99ct'),
                        400=>array('from'=>'4.00','to'=>'4.99','value'=>'400','name'=>'4.00-4.99ct'),
                        500=>array('from'=>'5.00','to'=>'5.99','value'=>'500','name'=>'5.00-5.99ct'),
                    ),*/
                    // 'color_arr'=>array('D','E','F','G','H','I','J','K','L','M'),
                    // 'clarity_arr'=>array('IF','VVS1','VVS2','VS1','VS2','SI1','SI2','SI3','I1','I2','I3'),
                    'color_arr'=>array('H','D-E','F-G','I-J','K-L'),
                    'clarity_arr'=>array('SI','VVS','VS'),
                );
                break;
        }
        return $setting_param;
    }

}
