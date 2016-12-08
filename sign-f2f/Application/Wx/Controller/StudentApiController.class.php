<?php

namespace Wx\Controller;

use Wx\Model\WechatModel as Wechat;
use Wx\Model\MobelPhoneModel as MobelPhone;

class StudentApiController extends AuthController
{
    public function _initialize()
    {
        parent::_initialize();
       /** 
        if(!D('Student')->isStudent($this->userId)) {
            $this->answer = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '您不需要签到...'
            );
            exit;
        }
	**/
    }

    // 获取签名
    public function getConfig($url)
    {
        $config = array(
            'debug'     => false,
            'url'       => $url,
            'jsApiList' => ['getLocation', 'scanQRCode', 'closeWindow'],
        );

        $wechat = new Wechat(C('WECHAT.AGENTID'));
        $config = $wechat->getJsConfig($config);
        if ($config) {
            return $this->answer['data'] = $config;
        }
		
        $this->answer = array(
            'code' => C('RETURN.ERROR'),
            'msg'  => '微信配置获取失败，如多次如此，请联系管理员'
        );
    }

    // 获取二维码
    public function getQRCode($longitude=0, $latitude=0, $accuracy = -1, $speed = 0, $oldQRCode = '',$sid=-1,$signname='')
    {
	//从redis中取出该用户最后一次签到的地理位置
	$redis  = $this->redis;
	$selfSignKey = 'selfsign_' . $this->userId;
	$prefix = C('REDIS.QRCODE_PREFIX');
        
        $min = -10;	
	$selfSignResult = $redis->zrange($selfSignKey, 0, 0);
        if($sid!=-1)
	{
		$sresult = $redis->zrange($selfSignKey, 0, -1);
        	for ($i = 0; $i < count($sresult); $i++) {
                	$array = json_decode($sresult[$i], true);
			if($array[0]==$sid)
			{$min = $i;
				$min=$i;
				$selfSignResult=$redis->zrange($selfSignKey, $i, $i);
				break;
			}			
		}	
	}
	/**
	else
	{
		$selfSignResult = $redis->zrange($selfSignKey, 0, 0);
	}**/

	#$selfSignResult = $redis->zrange($selfSignKey, 0, 0);	
	if(!empty($selfSignResult))
	{
		$lastSignInfo = $selfSignResult[0];
       		$array = json_decode($lastSignInfo, true); 	
		$signid = $array[0];
		if(empty($signname))$signname = $array[2];
		//$signname = $array[2];
		$incode = $array[4];
		$ls_longitude = $array[5];
        	$ls_latitude = $array[6];
                 		
		//验证该用户是否已经签到
		if(D('SignLaunchTemp')->vaildSign($signid))
		{
			$distance = getDistance($latitude,$longitude,$ls_latitude,$ls_longitude);
			//判断是否在签到范围之内
        		if ($distance > C('sign_distance')) 
			{
                		$this->answer['data'] = array(
                                		'code' => C('RETURN.ERROR'),
                                		'msg'  => '你已经不在签到范围内了，无法辅助其他人签到哦^_^',
						'signOk'  => 'exceeddis'
                		);
        		}elseif ($accuracy > C('accuracy_max')) 
			{
            			$error = '当前位置精度不够，确保已打开WIFI，并稍等片刻后再重新生成二维码';
            			$this->answer['data'] = array(
                			'code' => C('RETURN.ERROR'),
                			'msg'  => $error,
					'signOk'  => 'exceedpre'
            			);
					
		   	}else
			{
				// 更新旧二维码
        			if (!empty($oldQRCode)) 
				{
            				$key  = $prefix . $oldQRCode;
            				$info = S($key);
            				S($key, $info, C('qrcode_used') + 0);
        			}
				// 生成新二维码
           			$info = array(
					 'signid'    => $signid,
					 'signname'  => $signname,
					 'incode'    => $incode,
               				 'longitude' => $longitude,
               				 'latitude'  => $latitude,
					 'accuracy'  => $accuracy,
					 'speed'     => $speed
            			);
           			$code = getRandStr(6);
           			$key  = $prefix . $code;
           			S($key, $info, C('qrcode_max') + 0);
            			$this->answer['data'] = array(
                			'QRCode' => $code,
					'signname'=>$signname,
					'signOk' => 'yes'
            			);
      			  }
		}
		else
		{
                        $this->answer['data'] = array(
                                        'code' => C('RETURN.NORMAL'),
                                        'signOk'  => 'no'
                                );
		}
	        /**	
		$min = D('SignLaunchTemp')->vaildSign($signid);
		$this->answer['data'] = array(
                                        'QRCode' => $min
                                );**/
	}
	else
	{
                $this->answer['data'] = array(
                                        'code' => C('RETURN.NORAML'),
                                        'signOk'  => 'no'
                                );
        }
    }
    //开始签到
    public function startSign($longitude=0, $latitude=0, $accuracy = -1, $speed = 0, 
		     		$signid, $signname,$incode)
    {
        $phone = new MobelPhone();
        $ip = $phone->getClientIP();//移动端IP
        $browser = $phone->determinebrowser();//移动端浏览器及版本号
        $blang = $phone->getLang();//浏览器使用的语言
        $os = $phone->getOs();//操作系统类型与版本号


    	$this->answer = D('SignRecord')->signRecord(
    			$this->redis, $this->userId,$this->userName, 0,
    			$longitude, $latitude, $accuracy, $speed, $signid, $signname,$incode,
                        $ip, $browser, $blang, $os
    	);
    }    

    //根据用户提供的签到码与用户的地理位置获取 签到信息
    public function fetchSignInfo($longitude=0, $latitude=0, $accuracy = -1, $speed = 0, $signcode)
    {
    	$this->answer = D('SignLaunch')->fetchSignInfo(
    			$this->redis, $this->userId, 0,
    			$longitude, $latitude, $accuracy, $speed, $signcode
    		);
    	
    }
    //发起签到
    public function signLaunch($longitude=0, $latitude=0, $accuracy = -1, $speed = 0)
    {
        $phone = new MobelPhone();
        $ip = $phone->getClientIP();//移动端IP
        $browser = $phone->determinebrowser();//移动端浏览器及版本号
        $blang = $phone->getLang();//浏览器使用的语言
        $os = $phone->getOs();//操作系统类型与版本号

    	$this->answer = D('SignLaunch')->signLaunch(
			$this->redis, $this->userId, $this->userName, 0,
    			$longitude, $latitude, $accuracy, $speed,$ip,$browser,$blang,$os
			);
    }
    // 签到
    public function sign($longitude, $latitude, $accuracy = -1, $speed = 0)
    {
        $this->answer = D('Sign')->sign(
            $this->redis, $this->userId, 0,
            $longitude, $latitude, $accuracy, $speed
        );
    }

    // 扫码
    public function scan($code)
    {
        $phone = new MobelPhone();
        $ip = $phone->getClientIP();//移动端IP
        $browser = $phone->determinebrowser();//移动端浏览器及版本号
        $blang = $phone->getLang();//浏览器使用的语言
        $os = $phone->getOs();//操作系统类型与版本号        
	
        // 判断二维码
        $prefix = C('REDIS.QRCODE_PREFIX');
        $key    = $prefix . $code;
        $info   = S($key);
        if (!is_array($info)) {
            $error = '签到失败，二维码不存在或已失效';
            $this->answer = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => $error
            );
        }
	else {
	    $signid = $info['signid'];
	    $signname = $info['signname'];
	    $incode = $info['incode'];
	    $longitude = $info['longitude'];
	    $latitude = $info['latitude'];
	    $accuracy = $info['accuracy'];
	    $speed = $info['speed'];
	    $this->answer = D('SignRecord')->signRecord(
                        $this->redis, $this->userId, $this->userName, 1,
                        $longitude, $latitude, $accuracy, $speed, $signid, $signname,$incode,
                        $ip, $browser, $blang, $os);
        }
	/**
	$this->answer = array(
                                'code' => C('RETURN.NORMAL'),
                                'data'  => array(
                                        'signid'  => '1',
                                        'signname' => '2',
                                        'alldata'  => '3'
                                )
                        );
	**/
    }

    // 获取历史记录
    public function getHistoryLogs()
    {
        $data = D('SignStatistics')->getLogs($this->userId);
        $data['logs'] = D('Sign')->getOnePerson($this->userId);
        $this->answer['data'] = $data;
    }
}
