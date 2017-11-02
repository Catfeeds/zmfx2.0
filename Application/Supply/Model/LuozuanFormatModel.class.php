<?php
/**
 * 裸钻格式检查
 * User: Administrator
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Supply\Model;
Class LuozuanFormatModel extends \Think\Model{
    protected $autoCheckFields = false;

    public function _check($field,$data,$type=''){
        $data = trim(strtoupper($data));
        switch ($field){
            case 'shape':
                    $shape   = $data;                   
                    $shapeNo = '';	//默认（标识非常规异形钻）
                    if($shape=='圆形' or $shape=='RB' or $shape =='RBC' or $shape=='RD' or $shape=='BR' or $shape=='ROUND' or $shape=='P-ROUND' or $shape=='ROUNDS' or $shape=='RO'){
                        $shapeNo="ROUND";
                    }
                    if($shape=='椭圆' or $shape=='OL' or $shape=='OV' or $shape=='OVAL' or $shape=='OVALS' or $shape=='P-OVAL'){
                        $shapeNo="OVAL";
                    }
                    if($shape=='马眼' or $shape=='MQ' or $shape=='MARQUISE' or $shape == 'M' or $shape=='MARQUIESS' or $shape=='P-MARQUISE'){
                        $shapeNo="MARQUISE";
                    }
                    if($shape=='心形' or $shape=='HT' or $shape=='HEART' or $shape=='HEARTS' or $shape=='P-HEART'){
                        $shapeNo="HEART";
                    }
                    if($shape=='水滴' or $shape=='PE' or $shape=='PR' or $shape=='PEAR' or $shape=='PEARS' or $shape=='P-PEAR'){
                        $shapeNo="PEAR";
                    }
                    if($shape=='方形' or $shape=='公主方' or $shape=='PR' or $shape=='PRINCESS' or $shape=='P-PRINCESS'){
                        $shapeNo="PRINCESS";
                    }
                    if($shape=='祖母绿' or $shape=='em' or $shape=='ASH' or $shape == 'E' or $shape =='EM' or $shape=='EMERALD' or $shape=='EMERALDS' or $shape =='SQUARE EMERALD' or $shape == 'EMERALD CRISS' or $shape=='P-EMERALD'){
                        $shapeNo="EMERALD";
                    }
                    if($shape=='枕形' or $shape=='上丁方' or $shape=='上丁方形' or $shape=='ASSCHER' or $shape=='ASSCHERS' or $shape == 'CUSHION BRILLIANT' or $shape=='垫形' or $shape=='CU' or $shape=='CUSHION' or $shape=='CUSHIONS' or $shape=='P-CUSHION'){
                        $shapeNo="CUSHION";
                    }
                    if($shape=='雷迪恩' or $shape=='雷蒂恩' or $shape=='雷地恩' or $shape =='RN' or $shape=='REDIANT' or $shape == 'RADIANT' or $shape == 'RAD' or $shape=='P-RADIANT'){
                        $shapeNo="RADIANT";
                    }
                    if($shape=='梯方' or $shape=='RECTANGLE' or $shape=='长方形' or $shape =='ST BUG' or $shape=='P-BAGUETTE'){
                        $shapeNo="BAGUETTE";
                    }
                    if($shape=='三角形' or $shape=='TRILLIANT' or $shape == 'TRIANGLE' or $shape == 'TRIANGULAR' or $shape=='P-TRILLIANT'){
                        $shapeNo="TRILLIANT";
                    }
                    $data = $shapeNo;
                break;
            case 'dia_global_price':
                    $data = round($data,2);
                break;
            case "certificate_type":
                    $str = $data;
                    if(stristr($str,'GIA')){
                        $str = 'GIA';
                    }elseif(stristr($str,'IGI')){
                        $str = 'IGI';
                    }elseif(stristr($str,'HRD')){
                        $str = 'HRD';
                    }elseif(stristr($str,'NGTC') or stristr($str,'国检')){
                        $str = 'NGTC';
                    }elseif(stristr($str,'国首')){
                        $str = '国首';
                    }
                    $data = $str;
                break;
            case 'certificate_number':
                if( empty($type) ){
                    $data     = $data;
                    break;
                }else{
                    $reportNo = $data;
                    switch(strtoupper($type)){
                        case 'GIA'://GIA
                            $reportNo = preg_replace('/[^0-9]{8,12}/', '', $reportNo);
                            break;
                        case 'IGI'://IGI
                            $reportNo = preg_replace('/[^0-9A-Z]{8,12}/i', '', $reportNo);
                            break;
                        case 'HRD'://HRD
                            $reportNo = preg_replace('/[^0-9]{9,12}/', '', $reportNo);
                            break;
                        case 'NGTC'://NGTC
                            $reportNo = preg_replace('/[^0-9A-Z]{8,12}/i', '', $reportNo);
                            break;
                        case '国首':
                            $reportNo = preg_replace('/[^0-9A-Z]{8,12}/i', '', $reportNo);
                            break;
                    }
                    $data = $reportNo;
                }
                break;
            case 'weight':
                $data = $this->checkDiamondWeight(trim($data));
                break;
            case 'color':
                $data = trim(strtoupper($data));
                break;
            case 'clarity':
                $data = trim(strtoupper($data));
                break;
            case 'cut':
                $str = $data;
                if(!empty($str)){
                    $str = strtoupper($str);
                    if($str=='I'){
                        $str = 'I';
                    }elseif($str=='EX' or $str == '3EX'){
                        $str = 'EX';
                    }elseif($str=='VG'){
                        $str = 'VG';
                    }elseif($str=='GD' or $str=='G'){
                        $str = 'GD';
                    }elseif($str=='F' or $str=='FR'){
                        $str = 'F';
                    }else if($str =='EX1' or $str == 'EX2' or $str == 'EX4' or $str == 'EX3' or $str == 'VG1' or $str == 'VG2' or $str == 'VG3' or $str == 'VG4'){
                        $str = '-';
                    }else{
                        $str = '';
                    }
                }
                $data = trim(strtoupper($str));
                break;
            case 'polish':
                $str         = $data;
                if(!empty($str)){
                    $str     = strtoupper($str);
                    if($str=='I'){
                        $str = 'I';
                    }elseif($str=='EX'){
                        $str = 'EX';
                    }elseif($str=='VG'){
                        $str = 'VG';
                    }elseif($str=='GD' or $str=='G'){
                        $str = 'GD';
                    }elseif($str=='F' or $str=='FR'){
                        $str = 'F';
                    }
                }
                $data = strtoupper($str);
                break;
            case 'symmetry':
                $str = $data;
                if(!empty($str)){
                    $str = strtoupper($str);
                    if($str=='I'){
                        $str = 'I';
                    }elseif($str=='EX'){
                        $str = 'EX';
                    }elseif($str=='VG' or $str == 'VG2'){
                        $str = 'VG';
                    }elseif($str=='GD' or $str=='G'){
                        $str = 'GD';
                    }elseif($str=='F' or $str=='FR'){
                        $str = 'F';
                    }
                }
                $data = strtoupper($str);
                break;
            case 'fluor':
                $str = strtoupper(trim($data));
                if(!empty($str)){
                    $str = strtoupper($str);
                    if($str=='NONE' or $str == 'NON' or $str == 'FL0'){
                        $str = 'N';
                    }elseif($str=='FNT' or $str == 'FAINT' or $str == 'SLT' or $str == 'VSL' or $str == 'SL' or $str == 'V.SL' or $str == 'FL1' or $str =='FA' or $str =='FA-YL'){
                        $str = 'F';
                    }elseif($str=='MED' or $str == 'MEDIUM' or $str == 'FL2' or $str == 'MD' or $str=='MD-BL' or $str=='MD-YL'){
                        $str = 'M';
                    }elseif($str=='ST' or $str=='STG' or $str == 'STRONG' or $str == 'FL3' or $str=='ST-BL'){
                        $str = 'S';
                    }elseif($str=='V-STG' or $str == 'VERY STRON' or $str == 'VST' or $str == 'VSTG' or $str == 'FL4' or $str=='VSTB'){
                        $str = 'VS';
                    }else{
                        $str = $str;
                    }
                }
                $data = trim(strtoupper($str));
                break;
            case 'milk':
                $str = strtoupper(trim($data));
                if($str == '' or $str== 'M0' or $str == 'NO MILKY' or $str == '-' or $str == 'N' or $str == 'EX' or $str == 'VG' or $str=='NONE'){
                    $str = "无奶";
                }else if($str == 'MINOR MILKY' or $str == 'ML1' or $str == 'M1' or $str == 'ML-01' or $str == 'ML-1' or $str=='ML1' or $str=='ML0.5' or $str=='GD'){
                    $str =  "浅奶";
                }else if($str == 'MEDIUM MILKY' or $str == 'ML2' or $str == 'M2' or $str == 'ML-2' or $str=='ML1.5' or $str=='ML2'){
                    $str =  "中奶";
                }else if($str == 'GENUINE MILKY' or $str == 'ML3' or $str == 'M3' or $str == 'M4' or $str == 'TOTALLY MILKY' or $str == 'ML-3' or $str=='ML2.5' or $str=='ML3'){
                    $str =  "深奶";
                }else if($str == 'SLIGHTLY MILKY OR HAZY'){
                    $str = "轻奶";
                }else if($str == 'GD'){
                    $str = "带奶";
                }else{
                    $str = $str;
                }
                $data = $str;
                break;
            case 'location':
                $str = $data;
                if($str == 'HK' or $str == 'HONGKONG'){
                    $str = '香港';
                }else if($str == 'IND' or $str == 'INDIAN'){
                    $str = '印度';
                }
                $data = $str;
            break;
            case 'coffee':
                $str = strtoupper(trim($data));
                
                if($str == '' or $str == 'WH' or $str == 'NO BROWN' or $str == 'WHITE' or $str == '-' or $str == 'N' or $str == 'OWH'){
                    $str = "无咖";
                }else if($str == 'LIGHT BROWN' or $str == 'VB' or $str == 'BR1' or $str == 'BRN1' or $str == 'BRN1, CULET OPEN'  or $str == 'LBR'){
                    $str = "浅咖";
                }else if($str == 'MEDIUM BROWN' or $str == 'LB' or $str == 'BR2' or $str == 'BRN2' or $str == 'BRN2, CULET OPEN'){
                    $str = "中咖";
                }else if($str == 'GENUINE BROWN' or $str == 'DB' or $str == 'BR3' or $str == 'BRN3'){
                    $str = "深咖";
                }else if($str == 'BROWN' or $str == "GRN"  or $str == 'BR'){
                    $str = "带咖";
                }else if($str == 'MIXTING' or $str == 'MT' or $str == 'MIXTINGE' or $str == 'YL' or $str == 'YLB' or $str == 'BYL' or $str == 'GRYLG'){
                    $str = "咖黄";
                }else if($str == 'MIX TINGE1' or $str == 'FTYLB'){
                    $str = "浅咖黄";
                }else if($str == 'MIX TINGE2'){
                    $str = "中咖黄";
                }else if($str == 'MIX TINGE3' or $str == 'BRYL'){
                    $str = "深咖黄";
                }else if($str == 'NV' or $str == 'SLIGHTLY BROWN  -  NOT PROBLEMATIC' or $str == 'FRBR'  or $str == 'FTBR' ){
                    $str = "轻咖";
                }else if($str == 'BRN4'){
                    $str = "重咖";
                }else if($str == 'GRY'){
                    $str = "带灰";
                }else if($str == 'FTGR'){
                    $str = "轻绿";
                }else if($str == 'GR'){
                    $str = "绿";
                }else if($str == 'BGY'){
                    $str = "绿咖";
                }else if($str == 'FTGNY'){
                    $str = "轻黄绿";
                }else if($str == 'FP'){
                    $str = "浅粉";
                }else if($str == 'GNGR'){
                    $str = "绿灰";
                }else if($str == 'GNY'){
                    $str = "绿黄";
                }else if($str == 'YLGN'){
                    $str = "黄绿";
                }else if($str == 'PINK'){
                    $str = "带粉";
                }else if($str == 'YBR'){
                    $str = "带黄咖";
                }else if($str == 'FYL'){
                    $str = "带黄";
                }else{
                    $str = $str;
                }
                $data = $str;
                break;
            default:
                break;
        }

        return $data;
    }

    public function checkDiamondWeight($weight){
        $tmpweight = sprintf("%.2f", $weight);
        if( $tmpweight != $weight){
            return  bcdiv(floatval($weight), 1 ,2);
        }else{
            return $weight;
        }
    }

}