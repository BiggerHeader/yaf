<?php
use \Httpful\Request;


class TZ_Api
{

    private $call = null;

    private $api = null;
    
    // 保存脚本开始执行时的时间（以微秒的形式保存）
    private $startTime = 0;
    
    // 保存脚本结束执行时的时间（以微秒的形式保存）
    private $stopTime = 0;

    public function __construct()
    {
        // 将获取的时间赋值给成员属性$startTime
        $this->startTime = microtime(true);
    }

    /**
     * 检查API返回状态码是否存在500, 503, 999
     *
     * @return boolean
     */
    public static function goneCode($code)
    {
        return in_array($code, array(
            500,
            503,
            999
        ));
    }

    /**
     * 记录API调用错误日志信息
     *
     * @param string $txt
     *            - 需要添加的错误提示信息
     */
    public static function addRunLog($txt, $param = array())
    {
        TZ_Log::writeRunLog($txt, $param);
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
     * 创建一个新实例
     *
     * @return object - instance
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * 获取API请求地址
     *
     * @return string
     */
    private function getUrl()
    {
        $url = config('apilist.' . $this->api . '.url');
        
        if (empty($url)) {
            $this->noURL = true;
            self::addRunLog('API请求地址缺失: ' . $this->api);
        }
        
        return $url;
    }

    /**
     * 获取API请求的成功码
     *
     * @return string or array
     */
    public function getOkCode()
    {
        return config('apilist.' . $this->api . '.okCode');
    }

    /**
     * 判断API返回的状态码是否为成功码
     *
     * @param string $code
     *            - API返回的状态码
     * @return boolean
     */
    public function isOkCode($code)
    {
        $okCode = $this->getOkCode();
        
        if (is_null($okCode)) {
            return false;
        } elseif (gettype($okCode) == 'string') {
            return $code == $okCode;
        } else {
            return in_array($code, $okCode);
        }
    }

    /**
     * 执行请求
     *
     * @return 执行解析响应
     */
    public function call()
    {
        // 当请求地址为空时，返回null
        if (isset($this->noURL)) {
            return null;
        }
        
        try {
            $response = $this->call->timeout(20)->send();
        } catch (\Httpful\Exception\ConnectionErrorException $e) {
            $response = (object) array(
                'code' => 999,
                'error' => 'API error: ' . $e->getMessage()
            );
        }
        
        return $this->parseResponse($response);
    }

    /**
     * 解析接口的响应，并将信息格式化
     *
     * @param object $response
     *            - 接收到的响应
     * @return object - 解析后的响应
     */
    private function parseResponse($response)
    {
        // 将获取的时间赋给成员属性$stopTime
        $this->stopTime = microtime(true);
        
        if (isset($response->body) && isset($response->body->code)) {
            $apiCode = $response->body->code;
        } else {
            $apiCode = $response->code;
        }
        
        $is_gone = self::goneCode($apiCode);
        
        // 如果请求响应为成功
        if ($this->isOkCode($apiCode)) {
            $response->apiOk = true;
            
            // 如果是上传文件，需要获取路径
            if (isset($this->filePath)) {
                $response->apiFilePath = $this->filePath;
            }
        } else {
            $response->apiOk = false;
            
            // 如果API请求无响应或请求丢失时，如果为生产环境，则发送邮件报警
            if ($is_gone && YAF_ENVIRON == '.production') {
                $this->sendWarningEmail('乒乒网后端服务报警，API请求无响应或请求丢失: ' . $this->api . '，状态：' . $apiCode . '，请尽快查看日志详情，及时恢复服务。');
            }
        }
        
        // 时间拼接毫秒数
        $startWm = explode(".", $this->startTime);
        $endWm = explode(".", $this->stopTime);
        $runData['serviceStart'] = date('Y-m-d H:i:s', $startWm[0]) . '.' . $startWm[1];
        $runData['serviceEnd'] = date('Y-m-d H:i:s', $endWm[0]) . '.' . $endWm[1];
        $runData['elapsed'] = intval(bcmul(bcsub($this->stopTime, $this->startTime, 4), 1000));
        if ($this->api != 'uploadKpayFileBinary' && $this->api != 'che001UploadRaw') {
            $runData['arguments'][] = json_decode($this->call->serialized_payload, true);
        } else {
            $runData['arguments'][] = array(
                'file' => 'Binary file stream'
            );
        }
        $runData['status'] = $is_gone ? '500' : '200';
        $runData['requestUri'] = $this->call->uri;
        $runData['requestMethod'] = $this->call->method;
        if (isset($response->raw_body)) {
            $runData['result'] = ($this->api != 'userFileBinary') ? substr($response->raw_body, 0, 1024) : 'Binary file stream';
        } else {
            $runData['result'] = 'No response';
        }
        $is_gone && $runData['result'] = "API请求无响应或请求丢失({$apiCode})";
        
        // 记录接口调用日志
        $this->addRunLog('api calls logging', $runData);
        
        return $response;
    }

    /**
     * 设置内容类型头信息
     *
     * @return object
     */
    private function contentHeader()
    {
        $contentType = config('apilist.' . $this->api . '.contentType');
        
        if (! empty($contentType)) {
            $this->call->addHeader('Content-Type', $contentType);
        }
        
        return $this;
    }

    /**
     * 设置请求头中requestId信息
     *
     * @return object
     */
    private function requestHeader()
    {
        $this->call->addHeader('requestId', getRequestId());
        
        return $this;
    }

    /**
     * 解析并添加需要发送的其他特殊头信息
     *
     * @param array $headers
     *            - 需要添加的头信息
     * @return object
     */
    public function headers($headers = array())
    {
        foreach ($headers as $key => $val) {
            $this->call->addHeader($key, $val);
        }
        
        return $this;
    }

    /**
     * 垫付宝接口使用，设置etag头信息（版本信息）
     *
     * @param string $eTag
     *            - 版本号
     * @return object
     */
    public function eTag($eTag)
    {
        $this->call->addHeader('If-Match', $eTag);
        
        return $this;
    }

    /**
     * 垫付宝接口使用，上传文件时处理图片地址
     *
     * @param string $metaId
     *            - 文件id
     * @return object
     */
    public function grabPath($metaId, $public = false)
    {
        $url = $this->getUrl() . '/';
        
        if ($public) {
            $url .= 'public/';
        }
        
        $this->filePath = $url . $metaId;
        
        return $this;
    }

    /**
     * sso登录接口
     *
     * @param array $data
     *            - 登录数据
     * @return object
     */
    public function ssoLogin($data)
    {
        $this->method = 'get';
        
        $this->api = 'ssoLogin';
        
        $url = $this->getUrl();
        
        $this->call = \Httpful\Request::get($url)->addHeader('Authorization', 'Basic ' . base64_encode($data['userName'] . ':' . $data['passWord']));
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }

    /**
     * 创建一个get请求
     *
     * @param string $api
     *            - api名
     * @param string $params
     *            - URL参数
     * @param string $query
     *            - 查询语句
     * @return object
     */
    public function get($api, $params = null, $query = null)
    {
        $this->method = 'get';
        
        $this->api = $api;
        
        $url = $this->getUrl();
        
        if (! is_null($params)) {
            $url .= '/' . $params;
        }
        
        if (! is_null($query)) {
            $url .= $query;
        }
        
        $this->call = \Httpful\Request::get($url);
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }

    /**
     * 创建一个put请求
     *
     * @param string $api
     *            - api名
     * @param string $params
     *            - URL参数
     * @param array $data
     *            - 数据包
     * @param string $query
     *            - 查询语句
     * @return object
     */
    public function put($api, $params = null, $data = array(), $query = null)
    {
        $this->method = 'put';
        
        $this->api = $api;
        
        $url = $this->getUrl();
        
        if (! is_null($params)) {
            $url .= '/' . $params;
        }
        
        if (! is_null($query)) {
            $url .= $query;
        }
        
        $this->call = \Httpful\Request::put($url)->body(json_encode($data));
        
        // 设置内容类型头信息
        $this->contentHeader();
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }

    /**
     * 创建一个patch请求
     *
     * @param string $api
     *            - api名
     * @param string $params
     *            - URL参数
     * @param array $data
     *            - 数据包
     * @param string $query
     *            - 查询语句
     * @return object
     */
    public function patch($api, $params = null, $data = array(), $query = null)
    {
        $this->method = 'patch';
        
        $this->api = $api;
        
        $url = $this->getUrl();
        
        if (! is_null($params)) {
            $url .= '/' . $params;
        }
        
        if (! is_null($query)) {
            $url .= $query;
        }
        
        $this->call = \Httpful\Request::post($url)->addHeader('X-Method', 'PATCH')->body(json_encode($data));
        
        // 设置内容类型头信息
        $this->contentHeader();
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }

    /**
     * 创建一个post请求
     *
     * @param string $api
     *            - api名
     * @param string $params
     *            - URL参数
     * @param array $data
     *            - 数据包
     * @param string $query
     *            - 查询语句
     * @return object
     */
    public function post($api, $params = null, $data = array(), $query = null)
    {
        $this->method = 'post';
        
        $this->api = $api;
        
        $url = $this->getUrl();
        
        if (! is_null($params)) {
            $url .= '/' . $params;
        }
        
        if (! is_null($query)) {
            $url .= $query;
        }
        
        $this->call = \Httpful\Request::post($url)->body(json_encode($data));
        
        // 设置内容类型头信息
        $this->contentHeader();
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }

    /**
     * 创建一个delete请求
     *
     * @param string $api
     *            - api名
     * @param string $params
     *            - URL参数
     * @param string $query
     *            - 查询语句
     * @return object
     */
    public function delete($api, $params = null, $query = null)
    {
        $this->method = 'delete';
        
        $this->api = $api;
        
        $url = $this->getUrl();
        
        if (! is_null($params)) {
            $url .= '/' . $params;
        }
        
        if (! is_null($query)) {
            $url .= $query;
        }
        
        $this->call = \Httpful\Request::delete($url);
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }

    /**
     * 文件上传调用方法
     *
     * @param string $api
     *            - api名
     * @param string $params
     *            - 文件ID
     * @param file $data
     *            - 文件二进制流
     * @param string $query
     *            - 地址参数
     * @return object
     */
    public function upload($api, $params = null, $data = null, $query = null)
    {
        $this->method = 'post';
        
        $this->api = $api;
        
        $url = $this->getUrl();
        
        if (! is_null($params)) {
            $url .= '/' . $params;
        }
        
        if (! is_null($query)) {
            $url .= $query;
        }
        
        $this->call = \Httpful\Request::post($url)->body($data);
        
        // 设置内容类型头信息
        $this->contentHeader();
        
        // 设置请求头信息
        $this->requestHeader();
        
        return $this;
    }
}
