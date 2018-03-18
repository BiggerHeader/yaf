<?php

/**
 * 订单补偿处理类
 */
class Compensate
{

    /**
     * 普通订单补偿处理
     */
    public function generalOrderComplete($order_id)
    {
        // 修改订单数据 + 创建订单操作记录
        $result1 = $this->updateOrder($order_id);
        
        // 增加商品销量
        $result2 = $this->increaseSales($order_id);
        
        TZ_Log::writeRunLog('垫付宝普通订单-补偿机制处理完成，订单ID：' . $order_id);
    }

    /**
     * 轻易分期订单补偿处理
     */
    public function qyStageOrderComplete($order_id)
    {
        // 修改订单数据 + 创建订单操作记录
        $result1 = $this->updateOrder($order_id);
        
        // 增加商品销量
        $result2 = $this->increaseSales($order_id);
        
        TZ_Log::writeRunLog('轻易分期订单-补偿机制处理完成，订单ID：' . $order_id);
    }

    /**
     * 修改订单数据 + 创建订单操作记录
     */
    private function updateOrder($order_id)
    {
        $result = true;
        $thisTime = intval(time());
        
        $compensateData1 = array(
            'status' => 'COMPLETED',
            'paymentTime' => $thisTime,
            'lastModTime' => $thisTime
        );
        
        try {
            // 修改订单状态
            $where['id'] = $order_id;
            EshopTransaction_OrderModel::updateData($compensateData1, $where);
        } catch (Exception $e) {
            $result = true;
        }
        
        $compensateData2 = array(
            'id' => createId(),
            'orderId' => $order_id,
            'from' => 'admin',
            'operatorId' => '',
            'notes' => '系统自动修正订单数据',
            'operationType' => 8,
            'createTime' => $thisTime
        );
        
        try {
            // 记录订单操作日志
            EshopTransaction_OrderoperationlogModel::addOne($compensateData2);
        } catch (Exception $e) {
            $result = true;
        }
        
        return $result;
    }

    /**
     * 增加商品销量
     */
    private function increaseSales($order_id)
    {
        $result = true;
        $thisTime = intval(time());
        
        $where['id'] = $order_id;
        $oldOrder = EshopTransaction_OrderModel::getOne($where, 'oldOrder');
        
        if (! $oldOrder || $oldOrder['oldOrder'] != 1) {
            return $result;
        }
        
        // 查询订单详情
        $thiswhere = array(
            'orderId' => $order_id,
            'logicalDel' => 1
        );
        $thisOrderDetails = EshopTransaction_OrderdetailsModel::getList($thiswhere, 'goodsId,number');
        if (! $thisOrderDetails) {
            return $result;
        }
        
        foreach ($thisOrderDetails as $dk => $dv) {
            // 查询商品销量
            $goodswhere['id'] = $dv['goodsId'];
            $thisGoods = EshopGoods_GoodsModel::getOne($goodswhere, 'volume,shopId');
            if (! $thisGoods) {
                continue;
            }
            
            // 修改商品销量
            $thisGoodsData = array(
                'volume' => intval($thisGoods['volume']) + intval($dv['number']),
                'lastModTime' => $thisTime
            );
            try {
                EshopGoods_GoodsModel::updateData($thisGoodsData, $goodswhere);
            } catch (Exception $e) {
                TZ_Log::error($e);
                $result = true;
            }
            
            // 查询店铺商品销量
            $shopwhere['id'] = $thisGoods['shopId'];
            $thisShop = EshopShop_ShopModel::getOne($shopwhere, 'goodsSaleVolume');
            if (! $thisShop) {
                continue;
            }
            
            // 修改店铺商品销量
            $thisShopData = array(
                'goodsSaleVolume' => intval($thisShop['goodsSaleVolume']) + $dv['number'],
                'lastModTime' => $thisTime
            );
            try {
                EshopShop_ShopModel::updateData($thisShopData, $shopwhere);
            } catch (Exception $e) {
                TZ_Log::error($e);
                $result = true;
            }
        }
        
        return $result;
    }
}
