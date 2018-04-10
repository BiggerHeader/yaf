<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/18
 * Time: 15:22
 */
class SchooleController extends Yaf_Controller_Abstract
{
    public function getAction()
    {
        if ($this->getRequest()->isGet()) {
            $name = $this->getRequest()->getQuery('name');
            if (empty($name)) {
                TZ_Response::error(10001, '参数有误');
            }
            $get_data = SchooleModel::getList('*', ['name[~]' => $name]);

            exit(json_encode([
                'code' => 10000,
                'msg' => '查询成功',
                'data' => $get_data
            ]));
        }
        TZ_Response::error(10001, '请求类型错误');
    }
}