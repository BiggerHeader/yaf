<?php


class ErrorController extends Yaf_Controller_Abstract
{
    // 异常捕获
    public function errorAction(Exception $exception)
<<<<<<< HEAD
    {        
        var_dump($exception);exit();
=======
    {
        var_dump($exception);
        exit();
>>>>>>> a8bb7f7e24249e5cc49069a6f8081b30f8f36e04
    }
}
