<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/16
 * Time: 20:07
 */
class ProductController extends Yaf_Controller_Abstract
{
    /**
     *添加评论
     */
    /* public function addAction()
     {
         if ($this->getRequest()->isPost()) {
             // 获取请求体数据
             $body = getRequestBody();
             // 参数必传校验
             TZ_Validator::checkField($body, 'content,user_id,product_id', '&&', false);
             $body['create_time'] = date('Y-m-d H:i:s');
             $code = TZ_Loader::service('Comment', 'Comment')->insert($body);
             TZ_Response::success('评论成功！');
         }
         TZ_Response::error(10001, '请求类型错误');
     }*/

    public function getAction()
    {
        if ($this->getRequest()->isGet()) {
            //$product_id = $this->getRequest()->getQuery('product_id');
            $page = $this->getRequest()->getQuery('page');
            $pagesize = $this->getRequest()->getQuery('pageSize');
            $limit = getPageLimit(['page' => $page, 'pageSize' => $pagesize]);

            $count = TZ_Loader::service('Product', 'Product')->count();
            $where['LIMIT'] = $limit;
            $data = TZ_Loader::service('Product', 'Product')->select('*', $where);

            header("Content-type:application/json;charset=utf-8");
            exit(json_encode([
                'code' => 10000,
                'msg' => '查询成功',
                'data' => $data,
                'count' => $count,
            ]));
        }

        TZ_Response::error(10001, '请求类型错误');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $body = getRequestBody();
            if (empty($body['id'])) {
                TZ_Response::error(10001, '参数有错误');
            }
            $where['id'] = $body['id'];
            TZ_Loader::service('Product', 'Product')->delete($where);
            TZ_Response::success('删除成功');
        }
        TZ_Response::error(10001, '请求类型错误');
    }
}