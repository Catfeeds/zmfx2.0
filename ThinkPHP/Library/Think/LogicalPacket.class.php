<?php
/**
 *  
 * 遏制想象力和创造力的框架不是好框架。
 * zhy	find404@foxmail.com
 * 2017年10月18日 17:11:43
 */
namespace Think;
use Think\Exception;

class LogicalPacket{


    public   $Return              = 'error';     //接受条件数组		
    private static $key   		  = ['Judge','Msg','Right','Wrong'];
    private  $Array   		  	  = '';
 
 
	public  function __set($name,$value){
        $this->$name = $value;
    }
 
	 
    /**
     *  
     * zhy	find404@foxmail.com
     * 2017年10月18日 17:11:54
     */	 
    public function subject($val) {
		
		$Controller    =    new  \Think\View;
		
		foreach ($val as $k=>$v){
			if(is_array($v)){
				foreach ($v as $ke=>$va){
					$this->Array[$k][is_numeric($ke) ? self::$key[$ke] :$ke] = $va;
				}
			}else{
				$this->Array[0][self::$key[$k]] = $v;
			}
		}

 
		foreach($this->Array as $k=>$v){
			try {
				if($v['Judge']){
					isset($v['Right']) && $v['Right']();
				}else{
					isset($v['Wrong']) && $v['Wrong']();
					if($v['Msg']){
						throw new Exception($v['Msg']);
					}
				}
			} catch (Exception $e) {
				switch ($this->Return){
					case 'success':
						$Controller->assign('error',$e->getMessage());
						$Controller->assign('waitSecond','3');
						$Controller->assign('jumpUrl',"javascript:history.back(-1);");
						$Controller->display(C('TMPL_ACTION_SUCCESS'));
						exit();
					  break;
					case 'error':
						$Controller->assign('error',$e->getMessage());
						$Controller->assign('waitSecond','3');
						$Controller->assign('jumpUrl',"javascript:history.back(-1);");
						$Controller->display(C('TMPL_ACTION_ERROR'));
						exit();
					  break;
					case 'json':
						$this->ajaxReturn($e->getMessage());
					  break;
				}
			}	
		}
	}	 
 
 
 
 
}

?>