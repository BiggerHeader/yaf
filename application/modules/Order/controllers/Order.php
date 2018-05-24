<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/24
 * Time: 18:49
 */
class OrderController extends Yaf_Controller_Abstract
{
    public function getAction()
    {
        if ($this->getRequest()->isGet()) {
            $status = $this->getRequest()->getQuery('status');
            $page = $this->getRequest()->getQuery('page');
            $pagesize = $this->getRequest()->getQuery('pageSize');
            $limit = getPageLimit(['page' => $page, 'pageSize' => $pagesize]);
            if (isset($status)) {
                $where['status'] = $status;
            }

            $count = TZ_Loader::service('Order', 'Order')->count();
            $where['LIMIT'] = $limit;
            $joins['[>]addresses'] = ['address_id' => 'id'];
            $data = TZ_Loader::service('Order', 'Order')->select($joins, [
                'orders.id(id)',
                'orders.uuid',
                'orders.created_at',
                'orders.status',
                'orders.total_money',
                'orders.change_money',
                'addresses.detail_address',
            ], $where);

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

    public function modifyAction()
    {
        if ($this->getRequest()->isPost()) {
            $body = getRequestBody();
            $id = $body['id'];
            $uuid = $body['uuid'];

            if (isset($body['status'])) {
                $status = $body['status'] == 3 ? 3 : ($body['status'] == 1 ? 0 : 1);
                $data['status'] = $status;
            }
            if (isset($body['change_money'])) {
                $data['change_money'] = $body['change_money'];
            }
            if (empty($id)) {
                TZ_Response::error(10001, '参数有错误');
            }
            $where['id'] = $id;
            $where['uuid'] = $uuid;
            TZ_Loader::service('Order', 'Order')->update($data, $where);
            TZ_Response::success('修改成功');
        }
        TZ_Response::error(10001, '请求类型错误');
    }

    /*
     * 确认收货
     * */
    public function confireAction()
    {
        if ($this->getRequest()->isPost()) {
            $body = getRequestBody();
            $orderid = $body['orderid'];

            TZ_Loader::service('Order', 'Order')->update(['status' => 2], ['id' => $orderid]);
            TZ_Response::success('确认收货成功');
        }
        TZ_Response::error(10001, '请求类型错误');
    }
}