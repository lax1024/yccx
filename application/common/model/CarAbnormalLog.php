<?php

namespace app\common\model;

/**
 *异常车机列表模型 数据模型
 */

use definition\CarOperationType;
use think\cache\driver\Redis;
use think\Model;

class CarAbnormalLog extends Model
{
    public function formatx(&$data)
    {
        $data['status_str'] = "未处理";
        $data['voltage'] = "无数据";
        $data['energy'] = "无数据";
        $data['driving_mileage'] = "无数据";
        $data['car_device_str'] = "无数据";
        $data['driving_mileage_num'] = 0;
        $data['gain_time_str'] = "无数据";
        if (!empty($data['gain_time'])) {
            $data['gain_time_str'] = date("Y-m-d H:i:s", $data['gain_time']);
            $data['gain_hour'] = intval((time() - intval($data['gain_time'])) / 3600);
        }
        if (intval($data['status']) == 1) {
            $data['status_str'] = "已处理";
        } else {
            $redis = new Redis();
            if (!$redis->has("login:" . $data['device_number'])) {
                $data['car_device_str'] = "离线";
                $data['car_device'] = 0;
            } else {
                $data['car_device_str'] = "在线";
                $data['car_device'] = 1;
                if (intval($data['type']) == 2) {
                    $this->endAbnormalLog($data['goods_id']);
                    unset($data);
                }
            }
            $out_data_device = $redis->get("status:" . $data['device_number']);
            if (!empty($out_data_device)) {
                $device_data = json_decode($out_data_device, true);
                $data['driving_mileage'] = $device_data['drivingMileage'];
                $data['energy'] = $device_data['energy'];
                $data['location_latitude'] = $device_data['latitude'];
                $data['location_longitude'] = $device_data['longitude'];
                $data['voltage'] = $device_data['batteryVoltage'];
                if (empty($data['energy'])) {
                    $data['energy'] = (intval($data['driving_mileage']) / 160) * 100;
                } else if (empty($data['driving_mileage'])) {
                    $data['driving_mileage'] = intval(floatval($data['energy']) / 100 * 100);
                }
                $data['driving_mileage_num'] = $data['driving_mileage'];
            } else {
                $data['driving_mileage'] = "无数据";
                $data['driving_mileage_num'] = 0;
            }
            if (intval($data['type']) == 1) {
                if (intval($data['driving_mileage_num']) >= 90) {
                    $this->endAbnormalLog($data['goods_id']);
                    unset($data);
                }
            } else if (intval($data['type']) == 3) {
                if (floatval($data['voltage']) >= 11.3) {
                    $this->endAbnormalLog($data['goods_id']);
                    unset($data);
                }
            } else if (intval($data['type']) == 6) {
                $store_model = new Store();
                $field = "location_longitude,location_latitude,store_park_price";
                $car_data_model = new CarCommon();
                $car_data = $car_data_model->getCarCommonField($data['goods_id'], 'store_site_id');
                $store_site_id = 0;
                if (empty($car_data['code'])) {
                    $store_site_id = $car_data['data']['store_site_id'];
                }
                $store_data = $store_model->getStoreField($store_site_id, $field);
                if (floatval($store_data['store_park_price']) <= 0) {
                    $this->endAbnormalLog($data['goods_id']);
                    unset($data);
                }
            }
        }
        $CarOperationTypeCode = CarOperationType::$CAROPERATIONTYPE_CODE;
        $data['type_str'] = $CarOperationTypeCode[intval($data['type'])];
    }

    /**
     * 添加更新维护车辆
     * @param $abnormal
     * goods_id 车辆id
     * device_number 车机设备
     * licence_plate 管理人员等级
     * type 维护状态
     * remark 任务说明
     * store_site_id 站点id
     * store_site_name 站点名称
     * store_key_id 归属店铺id
     * store_key_name归属店铺名称
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addAbnormalLog($abnormal)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($abnormal['goods_id']) || empty($abnormal['device_number']) || empty($abnormal['licence_plate']) || empty($abnormal['store_key_id'])) {
            return $out_data;
        }
        $abnormal_data = $this->where(['goods_id' => $abnormal['goods_id'], 'status' => 0])->field('gain_time')->find();
        if (empty($abnormal_data['gain_time'])) {
            $in_operation = [
                'goods_id' => $abnormal['goods_id'],
                'cartype_name' => $abnormal['cartype_name'],
                'device_number' => $abnormal['device_number'],
                'licence_plate' => $abnormal['licence_plate'],
                'type' => $abnormal['type'],
                'remark' => $abnormal['remark'],
                'gain_time' => time(),
                'day' => date("Y-m-d"),
                'store_site_id' => $abnormal['store_site_id'],
                'store_site_name' => $abnormal['store_site_name'],
                'store_key_id' => $abnormal['store_key_id'],
                'store_key_name' => $abnormal['store_key_name']
            ];
            $this->save($in_operation);
            $out_data['code'] = 0;
            $out_data['gain_time'] = 0.0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['gain_time'] = round((time() - floatval($abnormal_data['gain_time'])) / 3600, 1);
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     *结束任务
     * @param $goods_id
     * @return bool
     */
    public function endAbnormalLog($goods_id)
    {
        $ret = $this->where(['goods_id' => $goods_id, 'status' => 0])->setField(['status' => 1, 'end_time' => time()]);
        if ($ret !== false) {
            return true;
        }
        return false;
    }

    /**
     * 任务是否存在
     * @param $goods_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHasAbnormal($goods_id)
    {
        $ret = $this->where(['goods_id' => $goods_id, 'status' => 0])->field('id')->find();
        if (!empty($ret)) {
            return true;
        }
        return false;
    }

    /**
     * 获取管理员
     * @param $id
     * @param $phone
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAbnormal($id = '')
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        if (empty($id)) {
            $abnormal = $this->where(['id' => $id])->find();
            if (empty($abnormal)) {
                return $out_data;
            }
            $this->formatx($abnormal);
            if (empty($abnormal)) {
                $out_data['code'] = 101;
                $out_data['info'] = "获取失败";
                return $out_data;
            }
            $out_data['code'] = 0;
            $out_data['data'] = $abnormal;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
        $out_data['code'] = 1;
        $out_data['info'] = "获取失败";
        return $out_data;
    }

    /**
     * 获取管理员
     * @param int $store_key_id 店铺归属
     * @param int $status 状态
     * @param int $type 类型
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAbnormalList($store_key_id = 153, $status = 0, $type = 6)
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        $abnormal_list_out = [];
        if (empty($store_key_id)) {
            return $out_data;
        } else {
            $map = ['store_key_id' => $store_key_id, 'status' => $status];
            if (!empty($type)) {
                $map['type'] = $type;
            }
            $abnormal_list = $this->where($map)->order("type DESC")->select();
            if (empty($abnormal_list)) {
                return $out_data;
            }
            foreach ($abnormal_list as &$value) {
                $this->formatx($value);
                if (!empty($value)) {
                    $abnormal_list_out[] = $value;
                }
            }
        }
        $out_data['code'] = 0;
        $out_data['data'] = $abnormal_list_out;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     * 获取列表 框架分页
     * @param array $map
     * @param string $order
     * @param $config_page
     * @param int $limit
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPagelist($map = array(), $order = '', $config_page, $limit = 8)
    {
        $abnormal_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if (!empty($abnormal_list)) {
            foreach ($abnormal_list as &$value) {
                $this->formatx($value);
            }
        }
        return $abnormal_list;
    }

}