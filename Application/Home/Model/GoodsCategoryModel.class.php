<?php
/**
 * 产品模型类
 */
namespace Home\Model;
use Think\Model;
class GoodsCategoryModel extends Model{

    private $page;
    private $sql = array(
        'where'=>null,
        'order'=>'',
        'limit'=>'',
        'join' =>array(),
    );

	//构造函数
    public function __construct(){
		parent::__construct();
    }

	public function category_id($category_id){
        $this -> sql['where']['category_id'] = " category_id in ($category_id) ";
        return $this;
    }

	public function parent_category_id($category_id){
        $this -> sql['where']['pid'] = " pid in ($category_id) ";
        return $this;
    }

	public function get_count(){
        $productM  = M('goods_category_config');
        if($this  -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1 = 1';
        }
        $this -> count = $productM -> where($where) -> count();
        return $this -> count;
    }

	public function _order($order,$desc){
        $this -> sql['order'] = "$order $desc";
        return $this;
    }

	public function _limit($per=20){
        if( empty( $this -> count ) ){
            $this -> get_count();
        }
        $page                 = new \Think\AjaxPage($this->count,$per,"setPage");
        $this -> page         = $page;
        $this -> sql['limit'] = $page->firstRow.','.$page->listRows;
        return $this;
    }

	public function getUserGoodsCategoryList(){

		$this     -> sql['where'][] = " agent_id = ".C('agent_id');
        $this     -> sql['where'][] = " is_show = 1 ";
		$productM  = M('goods_category_config');
        if($this  -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        if(empty($this->sql['limit'])){
            $this -> _limit();
        }
        if(empty($this->sql['order'])){
            $this -> _order('sort_id','ASC');
        }
        $data  = $productM -> where($where) -> limit($this->sql['limit']) -> order($this->sql['order']) -> select();
        $this -> sql['where'] = array();
        return $data;
    }

	public function getInfo(){

    	$this -> sql['where'][] = " agent_id = ".C('agent_id');

		$productM = M('goods_category_config');
        if($this -> sql['where']) {
            $where = implode(' and ',$this -> sql['where']);
        }else{
            $where = '1=1';
        }
		$data  = $productM -> where($where) -> find();
        $this -> sql['where'] = array();
        return $data;
    }

	public function getChildUserGoodsCategoryList(){

		$this -> sql['where'][] = " agent_id = ".C('agent_id');

		$productM  = M('goods_category_config');
        if($this  -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        } else {
            $where = ' 1 = 1 ';
        }
        $data                 = $productM -> where($where) -> getField('category_id',true);
        $this -> sql['where'] = array();
        return $data;
    }

	/**
	 * auth	：fangkai
	 * content：获取分类
	 * time	：2016-7-1
	**/
    public function getCategoryList($where='1=1',$sort= 'category_id DESC',$field="*") {
		$category_list = $this->field($field)->where($where)->order($sort)->select();
		if( $category_list ){
			return $category_list;
		}else{
			return false;
		}
    }
	/**
	 * auth	：fangkai
	 * content：连表查询获取分类，分类别名
	 * time	：2016-7-1
	**/
    public function getCategoryListTwo($field="*",$join,$where='1=1',$sort= 'cg.category_id DESC') {
		$category_list = $this
							->alias('cg')
							->field($field)
							->join($join)
							->where($where)
							->order($sort)
							->select();
		if($category_list){
			return $category_list;
		}else{
			return false;
		}
    }

}
?>
