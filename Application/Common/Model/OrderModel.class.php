<?php
/**
 * 订单模型
 */
namespace Common\Model;

use Think\Model;
use Think\Log;

class OrderModel extends Model{
	
	public  $agent_id;

    public function __construct() {
		parent::__construct();
        $this -> agent_id = C('agent_id');
    }
	
	/**
	 * auth	：fangkai
	 * content：获取订单列表
	 * time	：2016-9-2
	**/
	public function getOrderList($where='1=1',$order=''){
		$orderPayList  = $this->where($where)->order($order)->select();
		if($orderPayList){
			return $orderPayList;
		}else{
			return false;
		}
	}

    /**
     * 根据订单ID获取订单
     *
     * @author Kwan Wong
     * @param $orderId
     * @return mixed
     */
    private function getOrder($orderId)
    {
        //获取订单相关信息
        $where = array(
            'agent_id' => C('agent_id'),
            'order_id' => $orderId,
        );
        $order = M('order')->where($where)->find();

        return $order;
	}

    /**
     * 根据订单号获取订单
     *
     * @author Kwan Wong
     * @param $orderSn
     * @return mixed
     */
    public function getOrderBySn($orderSn)
    {
        $where = array(
            'agent_id' => C('agent_id'),
            'order_sn' => $orderSn,
        );
        $order = M('order')->where($where)->find();

        return $order;
	}

    /**
     * 检查订单信息
     *
     * @author Kwan Wong
     * @param $orderId
     * @return bool
     */
    private function orderCheck($orderId)
    {
        $error = '';

        if($orderId < 0){
            $error = '无效的订单ID';
        }else{
            $order = $this->getOrder($orderId);
            if(!$order){
                $error = "初始化订单信息失败，订单不存在";
            }else{
                if($order['order_status'] < 0 || $order['order_status'] == '6'){
                    $error = "初始化订单信息失败，订单已取消或已完成，不需要支付";
                }else if($order['order_price_up'] <= 0 && $order['order_price'] <= 0){
                    $error = "初始化订单信息失败，订单价格非法";
                }
            }
        }

        if(!empty($error)){
            Log::write($error, 'ERR');
            return false;
        }

        return true;
	}

    /**
     * 获取订单详情
     *
     * @author Kwan Wong
     * @param $orderId
     * @return array|bool
     */
    public function getOrderDetail($orderId)
    {
    	$orderDetail = array();

    	if($this->orderCheck($orderId)){
            $order = $this->getOrder($orderId);

            if($order && $order['order_status'] >= 0 && $order['order_status'] != 6 && ($order['order_price_up'] >0 || $order['order_price'] > 0)){
                //商户订单号，商户网站订单系统中唯一订单号
                $orderDetail['merBillNo'] = $order['order_sn'];

                //付款金额
                $orderDetail['amount'] = $order['order_price_up']>0 ? $order['order_price_up'] : $order['order_price'];

                //下单日期
                $orderDetail['orderDate'] = date('Ymd', $order['create_time']);
            }
        }

        return $orderDetail;
	}

    public function CreateBanfangOrder($param){
        $OM = M('banfang_order');
        $OGM = M('banfang_order_goods');
        $CM = D('Common/Cart');
        $BCM = M('banfang_cart');
        $BG = M('banfang_goods');
        $return = array(
            'status'=>'0',
            'msg'=>'商品下架'
        );
        $date_now = date('Y-m-d H:i:s');
        $time_now = time();
        /*$param = array(
            'shop_id'=>$param['shop_id'],
            'uid'=>$param['uid'],
            'order_type_name'=>$param['order_type_name'],
            'note'=>$param['note'],
            'cart_id_arr'=>$param['cart_id_arr'],
            'lxr'=>$param['lxr'],
            'lxfs'=>$param['lxfs'],
        );*/

        if(!$param['uid']){
            $return['msg'] = '请先登录';
            return $return;
        }
        if(empty($param['cart_id_arr'])){
            $return['msg'] = '请先登录';
            return $return;
        }

        $cart_all = $CM->getBanfanCart($param);
        if(!$cart_all['count']){
            $return['msg'] = '请先选择商品';
            return $return;
        }
        $order_sn_biaoshi = date('Ymd');
        $OM    -> startTrans();
        $data['order_sn']        = $this->createOrderSn();
        $data['note']        = $param['note'];
        $data['uid']           = $param['uid'];
        $data['order_status']  = 0;
        $data['order_confirm'] = 0;
        $data['order_payment'] = 0;
        $data['delivery_mode'] = 0;
        $data['parent_id']     = getParentIDByUID(); // 对应业务员ID
        //$data['address_id']    = 0;
        //$data['address_info']  = D('Common/UserAddress')->getAddressInfo($data['address_id']);
        $data['order_price']   = $cart_all['cart_money_all'];
        $data['create_time']   = time();
        $data['dollar_huilv']  = C("dollar_huilv");
        $data['parent_type']   = 0; //订单所属分销商
        $data['agent_id']      = C('agent_id');
        $data['order_sn_biaoshi']  = $order_sn_biaoshi;
        $data['order_sn_zlf']  = $this->createOrderSn(1,$order_sn_biaoshi);

        $order_id = $OM->add($data);

        $order_goods = array();
        $cart_delete = false;
        $cart_delete_banfang = false;
        $cart_id = array();
        $order_number = 0;
        foreach($cart_all['lists'] as $value){
            $cart_id[] = $value['id'];
            foreach($value['banfang_carts_info']['lists'] as $banfang){

                $temp = array(
                    'order_id'=>$order_id,
                    'goods_id'=>$banfang['goods_id'],
                    'goods_number'=>$banfang['goods_number'],
                    'goods_price'=>$banfang['sub_info']['goods_price']*$banfang['goods_number'],
                    'attribute'=>serialize($banfang)
                );
                $order_number += $banfang['goods_number'];
                $order_goods[] = $temp;
            }
        }

        $data['deposit_price'] = $order_number*300;
        if($order_id){
            $bool = $OM->where(array('order_id'=>$order_id))->save($data);
        }
        $order_goods_add = $OGM->addAll($order_goods);

        if(!empty($cart_id)){
            $cart_delete = $CM->where(array('id'=>array('in',$cart_id)))->delete();
            $cart_delete_banfang = $BCM->where(array('cart_id'=>array('in',$cart_id)))->delete();
            $cart_delete = 1;
            $cart_delete_banfang = 1;
        }
        if($order_id && $order_goods_add && $cart_delete && $cart_delete_banfang){
            $OM -> commit();
            /*$param = array(
                        'shop_id'=>$param['shop_id'],
                        'uid'=>$param['uid'],
                        'order_type_name'=>$param['order_type_name'],
                        'note'=>$param['note'],
                        'cart_id_arr'=>$param['cart_id_arr'],
                        'lxr'=>$param['lxr'],
                        'lxfs'=>$param['lxfs'],
                    );*/

            //$ZLF_O = M('wsddh','zlf_','ZLF_DB');
            //$ZLF_OG = M('wsddb','zlf_','ZLF_DB');
            $zlf_order = M('banfang_order')->count();
            $zlf_order_id = $zlf_order+300;
            $zlf_order_id++;
            //$shop_info = M('banfang_shop')->where(array('shop_id'=>$param['shop_id']))->find();
            $user_info = M('user')->where(array('uid'=>$param['uid']))->find();
            $order_zlf = array(
                'DjLsh'=>$zlf_order_id,
                'ddhm'=>$data['order_sn_zlf'],
                'qwjhrq'=>date('Y-m-d H:i:s',$time_now+C('expected_delivery_time')*3600*24),
                'ddrq'=>date('Y-m-d H:i:s',$time_now),
                'ddlx'=>$param['order_type_name'],
                //'khbm'=>$shop_info['shop_code'],
                //'khmc'=>$shop_info['shop_address'] ? $shop_info['shop_address'] : $shop_info['shop_name'],
                'khbm'=>$param['shop_code'] ? $param['shop_code'] : '',
                'khmc'=>$param['shop_address'] ? $param['shop_address'] : '',
                'hjjs'=>$order_number,
                'hjjz'=>null,
                'hjje'=>$data['order_price'],
                'zdrid'=>$user_info['username'],
                'zdrm'=>trim($user_info['realname']),
                'zdsj'=>date('Y-m-d H:i:s',$time_now),
                'lxdh'=>$param['lxfs'],
                'lxr'=>$param['lxr'],
                'bzh'=>$param['note'],
                'WDflag'=>0,
            );
            /*foreach($order_zlf as $key => $valx){
                $zlf_order[$key] = $valx;
            }*/

            //$sql = "INSERT INTO zlf_wsddh (".implode(",",array_keys($order_zlf)).") VALUES ('".implode(",",array_values($order_zlf))."')";


            //$sql = "INSERT INTO zlf_wsddh (DjLsh, ddhm) VALUES ('Wilson', 'Champs-Elysees')";
            //$bool = $ZLF_O->query("INSERT INTO zlf_wsddh (DjLsh,ddhm) VALUES ('".$order_zlf['DjLsh']."','".$order_zlf['ddhm']."','".$order_zlf['qwjhrq']."')");

            $xxx_ma_max = array(
                0=>array(
                    '01','铂Pt950'
                ),
                1=>array(
                    '02','金Au750'
                ),
            );
            $order_goods_zlf = array();
            $temp_num_hh = 0;
            $arr_name = array('','女','男');

            foreach($order_goods as $value){
                $value['attribute'] = unserialize($value['attribute']);
                $temp_num_hh++;
                $temp = array(
                    'DjLsh'=>$zlf_order_id,
                    'hh'=>$temp_num_hh,
                    'gskh'=>$value['attribute']['sub_info']['goods_sn'],
                    'gg'=>$value['attribute']['sub_info']['factory_lists'][0]['hand_info'],
                    'js'=>$value['goods_number'],
                    'jdj'=>$value['attribute']['sub_info']['factory_lists'][0]['g_price_info']['material_unit_price'],
                    'nl'=>$arr_name[$value['attribute']['sub_info']['factory_lists'][0]['factory_info']['sex']],
                    'bzzsz'=>$value['attribute']['sub_info']['factory_lists'][0]['diamond_info']['carat'],
                    'zssl'=>intval($value['attribute']['sub_info']['diamond_main_number']),
                    'zszl'=>$value['attribute']['sub_info']['factory_lists'][0]['diamond_info']['carat'],
                    'bzfsz'=>$value['attribute']['sub_info']['factory_lists'][0]['factory_info']['fushi_to'],
                    'fssl'=>intval($value['attribute']['sub_info']['diamond_sub_number']),
                    'fszl'=>$value['attribute']['sub_info']['factory_lists'][0]['factory_info']['fushi_to'],
                    'bzjz'=>$value['attribute']['sub_info']['factory_lists'][0]['material_info']['weight'],
                    'bzje'=>$value['attribute']['sub_info']['factory_lists'][0]['g_price_info']['material_price'],
                    //'zje'=>$value['attribute']['sub_info']['factory_lists'][0]['g_price_info']['diamond_price'],
                    'zje'=>$value['attribute']['sub_info']['factory_lists'][0]['g_price_info']['material_price'],
                    'jd'=>$value['attribute']['sub_info']['factory_lists'][0]['clarity_info'],
                    'sd'=>$value['attribute']['sub_info']['factory_lists'][0]['color_info'],
                    'dlbm'=>$value['attribute']['sub_info']['banfang_category_info']['ca_code'],
                    'dlmc'=>$value['attribute']['sub_info']['banfang_category_info']['ca_name'],
                    'jsbm'=>$xxx_ma_max[$value['attribute']['sub_info']['factory_lists'][0]['material_info']['type']][0],
                    'jsmc'=>$xxx_ma_max[$value['attribute']['sub_info']['factory_lists'][0]['material_info']['type']][1].'钻石',
                    'plbm'=>$value['attribute']['sub_info']['banfang_jewelry_info']['ca_code'],
                    'plmc'=>$value['attribute']['sub_info']['banfang_jewelry_info']['ca_name'],
                    //'xlbm'=>$value['attribute']['sub_info']['banfang_big_series_info']['ca_code'],
                    'xlbm'=>'',
                    'xlmc'=>$value['attribute']['sub_info']['banfang_big_series_info']['ca_name'],
                    'csbm'=>$xxx_ma_max[$value['attribute']['sub_info']['factory_lists'][0]['material_info']['type']][0],
                    'csmc'=>$xxx_ma_max[$value['attribute']['sub_info']['factory_lists'][0]['material_info']['type']][1].'钻石',
                    //'zsdj'=>$value['attribute']['sub_info']['goods_price'],
                    'zsdj'=>$value['attribute']['sub_info']['factory_lists'][0]['g_price_info']['diamond_unit_price'],
                    'fsdj'=>0,
                    'bz'=>$value['attribute']['sub_info']['factory_lists'][0]['note_info'],
                    'WDflag'=>0,
                    'sffs'=>$value['attribute']['sub_info']['factory_lists'][0]['deputystone_opened_info']==1 ? 'Y' : 'N',
                );
                $sell_num = $BG->where(array('goods_id'=>$value['goods_id']))->getField('sell_num');
                $BG->where(array('goods_id'=>$value['goods_id']))->save(array('sell_num'=>$sell_num+$value['goods_number']));
                $order_goods_zlf[] = $temp;

            }

            /*$ZLF_O = M('wsddh','zlf_','ZLF_DB');
            $ZLF_OG = M('wsddb','zlf_','ZLF_DB');
            $bool1 = $ZLF_O->add($order_zlf);
            $bool2 = $ZLF_OG->addAll($order_goods_zlf);*/

            $logs = array('data'=>array('order'=>$order_zlf,'order_goods'=>$order_goods_zlf));
            $bool = M('banfang_log')->add(
                array(
                    'type'=>3,
                    'param_all'=>json_encode($logs),
                    'add_time'=>time()
                )
            );
            $data = $this->subErpOrder($logs);

            $return = array(
                'order_id'=>$order_id,
                'status'=>'100',
                'msg'=>'生成订单成功'
            );
        }else{
            $OM->rollback();
        }

        return $return;
    }

    public function createOrderSn($type=0,$order_sn_biaoshi){
        if($type==1){
            $OM = M('banfang_order');
            $order_sn = $OM->where(array('order_sn_biaoshi'=>$order_sn_biaoshi))->getField('count(order_sn_biaoshi)');
            $order_sn = date("Ymd").str_pad($order_sn,4,'0',STR_PAD_LEFT);

        }else{
            $order_sn = date("YmdHis").rand(10,99);
        }
        return $order_sn;
    }

    public function getBanfangOrderInfo($param){
        $order_id = $param['order_id'];
        $OM = M('banfang_order');
        $order_info = $OM->where(array('order_id'=>$order_id))->find();
        return $order_info;
    }


    public function subErpOrder($params,$url='http://zlfyun.btbzm.com/Home/Zlf/AddOrder.html'){

        $conditions = array(
            'url'=>$url,
            'data'=>$params,
            'not_json'=>1
        );
        $result = $this->httpsRequest($conditions);

        return $result;
    }

    protected function httpsRequest($param){
        $url = $param['url'];
        $data = $param['data'] ? $param['data'] : '';
        $not_json = $param['not_json'] ? 1 : 0;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        if($not_json==1){
            return $output;
        }
        return json_decode($output,true);

    }
}
?>
