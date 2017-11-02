<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/20
 * Time: 16:19
 */
namespace Common\Model;
use Think\Model;
class GoodsOfferInfoModel extends Model{
    public function __construct() {
        parent::__construct();
    }
    public function verify_data($data_all){
        $data = $data_all['data'];
        $offer_id = $data_all['offer_id']>0 ? $data_all['offer_id'] : 0;
        $setting_param = $data_all['setting_param'];
        $return_data = array(
            'status'=>0,
            'message'=>'验证失败'
        );
        if($data['weight']<=0){
            $return_data['message'] = '重量必须大于0';
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
        if($data['dia_global_price']<=0){
            $return_data['message'] = '国际报价必须大于0';
            return $return_data;
        }
        if($data['dia_discount']<0){
            $return_data['message'] = '折扣不能小于0';
            return $return_data;
        }
        /* if($data['dia_discount']>$data['dia_global_price']){
            $return_data['message'] = '折扣不能大于国际报价';
            return $return_data;
        } */
        import("Org.Util.Tools");
        $TOOLS = new \Org\Util\Tools();
        $weight_section = $TOOLS->getWeightSection($data['weight']);
        if(!$weight_section){
            $return_data['message'] = '重量设置不对';
            return $return_data;
        }
        $where = array(
            'color'=>$data['color'],
            'clarity'=>$data['clarity'],
            'weight'=>$data['weight'],
            'offer_id'=>array('neq',$offer_id),
			'agent_id'=>C('agent_id')
        );
        $offer_info_ifs = $this->where($where)->find();
        if(!empty($offer_info_ifs)){
            $return_data['message'] = '不要插入同样的数据';
            return $return_data;
        }
        $where = array(
            'color'=>$data['color'],
            'clarity'=>$data['clarity'],
            'weight_section'=>$weight_section,
            'offer_id'=>array('neq',$offer_id),
			'agent_id'=>C('agent_id')
        );
        $offer_info_ifs = $this->where($where)->find();
        if(!empty($offer_info_ifs) && $offer_info_ifs['dia_global_price']!=$data['dia_global_price']){
            $return_data['message'] = '国际报价设置必须与该重量区间范围内其他钻石报价的国际报价保持一致！';
            return $return_data;
        }
        if(!empty($offer_info_ifs) && $offer_info_ifs['dia_discount']!=$data['dia_discount']){
            $return_data['message'] = '折扣设置必须与该重量区间范围内其他钻石报价的折扣保持一致！';
            return $return_data;
        }
        $return_data = array(
            'status'=>1,
            'weight_section'=>$weight_section
        );
        return $return_data;
    }
    //计算通过加价后的数据
    public function cateAllData($lists){
        $huilv = C('dollar_huilv');
        $GOP = M('goods_offer_point');
        $TOOLS = new \Org\Util\Tools();
        $point_arr = $GOP->where(array('agent_id'=>C('agent_id')))->select();
        if(!empty($lists) && is_array($lists)){
            foreach($lists as $key=>$vale){
                $point_value = $TOOLS->getWeightPoint($vale['weight'],$point_arr,C('agent_id'));
                $carat_arr = array();
                $carat_arr['price'] = formatRound($vale['dia_global_price']*$huilv*$vale['dia_discount']/100,2);
                $carat_arr['add_price'] = formatRound($vale['dia_global_price']*$huilv*$vale['dia_discount']/100*(100+$point_value)/100,2);
                $single_arr = array();
                $single_arr['price'] = formatRound($carat_arr['price']*$vale['weight'],2);
                $single_arr['add_price'] = formatRound($carat_arr['add_price']*$vale['weight'],2);

                $lists[$key]['carat_arr'] = $carat_arr;
                $lists[$key]['single_arr'] = $single_arr;
            }
        }
        return $lists;
    }
    //导出excel数据
    public function exportExcel(){
        import("Org.Util.Tools");
        $TOOLS = new \Org\Util\Tools();
        $name='报价模板';
        $title = array('钻重','颜色','净度','国际报价','折扣');
        $data = array();
        $data[] = $title;
        $TOOLS->exportExcel($data,array('name'=>$name));exit;
        /*$width_num = array(
            array(
                'key'=>'A',
                'value'=>'10'
            ),
            array(
                'key'=>'B',
                'value'=>'10'
            )
        );
        //合并单元格
        $mergecells_arr = array(
            array(
                'from'=>'A1',
                'to'=>'A2'
            ),
            array(
                'from'=>'B1',
                'to'=>'C1'
            )
        );
        $TOOLS->exportExcel($data,array('name'=>$name,'width_num'=>$width_num,'mergecells_arr'=>$mergecells_arr));exit;*/
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
            /*case 1:
                $setting_param = array();
                break;*/
            default:
                $setting_param = array(
                    'weight_arr'=>array(
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
                    ),
                    'color_arr'=>array('D','E','F','G','H','I','J','K','L','M'),
                    'clarity_arr'=>array('IF','VVS1','VVS2','VS1','VS2','SI1','SI2','SI3','I1','I2','I3'),
                );
                break;
        }
        return $setting_param;
    }
    public function getWeightOfferLists($weight,$param=array()){
        if(in_array($param['type'],array('carat_arr','single_arr'))){
            $type = $param['type'];
        }else{
            return;
        }
        $TOOLS = new \Org\Util\Tools();
        $weight_section = $TOOLS->getWeightSection($weight);
        $setting_param = $this->getOfferParam();
        $data = array();
        if(preg_match('/[-]+/',$weight)){
            $lists_search = $this->where(array('weight_section'=>$weight_section))->select();
        }else{
            $lists_search = $this->where(array('weight'=>$weight))->select();
        }
        $lists_search = $this->cateAllData($lists_search);
        foreach($setting_param['color_arr'] as $color_value){
            foreach($setting_param['clarity_arr'] as $clarity_value){
                $data[$color_value][$clarity_value] = array(
                    'weight'=>'',
                    'color'=>$color_value,
                    'clarity'=>$clarity_value,
                    'price'=>'',
                    'add_price'=>''
                );
            }
        }
        if(!empty($lists_search) && is_array($lists_search)){
            foreach($lists_search as $se_value){
                $data[$se_value['color']][$se_value['clarity']] = array(
                    'weight'=>$se_value['weight'],
                    'color'=>$se_value['color'],
                    'clarity'=>$se_value['clarity'],
                    'price'=>$se_value[$type]['price'],
                    'add_price'=>$se_value[$type]['add_price']
                );
            }
        }
        $setting_param = $this->getOfferParam();
        $title_top = $setting_param['clarity_arr'];
        $title_left = $setting_param['color_arr'];
        $all_data = array('lists'=>$data,'title_top'=>$title_top,'title_left'=>$title_left);
        return $all_data;
    }
}
