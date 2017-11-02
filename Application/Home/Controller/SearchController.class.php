<?php
/**
 * 前端文章显示类
 */
namespace Home\Controller;
class SearchController extends NewHomeController{
	
	public $reportRes;  //证书缓存（行记录）
    public $zs_id;  //证书编号（字符串）
  	public $zs_weight;  //钻石重量（数值）
  	public $zs_type;
  	
    public function _before_index(){
    	$this->seo_title   = "证书查询";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active      = "Search";
    }
    public function _before_detail(){
    	$this->seo_title   = "证书详情";
    	$this->seo_keyword = "裸钻查询";
    	$this->seo_content = "裸钻查询";
    	$this->active      = "Search";
    }
	
	
	public function index(){
 
		$this->display();
	}
	
	public function detail(){
		if(IS_AJAX){
			$this->zs_id = I('zs_id');
			$this->zs_weight = I('zs_weight','');
			$this->zs_type = I('zs_type');
			$where = " zs_id = '".$this->zs_id."'";
			$where .= " AND zs_weight = '".$this->zs_weight."'";
			$res = M('report')->where($where)->find(); 
			define('cacheDir','./zs/data.cache/');
			$cacheDir = './zs/data.cache/';
			$this->assign('zs_id',$this->zs_id);
			if(empty($this->zs_id)){			$this->redirect("index");		}
			// 引用 think\Report.class.php
			$Report = new \Think\Report(); 
			$Report->report($this->zs_id,$this->zs_weight,$this->zs_type);
			$zs_data = $Report->getReportData();  
			if($zs_data){	//若返回数据为数组则输出数据，否则输出错误信息
				$result['status'] = 1;					$result['data'] = $zs_data; 
			}else{
				$result['status'] = 0;					$result['data'] = null;	
			}
			$this->ajaxReturn($result);
		}else{
			$this->display('detail');
		}
	}
	
	/***** 从GIA官网下载证书 */
	public function giaReportPDF(){ 
		$reportNo = I('reportNumber');
		if(empty($reportNo)){
			$url ='';
		}else{
			$url = 'https://www.gia.edu/report-check?reportno='.$reportNo;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($cu, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
			curl_setopt($cu, CURLOPT_REFERER, "http://www.baidu.com/");			
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            $doc = curl_exec($ch);	
			curl_close($ch);
			preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
			if(strlen($matchs[1]) == 32){
				$url = 'https://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber='.$reportNo.'&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber='.$matchs[1];
				$Report = new \Think\Report();
				$Report->downloadGIAPDF($url,$reportNo);
			}else{
				$url ='';
			}
		}
		$this->ajaxReturn($url);
	}
	
	//* 下载证书 * /
	public function download(){
		define('cacheDir','./zs/data.cache/',false);		/*缓存目录*/
		$reportNumber = I('reportNumber');
		if(!empty($reportNumber)){
			$file_name = cacheDir.preg_replace('/[^a-zA-Z0-9-]/', '',$reportNumber).".pdf";
			//print_r($file_name);die;
			$mime = 'application/force-download';
			header('Pragma: public');       // required
			header('Expires: 0');           // no cache
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private',false);
			header('Content-Type: '.$mime);
			header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
			header('Content-Transfer-Encoding: binary');
			header('Connection: close');
			readfile($file_name);           // push it out
			exit();
		}
		
		$this->display();
	}
	
	/**   证书预览 */ 
	
	public function onlineView(){
		$reportNumber = I('reportNumber');
		preg_match('/([0-9]+)/i',I('reportNumber'),$match);
		$reportNumber = $match[1];
		define('cacheDir','./zs/data.cache/');
		if(file_exists(cacheDir.$reportNumber.'.pdf.png')){	//若存在PNG图片缓存则输出图片
			$img = cacheDir.$reportNumber.'.pdf.png';
			$this->reportNumber = $reportNumber;
			$this->img = $img;
			$this->display(); 
		}elseif(file_exists(cacheDir.$reportNumber.'.pdf')){	//非IE6用户则优先输出PDF文件
			header('Content-Type: application/pdf');
			echo file_get_contents(cacheDir.$reportNumber.'.pdf');
			die;
		}else{
			die("<p style='line-height:30px;text-align:center;'><br />Sorry,暂时还没有此证书的最新缓存,请先下载该证书，再行预览,若有其他问题,请联系客服,感谢您的来访，再见！</p>");
		}
		
	}
	
	// 添加证书查询日子
	function addLog($data){	// 数据表：lzwws_report_log 列名：zs_id	zs_type	zs_weight	ip time
		$ReportLog = M('report_log');
		if($ReportLog->where("zs_id='".$data['zs_id']."'")->find()){
			return M('report_log')->where("zs_id='".$data['zs_id']."'")->setInc('total',1);
		}else{
			return M('report_log')->data($data)->add();
		}
	}

	// 获取IP
	function getIP(){
		global $ip;
		if (getenv("HTTP_CLIENT_IP"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if(getenv("HTTP_X_FORWARDED_FOR"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if(getenv("REMOTE_ADDR"))
			$ip = getenv("REMOTE_ADDR");
		else $ip = "Unknow";
		return $ip;
	}
	
	//头部导航的查询货品
	public function goodsList(){
		$keyword = trim(I('keyword'));
		$p       = $_POST['p'] = I('p','1');
		$n       = I('n','8');
		$keyword = Search_Filter_var($keyword);
		if(!empty($keyword)){
			$data = D("GoodsLuozuan")->where("(certificate_number like '%".$keyword."%' OR goods_name like '%".$keyword."%')") -> limit(1) -> select();
			if($data){
				if($data[0]['luozuan_type'] == 1){
					$this -> redirect("/Home/Goods/goodsDiyColor?goods_sn=$keyword");
				}else{
					$this -> redirect("/Home/Goods/goodsDiy?goods_sn=$keyword");
				}
			}
			$M                   = D('Common/Goods');
			$where['goods_name'] = array('like', "%$keyword%");
			$where['goods_sn']   = array('like', "%$keyword%");
			$where['_logic']     = 'or';
			$M                  -> sql['where']['_complex']    = $where;
			$M                  -> sql['where']['price_model'] = "0";
			$M      -> set_where( 'zm_goods.sell_status' , '1' );             //判断上架
       		$M      -> set_where( 'zm_goods.shop_agent_id' , C('agent_id') ); //判断自己的货上架
			$count               = $M -> get_count();
			$M                  -> _limit($p,$n);
			$page                = new \Think\Page($count,$n);
			$products            = $M -> getList();
			if($products){
				$products        = $M -> getProductListAfterAddPoint($products,$_SESSION['web']['uid']);
			}
			
			$this->count    = $count;
			$this->page     = $page -> show();
			$this->products = $products;
			$this->keyword  = $keyword;
			$this->catShow  = false;
			$this->display();
		}else{
			$this->redirect("/Home/Index");
		}
	}
	
			
	/**
	*	根据id导出裸钻数据
	*	zhy		find404@foxmail.com
	*	2016年11月25日 10:45:02
	*/
	public function expertcertificatedata(){
			$ids       		= I('etd_id');
			$isColor        = I('isColor');					//1:B2C白钻模版  2:B2C彩钻模版 3:B2B白钻模版  4:B2B模版彩钻	
			if($ids){
				$dataList      = array();
				$s_dataList    = array();
				if($isColor=='2' || $isColor=='4'){
					$sglM 	   = new \Common\Model\GoodsLuozuanModel(1);
				}else{
					$sglM          =  D('GoodsLuozuan');
				}
				$where['gid']  = array('in',$ids);
				$data          = $sglM -> getLuozuanList($where,"weight ASC",null,null,C('agent_id'));
				$s_dataList    = getGoodsListPrice($data['list'],$_SESSION['web']['uid'],'luozuan');
				foreach ($s_dataList as $key=>$v){
							$dataList[$key]['location']=$v['location'];
						if($isColor=='1' ||$isColor=='2' ){
							$dataList[$key]['shape']= $v['shape_name'];																//形状
						}else{
							$dataList[$key]['shape']=$v['shape'];																
						} 	

						$dataList[$key]['certificate_number']=$v['certificate_type'].' '.$v['certificate_number'];					//证书
						$dataList[$key]['goods_name']=$v['goods_name'];																//编号
						$dataList[$key]['weight']=$v['weight'];																		//钻重
						$dataList[$key]['color']=$v['color'];																		//颜色
						
						if($isColor=='1' || $isColor=='3'){
							$dataList[$key]['clarity']=$v['clarity'];																//净度
							$dataList[$key]['cut']=$v['cut'];																		//切工
						}else{
							$dataList[$key]['clarity']=$v['clarity'];	
							$dataList[$key]['cut']=$v['intensity'];	
						}
						
						$dataList[$key]['polish']=$v['polish'];																		//抛光
						$dataList[$key]['symmetry']=$v['symmetry'];																	//对称
						$dataList[$key]['fluor']=$v['fluor'];																		//荧光
						$dataList[$key]['dia_depth']=$v['dia_depth'];																//全深比
						$dataList[$key]['dia_table']=$v['dia_table'];																//台宽比
						$dataList[$key]['cur_price']=$v['cur_price'];																//每卡单价
						$dataList[$key]['dia_global_price']=$v['dia_global_price'];													//国际单价						
						$dataList[$key]['dia_discount_all']=$v['dia_discount_all'];													//折扣
						$dataList[$key]['price']=floatval($v['price']);																//单粒价格
				}
				unset($s_dataList);
				unset($data);
						set_time_limit(0);
						ignore_user_abort(true);
						ini_set('memory_limit','1024M');
						import("Org.Util.PHPExcel");
						$objPHPExcel=new \PHPExcel();
						$PHPReader  =new \PHPExcel_Reader_Excel5();
						$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
							->setLastModifiedBy("Maarten Balliauw")
							->setTitle("Office 2007 XLSX Test Document")
							->setSubject("Office 2007 XLSX Test Document")
							->setDescription("Document for Office 2007 XLSX, generated using PHP classes.")
							->setKeywords("office 2007 openxml php")
							->setCategory("Test result file");
						$objPHPExcel->setActiveSheetIndex(0);
						$objActSheet = $objPHPExcel->getActiveSheet();	//获取当前激活的工作表指针
						$objRichText = new \PHPExcel_RichText();
						$objRichText->createText('');
						$objActSheet->getStyle('A2:T2')->getFill()->getStartColor()->setARGB('00CCCCCC');			// 底纹
						// 列宽
						$objActSheet->getColumnDimension('A')->setWidth(10);
						$objActSheet->getColumnDimension('B')->setWidth(10);
						$objActSheet->getColumnDimension('C')->setWidth(16);
						$objActSheet->getColumnDimension('D')->setWidth(10);
						$objActSheet->getColumnDimension('E')->setWidth(12);
						$objActSheet->getColumnDimension('F')->setWidth(14);
						$objActSheet->getColumnDimension('G')->setWidth(14);
						$objActSheet->getColumnDimension('H')->setWidth(10);
						$objActSheet->getColumnDimension('I')->setWidth(10);
						$objActSheet->getColumnDimension('J')->setWidth(10);
						$objActSheet->getColumnDimension('K')->setWidth(10);
						$objActSheet->getColumnDimension('L')->setWidth(10);
						$objActSheet->getColumnDimension('M')->setWidth(10);
						$objActSheet->getColumnDimension('N')->setWidth(10);

						foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N') as $k=>$v){
							$objActSheet->getStyle($v.'4')->getAlignment()->setHorizontal("center");
						}
						$objActSheet->getRowDimension(0)->setRowHeight(80);				//此前插1
						$objActSheet->getRowDimension(2)->setRowHeight(22);
						$objActSheet->getStyle('A2')->getFont()->setBold(true);       // 加粗
						$objActSheet->setCellValue('A2', mb_convert_encoding('日期：'.date('Y/m/d',time()).' （网站更新日期）', "UTF-8", "auto"));
						$objActSheet->setCellValue('Q2', mb_convert_encoding('货币单位：RMB·元', "UTF-8", "auto"));
						$objActSheet->mergeCells('A1:T1');	//合并
						$objActSheet->mergeCells('A2:P2');	//合并
						$objActSheet->mergeCells('Q2:T2');	//合并
						$objActSheet->getRowDimension(3)->setRowHeight(22);
						$objActSheet->setCellValue('A3', mb_convert_encoding('来源', "UTF-8", "auto"));
						$objActSheet->setCellValue('B3', mb_convert_encoding('形状', "UTF-8", "auto"));
						$objActSheet->setCellValue('C3', mb_convert_encoding('证书', "UTF-8", "auto"));
						$objActSheet->setCellValue('D3', mb_convert_encoding('编号', "UTF-8", "auto"));
						$objActSheet->setCellValue('E3', mb_convert_encoding('钻重', "UTF-8", "auto"));
						$objActSheet->setCellValue('F3', mb_convert_encoding('颜色', "UTF-8", "auto"));
						$objActSheet->setCellValue('G3', mb_convert_encoding('净度', "UTF-8", "auto"));
						if($isColor=='4' || $isColor=='2'){
							$objActSheet->setCellValue('H3', mb_convert_encoding('色度', "UTF-8", "auto"));
						}else{
							$objActSheet->setCellValue('H3', mb_convert_encoding('切工', "UTF-8", "auto"));
						}
						$objActSheet->setCellValue('I3', mb_convert_encoding('抛光', "UTF-8", "auto"));
						$objActSheet->setCellValue('J3', mb_convert_encoding('对称', "UTF-8", "auto"));
						$objActSheet->setCellValue('K3', mb_convert_encoding('荧光', "UTF-8", "auto"));
						$objActSheet->setCellValue('L3', mb_convert_encoding('全深比', "UTF-8", "auto"));
						$objActSheet->setCellValue('M3', mb_convert_encoding('台宽比', "UTF-8", "auto"));
						if($isColor=='4'){
							$objActSheet->setCellValue('N3', mb_convert_encoding('每卡单价', "UTF-8", "auto"));
							$objActSheet->setCellValue('O3', mb_convert_encoding('单粒价格', "UTF-8", "auto"));
						}else if($isColor=='3' && C('price_display_type')=='0'){
							$objActSheet->setCellValue('N3', mb_convert_encoding('国际报价', "UTF-8", "auto"));		//国际报价
							$objActSheet->setCellValue('O3', mb_convert_encoding('每卡单价', "UTF-8", "auto"));		//每卡单价
							$objActSheet->setCellValue('P3', mb_convert_encoding('折扣', "UTF-8", "auto"));			//折扣
							$objActSheet->setCellValue('Q3', mb_convert_encoding('单粒价格', "UTF-8", "auto"));		//单粒价格
						}else{
							$objActSheet->setCellValue('N3', mb_convert_encoding('单粒价格', "UTF-8", "auto"));
						}
						//设置对齐方式及边框
						foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
							$objActSheet->getStyle($v.'3')->getAlignment()->setHorizontal("center");
							$objCellBorder = $objActSheet->getStyle($v.'3')->getBorders();//获取边框
						}
						$index=4;
						$i = 1;
						$count_weight = 0;
						foreach($dataList as $row)
						{
							$objActSheet->getRowDimension($index)->setRowHeight(22);
							$objActSheet->setCellValue('A'.$index, mb_convert_encoding($row['location'], "UTF-8", "auto"));							//来源
							$objActSheet->setCellValue('B'.$index, mb_convert_encoding($row['shape'], "UTF-8", "auto"));							//形状
							$objActSheet->setCellValue('c'.$index, mb_convert_encoding($row['certificate_number'], "UTF-8", "auto"));				//证书		
							$objActSheet->setCellValue('D'.$index, mb_convert_encoding($row['goods_name'], "UTF-8", "auto"));						//证书
							$objActSheet->setCellValue('E'.$index, mb_convert_encoding(floatval($row['weight']), "UTF-8", "auto"));					//重量
							if($row['luozuan_type'] == 1){
								$objActSheet->setCellValue('F'.$index, mb_convert_encoding($row['intensity'].' '.$row['color'], "UTF-8", "auto"));	//颜色
							}else{
								$objActSheet->setCellValue('F'.$index, mb_convert_encoding($row['color'], "UTF-8", "auto"));						//颜色
							}
							$objActSheet->setCellValue('G'.$index, mb_convert_encoding($row['clarity'], "UTF-8", "auto"));							//净度
							$objActSheet->setCellValue('H'.$index, mb_convert_encoding($row['cut'], "UTF-8", "auto"));								//切工
							$objActSheet->setCellValue('I'.$index, mb_convert_encoding($row['polish'], "UTF-8", "auto"));							//抛光
							$objActSheet->setCellValue('J'.$index, mb_convert_encoding($row['symmetry'], "UTF-8", "auto"));							//对称
							$objActSheet->setCellValue('K'.$index, mb_convert_encoding($row['fluor'], "UTF-8", "auto"));							//荧光
							$objActSheet->setCellValue('L'.$index, mb_convert_encoding($row['dia_depth'], "UTF-8", "auto"));						//全深
							$objActSheet->setCellValue('M'.$index, mb_convert_encoding($row['dia_table'], "UTF-8", "auto"));						//台宽
							
							if($isColor=='4'){
								$objActSheet->setCellValue('N'.$index, mb_convert_encoding(floatval($row['cur_price']), "UTF-8", "auto"));			//每卡价格
								$objActSheet->setCellValue('O'.$index, mb_convert_encoding(floatval($row['price']), "UTF-8", "auto"));				//单粒价格
								$sum_price='O';
							}else if($isColor=='3' && C('price_display_type')=='0'){		
								$objActSheet->setCellValue('N'.$index, mb_convert_encoding(floatval($row['dia_global_price']), "UTF-8", "auto"));	//国际报价
								$objActSheet->setCellValue('O'.$index, mb_convert_encoding(floatval($row['cur_price']), "UTF-8", "auto"));			//每卡单价
								$objActSheet->setCellValue('P'.$index, mb_convert_encoding(floatval($row['dia_discount_all']), "UTF-8", "auto"));	//折扣
								$objActSheet->setCellValue('Q'.$index, mb_convert_encoding(floatval($row['price']), "UTF-8", "auto"));				//单粒价格	
								$sum_price='Q';								
							}else{
								$objActSheet->setCellValue('N'.$index, mb_convert_encoding(floatval($row['price']), "UTF-8", "auto"));				//单粒价格
								$sum_price='N';
							}
							
							//设置边框
							foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
								$objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
								$objActSheet->getStyle($v.$index)->getAlignment()->setHorizontal("center");
							}
							$i++;
							$index++;
						}

						$objActSheet->getRowDimension($index)->setRowHeight(22);	//设置行高
						$objActSheet->mergeCells("A$index:B$index");	//合并
						$objActSheet->mergeCells("C$index:E$index");	//合并
						$objActSheet->mergeCells("F$index:G$index");	//合并
						$objActSheet->mergeCells("H$index:K$index");	//合并
						$objActSheet->mergeCells("L$index:P$index");	//合并
						$objActSheet->mergeCells("Q$index:T$index");	//合并
						$objActSheet->setCellValue('A'.$index, mb_convert_encoding('货品总量(Ct) ', "UTF-8", "auto"));
						$objActSheet->setCellValue('C'.$index, sprintf('=SUM(E4:E%s)',($index-1)));
						$objActSheet->setCellValue('F'.$index, mb_convert_encoding('总金额 ', "UTF-8", "auto"));
						$objActSheet->setCellValue('H'.$index, sprintf('=SUM('.$sum_price.'4:'.$sum_price.'%s)',($index-1)));
						//$objActSheet->setCellValue('L'.$index, mb_convert_encoding('客户公司 ', "UTF-8", "auto"));
						$objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("RIGHT");//对齐方式
						$objActSheet->getStyle('C'.$index)->getAlignment()->setHorizontal("LEFT");//对齐方式
						$objActSheet->getStyle('F'.$index)->getAlignment()->setHorizontal("RIGHT");//对齐方式
						$objActSheet->getStyle('H'.$index)->getAlignment()->setHorizontal("LEFT");//对齐方式
						//$objActSheet->getStyle('L'.$index)->getAlignment()->setHorizontal("RIGHT");//对齐方式
						$objActSheet->getStyle('Q'.$index)->getAlignment()->setHorizontal("LEFT");//对齐方式
						foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
							$objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
						}
						$index++;
						$objActSheet->getRowDimension($index)->setRowHeight(22);	//设置行高
						//增加边框
						$styleThinBlackBorderOutline = array(  
							'borders' => array(  
								'outline' => array(
									'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框  
								),  
							),  
						);
						foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T') as $k=>$v){
							$objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
							for($i = 1; $i <= $index; $i++){
									$objPHPExcel->getActiveSheet()->getStyle($v.$i.':'.$v.$i)->applyFromArray($styleThinBlackBorderOutline);
							};
						}
						$fileName = date('YmdHis',time()).rand(100000,999999);
						//$xlsTitle = "裸钻数据";
						ob_end_clean();
						header('pragma:public');
						header('Cache-Control: max-age=0');
						header('Content-type:application/vnd.ms-excel;name="'.$xlsTitle.'.xls"');
						header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
						$PHPIF = new \PHPExcel_IOFactory();
						$objWriter = $PHPIF->createWriter($objPHPExcel, 'Excel5');
						$objWriter->save('php://output');
						exit;
			}else{
				return false;
			}
	}


	/**
	*	B2BB2C导出裸钻execl
	*	zhy find404@foxmail
	*	2016年11月25日 11:39:49

	function exportdiamondFile($data,$discount=0){
		set_time_limit(0);
		ignore_user_abort(true);
		ini_set('memory_limit','1024M');
		$uid = $_SESSION['web']['uid'];
		$md5Data = md5(serialize($data));	//计算数据的MD5
		$excelname="download/luozuan/diamond_".$md5Data.".xls";
		if(file_exists($excelname)){//若存在缓存，直接返回缓存数据
			return $excelname;
		}
		import("Org.Util.PHPExcel");
		$objPHPExcel=new \PHPExcel();
		$PHPReader=new \PHPExcel_Reader_Excel5();
 
		$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
			->setLastModifiedBy("Maarten Balliauw")
			->setTitle("Office 2007 XLSX Test Document")
			->setSubject("Office 2007 XLSX Test Document")
			->setDescription("Document for Office 2007 XLSX, generated using PHP classes.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Test result file");
		$objPHPExcel->setActiveSheetIndex(0);
		$objActSheet = $objPHPExcel->getActiveSheet();	//获取当前激活的工作表指针

		$objRichText = new \PHPExcel_RichText();
		$objRichText->createText('');
		$objActSheet->getStyle('A2:T2')->getFill()->getStartColor()->setARGB('00CCCCCC');			// 底纹
		// 列宽
		$objActSheet->getColumnDimension('A')->setWidth(10);
		$objActSheet->getColumnDimension('B')->setWidth(10);
		$objActSheet->getColumnDimension('C')->setWidth(16);
		$objActSheet->getColumnDimension('D')->setWidth(10);
		$objActSheet->getColumnDimension('E')->setWidth(12);
		$objActSheet->getColumnDimension('F')->setWidth(14);
		$objActSheet->getColumnDimension('G')->setWidth(14);
		$objActSheet->getColumnDimension('H')->setWidth(10);
		$objActSheet->getColumnDimension('I')->setWidth(10);
		$objActSheet->getColumnDimension('J')->setWidth(10);
		$objActSheet->getColumnDimension('K')->setWidth(10);
		$objActSheet->getColumnDimension('L')->setWidth(10);
		$objActSheet->getColumnDimension('M')->setWidth(10);
		$objActSheet->getColumnDimension('N')->setWidth(10);

		foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N') as $k=>$v){
			$objActSheet->getStyle($v.'4')->getAlignment()->setHorizontal("center");
		}
		$objActSheet->getRowDimension(0)->setRowHeight(80);				//此前插1

		$objActSheet->getRowDimension(2)->setRowHeight(22);
		$objActSheet->getStyle('A2')->getFont()->setBold(true);       // 加粗
		$objActSheet->setCellValue('A2', mb_convert_encoding('日期：'.date('Y/m/d',time()).' （网站更新日期）', "UTF-8", "auto"));
		$objActSheet->setCellValue('Q2', mb_convert_encoding('货币单位：RMB·元', "UTF-8", "auto"));

		$objActSheet->mergeCells('A1:T1');	//合并
		$objActSheet->mergeCells('A2:P2');	//合并
		$objActSheet->mergeCells('Q2:T2');	//合并
		$objActSheet->getRowDimension(3)->setRowHeight(22);
		$objActSheet->setCellValue('A3', mb_convert_encoding('来源', "UTF-8", "auto"));
		$objActSheet->setCellValue('B3', mb_convert_encoding('形状', "UTF-8", "auto"));
		$objActSheet->setCellValue('C3', mb_convert_encoding('证书', "UTF-8", "auto"));
		$objActSheet->setCellValue('D3', mb_convert_encoding('编号', "UTF-8", "auto"));
		$objActSheet->setCellValue('E3', mb_convert_encoding('钻重', "UTF-8", "auto"));
		$objActSheet->setCellValue('F3', mb_convert_encoding('颜色', "UTF-8", "auto"));
		$objActSheet->setCellValue('G3', mb_convert_encoding('净度', "UTF-8", "auto"));
		$objActSheet->setCellValue('H3', mb_convert_encoding('切工', "UTF-8", "auto"));
		$objActSheet->setCellValue('I3', mb_convert_encoding('抛光', "UTF-8", "auto"));
		$objActSheet->setCellValue('J3', mb_convert_encoding('对称', "UTF-8", "auto"));
		$objActSheet->setCellValue('K3', mb_convert_encoding('荧光', "UTF-8", "auto"));
		$objActSheet->setCellValue('L3', mb_convert_encoding('全深比', "UTF-8", "auto"));
		$objActSheet->setCellValue('M3', mb_convert_encoding('台宽比', "UTF-8", "auto"));
		$objActSheet->setCellValue('N3', mb_convert_encoding('单粒价格', "UTF-8", "auto"));

		//设置对齐方式及边框
		foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N') as $k=>$v){
			$objActSheet->getStyle($v.'3')->getAlignment()->setHorizontal("center");
			$objCellBorder = $objActSheet->getStyle($v.'3')->getBorders();//获取边框
		}

		$index=4;
		$i = 1;
		$count_weight = 0;

		foreach($data as $row)
		{
			$objActSheet->getRowDimension($index)->setRowHeight(22);
			$objActSheet->setCellValue('A'.$index, mb_convert_encoding($row['location'], "UTF-8", "auto"));							//来源
			$objActSheet->setCellValue('B'.$index, mb_convert_encoding($row['shape'], "UTF-8", "auto"));							//形状
			$objActSheet->setCellValue('c'.$index, mb_convert_encoding($row['certificate_number'], "UTF-8", "auto"));				//证书		
			$objActSheet->setCellValue('D'.$index, mb_convert_encoding($row['goods_name'], "UTF-8", "auto"));						//证书
			$objActSheet->setCellValue('E'.$index, mb_convert_encoding(floatval($row['weight']), "UTF-8", "auto"));					//重量
			if($row['luozuan_type'] == 1){
				$objActSheet->setCellValue('F'.$index, mb_convert_encoding($row['intensity'].' '.$row['color'], "UTF-8", "auto"));	//颜色
			}else{
				$objActSheet->setCellValue('F'.$index, mb_convert_encoding($row['color'], "UTF-8", "auto"));						//颜色
			}
			$objActSheet->setCellValue('G'.$index, mb_convert_encoding($row['clarity'], "UTF-8", "auto"));							//净度
			$objActSheet->setCellValue('H'.$index, mb_convert_encoding($row['cut'], "UTF-8", "auto"));								//切工
			$objActSheet->setCellValue('I'.$index, mb_convert_encoding($row['polish'], "UTF-8", "auto"));							//抛光
			$objActSheet->setCellValue('J'.$index, mb_convert_encoding($row['symmetry'], "UTF-8", "auto"));							//对称
			$objActSheet->setCellValue('K'.$index, mb_convert_encoding($row['fluor'], "UTF-8", "auto"));							//荧光
			$objActSheet->setCellValue('L'.$index, mb_convert_encoding($row['dia_depth'], "UTF-8", "auto"));						//全深
			$objActSheet->setCellValue('M'.$index, mb_convert_encoding($row['dia_table'], "UTF-8", "auto"));						//台宽
			$objActSheet->setCellValue('N'.$index, mb_convert_encoding(floatval($row['price']), "UTF-8", "auto"));					//单粒价格
			//设置边框
			foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N') as $k=>$v){
				$objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
				$objActSheet->getStyle($v.$index)->getAlignment()->setHorizontal("center");
			}
			$i++;
			$index++;
		}

		$objActSheet->getRowDimension($index)->setRowHeight(22);	//设置行高
		$objActSheet->mergeCells("A$index:B$index");	//合并
		$objActSheet->mergeCells("C$index:E$index");	//合并
		$objActSheet->mergeCells("F$index:G$index");	//合并
		$objActSheet->mergeCells("H$index:K$index");	//合并
		$objActSheet->mergeCells("L$index:P$index");	//合并
		$objActSheet->mergeCells("Q$index:T$index");	//合并
		$objActSheet->setCellValue('A'.$index, mb_convert_encoding('货品总量(Ct) ', "UTF-8", "auto"));
		$objActSheet->setCellValue('C'.$index, sprintf('=SUM(E4:F%s)',($index-1)));
		$objActSheet->setCellValue('F'.$index, mb_convert_encoding('总金额 ', "UTF-8", "auto"));
		$objActSheet->setCellValue('H'.$index, sprintf('=SUM(N4:T%s)',($index-1)));
		$objActSheet->setCellValue('L'.$index, mb_convert_encoding('客户公司 ', "UTF-8", "auto"));
		$objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("RIGHT");//对齐方式
		$objActSheet->getStyle('C'.$index)->getAlignment()->setHorizontal("LEFT");//对齐方式
		$objActSheet->getStyle('F'.$index)->getAlignment()->setHorizontal("RIGHT");//对齐方式
		$objActSheet->getStyle('H'.$index)->getAlignment()->setHorizontal("LEFT");//对齐方式
		$objActSheet->getStyle('L'.$index)->getAlignment()->setHorizontal("RIGHT");//对齐方式
		$objActSheet->getStyle('Q'.$index)->getAlignment()->setHorizontal("LEFT");//对齐方式
		
		
		
		foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N') as $k=>$v){
			$objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
		}
		$index++;
		$objActSheet->getRowDimension($index)->setRowHeight(22);	//设置行高
		//增加边框
		$styleThinBlackBorderOutline = array(  
			'borders' => array(  
				'outline' => array(
					'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框  
				),  
			),  
		);

		foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T') as $k=>$v){
			$objCellBorder = $objActSheet->getStyle($v.$index)->getBorders();//获取边框
			for($i = 1; $i <= $index; $i++){
					$objPHPExcel->getActiveSheet()->getStyle($v.$i.':'.$v.$i)->applyFromArray($styleThinBlackBorderOutline);
			};
		}
		$fileName = date('YmdHis',time()).rand(100000,999999);
		$xlsTitle = "裸钻数据";
		ob_end_clean();
		header('pragma:public');
		header('Cache-Control: max-age=0');
		header('Content-type:application/vnd.ms-excel;name="'.$xlsTitle.'.xls"');
		header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印

		$PHPIF = new \PHPExcel_IOFactory();
		$objWriter = $PHPIF->createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}	
		*/	

	/**
	*	搜索钻石属性
	*	zhy		find404@foxmail.com
	*	2017年3月21日 14:15:19
	*/
 	function Diamonds(){
		if(isset($_GET['unforgecode']) && !empty($_GET['unforgecode'])){
			$goods_id=	M('goods_sku')->where("unforgecode = '".$_GET['unforgecode']."'")->getField('goods_id');
			if($goods_id){
				$M          = D('Common/Goods');
				$goodsInfo    = $M -> get_info($goods_id);
				$goodsInfo['attributes']  = M('goods_associate_attributes')->where('goods_id = '.$goods_id) ->select();
				
				foreach($goodsInfo['attributes'] as $k => $v){
					$attr_ids[] = $v['attr_id'];
				}
				
				$attr_ids = array_unique($attr_ids);    //获取所有属性id(不重复的id)
				foreach($attr_ids as $k1 => $v1){
					foreach($goodsInfo['attributes'] as $k2 => $v2){
						if($v2['attr_id'] == $v1){
							$adjustGoodsAttributes[$v1] = $v2;
							$adjustGoodsAttributes[$v1]['attr_value_name'] = '';
						}
					}
				}
				$goodsInfo['attributes'] = $adjustGoodsAttributes;
			}
			$this->goodsInfo=$goodsInfo;
		}
		$this->display();
	}
 
 
 
 
 
}


?>