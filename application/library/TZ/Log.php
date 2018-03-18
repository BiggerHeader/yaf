<?php

use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \PhpAmqpLib\Message\AMQPMessage;

class TZ_Log
{
    // 日志信息
    protected static $log = array();
    
    // 接口响应状态
    protected static $logStatus = 200;

    /**
     * MQ相关配置
     */
    private static $conn = null;

    private static $channel = null;

    /**
     * 初始化时，自动执行该方法，进行日志记录
     *
     * @param unknown $name            
     * @param unknown $arguments            
     */
    public static function __callStatic($name, $arguments)
    {
        $name = strtolower($name);
        
        // 获取访问请求信息或异常信息
        $msg = isset($arguments[0]) ? $arguments[0] : '';
        is_object($msg) && $msg = (string) $msg;
        $msg = str_replace("\n", ',', $msg);
        
        // 判断记录日志类型
        $level = strtolower($name);
        $logType = ('info' === $level) ? 'access' : 'error';
        
        // 执行日志记录
        //self::writeLog($msg, $level, $logType);
    }

    /**
     * 记录运行日志
     *
     * @param string $message
     *            日志内容
     * @param array $param
     *            自定义日志参数+内容
     */
    public static function writeRunLog($message, $param = array())
    {
        // 执行日志记录
        self::writeLog($message, 'info', 'run', $param);
    }

    /**
     * 日志写入接口
     * @access public
     * @param string $logMsg
     *            日志信息
     * @param string $logLevel
     *            日志级别
     * @param string $logType
     *            日志类型
     * @param array $param
     *            自定义日志参数+内容
     * @return void
     */
    static private function writeLog($logMsg, $logLevel, $logType, $param = array())
    {
        // 将日志保存在内存，请求结束后统一记录
        self::$log[] = self::createLogString($logMsg, $logLevel, $logType, $param);
    }

    /**
     * 推送日志内容到队列
     */
    static private function directProducer($data)
    {
        self::connect();
        
        $exchange_name = Yaf_Registry::get('config')->rabbitmq->logExchangeName;
        
        try {
            // 声明交换器
            self::$channel->exchange_declare($exchange_name, 'direct', false, true, false);
            
            // 创建消息
            $msg = new AMQPMessage(json_encode($data), array(
                'content_type' => 'application/json',
                'delivery_mode' => 2
            ));
            
            // 发布消息
            self::$channel->basic_publish($msg, $exchange_name, 'pingpw_log');
        } catch (\Exception $e) {
            // 重连再试一次，不行就忽略错误
            try {
                self::stop();
                self::connect();
                
                // 声明交换器
                self::$channel->exchange_declare($exchange_name, 'direct', false, true, false);
                
                // 创建消息
                $msg = new AMQPMessage(json_encode($data), array(
                    'content_type' => 'application/json',
                    'delivery_mode' => 2
                ));
                
                // 发布消息
                self::$channel->basic_publish($msg, $exchange_name, 'pingpw_log');
            } catch (\Exception $e) {
                return;
            }
        }
    }

    /**
     * 连接mq
     */
    static private function connect()
    {
        if (is_null(self::$conn) || is_null(self::$channel)) {
            try {
                // 连接到RabbitMQ
                $rabbitmq = Yaf_Registry::get('config')->rabbitmq;
                self::$conn = new AMQPStreamConnection($rabbitmq->host, $rabbitmq->port, $rabbitmq->login, $rabbitmq->password, $rabbitmq->vhost);
                
                // 获取信道
                self::$channel = self::$conn->channel();
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * 终止连接mq
     */
    static private function stop()
    {
        try {
            if (! is_null(self::$channel)) {
                // 关闭信道
                self::$channel->close();
                self::$channel = null;
            }
            
            if (! is_null(self::$conn)) {
                // 关闭连接
                self::$conn->close();
                self::$conn = null;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 拼接日志内容
     */
    static private function createLogString($log, $logLevel, $logType, $param = array())
    {
        // 日志记录时间
        $nowTime = microtime(true);
        $nowWm = explode(".", $nowTime);
        $now = date('Y-m-d H:i:s', $nowWm[0]) . '.' . $nowWm[1];
        
        $logMsg['nowTime'] = $now; // 记录日志时间
        $logMsg['business'] = 'newApi' . Yaf_Registry::get('config')->log->curentModule . MODULE_CONTROLLER_ACTION; // 业务名称
        $logMsg['businessAlias'] = self::_getApiMessage(MODULE_CONTROLLER_ACTION); // 业务描述
        $logMsg['hostName'] = php_uname('n'); // 服务器名称
        $logMsg['hostAddress'] = $_SERVER['SERVER_ADDR']; // 服务器ip地址
        $logMsg['traceId'] = getRequestId(); // 链条id，请求id（例：一次请求十条日志，十条日志的traceid相同）
        $logMsg['spanId'] = self::createRequestId(); // 日志id（例：一次请求十条日志，十条日志的spanId不同）
        $logMsg['clientIp'] = get_client_ip(0, true); // 客户端ip
        $logMsg['methodName'] = MODULE_CONTROLLER_ACTION; // 方法名称
        $logMsg['logLevel'] = $logLevel; // 日志级别
        $logMsg['logType'] = $logType; // 日志类型
        ($logType == 'access') && $logMsg = self::getRequestData($logMsg); // 请求数据
                                                                           
        // 自定义参数
        if (! empty($param)) {
            foreach ($param as $key => $value) {
                $logMsg[$key] = $value;
            }
        }
        
        if ($logType == 'error') {
            self::$logStatus = 400;
            $logMsg['throwable'] = $log; // 异常堆栈信息
        } else {
            $logMsg['desc'] = $log; // 自定义日志内容
        }
        
        return $logMsg;
    }

    /**
     * 日志保存
     */
    public static function save()
    {
        if (empty(self::$log)) {
            return;
        }
        
        // 定义初始父id
        $parent = 'ROOT';
        
        // 时间拼接毫秒数
        $startTime = BEGIN_TIME;
        $startWm = explode(".", $startTime);
        $serviceStart = date('Y-m-d H:i:s', $startWm[0]) . '.' . $startWm[1];
        
        $endTime = microtime(true);
        $endWm = explode(".", $endTime);
        $serviceEnd = date('Y-m-d H:i:s', $endWm[0]) . '.' . $endWm[1];
        
        foreach (self::$log as $key => $value) {
            // 请求开始时间、结束时间
            ! isset($value['serviceStart']) && $value['serviceStart'] = $serviceStart;
            ! isset($value['serviceEnd']) && $value['serviceEnd'] = $serviceEnd;
            
            // 第一条为访问日志
            if ($key == 0) {
                // 计算总耗时
                $value['elapsed'] = intval(bcmul(bcsub($endTime, $startTime, 4), 1000));
                
                // 接口状态、出参
                $value['status'] = self::$logStatus;
                $value['result'] = '';
            }
            
            $value['level'] = $key; // 记录日志级别
            $value['parentId'] = $parent; // 记录父级日志id
                                          
            // 处理日志内容
            $logTime = $value['nowTime'];
            unset($value['nowTime']);
            $thisLog = $logTime . " - ###" . json_encode($value) . '###' . PHP_EOL; // 新起一行
            
            try {
                $mqData = array(
                    'log' => $thisLog,
                    'level' => $value['logType'],
                    'dirname' => 'pingpw_logs',
                    'module' => Yaf_Registry::get('config')->log->curentModule . 'Service'
                );
                self::directProducer($mqData);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
            
            // 定义下条日志的父级id
            $parent = $value['spanId'];
        }
        
        // 保存后清空日志缓存
        self::$log = array();
        self::$logStatus = 200;
    }

    /**
     * 获取请求数据
     */
    static private function getRequestData($logMsg)
    {
        $logMsg['requestHost'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; // 记录请求域名
        $logMsg['requestMethod'] = $_SERVER['REQUEST_METHOD']; // 记录请求类型
        isset($_SERVER['CONTENT_TYPE']) && $logMsg['contentType'] = $_SERVER['CONTENT_TYPE']; // 记录请求头信息
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = $_REQUEST;
        } else {
            if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                $data = $_REQUEST;
            } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/octet-stream') !== false) {
                $data = 'Binary file stream';
            } else {
                $data = json_decode(file_get_contents("php://input"), true);
            }
        }
        ! empty($data) && $logMsg['arguments'][] = $data;
        
        return $logMsg;
    }

    /**
     * 生成uuid
     */
    static private function createRequestId()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') { // windows
            $uuid = com_create_guid();
            return strtolower(ltrim(rtrim($uuid, '}'), '{')); // 清除两侧中括号，转为小写
        } else { // linux
            $uuid = uuid_create();
            return strtolower($uuid);
        }
    }

    /**
     * 获取接口别名
     */
    static private function _getApiMessage($code)
    {
        $config = new Yaf_Config_Ini(APP_PATH . '/config/apiname.ini');
        return isset($config[$code]) ? $config[$code] : $code;
    }
}
