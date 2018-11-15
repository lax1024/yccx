<?php

namespace app\common\model;

/**
 * 订单活动（） 数据模型
 *  统计一个月里程
 *  超过200 km 赠送 30元代金券
 *  超过500 km 赠送 50元代金券
 *  20181006 改动
 *  超过200 km 赠送 20元代金券
 *  超过500 km 赠送 30元代金券
 */

use definition\OrderStatus;
use think\Model;

class OrderActivity extends Model
{
    /**
     * 判断是否可以获得
     * @param $customer_id
     * @return bool
     */
    private function isAddActivity($customer_id)
    {
        $map = [
            'customer_id' => $customer_id,
            'date' => date("Y-m"),
        ];
        $count = $this->where($map)->count();
        if ($count < 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否可以获得
     * @param $customer_id
     * @return bool
     */
    private function isAddActivityPara($customer_id, $mileage)
    {
        $map = [
            'customer_id' => $customer_id,
            'mileage' => $mileage,
            'date' => date("Y-m"),
        ];
        $count = $this->where($map)->count();
        if ($count <= 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否可以获取代金券
     * @param $customer_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addActivity($customer_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "获取失败"
        ];
        $map_time = date("Y-m");
//        file_put_contents("add_activity.txt", $customer_id);
        if ($this->isAddActivity($customer_id)) {
            $map = [
                'customer_id' => $customer_id,
                'order_status' => OrderStatus::$OrderStatusFinish['code'],
                'return_month' => $map_time
            ];
            $order_model = new Order();
            $order_data = $order_model->where($map)->field('acquire_mileage,return_mileage')->select();
            if (empty($order_data)) {
                return $out_data;
            } else {
                $acquire_mileage = 0;
                $return_mileage = 0;
                foreach ($order_data as $v) {
                    if (!empty($v)) {
                        $acquire_mileage += intval($v['acquire_mileage']);
                        $return_mileage += intval($v['return_mileage']);
                    }
                }
                $mileage = $return_mileage - $acquire_mileage;
                if ($mileage >= 100 && $mileage < 200) {
                    if ($this->isAddActivityPara($customer_id, 100)) {
//                        if ($this->addCoupon($customer_id, 5)) {
                            $this->addActivityData($customer_id, 100, 0);
                            $out_data['code'] = 0;
                            $out_data['info'] = "获取成功";
                            return $out_data;
//                        }
                    }
                } else if ($mileage >= 200 && $mileage < 500) {
                    if ($this->isAddActivityPara($customer_id, 200)) {
                        //if ($this->addCoupon($customer_id, 5)) {
                            $this->addActivityData($customer_id, 200, 5);
                            $out_data['code'] = 0;
                            $out_data['info'] = "获取成功";
                            return $out_data;
                        //}
                    }
                } else if ($mileage >= 500) {
                    if ($this->isAddActivityPara($customer_id, 500)) {
                        //if ($this->addCoupon($customer_id, 10)) {
                            $this->addActivityData($customer_id, 500, 10);
                            $out_data['code'] = 0;
                            $out_data['info'] = "获取成功";
                            return $out_data;
                        //}
                    }
                }
                $out_data['code'] = 101;
                $out_data['info'] = "获取失败";
                return $out_data;
            }
        }
        return $out_data;
    }

    private function addCoupon($customer_id, $coupon = 30)
    {
        $customer_coupon_model = new CustomerCoupon();
        $end_time = (3600 * 24 * 30 * 3);
        $channel_in_coupon = [
            'customer_id' => $customer_id,
            'coupon_code' => "activity",
            'coupon_type' => $coupon,
            'remark' => "订单活动发放," . $coupon . "代金券",
            'explain' => "只可抵扣大于" . $coupon . "元的费用",
            'end_time' => $end_time,
            'admin_id' => 0,
            'admin_name' => ""
        ];
        $ret = $customer_coupon_model->add_coupon($channel_in_coupon);
        return $ret;
    }

    private function addActivityData($customer_id, $mileage = 200, $coupon = 30)
    {
        $in_activity = [
            'customer_id' => $customer_id,
            'mileage' => $mileage,
            'date' => date("Y-m"),
            'coupon' => $coupon
        ];
        $ret = $this->save($in_activity);
        if ($ret !== false) {
            return true;
        }
        return false;
    }
}