<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use app\common\model\CarCommon as CarCommonModel;
use definition\DeviceStatus;
use definition\CarDeviceType;
use think\cache\driver\Redis;
use think\Session;
use tool\CarDeviceTool;

/**
 * 设备通信接口
 * Class Ueditor
 * @package app\api\controller
 */
class CarDevice extends HomeBase
{
    private $car_device_tool;

    protected function _initialize()
    {
        parent::_initialize();
        $this->car_device_tool = new CarDeviceTool();
        $out_data = array(
            'code' => 1,
            'info' => "无权访问数据"
        );
        if (!Session::has('manage_id') && !Session::has('seller_id')) {
            out_json_data($out_data);
        }
    }

    /**
     * 根据设备id 获取设备列表
     * @param $device_number
     * @param int $device_type
     */
    public function get_device_list($device_number, $device_type = 1)
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if (empty($device_number) || empty($device_type)) {
            out_json_data($out_data);
        }
        if (Session::has('seller_id')) {
            $store_key_id = Session::get('store_key_id');
        }
        $page_config = ['page' => 1, 'query' => ['keyword' => $device_number]];
        $device_list = $this->car_device_tool->getPageList($device_number, $order = 'DESC', $page_config, $limit = 15);
        $device_list_data = array();
        foreach ($device_list as $value) {
            if (!empty($store_key_id)) {
                if ($value['storeKeyId'] == $store_key_id) {
                    $device_list_data[] = $value;
                }
            } else {
                $device_list_data[] = $value;
            }
        }
        $out_data['code'] = 0;
        $out_data['data'] = $device_list_data;
        $out_data['info'] = "数据获取成功";
        out_json_data($out_data);
    }

    /**
     * 解除设备绑定
     * @param $device_number
     * @param $device_type
     */
    public function remove_bind($device_number, $device_type)
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if (empty($device_number) || empty($device_type)) {
            out_json_data($out_data);
        }
        $map['device_status'] = DeviceStatus::$DeviceBind['code'];
        if (Session::has('seller_id')) {
            $map['store_key_id'] = Session::get('store_key_id');
        }
        if (empty($device_type) || $device_type == 1) {
            $map['device_type'] = CarDeviceType::$CarDeviceTypeDefault['code'];
        } else {
            $map['device_type'] = CarDeviceType::$CarDeviceTypeElectricity['code'];
        }
        $map['device_number'] = $device_number;
        $device_data = $this->car_device_tool->where($map)->find();
        if (!empty($device_data)) {
            $device_data->device_status = DeviceStatus::$DeviceOnBind['code'];
            $device_data->goods_id = 0;
            if ($device_data->save()) {
                $carcommon_model = new CarCommonModel();
                if (Session::has('seller_id')) {
                    $mapx['store_key_id'] = Session::get('store_key_id');
                }
                $mapx['device_number'] = $device_number;
                $carcommon_model->where($mapx)->setField("device_number", "");
                $out_data['code'] = 0;
                $out_data['info'] = "解除绑定成功";
                out_json_data($out_data);
            }
        }
        $out_data['code'] = 2;
        $out_data['info'] = "解除绑定失败";
        out_json_data($out_data);
    }

    /**
     * 获取车辆实时日志
     * @param $device_number
     * @return array|mixed
     */
    public function get_car_log($device_number)
    {
        $out_data = array(
            'code' => 100,
            'info' => '无数据'
        );
        if (empty($device_number)) {
            out_json_data($out_data);
        }
        $redis = new Redis();
        if (!$redis->has("status:".$device_number)) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机数据有误";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:".$device_number);
            if (empty($out_data)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $car_log_data = json_decode($out_data_device, true);
        }
        $out_data['data'] = $car_log_data;
        $out_data['code'] = 0;
        $out_data['info'] = '获取成功';
        out_json_data($out_data);
    }


}