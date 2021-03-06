<?php

namespace Wx\Model;

use Think\Model;

/**
 * 学生模型
 */
class StudentModel extends Model
{
    // 判断是否为学生
    public function isStudent($num)
    {
        $data = array(
            'num' => $num
        );
        return $this->where($data)->find();
    }

    // 验证姓名
    public function vaildName($num, $name)
    {
        $where = array(
            'num'  => $num,
            'name' => $name
        );
        return $this->where($where)->find();
    }

    // 根据学号获取姓名
    public function getName($num)
    {
        $name = S("name_{$num}");
        if ($name) {
            return $name;
        }
        $data = array(
            'num' => $num
        );
        $result = $this->field('name')->where($data)->find();
        if ($result['name']) {
            S("name_{$num}", $result['name'], C('STUDENT_NAME_CACHE_TIME'));
            return $result['name'];
        } else {
            return '';
        }
    }

    // 根据学号获取班级
    public function getClass($num)
    {
        $class = S("class_{$num}");
        if ($class) {
            return $class;
        }
        $data = array(
            'num' => $num
        );
        $result = $this->field('class')->where($data)->find();
        if ($result['class']) {
            S("class_{$num}", $result['class'], C('STUDENT_CLASS_CACHE_TIME'));
            return $result['class'];
        } else {
            return '';
        }
    }
}
