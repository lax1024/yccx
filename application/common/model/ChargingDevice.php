<?php

namespace app\common\model;

/**
 *充电桩(设备) 数据模型
 */
use think\Model;
class ChargingDevice extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * 添加设备
     * @param $device_data
     * name 设备名称
     * device_number 设备编号
     * device_type 设备类型
     * device_status 设备状态
     * location_longitude 地理位置经度
     * location_latitude 地理位置纬度
     * store_key_id 总店id
     * store_key_name 总店名称
     * @return array
     */
    public function addDevice($device_data)
    {
        $out_data = array(
            'code' => 200,
            'info' => "参数有误",
        );
        $in_device_data = array();
        //获取设备名称
        $name = $device_data['name'];
        if (empty($name)) {
            $out_data['code'] = 201;
            $out_data['info'] = "设备名称格式不正确";
            return $out_data;
        }
        $in_device_data['name'] = $name;

        //获取设备编号
        $device_number = $device_data['device_number'];
        if ($this->_verifyDeviceNumber($device_number) === true) {
            $out_data['code'] = 202;
            $out_data['info'] = "设备号已被占用";
            return $out_data;
        }
        $in_device_data['device_number'] = $device_number;

        //获取设备类型
        $device_type = $device_data['device_type'];
        if (!is_numeric($device_type)) {
            $out_data['code'] = 203;
            $out_data['info'] = "设备类型不符合规范";
            return $out_data;
        }
        $in_device_data['device_type'] = $device_type;

        //获取设备状态
        $device_status = $device_data['device_status'];
        if (!is_numeric($device_status)) {
            $out_data['code'] = 204;
            $out_data['info'] = "设备状态不符合规范";
            return $out_data;
        }
        $in_device_data['device_status'] = $device_status;

        //获取设备坐标经度
        $location_longitude = $device_data['location_longitude'];
        if (!is_numeric($location_longitude)) {
            $out_data['code'] = 205;
            $out_data['info'] = "设备坐标经度不符合规范";
            return $out_data;
        }
        $in_device_data['location_longitude'] = $location_longitude;

        //获取设备坐标纬度
        $location_latitude = $device_data['location_latitude'];
        if (!is_numeric($location_latitude)) {
            $out_data['code'] = 206;
            $out_data['info'] = "设备坐标纬度不符合规范";
            return $out_data;
        }
        $in_device_data['location_latitude'] = $location_latitude;

        //获取总店id
        $store_key_id = $device_data['store_key_id'];
        if (!is_numeric($store_key_id)) {
            $out_data['code'] = 207;
            $out_data['info'] = "总店id不符合规范";
            return $out_data;
        }
        $in_device_data['store_key_id'] = $store_key_id;

        //获取总店名称
        $store_key_name = $device_data['store_key_name'];
        if (empty($store_key_name)) {
            $out_data['code'] = 208;
            $out_data['info'] = "总店名称不符合规范";
            return $out_data;
        }
        $in_device_data['store_key_name'] = $store_key_name;

        $datetime = date("Y-m-d H:i:s");
        $in_device_data['create_time'] = $datetime;
        $in_device_data['update_time'] = $datetime;
        if ($this->save($in_device_data)) {
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 209;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 获取列表
     * @param $map
     * @param $order
     * @param $page
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getList($map, $order, $page, $limit)
    {
        $device_list = $this->where($map)->order($order)->limit($page * $limit, $limit)->select();
        return $device_list;
    }

    /**
     * 获取列表
     * @param $map
     * @param $order
     * @param $page_config
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getPageList($map, $order, $page_config, $limit)
    {
        $device_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);;
        return $device_list;
    }

    /**
     * 更新设备信息
     * @param $device_data
     * 必须数据
     * id 数据id
     * 非必须数据
     * name 设备名称
     * device_number 设备编号
     * device_type 设备类型
     * device_status 设备状态
     * location_longitude 地理位置经度
     * location_latitude 地理位置纬度
     * @return array
     */
    public function updateDevice($device_data)
    {
        $out_data = array(
            'code' => 200,
            'info' => "参数有误",
        );
        $update_device_data = array();

        //获取设备名称
        $id = $device_data['id'];
        if (!is_numeric($id)) {
            return $out_data;
        }
        //获取设备名称
        $name = $device_data['name'];
        if (!empty($name)) {
            $update_device_data['name'] = $name;
        }
        //获取设备编号
        $device_number = $device_data['device_number'];
        if (!empty($device_number)) {
            if ($this->_verifyDeviceNumber($device_number, $id, true) === false) {
                $update_device_data['device_number'] = $device_number;
            }
        }

        //获取设备类型
        $device_type = $device_data['device_type'];
        if (is_numeric($device_type)) {
            $update_device_data['device_type'] = $device_type;
        }

        //获取设备状态
        $device_status = $device_data['device_status'];
        if (is_numeric($device_status)) {
            $update_device_data['device_status'] = $device_status;
        }

        //获取设备坐标经度
        $location_longitude = $device_data['location_longitude'];
        if (is_numeric($location_longitude)) {
            $update_device_data['location_longitude'] = $location_longitude;
        }

        //获取设备坐标纬度
        $location_latitude = $device_data['location_latitude'];
        if (is_numeric($location_latitude)) {
            $update_device_data['location_latitude'] = $location_latitude;
        }

        //获取总店id
        $store_key_id = $device_data['store_key_id'];
        if (is_numeric($store_key_id)) {
            $update_device_data['store_key_id'] = $store_key_id;
        }

        //获取总店名称
        $store_key_name = $device_data['store_key_name'];
        if (!empty($store_key_name)) {
            $update_device_data['store_key_name'] = $store_key_name;
        }

        $datetime = date("Y-m-d H:i:s");
        $update_device_data['update_time'] = $datetime;
        if ($this->save($update_device_data, array(['id' => $id]))) {
            $out_data['code'] = 0;
            $out_data['info'] = "更新成功";
            return $out_data;
        }
        $out_data['code'] = 209;
        $out_data['info'] = "更新失败";
        return $out_data;
    }

    public function verifyBindDevice($device)
    {

    }

    /**
     * 验证设备编号是否已被占用
     * @param $device_number 设备编号
     * @param string $id 数据id
     * @param bool $is_ver_id 是否判断数据id
     * @return bool
     */
    private function _verifyDeviceNumber($device_number, $id = '', $is_ver_id = false)
    {
        if (empty($device_number)) {
            return true;
        }
        $device = $this->where(array('device_number' => $device_number))->field('id')->find();
        if (empty($device)) {
            //如果设备信息不存在
            return false;
        } else {
            //如果信息存在 判断是否是新数据
            if ($is_ver_id) {
                //如果是更新自身数据 则表示设备编号未被占用
                if (intval($device['id']) == intval($id)) {
                    return false;
                }
            }
        }
        return true;
    }

}