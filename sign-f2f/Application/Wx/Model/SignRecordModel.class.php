<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到记录模型
 */
class SignRecordModel extends Model
{
    public function signRecord($redis, $num, $userName, $type, $longitude = 0, $latitude = 0, $accuracy = -1, $speed = 0,$signid, $signname, $incode, $ip='', $browser='',$blang='',$os='')
    {	
	$time = date('Y-m-d H:i:s');
	
	$flag = false;
	$key_global = 'global_'.$signid;
	$all_recordBySignid = $redis->zrange($key_global,0,-1);
	for($i = 0; $i < count($all_recordBySignid); $i++){
		$array = json_decode($all_recordBySignid[$i], true);
		if($array[0] == $num){//判断该用户是否已经签到
			$flag = true;
			break;
		}

	}

	
        //判断对应签到是否已结束
        $signover_flag = true;
	$launch_expire_key = 'launch_expire_key_' . $signid;
        $launch_value = S($launch_expire_key);
	if($launch_value)
        {
		$temparray = explode('<>',$launch_value);
		$haveEnd = $temparray[2];
		if($haveEnd == 1) $signover_flag = false;//表明未结束
	}
        else
        {
		$signover_flag = false;//表明签到已经过期
		$result = array(
                                'code' => C('RETURN.ERROR'),
                                'msg'  => '你来晚了，签到已近过期了呢^_^!'
                        );
                return $result;
        }
	if($signover_flag)
	{
		$result = array(
                                'code' => C('RETURN.ERROR'),
                                'msg'  => '你来晚了，签到已近结束了呢^_^!'
                        );
		return $result;
	}


	if(!$flag){
	//保存签到记录
		$record = array(
	    		'signlaunch'=> $signid,
            		'num'       => $num,
            		'time'      => $time,
            		'longitude' => $longitude,
            		'latitude'  => $latitude,
            		'accuracy'  => $accuracy,
            		'speed'     => $speed,
            		'signtype'  => $type,
                        'username'  => $userName,
                        'ip'        => $ip,
                        'browser'   => $browser,
                        'blang'     => $blang,
                        'os'        => $os
        	);
        	$recordId = $this->add($record);	
	
		$result = array();
		if($recordId)
		{		
        		//保存签到用户自己的记录到redis中 
			$key = 'selfsign_'.$num;
                	$score = ("0.".strtotime($time) + 0) * -1;
                	$value = json_encode(array($signid,$recordId,$signname,$time,$incode,$longitude,$latitude));
                	$redis -> zadd($key, $score, $value);
		
			//向全局签到记录redis表中添加本次签到记录
			//$username = D('SignPeople')->getName($num);		
			$username = $userName;
			//$key_global = 'global_'.$signid;
			$score_global = ("0.".strtotime($time) + 0);
			$value_global = json_encode(array($num,$username,$time));
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
		else
		{
			$result = array(
                        	'code' => C('RETURN.ERROR'),
				'msg'  => '签到失败，请稍后重试~'
			);
		}
	}else{
		$result = array(
                                'code' => C('RETURN.ERROR'),
                                'msg'  => '你已经签到过了，请勿重复签到^_^'
                        );
	}
	
        return $result;
    }

}
