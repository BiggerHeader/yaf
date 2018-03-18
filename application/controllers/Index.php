<?php


class IndexController extends Yaf_Controller_Abstract···
{
    
    // 默认访问
    public function indexAction()
    {
        $this->getView()->assign("content", "Hello World");
    }

    public function testAction()
    {
        $this->getView()->assign("content", "Hello World  too");
    }

    // 服务自检
    public function healthCheckAction()
    {
        $healthCheck = new HealthCheck();
        exit($healthCheck->check());
    }
}
