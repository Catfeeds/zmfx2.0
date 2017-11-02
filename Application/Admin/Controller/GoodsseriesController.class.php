<?php

/**
 * 产品系列
 * @author 张超豪
 */
namespace Admin\Controller;
use Think\Page;
header("Content-Type: text/html; charset=UTF-8");
class GoodsseriesController extends AdminController {

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
	
	public function goodsSeriesList(){
		$GS = M('goods_series');
    	$list = $GS->where($this->buildWhere(''))->select();
    	$this->list = $this->_arrayRecursive($list, 'goods_series_id');
    	$this->display();
	}

	public function goodsSeriesAdd(){
		if(IS_POST){
			$data['series_name'] = I('post.series_name');
			$data['goods_series_id'] = I('post.id');
			$data['agent_id'] = C("agent_id");
			if(empty($data['series_name']))$this->error('产品系列名称不能为空!');
			$GS = M('goods_series');
			
			$info = $GS->where("series_name = '".$data['series_name'] ."' AND agent_id=".C("agent_id") )->find();
			if(!empty($info))$this->error('产品系列名称'.$data['series_name'].'不能重复!');
			
			if($GS->add($data)){$this->success('产品系列添加成功', '/Admin/Goodsseries/goodsSeriesList');}
			else{$this->error('产品系列添加失败');}
			
    	}else{
			$this->display();
		}	
	}

	public function goodsSeriesUpdate(){
		if(IS_POST){
			$data['series_name'] = I('post.series_name');
			$data['goods_series_id'] = I('post.id');
			if(empty($data['series_name']))$this->error('产品系列名称不能为空!');
			if($_FILES['images_path']['name']){						//判断是否为模版提交，是就直接更新，不是就上传。
				$upload = new \Think\Upload (); // 实例化上传类
				$upload->maxSize = 2097152; 	// 设置附件上传大小
				$upload->exts = array ('jpg','gif','png','jpeg' ); // 设置附件上传类型
				$upload->savePath = './Uploads/category/'; // 设置附件上传目录
				$Finfo = $upload->uploadOne ($_FILES ['images_path'] ); // 上传文件
				if($Finfo){
					$data['images_path'] = $Finfo ['savepath'] . $Finfo ['savename'];
				}else{
					$this->error($upload->getError());
				} 
			}
			$GS = M('goods_series');
			$info = $GS->where("series_name = '".$data['series_name'] ."' AND goods_series_id != '".$data['goods_series_id']."' AND agent_id=".C("agent_id") )->find();
			if(!empty($info))$this->error('产品系列名称'.$data['series_name'].'不能重复!');

			if($GS->save($data)){$this->success('产品系列修改成功');}
			else{$this->error('产品系列修改失败');}
		}else{
    		$data['goods_series_id'] = intval(I('get.id'));
    		if(!empty($data['goods_series_id'])){
				$GS = M('goods_series');
				$this->info = $GS->where('goods_series_id = '.$data['goods_series_id'] )->find();
				$this->display();
			}
    	}
	}
	public function goodsSeriesDelete(){
		$GS = M('goods_series');
		$id = I('get.id');
    	if($GS->where($this->buildWhere(''))->delete($id)){
    		$this->log('success', '产品系列删除成功','ID:'.$id,U('Admin/Goodsseries/goodsSeriesList'));
    	}else{
    		$this->log('error', '产品系列删除失败','ID:'.$id);
    	}
	}
	
	/**
	 * auth	：fangkai
	 * content：产品分类排序，首页展示修改
	 * time	：2016-8-3
	**/
	public function updateHomeShow(){
		if(IS_AJAX){
			$goods_series_id 	= I('post.goods_series_id','','intval');
			$nowName			= I('post.nowName','');
			$nowvalue		 	= I('post.nowValue','');
			$where['goods_series_id'] = $goods_series_id;
			$where['agent_id']	= C('agent_id');
			$goods_series = M('goods_series');
			$check = $goods_series->where($where)->find();
			if(empty($check)){
				$result['status'] = 0;
				$result['msg']	  = '产品系列不存在';
				$this->ajaxReturn($result);
				return false;
			}
			$save[$nowName] = $nowvalue;

			$action = $goods_series->where($where)->save($save);
			if($action){
				$result['status'] = 100;
				$result['msg']	  = '修改成功';
			}else{
				$result['status'] = 0;
				$result['msg']	  = '操作错误';
			}
			$this->ajaxReturn($result);
		}
	}

}