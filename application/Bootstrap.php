<?php


class Bootstrap extends Yaf_Bootstrap_Abstract
{

    /**
     * data
     */
    private $_config = null;

    /**
     * config init
     */
    public function _initConfig()
    {
        $this->_config = Yaf_Application::app()->getConfig()->toArray();
        
        // 获取zookeeper下配置
      /*  if (isset($this->_config['zookeeper']) && YAF_ENVIRON !== '.localhost') {
            $zk = new TZ_Zkenvironment($this->_config['zookeeper']['host']);
            $zkConf = $zk->getConfig('/pingpw/yaf');
            
            // 合并配置项
            $this->_config = array_merge($this->_config, $zkConf);
        }*/
        
        // 将每一级数组都转换为对象格式
        $this->_config = json_decode(json_encode($this->_config));
        
        Yaf_Registry::set('config', $this->_config);
    }

    /**
     * loader config
     */
    public function _initLoader()
    {
        $loader = new TZ_Loader();
        Yaf_Registry::set('loader', $loader);
    }

    /**
     * plug config
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        $routerPlugin = new RouterPlugin();
        $dispatcher->registerPlugin($routerPlugin);
    }

    /**
     * view config
     */
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->disableView();
    }

    /**
     * Init library
     *
     * @return void
     */
    public function _initLibrary()
    {
        $loader = Yaf_Loader::getInstance();
        $loader->registerLocalNamespace([
            'Code'
        ]);
    }

    /**
     * Init common
     *
     * @return void
     */
    public function _initCommon()
    {
        // 引用公共方法文件
        Yaf_Loader::import(Yaf_Application::app()->getConfig()->application->directory . '/common/functions.php');
        
        // 获取当前请求
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        
        // 定义当前route
        $uriParams = explode('/', ltrim($request->getServer('REQUEST_URI'), '/'));
        $uri = isset($uriParams[0]) ? '/' . $uriParams[0] : '/';
        $uri .= isset($uriParams[1]) ? '/' . $uriParams[1] : '';
        $uri .= isset($uriParams[2]) ? '/' . $uriParams[2] : '';
        define('MODULE_CONTROLLER_ACTION', $uri);
        
        // 记录访问日志
       // TZ_Log::info("access log record");
    }

    /**
     * Init ErrorHandler
     *
     * @return void
     */
    public function _initErrorHandler()
    {
        // 设定错误和异常处理
        register_shutdown_function('TZ_Errorhalt::fatalError');
        set_error_handler('TZ_Errorhalt::appError', E_ALL ^ E_NOTICE);
        set_exception_handler('TZ_Errorhalt::appException');
    }
}

/**
 * RouterPlugin.php
 */
class RouterPlugin extends Yaf_Plugin_Abstract
{

    /**
     * 这个会在路由之前触发，也就是路由之前会调用这个Hook ，这个是7个事件中, 最早的一个
     * 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
     */
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {}

    /**
     * 这个在路由结束之后触发
     * 需要注意的是，只有路由正确完成之后才会触发这个Hook
     */
    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {}

    /**
     * 分发循环开始之前被触发
     */
    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {}

    /**
     * 分发之前触发
     * 如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次
     */
    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {}

    /**
     * 分发结束之后触发，此时动作已经执行结束, 视图也已经渲染完成
     * 和preDispatch类似, 此事件也可能触发多次
     */
    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {}

    /**
     * 分发循环结束之后触发
     * 此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送
     */
    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {}
}

// tools
function d($params)
{
    echo '<pre>';
    var_dump($params);
    echo '</pre>';
}