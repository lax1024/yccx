<?php

namespace app\common\model;

/**
 *车辆车系类型 数据模型
 */
use think\Model;

class CarSeries extends Model
{
    /**
     * 获取车系名称
     * @param $series_id
     * @return mixed|string
     */
    public function get_series($series_id)
    {
        $series = $this->where(array('series_id' => $series_id))->field('series_name,series_img')->find();
        if (empty($series)) {
            return false;
        }
        return $series;
    }

    /**
     * 获取车系名称
     * @param $series_id
     * @return mixed|string
     */
    public function get_series_name($series_id)
    {
        $series = $this->where(array('series_id' => $series_id))->field('series_name')->find();
        if (empty($series)) {
            return "";
        }
        return $series['series_name'];
    }

    /**
     * 根据品牌id 获取车系列表
     * @param $brand_id
     * @param $page
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function get_series_list($brand_id, $page, $limit = 30)
    {
        $brand_list = $this->where(array('brand_id' => $brand_id))->limit($page, $limit)->select();
        return $brand_list;
    }

}