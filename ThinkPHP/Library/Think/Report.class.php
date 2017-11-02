<?php
/*
 *获取证书查询结果
  //使用示例：
  //$report = new report('1156602515','3',"1");
  //$report = new report('F5D17713','0.07',"2");
  //$report = new report('13018913002','3.02',"3");
  $report = new report('S172742','58639394',"4");
  print_r($report->getReportDate());die;
 *
*/
namespace Think;
class Report{
  public $reportData=array(); //证书数据（数组）
  public $reportRes;  //证书缓存（行记录）
  public $zs_id;  //证书编号（字符串）
  public $zs_weight;  //钻石重量（数值）

  function report($zs_id,$zs_weight,$zs_type){
    $this->zs_id = $zs_id;
    $this->zs_weight = $zs_weight;
    $this->zs_type = $zs_type;
	$this->setReportInfoCacheing(1);	//设置当前证书的缓存状态
  }
  
	function getReportData(){	//根据证书类型返回证书数据
    	$this->reportRes = $this->getReportDate4db();
    	
    	if(empty($this->reportRes['data'])){	//缓存为空，则进行远程采集，并对采集结果进行缓存
			switch($this->zs_type){
			case 1:		//GIA
				/*
					GIA:证书下载	https://myapps.gia.edu/ReportCheckPortal/downloadReport.do?reportNo=<?php echo $zs_id;?>&weight=<?php echo $zs_weight;?>
					GIA:证书下载  http://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber=1156602515&url=https://myapps.gia.edu/ReportCheckPOC/pocservlet?ReportNumber=1156602515
					GIA:证书数据  http://www.gia.edu/otmm_wcs_int/proxy-report/?ReportNumber=1156602515&url=https://myapps.gia.edu/ReportCheckPOC/pocservlet?ReportNumber=1156602515
				*/
				$url = 'https://www.gia.edu/report-check?reportno='.$this->zs_id;
				//$url = 'https://www.gia.edu/cs/Satellite?c=Page&childpagename=GIA%2FPage%2FReportCheck&cid=1355954554547&go=Look+Up&pagename=GIA%2FDispatcher&reportno='.$this->zs_id;
				$doc =  $this->request_by_curl($url);
				preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
				$document = $this->request_by_curl('https://www.gia.edu/otmm_wcs_int/proxy-report/','ReportNumber='.$this->zs_id.'&url=https://myapps.gia.edu/ReportCheckPOC/pocservlet?ReportNumber='.$matchs[1]);
				$this->reportData = (array)(simplexml_load_string($this->autoToutf8($document))->REPORT_DTLS->REPORT_DTL);
				$data = array();
				foreach($this->reportData as $k=>$v){
				  if(trim($v)<>'')$data[$k]=strval($v);
				}
				if(!empty($data) && $data['MESSAGE']!='Please check your entries and try again.'){
					$datas = array('0'=>array('证书编号'=>$data['REPORT_NO'],'颁发日期'=>$data['REPORT_DT']),
								   '1'=>array('证书类型'=>$data['REPORT_TYPE'],'证书下载'=>'<a  onclick="download_pdf_ajax('.$data['REPORT_NO'].')"   id="pdfLink" class="btn-link"  target="_blank">请点击此处下载证书</a> &nbsp; <a href="/Home/Search/onlineView/reportNumber/'.$data['REPORT_NO'].'.html" class="btn-link"  target="_blank">在线预览</a>'),
								   '2'=>array('尺寸'=>$data['WIDTH'],'重量'=>$data['WEIGHT'].'carat'),
								   '3'=>array('颜色'=>$data['COLOR'],'净度'=>$data['CLARITY']),	
                                   '4'=>array('切工'=>$data['FINAL_CUT'],'全深比'=>$data['DEPTH_PCT'].'%'),						   
								   '5'=>array('台宽比'=>$data['TABLE_PCT'].'%','冠角'=>$data['CRN_AG']),	
                                   '6'=>array('冠高比'=>$data['CRN_HT'],'亭角'=>$data['PAV_AG']),
                                   '7'=>array('亭深比'=>$data['PAV_DP'],'星小面比'=>$data['STR_LN']),
                                   '8'=>array('下腰小面比'=>$data['LR_HALF'],'腰棱'=>$data['GIRDLE'].' , '.$data['GIRDLE_CONDITION'].' , '.$data['GIRDLE_PCT']),
								   '9'=>array('底尖'=>$data['CULET_SIZE'],'拋光'=>$data['POLISH']),
                                   '10'=>array('对称'=>$data['SYMMETRY'],'荧光'=>$data['FLUORESCENCE_INTENSITY']),	
                                   '11'=>array('净度特征'=>$data['KEY_TO_SYMBOLS'],'腰码'=>$data['INSCRIPTION']),										   
                                   '12'=>array('备注'=>$data['REPORT_COMMENTS']),
								 );
				}else{			$datas=null;				}
			  	$this->reportData = $datas;
				if(!empty($datas)){			$this->insertReport2db(); 	}		//将证书数据写入数据库
				unset($data);
				break;
			case 2:		//IGI	F5D17713	0.07	M1F58085	0.41
				$zs_url = 'http://www.igiworldwide.com/ch/searchreport_postreq.php?r='.$this->zs_id;
				$doc = $this->getContent($zs_url);
				preg_match_all('/<span.*>(.*)<\/span>/isU',$doc,$this->reportData);
 				$data = array();
				if(!empty($this->reportData[1])){
					$datas =  array('0'=>array($this->reportData[1][0]=>$this->reportData[1][1],$this->reportData[1][4]=>$this->reportData[1][5]),
									'1'=>array($this->reportData[1][8]=>$this->reportData[1][9],$this->reportData[1][12]=>$this->reportData[1][13]),
									'2'=>array($this->reportData[1][16]=>$this->reportData[1][17],$this->reportData[1][20]=>$this->reportData[1][21]),
									'3'=>array($this->reportData[1][24]=>$this->reportData[1][25],$this->reportData[1][28]=>$this->reportData[1][29]),	
									'4'=>array($this->reportData[1][32]=>$this->reportData[1][33],$this->reportData[1][36]=>$this->reportData[1][37]),						   
									'5'=>array($this->reportData[1][40]=>$this->reportData[1][41],$this->reportData[1][44]=>$this->reportData[1][45]),	
									'6'=>array($this->reportData[1][48]=>$this->reportData[1][49],$this->reportData[1][52]=>$this->reportData[1][53]),
									'7'=>array($this->reportData[1][56]=>$this->reportData[1][57],$this->reportData[1][60]=>$this->reportData[1][61]),
									'8'=>array($this->reportData[1][64]=>$this->reportData[1][65],'LASERSCRIBE'=>'IGI '.$this->reportData[1][1]),
					);
				}else{			$datas=null;				}
			  	$this->reportData = $datas;
				if(!empty($datas)){			$this->insertReport2db(); 	}		//将证书数据写入数据库
				unset($data);
				break;
			case 3:		//HRD
				// $zs_url = 'http://my.hrdantwerp.com?id=1&no_cache=1&L=3';
				$post_string = '?id=34&no_cache=1&L=3&record_number='.$this->zs_id.'&weight='.$this->zs_weight;
				$document = $this->request_by_curl('https://my.hrdantwerp.com',$post_string);
				$document = $this->autoToutf8($document);
				preg_match_all('/<span.*>(.*)<\/span>/',$document,$mark_reportData);
				preg_match_all('/<strong.*>(.*)<\/strong>/isU',$document,$title_reportData);
				preg_match_all("/<table[^>]*?>(.*?)<\/table>/s",$document,$reportDatas);
				preg_match_all('/<td[^>]*([\s\S]*?)<\/td>/i',$reportDatas[0][0],$this->reportData[0]);
				preg_match_all('/<td[^>]*([\s\S]*?)<\/td>/i',$reportDatas[0][1],$this->reportData[1]);	
				$data = array();
				if(!empty($this->reportData[0][1])){
						$datas =  array('0'=>array('证书编号'=>'>'.$title_reportData[1][0],'颁发日期'=>'>'.$title_reportData[1][2]),
										'1'=>array('证书类型'=>$title_reportData[1][1],'抛光'=>$this->reportData[0][1][13]),
										'2'=>array('形状'=>$this->reportData[0][1][1],'荧光'=>$this->reportData[1][1][1]),
										'3'=>array('重量'=>$this->reportData[0][1][3],'尺寸'=>$this->reportData[1][1][3]),
										'4'=>array('色级'=>$this->reportData[0][1][5],'腰线'=>$this->reportData[1][1][5]),	
										'5'=>array('净度'=>$this->reportData[0][1][7],'底尖'=>$this->reportData[1][1][7]),						   
										'6'=>array('全深比'=>$this->reportData[1][1][9],'切工比例'=>$this->reportData[0][1][11]),	
										'7'=>array('台宽比'=>$this->reportData[1][1][11],'亭深比'=>$this->reportData[1][1][15]),
										'8'=>array('冠高比'=>$this->reportData[1][1][13],'对称性'=>$this->reportData[0][1][15]),
										'9'=>array('亭部下腰面水平投影长度比'=>$this->reportData[1][1][19],'冠高比与亭深比之和'=>$this->reportData[1][1][21]),
										'10'=>array('冠部上腰面水平投影长度比'=>$this->reportData[1][1][17],'备注'=>$mark_reportData[1][4]),
						);
				}else{			$datas=null;				}
			  	$this->reportData = $datas;
				if(!empty($datas)){			$this->insertReport2db(); 	}		//将证书数据写入数据库
				unset($data);
				break;
			case 4:		//NGTC
				$zs_url = 'http://www.ngtc.gov.cn/ngtc/channel/detect/checkpaper_que.do';
				$curl = new curl(true,'zs/data.cache/ntgc.cookies.txt');
				$post_url = 'http://www.ngtc.gov.cn/ngtcquery/QueryCertServlet';
				  
				$document = $curl->post($post_url,array('certNo'=>$this->zs_id,'verificationCode'=>(int)$this->zs_weight),$post_url);
				//preg_match_all('/\<td[^>]([\s\S]*?)\<\/td\>/i',$document,$this->reportData);
				preg_match_all('/\<tr\s*align\=\"left\"\s*valign\=\"top\"\>\s*\<td\s*height\=\"32\"\>\s*(.*)\s*\<br\>\s*.*\s*\<\/td\>\s*\<td\s*height\=\"32\"\s*class\=\"word1\"\>\s*(.*)\s*\<\/td\>\s*\<\/tr\>/iu',$document,$this->reportData);
				$data = array();
				foreach($this->reportData[1] as $k=>$v){
          			$s = '';$arr = array();
          			if(preg_match_all('/[%°\x{4e00}-\x{9fa5}]+/u',$this->reportData[1][$k],$arr)){
            			foreach($arr[0] as $v){
            				$s .= $v;
            			}
          			}
		          
          			$this->reportData[1][$k] = str_ireplace(array('%%','°°'),array('%','°'),$s);
          			$data[$this->reportData[1][$k]] = $this->reportData[2][$k];
				}
				$this->reportData = $data;  
				if(!empty($data)){
					$this->insertReport2db(); //将证书数据写入数据库
				}
				unset($data);
				break;
			default:
				$this->reportData = array();
				break;
		}
		}else{
      		$this->reportData = $this->reportRes['data'];
		}
		
		return $this->reportData;	//返回证书数据
	}
	
	// function getGIAReportUrl(){//获取GIA证书路径
		// include_once('curl.class.php');
		// $curl = new \Think\curl(false);
		// $reportNo = $this->zs_id;
		// if(empty($reportNo))die('请输入GIA证书编号');
		// $url = "https://www.gia.edu/cs/Satellite?c=Page&childpagename=GIA%2FPage%2FReportCheck&cid=1355954554547&go=Look+Up&pagename=GIA%2FDispatcher&reportno=$reportNo";
		// $doc = $curl->get($url);
		// preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
		// $url = 'https://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber='.$reportNo.'&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber='.$matchs[1];		
		// unset($curl,$reportNo,$doc,$matchs);
		// return $url;
	// }

	//下载GIA PDF文件
	function downloadGIAPDF($url,$reportNo){
		define('cacheDir','./zs/data.cache/',false);		/*缓存目录*/
		$b = cacheDir.$reportNo.'.pdf';
		if(!file_exists($b)){		/*PDF文件不存在则下载*/
			$this->str2file(cacheDir,'log-'.date('Ymd',time()).'.php',date('H:i:s',time())."Start the download Certificate ... Certificate Number:$reportNo\r\n",'a+');
			//获取远程文件所采用的方法   
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');			
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_REFERER, "http://www.baidu.com/");	
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$pdf_file = curl_exec($ch);
			curl_close($ch);
			$size=strlen($pdf_file);
			if($size>2000){
				$fp2=@fopen($b,'w');  
				fwrite($fp2,$pdf_file);  
				fclose($fp2);  
			}
			unset($pdf_file,$url);
		}
		return true;
	}
	
	/*检查PDF文档完整性 by 范灿杰
	 * 检测原理：完整的PDF文档最后以“EOF结束”
	 * 返回值：false 表示文档错误；
	 *
	function checkGIAPDF(){
		$ReportNumber = $this->zs_id;
		$b = cacheDir.$ReportNumber.'.pdf';
		$strPDF = file_get_contents($b);
		if(substr($strPDF,-4,3)<>'EOF'){
			return false;
		}else{
			return true;
		}
	}
	 */
	/*设置证书数据的缓冲状态
	 * 0 表示未处理；1 表示缓冲中；缓冲时候不能建立新的下载操作
	 * 2 表示PDF下载失败；3 表示下载成功
	 * */
	function setReportInfoCacheing($status=0){
		$data['cacheing'] = $status;
		$where = "`zs_id`='".$this->zs_id."' ";
		return M('Report')->data($data)->save();
	}
	
	/*文件路径，文件名称，内容，写入方式(默认为追加写入)*/
	function str2file($file_path,$file_name,$str,$mod='a+'){
		if(!file_exists($file_path)){if(!mkdir($file_path,0777,true)){return false;}}
		$file = fopen($file_path.$file_name,$mod);	/*/读写方式向文件追加内容，没有则创建/*/
		fwrite($file,$str);	/*/序列化数组，然后写入文件/*/
		fclose($file);	/*/保存关闭文件/*/
		return true;
	}

	
	function getReportDate4db(){  //从数据库中读取证书
	
	  $where = "zs_id = '".$this->zs_id."'";
	  $where .= " AND zs_type = '".$this->zs_type."'"; 
	  
	  $res = M('report')->where($where)->find();
	  
	  if($res)$res['data'] = unserialize($res['data']);
	  return $res;
	}
	
	//  向数据库添加证书信息
	public function insertReport2db(){ 
	  	$data['zs_id'] = $this->zs_id;
	  	$data['zs_weight'] = $this->zs_weight;
	  	$data['zs_type'] = $this->zs_type;
	  	
	  	$data['data'] = $this->autoToutf8(serialize($this->reportData));
	  	$MReport = M('report'); 
	  
	  	if($MReport->where("zs_id='".$data['zs_id']."'")->find()){
	  		return empty($this->reportData)?false:$MReport->where('zs_id='.$data['zs_id'])->data($data)->save();	 
	  	}else{
	  		return empty($this->reportData)?false:$MReport->data($data)->add();	
		}
	}
	
	
	
	function request_by_curl($remote_server, $post_string='' ,$referer='')
    {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remote_server);
            if($referer<>''){
                    curl_setopt($ch, CURLOPT_REFERER, $referer);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = curl_exec($ch);
            curl_close($ch);

            return $data;
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
	
	
	
	function array_is_null($arr=null){
		if(is_array($arr)){
			foreach($arr as $k=>$v){
				if($v&&!is_array($v)){
					return false;
				}
				$t = array_is_null($v);
				if(!$t){
					return false;
				}
			}
			return true;
		}elseif(!$arr){
		    return true;
	    }else{
		    return false;
		}
	}
	
	function getContent($url,$httpheader=0){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if($httpheader==0){
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
			curl_setopt($curl, CURLOPT_REFERER, "http://www.baidu.com/");
		}
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 0);
		curl_setopt($curl, 156, 99999); // problem solved
		curl_setopt($curl, CURLOPT_REFERER, $url);
		$content = curl_exec($curl);
		curl_close($curl);
		return $content;
	}
	
}  
?>
