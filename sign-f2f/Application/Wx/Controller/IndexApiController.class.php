<?php

namespace Wx\Controller;

class IndexApiController extends AuthController
{
    public function index()
    {
        $data = array(
            'common' => array(),
            'desks'  => array()
        );
        $userId = $this->userId;

        $CommonModel = D('Common');
	$data['common'] = $CommonModel->getByPower(0);
        
        if(($userId == '14010502003')||($userId == '16020501004'))
 	{
	    $data['desks']=array_merge(
		array(
                    /**
                    array(
                        'id'   => 'teachingspace',
                        'name' => '教学空间',
                        'icon' => 'cubes icon',
                        'url'  => '#/teachingspace'
                    ),**/
		   array(
                        'id'   => 'entertainment',
                        'name' => '娱乐空间',
                        'icon' => 'game icon',
                        'url'  => '#/entertainment'
                    )
	       )
	    );
		/**
	    $data['desks']=array_merge(
                array(
                    array(
                        'id'   => 'entertainment',
                        'name' => '游戏空间',
                        'icon' => 'game icon'
                    )
               )
            );**/

	}
		
        /**
        // 判断是不是学生
        if(D('Student')->isStudent($userId)) {
            $data['common'] = $CommonModel->getByPower(0);
        }

        // 判断是不是管理员
        if(D('Admin')->isAdmin($userId)) {
            $data['common'] = array_merge($data['common'], $CommonModel->getByPower(1));
            // 判断是否有教学班
            $ClassModel = D('TeacherStudent');
            if ($ClassModel->hasClass($userId)) {
                $data['common'] = array_merge($data['common'], $CommonModel->getByPower(2));
            }

            // 给出签到点
            $data['desks'] = array_merge(
                array(
                    array(
                        'id'   => 'qrcode',
                        'name' => '扫码签到',
                        'icon' => 'camera retro'
                    ),
                    array(
                        'id'   => 'add',
                        'name' => '手动登记',
                        'icon' => 'add user'
                    )
                ),
                D('Places')->getAllPlace()
            );
            for ($i = 0; $i < count($data['desks']); $i++) {
                $data['desks'][$i]['name'] .= '统计';
            }
        }
        **/
        if (empty($data['common'])) {
            $this->answer = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '你不需要签到...'
            );
        } else {
            $this->answer['data'] = $data;
            $noticeKey = C('REDIS.PREFIX') . 'notice';
            $isNoticed = $this->redis->hget($noticeKey, $userId);
            $this->answer['info'] = array(
                'isNoticed' => !!$isNoticed,
                 'userid'   => $userId            	
            );
            if (!$isNoticed) {
                $this->redis->hset($noticeKey, $userId, 1);
            }
        }
    }
}
