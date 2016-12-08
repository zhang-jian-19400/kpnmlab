<?php

namespace Wx\Model;

use Think\Model;

class SignStatisticsModel extends Model
{

    public function thisWeek()
    {
        if(date('N') == 7) {
            return date('Y-m-d', strtotime('monday last week'));
        } else {
            return date('Y-m-d', strtotime('monday this week'));
        }
    }

    public function getLogs($num)
    {
        $condition = array(
            'num'  => $num,
            'term' => C('term')
        );
        $data = array();
        $result = $this->where($condition)->find();
        if (empty($result)) {
            $this->add($condition);
            $result = $this->where($condition)->find();
        }
        $weekBegin = $this->thisWeek();
        if ($result['week_begin'] != $weekBegin) {  // 周次不对
            $data = array(
                'week_begin' => $weekBegin,
                'week_am'    => 0,
                'week_pm'    => 0,
            );
            $this->where($condition)->save($data);
        }
        return array_merge($result, $data);
    }

    public function getRank()
    {
        $condition = array(
            'term'       => C('term'),
            'term_total' => array('egt', C('TERM_TOTAL_COUNT'))
        );
        return $this->where($condition)->count();
    }

    public function addAm($num)
    {
        $condition = array(
            'num'  => $num,
            'term' => C('term')
        );
        $result = $this->getLogs($num);

        // 更新上午签到次数
        $data = array(
            'week_am'    => array('exp', 'week_am+1'),
            'term_am'    => array('exp', 'term_am+1'),
            'term_total' => array('exp', 'term_total+1')
        );
        if ($result['week_am'] < C('WEEK_AM_VALID_COUNT')) {
            $data['term_am_valid']    = array('exp', 'term_am_valid+1');
            $data['term_total_valid'] = array('exp', 'term_total_valid+1');
        }
        $this->where($condition)->save($data);
    }

    public function addPm($num)
    {
        $condition = array(
            'num'  => $num,
            'term' => C('term')
        );
        $result = $this->getLogs($num);

        // 更新下午签到次数
        $data = array(
            'week_pm'    => array('exp', 'week_pm+1'),
            'term_pm'    => array('exp', 'term_pm+1'),
            'term_total' => array('exp', 'term_total+1')
        );
        if ($result['week_pm'] < C('WEEK_PM_VALID_COUNT')) {
            $data['term_pm_valid']    = array('exp', 'term_pm_valid+1');
            $data['term_total_valid'] = array('exp', 'term_total_valid+1');
        }
        $this->where($condition)->save($data);
    }
}