<?php
use Think\Page;
// +----------------------------------------------------------------------
// | 手机端函数库，共有4个类型
// | 1。获取GET 2.设置 SET 3.判断检查IS 4公共处理类型 
// +----------------------------------------------------------------------

/**
 * 设置配置值
 * @param array $domain
 */
function setConfig($domain){
	$data = M('config')->field('agent_id = '.C('agent_id').' and config_key,config_value')->select();
    $data1        = M('config_value')->where('agent_id = '.C('agent_id'))->field('config_key,config_value')->select();
    //初始化默认值
    foreach ($data as $key => $value) {
        C($value['config_key'],$value['config_value']);
    }
    //注入设置的配置
    foreach ($data1 as $key => $value) {
		C($value['config_key'],$value['config_value']);
    }
}

/**
 * 获取产品材质
 */
function getGoodsMaterial(){
    $GAM = M('goods_material');
    return $GAM->where('agent_id = 7')->select();
}

/**
 * 返回供应宝链接
 * @param string $mo
 * @param string $vi
 * @return string
 */
function GetSupplyUrl($mo='',$vi='',$param=''){
    $siteweb = 'http://'.$_SERVER['HTTP_HOST'].'/Manage/';
	if($param){
		return $siteweb."route/mo/$mo/vi/$vi?$param";
	}else{
		return $siteweb."route/mo/$mo/vi/$vi";
	}
}
function getUploadLuozuanSelectHtml($html_attrs=array()){
    if(empty($html_attrs)){
        return '';
    }

    $html = '<select name="'.$html_attrs['name'].'" id="'.$html_attrs['id'].'" class="'.$html_attrs['class'].'">';        
    $html .= '<option value="">'.L('text_please_chose').'</option>';
    foreach($html_attrs['upload_title'] as $v){
        $v = trim($v);       
        $option = '<option value="'.$v.'" ';
        if(strtoupper($v) == strtoupper($html_attrs['val'])){
            $option .= ' selected ';
        }
        $option .= '>';
        $option .= $v.'</option>';
        $html   .= $option;
    }
    $html .= '</select>';
    return $html;
}
function getSelectHtml($html_attrs=array(),$m_name,$m_fields=array('value'=>'','text'=>''),$where=array(),$agent_id=0){

    if(empty($m_name)){
        return '';
    }else{  
        $Mobj     = M($m_name);
        if($agent_id){
            $where['agent_id'] = array('eq',$agent_id);
            $data = $Mobj->where('agent_id = '.$agent_id)->field($m_fields['value'].','.$m_fields['text'])->select();
        }else{
            $data = $Mobj->where($where)->field($m_fields['value'].','.$m_fields['text'])->select();
        }
        $html     = '<select ';
        if(count($html_attrs)>0){
            foreach($html_attrs as $key=>$value){
                $html .= $key.' = "'.$value.'" ';
            }
        }
        $html .= '>';
        $html .= '<option value="">'.L('text_please_chose').'</option>';
        foreach($data as $v){
            $option = '<option value="'.$v[$m_fields['value']].'" ';
            if($v[$m_fields['value']] == $html_attrs['value']){
                $option .= ' selected ';
            }
            $option .= '>';
            $option .= $v[$m_fields['text']].'</option>';
            $html   .= $option;
        }
        $html .= '</select>';
        return $html;
    }
}

/**
 * 生成IN参数
 * @param array $array
 * @param int $id
 * @return Ambigous <string, unknown>
 */
function parIn($array,$id){
	$ids ='';
	foreach ($array as $key => $value) {
		if($key) $ids .= ','.$value[$id];
		else $ids .= $value[$id];
	}
	return $ids;
}

/**
 * 把数组里的字段值作为KEY存放
 * @param array $array
 * @param string $id 没有键名，第一个就是键名
 */
function _arrayIdToKey($array,$id=''){
	if(!$id){$ids = array_keys($array[0]);$id = $ids[0];}
	foreach ($array as $key => $value) {
		$arr[$value[$id]] = $value;
	}
	return $arr;
}



/**
 * 递归数组
 * @param array $data 数组对象
 * @param int $id 一条记录的id
 * @param int $pid 上级id
 * @param int $objId 开始的id
 * @return array
 */
function arrayRecursive($data,$id,$pid,$objId=0,$isKey='0'){
    $list = array();
    foreach ($data AS $key => $val){
        if($val[$pid] == $objId){
            $val['sub'] = arrayRecursive($data, $id, $pid, $val[$id],$isKey);
            if(empty($val['sub'])){unset($val['sub']);}
            if($isKey) $list[] = $val;
            else $list[$val[$id]] = $val;
        }
    }
    return $list;
}

/**
 * 获得浏览器名称和版本
 * @return  string
 */
function getUserBrowser() {
    if (empty($_SERVER['HTTP_USER_AGENT'])) { return ''; }
    $agent = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)){
        $browser     = 'Internet Explorer';
        $browser_ver = $regs[1];
    }elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)){
        $browser     = 'FireFox';
        $browser_ver = $regs[1];
    }elseif (preg_match('/Maxthon/i', $agent, $regs)){
        $browser     = '(Internet Explorer ' .$browser_ver. ') Maxthon';
        $browser_ver = '';
    }elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)){
        $browser     = 'Opera';
        $browser_ver = $regs[1];
    }elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)){
        $browser     = 'OmniWeb';
        $browser_ver = $regs[2];
    }elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)){
        $browser     = 'Netscape';
        $browser_ver = $regs[2];
    }elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs) and stristr($agent,'Chrome')==false){
        $browser     = 'Safari';
        $browser_ver = $regs[1];
    }elseif (preg_match('/Chrome\/([^\s]+)/i', $agent, $regs)){
        $browser     = 'Chrome';
        $browser_ver = $regs[1];
    }elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)){
        $browser     = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
        $browser_ver = $regs[1];
    }elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)){
        $browser     = 'Lynx';
        $browser_ver = $regs[1];
    }
    if (!empty($browser)) {
        return addslashes($browser . ' ' . $browser_ver);
    } else {
        return 'Unknow browser';
    }
}

/**
 * 获得客户端的操作系统
 * @return string
 */
function getOs(){
    if (empty($_SERVER['HTTP_USER_AGENT'])){ return 'Unknown';}
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'win') !== false){
        if (strpos($agent, 'nt 5.1') !== false) $os = 'Windows XP';
        elseif (strpos($agent, 'nt 5.2') !== false) $os = 'Windows 2003';
        elseif (strpos($agent, 'nt 5.0') !== false) $os = 'Windows 2000';
        elseif (strpos($agent, 'nt 6.0') !== false) $os = 'Windows Vista';
        elseif (strpos($agent, 'nt') !== false) $os = 'Windows NT';
        elseif (strpos($agent, 'win 9x') !== false && strpos($agent, '4.90') !== false) $os = 'Windows ME';
        elseif (strpos($agent, '98') !== false) $os = 'Windows 98';
        elseif (strpos($agent, '95') !== false) $os = 'Windows 95';
        elseif (strpos($agent, '32') !== false) $os = 'Windows 32';
        elseif (strpos($agent, 'ce') !== false) $os = 'Windows CE';
    }
    elseif (strpos($agent, 'linux') !== false) $os = 'Linux';
    elseif (strpos($agent, 'unix') !== false) $os = 'Unix';
    elseif (strpos($agent, 'sun') !== false && strpos($agent, 'os') !== false) $os = 'SunOS';
    elseif (strpos($agent, 'ibm') !== false && strpos($agent, 'os') !== false) $os = 'IBM OS/2';
    elseif (strpos($agent, 'mac') !== false && strpos($agent, 'pc') !== false) $os = 'Macintosh';
    elseif (strpos($agent, 'powerpc') !== false) $os = 'PowerPC';
    elseif (strpos($agent, 'aix') !== false) $os = 'AIX';
    elseif (strpos($agent, 'hpux') !== false) $os = 'HPUX';
    elseif (strpos($agent, 'netbsd') !== false) $os = 'NetBSD';
    elseif (strpos($agent, 'bsd') !== false) $os = 'BSD';
    elseif (strpos($agent, 'osf1') !== false) $os = 'OSF1';
    elseif (strpos($agent, 'irix') !== false) $os = 'IRIX';
    elseif (strpos($agent, 'freebsd') !== false) $os = 'FreeBSD';
    elseif (strpos($agent, 'teleport') !== false) $os = 'teleport';
    elseif (strpos($agent, 'flashget') !== false) $os = 'flashget';
    elseif (strpos($agent, 'webzip') !== false) $os = 'webzip';
    elseif (strpos($agent, 'offline') !== false) $os = 'offline';
    else $os = 'Unknown';
    return $os;
}


/*根据形状编号获取钻石形状名称 by cool*/
function getDiamondsShapeName($shapeID="ROUND"){
    switch(trim(strtoupper($shapeID))){
        case "ROUND":
            $shapeName='圆形';
            break;
        case "OVAL":
            $shapeName='椭圆';
            break;
        case "MARQUIS":
            $shapeName='马眼';
            break;
        case "HEART":
            $shapeName='心形';
            break;
        case "PEAR":
            $shapeName='水滴';
            break;
        case "PRINCESS":
            $shapeName='方形';
            break;
        case "EMERALD":
            $shapeName='祖母绿';
            break;
        case "CUSHION":
            $shapeName='枕形';
            break;
        case "RADIANT":
            $shapeName='雷迪恩';
            break;
        case "BAGUETTE":
            $shapeName='梯方';
            break;
        /*		case 13:
                    $shapeName='Available';
                    break;
                case 14:
                    $shapeName='Bussiness Process';
                    break;
                case 15:
                    $shapeName='New Available';
                    break;
                case 16:
                    $shapeName='Show';
                    break;*/
        default:
            $shapeName='其它';
            break;
    }
    return $shapeName;
}
/**
 * 设置每页记录条数
 * @auther 张超豪
 * @param int $pageSize 每页的记录条数，默认是15,最大是100条
 * @return int $pageSize 
 */
function getPageSize($pageSize){
    
    if($pageSize < 1){
        $pageSize = 10;
    }else if($pageSize>1000){
        $pageSize = 1000;
    }
    return intval($pageSize);
}


/**
 * 设置商品类型
 * @return array $goodsTypeArray 
 * @auther 张超豪
 */
function getGoodsType(){
    return $goodsTypeArray = array(
        1=>L('goods_Type_diamond'),
        2=>L('goods_Type_sanhuo'),
        3=>L('goods_Type_product'),
        4=>L('goods_Type_custom'),
    ); 
}

/**
 * 订单状态文字描述
 * @return string $orderStatusStr 
 * @auther 张超豪
 */
function supplyGetOrderStatus($orderStatus = 100){
    if($orderStatus == -1){
        $orderStatusStr = L('index_order_quxiao');//'已取消'; 
    }elseif($orderStatus == 0){
        $orderStatusStr = L('index_order_pay');//'待付款';//'待处理'; 
    }elseif($orderStatus == 1){
        $orderStatusStr = L('index_order_pay');//'待付款';//'已确认, 待付款'; 
    }elseif($orderStatus == 2){
        $orderStatusStr = L('index_order_peihuo');//'待配货';//'已付款, 待配货'; 
    }elseif($orderStatus == 3){
        $orderStatusStr = L('index_order_fahuo');//'待发货';//'已配货, 待发货'; 
    }elseif($orderStatus == 4){
        $orderStatusStr = L('index_order_shouhuo');//'待收货';//'已发货, 待收货'; 
    }elseif($orderStatus == 5){
        $orderStatusStr = L('index_order_yishouhuo');//'已收货'; 
    }elseif($orderStatus == 6){
        $orderStatusStr = L('index_order_wancheng');//'已完成'; 
    }else{
        $orderStatusStr = L('index_order_status_error');   //订单状态出错
    }
    return $orderStatusStr;
}
/**
 * 订单状态数组
 * @return array $orderStatus 
 * @auther 张超豪
 * @addtime 2016-05-07
 */
function supplyOrderStatus(){
    $orderStatus = array(
         '0'=>L('index_order_daichuli')//待处理
        ,'1'=>L('index_order_pay')//'未付款'
        ,'2'=>L('index_order_peihuo')//'待配货'
        ,'3'=>L('index_order_fahuo')//'待发货'
        ,'4'=>L('index_order_shouhuo')//'待收货'
        ,'5'=>L('index_order_yishouhuo')//'已收货'
        ,'6'=>L('index_order_wancheng')//'已完成'
        ,'-1'=>L('index_order_quxiao')//'已取消'
    );
    return $orderStatus;
}
/**
 * 订单操作列表
 * @return array $orderActionList
 * @auther 张超豪
 * @addtime 2016-05-07
 */
function getSupplyOrderActionList($orderStatus=0){
    $list[0] = array('key'=>'orderNote',            'txt'=>'添加备注');
    $list[1] = array('key'=>'orderUpdate',          'txt'=>'确定有货和发货日期');
    $list[2] = array('key'=>'orderCancle',          'txt'=>'取消订单');
    $list[3] = array('key'=>'orderConfirm',         'txt'=>'确定订单');

    $list[4] = array('key'=>'orderCancleConfirm',   'txt'=>'取消订单确认');
    $list[5] = array('key'=>'orderPay',             'txt'=>'订单付款');
    $list[6] = array('key'=>'orderPeihuo',          'txt'=>'订单配货');

    $list[7] = array('key'=>'orderBuchajia',        'txt'=>'订单补差价');
    $list[8] = array('key'=>'orderFahuo',           'txt'=>'订单发货');
    $list[9] = array('key'=>'orderShouhuo',         'txt'=>'确定已收货');
    $list[10]= array('key'=>'orderOver',            'txt'=>'订单完成');

    //$orderActionList[] = $list[0];
    // if($orderStatus == 0){
    //     $orderActionList[] = $list[0];
    //     $orderActionList[] = $list[1];
    //    $orderActionList[] = $list[2];
    //    $orderActionList[] = $list[3];
    // }elseif($orderStatus == 200){
    //     $orderActionList[] = $list[1];
    // }
    // elseif($orderStatus == 1){
    //     $orderActionList[] = $list[4];
    //     $orderActionList[] = $list[5];
    //     $orderActionList[] = $list[6];
    // }elseif($orderStatus == 2){        
    //     $orderActionList[] = $list[7];
    //     $orderActionList[] = $list[8];
    // }elseif($orderStatus == 3){
    //     $orderActionList[] = $list[7];
    //     $orderActionList[] = $list[8];
    // }elseif($orderStatus == 4){
    //     $orderActionList[] = $list[9] ;
    // }elseif($orderStatus == 5){
    //     $orderActionList[] = $list[10]; 
    // }
    return $orderActionList;
}

/**
 * 后端数据处理价格格式化
 * @auther 张超豪
 * @addtime 2016-05-08
 * @param double $price
 * @return string
 */
function formatPrice($price){
    $price = round($price,2);
    return number_format($price,2,'.','');
}
/**
 * 钻明官网中的订单价格，人民币转成美元
 */
function getZMorderDollar($val = array()){
    if(count($val) == 0){
        return 0;
    }

    $Dollar = 0;
    $attribute = unserialize($val['attribute']);
    if(empty($attribute['supply_id'])){    //不是供应商的则跳过
        return 0;
    }

    $supply_advantage = M('supply_account', "zm_", "ZMFX_DB")->where('agent_id_build = '.$attribute['supply_id'])->getField('supply_advantage');
    $Dollar = formatPrice( ($attribute['dia_discount']- $supply_advantage) * $attribute['dia_global_price'] * $attribute['weight']/100);
    return $Dollar;
}

