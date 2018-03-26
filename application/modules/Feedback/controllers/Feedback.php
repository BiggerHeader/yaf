<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/16
 * Time: 20:07
 */
class FeedbackController extends Yaf_Controller_Abstract
{

    /**
     *意见反馈
     */
    public function addAction()
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

            TZ_Loader::service('Feedback', 'Feedback')->insert($data);
            TZ_Response::success('提交成功！');
        }
        TZ_Response::error(10001, '请求类型错误');
    }

    /**
     *后台查看数据
     */
    public function getAction()
    {
        if ($this->getRequest()->isGet()) {
            $page = $this->getRequest()->getQuery('page');
            $pagesize = $this->getRequest()->getQuery('pageSize');
            $limit = getPageLimit(['page' => $page, 'pageSize' => $pagesize]);
            $where = [];
            $data['count'] = TZ_Loader::service('Feedback', 'Feedback')->count($where);
            $where['LIMIT'] = $limit;
            $data['data'] = TZ_Loader::service('Feedback', 'Feedback')->select($where);
            TZ_Response::success_list('提交成功！', $data);
        }
        TZ_Response::error(10001, '请求类型错误');
    }

    public function modifyAction()
    {
        if ($this->getRequest()->isPost()) {
            // 获取请求体数据
            $body = getRequestBody();
            // 参数必传校验
            if (empty($body['status']) || empty($body['id'])) {
                TZ_Response::error(10005, '参数有错');
            }
            $where['id'] = $body['id'];
            $data['status'] = $body['status'] == 1 ? 2 : 1;
            $update_result = TZ_Loader::service('Feedback', 'Feedback')->update($data, $where);
            TZ_Response::success('修改成功！');
        }
        TZ_Response::error(10001, '请求类型错误');
    }
}