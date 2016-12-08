<?php

namespace Wx\Model;

use Think\Model;

/**
 * 管理员模型
 */
class TeacherStudentModel extends Model
{
    // 判断是否有教学班
    public function hasClass($num)
    {
        $data = array(
            'teacher' => $num
        );
        return $this->where($data)->select();
    }

    // 获取班级列表
    public function getClassList($teacher)
    {
        $field = 'class';
        $table = array(
            'student' => 's',
            'teacher_student' => 'ts'
        );
        $where = array(
            'ts.teacher' => $teacher
        );
        $group = 'class';
        return $this->field($field)
            ->table($table)
            ->where($where)
            ->where('s.num=ts.student')
            ->group($group)
            ->select();
    }

    // 根据教工号获取学生签到情况
    public function getClassSignData($teacher)
    {
        $table = array(
            'teacher_student' => 'ts'
        );
        $field = array(
            'ts.student' => 'num',
            // 'begin_time',
            // 'end_time',
            // 'begin_week',
            // 'end_week',
            // 'week',
            'IF(ss.term_total is null, 0, ss.term_total)' => 'total',
            'IF(ss.term_total_valid is null, 0, ss.term_total_valid)' => 'total_valid'
        );
        $where = array(
            'ts.teacher' => $teacher
        );
        $join = "sign_statistics ss on ts.student=ss.num";
        return $this->field($field)
            ->table($table)
            ->join($join, 'LEFT')
            ->where($where)
            ->select();
    }
}