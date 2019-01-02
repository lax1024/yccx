<?php

namespace app\common\model;

/**
 *预定用车模型
 */

use think\Config;
use think\Model;
use tool\CarDeviceTool;

class Reserve extends Model
{
    /**
     *添加预定
     * @param $goods_id
     * @param $customer_id
     * @param $mobile_phone
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addReserve($goods_id, $customer_id, $mobile_phone = '')
    {
        $out_data = array(
            'code' => 100,
            'info' => "预定失败"
        );
        if (empty($goods_id) || empty($customer_id)) {
            return $out_data;
        }
        $car_model = new CarCommon();
        $out_car_data = $car_model->getCarCommonField($goods_id, 'series_id,licence_plate');
        $reserve_mileage = Config::get('reserve_mileage');
        if (!empty($out_car_data['code'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "车辆数据有误，预订失败";
            return $out_data;
        }
        $car_data = $out_car_data['data'];
        if (intval($car_data['driving_mileage_num']) <= $reserve_mileage) {
            $operation_per_model = new CarOperationPer();
            $operation_per_list = $operation_per_model->getOperationPerList('');
            $operation_per_arr = [];
            foreach ($operation_per_list['data'] as $value) {
                $operation_per_arr[] = $value['phone'];
            }
            if (!in_array($mobile_phone, $operation_per_arr)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车辆续航低于" . $reserve_mileage . "km，无法租用";
                return $out_data;
            }
        }
        //判断是否有违章
        $car_regulations_model = new CarRegulations();
        $out_data = $car_regulations_model->is_return_cash($customer_id);
        if (!empty($out_data['code'])) {
            return $out_data;
        }
        $out_data = $this->isReserve($customer_id, $goods_id);
        if ($out_data['code'] == 0) {
            $in_reserve = [
                'goods_id' => $goods_id,
                'customer_id' => $customer_id,
                'create_time' => time(),
                'create_date' => date("Y-m-d"),
                'end_time' => date("Y-m-d H:i:s"),
            ];
            $ret = $this->save($in_reserve);
            if ($ret === false) {
                $out_data = array(
                    'code' => 100,
                    'info' => "预定失败"
                );
                return $out_data;
            }
            $reserve_time = Config::get('reserve_time');
            $in_reserve['surplus_time'] = $reserve_time - intval(time() - $in_reserve['create_time']);
            if ($in_reserve['surplus_time'] < 3) {
                $in_reserve['surplus_time'] = 0;
            }
            $out_data['code'] = 0;
            $out_data['data'] = $in_reserve;
            $out_data['goods_id'] = $goods_id;
            $out_data['info'] = "预定成功";
            return $out_data;
        }
        return $out_data;
    }

    /**
     * 取消预定
     * @param $goods_id
     * @param $customer_id
     * @return array
     */
    public function cancelReserve($goods_id, $customer_id)
    {
        $out_data = array(
            'code' => 100,
            'info' => "取消失败"
        );
        if (empty($goods_id)) {
            return $out_data;
        }
        $ret = $this->where(['goods_id' => $goods_id, 'customer_id' => $customer_id, 'reserve_status' => 10])->setField(['reserve_status' => 0]);
        if ($ret) {
            $count = $this->where(['create_date' => date("Y-m-d"), 'customer_id' => $customer_id, 'reserve_status' => 0])->count();
            $out_data['code'] = 0;
            $out_data['data'] = $count;
            $out_data['info'] = "取消成功";
        }
        return $out_data;
    }

    /**
     * 完成预定
     * @param $goods_id
     * @param $customer_id
     * @return array
     */
    public function endReserve($goods_id, $customer_id)
    {
        $out_data = array(
            'code' => 100,
            'info' => "完成失败"
        );
        if (empty($goods_id)) {
            return $out_data;
        }
        $ret = $this->where(['goods_id' => $goods_id, 'customer_id' => $customer_id, 'reserve_status' => 10])->setField(['reserve_status' => 20]);
        if ($ret) {
            $out_data['code'] = 0;
            $out_data['info'] = "完成成功";
        }
        return $out_data;
    }

    /**
     * 获取预定信息
     * @param $customer_id
     * @return array
     */
    public function getReserve($customer_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "获取失败"
        ];
        $this->updateReserve($customer_id);
        $data = $this->where(['customer_id' => $customer_id, 'reserve_status' => 10])->find();
        if (!empty($data)) {
            $reserve_time = Config::get('reserve_time');
            $data['surplus_time'] = $reserve_time - intval(time() - $data['create_time']);
            if ($data['surplus_time'] < 3) {
                $data['surplus_time'] = 0;
            }
            $out_data['code'] = 0;
            $out_data['data'] = $data;
            $out_data['info'] = "获取成功";
        }
        return $out_data;
    }

    /**
     * 判断是否可以预定
     * @param $customer_id
     * @param $goods_id
     * @return array
     */
    public function isReserve($customer_id, $goods_id = '')
    {
        $out_data = [
            'code' => 101,
            'info' => "每天取消的预定不得大于4次"
        ];
        $map['create_date'] = date("Y-m-d");
        $map['customer_id'] = $customer_id;
        $map['reserve_status'] = 0;
        $count = $this->where($map)->count();
        if ($count >= 4) {
            return $out_data;
        }
        if (!empty($goods_id)) {
            $data = $this->where(['goods_id' => $goods_id, 'reserve_status' => 10])->field('id')->find();
            if (!empty($data)) {
                $out_data['code'] = 102;
                $out_data['info'] = "当前车辆已被其他用户预订";
                return $out_data;
            }
        }
        $data = $this->where(['customer_id' => $customer_id, 'reserve_status' => 10])->field('id')->find();
        if (empty($data)) {
            $out_data['code'] = 0;
            $out_data['info'] = "可以预定";
            return $out_data;
        } else {
            $out_data['code'] = 103;
            $out_data['goods_id'] = $goods_id;
            $out_data['info'] = "当前已有预定车辆";
            return $out_data;
        }
    }

    /**
     * 判断是否可以租用
     * @param $goods_id
     * @return array
     */
    public function isReserveCar($goods_id)
    {
        $this->updateReserve('');
        $out_data = [
            'code' => 101,
            'info' => "已被预约,无法租用"
        ];
        $map['customer_id'] = $goods_id;
        $map['reserve_status'] = 10;
        $reserve_data = $this->where($map)->find();
        if (empty($reserve_data)) {
            return $out_data;
        } else {
            $out_data = [
                'code' => 0,
                'info' => "可以租用"
            ];
            return $out_data;
        }
    }

    /**
     * 预约找车
     * @param $goods_id
     * @param $customer_id
     * @return array
     */
    public function findCar($goods_id, $customer_id)
    {
        $out_data = array(
            'code' => 100,
            'info' => "无权操作"
        );
        $this->updateReserve($customer_id);
        $data = $this->where(['customer_id' => $customer_id, 'goods_id' => $goods_id, 'reserve_status' => 10])->find();
        if (!empty($data)) {
            $car_common_model = new CarCommon();
            $car_device_data = $car_common_model->where(['id' => $goods_id])->field('device_number')->find();
            $car_device_tool = new CarDeviceTool();
            $out_data = $car_device_tool->findCar($car_device_data['device_number']);
            return $out_data;
        }
        return $out_data;
    }

    /**
     * 返回预定的数据列表
     * @return array
     */
    public function getReserveKeyID()
    {
        $this->updateReserve();
        $map['reserve_status'] = 10;
        $reserve_data_list = $this->where($map)->field('goods_id')->select();
        $reserve_list = array();
        foreach ($reserve_data_list as $value) {
            $reserve_list[intval($value['goods_id'])] = intval($value['goods_id']);
        }
        return $reserve_list;
    }

    /**
     * 刷新预定数据
     * @param string $customer_id
     * @return bool
     */
    public function updateReserve($customer_id = '')
    {
        $reserve_time = Config::get('reserve_time');
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        $map['reserve_status'] = 10;
        $map['create_time'] = ['lt', time() - $reserve_time];
        $this->where($map)->setField('reserve_status', 0);
        return true;
    }

}