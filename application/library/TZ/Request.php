<?php

/**
 * CheKK
 *
 * @author Fermi <wanghaiyu@che001.com>
 * @final 2017-07-18
 */
class TZ_Request
{
    // 验证手机号是否合法
    static public function checkTelephone()
    {
        $params = self::getParams('post');
        if (empty($params['telephone']))
            self::error('手机号码不能为空。');
        if (! preg_match("#^1[3-8][0-9]{9}$#", $params['telephone']))
            self::error('手机号码错误。');
        return $params['telephone'];
    }
    
    // 获取参数
    static public function getParams($method = 'get')
    {
        switch ($method) {
            case 'get':
                return $_GET;
            
            case 'post':
                return ! empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), 1);
            
            default:
                return false;
        }
    }
    
    // 获取客户段ip
    static function getRemoteIp()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'unknown';
        }
        return $ip;
    }
    
    // filter
    static public function clean($str)
    {
        return addslashes(self::_xssClean($str));
    }
    
    // 去掉js和html
    static private function _xssClean($str)
    {
        $str = trim($str);
        if (strlen($str) <= 0)
            return $str;
        return @preg_replace(self::$_search, self::$_replace, $str);
    }

    private static $_search = array(
        "'<script[^>]*?>.*?</script>'si", // 去掉 javascript
        "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
        "'([\r\n])[\s]+'", // 去掉空白字符
        "'&(quot|#34);'i", // 替换 HTML 实体
        "'&(amp|#38);'i",
        "'&(lt|#60);'i",
        "'&(gt|#62);'i",
        "'&(nbsp|#160);'i"
    );

    private static $_replace = array( // 作为 PHP 代码运行
        '',
        '',
        "\\1",
        "\"",
        "&",
        "<",
        ">",
        ''
    );
}
