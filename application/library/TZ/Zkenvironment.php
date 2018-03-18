<?php

class TZ_Zkenvironment
{

    private $zookeeper = null;

    public function __construct($addr)
    {
        if (is_null($this->zookeeper)) {
            try {
                $this->zookeeper = new \Zookeeper($addr);
            } catch (Exception $e) {
                return array();
            }
        }
    }

    /**
     * 获取ZK中配置
     */
    public function getConfig($path)
    {
        $config = [];
        if ($this->zookeeper->exists($path)) {
            $nodeChildren = $this->zookeeper->getChildren($path);
            if (! empty($nodeChildren)) {
                foreach ($nodeChildren as $value) {
                    $thisConf = $this->zookeeper->get($path . "/" . $value);
                    $config[$value] = is_null(json_decode($thisConf)) ? $thisConf : json_decode($thisConf);
                }
            }
        }
        
        return $config;
    }

    /**
     * 设置ZK中配置
     */
    public function setConfig($path, $value)
    {
        if (! $this->zookeeper->exists($path)) {
            $this->makePath($path);
            $this->makeNode($path, $value);
        } else {
            $this->zookeeper->set($path, $value);
        }
    }

    public function makePath($path, $value = '')
    {
        $parts = explode('/', $path);
        $parts = array_filter($parts);
        $subpath = '';
        while (count($parts) > 1) {
            $subpath .= '/' . array_shift($parts);
            if (! $this->zookeeper->exists($subpath)) {
                $this->makeNode($subpath, $value);
            }
        }
    }

    public function makeNode($path, $value, array $params = array())
    {
        if (empty($params)) {
            $params = array(
                array(
                    'perms' => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id' => 'anyone'
                )
            );
        }
        return $this->zookeeper->create($path, $value, $params);
    }

    public function get($path)
    {
        if (! $this->zookeeper->exists($path)) {
            return null;
        }
        return $this->zookeeper->get($path);
    }

    public function getChildren($path)
    {
        if (strlen($path) > 1 && preg_match('@/$@', $path)) {
            // remove trailing /
            $path = substr($path, 0, - 1);
        }
        return $this->zookeeper->getChildren($path);
    }

    public function deleteNode($path)
    {
        if (! $this->zookeeper->exists($path)) {
            return null;
        } else {
            return $this->zookeeper->delete($path);
        }
    }
}
