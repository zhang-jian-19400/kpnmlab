<?php

namespace Wx\Model;

use Wx\Model\HttpModel as Http;

class WechatModel
{
    //缓存
    protected $secret;
    protected $agentId;
    protected $baseUrl;

    //初始缓存
    public function __construct($agentId)
    {
        //获取微信secret
        $this->secret  = C('WECHAT.SECRET');
        //获取基地址
        $localHost     = C('LOCAL_HOST');
        $this->baseUrl = "http://{$localHost}/wechat/{$agentId}/";
    }

    protected function getHttp($api, $post = array())
    {
        //HTTP请求
        $url    = $this->baseUrl . $api;
        $post   = json_encode(array_merge($post, array(
            'secret' => $this->secret
        )));
        $header = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post)
        );
        try {
            $http = new Http(array(
                CURLOPT_URL        => $url,
                CURLOPT_POSTFIELDS => $post,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_TIMEOUT    => 5,
            ));
        } catch (\Exception $e) {
            return array(
                'errcode' => -1,
                'errmsg'  => $e->getMessage()
            );
        }

        //检查数据
        $result = @json_decode($http->content, true);
        if (empty($result) || !is_array($result)) {
            $result = array(
                'errcode' => -1,
                'errmsg'  => 'Incorrect Data'
            );
        }
        return $result;
    }

    //获取微信应用信息
    public function getAgent($agentid)
    {
        $post   = array('agentid' => $agentid);
        $result = $this->getHttp('getAgent', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result;
    }

    //设置微信应用信息
    public function setAgent($opts)
    {
        $post   = array('opts' => $opts);
        $result = $this->getHttp('setAgent', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return !$result['errcode'];
    }

    //获取微信应用列表
    public function listAgent()
    {
        $result = $this->getHttp('listAgent');
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['agentlist'];
    }

    //创建部门
    public function createDepartment($name, $opts)
    {
        $post   = array(
            'name' => $name,
            'opts' => $opts
        );
        $result = $this->getHttp('createDepartment', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['id'];
    }

    //更新部门
    public function updateDepartment($id, $opts)
    {
        $post   = array(
            'id'   => $id,
            'opts' => $opts
        );
        $result = $this->getHttp('updateDepartment', $post);
        return !$result['errcode'];
    }

    //删除部门
    public function deleteDepartment($id)
    {
        $post   = array('id' => $id);
        $result = $this->getHttp('deleteDepartment', $post);
        return !$result['errcode'];
    }

    //获取所有部门
    public function getDepartments()
    {
        $result = $this->getHttp('getDepartments');
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['department'];
    }

    //获取jssdk ticket
    public function getTicket()
    {
        $result = $this->getHttp('getTicket');
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result;
    }

    //获取js配置信息
    public function getJsConfig($param)
    {
        $post   = array('param' => $param);
        $result = $this->getHttp('getJsConfig', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result;
    }

    //发送消息
    public function sendMsg($to, $type, $data)
    {
        //调用接口发送消息
        $post = array(
            'to'      => $to,
            'message' => array(
                'msgtype' => $type,
                $type     => $data,
            )
        );
        $result = $this->getHttp('sendMsg', $post);
        return !$result['errcode'];
    }

    //发送文本消息
    public function sendTextMsg($to, $content)
    {
        $type = 'text';
        $data = array(
            'content' => $content
        );
        return $this->sendMsg($to, $type, $data);
    }

    //创建标签
    public function createTag($name)
    {
        $post   = array('name' => $name);
        $result = $this->getHttp('createTag', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['tagid'];
    }

    //更新标签
    public function updateTagName($id, $name)
    {
        $post   = array(
            'id'   => $id,
            'name' => $name
        );
        $result = $this->getHttp('updateTagName', $post);
        return !$result['errcode'];
    }

    //删除标签
    public function deleteTag($id)
    {
        $post   = array('id' => $id);
        $result = $this->getHttp('deleteTag', $post);
        return !$result['errcode'];
    }

    //获取标签列表
    public function listTags()
    {
        $result = $this->getHttp('listTags');
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['taglist'];
    }

    //获取标签用户列表
    public function getTagUsers($id)
    {
        $post   = array('id' => $id);
        $result = $this->getHttp('getTagUsers', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['userlist'];
    }

    //添加标签列表用户
    public function addTagUsers($id, $userIdList)
    {
        $post   = array(
            'id'         => $id,
            'userIdList' => $userIdList
        );
        $result = $this->getHttp('addTagUsers', $post);
        return !$result['errcode'];
    }

    //添加标签列表用户
    public function deleteTagUsers($id, $userIdList)
    {
        $post   = array(
            'id'         => $id,
            'userIdList' => $userIdList
        );
        $result = $this->getHttp('deleteTagUsers', $post);
        return !$result['errcode'];
    }

    //创建用户
    public function createUser($user)
    {
        $post   = array('user' => $user);
        $result = $this->getHttp('createUser', $post);
        return !$result['errcode'];
    }

    //更新用户
    public function updateUser($user)
    {
        $post   = array('user' => $user);
        $result = $this->getHttp('updateUser', $post);
        return !$result['errcode'];
    }

    //删除用户
    public function deleteUser($userId)
    {
        $post   = array('userid' => $userId);
        $result = $this->getHttp('deleteUser', $post);
        return !$result['errcode'];
    }

    //获取用户信息
    public function getUser($userId, $useCache = true)
    {
        $cacheKey = 'weixin_user_' . $userId;
        //读取缓存
        if ($useCache && ($result = S($cacheKey))) {
            return $result;
        }

        $post   = array('userid' => $userId);
        $result = $this->getHttp('getUser',$post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        S($cacheKey, $result, C('WEIXIN_USER_CACHE_TIME'));
        return $result;
    }

    //获取部门所有成员
    public function getDepartmentUsers($departmentId, $fetchChild = 0, $status = 4)
    {
        $post   = array(
            'departmentId' => $departmentId,
            'fetchChild'   => $fetchChild,
            'status'       => $status
        );
        $result = $this->getHttp('getDepartmentUsers', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['userlist'];
    }

    //获取部门所有成员详情
    public function getDepartmentUsersDetail($departmentId, $fetchChild = 0, $status = 4)
    {
        $post   = array(
            'departmentId' => $departmentId,
            'fetchChild'   => $fetchChild,
            'status'       => $status
        );
        $result = $this->getHttp('getDepartmentUsersDetail', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result['userlist'];
    }

    //通过code获取用户id
    public function getUserIdByCode($code, $follow = true)
    {
        $post   = array('code' => $code);
        $result = $this->getHttp('getUserIdByCode', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        } elseif (!$follow && !empty($result['OpenId'])) {
            return $result['OpenId'];
        } elseif (!empty($result['UserId'])) {
            return $result['UserId'];
        } else {
            return false;
        }
    }

    //通过auth_code获取用户信息
    public function getLoginUserInfoByCode($auth_code)
    {
        $post   = array('auth_code' => $auth_code);
        $result = $this->getHttp('getLoginUserInfoByCode', $post);
        if (isset($result['errcode']) && $result['errcode']) {
            return false;
        }
        return $result;
    }
}