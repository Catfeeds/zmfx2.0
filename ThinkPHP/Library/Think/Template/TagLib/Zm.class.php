<?php
/**
 * 钻明自定义标签
 * @author adcbguo
 */
namespace Think\Template\TagLib;
use Think\Template\TagLib;
class Zm extends TagLib {
	
	protected $tags = array(
		'ad' => array('attr'=>'id','close'=>1),//钻明广告标签
	);
	
	/**
	 * 广告标签解析
	 * @param unknown $tag
	 * @param unknown $content
	 * @return string
	 */
	public function _ad($tag,$content){
		$id = $tag['id'];
		//获取广告数据
		$TAS = M('template_ads');
		$TA = M('template_ad');
		//广告位数据
		$info = $TAS->find($id);
		//广告列表数据
		
	}
}