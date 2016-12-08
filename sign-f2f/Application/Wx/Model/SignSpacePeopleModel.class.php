<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到空间用户信息
 */
class SignSpacePeopleModel extends Model
{
   //protected $autoCheckFields =false;
   //protected $tableName = 'sign_people';
	
    //清空签到空间中的人员信息
    public function clearSignSpacePeople($spaceid)
    {
	$where = array('spaceid'=>$spaceid);
        return $this->where($where)-> delete(); 
    }
    //
    public function addAllPeople($users)
    {
	return $this->addAll($users);
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
