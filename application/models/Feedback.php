<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/16
 * Time: 20:22
 */
class FeedbackModel extends Core_Db
{
    protected $db = 'biyesheji';

    public static $table = 'feedback';

    public static function addOne(array $data)
    {
        return self::insert(self::$table, $data);
    }

    public static function getList($fields = '', array $where = [])
    {
        return self::select(self::$table, $fields, $where);
    }

    public static function countData(array $where = [])
    {
        return self::count(self::$table, $where);
    }

    public static function updateData($data, $where)
    {
        self::update(self::$table, $data, $where);
    }
}