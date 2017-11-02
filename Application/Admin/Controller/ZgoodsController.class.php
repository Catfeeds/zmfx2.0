<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/29
 * Time: 18:38
 */
namespace Admin\Controller;
class ZgoodsController extends AdminController {
    //首页菜单列表
    public function index($p=1,$n=13){
        $agent_id = C('agent_id');
        $param = array(
            'agent_id'=>$agent_id,
            'no_page'=>1,
            'all_top'=>1
        );
        $CM = D('Common/BanfangUrl');
        $data = $CM->getTreeLists($param);
        $data = $CM->setListsAll($data);
        $this->lists = $data;
        $this->display();
    }
    //添加首页菜单
    public function addNewMenu(){
        if(IS_POST){
            $this->saveMenuInfo();
        }else{
            $this->getMenuInfo();
            $this->display();
        }
    }
    public function editNewMenu(){
        if(IS_POST){
            $this->saveMenuInfo();
        }else{
            $this->getMenuInfo();
            $this->display('addNewMenu');
        }
    }
    //系列列表
    public function cateLists($p=1,$n=13){
        $searchs = $_POST;
        $agent_id = C('agent_id');
        $param = array(
            'agent_id'=>$agent_id,
            'n'=>$n,
            'ca_rank'=>intval(I('ca_rank'))
        );
        $CM = D('Common/BanfangCate');
        $data = $CM->getCateLists($param);
        $this->lists = $data['lists'];
        $this->page = $data['page'];
        $this->cate_arr = $CM->getCateRankName();
        $this->searchs = $searchs;
        $this->display();

    }
    //添加系列
    public function addCate(){
        if(IS_POST){
            $this->saveCateInfo();
        }else{
            $this->getCateInfo();
            $this->display();
        }

    }

    public function editCate(){
        if(IS_POST){
            $this->saveCateInfo();
        }else{
            $this->getCateInfo();
            $this->display('addCate');
        }
    }
    //删除分类
    public function deleteCate(){
        $info = array(
            'status'=>0,
            'msg'=>'删除失败'
        );
        $id = intval(I('id'));
        if($id>0){
            $CM = D('Common/BanfangCate');
            $data = $CM->where('id="'.$id.'" AND agent_id='.C('agent_id'))->find();
            if(!empty($data) && in_array($data['ca_rank'],array(1,2))){
                $bool = $CM->where('id="'.$id.'" AND agent_id='.C('agent_id'))->delete();
            }else{
                $info = array(
                    'status'=>0,
                    'msg'=>'您没有权限删除，请联系钻明公司技术人员负责删除'
                );
            }

        }
        if($bool){
            $info = array(
                'status'=>100,
                'msg'=>'删除成功'
            );
        }

        $this->ajaxReturn($info);
    }
    //删除菜单
    public function deleteMenu(){
        $info = array(
            'status'=>0,
            'msg'=>'删除失败'
        );
        $id = intval(I('id'));
        if($id>0){
            $CM = D('Common/BanfangUrl');
            $data = $CM->where('id="'.$id.'" AND agent_id='.C('agent_id'))->find();
            $data_c = $CM->where('na_parent_id="'.$id.'" AND agent_id='.C('agent_id'))->find();
            if(!empty($data_c)){
                $info = array(
                    'status'=>0,
                    'msg'=>'请先删除子集再来删除这条记录'
                );
            }elseif(empty($data)){
                $info = array(
                    'status'=>0,
                    'msg'=>'该条记录不存在'
                );
            }else{
                $bool = $CM->where('id="'.$id.'" AND agent_id='.C('agent_id'))->delete();
            }


        }
        if($bool){
            $info = array(
                'status'=>100,
                'msg'=>'删除成功'
            );
        }

        $this->ajaxReturn($info);
    }
    //新增|编辑系列  公用操作
    protected function getCateInfo(){
        $CM = D('Common/BanfangCate');
        $cate_arr = $CM->getCateRankName(1);
        $id = intval(I('get.id'));
        if($id>0){
            $info = $CM->where('id="'.$id.'"')->find();
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
    //新增|编辑系列  公用操作
    protected function saveCateInfo(){
        $id = intval(I('post.id'));
        $agent_id = C('agent_id');
        $success = '添加成功';
        if($id){
            $success = '编辑成功';
        }
        $data = array(
            'ca_name' => trim(I('ca_name')),
            'ca_code' => trim(I('ca_code')),
            'ca_rank' => intval(I('ca_rank')),
            'ca_sort' => intval(I('ca_sort')),
            'ca_status'=>1,
            'agent_id'=>$agent_id,
            'pid' =>intval(I('ca_rank')),
            'top_id' =>intval(I('ca_rank')),
        );
        if(!$data['ca_name']){
            $this->error('系列名称不能为空');
        }
        if(!$data['ca_code']){
            $this->error('EOS系统编码不能为空');
        }
        if(!in_array($data['ca_rank'],array(1,2))){
            $this->error('请选择功能类别');
        }
        if($data['ca_sort']<0){
            $this->error('排序不能小于0');
        }

        $CM = D('Common/BanfangCate');
        $info = $CM->where(array('id'=>array('neq',$id),'ca_name'=>$data['ca_name']))->find();
        if($info){
            $this->error('该名称已经存在');
        }
        if($id>0){
            $CM->where(array('id'=>$id))->save($data);
        }else{
            $id = $CM->add($data);
        }
        $this->success($success,U('Admin/Zgoods/editCate/id/'.$id));
    }
    //新增|编辑菜单  公用操作
    protected function getMenuInfo(){
        $CM = D('Common/BanfangCate');
        $NM = D('Common/BanfangUrl');
        $id = intval(I('get.id'));
        $info = array();
        $data = array();
        if($id>0){
            $info   = $NM->where(array('id'=>$id))->find();
            if(empty($info)){
                $this->error('该信息不存在');
            }
        }
        $data['select_lists'] = $NM->getNavUrlAllRank($id);
        $data['url_type'] = $NM->getNavUrlTypeLists();
        $data['select_ca'] = $CM->field('id,ca_name name')->where('pid !=0')->order('ca_rank asc,ca_sort desc,id desc')->select();
        $this->info = $info;
        $this->data = $data;
    }
    //新增|编辑菜单  公用操作
    protected function saveMenuInfo(){
        $NM = D('Common/BanfangUrl');
        $id = intval(I('post.id'));
        $agent_id = C('agent_id');
        $success = '添加成功';
        if($id){
            $success = '编辑成功';
        }
		//菜单APP图片上传
		if($_FILES['appimg']['name']){
			$upload           	= new \Think\Upload();                // 实例化上传类
			$upload->maxSize	= 1024000 ;                           // 设置附件上传大小  1MB
			$upload->exts		= array('png','jpg');                       // 设置附件上传类型   , 'png'
			$upload->rootPath	= './Public/Uploads/appimg/'; 	  // 设置附件上传根目录
			$upload->savePath	=  ''; // 设置附件上传（子）目录
			$appimgAction		=   $upload->uploadOne($_FILES['appimg']);
			if($appimgAction){
				$appimg		= './Uploads/appimg/'.$appimgAction['savepath'].$appimgAction['savename'];
			}else{
				$appimg 	= '';
				$this->log('error', $upload->getError());
			}
		}
        $data = array(
            'na_name'      => trim(I('na_name')),
            'na_url'       => intval(I('na_url')),
            'appimg'       => $appimg,
            'ca_id'        => intval(I('ca_id')),
            'na_parent_id' => intval(I('na_parent_id')),
            'na_rank'      => 1,
            'na_sort'      => intval(I('na_sort')),
            'na_status'    => intval(I('na_status')),
            'agent_id'     => $agent_id,
            'condition_type'=> intval(I('condition_type')),
        );
        if(!$data['na_name']){
            $this->error('菜单名称不能为空');
        }
        if($data['ca_sort']<0){
            $this->error('排序不能小于0');
        }
        $url_type_arr = $NM->getNavUrlTypeLists();
        if(!isset($url_type_arr[$data['na_url']])){
            $this->error('请选择菜单地址');
        }
        if($data['na_parent_id']>0){
            $parent_info = $NM->where('id='.$data['na_parent_id'])->find();
            if(empty($parent_info)){
                $this->error('上级菜单不存在');
            }
            if($parent_info['na_url']!=$data['na_url']){
                $this->error('菜单地址必须与上级一致');
            }
            $data['na_rank'] = $parent_info['na_rank']+1;
        }
        $data['ca_search_id'] = $NM->getCaSearchId($data['na_parent_id']);
        if($id>0){
            $self_info = $NM->where('id='.$id)->find();
            if(!empty($self_info)){
                //先更自己的信息，如果有下级再更新下级信息
                $bool = $NM->where('id='.$id)->save($data);
                if($self_info['na_parent_id']!=$data['na_parent_id']){
                    $NM->saveNavAllChilds($id);
                }
            }

            $this->success($success,U('Admin/Zgoods/editNewMenu/id/'.$id));
        }else{
            $id = $NM->add($data);

            $this->success($success,U('Admin/Zgoods/index'));
        }

    }

    public function ajaxGetParents(){
        $pid = intval(I('pid'));
        $id = intval(I('id'));
        $agent_id = C('agent_id');
        $info = array(
            'status'=>100,
            'msg'=>'获取数据成功',
            'lists'=>array(),
            'count'=>0
        );
        if($pid>0){
            $NM = D('Common/BanfangUrl');
            $data = $NM->field('id pid,na_name name')->where(array('agent_id'=>$agent_id,'na_parent_id'=>$pid,'id'=>array('neq',$id)))->select();
            if(!empty($data)){
                $info['lists'] = $data;
                $info['count'] = count($data);
            }
        }

        $this->ajaxReturn($info);
    }
    public function setGoldPrice(){
        //实例化材质
        $GM 	= M('banfang_material');
        $BML 	= M('banfang_material_log');
        $where = $this->buildWhere();
        if(IS_POST){
            foreach ($_POST['material_id'] as $key => $value) {
				//价格变动的时候才执行修改操作			zengmingming		 2017-09-14
				$ma_price = $GM->where("ma_id=".$key)->getField("price");
				if($value!=$ma_price){
					$maData = array();
					if($GM->where($where.' and ma_id = '.$key)->setField('price',$value)){
						$maData["log_content"]	=	'修改金价成功！';
						$maData["log_type"]		=	'success';
					}else{
						$maData["log_content"]	=	'修改金价失败！';
						$maData["log_type"]		=	'error';
					}
					$maData["uid"]			=	$_SESSION["admin"]["uid"];
					$maData["ma_id"]		=	$key;
					$maData["price"]		=	$value;
					$maData["create_time"]	=	time();
					$maData["agent_id"]		=	C('agent_id');	
					//添加操作日志
					$BML->add($maData);
            	}
			}
            if(1){
                //$this->success('修改金价成功!所有产品价格已经全部重新计算!');
                $this->success('修改金价成功!');
            }else{
                $this->success('修改金价失败!');
            }
        }else{
            $this->materialList = $GM->where($where)->order('sort desc,ma_id desc')->select();
            $this->display();
        }
    }
	
	/**
	 * auth	：zengmingming
	 * content：金价操作日志
	 * time	：2017-09-14
	**/
    public function goldPriceLog($p=1,$n=20){
        $BML = M('banfang_material_log');
        $BM  = M('banfang_material');	
        $AU  = M('admin_user');	
		$this->create_time  	= I('post.create_time','');
		$startTime = strtotime($this->create_time);
		$endTime   = $startTime+24*60*60;
		$agent_id		= C('agent_id');
		if($this->create_time){
			$count = $BML->where("create_time>$startTime and create_time<$endTime and agent_id=$agent_id and is_del<>1")->count();
        }else{
			$count = $BML->where("agent_id=$agent_id and is_del<>1")->count();
		}
		$Page  = new \Think\Page($count,$n);
        $this->page = $Page->show();
		if($this->create_time){
        	$bmlList = $BML->where("create_time>$startTime and create_time<$endTime and agent_id=$agent_id and is_del<>1")->limit($Page->firstRow.','.$Page->listRows)->order("create_time desc")->select();
		}else{
			$bmlList = $BML->where("agent_id=$agent_id and is_del<>1")->limit($Page->firstRow.','.$Page->listRows)->order("create_time desc")->select();
		}
		foreach($bmlList as $key=>$val){
			$bmArr = $BM->where("ma_id=".$val["ma_id"])->field("name,price")->find();
			$bmlList[$key]["maName"] 		= $bmArr["name"];	
			$bmlList[$key]["create_time"]	= date("Y-m-d H:i:s",$val["create_time"]);	
			$bmlList[$key]["userName"] 	= $AU->where("user_id=".$val["uid"])->getField("user_name");	
		}
		$this->assign('bmlList',$bmlList);
     	$this->display();    
    }

    //报价列表
    public function offerList($p=1,$n=13){

        $GOI = D('Common/BanfangDiamond');
        $setting_param = $GOI->getOfferParam();
        $this->assign('setting_param',$setting_param);
        $banfang_carat_arr = M('banfang_carat')->where(array('agent_id'=>C('agent_id'),'status'=>1))->select();
        $this->banfang_carat_arr = $banfang_carat_arr;
        $search = array();
        if(I('get.carat_section')){
            $search['carat_section'] = I('get.carat_section');
        }
        if(I('get.color')){
            $search['color'] = I('get.color');
        }
        if(I('get.clarity')){
            $search['clarity'] = I('get.clarity');
        }
        $this->assign('search',$search);
        $search['agent_id']	= C('agent_id');
        $count = $GOI->where($search)->count();
        $Page  = new \Think\Page($count,$n);
        $this->page = $Page->show();
        $lists_temp = $GOI->where($search)->limit($Page->firstRow.','.$Page->listRows)->order()->select();
        $lists = $GOI->cateAllData($lists_temp,C('agent_id'));
        $this->assign('lists',$lists);
        $this->display();
    }
    //添加钻石
    public function addDiamonds(){
        $this->offerInfo();
    }
    //编辑钻石
    public function editDiamonds(){
        $this->offerInfo();
    }
    //新增报价|编辑报价
    protected function offerInfo(){
        import("Org.Util.Tools");
        $TOOLS = new \Org\Util\Tools();
        $GOI = D('Common/BanfangDiamond');
        $offer_id = I('request.offer_id',0,'intval');
        $agent_id = C('agent_id');
        //配置 颜色跟净度
        $setting_param = $GOI->getOfferParam();
        $banfang_carat_arr = M('banfang_carat')->where(array('agent_id'=>C('agent_id'),'status'=>1))->select();
        if($offer_id>0){
            $offer_info = $GOI ->where('offer_id='.$offer_id)->find();
        }
        if(empty($offer_info)){
            $offer_info = array('offer_id'=>0);
        }
        $this->setting_param = $setting_param;
        $this->banfang_carat_arr = $banfang_carat_arr;
        $this->offer_info = $offer_info;
        if(!IS_POST){
            $this->display('offerInfo');
        }else{

            if(I('get.submit_type',0)==2){
                $GSUL = M('banfang_diamond_log');
                if(empty($_FILES['file']['name'])){
                    $this->error('请选择上传文件');
                }
                $excel_param = array(
                    'title'=>array('carat','color','clarity','price'),
                    'row_key'=>1,
                    'from_height_from'=>2
                );
                $temp_data = $TOOLS->readUploadExcel($_FILES['file'],$excel_param);

                $count_success = 0;
                $count_success_add = 0;
                $count_success_edit = 0;
                $count_error = 0;
                $error_arr = array();
                foreach($temp_data as $keyx=>$valuex){
                    $verify_data = $GOI->verify_data(array('data'=>$valuex,'offer_id'=>0,'is_excel'=>1,'setting_param'=>$setting_param));
                    $valuex['agent_id']	= $agent_id;
                    $valuex['carat_section']	= $verify_data['carat_section'];
                    if($verify_data['status']){
                        $count_success++;
                        if($verify_data['offer_id']>0){
                            $count_success_edit++;
                            $bool = $GOI->where(array('offer_id'=>$verify_data['offer_id']))->save($valuex);
                        }else{
                            $count_success_add++;
                            $bool = $GOI->add($valuex);
                        }
                    }else{
                        $count_error++;
                        $error_arr[] = '第'.$keyx.'行数据导入失败'.$verify_data['message'];
                    }

                }
                $log_data = array(
                    'count_success'=>$count_success,
                    'count_error'=>$count_error,
                    'message'=>date('Y/m/d').' 数据上传成功，正确数据：'.$count_success.'，错误数据：'.$count_error,
                    'type'=>1,
                    'add_time'=>time()
                );
                $bool = $GSUL->add($log_data);

                $html_top = '共上传成功<span style="color:#7BD339;">'.$count_success.'</span>条数据&nbsp;&nbsp;';
                $html_top .= '共新增<span style="color:#6BD339;">'.$count_success_add.'</span>条数据&nbsp;&nbsp;';
                $html_top .= '共修改<span style="color:#5BD339;">'.$count_success_edit.'</span>条数据&nbsp;&nbsp;';
                $html_top .= '共失败<span style="color:#f00;">'.$count_error.'</span>条数据<br>';
                echo $html_top;

                if(!empty($error_arr)){
                    foreach($error_arr as $error_info){
                        echo $error_info.'<br>';
                    }
                }

            }else{
                //单粒上传
                $data = array(
                    'carat'=>I('post.carat')>0?I('post.carat'):'',
                    'color'=>I('post.color','','trim'),
                    'clarity'=>I('post.clarity','','trim'),
                    'price'=>I('post.price',0,'formatRound'),
                );
                $verify_data = $GOI->verify_data(array('data'=>$data,'offer_id'=>$offer_id,'setting_param'=>$setting_param));
                if($verify_data['status']){
                    $data['agent_id']		= $agent_id;
                    $data['carat_section']	= $verify_data['carat_section'];
                    if($offer_id>0){
                        $bool = $GOI->where('offer_id='.$offer_id.' and agent_id='.$agent_id)->save($data);
                        $this->success("更新成功");
                    }else{
                        $bool = $GOI->add($data);
                        $this->success("添加成功",U('Admin/Zgoods/offerList'));
                    }
                }else{
                    $this->error($verify_data['message']);
                }

                exit;
            }
        }

    }
    //下载excel模板
    public function offerExportMode(){
        $GOI = D('Common/BanfangDiamond');
        $GOI->exportExcel();
    }
    //删除报价
    public function offerDelete(){
        $GBD = D('Common/BanfangDiamond');
        $where = $this->buildWhere();
        $offer_id = I('request.offer_id',0,'intval');
        if($offer_id>0){
            $bool = $GBD->where('agent_id = '.C('agent_id').' AND offer_id='.$offer_id)->delete();
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }

    }
    //钻石报价设置
    public function offerSettingInfo(){
        $GOP = M('banfang_point');
        $GBD = D('Common/BanfangDiamond');
        if(IS_POST){

            $data = array();
            $data_temp = array(
                'min_value'=>I('post.min_value',array()),
                'max_value'=>I('post.max_value',array()),
                'point_value'=>I('post.point_value',array()),
            );

            foreach($data_temp as $key=>$value){
                foreach($value as $ke=>$val){
                    $data[$ke][$key] = $val;
                    $data[$ke]['agent_id'] = C('agent_id');
                }
            }
            $verify_data = $GBD->verify_point($data);
            if($verify_data['status']){
                $bool = $GOP->where(array('use_type'=>1,'agent_id'=>C('agent_id')))->save(array('use_type'=>2));
                $bool = $GOP->addAll($data);
                $bool = $GOP->where(array('use_type'=>2,'agent_id'=>C('agent_id')))->delete();
                $this->success('保存成功');
            }else{
                $this->error($verify_data['message']);
            }
            exit;
        }else{
            $data = $GOP->where(array('use_type'=>1,'agent_id'=>C('agent_id')))->select();
            $this->assign('data',$data);
            $this->display();
        }

    }
	
    public function zgoodsList($p=1,$n=20){
		$this->display();		
	}
	
	/**
	 * auth	：zengmingming
	 * content：产品列表页
	 * time	：2017-09-11
	**/
    public function goodsList($p=1,$n=20){
		$BanfangGoods	= M('banfang_goods');
		$BanfangCate	= M('banfang_cate');

		$goods_sn   	= I('get.goodsSn','');
		$Productname 	= I('get.goodsName','');
		$category_id	= I('get.category','','intval');
		$g_status	    = I('get.gStatus','');

		$where   = array();
		
		if($goods_sn){
			$where['goods_sn']     = array('like','%'.$goods_sn.'%');
		}
		if($Productname){
			$where['goods_name']   = array('like','%'.$Productname.'%');
		}
		if($category_id){
			$where['attr_3']  = $category_id;
		}
		if($g_status!==''){
			$where['g_status']   = $g_status;
		}
		
		$limit = (($p-1)*$n).','.$n;
		$count = $BanfangGoods->where($where)->count();
		$order = 'goods_id desc';
		$Page  = new \Think\Page($count,$n);
        $data  = $BanfangGoods->where($where)->limit($Page->firstRow.','.$Page->listRows)->order($order)->select();
		if(!empty($data)){
			foreach($data as $key=>$val){
				if(!empty($val["banfang_big_series"])){
					$BanfangCateArr = $BanfangCate->where("id=".$val["banfang_big_series"])->field("ca_name")->find();
					$data[$key]["ca_name"] = $BanfangCateArr["ca_name"];	
				}
			}	
		}
		$this->dataList = $data;
        $this->page 	= $Page->show();
		
		

        $this->display();
	}
	/**
	 * auth	：zengmingming
	 * content：修改产品的上下架状态
	 * time	：2017-09-11
	**/
    public function updGoodsGStatus(){
		$BanfangGoods	= M('banfang_goods');
		$data["goods_id"]  = I('post.goods_id','');
		$data["g_status"] 	= I('post.g_status','');
		if($data["g_status"]==2){	$data["g_status"]=0;	}	
		if($BanfangGoods->save($data)){
			$result = array('success'=>true, 'msg'=>'更改成功！', 'data'=>1);	
		}else{
			$result = array('success'=>false, 'msg'=>'更改失败！');	
		}
		$this->ajaxReturn($result);
	}
	/**
	 * auth	：zengmingming
	 * content：批量修改产品的上下架状态
	 * time	：2017-09-18
	**/
    public function updGStatus(){
		$BanfangGoods	= M('banfang_goods');
		$goodsVal   	= I('post.goodsVal','');
		$g_status 		= I('post.g_status','');
		if($g_status==2){	$g_status=0;	}	
		$goodsIdArr 	= explode(",",$goodsVal);
		array_pop($goodsIdArr); 
		$updStatus 		= "";
		$is_existence 	= "";
		foreach($goodsIdArr as $key=>$val){
			$data["goods_id"] = $val;
			$data["g_status"] = $g_status;
			//判断产品原来上架状态
			$y_g_status = $BanfangGoods->where("goods_id=".$data["goods_id"])->getField("g_status");
			if($y_g_status!=$data["g_status"]){
				if($BanfangGoods->save($data)){
					$updStatus = 1;
				}else{
					$updStatus = "";
				}
				//判断是否存在异样的状态
				$is_existence = 1;
			}
		}
		if($is_existence!=''){		
			if($updStatus!=""){
				if($g_status==1){
					$result = array('success'=>true, 'msg'=>'批量上架成功！', 'data'=>1);	
				}else{
					$result = array('success'=>true, 'msg'=>'批量下架成功！', 'data'=>1);	
				}
			}else{
				if($g_status==1){
					$result = array('success'=>false, 'msg'=>'批量上架失败！');	
				}else{
					$result = array('success'=>false, 'msg'=>'批量下架失败！');
				}
			}	
		}else{
			if($g_status==1){
				$result = array('success'=>true, 'msg'=>'批量上架成功！', 'data'=>1);	
			}else{
				$result = array('success'=>true, 'msg'=>'批量下架成功！', 'data'=>1);	
			}
		}
		$this->ajaxReturn($result);	
	}

	/**
	 * auth	：zengmingming
	 * content：查看/修改产品详情页面
	 * time	：2017-09-12
	**/
    public function goodsInfo(){
		$BanfangGoods 	= M('banfang_goods');
		if(IS_POST){
			$I = M('goods_images');//实例化产品图片表
			$data["goods_id"]	=	I('post.goods_id','');
			$data["content"]	=	I('post.content','');
			$res = $BanfangGoods->save($data);				
			//更新产品的图片数据
			$is_res = 0;
			foreach (I('post.images') as $key => $image) {
				if($I->where('images_id='.$image)->setField('goods_id',$data["goods_id"])){
					$is_res = 1;	
				}
			}	
			if($res || $is_res==1){
				$this->success("数据修改成功");
			}else{	
				$this->error('数据修改失败！');
			}
		}else{
    		$BC             		= M('Banfang_cate');//实例化分类配置表
			$G						= D('Common/Goods');//实例化产品表	
			$goods_id   			= I('get.goods_id','');
			$upd  					= I('get.upd','');
			//获取产品信息
			$info 						= $BanfangGoods->where("goods_id=".$goods_id)->find();
    		//大系列
			$info['largeSeries'] 		= $BC -> where('id = '.$info['banfang_category']) ->getField('ca_name');
			//小系列
			$info['smallSeriesInfo'] 	= $BC -> where('id = '.$info['banfang_jewelry']) ->getField('ca_name');
    		//产品分类
			$info['classificationInfo'] = $BC -> where('id = '.$info['banfang_big_series']) ->getField('ca_name');
			//产品款式
			$info['styleInfo']     		= $BC -> where('id = '.$info['banfang_small_series']) ->getField('ca_name');

			//商品所有属性 	banfang_attrs
			$banfang_attrs_arr = explode(",",$info['banfang_attrs']);
			array_pop($banfang_attrs_arr);
			array_shift($banfang_attrs_arr);
			$banfang_attrs 	= 	implode(",",$banfang_attrs_arr);
			$BCList 		= 	$BC->where("id in (".$banfang_attrs.")")->field("id,pid,ca_name")->select(); 
			$pidArr 		= 	array();
			foreach($BCList as $key=>$val){
				$pidArr[] 	= 	$val["pid"];
			}
			$pidStr 		= 	implode(",",array_unique($pidArr));
			$PBCList 		= 	$BC->where("id in (".$pidStr.")")->field("id,ca_name")->select();
			foreach($PBCList as $k=>$v){
				foreach($BCList as $key=>$val){
					if($val["pid"]==$v["id"]){
						$PBCList[$k]["sub"][] = $val;
					}
				}
			}
			$this->attrsList =	$PBCList;

			//产品定制规格
			$BGM 			 = 	M("banfang_goods_material");
			$leftjoin = " LEFT JOIN zm_banfang_material ON zm_banfang_goods_material.ma_id=zm_banfang_material.ma_id";
			$this -> BGMList = 	$BGM->join($leftjoin)->where("zm_banfang_goods_material.goods_id=".$goods_id)->select();
			$BF 			 = 	M("banfang_factory");
			$BFList	 	 	 =  $BF->where("goods_id=".$goods_id)->select();
			//去除.00的参数
			foreach($BFList as $key=>$val){
				$mwArr = explode(",",$val["material_weight"]);
				foreach($mwArr as $k=>$v){
					if($v==".00"){
						unset($mwArr[$k]);
					}	
				}
				$BFList[$key]["material_weight"] = implode(",",$mwArr);
			}
			$this -> BFList	 =	$BFList;	
			
			//获取图片列表
    		$this -> imgsList        	= $G -> get_goods_images($goods_id);
			$this->assign('info',$info);
			$this->assign('upd',$upd);
			$this->display();
		}
	}
	
	/**
	 * auth	：zengmingming
	 * content：用户信息列表
	 * time	：2017-09-13
	**/
    public function zuserList($p=1,$n=20){
		$U 	= M('User');
		$this -> realname 	= I('get.realname','');
		$this -> username 	= I('get.username','');
		$where = array();
		if($this->realname!=''){		
			$where["realname"] 	=   array('like','%'.$this -> realname.'%');		
		}	
		if($this->username!=''){	
			$where["username"]	=	array('like','%'.$this -> username.'%'); 	
		}
		$where["is_zlf"]	=	"1";
		$limit = (($p-1)*$n).','.$n;
		$count = $U->where($where)->count();
		$order = 'reg_time desc';
		$Page  = new \Think\Page($count,$n);
		$this->userlist = $U->where($where)->limit($Page->firstRow.','.$Page->listRows)->order($order)->field("uid,username,realname,ugroup")->select();
		$this->page 	= $Page->show();
		$this->display();	
	}
	/**
	 * auth	：zengmingming
	 * content：用户信息列表
	 * time	：2017-09-13
	**/
	public function updUserUgroup(){
		$U 	= M('User');
		$data["uid"]		=	I('post.uid','');
		$data["ugroup"]		=	I('post.ugroup','');
		if($U->save($data)){
			$result = array('success'=>true, 'msg'=>'修改成功！');	
		}else{
			$result = array('success'=>false, 'msg'=>'修改失败！');	
		}
		$this->ajaxReturn($result);	
	}
}