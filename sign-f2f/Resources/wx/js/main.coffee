#AngularJS
sign = angular.module 'sign', ['ngRoute', 'bw.paging','angularFileUpload']
#加载服务器数据
sign.factory 'request', ($rootScope, $http, $location) ->
    #检查数据
    check: (res, callback) ->
        res = if angular.isObject res then res else code:-1
        callback ||= ->
        switch parseInt(res.code)
            #需要获取用户信息
            when -2
                layer.msg res.msg || '即将请求获取用户身份'
                state = encodeURIComponent window.location.href
                href  = '/wechat/oauth?state=' + state
                return window.location.href = href
            #发生错误
            when -1
                error = res.msg || '网络连接超时 OR 服务器错误。'
        callback error, res.info, res.data

    #请求数据
    query: (req, callback) ->
        self = this
        #请求方式
        req.method = if req.data then 'POST' else 'GET'
        #get参数
        search = angular.copy $location.search()
        req.params = $.extend search, req.params || {}
        #post参数
        req.data = $.param req.data || {}
        #alert JSON.stringify(req)
        #超时时间
        req.timeout ||= 10000
        #回调函数
        callback ||= ->

        #发起请求
        $http(req)
            .success (res) ->
                #alert JSON.stringify(res)
                self.check res, callback
            .error ->
                callback '网络异常，请稍后再试。'

#微信初始化
sign.factory 'wechat', ($rootScope, request) ->
    #是否初始化
    isReady: false

    #获取配置微信文件
    getConfig: (callback) ->
        callback ||= ->
        request.query
            url: 'wx/studentApi/getConfig'
            params:
                url:$rootScope.baseUrl
        , (error, info, data) ->
            callback error, data

    #设置微信配置文件
    getReady: (callback) ->
        self = this
        callback ||= ->
        if self.isReady then return callback null
        self.getConfig (error, config) ->
            if error then return callback error
            #配置微信SDK
            wx.config config
            #微信SDK配置成功
            wx.ready ->
                self.isReady = true
                callback null
            #微信SDK配置失败
            wx.error (res) ->
                callback '服务初始化失败，如果多次如此，请联系管理员'

sign.config ($httpProvider, $routeProvider) ->
    #使用urlencode方式Post
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
    #设置路由
    $routeProvider
        .when '/home',
            title: '首页'
            controller: 'home'
            templateUrl: 'home.html'
        .when '/in',
            title: '签到'
            controller: 'in'
            templateUrl: 'in.html'
        .when '/tosign',
            title: '签到'
            controller: 'tosign'
            templateUrl: 'tosign.html'
        .when '/helper',
            title: '在线帮助'
            templateUrl: 'helper.html'
        .when '/qrcode1',
            title: '人定人签到'
            controller: 'qrcode1'
            templateUrl: 'qrcode1.html'
        .when '/signhistory',
            title: '参与签到记录'
            controller: 'signhistory'
            templateUrl: 'signhistory.html'
        .when '/signlaunchhistory',
            title: '发起签到记录'
            controller: 'signlaunchhistory'
            templateUrl: 'signlaunchhistory.html'
        .when '/signlist',
            title: '签到列表'
            controller: 'signlist'
            templateUrl: 'signlist.html'
        .when '/signspacedetail',
            title: '详细列表'
            controller: 'signspacedetail'
            templateUrl: 'signspacedetail.html'
        .when '/signspace',
            title:'签到空间管理'
            controller:'signspace'
            templateUrl:'signspace.html'
        .when '/teachingspace',
            title: '教学空间'
            controller: 'teachingspace'
            templateUrl: 'teachingspace.html'
        .when '/teachingcreate',
            title: '发起题目'
            controller: 'teachingcreate'
            templateUrl: 'teachingcreate.html'
        .when '/distributehistory',
            title: '发题记录'
            controller: 'distributehistory'
            templateUrl: 'distributehistory.html'
        .when '/answerhistory',
            title: '答题记录'
            controller: 'answerhistory'
            templateUrl: 'answerhistory.html'
        .when '/answerlist',
            title: '答题记录'
            controller: 'answerlist'
            templateUrl: 'answerlist.html'
        .when '/distributelist',
            title: '题目记录'
            controller: 'distributelist'
            templateUrl: 'distributelist.html'
        .when '/teachinganswer',
            title: '参与答题'
            controller: 'answer'
            templateUrl: 'answer.html' 
        .otherwise
            redirectTo: '/home'

sign.run ($rootScope, request) ->
    #获取当前网址
    $rootScope.baseUrl = window.location.href.split('#')[0]

    #监视路由成功事件
    $rootScope.$on '$routeChangeSuccess', (event, current, previous) ->
        #清空弹出层页面
        $('.ui.modals').html('')
        #设置标题
        $rootScope.title = current.$$route?.title  || ''

    #滚动到指定位置
    $rootScope.scrollTop = ->
        $('body,html').animate
            scrollTop:0
        , 500
        return

#首页
sign.controller 'home', ($scope, request) ->
    $scope.loading = true
    request.query
        url : 'wx/indexApi'
    , (error, info, data) ->
        $scope.loading = false
        $scope.error = error
        $scope.data  = data
       #alert info.userid
        $scope.notice info?.isNoticed

    $scope.notice = (isNoticed) ->
        if isNoticed then return
        $('.notice.modal').modal({blurring: true}).modal('show')

#发起签到
sign.controller 'in', ($scope, $location, request, wechat) ->
    #获取网址参数
    $scope.getQueryString = (url, name) ->
        reg = new RegExp("(/?|&)#{name}=([^&]*)(&|$)", 'i')
        res = url.substr(1).match(reg)
        if res then unescape(res[2]) else null

    #获取地址位置
    $scope.getLocation = ->
        if $scope.loading then return
        $scope.loading = true
        $scope.visible = true
        $scope.signinfo = false
        $scope.error = false
        $scope.status  = '正在定位'
        wechat.getReady (error) ->
            if error
                $scope.status  = '签到失败'
                $scope.loading = false
                #alert error
                if !$scope.$$phase then $scope.$digest()
                return
            wx.getLocation
                type: 'gcj02'
                success: (res) ->
                    #alert JSON.stringify(res)
                    $scope.status = '正在生成签到码'
                    #$scope.visible = false
                    #$scope.signinfo = true
                    if !$scope.$$phase then $scope.$digest()
                    
                    request.query
                        url : 'wx/studentApi/signLaunch'
                        data: res
                    , (error, info, data) ->
                        $scope.loading = false
                        if error
                            $scope.status  = '签到失败'
                            return alert error
                        #$scope.success = data.success
                        #$scope.prompt  = data.prompt
                        #$scope.type    = data.type
                        #alert JSON.stringify(data)
                        #alert data.gencoderesult
                        switch data.gencoderesult
                            when 'signSpanError'
                                $scope.error = true
                                $scope.errorinfo = '半小时之内不能连续发起签到哦^_^'
                                $scope.status  = '签到失败'            
                            when 'signCountError'
                                $scope.error = true
                                $scope.errorinfo = '一天之内最多只能发起4次签到哦^_^'
                                $scope.status  = '签到失败'
                            when 'maxNumError'
                                $scope.error = true
                                $scope.errorinfo = '对不起，该位置发起签到的人数太多，请稍后重试^_^'
                                $scope.status  = '签到失败'
                            else
                                $scope.error = false  
                                $scope.visible = false
                                $scope.signinfo = true
                                $scope.code = data.gencoderesult
                                $scope.informtext = data.dfilename
                                $scope.signid = data.signid
                                $scope.dfilename = data.dfilename                        

                        if !$scope.$$phase then $scope.$digest()
                    
                fail: (res) ->
                    $scope.status  = '定位失败'
                    $scope.loading = false
                    alert '位置获取失败，建议检查是否有安全软件禁止微信获取位置信息'
                    if !$scope.$$phase then $scope.$digest()
                
    #关闭窗口
    $scope.closeWindow = ->
        wechat.getReady (error) ->
            if error then return alert error
            wx.closeWindow()

    #提交二维码值
    $scope.submitQRCode = (code) ->
        request.query
            url : 'wx/studentApi/scan'
            data:
                code: code
        , (error, info, data) ->
            if error then return alert error
            alert JSON.stringify(data)
            $scope.success = true
            $scope.prompt = ''
            $scope.type = 0
            $rootScope.signname = $scope.signname
            $rootScope.signid = $scope.signid
            #$scope.success = data.success
            #$scope.prompt  = data.prompt
            #$scope.type    = data.type

    #扫一扫
    $scope.scanQRCode = ->
        wechat.getReady (error) ->
            if error then return alert error
            wx.scanQRCode
                desc: 'scanQRCode desc'
                needResult: 1
                scanType: ['qrCode']
                success: (res) ->
                    url  = res.resultStr
                    #alert url
                    code = $scope.getQueryString url, 'c'
                    alert code
                    $scope.submitQRCode code
                fail: (res) ->
                    alert '摄像头启动失败，建议检查是否有安全软件禁止微信启用摄像头'

    #判断并提交二维码值
    
    code = $location.search().c || ''
    if code
        layer.msg '提交扫码结果中...'
        $scope.submitQRCode code
    else
        #$scope.getLocation()
        $scope.visible = true
    


#我要签到
sign.controller 'tosign', ($rootScope, $scope, $location, request, wechat) ->
    #获取网址参数
    $scope.getQueryString = (url, name) ->
        reg = new RegExp("(/?|&)#{name}=([^&]*)(&|$)", 'i')
        res = url.substr(1).match(reg)
        if res then unescape(res[2]) else null

    
    #获取地址位置
    $scope.getLocation = ->
       $scope.isSureBt1 = true
       $scope.isSureBt2 = false
       $scope.isShow = true
       if !$scope.$$phase then $scope.$digest()
       #$scope.loading = false
       ###if $scope.loading then return
        #$scope.loading = true
        #$scope.visible = true
        #$scope.signinfo = false
        $scope.error = false
        #$scope.status  = '正在定位'
        wechat.getReady (error) ->
            if error
                $scope.status  = '签到失败'
                $scope.loading = false
                #alert error
                if !$scope.$$phase then $scope.$digest()
                return
            wx.getLocation
                type: 'gcj02'
                success: (res) ->
                    alert JSON.stringify(res)
                    #$scope.status = '输入4位签到码'
                    #$scope.visible = false
                    #$scope.signinfo = true
                    #$scope.isShow = true
                    if !$scope.$$phase then $scope.$digest()
                    
                    request.query
                        url : 'wx/studentApi/signLaunch'
                        data: res
                    , (error, info, data) ->
                        $scope.loading = false
                        if error
                            $scope.status  = '签到失败'
                            return alert error
                        #$scope.success = data.success
                        #$scope.prompt  = data.prompt
                        #$scope.type    = data.type
                        #alert data.dfilename
                        #alert data.gencoderesult
                        switch data.gencoderesult
                            when 'signSpanError'
                                $scope.error = true
                                $scope.errorinfo = '半小时之内不能连续发起签到哦^_^'
                                $scope.status  = '签到失败'            
                            when 'signCountError'
                                $scope.error = true
                                $scope.errorinfo = '一天之内最多只能发起4次签到哦^_^'
                                $scope.status  = '签到失败'
                            when 'maxNumError'
                                $scope.error = true
                                $scope.errorinfo = '对不起，该位置发起签到的人数太多，请稍后重试^_^'
                                $scope.status  = '签到失败'
                            else
                                $scope.error = false  
                                $scope.visible = false
                                $scope.signinfo = true
                                $scope.code = data.gencoderesult
                                $scope.informtext = data.dfilename                        

                        if !$scope.$$phase then $scope.$digest()
                    
                fail: (res) ->
                    $scope.status  = '定位失败'
                    $scope.loading = false
                    alert '位置获取失败，建议检查是否有安全软件禁止微信获取位置信息'
                    if !$scope.$$phase then $scope.$digest()
                ###
    #确定签到
    $scope.assureSign = ->
        $scope.isSure = true
        res = {'signid':$scope.signid,'signname':$scope.signname,'incode':String($scope.incode),'longitude':$scope.longitude,'latitude':$scope.latitude,'accuracy':$scope.accuracy,'speed':$scope.speed}
        #alert JSON.stringify(res)
        request.query
            url : 'wx/studentApi/startSign'
            data: res
        , (error, info, data) ->
            $scope.loading = false
            if error
                $scope.status  = '签到失败'
                $scope.isSure = false
                return alert error    
           
            #alert JSON.stringify(data)
            $scope.success = true
            $scope.prompt = ''
            $scope.type = 1
            #$rootScope.signname = $scope.signname
            #$rootScope.signid = $scope.signid
    ###
    $scope.viewSignList = ->
        #alert $scope.signname
        $rootScope.signname = $scope.signname
        #alert $rootScope.signname         
    ###


    #监听输入签到码
    $scope.codeinput = ->
        $scope.isText = false
        $scope.isSureBt1 = true
        $scope.isSureBt2 = false
        $scope.isShow = true
        $scope.status = '确定签到'
        if !$scope.$$phase then $scope.$digest()
        code = $scope.incode
        if String(code+'').length is 0 then return
        #alert code
        if !code 
            return
        else if String(code).length is 4
            $scope.isText = true
            $scope.loading = true
            $scope.status = '正在定位'
            $scope.error = false
            if !$scope.$$phase then $scope.$digest()
            
            wechat.getReady (error) ->
                if error
                    $scope.status  = '签到失败'
                    $scope.isText = false
                    $scope.isSureBt1 = true
                    $scope.isSureBt2 = false
                    $scope.isShow = true
                    #alert error
                    if !$scope.$$phase then $scope.$digest()
                    return
                wx.getLocation
                    type: 'gcj02'
                    success: (res) ->
                        #alert JSON.stringify(res)
                        res["signcode"]=String(code)
                        #alert JSON.stringify(res)
                        $scope.longitude = res.longitude
                        $scope.latitude = res.latitude
                        $scope.accuracy = res.accuracy
                        $scope.speed = res.speed
                        
                        request.query
                            url : 'wx/studentApi/fetchSignInfo'
                            data: res
                        , (error, info, data) ->
                            $scope.loading = false
                            if error
                                $scope.status  = '签到失败'
                                $scope.informtext = '请稍后重试'
                                $scope.isText = false
                                $scope.isShow = true
                                $scope.isSureBt1 = true
                                $scope.isSureBt2 = false
                                return alert error
                            #alert JSON.stringify(data)
                            switch data.dfilename
                                when 'nosign'
                                    $scope.informtext = '你的附近好像没有该签到码哦^_^'
                                    $scope.isText = false
                                    $scope.isSureBt1 = true
                                    $scope.isSureBt2 = false
                                    $scope.isShow = true
                                    $scope.status = '确定签到'
                                else
                                    #alert JSON.stringify(data)
                                    $scope.informtext = data.dfilename
                                    $scope.signid = data.signid;
                                    $scope.isSureBt1 = false
                                    $scope.isSureBt2 = true
                                    $scope.isText = false
                                    $scope.signname = data.dfilename                         

                        if !$scope.$$phase then $scope.$digest()
                    fail: (res) ->
                        $scope.informtext = '定位失败'
                        $scope.isText = false
                        $scope.isSureBt1 = true
                        $scope.isSureBt2 = false
                        $scope.isShow = true
                        $scope.status = '确定签到'
                        alert '位置获取失败，建议检查是否有安全软件禁止微信获取位置信息'
                        if !$scope.$$phase then $scope.$digest()
    

        #else if String(code).length > 4
            #alert '无效的签到码，签到码是有4位哦^_^'
        ###
        code = $scope.incode
        for k,v of code.toString()
            if !isNaN v
            else
                precode = code.substring(0,code.length-1)
                alert precode
                $scope.incode = precode
        ###
    #关闭窗口
    $scope.closeWindow = ->
        wechat.getReady (error) ->
            if error then return alert error
            wx.closeWindow()

    #提交二维码值
    $scope.submitQRCode = (code) ->
        request.query
            url : 'wx/studentApi/scan'
            data:
                code: code
        , (error, info, data) ->
            if error then return alert error
            $scope.success = data.success
            $scope.prompt  = data.prompt
            $scope.type    = data.type

    #扫一扫
    $scope.scanQRCode = ->
        wechat.getReady (error) ->
            if error then return alert error
            wx.scanQRCode
                desc: 'scanQRCode desc'
                needResult: 1
                scanType: ['qrCode']
                success: (res) ->
                    url  = res.resultStr
                    code = $scope.getQueryString url, 'c'
                    $scope.submitQRCode code
                fail: (res) ->
                    alert '摄像头启动失败，建议检查是否有安全软件禁止微信启用摄像头'

    #判断并提交二维码值
    code = $location.search().c || ''
    if code
        layer.msg '提交扫码结果中...'
        $scope.submitQRCode code
    else
        $scope.getLocation()





#签到记录
sign.controller 'history', ($scope, request) ->
    $scope.loading = true
    request.query
        url : 'wx/studentApi/getHistoryLogs'
    , (error, info, data) ->
        $scope.loading = false
        $scope.error = error
        $scope.data  = data

    #两数相加
    $scope.sum = (a, b) ->
        +a + +b

#签到二维码
sign.controller 'qrcode1', ($scope, $location, $rootScope, $interval, request, wechat) ->
    sid = $location.search().signid || ''
    sname = $location.search().signname || ''
    #获取网址参数
    $scope.getQueryString = (url, name) ->
        reg = new RegExp("(/?|&)#{name}=([^&]*)(&|$)", 'i')
        res = url.substr(1).match(reg)
        if res then unescape(res[2]) else null
    #获取地址位置
    $scope.getLocation = (callback) ->
        wechat.getReady (err) ->
            if err then return callback err
            wx.getLocation
                type: 'gcj02'
                success: (res) ->
                   #alert JSON.stringify(res)
                    callback null, res
                fail: (res) ->
                    callback '位置获取失败，建议检查是否有安全软件禁止微信获取位置信息'

    #获取二维码字符串
    $scope.getQRCode = (callback) ->
        #alert sid
        $scope.havesign = true
        $scope.notsign = false
        $scope.getLocation (err, res) ->
            #alert err
            if err then return callback err, res
            res.oldQRCode = $scope.oldQRCode
            if sid
                res['sid']=sid
                res['signname']=sname
            #alert JSON.stringify(res)
            request.query
                url : 'wx/studentApi/getQRCode'
                data: res
            , (err, info, data) ->
                #alert JSON.stringify(data)
                ##callback err, data?.QRCode
                switch data.signOk
                    when 'no'
                        $scope.stopInterval()
                        $scope.havesign = false
                        $scope.notsign = true
                        $scope.showsn = false
                        #alert '你还未签到'
                    when 'yes'
                        #alert JSON.stringify(data)
                        $scope.showsn = true
                        $scope.signname = data.signname
                        callback err, data?.QRCode
                    else
                        $scope.stopInterval()
                        $scope.error = data.msg
                        

    #设置二维码字符串
    $scope.setQRCode = (count, callback) ->
        callback ||= ->
        $scope.getQRCode (err, QRCode) ->
            $scope.error = err
            $scope.oldQRCode = QRCode
            url = "#{$rootScope.baseUrl}#/qrcode1?c=#{QRCode}"
           # alert url
            $('.qrcode.image').html('').qrcode(url)
            callback err, QRCode


    #关闭窗口
    $scope.closeWindow = ->
        wechat.getReady (error) ->
            if error then return alert error
            wx.closeWindow()
    #扫一扫 
    
    $scope.scanQRCode = ->
        wechat.getReady (error) ->
            if error then return alert error
            wx.scanQRCode
                desc: 'scanQRCode desc'
                needResult: 1
                scanType: ['qrCode']
                success: (res) ->
                    url  = res.resultStr
                    code = $scope.getQueryString url, 'c'
                    #alert code
                    $scope.submitQRCode code
                fail: (res) ->
                    alert '摄像头启动失败，建议检查是否有安全软件禁止微信启用摄像头' 
   

    #提交二维码值
    
    $scope.submitQRCode = (code) ->
        request.query
            url : 'wx/studentApi/scan'
            data:
                code: code
        , (error, info, data) ->
            if error then return alert error
            #alert JSON.stringify(data)
            $scope.success = true
            $scope.prompt = ''
            $scope.type = 0
            $scope.signname = data.signname
            $scope.signid = data.signid

    #手动刷新二维码
    $scope.refresh = ->
        $scope.setQRCode()

    #sid = $location.search().signid || ''
    ###
    if sid
        alert sid
    ###
    #判断并提交二维码值
    code = $location.search().c || ''
    if code
        $scope.success = false
        $scope.infomess = false
        $scope.helper = true 
        layer.msg '提交扫码结果中...'
        $scope.submitQRCode code
    else
        $scope.infomess = true
        $scope.success = false
        $scope.helper = false
        $scope.setQRCode()
        timer = $interval($scope.setQRCode, 10000)
    ###
    $scope.setQRCode()
    timer = $interval($scope.setQRCode, 10000)
    ###
    $rootScope.$on '$routeChangeStart', ->
        $interval.cancel(timer)
    
    $scope.stopInterval = ->
        $interval.cancel(timer)


#签到二维码
sign.controller 'qrcode2', ($scope, $rootScope, $interval, request) ->
    #获取二维码字符串
    $scope.getQRCode = (callback) ->
        callback ||= ->
        request.query
            url : 'wx/adminApi/getQRCode'
            data:
                oldQRCode:$scope.oldQRCode
        , (error, info, data) ->
            callback error, data?.QRCode

    #设置二维码字符串
    $scope.setQRCode = (count, callback) ->
        callback ||= ->
        $scope.getQRCode (error, QRCode) ->
            $scope.error = error
            $scope.oldQRCode = QRCode
            url = "#{$rootScope.baseUrl}#/in?c=#{QRCode}"
            $('.qrcode.image').html('').qrcode(url)
            callback error, QRCode

    #手动刷新二维码
    $scope.refresh = ->
        $scope.setQRCode()

    #手动添加
    $('.ui.form').form
        inline:true
        on: 'blur'
        fields:
            num:
                identifier : 'num'
                rules: [
                    type   : 'empty'
                    prompt : '学号不能为空'
                ]
        onSuccess: ->
            $scope.loading = true
            request.query
                url : 'wx/adminApi/registerSign'
                data:
                    num : $scope.num
                    name: $scope.name
            , (error, info, data) ->
                $scope.loading = false
                if error then return alert error
                $scope.num = $scope.name = ''
                layer.msg data?.prompt || '签到成功'

    $scope.setQRCode()
    timer = $interval($scope.setQRCode, 5000)
    $rootScope.$on '$routeChangeStart', ->
        $interval.cancel(timer)

#教学班管理
sign.controller 'class', ($scope, $rootScope, $timeout, $filter, request) ->
    $timeout ->
        $('.help.circle.icon').popup()
        $('.ui.dropdown').dropdown()
    $scope.class =
        key     : ''
        per     : 15
        page    : 1
        sortName: 'num'
        orderBy : false
        data    : []
        result  : []
        action: (key, page, sortName, orderBy)->
            if this.data.lenght is 0 then return
            this.key  = key  || this.key
            this.page = page || this.page
            if sortName is this.sortName
                this.orderBy = !this.orderBy
            else if typeof orderBy is 'boolean'
                this.orderBy = orderBy
            this.sortName = sortName || this.sortName
            this.offset = (this.page - 1) * this.per
            this.result = $filter('filter')(this.data, this.key)
            this.result = $filter('orderBy')(this.result, this.sortName, this.orderBy)
            this.total  = this.result.length
            this.result = $filter('cut')(this.result, this.offset, this.offset + this.per)
            $rootScope.scrollTop()

    #获取用户列表
    $scope.getList = ->
        $scope.loading = true
        request.query
            url : 'wx/adminApi/getClassSignData'
        , (error, info, data) ->
            $scope.loading    = false
            $scope.error      = error
            $scope.class.data = data
            $scope.class.action()

    $scope.getList()


#签到空间详细列表
sign.controller 'signspacedetail', ($scope, $location,FileUploader, request) ->
    $scope.data = []
    $scope.getSpaceList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #alert JSON.stringify($scope.data.slice(-1)[0])
        params = $scope.lastrecord
        request.query
            url   : 'wx/adminApi/getSignSpaceDetail'
            params: if isMore then params else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.spacename = data.spacename
            $scope.spaceid = data.spaceid
            $scope.myemail=data.email
            $scope.title = "该空间共#{data.usercount}人"
            $scope.spacecount = data.spacecount
            $scope.signs = data.spacesign
            $scope.data  = $scope.data.concat data.list
            if data.list.length
                $scope.lastrecord = data.list.slice(-1)[0]

            if !$scope.data.length
                $scope.error = "暂无人员信息"
            else if !data.list.length
                $scope.error = '没有更多了...'
            jdata=$scope.data
            if $scope.issort
                #jdata = $scope.data
                jdata.sort($scope.sortnum)
            else
                jdata.sort($scope.sortdepartment)
    $scope.getSpaceList(false)
    
    $scope.showsign = ->
        $('#showsigns').modal('show')

    $scope.sendEmail = ->
        if $scope.spacecount is 0
            alert '该空间下暂无签到信息哦^_^!'
            return
        $('#smail').modal('show')
        $('input[name="mail"]').val($scope.myemail)

    $scope.clearEmailInfo = ->
        $('input[name="mail"]').val('')
        $('textarea[name="leftword"]').val('')

    $scope.startSendEmail = ->
        params = {}

        $email = $('input[name="mail"]').val()
        $leftword = $('textarea[name="leftword"]').val()
        $spaceid = $scope.spaceid
        $spacename = $scope.spacename

        params['to']=$email
        #params['title']=$scope.signname
        params['leftword']=$leftword
        params['spaceid'] = $spaceid
        params['spacename']=$spacename
        #alert JSON.stringify(params)
        $('#smail').modal('hide')
        request.query
            url   : 'wx/commonApi/sendMailSpace'
            params: params
        , (error, info, data) ->
            if error
                alert error
            if data.status
                alert '邮件已发送成功，注意查收!'
            else
                alert '发送失败，请稍后重试'


    
    $scope.sortbynum = ->
        $scope.issort = true
        jdata = $scope.data
        jdata.sort($scope.sortnum)
        #alert JSON.stringify(jdata)
        #$scope.data  = $scope.data.concat data.list
    $scope.sortbydepartment = ->
        $scope.issort = false
        jdata = $scope.data
        jdata.sort($scope.sortdepartment)
        #alert JSON.stringify(jdata)
        #$scope.data  = $scope.data.concat data.list

    $scope.sortnum = (d1,d2) ->
        #return d1.num - d2.num
        if d1.num >=  d2.num then return 1
        if d1.num <   d2.num then return -1

    $scope.sortdepartment = (d1,d2) ->
        if d1.title >=  d2.title then return -1
        if d1.title <   d2.title then return 1



#签到空间管理
sign.controller 'signspace', ($scope, $location,FileUploader, request) ->
    $scope.data = []
    $scope.getSpaceList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #alert JSON.stringify($scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getSignSpace'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.spacecount = data.spacecount
            $scope.title = "签到空间共#{data.spacecount}个"
            #$scope.data  = $scope.data.concat data.list
            $scope.data  = data.list
            if !$scope.data.length
                $scope.error = "暂无空间记录"
            else if !data.list.length
                $scope.error = '没有更多了...'
    $scope.getSpaceList(false)

    uploader = $scope.uploader = new FileUploader url: 'wx/adminApi/createSignSpace'
    #alert $scope.uploader.onBeforeUploadItem

    $scope.checkname =(spacename)->
        $flag = false
        for item,i in $scope.data
            if item['spacename'] == spacename
                $flag = true
                break
        return $flag

    #uploader = $scope.uploader = new FileUploader url: ''
    $scope.uploader.onBeforeUploadItem  =(item) ->
        #item.formData = [{spacename:$spacename}]
        $selectValue = $('.ui.selection.dropdown').dropdown('get value')
        $spacename = $('input[name="spacename"]').val()
        $spacefile = $('input[name="spacefile"]').val()
        
                   
        $flag = $scope.checkname($spacename)
        if $selectValue == 'new'
            if $flag
                alert '名称与已有空间重复'
                $scope.uploader.cancelAll()
                return

        if !$selectValue
            alert '请选择是否创建新空间或者覆盖已有空间'
            $scope.uploader.cancelAll()
            return
        if $selectValue == 'nospace'
            $scope.uploader.cancelAll()
            return

        if !$spacename
            alert '请输入签到空间名称！'
            $scope.uploader.cancelAll()
            return
        $regxls=/[^\.](\.xls)$/i
        $regxlsx=/[^\.](\.xlsx)$/i
        if (!$regxls.test $spacefile) and (!$regxlsx.test $spacefile)
            alert '请输入Excel格式的文件'
            $scope.uploader.cancelAll()
            return
        $scope.uploaddisable = true
        $scope.showbar=true      
        item.formData = [{spacename:$spacename,selectvalue:$selectValue}]
    
    $scope.showhelp = ->
        $('#showhelp').modal('show')


    $scope.uploader.onProgressAll =(progress)->
        #alert progress

    $scope.uploader.onSuccessItem =(item, response, status, headers)->
        #alert JSON.stringify(response)
        $scope.clearSpaceInfo()
        alert '列表上传成功，请刷新空间列表！'
        $('.fullscreen.modal').modal('hide')

    $scope.uploader.onErrorItem =(item, response, status, headers)->
        #alert status

    $scope.clearQueue =->
        $scope.uploader.clearQueue() #清除队列中所有未上传的文件    

    $scope.createSpace = ->
        $scope.clearSpaceInfo()
        $scope.cover = false
        $('#creatspace').modal('show')
        $scope.showbar = false
        $('.ui.selection.dropdown').dropdown('clear')
        $('.ui.selection.dropdown').dropdown(
            #action:'hide',
            allowReselection:true,
            onChange:(value, text, $selectedItem)->
                if !text
                    return
                if value != 'new'
                    $scope.cover=true
                    #$('.ui.selection.dropdown').dropdown('set selected',1)
                    $('.ui.selection.dropdown').dropdown('set text','<i class="blue undo icon"></i>覆盖已有空间')
                    $scope.spacename=text
                    $scope.sapcenamedisable=true
                else
                    $scope.cover=false
                    $scope.spacename=''
                    $scope.sapcenamedisable=false

                if !$scope.$$phase then $scope.$digest()
        )

    $scope.clearSpaceInfo = ->
        $('input[name="spacename"]').val('')
        $('input[name="spacefile"]').val('')
        $('.ui.selection.dropdown').dropdown('clear')
        $scope.uploaddisable = false
        $scope.showbar=false
        $scope.uploader.clearQueue() #清除队列中所有未上传的文件


    ###
    $scope.submitSpaceInfo = (excelfile) ->
        alert excelfile
        $spacename = $('input[name="spacename"]').val()
        $spacefile = $('input[name="spacefile"]').val()
        if !$spacename
            alert '请输入签到空间名称！'
            return   
        $regxls=/[^\.](\.xls)$/i
        $regxlsx=/[^\.](\.xlsx)$/i
        if (!$regxls.test $spacefile) and (!$regxlsx.test $spacefile)
            alert '请输入Excel格式的文件'    
            return
        #uploader = $scope.uploader = new FileUploader url: 'wx/adminApi/createSignSpace' 

        #uploader = $scope.uploader = new FileUploader url: ''
        $scope.uploader.onBeforeUploadItem  =(item) -> 
            item.formData = [{spacename:$spacename}]

        $scope.uploader.onProgressAll =(progress)->
            alert progress

        $scope.uploader.onSuccessItem =(item, response, status, headers)->
            alert response.data.status

        $scope.uploader.onErrorItem =(item, response, status, headers)->
            alert status

        $scope.uploader.onCancelItem =(item, response, status, headers)->
            alert status
      
        FileUploader.upload
            url   : 'wx/adminApi/createSignSpace'
            method: 'PUT'
            file  : excelfile
            data  : {spacename:$spacename}
            fileFormDataName : 'spacefile'
        .progress (evt) ->
            #alert 'progress'
        .success (data, status, headers, config)->
          #alert 'success'
          $('#creatspace').modal('hide')
        .error (data, status, headers, config) ->
          alert 'error' 
    ###           
    #alert 'yes'
    #$scope.getSpaceList(false)

#教学空间
sign.controller 'teachingspace', ($scope, $rootScope, $location, request) ->
    $scope.dhref = $scope.fhref = 'javascript:void(0)'
    $scope.loading = true
    request.query
            url : 'wx/adminApi/getTeachingSpace'
        , (error, info, data) ->
            $scope.loading = false
            if error
                return alert error
            #alert JSON.stringify(data)
            $scope.hasExpire = data.hasExpire
            $scope.launchid = data.signid
            $scope.signname = data.signname
            $scope.signtime = data.signtime
            $scope.signlist = data.signlist
            $scope.sllength = data.signlist.length
            $scope.loading=false
    $scope.create = ->
        if $scope.hasExpire
            alert '暂无有效的发题空间'
            #$location.path("teachingcreate");
            return
        $scope.fhref = "#/teachingcreate?launchid=#{$scope.launchid}&hasExpire=#{$scope.hasExpire}&signname=#{$scope.signname}&signtime=#{$scope.signtime}&create=new"
    $scope.anwser = ->
        if $scope.sllength == 0
            alert '暂无有效的答题空间'
            return
        if $scope.sllength > 1
            #这里写答题的逻辑 
            $('#selectspace').modal('show')
            #alert ''
        else
            $scope.dhref = "#/teachinganswer?signname=#{$scope.signname}&launchid="+$scope.signlist[0]['id']
    $scope.selectone =(selectid) ->
        $('#selectspace').modal('hide')
        $scope.dhref = "#/teachinganswer?signname=#{$scope.signname}&launchid="+selectid
        #alert $scope.dhref
        $('#answerb').click();

#参与答题
sign.controller 'answer', ($scope, $rootScope, $interval, $location, request) ->
    $scope.loading = true
    $scope.submita = true
    $scope.isText = true
    $scope.showanswer = true
    $scope.seconds=0
    $scope.answerstatus = true
    request.query
            url : 'wx/adminApi/getAnswerInfo'
        , (error, info, data) ->
            $scope.loading = false
            if error
                return alert error
            
            #alert JSON.stringify(data)
            $scope.loading = false
            if data.anwserstatus == 'false' then $scope.answerstatus = false
            #alert data.anwserstatus
            if data.hasAnswer
                $scope.showanswer = false
                $scope.usedseconds = data.usedseconds
                $scope.tlaunchid = data.tlaunchid
                $scope.launchid = data.id
                $scope.answer = data.answer
                $scope.hasExpire = data.hasExpire 
                time = $scope.usedseconds
                h = Math.floor(time / 3600) + ''
                m = Math.floor((time % 3600) / 60) + ''
                s = (time % 60) + ''
                if h.length == 1
                    h = '0'+h
                if m.length == 1
                    m = '0'+m
                if s.length == 1
                    s = '0'+s
                str = h + ':' + m + ':' + s
                $scope.secondstr = str

            else
                if !data.hasExpire
                    if data.hasQuestion
                        if data.anwserstatus == 'true'
                            if data.hasAnswer
                                $scope.showanswer = false
                                $scope.usedseconds = data.usedseconds
                                $scope.answer = data.answer
                            else
                                $scope.submita = false
                                $scope.isText = false
                                $scope.informtext = '答题进行中，请抓紧时间填写答案哦^_^'
                                $scope.tlaunchid = data.tlaunchid
                                $scope.launchid = data.id
                                $scope.signname = data.signname
                                $scope.time = data.time
                                $scope.hasAnswer = data.hasAnswer
                                $scope.secondstr = '00:00:00'
                                $scope.timer = $interval($scope.tracktime, 1000)                                
                                #alert '赶紧答题'
                        else
                            $scope.informtext = '该题目已经结束了哦^_^!'
                            #alert '该题目已经结束了哦^_^!'
                    else
                        $scope.informtext = '你没有需要回答的问题哦^_^'
                        #alert '你没有需要回答的问题哦^_^'    
                else
                    $scope.informtext = '该空间已过期'
                    #alert '该空间已过期'
   
    $scope.tracktime = ->
        time = $scope.seconds++
        h = Math.floor(time / 3600) + ''
        m = Math.floor((time % 3600) / 60) + ''
        s = (time % 60) + ''
        if h.length == 1
            h = '0'+h
        if m.length == 1
            m = '0'+m
        if s.length == 1
            s = '0'+s
        str = h + ':' + m + ':' + s
        $scope.secondstr = str
        #alert $scope.secondstr


    $scope.showstatistic = ->
        if $scope.answerstatus && !$scope.hasExpire
            alert '答题结束后才可以查看哦^_^'
            return
        S1 = []
        request.query
            url   : 'wx/adminApi/getAnswerStatisitcs'
            params: {tlaunchid:$scope.tlaunchid}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.usercount = data.usercount
            #alert JSON.stringify(data.list)
            $.each data.list,(k,v)->
                S1.push [k,v]
            #alert JSON.stringify(S1)
            if $scope.usercount == 0
                alert '暂无参与答题用户'
                return
            $('.fullscreen.modal').modal('show')
            plot8 = $.jqplot('chart', [S1], {
                grid: {
                    drawBorder: false,
                    drawGridlines: false,
                    background: '#ffffff',
                    shadow:false
                },
                axesDefaults: {

                },
                seriesDefaults:{
                    renderer:$.jqplot.PieRenderer,
                    rendererOptions: {
                        showDataLabels: true,
                        padding: 4,
                        sliceMargin: 1
                    }
                },
                legend: {
                    show: true,
                    rendererOptions: {

                    },
                    xoffset: 2,
                    location: 'e'
                },
            })
            $('.fullscreen.modal').modal('show')

    $scope.submitanswer = ->
       #转换答案格式 如 bac-》ABC
       $answer = $scope.inanswer
       
       reg= /^[A-Za-z0-9]+$/
       if !reg.test $answer
           alert '请按要求填写答案'
           return
       arr = $answer.split ''
       for item,i in arr
           for j in [0..i-1]
               if arr[i] < arr[j]
                   temp = arr[i]
                   arr[i] = arr[j]
                   arr[j] = temp
       $answer=''
       for s in arr
           $answer = $answer + s
       $answer=$answer.toUpperCase()      
 
       $usedseconds = $scope.seconds
       if String($answer+'').length is 0 then return
       params = {}
       params['tlaunchid']=$scope.tlaunchid
       params['launchid']=$scope.launchid
       params['answer']=$answer
       params['signname']=$scope.signname
       params['time']=$scope.time
       params['usedseconds']=$usedseconds    
       #alert JSON.stringify(params)
       $interval.cancel($scope.timer)
       $scope.submita = true
       request.query
            url : 'wx/adminApi/submitAnswer'
            params:params
        , (error, info, data) ->
            $scope.loading = false
            if error
                return alert error
                $scope.submita = false
            #alert JSON.stringify(data)
            if data.answerresult == 'ok'
                $scope.showanswer = false
                $scope.usedseconds = $usedseconds
                $scope.answer = $answer          
                alert '答案提交成功'

#发起答题
sign.controller 'teachingcreate', ($scope, $location, $interval, request) ->
    $scope.editshow = true
    $scope.save = false
    $scope.inputshow = false
    $scope.anwser = true
    $scope.status = '未开始'    
    $scope.anwserstatus = 'false'   
    
    $scope.data = []
 
    $scope.hasExpire=true
    if $location.search().hasExpire == 'false' then $scope.hasExpire = false
    
    $scope.launchid = $location.search().launchid 
    $scope.signname = $location.search().signname
    $scope.signtime = $location.search().signtime 
    $scope.create = $location.search().create
    $scope.tlaunchid = $location.search().tlaunchid
    $scope.seconds = 0 
    $scope.secondstr = '00:00:00'
    $scope.anwsers = '暂无'
    $scope.notes = '暂无'
    $scope.questionnum = '暂无'
    $scope.listflag = false
    $scope.slidedown = false
    $scope.slideup = true
    
    $scope.title = "答题还未开始，暂无记录"
    
    
    #alert $scope.launchid
    if $scope.create == 'old'
        request.query
            url : 'wx/adminApi/getQuestionInfo'
            params:{launchid:$scope.launchid,tlaunchid:$scope.tlaunchid}
        , (error, info, data) ->
            $scope.loading = false
            if error
                return alert error
            #alert JSON.stringify(data)
            $scope.hasExpire = data.hasExpire
            $scope.anwserstatus = data.anwserstatus
            $scope.anwsers = data.anwser
            starttime = data.starttime
            starttime = starttime.replace(/-/g,"/")
            date =  new Date(starttime)
            ime_str = date.getTime().toString()
            timestamp = Date.parse(new Date())
            $scope.seconds = (timestamp-ime_str)/1000
            $scope.notes = data.notes
            $scope.questionnum = data.questionnum
            if $scope.hasExpire
                $scope.status = '已过期'
                $scope.seconds = data.seconds
            else if $scope.anwserstatus == 'true'
                $('.ui.toggle.checkbox').checkbox('check')
                $scope.status = '答题中'
                $scope.timer = $interval($scope.tracktime, 1000)
            else
                $('.ui.toggle.checkbox').checkbox('uncheck')
                $scope.status = '已结束'
                $scope.seconds = data.seconds
            time = $scope.seconds
            h = Math.floor(time / 3600) + ''
            m = Math.floor((time % 3600) / 60) + ''
            s = (time % 60) + ''
            if h.length == 1
                h = '0'+h
            if m.length == 1
                m = '0'+m
            if s.length == 1
                s = '0'+s
            str = h + ':' + m + ':' + s
            $scope.secondstr = str            
            $scope.getList(false)

    $scope.getList = (isMore) ->
        if $scope.create == 'new' then return
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #alert $scope.data.length
        #if $scope.data.length > 0
        #    params = $scope.data.slice(-1)[0]
        #params = params.concat {tlaunchid:$scope.tlaunchid}
        #alert JSON.stringify(params)
        request.query
            url   : 'wx/adminApi/getAllAnwserUsers'
            params: if isMore then params = $scope.data.slice(-1)[0] else {tlaunchid:$scope.tlaunchid}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            
            $scope.data  = $scope.data.concat data.list
            $scope.title = "参与答题共#{data.count}人" 
            if $scope.data.length then $scope.listflag = true
            if !$scope.data.length
                $scope.title = "暂无参与答题记录"
            else if !data.list.length
                $scope.error = '没有更多了...'       


    $scope.showstatistic = ->
        S1 = []
        request.query
            url   : 'wx/adminApi/getAnswerStatisitcs'
            params: {tlaunchid:$scope.tlaunchid}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.usercount = data.usercount
            #alert JSON.stringify(data.list)
            $.each data.list,(k,v)->
                S1.push [k,v]
            #alert JSON.stringify(S1)
            if $scope.usercount == 0
                alert '暂无参与答题用户'
                return    
            $('.fullscreen.modal').modal('show')
            plot8 = $.jqplot('chart', [S1], {
                grid: {
                    drawBorder: false, 
                    drawGridlines: false,
                    background: '#ffffff',
                    shadow:false
                },
                axesDefaults: {
             
                },
                seriesDefaults:{
                    renderer:$.jqplot.PieRenderer,
                    rendererOptions: {
                        showDataLabels: true,
                        padding: 4,
                        sliceMargin: 1
                    }
                },
                legend: {
                    show: true,
                    rendererOptions: {
                   
                    },
                    xoffset: 2,
                    location: 'e'
                }, 
            })
            $('.fullscreen.modal').modal('show')

    $scope.editAnwser = ->
        #alert $scope.hasExpire
        if $scope.hasExpire then return
        $scope.anwser = false
        $scope.editshow = false
        $scope.save = true
        $scope.inputshow = true
        
        if !$scope.$$phase then $scope.$digest()
    #保存新的签到命名
    $scope.saveAnwser = ->
        if $scope.haveExpire then return
        params = {}
        $anwsers = $('#anwsers').val()
        $notes = $('#notes').val()
        $questionnum = $('#questionnum').val()

        reg= /^[A-Za-z]+$/
        $anwsers=$anwsers.toUpperCase()
        if !(($anwsers == $scope.anwsers)&&($notes==$scope.notes)&&($questionnum==$scope.questionnum))

            #if !reg.test $anwsers && $anwsers
                #alert '请按要求填写答案'
                #return
            arr = $anwsers.split ''
            for item,i in arr
                for j in [0..i-1]
                    if arr[i] < arr[j]
                        temp = arr[i]
                        arr[i] = arr[j]
                        arr[j] = temp
            $anwsers=''
            for s in arr
                $anwsers = $anwsers + s
            $anwsers=$anwsers.toUpperCase()

            $scope.anwsers = $anwsers
            $scope.notes = $notes
            $scope.questionnum = $questionnum
            if $scope.create != 'new'
                params['launchid']=$scope.launchid
                params['anwserstatus']=$scope.anwserstatus
                params['anwser']= $scope.anwsers
                params['notes']= $scope.notes
                params['questionnum']= $scope.questionnum
                params['signname']=$scope.signname
                params['signtime']= $scope.signtime
                params['seconds']=$scope.seconds
                params['create']=$scope.create
                params['tlaunchid']=$scope.tlaunchid
                #请求后台更改签到状态
                #alert JSON.stringify(params)

                request.query
                    url   : 'wx/adminApi/updateAnwserStatus'
                    params: params
                , (error, info, data) ->
                    $scope.loading = false
                    if error
                        return alert error
                    #alert JSON.stringify(data)
                    switch data.launchresult
                        when 'ok'
                            alert '信息修改成功'
                        else
                            alert '更改失败，请稍后重试'

        $scope.editshow = true
        $scope.save = false
        $scope.inputshow = false
        $scope.anwser = true
        if !$scope.$$phase then $scope.$digest() 

    $scope.hideshow = (showstatus) ->
        if 'up' == showstatus
            $scope.slidedown = true
            $scope.slideup = false
        else
            $scope.slidedown = false
            $scope.slideup = true
        if !$scope.$$phase then $scope.$digest()
        $("#infotable").slideToggle(200)

    $scope.changeAnwserStatus =->
        params = {}
        #alert $scope.hasExpire
        #alert $scope.anwserstatus=='false'
        if $scope.hasExpire then return
       
        switch $scope.anwserstatus
            when 'true'
                params['launchid']=$scope.launchid
                params['anwserstatus']='false'
                params['anwser']= $scope.anwsers
                params['notes']= $scope.notes
                params['questionnum']= $scope.questionnum
                params['signname']=$scope.signname
                params['signtime']= $scope.signtime
                params['seconds']=$scope.seconds
                params['create']=$scope.create
                params['tlaunchid']=$scope.tlaunchid
                #请求后台更改签到状态
                #alert JSON.stringify(params)
                
                request.query
                    url   : 'wx/adminApi/updateAnwserStatus'
                    params: params
                , (error, info, data) ->
                    $scope.loading = false
                    if error
                        return alert error
                    #alert JSON.stringify(data)
                    switch data.launchresult
                        when 'ok'
                            $scope.status = '已结束'
                            $scope.anwserstatus = 'false'
                            $scope.create='old'
                            $scope.tlaunchid = data.tlaunchid
                            $interval.cancel($scope.timer);
                            $('.ui.toggle.checkbox').checkbox('uncheck')
                            alert '状态更改成功，已结束'
                            #alert $scope.seconds
                        else
                            alert '状态更改失败，请稍后重试'
                            $('.ui.toggle.checkbox').checkbox('check')                
            when 'false'
                params['launchid']=$scope.launchid
                params['anwserstatus']='true'
                params['anwser']= $scope.anwsers
                params['notes']= $scope.notes
                params['questionnum']= $scope.questionnum
                params['signname']=$scope.signname
                params['signtime']= $scope.signtime
                params['seconds']=$scope.seconds
                params['create']=$scope.create
                params['tlaunchid']=$scope.tlaunchid
                #alert JSON.stringify(params)
                request.query
                    url   : 'wx/adminApi/updateAnwserStatus'
                    params: params
                , (error, info, data) ->
                    $scope.loading = false
                    if error
                        return alert error
                    #alert JSON.stringify(data)
                    switch data.launchresult
                        when 'ok'
                            $scope.status = '答题中'
                            $scope.create='old'
                            $scope.tlaunchid = data.tlaunchid
                            $scope.anwserstatus = 'true'
                            $scope.timer = $interval($scope.tracktime, 1000)
                            $('.ui.toggle.checkbox').checkbox('check') 
                            alert '状态更改成功，答题中'
                        else
                            alert '状态更改失败，请稍后重试'
                            $('.ui.toggle.checkbox').checkbox('uncheck')    

    if !$scope.$$phase then $scope.$digest()    
    $scope.tracktime = ->
        time = $scope.seconds++
        h = Math.floor(time / 3600) + ''
        m = Math.floor((time % 3600) / 60) + ''
        s = (time % 60) + ''
        if h.length == 1
            h = '0'+h
        if m.length == 1
            m = '0'+m
        if s.length == 1
            s = '0'+s
        str = h + ':' + m + ':' + s
        $scope.secondstr = str  


#参与签到记录列表
sign.controller 'signhistory', ($scope, $location, request) ->
    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #alert JSON.stringify($scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getSignHistory'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "你参与签到共#{data.count}次"
            $scope.data  = $scope.data.concat data.list
            if !$scope.data.length
                $scope.error = "暂无参与签到记录"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)

#所有题目列表
sign.controller 'distributelist', ($scope, $location, request) ->

    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #if isMore then alert JSON.stringify(scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getDistributeList'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "你发起答题共#{data.count}次"
            $scope.data  = $scope.data.concat data.list
            #alert JSON.stringify($scope.data.slice(-1)[0])
            #alert JSON.stringify(data)
            if !$scope.data.length
                $scope.error = "暂无历史发起答题信息"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)

#所有答题列表
sign.controller 'answerlist', ($scope, $location, request) ->

    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #if isMore then alert JSON.stringify(scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getAnswerList'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "你答题共#{data.count}次"
            $scope.data  = $scope.data.concat data.list
            #alert JSON.stringify($scope.data.slice(-1)[0])
            #alert JSON.stringify(data)
            if !$scope.data.length
                $scope.error = "暂无历史发起答题信息"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)


#参与答题列表
sign.controller 'answerhistory', ($scope, $location, request) ->

    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #if isMore then alert JSON.stringify(scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getAnswerHistory'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "答题空间共#{data.count}个"
            $scope.data  = $scope.data.concat data.list
            #alert JSON.stringify($scope.data.slice(-1)[0])
            #alert JSON.stringify(data)
            if !$scope.data.length
                $scope.error = "暂无历史答题空间信息"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)


#发起答题列表
sign.controller 'distributehistory', ($scope, $location, request) ->

    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #if isMore then alert JSON.stringify(scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getDistributeHistory'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "答题空间共#{data.count}次"
            $scope.data  = $scope.data.concat data.list
            #alert JSON.stringify($scope.data.slice(-1)[0])
            #alert JSON.stringify(data)
            if !$scope.data.length
                $scope.error = "暂无历史答题空间信息"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)

#发起签到列表
sign.controller 'signlaunchhistory', ($scope, $location, request) ->
    
    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #if isMore then alert JSON.stringify(scope.data.slice(-1)[0])
        request.query
            url   : 'wx/adminApi/getLaunchHistory'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            #alert JSON.stringify(data)
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "你发起签到共#{data.count}次"
            $scope.data  = $scope.data.concat data.list
            #alert JSON.stringify($scope.data.slice(-1)[0])
            #alert JSON.stringify(data)
            if !$scope.data.length
                $scope.error = "暂无历史发起签到信息"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)

#当前签到列表
sign.controller 'signlist', ($rootScope, $scope, $location, request) ->
     
    #alert $rootScope.signname
    #alert $rootScope.signid
    #res={"signid":$rootScope.signid,"signname":$rootScope.signname}
    res={}
    $scope.lastrecord = []
    #alert JSON.stringify(res)
    $scope.data = []
    $scope.infotable = true
    $scope.issort = false
    $scope.slideup = true
    
    $scope.getList = (isMore) ->
        #if $scope.haveExpire then return
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        #$scope.isLaunch = false;
        params = {}
        if isMore
            #params = $scope.data.slice(-1)[0]
            params = $scope.lastrecord
            #params['signid'] = $rootScope.signid
            #params['signname'] = $rootScope.signname
            #alert JSON.stringify(params)
        request.query
            url   : 'wx/adminApi/getSignList'
            params: if isMore then params else res
        , (error, info, data) ->
            $scope.loading = false
            if error then return $scope.error = error
            #alert JSON.stringify(data)
            #alert data.start
            #alert data.count
            $scope.isLaunch = data.isLaunch
            if !$scope.isLaunch then $scope.isHelp = true
            $scope.signcode = data.signcode
            $scope.signname = data.signname
            $scope.signdate = data.ymd
            $scope.signid = data.signid
            $scope.myemail = data.email
            #alert $scope.spacelist 
            if data.spacelist.length
                $scope.spacelist = data.spacelist
                $scope.signedpeople = []
            if data.spacecount
                $scope.spacecount = data.spacecount
            
            $scope.peopleinspace = data.peopleinspace
            $scope.spaceids=data.spaceids
            $scope.spacecount = $scope.spaceids.length
            $scount=data.spacecount
            #alert JSON.stringify(data.spaceids)
            #if data.spaceids.length
            #$('.ui.multiple.dropdown').dropdown('show')
            $scope.haveExpire = data.haveExpire
            $scope.isSee = data.isSee
            #用户可见
            if $scope.isSee
                $scope.cs = true
            else
                $scope.errorlist='签到列表不可见^_^!'
                $scope.ncs = true
            #签到状态
            $scope.signstatus = data.haveEnd          
            switch $scope.signstatus
                when true
                    $scope.statusmsg = '已结束'
                when false
                    $scope.statusmsg = '签到中'
            if $scope.haveExpire
                $scope.statusmsg = '已过期'

            #签到ID
            $scope.signid = data.signid
            $scope.signnameshow = true
            $scope.editshow = false
            $scope.editicon = true
            $scope.saveicon = false
                       
            if data.list.length
                $scope.lastrecord = data.list.slice(-1)[0]
                $scope.signedpeople = $scope.data.concat data.list
            #alert JSON.stringify($scope.lastrecord)
            $scope.title = "共成功签到了#{data.count}次"
            #$scope.data  = $scope.data.concat data.list
            $scope.data = $scope.signedpeople.concat()
            #求签到空间与签到列表的并集            
            #alert JSON.stringify($scope.data) 
            #alert JSON.stringify($scope.peopleinspace)
            $globallist=[]
            $arr1=$scope.data
            $arr2=$scope.peopleinspace
            #alert JSON.stringify($arr1)
            #alert JSON.stringify($arr2)
            $index=0
            for item1,i in $arr1
                $usernum = item1['num']+""
                $username = item1['username']+""
                for item2,j in $arr2
                    $susernum = item2['num']+""
                    $susername = item2['username']+""
                    #alert $usernum+$susernum+$username+$susername
                    if $usernum == $susernum and $username == $susername
                        $arr1[i]['isInSpace']=true
                        $arr2[j]['haveSign']='yes'
                        break
                #$arr1[i]['num']=$scope.replacestr($usernum)                
                
                $globallist[i] = $arr1[i]
                $index = i
            #alert JSON.stringify($globallist)
            for item,k in $arr2
                $hs=item['haveSign']
                $num = $arr2[k]['num']+""
                if $hs is 'no'
                    $index++
                    #$arr2[k]['num']=$scope.replacestr($num)
                    $globallist[$index]=$arr2[k]            
            $scope.data=$globallist
            #alert alert JSON.stringify($globallist)
            jdata = $scope.data
            #$scope.lastrecord = $scope.data.slice(-1)[0]
            if $scope.issort
                #jdata = $scope.data
                jdata.sort($scope.sortnum)
            if !$scope.issort
                jdata.sort($scope.sorttime)
            
            #alert JSON.stringify(jdata)
            if !$scope.data.length
                $scope.error = "暂无签到记录"
            else if !data.list.length
                $scope.error = '点击加载更多...'
            if !$scope.$$phase then $scope.$digest()
    
    $scope.replacestr =(num)->
        num=num+""
        $len = num.length
        $start = $len-5
        $end = $len - 1
        $s1=num.substring(0,$start)
        $s2=num.substring($end)
        $s3=$s1+'****'+$s2
        return $s3


    $scope.sortbynum = ->
        $scope.issort = true
        jdata = $scope.data
        jdata.sort($scope.sortnum)
        #alert JSON.stringify(jdata)
        #$scope.data  = $scope.data.concat data.list
    $scope.sortbytime = ->
        $scope.issort = false
        jdata = $scope.data
        jdata.sort($scope.sorttime)
        #alert JSON.stringify(jdata)
        #$scope.data  = $scope.data.concat data.list

    $scope.sortnum = (d1,d2) ->
        #return d1.num - d2.num
        if d1.num >=  d2.num then return 1
        if d1.num <   d2.num then return -1
    
    $scope.sorttime = (d1,d2) ->
        if d1.time >=  d2.time then return -1
        if d1.time <   d2.time then return 1


    $scope.changeSignStatus =->
        params = {}
        if $scope.haveExpire then return
        switch $scope.signstatus
            when true
                #请求后台更改签到状态
                #alert $scope.signid
                params['signid']=$scope.signid
                params['signstatus']=false
                request.query
                    url   : 'wx/adminApi/updateSignStatus'
                    params: params
                , (error, info, data) ->
                    $scope.loading = false
                    if error
                        return alert error
                    #alert JSON.stringify(data)
                    switch data.result
                        when 'ok'
                            $scope.statusmsg = '签到中'
                            $scope.signstatus = false
                            alert '状态更改成功，签到中'
                        when 'error'
                            alert '状态更改失败，请稍后重试'
                            $('.ui.toggle.checkbox').checkbox('uncheck')
            when false
                params['signid']=$scope.signid
                params['signstatus']=true
                request.query
                    url   : 'wx/adminApi/updateSignStatus'
                    params: params
                , (error, info, data) ->
                    $scope.loading = false
                    if error
                        return alert error
                    #alert JSON.stringify(data)
                    switch data.result
                        when 'ok'
                            $scope.statusmsg = '已结束'
                            $scope.signstatus = true
                            alert '状态更改成功，已结束'
                        when 'error'
                            alert '状态更改失败，请稍后重试'
                            $('.ui.toggle.checkbox').checkbox('check')
               
        #$('.ui.toggle.checkbox').checkbox('check')
    
    #更改用户是否可见
    $scope.changeSeenable = (cansee) ->
        if $scope.haveExpire then return
        params = {}
        #alert cansee
        params['signid']=$scope.signid
        params['isseen']=cansee
        #alert JSON.stringify(params)
        request.query
            url   : 'wx/adminApi/updateIsSeen'
            params: params
        , (error, info, data) ->
            $scope.loading = false
            if error
                return alert error
            #alert JSON.stringify(data)
            switch data.result
                when 'ok'
                    #$scope.signname = $signname
                    alert '用户可见状态修改成功'
                when 'error'
                    alert '修改失败，请稍后重试'

    $scope.editSignSpace = ->
        $('#espace').modal('show')
        if $scope.spaceids.length
            for item,i in $scope.spaceids
                #alert item
                $('.ui.multiple.dropdown').dropdown('set selected',item)
            #$('.ui.multiple.dropdown').dropdown('set selected',33)
        $('.ui.multiple.dropdown').dropdown(
            onChange:(value, text, $selectedItem)->
                #$('.ui.multiple.dropdown').dropdown('set selected',34)
                $scope.spacecount=value.split(',').length
                if !$scope.$$phase then $scope.$digest()
                #alert $scope.spacecount
                
        )


    $scope.submitSpaceTable = ->
        $spacevalue = $('.ui.multiple.dropdown').dropdown('get value')
        
        if $spacevalue == 'nospace'
            return 
        $signid = $scope.signid
 
        request.query
            url   : 'wx/adminApi/updataSpaceTable'
            params: {'spacevalue':$spacevalue,'signid':$signid}
        , (error, info, data) ->
            if error
                alert error
            #alert JSON.stringify(data)
            alert '空间关联成功,请刷新签到列表！'
            $('#espace').modal('hide')
                


    $scope.sendEmail = ->
        $('#smail').modal('show')
        $('input[name="mail"]').val($scope.myemail)        

    $scope.clearEmailInfo = ->
        $('input[name="mail"]').val('')
        $('textarea[name="leftword"]').val('')
        
    $scope.startSendEmail = ->
        params = {}
      
        $email = $('input[name="mail"]').val()
        $leftword = $('textarea[name="leftword"]').val()
        $signid = $scope.signid
        $signcode = $scope.signcode

        params['to']=$email
        params['title']=$scope.signname
        params['leftword']=$leftword  
        params['signid'] = $signid
        params['signcode']=$signcode
        #alert JSON.stringify(params)
        $('#smail').modal('hide')
        request.query
            url   : 'wx/commonApi/sendMail'
            params: params
        , (error, info, data) ->
            if error
                alert error 
            if data.status
                alert '邮件已发送成功，注意查收!'
            else
                alert '发送失败，请稍后重试'
    $scope.hideshow = (showstatus) ->
        if 'up' == showstatus
            $scope.slidedown = true
            $scope.slideup = false
        else
            $scope.slidedown = false
            $scope.slideup = true
        if !$scope.$$phase then $scope.$digest()
        $("#infotable").slideToggle(200)
   
    $scope.editSignname = ->
        if $scope.haveExpire then return
        $scope.signnameshow = false
        $scope.editshow = true
        $scope.editicon = false
        $scope.saveicon = true
        if !$scope.$$phase then $scope.$digest()
    #保存新的签到命名
    $scope.saveSignname = ->
        if $scope.haveExpire then return
        params = {}
        $signname = $('#newSignname').val()
        if $signname == $scope.signname
            $scope.signnameshow = true
            $scope.editshow = false
            $scope.editicon = true
            $scope.saveicon = false
            if !$scope.$$phase then $scope.$digest()
            return

        params['signid']=$scope.signid
        params['signname']=$signname
        request.query
            url   : 'wx/adminApi/updateSignName'
            params: params
        , (error, info, data) ->
            $scope.loading = false
            if error
                return alert error
            #alert JSON.stringify(data)
            switch data.result
                when 'ok'
                    $scope.signname = $signname
                    alert '名称修改完成'
                when 'error'
                    alert '修改失败，请稍后重试'

        #alert $signname
        $scope.signnameshow = true
        $scope.editshow = false
        $scope.editicon = true
        $scope.saveicon = false
        if !$scope.$$phase then $scope.$digest()

    #$scope.showtable()
    $scope.getList(false)

    

#签到点详情
sign.controller 'desk', ($scope, $location, request) ->
    $scope.data = []
    $scope.getList = (isMore) ->
        if $scope.loading then return
        $scope.error   = null
        $scope.loading = true
        request.query
            url   : 'wx/adminApi/getOnePlace'
            params: if isMore then $scope.data.slice(-1)[0] else {}
        , (error, info, data) ->
            $scope.loading = false
            if error then return $scope.error = error
            $scope.title = "#{data.name}今日共成功签到#{data.count}次"
            $scope.data  = $scope.data.concat data.list
            if !$scope.data.length
                $scope.error = "#{data.name}今日暂无签到情况"
            else if !data.list.length
                $scope.error = '没有更多了...'

    $scope.getList(false)

#切片
sign.filter 'cut', cutFilter = ->
    (object, start, end) ->
        object.slice(start || 0, end || object.length)
