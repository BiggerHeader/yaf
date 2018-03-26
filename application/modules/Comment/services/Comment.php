<?php

class CommentService
{
    public function insert($data)
    {
        $insert_result = CommentModel::addOne($data);
        if ($insert_result) {
            //更新产品 评论数
            $product_data['comment_count[+]'] = 1;
            $where['id'] = $data['product_id'];
            ProductModel::updateData($product_data, $where);
            return 10000;
        } else {
            TZ_Response::error(99999);
        }
    }

    public function select($joins, $fields = '*', array $where = [])
    {
        return CommentModel::getList($joins, $fields, $where);
    }

    public function count($where)
    {
        return CommentModel::countData($where);
    }

    /**
     *保存 反馈数据
     */
    public function insert_feedback($data)
    {
        return FeedbackModel::addOne($data);
    }
}
