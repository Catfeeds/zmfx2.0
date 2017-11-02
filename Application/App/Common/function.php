<?php

/**
	用户登录检查
**/
function isUser(){
    if(!session('app.uid') || intval(session('app.uid'))<=0){
        redirect(U('/public/login'));
    }
}


/**
 * 设置配置值
 * @param array $domain
 */
function setConfig($domain){
	$data = M('config')->field('agent_id = '.C('agent_id').' and config_key,config_value')->select();
    $data1 = M('config_value')->where('agent_id = '.C('agent_id'))->field('config_key,config_value')->select();
    //初始化默认值
    foreach ($data as $key => $value) {
        C($value['config_key'],$value['config_value']);
    }
    //注入设置的配置
    foreach ($data1 as $key => $value) {
		C($value['config_key'],$value['config_value']);
    }
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
 * 后端数据处理价格格式化
 * @param unknown $price
 * @return string
 */
function formatPrice($price){
	$price = round($price,2);
	return number_format($price,2,'.','');
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
