<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到记录模型
 */

class TeachingAnswerModel extends Model
{
    public function submitAnswer($redis, $num, $userName,$launchid,$tlaunchid,$answer, $signname, $stime,$usedseconds, $ip='', $browser='',$blang='',$os='')
{
    $time = date('Y-m-d H:i:s');
    $score = ("0.".strtotime($time) + 0) * -1;
    $result = array();
    //将用户答案插入至数据库中
    //将答题空间信息保存至数据库中
    $tanswer = array(
                        'launchid'=>$launchid,
                        'tlaunchid'=>$tlaunchid,
                        'name'=>$userName,
                        'anwser'  =>$answer,
                        'num'     =>$num,
                        'ip'      =>$ip,
                        'browser' =>$browser,
                        'blang'   =>$blang,
                        'os'      =>$os,
                        'time'    =>$time,
                        'usedseconds'=>$usedseconds
    ); 
  
    $tanswerid = $this->add($tanswer);
    
    if($tanswerid)
    { 
        $allquestion = 'tanswerquestionself_'.$launchid.$num;
        $aqresult = $redis->zrange($allquestion,0,-1);
        //用户答题问题的一级目录
	if(count($aqresult) <= 0)
	{
        	$answerselfkey = "answerselfkey_".$num;
        	$answervalue = json_encode(array($launchid,$signname,$stime));
        	$redis->zadd($answerselfkey,$score,$answervalue);
        }
        $value = json_encode(array($tlaunchid,$time));
        $redis->zadd($allquestion,$score,$value);
        //空间下 所有答过的题目
        $keydetail = 'globalanswerquestiondetail_'.$tlaunchid.$num;
        $valuedetail = json_encode(array($answer,$usedseconds));
        $redis->zadd($keydetail,$score,$valuedetail);

        $keyglobalallanswer = 'globalallanswer_'.$tlaunchid;
        $valueallanswer = json_encode(array($num,$userName,$usedseconds,$answer));
        $redis->zadd($keyglobalallanswer,$score,$valueallanswer);

        $result['answerresult']='ok';
    }else{$result['answerresult']='error';}
    return $result;
}

}
