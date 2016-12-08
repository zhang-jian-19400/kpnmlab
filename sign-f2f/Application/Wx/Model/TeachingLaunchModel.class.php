<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到记录模型
 */

class TeachingLaunchModel extends Model
{
    public function updateAnwserStatus($redis, $num, $userName, $signname, $signtime,$seconds,$create,$tlaunchid,$launchid,$anwserstatus,$anwser,$notes,$questionnum, $ip='', $browser='',$blang='',$os='')
    {	
    	$time = date('Y-m-d H:i:s');
	$score = ("0.".strtotime($time) + 0) * -1;
	//判断该答题空间是否已经创建
        if($create!="new")//修改答题空间信息
	{
		$keydetail = 'globalquestiondetail_'.$tlaunchid;
                $questiondetail = $redis->zrange($keydetail, 0, 0);
                $array = json_decode($questiondetail[0], true);
                $startseconds = (int)strtotime($time) - (int)$seconds;
                
                $starttime = date('Y-m-d H:i:s', $startseconds+106);
		$redis->delete($keydetail);
		$valuedetail = json_encode(array($anwserstatus,$anwser,$seconds,$notes,$questionnum,$starttime));
                $redis->zadd($keydetail,$score,$valuedetail);
                
                $this->where('id='.$tlaunchid)->setField(array('anwser'=>$anwser,'usedseconds'=>$seconds,'notes'=>$notes,'questionnum'=>$questionnum));

                $result = array(
                                'code' => C('RETURN.NORMAL'),
				'tlaunchid'=>$tlaunchid,
                                'launchresult'=>'ok',
                                'anwserstatus'=>$anwserstatus,
				's0'=>$time,
                                's1'=>$startseconds,
				's2'=>(int)strtotime($time),
				's3'=>$seconds,
                                's4'=>$starttime
                                
                        );
	}
	else//则创建该答题空间
	{
		//将答题空间信息保存至数据库中
		$tlaunch = array(
			'launchid'=>$launchid,
			'anwser'  =>$anwser,
			'num'     =>$num,
			'ip'      =>$ip,
			'browser' =>$browser,
			'blang'   =>$blang,
			'os'      =>$os,
                        'name'    =>$userName,
                        'notes'   =>$notes,
                        'questionnum'=>$questionnum
		);
		$tlaunchid = $this->add($tlaunch);
		if($tlaunchid)
		{
			$allquestion = 'tlaunchquestionself_'.$launchid;
                        $aqresult = $redis->zrange($allquestion,0,-1);
			if(count($aqresult)<=0){
				//保存到用户自己的 创建答题记录中
				$key = 'tsignlaunchself_'.$num;
				$value = json_encode(array($launchid,$signname,$signtime));
				$redis->zadd($key,$score,$value);
			}
			$value = json_encode(array($tlaunchid,$time));
			$redis->zadd($allquestion,$score,$value);
			
			//对应空间下的所有题目
                        $keydetail = 'globalquestiondetail_'.$tlaunchid;
                        $valuedetail = json_encode(array($anwserstatus,$anwser,$seconds,$notes,$questionnum,$time));
                        $redis->zadd($keydetail,$score,$valuedetail);

                        $result = array(
                                'code' => C('RETURN.NORMAL'),
                                'launchresult'=>'ok',
				'tlaunchid'=>$tlaunchid,
				'count'=>count($aqresult)
                        );

		}
		
	}
	return $result;   	
    }

}

