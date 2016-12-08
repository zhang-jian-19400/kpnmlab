<?php

namespace Wx\Controller;

use Common\Controller\BaseController;

class AuthController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
		                
        $this->userId = session('user.userId');
	$this->userName = session('user.name');
	$this->email = session('user.email');
        if(!$this->userName)$this->userName = session('user.nick');
        if (empty($this->userId)) {
            $this->answer = array(
                'code' => C('RETURN.NEED_LOGIN'),
                'msg'  => '正在获取微信授权...'
            );
            exit;
        }
	
        // 加载配置
        D('Ini')->loadConf();
    }
}
