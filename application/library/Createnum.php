<?php

/**
 * 生成编号类
 */
class Createnum
{

    /**
     * 生成唯一ID
     */
    public function createId()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') { // windows
            $uuid = com_create_guid();
            return strtolower(ltrim(rtrim($uuid, '}'), '{')); // 清除两侧中括号，转为小写
        } else { // linux
            $uuid = uuid_create();
            return strtolower($uuid);
        }
    }

    /*
     * 生成唯一数字编号
     *
     * 1. 用uniqid获取一个基于当前的微秒数生成的唯一不重复的字符串，截取后6位
     * 2. 用str_split把这个字符串分割为数组
     * 3. 用ord作为自定义函数，获取数组中每个值的ASCII编码，返回新数组
     * 4. 把数组元素拼接为字符串，截取后8位
     * 5. 连接4位随机数
     */
    public function createNumber()
    {
        return substr(implode(null, array_map('ord', str_split(substr(strtoupper(uniqid()), 7, 6), 1))), 4, 8) . str_pad(mt_rand(1, 10000), 4, '0', STR_PAD_LEFT);
    }
}