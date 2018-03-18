<?php

/**
 * 商品品类数据处理类
 */
class Category
{

    private $parentStr;

    private $parentId;

    private $finalChild;

    private $parentInfo;

    /**
     * 根据父品类id获取所有末级子品类
     * $pid：父品类id，查一级品类时传null或0
     * $status：品类状态，all全部，1有效，0无效
     */
    public function getAllFinalChild($pid = null, $status = 'all')
    {
        $this->getFinalChildData($pid, $status);
        
        return $this->finalChild;
    }
    
    // 获取所有末级子品类（递归处理方法）
    private function getFinalChildData($pid = null, $status = 'all')
    {
        $where['AND']['logicalDel'] = 1;
        
        if (is_null($pid) || $pid == '0') {
            $where['AND']['OR']['pid'] = null;
            $where['AND']['OR']['pid'] = '';
        } else {
            $where['AND']['pid'] = $pid;
        }
        
        if ($status != 'all') {
            $where['AND']['status'] = $status;
        }
        
        $childData = EshopGoods_CategoryModel::getList($where, 'id,name,status,level,pid');
        if ($childData) {
            foreach ($childData as $key => $value) {
                if ($value['level'] == 3) {
                    $this->finalChild[] = $value;
                } else {
                    $this->getFinalChildData($value['id'], $status);
                }
            }
        }
    }

    /**
     * 根据父品类id获取子品类列表
     * $pid：父品类id，查一级品类时传null或0
     * $status：品类状态，all全部，1有效，0无效
     * $level：品类级别，all全部子集，2子集里的中级品类，3子集里的末级品类
     * $curentId：修改上级品类时，需要排除该品类本身及其子集
     */
    public function getChildCategory($pid = null, $status = 'all', $level = 'all', $curentId = null)
    {
        $where['AND']['logicalDel'] = 1;
        
        if (is_null($pid) || $pid == '0') {
            $where['AND']['OR']['pid'] = null;
            $where['AND']['OR']['pid'] = '';
        } else {
            $where['AND']['pid'] = $pid;
        }
        
        if ($status != 'all') {
            $where['AND']['status'] = $status;
        }
        
        if ($level != 'all') {
            $where['AND']['level'] = $level;
        }
        
        if (! is_null($curentId) && $curentId != '0') {
            $where['AND']['id[!]'] = $curentId;
        }
        $where['ORDER']['level'] = 'ASC';
        $where['ORDER']['createTime'] = 'DESC';
        
        return EshopGoods_CategoryModel::getList($where);
    }

    /**
     * 获取品类树
     * $status：品类状态，all全部，1有效，0无效
     * $forHtml：是否获取便于html输出的数据格式
     */
    public function getCategoryTree($status = 'all', $forHtml = true)
    {
        // 定义查询条件
        $where['logicalDel'] = 1;
        
        if ($status != 'all') {
            $where['status'] = $status;
        }
        $where['ORDER']['level'] = 'ASC';
        $where['ORDER']['createTime'] = 'DESC';
        
        $categoryData = EshopGoods_CategoryModel::getList($where, 'id,name,status,level,pid');
        
        $Tree = new PHPTree();
        if ($forHtml) {
            $treeList = $Tree->makeTreeForHtml($categoryData);
        } else {
            $treeList = $Tree->makeTree($categoryData);
        }
        
        return $treeList;
    }

    /**
     * 根据子品类id获取父级品类
     * $id：品类id
     */
    public function getParentCategory($childId = null)
    {
        if (is_null($childId)) {
            return null;
        }
        $where['id'] = $childId;
        
        $thisCategoryData = EshopGoods_CategoryModel::getList($where, 'id,name,pid');
        
        if ($thisCategoryData) {
            $this->parentStr = $thisCategoryData['name'];
            $this->getparentStr($thisCategoryData['pid']);
        }
        
        return $this->parentStr;
    }
    
    // 获取父级品类（递归处理方法）
    private function getparentStr($pid)
    {
        $where['id'] = $pid;
        
        $categoryData = EshopGoods_CategoryModel::getOne($where, 'id,name,pid,categoryNum,type');
        if ($categoryData) {
            $this->parentStr = $categoryData['name'] . '&nbsp;>&nbsp;' . $this->parentStr;
            $this->parentId = $categoryData['id'];
            $this->parentInfo[] = $categoryData;
            
            if (! is_null($categoryData['pid']) && $categoryData['pid'] != '') {
                $this->getparentStr($categoryData['pid']);
            }
        }
    }

    /**
     * 根据子品类id获取父级品类ID
     * $id：品类id
     */
    public function getParentId($childId = null)
    {
        if (is_null($childId)) {
            return null;
        }
        
        $where['id'] = $childId;
        
        $thisCategoryData = EshopGoods_CategoryModel::getOne($where, 'id,name,pid');
        if ($thisCategoryData) {
            $this->parentId = $thisCategoryData['id'];
            $this->getparentStr($thisCategoryData['pid']);
        }
        
        return $this->parentId;
    }

    /**
     * 根据子品类id获取父级品类树信息
     * $id：品类id
     */
    public function getParentInfo($childId = null)
    {
        if (is_null($childId)) {
            return null;
        }
        
        $this->parentInfo = null;
        $where['id'] = $childId;
        
        $thisCategoryData = EshopGoods_CategoryModel::getOne($where, 'id,name,pid,categoryNum,level,type');
        if ($thisCategoryData) {
            $this->parentInfo[] = $thisCategoryData;
            if ($thisCategoryData['level'] != 1) {
                $this->getparentStr($thisCategoryData['pid']);
            }
        }
        
        return $this->parentInfo;
    }
}
