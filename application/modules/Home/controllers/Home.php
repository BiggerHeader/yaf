<?php
/**
 * Created by PhpStorm.
 * User: 140439
 * Date: 2018/4/2
 * Time: 14:44
 */

class HomeController extends Yaf_Controller_Abstract
{

    public function getCategoryAction(){
        //修改这些从Redis 里面取
        $redis = new  Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->redis->auth('');
        $name = 'category';
        //判断缓存的键是否还存在
        if(!$this->redis->exists("cache:".$name))
        {
            //缓存不存在
            //下面的get_mysql_data（）函数只是个例子，按照自己具体情况去mysql获取数据

            $json = json_encode($data,JSON_UNESCAPED_UNICODE);
            //存入redis
            $this->redis->set("cache:".$name,$json);
            //设置过期时间5分钟
            $this->redis->expire("cache:".$name,60*60*24);
        }

        $json = $this->redis->get("cache:".$commentid);
    }
}