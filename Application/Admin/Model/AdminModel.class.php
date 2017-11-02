<?php
/**
 * 公共模型类
 */
namespace Admin\Model;
use Think\Model;
class AdminModel extends Model{
    
    // 获取当前用户的ID
    public function getMemberId() {
        return isset($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0;
    }
}