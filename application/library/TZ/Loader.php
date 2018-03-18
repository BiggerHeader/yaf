<?php


class TZ_Loader
{

    /**
     * module name
     *
     * @var string
     */
    static $_moduleName = null;

    /**
     * load model object
     *
     * @param string $modelName            
     * @param string $moduleName            
     * @return object
     */
    static public function model($modelName, $moduleName = null)
    {
        $modelObject = self::_loadModuleFile('models', $modelName, $moduleName);
        return $modelObject;
    }

    /**
     * load service object
     *
     * @param string $serviceName            
     * @param string $moduleName            
     * @return object
     */
    static public function service($serviceName, $moduleName = null)
    {
        $serviceObject = self::_loadModuleFile('services', $serviceName, $moduleName);
        return $serviceObject;
    }

    /**
     * load config node
     *
     * @param string $node            
     * @return array
     */
    static public function config($node = null)
    {
        $config = Yaf_Registry::get('config');
        if ((null !== $node) && isset($config->$node)) {
            return $config->$node;
        }
        return false;
    }

    /**
     * load module file
     *
     * @param string $directory            
     * @param string $modelName            
     * @param string $moduleName            
     * @return object
     */
    static private function _loadModuleFile($directory, $fileName, $moduleName = null)
    {
        if (null === self::$_moduleName)
            self::$_moduleName = Yaf_Dispatcher::getInstance()->getRequest()->getModuleName();
        
        if (null !== $moduleName)
            self::$_moduleName = $moduleName;
        if ($directory == 'services')
            $file = APP_PATH . '/application/modules/' . self::$_moduleName . '/' . $directory . '/' . $fileName . '.php';
        else
            $file = APP_PATH . '/application/models/' . self::$_moduleName . '/' . $fileName . '.php';
        
        $regObject = Yaf_Registry::get($file);
        if ((false !== $regObject) && (null !== $regObject))
            return $regObject;
        
        $loadResult = Yaf_Loader::import($file);
        $fileName .= ($directory == 'models') ? 'Model' : 'Service';
        if ($loadResult) {
            $fileObject = new $fileName();
            Yaf_Registry::set($file, $fileObject);
            return $fileObject;
        } else {
            throw new Exception($fileName . ' not found.');
        }
    }
}
