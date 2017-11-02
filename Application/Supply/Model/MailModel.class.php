<?php
/**
 * auth	：fangkai
 * content：邮件发送
 * time	：2016-5-3
**/
namespace Supply\Model;
use Think\Model;
class MailModel extends Model{
    protected $autoCheckFields = false;
    public function __construct() {
        parent::__construct();
        $obj                   = D('Config');
        $this->site_name       = C('SITE_NAME');
        $this->mail_address    = $obj -> getOneZmConfigValue('MAIL_ADDRESS');
        $this->mail_smtp       = $obj -> getOneZmConfigValue('MAIL_SMTP');
        $this->mail_login_name = $obj -> getOneZmConfigValue('MAIL_LOGINNAME');
        $this->mail_password   = $obj -> getOneZmConfigValue('MAIL_PASSWORD');
    }
    /**
    * 发送邮件
    * @param  string $address 邮箱地址
    * @param  string $message 邮件内容
    * @return boolean         发送成功(true)或失败(false)
    */
    public function sendMail($address, $message,$sobject="邮箱绑定") {
        vendor('PHPMailer.class#phpmailer');
        $mail = new \PHPMailer();
        $mail->IsSMTP();                	    // 设置PHPMailer使用SMTP服务器发送Email
        $mail->CharSet='UTF-8';          	    // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->AddAddress($address);    	    // 添加收件人地址，可以多次使用来添加多个收件人
        $mail->Body=$message;            	    // 设置邮件正文
        $mail->From=$this->site_name;           // 设置邮件头的From字段。
        $mail->FromName=$this->mail_smtp;  	    // 设置发件人名字
        $mail->Subject=$sobject;         	    // 设置邮件标题
        $mail->Host=$this->mail_smtp;		    // 设置SMTP服务器。
        $mail->SMTPAuth=true;           	    // 设置为"需要验证" ThinkPHP 的C方法读取配置文件
        $mail->IsHTML(true);
        $mail->Username=$this->mail_login_name; // 设置用户名和密码。
        $mail->Password=$this->mail_password;
        return($mail->Send());                 // 发送邮件。
    }
}
?>