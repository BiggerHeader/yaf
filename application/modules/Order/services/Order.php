<?php

/**
 * Created by PhpStorm.
 * User: Mary
 * Date: 2018/3/24
 * Time: 18:53
 */
class OrderService
{
    public function select(array $jions,$fields = '*', array $where = [])
    {
        return OrderModel::getList($jions,$fields, $where);
    }

    public function count($where = [])
    {
        return OrderModel::countData($where);
    }

    public function update($data,$where){
        return OrderModel::updateData($data,$where);
    }
}