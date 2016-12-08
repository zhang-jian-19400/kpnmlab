<?php

namespace Common\Controller;

use Think\Controller;

class BaseController extends Controller
{
    public $redis  = null;
    public $answer = array();

    public function _initialize()
    {
        //初始化缓存
        $this->redis = S(array(
            'type'   => 'redis',
            'host'   => C('REDIS.HOST'),
            'port'   => C('REDIS.PORT'),
            'prefix' => C('REDIS.PREFIX')
        ));
    }

    //析构函数，输出
    public function __destruct()
    {
        $answer = $this->answer;
        if (isset($answer['msg']) || isset($answer['code']) || isset($answer['info']) || isset($answer['data'])) {
            //code及msg属性
            if (isset($answer['msg']) && isset($answer['data']) && is_string($answer['data'])) {
                $answer['code'] = isset($answer['code'])? $answer['code']:C('RETURN.CONFIRM');
            } else if (isset($answer['msg'])) {
                $answer['code'] = isset($answer['code'])? $answer['code']:C('RETURN.ALERT');
            } else {
                $answer['msg']  = '';
                $answer['code'] = isset($answer['code'])? $answer['code']:C('RETURN.NORMAL');
            }
            //info属性
            $get = I('get.');
            if (isset($answer['info'])) {
                $answer['info'] = array_merge($get, $answer['info']);
            } else {
                $answer['info'] = $get;
            }
            //data属性
            $answer['data'] = isset($answer['data'])? $answer['data']:array();
            //Ajax输出
            $this->ajaxReturn($answer);
        }
        parent::__destruct();
    }
}
