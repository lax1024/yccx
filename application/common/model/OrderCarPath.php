<?php

namespace app\common\model;

/**
 * 用户订单运行轨迹 数据模型
 */

use definition\PayCode;
use think\Model;
use tool\CarDeviceTool;

class OrderCarPath extends Model
{

    public function formatx(&$data)
    {
        if (!empty($data['path'])) {
            $data['path'] = unserialize($data['path']);
        }
    }

    /**
     * 添加订单运行轨迹
     * @param $order_id
     * @param $device_id
     * @param $start_time
     * @param $end_time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addPath($order_id, $device_id, $start_time, $end_time)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($order_id) || empty($device_id) || empty($start_time) || empty($end_time)) {
            return $out_data;
        }
        $start_time = date("Y-m-d H:i:s", $start_time);
        $end_time = date("Y-m-d H:i:s", $end_time);
        $CarDeviceTool = new CarDeviceTool();
        $out_path = $CarDeviceTool->getDeviceLog($device_id, $start_time, $end_time);
        $path = [];
        if (empty($out_path['code'])) {
            $path = $out_path['data'];
        }
        $startt = date("Y-m",strtotime($start_time));
        $in_data = [
            'order_id' => $order_id,
            'path' => serialize($path),
            'device_number' => $device_id,
            'month' => $startt,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];
        $data_has = $this->where(['order_id' => $order_id])->find();
        if (empty($data_has)) {
            $ret = $this->save($in_data);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "添加成功";
                return $out_data;
            }
        } else {
            $ret = $this->save($in_data, ['id' => $data_has['id']]);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "添加成功";
                return $out_data;
            }
        }
        $out_data['code'] = 101;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 获取订单轨迹
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPath($order_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];

        if (empty($order_id)) {
            return $out_data;
        }
        $path_data = $this->where(['order_id' => $order_id])->find();
        if (!empty($path_data)) {
            $path_data['path'] = unserialize($path_data['path']);
            $out_data['code'] = 0;
            $out_data['info'] = "获取成功";
            $out_data['data'] = $path_data;
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "获取失败";
        return $out_data;
    }

}