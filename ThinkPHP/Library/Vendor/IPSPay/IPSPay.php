<?php
/**
 * Created by PhpStorm.
 * User: Kwan Wong
 * Date: 2017/5/8
 * Time: 10:04
 */

namespace Vendor\IPSPay;

class IPSPay
{
    /**
     * @var string|optional 版本号
     */
    private $version = 'v1.0.0';

    /**
     * 商户号，IPS 给商户分配的唯一标识号
     * @var string|required 商户号
     */
    private $merCode = '';
    //183143

    /**
     * @var string|optional 商户名
     */
    private $merName = '';
    //钻明钻石有限公司

    /**
     * @var string|required 商户证书
     */
    private $merCert = '';
    //JF6Nct5CsQzUy2wzNCDcfa3QjGUK1YCZEsTpObrEJbV12XhxPQeYMDvMPp0ZCcnfO6ZjuEjNVwWxfyHUpDBht0esTHWWrAaAMP9L8ynqsU9wqKyqDvQ70Pg3UD9JWc2G

    /**
     * 10位长度交易账户号
     * @var string|required 账户号
     */
    private $account = '';
    //1831430010

    /**
     * 消息唯一标示，交易必输，查询可选
     * @var string|required 消息编号
     */
    private $msgId = '';

    /**
     * date("Ymdhis")20位
     * @var string|required 商户请求时间
     */
    private $reqDate = '';

    /**
     * @var string 支付通道URL
     */
    private $payUrl = 'https://newpay.ips.com.cn/psfp-entry/gateway/payment.do';

    /**
     * @var string|required 商户订单号
     */
    private $merBillNo = '';

    /**
     * 01# 借记卡(默认)
     * 02# 信用卡
     * 03# IPS账户支付
     * @var string|required 支付方式
     */
    private $gatewayType = '01';

    /**
     * 规则: yyyyMMdd(20170101)
     * @var string|required 订单日期
     */
    private $date = '';

    /**
     * 156#人民币(默认156)
     * @var string|required 币种
     */
    private $currencyType = '156';

    /**
     * 保留2位小数
     * @var float|required 订单金额
     */
    private $amount = '0.00';

    /**
     * GB#中文
     * @var string|optional 语言
     */
    private $lang = 'GB';

    /**
     * 规则：动态的网页，在该页对IPS 返回信息进行签名验证后处理商户端的数据库
     * 用户不传值得话，默认使用 IPS 支付成功的页面
     * @var string|required 支付结果成功返回的商户URL
     */
    private $merchantUrl = '';

    /**
     * @var string|optional 支付结果失败返回的商户URL
     */
    private $failUrl = '';

    /**
     * 存放商户自己的信息，随订单传送到 IPS 平台，当订单返回的时候原封不动的返回给商户，由“数字、字母戒数字+字母”组成
     * @var string|optional 商户数据包
     */
    private $attach = '';

    /**
     * 默认为5# 采用md5摘要认证方式
     * @var int|required 订单支付接口加密方式
     */
    private $orderEncodeType = 5;

    /**
     * 说明：存放商户所选择的交易返回接口加密方式
     * 16# 交易返回采用 Md5WithRsa 的签名认证方式
     * 17# 交易返回采用 Md5 的摘要认证方式
     * @var int|required 交易返回接口加密方式
     */
    private $retEncodeType = 17;

    /**
     * 0# Browser返回(默认)
     * 1# Server to Server
     * @var int|required 返回方式
     */
    private $retType = 1;

    /**
     * 商户使用异步方式返回时可将返回地址存于此字段
     * 当 RetType#1 时,本字段有效
     * @var string 异步S2S返回
     */
    private $serverUrl = '';

    /**
     * 订单有效期(以小时计算，必须是整数)
     * 过了订单的有效时间 IPS 没处理完，订单将自动过期做失败处理
     * @var int|optional length|10 订单有效期
     */
    private $billExp = 1;

    /**
     * 用户购买的商品的名称
     * @var string|required length|40 商品名称
     */
    private $goodsName = '';

    /**
     * 0# 非直连
     * 1# 直连
     * @var int|optional 直连选项
     */
    private $isCredit = 0;

    /**
     * IPS 唯一标识指定支付银行的编号(直连必填)
     * 1100# 工商银行
     * 1101# 农业银行
     * 1102# 招商银行
     * 1103# 兴业银行
     * 1104# 中信银行
     * 1106# 建设银行
     * 1107# 中国银行
     * 1108# 交通银行
     * 1109# 浦发银行
     * 1110# 民生银行
     * 1111# 华夏银行
     * 1112# 光大银行
     * 1113# 北京银行
     * 1114# 广发银行
     * 1115# 南京银行
     * 1116# 上海银行
     * 1117# 杭州银行
     * 1118# 宁波银行
     * 1119# 邮政储蓄银行
     * 1120# 浙商银行
     * 1121# 平安银行
     * 1122# 东亚银行
     * 1123# 渤海银行
     * 1124# 北京农商行
     * 1127# 浙江泰隆商业银行
     * @var string|optional length|5 银行号
     */
    private $bankCode = '';

    /**
     * 产品类型(直连必填)
     * 1# 个人网银
     * 2# 企业网银
     * @var int|optional 产品类型
     */
    private $productType = 1;

    /**
     * IPSPay constructor.
     *
     * @author wangkun
     * @param $merCode
     * @param $merCert
     * @param $account
     * @param string $merName
     */
    public function __construct($merCode, $merCert, $account, $merName='')
    {
        $this->merCode = $merCode;
        $this->merCert = $merCert;
        $this->account = $account;
        if(!empty($merName)){
            $this->merName = $merName;
        }
    }

    /**
     * 设置支付提交需要的参数
     *
     * @author wangkun
     * @param array $data
     */
    public function setParams($data = array())
    {
        if(!empty($data['merBillNo'])){
            $this->merBillNo = $data['merBillNo'];
        }
        if(!empty($data['amount']) && $data['amount'] > 0){
            $this->amount = round($data['amount'], 2);
        }
        if(!empty($data['goodsName'])){
            $this->goodsName = strlen($data['goodsName']) >= 40 ? substr($data['goodsName'], 0, 40) : $data['goodsName'];
        }
        if(!empty($data['merchantUrl'])){
            $this->merchantUrl = $data['merchantUrl'];
        }
        if(!empty($data['serverUrl'])){
            $this->serverUrl = $data['serverUrl'];
        }
        if(!empty($data['date'])){
            $this->date = $data['date'];
        }

        return this;
    }

    /**
     * 输出支付表单到浏览器，提交到IPS服务器
     *
     * @author wangkun
     */
    public function submit()
    {
        $this->reqDate = date("Ymdhis");
  
        echo $this->buildHtml();
    }

    /**
     * 根据支付结果解析响应码，校验签名
     *
     * @author Kwan Wong
     * @param $paymentResult 支付结果参数
     * @return array
     */
    public function handleResponse($paymentResult)
    {
        if(!empty($paymentResult)){

            $rspXml = simplexml_load_string($paymentResult, 'SimpleXMLElement', LIBXML_NOCDATA);

            // 响应码
            $rspCode = $this->parseXmlElement($rspXml, 'GateWayRsp/head/RspCode');
            // 响应说明
            $rspMsg = $this->parseXmlElement($rspXml, 'GateWayRsp/head/RspMsg');
            // 数字签名
            $signature = $this->parseXmlElement($rspXml, 'GateWayRsp/head/Signature');
            // 商户订单号
            $merBillNo = $this->parseXmlElement($rspXml, 'GateWayRsp/body/MerBillNo');
            // 币种
            $currencyType = $this->parseXmlElement($rspXml, 'GateWayRsp/body/CurrencyType');
            // 订单金额
            $amount = $this->parseXmlElement($rspXml, 'GateWayRsp/body/Amount');
            // 订单日期
            $date = $this->parseXmlElement($rspXml, 'GateWayRsp/body/Date');
            // 交易状态
            $status = $this->parseXmlElement($rspXml, 'GateWayRsp/body/Status');
            // 发卡行返回信息
            $msg = $this->parseXmlElement($rspXml, 'GateWayRsp/body/Msg');
            // 数据包
            $attach = $this->parseXmlElement($rspXml, 'GateWayRsp/body/Attach');
            // IPS订单号
            $ipsBillNo = $this->parseXmlElement($rspXml, 'GateWayRsp/body/IpsBillNo');
            // IPS交易流水号
            $ipsTradeNo = $this->parseXmlElement($rspXml, 'GateWayRsp/body/IpsTradeNo');
            // 交易返回方式
            $retEncodeType = $this->parseXmlElement($rspXml, 'GateWayRsp/body/RetEncodeType');
            // 银行订单号
            $bankBillNo = $this->parseXmlElement($rspXml, 'GateWayRsp/body/BankBillNo');
            // 支付返回方式
            $resultType = $this->parseXmlElement($rspXml, 'GateWayRsp/body/ResultType');
            // IPS处理时间
            $ipsBillTime = $this->parseXmlElement($rspXml, 'GateWayRsp/body/IpsBillTime');

            $rspBodyXML  = '<body>';
            $rspBodyXML .= '<MerBillNo>'.$merBillNo.'</MerBillNo>';
            $rspBodyXML .= '<CurrencyType>'.$currencyType.'</CurrencyType>';
            $rspBodyXML .= '<Amount>'.$amount.'</Amount>';
            $rspBodyXML .= '<Date>'.$date.'</Date>';
            $rspBodyXML .= '<Status>'.$status.'</Status>';
            $rspBodyXML .= '<Msg><![CDATA['.$msg.']]></Msg>';
            if(!empty($attach)){
                $rspBodyXML .= '<Attach><![CDATA['.$attach.']]></Attach>';
            }
            $rspBodyXML .= '<IpsBillNo>'.$ipsBillNo.'</IpsBillNo>';
            $rspBodyXML .= '<IpsTradeNo>'.$ipsTradeNo.'</IpsTradeNo>';
            $rspBodyXML .= '<RetEncodeType>'.$retEncodeType.'</RetEncodeType>';
            $rspBodyXML .= '<BankBillNo>'.$bankBillNo.'</BankBillNo>';
            $rspBodyXML .= '<ResultType>'.$resultType.'</ResultType>';
            $rspBodyXML .= '<IpsBillTime>'.$ipsBillTime.'</IpsBillTime>';
            $rspBodyXML .= '</body>';

            $sign = strtolower(md5($rspBodyXML.$this->merCode.$this->merCert));

            // 签名验证通过
            if($sign == $signature){
                // 状态码成功
                if($rspCode == '000000'){
                    return array(
                        'status' => 'success',
                        'msg'    => $rspMsg,
                        'data'   => array(
                            'merBillNo' => $merBillNo->__toString(),
                            'ipsBillNo' => $ipsBillNo->__toString(),
                            'ipsTradeNo' => $ipsTradeNo->__toString(),
                            'amount'    => $amount->__toString()
                        )
                    );
                }else{
                    return array(
                        'status' => 'error',
                        'msg'    => $rspMsg
                    );
                }
            }else{
                return array(
                    'status' => 'error',
                    'msg'    => '无效的签名'
                );
            }
        }

        return array(
            'status' => 'error',
            'msg'    => '非法交易'
        );
    }

    /**
     * 解析XML元素
     *
     * @author Kwan Wong
     * @param $objXml xml对象
     * @param $xpath  标签路径
     * @return string 标签值
     */
    private function parseXmlElement($objXml, $xpath)
    {
        $values = $objXml->xpath($xpath);
        return !empty($values) ? $values[0] : '';
    }

    /**
     * 组装IPS支付表单
     *
     * @author Kwan Wong
     * @return string 组装后的Form
     */
    public function buildHtml()
    {
        $submitHtml = '';

        $requestBodyXML  = '<body>';
        $requestBodyXML .= '<MerBillNo>'.$this->merBillNo.'</MerBillNo>';
        $requestBodyXML .= '<Amount>'.$this->amount.'</Amount>';
        $requestBodyXML .= '<Date>'.$this->date.'</Date>';
        $requestBodyXML .= '<CurrencyType>'.$this->currencyType.'</CurrencyType>';
        $requestBodyXML .= '<GatewayType>'.$this->gatewayType.'</GatewayType>';
        $requestBodyXML .= '<Lang>'.$this->lang.'</Lang>';
        $requestBodyXML .= '<Merchanturl>'.$this->merchantUrl.'</Merchanturl>';
        $requestBodyXML .= '<FailUrl>'.$this->failUrl.'</FailUrl>';
        $requestBodyXML .= '<Attach>'.$this->attach.'</Attach>';
        $requestBodyXML .= '<OrderEncodeType>'.$this->orderEncodeType.'</OrderEncodeType>';
        $requestBodyXML .= '<RetEncodeType>'.$this->retEncodeType.'</RetEncodeType>';
        $requestBodyXML .= '<RetType>'.$this->retType.'</RetType>';
        $requestBodyXML .= '<ServerUrl>'.$this->serverUrl.'</ServerUrl>';
        $requestBodyXML .= '<BillEXP>'.$this->billExp.'</BillEXP>';
        $requestBodyXML .= '<GoodsName>'.$this->goodsName.'</GoodsName>';
        $requestBodyXML .= '<IsCredit>'.$this->isCredit.'</IsCredit>';
        $requestBodyXML .= '<BankCode>'.$this->bankCode.'</BankCode>';
        $requestBodyXML .= '<ProductType>'.$this->productType.'</ProductType>';
        $requestBodyXML .= '</body>';

        // 数字签名
        $signature = md5($requestBodyXML.$this->merCode.$this->merCert);

        $requestHeadXML  = '<head>';
        $requestHeadXML .= '<Version>'.$this->version.'</Version>';
        $requestHeadXML .= '<MerCode>'.$this->merCode.'</MerCode>';
        $requestHeadXML .= '<MerName>'.$this->merName.'</MerName>';
        $requestHeadXML .= '<Account>'.$this->account.'</Account>';
        $requestHeadXML .= '<MsgId>'.$this->msgId.'</MsgId>';
        $requestHeadXML .= '<ReqDate>'.$this->reqDate.'</ReqDate>';
        $requestHeadXML .= '<Signature>'.$signature.'</Signature>';
        $requestHeadXML .= '</head>';

        $submitXML  = '<Ips>';
        $submitXML .= '<GateWayReq>';
        $submitXML .= $requestHeadXML;
        $submitXML .= $requestBodyXML;
        $submitXML .= '</GateWayReq>';
        $submitXML .= '</Ips>';

        $submitHtml .= '<form name="IPSPayForm" id="IPSPayForm" method="post" action="'.$this->payUrl.'" target="_self">';
        $submitHtml .= '<input type="hidden" name="pGateWayReq" value="'.$submitXML.'" />';
        $submitHtml .= '</form>';
        $submitHtml .= '<script language="javascript">document.IPSPayForm.submit();</script>';
        
        return $submitHtml;
    }
}