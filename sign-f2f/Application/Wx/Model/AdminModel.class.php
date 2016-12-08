<?php

namespace Wx\Model;

use Think\Model;

/**
 * 管理员模型
 */
class AdminModel extends Model
{
    public function isAdmin($num)
    {
        $data = array(
            'num' => $num
        );
        return $this->where($data)->find();
    }
}