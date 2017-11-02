<?php
/**
 * Created by PhpStorm.
 * User: Sunyang
 * Date: 2017/4/20
 * Time: 12:28
 * 公共方法
 */
namespace Org\Util;
class Tools{
    //传入一个重量，返回该重量所属于的区间的key
    public function getWeightSection($weight=0,$type_arr=-1){ 
        $weight_section = 0;
        if($type_arr==-1){
            $setting_param = D('Common/GoodsOfferInfo')->getOfferParam();
            $type_arr = $setting_param['weight_arr'];
        }
        if(!empty($type_arr) && is_array($type_arr)){
            foreach($type_arr as $key=>$value){
                if(($weight>=$value['from'] && $weight<=$value['to']) || $weight==$value['name']){
                    $weight_section = $key;
                    break;
                }
            }
        }
        return $weight_section;
    }

    public function getWeightPoint($weight,$type_arr,$agent_id){
        if(!empty($type_arr) && is_array($type_arr)){
            foreach($type_arr as $key=>$value){
                if(($weight>=$value['min_value'] && $weight<=$value['max_value'])){
                    $point = $value['point_value'];
                    break;
                }
            }
        }else{
            $point = M('goods_offer_point')->field('point_value')->where('min_value<='.$weight.' AND max_value>'.$weight.' AND max_value = '.$agent_id )->find();
        }
        return $point>0 ? $point : 0;
    }

    public function exportExcel($data,$param = array()){
        ob_end_clean();//清除缓冲区,避免乱码
        import("Org.Util.PHPExcel");
        $objPHPExcel=new \PHPExcel();
        $houzui = $param['csv'] ? '.csv' : '.xls';
        $name = $param['name'] ? $param['name'] : 'Excel';
        $width_num = $param['width_num'];
        $xlsTitle = $param['title'] ? $param['title'] : 'Excel';
        $mergecells_arr = $param['mergecells_arr'];
        $all_temp_da = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("system")
            ->setLastModifiedBy("system")
            ->setSubject("Excel")
            ->setDescription("Excel")
            ->setKeywords("Excel")
            ->setCategory("result file");
        //设置行高
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        //宽居中
        //$objPHPExcel->getActiveSheet()->getColumnDimension()->setAutoSize(true);
        //设置当前操作的sheet
        $objPHPExcelTable = $objPHPExcel->setActiveSheetIndex(0);
        //设置当前操作的sheet的标题
        $objPHPExcel->getActiveSheet()->setTitle($xlsTitle);

        //设置宽
        if(!empty($width_num)){
            foreach($width_num as $num_value){
                if($num_value['value']>0){
                    $objPHPExcel->getActiveSheet()->getColumnDimension($num_value['key'])->setWidth($num_value['value']);
                }
            }
        }
        //合并单元格
        if(!empty($mergecells_arr)){
            foreach($mergecells_arr as $merge_value){
                //合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells($merge_value['from'].':'.$merge_value['to']);
                //居中
                //$objPHPExcel->getActiveSheet()->getStyle($merge_value['from'])->getAlignment()->setHorizontal("center");
            }
        }

        foreach ($data as $k => $v) {
            $num = $k + 1;
            foreach($v as $keyer => $valuer){
                //居中
                $objPHPExcel->getActiveSheet()->getStyle($all_temp_da[$keyer])->getAlignment()->setHorizontal("center");
                $objPHPExcelTable ->setCellValue($all_temp_da[$keyer] . $num, $valuer);
            }
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header('Content-Disposition: attachment;filename="' . $name .$houzui. '"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        return;
    }
    public function readUploadExcel($file,$param){
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('xls', 'xlsx');
        $upload->savePath = '/Uploads/excel/';
        $upload->autoSub = false;
        $info   =   $upload->uploadOne($file);
        if($info){
            $fileName = $info['savepath'].$info['savename'];
            $exts     = $info['ext'];
            $data = $this->readExcel($fileName, $exts ,$param);
        }else{
            $data = array();
        }
        return $data;
    }

    public function readExcel($fileName,$exts='xls',$param){
        //$title = array('location','type_name','goods_sn','goods_weight');

        import("Org.Util.PHPExcel");
        $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load('./Public/'.$fileName,$exts);
        $objPHPExcel->setActiveSheetIndex(0);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestColumn = $objWorksheet->getHighestColumn();

        $title = $param['title'];
        $row_key = $param['row_key'] ? $param['row_key'] : 0;
        //是否用Key代表excel表格的行数,1是，0否
        $from_height_from = $param['from_height_from']>0 ? $param['from_height_from'] : 1;
        $from_height_to = $param['from_height_to']>0 ? $param['from_height_to'] : $objWorksheet->getHighestRow();
        $from_width_from = $param['from_width_from']>0 ? $param['from_width_from'] : 1;
        $from_width_from--;
        $from_width_to = $param['from_width_to']>0 ? $param['from_width_to'] : \PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        if(!empty($title)){
            //$from_height_from = 1;
            $from_width_from = 0;
            $from_width_to = count($title);
            for ($row = $from_height_from; $row <= $from_height_to; $row++) {
                $temp = array();
                for ($col = $from_width_from; $col < $from_width_to; $col++) {
                    $temp[$title[$col]] = (string)$objWorksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                }
                if($row_key){
                    $excelData[$row] = $temp;
                }else{
                    $excelData[] = $temp;
                }

            }
        }else{
            for ($row = $from_height_from; $row <= $from_height_to; $row++) {
                $temp = array();
                for ($col = $from_width_from; $col < $from_width_to; $col++) {
                    $temp[] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                }
                if($row_key){
                    $excelData[$row] = $temp;
                }else{
                    $excelData[] = $temp;
                }
            }
        }
        return $excelData;
    }

    public function arrayGroupByField($data,$field){
        if(empty($field)){
            return $data;
        }
        $temp = array();
        foreach($data as $key=>$value){
            if(!isset($value[$field])){
                continue;
            }
            $temp[$value[$field]] = $value;
        }
        return $temp;
    }

}
?>
