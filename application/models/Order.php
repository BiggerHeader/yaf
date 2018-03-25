<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/24
 * Time: 18:54
 */
class OrderModel extends Core_Db
{
    protected $db = 'biyesheji';

    public static $table = 'orders';

    public static function getList($join, $fields = '', array $where)
    {
        return self::select(self::$table, $join, $fields, $where);
    }

    public static function countData($where)
    {
        return self::count(self::$table, $where);
    }

    public static function updateData($data, $where)
    {
        self::update(self::$table, $data, $where);
    }

}