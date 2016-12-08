<?php

namespace Wx\Model;
use Think\Model;

/**
* 配置模型
*/
class IniModel extends Model
{
    public function loadConf()
    {
        $array = $this->select();
        for ($i=0; $i < count($array); $i++) {
            C($array[$i]['method'], $array[$i]['value']);
        }
    }

    public function getAllIni()
    {
        $data = $this->select();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['method'] == "term") {
                $data[] = array(
                    'name'   => '开学时间',
                    'method' => 'term_begin',
                    'value'  => D('Term')->getTermBegin($data[$i]['value'])
                );
            }
        }
        return $data;
    }

    public function saveIni($method, $value, $name)
    {
        if ($method == "term_begin") {
            $term = $this->field('value')->where("method='term'")->find();
            $term = $term["value"];
            D('Term')->setTermBegin($term, $value);
            return true;
        }
        $data = array(
            'method' => $method,
            'value'  => $value,
            'name'   => $name
        );
        return $this->save($data);
    }
}