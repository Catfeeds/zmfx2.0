<?php
use Think\Page;
// +----------------------------------------------------------------------
// | 手机端函数库，共有4个类型
// | 1。获取GET 2.设置 SET 3.判断检查IS 4公共处理类型 
// +----------------------------------------------------------------------
// | Author: adcbguo <adcbguo@qq.com>
// +----------------------------------------------------------------------
/**
 * 验证用户登陆<br>
 * 如果没登陆跳转，如果登陆会返回信息<br>
 */
function isUser(){
    if(!$_SESSION['m']['uid']){
        redirect(U('Public/login'));
    }else{
        return $_SESSION['m'];
    }
}

/**
 * 获取用户订单列表
 * @param array $where_item
 * @param int $p
 * @param int $size
 * @return array:
 */
function getUserOrderList($uid,$where_item=array(),$p=1,$size=10){
    $order = " create_time DESC";
    if(empty($where_item['certificate_no'])) {
        $orderM  = M('order');
        if (count($where_item) > 0) {
            $where_item += array('uid' => array('EQ', $uid), 'order_status' => array('EGT', '0'), 'agent_id' => array('EQ', C('agent_id')));
        }else{
            $where_item  = array('uid' => array('EQ', $uid), 'order_status' => array('EGT', '0'), 'agent_id' => array('EQ', C('agent_id')));
        }
        $count = $orderM -> where($where_item) -> count('order_id');
        $data  = $orderM -> where($where_item) -> page($p, $size)->order($order)->select();
    }else{
        if (count($where_item) > 0) {
            $where_item += array('uid' => array('EQ', $uid), 'order_status' => array('EGT', '0'), 'agent_id' => array('EQ', C('agent_id')));
        }else{
            $where_item  = array('uid' => array('EQ', $uid), 'order_status' => array('EGT', '0'), 'agent_id' => array('EQ', C('agent_id')));
        }
        $orderGoodsM   = M('order_goods');
        $order_id_item = $orderGoodsM -> join(' zm_order on zm_order_goods.order_id = zm_order.order_id ')->where($where_item)->field("group_concat(distinct zm_order.order_id) as order_id_item")->find();
        if(!empty($order_id_item['order_id_item'])){
            $where  = ' agent_id = '.C('agent_id').' and order_id in ('.$order_id_item['order_id_item'].') ';
            $orderM = M('order');
            $count  = $orderM -> where($where) -> count('order_id');
            $data   = $orderM -> where($where) -> page($p, $size)->order($order)->select();
        }
    }
    if(count($data)>0&&$data[0]['order_id']){
        $orderGoodsM           = M('order_goods');
        $orderDeliveryGoodsM   = M('order_delivery_goods');
        foreach($data as &$row) {
            $countOrderGoods           = $orderGoodsM         -> where('agent_id = '.C('agent_id').' and order_id = '.$row['order_id'])->count('order_id');
            $countOrderDelivery6Goods  = $orderDeliveryGoodsM -> where('agent_id = '.C('agent_id').' and order_id = '.$row['order_id'])->count('og_id');
            if($countOrderGoods       == $countOrderDelivery6Goods){
                $row['confirmReceive'] = 1;
            }else{
                $row['confirmReceive'] = 0;
            }
            $payment_price = 0;
            $temp          = M('order_receivables')->where("agent_id = '".C('agent_id')."' and order_id='$row[order_id]'")->order($order)->select();
            if($temp){
                foreach($temp as $val){
                    $payment_price += formatRound($val['payment_price'],2);
                }
            }
            $row['payment_price'] = $payment_price;
        }
    }
    return array(ceil($count/$size),$data);
}

function getPeriods($order_id){
    return array();
}

function getReceivables($order_id){

    return array();
}

//
/**
 * 获取用户订单明细信息
 * @param array $user
 * @param int $order_id
 * @return multitype:
 */
function getUserOrderInfo($uid,$order_id,&$obj){
    if($order_id == 0){
        return array();
    }
    $order = M('order') -> alias('o') -> where("o.agent_id = ".C('agent_id')." and o.uid='".$uid."' AND o.order_id ='".$order_id."'")->find();
    if(!$order){
        return array();
    }
    if(empty($_SESSION['web']['uid'])) {
        $tags                       = 1;
        $_SESSION['web']['uid']     = $uid;//为了绕过user的登录验证
    }
    
    $config = C('TMPL_PARSE_STRING'); //取出路径配置
    $_SESSION['is_inside_call'] = 1;
    $HomeUserController     = new \Home\Controller\UserController();
    
    C('TMPL_PARSE_STRING',$config);   //置换回mobile项目路径配置
    $obj->periods           = $HomeUserController->getPeriods($order_id);
    $obj->receivables       = $HomeUserController->getReceivables($order_id);
    $dataList               = M('order') -> JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id') -> where("zm_order.agent_id = ".C('agent_id')." and zm_order.order_id ='".$order_id."'")->select();
    //发货单列表
    $orderDeliverys         = $HomeUserController->getDelivery($order_id);
    $obj->orderDeliverys    = $orderDeliverys;
    //退货单列表
    $returnOrders           = $HomeUserController->getReturnGoods($order_id);
    $obj->returnOrders      = $returnOrders;
    //补差价
    $obj->orderReceivables  = $HomeUserController->getOrderReceivables($order_id);
    //退款记录
    $obj->returnFunds       = $HomeUserController->getOrderReturnFunds($order_id);
    //订单支付信息
    $payList = M('order_payment')->where("agent_id = '".C('agent_id')."' and order_id = '$order_id' AND uid='".$_SESSION['web']['uid']."' AND payment_status >1")->order("create_time DESC")->select();
    
    // 付款方式

    if($payList){
        $no_payment = $order['order_price_up'];
        foreach($payList as $key=>$val){
            $no_payment -= $val['payment_price'];
            if($no_payment <=0){
                $no_payment = 0;
            }
            $payList[$key]['no_payment'] = formatRound($no_payment,2);
        }
    }
    $obj->payList = $payList;
    
    $total_amount = 0;
    if($dataList){
        foreach($dataList as $key=>$val){
            $dataList[$key]['goods'] = $goods = unserialize($val['attribute']);
            $dataList[$key]['goods']['goods_type'] = $HomeUserController->getGoodsTypeByTid($goods['tid']);
            if($val['goods_type'] == 1){ // 1表示证书货   
                if($order['order_status'] <= 0){
                    $dataList['diamond_total_amount'] += $val['goods_price'];
                    $total_amount  += $val['goods_price'];
                    $dataList[$key]['diamond_price'] = $val['goods_price'];
                    $dataList[$key]['diamond_number'] = intval($val['goods_number']);
                }else if($order['order_status'] > 0){
                    $dataList['diamond_total_amount'] += $val['goods_price_up'];
                    $total_amount  += $val['goods_price_up'];
                    $dataList[$key]['diamond_price']  = $val['goods_price_up'];
                    $dataList[$key]['diamond_number'] = intval($val['goods_number_up']);
                }
                $diamond[] = $dataList[$key];
            }else if($val['goods_type'] == 2){ // 散货
                if($order['order_status'] <= 0){  //表示订单未确认
                    $dataList[$key]['goods_price']   = formatRound($dataList[$key]['goods_price']*(1+C('sanhuo_advantage')/100),2);
                    $dataList[$key]['sanhuo_price']  = $val['goods_price'];
                    $dataList[$key]['sanhuo_number'] = $val['goods_number'];
                    $dataList['sanhuo_total_amount'] += formatRound($val['goods_price'],2);
                    $total_amount  += formatRound($val['goods_price'],2);
                }else if($order['order_status'] > 0){
                    $dataList[$key]['sanhuo_price']   = $val['goods_price_up'];
                    $dataList[$key]['sanhuo_number']  = $val['goods_number_up'];
                    $dataList['sanhuo_total_amount'] += formatRound($val['goods_price_up'],2);
                    $total_amount  += formatRound($val['goods_price_up'],2);
                }
                $sanhuo[] = $dataList[$key];
            }else if($val['goods_type'] == 3 || $val['goods_type'] == 4){  // 成品
                if($order['order_status'] <= 0){
                    $dataList['product_total_amount'] += $val['goods_price'];
                    $total_amount += formatRound($val['goods_price'],2);
                    $total_amount = formatRound($total_amount,2);
                    //print_r($val);
                    $dataList[$key]['product_price']   = $val['goods_price'];
                    $dataList[$key]['product_number'] += intval($val['goods_number']);
                }else if($order['order_status'] >0){
                    $dataList['product_total_amount'] += $val['goods_price_up'];
                    $total_amount += $val['goods_price_up'];
                    $dataList[$key]['product_price']   = $val['goods_price_up'];
                    $dataList[$key]['product_number'] += intval($val['goods_number_up']);
                }
                if($goods['specification_id']>0){
                    //$dataList[$key]['specification']= $HomeUserController->getGoodsSpecificationById($val['goods_type'],$goods['specification_id'],'');
                }
                $product[] = $dataList[$key];
            }else if($val['goods_type'] == 5 || $val['goods_type'] == 6){ // 代销
                if($order['order_status'] <= 0){
                    $dataList['zmProduct_total_amount'] += $val['goods_price'];
                    $total_amount += formatRound($val['goods_price'],2);
                    $total_amount = formatRound($total_amount,2);
                    $dataList[$key]['product_price'] = $val['goods_price'];
                    $dataList[$key]['product_number'] = intval($val['goods_number']);
                }else if($order['order_status'] >0){
                    $dataList['zmProduct_total_amount'] += $val['goods_price_up'];
                    $total_amount += $val['goods_price_up'];
                    $dataList[$key]['product_price'] = $val['goods_price_up'];
                    $dataList[$key]['product_number'] = intval($val['goods_number_up']);
                }
                if($goods['specification_id']>0){
                    $dataList[$key]['specification']= $HomeUserController->getGoodsSpecificationById($val['goods_type'],$goods['specification_id'],'');
                }
                $zmProduct[] = $dataList[$key];
            }
        }
        $dataList['sanhuo_total_amount']    = formatRound($dataList['sanhuo_total_amount'],2);
        $dataList['product_total_amount']   = formatRound($dataList['product_total_amount'],2);
        $dataList['zmProduct_total_amount'] = formatRound($dataList['zmProduct_total_amount'],2);
        $obj->order        = $order;
        $obj->total_amount = formatRound($total_amount,2);
        $obj->order_id     = $order_id;
        $obj->diamond      = $diamond;
        $obj->sanhuo       = $sanhuo;
        $obj->product      = $product;
        $obj->zmProduct    = $zmProduct;
        $obj->dataList     = $dataList;
        $obj->active       = "orderList";
    }
    if($tags == 1 && $_SESSION['web']['uid']) {
        unset($_SESSION['web'],$tags,$_SESSION['is_inside_call']);
    }
    return array();
}


//获得订单已经支付金额
function getOrderPaidAmount($uid,$order_id){


    return 123.00;
}

//获得订单未支付金额
function getOrderNoPaidAmount($uid,$order_id){


    return 120.00;
}

//获取订单的付款周期
function getOrderPayPeriod($uid,$order_id){

    return array();
}

//获取订单的付款方式信息
function getOrderPayMethod($uid,$order_id){

    return array();
}

/**
 *设置确认订单确认收货
 **/
function setConfirmReceive($uid,$order_id){
    $data                      = M('order') -> JOIN('LEFT JOIN zm_order_goods ON  zm_order.order_id = zm_order_goods.order_id') -> where("zm_order.agent_id = '".C('agent_id')."' and zm_order.order_id ='".$order_id."' and zm_order.uid = '$uid' ")->select();
    if(count($data)>0&&$data[0]['order_id']){
        $orderGoodsM           = M('order_goods');
        $orderDeliveryGoodsM   = M('order_delivery_goods');
        foreach($data as &$row) {
            $countOrderGoods          = $orderGoodsM         -> where('zm_order.agent_id = '.C('agent_id').' and order_id = '.$row['order_id'])->count('order_id');
            $countOrderDelivery6Goods = $orderDeliveryGoodsM -> where('zm_order.agent_id = '.C('agent_id').' and order_id = '.$row['order_id'])->count('og_id');
            if($countOrderGoods    == $countOrderDelivery6Goods){
                M('order')          -> where('zm_order.agent_id = '.C('agent_id').' and order_id = '.$order_id.' and uid = '.$uid)->save(array('order_status'=>5));
                M('order_delivery') -> where(' zm_order.agent_id = '.C('agent_id').' and order_id = '.$order_id.' and uid = '.$uid)->save(array('confirm_type'=>2,'confirm_time'=>time()));
                return true;
            }else{
                return false;
            }
        }
    }
    return false;
}

/**
 * 获取当前域名的信息
 */
function getDomainTrader(){
    $info = M('agent')->where("agent_id = '".C('agent_id')."'")->find();
    if($info){
        if($info['parent_id']){ // 分销商
            $traderM             = M('trader');
            $tradeInfo           = $traderM->where(" t_agent_id = ".C('agent_id'))->find();
            $data['trader_type'] = $info['level'];
            $data['trader_id']   = $tradeInfo['tid'];
            $join  = 'zm_template_config AS tc ON t.template_id = tc.template_id';
            $field = 't.template_path';
            $traderInfo = M('template')->alias('t')->field($field)->join($join)->where('t.status = 1 and t.template_type = 2 and tc.agent_id = '.C('agent_id'))->find();
            if($traderInfo){
                $data['theme'] = $traderInfo['template_path'];
            }else{
                $data['theme'] = 'default';
            }
        }else{ // 钻明
            $data['trader_type'] = 1;
            $data['trader_id']   = 0;
            $data['theme']       = 'default';
        }
        return $data;
    }else{
        return false;
    }
}
/**
 * 获取文章列表
 * @param array $domain
 * @param number $catId
 * @param number $p
 * @param number $n
 * @return array
 */
function getArticle($domain,$catId=0,$p=1,$n=30){
    if($catId){$wCatId = ' ac.cid ='.$catId;}
    $A = M('article');
    $joinWhere = 'ac.belongs_id = '.$domain['trader_id'].' and ac.cid = a.cat_id and ac.agent_id = '.C('agent_id');
    $join[] = 'zm_article_cat AS ac ON '.$joinWhere;
    $list = $A->alias('a')->join($join)->where($wCatId)->page($p,$n)->order("addtime DESC")->select();
    return $list;
}

/**
 * 获取文章信息
 * @param int $aid
 */
function getArticleInfo($aid){
    $A = M('article');
    $info = $A->find($aid);
    return $info;
}

/**
 * 设置配置值
 * @param array $domain
 */
function setConfig($domain){
	$data = M('config')->field('agent_id = '.C('agent_id').' and config_key,config_value')->select();
   // if($domain['trader_type'] == 2){
		//获得分销商的配置
    //    $data1        = M('config_value')->where('agent_id = '.C('agent_id').' and parent_type = 1 and parent_id = '.$domain['trader_id'])->field('config_key,config_value')->select();
    //}else{
		//获取钻明客户配置
   //     $data1        = M('config_value')->where(' parent_type = 0 ')->field('config_key,config_value')->select();
   // }
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
 * 获取产品加点
 * @param array $domain
 * @param int   $goods_type 产品类型
 * @param int $type 加点类型1.采购加点2。销售加点
 */
function getAdvantage($domain,$goods_type=1,$type=2){
    if($goods_type == 1){
        $config_key = 'luozuan_advantage';
    }elseif ($goods_type == 2){
        $config_key = 'sanhuo_advantage';
    }else{
        $config_key = 'consignment_advantage';
    }
    $advantage = C($config_key);
    if($type == 2){//销售加点
        if($domain['trader_type'] == 2){
            return $advantage;
        }elseif ($domain['trader_type'] == 3){
            //获取上级加点
            $T = M('trader');
            $pid = $T->where('tid = '.$domain['trader_id'])->getField('parent_id');
            $C = M('config_value');
            $where = 'parent_type = 1 and parent_id= '.$pid." and config_key = '$config_key' and agent_id = ".C('agent_id');
            $consignment_advantage = $C->where($where)->getField('config_value');
            $advantage             = $advantage + $consignment_advantage;
            return $advantage;
        }else{
            return 0;
        }
    }else{//采购加点
        if ($domain['trader_type'] == 3){
            //获取上级加点
            $T = M('trader');
            $pid = $T->where('tid = '.$domain['trader_id'])->getField('pid');
            $C = M('config_value');
            $advantage = $C->where('parent_type = 1 and parent_id= '.$pid.' and agent_id = '.C('agent_id'))->getField($config_key);
            return $advantage;
        }else{
            return 0;
        }
    }
}

/**
 * 获取裸钻证书货
 * @param array $domain
 */
function getLuozuan($domain,$n=20,$p=1){
    $GL    = D('GoodsLuozuan');
    $count = $GL->where(' 1 = 1 ')->count();
    $Page = new Page($count,$n);
    $list = $GL->where(‘ 1 = 1 ’)->limit($Page->firstRow,$Page->listRows)->select();
    return $list;
}

/**
 * 获取单个裸钻信息
 * @param $goods_id
 * @return array
 */
function getLuozuanInfo($goods_id){
    $GL   = D('GoodsLuozuan');
    $info = $GL->(' goods_id = '.$goods_id)->find();
    return $info;
}

/**
 * 获取裸钻散货
 * @param array $domain
 */
function getSanhuo($domain,$n=20,$p=1){

}

/**
 * 获取所有的产品分类
 * @return array
 */
function getGoodsCat($domain){
    $GC = M('goods_category_config');
    $list = $GC->where('parent_type = '.$domain['trader_type'].' AND parent_id = '.$domain['trader_id'].' AND is_show=1'.' and agent_id = '.C('agent_id'))->select();
    $list = arrayRecursive($list, 'category_id', 'pid');
    return $list;
}

/**
 * 获取数组最后一个元素的值
 * @param $array
 * @return mixed
 */
function getArrayLastValue($array){
    return $array[count($array)-1];
}

/**
 * 获取产品列表,会获取代销货和自己的产品  重写
 * @param array $domain
 * @param number $n
 * @param number $p
 * @param int $category_id
 * @param string $goods_type 单个类型直接数字，多个类型用逗号间隔的字符串
 * @return array
 */
function getGoodsList($domain,$n=20,$p=1,$category_id,$goods_type=0){
    $G  = M('goods');
    $DG = M('goods');
    //分类筛选
    if(!is_array($category_id) and $category_id){
        $gJoinWhere = ' and g.category_id = '.$category_id.' and g.agent_id = '.C('agent_id');
        $dgJoinWhere = ' and gt.category_id = '.$category_id.' and gt.agent_id = '.C('agent_id');
    }
    if(is_array($category_id)){
        //搜索条件
        if($category_id['search']){
            $search = htmlspecialchars($category_id['search']);
            $gWhere = "goods_name LIKE '%$search%' ";
            $dgWhere = "goods_name LIKE '%$search%' ";
        }
    }
    //对应的左查询
    $gJoin[] = 'zm_goods_category AS gc ON gc.category_id = g.category_id'.$gJoinWhere;
    $dgJoin[] = 'zm_goods_trader AS gt ON gt.goods_id = dg.goods_id and gt.parent_type = '
        .$domain['trader_type'].' and gt.parent_id = '.$domain['trader_id'].$dgJoinWhere;
    //类型筛选
    if($goods_type){
        $goods_type = arrayClearEmpty(explode(',', $goods_type),0);
        $gWhere .= ' (';$dgWhere .= ' (';
        foreach ($goods_type as $key => $value) {
            if($key){
                $gWhere .= ' or g.goods_type = '.$value;
                $dgWhere .= ' or dg.goods_type = '.$value;
            }else{
                $gWhere .= 'g.goods_type = '.$value;
                $dgWhere .= 'dg.goods_type = '.$value;
            }
        }
        $gWhere .= ')';
        $dgWhere .= ')';
    }
    //合并数据分页，合并数据查询
    $M = M();
    $union1 = $DG->alias('dg')->join($dgJoin)->field('dg.*')->where($dgWhere)->buildSql();
    $union2 = $G->alias('g')->join($gJoin)->field('g.*')->where($gWhere)->buildSql();
    $countSql = 'SELECT count(*) AS count FROM ('.$union2.' UNION ALL '.$union1.') AS goods';
    $count = $M->query($countSql);
    $count = $count[0]['count'];
    $Page = new Page($count,$n);
    $data['page'] = $Page->show();
    $listSql = 'SELECT * FROM ('.$union2.' UNION ALL '.$union1.') AS goods ORDER BY goods_type limit '
        .$Page->firstRow.','.$Page->listRows;
    $list = $M->query($listSql);
    foreach ($list as $key => $value) {
        $list[$key]['thumb'] = ltrim($value['thumb'],'.');
        if($value['goods_type'] == 5 or $value['goods_type'] == 6){
            $advantage = getAdvantage($domain,$value['goods_type'],2);
            $price =  formatPrice($value['goods_price'] * $advantage/100);
        }
        $list[$key]['goods_price'] = formatPrice($value['goods_price'] + $price);
    }
    $data['list'] = $list;
    return $data;
}


/**
 * 递归去除数组里面键值为空的数据
 * @param array $array
 * @param int $isKey 是否保留数组建
 * @return array
 */
function arrayClearEmpty($array,$isKey=1){
    foreach ($array as $key => $value) {
        if (!empty($value)){
            if(is_array($value))$value = arrayClearEmpty($value);
            if($isKey) $data[$key] = $value;
            else $data[] = $value;
        }
    }
    return $data;
}

/**
 * 获取产品信息
 * @param int $goods_id
 * @return array
 */
function getGoodsInfo($goods_id,$type,$domain){
    $G = M('goods');
    $info = $G->find($goods_id);
    if($info['goods_type'] == 5 or $info['goods_type'] == 6){
        $advantage = getAdvantage($domain,$info['goods_type'],2);
        $price     = formatPrice($info['goods_price'] * $advantage/100);
    }
    $info['goods_price'] = formatPrice($info['goods_price']+ $price);
    return $info;
}

/**
 * 获取产品金工石数据<br>
 * 默认获取默认的值，可选择获取已有值
 * @param int $goods_id 产品id
 * @param int $type 产品类型
 * @param array $domain 域名数据
 * @param string $selMaterial 选中的材质
 * @param string $selDeputystone 选中的副石
 * @param string $selLuozuan 选中的主石
 */
function getGoodsJGS($goods_id,$type,$domain,$selMaterial='0',$selDeputystone='0',$selLuozuan='0'){
    //实例化对应的数据表
	$GAL = M('goods_associate_luozuan');
	$GAI = M('goods_associate_info');
	$GAD = M('goods_associate_deputystone');
    //获取全部材质和材质数据,给选中或默认的加选中状态，加可选的加可选状态
    $infoJoin[] = 'zm_goods_material AS gm ON gm.material_id = gai.material_id';
    $infoList = $GAI->alias('gai')->field('gai.*,gm.material_name')->where('goods_id = '.$goods_id)->join($infoJoin)->select();
    if(!$infoList){return false;}
    foreach ($infoList as $key => $value) {
        if($key == 0 and !$selMaterial){
            $value['css'] = 'active';
            $selMaterial = $value['material_id'];
            $weights = $value['weights_name'];
            $loss = $value['loss_name'];
            $basic = $value['basic_cost'];
            $material_id = $value['material_id'];
        }elseif ($selMaterial == $value['material_id']){
            $value['css'] = 'active';
            $selMaterial = $value['material_id'];
            $weights = $value['weights_name'];
            $loss = $value['loss_name'];
            $basic = $value['basic_cost'];
            $material_id = $value['material_id'];
        }else{
            $value['css'] = 'option';
        }
        $data['material'][] = $value;
    }
    //获取全部副石数据,给默认的和选中的加选中状态，给可选的加可选状态
    $deputystoneList = $GAD->where('goods_id = '.$goods_id)->select();
    if($deputystoneList){
        foreach ($deputystoneList as $key => $value) {
            if($key == 0 and !$selDeputystone){
                $value['css'] = 'active';
                $deputystonePrice = $value['deputystone_price'];
            }elseif ($selDeputystone == $value['gad_id']){
                $value['css'] = 'active';
                $deputystonePrice = $value['deputystone_price'];
            }else{
                $value['css'] = 'option';
            }
            $data['deputystone'][] = $value;
        }
    }
    //获取全部匹配主石数据，给默认的加选中
    $luozuanList = $GAL->where('goods_id = '.$goods_id.' and material_id = '.$selMaterial)->select();
    foreach ($luozuanList as $key => $value) {
        if($selLuozuan == $value['gal_id']){
            $value['css'] = 'active';
            $luozuanPrice = $value['price'];
            $is_active = 1;
        }else{
            $value['css'] = 'option';
        }
        $data['luozuan'][] = $value;
    }
    if(!$is_active){
        $data['luozuan'][0]['css'] = 'active';
        $luozuanPrice = $data['luozuan'][0]['price'];
    }
    //生成手寸可选数据
    for ($i = 9; $i < 23; $i++) {
        $arr['key'] = $arr['val'] = $i;
        $data['hand'][] = $arr;
    }
    //金工石产品价格
    $materialPrice = getMaterialPrice($material_id);
    $advantage     = getAdvantage($domain, $type, 2);
    $data['price'] = countJgsGoodsPrice($weights,$loss,$basic,$materialPrice,$luozuanPrice,$deputystonePrice,$advantage);
    //返回完整的金工石显示数据
    return $data;
}

/**
 * 根据材质获取材质的金价
 * @param int $material_id
 */
function getMaterialPrice($material_id){
    $GM = M('goods_material');
    $gold_price = $GM->where('material_id ='.$material_id)->getField('gold_price');
    return $gold_price;
}

/**
 * 计算金工石产品价格
 * @param double $weights 金重
 * @param int $loss 损耗
 * @param double $basic 基础工费
 * @param double $luozuanPrice 主石匹配工费
 * @param double $deputystonePrice 副石工费
 * @param int $advantage 加点
 */
function countJgsGoodsPrice($weights,$loss,$basic,$materialPrice,$luozuanPrice,$deputystonePrice,$advantage){
    $price = ($weights*(1+$loss/100)*$materialPrice+$basic+$luozuanPrice+$deputystonePrice);//产品价格
    if($advantage){
        return formatPrice($price*(1+$advantage/100));
    }else{
        return formatPrice($price);
    }
}

/**
 * 后端数据处理价格格式化
 * @param double $price
 * @return string
 */
function formatPrice($price){
    $price = round($price,2);
    return number_format($price,2,'.','');
}

/**
 * 处理规格数据
 * @param array $data
 */
function countGoodsSpec($data,$sel){
    foreach ($data as $key => $value) {
        $list['attribute_name'] = $value['attribute_name'];
        $arr['specification_id'] = $value['specification_id'];
        $arr['specification_name'] = $value['specification_name'];
        $arr['goods_number'] = $value['goods_specification_num'];
        $arr['goods_price'] = $value['goods_specification_price'];
        //加选中
        if($arr['specification_id'] == $sel){
            $arr['css'] = $is_spec= 'active';
            $list['goods_number'] = $value['goods_specification_num'];
            $list['goods_price'] = $value['goods_specification_price'];
        }else{
            $arr['css'] = 'option';
        }
        $list['data'][] = $arr;
    }
    if(!$is_spec){
        $list['data'][0]['css'] = 'active';
        $list['goods_number'] = $list['data'][0]['goods_number'];
        $list['goods_price'] = $list['data'][0]['goods_price'];
    }
    return $list;
}


/**
 * 获取产品规格数据 ,改成sku
 * @param int $goods_id
 */
 /*
function getGoodsSpec($goods_id,$type,$domain,$selSpec){
    //实例化对应的数据表
    if($type == 3 or $type == 4){
        $GAS = M(getTableName($domain,4));
    }else{
        $GAS = M('goods_associate_specification');
    }
    $selSpec = explode(',', $selSpec);
    $list    = $GAS->where('goods_id = '.$goods_id)->select();
    $list    = arrayRecursive($list, 'specification_id', 'pid');
    $res     = countGoodsSpec($list,$selSpec[0]);
    $listS['data'][0] = $res;
    $sel1 = $selSpec[0]?$selSpec[0]:$res['data'][0]['specification_id'];
    //2级规格
    if($list[$sel1]['sub']){
        $sel2 = $selSpec[1]?$selSpec[1]:$res['data'][0]['specification_id'];
        $res = countGoodsSpec($list[$sel1]['sub'],$sel2);
        $listS['data'][1] = $res;
    }
    //3级规格
    if($list[$sel1]['sub'][$sel2]['sub']){
        $sel3 = $selSpec[2]?$selSpec[2]:$res['data'][0]['specification_id'];
        $res = countGoodsSpec($list[$sel1]['sub'][$sel2]['sub'],$sel3);
        $listS['data'][2] = $res;
    }
    $listS['number'] = $res['goods_number'];
    $listS['price'] = $res['goods_price'];
    return $listS;
}*/

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
 * 获取产品的SKU
 * @param int $category_id 分类ID
 * @param int $goods_id 产品ID
 * @param string $sku_sn 选中的SKU编号
 * @
 */
function getGoodsSpec($goods_id,$selSkuSn,$domain){
	$G = M('goods');
	$info = $G->where('goods_id = '.$goods_id)->find();
	if(!$info){
		$this->error('没有这个产品');
	}
	$GS = M('goods_sku');
	$skuList = $GS->where('goods_id = '.$info['goods_id'])->select();
	$GA  = M('goods_attributes');
	$GCA = M('goods_category_attributes');
	$GAV = M('goods_attributes_value');
	$gacList = $GCA->where('category_id = '.$info['category_id'].' and type = 2 and agent_id = '.C('agent_id'))->select();
	$ids = parIn($gacList, 'attr_id');
	$list = $GA->where('attr_id in('.$ids.')')->select();
	$list = _arrayIdToKey($list);
	$attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
	foreach ($attrValueList as $key => $value) {
		$list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
	}
	//获取SKU规格信息
	if($skuList){
		foreach ($skuList as $key => $value) {
			//给SKU做选中
			if($selSkuSn and $skuList[$key]['sku_sn'] == $selSkuSn){
				$skuList[$key]['css'] = 'active';
				$res['price'] = $skuList[$key]['goods_price'];
			}elseif($key == 0 and !$selSkuSn){
				$skuList[$key]['css'] = 'active';
				$res['price'] = $skuList[$key]['goods_price'];
			}else{
				$skuList[$key]['css'] = 'option';
			}
			$attr = explode('^', $skuList[$key]['attributes']);
			foreach ($attr as $kk => $vv) {
				$active = explode(':', $vv);
				if($kk == 0){
					$var = '';
				}else{
					$var = '/';
				}
				if($list[$active[0]]['input_type'] == 2){
					$skuList[$key]['attr_name'] .= $var . $list[$active[0]]['sub'][$active[1]]['attr_value_name'];
				}elseif ($list[$active[0]]['input_type'] == 3){
					$skuList[$key]['attr_name'] .= $var . $active[1];
				}
			}
		}
		$res['data'] = $skuList;
		return $res;
	}else{
		return false;
	}
}

function getGoodsAttr($goods_id,$type,$domain){

    $GCA     = M('goods_category_attributes');
    $GA      = M('goods_attributes');
    $GAV     = M('goods_attributes_value');
    $gacList = $GCA->where('category_id = '.$goods_id.' and type = 2 and agent_id = '.C('agent_id'))->select();
    $ids     = parIn($gacList, 'attr_id');
    if($ids){
        $list          = $GA->where('attr_id in('.$ids.')')->select();
        $list          = _arrayIdToKey($list,'attr_id');
        $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
        $listS         = array();
        foreach($attrValueList as $row){
            $arr                   = array();
            $arr['attribute_name'] = $list[$row['attr_id']]['attr_name'];
            $listS[$row['attr_id']]['attribute_value'] .= $row['attr_value_name'].':';
        }
    }
    return $listS;
}

/**
 * 获取产品图片
 * @param int $goods_id
 * @return array
 */
function getGoodsImages($goods_id,$type,$domain){
    $GI = M('goods_images');
    $list = $GI->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->select();
    foreach ($list as $key => $value) {
        $list[$key]['small_path'] = ltrim($value['small_path'],'.');
        $list[$key]['big_path'] = ltrim($value['big_path'],'.');
    }
    return $list;
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

function paramStr($param){	//mysql in 查询字符串处理
    $arr = explode(',',$param);
    $str = '';
    $count = count($arr);
    for($i=0 ; $i<$count ; $i++){
        if($i==0){
            $str = "'".$arr[$i]."'";
        }else{
            $str .= ",'".$arr[$i]."'";
        }
    }
    return $str;
}