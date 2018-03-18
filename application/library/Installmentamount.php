<?php

/**
 * 分期订单数据处理类
 */
class Installmentamount
{

    /**
     * 获取分期订单分期金额详细
     * 参数：订单数据，店铺id，分期类型
     */
    public function getDetail($orderData = array(), $shopId = null, $type = 'stage')
    {
        $amount = isset($orderData['installmentAmount']) ? $orderData['installmentAmount'] : null;
        $periods = isset($orderData['periods']) ? $orderData['periods'] : null;
        $pledgePeriod = isset($orderData['pledgePeriod']) ? $orderData['pledgePeriod'] : null;
        
        if (is_null($amount) || is_null($periods) || is_null($pledgePeriod) || is_null($shopId)) {
            return null;
        }
        
        $baseData = $this->getBaseData($amount, $periods);
        
        $feeList = $this->getFeelist($shopId, $type, $periods);
        
        // 分期交易时，手续费比例需要按照质押期数对应比例计算
        if ($type == 'stage') {
            $pledgeList = $feeList['pledgeList'];
            if (empty($pledgeList) || ! isset($pledgeList[$pledgePeriod])) {
                return null;
            }
            
            $pledgeData = $pledgeList[$pledgePeriod];
            
            $data = array(
                'ratio' => $pledgeData['ratio'],
                'ppwratio' => $pledgeData['ppw'],
                'dfbratio' => $pledgeData['dfb'],
                'fee' => bcmul($amount, $pledgeData['ratio'] / 100, 2), // 总手续费
                'kpayFee' => bcmul($amount, $pledgeData['dfb'] / 100, 2), // 垫付宝所收手续费
                'ppwFee' => bcmul($amount, $pledgeData['ppw'] / 100, 2), // 乒乒网所收手续费
                'firstStage' => $baseData['firstStage'],
                'average' => $baseData['average']
            );
        } else {
            $data = array(
                'ratio' => $feeList['ratio'],
                'ppwratio' => $feeList['ppw'],
                'dfbratio' => $feeList['dfb'],
                'fee' => bcmul($amount, $feeList['ratio'] / 100, 2), // 总手续费
                'kpayFee' => bcmul($amount, $feeList['dfb'] / 100, 2), // 垫付宝所收手续费
                'ppwFee' => bcmul($amount, $feeList['ppw'] / 100, 2), // 乒乒网所收手续费
                'firstStage' => $baseData['firstStage'],
                'average' => $baseData['average']
            );
        }
        
        return $data;
    }

    /**
     * 获取分期订单每期金额详细
     * 参数：店铺id，业务类型（分期/0息0首付），订单金额
     */
    public function getList($shopId, $type = 'stage', $amount)
    {
        $data = array();
        
        $feeList = $this->getFeelist($shopId, $type);
        
        if ($type == 'stage') {
            foreach ($feeList as $key => $value) {
                // 默认按照该期数下第一个可质押期数的比例计算手续费
                $baseData = $this->getBaseData($amount, $value['periods']);
                $data[$value['periods']] = array(
                    'firstStage' => number_format($baseData['firstStage'], 2),
                    'average' => number_format($baseData['average'], 2)
                );
                
                // 分期交易时，手续费比例需要按照质押期数对应比例计算
                foreach ($value['pledgeList'] as $k => $v) {
                    $data[$value['periods']][$v['pledgePeriods']] = array(
                        'ratio' => $v['ratio'],
                        'fee' => number_format(bcmul($amount, $v['ratio'] / 100, 3), 2)
                    );
                }
            }
        } else {
            foreach ($feeList as $key => $value) {
                $baseData = $this->getBaseData($amount, $value['periods']);
                $data[$value['periods']] = array(
                    'firstStage' => number_format($baseData['firstStage'], 2),
                    'average' => number_format($baseData['average'], 2),
                    $value['periods'] => array(
                        'ratio' => $value['ratio'],
                        'fee' => number_format(bcmul($amount, $value['ratio'] / 100, 3), 2)
                    )
                );
            }
        }
        
        return $data;
    }

    /**
     * 计算每期金额
     */
    public function getBaseData($amount, $periods)
    {
        // 总值 / 期数 = 平均值
        $average = bcdiv($amount, $periods, 2);
        
        // 平均值 * 期数 = 平均总值
        $averageTotal = bcmul($average, $periods, 2);
        
        // 总值 - 平均总值 = 差值
        $difference = bcsub($amount, $averageTotal, 2);
        
        // 平均值 + 差值 = 首期金额
        $firstStage = bcadd($average, $difference, 2);
        
        $data = array(
            'average' => $average,
            'firstStage' => $firstStage
        );
        
        return $data;
    }

    /**
     * 查询店铺分期可用期数
     */
    public function getFeelist($shopId, $type = 'stage', $periods = null, $periodsId = null)
    {
        $thiswhere = array(
            'shopId' => $shopId,
            'status' => 1
        );
        
        $field = ($type == 'stage') ? "id,periods" : "id,periods,ppw,dfbCode,dfb,ratio";
        if (! is_null($periods) || ! is_null($periodsId)) {
            ! is_null($periods) && $thiswhere['periods'] = $periods;
            ! is_null($periodsId) && $thiswhere['id'] = $periodsId;
            if ($type == 'stage') {
                $feeList = EshopShop_ShopfeelistModel::getOne($thiswhere, $field);
            } else {
                $feeList = EshopShop_ShoptotalpledgeModel::getOne($thiswhere, $field);
            }
            
            // 分期交易时，手续费比例需要按照质押期数对应比例计算
            if ($feeList && $type == 'stage') {
                $pledgeWhere['shopId'] = $shopId;
                $pledgeWhere['periods'] = $feeList['periods'];
                $pledgeWhere['ORDER'] = array(
                    'pledgePeriods' => 'ASC'
                );
                $pledgeList = EshopShop_FeeModel::getList($pledgeWhere, "ppw,dfbCode,dfb,ratio,pledgePeriods");
                // 如果不存在可质押期数，则代表当前期数不可用
                if (! $pledgeList) {
                    $feeList = null;
                } else {
                    foreach ($pledgeList as $key => $value) {
                        $feeList['pledgeList'][$value['pledgePeriods']] = $value;
                    }
                }
            }
        } else {
            $thiswhere['ORDER'] = array(
                'periods' => 'ASC'
            );
            if ($type == 'stage') {
                $feeList = EshopShop_ShopfeelistModel::getList($thiswhere, $field);
            } else {
                $feeList = EshopShop_ShoptotalpledgeModel::getList($thiswhere, $field);
            }
            
            // 分期交易时，手续费比例需要按照质押期数对应比例计算
            if ($feeList && $type == 'stage') {
                foreach ($feeList as $key => $value) {
                    $pledgeWhere['shopId'] = $shopId;
                    $pledgeWhere['periods'] = $value['periods'];
                    $pledgeWhere['ORDER'] = array(
                        'pledgePeriods' => 'ASC'
                    );
                    $pledgeList = EshopShop_FeeModel::getList($pledgeWhere, "ppw,dfbCode,dfb,ratio,pledgePeriods");
                    // 如果不存在可质押期数，则代表当前期数不可用
                    if (! $pledgeList) {
                        unset($feeList[$key]);
                    } else {
                        $feeList[$key]['pledgeList'] = $pledgeList;
                    }
                }
            }
        }
        
        return $feeList;
    }

    /**
     * 计算本单分期金额上限
     * 参数：订单金额，店铺分期比例，店铺分期金额上限
     */
    public function getAmountLimit($orderAmount, $ratio, $amountLimit)
    {
        // 订单金额 * 店铺分期比例 = 订单可分期金额上限
        $orderLimit = bcmul($orderAmount, $ratio / 100);
        
        // 判断 订单可分期金额上限 是否大于 店铺分期金额上限
        $comp = bccomp($orderLimit, $amountLimit, 2);
        if ($comp == 1) {
            $limit = intval($amountLimit);
        } else {
            $limit = $orderLimit;
        }
        
        // 分期金额需要大于100
        if ($limit < 100) {
            $limit = 0;
        }
        
        return $limit;
    }
}
