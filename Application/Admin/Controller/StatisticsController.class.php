<?php
/**
 * 统计模块
 * 一些统计计划任务也在这里执行
 */
namespace Admin\Controller;
class StatisticsController extends AdminController {
    
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
    
    // 统计首页
    public function StatisticsIndex(){
    	if(!isSuperAgent()){$this->error('你没有当前权限！');}
        $this->display();
    }
    
    // 分销商统计首页
	public function traderStatistics($tid=''){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
        $this->display();
	}
	
	//业务统计
	public function businessStatistics(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
	    $this->display();
	}
	
	// 统计每日客户注册量
	public function autoUserGrow(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
	    
	}
	
	// 统计每日业务销售量
	public function autoOrderGrow(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
	   
	}
	
	// 统计每周分销商增长
	public function autoTraderGrow(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
	    
	}
	
	// 自动记录上周所有分销商销量
	public function autoTraderRecord(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
	    
	}
	
	// 自动记录上周所有的业务员销量
	public function autoBusinessRecord(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
	    
	}

	//散货统计
	public function sanhuoStat(){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}

	}
	/**
	 * 销售统计
	 */
	public function salesStatistics($p=1,$n=10,$goods_type=0,$order_sn='',$parent_id='',$startTime='',$endTime='',$Uname=''){
		
		//组装订单条件
		$oWhere = $this->buildWhere('o.');
		//组装客户条件
		if($Uname){
			$this->Uname = $Uname;
			$U = M('user');
			$uid = $U->where("username = '$Uname' and agent_id = ".C('agent_id'))->getField('uid');
			if($uid){
				$oWhere .= " and o.uid = '$uid'";
			}else{
				
			}
			
		}
		//组装订单状态条件
		if($order_sn){
			$this->order_sn = $order_sn;
			$oWhere.= " and o.order_sn like '%".$order_sn."'";
		}
		//组装业务员条件
		
		if($this->is_yewuyuan){
			$oWhere.= ' and o.parent_id = '.$this->uid;
		}else{
			if($parent_id){
				$this->parent_id = $parent_id;
				$oWhere.= ' and o.parent_id = '.$parent_id;
			}
		} 
		//组装时间条件
		$this->startTime = $startTime;
		$this->endTime = $endTime;
		if($startTime and $endTime){
			$ST=strtotime($startTime);
			$ET=strtotime($endTime);
			$oWhere .= ' and o.create_time >= '.$ST.' and o.create_time <= '.$ET;
		}
		//组装产品条件
		$gWhere = 'og.agent_id='.C('agent_id');
		if($goods_type){
			$this->goods_type = $goods_type;
			$gWhere .= ' and og.goods_type = '.$goods_type;
		}
		//数据查询
		$OG = M('order_goods');
		//条件筛选
		$join = 'zm_order AS o ON o.order_id = og.order_id and '.$oWhere;
		$field = 'og.*,o.order_sn,o.create_time,o.uid,o.tid';
		$count = $OG->alias('og')->where($gWhere)->field($field)->join($join)->count();
		$Page = new \Think\Page($count,$n);
		$list = $OG->alias('og')->where($gWhere)->field($field)->join($join)->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->GN=$OG->alias('og')->where($gWhere)->field($field)->join($join)->limit($Page->firstRow.','.$Page->listRows)->sum('goods_number');
		$this->GP=$OG->alias('og')->where($gWhere)->field($field)->join($join)->limit($Page->firstRow.','.$Page->listRows)->sum('goods_price');
		//数据处理
		foreach($list AS $key=>$val){
			$goodsInfo = unserialize($val['attribute']);
			$val['goods_name'] = $goodsInfo['goods_name'];
			$val['goods_price'] = empty($val['goods_price_up'])?$val['goods_price_up']:$val['goods_price'];
				$goodsList[] = $val; 
		}
		//dump($this->GP);
		$this->assign('goodsList',$goodsList);
		$this->page = $Page->show();
		//查询出全部业务员
		$AU = M('admin_user');
		$join_group[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
		$join_group[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan = 1';
		$this->businessList = $AU->alias('au')->where('au.agent_id='.C('agent_id'))->field('au.user_id,au.nickname')->join($join_group)->select();
		$this->display(); 
	}
	public function productstatistics($p=1,$n=10){
		if(!isSuperAgent()){$this->error('你没有当前权限！');}
		$this->display(); 
	}
}