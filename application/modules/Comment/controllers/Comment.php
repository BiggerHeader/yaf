<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/16
 * Time: 20:07
 */
class CommentController extends Yaf_Controller_Abstract
{
    /**
     *添加评论
     */
    public function addAction()
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
    }

    public function getAction()
    {
        if ($this->getRequest()->isGet()) {
            $product_id = $this->getRequest()->getQuery('product_id');
            $page = $this->getRequest()->getQuery('page');
            $pagesize = $this->getRequest()->getQuery('pageSize');
            $limit = getPageLimit(['page' => $page, 'pageSize' => $pagesize]);
            if (empty($product_id)) {
                TZ_Response::error(10001, '参数有误！');
            }
            $where = [
                'product_id' => $product_id,
            ];
            $joins = ["[>]users" => ['user_id' => 'id']];
            $joins['[>]products'] = ['product_id' => 'id'];

            $count = TZ_Loader::service('Comment', 'Comment')->count($where);
            $where['LIMIT'] = $limit;

            $data = TZ_Loader::service('Comment', 'Comment')->select($joins, [
                'comment.content',
                'comment.create_time',
                //'users.name',
                'products.name',
                'products.uuid',
                'products.id',
            ], $where);

            header("Content-type:application/json;charset=utf-8");
            exit(json_encode([
                'code' => 10000,
                'msg' => '查询成功',
                'data' => $data,
                'count' => $count,
            ]));
            // TZ_Response::success('查询成功', $result_data);
        }
        TZ_Response::error(10001, '请求类型错误');
    }
}