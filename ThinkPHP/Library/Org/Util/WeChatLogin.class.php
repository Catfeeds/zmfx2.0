<?php
/**
 * Created by PhpStorm.
 * User: Sunyang
 * Date: 2017/4/20
 * Time: 12:28
 * 公共方法
 */
namespace Org\Util;
class WeChatLogin{
    //微信接口的参数
    public $AppID;
    protected $_AppSecret;
    public $redirect_uri;
    public $response_type;
    public $scope;
    public $state;
    public $request_url;
    public $grant_type;

    //自定义参数
    public $salt;

    public function __construct($appid,$appsecret){
        $this->AppID     = $appid;
        $this->_AppSecret = $appsecret;
        $this->redirect_uri  = 'http://'.$_SERVER['HTTP_HOST'].'/Home/Public/wxlogin.html';
        $this->redirect_uri  = urlencode($this->redirect_uri);
        $this->response_type = 'code';
        $this->scope         = 'snsapi_login';
        $session_id           = session_id() ? session_id() : '';
        $this->state         = md5($this->salt . $session_id);
        $this->grant_type = 'authorization_code';
        $this->salt      = 'loginjiami';
    }
    //此方法用于传一些参数在回调中用
    public function set_state($state_arr){
        if(!empty($state_arr) && is_array($state_arr)){
            $this->state .= '@@@'.implode($state_arr,'@@@');
        }
    }
    public function get_param($param_set=array()){
        $param = array(
            'appid'=>$this->AppID,
            'redirect_uri'=>$this->redirect_uri,
            'response_type'=>$this->response_type,
            'scope'=>$this->scope,
            'state'=>$this->state,
            'request_url'=>$this->get_login_url(),
        );
        if(!empty($param_set) && is_array($param_set)){
            foreach($param_set as $key=>$value){
                $param[$key] = $value;
            }
        }
        return $param;
    }
    public function get_login_url(){
        //授权登录后得到code
        $this->request_url   = 'https://open.weixin.qq.com/connect/qrconnect';
        $this->request_url .= '?appid=' . $this->AppID . '&redirect_uri=' . $this->redirect_uri . '&response_type=' . $this->response_type;
        $this->request_url .= '&scope=' . $this->scope . '&state=' . $this->state . '#wechat_redirect';
        return $this->request_url;
    }

    public function get_access_token($code){
        $return = array(
            'status'=>0,
            'msg'=>'获取数据失败'
        );

        $params = array(
            'appid'      => $this->AppID,
            'secret'     => $this->_AppSecret,
            'code'       => $code,
            'grant_type' => $this->grant_type,
        );
        $conditions = array(
            'url'=>'https://api.weixin.qq.com/sns/oauth2/access_token',
            'data'=>$params
        );
        $result = $this->httpsRequest($conditions);
        if(!empty($result['unionid'])){
            $return = $result;
            $return['status'] = 100;
            $return['msg'] = '获取数据成功';
        }
        return $return;
    }

    public function get_user_info($access_token,$openid,$lang='zh-CN'){
        $return = array(
            'status'=>0,
            'msg'=>'获取数据失败'
        );
        $params = array(
            'access_token'=>$access_token,
            'openid'=>$openid,
            'lang'=>$lang
        );
        $conditions = array(
            'url'=>'https://api.weixin.qq.com/sns/userinfo',
            'data'=>$params
        );
        $result = $this->httpsRequest($conditions);
        if(!empty($result['unionid'])){
            $return = array(
                'status'=>100,
                'msg'=>'获取数据成功',
                'info'=>$result
            );
        }
        return $return;
    }

    protected function httpsRequest($param){
        $url = $param['url'];
        $data =$param['data'] ? $param['data'] : '';
        $not_json = $param['not_json'] ? 1 : 0;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        if($not_json==1){
            return $output;
        }
        return json_decode($output,true);

    }


}
?>
