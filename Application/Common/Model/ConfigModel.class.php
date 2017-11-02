<?php
/**
 * 获取配置
 * User: 王松林
 * Date: 2016/4/23 0023
 * Time: 17:26
 */
namespace Common\Model;
Class ConfigModel extends \Think\Model{

    Protected $autoCheckFields = false;
    public function getZmConfig(){
        return M('config','zm_','ZMALL_DB')->where(' parent_type = 0 ')->field('config_key_name,config_key,config_value')->select();
    }
    public function getOneZmConfigValue($key){
        $obj   = M('config','zm_','ZMALL_DB');
        $value = $obj -> where(' parent_type = 0 and config_key = "'.$key.'"')->getField('config_value');
        return $value;
    }

}