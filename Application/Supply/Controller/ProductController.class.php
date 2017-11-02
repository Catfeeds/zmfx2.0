<?php
namespace Supply\Controller;
class ProductController extends SupplyController{
	


    public function __construct() {
        parent::__construct();
    }

    //裸钻
    public function selectLuozuan(){
        $page_id            = I('page_id',1);
        $page_size          = I('page_size',50);

        $gid                = I('gid',0);
        $certificate_number = I('certificate_number','');
        $product_status     = I('product_status','');
        $time_begin         = I('time_begin','');
        $time_end           = I('time_end','');

        $where                           = array();
        if($gid){
            $where['gid']                = array('eq',$gid);
        }
        if($certificate_number){
            $where['certificate_number'] = array('eq',$certificate_number);
        }
        if($product_status!==''){
            if( $product_status == '0' ) {
                $where['product_status'] = array('eq', $product_status);
                $where['valid_status']   = array('eq', 1);
            }
            if( $product_status == '1' ) {
                $where['product_status'] = array('eq', $product_status);
                $where['valid_status']   = array('eq', 1);
            }
            if( $product_status == '2' ) { //已经审核
                $where['valid_status']   = array('eq','1');
            }
            if( $product_status == '3' ) { //未审核
                $where['valid_status']   = array('neq','1');
            }
        }
        if($time_begin){
            $where['create_time']        = array('egt',$time_begin);
        }
        if($time_end){
            $where['create_time']        = array('elt',$time_end.' 23:59:59');
        }
        if($time_begin && $time_end){
            $where['create_time']        = array(array('egt',$time_begin),array('elt',$time_end.' 23:59:59'), 'and') ;
        }
        $sglM  = D('SupplyGoodsLuozuan');
        $data  = $sglM        -> getLuozuanList($where,'gid desc',$page_id,$page_size,$this->agent_id);
        $product_status_array = array(
            '0'=>L('text_product_unshelves'),
            '1'=>L('text_product_onshelves'),
        );
        foreach($data['list'] as &$row){
            $row['create_time']         = date('Y-m-d H:i',strtotime($row['create_time']));
            $row['product_status_text'] = $product_status_array[$row['product_status']];
        }
        $this -> echoJson($data);

    }
    public function luozuanOnSale(){
        $goods_ids = I('luozuan_goods_ids',array());
        $sglM      = D('SupplyGoodsLuozuan');
        if($sglM->onSale($goods_ids)){
            D('SaleStatus')->on_sale($goods_ids,'luozuan',$this->agent_id);
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('shelves_failed'));
        }
    }
    public function luozuanOffShelves(){
        $goods_ids = I('luozuan_goods_ids',array());
        $sglM      = D('SupplyGoodsLuozuan');
        if($sglM->offShelves($goods_ids)){
            D('SaleStatus')->off_shelves($goods_ids,'luozuan',$this->agent_id);
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('dowshelf_failed'));
        }
    }
    public function luozuanOnSaleOne(){       
        $sglM = D('SupplyGoodsLuozuan');

        $data['product_status'] = 1;
        $data['on_sale_time']   = date('Y-m-d H:i:s', time());           
        $sglM->where('valid_status=1 and agent_id ='.$this->agent_id)->save($data); 

        $goods_array = $sglM->where('product_status=1 and agent_id ='.$this->agent_id)->field('gid')->select();
        foreach($goods_array as $key => $value){
            $goods_ids[]=$value['gid'];
        }
        D('SaleStatus')->on_sale($goods_ids,'luozuan',$this->agent_id);
        $this->echoJson(1);
       
    }
    public function luozuanOffShelvesOne(){    
        $sglM = D('SupplyGoodsLuozuan');        
        $sglM->where('valid_status=1 and agent_id ='.$this->agent_id)->setField('product_status', 0); 
        M('goods_luozuan', 'zm_', 'ZMALL_DB')->where('supply_id = '. $this->agent_id)->delete();       
        $this -> echoJson(1);
        
    }
    public function luozuanDelete(){
        $goods_ids = I('luozuan_goods_ids',array());
        $sglM      = D('SupplyGoodsLuozuan');
        if($sglM -> deleteLuozuan($goods_ids,$this->agent_id)){
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('delete_failed'));
        }
    }

    public function luozuanUpdate(){
        $sglM                  = D('SupplyGoodsLuozuan');
        $_POST['valid_status'] = 0;
		if($_POST['weight']){
		   $_POST['weight']=substr(sprintf("%.3f", $_POST['weight']),0,-1);
		}
        if($sglM->create()){
            $sglM -> modifyLuozuan($this->agent_id);
            $this -> echoJson(1,100,L('operation_success'));
        }else{
            $msg   = $sglM->getError();
            $this -> echoJson(0,400,$msg);
        }
    }

    public function luozuanAdd(){
        $sglM              = D('SupplyGoodsLuozuan');
        $_POST['agent_id'] = $this -> agent_id;
        if($sglM->create()){
            $sglM -> addLuozuan();
            $this -> echoJson(1,100,L('operation_success'));
        }else{
            $msg   = $sglM->getError();
            $this -> echoJson(0,400,$msg);
        }
    }
    //散货
    public function selectSanhuo(){
        $page_id                = I('page_id',1);
        $page_size              = I('page_size',50);

        $goods_id               = I('goods_id',0);
        $goods_sn               = I('goods_sn',0);
        $product_status         = I('product_status','');
        $time_begin             = I('time_begin','');
        $time_end               = I('time_end','');

        $where                           = array();
        if($goods_id){
            $where['goods_id']           = array('eq',$goods_id);
        }
        if($goods_sn){
            $where['goods_sn']           = array('eq',$goods_sn);
        }
        if($product_status!==''){
            if( $product_status == '0' ) {
                $where['product_status'] = array('eq', $product_status);
                $where['valid_status']   = array('eq', 1);
            }
            if( $product_status == '1' ) {
                $where['product_status'] = array('eq', $product_status);
                $where['valid_status']   = array('eq', 1);
            }
            if( $product_status == '2' ) { //已经审核
                $where['valid_status']   = array('eq','1');
            }
            if( $product_status == '3' ) { //未审核
                $where['valid_status']   = array('neq','1');
            }
        }
        if($time_begin){
            $where['create_time']       = array('egt',$time_begin);
        }
        if($time_end){
            $where['create_time']       = array('elt',$time_end.' 23:59:59');
        }
        if($time_begin && $time_end){
            $where['create_time']       = array(array('egt',$time_begin),array('elt',$time_end.' 23:59:59'), 'and') ;
        }
        $sglM  = D('SupplyGoodsSanhuo');
        $data  = $sglM -> getSanhuoList($where,'goods_id desc',$page_id,$page_size,$this->agent_id);
        $product_status_array = array(
            '0'=>L('text_product_unshelves'),
            '1'=>L('text_product_onshelves'),
        );
        $goods_status_array = array(
            '0'=>L('text_no_goods'),
            '1'=>L('text_have_goods'),
        );
        //散伙类型
        $gstM                    = D('GoodsSanhuoType');
        $goods_sanhuo_type_array = $gstM -> getList();
        $goods_sanhuo_type_array_en = $gstM -> getList_en();

        foreach($data['list'] as &$row){
            $row['product_status_text'] = $product_status_array[$row['product_status']];
            $row['goods_status_text']   = $goods_status_array[$row['goods_status']];
            $row['type_name']           = $goods_sanhuo_type_array[$row['tid']];
            $row['type_name_en']        = $goods_sanhuo_type_array_en[$row['tid']];
            $row['create_time']         = date('Y-m-d H:i',strtotime($row['create_time']));
            $row['color']               = $row['color'];
            $row['clarity']             = $row['clarity'];
            $row['cut']                 = htmlspecialchars($row['cut']);
            $row['weights']             = htmlspecialchars($row['weights']);
        }
        $this -> echoJson($data);
    }
    public function selectSanhuoInfo4c(){
        $goods_id  = I('goods_id',0);
        $sgsM      = D('SupplyGoodsSanhuoCc');
        $data      = $sgsM->getList($goods_id);
        $this -> echoJson($data);
    }
    public function sanhuoOnSale(){
        $goods_ids = I('sanhuo_goods_ids',array());
        $sglM      = D('SupplyGoodsSanhuo');
        if($sglM->onSale($goods_ids)){
            D('SaleStatus')->on_sale($goods_ids,'sanhuo',$this->agent_id);
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('shelves_failed'));
        }
    }
    public function sanhuoOffShelves(){
        $goods_ids = I('sanhuo_goods_ids',array());
        $sglM      = D('SupplyGoodsSanhuo');
        if($sglM->offShelves($goods_ids)){
            D('SaleStatus')->off_shelves($goods_ids,'sanhuo',$this->agent_id);
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('dowshelf_failed'));
        }
    }
    public function sanhuoDelete(){
        $goods_ids = I('sanhuo_goods_ids',array());
        $sglM      = D('SupplyGoodsSanhuo');
        if($sglM->deleteSanhuo($goods_ids)){
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('delete_failed'));
        }
    }
    public function sanhuoUpdate(){
        $goods_id              = I('goods_id',0);
        $cc                    = I('cc','');
        $sglM                  = D('SupplyGoodsSanhuo');
        $_POST['valid_status'] = 0;
		
		$_POST['color']        = I('color',0);
		$_POST['clarity']      = I('clarity',0);
		
        if($sglM->create()){
            $sglM -> modifySanhuo($goods_id);
            if($cc){
                $cc_obj = json_decode(htmlspecialchars_decode($cc),true);
                $sgsM   = D('SupplyGoodsSanhuoCc');
                $sgsM  -> addGoods4c($goods_id,$cc_obj);
            }
            $this -> echoJson($cc_obj,100,L('operation_success'));
        }else{
            $msg   = $sglM->getError();
            $this -> echoJson(0,400,$msg);
        }
    }
    public function sanhuoAdd(){
        $cc                = I('cc','');
        $sglM              = D('SupplyGoodsSanhuo');
        $_POST['agent_id'] = $this -> agent_id;
        if($sglM->create()){
            $id = $sglM -> addSanhuo();
            if( $cc && $id ){
                $cc_obj = json_decode(htmlspecialchars_decode($cc),true);
                $sgsM   = D('SupplyGoodsSanhuoCc');
                $sgsM  -> addGoods4c($id,$cc_obj,$this -> agent_id);
            }
            $this -> echoJson(1,100,L('operation_success'));
        }else{
            $msg   = $sglM->getError();
            $this -> echoJson(0,400,$msg);
        }
    }
    public function getSanhuo4c(){
        $type      = I('type','');
        $parent_id = I('parent_id',0);
        $data      = array();
        switch($type){
            case "cut":
                $data = M('goods_sanhuo_cut')->select();
                break;
            case "color":
                $data = M('goods_sanhuo_color')->select();
                break;
            case "clarity":
                $data = M('goods_sanhuo_clarity')->select();
                break;
            case "weights":
                if(empty($parent_id)){
                    $where = " pid = 0";
                }else{
                    $where = " pid = ".intval($parent_id);
                }
                $data = M('goods_sanhuo_weights')->where($where)->select();
                break;
            default:
                break;
        }
        $this -> echoJson($data,100,L('operation_success'));
    }

    //成品,订制
    public function selectGoods(){
        $page_id                = I('page_id',1);
        $page_size              = I('page_size',50);

        $goods_type             = I('goods_type',0);
        $goods_sn               = I('goods_sn','');
        $goods_id               = I('goods_id',0);
        $product_status         = I('product_status','');
        $time_begin             = I('time_begin','');
        $time_end               = I('time_end','');

        $where                           = array();
        if($goods_id){
            $where['goods_id']           = array('eq',$goods_id);
        }
        if($goods_sn){
            $where['goods_sn']           = array('eq',$goods_sn);
        }
        if($goods_type){
            $where['goods_type']         = array('eq',$goods_type);
        }
        if($product_status!==''){
            if( $product_status == '0' ) {
                $where['product_status'] = array('eq', $product_status);
                $where['valid_status']   = array('eq', 1);
            }
            if( $product_status == '1' ) {
                $where['product_status'] = array('eq', $product_status);
                $where['valid_status']   = array('eq', 1);
            }
            if( $product_status == '2' ) { //已经审核
                $where['valid_status']   = array('eq','1');
            }
            if( $product_status == '3' ) { //未审核
                $where['valid_status']   = array('neq','1');
            }
        }
        if($time_begin){
            $where['create_time']       = array('egt',$time_begin);
        }
        if($time_end){
            $where['create_time']       = array('elt',$time_end.' 23:59:59');
        }
        if($time_begin && $time_end){
            $where['create_time']       = array(array('egt',$time_begin),array('elt',$time_end.' 23:59:59'), 'and') ;
        }
        $sglM  = D('Goods');
        $data  = $sglM -> getList($where,'goods_id desc',$page_id,$page_size,$this->agent_id);
        $product_status_array = array(
            '0'=>L('text_product_unshelves'),
            '1'=>L('text_product_onshelves'),
        );
        foreach($data['list'] as &$row){
            $row['product_status_text'] = $product_status_array[$row['product_status']];
            $row['create_time']         = date('Y-m-d H:i',strtotime($row['create_time']));
            $row['thumb']               = '/Public/'.$row['thumb'];
            $row['category_name']       = M('goods_category')->where("category_id = ".$row['category_id'])->getField('category_name');
        }
        $this -> echoJson($data);
    }
    public function goodsOnSale(){
        $goods_ids = I('goods_ids',array());
        $sglM      = D('Goods');
        if($sglM->onSale($goods_ids)){
            D('SaleStatus')->on_sale($goods_ids,'goods',$this->agent_id);
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('shelves_failed'));
        }
    }
    public function goodsOffShelves(){
        $goods_ids = I('goods_ids',array());
        $sglM      = D('Goods');
        if($sglM->offShelves($goods_ids)){
            D('SaleStatus')->off_shelves($goods_ids,'goods',$this->agent_id);
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('dowshelf_failed'));
        }
    }
    public function goodsDelete(){
        $goods_ids = I('goods_ids',array());
        $sglM      = D('Goods');
        if($sglM->deleteGoods($goods_ids,$this->agent_id)){
            $this -> echoJson(1);
        }else{
            $this -> echoJson(0,400,L('delete_failed'));
        }
    }

    //保存数据
    public function importLuozuanSave(){
		$msg = L('designation');
    	$rules = array(
		    array('shape',			    'require', $msg.'shape'),
		    array('certificate_type',   'require', $msg.'certificate_type'), 
		    array('certificate_number', 'require', $msg.'certificate_number'), 
		    array('weight',			 	'require', $msg.'weight'), 
		    array('color',				'require', $msg.'color'), 
		    array('clarity',			'require', $msg.'clarity'), 
		    array('dia_global_price',	'require', $msg.'dia_global_price'), 
		    array('dia_discount',		'require', $msg.'dia_discount'),  
	    );
    	$file_path = $_SESSION['supply']['luozuan_file_path'];
    	if($file_path) {     // 上传错误提示错误信息
            $lM           = D('LuozuanImport');

            if (!$lM->validate($rules)->create()){
			     // 如果创建失败 表示验证没有通过 输出错误提示信息
			     exit($lM->getError());
			}
			$fields_data = array();


			$lM    = D('LuozuanImport'); 
            $info  = $lM->getFileLines($file_path, 1, 1);
            $row   = explode(',', $lM->autoToutf8($info[0]));    		//第一条是标题                         
            if(count($row)<8){  						//4c，lab， 证书号, 价格,折扣  必须要有8列
            	$row = explode('"', $lM->autoToutf8($info[0]));     //csv，一般用  ，"  分割
            	if($row<8){
            		$this->error(L('error_luozuan_data'));
            	}             	
            }
            foreach($row as $val){
             	$title_col[] = strtoupper($val);
            }


            $POST = I('post.');
            
           // print_r($title_col);
          //  print_r($POST);
			foreach($POST as $key => $value){
				if(!empty($value)){
					foreach($title_col as $k=>$v){                       
						if(strtoupper($value) == trim($v)){
							$fields_data[$key] = $k;                           
							break;
						}
					}
				}
			}
            
			
            //$fields_data  = $lM->checkFields($file_path, $luozuanFieldsData_rule);
            //$fields_data的格式为array(0=>'字段key1',1=>'字段key2')
            $code         = $lM -> importSupply($file_path,$fields_data,500);
            if($code){
                $this -> success(L('upload_success'), '/Manage/route/mo/Product/vi/index');
                unset($_SESSION['supply']['luozuan_file_path']);
                die;
            }
        }

    }

    //上传表格
    public function importLuozuan(){

        $verification_code = I('post.verification_code','');
        if(!$this->checkVerify($verification_code)){
            $info  = L('text_verification_code');
            $this -> error($info);
            die;
        }
        $upload           = new \Think\Upload();                // 实例化上传类
        $upload->maxSize  = 31457280 ;                           // 设置附件上传大小
        $upload->exts     = array('csv');        				// 设置附件上传类型   , 'xls', 'xlsx'
        $upload->savePath = C('SUPPLY_LUOZUAN_FILE_SAVE_PATH'); // 设置附件上传目录
        $info             = $upload->upload();
        $file_path        = realpath(dirname(__FILE__).'/../../../Public/').$info['luozuan_file']['savepath'].$info['luozuan_file']['savename'];
       
        if($info) {     // 上传错误提示错误信息
        	$_SESSION['supply']['luozuan_file_path'] = $file_path ;
        	
            $lM   = D('LuozuanImport');
            $info = $lM->getFileLines($file_path, 1, 1);//获取前面4条信息
            $row  = explode(',', $lM->autoToutf8($info[0]));    		//第一条是标题
            if(count($row)<8){  						//4c，lab， 证书号, 价格,折扣  必须要有8列
            	$row   = explode('"', $lM->autoToutf8($info[0]));     //csv，一般用  ，"  分割
            	if($row<8){
            		$this->error(L('error_luozuan_data'));
            	}             	
            }

            foreach($row as $val){
             	$title_col[] = strtoupper($val);
            }
            $this->title_col = $row;

            $this->fieldMap = $lM->importLuozuanFieldMap($title_col);
            
            /* 导入其它模块的语言  START */

            $lang_path = MODULE_PATH.'Lang/'. LANG_SET;         
		    if(file_exists($lang_path."/Product/index.php")){
		        $view_lang_array1 = include_once($lang_path."/Product/index.php");                  
		        if( !empty($view_lang_array) && !is_array($view_lang_array)){
		            $this->error($vi.' language file is error !');
		        }
		    }else{
		    	$this->error($vi.' language file is error !');
		    }

		    if(file_exists($lang_path."/Product/upload.php")){
		        $view_lang_array2 = include_once($lang_path."/Product/upload.php");                  
		        if( !empty($view_lang_array) && !is_array($view_lang_array)){
		            $this->error($vi.' language file is error !');
		        }
		    }else{
		    	$this->error($vi.' language file is error !');
		    }

			if(isset($view_lang_array1) && is_array($view_lang_array1) && isset($view_lang_array2) && is_array($view_lang_array2) ){
        		$lang_array = array_merge($this -> L, $view_lang_array1, $view_lang_array2);
    		} 
    		
   			$this->L  = $lang_array;  //提供控制器使用   
    		array_walk($lang_array,function($value,$key){
		        L($key,$value);
		    }); 

		    $this -> assign('L',$lang_array);      //提供模板使用 

		    /* 导入其它模块的语言   END  */

            $this->display('Manage/Default/Product/luozuanUploadMap'); 

        }else{
        	unset($_SESSION['luozuan_file_path']);
        	$this->file_path = '';
            $this->error(L('upload_failed'));
            die();
        }
    }
}
