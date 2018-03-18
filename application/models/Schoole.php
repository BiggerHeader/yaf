<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/18
 * Time: 15:25
 */
class SchooleModel extends Core_Db
{
    protected $db = 'biyesheji';

    public static $table = 'university';


    public static function getList($fields = '*', array $where)
    {
        return self::select(self::$table, $fields, $where);
    }

}