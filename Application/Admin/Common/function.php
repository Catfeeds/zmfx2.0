<?php
/**
 * 扩展的in_array数组
 * @param string $value
 * @param array $array
 */
function in_array_diy($value,$array){
	$valueS = explode(',', $value);
	foreach ($valueS as $key) {
		if(in_array($key, $array)) return true;
	}
	return false;
}
/**
 * 获取单个客户信息
 * @param int $uid
 * @return array
 */
function getUserInfo($uid){
	return M('user')->where("uid=$uid and agent_id=".C('agent_id'))->find();
}
/**
 * 获取单个管理员信息
 * @param int $aid
 * @return array
 */
function getAdminInfo($aid){
	return M('admin_user')->where("user_id=$aid")->find();
} 
/**
 * 获取管理员的角色组ID，只能获取一个
 * @param int $aid
 * @return int
 */
function getGroupId($aid){
	return M('auth_group_access')->where("uid=$aid")->getField('group_id');
}
/**
 * 获取钻明业务员列表
 */
function getYeWuYuan(){
	$join[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
	$join[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan=1';
	return M("admin_user")->alias('au')->where('au.agent_id='.C('agent_id'))->join($join)->select();
}
/**
 * 验证管理员账户是否是分销商 
 * @param int $aid
 * @return boolean
 */
function getDistributor($aid){
	$groupID = getGroupId($aid);
	if($groupID == 7 || $groupID == 8) return true;
	else return false;
}
/**
 * 根据文章ID获取文章分类名称
 * @param int $cid
 * @return string
 */
function getCatenameByCateID($cid){
	return M('article_cat')->where("cid='$cid'")->getField("catname");
} 
/**
 * 获取产品材质
 */
function getGoodsMaterial($where){
	$GAM = M('goods_material');
	return $GAM->where($where)->select();
}
/**
 * 获取上级分销商ID
 */
function getParentTraderId($tid){
	$T = M('trader');
	return $T->where('tid = '.$tid.' and agent_id = '.C('agent_id'))->getField('parent_id');
}
/**
 * 获取分销商信息  
 * @trader_id
 * @return array
*/
function getTrader($trader_id){
	return M("trader")->where("tid='$trader_id' and agent_id = ".C('agent_id'))->find();
}
/**
 * 获取上级分销商的ID
 * @param int $tid
 * @return int
 */
function getTraderParentID($tid){
	return M("trader")->where("tid='$tid' and agent_id = ".C('agent_id'))->getField('pid');
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
 * 计算两个数的百分比
 * @param float $num
 * @param float $total
 */
function formatPercentage($num,$total){
	return formatPrice($num/$total*1000/10,2).'%';
}
/**
 *  后端处理导入数据格式化
 *  @param $string
 * 	@return string
 */
function formatString($string){
	if(strtoupper($string) == "G"){
		return "GD";
	}else{
		return $string;
	}
}

function formatStringMilky($string){
	if($string == ''){
		return "无奶";
	}else{
		return $string;
	}
}
function formatStringCoffee($string){
	if($string == ''){
		return "无咖";
	}else{
		return $string;
	}
}
function formatStringLocation($string){
	if(strtoupper($string) == "HK" || $string == ''){
		return "香港";
	}else if(strtoupper($string) == "INDIA"){
		return "印度";
	}else{
		return "国外";
	}
}