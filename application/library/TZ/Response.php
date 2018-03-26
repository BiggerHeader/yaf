<?php


class TZ_Response
{

    /**
     * 请求成功
     *
     * @param string $msg            
     * @param array $data            
     * @param int $code            
     * @return void
     */
    static public function success($msg = null, $data = array(), $code = 10000)
    {
        $response = array(
            'code' => $code,
            'msg' => $msg
        );
        
        if (! empty($data)) {
            $response['data'] = $data;
            //$response=  array_merge($response,$data);
        }
        
        // send
        self::sendJson($response);
    }


    static public function success_list($msg = null, $data = array(), $code = 10000)
    {
        $response = array(
            'code' => $code,
            'msg' => $msg
        );

        if (! empty($data)) {
            $response=  array_merge($response,$data);
        }

        // send
        self::sendJson($response);
    }



    /**
     * 请求错误
     *
     * @param int $code            
     * @param string $msg            
     * @return void
     */
    static public function error($code = 99999, $errorMessage = null)
    {
        $errorMessage = (is_null($errorMessage)) ? self::_getErrorMessage($code) : $errorMessage;
        $response = array(
            'code' => $code,
            'msg' => $errorMessage
        );
        
        // send
        self::sendJson($response);
    }

    /**
     * 发送JSON数据
     *
     * @param array $response            
     * @return void
     */
    static public function sendJson($response)
    {
        header("Content-type:application/json;charset=utf-8");
        exit(json_encode($response));
    }

    /**
     * 获取错误信息
     *
     * @param int $code            
     * @return string
     */
    static private function _getErrorMessage($code)
    {
        if (is_null(self::$_error)) {
            $config = new Yaf_Config_Ini(APP_PATH . '/config/error.ini');
            self::$_error = $config->error->toArray();
        }
        
        if (! isset(self::$_error[$code])) {
            throw new Exception('不存在的错误码');
        }
        
        return self::$_error[$code];
    }
}
