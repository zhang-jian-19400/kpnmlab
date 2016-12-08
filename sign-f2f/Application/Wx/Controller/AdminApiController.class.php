<?php

namespace Wx\Controller;
use Wx\Model\WechatModel as Wechat;
use Wx\Model\MobelPhoneModel as MobelPhone;

class AdminApiController extends AuthController
{
    public function _initialize()
    {
        parent::_initialize();

	/**
        // 验证管理员
        if(!D('Admin')->isAdmin($this->userId)) {
            $this->answer = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '您不是管理员...'
            );
            exit;
        }
	**/
    }

    // 获取二维码
    public function getQRCode($oldQRCode = '')
    {
        $prefix = C('REDIS.QRCODE_PREFIX');
        // 更新旧二维码
        if (!empty($oldQRCode)) {
            $key  = $prefix . $oldQRCode;
            $info = S($key);
            S($key, $info, C('qrcode_used') + 0);
        }
        //生成新二维码
        $code = getRandStr(6);
        $key  = $prefix . $code;
        S($key, array(), C('qrcode_max') + 0);
        $this->answer['data'] = array(
            'QRCode' => $code
        );
    }

    // 登记签到
    public function registerSign($num, $name = '')
    {
        // 验证姓名
        if (!empty($name)) {
            if (!D('Student')->vaildName($num, $name)) {
                return $this->answer = array(
                    'code' => C('RETURN.ERROR'),
                    'msg'  => '学号与姓名不匹配，请检查'
                );
            }
        }

        // 签到
        $this->answer = D('Sign')->sign($this->redis, $num, 2);
    }

    // 获取所有签到点信息
    public function getAllPlaces()
    {
        $this->answer['data'] = D('Places')->getAllPlace();
    }
    

    //创建用户签到空间
    public function createSignSpace($spacename='',$selectvalue='')
    {
	vendor('PHPExcel.PHPExcel');

        if (!empty($_FILES)) {
		$file = $_FILES['file'];
		$filename = $_FILES['file']['name'];
                $tempPath = $_FILES[ 'file' ][ 'tmp_name'];

		$file_array = explode ( ".", $file ["name"] );
                $file_extension = strtolower ( array_pop ( $file_array ) ); 
		$uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $_FILES[ 'file' ][ 'name' ];                

		vendor('PHPExcel.PHPExcel.Reader.Excel2007');
		vendor('PHPExcel.PHPExcel.Reader.Excel5');
		 
                $PHPExcel = new \PHPExcel();
		$PHPReader = new \PHPExcel_Reader_Excel2007();
		switch ($file_extension) {  
        		case "xls" :
			    $PHPReader = new \PHPExcel_Reader_Excel5();	
			case "xlsx":
			    $PHPReader = new \PHPExcel_Reader_Excel2007();
		}           
		//$fn = fopen ( $file ["tmp_name"], "rb" );
		$PHPExcel = $PHPReader->load($tempPath);
                $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
                $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
		$allRow = $currentSheet->getHighestRow(); //取得一共有多少行 
		$list= array(); 
		for($row = 2; $row <= $allRow; $row++)
		{
			for($column='A'; $column<=$allColumn; $column++)
			{
				$val = $currentSheet->getCellByColumnAndRow(ord($column)-65, $row)->getValue();
				$list[$row-1][]=$val;	
			}
		}

		
		if (!empty($selectvalue) && $selectvalue == "new")
		{
			 $this->answer['data'] = D('SignSpace') -> createSpace($this->redis, $spacename, $this->userId,$this->userName,$list);
			//$this->answer['data'] = array('info'=>count($list));		
		}
		else//覆盖已有空间
		{
			$redis  = $this->redis;
		        $global_users_key = 'global_users'.$selectvalue;	
			
			//删除缓存中该签到空间
			$redis->delete($global_users_key);	
			
			//重新建立该签到空间
			$users=array();
			for($row=1; $row<= count($list); $row++)
	                {
        	                $user = array('spaceid'=>$selectvalue,'usernum'=>$list[$row][0], 'username'=>$list[$row][1],'title'=>$list[$row][2]);
                	        //$users[$row][] = $user;
                       		array_push($users,$user);
                	}
				
			for($i=0; $i< count($users); $i++)
                        {
                                $currenttime = date('Y-m-d H:i:s');
                                $global_users_score = ('0.'.strtotime($currenttime)+0);
                                $global_users_value = json_encode($users[$i]);
                                $redis -> zadd($global_users_key, $global_users_score, $global_users_value);
                        }

			$deleteresult = D('SignSpacePeople')-> clearSignSpacePeople($selectvalue);//清空数据库中该空间的数据
                	$result = D('SignSpacePeople')->addAllPeople($users);//重新插入新的覆盖人员数据
			
			$this->answer['data'] = array('info'=>'update','users'=>$users);
		}

	/**
               $this-answer['data'] = $this->answer = D('SignSpace')->createSpace(
                        $this->redis, $spacename, $this->userId,$this->userName,$list);
 



        	$data = array(
                       		'status' => 'good',
                        	'filename'=> $filename,
                        	'spacename'=>$spacename,
                                'temppath' => $tempPath,
                                'extension'=> $file_extension,
				'uploadPath'=>$uploadPath,
				'column'=>$allColumn,
                	        'list'  => $list,
				'row'=>$allRow
        	);
		

        	$this->answer['data'] = $data;

	        	
        **/
	}
	else{
		$this->answer['data'] = array('info'=>'no file');
	}
    }
    
    //提交题目答案
    public function submitAnswer($launchid, $tlaunchid, $answer, $signname,$time,$usedseconds=0)
    {
        $phone = new MobelPhone();
        $ip = $phone->getClientIP();//移动端IP
        $browser = $phone->determinebrowser();//移动端浏览器及版本号
        $blang = $phone->getLang();//浏览器使用的语言
        $os = $phone->getOs();//操作系统类型与版本号

        $this->answer['data'] = D('TeachingAnswer')->submitAnswer($this->redis, $this->userId,$this->userName,
                        $launchid,$tlaunchid,$answer, $signname, $time, $usedseconds,$ip, $browser, $blang, $os);
          //$this->answer['data'] = array('answerresult'=>'ok');  	
    }

    //获取答题统计信息
    public function getAnswerStatisitcs($tlaunchid)
    {
	$redis  = $this->redis;
        $keyglobalallanswer = 'globalallanswer_'.$tlaunchid;
        $all_users = $redis->zrange($keyglobalallanswer, 0, -1);
        $usercount = count($all_users);
        $anwserstat = array(
            'usercount'=>$usercount,
	    'list'=>array()
        );
        for($i = 0; $i < count($all_users); $i++)
        {
            $array = json_decode($all_users[$i], true);
            $answer = $array[3];
	    if(!$anwserstat['list'][$answer])
                $anwserstat['list'][$answer] = 1;
            else
            	$anwserstat['list'][$answer] = $anwserstat['list'][$answer] +1;            
        }
        
        $this->answer['data'] = $anwserstat;
    }
   
    //获取所有答题用户答题信息
    public function getAllAnwserUsers($tlaunchid, $num='', $name='', $usedseconds='', $answer='')
    {
        $redis  = $this->redis;
        $keyglobalallanswer = 'globalallanswer_'.$tlaunchid;
        $start = -1;
        if(!empty($num))
        {
               $all_users = $redis->zrange($keyglobalallanswer, 0, -1);
               for($i = 0; $i < count($all_users); $i++)
               {
                       $array = json_decode($all_users[$i], true);
                       if($array[0] == $num)
                       {
                               $start = $i;
                               break;
                       }
               }

        }        
	
	$result = $redis->zrange($keyglobalallanswer, $start + 1, $start + C('num_of_page'));
        $usercount = count($redis->zrange($keyglobalallanswer, 0, -1));
        $data=array(
                 'count'=>$usercount,
                 'list'=>array()
         );
        for($i = 0; $i < count($result); $i++)
         {
                 $array = json_decode($result[$i], true);
                 $time = $array[2];
                 $h = floor($time/3600) . '';
                 $m = floor($time%3600/60) . '';
                 $s = ($time % 6) + '';
                 if(strlen($h)==1)$h='0'.$h;
                 if(strlen($m)==1)$m='0'.$m;
                 if(strlen($s)==1)$s='0'.$s;
		 $str = $h.':'.$m.':'.$s;

                 $data['list'][$i] = array(
                         'num'=>$array[0],
                         'name'=>$array[1],
                         'answer'=>$array[3],
                         'usedseconds'=>$str
                 );
         }
	$this->answer['data']=$data;
    }
     
    //获取题目状态
    public function getAnswerInfo($launchid,$signname='',$tlaunchid='')
    {
	$redis  = $this->redis;
        $data = array();
    
        $data['tlaunchid'] = $tlaunchid;
        $data['id'] = $launchid; 
        if(!empty($tlaunchid))
	{
		$keydetail = 'globalanswerquestiondetail_'.$tlaunchid.$this->userId;
                $re = $redis->zrange($keydetail,0,0);
                $reresult = json_decode($re[0], true);
                $data['hasAnswer']=true;
                $data['answer']=$reresult[0];
                $data['usedseconds']=$reresult[1];
                $data['tlaunchid'] = $tlaunchid;
                $data['id'] = $launchid;

                $keydetail2 = 'globalquestiondetail_'.$tlaunchid;
                $quesdetaillist2 = $redis->zrange($keydetail2,0,0);
                $quesdetail2 = json_decode($quesdetaillist2[0], true);
                $data['anwserstatus']=$quesdetail2[0];
                $launch_expire_key = 'launch_expire_key_' . $launchid;
       	 	$launch_value = S($launch_expire_key);
        	$hasExpire = true;
        	if($launch_value)
        	{
                	$hasExpire = false;
		}
		$data['hasExpire']=$hasExpire;
	}
        else
        {
        $launch_expire_key = 'launch_expire_key_' . $launchid;
        $launch_value = S($launch_expire_key);
        $hasExpire = true;
        if($launch_value)
        {
                $hasExpire = false;
        
	
        
        $allquestion = 'tlaunchquestionself_'.$launchid;
        $aqresult = $redis->zrange($allquestion,0,0);
         
		if(count($aqresult) != 0)
		{
		$data['hasQuestion']=true;
		$questions = json_decode($aqresult[0], true);
		$tlaunchid = $questions[0];
                $data['tlaunchid'] = $tlaunchid;
                $keydetail = 'globalquestiondetail_'.$tlaunchid;
                $quesdetaillist = $redis->zrange($keydetail,0,0);
                $quesdetail = json_decode($quesdetaillist[0], true);
                $anwserstatus = $quesdetail[0];
                $data['id']=$launchid;
		$data['anwserstatus']=$anwserstatus;


                //判断用户是否已经答过题
                $keyglobalallanswer = 'globalallanswer_'.$tlaunchid;
                $allanswerusers = $redis->zrange($keyglobalallanswer,0,-1);
                $flag = false;
		for($i = 0; $i < count($allanswerusers); $i++)
                {
			$user = json_decode($allanswerusers[0], true);
                        if($user[0] == $this->userId)
			{
				$flag= true;
                                $data['answer']=$user[3];
				$data['usedseconds']=$user[2];
				break;
			}
		}
		$data['hasAnswer']=$flag;

		}else{$data['hasQuestion']=false;}        
	    	$temparray = explode('<>',$launch_value);
           	$data['signname']=$signname;
            	$data['time']=date('Y-m-d H:i:s',$temparray[4]);
	}

	$data['hasExpire']=$hasExpire;
        }
	$this->answer['data']=$data;
 
    }
 
    //教学空间发题
    public function updateAnwserStatus($launchid, $anwserstatus, $anwser='',$notes='',$questionnum='',$signname='',$signtime='',$seconds=0,$create,$tlaunchid=-1)
    {
        $phone = new MobelPhone();
        $ip = $phone->getClientIP();//移动端IP
        $browser = $phone->determinebrowser();//移动端浏览器及版本号
        $blang = $phone->getLang();//浏览器使用的语言
        $os = $phone->getOs();//操作系统类型与版本号

       
        $this->answer['data'] = D('TeachingLaunch')->updateAnwserStatus(
                        $this->redis, $this->userId,$this->userName,$signname,$signtime,$seconds,
                        $create,$tlaunchid,$launchid,$anwserstatus,$anwser,$notes,$questionnum,$ip, $browser, $blang, $os
        );
       //$this->answer['data']=array('launchresult'=>'ok');
    }


    //获取签到空间详细列表
    public function getSignSpaceDetail($spaceid,$spacename='', $num='', $username='', $title='')
    {
	$redis  = $this->redis;
	$usercount = 0;
	$global_users_key = 'global_users'.$spaceid;
	
	$start = -1;
          if(!empty($num) && !empty($username) && !empty($title))
          {      
                 $all_spaces = $redis->zrange($global_users_key, 0, -1);
                 for($i = 0; $i < count($all_spaces); $i++)
                 {       
                         $array = json_decode($all_spaces[$i], true);
                         if($array['usernum'] == $num)
                         {       
                                 $start = $i;
                                 break;
                         }
                 }
          
          }

	$result = $redis->zrange($global_users_key, $start + 1, $start + C('num_of_page'));
        $usercount = count($redis->zrange($global_users_key, 0, -1));
	$data=array( 
                 'usercount'=>$usercount,
                 'spaceid'=>$spaceid,
                 'spacename'=>$spacename,
                 'email'=>$this->email,
                 'list'=>array(),
                 'spacesign'=>array()
         ); 
	for($i = 0; $i < count($result); $i++)
         {
                 $array = json_decode($result[$i], true);
                 $data['list'][$i] = array(
                         'num'=>$array['usernum'],
                         'username'=>$array['username'],
                         'title'=>$array['title']
                 );
         }
	
	//获取该空间所有关联的签到信息
	$spaceToSigns_key = "spaceToSigns_key"+$spaceid;
        $spaceresult = $redis->zrange($spaceToSigns_key, 0, -1);
        $spacecount = count($spaceresult);
        $data['spacecount']=$spacecount;
        for($i = 0; $i < count($spaceresult); $i++)
	{
		$signid = $spaceresult[$i];
		$launch_forever_key = 'launch_forever_key_' . $signid;
		$launch_forever_array = $redis->zrange($launch_forever_key,0,0);
		$temparray = json_decode($launch_forever_array [0], true);
		$signname = $temparray[0];
		$ymd = explode(' ',$temparray[4])[0];
                $data['spacesign'][$i]=array(
			'signname'=>$signname,
			'signdate'=>$ymd
		);		

	}

	$this->answer['data'] = $data;

    }
    
    //更新签到-签到空间配置表
    public function updataSpaceTable($signid, $spacevalue='')
    {
	$redis = $this->redis;
	$spaceTable_key = "spaceTable_key".$signid;
	
	$result = $redis->zrange($spaceTable_key, 0, -1);
	
        $previouscount=count($result);
        $currentcount = 0;

        $values = explode(",", $spacevalue);

	if(empty($spacevalue))
	{
		if($previouscount > 0)
		{
			$redis->delete($spaceTable_key);	
		}
		/**
		//将签到空间与该空间下的所有签到关联起来
		for($i =0; $i<count($values); $i++)
		{
			$spaceToSigns_key = "spaceToSigns_key"+$values[$i];
  			$currenttime = date('Y-m-d H:i:s');
                        $spaceToSigns_score = ('0.'.strtotime($currenttime)+0);
                        $redis->zadd($spaceToSigns_key, $spaceToSigns_score, $signid);	
		}
                **/
	}
	else
	{
		/**
                //删除空间与签到关联表中 ID为 $signid的关联                
                for ($i = 0; $i < count($result); $i++)
        	{
			$array = json_decode($result[$i], true);
                        $spaceid = $array[0];
			$spaceToSigns_key = "spaceToSigns_key"+$spaceid;
                        	
                        $delid = ('-0.'.$signid+0);
                        $redis->zRemRangeByScore($spaceToSigns_key,$delid,$delid);			
                	
        	}**/


		//先删除旧的配置表
		$redis->delete($spaceTable_key);
		
		//重建配置表
		for($i =0; $i<count($values); $i++)
		{

			$currenttime = date('Y-m-d H:i:s');
                        $spaceTable_score = ('0.'.strtotime($currenttime)+0);
                        $redis -> zadd($spaceTable_key, $spaceTable_score, $values[$i]);
		}
		$currentcount = count($redis->zrange($spaceTable_key, 0, -1));		
	}

	//删除空间与签到关联表中 ID为 $signid的关联                
                for ($i = 0; $i < count($result); $i++)
                {
                        $array = json_decode($result[$i], true);
                        $spaceid = $array;
                        $spaceToSigns_key = "spaceToSigns_key"+$spaceid;

                        $delid = $signid+0;
                        $redis->zRemRangeByScore($spaceToSigns_key,$delid,$delid);

                }


	//将签到空间与该空间下的所有签到关联起来
        for($i =0; $i<count($values); $i++)
        {
            $spaceToSigns_key = "spaceToSigns_key"+$values[$i];
            $spaceToSigns_score = $signid+0;
            $redis->zadd($spaceToSigns_key, $spaceToSigns_score, $signid);
        }
	
	$this->answer['data']=array(
			'previouscount'=>$previouscount,
			'currentcount'=>$currentcount
		);
    }


    //获取用户签到空间
    public function getSignSpace($spaceid='', $spacename='', $usercount='')
    {
         $redis  = $this->redis;
	 $user_signspace_key = "all_signspace". $this->userId;
	 /**
	 $start = -1;
	 if(!empty($spaceid) && !empty($spacename) && !empty($usercount))
	 {
	 	$all_spaces = $redis->zrange($user_signspace_key, 0, -1);
		for($i = 0; $i < count($all_spaces); $i++)
		{
			$array = json_decode($all_spaces[$i], true);
			if($array[0] == $spaceid)
			{
				$start = $i;
				break;
			}
		}
	
	 }
	 **/
	 //$result = $redis->zrange($user_signspace_key, $start + 1, $start + C('num_of_page'));
         $result = $redis->zrange($user_signspace_key, 0, -1);
	 $spacecount  = count($result);
	 //$spacecount = count($redis->zrange($user_signspace_key, 0, -1));
	 $data=array(
			'spacecount'=>$spacecount,
			'list'=>array()
		);	 
	for ($i = 0; $i < count($result); $i++) 
	{
		$array = json_decode($result[$i], true);
		$data['list'][$i] = array(
			'spaceid'=>$array[0],
			'spacename'=>$array[1],
			'usercount'=>$array[2]
		);
	}
	$this->answer['data'] = $data;
    }    

    //获取参与签到列表
    public function getSignHistory($signid = '', $recordId='', $signname = '', $time = '', $incode='')
    {
    	$redis  = $this->redis;
    	$prefix = C('REDIS.PREFIX');
    	$key_sign   = 'selfsign_' . $this->userId;
    	$start = -1;
    	if (!empty($signid) && !empty($recordId) && !empty($signname) && !empty($time)&& !empty($incode)) 
	{
    		$all_signhis = $redis->zrange($key_sign,0,-1);
	        for($i = 0; $i < count($all_signhis); $i++)
		{
        	        $array = json_decode($all_signhis[$i], true);
			$shimin = $array[0];
               		if(($array[0] == $signid))
			{
				$start = $i;
                        	break;
                	}

        	}
		#$last  = json_encode(array($signid, $recordId, $signname, $time, $incode));
    		#$start = $redis->zrank($key_sign, $last);
    	}


    	$result = $redis->zrange($key_sign, $start + 1, $start + C('num_of_page'));
    	$count  = $redis->zcount($key_sign, -1, 0);
    	$data = array(
    			'count' => $count,
    			'list'  => array()
    	);
    	for ($i = 0; $i < count($result); $i++) {
    		$array = json_decode($result[$i], true);

		$launch_forever_key = 'launch_forever_key_' . $array[0];
        $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
        $temparray0 = json_decode($launch_forever_array [0], true);
        $signname0 = $temparray0[0];
                $isSee0 = $temparray0[3];

    		$data['list'][$i] = array(
    				'signid'  => $array[0],
    				'recordId' => $array[1],
    				'signname' => $signname0,
    				'time'  => $array[3],
    				'incode' => $array[4],
				'isseen'=>$isSee0,
				'uname'=>$this->userName
    		);
    	}
	#$tempkey = "selfsign_1050101";
	#$redis->delete($key_sign); 
    	$this->answer['data'] = $data;
    }

    //教学空间管理
    public function getQuestionInfo($launchid,$tlaunchid)
    {
	$redis  = $this->redis;
	$launch_expire_key = 'launch_expire_key_'.$launchid;
        $launch_value = S($launch_expire_key);
        $hasExpire = true;
        if($launch_value)
        {
                $hasExpire = false;
        }    	

	$keydetail = 'globalquestiondetail_'.$tlaunchid;
	$questiondetail = $redis->zrange($keydetail, 0, 0);
        $array = json_decode($questiondetail[0], true);
	
	$data=array(
		'hasExpire'=>$hasExpire,
		'anwserstatus'=>$array[0],
                'anwser'=>$array[1],
                'seconds'=>$array[2],
                'notes'=>$array[3],
                'questionnum'=>$array[4],
                'starttime'=>$array[5]
	);

	$this->answer['data']=$data;

    }


    //教学空间管理
    public function getTeachingSpace()
    {
        $redis  = $this->redis;
        $prefix = C('REDIS.PREFIX');

        //小面查找用户最近一次发起的，目前还未失效的 签到信息
        $key_launch   = 'launch_' . $this->userId;
        $finallaunch = $redis->zrange($key_launch, 0, 0);
        $array = json_decode($finallaunch[0], true);
        $signid = $array[0];
        $signname = $array[1];
	$signtime = $array[3];
        $launch_expire_key = 'launch_expire_key_' . $signid;
        $launch_value = S($launch_expire_key);
        $hasExpire = true;
	if($launch_value)
        {
		$hasExpire = false;
	}        

        $data = array(
			'signid'=>$signid,
                        'key_launch'=> $key_launch,
			'hasExpire'=>$hasExpire,
			'signname'=>$signname,
			'signtime'=>$signtime,
 			'signlist'=> array()
		);
 
        //下面查找用参与的，目前还有效的 所有签到信息
 	$launchs = D('SignLaunchTemp')->getAllLaunch(); 
	$index = 0;       
        for($j=0; $j < count($launchs); $j++)
	{
		$id = $launchs[$j]['id'];//发起签到的ID
		$launch_key = 'launch_expire_key_' . $id;
		$launch_result = S($launch_key);
        	if($launch_result)//判断是否过期
		{
			$key_global = 'global_'.$id;
			$all_signlist = $redis->zrange($key_global,0,-1);
                	for($i = 0; $i < count($all_signlist); $i++)
                	{
                        	$array_signlist = json_decode($all_signlist[$i], true);
                        	//判断用户是否已近签到
                        	if($array_signlist[0] == $this->userId)
				{
					$temparray = explode('<>',$launch_result);
		                        $signname = $temparray[0];
					array_push($data['signlist'],array('id'=>$id,'signname'=>$signname));
				}
			}
		}
	}
        $this->answer['data'] = $data;
    }

    //获取答题列表
    public function getAnswerList($launchid, $tlaunchid = '', $time = '')
    {
	$redis  = $this->redis;
        $allquestion = 'tanswerquestionself_'.$launchid.$this->userId;
        $start = -1;
        if(!empty($tlaunchid) && !empty($time))
        {
                $all_questions = $redis->zrange($allquestion,0,-1);
                for($i = 0; $i < count($all_questions); $i++)
                {
                        $array = json_decode($all_questions[$i], true);
                        if(($array[0] == $tlaunchid))
                        {
                                $start = $i;
                                break;
                        }
                }
        } 
        $result = $redis->zrange($allquestion, $start + 1, $start + C('num_of_page'));
        $count  = $redis->zcount($allquestion, -1, 0);
        $data = array(
                        'count' => $count,
                        'anid'=>$launchid,
                        'list'  => array()
        );
        for ($i = 0; $i < count($result); $i++) {
                $array = json_decode($result[$i], true);
                $keydetail = 'globalquestiondetail_'.$array[0];
                $keyresult = $redis->zrange($keydetail, 0, 0);
                $temparray = json_decode($keyresult[0], true);
                $data['list'][$i] = array(
                                'launchid'=>$launchid,
                                'tlaunchid'  => $array[0],
                                'time' => explode(' ',$array[1])[1],
                                'notes'=>$temparray[3],
                                'questionnum'=>$temparray[4]
                );
        }
        //$temkey = 'launch_1050101';
        #$redis->delete($key_launch);   
        $this->answer['data'] = $data;
    }

    //获取发起答题列表
    public function getDistributeList($launchid, $tlaunchid = '', $time = '')
    {
	$redis  = $this->redis;
        $key = 'tlaunchquestionself_'.$launchid;
        $start = -1;
 	if(!empty($tlaunchid) && !empty($time))
        {
                $all_questions = $redis->zrange($key,0,-1);
                for($i = 0; $i < count($all_questions); $i++)
                {
                        $array = json_decode($all_questions[$i], true);
                        if(($array[0] == $tlaunchid))
                        {
                                $start = $i;
                                break;
                        }
                }
        }
	$result = $redis->zrange($key, $start + 1, $start + C('num_of_page'));
        $count  = $redis->zcount($key, -1, 0);
        $data = array(
                        'count' => $count,
                        'list'  => array()
        );
        for ($i = 0; $i < count($result); $i++) {
                $array = json_decode($result[$i], true);

                $keydetail = 'globalquestiondetail_'.$array[0];
		$keyresult = $redis->zrange($keydetail, 0, 0);
                $temparray = json_decode($keyresult[0], true);

                $data['list'][$i] = array(
                                'launchid'=>$launchid,
                                'tlaunchid'  => $array[0],
                                'time' => explode(' ',$array[1])[1],
                                'notes'=>$temparray[3],
                                'questionnum'=>$temparray[4]
                );
        }
        //$temkey = 'launch_1050101';
        #$redis->delete($key_launch);   
        $this->answer['data'] = $data;
    }

    //获取发起答题列表
    public function getAnswerHistory($launchid = '', $signname = '', $signtime = '')
    {
	$redis  = $this->redis;
	$answerselfkey = "answerselfkey_".$this->userId;
	$start = -1;
	if(!empty($launchid) && !empty($signname) && !empty($signtime))
	{
	        $all_answers = $redis->zrange($answerselfkey,0,-1);
                for($i = 0; $i < count($all_answers); $i++)
                {
                        $array = json_decode($all_answers[$i], true);
                        if(($array[0] == $launchid))
                        {
                                $start = $i;
                                break;
                        }
                }	
	}
	$result = $redis->zrange($answerselfkey, $start + 1, $start + C('num_of_page'));
        $count  = $redis->zcount($answerselfkey, -1, 0);
        
        $data = array(
                        'count' => $count,
                        'list'  => array()
        );
        for ($i = 0; $i < count($result); $i++) {
                $array = json_decode($result[$i], true);

		$launch_forever_key = 'launch_forever_key_' . $array[0];
                $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
                $temparray0 = json_decode($launch_forever_array [0], true);
                $signname0 = $temparray0[0];

                $data['list'][$i] = array(
                                'launchid'  => $array[0],
                                'signname' => $signname0,
                                'signtime'=> $array[2]
                );
        }
        //$temkey = 'launch_1050101';
        #$redis->delete($key_launch);   

        $this->answer['data'] = $data;

    }

    //获取发起答题列表
    public function getDistributeHistory($launchid = '', $signname = '', $signtime = '')
    {
	$redis  = $this->redis;
        $prefix = C('REDIS.PREFIX');
	$key = 'tsignlaunchself_'.$this->userId;
        $start = -1;
        if(!empty($launchid) && !empty($signname) && !empty($signtime))
	{
		$all_distributes = $redis->zrange($key,0,-1);
		for($i = 0; $i < count($all_distributes); $i++)
                {
                        $array = json_decode($all_distributes[$i], true);
                        if(($array[0] == $launchid))
                        {
                                $start = $i;
                                break;
                        }
                }
	}
	$result = $redis->zrange($key, $start + 1, $start + C('num_of_page'));
        $count  = $redis->zcount($key, -1, 0);
        $data = array(
                        'count' => $count,
                        'list'  => array()
        );
        for ($i = 0; $i < count($result); $i++) {
                $array = json_decode($result[$i], true);

                $launch_forever_key = 'launch_forever_key_' . $array[0];
                $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
                $temparray0 = json_decode($launch_forever_array [0], true);
                $signname0 = $temparray0[0];

                $data['list'][$i] = array(
                                'launchid'  => $array[0],
                                'signname' => $signname0,
                                'signtime'=> $array[2]
                );
        }
        //$temkey = 'launch_1050101';
        #$redis->delete($key_launch);   

        $this->answer['data'] = $data;
    }

    //获取发起签到列表
    public function getLaunchHistory($signid = '', $dfilename = '', $gencoderesult='', $time = '')
    {
    	$redis  = $this->redis;
    	$prefix = C('REDIS.PREFIX');
    	$key_launch   = 'launch_' . $this->userId;
    	$start = -1;
    	if (!empty($signid) && !empty($dfilename) && !empty($gencoderesult) && !empty($time)) {
	
		$all_launchhis = $redis->zrange($key_launch,0,-1);
                for($i = 0; $i < count($all_launchhis); $i++)
                {
                        $array = json_decode($all_launchhis[$i], true);
                        if(($array[0] == $signid))
                        {
                                $start = $i;
                                break;
                        }

                }
    		//$last  = json_encode(array($signid, $dfilename, $gencoderesult, $time));
    		//$start = $redis->zrank($key_launch, $last);
    	}
    	$result = $redis->zrange($key_launch, $start + 1, $start + C('num_of_page'));
    	$count  = $redis->zcount($key_launch, -1, 0);
    	$data = array(
    			'count' => $count,
    			'list'  => array()
    	);
    	for ($i = 0; $i < count($result); $i++) {
    		$array = json_decode($result[$i], true);

		$launch_forever_key = 'launch_forever_key_' . $array[0];
                         $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
                         $temparray0 = json_decode($launch_forever_array [0], true);
                         $signname0 = $temparray0[0];
                         $haveEnd0 = $temparray0[2];
                         $isSee0 = $temparray0[3];

    		$data['list'][$i] = array(
    				'signid'  => $array[0],
    				'dfilename' => $signname0,
				'gencoderesult'=> $array[2],
    				'time' => $array[3],
				'signstatus' => $haveEnd0,
				'issee' => $isSee0
    		);
    	}
	//$temkey = 'launch_1050101';
	#$redis->delete($key_launch);	

    	$this->answer['data'] = $data;
    }
  

    //更改用户是否可见
    public function updateIsSeen($signid, $isseen)
    {
    	$redis  = $this->redis;
    	
    	//更改永久缓存表中的发起签到信息
    	$launch_forever_key = 'launch_forever_key_' . $signid;
    	$launch_forever_array = $redis->zrange($launch_forever_key,0,0);
    	$temparray0 = json_decode($launch_forever_array [0], true);
    	$signname0 = $temparray0[0];
    	$signcode0 = $temparray0[1];
    	$haveEnd0 = $temparray0[2];
	$isSee0 = $temparray0[2];
	if($isseen=='true')
	{	
		$isSee0 = 1;
	}else
	{
		$isSee0 = 0;
	}   
    	
    	$launch_forever_new_array =json_encode(array($signname0,$signcode0,$haveEnd0,$isSee0,$temparray0[4]));
    	$redis->delete($launch_forever_key);
    	$redis->zadd($launch_forever_key, -0.9, $launch_forever_new_array);
    	
    	
    	
    	
    	$launch_expire_key = 'launch_expire_key_' . $signid;
    	$launch_value = S($launch_expire_key);
    	$data = array();
    	if($launch_value)
    	{
    		$temparray = explode('<>',$launch_value);
    		$signname = $temparray[0];
    		$signcode = $temparray[1];
    		$haveEnd = $temparray[2];
    		//$isSee = $temparray[3];
    		$launchtime = $temparray[4];
    	
    		//计算签到离过期还剩多长时间
    		//$now=strtotime(date('y-m-d h:i:s'));
    		//$lefttime=floor(($now-$launchtime)/(1000));

                $now=strtotime(date('Y-m-d H:i:s'));
                $lefttime=C('sign_expire')-($now-$launchtime);
               // if($lefttime<=0) $lefttime = 1;
    	
    		//更新redis
    		//$redis->delete($launch_expire_key);
    		if($lefttime>0){
    		$launch_expire_array =$signname.'<>'.$signcode.'<>'.$haveEnd.'<>'.$isSee0.'<>'.$launchtime;
    		S($launch_expire_key, $launch_expire_array, $lefttime);
    		}
		else
		{
			$launch_expire_array =$signname.'<>'.$signcode.'<>'.$haveEnd.'<>'.$isSee0.'<>'.$launchtime;
                S($launch_expire_key, $launch_expire_array, 1);
		}
    		//更新数据库
    		//D('SignLaunchTemp')->updateSignName($signid,$signname);
    	
    		$data = array(
    				'result' => 'ok',
                                'lt'=>$lefttime,
                                'now'=>C('sign_expire'),
                                'ct'=>$launchtime,
                                'lat'=>strtotime(date('Y-m-d H:i:s')),
				'key'=>$launch_expire_key
    		);
    	}
    	else
    	{
    		$data = array(
    				'result' => 'error'
    		);
    	}
    	$this->answer['data']=$data;
    }
 
     //更改签到命名
    public function updateSignName($signid, $signname)
    {
    	$redis  = $this->redis;

	//更改永久缓存表中的发起签到信息
        $launch_forever_key = 'launch_forever_key_' . $signid;
        $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
        $temparray0 = json_decode($launch_forever_array [0], true);
        $signname0 = $temparray0[0];
                $signcode0 = $temparray0[1];
                $haveEnd0 = $temparray0[2];
                $isSee0 = $temparray0[3];

        $launch_forever_new_array =json_encode(array($signname,$signcode0,$haveEnd0,$isSee0,$temparray0[4]));
        $redis->delete($launch_forever_key);
        $redis->zadd($launch_forever_key, -0.9, $launch_forever_new_array);



 
    	$launch_expire_key = 'launch_expire_key_' . $signid;
    	$launch_value = S($launch_expire_key);
    	$data = array();
    	if($launch_value)
    	{
    		$temparray = explode('<>',$launch_value);
    		$signcode = $temparray[1];
    		$haveEnd = $temparray[2];
    		$isSee = $temparray[3];
    		$launchtime = $temparray[4];
    	
    		//计算签到离过期还剩多长时间
    		$now=strtotime(date('Y-m-d H:i:s'));
    		//$lefttime=floor(($now-$launchtime)/(1000));
		//$lefttime=floor(C('sign_expire')-floor($now-$launchtime));
                $lefttime=C('sign_expire')-($now-$launchtime);
		//if($lefttime<=0) $lefttime = 1;
    	
    		//更新redis
		//$redis->delete($launch_expire_key);
    		if($lefttime>0){
    		$launch_expire_array =$signname.'<>'.$signcode.'<>'.$haveEnd.'<>'.$isSee.'<>'.$launchtime;
    		S($launch_expire_key, $launch_expire_array, $lefttime);
    		}
		else
		{
			 $launch_expire_array =$signname.'<>'.$signcode.'<>'.$haveEnd.'<>'.$isSee0.'<>'.$launchtime;
                S($launch_expire_key, $launch_expire_array, 1);
		}
    		//更新数据库
    		D('SignLaunchTemp')->updateSignName($signid,$signname);
    	
    		$data = array(
    				'result' => 'ok'
    		);
    	}
    	else
    	{
    		$data = array(
    				'result' => 'error'
    		);
    	}
	$this->answer['data']=$data;
    }

 
    //更改用户请求的签到状态 （签到中或已结束）
    //更改缓存中的状态即可
    public function updateSignStatus($signid, $signstatus)
    {
    	$redis  = $this->redis;
    	
	//更改永久缓存表中的发起签到信息
        $launch_forever_key = 'launch_forever_key_' . $signid;
        $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
	$temparray0 = json_decode($launch_forever_array [0], true);
	$signname0 = $temparray0[0];
               	$signcode0 = $temparray0[1];
                $haveEnd0 = $temparray0[2];
                $isSee0 = $temparray0[3];

                //更改签到状态
                if($signstatus=='true')
                {
                        $haveEnd0 = 0;//已结束
                }
                elseif ($signstatus=='false')
                {
                        $haveEnd0 = 1;//签到中
                }

	$launch_forever_new_array =json_encode(array($signname0,$signcode0,$haveEnd0,$isSee0,$temparray0[4]));
	$redis->delete($launch_forever_key);
	$redis->zadd($launch_forever_key, -0.9, $launch_forever_new_array);
	
    	$launch_expire_key = 'launch_expire_key_' . $signid;
    	$launch_value = S($launch_expire_key);
    	$data = array();
    	if($launch_value)
    	{
    		$temparray = explode('<>',$launch_value);
    		$signname = $temparray[0];
    		$signcode = $temparray[1];
    		$haveEnd = $temparray[2];
    		$isSee = $temparray[3];
    		$launchtime = $temparray[4];
    		
    		//计算签到离过期还剩多长时间
    		$now=strtotime(date('Y-m-d H:i:s'));
    		//$lefttime=floor(C('sign_expire')-floor($now-$launchtime));
                $lefttime=C('sign_expire')-($now-$launchtime);
		//if($lefttime<=0) $lefttime = 1;  		
    		//更改签到状态
    		if($signstatus=='true')
    		{
    			$haveEnd = 0;//已结束
    		}
    		elseif ($signstatus=='false')
    		{
    			$haveEnd = 1;//签到中
    		}
    		
    		//更新redis
    		//$redis->delete($launch_expire_key);
    		if($lefttime>0){
    		$launch_expire_array =$signname.'<>'.$signcode.'<>'.$haveEnd.'<>'.$isSee.'<>'.$launchtime;
    		S($launch_expire_key, $launch_expire_array, $lefttime);
    		}
		else
		{
			 $launch_expire_array =$signname.'<>'.$signcode.'<>'.$haveEnd.'<>'.$isSee0.'<>'.$launchtime;
                S($launch_expire_key, $launch_expire_array, 1);
		}
	
    		$data = array(
    				'result' => 'ok',
				'lefttime'=>$launch_expire_array,
				'signstatus'=>$signstatus
    		);
    	}
    	else 
    	{
    		$data = array(
    				'result' => 'error'
    		);
    	}
    	$this->answer['data']=$data;
    }
    
    //获取签到列表
    public function getSignList($signid, $signname, $num = '', $username = '', $time = '')
    {
    	$redis  = $this->redis;
    	$prefix = C('REDIS.PREFIX');

	//判断该用户是否为该签到的发起者
	$isLaunch = false;
	$launch_key = 'launch_'.$this->userId;
	$all_launchhis = $redis->zrange($launch_key,0,-1);
        for($i = 0; $i < count($all_launchhis); $i++)
        {
            $array = json_decode($all_launchhis[$i], true);
            if(($array[0] == $signid))
            {       
                $isLaunch = true;
                break;
            }
        }
	//检查对应的签到是否已经结束
	$haveEnd = true;
	$isSee = false;
        $haveExpire = false;
		$launch_expire_key = 'launch_expire_key_' . $signid;
        	$launch_value = S($launch_expire_key);	
		if($launch_value)
		{
		        $temparray = explode('<>',$launch_value);
			$signname = $temparray[0];
			$signcode = $temparray[1];
			$ymd = date("Y-m-d",$temparray[4]);
			//$ymd = $launch_value
			if($temparray[2] == 1)$haveEnd = false;
			if($temparray[3] == 1)$isSee = true;
		}
		else//如果已经结束则 同步数据库中的信息
		{
			#D('SignLaunchTemp')->saveLaunch($signid);
			//更改永久缓存表中的发起签到信息
        		$launch_forever_key = 'launch_forever_key_' . $signid;
       			 $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
       			 $temparray0 = json_decode($launch_forever_array [0], true);
       			 $signname0 = $temparray0[0];
               		 $signcode0 = $temparray0[1];
              		 $haveEnd0 = $temparray0[2];
               		 $isSee0 = $temparray0[3];
 			 $ymd = explode(' ',$temparray0[4])[0];
			 //$ymd = date("Y-m-d",$temparray0[4]);
			 //$ymd=$temparray0[4];
			 $launch_forever_new_array =json_encode(array($signname0,$signcode0,'0',$isSee0,$temparray0[4]));
                         $redis->delete($launch_forever_key);
                         $redis->zadd($launch_forever_key, -0.9, $launch_forever_new_array);
			 
 			 $haveExpire = true;
                         $signname = $signname0;
                         $signcode =$signcode0;
                         //$signname = 'test';
                         $haveEnd = true;
                         if($temparray0[3] == 1)$isSee = true;
		}

	/**
	else
	{
		$launch_forever_key = 'launch_forever_key_' . $signid;
                         $launch_forever_array = $redis->zrange($launch_forever_key,0,0);
                         $temparray0 = json_decode($launch_forever_array [0], true);
		$signname0 = $temparray0[0];
		$signcode = $temparray0[1];
	}**/


	        $data = array(
                        'signid'  => $signid,
                        'signname' => $signname,
                        'signcode' => $signcode,
                        'isLaunch' => $isLaunch,
                        'haveEnd'  => $haveEnd,
                        'isSee'   =>  $isSee,
                        'haveExpire' => $haveExpire,
                        'ymd'=>$ymd,
                        'email'=>$this->email,
                        'list'  => array(),
                        'spacelist'=>array()
        );

    	$key_global   = 'global_' . $signid;
    	$start = -1;
    	if (!empty($num) && !empty($username) && !empty($time)) {
    		//$last  = json_encode(array($num, $username, $time));
    		//$start = $redis->zrank($key_global, $last);

                  
		$all_signlist = $redis->zrange($key_global,0,-1);
                for($i = 0; $i < count($all_signlist); $i++)
                {
                        $array_signlist = json_decode($all_signlist[$i], true);
			//substr_replace($array_signlist[0], '****',-5,-1)
                        if((substr_replace($array_signlist[0], '****',-5,-1) == $num)&&$array_signlist[1] == $username&&explode(' ',$array_signlist[2])[1] == $time)
                       	#if(($array_signlist[0] == $num)&&$array_signlist[1] == $username&&explode(' ',$array_signlist[2])[1] == $time)
			{
                                $start = $i;
                                break;
                        }

                }
    	}
	else
	{
                /**
		        //获取签到空间中的所有用户
        	$spaceTable_key = "spaceTable_key".$signid;
        	$spacevalues = $redis->zrange($spaceTable_key,0,-1);
        	$piscount = 0;
        	$peopleinspace = array();//保存签到空间中的人员列表

        	for($i = 0; $i < count($spacevalues); $i++)
        	{
                	$svalue = $spacevalues[$i];//具体签到空间人员列表主键
                	$global_users_key = 'global_users'.$svalue;

                	$spacepeople = $redis->zrange($global_users_key, 0, -1);

                	for($j = 0; $j < count($spacepeople); $j++)
                	{
                        	 $arrayp = json_decode($spacepeople[$j], true);
                        	 $pis = array(
                                	'num'=>$arrayp['usernum'],
                                	'username'=>$arrayp['username'],
                                	'time'=>'----',
                               		'isSelf'=>false,
                                	'isInSpace'=>true,
                                	'haveSign'=>'no'
                        	);
                        	array_push($peopleinspace, $pis);
                        	$piscount ++;
                	}
        	}
        	$data['peopleinspace'] = $peopleinspace;
        	$data['piscount']=$piscount;
		**/

		//获取用户的签到空间
         	$user_signspace_key = "all_signspace". $this->userId;

         	$result = $redis->zrange($user_signspace_key, 0, -1);
         	$spacecount = count($result);
         	$data['spacecount']=$spacecount;

        	for ($i = 0; $i < count($result); $i++)
        	{
                	$array = json_decode($result[$i], true);
                	$data['spacelist'][$i] = array(
                        	'spaceid'=>$array[0],
                        	'spacename'=>$array[1],
                        	'usercount'=>$array[2]
                	);
        	}


	}
        $result = array();
        if (!empty($num) && !empty($username) && !empty($time)) {
		$result = $redis->zrange($key_global, $start + 1, $start + C('num_of_page'));
	}
	else
	{
		$result = $redis->zrange($key_global, 0, -1);
	}
    	//$result = $redis->zrange($key_global, $start + 1, $start + C('num_of_page'));
    	//$count  = $redis->zcount($key_global,-1,0);
	$count = count($redis->zrange($key_global,0,-1));    	
	$data['count']=$count;
	/**
    	$data = array(
    			'signid'  => $signid,
    			'signname' => $signname,
			'signcode' => $signcode,
			'count'   => $count,
			'isLaunch' => $isLaunch,
			'haveEnd'  => $haveEnd,
			'isSee'   =>  $isSee,
			'haveExpire' => $haveExpire,
			'ymd'=>$ymd,
			'email'=>$this->email,
    			'list'  => array(),
                        'spacelist'=>array()
    	);
	**/

	//下面将签到空间中用户集合与真实签到的用户进行合并

        $spaceids=array();	
        //获取签到空间中的所有用户
        $spaceTable_key = "spaceTable_key".$signid;
	$spacevalues = $redis->zrange($spaceTable_key,0,-1);
        $piscount = 0;	
	$peopleinspace = array();//保存签到空间中的人员列表
	
	for($i = 0; $i < count($spacevalues); $i++)
	{
		$svalue = $spacevalues[$i];//具体签到空间人员列表主键
        	$spaceids[$i]=$svalue; 
	        $global_users_key = 'global_users'.$svalue;
		
		$spacepeople = $redis->zrange($global_users_key, 0, -1);
        	
        	for($j = 0; $j < count($spacepeople); $j++)
         	{
			 #$usernum = substr_replace($arrayp['usernum'], '****',-5,-1);
                	 $arrayp = json_decode($spacepeople[$j], true);
			 $usernum = substr_replace($arrayp['usernum'], '****',-5,-1);
                 	 $pis = array(
                         	'num'=>$usernum,
                         	'username'=>$arrayp['username'],
				'time'=>'----',
				'isSelf'=>false,
				'isInSpace'=>true,
				'haveSign'=>'no'
                 	);
			array_push($peopleinspace, $pis);
			$piscount ++;
         	}
	}
	$data['peopleinspace'] = $peopleinspace;
	$data['piscount']=$piscount;
	$data['spaceids']=$spaceids;

        //获取已签到的用户
	$listIndex = 0;
    	for ($i = 0; $i < count($result); $i++) {
    		$array = json_decode($result[$i], true);
                $isSelf = false;
                if($this->userId == $array[0])$isSelf = true;
		/**
		$usernum = $array[0];
                $username = $array[1];
		
                //判断当前人员是否在签到空间列表中
                $flag = false;
		for($k=0; $k <count($peopleinspace); $k++)
		{
			$spacenum = $peopleinspace[$k]['num'];
			$spacename = $peopleinspace[$k]['username'];
			if(($usernum == $spacenum) && ($username == $spacename))
			{
				$flag = true;
				$peopleinspace[$k]['haveSign']='yes';
				break;
			}
		}		
		**/
		$usernum = substr_replace($array[0], '****',-5,-1);
    		$data['list'][$i] = array(
    				'num'  => $usernum,
    				'username' => $array[1],
    				'time' => explode(' ',$array[2])[1],
				'isSelf' => $isSelf,
				'isInSpace'=>false,
				'haveSign'=>'yes'
    		);
		$listIndex = $i;//保存数组当前位置 用于后续继续添加数据
    	}
        
	/**
	for($j = 0; $j < count($peopleinspace); $j++)
	{
		$hs = $peopleinspace[$j]['haveSign'];
		if($hs == 'no')
		{
			$listIndex ++;
			$peopleinspace[$j]['num']=substr_replace($peopleinspace[$i]['num'], '****',-5,-1);
			$data['list'][$listIndex] = $peopleinspace[$j];
		}
	}**/
	/**
 	 //获取用户的签到空间
         $user_signspace_key = "all_signspace". $this->userId;
         
	 $result = $redis->zrange($user_signspace_key, 0, -1);
         $spacecount = count($result);
         $data['spacecount']=$spacecount;
         
        for ($i = 0; $i < count($result); $i++)
        {
                $array = json_decode($result[$i], true);
                $data['spacelist'][$i] = array(
                        'spaceid'=>$array[0],
                        'spacename'=>$array[1],
                        'usercount'=>$array[2]
                );
        }
	**/
    	#$redis->delete($key_global);
    	$this->answer['data'] = $data;
    }


    // 获取某个签到点的签到情况
    public function getOnePlace($id, $num = '', $name = '', $time = '')
    {
        if ($id == "add") {
            $placeName = "手动登记";
        } elseif ($id == "qrcode") {
            $placeName = "扫码签到";
        } else {
            $placeName = D('Places')->getPlaceNames()[$id];
        }

        $redis  = $this->redis;
        $prefix = C('REDIS.PREFIX');

        $key   = $prefix . date('Ymd') . "_" . $id;
        $start = -1;
        if (!empty($num) && !empty($name) && !empty($time)) {
            $last  = json_encode(array($num, $time, $name));
            $start = $redis->zrank($key, $last);
        }
        $result = $redis->zrange($key, $start + 1, $start + C('num_of_page'));
        $count  = $redis->zcount($key, -1, 0);

        $data = array(
            'name'  => $placeName,
            'count' => $count,
            'list'  => array()
        );
        for ($i = 0; $i < count($result); $i++) {
            $array = json_decode($result[$i], true);
            $data['list'][$i] = array(
                'num'  => $array[0],
                'time' => $array[1],
                'name' => $array[2]
            );
        }

        $this->answer['data'] = $data;
    }

    // 获取教学班列表
    public function getClassList()
    {
        $TeacherStudent = D('TeacherStudent');
        if (!$TeacherStudent->hasClass($this->userId)) {
            return $this->answer = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '您没有教学班...'
            );
        }
        $this->answer['data'] = $TeacherStudent->getClassList($this->userId);
    }

    // 获取教学班签到情况
    public function getClassSignData($class = '')
    {
        $TeacherStudent = D('TeacherStudent');
        if (!$TeacherStudent->hasClass($this->userId)) {
            return $this->answer = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '您没有教学班...'
            );
        }
        $data = $TeacherStudent->getClassSignData($this->userId);
        $Student = D('Student');
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['name']  = $Student->getName($data[$i]['num']);
            $data[$i]['class'] = $Student->getClass($data[$i]['num']);
        }
        if ($class) {
            $tmp  = $data;
            $data = array();
            for ($i = 0; $i < count($tmp); $i++) {
                if ($tmp[$i]['class'] == $class) {
                    $data[] = $tmp[$i];
                }
            }
        }
        $this->answer['data'] = $data;
    }
}
