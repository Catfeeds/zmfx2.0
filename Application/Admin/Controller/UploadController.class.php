<?php
/**
 * 产品上传模块  by cool
 */
namespace Admin\Controller;
use Think\Page;
header("Content-Type: text/html; charset=UTF-8");
class UploadController extends AdminController { 
	function getFiletype($val){
		$val = strtoupper($val);
		if(stristr($val, "ERROR")){
			$filetype = "error";
		}elseif(stristr($val,"FMD")){
			$filetype = "FMD";
		}elseif(stristr($val,"FME")){
			$filetype = "FME";
		}elseif(stristr($val,"FMH")){
			$filetype = "FMH";
		}elseif(stristr($val,"FMI")){
			$filetype = "FMI";
		}elseif(stristr($val,"FMJ")){
			$filetype = "FMJ";
		}elseif(stristr($val,"FML")){
			$filetype = "FML";
		}elseif(stristr($val,"FMM")){
			$filetype = "FMM";
		}elseif(stristr($val,"FMN")){
			$filetype = "FMN";
		}elseif(stristr($val,"FMQ")){
			$filetype = "FMQ";
		}elseif(stristr($val,"FMP")){
			$filetype = "FMP";
		}elseif(stristr($val,"ZM")){
			$filetype = "ZM";
		}elseif(stristr($val,"SH")){
			$filetype = "SH";
		}elseif(stristr($val,"FMR")){
			$filetype = "FMR";
		}elseif(stristr($val,"COMMON")){
			$filetype = "COMMON";
		}else{
			$filetype = "未知";
		}
		return $filetype;
	}
	
	function importCSV2DB($csvFile,$filetype,$pagesize=500){                 
		switch($filetype){
			case "FMD":$this->importFMD($csvFile);break;
			case "FME":$this->importFME($csvFile);break;
			case "FMH":$this->importFMH($csvFile);break;
			case "FMI":$this->importFMI($csvFile);break;
			case "FMJ":$this->importFMJ($csvFile);break;
			case "FML":$this->importFML($csvFile);break;
			case "FMM":$this->importFMM($csvFile);break;
			case "FMN":$this->importFMN($csvFile);break;
			case "FMQ":$this->importFMQ($csvFile);break;
			case "FMP":$this->importFMP($csvFile);break;
			case "ZM":$this->importZM($csvFile);break;
			case "SH":$this->importSH($csvFile);break;
			case "FMR":$this->importFMR($csvFile);break;
			case "COMMON":$this->importCOMMON($csvFile);break;
		}             
	}
	
	function getFileLines($filename, $startLine = 1, $endLine=100, $method='rb') {
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
	
	function importFMD($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMD%'")->delete();
		$filename = "../admin/luozuan/new/Error_FMD".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);  
		$filetype = "FMD";
		$j = 1;   
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行） 
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据   
			$sql_goods = ''; 
			$luozuanDataList = array(); 
			foreach($luozuanData as $k=>&$row){  
				$row = explode('"',$this->autoToutf8($row));
				$row[1] = $this->getDiamondsShapeNo($filetype,$row[1]);
				if($this->checkData($row[1],$row[9],$row[41],$row[2],$row[3],$row[4],$row[10],$row[12],$row[25],$row[24])){
					$temp = array($filetype.$j,$row[1],$row[9],$row[41],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[18],$row[19],$row[15]."*".$row[16]."*".$row[17],$row[10],$row[12],$row[33],"订货",$row[25],$row[24]);
					$data = $this->combineData($filetype,$temp);  
					/*
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){ 
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}  
					*/         
					//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
					$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
					$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
					$data['price'] = $shop_price; 
					$luozuanDataList[] = $data;
				}else{
				    $data2 = array("FMD".$j,$row[1],$row[9],$row[41],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[18],$row[19],$row[10],$row[12],$row[25],$row[24]); 
				    $this->writeDataIntoCsv($filename,$data2);
				} 
				$j++;
			}  
			M("goods_luozuan")->addAll($luozuanDataList);  
			unset($luozuanDataList);
		}                             
		fclose($fp);
	}
	
	function combineData($filetype,$row){
		$data['goods_name'] = $row[0];
		$data['shape'] = $row[1];
		$data['certificate_type'] = $this->formatDiamondReportType($row[2]);
		$data['certificate_number'] = $this->formatDiamondReportNo($row[3],$row[2]);
		$data['weight'] = $this->formatDiamondClarity($row[4]);
		$data['color'] = $this->formatDiamondColor($row[5]);
		$data['clarity'] = $this->formatDiamondClarity($row[6]);
		$data['cut'] = $this->formatDiamondCut($row[7]);
		$data['polish'] = $this->formatDiamondPolish($row[8]);
		$data['symmetry'] = $this->formatDiamondSymmetry($row[9]);
		$data['fluor'] = $this->formatDiamondFlour(strtoupper($row[10]));
		$data['dia_table'] = $row[11];
		$data['dia_depth'] = $row[12];
		$data['dia_size'] = $row[13];
		$data['dia_global_price'] = round($row[14],2);
		$data['dia_discount'] = $this->formatDiamondDiscount($filetype,$row[15]);
		$data['location'] = $this->formatDiamondLocation($row[16]);
		$data['quxiang'] = $row[17];
		$data['milk'] = $this->formatDiamondMilky($row[18]);
		$data['coffee'] = $this->formatDiamondCoffee($row[19]);
		$data['goods_number'] = 1;
		$data['tid'] = 0;
		$data['belongs_id'] = 1;
		return $data;
	}

	function writeDataIntoCsv($filename,$data){
		$data = implode('"',$data); // 用 ' 分割成字符串
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建 
		$data_str =$data."\r\n"; //加入换行符
		$p = fwrite($fp, $data_str); 
	}


	function importFME($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FME%'")->delete();
		$filename = "../admin/luozuan/new/Error_FME".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;
		$filetype = "FME";
		for($_i=6;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			$luozuanDataList = array();         
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode(',',$this->autoToutf8($row));
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
	//			print_r($row);die;
				$row[3] = $this->getDiamondsShapeNo($filetype,$row[3]);
				if($this->checkData($row[3],$row[20],$row[31],$row[4],$row[6],$row[5],$row[8],$row[10],$row[26],$row[7],'订货',$row[2])){
					//$data = $tmp;
					$data['goods_name'] = "FME".($j);
					$data['shape'] = $row[3];
					$data['certificate_type'] = $this->formatDiamondReportType($row[20]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[31],$row[20]);
					$data['weight'] = $this->formatDiamondClarity($row[4]);
					$data['color'] = $this->formatDiamondColor($row[6]);
					$data['clarity'] = $this->formatDiamondClarity($row[5]);
					$data['fluor'] = $this->formatDiamondFlour($row[16]);
					$data['cut'] = $this->formatDiamondCut($row[13]);
					$data['polish'] = $this->formatDiamondPolish($row[14]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[15]);
					$data['dia_table'] = $row[18];
					$data['dia_depth'] = $row[17];
					$data['dia_size'] = $row[19];
					$data['dia_global_price'] = round($row[8],2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FME",$row[10]);
					$data['location'] = $this->formatDiamondLocation($row[35]);
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky($row[26]);
					$data['coffee'] = $this->formatDiamondCoffee($row[7]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					/*
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					*/         
					//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
					$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
					$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
					$data['price'] = $shop_price; 
					$luozuanDataList[] = $data;            
					$num++; 
					$j++;
				}else{
				    $data2 = array("FME".$j,$row[3],$row[20],$row[31],$row[4],$row[6],$row[5],$row[13],$row[14],$row[15],$row[16],$row[18],$row[17],$row[8],$row[10],$row[35],$row[26],$row[7]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}     
			M("goods_luozuan")->addAll($luozuanDataList);  
		}
		fclose($fp);
	}

	function importFMH($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMH%'")->delete();
		$filename = "../admin/luozuan/new/Error_FMH".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;
		$filetype = "FMH";
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode(',',$this->autoToutf8($row));
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				$row[2] = $this->getDiamondsShapeNo($filetype,$row[2]); 
				if($this->checkData($row[2],$row[20],$row[21],$row[3],$row[4],$row[5],$row[23],$row[24],$row[19],$row[18])){
					//键值转换  
					$data['goods_name'] = "FMH".($j);
					$data['shape'] = $row[2];
					$data['certificate_type'] = $this->formatDiamondReportType($row[20]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[21],$row[20]);
					$data['weight'] = $this->formatDiamondClarity($row[3]);
					$data['color'] = $this->formatDiamondColor($row[4]);
					$data['clarity'] = $this->formatDiamondClarity($row[5]);
					$data['cut'] = $this->formatDiamondCut($row[6]);
					$data['polish'] = $this->formatDiamondPolish($row[7]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[8]);
					$data['fluor'] = $this->formatDiamondFlour($row[9]);
					$data['dia_table'] = $row[11];
					$data['dia_depth'] = $row[10];
					$data['dia_size'] = $this->formatDiamondSize($row[12],$row[13],$row[14]);
					$data['dia_global_price'] = round($row[23]/(100-$row[24])*100,2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FMH",$row[24]);
					$data['location'] = $this->formatDiamondLocation($row[25]);
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky($row[19]);
					$data['coffee'] = $this->formatDiamondCoffee($row[18]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
					$data2 = array("FMH".$j,$row[2],$row[20],$row[21],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[23]/(100-$row[24])*100,$row[24],$row[25],$row[19],$row[18]); 
					$this->writeDataIntoCsv($filename,$data2);
				}
			}
		}
		fclose($fp);
	}

	function importFMI($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMI%'")->delete();
		$filename = "../admin/luozuan/new/Error_FMI".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;
		$filetype = "FMI";
		for($_i=8;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode('"',$this->autoToutf8($row));
				//print_r($row);die;
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				$row[7] = $this->getDiamondsShapeNo($filetype,$row[7]); 
				if($this->checkData($row[7],$row[6],$row[9],$row[13],$row[11],$row[12],$row[14],$row[16],'-',$row[10],"订货",$row[5])){
					$data['goods_name'] = "FMI".($j);
					$data['shape'] = $row[7];
					$data['certificate_type'] = $this->formatDiamondReportType($row[6]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[9],$row[6]);
					$data['weight'] = $this->formatDiamondClarity($row[13]);
					$data['color'] = $this->formatDiamondColor($row[11]);
					$data['clarity'] = $this->formatDiamondClarity($row[12]);
					$data['fluor'] = $this->formatDiamondFlour($row[21]);
					$data['cut'] = $this->formatDiamondCut($row[18]);
					$data['polish'] = $this->formatDiamondPolish($row[19]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[20]);
					$data['dia_table'] = $row[26];
					$data['dia_depth'] = $row[25];
					$data['dia_size'] = $this->formatDiamondSize($row[22],$row[23],$row[24]);
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[14]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FMI",$row[16]);
					$data['location'] = "国外";
					$data['quxiang'] = "订货";
					$data['milk'] = "无奶";
					$data['coffee'] = $this->formatDiamondCoffee($row[10]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
				    $data2 = array("FMI".$j,$row[7],$row[6],$row[9],$row[13],$row[11],$row[12],$row[21],$row[18],$row[19],$row[20],$row[26],$row[25],$row[14],$row[16],$row[38],"NO MILKY",$row[10]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}
		fclose($fp);
	}


	function importFMJ($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMJ%'")->delete();
		$filename = "../admin/luozuan/new/Error_FMJ".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;   
		$filetype = "FMJ";               
		for($_i=2;$_i<=$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = ''; 
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				
				$row = explode('"',$this->autoToutf8($row));  
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				$row[4] = $this->getDiamondsShapeNo($filetype,$row[4]); 
				if($this->checkData($row[4],$row[21],$row[25],$row[5],$row[6],$row[8],$row[42],$row[14],$row[30],$row[7])){   
					$data['goods_name'] = "FMJ".($j);
					$data['shape'] = $row[4];
					$data['certificate_type'] = $this->formatDiamondReportType($row[21]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[25],$row[21]);
					$data['weight'] = $this->formatDiamondClarity($row[5]);
					$data['color'] = $this->formatDiamondColor($row[6]);
					$data['clarity'] = $this->formatDiamondClarity($row[8]);
					$data['fluor'] = $this->formatDiamondFlour($row[13]);
					$data['cut'] = $this->formatDiamondCut($row[10]);
					$data['polish'] = $this->formatDiamondPolish($row[11]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[12]);
					$data['dia_table'] = $row[18];
					$data['dia_depth'] = $row[17];
					$data['dia_size'] = $this->formatDiamondSize($row[15],$row[16],$row[31]);
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[42]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FMJ",$row[14]);
					$data['location'] = "国外";
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky($row[30]);
					$data['coffee'] = $this->formatDiamondCoffee($row[7]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;  
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
				    $data2 = array("FMJ".$j,$row[4],$row[21],$row[25],$row[5],$row[6],$row[8],$row[13],$row[10],$row[11],$row[12],$row[18],$row[17],$row[42],$row[14],"订货",$row[30],$row[7]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}
		fclose($fp);
	}

	function importFML($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FML%'")->delete();
		$filename = "../admin/luozuan/new/Error_FML".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data1 = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data1);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;       
		$filetype = "FML";         
		for($_i=7;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode('"',$this->autoToutf8($row)); 
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown,$location){                    
				if($this->checkData("ROUND",$row[1],$row[0],$row[6],$row[7],$row[8],$row[14],$row[15],"-","-",$row[3])){
					$data['goods_name'] = "FML".($j);
					$data['shape'] = $this->getDiamondsShapeNo($filetype,'ROUND');
					$data['certificate_type'] = $this->formatDiamondReportType($row[1]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[0],$row[1]);
					$data['weight'] = $this->formatDiamondClarity($row[6]);
					$data['color'] = $this->formatDiamondColor($row[7]);
					$data['clarity'] = $this->formatDiamondClarity($row[8]);
					$data['fluor'] = $this->formatDiamondFlour($row[13]);
					$data['cut'] = $this->formatDiamondCut($row[9]);
					$data['polish'] = $this->formatDiamondPolish($row[10]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[11]);
					$data['dia_table'] = "";
					$data['dia_depth'] = "";
					$data['dia_size'] = "";
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[14]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FML",$row[15]);
					$data['location'] = "深圳";
					$data['quxiang'] = "现货";
					$data['milk'] = $this->formatDiamondMilky('');
					$data['coffee'] = $this->formatDiamondCoffee('');
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					$data['imageURL'] = '-';
					$data['videoURL'] = '-';
					$data['type'] = 0;
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}                  
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price;    
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
				    $data2 = array("FML".$j,"ROUND",$row[1],$row[0],$row[6],$row[7],$row[8],$row[13],$row[9],$row[10],$row[11],"","",$row[14],$row[15],"In Stock",'',''); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}
		fclose($fp);
	}

	function importFMM($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMM%'")->delete();
		$filename = "../admin/luozuan/new/Error_FMM".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$temp = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$temp);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;
		$filetype = "FMM";
		for($_i=6;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据  
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode(',',$this->autoToutf8($row));
				foreach($row as $key=>$val){
					$row[$key] = substr(trim($val),1,-1);
				}
				  
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				$row[2] = $this->getDiamondsShapeNo($filetype,$row[2]); 
				if($this->checkData($row[2],$row[12],$row[13],$row[3],$row[4],$row[5],$row[15],$row[18],"-","-",'订货',$row[31])){
					$data['goods_name'] = "FMM".($j);
					$data['shape'] = $row[2];
					$data['certificate_type'] = $this->formatDiamondReportType($row[12]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[13],$row[12]);
					$data['weight'] = $this->formatDiamondClarity($row[3]);
					$data['color'] = $this->formatDiamondColor($row[4]);
					$data['clarity'] = $this->formatDiamondClarity($row[5]);
					$data['cut'] = $this->formatDiamondCut($row[6]);
					$data['polish'] = $this->formatDiamondPolish($row[7]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[8]);
					$data['fluor'] = $this->formatDiamondFlour($row[9]);
					$data['dia_table'] = $row[22];
					$data['dia_depth'] = $row[21];
					$data['dia_size'] = $row[11];
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[15]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FMM",$row[18]);
					$data['location'] = "国外";
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky('');
					$data['coffee'] = $this->formatDiamondCoffee('');
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}                  
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}  
					$j++;
				}else{
				    $data2 = array("FMM".$j,$row[1],$row[13],$row[14],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[11],$row[10],$row[15],$row[12],"Ding Huo",'',''); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		} 
		fclose($fp);
	}


	function importFMN($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMN%'")->delete();  
		$filename = "../admin/luozuan/new/Error_FMN".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Fluor","Cut","Polish","Symmetry","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;
		$filetype = "FMN";
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode('"',$this->autoToutf8($row));
				//print_r($row);die;
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				$row[4] = $this->getDiamondsShapeNo($filetype,$row[4]); 
				if($this->checkData($row[4],$row[2],$row[3],$row[5],$row[6],$row[7],$row[12],$row[13],$row[31],$row[28])){
					$data['goods_name'] = "FMN".($j);
					$data['shape'] = $row[4];
					$data['certificate_type'] = $this->formatDiamondReportType($row[2]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[3],$row[2]);
					$data['weight'] = $this->formatDiamondClarity($row[5]);
					$data['color'] = $this->formatDiamondColor($row[6]);
					$data['clarity'] = $this->formatDiamondClarity($row[7]);
					$data['cut'] = $this->formatDiamondCut($row[8]);
					$data['polish'] = $this->formatDiamondPolish($row[9]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[10]);
					$data['fluor'] = $this->formatDiamondFlour($row[11]);
					$data['dia_table'] = $row[16];
					$data['dia_depth'] = $row[21];
					$data['dia_size'] = $row[15];
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[12]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount("FMN",$row[13]);
					$data['location'] = "国外";
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky($row[31]);
					$data['coffee'] = $this->formatDiamondCoffee($row[28]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
				    $data2 = array("FMN".$j,$row[4],$row[2],$row[3],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[16],$row[21],$row[12],$row[13],"Ding Huo",$row[31],$row[28]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}
		fclose($fp);
	}
	function importFMQ($csvFile,$pagesize=500){
		global $db,$ecs;
		$type = "FMQ";
		M("goods_luozuan")->where("goods_name like '%FMQ%'")->delete();    
		$filename = "../admin/luozuan/new/Error_$type".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$temp = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Fluor","Cut","Polish","Symmetry","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$temp);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1; 
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			$keys = array(0=>'编号',1=>'形状',2=>'证书',3=>'去向',4=>'证书号',5=>'重量',6=>'颜色',7=>'净度',8=>'切工',9=>'抛光',10=>'对称',11=>'荧光',12=>'全深',13=>'台宽',14=>'尺寸',15=>'国际报价',16=>'折扣',17=>'产地',18=>'奶色',19=>'咖色',20=>'颜色备注'); //自定义字段
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode('"',$this->autoToutf8($row));
				
				$row[2] = "GIA";
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				$row[0] = $this->getDiamondsShapeNo($type,$row[0]); 
				if($this->checkData($row[0],"GIA",$row[3],$row[4],$row[6],$row[5],$row[13],$row[15],$row[12],$row[11])){
					$data['goods_name'] = $type.($j);
					$data['shape'] = $row[0];
					$data['certificate_type'] = $this->formatDiamondReportType("GIA");
					$data['certificate_number'] = $this->formatDiamondReportNo($row[3],$row[2]);
					$data['weight'] = $this->formatDiamondClarity($row[4]);
					$data['color'] = $this->formatDiamondColor($row[6]);
					$data['clarity'] = $this->formatDiamondClarity($row[5]);
					$data['cut'] = $this->formatDiamondCut($row[7]);
					$data['polish'] = $this->formatDiamondPolish($row[8]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[9]);
					$data['fluor'] = $this->formatDiamondFlour($row[10]);
					$data['dia_table'] = $row[20];
					$data['dia_depth'] = $row[19];
					$data['dia_size'] = $this->formatDiamondSize($row[16],$row[17],$row[18]);
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[13]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount($type,$row[15]);
					$data['location'] = $this->formatDiamondLocation($row[22]);
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky($row[12]);
					$data['coffee'] = $this->formatDiamondCoffee($row[11]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
							//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
							$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
							$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
							$data['price'] = $shop_price; 
							M("goods_luozuan")->data($data)->add();
							$num++; 
						}
						$j++;
				}else{
				    $data2 = array("FMQ".$j,$row[4],$row[2],$row[3],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[16],$row[21],$row[12],$row[13],"订货",$row[31],$row[28]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}             
	}

	function importFMP($csvFile,$pagesize=500){
		global $db,$ecs;
		$type = "FMP";
		M("goods_luozuan")->where("goods_name like '%FMP%'")->delete();                          
		$filename = "../admin/luozuan/new/Error_$type".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$temp = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Fluor","Cut","Polish","Symmetry","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$temp);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$filetype = "FMP";
		$j = 1;
		for($_i=6;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			$keys = array(0=>'编号',1=>'形状',2=>'证书',3=>'去向',4=>'证书号',5=>'重量',6=>'颜色',7=>'净度',8=>'切工',9=>'抛光',10=>'对称',11=>'荧光',12=>'全深',13=>'台宽',14=>'尺寸',15=>'国际报价',16=>'折扣',17=>'产地',18=>'奶色',19=>'咖色',20=>'颜色备注'); //自定义字段
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode('"',$this->autoToutf8($row));
				
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				  
				$row[0] = $this->getDiamondsShapeNo($filetype,$row[0]);   
				if($this->checkData($row[0],$row[29],$row[32],$row[3],$row[8],$row[9],$row[6],$row[7],$row[19],$row[28])){
					$data['goods_name'] = $type.($j);
					$data['shape'] = $row[0];
					$data['certificate_type'] = $this->formatDiamondReportType($row[29]); // 证书类型
					$data['certificate_number'] = $this->formatDiamondReportNo($row[32],$row[29]);
					$data['weight'] = $this->formatDiamondClarity($row[3]);
					$data['color'] = $this->formatDiamondColor($row[8]);
					$data['clarity'] = $this->formatDiamondClarity($row[9]);
					$data['cut'] = $this->formatDiamondCut($row[10]);
					$data['polish'] = $this->formatDiamondPolish($row[11]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[12]);
					$data['fluor'] = $this->formatDiamondFlour($row[13]);
					$data['dia_table'] = $row[17];
					$data['dia_depth'] = $row[16];
					$data['dia_size'] = $row[15];
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[6]),2);
					$data['dia_discount'] = $this->formatDiamondDiscount($type,$row[7]);
					$data['location'] = "国外";
					$data['quxiang'] = "订货";
					$data['milk'] = $this->formatDiamondMilky($row[19]);
					$data['coffee'] = $this->formatDiamondCoffee($row[28]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
							//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
							$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
							$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
							$data['price'] = $shop_price; 
							M("goods_luozuan")->data($data)->add();
							$num++; 
						}
						$j++;
				}else{
				    $data2 = array("FMP".$j,$row[4],$row[2],$row[3],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[16],$row[21],$row[12],$row[13],"Ding Huo",$row[31],$row[28]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}           
	}
	function importZM($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%ZM%'")->delete();  
		$filename = "../admin/luozuan/new/Error_ZM".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Fluor","Cut","Polish","Symmetry","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$filetype = "ZM";
		$j = 1;
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode(',',$this->autoToutf8($row)); 
				$row[1] = $this->getDiamondsShapeNo($filetype,$row[1]);    
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				if($this->checkData($row[1],$row[2],$row[4],$row[5],$row[6],$row[7],$row[15],$row[16],$row[18],$row[19])){
					$data['goods_name'] = $row[0];
					$data['goods_number'] = 1;
					$data['shape'] = $row[1];
					$data['certificate_type'] = $this->formatDiamondReportType($row[2]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[4],$row[2]);
					$data['weight'] = $this->formatDiamondClarity($row[5]);
					$data['color'] = $this->formatDiamondColor($row[6]);
					$data['clarity'] = $this->formatDiamondClarity($row[7]);
					$data['cut'] = $this->formatDiamondCut($row[8]);
					$data['polish'] = $this->formatDiamondPolish($row[9]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[10]);
					$data['fluor'] = $this->formatDiamondFlour($row[11]);
					$data['dia_table'] = $row[12];
					$data['dia_depth'] = $row[13];
					$data['dia_size'] = $row[14];
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[15]),2);
					$data['dia_discount'] = $row[16];
					$data['location'] = $row[17];
					$data['quxiang'] = $row[3];
					$data['milk'] = $this->formatDiamondMilky($row[18]);
					$data['coffee'] = $this->formatDiamondCoffee($row[19]);
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
					
					if($this->diamondInLuozuan($data['certificate_number']) && $_REQUEST['import_checkSelDiamond']=='on'){ 
						continue;
					}                 
					if($k>=0){
						
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
				    $data2 = array("ZM".$j,$row[1],$row[2],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12],$row[13],$row[15],$row[16],$row[17],$row[18],$row[19]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}      
	}

	function importSH($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%SH%'")->delete();
		$filename = "../admin/luozuan/new/Error_SH".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Fluor","Cut","Polish","Symmetry","Table","Depth","GlobaPrice","Discount","Location","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);
		$tempLines = '';		//存储CSV数据行
		$errLines = '';	//存储导入失败的数据
		$j = 1;
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行）
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据
			//file_put_contents(importLogFile,date('Y-m-d H:i:s')." 读取：$_i 至 $endLine 行，共 $fileLines 行【".round(($_i/$fileLines)*100,2)."%】 \n",FILE_APPEND);
			
			$sql_goods = '';
			foreach($luozuanData as $k=>$row){
				$tempLines = $row;
				$row = explode('"',$this->autoToutf8($row));
				//print_r($row);die;
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				if($this->checkData($row[1],$row[2],$row[4],$row[5],$row[6],$row[7],$row[15],$row[16],'-','-')){
					$data['goods_name'] = $row[0];
					$data['shape'] = $this->getDiamondsShapeNo($row[1]);
					$data['certificate_type'] = $this->formatDiamondReportType($row[2]);
					$data['certificate_number'] = $this->formatDiamondReportNo($row[4],$row[2]);
					$data['weight'] = $this->formatDiamondClarity($row[5]);
					$data['color'] = $this->formatDiamondColor($row[6]);
					$data['clarity'] = $this->formatDiamondClarity($row[7]);
					$data['cut'] = $this->formatDiamondCut($row[8]);
					$data['polish'] = $this->formatDiamondPolish($row[9]);
					$data['symmetry'] = $this->formatDiamondSymmetry($row[10]);
					$data['fluor'] = $this->formatDiamondFlour($row[11]);
					$data['dia_table'] = $row[12];
					$data['dia_depth'] = $row[13];
					$data['dia_size'] = $row[14];
					$data['dia_global_price'] = round($this->formatDiamondGlobalPrice($row[15]),2);
					$data['dia_discount'] = $row[16];
					$data['location'] = "深圳";
					$data['quxiang'] = $row[3];
					$data['milk'] = $this->formatDiamondMilky("-");
					$data['coffee'] = $this->formatDiamondCoffee("-");
					$data['goods_number'] = 1;
					$data['tid'] = 0;
					$data['belongs_id'] = 1;
	//				print_r($data);die;
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}
					if($k>=0){
						//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
						$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
						$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
						$data['price'] = $shop_price; 
						M("goods_luozuan")->data($data)->add();
						$num++; 
					}
					$j++;
				}else{
				    $data2 = array("SH".$j,$row[1],$row[2],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12],$row[13],$row[15],$row[16],$row[17],$row[18],$row[19]); 
				    $this->writeDataIntoCsv($filename,$data2);
				}
			}
		}
	}
	
	// 20151012 
	function importFMR($csvFile,$pagesize=500){
		global $db,$ecs;
		M("goods_luozuan")->where("goods_name like '%FMR%'")->delete();
		$filename = "../admin/luozuan/new/Error_FMR".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);  
		$filetype = "FMR";
		$j = 1;   
		for($_i=9;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行） 
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据   
			$sql_goods = ''; 
			$luozuanDataList = array(); 
			foreach($luozuanData as $k=>&$row){  
				$row = explode('"',$this->autoToutf8($row));   
				$row[3] = $this->getDiamondsShapeNo($filetype,$row[3]); 
				// 检测数据 checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown){
				if($this->checkData($row[3],$row[14],$row[15],$row[4],$row[5],$row[6],$row[7],$row[8],$row[21],$row[23])){
					$temp = array($filetype.$j,$row[3],$row[14],$row[15],$row[4],$row[5],$row[6],$row[11],$row[12],$row[13],$row[20],$row[18],$row[17],$row[16],$row[7],$row[8],"国外","订货",$row[21],$row[23]);
					$data = $this->combineData($filetype,$temp); 
					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){ 
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}      
					//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
					$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
					$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
					$data['price'] = $shop_price;   
					$luozuanDataList[] = $data;
				}else{
				    $data2 = array("FMR".$j,$row[1],$row[9],$row[41],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[18],$row[19],$row[10],$row[12],$row[25],$row[24]); 
				    $this->writeDataIntoCsv($filename,$data2);
				} 
				$j++;
			}
			
			M("goods_luozuan")->addAll($luozuanDataList);                          
			unset($luozuanDataList);
		}                             
		fclose($fp);
	}
	
	function importCOMMON($csvFile,$pagesize=500){
		global $db,$ecs;		
		$filename = "../admin/luozuan/new/Error_COMMON".date("m.j").".csv";
		$fp = fopen($filename,"a"); //打开csv文件，如果不存在则创建  
		$data = array("ID","Shape","Certificate","CertificateNo","Weight","Color","Clarity","Cut","Polish","Symmetry","Fluor","Table","Depth","GlobaPrice","Discount","Milky","Brown");  
	    $this->writeDataIntoCsv($filename,$data);
		$fileLines = $this->countFileLines($csvFile);  
		$filetype = "COMMON";
		//M("goods_luozuan")->where("goods_name like '%COMMON%' and agent_id = ".C('agent_id'))->delete();
		$j = 1;
		set_time_limit(300);  		
		$biaoji_time = time();
		for($_i=2;$_i<$fileLines;$_i+=$pagesize){	//从第二行开始读取数据（第一行为标题行） 			
			$startLine = $_i;
			$endLine = $_i+$pagesize-1;
			$luozuanData = $this->getFileLines($csvFile, $startLine, $endLine);//每次读取指定数量的数据   
			$sql_goods = ''; 
			$luozuanDataList = array(); 
			foreach($luozuanData as $k=>&$row){  
				$row = explode('"',$this->autoToutf8($row)); 	
					
				$row[1] = $this->getDiamondsShapeNo($filetype,$row[1]); 						
				
				//检测数据checkData(     $shape,  $certificateType,$certificateNo,$weight,$color, $clarity, $globalPrice,$discount,$milky,  $brown)					
				if($this->checkData($row[1], $row[2],        $row[3],       $row[4],$row[5], $row[8], $row[16],    $row[17], $row[6], $row[7])){
								//goods_name, shape,     certificate_type,   certificate_number, weight,   color  ,  clarity,  cut,   polish(抛光)  ,  symmetry(对称),fluor荧光   , dia_table直径, dia_depth深度, dia_size尺寸, dia_global_price  ,dia_discount, location,  quxiang, milk,    coffee

					
					$check_certificate_number = D('GoodsLuozuan')->where('agent_id ='.C('agent_id').' and certificate_number like \''.$row[3].'\'')->find();
					
					if(count($check_certificate_number) != 0){
						if($check_certificate_number['belongs_id'] == 0){														
							continue;
						}else{										
							D('GoodsLuozuan')->where('agent_id ='.C('agent_id').'  and certificate_number like \''.$row[3].'\' ')->delete();
						}
					}
					
					$row[19]= str_replace(array("\r\n", "\r", "\n"), "", $row[19]); 
					if( $row[19]!='现货' and $row[19]!='订货'){
						$row[19] = '订货';
					}
					
					$temp = array($filetype.'_'.$row[0],  $row[1],   $row[2],            $row[3],            $row[4],  $row[5],  $row[8],  $row[9],  $row[10],       $row[11],       $row[13],      $row[15],     $row[14],      $row[12],      $row[16],         strip_tags($row[17]),       $row[18],    $row[19],  $row[6], $row[7]);

					$data = $this->combineData($filetype,$temp); 

					if($this->diamondInLuozuan($data['certificate_number']) and $_REQUEST['import_checkSelDiamond']=='on'){ 
						$errLines .= $tempLines;	//将未导入的数据存入错误记录
						continue;
					}      
					//$data['dia_global_price'] = $lz->getDiamondPrice($data['shape'],$data['weight'],$data['color'],$data['clarity']);//获取钻石的'dia_global_price',参数：'shape'/'weight'/'color'/'clarity'（若是不需要自动计算'dia_global_price'则屏蔽此行）
					$market_price = $data['dia_global_price']*$data['weight']*C("dollar_huilv");	//市场价 = 国际克拉价*实际'weight'*美元汇率
					$shop_price = round($market_price*$data['dia_discount']/100,2);	//本店价 = 市场价*'dia_discount'/100
					$data['price'] = $shop_price;
					$data['agent_id'] = C('agent_id') ;
					$data['belongs_id'] = 1;//common类型的商品都是自己本站的商品  
					$data['goods_name'] = $filetype.'_'.$row[0];
					$data['location']   = trim($row[18]);

					if($data['color'] == 'FANCY'){
		            	list($data['color'], $data['intensity']) = $this->getColorDiamond($data['certificate_number'], trim(strtoupper($data['certificate_type']))  );
		            	$data['luozuan_type']       = 1;         //默认为白钻  钻石类型   
		            }else{
		            	$data['luozuan_type']       = 0;         //默认为白钻  钻石类型   
		            	$data['intensity']          = '';			            
					}
					
					$data['supply_id'] = 0;  
		            $data['supply_gid'] = 0;  
		            $data['zm_gid'] = 0;  
		            $data['agent_id'] = C('agent_id');                         
		 			$data['biaoji_time'] = $biaoji_time;
					
		 			$this->addLuozuanBatchData($data);

				}else{
				    // $data2 = array("COMMON".$j,$filetype.'_'.$row[0],  $row[1],   $row[2],            $row[3],            $row[4],  $row[5],  $row[8],  $row[9],  $row[10],       $row[11],       $row[13],      $row[15],     $row[14],      $row[12],      $row[16],          $row[17],       $row[18],    "订货",  $row[6], $row[7], 'agent_id:'.C('agent_id'),); 
				    // $this->writeDataIntoCsv($filename,$data2);
				} 
				$j++;
			}
			
			//M("goods_luozuan")->addAll($luozuanDataList);

			//unset($luozuanDataList);
		}  
		//$this->delLuozuanBatchData($filetype, $biaoji_time);
		                  
		fclose($fp);
	}	

	// 内部的读文件方法
	function _fgetcsv(& $handle, $length = null, $d = ',', $e = '"') {
		$d = preg_quote ( $d );
		$e = preg_quote ( $e );
		$_line = "";
		$eof = false;
		while ( $eof != true ) {
			$_line .= (empty ( $length ) ? fgets ( $handle ) : fgets ( $handle, $length ));
			$itemcnt = preg_match_all ( '/' . $e . '/', $_line, $dummy );
			if ($itemcnt % 2 == 0)$eof = true;
		}
		$_csv_line = preg_replace ( '/(?: |[ ])?$/', $d, trim ( $_line ) );
		$_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
		preg_match_all ( $_csv_pattern, $_csv_line, $_csv_matches );
		$_csv_data = $_csv_matches [1];
		for($_csv_i = 0; $_csv_i < count ( $_csv_data ); $_csv_i ++) {
			$_csv_data [$_csv_i] = preg_replace ( '/^' . $e . '(.*)' . $e . '$/s', '$1', $_csv_data [$_csv_i] );
			$_csv_data [$_csv_i] = str_replace ( $e . $e, $e, $_csv_data [$_csv_i] );
		}
		return empty ( $_line ) ? false : $_csv_data;
	}
	
	function checkData($shape,$certificateType,$certificateNo,$weight,$color,$clarity,$globalPrice,$discount,$milky,$brown,$location="订货",$other = ''){
	
		//if(checkData($row[1],$row[9],$row[37],$row[2],$row[3],$row[4],$row[10],$row[12],$row[24],$row[25])){
		//print_r($certificateType);die;
		/*
		if($certificateType == "IGI"){
			print_r($shape."_".$certificateNo."_".$weight."_".$color."_".$clarity."_".$globalPrice."_".$discount."_".$milky."_".$brown);die;
		}
		*/
		$shape = strtoupper(trim($shape));
		$other = trim($other);
		$certificateType = strtoupper(trim($certificateType));
		$color = strtoupper(trim($color));
		$clarity = strtoupper(trim($clarity));
		$milky = strtoupper(trim($milky));
		$brown = strtoupper(trim($brown)); 
		$certificateTypeArray = array("GIA","IGI","HRD","散货","国首","国检");
		$colorArray = array("D","E","F","G","H","I","D-E","I-J","F-G","J","K","L","M","N","D+","D-","E+","E-","F+","F-","G+","G-","H+","H-","I+","I-","J+","J-","K+","K-","L+","L-","M+","M-","N+","N-", 'FANCY');
		$clarityArray = array("LC","FL","IF","VVS1","VVS","VS","SI","VVS2","VS1","VS2","SI1","SI2","SI3","I1","I2","I3","VVS1+","VVS2+","VS1+","VS2+","SI1+","SI2+","VVS1-","VVS2-","VS1-","VS2-","SI1-","SI2-");
		$milkyArray = array("","TOTALLY MILKY","EX","VG","M0","FR","GD","ML1","NO MILKY","-","N","MINOR MILKY","SLIGHTLY MILKY OR HAZY","ML1","M1","MEDIUM MILKY","ML2",
						"M2","GENUINE MILKY","ML3","M3","无奶","M4","FR","GD","ML1","VG","ML-01","ML-1","ML-2","ML-3","GD","ML0.5","ML1.5","ML2.5");
		
		$brownArray = array("","YL","SLIGHTLY BROWN  -  NOT PROBLEMATIC","GRN","GRY","WH","NO BROWN","NO BROWN NO MILKY","WHITE","-","N","LIGHT BROWN","VB","BR1","BRN1","MEDIUM BROWN","LB","BR2","BRN2",
			"GENUINE BROWN","DB","BR3","BRN3","BROWN","MIXTING","MT","MIXTINGE","MIX TINGE1","MIX TINGE2","MIX TINGE3","NV","无咖","BRN1, CULET OPEN","BRN2, CULET OPEN","BRN4","OWH"
			,"FRBR","BR","FTYLB","YLB","BRYL","FTGR","GR","BGY","FTGNY","BYL","FP","FTBR","GNGR","GNY","GRYLG","YL","YLGN","LBR","PINK","YBR","BROWN","FYL");
		/* 2015.7.16 暂时不屏蔽形状功能
		if(!in_array($shape,$shapeArray)){
			return false;
		}
		*/
		if($shape == "OTHER"){
			return false;
		}
		if(!in_array($certificateType,$certificateTypeArray)){
			return false;
		}
		if(!in_array($color,$colorArray)){
			return false;
		}
		if(!in_array($clarity,$clarityArray)){
			return false;
		}
		if(!in_array($milky,$milkyArray)){
		//	return false;
		}
		if(!in_array($brown,$brownArray)){
		//	return false;
		}
		$globalPrice = (float)(str_replace(",","",$globalPrice));
		if($globalPrice <= 500){
			return false;
		}
		if($discount <= -80){
			return false;
		}
		/*
		if($certificateType != "散货" && $certificateNo <=100){
			return false;
		}
		*/
		if($location == "代销"){
			return false;
		}
		if($other == 'BROWN NO MILKY' or $other == 'MixTinge' or $other == 'NO BROWN MILKY' or $other == 'B' or $other == 'Bussiness Process' or $other == 'Selected' or $other == 'Status' or $other == 'Total' or $other == 'Web Hold'){
			return false;
		}
		return true;
	}
		
	function autoToutf8($data,$to='utf-8'){ //字符串转换编码
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

	function formatDiamondGlobalPrice($str){
		return str_replace(',','',$str); 
	}
	
	
	/*获取钻石'shape'对应的'goods_name' by cool*/
	function getDiamondsShapeNo($filetype,$shape){
		$shape = trim(strtoupper($shape));
		$shapeNo = "other";

		if($shape == 'ROUND' 	or $shape == '圆形' or $shape == 'ROUNDS') $shapeNo = "ROUND";   
		if($shape == 'OVAL' 	or $shape == '椭圆') $shapeNo = "OVAL";                      
		if($shape == 'MARQUISE' or $shape == '马眼') $shapeNo = "MARQUISE";                 
		if($shape == 'HEART' 	or $shape == '心形') $shapeNo = "HEART";                     
		if($shape == 'PEAR' 	or $shape == 'PEARS' 	or $shape == '水滴') $shapeNo = "PEAR";                      
		if($shape == 'PRINCESS' or $shape == '方形') $shapeNo = "PRINCESS";                   
		if($shape == 'EMERALD'  or $shape == 'EMERALD CRISS' or $shape == '祖母绿') $shapeNo = "EMERALD";                
		if($shape == 'CUSHION'  or $shape == '枕形') $shapeNo = "CUSHION";
		if($shape == 'RADIANT'  or $shape == '蕾蒂恩') $shapeNo = "RADIANT";
	
		if($filetype == "FMD"){
			if($shape == 'RB' or $shape == "PB") $shapeNo = "ROUND";   //圆形
			if($shape == 'OL') $shapeNo = "OVAL";                      //椭圆
			if($shape == 'MQ') $shapeNo = "MARQUISE";                  //马眼
			if($shape == 'HT') $shapeNo = "HEART";                     //心形
			if($shape == 'PE') $shapeNo = "PEAR";                      //水滴
			if($shape == 'PR') $shapeNo = "PRINCESS";                   //方形
			if($shape == 'EM') $shapeNo = "EMERALD";                   //祖母绿
			if($shape == 'CU') $shapeNo = "CUSHION";                   //枕形
			if($shape == 'RD') $shapeNo = "RADIANT";                   //蕾蒂恩 
		}elseif($filetype == 'FME'){                                
			         
		}elseif($filetype == "FMH"){
			
		}elseif($filetype == "FMI"){
			
		}elseif($filetype == "FMJ"){		            
			if($shape == 'CUSHION MOD') $shapeNo = "CUSHION";
			if($shape == 'SQ RADIANT') $shapeNo = "RADIANT";     
		}elseif($filetype == "FML"){		           
			if($shape == 'EMERALD' or $shape == 'EMERALD CRISS') $shapeNo = "EMERALD";                
		}
		elseif($filetype == "FMM"){
			if($shape == 'SQUARE EMERALD') $shapeNo = "EMERALD";                
		}
		elseif($filetype == "FMN"){
		}
		elseif($filetype == "FMP"){	                
		}
		elseif($filetype == "FMQ"){		
			if($shape == 'OV') $shapeNo = "OVAL";                      
			if($shape == 'MQ') $shapeNo = "MARQUISE";                 
			if($shape == 'HT') $shapeNo = "HEART";                     
			if($shape == 'PEAR' or $shape == 'PR') $shapeNo = "PEAR";                      
			if($shape == 'PC') $shapeNo = "PRINCESS";                   
			if($shape == 'EM') $shapeNo = "EMERALD";                
			if($shape == 'CS' or $shape == 'CM') $shapeNo = "CUSHION";     
			if($shape == 'RN') $shapeNo = "RADIANT";     
		}
		elseif($filetype == "ZM"){		         
		}
		elseif($filetype == "SH"){				            
		}
		elseif($filetype == "FMR"){
			if($shape == 'RD') $shapeNo = "ROUND";   
			if($shape == 'OV') $shapeNo = "OVAL";                      
			if($shape == 'MQ') $shapeNo = "MARQUISE";                 
			if($shape == 'HE') $shapeNo = "HEART";                     
			if($shape == 'PE') $shapeNo = "PEAR";                      
			if($shape == 'PR') $shapeNo = "PRINCESS";                   
			if($shape == 'EM' or $shape == 'SQE') $shapeNo = "EMERALD";                
			if($shape == 'CMB' or $shape == 'CMB-N') $shapeNo = "CUSHION";    
			if($shape == 'RA' or $shape == 'SQR') $shapeNo = "RADIANT";  
		}
		elseif($filetype == "COMMON"){				                 
			if($shape == 'RA') $shapeNo = "RADIANT";       
		}
		
		
		/*
		
		$shape = trim(strtoupper($shape));	//转换为小写
		$shapeNo=0;	//默认（标识非常规异形钻）                                                                
		if($shape=='圆形' or $shape=='RB' or $shape =='RBC' or $shape=='RD' or $shape=='ROUND' or $shape=='ROUNDS' or $shape=='RO'){$shapeNo="ROUND";}
		if($shape=='椭圆' or $shape=='OL' or $shape=='OVAL' or $shape=='OVALS'){$shapeNo="OVAL";}
		if($shape=='马眼' or $shape=='MQ' or $shape=='MARQUIES' or $shape == 'M' or $shape=='MARQUIESS'){$shapeNo="MARQUISE";}
		if($shape=='心形' or $shape=='HT' or $shape=='HEART' or $shape=='HEARTS'){$shapeNo="HEART";}
		if($shape=='水滴' or $shape=='PE' or $shape=='PEAR' or $shape=='PEARS'){$shapeNo="PEAR";}
		if($shape=='方形' or $shape=='公主方' or $shape=='PR' or $shape=='PRINCESS'){$shapeNo="PRINCESS";}
		if($shape=='祖母绿' or $shape=='em' or $shape=='ASH' or $shape == 'E' or $shape =='EM' or $shape=='EMERALD' or $shape=='EMERALDS' or $shape =='SQUARE EMERALD' or $shape == 'EMERALD CRISS'){$shapeNo="EMERALD";}
		if($shape=='枕形' or $shape=='上丁方' or $shape=='上丁方形' or $shape=='ASSCHER' or $shape=='ASSCHERS' or shape == 'CUSHION BRILLIANT' or $shape=='垫形' or $shape=='CU' or $shape=='CUSHION' or $shape=='CUSHIONS'){$shapeNo="CUSHION";}
		if($shape=='雷迪恩' or $shape=='雷蒂恩' or $shape=='雷地恩' or $shape ='RN' or $shape=='REDIANT' or $shape == 'RADIANT'){$shapeNo="RADIANT";}
		if($shape=='梯方' or $shape=='RECTANGLE' or $shape=='长方形' or $shape =='ST BUG'){$shapeNo="BAGUETTE";}
		if($shape=='三角形' or $shape=='TRILLIANT' or $shape == 'TRIANGLE' or $shape == 'TRIANGULAR'){$shapeNo="TRILLIANT";} 
		*/
		return $shapeNo;
	}

	/*
	 * 'certificate_type'类型过滤
	 */
	function formatDiamondReportType($str){
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
		return $str;
	}

	/*'certificate_number'过滤
	 * */
	function formatDiamondReportNo($reportNo,$reportType){
		switch(strtoupper($reportType)){
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
		return $reportNo;
	}

	/*'color'字符串处理
	 * 函数原理：
	 * 		检测'color'为fancy并且'color'备注参数字符串中是否存在全角逗号，若存在全角逗号则以全角逗号分割字符串为数组，提取数组第一个值，对其取每个单词的首写字母
	 */
	function formatDiamondColor($str='',$strInfo=''){
		if(strtoupper($str)=='FANCY' and strpos($strInfo,'，')){
			$strTmp = '';
			$strArr = explode('，',$strInfo);
			$strArr = explode(' ',$strArr[0]);
			foreach($strArr as $k=>$v){
				if(!empty($v)){
					$strTmp .= substr($v,0,1);
				}
			}
			$str = $strTmp;
		}
		return trim($str);
	}
	/*'certificate_type'字符串处理 
	 * */
	function formatDiamondCert($str=''){
		if(stristr($str,'GIA')){
			$str = 'GIA';
		}elseif(stristr($str,'IGI')){
			$str = 'IGI';
		}elseif(stristr($str,'HRD')){
			$str = 'HRD';
		}elseif(stristr($str,'NGTC')){
			$str = 'NGTC';
		}
		return trim(strtoupper($str));
	}

	/**'clarity'字符串处理
	 * 标准值：IF,VVS1,VVS2,VS1,VS2,SI1,SI2,P1,P2,P3
	 * 可能值：FL,I1,I2,IF,LC,SI1,SI1+,SI1-,SI2,SI2+,SI3,VS1,VS1+,VS2,VS2+,VVS1,VVS1+,VVS2,VVS2+,VVS2-
	 * 处理后可能值：FL,I1,I2,IF,LC,SI1,SI2,SI3,VS1,VS2,VVS1,VVS2
	 * 更新日期：
	 */
	function formatDiamondClarity($str=''){
		if(!empty($str)){
			$str = strtoupper($str);
			//$str = str_ireplace(array('+','-'),'',$str);
		}
		return trim(strtoupper($str));
	}

	/**
	 * 荧光字符串处理
	 * 标准值：N,F,M,S,VS
	 * 可能值：F,FNT,M,MED,N,S,SL,ST,STG,V-STG,VS,VSL
	 * 更新日期：
	 */
	function formatDiamondFlour($str=''){
		$str = strtoupper(trim($str));
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
		return trim(strtoupper($str));
	}

	/**
	 * 'cut'字符串处理
	 * 标准值：I,EX,VG,GD,F
	 * 可能值：EX,F,FG,FR,GD,NONE,PR,VG
	 * 更新日期：
	 */
	function formatDiamondCut($str=''){
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
		return trim(strtoupper($str));
	}

	/**
	 * 'polish'字符串处理
	 * 标准值：I,EX,VG,GD,F
	 * 可能值：EX,G,GD,VG
	 * 更新日期：
	 */
	function formatDiamondPolish($str=''){
		if(!empty($str)){
			$str = strtoupper($str);
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
		return trim(strtoupper($str));
	}

	/**
	 * 'symmetry'字符串处理
	 * 标准值：I,EX,VG,GD,F
	 * 可能值：EX,F,FR,G,GD,VG
	 * 更新日期：
	 */
	function formatDiamondSymmetry($str=''){
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
		return trim(strtoupper($str));
	}

	function formatDiamondDiscount($type,$discount){
		$companyDiscount = 0;
		switch($type){
			case "FMD":
				$discount = 100 - $discount + 4;
			break;
			case "FME":
				$discount = 100 + $discount + 4;
			break;
			case "FMH":
				$discount = 100 - round($discount,2)-0.5;
			break;
			case "FMI":
				$discount = 100 + round($discount,2) + 4;
			break;
			case "FMJ":
				$discount = 100 + round($discount,2) + 2;
			break;
			case "FML":
				$discount = round(100*$discount,2)+4;
			break;
			case "FMM":
				$discount = 100+$discount;
			break;	
			case "FMN":
				$discount = 100*$discount+4;
			break;	
			case "FMQ":
			$discount = 100+$discount+3.5;
			break;
			case "FMP":
				$discount = 100+$discount+4;
			break;	
			case "FMR":
				$discount = 100+$discount+1;
			break;
			case "COMMON":
				$discount = 100+$discount;
			break;				
		}
		return $discount;
	}

	 
	function formatDiamondSize($length,$width,$height){
		return $length."*".$width."*".$height;
	}
	function formatDiamondMilky($str){
		$str = strtoupper(trim($str));
		if($str == '' or $str== 'M0' or $str == 'NO MILKY' or $str == '-' or $str == 'N' or $str == 'EX' or $str == 'VG' or $str=='NONE'){
			return "无奶";
		}else if($str == 'MINOR MILKY' or $str == 'ML1' or $str == 'M1' or $str == 'ML-01' or $str == 'ML-1' or $str=='ML1' or $str=='ML0.5' or $str=='GD'){
			return "浅奶";
		}else if($str == 'MEDIUM MILKY' or $str == 'ML2' or $str == 'M2' or $str == 'ML-2' or $str=='ML1.5' or $str=='ML2'){
			return "中奶";
		}else if($str == 'GENUINE MILKY' or $str == 'ML3' or $str == 'M3' or $str == 'M4' or $str == 'TOTALLY MILKY' or $str == 'ML-3' or $str=='ML2.5' or $str=='ML3'){
			return "深奶";
		}else if($str == 'SLIGHTLY MILKY OR HAZY'){
			return "轻奶";
		}else if($str == 'GD'){
			return "带奶";
		}else{
			return $str;
		}
		
	}
	function formatDiamondCoffee($str){
		$str = strtoupper(trim($str));
		if($str == '' or $str == 'WH' or $str == 'NO BROWN' or $str == 'WHITE' or $str == '-' or $str == 'N' or $str == 'OWH'){
			return "无咖";
		}else if($str == 'LIGHT BROWN' or $str == 'VB' or $str == 'BR1' or $str == 'BRN1' or $str == 'BRN1, CULET OPEN'  or $str == 'LBR'){
			return "浅咖";
		}else if($str == 'MEDIUM BROWN' or $str == 'LB' or $str == 'BR2' or $str == 'BRN2' or $str == 'BRN2, CULET OPEN'){
			return "中咖";
		}else if($str == 'GENUINE BROWN' or $str == 'DB' or $str == 'BR3' or $str == 'BRN3'){
			return "深咖";
		}else if($str == 'BROWN' or $str == "GRN"  or $str == 'BR'){
			return "带咖";
		}else if($str == 'MIXTING' or $str == 'MT' or $str == 'MIXTINGE' or $str == 'YL' or $str == 'YLB' or $str == 'BYL' or $str == 'GRYLG'){
			return "咖黄";
		}else if($str == 'MIX TINGE1' or $str == 'FTYLB'){
			return "浅咖黄";
		}else if($str == 'MIX TINGE2'){
			return "中咖黄";
		}else if($str == 'MIX TINGE3' or $str == 'BRYL'){
			return "深咖黄";
		}else if($str == 'NV' or $str == 'SLIGHTLY BROWN  -  NOT PROBLEMATIC' or $str == 'FRBR'  or $str == 'FTBR' ){
			return "轻咖";
		}else if($str == 'BRN4'){
			return "重咖";
		}else if($str == 'GRY'){
			return "带灰";
		}else if($str == 'FTGR'){
			return "轻绿";
		}else if($str == 'GR'){
			return "绿";
		}else if($str == 'BGY'){
			return "绿咖";
		}else if($str == 'FTGNY'){
			return "轻黄绿";
		}else if($str == 'FP'){
			return "浅粉";
		}else if($str == 'GNGR'){
			return "绿灰";
		}else if($str == 'GNY'){
			return "绿黄";
		}else if($str == 'YLGN'){
			return "黄绿";
		}else if($str == 'PINK'){
			return "带粉";
		}else if($str == 'YBR'){
			return "带黄咖";
		}else if($str == 'FYL'){
			return "带黄";
		}else{
			return $str;
		}
	}

	function formatDiamondShape($str){
		if($str == "RB" or $str == "RBC"){
			return "001";
		}elseif($str == "OL"){
			return "002";
		}elseif($str == "MQ"){
			return "003";
		}elseif($str == "HT"){
			return "004";
		}elseif($str == "PE"){
			return "005";
		}elseif($str == "PR"){
			return "006";
		}elseif($str == "ME"){
			return "007";
		}elseif($str == "CU" or $str == "CUSHION"){
			return "008";
		}elseif($str == "ASH"){
			return "009";
		}elseif($str == ""){
			return "010";
		}else{
			return "001";
		}
	}

	function formatDiamondLocation($str){
		$str = trim(strtoupper($str));
		if($str == "HK" or $str == "香港" or $str == "HONGKONG"){
			return "香港";
		}else if($str == "SH"){
			return "上海";}
		else if($str == "INDIA" or $str == "印度" or $str == 'IN'){
			return "印度";
		}else if($str == "深圳"){
			return "深圳";
		}else{
			return "国外";
		}
	}
	
	function countFileLines($file_path,$maxLineSize=8192,$ending="\n"){
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
	
	
	
	
	/*
	 * 根据钻石'certificate_type''goods_name'判断钻石是否已经卖出
	 * 参数：
	 * 		$reportNo			string		钻石'certificate_type''goods_name'
	 * 返回值（布尔值）：
	 * 		true		表示存在
	 * 		false		不存在
	 */

	function diamondInLuozuan($reportNo=''){
		$join = " LEFT JOIN ".C('DB_PREFIX')."order O ON O.order_id=OG.order_id ";
		$count = M('order_goods')->alias("OG")->join($join)->where("OG.agent_id = '".C('agent_id')."' and O.order_status >0 AND OG.certificate_no='$reportNo' AND OG.goods_number>0")->count();;
		if($count>0 && !empty($reportNo)){
			return true;
		}else{
			return false;
		}
	}

	/////////获取指定网页的彩钻色度，颜色信息；
	public function getColorDiamond($certificate_number, $certificate_type){
		$curl = curl_init();
		$data_url = 'http://www.checkdiamond.com/zhengshu-web/zhengshu/openContent.shtml?language=zh-cn&type='.$certificate_type.'&reportno='.$certificate_number;
								
		curl_setopt($curl, CURLOPT_URL, $data_url);
		curl_setopt($curl, CURLOPT_HEADER, 0);//设定是否显示头信息
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//设定是否输出页面内容
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");          
		$data1 = curl_exec($curl); 

		
		//preg_match('/<span id=\"grade\">(.*?)<\/span>/',$data1,$result);
		preg_match('/<strong id=\"grade\" class=\"span_color\">(.*?)<\/strong>/',$data1,$result);

		$data_color_intensity = explode(' ', $result[1]);
		$data = array();
		if(count($data_color_intensity) == 3){
			
			if(strtoupper($data_color_intensity[1])== 'LIGHT' or strtoupper($data_color_intensity[1])== 'INTENSE' or strtoupper($data_color_intensity[1])== 'DEEP' or strtoupper($data_color_intensity[1])== 'DARK' or strtoupper($data_color_intensity[1])== 'VIVID'){
				$data[0]  = strtoupper($data_color_intensity[2]);//color
				$data[1]  = strtoupper($data_color_intensity[0]).' '.strtoupper($data_color_intensity[1]);//Intensity
			}else{
				$data[0]  = strtoupper($data_color_intensity[1]) .' '.strtoupper($data_color_intensity[2]);//color
				$data[1]  = strtoupper($data_color_intensity[0]);//Intensity				
			}

		}elseif(count($data_color_intensity) == 2){
			
			$data[0]  = strtoupper($data_color_intensity[1]);//color
			$data[1]  = strtoupper($data_color_intensity[0]);//Intensity
		}else{
			return '';
		}

		// if($data[0] == 'YELLOW-GREEN'){
		// 	$data[0] = 'YELLOW';
		// }

		if($data[1] == 'W-X' or $data[1] == 'Y-Z'){
			return '';
		}
		curl_close($curl);
		return $data;				            
	}

	//批量上传裸钻数据时，进行update or insert
	public function addLuozuanBatchData($data){
		if(empty($data['certificate_type']) or empty($data['certificate_number']) or ($data['dia_global_price']<500)){			
			//exit('裸钻分类,裸钻类型,证书号不能为空');
			return '';
		}		
		$where['certificate_type']	   = array('eq',   $data['certificate_type']);		
		$where['certificate_number']   = array('eq',   $data['certificate_number']);
		$where['agent_id']   = array('eq',   $data['agent_id']);
		//$where['goods_name']           = array('like', $type.'%');	
					
		if(empty($data['supply_gid'])){
			$data['supply_gid'] = null;
		}
		//供应宝加上后，添加这个条件	
        //$where['supply_id']   		   = array('gt',   0);	
        $gInfo = M('goods_luozuan')->where($where)->field('gid, dia_global_price, dia_discount')->find();
        
        
        //if($gInfo and ($gInfo['dia_discount']!=$data['dia_discount'] or $gInfo['dia_global_price']!=$data['dia_global_price'])){

        $gid = M('goods_luozuan')->where($where)->getField('gid');
        if($gid){
        	$where['gid'] = $gid;

            M('goods_luozuan')->where($where)->save($data);
        }else{
        	
            M('goods_luozuan')->data($data)->add();
        }
       
      
	}
	
}