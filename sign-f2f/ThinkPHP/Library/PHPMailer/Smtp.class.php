<?php

namespace PHPMailer;

require_once __DIR__ . '/PHPMailer/PHPMailerAutoload.php';

class Smtp {

    static public function send($address, $title, $content)
    {
        $mail  = new \PHPMailer();
        //$mail->SMTPDebug = 2; 
         
         //服务器配置
        $mail->isSMTP();
        $mail->SMTPAuth=true;
        $mail->Host = 'smtp.qq.com';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        //用户名设置
        $mail->FromName = '网站邮件提醒';
        $mail->Username ='dashixiong@xybangzhu.com';
        $mail->Password = 'dsxdsx0';
        $mail->From = 'dashixiong@xybangzhu.com';
        $mail->addAddress($address);

        //内容设置
        $mail->isHTML(true); 
        $mail->Subject = $title;
        $mail->Body = $content;

        //返回结果
        //echo $mail->send()? '发送邮件成功。':'发送邮件失败：' . $mail->ErrorInfo;
        return $mail->send();
    }
}
