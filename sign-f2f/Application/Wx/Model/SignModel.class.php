<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到记录模型
 */
class SignModel extends Model
{
    public function sign($redis, $num, $type, $longitude = 0, $latitude = 0, $accuracy = -1, $speed = 0)
    {
        $result   = array();
        $time     = date('H:i:s');
        $datetime = time();

        // 数据库记录条件
        $condition = array(
            'num'  => $num,
            'date' => date('Y-m-d'),
        );

        // 尝试插入一条记录
        try {
            $this->add($condition);
        } catch (\Exception $e) {
            // 已经存在记录不需要任何操作
        }

        // 判断时间
        $isAm = $this->isAm($datetime);
        $isPm = $this->isPm($datetime);
        $isPmSecond = false;

        // 判断地点
        $place = D('Places')->checkPlace($longitude, $latitude);
        $isValidPlace = $this->isValidPlace($type, $place);

        // 获取当日签到信息
        $info = $this->where($condition)->find();

        // 未到签到周次
        if (!$this->canSign($datetime)) {
            $result = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '签到失败，当前非签到周次，签到周次范围请查看【在线帮助】'
            );
        // 未到签到时间
        } elseif (!$isAm && !$isPm) {
            $result = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '签到失败，当前非签到时间段，签到时间范围请查看【在线帮助】'
            );
        // 精度不够或位置不确定
        } elseif (($accuracy > C('accuracy_max')) || ((!$isValidPlace) && ($accuracy > 200))) {
            $result = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '确保已打开WIFI，并稍等片刻后再重新点开微信签到',
            );
        // 位置不对
        } elseif (!$isValidPlace) {
            $result = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '签到失败，当前位置不在签到点范围内，具体签到点请查看在线帮助或询问体育老师',
            );
        // 早锻炼签到但已签到
        } elseif ($isAm && !is_null($info['am_type'])) {
            $result = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '签到失败，今天的早锻炼您已经签过到了',
            );
        // 早锻炼签到
        } elseif ($isAm) {
            $data = array(
                'am'      => $time,
                'am_part' => $place,
                'am_type' => $type
            );
            D('SignStatistics')->addAm($num);
            $result = array(
                'data' => array(
                    'success' => true,
                    'prompt'  => '',
                    'type'    => $type
                )
            );
        // 下午锻炼签到但已签到
        } elseif ($isPm && !is_null($info['pm_2_type'])) {
            $result = array(
                'code' => C('RETURN.ERROR'),
                'msg'  => '签到失败，今天的下午(晚上)锻炼您已经签过到了',
            );
        // 下午锻炼签到第二次
        } elseif ($isPm && !is_null($info['pm_1_type'])) {
            $diff = strtotime($time) - strtotime($info['pm_1']);
            // 锻炼超过一小时
            if ($diff >= 3600) {
                $data = array(
                    'pm_2'      => $time,
                    'pm_2_part' => $place,
                    'pm_2_type' => $type
                );
                D('SignStatistics')->addPm($num);
                $isPmSecond  = true;
                $result = array(
                    'data' => array(
                        'success' => true,
                        'prompt'  => '',
                        'type'    => $type
                    )
                );
            // 锻炼不足一个小时
            } else {
                $result = array(
                    'code' => C('RETURN.ERROR'),
                    'msg'  => '签到失败，本次下午（晚上）锻炼尚不足1小时'
                );
            }
        //下午锻炼签到第一次签到
        } elseif ($isPm) {
            $data = array(
                'pm_1'      => $time,
                'pm_1_part' => $place,
                'pm_1_type' => $type
            );
            $result = array(
                'data' => array(
                    'success' => true,
                    'prompt'  => '请锻炼1个小时之后再回签1次，此次签到才有效，计为1次下午（晚上）锻炼',
                    'type'    => $type
                )
            );
        }

        // 保存到 redis 和 mysql 里
        if (!empty($data)) {
            $this->where($condition)->save($data);

            $prefix = C('REDIS.PREFIX');
            $key = $prefix . date('Ymd') . "_";
            if ($type == 0) {
                $key .= $place;
            } else if ($type == 1) {
                $key .= 'qrcode';
            } else {
                $key .= 'add';
            }

            $name = D('Student')->getName($num);
            // 上午
            if ($isAm) {
                $name .= "-AM";
            // 下午第一次
            } elseif ($isPmSecond) {
                $name .= "-PM-2";
            // 下午第二次
            } else {
                $name .= "-PM-1";
            }
            $value = json_encode(array($num, $time, $name));

            $score = ("0.".strtotime($time) + 0) * -1;
            $redis->zadd($key, $score, $value);

            // 签到成功后给出需要剩余签到的次数
            if ($isAm || $isPmSecond) {
                $statistics = D('SignStatistics');
                $logs       = $statistics->getLogs($num);
                $termLeft   = C('TERM_TOTAL_COUNT') - $logs['term_total'];
                //尚有锻炼
                if ($termLeft > 0) {
                    $prompt = "您本学期至少还需要锻炼{$termLeft}次";
                //锻炼完成
                } elseif (0 == $termLeft) {
                    $rank   = $statistics->getRank();
                    $prompt = "恭喜，您是本学期第 {$rank} 个完成锻炼任务的同学";
                //超额完成
                } else {
                    $prompt = "锻炼任务已完成，坚持每天锻炼，身体一定会是棒棒哒";
                }
                $result['data']['prompt'] = $prompt;
            }
        }

        //保存记录
        $error = (C('RETURN.ERROR') === $result['code'])? $result['msg']:null;
        D('Record')->saveData($num, $longitude, $latitude, $accuracy, $speed, $type, $place, $error);

        return $result;
    }

    // 判断周次是否在签到时间范围
    private function canSign($time)
    {
        $term      = C('term');
        $termBegin = D('Term')->getTermBegin($term);
        $weekly    = $this->getWeekly($time, strtotime($termBegin));
        /*
        * 第一学期和第二学期区别的代码
        $termNum = substr($term, -1, 1) + 0;
        if ($termNum == 1) {
            return $weekly >= 6 && $weekly <= 18;
        } else {
            return $weekly >= 2 && $weekly <= 18;
        }
        */
        return $weekly >= 2 && $weekly <= 18;
    }

    // 判断早上
    private function isAm($time)
    {
        $now = date('H', $time) * 60 + date('i', $time);

        $beginTime = 5 * 60 + 30; // 5:30
        $endTime   = 7 * 60 + 30; // 7:30
        if ($now >= $beginTime && $now < $endTime) {
            return true;
        }

        return false;
    }

    // 判断下午
    private function isPm($time)
    {
        $now = date('H', $time) * 60 + date('i', $time);

        // 判断下午
        $beginTime = 16 * 60 + 30; // 16:30
        $endTime   = 19 * 60 + 30; // 19:30
        if ($now >= $beginTime && $now < $endTime) {
            return true;
        }

        // 判断晚上
        $beginTime = 20 * 60 + 30; // 20:30
        $endTime   = 22 * 60 + 30; // 22:30
        if ($now >= $beginTime && $now < $endTime) {
            return true;
        }

        return false;
    }

    //判断位置是否有效
    public function isValidPlace($type, $place)
    {
        //扫码或者登陆直接返回正确的位置
        if (in_array($type, array(1, 2))) {
            return true;
        }
        return !!$place;
    }

    // 计算第几周
    public function getWeekly($now, $weekBegin)
    {
        return ceil(($now - $weekBegin) / (86400 * 7));
    }

    // TODO 此处可以优化，记得处理
    public function getOnePerson($num)
    {
        $map = array();
        $map['num'] = $num;
        $map['_string'] = "`am_type` IS NOT NULL OR `pm_1_type` IS NOT NULL OR `pm_2_type` IS NOT NULL";
        $result = $this->where($map)->order('date desc')->limit(10)->select();
        $placeNames = D('Places')->getPlaceNames();
        $list = array();
        foreach ($result as $item) {
            if (!is_null($item['am_type'])) {
                $part = isset($placeNames[$item['am_part']])? $placeNames[$item['am_part']]:'未知地点';
                $list[] = array(
                    'time' => $item['date'] . ' ' . $item['am'],
                    'part' => $part . '-AM'
                );
            }
            if (!is_null($item['pm_1_type'])) {
                $part = isset($placeNames[$item['pm_1_part']])? $placeNames[$item['pm_1_part']]:'未知地点';
                $list[] = array(
                    'time' => $item['date'] . ' ' . $item['pm_1'],
                    'part' => $part . '-PM-1'
                );
            }
            if (!is_null($item['pm_2_type'])) {
                $part = isset($placeNames[$item['pm_2_part']])? $placeNames[$item['pm_2_part']]:'未知地点';
                $list[] = array(
                    'time' => $item['date'] . ' ' . $item['pm_2'],
                    'part' => $part . '-PM-2'
                );
            }
        }
        // 按时间正序排序
        usort($list, function($a, $b) {
            return ($a['time'] > $b['time']) ? -1 : 1;
        });
        return $list;
    }

}