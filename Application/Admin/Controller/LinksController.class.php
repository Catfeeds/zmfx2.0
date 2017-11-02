<?php
/**
 * 友情链接
 * @author cool
 */
namespace Admin\Controller;
use Think\Page;
class LinksController extends AdminController {
	protected 	$link_model;
	protected  	$targets=array("_blank"=>"新标签页打开","_self"=>"本窗口打开");	
	public function _initialize() {
		parent::_initialize();
		$this->agent_id = C('agent_id');
		$this->link_model = D("Links");  
	}
	
	public function index(){
		$count = $this->link_model->where(array("agent_id"=>$this->agent_id))->count();
		$n = 20;
        $Page = new Page($count,$n);
        $links=$this->link_model->where(array("agent_id"=>$this->agent_id))->limit($Page->firstRow.','.$Page->listRows)->order("listorder asc")->select();
		
		$this->page = $Page->show();
		$this->assign("links",$links);
		$this->display();
	}  
	
	public function add(){
		$this->assign("targets",$this->targets);
		$this->display();
	}
	
	public function add_post(){
		if(IS_POST){
			$data = I('post.');
			$data['agent_id'] = $this->agent_id;
			if ($this->link_model->create($data)) {
				if ($this->link_model->add()!==false) {
					$this->success("添加成功！", U("links/index"));
				} else {
					$this->error("添加失败！");
				}
			} else {
				$this->error($this->link_model->getError());
			}
		
		}
	}
	
	public function edit(){
		$id=I("get.id",'','intval');
		$where['link_id'] = $id;
		$where['agent_id']= $this->agent_id;
		$link=$this->link_model->where($where)->find();
		if(empty($link)){
			$this->error('友情链接不存在',U('/Admin/links/index'));
		}
		$this->assign($link);
		$this->assign("targets",$this->targets);
		$this->display();
	}
	
	public function edit_post(){
		if (IS_POST) {
			$where['agent_id'] = $this->agent_id;
			$where['link_id']  = I('post.link_id','','intval');
			if ($this->link_model->create()) {
				if ($this->link_model->where($where)->save()!==false) {
					$this->success("保存成功！",U("links/index"));
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->link_model->getError());
			}
		}
	} 
	
	
	//排序
	public function listorders() {
		$status = $this->_listorders($this->link_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	//删除
	public function delete(){
		if(isset($_POST['ids'])){
			
		}else{
			$id = intval(I("get.id"));
			$where['link_id']  = $id;
			$where['agent_id'] = $this->agent_id;
			$link=$this->link_model->where($where)->find();
			if(empty($link)){
				$this->error('友情链接不存在',U('/Admin/links/index'));
			}
			if ($this->link_model->where($where)->delete($id)!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	
	}	
	
	protected function _listorders($model) {
        if (!is_object($model)) {
            return false;
        }
        $pk = $model->getPk(); //获取主键名称
        $ids = $_POST['listorders'];
        foreach ($ids as $key => $r) {
            $data['listorder'] = $r;
            $model->where(array($pk => $key,'agent_id'=>$this->agent_id))->save($data);
        }
        return true;
    }      
	
	/**
	 * 显示/隐藏
	 */
	public function toggle(){
		if(isset($_POST['ids']) && $_GET["display"]){
			$ids = implode(",", $_POST['ids']);
			$data['link_status']=1;
			$where['link_id'] = array('in',$ids);
			$where['agent_id']= $this->agent_id;
			if ($this->link_model->where($where)->save($data)!==false) {
				$this->success("显示成功！");
			} else {
				$this->error("显示失败！");
			}
		}
		if(isset($_POST['ids']) && $_GET["hide"]){
			$ids = implode(",", $_POST['ids']);
			$data['link_status']=0;
			$where['link_id'] = array('in',$ids);
			$where['agent_id']= $this->agent_id;
			if ($this->link_model->where($where)->save($data)!==false) {
				$this->success("隐藏成功！");
			} else {
				$this->error("隐藏失败！");
			}
		}
	}
}
?>