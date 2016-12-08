<?php

namespace Wx\Model;

use Think\Model;

/**
 * 发起签到零时表
 */
class SignLaunchTempModel extends Model
{
    // 更新签到命名
    public function updateSignName($signid,$signname)
    {
        $data = array(
            'id' => $signid
        );
        return $this->where($data)->setField('name',$signname);
    }    

    // 判断是否为学生
    public function isStudent($num)
    {
        $data = array(
            'num' => $num
        );
        return $this->where($data)->find();
    }

    // 验证用户是否已签到
    public function vaildSign($signid)
    {
        $where = array(
            'id'  => $signid
        );
        return $this->where($where)->find();
    }

    //查询数据库中所有未失效的签到
    public function getAllLaunch()
    {
        return $this->field('id')->select(); 
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
    
    //同步缓存与数据库中的发起签到数据 （是否过期）
    public function saveLaunch($signid)
    {
        $sql = "call signExpire($signid)";
        $con = mysql_connect("localhost","signf2f","RG2wmUGrmnJCXqrF");
        mysql_query('set names utf8');
        mysql_select_db("signf2f");
        $result=mysql_query($sql);
	mysql_close($con);
    }
}
