<?php
/**
 * Created by PhpStorm.
 * User: Sunyang
 * Date: 2017/4/20
 * Time: 12:28
 * 公共方法
 */
namespace Org\Util;
class QQLogin{
    //微信接口的参数
    public $AppID;
    protected $_AppKey;
    public $redirect_uri;
    public $response_type;
    public $scope;
    public $state;
    public $request_url;
    public $grant_type;

    //自定义参数
    public $salt;

    public function __construct($appid,$appkey){
        //设置一些可能用到的参数
        $this->AppID     = $appid;
        $this->_AppKey = $appkey;
        $this->redirect_uri  = 'http://'.$_SERVER['HTTP_HOST'].'/Home/Public/qqlogin.html';

        $this->response_type = 'code';
        $this->scope         = 'do_like';
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

    //获取用户授权地址
    public function get_login_url(){
        //拼凑请求地址
        $this->request_url   = 'https://graph.qq.com/oauth2.0/authorize';
        $this->request_url .= '?client_id=' . $this->AppID . '&redirect_uri=' . urlencode($this->redirect_uri) . '&response_type=' . $this->response_type;
        $this->request_url .= '&scope=' . $this->scope . '&state=' . $this->state;
        return $this->request_url;
    }

    public function get_access_token($code){
        $params = array(
            'grant_type'=>$this->grant_type,
            'client_id'      => $this->AppID,
            'client_secret'     => $this->_AppKey,
            'code'       => $code,
            'redirect_uri'=>$this->redirect_uri
        );
        $conditions = array(
            'url'=>'https://graph.qq.com/oauth2.0/token',
            'data'=>$params,
            'not_json'=>1
        );
        $return = $this->httpsRequest($conditions);        
        $sub_start = strpos($return,'access_token=');
        $sub_end = strpos($return,'&expires_in');
        $need_sting = substr($return,$sub_start,$sub_end-$sub_start);
        $result = array(
            'status'=>0,
            'msg'=>'获取失败'
        );
        if(!empty($need_sting)){
            $need_arr = explode('=',$need_sting);
            $result = array(
                'status'=>100,
                'msg'=>'获取成功',
                $need_arr[0]=>$need_arr[1]
            );
        }
        return $result;
    }

    public function get_user_openid($access_token){
        $params = array(
            'access_token'=>$access_token
        );
        $conditions = array(
            'url'=>'https://graph.qq.com/oauth2.0/me',
            'data'=>$params,
            'not_json'=>1
        );
        $return = $this->httpsRequest($conditions);
        $sub_start = strpos($return,'{');
        $sub_end = strpos($return,'}');
        $need_sting = substr($return,$sub_start,$sub_end-$sub_start+1);
        $result = array(
            'status'=>0,
            'msg'=>'获取失败'
        );
        $need_sting = $need_sting ? json_decode($need_sting,true) : array();       
        
        if(!empty($need_sting)){
            $result = array(
                'status'=>100,
                'msg'=>'获取成功',
                'openid'=>$need_sting['openid']
            );
        }
        return $result;
    }

    public function get_user_info($access_token,$openid){
        $result = array(
            'status'=>0,
            'msg'=>'获取数据失败'
        );
        $params = array(
            'access_token'=>$access_token,
            'oauth_consumer_key'=>$this->AppID,
            'openid'=>$openid
        );
        $conditions = array(
            'url'=>'https://graph.qq.com/user/get_user_info',
            'data'=>$params
        );
        $info = $this->httpsRequest($conditions);
        if(!empty($result) && $result['ret']==0){
            $result = array(
                'status'=>100,
                'msg'=>'获取数据成功',
                'info'=>$info
            );
        }
        return $result;
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
