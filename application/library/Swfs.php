<?php

/**
 * 敏感词类
 * @author 140210
 */
class Swfs
{

    /**
     * 敏感词检测函数
     * @author wen
     * @param string $content
     *            检测内容
     */
    public function check($content)
    {
        $url = config('apilist.che001Swfs.url');
        $data = array(
            'content' => $content
        );
        $result = $this->curlPost($url, $data);
        // 判断是否有返回值
        if (! empty($result)) {
            // 返回结果转换json字符串
            $resultObj = json_decode($result);
            if ($resultObj->errcode === 0) {
                if (! empty($resultObj->data)) {
                    $swfsArr = array();
                    
                    // 重新赋值敏感词
                    foreach ($resultObj->data as $key => $val) {
                        $swfsArr[] = $val->word;
                    }
                    
                    // 去除重复敏感词
                    $swfsArr = array_unique($swfsArr);
                    
                    // 返回敏感词
                    return array(
                        'status' => 200,
                        'info' => '检测到敏感词',
                        'data' => $swfsArr
                    );
                } else {
                    return array(
                        'status' => 404,
                        'info' => '未检测到敏感词'
                    );
                }
            } else {
                return array(
                    'status' => 400,
                    'info' => '接口发生错误，请稍后重试'
                );
            }
        } else {
            // 调用公用发送邮件
            $this->sendWarningEmail('敏感词接口服务调用失败，请尽快查看，及时恢复服务。');
            return array(
                'status' => 500,
                'info' => '系统发生异常，请稍后重试'
            );
        }
    }

    /**
     * 公用发送邮件
     * @author wen
     * @param string $content
     *            发送内容
     */
    private function sendWarningEmail($content)
    {
        /*
         * 使用RabbitMQ解耦程序（异步处理发送错误警报邮件）
         */
        $MQproducer = new Mqproducer();
        $warningData = array(
            'uri' => 'Common/emailWarning',
            'uriType' => 'userUri',
            'content' => $content
        );
        $MQproducer->directProducer($warningData, 'emailWarningExchange', 'emailWarning');
    }

    /**
     * CURL POST请求
     * @author wen
     * @param string $url
     *            请求地址
     * @param array $data
     *            请求数据
     */
    private function curlPost($url, $data)
    {
        // 生成 URL-encode 之后的请求字符串
        $strData = http_build_query($data);
        
        // 将获取的时间赋值给成员属性$startTime
        $startTime = microtime(true);
        
        $ch = curl_init(); // 初始化
        curl_setopt($ch, CURLOPT_URL, $url); // 设置选项，包括URL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $strData); // Post提交的数据包
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
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
        $resultObj = json_decode($output);
        $runData['status'] = ($resultObj->errcode === 0) ? '200' : '400';

        // 接口调用，记录运行日志
        TZ_Log::writeRunLog('api calls logging', $runData);
        
        return $output;
    }
}
