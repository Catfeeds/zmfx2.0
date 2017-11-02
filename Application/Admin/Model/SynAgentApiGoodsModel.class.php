<?php
/**
 * 同步数易宝用户自身的API数据到网站中
 */
namespace Admin\Model;
use Think\Model;
class SynAgentApiGoodsModel extends Model{
  	protected $autoCheckFields = false;
	/**
	 * @author	：haoge
	 * @content：获取成品/定制产品列表
	 * @time	：2016-10-10
	**/
    public function whichAgent() {
		$agent_id = C('agent_id');

		if($agent_id == '2'){ // www.rosy-zb.com
			$this->synLanpoLuozuan();
		}elseif($agent_id == '85'){//兰柏数据
			$this->synLanpoLuozuan();
		}

    }

    protected function checkip(){
        $ip = get_client_ip();
        if($ip != '192.168.16.21' and $ip != '14.215.133.100' and $ip != '192.168.16.63' and $ip != '127.0.0.1'){
            error_log(date('Y-m-d H:i:s', time())  .": ".$ip."IP error \n", 3, $logdir );
            return false;
        }
        return true;
    }
    public function synLanpoLuozuan(){
    	set_time_limit(0);
        ini_set('memory_limit', '256M');     
    	header("Content-Type: text/html; charset=utf-8");

        $user_name = '兰柏数据对接';
        //$logdir    =  "/data/htdocs/log/zmfx/synlanpoluozun.log"; //测试上面
        $logdir      =  "/home/data/log/zmfx/synlanpoluozun.log";     //速易宝上
        //$logdir    =  "./Uploads/log/synlanpaluozun.log";  
        $this->checkip();
       

        // $url = "http://test.yjyzb.net/service/webYjyDiamonds.asmx?WSDL"; 
        // $user_pwd  = '123456789';    
                
    	$url = "http://www.yjyzb.net/service/webYjyDiamonds.asmx?WSDL";   	 
        $user_pwd  = '111111';   

    	$client    = new \SoapClient($url);
        $filetype  = 'LB_';    
		//设置字符编码
		$client->soap_defencoding = 'utf-8';
		$client->xml_encoding = 'utf-8';

        $pagesize = 500;
        $biaoji_time = time();
           
       
        for($page=0;$page<140;$page++){  //大概69000条记录
            $lapa_key_value = S('lapa_key');
            $newstr = $client->getDiamonds(array('key'=>$lapa_key_value, 'find'=>"", 'start'=>$page*$pagesize+１ , 'count'=>$pagesize, 'sort'=>'c_carat', 'webRate'=>'1.0'))->getDiamondsResult->any;
            
            if(empty($newstr)){
                //echo 'login:';
                $ukeyobj = $client->login(array('userKey'=>$user_name,'password'=>$user_pwd));
                $lapa_key_value = $ukeyobj->loginResult;
                S('lapa_key', $lapa_key_value, 3600);
                $newstr = $client->getDiamonds(array('key'=>$lapa_key_value, 'find'=>"", 'start'=>$page*$pagesize+１, 'count'=>$pagesize, 'sort'=>'c_carat', 'webRate'=>'1.0'))->getDiamondsResult->any;
            }
        
            $num = $this->synLanpoLuozuanInsert($biaoji_time, $newstr,  $filetype);
            unset($newstr);
            //echo date('H:i:s', time()).','.$page.':'.$num.'<br>';
            error_log(date('Y-m-d H:i:s', time()) .', PAGE:'.$page.', PAGESIZE:'.$num." \n", 3, $logdir );
            if($num == 0){
                break;
            }   
        }
             
 		
	   // $data['name'] = "通过API接口导入了".$i."条数据";
	   // $data['addtime'] = time();
	   // $data['agent_id'] = C('agent_id');
	   // $data['uid']= $_SESSION['admin']['uid'];
	   // M('luozuan_history')->data($data)->add();
       
       $goods_luozuan = D('GoodsLuozuan');        
       $goods_luozuan->delLuozuanBatchData($filetype, $biaoji_time, -1, $agent_id); 
        
    }

    public function synLanpoLuozuanInsert($biaoji_time, $newstr, $filetype){
        $Upload = A('Upload');
        $i = 0;
         //转换$newstr值为标准XML　----------开始    
        $newxml = '<?xml version="1.0" encoding="utf-8"?><diamonds>';
        $index = strpos($newstr, '<t_diamonds');
        while($index != ""){
            $newxml = $newxml.'<t_diamonds>';
            $newstr = substr($newstr, $index);
            $newstr = substr($newstr, strpos($newstr, '>')+1);
            $newxml = $newxml.substr($newstr, 0, strpos($newstr, '</t_diamonds>'));
            $newxml = $newxml.'</t_diamonds>';
            $index = strpos($newstr, '<t_diamonds');
        }
        $newxml = $newxml.'</diamonds>';
        //转换$diamondsxmlstr值为标准XML　----------结束
        //获取新XML数组值
        $diamonds = simplexml_load_string($newxml); 

        foreach($diamonds as $newData){ 
            if($Upload->diamondInLuozuan($newData['c_certificateNo']) and $_REQUEST['import_checkSelDiamond']=='on'){
                    continue;
            }
            
            $newData =  (array)$newData;
            
            $data['goods_name']         = $filetype.$newData['c_id'];
            $data['certificate_type']   = strip_tags($newData['c_certificateLink']);
            $data['certificate_number'] = $newData['c_certificateNo'];
            $data['location']           = $Upload->formatDiamondLocation($newData['c_areaState']);
            if($newData['c_typeName'] == '深圳现货' or $newData['c_typeName'] == '香港现货'){
                $data['quxiang']        = "现货";
            }else{
                $data['quxiang']        = "订货";
            }

           
                                                 
            $data['shape']              = $Upload->getDiamondsShapeNo($filetype, $newData['c_shapeName']);                                   
            $data['weight']             = $newData['c_carat'];
            if($newData['c_colorName'] == '彩钻'){
                list($data['color'], $data['intensity']) = $Upload->getColorDiamond($data['certificate_number'], trim(strtoupper($data['certificate_type']))  );
                $data['luozuan_type']       = 1;         //默认为白钻  钻石类型   
            }else{
                $data['color']              = $Upload->formatDiamondColor($newData['c_colorName']); 
                $data['luozuan_type']       = 0;         //默认为白钻  钻石类型   
            }
            

            $data['clarity']            = $Upload->formatDiamondClarity($newData['c_clarityName']);
            $data['cut']                = $Upload->formatDiamondCut($newData['c_cutName']);                                      
            $data['polish']             = $Upload->formatDiamondPolish($newData['c_polishName']);                      
            $data['symmetry']           = $Upload->formatDiamondSymmetry($newData['c_symmetryName']);                                      
            $data['fluor']              = $Upload->formatDiamondFlour($newData['c_fluorescenceName']);

            $data['dia_size']           = $newData['c_measurement'];
            $data['dia_table']          = $newData['c_tableProportion'];
            $data['dia_depth']          = $newData['c_depthProportion'];
            

            $data['milk']               = $Upload->formatDiamondMilky($newData['c_milky']);
            $data['coffee']             = $Upload->formatDiamondCoffee($newData['c_browness']);              
            $data['dia_global_price']   = floatval($newData['c_USPrice']); 
            if($newData['c_userDiscount'] < 0){
                $data['dia_discount']       = 100 + $newData['c_userDiscount'];
            }else{
                $data['dia_discount']       = $newData['c_userDiscount'];
            }
           
            $data['price']              = $newData['c_userUnitPrice']; 
            //$data['imageURL']           = $newData['c_picturePaths']; 
            $data['goods_number'] = 1;   
            $data['tid'] = 0;  
            $data['belongs_id'] = 1;                          
            $data['type'] = 0;

            $data['supply_id'] = 0;  
            $data['supply_gid'] = 0;  
            $data['zm_gid'] = 0;  
            $data['agent_id'] = 85;                         
            $data['biaoji_time'] = $biaoji_time;


            if($Upload->checkData($data['shape'],$data['certificate_type'],$data['certificate_number'],$data['weight'],$data['color'],$data['clarity'],$data['dia_global_price'],$newData['c_userDiscount'],$data['milk'],$data['coffee'],$data['quxiang'],'')){               
                $Upload->addLuozuanBatchData($data);
                $i++;
            }
        }

        return $i;   
    }




    public function synCadiamondLuozuan(){
        set_time_limit(0);
        ini_set('memory_limit', '256M');     
        header("Content-Type: text/html; charset=utf-8");

        $user_name = '中美钻石对接裸钻API接口';
        $logdir    =  "/home/data/log/zmfx/synCadiamondLuozuan.log";     //速易宝上
        $agent_id  = 83;
        $filetype  = 'Cad_';          
        $biaoji_time = time();
           

        $this->checkip();
        //$url = "http://kgk.cc/WCFKGKccClientService/Service1.svc/GetStockChinaXML/395623948@qq.com/94101577";
        $url = "http://kgk.cc/WCFKGKccClientService/Service1.svc/GetStockChinaXML/395623948@qq.com/kgk";     
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $doc=curl_exec($ch);
        curl_close($ch);

        $diamond = simplexml_load_string($doc);
        $num = 0;
        foreach($diamond as $value){
            $newData =  (array)$value;
            $res = $this->synCadiamondLuozuanInsert($biaoji_time, $newData,  $filetype, $agent_id);
            if($res){
                $num++;
            }
        }
        error_log(date('Y-m-d H:i:s', time()) .', '.$user_name.', input number:'.$num." \n", 3, $logdir );
        $goods_luozuan = D('GoodsLuozuan');        
        $goods_luozuan->delLuozuanBatchData($filetype, $biaoji_time, -1, $agent_id);    

    }

   
    public function synCadiamondLuozuanInsert($biaoji_time, $newData, $filetype, $agent_id){
        $Upload = A('Upload');           
        $data['goods_name']         = $filetype.$newData['stone'];
        $data['certificate_type']   = $newData['labName'];
        $data['certificate_number'] = $newData['certNo'];

        if(strtoupper($newData['location']) == 'CS'){
            $data['location']           = '深圳';
        }else{
            $data['location']           = $Upload->formatDiamondLocation($newData['location']);
        }
        $data['quxiang']            = "订货";                                                  
        $data['shape']              = $Upload->getDiamondsShapeNo($filetype, $newData['Shape']);                                   
        $data['weight']             = $newData['Weight'];   
        $data['color']              = $Upload->formatDiamondColor($newData['colorName']); 
        $data['luozuan_type']       = 0;         //默认为白钻  钻石类型   
        $data['clarity']            = $Upload->formatDiamondClarity($newData['clarityName']);
        $data['cut']                = $Upload->formatDiamondCut($newData['cutName']);                                      
        $data['polish']             = $Upload->formatDiamondPolish($newData['polName']);                      
        $data['symmetry']           = $Upload->formatDiamondSymmetry($newData['symName']);                                      
        $data['fluor']              = $Upload->formatDiamondFlour($newData['fluoName']);
        $data['dia_size']           = $newData['LxWxD'];
        $data['dia_table']          = $newData['Table1'];
        $data['dia_depth']          = $newData['Depth1'];
        $data['milk']               = $Upload->formatDiamondMilky('');//无奶
        $data['coffee']             = $Upload->formatDiamondCoffee('');//无咖              
        $data['dia_global_price']   = floatval($newData['intRap']); 
        if($newData['discount'] < 0){
            $data['dia_discount']       = 100 + $newData['discount'];
        }else{
            $data['dia_discount']       = $newData['discount'];
        }
       
        $data['price']              = $newData['Amount']; //这里的价格是美元
        //$data['imageURL']           = $newData['c_picturePaths']; 
        $data['goods_number'] = 1;   
        $data['tid'] = 0;  
        $data['belongs_id'] = 1;                          
        $data['type'] = 0;

        $data['supply_id'] = 0;  
        $data['supply_gid'] = 0;  
        $data['zm_gid'] = 0;  
        $data['agent_id'] = $agent_id;                         
        $data['biaoji_time'] = $biaoji_time;

        if($Upload->checkData($data['shape'],$data['certificate_type'],$data['certificate_number'],$data['weight'],$data['color'],$data['clarity'],$data['dia_global_price'],$newData['discount'],$data['milk'],$data['coffee'],$data['quxiang'],'')){  
            $Upload->addLuozuanBatchData($data);
           return true;
        }
        return false;
    }
  
       


}