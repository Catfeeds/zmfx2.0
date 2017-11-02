<?php
/**
 * 个性符号
 * User: 王松林
 * Date: 2016/10/10 0023
 * Time: 17:26
 */
namespace Common\Model;
Class SymbolDesignModel extends \Think\Model{

    public function getList($agent_id=0){
        $where                 = array();
        if($agent_id){
            $where['agent_id'] = $agent_id;
        }
        $list = $this -> where($where) -> select();
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/';
        foreach($list as $key=>$row){
            $list[$key]['images_path']   = str_replace('./',$url,$row['images_path']);
        }
        return $list;
    }

    public function getInfo( $sd_id = 0 ){
        $where               = array();
        if($sd_id){
            $where['sd_id']  = $sd_id;
        }
        $info                = $this -> where($where) -> find();
        $url                 = 'http://'.$_SERVER['HTTP_HOST'].'/';
        $info['images_path'] = str_replace('./',$url,$info['images_path']);        
        return $info;
    }
    
    public function addOne($file_info,$agent_id){
        $path     = $this -> upload($file_info);
        if(empty($path)){
            $code = false;
        }else{
            $info = array('agent_id'=>$agent_id,'images_path'=>$path);
            $code = $this -> data($info) -> add();
        }
        return $code;
    }
    public function delOne($sd_id,$agent_id=0){
        $where          = array();
        $where['sd_id'] = $sd_id;
        if($agent_id){
            $where['agent_id'] = $agent_id;
        }
        $info = $this -> where($where) -> find();
        $code = $this -> where($where) -> delete();
        if($code){
            unlink( $info['images_path'] );
        }
        return $code;
    }
    private function upload($file_info){
        $upload             = new \Think\Upload();
        $upload -> maxSize  = 3145728 ;
        $upload -> exts     = array('jpg', 'gif', 'png', 'jpeg');
        $upload -> savePath = './Uploads/product/';
        $info   = $upload   -> uploadOne($file_info);
        if( !$info ) { // 上传错误提示错误信息
            $result = null; /// $upload -> getError();
        } else {       // 上传成功
            $path   =  $info['savepath'].$info['savename'];
            $path   =  './Public'.substr( $path , 1 );
            $image  =  new \Think\Image();
            $image  -> open( $path );
            $ratio  =  number_format($image->width() / $image->height(), 1);
            if ( ($ratio >= 1) && ($ratio <= 1.3) ) {
                $image -> thumb(80, 80, 1) -> save($path);
                $result = $path ;
            } else {
                unlink( $path );
                $result = '';
            }
        }
        return $result;
    }
}