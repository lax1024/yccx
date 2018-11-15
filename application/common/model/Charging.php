<?php

namespace app\common\model;

/**
 *充电桩(商品) 数据模型
 */

use definition\ChargingStatus;
use definition\ChargingType;
use definition\PileStatus;
use definition\SceneType;
use think\Model;
use tool\ChargingDeviceTool;
use tool\WecharTool;

class Charging extends Model
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
     * 添加充电桩
     * @param $charging_data
     * 必须数据
     * store_id 店铺id
     * store_name 店铺名称
     * name 充电桩名称
     * device_number 充电桩设备编号
     * device_gun 充电桩设备抢编号
     * power 充电桩功率
     * quantity_price 充电桩电费价格
     * hour_price 每小时充电价格
     * charging_photos 充电桩图集
     * location_longitude 充电桩位置经度
     * location_latitude 充电桩位置纬度
     * charging_status 充电桩状态
     * charging_type 充电桩类型
     * store_key_id 电桩归属总店id
     * store_key_name 电桩归属总店名称
     * 非必须数据
     * tag 充电桩注意标签
     * @return array
     */
    public function addCharging($charging_data)
    {
        $out_data = array(
            'code' => 100,
            'info' => "参数有误"
        );
        $in_charging_data = array();

        //获取店铺id
        $store_id = $charging_data['store_id'];
        if (!is_numeric($store_id)) {
            //验证店铺id合法性
            $out_data['code'] = 101;
            $out_data['info'] = "店铺id不合法";
            return $out_data;
        }
        $in_charging_data['store_id'] = $store_id;

        //获取店铺名称
        $store_name = $charging_data['store_name'];
        if (empty($store_name)) {
            //验证店铺名称合法性
            $out_data['code'] = 102;
            $out_data['info'] = "店铺名称不合法";
            return $out_data;
        }
        $in_charging_data['store_name'] = $store_name;

        //获取店铺名称
        $name = $charging_data['name'];
        if (empty($name)) {
            //验证店铺名称合法性
            $out_data['code'] = 103;
            $out_data['info'] = "充电桩名称不合法";
            return $out_data;
        }
        $in_charging_data['name'] = $name;

        //获取充电桩编号
        $device_number = $charging_data['device_number'];
        if (empty($device_number)) {
            //验证店铺名称合法性
            $out_data['code'] = 104;
            $out_data['info'] = "充电桩编号不合法";
            return $out_data;
        }
        $in_charging_data['device_number'] = $device_number;

        //获取充电桩充电枪编号
        $device_gun = $charging_data['device_gun'];
        if (empty($device_gun)) {
            $device_gun = 0;
        }
        $in_charging_data['device_gun'] = $device_gun;

        $in_charging_data['power'] = $charging_data['power'];
        //获取充电桩每小时充电价格
        $hour_price = $charging_data['hour_price'];
        if (!is_numeric($hour_price) || intval($hour_price) < 0) {
            //验证店铺名称合法性
            $out_data['code'] = 107;
            $out_data['info'] = "充电桩每小时充电价格不合法";
            return $out_data;
        }
        $in_charging_data['hour_price'] = $hour_price;
        //获取充电桩每度电价格
        $quantity_price = $charging_data['quantity_price'];
        if (!is_numeric($quantity_price) || intval($quantity_price) < 0) {
            //验证店铺名称合法性
            $out_data['code'] = 108;
            $out_data['info'] = "充电桩电价格不合法";
            return $out_data;
        }
        $in_charging_data['quantity_price'] = $quantity_price;

        //获取电桩归属总店id
        $location_longitude = $charging_data['location_longitude'];
        if (!is_numeric($location_longitude)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 111;
            $out_data['info'] = "充电桩位置经度不合法";
            return $out_data;
        }
        $in_charging_data['location_longitude'] = $location_longitude;

        //获取充电桩位置经度
        $location_latitude = $charging_data['location_latitude'];
        if (!is_numeric($location_latitude)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 112;
            $out_data['info'] = "充电桩位置经度不合法";
            return $out_data;
        }
        $in_charging_data['location_latitude'] = $location_latitude;

        //获取充电桩状态
        $charging_status = $charging_data['charging_status'];
        if (!is_numeric($charging_status) || intval($charging_status) < 0) {
            //验证充电桩状态合法性
            $out_data['code'] = 110;
            $out_data['info'] = "充电桩状态不合法";
            return $out_data;
        }
        $in_charging_data['charging_status'] = $charging_status;

        //获取充电桩类型
        $charging_type = $charging_data['charging_type'];
        if (!is_numeric($charging_type) || intval($charging_type) < 0) {
            //验证充电桩状态合法性
            $out_data['code'] = 115;
            $out_data['info'] = "充电桩类型不合法";
            return $out_data;
        }
        $in_charging_data['charging_type'] = $charging_type;

        //获取电桩归属总店id
        $store_key_id = $charging_data['store_key_id'];
        if (!is_numeric($store_key_id)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 113;
            $out_data['info'] = "获取电桩归属总店id不合法";
            return $out_data;
        }
        $in_charging_data['store_key_id'] = $store_key_id;

        //获取电桩归属总店名称
        $store_key_name = $charging_data['store_key_name'];
        if (empty($store_key_name)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 114;
            $out_data['info'] = "获取电桩归属总店名称不合法";
            return $out_data;
        }
        $in_charging_data['store_key_name'] = $store_key_name;
        //获取电桩图集
        $charging_photos = $charging_data['charging_photos'];
        if (!empty($charging_photos)) {
            $in_charging_data['charging_photos'] = serialize($charging_photos);
        }
        $in_charging_data['create_time'] = date("Y-m-d H:i:s");
        $in_charging_data['update_time'] = date("Y-m-d H:i:s");
        if ($this->save($in_charging_data)) {
            $charging_device_tool = new ChargingDeviceTool();
            $charging_device_tool->setCharging($device_number, $quantity_price);
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 120;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 更新充电桩
     * @param $charging_data
     * 必须数据
     * id 设备编号id
     * store_id 店铺id
     * store_name 店铺名称
     * name 充电桩名称
     * device_number 充电桩设备编号
     * device_gun 充电桩设备抢编号
     * power 充电桩功率
     * quantity_price 充电桩电费价格
     * hour_price 每小时充电价格
     * charging_status 充电桩状态
     * charging_type 充电桩类型
     * location_longitude 充电桩位置经度
     * location_latitude 充电桩位置纬度
     * store_key_id 电桩归属总店id
     * store_key_name 电桩归属总店名称
     * 非必须数据
     * tag 充电桩注意标签
     * @return array
     */
    public function updateCharging($charging_data)
    {
        $out_data = array(
            'code' => 100,
            'info' => "参数有误"
        );

        $update_charging_data = array();

        //设备编号id
        $id = $charging_data['id'];
        if (!is_numeric($id)) {
            //验证设备编号id合法性
            $out_data['code'] = 100;
            $out_data['info'] = "设备编号id不合法";
            return $out_data;
        }
        //获取店铺id
        $store_id = $charging_data['store_id'];
        if (!is_numeric($store_id)) {
            //验证店铺id合法性
            $out_data['code'] = 101;
            $out_data['info'] = "店铺id不合法";
            return $out_data;
        }
        $update_charging_data['store_id'] = $store_id;

        //获取店铺名称
        $store_name = $charging_data['store_name'];
        if (empty($store_name)) {
            //验证店铺名称合法性
            $out_data['code'] = 102;
            $out_data['info'] = "店铺名称不合法";
            return $out_data;
        }
        $update_charging_data['store_name'] = $store_name;

        //获取店铺名称
        $name = $charging_data['name'];
        if (empty($name)) {
            //验证店铺名称合法性
            $out_data['code'] = 103;
            $out_data['info'] = "充电桩名称不合法";
            return $out_data;
        }
        $update_charging_data['name'] = $name;

        //获取充电桩编号
        $device_number = $charging_data['device_number'];
        if (empty($device_number)) {
            //验证店铺名称合法性
            $out_data['code'] = 104;
            $out_data['info'] = "充电桩编号不合法";
            return $out_data;
        }
        $update_charging_data['device_number'] = $device_number;
        $update_charging_data['power'] = $charging_data['power'];

        //获取充电桩充电枪编号
        $device_gun = $charging_data['device_gun'];
        if (empty($device_gun)) {
            $device_gun = 0;
        }
        $update_charging_data['device_gun'] = $device_gun;

        //获取充电桩每度电价格
        $quantity_price = $charging_data['quantity_price'];
        if (!is_numeric($quantity_price) || intval($quantity_price) < 0) {
            //验证店铺名称合法性
            $out_data['code'] = 108;
            $out_data['info'] = "充电桩电价格不合法";
            return $out_data;
        }
        $update_charging_data['quantity_price'] = $quantity_price;

        //获取充电桩每小时充电价格
        $hour_price = $charging_data['hour_price'];
        if (!is_numeric($hour_price) || intval($hour_price) < 0) {
            //验证店铺名称合法性
            $out_data['code'] = 107;
            $out_data['info'] = "充电桩每小时充电价格不合法";
            return $out_data;
        }
        $update_charging_data['hour_price'] = $hour_price;
        //获取充电桩状态
        $charging_status = $charging_data['charging_status'];
        if (!is_numeric($charging_status) || intval($charging_status) < 0) {
            //验证充电桩状态合法性
            $out_data['code'] = 110;
            $out_data['info'] = "充电桩状态不合法";
            return $out_data;
        }
        $update_charging_data['charging_status'] = $charging_status;

        //获取充电桩类型
        $charging_type = $charging_data['charging_type'];
        if (!is_numeric($charging_type) || intval($charging_type) < 0) {
            //验证充电桩状态合法性
            $out_data['code'] = 115;
            $out_data['info'] = "充电桩类型不合法";
            return $out_data;
        }
        $update_charging_data['charging_type'] = $charging_type;

        //获取电桩归属总店id
        $location_longitude = $charging_data['location_longitude'];
        if (!is_numeric($location_longitude)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 110;
            $out_data['info'] = "充电桩位置经度不合法";
            return $out_data;
        }
        $update_charging_data['location_longitude'] = $location_longitude;

        //获取充电桩位置经度
        $location_latitude = $charging_data['location_latitude'];
        if (!is_numeric($location_latitude)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 111;
            $out_data['info'] = "充电桩位置经度不合法";
            return $out_data;
        }
        $update_charging_data['location_latitude'] = $location_latitude;

        //获取电桩归属总店id
        $store_key_id = $charging_data['store_key_id'];
        if (!is_numeric($store_key_id)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 112;
            $out_data['info'] = "获取电桩归属总店id不合法";
            return $out_data;
        }
        $update_charging_data['store_key_id'] = $store_key_id;

        //获取电桩归属总店名称
        $store_key_name = $charging_data['store_key_name'];
        if (empty($store_key_name)) {
            //验证充电桩位置经度合法性
            $out_data['code'] = 113;
            $out_data['info'] = "获取电桩归属总店名称不合法";
            return $out_data;
        }
        $update_charging_data['store_key_name'] = $store_key_name;

        //获取电桩图集
        $charging_photos = $charging_data['charging_photos'];
        if (!empty($charging_photos)) {
            $in_charging_data['charging_photos'] = serialize($charging_photos);
        }

        $update_charging_data['update_time'] = date("Y-m-d H:i:s");
        if ($this->save($update_charging_data, ['id' => $id])) {
            $charging_device_tool = new ChargingDeviceTool();
            $charging_device_tool->setCharging($device_number, $quantity_price);
            $out_data['code'] = 0;
            $out_data['info'] = "更新成功";
            return $out_data;
        }
        $out_data['code'] = 114;
        $out_data['info'] = "更新成功";
        return $out_data;
    }

    /**
     * 更新充电枪的二维码
     * @param $device_number
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateChargingUrl($device_number)
    {
        $out_data = [
            'code' => 100,
            'info' => "更新失败"
        ];
        $charging_data = $this->where(['device_number' => $device_number])->field('id,device_number,device_gun')->select();
        if (!empty($charging_data)) {
            foreach ($charging_data as $v) {
                $terminal_str = SceneType::$SceneTypeEle['code'] . "_" . $v['id'];
                $out_qrcode = WecharTool::getQRcode($terminal_str);
                if (isset($out_qrcode['url'])) {
                    $this->where(['id' => $v['id']])->setField(['charging_url' => $out_qrcode['url']]);
                }
            }
            $out_data['code'] = 0;
            $out_data['info'] = "二维码更新成功";
            return $out_data;
        }
        return $out_data;
    }

    /**
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
        $charging_status = ChargingStatus::$CHARGINGSTATUS_CODE;
        $data['charging_status_str'] = $charging_status[intval($data['charging_status'])];
        $charging_type = ChargingType::$CHARGINGTYPE_CODE;
        $data['charging_type_str'] = $charging_type[intval($data['charging_type'])];
    }

    /**
     * 获取充电桩管理列表
     * @param array $map
     * @param string $order
     * @param int $page
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return \think\Paginator
     */
    public function getChargingList($map = array(), $order = '', $limit = 8, $is_super_admin = false, $store_key_id = '', $page_config)
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $charging_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($charging_list)) {
            foreach ($charging_list as &$value) {
                $this->formatx($value);
            }
        }
        return $charging_list;
    }

    /**
     * 获取充电桩信息
     * @param $id
     * @param bool $is_admin
     * @return mixed
     */
    public function getCharging($id, $is_admin = false)
    {
        if (!is_numeric($id)) {
            $out_data['code'] = 840;
            $out_data['info'] = "充电桩数据id有误";
            return $out_data;
        }
        $charging = $this->find($id);
        if (empty($charging)) {
            $out_data['code'] = 841;
            $out_data['info'] = "充电桩数据不存在";
            return $out_data;
        }
        if (!$is_admin) {
            if (intval($charging['charging_status']) == ChargingStatus::$ChargingStatusNormal['code']) {
                if (!empty($charging['tag'])) {
                    $charging['tag'] = unserialize($charging['tag']);
                }
                $out_data['code'] = 0;
                $out_data['data'] = $charging;
                $out_data['info'] = "充电桩数据获取成功";
                return $out_data;
            }
            $out_data['code'] = 840;
            $out_data['info'] = "充电桩数据无权获取";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $charging;
            $out_data['info'] = "充电桩数据获取成功";
            return $out_data;
        }
    }

    /**
     * 获取充电桩信息
     * @param $id
     * @param bool $is_admin
     * @return mixed
     */
    public function getChargingWhere($where, $is_admin = false)
    {
        if (!is_numeric($where)) {
            $out_data['code'] = 840;
            $out_data['info'] = "充电桩数据有误";
            return $out_data;
        }
        $charging = $this->where($where)->find();
        if (empty($charging)) {
            $out_data['code'] = 841;
            $out_data['info'] = "充电桩数据不存在";
            return $out_data;
        }
        if (!$is_admin) {
            if (intval($charging['charging_status']) == ChargingStatus::$ChargingStatusNormal['code']) {
                $charging['tag'] = unserialize($charging['tag']);
                $out_data['code'] = 0;
                $out_data['data'] = $charging;
                $out_data['info'] = "充电桩数据获取成功";
                return $out_data;
            }
            $out_data['code'] = 840;
            $out_data['info'] = "充电桩数据无权获取";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $charging;
            $out_data['info'] = "充电桩数据获取成功";
            return $out_data;
        }
    }

    /**
     * 充电桩下线
     * @param $charging_id
     * @return bool
     *  0下线成功
     *  150 下线失败
     */
    public function addLockPile($charging_id)
    {
        $lock_charging_data = array(
            'charging_status' => ChargingStatus::$ChargingStatusLogoff['code']
        );
        if ($this->save($lock_charging_data, array('id' => $charging_id))) {
            return true;//充电桩下线成功
        }
        return false;//充电桩下线失败
    }

    /**
     * 充电桩正在使用中
     * @param $charging_id
     * @return bool
     */
    public function useLockCar($charging_id)
    {
        $lock_charging_data = array(
            'charging_status' => ChargingStatus::$ChargingStatusInuse['code']
        );
        if ($this->save($lock_charging_data, array('id' => $charging_id))) {
            return true;//充电桩下使用中
        }
        return false;//充电桩使用失败
    }

    /**
     * 充电桩正常上线（）
     * @param $charging_id
     * @return bool
     *  0上线成功
     *  150 上线失败
     */
    public function delLockCar($charging_id)
    {
        $lock_charging_data = array(
            'charging_status' => ChargingStatus::$ChargingStatusNormal['code']
        );
        if ($this->save($lock_charging_data, array('id' => $charging_id))) {
            return true;//充电桩上线成功
        }
        return false;//充电桩上线失败
    }

    /**
     * 累计出租数量
     * @param $charging_id
     * @return bool
     * @throws \think\Exception
     */
    public function rentCount($charging_id)
    {
        if ($this->where(array('id' => $charging_id))->setInc('rent_count', 1)) {
            return true;
        }
        return false;
    }

}