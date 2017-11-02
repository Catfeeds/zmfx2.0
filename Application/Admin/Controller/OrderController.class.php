<?php
/**
 * 订单模块
 * @author adcbguo
 */
namespace Admin\Controller;
use Think\Page;
class OrderController extends AdminController {

    // 模块跳转
    public function index(){
        foreach ($this->AuthListS AS $k){
            $val = stristr($k,strtolower(MODULE_NAME.'/'.CONTROLLER_NAME));
            if($val and strtolower($val) != strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME)){
               $this->redirect($val);
            }
        }
        $this->log('error', 'A1','',U('Admin/Public/login'));
    }

	/**
	 * 订单列表
	 * @param int $p
	 * @param int $n
	 */
	public function orderList($p=1,$n=13,$order_status=10){
		$O  = M('order');
		$AU = M('admin_user');
		$OG = M('order_goods');
		$where =  $wheres=' 1=1 and o.is_yiji_caigou < 1 and o.agent_id = '.C('agent_id');
		//下单时间
		if(I('get.create_time') or I('get.end_time')){
			$create_time = I('get.create_time');
			$this->time  = $create_time;
			$create_time = $create_time?$create_time:'2015-01-01';
			$create_time = strtotime($create_time);
			$end_time    = I('get.end_time');
			$this->end_time = $end_time;
			if($end_time) $end_time = strtotime($end_time)+86400;
			else $end_time = time();
			$where .=' AND ( o.create_time >='.$create_time.' and o.create_time <= '.$end_time.' )';
		}
		//订单对象
		if(I('get.userName')){
			$U = M('user');
			$T = M('trader');
			$name = trim(trim(I('get.userName')),'+');
			$name = addslashes($name);
			$uWhere = "(username like '%$name%' or realname like '%$name%') and agent_id = ".C('agent_id');
			$list1 = $U->where($uWhere)->field('uid')->select();
			$tWhere = "trader_name like '%$name%' and agent_id = ".C('agent_id');
			$list2 = $T->where($tWhere)->field('tid')->select();
			foreach ($list1 as $key => $value) {
				if($key == 0 and !empty($value)){
					$str1 = $value['uid'];
				}elseif ($key){
					$str1 .= ','.$value['uid'];
				}
			}
			if($str1)$where .= ' and (o.uid in ('.$str1.')';
			foreach ($list2 as $key => $value) {
				if($key == 0 and !empty($value)){
					$str2 = $value['tid'];
				}elseif ($key){
					$str2 .= ','.$value['tid'];
				}
			}
			if($str1) $jx = ' or ';
			else $jx = ' and (';
			if($str2) $where .= $jx.'o.tid in ('.$str2.') )';
			elseif ($str1)$where .= ')';
			$this->userName = I('get.userName');
		}
		//订单类型
		$this->order_type=I('get.order_type');
		if(I('get.order_type') == 1){
			$where .=' and o.is_erji_caigou = 0 ';
		}elseif(I('get.order_type') == 2){
			$where .=' and is_erji_caigou = 1 ';
		}
		//订单状态
		$this->order_status=$order_status;
		if($order_status == 0){
			$where .=' and o.order_status = 0';
		}else if($order_status == 1){
			$where .=' and o.order_status = 1 ';
		}else if($order_status == 2){
			$where .=' and o.order_status = 2 ';
		}else if($order_status == 3){
			$where .=' and o.order_status = 3 ';
		}else if($order_status == 4){
			$where .=' and o.order_status = 4 ';
		}else if($order_status == 5){
			$where .=' and o.order_status = 5 ';
		}else if($order_status == 6){
			$where .=' and o.order_status = 6 ';
		}elseif ($order_status == -1){
		    $where .=' and o.order_status = -1 ';
		}else{
		    //$where .=' and o.order_status > -1';
		}

		//订单编号
		if($_GET['order_sn']){
			$order_sn=addslashes(I('get.order_sn'));
			$this->order_sn = $order_sn;
			$where .= " and o.order_sn like '%$order_sn'";
		}
		//证书号查询
		$ogwhere = '';
		if(I('get.certificate_no')){
			$this -> certificate_no = I('get.certificate_no');
			$certificate_no = addslashes(I('get.certificate_no'));
			$ogwhere = " og.agent_id = ".C('agent_id')." and og.certificate_no like '%$certificate_no%'";
			$og      = $OG -> alias('og') -> where($ogwhere) -> field('og.order_id') -> select();
			$tempArr = array();
			if($og){
				foreach($og as $key=>$val){
					$tempArr[] = $val['order_id'];
				}
			}
			if($tempArr){
				$orderIds = implode(',',$tempArr);
				$where .=" and o.order_id in($orderIds)";
			}
		}

		//if(in_array('8', $this->group)){//2级分销商的订单
		//	$where .= ' and o.parent_type = 3 and o.parent_id = '.$this->tid;
		//}elseif (in_array('7', $this->group)){//1级分销商的订单
			// 订单类型
		//	if(I('post.parent_type')){//有选择类型查看
		//		$this->parent_type = I('post.parent_type');
		//	}
		//	$where .= ' and (o.parent_type = 2  and o.parent_id = '.$this->tid.')';
		//}else{//钻明可以查看的订单
			//业务员只可以看到自己的,其他可以看到全部
		//	if(in_array('3', $this->group)){
		//		$where .= ' and o.parent_type = 1 and o.parent_id = '.$this->uid;
		//	}else{
		//		$where .= ' and o.parent_type = 1';
		//	}
			//订单所属
		//	if(I('get.parent_id')){
		//		$this->parent_id = I('get.parent_id');
		//		$where .= ' and o.parent_id = '.I('get.parent_id');
		//	}

		if($this->is_yewuyuan){
			$where .= '  and o.parent_id = '.$this->uid;
		}else{
			if(I('get.parent_id')){
				$this   -> parent_id = I('get.parent_id');
				$where .=  ' and o.parent_id = '.I('get.parent_id');
			}
		}


		//查询出全部业务员

		$join_group[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
		$join_group[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan = 1';
		$this->businessList = $AU->alias('au')->where('au.agent_id='.C('agent_id'))->field('au.user_id,au.nickname')->join($join_group)->select();
		//字段和左查询
		//$field = 'o.*,u.realname,u.username,u.usernum,au.user_name,t.company_name, t.tid as trader_id';
		$field = 'o.*,u.realname,u.username,u.usernum,au.user_name';
		$join['user'] = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
		//$join['trader'] = 'LEFT JOIN `zm_trader` AS t ON t.t_agent_id = o.tid';
		if($ogwhere){
			$join['goods'] = 'LEFT JOIN `zm_order_goods` AS og ON og.order_id = o.order_id and ' . $ogwhere;
		}
		$join['admin_user'] = 'LEFT JOIN zm_admin_user AS au ON au.user_id = o.parent_id';
		//分页
		$count = $O->alias('o')->field($field)->where($where)->join($join)->count();
		$Page = new Page($count,$n);
		$this->page = $Page->show();
		//查询数据
		$list = $O->alias('o')->field($field)->where($where)->join($join)
		->order('create_time desc')->group('o.order_id')
		->limit($Page->firstRow.','.$Page->listRows)->select();
		//获取订单列表已付金额
		$list = $this->getOrderListReceivables($list);
		//处理订单相关信息
		if($where==$wheres){
			$this->orderList_null='';
		}else{
			$stopgap_list = $O->alias('o')->field($field)->where($where)->join($join)->order('create_time desc')->group('o.order_id')->select();
			if($stopgap_list){
				foreach($stopgap_list as $v){
					$orderList_ids[]=$v['order_id'];
				}
				$this->orderList_null=implode(',',$orderList_ids);
				unset($stopgap_list);
			}
		}
		$this->list = $this->countOrder($list);
		$this->agent_id = C("agent_id");

		$this->display();
	}

	/**
	 * 获取订单列表已付金额
	 * @param array $list
	 */
	protected function getOrderListReceivables($list){
		if(!$list){return false;}
		$OY = M('order_receivables');
		foreach ($list as $key => $value) {
			if(!$key){
				$in = $value['order_id'];
			}else{
				$in .= ','.$value['order_id'];
			}
		}
		$array = $OY->where('agent_id = '.C('agent_id').' and order_id in('.$in.') and payment_price > 0')->field('order_id,payment_price')->select();
		foreach ($array as $key => $value) {
			$arr[$value['order_id']]['payment_price'] += $value['payment_price'];
		}
		foreach ($list as $key => $value) {
			$list[$key]['payment_price'] = formatPrice($arr[$value['order_id']]['payment_price']);
		}
		return $list;
	}

	/**
	 * 订单详情页面
	 * @param int $order_id
	 * 需要有$this->group/$this->tid 管理员组和分销商id
	 */
	public function orderInfo($order_id){
		//查询订单信息
		$info = $this->getOrderInfo($order_id);
		if(!$info){ $this->error('当前订单不是您的或者订单错误！');}
		if($info['delivery_mode']){
			$info	 = D('Common/OrderPayment')->this_dispatching_way($info['delivery_mode'],$info);
		}			
		$O = M('order');
		$orderData['is_read'] = 1;
		$orderData['update_time'] = time();
		$O->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->save($orderData);
		//获取订单分期信息
		$this->periodList = $this->getOrderPeriodInfo($order_id);
		//订单详情数据
		$this->orderInfo = $this->countOrder($info);
		//订单产品数据
		$this->goodsList = $this->getOrderGoodsList($order_id);
		//统计订单产品价格
		$this->countGoodsPrice($this->goodsList,$info['order_status']);
		//获取订单应付信息
		$this->receivablesList = $this->GetOrderReceivables($order_id);
		//操作列表数据
		$this->actionList = $this->checkAction($info);
				 
		//收货地址
		if(empty($info['address_info'])){
			$info['address_info'] = D('Common/UserAddress')->getAddressInfo($info['address_id']);
		}
		
		//获取订单操作日志
		$this->orderLogList = $this->getOrderLogList($order_id);
		//操作标记
		if($info['mark']) {
			$this->orderMark = explode('<br/>',$info['mark']);
		}
		$this->parent_id = get_parent_id();
		$this->agent_id = C("agent_id");
		$this->display();
	}

    /**
     * 导出订单详情页
     * @param $order_id
     */
    public function orderExport($order_id)
    {
        //查询订单信息
        $orderInfo = $this->getOrderInfo($order_id);
        if($orderInfo['delivery_mode']){
            $orderInfo = D('Common/OrderPayment')->this_dispatching_way($orderInfo['delivery_mode'], $orderInfo);
        }

        //订单详情数据
        $orderInfo = $this->countOrder($orderInfo);

        //订单产品数据
        $goodsList = $this->getOrderGoodsList($order_id);

        if(!empty($goodsList)){
            ini_set('memory_limit','1024M');
            import("Org.Util.PHPExcel");

            $userName = !empty($_SESSION['admin']['UserName']) ? $_SESSION['admin']['UserName'] : '深圳钻明网络运营有限公司';
            $objPHPExcel=new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator($userName)
                ->setLastModifiedBy($userName)
                ->setTitle("Order Detail")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("Document for Office 2007 XLSX, generated using PHP classes.")
                ->setKeywords("Order Detail");
            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();
            $objRichText = new \PHPExcel_RichText();
            $objRichText->createText('');

            // 列宽
            $objActSheet->getColumnDimension('A')->setWidth(20);
            $objActSheet->getColumnDimension('B')->setWidth(20);
            $objActSheet->getColumnDimension('C')->setWidth(15);
            $objActSheet->getColumnDimension('D')->setWidth(15);
            $objActSheet->getColumnDimension('E')->setWidth(15);
            $objActSheet->getColumnDimension('F')->setWidth(20);
            $objActSheet->getColumnDimension('G')->setWidth(15);
            $objActSheet->getColumnDimension('H')->setWidth(20);
            $objActSheet->getColumnDimension('I')->setWidth(15);
            $objActSheet->getColumnDimension('J')->setWidth(50);
            $objActSheet->getColumnDimension('K')->setWidth(15);
            $objActSheet->getColumnDimension('L')->setWidth(15);
            $objActSheet->getColumnDimension('M')->setWidth(15);
            $objActSheet->getColumnDimension('N')->setWidth(15);
            $objActSheet->getColumnDimension('O')->setWidth(15);

            $objActSheet->getRowDimension(1)->setRowHeight(30);

            foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
                $objActSheet->getStyle($v.'2')->getAlignment()->setHorizontal("center");
                $objActSheet->getStyle($v.'2')->getAlignment()->setVertical('center');
                $objActSheet->getStyle($v.'2')->getFont()->setBold(true);
                $objActSheet->getStyle($v.'3')->getAlignment()->setHorizontal("center");
                $objActSheet->getStyle($v.'3')->getAlignment()->setVertical('center');
            }

            $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
            $objPHPExcel->getActiveSheet()->mergeCells('I1:O1');

            $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('I1:O1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A1:O1')->getFill()->getStartColor()->setARGB('FF8BA8AF');
            $objPHPExcel->getActiveSheet()->getStyle('A1:O1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getBorders()->getRight()->getColor()->setARGB('FF000000');

            $objActSheet->setCellValue('A1', mb_convert_encoding('订单信息', "UTF-8", "auto"));
            $objActSheet->setCellValue('I1', mb_convert_encoding('客户信息', "UTF-8", "auto"));
            $objActSheet->getStyle('A1')->getFont()->setBold(true);
            $objActSheet->getStyle('A1')->getAlignment()->setHorizontal("center");
            $objActSheet->getStyle('A1')->getAlignment()->setVertical('center');
            $objActSheet->getStyle('I1')->getFont()->setBold(true);
            $objActSheet->getStyle('I1')->getAlignment()->setHorizontal("center");
            $objActSheet->getStyle('I1')->getAlignment()->setVertical('center');

            //设置单元格的值
            $objPHPExcel->getActiveSheet()->getStyle('A2:O2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A2:O2')->getFill()->getStartColor()->setARGB('FFCAE8EA');
            $objPHPExcel->getActiveSheet()->getStyle('A2:O2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objActSheet->getRowDimension(2)->setRowHeight(20);

            $objPHPExcel->getActiveSheet()->mergeCells('J2:M2');

            $objActSheet->setCellValue('A2', mb_convert_encoding('订单编号', "UTF-8", "auto"));
            $objActSheet->setCellValue('B2', mb_convert_encoding('下单时间', "UTF-8", "auto"));
            $objActSheet->setCellValue('C2', mb_convert_encoding('所属业务员', "UTF-8", "auto"));
            $objActSheet->setCellValue('D2', mb_convert_encoding('配送方式', "UTF-8", "auto"));
            $objActSheet->setCellValue('E2', mb_convert_encoding('订单状态', "UTF-8", "auto"));
            $objActSheet->setCellValue('F2', mb_convert_encoding('发票信息', "UTF-8", "auto"));
            $objActSheet->setCellValue('G2', mb_convert_encoding('订单汇率', "UTF-8", "auto"));
            $objActSheet->setCellValue('H2', mb_convert_encoding('订单总价格(¥)', "UTF-8", "auto"));
            $objActSheet->setCellValue('I2', mb_convert_encoding('订单来源', "UTF-8", "auto"));
            $objActSheet->setCellValue('J2', mb_convert_encoding('收货信息', "UTF-8", "auto"));
            $objActSheet->setCellValue('N2', mb_convert_encoding('联系手机', "UTF-8", "auto"));
            $objActSheet->setCellValue('O2', mb_convert_encoding('邮编', "UTF-8", "auto"));

            $objActSheet->getRowDimension(3)->setRowHeight(20);
            $objPHPExcel->getActiveSheet()->mergeCells('J3:M3');
            $objActSheet->setCellValue('A3', mb_convert_encoding(' '.$orderInfo['order_sn'], "UTF-8", "auto"));
            $objActSheet->setCellValue('B3', mb_convert_encoding(date('Y/m/d H:i:s', $orderInfo['create_time']), "UTF-8", "auto"));
            $objActSheet->setCellValue('C3', mb_convert_encoding(!empty($orderInfo['parent_admin_name']) ? $orderInfo['parent_admin_name'] : '---', "UTF-8", "auto"));
            $objActSheet->setCellValue('D3', mb_convert_encoding($orderInfo['mode_name'], "UTF-8", "auto"));
            $objActSheet->setCellValue('E3', mb_convert_encoding($orderInfo['status'], "UTF-8", "auto"));
            $objActSheet->setCellValue('F3', mb_convert_encoding(!empty($orderInfo['invoice']) ? $orderInfo['invoice'] : '暂不开具发票', "UTF-8", "auto"));
            $objActSheet->setCellValue('G3', mb_convert_encoding($orderInfo['dollar_huilv'], "UTF-8", "auto"));
            $objActSheet->setCellValue('H3', mb_convert_encoding($orderInfo['order_price'], "UTF-8", "auto"));
            $objPHPExcel->getActiveSheet()->getStyle('H3')->getBorders()->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getStyle('H3')->getBorders()->getRight()->getColor()->setARGB('FF000000');
            $objActSheet->setCellValue('I3', mb_convert_encoding($orderInfo['obj'], "UTF-8", "auto"));
            $objActSheet->setCellValue('J3', mb_convert_encoding(!empty($orderInfo['address_info']) ? $orderInfo['address_info'] : '---', "UTF-8", "auto"));
            $objActSheet->setCellValue('N3', mb_convert_encoding(!empty($orderInfo['phone']) ? $orderInfo['phone'] : '---', "UTF-8", "auto"));
            $objActSheet->setCellValue('O3', mb_convert_encoding(!empty($orderInfo['code']) ? $orderInfo['code'] : '---', "UTF-8", "auto"));

            $index = 3;

            //证书货产品
            if(!empty($goodsList['luozuan']) && is_array($goodsList['luozuan'])){
                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$index.':O'.$index);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getFill()->getStartColor()->setARGB('FF8BA8AF');
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getBorders()->getRight()->getColor()->setARGB('FF000000');
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding('证书货', "UTF-8", "auto"));
                $objActSheet->getStyle('A'.$index)->getFont()->setBold(true);
                $objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("center");
                $objActSheet->getStyle('A'.$index)->getAlignment()->setVertical('center');
                $objActSheet->getRowDimension($index)->setRowHeight(30);

                // 证书货产品标题
                $index++;
                $objActSheet->getRowDimension($index)->setRowHeight(20);

                foreach(range('A', 'O') as $col){
                    $objActSheet->getStyle($col.$index)->getAlignment()->setHorizontal("center");
                    $objActSheet->getStyle($col.$index)->getAlignment()->setVertical('center');
                    $objActSheet->getStyle($col.$index)->getFont()->setBold(true);
                }

                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getFill()->getStartColor()->setARGB('FFCAE8EA');
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding('序号', "UTF-8", "auto"));
                $objActSheet->setCellValue('B'.$index, mb_convert_encoding('证书号', "UTF-8", "auto"));
                $objActSheet->setCellValue('C'.$index, mb_convert_encoding('原销售价(¥)', "UTF-8", "auto"));
                $objActSheet->setCellValue('D'.$index, mb_convert_encoding('单价(¥)', "UTF-8", "auto"));
                $objActSheet->setCellValue('E'.$index, mb_convert_encoding('国际报价($)', "UTF-8", "auto"));
                $objActSheet->setCellValue('F'.$index, mb_convert_encoding('原折扣', "UTF-8", "auto"));
                $objActSheet->setCellValue('G'.$index, mb_convert_encoding('折扣', "UTF-8", "auto"));
                $objActSheet->setCellValue('H'.$index, mb_convert_encoding('产品类型', "UTF-8", "auto"));
                $objActSheet->setCellValue('I'.$index, mb_convert_encoding('订购数量(颗)', "UTF-8", "auto"));
                $objActSheet->setCellValue('J'.$index, mb_convert_encoding('4C属性(重量/颜色/净度/切工)', "UTF-8", "auto"));
                $objActSheet->setCellValue('K'.$index, mb_convert_encoding('库存参考', "UTF-8", "auto"));
                $objActSheet->setCellValue('L'.$index, mb_convert_encoding('备注', "UTF-8", "auto"));
                $objActSheet->setCellValue('M'.$index, mb_convert_encoding('有货', "UTF-8", "auto"));
                $objActSheet->setCellValue('N'.$index, mb_convert_encoding('发货时间', "UTF-8", "auto"));
                $objActSheet->setCellValue('O'.$index, mb_convert_encoding('小计(¥)', "UTF-8", "auto"));

                $luozuanList = $goodsList['luozuan'];
                $luozuanTotal = 0;
                foreach($luozuanList as $luozuan){
                    $index++;
                    $objActSheet->getRowDimension($index)->setRowHeight(30);
                    foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
                        $objActSheet->getStyle($v.$index)->getAlignment()->setHorizontal("center");
                        $objActSheet->getStyle($v.$index)->getAlignment()->setVertical('center');
                    }
                    $objActSheet->setCellValue('A'.$index, mb_convert_encoding($luozuan['og_id'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('B'.$index, mb_convert_encoding($luozuan['attribute']['certificate_type'].' '.$luozuan['certificate_no'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('C'.$index, mb_convert_encoding($luozuan['goods_price'], "UTF-8", "auto"));

                    if($orderInfo['order_status'] == 0 or $orderInfo['order_status'] == -1 or $orderInfo['order_status'] == -2){
                        $goodsPrice = $luozuan['goods_price'];
                    }else{
                        $goodsPrice = $luozuan['goods_price_up'];
                    }

                    $objActSheet->setCellValue('D'.$index, mb_convert_encoding($goodsPrice, "UTF-8", "auto"));
                    $objActSheet->setCellValue('E'.$index, mb_convert_encoding($luozuan['attribute']['dia_global_price'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('F'.$index, mb_convert_encoding($luozuan['advantage'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('G'.$index, mb_convert_encoding($luozuan['xian_advantage'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('H'.$index, mb_convert_encoding($luozuan['attribute']['quxiang'], "UTF-8", "auto"));

                    if($orderInfo['order_status'] == 0 or $orderInfo['order_status'] == -1 or $orderInfo['order_status'] == -2){
                        $num = 1;
                    }else{
                        $num = $luozuan['goods_number_up'];
                    }

                    $objActSheet->setCellValue('I'.$index, mb_convert_encoding($num, "UTF-8", "auto"));

                    $goods_4c = '重量:'.$luozuan['attribute']['weight'].' ';
                    $goods_4c .= '颜色:'.$luozuan['attribute']['color'].' ';
                    $goods_4c .= '净度:'.$luozuan['attribute']['clarity'].' ';
                    $goods_4c .= '切工:'.$luozuan['attribute']['cut']."\n";
                    $goods_4c .= '抛光:'.$luozuan['attribute']['polish'].' ';
                    $goods_4c .= '对称:'.$luozuan['attribute']['symmetry'].' ';
                    $goods_4c .= '荧光:'.$luozuan['attribute']['fluor'].' ';
                    $goods_4c .= '奶咖色:'.$luozuan['attribute']['milk'].'/'.$luozuan['attribute']['coffee'];

                    $objPHPExcel->getActiveSheet()->getStyle('J'.$index)->getAlignment()->setWrapText(true);
                    $objActSheet->setCellValue('J'.$index, mb_convert_encoding($goods_4c, "UTF-8", "auto"));
                    $objActSheet->setCellValue('K'.$index, mb_convert_encoding($luozuan['goods_number_luozuan'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('L'.$index, mb_convert_encoding(!empty($luozuan['beizhu']) ? $luozuan['beizhu'] : '---', "UTF-8", "auto"));

                    if($luozuan['fahuo_time'] > 0){
                        if($luozuan['have_goods'] > 0){
                            $haveGoods = '有货';
                        }else{
                            $haveGoods = '无货';
                        }
                        $shippingTime = date('Y-m-d', $luozuan['fahuo_time']);
                    }else{
                        $haveGoods = '待确认';
                        $shippingTime = '待确认';
                    }

                    $objActSheet->setCellValue('M'.$index, mb_convert_encoding($haveGoods, "UTF-8", "auto"));
                    $objActSheet->setCellValue('N'.$index, mb_convert_encoding($shippingTime, "UTF-8", "auto"));
                    if($orderInfo['order_status'] ==0 || $orderInfo['order_status'] ==-1 || $orderInfo['order_status'] ==-2){
                        $goodsPriceTotal = $luozuan['goods_price'];
                    }else{
                        $goodsPriceTotal = $luozuan['goods_price_up'];
                    }
                    $objActSheet->setCellValue('O'.$index, mb_convert_encoding($goodsPriceTotal, "UTF-8", "auto"));

                    $luozuanTotal += $goodsPriceTotal;
                }

                $luozuanTotal = floor($luozuanTotal*100)/100;

                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$index.':O'.$index);
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding("合计(¥): ".$luozuanTotal.'  ', "UTF-8", "auto"));
                $objActSheet->getStyle('A'.$index)->getFont()->setBold(true);
                $objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("right");
                $objActSheet->getStyle('A'.$index)->getAlignment()->setVertical('center');
                $objActSheet->getRowDimension($index)->setRowHeight(30);

            }

            //散货产品
            if(!empty($goodsList['sanhuo']) && is_array($goodsList['sanhuo'])){
                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$index.':O'.$index);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getFill()->getStartColor()->setARGB('FF8BA8AF');
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getBorders()->getRight()->getColor()->setARGB('FF000000');
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding('散货', "UTF-8", "auto"));
                $objActSheet->getStyle('A'.$index)->getFont()->setBold(true);
                $objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("center");
                $objActSheet->getStyle('A'.$index)->getAlignment()->setVertical('center');
                $objActSheet->getRowDimension($index)->setRowHeight(30);

                // 散货产品标题
                $index++;
                $objActSheet->getRowDimension($index)->setRowHeight(20);

                foreach(range('A', 'O') as $col){
                    $objActSheet->getStyle($col.$index)->getAlignment()->setHorizontal("center");
                    $objActSheet->getStyle($col.$index)->getAlignment()->setVertical('center');
                    $objActSheet->getStyle($col.$index)->getFont()->setBold(true);
                }

                $objPHPExcel->getActiveSheet()->mergeCells('F'.$index.':L'.$index);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getFill()->getStartColor()->setARGB('FFCAE8EA');
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding('序号', "UTF-8", "auto"));
                $objActSheet->setCellValue('B'.$index, mb_convert_encoding('产品编号', "UTF-8", "auto"));
                $objActSheet->setCellValue('C'.$index, mb_convert_encoding('每卡单价(¥)', "UTF-8", "auto"));
                $objActSheet->setCellValue('D'.$index, mb_convert_encoding('订购重量(Ct)', "UTF-8", "auto"));
                $objActSheet->setCellValue('E'.$index, mb_convert_encoding('库存参考', "UTF-8", "auto"));
                $objActSheet->setCellValue('F'.$index, mb_convert_encoding('4C属性(颜色/净度/切工)', "UTF-8", "auto"));
                $objActSheet->setCellValue('M'.$index, mb_convert_encoding('有货', "UTF-8", "auto"));
                $objActSheet->setCellValue('N'.$index, mb_convert_encoding('发货时间', "UTF-8", "auto"));
                $objActSheet->setCellValue('O'.$index, mb_convert_encoding('小计(¥)', "UTF-8", "auto"));

                $sanhuoList = $goodsList['sanhuo'];
                $sanhuoTotal = 0;
                foreach($sanhuoList as $sanhuo){
                    $index++;
                    $objPHPExcel->getActiveSheet()->mergeCells('F'.$index.':L'.$index);
                    $objActSheet->getRowDimension($index)->setRowHeight(20);
                    foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
                        $objActSheet->getStyle($v.$index)->getAlignment()->setHorizontal("center");
                        $objActSheet->getStyle($v.$index)->getAlignment()->setVertical('center');
                    }
                    $objActSheet->setCellValue('A'.$index, mb_convert_encoding($sanhuo['og_id'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('B'.$index, mb_convert_encoding($sanhuo['attribute']['goods_sn'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('C'.$index, mb_convert_encoding($sanhuo['goods_price_sanhuo'], "UTF-8", "auto"));

                    $goodPrice = 0;
                    $goodWeight = 0;
                    if($orderInfo['order_status'] == 0 or $orderInfo['order_status'] == -1 or $orderInfo['order_status'] == -2){
                        $goodPrice = $sanhuo['goods_price'];
                        $goodWeight = $sanhuo['goods_number'];
                    }else{
                        $goodPrice = $sanhuo['goods_price_up'];
                        $goodWeight = $sanhuo['goods_number_up'];
                    }

                    $objActSheet->setCellValue('D'.$index, mb_convert_encoding($goodWeight, "UTF-8", "auto"));
                    $objActSheet->setCellValue('E'.$index, mb_convert_encoding($sanhuo['goods_number_sanhuo'], "UTF-8", "auto"));
                    $objActSheet->setCellValue('F'.$index, mb_convert_encoding($sanhuo['4c'], "UTF-8", "auto"));

                    if($luozuan['fahuo_time'] > 0){
                        if($luozuan['have_goods'] > 0){
                            $haveGoods = '有货';
                        }else{
                            $haveGoods = '无货';
                        }
                        $shippingTime = date('Y-m-d', $luozuan['fahuo_time']);
                    }else{
                        $haveGoods = '待确认';
                        $shippingTime = '待确认';
                    }

                    $objActSheet->setCellValue('M'.$index, mb_convert_encoding($haveGoods, "UTF-8", "auto"));
                    $objActSheet->setCellValue('N'.$index, mb_convert_encoding($shippingTime, "UTF-8", "auto"));
                    $objActSheet->setCellValue('O'.$index, mb_convert_encoding($goodPrice, "UTF-8", "auto"));

                    $sanhuoTotal += $goodPrice;
                }

                $sanhuoTotal = floor($sanhuoTotal*100)/100;

                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$index.':O'.$index);
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding("合计(¥): ".$sanhuoTotal.'  ', "UTF-8", "auto"));
                $objActSheet->getStyle('A'.$index)->getFont()->setBold(true);
                $objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("right");
                $objActSheet->getStyle('A'.$index)->getAlignment()->setVertical('center');
                $objActSheet->getRowDimension($index)->setRowHeight(30);
            }

            //代销货产品
            if(!empty($goodsList['consignment']) && is_array($goodsList['consignment'])){
                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$index.':O'.$index);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getFill()->getStartColor()->setARGB('FF8BA8AF');
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index)->getBorders()->getRight()->getColor()->setARGB('FF000000');
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding('代销货', "UTF-8", "auto"));
                $objActSheet->getStyle('A'.$index)->getFont()->setBold(true);
                $objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("center");
                $objActSheet->getStyle('A'.$index)->getAlignment()->setVertical('center');
                $objActSheet->getRowDimension($index)->setRowHeight(30);

                // 带销货产品标题
                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('B'.$index.':F'.$index);
                $objPHPExcel->getActiveSheet()->mergeCells('I'.$index.':L'.$index);
                $objActSheet->getRowDimension($index)->setRowHeight(20);

                foreach(range('A', 'O') as $col){
                    $objActSheet->getStyle($col.$index)->getAlignment()->setHorizontal("center");
                    $objActSheet->getStyle($col.$index)->getAlignment()->setVertical('center');
                    $objActSheet->getStyle($col.$index)->getFont()->setBold(true);
                }

                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getFill()->getStartColor()->setARGB('FFCAE8EA');
                $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding('产品编号', "UTF-8", "auto"));
                $objActSheet->setCellValue('B'.$index, mb_convert_encoding('产品名称', "UTF-8", "auto"));
                $objActSheet->setCellValue('G'.$index, mb_convert_encoding('数量(件)', "UTF-8", "auto"));
                $objActSheet->setCellValue('H'.$index, mb_convert_encoding('货品类型', "UTF-8", "auto"));
                $objActSheet->setCellValue('I'.$index, mb_convert_encoding('附加数据', "UTF-8", "auto"));
                $objActSheet->setCellValue('M'.$index, mb_convert_encoding('有货', "UTF-8", "auto"));
                $objActSheet->setCellValue('N'.$index, mb_convert_encoding('发货时间', "UTF-8", "auto"));
                $objActSheet->setCellValue('O'.$index, mb_convert_encoding('下单价格(¥)', "UTF-8", "auto"));

                //把值放进excel中
                $consignments = $goodsList['consignment'];
                $consignTotal = 0;
                foreach($consignments as $consign){
                    $index++;

                    $objPHPExcel->getActiveSheet()->mergeCells('B'.$index.':F'.$index);
                    $objPHPExcel->getActiveSheet()->mergeCells('I'.$index.':L'.$index);

                    $objPHPExcel->getActiveSheet()->getStyle('A'.$index.':O'.$index)->getAlignment()->setWrapText(true);

                    $objActSheet->getRowDimension($index)->setRowHeight(30);
                    foreach(array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O') as $k=>$v){
                        $objActSheet->getStyle($v.$index)->getAlignment()->setHorizontal("center");
                        $objActSheet->getStyle($v.$index)->getAlignment()->setVertical('center');
                    }
                    $objActSheet->setCellValue('A'.$index, mb_convert_encoding($consign['attribute']['goods_sn'].' ', "UTF-8", "auto"));
                    $objActSheet->setCellValue('B'.$index, mb_convert_encoding($consign['attribute']['goods_name'], "UTF-8", "auto"));

                    if($orderInfo['order_status'] == 0 or $orderInfo['order_status'] == -1 or $orderInfo['order_status'] == -2){
                        $goodsNumber = $consign['goods_number'];
                    }else{
                        $goodsNumber = $consign['goods_number_up'];
                    }

                    $objActSheet->setCellValue('G'.$index, mb_convert_encoding($goodsNumber, "UTF-8", "auto"));

                    $goodsType = '现货';
                    if($consign['attribute']['banfang_goods_id'] == true){
                        $goodsType = '代销';
                    }

                    $objActSheet->setCellValue('H'.$index, mb_convert_encoding($goodsType, "UTF-8", "auto"));

                    $goods_4c = html_entity_decode($consign['4c']);
                    $goods_4c = str_replace(["<br />", "<br/>", "<br>"], "  ", $goods_4c);
                    $goods_4c = str_replace("&lt;", "<", $goods_4c);
                    $goods_4c = str_replace("&gt;", ">", $goods_4c);
                    $goods_4c = str_replace("//", "\n", $goods_4c);

                    $objActSheet->getStyle('I'.$index)->getAlignment()->setWrapText(true);
                    $objActSheet->setCellValue('I'.$index, mb_convert_encoding($goods_4c, "UTF-8", "auto"));

                    if($consign['fahuo_time'] > 0){
                        if($consign['have_goods'] > 0){
                            $haveGoods = '有货';
                        }else{
                            $haveGoods = '无货';
                        }
                        $shippingTime = date('Y-m-d', $consign['fahuo_time']);
                    }else{
                        $haveGoods = '待确认';
                        $shippingTime = '待确认';
                    }
                    $objActSheet->setCellValue('M'.$index, mb_convert_encoding($haveGoods, "UTF-8", "auto"));
                    $objActSheet->setCellValue('N'.$index, mb_convert_encoding($shippingTime, "UTF-8", "auto"));

                    if($orderInfo['order_status'] == 0 or $orderInfo['order_status'] == -1 or $orderInfo['order_status'] == -2){
                        $goodsPrice = $consign['goods_price'];
                    }else{
                        $goodsPrice = $consign['goods_price_up'];
                    }
                    $objActSheet->setCellValue('O'.$index, mb_convert_encoding($goodsPrice, "UTF-8", "auto"));
                    $consignTotal += $goodsPrice;
                }

                $index++;
                $objPHPExcel->getActiveSheet()->mergeCells('A'.$index.':O'.$index);
                $objActSheet->setCellValue('A'.$index, mb_convert_encoding("合计(¥): ".$consignTotal.'  ', "UTF-8", "auto"));
                $objActSheet->getStyle('A'.$index)->getFont()->setBold(true);
                $objActSheet->getStyle('A'.$index)->getAlignment()->setHorizontal("right");
                $objActSheet->getStyle('A'.$index)->getAlignment()->setVertical('center');
                $objActSheet->getRowDimension($index)->setRowHeight(30);
            }

            $fileName = $orderInfo['order_sn'];
            $xlsTitle = "订单数据";
            ob_end_clean();
            header('pragma:public');
            header('Cache-Control: max-age=0');
            header('Content-type:application/vnd.ms-excel;name="'.$xlsTitle.'.xls"');
            header("Content-Disposition:attachment;filename=$fileName.xls");
            $PHPIF = new \PHPExcel_IOFactory();
            $objWriter = $PHPIF->createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
	}

	/**
	 * 确认后的取消订单
	 * @param int $order_id
	 */
	protected function quxiaoOrderAction($order_id){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'quxiaoOrderAction';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		//获取订单状态
		$O = M('order');
		$O->startTrans();
		$where = $this->buildWhere().' and order_id = '.$order_id;
		$info = $O->where($where)->find();
		if(!$info){
		    $this->error('没有当前订单！');
		}
		//订单状态为1，2，3才可以取消
		if(in_array($info['order_status'],array(1,2,3))){
		    //实例化一些表
		    $OG = M('order_goods');//订单产品表
		    $GL = D('GoodsLuozuan');//裸钻表
		    $GS = D('GoodsSanhuo');//散货表
		    $GSL = M('goods_sanhuo_lock');//分销商散货库存表
		    $G = M('goods');//珠宝产品表
		    $GSKU = M('goods_sku');//珠宝成品SKU库存表
		    $OP = M('order_payment');//收款记录表
		    $OR = M('order_receivables');//应收款表
		    $OC = M('order_compensate');//应退款表
		    $ZOR = M('order_refund');//退款表
		    $OP2 = M('order_period');//分期表
		    //库存恢复（1裸钻，2散货，3珠宝成品,4珠宝定制）
		    $list = $OG->where($where)->select();

		    foreach ($list as $key => $value) {
		    	if($value['goods_number_up']<1){
		    		$value['goods_number_up'] = $value['goods_number'];
		    	}

		        if($value['goods_type'] == 1 and $value['goods_number_up'] > 0){//证书货恢复库存
		           //if(in_array(7, $this->group)){$number = '-1';}
		           // elseif (in_array(7, $this->group)){ $number = '-2';}
		           // else{ $number = '1'; }
				    $number=1;
				    $is_owner_luozuan = $GL->where(" certificate_number = '".$value['certificate_no']."' and agent_id = ".C('agent_id'))->select();
				    if(!$is_owner_luozuan)continue;//不是自己的货不恢复库存

                    $res1 = $GL->where(" certificate_number = '".$value['certificate_no']."'")->setField('goods_number',$number);
                    if(!$res1){
		                $O->rollback();
		                $this->error('取消货品确认失败,证书货!'.$value['certificate_no'].'不能回复库存!');
		            }
		        }elseif ($value['goods_type'] == 2 and $value['goods_number_up'] > 0){//散货恢复库存
		            // if(in_array_diy('7,8', $this->group)){
		            //     $where1 = 'agent_id = '.C('agent_id').' and tid ='.$this->tid.' and goods_id = '.$value['goods_id'];
		            //     $number = $GSL->where($where1)->getField('goods_weight');
		            //     $number = $number + $value['goods_number_up'];
		            //     $res2 = $GSL->where($where1)->setField('goods_weight',$number);
		            // }else{
		            	$is_owner_sanhuo = $GS->where(" goods_sn = '".$value['certificate_no']."' and agent_id = ".C('agent_id'))->select();
				   		if(!$is_owner_sanhuo)continue;//不是自己的货不恢复库存

						$number = $GS->getOne('goods_weight',array('zm_goods_sanhuo.goods_sn'=>array('in',$value['certificate_no'])));
		                $number = $number + $value['goods_number_up'];
		                $res2   = $GS->where("agent_id = ".C('agent_id')." and goods_sn = '".$value['certificate_no']."'")->setField('goods_weight',$number);
		                if(!$res2){
			                $O->rollback();
			                $this->error('取消货品确认失败,散货'.$value['certificate_no'].'不能恢复库存!');
			            }
		            // }
		            
		        }elseif ($value['goods_type'] == 3  and $value['goods_number_up'] > 0){//珠宝产品恢复库存


		            $info = unserialize($value['attribute']);


		            $where3 = 'agent_id = '.C('agent_id').' and goods_id = '.$value['goods_id'];
		            $where4 = 'agent_id = '.C('agent_id').' and goods_id = '.$value['goods_id']." and sku_sn = '".$info['goods_sku']['sku_sn']."'";
		            $is_owner_consignment = $GSKU->where($where4)->select();
				   	if(!$is_owner_consignment)continue;//不是自己的货不恢复库存

		            $number1 = $G->where($where3)->getField('goods_number');
		            $number2 = $GSKU->where($where4)->getField('goods_number');
		            $number3 = $number1 + $value['goods_number_up'];
		            $number4 = $number2 + $value['goods_number_up'];
		            $res3 = $G->where($where3)->setField('goods_number',$number3);
		            $res4 = $GSKU->where($where4)->setField('goods_number',$number4);
		            if(!$res3 or !$res4){
		                $O->rollback();
		                $this->error('取消货品确认失败，珠宝成品'.$value['certificate_no'].'不能恢复库存!');
		            }

		        }

		    }
	        //改变订单状态
	        $data['order_status'] = 0;
	        $data['update_time'] = time();
	        $data['order_price_up'] = '0';
	        $res4 = $O->where($where)->save($data);
	        if(!$res4){
	            $O->rollback();
	            $this->error('取消货品确认失败，不能改变订单状态!');
	        }
	        //把所有的已审核收款记录改为未收款
	        $t = $OP->where($where.' and payment_status = 2')->count();
	        $res5 = $OP->where($where.' and payment_status = 2')->setField('payment_status',1);
	        if($t != $res5){
	            $O->rollback();
	            $this->error('取消货品确认失败，不能恢复收款审核!');
	        }
	        //散货应收款和和产品分期记录
	        $t = $OR->where($where)->count();
	        $res6 = $OR->where($where)->delete();
	        if($t != $res6){
	            $O->rollback();
	            $this->error('取消货品确认失败，不能删除应收款数据!');
	        }
	        $t = $OP2->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->count();
	        $res7 = $OP2->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->delete();
	        if($t != $res7){
	            $O->rollback();
	            $this->error('取消货品确认失败，不能删除分期数据!');
	        }
	        //应退款（只是退差价部分）
	        $t = $OC->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->count();
	        $res9 = $OC->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->delete();
	        if($t != $res9){
	            $O->rollback();
	            $this->error('取消货品确认失败，不能删除应退款数据!');
	        }
	        //退款记录的清理（只是退差价部分）
	        $t = $ZOR->where($where.' and refund_status = 2')->count();
	        $res10 = $ZOR->where($where.' and refund_status = 2')->setField('refund_status',1);
	        if($t != $res10){
	            $O->rollback();
	            $this->error('取消货品确认失败，退款记录状态改变失败!');
	        }
	        //恢复订单产品数量价格
	        $ogData['goods_number_up'] = '0';
	        $ogData['goods_price_up']  = '0';
	        $where = $where." and goods_number_up > 0 and goods_price_up > 0";
	        $t     = $OG->where($where)->count();
	        $res8  = $OG->where($where)->save($ogData);
	        if($t != $res8){
	            $O    -> rollback();
	            $this -> error('取消货品确认失败，订单产品不能恢复数据!');
	        }
	        $O    -> commit();
			$obj   = new \Common\Model\Sms();
			$info  = M("Order") -> where('order_id='.$order_id) -> find();
			if( $info['parent_id'] > 1 ){
				$phone = M("admin_user") -> where('user_id='.$info['parent_id']) -> getField('phone');
				$obj  -> sendSms($phone,'order_cancel_send_admin', C('agent_id'));
			}
			/*
			if( $info['uid'] ){
				$phone = M("User")  -> where('uid='.$info['uid']) -> getField('phone');
			} 
			*/
	        $notes = '操作了取消货品确认';
	        $this -> orderLog($order_id, $notes, '取消货品确认成功!');
		}else{
		    $this->error('当前订单已经不能取消确认了!');
		}
	}

	/**
	 * 获取裸钻销售折扣价格
	 * @param int $goods_sn
	 * @param double $discount
	 */
	public function getLuozuanDiscountPrice($goods_sn,$discount){
		$GL = D('GoodsLuozuan');
		$info = $GL->where('certificate_number = '.$goods_sn)->find();
		$dollar_huilv = C('DOLLAR_HUILV');
		//国际报价*汇率*重量*(折扣/100)
		$price = $info['dia_global_price']*$dollar_huilv*$info['weight']*($discount/100);
		$this->ajaxReturn(formatPrice($price));
	}

	/**
	 * 获取订单操作日志列表
	 * @param int $order_id
	 * @return array
	 */
	protected function getOrderLogList($order_id){
		$OL = M('order_log');
		$field = 'ol.ol_id,ol.create_time,ol.note,au.user_name,ag.title AS group_name';
		$join[] = 'LEFT JOIN zm_admin_user AS au ON au.user_id = ol.user_id';
		$join[] = 'LEFT JOIN zm_auth_group AS ag ON ag.id = ol.group_id';

		$list = $OL->alias('ol')->field($field)->where('ol.agent_id = '.C('agent_id').' and ol.order_id = '.$order_id)->join($join)->order('ol_id DESC')->select();
		return $list;
	}

	/**
	 * 获取单个订单详细信息,订单和采购单通用
	 * @param int $order_id
	 * @param int $type 默认1：订单2：采购单
	 * @return array
	 */
	protected function getOrderInfo($order_id,$type=1){
		$O = M('order');
		//组装管理组查询条件
		if($type==1){
			//if(in_array('7', $this->group)){//1级分销商
			//	$where = 'o.parent_type = 2 and o.parent_id = '.$this->tid.' and ';
			//}elseif (in_array('8', $this->group)){//2及分销商
			//	$where = 'o.parent_type = 3 and o.parent_id = '.$this->tid.' and ';
			//}elseif(in_array('3', $this->group)){
			//	$where = 'o.parent_type = 1 and o.parent_id = '.$this->uid.' and ';
			//}else{
			//	$where = 'o.parent_type = 1 and ';
			//}

			if($this->is_yewuyuan){
				$where = 'o.parent_id = '.$this->uid.' and o.agent_id = '.C('agent_id').' and ';
			}else{
				$where = 'o.agent_id = '.C('agent_id').' and ';
			}
		}else{
			//$where = 'o.tid = '.$this->tid.' and ';
			if( get_create_user_id() && get_parent_id() ) {
				$where = 'o.uid = ' . get_create_user_id() . ' and  o.agent_id = ' . get_parent_id() . ' and ';
			}
		}
		// 查询订单关联的数据
		$field = 'o.*, u.realname, u.username, t.trader_name, ua.phone, ua.code,';
		$field .='au.nickname as parent_admin_name,tu.trader_name as parent_trader_name';
		//获取订单对象
		$join['u'] = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
		$join['t'] = 'LEFT JOIN `zm_trader` AS t ON t.tid = o.tid';
		//获取订单所属
		$join['au'] = 'LEFT JOIN `zm_admin_user` AS au ON o.parent_id = au.user_id';
		$join['tu'] = 'LEFT JOIN `zm_trader` AS tu ON o.parent_id = tu.tid';
		//获取用户手机号及邮编
        $join['ua'] = 'LEFT JOIN `zm_user_address` AS ua ON o.address_id = ua.address_id';
		//查询数据
		$info = $O->alias('o')->where($where.' o.order_id = '.$order_id)
			->field($field)->join($join)->find();
		return $info;
	}

	/**
	 * 获取订单分期信息
	 * @param string $where
	 */
	protected function getOrderPeriodInfo($order_id, $is_caigoudan = 0){
		//获取订单的分期信息
		$O = M('order');
		$join = 'LEFT JOIN zm_order_period AS op ON o.order_id = op.order_id';
		if($is_caigoudan == 0){
			$periodList = $O->alias('o')->join($join)->field('op.*')->where('o.agent_id = '.C('agent_id').' and o.order_id = '.$order_id)->select();
		}else{
			$periodList = $O->alias('o')->join($join)->field('op.*')->where('o.agent_id = '.get_parent_id().' and o.order_id = '.$order_id)->select();
		}
		foreach ($periodList as $key => $value) {
			if($value['period_type'] == 1){
				$list['luozuan'] = $value;
			}elseif ($value['period_type'] == 2){//散货
				$list['sanhuo'] = $value;
			}elseif ($value['period_type'] == 3 or $value['period_type'] == 4){//成品和钻托
				$list['consignment'] = $value;
			}
		}
		return $list;
	}

	/**
	 * 采购单列表
	 * @param number $p
	 * @param number $n
	 */
	public function purchaseOrderList($p=1,$n=12,$order_status=10,$startTime='',$endTime=''){
		$agent_father   = get_parent_id();
		$create_user_id = get_create_user_id();


		$where = '';
		if(I('get.order_sn')){
			$this->order_sn=I('get.order_sn');
			$where .=' o.order_sn like \'%'.I('get.order_sn').'%\' and ';
		}
		//订单状态
		$this->order_status=$order_status;
		if($order_status == 0){
			$where .='o.order_status = 0 and ';
		}else if($order_status == 1){
			$where .='o.order_status = 1 and ';
		}else if($order_status == 2){
			$where .='o.order_status = 2 and ';
		}else if($order_status == 3){
			$where .='o.order_status = 3 and ';
		}else if($order_status == 4){
			$where .='o.order_status = 4 and ';
		}else if($order_status == 5){
			$where .='o.order_status = 5 and ';
		}else if($order_status == 6){
			$where .='o.order_status = 6 and ';
		}
		//组装时间条件
		$this->startTime = $startTime;
		$this->endTime = $endTime;
		if($startTime and $endTime){
			$ST=strtotime($startTime);
			$ET=strtotime("$endTime +1 day");
			$where .= 'o.create_time  >= '.$ST.' and o.create_time <= '.$ET.' and ';
		}

		if(!$agent_father){
			//进入1级别分销商的查询
			$where 		.= 'tid ='.C('agent_id');
			$count 		= M('order','zm_','ZMALL_DB')->alias('o')->where($where)->count();
			$Page  		= new Page($count,$n);
			$list 		= M('order','zm_','ZMALL_DB')->alias('o')->where($where)->order('create_time desc')->group('order_id')->limit($Page->firstRow.','.$Page->listRows)->select();
			$this->page = $Page->show();
			$this->list = $this->countOrder($list);
			$this->display();
			exit();
			
			
			/*
			$O = M('order');
			$where     .= ' o.is_yiji_caigou = 1 and o.agent_id = '.C('agent_id');
			$field      = 'o.*,u.realname,u.username';
			$join['u']  = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
			$join['og'] = '`zm_order_goods` AS og ON og.order_id = o.order_id';
			//分页
			$count = $O->alias('o')->where($where)->count();
			$Page = new Page($count,$n);
			$this->page = $Page->show();
			//查询数据
			$list = $O->alias('o')->field($field)->where($where)->join($join)->order('create_time desc')->group('o.order_id')
				->limit($Page->firstRow.','.$Page->listRows)->select();
			$this->list = $this->countOrder($list);
					//echo  M('order')->getLastSql();exit();
			$this->display();
			die;
			*/
		}
		$O = M('order');
		//if(in_array('8', $this->group)){//2级分销商的订单
		//	$where .= 'o.parent_type = 2 and o.tid = '.$this->tid.' and ';
		//}elseif (in_array('7', $this->group)){//1级分销商的订单
		//	$where .= 'o.parent_type = 1  and o.tid = '.$this->tid.' and ';
		//}
		//订单编号筛选条件




		/* 列表
		*/

		//字段和左查询
		$where .= ' o.uid='.$create_user_id.' and o.agent_id = '.$agent_father;
		$field = 'o.*,u.realname,u.username';//,t.trader_name';
		$join['u'] = 'LEFT JOIN `zm_user` AS u ON u.uid = o.uid';
		//$join['t'] = 'LEFT JOIN `zm_trader` AS t ON t.tid = o.tid';
		$join['og'] = '`zm_order_goods` AS og ON og.order_id = o.order_id';
		//分页
		$count = $O->alias('o')->where($where)->count();


		$Page = new Page($count,$n);
		$this->page = $Page->show();
		//查询数据
		$list = $O->alias('o')->field($field)->where($where)->join($join)->order('create_time desc')->group('o.order_id')
			->limit($Page->firstRow.','.$Page->listRows)->select();
 


		//计算订单数据（状态等）
		$this->list = $this->countOrder($list);


		$this->display();
	}

	/**
	 * 添加采购单
	 */
	protected function purchaseOrderAdd(){
		//订单归属
		// if(in_array(7, $this->group)){//一级分销商
		// 	$_POST['parent_id'] = 0;
		// 	$_POST['parent_type'] = 1;
		// }elseif(in_array(8, $this->group)){//二级分销商
		// 	$T = M('trader');
		// 	$pid = $T->where('tid = '.$this->tid.' and agent_id = '.C('agent_id'))->getField('parent_id');
		// 	$_POST['parent_id'] = $pid;
		// 	$_POST['parent_type'] = 2;
		// }
		$_POST['delivery_mode'] = 1;
		$_POST['tid'] = C('agent_id');//$this->tid;
		$OC = D('order_cart');
		//$array = $OC->where($this->buildWhere())->select();

		$where['agent_id'] = C('agent_id');
		$array1 = $OC->getOrderCartGoodsList($where);
		$array  = $OC->delNullGoods($array1);

		//1:裸钻2:散货5:代销货(定制)
		foreach ($array as $key => $value) {			
			if($value['goods_type'] == 1){//证书货
				$_POST['luozuan'][$value['id']]['certificate_number'] = $value['goods_sn'];
				$_POST['luozuan'][$value['id']]['goods_num'] = 1;
				$_POST['luozuan'][$value['id']]['goods_id'] = $value['goods_id'];
			}elseif ($value['goods_type'] == 2){//散货
				$_POST['sanhuo'][$value['id']]['goods_sn'] = $value['goods_sn'];
				$num = $_POST['sanhuo'][$value['id']]['goods_num'];
				if(!$num){
				    $_POST['sanhuo'][$value['id']]['goods_num'] = $value['goods_number'];
				}
				$_POST['sanhuo'][$value['id']]['goods_id'] = $value['goods_id'];
			}elseif ($value['goods_type'] == 3){//代销货（成品）
			    $_POST['consignment'][$value['id']]['goods_sn'] = $value['goods_sn'];
			    $num = $_POST['consignment'][$value['id']]['goods_num'];
			    if(!$num){
			        $_POST['consignment'][$value['id']]['goods_num'] = $value['goods_number'];
			    }
			    $_POST['consignment'][$value['id']]['cart_id'] = $value['id'];
			}elseif($value['goods_type'] == 4){//代销货（定制）
				$_POST['consignment'][$value['id']]['goods_sn'] = $value['goods_sn'];
				$num = $_POST['consignment'][$value['id']]['goods_num'];
				if(!$num){
				    $_POST['consignment'][$value['id']]['goods_num'] = $value['goods_number'];
				}
				$_POST['consignment'][$value['id']]['cart_id'] = $value['id'];
			}
		}
		//过滤掉无货的产品
		$ids = '0';
		foreach($_POST as $key => $value){
			if($key=='consignment' or $key=='sanhuo' ){
				foreach ($value as $k => $v){
					if($v['goods_sn'] == ''){
						unset($_POST[$key][$k]);
					}else{
						$ids = $ids.','.$k;
					}
				}
			}

			if($key == 'luozuan'){
				foreach ($value as $k => $v){
					if($v['certificate_number'] == ''){
						unset($_POST[$key][$k]);
					}else{
						$ids = $ids.','.$k;
					}
				}
			}			
		}
		if($ids == '0'){
			$this->log('error', '采购单是空的，或者商品均是无货状态！');
		}
		if($this->orderDataCaigou($_POST)){
			$OC->where($this->buildWhere() .' and id in('.$ids.')')->delete();
			$this->log('success', '采购单添加成功','',U('Admin/Order/purchaseOrderList'));
		}else {
			$this->log('error', '加入采购单失败！');
		}
	}

	/**
	 * 采购单详情
	 * @param int $order_id
	 */
	public function purchaseOrderInfo($order_id=''){

		if(IS_POST){
			//收货方确认收货
			if($_POST['confirmDelivery2']){
				$this->confirmDelivery2($order_id);
			}
			//添加采购单
			if($_POST['purchaseOrderAdd']){
				$this->purchaseOrderAdd();
			}
		}else{

			//查看分销商是否有会员ID			
			$create_user_id = get_create_user_id();			
			if(empty($create_user_id)){
				//$this->error('申请成分销商时，你不是通过在上级网站注册会员申请的！');
				$create_user_id = 0;
			}

			$agent_father = get_parent_id();
			if(empty($create_user_id)){
				$agent_father = 0;
			}
			//在上级中查看会员id是否存在
			$user_cunzai = M('user')->where('agent_id = '.$agent_father.' and uid='.$create_user_id)->select();
			if(!$user_cunzai){
				//$this->error('你在上级网站注册的会员删除掉了，请联系上级网站并重新注册！');
				$user_cunzai = 0;
			}


			if($order_id){
				$this->order_id = $order_id;
				//查询订单信息
				$info = $this->getOrderInfo($order_id,2);

				if($info){
					//$this->addressInfo = $this->getAddressInfo($info['address_id']);
					if(!$info['address_info']){
						$UA = M('user_address');
						$field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
								'r2.region_name as province,r3.region_name as city,r4.region_name as district';
						$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
						$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
						$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
						$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
						//$data = $UA->alias('ua')->where('ua.address_id = '.$info['address_id'].' and ua.agent_id = '.$agent_father)->field($field)
						$data = $UA->alias('ua')->where('ua.address_id = '.$info['address_id'])->field($field)
							->join($join1)->join($join2)->join($join3)->join($join4)->find();

						$info['address_info']  = $data['country'].' '.$data['province'].' '.$data['city'].' '.$data['district'].' '.$data['address'].' '.$data['name'].' '.$data['phone'].' '.$data['title'];
					}
					//计算订单详情数据
					$this->orderInfo = $this->countOrder($info);
					//下单后产品数据
					$this->goodsList = $this->getOrderPeriodGoodsList($order_id);
					//统计产品价格
					$this->countGoodsPrice($this->goodsList,$this->orderInfo['order_status']);
					//获取订单分期信息
					$this->periodList = $this->getOrderPeriodInfo($order_id, 1);
					//获取订单应付信息
					$this->receivablesList = $this->GetOrderReceivables($order_id, 1);
					//获取采购订单发货信息
					$this->purchaseOrderDeliveryList = $this->getPurchaseOrderDelivery($order_id, 1);
					//获取采购订单退货信息
					$this->purchaseOrderReturnList = $this->getPurchaseOrderReturn($order_id, 1);
					//获取付款信息
					$this->purchaseOrderPaymentList = $this->getPurchaseOrderPayment($order_id, 1);
					//获取退款信息
					$this->purchaseOrderRefundList = $this->getPurchaseOrderRefund($order_id,1);
					//地址
				}else{
					//判断是否为一级到官网的采购单。
					$ZMO		= M('order','zm_','ZMALL_DB');
					$info 		= $ZMO->where('order_id = '.$order_id)->find();
					if(!$info){
						$this->error('当前采购单不是您的或者采购单错误！');
					}
					if(!$info['address_info']){
						$UA    = M('user_address','zm_','ZMALL_DB');
						$field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
								'r2.region_name as province,r3.region_name as city,r4.region_name as district';
						$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
						$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
						$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
						$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
						//$data = $UA->alias('ua')->where('ua.address_id = '.$info['address_id'].' and ua.agent_id = '.$agent_father)->field($field)
						$data = $UA->alias('ua')->where('ua.address_id = '.$info['address_id'])->field($field)
							->join($join1)->join($join2)->join($join3)->join($join4)->find();

							
						$info['address_info']  = $data['country'].' '.$data['province'].' '.$data['city'].' '.$data['district'].' '.$data['address'].' '.$data['name'].' '.$data['phone'].' '.$data['title'];
					}
					//计算订单详情数据
					$this->orderInfo = $this->countOrder($info);
					$this->goodsList = $this->getZmOrderPeriodGoodsList($order_id);
					//统计产品价格
					$this->countGoodsPrice($this->goodsList,$this->orderInfo['order_status']);
					//获取订单的分期信息
					$join = 'LEFT JOIN zm_order_period AS op ON o.order_id = op.order_id';
					$periodList = $ZMO->alias('o')->join($join)->field('op.*')->where('o.order_id = '.$order_id)->select();
					foreach ($periodList as $key => $value) {
						if($value['period_type'] == 1){
							$list['luozuan'] = $value;
						}elseif ($value['period_type'] == 3){//成品和钻托
							$list['goods'] = $value;
						}elseif ($value['period_type'] == 2){//散货
							$list['sanhuo'] = $value;
						}elseif ($value['period_type'] == 5 or $value['period_type'] == 6){//订制和成品
							$list['consignment'] = $value;
						}
					}
					$this->periodList =$list;
					//获取订单应付信息
					$OC		= M('order_compensate','zm_','ZMALL_DB');
					$list2 = $OC->where('order_id = '.$order_id.' and period_type in(12)')->order('create_time')->select();
					foreach ($list2 as $key => $value) {
						if($value['period_type'] == 12){//退差价
							$arr['consignment'][] = $value;
						}
					}
					//订单应付款信息
					$OY = M('order_receivables');
					$order_receivableslist = $OY->where('order_id = '.$order_id.' and period_type in(1,2,3,5,12)')->order('period_current')->select();
					foreach ($order_receivableslist as $key => $value) {
						if($value['period_type'] == 1){
							$arr['luozuan'][] = $value;
						}elseif ($value['period_type'] == 2){//散货
							$arr['sanhuo'][] = $value;
						}elseif ($value['period_type'] == 3 or $value['period_type'] == 4){//成品和钻托
							$arr['goods'][] = $value;
						}elseif ($value['period_type'] == 5 or $value['period_type'] == 6){//代销货
							$arr['consignment'][] = $value;
						}elseif($value['period_type'] == 12){//补差价
							$arr['consignment'][] = $value;
						}
					}
					$this->receivablesList = $arr;
					//获取采购订单发货信息
					$where = 'order_id = '.$order_id;
					$OD    = M('order_delivery','zm_','ZMALL_DB');			//发货单
					$ODG   = M('order_delivery_goods','zm_','ZMALL_DB');  //发货单产品信息
					$OR    = M('order_return','zm_','ZMALL_DB');			//退货单
					$ORG   = M('order_return_goods','zm_','ZMALL_DB');	//退货单产品信息
					//查询订单发货单信息
					$ORGlist = $OD->where($where)->select();
					//查询采购单的发货单的产品信息
					foreach ($ORGlist as $key => $value) {
						if($key){
							$ids1 .= ','.$value['delivery_id'];
						}else{
							$ids1 = $value['delivery_id'];
						}
					}
					$ORGlist = $this->_arrayIdToKey($ORGlist);
					if($ids1){
						//查询采购单的发货单的产品信息
						$join = 'zm_order_goods AS og ON og.og_id = odg.og_id';
						$list1 = $ODG->alias('odg')->where('delivery_id in ('.$ids1.')')->join($join)->field('odg.*,og.certificate_no')->select();
						$list1 = $this->_arrayIdToKey($list1);
						//查询采购单的退货单的产品信息
						$join2 = 'zm_order_return AS zor ON zor.status <> 0 and zor.return_id = org.return_id';
						$list2 = $ORG->alias('org')->where('org.delivery_id in ('.$ids1.')')->join($join2)->field('org.*')->select();
						foreach ($list2 as $key => $value) {
							$list1[$value['dg_id']]['return'][] = $value;
						}
						//处理数据
						foreach ($list1 as $key => $value) {
							//检查发货单退货信息
							foreach ($value['return'] as $k => $v) {
								$value['return_number'] += $v['goods_number'];
							}
							//全部退货
							$ORGlist[$value['delivery_id']]['deliveryWhole'] = '1';
							if($value['goods_number'] != $value['return_number']){
								$ORGlist[$value['delivery_id']]['deliveryWhole'] = 0;
							}
							if($value['goods_type'] == 1){
								$value['goods_number'] = intval($value['goods_number']).'颗';
								if($value['return_number'])$value['return_number'] = '已退货';
							}elseif ($value['goods_type'] == 2){
								if($value['return_number'] and round($value['goods_number'],3) == round($value['return_number'],3)){
									$value['return_number'] = '已退货';
								}elseif ($value['return_number']){
									$value['return_number'] = '退货'.number_format($value['return_number'],3,'.','').'卡';
								}
							}elseif ($value['goods_type'] == 5){
								$value['goods_number'] = intval($value['goods_number']).'件';
								if($value['return_number'])$value['return_number'] = '退货'.$value['return_number'].'件';
							}
							$ORGlist[$value['delivery_id']]['delivery'][] = $value;
						}
					
					}
					//没有发货单信息直接返回空
					$this->purchaseOrderDeliveryList = $ORGlist;
					
					
					$OR  = M('order_return','zm_','ZMALL_DB');		//退货单
					$ORG = M('order_return_goods','zm_','ZMALL_DB'); //退货单产品信息
					//查询订单退货单信息
					$OREGlist = $OR->where($where)->select();
					//查询采购单的退货单的产品信息
					foreach ($OREGlist as $key => $value) {
						if($key){
							$ids1 .= ','.$value['delivery_id'];
						}else{
							$ids1 = $value['delivery_id'];
						}
					}
					//没有退货单信息直接返回空
					if($ids1){
						$OREGlist = $this->_arrayIdToKey($OREGlist);
						$join = 'zm_order_goods AS og ON og.og_id = org.og_id';
						$list1 = $ORG->alias('org')->where('delivery_id in ('.$ids1.')')->join($join)->field('org.*,og.certificate_no')->select();
						//处理数据
						foreach ($list1 as $key => $value) {
							if($value['goods_type'] == 1){
								$value['goods_number'] = intval($value['goods_number']).'颗';
							}elseif ($value['goods_type'] == 2){
							}elseif ($value['goods_type'] == 5){
								$value['goods_number'] = intval($value['goods_number']).'件';
							}
							$OREGlist[$value['return_id']]['return'][] = $value;
						}
					}
				//获取采购订单退货信息
				$this->purchaseOrderReturnList  = $OREGlist;
				//获取付款信息
				$this->purchaseOrderPaymentList = M('order_payment','zm_','ZMALL_DB')->where($where)->select();
				//获取付款信息
				$this->purchaseOrderRefundList  = M('order_refund','zm_','ZMALL_DB')->where($where)->select();
				}
			}else{
				//下单前数据
				$goodsList = $this->getOrderCartGoodsList();
				//采购单无产品
				if(!count($goodsList)){
					$this->error('请先添加产品到采购单!',U('Admin/Order/orderList'));
				}else{
					$this->goodsList = $goodsList;
				}

				$UA = M('user_address');
				$field = 'ua.*,r1.region_name as country,r2.region_name as province,r3.region_name as city,r4.region_name as district';
				$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
				$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
				$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
				$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
				$addressList = $UA->alias('ua')->join($join1)->join($join2)->join($join3)->join($join4)->where('uid = '.$create_user_id)->field($field)->select();
				$this->addressList = $addressList ;
			}
 
			$this->display();
		}
	}

	/**
	 * 获取退款信息
	 * @param int $order_id
	 */
	protected function getPurchaseOrderRefund($order_id, $is_caigoudan=0){
		if($is_caigoudan == 0){
			$where = 'agent_id = '.C('agent_id').' and order_id = '.$order_id;
		}else{
			if( get_create_user_id() && get_parent_id() ) {
				$where = 'agent_id = ' . get_parent_id() . ' and uid=' . get_create_user_id() . ' and order_id = ' . $order_id;
			}else{
				$where = 'agent_id = ' . C('agent_id') . ' and order_id = ' . $order_id;
			}
		}

	    $OR = M('order_refund');
	    $list = $OR->where($where)->select();
	    return $list;
	}

	/**
	 * 获取付款信息
	 * @param int $order_id
	 */
	protected function getPurchaseOrderPayment($order_id, $is_caigoudan=0){
		if($is_caigoudan == 0){
			$where = 'agent_id = '.C('agent_id').' and order_id = '.$order_id;
		}else{
			if( get_create_user_id() && get_parent_id() ) {
				$where = 'agent_id = ' . get_parent_id() . ' and uid=' . get_create_user_id() . ' and order_id = ' . $order_id;
			}else{
				$where = 'agent_id = ' . C('agent_id') . ' and order_id = ' . $order_id;
			}
		}

	    $OP = M('order_payment');
	    $list = $OP->where($where)->select();
	    return $list;
	}

	/**
	 * 获取采购订单退货货品信息
	 * @param int $order_id
	 */
	protected function getPurchaseOrderReturn($order_id, $is_caigoudan=0){
		if($is_caigoudan == 0){
			$where = 'agent_id = '.C('agent_id').' and order_id = '.$order_id;
		}else{
			if( get_create_user_id() && get_parent_id() ) {
				$where = 'agent_id = ' . get_parent_id() . ' and uid=' . get_create_user_id() . ' and order_id = ' . $order_id;
			}else{
				$where = 'agent_id = ' . C('agent_id') . ' and order_id = ' . $order_id;
			}
		}

	    $OR = M('order_return');//退货单
	    $ORG = M('order_return_goods');//退货单产品信息
	    //查询订单退货单信息
	    $list = $OR->where($where)->select();
	    //查询采购单的退货单的产品信息
	    foreach ($list as $key => $value) {
	        if($key){
	            $ids1 .= ','.$value['delivery_id'];
	        }else{
	            $ids1 = $value['delivery_id'];
	        }
	    }
	    //没有退货单信息直接返回空
	    if(!$ids1){return;}
	    $list = $this->_arrayIdToKey($list);
	    $join = 'zm_order_goods AS og ON og.og_id = org.og_id';
	    if($is_caigoudan == 0){
	    	$list1 = $ORG->alias('org')->where('org.agent_id = '.C('agent_id').' and delivery_id in ('.$ids1.')')->join($join)->field('org.*,og.certificate_no')->select();
	    }else{
	    	$list1 = $ORG->alias('org')->where('org.agent_id = '.get_parent_id().' and delivery_id in ('.$ids1.')')->join($join)->field('org.*,og.certificate_no')->select();
	    }
	    //处理数据
	    foreach ($list1 as $key => $value) {
	        if($value['goods_type'] == 1){
	            $value['goods_number'] = intval($value['goods_number']).'颗';
	        }elseif ($value['goods_type'] == 2){
	        }elseif ($value['goods_type'] == 5){
	            $value['goods_number'] = intval($value['goods_number']).'件';
	        }
	        $list[$value['return_id']]['return'][] = $value;
	    }
	    return $list;
	}

	/**
	 * 获取采购订单发货货品信息
	 * @param int $order_id
	 */
	protected function getPurchaseOrderDelivery($order_id, $is_caigoudan=0){
		if($is_caigoudan==0){
			$where = 'agent_id = '.C('agent_id').' and order_id = '.$order_id;
		}else{
			if( get_create_user_id() && get_parent_id() ) {
				$where = 'agent_id = ' . get_parent_id() . ' and uid=' . get_create_user_id() . ' and order_id = ' . $order_id;
			} else {
				$where = 'order_id = ' . $order_id;
			}
		}

	    $OD = M('order_delivery');//发货单
        $ODG = M('order_delivery_goods');//发货单产品信息
        $OR = M('order_return');//退货单
        $ORG = M('order_return_goods');//退货单产品信息
        //查询订单发货单信息
	    $list = $OD->where($where)->select();

	    //查询采购单的发货单的产品信息
	    foreach ($list as $key => $value) {
	        if($key){
	            $ids1 .= ','.$value['delivery_id'];
	        }else{
	            $ids1 = $value['delivery_id'];
	        }
	    }
	    $list = $this->_arrayIdToKey($list);
	    //没有发货单信息直接返回空
	    if(!$ids1){return;}
	    //查询采购单的发货单的产品信息
	    $join = 'zm_order_goods AS og ON og.og_id = odg.og_id';
	    if($is_caigoudan==0){
	    	$list1 = $ODG->alias('odg')->where('odg.agent_id = '.C('agent_id').' and delivery_id in ('.$ids1.')')->join($join)->field('odg.*,og.certificate_no')->select();
		}else{
			$list1 = $ODG->alias('odg')->where('odg.agent_id = '.get_parent_id().' and delivery_id in ('.$ids1.')')->join($join)->field('odg.*,og.certificate_no')->select();
		}

	    $list1 = $this->_arrayIdToKey($list1);

	    //查询采购单的退货单的产品信息
	    $join2 = 'zm_order_return AS zor ON zor.status <> 0 and zor.return_id = org.return_id';
	    $list2 = $ORG->alias('org')->where('org.agent_id = '.C('agent_id').' and org.delivery_id in ('.$ids1.')')->join($join2)->field('org.*')->select();
	    foreach ($list2 as $key => $value) {
	        $list1[$value['dg_id']]['return'][] = $value;
	    }

	    //处理数据
	    foreach ($list1 as $key => $value) {
	        //检查发货单退货信息
	        foreach ($value['return'] as $k => $v) {
	            $value['return_number'] += $v['goods_number'];
	        }
	        //全部退货
	        $list[$value['delivery_id']]['deliveryWhole'] = '1';
	        if($value['goods_number'] != $value['return_number']){
	            $list[$value['delivery_id']]['deliveryWhole'] = 0;
	        }
	        if($value['goods_type'] == 1){
	            $value['goods_number'] = intval($value['goods_number']).'颗';
	            if($value['return_number'])$value['return_number'] = '已退货';
	        }elseif ($value['goods_type'] == 2){
	            if($value['return_number'] and round($value['goods_number'],3) == round($value['return_number'],3)){
	                $value['return_number'] = '已退货';
	            }elseif ($value['return_number']){
	                $value['return_number'] = '退货'.number_format($value['return_number'],3,'.','').'卡';
	            }
	        }elseif($value['goods_type'] == 3){
				$value['goods_number'] = intval($value['goods_number']).'件';
				if($value['return_number'])$value['return_number'] = '已退货';
			}elseif ($value['goods_type'] == 5){
	            $value['goods_number'] = intval($value['goods_number']).'件';
	            if($value['return_number'])$value['return_number'] = '退货'.$value['return_number'].'件';
	        }
	        $list[$value['delivery_id']]['delivery'][] = $value;
	    }

	    return $list;
	}

	/**
	 * 分销商确认收货一个商家的发货单，
	 * @param int $delivery_id
	 */
	public function confirmDeliveryOne($delivery_id){
	    //$where = 'agent_id = '.C('agent_id').' and tid = '.$this->tid.' and delivery_id = '.$delivery_id;
	    $where = 'agent_id =  '.get_parent_id().' and uid='.get_create_user_id().'  and delivery_id = '.$delivery_id;
	    $OD = M('order_delivery');
		$O	= M('order');	

	    $info = $OD->where($where)->find();

	    if(!$info){
			$O			= M('order','zm_','ZMALL_DB');	
			$OD			= M('order_delivery','zm_','ZMALL_DB');
			$where 		= 'tid = '.C('agent_id').' and uid='.get_create_user_id().'  and delivery_id = '.$delivery_id;
			$info 		= $OD->where($where)->find();
			if(!$info){
				$this->error('没有这张发货单，你不能操作收货!');
			}
	    }
		$O->where('agent_id = '.C('agent_id').' and order_id = ' .$order_id)->save($data);
		$O->startTrans();		
	    if($info['confirm_type'] == 0){
			$data['order_status'] = 5;
			$data['update_time'] = time();
			if($O->where(' order_id = ' .$info['order_id'])->save($data)){
				$ODdata['confirm_type'] = 2;
				$ODdata['confirm_time'] = time();
				$res = $OD->where($where)->save($ODdata);
				if($res){
					$O->commit();
					$this->success('操作采购单收货成功!');
				}else{
					$O->rollback();
				}
			}	
	    }else{
	        $this->error('当前发货单已经收货!');
	    }
	}

	/**
	 * 取消采购单(下采购单客户取消采购单)
	 * @param int $order_id
	 */
	public function purchaseOrderQuxia($order_id){
		$O = M('order');
		$info = $O->where('order_id='.I('get.order_id').' and agent_id = '.get_parent_id())->find();
		if($info['is_erji_caigou']=='1'){
			//二级到一级的。
			//验证订单归属
			if($info['agent_id'] != get_parent_id() and $info['uid'] != get_create_user_id()){
				$this->error('非法操作订单!');
			}
			//验证状态
			if($info['order_status'] == 0){
				$data['order_status'] = '-1';
				$data['update_time'] = time();
				if($O->where('agent_id = '.get_parent_id().' and order_id = '.I('get.order_id'))->save($data)){
					$this->success('取消成功。',U('Admin/Order/purchaseOrderList'));
				}else{
					$this->error('取消出错!');
				}
			}else{
				$this->error('已经货品确认后的采购单不可以主动取消!');
			}
		}else{
			$ZMO		= M('order','zm_','ZMALL_DB');
			$info 		= $ZMO->where('order_id = '.$order_id)->find();
			if($info['order_status'] == 0){
				$data['order_status'] = '-1';
				$data['update_time'] = time();
				if($ZMO->where('tid = '.C('agent_id').' and order_id = '.I('get.order_id'))->save($data)){
					$this->success('取消成功。',U('Admin/Order/purchaseOrderList'));
				}else{
					$this->error('取消出错!');
				}
			}else{
				$this->error('已经货品确认后的采购单不可以主动取消!');
			}
			
		}

	}

	
	
	/**
	 * 删除采购单(下采购单客户取消采购单)
	 * @param int $order_id
	 */
	public function purchaseOrderShangChu($order_id){
		$O = M('order');
		$info = $O->where('order_id='.I('get.order_id').' and agent_id = '.get_parent_id())->find();
		if($info['is_erji_caigou']=='1'){
			//二级到一级的。
			//验证订单归属
			if($info['agent_id'] != get_parent_id() and $info['uid'] != get_create_user_id()){
				$this->error('非法操作订单!');
			}
			//验证状态
			if($info['order_status'] == '-1'){
				if($O->where('agent_id = '.get_parent_id().' and order_id = '.I('get.order_id'))->delete()){
					$this->success('删除成功。',U('Admin/Order/purchaseOrderList'));
				}else{
					$this->error('删除出错!');
				}
			}else{
				$this->error('已经货品确认后的采购单不可以主动删除!');
			}
		}else{
			$ZMO		= M('order','zm_','ZMALL_DB');
			$info 		= $ZMO->where('order_id = '.$order_id)->find();
			if($info['order_status'] == '-1'){
				if($ZMO->where('tid = '.C('agent_id').' and order_id = '.I('get.order_id'))->delete()){
					$this->success('删除成功。',U('Admin/Order/purchaseOrderList'));
				}else{
					$this->error('删除出错!');
				}
			}else{
				$this->error('已经货品确认后的采购单不可以主动删除!');
			}
			
		}

	}	
	
	
	
	
	
	
	/**
	 * 获取采购单产品
	 */
	protected function getOrderCartGoodsList(){
	    $GAD = M('goods_associate_deputystone');//贷销货副石表
		$OC = D('order_cart');
		//$array = $OC->where($this->buildWhere())->select();
		$where['agent_id'] = C('agent_id');
		$array = $OC->getUpdateOrderCartGoodsList($where);


		$dollar_huilv = M('config_value')->where("config_key = 'dollar_huilv' and agent_id=".get_parent_id())->getField('config_value');

		foreach ($array as $key => $value) {

			$attr = unserialize($value['goods_attr']);			
			if($value['goods_type'] == 1){//裸钻产品
				$value['4c'] = '('.$attr['weight'].'/'.$attr['color'].'/'.$attr['clarity'].'/'.$attr['cut'].')';
				//$dia_discount = $attr['pifa_luozuan_advantage'] + $attr['dia_discount'];
				$dia_discount = $attr['dia_discount_all'];
				$value['goods_price'] = formatPrice($attr['dia_global_price']*$attr['weight']*$dollar_huilv*$dia_discount/100);
				$value['goods_price2'] = formatPrice($attr['dia_global_price']*$attr['weight']*$dollar_huilv*$dia_discount/100);
				$value['goods_sn_a'] = $attr['certificate_type'].' '.$value['goods_sn'];
				$value['goods_number'] = intval($value['goods_number']);
				$list['luozuan'][] = $value;
				$luozuanPrice += $value['goods_price2'];
			}elseif ($value['goods_type'] == 2){//散货
				
				$value['4c'] = htmlentities('('.$attr['color'].'/'.$attr['clarity'].'/'.$attr['cut'].')');
				//散货有特价获取特价
				if($attr['goods_price2'] > 0) $price = $attr['goods_price2'];
				else $price = $attr['goods_price'];
				//销售单价
				 $price1 = $price + ($price*$attr['pifa_sanhuo_advantage']/100);

				//采购单价
				$price2 = $price1;
				$value['goods_price'] = formatPrice($price1);
				$value['goods_price2'] = formatPrice($price2);
				$value['goods_price3'] = formatPrice($price2*$value['goods_number']);
				$list['sanhuo'][] = $value;
				$sanhuoPrice += $value['goods_price3'];
			}elseif ($value['goods_type'] == 3 or $value['goods_type'] == 4){//代销货
			    if($value['goods_type'] == 4){
			        // $material = $attr['associateInfo'];//贷销货基础数据
			        // $luozuan = $attr['luozuanInfo'];//主石匹配数据
			        // if($attr['deputystone']){
			        //     $deputystone_price = $GAD->where('gad_id = '.$attr['deputystone']['gad_id'])->getField('deputystone_price');//副石价格
			        // }else{
			        //     $deputystone_price = 0;
			        // }


					//采购单价
					
					$dingzhi_info = D('OrderCart')->getUpdateDingzhiGoodsInfo($attr, $value['goods_id']);
					$value['goods_price'] = $value['goods_price2'] = $dingzhi_info['goods_price'];
					/*
			       $value['goods_price2'] = $this->recalculatingPriceDo($material['weights_name'], $material['loss_name'], $material['gold_price'], $material['basic_cost'], $luozuan['price'], $deputystone_price, $attr['agent_consignment_advantage']);
			        //销售单价
			       $value['goods_price']  = $this->recalculatingPriceDo($material['weights_name'], $material['loss_name'], $material['gold_price'], $material['basic_cost'], $luozuan['price'], $deputystone_price, $attr['agent_consignment_advantage']);

					*/
			        //采购价小计
			        $value['goods_price3'] = formatPrice($value['goods_price2']*$value['goods_number']);
			        //金工石数据
			        $value['4c'] = $this->getJGSdata($attr);

			    }elseif ($value['goods_type'] == 3){
			        //$spec = $this->getSpecData($attr['specification_id']);
			        $spec = $attr;//$this->getSpecData($attr['specification_id']);
			        //采购单价
			        $value['goods_price2'] = $spec['goods_price']*($attr['pifa_consignment_advantage']/100+1);
			        //采购价小计
			        $value['goods_price3'] = formatPrice($spec['goods_price']*$value['goods_number']*($attr['pifa_consignment_advantage']/100+1));
			        //销售单价
			        $value['goods_price'] = formatPrice($spec['goods_price']*$value['goods_number']*($attr['pifa_consignment_advantage']/100+1));
			        //规格数据
				    //$value['4c'] = 'SKU:'.$spec['goods_sku']['sku_sn'];
				  	
				    $attributes = $attr['goods_sku']['attributes'];//使用订单里面的SKU规格，防止修改产品
  		            $sku = $this->getGoodsSku($attr['category_id'],$attr['goods_id'],$attr['goods_sku']['sku_sn'],$attributes);
  		            $value['4c'] = $sku['sku'];
			    }
				//格式化数量
				$value['goods_number_up'] = intval($value['goods_number_up']);
				$value['goods_number'] = intval($value['goods_number']);

				$value['goods_name'] = $attr['goods_name'];

				$list['consignment'][] = $value;
				$consignmentPrice += $value['goods_price3'];
			}
		}
		$this->consignmentPrice = formatPrice($consignmentPrice);
		$this->luozuanPrice = formatPrice($luozuanPrice);
		$this->sanhuoPrice = formatPrice($sanhuoPrice);
		return $list;
	}

	/**
	 * 采购单删除产品
	 * @param int $id
	 */
	public function purchaseOrderdel($id){
		$OC = M('order_cart');
		if($OC->where('agent_id = '.C('agent_id').' and id = '.$id)->delete()){
			$this->success('删除产品成功！',U('Admin/Order/purchaseOrderInfo'));
		}else{
			$this->error('删除产品失败！',U('Admin/Order/purchaseOrderInfo'));
		}
	}



	/**
	 * 统计产品价格
	 * @param array $goods
	 */
	protected function countGoodsPrice($goods,$up='0'){
		$noPrice = array('0','-1','-2');
		//裸钻计算
		foreach ($goods['luozuan'] as $key => $value) {
			if(in_array($up,$noPrice)){
				$luozuanPrice += $value['goods_price']*$value['goods_number'];
			}else{
				$luozuanPrice += $value['goods_price_up']*$value['goods_number_up'];
			}
		}
		//成品计算
		foreach ($goods['goods'] as $key => $value) {
			if(in_array($up,$noPrice)){
				$goodsPrice += $value['goods_price'];
			}else{
				if($value['goods_number_up'] > 0){$goodsPrice += $value['goods_price_up'];
				}else{$goodsPrice += 0;}
			}
		}
		//散货计算
		foreach ($goods['sanhuo'] as $key => $value) {
			if(in_array($up,$noPrice)){
				$sanhuoPrice += $value['goods_price'];
			}else{
				if($value['goods_number_up'] > 0) $sanhuoPrice += $value['goods_price_up'];
				else $sanhuoPrice += 0;
			}
		}
		foreach ($goods['consignment'] as $key => $value) {
			if(in_array($up,$noPrice)){
				$consignmentPrice += $value['goods_price'];
			}else{
				if($value['goods_number_up'] > 0){$consignmentPrice += $value['goods_price_up'];
				}else{$consignmentPrice += 0;}
			}
		}
		foreach ($goods['banfang'] as $key => $value) {
			if(in_array($up,$noPrice)){
				$consignmentPrice += $value['goods_price'];
			}else{
				if($value['goods_number_up'] > 0){$consignmentPrice += $value['goods_price_up'];
				}else{$consignmentPrice += 0;}
			}
		}

		foreach ($goods['consignment_szzmzb'] as $key => $value) {
			if(in_array($up,$noPrice)){
				$consignmentPrice += $value['goods_price'];
			}else{
				if($value['goods_number_up'] > 0){
					$consignmentPrice += $value['goods_price_up'];
				}else{
					$consignmentPrice += 0;
				}
			}
		}		
		$this->consignmentPrice = formatPrice($consignmentPrice);
		$this->luozuanPrice = formatPrice($luozuanPrice);
		$this->goodsPrice = formatPrice($goodsPrice);
		$this->sanhuoPrice = formatPrice($sanhuoPrice);
	}

	// 添加订单
	public function orderAdd(){
		if(IS_POST){
			//订单归属
			if($this->is_yewuyuan){
				$_POST['parent_id'] = $this->uid;
			}else{
				$_POST['parent_id'] = empty($_POST['business_id'])?'0':intval($_POST['business_id']);
			}

			if(empty($_POST['uid']) or empty($_POST['parent_id']) or empty($_POST['address_id']) or empty($_POST['delivery_mode'])){
				$this->error('客户,订单所属,收货地址,配送方式必须选择!');
			}
			if($this->orderData($_POST)) $this->log('success', 'A47','',U('Admin/Order/orderList'));
			else $this->log('error', 'A48');
		}else{
			if($this->is_yewuyuan){
				$U = M('user');
				$this->userList = $U->where('parent_id = '.$this->uid . ' and agent_id = '.C('agent_id'))->select();//获取业务员的用户
			}else{
				$join[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
				$join[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan=1';
				$field = 'au.nickname,au.user_id';
				$this->businessList = M('admin_user')->where('au.agent_id='.C('agent_id'))->field($field)->alias('au')->join($join)->select();
			}
			$this->user_delivery_mode	 = D('Common/OrderPayment')->this_dispatching_way();		//配送方式。
			$this->display();
		}
	}

	//钻明添加一个订单,采购单和订单通用
	private function orderData(){
		$POST = I('post.');

		//组装订单数据
		$data['order_sn']      = date('Ymdhis',time()).rand(10, 99);
		$data['uid']           = $POST['uid']?$POST['uid']:0;
		$data['tid']           = $POST['tid']?$POST['tid']:0;
		//确认前价格
		$data['order_price']   = 0;
		$data['create_time']   = time();
		$data['parent_id']     = $POST['parent_id'];
		//$data['parent_type'] = $POST['parent_type'];
		$data['note']          = $POST['note'];
		$data['address_id']    = $POST['address_id']?$POST['address_id']:0;
		$data['address_info']  = D('Common/UserAddress')->getAddressInfo($data['address_id']);
		$data['delivery_mode'] = $POST['delivery_mode'];
		$data['dollar_huilv']  = C('dollar_huilv');
		$data['agent_id']      = C('agent_id');
		//插入订单数据（事务）
		$O = D('order');
		$O->startTrans();
		$order_id = $O->add($data);
		if($order_id){
			$GL = D('GoodsLuozuan');//证书货表
			$GS = D('GoodsSanhuo');//散货表
// 			$G = Mw('goods');//分销商产品表
			$OG = M('order_goods');
			$order_price = '0';//确认前订单价格
			//////////////解决证书货存在重复问题
			$out = array();
			foreach ($POST['luozuan'] as $value) {
				if (!in_array($value['certificate_number'], $out)){
			        $out[] = $value['certificate_number'];
			    }else{
			    	$O->rollback();
			    	$this->error('有证书编号'.$value['certificate_number'].'存在重复！');
			    }
			}

			$parent_id = get_parent_id();

			foreach ($POST['luozuan'] as $key => $value) {
				if(empty($value['certificate_number'])){$O->rollback();$this->error('有证书编号不能为空');}
				if($value['goods_num'] <= 0){$O->rollback();$this->error('数量必须大于0');}
                $info = $GL->where("agent_id in  (0,".C('agent_id').",".$parent_id.") and certificate_number = '".$value['certificate_number']."'")->find();

				$info                       = getGoodsListPrice($info,  $data['uid'] , 'luozuan', 'single');
				$arr['goods_price'] 		= $info['price'];
				$arr['order_id'] 			= $order_id;
				$arr['goods_id'] 			= $info['gid'];

				$arr['attribute'] 			= serialize($info);
				$arr['goods_type'] 			= '1';
				$arr['parent_id'] 			= $POST['parent_id'];
				$arr['goods_number'] 		= $value['goods_num'];
				$arr['certificate_no'] 		= $value['certificate_number'];
				$arr['update_time'] 		= time();
				$arr['agent_id'] 			= C('agent_id');
				$arr['luozuan_type'] 		= $info['luozuan_type'];
				$goodsData[] = $arr;
				$order_price += $arr['goods_price'];//计算确认前订单价
			}
			//散货数据添加
			foreach ($POST['sanhuo'] as $key => $value) {
				if(empty($value['goods_sn'])){$O->rollback();$this->error('有散货编号不能为空');}
				if($value['goods_num'] <= 0){$O->rollback();$this->error('数量必须大于0');}
				$info = $GS -> getInfo(array('zm_goods_sanhuo.goods_sn'=>array('like',$value['goods_sn']),'zm_goods_sanhuo.goods_id'=>array('eq',intval($value['goods_id']))));
				$info = getGoodsListPrice($info , $data['uid'] , 'sanhuo', 'single');
				$arr['goods_price'] = $info['goods_price'] * $value['goods_num'];
				$arr['order_id']    = $order_id;
				$arr['goods_id']    = $info['goods_id'];

				$info['sanhuo_advantage'] = $sanhuo_advantage;
				$arr['attribute']         = serialize($info);
				$arr['goods_type']        = '2';
				$arr['parent_id']         = $POST['parent_id'];
				$arr['goods_number']      = $value['goods_num'];
				$arr['certificate_no']    = $value['goods_sn'];
				$arr['update_time']       = time();
				$arr['agent_id']          = C('agent_id');

				$goodsData[] = $arr;
				$order_price += $arr['goods_price'];//计算总价
			}

			//添加订单产品
			if($OG->addAll($goodsData)){
				$res = $O->where('agent_id = '.C('agent_id').' and order_id ='.$order_id)->setField('order_price',$order_price);
				if($res){
					$O->sendEmail($order_id);
					$O->commit();
					//发送用户消息
					$username = M("User")->where('uid='.$data['uid'])->getField('username');
					$delivery_mode = M("delivery_mode")->where('mode_id='.$data['delivery_mode'])->getField('mode_name');
					$title = "下单成功";
					$content = "用户".$username." 您已下单成功！订单编号：".$data['order_sn']."，配送方式：".$delivery_mode."，支付方式：线下转账， 订单金额：￥". $order_price;
					$this->sendMsg($data['uid'],$content, $title,  1);
					return true;
				}else{
					$O->rollback();
					return false;
				}
			}else{
				$O->rollback();
				return false;
			}
		}else{
			
			$O->rollback();
			return false;
		}
	}
	//钻明添加一个采购单
	private function orderDataCaigou(){
		$POST = I('post.');

		$OC = D('order_cart');	
		$where['agent_id'] = C('agent_id');
		$array = $OC->getUpdateOrderCartGoodsList($where);

		//组装订单数据
		$agent_father = get_parent_id();
		$data['order_sn'] = date('Ymdhis',time()).rand(10, 99);
		$data['uid'] = get_create_user_id();//$POST['uid']?$POST['uid']:0;

		$data['parent_id'] = M('user')->where('uid = '. $data['uid'])->getField('parent_id');
		$data['tid'] = C('agent_id');//$POST['tid']?$POST['tid']:0;
		//确认前价格
		$data['order_price'] = 0;
		$data['create_time'] = time();
		//$data['parent_id'] = $POST['parent_id'];
		//$data['parent_type'] = $POST['parent_type'];
		$data['note'] = $POST['note'];
		$data['address_id'] = $POST['address_id']?$POST['address_id']:0;
		$data['delivery_mode'] = $POST['delivery_mode'];
		
		$UA = M('user_address');
		$field = 'ua.address,ua.title,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
				'r2.region_name as province,r3.region_name as city,r4.region_name as district';
		$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
		$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
		$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
		$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';

		$data_address = $UA->alias('ua')->where('ua.address_id = '.$data['address_id'])->field($field)
			->join($join1)->join($join2)->join($join3)->join($join4)->find();

		$data['address_info'] = $data_address['country'].' '.$data_address['province'].' '.$data_address['city'].' '.$data_address['district'].' '.$data_address['address'].' '.$data_address['name'].' '.$data_address['phone'].' '.$data_address['title'];

		$huilv = M('config_value')->where("config_key = 'dollar_huilv' and agent_id = ".$agent_father)->field('config_value')->find();
		$data['dollar_huilv']   = $huilv['config_value'];
		$huilv 				    = $data['dollar_huilv'] ;
		$data['agent_id']       = $agent_father;
		$data['is_erji_caigou'] = 1 ;//1表示是二级在后台下的采购单，0表示是在一级网站下的采购单

		//插入订单数据（事务）
		$O = M('order');
		$O->startTrans();

		$order_id = $O->add($data);


		if($order_id){

			$GL = D('GoodsLuozuan');//证书货表
			$GS = D('GoodsSanhuo');//散货表
 			$G  = M('goods');//分销商产品表
			$OG = M('order_goods');
			$order_price = '0';//确认前订单价格

			$erji_info = M('trader')->where('t_agent_id='.C('agent_id'))->find();  //二级的信息

			//代销货数据添加,代销货只有1，2级分销商才会有
			foreach ($POST['luozuan'] as $value) {
				if (!in_array($value['certificate_number'], $out)){
			        $out[] = $value['certificate_number'];
			    }else{
			    	$O->rollback();
			    	$this->error('有证书编号'.$value['certificate_number'].'存在重复！');
			    }
			}
			
			foreach ($POST['luozuan'] as $key => $value) {
				if(empty($value['certificate_number'])){$O->rollback();$this->error('有证书编号不能为空');}
				if($value['goods_num'] <= 0){$O->rollback();$this->error('数量必须大于0');}
               
				//$info  = $GL->where('certificate_number = \''.$value['certificate_number'].'\' and gid = '.$value['goods_id'])->find();



				$goods    = M('goods_luozuan')->where("`gid`='".$value['goods_id']."' AND `goods_number` = 1 ") -> find();
                $goodsObj = D("Common/GoodsLuozuan");//初始化默认的读取的是白钻的加点

                if($goods['luozuan_type'] == 1){
                    //设置彩钻参数
                    $goodsObj -> setLuoZuanPoint('0','1');
                }

                $info = $goodsObj -> where("`gid`='".$value['goods_id']."' AND `goods_number` = 1 ") -> find();

                
				 //返回上级的折扣点数
				if($info['luozuan_type'] == 1){
					$point = getLuoZuanPoint(1,'luozuan_advantage');
				}else{
					$point = getLuoZuanPoint(1,'caizuan_advantage');
				}
                
				// if($agent_father != C('agent_id')){
				// 	$info['dia_discount'] = $info['dia_discount'] -  $erji_info['luozuan_advantage'];
				// }
				//$info['dia_discount_all'] = $info['dia_discount'] -  $erji_info['luozuan_advantage'];
				$info = getCaigouGoodsListPrice($info, $data['uid'], 'luozuan',     'single');
				//$userdiscount = getUserDiscount(intval($data['uid']));
				//$info['userdiscount'] 		    = $userdiscount;

				// $info['luozuan_advantage']      = 0;   //二级批发商，不算会员的折扣
				// $info['pifa_luozuan_advantage'] = $erji_info['luozuan_advantage'];
				// $info['price_display_type']     = M('config_value')->where("config_key = 'price_display_type' and agent_id=".$agent_father)->getField('config_value');

				// if($info['price_display_type'] != 1) {
	            //    $info['dia_discount_all']  = $info['dia_discount'] + $info['luozuan_advantage']  +  $info['userdiscount'];
	            // }else{
	            //   $info['dia_discount_all']  = $info['dia_discount'] * (1+($info['luozuan_advantage'] +  $info['userdiscount'])/100);
	            // }
			    $price = formatRound($info['dia_global_price'] * $huilv * ($info['dia_discount_all']) * $info['weight']/100, 2);
				$arr['goods_price'] 		= $price;
				$arr['order_id'] 			= $order_id;
				$arr['goods_id'] 			= $info['gid'];

				$arr['attribute'] 			= serialize($info);
				$arr['goods_type'] 			= '1';
				//$arr['parent_type'] 		= $POST['parent_type'];
				$arr['parent_id'] 			= 0;//$POST['parent_id'];
				$arr['goods_number'] 		= $value['goods_num'];
				$arr['certificate_no'] 		= $value['certificate_number'];
				$arr['update_time'] 		= time();
				$arr['agent_id'] 			= $agent_father;
				$goodsData[] = $arr;
				$order_price += $price;//计算确认前订单价
			}

			//散货数据添加
			foreach ($POST['sanhuo'] as $key => $value) {
				if(empty($value['goods_sn'])){$O->rollback();$this->error('有散货编号不能为空');}
				if($value['goods_num'] <= 0){$O->rollback();$this->error('数量必须大于0');}
				
				// $zm_goods_id = $GS->getOne('zm_goods_id',array('zm_goods_sanhuo.goods_id'=>array('eq',$value['goods_id'])));
				// if(!$zm_goods_id){
				// 	$this->error('供应商没有散货'.$value['goods_sn'].'！');
				// }


				
                $where = array('goods_id'=>array('eq',$value['goods_id']));
                $info         = $GS -> join('join zm_goods_sanhuo_type on zm_goods_sanhuo_type.tid = zm_goods_sanhuo.tid ') -> where($where) -> field('zm_goods_sanhuo.*,zm_goods_sanhuo_type.type_name,zm_goods_sanhuo_type.type_name_en') -> find();

				//$info = $GS->getInfo(array('zm_goods_sanhuo.goods_id'=>array('eq',$value['goods_id']),'zm_goods_sanhuo.zm_goods_id'=>array('in',intval($zm_goods_id))));
				$info = getCaigouGoodsListPrice($info, $data['uid'], 'sanhuo',      'single', $agent_father);

				$sanhuo_advantage = $erji_info['sanhuo_advantage']; 				//从采购单发过来的散货从新计算价格
				//$info['pifa_sanhuo_advantage'] = $sanhuo_advantage;

				if(empty($value['goods_price'])){
					//计算散货每卡单价,小计价格
					if($info['goods_price2'] > 0){
						$info['goods_price'] = $info['goods_price2'];
					}
					//if($sanhuo_advantage){
					//	$goods_price1 = formatPrice($info['goods_price'] * (1+$sanhuo_advantage /100) );
					//}else{
						$goods_price1 = formatPrice($info['goods_price']);
					//}
					$goods_price2 = formatPrice($goods_price1*$value['goods_num']);
					$arr['goods_price'] = $goods_price2;

				}
				$arr['order_id'] = $order_id;
				$arr['goods_id'] = $info['goods_id'];

				$info['sanhuo_advantage'] = 0;
				$arr['attribute'] = serialize($info);
				$arr['goods_type'] = '2';
				//$arr['parent_type'] = $POST['parent_type'];
				$arr['parent_id'] = 0;
				$arr['goods_number'] = $value['goods_num'];
				$arr['certificate_no'] = $value['goods_sn'];
				$arr['update_time'] = time();
				$arr['agent_id'] = $agent_father;;

				$goodsData[] = $arr;
				$order_price += $arr['goods_price'];//计算总价
			}


			foreach ($POST['consignment'] as $key => $value) {
				if(empty($value['goods_sn'])){$O->rollback();$this->error('没有产品编号,并且不能为空!');}
				if($value['goods_num'] <= 0){$O->rollback();$this->error('数量必须大于0');}
				$OR = M('order_cart');
				$cart_info = $OR->where('id = '.$value['cart_id'])->find();
				$info = unserialize($cart_info['goods_attr']);
				//$info = $G->where("agent_id = ".$agent_father." and goods_id = '".$cart_info['goods_id']."' ")->find();
				$info['pifa_consignment_advantage'] = $erji_info['consignment_advantage'];

				if(is_float($value['goods_num'])){
				    $O->rollback();
				    $this->error('代销货数量必须是整数');
				}
				//代销货价格计算
				//if(in_array_diy('7,8',$this->group)){//1级,2级分销商计算
					if($info['goods_type'] == 4){//代销货定制货品
					    //计算数据以序列化的数据为准，不以产品数据为准
					    
					    $dingzhi_info = D('OrderCart')->getUpdateDingzhiGoodsInfo($info, $cart_info['goods_id']);
					    $price =  $dingzhi_info['goods_price'];
					    // $GAD = M('goods_associate_deputystone');//贷销货副石表
					    // $material = $info['associateInfo'];
					    // $luozuan = $info['luozuanInfo'];
					    // if($info['deputystone']){
					    //     $deputystone_price = $GAD->where('gad_id = '.$info['deputystone']['gad_id'])->getField('deputystone_price');//副石价格
					    // }else{
					    //     $deputystone_price = 0;
					    // }

					    // $price = $this->recalculatingPriceDo($material['weights_name'], $material['loss_name'], $material['gold_price'],
					    //     $material['basic_cost'], $luozuan['price'], $deputystone_price, $info['agent_consignment_advantage']);

					    $price = formatPrice($price*$value['goods_num']);
					}elseif ($info['goods_type'] == 3){//代销货成品货
					    $spec = $info;//$this->getSpecData($info['specification_id']);
					    //$price = formatPrice($spec['goods_price']*(1+$info['pifa_consignment_advantage']/100)*$value['goods_num']);
					    $price = formatPrice($spec['goods_price']*(1+$info['agent_consignment_advantage']/100)*$value['goods_num']);
					}
				//}
				$arr['goods_price']    = $price;
				$arr['order_id'] 	   = $order_id;
				$arr['goods_id'] 	   = $info['goods_id'];
				$info = getCaigouGoodsListPrice($info, $data['uid'], 'consignment', 'single', $agent_father);
				$arr['attribute'] 	   = serialize($info);
				$arr['goods_type'] 	   = $info['goods_type'];
				$arr['goods_number']   = $value['goods_num'];
				$arr['certificate_no'] = $value['goods_sn'];
				$arr['update_time']    = time();
				$arr['agent_id'] 	   = $agent_father;

				$goodsData[] = $arr;

				$order_price += $price;//计算确认前订单价
			}

			if($OG->addAll($goodsData)){
				$res = $O->where('agent_id = '.$agent_father.' and order_id ='.$order_id)->setField('order_price',$order_price);
				if($res){
					$O->commit();
					return true;
				}else{
					$O->rollback();
					return false;
				}
			}else{
				$O->rollback();
				return false;
			}
		}else{
			$O->rollback();
			return false;
		}
	}
	/**
	 * 获取业务员下面的客户
	 * @param int $id
	 */
	public function getBusinessUserList($id){
		$this->getBusinessUser($id);
	}

	/**
	 * 获取用户收货地址
	 * @param int $uid
	 */
	public function getUserAddress($uid){		
		$data = D('Common/UserAddress')->getAddressList(I('get.uid'));
		$this->ajaxReturn($data);
	}

	/**
	 * 根据地址ID获取收货地址信息
	 * @param int $id
	 * @return array
	 */
	private function getAddressInfo($id){
		$UA = M('user_address');
		$field = 'ua.address,ua.name,ua.phone,ua.address_id,r1.region_name as country,'.
				'r2.region_name as province,r3.region_name as city,r4.region_name as district';
		$join1 = 'zm_region AS r1 ON r1.region_id = ua.country_id';
		$join2 = 'zm_region AS r2 ON r2.region_id = ua.province_id';
		$join3 = 'zm_region AS r3 ON r3.region_id = ua.city_id';
		$join4 = 'zm_region AS r4 ON r4.region_id = ua.district_id';
		$data = $UA->alias('ua')->where('ua.address_id = '.$id.' and ua.agent_id = '.C('agent_id'))->field($field)
			->join($join1)->join($join2)->join($join3)->join($join4)->find();
		$data['addressInfo'] = $data['country'].$data['province'].$data['city'].$data['district'].$data['address'];
		return $data;
	}

	/**
	 *  订单添加裸钻产品,获取裸钻的数据
	 * @param string $certificate_number
	 */
	public function orderAddGoodsLuozuan($certificate_number='', $uid=0){
		if($certificate_number){
            $GL    = D('GoodsLuozuan');
            $data  = $GL->where("certificate_number = '".I('get.certificate_number')."'")->find();
			$data  = getGoodsListPrice($data,  $uid, 'luozuan', 'single');
   			$this -> ajaxReturn($data);
		}else{
			layout(false);
			$this->SJID = rand(10000,99999);
			$this->ajaxReturn($this->fetch());
		}
	}

	/**
	 * 订单添加散货产品，获取散货的数据
	 * @param string $goods_sn
	 */
	public function orderAddGoodsSanhuo($goods_sn='', $uid=0){
		if($goods_sn){
			$GS                               = D('GoodsSanhuo');
			$goods_id                         = intval(I('get.goods_id'));
			$data                             = array();
			$data['zm_goods_sanhuo.goods_id'] = array('eq',$goods_id);
			$data['zm_goods_sanhuo.goods_sn'] = array('like',$goods_sn);
			$GS_data  = $GS->getInfo($data);
			if($GS_data){
				$data = getGoodsListPrice($GS_data, $uid, 'sanhuo', 'single');
			}
			$this->ajaxReturn($data);
		}else{
			layout(false);
			$this->SJID = rand(10000,99999);
			$data = array('html'=>$this->fetch());
			$this->ajaxReturn($data);
		}
	}

	/**
	 * 订单添加代销货
	 */
	public function orderAddConsignment($goods_sn='',$material='',$deputystone='',$d){
		$this->SJID = rand(10000,99999);
		layout(false);
		if(IS_POST){
			//获取产品信息
			$G = M('goods');
			$info = $G->where("agent_id = ".C('agent_id')." and goods_sn = '$goods_sn'")->find();
			if(is_array($info)){
				//计价：金重*(1+损耗)*当日金价+工费
				//获取材质选择（得到金重和损耗）
				$GAI = M('goods_associate_info');
				$join = 'JOIN zm_goods_material AS gm ON gm.material_id = gai.material_id';
				$info['materialList'] = $GAI->alias('gai')->where('gai.agent_id = '.C('agent_id').' and goods_id = '.$info['goods_id'])->join($join)->select();
				//获取副石选择
				$GAD = M('goods_associate_deputystone');
				$info['deputystoneList'] = $GAD->alias('gad')->where('agent_id = '.C('agent_id').' and goods_id = '.$info['goods_id'])->select();
				$this->info = $info;
				$data['info'] = $this->fetch();
				$data['status'] = 1;
				$this->ajaxReturn($data);
			}else{
				$this->error('没有当前的代销产品');
			}
		}else{
			$this->ajaxReturn($this->fetch());
		}
	}

	/**
	 * 全部订单操作中间跳转方法
	 * @param int $order_id
	 */
	public function orderAction($order_id=''){

		if(!$order_id)redirect(U('Admin/Order/orderList'));
		// 钻明修改备注
		if($_POST['noteAction']){
			//$res1 = $this->markAction($order_id, $_POST['mark_item']);//var_dump($_POST['mark_item']);die;
			if(!in_array_diy('7,8', $this->group)){
                $res2 = $this->editOrderLuozuanNumber($order_id);
			}
			if(!empty($_POST['note'])){
				$this->noteAction($order_id, htmlspecialchars($_POST['note']));
			}else{
			    if($res1 and $res2){
			        $this->success('操作标记和修改订购数量成功！');
			    }elseif ($res2){
			        $this->success('修改订购数量成功！');
			    }elseif($res1){
			        $this->success('操作标记成功！');
			    }else{
			        $this->success('你都没有做任何操作！');
			    }
			}
		}
	    // 钻明货品确认
	    if($_POST['confirmAction']){
	    	$this->confirmAction($order_id,$_POST);
	    }
	    // 钻明手工入账
	    if($_POST['paymentAction']){
	    	$this->redirect('Admin/Business/payMoney?order_id='.$order_id);
	    }
	    //发货给客户
	    if($_POST['deliveryInfo']){
	    	$this->redirect('Admin/Order/deliveryInfo?order_id='.$order_id);
	    }
	    //取消订单
	    if($_POST['quxiaoAction']){
	    	$this->quxiaoAction($order_id);
	    }
	    //取消确认订单
	    if($_POST['quxiaoOrderAction']){
	    	$this->quxiaoOrderAction($order_id);
	    }
	    //发货方确认已发货
	 	if($_POST['confirmDelivery']){
	    	$this->confirmDelivery($order_id);
	    }
	    //订单确认付款
	    if($_POST['confirmPay']){
	    	$this->confirmPay($order_id);
	    }
	    //订单配货完成
	    if($_POST['purchaseAction']){
	    	$this->purchaseAction($order_id);
	    }
	    //完成订单
	    if($_POST['completeAction']){
	    	$this->completeAction($order_id);
	    }
	    //订单补差价
	    if($_POST['orderDifference']){
	        $this->orderDifference($order_id);
	    }

	}

	/**
	 * 订单补差价
	 * @param int $order_id
	 */
	protected function orderDifference($order_id){
	    $url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'orderDifference';
	    if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
	    //获取订单信息
	    $where = $this->buildWhere();
	    $O = M('order');
	    $info = $O->where($where.' and order_id = '.$order_id)->find();
	    if(!$info){
	        $this->error('没有这个订单!');
	    }
	    //数据检查
	    if($_POST['consignment']['Return'] and $_POST['consignment']['Fill']){
	        $this->error('补差价只能补一样');
	    }
	    $data = $this->buildData();
	    //退差价
	    if($_POST['consignment']['Return']){
	        $OC = M('order_compensate');//应退款表
	        $data['order_id'] = $order_id;
	        $data['period_type'] = '12';
	        $data['uid'] = $info['uid'];
	        $data['tid'] = $info['tid'];
	        $data['compensate_price'] = $_POST['consignment']['Return'];
	        $data['compensate_note'] = '代销货品退差价';
	        $data['compensate_status'] = '1';
	        $data['create_time'] = time();
			$data['agent_id'] = C('agent_id');
	        $res = $OC->add($data);
	        $notes = '订单珠宝定制产品退差价：'.$data['compensate_price'];
	        $msg = '退差价成功!';
	    }
	    //补差价
	    if($_POST['consignment']['Fill']){
	        $OR = M('order_receivables');//应付款表
	        $data['order_id'] = $order_id;
	        $data['period_type'] = '12';
	        $data['uid'] = $info['uid'];
	        $data['tid'] = $info['tid'];
	        $data['period_current'] = '0';
	        $data['period_day'] = '0';
	        $data['receivables_price'] = $_POST['consignment']['Fill'];
	        $data['payment_time'] = time();
	        $data['receivables_note'] = '需要客户补差价';
	        $data['receivables_status'] = 1;
	        $data['create_time'] = time();
			$data['agent_id'] = C('agent_id');
	        $res = $OR->add($data);
	        $notes = '订单珠宝定制产品补差价：'.$data['receivables_price'];
	        $msg = '补差价成功!';
	    }
        if($res){
	        $this->orderLog($order_id, $notes, $msg);
	    }else{
	        $this->error('操作失败!');
	    }
	}

	/**
	 * 发货单列表
	 * @param number $p
	 * @param number $n
	 */
    public function deliveryList($p=1,$n=13,$order_sn=0,$create_time=0,$confirm_time=0,$status=3){
        $OD = M('order_delivery');
        $where = $this->buildWhere('od.');
        //订单编号筛选条件
        if($order_sn){
			$order_sn = addslashes($order_sn);
            $owhere .=" and o.order_sn like '%".$order_sn."%' ";
            $this->order_sn = $order_sn;
        }
        //发货时间筛选条件
        if($create_time){
            $time = strtotime(I('get.create_time'));
            $time1 = $time + 60*60*24;
            $where .= ' and od.create_time > '.$time.' and od.create_time < '.$time1;
            $this->create_time = $create_time;
        }
        //客户筛选
        if(I('get.userName')){
            $U = M('user');
            $T = M('trader');
            $name = addslashes(I('get.userName'));
            $uWhere = "(username like '%$name%' or realname like '%$name%') and agent_id = ".C('agent_id');
            $list1 = $U->where($uWhere)->field('uid')->select();
            $tWhere = "agent_id = ".C('agent_id')." and trader_name like '%$name%'";
            $list2 = $T->where($tWhere)->field('tid')->select();
            foreach ($list1 as $key => $value) {
                if($key == 0 and !empty($value)){
                    $str1 = $value['uid'];
                }elseif ($key){
                    $str1 .= ','.$value['uid'];
                }
            }
            if($str1)$where .= ' and (od.uid in ('.$str1.')';
            foreach ($list2 as $key => $value) {
                if($key == 0 and !empty($value)){
                    $str2 = $value['tid'];
                }elseif ($key){
                    $str2 .= ','.$value['tid'];
                }
            }
            if($str1) $jx = ' or ';
            else $jx = ' and (';
            if($str2) $where .= $jx.'od.tid in ('.$str2.') )';
            elseif ($str1)$where .= ')';
            $this->userName = I('get.userName');
        }

		if($this->is_yewuyuan){
			$where .=' and od.parent_id = '.$this->uid.'';
		}

        //收货时间筛选条件
        if($confirm_time){
            $endtime = strtotime(I('get.confirm_time'));
            $endtime1 = $endtime + 60*60*24;
            $where .= ' and od.confirm_time > '.$endtime.' and od.confirm_time < '.$endtime1;
            $this->confirm_time = $confirm_time;
        }
        //收货状态筛选条件
        $this->status = $status;
        if($status == 1){
            $where .=' and od.confirm_type = 1';
        }else if($status == 2){
            $where .=' and od.confirm_type = 2';
        }else if($status == 0){
            $where .=' and od.confirm_type = 0';
        }else{

		}
        //多表查询
        $field = 'od.*,o.order_sn,u.username,u.realname,t.trader_name';
        $join[1] = 'zm_order AS o ON o.order_id = od.order_id '.$owhere;
        $join[2] = 'LEFT JOIN zm_user AS u ON u.uid = od.uid';
        $join[3] = 'LEFT JOIN zm_trader AS t ON t.tid = od.tid';
        //分页
        $count = $OD->alias('od')->where($where)->join($join)->count();
        $Page = new Page($count,$n);
        $this->page = $Page->show();
        $list = $OD->alias('od')->field($field)->where($where)->order('od.create_time DESC')
            ->join($join)->limit($Page->firstRow,$Page->listRows)->select();
        //处理发货单信息
        foreach ($list as $key => $value) {
            if($value['uid']){
            	$value['order_type'] = '客户订单';
            	if($value['realname']){
            		$value['obj'] = $value['realname'];
            	}else{
            		$value['obj'] = $value['username'];
            	}
            	$value['objUrl'] = U('Admin/User/userInfo?uid='.$value['uid']);
            }else{
            	$value['order_type'] = '采购单';
            	$value['obj'] = $value['trader_name'];
            	$value['objUrl'] = U('Admin/Trader/traderInfo?tid='.$value['tid']);
            }
            $array[] = $value;
        }
        $this->List = $array;
        $this->display();
    }

	/**
	 * 订单退货(客户退货)
	 * 分为两类，1有发货退货，2无发货退货
	 * @param int $order_id
	 */
	public function orderReturn($order_id,$delivery_id){
		if($_POST['orderReturn']){
			//获取订单数据
			$O = M('order');
			$orderInfo = $O->where('agent_id = '.C('agent_id'))->find($order_id);
			$OR = M('order_return');
			$OR->startTrans();
			$goods_price = '';//退货单总价格
			//把页面发过来的数组组装成一个大数组
			$list = array();
			$goodsLuozuan = I('post.goodsLuozuan');//证书货
			$goodsSanhuo = I('post.goodsSanhuo');//散货
			$goods = I('post.goods');//珠宝产品
			$consignment = I('post.consignment');//代销货
			if($goodsLuozuan){$list = array_merge($list,$goodsLuozuan);}
			if($goodsSanhuo){$list = array_merge($list,$goodsSanhuo);}
			if($goods){$list = array_merge($list,$goods);}
			if($consignment){$list = array_merge($list,$consignment);}
			//判断是否有勾选产品
			$num = 0;
			foreach ($list as $key => $value) {if($value['dg_id']) $num ++;}
			if(!$num){$this->error('退货请勾选相应的货品');}
			$ODG = M('order_delivery');
			$info = $ODG->where('agent_id = '.C('agent_id').' and delivery_id = '.$delivery_id)->find();

			//生成一张退货单记录
			$returnArr['order_id'] = $order_id;
			$returnArr['delivery_id'] = $delivery_id;
			$returnArr['return_type'] = 1;//有发货退货
			$returnArr['uid'] = $info['uid'];
			$returnArr['tid'] = $info['tid'];
			$returnArr['parent_type'] = $info['parent_type'];
			$returnArr['parent_id'] = $info['parent_id'];
			$returnArr['status'] = 1;
			$returnArr['create_time'] = time();
			$returnArr['goods_price'] = '0';//先默认0，等计算出来再处理
			$returnArr['agent_id'] = C('agent_id');
			$return_id = $OR->add($returnArr);
			if(!$return_id){
				$OR->rollback();
				$this->error('添加退货单失败!');
			}
			//生成退货单产品
			$ODG = M('order_delivery_goods');
			foreach ($list as $key => $value) {
				if($value['dg_id']){
				    $ids .= ','.$value['dg_id'];
				}
			}
			//删除掉第一个','
			$ids = substr($ids,1);
			$goodsList = $ODG->where('agent_id = '.C('agent_id').' and dg_id in('.$ids.')')->select();
			$goodsList = $this->_arrayIdToKey($goodsList);
			//获取发货单产品退货情况
			$ORG = M('order_return_goods');
			$join1[] = 'JOIN zm_order_return AS zor ON zor.status <> 0 and zor.return_id = org.return_id';
			$res = $ORG->alias('org')->where('org.agent_id = '.C('agent_id').' and org.delivery_id = '.$delivery_id)->join($join1)->select();
			//相同退货单产品数据合到一起
			foreach ($res as $key => $value) {
				$listS[$value['dg_id']]['goods_price'] += formatPrice($value['goods_price']);
				$listS[$value['dg_id']]['goods_number'] += $value['goods_number'];
			}
			//循环生成退货单产品信息
			foreach ($list as $key => $value) {
			    if($value['dg_id']){
			        $info = $goodsList[$value['dg_id']];//有发货退货
    				$Arr['return_id'] = $return_id;
    				$Arr['order_id'] = $order_id;
    				$Arr['delivery_id'] = $delivery_id;
    				$Arr['og_id'] = $info['og_id'];
    				$Arr['dg_id'] = $info['dg_id'];
    				$Arr['goods_type'] = $info['goods_type'];
					$Arr['agent_id'] = C('agent_id');
    				//是全部退货还是按数量退货
    				if($value['goods_number']){
    					//总价除以总数量*退货数量=退货价格
    					$Arr['goods_number'] = $value['goods_number'];
    				}else{
    					$Arr['goods_number'] = $info['goods_number'];
    				}
    				//还可退货数量
    				$residue = $info['goods_number'] - $listS[$value['dg_id']]['goods_number'];
    				if(round($residue,3) < round($Arr['goods_number'],3)){
    					$this->error('对不起，已经没有那么多货品可以退货了');
    				}
    				$Arr['goods_price'] = $info['goods_price']/$info['goods_number']*$Arr['goods_number'];
    				$goods_price += $Arr['goods_price'];
    				$goodsArr[] = $Arr;
			    }
			}
			$ORG = M('order_return_goods');
			if(!$ORG->addAll($goodsArr)){
				$OR->rollback();
				$this->error('添加退货单产品失败!');
			}
			//生成财务应退记录
			$compensate['order_id'] = $order_id;
			$compensate['return_id'] = $return_id;
			$compensate['period_type'] = 21;
			$compensate['uid'] = $orderInfo['uid'];
			$compensate['tid'] = $orderInfo['tid'];
			$compensate['compensate_price'] = $goods_price;
			$compensate['payment_price'] = '0';
			$compensate['compensate_note'] = '退货退款';
			$compensate['compensate_status'] = '1';
			$compensate['parent_type'] = $orderInfo['parent_type'];
			$compensate['parent_id'] = $orderInfo['parent_id'];
			$compensate['create_time'] = time();
			$compensate['agent_id'] = C('agent_id');
			$OC = M('order_compensate');
			if(!$OC->add($compensate)){
				$OR->rollback();
				$this->error('添加应退款记录失败!');
			}
			//修改退货金额
			$res = $OR->where('agent_id = '.C('agent_id').' and return_id ='.$return_id)->setField('goods_price',$goods_price);
			if(!$res){
				$OR->rollback();
				$this->error('修改退货金额失败!');
			}
			$OR->commit();
			$notes = '操作了一次退货,退货单ID:'.$return_id;
			$this->orderLog($order_id, $notes, '退货申请成功！请等待审核通过');
		}
	}

	/**
	 * 退货单列表
	 * @param int $return_id
	 */
	public function orderReturnList($p=1,$n=13,$order_sn=0,$create_time=0,$status=4){
		$OR = M('order_return');
		$owhere = ' and '.$this->buildWhere('o.');
		//订单编号筛选条件
        if($order_sn){
			$order_sn = addslashes($order_sn);
            $owhere .=" and o.order_sn like '%".$order_sn."%'";
            $this->order_sn = $order_sn;
        }
		//退货时间筛选条件
        if($create_time){
            $time = strtotime(I('get.create_time'));
            $time1 = $time + 60*60*24;
            $where .= ' zor.create_time > '.$time.' and zor.create_time < '.$time1.' and ';
            $this->create_time = $create_time;
        }
		//收货状态筛选条件
        $this->status = $status;
        if($status == 1){
            $where .='zor.status = 1 and ';
        }else if($status == 2){
            $where .='zor.status = 2 and ';
        }else if($status == 3){
			 $where .=' zor.status = 3 and ';
		}elseif($status == 0){
            $where .=' zor.status = 0 and ';
        }
		$where .= ' zor.agent_id = '.C('agent_id');
		//客户筛选
        if(I('get.userName')){
            $U = M('user');
            $T = M('trader');
            $name   = addslashes(I('get.userName'));
            $uWhere = "(username like '%$name%' or realname like '%$name%' ) and agent_id = ".C('agent_id');
            $list1  = $U->where($uWhere)->field('uid')->select();
            $tWhere = "trader_name like '%$name%' and agent_id = ".C('agent_id');
            $list2  = $T->where($tWhere)->field('tid')->select();
            foreach ($list1 as $key => $value) {
                if($key == 0 and !empty($value)){
                    $str1 = $value['uid'];
                }elseif ($key){
                    $str1 .= ','.$value['uid'];
                }
            }
            if($str1)$where .= ' and (zor.uid in ('.$str1.')';
            foreach ($list2 as $key => $value) {
                if($key == 0 and !empty($value)){
                    $str2 = $value['tid'];
                }elseif ($key){
                    $str2 .= ','.$value['tid'];
                }
            }
            if($str1) $jx = ' or ';
            else $jx = ' and (';
            if($str2) $where .= $jx.'zor.tid in ('.$str2.') )';
            elseif ($str1)$where .= ')';
            $this->userName = I('get.userName');
        }
		if($this->is_yewuyuan){
			$where .=' and zor.parent_id = '.$this->uid.'';
		}
		//多表查询
		$join[] = 'LEFT JOIN zm_user AS u ON u.uid = zor.uid';//查看对应的用户信息
		$join[] = 'LEFT JOIN zm_trader AS t ON t.tid = zor.tid';//查看对应的分销商信息
		$join[] = 'JOIN zm_order AS o ON o.order_id = zor.order_id'.$owhere;//查看退货单对应订单信息
		$feild = 'zor.*,u.username,u.realname,t.trader_name,o.order_sn';
		//分页
		$count = $OR->alias('zor')->join($join)->count();
		$Page = new Page($count,$n);
		$this->page = $Page->show();
		$list = $OR->alias('zor')->join($join)->field($feild)->where($where)->limit($Page->firstRow,$Page->listRows)->order('return_id desc')->select();
		foreach ($list as $key => $value) {
			if($value['uid']){
				$value['order_type'] = '客户退货';
				$value['obj'] = $value['realname']?$value['realname']:$value['username'];
				$value['objUrl'] = '';
			}else{
				$value['order_type'] = '采购退货';
				$value['obj'] = $value['trader_name'];
			}
			$array[] = $value;
		}
		$this->List = $array;
		$this->display();
	}

	/**
	 * 查看退货单
	 */
	public function orderReturnInfo($return_id){
		//退货单信息
		$OR = M('order_return');
		$join[] = 'JOIN zm_order AS o ON o.order_id = zor.order_id';
		$this->info = $OR->where('zor.agent_id = '.C('agent_id'))->alias('zor')->join($join)->field('zor.*,o.order_sn')->find($return_id);
		//退货单产品信息
		$this->goodsList = $this->getOrderReturnGoodsData($return_id);
		//获取退货单退款信息
		$this->compensateList = $this->getOrderCompensate($return_id);
		$this->display();
	}

	/**
	 * 获取退货单退款信息
	 * @param int $return_id
	 */
	protected function getOrderCompensate($return_id){
	    $OC = M('order_compensate');
	    $list = $OC->where('agent_id = '.C('agent_id').' and return_id ='.$return_id)->select();
	    return $list;
	}

	/**
	 * 获取退货单产品
	 * @param int $return_id
	 */
	protected function getOrderReturnGoodsData($return_id){
		$ORG = M('order_return_goods');
		$join[] = 'JOIN zm_order_goods AS og ON og.og_id = org.og_id';
		$field = 'og.attribute,org.*';
		$list = $ORG->alias('org')->where('org.agent_id = '.C('agent_id').' and return_id='.$return_id)->join($join)->field($field)->select();
		foreach ($list as $key => $value) {
			$value['goodsInfo'] = unserialize($value['attribute']);
			if($value['goods_type'] == 1){//证书货
				$info = $value['goodsInfo'];
				$value['goods_number'] = intval($value['goods_number']);
				$value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
			    $goodsList['luozuan'][] = $value;
			}elseif ($value['goods_type'] == 2){//散货
				//4c信息
				$info = $value['goodsInfo'];
				$value['4c'] = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
			    $goodsList['sanhuo'][] = $value;
			}elseif ($value['goods_type'] == 3){//珠宝产品(成品)
			    $info = unserialize($value['attribute']);
			    $sku = $this->getGoodsSku($info['category_id'], $info['goods_id'], $info['sku_sn'], $info['goods_sku']['attributes']);
			    $value['4c'] = $sku['sku'];
			    $value['goods_number'] = intval($value['goods_number']);
			    $goodsList['goods'][] = $value;
			}elseif($value['goods_type'] == 4){//珠宝产品(定制)
			    $value['4c'] = $this->getJGSdata($value['goodsInfo']);
			    $value['goods_number'] = intval($value['goods_number']);
			    $goodsList['goods'][] = $value;
			}
			$goodsList[] = $value;
		}
		return $goodsList;
	}

	/**
	 * 取消退货<br>
	 * 取消退货只是改变退货单的状态
	 * @param int $return_id
	 */
	public function quxiaoOrderReturn($return_id){
		$OC = M('order_compensate');
		$OC->startTrans();
		//查看退款记录是否有核销
		$list = $OC->where('agent_id = '.C('agent_id').' and return_id = '.$return_id)->select();
		foreach ($list as $key => $value) {
			if($value['compensate_status'] == 2 or $value['compensate_status'] == 3){
				$OC->rollback();
				$this->error('当前订单有退款记录已核销或核销部分，不允许取消退货!');
			}
		}
		//没有核销记录，删除应退款记录
		$res = $OC->where('agent_id = '.C('agent_id').' and return_id = '.$return_id)->delete();
		if(!$res){
			$OC->rollback();
			$this->error('删除应退款记录失败!');
		}
		//改变退货单状态
		$data['status'] = 0;
		$data['return_id'] = $return_id;
		$OR = M('order_return');
		$res = $OR->save($data);
		if($res){
			$OC->commit();
			$this->success('取消退货成功');
		}else{
			$OC->rollback();
			$this->error('取消退货失败!');
		}
	}

	/**
	 * 审核退货单
	 * @param int $order_id
	 */
	public function checkOrderReturn($return_id){
		$OC = M('order_compensate');
		$where = 'agent_id = '.C('agent_id').' and return_id ='.$return_id;
		$list = $OC->where($where)->select();
		foreach ($list as $key => $value) {
			if($value['compensate_status'] != 3){
				$this->error('当前退货单还有应退款项没有核销');
			}else{
				//增加库存
                //@todo
				//改变退货状态
				$OR = M('order_return');
				$data['confirm_time'] = time();
				$data['status'] = 2;
				$res = $OR->where($where)->save($data);
				if($res){
					$this->success('审核退货成功!');
				}else{
					$this->error('审核退货失败!');
				}
			}
		}
	}

	/**
	 * 退货操作（货品确认）
	 * @param int $return_id
	 */
	protected function orderReturnConfirm($return_id){
	    $OR = M('order_return');
	    $where = 'agent_id = '.C('agent_id').' and return_id ='.$return_id;
	    $data['confirm_time'] = time();
	    $data['status'] = 3;
	    $res = $OR->where($where)->save($data);
	    if($res){
	        $this->success('退货货品确认成功!');
	    }else{
	        $this->error('退货货品确认失败!');
	    }
	}

	/**
	 * 订单退货操作(中间跳转方法)
	 */
	public function orderReturnAction($return_id){
	    //取消退货
	    if($_POST['quxiaoOrderReturn']){
	        $this->quxiaoOrderReturn($return_id);
	    }
        //退货货品确认
        if($_POST['orderReturnConfirm']){
            $this->orderReturnConfirm($return_id);
        }
        //退货货品确认
        if($_POST['checkOrderReturn']){
            $this->checkOrderReturn($return_id);
        }
	}

	/**
	 * 订单发货(发货给客户)
	 * @param int $order_id
	 */
	public function orderDelivery($order_id){

		$OD  = M('order_delivery');
		$O   = M('order');
		$OG  = M('order_goods');
		$ODG = M('order_delivery_goods');
		$OD->startTrans();
		//获取基础数据
		$orderInfo = $O->where('agent_id = '.C('agent_id'))->find($order_id);
		$post = I('post.');
		//组装一张发货单数据
		$data['order_id'] = $order_id;
		$data['tid'] = $orderInfo['tid'];
		$data['uid'] = $orderInfo['uid'];
		$data['parent_type'] = $orderInfo['parent_type'];
		$data['parent_id'] = $orderInfo['parent_id'];
		$data['delivery_mode'] = 1;
		$data['create_time'] = time();
		$data['agent_id'] = C('agent_id');
		if($data['uid']){//发货给客户
			$data['address'] = serialize($orderInfo['address_info']);
		}else{//发货给分销商
			$data['address'] = '分销商客户地址!';
		}
		$delivery_id = $OD->add($data);
		//发裸钻货品给客户
		if(count($post['goodsLuozuan'])){
			foreach ($post['goodsLuozuan'] as $key => $value) {
				$ids .= $value.',';
			}
		}
		//发散货给客户
		if(count($post['goodsSanhuo'])){
			foreach ($post['goodsSanhuo'] as $key => $value) {
				if($value['og_id']){$ids .= $value['og_id'].',';}
			}
		}
		//发成品货给客户
		if(count($post['goods'])){
			foreach ($post['goods'] as $key => $value) {
				if($value['og_id']){$ids .= $value['og_id'].',';}
			}
		}
		//发代销货给客户
		if(count($post['consignment'])){
			foreach ($post['consignment'] as $key => $value) {
				if($value['og_id']){$ids .= $value['og_id'].',';}
			}
		}

		//发代销货给客户 szzmzb 板房
		if(count($post['consignment_szzmzb'])){
			foreach ($post['consignment_szzmzb'] as $key => $value) {
				if($value['og_id']){$ids .= $value['og_id'].',';}
			}
		}

		$ids = substr($ids, 0,strrchr($ids, ',')-1);
		if(!$ids){
			$this->error('请最少选中一个产品发货!');
		}
		
		

		
		//获取选中发货货品信息
		$list = $OG->where("agent_id = ".C('agent_id')." and og_id in($ids)")->select();
		//获取选中货品已发货信息
		$list2 = $ODG->where("agent_id = ".C('agent_id')." and og_id in($ids)")->select();
		//生成当前发货单产品信息
		$goods = $this->buildOrderDeliveryAmountGoods($order_id,$delivery_id,$post,$list,$list2);

		$res = $ODG->addAll($goods['list']);
		if(!$res){
			$OD->rollback();
			$this->log('error', 'A55');
		}else{
			//检测是否全部货品发货完成
			$res = $this->CheckOrderDeliveryAmountGoods($order_id);
			if($res){
				$orderData['order_status'] = 4;
				$orderData['order_delivery'] = 3;
				$orderData['update_time'] = time();
				$res = $O->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->save($orderData);
				if(!$res){
					$OD->rollback();
					$this->log('error', 'A55');
				}
			}
			//修改订单发货总价格
			$res = $OD->where('agent_id = '.C('agent_id').' and delivery_id = '.$delivery_id)->setField('goods_price',$goods['amount']);
			if(!$res){
				$OD->rollback();
				$this->log('error', 'A55');
			}

			if($_POST['is_delivery']=='1'){		
				if($_POST['type']=='0'){
					if(empty($_POST['company'])){
						$this->error('快递公司不能为空！');
					}
					if(empty($_POST['number'])){
						$this->error('运单号不能为空！');
					}
					$delivery_number=$_POST['number'];
				}else if($_POST['type']=='1'){
					if(empty($_POST['name'])){
						$this->error('配送人不能为空！');
					}
					if(empty($_POST['phone'])){
						$this->error('手机号不能为空！');
					}
 
					// $delivery_number='配送人手机号为'.$_POST['phone'];					
				}
				$_POST['ctime']=time();
				$_POST['delivery_id']=$delivery_id;
				$_POST['order_sn']	 =$orderInfo['order_sn'];
				$_POST['order_id']	 =$order_id;
				$_POST['agent_id']	 =C('agent_id');			
				$order_express=M('order_express')->data($_POST)->add();			
				
				if(!$order_express){
					$OD->rollback();
					$this->log('error', 'A55');
				}
			}
			$OD->commit();
			$notes = '操作发了一次货品，发货单ID：'.$delivery_id;
			//发货用户提醒
			$username = M("User")->where('uid='.$orderInfo['uid'])->getField('username');
			$title    = "商家发货";
			$content  = "亲爱的用户".$username.", 您的订单".$orderInfo['order_sn']." 已发货，请注意查收";
			$this    -> sendMsg($orderInfo['uid'],$content, $title,  1);
			$phone    = M("User")->where('uid='.$orderInfo['uid'])->getField('phone');
			//$s        = new \Common\Model\Sms();
			//$s       -> sendSms($phone,'send_out_goods_ok_send_user',C('agent_id'));
			if($phone){
				$SMS = new \Common\Model\Sms();
				if(isset($delivery_number)){
					$SMS->SendSmsByType($SMS::BUSINESS_DELIVER,$phone,array($orderInfo['order_sn'],$delivery_number));
				}
			}
			$this    -> orderLog($order_id, $notes, L('A54'));
		}
	}

	/**
	 * 检测是否全部货品发货完成
	 * @param int $order_id
	 * @return boolean
	 */
	protected function CheckOrderDeliveryAmountGoods($order_id){
		$OG = M('order_goods');
		$ODG = M('order_delivery_goods');
		$list = $OG->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->select();
		$list2 = $ODG->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->select();
		//把相同订单产品发货数量合到一起
		foreach ($list2 as $key => $value) {
			$array[$value['og_id']]['goods_price'] += formatPrice($value['goods_price']);
			$array[$value['og_id']]['goods_number'] += $value['goods_number'];
		}
		//检查每个订单产品发货情况
		foreach ($list as $key => $value) {
			$num1 = number_format($array[$value['og_id']]['goods_number'],3,'.','');//已发货数量
			$num2 = $value['goods_number_up'];//需要发货数量
			if($num1 != $num2){
				return false;
			}
		}
		return true;
	}

	/**
	 * 订单货品操作（里面包含单次发货，单次退货）
	 * @param int $order_id
	 */
	public function deliveryInfo($order_id){
		$info = $this->getOrderInfo($order_id);//查询订单信息
		if(!$info){ $this->error('当前订单不是您的或者订单错误！');}
		if(IS_POST){
			//订单发货
			if($_POST['orderDelivery']){
				$this->orderDelivery($order_id);
			}
			//订单退货
			if($_POST['orderReturn']){
			    //暂时不做订单无发货退货
				//$this->orderReturn($order_id);
			}
		}else{
			if($info['delivery_mode']){
				$info	 = D('Common/OrderPayment')->this_dispatching_way($info['delivery_mode'],$info);
			}	
			$this->orderInfo = $this->countOrder($info);//订单详情数据
			$goodsList = $this->getOrderGoodsList($order_id);//订单产品数据
			$goodsList = $this->countDeliveryGoods($goodsList);//处理销售数量为0的产品不显示
			$this->goodsList = $this->getOrderGoodsDeliveryList($goodsList,$order_id);//获取订单产品已发货数据列表
			if(empty($info['address_info'])){
				$this->addressInfo = D('Common/UserAddress')->getAddressInfo($info['address_id']);//收货地址
			}else{
				$this->addressInfo = $info['address_info'];//收货地址
			}
			
			$EI 				   		= new \Think\ExpressInter();
			$this->expert_data	    	= $EI->get_expert_data();
			$this->expert_order_data_0	= M('order_express')->where(' order_id = '.$order_id .' and type = 0 ')->select();			
			$this->expert_order_data_1	= M('order_express')->where(' order_id = '.$order_id .' and type = 1 ')->select();
			$this->display();
		}
	}

	/**
	 * 查看发货单信息
	 * @param int $delivery_id
	 */
	public function deliveryOrderInfo($delivery_id){
		$OD = M('order_delivery');
		$deliveryInfo = $OD->where('agent_id = '.C('agent_id'))->find($delivery_id);
		$this->deliveryInfo = $deliveryInfo;
		$order_id = $deliveryInfo['order_id'];
		//查询订单信息
		$info = $this->getOrderInfo($order_id);
		if(!$info){ $this->error('当前订单不是您的或者订单错误！');}
		if($info['delivery_mode']){
			$info	 = D('Common/OrderPayment')->this_dispatching_way($info['delivery_mode'],$info);
		}			
		//订单详情数据
		$this->orderInfo = $this->countOrder($info);
		//收货地址
		
		if(empty($info['address_info'])){
			$this->addressInfo = $this->getAddressInfo($info['address_id']);
		}else{
			$this->addressInfo = $info['address_info'];//收货地址
		}
		//发货单产品列表
		$this->goodsList = $this->getOrderDeliveryAmountGoods($delivery_id);
		$this->delivery_id = $delivery_id;
		//物流信息
		$this->expert_order_data_0	= M('order_express')->where(' delivery_id = '.$delivery_id .' and type = 0 ')->select();
		$this->expert_order_data_1	= M('order_express')->where(' delivery_id = '.$delivery_id .' and type = 1 ')->select();			
		$this->display();
	}

	/**
	 * 生成发货单货品信息
	 * @param int $res
	 * @param array $list
	 * @param array $list2
	 * @return array
	 */
	public function buildOrderDeliveryAmountGoods($order_id,$delivery_id,$post,$list,$list2){
		//把相同订单产品发货数量合到一起
		foreach ($list2 as $key => $value) {
			$array[$value['og_id']]['goods_price'] += formatPrice($value['goods_price']);
			$array[$value['og_id']]['goods_number'] += $value['goods_number'];
		}
		//发货总价格
		$amount = 0;
		//生成发货单产品记录

		foreach ($list as $key => $value) {
			$arr['delivery_id'] = $delivery_id;
			$arr['og_id'] = $value['og_id'];
			$arr['goods_type'] = $value['goods_type'];
			$arr['order_id'] = $order_id;
			$arr['agent_id'] = C('agent_id');
			//计算单价
			$price = $value['goods_price_up']/$value['goods_number_up'];
			if($value['goods_type'] == 1){$num = 1;}
			if($value['goods_type'] == 2){$num = $post['goodsSanhuo'][$value['og_id']]['goods_number'];}
			//if($value['goods_type'] == 3 or $value['goods_type'] == 4){$num = $post['goods'][$value['og_id']]['goods_number'];}
			if($value['goods_type'] == 3 or $value['goods_type'] == 4){$num = $post['consignment'][$value['og_id']]['goods_number'];}
			if($value['goods_type'] == 16){ //szzmzb 板房
				$num = $post['consignment_szzmzb'][$value['og_id']]['goods_number'];
			}
			$arr['goods_number'] = $num;
			if($arr['goods_type'] == 3 or $arr['goods_type'] == 4 or $arr['goods_type'] == 5 or $arr['goods_type'] == 6){
			    if(!($num == intval($num))){
			        $this->error('珠宝产品发货数量必须等于整数');
			    }
			}
			
			//当前发货金额
			$arr['goods_price'] = formatPrice($price*$num);
			//总发货数量
			$tnum = $value['goods_number_up'];
			//已发货数量
			$ynum = $array[$value['og_id']]['goods_number'];


			if($array[$value['og_id']] and (round($tnum-$ynum, 3) < $num)){//有发货过,剩余发货数量小于当前的发货数量
			    $this->error('当前货品:'.$value['certificate_no'].'已经没有那么多重量要发货!');
			}elseif ($tnum < $num){//无发货过,发货数量大于总发货量
			    $this->error('当前货品:'.$value['certificate_no'].'不需要发那么多重量!');
			}
			$amount += $arr['goods_price'];
			$goods['list'][] = $arr;
		}
		$goods['amount'] = formatPrice($amount);
		return $goods;
	}

	/**
	 * 获取订单发货单产品详细
	 * @param int $delivery_id
	 */
	protected function getOrderDeliveryAmountGoods($delivery_id){
		//获取发货情况
		$ODG = M('order_delivery_goods');
		$field  = 'og.*,odg.goods_price AS delivery_price,odg.goods_number AS delivery_number,odg.dg_id';
		$join[] = 'zm_order_goods AS og ON og.og_id = odg.og_id';
		$list   = $ODG->alias('odg')->where('odg.agent_id = '.C('agent_id').' and delivery_id = '.$delivery_id)->field($field)->join($join)->select();
 
		
		//获取发货单产品退货情况
		$ORG = M('order_return_goods');
		$join1[] = 'JOIN zm_order_return AS zor ON zor.status <> 0 and zor.return_id = org.return_id';
		$res = $ORG->alias('org')->where('org.agent_id = '.C('agent_id').' and org.delivery_id = '.$delivery_id)->join($join1)->select();
		//相同退货单产品数据合到一起
		foreach ($res as $key => $value) {
			$listS[$value['dg_id']]['goods_price'] += formatPrice($value['goods_price']);
			$listS[$value['dg_id']]['goods_number'] += $value['goods_number'];
		}
		//循环遍历数据
		foreach ($list as $key => $value) {
			$value['attribute'] = unserialize($value['attribute']);
			$info = $value['attribute'];//订单产品信息
			//证书货数据
			if($value['goods_type'] == 1){
				$value['4c'] = '('.$info['weight'].'/'.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
				$value['delivery_number'] = intval($value['delivery_number']);
				if($listS[$value['dg_id']]){
					$value['is_return'] = '1';
					$value['return_number'] = intval($listS[$value['dg_id']]['goods_number']);
					$value['goods_price'] = $listS[$value['dg_id']]['goods_price'];
				}else{
					$value['is_return'] = '3';
					$value['return_number'] = '0';
					$value['goods_price'] = 0;
				}
				$arr['luozuan'][] = $value;
			}
			//成品货数据
			if($value['goods_type'] == 3 or $value['goods_type'] == 4){
			    if($value['goods_type'] == 3){
			        $attributes = $info['goods_sku']['attributes'];//使用订单里面的SKU规格，防止修改产品
			        $sku = $this->getGoodsSku($info['category_id'],$info['goods_id'],$info['sku_sn'],$attributes);
			        $value['4c'] = $sku['sku'];
			    }
                if($value['goods_type'] == 4){
                    $value['4c'] = $this->getJGSdata($info);
                }
			    $value['delivery_number'] = intval($value['delivery_number']);
				if($listS[$value['dg_id']]){
				    if($listS[$value['dg_id']]['goods_number'] < $value['delivery_number']){
				        $value['is_return'] = '2';
				        $value['return_number'] = $listS[$value['dg_id']]['goods_number'];
				        $value['goods_price'] = intval($listS[$value['dg_id']]['goods_price']);
				        $value['residue'] = $value['delivery_number']-$value['return_number'];
				    }else{
				        $value['is_return'] = '1';
				        $value['return_number'] = intval($listS[$value['dg_id']]['goods_number']);
				        $value['goods_price'] = intval($listS[$value['dg_id']]['goods_price']);
				        $value['residue'] = $value['delivery_number']-$value['return_number'];
				    }
				}else{
				    $value['is_return'] = '3';
				    $value['return_number'] = '0';
				    $value['goods_price'] = 0;
				    $value['residue'] = $value['delivery_number'];
				}
				$arr['goods'][] = $value;
			}
			//散货数据
			if($value['goods_type'] == 2){
				if($listS[$value['dg_id']]){
					if($listS[$value['dg_id']]['goods_number'] < $value['delivery_number']){
						$value['is_return'] = '2';
						$value['return_number'] = $listS[$value['dg_id']]['goods_number'];
						$value['goods_price'] = intval($listS[$value['dg_id']]['goods_price']);
						$value['residue'] = $value['delivery_number']-$value['return_number'];
					}else{
						$value['is_return'] = '1';
						$value['return_number'] = intval($listS[$value['dg_id']]['goods_number']);
						$value['goods_price'] = intval($listS[$value['dg_id']]['goods_price']);
						$value['residue'] = $value['delivery_number']-$value['return_number'];
					}
				}else{
					$value['is_return'] = '3';
					$value['return_number'] = '0';
					$value['goods_price'] = 0;
					$value['residue'] = $value['delivery_number'];
				}
				//4c信息
				$value['4c']     = '('.$info['color'].'/'.$info['clarity'].'/'.$info['cut'].')';
				$arr['sanhuo'][] = $value;
			}
			//代销货数据
			if($value['goods_type'] == 5 or $value['goods_type'] == 6){
				if($listS[$value['dg_id']]){
					if($listS[$value['dg_id']]['goods_number'] < $value['delivery_number']){
						$value['is_return']     = '2';
						$value['return_number'] = intval($listS[$value['dg_id']]['goods_number']);
						$value['goods_price']   = intval($listS[$value['dg_id']]['goods_price']);
						$value['residue']       = $value['delivery_number']-$value['return_number'];
					}else{
						$value['is_return']     = '1';
						$value['return_number'] = intval($listS[$value['dg_id']]['goods_number']);
						$value['goods_price']   = intval($listS[$value['dg_id']]['goods_price']);
						$value['residue']       = $value['delivery_number']-$value['return_number'];
					}
				}else{
					$value['is_return']     = '3';
					$value['return_number'] = '0';
					$value['goods_price']   = 0;
					$value['residue']       = intval($value['delivery_number']);
				}
				$value['delivery_number'] = round($value['delivery_number']);
				if($value['goods_type'] == 5){
				    $value['4c'] = $this->getJGSdata($info);
				}elseif ($value['goods_type'] == 6){
				    $spec = $this->getSpecData($info['specification_id']);
				    $value['4c'] = $spec['4c'];
				}
				$arr['consignment'][] = $value;
			}
		}
		return $arr;
	}

	/**
	 * 获取订单产品已发货数据
	 * @param array $goodsList
	 * @param int $order_id
	 * @return Ambigous <number, unknown>
	 */
	private function getOrderGoodsDeliveryList($goodsList,$order_id){
		$ODG = M('order_delivery_goods');
		$list = $ODG->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->select();('og_id,goods_price,goods_number');
		//相同订单产品发货数据合到一起
		foreach ($list as $key => $value) {
			$array[$value['og_id']]['goods_price'] += formatPrice($value['goods_price']);
			$array[$value['og_id']]['goods_number'] += $value['goods_number'];
		}
		//判断发货状态，剩余发货数量和金额
		foreach ($goodsList as $key => $value) {
			foreach ($value as $k => $v) {
				$num = $array[$v['og_id']]['goods_number'];
				$goodsList[$key][$k]['delivery_num'] = $num?$num:0;
				$residue = round($v['goods_number_up'] - $num,3);
				$residue_price = formatPrice($v['goods_price_up']/$v['goods_number_up']*$residue);
				if($num and $residue==0){
					$goodsList[$key][$k]['delivery_status'] = '3';
					$goodsList[$key][$k]['delivery_string'] = '已发货';
					$goodsList[$key][$k]['delivery_price'] = $residue_price;
				}elseif($num and $residue>0){
					$goodsList[$key][$k]['delivery_status'] = '2';
					$goodsList[$key][$k]['delivery_string'] = '部分发货';
					$goodsList[$key][$k]['residue'] = $residue;
					$goodsList[$key][$k]['delivery_price'] = $residue_price;
				}else{
					$goodsList[$key][$k]['delivery_status'] = '1';
					$goodsList[$key][$k]['delivery_string'] = '未发货';
					$goodsList[$key][$k]['residue'] = $residue;
					$goodsList[$key][$k]['delivery_price'] = $residue_price;
				}
			}
		}
		return $goodsList;
	}

	/**
	 * 处理发货产品,把数量或者重量为0的干掉
	 * @param array $array
	 * @return array
	 */
	private function countDeliveryGoods($array){
		foreach ($array as $key => $value) {
			foreach ($value as $k => $v) {
				if($v['goods_number_up'] <= 0){
					unset($array[$key][$k]);
				}
			}
		}
		return $array;
	}

	/**
	 * 提取能进行的操作/订单操作验证
	 * @param array $info
	 */
	public function checkAction($list,$action_key=0){
		$action = M('order_action')->getField('action_id,action_key,action_name_cn,action_name_en,note');
		// 有操作关键词，统一对操作进行权限验证
		if($action_key){
			foreach($action as $v){if($v['action_key'] == $action_key) return true;}
			return false;
		}
		// 订单操作
		$actionList[] = $action[1];
		if($list['order_status'] == 0){//下单后
			$actionList[] = $action[8];//取消订单
			$actionList[] = $action[2];//货品确认
		}elseif($list['order_status'] == 1){//确认后
			$actionList[] = $action[4];//订单支付
			$actionList[] = $action[15];//取消货品确认
			$actionList[] = $action[11];//支付确认
		}elseif($list['order_status'] == 2){//支付后
			$actionList[] = $action[4];//订单支付
			$actionList[] = $action[12];//配货完成
		}elseif($list['order_status'] == 3){//配货后
			$actionList[] = $action[4];//订单支付
			$actionList[] = $action[15];//取消货品确认
			$actionList[] = $action[16];//订单补差价
			$actionList[] = $action[6];//发货给客户
		}elseif($list['order_status'] == 4){//发货后
			$actionList[] = $action[4];//订单支付
			$actionList[] = $action[14];//确认已发货
		}elseif($list['order_status'] == 5){//收货后
			$actionList[] = $action[4];//订单支付
			$actionList[] = $action[13];//订单完成
		}
		//权限验证
		foreach ($actionList as $key => $value) {
			$url = MODULE_NAME . '/' . CONTROLLER_NAME.'/'.$value['action_key'];
			if($this->checkActionAuth($url)){
				$actionArr[$key] = $value;
			}
		}
		return $actionArr;
	}

	/**
	 * 计算订单相关状态
	 * @param array $list 订单数组
	 * @param int $type 数组类型，单个订单/2多个订单
	 * 依赖管理员用户组id,$this->group,保证必须
	 */
	private function countOrder($list){
		// 单个订单和多个订单检查
		if(!empty($list['order_id'])){
			//订单金额
			if($list['order_status'] == 0 or $list['order_status'] == -1 or $list['order_status'] == -2){
				$list['order_price'] = $list['order_price'];
			}else{
				$list['order_price'] = $list['order_price_up'];
			}
			//订单对象，订单类型
			if($list['is_erji_caigou'] ==0){
				$list['order_type'] = '客户订单';
				$list['obj'] = empty($list['realname'])?$list['username']:$list['realname'];
				$list['objUrl'] = U('Admin/User/userInfo?uid='.$list['uid']);
			}else{
				$list['order_type'] = '采购单';
			//	$list['obj'] = $list['company_name'];
			//	$list['objUrl'] = U('Admin/Trader/traderInfo?tid='.$list['trader_id']);
			}
			$list['obj'] = empty($list['realname'])?$list['username']:$list['realname'];
			$list['objUrl'] = U('Admin/User/userInfo?uid='.$list['uid']);

			//订单状态检查
			if($list['order_status'] == 0){//确认状态判断
				$list['status'] = L('A01');
			}elseif($list['order_status'] == 1){//支付状态判断
				$list['status'] = L('A05');
			}elseif ($list['order_status'] == 2){//已支付，配货中
				if($list['uid']){//直批客户单
					$list['status'] .= L('A06');
				}else{//分销商客户单
					$list['status'] .= L('A08');
				}
			}elseif ($list['order_status'] == 3){//发货状态判断
				$list['status'] .= L('A010');
			}elseif ($list['order_status'] == 4){//已发货给客户，待确认
				$list['status'] .= L('A014');
			}elseif($list['order_status'] == 5){//已确认收货
				$list['status'] .= L('A015');
			}elseif($list['order_status'] == 6){//已完成订单
				$list['status'] .= L('A024');
			}elseif($list['order_status'] == -1){//已取消订单
				$list['status'] .= L('A022');
			}elseif($list['order_status'] == -2){//已删除订单
				$list['status'] .= L('A023');
			}elseif($list['order_status'] == 8){//已删除订单
				$list['status'] .= L('A023');
			}else{//其他错误状态
				$list['status'] = L('A038');
			}
			return $list;
		}else{
			foreach ($list AS $array){
				$arr[] = $this->countOrder($array);
			}
			return $arr;
		}
	}

	/**
	 * 订单操作（发货方确认已发货）
	 * @param int $order_id
	 */
	protected function confirmDelivery($order_id){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'confirmDelivery';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		$O = M('order');
		$info = $O->where('agent_id = '.C('agent_id'))->find($order_id);
		$O->startTrans();
		$data['order_status'] = 5;
		$where = 'order_id = '.$order_id;
		if($O->where($where)->save($data)){
			$OD = M('order_delivery');
			$update['confirm_type'] = 1;
			$update['confirm_time'] = time();
			if($OD->where($where)->save($update)){
				$O->commit();
			    $this->orderLog($order_id, '后台操作全部货品已经发货', L('A60'));
			}else{
				$O->rollback();
				$this->error(L('A61'));
			}
		}else{
			$O->rollback();
			$this->error(L('A61'));
		}
	}

	/**
	 * 采购单操作(收货方确认收货)
	 * @param int $order_id
	 */
	public function confirmDelivery2($order_id){
		$O = M('order');
		$info = $O->where('uid='.get_create_user_id().' and agent_id = '.get_parent_id())->find($order_id);
		$O->startTrans();
		$data['order_status'] = 5;
		$data['update_time'] = time();
		$where = 'agent_id = '.get_parent_id().'  and order_id = '.$order_id;
 
		if(!$info){
			//一级到官网的确认收货
			$OD			= M('order_delivery','zm_','ZMALL_DB');
			$O			= M('order','zm_','ZMALL_DB');			
			$where 		= 'tid = '.C('agent_id').' and uid='.get_create_user_id().'  and order_id = '.$order_id;
			$info 		= $OD->where($where)->find();
			if(!$info){
				$this->error('没有这张发货单，你不能操作收货!');
			}
			
			if($info['confirm_type'] == 0){
				if($O->where('agent_id = '.C('agent_id').' and order_id = ' .$order_id)->save($data)){
					$confirm_type_data['confirm_type'] = 2;
					$confirm_type_data['confirm_time'] = time();
					$res = $OD->where($where)->save($confirm_type_data);
					if($res){
						$O->commit();
						$this->success('操作采购单收货成功!');
					}
							
				}
			}else{
				$this->error('当前发货单已经收货!');
			}
		}elseif($info['uid'] == get_create_user_id()){
			//if($this->tid == $info['tid']){
			if($O->where($where)->save($data)){
				$OD = M('order_delivery');
				$update['confirm_type'] = 2;
				$update['confirm_time'] = time();
				if($OD->where($where)->save($update)){
					$O->commit();
					$this->success(L('A69'));
				}else{
					$O->rollback();
					$this->error(L('A70'));
				}
			}else{
				$O->rollback();
				$this->error(L('A70'));
			}
		}else{
			$this->error(L('A71'));
		}
	}

	/**
	 * 订单操作（订单确认付款）
	 * @param int $order_id
	 */
	public function confirmPay($order_id){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'confirmPay';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		$OR = M('order_receivables');
		$O = M('order');
		$field = 'oy.receivables_price,oy.payment_price,oy.receivables_status';
		$array = $OR->alias('oy')->field($field)->where('oy.agent_id = '.C('agent_id').' and oy.order_id = '.I('get.order_id').' and oy.period_current = 1')->select();
		foreach ($array as $key => $value) {
			if($value['receivables_status'] != 3){
				$this->log('error', 'A49');
			}
			if($value['receivables_status'] == $value['payment_price']){
				$this->log('error', 'A49');
			}
		}
		$data['order_status'] = 2;
		$data['order_payment'] = 1;
		$data['update_time'] = time();
		$res = $O->where('agent_id = '.C('agent_id').' and order_id = '.I('get.order_id'))->save($data);
		if($res){
		    $this->orderLog($order_id, '操作了订单确认支付', L('A50'));
		}else{
			$this->log('error', 'A51');
		}
	}

	/**
	 * 订单操作（订单完成）（在2015年10月13由郭冠常把钻明的替换过来）
	 * @param int $order_id
	 */
	public function completeAction($order_id){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'completeAction';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		header("Content-type: text/html; charset=utf-8");
		$where = $this->buildWhere().' and order_id = '.$order_id;//公共条件
		$where2 = 'agent_id = '.C('agent_id').' and order_id = '.$order_id;//公共条件2
		$O = M('order');//订单表
		$OR = M('order_receivables');//应收款
		$OP = M('order_payment');//实际收款
		$OC = M('order_compensate');//应退款
		$OR2 = M('order_refund');//实际退款
		$OG = M('order_goods');//订单产品表
		$OD = M('order_delivery');//发货单
		$ODG = M('order_delivery_goods');//发货单产品
		$OR3 = M('order_return');//退货单
		$ORG = M('order_return_goods');//退货单产品
		//获取订单状态，进行验证
		$info = $O->where($where)->find();
		if($info['order_status'] != 5){
			$this->error('当前订单现在还不可以完成!');
		}
		//获取订单应收总金额
		$receivables_price = 0;
		$hexiaoPrice = 0;
		$otherPrice = 0;//其他收款
		$list = $OR->where($where)->select();
		foreach ($list as $key => $value) {
			$receivables_price += $value['receivables_price'];
			$hexiaoPrice += $value['payment_price'];
			if($value['period_type'] > 5){
				$otherPrice += $value['receivables_price'];
			}
		}
		//获取订单收款总金额(只获取已经审核的数据),获取截余金额
		$payment_price = 0;
		$more_price = 0;
		$list = $OP->where($where.' and payment_status = 2')->select();
		foreach ($list as $key => $value) {
			$more_price += $value['more_price'];
			$payment_price += formatPrice($value['payment_price'] + $value['discount_price']);
		}
		//获取订单应退款总金额
		$compensate_price = 0;
		$otherPrice2 = 0;//其他退款
		$list = $OC->where($where)->select();
		foreach ($list as $key => $value) {
			$compensate_price += $value['compensate_price'];
			if($value['period_type'] == 12){
				$otherPrice2 += $value['compensate_price'];
			}
		}
		//获取订单退款金额
		$refund_price = 0;
		$list = $OR2->where($where.' and refund_status = 2')->select();
		foreach ($list as $key => $value) {
			$refund_price += $value['refund_price'];
		}
		//所有的款项泪数据核对
		//核销的金额等于实际付款的
		if(round($hexiaoPrice,2) != round($receivables_price,2)){
			$this->error('当前订单还有应付款项没有核销');
		}
		//核销金额等于（实际支付的金额+应退款的金额-实际退款的金额-截余金额）
		if(round($hexiaoPrice,2)  != round(($payment_price + $compensate_price - $refund_price - $more_price),2)){
			$this->error('当前订单应付款核销金额和实际收款金额不匹配,有截余金额导致请找IT部同事修复数据!');
		}
		//订单产品数据
		$ogList = $OG->where($where)->select();
		//获取订单发货单情况,发货单产品数据，发货单总价格，发货单产品总价格
		$odList = $OD->where($where)->select();//发货单
		$orderDeliveryPrice = 0;//发货总价格
		foreach ($odList as $key => $value) {
			if($value['confirm_type'] != 0){
				$orderDeliveryPrice += $value['goods_price'];
			}else{
				$this->error('当前订单还有发货单没有收货!');
			}
		}
		$odgList = $ODG->where($where2)->select();//发货单产品
		$orderDeliveryGoodsPrice = 0;//发货单产品总价格
		foreach ($odgList as $key => $value) {
			$orderDeliveryGoodsPrice += $value['goods_price'];
		}
		//发货金额核对
		if(round($orderDeliveryPrice,2) != round($orderDeliveryGoodsPrice,2)){
			$this->error('当前订单发货单金额和发货单产品金额不一致!');
		}
		//检查订单退货情况，退货单产品数据,退货单总价格，退货单产品总价格
		$orList = $OR3->where($where)->select();//退货单
		$orderReturnPrice = 0;//退货单总价格
		foreach ($orList as $key => $value) {
			if($value['status'] == 2){
				$orderReturnPrice += $value['goods_price'];
			}
			if($value['status'] == 1 or $value['status'] == 3){
				$this->error('当前订单还有退货单没有完成!');
			}
		}
		$join = 'zm_order_return AS or3 ON or3.status = 2 and or3.return_id = org.return_id';
		$orgList = $ORG->alias('org')->join($join)->where('org.agent_id = '.C('agent_id').' and org.order_id = '.$order_id)->field('org.*')->select();//退货单产品
		$orderReturnGoodsPrice = 0;//退货单产品总价格
		foreach ($orgList as $key => $value) {
			$orderReturnGoodsPrice += $value['goods_price'];
		}
		//退货金额核对
		if(round($orderReturnPrice,2) != round($orderReturnGoodsPrice,2)){
			$this->error('当前订单退货单金额和退货单产品金额不一致!');
		}
		//最终金额核对
		if(round(($payment_price - $refund_price - $otherPrice + $otherPrice2 - $more_price),2) !=  round(($orderDeliveryPrice - $orderReturnPrice),2)){
			$this->error('当前订单实际款项错误!');
		}
		//以下算法不包括2015年10月8号新增的截余金额，在上面的的程序里已经加上了
// 	    dump('应收款:'.$receivables_price);
// 	    dump('其他应收款:'.$otherPrice);
// 	    dump('实际核销款:'.$hexiaoPrice);
// 	    dump('实际收款:'.$payment_price);
// 	    dump('应退款:'.$compensate_price);
// 	    dump('其他应退款:'.$otherPrice2);
// 	    dump('实际退款:'.$refund_price);
// 	    dump('实际核销:' . round(($payment_price + $compensate_price - $refund_price),2));
// 		dump('实际发货单价格:'.$orderDeliveryPrice);
// 		dump('实际发货产品价格:'.$orderDeliveryGoodsPrice);
// 		dump('实际退货单价格:'.$orderReturnPrice);
// 		dump('实际退货产品价格:'.$orderReturnGoodsPrice);
// 		dump('订单实际货品价格:'.round(($orderDeliveryPrice - $orderReturnPrice),2));
// 		dump('订单实际收到款项:'.round(($payment_price - $refund_price - $otherPrice + $otherPrice2),2));//实际收款-实际退款-其他应收款+其他应退款
// 		dump('订单货品实际款项:'.round(($orderDeliveryPrice - $orderReturnPrice),2));//发货金额-退货金额
// 		die();
		//操作订单状态
		$data['order_status'] = 6;
		$data['update_time'] = time();
		$res = $O->where('agent_id = '.C('agent_id').' and order_id = '.I('get.order_id'))->save($data);
		if($res){
			//订单有截余金额
			if(round($more_price) > 0){
				$more = ',当前订单有截余金额&yen;'.$more_price;
			}
			$notes = '操作订单完成，所有的款项核对成功，所有的发货单退货单核对成功'.$more.'!';
			//发送订单完成信息
			$username = M("User")->where('uid='.$info['uid'])->getField('username');
			$title = "订单完成";
			$content = "亲爱的用户".$username." 您的订单".$info['order_sn']." 已完成，快快去评价吧";
			$this->sendMsg($info['uid'],$content, $title,  1);
			$msg = L('A57');
			$this->orderLog($order_id, $notes, $msg);
		}else{
			$this->log('error', 'A58');
		}
	}

	/**
	 * 订单操作（配货完成）
	 * @param int $order_id
	 */
	public function purchaseAction($order_id){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'purchaseAction';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		$O = M('order');
		$data['order_status'] = 3;
		$data['update_time'] = time();
		$res = $O->where('agent_id = '.C('agent_id').' and order_id = '.I('get.order_id'))->save($data);
		if($res){

			$where = $this->buildWhere().' and order_id = '.$order_id;//公共条件
			$O = M('order');//订单表
			$info = $O->where($where)->find();
			//订单配货提醒信息
			$username = M("User")->where('uid='.$info['uid'])->getField('username');
			$title = "配货完成";
			$content = "亲爱的用户".$username." 您的订单".$info['order_sn']." 已配货完成";
			$this->sendMsg($info['uid'],$content, $title,  1);

		    $this->orderLog($order_id, '操作了订单配货', L('A52'));
		}else{
			$this->log('error', 'A53');
		}
	}

	/**
	 * 订单操作(取消订单)
	 * @param int $order_id
	 */
	public function quxiaoAction($order_id){
		$O = M('order');
		$info = $O->find(I('get.order_id'));
		//验证状态
		if($info['order_status'] == 0){
			$data['order_status'] = '-1';
			$data['update_time'] = time();
			if($O->where('agent_id = '.C('agent_id').' and order_id = '.I('get.order_id'))->save($data)){
				$info      = M("Order") -> where('order_id='.$order_id) -> find();
				if( $info['parent_id'] ){
					$phone = M("admin_user") -> where('user_id='.$info['parent_id']) -> getField('phone');
					if(!empty($phone)){
						$obj   = new \Common\Model\Sms();
						$obj  -> sendSms($phone,'order_cancel_send_admin', C('agent_id'));
					}
					
				}
				$this -> success('取消成功。',U('Admin/Order/orderList'));
			}else{
				$this -> error('取消出错!');
			}
		}else{
			$this     -> error('已确认后不可以取消');
		}
	}

	/**
	 * 订单操作(确认订单)
	 * @param int $order_id 订单id
	 * @param array $data 页面数据
	 */
	public function confirmAction($order_id,$data){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME.'/'.'confirmAction';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		//获取订单数据
		$O = M('order');
		$info = $O->where('agent_id = '.C('agent_id'))->find($order_id);//单条订单数据
		$goodsLuozuan = $data['goodsLuozuan'];//裸钻产品数据
		$goodsSanhuo  = $data['goodsSanhuo'];//散货产品数据
		$goods        = $data['goods'];//成品产品数据
		$consignment  = $data['consignment'];//代销货数据
		$consignment_szzmzb = $data['consignment_szzmzb'];//代销货数据 szzmzb 板房数据
		$goodsList    = $this->getOrderGoodsList($order_id);//订单产品数据（来自数据库）
		$agent_father = get_parent_id();
		//订单操作验证
		if($this->checkAction($info,'confirmAction')){
			
	
		 
			
			$order_price_up = 0;//订单总价格
			$receivables_price = 0;//应付总价格
			$O->startTrans();//开始事务
			$OG = M('order_goods');
			$OR = M('order_receivables');
 			//代销货产品验证
			if(count($consignment)>0 or count($goodsList['consignment']) >0){
				$order_consignment_price = 0;//订单代销货价格
				//页面发过来的数据和数据表裸钻数据个数对比
				if(count($goodsList['consignment']) != count($consignment)){
					$O->rollback();
					$this->log('error', 'A34');
				}
				//代销货确认订单修改订单产品信息
				$G  = D('Common/Goods');//实例化钻明产品表
				//$GAS = M('goods_associate_specification');//实例化钻明关联规格表
				$GL = M('goods_lock');//实例化代销货数量锁定表
				foreach ($goodsList['consignment'] AS $v){
				    $num = $consignment[$v['og_id']]['goods_num'];//购买的数量
				    if($v['goods_type'] == 4){
				        //不需要验证代销定制货品的库存
				    }elseif ($v['goods_type'] == 3 and $num > 0){
				        // if($this->tid){//分销商确认成品
				        //     $specification_id = $v['attribute']['specification_id'];
				        //     $where = 'goods_id = '.$v['goods_id'].' and tid = '.$this->tid.' and specification_id = '.$specification_id;
				        //     $number1  = $GL->where($where)->getField('goods_number');//获取锁定的代销货库存
				        //     $number2 = $number1 - $num;//扣库存
				        //     if($number2 < 0){
				        //         $O->rollback();
				        //         $this->error('代销货品:'.$v['goods_sn'].'没有这么多的货品');
				        //     }
				        //     //1级分销商的采购单处理
				        //     if(in_array(7, $this->group) and $info['tid']){
				        //         $res = $GL->where($where)->find();
				        //         if($res){
				        //             $goods_number = $res['goods_number'] + $num;
				        //             $res = $GL->where($where)->setField('goods_number',$goods_number);
				        //         }else{
				        //             //无记录增加一条记录
				        //             $GLData['goods_id'] = $v['goods_id'];
				        //             $GLData['goods_number'] = $num;
				        //             $GLData['tid'] = $info['tid'];
				        //             $GLData['specification_id'] = $specification_id;
				        //             $res = $GL->add($GLData);
				        //         }
				        //         if(!$res){
				        //             $O->rollback();
				        //             $this->error('采购单代销货品给下级分销商增加库存失败!');
				        //         }
				        //     }
				        //     //扣库存
				        //     $res = $GL->where($where)->setField('goods_number',$number2);
				        //     if(!$res){
			            //            $O->rollback();
			            //            $this->error('订单减库存失败!');
			            //        }
				        // }else{//钻明确认代销货成品
				            //库存验证
				            

				            $sku_sn     = $v['attribute']['goods_sku']['sku_sn'];
							$goods_info = $G -> get_info($v['goods_id']);
				            $number1    = $goods_info['goods_number'];
							$sku_info   = $G -> getGoodsSku($v['goods_id'],"sku_sn = '".$sku_sn."'");
				            $number2    = $sku_info['goods_number'];

				            if($goods_info['agent_id'] == C('agent_id')){  //不是自己的货，不减库存
				            	$number3 = $number1 - $num;//扣总库存
				            	$number4 = $number2 - $num;//扣规格库存
				            }else{
				            	$number3 = $number1;//扣总库存
				            	$number4 = $number2;//扣规格库存
				            }
				           

				            if($number3 < 0 or $number4 < 0){
				                $O    -> rollback();
				                $this -> error('代销货品:'.$v['goods_sn'].'没有这么多的货品');
				            }
				            //采购单把库存锁定给分销商
				            // if($info['tid']){
				            //     $where = 'goods_id = '.$v['goods_id'].' and tid = '.$info['tid'].' and specification_id = '.$specification_id;
				            //     $res = $GL->where($where)->find();
				            //     if($res){
				            //         $goods_number = $res['goods_number'] + $num;
				            //         $res = $GL->where($where)->setField('goods_number',$goods_number);
				            //     }else{
				            //         //无记录增加一条记录
				            //         $GLData['goods_id'] = $v['goods_id'];
				            //         $GLData['goods_number'] = $num;
				            //         $GLData['tid'] = $info['tid'];
				            //         $GLData['specification_id'] = $specification_id;
				            //         $res = $GL->add($GLData);
				            //     }
				            //     if(!$res){
				            //         $O->rollback();
				            //         $this->error('采购单代销货品给下级分销商增加库存失败!');
				            //     }
				            // }
				            //修改库存
				            if($goods_info['agent_id'] == C('agent_id')){  //不是自己的货，不减库存
					            $res1 = $G -> where('goods_id = '.$v['goods_id']) -> setField('goods_number',$number3);
					            $res2 = M('goods_sku') -> where("sku_sn = '".$sku_sn."' and goods_id = ".$v['goods_id']) -> setField('goods_number',$number4);

					            if(!$res1 or !$res2){
					                $O->rollback();
					                $this->error('货品扣库存出错');
					            }
					        }
				        //}
				    }
					//修改订单产品价格和数量语句
					$update['goods_price_up']  = $consignment[$v['og_id']]['price'];
					$update['goods_number_up'] = $consignment[$v['og_id']]['goods_num'];
					$update['update_time']     = time();
					$order_consignment_price  += $consignment[$v['og_id']]['price'];
					$res2 = $OG->where('og_id = '.$v['og_id'])->save($update);
					unset($update);
					if(!$res2){
						//失败事务回滚
						$O->rollback();
						$this->log('error', 'A22');
					}
				}
				//生成代销货订单应付信息
				if($data['consignmentPay'] == 1 and $order_consignment_price != 0){//做期支付
					//对发送过来的数据进行验证
					if(empty($data['consignment_period_num'])){
						$O->rollback();
						$this->log('error', 'A33','OrderId:'.$order_id);
					}
					//生成一条记录到订单分期表
					$OP = M('order_period');
					$periodArr['order_id'] = $order_id;
					$periodArr['period_type'] = 3;
					$periodArr['period_num'] = $data['consignment_period_num'];
					$periodArr['period_overdue'] = $data['consignment_period_overdue'];
					$periodArr['agent_id'] = C('agent_id');
					if($OP->add($periodArr)){
						$order_price = 0;//当前分期支付总价格
						foreach ($data['consignment_period'] as $key => $value) {
							// 分期时间或金额不能为空!
							if(empty($value['time']) or empty($value['price'])){
								$O->rollback();
								$this->log('error', 'A28','OrderId:'.$order_id);
							}
							if($key){
								// 做期支付后面一个时间不能小于等于前一个时间!
								if($value['time'] <= $data['consignment_period'][$key-1]['time']){
									$O->rollback();
									$this->log('error', 'A29','OrderId:'.$order_id);
								}
							}else{
								// 首期支付时间不能小于1天!
								if($value['time']< 1){
									$O->rollback();
									$this->log('error', 'A32','OrderId:'.$order_id);
								}
							}
							// 生成应付信息
							$order_price += $value['price'];
							$add['order_id'] = $order_id;
							$add['receivables_price'] = $value['price'];
							$add['create_time'] = time();
							$add['period_day'] = $value['time'];
							$add['payment_time'] = time()+$value['time']*86400;
							$add['period_current'] = $key+1;
							$add['period_type'] = 3;
							$add['parent_type'] = $info['parent_type'];
							$add['parent_id'] = $info['parent_id'];
							$add['uid'] = $info['uid'];
							$add['tid'] = $info['tid'];
							$add['agent_id'] = C('agent_id');
							if(!$OR->add($add)){
								$O->rollback();
								$this->log('error', 'A30','OrderId:'.$order_id);
							}
							unset($add);
						}
						// 应付价格 和 产品总价格对比
						if(round($order_price,2) != round($order_consignment_price,2)){
							$O->rollback();
							$this->error('产品价格和付款信息不对应!');
						}
					}else{
						$O->rollback();
						$this->error('生成分期信息出错！');
					}
				}elseif($data['consignmentPay'] == 2 and $order_consignment_price != 0) {//全额支付
					$add['order_id'] = $order_id;
					$add['receivables_price'] = $order_consignment_price;
					$add['create_time'] = time();
					$add['period_day'] = 30;
					$add['payment_time'] = time()+2592000;
					$add['period_current'] = 1;
					$add['period_type'] = 3;
					$add['parent_type'] = $info['parent_type'];
					$add['parent_id'] = $info['parent_id'];
					$add['uid'] = $info['uid'];
					$add['tid'] = $info['tid'];
					$add['agent_id'] = C('agent_id');
					if(!$OR->add($add)){
						$O->rollback();
						$this->log('error', 'A30','OrderId:'.$order_id);
					}
					unset($add);
				}else{
					$O->rollback();
					$this->error('没有选择结算方式付款');
				}
				$order_price_up += $order_consignment_price;
			}
			//代销货产品 szzmzb 板房验证
			if(count($consignment_szzmzb)>0 or count($goodsList['consignment_szzmzb']) >0){
				$order_consignment_price = 0;//订单代销货价格
				//页面发过来的数据和数据表裸钻数据个数对比
				if(count($goodsList['consignment_szzmzb']) != count($consignment_szzmzb)){
					$O->rollback();
					$this->log('error', 'A34');
				}
				//代销货确认订单修改订单产品信息
				foreach ($goodsList['consignment_szzmzb'] AS $v){
				    $num = $consignment_szzmzb[$v['og_id']]['goods_num'];//购买的数量
					//修改订单产品价格和数量语句
					$update['goods_price_up']  = $consignment_szzmzb[$v['og_id']]['price'];
					$update['goods_number_up'] = $consignment_szzmzb[$v['og_id']]['goods_num'];
					$update['update_time']     = time();
					$order_consignment_price  += $consignment_szzmzb[$v['og_id']]['price'];
					$res2 = $OG->where('og_id = '.$v['og_id'])->save($update);
					unset($update);
					if(!$res2){
						//失败事务回滚
						$O->rollback();
						$this->log('error', 'A22');
					}
				}
				//生成代销货订单应付信息
				if($data['consignmentPay'] == 1 and $order_consignment_price != 0){//做期支付
					//对发送过来的数据进行验证
					if(empty($data['consignment_period_num'])){
						$O->rollback();
						$this->log('error', 'A33','OrderId:'.$order_id);
					}
					//生成一条记录到订单分期表
					$OP = M('order_period');
					$periodArr['order_id'] = $order_id;
					$periodArr['period_type'] = 3;
					$periodArr['period_num'] = $data['consignment_period_num'];
					$periodArr['period_overdue'] = $data['consignment_period_overdue'];
					$periodArr['agent_id'] = C('agent_id');
					if($OP->add($periodArr)){
						$order_price = 0;//当前分期支付总价格
						foreach ($data['consignment_period'] as $key => $value) {
							// 分期时间或金额不能为空!
							if(empty($value['time']) or empty($value['price'])){
								$O->rollback();
								$this->log('error', 'A28','OrderId:'.$order_id);
							}
							if($key){
								// 做期支付后面一个时间不能小于等于前一个时间!
								if($value['time'] <= $data['consignment_period'][$key-1]['time']){
									$O->rollback();
									$this->log('error', 'A29','OrderId:'.$order_id);
								}
							}else{
								// 首期支付时间不能小于1天!
								if($value['time']< 1){
									$O->rollback();
									$this->log('error', 'A32','OrderId:'.$order_id);
								}
							}
							// 生成应付信息
							$order_price += $value['price'];
							$add['order_id'] = $order_id;
							$add['receivables_price'] = $value['price'];
							$add['create_time'] = time();
							$add['period_day'] = $value['time'];
							$add['payment_time'] = time()+$value['time']*86400;
							$add['period_current'] = $key+1;
							$add['period_type'] = 3;
							$add['parent_type'] = $info['parent_type'];
							$add['parent_id'] = $info['parent_id'];
							$add['uid'] = $info['uid'];
							$add['tid'] = $info['tid'];
							$add['agent_id'] = C('agent_id');
							if(!$OR->add($add)){
								$O->rollback();
								$this->log('error', 'A30','OrderId:'.$order_id);
							}
							unset($add);
						}
						// 应付价格 和 产品总价格对比
						if(round($order_price,2) != round($order_consignment_price,2)){
							$O->rollback();
							$this->error('产品价格和付款信息不对应!');
						}
					}else{
						$O->rollback();
						$this->error('生成分期信息出错！');
					}
				}elseif($data['consignmentPay'] == 2 and $order_consignment_price != 0) {//全额支付
					$add['order_id'] = $order_id;
					$add['receivables_price'] = $order_consignment_price;
					$add['create_time'] = time();
					$add['period_day'] = 30;
					$add['payment_time'] = time()+2592000;
					$add['period_current'] = 1;
					$add['period_type'] = 3;
					$add['parent_type'] = $info['parent_type'];
					$add['parent_id'] = $info['parent_id'];
					$add['uid'] = $info['uid'];
					$add['tid'] = $info['tid'];
					$add['agent_id'] = C('agent_id');
					if(!$OR->add($add)){
						$O->rollback();
						$this->log('error', 'A30','OrderId:'.$order_id);
					}
					unset($add);
				}else{
					$O->rollback();
					$this->error('没有选择结算方式付款');
				}
				$order_price_up += $order_consignment_price;
			}
			//裸钻数据验证
			if(count($goodsLuozuan)>0 or count($goodsList['luozuan']) >0){
				$order_luozuan_price = 0;//订单裸钻总价格
				//页面发过来的数据和数据表裸钻数据个数对比
				if(count($goodsList['luozuan']) != count($goodsLuozuan)){
					$O->rollback();
					$this->log('error', 'A34');
				}
				//裸钻库存检查,减少库存,修改订单产品价格和数量
				$GL = D('GoodsLuozuan');
				foreach ($goodsList['luozuan'] AS $v){
					if($goodsLuozuan[$v['og_id']]['goods_num']){//有购买产品修改库存
                        $luozuanInfo = $GL->where("certificate_number = '".$v['certificate_no']."'")->find();
                        $number = $luozuanInfo['goods_number'];
						$tid    = $luozuanInfo['tid'];
						//钻明确认
						//if(!in_array_diy('7,8', $this->group)){
							
							
							// if($info['tid']){//采购单
							// 	$luozuanArr['goods_number'] = -1;//钻明确认采购单
							// 	$luozuanArr['tid'] = $info['tid'];
							// }else{//客户订单
							// 	$luozuanArr['goods_number'] = 0;
							// }
							
							//只能减自己的库存
							if($luozuanInfo['agent_id'] == C('agent_id')){  //自己的货
								if($number == 0){
									$this->error('当前订单证书货:'.$v['attribute']['certificate_type'].$v['certificate_no'].'已销售,无库存!');
								}elseif($number != 1){
									$this->error('当前订单有证书货已在销售中!');
								}
								$luozuanArr['goods_number'] = 0;
							}else{
								unset($luozuanArr);
							}

							
						//}
						//1级分销商确认订单，裸钻库存状态不是-1不能确认，必须钻明先确认
						/*
						if((in_array(7,$this->group))){
							if($number == 1){
								$this->error('当前订单有证书货钻明没确认，请向上级下采购单或等待上级确认!');
							}elseif ($number == 0){
								$this->error('当前订单有证书货已销售!');
							}elseif (($number == '-1') and ($tid == $this->tid)){//钻明已确认给这个1级分销商，1级分销商才可以继续确认
								if($info['tid']){//采购单
									$luozuanArr['goods_number'] = -2;
									$luozuanArr['tid'] = $info['tid'];
								}else{//客户订单
									$luozuanArr['goods_number'] = 0;
								}
							}else{
								$this->error('当前订单有证书货已销售!');
							}
						}
						*/
						//2级分销商确认订单，裸钻库存状态不是-2不能确认，必须1级分销商先确认
						/*
						if(in_array(8,$this->group)){
							if($number == '1' or $number == '-1'){
								$this->error('当前订单有证书货上级供应商没确认，请向上级下采购单或等待上级确认!');
							}elseif ($number == 0){
								$this->error('当前订单有证书货已销售!');
							}elseif (($number == '-2') and ($tid == $this->tid)){//1级分销商已确认给这个2级分销商，2级分销商才可以继续确认
								$luozuanArr['goods_number'] = 0;
							}
						}
						*/

						if(!empty($luozuanArr)){
							$res1 = $GL->where("certificate_number = '".$v['certificate_no']."'")->save($luozuanArr);
						}
						
						//修改订单产品价格和数量语句
						$update['goods_price_up']  = $goodsLuozuan[$v['og_id']]['price'];
						$update['goods_number_up'] = $goodsLuozuan[$v['og_id']]['goods_num'];
						$update['update_time'] = time();
						$order_luozuan_price += $goodsLuozuan[$v['og_id']]['price'];
						$res2 = $OG->where('agent_id = '.C('agent_id').' and og_id = '.$v['og_id'])->save($update);

						unset($update);
						if( !$res2){
							//失败事务回滚
							$O->rollback();
							$this->log('error', 'A22');
						}
					}else{
						//修改订单产品价格和数量语句
						$update['goods_price_up'] = 0;
						$update['goods_number_up'] = $goodsLuozuan[$v['og_id']]['goods_num'];
						$update['update_time'] = time();
						$res2 = $OG->where('agent_id = '.C('agent_id').' and og_id = '.$v['og_id'])->save($update);
						unset($update);
						if(!$res2){
							//失败事务回滚
							$O->rollback();
							$this->log('error', 'A35');
						}
					}
				}
				//生成裸钻订单应付信息
				if($data['luozuanPay'] == 1 and $order_luozuan_price != 0){//做期支付
					//对发送过来的数据进行验证
					if(empty($data['luozuan_period_num'])){
						$O->rollback();
						$this->log('error', 'A33','OrderId:'.$order_id);
					}
					//生成一条记录到订单分期表
					$OP = M('order_period');
					$periodArr['order_id'] = $order_id;
					$periodArr['period_type'] = 1;
					$periodArr['period_num'] = $data['luozuan_period_num'];
					$periodArr['period_overdue'] = $data['luozuan_period_overdue'];
					$periodArr['agent_id'] = C('agent_id');
					if($OP->add($periodArr)){
						$order_price = 0;//当前分期支付总价格
						foreach ($data['luozuan_period'] as $key => $value) {
							// 分期时间或金额不能为空!
							if(empty($value['time']) or empty($value['price'])){
								$O->rollback();
								$this->log('error', 'A28','OrderId:'.$order_id);
							}
							if($key){
								// 做期支付后面一个时间不能小于等于前一个时间!
								if($value['time'] <= $data['luozuan_period'][$key-1]['time']){
									$O->rollback();
									$this->log('error', 'A29','OrderId:'.$order_id);
								}
							}else{
								// 首期支付时间不能小于1天!
								if($value['time']< 1){
									$O->rollback();
									$this->log('error', 'A32','OrderId:'.$order_id);
								}
							}
							// 生成应付信息
							$order_price += $value['price'];
							$add['order_id'] = $order_id;
							$add['receivables_price'] = $value['price'];
							$add['create_time'] = time();
							$add['period_day'] = $value['time'];
							$add['payment_time'] = time()+$value['time']*86400;
							$add['period_current'] = $key+1;
							$add['period_type'] = 1;
							$add['parent_type'] = $info['parent_type'];
							$add['parent_id'] = $info['parent_id'];
							$add['uid'] = $info['uid'];
							$add['tid'] = $info['tid'];
							$add['agent_id'] = C('agent_id');
							if(!$OR->add($add)){
								$O->rollback();
								$this->log('error', 'A30','OrderId:'.$order_id);
							}
							unset($add);
						}
						// 裸钻应付价格 和 裸钻产品总价格对比
						if(round($order_price,2) != round($order_luozuan_price,2)){
							$O->rollback();
							$this->error('裸钻产品价格和付款信息部对应!');
						}
					}else{
						$O->rollback();
						$this->error('生成分期信息出错！');
					}
				}elseif($data['luozuanPay'] == 2 and $order_luozuan_price != 0) {//全额支付
					$add['order_id'] = $order_id;
					$add['receivables_price'] = $order_luozuan_price;
					$add['create_time'] = time();
					$add['period_day'] = 30;
					$add['payment_time'] = time()+2592000;
					$add['period_current'] = 1;
					$add['period_type'] = 1;
					$add['parent_type'] = $info['parent_type'];
					$add['parent_id'] = $info['parent_id'];
					$add['uid'] = $info['uid'];
					$add['tid'] = $info['tid'];
					$add['agent_id'] = C('agent_id');
					if(!$OR->add($add)){
						$O->rollback();
						$this->log('error', 'A30','OrderId:'.$order_id);
					}
					unset($add);
				}
				$order_price_up += $order_luozuan_price;
			}
			//散货数据验证
			if(count($goodsList['sanhuo']) > 0 or count($goodsSanhuo) > 0){
				$order_sanhuo_price = 0;//订单散货总价格
				//页面发过来的数据和数据表散货数据个数对比
				if(count($goodsList['sanhuo']) != count($goodsSanhuo)){
					$O->rollback();
					$this->log('error', 'A34');
				}
				//散货库存检查和修改库存和价格
				$GS  = D('GoodsSanhuo');
				$GSL = M('goods_sanhuo_lock');
				foreach ($goodsList['sanhuo'] AS $v){
					if($goodsSanhuo[$v['og_id']]['goods_num']){//有购买散货产品修改库存
						//钻明确认,减库存，如果采购单给下级分销商加一个锁定库存
						if(!in_array_diy('7,8', $this->group)){

							$res = M('goods_sanhuo')->where('goods_sn = \''.$v['certificate_no'].'\' and agent_id='.C('agent_id'))->select();
							if($res){//不是自己的货不能减库存
														
								$number = $GS -> getOne('goods_weight',array('zm_goods_sanhuo.goods_sn'=>array('in',$v['certificate_no'])));

								$number = $number - $goodsSanhuo[$v['og_id']]['goods_num'];//库存重量-当前销售重量
								if($number < 0){
									$O    -> rollback();
									$this -> log('error', 'A59');
								}


	                            $agent_id     = $GS -> get_where_agent();
								$res1         = $GS -> where(" agent_id in ($agent_id) and goods_sn = '".$v['certificate_no']."' ") -> setField('goods_weight',$number);


								// if($info['tid']){//采购单确认
								// 	$GSLRES   = $GSL -> where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$info['tid'])->find();
								// 	if($GSLRES){
								// 		//采购单增加锁定数量
								// 		$res2 = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$info['tid'])
								// 			->setField('goods_weight',$GSLRES['goods_weight']+$goodsSanhuo[$v['og_id']]['goods_num']);
								// 	}else{
								// 		$arrLock['goods_id']     = $v['goods_id'];
								// 		$arrLock['goods_weight'] = $goodsSanhuo[$v['og_id']]['goods_num'];
								// 		$arrLock['tid']          = $info['tid'];
								// 		$arrLock['agent_id']     = C('agent_id');
								// 		//采购单锁定销售数量
								// 		$res2 = $GSL -> add($arrLock);
								// 	}
								// }


								// if(!$res1 and !$res2){
								if(!$res1){
									$O->rollback();
									$this->log('error', 'A59');
								}
							}
						}
						//1级分销商确认,减库存，如果采购单给下级分销商加一个锁定库存
						if(in_array('7', $this->group)){
							$number = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$this->tid)->getField('goods_weight');
							$number = $number  - $goodsSanhuo[$v['og_id']]['goods_num'];//库存重量-当前销售重量
							if($number < 0){
								$O->rollback();
								$this->log('error', 'A68');
							}
							$res1 = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$this->tid)->setField('goods_weight',$number);
							if($info['tid']){//采购单确认
								$GSLRES = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$info['tid'])->find();
								if($GSLRES){
									//采购单增加锁定数量
									$res2 = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$info['tid'])
										->setField('goods_weight',$GSLRES['goods_weight']+$goodsSanhuo[$v['og_id']]['goods_num']);
								}else{
									$arrLock['goods_id'] = $v['goods_id'];
									$arrLock['goods_weight'] = $goodsSanhuo[$v['og_id']]['goods_num'];
									$arrLock['tid'] = $info['tid'];
									$arrLock['agent_id'] = C('agent_id');
									//采购单锁定销售数量
									$res2 = $GSL->add($arrLock);
								}
							}
							if(!$res1 and !$res2){
								$O->rollback();
								$this->log('error', 'A59');
							}
						}
						//2级分销商确认,减库存
						if(in_array('8',$this->group)){
							$number = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$this->tid)->getField('goods_weight');
							$number = $number  - $goodsSanhuo[$v['og_id']]['goods_num'];//库存重量-当前销售重量
							if($number < 0){
								$O->rollback();
								$this->log('error', 'A68');
							}
							$res1 = $GSL->where('agent_id = '.C('agent_id').' and goods_id = '.$v['goods_id'].' and tid = '.$this->tid)->setField('goods_weight',$number);
							if(!$res1){
								$O->rollback();
								$this->log('error', 'A59');
							}
						}
						//修改订单产品价格和数量语句
						$update['goods_price_up'] = $goodsSanhuo[$v['og_id']]['price'];
						$update['goods_number_up'] = $goodsSanhuo[$v['og_id']]['goods_num'];
						$update['update_time'] = time();
						$order_sanhuo_price += $goodsSanhuo[$v['og_id']]['price'];
						$res2 = $OG->where('agent_id = '.C('agent_id').' and og_id = '.$v['og_id'])->save($update);
						unset($update);
						if(!$res2){
							//失败事务回滚
							$O->rollback();
							$this->log('error', 'A59');
						}
					}else{
						//修改订单产品价格和数量语句
						$update['goods_price_up'] = $goodsSanhuo[$v['og_id']]['price'];
						$update['goods_number_up'] = $goodsSanhuo[$v['og_id']]['goods_num'];
						$update['update_time'] = time();
						$res2 = $OG->where('agent_id = '.C('agent_id').' and og_id = '.$v['og_id'])->save($update);
						unset($update);
						if(!$res2){
							//失败事务回滚
							$O->rollback();
							$this->log('error', 'A35');
						}
					}
				}
				//生成散货订单应付信息
				if($data['sanhuoPay'] == 1 and $order_sanhuo_price != 0){//做期支付
					//对发送过来的数据进行验证
					if(empty($data['sanhuo_period_num'])){
						$O->rollback();
						$this->log('error', 'A33','OrderId:'.$order_id);
					}
					//生成一条记录到订单分期表
					$OP = M('order_period');
					$periodArr['order_id'] = $order_id;
					$periodArr['period_type'] = 2;
					$periodArr['period_num'] = $data['sanhuo_period_num'];
					$periodArr['period_overdue'] = $data['sanhuo_period_overdue'];
					$periodArr['agent_id'] = C('agent_id');
					if($OP->add($periodArr)){
						$order_price = 0;//当前分期支付总价格
						foreach ($data['sanhuo_period'] as $key => $value) {
							// 分期时间或金额不能为空!
							if(empty($value['time']) or empty($value['price'])){
								$O->rollback();
								$this->log('error', 'A28','OrderId:'.$order_id);
							}
							if($key){
								// 做期支付后面一个时间不能小于等于前一个时间!
								if($value['time'] <= $data['sanhuo_period'][$key-1]['time']){
									$O->rollback();
									$this->log('error', 'A29','OrderId:'.$order_id);
								}
							}else{
								// 首期支付时间不能小于1天!
								if($value['time']< 1){
									$O->rollback();
									$this->log('error', 'A32','OrderId:'.$order_id);
								}
							}
							// 生成应付信息
							$order_price += $value['price'];
							$add['order_id'] = $order_id;
							$add['receivables_price'] = $value['price'];
							$add['create_time'] = time();
							$add['period_day'] = $value['time'];
							$add['payment_time'] = time()+$value['time']*86400;
							$add['period_current'] = $key+1;
							$add['period_type'] = 2;
							$add['parent_type'] = $info['parent_type'];
							$add['parent_id'] = $info['parent_id'];
							$add['uid'] = $info['uid'];
							$add['tid'] = $info['tid'];
							$add['agent_id'] = C('agent_id');
							if(!$OR->add($add)){
								$O->rollback();
								$this->log('error', 'A30','OrderId:'.$order_id);
							}
							unset($add);
						}
						// 散货应付价格 和 散货产品总价格对比
						if(round($order_price,2) != round($order_sanhuo_price,2)){
							$O->rollback();
							$this->error('散货产品价格和应付款项不能对应!');
						}
					}else{
						$O->rollback();
						$this->error('生成散货做期信息失败！');
					}
				}elseif($data['sanhuoPay'] == 2 and $order_sanhuo_price != 0) {//全额支付
					$add['order_id'] = $order_id;
					$add['receivables_price'] = $order_sanhuo_price;
					$add['create_time'] = time();
					$add['period_day'] = 30;
					$add['payment_time'] = time()+2592000;
					$add['period_current'] = 1;
					$add['period_type'] = 2;
					$add['parent_type'] = $info['parent_type'];
					$add['parent_id'] = $info['parent_id'];
					$add['uid'] = $info['uid'];
					$add['tid'] = $info['tid'];
					$add['agent_id'] = C('agent_id');
					if(!$OR->add($add)){
						$O->rollback();
						$this->log('error', 'A30','OrderId:'.$order_id);
					}
					unset($add);
				}
				$order_price_up += $order_sanhuo_price;
			}

			//珠宝产品数据验证
			/*
			if(count($goodsList['goods']) > 0 or count($goods) > 0){
				$order_goods_price = 0;//订单成品货总价格
				//页面发过来的数据和数据表成品货数据个数对比
				if(count($goodsList['goods']) != count($goods)){
					$O->rollback();
					$this->log('error', 'A34');
				}
				//珠宝库存检查和修改库存和价格
				$G  = M('goods');
				$GS = M('goods_sku');
				foreach ($goodsList['goods'] AS $v){
				    $num = $goods[$v['og_id']]['goods_num'];
					if($num and $v['goods_type'] != 4){//有购买成品产品修改库存
					    $sku_sn = $v['attribute']['sku_sn'];
					    //获取总库存和规格库存
					    $number1 = $G->where("goods_id = '".$v['goods_id']."'")->getField('goods_number');
					    $number2 = $GS->where('goods_id = '.$v['goods_id']." and sku_sn = '$sku_sn'")->getField('goods_number');
					    //减库存
					    $number3 = $number1 - $num;
					    $number4 = $number2 - $num;
					    //只有珠宝产品有销售数量才进行库存检查
						if($number3 < 0 or $number4 < 0){//其中有成品产品没有库存
						    $O->rollback();
						    $this->error('当前珠宝产品'.$v['certificate_no'].'没有库存，请添加库存后再销售!');
						}else{
						    $res1 = $G->where("goods_id = '".$v['goods_id']."'")->setField('goods_number',$number3);
						    $res2 = $GS->where('goods_id = '.$v['goods_id']." and sku_sn = '$sku_sn'")->setField('goods_number',$number4);
						    if(!$res1 or !$res2){
						        //失败事务回滚
						        $O->rollback();
						        $this->error('扣订单产品库存失败!');
						    }
						}
					}
					//修改订单产品价格和数量语句
					$update['goods_price_up'] = $goods[$v['og_id']]['price'];
					$update['goods_number_up'] = $goods[$v['og_id']]['goods_num'];
					$update['update_time'] = time();
					$order_goods_price += $goods[$v['og_id']]['price'];
					$res3 = $OG->where('agent_id = '.C('agent_id').' and og_id = '.$v['og_id'])->save($update);
					unset($update);
					if(!$res3){
					    //失败事务回滚
					    $O->rollback();
					    $this->error('修改订单产品数据库失败!');
					}
				}
				//生成珠宝产品支付信息
				if($data['goodsPay'] == 1  and $order_goods_price != 0){//做期支付
					//对发送过来的数据进行验证
					if(empty($data['goods_period_num'])){
						$O->rollback();
						$this->log('error', 'A33','OrderId:'.$order_id);
					}
					//生成一条记录到订单分期表
					$OP = M('order_period');
					$periodArr['order_id'] = $order_id;
					$periodArr['period_type'] = 3;
					$periodArr['period_num'] = $data['goods_period_num'];
					$periodArr['period_overdue'] = $data['goods_period_overdue'];
					$periodArr['agent_id'] = C('agent_id');
					if($OP->add($periodArr)){
						$order_price = 0;//当前分期支付总价格
						foreach ($data['goods_period'] as $key => $value) {
							// 分期时间或金额不能为空!
							if(empty($value['time']) or empty($value['price'])){
								$O->rollback();
								$this->log('error', 'A28','OrderId:'.$order_id);
							}
							if($key){
								// 做期支付后面一个时间不能小于等于前一个时间!
								if($value['time'] <= $data['goods_period'][$key-1]['time']){
									$O->rollback();
									$this->log('error', 'A29','OrderId:'.$order_id);
								}
							}else{
								// 首期支付时间不能小于1天!
								if($value['time']< 1){
									$O->rollback();
									$this->log('error', 'A32','OrderId:'.$order_id);
								}
							}
							// 生成应付信息
							$order_price += $value['price'];
							$add['order_id'] = $order_id;
							$add['receivables_price'] = $value['price'];
							$add['create_time'] = time();
							$add['period_day'] = $value['time'];
							$add['payment_time'] = time()+$value['time']*86400;
							$add['period_current'] = $key+1;
							$add['period_type'] = 3;
							$add['parent_type'] = $info['parent_type'];
							$add['parent_id'] = $info['parent_id'];
							$add['uid'] = $info['uid'];
							$add['tid'] = $info['tid'];
							$add['agent_id'] = C('agent_id');
							if(!$OR->add($add)){
								$O->rollback();
								$this->log('error', 'A30','OrderId:'.$order_id);
							}
							unset($add);
						}
						// 成品货应付价格 和 成品货产品总价格对比
						if(round($order_price,2) != round($order_goods_price,2)){
							$O->rollback();
							$this->log('error', 'A31','OrderId:'.$order_id);
						}
					}else{
						$O->rollback();
						$this->error('生成成品货做期信息失败！');
					}
				}elseif($data['goodsPay'] == 2 and $order_goods_price != 0){//成品全款支付
					$add['order_id'] = $order_id;
					$add['receivables_price'] = $order_goods_price;
					$add['create_time'] = time();
					$add['period_day'] = 30;
					$add['payment_time'] = time()+2592000;
					$add['period_current'] = 1;
					$add['period_type'] = 3;
					$add['parent_type'] = $info['parent_type'];
					$add['parent_id'] = $info['parent_id'];
					$add['uid'] = $info['uid'];
					$add['tid'] = $info['tid'];
					$add['agent_id'] = C('agent_id');
					if(!$OR->add($add)){
						$O->rollback();
						$this->log('error', 'A30','OrderId:'.$order_id);
					}
					unset($add);
				}
				$order_price_up += $order_goods_price;
			}
			*/
			//修改订单状态和总价格
			$orderData['order_status'] = 1;
			$orderData['order_price_up'] = $order_price_up;	
			$orderData['update_time'] = time();
			//总价格等于0，不能确认订单
			if($order_price_up == 0){
			    $O->rollback();
			    $this->error('订单总价格等于0,不能确认订单!');
			}
			if($O->where('agent_id = '.C('agent_id').' and order_id = '.$order_id)->save($orderData)){//成功事务提交
                $O->commit();
			    $notes = '操作了货品确认，生成了应收款和支付分期记录!';				
			    $this->orderLog($order_id, $notes, L('A24'));
				
			}else{//失败事务回滚和报错
				$O->rollback();
				$this->log('error', 'A23','OrderId:'.$order_id);
			}
		}else{//没有当前操作权限
			$this->log('error', 'A20','OrderId:'.$order_id);
		}
	}

	/**
	 * 订单操作（修改备注）
	 * @param int $order_id
	 * @param string $note
	 */
	public function noteAction($order_id,$note){
		$url = MODULE_NAME . '/' . CONTROLLER_NAME . '/'.'noteAction';
		if(!$this->checkActionAuth($url)) $this->error('你没有权限操作');
		if(!$note){
		    $this->error('请填写备注内容!');
		}
		$OL = M('order_log');
		$arr['order_id'] = $order_id;
		$arr['group_id'] = $this->group[0];
		$arr['user_id'] = $this->uid;
		$arr['create_time'] = time();
		$arr['note'] = $note;
		$arr['agent_id'] = C('agent_id');
		if($OL->add($arr)){
			$this->success('添加订单操作备注成功');
		}else{
			$this->error('添加订单操作日志失败');
		}
	}

	/**
	 * 标记操作
	 * @param int $order_id
	 * @param string $note
	 */
	public function markAction($order_id,$mark_items){
		if(count($mark_items)>0){
			$mark_items_str = implode('<br/>',$mark_items);
		}else{
			$mark_items_str = "";
		}
		$O    = M('order');
		$O->where('agent_id = '.C('agent_id').' and order_id='.$order_id)->setField('mark',$mark_items_str);
		return true;
	}

	/**
	 * 修改订单裸钻数量
	 * @param int $order_id
	 * @param array $_POST
	 */
	protected function editOrderLuozuanNumber($order_id){
		$O = M('order');
		$OG = M('order_goods');
		$GL = D('GoodsLuozuan');
		$OG->startTrans();
		$orderInfo = $O->where('agent_id = '.C('agent_id'))->find($order_id);
		if($orderInfo['order_status'] == 0){
			//采购部可以修改证书货数量
			foreach ($_POST['goodsLuozuan'] as $key => $value) {
				if($value['goods_num'] == 0){
					$orderGoodsInfo = $OG->where('agent_id = '.C('agent_id'))->find($key);
					if($orderGoodsInfo['goods_number'] != 0){
						//把订单订购数量和价格改为0
						$data['goods_number'] = 0;
						$data['goods_price'] = 0;
						$price += $OG->where('agent_id = '.C('agent_id').' and og_id = '.$key)->getField('goods_price');
						$res   = $OG->where('agent_id = '.C('agent_id').' and og_id = '.$key)->save($data);
						if(!$res){$OG->rollback();$this->error('有修改订单产品数量，出错');}
						//有货品把货品数量改为0
                        if(C('is_sync_luozuan')){
                            $agent_id = C('agent_id');
                            $where    = ' agent_id in ('.C('agent_id').',0) and certificate_number = '.$orderGoodsInfo['certificate_no'];
                        }else{
                            $where    = ' agent_id = '.C('agent_id').' and certificate_number = '.$orderGoodsInfo['certificate_no'];
                        }
                        $where    = ' certificate_number = '.$orderGoodsInfo['certificate_no'];
                        $goodsLuozuanInfo = $GL->where($where)->find();
                        if($goodsLuozuanInfo and $goodsLuozuanInfo['goods_number'] != 0){
							$res = $GL->where($where)->setField('goods_number',0);
							if(!$res){$OG->rollback();$this->error('有修改订单产品库存数量，出错');}
						}
					}
				}
			}

			if($price > 0){
				$where = 'agent_id = '.C('agent_id').' and order_id = '.$order_id;
				$order_price = $O->where($where)->getField('order_price');
				$order_price = formatPrice($order_price - $price);
				$res = $O->where($where)->setField('order_price',$order_price);
				if(!$res){$OG->rollback();return false;}
				$OG->commit();
				return true;
			}else{
				$OG->commit();
				return true;
			}
		}
		$OG->commit();
		return false;
	}

	/**
	 * 删除操作日志
	 * @param int $ol_id
	 */
	public function delOrderLog($ol_id){
		$OL = M('order_log');
		if($OL->where('agent_id = '.C('agent_id').' and ol_id = '.$ol_id)->delete()){
			$this->success('删除操作日志成功!');
		}else{
			$this->error('删除操作日志失败!');
		}
	}

	/**
	 * 订单操作(删除已经取消的订单)
	 * @param int $order_id
	 * @date: 2016-08-28
	 */
	public function orderDelete($order_id){
		$O = M('order');
		$OG = M('order_goods');
		$info = $O->find(I('get.order_id'));
		//验证状态
		if($info['order_status'] == -1){    //订单状态必须是-1，已经取消的状态
			$O->startTrans();

			$order_sn =  $O->where("agent_id = '".C('agent_id')."' AND order_id = '" . $order_id . "'")->getField('order_sn');

			$res1 = $O->where('agent_id = '.C('agent_id').' AND order_id = '.$order_id)->delete();
			$res2 = $OG->where('agent_id = '.C('agent_id').' AND order_id = '.$order_id)->delete();

			if($res1 and $res2){
				$arr['agent_id'] = C('agent_id');
				$arr['order_id'] = $order_id;
				$arr['group_id'] = $this->group[0];
				$arr['user_id'] = $this->uid;
				$arr['create_time'] = time();
				$arr['note'] = $order_sn.'订单删除成功!';
				M('order_log')->add($arr);
				$O->commit();
				$this->success('订单删除成功!',U('Admin/Order/orderList'));
			}else{
				$O->rollback();
				$this->error('订单删除出错!');
			}
		}else{
			$this->error('订单状态不是已取消订单！');
		}
	}


	/**
	 * 获取订单/采购单应付信息
	 * @param int $order_id
	 * @param int $is_caigoudan
	 */
	protected function GetOrderReceivables($order_id , $is_caigoudan = 0){
		//订单退款信息
		$OC = M('order_compensate');
		if($is_caigoudan == 0){
			$list2 = $OC->where('agent_id = '.C('agent_id').' and order_id = '.$order_id.' and period_type in(12)')->order('create_time')->select();
		}else{
			$list2 = $OC->where('agent_id = '.get_parent_id().' and order_id = '.$order_id.' and period_type in(12)')->order('create_time')->select();
		}

		foreach ($list2 as $key => $value) {
            if($value['period_type'] == 12){//退差价
			    $arr['consignment'][] = $value;
			}
		}
		//订单应付款信息
		$OY = M('order_receivables');
		if($is_caigoudan == 0){
			$list = $OY->where('agent_id = '.C('agent_id').' and order_id = '.$order_id.' and period_type in(1,2,3,5,12)')->order('period_current')->select();
		}else{
			$list = $OY->where('agent_id = '.get_parent_id().' and order_id = '.$order_id.' and period_type in(1,2,3,5,12)')->order('period_current')->select();
		}

		foreach ($list as $key => $value) {
			if($value['period_type'] == 1){
				$arr['luozuan'][] = $value;
			}elseif ($value['period_type'] == 2){//散货
				$arr['sanhuo'][] = $value;
			}elseif ($value['period_type'] == 3 or $value['period_type'] == 4){//成品和钻托
				$arr['consignment'][] = $value;
			}
			// elseif ($value['period_type'] == 5 or $value['period_type'] == 6){//代销货
			//  	$arr['consignment'][] = $value;
			// }
			elseif($value['period_type'] == 12){//补差价
			    $arr['consignment'][] = $value;
			}
		}
		return $arr;
	}

	/**
	 * 订单删除产品
	 * @param int $order_id
	 * @param int $og_id
	 */
	public function orderDelGoods($order_id,$og_id){
	    $O = M('order');
	    $OG = M('order_goods');
	    $O->startTrans();
	    //订单检查
	    $where = $this->buildWhere('o.') . ' AND o.order_id = '.$order_id;
	    $orderInfo = $O->alias('o')->where($where)->find();
	    if(!$orderInfo or $orderInfo['order_status'] === 0){
	        $this->error('没有当前订单或者当前订单已确认');
	    }
	    //把当前的产品购买数量屏蔽成-1
	    $ogwhere = 'order_id ='.$order_id.' and agent_id = '.C('agent_id').' and og_id = '.$og_id;
	    $ogInfo = $OG->where($ogwhere)->find();

	    if($ogInfo){
    	    $res = $OG->where($ogwhere)->delete();
    	    if($res){
    	        //重新计算订单的价格
    	        $ogwhere = 'order_id ='.$order_id;
    	        $ogList = $OG->where($ogwhere)->select();
    	        $order_price = 0;
    	        
    	        foreach ($ogList as $key => $value) {    	            
    	            $order_price += $value['goods_price'];    	            
    	        }

    	        if(round($order_price,2) <= 0){
    	            $O->rollback();
    	            $this->error('订单总金额不能小于等于0');
    	        }

    	        $orderData['order_price'] = $order_price;
    	        $orderData['update_time'] = time();
    	        $res = $O->alias('o')->where($where)->save($orderData);
    	        if($res){
    	            $O->commit();
    	            if($ogInfo['goods_type'] == 1){
    	            	$notes = '删除了证书号' . $ogInfo['certificate_no'];
    	            }elseif($ogInfo['goods_type'] == 2){
    	            	$notes = '删除了散货' . $ogInfo['certificate_no'];
    	            }else{
    	            	$notes = '删除了货品' . $ogInfo['certificate_no'];
    	            }
    	            
    	            $this->orderLog($order_id, $notes, '删除产品成功，订单价格已重新计算！');
    	        }else{
    	            $O->rollback();
    	            $this->error('删除产品失败');
    	        }
    	    }else{
    	        $O->rollback();
    	        $this->error('删除产品失败');
    	    }
	    }else{
	        $O->rollback();
	        $this->error('没有这个产品!');
	    }
	}
	
	
	
	public function OrderAddOrder(){
		$par		  = I('get.par');
        $yiji_agent_id= get_parent_id();    
        $uid          = get_create_user_id();
    	$par          = explode(',', $par);
    	$orderType    = $par[0];//添加的订单类型
    	$goodsType    = $par[1];//添加的产品类型
    	$goodsSn      = $par[2];//产品编号
    	$goods_number = $par[3];//产品数量
    	$og_id        = $par[4];//订单产品id
		$unqion		  = $par[5];//提交时间
		if($unqion){
			$old_unqion = cookie('AddCaigouOrderNameTime');
			if($unqion==$old_unqion){
				echo '新的唯一标识和旧的唯一标识一样！';
				exit();
			}else{
				cookie('AddCaigouOrderNameTime',$unqion);
			}
		}else{
			echo '空的唯一标识！';
			exit();
		}
		
 
		if( ! C('dollar_huilv_type') ){
			$dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
			C('dollar_huilv',$dollar_huilv);
		}
		
    	$OC                = M('order_cart');
        $OG                = M('order_goods');


		if ( $goodsType == 'supply'){  //王松林的代码
			if(strpos( $og_id , ':' )){
				$og_array = explode(':',$og_id);
				$og_id    = implode(',',$og_array);
			}else{
				$og_id    = $og_id;
			}
			$order_id = $OG->where( ' og_id in ( ' . $og_id.' ) ')->select();
			if($og_id) {
                if($yiji_agent_id < 1){
						if(!$uid){
							$result = array('success'=>false, 'msg'=>'请绑定钻明官网帐号在做操作！','error'=>true);
							$this->ajaxReturn($result);
						}
						//D('SyncZmOrder')->addCaiGouDan($order_id[0]['order_id'], $og_id);
						$SyncZmOrder			= new \Common\Model\SyncZmOrder();
						$SyncZmOrder->pushOrderToZm($order_id[0]['order_id'], $og_id);

                }else{
                    $return_str = D('JoinCaigouOrder')->joinYijiAgentCaigou($order_id[0]['order_id'], $og_id);
                    if($return_str !== true){
                        $result = array('success'=>false, 'msg'=> $return_str,'error'=>true);    
                    }
                }
				
				$result = array('success'=>true, 'msg'=>'添加到采购单成功！','error'=>true);
			}else{
				$result = array('success'=>false, 'msg'=>'添加到采购单失败！','error'=>true);
			}
			$this->ajaxReturn($result);
			exit;
		}
	}
	
	
	
	
	
}
