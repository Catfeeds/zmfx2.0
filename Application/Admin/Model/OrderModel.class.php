<?php
/**
 * User: 张超豪
 * Date: 2016/7/18 
 */
namespace Admin\Model;
class OrderModel extends AdminModel{
    //Protected $autoCheckFields = false;
    
    public function sendEmail($order_id){
        if(C('MAIL_SMTP')=='' or C('MAIL_LOGINNAME')=='' or C('MAIL_PASSWORD')==''){
            return false;
        }
        $order_info  = $this->where('order_id = "'.$order_id.'" and agent_id = '.C('agent_id'))->find();
        $order_sn    = $order_info['order_sn'];
        $order_price = $order_info['order_price'];
        $goods_list  = M('order_goods')->where('order_id = "'.$order_id.'"')->select();
        $goods_html  = "";
        if(is_array($goods_list)){
            $goods_html = "<table width='400px' border='1' bordercolor='#000' cellspacing='0px' style='border-collapse:collapse'>
                        <tbody>
                        <tr>
                            <th>货号</th>
                            <th>价格</th>
                            <th>数量</th>
                        </tr>";
            foreach($goods_list as $goods_info){
                $goods_html .='<tr>';
                $goods_html .='<td>'.$goods_info['certificate_no'].'</td>';
                $goods_html .='<td>'.$goods_info['goods_price'].'</td>';
                $goods_html .='<td>'.$goods_info['goods_number'].'</td>';
                $goods_html .='</tr>';
            }
            $goods_html .='</tbody></table>';
        }
        
        $address_info = M('user_address')->where('address_id = "'.$order_info['address_id'].'"')->find();
        $name         = $address_info['name'];
        $_address     = $address_info['address'];
        $phone        = $address_info['phone'];
        
        $delivery_mode_info = M('delivery_mode')->where('mode_id = "'.$order_info['delivery_mode'].'"')->find();
        $delivery_mode = $delivery_mode_info['mode_name'].'('.$delivery_mode_info['mode_note'].')';

        $userInfo = M("user")->field("usernum")->where("uid=".(int)$order_info['uid'])->find();
        $usernum = $userInfo?$userInfo['usernum']:0;

        $html = '';
        $html = C('site_name')."订单提醒：
                    <div>
                        <br>&nbsp;
                        <div>客户编号:$usernum</div>
                        <div>订单号:$order_sn</div>
                        <div>订单金额:$order_price</div>
                        <div>订单中的商品:</div>
                        <br>
                    </div>
                    <div>
                    $goods_html
                    </div>
                    <br>
                    <div>收货人:$name</div>
                    <div>收货人地址:$_address</div>
                    <div>收货人电话:$phone</div>
                    <div>配送方式:$delivery_mode</div>
                    <div>付款方式:联系业务员沟通后确认</div>
                    <br><br>
                    <div><font color='#ff0000'>*</font>此邮件由".C('site_name')."<font color='#ff0000'>系统自动发出</font>，请勿回复！</div>
                    <div>如果此订单与您无关，请进入".C('site_name')."用户中心取消订单，或联系".C('site_name')."在线客服。</div>
                    </div>";
        //得到客户的邮箱，并发送邮件
        $uid = $order_info['uid'];
        $uinfo = M('user')->field('email,parent_id')->where(array('uid'=>$uid))->find();
        if($uinfo['email']){
            if ( preg_match( "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $uinfo['email'] ) ){
                $kehuEmailTrueOrfalse = $this->sendMail($uinfo['email'],$html);
            }
        }
        //得到业务员的邮箱,并发送邮件
        $busid = $uinfo['parent_id'];
        $businfo = M('admin_user')->field('email')->where(array('user_id'=>$busid))->find();
        if($businfo['email']){
            if ( preg_match( "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $businfo['email'] ) ){
                $yewuEmailTrueOrfalse = $this->sendMail($businfo['email'],$html);
            }
        }
    }

    private function sendMail($address='',$message='',$fromName='',$subject='') {
        vendor('PHPMailer.class#phpmailer');
        $mail = new \PHPMailer();
        if(empty($subject)){
            $mail->Subject  = L('A8020');      // 设置邮件标题
        }else{
            $mail->Subject  = $subject;        // 设置邮件标题
        }
        if(empty($fromName)){
            $mail->FromName = L('A8020');      // 设置发件人名字
        }else{
            $mail->FromName = $fromName;       // 设置发件人名字
        }
        $mail->IsSMTP();                       // 设置PHPMailer使用SMTP服务器发送Email
        $mail->CharSet  = 'UTF-8';             // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->AddAddress($address);           // 添加收件人地址，可以多次使用来添加多个收件人
        $mail->Body     = $message;            // 设置邮件正文
        $mail->From     = C('MAIL_ADDRESS');   // 设置邮件头的From字段。
        $mail->Host     = C('MAIL_SMTP');      // 设置SMTP服务器。
        $mail->SMTPAuth = true;               // 设置为"需要验证" ThinkPHP 的C方法读取配置文件
        $mail->IsHTML(true);
        $mail->Username = C('MAIL_LOGINNAME'); // 设置用户名和密码。
        $mail->Password = C('MAIL_PASSWORD');
        return($mail->Send());                // 发送邮件。
    }
}

