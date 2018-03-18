<?php

class ProductService
{
   /* public function insert($data)
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
    }*/

    public function select( $fields = '*', array $where = [])
    {
        return ProductModel::getList($fields, $where);
    }

    public function count($where =[])
    {
        return ProductModel::countData($where);
    }

    public function delete($where){
        return ProductModel::deleteData($where);
    }
}
