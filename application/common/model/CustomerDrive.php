<?php

namespace app\common\model;

/**
 *用户租车信息 数据模型
 */
use think\Model;

class CustomerDrive extends Model
{
    /**
     * 获取驾驶人员信息
     * @param int $id
     * @param int $customer_id
     * @param bool $is_default
     * @return mixed
     */
    public function getCustomerDrive($id, $customer_id, $is_default = false)
    {
        $out_data['code'] = 1;
        $out_data['info'] = "数据不存在";
        $map = array(
            'customer_id' => $customer_id
        );
        if (!empty($id)) {
            $map['id'] = $id;
        } else if ($is_default) {
            $map['is_default'] = 1;
        }
        $drive = $this->where($map)->find();
        if (empty($drive)) {
            $map = array(
                'customer_id' => $customer_id
            );
            $drive = $this->where($map)->find();
            if (empty($drive)) {
                return $out_data;
            }
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = array(
                'id' => $drive['id'],
                'customer_id' => $drive['customer_id'],
                'vehicle_drivers' => $drive['vehicle_drivers'],//驾驶人姓名
                'mobile_phone' => $drive['mobile_phone'],
                'id_number' => $drive['id_number'],
                'update_time' => $drive['update_time']
            );
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 获取驾驶人员信息 列表
     * @param int $customer_id
     * @return mixed
     */
    public function getListCustomerDrive($customer_id)
    {
        $out_data['code'] = 1;
        $out_data['info'] = "数据不存在";
        $map = array(
            'customer_id' => $customer_id
        );
        $drive_list = $this->where($map)->order('is_default DESC')->select();
        if (empty($drive_list)) {
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $drive_list;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 添加驾驶人员信息
     * @param $customer_drive
     * customer_id 客户id
     * vehicle_drivers 驾驶人姓名
     * mobile_phone 驾驶电话号码
     * id_number 驾驶身份证号码
     * @return array
     */
    public function addCustomerDrive($customer_drive)
    {
        $customer_id = $customer_drive['customer_id'];
        if (!is_numeric($customer_id)) {
            $out_data['code'] = 1;
            $out_data['info'] = "客户id格式非法";
            return $out_data;
        }
        $vehicle_drivers = $customer_drive['vehicle_drivers'];
        if (strlen($vehicle_drivers) < 2) {
            $out_data['code'] = 2;
            $out_data['info'] = "驾驶员姓名有误";
            return $out_data;
        }
        $mobile_phone = $customer_drive['mobile_phone'];
        if (check_mobile_number($mobile_phone) === false) {
            $out_data['code'] = 3;
            $out_data['info'] = "电话号码格式有误";
            return $out_data;
        }
        $id_number = $customer_drive['id_number'];
        if (idcard_checksum18($id_number) === false) {
            $out_data['code'] = 4;
            $out_data['info'] = "身份证号码不符合规范";
            return $out_data;//身份证号码不符合规范
        }
        $in_customer_drive = array(
            'customer_id' => $customer_id,
            'vehicle_drivers' => $vehicle_drivers,
            'mobile_phone' => $mobile_phone,
            'id_number' => $id_number,
            'update_time' => date("Y-m-d H:i:s")
        );
        if ($this->_verifyIdNumber($id_number, $customer_id)) {
            $out_data['code'] = 0;
            $out_data['info'] = "数据已存在";
            return $out_data;
        }
        if ($this->save($in_customer_drive)) {
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 5;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 跟新驾驶人员信息
     * @param $customer_drive
     * drive_id 数据id
     * customer_id 客户id
     * vehicle_drivers 驾驶人姓名
     * mobile_phone 驾驶电话号码
     * id_number 驾驶身份证号码
     * @return array
     */
    public function updateCustomerDrive($customer_drive)
    {
        $drive_id = $customer_drive['drive_id'];
        if (!is_numeric($drive_id)) {
            $out_data['code'] = 1;
            $out_data['info'] = "数据id格式非法";
            return $out_data;
        }
        $customer_id = $customer_drive['customer_id'];
        if (!is_numeric($customer_id)) {
            $out_data['code'] = 2;
            $out_data['info'] = "客户id格式非法";
            return $out_data;
        }
        $customer_drive_data = $this->where(array('id' => $drive_id, 'customer_id' => $customer_id))->find();
        $vehicle_drivers = $customer_drive['vehicle_drivers'];
        if (strlen($vehicle_drivers) < 2) {
            $out_data['code'] = 3;
            $out_data['info'] = "驾驶员姓名有误";
            return $out_data;
        }
        $customer_drive_data->vehicle_drivers = $vehicle_drivers;
        $mobile_phone = $customer_drive['mobile_phone'];
        if (check_mobile_number($mobile_phone) === false) {
            $out_data['code'] = 4;
            $out_data['info'] = "电话号码格式有误";
            return $out_data;
        }
        $customer_drive_data->mobile_phone = $mobile_phone;
        $id_number = $customer_drive['id_number'];
        if (idcard_checksum18($id_number) === false) {
            $out_data['code'] = 5;
            $out_data['info'] = "身份证号码不符合规范";
            return $out_data;//身份证号码不符合规范
        }
        $customer_drive_data->id_number = $id_number;
        $customer_drive_data->update_time = date("Y-m-d H:i:s");
        if ($customer_drive_data->save()) {
            $out_data['code'] = 0;
            $out_data['info'] = "更新成功";
            return $out_data;
        }
        $out_data['code'] = 6;
        $out_data['info'] = "更新失败";
        return $out_data;
    }


    /**
     * 设置默认驾驶人员信息
     * @param $drive_id 驾驶员id
     * @param $customer_id 客户id
     * @return mixed
     */
    public function defaultCustomerDrive($drive_id, $customer_id)
    {
        if (!is_numeric($drive_id)) {
            $out_data['code'] = 1;
            $out_data['info'] = "数据id格式非法";
            return $out_data;
        }
        $this->where(array('customer_id' => $customer_id))->setField('is_default', 0);
        $ret = $this->where(array('id' => $drive_id, 'customer_id' => $customer_id))->setField('is_default', 1);
        if ($ret) {
            $out_data['code'] = 0;
            $out_data['info'] = "设置成功";
            return $out_data;
        }
        $out_data['code'] = 2;
        $out_data['info'] = "设置失败";
        return $out_data;
    }

    /**
     * 删除驾驶人员信息
     * @param $customer_drive
     * drive_id 数据id
     * customer_id 客户id
     * @param bool $is_admin
     * @return mixed
     */
    public function deleteCustomerDrive($customer_drive, $is_admin = false)
    {
        $drive_id = $customer_drive['drive_id'];
        if (!is_numeric($drive_id)) {
            $out_data['code'] = 1;
            $out_data['info'] = "数据id格式非法";
            return $out_data;
        }
        if (!$is_admin) {
            $customer_id = $customer_drive['customer_id'];
            if (!is_numeric($customer_id)) {
                $out_data['code'] = 2;
                $out_data['info'] = "客户id格式非法";
                return $out_data;
            }
            if ($this->where(array('id' => $drive_id, 'customer_id' => $customer_id))->delete()) {
                $out_data['code'] = 0;
                $out_data['info'] = "删除成功";
                return $out_data;
            }
            $out_data['code'] = 3;
            $out_data['info'] = "删除失败";
            return $out_data;
        } else {
            if ($this->where(array('id' => $drive_id))->delete()) {
                $out_data['code'] = 0;
                $out_data['info'] = "删除成功";
                return $out_data;
            }
            $out_data['code'] = 4;
            $out_data['info'] = "删除失败";
            return $out_data;
        }
    }

    /**
     * 验证数据是否存在
     * @param $id_number
     * @param $customer_id
     * @return bool
     */
    public function _verifyIdNumber($id_number, $customer_id)
    {
        $drive = $this->where(array('customer_id' => $customer_id, 'id_number' => $id_number))->find();
        if (empty($drive)) {
            return false;
        }
        return true;
    }
}