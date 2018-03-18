<?php

class Core_Db
{

    protected static $_instance = [];

    protected $db = '';

    public function medoo()
    {
        $db = $this->db;
        if (! isset(self::$_instance[$db])) {
            $dbconfig = Yaf_Registry::get('config')->databases;
            
            self::$_instance[$db] = new Core_Db_Medoo([
                'database_type' => $dbconfig->driver,
                'database_name' => $this->db,
                'server' => $dbconfig->host,
                'username' => $dbconfig->username,
                'password' => $dbconfig->password,
                'port' => $dbconfig->port,
                'charset' => $dbconfig->charset,
                'option' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            ]);
            
            self::$_instance[$db]->exec("SET AUTOCOMMIT=1");
        }
        
        return self::$_instance[$db];
    }

    public function __call($name, $arguments)
    {
        $data = call_user_func_array([
            $this->medoo(),
            $name
        ], $arguments);
        
        return $data;
    }

    private static function getInstance()
    {
        return new static();
    }

    public static function __callStatic($name, $arguments)
    {
        return self::getInstance()->__call($name, $arguments);
    }

    /**
     *
     * @param Closure $callback            
     * @param null $db            
     * @param
     *            $opt
     * @return null|object pdo对象引用
     */
    public static function transaction(Closure $callback, $db = null, &$pdo = null)
    {
        if (is_null($db) || is_null($pdo)) {
            return false;
        }
        
        $instance = self::getInstance();
        $instance->_setDb($db);
        $pdo = $oriPdo = $instance->medoo()->pdo;
        $oriPdo->beginTransaction();
        
        try {
            $res = $callback();
            
            if ($res === false) {
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            TZ_Log::error($e);
            return false;
        }
    }

    /**
     * 启动事务
     */
    public static function startTrans($db = null)
    {
        if (is_null($db)) {
            return false;
        }
        
        $instance = self::getInstance();
        $instance->_setDb($db);
        $pdo = $instance->medoo()->pdo;
        $pdo->beginTransaction();
        
        return $pdo;
    }

    public function _setDb($db)
    {
        $this->db = $db;
    }
}
