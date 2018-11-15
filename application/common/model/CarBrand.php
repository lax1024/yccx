<?php

namespace app\common\model;

/**
 *车辆品牌类型 数据模型
 */
use think\Model;

class CarBrand extends Model
{

    /**
     * 获取品牌名称/首字母
     * @param $brand_id
     * @return mixed|string
     */
    public function get_brand_name_initial($brand_id)
    {
        $brand = $this->where(array('brand_id' => $brand_id))->field('brand_name,initial')->find();
        if (empty($brand)) {
            return array();
        }
        return $brand;
    }
    /**
     * 获取品牌名称
     * @param $brand_id
     * @return mixed|string
     */
    public function get_brand_name($brand_id)
    {
        $brand = $this->where(array('brand_id' => $brand_id))->field('brand_name')->find();
        if (empty($brand)) {
            return "";
        }
        return $brand['brand_name'];
    }

    /**
     * 根据页码获取列表
     * @param $page
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function get_brand_list($page, $limit = 30)
    {
        $brand_list = $this->order('initial ASC')->limit($page, $limit)->select();
        return $brand_list;
    }

    /**
     * 根据首写字母获取列表
     * @param string $initial
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function get_brand_initial($initial = 'A')
    {
        $brand_list = $this->where(array('initial' => $initial))->select();
        return $brand_list;
    }
}