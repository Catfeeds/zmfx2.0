<?php
namespace Admin\Controller;
use Think\Controller;
class TestController extends Controller {
	
	/**
	 * 清除测试数据
	 */
	public function index(){
		if(IS_POST){
			if(is_array($_POST['par'])){
				foreach ($_POST['par'] as $key => $value) {
					switch ($value){
						case 1:
							$num = $this->clearUser();
							$string .= '共删除了'.$num.'个用户!<br />';
							break;
						case 2:
							$num = $this->clearGoods();
							$string .= '共删除了'.$num.'个产品!<br />';
							break;
						case 3:
							$num = $this->clearOrder();
							$string .= '共删除了'.$num.'个订单!<br />';
							break;
						case 4:
							$num = $this->resetGoodsLuozuan();
							$string .= '重置了'.$num.'个证书货产品!<br />';
							break;
						case 5:
							$num = $this->resetGoodsSanhuo();
							$string .= '重置了'.$num.'个散货产品!<br />';
							break;
						case 6:
							$num = $this->clearTrader();
							$string .= '清空了'.$num.'个分销商相关数据!<br />';
							break;
						case 7:
							$num = $this->clearDomain();
							$string .= '删除了'.$num.'个域名!<br />';
							break;
					}
				}
				$this->success($string,'',30);
			}else{
				$this->error('请选择一项你需要清空的项目!');
			}
		}else{
			
			
			
			
			layout(false);
			$this->display();
		}
	}
	
	/**
	 * 清除用户
	 * @param string $time
	 * @return num
	 */
	protected function clearUser($time='2015-07-17'){
		$U = M('user');
		$where = 'reg_time >'.strtotime($time).' and agent_id = '.C('agent_id');
		$res = $U->where($where)->delete();
		return $res;
	}
	
	/**
	 * 清除产品
	 */
	protected function clearGoods(){
		//清除产品表
		$G = M('goods');
		$G->startTrans();
		$res = $G->where(1)->delete();
		//清除关联的属性信息
		$GAA = M('goods_associate_attribute');
		$GAA->where(1)->delete();
		//清除产品图片
		$GI = M('goods_images');
		$imgS = $GI->select();
		foreach ($imgS as $key => $value) {
			unlink('./Public/'.$value['small_path']);
			unlink('./Public/'.$value['big_path']);
		}
		//清除产品关联的副石
		$GAD = M('goods_associate_deputystone');
		$GAD->where(1)->delete();
		//清除产品材质关联的金重损耗
		$GAI = M('goods_associate_info');
		$GAI->where(1)->delete();
		//清除产品材质关联的裸钻
		$GAL = M('goods_associate_luozuan');
		$GAL->where(1)->delete();
		//清除产品关联的规格
		$GAS = M('goods_associate_specification');
		$GAS->where(1)->delete();
		$G->commit();
		return $res;
	}
	
	/**
	 * 清除订单
	 */
	protected function clearOrder(){
		
		return 0;
	}
	
	/**
	 * 重置所有的证书货产品状态
	 * @return number
	 */
	protected function resetGoodsLuozuan(){
		$GL  = D('GoodsLuozuan');
		$res = $GL->where('goods_number <> 1')->setField('goods_number',1);
		return $res;
	}
	
	/**
	 * 重置所有的散货产品状态
	 * @return number
	 */
	protected function resetGoodsSanhuo(){
		$GS = M('goods_sanhuo');
		$list = $GS->field('goods_id')->select();
		$res = count($list);
		foreach ($list as $key => $value) {
			$n = sprintf("%.3f",rand(100000,250000)/1000);
			$GS->where('goods_id = '.$value['goods_id'])->setField('goods_weight',$n);
		}
		return $res;
	}
	
	/**
	 * 清空分销商数据
	 * @param string $tid
	 */
	protected function clearTrader($tid=''){
		
	}
	
	/**
	 * 清空域名数据
	 */
	protected function clearDomain($tid){
		
	}
	
	/**
	 * 删除某个表
	 * @param string $table
	 */
	protected function deleteTable($table){
		
	}
}