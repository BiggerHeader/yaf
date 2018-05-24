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
            $where=[];
            if (!empty($product_id)) {
                $where = [
                    'product_id' => $product_id,
                ];
            }

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
             //var_dump($data);exit();
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

    /**
     *意见反馈
     */
    public function feedbackAction()
    {
        if ($this->getRequest()->isPost()) {
            // 获取请求体数据
            $body = getRequestBody();
            // 参数必传校验
            if (empty($body['content'])) {
                TZ_Response::error(10005, '内容不能为空');
            }
            $data = [];

            if (!empty($body['name'])) {
                $data['name'] = $body['name'];
            } else {
                TZ_Response::error(10004, '名字不能为空');
            }

            if (!empty($body['email']) && filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                $data['email'] = $body['email'];
            } else {
                TZ_Response::error(10003, '邮箱格式不正确或邮箱不能为空');
            }
            $data['content'] = $body['content'];
            $data['create_time'] = $data['update_time'] = date('Y-m-d H:i:s');

            TZ_Loader::service('Comment', 'Comment')->insert_feedback($data);
            TZ_Response::success('提交成功！');
        }
        TZ_Response::error(10001, '请求类型错误');
    }

    /**
     *后台查看数据
     */
    public function getfeedbackAction()
    {
        if ($this->getRequest()->isPost()) {
            TZ_Loader::service('Comment', 'Comment')->get_feedback();

        }
        TZ_Response::error(10001, '请求类型错误');
    }
}