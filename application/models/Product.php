<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/17
 * Time: 10:14
 */
class ProductModel extends Core_Db
{
    protected $db = 'biyesheji';

    public static $table = 'products';

    public static function updateData($data, $where)
    {
        self::update(self::$table, $data, $where);
    }

    public static function countData($where)
    {
        return self::count(self::$table, $where);
    }

    public static function getList($fields = '', array $where)
    {
        return self::select(self::$table, $fields, $where);
    }
    public static  function deleteData($where){
        return self::delete(self::$table,$where);
    }
}