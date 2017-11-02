<?php
/**
 * 分销商模块
 */
namespace Admin\Controller;
use Think\Upload;
use Think\Page;
class TraderController extends AdminController {

    // 模块跳转
    public function index(){		
		$this->_isZifenxiaoshang();			
        foreach ($this->AuthListS AS $k){
            $val = stristr($k,strtolower(MODULE_NAME.'/'.CONTROLLER_NAME));
            if($val and strtolower($val) != strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME)){
               $this->redirect($val);
            }
        }
        $this->log('error', 'A1','',U('Admin/Public/login'));
    }
    
    // 分销商列表
    public function traderList($p=1,$n=13){
		$this->_isZifenxiaoshang();	
        $T = M('trader');
        $count = $T->alias('t')->where('agent_id = '.C('agent_id'))->count();
        $Page = new Page($count,$n);
        $this->page = $Page->show();
        $list = $T->alias('t')->where('agent_id = '.C('agent_id'))->limit($Page->firstRow,$Page->listRows)->select();
        $this->traderList = $list;
        $this->display();
    }

    // 编辑分销商
    public function traderInfo($tid){
		$this->_isZifenxiaoshang();
    	$T = M('trader');
    	if(IS_POST){
			$T -> create();
			$T -> where('tid = '.I('get.tid').' and agent_id = '.C('agent_id'))->save();
            if( empty($T->getError()) ){
            	$this->success('修改分销商成功!');
            }else{
            	$this->error('修改分销商出错!');
            }
        }else{
            if($tid){
            	//分销商信息
                $this->traderInfo    = $T->find($tid);
                //获取分销地区信息
                $R                   = M('region');
                $province_id         = $this->traderInfo['province_id'];
                $this->province_name = $R->where('region_id = '.$province_id)->getField('region_name');
                $city_id = $this->traderInfo['city_id'];
                $this->city_name = $R->where('region_id = '.$city_id)->getField('region_name');
				if( $this->traderInfo['status'] == 1 ){
					$A             = M('agent');
					$this -> agent = $A -> where('agent_id = '.$this->traderInfo['t_agent_id'])->find();
				}
            }
            $this->display();
        }
    }
    
    // 分销商审核
    public function traderCheck($tid){
		$this->_isZifenxiaoshang();
    	//分销商信息处理
    	$T    = M('trader');
    	$info = $T->find($tid);
    	if($info['status'] == 1){
    		$this->error('当前分销商已经审核，请不要重复审核');
    	}
    	//分销商数量判断
    	$num = $T->where('status = 1 and agent_id = '.C('agent_id'))->count();
    	//2级分销商审核检测1级分销商是否还有名额
    	if($num >= 5){
    		$this->error('对不起，已经达到5个名额上线，不能增加更多下级分销商!');
    	}
    	if(IS_POST){
    		$T->startTrans();
    		//对输入数据先做过滤
    		$user_name   = I('post.user_name');
    		$password    = I('post.password');
    		$repassword  = I('post.repassword');
    		$domain      = I('post.domain');
    		$template_id = I('post.template_id',0);
    		$m_template_id = I('post.m_template_id',0);
    		/**
    		 * 绑定一个分销商域名,监测"www,@,M"3个域名
    		 */
    		$D     = M('agent');
    		$where = "domain = 'www.$domain' or domain = '$domain' or domain = 'm.$domain'";
    		$domainInfo = $D->where($where)->find();
    		if($domainInfo){
    			$T->rollback();
    			$this->error('您申请的域名已经被绑定了！');
    		}else{
				$level = $D->where('agent_id = '.C('agent_id'))->getField('level');
    		    $data['parent_id']     = C('agent_id');
    		    $data['parent_type']   = $level+1;
    		    $data['domain']        = $domain;
    		    $data['mobile_domain'] = 'm.'.$domain;
    		    $data['agent_name']    = $info['trader_name'];
    			$t_agent_id            = $D->add($data);
    			unset($data);
    			if(!$t_agent_id){
					$T->rollback();
					$this->error('域名绑定失败！');
				}
    		}
    		/**
    		 * 增加分销商配置
    		 */
    		$C  = M('config');
    	    $CV = M('config_value');
    		$UP = new Upload();
    		$UP->maxSize  = 3145728 ;
    		$UP->exts     = array('jpg', 'gif', 'png', 'jpeg');
    		$tidPath      = $t_agent_id;//按分销商目录保存
    		$UP->savePath = './Uploads/Config/'.$tidPath.'/';
			
			$configList = $C->order('sort_id')->select();//取出所有的配置项
			//清理数据
			foreach ($configList as $key => $value) {
				$arr['parent_type'] = $level+1;
				$arr['parent_id']   = C('agent_id');
				$arr['agent_id']    = $t_agent_id;
				//图片上传
				if($value['config_type'] == 6){
					$upInfo = $UP->uploadOne($_FILES[$value['config_key']]);
					$cv = $upInfo['savepath'].$upInfo['savename'];
					if(!$cv) $cv = $value['config_value'];
				}elseif ($value['config_type'] == 3){
				    unset($cv);
				    foreach ($_POST[$value['config_key']] as $k => $v) {
				        $cv .= ','.$v;
				    }
				    $cv= substr($cv, 1);
				}else{
					$cv = $_POST[$value['config_key']];
				}
				$arr['config_value']   = empty($cv)?0:$cv;
				$arr['config_key'] = $value['config_key'];
				$data[] = $arr;
			}
			$res = $CV->addAll($data);
			unset($data);
			if(!$res){
			    $T->rollback();
			    $this->error('增加默认配置失败!');
			}
			//增加默认的金价配置
			$GM = M('goods_material');
			$materialList = $GM->where('agent_id = '.C('agent_id'))->select();
			foreach ($materialList as $key => $value) {
				$value['parent_type'] = $level+1;
				$value['parent_id']   = C('agent_id');
				$value['agent_id']    = $t_agent_id;
				unset($value['material_id']);
				$materiaArr[] = $value;
			}
			$GM->addAll($materiaArr);
    		//增加一个分销商管理员，管理员和申请的客户账户绑定
	    	$AU               = M('admin_user');
            $map["user_name"] = $user_name;
            $map["agent_id"]  = $t_agent_id;
            if($AU->Where($map)->find()){
            	$T->rollback();
            	$this->error('已有当前管理员');
            }else{
            	if(!$password or !$repassword or ($password != $repassword)){
            		$T->rollback();
            		$this->error('请输入密码和重复密码，并保证一致！');
            	}
            	$data['update_time'] = time();
            	$data['create_time'] = time();
            	$data['password']    = pwdHash($password);
            	$data['nickname']    = $user_name;
            	$data['user_name']   = $user_name;
            	$data['status']      = 1;
            	$data['agent_id']    = $t_agent_id;
            	$id = $AU->add($data);
            	if($id){
            		$AGA              = M('auth_group_access');
            		$data['uid']      = $id;
            		$data['group_id'] = 1;
            		$data['agent_id'] = $t_agent_id;
            		if(!$AGA->add($data)){
            			$T->rollback();
            			$this->error('创建管理员失败');
            		}
            	}else{
            		$T->rollback();
            		$this->error('创建管理员失败');
            	}
            }
    		//分销商和管理员绑定
    		$res = $T->where('tid = '.$tid)->setField('admin_id',$id);
            if(!$res){
            	$T->rollback();
            	$this->error('分销商绑定管理账户失败！');
            }
    		//分销商管理员和客户ID绑定
    		$U = M('user');
    		$res = $U->where('uid = '.$info['create_user_id'].' and agent_id='.C('agent_id'))->setField('bind_admin',$id);
    		if(!$res){
    			$T->rollback();
    			$this->error('分销商绑定管理账户失败！');
    		}
    		//绑定PC模板和手机模板
    		if($template_id and $m_template_id){
    		    unset($arr);
    		    $arr[0]['template_id'] = $template_id;
    		    $arr[0]['template_status'] = '1';
    		    $arr[0]['parent_type'] = $level+1;
    		    $arr[0]['parent_id'] = C('agent_id');
    		    $arr[0]['agent_id'] = $t_agent_id;
				if(!empty($m_template_id)) {
					$arr[1]['template_id'] = $m_template_id;
					$arr[1]['template_status'] = '1';
					$arr[1]['parent_type'] = $level + 1;
					$arr[1]['parent_id']   = C('agent_id');
					$arr[1]['agent_id']    = $t_agent_id;
				}
				$res = M('template_config')->addAll($arr);
    		    if(!$res){
    		        $T->rollback();
    		        $this->error('绑定模板失败！');
    		    }
    		}else{
    		    $T->rollback();
    			$this->error('请选择模板绑定！');
    		}
    		//改变分销商状态和等级
			$data                = array();
    		$data['trader_rank'] = I('post.trader_rank');
    		$data['status']      = 1;
    		$data['t_agent_id']  = $t_agent_id;			
    		if($T->where('tid = '.$tid)->save($data)){
    			$T->commit();
				$sql = "
					INSERT INTO `zm_template_ad` (`ads_id`,`images_path`,`title`,`url`,`sort`,`lang`,`status`,`agent_id`) VALUES ('3','default/big/index/1.jpg','默认图1','#','1','1','1',$t_agent_id);
					INSERT INTO `zm_template_ad` (`ads_id`,`images_path`,`title`,`url`,`sort`,`lang`,`status`,`agent_id`) VALUES ('3','default/big/index/2.jpg','默认图2','#','2','1','1',$t_agent_id);
					INSERT INTO `zm_template_ad` (`ads_id`,`images_path`,`title`,`url`,`sort`,`lang`,`status`,`agent_id`) VALUES ('4','default/big/login/3.jpg','默认图3','#','1','1','1',$t_agent_id);
					INSERT INTO `zm_template_ad` (`ads_id`,`images_path`,`title`,`url`,`sort`,`lang`,`status`,`agent_id`) VALUES ('4','default/big/login/4.jpg','默认图4','#','2','1','1',$t_agent_id);
					INSERT INTO `zm_template_ad` (`ads_id`,`images_path`,`title`,`url`,`sort`,`lang`,`status`,`agent_id`) VALUES ('4','default/big/ad/5.jpg','默认图5','#','1','1','1',$t_agent_id);

					INSERT INTO `zm_delivery_mode` (`mode_name`, `mode_note`, `agent_id`) VALUES ('送货上门', '配送时间:工作是、双休日、节假日均可送货',$t_agent_id);
					INSERT INTO `zm_delivery_mode` (`mode_name`, `mode_note`, `agent_id`) VALUES ('上门取件', '用户自己到配送点取货',$t_agent_id);

					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('现金/汇款', '通过现金和汇款支付', 'a:1:{i:0;a:4:{s:8:\"mode_key\";s:8:\"pay_info\";s:13:\"mode_key_name\";s:12:\"支付信息\";s:10:\"mode_value\";s:30:\"此处填写线下支付信息\";s:4:\"type\";s:1:\"3\";}}', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('支付宝支付', '线上支付宝支付，需要到支付宝申请支付接口', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('财付通支付', '线上财付通支付，需要到财付通申请支付接口', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('到店现金支付', '直接支付现金', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('支付宝汇款', '线下直接支付宝汇款', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('中国银行汇款', '中国银行线下汇款', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('工商银行汇款', '工商银行线下汇款', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('建设银行汇款', '建设银行线下汇款', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('农业银行汇款', '农业银行线下汇款', '', $t_agent_id);
					INSERT INTO `zm_payment_mode` (`mode_name`, `mode_note`, `mode_config`, `agent_id`) VALUES ('银联支付', '线上银联支付，需要到财付通申请支付接口', '', $t_agent_id);

					INSERT INTO `zmfx_db`.`zm_article_cat` ( `catname`, `seo_title`, `seo_keywords`, `seo_desc`, `sort`, `is_show`, `parent_id`,`agent_id`) VALUES ('行业信息', '行业信息', '行业信息', '行业信息', '50', '1', '0',$t_agent_id);
					INSERT INTO `zmfx_db`.`zm_article_cat` ( `catname`, `seo_title`, `seo_keywords`, `seo_desc`, `sort`, `is_show`, `parent_id`,`agent_id`) VALUES ('帮助中心', '帮助中心', '帮助中心', '帮助中心', '50', '1', '0',$t_agent_id);
				";
				$T     -> execute($sql);
				$art    = M('article_cat');
				$a_info = $art -> where("catname='帮助中心' and agent_id = $t_agent_id")->find();
				$art   -> execute("INSERT INTO `zmfx_db`.`zm_article` (`cat_id`, `title`, `content`, `author`, `keywords`, `is_show`, `addtime`, `file`, `link`, `description`, `thumb`,`agent_id`)
					SELECT ".$a_info['cid']." , `title`, `content`, `author`, `keywords`, 1 ,".time().", `file`, `link`, `description`, `thumb`,$t_agent_id FROM  zm_article_default;");
				$art   -> execute("
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('54', '钻石', '0', '1', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('60', '黄金', '54', '1', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('62', '耳饰', '54', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('63', '手链', '54', '3', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('64', '手镯', '54', '4', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('65', '情侣对戒', '54', '5', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('78', '项链', '54', '6', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('112', '胸针扣', '54', '7', '1', '$t_agent_id');

							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('55', '黄金', '0', '1', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('71', '戒指', '55', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('72', '吊坠', '55', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('73', '耳饰', '55', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('74', '手链', '55', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('75', '手镯', '55', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('76', '项链', '55', '2', '1', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_goods_category_config` (`category_id`, `name_alias`, `pid`, `sort_id`, `is_show`, `agent_id`) VALUES ('79', '投资礼品', '55', '2', '1', '$t_agent_id');

							INSERT INTO `zmfx_db`.`zm_nav` (`nav_name`, `nav_type`, `category_id`, `function_id`, `nav_url`,`status`, `sort`, `pid`, `agent_id`) VALUES ('裸钻查询', '2', '0', '1', '/Home/Goods/goodsDiy.html', '1', '1', '0', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_nav` (`nav_name`, `nav_type`, `category_id`, `function_id`, `nav_url`,`status`, `sort`, `pid`, `agent_id`) VALUES ('钻石散货', '2', '0', '2', '/Home/Goods/sanhuo.html', '1', '2', '0', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_nav` (`nav_name`, `nav_type`, `category_id`, `function_id`, `nav_url`,`status`, `sort`, `pid`, `agent_id`) VALUES ('成品珠宝', '1', '54', '0', '/Home/Goods/goodsCat/cid/54.html', '1', '3', '0', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_nav` (`nav_name`, `nav_type`, `category_id`, `function_id`, `nav_url`,`status`, `sort`, `pid`, `agent_id`) VALUES ('珠宝定制', '4', '54', '0', '/Home/Goods/goodsCategory/gcid/54.html', '1', '4', '0', '$t_agent_id');
							INSERT INTO `zmfx_db`.`zm_nav` (`nav_name`, `nav_type`, `category_id`, `function_id`, `nav_url`,`status`, `sort`, `pid`, `agent_id`) VALUES ('行业信息', '2', '0', '3', '/Home/Article/index.html', '1', '4', '0', '$t_agent_id');
				");
				copy("/Public/default/html/3.html","./Application/Home/View/default/Ad/ad_3_$t_agent_id.html");
				copy("/Public/default/html/4.html","./Application/Home/View/default/Ad/ad_4_$t_agent_id.html");
				copy("/Public/default/html/6.html","./Application/Home/View/default/Ad/ad_6_$t_agent_id.html");
				$this->success('审核分销商成功，站点已经生成',U('Admin/Trader/traderList'));
    		}else{
    			$T->rollback();
    			$this->error('分销商审核失败！');
    		}
    	}else{
    		$this->info = $info;
    		//获取分销商等级数据
    		$TR = M('trader_rank');
    		$this->traderRankList = $TR->select();
			$C  = M('config');
			$configList = $C->field('config_key_name,config_key,config_value,config_type,config_option')->order('sort_id')->select();//取出所有的配置项
    		//把分销商默认配置取出来
    		foreach ($configList AS $key=>$val){
    			if($val['config_type'] == 2 or $val['config_type'] == 3){
    				$val['config_option'] = unserialize($val['config_option']);
    			}
    			if($val['config_type'] == 3){
    				$val['config_value'] = explode(',', $val['config_value']);
    			}
    			$array[] = $val;
    		}
    		$this->configList = $array;
    		//获取分销商可用模板
    		$TPL = M('template');
    		$template = $TPL->where('status = 1')->select();
    		foreach ($template as $key => $value) {
    		    if($value['template_type'] == 1){
    		        $templateList[1][] = $value;
    		    }elseif ($value['template_type'] == 2){
    		        $templateList[2][] = $value;
    		    }
    		}
    		$this->templateList = $templateList;
    		$this->display();
    	}
    }
    
    // 分销商配置
    public function traderSet($tid){
		$this->_isZifenxiaoshang();
    	if(IS_POST){
			if($tid){
				$info = M('trader')->where(' tid = '.$tid.' and agent_id = '.C('agent_id'))->find();
				if(empty($info)){
					$this->success('修改配置成功!');
				}
			}
			$C  = M('config');
			$CV = M('config_value');
    		$configList = $C -> field('config_key')->order('sort_id')->select();
            //配置相关的图片上传
            $UP           = new Upload();
            $UP->maxSize  = 3145728 ;
            $UP->exts     = array('jpg', 'gif', 'png', 'jpeg');
            $UP->savePath = './Uploads/Config/'.$info['t_agent_id'].'/';
            foreach ($configList AS $k=>$v){
            	$val = htmlspecialchars($_POST[$v['config_key']]);
                if(is_array($val)){
					$val = implode(',',$val);
                }else{
                	//前后端图片上传
                	if($v['config_key'] == 'web_logo' or $v['config_key'] == 'admin_logo'){
                		$upInfo = $UP->uploadOne($_FILES[$v['config_key']]);
                		if($upInfo){$val = $upInfo['savepath'].$upInfo['savename'];}
                	}
                }
                $config_key = $v['config_key'];
				$_cv  = $CV->where("config_key = '$config_key'and agent_id = ".$info['t_agent_id']) ->select();
				if($_cv){
					$CV->where("config_key = '$config_key' and agent_id = ".$info['t_agent_id'])->setField('config_value',$val);
				}else{
					$v                 = array();
					$v['parent_type']  = $this->level+1;
					$v['parent_id']    = $tid;
					$v['config_key']   = $config_key;
					$v['config_value'] = $val;
					$v['agent_id']     = $info['t_agent_id'];
					$CV->add($v);
				}
            }
            $this->success('修改配置成功!');
    	}else{
			//取出所有的项
			if($tid){
				$info = M('trader')->where(' tid = '.$tid.' and agent_id = '.C('agent_id'))->find();
				if(empty($info)){
					$this->success('错误id!');
				}
			}
			$C          = M('config');
			$configList = $C->order('sort_id')->select();//取出所有的配置项
			$CV         = M('config_value');
			$configValueList = $CV->field('config_key,config_value')->where('agent_id = '.$info['t_agent_id'])->select();//取出所有的配置项
			foreach($configList as &$c1){
				foreach($configValueList as $c2){
					if( $c1['config_key'] == $c2['config_key'] ){
						$c1['config_value'] = $c2['config_value'];
					}
				}
			}
    		foreach ($configList AS $key=>$val){
    			if($val['config_type'] == 2 or $val['config_type'] == 3){
                    $val['config_option'] = unserialize($val['config_option']);   
                }
    			if($val['config_type'] == 3){
    				$val['config_value'] = explode(',', $val['config_value']);
    			}
    			$array[] = $val;
    		}
    		$this->configList = $array;
    		$this->display();
    	}
    }
    
    //删除未审核的分销商
    public function traderDel($tid){
		$this->_isZifenxiaoshang();
    	$T = M('trader');
    	$info = $T->find($tid);
    	if($info){
    		if($info['status'] == 0){
    			if($T->where('tid = '.$tid.' and agent_id = '.C('agent_id'))->delete()){
    				$this->success('删除分销商成功');
    			}else{
    				$this->error('删除分销商出错！');
    			}
    		}else{
    			$this->error('已经审核开通的分销商不可以删除！');
    		}
    	}else{
    		$this->error('分销商ID错误！');
    	}
    }

    // 分销商等级列表
    public function traderRankList(){
		$this->_isZifenxiaoshang();
    	$TR = M('trader_rank');
    	$list = $TR->select();
    	$this->list = $list;
        $this->display();
    }

    // 添加或编辑分销商等级
    public function traderRankInfo($trid){
		$this->_isZifenxiaoshang();
    	$TR = M('trader_rank');
    	if(IS_POST){
    		if($TR->create() and $TR->where('trid = '.I('get.trid'))->save()){
    			$this->success('修改分销商等级成功!',U('Admin/Trader/traderRankList'));
    		}else{
    			$this->error('数据出错');
    		}
    	}else{
    		$this->info = $TR->find($trid);
    		$this->display();
    	}
    }
	
}