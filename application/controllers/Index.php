<?php


class IndexController extends Yaf_Controller_Abstract
{
    
    // 默认访问
    public function indexAction()
    {
        echo 'Hello World 000';
        var_dump(new redis());
    }
}
