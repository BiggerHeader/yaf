<?php

class FeedbackService
{

    public function select($where = [])
    {
        return FeedbackModel::getList('*',$where);
    }

    public function count(array  $where =[])
    {
        return FeedbackModel::countData($where);
    }

    /**
     *保存 反馈数据
     */
    public function insert($data)
    {
        return FeedbackModel::addOne($data);
    }

    public function update($data,$where){
        return FeedbackModel::updateData($data,$where);
    }
}
