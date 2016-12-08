<?php
namespace Wx\Controller;

class CommonApiController extends NoneAuthController
{
    //邮件发送
    public function sendMail($to, $title, $signid, $signcode, $leftword='')
    {
        $link="http://49.123.2.37/sign-f2f/wx/commonApi/downloadSignList"."?signid=".$signid."&signcode="."$signcode";
        $content = "<h3>湖南科技大学（企业号）-面对面签到：</h3>";
        $content = $content."<div style='font-size:15px'>".$leftword."</div>";
        $content = $content."<p>请点击下面链接下载签到列表（<span style='font-weight:bold'>右键另存为...</span>）。</p>";
        $content = $content."<p><a href=".$link.">$title.xls</a></p>";   
	$content = $content."<p>祝您生活愉快！</p>"; 

        vendor('PHPMailer.PHPMailerAutoload');
        $mail = new \PHPMailer(); //实例化
        $mail->IsSMTP(); // 启用SMTP
        $mail->Host=C('MAIL_HOST'); //smtp服务器的名称
        $mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
        $mail->Username = C('MAIL_USERNAME'); //发件人邮箱
        $mail->Password = C('MAIL_PASSWORD'); //邮箱密码
        $mail->From = C('MAIL_USERNAME'); //发件人地址（也就是你的邮箱地址）
        $mail->FromName = '湖南科技大学(企业号)'; //发件人姓名
        $mail->AddAddress($to,"尊敬的用户");
        $mail->WordWrap = 50; //设置每行字符长度
        $mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
        $mail->CharSet=C('MAIL_CHARSET'); //设置邮件编码
        $mail->Subject =$title; //邮件主题
        $mail->Body = $content; //邮件内容
        
	$this->answer['data'] = array(
                'msg'  => '正在获取微信授权...',
		'status' => $mail->Send()
            );

    }
    
     //邮件发送
    public function sendMailSpace($to,$spaceid, $spacename, $leftword='')
    {
        $link="http://49.123.2.37/sign-f2f/wx/commonApi/downloadSpaceSignList"."?spaceid=".$spaceid."&spacename="."$spacename";
        $content = "<h3>湖南科技大学（企业号）-面对面签到：</h3>";
        $content = $content."<div style='font-size:15px'>".$leftword."</div>";
        $content = $content."<p>请点击下面链接下载所有签到信息（<span style='font-weight:bold'>右键另存为...</span>）。</p>";
        $content = $content."<p><a href=".$link.">$spacename.xls</a></p>";
        $content = $content."<p>祝您生活愉快！</p>";

        vendor('PHPMailer.PHPMailerAutoload');
        $mail = new \PHPMailer(); //实例化
        $mail->IsSMTP(); // 启用SMTP
        $mail->Host=C('MAIL_HOST'); //smtp服务器的名称
        $mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
        $mail->Username = C('MAIL_USERNAME'); //发件人邮箱
        $mail->Password = C('MAIL_PASSWORD'); //邮箱密码
        $mail->From = C('MAIL_USERNAME'); //发件人地址（也就是你的邮箱地址）
        $mail->FromName = '湖南科技大学(企业号)'; //发件人姓名
        $mail->AddAddress($to,"尊敬的用户");
        $mail->WordWrap = 50; //设置每行字符长度
        $mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
        $mail->CharSet=C('MAIL_CHARSET'); //设置邮件编码
        $mail->Subject =$spacename; //邮件主题
        $mail->Body = $content; //邮件内容

        $this->answer['data'] = array(
                'msg'  => '正在获取微信授权...',
                'status' => $mail->Send()
            );
    }



    //根据条件获取签到列表信息
    public function getData($signid, $signcode)
    {
	$redis  = $this->redis;

	$launch_forever_key = 'launch_forever_key_' . $signid;
        $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
	$temparray = json_decode($launch_forever_array [0], true);
	
	$launchname = $temparray[0];
	//$launchtime = date("Y-m-d",$temparray[4]);
        //$launchtime = explode(' ',$temparray[4])[0];
        $launchtime = $temparray[4];
	$signcodereal = $temparray[1];

	
	$key_global   = 'global_' . $signid;
	$result = $redis->zrange($key_global, 0, -1);
	$count = count($result);

	$data = array(
                        'launchname' => $launchname,
                        'signcode' => $signcodereal,
                        'count'   => $count,
                        'launchtime'=>$launchtime,
                        'list'  => array()
        );

	for ($i = 0; $i < count($result); $i++) {
                $array = json_decode($result[$i], true);
                //$usernum = substr_replace($array[0], '****',-5,-1);
                $usernum = $array[0];
                $data['list'][$i] = array(
                                'num'  => $usernum,
                                'username' => $array[1],
                                'signtime' => explode(' ',$array[2])[1]
                );
        }
        
	$this->answer = $data;
        
    }
    public function getSpaceSignData($spaceid,$spacename='')
    {
	$tarray = array(
		'signinfo'=>array(),
		'allusers'=>array());
	$redis  = $this->redis;

	$global_users_key = 'global_users'.$spaceid;
	$global_users_result = $redis->zrange($global_users_key, 0, -1);
	$spaceusercount = count($global_users_result);

        $spaceToSigns_key = "spaceToSigns_key"+$spaceid;
        $result = $redis->zrange($spaceToSigns_key, 0, -1);
	//$tarray['result']=$result;
	$signcount = count($result);
	$tarray['title']="空间名称：".$spacename." 人数：".$spaceusercount." 签到次数：".$signcount;
        $signinfo = array();
	//$allusers = array();
	for($i = 0; $i < $signcount+1; $i++)
	{
	  	
	     $signedpeople = array();
	  if($i>0){
	    
                     $signid = $result[$i-1];
			 $key_global = "global_".$signid;
            $signlistresult = $redis->zrange($key_global, 0, -1);
                     $launch_forever_key = 'launch_forever_key_' . $signid;
                     $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
                     $temparray = json_decode($launch_forever_array [0], true);
                     $signname = $temparray[0];
                     $ymd = explode(' ',$temparray[4])[0];
		     
		    
	             $tarray['signinfo'][$i-1] = array(
                        'signname'=>$signname,
                        'signdata'=>$ymd,
                );

		 for ($j = 0; $j < count($signlistresult); $j++) {
                         $signlistarray = json_decode($signlistresult[$j], true);
                         $usernum = $signlistarray[0];
                         $username = $signlistarray[1];
                         $signedpeople[$usernum][$username]=1;
                     }
		$tarray['signedpeople'][$i]=$signedpeople;
         }




                     //$key_global = "global_"+$signid;
                     //$signlistresult = $redis->zrange($key_global, 0, -1);

                   
                

	    for($j=0; $j<$spaceusercount; $j++)
	    {
	
		 $arraysm = json_decode($global_users_result[$j], true);
		 $spaceunum = $arraysm['usernum'].'';
                 $spaceuname= $arraysm['username'];
                 $spacetitle= $arraysm['title'];
             
		 if($i == 0)
		 {
		     $tarray['allusers'][$j][$i]= $spacetitle;
		     $tarray['allusers'][$j][$i+1]= $spaceunum;
                       $tarray['allusers'][$j][$i+2]= $spaceuname;
		 } 
		 else
		 {
		     if($signedpeople[$spaceunum][$spaceuname] == 1)
		     {
		         $tarray['allusers'][$j][$i+2]= 1;
		     }
		     else
		     {
		         $tarray['allusers'][$j][$i+2]= 0;
		     }
	
		 }   
            }    
		 

	}
	//$this->answer['data'] = $tarray;
	$this->answer = $tarray;

        /** 
        //$this->answer['data'] = array('result' => $result);
        for ($i = 0; $i < count($result); $i++)
        {
		$array = json_decode($result[$i], true);
                $signid = $array[0];

  		$launch_forever_key = 'launch_forever_key_' . $signid;
                $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
       	 	$temparray = json_decode($launch_forever_array [0], true);
		$signname = $temparray[0];
 		$ymd = explode(' ',$temparray[4])[0];
            
		$key_global = "global_"+$signid;
                $signlistresult = $redis->zrange($key_global, 0, -1);

		$signcount = count($signlistresult);

		
		
		
		$signedpeople = array();
		for ($j = 0; $j < count($signlistresult); $j++) {
                         $signlistarray = json_decode($signlistresult[$i], true);
                         $usernum = $array[0];
                         $username = $array[0];
                         $signedpeople[$usernum][$username]=1;
                }

		for($i = 0; $i < count($global_users_result); $i++)
        	{
                 	$arraysm = json_decode($global_users_result[$i], true);
                 	$spaceunum = $arraysm['usernum'];
                 	$spaceuname= $arraysm['username'];
                 	$spacetitle= $arraysm['title'];

			
        	}

	
                for ($j = 0; $j < count($signlistresult); $j++) {
			 $signlistarray = json_decode($signlistresult[$i], true);
                         $usernum = $array[0];
                         $username = $array[0];
                         
		}      	
        }**/
	
	        
    }

    //下载给定空间所有关联的签到列表信息
    public function downloadSpaceSignList($spaceid, $spacename='')
    {
        $this->getSpaceSignData($spaceid,$spacename);
        $data=$this->answer;
        D('SignSpaceExcel')->sum($data, $spacename);	
    }
    //下载签到列表
    public function downloadSignList($signid, $signcode='')
    {
        $this->getData($signid, $signcode);
        $data = $this->answer;
        D('SignListExcel')->sum($data); 
       //$this->answer = array();
    }    
    

}
