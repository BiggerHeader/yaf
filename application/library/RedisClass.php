<?php


use Predis\Client;

class RedisClass
{

    /**
     * redis object
     *
     * @var object
     */
    public static $_redis = null;

    public function __construct()
    {
        self::connect();
    }

    /**
     * connect to redis server
     *
     * @param string $host            
     * @param int $port            
     * @return object
     */
    static public function connect($host = null, $port = null)
    {
        if (! is_null(self::$_redis)) {
            return self::$_redis;
        }
        
        // 检查redis扩展是否加载
        if (! extension_loaded('redis')) {
            throw new Exception('Redis extension not found.');
        }
        
        // 获取redis配置
        $redisConfig = TZ_Loader::config('redis');
        if (empty($redisConfig)) {
            throw new Exception('Reids Configuration error.');
        }
        
        // 判断是否开启哨兵模式
        if ($redisConfig->sentinel->status) {
            $redis = new Client(explode(',', $redisConfig->sentinel->list), [
                'replication' => 'sentinel',
                'service' => $redisConfig->sentinel->masterName
            ]);
        } else {
            $host = (null === $host) ? $redisConfig->host : $host;
            $port = (null === $port) ? $redisConfig->port : $port;
            $config = [
                'host' => $host,
                'port' => $port,
                'database' => 0
            ];
            $redis = new Client($config);
        }
        
        return self::$_redis = $redis;
    }

    public static function __callStatic($name, $arguments)
    {
        $redis = self::connect();
        
        $res = call_user_func_array([
            $redis,
            $name
        ], $arguments);
        
        if (is_object($res) && method_exists($res, 'getPayload')) {
            return $res->getPayload();
        }
        
        return $res;
    }

    /**
     * close connection
     *
     * @return void
     */
    static public function close()
    {
        self::$_redis->close();
        self::$_redis = null;
    }
}
