<?php

namespace app\common\model;

/**
 * 维护订单管理 数据模型
 */

use definition\CarCmd;
use definition\CarStatus;
use definition\GoodsType;
use definition\OrderOperationType;
use definition\OrderStatus;
use think\cache\driver\Redis;
use think\Config;
use think\Model;
use tool\CarDeviceTool;

class OrderOperation extends Model
{
    private $customer_model;
    protected $insert = ['create_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_model = new Customer();
    }

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * 获取任务列表
     * @param array $map
     * @param string $order
     * @param $page_config
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPageList($map = array(), $order = '', $page_config, $limit = 8, $is_super_admin = false, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
                unset($value['order_goods']);
            }
        }
        return $order_list;
    }

    /**
     * 获取任务列表
     * @param array $map
     * @param string $order
     * @param $page
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($map = array(), $order = '', $page, $limit = 8, $is_super_admin = false, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        if (empty($limit)) {
            $order_list = $this->where($map)->order($order)->select();
        } else {
            $order_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        }
        $car_model = new CarCommon();
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
                $out_car_data = $car_model->where(['id' => $value['goods_id']])->field("store_site_id,store_site_name")->find();
                $value['store_site_id'] = $out_car_data['store_site_id'];
                $value['store_site_name'] = $out_car_data['store_site_name'];
                $value['car_device_str'] = "掉线";
                $value['car_device'] = 0;
                $value['driving_mileage'] = "无数据";
                $value['driving_mileage_num'] = 0;
                $value['energy'] = "无数据";
                $value['voltage'] = "无数据";
                $value['location_latitude'] = 0;
                $value['location_longitude'] = 0;
                $value['odometer'] = 0;
                $return_data_device = getCarDevice($value['goods_device']);
                if (empty($return_data_device['code'])) {
                    $data_device = $return_data_device['data'];
                    if (!empty($data_device['car_device'])) {
                        $value['car_device_str'] = $data_device['car_device_str'];
                        $value['car_device'] = $data_device['car_device'];
                        $value['driving_mileage'] = $data_device['driving_mileage'];
                        $value['driving_mileage_num'] = $data_device['driving_mileage'];
                        $value['energy'] = $data_device['energy'];
                        $value['voltage'] = $data_device['voltage'];
                        $value['location_latitude'] = $data_device['location_latitude'];
                        $value['location_longitude'] = $data_device['location_longitude'];
                        $value['odometer'] = $data_device['odometer'];
                    }
                }
            }
        }
        return $order_list;
    }

    /**
     * 获取任务结束列表
     * @param array $map
     * @param string $order
     * @param $page
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getEndList($map = array(), $order = 'id DESC', $page, $limit = 8, $is_super_admin = false, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $order_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
                unset($value['acquire_img']);
                unset($value['return_img']);
            }
        }
        return $order_list;
    }


    /**
     * 格式化常规订单数据
     * @param $data
     */
    public function formatx(&$data)
    {
        if (empty($data['finnshed_time'])) {
            $data['finnshed_time'] = "未完成";
        } else {
            $data['finnshed_time'] = date('Y-m-d H:i:s', $data['finnshed_time']);
        }
        $data['order_type_str'] = "未知类型";
        if (!empty($data['order_type'])) {
            $data['order_type_str'] = OrderOperationType::$ORDEROPERATIONTYPE_CODE[intval($data['order_type'])];
        }
        $order_status = OrderStatus::$ORDER_OP_STATUS_CODE;
        $data['order_status_str'] = $order_status[$data['order_status']];
        $data['acquire_time_str'] = date("Y-m-d H:i:s", $data['acquire_time']);
        $data['all_time_str'] = round(($data['return_time'] - $data['acquire_time']) / 3600, 2);
        $data['return_time_str'] = date("Y-m-d H:i:s", $data['return_time']);
        $data['is_unusual_str'] = "是";
        if (empty($data['is_unusual'])) {
            $data['is_unusual_str'] = "否";
        }
        $data['is_fail_str'] = "合格";
        if (!empty($data['is_fail'])) {
            $data['is_fail_str'] = "不合格";
        }
    }

    /**
     * 获取任务信息(通过id)
     * @param string $order_id
     * @param string $operation_id
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderOperation($order_id = '', $operation_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是管理员操作
        if (!empty($operation_id)) {
            $map['operation_id'] = $operation_id;
        }
        //判断是不是 超级管理员 并且没有管理员操作 如果不是 过滤数据
        if (!$is_super_admin && empty($operation_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        $this->formatx($order);
        $car_model = new CarCommon();
        $car_data = $car_model->where(['id' => $order['goods_id']])->field("store_site_id,store_site_name")->find();
//        $order = json_encode($order);
//        $order = json_decode($order,true);
        $order['store_site_id'] = $car_data['store_site_id'];
        $order['store_site_name'] = $car_data['store_site_name'];
        if (empty($order)) {
            $out_data['code'] = 1190;
            $out_data['info'] = "任务不存在";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $order;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 获取任务信息(通过编号)
     * @param string $order_sn
     * @param string $customer_id
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderSnOperation($order_sn = '', $customer_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_sn)) {
            return false;
        }
        $map = array();
        $map['order_sn'] = $order_sn;
        //验证是否是管理员操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有管理员操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        $this->formatx($order);
        if (empty($order)) {
            $out_data['code'] = 1190;
            $out_data['info'] = "订单不存在";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $order;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 接受任务
     * @param $operation_data 任务信息
     * goods_id 商品id
     * licence_plate 车牌号
     * goods_device 设备编号
     * goods_name 商品名称
     * goods_type 商品类
     * order_type 任务类型
     * order_goods 任务信息 ['goods_name'=>'任务名称','goods_price'=>'任务单价','goods_img'=>'任务图片']
     * acquire_store_id 取用点id
     * @param $operation_info 管理员信息
     * operation_id 接单人员id
     * operation_name 接单人员姓名
     * operation_phone 接单人员电话
     * customer_notes 接单人员备注信息
     * store_key_id 归属总店id
     * store_key_name 归属总店名称
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addOrderOperation($operation_data, $operation_info)
    {
        $out_data = array(
            'code' => 1111,
            'info' => '参数有误'
        );
        if (!is_numeric($operation_info['operation_id'])) {
            $out_data['code'] = 1116;
            $out_data['info'] = '管理员id不合法';
            return $out_data;
        }
        $car_model = new CarCommon();
        $car_data = $car_model->where(['id' => $operation_data['goods_id']])->field("car_status")->find();
        if (intval($car_data['car_status']) > CarStatus::$CarStatusNormal['code']) {
            if (intval($car_data['car_status']) == CarStatus::$CarStatusInuse['code']) {
                $out_data['code'] = 1122;
                $out_data['info'] = '用户使用中，无法维护';
                return $out_data;
            } else if (intval($car_data['car_status']) == CarStatus::$CarStatusVindicate['code']) {
                $out_data['code'] = 1123;
                $out_data['info'] = '运维人员正在维护，无法获取车辆控制权';
                return $out_data;
            } else if (intval($car_data['car_status']) == CarStatus::$CarStatusRent['code']) {
                $out_data['code'] = 1124;
                $out_data['info'] = '该车已被长期租用，无法维护';
                return $out_data;
            }
        }
        $map_oper = ['goods_id' => $operation_data['goods_id'], 'order_status' => ['between', OrderStatus::$OrderStatusNopayment['code'] . "," . OrderStatus::$OrderStatusAcquire['code']]];
        $operation_data_temp = $this->where($map_oper)->find();
        if (!empty($operation_data_temp)) {
            $out_data['code'] = 1121;
            $out_data['info'] = '该车辆正在被其他运维人员维护';
            return $out_data;
        }
        $map_op = ['operation_id' => $operation_info['operation_id'], 'order_status' => ['between', OrderStatus::$OrderStatusNopayment['code'] . "," . OrderStatus::$OrderStatusAcquire['code']]];
        $count = $this->where($map_op)->count();
        if ($count >= 10) {
            $out_data['code'] = 1117;
            $out_data['info'] = '任务接车不得超过10辆';
            return $out_data;
        }
        $order_sn = $this->_create_order_sn();
//        1补电小保洁 2大保洁 3调度 6小电瓶补电掉线 7移车
        if (!is_numeric($operation_data['goods_id'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '任务id不合法';
            return $out_data;
        } else if ($operation_data['order_type'] == 0) {
            $operation_list_model = new CarOperationList();
            $ret_task = $operation_list_model->isHasOperation($operation_data['goods_id']);
            if ($ret_task == 0) {
                $out_data['code'] = 1105;
                $out_data['info'] = '任务任务已被抢走';
                return $out_data;
            }
//            故障类型 1续航低于50km  2车机掉线  3小电瓶电压过低 6停车费用 7平台任务
            if ($ret_task == 1) {
                $order_type = 1;
            } else if ($ret_task == 2 || $ret_task == 3) {
                $order_type = 6;
            } else if ($ret_task == 7) {
                $order_type = 7;
            } else {
                $order_type = 3;
            }
            $operation_data['order_type'] = $order_type;
        }
        if (empty($operation_data['goods_name'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '任务名称不合法';
            return $out_data;
        }
        if (!is_numeric($operation_data['goods_type'])) {
            $out_data['code'] = 1106;
            $out_data['info'] = '任务类型不合法';
            return $out_data;
        }
        if (empty($operation_data['order_goods'])) {
            $out_data['code'] = 1107;
            $out_data['info'] = '任务信息不合法';
            return $out_data;
        }
        if (check_mobile_number($operation_info['operation_phone']) === false) {
            $out_data['code'] = 1116;
            $out_data['info'] = '管理员电话不合法';
            return $out_data;
        }
        $orderinfotemp = array(
            'order_sn' => $order_sn,//订单号
            'goods_id' => $operation_data['goods_id'],//任务id
            'series_id' => $operation_data['series_id'],//车系id
            'licence_plate' => $operation_data['licence_plate'],//任务车牌号
            'goods_device' => $operation_data['goods_device'],//任务设备编号
            'goods_name' => $operation_data['goods_name'],//任务名称
            'goods_type' => $operation_data['goods_type'],//车辆类型
            'order_type' => $operation_data['order_type'],//任务类型
            'order_goods' => serialize($operation_data['order_goods']),//任务信息
            'operation_id' => $operation_info['operation_id'],//管理员id
            'operation_name' => $operation_info['operation_name'],//管理员电话
            'operation_phone' => $operation_info['operation_phone'],//管理员电话
            'operation_notes' => $operation_info['operation_notes'],//管理员备注信息
            'order_status' => OrderStatus::$OrderStatusNopayment['code'],//任务状态 已接受
            'create_time' => time(),//订单创建时间
            'store_key_id' => $operation_info['store_key_id'],//总店铺id
            'store_key_name' => $operation_info['store_key_name'],//总店铺名称
        );
        if ($this->save($orderinfotemp)) {
            $order_id = $this->getLastInsID();
            $operation_list_model = new CarOperationList();
            $operation_list_model->endOperationList($operation_data['goods_id']);
            $this->_taskSucceed($operation_data['goods_id']);
            $out_data['code'] = 0;
            $out_data['data'] = $order_sn;
            $out_data['order_id'] = $order_id;
            $out_data['info'] = '添加成功';
            return $out_data;
        }
        $out_data['code'] = 1121;
        $out_data['info'] = '添加失败';
        return $out_data;
    }

    /**
     * 执行任务
     * @param $operation_data
     * @param $operation_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function acquireOrderOperation($operation_data, $operation_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "取用失败"
        ];
        $oper_id = $operation_data['id'];
        $operation_out_data = $this->where(['operation_id' => $operation_id, 'id' => $oper_id])->find();
        if (!empty($operation_out_data)) {
            $odometer = 0;
            $return_data_device = getCarDevice($operation_out_data['goods_device']);
            if (empty($return_data_device['code'])) {
                $data_device = $return_data_device['data'];
                if (!empty($data_device['odometer'])) {
                    $odometer = $data_device['odometer'];
                }
            }
            $update_operation = [
                'acquire_store_id' => $operation_data['acquire_store_id'],
                'acquire_store_name' => $operation_data['acquire_store_name'],
                'acquire_time' => time(),
                'acquire_mileage' => $odometer,
                'order_status' => 30,
                'acquire_img' => serialize($operation_data['acquire_img'])
            ];
            $ret = $this->save($update_operation, ['id' => $operation_data['id']]);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "提交成功";
                return $out_data;
            }
        }
//        $this->_acquireError($oper_id);
        return $out_data;
    }

    /**
     * 成功获取任务
     * @param $id
     * @param $car_id
     * @return bool
     */
    public function _taskSucceed($car_id)
    {
        $car_model = new CarCommon();
        if ($car_model->vindicateCar($car_id)) {
            return true;
        }
        return false;
    }

    /**
     * 维护取车失败
     * @param $id
     * @return bool
     */
    public function _acquireError($id)
    {
        $up_data = [
            'order_status' => OrderStatus::$OrderStatusCanceled['code']
        ];
        $ret = $this->where(['id' => $id])->setField($up_data);
        if ($ret !== false) {
            return true;
        }
        return false;
    }

    /**
     *提交相关数据 归还商品
     * @param $operation_id
     * @param $operation_data
     * is_unusual 是否异常提交
     * is_fail 0正常 1不合格
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function submitOrderOperation($operation_id, $operation_data)
    {
        $operation_out_data = $this->where(['operation_id' => $operation_id, 'id' => $operation_data['id']])->find();
        $store_model = new Store();
        $return_store_data = $store_model->where(array('id' => $operation_data['return_store_id'], 'store_status' => 0))->field('store_name,location_longitude,location_latitude,store_scope')->find();
        if (empty($return_store_data)) {
            $out_data['code'] = 1009;
            $out_data['info'] = '请选择可用的还车点';
            return $out_data;
        }
        $operation_data['store_name'] = $return_store_data['store_name'];
        $operation_data['odometer'] = 0;
        if (empty($operation_data['is_unusual'])) {
            $return_data_device = getCarDevice($operation_out_data['goods_device']);
            if (empty($return_data_device['code'])) {
                $course = Config::get('course');
                $data_device = $return_data_device['data'];
                if ($data_device['car_device'] == 0) {
                    $out_data['code'] = 1013;
                    $out_data['info'] = '车机掉线，请报备异常';
                    return $out_data;
                }
                if (!empty($data_device['odometer'])) {
                    $operation_data['odometer'] = $data_device['odometer'];
                }
                $driving_mileage_num = $data_device['driving_mileage_num'];
                if ($driving_mileage_num < $course[intval($operation_out_data['series_id'])]['online_drive_km']) {
                    if (intval($operation_data['order_type']) != OrderOperationType::$OrderTypeMove['code']) {
                        if (intval($operation_data['order_type']) != OrderOperationType::$OrderTypeClean['code']) {
                            $out_data['code'] = 1011;
                            $out_data['info'] = '车辆续航没有达到指定要求';
                            return $out_data;
                        }
                    }
                }
            } else {
                $out_data['code'] = 1010;
                $out_data['info'] = '车机数据有误无法归还';
                return $out_data;
            }
        }
        $out_data['code'] = 0;
        $operation_model = new OrderOperation();
        $operation_data['is_fail'] = 0;
        $operation_data['is_fail_info'] = "";
        $ret = $operation_model->endOrderOperation($operation_out_data['id'], $operation_id, $operation_data);
        if ($ret !== false) {
            return array('code' => 0, 'info' => "归还成功");
        } else {
            return array('code' => 90, 'info' => "归还车辆失败,请查看车辆是否满足归还条件");
        }
    }

    /**
     *提交相关数据 归还商品
     * @param $operation_id
     * @param $operation_data
     * operation_id
     * is_fail 0正常 1不合格
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function submitOrderOperationFail($operation_id, $operation_data)
    {
        $operation_out_data = $this->where(['operation_id' => $operation_id, 'id' => $operation_data['id']])->find();
        $store_model = new Store();
        $return_store_data = $store_model->where(array('id' => $operation_data['return_store_id'], 'store_status' => 0))->field('store_name,location_longitude,location_latitude,store_scope')->find();
        if (empty($return_store_data)) {
            $out_data['code'] = 1009;
            $out_data['info'] = '请选择可用的还车点';
            return $out_data;
        }
        $is_fail = 0;
        $operation_data['store_name'] = $return_store_data['store_name'];
        $operation_data['odometer'] = 0;
        $return_data_device = getCarDevice($operation_out_data['goods_device']);
        $is_fail_info = "车机掉线，请报备异常";
        if (empty($return_data_device['code'])) {
            $course = Config::get('course');
            $data_device = $return_data_device['data'];
            if ($data_device['car_device'] == 0) {
                $out_data['code'] = 1013;
                $out_data['info'] = '车机掉线，请报备异常';
                $is_fail = 1;
                $is_fail_info = "车机掉线，请报备异常";
            } else {
                $operation_data['odometer'] = $data_device['odometer'];
                if ($data_device['driving_mileage_num'] < $course[intval($operation_out_data['series_id'])]['online_drive_km']) {
                    $out_data['code'] = 1011;
                    $out_data['info'] = '车辆续航没有达到指定要求';
                    $is_fail = 1;
                    $is_fail_info = "车辆续航没有达到指定要求";
                }
            }
        } else {
            $out_data['code'] = 1010;
            $out_data['info'] = '车机数据有误无法归还';
            $is_fail = 1;
            $is_fail_info = "车机掉线，请报备异常";
        }
        $out_data['code'] = 0;
        $operation_model = new OrderOperation();
        $operation_data['is_unusual'] = 0;
        $operation_data['is_fail'] = $is_fail;
        $operation_data['is_fail_info'] = $is_fail_info;
        $ret = $operation_model->endOrderOperation($operation_data['id'], $operation_id, $operation_data, true);
        if ($ret !== false) {
            return array('code' => 0, 'info' => "归还成功");
        } else {
            return array('code' => 90, 'info' => "归还车辆失败,请查看车辆是否满足归还条件");
        }
    }


    /**
     * 确认任务
     * @param string $order_id
     * @param string $operation_id
     * @param array $operation_data
     * return_store_id
     * store_name
     * return_img
     * order_type
     * car_status
     * odometer
     * is_unusual
     * notes
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function endOrderOperation($order_id = '', $operation_id = '', $operation_data, $is_fail = false)
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        $map['operation_id'] = $operation_id;
        $order = $this->where($map)->find();
        if ($order['order_status'] == OrderStatus::$OrderStatusAcquire['code'] || $is_fail) {
            $order->order_status = OrderStatus::$OrderStatusFinish['code'];
            $order->return_store_id = $operation_data['return_store_id'];
            $order->return_store_name = $operation_data['store_name'];
            $order->order_type = $operation_data['order_type'];
            $order->car_status = $operation_data['car_status'];
            $order->is_unusual = $operation_data['is_unusual'];
            $order->is_fail = $operation_data['is_fail'];
            $order->is_fail_info = $operation_data['is_fail_info'];
            $order->notes = $operation_data['notes'];
            $order->all_mileage = $operation_data['odometer'] - intval($order['acquire_mileage']);
            $order->return_mileage = $operation_data['odometer'];
            $order->return_img = serialize($operation_data['return_img']);
            $order->return_time = time();
            $order->finnshed_time = date("Y-m-d H:i:s");
            $goods_type = $order['goods_type'];
            $goods_id = $order['goods_id'];
            $goods_device = $order['goods_device'];
            if ($order->save()) {
                //如果是新能源车  上线操作
                if (intval($goods_type) == GoodsType::$GoodsTypeElectrocar['code']) {
                    $car_common_model = new CarCommon();
                    $up_data = [
                        'car_status' => $operation_data['car_status'],
                        'store_site_id' => $operation_data['return_store_id'],
                        'store_site_name' => $operation_data['store_name'],
                        'update_time' => date("Y-m-d H:i:s"),
                    ];
                    $car_common_model->where(['id' => $goods_id])->setField($up_data);
                    $device_tool = new CarDeviceTool();
                    $device_tool->powerFailure($goods_device);
                    $device_tool->closeDoor($goods_device);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * 取消已结任务
     * @param string $order_id
     */
    public function cancelOperation($id = '')
    {
        $return_data = array(
            'code' => 100,
            'info' => '参数有误'
        );
        if (empty($id)) {
            return $return_data;
        }
        $car_operation = $this->where(['id' => $id])->field('id,licence_plate,goods_id,order_status')->find();
        $car_operation->order_status = OrderStatus::$OrderStatusCanceled['code'];
        $licence_plate = $car_operation['licence_plate'];
        $goods_id = $car_operation['goods_id'];
        if ($car_operation->save()) {
            $car_operation_list_model = new CarOperationList();
            $car_operation_list_model->where(['licence_plate' => $licence_plate])->order('id DESC')->limit(1)->delete();
            $car_model = new CarCommon();
            $car_model->delLockCar($goods_id);
        }
        return $return_data;
    }

    /**
     * 任务命令控制
     * @param string $order_id
     * @param string $operation_id
     * @param string $cmd
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function OrderCarCmd($order_id = '', $operation_id = '', $cmd = '')
    {
        $out_data = array(
            'code' => 200,
            'info' => '操作无效'
        );
        $redis = new Redis();
        if ($redis->has($order_id . "" . $operation_id . "" . $cmd)) {
            $out_data['code'] = 201;
            $out_data['info'] = "时间间隔太短，请稍后操作";
            return $out_data;
        }
        $order_data = $this->where(array('id' => $order_id, 'operation_id' => $operation_id))->field('goods_id,goods_device,order_status')->find();
        $order_status = intval($order_data['order_status']);
        if ($order_status == 10 || $order_status == 30) {
            if (empty($order_data)) {
                return $out_data;
            }
            $redis = new Redis();
            if (!$redis->has("login:" . $order_data['goods_device'])) {
                $out_data['code'] = 101;
                $out_data['info'] = "车机已掉线";
                return $out_data;
            } else {
                $out_data_device = $redis->get("status:" . $order_data['goods_device']);
                if (empty($out_data_device)) {
                    $out_data['code'] = 102;
                    $out_data['info'] = "车机数据有误";
                    return $out_data;
                }
            }
            $car_device_model = new CarDeviceTool();
            switch (intval($cmd)) {
                case CarCmd::$CarCmdFind['code']:
                    $out_data = $car_device_model->findCar($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
                case CarCmd::$CarCmdOpenDoor['code']:
                    $out_data = $car_device_model->openDoor($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
                case CarCmd::$CarCmdCloseDoor['code']:
                    $out_data = $car_device_model->closeDoor($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
                case CarCmd::$CarCmdIgnite['code']:
                    $out_data = $car_device_model->powerSupply($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
                case CarCmd::$CarCmdFlameout['code']:
                    $out_data = $car_device_model->powerFailure($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
                case CarCmd::$CarCmdAcquire['code']:
                    $out_data = $car_device_model->startOrder($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
                case CarCmd::$CarCmdReturn['code']:
                    $out_data = $car_device_model->clearOrder($order_data['goods_device']);
                    if (intval($out_data['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "控制指令发送成功";
                        $redis->set($order_id . "" . $operation_id . "" . $cmd, "has", 10);
                        return $out_data;
                    }
                    break;
            }
        }
        return $out_data;
    }

    /**
     * 获取订单号
     * @return string
     */
    private function _create_order_sn()
    {
        $sn = "operation_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }
}