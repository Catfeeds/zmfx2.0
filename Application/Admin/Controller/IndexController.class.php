<?php
/**
 * 后端首页
 */
namespace Admin\Controller;
use Common\Model\Notices;

class IndexController extends AdminController {

	public function __construct() {
			parent::__construct();
			$this->agent_id = C("agent_id");
			$this->uid		= $_SESSION['web']['uid'];
	}

    /**
	 * auth	：fangkai
	 * content：首页
	 * time	：2016-9-1
	**/
    public function index(){
		//$this->updateOrderGoodsProduct();								//更新order_goods表成品数据
		//$this->updateOrderGoodsLuozuan();								//更新order_goods表裸钻数据

    	//产品数据楼层
		$GL		= D('Common/GoodsLuozuan');
		$GS		= D('Common/GoodsSanhuo');
		$G		= D('Common/Goods');
		$where_dia['goods_number'] 	= array('gt',0);
		$where_dia['luozuan_type'] 	= 0;
		$this->baizuan_count     	= $GL->getcount($where_dia,false);			    //得到白钻总数

		$where_dia['luozuan_type'] 	= 1;
		$this->caizuan_count  	= $GL->getcount($where_dia,false);					//得到彩钻总数

		$sum_sanhuo				= ' `goods_weight` ';
		$where_san['goods_status']	= 1;
		$this->sanhuo_count  	= $GS->getSum($where_san,$sum_sanhuo,false);		//得到散货总重量

		$where_pro['goods_type']= 4;
		$this->dingzhi_count  	= $G->getcount($where_pro);							//得到订制总数

		$where_pro['goods_number'] 	= array('gt',0);
		$where_pro['goods_type']    = 3;
		$sum_pro					= ' `goods_number` ';
		$this->chengpin_count  	= $G->getSum($where_pro,$sum_pro);					//得到成品总件数


		//订单数据统计楼层 传值 ：1 最近七天数据，2 最近一个月数据，3 最近一年数据
		$data = $this->getData(1);
		$subTitle	= '最近7天订单总数为：'.$data['totalCount'].'单';

		$this->X_date 		=  (string)json_encode($data['X_date']);	//默认显示最近七天时间组合，赋值到模板X轴坐标
		$this->orderCount	=  (string)json_encode($data['orderCount']);//默认显示最近七天订单总数组合，赋值到模板展示数据
		$this->subTitle		=	$subTitle;								//默认显示最近七天的订单总数


		//产品月销量楼层 传值 ：1 裸钻（白钻/彩钻），3 成品
		$data = $this->getSaleData(3);
		$saleSubTitle	= '最近1个月成品销量为：'.$data['totalSaleCount'].'件';

		$this->X_prodate	= (string)json_encode($data['X_prodate']);	//默认显示最近一个月成品X轴分类，赋值到模板X轴坐标
		$this->saleCount	= (string)json_encode($data['saleCount']);	//默认显示最近一个月成品分类订单数量，赋值到模板展示数据
		$this->saleSubTitle	=  $saleSubTitle;							//默认显示最近一个月成品的销量总数

		//销售总额楼层 传值 ：1 今天，2 昨天，3 近30天
		$data = $this->getMoneyData(1);
		$moneySubTitle	  		= '今天销售总额为：'.$data['totalMoneyCount'].'万元';

		$this->X_totalMoneydate	= (string)json_encode($data['X_totalMoneydate']);	//默认显示今天，赋值到模板X轴坐标
		$this->moneyCount		= (string)json_encode($data['moneyCount']);	//默认显示今天的销售总额，赋值到模板展示数据
		$this->moneySubTitle	=  $moneySubTitle;							//默认显示最近一个月成品的销量总数

		//会员统计
		$U			= D('Common/User');
		$agent		= M('agent');
		$agentInfo	= $agent->where(array('agent_id'=>$this->agent_id))->find();
		$identification	= 2;												//分销商标识，1：一级分销商，2：二级分销商
		if($agentInfo['parent_id'] == 0){
			$identification 	= 1;										//分销商标识赋值
			$agentList	= $agent->where(array('parent_id'=>$this->agent_id))->select();	//获取下级分销商的列表
			if($agentList){													//计算出该分销商的下级分销商的总数
				$countAgent	= count($agentList);
			}else{
				$countAgent	= 0;
			}
		}
		$where['status']	= array('neq','-1');
		$where['agent_id']	= $this->agent_id;
		$userList	= $U->getUserList($where,'uid DESC',1,'');			//获取网站会员列表
		if($userList){													//计算出网站会员总数
			$countUser	= count($userList);
		}else{
			$countUser	= 0;
		}

		$this->identification	= $identification;						//分销商标识赋值，前台用来区别是否显示分销商数量统计
		$this->countUser		= $countUser;							//显示网站用户总数
		$this->countAgent		= $countAgent;							//显示下级分销商总数

		$this->display();
    }

    /**
	 * auth	：fire
	 * content：ajax获取上周发布的最后一条未读的公告消息
	 * time	：2017-10-23
	**/
    public function noticeGetLast(){         
       
        if(IS_AJAX){       
            $uid=session ('admin.uid' );
            $data=M('Notices')
                ->where("status=1") // and YEARWEEK(date_format(update_time,'%Y-%m-%d')) = YEARWEEK(now())-1
                ->order('id desc')
                ->find();  
            //echo M('Notices')->getLastSql();
            if($data){
                $count=M('notices_status')->where("notice_id=%d and uid=%d",$data['id'],$uid)->count();
                if($count>0){
                    $data=null;
                }
            }
            
            $result['msg'] 		= '操作成功';
            $result['status'] 	=  '100';
            $result['data']		= $data;
            $this->ajaxReturn($result);
        }
    }
    
    /**
     * auth	：fire
     * content：ajax获取上周发布的最后一条未读的公告消息
     * time	：2017-10-23
     **/
    public function noticeSetStatus($id){
         
        if(IS_AJAX){
        $uid=session ('admin.uid' );
        $db=M('notices_status');
	    $count=$db->where("notice_id=%d and uid=%d",$id,$uid)->count();
	    if(empty($count)){
	        $data['notice_id']=$id;
	        $data['uid']=$uid;
	        $result['data']=$db->add($data);	     
	    }else{
	        $result['data']=0;
	    }
    
        $result['msg'] 		= '操作成功';
        $result['status'] 	=  '100';       
        $this->ajaxReturn($result);
        }
    }
    
    
	/**
	 * auth	：fangkai
	 * content：改变订单统计数据展示方式
				type ：1 最近七天数据，2 最近一个月数据，3 最近一年数据
	 * time	：2016-9-1
	**/
	public function changeOrderCountType(){
		if(IS_AJAX){
			$type = I('post.type','1','intval');
			if(!in_array($type,array(1,2,3))){
				$result['msg'] 		= '操作错误';
				$result['status']	= '0';
				$this->ajaxReturn($result);
				exit;
			}

			$data = $this->getData($type);

			$result['msg'] 		= '操作成功';
			$result['status'] 	=  '100';
			$result['data']		= $data;
			$this->ajaxReturn($result);
		}
	}

	/**
	 * auth	：fangkai
	 * content：获取N天/月/年订单总数数据，以及模板图表X轴等相关数据
				type ：1 最近七天数据，2 最近一个月数据，3 最近一年数据
	 * time	：2016-9-1
	**/
	public function getData($type=1){
		switch($type){
			case 1:
				$day = 6;
				break;
			case 2:
				$day = 29;
				break;
			case 3:
				$month = 11;
		}
		//根据类型获取数据
		$O		= D('Common/Order');

		$nowTime 	= time();										//获取当前时间
		$X_date	 	= array();										//定义X轴坐标数据变量
		$orderCount = array();										//定义展示的数据变量
		$array		= array();
		$totalCount = '';
		$where['agent_id'] = $this->agent_id;
		$where['order_status']		= array('in',array(2,3,4,5,6));	//订单状态为已支付的
		switch($type){
			case 1:													//获取最近七天，最近30天数据
			case 2:
				for ($i=$day;$i>=0;$i--){
					$X_date[] = date('m-d',$nowTime-$i*24*3600);
					$sTime	  = strtotime(date("Y-m-d",strtotime("-$i day")));
					$eTime    = $sTime + 24*3600-1;
					$where['create_time']	= array('between',''.$sTime.','.$eTime.'');

					$orderList    	= $O->getOrderList($where);
					if($orderList){
						$orderCount[]	= count($orderList);
						$totalCount    += count($orderList);
					}else{
						$orderCount[]	= 0;
					}
				}

				break;
			case 3:													//获取最近一年的数据
				for ($i=$month;$i>=0;$i--){
					$nowmonth = date("Y-m",strtotime("-$i month"));
					$bTime	  = date('Y-m-01',strtotime($nowmonth.'-01'));
					$sTime    = strtotime($nowmonth.'-01');
					$eTime    = strtotime("$bTime +1 month -1 day")+24*3600-1;
					$where['create_time'] = array('between',''.$sTime.','.$eTime.'');
					$X_date[] = $nowmonth;

					$orderList    	= $O->getOrderList($where);
					if($orderList){
						$orderCount[]	= count($orderList);
						$totalCount    += count($orderList);
					}else{
						$orderCount[]	= 0;
					}
				}
				break;
		}

		$data['orderCount']	= $orderCount;		//最近N天/月/年订单总数组合，赋值到模板展示数据
		$data['X_date'] 	= $X_date;			//最近N天/月/年时间组合，赋值到模板X轴坐标
		$data['totalCount'] = $totalCount;		//总的订单数
		return $data;

	}

	/**
	 * auth	：fangkai
	 * content：改变产品销量统计数据展示方式
				type ：1 裸钻（白钻/彩钻），3 成品
	 * time	：2016-9-2
	**/
	public function changeProType(){
		if(IS_AJAX){
			$type = I('post.type',1,'intval');
			if(!in_array($type,array(1,3))){
				$result['msg'] 		= '操作错误';
				$result['status']	= '0';
				$this->ajaxReturn($result);
				exit;
			}

			$data = $this->getSaleData($type);

			$result['msg'] 		= '操作成功';
			$result['status'] 	=  '100';
			$result['data']		= $data;
			$this->ajaxReturn($result);
		}
	}

	/**
	 * auth	：fangkai
	 * content：获取产品销量数据，以及模板图表X轴等相关数据
				type ：1 裸钻（白钻/彩钻），3 成品
	 * time	：2016-9-1
	**/
	public function getSaleData($type=1){

		$O		= D('Common/Order');
		$OS		= D('Common/OrderGoods');


		$ord_where['agent_id']	= $this->agent_id;
		$ord_where['order_status']	= array('in',array(2,3,4,5,6));			//订单状态为已支付的
		$ord_eTime		= time();
		$ord_sTime   	= strtotime(date('Y-m-d',time())) - 29*3600*24;		//近一个月天之前的开始时间（作30天算）
		$ord_where['create_time'] = array('between',''.$ord_sTime.','.$ord_eTime.'');

		$getOrderPayList= $O->getOrderList($ord_where);						//查询出近一个月的订单数列表（作30天算）

		$saleCount	= array();												//定义最近一个月产品销量数组
		$X_prodate = array();												//定义最近一个月产品分类X轴数据数组
		$totalSaleCount = '';												//定义最近一个月产品销量总数
		if($getOrderPayList){

			$order_id_array = array_column($getOrderPayList,'order_id');	//取出order_id为一个数组
			$pro_where['order_id'] 	= array('in',$order_id_array);

			switch($type){
				case 1:
					$pro_where['goods_type']= 1;							//条件主要为裸钻
					$field		= "sum(goods_number) as count,luozuan_type";
					$group		= ' `luozuan_type` ';
					$orderSaleList		= $OS->getOrderSaleNumberSum($pro_where,$field,$group);	//查询出已经付款的订单的商品列表

					foreach($orderSaleList as $keys=>$vals){
						if($vals['luozuan_type'] == 1){
							$X_prodate[1] = '彩钻';
							$saleCount[1] += $vals['count'];
						}else{
							$X_prodate[0] = '白钻';
							$saleCount[0] += $vals['count'];
						}
					}
					$totalSaleCount = $saleCount[0] + $saleCount[1];

					break;
				case 3:
					$GoodsCategory = D('Common/GoodsCategory');

					$field = 'cg.category_id,cg.category_name,cg.pid,cc.name_alias,cc.img,cc.agent_id,cc.sort_id';
					$category_where['cg.pid'] = 0;
					$category_where['cc.agent_id'] = $this->agent_id;
					$join = 'left join zm_goods_category_config as cc on cg.category_id = cc.category_id';
					$sort = 'cc.sort_id ASC';
					$category_list = $GoodsCategory->getCategoryListTwo($field,$join,$category_where,$sort);	//查询出所有的一级分类

					$pro_where['goods_type']= array('in',array(3,6));							//条件主要为成品和代销成品
					$field		= "sum(goods_number) as count,p_category_id";
					$group		= ' `p_category_id` ';
					$orderSaleList		= $OS->getOrderSaleNumberSum($pro_where,$field,$group);	//查询出已经付款的订单的商品列表

					$orderSaleNumber	= array();
					foreach($orderSaleList as $key=>$val){										//将大类ID赋值给键
						$orderSaleNumber[$val['p_category_id']] = $val;
					}

					foreach($category_list as $k=>$v){											//循环大类，获取每个大类的产品销量统计
						$X_prodate[$k]		 = $v['category_name'].'('.$v['name_alias'].')';
						if($orderSaleNumber[$v['category_id']]['count']){
							$saleCount[$k] 	 = intval($orderSaleNumber[$v['category_id']]['count']);
						}else{
							$saleCount[$k] 	 = 0;
						}
						$totalSaleCount += $saleCount[$k];
					}

					break;
			}
		}
		$data['saleCount']	= $saleCount;			//最近一个月产品销量分类总数量，赋值到模板展示数据
		$data['X_prodate'] 	= $X_prodate;			//最近一个月产品销售总量，赋值到模板X轴坐标
		$data['totalSaleCount'] = $totalSaleCount;	//总的订单数

		return $data;

	}


	/**
	 * auth	：fangkai
	 * content：改变销售总额数据展示方式
				type ：1 今天，2 昨天，3 近30天
	 * time	：2016-9-5
	**/
	public function changedayType(){
		$type = I('post.type',1,'intval');
		if(!in_array($type,array(1,2,3))){
			$result['msg'] 		= '操作错误';
			$result['status']	= '0';
			$this->ajaxReturn($result);
			exit;
		}

		$data = $this->getMoneyData($type);

		$result['msg'] 		= '操作成功';
		$result['status'] 	=  '100';
		$result['data']		= $data;
		$this->ajaxReturn($result);

	}

	/**
	 * auth	：fangkai
	 * content：获取产品销售总额，以及模板图表X轴等相关数据
				type ：1 今天，2 昨天，3 近30天，4 按天，5 按月，6 按年
	 * time	：2016-9-5
	**/
	public function choseDate(){
		if(IS_AJAX){
			$starTime 	= I('post.starTime','');
			$endTime	= I('post.endTime','');
			if(empty($starTime) || empty($endTime) ){
				$result['msg']		= '必须选择开始和结束时间';
				$result['status']	= 0;
				$this->ajaxReturn($result);
			}
			if(strtotime($starTime) > strtotime($endTime)){
				$result['msg']		= '开始时间不能大于结束时间';
				$result['status']	= 0;
				$this->ajaxReturn($result);
			}

			//计算出选择的时间段相隔的天数，用来计算出以何种形式展示数据
			$days 	  = round((strtotime($endTime)-strtotime($starTime))/86400)+1;
			$type = 4;
			if($days > 0 && $days <= 31){
				$type = 4;
			}else if($days > 31 && $days <= 366){
				$type = 5;
			}else if($days > 366 ){
				$type = 6;
			}
			$data = $this->getMoneyData($type,$days,$starTime,$endTime);
			$data['starTime']	= $starTime;
			$data['endTime']	= $endTime;

			$result['msg'] 		= '操作成功';
			$result['status'] 	= '100';
			$result['data']		= $data;
			$this->ajaxReturn($result);
		}

	}


	/**
	 * auth	：fangkai
	 * content：获取产品销售总额，以及模板图表X轴等相关数据
				type ：1 今天，2 昨天，3 近30天，4 按天，5 按月，6 按年
	 * time	：2016-9-5
	**/
	public function getMoneyData($type=1,$days,$starTime,$endTime){
		$OP		 = D('Common/OrderPayment');
		$nowTime = time();

		$mon_where['agent_id']	     = $this->agent_id;
		$mon_where['payment_status'] = 2;									//按照已审核的付款单算
		$moneyCount = array();												//定义销售额的数据变量
		$X_totalMoneydate = array();										//定义X轴坐标数据变量
		switch($type){
			case 1:
				$X_totalMoneydate[0] = '今天销售总额';
				$starTime	= strtotime(date('Y-m-d',$nowTime));
				$endTime	= $starTime + 24*3600-1;
				break;
			case 2:
				$X_totalMoneydate[0] = '昨天销售总额';
				$endTime	= strtotime(date('Y-m-d',$nowTime))-1;
				$starTime	= $endTime - 24*3600+1 ;
				break;
			case 3:
			case 4:
				if($starTime && $endTime){									//如果开始和结束时间存在，则获取该时间段数据，否则获取近30天数据
					$day 	= $days-1;
					$nowTime= strtotime($endTime);
				}else{
					$day 	= 29;
				}

				for ($i=$day;$i>=0;$i--){
					$X_totalMoneydate[]  = date('m-d',$nowTime-$i*24*3600);
					$starTime	   		 = strtotime(date("Y-m-d",strtotime("-$i day",$nowTime)));
					$endTime			 = $starTime + 24*3600-1;
					$mon_where['create_time'] = array('between',''.$starTime.','.$endTime.'');
					$orderMoneyCount	 = $OP->getOrderCount($mon_where,'payment_price');	//查询出已付款的付款单总额
					$moneyCount[]		 = $orderMoneyCount/10000;			//转化为万元
					$totalMoneyCount	+= $orderMoneyCount/10000;			//转化为万元
				}
				break;
			case 5:
				$month	= (date('Y',strtotime($endTime))- date('Y',strtotime($starTime))) * 12 + date('m',strtotime($endTime)) - date('m',strtotime($starTime));									  //如果按月计算，先计算出相差的月份
				$endTimeMonth= strtotime(date("Y-m",strtotime($endTime)));	//计算出结束日期的年-月
				for ($i=$month;$i>=0;$i--){
					$nowmonth = date("Y-m",strtotime("-$i month",$endTimeMonth));
					$bTime	  = date('Y-m-01',strtotime($nowmonth.'-01'));
					//如果为起始月份，则开始时间为选择的开始时间，否则为每月头一天的日期
					if($i == $month){
						$sTime    = strtotime($starTime);
					}else{
						$sTime    = strtotime($nowmonth.'-01');
					}
					//如果为结束月份，则结束时间为选择的结束时间，否则为每月末尾的日期
					if($i == 0){
						$eTime    = strtotime($endTime);
					}else{
						$eTime    = strtotime("$bTime +1 month -1 day")+24*3600-1;
					}

					$mon_where['create_time'] = array('between',''.$sTime.','.$eTime.'');
					$X_totalMoneydate[]	= $nowmonth;
					$orderMoneyCount   	= $OP->getOrderCount($mon_where,'payment_price');
					$moneyCount[]		= $orderMoneyCount/10000;			//转化为万元
					$totalMoneyCount   += $orderMoneyCount/10000;			//转化为万元
				}
				break;
			case 6:
				$year	= date('Y',strtotime($endTime))- date('Y',strtotime($starTime));	//如果按月计算，先计算出相差的年份
				for ($i=$year;$i>=0;$i--){
					$nowmonth = date("Y",strtotime("-$i year",strtotime($endTime)));
					$bTime	  = date('Y-01-01',strtotime($nowmonth.'-01'));
					//如果为起始月份，则开始时间为选择的开始时间，否则为每月头一天的日期
					if($i == $year){
						$sTime    = strtotime($starTime);
					}else{
						$sTime    = strtotime($nowmonth.'-01-01');
					}
					//如果为结束月份，则结束时间为选择的结束时间，否则为每月末尾的日期
					if($i == 0){
						$eTime    = strtotime($endTime);
					}else{
						$eTime    = strtotime("$bTime +1 year -1 day")+24*3600-1;
					}
					$mon_where['create_time'] = array('between',''.$sTime.','.$eTime.'');
					$X_totalMoneydate[]	= $nowmonth;
					$orderMoneyCount   	= $OP->getOrderCount($mon_where,'payment_price');
					$moneyCount[]		= $orderMoneyCount/10000;			//转化为万元
					$totalMoneyCount   += $orderMoneyCount/10000;			//转化为万元
				}
				break;
		}

		if(in_array($type,array(1,2))){
			$mon_where['create_time'] = array('between',''.$starTime.','.$endTime.'');
			$orderMoneyCount	= $OP->getOrderCount($mon_where,'payment_price');	//查询出已付款的付款单总额
			//print_r($OP->getlastsql());exit;
			$moneyCount[0]		= $orderMoneyCount/10000;					//转化为万元
			$totalMoneyCount 	= $moneyCount[0];							//转化为万元
		}

		$data['X_totalMoneydate']	=  $X_totalMoneydate;					//得到图表X轴天数，赋值到模板X轴坐标
		$data['moneyCount']			=  $moneyCount;							//得到不同天/月数类型的销售总额，赋值到模板展示数据
		$data['totalMoneyCount']	=  $totalMoneyCount;					//得到总的销售总额，赋值到模板

		return $data;
	}


	/**
	 * auth	：fangkai
	 * content：由于order_goods表新增加字段，故而写此方法更新数据库【成品和订制】的订单数据,只需要在inde方法调用此方法即可
	 * time	：2016-9-6
	**/
	public function updateOrderGoodsProduct(){
		$orderGoods = M('order_goods');
		$where['goods_type']= array('in',array(3,4));
		$orderList	= $orderGoods->where($where)->select();
		$proData	= array();
		foreach($orderList as $key=>$val){
			$goods_info = unserialize($val['attribute']);
			$catInfo	= $this->getMasterCat($goods_info['category_id']);

			$save['p_category_id']	= $catInfo['category_id'];
			$save['category_id']	= $goods_info['category_id'];
			$save_where['og_id']	= $val['og_id'];
			$action = $orderGoods->where($save_where)->save($save);
			if($action){
				$proData['success'] += 1;
			}else{
				$proData['error']	+= 1;
			}
		}
		print_r($proData);
		echo '<br/>';

	}

	/**
	 * auth	：fangkai
	 * content：由于order_goods表新增加字段，故而写此方法更新数据库【裸钻】的订单数据,只需要在inde方法调用此方法即可
	 * time	：2016-9-6
	**/
	public function updateOrderGoodsLuozuan(){
		$orderGoods = M('order_goods');
		$where['goods_type']= 1;
		$orderList	= $orderGoods->where($where)->select();
		$luozuanData= array();
		foreach($orderList as $key=>$val){
			$goods_info = unserialize($val['attribute']);
			if($goods_info['luozuan_type'] == 1){
				$save['luozuan_type'] = 1;
			}else{
				$save['luozuan_type'] = 0;
			}
			$save_where['og_id']	  = $val['og_id'];
			$action = $orderGoods->where($save_where)->save($save);
			if($action){
				$luozuanData['success'] += 1;
			}else{
				$luozuanData['error']	+= 1;
			}
		}
		print_r($luozuanData);exit;

	}


	/**
	 * auth	：fangkai
	 * content：递归获取最上级产品分类
	 * time	：2016-7-7
	**/
	protected function getMasterCat($category_id){
		$goods_category = M('goods_category');
		$where['category_id'] = $category_id;
		$catInfo = $goods_category->where($where)->find();
		if($catInfo['pid'] != 0){
			$catInfo = self::getMasterCat($catInfo['pid']);
		}
		return $catInfo;
	}

}
