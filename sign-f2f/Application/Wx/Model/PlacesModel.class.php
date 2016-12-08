<?php

namespace Wx\Model;

use Think\Model;

/**
 * 位置模型
 */
class PlacesModel extends Model
{

    /**
     * 获取所有地点
     *
     * @return array 获取内容
     */
    public function getAllPlace($status = -1)
    {
        $where = array();
        if ($status != -1) {
            $where['status'] = $status;
        }
        $result = $this->where($where)->select();
        return $result;
    }

    //获取所有签到点及其对应名字
    public function getPlaceNames()
    {
        $lists  = $this->select();
        $result = array();
        for ($i = 0; $i < count($lists); $i++) {
            $result[$lists[$i]['id']] = $lists[$i]['name'];
        }
        return $result;
    }

    //判断当前签到点
    public function checkPlace($longitude, $latitude)
    {
        if (!$longitude || !$latitude) {
            return 0;
        }

        $places = $this->getAllPlace(1);
        foreach ($places as $place) {
            $range = getDistance($latitude, $longitude, $place['latitude'], $place['longitude']);
            if ($range <= $place['ranges']) {
                return $place['id'];
            }
        }
        return 0;
    }
}