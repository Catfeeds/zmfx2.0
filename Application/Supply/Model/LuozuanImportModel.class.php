<?php
/**
 * 导入裸钻
 * User: Administrator
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Supply\Model;
Class LuozuanImportModel extends \Think\Model{

    protected $autoCheckFields = false;
    private   $delimiters      = ',';
    

    //文件行数
    private function getFileLinesCount($file_path,$maxLineSize=8192,$ending="\n"){

        $line = 0 ; //初始化行数
        if(!file_exists($file_path)){//文件不存在
            return $line;
        }
        if($fp = fopen($file_path , 'r')){//获取文件的一行内容
            while(stream_get_line($fp,$maxLineSize,$ending)){
                $line++;
            }
            fclose($fp);//关闭文件
        }
        return $line;

    }

    //获取一行数据
    public function getFileLines($filename, $startLine = 1, $endLine=100, $method='rb') {

        $content = array();
        $count = $endLine - $startLine;
        // 判断php版本（因为要用到SplFileObject，PHP>=5.1.0）
        if(version_compare(PHP_VERSION, '5.1.0', '>=')){
            $fp = new \SplFileObject($filename, $method);
            $fp->seek($startLine-1);// 转到第N行, seek方法参数从0开始计数
            for($i = 0; $i <= $count; ++$i) {
                $content[]=$fp->current();// current()获取当前行内容
                $fp->next();// 下一行
            }
        }else{//PHP<5.1
            $fp = fopen($filename, $method);
            if(!$fp) return 'error:can not read file';
            for ($i=1;$i<$startLine;++$i) {// 跳过前$startLine行
                fgets($fp);
            }
            for($i;$i<=$endLine;++$i){
                $content[]=fgets($fp);// 读取文件行内容
            }
            fclose($fp);
        }
        return array_filter($content); // array_filter过滤：false,null,''

    }

    public function autoToutf8($data,$to='utf-8'){ //字符串转换编码

        if(is_array($data)) {
            foreach($data as $key => $val) {
                $data[$key] = phpcharset($val, $to);
            }
        } else {
            $encode_array = array('ASCII', 'UTF-8', 'GBK', 'GB2312', 'BIG5');
            $encoded = mb_detect_encoding($data, $encode_array);
            $to = strtoupper($to);    //大写
            if($encoded != $to) {
                $data = mb_convert_encoding($data, $to, $encoded);
            }
        }
        return $data;

    }

    public function checkFields($file_path,$lang=LANG_SET){

        $luozuanFieldsData_rule_string = M('supply_goods_luozuan_cvs_mapping')->where("language_type='$lang'")->getField('rule_info');
        $luozuanFieldsData_rule        = array();
        foreach(explode('"',$luozuanFieldsData_rule_string) as $row){
            $_a                             = explode(':',$row);
            $luozuanFieldsData_rule[$_a[1]] = $_a[0];
        }

        $luozuanFieldsData_string = $this->getFileLines($file_path, 1, 1);//获取第一行数据
        $_luozuanFieldsData       = explode($this->delimiters,$luozuanFieldsData_string[0]);
        if(count($_luozuanFieldsData)<8){
            $_luozuanFieldsData       = explode(',', $luozuanFieldsData_string[0]);
        }
        $luozuanFieldsData        = array();
        foreach($_luozuanFieldsData as $key => $row){
            if(isset($luozuanFieldsData_rule[$row])){
                $luozuanFieldsData[$key] = $row;
            }
        }
        if(count($luozuanFieldsData) == 0 || count($luozuanFieldsData) != count($luozuanFieldsData_rule)){
            return false;
        }
        return $luozuanFieldsData;

    }

    public function importSupply($csvFile,$fields_data,$pagesize=500){

        $agent_id      = D('SupplyAccount') -> getAgentId();
        $LF            = D('LuozuanFormat');
        $dollar_huilv  = D('Config')        -> getOneZmConfigValue('dollar_huilv');
        $fileLines     = $this              -> getFileLinesCount($csvFile);//获取文件行数
        $j             = 1;
        $SGL           = M("supply_goods_luozuan");
        $biaoji_time   = time(); 

        for( $_i = 2; $_i < $fileLines; $_i += $pagesize ){	//从第二行开始读取数据（第一行为标题行）
            $startLine           = $_i;
            $endLine             = $_i + $pagesize - 1;
            $luozuanData         = $this -> getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
            $luozuanDataList     = array();
            foreach($luozuanData as $k => &$_row){
                $row  = explode($this->delimiters,$this->autoToutf8($_row));                                     
                if(count($row)<8){                          //4c，lab， 证书号, 价格,折扣  必须要有8列
                    $row = explode('"', $this->autoToutf8($_row));     //csv，一般用  ，"  分割
                    if($row<8){
                        continue;
                    }               
                }
                $data           = array();
                foreach( $fields_data as $key => $r ){
                    $data[$key] = $LF->_check($key,$row[$r]);                    
                }
                if(empty($data["certificate_type"])  or  $data["weight"] < 0.01 or  $data["dia_global_price"] < 500 or empty($data["certificate_number"]) ){
                    unset($data);
                    continue;
                }

                //两个字段互相关联，做特殊处理
                $data['certificate_number'] = $LF->_check('certificate_number',$data['certificate_number'],$data['certificate_type']);
                if($data !== false){
                    $data['dia_discount']  = 100 + $data['dia_discount'];
                    
                    $market_price      = $data['dia_global_price']*$data['weight'] * $dollar_huilv;   //市场价 = 国际克拉价*实际'weight'*美元汇率
                    $shop_price        = round($market_price*$data['dia_discount']/100,2);	          //本店价 = 市场价*'dia_discount'/100
                    $data['price']     = $shop_price;
                    $data['agent_id']  = $agent_id;
                    $data['biaoji_time']= $biaoji_time ;
                    //$luozuanDataList[] = $data;
                    $where['agent_id']           = $data['agent_id'];
                    $where['certificate_number'] = $data['certificate_number'];
                    $gid = $SGL->where($where)->getField('gid');
                    $where['gid'] = $gid;
                    $where['product_status'] = 0;
                    
                    if($gid){
                        $data['valid_status'] = 0;                        
                        $SGL->where($where)->save($data);
                    }else{
                        $SGL->data($data)->add();
                    }                    

                }else{
                    //错误处理,可以写入一个错误文档部署
                }
                $j++;
            }
            
            // if( count($luozuanDataList) > 0 ) {
            //     M("supply_goods_luozuan")->addAll($luozuanDataList);
            // }
            // unset($luozuanDataList);
        }

        //删除所有不是这一时间段的数据
        $SGL->where('product_status = 0 and agent_id = ' . $agent_id . ' and biaoji_time != ' . $biaoji_time)->delete();
        return true;
    }


    //导入的表格字段映射
    public function importLuozuanFieldMap($upload_title){
        foreach($upload_title as $val){
            $title[] = strtoupper($val);//大写
        }
        $maybeField = array(     //可能存在的对应
            'goods_name'         => array('goods_name', '产品名称', 'Diamond_name','ReferenceNum','ReferenceNumber','Stock','Stock Num','Stock_no','StockNo','StockNum','StockNumber','VenderStockNumber'),// '产品名称',
            'certificate_type'   => array('certificate_type', '证书类型', 'LAB',) ,//'证书类型',
            'certificate_number' => array('certificate_number', '证书编号', 'Cert No.', 'Rep No', 'CERT_NO', 'Certi No', 'Certificate', 'Certificate #'),// '证书编号',
            'location'           => array('location', '产地', 'Loc','Country','City','CityLocation','CountryLocation ','LotCountry'),//'产地',
            //    'quxiang'            => array('quxiang', '去向'),//'去向',
            'shape'              => array('shape', '形状', 'SH') ,// '形状',
            'weight'             => array('weight', '重量', 'WEIGHT', 'CRTWT', 'Cts', 'Carats', 'Carat', 'CaratSize', 'CaratWeight', 'Ct', 'CtSize', 'CtWeight'),// '重量',
            'color'              => array('color', '颜色', 'COLOR','col', 'Color', 'Colour'),// '颜色',
            'clarity'            => array('clarity', '净度', 'CLARTITY', 'Clr', 'Clar', 'Purity'),// '净度',
            'cut'                => array('cut', '切工', 'CUT','Cut Grade' ),//'切工',
            'polish'             => array('polish', '抛光', 'POLISH',  'Pol'),// '抛光',
            'symmetry'           => array('symmetry', '对称', 'SYMMETRY', 'Sym', 'Sym-metry', 'Symm'),// '对称',
            'fluor'              => array('fluor', '荧光', 'FLU', 'Flur', 'Flr', 'FLOURESENCE', 'Fls', 'Fluo Int', 'Fluorescence Intensity', 'FlrIntensity', 'Fluo Intensity', 'Fluor', 'Fluor Intensity', 'Fluorescence', 'Fluorescence Intensity', 'FluorescenceIntensity', 'FluorIntensity', 'FLO'),// '荧光',
            'milk'               => array('milk', '奶色', 'Milky', 'lus'),// '奶色',
            'coffee'             => array('coffee', '咖色',  'Brown','Col Tinge','Tinge','COL-SHADE'),// '咖色',
            'dia_table'          => array('dia_table', '台面', 'Table','Tbl','TBL','Table (%)','Table(%)','Table %','Table Percent','TablePct','TablePercent','Tbl'),// '台面',
            'dia_depth'          => array('dia_depth', '全深比', 'Depth', 'TD%', 'DP', 'Depth (%)', 'Depth(%)', 'Depth %', 'Depth', 'Depth Percent', 'DepthPct', 'DepthPercent', 'Dpth', 'TotalDepth'),// '全深比',
            'dia_size'           => array('Measurements', 'dia_size', '尺寸', 'size','MEA'),// '尺寸',
            'dia_global_price'   => array('dia_global_price', '国际报价', 'global_price', 'Rap price', 'rap', 'RAP_RTE', 'Rap Amt($)', 'Rap $'),// '国际报价',
            'dia_discount'       => array('dia_discount', '折扣', 'DIS','Disc%','RAP_DIS','Disc (%)','Disc %','Rapnet  Discount %'),// '折扣',
            'imageURL'           => array('imageURL', '图片链接地址', 'Images Address','Diamond Image','DiamondImage','Image','ImageFile','Photo'),// '图片链接地址',
            'videoURL'           => array('videoURL', '视频链接地址', 'Video Address'),// '视频链接地址',                
        );
        $fieldMap = array();  //字段对应
        foreach ($maybeField as $key=>$val){
            foreach ($val as $v){
                if(in_array(strtoupper($v), $title)){  //确定是否对应
                    $fieldMap[$key] = $v;
                    continue;
                }
            }            
        }
       
        return $fieldMap;


    }
}