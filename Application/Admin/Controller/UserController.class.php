<?php
/**
 * 管理用户相关模块
 * 客户管理模块
 */
namespace Admin\Controller;
use Think\Auth;
class UserController extends AdminController {

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

    // 管理组管理
    public function groupList(){
        $AG = M('auth_group');
        $this->groupList = $AG->where("agent_id='".C('agent_id')."' or id=1")->select();//id=1 超级管理员
        $this->display();
    }

    // 删除管理组
    public function groupDel($id){
        // if($id<10){
        //     $this->error('系统管理组不能删除');
        // }
        if($id == 1){
             $this->error('超级管理组不能删除');
        }else{
            $AG = M('auth_group');
            if($AG->where('agent_id='.C('agent_id').' and id='.$id)->delete()){
                $this->success('删除id:'.$id.'的管理组成功!');
            }else{
                $this->error('删除出错');
            }
        }
    }
    
    //修改管理员密码
    public function adminSavepwd($id=''){
        if(IS_POST){
            $AU = M('admin_user');
            $map["user_name"] = $_POST['user_name'];
            $map["agent_id"]  = C('agent_id');
            if( $AU->Where($map)->find()){
                $this->error('已有当前管理员');
            }else{
                $_POST['update_time'] = time();
                if($_POST['password'] and $_POST['repassword']){
                    if($_POST['password'] != $_POST['repassword']){
                        $this->error('两次输入的密码不一样');
                    }
                }else{
                    $this->error('必须输入密码和输入确认密码');
                }
                $data['password'] = pwdHash($_POST['password']);
                $data['email'] = $_POST['email'];
                $save = $AU->where('user_id = '.I('get.id'))->save($data);
                if($save){
                    $this->success('修改密码成功',U('Admin/Public/logout'));
                }else{
                    $this->error('修改密码失败');
                }
            }
        }else{
            $AU = M('admin_user');
            if($id){
                $this->adminUserInfo = $AU->find($id);
            }
            // 全部管理组
            $AT = M('auth_group');
            $this->groupList = $AT->select();
            $this->display();
        }
    }

    // 编辑添加管理组
    public function groupInfo($id=''){
        if(IS_POST){
            $_POST['rules'] = implode(',',$_POST['rules']);
            $_POST['agent_id'] = C('agent_id');
            $AG = M('auth_group');
            if($id){
                if($id==1){
                    $this->error('超级管理组拥有所有的权限,且信息不能更改.',U('Admin/User/groupList'));
                }
                if($AG->where('id='.$id)->save($_POST)){
                    $this->success('更新权限成功.',U('Admin/User/groupList'));
                }else{
                    $this->error('更新权限出错');
                }
            }else{
                if($AG->create() and $AG->add()){
                    $this->success('添加管理组成功!',U('Admin/User/groupList'));
                }else{
                    $this->error('添加管理组出错');
                }
            }
        }else{
            $AG = M('auth_group');

             //获取权限
            $AR = M('auth_rule');
            $ruleList = $AR->where('status=1')->order('sort_id')->select();
            // if($id){
            //     $groupInfo = $AG->where("agent_id = '".C('agent_id')."'" )->find($id);
            //     $this->groupRulesInfo = explode(',', $groupInfo['rules']);
            //     $this->groupInfo = $groupInfo;
            // }


            if($id){
                //$groupInfo = $AG->where("agent_id = '".C('agent_id')."'" )->find($id);
                 
                
                if($id == 1){       ////////admin超级管理员拥有所有的权限
                    $groupInfo = $AG->where("id = $id" )->find();             
                    foreach ($ruleList AS $value){
                        $admin_rules_info[] = $value['id'];                       
                    }
                    $this->groupRulesInfo = $admin_rules_info;
                }else{
                    $groupInfo = $AG->where("( agent_id = '".C('agent_id')."' and id = $id )  " )->find();
                    $this->groupRulesInfo = explode(',', $groupInfo['rules']);                   
                }
                $this->groupInfo = $groupInfo;
               
            }
           
            
           
            //获取模块名称
            $ART = M('auth_rule_type');
            $arr = $ART->where('s_status=1 and pid<>13')->getField('pid,auth_rule_type');
            foreach ($ruleList AS $key=>$val){
                if($arr[$ruleList[$key]['pid']]){
                    $array[$ruleList[$key]['pid']][title] = $arr[$ruleList[$key]['pid']];
                    $array[$ruleList[$key]['pid']]['sub'][$ruleList[$key]['id']] = $ruleList[$key];
                }
            }
            $this->ruleList = $array;
            $this->display();
        }
    }

    // 管理员列表
    public function adminUserList($p='1',$n='15',$id='0',$adminname=0){
        $AU = M('admin_user');
		//管理员名称筛选
         $where = 'agent_id = '.C('agent_id');
        if($id){
            $AGA = M('auth_group_access');
            $arr = $AGA->where($where.' and group_id = '.$id)->select();
            if($arr){
                foreach ($arr AS $k=>$v){
                    if($k){
                        $where1 .= ' or user_id = '.$v['uid'];                        
                    }else{
                        $where1 = 'user_id = '.$v['uid']; 
                      
                    }
                }
            }
            if(!$where1)$where1 = 'user_id = 0';
            $where = $where .' and ('.$where1.')';
        }
		//转义'  "  \
		$adminname = addslashes($adminname);
		if($adminname){
			$where .= " and user_name like '%$adminname%'";
		}
        $count = $AU->where($where)->count();
        $Page = new \Think\Page($count,$n);
        $this->userList = $AU->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('create_time desc')->select();
        $this->page = $Page->show();
        $this->display();
    }   


    // 删除管理员
    public function adminUserDel($id){
    	//if($id == 1){$this->error('不可以删除系统超级管理员');}
    	$U = M('user');
    	$group = $this->getUserAuth($id);        
    	$group = $group['group'];
		
		// if(in_array(7, $group)){
		// 	$where = 'parent_type = 2 and parent_id = '.$id;
		// }elseif (in_array(8, $group)){
		// 	$where = 'parent_type = 3 and parent_id = '.$id;
		// }else{
		// 	$where = 'parent_type = 1 and parent_id = '.$id;
		// }
        $where = 'agent_id = '.C('agent_id').' and parent_id = '.$id;
		if($U->where($where)->select()){
			$this->error('当前管理员不能删除，还有下级客户。');
		}else{
			//删除用户和对应的用户组关系
	    	$AU = M('admin_user');

            $adminUserCount = $AU->where('agent_id = '.C('agent_id'))->count();
            if( $adminUserCount == 1){
                $this->error('最后一个管理员不能删除，否则你就无法登陆系统！');
            }
            $user_name = $AU->where('agent_id = '.C('agent_id').' and user_id = '.$id)->getField('user_name');
            if($user_name == 'admin'){
                $this->error('admin是超级管理员，删除你可能就无法登陆系统！');
            }

	    	$AGA = M('auth_group_access');	    	
            $AU->where('agent_id = '.C('agent_id').' and user_id = '.$id)->delete();
            $AGA->where('agent_id = '.C('agent_id').' and uid = '.$id)->delete();
            $this->success('删除管理员成功。'); 
	    	
		}
    }

    // 添加编辑管理员
    public function adminUserInfo($id='0'){       
        if(IS_POST){
            $AU = M('admin_user');
            
            if(!is_array($_POST['group'])) {
                $this->error('必须有一个所属的用户组');               
            }           
            if($id){                     
                $_POST['update_time'] = time();
                if($_POST['password'] and $_POST['repassword']){
                     if($_POST['password'] != $_POST['repassword']){
                         $this->error('两次输入的密码不一样');
                     }else{
                     	$_POST['password'] = pwdHash($_POST['password']);
                     }
                }else{
                    unset($_POST['password']);
                    unset($_POST['repassword']);
                }
                $_POST['agent_id'] = C('agent_id');

                $user_name = $AU->where('agent_id = '.C('agent_id').' and user_id = '.$id)->getField('user_name');

                if($user_name == 'admin' and !in_array(1, $_POST['group']) ) {//自动将超级管理员权限付给 admin
                    $_POST['group'][] = 1;
                }


                if($AU->create() and $AU->where('agent_id = '.C('agent_id').' and user_id = '.$id)->save()){
                    $Auth = new \Think\Auth();					
                    if($Auth->check('Admin/User/allocation', $this->uid) or ($this->is_superManage==1)){                   	
                    	$AGA = M('auth_group_access');
                        $AGA->where('agent_id = '.C('agent_id').' and uid = '.$id)->delete();						
                        foreach ($_POST['group'] AS $k => $v){
                            $data['uid'] = $id;
                            $data['group_id'] = $v;
                            $data['agent_id'] = C('agent_id'); 							                    
                            $AGA->add($data);							
                        }                 
                        $this->success('修改管理员ID:'.$id.'成功!',U('Admin/User/adminUserList'));
                    }else{
						$this->error('你没有分配用户组权限！');
                    }
                }else{
                    $this->error('更新管理员信息出错');
                }
            }else{     
            	$map["user_name"] = $_POST['user_name'];
                $map["agent_id"] = C('agent_id');
                
                if($map["user_name"] == 'admin' and !in_array(1, $_POST['group']) ) {//自动将超级管理员权限付给 admin
                    $_POST['group'][] = 1;
                }          

            	if( $AU->Where($map)->find()){
            		$this->error('已有当前管理员');
            	}else{
	            	$_POST['update_time'] = time();
	            	if($_POST['password'] and $_POST['repassword']){
	            		if($_POST['password'] != $_POST['repassword']){
	            			$this->error('两次输入的密码不一样');
	            		}
	            	}else{
	            		$this->error('必须输入密码和输入确认密码');
	            	}
	            	$_POST['password'] = pwdHash($_POST['password']);
	            	$_POST['nickname'] = (isset($_POST['nickname']) and !empty($_POST['nickname'])) ? $_POST['nickname'] : $_POST['user_name'];
	            	$_POST['create_time'] = time();
                    $_POST['agent_id'] = C('agent_id');
	            	if($AU->create() and $id = $AU->add()){
	            		$Auth = new \Think\Auth();
	            		if($Auth->check('Admin/User/allocation', $this->uid)  or ($this->is_superManage==1)){
	            			
	            			$AGA = M('auth_group_access');
	            			$AGA->where('agent_id='.C('agent_id').' and uid = '.$id)->delete();
	            			foreach ($_POST['group'] AS $k => $v){
	            				$data['uid'] = $id;
	            				$data['group_id'] = $v;
                                $data['agent_id'] = C('agent_id');
	            				$AGA->add($data);
	            			}
	            			$this->success('添加管理员ID:'.$id.'成功!',U('Admin/User/adminUserList'));
	            		}else{
                            //没有Admin/User/allocation分配用户组的权限，则提示出错
	            			// $data['uid'] = $id;
	            			// $data['group_id'] = 9;
	            			// $AGA->add($data);
	            			// $this->success('添加管理员ID:'.$id.'成功!',U('Admin/User/adminUserList'));
                            $this->error('添加管理员信息出错');
	            		}
	            	}else{
	            		$this->error('添加管理员信息出错');
	            	}
            	}
            }
        }else{
            $AU = M('admin_user');
            if($id){
                $this->adminUserInfo = $AU->where("agent_id='".C('agent_id')."'")->find($id);
                // 所属的管理组
                $AGA = M('auth_group_access');
                $arr = $AGA->where('agent_id='.C('agent_id').' and uid ='.$id)->select();
                foreach($arr AS $k => $v){
                    $authGroupAccessList[] = $v['group_id'];
                }
                $this->authGroupAccessList = $authGroupAccessList;
            }
            // 全部管理组
            $AT = M('auth_group');
            $this->groupList = $AT->where('agent_id='.C('agent_id').' or id = 1')->select();
            $this->display();
        }
    }

    // 客户管理
    public function userList($p=1,$n=12){
		if(I('get.daochu')){
			$this->daochuChaxun();//导出符合查询条件的会员记录
		}
		
        $U = M('user');
		$AU = M('admin_user');
		$where = $this->buildWhere('u.').' and ';
        $username =addslashes(I('get.username'));
		$parentname =I('get.parentname');
        $this->username = $username;
        $this->parentname =$parentname;
        if($username) $where .= "u.username like '%".$username."%' and ";
		if($this->is_yewuyuan){
			$where .='u.parent_id = '.$this->uid.' and ';
		}else{
			if(I('get.parent_id')){
				$where .='u.parent_id = '.I('get.parent_id').' and ';
			}
		}
        
//		if($this->parentname){
//			$AU = M('admin_user');
//			$uid = $AU->where("user_name like '%".$parentname."%'")->getField('user_id');
//			$where .= 'u.parent_id = '.$uid.' and ';
//		}

		//查询出全部业务员
		$join_group[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
		$join_group[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan=1';
		$this->businessList = $AU->alias('au')->where('au.agent_id='.C('agent_id'))->field('au.user_id,au.nickname')->join($join_group)->select();
		$where .= 'u.status <> -1';
		$field='u.*,ur.name,au.user_name,(SELECT sum(order_price) FROM `zm_order` WHERE ( order_status >1 and uid = u.uid )) as user_sum_price';
		$join1 = 'left join zm_user_rank AS ur ON ur.rid = u.rank_id';
		$join2 = 'left join zm_admin_user AS au ON au.user_id = u.parent_id';
		$count = $U->alias('u')->where($where)->count();
        $Page = new \Think\Page($count,$n);
        $this->userList = $U->alias('u')->field($field)->where($where)
        	->join($join1)->join($join2)->limit($Page->firstRow.','.$Page->listRows)->order('reg_time DESC')->select();
			
			//echo  $U->getLastSql();exit();
        $this->page = $Page->show();
        $this->display();
    }

    // 客户信息编辑
    public function userInfo($uid ='0'){
        if(IS_POST){
        	//有修改密码
        	if($_POST['password'] != '' && $_POST['repassword'] != '' && $_POST['password'] == $_POST['repassword']){
        		$_POST['password'] = pwdHash($_POST['password']);
        	}else{
        		unset($_POST['password']);
        	}
        	$Auth =  new Auth();
        	//分配客户业务员
        	if(!$Auth->check('admin/user/allocationuserbusiness', $this->uid) and ($this->is_superManage!=1)){
        		unset($_POST['parent_id']);
        	}
        	//分配客户等级
        	if(!$Auth->check('admin/user/allocationuserrank', $this->uid)  and ($this->is_superManage!=1)){
        		unset($_POST['rank_id']);
        	}
        	//审核客户
        	if(!$Auth->check('admin/user/checkuserinfo', $this->uid)  and ($this->is_superManage!=1)){
        		unset($_POST['is_validate']);
        	}
			$_POST['agent_id'] = C('agent_id');
        	//保存数据
        	if(M('user')->data($_POST)->save()){
        		//$this->success("用户资料保存成功");
				$this->success('用户资料保存成功',U('Admin/User/userList'));
        	}else{
        		$this->error("用户资料保存失败");
        	}
			//echo  M("user")->getLastSql();exit();
        }else{
            //出生日期循环
            //年
            for($year=1955;$year<=2015;$year++){
                $arrYear[] = $year;
            }
            //月
            for($month=1;$month<=12;$month++){
                $arrMonth[] = $month;
            }
            //日
            for($day=1;$day<=31;$day++){
                $arrDay[] = $day;
            }
            $this->year = $arrYear;
            $this->month = $arrMonth;
            $this->day = $arrDay;
            // 客户等级
            $UR = M('user_rank');
            $this->userRankList = $UR->where($this->buildWhere().' and status = 1')->select();
            // 业务员
            $AU = M('admin_user');
            $join_group[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
			$join_group[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan=1';
            $this->businessList = $AU->alias('au')->where('au.agent_id='.C('agent_id'))->join($join_group)->distinct(true)->select();
            // 获取客户信息
            if($uid){
                $U = M('user');
                $join = 'left join zm_user_rank AS ur ON ur.rid = u.rank_id';
                $this->userInfo = $U->alias('u')->where('u.agent_id='.C('agent_id'))->field('u.*')->join($join)->find($uid);
            }
            $this->display();
        }
    }

    /**
     * 删除客户
     * @param int $uid
     */
    public function userDel($uid){
    	$U = M('user');
    	//$data['status'] = '-1';
        $userinfo = $U->where('uid = '.I('get.uid').' and agent_id = '.C('agent_id'))->field('uid,username,realname,phone,parent_id')->find();

    	if($U->where('uid = '.I('get.uid').' and agent_id = '.C('agent_id'))->delete()){
    		//$this->success('删除客户成功!');
            $this->log('success', '客户删除成功', '删除客户'.$uid.';'.serialize($userinfo), U('Admin/User/userList'));
    	}else{
    		$this->error('删除客户失败!');
    	}
    }

    // 客户等级管理
    public function userRank(){
        $UR = M('user_rank');
        $this->userRankList = $UR->where($this->buildWhere())->order('rid')->select();
        $this->display();
    }

    // 客户等级编辑
    public function userRankInfo($rid='0'){
        if(IS_POST){
            $rankM = M('user_rank');
			$_POST['agent_id'] = C("agent_id");
            if($rid){
                $where = $this->buildWhere().' and rid='.I('get.rid');
                if($rankM->where($where)->save($_POST)){
                    $this->success('更新客户等级成功！', U('Admin/User/userRank'));
                }else{
                    $this->error('更新客户等级出错');
                }
            }else{
                $rankData = $this->buildData();
                $_POST['parent_type'] = $rankData['parent_type'];
                $_POST['parent_id'] = $rankData['parent_id'];
                if($rankM->create() and $rankM->add()){
                    $this->success('添加客户等级成功!', U('Admin/User/userRank'));
                }else{
                    $this->error('添加客户等级出错!');
                }
            }
        }else{
            if (!empty($rid)) {
                $UR = M('user_rank');
                $where = $this->buildWhere().' and rid='.I('get.rid');
                $this->userRankInfo = $UR->where($where)->find();
            }
            $this->display();
        }
    }
    /**
     * 删除客户等级
     * @param  integer $rid 等级ID
     */
    public function deleteUserRank($rid=0) {
        if (M('user_rank')->where('rid='.$rid.' and agent_id = '.C('agent_id'))->delete()) {
            $this->log('success', 'L9076', 'ID:'.$rid, U('Admin/User/userRank'));
        }else{
            $this->error('error', 'L9075');
        }
    }
    // 导出用户
    public function export(){
    	$ids   = $_POST['uids'];
        $where = ' and zm_user.status <> -1 ';
    	if($ids != null){
    		$ids    = implode(',',$ids);
    		$where .= " AND uid IN(".$ids.")";
    	}else{
            $this->error('请选择要导出的客户');
        }
    	$join = " LEFT JOIN zm_admin_user ON zm_admin_user.user_id = zm_user.parent_id";
		//$join = " zm_admin_user ON zm_admin_user.user_id = zm_user.parent_id";//必须是有所属业务员的
    	$userList = M('user')->field('zm_user.*,zm_admin_user.user_name')->where( " zm_user.agent_id = ".C('agent_id').$where)->join($join)->order('reg_time DESC')->select();
		
		import("Org.Util.PHPExcel");
        $PHPExcel=new \PHPExcel();
        //$PHPReader=new \PHPExcel_Reader_Excel5();
        $xlsTitle = "用户资料";
        $objActSheet = $PHPExcel->getActiveSheet();
		//工作表名称
        $objActSheet->setTitle('用户资料');
		//excel文件名称
        $fileName = date('YmdHis',time()).rand(100000,999999);
		//设置单元格的值
        $objActSheet->setCellValue('A1','ID');
        $objActSheet->setCellValue('B1','用户名');
        $objActSheet->setCellValue('C1','真实姓名');
        $objActSheet->setCellValue('D1','所属业务员');
        $objActSheet->setCellValue('E1','用户组');
        $objActSheet->setCellValue('F1','邮箱');
        $objActSheet->setCellValue('G1','手机号');
        $objActSheet->setCellValue('H1','公司名称');
        $objActSheet->setCellValue('I1','公司地址');
        $objActSheet->setCellValue('J1','备注信息');
        $objActSheet->setCellValue('K1','注册日期');
        $objActSheet->setCellValue('L1','上次登录日期');
		//设置单元格的宽度
        $objActSheet->getColumnDimension('A')->setWidth(10);
        $objActSheet->getColumnDimension('B')->setWidth(15);
        $objActSheet->getColumnDimension('C')->setWidth(15);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(15);
        $objActSheet->getColumnDimension('F')->setWidth(25);
        $objActSheet->getColumnDimension('G')->setWidth(15);
        $objActSheet->getColumnDimension('H')->setWidth(25);
        $objActSheet->getColumnDimension('I')->setWidth(25);
        $objActSheet->getColumnDimension('J')->setWidth(30);
        $objActSheet->getColumnDimension('K')->setWidth(15);
        $objActSheet->getColumnDimension('L')->setWidth(15);
		//设置垂直居中
        $objActSheet->getStyle("A1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("B1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("C1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("D1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("E1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("F1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("G1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("H1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("I1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("J1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("K1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("L1")->getAlignment()->setHorizontal("center");
		//所要用到的列
        $sheet = array('A','B','C','D','E','F','G','H','I','J','K','L');
        foreach($sheet as $key=>$val){
        	$objActSheet->getStyle($val)->getAlignment()->setHorizontal("center");
        }
        $index = 2;//值从第二行开始
		//把值放进excel中
        foreach($userList as $key=>$v){
        	$objActSheet->setCellValue('A'.$index,$v['uid']);
        	$objActSheet->setCellValue('B'.$index,$v['username']);
        	$objActSheet->setCellValue('C'.$index,$v['realname']);
        	$objActSheet->setCellValue('D'.$index,$v['user_name']);
        	$objActSheet->setCellValue('E'.$index,$v['rank_id']);
        	$objActSheet->setCellValue('F'.$index,$v['email']);
        	$objActSheet->setCellValue('G'.$index,$v['phone']);
        	$objActSheet->setCellValue('H'.$index,$v['company_name']);
        	$objActSheet->getStyle("H".$index)->getAlignment()->setHorizontal("left");
        	$objActSheet->setCellValue('I'.$index,$v['company_address']);
        	$objActSheet->getStyle("I".$index)->getAlignment()->setHorizontal("left");
        	$objActSheet->getStyle("I".$index)->getAlignment()->setWrapText(true);
        	$objActSheet->setCellValue('J'.$index,$v['mark']);
        	$objActSheet->setCellValue('K'.$index,date('Y-m-d',$v['reg_time']));
        	$objActSheet->setCellValue('L'.$index,date('Y-m-d',$v['last_logintime']));
        	$index++;
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $PHPIF = new \PHPExcel_IOFactory();
        $objWriter = $PHPIF->createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    // 取指定用户的地址列表
    public function userAddList($uid=0) {
        $join = 'zm_region AS r ON r.region_id = s.country_id';
        $join2 = 'zm_region AS r2 ON r2.region_id = s.province_id';
        $join3 = 'zm_region AS r3 ON r3.region_id = s.city_id';
        $join4 = 'zm_region AS r4 ON r4.region_id = s.district_id';
        $field = 's.*, r.region_name AS country_name, r2.region_name AS province_name,
          r3.region_name AS city_name, r4.region_name AS district_name';
        $this->addList = M('user_address')->alias('s')->field($field)
            ->where('uid='.$uid.' and s.agent_id = '.C('agent_id'))->join($join)->join($join2)->join($join3)->join($join4)
            ->order('address_id desc')->select();
        $this->customerId = $uid;
        $this->display();
    }
    // 添加用户地址
    public function userAddAd() {
        if (IS_POST) {
            $addM = M('user_address');
            $_POST['agent_id'] = C('agent_id');
            $address_id = $addM->add($_POST);
            if ($_POST['is_default'] == 1) { // 保证一个用户只有一个默认地址
                $noDef['uid'] = I('post.uid', 0, 'htmlspecialchars');
                $noDef['address_id'] = array('NEQ', $address_id);
                $noDef['agent_id']   = array('EQ', C('agent_id'));
                $addM->where($noDef)->save(array('is_default'=>2));
            }
            if ($address_id) {
                $this->log('success', 'L9076', 'ID:'.$address_id, U('Admin/User/userAddList?uid='.$_POST['uid']));
            } else {
                $this->log('error', 'L9075');
            }
        } else {
            $this->userList = M('user')->where($this->buildWhere())->field('uid, username')->select();
            $this->display();
        }
    }
    // 编辑一个用户的地址
    public function adminUserAddInfo($address_id=0) {
        $addM = M('user_address');
        if (IS_POST) {
            if (empty($address_id)) { // 添加
                $this->log('error', 'L9017');
            } else { // 修改
                $editUid = $addM->where(array('address_id'=>$address_id))->find();
                if ($_POST['is_default'] == 1) { // 保证一个用户只有一个默认地址
                    $noDef['uid'] = I('post.uid', 0, 'htmlspecialchars');
                    $noDef['address_id'] = array('NEQ', $address_id);
					$noDef['agent_id'] = C("agent_id");
                    $addM->where($noDef['uid'])->save(array('is_default'=>2));
                }
				$_POST['agent_id'] = C("agent_id");
               if ($addM->where(array('address_id'=>$address_id))->save($_POST)) {
                   $this->log('success', 'L9044', 'ID:'.$address_id, U('Admin/User/userAddList?uid='.$editUid['uid']));
               } else {
                   $this->log('error', 'L9043');
               }
            }
        } else {
            $R = M('region');
            $this->addOnce = $addM->where(array('address_id'=>$address_id))->find();
            $this->country = $R->where('parent_id = 0')->select();
            $this->province = $R->where('parent_id = '.$this->addOnce['country_id'])->select();
            $this->city = $R->where('parent_id = '.$this->addOnce['province_id'])->select();
            $this->district = $R->where('parent_id = '.$this->addOnce['city_id'])->select();
            $this->display();
        }
    }
    // 删除用户地址
    public function adminUserAddDel($address_id=0, $uid=0) {
        $addM  = M('user_address');
        $userAdd = $addM->where('address_id='.$address_id .' and agent_id = '.C("agent_id"))->find();
        if ($addM->where('address_id='.$address_id .' and agent_id = '.C("agent_id"))->delete()) {
            $this->log ( 'success', 'A8018', 'ID:' . $address_id, U ( 'Admin/User/userAddList?uid='.$userAdd['uid'] ));
        } else {
            $this->log ( 'error', 'A8019' );
        }
    }
    // 后台消息中心，所有客户发的消息，只要做了菜单授权，所有人都可以看
    public function adminMsgsCenter(){
        $where = 'msg_type = 2 and agent_id = '.C('agent_id');
        $this->msgList = M('user_msg')->where($where)->order('create_time desc')->select();
        $this->display();
    }
    // 用户消息回复
    public function replyMsg($msg_id) {
        $msgM = M('user_msg');
        if (IS_POST) {
            $_POST['is_show'] = 0; // 回复后，状态应该是未读
            $_POST['content'] = $_POST['content']."<br/>&nbsp;&nbsp;"
                .session('admin.UserName').L('L9093').$_POST['replyContent'].'【'.date('Y-m-d H:i:s').'】';
			$_POST['agent_id'] = C('agent_id');
            if ($msgM->save($_POST)) {
                $this->log('success', 'L9076', 'ID:'.$_POST['msg_id'], U('Admin/User/adminMsgsCenter'));
            }else{
                $this->error('error', 'L9075');
            }
        } else {
            $this->replyRec = $msgM->find($msg_id);
            $this->display();
        }
    }

    // 客户跟进列表
    public function followList($p=1,$n=15){
    	$groupId = getGroupId($_SESSION['admin']['uid']);
    	$content = I("content");
    	$uid = I("uid",-1);
    	$where = " agent_id = ".C("agent_id");
    	if($groupId != 1){
    		$where .= " AND aid=".$_SESSION['admin']['uid'];
    	}
    	if($content != ""){
    		$where .= " AND content like '%$content%'";
    	}
    	if($uid != -1){
    		$where .= " AND aid=$uid";
    	}
    	$count = M("user_follow")->where($where)->count();
    	$Page = new \Think\Page($count,$n);
        $this->userList = M("user_follow")->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('addtime desc')->select();
    	$followList = M("user_follow")->where($where)->order("addtime DESC")->select();
    	foreach($followList as $key=>$val){
    		$userInfo = getUserInfo($val['uid']);
    		$adminInfo = getAdminInfo($val['aid']);
    		$followList[$key]['username'] = $userInfo['username'];
    		$followList[$key]['company_name'] = $userInfo['company_name'];
    		$followList[$key]['admin_name'] = $adminInfo['user_name'];
    	}
    	$yeWuYuans = getYeWuYuan();
    	$this->uid = $uid;
    	//print_r($yeWuYuans);
    	$this->yeWuYuans = $yeWuYuans;
    	$followList = $this->_arrayRecursive($followList, 'fid', 'pid');
    	$this->followList = $followList;
    	//dump($this->followList);
    	$this->display();
    }

    // 客户跟进详情
    public function followInfo(){
    	$users = M('user')->where("parent_id=".$_SESSION['admin']['uid'].' and agent_id = '.C('agent_id'))->getField("username,uid,company_name");
    	$this->users = $users;
    	$data = $_POST;
    	$data['aid'] = $_SESSION['admin']['uid'];
    	$data['addtime'] = time();
    	$data['agent_id'] = C('agent_id');
    	if($_POST){
    		if(M("user_follow")->data($data)->add()){
    			$this->success("添加客户跟进信息成功");
    		}else{
    			$this->success("添加客户跟进信息失败");
    		}
    	}else{
    		$this->display();
		}
    }
    
    //回复客户信息
    public function answer($fid){
		if(IS_POST){
			$UF = M('user_follow');
		
			$followInfo       = $UF->find($fid);
			$data['uid']      = $followInfo['uid'];
			$data['aid']      = $followInfo['aid'];
			$data['content']  = I('post.content');
			$data['pid']      = $fid;
			$data['addtime']  = time();
			$data['agent_id'] = C('agent_id');
			if($UF->add($data)){
				$this->success('回复跟进内容成功',U('Admin/User/followList'));
			}else{
				$this->error('回复跟进内容出错');
			}
		}else{
			$UF = M('user_follow');
			$AU = M('admin_user');
			$this->au=$AU->where('user_id = '.$this->uid.' and agent_id = '.C('agent_id'))->field('user_name')->find();
			//跟进内容
			$join[] = 'zm_user AS u ON u.uid = uf.uid';
			$join[] = 'zm_admin_user AS au ON au.user_id = uf.aid';
			$field = 'uf.*,u.username,u.realname,au.user_name';
			$this->followInfo = $UF->alias('uf')->join($join)->field($field)->where('fid = '.$fid.' and pid = 0 and agent_id = '.C('agent_id'))->find();
			//回复列表
			$this->followList = $UF->alias('uf')->join($join)->field($field)->where('pid = '.$fid.' and agent_id = '.C('agent_id'))->select();
			$this->display();
		}
    }
	/////////////////////用来导出查询到用户，必须拥有导出客户的权利
	private function daochuChaxun(){
		$Auth = new Auth();	
		if(!$Auth->check('Admin/User/export', $this->uid) and ($this->is_superManage!=1)){
        	return false;
        }	
		$U  = M('user');
		$AU = M('admin_user');
		$where = $this->buildWhere('u.').' and ';
        $username =I('get.username');
        $parentname =I('get.parentname');
        //$this->username = $username;
        //$this->parentname =$parentname;
        if($username) $where .= "u.username like '%".$username."%' and ";
		if(I('get.parent_id')){
			$where .='u.parent_id = '.I('get.parent_id').' and ';
		}
		$join_group[] = 'zm_auth_group_access AS aga ON aga.uid = au.user_id';
		$join_group[] = 'zm_auth_group AS zau ON zau.id = aga.group_id and zau.is_yewuyuan=1';
		//$this->businessList = $AU->alias('au')->field('au.user_id,au.nickname')->join($join_group)->select();
		$businessList = $AU->alias('au')->where('au.agent_id='.C('agent_id'))->field('au.user_id,au.nickname')->join($join_group)->select();
		$where .= 'u.status <> -1';
		$field ='u.*,ur.name,au.user_name';
		$join1 = 'left join zm_user_rank AS ur ON ur.rid = u.rank_id';
		$join2 = 'left join zm_admin_user AS au ON au.user_id = u.parent_id';
		
		$userList = $U->alias('u')->field($field)->where($where)->join($join1)->join($join2)->order('reg_time DESC')->select();

		import("Org.Util.PHPExcel");
        $PHPExcel=new \PHPExcel();
        $PHPReader=new \PHPExcel_Reader_Excel5();
        $xlsTitle = "用户资料";
        $objActSheet = $PHPExcel->getActiveSheet();
		//工作表名称
        $objActSheet->setTitle('用户资料');
		//excel文件名称
        $fileName = date('YmdHis',time()).rand(100000,999999);
		//设置单元格的值
        $objActSheet->setCellValue('A1','ID');
        $objActSheet->setCellValue('B1','用户名');
        $objActSheet->setCellValue('C1','真实姓名');
        $objActSheet->setCellValue('D1','所属业务员');
        $objActSheet->setCellValue('E1','用户组');
        $objActSheet->setCellValue('F1','邮箱');
        $objActSheet->setCellValue('G1','手机号');
        $objActSheet->setCellValue('H1','公司名称');
        $objActSheet->setCellValue('I1','公司地址');
        $objActSheet->setCellValue('J1','备注信息');
        $objActSheet->setCellValue('K1','注册日期');
        $objActSheet->setCellValue('L1','上次登录日期');
		//设置单元格的宽度
        $objActSheet->getColumnDimension('A')->setWidth(10);
        $objActSheet->getColumnDimension('B')->setWidth(15);
        $objActSheet->getColumnDimension('C')->setWidth(15);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(15);
        $objActSheet->getColumnDimension('F')->setWidth(25);
        $objActSheet->getColumnDimension('G')->setWidth(15);
        $objActSheet->getColumnDimension('H')->setWidth(25);
        $objActSheet->getColumnDimension('I')->setWidth(25);
        $objActSheet->getColumnDimension('J')->setWidth(30);
        $objActSheet->getColumnDimension('K')->setWidth(15);
        $objActSheet->getColumnDimension('L')->setWidth(15);
		//设置垂直居中
        $objActSheet->getStyle("A1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("B1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("C1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("D1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("E1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("F1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("G1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("H1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("I1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("J1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("K1")->getAlignment()->setHorizontal("center");
        $objActSheet->getStyle("L1")->getAlignment()->setHorizontal("center");
		//所要用到的列
        $sheet = array('A','B','C','D','E','F','G','H','I','J','K','L');
        foreach($sheet as $key=>$val){
        	$objActSheet->getStyle($val)->getAlignment()->setHorizontal("center");
        }
        $index = 2;//值从第二行开始
		//把值放进excel中
        foreach($userList as $key=>$v){
        	$objActSheet->setCellValue('A'.$index,$v['uid']);
        	$objActSheet->setCellValue('B'.$index,$v['username']);
        	$objActSheet->setCellValue('C'.$index,$v['realname']);
        	$objActSheet->setCellValue('D'.$index,$v['user_name']);
        	$objActSheet->setCellValue('E'.$index,$v['rank_id']);
        	$objActSheet->setCellValue('F'.$index,$v['email']);
        	$objActSheet->setCellValue('G'.$index,$v['phone']);
        	$objActSheet->setCellValue('H'.$index,$v['company_name']);
        	$objActSheet->getStyle("H".$index)->getAlignment()->setHorizontal("left");
        	$objActSheet->setCellValue('I'.$index,$v['company_address']);
        	$objActSheet->getStyle("I".$index)->getAlignment()->setHorizontal("left");
        	$objActSheet->getStyle("I".$index)->getAlignment()->setWrapText(true);
        	$objActSheet->setCellValue('J'.$index,$v['mark']);
        	$objActSheet->setCellValue('K'.$index,date('Y-m-d',$v['reg_time']));
        	$objActSheet->setCellValue('L'.$index,date('Y-m-d',$v['last_logintime']));
        	$index++;
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $PHPIF = new \PHPExcel_IOFactory();
        $objWriter = $PHPIF->createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
	
	}
	
    /**
     * 用户交易记录
     * zhy	find404@foxmail.com
     * 2016年12月12日 15:25:19
     */
	public function userTrecord($uid,$p=1,$n=12){
		if(is_numeric($uid)){
			$userInfo = getUserInfo($uid);
			$uo   = M('order');
			$where='order_status >1 and uid = '.$uid;
			$field='order_sn,order_price,create_time,order_id';
			$count= $uo->where($where)->count();
			$Page = new \Think\Page($count,$n);
			$this->userTrecordList 	       = $uo->field($field)->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('order_id DESC')->select();
			$this->userTrecordListSumPrice = $uo->where($where)->getField('sum(order_price)');
			//echo  $uo->getLastSql();exit();
			$this->page 				   = $Page->show();
			$this->username				   = $userInfo['username'];
		}
		$this->display();
	}
}