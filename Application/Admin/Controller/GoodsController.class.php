<?php
/**
 * 产品模块
 */
namespace Admin\Controller;
use Think\Page;
header("Content-Type: text/html; charset=UTF-8");
class GoodsController extends AdminController {


    // 模块跳转
    public function index(){
        foreach ($this->AuthListS AS $k){
            $val = stristr($k,strtolower(MODULE_NAME.'/'.CONTROLLER_NAME));
            if($val and strtolower($val) != strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME)){
               $this->redirect('Admin/Goods/goodsList');
            }
        }
        $this->log('error', 'A1','',U('Admin/Public/login'));

    }

	// 证书货列表
	public function goodsList($p=1,$n=13){
        $Goods       = M("goods_luozuan");
        $luozuanCate = I('luozuanCate','');
		$luozuantype = I('luozuantype',-1);
		$gia_number  = trim(I('gia_number'));			//裸钻编号，
        $certificate = trim(I('certificate'));
		$certificate = addslashes($certificate);
		$min_weight	 = I('min_weight','');
		$max_weight	 = I('max_weight','');
		$clarity	 = I('clarity','');
		$cut		 = I('cut','');
        //获取自己的分销商ID，上级分销商ID，钻名分销商ID以用来获取散货数据
        $GoodsLuozuan = D('Common/GoodsLuozuan');
        $agent_id = $GoodsLuozuan->get_where_agent();
		$where['agent_id']	= array('in',$agent_id);

        if($certificate != ''){
			$this->certificate=$certificate;
			$where['certificate_number']	= array('like','%'.$certificate.'%');
        }
		
		if($gia_number){
			$this->gia_number=$gia_number;
			$where['goods_name']	= $gia_number;
		}

		if($luozuantype!=-1){
			$where['luozuan_type']	= $luozuantype;
        }

        if($luozuanCate == 'COMMON'){
			$where['belongs_id']	= 1;
        }elseif($luozuanCate){
			$where['goods_name']	= array('like','%'.$luozuanCate.'%');
		}
		if($min_weight && $max_weight){
			$where['weight']	= array(array('egt',$min_weight),array('elt',$max_weight));
			$this->min_weight	= $min_weight;
			$this->max_weight	= $max_weight;
		}else if($min_weight){
			$where['weight']	= array('egt',$min_weight);
			$this->min_weight	= $min_weight;
		}else if($max_weight){
			$where['weight']	= array('elt',$max_weight);
			$this->max_weight	= $max_weight;
		}
		if($clarity){
			$where['clarity']	= $clarity;
			$this->clarity	= $clarity;
		}
		if($cut){
			$where['cut']	= $cut;
			$this->cut		= $cut;
		}
		
		/* //去掉特惠钻石
		$preferentialID	= $GoodsLuozuan->getPreferentialID(C('agent_id'));
		$PreferentialID_string	= '';
		if($preferentialID){
			foreach($preferentialID as $val){
				$PreferentialID_string	.= $val['gid'].',';
			}
			$where['gid']	= array('not in',$PreferentialID_string);
		} */
		
		$count = $Goods->where($where)->count();

        $Page  = new \Think\Page($count,$n);
        $data  = $Goods->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('gid desc')->select();

        if($data){
			$dataList = array();
			$stopgap_dataList = array();
			$trader				= M('trader');
			$GoodsLuozuanModel  = D("Common/GoodsLuozuan");
			
			foreach($data as $key=>$val){
				$point = 0;
				if($val['agent_id'] != C('agent_id')){
					
					if($val['luozuan_type'] == 1){
						if($val['agent_id']!='0'){
							$point 			  				= $trader -> where(' t_agent_id = '.C('agent_id'))->getField('caizuan_advantage');
						}else{
							$point 							= $GoodsLuozuanModel -> setLuoZuanPoint('0','1');
						}
					}else{
						if($val['agent_id']!='0'){
							$point 			  		  		= $trader -> where(' t_agent_id = '.C('agent_id'))->getField('luozuan_advantage');
						}else{
							$point 							= $GoodsLuozuanModel -> setLuoZuanPoint('0','0');
						}
					}
				}
				$val['dia_discount'] += $point;
				//采购折扣，只显示白钻
				if($val['luozuan_type']!=1){
					$val['purchase_discount'] = $val['dia_discount'];
				}else{
					$val['purchase_discount'] = '';
				}
                $val      = getGoodsListPrice($val,0,'luozuan', 'single');
				$dataList[$key] = $val;
			}	
 
			if($where!=" agent_id in ($agent_id)"){
				$this -> goodsList_null=base64_encode(serialize($where));
			}
        }

        $this->data = $dataList;
        $this->page = $Page->show();
        $luozuanCates = array(
        	'ZM'=>'ZM'
            ,'FMD'=>'FMD'
            ,'FME'=>'FME'
            ,'FMF'=>'FMF'
            ,'FMH'=>'FMH'
            ,'FMI'=>'FMI'
            ,'FMJ'=>'FMJ'
            ,'FML'=>'FML'
            ,'FMM'=>'FMM'
            ,'FMN'=>'FMN'
            ,'FMP'=>'FMP'
            ,'FMQ'=>'FMQ'
            ,'FMR'=>'FMR'
            ,'FMS'=>'FMS'
            ,'FMT'=>'FMT'
            ,'SH'=>'SH'
            ,'COMMON'=>'COMMON'
        );
        $this->agent_id = C('agent_id');
        $this->assign('luozuanCate');
        $this->assign('type', $type);
		$this->luozuan_type = $luozuantype;
        $this->luozuanCate = $luozuanCate;
        $this->luozuanCates = $luozuanCates;

	    $this->display();
	}

	// 证书货信息
	public function goodsInfo(){
		$shapes = array(
			0 => array('name'=>'ROUND','title'=>'圆形')
			,1 => array('name'=>'OVAL','title'=>'椭圆')
			,2 => array('name'=>'MARQUIS','title'=>'马眼')
			,3 => array('name'=>'HEART','title'=>'心形')
			,4 => array('name'=>'PEAR','title'=>'水滴')
			,5 => array('name'=>'PRINCESS','title'=>'方形')
			,6 => array('name'=>'EMERALD','title'=>'祖母绿')
			,7 => array('name'=>'CUSHION','title'=>'枕形')
			,8 => array('name'=>'RADIANT','title'=>'蕾蒂恩')
			,9 => array('name'=>'BAGUETTE','title'=>'梯方')
		);
		$certificate_types = array(
			0  => array('name'=>'GIA','title'=>'GIA')
			,1 => array('name'=>'HRD','title'=>'HRD')
			,2 => array('name'=>'IGI','title'=>'IGI')
			,3 => array('name'=>'NGTC','title'=>'国检')
			,4 => array('name'=>'GTC','title'=>'省检')
			,5 => array('name'=>'OTHER','title'=>'其它')
		);
		$cut = array(
			0 => array('name'=>'Ideal','title'=>'Ideal')
			,1 => array('name'=>'EX','title'=>'EX')
			,2 => array('name'=>'VG','title'=>'VG')
			,3 => array('name'=>'GOOD','title'=>'GOOD')
			,4 => array('name'=>'OTHER','title'=>'其它')
		);
		$Goods = D("GoodsLuozuan");
		$huilv = C('dollar_huilv');
		if($_POST['submit'] != ''){
			$upload           = new \Think\Upload();                // 实例化上传类
            $upload->maxSize  = 3136512 ;                           // 设置附件上传大小  3MB
            $upload->exts     = array('jpg','png','JPG','PNG','mp4','mp3');                       // 设置附件上传类型   , 'xls', 'xlsx'

            $upload->rootPath = realpath(dirname(__FILE__).'/../../../Public/Uploads/diamond').'/'.C('agent_id').'/pic_video/'; // 设置附件上传目录
            //$upload->rootPath = './Uploads/'; // 设置附件上传根目录
            $upload->savePath =  ''; // 设置附件上传（子）目录

            $info        = $upload->upload();

            if($info){
                if (!file_exists($upload->rootPath)){
                    mkdir($upload->rootPath, 0777, true);
                }
                $savePath    = '/Public/Uploads/diamond/'.C('agent_id').'/pic_video/'.date('Y-m-d',time()); // 设置附件上传目录
                $pic_path    = $savePath.'/'.$info['imageURL']['savename'];
                $video_path  = $savePath.'/'.$info['videoURL']['savename'];
            }

            ////是否有上传图片
            if($info['imageURL']['savename']){
                $big_path = '.'.$savePath.'/'.$info['imageURL']['savename'];
                $image = new \Think\Image();
                $image->open($big_path);

                // 检查图片宽高是否在指定的等比中
                $ratio = number_format($image->width() / $image->height(), 1);
                if (($ratio < 1) or ($ratio > 1.3)) {
                    unlink($big_path); // 删除原文件
                    $this->error('图片不是正方形！');
                }
            }
			if(!empty($info['imageURL']['ext'])){
                if(strtolower($info['imageURL']['ext']) !='jpg' and strtolower($info['imageURL']['ext']) !='png' ){
                    $this->error('上传的图片只能是jpg或者是png');
                }else{
                    $_POST['imageURL'] = $pic_path  ;
                }

            }
			if(!empty($info['videoURL']['ext'])){
                if($info['videoURL']['ext'] !='mp4'){
                    $this->error('上传的视频只能是mp4');
                }else{
                    $_POST['videoURL'] = $video_path ;
                }
            }

			$_POST['price'] = $_POST['dia_global_price']*$_POST['dia_discount']*$_POST['weight']*$huilv/100;
			// 分销商记录分销商的ID
			$groupID = getGroupId($_SESSION['admin']['uid']);
			if($_POST['goods_number']>=1){
				$_POST['goods_number'] = 1;
			}else{
				$_POST['goods_number'] = 0;
			}
			if(getDistributor($_SESSION['admin']['uid'])){
				$_POST['belongs_id'] = $_SESSION['admin']['uid'];
			}else{
				$_POST['belongs_id'] = 1;
			}

			//$_POST['agent_id'] = C("agent_id");

			$luozuan = I('post.luozuan_type','');
			if(!in_array($luozuan,array('0','1'))){
				$this->error('裸钻类型错误');
				exit;
			}
			if($luozuan == '1'){
				$_POST['cut'] = '';
			}else{
				$_POST['intensity'] = '';
			}

			if($_POST['gid'] != ''){
				// 更新操作 分销商只能修改自己的产品
				$where = "gid = ".$_POST['gid'];
				// 验证账号是否是经销商
				$where .= " AND agent_id = ".C("agent_id");
				if($Goods->where($where)->save($_POST)){
					$this->success("数据更新成功");
				}else{
					$this->error("数据更新失败,或者该证书货来自供应商!");
				}

			}else{
                $_POST['belongs_id'] = 1;//是自己本站添加的
				$_POST['agent_id']   = C("agent_id");
				if($Goods->data($_POST)->add()){
					$this->success("数据添加成功");
				}else{
					$this->error("数据添加失败");
				}
			}
		}else{
			$id = $_GET['id'];
			if($id>0){
				$goodsInfo = $Goods->where('gid='.$id)->find();
				$this->assign('goodsInfo',$goodsInfo);
			}
			$this->assign('cut',$cut);
			$this->assign('shapes',$shapes);
			$this->assign('certificate_types',$certificate_types);
	        $this->display();
        }

	}

	/**
	 * 删除证书货
	 * @param int $goods_id
	 */
	public function goodsDelete($id){
	    $GS = D('GoodsLuozuan');
		$GC	= D('goods_compare');
		$id	= I('get.id');
	    if( $GS->where('zm_gid=0')->delete($id) ){
			$GC->where(array('gid'=>$id,'agent_id'=>C('agent_id'),'type'=>0))->delete();
	        $this->log('success', 'A88','ID:'.$id,U('/Admin/Goods/goodsList'));
	    }else{
	        $this->log('error', $id.'不能删除!');

	    }
	}
    /**
     * @author ：张超豪
     * @content：批量删除裸钻
     * @time ：2016-6-29
    **/
    public function batchLuozuanDel(){
        if(IS_AJAX){
            $thisid = I('post.gid','');
            if(empty($thisid)){
                $result['status'] = 0;
                $result['info'] = '请选择证书货';
                $this->ajaxReturn($result);
            }
            $where['agent_id'] = C('agent_id');
            $where['gid'] = array('in',$thisid);
            
            $thisid = substr($thisid,0,-1);
            $thisid_array = explode(',',$thisid);
            
            $GL = D('goods_luozuan');
			//$GC	= D('compare');
            $goods_list = $GL->where($where)->select();

            if($goods_list){
                $action_sign = 0;
                foreach($thisid_array as $key=>$val){
                    //检查是否存在活动商品
                    $check_where['gid'] = $val;
                    $check_where['agent_id'] = C('agent_id');
                    $check = $GL->where($check_where)->delete();
                    if(!$check){
                        continue;
                    }
					//$GC->where(array('gid'=>$val,'agent_id'=>C('agent_id'),'type'=>0))->delete();
                    $action_sign = 1;
                    
                }
                if($action_sign == 1){
                    $result['status'] = 100;
                    $result['info'] = '操作成功';
                    $this->ajaxReturn($result);
                }else{
                    $result['status'] = 0;
                    $result['info'] = '操作失败';
                    $this->ajaxReturn($result);
                }       
            }else{
                $result['status'] = 0;
                $result['info']   = '证书货不存在';
                $this->ajaxReturn($result);
            }
        }
        
    }
	
	/**
	 * auth	：fangkai
	 * @param：特惠钻石列表
	 * time	：2017-4-17
	**/
	public function preferentialList(){
		$Goods			= M("goods_luozuan");
		$n				= 20;
		$get_data		= I('get.');
		
		//获取自己的分销商ID，上级分销商ID，钻名分销商ID以用来获取散货数据
        $GoodsLuozuan	= D('Common/GoodsLuozuan');
        $agent_id 		= $GoodsLuozuan->get_where_agent();
        $where['zm_goods_luozuan.agent_id']	= array('in',$agent_id);
		
		if($get_data['certificate_number']){
			$where['zm_goods_luozuan.certificate_number']	= array('like','%'.$get_data['certificate_number'].'%');
			$this->certificate_number	= $get_data['certificate_number'];
        }
		if($get_data['goods_name']){
			$this->gia_number=$gia_number;
			$where['zm_goods_luozuan.goods_name']	= array('like','%'.$get_data['goods_name'].'%');
			$this->goods_name	= $get_data['goods_name'];
		}
		if($get_data['min_weight'] && $get_data['max_weight']){
			$where['zm_goods_luozuan.weight']		= array(array('egt',$get_data['min_weight']),array('elt',$get_data['max_weight']));
			$this->min_weight	= $get_data['min_weight'];
			$this->max_weight	= $get_data['max_weight'];
		}else if($get_data['min_weight']){
			$where['zm_goods_luozuan.weight']		= array('egt',$get_data['min_weight']);
			$this->min_weight	= $get_data['min_weight'];
		}else if($get_data['max_weight']){
			$where['zm_goods_luozuan.weight']		= array('elt',$get_data['max_weight']);
			$this->max_weight	= $get_data['max_weight'];
		}
		
		if($get_data['color']){
			$where['zm_goods_luozuan.color']		= $get_data['color'];
			$this->color	= $get_data['color'];
		}
		if($get_data['clarity']){
			$where['zm_goods_luozuan.clarity']		= $get_data['clarity'];
			$this->clarity	= $get_data['clarity'];
		}
		if($get_data['cut']){
			$where['zm_goods_luozuan.cut']		= $get_data['cut'];
			$this->cut	= $get_data['cut'];
		}
		$where['zm_goods_preferential.agent_id']	= C('agent_id');
		$count = $Goods
					->join('  right join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid  ')
					->where($where)
					->count();

        $Page  = new \Think\Page($count,$n);
        $data  = $Goods
					->field('zm_goods_luozuan.*,zm_goods_preferential.id as preferential_id ,zm_goods_preferential.discount as pre_discount')
					-> join(' right join zm_goods_preferential on zm_goods_luozuan.gid = zm_goods_preferential.gid ')
					->where($where)
					->limit($Page->firstRow.','.$Page->listRows)
					->order('zm_goods_luozuan.gid desc')
					->select();
		if($data){
			$dataList = array();			
			foreach($data as $key=>$val){
				$point = 0;
				if($val['agent_id'] != C('agent_id')){
					if($val['luozuan_type'] == 1){
						$point = D("Common/GoodsLuozuan") -> setLuoZuanPoint('0','1');
					}else{
						$point = D("Common/GoodsLuozuan") -> setLuoZuanPoint();
					}
				}
				$val['dia_discount'] += $point;
                $val      = getGoodsListPrice($val,0,'luozuan', 'single');
				$dataList[$key] = $val;
			}
			
			if($where!=" agent_id in ($agent_id)"){
				$this -> goodsList_null=base64_encode(serialize($where));
			}
        }
		
		$this->page = $Page->show();
		$this->dataList	= $dataList;
		$this->agent_id = C('agent_id');
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * @param：设为特惠钻石
	 * time	：2017-4-17
	**/
	public function preferential_add(){
		if(IS_AJAX){
			$thisid	= I('post.gid','');
			$pre_discount	= I('post.pre_discount');
			if(empty($thisid)){
                $result['status'] = 0;
                $result['info'] = '请选择证书货';
                $this->ajaxReturn($result);
            }
			//获取自己的分销商ID，上级分销商ID，钻名分销商ID以用来获取散货数据
			$GoodsLuozuan = D('Common/GoodsLuozuan');
			$agent_id = $GoodsLuozuan->get_where_agent();
			$where['agent_id']	= array('in',$agent_id);
            $where['gid'] = array('in',$thisid);
			$where['luozuan_type']	= 0;
			$GL = D('goods_luozuan');
			$goods_list = $GL->where($where)->select();
			
			if($goods_list){
				$save_array	= array();
                foreach($goods_list as $key=>$val){
					$check	= M('goods_preferential')->where(array('gid'=>$val['gid'],'agent_id'=>C('agent_id')))->find();

					if($check){
						continue;
					}
                    $save_array[$key]['gid']	= $val['gid'];
                    $save_array[$key]['supply_goods_id']	= $val['supply_gid']?$val['supply_gid']:0;
					$save_array[$key]['zm_goods_id']	= $val['zm_gid']?$val['zm_gid']:0;
					$save_array[$key]['agent_id']	= C('agent_id');
					$save_array[$key]['create_time']	= date('Y-m-d H:i:s',time());
					$save_array[$key]['discount']	= $pre_discount;
				}
				
				if($save_array){
					$action	= M('goods_preferential')->addAll($save_array);
				}
				if($action){
                    $result['status'] = 100;
                    $result['info'] = '操作成功';
                    $this->ajaxReturn($result);
                }else{
                    $result['status'] = 0;
                    $result['info'] = '操作失败';
                    $this->ajaxReturn($result);
                }       
             }else{
                $result['status'] = 0;
                $result['info']   = '操作失败';
                $this->ajaxReturn($result);
            }
		}
	}
	
	/**
	 * auth	：fangkai
	 * @param：取消特惠钻石
	 * time	：2017-4-17
	**/
	public function preferential_cancel(){
		if(IS_AJAX){
			$thisid	= I('post.gid','');
			if(empty($thisid)){
                $result['status'] = 0;
                $result['info'] = '请选择证书货';
                $this->ajaxReturn($result);
            }
			
			$where['agent_id']	= C('agent_id');
			$where['gid'] 		= array('in',$thisid);
			$action	= M('goods_preferential')->where($where)->delete();

			 if($action){
				$result['status'] = 100;
				$result['info'] = '取消成功';
				$this->ajaxReturn($result);
			}else{
				$result['status'] = 0;
				$result['info'] = '取消失败';
				$this->ajaxReturn($result);
			}
		}
	}
	
	// 产品导入
    public function goodsImport(){
    	//产品导入历史记录
        $luozuanHistory = M("luozuan_history")->alias('LH')->where("LH.agent_id = ".C("agent_id"))->order("LH.addtime desc")->group("LH.name")->limit(10)->join(' LEFT JOIN zm_admin_user as AU ON AU.user_id = LH.uid')->select();
    	$data = array();
    	$luozuanCates = array(
            'ZM'=>'ZM'
            ,'FMD'=>'FMD'
            ,'FME'=>'FME'
            ,'FMF'=>'FMF'
            ,'FMH'=>'FMH'
            ,'FMI'=>'FMI'
            ,'FMJ'=>'FMJ'
            ,'FML'=>'FML'
            ,'FMM'=>'FMM'
            ,'FMN'=>'FMN'
            ,'FMP'=>'FMP'
            ,'FMQ'=>'FMQ'
            ,'FMR'=>'FMR'
            ,'FMS'=>'FMS'
            ,'FMT'=>'FMT'
            ,'SH'=>'SH'
            ,'COMMON'=>'COMMON'
        );
        $dir = __ROOT__."Public/Uploads/diamond/".C('agent_id');
	    $this->assign('agent_id', C('agent_id'));

        $this->files = $luozuanHistory;
        $this->display();
    }

    // 上传文件
    public function uploadFile(){
        import("Org.Util.PHPExcel");
        $PHPExcel=new \PHPExcel();
        $PHPReader=new \PHPExcel_Reader_Excel5();
        //载入文件
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = 3000000000;// 设置附件上传大小
        //$upload->exts  = array('csv','xls','xlsx');// 设置附件上传类型
        $upload->exts  = array('csv');// 设置附件上传类型
        $upload->replace = true;
        $upload->hash = true;
        $upload->saveRule  = '';
        $upload->saveName  = '';
        $upload->autoSub = false;
        $upload->savePath =  'Uploads/diamond/'.C('agent_id').'/';// 设置附件上传目录
        $upload->subName = C('agent_id').'/';

        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功 获取上传文件信息
        	$data['name'] = $info[0]['name'];
        	$data['addtime'] = time();
			$data['agent_id'] = C('agent_id');
        	$data['uid']= $_SESSION['admin']['uid'];
        	M('luozuan_history')->data($data)->add();
            $this->success("上传成功");
        }
    }

    // 格式化excel为数组
    public function format_excel2arr($file='',$sheet=0){
    	import("Org.Util.PHPExcel");
    	$PHPExcel=new \PHPExcel();
    	//如果excel文件后缀名为.xls，导入这个类
    	import("Org.Util.PHPExcel.Reader.Excel5");
        $PHPReader=new \PHPExcel_Reader_Excel5();
        //载入文件
        if(file_exists($file)){
	        $PHPExcel=$PHPReader->load($file);
	        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
	        $currentSheet = $PHPExcel->getSheet($sheet);	//**读取excel文件中的指定工作表*/
			return $currentSheet->toArray();
		}else{
			return null;
		}
    }

    // 导入文件
    public function importFile(){

    	import("Org.Util.PHPExcel");
    	$PHPExcel=new \PHPExcel();
    	//如果excel文件后缀名为.xls，导入这个类
    	import("Org.Util.PHPExcel.Reader.Excel5");
        $PHPReader=new \PHPExcel_Reader_Excel5();
    	$excelname = $_POST['excelname'];
    	$file_url = './Public/Uploads/diamond/'.C('agent_id').'/';


    	if($excelname){
    		foreach($excelname as $key=>$val){
    			$file = $file_url.$val;
    			$Upload = new UploadController();
    			$filetype = $Upload->getFiletype($val);
    			if(!$file){
    				$this->error($val."该文件不存在");
    			}
				if($filetype == "未知"){
					$this->error($val."不支持的文件");
				}
				if(!$Upload->importCSV2DB($file,$filetype)){
					$isupload = false;
				}
    		}
    	}

		$this->success("数据导入成功");
    }

    // 散货列表
    public function sanhuoList($p=1,$n=13){

		$where = array();
        //根据散货编号查询
        if(I('get.goods_sn')){
        	$this -> goods_sn = addslashes(I('get.goods_sn'));
			$where['zm_goods_sanhuo.goods_sn'] = array('like','%'.$this->goods_sn.'%');
        }
        //根据分类查询
        if(I('get.tid')){
        	$this -> tid                  = I('get.tid');
			$where['zm_goods_sanhuo.tid'] = $this->tid;
        }

		//获取自己的分销商ID，上级分销商ID，钻名分销商ID以用来获取散货数据
		$GoodsSanhuo = D('Common/GoodsSanhuo');
		$data        = $GoodsSanhuo->getList($where,'zm_goods_sanhuo.tid desc',$p,$n);
		$sanhuoList  = $data['list'];
		$count       = $data['total'];
		$Page        = new \Think\Page($count,$n);
        $this->page  = $Page->show();
		if($sanhuoList){
			$adminUserObj = M('admin_user');
			$goodsSanhuoLockObj = M('goods_sanhuo_lock');
			foreach($sanhuoList as $key=>$row){
				$sanhuoList[$key]['nickname']       = $adminUserObj->where('user_id = '.$row['user_id'])-> getField('nickname');
				$sanhuoList[$key]['c_goods_weight'] = $goodsSanhuoLockObj->where('goods_id = '.$row['goods_id'])-> getField('goods_weight');;
			}
			$this->sanhuoList = getGoodsListPrice($sanhuoList, 0, 'sanhuo');
		}

        //获取散伙类型列表
        $GST = M('goods_sanhuo_type');
        $this->sanhuoTypeList = $GST->order('sort')->select();
		$this->agent_id = C('agent_id');
        $this->display();
    }

    // 散货信息
    public function sanhuoInfo($goods_id=''){

    	if(IS_POST){
    		$GS   =  D('GoodsSanhuo');
    		$GS   -> startTrans();
    		$post =  I('post.');
			if(!$_POST['highlight']){
				$_POST['highlight'] = 0;
			}
    		if($goods_id){    			
    			$GS_info = $GS -> where('agent_id='.C('agent_id').' and goods_id = '.$goods_id) -> find(); 
				if($GS_info){
                    $GS  -> create();
					$res = $GS -> where('agent_id='.C('agent_id').' and goods_id = '.$goods_id) -> save();
					if(!$res){
						//unset($_POST['goods_price']);//系统同步过来的价格不能修改
                        $this->error('不能修改供应商的产品信息！');
					}
				}
    		}else{
                $_POST['user_id']     = $_SESSION['admin']['uid'];
    			$_POST['create_time'] = time();
				$_POST['agent_id']    = C("agent_id");
    			$GS  -> create();
    			$res =  $GS -> add();
    			$goods_id = $res;
    		}
			$string = '';
    		//计算颜色，净度，切工，钻石分数或筛号百分比和生成数据
    		foreach ($post as $key => $value) {
    			if(is_array($value)){
    				switch  ($key){
    					case 'c_color':$string .= '这包货的颜色是'.$post['color'].'：';$type = '颜色';$t = 1;break;
    					case 'c_clarity':$string .= '<br />净度是'.$post['clarity'].'：';$type = '净度';$t = 2;break;
    					case 'c_cut':$string .= '<br />切工是'.$post['cut'].'：';$type = '切工';$t = 3;break;
    					case 'c_weights':$string .= '<br />分数或筛号是'.$post['weights'].'：';$type = '分数或筛号';$t = 4;break;
    				}
    				$bl = 0;
    				foreach ($value as $k => $v) {
    					if($v['bl'] > 0){
    						$arr['goods_id'] = $goods_id;
    						$arr['cc_type'] = $t;
    						$arr['cc_value'] = $v['name'];
    						$arr['cc_ku'] = $v['bl'];
    						$ccArr[] = $arr;
    						$bl += $v['bl'];
    						$percentage = formatPercentage($v['bl'], $post['goods_weight']);
    						$string .= $v['name'].'为'.$percentage.';';
    					}
    				}
    				if(number_format($bl,3,'.','') != number_format($post['goods_weight'],3,'.','')){
    					$this->log('error', 'A81');
    				}
    			}
    		}
    		$GSC = M('goods_sanhuo_cc');
			$GSC->where('goods_id ='.$goods_id .' AND agent_id='.C("agent_id"))->delete();
    		$GSC->addAll($ccArr);
			$data['update_time'] = time();
			$data['goods_4c'] = $string;
    		$GS->where('goods_id ='.$goods_id .' AND agent_id='.C("agent_id"))->save($data);
    		if($GS->getError()){
    			$GS->rollback();
    			$this->log('error', 'A44','ID:'.$goods_id);
    		}else{
    			$GS->commit();
    			$this->log('success', 'A43','ID:'.$goods_id,U('Admin/Goods/sanhuoList'));
    		}
    	}else{
    		//获取散伙类型列表
    		$GST = M('goods_sanhuo_type');
    		$this->sanhuoTypeList = $GST->order('sort')->select();
    		$GS  = D('GoodsSanhuo');
    		//分数,颜色,净度,切工
    		$GSW = M('goods_sanhuo_weights');
    		$this->weights = $GSW->order('sort')->where('pid=0')->select();
    		$this->color   = M('goods_sanhuo_color')->select();
    		$this->clarity = M('goods_sanhuo_clarity')->select();
    		$this->cut     = M('goods_sanhuo_cut')->select();
    		if($goods_id){
				$this -> info   = $GS -> getInfo(array('zm_goods_sanhuo.goods_id'=>array('in',$goods_id)));
    			//散货信息
    			//$this->info     = getGoodsListPrice($info, 0, 'sanhuo', 'single');
    			//分数或者筛号
    			$weights_name   = $this->info['weights'];
    			$pid            = $GSW->where("weights_name = '$weights_name'")->Field('weights_id')->buildSql();
    			$this->weights2 = $GSW->where('pid = '.$pid)->select();
    			//获取选中的4C数据
    			$GSC  = M('goods_sanhuo_cc');
    			$list = $GSC->where('goods_id='.$goods_id)->select();
    			foreach ($list as $key => $value) {
    				$arr[$value['cc_type']][$value['cc_value']] = $value;
    			}
    			$this->CC = $arr;
    		}else{
    			//分数或者筛号
    			$this->weights2 = $GSW->where('pid = 1')->select();
    		}
    		$this->display();
    	}
    }

    /**
     * 删除散货
     * @param int $goods_id
     */
    public function sanhuoDel($goods_id){
		$goods_id  = I('get.goods_id');
    	$GS        = D('GoodsSanhuo');
    	if( $GS   -> where(' goods_id = "'.intval($goods_id).'" and agent_id = '.C('agent_id') ) -> delete()){
    		$this -> log('success', 'A82','ID:'.$goods_id,U('Admin/Goods/sanhuoList'));
    	}else{
    		$this->log('error', 'A83','ID:'.$goods_id);
    	}
    }

	public function syncshanhuo(){
		$cur_db        = '';
		$rep_db        = C('ZMALL_DB.DB_NAME').'.';

		$agent_id      = C('agent_id');
		$T             = M('trader');
		$trader        = $T -> where(' t_agent_id = '.$agent_id)->find();
		$point         = 1;
		if($trader) {
			$point = 1 + intval($trader['sanhuo_advantage']) / 100;
			$parent_id = get_parent_id();
			if ($parent_id) {
				$trader = $T->where(' t_agent_id = ' . $parent_id)->find();
				if ($trader) {
					$point = $point * (1 + intval($trader['sanhuo_advantage']) / 100);
				}
			}
		}
		$sql = "
				DELETE FROM {$cur_db}`zm_goods_sanhuo_cc` WHERE agent_id = {$agent_id} and zm_goods_id > 0 ;
				INSERT INTO {$cur_db}`zm_goods_sanhuo_cc` ( `zm_goods_id`, `cc_type`, `cc_value`, `cc_ku`, `agent_id`)
				SELECT `goods_id`, `cc_type`, `cc_value`, `cc_ku`,'{$agent_id}' FROM  {$rep_db}`zm_goods_sanhuo_cc`;

				DELETE FROM {$cur_db}`zm_goods_sanhuo` WHERE agent_id = {$agent_id} and zm_goods_id > 0;
				INSERT INTO {$cur_db}`zm_goods_sanhuo` (`supply_id`,`supply_goods_id`,`zm_goods_id`, `location`, `goods_sn`,`tid`, `weights`, `clarity`, `color`, `cut`, `goods_weight`, `goods_price`, `goods_price2`, `goods_4c`, `user_id`, `goods_status`, `create_time`, `update_time`, `highlight`, `agent_id`)
				SELECT `supply_id`,`supply_goods_id`,`goods_id`, `location`, `goods_sn`, `tid`, `weights`, `clarity`, `color`, `cut`, `goods_weight`, `goods_price`*{$point}, `goods_price2`*{$point}, `goods_4c`, `user_id`, `goods_status`, `create_time`, `update_time`, `highlight`, {$agent_id}
				FROM {$rep_db}zm_goods_sanhuo
				WHERE goods_weight > 0 and goods_status = 1;

				UPDATE {$cur_db}zm_goods_sanhuo_cc,{$cur_db}zm_goods_sanhuo set zm_goods_sanhuo_cc.goods_id = zm_goods_sanhuo.goods_id WHERE
				zm_goods_sanhuo_cc.zm_goods_id = zm_goods_sanhuo.zm_goods_id and zm_goods_sanhuo_cc.agent_id = zm_goods_sanhuo.agent_id
				and  zm_goods_sanhuo_cc.agent_id = {$agent_id};

				DELETE FROM {$cur_db}`zm_goods_sanhuo_cc` WHERE agent_id = {$agent_id} and goods_id = 0;
		";
		$sanhuo_m_obj  = D('GoodsSanhuo');
		$sanhuo_m_obj  -> execute($sql);
		echo '1';die;
	}

    //官网（分页）
    public function productCatLits(){
		$this -> list		=  D('Common/GoodsCategory') ->this_product_category();
		$this -> isSuperAgent = isSuperAgent();
    	$this -> display();
    }

	/*
	*	产品分类上传图片页面。
	*	zhy
	*	2016年5月20日 17:40:58
	*/
	public function productCatLitsupdatepic(){
		$pid   = I('get.pid');
		$GC    = M('goods_category_config');
	    $this -> imglist=$GC->field('category_id,img,name_alias')->where('category_id = '.$pid.' and agent_id='.C('agent_id'))->find();
    	$this -> display();
    }
	/*
	*	分类商品上传图片表单
	*	zhy
	*	2016年5月20日 17:40:58
	*/
	public function proupdatepic(){
		$jpg=I('post.parent_id');
		$pid=I('post.pid');
		if($_FILES ['template_img']['name']){			//判断是否为模版提交，是就直接更新，不是就上传。
			$upload = new \Think\Upload (); // 实例化上传类
			$upload->maxSize = 3145728; 	// 设置附件上传大小
			$upload->exts = array ('jpg','gif','png','jpeg' ); // 设置附件上传类型
			$upload->savePath = realpath(dirname(__FILE__).'/../../../Public/').'/Uploads/category/'; // 设置附件上传目录
			$Finfo = $upload->uploadOne ($_FILES['template_img']); // 上传文件
			if($Finfo){
				$date['img'] = $Finfo ['savepath'] . $Finfo ['savename'];
				$checkimg='./Public'.$date['img'];						//获取宽高
				$size=getimagesize($checkimg);
				if($size[0]>900 && $size[1]>300){
					$this->error("图片长宽必须小于900*300");
				}
			}
		}else{
			$date['img']=$jpg;
		}
		$GC=M('goods_category_config');
		if($GC->where('category_id = '.$pid. ' and agent_id='.C('agent_id'))->save($date)){
				$this->success('修改成功',U('Admin/Goods/productCatLits'));
		}else{
				$this->error("修改失败");
		}

    }


	//产品分类
    public function productCatInfo(){
    	if(IS_GET){
    		//组织数据，渲染页面
    		$id = I('get.id');
    		$pid = I('get.pid');
    		$isTop = I('get.isTop');
			if($isTop){
				$this->addTopCategory();die;
			}
    		if(!empty($id))
    			$this->editSubCategory($id);	//编辑二级分类
    		elseif(!empty($pid))
    			$this->addSubCategory($pid);	//添加二级分类
    		else
    			$this->addTopCategory();		//添加顶级分类
    	}elseif(IS_POST){
    		//获取数据，执行DB操作
    		$id = I('post.id');
    		$pid = I('post.pid');
    		if(!empty($id))
    			$this->editSubCategoryDo($id);	//编辑二级分类提交
    		elseif($pid>0)
    			$this->addSubCategoryDo();		//添加二级分类提交
    		else
    			$this->addTopCategoryDo();		//添加顶级分类提交
    	}
    }

    public function addTopCategory(){
    	//显示所有顶级分类供选择
		$GC = M('goods_category');
		$id = I('get.id',0);
		if($id){
			$this->info = $GC->where('category_id = '.$id)->find();
			$this->id   = $id;
		}
    	$this->catList = $GC->where('pid = 0')->select();
    	$this->display();
    }

    public function addTopCategoryDo(){
    	$data['category_name'] = I('post.category_name');
    	$data['pid'] = I('post.pid');
    	$data['pid'] = I('post.pid');
    	if(empty($data['category_name']))
    		$this->error('没有分类名字，添加分类出错!');
    	if(!isset($data['pid']))
    		$this->error('没有上级ID，添加分类出错!');
    	$GC = M('goods_category');
    	if($GC->add($data))
    		$this->success('添加顶级分类成功！');
    	else
    		$this->error('添加顶级分类失败！');
    }
    public function addSubCategory($pid){
    	$GC = M('goods_category');
    	$this->catList = $GC->where('category_id='.$pid)->select();
    	$this->pid = $pid;
    	$this->display();
    }

    public function addSubCategoryDo(){
    	$data['category_name'] = I('post.category_name');
    	$data['pid'] = I('post.pid');
		//检查提交的属性
    	if(empty($data['category_name']))
    		$this->error('没有分类名字，添加分类出错!');
    	if(!isset($data['pid']))
    		$this->error('没有上级ID，添加分类出错!');
    	$GC = M('goods_category');
    	$GC->startTrans();
    	//插入分类表
    	$category_id = $GC->add($data);

		$GCA = M('goods_category_attributes');
		$arr1 = $_POST['attr'][1];
		$arr2 = $_POST['attr'][2];
		$arr3 = $_POST['attr'][3];
		if(empty($arr1))
			$this->error('没有成品属性，添加分类出错!');
		if(empty($arr2))
			$this->error('没有成品规格，添加分类出错!');
		if(empty($arr3))
			$this->error('没有金工石属性，添加分类出错!');

    	//获取成品属性
    	foreach ($arr1 as $key => $value) {
    		$arr['category_id'] = $category_id;
    		$arr['attr_id'] = $value;
    		$arr['type'] = 1;
    		$arrS[] = $arr;
    	}
    	//获取成品规格
    	foreach ($arr2 as $key => $value) {
    		$arr['category_id'] = $category_id;
    		$arr['attr_id'] = $value;
    		$arr['type'] = 2;
    		$arrS[] = $arr;
    	}
    	//获取金工石属性
    	foreach ($arr3 as $key => $value) {
    		$arr['category_id'] = $category_id;
    		$arr['attr_id'] = $value;
    		$arr['type'] = 3;
    		$arrS[] = $arr;
    	}
    	//插入属性
    	if($GCA->addAll($arrS)){
    		$GC->commit();
    		$this->success('添加分类成功');
    	}else{
    		$GC->rollback();
    		$this->error('添加分类失败');
    	}
    }

    public function editSubCategoryDo($id){
    	$arr1 = $_POST['attr'][1];
    	$arr2 = $_POST['attr'][2];
    	$arr3 = $_POST['attr'][3];
    	$id   = I('post.id');
    	$GCA  = M('goods_category_attributes');

    	//修改分类名
    	M('goods_category')->where(['category_id'=>$id])->save(['category_name'=>I('category_name')]);

    	$existAttr = $GCA->where('category_id='.$id)->getField('category_attr_id,attr_id,sort_id,type');//查出此分类的所有已有属性
    	foreach ($existAttr as $key => $value) {	//按type分出3个数组
    		$existAttr[$value['type']][] = $value;
    	}
    	//1.更新成品属性，可改顺序、可添加.新增的insert,原有的sort_id有变则update sort_id
    	foreach ($arr1 as $key => $value){	//逐个检查前端传过来的属性在DB中是否已存在
    		$isNew = true;
    		foreach($existAttr[1] as $gcaVal){
    			if($value==$gcaVal['attr_id']){
    				if($key!=$gcaVal['sort_id']){
    					//前端会传来类似这样的数据 0=>1 1=>2 2=>5 3=>4,可以利用数组的key作为sort_id
    					//如果属性已有,且sort_id与前端的key不相等 ,则拼装where语句及set语句，更新sort_id;否则直接跳过不用处理
    					$updateList[] = array('where'=>array('category_attr_id'=>$gcaVal['category_attr_id']),'set'=>array('sort_id'=>$key));
    				}
    				$isNew=false;
    				breake;	//确定是已存在的属性则可跳出for循环,否则继续循环
    			}
    		}
    		if($isNew)
    			$dataList[] = array('category_id'=>$id,'attr_id'=>$value,'type'=>1,'sort_id'=>$key);//如果是新增的，则insert
    	}

    	//2.更新成品规格,由于只能添加不能修改顺序，故原有的跳出循环,新增的insert
    	foreach ($arr2 as $key => $value){		//逐个检查前端传过来的属性在DB中是否已存在
    		$isNew = true;
    		foreach($existAttr[2] as $gcaVal){
    			if($value==$gcaVal['attr_id']){	//正常情况下，金工石属性不应有重复值
    				$isNew = false;
    				break;						//确定是已存在的属性则可跳出for循环,否则继续循环
    			}
    		}
    		if($isNew)
    			$dataList[] = array('category_id'=>$id,'attr_id'=>$value,'type'=>2,'sort_id'=>$key);//如果是新增的，则insert
    	}

    	//3.更新金工石属性,可改顺序、可添加.新增的insert,原有的sort_id有变则update sort_id,
    	foreach ($arr3 as $key => $value){	//逐个检查前端传过来的属性在DB中是否已存在
    		$isNew = true;
    		foreach($existAttr[3] as $gcaVal){
    			if($value==$gcaVal['attr_id']){
    				if($key!=$gcaVal['sort_id']){
    					//前端会传来类似这样的数据 0=>1 1=>2 2=>5 3=>4,可以利用数组的key作为sort_id
    					//如果属性已有,且sort_id与前端的key不相等 ,则拼装where语句及set语句，更新sort_id;否则直接跳过不用处理
    					$updateList[] = array('where'=>array('category_attr_id'=>$gcaVal['category_attr_id']),'set'=>array('sort_id'=>$key));
    				}
    				$isNew=false;
    				break;
    			}
    		}
    		if($isNew)
    			$dataList[] = array('category_id'=>$id,'attr_id'=>$value,'type'=>3,'sort_id'=>$key);//如果是新增的，则insert
    	}

    	$GCA->startTrans();
		//如果有更新的数据，则更新数据
		if(count($updateList)){
			foreach ($updateList as $updateData){
				$res = $GCA->where($updateData['where'])->setField($updateData['set']);
				if(!$res){
					$GCA->rollback();
					$this->error('更新属性失败1');
				}
			}
		}


		//如果有新数据，则插入数据
		if(count($dataList)){
			$res = $GCA->addAll($dataList);
			if(!$res){
				$GCA->rollback();
				$this->error('添加属性失败2');
			}
		}

    	$GCA->commit();
    	$this->success('更新属性成功',U('Admin/Goods/productCatLits'));

    }

    //编辑二级分类
    public function editSubCategory($id){
    	//根据id获取分类信息
    	$GC         = M('goods_category');
    	$GCA        = M('goods_category_attributes');
    	$this->info = $GC->where('category_id='.$id)->find();
    	//获取此分类的所有属性
    	$join = "zm_goods_attributes ga ON gca.attr_id = ga.attr_id";
    	$field = "gca.attr_id,type,attr_name";
    	//3种属性都只能添加，不能删除 ;
    	//1.成品属性能修改顺序
    	$this->productAttr  = $GCA->alias('gca')->where('category_id='.$id.' AND type=1')->order('sort_id')->join($join)->field($field)->select();
    	//2.成品规格只能添加，不能删除、修改顺序
    	$this->specificAttr = $GCA->alias('gca')->where('category_id='.$id.' AND type=2')->order('sort_id')->join($join)->field($field)->select();
    	//3.金工石属性能修改顺序
    	$this->jgsAttr      = $GCA->alias('gca')->where('category_id='.$id.' AND type=3')->order('sort_id')->join($join)->field($field)->select();
    	//获取上级分类
    	$pid = $GC->where('category_id = '.$id)->getField('pid');
    	$this->topCatName = $GC->where('category_id = '.$pid)->getField('category_name');
    	$this->id = $id;	//把id传给页面的隐藏域hidden，以判断是编辑还是新增
    	$this->display(productCatInfoEdit);
    }

    public function productCatConfig(){
    	//1.显示所有产品分类以供配置,分页
    	$GC    = M('goods_category');
    	//$list = $GC->alias('gc')->join('zm_goods_category_config as gcc on gc.category_id = gcc.category_id','left')->field('gc.*,gcc.category_config_id,gcc.name_alias,gcc.is_show')->select();
    	$list  = $GC->select();
		$where = 'agent_id = '.C('agent_id');
    	$GCC   = M('goods_category_config');
    	$listConfig = $GCC->field('category_config_id,category_id,name_alias,is_show,home_show,sort_id')->where($where)->select();
    	$listConfig = $this->_arrayIdToKey($listConfig,'category_id');	//转换为以category_id为key

    	//根据category_id合并两个数组为$combineArr,方便前端遍历
    	foreach ($list as $key => $value) {
    		$arr['category_config_id'] = $listConfig[$value['category_id']]['category_config_id'];//可用来判断config表中是否有对应记录
    		$arr['category_id'] = $value['category_id'];
    		$has_alias = $listConfig[$value['category_id']]['name_alias'];
    		$arr['name_alias'] = $has_alias ? $has_alias:$value['category_name'];	//别名为空时默认与主站相同
    		$arr['is_show'] = $listConfig[$value['category_id']]['is_show'];
			$arr['home_show'] = $listConfig[$value['category_id']]['home_show'];
			$arr['sort_id'] = $listConfig[$value['category_id']]['sort_id'];
    		$arr['category_name'] = $value['category_name'];
    		$arr['pid'] = $value['pid'];
    		$combineArr[] = $arr;
    	}
    	$this->list = $this->_arrayRecursive($combineArr, 'category_id','pid');
    	$this->isSuperAgent = isSuperAgent();
    	//显示已配置的分类

    	$this->display();
    }

    /**
     * 取出所有属性
     */
    public function productCatAttributeAdd($type){
    	//type 1为成品属性;2为成品规格;3为金工石属性
    	//type为2,成品规格不能选择单选属性
    	$GA = M('goods_attributes');
    	if($type==2)
    		$this->list = $GA->where('input_type!=1')->select();
    	else
    		$this->list = $GA->select();
    	layout(false);
    	$this->display();
    }

    /**
     * 编辑二级分类时，控制可显示的属性以供添加(此分类已有的属性不再显示出来)
     * @param string $type 1为成品属性;2为成品规格;3为金工石属性
     */
    public function productCatAttributeEdit($id,$type){
    	//取出此分类已有的属性id,用来排除
    	$GCA = M('goods_category_attributes');
    	$existAttrIds = $GCA->where('category_id='.$id.' and type = '.$type)->getField('attr_id',true);
    	//取出此分类没有的属性
    	$GA = M('goods_attributes');
		if($existAttrIds) {
			$map['attr_id'] = array('not in', $existAttrIds);
		}
    	//type为2,成品规格不能选择单选属性
    	if($type==2)
    		$map['input_type'] = array('neq',1);
    	$list = $GA->where($map)->field('attr_id,attr_name')->select();
    	$this->list = $list;
    	layout(false);
    	$this->display();
    }

    //删除产品分类
    public function delProductCat($id){
    	$GC = M('goods_category');
    	$G  = D('Common/Goods');
    	//检查是否有下级分类
    	if($GC->where('pid = '.I('get.id'))->count()){
    		$this->error('该分类还有下级分类不能删除分类!');
    	}
    	//检查分类下是否有产品
    	if($G -> where('category_id = '.I('get.id'))->count()){
    		$this->error('该分类还有下级产品不能删除分类!');
    	}
    	if($GC -> where('category_id = '.I('get.id'))->delete()){
    		$this->success('删除分类成功!');
    	} else {
    		$this->error('删除分类错误!');
    	}
    }

    /**
     * 设置属性是否筛选
	 * 2016-4-1新增数据表goods_attrcontorl，分销商属性筛选开关数据储存在这张表中！
     */
    public function setAttributeIsFilter($attr_id,$is_filter){
		$GAC = M('goods_attrcontorl');
		$GA  = M('goods_attributes');
		$where['attr_id']  = $attr_id;
		if(C('agent_id') == 7){
			$actionGA = $GA->where($where)->setField('is_filter',$is_filter);
		}
		$where['agent_id'] = C('agent_id');
		$check_filter = $GAC->where($where)->find();
		if($check_filter){
			$action = $GAC->where($where)->setField('is_filter',$is_filter);
		}else{
			$add['attr_id']  = $attr_id;
			$add['agent_id'] = C('agent_id');
			$add['is_filter'] = $is_filter;
			$action = $GAC->add($add);
		}

        $this->ajaxReturn($action);
    }

    /**
     * 产品属性列表
	 * 2016-4-1 新增两张表关联得到分销商属性筛选开关数据
     */
    public function productAttributeList(){
    	$GA  = M('goods_attributes');
		$GAC = M('goods_attrcontorl');
		$list_all =  $GA->select();
		$list_contorl = $GAC->where(array('agent_id'=>C('agent_id')))->select();
		if($list_contorl){
			foreach($list_all as $key=>$value){
				foreach($list_contorl as $k=>$v){
					if($value['attr_id'] == $v['attr_id']){
						$list_all[$key]['is_filter'] = $v['is_filter'];
						break;
					}
				}
			}
		}
		$list = $list_all;

    	if($list){
    	    $GAV = M('goods_attributes_value');
    	    $attrValue = $GAV->where('attr_id IN ('.$this->parIn($list, 'attr_id').')')->select();
    	    $listS = $this->_arrayIdToKey($list);
    	    foreach ($attrValue as $key => $value) {
    	        $listS[$value['attr_id']]['sub'][] = $value;
    	    }
    	}
    	$this->list = $listS;
		$this->isSuperAgent = isSuperAgent();
    	$this->display();
    }

    /**
     * 添加编辑产品属性
     */
    public function productAttributeInfo(){
    	if(IS_POST){
    		$GA  = M('goods_attributes');
    		$GA -> startTrans();
    		$GAV = M('goods_attributes_value');
    		$attr_value = explode("\n",trim(urldecode($_REQUEST['attr_value'])));
    		$data['attr_name'] = I('post.attr_name');
    		$data['input_type'] = I('post.input_type');
    		if($data['input_type'] == 3){
    			$data['input_mode'] = I('post.input_mode');
    		}
    		if(empty($data['attr_name'])){
    			$GA->rollback();
    			$this->error('添加属性出错,属性名称不能为空');
    		}
    		$attr_id = $GA->add($data);
    		if($_REQUEST['attr_value']){
    			foreach ($attr_value as $key => $value) {
    				$arr['attr_value_name'] = trim($value);
    				if(empty($arr['attr_value_name'])){
    					$GA->rollback();
    					$this->error('添加属性出错,属性值不能为空');
    				}
    				$arr['attr_id'] = $attr_id;
    				$arr['attr_code'] = pow(2,$key);
    				$arrS[] = $arr;
    			}
    			if($GAV->addAll($arrS)){
    				$GA->commit();
    				$this->success('添加属性成功');
    			}else{
    				$GA->rollback();
    				$this->error('添加失败');
    			}
    		}else{
    			$GA->commit();
    			$this->success('添加属性成功');
    		}

    	}else{
    		layout(false);
    		$this->display();
    	}
    }

    /**
     * 属性值列表
     */
    public function productAttributeValueList($attr_id){
    	$GAV = M('goods_attributes_value');
    	$list = $GAV->where('attr_id = '.$attr_id)->select();
    	$this->list = $list;
    	$this->attr_id = $attr_id;
    	layout(false);
    	$this->display();
    }

    /**
     * 添加属性值
     */
    public function productAttributeValueInfo($attr_id,$attr_value_name=''){
    	if(IS_POST){
    		$GAV = M('goods_attributes_value');
    		if(!$attr_value_name){
    			$this->error('属性值名称不能为空');
    		}
    		$attr_code = $GAV->where('attr_id = '.$attr_id)->order('attr_code DESC')->getField('attr_code');
    		//查看最后的那个
    		$arr['attr_value_name'] = trim($attr_value_name);
    		$arr['attr_id'] = $attr_id;
    		$arr['attr_code'] = $attr_code*2;
    		$attr_value_id = $GAV->add($arr);
    		if($attr_value_id){
    			$arr['attr_value_id'] = $attr_value_id;
    			$this->ajaxReturn($arr);
    		}else{
    			$this->error('添加属性值失败');
    		}
    	}else{
    		layout(false);
    		$this->attr_id = $attr_id;
    		$this->display();
    	}
    }

    /**
     * 代销货
     * zhy	find404@foxmail.com
     * 2017年5月8日 14:24:53
     */
	public function disGoodsList($p=1,$n=20){
 
		$ZmallBanfangGoods							= M('banfang_goods','zm_','ZMALL_DB');
		$ZmallBanfangCategory						= M('banfang_category','zm_','ZMALL_DB');
		$ZmallBanfangJewelry						= M('banfang_jewelry','zm_','ZMALL_DB');
		
		$ZmfxGoodsShop								= M('goods_shop');

		$goods_sn   								= I('get.goods_sn','');
		$Productname 								= I('get.goods_name','');
		$category_id	    						= I('get.category','','intval');
        $jewelry_id	    							= I('get.category2','','intval');//二级
		$on_agent_sell	    						= I('get.on_agent_sell');
		$agent_sell	    							= I('get.agent_sell',0);

		$where   = array();

		$where['zm_banfang_goods.on_agent_sell'] = '1';
		/*if($agent_sell=='1'){
			$where['zmfx_db.zm_goods.banfang_goods_id'] =  array('GT',$agent_sell);	
			$where['zmfx_db.zm_goods.agent_id'] 		=  C('agent_id');
		}else{
			if($goods_id){
				$where['zm_banfang_goods.goods_id']     = $goods_id;
			}
			if($goods_sn){
				$where['zm_banfang_goods.goods_sn']     = array('exp'," like '%" . $goods_sn . "%' ");
			}
			if($Productname){
				$where['zm_banfang_goods.goods_name']   = array('exp'," like '%" . $Productname . "%' ");
			}		
			
			if($category_id){
				$where['zm_banfang_goods.category_id']  = $category_id;
			}
			if($jewelry_id){
				$where['zm_banfang_goods.jewelry_id']   = intval($jewelry_id);
			}
 
		
		}*/
		if($goods_sn){
			$where['zm_banfang_goods.goods_sn']     = array('exp'," like '%" . $goods_sn . "%' ");
		}
		if($Productname){
			$where['zm_banfang_goods.goods_name']   = array('exp'," like '%" . $Productname . "%' ");
		}

		if($category_id){
			$where['zm_banfang_goods.category_id']  = $category_id;
		}
		if($jewelry_id){
			$where['zm_banfang_goods.jewelry_id']   = intval($jewelry_id);
		}

		if($agent_sell==1){
			$where['zg.banfang_goods_id']   = array('gt',0);
		}
		if($agent_sell==-1){
			$where['_string'] = ' zg.banfang_goods_id is null';
		}

 
        $limit 				    = (($p-1)*$n).','.$n;


		$join 	 			    = 'join zm_banfang_category on zm_banfang_goods.category_id = zm_banfang_category.category_id ';
		$join 	   	           .= 'join zm_banfang_jewelry on zm_banfang_jewelry.jewelry_id = zm_banfang_goods.jewelry_id ';
		$join 	   	   	  	   .= 'join zm_banfang_goods_associate_attr on zm_banfang_goods.goods_id = zm_banfang_goods_associate_attr.goods_id ';	
		/*if($agent_sell=='1'){
			$join 	   	   	   .= 'join zmfx_db.zm_goods 	on zm_banfang_goods.goods_id = zm_goods.banfang_goods_id';
		}*/
		$join 	   	   	   .= 'left join zmfx_db.zm_goods zg	on zm_banfang_goods.goods_id = zg.banfang_goods_id and zg.agent_id='.C('agent_id').' ';
		
        $count 					= $ZmallBanfangGoods 	-> where($where) ->join($join) -> getField('count( distinct `zm_banfang_goods`.`goods_id` )');
		$order					= 'goods_id desc';
        $field 					= "zm_banfang_goods.goods_sn,zm_banfang_goods.goods_name,zm_banfang_goods.goods_price,zm_banfang_goods.on_agent_sell,zm_banfang_goods.goods_id,zm_banfang_category.category_name,zm_banfang_jewelry.jewelry_name";
		
        $dataList 				= $ZmallBanfangGoods 	-> where($where) ->join($join) -> group('goods_id') -> limit($limit) -> order($order) -> field( $field )-> select();

 
 
		//是否开启
		array_walk($dataList, function (&$via) use($agent_sell) {
			$via['sell_status'] 	= M('goods_shop')->join('join zm_goods on zm_goods.goods_id = zm_goods_shop.goods_id ')->where(' zm_goods.banfang_goods_id='.$via['goods_id'].' and zm_goods.agent_id = '.C('agent_id'))-> getField('zm_goods_shop.sell_status');
 
			// $viagoods_price 		= $this->GetDisGoodsPriceFsockopen('goods_id='.$via['goods_id']);
			// $viagoods_price 		= $this->GetDisGoodsPrice('goods_id='.$via['goods_id']);			
			// $viagoods_price	&& $via['goods_price'] 	= $viagoods_price;
		});
 
		$this->dataList			= $dataList; 				
        $this->category  		= $ZmallBanfangCategory -> select(); 
        $this->cate_children  	= $ZmallBanfangJewelry  -> select();
		$page           		= new Page($count,$n);
        $this->page  		    = $page -> show();
        $this->display();
	}
	
	
    /**
     * 获取板房即时价格
     * zhy	find404@foxmail.com
     * 2017年5月19日 17:38:50
     */
    public function GetDisGoodsPriceFsockopen($where){
		$fp 	= fsockopen('szzmzb.com', 80, $errno, $errstr, 3);
		$head 	= "GET ".'/Home/BanFang/GetBanfangGoodsPrice'."?".$where." HTTP/1.0\r\n";
		$head  .= "Host: ".'szzmzb.com'."\r\n";
		$head  .= "\r\n";
		$write = fputs($fp, $head);
		if(!feof($fp)){
			$price = trim(strstr(fread($fp,398),"\r\n\r\n"));
		}
		return is_numeric($price) ? $price : null;
	}
 
 
    /**
     * 代销货详情	
     * zhy	find404@foxmail.com
     * 2017年5月17日 15:11:45
     */
    public function disGoodsInfo(){
		$goods_id   								= I('goods_id');
		!is_numeric($goods_id) && exit();
		$this->goods_info							= $this->GetDisGoodsFindInsertDelete($goods_id,'find');
		$this->display();
    }
	
	
    /**
     * 代销货详情
     * zhy	find404@foxmail.com
     * 2017年5月17日 15:11:45
     */
    public function GetDisGoodsPrice($where){
		$url 	= C('ZMALL_URL').'/Home/BanFang/GetBanfangGoodsPrice?'.$where;
		$ch 	= curl_init();
		curl_setopt($ch,CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch,CURLOPT_HTTPHEADER, array("Expect: "));
		curl_setopt($ch,CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		curl_setopt($ch,CURLOPT_NOSIGNAL,TRUE);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch,CURLOPT_BINARYTRANSFER,TRUE);
		$price = curl_exec($ch);
		curl_close($ch);
		return is_numeric($price) ? $price : null;
    }
	

    /**
     * 代销货详情
     * zhy	find404@foxmail.com
     * 2017年5月17日 15:11:45
     */
    public function AjaxGetDisGoodsPrice(){
		$goods_id   			= I('goods_id');
		$price 					= $this->GetDisGoodsPrice('goods_id='.$goods_id);
    	$this->ajaxReturn($price);
	}
	
	
	
	
    /**
     * 分销查看和上下架接口
     * zhy	find404@foxmail.com
     * 2017年5月18日 14:49:43
     */
	public function GetDisGoodsFindInsertDelete($goods_id,$Do){
 
		$domain										=  C('ZMALL_URL');
		$ZmfxGoodsId								= '1';
		$ZmallBanfangGoods							= M('banfang_goods','zm_','ZMALL_DB');
		$ZmallBanfangImage							= M('banfang_goods_images','zm_','ZMALL_DB');
		$ZmallBanfangAssociateAttr					= M('banfang_goods_associate_attr','zm_','ZMALL_DB');		
		$ZmallBanfangAssociateLuozuan				= M('banfang_goods_associate_luozuan','zm_','ZMALL_DB');
		$ZmallBanfangAssociateDeputystone			= M('banfang_goods_associate_deputystone','zm_','ZMALL_DB');
		$ZmallBanfangCategory						= M('banfang_category','zm_','ZMALL_DB');
		$ZmallBanfangJewelry						= M('banfang_jewelry','zm_','ZMALL_DB');
		$ZmallBanfangDeputystone					= M('banfang_deputystone','zm_','ZMALL_DB');
		$ZmallBanfangBaseProcessItems				= M('banfang_base_process_items','zm_','ZMALL_DB');
		$ZmallBanfangAttr							= M('banfang_attr','zm_','ZMALL_DB');
		$ZmallBanfangLuozuanPremiums				= M('banfang_luozuan_premiums','zm_','ZMALL_DB');		
 
		
		
		$ZmfxGoodsCategory							= M('goods_category');
		$ZmfxGoodsAttributesValue					= M('goods_attributes_value');
		$ZmfxGoodsAttributes						= M('goods_associate_attributes');
		$ZmfxGoodsAttributesInfo					= M('goods_associate_info');	
		$ZmfxGoodsAttributesLuozuan					= M('goods_associate_luozuan');
		$ZmfxGoodsAttributesDeputystone				= M('goods_associate_deputystone');
		$ZmfxGoodsImages							= M('goods_images');
		$ZmfxGoodsMaterial							= M('goods_material');
		$ZmfxGoods									= M('goods');
		$ZmfxGoodsShop								= M('goods_shop');		
		
		if(strpos($goods_id, ',') !== false) {																		//生产工艺
			$goods_id=explode(',',$goods_id);
		}else{
			$goods_id=array('0'=>$goods_id);
		}		
		
		//官网和分销 分类款式映射表
		$goods_category_banfang_mp = M('goods_category_banfang_mp')->select();
		foreach ($goods_category_banfang_mp as $k => $v) {
			$goods_category_banfang_mp_new[$v['banfang_category_id'].','.$v['banfang_jewely_id']] = $v['category_id'];
		}

		foreach($goods_id as $key_goodsid=>$val_goodsid){	
			$AttributesValue			= array();
			$associate_luozuan_info		= array();
			$associate_luozuan_othen	= array();
			$associate_deputystone		= array();
			$category_id 				= '';
			
			
			$where 										= 'goods_id='.$val_goodsid;
			$goods_info = $ZmallBanfangGoods
							-> alias('zm_banfang_goods')
							-> field('zm_banfang_goods.*,zm_banfang_category.is_luozuan')
							-> where($where)
							-> join('left join zm_banfang_category on zm_banfang_goods.category_id = zm_banfang_category.category_id')
							-> find();

				$key_cj = $goods_info['category_id'].','.$goods_info['jewelry_id'];
				
				if($goods_info['category_id'] && $goods_info['jewelry_id']){
					// switch($goods_info['category_id']){
					// 	case '1':
					// 		$category_id 			= '58';
					// 		break;
					// 	case '2':
					// 		$category_id 			= '59';
					// 		break;
					// 	case '3':
					// 		$category_id 			= '113';
					// 		break;
					// 	case '4':
					// 		break;
					// 	case '5':
					// 		$category_id 			= '113';
					// 		break;
					// 	case '6':
					// 		$category_id 			= '93';
					// 		break;
					// 	case '7':
					// 		$category_id 			= '54';
					// 		break;
					// }
					
		 
					list($k_work_price,$k_loss_price,$p_work_price,$p_loss_price)	= $ZmallBanfangBaseProcessItems->alias('item')->join(' LEFT JOIN zm_banfang_base_process_fee as fee on fee.base_items_id = item.base_items_id ')->where(' jewelry_id = '.$goods_info['jewelry_id'])->field("au750_fee_price_general as '0',au750_loss_general as '1' ,pt950_fee_price_general  as '2',pt950_loss_general  as '3'")->find();

					$category_name    				= $ZmallBanfangCategory -> where("category_id = $goods_info[category_id] ") -> getField('category_name');
					$jewelry_name       			= $ZmallBanfangJewelry  -> where("jewelry_id  = $goods_info[jewelry_id] ")  -> getField('jewelry_name');

					$goods_info['goods_name'] 		= $category_name.'('.$jewelry_name.')';		
					$goods_info['category_name'] 	= $category_name; 

					// if($goods_info['jewelry_id']=='3'	|| $goods_info['jewelry_id']=='2'){
					// 	$goods_info['category_id'] =	'62';
					// }else{
					// 	$like_category = $ZmfxGoodsCategory->where(' pid = '.$category_id)->select();
					// 	if($like_category){
					// 		array_walk($like_category, function ($ClosureData) use ($jewelry_name, &$like_category_id){
					// 						similar_text($ClosureData['category_name'], $jewelry_name,$like_num);
					// 							if($like_num>1){
					// 								$like_category_id[$ClosureData['category_id']]=$like_num;
					// 							}
					// 						}
					// 					);
					// 		if(count($like_category_id)>0){
					// 			$goods_info['category_id'] =array_search(max($like_category_id), $like_category_id);								//类别
					// 		}else{
					// 			$goods_info['category_id'] = $category_id;
					// 		}
					// 	}
					// }

					//赋值映射分类款式category_id
					if($goods_category_banfang_mp_new[$key_cj]){
						$goods_info['category_id'] = $goods_category_banfang_mp_new[$key_cj];
						$category_id = $goods_info['category_id'];
					}

					$goods_info['product_status']			= 0;
					$goods_info['banfang_goods_id']			= $goods_info['goods_id'];

					unset($goods_info['goods_id']);		
					unset($goods_info['goods_sn']);

					$price									= $goods_info['goods_price'];
					
					if($Do!='find'){
						$price 								= $this->GetDisGoodsPrice($where);
						is_numeric($price) && $price>'0' ? $goods_info['goods_price']=$price : $price = $goods_info['goods_price'];
					}
						
					if($goods_info['content']){
						if(strpos($goods_info['content'], $domain.'/Public/Uploads/') !== false) {																		//生产工艺
							$goods_info['content'] 				= str_replace($domain.'/Public/Uploads/',$domain.'/Public/Uploads/', $goods_info['content']);
						}else{
							$goods_info['content'] 				= str_replace('/Public/Uploads/',$domain.'/Public/Uploads/', $goods_info['content']);
						}
					}
						
					
				}

				if($Do!='find'){
					$DeleteGoodsId	 								= $ZmfxGoods->where(' banfang_goods_id = '.$goods_info['banfang_goods_id'].' and agent_id = '.C('agent_id'))->getField('goods_id');
					if($DeleteGoodsId){
						$DWhere										= ' goods_id = '.$DeleteGoodsId;
						if($Do=='Insert' || $Do=='Update'){
							//上架
							$ZmfxGoods 								->where(' banfang_goods_id = '.$goods_info['banfang_goods_id'].' and agent_id = '.C('agent_id')) -> save($goods_info);
							$ZmfxGoodsId			    			= $DeleteGoodsId;						
						
						}else if($Do=='Delete'){												//下架
							$ZmfxGoods								->where($DWhere)->delete();		
							$ZmfxGoodsShop							->where($DWhere)->delete();	
						}
						$ZmfxGoodsAttributes						->where($DWhere)->delete();
						$ZmfxGoodsAttributesInfo					->where($DWhere)->delete();
						$ZmfxGoodsAttributesLuozuan					->where($DWhere)->delete(); 
						$ZmfxGoodsAttributesDeputystone				->where($DWhere)->delete();
						$ZmfxGoodsImages							->where($DWhere)->delete();
					
						if($Do=='Delete'){											 
								 continue;
						}
					}else if ($Do=='Insert'){

						$goods_info['goods_sn']       				= date('Ymdhis',time()).rand(10000, 99999);
						$goods_info['agent_id']						= C('agent_id');
						$goods_info_thumb = $ZmallBanfangImage-> where($where) -> getField('small_path');				
						$goods_info['thumb']						= $goods_info_thumb?$goods_info_thumb:'';
						$goods_info['goods_type']					= '4';
						$ZmfxGoodsId			    				= $ZmfxGoods->add($goods_info);
						$bool = $ZmfxGoodsShop->add(array('goods_id'=>$ZmfxGoodsId,'sell_status'=>'1','shop_agent_id'=>C('agent_id')));		//默认上架					
					}else{											 
						continue;
					}
				}
				
				
 
 
			
			//图片
			$goods_info['imgsList']    = $ZmallBanfangImage ->field('small_path,big_path,UNIX_TIMESTAMP(create_time) as create_time,'.$ZmfxGoodsId.' as goods_id,'.C('agent_id').' as agent_id')-> where($where) -> select();
			
			//主石
			$associate_luozuan = $ZmallBanfangAssociateLuozuan 
										-> where($where) 
										-> join('left join zm_goods_luozuan_shape on zm_banfang_goods_associate_luozuan.luozuan_shape_id = zm_goods_luozuan_shape.shape_id')
										-> order('zm_banfang_goods_associate_luozuan.associate_luozuan_id desc')
										-> select();

			if($associate_luozuan){
				//属性
				$goods_info['attrs']      = $ZmallBanfangAssociateAttr -> where($where) -> select();
				if($goods_info['attrs']){
					$BanFangAttrId	= implode(',',array_column($goods_info['attrs'], 'attr_id'));
					$attr_name      = $ZmallBanfangAttr -> where('attr_id in ('.$BanFangAttrId.')') ->getField('attr_name',true);
					$attr_names 	= implode('\',\'',$attr_name);			
 
					
					if(strpos($attr_names, 'CNC工艺') !== false) {																		//生产工艺
						$attr_names 	=	str_replace("CNC工艺","CNC",$attr_names);
					}
					if(strpos($attr_names, '分色') !== false) {																			//生产工艺
						$attr_names 	=	str_replace("分色","真分色",$attr_names);
						$material_match_28 = '35';
					}else{
						$material_match_28 = '';
					}

					
					if(strpos($attr_names, '分件') !== false) {
						$material_match_8 = '35';
					}else{
						$material_match_8 = '';
					}
					
					if(strpos($attr_names, '六围一') !== false) {																		 
						$material_match_19 = '30';
					}else{
						$material_match_19 = '';
					}

					if(strpos($attr_names, '八围一') !== false) {
						$material_match_21 = '45';
					}else{
						$material_match_21 = '';
					}
		 
 
 
					$Zmfxattrs1 		= $ZmfxGoodsAttributesValue->where(' attr_value_name in ( \''.$attr_names.'\')')->select();		//属性
					$Zmfxattrs2 		= $ZmfxGoodsAttributesValue->where(" attr_id = '8'")->select();									//价格
					$Zmfxattrs3 		= $ZmfxGoodsAttributesValue->where(" attr_id = '7'")->select();									//CT			
					$Zmfxattrs 			= array_merge($Zmfxattrs1, $Zmfxattrs2);

					$associate_luozuan_luozuan_weight 		= array_column($associate_luozuan, 'luozuan_weight');
					$associate_luozuan_size_mm 				= array_column($associate_luozuan, 'luozuan_size');				
					$associate_luozuan_luozuan_weight_unique= array_unique($associate_luozuan_luozuan_weight);

					foreach ($Zmfxattrs3 as $key1=>$val1){
						foreach ($associate_luozuan_luozuan_weight_unique as $key=>$val){
							if(strpos($val1['attr_value_name'], '-') !== false) {														//钻重
								$min 		= (float)strstr($val1['attr_value_name'], '-',true);
								$max 		= (float)str_replace("-",'',str_replace("CT",'',strstr($val1['attr_value_name'], '-')));
								if($min<=(float)$val && (float)$val<=$max){
									$AttributesValue[$key1+200]['attr_id']		= $val1['attr_id'];
									$AttributesValue[$key1+200]['attr_code']	= $val1['attr_code'];										
								}
							}else if(($val1['attr_value_name']=='0.2CT以下') && (float)$val<0.2){
									$AttributesValue[$key1+200]['attr_id']		= '7';
									$AttributesValue[$key1+200]['attr_code']	= '1';
							}else if(($val1['attr_value_name']=='4CT以上') && (float)$val>4){
									$AttributesValue[$key1+200]['attr_id']		= '7';
									$AttributesValue[$key1+200]['attr_code']	= '256';							
							}
							
							if(isset($AttributesValue[$key1+200]['attr_code'])){
									$AttributesValue[$key1+200]['category_id']	= $category_id;
									$AttributesValue[$key1+200]['goods_id']		= $ZmfxGoodsId;	
									$AttributesValue[$key1+200]['agent_id']		= C('agent_id');	
							}
						}
					}
					
	 
		 
					foreach ($Zmfxattrs as $key=>$val){
						if(strpos($val['attr_value_name'], '-') !== false) {											//空拖价格
							$min = (float)strstr($val['attr_value_name'], '-',true);
							$max = (float)str_replace("-",'',strstr($val['attr_value_name'], '-'));									
							if($min<=(float)$price && $max>=(float)$price){
								$AttributesValue[888]['attr_id']		= $val['attr_id'];
								$AttributesValue[888]['attr_code']		= $val['attr_code'];										
							}
						}else if(($val['attr_value_name']=='400以下') && (float)$price<400){
								$AttributesValue[888]['attr_id']		= '8';
								$AttributesValue[888]['attr_code']		= '1';
						}else if(($val['attr_value_name']=='2000以上')  && (float)$price>2000){
								$AttributesValue[888]['attr_id']		= '8';
								$AttributesValue[888]['attr_code']		= '256';							
						}else if(preg_match('/[\x80-\xff]./',$val['attr_value_name']) && ($val['attr_value_name']!='400以下')  && ($val['attr_value_name']!='2000以上')){
								$AttributesValue[$key]['attr_id']		= $val['attr_id'];
								$AttributesValue[$key]['attr_code']		= $val['attr_code'];
								$AttributesValue[$key]['category_id']	= $category_id;
								$AttributesValue[$key]['goods_id']		= $ZmfxGoodsId;
								$AttributesValue[$key]['agent_id']		= C('agent_id');								
						}else if($val['attr_value_name']=='CNC') {
								$AttributesValue[$key]['attr_id']		= $val['attr_id'];
								$AttributesValue[$key]['attr_code']	    = $val['attr_code'];
								$AttributesValue[$key]['category_id']	= $category_id;
								$AttributesValue[$key]['goods_id']		= $ZmfxGoodsId;
								$AttributesValue[$key]['agent_id']		= C('agent_id');								
						}
						
							
						
						if(isset($AttributesValue[888]['attr_code'])){
								$AttributesValue[888]['category_id']	= $category_id;
								$AttributesValue[888]['goods_id']		= $ZmfxGoodsId;
								$AttributesValue[888]['agent_id']		= C('agent_id');
						}
					}	
					$goods_info['Attribu'] 	= $AttributesValue;
					unset($goods_info['attrs']);
				}				
 
				$AssociateMaterialID		  			=  "'1','2','3'";							//默认产品材质
				$goods_info['material_match_type']=='1' && $AssociateMaterialID.=",'4','5'";		//是否选择pt950
				!empty($material_match_28) 				&& $AssociateMaterialID .=",'28'";			//是否分色
 

				$AssociateMaterialName 					= $ZmfxGoodsMaterial->where(' material_id 	 in ('.$AssociateMaterialID.')')->getField(" group_concat(material_name SEPARATOR \"','\") as '0'");
				$AssociateMaterialName 					= array_keys($AssociateMaterialName);
				$AssociateMaterialID		 			= $ZmfxGoodsMaterial->field('material_id')->where(' material_name in (\''.$AssociateMaterialName[0].'\') and agent_id = '.C('agent_id'))->getField('material_id',true);			
 
				$Arrayluozuan_number					= array_sum(array_column($associate_luozuan, 'luozuan_number'));		//获取主石颗
				$ArrayDeputystoneNumber					= count(array_column($associate_luozuan, 'associate_luozuan_id'));		//获取副石总颗数				
				
				foreach($associate_luozuan as $k=>$r){
 

					//主石
					//$associate_luozuan_othen[$k]['weights_name']   			= $category_id =='54' 		   ?  trim($r['luozuan_weight']) : trim($r['luozuan_size']);
					$associate_luozuan_othen[$k]['weights_name']   			= $r['luozuan_shape_id'] ==1 		   ?  trim($r['luozuan_weight']) : trim($r['luozuan_size']);
					$associate_luozuan_othen[$k]['shape_id'] 				= $r['luozuan_shape_id']=='12' ? 0 							 : str_pad($r['luozuan_shape_id'],3,'0',STR_PAD_LEFT);
					$associate_luozuan_othen[$k]['goods_id']				= $ZmfxGoodsId;
					$associate_luozuan_othen[$k]['price']					= '0';
					
					if($category_id =='54') {
						$banfang_luozuan_premiums = $ZmallBanfangLuozuanPremiums->where(' user_type = 0')->select();
						foreach($banfang_luozuan_premiums as $key_premiums=>$val_premiums){
							
							if($val_premiums['min_weight']<=$associate_luozuan_othen[$k]['weights_name'] 	  && $val_premiums['max_weight']>=$associate_luozuan_othen[$k]['weights_name']){
								$associate_luozuan_othen[$k]['price']	= $val_premiums['fee_price'];					
							}
						}
					}
		 
					if($associate_luozuan_othen[$k]['price']!='0'  && $r['luozuan_number']){
						$associate_luozuan_othen[$k]['price']	=$associate_luozuan_othen[$k]['price']*$r['luozuan_number'];
					}					
					
					
					$data = $ZmallBanfangAssociateDeputystone -> where(" associate_luozuan_id = '$r[associate_luozuan_id]' ") -> select();
 
					//副石
					foreach($data as $_r){
						$associate_deputystone1['deputystone_name'] 	 	= 'SAGH'.$_r['deputystone_number'].'颗'.doubleval($_r['deputystone_weight']);
						$associate_deputystone100['deputystone_name']  		= 'SBGH'.$_r['deputystone_number'].'颗'.doubleval($_r['deputystone_weight']);
						$associate_deputystone1['deputystone_price']		= '0';
						$associate_deputystone100['deputystone_price'] 		= '0';

						$banfang_deputystone = $ZmallBanfangDeputystone->where(' user_type = 0')->select();

						foreach($banfang_deputystone as $key_deputystone=>$val_deputystone){
							if($val_deputystone['min_weight']<= (string)$_r['deputystone_weight'] && $val_deputystone['max_weight']>= (string)$_r['deputystone_weight'] ){
								$associate_deputystone1['deputystone_price']			= $_r['deputystone_weight'] *$val_deputystone['sagh_price']*$_r['deputystone_number'];
								$associate_deputystone100['deputystone_price']			= $_r['deputystone_weight'] *$val_deputystone['sbgh_price']*$_r['deputystone_number'];
							}
						}
						
						$associate_deputystone1['deputystone_num']				= $_r['deputystone_number'];
						$associate_deputystone100['deputystone_num']			= $_r['deputystone_number'];
						$associate_deputystone1['goods_id']						= $ZmfxGoodsId;
						$associate_deputystone100['goods_id']					= $ZmfxGoodsId;
						$associate_deputystone_num 								+=$_r['deputystone_number'];
						
						array_push($associate_deputystone,$associate_deputystone1,$associate_deputystone100);
					}
				
 
 		
 
					$idi					=  count($associate_luozuan_othen);
					$AssociateMaterialIDs	=  count($AssociateMaterialID);
 
					if(!empty($material_match_28)){
						$k==0 && $loss_percent	=1;
						$k_loss_price		= $k_loss_price+$loss_percent;
						$p_loss_price		= $p_loss_price+$loss_percent;
						$loss_percent	=1 && $loss_percent	='';
					}
					//主石INFO
					foreach($AssociateMaterialID as $key_luozuan_info=>$val_luozuan_info){
						if($key_luozuan_info=='3' && $AssociateMaterialIDs>=5 || $key_luozuan_info=='4' && $AssociateMaterialIDs>=5){
							$associate_luozuan_info[$key_luozuan_info]['basic_cost']  	= isset($p_work_price) ? $p_work_price : 0;	
							$associate_luozuan_info[$key_luozuan_info]['loss_name']  	= isset($p_loss_price) ? $p_loss_price : 0;
							$associate_luozuan_info[$key_luozuan_info]['weights_name']  = floor($associate_luozuan[0]['material_weight']*'1.31'*100)/100;
						
						}else if($k_work_price && $k_loss_price){
							$associate_luozuan_info[$key_luozuan_info]['basic_cost']  	= $k_work_price;					
							$associate_luozuan_info[$key_luozuan_info]['loss_name']  	= $k_loss_price;
							$associate_luozuan_info[$key_luozuan_info]['weights_name'] 	= isset ($associate_luozuan[0]['material_weight']) ? $associate_luozuan[0]['material_weight'] : 0;					
						}else{
							$associate_luozuan_info[$key_luozuan_info]['basic_cost']  	= 0;					
							$associate_luozuan_info[$key_luozuan_info]['loss_name']  	= 0;
							$associate_luozuan_info[$key_luozuan_info]['weights_name'] 	= isset ($associate_luozuan[0]['material_weight']) ? $associate_luozuan[0]['material_weight'] : 0;	
						}

						if($associate_luozuan_info[$key_luozuan_info]['basic_cost']>0 && $associate_luozuan_info[$key_luozuan_info]['loss_name']>0){
							$associate_luozuan_info[$key_luozuan_info]['additional_price'] 		= $associate_deputystone_num *'5' + $material_match_28 + $material_match_8 + $material_match_19 + $material_match_21;
						}
							$associate_luozuan_info[$key_luozuan_info]['goods_id'] 				= $ZmfxGoodsId;
							$associate_luozuan_info[$key_luozuan_info]['material_id']  			= $val_luozuan_info;
					}
				}
				
 

				
				if(count($AssociateMaterialID)==3){
					$associate_luozuan_othen=array_merge($associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen); 
				}else if(count($AssociateMaterialID)==4){
					$associate_luozuan_othen=array_merge($associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen); 
				}else if(count($AssociateMaterialID)==5){
					$associate_luozuan_othen=array_merge($associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen); 
				}else if(count($AssociateMaterialID)==6){
					$associate_luozuan_othen=array_merge($associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen,$associate_luozuan_othen); 
				} 
				
				array_walk($associate_luozuan_othen, function (&$via,$key_luozuan) use ($AssociateMaterialID,$idi,$ZmfxGoodsId){
					
					$key_luozuan_start_num	= isset($idi) ? $idi-1 :0; 
 
					if($key_luozuan<=$key_luozuan_start_num){
							$via['material_id']							= 	$AssociateMaterialID[0];
					}else if ($key_luozuan>($key_luozuan_start_num) && $key_luozuan<=($key_luozuan_start_num+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[1];				
					}else if($key_luozuan>($key_luozuan_start_num+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[2];		
					}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[3];		
					}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[4];		
					}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[5];		
					}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[6];				
					}else if($key_luozuan>($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi) && $key_luozuan<=($key_luozuan_start_num+$idi+$idi+$idi+$idi+$idi+$idi+$idi)){
							$via['material_id']							= 	$AssociateMaterialID[7];			
					}
							$via['shape_name'] 							=   M('goods_luozuan_shape')->where(' shape_id 	 = '.$via['shape_id'])->getField("shape_name");
							$via['goods_id']							= 	$ZmfxGoodsId ;	
				});
				
				$goods_info['Luozuan'] 		= $associate_luozuan_othen;
				$goods_info['Deputystone'] 	= $associate_deputystone;
				$goods_info['LuozuanInfo'] 	= $associate_luozuan_info;		
				

				if($Do!='find'){
					$ZGoodsImagesStatus 						=   $ZmfxGoodsImages				->addAll($goods_info['imgsList']);
					$ZGoodsAttributesStatus 					=	$ZmfxGoodsAttributes			->addAll($AttributesValue);
					$ZGoodsAttributesInfoStatus 				=	$ZmfxGoodsAttributesInfo		->addAll($associate_luozuan_info);
					$ZGoodsAttributesLuozuanStatus 				=	$ZmfxGoodsAttributesLuozuan		->addAll($associate_luozuan_othen);
					$ZGoodsAttributesDeputystoneStatus 			=	$ZmfxGoodsAttributesDeputystone	->addAll($associate_deputystone);
					unset($goods_info['imgsList']);
					unset($AttributesValue);
					unset($associate_luozuan_info);
					unset($associate_luozuan_othen);
					unset($associate_deputystone);
					unset($associate_deputystone_num);
				}
			}
		}
		if($Do=='find'){											 
			return $goods_info;
		}
 
	}
	
 
    /**
     * 官网代销货转本分销商接口
     * zhy	find404@foxmail.com
     * 2017年5月8日 14:24:53
     */
	public function disGoodsChoice(){
		$id   					= I('id');
		$product_status   		= I('product_status');
		!$id && exit();
		if($product_status=='0')		$this->GetDisGoodsFindInsertDelete($id,'Insert');	
		if($product_status=='1')		$this->GetDisGoodsFindInsertDelete($id,'Delete');
		if($product_status=='2')		$this->GetDisGoodsFindInsertDelete($id,'Update');
			
		echo json_encode('1');
	}
	
	
	/**
	 * 产品列表
	 * @param int $p
	 * @param int $n
	 * @param string $Productno
	 * @param string $Productname
	 */
    public function productList($p=1,$n=13,$Productno='',$Productname=''){

		
		$Productno   	  = I('get.Productno','');
		$Productname	  = I('get.Productname','');
		//板房款式编号（搜索条件）
		$banfangGoodsSn   = I('get.banfangGoodsSn','');
		$Productno   	  = str_replace('+',' ',$Productno);
		$Productname 	  = str_replace('+',' ',$Productname);
		$banfangGoodsSn   = str_replace('+',' ',$banfangGoodsSn);
    	$Productno   	  = addslashes($Productno);
    	$Productname 	  = addslashes($Productname);
    	$banfangGoodsSn   = addslashes($banfangGoodsSn);
		$category_id	   = I('get.category','','intval');
                $category2_id	   = I('get.category2','','intval');//二级
		$home_show         = I('get.home_show','','intval');
                $goods_type        = I('get.goods_type','','intval');
                $this->goods_type = $goods_type;
		$sell_status	   = I('get.sell_status','-1');
		$this->category_id = $category_id;
                $this->category2_id = $category2_id;
		$this->Productno   = $Productno;
		$this->Productname = $Productname;
		$this->banfangGoodsSn = $banfangGoodsSn;
        $G                 = D('Common/Goods');
		$GA				   = D('Common/GoodsActivity');
		$GS				   = D('Common/GoodsHomeShow');

		//查询出活动商品的ID,以便用来获取产品列表的时候去掉这些ID， fangkai 2016/8/22
		$where['agent_id'] = C('agent_id');
		$activityGoods = $GA->GoodsActivityList($where,'',1,'');
		if($activityGoods){
			$activityGoods_id = array_column($activityGoods,'gid');
			$id_array		  = implode(',',$activityGoods_id);
			$G		          -> set_where('zm_goods.goods_id'  ,  'not in ('.$id_array.')'  , true);

		}
		//组装产品首页展示条件
		$where_home_show = '';
		if($home_show){
			$home_show_list 	    = $GS->GoodsHomeShowList($where,'id DESC','','');
			switch($home_show){
				case 1:
					if($home_show_list){
						$home_show_id       = array_column($home_show_list,'gid');
						if($activityGoods_id){
							$home_show_id 	= array_merge($activityGoods_id,$home_show_id);
						}
						$home_show_id_array	= implode(',',$home_show_id);
						$G		            -> set_where('zm_goods.goods_id'  ,  ' not in ('.$home_show_id_array.')'  , true);
					}
					break;
				case 2:
					if($home_show_list){
						$home_show_id       = array_column($home_show_list,'gid');
						$home_show_id_array	= implode(',',$home_show_id);
						$G		            -> set_where('zm_goods.goods_id'  ,  'in ('.$home_show_id_array.')'  , true);
					}
					break;
				default:
					$where_home_show	    = ' and gs.home_show <> 2 ';
					break;
			}
			$this->home_show	= $home_show;
		}

		$G -> my_table_name  = 'goods';//重要
		$G -> _join( ' left join zm_goods_shop as zgs on zgs.goods_id = zm_goods.goods_id and zgs.shop_agent_id = '.C('agent_id').$where_home_show);

		//组装产品编号条件
		if($Productno){
            $G -> set_where( 'zm_goods.goods_sn'  , "like '%$Productno%'" , true);
		}
		//组装产品名称条件
		if($Productname){
            $G -> set_where( 'zm_goods.goods_name'  , "like '%$Productname%'" , true);
		}

		//组装版房款式编号条件   zengmingming   2017-08-14
		if($banfangGoodsSn){
			$banfangGoodsSnArr = M('banfang_goods','zm_','ZMALL_DB')->where("goods_sn like '%$banfangGoodsSn%'")->field('goods_id')->select();
			if(!empty($banfangGoodsSnArr)){
				foreach($banfangGoodsSnArr as $val){
					$banfangSnArr[] = $val["goods_id"];
				}
				$banfangSnStr = implode(",",$banfangSnArr);	
				$G -> set_where( 'zm_goods.banfang_goods_id'," in ($banfangSnStr)" , true);
			}else{
				//搜索内容不存在,把banfang_goods_id置为99999999999999999
				$G -> set_where( 'zm_goods.banfang_goods_id'," in (99999999999999999)" , true);		
			}
		}

		//组装产品名称条件
		if($sell_status != '-1'){
			if($sell_status != '2'){
            	$G -> set_where( 'zgs.sell_status'  , " in ('$sell_status')" , true);
			} else {
				$G -> set_where( 'zgs.sell_status'    , " IS NULL " , true);
				$G -> set_where( 'zgs.shop_agent_id'  , " IS NULL " , true);
			}
		}
		//组装产品类别
		if(I('get.goods_type')){
                $G -> set_where( 'zm_goods.goods_type'  , I('get.goods_type'));}
		//查询出所有的二级分类
                if($category_id){
                    $cate_children = M('goods_category') -> field('category_id,category_name')->where('pid ='.$category_id)->select(); 
                    $this->cate_children = $cate_children;
                }
               
                //二级查询
                if($category2_id){
                    $category_id = $category2_id;
                    $G -> set_where( 'zm_goods.category_id'  , $category2_id );
                }
		//组装分类查询
		if($category_id){
			//查询出二级分类
			$category_array        = M('goods_category') -> field('category_id,category_name')->where(array('pid'=>$category_id))->select();
                        if($category_array){
				$category_id_array = array_column($category_array, 'category_id');
				$category_id_array = implode(",", $category_id_array);
			}else{
				$category_id_array = $category_id;
			}
            $G                     -> set_where( 'zm_goods.category_id'  , $category_id_array);
		}

		$field		    = 'zm_goods.*, gs.home_show,zgs.shop_agent_id,zgs.sell_status';
		$stopgap_field  = 'zm_goods.goods_id';
		$G			   -> _join( ' left join zm_goods_home_show as gs on gs.gid = zm_goods.goods_id and gs.agent_id = '.C('agent_id').$where_home_show);

		// 如果系统设置关闭同步后台产品
		if(!C('is_sync_goods')){
            $G->sql['where']['zm_goods.banfang_goods_id'] = 0;
        }
		//查询产品$this->sql['where']
        $count          = $G -> get_count();
		$page           = new Page($count,$n);
        $this  -> page  = $page -> show();
        $G             -> _limit($p,$n);
        $G             -> _order('goods_id','DESC');
        $dataList       = $G -> getList(false,false,$field,false,true);
		array_walk($dataList, function (&$via){
			if($via['banfang_goods_id']>'0'){
				$via['banfang_goods_id'] = M('banfang_goods','zm_','ZMALL_DB')->where(' goods_id = '.$via['banfang_goods_id'])->getField('goods_sn');
			}
		});
		
		$orderList_ids  = array();
		if($dataList){
			session('productList_sql_where_home_show',$where_home_show);			
			session('productList_sql_where',$G ->sql['where']);
			//$this -> productList_null=base64_encode(serialize($G ->sql['where']));
			$this -> dataList = $G -> getProductListAfterAddPoint($dataList,0);
		}else{
			$this -> dataList = $dataList;
                        
		}
		//当前使用的模板
		$TC       = M('template_config');
      	$is_template_id = $TC->where(array('agent_id'=>C('agent_id'),'template_status'=>'1','template_id'=>8))->getField('template_id');
		if($is_template_id){
			$detail_action = 'goodsDetails';
		}else{
			$detail_action = 'goodsInfo';
		}
		$this->detail_action	= $detail_action;
		//查询出分类大类
		$category         = M('goods_category')->where('pid=0')->select();
		$this -> category = $category;
		$this -> sell_status = $sell_status;
		$this -> agent_id = C('agent_id');
        $this -> display();
    }
	
	
	
	
    /**
     * 租赁设置功能
     * zhy	find404@foxmail.com
     * 2017年10月14日 15:37:57
     */
    public function LeaseSetting() {
		$EI   	= new \Think\LogicalPacket();
		$id    	= intval($_GET['id']);
		$where 	= ' goods_id = '.$id;
		$goods  = M('goods');

		$EI->subject([$id,'传入ID有误。']);
		$exist  = $goods->where($where)->getField(1);
		$EI->subject([$exist,'不存在的商品ID']);
		
		if($_POST){
			$EI->subject([  [!empty($_POST['first_lease_price']),'首日价不能为空！'], 
							[!empty($_POST['lease_percentage']),'百分比不能为空！'],
							[is_numeric($_POST['first_lease_price']),'首日价得为整数！'],
							[is_numeric($_POST['lease_percentage']),'百分比得为整数！',],
						]);
			$result =	$goods->where($where)->save($_POST);
			$this->success('更新成功');	
		}else{
			$this -> publicdataInfo = $goods->field('first_lease_price,lease_percentage,lease_switch,goods_id')->where($where)->find();
			$this -> display();	
		}
	}
	
 
    /**
     * 产品图片一次上传与删除
     */
    public function productByOne() {
        $images_id = I('post.images_id');
        if (empty($images_id)) {
            echo json_encode($this->productImg());
        } else {
            if (M('goods_images')->where('images_id = '.$images_id.' and agent_id = '.C('agent_id'))->delete()) {
                echo json_encode(array("success"=>true, "msg"=>'成功'));
            } else {
                echo json_encode(array("success"=>false, "msg"=>'操作失败！'));
            }
        }
    }

    /**
     * 产品图片文件上传的处理
     * @param number $goods_id
     */
    private function productImg($goods_id=0) {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728 ;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath = './Uploads/product/'; // 设置附件上传目录    // 上传文件
        $info = $upload->uploadOne($_FILES['product_img']);
        if(!$info) {// 上传错误提示错误信息
            $result = $upload->getError();
        }else{// 上传成功
            // 生成缩略图
            $thumbRes = $this->productThumb($info);
            if ($thumbRes['success']) {
                $imgData['small_path']  = '.'.substr($thumbRes['data'], 8);
                $I                      = M('goods_images');
                // 保存图片数据
                $imgData['goods_id']    = $goods_id;
                $imgData['big_path']    = $info['savepath'].$info['savename'];
				$imgData['agent_id']    = C("agent_id");
                $imgData['create_time'] = time();
                $imgID = $I->add($imgData);
                if ($imgID) {
                    $result = array('success'=>true, 'msg'=>'成功', 'data'=>$I->find($imgID));
                } else {
                    $result = array('success'=>false, 'msg'=>'失败');
                }
            } else {
                $result = $thumbRes;
            }
        }
        return $result;
    }

    /**
     * 产品生成小图
     * @param string $imgFile
     * @return multitype:boolean string
     */
    private function productThumb($imgFile) {
        $big_path = $imgFile['savepath'].$imgFile['savename'];
        $big_path = './Public'.substr($big_path, 1);
        $image = new \Think\Image();
        $image->open($big_path);
        // 检查图片宽高是否在指定的等比中
        $ratio = number_format($image->width() / $image->height(), 1);
        if (($ratio >= 1) && ($ratio <= 1.3)) {
            $pi = pathinfo($big_path);
            $small_path = $pi['dirname'].'/'.$pi['filename'].'_2.'.$pi['extension'];
            //默认采用原图等比例缩放的方式生成缩略图
            $image->thumb(230, 230, 1)->save($small_path);
            $result = array('success'=>true, 'data'=>$small_path);
        } else {
            unlink($big_path); // 删除原文件
            $result = array('success'=>false, 'msg'=>'图片不是正方形！');
        }
        return $result;
    }

	/**
	 * 属性关联，主要做页面发过来的属性处理
	 * @param array $attribute
	 * @param int $category_id
	 * @param int $goods_id
	 */
	protected function attributeAssociate($attribute,$category_id,$goods_id){
		if(!$attribute) return true;//没有属性直接返回
		$GA  = M('goods_attributes');
		$GAV = M('goods_attributes_value');
		$GAA = M('goods_associate_attributes');
		//根据属性名称和属性值生成实际插入数组
		foreach ($attribute as $key => $value) {
		    $ids .= ','.$key;
		}
		$ids  = substr($ids, 1);
		$list = $GA->where('attr_id in ('.$ids.')')->select();
		$list = $this->_arrayIdToKey($list,'attr_id');
		$listSub = $GAV->where('attr_id in ('.$ids.')')->select();
		foreach ($listSub as $key => $value) {
		    $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
		}
		foreach ($attribute as $key => $value) {
		    $arr['category_id'] = $category_id;
		    $arr['goods_id'] = $goods_id;
			$arr['agent_id'] = C("agent_id");
		    $arr['attr_id'] = $key;
		    $arr['attr_value'] = '0';
		    $arr['attr_code'] = '0';
		    if($list[$key]['input_type'] == 1){
		        $arr['attr_code'] = $list[$key]['sub'][$value]['attr_code'];
		    }elseif($list[$key]['input_type'] == 2){
		        foreach ($value as $k => $v) {
		            $arr['attr_code'] += intval($list[$key]['sub'][$v]['attr_code']);
		        }
		    }elseif($list[$key]['input_type'] == 3){
		        foreach ($value as $k => $v) {
		            $attr_value .= ','.$v;
		        }
		        $attr_value = substr($attr_value, 1);
		        $arr['attr_value'] = $attr_value;
		        unset($attr_value);
		    }
            $attributeAssociate[] = $arr;
            unset($arr);
		}
		$GAA->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->delete();
		return $GAA->addAll($attributeAssociate);
	}

	/**
	 * 产品材质的裸钻主石，副石，金重/损耗关联
	 * @param int $goods_id
	 * @return double
	 */
	protected function materialAssociate($goods_id){
		$GAL = M('goods_associate_luozuan');//产品材质裸钻关联表
		$GAI = M('goods_associate_info');//产品材质损耗金重金价关联表
		$GAD = M('goods_associate_deputystone');//产品副石关联表
		//页面post数据
		$material    = I('post.material');
		//处理副石相关数据
		$deputystone = $material['deputystone'];
		foreach ($deputystone AS $key => $value) {
			$value['goods_id'] = $goods_id;
			$value['agent_id'] = C("agent_id");
			$deputystoneS[]    = $value;
		}

		$GAD->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->delete();
		$GAD->addAll($deputystoneS);

		//处理材质损耗金重
		$info = $material['info'];
		if(empty($info)){
			$this->error('必须选填金工石数据!!');
		}
		foreach ($info as $key => $v) {
            $value = [
                'weights_name' => $v['weights_name'],
                'loss_name' => $v['loss_name'],
                'basic_cost' => $v['basic_cost'],
                'additional_price' => !isset($v['additional_price']) ? '0.00' : $v['additional_price'],
                'goods_id' => $goods_id,
                'agent_id' => C("agent_id"),
                'material_id' => $key
            ];

			$infoS[] = $value;
			//必须填写金重，并为数字
			if(!is_numeric($value['weights_name'])){
				$this->error('必须填写金重，并为数字!');
			}
			//必须填写损耗，并为数字
			if(!is_numeric($value['loss_name'])){
				$this->error('必须填写损耗，并为数字!');
			}
			//必须填写损耗，并为数字
			if(!is_numeric($value['basic_cost'])){
				$this->error('必须填写基本工费，并为数字!');
			}
		}
		$GAI->where('goods_id = '.$goods_id)->delete();

		$GAI->addAll($infoS);
		//处理材质裸钻
		$luozuan   = $material['luozuan'];
		if(empty($luozuan)){
			$this -> error('必须填写材质的匹配主石数据!');
		}
		foreach ($luozuan as $key => $value) {
			foreach ($value as $k => $v) {
				$v['goods_id']    = $goods_id;
				$v['agent_id']    = C("agent_id");
				$v['material_id'] = $key;
				$luozuanS[]       = $v;
				//形状必须填写
				if(!$v['shape_id']){
					$this->error('匹配主石必须全部选择形状!');
				}
				//分数必须填写
				if(!$v['weights_name'] or !is_numeric($v['weights_name'])){
					$this->error('主石分数必须填写数字，不能有单位!');
				}
				//价格必须填写
				if(!$v['price'] or !is_numeric($v['price']) or $v['price']<0 ){
					if($v['price']!=0){
						$this->error('价格必须填写正整数，不能有单位!');
					}
				}
			}
		}
		$GAL->where('goods_id = '.$goods_id)->delete();
		$GAL->addAll($luozuanS);
		//获取材质金价
		$GM = M('goods_material');
		$gp = $GM->where('material_id = '.$infoS[0]['material_id'])->getField('gold_price');
		if(!$gp){
		    $this->error('你的材质没有设置金价，请设置金价!');
		}
		$wn = $infoS[0]['weights_name'];//默认第一个金重
		$ln = $infoS[0]['loss_name'];//默认第一个损耗
		$bc = $infoS[0]['basic_cost'];//默认第一个为基础工费
		$lp = $luozuanS[0]['price'];//默认第一个主石工费为镶嵌工费
		$dp = $deputystoneS[0]['deputystone_price'];//默认第一个副石工费
		$price = $this->recalculatingPriceDo($wn,$ln,$gp,$bc,$lp,$dp,0);
		return $price;
	}

	/**
	 * 产品材质的裸钻主石，副石，金重/损耗关联,拓展自定义价格
	 * @param int $goods_id
	 * @return double
	 */
	protected function materialAssociateExt($goods_id){
		$GAL = M('goods_associate_luozuan');//产品材质裸钻关联表
		$GAI = M('goods_associate_info');//产品材质损耗金重金价关联表
		$GAD = M('goods_associate_deputystone');//产品副石关联表
		$GAS = M('goods_associate_size');
		//页面post数据
		$material    = I('post.material');
		//处理副石相关数据
		$deputystone = $material['deputystone'];
		foreach ($deputystone AS $key => $value) {
			if(!is_numeric($value['deputystone_num'])){
				$this->error('必须为数字!');
			}
			$value['goods_id'] = $goods_id;
			$value['agent_id'] = C("agent_id");
			$deputystoneS[]    = $value;
		}
		$GAD->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->delete();
		$GAD->addAll($deputystoneS);
		//处理材质损耗金重
		$info = $material['info'];
		if(empty($info)){
			$this->error('必须选填金工石数据!!');
		}
		
		foreach ($info as $key => $value) {
			$value['goods_id'] = $goods_id;
			$value['agent_id'] = C("agent_id");
			$value['material_id'] = $key;
			//必须填写金重，并为数字
			if(!is_numeric($value['weights_name'])){
				//$this->error('必须填写金重，并为数字!');
			}
			//必须填写损耗，并为数字
			if(!is_numeric($value['loss_name'])){
				$value['loss_name']  = '0';
			}
			//必须填写损耗，并为数字
			if(!is_numeric($value['basic_cost'])){
				$value['basic_cost'] = '0';
			}
			$infoS[] = $value;
		}
		$GAI->where('goods_id = '.$goods_id)->delete();
		$GAI->addAll($infoS);
		//处理材质裸钻
		$luozuan   = $material['luozuan'];
		if(empty($luozuan)){
			$this -> error('必须填写材质的匹配主石数据!');
		}
		foreach ($luozuan as $key  => $value) {
			foreach ($value as $k  => $v) {
				$v['goods_id']     = $goods_id;
				$v['agent_id']     = C("agent_id");
				$v['material_id']  = $key;

				if(!$v['shape_id']){
					$this->error('匹配主石必须全部选择形状!');
				}
				//分数必须填写
				if(!$v['weights_name'] or !is_numeric($v['weights_name'])){
					//$this->error('主石分数必须填写数字，不能有单位!');
				}
				//价格必须填写
				if(!$v['price'] or !is_numeric($v['price']) or $v['price']<0 ){
					$v['price'] = '0';
				}
				$luozuanS[]        = $v;
			}
		}
		$GAL  -> where('goods_id = '.$goods_id) -> delete();
		$GAL  -> addAll($luozuanS);

		$size =  $material['size'];
		if( !empty( $size ) ){
			foreach ($size as $key => $value) {
				foreach ($value as $k  => $v) {
					$v['goods_id']     = $goods_id;
					$v['agent_id']     = C("agent_id");
					$v['material_id']  = $key;
					//必须为数字
					if(!is_numeric($v['min_size'])){
						$v['min_size'] = '0';
					}
					//必须为数字
					if(!is_numeric($v['max_size'])){
						$v['max_size'] = '0';
					}
					if( $v['sex'] ){
						$v['sex'] = intval($v['sex']);
					}
					//必须填写损耗，并为数字
					if(!is_numeric($v['size_price'])){
						$this -> error('尺寸价格必须大于0!');
					}
					$sizeS[]  = $v;
				}
			}
			$GAS->where('goods_id = '.$goods_id)->delete();
			$GAS->addAll($sizeS);
		}


		//获取材质金价
		$GM = M('goods_material');
		$gp = $GM -> where('material_id = '.$infoS[0]['material_id'])->getField('gold_price');
		if(!$gp){
		    $this -> error('你的材质没有设置金价，请设置金价!');
		}
		$price = $sizeS[0]['size_price'];//$this->recalculatingPriceDo($wn,$ln,$gp,$bc,$lp,$dp,0);
		return $price;
	}
	/**
	 * 生成新的sku编码和检查编码是否可用
	 */
	protected function generateSkuSn($sku_sn,$goods_id,$attributes){
	    $sn     = date('Ymdhis',time()).rand(10000, 99999);
	    $sku_sn = empty($sku_sn)?$sn:$sku_sn;
		$G      = D('Common/Goods');
		$G     -> calculationPoint($goods_id);
		$list   = $G->getGoodsSku( 0 , "sku_sn = '$sku_sn'" , 'list');
		if($list and count($list) > 1){
		    $this -> error('出错，有多个相同的SKU编码!');
		}else if( $list ){
		    if($list[0]['goods_id'] == $goods_id and $list[0]['attributes'] == $attributes){
		        return $sku_sn;
		    }else{
		        $this->error('出错，其他产品已经使用了这个SKU编码!');
		    }
		} else {
		    return $sku_sn;
		}
	}

	/**
	 * 添加成品成品的SKU
	 * @param int $goods_id
	 * @param array $sku
	 */
	protected function goodsSku($goods_id,$sku){
	    $GS = M('goods_sku');
		if(empty($sku)){
			$this->error('必须填写最少一个SKU数据');
		}
	    foreach ($sku as $key => $value) {
	        $arr['sku_sn'] = $this->generateSkuSn($value['sku_sn'],$goods_id,$key);
	        $arr['goods_id'] = $goods_id;
			$arr['agent_id'] = C("agent_id");
	        $arr['goods_number'] = $value['goods_number']?$value['goods_number']:1;
	        $arr['goods_price'] = $value['goods_price'];
	        $arr['attributes'] = $key;
			if(C('unforgecode')){
				$arr['unforgecode'] =$value['unforgecode'];
			}
			
	        $skuS[] = $arr;
	    }
	    $GS->where('goods_id = '.$goods_id)->delete();
	    return $GS->addAll($skuS);
	}

	/**
	 * 检查页面发送过来的产品编号，产品表是否有产品编号
	 * @param string $goods_sn
	 * @param int $goods_id
	 * @return boolean
	 */
	protected function checkGoodsSn($goods_sn,$goods_id=''){
		$G = D('Common/Goods');//实例化产品表
		//页面没有填写编号，自动生成编号
		$sn       = date('Ymdhis',time()).rand(10000, 99999);
		$goods_sn = empty($goods_sn)?$sn:$goods_sn;
		//组装条件查询
		if($goods_id){
            $G -> set_where( 'zm_goods.goods_sn' , $goods_sn );
            $G -> set_where( 'zm_goods.goods_id'  , " <> '$goods_id'" , true);
        }else {
            $G -> set_where( 'zm_goods.goods_sn' , $goods_sn );
        }
		$res   = $G -> getList(false,false,'zm_goods.*',false,true);
		//不能用返回false
		if($res) return false;
		else return $goods_sn;
	}

	/**
	 * 获取个性符号列表
	 * @param int $goods_id
	 * @return double
	 */
	public function symbolDesign(){
		$S        = D('Common/SymbolDesign');
		$agent_id = C('agent_id');
		$list     = $S -> getList($agent_id);
		$data     = array();
		if($list){
			$data['code'] = '1';
			$data['data'] = $list;
		}else{
			$data['code'] = '0';
			$data['data'] = '';
		}
		echo json_encode($data);die;
	}

	/**
	 * 删除一个个性符号
	 * @param int $goods_id
	 * @return double
	 */
	public function symbolDesignDel($sd_id=0){
		$agent_id = C('agent_id');
		$S        = D('Common/SymbolDesign');
		$code     = $S -> delOne($sd_id,$agent_id);
		$data     = array();
		if($code){
			$data['code'] = '1';
			$data['data'] = '操作成功';
		}else{
			$data['code'] = '0';
			$data['data'] = '操作失败';
		}
		echo json_encode($data);die;
	}

	/**
	 * 添加一个个性符号
	 * @param int $goods_id
	 * @return double
	 */
	public function symbolDesignAdd(){
		$info     = $_FILES['images_path'];
		$agent_id = C('agent_id');
		$S        = D('Common/SymbolDesign');
		$code     = $S -> addOne($info,$agent_id);
		$data     = array();
		if($code){
			$data['code'] = '1';
			$data['data'] = '操作成功';
		}else{
			$data['code'] = '0';
			$data['data'] = '操作失败';
		}
		echo json_encode($data);die;
	}

	/**
	 * 添加编辑产品（实现过程）
	 */
	public function productInfoDo($goods_id=0){

		
		$G = M('goods');//实例化产品表
		$I = M('goods_images');//实例化产品图片表
		$G->startTrans();//开启事务
		//动态验证数组
		$rules = array(
			array('category_id',0,'必须选择分类ID！',2,'notin'),
			array('goods_name','require','必须填写产品名称！'),
			array('goods_type','require','必须选择产品类别！'),
			///array('goods_price','require','必须填写产品价格！'),
		);
		//验证公共数据
		$_POST['update_time']  = time();
		

	    $_POST['goods_sn'] = $this->checkGoodsSn($_POST['goods_sn'],$goods_id);
	    if(!$_POST['goods_sn']) $this->error(L('A72'));
		
		if(!$goods_id){
			$_POST['create_time'] = time();
		}
		//添加产品归属
		if($this->tid){
		    $_POST['parent_type'] = 2;
		    $_POST['parent_id']   = $this -> tid;
		}else{
		    $_POST['parent_type'] = 1;
		    $_POST['parent_id']   = 0;
		}
		$_POST['agent_id'] = C("agent_id");
		//创建数据，验证字段

		if($G->validate($rules)->create()){
			if($goods_id){
				$res = $G->where('agent_id = '.C("agent_id").' and  goods_id = '.$goods_id)->save();//修改产品
				$banfang_goods     = $G -> where(" goods_id = '$goods_id' ") -> getField('banfang_goods_id');					
                if(!$res || $banfang_goods>0){
                    $this->error('不能修改代销的产品信息！');
                }
			}else{
				$res = $goods_id = $G -> add();//添加产品
			}
			$agent_id = C('agent_id');
			$sql = "
				insert into zm_goods_shop(`goods_id`,`shop_agent_id`,`sell_status`) 
				VALUES ('$goods_id','$agent_id','1')
				ON DUPLICATE KEY UPDATE `sell_status` = `sell_status`;
			";

			$G  -> execute( $sql );
		}else{
			$G->rollback();
			$this->error($G->getError());
		}
		if($res){
			//更新产品的图片数据
			foreach (I('post.images') as $key => $image) {
				$I->where('images_id='.$image)->setField('goods_id',$goods_id);
				if(!$key){
					$thumb = $I->where('goods_id='.$goods_id)->order('images_id ASC')->getField("small_path");
					$G->where('goods_id='.$goods_id)->setField('thumb',$thumb);
				}
			}
			//修改产品属性数据
			$res = $this->attributeAssociate(I('post.attribute'),I('post.category_id'),$goods_id);
			if(!$res){$G->rollback();$this->log('error', 'A63');}
			if($_POST['goods_type'] == 3){
				//珠宝成品添加Sku
				$res = $this->goodsSku($goods_id,I('post.sku'));
				foreach (I('post.sku') as $key => $value) {
				    $value['goods_number'] = !empty($value['goods_number'])?$value['goods_number']:1;
				    $number += intval($value['goods_number']);
				}
				$G->where('goods_id = '.$goods_id)->setField('goods_number',$number);
				if(!$res){$G->rollback();$this->log('error', 'A63');}
			}elseif ($_POST['goods_type'] == 4){
				//定制货品添加金工石数据
				if( !empty($_POST['price_model']) ){
					$res = $this->materialAssociateExt($goods_id);
				}else{
					$res = $this->materialAssociate($goods_id);
				}
				if($res > 0){
					//有金工石数据，根据金工石数据自动计算价格
					$G->where('goods_id = '.$goods_id)->setField('goods_price',$res);
				}
			}
			
			if($_FILES['video_adds']){
				$upload           = new \Think\Upload();                // 实例化上传类
				$upload->maxSize  = 3136512 ;                           // 设置附件上传大小  3MB
				$upload->exts     = array('mp4');                       // 设置附件上传类型   , 'xls', 'xlsx'
				$upload->rootPath = realpath(dirname(__FILE__).'/../../../Public/Uploads/diamond').'/'.C('agent_id').'/pic_video/'; // 设置附件上传目录
				if (!file_exists($upload->rootPath)){
					mkdir($upload->rootPath, 0777, true);
				}			
				//$upload->rootPath = './Uploads/'; // 设置附件上传根目录
				$upload->savePath =  ''; // 设置附件上传（子）目录
				$info        = $upload->upload();
				if($info){
					// if (!file_exists($upload->rootPath)){
						// mkdir($upload->rootPath, 0777, true);
					// }
					$savePath    = 'Uploads/diamond/'.C('agent_id').'/pic_video/'.date('Y-m-d',time()); // 设置附件上传目录
					$video_path  = $savePath.'/'.$info['video_adds']['savename'];
				}
			}
			
			if(!empty($info['video_adds']['ext'])){
				if($info['video_adds']['ext'] !='mp4'){
					$this->error('上传的视频只能是mp4');
				}else{
					$zgv 						= M('goods_videos');
					$exist  = $zgv->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->getField(1);
					if($exist){
						$exist  = $zgv->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->delete();
					}					
					
					$goods_vides['video_adds'] = $video_path ;
					if($goods_vides['video_adds']){
						$goods_vides['goods_id'] 	= $goods_id;
						$goods_vides['create_time'] = time();
						$goods_vides['agent_id']	= C("agent_id");
						$zgv->add($goods_vides);
					}
				}
			}
			
			if($_POST['video_adds_text']){
				if(!$zgv ){
					$zgv 						= M('goods_videos');
				}
				preg_match('/<iframe[^>]*\s+src="([^"]*)"[^>]*>/is', $_POST['video_adds_text'], $matched);
				if($matched[1]){
					$exist  = $zgv->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->getField(1);
					if($exist){
						$exist  = $zgv->where('goods_id = '.$goods_id.' and agent_id = '.C('agent_id'))->delete();
					}				
					$goods_vides_othen['video_adds']  = $matched[1];
					$goods_vides_othen['goods_id'] 	= $goods_id;
					$goods_vides_othen['create_time'] = time();
					$goods_vides_othen['agent_id']	= C("agent_id");
					$zgv->add($goods_vides_othen);
				}else{
					$this->error('提交的网络视频链接格式有误！');
				}
			}
			
			if($_FILES['tryThumb']['name']){
				$upload           	= new \Think\Upload();                // 实例化上传类
				$upload->maxSize	= 1024000 ;                           // 设置附件上传大小  1MB
				$upload->exts		= array('png');                       // 设置附件上传类型   , 'png'
				$upload->rootPath	= './Public/Uploads/trythumb/'; 	  // 设置附件上传根目录
				$upload->savePath	=  ''; // 设置附件上传（子）目录
				$tryThumbAction		=   $upload->uploadOne($_FILES['tryThumb']);
				if($tryThumbAction){
					$tryThumb		= './Uploads/trythumb/'.$tryThumbAction['savepath'].$tryThumbAction['savename'];
					$action			= $G->where(array('goods_id'=>$goods_id))->setField('tryThumb',$tryThumb);
					if(!$action){
						$G->rollback();$this->log('error','A63');
					}
				}else{
					$G->rollback();$this->log('error', $upload->getError());
				}
			}
			
			
			//全部成功提交事务
			$G->commit();
			if($_POST['back_url']){
				$this->assign('message','添加或修改产品数据成功!'); 
				$this->assign('waitSecond','2');
				$this->assign("jumpUrl",$_POST['back_url']);
				$this->display(C('TMPL_ACTION_SUCCESS'));
			}else{
				$this->log('success', 'A65', 'ID:'.$goods_id,U('Admin/Goods/productList'));
			}
		}else{
			$G->rollback();
			$this->log('error', 'A63');
		}
	}
	/**
	*	删除视频
	*	zhy
	*	2016年11月17日 15:25:44
	*/
	public function delete_video_id($id=0){
		 if(intval($id)>0){
			$zgv 						= M('goods_videos');
			$info = $zgv->where('videos_id = '.$id.' and agent_id ='.C("agent_id"))->find();	
			if($info){
				if($zgv->where('videos_id = '.$id.' and agent_id ='.C("agent_id"))->delete()){
					$data['msg']='删除成功！';
					$data['code']='100';
					$this->imagesDel($info['video_adds']);
				}else{
					$data['code']='101';
					$data['msg']='删除失败！';
				}
			}else{
				$data['code']='101';
				$data['msg']='没有该文件或者权限不够！';
			}
		 }else{
				$data['code']='101';
			    $data['msg']='数据有误！';
		 }
		 echo json_encode($data);exit();
	}

    /**
     * 添加编辑信息（页面显示）
     * @param number $goods_id
     */
    public function productInfo($goods_id=0) {
		$agent_id	=	C('agent_id');
		setcookie("unforgecode",C("unforgecode"));
		$gsM = M('goods_series');
		$res = $gsM->where($this->buildWhere(''))->select();
		if(!empty($res)){
			$this -> goods_series = $res;
		}else{
			$this -> goods_series = array();
		}
    	if ($goods_id) {
    		//获取产品信息
    		$G    = D('Common/Goods');//实例化产品表
			$G -> my_table_name  = 'goods';//重要
    		$info = $G -> get_info($goods_id);
    		//获取分类名称
    		$GC                    = M('goods_category');//实例化分类配置表
    		$category_info         = $GC -> alias('gc') -> where('gc.category_id = '.$info['category_id']) -> find();
			
	
    		$info['category_name'] = $category_info['category_name'].'('.$category_info['name_alias'].')';
    		$this -> info          = $info;
    		//获取图片列表
    		$this -> imgsList        = $G -> get_goods_images($goods_id);
    		$this -> vdesList        = $G -> get_goods_videos($goods_id);
			$join  = array('zm_goods_category_config AS gcc ON gcc.is_show = 1 and gcc.category_id = gc.category_id and gcc.agent_id ='.$agent_id);
			$data  = M('goods_category')->alias('gc')->join($join)->field('gc.*,gcc.name_alias')->select();
			$this -> categoryList = $this->_arrayRecursive($data, 'category_id', 'pid');
            $this -> agent_id = $agent_id = C('agent_id');
            $category_config_info  = M('goods_category_config') -> where(" category_id = $info[category_id] and agent_id = $agent_id ") -> find();
            if($info['goods_type']=='3'){
                $this -> public_gooda_desc_down = $category_config_info['public_goods_desc_chengpin_down'];
            }else if($info['goods_type']=='4'){
                $this -> public_gooda_desc_down = $category_config_info['public_goods_desc_dingzhi_down'];
            }

            $this -> goods_type   = $info['goods_type'];
            $this -> category_id  = $info['category_id'];
    	} else {
    	    //取到所有分类
			$join[] = 'zm_goods_category_config AS gcc ON gcc.is_show = 1 and gcc.category_id = gc.category_id and  gcc.agent_id ='.$agent_id;
			$data = M('goods_category')->alias('gc')->join($join)->field('gc.*,gcc.name_alias')->select();
			$this->categoryList = $this->_arrayRecursive($data, 'category_id', 'pid');

    	}
    	// dump($info);exit;
    	$this->display();
    }

    public function setPublicGoodsDesc(){

        $category_id = I("category_id",'0');
        $goods_type  = I("goods_type",'3');
        $content     = I("content",'');
        $agent_id    = C('agent_id');

        $join[]      = 'zm_goods_category_config AS gcc ON gcc.is_show = 1 and gcc.category_id = gc.category_id and '.$this->buildWhere('gcc.');
        $data        = M('goods_category')->alias('gc')->join($join)->field('gc.*,gcc.name_alias,gcc.category_config_id')->select();
        $this       -> categoryList = $this->_arrayRecursive($data, 'category_id', 'pid');

        if($_POST){
            if($category_id){
                $data                 = array();
                if( $goods_type == '3' ){
                    $data['public_goods_desc_chengpin_down'] = $content;
                }else if( $goods_type == '4' ){
                    $data['public_goods_desc_dingzhi_down']  = $content;
                }
                M('goods_category_config') -> where(" category_id = $category_id and agent_id = $agent_id ") ->data($data)-> save();
                $e = M('goods_category_config') -> getError();
                if($e){
                    $this->msg = '保存错误,请联系钻明网络！';
                }else{
                    $this->msg = '保存成功！';
                }
            }
        }
        $this     -> content = '';
        if( $category_id ){
            $info  = M('goods_category_config') -> where(" category_id = $category_id and agent_id = $agent_id ") -> find();
            if($goods_type=='3'){
                $this -> content = $info['public_goods_desc_chengpin_down'];
            }else if($goods_type=='4'){
                $this -> content = $info['public_goods_desc_dingzhi_down'];
            }
        }

        $this->goods_type  = $goods_type;
        $this->category_id = $category_id;
        $this->display();

    }

    /**
     * 删除产品,需要删除7张表的数据和实际的图片
     * @param number $goods_id
	 * updateData 2016-7-13 fangkai 追加批量删除
     */
    public function productDel($goods_id=0) {
		if(IS_AJAX){
			$goods_id = I('post.goods_id','');
			$agent_id = C('agent_id');
			if(empty($goods_id)){
				$result['status'] = 0;
				$result['info']   = '没有选择产品';
				$this->ajaxReturn($result);
			}
			//实例化产品相关联的8个表，并开启事务
			$G   = M('goods');
			$GI  = M('goods_images');
			$GAA = M('goods_associate_attributes');
			$GS  = M('goods_sku');
			$GAL = M('goods_associate_luozuan');
			$GAI = M('goods_associate_info');
			$GAD = M('goods_associate_deputystone');
			$GHS = D('Common/GoodsHomeShow');
            $GSH = M('goods_shop');

			$where['agent_id'] = array('neq',$agent_id);
			$where['goods_id'] = array('in',$goods_id);
			//查找是否有不属于自己产品的数据
			$check = $G->where($where)->select();
			if($check){
				$result['status'] = 0;
				$result['info']   = '存在上级产品，无权删除';
				$this->ajaxReturn($result);
				exit;
			}
			$where['agent_id'] = $agent_id;

			//开始事务
			$G->startTrans();
            //删除goods_shop中的上下架问题
            $res9 = $GSH->where($where)->delete();

			//删除产品表记录
			$res1 = $G->where($where)->delete();
			//删除图片表记录
			$imgList = $GI->where($where)->select();
			$res2 = $GI->where($where)->delete();
			//删除产品属性关联数据
			$res3 = $GAA->where($where)->delete();
			//删除产品SKU数据
			$res4 = $GS->where($where)->delete();
			//删除材质裸钻关联数据
			$res5 = $GAL->where($where)->delete();
			//删除材质金重损耗关联数据
			$res6 = $GAI->where($where)->delete();
			//删除副石关联数据
			$res7 = $GAD->where($where)->delete();
			//删除普通产品活动列表的数据
			unset($where['goods_id']);
			$where['gid']	= array('in',$goods_id);
			$res8 = $GHS->GoodsHomeShowDel($where);

			if($res1){
				$G->commit();
				foreach ($imgList as $key => $value) {
					unlink('./Public/'.$value['small_path']);
					unlink('./Public/'.$value['big_path']);
				}
				$result['status'] = 100;
				$result['info']   = '删除产品成功';
			}else{
				$G->rollback();
				$result['status'] = 0;
				$result['info']   = '删除产品失败';
			}
			$this->ajaxReturn($result);
		}
    }

    /**
     * 获取产品金工石数据
     * @param int $goods_id
     */
    protected function getGoodsMaterialData($goods_id){
    	$GAL = M('goods_associate_luozuan');//产品材质裸钻关联表
    	$GAI = M('goods_associate_info');//产品材质损耗金重关联表
    	$GAD = M('goods_associate_deputystone');//产品副石关联表
    	$material['deputystone'] = $GAD->where('goods_id = '.$goods_id)->select();
    	$join              = 'LEFT JOIN zm_goods_material AS gm ON gm.material_id = gai.material_id';
    	$goodsMaterialInfo = $GAI->alias('gai')->where('goods_id = '.$goods_id)->join($join)->select();
    	$list              = $GAL -> where('goods_id = '.$goods_id)->select();
    	foreach ($goodsMaterialInfo as $key => $value) {
    		foreach ($list as $k => $v) {
    			if($v['material_id'] == $value['material_id']){
    				$goodsMaterialInfo[$key]['sub'][] = $v;
    			}
    		}
    	}
    	$material['list'] = $goodsMaterialInfo;
    	return $material;
    }

    public function object_to_array($obj){
		$_arr = is_object($obj)? get_object_vars($obj) :$obj;
		foreach ($_arr as $key => $val){
			$val=(is_array($val)) || is_object($val) ? $this->object_to_array($val) :$val;
			$arr[$key] = $val;
		}
		return $arr;
	}

    public function synSupplyData(){
        $laiziWhere = '';
        $this->assign('laiziWhere', $laiziWhere);
    	$this->display("goodsSynZuanming");
    }

    public function getSynZuanmingData(){
    	$pagesize = 500;
    	$page = I('page',1);
    	$addsql = "&orderby=goods_name&orderway=DESC&page=$page&pagesize=$pagesize";
    	if($page == 1) {
    		D("GoodsLuozuan")->where(" supply_id > 0 ")->delete();
		}
    	//http://api.szzmzb.com/index.php?ZMAPPKEY=01bbb4a43a8b4fe408338433980795ed&k_id=3&orderby=goods_name&orderway=DESC&page=1&pagesize=500
    	//print_r("http://api.zmall.com/index.php?ZMAPPKEY=01bbb4a43a8b4fe408338433980795ed&k_id=".get_create_user_id()).$addsql);die;

		//$objData = json_decode(file_get_contents("http://api.szzmzb.com/index.php?ZMAPPKEY=01bbb4a43a8b4fe408338433980795ed".$addsql));
        $objData = json_decode(file_get_contents("http://zmall.com/api/index/index.html?ZMAPPKEY=01bbb4a43a8b4fe408338433980795ed".$addsql));///同步到测试平台的数据
		$res = $this->object_to_array($objData->data->rows);
        $zm_agent_id = M("config", "zm_", "ZMALL_DB")->where("config_key = 'szzmzb_in_supply_agent_id'")->getField('config_value');//钻明默认的agent_id;
		foreach($res as $newData){
			$data['goods_name'] = trim($newData['goods_sn']);
			$data['certificate_type'] = trim($newData['CertificateId']);
			$data['certificate_number'] = trim($newData['CertificateNo']);
			$data['location'] = trim($newData['Location']);
			$data['quxiang'] = trim($newData['quxiang']);
			$data['shape'] = $this->formatSynShape($newData['Shape']);
			$data['weight'] = trim($newData['Weight']);
			$data['color'] = trim($newData['Color']);
			$data['clarity'] = trim($newData['Clarity']);
			$data['cut'] = trim($newData['Cut']);
			$data['polish'] = trim($newData['Polish']);
			$data['symmetry'] = trim($newData['Symmetrys']);
			$data['fluor'] = trim($newData['Flou']);
			$data['milk'] = trim($newData['naise']);
			$data['coffee'] = trim($newData['kafeise']);
			$data['dia_table'] = trim($newData['dia_table']);
			$data['dia_depth'] = trim($newData['dia_depth']);
			$data['dia_size'] = trim($newData['dia_size']);
			$data['dia_global_price'] = trim($newData['dia_global_price']);


			$data['dia_discount'] = trim($newData['dia_discount']);
			$data['price']        = formatPrice($newData['dia_global_price']*C("dollar_huilv")*$data['weight']*($data['dia_discount']+C("luozuan_advantage"))/100);

			$data['goods_number'] = trim($newData['goods_number']);
			$data['tid']          = 0;
			$data['agent_id']     = 0;
			$data['belongs_id']   = 0;
			$data['imageURL']     = $newData['imageURL']==""?"-":$newData['imageURL'];
			$data['videoURL']     = $newData['videoURL']==""?"-":$newData['videoURL'];
			$data['belongs_id']   = 0;
            if(empty($newData['supply_id'])){
                $newData['supply_id']  = trim($zm_agent_id);
                $newData['supply_gid'] = 0;
            }
			$data['supply_id']    = trim($newData['supply_id']);
			$data['supply_gid']   = trim($newData['supply_gid']);
			$data['zm_gid']       = trim($newData['goods_id']);  //应该是gid才不会有歧义
			$diamondData[]        = $data;
		}

		if(M("goods_luozuan")->addAll($diamondData,'',' goods_number ')){
			$this->ajaxReturn(array('status'=>1,'page'=>$page,'total'=>$objData->data->total,'totalpage'=>$objData->data->totalpage,'msg'=>'第'.$page.'页数据导入成功'));
		}else{
			$this->ajaxReturn(array('status'=>0,'page'=>$page,'total'=>$objData->data->total,'totalpage'=>$objData->data->totalpage,'msg'=>'第'.$page.'页数据导入失败'));
		}
    }

    public function formatSynShape($str){
    	if($str == "001"){
    		return "ROUND";
    	}
    	elseif($str == "002"){
    		return "OVAL";
    	}
    	elseif($str == "003"){
    		return "MARQUIS";
    	}
    	elseif($str == "004"){
    		return "HEART";
    	}
    	elseif($str == "005"){
    		return "PEAR";
    	}
    	elseif($str == "006"){
    		return "PRINCESS";
    	}
    	elseif($str == "007"){
    		return "EMERALD";
    	}
    	elseif($str == "008"){
    		return "CUSHION";
    	}
    	elseif($str == "009"){
    		return "RADIANT";
    	}
    	elseif($str == "010"){
    		return "BAGUETTE";
    	}else{
    		return $str;
    	}
    }



    // 添加代销货品，产品来源于上级
    public function disGoodsAdd($p=1,$n=13){
		$parent_id = get_parent_id();
    	if(IS_POST){//处理产品关联
    		$goods_id = I('post.goods_id');
    		if(!$goods_id){
    		    $this->error('请至少选中一个产品！');
    		}
            $pid = M('goods_category_config')->where('category_id ='. I('post.category_id').' and is_show=1 and agent_id ='.C('agent_id'))->field('pid')->find();

            if($pid['pid'] == 0){
                $this->error('请将产品放到二级分类下面！');
            }
    		$data = $this->buildData();
    		foreach ($goods_id as $key => $value) {
    			if($value['is_checkbox']){
    				$arr['parent_type'] = 1;
    				$arr['parent_id']   = $parent_id;
    				$arr['category_id'] = I('post.category_id');
    				$arr['goods_id']    = $value;
    				$arr['agent_id']    = C('agent_id');
					$goods_trader[]     = $arr;
    			}
    		}
    		$GT = M('goods_trader');
    		if($GT->addAll($goods_trader)){
    			$this->success('添加代销产品成功',U('Admin/Goods/disGoodsList'));
    		}else{
    			$this->error('添加代销产品失败');
    		}
    	}else{
			//货品编号
            $where3 = '';
            $goods_sn = I('get.goods_sn');
			$goods_sn=Search_Filter_var($goods_sn);
            if($goods_sn){
                $this->goods_sn=$goods_sn;
                $where3 = " and (goods_sn like '%".$goods_sn."%' or goods_name like '%".$goods_sn."%')";

            }

			$GT       = M('goods_trader');
			$where    = "agent_id =".C("agent_id");
			$buildSql = $GT->where($where)->field('goods_id')->buildSql();
			//echo $buildSql;( SELECT `goods_id` FROM `zm_goods_trader` WHERE ( agent_id =8 ) )
			$G      = M('goods');
			$where2 = "agent_id =".$parent_id ." AND goods_id NOT IN $buildSql";
            //$where2 = agent_id =2 AND goods_id NOT IN ( SELECT `goods_id` FROM `zm_goods_trader` WHERE ( agent_id =8 ) )
			$count  = $G->where($where2.$where3)->count();
			$Page   = new Page($count,$n);
			$this   ->page = $Page->show();
			$list   = $G->where($where2.$where3)->limit($Page->firstRow,$Page->listRows)->order('goods_id DESC')->select();
    		//获取产品图片

    		foreach ($list as $key => $value) {
    		    if($key){
    		        $ids .= ','.$value['goods_id'];
    		    }else{
    		        $ids = $value['goods_id'];
    		    }

    		}
    		if(!$ids){
    		    //$this->error('上级已经没有产品可以代销了!');
                $this->error('没有查找到相应的产品!');
    		}
        	if($ids){
        	    $GI = M('goods_images');
        	    $images = $GI->where('goods_id IN('.$ids.')')->select();
        	    $list = $this->_arrayIdToKey($list,'goods_id');
        	    foreach ($images as $key => $value) {
        	        foreach ($value as $k => $v) {
        	            if($k == 'small_path' or $k == 'big_path'){
        	                $value[$k] = substr($v,1);
        	            }
        	        }
        	        $list[$value['goods_id']]['imgSub'][] =  $value;
        	    }
        	}

    		$this->list = getGoodsListPrice($list, 0, 'consignment');

    		//获取分销商分类
    		$GC   =  M('goods_category_config');
    		$list =  $GC->where($where . ' and is_show=1')->select();

    		$this->catList = $this->_arrayRecursive($list, 'category_id', 'pid');

    		$this->display();
    	}
    }

    // 删除代销货品
    public function disGoodsDel($gt_id){
    	$GT = M('goods_trader');
    	if($GT->where("gt_id = $gt_id and agent_id = ".C('agent_id'))->delete()){
    		$this->success('删除代销货品成功。');
    	}else{
    		$this->error('删除代销货品失败！');
    	}
    }


    // 证书号
    public function formatReportURL($CertificateId,$CertificateNo,$weight){
		$url = '';
		switch (strtoupper($CertificateId)){//过滤证书参数
		case 'GIA'://1
			$url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=1";
			break;
		case 'IGI'://2
			$url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=2";
			break;
		case 'HRD'://3
			$url = "/Home/Search/detail.html?zs_id=$CertificateNo&zs_weight=$weight&zs_type=3";
			break;
		case 'NGTC'://4
			$url = "/Home/Search";
			break;
		default:
			$url = '/Home/Search';
			break;
		}
		return $url;
	}

	public function jieru(){

		$agent_id    =  C('agent_id');
		$agent_where =  D('Common/Goods') -> get_where_agent();
		$GS          =  M('goods_shop'); //实例化产品图片表
		$GS          -> startTrans();    //开启事务
		$sql = "
			insert into zm_goods_shop(`goods_id`,`shop_agent_id`,`sell_status`) 
			select `goods_id`,'$agent_id','1' 
			from zm_goods where `agent_id` in ($agent_where) 
			ON DUPLICATE KEY UPDATE `zm_goods_shop`.`goods_id` = `zm_goods`.`goods_id`;
		";
		$GS  -> execute( $sql );
		if( $GS  -> getError() ){
			$GS  -> rollback();	
			$result['status'] = 0;
			$result['info']   = '操作失败，请重新尝试';
			$this->ajaxReturn($result);
		} else {
			$GS  -> commit();
			$result['status'] = 100;
			$result['info'] = '操作成功';
			$this->ajaxReturn($result);
		}
	}

	public function updateSellStatus(){
        $thisid      = I('post.goods_id','');
        $sell_status = I('post.sell_status','0');
        if(empty($thisid)){
            $result['status'] = 0;
            $result['info'] = '请选择产品';
            $this->ajaxReturn($result);
        }
        $agent_id  = C('agent_id');
        $thisid    = substr($thisid,0,-1);
        $thisid    = explode(',',$thisid);
        $G         = M('goods');
        $list      = $G -> where(array('goods_id'=>array('in',$thisid)))->select();
        $sql_value = "";
        foreach( $list as $key => $row ){
            if( $key > 0 ){
                $sql_value .= ',';
            }
            $sql_value .= " ('$row[goods_id]','$agent_id','$sell_status') ";
        }
        if($sell_status){
            $sell_status = '1';
        }else{
            $sell_status = '0';
        }

        $sql = "
            insert into zm_goods_shop(`goods_id`,`shop_agent_id`,`sell_status`) 
            VALUES $sql_value
            ON DUPLICATE KEY UPDATE `sell_status` = '$sell_status';
        ";

        $G  -> execute( $sql );
        if( $G -> getError() ){
            $G -> rollback();	
            $result['status'] = 0;
            $result['info']   = '操作失败，请重新尝试';
            $this->ajaxReturn($result);
        } else {
            $G  -> commit();
            $result['status'] = 100;
            $result['info']   = '操作成功';
            $this->ajaxReturn($result);
        }
    
	}

	//报价列表
	public function offerList($p=1,$n=13){

		$GOI = D('Common/GoodsOfferInfo');
		$setting_param = $GOI->getOfferParam();
		$this->assign('setting_param',$setting_param);
		$search = array();
		if(I('get.weight_section')){
			$search['weight_section'] = I('get.weight_section');
		}
		if(I('get.color')){
			$search['color'] = I('get.color');
		}
		if(I('get.clarity')){
			$search['clarity'] = I('get.clarity');
		}
		
		$huilv = C('dollar_huilv');
		$this->huilv = $huilv;
		
		$this->assign('search',$search);
		$search['agent_id']	= C('agent_id');
		$count = $GOI->where($search)->count();
		$Page  = new \Think\Page($count,$n);
		$this->page = $Page->show();
		$lists_temp = $GOI->where($search)->limit($Page->firstRow.','.$Page->listRows)->order()->select();
		$lists = $GOI->cateAllData($lists_temp);
		$this->assign('lists',$lists);
		$this->display();
	}
	//新增报价|编辑报价
	public function offerInfo(){
		import("Org.Util.Tools");
		$TOOLS = new \Org\Util\Tools();
		$GOI = D('Common/GoodsOfferInfo');
		$offer_id = I('request.offer_id',0,'intval');
		//配置 颜色跟净度
		$setting_param = $GOI->getOfferParam();
		if($offer_id>0){
			$offer_info = $GOI ->where('offer_id='.$offer_id)->find();
		}
		if(empty($offer_info)){
			$offer_info = array('offer_id'=>0);
		}
		if(!IS_POST){
			$this->assign('setting_param',$setting_param);
			$this->assign('offer_info', $offer_info);
			$this->display();
		}else{

			if(I('get.submit_type',0)==2){
				if(empty($_FILES['file']['name'])){
					$this->error('请选择上传文件');
				}
				$excel_param = array(
					'title'=>array('weight','color','clarity','dia_global_price','dia_discount'),
					'row_key'=>1,
					'from_height_from'=>2
				);
				$temp_data = $TOOLS->readUploadExcel($_FILES['file'],$excel_param);
				$data = array();
				$error_arr = array();
				foreach($temp_data as $keyx=>$valuex){
					$verify_data = $GOI->verify_data(array('data'=>$valuex,'offer_id'=>0,'setting_param'=>$setting_param));
					$valuex['agent_id']	= C('agent_id');
					if($verify_data['status']){
						$valuex['weight_section'] = $verify_data['weight_section'];
						$data[] = $valuex;
					}else{
						$error_arr[] = '第'.$keyx.'行数据导入失败'.$verify_data['message'];
					}
				}
				if(!empty($data)){
					$bool = $GOI->addAll($data);
				}
				if(!empty($data) && empty($error_arr)){
					$this->success("添加成功",U('Admin/Goods/offerList'));
				}else{
					echo '共添加成功'.count($data).'条数据<br>';
					for($i=0;$i<count($error_arr);$i++){
						echo $error_arr[$i].'<br>';
					}
				}
				exit;
			}else{
				//单粒上传
				$data = array(
						'weight'=>I('post.weight',0,'formatRound'),
						'color'=>I('post.color','','trim'),
						'clarity'=>I('post.clarity','','trim'),
						'dia_global_price'=>I('post.dia_global_price',0,'formatRound'),
						'dia_discount'=>I('post.dia_discount',0,'formatRound')
				);
				$verify_data = $GOI->verify_data(array('data'=>$data,'offer_id'=>$offer_id,'setting_param'=>$setting_param));
				if($verify_data['status']){
					$data['weight_section'] = $verify_data['weight_section'];
					$data['agent_id']		= C('agent_id');
					if($offer_id>0){
						$bool = $GOI->where('offer_id='.$offer_id)->save($data);
						$this->success("更新成功");
					}else{
						$bool = $GOI->add($data);
						$this->success("添加成功",U('Admin/Goods/offerList'));
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
		$GOI = D('Common/GoodsOfferInfo');
		$GOI->exportExcel();
	}
	//删除报价
	public function offerDelete(){
		$offer_id = I('request.offer_id',0,'intval');
		if($offer_id>0){
			$bool = M('goods_offer_info')->where('offer_id='.$offer_id)->delete();
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}

	}
	//钻石报价设置
	public function offerSettingInfo(){
		$GOP = M('goods_offer_point');
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
			$verify_data = D('Common/GoodsOfferInfo')->verify_point($data);
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
}
