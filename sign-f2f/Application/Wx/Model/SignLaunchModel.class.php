<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到记录模型
 */
class SignLaunchModel extends Model
{
    public function fetchSignInfo($redis, $num, $type, $longitude = 0, $latitude = 0, $accuracy = -1, $speed = 0,                                  $signcode)
    {
	$result   = array();
        //判断定位精度
        if ($accuracy > C('accuracy_max')+0) {
                $result = array(
                                'code' => C('RETURN.ERROR'),
                                'msg'  => '当前位置定位精度不够，请尝试打开WIFI后重试，或者请人帮忙辅助扫码签到'
                );
        }
        else
        {
	        //根据用户填写的签到码 及其 地理位置 查询对应签到名称
		$resultparams = $this->fetchByGivenCode($longitude,$latitude,$signcode);
		//$dfilename = 'test';	
	
		$result = array(
                        'data' => array(
                                        'success' => true,
					'signid' => $resultparams['signid'],
                                        'dfilename' => $resultparams['dfilename'],
                                        'type'    => $type
                        )
                );
                
	}
        return $result;
    }
    public function signLaunch($redis, $num,$userName, $type, $longitude = 0, $latitude = 0, $accuracy = -1, $speed = 0,$ip='',$browser='',$blang='',$os='')
    {
       
        $result   = array();
        //判断定位精度
        if ($accuracy > C('accuracy_max')+0) {
        	$result = array(
        			'code' => C('RETURN.ERROR'),
        			'msg'  => '当前位置定位精度不够，请确保已打开WIFI，并稍等片刻后再重新发起签到'
        	);
	}
        else
	{
		$time = date('Y-m-d H:i:s');
        	//生本次签到的默认名称	
        	$username = $userName;
       		$date = time();
        	$dfilename = $username.'_'.$date;  
                
       		//生成签到码 并保存到 mysql中
       		$resultparams = $this->saveLaunch($num, $longitude, $latitude, $accuracy, $speed, $dfilename, $time,$ip,$browser,$blang,$os,$userName); 
		$gencoderesult = $resultparams['gencoderesult'];
		$signid = $resultparams['signid'];		
		/**
       		$result = array(
       			'data' => array(
       					'success' => true,
       					'gencoderesult'  => $gencoderesult,
					'dfilename' => $dfilename,
					'signid'    => $signid,
       					'type'    => $type
       			)
       		);**/
		if($signid > 0){//大于0 表明生成签到码成功，signid由数据库存储过程生成
                        $ymd = date('Y-m-d');
			//保存到redis中
			$key = 'launch_'.$num;
			$score = ("0.".strtotime($time) + 0) * -1;
			$value = json_encode(array($signid,$dfilename,$gencoderesult,$time));
			$redis -> zadd($key, $score, $value);	

			////1表示用户默认可见和签到中
			$launch_expire_array =$dfilename.'<>'.$gencoderesult.'<>'.'1'.'<>'.'1'.'<>'.strtotime($time);
			//设置本次签到的过期时间
			$launch_expire_key = 'launch_expire_key_' . $signid;
			#$launch_expire_value = strtotime($time).'_'.'1'.'_'.'1';//1表示用户默认可见和签到中
			S($launch_expire_key, $launch_expire_array,intval( C('sign_expire'))); 
			//$redis->delete($launch_expire_key);

			//创建缓存用于永久保存用户的发起签到信息

			
			$launch_forever_key = 'launch_forever_key_' . $signid;
			$launch_forever_array =json_encode(array($dfilename,$gencoderesult,'1','1',$time));
			$score = -0.9;
           		$redis->zadd($launch_forever_key, $score, $launch_forever_array);




                        //保存签到用户自己的记录到redis中 
                        $key_self = 'selfsign_'.$num;
                        $score_self = ("0.".strtotime($time) + 0) * -1;
 			$value_self = json_encode(array($signid,-1,$dfilename,$time,$gencoderesult,$longitude,$latitude));
                        $redis -> zadd($key_self, $score_self, $value_self);

                        //向全局签到记录redis表中添加本次签到记录
                        //$username = D('SignPeople')->getName($num);           
                        $username = $userName;
                        $key_global = 'global_'.$signid;
                        $score_global = ("0.".strtotime($time) + 0) * -1;
                        $value_global = json_encode(array($num,$userName,$time));
                        $redis -> zadd($key_global,$score_global,$value_global);

                        $result = array(
                                'code' => C('RETURN.NORMAL'),
                                'data'  => array(
                                        'signid'  => $signid,
                                        'signname' => $signname,
                                        'alldata'  => $flag
                                )
                        );




		}
		 $result = array(
                        'data' => array(
                                        'success' => true,
                                        'gencoderesult'  => $gencoderesult,
                                        'dfilename' => $dfilename,
                                        'signid'    => $signid,
                                        'type'    => $type
                        )
                );

		
        }
        //保存记录
        //$error = (C('RETURN.ERROR') === $result['code'])? $result['msg']:null;
        //D('Record')->saveData($num, $longitude, $latitude, $accuracy, $speed, $type, $place, $error);
        return $result;
    }

    //查询签到信息
    public function fetchByGivenCode($longitude, $latitude, $givecode)
    {
        $sql = "call fetchSignInfo('$givecode',$longitude,$latitude,@result,@signid)";
        $con = mysql_connect("localhost","signf2f","RG2wmUGrmnJCXqrF");
        mysql_query('set names utf8');
        mysql_select_db("signf2f");
        $result=mysql_query($sql);

        $row=mysql_fetch_row($result);
        $signid = $row[0];
        $dfilename = $row[1];
        mysql_close($con);
        $resultparams = array(
				"dfilename"=>$dfilename,
 				"signid"=>$signid
			);
        return $resultparams;
    }

    //保存本次发起签到信息
    public function saveLaunch($num, $longitude, $latitude, $accuracy, $speed, $dfilename, $time,$ip='',$browser='',$blang='',$os='',$userName='')
    {
	$sql = "call genSignCode('$num',$longitude,$latitude,
				$accuracy,$speed,'$dfilename','$time','$ip','$browser','$blang','$os','$userName',@result,@signid)";
  	$con = mysql_connect("localhost","signf2f","RG2wmUGrmnJCXqrF");
        mysql_query('set names utf8');
	mysql_select_db("signf2f");
	$result=mysql_query($sql);
	$row=mysql_fetch_array($result);
        $gencoderesult = $row[0];
	$signid = $row[1];
	mysql_close($con);
	$resultparams = array(
                                "gencoderesult"=>$gencoderesult,
                                "signid"=>$signid
                        );
        return $resultparams;
    }


}
