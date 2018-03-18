<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/16
 * Time: 20:22
 */
class CommentModel extends Core_Db
{
    protected $db = 'biyesheji';

    public static $table = 'comment';

    public static function addOne(array $data)
    {
        return self::insert(self::$table, $data);
    }

    public static function getList($join, $fields = '', array $where)
    {
        return self::select(self::$table, $join, $fields, $where);
    }

    public static function countData($where)
    {
        return self::count(self::$table, $where);
    }
}