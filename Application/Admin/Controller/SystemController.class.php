<?php
/**
 * 系统模块
 */
namespace Admin\Controller;
use Think\Upload;
use Think\Page;
class SystemController extends AdminController{

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
     * 系统设置
     * @param number $parent_type 0钻明系统，2默认分销商配置
     */
    public function systemSet($parent_type=0){
		$agent_id  = C('agent_id');
		$where     = 'agent_id = '.C("agent_id");
                $AuthList=$this->AuthListS;
    	if(IS_POST){
    		//分销商修改自己的系统配置
    	    if(in_array($this->parent_type, array(0,1,2))){
    	        $C  = M('config');
    	        $C->startTrans(); 
				$configList = $C->field('config_key,config_value,config_type')->order('sort_id')->select();//取出所有的配置项
				$CV = M('config_value');
				$configValueList = $CV->field('config_key,config_value')->where($where)->select();//取出所有的配置项
				foreach($configList as &$c1){
					foreach($configValueList as $c2){
						if( $c1['config_key'] == $c2['config_key'] ){
							$c1['config_value'] = $c2['config_value'];
						}
					}
				}
				$pointData = $_POST['point'];

				M('config_interval_point') -> where(array('id'=>array('not in',$pointData['luozuan_advantage_type']['id']),'agent_id'=>$agent_id)) -> delete();	
				M('config_interval_point') -> where(array('id'=>array('not in',$pointData['sanhuo_advantage_type']['id']),'agent_id'=>$agent_id)) -> delete();	
				M('config_interval_point') -> where(array('id'=>array('not in',$pointData['consignment_advantage_type']['id']),'agent_id'=>$agent_id)) -> delete();	
				M('config_interval_point') -> where(array('id'=>array('not in',$pointData['caizuan_advantage_type']['id']),'agent_id'=>$agent_id)) -> delete();	
    	        //配置相关的图片上传
    	        $UP           = new Upload();
    	        $UP->maxSize  = 3145728 ;
    	        $UP->exts     = array('jpg', 'gif', 'png', 'jpeg');
    	        $UP->savePath = './Uploads/Config/'.C('agent_id').'/';
    	        foreach ($configList AS $k=>$v){
					
    	            $val = $_POST[$v['config_key']];
    	            if(is_array($val)){
						if(($v['config_key'] == 'activity_time') && ($val[0] >= $val[1])){
							$C->rollback();
							$this->error('活动开始时间必须小于结束时间');
						}
    	                $config_value = implode(',',$val);
					}else{
						$val = htmlspecialchars($val);
						//修改版本号的提示
						/*if(($v['config_key'] == 'version') && ($v['config_value']>$val)){
							$C->rollback();
							$this->error('修改的版本号不能低于当前版本');
						}*/
    	                //前后端图片上传
    	                if($v['config_type'] == 6){
    	                    $upInfo = $UP->uploadOne($_FILES[$v['config_key']]);
    	                    if($upInfo){$config_value = $upInfo['savepath'].$upInfo['savename'];}
    	                }else{
    	                    $config_value = $val;
    	                }
    	            }
    	            if( isset($config_value) && $config_value != $v['config_value'] ){
						$data[$v['config_key']] = $config_value;
					}

					if( $v['config_type'] == 11 ){
						if( $config_value == '1' || $config_value == '2' ){
							if( !empty($pointData[$v['config_key']]['id']) ){
								foreach($pointData[$v['config_key']]['id'] as $key1=>$row){
									$data1                  = array();
									$data1['config_key']    = trim($v['config_key']);
									$data1['interval_type'] = intval($config_value);
									$data1['min_value']     = doubleval($pointData[$v['config_key']]['min_value'][$key1]);
									$data1['max_value']     = doubleval($pointData[$v['config_key']]['max_value'][$key1]);
									$data1['point']         = doubleval($pointData[$v['config_key']]['point'][$key1]);
									$data1['agent_id']      = $agent_id;
									if($row){
										$data1['id']        = $row;
									}
									foreach( $pointData[$v['config_key']]['min_value'] as $key2 => $row2){
										if( 
											$data1['min_value'] == $pointData[$v['config_key']]['min_value'][$key2] && 
											$data1['max_value'] == $pointData[$v['config_key']]['max_value'][$key2]
										){
											continue;
										}
										if(
											$data1['min_value'] <= $pointData[$v['config_key']]['min_value'][$key2] && 
											$data1['max_value'] <= $pointData[$v['config_key']]['min_value'][$key2]
										){
											continue;
										}
										if(
											$data1['min_value'] >= $pointData[$v['config_key']]['max_value'][$key2] && 
											$data1['max_value'] >= $pointData[$v['config_key']]['max_value'][$key2]
										){
											continue;
										}
										$C    -> rollback(); 
										$this -> error('保存错误，请正确的输入数值区间!(不允许有多个区间交叉，以及大小值颠倒的数据)','',10);
										die;
									}
									M('config_interval_point') -> add( $data1 );	
								}
							}
						} 
					}
    	           unset($config_value);
    	        }

				$y = 0;
				if ($parent_type != 2 ) {
					foreach ($data as $key => $value) {
						$_cv = $CV->where($where . " and config_key = '$key'")->select();
						if ($_cv) {
							$res = $CV->where($where . " and config_key = '$key'")->setField('config_value', $value);
						} else {
							$v = array();
							$v['parent_type'] = $this->parent_type;
							$v['parent_id'] = $this->parent_id;
							$v['agent_id'] = C("agent_id");
							$v['config_key'] = $key;
							$v['config_value'] = $value;
							$res = $CV->add($v);
						}
						if ($res) $y++;
					}
				}else{
					foreach ($data as $key => $value) {	
						$res = $C->where(" config_key = '$key'")->setField('config_value', $value);
						if ($res) $y++;
					}
				}
				if( !$C->getError() && !$CV->getError() ){
					$C    -> commit(); 
					$this -> success('修改配置成功!');
				}else{
					$C->rollback(); 
					$this->success('修改配置未成功!');
				}
    	    }
        }else{
        	//分销商修改自己的系统配置
        	if($parent_type == '2' and !isSuperAgent()){
        		$this->error('你没有当前权限！');
        	}
    	    $C  = M('config');
			$configList = $C->field('config_key_name,config_key,config_value,config_option,config_type')->order('sort_id')->select();//取出所有的配置项
			if($parent_type!='2') {
				$CV = M('config_value');
				$configValueList = $CV->field('config_key,config_value')->where($where)->select();//取出所有的配置项 
				foreach ($configList as &$c1) {
					foreach ($configValueList as $c2) {
						if ($c1['config_key'] == $c2['config_key']) {
							$c1['config_value'] = $c2['config_value'];
						}
					}
				}
			}

			
			$sms_jurisdiction = M('config_value')->where(" config_key = 'sms_jurisdiction'  and agent_id = ".C('agent_id'))->find();
 
			
			foreach ($configList AS $key=>$val){
                if($val['config_type'] == 2 or $val['config_type'] == 3 or $val['config_type'] == 10 or $val['config_type'] == 11 ){
                    $val['config_option'] = unserialize($val['config_option']);   
                }
                if( $val['config_type'] == 3 or $val['config_type'] == 10 ){
                    $val['config_value'] = explode(',', $val['config_value']);
                }
                if( $val['config_type'] == 11 ){
					if( $val['config_value'] == '1' || $val['config_value'] == '2' ){
						$val['config_goods_point_list'] = M('config_interval_point') -> where(" agent_id = $agent_id and config_key = '$val[config_key]' ") -> select();	
					} 
                }

				//如果未开启门店预约功能，则删除后台预约开关配置项
				if(C('templateSetting')['store_show'] != 1){
					if($val['config_key'] == 'booked_show' ){
						unset($val);
					}
				}
				
				//开启了短信发送权限，在给显示。
				if(!$sms_jurisdiction){
					if($val['config_key'] == 'sms_push_reminder' ){
						unset($val);
					}
				}				
				
                $array[] = $val;
            }
			$this -> dollar_huilv = D('Config') -> getOneZmConfigValue('dollar_huilv');
            $this->luozuan_jiadian = $luozuan_jiadian;
            $this->configList = $array;
            $this->display();
        }
    }

    //日志列表
    public function LogList($p='1',$n='13'){
        $LOG = M('admin_log');
        $count = $LOG->where('agent_id='.C("agent_id").' AND is_del = 0')->count();
        $Page = new \Think\Page($count,$n);
        $join = 'LEFT JOIN zm_admin_user AS au ON au.user_id = log.user_id';
        $this->list = $LOG->alias('log')
                        ->field('log.*,au.user_name')
                        ->join($join)->where($this->buildWhere('log.') .' AND is_del = 0')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->order('log.create_time DESC')->select();
        $this->page = $Page->show();
        $this->display();
    }

	/**
	*	删除在线咨询图片
	*	zhy
	*	2016年10月13日 17:59:03
	*/
    public function del_online_consult_img_origin(){
        $MCV = M('config_value');
		$where=" config_key = 'online_consult_img' and agent_id= ".C('agent_id');
		$res = $MCV->where($where)->find();
		if(!empty($res['config_value'])){
			$this->imagesDel ($res['config_value']);
			$data_res['config_value'] = '';
			$mres=$MCV->where('config_value_id= '.$res['config_value_id'])->save($data_res);
			if($mres){
				$data['code']='1';$data['msg']='恢复默认在线咨询图片成功！';
			}else{
				$data['code']='0';$data['msg']='恢复默认在线咨询图片失败！';
			}
		}else{
			$data['code']='0';$data['msg']='请先上传在线咨询图片，在行撤销！';
		}
 		$this->ajaxReturn($data);
 
    }
	
	    // 删除日志
    public function delLog($id){
        $LOG = M('admin_log');
        $LOG->is_del = 1;
        if($LOG->where($this->buildWhere() .' AND log_id = '.$id)->save()){
            $this->log('success', 'A3',' LogId:'.$id);
        }else{
            $this->log('error', 'A4',' LogId:'.$id);
        }
    }
	
	

    //消息模板
    public function msgMubanList($p=1,$n=15){
        $MB = M('msg_muban');
        $count = $MB->where($this->buildWhere())->count();
        $Page = new \Think\Page($count,$n);
        $this->list = $MB->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->display();
    }

    // 编辑消息模板
    public function msgMubanInfo($id){
    	$MM = M('msg_muban');
    	if(IS_POST){
    		$_POST['update_time'] = time();
    		if($MM->create() and $MM->where($this->buildWhere() .' AND muban_id = '.$id)->save()){
    			$this->log('success', 'A25','ID:'.$id,U('Admin/System/msgMubanList'));
    		}else{
    			$this->log('success', 'A26','ID:'.$id);
    		}
    	}else{
    		$this->info = $MM->find($id);
    		$this->display();
    	}
    }


    // 支付模式列表
    public function paymentMode(){
      $PM = M('payment_mode');
      $PML = M('payment_mode_line');
      $where = $this->buildWhere('pm.');
      $join[] = 'LEFT JOIN zm_payment_config AS pc ON pc.mode_id = pm.mode_id';
      $field = 'pm.*,pc.pay_status,pc.pay_attr';
      $this->payModeList = $PM->alias('pm')->join($join)->field($field)->where($where)->select();
	  $this->LinePayMode = $PML->where(' agent_id = '.C("agent_id"))->select();
      $this->display();
    }
	
	/**
	*	线下转帐-添加账户
	*	zhy
	*	2016年10月31日 16:10:28
	*/
	public function paymentMode_api(){
		if($_POST){ 
			$s_data['aname']		 	 = I('post.line_aname');
			$s_data['abank']		 	 = I('post.line_abank');
			$s_data['tback']			 = I('post.line_tback');
			$s_data['atype']		 	 = I('post.line_type');
			$s_data['line_id']		 	 = I('post.line_id');
			$PML = M('payment_mode_line');
			if($s_data['line_id']){
				if(empty($s_data['aname']) && empty($s_data['abank']) && empty($s_data['tback']) && empty($s_data['atype'])){
					$action = $PML->where(' id ='.$s_data['line_id'])->delete();
					if($action){
						$data['code']='200';	$data['id']=$s_data['line_id'];	$data['ret']='删除成功！';
					}else{
						$data['code']='201';	$data['ret']='删除失败！';
					}
				}else{
					$s_data['utime']=time();
					$action = $PML->where(' id ='.$s_data['line_id'])->save($s_data);
					if($action){
						$data['code']='100';	$data['id']=$s_data['line_id'];	$data['ret']='更新成功！';
					}else{
						$data['code']='101';	 $data['ret']='更新失败！';
					}
				}
			}else{
				$s_data['ctime']=time();
				$s_data['agent_id']=C("agent_id");	
				$add_action =$PML->add($s_data);
				if($add_action){
					$data['code']='300';	$data['ret']='添加成功！';
				}else{
					$data['code']='101';	$data['ret']='更新失败！';
				}
			}
			    $this->ajaxReturn($data);
		}
	}

	// 修改支付模式
	public function payModeInfo($mode_id = 0) {
		$PM = M('payment_mode');
		$PC = M('payment_config');
		$where = $this->buildWhere('pm.').' AND pm.mode_id = '.$mode_id;
		$join[] = 'LEFT JOIN zm_payment_config AS pc ON pc.mode_id = pm.mode_id';
		$field = 'pm.*,pc.pay_status,pc.pay_attr';
		$info = $PM->alias('pm')->join($join)->field($field)->where($where)->find();
		if (IS_POST){
		    $PC->startTrans();
		    $arr = $this->buildData();
		    $arr['pay_status'] = $_POST['pay_status'];
		    $arr['pay_attr'] = serialize($_POST['pay_attr']);
		    $arr['mode_id'] = $mode_id;
			$arr['agent_id'] = C("agent_id");
			$where = $this->buildWhere().' AND mode_id = '.$mode_id;
			$PC->where($where)->delete();
			$PC->add($arr);
			if(!$PC->getError()){
			    $PC->commit();
		        $this->success('修改配置成功',U('Admin/System/paymentMode'));
			}else{
			    $PC->rollback();
			    $this->error('添加出错');
			}
		}else{
		    $infoConfig = unserialize($info['mode_config']);
		    $configValue = unserialize($info['pay_attr']);
		    foreach ($infoConfig as $key => $value) {
		        $infoConfig[$key]['mode_value'] = $configValue[$value['mode_key']];
		    }
		    $this->configList = $infoConfig;
		    $this->info = $info;
		    $this->display();
		}
	}

    /**
     * 模板管理
     * @param int $template_type
     */
    public function templateManage($template_type=1) {
      	$T        = M('template');
      	$where    = $this->buildWhere('tc.');
      	$join     = 'LEFT JOIN zm_template_config AS tc ON tc.template_id = t.template_id and '.$where.' and '.'tc.parent_id = '.$this->parent_id;
      	$field    = 't.*,tc.template_status';
      	$TmpList  = $T->alias('t')->join($join)->where(' template_type = '.$template_type)->field($field)->select();
		$nullData = true;
		foreach($TmpList as $row){
			if($row['template_status']){
				$nullData = false;
			}
		}
		if($nullData){
			$TmpList[0]['template_status'] = 1;
		}
		
		$this->TmpList = $TmpList;
      	$this  -> display();
    }

	/**
	 * 使用模板
	 * @param int $template_id
	 */
	public function templateInfo($template_type,$template_id) {
		//模板
		$T = M('template');
		$where = $this->buildWhere('tc.');
		$join = ' JOIN zm_template_config AS tc ON tc.template_id = t.template_id and '.$where;
		$field = 't.*,tc.template_status,tc.template_color AS color';
		$templateList = $T->alias('t')->join($join)->where('t.template_type = '.$template_type)->group("t.template_id")->field($field)->select();
		$TC = M('template_config');
		//删除原来开启的模板状态
		foreach ($templateList as $key => $value) {
			if(isset($ids)){
				$ids .= ','.$value['template_id'];
			} else {
				$ids = $value['template_id'];
			}
		}
		$TC->startTrans();
		$where = $this->buildWhere();
		//删除原来的模板开关
		if(!empty($ids)){
			//$TC->where($where.' and template_id IN('.$ids.')')->delete();
		    $old_data['template_status'] = 0;
		    $res = $TC->where($where.' and template_id IN('.$ids.')')->setField($old_data);
		}
		if($TC->where($where.' and template_id = '.$template_id)->find()){     //如果已存在
		    $old_data['template_status'] = 1;
		    $res2 = $TC->where($where.' and template_id = '.$template_id)->setField($old_data);
		}else{
		    $template_color = $T->where('template_id = '.$template_id)->getField('template_color');
		    //新增一条模板状态
		    $color = explode(',',$template_color);
		    $data  = $this->buildData();
		    $data['parent_type']     = $this->parent_type;
		    $data['parent_id']       = $this->parent_id;
		    $data['template_id']     = $template_id;
		    $data['template_status'] = '1';
		    $data['template_color']  = $color[0];
		    $data['agent_id']        = C('agent_id');
		    $res2 = $TC->add($data);
		}
		if($res2){
			$TC->commit();
			$this->success('切换模板成功');
		}else{
			$TC->rollback();
			$this->error('切换模板失败');
		}
	}
	

	/**
	 * 配置手机站点模板
	 */
	public function templateSysMobile($template_id){
		//模板
		$T = M('template');
		$where = $this->buildWhere('tc.');
		$join = 'LEFT JOIN zm_template_config AS tc ON tc.template_id = t.template_id and '.$where;
		$field = 't.*,tc.template_status,tc.template_color AS color';
		$templateInfo = $T->alias('t')->join($join)->where('t.template_id = '.$template_id)->field($field)->find();
		
		$template_path = $templateInfo['template_path'];
		$file = './Application/Mobile/View/'.$template_path.'/Css/color/'.C('agent_id').'.css';
		$this->domain = top_domain($_SERVER['HTTP_HOST']);

		if(IS_POST){

			$data = I('post.css','', NULL);	
			if($_POST['default']){//恢复默认，这里直接将css变空。
				$data = 'body{}';
			}
			$res2 =  file_put_contents($file, $data);

			if(!$res2){	
				$this->error('样式文件存放目录无权限');
			}			
			$this->success('样式文件修改成功');
		}else{
			$this->mobile_domain = M('agent')->where('agent_id ='.C('agent_id'))->getField('mobile_domain');
			
			
			$content = file_get_contents($file);
		
			$cssText = explode('/*@@@@*/', $content);
			$this->cssContent = $cssText[1];
			
			$this->templateInfo = $templateInfo;
            $this->display();
		}
	}
	/**
	 * 配置模板
	 * updataAuth fangkai
	 * updateTime 2016/7/27
	 * @param 增加B2C模板自定义样式配置
	 */
	public function templateSys($template_id){
		//模板
		$T     = M('template');
		$where = $this->buildWhere('tc.');
		$join  = 'LEFT JOIN zm_template_config AS tc ON tc.template_id = t.template_id and '.$where;
		$field = 't.*,tc.template_status,tc.template_color AS color,tc.style';
		$templateInfo = $T -> alias('t') -> join($join) -> where('t.template_id = '.$template_id) -> field($field) -> find();
		if( IS_POST ){
			if($template_id == 8){
				$css_zidingyi = I('post.css_zidingyi','','htmlspecialchars');
				$css_where['template_id'] = $template_id;
				$css_where['template_status'] = 1;
				$css_where['agent_id'] = C('agent_id');
				$save['style'] = trim($css_zidingyi);
				$action = D('template_config')->where($css_where)->save($save);
				if(!$action){
					$this->error('数据更新出错');
				}
			}else{
				$template_path = I('post.template_path');
				$color = I('post.color');
				
				$data = $_POST['css'];//I('post.css','', NULL);		
				$TC = M('template_config');
				$TC->startTrans();
				$info = $TC->alias('tc')->where($where.' and tc.template_id = '.$template_id)->find();
				if($info and $info['template_color'] != $color){
					$TC->alias('tc')->where($where.' and tc.template_id = '.$template_id)->setField('template_color',$color);
				}elseif(!$info){
					$arr = $this->buildData();
					$arr1['template_id'] = $template_id;
					$arr1['template_status'] = 0;
					$arr1['template_color'] = $color;
					$arrS = array_merge($arr,$arr1);
					$TC->add($arrS);
				}
				
				$res2 = $this->putCssColorFile($template_path, $color, $data);
				if($TC->getError()){
					$TC->rollback();
					$this->error('数据更新出错');
				}
				if(!$res2){
					$TC->rollback();
					$this->error('样式文件存放目录无权限');
				}
				$TC->commit();
			}
			$this->success('样式文件修改成功');
		}else{
			if($template_id != 8){
				//全部颜色
				$template_color = explode(',', $templateInfo['template_color']);
				foreach ($template_color as $key => $value) {
					$arr['key'] = $value;
					$arr['val'] = $value;
					$arrS[] = $arr;
				}
				$this->template_color = $arrS;
				//获取样式文件
				$cssText = explode('/*@@@@*/', $this->getCssColorFile($templateInfo['template_path'], $templateInfo['color']));
				$this->cssContent = $cssText[0];
				$this->cssContent_zidingyi = $cssText[1];
				
			}
			$this->templateInfo = $templateInfo;
			$this->display();
		}
	}
	
	/**
	 * 生成CSS样式文件
	 * @param string $template_path
	 * @param string $color
	 * @param string $data
	 * @return number
	 */
	public function putCssColorFile($template_path,$color,$data){
		//组装文件地址
		$color = strtolower($color);
		$file  = './Application/Home/View/'.$template_path.'/Styles/Css/color/'.$color.'_'.C('agent_id').'.css';
		return file_put_contents($file, $data);
	}
	
	
	/**
	 * 获取模板颜色样式文件
	 * @param string $template_path
	 * @param string $color
	 */
	public function getCssColorFile($template_path,$color,$isNoId='0'){
		//组装文件地址
		$color = strtolower($color);
		$file1 = './Application/Home/View/'.$template_path.'/Styles/Css/color/'.$color.'.css';
		$file2 = './Application/Home/View/'.$template_path.'/Styles/Css/color/'.$color.'_'.C('agent_id').'.css';
		if($isNoId){
			$file = $file1;
		}else{
			if(file_exists($file2)){
				$file = $file2;
			}elseif (file_exists($file1)){
				$file = $file1;
			}else{
				$this->error('样式文件出错');
			}
		}
		$content = file_get_contents($file);
		if(IS_AJAX){
			$data['content'] = $content;
			$this->ajaxReturn($data);
		}else{
			return $content;
		}
	}
	
	
    // 配送模式列表
    public function deliveryMode() {
      $deliMode = M('delivery_mode');
	  $where = $this->buildWhere();
      $this->deliModeList = $deliMode->field('*')->where($where)->select();
      $this->display();
    }

	// 配送模式修改
	public function deliveryInfo($mode_id) {
		$deliMode = M ( 'delivery_mode' );
		$where = 'mode_id ='. $mode_id .' AND '.$this->buildWhere();
		if (IS_POST) {
			$deliData ['mode_name'] = I ( 'post.mode_name', '', 'htmlspecialchars' );
			$deliData ['mode_note'] = I ( 'post.mode_note', '', 'htmlspecialchars' );
			$deliData ['mode_time'] = I ( 'post.mode_time');
			$deliData ['mode_safep'] = I ( 'post.mode_safep');
			$deliData ['mode_expressp'] = I ( 'post.mode_expressp');			
			if ($deliMode->where($where)->save( $deliData )) {
				$this->log ( 'success', 'A8006', 'ID:' . $mode_id, U ( 'Admin/System/deliveryMode' ) );
			} else {
				$this->log ( 'error', 'A8007' );
			}
		} else {
			$this->editDeli = $deliMode->field ( '*' )->where($where)->find ();
			$this->display ();
		}
	}
	
	// 导航列表
	public function navList(){
		$N = M('nav');
		$where = $this->buildWhere('n.');
		$field = 'n.*,gc.category_name';
		$join = 'LEFT JOIN zm_goods_category AS gc ON gc.category_id = n.category_id';
		$list = $N->alias('n')->field($field)->where($where)->join($join)->select();
		$this->list = $this->_arrayRecursive($list, 'nid', 'pid');
		$this->display();
	}
	
	//添加编辑导航
	public function navInfo($nid=0){
		$N = M('nav');
		if(IS_POST){
			//不同类型去除其他类型数据
			if($_POST['nav_type'] == 1){
				unset($_POST['function_id']);unset($_POST['goods_series_id']);
			}elseif ($_POST['nav_type'] == 2){
				unset($_POST['category_id']);unset($_POST['goods_series_id']);
			}elseif ($_POST['nav_type'] == 3){
				unset($_POST['category_id'],$_POST['function_id']);unset($_POST['goods_series_id']);
			}elseif ($_POST['nav_type'] == 5){
				unset($_POST['category_id'],$_POST['function_id']);
			}
			
			if (!empty($_FILES['nav_img']['name'])) {
				$upload = new \Think\Upload(); // 实例化上传类
				$upload->maxSize = 3145728; // 设置附件上传大小  
				$upload->exts = array ('jpg','gif','png','jpeg'); // 设置附件上传类型
				$upload->savePath = '/Uploads/ad/'.C('agent_id').'/'; // 设置附件上传目录 
				$info = $upload->upload (); // 上传多个文件
				if (! $info) {
					$this->log ( 'error', L ( 'A8013' ) . ',' . $upload->getError () );
				} else {
					if($info['nav_img']['savename']){
						$_POST['nav_img'] = $info['nav_img']['savepath'] . $info['nav_img']['savename'];
					}
				}
			}

			if($nid){
				if($N->create() and $N->where($this->buildWhere().' and nid = '.$nid)->save()){
					$this->success('修改导航成功!',U('Admin/System/navList'));
				}else{
					$this->error('修改导航失败!');
				}
			}else{
				$_POST['agent_id'] = C("agent_id");
				$_POST = array_merge($_POST,$this->buildData());
				if($N->create() and $N->add()){
					$this->success('添加导航成功!',U('Admin/System/navList'));
				}else{
					$this->error('添加导航失败!');	
				}						
			}		
		}else{
			//获取当前分销商分类
			$GC = M('goods_category');
			$where = $this->buildWhere('gcc.');
			$join[] = 'zm_goods_category_config AS gcc ON gcc.category_id = gc.category_id ';
			$list = $GC->alias('gc')->join($join)->where($where)->select();
			$this->catList = $this->_arrayRecursive($list, 'category_id', 'pid');
			//获取顶级导航
			$this->navList =$N->where($this->buildWhere().' and pid=0')->select();
			if($nid){
				$this->info = $N->where($this->buildWhere())->find($nid);
			}
			$this->goodsSeries = M("goods_series") ->where('agent_id = '.C('agent_id')) -> select();
			$this->display();
		}
	}
	
	/**
	 * 设置不同材质的金费
	 */
	public function materialGoldPrice(){
		//实例化材质
		$GM = M('goods_material');
		$where = $this->buildWhere();
		if(IS_POST){
			foreach ($_POST['material_id'] as $key => $value) {
				$GM->where($where.' and material_id = '.$key)->setField('gold_price',$value);
			}
			if($this->recalculatingPriceS()){
			    $this->success('修改金价成功!所有产品价格已经全部重新计算!');
			}else{
			    $this->success('修改金价失败!');
			}
		}else{
			$this->materialList = $GM->where($where)->select();
			$this->display();
		}
	}
	
	//删除导航
	public function navDel($nid){
		$N = M('nav');
		$where = $this->buildWhere();
		if($N->where($where)->delete($nid)){
			$this->success('删除成功',U('Admin/System/navList'));
		}else{
			$this->error('删除出错!');
		}
	}
	//
	public function  templateLayoutSys($template_id = 0){
		$this -> template_id = $template_id;
		$this -> template_name = M('template')->where('template_id='.$template_id)->getField('template_name');
		$re   = M('Layout') -> select();
		$html = "";
		foreach($re as $k=>$r) {

			$html  .= '<ul><div class="tname"><label><span>'.$r['title'].'</span></label></div><div style="padding:10px;"> ';
			$re_item = M('layout_item')->where(" layout_id = ".$r['layout_id'].' and template_id = '.$template_id )->select();
			if(!empty($re_item)){
				$re_item_setting = M('layout_item_setting')->where(" layout_name = '".$r['name'].'\' and template_id = '.$template_id .' and '.$this->buildWhere())->select();
				foreach($re_item as &$row){
					foreach($re_item_setting as $row2){
						if($row['layout_item_key']==$row2['layout_item_key']){
							$row = $row2;
						}
					}
					if(!isset($row['id'])){
						$row['id']=0;
					}
					if(!isset($row['status'])){
						$row['status']=1;
					}
				}
			}
			if($re_item){
				$html .= '<table class="table"  border="1px" cellspacing="0px" style="width: 100%;border-collapse:collapse;">';
				$html .= '<tr align="center">
								<td align="center" style="width:180px;"><span>描述/key</span></td>
								<td align="center"><span>模块标题</span></td>
								<td align="center"><span>模块类型</span></td>
								<td align="center"><span>内容分类</span></td>
								<td align="center"><span>显示数量</span></td>
								<td align="center"><span>是否显示</span></td>
								<td align="center" ><span>操作</span></td>
						  </tr>';
				foreach($re_item as $m) {
					$html .= '<tr align="center" >'.
								   '<td align="left"><label><span>&nbsp;'.$m['layout_item_desc'].'/'.$m['layout_item_key'].'</span></label></td>'.
								   '<td><label><span>'.$m['module_title'].'</span></label></td>'.
								   '<td><label><span>'.$m['module_type'].'</span></label></td>'.
								   '<td><label><span>'.$m['module_where'].'</span></label></td>'.
								   '<td><label><span>'.$m['module_limit'].'</span></label></td>'.
								   '<td><label><span>'.$m['status'].'</span></label></td>'.
								   '<td><label><span><a href="javascript:void(0);" layout_item_id="'.$m['layout_item_id'].'" id="'.$m['id'].'" class="parentIframe">编辑</a></span></label></td>'.
							  '</tr>';
				}
				$html .='</table>';
			}else{
				$html .= '<li align="center" style="width:100%;">
								<span>此页无可配置内容...</span>
						  </li>';
			}

			$html .= '<div class="clear"></div></div></ul>';
		}
		$this->html = $html;
		$this->display();
	}
	public function  templateModuleEditor($layout_item_id=0,$id=0){
		$layoutM              = M('layout');
		$layout_itemM         = M('layout_item');
		$layout_item_settingM = M('layout_item_setting');
		$layout = $layoutM->select();
		$layout_item = array();
		foreach($layout as $row) {
			$row['name'] = str_replace('/','',$row['name']);
			$layout_item[$row['name']] = $layout_itemM->where("layout_id = '$row[layout_id]'")->select();
		}
		$this->layout = $layout;
		$this->layout_item = json_encode($layout_item);

		if(IS_POST) {

			$deliData ['layout_name']     = I ( 'post.layout_name', '', 'htmlspecialchars' );
			$deliData ['layout_item_key'] = I ( 'post.layout_item_key', '', 'htmlspecialchars' );
			$deliData ['module_type']     = I ( 'post.module_type', '', 'htmlspecialchars' );
			$deliData ['module_title']    = I ( 'post.module_title', '', 'htmlspecialchars' );
			$deliData ['module_where']    = I ( 'post.module_where', '', 'htmlspecialchars' );
			$deliData ['module_limit']    = I ( 'post.module_limit', '', 'htmlspecialchars' );
			$deliData ['status']          = I ( 'post.status', '', 'htmlspecialchars' );
			if(intval($id)>0) {
				$layout_item_settingM->where('id = ' . intval($id).' and ' . $this->buildWhere())->save($deliData);
			}else{
				if(in_array(7, $this->group)){
					$deliData ['parent_type']        = 2;
					$deliData ['parent_id']          = $this->tid;
				}else{
					$deliData ['parent_type']        = 1;
					$deliData ['parent_id']          = 0;
				}
				$deliData ['template_id'] = I ( 'post.template_id', '', 'htmlspecialchars' );
				$layout_item_settingM->add($deliData);
			}

			$this -> update_text = '<tr align="center" style="height: 20px;"><td colspan="2"><span style="color: red;">操作成功</span></td></tr>';
		}

		$GC      = M('goods_category');
		$where   = $this->buildWhere('gcc.');
		$join[]  = 'zm_goods_category_config AS gcc ON gcc.category_id = gc.category_id and '.$where;
		$list    = $GC->alias('gc')->join($join)->select();
		$catList = $this->_arrayRecursive($list, 'category_id', 'pid');
		$cathtml = "";
		foreach($catList as $cat){
			$cathtml .= "<option value=\"category_id={$cat[category_id]}\" >&nbsp;&nbsp;{$cat[category_name]}({$cat[name_alias]})</option>";
			if(is_array($cat['sub'])){
				foreach($cat['sub'] as $sub){
					$cathtml .= "<option value=\"category_id={$sub[category_id]}\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|---{$sub[category_name]}({$sub[name_alias]})</option>";
				}
			}
		}

		$AC        = M('article_cat');
		if(in_array(7, $this->group)){
			$where = 'parent_type = 2 and belongs_id = '.$this->tid;
		}else{
			$where = 'belongs_type = 1 and belongs_id = 0 ';
		}
		$list   = $AC->where($where)->select();
		$aCatList = $this->_arrayRecursive($list, 'cid', 'parent_id');
		$acathtml = "";
		foreach($aCatList as $cat){
			$acathtml .= "<option value=\"cid={$cat[cid]}\" >&nbsp;&nbsp;{$cat[catname]}</option>";
			if(is_array($cat['sub'])){
				foreach($cat['sub'] as $sub){
					$acathtml .= "<option value=\"category_id={$sub[cid]}\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|---{$sub[catname]}</option>";
				}
			}
		}

		$module_type         = array();
		$module_type[0]      = "<option value=\"\" >默认全局分类</option>";
		$module_type[1]      = "<option value=\"\" >全部裸钻分类</option>";
		$module_type[2]      = "<option value=\"\" >全部散货分类</option>";
		$module_type[3]      = $cathtml;
		$module_type[4]      = $cathtml;
		$module_type[5]      = $acathtml;
		$this -> module_type = json_encode($module_type);
		$info =  $layout_item_settingM->where( $this->buildWhere() .' and id = '.$id)->find();
		if(empty($info)){
			$info           = $layout_itemM->where('layout_item_id = '.$layout_item_id)->find();
			$info['status'] = 1;
			$info['id']     = 0;
		}
		$this->info = $info;
		layout(false);
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：APP设置
	 * time	：2016-5-20
	**/
	public function app(){
		$this->agent_id = C("agent_id");
		$app_modswitch = M('app_modswitch');
		$data = $app_modswitch->where(array('agent_id'=>$this->agent_id))->select();
		/* 如果查询出来的为空，则进行初始化，插入三条初始数据 */
		if(empty($data)){
			$data = array();
			$data[0] = array(
				'name'=>'钻石专区',
				'position_id'=>'1',
				'name_notes'=>'钻石专区',
				'status'=>'1',
				'img_path'=>'/images/defalut1.jpg',
				'ico_img_path'=>'/images/b2c_menu_1.png',
				'agent_id'=>$this->agent_id,
			);
			$data[1] = array(
				'name'=>'珠宝定制',
				'position_id'=>'2',
				'name_notes'=>'个性订制',
				'status'=>'1',
				'img_path'=>'/images/defalut2.jpg',
				'ico_img_path'=>'/images/b2c_menu_2.png',
				'agent_id'=>$this->agent_id,
			);
			$data[2] = array(
				'name'=>'珠宝现货',
				'position_id'=>'3',
				'name_notes'=>'珠宝现货',
				'status'=>'1',
				'img_path'=>'/images/defalut3.jpg',
				'ico_img_path'=>'/images/b2c_menu_3.png',
				'agent_id'=>$this->agent_id,
			);
			$data[3] = array(
				'name'=>'珠宝试戴',
				'position_id'=>'4',
				'name_notes'=>'珠宝试戴',
				'status'=>'1',
				'img_path'=>'',
				'ico_img_path'=>'/images/b2c_menu_4.png',
				'agent_id'=>$this->agent_id,
			);
			$data[4] = array(
				'name'=>'门店预约',
				'position_id'=>'5',
				'name_notes'=>'门店预约',
				'status'=>'1',
				'img_path'=>'',
				'ico_img_path'=>'/images/b2c_menu_5.png',
				'agent_id'=>$this->agent_id,
			);
			$data[5] = array(
				'name'=>'证书查询',
				'position_id'=>'6',
				'name_notes'=>'证书查询',
				'status'=>'1',
				'img_path'=>'',
				'ico_img_path'=>'/images/b2c_menu_6.png',
				'agent_id'=>$this->agent_id,
			);
			$action = $app_modswitch->addAll($data);
		}
		$this->modlist = $data;
		$this->title = 'APP设置';
		$this->operation = 'APP设置';
		$this->display();
	}

    /**
     * H5设置
     *
     * @author wangkun
     * @time 2017/7/24
     */
    public function h5()
    {
        $this->agent_id = C("agent_id");
        $moduleList = M('b2c_module_config')->where(array('agent_id'=>$this->agent_id))->select();
        if(empty($moduleList)){
            $data = [
                ['name' => '钻石专区', 'position_id' => 1, 'name_notes' => '钻石专区', 'agent_id' => C("agent_id"), 'sort' => 1, 'ico_img_path' => '/images/nav_01.png'],
                ['name' => '珠宝定制', 'position_id' => 2, 'name_notes' => '珠宝定制', 'agent_id' => C("agent_id"), 'sort' => 2, 'ico_img_path' => '/images/nav_02.png'],
                ['name' => '珠宝现货', 'position_id' => 3, 'name_notes' => '珠宝现货', 'agent_id' => C("agent_id"), 'sort' => 3, 'ico_img_path' => '/images/nav_03.png'],
                ['name' => '门店预约', 'position_id' => 4, 'name_notes' => '门店预约', 'agent_id' => C("agent_id"), 'sort' => 4, 'ico_img_path' => '/images/nav_04.png'],
            ];
            M('b2c_module_config')->addAll($data);
        }

        $moduleList = M('b2c_module_config')->where(array('agent_id'=>$this->agent_id))->select();

        $this->modlist = $moduleList;
        $this->title = 'H5设置';
        $this->operation = 'H5设置';
        $this->display();
    }
	
	/**
	 * auth	：fangkai
	 * content：编辑APP页面广告位
	 * time	：2016-5-21
	**/
	public function appEdit(){
		$this->agent_id = C("agent_id");
		$id = I('get.id','','intval');
		$app_modswitch = M('app_modswitch');
		$check = $app_modswitch->where(array('id'=>$id,'agent_id'=>$this->agent_id))->find();
		if(!$check){
			$this->error('广告位不存在');
			exit;
		}
		if($check['url_id'])  $check['url_other_id']=strtok($check['url_id'], ',');
		$this->info = $check;
		$this->title = 'APP设置-广告位编辑';
		$this->operation = '广告位编辑';
		$this->series_array  = M('goods_series') ->where(' agent_id = '.C('agent_id'))->select();
		$this->display();
	}

    /**
     * 编辑h5页面模块
     *
     * @author wangkun
     * @time 2017/7/24
     */
    public function h5Edit()
    {
        $this->agent_id = C("agent_id");
        $id = I('get.id','','intval');
        $check = M('b2c_module_config')->where(array('id'=>$id,'agent_id'=>$this->agent_id))->find();
        if(!$check){
            $this->error('广告位不存在');
            exit;
        }
        if($check['url_id'])  $check['url_other_id']=strtok($check['url_id'], ',');
        $this->info = $check;
        $this->title = 'H5设置-模块编辑';
        $this->operation = '模块编辑';
        $this->series_array  = M('goods_series') ->where(' agent_id = '.C('agent_id'))->select();
        $this->display();
    }
	
	/**
	 * auth	：fangkai
	 * content：保存APP页面广告位
	 * time	：2016-5-21
	**/
	public function appSave(){
		if(IS_POST){
			$app_modswitch = M('app_modswitch');
			$save['id'] = I('get.id','','intval');
			$save['agent_id'] = C("agent_id");
			$check = $app_modswitch->where(array('id'=>$save['id'],'agent_id'=>$save['agent_id']))->find();
			//print_r($app_modswitch->getlastsql());exit;
			if(!$check){
				$this->error('广告位不存在');
				exit;
			}
			if (!empty($_FILES['img_path']['name']) || !empty($_FILES['ico_img_path']['name'])) {
				$upload = new \Think\Upload(); // 实例化上传类
				$upload->maxSize = 3145728; // 设置附件上传大小
				$upload->exts = array ('jpg','gif','png','jpeg'); // 设置附件上传类型
				$upload->savePath = '/Uploads/ad/'.C('agent_id').'/'; // 设置附件上传目录
				$info = $upload->upload (); // 上传多个文件

				if (! $info) {
					$this->log ( 'error', L ( 'A8013' ) . ',' . $upload->getError () );
				} else {
					if($info['img_path']['savename']){
						$save['img_path'] = $info['img_path']['savepath'] . $info['img_path']['savename'];
					}
					if($info['ico_img_path']['savename']){
						$save['ico_img_path'] = $info['ico_img_path']['savepath'] . $info['ico_img_path']['savename'];
					}
					
				}
			}
			$save['sort'] 	= I('post.sort','');
			$save['status'] = I('post.status','');
			$save['url_id'] 	= I('post.url_id','');
			$save['name_notes'] = I('post.name_notes','');
			$action = $app_modswitch->where(array('id'=>$save['id'],'agent_id'=>$save['agent_id']))->save($save);
			if($action){
				$this->success('保存成功');
				exit;
			}else{
				$this->error('保存失败');
				exit;
			}
		}
	}

    /**
     * H5设置保存
     *
     * @author wangkun
     * @time 2017/7/24
     */
    public function h5Save()
    {
        if(IS_POST){
            $h5Config = M('b2c_module_config');
            $save['id'] = I('get.id','','intval');
            $save['agent_id'] = C("agent_id");
            $check = $h5Config->where(array('id'=>$save['id'],'agent_id'=>$save['agent_id']))->find();
            if(!$check){
                $this->error('广告位不存在');
                exit;
            }
            if (!empty($_FILES['img_path']['name']) || !empty($_FILES['ico_img_path']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 3145728; // 设置附件上传大小
                $upload->exts = array ('jpg','gif','png','jpeg'); // 设置附件上传类型
                $upload->savePath = '/Uploads/ad/'.C('agent_id').'/'; // 设置附件上传目录
                $info = $upload->upload (); // 上传多个文件

                if (! $info) {
                    $this->log ( 'error', L ( 'A8013' ) . ',' . $upload->getError () );
                } else {
                    if($info['img_path']['savename']){
                        $save['img_path'] = $info['img_path']['savepath'] . $info['img_path']['savename'];
                    }
                    if($info['ico_img_path']['savename']){
                        $save['ico_img_path'] = $info['ico_img_path']['savepath'] . $info['ico_img_path']['savename'];
                    }

                }
            }
            $save['sort'] 	= I('post.sort','');
            $save['status'] = I('post.status','');
            $save['url_id'] 	= I('post.url_id','');
            $save['name_notes'] = I('post.name_notes','');
            $action = $h5Config->where(array('id'=>$save['id'],'agent_id'=>$save['agent_id']))->save($save);
            if($action){
                $this->success('保存成功');
                exit;
            }else{
                $this->error('保存失败');
                exit;
            }
        }
    }
	
	/**
	 * auth	：fangkai
	 * content：APP风格设置
	 * time	：2016-5-21
	**/
	public function appSys(){
		$check = M('app_config')->where(array('agent_id'=>C('agent_id')))->find();
		if(IS_POST){
			$save	= I('post.');
			if (!empty($_FILES['ico_image_index']['name']) || !empty($_FILES['ico_image_category']['name']) || !empty($_FILES['ico_image_new']['name']) || !empty($_FILES['ico_image_cart']['name']) || !empty($_FILES['ico_image_my']['name'])) {
				$upload = new \Think\Upload(); // 实例化上传类
				$upload->maxSize = 3145728; // 设置附件上传大小
				$upload->exts = array ('jpg','gif','png','jpeg'); // 设置附件上传类型
				$upload->savePath = '/Uploads/appStyle/'.C('agent_id').'/'; // 设置附件上传目录
				$info = $upload->upload (); // 上传多个文件

				if (! $info) {
					$this->log ( 'error', L ( 'A8013' ) . ',' . $upload->getError () );
				} else {
					if($info['ico_image_index']['savename']){
						$save['ico_image_index'] = $info['ico_image_index']['savepath'] . $info['ico_image_index']['savename'];
					}
					if($info['ico_image_category']['savename']){
						$save['ico_image_category'] = $info['ico_image_category']['savepath'] . $info['ico_image_category']['savename'];
					}
					if($info['ico_image_new']['savename']){
						$save['ico_image_new'] = $info['ico_image_new']['savepath'] . $info['ico_image_new']['savename'];
					}
					if($info['ico_image_cart']['savename']){
						$save['ico_image_cart'] = $info['ico_image_cart']['savepath'] . $info['ico_image_cart']['savename'];
					}
					if($info['ico_image_my']['savename']){
						$save['ico_image_my'] = $info['ico_image_my']['savepath'] . $info['ico_image_my']['savename'];
					}
					
				}
			}
			$save['agent_id']	= C('agent_id');
			
			if($check){
				$action = M('app_config')->where(array('agent_id'=>$save['agent_id']))->save($save);
			}else{
				$action = M('app_config')->add($save);
				
			}
			
			if($action){
				$this->success('保存成功');
				exit;
			}else{
				$this->error('保存失败');
				exit;
			}
			
		}
		
		$this->title = 'APP设置-模板风格设置';
		$this->appStyle	= $check;
		$this->display();
	}

    /**
     * B2C手机模板风格设置
     */
    public function templateSysMobileB2C()	{
		$file = './Application/Mobile/View/b2c/Styles/css/'.C('agent_id').'.css';
        $check = M('b2c_wap_theme')->where(array('agent_id'=>C('agent_id')))->find();
        if(IS_POST){
            $save	= I('post.');
            if (!empty($_FILES['icon_index']['name'])
                || !empty($_FILES['icon_index_active']['name'])
                || !empty($_FILES['icon_category']['name'])
                || !empty($_FILES['icon_category_active']['name'])
                || !empty($_FILES['icon_diamond']['name'])
                || !empty($_FILES['icon_diamond_active']['name'])
                || !empty($_FILES['icon_cart']['name'])
                || !empty($_FILES['icon_cart_active']['name'])
                || !empty($_FILES['icon_my']['name'])
                || !empty($_FILES['icon_my_active']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 3145728; // 设置附件上传大小
                $upload->exts = array ('jpg','gif','png','jpeg'); // 设置附件上传类型
                $upload->savePath = '/Uploads/b2c/'.C('agent_id').'/'; // 设置附件上传目录
                $info = $upload->upload (); // 上传多个文件

                if (! $info) {
                    $this->log ( 'error', L ( 'A8013' ) . ',' . $upload->getError () );
                } else {
                    if($info['icon_index']['savename']){
                        $save['icon_index'] = $info['icon_index']['savepath'] . $info['icon_index']['savename'];
                    }
                    if($info['icon_index_active']['savename']){
                        $save['icon_index_active'] = $info['icon_index_active']['savepath'] . $info['icon_index_active']['savename'];
                    }
                    if($info['icon_category']['savename']){
                        $save['icon_category'] = $info['icon_category']['savepath'] . $info['icon_category']['savename'];
                    }
                    if($info['icon_category_active']['savename']){
                        $save['icon_category_active'] = $info['icon_category_active']['savepath'] . $info['icon_category_active']['savename'];
                    }
                    if($info['icon_diamond']['savename']){
                        $save['icon_diamond'] = $info['icon_diamond']['savepath'] . $info['icon_diamond']['savename'];
                    }
                    if($info['icon_diamond_active']['savename']){
                        $save['icon_diamond_active'] = $info['icon_diamond_active']['savepath'] . $info['icon_diamond_active']['savename'];
                    }
                    if($info['icon_cart']['savename']){
                        $save['icon_cart'] = $info['icon_cart']['savepath'] . $info['icon_cart']['savename'];
                    }
                    if($info['icon_cart_active']['savename']){
                        $save['icon_cart_active'] = $info['icon_cart_active']['savepath'] . $info['icon_cart_active']['savename'];
                    }
                    if($info['icon_my']['savename']){
                        $save['icon_my'] = $info['icon_my']['savepath'] . $info['icon_my']['savename'];
                    }
                    if($info['icon_my_active']['savename']){
                        $save['icon_my_active'] = $info['icon_my_active']['savepath'] . $info['icon_my_active']['savename'];
                    }
                }
            }
            $save['agent_id']	= C('agent_id');

			
			
			$data = I('post.css','', NULL);	
			if($_POST['default']){//恢复默认，这里直接将css变空。
				$data = 'body{}';
			}
			$res2 =  file_put_contents($file, $data);			
			
			
			
            if($check){
                $action = M('b2c_wap_theme')->where(array('agent_id'=>$save['agent_id']))->save($save);
            }else{
                $action = M('b2c_wap_theme')->add($save);

            }

            if($action || $data){
                $this->success('保存成功');
                exit;
            }else{
                $this->error('保存失败');
                exit;
            }

        }else{
			$content = file_get_contents($file);
			$cssText = explode('/*@@@@*/', $content);
			$check['cssContent'] = $cssText[1];
		}
        $this->title = 'B2C设置-手机模板风格设置';
        $this->b2cStyle	= $check;
        $this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：B2C设置模板
	 * time	：2016-10-27
	**/
	public function templateSetting($template_id){
		$TemplateSeeting	= new \Admin\Model\TemplateSeeting();
		
		$indexData	= $TemplateSeeting->getPositionList($template_id);
		$indexData	= arraySort($indexData,0,'position_id');

		$nav_is_pic =  M('config_value')->where(array('config_key'=>'nav_is_pic','agent_id'=>C('agent_id')))->find();
		if($nav_is_pic){
			$this->nav_is_pic=$nav_is_pic;
		}

		$this->title = $template_id == 10 ? 'B2C手机模板' : 'B2C模板';
		$this->template_id = $template_id;

		$this->indexData	= $indexData;
		$this->display();
	}
	
	/**
	 * auth	：fangkai
	 * content：B2C首页模板设置
	 * time	：2016-10-28
	**/
	public function b2cIndexSetting(){
		if(IS_AJAX){
			$TemplateSeeting	= new \Admin\Model\TemplateSeeting();
			$data	= I('post.');
			$action	= $TemplateSeeting->setTemplateSetting($data);
			if($action){
				$result['status']	= 100;
				$result['msg']		= '保存成功';
			}else{
				$result['status']	= 0;
				$result['msg']		= $TemplateSeeting->error;
			}
			$this->ajaxReturn($result);
		}
		
	}
	
	/**
	 * auth	：fangkai
	 * content：B2C首页自定义模板内容设置
	 * time	：2016-12-10
	**/
	public function templateContent(){
		$position_id	= I('get.position_id');
		$agent_id		= C('agent_id');
		$b2c_template_value= M('b2c_template_value');
		$where['position_id']	= $position_id;
		$where['agent_id']		= $agent_id;
		$checkTemplate	= $b2c_template_value->where($where)->find();
		if($_POST){
			$save['content']	= I('post.content');
			if(empty($save)){
				$this->error('自定义内容不能为空');
			}
			if($checkTemplate){
				$action = $b2c_template_value->where($where)->save($save);
			}else{
				$defultPosition = M('b2c_template_config')->where(array('id'=>$position_id))->find();
				$save['title']	= $defultPosition['title'];
				$save['english_title']	= $defultPosition['english_title'];
				$save['position_id']	= $defultPosition['id'];
				$save['type']			= $defultPosition['type'];
				$save['agent_id']		= $agent_id; 
				$action = $b2c_template_value->where($where)->add($save);
			}
			if($action){
				$this->success('保存成功');
			}else{
				$this->success('保存失败');
			}
			exit;
		}
		$this->checkTemplate	= $checkTemplate;
		$this->display();
	}
	
	/**
	 * 二级导航是否带图
	 * zhy
	 * 2016年11月15日 10:42:03
	*/
	public function b2c_nav_is_pic(){
		if(IS_AJAX){
			$data=array();
			$data['config_value'] = I('nav_is_pic');
			$zcv = M('config_value');
			$nav_is_pic = $zcv->where(array('config_key'=>'nav_is_pic','agent_id'=>C('agent_id')))->find();
			if($nav_is_pic){
				$action = $zcv->where(array('config_key'=>'nav_is_pic','agent_id'=>C('agent_id')))->save($data);
					if($action){
						$result['status']	= 100;
						$result['msg']		= '修改成功！';
					}else{
						$result['status']	= 101;
						$result['msg']		= '修改失败！';
					}
			}else{
				$add_data = array();
				$add_data['config_key'] = 'nav_is_pic';
				$add_data['config_value'] = $data['config_value'];
				$add_data['agent_id'] = C("agent_id");
				$res = $zcv->add($add_data);
				if($res){
					$result['status']	= 100;
					$result['msg']		= '修改成功！';
				}else{
					$result['status']	= 101;
					$result['msg']		= '修改失败！';
				}
			}			 
			$this->ajaxReturn($result);
		}
	}
	
	
}