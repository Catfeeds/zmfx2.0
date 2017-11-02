<?php
	/**
	* Array扩展方法
	* User: YangChao
	* Date: 2016/2/24
	**/

namespace Common\Model;

class CommArray {

	//构造函数
	public function __construct()
	{
		
	}
	/**
	   * 把数组里的字段值作为KEY存放
	   * @param array $array
	   * @param string $id 没有键名，第一个就是键名
	*/
	public static function _arrayIdToKey($array,$id=''){
		if(!$id){$ids = array_keys($array[0]);$id = $ids[0];}
		foreach ($array as $key => $value) {
			$arr[$value[$id]] = $value;
		}
		return $arr;
	}
  
	/**
	 * 把Key回复成从0开始
	 * @param array $array
	 */
	public static function _arrayAutoKey($array){
		foreach ($array as $key => $value) {
			$list[] = $value;
		}
		return $list;
	}

}
?>