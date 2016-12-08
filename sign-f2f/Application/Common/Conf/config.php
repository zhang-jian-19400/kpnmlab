<?php
return array(
    //默认模块
    'DEFAULT_MODULE' => 'Wx',

    // url模式
    'URL_MODEL' => 2,

    'DB_TYPE'   => 'mysql',            // 数据库类型
    'DB_HOST'   => '127.0.0.1',        // 服务器地址
    'DB_NAME'   => 'signf2f',          // 数据库名
    'DB_USER'   => 'signf2f',          // 用户名
    'DB_PWD'    => 'RG2wmUGrmnJCXqrF', // 密码
    'DB_PORT'   => 3306,               // 端口
    'DB_PREFIX' => '',                 // 数据库表前缀
    'MAIL_HOST' => 'smtp.mxhichina.com', //smtp服务器名称
    'MAIL_SMTPAUTH' => TRUE,              //启用smtp认证
    'MAIL_USERNAME' => 'shimin@17translate.com', //发件人邮箱
    'MAIL_PASSWORD' =>'SHI_MIN123',        //邮箱密码
    'MAIL_CHARSET' =>'utf-8',          //设置邮件编码
    'MAIL_ISHTML' =>TRUE,              // 是否HTML格式邮件
    'DB_CHARSET'=> 'utf8',             // 字符集
    'DB_DEBUG'  =>  TRUE,              // 数据库调试模式 开启后可以记录SQL日志 3.2.3新增

    'WECHAT' => array(
        //企业号ID
        'APPID'   => 'wx6219dbfa9b86489e',
        //企业号学生团队密钥
        'SECRET'  => 'ir4eu9qCvOHmX9YtgQnqrhe2iWzlWziBvV9ZW7jltk-tR8cZ5cYppex_obczUuG4',
        //主动发消息应用ID
        'AGENTID' => 61,
        // 微信签到标签ID
    ),

    'LOCAL_HOST' => 'wechat.hnust.cn',

    // PC端登陆地址
    'PC_LOGIN_URL'         => 'http://weixin.hnust.cn/',
    'PC_LOGIN_DECRYPT_URL' => 'http://weixin.hnust.cn:8080/WeiMenHuAPI/DecryptStr?str=%s',

    //REDIS缓存相关信息
    'REDIS' => array(
        'HOST'   => 'localhost',
        'PORT'   => '6379',
        'PREFIX' => 'f2f_sign_',
        'QRCODE_PREFIX' => 'f2f_qrcode_'
    ),

    //缓存时间相关
    'STUDENT_NAME_CACHE_TIME'  => 864000,
    'STUDENT_CLASS_CACHE_TIME' => 864000,
    'WEIXIN_USER_CACHE_TIME'   => 86400,
    'LAUNCH_SIGN_CACHE_TIME'   => 10,//用户发起签到记录缓存两个小时, 超时签到失效

    //返回值相关
    'RETURN' => array(
        'NEED_LOGIN'    => -2, // 需要登陆
        'ERROR'         => -1, // 返回错误
        'NORMAL'        =>  0, // 正常
        'ALERT'         =>  1, // 弹出提示信息
        'CONFIRM'       =>  2, // 确认窗口
        'NEED_PASSWORD' =>  3, // 输入密码
    ),

    'TERM_TOTAL_COUNT'    => 12, // 每学期需要锻炼的次数
    'WEEK_AM_VALID_COUNT' => 2,  // 每周上午锻炼有效次数（已废弃）
    'WEEK_PM_VALID_COUNT' => 1   // 每周下午锻炼有效次数（已废弃）
);
