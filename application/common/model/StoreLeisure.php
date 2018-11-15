<?php

namespace app\common\model;

/**
 * 站点信息统计管理
 * 实现站点信息的车辆数据统计
 * 添加无车起始时间
 * 完成无车时间
 */

use definition\CarStatus;
use think\Model;

class StoreLeisure extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return time();
    }

    public function formatx(&$data)
    {
        $data['create_time_str'] = date("Y-m-d H:i:s", $data['create_time']);
        $data['end_time_str'] = date("Y-m-d H:i:s", $data['end_time']);
    }

    /**
     * 获取店铺指定字段
     * @param string $store_id
     * @param $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreField($store_id = '', $field)
    {
        if (!empty($store_id)) {
            $store_data = $this->where(array('id' => $store_id))->field($field)->find();
            if (!empty($store_data)) {
                return $store_data;
            }
        }
        return array();
    }

    /**
     * 获取店铺信息
     * @param string $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStore($store_id = '')
    {
        if (!empty($store_id)) {
            $store_data = $this->where(array('id' => $store_id))->find();
            $this->formatx($store_data);
            $out_data['code'] = 0;
            $out_data['data'] = $store_data;
            $out_data['info'] = "获取成功";
            return $out_data;//获取成功
        }
    }

    /**
     * 根据条件获取店铺列表
     * @param $map
     * @param $order
     * @param $page
     * @param int $limit
     * @return $this
     */
    public function getStoreList($map, $order, $page, $limit = 15)
    {
        $store_list = $this->where($map)->order($order)->limit($limit, $page);
        foreach ($store_list as $value) {
            $this->formatx($value);
        }
        return $store_list;
    }

    /**
     * 添加无车记录
     * @param $leisure
     * store_id 店铺id
     * store_name 店铺名称
     * store_key_id 店铺归属id
     * @return array
     */
    public function addLeisure($leisure)
    {
        $return_data = [
            'code' => 100,
            'info' => "店铺id有误",
        ];
        $store_id = $leisure['store_id'];
        if (empty($store_id)) {
            return $return_data;
        }
        $count = $this->_isCountCar($store_id);
        if ($count <= 0) {
            $store_name = $leisure['store_name'];
            if (empty($store_name)) {
                $return_data['code'] = 101;
                $return_data['info'] = "店铺名称有误";
                return $return_data;
            }
            $store_key_id = $leisure['store_key_id'];
            if (empty($store_key_id)) {
                $return_data['code'] = 102;
                $return_data['info'] = "店铺归属id有误";
                return $return_data;
            }
            $is_has = $this->where(['store_id' => $store_id, 'status' => 0])->find();
            if (empty($is_has)) {
                $in_leisure_data = [
                    'store_id' => $store_id,
                    'store_name' => $store_name,
                    'create_time' => time(),
                    'end_time' => 0,
                    'date_s' => date("Y-m-d"),
                    'date_s_year_week' => date("Y-W"),
                    'hour' => 0,
                    'status' => 0,
                    'store_key_id' => $store_key_id
                ];
                $ret = $this->save($in_leisure_data);
                if ($ret !== false) {
                    $return_data['code'] = 0;
                    $return_data['info'] = "添加成功";
                    return $return_data;
                }
            }
            $return_data['code'] = 105;
            $return_data['info'] = "添加失败";
            return $return_data;
        } else {
            $leisure = $this->where(['store_id' => $store_id, 'status' => 0])->find();
            $hour = round((time() - $leisure['create_time']) / 3600, 2);
            $up_leisure_data = [
                'end_time' => time(),
                'date_e' => date("Y-m-d"),
                'date_e_year_week' => date("Y-W"),
                'date_e_month' => date("Y-m"),
                'hour' => $hour,
                'status' => 1,
            ];
            $ret = $this->where(['id' => $leisure['id']])->setField($up_leisure_data);
            if ($ret !== false) {
                $return_data['code'] = 0;
                $return_data['info'] = "结束成功";
                return $return_data;
            }
            $return_data['code'] = 101;
            $return_data['info'] = "结束失败";
            return $return_data;
        }

    }

    /**
     * 返回店内可用车辆数量
     * @param $store_id
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function _isCountCar($store_id)
    {
        $car_model = new CarCommon();
        $map = [
            'store_site_id' => $store_id,
            'car_status' => CarStatus::$CarStatusNormal['code'],
        ];
        $car_list = $car_model->where($map)->select();
        $count = 0;
        foreach ($car_list as $value) {
            $car_model->formatx($value);
            if ($value['car_device'] == 1 && $value['driving_mileage_num'] > 50) {
                $count++;
            }
        }
        return $count;
    }
}