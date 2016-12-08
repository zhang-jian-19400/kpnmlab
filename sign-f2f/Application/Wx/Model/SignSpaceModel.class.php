<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到空间模型
 */
class SignSpaceModel extends Model
{
    public function createSpace($redis,$spacename, $num, $username, $list)
    {	
	$time = date('Y-m-d H:i:s');

        //保存到数据库
	$space = array(

		'spacename'=>$spacename,
		'createtime'=>$time,
		'usernum'=>$num,
		'username'=>$username
	);
	$spaceId = $this->add($space);

	$answer = array();
	//插入数据库成功后，继续保存签到空间中的用户信息
	$users=array();
	$usercount = 0;
	if($spaceId)
	{
		for($row=1; $row<= count($list); $row++)
		{
			$user = array('spaceid'=>$spaceId,'usernum'=>$list[$row][0], 'username'=>$list[$row][1],'title'=>$list[$row][2]);
			//$users[$row][] = $user;
			$usercount=array_push($users,$user);
		}
		$result = D('SignSpacePeople')->addAll($users);

		if($result)
		{

			//若数据库保存成功 则将相关数据存入缓存中
			//保存全局签到空间

			$global_users_key = 'global_users'.$spaceId;
				
			for($i=0; $i< count($users); $i++)
	                {
				$currenttime = date('Y-m-d H:i:s');
				$global_users_score = ('0.'.strtotime($currenttime)+0);
				$global_users_value = json_encode($users[$i]);
                	        $redis -> zadd($global_users_key, $global_users_score, $global_users_value);
        	        }



			$redis -> zadd($global_users_key, $global_users_score, $global_users_value);

			//保存用户的签到空间列表
			$user_signspace_key = "all_signspace".$num;
			$user_signspace_score = ('0.'.strtotime($time)+0);
			$user_signspace_value = json_encode(array($spaceId, $spacename, $usercount));
			$redis -> zadd($user_signspace_key, $user_signspace_score, $user_signspace_value);


			$answer= array('createStatus'=>'ok','spaceid'=>$spaceId,'usercount'=>$usercount);
		}
	}
        return $answer;

     
    }

}

