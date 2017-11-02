<?php
/**
 * Created by PhpStorm.
 * User: Kwan Wong
 * Date: 2017/5/12
 * Time: 17:15
 */

namespace Common\Model;

class AgentModel
{
    public $error;
    private $agent;

    public function __construct()
    {
        $this->agent = array();
    }

    /**
     * 初始化分销商信息
     *
     * @author Kwan Wong
     * @return bool 初始化是否成功
     */
    public function init()
    {
        if(!C('agent_id')){
            $this->error = "初始化分销商信息失败，agent_id为零或空";
            return false;
        }

        $this->agent = M("agent")->where(array('agent_id' => C('agent_id')))->find();

        if(!$this->agent){
            $this->error = "初始化分销商信息失败，分销商不存在";
            return false;
        }

        if(strlen($this->agent['ips_account']) != 10 || strlen($this->agent['ips_mer_code']) != 6 || $this->agent['ips_mer_cert'] == ''){
            $this->error = "初始化分销商信息失败，分销商未正确开通环迅支付";
            return false;
        }

        $domain = $this->agent['domain'];
        //如果客户域名为主域名，则添加www
        if(substr_count($domain, '.') == 1){
            $this->agent['domain'] = $domain;
        }

        return true;
    }

    /**
     * 获取分销商信息
     *
     * @author Kwan Wong
     * @return array
     */
    public function getAgent()
    {
        return $this->agent;
    }
}