<?php


class ErrorController extends Yaf_Controller_Abstract
{
    // 异常捕获
    public function errorAction(Exception $exception)
    {
        var_dump($exception);
        exit();
    }
}
