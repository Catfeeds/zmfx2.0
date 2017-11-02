<?php
/**
 * Created by PhpStorm.
 * User: 王松林
 * Date: 2015/10/8 0008
 * Time: 上午 11:42
 */
namespace Supply\Model;
use Think\Model;
class MessageModel extends Model{

    protected $autoCheckFields = false;
    public function sendUserVerifyOfEmail($uid,$email){
        $html                = '请点击一下链接进行验证。';
        $code                = substr(time(),-6);
        $data                = array();
        $data['email']       = base64_encode($email);
        $data['verify_code'] = base64_encode($code);
        $code_str            = base64_encode(json_encode($data));
        $verify_expire_time  = time() + 600;
        D('SupplyUser')      -> where("status = 0.and uid = $uid") -> save(array('verify_code'=>$code,'verify_expire_time'=>$verify_expire_time));
        $url                 = U('Supply/Index/verifyEmail',"code=$code_str",true,true);
        $a                   = "<a href='$url'>$url</a>";
        $html                = $html.$a;
        return D('Mail') -> sendMail($email,$html,'您的绑定的Email需要激活!');

    }
    public function sendVerifyCodeOfEmail($email){
        $html               = '您的验证码是:';
        $code               = substr(time(),-6);
        $verify_expire_time = time() + 600;
        D('SupplyUser')     -> where(" username = '$email' and status >= 0 ") -> save(array('verify_code'=>$code,'verify_expire_time'=>$verify_expire_time));
        $html               = $html.$code;
        return D('Mail') -> sendMail($email,$html,'供应宝验证码!');
    }
}