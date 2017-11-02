<?php
/**
 * 后台模块设置类
 */
namespace Admin\Model;

class TemplateSeeting {
    
    public $error = '';
	
	/**
	 * auth	：fangkai
	 * @param：构造函数
	 * time	：2016-10-28
	**/
	public function __construct(){
		$this->agent_id = C('agent_id');
	}
	
	/**
	 * auth	：fangkai
	 * @param：获取要设置的模块列表
	 * time	：2016-10-28
	**/
	public function getPositionList($template_id=8){
		$template_config	= M('b2c_template_config');
		$postitionList	= $template_config
							->alias('tc')
							->join('left join zm_b2c_template_value as tv on tv.position_id = tc.id and tv.agent_id = '.$this->agent_id.' where tc.template_id = '.$template_id.'')
							->field('(case when tv.title is not null and tv.title <> "" then tv.title else tc.title end ) as title,(case when tv.english_title is not null and tv.english_title <> "" then tv.english_title else tc.english_title end ) as english_title,(case when tv.font is not null and tv.font <> "" then tv.font else tc.font end ) as font,(case when tv.is_show is not null and (tv.is_show <> "" OR tv.is_show = 0) then tv.is_show else tc.is_show end ) as is_show,(case when tv.position_id is not null and tv.position_id <> "" then tv.position_id else tc.id end ) as position_id,(case when tv.type is not null and tv.type <> "" then tv.type else tc.type end ) as type,(case when tv.content is not null and tv.content <> "" then tv.content else tc.content end ) as content')
							->select();
		return $postitionList;
	}
	
	/**
	 * auth	：fangkai
	 * @param：设置B2C首页楼层
	 * time	：2016-10-28
	**/
	public function setTemplateSetting($data){
		$checkPosition	= M('b2c_template_value')->where(array('position_id'=>$data['position_id'],'agent_id'=>$this->agent_id))->find();
		$defultPosition = M('b2c_template_config')->where(array('id'=>$data['position_id']))->find();
		if(mb_strlen($data['title']) > 50 ){
			$this->error= '中文标题不能超过50个字符'; 
			return false;
		}
		if(empty($data['title'])){
			$data['title']	= $defultPosition['title'];
		}
		if(mb_strlen($data['english_title']) > 50 ){
			$this->error= '英文标题不能超过50个字符'; 
			return false;
		}
		if(empty($data['english_title'])){
			$data['english_title']	= $defultPosition['english_title'];
		}
		
		//如果value表不存在楼层设置值，则取值config表作为默认值存进value表,如果存在则作修改
		if(empty($checkPosition)){
			$data['position_id']	= $defultPosition['id'];
			$data['type']			= $defultPosition['type']; 
			$data['agent_id']		= $this->agent_id;
			$action		= M('b2c_template_value')->add($data);
			if($action){
				return true;
			}else{
				$this->error	= '保存失败';
				return false;
			}
		}else{
			$action		= M('b2c_template_value')->where(array('position_id'=>$data['position_id'],'agent_id'=>$this->agent_id))->save($data);
			if($action){
				return true;
			}else{
				$this->error	= '保存失败';
				return false;
			}
		}
		
	}
	
	
	
	
	
	
	
	
	
	
	
}