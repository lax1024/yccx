<?php

namespace app\common\model;

/**
 *车辆车型类型 数据模型
 */
use think\Model;

class CarType extends Model
{
    /**
     * 获取车型
     * @param $type_id
     * @return mixed|string
     */
    public function get_type($type_id)
    {
        $type = $this->where(array('type_id' => $type_id))->field('type_name')->find();
        if (empty($type)) {
            return false;
        }
        return $type;
    }

    /**
     * 获取车型名称
     * @param $type_id
     * @return mixed|string
     */
    public function get_type_name($type_id)
    {
        $type = $this->where(array('type_id' => $type_id))->field('type_name')->find();
        if (empty($type)) {
            return "";
        }
        return $type['type_name'];
    }

    /**
     * 根据品牌id 车系id 获取车型列表
     * @param $brand_id
     * @param $series_id
     * @param $page
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function get_type_list($brand_id, $series_id, $page, $limit = 30)
    {
        $type_list = $this->where(array('series_id' => $series_id, 'brand_id' => $brand_id))->limit($page, $limit)->select();
        return $type_list;
    }

}