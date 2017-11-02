<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/29
 * Time: 18:38
 */
namespace Admin\Controller;
class SgoodsController extends AdminController {
    //首页菜单列表
    public function index($p=1,$n=13){

    }
    //属性列表
    public function goodsAttrLists($p=1,$n=13){
        $searchs = $_POST;
        $agent_id = C('agent_id');
        $param = array(
            'agent_id'=>$agent_id,
            'top_id'=>intval(I('top_id')),
			'status'=>I('status') ? intval(I('status')) : 0,
			'is_base'=>I('is_base') ? intval(I('is_base')) : 0
        );
        $CM = D('Common/AttrBase');
        $data = $CM->getAttrTreeLists($param);
		$this->lists = $CM ->setTreeToLists($data);

		$this->status_arr = array(
				array(
					'id'=>1,
					'name'=>'开启'
				),
				array(
					'id'=>2,
					'name'=>'关闭'
				)
		);
		$this->can_edit_arr = array(
				array(
						'id'=>1,
						'name'=>'部分可改'
				),
				array(
						'id'=>2,
						'name'=>'可修改'
				)
		);
        $this->cate_arr = $CM->getAttrTopLists();
        $this->searchs = $searchs;
        $this->display();

    }
    //添加系列
    public function addAttr(){
        if(IS_POST){
            $this->saveAttrInfo();
        }else{
            $this->getAttrInfo();
            $this->display();
        }

    }

    public function editAttr(){
        if(IS_POST){
            $this->saveAttrInfo();
        }else{
            $this->getAttrInfo();
            $this->display('addAttr');
        }
    }

	public function editAttrZhiDing(){
		$default_arr = array('sort_time'=>time(),'ca_sort'=>0);
		$this->saveAttrInfo($default_arr,1);
	}
    //新增|编辑系列  公用操作
    protected function getAttrInfo(){
        $CM = D('Common/AttrBase');
        $cate_arr = $CM->getAttrTopLists();
        $id = intval(I('get.id'));
        if($id>0){
            $info = $CM->getAttrInfo($id);
            if(empty($info)){
                $this->error('该信息不存在');
            }
        }
        if(empty($info)){
            $info = array();
        }
        $this->info = $info;
        $this->cate_arr = $cate_arr;
    }
    //新增|编辑 分类属性  公用操作
    protected function saveAttrInfo($default_arr=array(),$is_ajax){
		//请注意区分id跟edit_id,id为base表中的id,edit_id才是config表中的id
        $id = intval(I('id'));
        $agent_id = C('agent_id');
        $success = '添加成功';
        if($id){
            $success = '编辑成功';
        }
		$BM = D('Common/AttrBase');
		$BCM = D('Common/AttrBaseConfig');

		$info = $BM->getAttrInfo($id);
		if(empty($info)){
			//基础表不让增删改 关闭新增按钮
			$this->error('信息不存在');
		}
		$data = array(
				'ca_sort' => $info['sort'],
				'agent_id'=>$agent_id,
				'ca_status' =>$info['status'],
				'bid'=>$id,
				'ca_name'=>$info['name'],
				//'ca_is_hand'=>$info['is_hand']
		);
		if(empty($default_arr)){
			$data['ca_sort'] = intval(I('sort'));
			$data['ca_status'] = intval(I('status'));
			if(!$info['ba_is_base']){
				$data['ca_name'] = trim(I('name'));
				//$data['ca_is_hand'] = intval(I('is_hand'));
				if(empty($data['ca_name'])){
					$this->error('名称不能为空');
				}
			}
		}else{
			foreach($default_arr as $key=>$value){
				$data[$key] = $value;
			}
		}
		$edit_id = $BCM->where(array('agent_id'=>$agent_id,'bid'=>$id))->getField('id');
        if($edit_id>0){
			$BCM->where(array('id'=>$edit_id))->save($data);
        }else{
			$edit_id = $BCM->add($data);
        }
		if($is_ajax){
			$this->ajaxReturn(array('status'=>100,'msg'=>'操作成功'));
		}else{
			$this->success($success,U('Admin/Sgoods/goodsAttrLists'));
		}

    }


    public function goodsLists($p=1,$n=20){
		$G	= M('goods');
		$NM = D('Common/GoodsCategory');
		$where   = array('g.agent_id'=>0);
		$field   = 'g.*,s.sell_status';
		$goods_sn = trim(I('goods_sn'));
		$goods_name = trim(I('goods_name'));
		$search_id = intval(I('search_id'));
		$sell_status = intval(I('sell_status'));
		$agent_id = C('agent_id');
		$where['_string'] = '1=1';
		if($goods_sn){
			$where['g.goods_sn']     = array('like','%'.$goods_sn.'%');
		}
		if($goods_name){
			$where['g.goods_name']     = array('like','%'.$goods_name.'%');
		}

		if($sell_status>0){
			if($sell_status==1){
				//上架
				$where['s.sell_status'] = 1;
			}else{
				//下架
				$where['_string'] .= ' AND s.sell_status is null';
			}
		}

		if($search_id){
			$search_id_arr = $NM->where('pid='.$search_id.' OR category_id='.$search_id)->getField('category_id,category_id id');
			if(!empty($search_id_arr)){
				$where['g.category_id']     = array('in',array_values($search_id_arr));
			}
		}
		$this->select_lists = $NM->getCateGoryAllRank($search_id);
		$this->search_id = $search_id;
		$count = $G->alias('g')->field($field)->join('left join zm_goods_gstatus s on g.goods_id=s.goods_id and s.agent_id='.$agent_id)->where($where)->count();
		$order = 'g.goods_id desc';
		$Page  = new \Think\Page($count,$n);

        $data  = $G->alias('g')->field($field)->join('left join zm_goods_gstatus s on g.goods_id=s.goods_id and s.agent_id='.$agent_id)->where($where)->limit($Page->firstRow.','.$Page->listRows)->order($order)->select();

		if(!empty($data)){
			foreach($data as $key=>$val){
				$val['ca_name'] = $NM->getGateGoryName($val['category_id'],'&nbsp;');
				$val['sell_status_name'] = $val['sell_status'] ? '已上架' : '';
				$data[$key] = $val;
			}
		}
		$this->dataList = $data;
        $this->page 	= $Page->show();

		$TC       = M('template_config');
		$is_template_id = $TC->where(array('agent_id'=>C('agent_id'),'template_status'=>'1','template_id'=>8))->getField('template_id');
		if($is_template_id){
			$detail_action = 'goodsDetails';
		}else{
			$detail_action = 'goodsInfo';
		}
		$this->detail_action = $detail_action;
        $this->display();
	}

	/*public function addBaseGoods(){
		$data = array(
				'goods_type'=>7
		);
		$goods_id = addBaseGoods($data);

	}*/
	/**
	 * auth	：zengmingming
	 * content：修改产品的上下架状态
	 * time	：2017-09-11
	**/
    public function ajaxUpdateGoodsStatus(){
		$goods_id_arr = I('goods_id');
		$status = intval(I('sell_status'));
		$agent_id = C('agent_id');
		$GM	= M('goods');
		$SM = M('goods_gstatus');
		$bool = 0;
		if(!empty($goods_id_arr)){

			if($status==1){
				$where = array(
						'g.agent_id'=>0,
						's.sell_status is null'
				);
				if($goods_id_arr[0]!=-1){
					$where['g.goods_id'] = array('in',$goods_id_arr);
				}

				$goods_data = $GM->alias('g')->join('left join zm_goods_gstatus s on g.goods_id=s.goods_id and s.agent_id='.$agent_id)->where($where)->getField('g.goods_id id,g.goods_id gid');
				if(!empty($goods_data)){
					$data = array();
					foreach($goods_data as $val){
						if($val>0){
							$temp = array(
									'goods_id'=>intval($val),
									'agent_id'=>$agent_id,
									'sell_status'=>1
							);
							$data[] = $temp;
						}
					}

					$bool = $SM->addAll($data);
				}
			}else{
				$where = array(
						'agent_id'=>$agent_id,
				);
				if($goods_id_arr[0]!=-1){
					$where['goods_id'] = array('in',$goods_id_arr);
				}
				$bool = $SM->where($where)->delete();
			}
		}

		if($bool){
			$result = ['status'=>100,'msg'=>'操作成功'];
		}else{
			$result = ['status'=>0,'msg'=>'操作失败'];
		}
		$this->ajaxReturn($result);
	}

	public function ajaxGetParents(){
		$pid = intval(I('pid'));
		$info = array(
				'status'=>100,
				'msg'=>'获取数据成功',
				'lists'=>array(),
				'count'=>0
		);
		if($pid>0){
			$NM = M('goods_category');
			$data = $NM->field('category_id pid,category_name name')->where(array('pid'=>$pid))->select();
			if(!empty($data)){
				$info['lists'] = $data;
				$info['count'] = count($data);
			}
		}

		$this->ajaxReturn($info);
	}


}