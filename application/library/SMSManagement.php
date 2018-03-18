<?php

class SMSManagement
{

    /**
     * 发送短信
     * $phone 手机号，可以是多个，用英文逗号分开
     * $getType 发送类型
     * $param 短信模版参数
     */
    public function send($getType, $phone, $param = null)
    {
        // 获取短信验证码类型
        $getTypeArr = array(
            'register',
            'updatePsw',
            'updateEmail'
        );
        
        // 判断是否生成短信验证码
        if (in_array($getType, $getTypeArr)) {
            $param = $this->getVerifyCode();
        }
        
        $data = array(
            'content' => $this->getContent($getType, $param),
            'phones' => $phone,
            'signatureId' => 'sig-ppw',
            'type' => 1
        );
        
        // 判断是否真实发送短信
        $isSend = Yaf_Registry::get('config')->SMS->useSms;
        if ($isSend) {
            $sendData = $this->getSendSign($data);
            $apiUrl = env('apiUrl.dfb_management') . '/callservice/sender/send';
            $response = $this->curlPost($apiUrl, $sendData);
            $response = json_decode($response);
        } else {
            $response = (object) array(
                'msg' => '',
                'code' => 0
            );
        }
        
        $time = intval(time());
        
        // 记录短信发送记录
        $smssendrecordData = array(
            'id' => createId(),
            'phone' => $phone,
            'content' => $data['content'],
            'sendTime' => $time,
            'sendResult' => isset($response->msg) ? $response->msg : '',
            'gatewayError' => isset($response->code) ? $response->code : '0',
            'createTime' => $time
        );
        try {
            EshopOthers_SmssendrecordModel::addOne($smssendrecordData);
        } catch (Exception $e) {
            return false;
        }
        
        if (isset($response->code) && $response->code === 0) {
            $sendResult = true;
            
            // 如果为获取短信验证码，记录验证码发送记录
            if (in_array($getType, $getTypeArr)) {
                $sendResult = $this->recordVerificationCode($getType, $phone, $param);
            }
            
            return $sendResult;
        }
        
        return false;
    }

    /**
     * CURL POST请求
     */
    private function curlPost($url, $data)
    {
        // 将获取的时间赋值给成员属性$startTime
        $startTime = microtime(true);
        
        // 循环生成，不采用http_build_query，避免中文转码
        $query = '';
        foreach ($data as $key => $value) {
            $query .= '&' . $key . '=' . $value;
        }
        $query = ltrim($query, '&');
        
        $ch = curl_init(); // 初始化
        curl_setopt($ch, CURLOPT_URL, $url); // 设置选项，包括URL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query); // Post提交的数据包
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $output = curl_exec($ch); // 执行并获取HTML文档内容
        curl_close($ch); // 释放curl句柄
                         
        // 将获取的时间赋给成员属性$stopTime
        $stopTime = microtime(true);
        
        // 时间拼接毫秒数
        $startWm = explode(".", $startTime);
        $endWm = explode(".", $stopTime);
        $runData['serviceStart'] = date('Y-m-d H:i:s', $startWm[0]) . '.' . $startWm[1];
        $runData['serviceEnd'] = date('Y-m-d H:i:s', $endWm[0]) . '.' . $endWm[1];
        $runData['elapsed'] = intval(bcmul(bcsub($stopTime, $startTime, 4), 1000));
        $runData['arguments'][] = $data; // 这里需要是数组
        $runData['requestUri'] = $url;
        $runData['requestMethod'] = 'POST';
        $runData['result'] = $output;
        
        // 判断接口调用状态
        $response = json_decode($output);
        $runData['status'] = (isset($response->code) && $response->code === 0) ? '200' : '400';

        // 接口调用，记录运行日志
        TZ_Log::writeRunLog('api calls logging', $runData);
        
        return $output;
    }

    /**
     * 获取加密密钥
     */
    private function getSendSign($sendData)
    {
        array_filter($sendData);
        $appSecret = '0a1b9b48-301d-4e81-844b-c4cc29f8a348';
        $newData = $sendData;
        ksort($newData);
        reset($newData);
        // 循环生成，不采用http_build_query，避免中文转码
        $queryString = '';
        foreach ($newData as $key => $value) {
            $queryString .= '&' . $key . '=' . $value;
        }
        $queryString = ltrim($queryString, '&');
        $sendData['sign'] = md5($queryString . $appSecret);
        return $sendData;
    }

    /**
     * 获取短信内容
     */
    public function getContent($getType, $param)
    {
        switch ($getType) {
            // 注册时获取短信验证码
            case 'register':
                $txt = '验证码为' . $param . '，本验证码有效时长为15分钟，仅用于乒乒网注册，请勿告知他人。如有疑问可致电4001008899';
                break;
            // 修改密码时获取短信验证码
            case 'updatePsw':
                $txt = '验证码为' . $param . '，本验证码有效时长为15分钟，仅用于乒乒网修改密码校验，请勿告知他人。如有疑问可致电4001008899';
                break;
            // 修改邮箱时获取短信验证码
            case 'updateEmail':
                $txt = '验证码为' . $param . '，本验证码有效时长为15分钟，仅用于乒乒网修改邮箱校验，请勿告知他人。如有疑问可致电4001008899';
                break;
            // 商户发起交易通知
            case 'sendOrderInfo':
                $txt = $param . '在乒乒网向您发起一笔订单，请于48小时内完成支付。';
                break;
            // 店铺开通通知
            case 'agreeShop':
                $txt = '尊敬的乒乒网会员，您的店铺已开通，您可以登录乒乒网店铺后台管理您的店铺！';
                break;
            // 密码修改通知
            case 'updatePswSuccess':
                $txt = '尊敬的用户：您已成功修改乒乒网登录密码，请牢记新密码';
                break;
        }
        return $txt;
    }

    /**
     * 获取数字验证码
     */
    public function getVerifyCode()
    {
        // 读取配置文件是否发送参数
        $isSend = Yaf_Registry::get('config')->SMS->useSms;
        
        // 生成验证码：根据配置，生产环境时正常生成，测试环境时采用000000
        if ($isSend) {
            $num_arr = array(
                '0',
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9'
            );
            for ($m = 0; $m < 6; $m ++) {
                $code .= $num_arr[rand(0, 9)];
            }
            
            return $code;
        }
        
        return '000000';
    }
    
    // 记录验证码发送记录
    public function recordVerificationCode($getType, $phone, $param)
    {
        $verificationCodeData = array(
            'id' => createId(),
            'receiver' => $phone,
            'captcha' => $param,
            'type' => 'SMS',
            'module' => $getType,
            'times' => 0,
            'createTime' => intval(time()),
            'lastModTime' => intval(time()),
            'logicalDel' => 1
        );
        
        try {
            EshopUser_VerificationcodeModel::addOne($verificationCodeData);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 发送邮件
     * @param string $template
     *            模板唯一标识
     * @param string $title
     *            标题
     * @param array $xsmtpapi
     *            内容（需要模板解析的变量内容）
     * @return bool
     */
    public function sendEmail($template, $title, $xsmtpapi)
    {
        // 读取配置文件是否发送参数
        $emailConf = Yaf_Registry::get('config')->EMAIL;
        
        // 判断是否开启邮件发送
        if ($emailConf->useEmail) {
            if ($template && $title && $xsmtpapi) {
                // 模板发送请求地址
                $url = 'http://api.sendcloud.net/apiv2/mail/sendtemplate';
                
                $post_data = array(
                    'apiUser' => $emailConf->apiUser, // API_USER 是调用接口发信时的帐号
                    'apiKey' => $emailConf->apiKey, // API_KEY 是调用接口发信时的密码
                    'from' => $emailConf->from, // 发件人邮箱
                    'fromName' => $emailConf->fromName, // 发送人名称
                    'to' => '',
                    'subject' => $title, // 邮箱标题
                    'xsmtpapi' => json_encode($xsmtpapi), // 模板解析的变量内容
                                                          // 模板唯一标识
                    'templateInvokeName' => $template
                );
                
                // 将获取的时间赋值给成员属性$startTime
                $startTime = microtime(true);
                
                $ch = curl_init(); // 初始化
                curl_setopt($ch, CURLOPT_URL, $url); // 设置选项，包括URL
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
                curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
                curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // Post提交的数据包
                curl_setopt($ch, CURLOPT_TIMEOUT, 20); // 设置超时限制防止死循环
                curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
                $output = curl_exec($ch); // 执行并获取HTML文档内容
                curl_close($ch); // 释放curl句柄
                                 
                // 将获取的时间赋给成员属性$stopTime
                $stopTime = microtime(true);
                
                // 时间拼接毫秒数
                $startWm = explode(".", $startTime);
                $endWm = explode(".", $stopTime);
                $runData['serviceStart'] = date('Y-m-d H:i:s', $startWm[0]) . '.' . $startWm[1];
                $runData['serviceEnd'] = date('Y-m-d H:i:s', $endWm[0]) . '.' . $endWm[1];
                $runData['elapsed'] = intval(bcmul(bcsub($stopTime, $startTime, 4), 1000));
                $runData['arguments'][] = $post_data; // 这里需要是数组
                $runData['requestUri'] = $url;
                $runData['requestMethod'] = 'POST';
                $runData['result'] = $output;
                
                // 判断接口调用状态
                $result = json_decode($output, true);
                $runData['status'] = (isset($result['result']) && $result['result'] == true && isset($result['statusCode']) && $result['statusCode'] == 200) ? '200' : '400';
                
                // 接口调用，记录运行日志
                TZ_Log::writeRunLog('api calls logging', $runData);
                
                if (isset($result['result']) && $result['result'] == true && isset($result['statusCode']) && $result['statusCode'] == 200) {
                    return true;
                }
                
                return false;
            } else {
                $content = '邮件发送失败，错误原因：';
                if ($template) {
                    $content .= '$template值为空，';
                }
                if ($title) {
                    $content .= '$title值为空，';
                }
                if ($xsmtpapi) {
                    $content .= '$xsmtpapi值为空，';
                }
                TZ_Log::writeRunLog($content . '，发送时间：' . date('Y-m-d H:i:s'));
                
                return false;
            }
        }
        
        return true;
    }
}
