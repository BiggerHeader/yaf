<?php
use \PhpAmqpLib\Connection\AMQPStreamConnection;
use \PhpAmqpLib\Message\AMQPMessage;


class Mqproducer
{

    /**
     * MQ相关配置
     */
    private $conn = null;

    private $channel = null;

    public function __construct()
    {
        $this->connect();
    }

    public function __destruct()
    {
        $this->stop();
    }

    public function directProducer($data, $exchange_name, $routing_key, $config = null)
    {
        // 如果没有特殊配置，使用默认连接，否则重新连接mq
        if (! is_null($config)) {
            $this->stop();
            $this->connect($config);
        }
        
        try {
            // 声明交换器
            $this->channel->exchange_declare($exchange_name, 'direct', false, true, false);
            
            // 创建消息
            $msg = new AMQPMessage(json_encode($data), array(
                'content_type' => 'application/json',
                'delivery_mode' => 2
            ));
            
            // 发布消息
            $this->channel->basic_publish($msg, $exchange_name, $routing_key);
        } catch (\Exception $e) {
            // 重连再试一次，不行就忽略错误
            try {
                $this->stop();
                $this->connect($config);
                
                // 声明交换器
                $this->channel->exchange_declare($exchange_name, 'direct', false, true, false);
                
                // 创建消息
                $msg = new AMQPMessage(json_encode($data), array(
                    'content_type' => 'application/json',
                    'delivery_mode' => 2
                ));
                
                // 发布消息
                $this->channel->basic_publish($msg, $exchange_name, $routing_key);
            } catch (\Exception $e) {
                return;
            }
        }
    }

    /**
     * 连接mq
     */
    private function connect($config = null)
    {
        if (is_null($this->conn) || is_null($this->channel)) {
            // 如果$config为空则使用默认rabbitmq服务，可传递其他平台rabbitmq服务配置
            $rabbitmqConfig = TZ_Loader::config('rabbitmq');
            $host = is_null($config) ? $rabbitmqConfig->host : $config->host;
            $port = is_null($config) ? $rabbitmqConfig->port : $config->port;
            $user = is_null($config) ? $rabbitmqConfig->login : $config->login;
            $pass = is_null($config) ? $rabbitmqConfig->password : $config->password;
            $vhost = is_null($config) ? $rabbitmqConfig->vhost : $config->vhost;
            
            // 连接到RabbitMQ
            $this->conn = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
            
            // 获取信道
            $this->channel = $this->conn->channel();
        }
    }

    /**
     * 终止连接mq
     */
    private function stop()
    {
        if (! is_null($this->channel)) {
            // 关闭信道
            $this->channel->close();
            $this->channel = null;
        }
        
        if (! is_null($this->conn)) {
            // 关闭连接
            $this->conn->close();
            $this->conn = null;
        }
    }
}
