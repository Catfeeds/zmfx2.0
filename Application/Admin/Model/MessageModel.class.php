<?php
/**
 * Created by PhpStorm.
 * User: 王松林
 * Date: 2015/10/8 0008
 * Time: 上午 11:42
 */
namespace Admin\Model;
class MessageModel extends AdminModel{
    Protected $autoCheckFields = false;
    public $adminname     = '{adminname}';     //业务员名称
    public $username      = '{username}';      //客户名称
    public $order_sn      = '{order_sn}';      //订单号
    public $optiontime    = '{optiontime}';    //当前时间
    public $paymodee      = '{paymode}';       //支付方式
    public $payment_price = '{payment_price}'; //支付金额
    public $total         = '{total}';         //总金额
	public $error		  = '';					//错误描述

    /**
     * 发送邮件
     * @param  string $address 邮箱地址
     * @param  string $message 邮件内容
     * @param  string $fromName 发件人名字
     * @param  string $subject 标题
     * @return boolean         发送成功(true)或失败(false)
     */
    public function sendMail($address='',$message='',$fromName='',$subject='') {
        vendor('PHPMailer.class#phpmailer');
        $mail = new \PHPMailer();
        if(empty($subject)){
            $mail->Subject  = L('A8020');      // 设置邮件标题
        }else{
            $mail->Subject  = $subject;        // 设置邮件标题
        }
        if(empty($fromName)){
            //$mail->FromName = L('A8020');      // 设置发件人名字
            $mail->FromName = M('agent')->where('agent_id='.C('agent_id'))->getField('agent_name');
            if(empty($mail->FromName)){
                $mail->FromName = L('A8020');
            }
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

    /**
     * 发送手机消息
     * @param int $phone
     * @param string $content
     */
    public function sendSms($phone,$message){
        return true;
    }
    /**
     * 发送站点信息
     * @param  int $uid 客户ID
     * @param  int $id  数据ID
     * @param  time $optiontime  操作时间，毫秒数
     * @return array    array('success'=>true/false, 'msg'=>'')
     */
    public function sendSiteMsg($send_from_id,$send_to_id,$subject,$message,$folder='outbox',$message_type='site'){
        $data['send_from_id']   = $send_from_id;
        $data['send_to_id']     = $send_to_id;
        $data['subject']        = $subject;
        $data['content']        = $message;
        $data['folder']         = $folder;
        $data['message_type']   = $message_type;
        $data['status']         = 1;
        $data['message_time']   = time();
        return M("message")->data($data)->add();
    }

    public function getMessageMuban($id){
       return M('msg_muban')->where('muban_id = '.$id)->find();
    }
    private function explainMuban($content=''){
        $content = str_replace('{$admin.adminname}',$this->adminname, $content);
        $content = str_replace('{$user.username}',$this->username, $content);
        $content = str_replace('{$order.order_sn}',$this->order_sn, $content);
        $content = str_replace('{$order.optiontime}',$this->optiontime,$content);
        $content = str_replace('{$order.paymode}',$this->paymode, $content);
        $content = str_replace('{$order.payment_price}',$this->payment_price, $content);
        $content = str_replace('{$order.total}',$this->total, $content);
        return $content;
    }

    public function sendMubanMsg($address,$muban_id,$type='email',$folder='outbox'){
        if($type=='email'){
            if(!filter_var($address,FILTER_VALIDATE_EMAIL)){
                return false;
            }
            $muban_data    = $this->getMessageMuban($muban_id);
            $muban_content = $muban_data['email_content'];
            $content       = $this->explainMuban($muban_content);
            if(empty($content)){
                return false;
            }
            $code          = $this->sendMail($address,$content);
            return        $code;
        }else if($type=='sms'){
            return false;
        }else if($type=='site'){
            if(empty($address)){
                return false;
            }
            $muban_data    = $this->getMessageMuban($muban_id);
            $title         = $muban_data['title'];
            $muban_content = $muban_data['websys_content'];
            $title         = $this->explainMuban($title);
            $content       = $this->explainMuban($muban_content);
            $code          = $this->sendSiteMsg('system',$address,$title,$content,$folder,$type);
            return $code;
        }
    }
    //发送订单消息
    public function sendOrderMsg2($order_sn,$optiontime,$username,$adminname,$address,$systemAddress,$userid=0,$adminuserid=0){
        $this  -> adminname   = $adminname;                        //
        $this  -> username    = $username;                         //
        $this  -> adminuserid = $adminuserid;                      //
        $this  -> userid      = $userid;                           //
        $this  -> order_sn    = $order_sn;                         //
        $this  -> optiontime  = $optiontime;                       //
        $this  -> sendMubanMsg($this  -> userid,1,'site');              //客户寄信箱
        $this  -> sendMubanMsg($this  -> userid,1,'site','inbox');      //客户收信箱
        $this  -> sendMubanMsg($this  -> adminuserid,2,'site');         //管理员寄信箱
        $this  -> sendMubanMsg($this  -> adminuserid,2,'site','inbox'); //管理员收信箱
       
	    $kefuEmailTrueOrfalse = $this  -> sendMubanMsg($address,             1);                //给客户发送email
        $yewuEmailTrueOrfalse = $this  -> sendMubanMsg($systemAddress,       2);                //给业务员发送email
		$returncode = 0;
		if($kefuEmailTrueOrfalse == false){$returncode = $returncode-1;}//返回-1是客户email没收到
		if($yewuEmailTrueOrfalse == false){$returncode = $returncode-2;}//返回-2是业务email没收到，返回-3是客户，业务都没收到
		if($returncode == 0){return true;}else{return $returncode;}		
        //return true;
    }
	
    //发送订单确认消息
    public  function  sendOrderConfirmMsg($order_sn,$optiontime,$username,$address){
        $this  -> username    = $username;                      //
        $this  -> order_sn    = $order_sn;                      //
        $this  -> optiontime  = $optiontime;                    //
        $this  -> sendMubanMsg($this  -> adminuserid,3,'site'); // 给业务员发送站内短信
        $this  -> sendMubanMsg($address,3);                     //
    }

	//发送订单消息
    public function sendOrderMsg($order_sn,$optiontime,$username,$adminname,$address,$systemAddress,$userid=0,$adminuserid=0, $site_name){
        $this  -> adminname   = $adminname;                        //
        $this  -> username    = $username;                         //
        $this  -> adminuserid = $adminuserid;                      //
        $this  -> userid      = $userid;                           //
        $this  -> order_sn    = $order_sn;                         //
        $this  -> optiontime  = $optiontime;                       //
        $this  -> sendMubanMsg($this  -> userid,1,'site');              //客户寄信箱
        $this  -> sendMubanMsg($this  -> userid,1,'site','inbox');      //客户收信箱
        $this  -> sendMubanMsg($this  -> adminuserid,2,'site');         //管理员寄信箱
        $this  -> sendMubanMsg($this  -> adminuserid,2,'site','inbox'); //管理员收信箱
       
	    //$kefuEmailTrueOrfalse = $this  -> sendMubanMsg($address,             1);                //给客户发送email
        //$yewuEmailTrueOrfalse = $this  -> sendMubanMsg($systemAddress,       2);                //给业务员发送email
		
		$order_info  = M('order')->where('order_sn = "'.$order_sn.'"')->find();
		$order_sn    = $order_info['order_sn'];
		$order_price = $order_info['order_price'];
		$goods_list  = M('order_goods')->where('order_id = "'.$order_info['order_id'].'"')->select();
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

		$userInfo = M("user")->field("usernum")->where("uid=".(int)$userid)->find();
		$usernum = $userInfo?$userInfo['usernum']:0;
		$html = '';
		$html = $site_name."订单提醒：
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
					<div><font color='#ff0000'>*</font>此邮件由".$site_name."<font color='#ff0000'>系统自动发出</font>，请勿回复！</div>
					<div>如果此订单与您无关，请进入".$site_name."用户中心取消订单，或联系".$site_name."在线客服。</div>
					</div>";
        if($address){
            $kefuEmailTrueOrfalse = $this->sendMail($address,$html);
        }		
        if($systemAddress){
            $yewuEmailTrueOrfalse = $this->sendMail($systemAddress,$html);
        }
		
		
		$returncode = 0;
		if($kefuEmailTrueOrfalse == false){$returncode = $returncode-1;}//返回-1是客户email没收到
		if($yewuEmailTrueOrfalse == false){$returncode = $returncode-2;}//返回-2是业务email没收到，返回-3是客户，业务都没收到
		if($returncode == 0){return true;}else{return $returncode;}		
        //return true;
    }

	/**
	 * 发送用户注册邮件
	 * $param array $params 注册用户信息
	**/
    public function sendRegisterMsg($data){
		if(C('MAIL_REG_INFORM')==''){
			$this->error = "业务员提醒邮箱未设置";
			return false;
		}

		if(C('MAIL_SMTP')=='' or C('MAIL_LOGINNAME')=='' or C('MAIL_PASSWORD')==''){
			$this->error = "后台发送邮箱未设置";
			return false;
		}
		
		if(count($data)<=0){
			$this->error = "传入提醒参数不正确";
			return false;
		}

		if(!isset($data['username']) || $data['username']==''){
			$this->error = "用户帐号不能为空";
			return false;
		}

		$address = C('MAIL_REG_INFORM');
		$subject = "用户注册提醒";
		$curdata = date("Y").'年'.date("m").'月'.date("d").'日';  
		$html = "有新的用户于".$curdata."注册成功，请及时关注。用户名：".$data['username'];
		if(isset($data['phone']) && $data['phone']!=''){
			$html .= " 电话 " . $data['phone'];
		}
		if(isset($data['email']) && $data['email']!=''){
			$html .= " 邮箱 " . $data['email'];
		}
		$isOk = false;
		if($address){
            $isOk = $this->sendMail($address,$html,'',$subject);
        }
        return $isOk;
    }
}

