<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/12
 * Time: 19:30
 */

namespace app\common\model;


use think\Model;

class PileStore extends Model
{
    /**
     * 根据门店id查询门店的相关信息
     * @param string $store_id  所要查询的门店id
     */
    function getStoreById($store_id)
    {
        return $this->where(array('store_id' => $store_id))->find();
    }

    /**
     * 获取门店列表
     */
    function getStoreList($page){
        $limit = $page * 10;
        return $this->where($this->limit(0,$limit))->select();
    }
}