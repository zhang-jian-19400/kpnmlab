<?php

namespace Wx\Model;

use Think\Model;

/**
 * 首页模型
 */
class CommonModel extends Model
{
    public function getByPower($power)
    {
        $data = array(
            'power' => $power
        );
        return $this->where($data)->select();
    }
}