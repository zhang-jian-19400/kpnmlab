<?php

namespace Wx\Model;

use Think\Model;

/**
* 学期模型
*/
class TermModel extends Model
{
    public function getTermBegin($term)
    {
        $data = array(
            'term' => $term
        );
        $result = $this->where($data)->find();
        return $result ? $result['time'] : '0000-00-00';
    }
}