<?php

/**
 * 获取请求body数据
 * @param boolean $getArray
 *            是否解析json获取数组
 * @return json,array,file
 */
function getRequestBody($getArray = true)
{
    // 获取请求body，json串或文件流
    $body = file_get_contents("php://input");
    
    // 是否需要解析json获取数组
    if ($getArray) {
        $body = json_decode($body, true);
    }
    
    return $body;
}

/**
 * 获取请求header数据
 */
function getRequestHeader($key = null)
{
    $headers = getallheaders();
    
    if (! is_null($key)) {
        return $headers[$key];
    }
    
    return $headers;
}

/**
 * 获取头http客户端请求信息 nginx下使用 apache自身支持
 */
if (! function_exists('getallheaders')) {

    function getallheaders()
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type
 *            返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv
 *            是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = null;
    if ($ip !== null) {
        return $ip[$type];
    }
    
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array(
        $ip,
        $long
    ) : array(
        '0.0.0.0',
        0
    );
    return $ip[$type];
}

/**
 * 替换配置文件
 */
function env($name, $default = null)
{
    $arrName = explode('.', $name);
    $config = Yaf_Registry::get('config');
    $rtn = null;
    foreach ($arrName as $val) {
        if (is_object($config) && isset($config->$val)) {
            $config = $config->$val;
        } else {
            $config = $default;
            break;
        }
    }
    if (! is_object($config)) {
        return $config;
    }
    return $default;
}


/**
 * 计算分页数据
 */
function getPageLimit($data)
{
    $page = (isset($data['page']) && $data['page'] > 0) ? $data['page'] : 1;
    $pageSize = (isset($data['pageSize']) && $data['pageSize'] > 0) ? $data['pageSize'] : 20;
    $pageStart = ($page - 1) * $pageSize;
    return array(
        $pageStart,
        $pageSize
    );
}

/**
 * 获取富文本内容
 * @author wen
 * @param string $contentId
 *            富文本ID
 * @param bool $imgHref
 *            图片地址格式化
 */
function getContent($contentId, $imgHref = false)
{
    if ($contentId) {
        $fileViewUrl = Yaf_Registry::get('config')->che001File->view;
        $filePath = $fileViewUrl . $contentId;
        $description = file_get_contents($filePath);
        $description = html_entity_decode(stripslashes($description), ENT_QUOTES, "UTF-8");
        if ($imgHref) {
            $description = str_replace('/File/view/id/', $fileViewUrl, $description);
        }
        return $description;
    }
    return '';
}




/**
 * 过滤html标签
 * @author wen
 * @param string $content
 *            需要过滤的内容
 * @return string
 */
function filterHtml($content)
{
    // 转化实体标签
    $content = html_entity_decode($content);
    // 去除html标签
    $content = strip_tags($content);
    // 去除空格
    $content = str_replace('&nbsp;', '', $content);
    $content = str_replace(' ', '', $content);
    return $content;
}




