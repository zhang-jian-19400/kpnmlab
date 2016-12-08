<?php

namespace Wx\Controller;

use Common\Controller\BaseController;

/**
* 无权限验证
*/
class NoneAuthController extends BaseController
{
    public function _initialize()
    {
        // 加载配置
        D('Pc/Ini')->loadConf();

        parent::_initialize();
    }
}
