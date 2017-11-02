<?php
/**
 * 产品模型类
 */
namespace Common\Model;
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
        $page               = new \Think\AjaxPage($this->count,$per,"setPage");
        $this->page         = $page;
        $this->sql['limit'] = $page->firstRow.','.$page->listRows;
        return $this;
    }
    
	public function getUserGoodsCategoryList(){
		$this -> sql['where'][] = " agent_id = ".C('agent_id');
        
		$productM = M('goods_category_config');
        if($this -> sql['where']) {
            $where = implode(' and ',$this->sql['where']);
        }else{
            $where = '1=1';
        }
        if(empty($this->sql['limit'])){
            $this->_limit();
        }
        if(empty($this->sql['order'])){
            $this->_order('sort_id','ASC');
        }
        $data  = $productM->where($where)->limit($this->sql['limit'])->order($this->sql['order'])->select();
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
		if($category_list){
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

		return $category_list;

    }
	/**
	 * auth	：fangkai
	 * content：获取单个分类信息
	 * time	：2016-9-7
	**/
    public function getCategoryInfo($where='1=1',$field="*") {
		$categoryInfo = $this->field($field)->where($where)->select();
		return $categoryInfo;
	}
	
	/**
	*	获取当前客户成品产品分类
	*	zhy
	*	2016年10月21日 14:19:38
	*/
	public function this_product_category( $cid = 0){
		$GC 								= 	M('goods_category');
		$where 								= 	' gcc.agent_id = '.C("agent_id");
		$join[]	 						    = 	' left join zm_goods_category_config AS gcc ON gcc.category_id = gc.category_id ';
		$where							   .=   ' and gcc.is_show = 1';
        if(intval($cid)>0){
			$where 				   		   .= 	' and gc.pid  = '.$cid;
        }
		$list 								= 	$GC-> alias('gc')->join($join)->where($where)->order('sort_id asc')->getField('gc.category_id,gc.img as cimg ,category_name,gc.pid,gcc.agent_id,gcc.img,category_config_id,name_alias,sort_id,parent_type,parent_id,is_show');
		if($list){
            foreach($list as $k=>$row){
                if(empty($list[$k]['name_alias'])){
                    $list[$k]['name_alias'] = $list[$k]['category_name'];
                }
            }
			$list							=	_arrayRecursive($list, 'category_id', 'pid', intval($cid) ,'1');	
        }else{
			$list							=	null;
		}
		return $list;
	}

    /**
	*	获取当前客户成品产品分类
	*	wsl
	*	2016年10月21日 14:19:38
	*/
	public function this_product_category2( $cid = 0 ){
		$GC 								= 	M('goods_category');
		$where 								= 	' gcc.agent_id = '.C("agent_id");
        if(intval($cid)>0){
			$join[]	 						= 	" zm_goods_category_config AS gcc ON gcc.category_id = gc.category_id and (gc.pid  = $cid or gc.category_id = $cid) and gcc.is_show = 1 ";
        }else{
			$join[]	 						= 	' zm_goods_category_config AS gcc ON gcc.category_id = gc.category_id and gcc.is_show = 1 ';
		}
		$list 								= 	$GC-> alias('gc')->join($join)->where($where)->order('sort_id asc')->getField('gc.category_id,gc.img as cimg ,category_name,gc.pid,gcc.agent_id,gcc.img,category_config_id,name_alias,sort_id,parent_type,parent_id,is_show');     
	    if($list){
            foreach($list as $k=>$row){
                if(empty($list[$k]['name_alias'])){
                    $list[$k]['name_alias'] = $list[$k]['category_name'];
                }
                if($row['img']){
                    $list[$k]['cimg'] = 'http://'.C('agent')['domain'].'/Public/'.$row['img'];
                }else{
                    if($row['cimg']){
                        $list[$k]['cimg'] = 'http://'.C('agent')['domain'].'/Public/'.$row['cimg'];
                    }
                }
            }
			$list							=	_arrayRecursive($list, 'category_id', 'pid', 0 ,'1');	
        }else{
			$list							=	null;
		} 			
		return $list;
	}

    /**
     * @param int $my_id            最底层的Id
     * @param array $param          设置数据库相关
     * @param int $limit_from       用于限制下级 如：当传-2的时候则少2个数组
     * @param int $limit_to         用于限制上级
     * @param array $arr
     * @return array                返回多个下拉的数组
     */
    public function getCateGoryAllRank($my_id=0,$param,$limit_from=0,$limit_to=0,$arr=array()){
        $default_arr = array('id'=>0,'name'=>'请选择','pid'=>0);
        $id_string = $param['id'] ? $param['id'] : 'category_id';
        $name_string = $param['name'] ? $param['name'] : 'category_name';
        $pid_string = $param['pid'] ? $param['pid'] : 'pid';
        //假如数据没有rank字段则无法拿出他的等级
        //$rank_string = $param['rank'] ? $param['rank'] : 0;

        $field = "$id_string as id,$name_string as name,$pid_string as pid";
        $limit_from++;
        if($my_id<=$limit_to){
            //当首次进入页面的时候拿到一级下拉列表
            if(empty($arr)){
                $rank_arr = $this->field($field)->where("$pid_string='$my_id'")->select();
                $rank_arr = array_merge(array($default_arr),$rank_arr);
                $arrData = array(
                    array(
                        'lists'=>$rank_arr,
                        'count'=>count($rank_arr),
                        'rank'=>1
                    )
                );
            }else{
                $arrData = array_reverse($arr);

            }
            return $arrData;
        }else{
            //拿到同一级别的下拉列表
            $search_arr = $this->field($field)->where("$id_string=$my_id")->find();
            if(!empty($search_arr)){
                //找出同级的数据
                $rank_arr = $this->field($field)->where("$pid_string=$search_arr[$pid_string]")->select();
                $rank_arr = array_merge(array($default_arr),$rank_arr);
                if($limit_from>0){
                    $arr[]= array(
                        'lists'=>$rank_arr,
                        'count'=>count($rank_arr),
                        'rank'=>$search_arr['pid']>0 ? 2 : 1,
                        'checked'=>$search_arr
                    );
                }
                return $this->getCateGoryAllRank($search_arr[$pid_string],$param,$limit_from,$limit_to,$arr);
            }
        }
    }

    public function getGateGoryInfo($my_id,$param,$arr=array()){
        $id_string = $param['id'] ? $param['id'] : 'category_id';
        $name_string = $param['name'] ? $param['name'] : 'category_name';
        $pid_string = $param['pid'] ? $param['pid'] : 'pid';
        //假如数据没有rank字段则无法拿出他的等级
        //$rank_string = $param['rank'] ? $param['rank'] : 0;

        $field = "$id_string as id,$name_string as name,$pid_string as pid";
        if($my_id>0){
            $search_arr = $this->field($field)->where("$id_string=$my_id")->find();
            if(!empty($search_arr)){
                $arr[] = $search_arr;
                return $this->getGateGoryInfo($search_arr[$pid_string],$param,$arr);
            }
        }else{
            $arr = array_reverse($arr);
        }

        return $arr;
    }

    public function getGateGoryName($my_id,$string='&nbsp;&nbsp;'){
        $lists = $this->getGateGoryInfo($my_id);
        $name = '';
        foreach($lists as $value){
            $name .= $value['name'].$string;
        }
        return $name;
    }
}
?>
