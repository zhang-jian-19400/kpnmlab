<?php

namespace Wx\Model;

class MobelPhoneModel
{
////获得访IP
function getClientIP()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if(getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow";
    return $ip;
}

////获得访客浏览器
function determinebrowser () {
$Agent = $_SERVER['HTTP_USER_AGENT'];
$browseragent=""; //浏览器 
$browserversion=""; //浏览器的版本 
if (ereg('MSIE ([0-9].[0-9]{1,2})',$Agent,$version)) {
$browserversion=$version[1];
$browseragent="Internet Explorer";
} else if (ereg( 'Opera/([0-9]{1,2}.[0-9]{1,2})',$Agent,$version)) {
$browserversion=$version[1];
$browseragent="Opera";
} else if (ereg( 'Firefox/([0-9.]{1,5})',$Agent,$version)) {
$browserversion=$version[1];
$browseragent="Firefox";
}else if (ereg( 'Chrome/([0-9.]{1,3})',$Agent,$version)) {
$browserversion=$version[1];
$browseragent="Chrome";
}
else if (ereg( 'Safari/([0-9.]{1,3})',$Agent,$version)) {
$browseragent="Safari";
$browserversion=$version[1];
}
else {
$browserversion="";
$browseragent="Unknown";
}
return $browseragent." ".$browserversion;
}

////获得访客浏览器语言
  function getLang(){
   if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $lang = substr($lang,0,5);
    if(preg_match("/zh-cn/i",$lang)){
     $lang = "简体中文";
    }elseif(preg_match("/zh/i",$lang)){
     $lang = "繁体中文";
    }else{
        $lang = "English";
    }
    return $lang;

   }else{return "获取浏览器语言失败！";}
  }

 ////获取访客操作系统
  function getOs(){
   $ua = $_SERVER['HTTP_USER_AGENT'];
   if(!empty($_SERVER['HTTP_USER_AGENT'])){
    $OS = $_SERVER['HTTP_USER_AGENT'].'unknow';
      if (strpos($ua, 'Android') !== false) {
     preg_match("/(?<=Android )[\d\.]{1,}/", $ua, $version);
     $OS = 'Android '.$version[0];
    }elseif (strpos($ua, 'iPhone') !== false) {
     preg_match("/(?<=CPU iPhone OS )[\d\_]{1,}/", $ua, $version);
     $OS = 'iPhone '.str_replace('_', '.', $version[0]);
    }elseif (strpos($ua, 'iPad') !== false) {
     $OS = 'iPad '.str_replace('_', '.', $version[0]);
    }elseif (strpos($ua, 'Windows Phone') !== false) {
     $OS = 'Windows Phone '.$version[0];
    }else {
     $OS = 'Other';
    }
          return $OS;
   }else{
    return '未知系统';
}
}




}

