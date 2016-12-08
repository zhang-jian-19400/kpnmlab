<?php

namespace Wx\Model;

use Think\Model;

/**
 * 签到记录模型
 */
class RecordModel extends Model
{
    // 记录签到并判断签到是否有效
    public function saveData($num, $longitude, $latitude, $accuracy, $speed, $type, $place, $error = null)
    {
        $record = array(
            'num'       => $num,
            'time'      => date('Y-m-d H:i:s'),
            'longitude' => $longitude,
            'latitude'  => $latitude,
            'accuracy'  => $accuracy,
            'speed'     => $speed,
            'type'      => $type,
            'place'     => $place,
            'error'     => $error
        );
        return $this->add($record);
    }
}