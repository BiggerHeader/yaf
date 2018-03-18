<?php
use Predis\Client;


class HealthCheck
{

    /**
     * 公共检查调用方法
     */
    public function check()
    {
        // mysql连接检查
        $mysqlConfig = TZ_Loader::config('databases');
        $mysql = $this->telnet($mysqlConfig->host, $mysqlConfig->port);
        if ($mysql !== true) {
            $this->returnResult(500, 'Mysql : ' . $mysql);
        }
        
        // redis连接检查
        try {
            $redis = new RedisClass();
            $testRedis = $redis::set('ppwRds', 'test');
            $testRedis = $redis::del('ppwRds');
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $testRedis = false;
        }
        if ($testRedis === false) {
            $this->returnResult(500, 'Redis : ' . $msg);
        }
        
        // es连接检查
        $esConfig = TZ_Loader::config('elasticsearch');
        if (! empty($esConfig)) {
            $es = $this->telnet($esConfig->host, $esConfig->port);
            if ($es !== true) {
                $this->returnResult(500, 'ES : ' . $es);
            }
        }
        
        // rabbitmq连接检查
        $rabbitmqConfig = TZ_Loader::config('rabbitmq');
        $rabbitmq = $this->telnet($rabbitmqConfig->host, $rabbitmqConfig->port);
        if (! $rabbitmq) {
            $this->returnResult(500, 'RabbitMQ : ' . $rabbitmq);
        }
        
        // 第三方服务连接检查
        $apiList = TZ_Loader::config('apiUrl');
        foreach ((array) $apiList as $key => $value) {
            if (! $this->connect($value)) {
                $this->returnResult(500, 'Service cannot connect : ' . $key . ' --- ' . $value);
            }
        }
        
        $this->returnResult(200, 'ok');
    }

    /**
     * 底层服务连接检查
     */
    public function telnet($host, $port)
    {
        // 打开数据流
        $fp = @fsockopen($host, $port, $errNo, $errstr, 2);
        
        // 如果打开失败
        if (! $fp) {
            return 'cannot connect';
        }
        
        // 关闭数据流
        fclose($fp);
        
        return true;
    }

    /**
     * curl调用
     */
    public function connect($url)
    {
        $ch = curl_init($url);
        
        // 设置通过函数返回响应体
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        // 使用自动跳转
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        
        // 自动设置Referer
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        
        // 返回时带http头
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        
        // 获取返回结果
        $response = curl_exec($ch);
        
        // 获取最后一次的错误号，0为调用正常
        $curlNo = curl_errno($ch);
        
        curl_close($ch);
        
        if ($curlNo > 0) {
            return false;
        }
        
        return true;
    }

    /**
     * 返回检查结果
     */
    public function returnResult($code, $msg)
    {
        $data = array(
            'code' => $code,
            'msg' => $msg
        );
        header("Content-type:application/json;charset=utf-8");
        exit(json_encode($data));
    }
}
